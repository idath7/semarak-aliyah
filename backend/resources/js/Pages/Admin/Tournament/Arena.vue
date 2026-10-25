<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { initEcho } from '@/echo';

const props = defineProps({
  competition: Object,
  teams: Array,
  matches: Array,
  rounds: Object,
  selectedMatch: Object,
  completedMatchesCount: Number,
  totalMatchesCount: Number,
});

// Local state for active match
const activeMatch = ref(props.selectedMatch || props.matches?.[0] || null);
const showBracketGeneratorModal = ref(false);
const showTeamSelectorModal = ref(false);
const liveEventLog = ref([]);

// Form for Referee Scorekeeper Console
const scoreForm = useForm({
  team1_score: activeMatch.value?.team1_score ?? 0,
  team2_score: activeMatch.value?.team2_score ?? 0,
  team1_set_scores: activeMatch.value?.team1_set_scores ?? '',
  team2_set_scores: activeMatch.value?.team2_set_scores ?? '',
  status: activeMatch.value?.status ?? 'scheduled',
  court: activeMatch.value?.court ?? 'Lapangan Utama',
  referee_name: activeMatch.value?.referee_name ?? 'Wasit Utama',
  notes: activeMatch.value?.notes ?? '',
});

// Sync form whenever activeMatch changes
function selectMatch(m) {
  activeMatch.value = m;
  scoreForm.team1_score = m.team1_score ?? 0;
  scoreForm.team2_score = m.team2_score ?? 0;
  scoreForm.team1_set_scores = m.team1_set_scores ?? '';
  scoreForm.team2_set_scores = m.team2_set_scores ?? '';
  scoreForm.status = m.status ?? 'scheduled';
  scoreForm.court = m.court || 'Lapangan Utama';
  scoreForm.referee_name = m.referee_name || 'Wasit Utama';
  scoreForm.notes = m.notes || '';
}

// Submit referee score update
function submitScore() {
  if (!activeMatch.value) return;
  scoreForm.post(`/admin/tournament/match/${activeMatch.value.id}/score`, {
    preserveScroll: true,
    onSuccess: () => {
      // Form submitted successfully, WebSocket will broadcast updates
    }
  });
}

// Real-time Reverb WebSocket Listener
let tournamentChannel = null;

onMounted(() => {
  try {
    const echo = initEcho();
    if (echo && props.competition?.id) {
      tournamentChannel = echo.channel(`tournament.${props.competition.id}`);
      tournamentChannel.listen('.match.updated', (event) => {
        const updated = event.match;
        if (!updated) return;

        // 1. Update in matches list
        const idx = props.matches.findIndex(m => m.id === updated.id);
        if (idx !== -1) {
          props.matches[idx] = updated;
        }

        // 2. If it's the active match, update referee console
        if (activeMatch.value && activeMatch.value.id === updated.id) {
          activeMatch.value = updated;
          scoreForm.team1_score = updated.team1_score;
          scoreForm.team2_score = updated.team2_score;
          scoreForm.team1_set_scores = updated.team1_set_scores;
          scoreForm.team2_set_scores = updated.team2_set_scores;
          scoreForm.status = updated.status;
        }

        // 3. Log live activity
        liveEventLog.value.unshift({
          text: `Skor ${updated.match_number} diperbarui: ${updated.team1_score} - ${updated.team2_score}`,
          time: new Date().toLocaleTimeString('id-ID')
        });
        if (liveEventLog.value.length > 5) liveEventLog.value.pop();
      });
    }
  } catch (err) {
    console.warn('Reverb Tournament Listener:', err);
  }
});

onUnmounted(() => {
  if (tournamentChannel && props.competition?.id) {
    try {
      const echo = initEcho();
      echo.leaveChannel(`tournament.${props.competition.id}`);
    } catch(e) {}
  }
});

// Auto-generate bracket form
const generateForm = useForm({
  seed_mode: 'random'
});

function handleGenerateBracket() {
  generateForm.post(`/admin/tournament/bracket/${props.competition.id}/generate`, {
    onSuccess: () => {
      showBracketGeneratorModal.value = false;
    }
  });
}
</script>

<template>
  <AppLayout>
    <Head :title="`Arena Turnamen: ${competition.title}`" />

    <!-- Arena Header Banner -->
    <div class="mb-6 p-5 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/80 to-slate-900 border border-slate-800 shadow-xl relative overflow-hidden">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 relative z-10">
        <div>
          <div class="flex items-center gap-2 mb-2 flex-wrap">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-red-500/20 text-red-400 border border-red-500/30">
              <i class="ri-sword-line mr-1"></i> SISTEM GUGUR (KNOCKOUT)
            </span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase">
              {{ competition.category }}
            </span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-800 text-slate-300 border border-slate-700 flex items-center gap-1">
              <i class="ri-flashlight-fill text-amber-400"></i>
              <span>Laravel Reverb WebSocket Active</span>
            </span>
          </div>
          <h1 class="text-xl lg:text-2xl font-black text-white flex items-center gap-2">
            <span>{{ competition.icon }}</span>
            <span>{{ competition.title }}</span>
          </h1>
          <p class="text-xs text-slate-400 mt-1 max-w-2xl">
            Bagan resmi sistem gugur turnamen. Skor dan status pemenang tersinkronisasi otomatis via WebSocket ke seluruh layar juri, wasit, dan scoreboard panggung.
          </p>
        </div>

        <!-- Controls: Acak Ulang Bagan, Status Laga -->
        <div class="flex items-center gap-2.5 flex-wrap">
          <button 
            @click="showBracketGeneratorModal = true"
            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950 transition-all flex items-center gap-1.5 shadow-md shadow-amber-500/10"
          >
            <i class="ri-shuffle-line text-sm"></i>
            <span>Undi / Acak Bagan Otomatis</span>
          </button>
          <div class="px-3 py-2 rounded-xl bg-slate-950/80 border border-slate-800 text-xs flex items-center gap-2">
            <span class="text-slate-400">Kemajuan:</span>
            <span class="font-bold text-emerald-400">{{ completedMatchesCount }} / {{ totalMatchesCount }} Laga Selesai</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Workspace Grid: Referee Console (Left) + Interactive Bracket Tree (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- LEFT COLUMN: Wasit / Referee Scorekeeper Stadium Console (lg:col-span-5 xl:col-span-4) -->
      <div class="lg:col-span-5 xl:col-span-4 space-y-4">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
          <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                <i class="ri-remote-control-line text-sm"></i>
              </div>
              <h3 class="text-sm font-bold text-white">Lembar Wasit & Papan Skor</h3>
            </div>
            <!-- Match Status Badge -->
            <span 
              v-if="activeMatch"
              :class="[
                'px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider',
                activeMatch.status === 'ongoing' ? 'bg-red-500/20 text-red-400 border border-red-500/30' :
                activeMatch.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' :
                'bg-slate-800 text-slate-400 border border-slate-700'
              ]"
            >
              <span v-if="activeMatch.status === 'ongoing'" class="live-pulse-dot mr-1"></span>
              {{ activeMatch.status === 'ongoing' ? 'LIVE ARENA' : activeMatch.status === 'completed' ? 'SELESAI' : 'TERJADWAL' }}
            </span>
          </div>

          <div v-if="activeMatch">
            <!-- Match Header Badge -->
            <div class="flex items-center justify-between text-xs text-slate-400 mb-3 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800/80">
              <span class="font-mono font-bold text-amber-400">{{ activeMatch.match_number }}</span>
              <span class="font-semibold text-slate-300">{{ activeMatch.round_name }}</span>
            </div>

            <!-- ELECTRONIC DIGITAL SCOREBOARD (ORBITRON FONT) -->
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 mb-5 text-center shadow-inner relative overflow-hidden">
              <div class="grid grid-cols-5 items-center gap-2">
                <!-- Team 1 -->
                <div class="col-span-2">
                  <div class="text-xs font-bold text-slate-300 truncate mb-1">
                    {{ activeMatch.team1?.school_name || activeMatch.team1_placeholder || 'TBD' }}
                  </div>
                  <div class="font-digital text-4xl lg:text-5xl font-black text-amber-400">
                    {{ scoreForm.team1_score }}
                  </div>
                  <input 
                    type="number" 
                    min="0" 
                    v-model.number="scoreForm.team1_score"
                    class="w-16 mt-2 px-2 py-1 rounded-lg bg-slate-900 border border-slate-700 text-center font-bold text-sm text-white focus:outline-none focus:border-amber-400"
                  />
                </div>

                <!-- VS Badge -->
                <div class="col-span-1 flex flex-col items-center justify-center">
                  <span class="text-xs font-black text-slate-600 uppercase tracking-widest">VS</span>
                  <span class="text-[10px] text-slate-500 mt-1">SETS</span>
                </div>

                <!-- Team 2 -->
                <div class="col-span-2">
                  <div class="text-xs font-bold text-slate-300 truncate mb-1">
                    {{ activeMatch.team2?.school_name || activeMatch.team2_placeholder || 'TBD' }}
                  </div>
                  <div class="font-digital text-4xl lg:text-5xl font-black text-amber-400">
                    {{ scoreForm.team2_score }}
                  </div>
                  <input 
                    type="number" 
                    min="0" 
                    v-model.number="scoreForm.team2_score"
                    class="w-16 mt-2 px-2 py-1 rounded-lg bg-slate-900 border border-slate-700 text-center font-bold text-sm text-white focus:outline-none focus:border-amber-400"
                  />
                </div>
              </div>
            </div>

            <!-- Set Score Inputs & Settings -->
            <form @submit.prevent="submitScore" class="space-y-3.5">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Poin Set Tim 1:</label>
                  <input 
                    type="text" 
                    v-model="scoreForm.team1_set_scores"
                    placeholder="Contoh: 25, 25"
                    class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-amber-400"
                  />
                </div>
                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Poin Set Tim 2:</label>
                  <input 
                    type="text" 
                    v-model="scoreForm.team2_set_scores"
                    placeholder="Contoh: 23, 21"
                    class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-amber-400"
                  />
                </div>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">Status Pertandingan:</label>
                <CustomSelect 
                  v-model="scoreForm.status"
                  size="sm"
                  variant="dark-slate"
                >
                  <option value="scheduled">Terjadwal (Scheduled)</option>
                  <option value="ongoing">Sedang Bertanding (Ongoing - Live Scoreboard)</option>
                  <option value="completed">Selesai (Completed - Otomatis Loloskan Pemenang)</option>
                </CustomSelect>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Lapangan / Court:</label>
                  <input 
                    type="text" 
                    v-model="scoreForm.court"
                    class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400"
                  />
                </div>
                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Nama Wasit:</label>
                  <input 
                    type="text" 
                    v-model="scoreForm.referee_name"
                    class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400"
                  />
                </div>
              </div>

              <button 
                type="submit" 
                :disabled="scoreForm.processing"
                class="w-full mt-2 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-extrabold text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50"
              >
                <i class="ri-broadcast-line text-sm"></i>
                <span>{{ scoreForm.processing ? 'Menyimpan...' : 'Simpan & Siarkan Real-time' }}</span>
              </button>
            </form>
          </div>

          <div v-else class="text-center py-10 text-xs text-slate-500">
            Pilih salah satu pertandingan pada bagan untuk mulai input skor wasit.
          </div>
        </div>

        <!-- Live Real-time Activity Feeds -->
        <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-md">
          <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2 flex items-center justify-between">
            <span>Aktivitas Wasit Real-time</span>
            <i class="ri-wifi-line text-emerald-400"></i>
          </div>
          <div v-if="liveEventLog.length > 0" class="space-y-1.5">
            <div 
              v-for="(item, i) in liveEventLog" 
              :key="i"
              class="text-[11px] p-2 rounded-lg bg-slate-950/60 border border-slate-800 flex items-center justify-between text-slate-300"
            >
              <span>{{ item.text }}</span>
              <span class="font-mono text-[10px] text-slate-500">{{ item.time }}</span>
            </div>
          </div>
          <div v-else class="text-xs text-slate-500 text-center py-2">
            Belum ada pembaruan laga. Perubahan skor wasit akan muncul seketika di sini.
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN: Interactive Knockout Bracket Tree (lg:col-span-7 xl:col-span-8) -->
      <div class="lg:col-span-7 xl:col-span-8">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl overflow-hidden">
          <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800 flex-wrap gap-2">
            <div>
              <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i class="ri-node-tree text-amber-400"></i>
                <span>Pohon Bagan Turnamen Sistem Gugur</span>
              </h3>
              <p class="text-[11px] text-slate-400">Klik salah satu kotak laga untuk memuat ke konsol wasit dan memperbarui skor set.</p>
            </div>
            <div class="text-xs text-amber-400 font-semibold flex items-center gap-1">
              <i class="ri-cursor-line"></i> Klik Match untuk Skor
            </div>
          </div>

          <!-- Bracket Canvas Columns -->
          <div class="flex items-stretch gap-6 overflow-x-auto pb-4 pt-2 bracket-tree-canvas min-h-[560px]">
            <div 
              v-for="(roundMatches, roundName) in rounds" 
              :key="roundName"
              class="w-64 shrink-0 flex flex-col justify-around gap-4"
            >
              <!-- Round Header -->
              <div class="py-1.5 px-3 rounded-full text-center text-[11px] font-extrabold uppercase tracking-wider bg-slate-800/80 text-amber-400 border border-slate-700/80 shadow-sm">
                {{ roundName }}
              </div>

              <!-- Matches in this Round -->
              <div 
                v-for="m in roundMatches" 
                :key="m.id"
                @click="selectMatch(m)"
                :class="[
                  'p-3 rounded-xl border transition-all cursor-pointer relative shadow-md group',
                  activeMatch?.id === m.id 
                    ? 'bg-slate-800/90 border-amber-400 ring-2 ring-amber-400/30' 
                    : 'bg-slate-950/80 border-slate-800 hover:border-slate-700 hover:bg-slate-900'
                ]"
              >
                <!-- Match Meta -->
                <div class="flex items-center justify-between text-[10px] text-slate-400 mb-2">
                  <span class="font-mono font-bold text-amber-400">{{ m.match_number }}</span>
                  <span 
                    :class="[
                      'px-1.5 py-0.2 rounded font-semibold',
                      m.status === 'ongoing' ? 'bg-red-500/20 text-red-400' :
                      m.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' :
                      'text-slate-500'
                    ]"
                  >
                    {{ m.status === 'ongoing' ? 'LIVE' : m.status === 'completed' ? 'FIN' : 'SCH' }}
                  </span>
                </div>

                <!-- Team 1 -->
                <div 
                  :class="[
                    'flex items-center justify-between p-2 rounded-lg text-xs transition-colors mb-1.5',
                    m.winner_registration_id && m.winner_registration_id === m.team1_registration_id
                      ? 'bg-emerald-500/20 text-emerald-300 font-bold border-l-2 border-emerald-400'
                      : 'bg-slate-900 text-slate-300'
                  ]"
                >
                  <span class="truncate max-w-[140px]">{{ m.team1?.school_name || m.team1_placeholder || 'TBD' }}</span>
                  <span class="font-digital text-sm font-black text-amber-400 ml-2">{{ m.team1_score }}</span>
                </div>

                <!-- Team 2 -->
                <div 
                  :class="[
                    'flex items-center justify-between p-2 rounded-lg text-xs transition-colors',
                    m.winner_registration_id && m.winner_registration_id === m.team2_registration_id
                      ? 'bg-emerald-500/20 text-emerald-300 font-bold border-l-2 border-emerald-400'
                      : 'bg-slate-900 text-slate-300'
                  ]"
                >
                  <span class="truncate max-w-[140px]">{{ m.team2?.school_name || m.team2_placeholder || 'TBD' }}</span>
                  <span class="font-digital text-sm font-black text-amber-400 ml-2">{{ m.team2_score }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Undi / Acak Bagan Otomatis -->
    <div v-if="showBracketGeneratorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
      <div class="w-full max-w-md p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl">
        <h3 class="text-base font-bold text-white mb-2 flex items-center gap-2">
          <i class="ri-shuffle-line text-amber-400"></i>
          <span>Undi Bagan Sistem Gugur</span>
        </h3>
        <p class="text-xs text-slate-400 mb-4">
          Sistem akan mengacak slot tim peserta yang terdaftar ke babak perempat final. Seluruh pemenang akan otomatis melaju ke semi-final dan grand final saat skor wasit selesai diinput.
        </p>

        <div class="flex justify-end gap-2 mt-6">
          <button 
            type="button" 
            @click="showBracketGeneratorModal = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-800"
          >
            Batal
          </button>
          <button 
            type="button" 
            @click="handleGenerateBracket"
            :disabled="generateForm.processing"
            class="px-4 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-400 text-slate-950"
          >
            {{ generateForm.processing ? 'Mengacak...' : 'Mulai Acak Bagan Sekarang' }}
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
