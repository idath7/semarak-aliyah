<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  initialTab: { type: String, default: 'overview' },
  initialComp: { type: String, default: 'albanjari' },
  stats: { type: Object, default: () => ({}) },
  competitions: { type: Array, default: () => [] },
  recentCheckins: { type: Array, default: () => [] },
  registrations: { type: Array, default: () => [] },
  scores: { type: Array, default: () => [] },
  matches: { type: Array, default: () => [] },
  defaultRubrics: { type: Object, default: () => ({}) },
});

const { confirm: confirmDialog } = useConfirmDialog();

// Navigation state
const activeTab = ref(props.initialTab || 'overview');
const activeCompetitionId = ref(props.initialComp || (props.competitions[0]?.id || 'albanjari'));

// Live reactive data states
const localStats = ref({ ...props.stats });
const checkinsList = ref([...props.recentCheckins]);
const delegatesList = ref([...props.registrations]);
const scoresList = ref([...props.scores]);
const matchesList = ref([...props.matches]);
const liveEventLog = ref([]);

// WebSocket connection state
const wsConnected = ref(false);
let echoInstance = null;

// Audio synthesis for barcode scanner (Web Audio API)
function playBeep(success = true) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);

    if (success) {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.08);
      gain.gain.setValueAtTime(0.25, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.22);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.22);
    } else {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(220, ctx.currentTime);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.28);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.28);
    }
  } catch (e) {}
}

// Switch tabs and update browser URL without full-page reload (SPA)
function setTab(tab) {
  activeTab.value = tab;
  const url = new URL(window.location.href);
  url.searchParams.set('tab', tab);
  if (tab === 'juri' || tab === 'turnamen') {
    url.searchParams.set('comp', activeCompetitionId.value);
  } else {
    url.searchParams.delete('comp');
  }
  window.history.replaceState({}, '', url.toString());
}

function selectCompetition(comp) {
  activeCompetitionId.value = comp.id;
  const url = new URL(window.location.href);
  url.searchParams.set('tab', activeTab.value);
  url.searchParams.set('comp', comp.id);
  window.history.replaceState({}, '', url.toString());

  // Load rubric and active scores for this competition
  loadCompetitionRubric(comp.id);
}

// -------------------------------------------------------------
// TAB 2: GATE BARCODE SCANNER LOGIC
// -------------------------------------------------------------
const barcodeInput = ref('');
const barcodeInputRef = ref(null);
const lastScannedParticipant = ref(null);
const scanFeedback = ref(null); // { type: 'success' | 'warn' | 'error', text: '' }
const isScanning = ref(false);

async function handleScanSubmit() {
  const code = barcodeInput.value.trim();
  if (!code) return;

  isScanning.value = true;
  scanFeedback.value = null;

  try {
    const res = await fetch('/api/operasional/gate/scan', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ code }),
    });
    const data = await res.json();

    if (data.success) {
      lastScannedParticipant.value = data.delegate;
      playBeep(!data.alreadyCheckedIn);

      if (data.alreadyCheckedIn) {
        scanFeedback.value = {
          type: 'warn',
          text: `Peserta ${data.delegate.school_name} SUDAH check-in sebelumnya!`,
        };
      } else {
        scanFeedback.value = {
          type: 'success',
          text: `CHECK-IN BERHASIL! Selamat bertanding ${data.delegate.school_name}.`,
        };

        // Update local stats and recent check-ins
        localStats.value.hadir++;
        if (localStats.value.belumHadir > 0) localStats.value.belumHadir--;
        if (localStats.value.total > 0) {
          localStats.value.persenKehadiran = Math.round((localStats.value.hadir / localStats.value.total) * 100);
        }

        const existingIdx = checkinsList.value.findIndex(c => c.id === data.delegate.id);
        if (existingIdx === -1) {
          checkinsList.value.unshift(data.delegate);
        } else {
          checkinsList.value[existingIdx] = data.delegate;
        }

        const dIdx = delegatesList.value.findIndex(d => d.id === data.delegate.id);
        if (dIdx !== -1) {
          delegatesList.value[dIdx].checkin_status = 'hadir';
        }
      }
    } else {
      playBeep(false);
      scanFeedback.value = {
        type: 'error',
        text: data.message || `Barcode [${code}] tidak ditemukan dalam database.`,
      };
    }
  } catch (err) {
    playBeep(false);
    scanFeedback.value = {
      type: 'error',
      text: 'Terjadi kesalahan jaringan saat memproses barcode.',
    };
  } finally {
    isScanning.value = false;
    barcodeInput.value = '';
    nextTick(() => barcodeInputRef.value?.focus());
  }
}

async function undoCheckin(id) {
  try {
    const res = await fetch(`/api/operasional/gate/undo/${id}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (data.success) {
      playBeep(false);
      localStats.value.hadir = Math.max(0, localStats.value.hadir - 1);
      localStats.value.belumHadir++;
      if (localStats.value.total > 0) {
        localStats.value.persenKehadiran = Math.round((localStats.value.hadir / localStats.value.total) * 100);
      }
      checkinsList.value = checkinsList.value.filter(c => c.id !== id);
      const dIdx = delegatesList.value.findIndex(d => d.id === id);
      if (dIdx !== -1) {
        delegatesList.value[dIdx].checkin_status = 'belum_hadir';
      }
      if (lastScannedParticipant.value?.id === id) {
        lastScannedParticipant.value = null;
      }
    }
  } catch (e) {}
}

// -------------------------------------------------------------
// TAB 3: VERIFIKASI BERKAS LOGIC
// -------------------------------------------------------------
const berkasFilterStatus = ref('all');
const berkasSearch = ref('');
const selectedBerkasItem = ref(null);
const berkasNotes = ref('');
const isSavingVerification = ref(false);

const filteredBerkasDelegates = computed(() => {
  return delegatesList.value.filter(d => {
    const matchStatus = berkasFilterStatus.value === 'all' || d.payment_status === berkasFilterStatus.value;
    const q = berkasSearch.value.toLowerCase().trim();
    const matchQ = !q ||
      d.school_name?.toLowerCase().includes(q) ||
      d.mentor_name?.toLowerCase().includes(q) ||
      d.reg_code?.toLowerCase().includes(q);
    return matchStatus && matchQ;
  });
});

function selectBerkasForReview(item) {
  selectedBerkasItem.value = item;
  berkasNotes.value = item.verification_notes || '';
}

async function updateVerificationDecision(status) {
  if (!selectedBerkasItem.value) return;
  isSavingVerification.value = true;
  try {
    const res = await fetch(`/api/operasional/verification/${selectedBerkasItem.value.id}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        status: status,
        notes: berkasNotes.value,
      }),
    });
    const data = await res.json();
    if (data.success) {
      selectedBerkasItem.value.payment_status = status;
      selectedBerkasItem.value.verification_notes = berkasNotes.value;
      const idx = delegatesList.value.findIndex(d => d.id === selectedBerkasItem.value.id);
      if (idx !== -1) {
        delegatesList.value[idx].payment_status = status;
        delegatesList.value[idx].verification_notes = berkasNotes.value;
      }
      // Update local count
      localStats.value.terverifikasi = delegatesList.value.filter(d => d.payment_status === 'Terverifikasi').length;
    }
  } catch (err) {
  } finally {
    isSavingVerification.value = false;
  }
}

// -------------------------------------------------------------
// TAB 4: DEWAN JURI DIGITAL & RUBRIK KUSTOMISASI
// -------------------------------------------------------------
const activeCompetition = computed(() => {
  return props.competitions.find(c => c.id === activeCompetitionId.value) || props.competitions[0] || {};
});

const currentDelegatesForComp = computed(() => {
  return delegatesList.value.filter(d => d.competition_id === activeCompetitionId.value);
});

const currentScoresForComp = computed(() => {
  return scoresList.value
    .filter(s => s.competition_id === activeCompetitionId.value)
    .sort((a, b) => Number(b.total_score) - Number(a.total_score));
});

// Active Rubric Definition
const activeRubricType = ref('percentage'); // 'percentage' | 'point'
const activeCriteria = ref({});
const isCustomRubric = ref(false);

// Active Scoring Participant & Form
const selectedParticipant = ref(null);
const juryName = ref('Dewan Juri 1');
const criteriaScores = ref({});
const juryNotes = ref('');
const isSubmittingScore = ref(false);
const scoreToast = ref(null);

// Stopwatch HUD
const stopwatchSeconds = ref(0);
const stopwatchRunning = ref(false);
let stopwatchInterval = null;

const formattedStopwatch = computed(() => {
  const m = String(Math.floor(stopwatchSeconds.value / 60)).padStart(2, '0');
  const s = String(stopwatchSeconds.value % 60).padStart(2, '0');
  return `${m}:${s}`;
});

function toggleStopwatch() {
  if (stopwatchRunning.value) {
    clearInterval(stopwatchInterval);
    stopwatchRunning.value = false;
  } else {
    stopwatchRunning.value = true;
    stopwatchInterval = setInterval(() => {
      stopwatchSeconds.value++;
    }, 1000);
  }
}

function resetStopwatch() {
  clearInterval(stopwatchInterval);
  stopwatchRunning.value = false;
  stopwatchSeconds.value = 0;
}

// Spotlight Participant & Auto Start Timer
function spotlightParticipant(p) {
  selectedParticipant.value = p;
  // Initialize scores with defaults
  Object.keys(activeCriteria.value).forEach(crit => {
    if (activeRubricType.value === 'percentage') {
      criteriaScores.value[crit] = 80;
    } else {
      criteriaScores.value[crit] = Math.round(Number(activeCriteria.value[crit]) * 0.8);
    }
  });

  // Check if score exists already
  const existing = scoresList.value.find(s => s.registration_id === p.id && s.competition_id === activeCompetitionId.value);
  if (existing) {
    if (existing.criteria_scores) {
      criteriaScores.value = { ...existing.criteria_scores };
    }
    juryNotes.value = existing.notes || '';
  } else {
    juryNotes.value = '';
  }

  // Trigger Stopwatch
  resetStopwatch();
  toggleStopwatch();
}

// Calculate Live Total Score
const liveCalculatedTotal = computed(() => {
  const crits = activeCriteria.value || {};
  const keys = Object.keys(crits);
  if (keys.length === 0) return 0;

  if (activeRubricType.value === 'percentage') {
    let sum = 0;
    keys.forEach(k => {
      const val = Number(criteriaScores.value[k] || 0);
      const weight = Number(crits[k] || 0);
      sum += (val * weight) / 100;
    });
    return Math.round(sum * 100) / 100;
  } else {
    let sum = 0;
    keys.forEach(k => {
      sum += Number(criteriaScores.value[k] || 0);
    });
    return sum;
  }
});

// Live Performance Grade
const performanceGrade = computed(() => {
  const total = liveCalculatedTotal.value;
  let normalized = total;
  if (activeRubricType.value === 'point') {
    const maxPossible = Object.values(activeCriteria.value).reduce((a, b) => a + Number(b), 0);
    normalized = maxPossible > 0 ? (total / maxPossible) * 100 : 0;
  }

  if (normalized >= 90) return { letter: 'A+', label: 'Istimewa / Sangat Memuaskan', color: 'emerald' };
  if (normalized >= 80) return { letter: 'A', label: 'Unggul / Memuaskan', color: 'blue' };
  if (normalized >= 70) return { letter: 'B', label: 'Baik', color: 'amber' };
  return { letter: 'C', label: 'Cukup', color: 'rose' };
});

// Quick Score Steppers
function adjustScore(criterion, delta) {
  const max = activeRubricType.value === 'percentage' ? 100 : Number(activeCriteria.value[criterion] || 100);
  const current = Number(criteriaScores.value[criterion] || 0);
  const next = Math.max(0, Math.min(max, current + delta));
  criteriaScores.value[criterion] = next;
}

// Load Rubric from API
async function loadCompetitionRubric(compId) {
  try {
    const res = await fetch(`/api/operasional/jury/rubric/${compId}`);
    const data = await res.json();
    if (data.success) {
      activeRubricType.value = data.rubric_type || 'percentage';
      activeCriteria.value = data.active_rubric || props.defaultRubrics[compId] || {};
      isCustomRubric.value = data.is_custom || false;

      // Reset criteria input model
      Object.keys(activeCriteria.value).forEach(crit => {
        if (!criteriaScores.value[crit]) {
          criteriaScores.value[crit] = activeRubricType.value === 'percentage' ? 80 : 80;
        }
      });
    }
  } catch (err) {}
}

// Save Jury Score
async function submitJuryScore() {
  if (!selectedParticipant.value) return;

  isSubmittingScore.value = true;
  scoreToast.value = null;

  try {
    const payload = {
      registration_id: selectedParticipant.value.id,
      competition_id: activeCompetitionId.value,
      jury_name: juryName.value,
      criteria_scores: criteriaScores.value,
      total_score: liveCalculatedTotal.value,
      performance_duration: formattedStopwatch.value,
      notes: juryNotes.value,
      is_locked: true,
    };

    const res = await fetch('/api/operasional/jury/score', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      scoreToast.value = {
        type: 'success',
        text: `Nilai ${selectedParticipant.value.school_name} (${liveCalculatedTotal.value}) berhasil dikunci dan diarsipkan!`,
      };

      // Update in local score list
      const idx = scoresList.value.findIndex(s => s.registration_id === selectedParticipant.value.id && s.competition_id === activeCompetitionId.value);
      if (idx !== -1) {
        scoresList.value[idx] = data.score;
      } else {
        scoresList.value.push(data.score);
      }

      localStats.value.scoresCount = scoresList.value.length;
      localStats.value.scoresLocked = scoresList.value.filter(s => s.is_locked).length;

      // Stop stopwatch
      if (stopwatchRunning.value) toggleStopwatch();
    }
  } catch (err) {
    scoreToast.value = {
      type: 'error',
      text: 'Gagal menyimpan nilai dewan juri. Coba periksa koneksi.',
    };
  } finally {
    isSubmittingScore.value = false;
  }
}

// Rubrik Customizer Modal State
const showRubricModal = ref(false);
const editableRubricType = ref('percentage');
const editableCriteriaList = ref([]); // [{ name: '', weight: 0 }]
const isSavingRubric = ref(false);

function openRubricCustomizer() {
  editableRubricType.value = activeRubricType.value;
  editableCriteriaList.value = Object.entries(activeCriteria.value).map(([k, v]) => ({
    name: k,
    weight: Number(v),
  }));
  showRubricModal.value = true;
}

function addCriterionRow() {
  editableCriteriaList.value.push({
    name: `Kriteria Baru ${editableCriteriaList.value.length + 1}`,
    weight: editableRubricType.value === 'percentage' ? 10 : 25,
  });
}

function removeCriterionRow(idx) {
  if (editableCriteriaList.value.length <= 1) return;
  editableCriteriaList.value.splice(idx, 1);
}

const rubricWeightSum = computed(() => {
  return editableCriteriaList.value.reduce((acc, curr) => acc + Number(curr.weight || 0), 0);
});

async function saveRubricCustomization() {
  isSavingRubric.value = true;
  try {
    const formattedCriteria = {};
    editableCriteriaList.value.forEach(item => {
      const name = item.name.trim();
      if (name) formattedCriteria[name] = Number(item.weight);
    });

    const res = await fetch(`/api/operasional/jury/rubric/${activeCompetitionId.value}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        rubric_type: editableRubricType.value,
        criteria: formattedCriteria,
      }),
    });
    const data = await res.json();
    if (data.success) {
      activeRubricType.value = data.rubric_type;
      activeCriteria.value = data.criteria;
      isCustomRubric.value = true;
      showRubricModal.value = false;
    }
  } catch (e) {
  } finally {
    isSavingRubric.value = false;
  }
}

async function resetRubricToDefault() {
  const ok = await confirmDialog({
    title: 'Reset Rubrik Penilaian?',
    message: 'Apakah Anda yakin ingin mereset rubrik penilaian cabang ini kembali ke standar baku resmi Juknis?',
    type: 'warning',
    confirmText: 'Ya, Reset Rubrik',
    icon: 'ri-refresh-line',
  });
  if (!ok) return;

  isSavingRubric.value = true;
  try {
    const res = await fetch(`/api/operasional/jury/rubric/${activeCompetitionId.value}/reset`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (data.success) {
      activeRubricType.value = data.rubric_type;
      activeCriteria.value = data.criteria;
      isCustomRubric.value = false;
      showRubricModal.value = false;
    }
  } catch (e) {
  } finally {
    isSavingRubric.value = false;
  }
}

// -------------------------------------------------------------
// TAB 5: TURNAMEN SISTEM GUGUR (BRACKET & REFEREE) LOGIC
// -------------------------------------------------------------
const tournamentMatches = computed(() => {
  return matchesList.value
    .filter(m => m.competition_id === activeCompetitionId.value)
    .sort((a, b) => a.round_order - b.round_order || a.id - b.id);
});

const activeMatch = ref(null);
const refereeScore1 = ref(0);
const refereeScore2 = ref(0);
const refereeSet1 = ref('');
const refereeSet2 = ref('');
const refereeStatus = ref('scheduled');
const refereeCourt = ref('Lapangan Utama A');
const refereeName = ref('Wasit Utama');
const isSavingMatch = ref(false);

function selectTournamentMatch(m) {
  activeMatch.value = m;
  refereeScore1.value = m.team1_score || 0;
  refereeScore2.value = m.team2_score || 0;
  refereeSet1.value = m.team1_set_scores || '';
  refereeSet2.value = m.team2_set_scores || '';
  refereeStatus.value = m.status || 'scheduled';
  refereeCourt.value = m.court || 'Lapangan Utama A';
  refereeName.value = m.referee_name || 'Wasit Utama';
}

async function saveMatchScore() {
  if (!activeMatch.value) return;
  isSavingMatch.value = true;
  try {
    const res = await fetch(`/api/operasional/tournament/match/${activeMatch.value.id}/score`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        team1_score: refereeScore1.value,
        team2_score: refereeScore2.value,
        team1_set_scores: refereeSet1.value,
        team2_set_scores: refereeSet2.value,
        status: refereeStatus.value,
        court: refereeCourt.value,
        referee_name: refereeName.value,
      }),
    });
    const data = await res.json();
    if (data.success) {
      activeMatch.value.team1_score = refereeScore1.value;
      activeMatch.value.team2_score = refereeScore2.value;
      activeMatch.value.team1_set_scores = refereeSet1.value;
      activeMatch.value.team2_set_scores = refereeSet2.value;
      activeMatch.value.status = refereeStatus.value;
      activeMatch.value.court = refereeCourt.value;

      // Reload tournament data to see updated bracket
      reloadTournamentData();
    }
  } catch (err) {
  } finally {
    isSavingMatch.value = false;
  }
}

async function reloadTournamentData() {
  try {
    const res = await fetch(`/api/operasional/tournament/${activeCompetitionId.value}`);
    const data = await res.json();
    if (data.status === 'success' && data.data) {
      // Replace matches in matchesList
      matchesList.value = matchesList.value.filter(m => m.competition_id !== activeCompetitionId.value);
      matchesList.value.push(...data.data.matches);
    }
  } catch (e) {}
}

async function generateBracket() {
  const ok = await confirmDialog({
    title: 'Undi Ulang Bagan Pertandingan?',
    message: 'Apakah Anda yakin ingin mengundi dan mengenerate ulang bagan 8 besar sistem gugur? Data skor laga yang sudah ada akan di-reset.',
    type: 'danger',
    confirmText: 'Ya, Undi Ulang',
    icon: 'ri-shuffle-line',
  });
  if (!ok) return;

  try {
    const res = await fetch(`/api/operasional/tournament/generate/${activeCompetitionId.value}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (data.success) {
      reloadTournamentData();
    }
  } catch (e) {}
}

// -------------------------------------------------------------
// TAB 6: PRINT STUDIO LOGIC
// -------------------------------------------------------------
const printSearch = ref('');
const printCompFilter = ref('all');

const printableDelegates = computed(() => {
  return delegatesList.value.filter(d => {
    const matchComp = printCompFilter.value === 'all' || d.competition_id === printCompFilter.value;
    const q = printSearch.value.toLowerCase().trim();
    const matchQ = !q ||
      d.school_name?.toLowerCase().includes(q) ||
      d.reg_code?.toLowerCase().includes(q) ||
      d.mentor_name?.toLowerCase().includes(q);
    return matchComp && matchQ;
  });
});

function triggerPrint() {
  window.print();
}

// -------------------------------------------------------------
// WEBSOCKET REVERB INTEGRATION
// -------------------------------------------------------------
onMounted(() => {
  // Focus scanner input if gate tab is active
  if (activeTab.value === 'gate') {
    nextTick(() => barcodeInputRef.value?.focus());
  }

  // Initial rubric load
  loadCompetitionRubric(activeCompetitionId.value);

  // Auto select first match if tournament
  if (tournamentMatches.value.length > 0) {
    selectTournamentMatch(tournamentMatches.value[0]);
  }

  // Connect Echo
  try {
    const Echo = window.Echo;
    if (Echo) {
      echoInstance = Echo;
      if (Echo.connector && Echo.connector.pusher) {
        Echo.connector.pusher.connection.bind('connected', () => {
          wsConnected.value = true;
        });
        Echo.connector.pusher.connection.bind('disconnected', () => {
          wsConnected.value = false;
        });
        if (Echo.connector.pusher.connection.state === 'connected') {
          wsConnected.value = true;
        }
      }

      // 1. Channel Gate Check-in
      Echo.channel('gate.checkin')
        .listen('.gate.logged', (e) => {
          if (e?.registration) {
            if (e.action === 'checkin') {
              localStats.value.hadir++;
              if (localStats.value.belumHadir > 0) localStats.value.belumHadir--;
              checkinsList.value.unshift(e.registration);
              liveEventLog.value.unshift({
                text: `Delegasi ${e.registration.school_name} baru saja check-in di gate.`,
                time: new Date().toLocaleTimeString('id-ID'),
              });
            } else if (e.action === 'undo') {
              localStats.value.hadir = Math.max(0, localStats.value.hadir - 1);
              localStats.value.belumHadir++;
              checkinsList.value = checkinsList.value.filter(c => c.id !== e.registration.id);
            }
          }
        });

      // 2. Channel Jury Scores
      Echo.channel('jury-scores')
        .listen('.score.submitted', (e) => {
          if (e?.score) {
            const idx = scoresList.value.findIndex(s => s.id === e.score.id);
            if (idx !== -1) {
              scoresList.value[idx] = e.score;
            } else {
              scoresList.value.unshift(e.score);
            }
            localStats.value.scoresCount = scoresList.value.length;
            liveEventLog.value.unshift({
              text: `Nilai baru masuk: ${e.score.registration?.school_name} skor ${e.score.total_score}.`,
              time: new Date().toLocaleTimeString('id-ID'),
            });
          }
        });

      // 3. Channel Tournament Matches
      Echo.channel('tournament.arena')
        .listen('.match.updated', (e) => {
          if (e?.match) {
            const idx = matchesList.value.findIndex(m => m.id === e.match.id);
            if (idx !== -1) {
              matchesList.value[idx] = e.match;
            }
            if (activeMatch.value && activeMatch.value.id === e.match.id) {
              activeMatch.value = e.match;
            }
            liveEventLog.value.unshift({
              text: `Skor laga ${e.match.match_number} diperbarui: ${e.match.team1_score} - ${e.match.team2_score}`,
              time: new Date().toLocaleTimeString('id-ID'),
            });
          }
        });
    }
  } catch (err) {
    console.warn('Reverb WebSocket initialization error:', err);
  }
});

onUnmounted(() => {
  if (stopwatchInterval) clearInterval(stopwatchInterval);
  if (echoInstance) {
    echoInstance.leave('gate.checkin');
    echoInstance.leave('jury-scores');
    echoInstance.leave('tournament.arena');
  }
});
</script>

<template>
  <AppLayout>
    <Head title="Pusat Operasional Lapangan Hari-H" />

    <!-- Top Command Header with WebSocket Live Beacon & Tab Strip -->
    <div class="mb-6 space-y-4">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/20 text-slate-950 font-black text-2xl">
            <i class="ri-flashlight-fill"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-xl lg:text-2xl font-black text-white tracking-wide">
                Pusat Komando Operasional Hari-H
              </h1>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/40">
                LIVE ARENA
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
              Sistem Operasional Terintegrasi • Single Page Application • Laravel Echo WebSocket Realtime
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Realtime WebSocket Live Badge -->
          <div 
            class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold border transition-all"
            :class="wsConnected ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30'"
            :title="wsConnected ? 'WebSocket Reverb Terkoneksi Real-time' : 'Reverb Menghubungkan...'"
          >
            <span class="w-2.5 h-2.5 rounded-full" :class="wsConnected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
            <span>{{ wsConnected ? 'Reverb Live: Port 8085' : 'Connecting Reverb...' }}</span>
          </div>

          <!-- Public Scoreboard Projector Link -->
          <Link 
            href="/scoreboard" 
            target="_blank"
            class="hidden sm:flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 transition-all"
          >
            <i class="ri-broadcast-line text-sm"></i>
            <span>Layar Scoreboard</span>
          </Link>
        </div>
      </div>

      <!-- Main Operational SPA Tab Navigation -->
      <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900 border border-slate-800 overflow-x-auto scrollbar-none shadow-lg">
        <button 
          @click="setTab('overview')"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeTab === 'overview' 
              ? 'bg-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-dashboard-3-line text-base"></i>
          <span>Ringkasan Command</span>
        </button>

        <button 
          @click="setTab('gate')"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeTab === 'gate' 
              ? 'bg-emerald-500 text-slate-950 font-black shadow-md shadow-emerald-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-qr-scan-2-line text-base"></i>
          <span>Gate Scanner Barcode</span>
          <span v-if="localStats.hadir > 0" class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-slate-950/40 text-current">
            {{ localStats.hadir }}
          </span>
        </button>

        <button 
          @click="setTab('berkas')"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeTab === 'berkas' 
              ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-file-search-line text-base"></i>
          <span>Verifikasi Berkas</span>
        </button>

        <button 
          @click="setTab('juri')"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeTab === 'juri' 
              ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black shadow-md shadow-amber-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-scales-3-line text-base"></i>
          <span>Penjurian Dewan Juri</span>
        </button>

        <button 
          @click="setTab('turnamen')"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeTab === 'turnamen' 
              ? 'bg-indigo-600 text-white font-bold shadow-md shadow-indigo-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-trophy-line text-base"></i>
          <span>Bagan Turnamen Gugur</span>
        </button>

        <button 
          @click="setTab('print')"
          :class="[
            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeTab === 'print' 
              ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-printer-line text-base"></i>
          <span>Print Studio ID Card</span>
        </button>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 1: RINGKASAN COMMAND OVERVIEW                                 -->
    <!-- ================================================================= -->
    <div v-if="activeTab === 'overview'" class="space-y-6">
      <!-- Top Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-6 gap-3.5">
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>Total Delegasi</span>
            <i class="ri-team-line text-base text-blue-400"></i>
          </div>
          <div class="text-2xl font-black text-white font-mono">{{ localStats.total || 0 }}</div>
          <div class="text-[10px] text-slate-500 mt-1">Seluruh Pendaftar</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>Check-in Gate</span>
            <i class="ri-checkbox-circle-line text-base text-emerald-400"></i>
          </div>
          <div class="text-2xl font-black text-emerald-400 font-mono">{{ localStats.hadir || 0 }}</div>
          <div class="text-[10px] text-emerald-500/80 font-bold mt-1">{{ localStats.persenKehadiran || 0 }}% Kehadiran</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>Belum Hadir</span>
            <i class="ri-time-line text-base text-amber-400"></i>
          </div>
          <div class="text-2xl font-black text-amber-400 font-mono">{{ localStats.belumHadir || 0 }}</div>
          <div class="text-[10px] text-slate-500 mt-1">Di Perjalanan / Luar</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>Berkas Sah</span>
            <i class="ri-shield-check-line text-base text-cyan-400"></i>
          </div>
          <div class="text-2xl font-black text-cyan-400 font-mono">{{ localStats.terverifikasi || 0 }}</div>
          <div class="text-[10px] text-slate-500 mt-1">Terverifikasi Valid</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>Nilai Masuk</span>
            <i class="ri-scales-3-line text-base text-purple-400"></i>
          </div>
          <div class="text-2xl font-black text-purple-400 font-mono">{{ localStats.scoresCount || 0 }}</div>
          <div class="text-[10px] text-slate-500 mt-1">{{ localStats.scoresLocked || 0 }} Terkunci Resmi</div>
        </div>

        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
          <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
            <span>Database Driver</span>
            <i class="ri-database-2-line text-base text-rose-400"></i>
          </div>
          <div class="text-sm font-black text-white font-mono">MariaDB:3307</div>
          <div class="text-[10px] text-emerald-400 flex items-center gap-1 mt-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Terkoneksi</span>
          </div>
        </div>
      </div>

      <!-- Attendance Percentage Progress Banner -->
      <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
        <div class="flex items-center justify-between mb-2">
          <div class="text-sm font-bold text-white flex items-center gap-2">
            <i class="ri-pie-chart-2-line text-amber-400"></i>
            <span>Tingkat Kehadiran Delegasi Kontingen di Gerbang Utama</span>
          </div>
          <div class="text-sm font-mono font-black text-amber-400">
            {{ localStats.hadir }} / {{ localStats.total }} ({{ localStats.persenKehadiran }}%)
          </div>
        </div>
        <div class="w-full bg-slate-950 rounded-full h-3.5 p-0.5 border border-slate-800 overflow-hidden">
          <div 
            class="h-full rounded-full bg-gradient-to-r from-amber-500 via-orange-500 to-emerald-400 transition-all duration-500"
            :style="{ width: `${localStats.persenKehadiran}%` }"
          ></div>
        </div>
      </div>

      <!-- Quick Action Cards & Live WebSocket Log -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Quick Operation Launcher -->
        <div class="lg:col-span-7 space-y-4">
          <h2 class="text-sm font-extrabold text-slate-300 uppercase tracking-wider flex items-center gap-2">
            <i class="ri-flashlight-line text-amber-400"></i>
            <span>Pintasan Akses Cepat Lapangan</span>
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <button 
              @click="setTab('gate')"
              class="p-4 rounded-2xl bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-emerald-500/40 text-left transition-all group shadow-md"
            >
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform">
                <i class="ri-qr-scan-2-line"></i>
              </div>
              <div class="text-sm font-extrabold text-white group-hover:text-emerald-300">Gate Barcode Scanner</div>
              <p class="text-xs text-slate-400 mt-1">Pemindaian kilat kartu peserta, validasi kehadiran, audio synthesizer.</p>
            </button>

            <button 
              @click="setTab('juri')"
              class="p-4 rounded-2xl bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-amber-500/40 text-left transition-all group shadow-md"
            >
              <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform">
                <i class="ri-scales-3-line"></i>
              </div>
              <div class="text-sm font-extrabold text-white group-hover:text-amber-300">Penjurian & Stopwatch</div>
              <p class="text-xs text-slate-400 mt-1">Digital stopwatch HUD, rubric weight kustomisasi (skor & %), steppers.</p>
            </button>

            <button 
              @click="setTab('turnamen')"
              class="p-4 rounded-2xl bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-indigo-500/40 text-left transition-all group shadow-md"
            >
              <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform">
                <i class="ri-trophy-line"></i>
              </div>
              <div class="text-sm font-extrabold text-white group-hover:text-indigo-300">Bagan Turnamen Gugur</div>
              <p class="text-xs text-slate-400 mt-1">Bagan 8 besar Voli & MLBB, konsol wasit skor set, auto-advance.</p>
            </button>

            <button 
              @click="setTab('berkas')"
              class="p-4 rounded-2xl bg-slate-900 hover:bg-slate-850 border border-slate-800 hover:border-blue-500/40 text-left transition-all group shadow-md"
            >
              <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-xl mb-3 group-hover:scale-110 transition-transform">
                <i class="ri-file-search-line"></i>
              </div>
              <div class="text-sm font-extrabold text-white group-hover:text-blue-300">Review & Verifikasi Berkas</div>
              <p class="text-xs text-slate-400 mt-1">Inspeksi surat tugas sekolah, bukti bayar bank, status berkas kontingen.</p>
            </button>
          </div>
        </div>

        <!-- Live WebSocket Event Feed -->
        <div class="lg:col-span-5 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-extrabold text-slate-300 uppercase tracking-wider flex items-center gap-2">
              <i class="ri-broadcast-fill text-emerald-400 animate-pulse"></i>
              <span>Live Activity WebSocket</span>
            </h2>
            <span class="text-[11px] font-mono text-slate-500">Echo / Reverb</span>
          </div>

          <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 h-[280px] overflow-y-auto space-y-2.5">
            <div v-if="liveEventLog.length === 0" class="text-center py-12 text-slate-500 text-xs">
              <i class="ri-wifi-line text-2xl mb-1 block"></i>
              <span>Menunggu aktivitas live lapangan...</span>
            </div>
            <div 
              v-for="(item, idx) in liveEventLog" 
              :key="idx" 
              class="p-2.5 rounded-xl bg-slate-950 border border-slate-800/80 flex items-start gap-2.5 text-xs text-slate-300"
            >
              <span class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5 shrink-0"></span>
              <div class="flex-1">
                <div>{{ item.text }}</div>
                <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ item.time }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 2: GATE SCANNER BARCODE                                       -->
    <!-- ================================================================= -->
    <div v-if="activeTab === 'gate'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Scanner Input Box & Last Scanned Card -->
        <div class="lg:col-span-7 space-y-4">
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-extrabold text-white flex items-center gap-2">
                <i class="ri-qr-scan-line text-emerald-400 text-lg"></i>
                <span>Pemindai Barcode / Tiket Peserta</span>
              </h2>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                Auto-Focus Siap Scan
              </span>
            </div>

            <!-- Input Form -->
            <form @submit.prevent="handleScanSubmit" class="relative">
              <input 
                ref="barcodeInputRef"
                v-model="barcodeInput"
                type="text" 
                placeholder="Arahkan barcode scanner / ketik kode..."
                class="w-full pl-11 pr-28 py-3.5 rounded-xl bg-slate-950 border-2 border-emerald-500/40 text-white font-mono text-base placeholder:text-slate-600 focus:outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-500/20 transition-all shadow-inner"
                :disabled="isScanning"
                autofocus
              />
              <i class="ri-barcode-line text-xl text-emerald-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <button 
                type="submit" 
                class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs flex items-center gap-1.5 transition-all"
                :disabled="isScanning"
              >
                <i class="ri-check-line text-sm"></i>
                <span>{{ isScanning ? 'Proses...' : 'Validasi' }}</span>
              </button>
            </form>

            <!-- Feedback Banner -->
            <div 
              v-if="scanFeedback" 
              class="p-3.5 rounded-xl text-xs font-bold flex items-center gap-2 border transition-all"
              :class="{
                'bg-emerald-500/10 text-emerald-300 border-emerald-500/30': scanFeedback.type === 'success',
                'bg-amber-500/10 text-amber-300 border-amber-500/30': scanFeedback.type === 'warn',
                'bg-rose-500/10 text-rose-300 border-rose-500/30': scanFeedback.type === 'error'
              }"
            >
              <i 
                :class="{
                  'ri-checkbox-circle-fill text-emerald-400 text-base': scanFeedback.type === 'success',
                  'ri-error-warning-fill text-amber-400 text-base': scanFeedback.type === 'warn',
                  'ri-close-circle-fill text-rose-400 text-base': scanFeedback.type === 'error'
                }"
              ></i>
              <span>{{ scanFeedback.text }}</span>
            </div>
          </div>

          <!-- Last Scanned Participant Detail Card -->
          <div v-if="lastScannedParticipant" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <i class="ri-user-star-line text-amber-400"></i>
                <span>Data Delegasi Terakhir Dipindai</span>
              </span>
              <button 
                @click="undoCheckin(lastScannedParticipant.id)"
                class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold flex items-center gap-1 transition-all"
                title="Batalkan Kehadiran"
              >
                <i class="ri-arrow-go-back-line"></i>
                <span>Batalkan Check-in</span>
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              <div>
                <span class="text-slate-500 block mb-0.5">Asal Sekolah / Kontingen:</span>
                <span class="text-base font-extrabold text-white">{{ lastScannedParticipant.school_name }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-0.5">Cabang Lomba:</span>
                <span class="text-sm font-bold text-amber-400">{{ lastScannedParticipant.competition_title || lastScannedParticipant.competition_id }}</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-0.5">Nama Pendamping:</span>
                <span class="font-semibold text-slate-200">{{ lastScannedParticipant.mentor_name }} ({{ lastScannedParticipant.mentor_wa }})</span>
              </div>
              <div>
                <span class="text-slate-500 block mb-0.5">Kode Registrasi:</span>
                <span class="font-mono font-bold text-blue-400">{{ lastScannedParticipant.reg_code }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Check-ins List Table -->
        <div class="lg:col-span-5 space-y-4">
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                <i class="ri-history-line text-blue-400"></i>
                <span>Riwayat Hadir Check-in Hari-H</span>
              </h3>
              <span class="text-xs font-mono font-bold text-emerald-400">{{ checkinsList.length }} Masuk</span>
            </div>

            <div class="divide-y divide-slate-800/80 max-h-[460px] overflow-y-auto">
              <div v-if="checkinsList.length === 0" class="text-center py-10 text-slate-500 text-xs">
                Belum ada delegasi yang melakukan scan check-in.
              </div>
              <div 
                v-for="c in checkinsList" 
                :key="c.id" 
                class="py-3 flex items-center justify-between gap-3 text-xs"
              >
                <div>
                  <div class="font-bold text-white">{{ c.school_name }}</div>
                  <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
                    <span class="text-amber-400 font-mono">{{ c.reg_code }}</span>
                    <span>•</span>
                    <span>{{ c.competition_title || c.competition_id }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                    Hadir
                  </span>
                  <button 
                    @click="undoCheckin(c.id)"
                    class="p-1 rounded text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                    title="Undo Check-in"
                  >
                    <i class="ri-close-line text-sm"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 3: VERIFIKASI BERKAS                                          -->
    <!-- ================================================================= -->
    <div v-if="activeTab === 'berkas'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Filter & Delegates List -->
        <div class="lg:col-span-7 space-y-4">
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <h2 class="text-sm font-extrabold text-white flex items-center gap-2">
                <i class="ri-file-search-line text-blue-400"></i>
                <span>Daftar Berkas Masuk Kontingen</span>
              </h2>

              <div class="flex items-center gap-2">
                <!-- Status Filter -->
                <select 
                  v-model="berkasFilterStatus"
                  class="px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
                >
                  <option value="all">Semua Status</option>
                  <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                  <option value="Terverifikasi">Terverifikasi</option>
                  <option value="Ditolak">Ditolak / Belum Sah</option>
                </select>
              </div>
            </div>

            <!-- Search -->
            <div class="relative">
              <input 
                v-model="berkasSearch"
                type="text" 
                placeholder="Cari sekolah, pendamping, kode registrasi..."
                class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder:text-slate-600 focus:outline-none focus:border-blue-500"
              />
              <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
            </div>
          </div>

          <!-- List Items -->
          <div class="space-y-2.5 max-h-[580px] overflow-y-auto">
            <div 
              v-for="item in filteredBerkasDelegates" 
              :key="item.id"
              @click="selectBerkasForReview(item)"
              :class="[
                'p-4 rounded-2xl border transition-all cursor-pointer shadow-md',
                selectedBerkasItem?.id === item.id 
                  ? 'bg-blue-600/15 border-blue-500/60 shadow-blue-500/10' 
                  : 'bg-slate-900 border-slate-800 hover:border-slate-700 hover:bg-slate-850'
              ]"
            >
              <div class="flex items-center justify-between gap-3 mb-1.5">
                <div class="font-extrabold text-sm text-white">{{ item.school_name }}</div>
                <span 
                  class="px-2 py-0.5 rounded text-[10px] font-bold"
                  :class="{
                    'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': item.payment_status === 'Terverifikasi',
                    'bg-amber-500/20 text-amber-300 border border-amber-500/30': item.payment_status === 'Menunggu Verifikasi',
                    'bg-rose-500/20 text-rose-300 border border-rose-500/30': item.payment_status === 'Ditolak'
                  }"
                >
                  {{ item.payment_status }}
                </span>
              </div>

              <div class="flex items-center gap-3 text-xs text-slate-400">
                <span class="font-mono text-blue-400 font-bold">{{ item.reg_code }}</span>
                <span>•</span>
                <span>{{ item.competition_title || item.competition_id }}</span>
                <span>•</span>
                <span>Pendamping: {{ item.mentor_name }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Detail Review & Action Pane -->
        <div class="lg:col-span-5 space-y-4">
          <div v-if="selectedBerkasItem" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-300 flex items-center gap-2">
                <i class="ri-shield-check-line text-cyan-400"></i>
                <span>Detail Pemeriksaan Berkas</span>
              </h3>
              <span class="text-xs font-mono font-bold text-blue-400">{{ selectedBerkasItem.reg_code }}</span>
            </div>

            <div class="space-y-3 text-xs">
              <div>
                <span class="text-slate-500 block mb-0.5">Asal Sekolah:</span>
                <span class="text-sm font-extrabold text-white">{{ selectedBerkasItem.school_name }}</span>
              </div>

              <div>
                <span class="text-slate-500 block mb-0.5">Cabang Lomba & Paket:</span>
                <span class="font-bold text-amber-400">{{ selectedBerkasItem.competition_title || selectedBerkasItem.competition_id }}</span>
                <span class="ml-2 px-1.5 py-0.5 rounded text-[10px] bg-slate-800 text-slate-300">{{ selectedBerkasItem.package_type }}</span>
              </div>

              <div>
                <span class="text-slate-500 block mb-0.5">Nama Peserta / Tim:</span>
                <span class="text-slate-200">{{ selectedBerkasItem.participants_names }}</span>
              </div>

              <div>
                <span class="text-slate-500 block mb-0.5">Pendamping:</span>
                <span class="text-slate-200">{{ selectedBerkasItem.mentor_name }} ({{ selectedBerkasItem.mentor_wa }})</span>
              </div>

              <!-- Document Links / Previews -->
              <div class="pt-2 border-t border-slate-800 space-y-2">
                <div class="text-[11px] font-bold text-slate-400 uppercase">Lampiran Berkas:</div>
                <div class="flex flex-col gap-2">
                  <a 
                    v-if="selectedBerkasItem.assignment_letter_path"
                    :href="`/storage/${selectedBerkasItem.assignment_letter_path}`" 
                    target="_blank"
                    class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 flex items-center justify-between text-slate-300 hover:text-white transition-all"
                  >
                    <div class="flex items-center gap-2">
                      <i class="ri-file-pdf-line text-base text-rose-400"></i>
                      <span>Surat Tugas Resmi Sekolah</span>
                    </div>
                    <i class="ri-external-link-line text-slate-500"></i>
                  </a>
                  <div v-else class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60 text-slate-500 italic">
                    Surat tugas belum diunggah.
                  </div>

                  <a 
                    v-if="selectedBerkasItem.payment_proof_path"
                    :href="`/storage/${selectedBerkasItem.payment_proof_path}`" 
                    target="_blank"
                    class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 border border-slate-800 flex items-center justify-between text-slate-300 hover:text-white transition-all"
                  >
                    <div class="flex items-center gap-2">
                      <i class="ri-image-line text-base text-emerald-400"></i>
                      <span>Bukti Transfer / Pembayaran Bank</span>
                    </div>
                    <i class="ri-external-link-line text-slate-500"></i>
                  </a>
                  <div v-else class="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60 text-slate-500 italic">
                    Bukti pembayaran belum diunggah (bayar di tempat).
                  </div>

                  <!-- Custom Uploaded Documents from Form Builder -->
                  <template v-if="selectedBerkasItem.file_documents && selectedBerkasItem.file_documents.length > 0">
                    <a 
                      v-for="(doc, dIdx) in selectedBerkasItem.file_documents"
                      :key="dIdx"
                      :href="doc.url" 
                      target="_blank"
                      class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800 border border-amber-500/30 flex items-center justify-between text-slate-300 hover:text-white transition-all"
                    >
                      <div class="flex items-center gap-2">
                        <i class="ri-attachment-line text-base text-amber-400"></i>
                        <span class="truncate">{{ doc.title || doc.filename || 'Dokumen Persyaratan Khusus' }}</span>
                      </div>
                      <i class="ri-external-link-line text-slate-500"></i>
                    </a>
                  </template>
                </div>
              </div>

              <!-- Notes Input -->
              <div class="pt-2 border-t border-slate-800">
                <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Catatan / Instruksi Verifikator:</label>
                <textarea 
                  v-model="berkasNotes"
                  rows="2"
                  placeholder="Tambahkan catatan khusus verifikasi jika berkas kurang..."
                  class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
                ></textarea>
              </div>

              <!-- Decision Buttons -->
              <div class="grid grid-cols-2 gap-2 pt-2">
                <button 
                  @click="updateVerificationDecision('Terverifikasi')"
                  class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-md shadow-emerald-600/20"
                  :disabled="isSavingVerification"
                >
                  <i class="ri-checkbox-circle-line text-sm"></i>
                  <span>Verifikasi Sah</span>
                </button>

                <button 
                  @click="updateVerificationDecision('Ditolak')"
                  class="py-2.5 px-3 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/40 font-bold text-xs flex items-center justify-center gap-1.5 transition-all"
                  :disabled="isSavingVerification"
                >
                  <i class="ri-close-circle-line text-sm"></i>
                  <span>Tolak Berkas</span>
                </button>
              </div>
            </div>
          </div>

          <div v-else class="p-10 rounded-2xl bg-slate-900 border border-slate-800 text-center text-slate-500 text-xs">
            <i class="ri-cursor-line text-2xl mb-2 block text-slate-600"></i>
            <span>Pilih salah satu delegasi di sebelah kiri untuk memeriksa kelengkapan berkas.</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 4: DEWAN JURI DIGITAL & KUSTOMISASI RUBRIK                    -->
    <!-- ================================================================= -->
    <div v-if="activeTab === 'juri'" class="space-y-6">
      <!-- Competition Category Selector Bar -->
      <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900 border border-slate-800 overflow-x-auto scrollbar-none shadow-lg">
        <button 
          v-for="c in competitions" 
          :key="c.id"
          @click="selectCompetition(c)"
          :class="[
            'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
            activeCompetitionId === c.id 
              ? 'bg-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/20' 
              : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
          ]"
        >
          <i class="ri-medal-line text-sm"></i>
          <span>{{ c.title }}</span>
        </button>
      </div>

      <!-- Jury Arena Workspace Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Stopwatch HUD & Active Participant Scoring Form -->
        <div class="lg:col-span-8 space-y-4">
          <!-- Stopwatch HUD & Rubric Customizer Action Banner -->
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Digital Stopwatch HUD -->
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-2xl">
                <i class="ri-timer-flash-line"></i>
              </div>
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Stopwatch Durasi Tampil</span>
                <div class="text-3xl font-black font-mono text-white tracking-widest">{{ formattedStopwatch }}</div>
              </div>
              <div class="flex items-center gap-1.5 ml-2">
                <button 
                  @click="toggleStopwatch"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1 transition-all',
                    stopwatchRunning ? 'bg-rose-600 hover:bg-rose-500 text-white' : 'bg-emerald-600 hover:bg-emerald-500 text-white'
                  ]"
                >
                  <i :class="stopwatchRunning ? 'ri-pause-line' : 'ri-play-line'"></i>
                  <span>{{ stopwatchRunning ? 'Jeda' : 'Mulai' }}</span>
                </button>
                <button 
                  @click="resetStopwatch"
                  class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold"
                  title="Reset Stopwatch"
                >
                  <i class="ri-refresh-line"></i>
                </button>
              </div>
            </div>

            <!-- Rubric Info & Customizer Button -->
            <div class="flex items-center gap-2">
              <div class="text-right hidden sm:block">
                <span class="text-[10px] font-bold text-slate-400 block uppercase">Mode Rubrik:</span>
                <span class="text-xs font-extrabold text-amber-400">
                  {{ activeRubricType === 'percentage' ? 'Persentase (%)' : 'Skor Poin Maksimal' }}
                </span>
              </div>
              <button 
                @click="openRubricCustomizer"
                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 border border-amber-500/30 font-bold text-xs flex items-center gap-1.5 transition-all shadow-md"
              >
                <i class="ri-settings-4-line text-sm text-amber-400"></i>
                <span>Kustomisasi Rubrik</span>
              </button>
            </div>
          </div>

          <!-- Participant Spotlight Card -->
          <div v-if="selectedParticipant" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 block">Peserta Sedang Tampil (Spotlight)</span>
                <h2 class="text-lg font-black text-white">{{ selectedParticipant.school_name }}</h2>
              </div>
              <div class="text-right">
                <span class="font-mono text-xs font-bold text-blue-400">{{ selectedParticipant.reg_code }}</span>
                <span 
                  class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold"
                  :class="selectedParticipant.checkin_status === 'hadir' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'"
                >
                  {{ selectedParticipant.checkin_status === 'hadir' ? 'Hadir di Arena' : 'Belum Hadir' }}
                </span>
              </div>
            </div>

            <!-- Toast alert -->
            <div 
              v-if="scoreToast" 
              class="p-3 rounded-xl text-xs font-bold flex items-center gap-2 border"
              :class="scoreToast.type === 'success' ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30' : 'bg-rose-500/10 text-rose-300 border-rose-500/30'"
            >
              <i :class="scoreToast.type === 'success' ? 'ri-checkbox-circle-fill text-emerald-400' : 'ri-close-circle-fill text-rose-400'"></i>
              <span>{{ scoreToast.text }}</span>
            </div>

            <!-- Criteria Rubric Sliders and Steppers -->
            <div class="space-y-4">
              <div 
                v-for="(weight, criterion) in activeCriteria" 
                :key="criterion"
                class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 space-y-2.5"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-extrabold text-slate-200">{{ criterion }}</span>
                  <div class="flex items-center gap-2">
                    <!-- Contrast Rubric Badge -->
                    <span 
                      v-if="activeRubricType === 'percentage'" 
                      class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500/20 text-amber-300 border border-amber-500/40"
                    >
                      Bobot: {{ weight }}%
                    </span>
                    <span 
                      v-else 
                      class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-500/20 text-blue-300 border border-blue-500/40"
                    >
                      Maks: {{ weight }} Poin
                    </span>

                    <span class="font-mono text-base font-black text-white w-10 text-right">
                      {{ criteriaScores[criterion] || 0 }}
                    </span>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <!-- Range slider -->
                  <input 
                    type="range" 
                    min="0" 
                    :max="activeRubricType === 'percentage' ? 100 : weight" 
                    v-model.number="criteriaScores[criterion]"
                    class="w-full h-2 rounded-lg bg-slate-800 accent-amber-500 cursor-pointer"
                  />

                  <!-- Quick Score Stepper Chips -->
                  <div class="flex items-center gap-1 shrink-0">
                    <button 
                      @click="adjustScore(criterion, -5)"
                      class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] font-bold flex items-center justify-center transition-colors"
                      title="Kurang 5"
                    >
                      -5
                    </button>
                    <button 
                      @click="adjustScore(criterion, -1)"
                      class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] font-bold flex items-center justify-center transition-colors"
                      title="Kurang 1"
                    >
                      -1
                    </button>
                    <button 
                      @click="adjustScore(criterion, 1)"
                      class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] font-bold flex items-center justify-center transition-colors"
                      title="Tambah 1"
                    >
                      +1
                    </button>
                    <button 
                      @click="adjustScore(criterion, 5)"
                      class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono text-[11px] font-bold flex items-center justify-center transition-colors"
                      title="Tambah 5"
                    >
                      +5
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Total Score & Performance Grade HUD -->
            <div class="p-4 rounded-xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-amber-500/30 flex items-center justify-between">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Skor Akhir Dewan Juri</span>
                <div class="text-3xl font-black font-mono text-amber-400 tracking-tight">
                  {{ liveCalculatedTotal }}
                  <span class="text-sm font-sans text-slate-400 font-normal">/ 100</span>
                </div>
              </div>

              <!-- Performance Grade Pill -->
              <div class="text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Grade Predikat:</span>
                <div 
                  class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black border"
                  :class="{
                    'bg-emerald-500/20 text-emerald-300 border-emerald-500/40': performanceGrade.color === 'emerald',
                    'bg-blue-500/20 text-blue-300 border-blue-500/40': performanceGrade.color === 'blue',
                    'bg-amber-500/20 text-amber-300 border-amber-500/40': performanceGrade.color === 'amber',
                    'bg-rose-500/20 text-rose-300 border-rose-500/40': performanceGrade.color === 'rose'
                  }"
                >
                  <span>{{ performanceGrade.letter }}</span>
                  <span>•</span>
                  <span>{{ performanceGrade.label }}</span>
                </div>
              </div>
            </div>

            <!-- Notes & Submitter -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div>
                <label class="block text-slate-400 mb-1 font-semibold">Nama Juri Penguji:</label>
                <input 
                  v-model="juryName" 
                  type="text" 
                  class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-medium focus:outline-none focus:border-amber-500"
                />
              </div>
              <div>
                <label class="block text-slate-400 mb-1 font-semibold">Catatan Dewan Juri:</label>
                <input 
                  v-model="juryNotes" 
                  type="text" 
                  placeholder="Komentar teknik vokal, artikulasi, atau catatan panggung..."
                  class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-medium focus:outline-none focus:border-amber-500"
                />
              </div>
            </div>

            <!-- Submit Button -->
            <button 
              @click="submitJuryScore"
              class="w-full py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-amber-500/20 transition-all"
              :disabled="isSubmittingScore"
            >
              <i class="ri-lock-2-line text-lg"></i>
              <span>{{ isSubmittingScore ? 'Mengunci Nilai...' : 'Kunci & Simpan Nilai Resmi' }}</span>
            </button>
          </div>

          <div v-else class="p-12 rounded-2xl bg-slate-900 border border-slate-800 text-center text-slate-500 text-xs">
            <i class="ri-user-voice-line text-3xl mb-2 block text-slate-600"></i>
            <span>Pilih salah satu delegasi di daftar kanan untuk membuka formulir penilaian dan memulai stopwatch.</span>
          </div>
        </div>

        <!-- Right: Delegasi & Leaderboard Tabs -->
        <div class="lg:col-span-4 space-y-4">
          <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
              <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                <i class="ri-team-line text-amber-400"></i>
                <span>Delegasi {{ activeCompetition.title }}</span>
              </h3>
              <span class="text-xs font-mono font-bold text-amber-400">{{ currentDelegatesForComp.length }} Peserta</span>
            </div>

            <div class="divide-y divide-slate-800/80 max-h-[560px] overflow-y-auto space-y-1">
              <div v-if="currentDelegatesForComp.length === 0" class="text-center py-10 text-slate-500 text-xs">
                Belum ada delegasi terdaftar untuk cabang ini.
              </div>
              <div 
                v-for="d in currentDelegatesForComp" 
                :key="d.id"
                class="p-3 rounded-xl bg-slate-950/60 hover:bg-slate-950 border border-slate-800/80 hover:border-amber-500/40 transition-all"
              >
                <div class="flex items-center justify-between mb-1">
                  <div class="font-extrabold text-xs text-white">{{ d.school_name }}</div>
                  <span 
                    class="px-2 py-0.5 rounded text-[10px] font-bold"
                    :class="d.checkin_status === 'hadir' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'"
                  >
                    {{ d.checkin_status === 'hadir' ? 'Hadir' : 'Belum' }}
                  </span>
                </div>

                <div class="text-[11px] text-slate-400 mb-2">
                  <span>{{ d.mentor_name }}</span> • <span class="font-mono text-blue-400">{{ d.reg_code }}</span>
                </div>

                <button 
                  @click="spotlightParticipant(d)"
                  class="w-full py-1.5 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 text-amber-300 border border-amber-500/30 text-xs font-bold flex items-center justify-center gap-1.5 transition-all"
                >
                  <i class="ri-play-circle-line"></i>
                  <span>Panggil & Mulai Timer</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 5: BAGAN TURNAMEN SISTEM GUGUR                                -->
    <!-- ================================================================= -->
    <div v-if="activeTab === 'turnamen'" class="space-y-6">
      <!-- Tournament Selection Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
        <div class="flex items-center gap-2">
          <button 
            @click="selectCompetition({ id: 'volleyball-putra', title: 'Bola Voli Putra' })"
            :class="[
              'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all',
              activeCompetitionId === 'volleyball-putra' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800'
            ]"
          >
            <i class="ri-football-line text-sm"></i>
            <span>Voli Putra</span>
          </button>

          <button 
            @click="selectCompetition({ id: 'volleyball-putri', title: 'Bola Voli Putri' })"
            :class="[
              'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all',
              activeCompetitionId === 'volleyball-putri' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800'
            ]"
          >
            <i class="ri-football-line text-sm"></i>
            <span>Voli Putri</span>
          </button>

          <button 
            @click="selectCompetition({ id: 'mobile-legends', title: 'Mobile Legends (MLBB)' })"
            :class="[
              'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all',
              activeCompetitionId === 'mobile-legends' ? 'bg-indigo-600 text-white font-black' : 'text-slate-400 hover:text-white hover:bg-slate-800'
            ]"
          >
            <i class="ri-gamepad-line text-sm"></i>
            <span>Mobile Legends</span>
          </button>
        </div>

        <button 
          @click="generateBracket"
          class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 text-xs font-bold flex items-center gap-1.5 transition-all"
        >
          <i class="ri-shuffle-line text-amber-400"></i>
          <span>Undi Ulang Bagan 8 Besar</span>
        </button>
      </div>

      <!-- Interactive 8-Bracket Tree Visualizer -->
      <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl overflow-x-auto">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400 mb-6 flex items-center gap-2">
          <i class="ri-node-tree text-amber-400"></i>
          <span>Bagan Sistem Gugur 8 Besar Resmi • {{ activeCompetition.title }}</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 min-w-[760px]">
          <!-- Column 1: Quarter Finals -->
          <div class="space-y-4">
            <div class="text-xs font-extrabold text-blue-400 uppercase tracking-wider border-b border-slate-800 pb-2">
              Babak Perempat Final (QF)
            </div>
            <div 
              v-for="m in tournamentMatches.filter(m => m.round_order === 1)" 
              :key="m.id"
              @click="selectTournamentMatch(m)"
              :class="[
                'p-3.5 rounded-xl border transition-all cursor-pointer shadow-md',
                activeMatch?.id === m.id ? 'bg-blue-600/15 border-blue-500 shadow-blue-500/20' : 'bg-slate-950 border-slate-800 hover:border-slate-700'
              ]"
            >
              <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1 font-mono">
                <span>{{ m.match_number }}</span>
                <span class="text-emerald-400 font-bold uppercase">{{ m.status }}</span>
              </div>
              <div class="flex items-center justify-between text-xs font-bold text-white mb-1">
                <span>{{ m.team1_name || m.team1_placeholder || 'TBD' }}</span>
                <span class="font-mono text-amber-400">{{ m.team1_score }}</span>
              </div>
              <div class="flex items-center justify-between text-xs font-bold text-white">
                <span>{{ m.team2_name || m.team2_placeholder || 'TBD' }}</span>
                <span class="font-mono text-amber-400">{{ m.team2_score }}</span>
              </div>
            </div>
          </div>

          <!-- Column 2: Semi Finals -->
          <div class="space-y-4 md:pt-10">
            <div class="text-xs font-extrabold text-amber-400 uppercase tracking-wider border-b border-slate-800 pb-2">
              Babak Semi Final (SF)
            </div>
            <div 
              v-for="m in tournamentMatches.filter(m => m.round_order === 2)" 
              :key="m.id"
              @click="selectTournamentMatch(m)"
              :class="[
                'p-3.5 rounded-xl border transition-all cursor-pointer shadow-md',
                activeMatch?.id === m.id ? 'bg-amber-500/15 border-amber-500 shadow-amber-500/20' : 'bg-slate-950 border-slate-800 hover:border-slate-700'
              ]"
            >
              <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1 font-mono">
                <span>{{ m.match_number }}</span>
                <span class="text-emerald-400 font-bold uppercase">{{ m.status }}</span>
              </div>
              <div class="flex items-center justify-between text-xs font-bold text-white mb-1">
                <span>{{ m.team1_name || m.team1_placeholder || 'TBD' }}</span>
                <span class="font-mono text-amber-400">{{ m.team1_score }}</span>
              </div>
              <div class="flex items-center justify-between text-xs font-bold text-white">
                <span>{{ m.team2_name || m.team2_placeholder || 'TBD' }}</span>
                <span class="font-mono text-amber-400">{{ m.team2_score }}</span>
              </div>
            </div>
          </div>

          <!-- Column 3: Grand Final -->
          <div class="space-y-4 md:pt-24">
            <div class="text-xs font-extrabold text-emerald-400 uppercase tracking-wider border-b border-slate-800 pb-2 flex items-center gap-1.5">
              <i class="ri-trophy-fill text-amber-400"></i>
              <span>Grand Final (Championship)</span>
            </div>
            <div 
              v-for="m in tournamentMatches.filter(m => m.round_order === 3)" 
              :key="m.id"
              @click="selectTournamentMatch(m)"
              :class="[
                'p-4 rounded-xl border-2 transition-all cursor-pointer shadow-lg',
                activeMatch?.id === m.id ? 'bg-emerald-500/15 border-emerald-500 shadow-emerald-500/20' : 'bg-slate-950 border-slate-800 hover:border-emerald-500/40'
              ]"
            >
              <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1.5 font-mono">
                <span class="text-amber-400 font-black">FINAL</span>
                <span class="text-emerald-400 font-bold uppercase">{{ m.status }}</span>
              </div>
              <div class="flex items-center justify-between text-sm font-extrabold text-white mb-1.5">
                <span>{{ m.team1_name || m.team1_placeholder || 'Pemenang SF-1' }}</span>
                <span class="font-mono text-amber-400 text-lg">{{ m.team1_score }}</span>
              </div>
              <div class="flex items-center justify-between text-sm font-extrabold text-white">
                <span>{{ m.team2_name || m.team2_placeholder || 'Pemenang SF-2' }}</span>
                <span class="font-mono text-amber-400 text-lg">{{ m.team2_score }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Referee Scorekeeper Console for Selected Match -->
      <div v-if="activeMatch" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Konsol Wasit Pencatat Skor Laga</span>
            <h3 class="text-base font-extrabold text-white">{{ activeMatch.match_number }} • {{ activeMatch.round_name }}</h3>
          </div>
          <span class="px-2.5 py-1 rounded text-xs font-bold uppercase bg-slate-800 text-slate-300">
            {{ activeMatch.court || 'Lapangan Utama' }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Team 1 -->
          <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
            <span class="text-xs font-bold text-slate-400 block">Tim 1:</span>
            <div class="text-sm font-black text-white">{{ activeMatch.team1_name || activeMatch.team1_placeholder }}</div>
            <div class="flex items-center gap-3 pt-2">
              <label class="text-xs text-slate-400">Skor:</label>
              <input 
                type="number" 
                v-model.number="refereeScore1" 
                class="w-20 p-2 rounded-lg bg-slate-900 border border-slate-700 text-white font-mono text-center text-lg font-bold"
              />
              <div class="flex items-center gap-1">
                <button @click="refereeScore1++" class="w-8 h-8 rounded bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs">+</button>
                <button @click="refereeScore1 = Math.max(0, refereeScore1 - 1)" class="w-8 h-8 rounded bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs">-</button>
              </div>
            </div>
            <div>
              <label class="text-[11px] text-slate-500 block mb-1">Set Scores (contoh: 25, 23, 15):</label>
              <input 
                v-model="refereeSet1"
                type="text" 
                placeholder="25, 23, 15"
                class="w-full p-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white font-mono"
              />
            </div>
          </div>

          <!-- Team 2 -->
          <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
            <span class="text-xs font-bold text-slate-400 block">Tim 2:</span>
            <div class="text-sm font-black text-white">{{ activeMatch.team2_name || activeMatch.team2_placeholder }}</div>
            <div class="flex items-center gap-3 pt-2">
              <label class="text-xs text-slate-400">Skor:</label>
              <input 
                type="number" 
                v-model.number="refereeScore2" 
                class="w-20 p-2 rounded-lg bg-slate-900 border border-slate-700 text-white font-mono text-center text-lg font-bold"
              />
              <div class="flex items-center gap-1">
                <button @click="refereeScore2++" class="w-8 h-8 rounded bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs">+</button>
                <button @click="refereeScore2 = Math.max(0, refereeScore2 - 1)" class="w-8 h-8 rounded bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs">-</button>
              </div>
            </div>
            <div>
              <label class="text-[11px] text-slate-500 block mb-1">Set Scores (contoh: 21, 25, 12):</label>
              <input 
                v-model="refereeSet2"
                type="text" 
                placeholder="21, 25, 12"
                class="w-full p-2 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white font-mono"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
          <div>
            <label class="block text-slate-400 text-xs mb-1 font-semibold">Status Pertandingan:</label>
            <select v-model="refereeStatus" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
              <option value="scheduled">Dijadwalkan</option>
              <option value="ongoing">Sedang Berlangsung (LIVE)</option>
              <option value="completed">Selesai Pertandingan</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-400 text-xs mb-1 font-semibold">Lokasi Lapangan:</label>
            <input v-model="refereeCourt" type="text" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          </div>
          <div>
            <label class="block text-slate-400 text-xs mb-1 font-semibold">Nama Wasit:</label>
            <input v-model="refereeName" type="text" class="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white" />
          </div>
        </div>

        <button 
          @click="saveMatchScore"
          class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs flex items-center justify-center gap-2 transition-all shadow-md shadow-blue-600/20"
          :disabled="isSavingMatch"
        >
          <i class="ri-save-line text-base"></i>
          <span>{{ isSavingMatch ? 'Menyimpan...' : 'Simpan Skor & Perbarui Bagan' }}</span>
        </button>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- TAB 6: PRINT STUDIO ID CARD                                       -->
    <!-- ================================================================= -->
    <div v-if="activeTab === 'print'" class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl print:hidden">
        <div>
          <h2 class="text-sm font-extrabold text-white flex items-center gap-2">
            <i class="ri-printer-line text-purple-400"></i>
            <span>Studio Cetak Kartu Tanda Pengenal Delegasi</span>
          </h2>
          <p class="text-xs text-slate-400 mt-0.5">
            Cetak kartu ID Card peserta kontingen bertanda barcode resmi untuk keperluan gate check-in hari-H.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <select 
            v-model="printCompFilter"
            class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
          >
            <option value="all">Semua Cabang Lomba</option>
            <option v-for="c in competitions" :key="c.id" :value="c.id">{{ c.title }}</option>
          </select>

          <button 
            @click="triggerPrint"
            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow-md shadow-purple-600/20"
          >
            <i class="ri-printer-fill text-sm"></i>
            <span>Cetak Halaman (Ctrl+P)</span>
          </button>
        </div>
      </div>

      <!-- Printable ID Card Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div 
          v-for="card in printableDelegates" 
          :key="card.id"
          class="p-4 rounded-2xl bg-slate-900 border border-slate-800 print:bg-white print:text-black print:border-black/30 space-y-3 relative overflow-hidden shadow-md"
        >
          <!-- Card Header -->
          <div class="text-center border-b border-slate-800 print:border-black/20 pb-2">
            <div class="text-[10px] font-black uppercase tracking-wider text-amber-400 print:text-black">
              SEMARAK ALIYAH XIV 2026
            </div>
            <div class="text-[9px] text-slate-400 print:text-slate-600">MA Unggulan Wahid Hasyim Balung</div>
          </div>

          <!-- Participant Info -->
          <div class="text-center space-y-1">
            <div class="w-14 h-14 mx-auto rounded-full bg-slate-800 print:bg-slate-200 flex items-center justify-center font-black text-amber-400 text-lg border-2 border-amber-500/30">
              <i class="ri-user-star-line"></i>
            </div>
            <div class="font-extrabold text-sm text-white print:text-black truncate">{{ card.school_name }}</div>
            <div class="text-[10px] font-bold text-amber-400 print:text-slate-800">{{ card.competition_title || card.competition_id }}</div>
            <div class="text-[10px] text-slate-400 print:text-slate-600">Pendamping: {{ card.mentor_name }}</div>
          </div>

          <!-- Barcode Footer -->
          <div class="text-center pt-2 border-t border-slate-800 print:border-black/20">
            <div class="font-mono text-xs font-black text-blue-400 print:text-black">{{ card.reg_code }}</div>
            <div class="text-[9px] text-slate-500 font-mono">{{ card.barcode }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL: KUSTOMISASI RUBRIK PENILAIAN (PERSENTASE & SKOR POIN)       -->
    <!-- ================================================================= -->
    <div 
      v-if="showRubricModal" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
    >
      <div class="w-full max-w-xl rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
          <h3 class="text-base font-extrabold text-white flex items-center gap-2">
            <i class="ri-settings-4-fill text-amber-400"></i>
            <span>Kustomisasi Rubrik Penilaian: {{ activeCompetition.title }}</span>
          </h3>
          <button @click="showRubricModal = false" class="text-slate-400 hover:text-white">
            <i class="ri-close-line text-xl"></i>
          </button>
        </div>

        <!-- Mode Picker (Percentage vs Point Score) -->
        <div class="space-y-2">
          <label class="text-xs font-bold text-slate-300 block">Pilih Mode Perhitungan Nilai:</label>
          <div class="grid grid-cols-2 gap-3">
            <label 
              :class="[
                'p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition-all',
                editableRubricType === 'percentage' ? 'bg-amber-500/15 border-amber-500 text-white font-bold' : 'bg-slate-950 border-slate-800 text-slate-400'
              ]"
            >
              <input type="radio" value="percentage" v-model="editableRubricType" class="accent-amber-500" />
              <div>
                <div class="text-xs">Mode Persentase (%)</div>
                <div class="text-[10px] text-slate-500">Total bobot berimbang = 100%</div>
              </div>
            </label>

            <label 
              :class="[
                'p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition-all',
                editableRubricType === 'point' ? 'bg-blue-600/15 border-blue-500 text-white font-bold' : 'bg-slate-950 border-slate-800 text-slate-400'
              ]"
            >
              <input type="radio" value="point" v-model="editableRubricType" class="accent-blue-500" />
              <div>
                <div class="text-xs">Mode Skor / Poin Maksimal</div>
                <div class="text-[10px] text-slate-500">Masing-masing kriteria punya skor maks</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Criteria List Inputs -->
        <div class="space-y-2.5 max-h-[300px] overflow-y-auto pr-1">
          <div 
            v-for="(row, idx) in editableCriteriaList" 
            :key="idx"
            class="flex items-center gap-2"
          >
            <input 
              v-model="row.name" 
              type="text" 
              placeholder="Nama Kriteria..."
              class="flex-1 p-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
            />
            <div class="flex items-center gap-1.5 w-28 shrink-0">
              <input 
                v-model.number="row.weight" 
                type="number" 
                class="w-full p-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white text-center font-mono font-bold"
              />
              <span class="text-xs font-bold text-slate-400 font-mono">
                {{ editableRubricType === 'percentage' ? '%' : 'Pt' }}
              </span>
            </div>
            <button 
              @click="removeCriterionRow(idx)"
              class="w-8 h-8 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 flex items-center justify-center transition-colors"
              title="Hapus Kriteria"
            >
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-800">
          <button 
            @click="addCriterionRow"
            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold flex items-center gap-1"
          >
            <i class="ri-add-line"></i>
            <span>Tambah Kriteria</span>
          </button>

          <div class="font-mono text-xs font-bold" :class="editableRubricType === 'percentage' && rubricWeightSum !== 100 ? 'text-rose-400' : 'text-emerald-400'">
            Total: {{ rubricWeightSum }} {{ editableRubricType === 'percentage' ? '%' : 'Poin' }}
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-800">
          <button 
            @click="resetRubricToDefault"
            class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white text-xs font-semibold"
          >
            Reset Standar Juknis
          </button>

          <div class="flex items-center gap-2">
            <button 
              @click="showRubricModal = false"
              class="px-3.5 py-2 rounded-xl bg-slate-800 text-slate-400 text-xs"
            >
              Batal
            </button>
            <button 
              @click="saveRubricCustomization"
              class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs flex items-center gap-1.5"
              :disabled="isSavingRubric"
            >
              <i class="ri-check-line font-bold"></i>
              <span>{{ isSavingRubric ? 'Menyimpan...' : 'Simpan Rubrik' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
