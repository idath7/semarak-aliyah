<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  competitions: { type: Array, default: () => [] },
  selectedCompetition: { type: Object, default: () => ({}) },
  presets: { type: Object, default: () => ({}) },
  canManageForm: { type: Boolean, default: false },
  isFormInactive: { type: Boolean, default: false },
  isCoordinator: { type: Boolean, default: false },
  isGlobalAdmin: { type: Boolean, default: false },
  hasManagePermission: { type: Boolean, default: false },
  paymentBankAccounts: { type: Array, default: () => [] },
  packageAllinPrice: { type: Number, default: 200000 },
});

const page = usePage();

const bankAccounts = computed(() => {
  return (props.paymentBankAccounts && props.paymentBankAccounts.length > 0)
    ? props.paymentBankAccounts
    : (page.props.paymentBankAccounts || []);
});

const activeBankAccounts = computed(() => {
  return bankAccounts.value.filter(b => b.is_active !== false);
});

const primaryBank = computed(() => {
  return activeBankAccounts.value.find(b => b.is_primary) || activeBankAccounts.value[0] || null;
});

const toast = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// Navigation & View Mode State
const currentCompId = ref(props.selectedCompetition?.id || props.competitions[0]?.id || '');
const viewMode = ref('split'); // 'builder' (Editor Saja) | 'split' (Berdampingan) | 'preview' (Pratinjau Saja)
const previewDevice = ref('desktop'); // 'desktop' | 'mobile'
const activeSectionTab = ref('section_1'); // 'section_1' | 'section_2' | 'section_3' | 'section_4'

// Form Settings State (Headline & Submit Button)
const formSettings = ref({
  is_active: true,
  show_package_type: true,
  form_title: '',
  form_subtitle: '',
  submit_text: 'Kirim Pendaftaran Delegasi',
  section_1_title: '',
  section_1_subtitle: '',
  section_2_title: '',
  section_2_subtitle: '',
  section_3_title: '',
  section_3_subtitle: '',
  section_4_title: '',
  section_4_subtitle: '',
});

// Canonical Form Fields State
const identityFields = ref([]);
const repeaterField = ref(null);
const documentFields = ref([]);
const allinField = ref(null);

const isDirty = ref(false);
const isSaving = ref(false);
const showHeadlineModal = ref(false);
const showCopyModal = ref(false);
const isApplyingHeadlineAll = ref(false);
const sourceCompIdToCopy = ref('');

// Authorization Computeds
const canManageForm = computed(() => props.canManageForm === true);
const isUserCoordinator = computed(() => props.isCoordinator === true || props.selectedCompetition?.is_coordinated === true);
const isFormInactive = computed(() => {
  return props.isFormInactive === true || props.selectedCompetition?.is_form_inactive === true || formSettings.value.is_active === false;
});

const disabledReasonTitle = computed(() => {
  if (isFormInactive.value) return 'Formulir Nonaktif / Terkunci';
  if (!props.isGlobalAdmin && !isUserCoordinator.value) return 'Bukan Wewenang Anda (Lihat Saja)';
  if (!props.hasManagePermission) return 'Hak Akses Kelola Formulir Dinonaktifkan';
  return 'Mode Baca Saja (Terkunci)';
});

const disabledReasonDetail = computed(() => {
  if (isFormInactive.value) {
    if (props.selectedCompetition?.registration_type === 'direct_contact') {
      return 'Cabang lomba ini menggunakan pendaftaran via kontak langsung (WhatsApp). Formulir online tidak aktif.';
    }
    if (props.selectedCompetition?.status === 'closed') {
      return 'Pendaftaran cabang lomba ini telah ditutup oleh panitia.';
    }
    return 'Formulir pendaftaran cabang lomba ini telah dinonaktifkan / dikunci dari pengaturan.';
  }
  if (!props.isGlobalAdmin && !isUserCoordinator.value) {
    return 'Cabang lomba ini dikoordinasikan oleh panitia lain. Anda hanya dapat melihat pratinjau formulir.';
  }
  if (!props.hasManagePermission) {
    return 'Akun Anda tidak memiliki izin untuk mengonfigurasi atau mengubah formulir pendaftaran.';
  }
  return 'Anda hanya memiliki wewenang membaca untuk formulir cabang lomba ini.';
});

// Competition Options for Dropdown
const competitionSelectOptions = computed(() => {
  return (props.competitions || []).map(c => {
    let tag = c.participant_type === 'tim' ? 'Tim' : 'Individu';
    let statusText = c.status === 'closed' ? ' • Ditutup' : '';
    if (c.registration_type === 'direct_contact') statusText = ' • Via WA';
    return {
      value: c.id,
      label: `${c.title} (${tag}${statusText})`
    };
  });
});

const copySourceOptions = computed(() => {
  return (props.competitions || [])
    .filter(c => c.id !== currentCompId.value)
    .map(c => ({
      value: c.id,
      label: `${c.title} (${c.participant_type === 'tim' ? 'Tim' : 'Individu'})`
    }));
});

// Standard Mandatory 7 Identity Fields
const MANDATORY_KEYS = [
  { name: 'school_name', label: 'Nama Sekolah / Madrasah', type: 'text', placeholder: 'Pilih atau ketik nama sekolah / madrasah pengirim', help_text: 'Nama resmi sekolah/madrasah yang mendelegasikan siswa', required: true },
  { name: 'school_district', label: 'Kecamatan Sekolah', type: 'select', placeholder: 'Pilih Wilayah Kecamatan Sekolah', help_text: 'Pilih kecamatan tempat sekolah/madrasah berada', required: true },
  { name: 'school_npsn', label: 'Nomor Pokok Sekolah Nasional (NPSN)', type: 'text', placeholder: 'Contoh: 20512345 (8 Digit Angka)', help_text: 'NPSN resmi sekolah/madrasah (opsional atau wajib sesuai verifikasi)', required: false },
  { name: 'school_address', label: 'Alamat Sekolah / Madrasah', type: 'textarea', placeholder: 'Alamat lengkap jalan, RT/RW, desa/kelurahan sekolah', help_text: 'Alamat instansi pengirim delegasi', required: true },
  { name: 'mentor_name', label: 'Nama Guru Pendamping / Pembina', type: 'text', placeholder: 'Nama lengkap guru pendamping beserta gelar', help_text: 'Guru atau pembina resmi yang mendampingi delegasi lomba', required: true },
  { name: 'mentor_wa', label: 'Nomor WhatsApp Guru Pendamping', type: 'number', placeholder: 'Contoh: 081234567890 (Aktif WhatsApp)', help_text: 'Nomor WhatsApp aktif untuk koordinasi teknis dan verifikasi panitia', required: true },
  { name: 'mentor_email', label: 'Email Aktif Guru Pendamping', type: 'email', placeholder: 'guru.pembina@sekolah.sch.id', help_text: 'Email aktif untuk pengiriman bukti pendaftaran & barcode TM', required: false },
];

function createDefaultSubFields() {
  return {
    name: { active: true, required: true, label: 'Nama Lengkap Siswa', placeholder: 'Nama lengkap siswa sesuai akta / KTS' },
    gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)', placeholder: '' },
    birth_date: { active: true, required: false, label: 'Tanggal Lahir Siswa', placeholder: 'dd/mm/yyyy' },
    grade: { active: true, required: true, label: 'Kelas / Tingkat', placeholder: 'Pilih Kelas...' },
    nisn: { active: true, required: true, label: 'Nomor Induk Siswa Nasional (NISN)', placeholder: 'Contoh: 0081234567' },
    custom_id: { active: false, required: false, label: 'ID Siswa / No. Identitas', placeholder: 'Contoh: ID-0912 / User ID Game' },
    wa_number: { active: false, required: false, label: 'Nomor WhatsApp Siswa', placeholder: '08xxxxxxxxxx' },
    captain_option: { active: false, required: false, label: 'Tandai Kapten Tim & Kontak WA', placeholder: '08xxxxxxxxxx' },
    team_name: { active: false, required: true, label: 'Nama Tim / Skuad', placeholder: 'Contoh: Garuda Squad / Tim Voli' },
    photo_formal: { active: true, required: false, label: 'Pas Foto Formal (3x4)', placeholder: 'Pilih Pas Foto Formal Siswa' },
    photo_freestyle: { active: false, required: false, label: 'Upload Foto Pose Gaya Bebas', placeholder: 'Pilih Foto Pose Bebas', multiple: true },
    custom_columns: []
  };
}

function normalizeSubFields(subs) {
  const defaults = createDefaultSubFields();
  if (!subs || typeof subs !== 'object') return defaults;
  const res = { ...defaults };
  for (const k in defaults) {
    if (subs[k] && typeof subs[k] === 'object') {
      res[k] = { ...defaults[k], ...subs[k] };
    }
  }
  return res;
}

// Auto-Sync Logic between Competition Entity and Repeater
function syncWithCompetitionRules(field, isManualRefresh = false) {
  const comp = props.selectedCompetition;
  if (!comp || !field) return;

  const isTeam = comp.participant_type === 'tim';
  const rule = (comp.participants_rule || '').trim();
  const compId = (comp.id || '').toLowerCase();
  const compTitle = (comp.title || '').toLowerCase();
  const compCategory = (comp.category || '').toLowerCase();

  // 1. Ambil data primer langsung dari atribut numerik lomba (Manajemen Lomba)
  let max = comp.max_participants ? Number(comp.max_participants) : (isTeam ? 10 : 1);
  let min = comp.min_participants ? Number(comp.min_participants) : (isTeam ? Math.min(max, 5) : 1);
  let reserve = (comp.reserve_participants !== undefined && comp.reserve_participants !== null && comp.reserve_participants !== '')
    ? Number(comp.reserve_participants)
    : 0;
  let main = min;

  // Fallback regex HANYA jika data min_participants tidak terdefinisi di database
  if (!comp.min_participants && rule) {
    const matchInti = rule.match(/(\d+)\s*(?:pemain|orang|siswa|anggota|personel)?\s*inti/i);
    const matchCadangan = rule.match(/(\d+)\s*(?:pemain|orang|siswa|anggota|personel)?\s*cadangan/i);
    const matchRange = rule.match(/(\d+)\s*(?:s\.d|sampai|-)\s*(\d+)\s*(?:siswa|orang|pemain|peserta|personel)?/i);
    const matchMaksimal = rule.match(/maksimal\s*(\d+)\s*(?:siswa|orang|pemain|peserta|personel)?/i);

    if (matchInti) {
      main = parseInt(matchInti[1], 10);
      min = main;
      if (matchCadangan) reserve = parseInt(matchCadangan[1], 10);
      if (!comp.max_participants) max = main + reserve;
    } else if (matchRange) {
      min = parseInt(matchRange[1], 10);
      if (!comp.max_participants) max = parseInt(matchRange[2], 10);
      main = min;
      reserve = Math.max(0, max - min);
    } else if (matchMaksimal) {
      if (!comp.max_participants) max = parseInt(matchMaksimal[1], 10);
      min = isTeam ? Math.min(max, 5) : 1;
      main = min;
      reserve = 0;
    }
  }

  if (min > max) min = max;
  if (min < 1) min = 1;
  const maxReserveAllowed = Math.max(0, max - min);
  if (reserve > maxReserveAllowed) reserve = maxReserveAllowed;
  main = min;

  field.min_items = min;
  field.max_items = max;
  field.main_members = main;
  field.reserve_members = reserve;
  field.name = isTeam ? 'roster_tim' : 'biodata_peserta';

  // Hanya set default label jika label masih kosong
  if (!field.label || !field.label.trim()) {
    if (isTeam) {
      field.label = compId === 'mobile-legends'
        ? `Roster Skuad Tim Mobile Legends (${main} Inti + ${reserve} Cadangan)`
        : (compId.includes('volleyball') || compTitle.includes('voli')
          ? `Roster Skuad Tim Bola Voli (${main} Inti + ${reserve} Cadangan)`
          : `Biodata Personel Grup ${comp.title || 'Banjari'} (Maksimal ${max} Personel)`);
    } else {
      field.label = `Biodata Peserta ${comp.title || 'Lomba'} (Maksimal ${max} Siswa)`;
    }
  }

  if (comp.participants_rule) {
    field.help_text = `Aturan: ${comp.participants_rule} (Minimal ${min}, Maksimal ${max} peserta)`;
  }

  // Normalisasi sub_fields TANPA menimpa kustomisasi yang sudah ada
  const hadExistingSubs = field.sub_fields && typeof field.sub_fields === 'object' && Object.keys(field.sub_fields).length > 0;
  field.sub_fields = normalizeSubFields(field.sub_fields);

  // Default preset cerdas HANYA jika field belum pernah memiliki sub_fields atau tombol Segarkan Kuota ditekan secara manual
  if (!hadExistingSubs || isManualRefresh) {
    if (isTeam) {
      field.sub_fields.team_name.active = true;
      field.sub_fields.team_name.required = true;
      field.sub_fields.captain_option.active = true;

      if (compId === 'mobile-legends' || compTitle.includes('mobile legends') || compCategory === 'teknologi') {
        field.sub_fields.team_name.label = 'Nama Tim / Skuad MLBB';
        field.sub_fields.team_name.placeholder = 'Contoh: Garuda Squad / RRQ Reborn';
        field.sub_fields.custom_id.active = true;
        field.sub_fields.custom_id.required = true;
        field.sub_fields.custom_id.label = 'ID Akun MLBB';
        field.sub_fields.custom_id.placeholder = 'ID Game (Server ID)';
        field.sub_fields.captain_option.label = 'Tandai Kapten Tim & No. WhatsApp';
        field.sub_fields.captain_option.placeholder = '08xxxxxxxxxx (Untuk koordinasi panitia turnamen)';
      } else if (compId.includes('volleyball') || compTitle.includes('volleyball') || compTitle.includes('voli') || compCategory === 'olahraga') {
        field.sub_fields.team_name.label = 'Nama Tim Bola Voli';
        field.sub_fields.team_name.placeholder = 'Contoh: Spiker Utama / Tim Voli Putra';
        field.sub_fields.custom_id.active = true;
        field.sub_fields.custom_id.required = false;
        field.sub_fields.custom_id.label = 'No. Punggung / Posisi';
        field.sub_fields.custom_id.placeholder = 'Contoh: 10 / Spiker';
        field.sub_fields.captain_option.label = 'Tandai Kapten Tim & No. WhatsApp';
        field.sub_fields.captain_option.placeholder = '08xxxxxxxxxx (Untuk koordinasi kapten voli)';
      } else if (compId === 'albanjari' || compTitle.includes('banjari') || compCategory === 'keagamaan') {
        field.sub_fields.team_name.label = 'Nama Grup Albanjari';
        field.sub_fields.team_name.placeholder = 'Contoh: Syauqul Habib / Al-Fath';
        field.sub_fields.custom_id.active = true;
        field.sub_fields.custom_id.required = false;
        field.sub_fields.custom_id.label = 'Peran / Alat Musik';
        field.sub_fields.custom_id.placeholder = 'Contoh: Vokal Utama / Terbang 1 / Bass';
        field.sub_fields.captain_option.label = 'Tandai Ketua Grup & No. WhatsApp';
        field.sub_fields.captain_option.placeholder = '08xxxxxxxxxx (Untuk koordinasi ketua grup banjari)';
      } else {
        field.sub_fields.team_name.label = 'Nama Tim / Regu';
        field.sub_fields.team_name.placeholder = 'Contoh: Regu Utama / Tim Kontingen';
      }
    } else if (isManualRefresh) {
      field.sub_fields.team_name.active = false;
      field.sub_fields.captain_option.active = false;
      field.sub_fields.custom_id.active = false;
    }
  }

  if (isManualRefresh) {
    toast.success(`Pengaturan kuota (${min} s.d ${max} peserta) & roster berhasil disegarkan sesuai aturan resmi lomba!`);
  }
}

// Initialize Data into 4 Dedicated Sections
function initializeData() {
  const rawFields = Array.isArray(props.selectedCompetition?.custom_fields) 
    ? JSON.parse(JSON.stringify(props.selectedCompetition.custom_fields)) 
    : [];

  // 1. Extract 7 Identity Fields
  const idMap = {};
  rawFields.forEach(f => {
    if (!f || !f.name) return;
    idMap[f.name] = f;
  });

  const parsedIdentity = MANDATORY_KEYS.map(def => {
    const existing = idMap[def.name] || {};
    return {
      id: existing.id || `cf_${def.name}`,
      name: def.name,
      label: existing.label || def.label,
      type: def.type,
      placeholder: existing.placeholder !== undefined ? existing.placeholder : def.placeholder,
      help_text: existing.help_text !== undefined ? existing.help_text : def.help_text,
      required: existing.required !== undefined ? !!existing.required : def.required,
      options: existing.options || [],
      allowed_extensions: [],
      max_size_mb: null,
      is_mandatory_identity: true
    };
  });
  identityFields.value = parsedIdentity;

  // 2. Extract Repeater Field (Bagian 2)
  let foundRepeater = rawFields.find(f => f.type === 'participant_repeater');
  if (!foundRepeater) {
    const isTeam = props.selectedCompetition?.participant_type === 'tim';
    foundRepeater = {
      id: 'cf_participant_repeater',
      name: isTeam ? 'roster_tim' : 'biodata_peserta',
      label: isTeam ? 'Roster Skuad Tim' : 'Biodata Peserta Siswa',
      type: 'participant_repeater',
      placeholder: '',
      help_text: '',
      required: true,
      min_items: 1,
      max_items: 1,
      sub_fields: createDefaultSubFields(),
    };
  }
  syncWithCompetitionRules(foundRepeater);
  repeaterField.value = foundRepeater;

  // 3. Extract Document File Fields (Bagian 3)
  const standardNames = MANDATORY_KEYS.map(m => m.name).concat([
    'allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin',
    'nama_tim', 'team_name', 'nama_squad', 'nama_regu', 'nama_grup'
  ]);

  const parsedDocs = rawFields.filter(f => {
    if (f.type === 'participant_repeater') return false;
    if (standardNames.includes(f.name)) return false;
    return true; // Any custom file or question
  }).map(f => ({
    id: f.id || `cf_doc_${Date.now()}_${Math.random().toString(36).substring(2, 6)}`,
    name: f.name || `dokumen_${Date.now()}`,
    label: f.label || 'Dokumen Persyaratan',
    type: f.type || 'file',
    placeholder: f.placeholder || '',
    help_text: f.help_text || 'Unggah berkas resmi sesuai petunjuk juknis',
    required: f.required !== undefined ? !!f.required : true,
    allowed_extensions: Array.isArray(f.allowed_extensions) && f.allowed_extensions.length > 0 ? f.allowed_extensions : ['pdf', 'jpg', 'jpeg', 'png'],
    max_size_mb: f.max_size_mb ? Number(f.max_size_mb) : 5,
    max_files: 1
  }));
  documentFields.value = parsedDocs;

  // 4. Extract All-In Field (Bagian 4)
  const foundAllin = rawFields.find(f => ['allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin'].includes(f.name));
  allinField.value = {
    id: foundAllin?.id || 'cf_allin_code',
    name: 'allin_code',
    label: foundAllin?.label || 'Kode Paket Komplit (All-In)',
    type: 'text',
    placeholder: foundAllin?.placeholder || 'Contoh: SA26-XXXXXX (Otomatis jika sekolah terverifikasi All-In)',
    help_text: foundAllin?.help_text || 'Masukkan kode paket jika sekolah Anda mendaftar Paket Komplit agar pendaftaran cabang ini bebas biaya (Rp 0).',
    required: false
  };

  // Form Settings (Headline 4 Bagian & Tombol Kirim)
  const s = props.selectedCompetition?.form_settings;
  const compIsInactive = props.selectedCompetition?.is_form_inactive ?? false;
  if (s && typeof s === 'object') {
    formSettings.value = {
      is_active: s.is_active !== undefined ? !!s.is_active : !compIsInactive,
      show_package_type: s.show_package_type !== undefined ? !!s.show_package_type : true,
      form_title: s.form_title || '',
      form_subtitle: s.form_subtitle || '',
      submit_text: s.submit_text || 'Kirim Pendaftaran Delegasi',
      section_1_title: s.section_1_title || '1. Identitas Sekolah / Madrasah',
      section_1_subtitle: s.section_1_subtitle || 'Lembaga & Guru Pendamping Resmi',
      section_2_title: s.section_2_title || '2. Biodata Peserta & Anggota Delegasi',
      section_2_subtitle: s.section_2_subtitle || 'Format Roster Resmi',
      section_3_title: s.section_3_title || '3. Berkas Dokumen Pendukung',
      section_3_subtitle: s.section_3_subtitle || 'Sesuai Juknis Panitia',
      section_4_title: s.section_4_title || '4. Skema Paket & Metode Pembayaran',
      section_4_subtitle: s.section_4_subtitle || 'Biaya Pendaftaran & Konfirmasi',
    };
  } else {
    formSettings.value = {
      is_active: !compIsInactive,
      show_package_type: true,
      form_title: '',
      form_subtitle: '',
      submit_text: 'Kirim Pendaftaran Delegasi',
      section_1_title: '1. Identitas Sekolah / Madrasah',
      section_1_subtitle: 'Lembaga & Guru Pendamping Resmi',
      section_2_title: '2. Biodata Peserta & Anggota Delegasi',
      section_2_subtitle: 'Format Roster Resmi',
      section_3_title: '3. Berkas Dokumen Pendukung',
      section_3_subtitle: 'Sesuai Juknis Panitia',
      section_4_title: '4. Skema Paket & Metode Pembayaran',
      section_4_subtitle: 'Biaya Pendaftaran & Konfirmasi',
    };
  }

  nextTick(() => {
    isDirty.value = false;
  });
}

// Watch selectedCompetition prop changes
watch(() => props.selectedCompetition, () => {
  currentCompId.value = props.selectedCompetition?.id || '';
  initializeData();
}, { immediate: true, deep: true });

// Track unsaved modifications
watch([identityFields, repeaterField, documentFields, allinField, formSettings], () => {
  isDirty.value = true;
}, { deep: true });

// Switch Competition via Dropdown
async function switchCompetition(compId) {
  if (isDirty.value) {
    const ok = await confirmDialog({
      title: 'Perubahan Belum Disimpan!',
      message: 'Anda memiliki perubahan formulir yang belum disimpan pada cabang ini. Beralih ke cabang lain akan membatalkan perubahan tersebut.',
      type: 'warning',
      confirmText: 'Tetap Beralih',
      cancelText: 'Kembali & Simpan',
      icon: 'ri-alert-line',
    });
    if (!ok) {
      currentCompId.value = props.selectedCompetition?.id;
      return;
    }
  }
  router.get('/admin/form-builder', { comp: compId }, {
    preserveState: false,
    preserveScroll: true,
  });
}

// Add New Document Requirement (Bagian 3)
function addDocumentField() {
  if (!canManageForm.value) {
    toast.warning(disabledReasonDetail.value);
    return;
  }
  const id = `cf_doc_${Date.now()}`;
  documentFields.value.push({
    id: id,
    name: `berkas_${documentFields.value.length + 1}`,
    label: `Berkas Dokumen ${documentFields.value.length + 1}`,
    type: 'file',
    placeholder: '',
    help_text: 'Unggah berkas resmi berformat PDF/JPG/PNG maksimal 5 MB',
    required: true,
    allowed_extensions: ['pdf', 'jpg', 'jpeg', 'png'],
    max_size_mb: 5,
    max_files: 1
  });
  toast.success('Persyaratan berkas dokumen baru ditambahkan.');
}

// Remove Document Requirement (Bagian 3)
async function removeDocumentField(index) {
  if (!canManageForm.value) {
    toast.warning(disabledReasonDetail.value);
    return;
  }
  const doc = documentFields.value[index];
  const ok = await confirmDialog({
    title: 'Hapus Persyaratan Berkas?',
    message: `Hapus persyaratan "${doc.label}" dari formulir cabang ini?`,
    type: 'danger',
    confirmText: 'Ya, Hapus',
    icon: 'ri-delete-bin-line',
  });
  if (ok) {
    documentFields.value.splice(index, 1);
    toast.info('Persyaratan berkas dihapus.');
  }
}

// Toggle file extension for Document
function toggleExtension(doc, ext) {
  if (!Array.isArray(doc.allowed_extensions)) doc.allowed_extensions = [];
  const idx = doc.allowed_extensions.indexOf(ext);
  if (idx > -1) {
    if (doc.allowed_extensions.length > 1) {
      doc.allowed_extensions.splice(idx, 1);
    } else {
      toast.warning('Minimal 1 format file harus diizinkan.');
    }
  } else {
    doc.allowed_extensions.push(ext);
  }
}

// Save All 4 Sections
function saveFormFields() {
  if (!canManageForm.value) {
    toast.error(disabledReasonDetail.value, disabledReasonTitle.value);
    return;
  }
  if (!currentCompId.value) return;

  // Validation
  for (const idf of identityFields.value) {
    if (!idf.label || !idf.label.trim()) {
      activeSectionTab.value = 'section_1';
      toast.warning(`Field identitas "${idf.name}" wajib memiliki label.`);
      return;
    }
  }

  if (repeaterField.value) {
    if (!repeaterField.value.label || !repeaterField.value.label.trim()) {
      activeSectionTab.value = 'section_2';
      toast.warning('Label Bagian Multi-Peserta wajib diisi.');
      return;
    }
  }

  for (let i = 0; i < documentFields.value.length; i++) {
    const doc = documentFields.value[i];
    if (!doc.label || !doc.label.trim()) {
      activeSectionTab.value = 'section_3';
      toast.warning(`Dokumen ke-${i + 1} belum memiliki Label Berkas.`);
      return;
    }
  }

  // Construct combined custom_fields: Section 1 + Section 2 + Section 3 + Section 4
  const combinedFields = [
    ...identityFields.value,
    repeaterField.value,
    ...documentFields.value,
    allinField.value
  ].filter(Boolean);

  isSaving.value = true;
  router.post(`/admin/form-builder/${currentCompId.value}`, {
    custom_fields: combinedFields,
    form_settings: formSettings.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      isDirty.value = false;
      toast.success(`Pengaturan formulir cabang "${props.selectedCompetition?.title}" berhasil disimpan!`, 'Tersimpan');
    },
    onError: () => {
      isSaving.value = false;
      toast.error('Gagal menyimpan pengaturan formulir.', 'Terjadi Kesalahan');
    }
  });
}

// Copy Settings from Another Competition
async function copyFromCompetition() {
  if (!sourceCompIdToCopy.value) {
    toast.warning('Pilih cabang lomba sumber terlebih dahulu.');
    return;
  }
  const sourceComp = props.competitions.find(c => c.id === sourceCompIdToCopy.value);
  const ok = await confirmDialog({
    title: 'Salin Pengaturan Formulir?',
    message: `Salin judul headline, petunjuk, dan berkas persyaratan dari "${sourceComp?.title}" ke "${props.selectedCompetition?.title}"?`,
    type: 'warning',
    confirmText: 'Ya, Salin Pengaturan',
    icon: 'ri-file-copy-line'
  });
  if (ok) {
    router.post(`/admin/form-builder/${currentCompId.value}/copy-from`, {
      source_competition_id: sourceCompIdToCopy.value
    }, {
      preserveScroll: true,
      onSuccess: () => {
        showCopyModal.value = false;
        toast.success(`Pengaturan formulir berhasil disalin dari "${sourceComp?.title}"!`);
      }
    });
  }
}

// Apply Headline to All Competitions
async function applyHeadlineAll() {
  const ok = await confirmDialog({
    title: 'Terapkan Headline ke Semua Cabang?',
    message: 'Judul dan subjudul dari 4 Bagian Formulir aktif akan diseragamkan ke seluruh 8 cabang lomba. Lanjutkan?',
    type: 'warning',
    confirmText: 'Ya, Terapkan ke Semua',
    icon: 'ri-global-line',
  });
  if (ok) {
    isApplyingHeadlineAll.value = true;
    router.post('/admin/form-builder/apply-headline-all', {
      form_title: formSettings.value.form_title,
      form_subtitle: formSettings.value.form_subtitle,
      submit_text: formSettings.value.submit_text,
      show_package_type: formSettings.value.show_package_type,
      section_1_title: formSettings.value.section_1_title,
      section_1_subtitle: formSettings.value.section_1_subtitle,
      section_2_title: formSettings.value.section_2_title,
      section_2_subtitle: formSettings.value.section_2_subtitle,
      section_3_title: formSettings.value.section_3_title,
      section_3_subtitle: formSettings.value.section_3_subtitle,
      section_4_title: formSettings.value.section_4_title,
      section_4_subtitle: formSettings.value.section_4_subtitle,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        isApplyingHeadlineAll.value = false;
        showHeadlineModal.value = false;
        toast.success('Headline 4 Bagian Formulir berhasil diseragamkan ke seluruh cabang lomba!');
      },
      onError: () => {
        isApplyingHeadlineAll.value = false;
        toast.error('Gagal menerapkan headline ke semua cabang.');
      }
    });
  }
}
</script>

<template>
  <VelzonLayout>
    <Head :title="`Kelola Formulir - ${selectedCompetition?.title || 'Semarak Aliyah'}`" />

    <div class="container-fluid py-3 px-3 px-md-4">
      <!-- Top Header & Breadcrumb -->
      <div class="row mb-3 align-items-center">
        <div class="col-12 col-md-6 mb-2 mb-md-0">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center shadow-xs">
              <i class="ri-survey-line fs-20"></i>
            </div>
            <div>
              <h4 class="fs-17 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <span>Kelola Formulir Pendaftaran</span>
                <span class="badge bg-primary text-white fs-11 fw-normal px-2 py-0.5 rounded-pill">
                  {{ selectedCompetition?.category_label || 'Perlombaan' }}
                </span>
              </h4>
              <p class="text-muted fs-11 mb-0 mt-0.5">
                Konfigurasi 4 Bagian Formulir Resmi Per Cabang Perlombaan • Semarak Aliyah XIV
              </p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
          <!-- Unsaved badge -->
          <span v-if="isDirty" class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 fs-11 d-inline-flex align-items-center gap-1 shadow-xs">
            <i class="ri-record-circle-line animate-pulse"></i>
            <span>Ada Perubahan Belum Disimpan</span>
          </span>

          <!-- Button Terapkan Headline Semua Cabang -->
          <button 
            type="button" 
            class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5 fw-semibold shadow-xs"
            @click="showHeadlineModal = true"
            title="Seragamkan Judul 4 Bagian ke Semua Cabang"
          >
            <i class="ri-global-line fs-14"></i>
            <span class="d-none d-sm-inline">Headline Semua Cabang</span>
          </button>

          <!-- Button Salin dari Cabang Lain -->
          <button 
            type="button" 
            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1.5 fw-semibold shadow-xs"
            @click="showCopyModal = true"
            title="Salin Berkas & Pengaturan dari Cabang Lain"
          >
            <i class="ri-file-copy-line fs-14"></i>
            <span class="d-none d-sm-inline">Salin Pengaturan</span>
          </button>

          <!-- Button Simpan -->
          <button 
            type="button" 
            class="btn btn-sm btn-success d-flex align-items-center gap-1.5 fw-bold px-3 shadow-sm"
            :disabled="isSaving || (!isDirty && !props.isGlobalAdmin)"
            @click="saveFormFields"
          >
            <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
            <i v-else class="ri-save-line fs-15"></i>
            <span>Simpan Perubahan</span>
          </button>
        </div>
      </div>

      <!-- Command Bar: Cabang Selector & View Mode Switcher -->
      <div class="card border-0 shadow-sm rounded-3 mb-3 bg-body">
        <div class="card-body p-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <!-- Competition Dropdown -->
          <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 460px;">
            <label class="form-label fs-12 fw-bold text-dark mb-0 text-nowrap d-flex align-items-center gap-1">
              <i class="ri-trophy-line text-warning fs-14"></i>
              <span>Pilih Cabang:</span>
            </label>
            <div class="flex-grow-1">
              <CustomSelect 
                v-model="currentCompId" 
                :options="competitionSelectOptions"
                @change="switchCompetition"
                size="sm"
              />
            </div>
          </div>

          <!-- Quick Competition Meta Pills -->
          <div class="d-none d-lg-flex align-items-center gap-2 text-muted fs-11">
            <span class="badge" :class="selectedCompetition?.participant_type === 'tim' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary'">
              <i :class="selectedCompetition?.participant_type === 'tim' ? 'ri-team-line' : 'ri-user-line'" class="me-0.5"></i>
              {{ selectedCompetition?.participant_type === 'tim' ? 'Format Tim/Regu' : 'Format Individu' }}
            </span>
            <span class="badge bg-light text-dark border">
              Biaya: Rp {{ Number(selectedCompetition?.fee || 0).toLocaleString('id-ID') }}
            </span>
            <span v-if="selectedCompetition?.registration_type === 'direct_contact'" class="badge bg-warning text-dark">
              <i class="ri-whatsapp-line me-0.5"></i> Via PJ WA
            </span>
            <span v-else class="badge bg-success text-white">
              <i class="ri-checkbox-circle-line me-0.5"></i> Formulir Aktif
            </span>
          </div>

          <!-- View Mode Toggle -->
          <div class="btn-group btn-group-sm shadow-xs">
            <button 
              type="button" 
              class="btn px-2.5" 
              :class="viewMode === 'builder' ? 'btn-primary fw-bold' : 'btn-outline-secondary'"
              @click="viewMode = 'builder'"
              title="Editor Saja"
            >
              <i class="ri-layout-left-line me-1"></i> Editor
            </button>
            <button 
              type="button" 
              class="btn px-2.5" 
              :class="viewMode === 'split' ? 'btn-primary fw-bold' : 'btn-outline-secondary'"
              @click="viewMode = 'split'"
              title="Berdampingan (Editor & Pratinjau)"
            >
              <i class="ri-layout-column-line me-1"></i> Berdampingan
            </button>
            <button 
              type="button" 
              class="btn px-2.5" 
              :class="viewMode === 'preview' ? 'btn-primary fw-bold' : 'btn-outline-secondary'"
              @click="viewMode = 'preview'"
              title="Pratinjau Penuh Pendaftar"
            >
              <i class="ri-eye-line me-1"></i> Pratinjau
            </button>
          </div>
        </div>
      </div>

      <!-- Main Content Layout -->
      <div class="row g-3">
        <!-- LEFT COLUMN: 4 Section Configuration Editor -->
        <div :class="viewMode === 'preview' ? 'd-none' : (viewMode === 'split' ? 'col-12 col-xl-7' : 'col-12')">
          <!-- 4-Section Navigation Tabs -->
          <ul class="nav nav-pills nav-justified mb-3 bg-body p-1.5 rounded-3 border shadow-xs gap-1">
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link py-2 px-2 text-start d-flex align-items-center gap-2 rounded-2 transition-all fs-12 fw-bold"
                :class="activeSectionTab === 'section_1' ? 'active shadow-sm' : 'text-dark'"
                @click="activeSectionTab = 'section_1'"
              >
                <span class="avatar-xs rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px; font-size: 11px;">1</span>
                <div class="text-truncate">
                  <div class="text-truncate">1. Identitas Sekolah</div>
                  <div class="fs-10 fw-normal opacity-75">7 Field Baku</div>
                </div>
              </button>
            </li>
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link py-2 px-2 text-start d-flex align-items-center gap-2 rounded-2 transition-all fs-12 fw-bold"
                :class="activeSectionTab === 'section_2' ? 'active shadow-sm' : 'text-dark'"
                @click="activeSectionTab = 'section_2'"
              >
                <span class="avatar-xs rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px; font-size: 11px;">2</span>
                <div class="text-truncate">
                  <div class="text-truncate">2. Multi-Peserta</div>
                  <div class="fs-10 fw-normal opacity-75">Roster & Kuota</div>
                </div>
              </button>
            </li>
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link py-2 px-2 text-start d-flex align-items-center gap-2 rounded-2 transition-all fs-12 fw-bold"
                :class="activeSectionTab === 'section_3' ? 'active shadow-sm' : 'text-dark'"
                @click="activeSectionTab = 'section_3'"
              >
                <span class="avatar-xs rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px; font-size: 11px;">3</span>
                <div class="text-truncate">
                  <div class="text-truncate">3. Berkas Dokumen</div>
                  <div class="fs-10 fw-normal opacity-75">{{ documentFields.length }} Persyaratan</div>
                </div>
              </button>
            </li>
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link py-2 px-2 text-start d-flex align-items-center gap-2 rounded-2 transition-all fs-12 fw-bold"
                :class="activeSectionTab === 'section_4' ? 'active shadow-sm' : 'text-dark'"
                @click="activeSectionTab = 'section_4'"
              >
                <span class="avatar-xs rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 22px; height: 22px; font-size: 11px;">4</span>
                <div class="text-truncate">
                  <div class="text-truncate">4. Pembayaran</div>
                  <div class="fs-10 fw-normal opacity-75">All-In & Transfer</div>
                </div>
              </button>
            </li>
          </ul>

          <!-- TAB 1: IDENTITAS SEKOLAH / MADRASAH -->
          <div v-show="activeSectionTab === 'section_1'" class="space-y-3">
            <!-- Section 1 Headline Card -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-primary-subtle py-2.5 px-3 border-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-government-line text-primary fs-16"></i>
                  <span class="fs-13 fw-bold text-primary-emphasis">Headline Bagian 1: Identitas Sekolah</span>
                </div>
                <span class="badge bg-primary text-white fs-10">Tampil di Web Publik</span>
              </div>
              <div class="card-body p-3">
                <div class="row g-2">
                  <div class="col-md-7">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Judul Bagian 1:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_1_title" 
                      class="form-control form-control-sm fs-12 fw-semibold"
                      placeholder="Contoh: 1. Identitas Sekolah / Madrasah"
                    />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Subjudul Keterangan:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_1_subtitle" 
                      class="form-control form-control-sm fs-12"
                      placeholder="Contoh: Lembaga & Guru Pendamping Resmi"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Informational Notice -->
            <div class="p-2.5 rounded-3 bg-info-subtle border border-info-subtle text-info-emphasis fs-11 d-flex align-items-center gap-2">
              <i class="ri-information-line fs-16 flex-shrink-0"></i>
              <div>
                <strong>7 Field Identitas Baku Sistem:</strong> Seluruh nama field dan urutan telah dipatok otomatis untuk memastikan integrasi sempurna dengan <em>Modal Input Manual</em>, pencarian sekolah terverifikasi, dan rekapitulasi delegasi. Anda dapat menyesuaikan label, placeholder, dan petunjuk di bawah ini.
              </div>
            </div>

            <!-- 7 Fixed Identity Field Cards -->
            <div class="space-y-2.5">
              <div 
                v-for="(idf, idx) in identityFields" 
                :key="idf.name" 
                class="card border shadow-xs rounded-3 p-3 bg-body"
              >
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-secondary-subtle text-secondary font-mono fw-bold fs-11">
                      #{{ idx + 1 }}
                    </span>
                    <span class="fs-12 fw-bold text-dark">{{ idf.name }}</span>
                    <span class="badge bg-light text-muted border fs-10 font-mono">{{ idf.type }}</span>
                  </div>

                  <!-- Required toggle -->
                  <div class="form-check form-switch mb-0 fs-12">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      :id="`req_${idf.name}`"
                      v-model="idf.required"
                    />
                    <label class="form-check-label fs-11 fw-semibold text-dark cursor-pointer ms-1" :for="`req_${idf.name}`">
                      {{ idf.required ? 'Wajib Diisi' : 'Opsional' }}
                    </label>
                  </div>
                </div>

                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Label Teks:</label>
                    <input 
                      type="text" 
                      v-model="idf.label" 
                      class="form-control form-control-sm fs-12 fw-semibold"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Placeholder:</label>
                    <input 
                      type="text" 
                      v-model="idf.placeholder" 
                      class="form-control form-control-sm fs-12"
                      placeholder="Placeholder kosong..."
                    />
                  </div>
                  <div class="col-12">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Petunjuk Pengisian (Help Text):</label>
                    <input 
                      type="text" 
                      v-model="idf.help_text" 
                      class="form-control form-control-sm fs-11 text-muted"
                      placeholder="Petunjuk opsional untuk pendaftar..."
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: MULTI-PESERTA DINAMIS -->
          <div v-show="activeSectionTab === 'section_2'" class="space-y-3">
            <!-- Section 2 Headline Card -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-success-subtle py-2.5 px-3 border-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-team-line text-success fs-16"></i>
                  <span class="fs-13 fw-bold text-success-emphasis">Headline Bagian 2: Multi-Peserta Dinamis</span>
                </div>
                <span class="badge bg-success text-white fs-10">Tampil di Web Publik</span>
              </div>
              <div class="card-body p-3">
                <div class="row g-2">
                  <div class="col-md-7">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Judul Bagian 2:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_2_title" 
                      class="form-control form-control-sm fs-12 fw-semibold"
                      placeholder="Contoh: 2. Biodata Peserta & Anggota Delegasi"
                    />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Subjudul Keterangan:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_2_subtitle" 
                      class="form-control form-control-sm fs-12"
                      placeholder="Contoh: Format Roster Resmi"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Auto-Sync Status Banner -->
            <div class="card border-success-subtle bg-success-subtle p-3 rounded-3 shadow-xs">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-checkbox-circle-fill text-success fs-18"></i>
                  <div>
                    <div class="fs-12 fw-bold text-success-emphasis d-flex align-items-center gap-1.5">
                      <span>Otomatis Tersinkron dengan Manajemen Lomba</span>
                      <span class="badge bg-success text-white fs-10">Real-time</span>
                    </div>
                    <div class="fs-11 text-success-emphasis opacity-90">
                      Aturan Resmi: <strong>{{ selectedCompetition?.participants_rule || '-' }}</strong>
                    </div>
                  </div>
                </div>

                <button 
                  type="button" 
                  class="btn btn-xs btn-outline-success d-flex align-items-center gap-1 fw-bold py-1 px-2.5 rounded-pill bg-white shadow-xs"
                  @click="syncWithCompetitionRules(repeaterField, true)"
                >
                  <i class="ri-refresh-line"></i>
                  <span>Segarkan Kuota</span>
                </button>
              </div>

              <!-- Quota Breakdown Grid -->
              <div class="row g-2 mt-1">
                <div class="col-6 col-sm-3">
                  <div class="bg-white p-2 rounded-2 border text-center">
                    <div class="fs-10 text-muted fw-semibold">Minimal Peserta</div>
                    <div class="fs-14 font-mono fw-bold text-dark">{{ repeaterField?.min_items || 1 }} Siswa</div>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="bg-white p-2 rounded-2 border text-center">
                    <div class="fs-10 text-muted fw-semibold">Maksimal Peserta</div>
                    <div class="fs-14 font-mono fw-bold text-primary">{{ repeaterField?.max_items || 1 }} Siswa</div>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="bg-white p-2 rounded-2 border text-center">
                    <div class="fs-10 text-muted fw-semibold">Pemain Inti</div>
                    <div class="fs-14 font-mono fw-bold text-success">{{ repeaterField?.main_members || repeaterField?.max_items || 1 }} Siswa</div>
                  </div>
                </div>
                <div class="col-6 col-sm-3">
                  <div class="bg-white p-2 rounded-2 border text-center">
                    <div class="fs-10 text-muted fw-semibold">Pemain Cadangan</div>
                    <div class="fs-14 font-mono fw-bold text-warning">{{ repeaterField?.reserve_members || 0 }} Siswa</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Repeater Main Label & Help Text -->
            <div class="card border shadow-xs rounded-3 p-3 bg-body" v-if="repeaterField">
              <h6 class="fs-12 fw-bold text-dark mb-2 d-flex align-items-center gap-1.5">
                <i class="ri-edit-2-line text-primary"></i>
                <span>Teks Label Roster Peserta</span>
              </h6>
              <div class="row g-2">
                <div class="col-md-7">
                  <label class="form-label fs-11 fw-semibold text-muted mb-1">Label Kotak Peserta:</label>
                  <input 
                    type="text" 
                    v-model="repeaterField.label" 
                    class="form-control form-control-sm fs-12 fw-semibold"
                  />
                </div>
                <div class="col-md-5">
                  <label class="form-label fs-11 fw-semibold text-muted mb-1">Petunjuk Pengisian:</label>
                  <input 
                    type="text" 
                    v-model="repeaterField.help_text" 
                    class="form-control form-control-sm fs-11"
                  />
                </div>
              </div>
            </div>

            <!-- Sub-Fields Configurator -->
            <div class="card border shadow-xs rounded-3 p-3 bg-body" v-if="repeaterField?.sub_fields">
              <h6 class="fs-12 fw-bold text-dark mb-2.5 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-1.5">
                  <i class="ri-list-check-2 text-primary fs-14"></i>
                  <span>Konfigurasi Kolom Biodata Siswa Delegasi (Sub-Fields)</span>
                </div>
                <span class="badge bg-light text-muted border fs-10 font-mono">10 Kolom Roster</span>
              </h6>

              <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0 fs-12">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 25%;">Kolom Roster</th>
                      <th style="width: 20%;">Status Aktif</th>
                      <th style="width: 20%;">Wajib Diisi</th>
                      <th style="width: 35%;">Label Kustom</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Nama Siswa -->
                    <tr>
                      <td class="fw-semibold">Nama Lengkap Siswa</td>
                      <td><span class="badge bg-success-subtle text-success">Selalu Aktif</span></td>
                      <td><span class="badge bg-danger-subtle text-danger">Wajib</span></td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.name.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Gender -->
                    <tr>
                      <td class="fw-semibold">Jenis Kelamin (L/P)</td>
                      <td><span class="badge bg-success-subtle text-success">Selalu Aktif</span></td>
                      <td><span class="badge bg-danger-subtle text-danger">Wajib</span></td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.gender.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Kelas -->
                    <tr>
                      <td class="fw-semibold">Kelas / Tingkat</td>
                      <td><span class="badge bg-success-subtle text-success">Selalu Aktif</span></td>
                      <td><span class="badge bg-danger-subtle text-danger">Wajib</span></td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.grade.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Tanggal Lahir -->
                    <tr>
                      <td class="fw-semibold">Tanggal Lahir</td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.birth_date.active" />
                        </div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0" v-if="repeaterField.sub_fields.birth_date.active">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.birth_date.required" />
                        </div>
                        <span v-else class="text-muted fs-11">-</span>
                      </td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.birth_date.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- NISN -->
                    <tr>
                      <td class="fw-semibold">NISN Siswa</td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.nisn.active" />
                        </div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0" v-if="repeaterField.sub_fields.nisn.active">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.nisn.required" />
                        </div>
                        <span v-else class="text-muted fs-11">-</span>
                      </td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.nisn.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Kolom ID Kontekstual -->
                    <tr class="table-info-subtle">
                      <td class="fw-semibold">
                        <div>ID Kontekstual Lomba</div>
                        <div class="fs-10 text-muted">ID MLBB / No Punggung / Peran Musik</div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.custom_id.active" />
                        </div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0" v-if="repeaterField.sub_fields.custom_id.active">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.custom_id.required" />
                        </div>
                        <span v-else class="text-muted fs-11">-</span>
                      </td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.custom_id.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Nama Tim / Skuad -->
                    <tr :class="selectedCompetition?.participant_type === 'tim' ? 'table-warning-subtle' : ''">
                      <td class="fw-semibold">
                        <div>Nama Tim / Skuad</div>
                        <div class="fs-10 text-muted">Otomatis aktif pada lomba beregu</div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.team_name.active" />
                        </div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0" v-if="repeaterField.sub_fields.team_name.active">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.team_name.required" />
                        </div>
                        <span v-else class="text-muted fs-11">-</span>
                      </td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.team_name.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Kapten Tim -->
                    <tr>
                      <td class="fw-semibold">
                        <div>Tandai Kapten Tim</div>
                        <div class="fs-10 text-muted">& No. WhatsApp Kapten</div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.captain_option.active" />
                        </div>
                      </td>
                      <td><span class="text-muted fs-11">Opsional</span></td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.captain_option.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Pas Foto Formal -->
                    <tr>
                      <td class="fw-semibold">Pas Foto Formal (3x4)</td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.photo_formal.active" />
                        </div>
                      </td>
                      <td>
                        <div class="form-check form-switch mb-0" v-if="repeaterField.sub_fields.photo_formal.active">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.photo_formal.required" />
                        </div>
                        <span v-else class="text-muted fs-11">-</span>
                      </td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.photo_formal.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                    <!-- Foto Pose Bebas -->
                    <tr>
                      <td class="fw-semibold">Foto Pose Gaya Bebas</td>
                      <td>
                        <div class="form-check form-switch mb-0">
                          <input class="form-check-input cursor-pointer" type="checkbox" v-model="repeaterField.sub_fields.photo_freestyle.active" />
                        </div>
                      </td>
                      <td><span class="text-muted fs-11">Opsional</span></td>
                      <td>
                        <input type="text" v-model="repeaterField.sub_fields.photo_freestyle.label" class="form-control form-control-xs fs-11 py-1" />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- TAB 3: BERKAS DOKUMEN PENDUKUNG -->
          <div v-show="activeSectionTab === 'section_3'" class="space-y-3">
            <!-- Section 3 Headline Card -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-warning-subtle py-2.5 px-3 border-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-file-upload-line text-warning fs-16"></i>
                  <span class="fs-13 fw-bold text-warning-emphasis">Headline Bagian 3: Berkas Dokumen Pendukung</span>
                </div>
                <span class="badge bg-warning text-dark fs-10">Tampil di Web Publik</span>
              </div>
              <div class="card-body p-3">
                <div class="row g-2">
                  <div class="col-md-7">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Judul Bagian 3:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_3_title" 
                      class="form-control form-control-sm fs-12 fw-semibold"
                      placeholder="Contoh: 3. Berkas Dokumen Pendukung"
                    />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Subjudul Keterangan:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_3_subtitle" 
                      class="form-control form-control-sm fs-12"
                      placeholder="Contoh: Sesuai Juknis Panitia"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Supporting Document Management Header -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="fs-12 fw-bold text-dark d-flex align-items-center gap-1.5">
                <i class="ri-folder-upload-line text-primary fs-15"></i>
                <span>Daftar Dokumen yang Dipersyaratkan ({{ documentFields.length }} Berkas)</span>
              </div>
              <button 
                type="button" 
                class="btn btn-sm btn-primary d-flex align-items-center gap-1 fw-bold shadow-xs px-2.5"
                @click="addDocumentField"
              >
                <i class="ri-add-line fs-14"></i>
                <span>Tambah Persyaratan Berkas</span>
              </button>
            </div>

            <!-- Empty State if no documents -->
            <div v-if="documentFields.length === 0" class="card border border-dashed rounded-3 p-4 text-center bg-body">
              <div class="avatar-md rounded-circle bg-light text-muted mx-auto mb-2 d-flex align-items-center justify-content-center">
                <i class="ri-file-upload-line fs-24"></i>
              </div>
              <h6 class="fs-13 fw-bold text-dark mb-1">Belum Ada Persyaratan Berkas Dokumen</h6>
              <p class="text-muted fs-11 mb-3 mx-auto" style="max-width: 420px;">
                Jika cabang lomba ini mensyaratkan upload berkas (seperti Surat Tugas Kepala Sekolah, Scan KTS, atau Berkas Karya/Naskah), klik tombol di bawah untuk menambahkannya.
              </p>
              <div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" @click="addDocumentField">
                  <i class="ri-add-line me-1"></i> Tambah Berkas Pertama
                </button>
              </div>
            </div>

            <!-- Document Field Cards -->
            <div v-else class="space-y-2.5">
              <div 
                v-for="(doc, dIdx) in documentFields" 
                :key="doc.id" 
                class="card border shadow-xs rounded-3 p-3 bg-body"
              >
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white font-mono fw-bold fs-11">
                      Berkas #{{ dIdx + 1 }}
                    </span>
                    <span class="fs-12 fw-bold text-dark">{{ doc.label }}</span>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <!-- Required switch -->
                    <div class="form-check form-switch mb-0 fs-12">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        :id="`doc_req_${doc.id}`"
                        v-model="doc.required"
                      />
                      <label class="form-check-label fs-11 fw-semibold text-dark cursor-pointer ms-1" :for="`doc_req_${doc.id}`">
                        {{ doc.required ? 'Wajib' : 'Opsional' }}
                      </label>
                    </div>

                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-danger p-1"
                      @click="removeDocumentField(dIdx)"
                      title="Hapus persyaratan berkas ini"
                    >
                      <i class="ri-delete-bin-line fs-13"></i>
                    </button>
                  </div>
                </div>

                <div class="row g-2">
                  <div class="col-md-7">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Nama / Label Dokumen:</label>
                    <input 
                      type="text" 
                      v-model="doc.label" 
                      class="form-control form-control-sm fs-12 fw-semibold"
                      placeholder="Contoh: Scan Surat Tugas / Rekomendasi Kepala Sekolah (PDF)"
                    />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Ukuran Maksimal (MB):</label>
                    <div class="input-group input-group-sm">
                      <input 
                        type="number" 
                        v-model.number="doc.max_size_mb" 
                        class="form-control font-mono fs-12"
                        min="1"
                        max="50"
                      />
                      <span class="input-group-text bg-light text-muted fs-11">MB</span>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Petunjuk Pengisian:</label>
                    <input 
                      type="text" 
                      v-model="doc.help_text" 
                      class="form-control form-control-sm fs-11 text-muted"
                      placeholder="Contoh: Gabungkan seluruh berkas dalam 1 file PDF resmi, maksimal 5 MB."
                    />
                  </div>

                  <!-- Allowed format checkboxes -->
                  <div class="col-12 pt-1">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1 d-block">Format File yang Diizinkan:</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                      <div class="form-check fs-11" v-for="ext in ['pdf', 'jpg', 'jpeg', 'png', 'docx']" :key="ext">
                        <input 
                          class="form-check-input cursor-pointer" 
                          type="checkbox" 
                          :id="`ext_${doc.id}_${ext}`"
                          :checked="doc.allowed_extensions?.includes(ext)"
                          @change="toggleExtension(doc, ext)"
                        />
                        <label class="form-check-label text-dark text-uppercase cursor-pointer fw-semibold ms-0.5" :for="`ext_${doc.id}_${ext}`">
                          .{{ ext }}
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 4: SKEMA PAKET & METODE PEMBAYARAN -->
          <div v-show="activeSectionTab === 'section_4'" class="space-y-3">
            <!-- Section 4 Headline Card -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-info-subtle py-2.5 px-3 border-0 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-bank-card-line text-info fs-16"></i>
                  <span class="fs-13 fw-bold text-info-emphasis">Headline Bagian 4: Skema Paket & Metode Pembayaran</span>
                </div>
                <span class="badge bg-info text-white fs-10">Tampil di Web Publik</span>
              </div>
              <div class="card-body p-3">
                <div class="row g-2">
                  <div class="col-md-7">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Judul Bagian 4:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_4_title" 
                      class="form-control form-control-sm fs-12 fw-semibold"
                      placeholder="Contoh: 4. Skema Paket & Metode Pembayaran"
                    />
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fs-11 fw-bold text-dark mb-1">Subjudul Keterangan:</label>
                    <input 
                      type="text" 
                      v-model="formSettings.section_4_subtitle" 
                      class="form-control form-control-sm fs-12"
                      placeholder="Contoh: Biaya Pendaftaran & Konfirmasi"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Package Options & Code Config -->
            <div class="card border shadow-xs rounded-3 p-3 bg-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                  <h6 class="fs-12 fw-bold text-dark mb-0.5">Opsi Paket Komplit (All-In)</h6>
                  <p class="text-muted fs-11 mb-0">Tampilkan pilihan paket hemat komplit seluruh cabang lomba pada formulir ini</p>
                </div>
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input cursor-pointer" type="checkbox" v-model="formSettings.show_package_type" />
                </div>
              </div>

              <!-- Allin Code Field Customizer -->
              <div class="p-2.5 rounded-2 bg-light border" v-if="allinField">
                <div class="fs-11 fw-bold text-dark mb-2 d-flex align-items-center gap-1.5">
                  <i class="ri-ticket-2-line text-warning"></i>
                  <span>Kustomisasi Field Kode Paket Komplit:</span>
                </div>
                <div class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Label Field Kode:</label>
                    <input type="text" v-model="allinField.label" class="form-control form-control-sm fs-12" />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Placeholder:</label>
                    <input type="text" v-model="allinField.placeholder" class="form-control form-control-sm fs-12" />
                  </div>
                  <div class="col-12">
                    <label class="form-label fs-11 fw-semibold text-muted mb-1">Petunjuk Pengisian:</label>
                    <input type="text" v-model="allinField.help_text" class="form-control form-control-sm fs-11 text-muted" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button Customizer -->
            <div class="card border shadow-xs rounded-3 p-3 bg-body">
              <h6 class="fs-12 fw-bold text-dark mb-2 d-flex align-items-center gap-1.5">
                <i class="ri-cursor-line text-primary"></i>
                <span>Teks Tombol Kirim Formulir</span>
              </h6>
              <div class="row g-2 align-items-center">
                <div class="col-md-8">
                  <input 
                    type="text" 
                    v-model="formSettings.submit_text" 
                    class="form-control form-control-sm fs-12 fw-semibold"
                    placeholder="Contoh: Kirim Pendaftaran Delegasi"
                  />
                </div>
                <div class="col-md-4">
                  <button type="button" class="btn btn-sm btn-primary w-100 disabled">
                    <i class="ri-send-plane-fill me-1"></i> {{ formSettings.submit_text || 'Kirim Pendaftaran' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- Pricing & Bank Summary -->
            <div class="row g-2">
              <div class="col-md-6">
                <div class="card border shadow-xs rounded-3 p-2.5 bg-body h-100">
                  <div class="fs-11 fw-bold text-muted mb-1">Biaya Cabang Ini:</div>
                  <div class="fs-16 font-mono fw-bold text-primary">
                    Rp {{ Number(selectedCompetition?.fee || 0).toLocaleString('id-ID') }}
                  </div>
                  <div class="fs-10 text-muted mt-0.5">Dikelola dari menu Manajemen Lomba</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border shadow-xs rounded-3 p-2.5 bg-body h-100">
                  <div class="fs-11 fw-bold text-muted mb-1">Rekening Transfer Panitia:</div>
                  <template v-if="primaryBank">
                    <div class="fs-13 fw-bold text-dark">
                      {{ primaryBank.bank_name }}
                    </div>
                    <div class="fs-11 font-mono text-muted">
                      {{ primaryBank.account_number }} a.n {{ primaryBank.account_holder }}
                    </div>
                    <div v-if="activeBankAccounts.length > 1" class="fs-10 text-muted mt-0.5">
                      +{{ activeBankAccounts.length - 1 }} rekening alternatif lainnya
                    </div>
                  </template>
                  <template v-else>
                    <div class="fs-12 text-muted fst-italic">
                      Belum ada rekening transfer aktif
                    </div>
                    <div class="fs-10 text-muted mt-0.5">Dapat diatur di Pengaturan Biaya & Pembayaran</div>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Interactive Live Preview -->
        <div :class="viewMode === 'builder' ? 'd-none' : (viewMode === 'split' ? 'col-12 col-xl-5' : 'col-12')">
          <div class="card border-0 shadow-sm rounded-3 bg-body sticky-top" style="top: 80px; z-index: 10;">
            <!-- Preview Device Header -->
            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-1.5 fs-12 fw-bold text-dark">
                <i class="ri-eye-line text-primary fs-14"></i>
                <span>Pratinjau Live Pendaftar</span>
              </div>
              <div class="btn-group btn-group-sm">
                <button 
                  type="button" 
                  class="btn px-2 py-0.5" 
                  :class="previewDevice === 'desktop' ? 'btn-primary' : 'btn-outline-secondary'"
                  @click="previewDevice = 'desktop'"
                  title="Tampilan Desktop"
                >
                  <i class="ri-computer-line"></i>
                </button>
                <button 
                  type="button" 
                  class="btn px-2 py-0.5" 
                  :class="previewDevice === 'mobile' ? 'btn-primary' : 'btn-outline-secondary'"
                  @click="previewDevice = 'mobile'"
                  title="Tampilan Mobile"
                >
                  <i class="ri-smartphone-line"></i>
                </button>
              </div>
            </div>

            <!-- Preview Card Body -->
            <div class="card-body p-3 overflow-y-auto" style="max-height: calc(100vh - 210px);">
              <div :class="previewDevice === 'mobile' ? 'mx-auto' : ''" :style="previewDevice === 'mobile' ? 'max-width: 360px;' : ''">
                <!-- Preview Frame Container -->
                <div class="p-3 rounded-3 border bg-body shadow-xs space-y-4">
                  <!-- Form Title Header -->
                  <div class="text-center pb-2.5 border-bottom">
                    <span class="badge bg-primary text-white fs-10 px-2 py-0.5 rounded-pill mb-1">
                      {{ selectedCompetition?.category_label || 'Perlombaan' }}
                    </span>
                    <h5 class="fs-15 fw-bold text-dark mb-0.5">
                      {{ formSettings.form_title || `Formulir Pendaftaran ${selectedCompetition?.title || 'Lomba'}` }}
                    </h5>
                    <p class="fs-11 text-muted mb-0">
                      {{ formSettings.form_subtitle || 'Silakan lengkapi formulir pendaftaran delegasi sekolah di bawah ini secara teliti.' }}
                    </p>
                  </div>

                  <!-- Preview Section 1: Identitas Sekolah -->
                  <div>
                    <div class="d-flex align-items-center justify-content-between pb-1 mb-2 border-bottom">
                      <div class="fs-12 fw-bold text-primary d-flex align-items-center gap-1">
                        <i class="ri-government-line"></i>
                        <span>{{ formSettings.section_1_title || '1. Identitas Sekolah / Madrasah' }}</span>
                      </div>
                      <span class="fs-10 text-muted">{{ formSettings.section_1_subtitle }}</span>
                    </div>

                    <div class="space-y-1.5 fs-11">
                      <div v-for="idf in identityFields.slice(0, 4)" :key="idf.name" class="p-1.5 rounded-2 bg-light border">
                        <div class="d-flex align-items-center justify-content-between text-muted fs-10">
                          <span>{{ idf.label }}</span>
                          <span v-if="idf.required" class="text-danger">*</span>
                        </div>
                        <div class="text-muted opacity-75 font-italic fs-10">{{ idf.placeholder || '-' }}</div>
                      </div>
                      <div class="text-center text-muted fs-10 pt-1">+ 3 Field Kontak Pendamping</div>
                    </div>
                  </div>

                  <!-- Preview Section 2: Multi-Peserta -->
                  <div>
                    <div class="d-flex align-items-center justify-content-between pb-1 mb-2 border-bottom">
                      <div class="fs-12 fw-bold text-success d-flex align-items-center gap-1">
                        <i class="ri-team-line"></i>
                        <span>{{ formSettings.section_2_title || '2. Biodata Peserta & Anggota Delegasi' }}</span>
                      </div>
                      <span class="fs-10 text-muted">{{ formSettings.section_2_subtitle }}</span>
                    </div>

                    <div class="p-2 rounded-2 border bg-success-subtle text-success-emphasis fs-11">
                      <div class="fw-bold mb-0.5">{{ repeaterField?.label }}</div>
                      <div class="fs-10 opacity-90">{{ repeaterField?.help_text }}</div>
                      <div class="mt-1 d-flex gap-1.5 flex-wrap fs-10">
                        <span class="badge bg-white text-success border">Min: {{ repeaterField?.min_items }} Siswa</span>
                        <span class="badge bg-white text-success border">Maks: {{ repeaterField?.max_items }} Siswa</span>
                        <span v-if="selectedCompetition?.participant_type === 'tim'" class="badge bg-white text-success border">Tim / Skuad</span>
                      </div>
                    </div>
                  </div>

                  <!-- Preview Section 3: Berkas Dokumen -->
                  <div>
                    <div class="d-flex align-items-center justify-content-between pb-1 mb-2 border-bottom">
                      <div class="fs-12 fw-bold text-warning d-flex align-items-center gap-1">
                        <i class="ri-file-upload-line"></i>
                        <span>{{ formSettings.section_3_title || '3. Berkas Dokumen Pendukung' }}</span>
                      </div>
                      <span class="fs-10 text-muted">{{ formSettings.section_3_subtitle }}</span>
                    </div>

                    <div v-if="documentFields.length === 0" class="text-muted fs-10 text-center py-2 bg-light rounded-2 border">
                      Tidak ada persyaratan berkas khusus pada cabang ini.
                    </div>
                    <div v-else class="space-y-1.5 fs-11">
                      <div v-for="doc in documentFields" :key="doc.id" class="p-1.5 rounded-2 bg-light border d-flex align-items-center justify-content-between">
                        <div>
                          <div class="fw-semibold text-dark fs-10">{{ doc.label }}</div>
                          <div class="text-muted fs-9">{{ doc.allowed_extensions?.join(', ').toUpperCase() }} • Maks {{ doc.max_size_mb }} MB</div>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary fs-9">{{ doc.required ? 'Wajib' : 'Opsional' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Preview Section 4: Pembayaran -->
                  <div>
                    <div class="d-flex align-items-center justify-content-between pb-1 mb-2 border-bottom">
                      <div class="fs-12 fw-bold text-info d-flex align-items-center gap-1">
                        <i class="ri-bank-card-line"></i>
                        <span>{{ formSettings.section_4_title || '4. Skema Paket & Metode Pembayaran' }}</span>
                      </div>
                      <span class="fs-10 text-muted">{{ formSettings.section_4_subtitle }}</span>
                    </div>

                    <div class="p-2 rounded-2 bg-light border fs-11 space-y-1">
                      <div class="d-flex justify-content-between">
                        <span class="text-muted">Biaya Satuan Cabang Ini:</span>
                        <span class="font-mono fw-bold text-primary">Rp {{ Number(selectedCompetition?.fee || 0).toLocaleString('id-ID') }}</span>
                      </div>
                      <div v-if="formSettings.show_package_type" class="d-flex justify-content-between pt-1 border-top">
                        <span class="text-muted">Paket All-In Komplit (8 Lomba):</span>
                        <span class="font-mono fw-bold text-warning">Rp {{ Number(packageAllinPrice || 200000).toLocaleString('id-ID') }}</span>
                      </div>
                    </div>

                    <!-- Submit Button Preview -->
                    <div class="mt-2.5">
                      <button type="button" class="btn btn-sm btn-primary w-100 fw-bold disabled fs-11 py-1.5">
                        <i class="ri-send-plane-fill me-1"></i>
                        <span>{{ formSettings.submit_text || 'Kirim Pendaftaran Delegasi' }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Terapkan Headline ke Semua Cabang -->
    <div v-if="showHeadlineModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header bg-light py-2.5 px-3 border-bottom">
            <h5 class="modal-title fs-13 fw-bold text-dark d-flex align-items-center gap-1.5">
              <i class="ri-global-line text-primary"></i>
              <span>Terapkan Headline ke Semua Cabang Lomba</span>
            </h5>
            <button type="button" class="btn-close" @click="showHeadlineModal = false"></button>
          </div>
          <div class="modal-body p-3 fs-12 space-y-2.5">
            <p class="text-muted fs-11 mb-2">
              Judul dan subjudul 4 bagian formulir aktif di bawah ini akan diseragamkan ke seluruh <strong>{{ competitions.length }} Cabang Lomba</strong>:
            </p>

            <div class="p-2 rounded-2 bg-light border space-y-1 fs-11">
              <div><strong>Bagian 1:</strong> {{ formSettings.section_1_title }} <span class="text-muted">• {{ formSettings.section_1_subtitle }}</span></div>
              <div><strong>Bagian 2:</strong> {{ formSettings.section_2_title }} <span class="text-muted">• {{ formSettings.section_2_subtitle }}</span></div>
              <div><strong>Bagian 3:</strong> {{ formSettings.section_3_title }} <span class="text-muted">• {{ formSettings.section_3_subtitle }}</span></div>
              <div><strong>Bagian 4:</strong> {{ formSettings.section_4_title }} <span class="text-muted">• {{ formSettings.section_4_subtitle }}</span></div>
              <div><strong>Tombol Kirim:</strong> {{ formSettings.submit_text }}</div>
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="showHeadlineModal = false">Batal</button>
            <button type="button" class="btn btn-sm btn-primary fw-bold" :disabled="isApplyingHeadlineAll" @click="applyHeadlineAll">
              <span v-if="isApplyingHeadlineAll" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="ri-check-line me-1"></i> Terapkan Sekarang
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL: Salin Pengaturan dari Cabang Lain -->
    <div v-if="showCopyModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header bg-light py-2.5 px-3 border-bottom">
            <h5 class="modal-title fs-13 fw-bold text-dark d-flex align-items-center gap-1.5">
              <i class="ri-file-copy-line text-primary"></i>
              <span>Salin Pengaturan Formulir</span>
            </h5>
            <button type="button" class="btn-close" @click="showCopyModal = false"></button>
          </div>
          <div class="modal-body p-3 fs-12 space-y-2.5">
            <p class="text-muted fs-11 mb-2">
              Salin judul headline, petunjuk, dan berkas persyaratan dari cabang lomba lain ke cabang <strong>{{ selectedCompetition?.title }}</strong>.
            </p>

            <div>
              <label class="form-label fs-11 fw-bold text-dark mb-1">Pilih Cabang Sumber:</label>
              <CustomSelect 
                v-model="sourceCompIdToCopy"
                :options="copySourceOptions"
                size="sm"
              />
            </div>
          </div>
          <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="showCopyModal = false">Batal</button>
            <button type="button" class="btn btn-sm btn-primary fw-bold" @click="copyFromCompetition">
              <i class="ri-check-line me-1"></i> Salin Pengaturan
            </button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.space-y-1 > * + * { margin-top: 0.25rem; }
.space-y-1\.5 > * + * { margin-top: 0.375rem; }
.space-y-2 > * + * { margin-top: 0.5rem; }
.space-y-2\.5 > * + * { margin-top: 0.625rem; }
.space-y-3 > * + * { margin-top: 0.75rem; }
.space-y-4 > * + * { margin-top: 1rem; }
.form-control-xs { height: calc(1.5em + 0.5rem + 2px); padding: 0.25rem 0.5rem; }
</style>
