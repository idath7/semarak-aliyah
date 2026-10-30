<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import CustomSelect from '@/Components/CustomSelect.vue';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import { useToast } from '@/Composables/useToast';
import { useTheme } from '@/Composables/useTheme';

const page = usePage();

const props = defineProps({
  user: { type: Object, required: true },
  matchedSchool: { type: Object, default: () => ({}) },
  competitions: { type: Array, default: () => [] },
  existingCompIds: { type: Array, default: () => [] },
  isAllInVerified: { type: Boolean, default: false },
  allInRegCode: { type: String, default: null },
  allInSchoolName: { type: String, default: null },
  pricingSettings: { type: Object, default: () => ({}) },
  generatedAllInCode: { type: String, default: null },
  generatedAllInSchool: { type: String, default: null },
});

const toast = useToast();
const { isDark, toggleTheme, initTheme } = useTheme();

onMounted(() => {
  initTheme();
  const primary = activeBankAccounts.value.find(a => a.is_primary) || activeBankAccounts.value[0];
  const defaultMethod = 'Transfer ' + (primary?.bank_name || 'Bank BSI');
  if (!form.payment_method || form.payment_method === 'Transfer Bank BSI') {
    form.payment_method = defaultMethod;
  }
  if (!allInForm.payment_method || allInForm.payment_method === 'Transfer Bank BSI') {
    allInForm.payment_method = defaultMethod;
  }
});

// Package Type: 'Paket Satuan' | 'Paket All-In'
const selectedPackageType = ref('Paket Satuan');

// State Verifikasi Kode Paket Komplit (All-In)
const isCheckingPackageCode = ref(false);
const isManualCodeVerified = ref(!!props.isAllInVerified || !!props.allInRegCode);
const codeVerificationError = ref('');
const manualCodeSuccessData = ref(props.allInRegCode ? {
  reg_code: props.allInRegCode,
  school_name: props.allInSchoolName || props.user.school_name,
  is_fully_verified: true,
} : null);

// Grade options for team members (Rule 1: CustomSelect)
const gradeOptions = [
  { value: 'Kelas 7 SMP/MTs', label: 'Kelas 7 SMP/MTs' },
  { value: 'Kelas 8 SMP/MTs', label: 'Kelas 8 SMP/MTs' },
  { value: 'Kelas 9 SMP/MTs', label: 'Kelas 9 SMP/MTs' },
];

// 31 Kecamatan Kabupaten Jember for CustomSelect (Rule 1: No native select, search enabled)
const kecamatanOptions = [
  { value: 'Ajung', label: 'Kec. Ajung' },
  { value: 'Ambulu', label: 'Kec. Ambulu' },
  { value: 'Arjasa', label: 'Kec. Arjasa' },
  { value: 'Balung', label: 'Kec. Balung' },
  { value: 'Bangsalsari', label: 'Kec. Bangsalsari' },
  { value: 'Gumukmas', label: 'Kec. Gumukmas' },
  { value: 'Jelbuk', label: 'Kec. Jelbuk' },
  { value: 'Jenggawah', label: 'Kec. Jenggawah' },
  { value: 'Jombang', label: 'Kec. Jombang' },
  { value: 'Kalisat', label: 'Kec. Kalisat' },
  { value: 'Kaliwates', label: 'Kec. Kaliwates' },
  { value: 'Kencong', label: 'Kec. Kencong' },
  { value: 'Ledokombo', label: 'Kec. Ledokombo' },
  { value: 'Mayang', label: 'Kec. Mayang' },
  { value: 'Mumbulsari', label: 'Kec. Mumbulsari' },
  { value: 'Pakusari', label: 'Kec. Pakusari' },
  { value: 'Panti', label: 'Kec. Panti' },
  { value: 'Patrang', label: 'Kec. Patrang' },
  { value: 'Puger', label: 'Kec. Puger' },
  { value: 'Rambipuji', label: 'Kec. Rambipuji' },
  { value: 'Semboro', label: 'Kec. Semboro' },
  { value: 'Silo', label: 'Kec. Silo' },
  { value: 'Sukorambi', label: 'Kec. Sukorambi' },
  { value: 'Sukowono', label: 'Kec. Sukowono' },
  { value: 'Sumberbaru', label: 'Kec. Sumberbaru' },
  { value: 'Sumberjambe', label: 'Kec. Sumberjambe' },
  { value: 'Sumbersari', label: 'Kec. Sumbersari' },
  { value: 'Tanggul', label: 'Kec. Tanggul' },
  { value: 'Tempurejo', label: 'Kec. Tempurejo' },
  { value: 'Umbulsari', label: 'Kec. Umbulsari' },
  { value: 'Wuluhan', label: 'Kec. Wuluhan' },
];

function resolveInitialRegCompId() {
  if (typeof window !== 'undefined') {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const fromUrl = urlParams.get('lomba') || urlParams.get('cabang');
      if (fromUrl) {
        const matched = (props.competitions || []).find(c => String(c.id) === String(fromUrl) || c.slug === fromUrl);
        if (matched) return matched.id;
      }
      const saved = sessionStorage.getItem('semarak_reg_last_competition_id') || localStorage.getItem('semarak_reg_last_competition_id');
      if (saved) {
        const matched = (props.competitions || []).find(c => String(c.id) === String(saved) || c.slug === saved);
        if (matched) return matched.id;
      }
    } catch (e) {}
  }
  return props.competitions?.[0]?.id || '';
}

// Form state untuk PAKET SATUAN (Per Cabang Lomba)
const form = useForm({
  package_type: 'Paket Satuan',
  competition_id: resolveInitialRegCompId(),
  participants_names: '',
  notes: '',
  payment_method: 'Transfer Bank BSI',
  file_documents: [],
  payment_proof: null,
  allin_code: props.allInRegCode || '',
  custom_fields: {},
  custom_files: {},
});

// Form state untuk PAKET KOMPLIT (Hanya 7 Informasi Sekolah & Koordinator)
const allInForm = useForm({
  package_type: 'Paket All-In',
  school_name: props.matchedSchool?.name || (props.user.school_name !== '-' ? props.user.school_name : ''),
  school_district: props.matchedSchool?.kecamatan || (props.user.school_district !== '-' ? props.user.school_district : ''),
  npsn: props.matchedSchool?.npsn || '',
  school_address: props.matchedSchool?.alamat || '',
  coordinator_name: props.user.name || '',
  coordinator_phone: props.user.phone || '',
  coordinator_email: props.user.email || '',
  payment_method: 'Transfer Bank BSI',
  payment_proof: null,
  notes: '',
});

// State Modal Pop-up Sukses Kode Paket Komplit Otomatis
const showSuccessCodeModal = ref(false);
const newGeneratedCode = ref(props.generatedAllInCode || '');
const newGeneratedSchool = ref(props.generatedAllInSchool || props.user.school_name || '');
const isCopied = ref(false);

watch(() => props.generatedAllInCode, (code) => {
  if (code) {
    newGeneratedCode.value = code;
    newGeneratedSchool.value = props.generatedAllInSchool || allInForm.school_name || props.user.school_name;
    showSuccessCodeModal.value = true;
  }
}, { immediate: true });

function copyGeneratedCode() {
  if (navigator.clipboard && newGeneratedCode.value) {
    navigator.clipboard.writeText(newGeneratedCode.value);
    isCopied.value = true;
    toast.success('Kode Paket Komplit berhasil disalin ke clipboard!');
    setTimeout(() => { isCopied.value = false; }, 2500);
  }
}

function useGeneratedCodeForSingle() {
  const code = newGeneratedCode.value;
  showSuccessCodeModal.value = false;
  selectedPackageType.value = 'Paket Satuan';
  form.allin_code = code;
  verifyPackageCodeManual();
  toast.info(`Kode ${code} otomatis terpasang pada Paket Satuan.`);
}

// Format Tanggal Indonesia (Rule 4: Selalu dd/mm/yyyy & Jam 24 Jam)
function formatIndoDate(dateStr) {
  if (!dateStr) return '-';
  const str = String(dateStr).trim();
  const matchIso = str.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?/);
  if (matchIso) {
    const [, y, m, d, hh, mm] = matchIso;
    if (hh !== undefined && mm !== undefined) {
      return `${d}/${m}/${y} ${hh}:${mm} WIB`;
    }
    return `${d}/${m}/${y}`;
  }
  if (/^\d{2}\/\d{2}\/\d{4}/.test(str)) {
    return str;
  }
  try {
    const dt = new Date(str);
    if (!isNaN(dt.getTime())) {
      const d = String(dt.getDate()).padStart(2, '0');
      const m = String(dt.getMonth() + 1).padStart(2, '0');
      const y = dt.getFullYear();
      const hh = String(dt.getHours()).padStart(2, '0');
      const mm = String(dt.getMinutes()).padStart(2, '0');
      if (str.includes(':') || dt.getHours() !== 0 || dt.getMinutes() !== 0) {
        return `${d}/${m}/${y} ${hh}:${mm} WIB`;
      }
      return `${d}/${m}/${y}`;
    }
  } catch (e) {}
  return str;
}

// Format Currency
function formatRupiah(num) {
  return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

// Manual verification of Package Code
async function verifyPackageCodeManual() {
  const code = (form.allin_code || '').trim();
  if (!code) {
    codeVerificationError.value = 'Silakan masukkan Kode Paket Komplit terlebih dahulu.';
    isManualCodeVerified.value = false;
    manualCodeSuccessData.value = null;
    return;
  }

  isCheckingPackageCode.value = true;
  codeVerificationError.value = '';
  try {
    const res = await fetch(`/api/schools/verify-package-code?code=${encodeURIComponent(code)}`);
    const json = await res.json();
    if (res.ok && json.status === 'success' && json.valid) {
      isManualCodeVerified.value = true;
      manualCodeSuccessData.value = json.data;
      codeVerificationError.value = '';
      form.allin_code = json.data.reg_code;
      selectedPackageType.value = 'Paket All-In';
      form.package_type = 'Paket All-In';
      toast.success(`Kode terverifikasi! Terhubung ke Paket Komplit: ${json.data.school_name}`);
    } else {
      isManualCodeVerified.value = false;
      manualCodeSuccessData.value = null;
      codeVerificationError.value = json.message || 'Kode Paket Komplit tidak valid atau tidak terdaftar.';
      toast.error(codeVerificationError.value);
    }
  } catch (err) {
    isManualCodeVerified.value = false;
    manualCodeSuccessData.value = null;
    codeVerificationError.value = 'Gagal memverifikasi kode paket.';
  } finally {
    isCheckingPackageCode.value = false;
  }
}

function clearManualPackageCode() {
  form.allin_code = '';
  isManualCodeVerified.value = false;
  manualCodeSuccessData.value = null;
  codeVerificationError.value = '';
}

function onAllInCodeInput() {
  if (isManualCodeVerified.value && !props.isAllInVerified) {
    isManualCodeVerified.value = false;
    manualCodeSuccessData.value = null;
  }
  codeVerificationError.value = '';
}

// Competition select options for CustomSelect (Rule 1)
const competitionOptions = computed(() => {
  return (props.competitions || []).map(c => {
    const isRegistered = (props.existingCompIds || []).includes(c.id);
    const isDirect = c.registration_type === 'direct_contact' || c.is_direct_registration;
    const quotaLeft = Math.max(0, (c.max_quota || 0) - (c.current_quota || 0));
    let statusSuffix = '';
    if (isRegistered) {
      statusSuffix = ' [Sudah Terdaftar]';
    } else if (c.is_closed) {
      statusSuffix = ' [Pendaftaran Ditutup]';
    } else if (isDirect) {
      statusSuffix = ` [📲 Daftar via Kontak Koordinator • Rp ${Number(c.fee).toLocaleString('id-ID')}]`;
    } else {
      statusSuffix = ` [Sisa ${quotaLeft} Kuota • Rp ${Number(c.fee).toLocaleString('id-ID')}]`;
    }

    return {
      value: c.id,
      label: `${c.title}${statusSuffix}`,
      disabled: c.is_closed || (isRegistered && selectedPackageType.value !== 'Paket All-In'),
      fee: c.fee,
      isRegistered,
      isClosed: c.is_closed,
      isDirect,
      deadline: c.registration_deadline,
      description: c.description,
    };
  });
});

// Selected competition object
const currentCompetition = computed(() => {
  if (!form.competition_id) return null;
  return (props.competitions || []).find(c => c.id === form.competition_id) || null;
});

function isTeamFieldName(name = '', label = '') {
  const n = String(name || '').toLowerCase().trim();
  const l = String(label || '').toLowerCase().trim();
  const keys = ['nama_tim', 'team_name', 'nama_squad', 'nama_regu', 'team', 'squad', 'regu'];
  return keys.includes(n) || l.includes('nama tim') || l.includes('nama skuad') || l.includes('nama regu');
}

// Repeater field (jika cabang lomba menggunakan konfigurasi tim / anggota jamak)
const repeaterField = computed(() => {
  if (!currentCompetition.value || !Array.isArray(currentCompetition.value.custom_fields)) return null;
  return currentCompetition.value.custom_fields.find(f => f.type === 'participant_repeater') || null;
});

// Konfigurasi Input Nama Tim / Skuad (Jika aktif dari Sub-Fields Repeater atau Custom Fields)
const teamFieldConfig = computed(() => {
  // 1. Cek dari sub-fields repeater
  const rf = repeaterField.value;
  if (rf?.sub_fields?.team_name?.active) {
    return {
      name: 'nama_tim',
      label: rf.sub_fields.team_name.label || 'Nama Tim / Skuad',
      placeholder: rf.sub_fields.team_name.placeholder || 'Contoh: Garuda Squad / Tim Voli Utama',
      required: rf.sub_fields.team_name.required !== false,
      help_text: 'Nama tim / regu resmi yang mewakili sekolah Anda'
    };
  }

  // 2. Cek apakah ada custom field standalone dengan nama tim
  if (currentCompetition.value && Array.isArray(currentCompetition.value.custom_fields)) {
    const found = currentCompetition.value.custom_fields.find(f => f && f.type !== 'participant_repeater' && isTeamFieldName(f.name, f.label));
    if (found) {
      return {
        name: found.name,
        label: found.label || 'Nama Tim / Skuad',
        placeholder: found.placeholder || 'Contoh: Garuda Squad / Tim Voli Utama',
        required: !!found.required,
        help_text: found.help_text || 'Nama tim / regu resmi yang mewakili sekolah Anda'
      };
    }
  }

  return null;
});

// Dynamic Custom Fields dari Form Builder (selain repeater, field identitas, dan field tim)
const dynamicFields = computed(() => {
  if (!currentCompetition.value || !Array.isArray(currentCompetition.value.custom_fields)) return [];
  const excluded = [
    'school_name', 
    'school_district', 
    'school_npsn', 
    'school_address', 
    'mentor_name', 
    'mentor_wa', 
    'mentor_email', 
    'allin_code', 
    'competition_id', 
    'participants_names', 
    'notes'
  ];
  return currentCompetition.value.custom_fields.filter(f => {
    if (!f || !f.name) return false;
    if (f.type === 'participant_repeater') return false;
    if (excluded.includes(f.name)) return false;
    if (teamFieldConfig.value && (f.name === teamFieldConfig.value.name || isTeamFieldName(f.name, f.label))) return false;
    return true;
  });
});

// Evaluator Conditional Logic Form Builder
function isFieldVisible(field) {
  if (!field.conditional_logic || !field.conditional_logic.enabled || !field.conditional_logic.depend_on_field) {
    return true;
  }
  return evaluateCondition(field.conditional_logic, form.custom_fields);
}

function evaluateCondition(logic, values) {
  if (!logic || !logic.enabled || !logic.depend_on_field) return true;
  const targetVal = values[logic.depend_on_field];
  const compareVal = logic.value;
  const op = logic.operator || '==';

  if (targetVal === undefined || targetVal === null || targetVal === '') return false;

  const numTarget = Number(targetVal);
  const numCompare = Number(compareVal);
  const isNumeric = !isNaN(numTarget) && !isNaN(numCompare) && compareVal !== '';

  switch (op) {
    case '==':
      return String(targetVal).trim().toLowerCase() === String(compareVal).trim().toLowerCase();
    case '!=':
      return String(targetVal).trim().toLowerCase() !== String(compareVal).trim().toLowerCase();
    case '>':
      return isNumeric ? numTarget > numCompare : String(targetVal) > String(compareVal);
    case '>=':
      return isNumeric ? numTarget >= numCompare : String(targetVal) >= String(compareVal);
    case '<':
      return isNumeric ? numTarget < numCompare : String(targetVal) < String(compareVal);
    case '<=':
      return isNumeric ? numTarget <= numCompare : String(targetVal) <= String(compareVal);
    default:
      return true;
  }
}

// Team composition parsing
function getTeamComposition(comp, rf) {
  if (!comp) return { isTeam: false, min: 1, max: 10, main: 1, reserve: 0, rule: '' };
  const isTeam = comp.participant_type === 'tim';
  const rule = comp.participants_rule || '';
  let max = rf?.max_items ? Number(rf.max_items) : (comp.max_participants ? Number(comp.max_participants) : (isTeam ? 10 : 1));
  let min = rf?.min_items ? Number(rf.min_items) : (isTeam ? Math.min(max, 3) : 1);
  let main = rf?.main_members ? Number(rf.main_members) : 0;
  let reserve = rf?.reserve_members ? Number(rf.reserve_members) : 0;

  if ((!main || !reserve) && rule) {
    const matchInti = rule.match(/(\d+)\s*(?:pemain|orang)?\s*inti/i);
    const matchCadangan = rule.match(/(\d+)\s*cadangan/i);
    if (matchInti) {
      main = parseInt(matchInti[1], 10);
      if (matchCadangan) {
        reserve = parseInt(matchCadangan[1], 10);
      }
      if (!rf?.min_items) min = main;
      if (!rf?.max_items) max = main + reserve;
    }
  }

  if (!main) main = isTeam ? max : 1;

  return { isTeam, min: Math.max(1, min), max: Math.max(min, max), main, reserve, rule };
}

const teamInfo = computed(() => getTeamComposition(currentCompetition.value, repeaterField.value));
const participantCount = ref(1);

function createEmptyParticipant() {
  return {
    name: '',
    gender: 'L',
    grade: 'Kelas 9 SMP/MTs',
    birth_date: '',
    nisn: '',
    custom_id: '',
    wa_number: '',
    is_captain: false,
    photo_formal: null,
    photo_formal_preview: null,
    photo_freestyle: [],
    photo_freestyle_previews: [],
    custom_columns: {},
  };
}

function handleParticipantPhotoFormal(pIdx, e) {
  const file = e.target?.files?.[0];
  if (!file || !participantsList.value[pIdx]) return;
  if (!file.type.startsWith('image/')) {
    toast.error('Format berkas harus berupa gambar (JPG, PNG, WEBP)');
    return;
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.error('Ukuran foto maksimal 5 MB');
    return;
  }
  if (participantsList.value[pIdx].photo_formal_preview) {
    URL.revokeObjectURL(participantsList.value[pIdx].photo_formal_preview);
  }
  participantsList.value[pIdx].photo_formal = file;
  participantsList.value[pIdx].photo_formal_preview = URL.createObjectURL(file);
  toast.success(`Pas foto peserta #${pIdx + 1} berhasil dipilih`);
}

function removeParticipantPhotoFormal(pIdx) {
  if (!participantsList.value[pIdx]) return;
  if (participantsList.value[pIdx].photo_formal_preview) {
    URL.revokeObjectURL(participantsList.value[pIdx].photo_formal_preview);
  }
  participantsList.value[pIdx].photo_formal = null;
  participantsList.value[pIdx].photo_formal_preview = null;
  const inputEl = document.getElementById(`reg_photo_formal_${pIdx + 1}`);
  if (inputEl) inputEl.value = '';
}

function handleParticipantPhotoFreestyle(pIdx, e) {
  const files = Array.from(e.target?.files || []);
  if (!files.length || !participantsList.value[pIdx]) return;

  const current = Array.isArray(participantsList.value[pIdx].photo_freestyle) ? [...participantsList.value[pIdx].photo_freestyle] : [];
  const currentPreviews = Array.isArray(participantsList.value[pIdx].photo_freestyle_previews) ? [...participantsList.value[pIdx].photo_freestyle_previews] : [];

  let added = 0;
  for (const file of files) {
    if (!file.type.startsWith('image/')) {
      toast.error(`Berkas "${file.name}" bukan gambar.`);
      continue;
    }
    if (file.size > 5 * 1024 * 1024) {
      toast.error(`Berkas "${file.name}" melebihi 5 MB.`);
      continue;
    }
    current.push(file);
    currentPreviews.push(URL.createObjectURL(file));
    added++;
  }

  participantsList.value[pIdx].photo_freestyle = current;
  participantsList.value[pIdx].photo_freestyle_previews = currentPreviews;
  if (added > 0) {
    toast.success(`${added} foto pose gaya bebas ditambahkan untuk peserta #${pIdx + 1}`);
  }
  const inputEl = document.getElementById(`reg_photo_freestyle_${pIdx + 1}`);
  if (inputEl) inputEl.value = '';
}

function removeParticipantPhotoFreestyleItem(pIdx, fIdx) {
  if (!participantsList.value[pIdx]) return;
  if (participantsList.value[pIdx].photo_freestyle_previews?.[fIdx]) {
    URL.revokeObjectURL(participantsList.value[pIdx].photo_freestyle_previews[fIdx]);
    participantsList.value[pIdx].photo_freestyle_previews.splice(fIdx, 1);
  }
  if (Array.isArray(participantsList.value[pIdx].photo_freestyle)) {
    participantsList.value[pIdx].photo_freestyle.splice(fIdx, 1);
  }
}

const participantsList = ref([createEmptyParticipant()]);

function setCaptain(idx) {
  participantsList.value.forEach((p, i) => {
    p.is_captain = (i === idx);
  });
}

function syncParticipantsCount(count) {
  const target = Math.max(1, count);
  participantCount.value = target;
  while (participantsList.value.length < target) {
    participantsList.value.push(createEmptyParticipant());
  }
  if (participantsList.value.length > target) {
    participantsList.value.splice(target);
  }
  if (repeaterField.value?.sub_fields?.captain_option?.active) {
    const hasCap = participantsList.value.some(p => p.is_captain);
    if (!hasCap && participantsList.value.length > 0) {
      participantsList.value[0].is_captain = true;
    }
  }
}

// Draft auto-save & restoration (Registrant Portal)
const hasRegDraftRestored = ref(false);
const lastSavedRegDraftAt = ref(null);
let regDraftDebounceTimer = null;

function getRegDraftStorageKey(compId) {
  return `semarak_reg_draft_${compId || form.competition_id || 'general'}`;
}

function saveRegDraft() {
  if (typeof window === 'undefined' || !form.competition_id) return;
  try {
    const key = getRegDraftStorageKey(form.competition_id);
    // Sanitize participantsList (remove File/Blob instances, keep textual fields)
    const sanitizedParticipants = participantsList.value.map(p => {
      const copy = { ...p };
      delete copy.photo_formal;
      delete copy.photo_formal_preview;
      delete copy.photo_freestyle;
      delete copy.photo_freestyle_previews;
      return copy;
    });

    const draftData = {
      savedAt: new Date().toISOString(),
      competition_id: form.competition_id,
      notes: form.notes || '',
      payment_method: form.payment_method || 'Transfer Bank BSI',
      allin_code: form.allin_code || '',
      custom_fields: { ...(form.custom_fields || {}) },
      participantCount: participantCount.value,
      participantsList: sanitizedParticipants,
    };

    localStorage.setItem(key, JSON.stringify(draftData));
    lastSavedRegDraftAt.value = new Date();
  } catch (err) {
    console.warn('[Semarak Reg Draft] Failed to auto-save:', err);
  }
}

function restoreRegDraft(compId) {
  if (typeof window === 'undefined') return false;
  const targetId = compId || form.competition_id;
  if (!targetId) return false;
  try {
    const key = getRegDraftStorageKey(targetId);
    const raw = localStorage.getItem(key);
    if (!raw) return false;
    const data = JSON.parse(raw);
    if (!data || !data.savedAt) return false;

    if (data.notes !== undefined && !form.notes) form.notes = data.notes;
    if (data.payment_method) form.payment_method = data.payment_method;
    if (data.allin_code !== undefined && !form.allin_code) form.allin_code = data.allin_code;
    if (data.custom_fields && typeof data.custom_fields === 'object') {
      form.custom_fields = { ...form.custom_fields, ...data.custom_fields };
    }
    if (Array.isArray(data.participantsList) && data.participantsList.length > 0) {
      participantCount.value = data.participantCount || data.participantsList.length;
      participantsList.value = data.participantsList.map(p => ({
        name: p.name || '',
        gender: p.gender || 'L',
        grade: p.grade || 'Kelas 9 SMP/MTs',
        birth_date: p.birth_date || '',
        nisn: p.nisn || '',
        custom_id: p.custom_id || '',
        wa_number: p.wa_number || '',
        is_captain: !!p.is_captain,
        photo_formal: null,
        photo_formal_preview: null,
        photo_freestyle: [],
        photo_freestyle_previews: [],
        custom_columns: p.custom_columns ? { ...p.custom_columns } : {},
      }));
    }
    hasRegDraftRestored.value = true;
    lastSavedRegDraftAt.value = new Date(data.savedAt);
    return true;
  } catch (err) {
    console.warn('[Semarak Reg Draft] Failed to restore draft:', err);
    return false;
  }
}

function clearRegDraft(compId) {
  if (typeof window === 'undefined') return;
  const targetId = compId || form.competition_id;
  try {
    localStorage.removeItem(getRegDraftStorageKey(targetId));
  } catch (e) {}
}

function resetRegDraftAndForm() {
  clearRegDraft(form.competition_id);
  hasRegDraftRestored.value = false;
  form.notes = '';
  form.custom_fields = {};
  const info = teamInfo.value;
  const count = info.isTeam ? Math.max(info.min, info.main || info.min) : 1;
  syncParticipantsCount(count);
  toast.info('Draf isian untuk cabang lomba ini telah dihapus.');
}

// Watch competition selection to initialize repeater, persist session last view & restore draft
watch(() => form.competition_id, (newId) => {
  if (typeof window !== 'undefined' && newId) {
    try {
      sessionStorage.setItem('semarak_reg_last_competition_id', String(newId));
      localStorage.setItem('semarak_reg_last_competition_id', String(newId));
      const comp = (props.competitions || []).find(c => String(c.id) === String(newId));
      const url = new URL(window.location.href);
      url.searchParams.set('lomba', comp?.slug || newId);
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  }

  if (repeaterField.value) {
    const info = teamInfo.value;
    const initialCount = info.isTeam ? Math.max(info.min, info.main || info.min) : 1;
    syncParticipantsCount(initialCount);
  }

  if (newId) {
    restoreRegDraft(newId);
  }
}, { immediate: true });

// Auto-save debounced watcher
watch([
  () => form.notes,
  () => form.payment_method,
  () => form.allin_code,
  () => form.custom_fields,
  () => participantCount.value,
  () => participantsList.value,
], () => {
  if (regDraftDebounceTimer) clearTimeout(regDraftDebounceTimer);
  regDraftDebounceTimer = setTimeout(() => {
    saveRegDraft();
  }, 600);
}, { deep: true });

// Checkbox multi-select helpers
function isCheckboxSelected(fieldName, opt) {
  const current = form.custom_fields[fieldName];
  return Array.isArray(current) && current.includes(opt);
}

function toggleCheckboxOption(fieldName, opt) {
  if (!Array.isArray(form.custom_fields[fieldName])) {
    form.custom_fields[fieldName] = [];
  }
  const idx = form.custom_fields[fieldName].indexOf(opt);
  if (idx > -1) {
    form.custom_fields[fieldName].splice(idx, 1);
  } else {
    form.custom_fields[fieldName].push(opt);
  }
}

// Custom file state for Form Builder file fields
const customFileState = ref({});

function handleCustomFile(fieldName, event, maxFiles = 1) {
  const selectedFiles = Array.from(event.target?.files || []);
  if (!selectedFiles.length) return;

  const limit = Math.max(1, Number(maxFiles || 1));
  if (limit > 1) {
    const existing = Array.isArray(customFileState.value[fieldName]) 
      ? [...customFileState.value[fieldName]] 
      : (customFileState.value[fieldName] ? [customFileState.value[fieldName]] : []);
    const combined = [...existing, ...selectedFiles];
    if (combined.length > limit) {
      toast.warning(`Maksimal hanya dapat mengunggah ${limit} berkas untuk kolom ini.`);
      customFileState.value[fieldName] = combined.slice(0, limit);
    } else {
      customFileState.value[fieldName] = combined;
    }
  } else {
    customFileState.value[fieldName] = selectedFiles[0];
  }

  if (!form.custom_files) form.custom_files = {};
  if (!form.custom_fields) form.custom_fields = {};
  form.custom_files[fieldName] = customFileState.value[fieldName];
  form.custom_fields[fieldName] = customFileState.value[fieldName];

  if (event.target) event.target.value = '';
}

function removeCustomFile(fieldName, index = null) {
  if (index !== null && Array.isArray(customFileState.value[fieldName])) {
    customFileState.value[fieldName].splice(index, 1);
    if (customFileState.value[fieldName].length === 0) {
      delete customFileState.value[fieldName];
      if (form.custom_files) delete form.custom_files[fieldName];
      if (form.custom_fields) delete form.custom_fields[fieldName];
    } else {
      if (form.custom_files) form.custom_files[fieldName] = customFileState.value[fieldName];
      if (form.custom_fields) form.custom_fields[fieldName] = customFileState.value[fieldName];
    }
  } else {
    delete customFileState.value[fieldName];
    if (form.custom_files) delete form.custom_files[fieldName];
    if (form.custom_fields) delete form.custom_fields[fieldName];
  }
}

function getRegistrantFileList(fieldName) {
  const val = customFileState.value[fieldName];
  if (!val) return [];
  if (Array.isArray(val)) return val;
  return [val];
}

// Calculated Total Fee
const calculatedFee = computed(() => {
  if (props.isAllInVerified || (isManualCodeVerified.value && manualCodeSuccessData.value)) {
    return 0; // Free because school verified All-In
  }
  if (selectedPackageType.value === 'Paket All-In') {
    return props.pricingSettings.allin_price || 200000;
  }
  if (currentCompetition.value) {
    return currentCompetition.value.fee || 50000;
  }
  return 50000;
});

// File selection handlers for standard documents & payment proof
const selectedDocumentFiles = ref([]);
const paymentProofFile = ref(null);

function handleDocumentsChange(e) {
  const files = Array.from(e.target.files || []);
  if (files.length === 0) return;

  const validFiles = [];
  for (const f of files) {
    if (f.size > 10 * 1024 * 1024) {
      toast.error(`File "${f.name}" melebihi batas 10MB.`);
      continue;
    }
    validFiles.push(f);
  }

  selectedDocumentFiles.value = [...selectedDocumentFiles.value, ...validFiles];
  form.file_documents = selectedDocumentFiles.value;
}

function removeDocumentFile(index) {
  selectedDocumentFiles.value.splice(index, 1);
  form.file_documents = selectedDocumentFiles.value;
}

function handleProofChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    toast.error('Ukuran bukti transfer melebihi batas 10MB.');
    return;
  }

  paymentProofFile.value = file;
  form.payment_proof = file;
}

function removeProofFile() {
  paymentProofFile.value = null;
  form.payment_proof = null;
}

// Upload bukti transfer untuk Formulir Paket Komplit
const allInProofFile = ref(null);
function handleAllInProofChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    toast.error('Ukuran bukti transfer melebihi batas 10MB.');
    return;
  }

  allInProofFile.value = file;
  allInForm.payment_proof = file;
}

function removeAllInProofFile() {
  allInProofFile.value = null;
  allInForm.payment_proof = null;
}

// Bank Accounts for Payment (Multi-Rekening)
const activeBankAccounts = computed(() => {
  const globalAccounts = page.props.paymentBankAccounts;
  if (Array.isArray(globalAccounts) && globalAccounts.length > 0) {
    return globalAccounts.filter(a => a.is_active !== false);
  }
  const propAccounts = props.pricingSettings?.bank_accounts;
  if (Array.isArray(propAccounts) && propAccounts.length > 0) {
    return propAccounts.filter(a => a.is_active !== false);
  }
  return [
    {
      id: 'default',
      bank_name: props.pricingSettings?.bank_name || 'Bank Syariah Indonesia (BSI)',
      account_number: props.pricingSettings?.account_number || '7123456789',
      account_holder: props.pricingSettings?.account_holder || 'Semarak Aliyah',
      is_primary: true,
      notes: 'Rekening Resmi Panitia'
    }
  ];
});

// Copy Bank Account
function copyAccountNumber() {
  const primary = activeBankAccounts.value.find(a => a.is_primary) || activeBankAccounts.value[0];
  const num = primary?.account_number || props.pricingSettings.account_number || '7123456789';
  if (navigator.clipboard) {
    navigator.clipboard.writeText(num);
    toast.success('Nomor rekening berhasil disalin!');
  }
}

function copySpecificAccountNumber(acc) {
  const num = acc.account_number || '7123456789';
  if (navigator.clipboard) {
    navigator.clipboard.writeText(num);
    toast.success(`Nomor rekening ${acc.bank_name} (${num}) berhasil disalin!`);
  }
}

function getDirectRegistrationWaUrl(comp) {
  if (!comp) return '#';
  const phone = comp.primary_coordinator?.contact || comp.primary_coordinator?.phone || comp.contact_person || '083850070767';
  const cleanPhone = String(phone).replace(/\D/g, '');
  const targetPhone = cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : (cleanPhone.startsWith('62') ? cleanPhone : '62' + cleanPhone);

  let tpl = comp.direct_registration_template || '';
  if (!tpl.trim()) {
    if (!props.user?.school_name && comp.direct_contact_wa_url) {
      return comp.direct_contact_wa_url;
    }
    tpl = "Assalamu'alaikum Wr. Wb. Kak {nama_pj},\\n\\nSaya perwakilan dari {nama_sekolah}, bermaksud mendaftarkan delegasi sekolah kami untuk cabang lomba *{cabang_lomba}* pada agenda *{nama_event}*.\\n\\n• Asal Lembaga: {nama_sekolah}\\n• Cabang Lomba: {cabang_lomba}\\n• Biaya Pendaftaran: {biaya}\\n• Narahubung/Pembina: {nama_pendaftar} ({nomor_wa})\\n\\nMohon petunjuk teknis pendaftaran langsung, persyaratan berkas, serta ketersediaan kuota delegasi. Terima kasih.\\nWassalamu'alaikum Wr. Wb.";
  }

  const coordName = comp.primary_coordinator?.name || ('Koordinator ' + (comp.title || 'Lomba'));
  const feeFormatted = comp.fee && comp.fee > 0 ? ('Rp ' + Number(comp.fee).toLocaleString('id-ID')) : 'Gratis';
  const eventName = 'Semarak Aliyah XIV 2026';

  const replacements = {
    '{nama_pj}': coordName,
    '{nama_koordinator}': coordName,
    '{cabang_lomba}': comp.title || 'Lomba',
    '{nama_event}': eventName,
    '{nama_sekolah}': props.user?.school_name || '[Nama Sekolah]',
    '{nama_pendaftar}': props.user?.name || '[Nama Pendaftar]',
    '{nomor_wa}': props.user?.phone || '[Nomor WA]',
    '{biaya}': feeFormatted,
    '{kontak_pj}': phone,
  };

  let rendered = tpl;
  for (const [k, v] of Object.entries(replacements)) {
    rendered = rendered.split(k).join(v);
  }

  return `https://wa.me/${targetPhone}?text=${encodeURIComponent(rendered)}`;
}

// Form Submit: PAKET SATUAN (Per-Cabang Lomba Mengacu Form Builder)
function submitRegistration() {
  if (!form.competition_id) {
    toast.error('Silakan pilih cabang lomba yang ingin diikuti.');
    return;
  }

  if (currentCompetition.value && (currentCompetition.value.is_direct_registration || currentCompetition.value.registration_type === 'direct_contact')) {
    toast.info('Pendaftaran cabang lomba ini dilakukan melalui kontak langsung ke Koordinator Lomba.');
    const waUrl = getDirectRegistrationWaUrl(currentCompetition.value);
    if (waUrl && waUrl !== '#') {
      window.open(waUrl, '_blank');
    }
    return;
  }

  // Validate team name if active and required
  if (teamFieldConfig.value?.required) {
    const teamVal = form.custom_fields?.[teamFieldConfig.value.name];
    if (!teamVal || !String(teamVal).trim()) {
      toast.error(`${teamFieldConfig.value.label} wajib diisi.`);
      return;
    }
  }

  if (teamFieldConfig.value) {
    const tVal = form.custom_fields?.[teamFieldConfig.value.name] || '';
    if (!form.custom_fields) form.custom_fields = {};
    form.custom_fields[teamFieldConfig.value.name] = tVal;
    form.custom_fields['nama_tim'] = tVal;
    form.custom_fields['team_name'] = tVal;
  }

  // Compile participants names from repeater if present
  if (repeaterField.value) {
    const list = participantsList.value.slice(0, participantCount.value);
    const subFields = repeaterField.value?.sub_fields || {};

    // Validate participant photos if required
    if (subFields.photo_formal?.active && subFields.photo_formal?.required) {
      for (let i = 0; i < list.length; i++) {
        if (!list[i].photo_formal) {
          toast.error(`Pas foto formal peserta #${i + 1} (${list[i].name || 'Tanpa Nama'}) wajib diunggah.`);
          return;
        }
      }
    }
    if (subFields.photo_freestyle?.active && subFields.photo_freestyle?.required) {
      for (let i = 0; i < list.length; i++) {
        if (!list[i].photo_freestyle || (Array.isArray(list[i].photo_freestyle) && list[i].photo_freestyle.length === 0)) {
          toast.error(`Foto gaya bebas peserta #${i + 1} (${list[i].name || 'Tanpa Nama'}) wajib diunggah minimal 1 foto.`);
          return;
        }
      }
    }

    const lines = [];
    for (let i = 0; i < list.length; i++) {
      const p = list[i];
      const pName = (p.name || '').trim() || `Peserta ${i + 1}`;
      const details = [];
      if (p.is_captain) details.push('★ KAPTEN TIM');
      if (p.gender) details.push(p.gender === 'L' ? 'Laki-laki' : 'Perempuan');
      if (p.grade) details.push(p.grade);
      if (p.birth_date) details.push(formatIndoDate(p.birth_date));
      if (p.nisn) details.push(`NISN: ${p.nisn}`);
      if (p.custom_id) details.push(`ID: ${p.custom_id}`);
      if (p.wa_number) details.push(`WA: ${p.wa_number}`);
      if (p.custom_columns) {
        Object.entries(p.custom_columns).forEach(([k, v]) => {
          if (v) details.push(`${k}: ${v}`);
        });
      }
      lines.push(`${i + 1}. ${pName}${details.length ? ' (' + details.join(', ') + ')' : ''}`);
    }
    form.participants_names = lines.join('\n');
    if (!form.custom_fields) form.custom_fields = {};

    // Clean preview URLs before passing to form
    const cleanList = list.map(p => {
      const cp = { ...p };
      delete cp.photo_formal_preview;
      delete cp.photo_freestyle_previews;
      return cp;
    });
    form.custom_fields[repeaterField.value.name] = cleanList;
    form.custom_fields[repeaterField.value.name + '_count'] = participantCount.value;
  }

  if (!form.participants_names || !form.participants_names.trim()) {
    toast.error('Daftar nama peserta / delegasi wajib diisi.');
    return;
  }

  // Validate dynamic custom fields
  for (const f of dynamicFields.value) {
    if (f.required && isFieldVisible(f)) {
      if (f.type === 'file') {
        const files = getRegistrantFileList(f.name);
        if (!files.length && !form.custom_files?.[f.name] && !form.custom_fields?.[f.name]) {
          toast.error(`Berkas "${f.label}" wajib diunggah.`);
          return;
        }
      } else if (f.type === 'checkbox') {
        const val = form.custom_fields?.[f.name];
        if (!val || (Array.isArray(val) && val.length === 0)) {
          toast.error(`Pilihan "${f.label}" minimal pilih 1 opsi.`);
          return;
        }
      } else {
        const val = form.custom_fields?.[f.name];
        if (val === undefined || val === null || String(val).trim() === '') {
          toast.error(`Kolom "${f.label}" wajib diisi.`);
          return;
        }
      }
    }
  }

  if (isManualCodeVerified.value || props.isAllInVerified) {
    form.package_type = 'Paket All-In';
  } else {
    form.package_type = 'Paket Satuan';
  }

  form.post('/pendaftar/daftar-lomba', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      clearRegDraft(form.competition_id);
      hasRegDraftRestored.value = false;
      toast.success('Pendaftaran delegasi lomba berhasil disimpan!');
    },
    onError: (errs) => {
      const first = Object.values(errs)[0];
      toast.error(first || 'Harap periksa kembali isian formulir pendaftaran.');
    }
  });
}

// Form Submit: PAKET KOMPLIT (Hanya 7 Informasi Sekolah & Koordinator)
function submitPaketKomplit() {
  if (!allInForm.school_name || !allInForm.school_name.trim()) {
    toast.error('Nama sekolah / madrasah wajib diisi.');
    return;
  }
  if (!allInForm.school_district || !allInForm.school_district.trim()) {
    toast.error('Kecamatan sekolah / madrasah wajib dipilih.');
    return;
  }
  if (!allInForm.school_address || !allInForm.school_address.trim()) {
    toast.error('Alamat lengkap sekolah / madrasah wajib diisi.');
    return;
  }
  if (!allInForm.coordinator_name || !allInForm.coordinator_name.trim()) {
    toast.error('Nama koordinator / pendamping wajib diisi.');
    return;
  }
  if (!allInForm.coordinator_phone || !allInForm.coordinator_phone.trim()) {
    toast.error('Nomor WhatsApp / kontak koordinator wajib diisi.');
    return;
  }
  if (!allInForm.coordinator_email || !allInForm.coordinator_email.trim()) {
    toast.error('Email koordinator / pendamping wajib diisi.');
    return;
  }

  allInForm.post('/pendaftar/daftar-lomba', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      toast.success('Pendaftaran Paket Komplit berhasil! Kode paket otomatis diterbitkan.');
    },
    onError: (errs) => {
      const first = Object.values(errs)[0];
      toast.error(first || 'Harap periksa kembali isian formulir Paket Komplit.');
    }
  });
}

// Unified Submit Handler
function handleUnifiedSubmit() {
  if (selectedPackageType.value === 'Paket All-In') {
    submitPaketKomplit();
  } else {
    submitRegistration();
  }
}
</script>

<template>
  <Head title="Pendaftaran Cabang Lomba Baru - Akun Pendaftar" />

  <div class="reg-page-wrapper min-h-screen d-flex flex-column justify-content-between" :class="{ 'dark-theme': isDark }">
    
    <!-- Topbar Navigation (Konsisten dengan Dashboard Pendaftar) -->
    <header class="registrant-topbar sticky-top shadow-sm w-100">
      <div class="container-fluid px-3 px-md-4 py-2.5 d-flex align-items-center justify-content-between mx-auto" style="max-width: 1140px;">
        
        <!-- Brand & Back Button -->
        <div class="d-flex align-items-center gap-3">
          <Link
            href="/pendaftar/dashboard"
            class="btn btn-sm reg-back-btn rounded-pill d-flex align-items-center gap-1.5 px-3 py-1.5 fs-12 fw-semibold"
          >
            <i class="ri-arrow-left-line"></i>
            <span>Kembali ke Dashboard</span>
          </Link>

          <Link href="/" class="d-none d-md-flex align-items-center gap-2 text-decoration-none">
            <img src="/assets/img/logo.png" alt="Logo" class="rounded-circle shadow-sm" style="width: 32px; height: 32px; object-fit: contain;" />
            <div class="text-start">
              <div class="fw-black fs-12 tracking-wide text-uppercase topbar-brand" style="letter-spacing: 0.5px;">SEMARAK ALIYAH XIV</div>
              <div class="fs-10 topbar-subbrand fw-semibold">Pendaftaran Cabang Lomba</div>
            </div>
          </Link>
        </div>

        <!-- Right User Actions -->
        <div class="d-flex align-items-center gap-2">
          <!-- Area Akun Pendaftar Badge (Rule 3) -->
          <span class="badge badge-portal-resmi rounded-pill px-2.5 py-1 fs-11 fw-bold d-none d-sm-inline-flex align-items-center gap-1">
            <i class="ri-shield-user-line"></i>
            <span>Area Akun Pendaftar</span>
          </span>

          <!-- Theme Switcher Button (Rule 2 & 3: Mandatory Light / Dark Toggle) -->
          <button
            type="button"
            class="btn btn-sm dashboard-theme-toggle rounded-pill d-flex align-items-center gap-1.5 px-2.5 py-1.5 fs-11.5 fw-bold"
            @click="toggleTheme"
            :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
          >
            <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line text-primary'"></i>
            <span class="d-none d-sm-inline">{{ isDark ? 'Mode Terang' : 'Mode Gelap' }}</span>
          </button>

          <!-- User Avatar Pill -->
          <div class="d-flex align-items-center gap-2 ps-2 border-start topbar-divider">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-11" style="width: 26px; height: 26px;">
              {{ user.name ? user.name.charAt(0).toUpperCase() : 'P' }}
            </div>
            <span class="fs-12 fw-bold d-none d-lg-inline text-truncate reg-title" style="max-width: 140px;">
              {{ user.name }}
            </span>
          </div>
        </div>

      </div>
    </header>

    <!-- Main Container -->
    <main class="w-100 mx-auto px-3 px-md-4 py-4" style="max-width: 1140px;">
      
      <!-- Page Header Banner -->
      <div class="text-center mb-4">
        <h1 class="fs-22 fw-bold reg-title mb-1.5">
          {{ (selectedPackageType === 'Paket Satuan' && currentCompetition?.form_settings?.form_title) ? currentCompetition.form_settings.form_title : 'Pendaftaran Cabang Lomba Delegasi' }}
        </h1>
        <p class="fs-13 reg-desc mb-0">
          {{ (selectedPackageType === 'Paket Satuan' && currentCompetition?.form_settings?.form_subtitle) ? currentCompetition.form_settings.form_subtitle : 'Daftarkan delegasi kontingen madrasah/sekolah Anda. Formulir menyesuaikan persyaratan khusus dari masing-masing cabang lomba.' }}
        </p>
      </div>

      <!-- 1. IDENTITAS PENDAFTAR (Locked / Auto-filled from Account) -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card mb-4">
        <div class="card-header border-bottom reg-card-header p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-16 flex-shrink-0" style="width: 36px; height: 36px;">
              <i class="ri-user-star-line"></i>
            </div>
            <div>
              <h6 class="fs-14 fw-bold reg-title mb-0">Identitas Akun Pendaftar (Otomatis Terhubung)</h6>
              <div class="fs-11 reg-desc">Data diambil langsung dari akun pendaftar resmi lembaga Anda.</div>
            </div>
          </div>
          <Link href="/pendaftar/dashboard" class="btn btn-sm btn-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fs-11 fw-semibold">
            <i class="ri-edit-line me-1"></i> Edit Profil di Dashboard
          </Link>
        </div>

        <div class="card-body p-3.5">
          <div class="row g-3">
            <div class="col-md-4">
              <small class="fs-11 text-uppercase fw-semibold reg-desc d-block">Nama Lengkap Pendamping</small>
              <div class="fs-13 fw-bold reg-title mt-0.5 d-flex align-items-center gap-2">
                <span>{{ user.name }}</span>
                <span class="badge badge-subtle-primary fs-10 px-2 py-0.5 rounded-pill">
                  {{ user.registrant_role || 'Pendamping' }}
                </span>
              </div>
            </div>

            <div class="col-md-5">
              <small class="fs-11 text-uppercase fw-semibold reg-desc d-block">Asal Sekolah & Kecamatan</small>
              <div class="fs-13 fw-semibold reg-title mt-0.5">
                <i class="ri-building-line text-primary me-1"></i>
                <strong>{{ user.school_name }}</strong>
                <span v-if="user.school_district && user.school_district !== '-'" class="reg-desc ms-1">
                  (Kec. {{ user.school_district }})
                </span>
              </div>
            </div>

            <div class="col-md-3">
              <small class="fs-11 text-uppercase fw-semibold reg-desc d-block">Nomor WhatsApp Aktif</small>
              <div class="fs-13 fw-semibold text-success mt-0.5 d-flex align-items-center gap-1">
                <i class="ri-whatsapp-line fs-14"></i>
                <span>{{ user.phone }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. FORMULIR PENDAFTARAN LOMBA -->
      <form @submit.prevent="handleUnifiedSubmit">
        <div class="row g-4">
          
          <!-- Left Column: Lomba, Form Builder Fields / Paket Komplit Fields (Col-lg-7) -->
          <div class="col-lg-7 d-flex flex-column gap-4">
            
            <!-- Card Pilihan Paket -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card">
              <div class="card-header border-bottom reg-card-header p-3.5">
                <h6 class="fs-14 fw-bold reg-title mb-0 d-flex align-items-center gap-2">
                  <i class="ri-trophy-line text-warning fs-16"></i>
                  <span>Pilihan Jenis Paket Pendaftaran</span>
                </h6>
              </div>

              <div class="card-body p-4 d-flex flex-column gap-3.5">
                
                <!-- Notice jika sekolah terverifikasi All-In -->
                <div v-if="isAllInVerified" class="p-3 rounded-3 reg-allin-badge d-flex gap-2.5 align-items-center">
                  <i class="ri-award-fill fs-24 text-warning flex-shrink-0"></i>
                  <div class="fs-12">
                    <strong class="d-block mb-0.5 text-warning">Sekolah Anda Terverifikasi Paket All-In Komplit!</strong>
                    <span>Anda bebas mendaftarkan delegasi ke cabang lomba manapun dengan <strong>biaya Rp 0 (Gratis)</strong>.</span>
                  </div>
                </div>

                <!-- Paket Type Selector -->
                <div v-if="!isAllInVerified">
                  <label class="form-label fs-13 fw-semibold reg-label mb-1.5">
                    Jenis Paket Pendaftaran <span class="text-danger">*</span>
                  </label>
                  <div class="row g-2">
                    <div class="col-6">
                      <div
                        class="p-3 rounded-3 border cursor-pointer transition-all reg-package-box text-center"
                        :class="selectedPackageType === 'Paket Satuan' ? 'is-selected' : ''"
                        @click="selectedPackageType = 'Paket Satuan'"
                      >
                        <i class="ri-file-list-3-line fs-22 text-primary mb-1 d-block"></i>
                        <div class="fs-13 fw-bold reg-title">Paket Satuan</div>
                        <div class="fs-11 reg-desc">Daftar per cabang • Sesuai Form Builder</div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div
                        class="p-3 rounded-3 border cursor-pointer transition-all reg-package-box text-center"
                        :class="selectedPackageType === 'Paket All-In' ? 'is-selected-gold' : ''"
                        @click="selectedPackageType = 'Paket All-In'"
                      >
                        <i class="ri-vip-crown-line fs-22 text-warning mb-1 d-block"></i>
                        <div class="fs-13 fw-bold reg-title">Paket Komplit</div>
                        <div class="fs-11 text-warning fw-semibold">Bebas Semua Cabang (Diskon Rp 200rb)</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- JIKA PAKET SATUAN: Pilih Cabang Lomba (Rule 1: CustomSelect) -->
                <div v-if="selectedPackageType === 'Paket Satuan'">
                  <label class="form-label fs-13 fw-semibold reg-label mb-1">
                    Pilih Cabang Lomba <span class="text-danger">*</span>
                  </label>
                  <CustomSelect
                    v-model="form.competition_id"
                    :options="competitionOptions"
                    placeholder="Pilih cabang perlombaan..."
                    prefixIcon="ri-medal-line"
                    size="md"
                    searchable
                    searchPlaceholder="Cari nama cabang lomba..."
                  />
                  <div v-if="form.errors.competition_id" class="text-danger fs-12 mt-1 fw-medium">
                    {{ form.errors.competition_id }}
                  </div>

                  <!-- Info Lomba Terpilih (Rule 4: Batas Tanggal dd/mm/yyyy & Jam 24 Jam) -->
                  <div v-if="currentCompetition" class="mt-3 p-3 rounded-3 reg-comp-info d-flex flex-column gap-1.5 fs-12">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                      <span class="fs-13 fw-bold reg-title">{{ currentCompetition.title }}</span>
                      <span class="badge badge-subtle-success fs-11">
                        Biaya: {{ formatRupiah(calculatedFee) }}
                      </span>
                    </div>
                    <div v-if="currentCompetition.registration_deadline" class="reg-desc">
                      <i class="ri-calendar-event-line text-warning me-1"></i>
                      Batas Pendaftaran: <strong class="reg-title">{{ formatIndoDate(currentCompetition.registration_deadline) }}</strong>
                    </div>
                    <div v-if="currentCompetition.description" class="reg-desc mt-1">
                      {{ currentCompetition.description }}
                    </div>
                  </div>

                  <!-- Alert Khusus Pendaftaran Langsung via Koordinator -->
                  <div 
                    v-if="currentCompetition && (currentCompetition.is_direct_registration || currentCompetition.registration_type === 'direct_contact')"
                    class="mt-3 p-3.5 rounded-3 border border-success-subtle bg-success-subtle"
                  >
                    <div class="d-flex align-items-start gap-2.5">
                      <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fs-16 flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="ri-whatsapp-line"></i>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="fs-13 fw-bold text-success mb-1">
                          Pendaftaran Khusus: Kontak Langsung ke Koordinator Lomba
                        </h6>
                        <p class="fs-12 text-dark mb-2" style="line-height: 1.5;">
                          {{ currentCompetition.direct_registration_note || 'Pendaftaran cabang lomba ini tidak menggunakan formulir pendaftaran mandiri di sistem web. Silakan hubungi Koordinator Lomba langsung via WhatsApp di bawah ini untuk pendaftaran delegasi dan koordinasi kuota.' }}
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                          <a 
                            v-if="currentCompetition.direct_contact_wa_url || currentCompetition.primary_coordinator?.contact"
                            :href="getDirectRegistrationWaUrl(currentCompetition)"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 fw-semibold shadow-xs"
                          >
                            <i class="ri-whatsapp-line fs-14"></i>
                            <span>Chat WhatsApp Koordinator ({{ currentCompetition.primary_coordinator?.name || 'Koordinator Lomba' }})</span>
                          </a>
                          <span v-if="currentCompetition.primary_coordinator?.contact" class="badge bg-white text-dark border d-inline-flex align-items-center px-2 py-1 fs-11">
                            <i class="ri-phone-line text-success me-1"></i> {{ currentCompetition.primary_coordinator.contact }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- KODE PAKET KOMPLIT (ALL-IN) SECTION (Khusus Paket Satuan) -->
                <div v-if="selectedPackageType === 'Paket Satuan' && !isAllInVerified" class="p-3.5 rounded-3 border reg-package-box">
                  <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-14 flex-shrink-0" style="width: 32px; height: 32px;">
                        <i class="ri-vip-crown-line"></i>
                      </div>
                      <div>
                        <div class="fs-13 fw-bold reg-title">Kode Paket Komplit (All-In)</div>
                        <div class="fs-11 reg-desc">Gunakan jika sekolah sudah memiliki Kode Paket Komplit agar cabang ini Bebas Biaya (Rp 0).</div>
                      </div>
                    </div>
                    <span class="badge badge-subtle-warning fs-10 px-2 py-0.5 rounded-pill">
                      Opsional
                    </span>
                  </div>

                  <div class="input-group">
                    <span class="input-group-text reg-input-addon">
                      <i class="ri-qr-code-line"></i>
                    </span>
                    <input
                      type="text"
                      v-model="form.allin_code"
                      class="form-control reg-input font-monospace fw-bold text-uppercase fs-12"
                      placeholder="Contoh: SA26-KMP-ABC123"
                      @input="onAllInCodeInput"
                    />
                    <button
                      type="button"
                      class="btn btn-primary px-3 fs-12 fw-semibold d-flex align-items-center gap-1.5"
                      :disabled="isCheckingPackageCode || !form.allin_code"
                      @click="verifyPackageCodeManual"
                    >
                      <i v-if="isCheckingPackageCode" class="ri-loader-4-line ri-spin"></i>
                      <i v-else class="ri-shield-check-line"></i>
                      <span>{{ isCheckingPackageCode ? 'Mengecek...' : 'Cek Kode' }}</span>
                    </button>
                    <button
                      v-if="isManualCodeVerified || form.allin_code"
                      type="button"
                      class="btn btn-sm btn-danger-subtle text-danger border border-danger-subtle px-2.5 fs-14"
                      @click="clearManualPackageCode"
                      title="Hapus kode"
                    >
                      <i class="ri-close-line"></i>
                    </button>
                  </div>

                  <!-- Verified Alert (Rule 2 & 3: High Contrast in Light/Dark) -->
                  <div v-if="isManualCodeVerified && manualCodeSuccessData" class="mt-2.5 p-2.5 rounded-2 alert-verified-box fs-12">
                    <div class="d-flex align-items-center gap-1.5 fw-bold mb-1 text-success">
                      <i class="ri-checkbox-circle-fill fs-15"></i>
                      <span>Terverifikasi Paket Komplit! (Bebas Biaya Rp 0)</span>
                    </div>
                    <div class="reg-desc fs-11">
                      Sekolah: <strong class="reg-title">{{ manualCodeSuccessData.school_name }}</strong> • Kode: <code class="fw-bold px-1.5 py-0.5 rounded verified-code-badge">{{ manualCodeSuccessData.reg_code }}</code>
                    </div>
                  </div>

                  <!-- Error Alert -->
                  <div v-else-if="codeVerificationError" class="mt-2.5 p-2.5 rounded-2 alert-danger-box fs-12 d-flex align-items-center gap-1.5">
                    <i class="ri-error-warning-fill fs-15 text-danger flex-shrink-0"></i>
                    <span class="text-danger fw-semibold">{{ codeVerificationError }}</span>
                  </div>
                </div>

              </div>
            </div>

            <!-- ========================================================================= -->
            <!-- JIKA PAKET KOMPLIT (ALL-IN): HANYA 7 INFORMASI SEKOLAH & KOORDINATOR -->
            <!-- ========================================================================= -->
            <div v-if="selectedPackageType === 'Paket All-In'" class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card">
              <div class="card-header border-bottom reg-card-header p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2.5">
                  <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-16 flex-shrink-0" style="width: 36px; height: 36px;">
                    <i class="ri-vip-crown-fill"></i>
                  </div>
                  <div>
                    <h6 class="fs-14 fw-bold reg-title mb-0">Formulir Informasi Pendaftaran Paket Komplit</h6>
                    <div class="fs-11 reg-desc">Cukup isi 7 data identitas lembaga & kontak koordinator berikut. Kode paket komplit akan terbuat secara otomatis.</div>
                  </div>
                </div>
                <span class="badge badge-subtle-warning fs-11 px-2.5 py-1 rounded-pill">
                  Bebas Semua Cabang Lomba
                </span>
              </div>

              <div class="card-body p-4 d-flex flex-column gap-3.5">
                
                <!-- 1. Nama Sekolah / Madrasah -->
                <div>
                  <label class="form-label fs-13 fw-semibold reg-label mb-1">
                    1. Nama Sekolah / Madrasah <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text reg-input-addon"><i class="ri-building-line"></i></span>
                    <input
                      type="text"
                      v-model="allInForm.school_name"
                      class="form-control reg-input fs-13 py-2"
                      placeholder="Contoh: MTs Unggulan Wahid Hasyim"
                      required
                    />
                  </div>
                  <div v-if="allInForm.errors.school_name" class="text-danger fs-12 mt-1">{{ allInForm.errors.school_name }}</div>
                </div>

                <!-- 2. Kecamatan Sekolah / Madrasah (Rule 1: CustomSelect) -->
                <div>
                  <label class="form-label fs-13 fw-semibold reg-label mb-1">
                    2. Kecamatan Sekolah / Madrasah <span class="text-danger">*</span>
                  </label>
                  <CustomSelect
                    v-model="allInForm.school_district"
                    :options="kecamatanOptions"
                    placeholder="Pilih kecamatan di Kabupaten Jember..."
                    prefixIcon="ri-map-pin-2-line"
                    size="md"
                    searchable
                    searchPlaceholder="Cari nama kecamatan..."
                  />
                  <div v-if="allInForm.errors.school_district" class="text-danger fs-12 mt-1">{{ allInForm.errors.school_district }}</div>
                </div>

                <!-- 3. NPSN (Opsional) -->
                <div>
                  <label class="form-label fs-13 fw-semibold reg-label mb-1 d-flex justify-content-between align-items-center">
                    <span>3. NPSN (Nomor Pokok Sekolah Nasional)</span>
                    <span class="badge badge-subtle-secondary fs-10 rounded-pill">Opsional</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text reg-input-addon"><i class="ri-hashtag"></i></span>
                    <input
                      type="text"
                      v-model="allInForm.npsn"
                      class="form-control reg-input fs-13 py-2"
                      placeholder="Contoh: 20581001 (8 Digit, Opsional)"
                    />
                  </div>
                  <div v-if="allInForm.errors.npsn" class="text-danger fs-12 mt-1">{{ allInForm.errors.npsn }}</div>
                </div>

                <!-- 4. Alamat Lengkap Sekolah / Madrasah -->
                <div>
                  <label class="form-label fs-13 fw-semibold reg-label mb-1">
                    4. Alamat Lengkap Sekolah / Madrasah <span class="text-danger">*</span>
                  </label>
                  <textarea
                    v-model="allInForm.school_address"
                    rows="2"
                    class="form-control reg-input fs-13 py-2"
                    placeholder="Contoh: Jl. Yos Sudarso No. 1, Balung Lor, Kec. Balung, Kabupaten Jember"
                    required
                  ></textarea>
                  <div v-if="allInForm.errors.school_address" class="text-danger fs-12 mt-1">{{ allInForm.errors.school_address }}</div>
                </div>

                <!-- Divider Koordinator / Pendamping -->
                <div class="border-top pt-3 mt-1">
                  <div class="fs-12 fw-bold text-uppercase reg-desc tracking-wider mb-2.5 d-flex align-items-center gap-1.5">
                    <i class="ri-user-star-line text-warning"></i>
                    <span>Kontak Koordinator / Pendamping Delegasi</span>
                  </div>

                  <div class="d-flex flex-column gap-3">
                    <!-- 5. Nama Koordinator / Pendamping -->
                    <div>
                      <label class="form-label fs-13 fw-semibold reg-label mb-1">
                        5. Nama Koordinator / Pendamping <span class="text-danger">*</span>
                      </label>
                      <div class="input-group">
                        <span class="input-group-text reg-input-addon"><i class="ri-user-line"></i></span>
                        <input
                          type="text"
                          v-model="allInForm.coordinator_name"
                          class="form-control reg-input fs-13 py-2"
                          placeholder="Nama lengkap koordinator pendamping"
                          required
                        />
                      </div>
                      <div v-if="allInForm.errors.coordinator_name" class="text-danger fs-12 mt-1">{{ allInForm.errors.coordinator_name }}</div>
                    </div>

                    <!-- 6. Nomor WA/Kontak Koordinator -->
                    <div>
                      <label class="form-label fs-13 fw-semibold reg-label mb-1">
                        6. Nomor WhatsApp / Kontak Koordinator <span class="text-danger">*</span>
                      </label>
                      <div class="input-group">
                        <span class="input-group-text reg-input-addon text-success"><i class="ri-whatsapp-line"></i></span>
                        <input
                          type="text"
                          v-model="allInForm.coordinator_phone"
                          class="form-control reg-input fs-13 py-2 font-monospace"
                          placeholder="Contoh: 081234567890"
                          required
                        />
                      </div>
                      <div v-if="allInForm.errors.coordinator_phone" class="text-danger fs-12 mt-1">{{ allInForm.errors.coordinator_phone }}</div>
                    </div>

                    <!-- 7. Email Koordinator / Pendamping -->
                    <div>
                      <label class="form-label fs-13 fw-semibold reg-label mb-1">
                        7. Email Koordinator / Pendamping <span class="text-danger">*</span>
                      </label>
                      <div class="input-group">
                        <span class="input-group-text reg-input-addon"><i class="ri-mail-line"></i></span>
                        <input
                          type="email"
                          v-model="allInForm.coordinator_email"
                          class="form-control reg-input fs-13 py-2"
                          placeholder="Contoh: koordinator@sekolah.sch.id"
                          required
                        />
                      </div>
                      <div v-if="allInForm.errors.coordinator_email" class="text-danger fs-12 mt-1">{{ allInForm.errors.coordinator_email }}</div>
                    </div>

                    <!-- Catatan Tambahan (Opsional) -->
                    <div>
                      <label class="form-label fs-13 fw-semibold reg-label mb-1">Catatan Khusus Tambahan (Opsional)</label>
                      <input
                        type="text"
                        v-model="allInForm.notes"
                        class="form-control reg-input fs-13 py-2"
                        placeholder="Catatan untuk panitia (opsional)"
                      />
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- ========================================================================= -->
            <!-- JIKA PAKET SATUAN: FORM BUILDER DYNAMIC FIELDS, DATA PESERTA, BERKAS UMUM -->
            <!-- ========================================================================= -->
            <template v-if="selectedPackageType === 'Paket Satuan'">
              <!-- Draf Restoration Notification Banner (Rule 2 & 3: High Contrast in Dark/Light) -->
              <div 
                v-if="hasRegDraftRestored" 
                class="alert alert-info border-info-subtle d-flex align-items-center justify-content-between flex-wrap gap-2 py-2.5 px-3.5 rounded-4 shadow-sm mb-3.5"
                style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.25);"
              >
                <div class="d-flex align-items-center gap-2.5">
                  <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fs-16 flex-shrink-0" style="width: 32px; height: 32px;">
                    <i class="ri-history-line"></i>
                  </div>
                  <div>
                    <div class="fs-12 fw-bold text-dark" :class="{ 'text-white': isDark }">Draf Isian Formulir Terpulihkan!</div>
                    <div class="text-muted fs-11">
                      Data yang Anda ketik sebelumnya pada cabang ini berhasil dipulihkan secara otomatis.
                    </div>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-1.5 ms-auto">
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-info d-inline-flex align-items-center gap-1 fs-11 px-2.5 py-1 rounded-2"
                    @click="hasRegDraftRestored = false"
                    title="Tutup pemberitahuan"
                  >
                    <i class="ri-check-line"></i> Lanjutkan
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-danger d-inline-flex align-items-center gap-1 fs-11 px-2.5 py-1 rounded-2"
                    @click="resetRegDraftAndForm"
                    title="Hapus draf dan kosongkan isian form"
                  >
                    <i class="ri-delete-bin-line"></i> Reset Draf
                  </button>
                </div>
              </div>

              <!-- Card 2: Form Builder Dynamic Fields (Jika Cabang Lomba Memiliki Custom Fields) -->
              <div v-if="dynamicFields.length > 0" class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card">
                <div class="card-header border-bottom reg-card-header p-3.5">
                  <h6 class="fs-14 fw-bold reg-title mb-0 d-flex align-items-center gap-2">
                    <i class="ri-input-method-line text-info fs-16"></i>
                    <span>Persyaratan Khusus Cabang {{ currentCompetition?.title || '' }}</span>
                  </h6>
                </div>

                <div class="card-body p-4 d-flex flex-column gap-3.5">
                  <template v-for="field in dynamicFields" :key="field.id || field.name">
                    <div v-if="isFieldVisible(field)" class="d-flex flex-column gap-1">
                      
                      <!-- Field Label -->
                      <label :for="'cf-' + field.name" class="form-label fs-13 fw-semibold reg-label mb-0.5 d-flex justify-content-between align-items-center">
                        <span>
                          {{ field.label }}
                          <span v-if="field.required" class="text-danger">*</span>
                          <span v-else class="reg-desc fs-11 fw-normal ms-1">(Opsional)</span>
                        </span>
                        <span v-if="field.type === 'file'" class="badge badge-subtle-info fs-10">
                          {{ (field.allowed_extensions && field.allowed_extensions.length) ? field.allowed_extensions.join(', ').toUpperCase() : 'PDF / JPG / PNG' }} • Maks. {{ field.max_size_mb || 5 }} MB • {{ (field.max_files || 1) > 1 ? `Maks. ${field.max_files} Berkas` : '1 Berkas' }}
                        </span>
                      </label>

                      <!-- 1. Text & URL -->
                      <input
                        v-if="field.type === 'text' || field.type === 'url'"
                        :type="field.type === 'url' ? 'url' : 'text'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        class="form-control reg-input fs-12 py-2"
                        :placeholder="field.placeholder || (field.type === 'url' ? 'https://drive.google.com/...' : `Masukkan ${field.label}...`)"
                        :required="field.required"
                      />

                      <!-- 2. Number / Phone -->
                      <input
                        v-else-if="field.type === 'number'"
                        type="tel"
                        inputmode="numeric"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        @input="form.custom_fields[field.name] = (form.custom_fields[field.name] || '').toString().replace(/\D/g, '')"
                        class="form-control reg-input font-monospace fs-12 py-2"
                        :placeholder="field.placeholder || `Masukkan angka ${field.label}...`"
                        :required="field.required"
                      />

                      <!-- 3. Email -->
                      <input
                        v-else-if="field.type === 'email'"
                        type="email"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        class="form-control reg-input fs-12 py-2"
                        :placeholder="field.placeholder || 'nama@sekolah.sch.id'"
                        :required="field.required"
                      />

                      <!-- 4. Textarea -->
                      <textarea
                        v-else-if="field.type === 'textarea'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        class="form-control reg-input fs-12 py-2"
                        rows="3"
                        :placeholder="field.placeholder || `Tuliskan ${field.label}...`"
                        :required="field.required"
                      ></textarea>

                      <!-- 5. Dropdown Pilihan (Rule 1: Wajib CustomSelect) -->
                      <CustomSelect
                        v-else-if="field.type === 'select'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        :options="field.options || []"
                        :placeholder="field.placeholder || `-- Pilih ${field.label} --`"
                        :searchable="(field.options || []).length > 7"
                        searchPlaceholder="Cari opsi..."
                        size="md"
                      />

                      <!-- 6. Pilihan Tunggal (Radio) -->
                      <div v-else-if="field.type === 'radio'" class="d-flex flex-wrap gap-2 pt-1">
                        <label
                          v-for="(opt, oIdx) in (field.options || [])"
                          :key="oIdx"
                          class="p-2 px-3 rounded-3 border cursor-pointer reg-radio-card fs-12 d-flex align-items-center gap-2"
                          :class="form.custom_fields[field.name] === opt ? 'is-selected' : ''"
                        >
                          <input
                            type="radio"
                            :name="'cf_radio_' + field.name"
                            :value="opt"
                            v-model="form.custom_fields[field.name]"
                            class="form-check-input mt-0"
                          />
                          <span class="reg-title">{{ opt }}</span>
                        </label>
                      </div>

                      <!-- 7. Pilihan Ganda (Checkbox Multi-Select) -->
                      <div v-else-if="field.type === 'checkbox'" class="d-flex flex-wrap gap-2 pt-1">
                        <label
                          v-for="(opt, oIdx) in (field.options || [])"
                          :key="oIdx"
                          @click.prevent="toggleCheckboxOption(field.name, opt)"
                          class="p-2 px-3 rounded-3 border cursor-pointer reg-radio-card fs-12 d-flex align-items-center gap-2"
                          :class="isCheckboxSelected(field.name, opt) ? 'is-selected' : ''"
                        >
                          <input
                            type="checkbox"
                            :checked="isCheckboxSelected(field.name, opt)"
                            class="form-check-input mt-0"
                            tabindex="-1"
                          />
                          <span class="reg-title">{{ opt }}</span>
                        </label>
                      </div>

                      <!-- 8. Tanggal Khusus (Rule 4: Wajib Flatpickr dd/mm/yyyy) -->
                      <IndoDatePicker
                        v-else-if="field.type === 'date'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        :min-date="field.min_date"
                        :max-date="field.max_date"
                        :placeholder="field.placeholder || 'dd/mm/yyyy'"
                        inputClass="form-control reg-input fs-12 py-2"
                        iconClass="ri-calendar-line text-primary"
                      />

                      <!-- 9. Upload Berkas Khusus Cabang -->
                      <div v-else-if="field.type === 'file'">
                        <!-- Single File Mode -->
                        <div v-if="Number(field.max_files || 1) <= 1" class="p-3 rounded-3 border border-dashed reg-upload-zone text-center">
                          <input
                            type="file"
                            :id="'cf-' + field.name"
                            :accept="field.allowed_extensions ? field.allowed_extensions.map(e => '.' + e).join(',') : undefined"
                            class="d-none"
                            @change="e => handleCustomFile(field.name, e, 1)"
                          />
                          <label :for="'cf-' + field.name" class="cursor-pointer mb-0">
                            <i class="ri-upload-cloud-line fs-24 text-primary mb-1 d-block"></i>
                            <span class="fs-12 fw-semibold text-primary d-block">
                              {{ customFileState[field.name] ? customFileState[field.name].name : `Pilih Berkas ${field.label}` }}
                            </span>
                            <span class="fs-10 reg-desc">
                              Format: {{ (field.allowed_extensions && field.allowed_extensions.length) ? field.allowed_extensions.join(', ').toUpperCase() : 'PDF, JPG, PNG' }} • Maks. {{ field.max_size_mb || 5 }} MB
                            </span>
                          </label>
                          <div v-if="customFileState[field.name]" class="mt-2 text-end">
                            <button type="button" class="btn btn-xs btn-danger-subtle text-danger border border-danger-subtle rounded px-2 py-0.5" @click="removeCustomFile(field.name)">
                              <i class="ri-close-line me-1"></i> Batal Berkas
                            </button>
                          </div>
                        </div>

                        <!-- Multi File Mode -->
                        <div v-else class="p-3 rounded-3 border border-dashed reg-upload-zone">
                          <input
                            type="file"
                            :id="'cf-' + field.name"
                            multiple
                            :accept="field.allowed_extensions ? field.allowed_extensions.map(e => '.' + e).join(',') : undefined"
                            class="d-none"
                            @change="e => handleCustomFile(field.name, e, field.max_files)"
                          />
                          <div v-if="getRegistrantFileList(field.name).length === 0" class="text-center">
                            <label :for="'cf-' + field.name" class="cursor-pointer mb-0">
                              <i class="ri-folders-line fs-24 text-primary mb-1 d-block"></i>
                              <span class="fs-12 fw-semibold text-primary d-block">
                                Unggah Dokumen {{ field.label }} (Hingga {{ field.max_files }} Berkas)
                              </span>
                              <span class="fs-10 reg-desc">
                                Format: {{ (field.allowed_extensions && field.allowed_extensions.length) ? field.allowed_extensions.join(', ').toUpperCase() : 'PDF, JPG, PNG' }} • Maks. {{ field.max_size_mb || 5 }} MB per berkas
                              </span>
                            </label>
                          </div>
                          <div v-else class="d-flex flex-column gap-2 text-start">
                            <div class="d-flex align-items-center justify-content-between pb-1 border-bottom">
                              <span class="fs-12 fw-bold text-dark d-flex align-items-center gap-1">
                                <i class="ri-folders-fill text-primary"></i> Berkas Terpilih ({{ getRegistrantFileList(field.name).length }} dari {{ field.max_files }})
                              </span>
                              <label 
                                v-if="getRegistrantFileList(field.name).length < Number(field.max_files || 1)"
                                :for="'cf-' + field.name" 
                                class="btn btn-xs btn-primary-subtle text-primary border border-primary-subtle rounded mb-0 cursor-pointer d-inline-flex align-items-center gap-1"
                              >
                                <i class="ri-add-line"></i> Tambah Berkas
                              </label>
                            </div>
                            <div 
                              v-for="(fItem, fIdx) in getRegistrantFileList(field.name)" 
                              :key="fIdx"
                              class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-white border"
                            >
                              <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="badge bg-light text-muted fs-10 px-1.5 py-0.5">#{{ fIdx + 1 }}</span>
                                <i class="ri-file-text-line fs-18 text-primary"></i>
                                <div class="min-w-0">
                                  <div class="fs-12 fw-semibold text-dark text-truncate">{{ fItem.name }}</div>
                                  <div class="fs-10 text-muted font-monospace">{{ (fItem.size / 1024).toFixed(1) }} KB</div>
                                </div>
                              </div>
                              <button 
                                type="button" 
                                class="btn btn-xs btn-danger-subtle text-danger border border-danger-subtle rounded py-0.5 px-2"
                                @click="removeCustomFile(field.name, fIdx)"
                                title="Hapus berkas ini"
                              >
                                <i class="ri-close-line"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Help Text -->
                      <div v-if="field.help_text" class="fs-11 reg-desc mt-0.5">
                        <i class="ri-information-line me-1"></i>
                        <span>{{ field.help_text }}</span>
                      </div>

                    </div>
                  </template>
                </div>
              </div>

              <!-- Card 3: Data Peserta / Delegasi (Repeater atau Textarea Standar) -->
              <div class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card">
                <div class="card-header border-bottom reg-card-header p-3.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <h6 class="fs-14 fw-bold reg-title mb-0 d-flex align-items-center gap-2">
                    <i class="ri-team-line text-primary fs-16"></i>
                    <span>Data Peserta / Anggota Tim Delegasi</span>
                  </h6>

                  <!-- Participant Count Selector (Jika Repeater Aktif) -->
                  <div v-if="repeaterField" class="d-flex align-items-center gap-2">
                    <span class="fs-11 reg-desc fw-semibold">Jumlah Anggota:</span>
                    <div class="d-flex align-items-center gap-1">
                      <button
                        type="button"
                        class="btn btn-xs btn-light border px-2 py-0.5 rounded"
                        :disabled="participantCount <= teamInfo.min"
                        @click="syncParticipantsCount(participantCount - 1)"
                      >
                        <i class="ri-subtract-line"></i>
                      </button>
                      <span class="fs-12 fw-bold px-2 reg-title">{{ participantCount }}</span>
                      <button
                        type="button"
                        class="btn btn-xs btn-light border px-2 py-0.5 rounded"
                        :disabled="participantCount >= teamInfo.max"
                        @click="syncParticipantsCount(participantCount + 1)"
                      >
                        <i class="ri-add-line"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <div class="card-body p-4 d-flex flex-column gap-3.5">
                  
                  <!-- Kolom Pengisian Nama Tim / Skuad (Jika Aktif, Ditampilkan SEBELUM Data Siswa) -->
                  <div v-if="teamFieldConfig" class="p-3.5 rounded-3 border bg-light-subtle d-flex flex-column gap-2 mb-1">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <label class="form-label fs-12 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                        <i class="ri-shield-star-line text-primary"></i>
                        <span>{{ teamFieldConfig.label }}</span>
                        <span v-if="teamFieldConfig.required" class="text-danger">*</span>
                        <span v-else class="text-muted fs-11 font-normal">(Opsional)</span>
                      </label>
                      <span class="badge bg-primary-subtle text-primary fs-10 px-2 py-0.5 rounded-pill">
                        Delegasi Tim / Skuad
                      </span>
                    </div>
                    <input 
                      type="text" 
                      v-model="form.custom_fields[teamFieldConfig.name]"
                      :placeholder="teamFieldConfig.placeholder"
                      class="form-control form-control-sm fs-12 py-2"
                      :class="{ 'is-invalid': form.errors[teamFieldConfig.name] }"
                    />
                    <div v-if="form.errors[teamFieldConfig.name]" class="invalid-feedback d-block fs-11">
                      {{ form.errors[teamFieldConfig.name] }}
                    </div>
                    <div v-else-if="teamFieldConfig.help_text" class="fs-11 text-muted">
                      <i class="ri-information-line me-1"></i>{{ teamFieldConfig.help_text }}
                    </div>
                  </div>

                  <!-- JIKA MENGGUNAKAN REPEATER ANGGOTA TIM -->
                  <div v-if="repeaterField" class="d-flex flex-column gap-3">
                    <div v-if="teamInfo.rule" class="p-2.5 rounded-3 reg-comp-info fs-11 reg-desc d-flex align-items-center gap-2">
                      <i class="ri-information-line text-warning fs-14 flex-shrink-0"></i>
                      <span>Aturan Anggota Tim: <strong class="reg-title">{{ teamInfo.rule }}</strong></span>
                    </div>

                    <div
                      v-for="pIdx in participantCount"
                      :key="pIdx"
                      class="p-3 rounded-3 border reg-participant-card d-flex flex-column gap-2.5"
                    >
                      <!-- Header Kartu Siswa -->
                      <div class="d-flex align-items-center justify-content-between pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge badge-subtle-primary fs-11 fw-bold rounded-pill">
                            Peserta #{{ pIdx }}
                          </span>
                          <span v-if="participantsList[pIdx - 1]?.is_captain" class="badge badge-subtle-warning fs-10 rounded-pill">
                            <i class="ri-star-fill text-warning me-1"></i> Kapten Tim
                          </span>
                        </div>

                        <!-- Star Captain Toggle -->
                        <button
                          v-if="repeaterField.sub_fields?.captain_option?.active !== false"
                          type="button"
                          class="btn btn-xs rounded-pill d-flex align-items-center gap-1 fs-11 fw-semibold"
                          :class="participantsList[pIdx - 1]?.is_captain ? 'btn-warning text-dark' : 'btn-outline-secondary'"
                          @click="setCaptain(pIdx - 1)"
                        >
                          <i :class="participantsList[pIdx - 1]?.is_captain ? 'ri-star-fill' : 'ri-star-line'"></i>
                          <span>{{ participantsList[pIdx - 1]?.is_captain ? 'Kapten Tim' : 'Jadikan Kapten' }}</span>
                        </button>
                      </div>

                      <!-- Fields Siswa -->
                      <div class="row g-2.5">
                        <!-- Nama Lengkap -->
                        <div class="col-12">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5">Nama Lengkap Siswa <span class="text-danger">*</span></label>
                          <input
                            type="text"
                            v-model="participantsList[pIdx - 1].name"
                            class="form-control reg-input fs-12 py-1.5"
                            :placeholder="repeaterField.sub_fields?.name?.placeholder ? (repeaterField.sub_fields.name.placeholder.includes('#') ? repeaterField.sub_fields.name.placeholder.replace('#', `#${pIdx}`) : `${repeaterField.sub_fields.name.placeholder} #${pIdx}`) : `Nama Lengkap Peserta ${pIdx}`"
                            required
                          />
                        </div>

                        <!-- Jenis Kelamin & Kelas (Rule 1: CustomSelect) -->
                        <div class="col-sm-6">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5">Jenis Kelamin</label>
                          <div class="d-flex gap-2">
                            <label
                              class="flex-grow-1 p-1.5 rounded-2 border cursor-pointer text-center fs-12 reg-gender-btn"
                              :class="participantsList[pIdx - 1].gender === 'L' ? 'reg-gender-male-selected' : 'reg-gender-unselected'"
                            >
                              <input type="radio" :name="`reg_gender_${pIdx}`" value="L" v-model="participantsList[pIdx - 1].gender" class="d-none" />
                              <i class="ri-men-line me-1" :class="participantsList[pIdx - 1].gender === 'L' ? 'text-primary fw-bold' : 'text-muted'"></i>
                              <span>Laki-laki</span>
                              <i v-if="participantsList[pIdx - 1].gender === 'L'" class="ri-checkbox-circle-fill text-primary ms-1"></i>
                            </label>
                            <label
                              class="flex-grow-1 p-1.5 rounded-2 border cursor-pointer text-center fs-12 reg-gender-btn"
                              :class="participantsList[pIdx - 1].gender === 'P' ? 'reg-gender-female-selected' : 'reg-gender-unselected'"
                            >
                              <input type="radio" :name="`reg_gender_${pIdx}`" value="P" v-model="participantsList[pIdx - 1].gender" class="d-none" />
                              <i class="ri-women-line me-1" :class="participantsList[pIdx - 1].gender === 'P' ? 'gender-pink-icon fw-bold' : 'text-muted'"></i>
                              <span>Perempuan</span>
                              <i v-if="participantsList[pIdx - 1].gender === 'P'" class="ri-checkbox-circle-fill gender-pink-icon ms-1"></i>
                            </label>
                          </div>
                        </div>

                        <div class="col-sm-6">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5">Kelas / Tingkat</label>
                          <CustomSelect
                            v-model="participantsList[pIdx - 1].grade"
                            :options="gradeOptions"
                            size="sm"
                            :placeholder="repeaterField.sub_fields?.grade?.placeholder || 'Pilih kelas...'"
                          />
                        </div>

                        <!-- Tanggal Lahir (Rule 4: IndoDatePicker dd/mm/yyyy Flatpickr) & NISN -->
                        <div class="col-sm-6">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5">Tanggal Lahir (dd/mm/yyyy)</label>
                          <IndoDatePicker
                            v-model="participantsList[pIdx - 1].birth_date"
                            :placeholder="repeaterField.sub_fields?.birth_date?.placeholder || 'dd/mm/yyyy'"
                            inputClass="form-control reg-input fs-12 py-1.5"
                            iconClass="ri-calendar-line text-primary"
                          />
                        </div>

                        <div class="col-sm-6">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5">NISN (Opsional)</label>
                          <input
                            type="text"
                            v-model="participantsList[pIdx - 1].nisn"
                            class="form-control reg-input fs-12 py-1.5"
                            :placeholder="repeaterField.sub_fields?.nisn?.placeholder || 'Nomor Induk Siswa Nasional'"
                          />
                        </div>

                        <!-- Kolom Tambahan Biodata Siswa dari Form Builder -->
                        <template v-if="repeaterField.sub_fields?.custom_columns?.length">
                          <div 
                            v-for="col in repeaterField.sub_fields.custom_columns" 
                            :key="col.id || col.label" 
                            class="col-sm-6"
                          >
                            <label class="form-label fs-12 fw-semibold reg-label mb-0.5 d-flex justify-content-between align-items-center">
                              <span>{{ col.label || 'Kolom Tambahan' }}</span>
                              <span v-if="col.required" class="text-danger">*</span>
                            </label>

                            <!-- Kolom Tanggal Flatpickr -->
                            <IndoDatePicker
                              v-if="col.type === 'date'"
                              v-model="participantsList[pIdx - 1].custom_columns[col.name || col.id || col.label]"
                              :placeholder="col.placeholder || 'dd/mm/yyyy'"
                              inputClass="form-control reg-input fs-12 py-1.5"
                              iconClass="ri-calendar-line text-primary"
                              :required="col.required"
                            />

                            <!-- Kolom Pilihan CustomSelect -->
                            <CustomSelect
                              v-else-if="col.type === 'select'"
                              v-model="participantsList[pIdx - 1].custom_columns[col.name || col.id || col.label]"
                              :options="col.options || []"
                              size="sm"
                              :placeholder="col.placeholder || `Pilih ${col.label}...`"
                              :required="col.required"
                            />

                            <!-- Kolom Angka / Teks / Email -->
                            <input
                              v-else
                              :type="col.type === 'number' ? 'number' : (col.type === 'email' ? 'email' : 'text')"
                              :inputmode="col.type === 'number' ? 'numeric' : undefined"
                              v-model="participantsList[pIdx - 1].custom_columns[col.name || col.id || col.label]"
                              :placeholder="col.placeholder || `Masukkan ${col.label}...`"
                              class="form-control reg-input fs-12 py-1.5"
                              :required="col.required"
                            />
                          </div>
                        </template>

                        <!-- Upload Pas Foto Formal Siswa (1 File) -->
                        <div v-if="repeaterField.sub_fields?.photo_formal?.active" class="col-12 col-md-6">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5 d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-1">
                              <i class="ri-camera-line text-primary"></i>
                              <span>{{ repeaterField.sub_fields.photo_formal.label || 'Upload Pas Foto Formal' }}</span>
                              <span v-if="repeaterField.sub_fields.photo_formal.required" class="text-danger">*</span>
                            </span>
                            <span class="badge bg-primary-subtle text-primary fs-10">Maks. 5 MB (1 File)</span>
                          </label>

                          <!-- Preview jika sudah ada foto -->
                          <div 
                            v-if="participantsList[pIdx - 1].photo_formal"
                            class="p-2 rounded-3 border bg-light d-flex align-items-center justify-content-between gap-2"
                          >
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                              <div class="rounded border overflow-hidden flex-shrink-0" style="width: 42px; height: 52px; background: #eaedf1;">
                                <img 
                                  v-if="participantsList[pIdx - 1].photo_formal_preview" 
                                  :src="participantsList[pIdx - 1].photo_formal_preview" 
                                  alt="Pas Foto" 
                                  class="w-100 h-100 object-fit-cover"
                                />
                                <div v-else class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                  <i class="ri-user-line fs-18"></i>
                                </div>
                              </div>
                              <div class="overflow-hidden text-start">
                                <div class="fs-12 fw-bold text-dark text-truncate">{{ participantsList[pIdx - 1].photo_formal.name }}</div>
                                <div class="fs-10 text-muted">{{ (participantsList[pIdx - 1].photo_formal.size / 1024).toFixed(1) }} KB • Foto Formal</div>
                              </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                              <input 
                                type="file" 
                                class="d-none" 
                                :id="`reg_photo_formal_${pIdx}`"
                                accept="image/*"
                                @change="handleParticipantPhotoFormal(pIdx - 1, $event)"
                              />
                              <label 
                                :for="`reg_photo_formal_${pIdx}`" 
                                class="btn btn-xs btn-outline-secondary mb-0 cursor-pointer"
                                title="Ganti Foto"
                              >
                                <i class="ri-refresh-line"></i>
                              </label>
                              <button 
                                type="button" 
                                class="btn btn-xs btn-outline-danger" 
                                @click="removeParticipantPhotoFormal(pIdx - 1)"
                                title="Hapus Foto"
                              >
                                <i class="ri-delete-bin-line"></i>
                              </button>
                            </div>
                          </div>

                          <!-- Dropzone jika belum ada foto -->
                          <div 
                            v-else
                            class="border border-dashed rounded-3 p-2.5 text-center bg-light cursor-pointer hover-bg-subtle transition-all"
                          >
                            <input 
                              type="file" 
                              class="d-none" 
                              :id="`reg_photo_formal_${pIdx}`"
                              accept="image/*"
                              @change="handleParticipantPhotoFormal(pIdx - 1, $event)"
                            />
                            <label :for="`reg_photo_formal_${pIdx}`" class="cursor-pointer mb-0 w-100 d-block">
                              <i class="ri-camera-line text-primary fs-20"></i>
                              <div class="fs-11 fw-semibold text-dark mt-0.5">{{ repeaterField.sub_fields.photo_formal.placeholder || 'Pilih Pas Foto Formal Siswa' }}</div>
                              <div class="fs-10 text-muted">Format JPG, PNG, WEBP (Maks. 5 MB)</div>
                            </label>
                          </div>
                        </div>

                        <!-- Upload Foto Siswa Pose Gaya Bebas (Bisa >1 File) -->
                        <div v-if="repeaterField.sub_fields?.photo_freestyle?.active" class="col-12 col-md-6">
                          <label class="form-label fs-12 fw-semibold reg-label mb-0.5 d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-1">
                              <i class="ri-gallery-upload-line text-success"></i>
                              <span>{{ repeaterField.sub_fields.photo_freestyle.label || 'Foto Pose Gaya Bebas' }}</span>
                              <span v-if="repeaterField.sub_fields.photo_freestyle.required" class="text-danger">*</span>
                            </span>
                            <span class="badge bg-success-subtle text-success fs-10">Bisa >1 Foto</span>
                          </label>

                          <!-- Galeri Preview jika sudah ada foto -->
                          <div 
                            v-if="participantsList[pIdx - 1].photo_freestyle?.length"
                            class="p-2 rounded-3 border bg-light"
                          >
                            <div class="d-flex flex-wrap gap-1.5 mb-1.5">
                              <div 
                                v-for="(f, fIdx) in participantsList[pIdx - 1].photo_freestyle" 
                                :key="fIdx"
                                class="position-relative rounded border overflow-hidden"
                                style="width: 48px; height: 58px; background: #eaedf1;"
                              >
                                <img 
                                  v-if="participantsList[pIdx - 1].photo_freestyle_previews?.[fIdx]" 
                                  :src="participantsList[pIdx - 1].photo_freestyle_previews[fIdx]" 
                                  alt="Foto Bebas" 
                                  class="w-100 h-100 object-fit-cover"
                                />
                                <div v-else class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                  <i class="ri-image-line fs-14"></i>
                                </div>
                                <button 
                                  type="button" 
                                  class="btn btn-danger btn-icon btn-xs position-absolute top-0 end-0 p-0 rounded-circle"
                                  style="width: 16px; height: 16px; font-size: 10px; line-height: 1;"
                                  @click.stop="removeParticipantPhotoFreestyleItem(pIdx - 1, fIdx)"
                                  title="Hapus foto ini"
                                >
                                  ×
                                </button>
                              </div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between pt-1 border-top fs-11">
                              <span class="text-muted">{{ participantsList[pIdx - 1].photo_freestyle.length }} foto terpilih</span>
                              <input 
                                type="file" 
                                class="d-none" 
                                :id="`reg_photo_freestyle_add_${pIdx}`"
                                multiple
                                accept="image/*"
                                @change="handleParticipantPhotoFreestyle(pIdx - 1, $event)"
                              />
                              <label :for="`reg_photo_freestyle_add_${pIdx}`" class="btn btn-xs btn-outline-success mb-0 cursor-pointer d-inline-flex align-items-center gap-1">
                                <i class="ri-add-line"></i> Tambah Foto
                              </label>
                            </div>
                          </div>

                          <!-- Dropzone jika belum ada foto -->
                          <div 
                            v-else
                            class="border border-dashed rounded-3 p-2.5 text-center bg-light cursor-pointer hover-bg-subtle transition-all"
                          >
                            <input 
                              type="file" 
                              class="d-none" 
                              :id="`reg_photo_freestyle_${pIdx}`"
                              multiple
                              accept="image/*"
                              @change="handleParticipantPhotoFreestyle(pIdx - 1, $event)"
                            />
                            <label :for="`reg_photo_freestyle_${pIdx}`" class="cursor-pointer mb-0 w-100 d-block">
                              <i class="ri-images-line text-success fs-20"></i>
                              <div class="fs-11 fw-semibold text-dark mt-0.5">{{ repeaterField.sub_fields.photo_freestyle.placeholder || 'Pilih Satu atau Beberapa Foto Pose Bebas' }}</div>
                              <div class="fs-10 text-muted">Bisa upload >1 foto (JPG, PNG, WEBP, Maks. 5 MB)</div>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- JIKA TANPA REPEATER (TEXTAREA STANDAR FORMATTED) -->
                  <div v-else>
                    <label class="form-label fs-13 fw-semibold reg-label d-flex justify-content-between align-items-center mb-1">
                      <span>Daftar Nama Peserta / Anggota Delegasi <span class="text-danger">*</span></span>
                      <span class="fs-11 reg-desc">Satu nama per baris</span>
                    </label>
                    <textarea
                      v-model="form.participants_names"
                      rows="5"
                      class="form-control reg-input fs-13 font-monospace py-2"
                      placeholder="Contoh format:&#10;1. Muhammad Zaky (NISN: 0081234567, Kelas: 9A) - Kapten&#10;2. Budi Prasetyo (NISN: 0081234568, Kelas: 9B)&#10;3. Citra Kirana (NISN: 0081234569, Kelas: 8C)"
                      required
                    ></textarea>
                    <div class="fs-11 reg-desc mt-1">
                      Tuliskan nomor urut, nama lengkap siswa, NISN, serta kelas/posisi lomba untuk memudahkan verifikasi panitia.
                    </div>
                    <div v-if="form.errors.participants_names" class="text-danger fs-12 mt-1 fw-medium">
                      {{ form.errors.participants_names }}
                    </div>
                  </div>

                  <!-- Catatan Khusus (Opsional) -->
                  <div>
                    <label class="form-label fs-13 fw-semibold reg-label mb-1">
                      Catatan Khusus Tambahan (Opsional)
                    </label>
                    <input
                      v-model="form.notes"
                      type="text"
                      class="form-control reg-input fs-13 py-2"
                      placeholder="Contoh: Jadwal bertanding mohon sesi pagi jika memungkinkan"
                    />
                  </div>

                </div>
              </div>

              <!-- Card 4: Unggah Berkas Dokumen Persyaratan -->
              <div class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card">
                <div class="card-header border-bottom reg-card-header p-3.5">
                  <h6 class="fs-14 fw-bold reg-title mb-0 d-flex align-items-center gap-2">
                    <i class="ri-folder-upload-line text-info fs-16"></i>
                    <span>Unggah Berkas Persyaratan Umum (Opsional / Bisa Susulan)</span>
                  </h6>
                </div>

                <div class="card-body p-4">
                  <p class="fs-12 reg-desc mb-3">
                    Unggah berkas Surat Tugas Kepala Sekolah, Kartu Pelajar, atau Akta Kelahiran (Format PDF / JPG / PNG maksimal 10MB per file). Berkas juga dapat diunggah belakangan melalui Dashboard.
                  </p>

                  <div class="p-3 rounded-3 border border-dashed reg-upload-zone text-center mb-3">
                    <input
                      type="file"
                      id="docFiles"
                      multiple
                      accept=".pdf,.jpg,.jpeg,.png,.docx,.zip"
                      class="d-none"
                      @change="handleDocumentsChange"
                    />
                    <label for="docFiles" class="cursor-pointer mb-0">
                      <i class="ri-upload-cloud-2-line fs-28 text-primary mb-1 d-block"></i>
                      <span class="fs-13 fw-semibold text-primary d-block">Pilih Berkas Dokumen dari Perangkat</span>
                      <span class="fs-11 reg-desc">Mendukung multi-file PDF, Foto, Word, atau ZIP</span>
                    </label>
                  </div>

                  <!-- Selected Files List -->
                  <div v-if="selectedDocumentFiles.length > 0" class="d-flex flex-column gap-2">
                    <div
                      v-for="(f, idx) in selectedDocumentFiles"
                      :key="idx"
                      class="p-2.5 rounded-3 reg-file-item d-flex align-items-center justify-content-between fs-12"
                    >
                      <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="ri-file-text-line text-primary fs-16 flex-shrink-0"></i>
                        <span class="text-truncate fw-medium reg-title">{{ f.name }}</span>
                        <span class="reg-desc fs-11">({{ (f.size / 1024 / 1024).toFixed(2) }} MB)</span>
                      </div>
                      <button
                        type="button"
                        class="btn btn-xs btn-danger-subtle text-danger border border-danger-subtle rounded p-1"
                        @click="removeDocumentFile(idx)"
                        title="Hapus berkas"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </div>
                  </div>

                </div>
              </div>
            </template>

          </div>

          <!-- Right Column: Rincian Biaya & Pembayaran & Submit (Col-lg-5) -->
          <div class="col-lg-5">
            <div class="sticky-top" style="top: 85px; z-index: 10;">
              
              <!-- ========================================================================= -->
              <!-- RINCIAN PEMBAYARAN: PAKET KOMPLIT VS PAKET SATUAN -->
              <!-- ========================================================================= -->

              <!-- 1. JIKA PAKET KOMPLIT (ALL-IN) -->
              <div v-if="selectedPackageType === 'Paket All-In'" class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card mb-4">
                <div class="card-header border-bottom reg-card-header p-3.5">
                  <h6 class="fs-14 fw-bold reg-title mb-0 d-flex align-items-center gap-2">
                    <i class="ri-vip-crown-fill text-warning fs-16"></i>
                    <span>Rincian Pembayaran Paket Komplit</span>
                  </h6>
                </div>

                <div class="card-body p-4 d-flex flex-column gap-3.5">
                  <!-- Nominal Tagihan Paket Komplit -->
                  <div class="p-3.5 rounded-3 text-center reg-fee-box">
                    <div class="fs-12 text-uppercase fw-semibold reg-desc">Biaya Paket All-In (Bebas Semua Cabang)</div>
                    <div class="fs-24 fw-black text-warning mt-1">
                      {{ formatRupiah(pricingSettings.allin_price || 200000) }}
                    </div>
                    <div class="badge badge-subtle-warning fs-11 mt-1">
                      Akses Seluruh Cabang Lomba • Diskon Hemat
                    </div>
                  </div>

                  <!-- Info Rekening Bank Panitia (Multi-Rekening) -->
                  <div class="d-flex flex-column gap-2">
                    <div 
                      v-for="(acc, aIdx) in activeBankAccounts" 
                      :key="'allin-acc-' + (acc.id || aIdx)"
                      class="p-3 rounded-3 reg-bank-box d-flex flex-column gap-2"
                    >
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-11 fw-bold text-uppercase reg-desc">Rekening Transfer Panitia</span>
                        <span v-if="acc.is_primary" class="badge badge-subtle-success fs-10">Utama</span>
                        <span v-else class="badge badge-subtle-info fs-10">Pilihan</span>
                      </div>
                      <div class="fs-13 fw-bold reg-title">
                        {{ acc.bank_name }}
                      </div>
                      <div class="d-flex align-items-center justify-content-between p-2 rounded border reg-rekening-container">
                        <span class="font-monospace fs-15 fw-bold text-success">{{ acc.account_number }}</span>
                        <button
                          type="button"
                          class="btn btn-xs btn-primary rounded-pill px-2.5 py-1 fs-11 fw-semibold"
                          @click="copySpecificAccountNumber(acc)"
                        >
                          <i class="ri-file-copy-line me-1"></i> Salin
                        </button>
                      </div>
                      <div class="d-flex justify-content-between align-items-center fs-11 reg-desc flex-wrap gap-1">
                        <span>Atas Nama: <strong class="reg-title">{{ acc.account_holder }}</strong></span>
                        <span v-if="acc.notes" class="text-muted fst-italic">{{ acc.notes }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Pilihan Metode Pembayaran -->
                  <div>
                    <label class="form-label fs-13 fw-semibold reg-label mb-1">
                      Metode Pembayaran <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex flex-column gap-2">
                      <label 
                        v-for="acc in activeBankAccounts" 
                        :key="'allin-pay-' + (acc.id || acc.account_number)"
                        class="p-2.5 rounded-3 border cursor-pointer reg-radio-option d-flex align-items-center justify-content-between" 
                        :class="allInForm.payment_method === ('Transfer ' + acc.bank_name) ? 'is-selected' : ''"
                      >
                        <div class="d-flex align-items-center gap-2">
                          <input
                            type="radio"
                            name="allin_payment_method"
                            :value="'Transfer ' + acc.bank_name"
                            v-model="allInForm.payment_method"
                            class="form-check-input mt-0"
                          />
                          <span class="fs-12 fw-semibold reg-title">Transfer {{ acc.bank_name }} (Rek. {{ acc.account_number }})</span>
                        </div>
                        <i class="ri-bank-line text-success fs-16"></i>
                      </label>

                      <label class="p-2.5 rounded-3 border cursor-pointer reg-radio-option d-flex align-items-center justify-content-between" :class="allInForm.payment_method === 'Tunai di Sekretariat' ? 'is-selected' : ''">
                        <div class="d-flex align-items-center gap-2">
                          <input
                            type="radio"
                            name="allin_payment_method"
                            value="Tunai di Sekretariat"
                            v-model="allInForm.payment_method"
                            class="form-check-input mt-0"
                          />
                          <span class="fs-12 fw-semibold reg-title">Tunai Langsung di Sekretariat Panitia</span>
                        </div>
                        <i class="ri-money-dollar-circle-line text-warning fs-16"></i>
                      </label>
                    </div>
                  </div>

                  <!-- Upload Bukti Transfer Paket Komplit (Opsional / Bisa Susulan) -->
                  <div v-if="allInForm.payment_method !== 'Tunai di Sekretariat'">
                    <label class="form-label fs-13 fw-semibold reg-label mb-1">
                      Bukti Transfer Pembayaran (Opsional / Bisa Susulan)
                    </label>
                    <div class="p-3 rounded-3 border border-dashed reg-upload-zone text-center">
                      <input
                        type="file"
                        id="allInProofFile"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="d-none"
                        @change="handleAllInProofChange"
                      />
                      <label for="allInProofFile" class="cursor-pointer mb-0">
                        <i class="ri-receipt-line fs-24 text-warning mb-1 d-block"></i>
                        <span class="fs-12 fw-semibold text-warning d-block">
                          {{ allInProofFile ? allInProofFile.name : 'Pilih Foto Struk / Screenshot Transfer' }}
                        </span>
                        <span class="fs-10 reg-desc">JPG, PNG, atau PDF maks 10MB</span>
                      </label>
                    </div>
                    <div v-if="allInProofFile" class="mt-2 text-end">
                      <button type="button" class="btn btn-xs btn-danger-subtle text-danger border border-danger-subtle rounded px-2 py-0.5" @click="removeAllInProofFile">
                        <i class="ri-close-line me-1"></i> Batal Bukti
                      </button>
                    </div>
                  </div>

                  <!-- Submit Button Paket Komplit -->
                  <button
                    type="submit"
                    class="btn btn-warning text-dark w-100 py-2.5 fs-14 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-2"
                    :disabled="allInForm.processing"
                  >
                    <span v-if="allInForm.processing" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ri-vip-crown-fill fs-18"></i>
                    <span>{{ allInForm.processing ? 'Memproses Pendaftaran...' : 'Daftar & Terbitkan Kode Paket Komplit' }}</span>
                  </button>

                  <div class="fs-11 reg-desc text-center">
                    Setelah disimpan, <strong>Kode Paket Komplit</strong> akan langsung terbuat secara otomatis untuk mendaftarkan semua cabang lomba.
                  </div>
                </div>
              </div>

              <!-- 2. JIKA PAKET SATUAN -->
              <div v-else class="card border-0 shadow-sm rounded-4 overflow-hidden reg-card mb-4">
                <div class="card-header border-bottom reg-card-header p-3.5">
                  <h6 class="fs-14 fw-bold reg-title mb-0 d-flex align-items-center gap-2">
                    <i class="ri-bank-card-line text-success fs-16"></i>
                    <span>Rincian Pembayaran Cabang Lomba</span>
                  </h6>
                </div>

                <div class="card-body p-4 d-flex flex-column gap-3.5">
                  
                  <!-- Nominal Fee Card -->
                  <div class="p-3.5 rounded-3 text-center reg-fee-box">
                    <div class="fs-12 text-uppercase fw-semibold reg-desc">Total Tagihan Registrasi</div>
                    <div class="fs-24 fw-black text-primary mt-1">
                      {{ formatRupiah(calculatedFee) }}
                    </div>
                    <div v-if="calculatedFee === 0" class="badge badge-subtle-success fs-11 mt-1">
                      Gratis / Lunas Paket All-In
                    </div>
                  </div>

                  <!-- Info Rekening Bank Panitia (Multi-Rekening) -->
                  <div v-if="calculatedFee > 0" class="d-flex flex-column gap-2">
                    <div 
                      v-for="(acc, aIdx) in activeBankAccounts" 
                      :key="'satuan-acc-' + (acc.id || aIdx)"
                      class="p-3 rounded-3 reg-bank-box d-flex flex-column gap-2"
                    >
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-11 fw-bold text-uppercase reg-desc">Rekening Transfer Panitia</span>
                        <span v-if="acc.is_primary" class="badge badge-subtle-success fs-10">Utama</span>
                        <span v-else class="badge badge-subtle-info fs-10">Pilihan</span>
                      </div>
                      <div class="fs-13 fw-bold reg-title">
                        {{ acc.bank_name }}
                      </div>
                      <div class="d-flex align-items-center justify-content-between p-2 rounded border reg-rekening-container">
                        <span class="font-monospace fs-15 fw-bold text-success">{{ acc.account_number }}</span>
                        <button
                          type="button"
                          class="btn btn-xs btn-primary rounded-pill px-2.5 py-1 fs-11 fw-semibold"
                          @click="copySpecificAccountNumber(acc)"
                        >
                          <i class="ri-file-copy-line me-1"></i> Salin
                        </button>
                      </div>
                      <div class="d-flex justify-content-between align-items-center fs-11 reg-desc flex-wrap gap-1">
                        <span>Atas Nama: <strong class="reg-title">{{ acc.account_holder }}</strong></span>
                        <span v-if="acc.notes" class="text-muted fst-italic">{{ acc.notes }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Metode Pembayaran Selector -->
                  <div v-if="calculatedFee > 0">
                    <label class="form-label fs-13 fw-semibold reg-label mb-1">
                      Metode Pembayaran <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex flex-column gap-2">
                      <label 
                        v-for="acc in activeBankAccounts" 
                        :key="'single-pay-' + (acc.id || acc.account_number)"
                        class="p-2.5 rounded-3 border cursor-pointer reg-radio-option d-flex align-items-center justify-content-between" 
                        :class="form.payment_method === ('Transfer ' + acc.bank_name) ? 'is-selected' : ''"
                      >
                        <div class="d-flex align-items-center gap-2">
                          <input
                            type="radio"
                            name="payment_method"
                            :value="'Transfer ' + acc.bank_name"
                            v-model="form.payment_method"
                            class="form-check-input mt-0"
                          />
                          <span class="fs-12 fw-semibold reg-title">Transfer {{ acc.bank_name }} (Rek. {{ acc.account_number }})</span>
                        </div>
                        <i class="ri-bank-line text-success fs-16"></i>
                      </label>

                      <label class="p-2.5 rounded-3 border cursor-pointer reg-radio-option d-flex align-items-center justify-content-between" :class="form.payment_method === 'Tunai di Sekretariat' ? 'is-selected' : ''">
                        <div class="d-flex align-items-center gap-2">
                          <input
                            type="radio"
                            name="payment_method"
                            value="Tunai di Sekretariat"
                            v-model="form.payment_method"
                            class="form-check-input mt-0"
                          />
                          <span class="fs-12 fw-semibold reg-title">Tunai Langsung di Sekretariat Panitia</span>
                        </div>
                        <i class="ri-money-dollar-circle-line text-warning fs-16"></i>
                      </label>
                    </div>
                  </div>

                  <!-- Upload Bukti Transfer -->
                  <div v-if="calculatedFee > 0 && form.payment_method !== 'Tunai di Sekretariat'">
                    <label class="form-label fs-13 fw-semibold reg-label mb-1">
                      Unggah Bukti Transfer Pembayaran (Opsional / Bisa Susulan)
                    </label>
                    <div class="p-3 rounded-3 border border-dashed reg-upload-zone text-center">
                      <input
                        type="file"
                        id="proofFile"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="d-none"
                        @change="handleProofChange"
                      />
                      <label for="proofFile" class="cursor-pointer mb-0">
                        <i class="ri-receipt-line fs-24 text-success mb-1 d-block"></i>
                        <span class="fs-12 fw-semibold text-success d-block">
                          {{ paymentProofFile ? paymentProofFile.name : 'Pilih Foto Struk / Screenshot Transfer' }}
                        </span>
                        <span class="fs-10 reg-desc">JPG, PNG, atau PDF maks 10MB</span>
                      </label>
                    </div>
                    <div v-if="paymentProofFile" class="mt-2 text-end">
                      <button type="button" class="btn btn-xs btn-danger-subtle text-danger border border-danger-subtle rounded px-2 py-0.5" @click="removeProofFile">
                        <i class="ri-close-line me-1"></i> Batal Unggah Bukti
                      </button>
                    </div>
                  </div>

                  <!-- Submit Button Paket Satuan -->
                  <template v-if="currentCompetition && (currentCompetition.is_direct_registration || currentCompetition.registration_type === 'direct_contact')">
                    <a
                      :href="getDirectRegistrationWaUrl(currentCompetition)"
                      target="_blank"
                      rel="noopener"
                      class="btn btn-success w-100 py-2.5 fs-14 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-2"
                    >
                      <i class="ri-whatsapp-line fs-18"></i>
                      <span>Hubungi Koordinator via WhatsApp untuk Mendaftar</span>
                    </a>
                  </template>
                  <button
                    v-else
                    type="submit"
                    class="btn btn-primary w-100 py-2.5 fs-14 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-2"
                    :disabled="form.processing"
                  >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ri-send-plane-fill fs-18"></i>
                    <span>{{ form.processing ? 'Menyimpan Pendaftaran...' : (currentCompetition?.form_settings?.submit_text || 'Kirim Pendaftaran Delegasi') }}</span>
                  </button>

                  <div class="fs-11 reg-desc text-center">
                    Setelah dikirim, nomor registrasi dan status verifikasi akan langsung muncul di <strong>Dashboard Pendaftar</strong>.
                  </div>

                </div>
              </div>

            </div>
          </div>

        </div>
      </form>

      <!-- ========================================================================= -->
      <!-- POP-UP MODAL: KODE PAKET KOMPLIT TERBUAT OTOMATIS -->
      <!-- ========================================================================= -->
      <div v-if="showSuccessCodeModal" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
        <div class="modal-card-custom rounded-4 p-4 shadow-lg text-center position-relative w-100" style="max-width: 500px;">
          
          <!-- Close button top-right -->
          <button
            type="button"
            class="btn-close-custom position-absolute top-0 end-0 m-3 d-flex align-items-center justify-content-center rounded-circle"
            @click="showSuccessCodeModal = false"
            title="Tutup"
          >
            <i class="ri-close-line fs-18"></i>
          </button>

          <!-- Icon Header -->
          <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning" style="width: 64px; height: 64px;">
            <i class="ri-vip-crown-fill fs-32"></i>
          </div>

          <h4 class="fs-18 fw-bold reg-title mb-1.5">Kode Paket Komplit Berhasil Dibuat!</h4>
          <p class="fs-12.5 reg-desc mb-3">
            Pendaftaran Paket Komplit untuk <strong>{{ newGeneratedSchool }}</strong> telah berhasil disimpan. Berikut kode pendaftaran resmi kontingen Anda:
          </p>

          <!-- Big Display Kode Box -->
          <div class="reg-generated-code-box p-3 rounded-3 mb-3 d-flex align-items-center justify-content-between gap-2 border">
            <div class="text-start">
              <span class="fs-10 text-uppercase fw-bold text-warning d-block">Kode Paket Komplit (All-In)</span>
              <span class="fs-20 fw-black font-monospace text-primary letter-spacing-1">{{ newGeneratedCode }}</span>
            </div>
            <button
              type="button"
              class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 fs-12 fw-semibold d-flex align-items-center gap-1.5 flex-shrink-0"
              @click="copyGeneratedCode"
            >
              <i :class="isCopied ? 'ri-check-line text-success' : 'ri-file-copy-line'"></i>
              <span>{{ isCopied ? 'Tersalin!' : 'Salin Kode' }}</span>
            </button>
          </div>

          <!-- Info Benefit -->
          <div class="p-2.5 rounded-3 reg-comp-info fs-11 reg-desc text-start mb-3 d-flex align-items-start gap-2">
            <i class="ri-information-line text-warning fs-15 mt-0.5 flex-shrink-0"></i>
            <div>
              Gunakan kode ini saat mendaftarkan delegasi cabang lomba di tab <strong>Paket Satuan</strong> agar seluruh cabang lomba menjadi <strong>Bebas Biaya (Rp 0)</strong>.
            </div>
          </div>

          <!-- Modal Actions -->
          <div class="d-flex flex-column gap-2">
            <button
              type="button"
              class="btn btn-primary py-2 fs-13 fw-bold rounded-pill d-flex align-items-center justify-content-center gap-2 shadow-sm"
              @click="useGeneratedCodeForSingle"
            >
              <i class="ri-arrow-right-line"></i>
              <span>Lanjut Daftarkan Cabang Lomba (Gunakan Kode Ini)</span>
            </button>

            <button
              type="button"
              class="btn btn-outline-secondary py-1.5 fs-12 fw-semibold rounded-pill"
              @click="showSuccessCodeModal = false"
            >
              Tutup Dialog
            </button>
          </div>

        </div>
      </div>

    </main>

    <!-- Bottom Footer -->
    <footer class="w-100 text-center py-3 mt-4 mx-auto border-top" style="max-width: 1140px;">
      <div class="fs-12 reg-desc">
        © 2026 SEMARAK ALIYAH XIV • MA Unggulan Wahid Hasyim Balung Jember
      </div>
    </footer>

  </div>
</template>

<style>
.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Page Wrapper Background */
.reg-page-wrapper {
  background: radial-gradient(circle at 50% 10%, #e0e7ff 0%, #f8fafc 60%, #f1f5f9 100%);
  transition: background 0.3s ease;
}
[data-bs-theme="dark"] .reg-page-wrapper,
.reg-page-wrapper.dark-theme {
  background: radial-gradient(circle at 50% 15%, #1e1b4b 0%, #0b0f19 70%, #05070e 100%) !important;
}

/* Topbar Header */
.registrant-topbar {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .registrant-topbar {
  background: rgba(15, 23, 42, 0.92);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.topbar-brand {
  color: #0f172a;
}
[data-bs-theme="dark"] .topbar-brand {
  color: #ffffff;
}

.topbar-subbrand {
  color: #64748b;
}
[data-bs-theme="dark"] .topbar-subbrand {
  color: #94a3b8;
}

.topbar-divider {
  border-color: #e2e8f0 !important;
}
[data-bs-theme="dark"] .topbar-divider {
  border-color: rgba(255, 255, 255, 0.15) !important;
}

/* Badge Portal Resmi */
.badge-portal-resmi {
  background: rgba(59, 130, 246, 0.12);
  border: 1px solid rgba(59, 130, 246, 0.25);
  color: #1d4ed8;
}
[data-bs-theme="dark"] .badge-portal-resmi {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.35);
  color: #93c5fd;
}

/* Theme Toggle Button */
.dashboard-theme-toggle,
.reg-back-btn {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #1e293b;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
[data-bs-theme="dark"] .dashboard-theme-toggle,
[data-bs-theme="dark"] .reg-back-btn {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #f8fafc;
}
.dashboard-theme-toggle:hover,
.reg-back-btn:hover {
  filter: brightness(0.96);
}
[data-bs-theme="dark"] .dashboard-theme-toggle:hover,
[data-bs-theme="dark"] .reg-back-btn:hover {
  filter: brightness(1.2);
}

/* Card Styling */
.reg-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid #e2e8f0 !important;
}
[data-bs-theme="dark"] .reg-card {
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.reg-card-header {
  background: transparent;
  border-color: #e2e8f0;
}
[data-bs-theme="dark"] .reg-card-header {
  border-color: rgba(255, 255, 255, 0.08);
}

/* Typography Contrast (Rule 2) */
.reg-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .reg-title {
  color: #ffffff;
}

.reg-desc {
  color: #334155;
}
[data-bs-theme="dark"] .reg-desc {
  color: #cbd5e1;
}

.reg-label {
  color: #1e293b;
}
[data-bs-theme="dark"] .reg-label {
  color: #f8fafc;
}

/* Input Fields */
.reg-input {
  background-color: #ffffff;
  border-color: #cbd5e1;
  color: #0f172a;
}
[data-bs-theme="dark"] .reg-input {
  background-color: rgba(30, 41, 59, 0.7);
  border-color: rgba(255, 255, 255, 0.15);
  color: #ffffff;
}
.reg-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.reg-input-addon {
  background-color: #f8fafc;
  border-color: #cbd5e1;
  color: #64748b;
}
[data-bs-theme="dark"] .reg-input-addon {
  background-color: rgba(30, 41, 59, 0.9);
  border-color: rgba(255, 255, 255, 0.15);
  color: #94a3b8;
}

/* Boxes & Zones */
.reg-package-box {
  background: #f8fafc;
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .reg-package-box {
  background: rgba(30, 41, 59, 0.6);
  border-color: rgba(255, 255, 255, 0.12) !important;
}
.reg-package-box.is-selected {
  background: rgba(59, 130, 246, 0.08);
  border-color: #3b82f6 !important;
}
[data-bs-theme="dark"] .reg-package-box.is-selected {
  background: rgba(59, 130, 246, 0.2);
  border-color: #60a5fa !important;
}
.reg-package-box.is-selected-gold {
  background: rgba(245, 158, 11, 0.08);
  border-color: #f59e0b !important;
}
[data-bs-theme="dark"] .reg-package-box.is-selected-gold {
  background: rgba(245, 158, 11, 0.2);
  border-color: #fbbf24 !important;
}

.reg-comp-info {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .reg-comp-info {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.reg-participant-card {
  background: #ffffff;
  border-color: #e2e8f0 !important;
}
[data-bs-theme="dark"] .reg-participant-card {
  background: rgba(30, 41, 59, 0.5);
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.reg-upload-zone {
  background: #f8fafc;
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .reg-upload-zone {
  background: rgba(30, 41, 59, 0.5);
  border-color: rgba(255, 255, 255, 0.15) !important;
}

.reg-file-item {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .reg-file-item {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.reg-fee-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .reg-fee-box {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.reg-bank-box {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .reg-bank-box {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.reg-rekening-container {
  background-color: #ffffff !important;
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .reg-rekening-container {
  background-color: #0b0f19 !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}

.reg-radio-option,
.reg-radio-card {
  background: #f8fafc;
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .reg-radio-option,
[data-bs-theme="dark"] .reg-radio-card {
  background: rgba(30, 41, 59, 0.6);
  border-color: rgba(255, 255, 255, 0.12) !important;
}
.reg-radio-option.is-selected,
.reg-radio-card.is-selected {
  background: rgba(59, 130, 246, 0.08);
  border-color: #3b82f6 !important;
}
[data-bs-theme="dark"] .reg-radio-option.is-selected,
[data-bs-theme="dark"] .reg-radio-card.is-selected {
  background: rgba(59, 130, 246, 0.2);
  border-color: #60a5fa !important;
}

/* Gender Selector High-Contrast Styling (User Request & Rule 2) */
.reg-gender-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  border: 1.5px solid transparent;
  user-select: none;
}
.reg-gender-unselected {
  background: #f8fafc;
  border-color: #cbd5e1 !important;
  color: #475569;
}
[data-bs-theme="dark"] .reg-gender-unselected {
  background: rgba(30, 41, 59, 0.6);
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #94a3b8;
}
.reg-gender-unselected:hover {
  border-color: #94a3b8 !important;
  background: #f1f5f9;
}
[data-bs-theme="dark"] .reg-gender-unselected:hover {
  background: rgba(51, 65, 85, 0.7);
  border-color: rgba(255, 255, 255, 0.25) !important;
}

/* Laki-laki Selected: High Contrast Blue */
.reg-gender-male-selected {
  background: rgba(37, 99, 235, 0.12) !important;
  border-color: #2563eb !important;
  color: #1d4ed8 !important;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.25);
  font-weight: 700;
}
[data-bs-theme="dark"] .reg-gender-male-selected {
  background: rgba(59, 130, 246, 0.22) !important;
  border-color: #60a5fa !important;
  color: #93c5fd !important;
  box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.3);
}

/* Perempuan Selected: High Contrast Pink/Rose */
.reg-gender-female-selected {
  background: rgba(236, 72, 153, 0.12) !important;
  border-color: #ec4899 !important;
  color: #be185d !important;
  box-shadow: 0 0 0 2px rgba(236, 72, 153, 0.25);
  font-weight: 700;
}
[data-bs-theme="dark"] .reg-gender-female-selected {
  background: rgba(244, 114, 182, 0.22) !important;
  border-color: #f472b6 !important;
  color: #fbcfe8 !important;
  box-shadow: 0 0 0 2px rgba(244, 114, 182, 0.3);
}

.gender-pink-icon {
  color: #ec4899 !important;
}
[data-bs-theme="dark"] .gender-pink-icon {
  color: #f472b6 !important;
}

.reg-allin-badge {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}
[data-bs-theme="dark"] .reg-allin-badge {
  background: rgba(245, 158, 11, 0.15);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #fef3c7;
}

/* Alert Boxes Contrast (Rule 2) */
.alert-verified-box {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #065f46;
}
[data-bs-theme="dark"] .alert-verified-box {
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(16, 185, 129, 0.35);
  color: #a7f3d0;
}

.verified-code-badge {
  background: rgba(16, 185, 129, 0.15);
  color: #047857;
}
[data-bs-theme="dark"] .verified-code-badge {
  background: rgba(16, 185, 129, 0.25);
  color: #86efac;
}

.alert-danger-box {
  background: rgba(239, 68, 68, 0.08);
  border: 1px solid rgba(239, 68, 68, 0.25);
  color: #991b1b;
}
[data-bs-theme="dark"] .alert-danger-box {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #fca5a5;
}

/* Rule 3: Subtle Badges High-Luminescence in Dark Mode & Rich Contrast in Light Mode */
.badge-subtle-primary {
  background: rgba(59, 130, 246, 0.12);
  border: 1px solid rgba(59, 130, 246, 0.25);
  color: #1d4ed8;
}
[data-bs-theme="dark"] .badge-subtle-primary {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.35);
  color: #93c5fd;
}

.badge-subtle-success {
  background: rgba(16, 185, 129, 0.12);
  border: 1px solid rgba(16, 185, 129, 0.25);
  color: #047857;
}
[data-bs-theme="dark"] .badge-subtle-success {
  background: rgba(16, 185, 129, 0.2);
  border-color: rgba(16, 185, 129, 0.35);
  color: #86efac;
}

.badge-subtle-warning {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.25);
  color: #b45309;
}
[data-bs-theme="dark"] .badge-subtle-warning {
  background: rgba(245, 158, 11, 0.22);
  border-color: rgba(245, 158, 11, 0.35);
  color: #fde047;
}

.badge-subtle-info {
  background: rgba(14, 165, 233, 0.12);
  border: 1px solid rgba(14, 165, 233, 0.25);
  color: #0369a1;
}
[data-bs-theme="dark"] .badge-subtle-info {
  background: rgba(14, 165, 233, 0.2);
  border-color: rgba(14, 165, 233, 0.35);
  color: #7dd3fc;
}

/* Modal Pop-up Sukses Terbitkan Kode Paket Komplit Otomatis */
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.72);
  backdrop-filter: blur(8px);
  z-index: 1050;
  animation: modalFadeIn 0.2s ease-out;
}

.modal-card-custom {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalScaleUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
[data-bs-theme="dark"] .modal-card-custom {
  background: #0f172a;
  border-color: rgba(255, 255, 255, 0.15);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
}

.btn-close-custom {
  width: 32px;
  height: 32px;
  background: #f1f5f9;
  border: none;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-close-custom:hover {
  background: #e2e8f0;
  color: #0f172a;
}
[data-bs-theme="dark"] .btn-close-custom {
  background: rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
}
[data-bs-theme="dark"] .btn-close-custom:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.reg-generated-code-box {
  background: #f8fafc;
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .reg-generated-code-box {
  background: rgba(30, 41, 59, 0.8);
  border-color: rgba(255, 255, 255, 0.15) !important;
}

@keyframes modalFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalScaleUp {
  from { transform: scale(0.92); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>
