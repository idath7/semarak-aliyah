<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { getWaChatUrl } from '@/Utils/whatsapp';

const page = usePage();
const school = computed(() => page.props.schoolProfile || {});

const props = defineProps({
  competitions: {
    type: Array,
    default: () => []
  },
  preselectedLomba: String,
  pricingSettings: {
    type: Object,
    default: () => ({})
  },
  schools: {
    type: Array,
    default: () => []
  },
  isLoginRequired: {
    type: Boolean,
    default: false
  },
  authRegistrant: {
    type: Object,
    default: null
  }
});

const isSuccess = ref(false);
const registeredData = ref(null);
const registerModalOpen = ref(false);
const modalBodyRef = ref(null);

const form = useForm({
  package_type: 'Paket Satuan', // 'Paket Satuan' | 'Paket All-In'
  school_name: props.authRegistrant?.school_name || '',
  mentor_name: props.authRegistrant?.name || '',
  mentor_wa: props.authRegistrant?.phone || '',
  competition_id: props.preselectedLomba || props.competitions?.[0]?.id || 'mipa',
  participants_names: '',
  custom_fields: {},
  notes: '',
  payment_method: 'Transfer Bank BSI',
});

// Dynamic Participant Repeater & Team Sync Logic
const activeComp = computed(() => props.competitions?.find(c => c.id === form.competition_id) || null);
const repeaterField = computed(() => activeComp.value?.custom_fields?.find(f => f.type === 'participant_repeater') || null);

// Dynamic Custom Fields dari Form Builder (selain repeater & 8 field standar identitas/paket)
const dynamicFields = computed(() => {
  if (!activeComp.value || !Array.isArray(activeComp.value.custom_fields)) return [];
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
  return activeComp.value.custom_fields.filter(f => {
    if (!f || !f.name) return false;
    if (f.type === 'participant_repeater') return false;
    if (excluded.includes(f.name)) return false;
    return true;
  });
});

// Evaluator Conditional Logic Form Builder
function evaluateCondition(logic, values) {
  if (!logic || !logic.enabled || !logic.depend_on_field) {
    return true;
  }
  const targetVal = values[logic.depend_on_field];
  const compareVal = logic.value;
  const op = logic.operator || '==';

  if (targetVal === undefined || targetVal === null || targetVal === '') {
    return false;
  }

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
    case 'contains':
      return String(targetVal).toLowerCase().includes(String(compareVal).toLowerCase());
    default:
      return true;
  }
}

function isFieldVisible(field) {
  return evaluateCondition(field?.conditional_logic, form.custom_fields);
}

// Inisialisasi otomatis custom_fields setiap kali cabang lomba berubah
watch(activeComp, (newComp) => {
  if (!newComp || !Array.isArray(newComp.custom_fields)) return;
  const currentFields = { ...(form.custom_fields || {}) };
  newComp.custom_fields.forEach(f => {
    if (f.type === 'participant_repeater') return;
    if (f.name && currentFields[f.name] === undefined) {
      currentFields[f.name] = f.type === 'checkbox' ? [] : (f.type === 'file' ? null : '');
    }
  });
  form.custom_fields = currentFields;
}, { immediate: true });

function handleCustomFileChange(fieldName, event) {
  const file = event.target.files?.[0];
  if (file) {
    form.custom_fields[fieldName] = file;
  }
}

function clearCustomFile(fieldName) {
  form.custom_fields[fieldName] = null;
}

function getCustomFileName(fieldName) {
  const f = form.custom_fields[fieldName];
  if (!f) return '';
  return f.name || 'Dokumen Terpilih';
}

function getCustomFileSize(fieldName) {
  const f = form.custom_fields[fieldName];
  return f?.size || 0;
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

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

function getFieldError(fieldName) {
  return form.errors['cf_' + fieldName] || form.errors[fieldName] || form.errors['custom_fields.' + fieldName];
}

// Helper to parse and synchronize team rules (inti & cadangan) from competition management
function getTeamComposition(comp, rf) {
  if (!comp) return { isTeam: false, min: 1, max: 10, main: 1, reserve: 0, rule: '' };
  
  const isTeam = comp.participant_type === 'tim';
  const rule = comp.participants_rule || '';
  let max = rf?.max_items ? Number(rf.max_items) : (comp.max_participants ? Number(comp.max_participants) : (isTeam ? 10 : 1));
  let min = rf?.min_items ? Number(rf.min_items) : (isTeam ? Math.min(max, 3) : 1);
  let main = rf?.main_members ? Number(rf.main_members) : 0;
  let reserve = rf?.reserve_members ? Number(rf.reserve_members) : 0;

  // Try parsing participants_rule like "1 Tim (5 pemain inti + 1 cadangan)" or "6 pemain inti + 4 cadangan"
  if ((!main || !reserve) && rule) {
    const matchInti = rule.match(/(\d+)\s*(?:pemain|orang)?\s*inti/i);
    const matchCadangan = rule.match(/(\d+)\s*cadangan/i);
    if (matchInti) {
      main = parseInt(matchInti[1], 10);
      if (matchCadangan) {
        reserve = parseInt(matchCadangan[1], 10);
      }
      if (!rf?.min_items) {
        min = main; // core members are required
      }
      if (!rf?.max_items) {
        max = main + reserve;
      }
    }
  }

  if (!main) {
    main = isTeam ? max : 1;
  }

  return {
    isTeam,
    min: Math.max(1, min),
    max: Math.max(min, max),
    main,
    reserve,
    rule
  };
}

const teamInfo = computed(() => getTeamComposition(activeComp.value, repeaterField.value));

const participantCount = ref(3);

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
    captain_wa: '',
    photo_formal_name: '',
    freestyle_count: 0,
  };
}

const participantsList = ref([
  createEmptyParticipant(),
  createEmptyParticipant(),
  createEmptyParticipant(),
]);

function setCaptain(idx) {
  participantsList.value.forEach((p, i) => {
    p.is_captain = (i === idx);
    if (!p.is_captain) {
      p.captain_wa = '';
    }
  });
}

function handleFreestyleFiles(idx, event) {
  const files = event.target.files;
  participantsList.value[idx].freestyle_count = files ? files.length : 0;
}

function syncParticipantsList() {
  const target = Math.max(1, participantCount.value);
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

watch([activeComp, repeaterField], () => {
  if (repeaterField.value) {
    const info = teamInfo.value;
    participantCount.value = Math.max(info.min, Math.min(info.max, info.main || info.min));
    syncParticipantsList();
  }
}, { immediate: true });

function adjustCount(delta) {
  if (!repeaterField.value) return;
  const info = teamInfo.value;
  participantCount.value = Math.max(info.min, Math.min(info.max, participantCount.value + delta));
  syncParticipantsList();
}

// Auto-open modal if lomba query param is present & auto-select school from profile
onMounted(async () => {
  const initialSchool = props.authRegistrant?.school_name || form.school_name;
  if (initialSchool && initialSchool.trim() !== '') {
    schoolSearchInput.value = initialSchool;
    const found = props.schools?.find(s => s.name.toLowerCase() === initialSchool.toLowerCase());
    if (found) {
      selectedSchool.value = found;
    } else {
      selectedSchool.value = {
        name: initialSchool,
        kecamatan: props.authRegistrant?.school_district || 'Jember',
        jenjang: 'SMP/MTs',
        status: 'Terdaftar',
        npsn: ''
      };
    }
    await checkSchoolStatus(initialSchool);
  }

  if (props.preselectedLomba) {
    const matched = props.competitions?.find(c => c.id === props.preselectedLomba);
    if (matched) {
      form.competition_id = matched.id;
      registerModalOpen.value = true;
    }
  }
});

// -----------------------------------------------------------------------------
// School Search & Status Hub State
// -----------------------------------------------------------------------------
const schoolSearchInput = ref('');
const selectedSchool = ref(null);
const schoolStatusData = ref(null);
const isCheckingSchoolStatus = ref(false);
const showHubSchoolDropdown = ref(false);
const showNewSchoolModal = ref(false);
const showAllInModal = ref(false);
const hubSuggestions = ref([]);
let hubSearchTimeout = null;

// 31 Kecamatan di Kabupaten Jember + Opsi Luar Kabupaten untuk CustomSelect
const kecamatanList = [
  'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 'Jenggawah',
  'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 'Mayang', 'Mumbulsari', 'Pakusari',
  'Panti', 'Patrang', 'Puger', 'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono',
  'Sumberbaru', 'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari', 'Wuluhan',
  'Luar Kabupaten Jember'
];

// State & Handler Verifikasi Kode Paket Komplit (All-In)
const isCheckingPackageCode = ref(false);
const isManualCodeVerified = ref(false);
const codeVerificationError = ref('');
const manualCodeSuccessData = ref(null);

async function verifyPackageCodeManual() {
  const code = (form.custom_fields?.['allin_code'] || '').trim();
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
      if (!form.custom_fields) form.custom_fields = {};
      form.custom_fields['allin_code'] = json.data.reg_code;

      // Auto-prefill data sekolah secara lengkap agar pendaftar tidak perlu input lagi
      if (json.data.school_name) {
        form.school_name = json.data.school_name;
      }
      if (json.data.school_district) {
        form.custom_fields['school_district'] = json.data.school_district;
      }
      if (json.data.school_npsn) {
        form.custom_fields['school_npsn'] = json.data.school_npsn;
      }
      if (json.data.school_address) {
        form.custom_fields['school_address'] = json.data.school_address;
      }

      form.package_type = 'Paket All-In';
    } else {
      isManualCodeVerified.value = false;
      manualCodeSuccessData.value = null;
      codeVerificationError.value = json.message || 'Kode Paket Komplit tidak valid atau tidak terdaftar.';
    }
  } catch (err) {
    isManualCodeVerified.value = false;
    manualCodeSuccessData.value = null;
    codeVerificationError.value = 'Gagal menghubungi server untuk validasi kode paket.';
  } finally {
    isCheckingPackageCode.value = false;
  }
}

function clearManualPackageCode() {
  if (form.custom_fields) {
    form.custom_fields['allin_code'] = '';
  }
  isManualCodeVerified.value = false;
  manualCodeSuccessData.value = null;
  codeVerificationError.value = '';
  if (form.package_type === 'Paket All-In') {
    form.package_type = 'Paket Satuan';
  }
}

function onAllInCodeInput() {
  if (isManualCodeVerified.value) {
    isManualCodeVerified.value = false;
    manualCodeSuccessData.value = null;
    if (form.package_type === 'Paket All-In') {
      form.package_type = 'Paket Satuan';
    }
  }
  codeVerificationError.value = '';
}

// New School Self-Registration Form
const newSchoolForm = useForm({
  name: '',
  jenjang: 'SMP',
  status: 'Terdaftar',
  kecamatan: 'Balung',
  npsn: '',
  phone: '',
  alamat: '',
});
const isSubmittingNewSchool = ref(false);
const newSchoolError = ref('');

// Dedicated Paket All-In Form
const allInForm = useForm({
  package_type: 'Paket All-In',
  competition_id: 'all-in',
  school_name: '',
  npsn: '',
  mentor_name: '',
  mentor_wa: '',
  coordinators_count: 1,
  coordinators: [
    { name: '', role: 'Koordinator Utama / Guru Pendamping', wa: '' }
  ],
  notes: '',
  payment_method: 'Transfer Bank BSI',
  payment_proof: null,
});
const isSubmittingAllIn = ref(false);
const allInError = ref('');

function onHubSearchInput() {
  const q = schoolSearchInput.value ? schoolSearchInput.value.trim() : '';
  if (!q || q.length < 2) {
    hubSuggestions.value = [];
    showHubSchoolDropdown.value = false;
    return;
  }

  if (hubSearchTimeout) clearTimeout(hubSearchTimeout);
  hubSearchTimeout = setTimeout(async () => {
    try {
      const res = await fetch(`/api/schools?search=${encodeURIComponent(q)}&limit=10&paginate=0`);
      if (res.ok) {
        const json = await res.json();
        hubSuggestions.value = json.data || [];
        showHubSchoolDropdown.value = true;
      }
    } catch (e) {
      console.error('Hub search error:', e);
    }
  }, 200);
}

async function selectHubSchool(sch) {
  selectedSchool.value = sch;
  schoolSearchInput.value = sch.name;
  showHubSchoolDropdown.value = false;
  form.school_name = sch.name;
  if (!form.custom_fields) form.custom_fields = {};
  if (sch.kecamatan) form.custom_fields['school_district'] = sch.kecamatan;
  if (sch.npsn && sch.npsn !== '-') form.custom_fields['school_npsn'] = sch.npsn;
  if (sch.alamat) form.custom_fields['school_address'] = sch.alamat;
  allInForm.school_name = sch.name;
  allInForm.npsn = sch.npsn || '';
  await checkSchoolStatus(sch.name);
}

function resetHubSchool() {
  selectedSchool.value = null;
  schoolStatusData.value = null;
  schoolSearchInput.value = '';
  showHubSchoolDropdown.value = false;
}

async function checkSchoolStatus(schoolName) {
  if (!schoolName) return;
  isCheckingSchoolStatus.value = true;
  try {
    const res = await fetch(`/api/schools/check-status?school_name=${encodeURIComponent(schoolName)}`);
    if (res.ok) {
      const json = await res.json();
      schoolStatusData.value = json.data;

      // Auto pre-fill mentor/coordinator if available
      if (json.data?.coordinator?.name) {
        if (!form.mentor_name) form.mentor_name = json.data.coordinator.name;
        if (!allInForm.mentor_name) allInForm.mentor_name = json.data.coordinator.name;
      }
      if (json.data?.coordinator?.wa) {
        if (!form.mentor_wa) form.mentor_wa = json.data.coordinator.wa;
        if (!allInForm.mentor_wa) allInForm.mentor_wa = json.data.coordinator.wa;
      }

      // Deteksi otomatis Kode Paket Komplit jika sekolah terverifikasi/terdaftar All-In
      if (json.data?.all_in_reg_code) {
        if (!form.custom_fields) form.custom_fields = {};
        form.custom_fields['allin_code'] = json.data.all_in_reg_code;
        if (json.data.school?.kecamatan && !form.custom_fields['school_district']) {
          form.custom_fields['school_district'] = json.data.school.kecamatan;
        }
        if (json.data.school?.npsn && !form.custom_fields['school_npsn']) {
          form.custom_fields['school_npsn'] = json.data.school.npsn;
        }
        if (json.data.school?.alamat && !form.custom_fields['school_address']) {
          form.custom_fields['school_address'] = json.data.school.alamat;
        }
        form.package_type = 'Paket All-In';
        isManualCodeVerified.value = true;
        manualCodeSuccessData.value = {
          reg_code: json.data.all_in_reg_code,
          school_name: json.data.school?.name || schoolName,
          school_district: json.data.school?.kecamatan || form.custom_fields['school_district'] || 'Kabupaten Jember',
          school_npsn: json.data.school?.npsn || form.custom_fields['school_npsn'] || '-',
          school_address: json.data.school?.alamat || form.custom_fields['school_address'] || '-',
          mentor_name: json.data.coordinator?.name || '',
          mentor_wa: json.data.coordinator?.wa || '',
          payment_status: json.data.all_in_status?.payment_status || 'Terverifikasi',
          registration_status: json.data.all_in_status?.registration_status || 'Terverifikasi',
          is_fully_verified: json.data.can_register_all_competitions_free ?? true,
          message: `Terdeteksi otomatis dari database Paket Komplit untuk ${json.data.school?.name || schoolName}.`
        };
        codeVerificationError.value = '';
      }
    }
  } catch (e) {
    console.error('Status check error:', e);
  } finally {
    isCheckingSchoolStatus.value = false;
  }
}

async function submitNewSchool() {
  if (!newSchoolForm.name.trim()) {
    newSchoolError.value = 'Nama sekolah/madrasah wajib diisi.';
    return;
  }
  isSubmittingNewSchool.value = true;
  newSchoolError.value = '';
  try {
    const res = await fetch('/api/schools/public-register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify(newSchoolForm.data()),
    });
    const json = await res.json();
    if (res.ok && json.status === 'success') {
      showNewSchoolModal.value = false;
      const createdSchool = json.data;
      await selectHubSchool(createdSchool);
      newSchoolForm.reset();
    } else {
      newSchoolError.value = json.message || 'Gagal mendaftarkan sekolah baru.';
    }
  } catch (e) {
    newSchoolError.value = 'Terjadi kendala saat mendaftarkan sekolah baru.';
  } finally {
    isSubmittingNewSchool.value = false;
  }
}

function openAllInModal() {
  if (selectedSchool.value) {
    allInForm.school_name = selectedSchool.value.name;
    allInForm.npsn = selectedSchool.value.npsn || '';
  }
  showAllInModal.value = true;
}

function handleAllInProofChange(event) {
  const file = event.target.files?.[0];
  if (file) {
    allInForm.payment_proof = file;
  }
}

function updateCoordinatorsCount(targetCount) {
  const c = Math.max(1, Math.min(10, parseInt(targetCount, 10) || 1));
  allInForm.coordinators_count = c;
  while (allInForm.coordinators.length < c) {
    allInForm.coordinators.push({
      name: '',
      role: `Guru Pendamping ${allInForm.coordinators.length + 1}`,
      wa: ''
    });
  }
  while (allInForm.coordinators.length > c) {
    allInForm.coordinators.pop();
  }
}

function addCoordinator() {
  if (allInForm.coordinators.length < 10) {
    allInForm.coordinators.push({
      name: '',
      role: `Guru Pendamping ${allInForm.coordinators.length + 1}`,
      wa: ''
    });
    allInForm.coordinators_count = allInForm.coordinators.length;
  }
}

function removeCoordinator(idx) {
  if (allInForm.coordinators.length > 1) {
    allInForm.coordinators.splice(idx, 1);
    allInForm.coordinators_count = allInForm.coordinators.length;
  }
}

function submitAllInRegistration() {
  if (!allInForm.school_name.trim()) {
    allInError.value = 'Nama sekolah wajib diisi.';
    return;
  }
  if (!allInForm.mentor_name.trim()) {
    allInError.value = 'Nama Koordinator / Pembina Utama wajib diisi.';
    return;
  }
  if (!allInForm.mentor_wa.trim()) {
    allInError.value = 'Nomor WhatsApp Koordinator / Pendamping wajib diisi.';
    return;
  }

  isSubmittingAllIn.value = true;
  allInError.value = '';

  allInForm.post('/layanan-registrasi', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: (page) => {
      isSubmittingAllIn.value = false;
      showAllInModal.value = false;
      checkSchoolStatus(allInForm.school_name);
      isSuccess.value = true;
      registeredData.value = page.props.flash?.registered;
      registerModalOpen.value = true;
    },
    onError: (errs) => {
      isSubmittingAllIn.value = false;
      const firstErr = Object.values(errs)[0];
      allInError.value = firstErr || 'Gagal mengirim pendaftaran Paket All-In.';
    }
  });
}

function openRegisterModal(compId = null, isAllIn = false) {
  if (isAllIn) {
    openAllInModal();
    return;
  }

  if (selectedSchool.value) {
    form.school_name = selectedSchool.value.name;
  }
  
  if (schoolStatusData.value?.can_register_all_competitions_free || isManualCodeVerified.value) {
    form.package_type = 'Paket All-In';
  } else {
    form.package_type = 'Paket Satuan';
  }

  if (compId) {
    form.competition_id = compId;
  }

  if (schoolStatusData.value?.coordinator?.name && !form.mentor_name) {
    form.mentor_name = schoolStatusData.value.coordinator.name;
  }
  if (schoolStatusData.value?.coordinator?.wa && !form.mentor_wa) {
    form.mentor_wa = schoolStatusData.value.coordinator.wa;
  }
  if (props.authRegistrant?.email && form.custom_fields && !form.custom_fields['mentor_email']) {
    form.custom_fields['mentor_email'] = props.authRegistrant.email;
  }

  isSuccess.value = false;
  registerModalOpen.value = true;
  nextTick(() => {
    if (modalBodyRef.value) {
      modalBodyRef.value.scrollTop = 0;
    }
  });
}

function closeRegisterModal() {
  registerModalOpen.value = false;
  showSchoolDropdown.value = false;
}

// -----------------------------------------------------------------------------
// School Database Autocomplete (SMP / MTs Kabupaten Jember) in Standard Form Modal
// -----------------------------------------------------------------------------
const schoolSuggestions = ref([]);
const isSearchingSchools = ref(false);
const showSchoolDropdown = ref(false);
let schoolSearchTimeout = null;

function onSchoolInput() {
  const query = form.school_name ? form.school_name.trim() : '';
  if (!query || query.length < 2) {
    schoolSuggestions.value = [];
    showSchoolDropdown.value = false;
    return;
  }

  if (schoolSearchTimeout) clearTimeout(schoolSearchTimeout);
  schoolSearchTimeout = setTimeout(async () => {
    isSearchingSchools.value = true;
    try {
      const res = await fetch(`/api/schools?search=${encodeURIComponent(query)}&limit=6&paginate=0`);
      if (res.ok) {
        const json = await res.json();
        schoolSuggestions.value = json.data || [];
        showSchoolDropdown.value = schoolSuggestions.value.length > 0;
      }
    } catch (e) {
      console.error('Failed to fetch school suggestions:', e);
    } finally {
      isSearchingSchools.value = false;
    }
  }, 250);
}

function onSchoolBlur() {
  setTimeout(() => {
    showSchoolDropdown.value = false;
    if (form.school_name && form.school_name.trim().length >= 3) {
      checkSchoolStatus(form.school_name.trim());
    }
  }, 200);
}

function selectSchool(school) {
  form.school_name = school.name;
  if (!form.custom_fields) form.custom_fields = {};
  if (school.kecamatan) form.custom_fields['school_district'] = school.kecamatan;
  if (school.npsn && school.npsn !== '-') form.custom_fields['school_npsn'] = school.npsn;
  if (school.alamat) form.custom_fields['school_address'] = school.alamat;
  showSchoolDropdown.value = false;
  schoolSuggestions.value = [];
  checkSchoolStatus(school.name);
}

// Helper: Cek apakah pendaftaran bebas biaya dari Paket Komplit (All-In)
const isAllInFree = computed(() => {
  return !!(
    schoolStatusData.value?.can_register_all_competitions_free ||
    (isManualCodeVerified.value && manualCodeSuccessData.value)
  );
});

// Dynamic Fee
const calculatedFee = computed(() => {
  if (isAllInFree.value) {
    return 0; // Free for all competitions under verified All-In!
  }
  if (form.package_type === 'Paket All-In') {
    return Number(props.pricingSettings?.allin_price || 200000);
  }
  const selected = props.competitions?.find(c => c.id === form.competition_id);
  return selected ? Number(selected.fee || 50000) : 50000;
});

const selectedCompFee = computed(() => {
  if (isAllInFree.value) {
    return 0;
  }
  const selected = props.competitions?.find(c => c.id === form.competition_id);
  return selected ? Number(selected.fee || 50000) : 50000;
});

function handleSubmit() {
  if (isAllInFree.value) {
    form.package_type = 'Paket All-In';
  }

  const mergedCustom = { ...(form.custom_fields || {}) };

  // Selalu sertakan 8 identitas utama & paket di custom_fields untuk konsistensi backend
  mergedCustom.school_name = form.school_name;
  mergedCustom.school_district = form.custom_fields?.['school_district'] || '';
  mergedCustom.school_npsn = form.custom_fields?.['school_npsn'] || '';
  mergedCustom.school_address = form.custom_fields?.['school_address'] || '';
  mergedCustom.mentor_name = form.mentor_name;
  mergedCustom.mentor_wa = form.mentor_wa;
  mergedCustom.mentor_email = form.custom_fields?.['mentor_email'] || '';
  mergedCustom.allin_code = form.custom_fields?.['allin_code'] || '';

  if (repeaterField.value) {
    const list = participantsList.value.slice(0, participantCount.value);
    const lines = [];
    for (let i = 0; i < list.length; i++) {
      const p = list[i];
      const pName = (p.name || '').trim() || `Peserta ${i + 1}`;
      const details = [];
      if (p.is_captain) details.push('★ KAPTEN TIM');
      if (p.captain_wa) details.push(`WA Kapten: ${p.captain_wa}`);
      if (p.wa_number) details.push(`WA: ${p.wa_number}`);
      if (p.custom_id) details.push(`ID: ${p.custom_id}`);
      if (p.gender) details.push(p.gender === 'L' ? 'Laki-laki' : 'Perempuan');
      if (p.grade) details.push(p.grade);
      if (p.birth_date) details.push(p.birth_date);
      if (p.nisn) details.push(`NISN: ${p.nisn}`);
      if (p.photo_formal_name) details.push(`Pas Foto: ${p.photo_formal_name}`);
      if (p.freestyle_count) details.push(`Foto Bebas: ${p.freestyle_count} file`);
      lines.push(`${i + 1}. ${pName}${details.length ? ' (' + details.join(', ') + ')' : ''}`);
    }
    form.participants_names = lines.join('\n');
    mergedCustom[repeaterField.value.name] = list;
    mergedCustom[repeaterField.value.name + '_count'] = participantCount.value;
  }

  form.custom_fields = mergedCustom;

  form.post('/layanan-registrasi', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: (page) => {
      isSuccess.value = true;
      registeredData.value = page.props.flash?.registered;
    }
  });
}

// Helper to map competitions to clean, modern vector RemixIcons
function getCompIconClass(comp) {
  if (!comp) return 'ri-trophy-line';
  const id = String(comp.id || '').toLowerCase();
  const title = String(comp.title || '').toLowerCase();
  const category = String(comp.category || '').toLowerCase();

  if (id.includes('voli') || title.includes('voli') || title.includes('volley')) return 'ri-football-line';
  if (id.includes('ml') || id.includes('esport') || title.includes('mobile legends') || title.includes('e-sport')) return 'ri-gamepad-line';
  if (id.includes('mipa') || title.includes('mipa') || title.includes('sains')) return 'ri-microscope-line';
  if (id.includes('ips') || title.includes('ips') || title.includes('sosial')) return 'ri-earth-line';
  if (id.includes('story') || title.includes('story') || title.includes('pidato')) return 'ri-mic-line';
  if (id.includes('banjari') || title.includes('banjari') || title.includes('hadrah')) return 'ri-music-2-line';
  if (id.includes('singer') || title.includes('singer') || title.includes('nyanyi') || title.includes('lagu')) return 'ri-mv-line';
  if (id.includes('tahfidz') || title.includes('tahfidz') || title.includes('quran') || title.includes('mtq')) return 'ri-book-open-line';
  if (category === 'akademik') return 'ri-flask-line';
  if (category === 'olahraga') return 'ri-medal-line';
  if (category === 'seni') return 'ri-palette-line';
  return 'ri-trophy-line';
}

// Helper to determine accurate competition status and quota text
function getCompStatusInfo(comp) {
  if (!comp) {
    return {
      text: 'Ditutup',
      badgeClass: 'status-closed',
      iconClass: 'ri-close-circle-line',
      isClosed: true,
      isManuallyClosed: false,
    };
  }

  // 1. Jika masih dibuka normal
  if (!comp.is_registration_closed && comp.status === 'open') {
    return {
      text: 'Buka Pendaftaran',
      badgeClass: 'status-open',
      iconClass: 'ri-checkbox-circle-line',
      isClosed: false,
      isManuallyClosed: false,
    };
  }

  // 2. Jika Kuota Penuh (kapasitas maksimal telah tercapai)
  if (comp.is_quota_full || (comp.max_quota > 0 && comp.current_quota >= comp.max_quota)) {
    return {
      text: 'Kuota Penuh',
      badgeClass: 'status-closed',
      iconClass: 'ri-close-circle-line',
      isClosed: true,
      isManuallyClosed: false,
    };
  }

  // 3. Jika Melewati Batas Tanggal Penutupan Pendaftaran
  if (comp.is_deadline_passed) {
    return {
      text: 'Waktu Habis',
      badgeClass: 'status-closed',
      iconClass: 'ri-calendar-close-line',
      isClosed: true,
      isManuallyClosed: false,
    };
  }

  // 4. Jika Tanggal Masih Berlaku dan Kuota Masih Tersedia, TETAPI status ditutup secara manual oleh panitia/admin
  return {
    text: comp.quota_status_text || 'Ditutup Panitia',
    badgeClass: 'status-manual',
    iconClass: 'ri-lock-2-line',
    isClosed: true,
    isManuallyClosed: true,
  };
}

function getCompWaLink(comp) {
  if (!comp) return getWaChatUrl(school.value.contact_wa || '083850070767');
  const statusInfo = getCompStatusInfo(comp);
  let msg = `Halo Admin Semarak, saya ingin menanyakan perihal cabang lomba ${comp.title}.`;
  if (statusInfo.isManuallyClosed) {
    msg = `Halo Admin Semarak, perihal pendaftaran cabang lomba ${comp.title} yang saat ini berstatus Ditutup Panitia, apakah masih berpeluang bagi sekolah kami untuk mendaftar?`;
  } else if (comp.is_quota_full) {
    msg = `Halo Admin Semarak, apakah delegasi kami bisa masuk waiting list untuk ${comp.title}?`;
  } else if (comp.is_deadline_passed) {
    msg = `Halo Admin Semarak, apakah pendaftaran ${comp.title} masih bisa menerima pendaftar susulan?`;
  }
  return getWaChatUrl(school.value.contact_wa || '083850070767', msg);
}

function getCompDropdownSuffix(c) {
  const status = getCompStatusInfo(c);
  if (!status.isClosed) return '';
  if (status.isManuallyClosed) return ' — [DITUTUP PANITIA]';
  if (c.is_quota_full) return ' — [KUOTA PENUH]';
  if (c.is_deadline_passed) return ' — [WAKTU HABIS]';
  return ' — [TUTUP]';
}
</script>

<template>
  <PublicLayout>
    <Head :title="'Layanan Registrasi - ' + (school.event_name || 'SEMARAK ALIYAH XIV 2026')" />

    <!-- ==========================================================================
         PAGE BANNER
         ========================================================================== -->
    <section class="page-banner">
      <div class="container">
        <h1 class="page-title">
          Layanan Pendaftaran <span class="text-gradient-gold">{{ school.event_name || 'Semarak Aliyah XIV 2026' }}</span>
        </h1>
        <div class="page-breadcrumb">
          <Link href="/">Beranda</Link>
          <span>/</span>
          <span style="color: var(--accent-gold-light);">Layanan Registrasi</span>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         MAIN CONTENT HUB
         ========================================================================== -->
    <main class="section-padding">
      <div class="container">

        <!-- Smart Registrant Account Banner -->
        <!-- Smart Registrant Account Banner (VIP COMMAND STRIP) -->
        <div v-if="authRegistrant" class="smart-account-card position-relative overflow-hidden">
          <div class="smart-card-top-edge"></div>
          <div class="smart-card-ambient-glow-1"></div>
          <div class="smart-card-ambient-glow-2"></div>

          <!-- Top Executive Profile & Docked Action -->
          <div class="smart-account-header position-relative z-1">
            <div class="smart-account-profile min-w-0">
              <!-- 3D Luxury Avatar with Pulsing Beacon -->
              <div class="auth-banner-avatar-wrap flex-shrink-0">
                <div class="auth-banner-avatar">
                  <span>{{ authRegistrant.name ? authRegistrant.name.charAt(0).toUpperCase() : 'P' }}</span>
                </div>
                <span class="avatar-status-dot-pulse" title="Akun Aktif &amp; Terverifikasi">
                  <span class="dot-core"></span>
                  <span class="dot-ripple"></span>
                </span>
              </div>

              <div class="smart-account-details min-w-0">
                <!-- Badges & Name -->
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                  <h4 class="auth-banner-name mb-0 text-truncate">{{ authRegistrant.name }}</h4>
                  <span v-if="authRegistrant.registrant_role" class="badge-role-pill">
                    <i class="ri-checkbox-circle-fill verified-check-icon me-1"></i> {{ authRegistrant.registrant_role }}
                  </span>
                  <span v-else class="badge-role-pill">
                    <i class="ri-checkbox-circle-fill verified-check-icon me-1"></i> Pendaftar Resmi
                  </span>
                  <span class="badge-connected-pill">
                    <span class="live-pulse-emerald me-1.5"></span>
                    <span>Akun Terhubung</span>
                  </span>
                </div>

                <!-- Subtitle with School Affiliation -->
                <div class="auth-banner-subtitle">
                  <span v-if="authRegistrant.school_name" class="auth-banner-school">
                    <i class="ri-government-line me-1 text-primary"></i>{{ authRegistrant.school_name }}
                    <span class="mx-1.5 opacity-50">•</span>
                  </span>
                  <span>Data pendamping &amp; nomor WhatsApp otomatis tersinkronisasi dari akun Anda.</span>
                </div>
              </div>
            </div>

            <!-- Executive Action Button - Cleanly Docked on Right -->
            <div class="smart-account-action">
              <Link href="/pendaftar/dashboard" class="btn-dashboard-nav-hero">
                <span>Dashboard Pendaftar</span>
                <i class="ri-arrow-right-line ms-2 btn-nav-arrow"></i>
              </Link>
            </div>
          </div>

          <!-- Bottom Micro Feature Badges Status Bar -->
          <div class="smart-account-footer d-flex align-items-center gap-2 flex-wrap position-relative z-1">
            <span class="auth-feature-chip">
              <i class="ri-flashlight-fill text-warning me-1"></i> Auto-Fill Formulir Aktif
            </span>
            <span class="auth-feature-chip">
              <i class="ri-whatsapp-fill text-success me-1"></i> WhatsApp Sync Terhubung
            </span>
            <span class="auth-feature-chip">
              <i class="ri-medal-fill text-info me-1"></i> Akses Prioritas Delegasi
            </span>
          </div>
        </div>

        <div v-else class="smart-guest-card position-relative overflow-hidden">
          <div class="smart-guest-header position-relative z-1">
            <div class="smart-guest-info min-w-0">
              <div class="guest-banner-icon flex-shrink-0">
                <i class="ri-shield-user-fill"></i>
              </div>
              <div class="min-w-0">
                <div class="guest-banner-title">Ingin menyimpan riwayat pendaftaran &amp; memperbarui berkas lomba kapan saja?</div>
                <div class="guest-banner-desc">Masuk atau daftar akun pendaftar untuk mengelola seluruh delegasi sekolah Anda di satu tempat yang aman.</div>
              </div>
            </div>
            <div class="smart-guest-actions">
              <Link :href="`/pendaftar/login?redirect_to=register`" class="btn-guest-login">
                <i class="ri-login-box-line me-1.5"></i>
                <span>Masuk Akun</span>
              </Link>
              <Link :href="`/pendaftar/register?redirect_to=register`" class="btn-guest-register">
                <i class="ri-user-add-line me-1.5"></i>
                <span>Daftar Akun Baru</span>
              </Link>
            </div>
          </div>
        </div>

        <!-- Registration Guidelines Box -->
        <div class="guide-box">
          <h2 class="guide-title">
            <i class="ri-file-list-3-line"></i>
            <span>Petunjuk &amp; Kelengkapan Isian Formulir</span>
          </h2>
          <p class="guide-desc">
            Sebelum melakukan pendaftaran online, mohon siapkan data dan kelengkapan persyaratan berikut:
          </p>

          <div class="guide-list">
            <div class="guide-item">
              <i class="ri-checkbox-circle-fill"></i>
              <span>Nama Asal Sekolah / Madrasah SMP/MTs sederajat.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-fill"></i>
              <span>Nama Lengkap Guru Pembina / Pendamping Resmi.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-fill"></i>
              <span>Nomor WhatsApp Aktif Guru Pendamping (untuk koordinasi teknis).</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-fill"></i>
              <span>Daftar nama lengkap seluruh siswa delegasi beserta kelasnya.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-fill"></i>
              <span>Scan Kartu Pelajar / KTS atau Surat Delegasi Kepala Sekolah.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-fill"></i>
              <span>Pilihan Skema Biaya: Paket Fleksibel Satuan (Rp 50.000 / lomba) atau Paket All-In Komplit (Rp 200.000 / seluruh cabang lomba).</span>
            </div>
          </div>
        </div>
        <!-- ================================================================== -->
        <!-- HUB PENCARIAN & STATUS REGISTRASI SEKOLAH KONTINGEN (COMMAND COCKPIT) -->
        <!-- ================================================================== -->
        <div class="school-hub-card mb-4 position-relative overflow-hidden">
          <div class="school-hub-ambient-glow-1"></div>
          <div class="school-hub-ambient-glow-2"></div>

          <div class="school-hub-header position-relative z-1">
            <div class="school-hub-icon-container">
              <div class="school-hub-icon-aura"></div>
              <div class="school-hub-icon">
                <i class="ri-government-fill"></i>
              </div>
            </div>

            <div class="flex-grow-1 min-w-0">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1.5">
                <span class="school-hub-milestone-tag">
                  <span class="milestone-badge-num">LANGKAH 1 DARI 3</span>
                  <span class="milestone-badge-title">VERIFIKASI SEKOLAH KONTINGEN</span>
                </span>
                <span v-if="selectedSchool" class="school-hub-active-badge">
                  <span class="live-dot-glow-emerald me-1.5"></span>
                  <i class="ri-checkbox-circle-fill me-1"></i> Sekolah Terpilih: {{ selectedSchool.name }}
                </span>
                <span v-else class="school-hub-live-badge">
                  <span class="live-dot-glow-amber me-1.5"></span>
                  <i class="ri-database-2-line me-1"></i> Database Sekolah Terverifikasi
                </span>
              </div>
              <h2 class="school-hub-title">Pencarian &amp; Status Verifikasi Kontingen Sekolah</h2>
              <p class="school-hub-desc">
                Cari dan tentukan sekolah asal Anda untuk memeriksa riwayat keikutsertaan kontingen, status paket pendaftaran (All-In Komplit / Satuan), serta validasi 2 verifikasi panitia.
              </p>
            </div>
          </div>

          <!-- COMMAND SEARCH BAR (INTEGRATED SPOTLIGHT ARCHITECTURE) -->
          <div class="school-search-container position-relative mt-3.5 z-1">
            <div class="school-command-search-frame">
              <div class="command-search-icon">
                <i class="ri-search-line"></i>
              </div>
              <input 
                v-model="schoolSearchInput"
                type="text"
                class="school-command-input"
                placeholder="Ketik nama sekolah atau madrasah asal Anda..."
                @input="onHubSearchInput"
                @focus="onHubSearchInput"
              />
              <button 
                v-if="schoolSearchInput"
                type="button"
                class="btn-command-clear"
                @click="resetHubSchool"
                title="Hapus pencarian"
              >
                <i class="ri-close-circle-fill"></i>
              </button>

              <div class="command-frame-separator d-none d-md-block"></div>

              <!-- Button Register New School if not found -->
              <button 
                type="button" 
                class="btn-new-school-command flex-shrink-0"
                @click="showNewSchoolModal = true"
                title="Daftarkan nama sekolah baru jika belum tersedia di database master"
              >
                <i class="ri-add-circle-fill me-1.5"></i>
                <span>Daftarkan Sekolah Baru</span>
              </button>
            </div>

            <!-- Autocomplete Dropdown List with Glass Elevation -->
            <div 
              v-if="showHubSchoolDropdown && hubSuggestions.length > 0" 
              class="school-hub-dropdown-glass shadow-xl"
            >
              <div class="dropdown-header-bar d-flex align-items-center justify-content-between">
                <span><i class="ri-database-2-line me-1 text-primary"></i> Ditemukan {{ hubSuggestions.length }} sekolah di database master:</span>
                <span class="dropdown-header-sub">Klik salah satu untuk memilih kontingen</span>
              </div>

              <div class="dropdown-items-scroll">
                <div 
                  v-for="s in hubSuggestions" 
                  :key="s.id || s.name"
                  class="school-hub-result-card"
                  @click="selectHubSchool(s)"
                >
                  <div class="result-avatar-box">
                    <i :class="s.jenjang === 'MTs' ? 'ri-book-open-fill' : 'ri-building-fill'"></i>
                  </div>
                  <div class="result-card-info flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                      <span class="school-result-name">{{ s.name }}</span>
                      <span v-if="s.jenjang" class="badge-jenjang">{{ s.jenjang }}</span>
                    </div>
                    <div class="school-result-sub">
                      <span v-if="s.npsn" class="me-3 font-mono"><i class="ri-hashtag text-warning me-0.5"></i>NPSN: {{ s.npsn }}</span>
                      <span><i class="ri-map-pin-line text-danger me-1"></i>Kecamatan {{ s.kecamatan }}, Kabupaten Jember</span>
                    </div>
                  </div>
                  <div class="result-card-action flex-shrink-0">
                    <span class="btn-select-pill">
                      Pilih <i class="ri-arrow-right-line ms-1"></i>
                    </span>
                  </div>
                </div>
              </div>

              <div class="dropdown-footer-callout" @click="showNewSchoolModal = true; showHubSchoolDropdown = false;">
                <div class="d-flex align-items-center justify-content-center gap-2">
                  <i class="ri-question-line fs-15 text-primary"></i>
                  <span>Tidak menemukan sekolah asal Anda? <strong class="text-primary text-decoration-underline">Klik di sini untuk mendaftarkan nama sekolah baru</strong></span>
                </div>
              </div>
            </div>
          </div>

          <!-- SELECTED SCHOOL & ALGORITHMIC STATUS CARD ("KONTINGEN PASSPORT") -->
          <div v-if="selectedSchool" class="selected-school-container mt-4 z-1 position-relative">
            <!-- Active School Profile Header (Passport Card) -->
            <div class="selected-school-passport-card">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="school-passport-avatar">
                    <i class="ri-building-4-fill"></i>
                    <span class="passport-verified-dot" title="Sekolah Terpilih &amp; Terverifikasi">
                      <i class="ri-checkbox-circle-fill"></i>
                    </span>
                  </div>
                  <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                      <h3 class="selected-school-name mb-0">{{ selectedSchool.name }}</h3>
                      <span v-if="selectedSchool.jenjang" class="badge-jenjang">{{ selectedSchool.jenjang }}</span>
                      <span v-if="selectedSchool.npsn" class="badge-npsn font-mono">
                        <i class="ri-hashtag text-warning me-0.5"></i>NPSN: {{ selectedSchool.npsn }}
                      </span>
                    </div>
                    <div class="selected-school-location">
                      <i class="ri-map-pin-2-fill text-danger me-1"></i>
                      <span>Kecamatan {{ selectedSchool.kecamatan }}, Kabupaten Jember, Jawa Timur</span>
                    </div>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn-change-school-hero" @click="resetHubSchool">
                    <i class="ri-refresh-line me-1"></i> Ganti Sekolah
                  </button>
                </div>
              </div>
            </div>

            <!-- LOADING INDICATOR -->
            <div v-if="isCheckingSchoolStatus" class="status-loading-box">
              <i class="ri-loader-4-line ri-spin fs-24 text-warning"></i>
              <span class="status-loading-text">Memeriksa riwayat pendaftaran & status verifikasi kontingen...</span>
            </div>

            <!-- ALGORITHMIC RESULTS -->
            <div v-else-if="schoolStatusData" class="school-status-dashboard mt-3">
              <!-- CASE 1: PAKET ALL-IN TERVERIFIKASI PENUH (VIP FREEDOM!) -->
              <div v-if="schoolStatusData.can_register_all_competitions_free" class="status-box status-allin-verified">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom status-divider">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="status-icon-glow icon-success">
                      <i class="ri-vip-crown-fill text-warning fs-24"></i>
                    </div>
                    <div>
                      <span class="hub-pill-badge badge-allin-gold mb-1">
                        <i class="ri-shield-check-fill me-1"></i> Paket All-In Komplit Terverifikasi Penuh
                      </span>
                      <h4 class="hub-status-heading mb-0">Bebas Mengisi Delegasi di Seluruh Cabang Lomba (Rp 0 / Gratis)</h4>
                    </div>
                  </div>

                  <!-- 2 Badges Verifikasi -->
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="hub-verify-badge badge-verify-success">
                      <i class="ri-checkbox-circle-fill me-1"></i> 1. Verifikasi Pendaftaran: Terverifikasi
                    </span>
                    <span class="hub-verify-badge badge-verify-success">
                      <i class="ri-money-dollar-circle-fill me-1"></i> 2. Verifikasi Pembayaran: Terverifikasi (Lunas)
                    </span>
                  </div>
                </div>

                <p class="status-desc-text mt-3 mb-3">
                  Selamat! Kontingen <strong class="status-strong-text">{{ selectedSchool.name }}</strong> telah menyelesaikan pembayaran Paket All-In dan dinyatakan <strong class="status-strong-text">Sah &amp; Terverifikasi</strong> oleh Panitia Semarak Aliyah XIV. Sekolah Anda bebas mendaftarkan siswa delegasi di seluruh 8 cabang lomba tanpa dipungut biaya pendaftaran lagi (Biaya Rp 0).
                </p>

                <!-- Branch registration status grid -->
                <div class="allin-branches-list">
                  <h5 class="status-branch-header mb-2">
                    <i class="ri-list-check-2 text-primary me-1"></i> Status Pengisian Cabang Lomba Delegasi:
                  </h5>
                  <div class="row g-2">
                    <div v-for="comp in competitions" :key="comp.id" class="col-md-6 col-lg-3">
                      <div 
                        class="branch-card-item h-100 d-flex flex-column justify-content-between"
                        :class="schoolStatusData.registered_competition_ids.includes(comp.id) ? 'branch-card-registered' : 'branch-card-unregistered'"
                      >
                        <div>
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="branch-item-title truncate">{{ comp.title }}</span>
                            <i :class="getCompIconClass(comp)" class="branch-item-icon"></i>
                          </div>
                          <div class="branch-item-status">
                            <span v-if="schoolStatusData.registered_competition_ids.includes(comp.id)" class="text-success fw-bold">
                              <i class="ri-check-line"></i> Sudah Terdaftar
                            </span>
                            <span v-else class="text-muted">
                              Belum Mengisi Delegasi
                            </span>
                          </div>
                        </div>

                        <div class="mt-2 pt-2 branch-item-footer">
                          <button 
                            v-if="!schoolStatusData.registered_competition_ids.includes(comp.id) && !getCompStatusInfo(comp).isClosed"
                            type="button" 
                            class="btn-fill-delegate"
                            @click="openRegisterModal(comp.id)"
                          >
                            <i class="ri-add-line"></i> Isi Delegasi (Rp 0)
                          </button>
                          <span v-else-if="schoolStatusData.registered_competition_ids.includes(comp.id)" class="badge-covered-allin">
                            <i class="ri-checkbox-circle-line me-1"></i> Tercover All-In
                          </span>
                          <span v-else class="badge-comp-closed">
                            {{ getCompStatusInfo(comp).text }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- CASE 2: PAKET ALL-IN MENUNGGU VERIFIKASI PANITIA -->
              <div v-else-if="schoolStatusData.is_all_in" class="status-box status-allin-pending">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom status-divider">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="status-icon-glow icon-warning">
                      <i class="ri-time-line text-warning fs-24"></i>
                    </div>
                    <div>
                      <span class="hub-pill-badge badge-allin-pending mb-1">
                        <i class="ri-hourglass-2-fill me-1"></i> Paket All-In Komplit: Menunggu Verifikasi Panitia
                      </span>
                      <h4 class="hub-status-heading mb-0">Pendaftaran Paket All-In Sedang Dalam Proses Review</h4>
                    </div>
                  </div>

                  <!-- 2 Badges Status -->
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span 
                      class="hub-verify-badge"
                      :class="schoolStatusData.all_in_status.is_registration_verified ? 'badge-verify-success' : 'badge-verify-warning'"
                    >
                      <i :class="schoolStatusData.all_in_status.is_registration_verified ? 'ri-checkbox-circle-fill' : 'ri-time-line'" class="me-1"></i>
                      1. Verifikasi Pendaftaran: {{ schoolStatusData.all_in_status.registration_status || 'Menunggu' }}
                    </span>
                    <span 
                      class="hub-verify-badge"
                      :class="schoolStatusData.all_in_status.is_payment_verified ? 'badge-verify-success' : 'badge-verify-warning'"
                    >
                      <i :class="schoolStatusData.all_in_status.is_payment_verified ? 'ri-checkbox-circle-fill' : 'ri-time-line'" class="me-1"></i>
                      2. Verifikasi Pembayaran: {{ schoolStatusData.all_in_status.payment_status || 'Menunggu' }}
                    </span>
                  </div>
                </div>

                <p class="status-desc-text mt-3 mb-3">
                  Sekolah <strong class="status-strong-text">{{ selectedSchool.name }}</strong> telah mendaftar Paket All-In Komplit (Kode: <code class="hub-code-badge">{{ schoolStatusData.all_in_reg_code }}</code>). Panitia saat ini sedang memverifikasi berkas pendaftaran dan konfirmasi transfer bank.
                  <br />
                  <strong class="status-strong-text">Penting:</strong> Setelah kedua verifikasi disetujui oleh panitia di panel admin, formulir pendaftaran bebas biaya (Rp 0) untuk seluruh cabang lomba akan otomatis aktif untuk sekolah Anda.
                </p>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <a 
                    :href="getWaChatUrl(school.contact_wa || '083850070767', `Halo Panitia Semarak Aliyah, kami dari ${selectedSchool.name} ingin mengonfirmasi verifikasi Paket All-In (Kode: ${schoolStatusData.all_in_reg_code}): `)"
                    target="_blank"
                    class="btn-wa-confirm"
                  >
                    <i class="ri-whatsapp-line"></i> Konfirmasi ke Panitia via WhatsApp
                  </a>
                </div>
              </div>

              <!-- CASE 3: SUDAH DAFTAR BEBERAPA CABANG SATUAN -->
              <div v-else-if="schoolStatusData.has_registrations" class="status-box status-satuan">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 pb-3 border-bottom status-divider">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="status-icon-glow icon-info">
                      <i class="ri-file-list-3-line text-info fs-24"></i>
                    </div>
                    <div>
                      <span class="hub-pill-badge badge-satuan-info mb-1">
                        <i class="ri-price-tag-3-fill me-1"></i> Skema Terdaftar: Paket Fleksibel Satuan
                      </span>
                      <h4 class="hub-status-heading mb-0">Sekolah Ini Sudah Mendaftar {{ schoolStatusData.total_competitions_registered }} Cabang Lomba</h4>
                    </div>
                  </div>

                  <button 
                    type="button" 
                    class="btn-upgrade-allin"
                    @click="openAllInModal"
                  >
                    <i class="ri-vip-crown-fill"></i> Beralih / Upgrade ke Paket All-In Komplit
                  </button>
                </div>

                <div class="mt-3">
                  <div class="status-sub-label mb-2">Daftar Cabang Lomba Terdaftar:</div>
                  <div class="d-flex flex-column gap-2">
                    <div 
                      v-for="reg in schoolStatusData.registered_competitions" 
                      :key="reg.id"
                      class="satuan-item-card d-flex align-items-center justify-content-between flex-wrap gap-2"
                    >
                      <div>
                        <div class="satuan-item-title">{{ reg.competition_title }}</div>
                        <div class="satuan-item-sub">
                          Kode: <span class="satuan-item-code">{{ reg.reg_code }}</span> • Biaya: Rp {{ Number(reg.total_fee).toLocaleString('id-ID') }}
                        </div>
                      </div>

                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span 
                          class="hub-verify-badge"
                          :class="reg.is_registration_verified ? 'badge-verify-success' : 'badge-verify-warning'"
                        >
                          Daftar: {{ reg.registration_status }}
                        </span>
                        <span 
                          class="hub-verify-badge"
                          :class="reg.is_payment_verified ? 'badge-verify-success' : 'badge-verify-warning'"
                        >
                          Bayar: {{ reg.payment_status }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- CASE 4: BELUM ADA PENDAFTARAN SAMA SEKALI -->
              <div v-else class="status-box status-fresh">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="status-icon-glow icon-primary">
                      <i class="ri-sparkling-fill text-primary fs-24"></i>
                    </div>
                    <div>
                      <span class="hub-pill-badge badge-fresh-info mb-1">
                        <i class="ri-information-line me-1"></i> Belum Mendaftarkan Delegasi
                      </span>
                      <h4 class="hub-status-heading mb-0">Sekolah Anda Siap Mendaftarkan Delegasi Kontingen!</h4>
                      <p class="status-desc-text fs-12 mb-0 mt-0.5">
                        Pilih opsi hemat Paket All-In Komplit untuk semua cabang lomba, atau tentukan cabang tertentu secara satuan di bawah.
                      </p>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button 
                      type="button" 
                      class="btn-allin-action"
                      @click="openAllInModal"
                    >
                      <i class="ri-vip-crown-fill"></i>
                      <span>Daftar Paket All-In (Hemat 50%)</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Header -->
        <div class="section-header reg-portal-header" style="margin-top: 60px; margin-bottom: 35px;">
          <span class="section-tag">Pilih Pendaftaran Cabang</span>
          <h2 class="section-title">Portal Pendaftaran Per-Cabang Lomba</h2>
          <p class="section-desc">
            <span v-if="schoolStatusData?.can_register_all_competitions_free" class="text-success fw-bold">
              🌟 Akses Paket All-In Aktif: Anda bebas memilih cabang lomba manapun di bawah ini tanpa biaya (Rp 0).
            </span>
            <span v-else>
              Klik tombol daftar pada cabang kompetisi pilihan Anda untuk membuka formulir pendaftaran kontingen.
            </span>
          </p>
        </div>

        <!-- Registration Cards Grid for All Competitions -->
        <div class="reg-hub-grid">
          <div 
            v-for="comp in competitions" 
            :key="comp.id" 
            class="reg-card"
            :class="{ 'card-allin-free': schoolStatusData?.can_register_all_competitions_free }"
          >
            <div>
              <div class="reg-card-top">
                <div class="reg-card-icon">
                  <i :class="getCompIconClass(comp)"></i>
                </div>
                <div>
                  <h3 class="reg-card-title">{{ comp.title }}</h3>
                  <span 
                    :class="[
                      'comp-status-badge',
                      getCompStatusInfo(comp).badgeClass
                    ]"
                  >
                    <i :class="getCompStatusInfo(comp).iconClass"></i>
                    {{ getCompStatusInfo(comp).text }}
                  </span>
                </div>
              </div>

              <div class="reg-info-box">
                {{ comp.description }}
              </div>
            </div>

            <div>
              <div style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                  <span>Biaya : </span>
                  <template v-if="schoolStatusData?.can_register_all_competitions_free">
                    <span class="text-decoration-line-through text-muted fs-11 me-1">Rp {{ Number(comp.fee || 50000).toLocaleString('id-ID') }}</span>
                    <strong class="text-success" style="font-weight: 800;">Gratis (Rp 0)</strong>
                  </template>
                  <template v-else>
                    <strong style="color: var(--theme-orange); font-weight: 700;">Rp {{ Number(comp.fee || 50000).toLocaleString('id-ID') }}</strong>
                  </template>
                </div>
                <span>Terisi : <strong style="color: var(--text-main); font-weight: 700;">{{ comp.current_quota }} / {{ comp.max_quota }}</strong></span>
              </div>

              <!-- Button Action -->
              <div v-if="!getCompStatusInfo(comp).isClosed">
                <button 
                  type="button" 
                  class="btn-card-register" 
                  style="width: 100%;"
                  @click="openRegisterModal(comp.id)"
                >
                  <i :class="schoolStatusData?.can_register_all_competitions_free ? 'ri-gift-line' : 'ri-flashlight-line'"></i>
                  <span>{{ schoolStatusData?.can_register_all_competitions_free ? 'Isi Delegasi (Gratis All-In)' : (schoolStatusData?.registered_competition_ids?.includes(comp.id) ? 'Daftar Tim Tambahan' : 'Daftar Sekarang') }}</span>
                </button>
              </div>
              <a 
                v-else
                :href="getCompWaLink(comp)" 
                target="_blank" 
                rel="noopener" 
                class="btn-card-detail" 
                style="text-align: center; display: block;"
                :title="getCompStatusInfo(comp).isManuallyClosed ? 'Pendaftaran ditutup oleh panitia. Klik untuk bertanya via WhatsApp' : 'Masuk daftar tunggu via WhatsApp'"
              >
                <i :class="getCompStatusInfo(comp).isManuallyClosed ? 'ri-whatsapp-line' : 'ri-time-line'"></i>
                <span>{{ getCompStatusInfo(comp).isManuallyClosed ? 'Hubungi Panitia (Ditutup)' : 'Hubungi Panitia (Waiting List)' }}</span>
              </a>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- ==========================================================================
         REGISTRATION MODAL FORM (INTERACTIVE POPUP)
         ========================================================================== -->
    <div 
      v-if="registerModalOpen" 
      class="modal-overlay reg-modal-overlay" 
      role="dialog" 
      aria-modal="true" 
      aria-labelledby="reg-modal-title"
      @click.self="closeRegisterModal"
    >
      <div class="modal-card reg-modal-card">
        <!-- SUCCESS STATE DIALOG -->
        <div v-if="isSuccess" class="reg-success-view">
          <div class="reg-success-icon-wrap">
            <i class="ri-checkbox-circle-fill"></i>
          </div>
          <h3 class="reg-success-title">Pendaftaran Berhasil Dikirim!</h3>
          <p class="reg-success-desc">
            Terima kasih telah mendaftarkan delegasi sekolah di <strong>{{ school.event_name || 'Semarak Aliyah XIV 2026' }}</strong>. Data telah tersimpan di sistem panitia dan siap diverifikasi.
          </p>

          <div v-if="registeredData" class="reg-success-summary-box">
            <div class="summary-row">
              <span class="summary-label">Kode Registrasi</span>
              <span class="summary-val code-val">{{ registeredData.reg_code }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Asal Sekolah</span>
              <span class="summary-val">{{ registeredData.school_name }}</span>
            </div>
            <div class="summary-row">
              <span class="summary-label">Cabang & Paket</span>
              <span class="summary-val highlight-val">{{ registeredData.competition_title }} ({{ registeredData.package_type }})</span>
            </div>
            <div class="summary-row total-row">
              <span class="summary-label">Total Biaya Pendaftaran</span>
              <span class="summary-val fee-val">Rp {{ Number(registeredData.total_fee).toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <div class="reg-success-actions">
            <button type="button" class="btn-cancel" @click="closeRegisterModal">
              <i class="ri-check-line"></i> Selesai & Tutup
            </button>
            <button type="button" class="btn-submit" @click="isSuccess = false; form.reset()">
              <i class="ri-add-line"></i> Daftar Lomba Lain
            </button>
          </div>
        </div>

        <!-- FORM STATE (STICKY HEADER + SCROLLABLE BODY + STICKY FOOTER) -->
        <template v-else>
          <!-- 1. STICKY MODAL HEADER -->
          <div class="reg-modal-header">
            <div class="reg-modal-header-icon">
              <i :class="getCompIconClass(activeComp)"></i>
            </div>
            <div class="reg-modal-header-info">
              <div class="reg-modal-header-tags">
                <span class="reg-badge-tag tag-pkg">
                  <i class="ri-price-tag-3-fill"></i> {{ form.package_type }}
                </span>
                <span v-if="activeComp?.category" class="reg-badge-tag tag-cat">
                  {{ activeComp.category }}
                </span>
                <span v-if="teamInfo.isTeam" class="reg-badge-tag tag-team">
                  <i class="ri-team-line"></i> Tim Delegasi
                </span>
              </div>
              <h3 id="reg-modal-title" class="reg-modal-title">
                {{ activeComp?.form_settings?.form_title || activeComp?.title || ('Formulir Registrasi ' + (school.event_name || 'Semarak Aliyah 2026')) }}
              </h3>
              <p class="reg-modal-subtitle">
                {{ activeComp?.form_settings?.form_subtitle || 'Lengkapi data pendaftaran delegasi di bawah ini untuk diverifikasi oleh panitia lomba.' }}
              </p>
            </div>
            <button type="button" class="reg-modal-close" @click="closeRegisterModal" aria-label="Tutup Formulir">
              <i class="ri-close-line"></i>
            </button>
          </div>

          <!-- 2. SCROLLABLE FORM BODY -->
          <div class="reg-modal-body" ref="modalBodyRef">
            <form id="registration-form" @submit.prevent="handleSubmit">
              
              <!-- CARD 1: PILIHAN PAKET & CABANG LOMBA -->
              <div class="reg-section-card">
                <div class="reg-section-header">
                  <span class="reg-step-pill">1</span>
                  <div>
                    <h4 class="reg-section-title">Pilihan Paket & Cabang Lomba</h4>
                    <p class="reg-section-desc">Pilih skema pendaftaran satuan per lomba atau paket komplit all-in.</p>
                  </div>
                </div>

                <div class="reg-package-grid">
                  <!-- Paket Satuan -->
                  <label 
                    class="reg-package-item" 
                    :class="{ 'is-selected': form.package_type === 'Paket Satuan' }"
                  >
                    <div class="reg-pkg-radio">
                      <input type="radio" value="Paket Satuan" v-model="form.package_type" />
                      <span class="custom-radio-dot"></span>
                    </div>
                    <div class="reg-pkg-content">
                      <div class="reg-pkg-name">{{ pricingSettings?.satuan_name || 'Paket Satuan' }}</div>
                      <div class="reg-pkg-price satuan-price">Rp {{ selectedCompFee.toLocaleString('id-ID') }} <span class="price-sub">/ Cabang</span></div>
                      <div class="reg-pkg-note">Pendaftaran untuk 1 cabang lomba pilihan</div>
                    </div>
                  </label>

                  <!-- Paket All-In -->
                  <label 
                    class="reg-package-item" 
                    :class="{ 'is-selected': form.package_type === 'Paket All-In' }"
                  >
                    <div class="reg-pkg-radio">
                      <input type="radio" value="Paket All-In" v-model="form.package_type" />
                      <span class="custom-radio-dot"></span>
                    </div>
                    <div class="reg-pkg-content">
                      <div class="reg-pkg-name">{{ pricingSettings?.allin_name || 'Paket All-In Komplit' }}</div>
                      <div class="reg-pkg-price allin-price">Rp {{ Number(pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }} <span class="price-sub">Semua Lomba</span></div>
                      <div class="reg-pkg-note">Bebas ikuti seluruh cabang lomba resmi</div>
                    </div>
                  </label>
                </div>

                <!-- Dropdown Cabang Lomba -->
                <div class="form-group" style="margin-top: 14px; margin-bottom: 0;">
                  <label class="form-label" for="reg-competition">
                    {{ form.package_type === 'Paket Satuan' ? 'Pilih Cabang Lomba Utama *' : 'Cabang Lomba yang Ingin Didahulukan' }}
                  </label>
                  <CustomSelect 
                    id="reg-competition" 
                    v-model="form.competition_id"
                    size="md"
                    :disabled="form.package_type === 'Paket All-In'"
                    placeholder="Pilih Cabang Lomba..."
                    searchable
                    search-placeholder="Ketik untuk mencari cabang lomba..."
                  >
                    <option 
                      v-for="c in competitions" 
                      :key="c.id" 
                      :value="c.id"
                      :disabled="getCompStatusInfo(c).isClosed"
                    >
                      {{ c.title }} (Rp {{ Number(c.fee || 50000).toLocaleString('id-ID') }}){{ getCompDropdownSuffix(c) }}
                    </option>
                  </CustomSelect>
                </div>
              </div>

              <!-- CARD 2: ASAL SEKOLAH & KONTAK PEMBINA -->
              <div class="reg-section-card">
                <div class="reg-section-header">
                  <span class="reg-step-pill">2</span>
                  <div>
                    <h4 class="reg-section-title">Data Sekolah & Kontak Pembina</h4>
                    <p class="reg-section-desc">Identitas sekolah delegasi dan narahubung guru pendamping resmi.</p>
                  </div>
                </div>

                <!-- 1. KODE PAKET KOMPLIT (ALL-IN) INTERACTIVE CARD (Diletakkan di atas agar pendaftar dapat mengecek kode paket lebih dulu) -->
                <div class="package-code-card mb-3">
                  <div class="package-code-header">
                    <div class="package-code-title-wrap">
                      <div class="package-code-icon">
                        <i class="ri-vip-crown-line"></i>
                      </div>
                      <div>
                        <div class="package-code-title">
                          <span>Punya Kode Paket Komplit (All-In)?</span>
                          <span class="package-code-badge">Bebas Biaya & Otomatis</span>
                        </div>
                        <p class="package-code-desc">
                          Jika sekolah mendaftar Paket Komplit, masukkan kodenya di sini. Data sekolah terisi otomatis dan pendaftaran cabang ini <strong>Bebas Biaya (Rp 0)</strong> tanpa perlu mengisi ulang data sekolah.
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class="package-code-body">
                    <div class="package-code-input-row">
                      <div class="input-with-icon" style="flex: 1;">
                        <i class="ri-qr-code-line input-icon"></i>
                        <input 
                          type="text" 
                          id="reg-allin-code"
                          v-model="form.custom_fields['allin_code']"
                          @input="onAllInCodeInput"
                          class="form-control package-code-input font-mono" 
                          placeholder="Masukkan kode, misal: SA26-36C662" 
                          autocomplete="off"
                        />
                      </div>
                      <button 
                        type="button" 
                        class="btn-verify-code"
                        :disabled="isCheckingPackageCode || !form.custom_fields?.['allin_code']"
                        @click="verifyPackageCodeManual"
                      >
                        <i v-if="isCheckingPackageCode" class="ri-loader-4-line ri-spin"></i>
                        <i v-else class="ri-checkbox-circle-line"></i>
                        <span>{{ isCheckingPackageCode ? 'Mengecek...' : 'Cek Kode' }}</span>
                      </button>
                      <button 
                        v-if="isManualCodeVerified || form.custom_fields?.['allin_code']"
                        type="button" 
                        class="btn-clear-code"
                        @click="clearManualPackageCode"
                        title="Hapus / ganti kode"
                      >
                        <i class="ri-close-line"></i>
                      </button>
                    </div>

                    <!-- Error State Alert -->
                    <div v-if="codeVerificationError" class="code-error-alert">
                      <i class="ri-error-warning-fill text-danger me-2"></i>
                      <span>{{ codeVerificationError }}</span>
                    </div>

                    <!-- Info Note bila belum diverifikasi -->
                    <div v-else-if="!isManualCodeVerified" class="package-code-help">
                      <i class="ri-information-line"></i>
                      <span>
                        Kode paket ini opsional. Jika sekolah mendaftar paket satuan per cabang lomba, silakan langsung mengisi data sekolah di bawah.
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 2. KONDISI A: JIKA KODE PAKET KOMPLIT VALID & TERVERIFIKASI -->
                <!-- Pendaftar TIDAK PERLU input informasi sekolah lagi! Tampilkan summary sekolah terverifikasi -->
                <div v-if="isManualCodeVerified && manualCodeSuccessData" class="verified-school-summary-card">
                  <div class="verified-school-header">
                    <div class="verified-school-badge-wrap">
                      <div class="verified-school-icon">
                        <i class="ri-shield-check-fill"></i>
                      </div>
                      <div>
                        <div class="verified-school-title">
                          <span>Data Sekolah Terverifikasi (Paket Komplit)</span>
                          <span class="badge-free-pill">Bebas Biaya (Rp 0)</span>
                        </div>
                        <div class="verified-school-subtitle">
                          Data sekolah di bawah ini terisi otomatis dari Paket Komplit. Anda tidak perlu menginput data sekolah lagi. Silakan lengkapi kontak guru pendamping cabang lomba ini di bawah.
                        </div>
                      </div>
                    </div>
                    <button 
                      type="button" 
                      class="btn-unlink-school" 
                      @click="clearManualPackageCode"
                      title="Klik untuk mengganti sekolah atau membatalkan kode paket"
                    >
                      <i class="ri-edit-line me-1"></i>
                      <span>Ganti Sekolah / Ubah Kode</span>
                    </button>
                  </div>

                  <div class="verified-school-details-grid">
                    <div class="detail-box school-main">
                      <div class="detail-label"><i class="ri-building-line me-1"></i> Nama Asal Sekolah / Madrasah</div>
                      <div class="detail-val school-name-val">{{ manualCodeSuccessData.school_name || form.school_name }}</div>
                    </div>
                    <div class="detail-box">
                      <div class="detail-label"><i class="ri-map-pin-2-line me-1"></i> Kecamatan</div>
                      <div class="detail-val">{{ manualCodeSuccessData.school_district || form.custom_fields['school_district'] || 'Kabupaten Jember' }}</div>
                    </div>
                    <div class="detail-box">
                      <div class="detail-label"><i class="ri-numbers-line me-1"></i> NPSN</div>
                      <div class="detail-val">{{ manualCodeSuccessData.school_npsn || form.custom_fields['school_npsn'] || '-' }}</div>
                    </div>
                    <div class="detail-box school-address-box">
                      <div class="detail-label"><i class="ri-map-pin-line me-1"></i> Alamat Sekolah / Madrasah</div>
                      <div class="detail-val">{{ manualCodeSuccessData.school_address || form.custom_fields['school_address'] || '-' }}</div>
                    </div>
                    <div class="detail-box">
                      <div class="detail-label"><i class="ri-coupon-3-line me-1"></i> Kode Registrasi Paket</div>
                      <div class="detail-val font-mono text-success fw-bold">{{ manualCodeSuccessData.reg_code }}</div>
                    </div>
                    <div class="detail-box" v-if="manualCodeSuccessData.payment_status">
                      <div class="detail-label"><i class="ri-checkbox-circle-line me-1"></i> Status Pembayaran Paket</div>
                      <div class="detail-val text-success fw-bold">{{ manualCodeSuccessData.payment_status }}</div>
                    </div>
                  </div>
                </div>

                <!-- 2. KONDISI B: JIKA KODE TIDAK TERVERIFIKASI (MANUAL INPUT SEKOLAH) -->
                <div v-else class="manual-school-inputs-group">
                  <!-- Autocomplete Nama Sekolah -->
                  <div class="form-group" style="position: relative;">
                    <label class="form-label" for="reg-sekolah">
                      <span>Nama Asal Sekolah / Madrasah *</span>
                      <span v-if="isSearchingSchools" class="searching-badge">
                        <i class="ri-loader-4-line ri-spin"></i> Mencari database SMP/MTs...
                      </span>
                    </label>
                    <div class="input-with-icon">
                      <i class="ri-building-line input-icon"></i>
                      <input 
                        type="text" 
                        id="reg-sekolah" 
                        v-model="form.school_name"
                        class="form-control" 
                        placeholder="Ketik nama SMP / MTs (Contoh: SMPN 1 Balung atau MTs Wahid Hasyim)" 
                        required
                        autocomplete="off"
                        @input="onSchoolInput"
                        @focus="onSchoolInput"
                        @blur="onSchoolBlur"
                      />
                    </div>

                    <!-- Autocomplete Floating Dropdown -->
                    <div 
                      v-if="showSchoolDropdown && schoolSuggestions.length > 0" 
                      class="school-autocomplete-dropdown"
                    >
                      <div 
                        v-for="s in schoolSuggestions" 
                        :key="s.id"
                        class="school-suggestion-item"
                        @click="selectSchool(s)"
                      >
                        <div class="school-info">
                          <div class="school-name">{{ s.name }}</div>
                          <div class="school-meta">
                            <span>Kecamatan {{ s.kecamatan }}</span>
                            <span v-if="s.npsn && s.npsn !== '-'"> • NPSN: {{ s.npsn }}</span>
                          </div>
                        </div>
                        <span 
                          class="school-tag"
                          :class="s.jenjang === 'MTs' ? 'tag-mts' : 'tag-smp'"
                        >
                          {{ s.jenjang }} {{ s.status }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Grid Kecamatan Sekolah & NPSN -->
                  <div class="reg-grid-2">
                    <div class="form-group">
                      <label class="form-label" for="reg-kecamatan">
                        Kecamatan Asal Sekolah *
                      </label>
                      <CustomSelect
                        id="reg-kecamatan"
                        v-model="form.custom_fields['school_district']"
                        :options="kecamatanList"
                        size="md"
                        placeholder="Pilih Kecamatan Asal Sekolah..."
                        searchable
                        search-placeholder="Cari kecamatan..."
                      />
                    </div>

                    <div class="form-group">
                      <label class="form-label" for="reg-npsn">
                        Nomor Pokok Sekolah Nasional (NPSN) <span class="optional-tag">(Opsional)</span>
                      </label>
                      <div class="input-with-icon">
                        <i class="ri-numbers-line input-icon"></i>
                        <input 
                          type="text" 
                          id="reg-npsn" 
                          v-model="form.custom_fields['school_npsn']"
                          class="form-control" 
                          placeholder="Contoh: 20524589" 
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Alamat Sekolah / Madrasah -->
                  <div class="form-group">
                    <label class="form-label" for="reg-alamat">Alamat Sekolah / Madrasah *</label>
                    <div class="input-with-icon input-textarea-wrapper">
                      <i class="ri-map-pin-line input-icon" style="top: 14px;"></i>
                      <textarea 
                        id="reg-alamat" 
                        v-model="form.custom_fields['school_address']"
                        class="form-control" 
                        rows="2"
                        placeholder="Contoh: Jl. Raya Puger No. 20, Balung Kulon, Kec. Balung" 
                        required
                      ></textarea>
                    </div>
                  </div>
                </div>

                <!-- 3. DATA KONTAK GURU PENDAMPING / PEMBINA (DIISI OLEH PENDAFTAR) -->
                <div class="mentor-section-divider">
                  <div class="mentor-section-title">
                    <i class="ri-user-star-line me-1.5 text-warning"></i>
                    <span>Kontak Guru Pendamping / Pembina Cabang Lomba</span>
                  </div>
                  <div class="mentor-section-subtitle">
                    Lengkapi identitas guru pendamping yang bertanggung jawab mendampingi siswa pada cabang lomba ini.
                  </div>
                </div>

                <!-- Grid Pembina & WhatsApp -->
                <div class="reg-grid-2">
                  <div class="form-group">
                    <label class="form-label" for="reg-pendamping">Nama Guru Pendamping / Pembina *</label>
                    <div class="input-with-icon">
                      <i class="ri-user-star-line input-icon"></i>
                      <input 
                        type="text" 
                        id="reg-pendamping" 
                        v-model="form.mentor_name"
                        class="form-control" 
                        placeholder="Contoh: Bpk. Ahmad Hidayat, S.Pd" 
                        required
                      />
                    </div>
                  </div>

                  <div class="form-group">
                    <label class="form-label" for="reg-wa">Nomor WhatsApp Pembina (Aktif) *</label>
                    <div class="input-with-icon">
                      <i class="ri-whatsapp-line input-icon wa-icon"></i>
                      <input 
                        type="tel" 
                        inputmode="numeric"
                        pattern="[0-9]*"
                        id="reg-wa" 
                        v-model="form.mentor_wa"
                        @input="form.mentor_wa = form.mentor_wa.replace(/\D/g, '')"
                        class="form-control" 
                        placeholder="Contoh: 081234567890" 
                        required
                      />
                    </div>
                    <div class="field-hint">Hanya angka aktif untuk koordinasi teknis dan TM.</div>
                  </div>
                </div>

                <!-- Email Pendamping -->
                <div class="form-group">
                  <label class="form-label" for="reg-email">Email Pendamping *</label>
                  <div class="input-with-icon">
                    <i class="ri-mail-line input-icon"></i>
                    <input 
                      type="email" 
                      id="reg-email" 
                      v-model="form.custom_fields['mentor_email']"
                      class="form-control" 
                      placeholder="Contoh: pendamping@sekolah.sch.id" 
                      required
                    />
                  </div>
                  <div class="field-hint">Alamat email aktif untuk tanda terima dan dokumen lomba.</div>
                </div>
              </div>

              <!-- CARD 3: BIODATA ANGGOTA DELEGASI -->
              <div class="reg-section-card">
                <div class="reg-section-header">
                  <span class="reg-step-pill">3</span>
                  <div>
                    <h4 class="reg-section-title">Biodata Peserta Delegasi</h4>
                    <p class="reg-section-desc">Roster peserta didik yang mewakili sekolah pada cabang lomba ini.</p>
                  </div>
                </div>

                <!-- DYNAMIC PARTICIPANT REPEATER / ROSTER -->
                <div v-if="repeaterField">
                  <!-- Repeater Toolbar & Quota Counter -->
                  <div class="reg-roster-toolbar">
                    <div class="toolbar-info">
                      <div class="toolbar-title">
                        <i class="ri-team-line"></i>
                        <span>{{ repeaterField.label || 'Jumlah Anggota Delegasi yang Didaftarkan' }} *</span>
                      </div>
                      <div class="toolbar-quota-desc">
                        <span>Kapasitas: <strong>Maksimal {{ teamInfo.max }} Siswa</strong></span>
                        <span v-if="teamInfo.min > 1"> (Minimal {{ teamInfo.min }} Siswa)</span>
                        <span v-if="teamInfo.isTeam && teamInfo.main" class="core-tag">
                          • {{ teamInfo.main }} Inti<template v-if="teamInfo.reserve"> + {{ teamInfo.reserve }} Cadangan</template>
                        </span>
                      </div>
                      <div v-if="teamInfo.rule" class="toolbar-rule-alert">
                        <i class="ri-information-line"></i>
                        <span>Aturan Cabang: {{ teamInfo.rule }}</span>
                      </div>
                    </div>

                    <!-- Stepper Counter -->
                    <div class="roster-stepper">
                      <button 
                        type="button" 
                        class="stepper-btn"
                        :disabled="participantCount <= teamInfo.min"
                        @click="adjustCount(-1)"
                        title="Kurangi kuota siswa"
                      >
                        <i class="ri-subtract-line"></i>
                      </button>
                      <div class="stepper-display">
                        {{ participantCount }} <span class="stepper-unit">Siswa</span>
                      </div>
                      <button 
                        type="button" 
                        class="stepper-btn"
                        :disabled="participantCount >= teamInfo.max"
                        @click="adjustCount(1)"
                        title="Tambah kuota siswa"
                      >
                        <i class="ri-add-line"></i>
                      </button>
                    </div>
                  </div>

                  <!-- Student Roster Cards List -->
                  <div class="reg-students-stack">
                    <div 
                      v-for="pIdx in participantCount" 
                      :key="pIdx"
                      class="reg-student-card"
                    >
                      <!-- Student Card Header -->
                      <div class="student-card-header">
                        <div class="student-header-left">
                          <span class="student-card-idx">
                            <i class="ri-user-line"></i> Siswa #{{ pIdx }}
                          </span>
                          
                          <!-- Inti vs Cadangan Badge -->
                          <span 
                            v-if="teamInfo.isTeam"
                            class="team-role-badge"
                            :class="pIdx <= teamInfo.main ? 'role-inti' : 'role-cadangan'"
                          >
                            <i :class="pIdx <= teamInfo.main ? 'ri-shield-user-line' : 'ri-user-shared-line'"></i>
                            {{ pIdx <= teamInfo.main ? `Pemain Inti #${pIdx}` : `Cadangan #${pIdx - teamInfo.main}` }}
                          </span>

                          <!-- Kapten Badge -->
                          <span v-if="participantsList[pIdx - 1].is_captain" class="captain-badge">
                            <i class="ri-vip-crown-fill"></i> KAPTEN TIM
                          </span>
                        </div>

                        <div class="student-header-right">
                          <label 
                            v-if="repeaterField.sub_fields?.captain_option?.active" 
                            class="captain-toggle-label"
                            :class="{ 'is-active': participantsList[pIdx - 1].is_captain }"
                            title="Tandai siswa ini sebagai kapten tim"
                          >
                            <input 
                              type="radio" 
                              name="reg_captain_sel" 
                              :checked="participantsList[pIdx - 1].is_captain" 
                              @change="setCaptain(pIdx - 1)" 
                            />
                            <span>⭐ Jadikan Kapten</span>
                          </label>
                        </div>
                      </div>

                      <!-- Student Fields Grid -->
                      <div class="student-card-body">
                        <!-- Nama Lengkap -->
                        <div class="form-group">
                          <label class="form-label">Nama Lengkap Siswa *</label>
                          <input 
                            type="text" 
                            v-model="participantsList[pIdx - 1].name"
                            class="form-control" 
                            :placeholder="`Nama Lengkap Peserta ${pIdx}`"
                            required
                          />
                        </div>

                        <!-- Row: Jenis Kelamin & Kelas -->
                        <div class="reg-grid-2">
                          <div v-if="repeaterField.sub_fields?.gender?.active !== false" class="form-group">
                            <label class="form-label">Jenis Kelamin</label>
                            <div class="gender-segmented-wrap">
                              <label 
                                class="gender-segmented-btn" 
                                :class="{ 'is-selected': participantsList[pIdx - 1].gender === 'L' }"
                              >
                                <input type="radio" :name="`reg_gender_${pIdx}`" value="L" v-model="participantsList[pIdx - 1].gender" />
                                <span>Laki-laki</span>
                              </label>
                              <label 
                                class="gender-segmented-btn" 
                                :class="{ 'is-selected': participantsList[pIdx - 1].gender === 'P' }"
                              >
                                <input type="radio" :name="`reg_gender_${pIdx}`" value="P" v-model="participantsList[pIdx - 1].gender" />
                                <span>Perempuan</span>
                              </label>
                            </div>
                          </div>

                          <div v-if="repeaterField.sub_fields?.grade?.active !== false" class="form-group">
                            <label class="form-label">Kelas / Tingkat</label>
                            <CustomSelect v-model="participantsList[pIdx - 1].grade" size="md">
                              <option value="Kelas 7 SMP/MTs">Kelas 7 SMP/MTs</option>
                              <option value="Kelas 8 SMP/MTs">Kelas 8 SMP/MTs</option>
                              <option value="Kelas 9 SMP/MTs">Kelas 9 SMP/MTs</option>
                            </CustomSelect>
                          </div>
                        </div>

                        <!-- Row: Tanggal Lahir (dd/mm/yyyy Flatpickr) & NISN -->
                        <div class="reg-grid-2">
                          <div v-if="repeaterField.sub_fields?.birth_date?.active !== false" class="form-group">
                            <label class="form-label">Tanggal Lahir (dd/mm/yyyy)</label>
                            <IndoDatePicker 
                              v-model="participantsList[pIdx - 1].birth_date"
                              placeholder="dd/mm/yyyy"
                              inputClass="form-control" 
                              iconClass="ri-calendar-line text-amber-500"
                            />
                          </div>
                          
                          <div v-if="repeaterField.sub_fields?.nisn?.active !== false" class="form-group">
                            <label class="form-label">NISN (Opsional)</label>
                            <input 
                              type="text" 
                              v-model="participantsList[pIdx - 1].nisn"
                              class="form-control" 
                              placeholder="Nomor Induk Siswa Nasional"
                            />
                          </div>
                        </div>

                        <!-- ID Siswa (Custom ID) -->
                        <div v-if="repeaterField.sub_fields?.custom_id?.active" class="form-group">
                          <label class="form-label">
                            <span>{{ repeaterField.sub_fields.custom_id.label || 'ID Siswa / No. Identitas' }}</span>
                            <span class="field-type-pill">Field Text</span>
                          </label>
                          <input 
                            type="text" 
                            v-model="participantsList[pIdx - 1].custom_id"
                            class="form-control font-mono" 
                            placeholder="Contoh: ID-0912 / User ID Game / No. Punggung"
                          />
                        </div>

                        <!-- Nomor WhatsApp Siswa -->
                        <div v-if="repeaterField.sub_fields?.wa_number?.active" class="form-group">
                          <label class="form-label">
                            <i class="ri-whatsapp-line text-emerald-500"></i>
                            <span>Nomor WhatsApp Siswa</span>
                          </label>
                          <input 
                            type="tel" 
                            inputmode="numeric"
                            pattern="[0-9]*"
                            v-model="participantsList[pIdx - 1].wa_number"
                            @input="participantsList[pIdx - 1].wa_number = (participantsList[pIdx - 1].wa_number || '').replace(/\D/g, '')"
                            class="form-control font-mono" 
                            placeholder="Contoh: 081234567890"
                          />
                        </div>

                        <!-- WA Khusus Kapten Tim (Muncul jika siswa ditandai sebagai kapten) -->
                        <div 
                          v-if="repeaterField.sub_fields?.captain_option?.active && participantsList[pIdx - 1].is_captain" 
                          class="captain-wa-alert-card"
                        >
                          <label class="captain-wa-label">
                            <i class="ri-whatsapp-fill"></i>
                            <span>Nomor WhatsApp Khusus Kapten Tim (Field Angka) *</span>
                          </label>
                          <input 
                            type="tel" 
                            inputmode="numeric"
                            pattern="[0-9]*"
                            v-model="participantsList[pIdx - 1].captain_wa"
                            @input="participantsList[pIdx - 1].captain_wa = (participantsList[pIdx - 1].captain_wa || '').replace(/\D/g, '')"
                            class="form-control captain-input font-mono" 
                            placeholder="Contoh: 081234567890 (WA Kapten untuk koordinasi & Technical Meeting)"
                            required
                          />
                          <div class="captain-wa-hint">
                            Nomor WA ini digunakan panitia lomba untuk koordinasi TM & grup WhatsApp resmi seluruh kapten tim.
                          </div>
                        </div>

                        <!-- Upload Pas Foto Formal Siswa -->
                        <div v-if="repeaterField.sub_fields?.photo_formal?.active" class="form-group">
                          <label class="form-label">
                            <span><i class="ri-camera-line text-sky-500"></i> Pas Foto Formal Siswa</span>
                            <span class="file-spec-tag">1 File • Format Foto</span>
                          </label>
                          <div class="reg-file-dropzone-sm">
                            <input 
                              type="file" 
                              accept="image/*"
                              class="form-control" 
                              @change="e => participantsList[pIdx - 1].photo_formal_name = e.target.files[0]?.name || ''"
                            />
                            <div v-if="participantsList[pIdx - 1].photo_formal_name" class="file-chosen-info">
                              <i class="ri-checkbox-circle-fill text-emerald-500"></i>
                              <span>{{ participantsList[pIdx - 1].photo_formal_name }}</span>
                            </div>
                          </div>
                        </div>

                        <!-- Upload Foto Gaya Bebas Siswa (Bisa > 1 File) -->
                        <div v-if="repeaterField.sub_fields?.photo_freestyle?.active" class="form-group">
                          <label class="form-label">
                            <span><i class="ri-gallery-upload-line text-emerald-500"></i> Upload Foto Pose Gaya Bebas</span>
                            <span class="file-spec-tag tag-multi">Dapat &gt;1 Foto</span>
                          </label>
                          <div class="reg-file-dropzone-sm">
                            <input 
                              type="file" 
                              multiple
                              accept="image/*"
                              class="form-control" 
                              @change="e => handleFreestyleFiles(pIdx - 1, e)"
                            />
                            <div v-if="participantsList[pIdx - 1].freestyle_count" class="file-chosen-info">
                              <i class="ri-checkbox-circle-fill text-emerald-500"></i>
                              <span>{{ participantsList[pIdx - 1].freestyle_count }} file foto pose gaya bebas dipilih</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- STANDARD PARTICIPANTS TEXTAREA (FALLBACK IF NO REPEATER) -->
                <div v-else class="form-group" style="margin-bottom: 0;">
                  <label class="form-label" for="reg-peserta">Nama Lengkap Peserta / Anggota Tim *</label>
                  <textarea 
                    id="reg-peserta" 
                    v-model="form.participants_names"
                    class="form-control" 
                    rows="3" 
                    placeholder="Contoh :&#10;1. Ahmad Fauzi (Kelas 9A)&#10;2. Siti Nurhaliza (Kelas 8B)" 
                    required
                  ></textarea>
                  <div class="field-hint">Tuliskan nama lengkap peserta beserta keterangan kelas.</div>
                </div>
              </div>

              <!-- CARD 4: PERSYARATAN KHUSUS & BERKAS CABANG (DYNAMIC CUSTOM FIELDS) -->
              <div v-if="dynamicFields.length > 0" class="reg-section-card">
                <div class="reg-section-header">
                  <span class="reg-step-pill">4</span>
                  <div>
                    <h4 class="reg-section-title">Persyaratan Khusus & Berkas Cabang Lomba</h4>
                    <p class="reg-section-desc">Isian khusus yang ditentukan panitia cabang {{ activeComp?.title || '' }}.</p>
                  </div>
                </div>

                <div class="dynamic-fields-stack">
                  <template v-for="field in dynamicFields" :key="field.id || field.name">
                    <div v-if="isFieldVisible(field)" class="form-group dynamic-form-group">
                      <!-- Field Label -->
                      <label 
                        :for="'cf-' + field.name" 
                        class="form-label" 
                      >
                        <span class="field-label-text">
                          {{ field.label }}
                          <span v-if="field.required" class="required-star">*</span>
                          <span v-else class="optional-tag">(Opsional)</span>
                        </span>

                        <!-- File Spec Tag -->
                        <span v-if="field.type === 'file'" class="file-spec-tag">
                          {{ (field.allowed_extensions && field.allowed_extensions.length) ? field.allowed_extensions.join(', ').toUpperCase() : 'PDF / JPG / PNG' }} • Maks. {{ field.max_size_mb || 5 }} MB
                        </span>
                      </label>

                      <!-- 1. Text Singkat & Tautan Link URL -->
                      <input 
                        v-if="field.type === 'text' || field.type === 'url'"
                        :type="field.type === 'url' ? 'url' : 'text'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        class="form-control"
                        :placeholder="field.placeholder || (field.type === 'url' ? 'https://drive.google.com/...' : `Masukkan ${field.label}...`)"
                        :required="field.required"
                      />

                      <!-- 2. Angka / WhatsApp / Nominal -->
                      <input 
                        v-else-if="field.type === 'number'"
                        type="tel"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        @input="form.custom_fields[field.name] = (form.custom_fields[field.name] || '').toString().replace(/\D/g, '')"
                        class="form-control font-mono"
                        :placeholder="field.placeholder || `Masukkan angka ${field.label}...`"
                        :required="field.required"
                      />

                      <!-- 3. Alamat Email -->
                      <input 
                        v-else-if="field.type === 'email'"
                        type="email"
                        inputmode="email"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        class="form-control"
                        :placeholder="field.placeholder || 'nama@sekolah.sch.id'"
                        :required="field.required"
                      />

                      <!-- 4. Textarea / Paragraf -->
                      <textarea 
                        v-else-if="field.type === 'textarea'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        class="form-control"
                        rows="3"
                        :placeholder="field.placeholder || `Tuliskan ${field.label}...`"
                        :required="field.required"
                      ></textarea>

                      <!-- 5. Dropdown Pilihan (Wajib CustomSelect) -->
                      <CustomSelect 
                        v-else-if="field.type === 'select'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        :options="field.options || []"
                        :placeholder="field.placeholder || `-- Pilih ${field.label} --`"
                        :searchable="(field.options || []).length > 7"
                        search-placeholder="Ketik untuk mencari opsi..."
                        size="md"
                      />

                      <!-- 6. Pilihan Tunggal (Radio) -->
                      <div v-else-if="field.type === 'radio'" class="reg-options-list">
                        <label 
                          v-for="(opt, oIdx) in (field.options || [])" 
                          :key="oIdx"
                          class="reg-option-card"
                          :class="{ 'is-selected': form.custom_fields[field.name] === opt }"
                        >
                          <span class="option-text">{{ opt }}</span>
                          <input 
                            type="radio" 
                            :name="'cf_radio_' + field.name" 
                            :value="opt" 
                            v-model="form.custom_fields[field.name]" 
                          />
                        </label>
                      </div>

                      <!-- 7. Pilihan Ganda (Checkbox Multi-Select) -->
                      <div v-else-if="field.type === 'checkbox'" class="reg-options-list">
                        <label 
                          v-for="(opt, oIdx) in (field.options || [])" 
                          :key="oIdx"
                          @click.prevent="toggleCheckboxOption(field.name, opt)"
                          class="reg-option-card"
                          :class="{ 'is-selected': isCheckboxSelected(field.name, opt) }"
                        >
                          <span class="option-text">{{ opt }}</span>
                          <input 
                            type="checkbox" 
                            :checked="isCheckboxSelected(field.name, opt)"
                            tabindex="-1"
                          />
                        </label>
                      </div>

                      <!-- 8. Upload Berkas / Dokumen / File Dropzone -->
                      <div 
                        v-else-if="field.type === 'file'"
                        class="reg-file-dropzone-box"
                        :class="{ 'has-file': form.custom_fields[field.name] }"
                      >
                        <div v-if="!form.custom_fields[field.name]" class="dropzone-empty">
                          <i class="ri-upload-cloud-2-line dropzone-icon"></i>
                          <div class="dropzone-title">Pilih Berkas Dokumen untuk Diunggah</div>
                          <div class="dropzone-spec">
                            Format: {{ (field.allowed_extensions && field.allowed_extensions.length) ? field.allowed_extensions.join(', ').toUpperCase() : 'PDF, JPG, PNG, DOCX' }} • Maks. {{ field.max_size_mb || 5 }} MB
                          </div>
                          <input 
                            type="file" 
                            :id="'cf-' + field.name"
                            class="form-control file-input-element"
                            :accept="field.allowed_extensions ? field.allowed_extensions.map(e => '.' + e).join(',') : undefined"
                            @change="e => handleCustomFileChange(field.name, e)"
                            :required="field.required && !form.custom_fields[field.name]"
                          />
                        </div>
                        <div v-else class="dropzone-filled">
                          <div class="file-filled-left">
                            <div class="file-icon-box">
                              <i class="ri-file-text-line"></i>
                            </div>
                            <div class="file-meta">
                              <div class="file-name">{{ getCustomFileName(field.name) }}</div>
                              <div class="file-size-status">
                                <i class="ri-checkbox-circle-fill"></i> Siap diunggah ({{ formatFileSize(getCustomFileSize(field.name)) }})
                              </div>
                            </div>
                          </div>
                          <button 
                            type="button" 
                            class="btn-remove-file"
                            @click="clearCustomFile(field.name)" 
                            title="Hapus / ganti berkas"
                          >
                            <i class="ri-delete-bin-line"></i> Hapus
                          </button>
                        </div>
                      </div>

                      <!-- 9. Tanggal (Wajib dd/mm/yyyy Flatpickr) -->
                      <IndoDatePicker 
                        v-else-if="field.type === 'date'"
                        :id="'cf-' + field.name"
                        v-model="form.custom_fields[field.name]"
                        placeholder="dd/mm/yyyy"
                        inputClass="form-control"
                        iconClass="ri-calendar-line text-amber-500"
                      />

                      <!-- Help Text -->
                      <div v-if="field.help_text" class="field-hint">
                        <i class="ri-information-line"></i>
                        <span>{{ field.help_text }}</span>
                      </div>

                      <!-- Error Feedback -->
                      <div v-if="getFieldError(field.name)" class="field-error-feedback">
                        <i class="ri-error-warning-line"></i>
                        <span>{{ getFieldError(field.name) }}</span>
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <!-- CARD 5: CATATAN TAMBAHAN -->
              <div class="reg-section-card" style="margin-bottom: 0;">
                <div class="form-group" style="margin-bottom: 0;">
                  <label class="form-label" for="reg-catatan">Catatan / Keterangan Tambahan (Opsional)</label>
                  <input 
                    type="text" 
                    id="reg-catatan" 
                    v-model="form.notes"
                    class="form-control" 
                    placeholder="Tuliskan catatan untuk panitia pendaftaran jika ada..."
                  />
                </div>
              </div>
            </form>
          </div>

          <!-- 3. STICKY MODAL FOOTER -->
          <div class="reg-modal-footer">
            <div class="footer-summary">
              <div class="footer-fee-row">
                <span class="fee-label">Total Biaya :</span>
                <span v-if="isAllInFree" class="fee-amount text-success">
                  <span class="text-decoration-line-through text-muted fs-11 me-1">Rp {{ selectedCompFee.toLocaleString('id-ID') }}</span>
                  Gratis (Rp 0 - Paket Komplit)
                </span>
                <span v-else class="fee-amount">Rp {{ calculatedFee.toLocaleString('id-ID') }}</span>
              </div>
              <div class="footer-bank-info">
                <span v-if="isAllInFree" class="text-success fw-bold">
                  <i class="ri-checkbox-circle-fill"></i> Tercover Bebas Biaya (Paket Komplit: {{ manualCodeSuccessData?.school_name || form.school_name }})
                </span>
                <template v-else>
                  <span>Transfer {{ pricingSettings?.bank_name || 'Bank BSI' }}</span>
                  <span class="bank-dot">•</span>
                  <span class="bank-acc">Rek. {{ pricingSettings?.account_number || '7123456789' }}</span>
                  <span class="bank-name">a.n. {{ pricingSettings?.account_holder || (school.event_name || 'Semarak Aliyah') }}</span>
                </template>
              </div>
            </div>

            <div class="footer-actions">
              <button 
                type="button" 
                class="btn-cancel"
                @click="closeRegisterModal"
              >
                Batal
              </button>
              <button 
                type="submit" 
                form="registration-form"
                class="btn-submit"
                :disabled="form.processing"
              >
                <i v-if="form.processing" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-send-plane-fill"></i>
                <span>{{ form.processing ? 'Mengirim Pendaftaran...' : (activeComp?.form_settings?.submit_text || 'Kirim Pendaftaran Delegasi') }}</span>
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- ==========================================================================
         MODAL: DAFTAR SEKOLAH / MADRASAH BARU
         ========================================================================== -->
    <div 
      v-if="showNewSchoolModal" 
      class="modal-overlay reg-modal-overlay" 
      role="dialog" 
      aria-modal="true" 
      @click.self="showNewSchoolModal = false"
    >
      <div class="modal-card reg-modal-card" style="max-width: 580px;">
        <div class="reg-modal-header">
          <div class="reg-modal-header-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <i class="ri-community-line"></i>
          </div>
          <div class="reg-modal-header-info">
            <div class="reg-modal-header-tags">
              <span class="reg-badge-tag" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                <i class="ri-add-line"></i> Master Database Sekolah
              </span>
            </div>
            <h3 class="reg-modal-title mb-0">Daftarkan Sekolah Baru</h3>
          </div>
          <button type="button" class="btn-modal-close" @click="showNewSchoolModal = false">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="reg-modal-body p-4" style="overflow-y: auto; max-height: calc(88vh - 140px);">
          <p class="text-muted fs-12 mb-3">
            Silakan lengkapi data sekolah/madrasah Anda jika belum ada dalam database Kabupaten Jember. Setelah disimpan, sekolah Anda otomatis aktif dan siap dipilih untuk pendaftaran lomba.
          </p>

          <div v-if="newSchoolError" class="alert alert-danger py-2 px-3 fs-12 mb-3">
            <i class="ri-error-warning-line me-1"></i> {{ newSchoolError }}
          </div>

          <div class="form-group mb-3">
            <label class="form-label">
              <span>Nama Sekolah / Madrasah</span>
              <span class="required-star">*</span>
            </label>
            <input 
              v-model="newSchoolForm.name"
              type="text" 
              class="form-control" 
              placeholder="Contoh: SMP Unggulan Jember / MTs Nurul Huda"
              required
            />
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label">
                <span>Jenjang Pendidikan</span>
                <span class="required-star">*</span>
              </label>
              <CustomSelect v-model="newSchoolForm.jenjang">
                <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                <option value="MTs">MTs (Madrasah Tsanawiyah)</option>
              </CustomSelect>
            </div>
            <div class="col-md-6">
              <label class="form-label">
                <span>Wilayah Kecamatan</span>
                <span class="required-star">*</span>
              </label>
              <CustomSelect v-model="newSchoolForm.kecamatan">
                <option v-for="kec in kecamatanList" :key="kec" :value="kec">Kec. {{ kec }}</option>
              </CustomSelect>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label">
                <span>NPSN Sekolah (Opsional)</span>
              </label>
              <input 
                v-model="newSchoolForm.npsn"
                type="text" 
                class="form-control font-mono" 
                placeholder="Nomor Pokok Sekolah..."
              />
            </div>
            <div class="col-md-6">
              <label class="form-label">
                <span>No. WhatsApp / Telepon Sekolah (Opsional)</span>
              </label>
              <input 
                v-model="newSchoolForm.phone"
                type="text" 
                class="form-control" 
                placeholder="08xxxxxxxxxx"
              />
            </div>
          </div>

          <div class="form-group mb-0">
            <label class="form-label">
              <span>Alamat Lengkap Sekolah (Opsional)</span>
            </label>
            <textarea 
              v-model="newSchoolForm.alamat"
              rows="2" 
              class="form-control" 
              placeholder="Jl. ..., Desa/Kelurahan, Kecamatan..."
            ></textarea>
          </div>
        </div>

        <div class="reg-modal-footer">
          <div class="d-flex justify-content-end gap-2 w-100">
            <button type="button" class="btn-cancel" @click="showNewSchoolModal = false">Batal</button>
            <button 
              type="button" 
              class="btn-submit"
              :disabled="isSubmittingNewSchool"
              @click="submitNewSchool"
            >
              <i v-if="isSubmittingNewSchool" class="ri-loader-4-line ri-spin"></i>
              <i v-else class="ri-check-line"></i>
              <span>{{ isSubmittingNewSchool ? 'Menyimpan...' : 'Simpan & Pilih Sekolah' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==========================================================================
         MODAL: PENDAFTARAN PAKET ALL-IN KOMPLIT
         ========================================================================== -->
    <div 
      v-if="showAllInModal" 
      class="modal-overlay reg-modal-overlay" 
      role="dialog" 
      aria-modal="true" 
      @click.self="showAllInModal = false"
    >
      <div class="modal-card reg-modal-card" style="max-width: 720px;">
        <div class="reg-modal-header">
          <div class="reg-modal-header-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
            <i class="ri-vip-crown-fill"></i>
          </div>
          <div class="reg-modal-header-info">
            <div class="reg-modal-header-tags">
              <span class="reg-badge-tag" style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                <i class="ri-sparkling-fill"></i> Paling Hemat • Akses 8 Cabang Lomba
              </span>
            </div>
            <h3 class="reg-modal-title mb-0">Pendaftaran Kontingen Paket All-In Komplit</h3>
          </div>
          <button type="button" class="btn-modal-close" @click="showAllInModal = false">
            <i class="ri-close-line"></i>
          </button>
        </div>

        <div class="reg-modal-body p-4" style="overflow-y: auto; max-height: calc(88vh - 140px);">
          <!-- Info Banner Paket All-In -->
          <div class="p-3 rounded border border-warning-subtle bg-warning-subtle mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
              <span class="fw-bold fs-13 text-dark">
                <i class="ri-flashlight-fill text-warning me-1"></i> Biaya Registrasi Paket All-In:
              </span>
              <span class="fw-bold fs-16 font-mono text-dark">
                Rp {{ Number(pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }}
              </span>
            </div>
            <p class="fs-12 text-secondary mb-0" style="line-height: 1.5;">
              Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus! Cukup isi nama sekolah, nomor kontak koordinator, tentukan jumlah guru pendamping, dan unggah bukti transfer.
            </p>
          </div>

          <div v-if="allInError" class="alert alert-danger py-2 px-3 fs-12 mb-3">
            <i class="ri-error-warning-line me-1"></i> {{ allInError }}
          </div>

          <!-- Section 1: Data Identitas Sekolah & Koordinator -->
          <div class="reg-section-card mb-3">
            <div class="reg-section-title">
              <i class="ri-building-4-line text-warning"></i> 1. Identitas Sekolah & Koordinator Utama
            </div>

            <div class="form-group mb-3">
              <label class="form-label">
                <span>Nama Lembaga / Sekolah Asal</span>
                <span class="required-star">*</span>
              </label>
              <input 
                v-model="allInForm.school_name"
                type="text" 
                class="form-control fw-bold" 
                placeholder="Contoh: SMPN 1 Balung"
                required
              />
            </div>

            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label">
                  <span>NPSN Sekolah (Opsional)</span>
                </label>
                <input 
                  v-model="allInForm.npsn"
                  type="text" 
                  class="form-control font-mono" 
                  placeholder="Nomor NPSN jika ada..."
                />
              </div>
              <div class="col-md-6">
                <label class="form-label">
                  <span>Nama Koordinator / Pembina Utama</span>
                  <span class="required-star">*</span>
                </label>
                <input 
                  v-model="allInForm.mentor_name"
                  type="text" 
                  class="form-control" 
                  placeholder="Nama Lengkap & Gelar Guru Pembina..."
                  required
                />
              </div>
            </div>

            <div class="form-group mb-0">
              <label class="form-label">
                <span>Nomor WhatsApp Aktif Koordinator / Pendamping</span>
                <span class="required-star">*</span>
              </label>
              <input 
                v-model="allInForm.mentor_wa"
                type="text" 
                class="form-control" 
                placeholder="08xxxxxxxxxx (untuk koordinasi teknis dan verifikasi)"
                required
              />
            </div>
          </div>

          <!-- Section 2: Opsi Menentukan Jumlah Pendamping yang Didaftarkan -->
          <div class="reg-section-card mb-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <div class="reg-section-title mb-0">
                <i class="ri-team-line text-primary"></i> 2. Guru / Pembina Pendamping yang Didaftarkan
              </div>

              <!-- Stepper Menentukan Jumlah Pendamping -->
              <div class="d-flex align-items-center gap-2">
                <span class="fs-12 text-muted fw-semibold">Jumlah Pendamping:</span>
                <div class="d-flex align-items-center border rounded overflow-hidden">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-light border-0 px-2.5 py-1"
                    :disabled="allInForm.coordinators_count <= 1"
                    @click="updateCoordinatorsCount(allInForm.coordinators_count - 1)"
                  >
                    <i class="ri-subtract-line"></i>
                  </button>
                  <span class="px-3 fw-bold fs-13 font-mono text-dark bg-white">
                    {{ allInForm.coordinators_count }}
                  </span>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-light border-0 px-2.5 py-1"
                    :disabled="allInForm.coordinators_count >= 10"
                    @click="updateCoordinatorsCount(allInForm.coordinators_count + 1)"
                  >
                    <i class="ri-add-line"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Dynamic Repeater List Pendamping -->
            <div class="d-flex flex-column gap-2.5">
              <div 
                v-for="(coord, cIdx) in allInForm.coordinators" 
                :key="cIdx"
                class="p-3 rounded bg-light border"
              >
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fw-bold fs-12 text-dark">
                    <i class="ri-user-star-line text-primary me-1"></i> Pendamping #{{ cIdx + 1 }}
                    <span v-if="cIdx === 0" class="badge bg-primary-subtle text-primary fs-10 ms-1">Utama</span>
                  </span>
                  <button 
                    v-if="allInForm.coordinators.length > 1"
                    type="button" 
                    class="btn btn-xs text-danger border-0 p-0"
                    @click="removeCoordinator(cIdx)"
                    title="Hapus pendamping ini"
                  >
                    <i class="ri-delete-bin-line"></i> Hapus
                  </button>
                </div>

                <div class="row g-2">
                  <div class="col-md-5">
                    <label class="form-label fs-11 mb-1">Nama Lengkap</label>
                    <input 
                      v-model="coord.name"
                      type="text" 
                      class="form-control form-control-sm" 
                      :placeholder="cIdx === 0 ? allInForm.mentor_name || 'Nama Pendamping...' : 'Nama Guru Pendamping...'"
                    />
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fs-11 mb-1">Jabatan / Peran</label>
                    <input 
                      v-model="coord.role"
                      type="text" 
                      class="form-control form-control-sm" 
                      placeholder="Contoh: Guru Olahraga / Pembina Seni"
                    />
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fs-11 mb-1">Nomor WhatsApp</label>
                    <input 
                      v-model="coord.wa"
                      type="text" 
                      class="form-control form-control-sm" 
                      placeholder="08xxxxxxxxxx"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-2.5">
              <button 
                type="button" 
                class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1"
                :disabled="allInForm.coordinators.length >= 10"
                @click="addCoordinator"
              >
                <i class="ri-user-add-line"></i> + Tambah Pendamping Lain
              </button>
            </div>
          </div>

          <!-- Section 3: Informasi Pembayaran & Unggah Bukti -->
          <div class="reg-section-card mb-0">
            <div class="reg-section-title">
              <i class="ri-wallet-3-line text-success"></i> 3. Rekening Transfer & Bukti Pembayaran
            </div>

            <div class="p-3 bg-white border rounded mb-3">
              <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <span class="fs-12 text-muted">Rekening Resmi Panitia:</span>
                <span class="badge bg-success-subtle text-success fs-11 font-mono fw-bold">
                  {{ pricingSettings?.bank_name || 'Bank Syariah Indonesia (BSI)' }}
                </span>
              </div>
              <div class="d-flex align-items-baseline gap-2 mb-1">
                <h4 class="fs-18 fw-bold font-mono text-dark mb-0">{{ pricingSettings?.account_number || '7123456789' }}</h4>
                <small class="text-muted">a.n. {{ pricingSettings?.account_holder || (school.event_name || 'Semarak Aliyah') }}</small>
              </div>
              <div class="fs-11 text-muted">
                Jumlah transfer tepat: <strong class="text-dark">Rp {{ Number(pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }}</strong>
              </div>
            </div>

            <div class="form-group mb-3">
              <label class="form-label">
                <span>Unggah Bukti Transfer Bank (Opsional)</span>
                <span class="text-muted fs-11">Format: JPG, PNG, PDF (Maks. 5MB)</span>
              </label>
              <input 
                type="file" 
                accept="image/*,.pdf"
                class="form-control"
                @change="handleAllInProofChange"
              />
            </div>

            <div class="form-group mb-0">
              <label class="form-label">
                <span>Catatan untuk Panitia (Opsional)</span>
              </label>
              <textarea 
                v-model="allInForm.notes"
                rows="2" 
                class="form-control" 
                placeholder="Tuliskan catatan khusus atau konfirmasi transfer jika ada..."
              ></textarea>
            </div>
          </div>
        </div>

        <div class="reg-modal-footer">
          <div class="footer-summary">
            <div class="footer-fee-row">
              <span class="fee-label">Total Pembayaran :</span>
              <span class="fee-amount">Rp {{ Number(pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }}</span>
            </div>
            <div class="footer-bank-info">
              <span>Paket All-In Komplit • Meliputi Seluruh 8 Cabang Lomba</span>
            </div>
          </div>

          <div class="footer-actions">
            <button type="button" class="btn-cancel" @click="showAllInModal = false">Batal</button>
            <button 
              type="button" 
              class="btn-submit"
              :disabled="isSubmittingAllIn"
              @click="submitAllInRegistration"
            >
              <i v-if="isSubmittingAllIn" class="ri-loader-4-line ri-spin"></i>
              <i v-else class="ri-send-plane-fill"></i>
              <span>{{ isSubmittingAllIn ? 'Mengirim Pendaftaran...' : 'Kirim Pendaftaran Paket All-In' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<style scoped>
/* =============================================================================
   SEMARAK ALIYAH - REGISTRATION MODAL ENHANCED STYLING
   State-of-the-art Modern UI with High Contrast in Light & Dark Mode
   ============================================================================= */

/* 1. Modal Overlay & Card Container */
.reg-modal-overlay {
  position: fixed;
  inset: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(8px);
  display: flex !important;
  align-items: center;
  justify-content: center;
  z-index: 2100;
  padding: 16px;
  animation: regFadeIn 0.2s ease-out;
}

@keyframes regFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.reg-modal-card {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  width: 100%;
  max-width: 780px;
  max-height: 92vh;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.04);
  padding: 0 !important;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  position: relative;
  animation: regSlideUp 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes regSlideUp {
  from { transform: translateY(18px) scale(0.97); opacity: 0; }
  to { transform: translateY(0) scale(1); opacity: 1; }
}

/* 2. Sticky Header */
.reg-modal-header {
  position: sticky;
  top: 0;
  z-index: 30;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 18px 24px;
  display: flex;
  align-items: center;
  gap: 14px;
}

.reg-modal-header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
  box-shadow: 0 4px 14px rgba(236, 72, 153, 0.25);
}

.reg-modal-header-info {
  flex: 1;
  min-width: 0;
}

.reg-modal-header-tags {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 4px;
}

.reg-badge-tag {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  line-height: 1.3;
}

.tag-pkg {
  background: #fdf2f8;
  color: #db2777;
  border: 1px solid #fbcfe8;
}

.tag-cat {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.tag-team {
  background: #f5f3ff;
  color: #7c3aed;
  border: 1px solid #ddd6fe;
}

.reg-modal-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.reg-modal-subtitle {
  font-size: 0.8rem;
  color: #64748b;
  margin: 2px 0 0 0;
  line-height: 1.4;
}

.reg-modal-close {
  background: #f1f5f9;
  border: none;
  color: #475569;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  transition: all 0.15s ease;
  flex-shrink: 0;
}

.reg-modal-close:hover {
  background: #fee2e2;
  color: #ef4444;
  transform: rotate(90deg);
}

/* 3. Scrollable Modal Body */
.reg-modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 18px;
  background: #f8fafc;
}

.reg-modal-body::-webkit-scrollbar {
  width: 6px;
}

.reg-modal-body::-webkit-scrollbar-track {
  background: transparent;
}

.reg-modal-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.reg-modal-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* 4. Section Card Containers */
.reg-section-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  margin-bottom: 16px;
}

.reg-section-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 14px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
}

.reg-step-pill {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: linear-gradient(135deg, #8b5cf6 0%, #db2777 100%);
  color: #ffffff;
  font-weight: 800;
  font-size: 0.78rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.reg-section-title {
  font-size: 0.98rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  line-height: 1.3;
}

.reg-section-desc {
  font-size: 0.78rem;
  color: #64748b;
  margin: 2px 0 0 0;
}

/* 5. Package Selector Cards */
.reg-package-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.reg-package-item {
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 14px;
  cursor: pointer;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: #ffffff;
  transition: all 0.18s ease;
  position: relative;
}

.reg-package-item:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.reg-package-item.is-selected {
  border-color: #8b5cf6;
  background: #f5f3ff;
  box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15);
}

.reg-pkg-radio {
  margin-top: 2px;
}

.reg-pkg-radio input {
  cursor: pointer;
  accent-color: #8b5cf6;
}

.reg-pkg-name {
  font-weight: 700;
  font-size: 0.88rem;
  color: #0f172a;
}

.reg-pkg-price {
  font-family: monospace;
  font-weight: 800;
  font-size: 0.92rem;
  margin: 2px 0;
}

.satuan-price {
  color: #059669;
}

.allin-price {
  color: #ea580c;
}

.price-sub {
  font-size: 0.72rem;
  font-family: sans-serif;
  font-weight: 500;
  color: #64748b;
}

.reg-pkg-note {
  font-size: 0.72rem;
  color: #64748b;
  line-height: 1.3;
}

/* 6. Form Controls & Grids */
.form-group {
  margin-bottom: 14px;
}

.form-label {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.82rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 6px;
  line-height: 1.3;
}

.required-star {
  color: #ef4444;
  margin-left: 2px;
  font-weight: 800;
}

.optional-tag {
  font-size: 0.72rem;
  color: #94a3b8;
  font-weight: normal;
  margin-left: 4px;
}

.form-control {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 0.85rem;
  color: #0f172a;
  width: 100%;
  transition: all 0.18s ease;
  line-height: 1.5;
}

.form-control:focus {
  border-color: #8b5cf6;
  outline: none;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15);
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  color: #94a3b8;
  font-size: 1.1rem;
  pointer-events: none;
}

.input-with-icon .form-control {
  padding-left: 38px;
}

.wa-icon {
  color: #10b981;
}

.field-hint {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.field-error-feedback {
  font-size: 0.75rem;
  color: #ef4444;
  font-weight: 600;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.searching-badge {
  font-size: 0.72rem;
  color: #059669;
  font-weight: normal;
}

.reg-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

/* 7. School Autocomplete Dropdown */
.school-autocomplete-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  z-index: 1050;
  background: #ffffff;
  border: 1.5px solid #a7f3d0;
  border-radius: 10px;
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
  max-height: 230px;
  overflow-y: auto;
}

.school-suggestion-item {
  padding: 10px 14px;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  transition: background 0.15s ease;
}

.school-suggestion-item:last-child {
  border-bottom: none;
}

.school-suggestion-item:hover {
  background: #ecfdf5;
}

.school-name {
  font-weight: 700;
  font-size: 0.85rem;
  color: #0f172a;
}

.school-meta {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 1px;
}

.school-tag {
  font-size: 0.68rem;
  padding: 2px 7px;
  border-radius: 4px;
  font-weight: 700;
  white-space: nowrap;
}

.tag-mts {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.tag-smp {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

/* Package Code Card (Paket Komplit) */
.package-code-card {
  background: #f8fafc;
  border: 1.5px solid #cbd5e1;
  border-radius: 14px;
  padding: 16px;
  margin-top: 14px;
  transition: all 0.2s ease;
}

.package-code-header {
  margin-bottom: 12px;
}

.package-code-title-wrap {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.package-code-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.25);
}

.package-code-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.package-code-badge {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.package-code-desc {
  font-size: 0.78rem;
  color: #475569;
  margin: 3px 0 0 0;
  line-height: 1.4;
}

.package-code-input-row {
  display: flex;
  gap: 8px;
  align-items: center;
}

.package-code-input {
  font-family: monospace;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 0.95rem;
}

.btn-verify-code {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 16px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  background: #059669;
  color: #ffffff;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-verify-code:hover:not(:disabled) {
  background: #047857;
}

.btn-verify-code:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn-clear-code {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: #e2e8f0;
  color: #64748b;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1.1rem;
}

.btn-clear-code:hover {
  background: #fee2e2;
  color: #ef4444;
}

.code-verified-alert {
  margin-top: 10px;
  background: #ecfdf5;
  border: 1.5px solid #a7f3d0;
  border-radius: 10px;
  padding: 12px 14px;
  display: flex;
  gap: 10px;
  align-items: flex-start;
  animation: regFadeIn 0.25s ease;
}

.code-verified-alert .alert-headline {
  font-size: 0.88rem;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.code-verified-alert .alert-details {
  font-size: 0.78rem;
  color: #334155;
  margin-top: 3px;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.code-verified-alert .alert-details code {
  background: #d1fae5;
  color: #065f46;
  padding: 1px 5px;
  border-radius: 4px;
  font-weight: 700;
}

.code-verified-alert .alert-subnote {
  font-size: 0.74rem;
  color: #065f46;
  margin-top: 4px;
}

.badge-free-pill {
  background: #10b981;
  color: #ffffff;
  font-size: 0.72rem;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 999px;
  display: inline-block;
}

.code-error-alert {
  margin-top: 10px;
  background: #fef2f2;
  border: 1.5px solid #fecaca;
  border-radius: 10px;
  padding: 10px 12px;
  color: #dc2626;
  font-size: 0.82rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  animation: regFadeIn 0.25s ease;
}

.package-code-help {
  margin-top: 8px;
  font-size: 0.76rem;
  color: #64748b;
  display: flex;
  align-items: flex-start;
  gap: 5px;
  line-height: 1.35;
}

/* Verified School Summary Card */
.verified-school-summary-card {
  background: #f0fdf4;
  border: 1.5px solid #86efac;
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
  animation: regFadeIn 0.3s ease;
}

.verified-school-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.verified-school-badge-wrap {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  flex: 1;
}

.verified-school-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #10b981;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
}

.verified-school-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #065f46;
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.verified-school-subtitle {
  font-size: 0.78rem;
  color: #166534;
  margin-top: 3px;
  line-height: 1.4;
}

.btn-unlink-school {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 0.76rem;
  font-weight: 600;
  color: #047857;
  background: #ffffff;
  border: 1px solid #86efac;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-unlink-school:hover {
  background: #fef2f2;
  color: #dc2626;
  border-color: #fca5a5;
}

.verified-school-details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 10px;
  background: #ffffff;
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 12px;
}

.detail-box {
  padding: 8px 10px;
  border-radius: 6px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

.detail-box.school-main {
  grid-column: 1 / -1;
  background: #ecfdf5;
  border-color: #a7f3d0;
}

.detail-box.school-address-box {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 2px;
  display: flex;
  align-items: center;
}

.detail-val {
  font-size: 0.85rem;
  font-weight: 600;
  color: #0f172a;
  word-break: break-word;
}

.school-name-val {
  font-size: 1.05rem;
  font-weight: 700;
  color: #065f46;
}

/* Mentor Section Divider */
.mentor-section-divider {
  margin-top: 18px;
  margin-bottom: 14px;
  padding-top: 14px;
  border-top: 1.5px dashed #cbd5e1;
}

.mentor-section-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
}

.mentor-section-subtitle {
  font-size: 0.76rem;
  color: #64748b;
  margin-top: 2px;
}

.input-textarea-wrapper textarea {
  padding-left: 38px !important;
}

/* 8. Roster Toolbar & Stepper */
.reg-roster-toolbar {
  background: #ecfdf5;
  border: 1.5px solid #a7f3d0;
  border-radius: 12px;
  padding: 14px 16px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.toolbar-title {
  font-weight: 700;
  font-size: 0.88rem;
  color: #065f46;
  display: flex;
  align-items: center;
  gap: 6px;
}

.toolbar-quota-desc {
  font-size: 0.76rem;
  color: #047857;
  margin-top: 2px;
}

.core-tag {
  color: #065f46;
  font-weight: 700;
}

.toolbar-rule-alert {
  font-size: 0.72rem;
  color: #b45309;
  background: #fffbeb;
  border: 1px solid #fde68a;
  padding: 3px 8px;
  border-radius: 4px;
  margin-top: 4px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.roster-stepper {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #ffffff;
  padding: 4px 6px;
  border-radius: 8px;
  border: 1.5px solid #a7f3d0;
}

.stepper-btn {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #334155;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.15s ease;
}

.stepper-btn:hover:not(:disabled) {
  background: #10b981;
  color: #ffffff;
  border-color: #10b981;
}

.stepper-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.stepper-display {
  font-family: monospace;
  font-weight: 800;
  font-size: 0.95rem;
  color: #047857;
  padding: 0 8px;
}

.stepper-unit {
  font-size: 0.72rem;
  font-weight: normal;
  color: #64748b;
  font-family: sans-serif;
}

/* 9. Student Roster Stack & Cards */
.reg-students-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.reg-student-card {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  transition: border-color 0.18s ease;
}

.reg-student-card:hover {
  border-color: #cbd5e1;
}

.student-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-bottom: 8px;
  margin-bottom: 12px;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
  gap: 8px;
}

.student-header-left {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.student-card-idx {
  font-weight: 700;
  font-size: 0.85rem;
  color: #d97706;
  display: flex;
  align-items: center;
  gap: 5px;
}

.team-role-badge {
  font-size: 0.68rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.role-inti {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.role-cadangan {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
}

.captain-badge {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fcd34d;
  font-size: 0.68rem;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 4px;
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.captain-toggle-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  padding: 3px 9px;
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  transition: all 0.15s ease;
}

.captain-toggle-label.is-active {
  background: #fef3c7;
  color: #b45309;
  border-color: #f59e0b;
}

.gender-segmented-wrap {
  display: flex;
  gap: 6px;
}

.gender-segmented-btn {
  flex: 1;
  border: 1.5px solid #cbd5e1;
  background: #ffffff;
  padding: 7px 10px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-size: 0.8rem;
  color: #334155;
  font-weight: 500;
  transition: all 0.15s ease;
}

.gender-segmented-btn.is-selected {
  background: #ecfdf5;
  border-color: #10b981;
  color: #065f46;
  font-weight: 700;
}

.captain-wa-alert-card {
  background: #fffbeb;
  border: 1.5px solid #f59e0b;
  border-radius: 10px;
  padding: 12px 14px;
  margin-top: 6px;
  margin-bottom: 10px;
}

.captain-wa-label {
  font-size: 0.78rem;
  color: #b45309;
  font-weight: 700;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.captain-input {
  border-color: #f59e0b !important;
}

.captain-wa-hint {
  font-size: 0.7rem;
  color: #92400e;
  margin-top: 4px;
  line-height: 1.4;
}

.reg-file-dropzone-sm {
  margin-top: 4px;
}

.file-chosen-info {
  font-size: 0.72rem;
  color: #059669;
  font-weight: 600;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
}

/* 10. Dynamic Options (Radio & Checkbox Cards) */
.reg-options-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.reg-option-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 14px;
  border-radius: 8px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  cursor: pointer;
  transition: all 0.15s ease;
}

.reg-option-card:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.reg-option-card.is-selected {
  background: #f5f3ff;
  border-color: #8b5cf6;
  color: #6d28d9;
}

.option-text {
  font-size: 0.84rem;
  font-weight: 600;
}

/* 11. Styled File Dropzone */
.reg-file-dropzone-box {
  border: 1.5px dashed #cbd5e1;
  border-radius: 10px;
  padding: 16px;
  background: #f8fafc;
  text-align: center;
  transition: all 0.18s ease;
}

.reg-file-dropzone-box.has-file {
  border-style: solid;
  border-color: #10b981;
  background: #ecfdf5;
  text-align: left;
}

.dropzone-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.dropzone-icon {
  font-size: 2rem;
  color: #8b5cf6;
  margin-bottom: 4px;
}

.dropzone-title {
  font-weight: 700;
  font-size: 0.85rem;
  color: #0f172a;
}

.dropzone-spec {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 2px;
}

.file-input-element {
  margin-top: 10px;
  max-width: 320px;
}

.dropzone-filled {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.file-filled-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.file-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  background: #d1fae5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.file-name {
  font-weight: 700;
  font-size: 0.85rem;
  color: #065f46;
  word-break: break-all;
}

.file-size-status {
  font-size: 0.72rem;
  color: #047857;
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 1px;
}

.btn-remove-file {
  background: #fee2e2;
  border: 1px solid #fecaca;
  color: #ef4444;
  border-radius: 6px;
  padding: 5px 10px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
  transition: all 0.15s ease;
}

.btn-remove-file:hover {
  background: #ef4444;
  color: #ffffff;
}

.file-spec-tag {
  font-size: 0.68rem;
  color: #0284c7;
  background: #e0f2fe;
  border: 1px solid #bae6fd;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 600;
}

.tag-multi {
  color: #059669;
  background: #ecfdf5;
  border-color: #a7f3d0;
}

/* 12. Sticky Footer */
.reg-modal-footer {
  position: sticky;
  bottom: 0;
  z-index: 30;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
  padding: 14px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.04);
}

.footer-fee-row {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.fee-label {
  font-size: 0.76rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.fee-amount {
  font-family: monospace;
  font-size: 1.35rem;
  font-weight: 800;
  color: #059669;
}

.footer-bank-info {
  font-size: 0.74rem;
  color: #475569;
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
}

.bank-dot {
  color: #cbd5e1;
}

.bank-acc {
  font-family: monospace;
  font-weight: 700;
  color: #d97706;
}

.footer-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.btn-cancel {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #475569;
  border-radius: 8px;
  padding: 9px 16px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel:hover {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-submit {
  background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
  border: none;
  color: #ffffff;
  border-radius: 8px;
  padding: 10px 20px;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 4px 14px rgba(236, 72, 153, 0.35);
  transition: all 0.18s ease;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(236, 72, 153, 0.45);
}

.btn-submit:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none;
}

/* 13. Success View */
.reg-success-view {
  padding: 36px 28px;
  text-align: center;
}

.reg-success-icon-wrap {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: #d1fae5;
  color: #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.8rem;
  margin: 0 auto 16px auto;
  box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25);
}

.reg-success-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 8px;
}

.reg-success-desc {
  font-size: 0.88rem;
  color: #64748b;
  max-width: 480px;
  margin: 0 auto 24px auto;
  line-height: 1.5;
}

.reg-success-summary-box {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  max-width: 500px;
  margin: 0 auto 24px auto;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 8px;
}

.summary-label {
  color: #64748b;
}

.summary-val {
  font-weight: 700;
  color: #0f172a;
}

.code-val {
  font-family: monospace;
  color: #8b5cf6;
  font-size: 1rem;
}

.highlight-val {
  color: #d97706;
}

.total-row {
  border-bottom: none;
  padding-bottom: 0;
  margin-top: 2px;
}

.fee-val {
  font-family: monospace;
  font-size: 1.1rem;
  color: #059669;
}

.reg-success-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
}

/* 14. Responsive Rules */
@media (max-width: 640px) {
  .reg-modal-overlay {
    padding: 0;
  }
  .reg-modal-card {
    max-height: 100vh;
    height: 100vh;
    border-radius: 0;
  }
  .reg-modal-header {
    padding: 14px 16px;
  }
  .reg-modal-body {
    padding: 14px 16px;
  }
  .reg-package-grid,
  .reg-grid-2 {
    grid-template-columns: 1fr;
  }
  .reg-modal-footer {
    flex-direction: column;
    align-items: stretch;
    padding: 14px 16px;
  }
  .footer-actions {
    display: flex;
    gap: 8px;
  }
  .btn-cancel, .btn-submit {
    flex: 1;
    justify-content: center;
  }
}

/* 15. Modal theme overrides moved to dedicated global style block at the bottom of this file */

/* =============================================================================
   KEYFRAME ANIMATIONS & GLOW PULSES
   ============================================================================= */
@keyframes radar-pulse {
  0% { transform: scale(0.9); opacity: 0.85; }
  70% { transform: scale(2.4); opacity: 0; }
  100% { transform: scale(2.4); opacity: 0; }
}

@keyframes top-edge-shimmer {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

.live-dot-glow {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 8px #10b981;
}

.live-dot-glow-emerald {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 8px #10b981;
}

.live-dot-glow-amber {
  display: inline-block;
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #f59e0b;
  box-shadow: 0 0 8px #f59e0b;
}

/* =============================================================================
   SMART REGISTRANT ACCOUNT & GUEST BANNERS (VIP COMMAND STRIP)
   ============================================================================= */
/* =============================================================================
   SMART REGISTRANT ACCOUNT & GUEST BANNERS (VIP COMMAND STRIP)
   ============================================================================= */
.smart-account-card {
  background: radial-gradient(ellipse 90% 120px at 50% 0%, rgba(99, 102, 241, 0.08) 0%, transparent 100%), #ffffff;
  border: 1.5px solid rgba(99, 102, 241, 0.28);
  border-top: 4px solid #4f46e5;
  border-radius: 22px;
  padding: 22px 26px;
  box-shadow: 0 12px 35px -8px rgba(99, 102, 241, 0.14), 0 2px 8px -1px rgba(0, 0, 0, 0.04);
  position: relative;
  margin-bottom: 28px !important;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.smart-account-card:hover {
  border-color: rgba(99, 102, 241, 0.42);
  border-top-color: #4338ca;
  box-shadow: 0 16px 45px -8px rgba(99, 102, 241, 0.2), 0 4px 12px -2px rgba(0, 0, 0, 0.05);
  transform: translateY(-1px);
}

.smart-card-top-edge {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 24px;
  border-top-left-radius: 20px;
  border-top-right-radius: 20px;
  background: linear-gradient(180deg, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0) 100%);
  pointer-events: none;
  z-index: 1;
}

.smart-card-ambient-glow-1 {
  position: absolute;
  right: -50px;
  top: -50px;
  width: 260px;
  height: 260px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 70%);
  pointer-events: none;
}

.smart-card-ambient-glow-2 {
  position: absolute;
  left: -40px;
  bottom: -40px;
  width: 220px;
  height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
  pointer-events: none;
}

.auth-banner-avatar-wrap {
  position: relative;
  width: 50px;
  height: 50px;
  flex-shrink: 0;
}

.auth-banner-avatar {
  width: 100%;
  height: 100%;
  border-radius: 14px;
  background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.3rem;
  box-shadow: 0 8px 20px -3px rgba(79, 70, 229, 0.4), 0 0 0 2px rgba(99, 102, 241, 0.15);
  border: 1.5px solid rgba(255, 255, 255, 0.4);
}

.avatar-status-dot-pulse {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-status-dot-pulse .dot-core {
  width: 11px;
  height: 11px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #ffffff;
  z-index: 2;
  box-shadow: 0 0 6px #10b981;
}

.avatar-status-dot-pulse .dot-ripple {
  position: absolute;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: #10b981;
  opacity: 0.8;
  animation: radar-pulse 2s infinite cubic-bezier(0, 0, 0.2, 1);
}

.auth-banner-name {
  color: #0f172a;
  font-weight: 800;
  font-size: 1.25rem;
  letter-spacing: -0.3px;
}

.badge-role-pill {
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  border: 1px solid rgba(37, 99, 235, 0.28);
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 9999px;
  padding: 3px 12px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  box-shadow: 0 1px 2px rgba(37, 99, 235, 0.04);
}

.verified-check-icon {
  color: #2563eb;
  font-size: 0.95rem;
  line-height: 1;
}

.badge-connected-pill {
  background: rgba(16, 185, 129, 0.1);
  color: #065f46;
  border: 1px solid rgba(16, 185, 129, 0.3);
  font-size: 0.74rem;
  font-weight: 700;
  border-radius: 9999px;
  padding: 3px 12px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  letter-spacing: 0.15px;
}

.live-pulse-emerald {
  display: inline-block;
  width: 7.5px;
  height: 7.5px;
  border-radius: 50%;
  background: #10b981;
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  animation: live-pulse-ring 2s infinite cubic-bezier(0.24, 0, 0.38, 1);
  flex-shrink: 0;
}

@keyframes live-pulse-ring {
  0% {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
  }
  70% {
    box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
  }
}

.auth-banner-subtitle {
  color: #475569;
  font-size: 0.86rem;
  line-height: 1.5;
}

.auth-banner-school {
  color: #0f172a;
  font-weight: 800;
}

/* Explicit Header Row Alignment (Avatar + Identity on Left, Action on Right) */
.smart-account-header {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: 20px !important;
}

.smart-account-profile {
  display: flex !important;
  align-items: center !important;
  gap: 16px !important;
  flex: 1 1 auto !important;
  min-width: 0 !important;
}

.smart-account-details {
  flex: 1 1 auto !important;
  min-width: 0 !important;
}

.smart-account-action {
  flex-shrink: 0 !important;
}

@media (max-width: 860px) {
  .smart-account-header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 16px !important;
  }
  .smart-account-action {
    width: 100% !important;
  }
  .btn-dashboard-nav-hero {
    width: 100% !important;
    justify-content: center !important;
  }
}

/* Bottom status bar in account card */
.smart-account-footer {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid rgba(99, 102, 241, 0.12);
}

.auth-feature-chip {
  font-size: 0.74rem;
  font-weight: 700;
  color: #334155;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 3px 10px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
  display: inline-flex;
  align-items: center;
  transition: all 0.2s ease;
}

.auth-feature-chip:hover {
  transform: translateY(-1px);
  border-color: #cbd5e1;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.05);
}

.btn-dashboard-nav-hero {
  background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
  color: #ffffff !important;
  font-weight: 700;
  font-size: 0.86rem;
  padding: 10px 20px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  text-decoration: none;
  box-shadow: 0 6px 18px -2px rgba(79, 70, 229, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.25);
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-dashboard-nav-hero:hover {
  background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
  transform: translateY(-2px);
  box-shadow: 0 10px 24px -2px rgba(79, 70, 229, 0.5);
  color: #ffffff !important;
}

.btn-nav-arrow {
  transition: transform 0.2s ease;
}

.btn-dashboard-nav-hero:hover .btn-nav-arrow {
  transform: translateX(3px);
}

.smart-guest-card {
  background: radial-gradient(ellipse 90% 120px at 50% 0%, rgba(245, 158, 11, 0.1) 0%, transparent 100%), #ffffff;
  border: 1px solid rgba(245, 158, 11, 0.35);
  border-radius: 20px;
  padding: 22px 26px;
  box-shadow: 0 10px 30px -5px rgba(245, 158, 11, 0.12);
  margin-bottom: 28px !important;
}

.smart-guest-header {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: space-between !important;
  gap: 20px !important;
}

.smart-guest-info {
  display: flex !important;
  align-items: center !important;
  gap: 16px !important;
  flex: 1 1 auto !important;
  min-width: 0 !important;
}

.smart-guest-actions {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  flex-shrink: 0 !important;
}

@media (max-width: 860px) {
  .smart-guest-header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 16px !important;
  }
  .smart-guest-actions {
    width: 100% !important;
  }
  .btn-guest-login, .btn-guest-register {
    flex: 1 1 auto !important;
    text-align: center !important;
    justify-content: center !important;
  }
}

.guest-banner-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #ffffff;
  font-size: 1.45rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
  border: 1.5px solid rgba(255, 255, 255, 0.4);
}

/* =============================================================================
   REGISTRATION GUIDELINE CHECKLIST CARD (.guide-box)
   ============================================================================= */
.guide-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 24px 28px;
  box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
  margin-bottom: 32px !important;
  position: relative;
  transition: all 0.2s ease;
}

.guide-box:hover {
  border-color: #cbd5e1;
  box-shadow: 0 6px 24px -4px rgba(15, 23, 42, 0.08);
}

.guide-title {
  font-size: 1.12rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.guide-title i {
  color: #6366f1;
  font-size: 1.35rem;
}

.guide-desc {
  color: #64748b;
  font-size: 0.88rem;
  margin-bottom: 18px;
  line-height: 1.5;
}

.guide-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px 24px;
}

@media (max-width: 768px) {
  .guide-list {
    grid-template-columns: 1fr;
    gap: 10px;
  }
}

.guide-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  font-size: 0.86rem;
  font-weight: 600;
  color: #334155;
  line-height: 1.45;
}

.guide-item i {
  color: #10b981 !important;
  font-size: 1.15rem;
  flex-shrink: 0;
  margin-top: 1px;
}

.guest-banner-title {
  font-weight: 800;
  font-size: 1.05rem;
  color: #92400e;
}

.guest-banner-desc {
  font-size: 0.86rem;
  color: #78350f;
}

.btn-guest-login {
  background: #ffffff;
  border: 1.5px solid #d97706;
  color: #b45309;
  font-weight: 800;
  font-size: 0.86rem;
  padding: 9px 18px;
  border-radius: 12px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.btn-guest-login:hover {
  background: #fef3c7;
  color: #92400e;
  transform: translateY(-1px);
}

.btn-guest-register {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  border: 1.5px solid #d97706;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.86rem;
  padding: 9px 18px;
  border-radius: 12px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  transition: all 0.2s ease;
  box-shadow: 0 6px 16px rgba(217, 119, 6, 0.35);
}

.btn-guest-register:hover {
  background: linear-gradient(135deg, #b45309 0%, #92400e 100%);
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(217, 119, 6, 0.45);
}

/* =============================================================================
   SCHOOL SEARCH & ALGORITHMIC STATUS HUB (COMMAND COCKPIT)
   ============================================================================= */
.school-hub-card {
  background: linear-gradient(145deg, #ffffff 0%, #fafcff 60%, #fffdfa 100%);
  border: 1.5px solid #e2e8f0;
  border-radius: 24px;
  padding: 26px 30px;
  box-shadow: 0 18px 45px -12px rgba(15, 23, 42, 0.07), inset 0 1px 2px rgba(255, 255, 255, 0.9);
  position: relative;
  overflow: visible;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  margin-bottom: 32px !important;
}

.school-hub-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 22px 50px -12px rgba(15, 23, 42, 0.1), inset 0 1px 2px rgba(255, 255, 255, 1);
}

.reg-portal-header {
  margin-top: 60px !important;
  margin-bottom: 35px !important;
}

@media (max-width: 768px) {
  .reg-portal-header {
    margin-top: 42px !important;
    margin-bottom: 25px !important;
  }
}

.school-hub-ambient-glow-1 {
  position: absolute;
  right: -50px;
  top: -50px;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(245, 158, 11, 0.12) 0%, transparent 70%);
  pointer-events: none;
}

.school-hub-ambient-glow-2 {
  position: absolute;
  left: -40px;
  bottom: -40px;
  width: 240px;
  height: 240px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 70%);
  pointer-events: none;
}

.school-hub-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
}

.school-hub-icon-container {
  position: relative;
  flex-shrink: 0;
}

.school-hub-icon-aura {
  position: absolute;
  inset: -4px;
  border-radius: 22px;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.5), rgba(234, 88, 12, 0.3));
  filter: blur(10px);
}

.school-hub-icon {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 60%, #b45309 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.9rem;
  box-shadow: 0 10px 25px -4px rgba(245, 158, 11, 0.45), inset 0 2px 0 rgba(255, 255, 255, 0.4);
  border: 2px solid rgba(255, 255, 255, 0.35);
  z-index: 1;
}

.school-hub-milestone-tag {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 0.74rem;
  font-weight: 800;
  color: #b45309;
  background: #fffbeb;
  border: 1.5px solid #fde68a;
  padding: 3px 12px;
  border-radius: 9999px;
  letter-spacing: 0.4px;
}

.milestone-badge-num {
  background: #d97706;
  color: #ffffff;
  padding: 1.5px 7px;
  border-radius: 9999px;
  font-size: 0.68rem;
  font-weight: 900;
}

.milestone-badge-title {
  letter-spacing: 0.3px;
}

.school-hub-live-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.74rem;
  font-weight: 700;
  color: #b45309;
  background: #fffbeb;
  border: 1.5px solid #fde68a;
  padding: 3px 12px;
  border-radius: 9999px;
}

.school-hub-active-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.74rem;
  font-weight: 700;
  color: #047857;
  background: #ecfdf5;
  border: 1.5px solid #a7f3d0;
  padding: 3px 12px;
  border-radius: 9999px;
}

.school-hub-title {
  font-family: var(--font-heading, inherit);
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
  letter-spacing: -0.4px;
  line-height: 1.3;
}

.school-hub-desc {
  font-size: 0.92rem;
  color: #475569;
  margin-bottom: 0;
  line-height: 1.6;
  max-width: 880px;
}

/* Command Center Search Bar */
.school-search-container {
  position: relative;
  z-index: 50;
}

.school-command-search-frame {
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 2px solid #cbd5e1;
  border-radius: 18px;
  padding: 6px 8px;
  box-shadow: 0 6px 25px -4px rgba(15, 23, 42, 0.07), inset 0 1px 2px rgba(0, 0, 0, 0.02);
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.school-command-search-frame:focus-within {
  border-color: #f59e0b;
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.2), 0 12px 32px -4px rgba(245, 158, 11, 0.15);
}

.command-search-icon {
  padding-left: 14px;
  padding-right: 8px;
  color: #64748b;
  font-size: 1.35rem;
  display: flex;
  align-items: center;
}

.school-command-input {
  flex-grow: 1;
  border: none;
  background: transparent;
  padding: 12px 10px;
  font-size: 0.94rem;
  font-weight: 500;
  color: #0f172a;
  outline: none;
  min-width: 0;
}

.school-command-input::placeholder {
  color: #94a3b8;
  font-weight: 400;
  opacity: 0.85;
}

.btn-command-clear {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.3rem;
  padding: 4px 8px;
  cursor: pointer;
  transition: color 0.15s ease;
}

.btn-command-clear:hover {
  color: #ef4444;
}

.command-frame-separator {
  width: 1.5px;
  height: 32px;
  background: #e2e8f0;
  margin: 0 10px;
}

.btn-new-school-command {
  background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
  color: #ffffff;
  border: none;
  border-radius: 13px;
  padding: 11px 20px;
  font-size: 0.88rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 4px 16px -2px rgba(79, 70, 229, 0.45);
  transition: all 0.2s ease;
}

.btn-new-school-command:hover {
  background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px -2px rgba(79, 70, 229, 0.55);
  color: #ffffff;
}

/* Autocomplete Dropdown Glass */
.school-hub-dropdown-glass {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(25px);
  -webkit-backdrop-filter: blur(25px);
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  max-height: 390px;
  overflow: hidden;
  z-index: 1050;
  box-shadow: 0 30px 65px -15px rgba(15, 23, 42, 0.25);
}

.dropdown-header-bar {
  padding: 12px 20px 9px;
  background: #f8fafc;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.76rem;
  font-weight: 800;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dropdown-header-sub {
  font-size: 0.72rem;
  color: #94a3b8;
  text-transform: none;
  font-weight: 600;
}

.dropdown-items-scroll {
  max-height: 300px;
  overflow-y: auto;
}

.school-hub-result-card {
  padding: 13px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  gap: 15px;
  cursor: pointer;
  transition: all 0.15s ease;
  border-left: 3.5px solid transparent;
}

.school-hub-result-card:hover {
  background: #fffbeb;
  border-left-color: #f59e0b;
}

.result-avatar-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: #f1f5f9;
  color: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.3rem;
  flex-shrink: 0;
}

.school-result-name {
  font-weight: 800;
  font-size: 0.96rem;
  color: #0f172a;
}

.badge-jenjang {
  font-size: 0.7rem;
  font-weight: 700;
  background: #dbeafe;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  padding: 2.5px 8px;
  border-radius: 6px;
}

.badge-npsn {
  font-size: 0.7rem;
  font-weight: 600;
  background: #f8fafc;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 2.5px 8px;
  border-radius: 6px;
}

.school-result-sub {
  font-size: 0.8rem;
  color: #64748b;
  margin-top: 3px;
}

.btn-select-pill {
  background: rgba(245, 158, 11, 0.12);
  color: #b45309;
  border: 1.5px solid rgba(245, 158, 11, 0.3);
  border-radius: 9999px;
  padding: 5px 13px;
  font-size: 0.78rem;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  transition: all 0.2s ease;
}

.school-hub-result-card:hover .btn-select-pill {
  background: #f59e0b;
  color: #ffffff;
  border-color: #f59e0b;
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
}

.dropdown-footer-callout {
  padding: 13px 20px;
  font-size: 0.86rem;
  color: #4338ca;
  background: #f5f3ff;
  cursor: pointer;
  text-align: center;
  border-top: 1px solid #ede9fe;
  transition: background 0.15s ease;
}

.dropdown-footer-callout:hover {
  background: #ede9fe;
}

/* Selected School Passport Card */
.selected-school-container {
  border-top: 1px dashed #cbd5e1;
  padding-top: 24px;
}

.selected-school-passport-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  padding: 20px 24px;
  box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.06);
}

.school-passport-avatar {
  position: relative;
  width: 52px;
  height: 52px;
  border-radius: 16px;
  background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
  color: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  flex-shrink: 0;
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25);
}

.passport-verified-dot {
  position: absolute;
  bottom: -3px;
  right: -3px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  color: #10b981;
  font-size: 1.1rem;
  line-height: 1;
}

.selected-school-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.selected-school-location {
  font-size: 0.84rem;
  color: #475569;
}

.btn-change-school-hero {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #334155;
  font-size: 0.86rem;
  font-weight: 700;
  border-radius: 12px;
  padding: 9px 18px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.btn-change-school-hero:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #94a3b8;
  transform: translateY(-1px);
}

/* Status Cards & Content */
.status-box {
  border-radius: 20px;
  padding: 24px;
  margin-top: 16px;
  border: 1.5px solid transparent;
}

.status-divider {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
}

.hub-status-heading {
  font-size: 1.1rem;
  font-weight: 800;
  color: #0f172a;
}

.status-desc-text {
  color: #334155;
  font-size: 0.92rem;
  line-height: 1.65;
}

.status-strong-text {
  color: #0f172a;
  font-weight: 800;
}

.status-sub-label {
  font-size: 0.84rem;
  font-weight: 700;
  color: #475569;
}

.status-branch-header {
  font-size: 0.86rem;
  font-weight: 800;
  color: #0f172a;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-loading-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px;
  border-radius: 14px;
  margin-top: 14px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
}

.status-loading-text {
  font-size: 0.88rem;
  color: #475569;
  font-weight: 600;
}

/* Pill Badges */
.hub-pill-badge {
  font-size: 0.74rem;
  font-weight: 800;
  border-radius: 9999px;
  padding: 4px 13px;
  display: inline-flex;
  align-items: center;
}

.badge-allin-gold {
  background: #15803d;
  color: #ffffff;
}

.badge-allin-pending {
  background: #b45309;
  color: #ffffff;
}

.badge-satuan-info {
  background: #0284c7;
  color: #ffffff;
}

.badge-fresh-info {
  background: #4f46e5;
  color: #ffffff;
}

/* Verify Badges */
.hub-verify-badge {
  font-size: 0.76rem;
  font-weight: 700;
  border-radius: 10px;
  padding: 6px 13px;
  display: inline-flex;
  align-items: center;
}

.badge-verify-success {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #86efac;
}

.badge-verify-warning {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}

/* Status Card Backgrounds */
.status-allin-verified {
  background: #f0fdf4;
  border-color: #86efac;
  box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.12);
}

.status-allin-pending {
  background: #fffbeb;
  border-color: #fde68a;
  box-shadow: 0 10px 30px -5px rgba(245, 158, 11, 0.12);
}

.status-satuan {
  background: #f8fafc;
  border-color: #cbd5e1;
  box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
}

.status-fresh {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-color: #e2e8f0;
  box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05);
}

.hub-code-badge {
  background: #f1f5f9;
  color: #0f172a;
  padding: 2.5px 8px;
  border-radius: 6px;
  font-weight: 800;
  border: 1px solid #cbd5e1;
}

.status-icon-glow {
  width: 52px;
  height: 52px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-success {
  background: #dcfce7;
}

.icon-warning {
  background: #fef3c7;
}

.icon-info {
  background: #e0f2fe;
}

.icon-primary {
  background: #ede9fe;
}

/* Branch Grid Cards */
.branch-card-item {
  border-radius: 14px;
  padding: 15px;
  transition: all 0.2s ease;
}

.branch-card-registered {
  background: #ecfdf5;
  border: 1.5px solid #a7f3d0;
}

.branch-card-unregistered {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
}

.branch-item-title {
  font-size: 0.86rem;
  font-weight: 800;
  color: #0f172a;
}

.branch-item-icon {
  color: #94a3b8;
  font-size: 1.05rem;
}

.branch-item-status {
  font-size: 0.76rem;
}

.branch-item-footer {
  border-top: 1px solid #f1f5f9;
}

.btn-fill-delegate {
  width: 100%;
  background: #10b981;
  color: #ffffff;
  border: none;
  border-radius: 9px;
  padding: 7px 11px;
  font-size: 0.78rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-fill-delegate:hover {
  background: #059669;
  transform: translateY(-1px);
}

.badge-covered-allin {
  width: 100%;
  background: #10b981;
  color: #ffffff;
  border-radius: 9px;
  padding: 7px 11px;
  font-size: 0.76rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.badge-comp-closed {
  width: 100%;
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  padding: 7px 11px;
  font-size: 0.76rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Satuan Items */
.satuan-item-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  padding: 15px 19px;
  transition: all 0.2s ease;
}

.satuan-item-title {
  font-weight: 800;
  font-size: 0.92rem;
  color: #0f172a;
}

.satuan-item-sub {
  color: #64748b;
  font-size: 0.8rem;
  margin-top: 3px;
}

.satuan-item-code {
  font-family: monospace;
  color: #4f46e5;
  font-weight: 800;
}

/* Action Buttons */
.btn-upgrade-allin {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #ffffff;
  font-weight: 800;
  font-size: 0.86rem;
  border: none;
  border-radius: 12px;
  padding: 9px 18px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);
  transition: all 0.2s ease;
}

.btn-upgrade-allin:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(245, 158, 11, 0.45);
}

.btn-allin-action {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: #ffffff;
  font-weight: 800;
  font-size: 0.92rem;
  border: none;
  border-radius: 14px;
  padding: 12px 22px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(245, 158, 11, 0.4);
  transition: all 0.2s ease;
}

.btn-allin-action:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(245, 158, 11, 0.5);
}

.btn-wa-confirm {
  background: #25d366;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.86rem;
  border: none;
  border-radius: 12px;
  padding: 9px 18px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35);
  transition: all 0.2s ease;
}

.btn-wa-confirm:hover {
  background: #128c7e;
  color: #ffffff;
  transform: translateY(-1px);
}

.card-allin-free {
  border-color: #86efac !important;
  box-shadow: 0 4px 20px rgba(16, 185, 129, 0.12) !important;
}

/* =============================================================================
   DARK MODE OVERRIDES (WCAG AA COMPLIANCE & HIGH CONTRAST)
   ============================================================================= */
[data-bs-theme="dark"] .smart-account-card,
:global([data-bs-theme="dark"]) .smart-account-card,
:global(html[data-bs-theme="dark"]) .smart-account-card,
:global(.dark) .smart-account-card {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
  border: 1.5px solid rgba(99, 102, 241, 0.45) !important;
  border-top: 4px solid #818cf8 !important;
  box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .smart-account-card:hover,
:global([data-bs-theme="dark"]) .smart-account-card:hover,
:global(html[data-bs-theme="dark"]) .smart-account-card:hover,
:global(.dark) .smart-account-card:hover {
  border-top-color: #a5b4fc !important;
}

[data-bs-theme="dark"] .smart-card-top-edge,
:global([data-bs-theme="dark"]) .smart-card-top-edge,
:global(html[data-bs-theme="dark"]) .smart-card-top-edge,
:global(.dark) .smart-card-top-edge {
  background: linear-gradient(180deg, rgba(129, 140, 248, 0.15) 0%, transparent 100%) !important;
}

[data-bs-theme="dark"] .avatar-status-dot-pulse .dot-core,
:global([data-bs-theme="dark"]) .avatar-status-dot-pulse .dot-core,
:global(html[data-bs-theme="dark"]) .avatar-status-dot-pulse .dot-core,
:global(.dark) .avatar-status-dot-pulse .dot-core {
  border-color: #0f172a !important;
}

[data-bs-theme="dark"] .auth-banner-name,
:global([data-bs-theme="dark"]) .auth-banner-name,
:global(html[data-bs-theme="dark"]) .auth-banner-name,
:global(.dark) .auth-banner-name {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .badge-role-pill,
:global([data-bs-theme="dark"]) .badge-role-pill,
:global(html[data-bs-theme="dark"]) .badge-role-pill,
:global(.dark) .badge-role-pill {
  background: rgba(37, 99, 235, 0.22) !important;
  color: #93c5fd !important;
  border-color: rgba(96, 165, 250, 0.45) !important;
}

[data-bs-theme="dark"] .verified-check-icon,
:global([data-bs-theme="dark"]) .verified-check-icon,
:global(html[data-bs-theme="dark"]) .verified-check-icon,
:global(.dark) .verified-check-icon {
  color: #60a5fa !important;
}

[data-bs-theme="dark"] .badge-connected-pill,
:global([data-bs-theme="dark"]) .badge-connected-pill,
:global(html[data-bs-theme="dark"]) .badge-connected-pill,
:global(.dark) .badge-connected-pill {
  background: rgba(16, 185, 129, 0.22) !important;
  color: #86efac !important;
  border-color: rgba(134, 239, 172, 0.45) !important;
}

[data-bs-theme="dark"] .live-pulse-emerald,
:global([data-bs-theme="dark"]) .live-pulse-emerald,
:global(html[data-bs-theme="dark"]) .live-pulse-emerald,
:global(.dark) .live-pulse-emerald {
  background: #34d399 !important;
}

[data-bs-theme="dark"] .auth-banner-subtitle,
:global([data-bs-theme="dark"]) .auth-banner-subtitle,
:global(html[data-bs-theme="dark"]) .auth-banner-subtitle,
:global(.dark) .auth-banner-subtitle {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .auth-banner-school,
:global([data-bs-theme="dark"]) .auth-banner-school,
:global(html[data-bs-theme="dark"]) .auth-banner-school,
:global(.dark) .auth-banner-school {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .smart-account-footer,
:global([data-bs-theme="dark"]) .smart-account-footer,
:global(html[data-bs-theme="dark"]) .smart-account-footer,
:global(.dark) .smart-account-footer {
  border-top-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .guide-box,
:global([data-bs-theme="dark"]) .guide-box,
:global(html[data-bs-theme="dark"]) .guide-box,
:global(.dark) .guide-box {
  background: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .guide-title,
:global([data-bs-theme="dark"]) .guide-title,
:global(html[data-bs-theme="dark"]) .guide-title,
:global(.dark) .guide-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .guide-desc,
:global([data-bs-theme="dark"]) .guide-desc,
:global(html[data-bs-theme="dark"]) .guide-desc,
:global(.dark) .guide-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .guide-item,
:global([data-bs-theme="dark"]) .guide-item,
:global(html[data-bs-theme="dark"]) .guide-item,
:global(.dark) .guide-item {
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .smart-guest-card,
:global([data-bs-theme="dark"]) .smart-guest-card,
:global(html[data-bs-theme="dark"]) .smart-guest-card,
:global(.dark) .smart-guest-card {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.18) 0%, rgba(30, 41, 59, 0.95) 100%) !important;
  border-color: rgba(245, 158, 11, 0.4) !important;
}

[data-bs-theme="dark"] .guest-banner-title,
:global([data-bs-theme="dark"]) .guest-banner-title,
:global(html[data-bs-theme="dark"]) .guest-banner-title,
:global(.dark) .guest-banner-title {
  color: #fde68a !important;
}

[data-bs-theme="dark"] .guest-banner-desc,
:global([data-bs-theme="dark"]) .guest-banner-desc,
:global(html[data-bs-theme="dark"]) .guest-banner-desc,
:global(.dark) .guest-banner-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .btn-guest-login,
:global([data-bs-theme="dark"]) .btn-guest-login,
:global(html[data-bs-theme="dark"]) .btn-guest-login,
:global(.dark) .btn-guest-login {
  background: rgba(245, 158, 11, 0.18) !important;
  border-color: #f59e0b !important;
  color: #fde68a !important;
}
</style>

<style>
/* =============================================================================
   SEMARAK ALIYAH - REGISTRATION MODAL GLOBAL & THEME OVERRIDES (STRICT UI COMPLIANT)
   ============================================================================= */

/* --- 1. LIGHT MODE BASE & HIGH CONTRAST (Rule 2 & 3) --- */
.reg-modal-card {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 20px !important;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.25) !important;
}

.reg-modal-header {
  background: #ffffff !important;
  border-bottom: 1px solid #e2e8f0 !important;
}

.reg-modal-title {
  color: #0f172a !important;
}

.reg-modal-subtitle {
  color: #64748b !important;
}

.reg-modal-close,
.btn-modal-close {
  background: #f1f5f9 !important;
  color: #475569 !important;
  border: 1px solid #cbd5e1 !important;
}

.reg-modal-close:hover,
.btn-modal-close:hover {
  background: #fee2e2 !important;
  color: #ef4444 !important;
}

.reg-modal-body {
  background: #f8fafc !important;
}

.reg-section-card {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
}

.reg-section-title {
  color: #0f172a !important;
}

.reg-section-desc {
  color: #64748b !important;
}

.reg-modal-card .form-label {
  color: #0f172a !important;
  font-weight: 700 !important;
}

.reg-modal-card .form-control {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1.5px solid #cbd5e1 !important;
}

.reg-modal-card .form-control:focus {
  border-color: #8b5cf6 !important;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15) !important;
}

.reg-package-item {
  background: #ffffff !important;
  border: 1.5px solid #e2e8f0 !important;
}

.reg-package-item:hover {
  background: #f8fafc !important;
  border-color: #cbd5e1 !important;
}

.reg-package-item.is-selected {
  background: #f5f3ff !important;
  border-color: #8b5cf6 !important;
  box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15) !important;
}

.reg-pkg-name {
  color: #0f172a !important;
}

.gender-segmented-btn {
  background: #ffffff !important;
  border: 1.5px solid #cbd5e1 !important;
  color: #334155 !important;
}

.gender-segmented-btn.is-selected {
  background: #ecfdf5 !important;
  border-color: #10b981 !important;
  color: #065f46 !important;
  font-weight: 700 !important;
}

.reg-student-card {
  background: #f8fafc !important;
  border: 1.5px solid #e2e8f0 !important;
}

.reg-modal-footer {
  background: #ffffff !important;
  border-top: 1px solid #e2e8f0 !important;
}

.btn-cancel {
  background: #f1f5f9 !important;
  border: 1px solid #cbd5e1 !important;
  color: #475569 !important;
}

.btn-cancel:hover {
  background: #e2e8f0 !important;
  color: #1e293b !important;
}

/* --- 2. DARK MODE OVERRIDES (Rule 2 & 3) --- */
[data-bs-theme="dark"] .reg-modal-overlay,
html[data-bs-theme="dark"] .reg-modal-overlay {
  background: rgba(8, 12, 22, 0.78) !important;
}

[data-bs-theme="dark"] .reg-modal-card,
html[data-bs-theme="dark"] .reg-modal-card {
  background: #0f172a !important;
  color: #f8fafc !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.85) !important;
}

[data-bs-theme="dark"] .reg-modal-header,
html[data-bs-theme="dark"] .reg-modal-header {
  background: #111827 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .reg-modal-title,
html[data-bs-theme="dark"] .reg-modal-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .reg-modal-subtitle,
html[data-bs-theme="dark"] .reg-modal-subtitle {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .reg-modal-close,
[data-bs-theme="dark"] .btn-modal-close,
html[data-bs-theme="dark"] .reg-modal-close,
html[data-bs-theme="dark"] .btn-modal-close {
  background: #1e293b !important;
  color: #cbd5e1 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .reg-modal-close:hover,
[data-bs-theme="dark"] .btn-modal-close:hover,
html[data-bs-theme="dark"] .reg-modal-close:hover,
html[data-bs-theme="dark"] .btn-modal-close:hover {
  background: #ef4444 !important;
  color: #ffffff !important;
  border-color: #ef4444 !important;
}

/* Modal Body & Scrollbar */
[data-bs-theme="dark"] .reg-modal-body,
html[data-bs-theme="dark"] .reg-modal-body {
  background: #090e1a !important;
}

[data-bs-theme="dark"] .reg-modal-body::-webkit-scrollbar-thumb,
html[data-bs-theme="dark"] .reg-modal-body::-webkit-scrollbar-thumb {
  background: #334155 !important;
}

[data-bs-theme="dark"] .reg-modal-body::-webkit-scrollbar-thumb:hover,
html[data-bs-theme="dark"] .reg-modal-body::-webkit-scrollbar-thumb:hover {
  background: #475569 !important;
}

/* Section Cards */
[data-bs-theme="dark"] .reg-section-card,
html[data-bs-theme="dark"] .reg-section-card {
  background: #111827 !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
}

[data-bs-theme="dark"] .reg-section-header,
html[data-bs-theme="dark"] .reg-section-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
}

[data-bs-theme="dark"] .reg-section-title,
html[data-bs-theme="dark"] .reg-section-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .reg-section-desc,
html[data-bs-theme="dark"] .reg-section-desc {
  color: #94a3b8 !important;
}

/* Badges & Tags (Rule 3) */
[data-bs-theme="dark"] .tag-pkg,
html[data-bs-theme="dark"] .tag-pkg {
  background: rgba(236, 72, 153, 0.18) !important;
  color: #f472b6 !important;
  border: 1px solid rgba(244, 114, 182, 0.4) !important;
}

[data-bs-theme="dark"] .tag-cat,
html[data-bs-theme="dark"] .tag-cat {
  background: rgba(16, 185, 129, 0.18) !important;
  color: #6ee7b7 !important;
  border: 1px solid rgba(110, 231, 183, 0.4) !important;
}

[data-bs-theme="dark"] .tag-team,
html[data-bs-theme="dark"] .tag-team {
  background: rgba(139, 92, 246, 0.2) !important;
  color: #c4b5fd !important;
  border: 1px solid rgba(196, 181, 253, 0.4) !important;
}

/* Package Selector Items */
[data-bs-theme="dark"] .reg-package-item,
html[data-bs-theme="dark"] .reg-package-item {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .reg-package-item:hover,
html[data-bs-theme="dark"] .reg-package-item:hover {
  background: #273549 !important;
  border-color: rgba(255, 255, 255, 0.22) !important;
}

[data-bs-theme="dark"] .reg-package-item.is-selected,
html[data-bs-theme="dark"] .reg-package-item.is-selected {
  background: rgba(139, 92, 246, 0.24) !important;
  border-color: #a78bfa !important;
  box-shadow: 0 0 0 2px rgba(167, 139, 250, 0.3) !important;
}

[data-bs-theme="dark"] .reg-pkg-name,
html[data-bs-theme="dark"] .reg-pkg-name {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .reg-pkg-note,
[data-bs-theme="dark"] .price-sub,
html[data-bs-theme="dark"] .reg-pkg-note,
html[data-bs-theme="dark"] .price-sub {
  color: #94a3b8 !important;
}

/* Form Controls & Labels (Rule 2) */
[data-bs-theme="dark"] .reg-modal-card .form-label,
html[data-bs-theme="dark"] .reg-modal-card .form-label {
  color: #f1f5f9 !important;
  font-weight: 700 !important;
}

[data-bs-theme="dark"] .reg-modal-card .form-control,
html[data-bs-theme="dark"] .reg-modal-card .form-control {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.16) !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .reg-modal-card .form-control:focus,
html[data-bs-theme="dark"] .reg-modal-card .form-control:focus {
  border-color: #a78bfa !important;
  box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.25) !important;
}

[data-bs-theme="dark"] .reg-modal-card .field-hint,
html[data-bs-theme="dark"] .reg-modal-card .field-hint {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .reg-modal-card .optional-tag,
html[data-bs-theme="dark"] .reg-modal-card .optional-tag {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .reg-modal-card .input-icon,
html[data-bs-theme="dark"] .reg-modal-card .input-icon {
  color: #94a3b8 !important;
}

/* School Autocomplete Dropdown */
[data-bs-theme="dark"] .school-autocomplete-dropdown,
html[data-bs-theme="dark"] .school-autocomplete-dropdown {
  background: #1e293b !important;
  border: 1.5px solid rgba(16, 185, 129, 0.5) !important;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.6) !important;
}

[data-bs-theme="dark"] .school-suggestion-item,
html[data-bs-theme="dark"] .school-suggestion-item {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .school-suggestion-item:hover,
html[data-bs-theme="dark"] .school-suggestion-item:hover {
  background: rgba(16, 185, 129, 0.18) !important;
}

[data-bs-theme="dark"] .school-name,
html[data-bs-theme="dark"] .school-name {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .school-meta,
html[data-bs-theme="dark"] .school-meta {
  color: #94a3b8 !important;
}

/* Card 2: Package Code Dark Mode */
[data-bs-theme="dark"] .package-code-card,
html[data-bs-theme="dark"] .package-code-card {
  background: rgba(30, 41, 59, 0.7) !important;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .package-code-title,
html[data-bs-theme="dark"] .package-code-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .package-code-badge,
html[data-bs-theme="dark"] .package-code-badge {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
  border: 1px solid rgba(245, 158, 11, 0.4) !important;
}

[data-bs-theme="dark"] .package-code-desc,
html[data-bs-theme="dark"] .package-code-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .code-verified-alert,
html[data-bs-theme="dark"] .code-verified-alert {
  background: rgba(16, 185, 129, 0.15) !important;
  border: 1.5px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .code-verified-alert .alert-headline,
html[data-bs-theme="dark"] .code-verified-alert .alert-headline {
  color: #34d399 !important;
}

[data-bs-theme="dark"] .code-verified-alert .alert-details,
html[data-bs-theme="dark"] .code-verified-alert .alert-details {
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .code-verified-alert .alert-details code,
html[data-bs-theme="dark"] .code-verified-alert .alert-details code {
  background: rgba(16, 185, 129, 0.3) !important;
  color: #6ee7b7 !important;
}

[data-bs-theme="dark"] .code-verified-alert .alert-subnote,
html[data-bs-theme="dark"] .code-verified-alert .alert-subnote {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .code-error-alert,
html[data-bs-theme="dark"] .code-error-alert {
  background: rgba(239, 68, 68, 0.18) !important;
  border: 1.5px solid rgba(239, 68, 68, 0.4) !important;
  color: #fca5a5 !important;
}

[data-bs-theme="dark"] .package-code-help,
html[data-bs-theme="dark"] .package-code-help {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .btn-clear-code,
html[data-bs-theme="dark"] .btn-clear-code {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .btn-clear-code:hover,
html[data-bs-theme="dark"] .btn-clear-code:hover {
  background: rgba(239, 68, 68, 0.25) !important;
  color: #fca5a5 !important;
}

/* Dark Mode: Verified School Summary Card */
[data-bs-theme="dark"] .verified-school-summary-card,
html[data-bs-theme="dark"] .verified-school-summary-card {
  background: rgba(16, 185, 129, 0.12) !important;
  border: 1.5px solid rgba(16, 185, 129, 0.35) !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3) !important;
}

[data-bs-theme="dark"] .verified-school-title,
html[data-bs-theme="dark"] .verified-school-title {
  color: #6ee7b7 !important;
}

[data-bs-theme="dark"] .verified-school-subtitle,
html[data-bs-theme="dark"] .verified-school-subtitle {
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .btn-unlink-school,
html[data-bs-theme="dark"] .btn-unlink-school {
  background: #1e293b !important;
  color: #6ee7b7 !important;
  border: 1px solid rgba(16, 185, 129, 0.4) !important;
}

[data-bs-theme="dark"] .btn-unlink-school:hover,
html[data-bs-theme="dark"] .btn-unlink-school:hover {
  background: rgba(239, 68, 68, 0.2) !important;
  color: #fca5a5 !important;
  border-color: rgba(239, 68, 68, 0.5) !important;
}

[data-bs-theme="dark"] .verified-school-details-grid,
html[data-bs-theme="dark"] .verified-school-details-grid {
  background: #0f172a !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .detail-box,
html[data-bs-theme="dark"] .detail-box {
  background: #1e293b !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .detail-box.school-main,
html[data-bs-theme="dark"] .detail-box.school-main {
  background: rgba(16, 185, 129, 0.15) !important;
  border-color: rgba(16, 185, 129, 0.3) !important;
}

[data-bs-theme="dark"] .detail-label,
html[data-bs-theme="dark"] .detail-label {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .detail-val,
html[data-bs-theme="dark"] .detail-val {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .school-name-val,
html[data-bs-theme="dark"] .school-name-val {
  color: #6ee7b7 !important;
}

[data-bs-theme="dark"] .mentor-section-divider,
html[data-bs-theme="dark"] .mentor-section-divider {
  border-top-color: rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .mentor-section-title,
html[data-bs-theme="dark"] .mentor-section-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .mentor-section-subtitle,
html[data-bs-theme="dark"] .mentor-section-subtitle {
  color: #cbd5e1 !important;
}

/* Card 3: Roster Toolbar & Student Cards */
[data-bs-theme="dark"] .reg-roster-toolbar,
html[data-bs-theme="dark"] .reg-roster-toolbar {
  background: rgba(16, 185, 129, 0.12) !important;
  border: 1.5px solid rgba(16, 185, 129, 0.35) !important;
}

[data-bs-theme="dark"] .toolbar-title,
html[data-bs-theme="dark"] .toolbar-title {
  color: #6ee7b7 !important;
}

[data-bs-theme="dark"] .toolbar-quota-desc,
[data-bs-theme="dark"] .core-tag,
html[data-bs-theme="dark"] .toolbar-quota-desc,
html[data-bs-theme="dark"] .core-tag {
  color: #a7f3d0 !important;
}

[data-bs-theme="dark"] .toolbar-rule-alert,
html[data-bs-theme="dark"] .toolbar-rule-alert {
  background: rgba(245, 158, 11, 0.18) !important;
  color: #fde047 !important;
  border: 1px solid rgba(245, 158, 11, 0.4) !important;
}

[data-bs-theme="dark"] .roster-stepper,
html[data-bs-theme="dark"] .roster-stepper {
  background: #0f172a !important;
  border: 1.5px solid rgba(16, 185, 129, 0.4) !important;
}

[data-bs-theme="dark"] .stepper-display,
html[data-bs-theme="dark"] .stepper-display {
  color: #34d399 !important;
}

[data-bs-theme="dark"] .stepper-btn,
html[data-bs-theme="dark"] .stepper-btn {
  background: #1e293b !important;
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .stepper-btn:hover:not(:disabled),
html[data-bs-theme="dark"] .stepper-btn:hover:not(:disabled) {
  background: #10b981 !important;
  color: #ffffff !important;
  border-color: #10b981 !important;
}

[data-bs-theme="dark"] .reg-student-card,
html[data-bs-theme="dark"] .reg-student-card {
  background: #0f172a !important;
  border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .reg-student-card:hover,
html[data-bs-theme="dark"] .reg-student-card:hover {
  border-color: rgba(255, 255, 255, 0.2) !important;
}

[data-bs-theme="dark"] .student-card-header,
html[data-bs-theme="dark"] .student-card-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .student-card-idx,
html[data-bs-theme="dark"] .student-card-idx {
  color: #fbbf24 !important;
}

[data-bs-theme="dark"] .role-inti,
html[data-bs-theme="dark"] .role-inti {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
  border-color: rgba(134, 239, 172, 0.4) !important;
}

[data-bs-theme="dark"] .role-cadangan,
html[data-bs-theme="dark"] .role-cadangan {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
  border-color: rgba(253, 224, 71, 0.4) !important;
}

[data-bs-theme="dark"] .captain-badge,
html[data-bs-theme="dark"] .captain-badge {
  background: rgba(245, 158, 11, 0.25) !important;
  color: #fde047 !important;
  border-color: rgba(253, 224, 71, 0.45) !important;
}

[data-bs-theme="dark"] .captain-toggle-label,
html[data-bs-theme="dark"] .captain-toggle-label {
  background: #1e293b !important;
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .captain-toggle-label.is-active,
html[data-bs-theme="dark"] .captain-toggle-label.is-active {
  background: rgba(245, 158, 11, 0.25) !important;
  color: #fde047 !important;
  border-color: #f59e0b !important;
}

/* Gender Segmented Controls */
[data-bs-theme="dark"] .gender-segmented-btn,
html[data-bs-theme="dark"] .gender-segmented-btn {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.14) !important;
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .gender-segmented-btn:hover,
html[data-bs-theme="dark"] .gender-segmented-btn:hover {
  background: #273549 !important;
}

[data-bs-theme="dark"] .gender-segmented-btn.is-selected,
html[data-bs-theme="dark"] .gender-segmented-btn.is-selected {
  background: rgba(16, 185, 129, 0.25) !important;
  border-color: #10b981 !important;
  color: #86efac !important;
}

/* Captain WA Alert Card */
[data-bs-theme="dark"] .captain-wa-alert-card,
html[data-bs-theme="dark"] .captain-wa-alert-card {
  background: rgba(245, 158, 11, 0.15) !important;
  border: 1.5px solid rgba(245, 158, 11, 0.5) !important;
}

[data-bs-theme="dark"] .captain-wa-label,
html[data-bs-theme="dark"] .captain-wa-label {
  color: #fde047 !important;
}

[data-bs-theme="dark"] .captain-wa-hint,
html[data-bs-theme="dark"] .captain-wa-hint {
  color: #fcd34d !important;
}

/* Dynamic Options & Dropzones */
[data-bs-theme="dark"] .reg-option-card,
html[data-bs-theme="dark"] .reg-option-card {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .reg-option-card:hover,
html[data-bs-theme="dark"] .reg-option-card:hover {
  background: #273549 !important;
  border-color: rgba(255, 255, 255, 0.22) !important;
}

[data-bs-theme="dark"] .reg-option-card.is-selected,
html[data-bs-theme="dark"] .reg-option-card.is-selected {
  background: rgba(139, 92, 246, 0.25) !important;
  border-color: #a78bfa !important;
  color: #c4b5fd !important;
}

[data-bs-theme="dark"] .reg-file-dropzone-box,
html[data-bs-theme="dark"] .reg-file-dropzone-box {
  background: #0f172a !important;
  border: 1.5px dashed rgba(255, 255, 255, 0.18) !important;
}

[data-bs-theme="dark"] .dropzone-title,
html[data-bs-theme="dark"] .dropzone-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .dropzone-spec,
html[data-bs-theme="dark"] .dropzone-spec {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .reg-file-dropzone-box.has-file,
html[data-bs-theme="dark"] .reg-file-dropzone-box.has-file {
  background: rgba(16, 185, 129, 0.15) !important;
  border-color: #10b981 !important;
}

[data-bs-theme="dark"] .file-name,
html[data-bs-theme="dark"] .file-name {
  color: #86efac !important;
}

[data-bs-theme="dark"] .file-icon-box,
html[data-bs-theme="dark"] .file-icon-box {
  background: rgba(16, 185, 129, 0.3) !important;
  color: #34d399 !important;
}

[data-bs-theme="dark"] .file-size-status,
html[data-bs-theme="dark"] .file-size-status {
  color: #6ee7b7 !important;
}

[data-bs-theme="dark"] .file-spec-tag,
html[data-bs-theme="dark"] .file-spec-tag {
  background: rgba(14, 165, 233, 0.2) !important;
  color: #7dd3fc !important;
  border-color: rgba(125, 211, 252, 0.4) !important;
}

/* Modal Footer */
[data-bs-theme="dark"] .reg-modal-footer,
html[data-bs-theme="dark"] .reg-modal-footer {
  background: #111827 !important;
  border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
  box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.4) !important;
}

[data-bs-theme="dark"] .footer-fee-row .fee-label,
html[data-bs-theme="dark"] .footer-fee-row .fee-label {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .footer-fee-row .fee-amount,
html[data-bs-theme="dark"] .footer-fee-row .fee-amount {
  color: #34d399 !important;
}

[data-bs-theme="dark"] .footer-bank-info,
html[data-bs-theme="dark"] .footer-bank-info {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .bank-dot,
html[data-bs-theme="dark"] .bank-dot {
  color: #475569 !important;
}

[data-bs-theme="dark"] .bank-acc,
html[data-bs-theme="dark"] .bank-acc {
  color: #fbbf24 !important;
}

[data-bs-theme="dark"] .btn-cancel,
html[data-bs-theme="dark"] .btn-cancel {
  background: #1e293b !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .btn-cancel:hover,
html[data-bs-theme="dark"] .btn-cancel:hover {
  background: #273549 !important;
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.25) !important;
}

/* Success View */
[data-bs-theme="dark"] .reg-success-title,
html[data-bs-theme="dark"] .reg-success-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .reg-success-desc,
html[data-bs-theme="dark"] .reg-success-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .reg-success-summary-box,
html[data-bs-theme="dark"] .reg-success-summary-box {
  background: #131c2e !important;
  border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .summary-row,
html[data-bs-theme="dark"] .summary-row {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .summary-label,
html[data-bs-theme="dark"] .summary-label {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .summary-val,
html[data-bs-theme="dark"] .summary-val {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .summary-val.code-val,
html[data-bs-theme="dark"] .summary-val.code-val {
  color: #a78bfa !important;
}

[data-bs-theme="dark"] .summary-val.highlight-val,
html[data-bs-theme="dark"] .summary-val.highlight-val {
  color: #fbbf24 !important;
}

[data-bs-theme="dark"] .summary-val.fee-val,
html[data-bs-theme="dark"] .summary-val.fee-val {
  color: #34d399 !important;
}

/* =============================================================================
   SCHOOL SEARCH & ALGORITHMIC STATUS HUB (COMMAND COCKPIT) - FULL THEME OVERRIDES
   Compliant with Mandatory UI/UX Rules: Contrast WCAG AA, Luminous Dark Badges, Full Theme Support
   ============================================================================= */

/* --- 1. LIGHT MODE (Crisp, High Contrast, Deep Saturated Badges) --- */
.school-hub-card {
  background: linear-gradient(145deg, #ffffff 0%, #fafcff 60%, #fffdfa 100%) !important;
  border: 1.5px solid #e2e8f0 !important;
  box-shadow: 0 18px 45px -12px rgba(15, 23, 42, 0.08) !important;
}

.school-hub-title {
  color: #0f172a !important;
}

.school-hub-desc {
  color: #334155 !important;
}

.school-hub-milestone-tag {
  background: #fffbeb !important;
  color: #92400e !important;
  border: 1.5px solid #fde68a !important;
}

.milestone-badge-num {
  background: #d97706 !important;
  color: #ffffff !important;
}

.school-hub-active-badge {
  background: #ecfdf5 !important;
  color: #065f46 !important;
  border: 1.5px solid #a7f3d0 !important;
}

.school-hub-live-badge {
  background: #fffbeb !important;
  color: #92400e !important;
  border: 1.5px solid #fde68a !important;
}

.school-command-search-frame {
  background: #ffffff !important;
  border: 2px solid #cbd5e1 !important;
  box-shadow: 0 6px 25px -4px rgba(15, 23, 42, 0.07) !important;
}

.school-command-input {
  color: #0f172a !important;
}

.school-command-input::placeholder {
  color: #64748b !important;
}

.command-search-icon {
  color: #64748b !important;
}

.btn-command-clear {
  color: #94a3b8 !important;
}

.btn-command-clear:hover {
  color: #ef4444 !important;
}

.command-frame-separator {
  background: #e2e8f0 !important;
}

.selected-school-container {
  border-top: 1px dashed #cbd5e1 !important;
}

.selected-school-passport-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
  border: 2px solid #e2e8f0 !important;
  box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.06) !important;
}

.selected-school-name {
  color: #0f172a !important;
}

.selected-school-location {
  color: #334155 !important;
}

.btn-change-school-hero {
  background: #ffffff !important;
  border: 1.5px solid #cbd5e1 !important;
  color: #1e293b !important;
}

.btn-change-school-hero:hover {
  background: #f1f5f9 !important;
  color: #0f172a !important;
  border-color: #94a3b8 !important;
}

.status-divider {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
}

.hub-status-heading {
  color: #0f172a !important;
}

.status-desc-text {
  color: #1e293b !important;
}

.status-strong-text {
  color: #0f172a !important;
}

.hub-code-badge {
  background: #f1f5f9 !important;
  color: #0f172a !important;
  border: 1px solid #cbd5e1 !important;
}

/* Light Mode Status Boxes */
.status-box.status-allin-pending {
  background: #fffbeb !important;
  border: 1.5px solid #fde68a !important;
  box-shadow: 0 10px 30px -5px rgba(245, 158, 11, 0.12) !important;
}

.status-box.status-allin-verified {
  background: #f0fdf4 !important;
  border: 1.5px solid #86efac !important;
  box-shadow: 0 10px 30px -5px rgba(16, 185, 129, 0.12) !important;
}

.status-box.status-satuan {
  background: #f8fafc !important;
  border: 1.5px solid #cbd5e1 !important;
  box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05) !important;
}

.status-box.status-fresh {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
  border: 1.5px solid #e2e8f0 !important;
  box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05) !important;
}

/* Light Mode Status Icons */
.status-icon-glow.icon-warning {
  background: #fef3c7 !important;
}

.status-icon-glow.icon-success {
  background: #dcfce7 !important;
}

.status-icon-glow.icon-info {
  background: #e0f2fe !important;
}

.status-icon-glow.icon-primary {
  background: #ede9fe !important;
}

/* Light Mode Badges */
.hub-pill-badge.badge-allin-pending {
  background: #b45309 !important;
  color: #ffffff !important;
}

.hub-pill-badge.badge-allin-gold {
  background: #15803d !important;
  color: #ffffff !important;
}

.hub-pill-badge.badge-satuan-info {
  background: #0284c7 !important;
  color: #ffffff !important;
}

.hub-pill-badge.badge-fresh-info {
  background: #4f46e5 !important;
  color: #ffffff !important;
}

.hub-verify-badge.badge-verify-warning {
  background: #fef3c7 !important;
  color: #92400e !important;
  border: 1px solid #fde68a !important;
}

.hub-verify-badge.badge-verify-success {
  background: #dcfce7 !important;
  color: #15803d !important;
  border: 1px solid #86efac !important;
}

.badge-jenjang {
  background: #dbeafe !important;
  color: #1e40af !important;
  border: 1px solid #bfdbfe !important;
}

.badge-npsn {
  background: #f8fafc !important;
  color: #334155 !important;
  border: 1px solid #cbd5e1 !important;
}

/* Light Mode Autocomplete Dropdown */
.school-hub-dropdown-glass {
  background: rgba(255, 255, 255, 0.98) !important;
  border: 2px solid #e2e8f0 !important;
}

.dropdown-header-bar {
  background: #f8fafc !important;
  color: #475569 !important;
  border-bottom: 1px solid #f1f5f9 !important;
}

.dropdown-header-sub {
  color: #94a3b8 !important;
}

.school-hub-result-card {
  border-bottom: 1px solid #f1f5f9 !important;
}

.school-hub-result-card:hover {
  background: #fffbeb !important;
  border-left-color: #f59e0b !important;
}

.result-avatar-box {
  background: #f1f5f9 !important;
  color: #f59e0b !important;
}

.school-result-name {
  color: #0f172a !important;
}

.school-result-sub {
  color: #64748b !important;
}

.btn-select-pill {
  background: rgba(245, 158, 11, 0.12) !important;
  color: #b45309 !important;
  border: 1.5px solid rgba(245, 158, 11, 0.3) !important;
}

.dropdown-footer-callout {
  background: #f5f3ff !important;
  color: #4338ca !important;
  border-top: 1px solid #ede9fe !important;
}

.dropdown-footer-callout:hover {
  background: #ede9fe !important;
}

/* Light Mode Branch Grid & Satuan */
.status-branch-header {
  color: #0f172a !important;
}

.branch-card-registered {
  background: #ecfdf5 !important;
  border: 1.5px solid #a7f3d0 !important;
}

.branch-card-unregistered {
  background: #ffffff !important;
  border: 1.5px solid #e2e8f0 !important;
}

.branch-item-title {
  color: #0f172a !important;
}

.branch-item-icon {
  color: #94a3b8 !important;
}

.branch-item-footer {
  border-top: 1px solid #f1f5f9 !important;
}

.badge-covered-allin {
  background: #10b981 !important;
  color: #ffffff !important;
}

.badge-comp-closed {
  background: #f1f5f9 !important;
  color: #64748b !important;
  border: 1px solid #e2e8f0 !important;
}

.status-sub-label {
  color: #475569 !important;
}

.satuan-item-card {
  background: #ffffff !important;
  border: 1.5px solid #e2e8f0 !important;
}

.satuan-item-title {
  color: #0f172a !important;
}

.satuan-item-sub {
  color: #475569 !important;
}

.satuan-item-code {
  color: #4f46e5 !important;
}

.status-loading-box {
  background: #f8fafc !important;
  border: 1.5px solid #e2e8f0 !important;
}

.status-loading-text {
  color: #475569 !important;
}


/* --- 2. DARK MODE (Sleek Slate, High Contrast Text, Luminous Badges) --- */
[data-bs-theme="dark"] .school-hub-card,
html[data-bs-theme="dark"] .school-hub-card {
  background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%) !important;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 24px 50px -12px rgba(0, 0, 0, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .school-hub-title,
html[data-bs-theme="dark"] .school-hub-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .school-hub-desc,
html[data-bs-theme="dark"] .school-hub-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .school-hub-milestone-tag,
html[data-bs-theme="dark"] .school-hub-milestone-tag {
  background: rgba(245, 158, 11, 0.18) !important;
  color: #fde047 !important;
  border: 1.5px solid rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .milestone-badge-num,
html[data-bs-theme="dark"] .milestone-badge-num {
  background: #d97706 !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .school-hub-active-badge,
html[data-bs-theme="dark"] .school-hub-active-badge {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
  border: 1.5px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .school-hub-live-badge,
html[data-bs-theme="dark"] .school-hub-live-badge {
  background: rgba(245, 158, 11, 0.18) !important;
  color: #fde047 !important;
  border: 1.5px solid rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .school-command-search-frame,
html[data-bs-theme="dark"] .school-command-search-frame {
  background: #1e293b !important;
  border: 2px solid rgba(255, 255, 255, 0.18) !important;
  box-shadow: 0 6px 24px -2px rgba(0, 0, 0, 0.45) !important;
}

[data-bs-theme="dark"] .school-command-search-frame:focus-within,
html[data-bs-theme="dark"] .school-command-search-frame:focus-within {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25) !important;
}

[data-bs-theme="dark"] .school-command-input,
html[data-bs-theme="dark"] .school-command-input {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .school-command-input::placeholder,
html[data-bs-theme="dark"] .school-command-input::placeholder {
  color: #64748b !important;
  opacity: 0.85 !important;
}

[data-bs-theme="dark"] .command-search-icon,
html[data-bs-theme="dark"] .command-search-icon {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .btn-command-clear,
html[data-bs-theme="dark"] .btn-command-clear {
  color: #64748b !important;
}

[data-bs-theme="dark"] .btn-command-clear:hover,
html[data-bs-theme="dark"] .btn-command-clear:hover {
  color: #f87171 !important;
}

[data-bs-theme="dark"] .command-frame-separator,
html[data-bs-theme="dark"] .command-frame-separator {
  background: rgba(255, 255, 255, 0.16) !important;
}

[data-bs-theme="dark"] .selected-school-container,
html[data-bs-theme="dark"] .selected-school-container {
  border-color: rgba(255, 255, 255, 0.16) !important;
}

[data-bs-theme="dark"] .selected-school-passport-card,
html[data-bs-theme="dark"] .selected-school-passport-card {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
  border: 2px solid rgba(255, 255, 255, 0.14) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .school-passport-avatar,
html[data-bs-theme="dark"] .school-passport-avatar {
  background: linear-gradient(135deg, #334155 0%, #1e293b 100%) !important;
  color: #fbbf24 !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
}

[data-bs-theme="dark"] .selected-school-name,
html[data-bs-theme="dark"] .selected-school-name {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .selected-school-location,
html[data-bs-theme="dark"] .selected-school-location {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .btn-change-school-hero,
html[data-bs-theme="dark"] .btn-change-school-hero {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.2) !important;
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .btn-change-school-hero:hover,
html[data-bs-theme="dark"] .btn-change-school-hero:hover {
  background: #334155 !important;
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.35) !important;
}

[data-bs-theme="dark"] .status-divider,
html[data-bs-theme="dark"] .status-divider {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .hub-status-heading,
html[data-bs-theme="dark"] .hub-status-heading {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .status-desc-text,
html[data-bs-theme="dark"] .status-desc-text {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .status-strong-text,
html[data-bs-theme="dark"] .status-strong-text {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .hub-code-badge,
html[data-bs-theme="dark"] .hub-code-badge {
  background: rgba(0, 0, 0, 0.45) !important;
  color: #fde047 !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
}

/* Dark Mode Status Boxes */
[data-bs-theme="dark"] .status-box.status-allin-pending,
html[data-bs-theme="dark"] .status-box.status-allin-pending {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.14) 0%, rgba(30, 41, 59, 0.85) 100%) !important;
  border: 1.5px solid rgba(245, 158, 11, 0.42) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .status-box.status-allin-verified,
html[data-bs-theme="dark"] .status-box.status-allin-verified {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.14) 0%, rgba(30, 41, 59, 0.85) 100%) !important;
  border: 1.5px solid rgba(16, 185, 129, 0.42) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .status-box.status-satuan,
html[data-bs-theme="dark"] .status-box.status-satuan {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.12) 0%, rgba(30, 41, 59, 0.85) 100%) !important;
  border: 1.5px solid rgba(14, 165, 233, 0.4) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .status-box.status-fresh,
html[data-bs-theme="dark"] .status-box.status-fresh {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(30, 41, 59, 0.85) 100%) !important;
  border: 1.5px solid rgba(99, 102, 241, 0.4) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

/* Dark Mode Status Icons Glow */
[data-bs-theme="dark"] .status-icon-glow.icon-warning,
html[data-bs-theme="dark"] .status-icon-glow.icon-warning {
  background: rgba(245, 158, 11, 0.22) !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .status-icon-glow.icon-success,
html[data-bs-theme="dark"] .status-icon-glow.icon-success {
  background: rgba(16, 185, 129, 0.22) !important;
  border: 1px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .status-icon-glow.icon-info,
html[data-bs-theme="dark"] .status-icon-glow.icon-info {
  background: rgba(14, 165, 233, 0.22) !important;
  border: 1px solid rgba(14, 165, 233, 0.45) !important;
}

[data-bs-theme="dark"] .status-icon-glow.icon-primary,
html[data-bs-theme="dark"] .status-icon-glow.icon-primary {
  background: rgba(99, 102, 241, 0.22) !important;
  border: 1px solid rgba(99, 102, 241, 0.45) !important;
}

/* Dark Mode Badges (Luminous Glow WCAG AA) */
[data-bs-theme="dark"] .hub-pill-badge.badge-allin-pending,
html[data-bs-theme="dark"] .hub-pill-badge.badge-allin-pending {
  background: rgba(245, 158, 11, 0.25) !important;
  color: #fde047 !important;
  border: 1px solid rgba(245, 158, 11, 0.5) !important;
}

[data-bs-theme="dark"] .hub-pill-badge.badge-allin-gold,
html[data-bs-theme="dark"] .hub-pill-badge.badge-allin-gold {
  background: rgba(16, 185, 129, 0.25) !important;
  color: #86efac !important;
  border: 1px solid rgba(16, 185, 129, 0.5) !important;
}

[data-bs-theme="dark"] .hub-pill-badge.badge-satuan-info,
html[data-bs-theme="dark"] .hub-pill-badge.badge-satuan-info {
  background: rgba(14, 165, 233, 0.25) !important;
  color: #7dd3fc !important;
  border: 1px solid rgba(14, 165, 233, 0.5) !important;
}

[data-bs-theme="dark"] .hub-pill-badge.badge-fresh-info,
html[data-bs-theme="dark"] .hub-pill-badge.badge-fresh-info {
  background: rgba(99, 102, 241, 0.25) !important;
  color: #c7d2fe !important;
  border: 1px solid rgba(99, 102, 241, 0.5) !important;
}

[data-bs-theme="dark"] .hub-verify-badge.badge-verify-warning,
html[data-bs-theme="dark"] .hub-verify-badge.badge-verify-warning {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .hub-verify-badge.badge-verify-success,
html[data-bs-theme="dark"] .hub-verify-badge.badge-verify-success {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
  border: 1px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .badge-jenjang,
html[data-bs-theme="dark"] .badge-jenjang {
  background: rgba(59, 130, 246, 0.25) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(59, 130, 246, 0.45) !important;
}

[data-bs-theme="dark"] .badge-npsn,
html[data-bs-theme="dark"] .badge-npsn {
  background: rgba(255, 255, 255, 0.1) !important;
  color: #e2e8f0 !important;
  border: 1px solid rgba(255, 255, 255, 0.18) !important;
}

/* Dark Mode Autocomplete Dropdown */
[data-bs-theme="dark"] .school-hub-dropdown-glass,
html[data-bs-theme="dark"] .school-hub-dropdown-glass {
  background: #1e293b !important;
  border: 2px solid rgba(255, 255, 255, 0.18) !important;
  box-shadow: 0 28px 60px -12px rgba(0, 0, 0, 0.8) !important;
}

[data-bs-theme="dark"] .dropdown-header-bar,
html[data-bs-theme="dark"] .dropdown-header-bar {
  background: #0f172a !important;
  color: #cbd5e1 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .dropdown-header-sub,
html[data-bs-theme="dark"] .dropdown-header-sub {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .school-hub-result-card,
html[data-bs-theme="dark"] .school-hub-result-card {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .school-hub-result-card:hover,
html[data-bs-theme="dark"] .school-hub-result-card:hover {
  background: rgba(245, 158, 11, 0.16) !important;
  border-left-color: #fbbf24 !important;
}

[data-bs-theme="dark"] .result-avatar-box,
html[data-bs-theme="dark"] .result-avatar-box {
  background: #0f172a !important;
  color: #fbbf24 !important;
}

[data-bs-theme="dark"] .school-result-name,
html[data-bs-theme="dark"] .school-result-name {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .school-result-sub,
html[data-bs-theme="dark"] .school-result-sub {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .btn-select-pill,
html[data-bs-theme="dark"] .btn-select-pill {
  background: rgba(245, 158, 11, 0.22) !important;
  color: #fbbf24 !important;
  border: 1.5px solid rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .dropdown-footer-callout,
html[data-bs-theme="dark"] .dropdown-footer-callout {
  background: rgba(99, 102, 241, 0.18) !important;
  color: #c7d2fe !important;
  border-top: 1px solid rgba(99, 102, 241, 0.3) !important;
}

[data-bs-theme="dark"] .dropdown-footer-callout:hover,
html[data-bs-theme="dark"] .dropdown-footer-callout:hover {
  background: rgba(99, 102, 241, 0.28) !important;
}

/* Dark Mode Branch Grid & Satuan */
[data-bs-theme="dark"] .status-branch-header,
html[data-bs-theme="dark"] .status-branch-header {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .branch-card-registered,
html[data-bs-theme="dark"] .branch-card-registered {
  background: rgba(16, 185, 129, 0.15) !important;
  border: 1.5px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .branch-card-unregistered,
html[data-bs-theme="dark"] .branch-card-unregistered {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .branch-item-title,
html[data-bs-theme="dark"] .branch-item-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .branch-item-icon,
html[data-bs-theme="dark"] .branch-item-icon {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .branch-item-footer,
html[data-bs-theme="dark"] .branch-item-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .badge-covered-allin,
html[data-bs-theme="dark"] .badge-covered-allin {
  background: #10b981 !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .badge-comp-closed,
html[data-bs-theme="dark"] .badge-comp-closed {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #94a3b8 !important;
  border: 1px solid rgba(255, 255, 255, 0.14) !important;
}

[data-bs-theme="dark"] .status-sub-label,
html[data-bs-theme="dark"] .status-sub-label {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .satuan-item-card,
html[data-bs-theme="dark"] .satuan-item-card {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .satuan-item-title,
html[data-bs-theme="dark"] .satuan-item-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .satuan-item-sub,
html[data-bs-theme="dark"] .satuan-item-sub {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .satuan-item-code,
html[data-bs-theme="dark"] .satuan-item-code {
  color: #a78bfa !important;
}

[data-bs-theme="dark"] .status-loading-box,
html[data-bs-theme="dark"] .status-loading-box {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .status-loading-text,
html[data-bs-theme="dark"] .status-loading-text {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .btn-wa-confirm,
html[data-bs-theme="dark"] .btn-wa-confirm {
  background: #22c55e !important;
  color: #ffffff !important;
  box-shadow: 0 4px 14px rgba(34, 197, 94, 0.4) !important;
}
</style>

