<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  competitions: { type: Array, default: () => [] },
  selectedCompetition: { type: Object, default: () => ({}) },
  presets: { type: Object, default: () => ({}) },
});

const toast = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// Navigation & View Mode State
const currentCompId = ref(props.selectedCompetition?.id || props.competitions[0]?.id || '');
const viewMode = ref('split'); // 'builder' (Editor Penuh) | 'split' (Berdampingan) | 'preview' (Pratinjau Penuh)
const previewDevice = ref('desktop'); // 'desktop' | 'mobile'
const toolboxTab = ref('types'); // 'types' | 'presets' | 'identity'
const toolboxSearch = ref('');
const canvasSearch = ref('');

// Form Data State
const fields = ref([]);
const formSettings = ref({
  show_package_type: false,
  form_title: '',
  form_subtitle: '',
  submit_text: 'Kirim Pendaftaran Delegasi',
});
const isDirty = ref(false);
const isSaving = ref(false);

// Accordion Expand/Collapse Map: { [fieldId]: boolean }
const expandedMap = ref({});

function isExpanded(id) {
  return expandedMap.value[id] !== false;
}

function toggleExpand(id) {
  expandedMap.value[id] = !isExpanded(id);
}

function expandAll() {
  fields.value.forEach(f => {
    expandedMap.value[f.id] = true;
  });
}

function collapseAll() {
  fields.value.forEach(f => {
    expandedMap.value[f.id] = false;
  });
}

// Competition Index Helpers for Quick Prev / Next navigation
const currentCompIndex = computed(() => {
  return props.competitions.findIndex(c => String(c.id) === String(currentCompId.value));
});

const prevCompetition = computed(() => {
  if (currentCompIndex.value > 0) {
    return props.competitions[currentCompIndex.value - 1];
  }
  return null;
});

const nextCompetition = computed(() => {
  if (currentCompIndex.value >= 0 && currentCompIndex.value < props.competitions.length - 1) {
    return props.competitions[currentCompIndex.value + 1];
  }
  return null;
});

// Initialize Data from Props
function initializeData() {
  const sourceFields = props.selectedCompetition?.custom_fields;
  if (Array.isArray(sourceFields) && sourceFields.length > 0) {
    fields.value = JSON.parse(JSON.stringify(sourceFields));
  } else {
    fields.value = [];
  }

  // Ensure every field has conditional_logic and repeater properties initialized
  fields.value.forEach(f => {
    if (!f.conditional_logic || typeof f.conditional_logic !== 'object') {
      f.conditional_logic = {
        enabled: false,
        depend_on_field: '',
        operator: '==',
        value: '',
      };
    }
    if (f.type === 'participant_repeater') {
      if (!f.min_items) f.min_items = 1;
      if (!f.sub_fields || typeof f.sub_fields !== 'object') {
        f.sub_fields = {
          name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
          gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
          birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
          grade: { active: true, required: true, label: 'Kelas / Tingkat' },
          nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
        };
      }
    }
  });

  // Setup initial expansion: if more than 3 fields, expand first 2, collapse rest
  const newMap = {};
  fields.value.forEach((f, idx) => {
    newMap[f.id] = fields.value.length <= 3 ? true : (idx < 2);
  });
  expandedMap.value = newMap;

  // Initialize preview values for repeaters
  fields.value.forEach(f => {
    if (f.type === 'participant_repeater') {
      previewValues.value[f.name + '_count'] = 3;
    }
  });

  const s = props.selectedCompetition?.form_settings;
  if (s && typeof s === 'object') {
    formSettings.value = {
      show_package_type: !!s.show_package_type,
      form_title: s.form_title || '',
      form_subtitle: s.form_subtitle || '',
      submit_text: s.submit_text || 'Kirim Pendaftaran Delegasi',
    };
  } else {
    formSettings.value = {
      show_package_type: false,
      form_title: '',
      form_subtitle: '',
      submit_text: 'Kirim Pendaftaran Delegasi',
    };
  }

  isDirty.value = false;
}

watch(() => props.selectedCompetition, () => {
  currentCompId.value = props.selectedCompetition?.id || '';
  initializeData();
}, { immediate: true, deep: true });

// Track unsaved changes
watch([fields, formSettings], () => {
  isDirty.value = true;
}, { deep: true });

// Switch Competition
async function switchCompetition(compId) {
  if (String(compId) === String(currentCompId.value)) return;
  if (isDirty.value) {
    const ok = await confirmDialog({
      title: 'Perubahan Belum Disimpan',
      message: 'Ada perubahan formulir yang belum disimpan. Yakin ingin beralih ke cabang lomba lain?',
      type: 'warning',
      confirmText: 'Beralih & Abaikan',
      cancelText: 'Tetap di Sini',
      icon: 'ri-alert-line',
    });
    if (!ok) return;
  }
  router.visit(`/admin/form-builder/${compId}`, { preserveScroll: true });
}

// Helpers
function generateId() {
  return 'cf_' + Math.random().toString(36).substr(2, 9);
}

function makeSlug(text) {
  return (text || '')
    .toString()
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '_')
    .replace(/^-+|-+$/g, '');
}

// 1. Core Identity Field Presets
const coreIdentityPresets = [
  {
    name: 'Asal Sekolah / Lembaga',
    type: 'text',
    default_name: 'school_name',
    label: 'Nama Sekolah / Madrasah / Instansi Pengirim',
    placeholder: 'Contoh: MTs Negeri 1 Jember',
    help_text: 'Nama resmi instansi atau pangkalan delegasi',
    required: true,
    icon: 'ri-community-line',
    desc: 'Wajib untuk sertifikat & administrasi sekolah'
  },
  {
    name: 'Guru / Pembina Pendamping',
    type: 'text',
    default_name: 'mentor_name',
    label: 'Nama Guru / Pembina Pendamping',
    placeholder: 'Contoh: Ustadz Ahmad, S.Pd',
    help_text: 'Nama pembina atau guru penanggung jawab delegasi',
    required: false,
    icon: 'ri-user-star-line',
    desc: 'Untuk kontak koordinasi guru & presensi'
  },
  {
    name: 'Nomor WhatsApp Kontak',
    type: 'text',
    default_name: 'mentor_wa',
    label: 'Nomor WhatsApp Kontak Aktif',
    placeholder: 'Contoh: 081234567890',
    help_text: 'Nomor kontak aktif untuk informasi koordinasi teknis dan TM',
    required: true,
    icon: 'ri-whatsapp-line',
    desc: 'Untuk undangan grup TM & notifikasi pendaftaran'
  },
  {
    name: 'Daftar Peserta / Kontingen',
    type: 'textarea',
    default_name: 'participants_names',
    label: 'Daftar Nama Siswa Peserta & Kelas',
    placeholder: "1. Ahmad Fauzi (Kelas 9A)\n2. Siti Nurhaliza (Kelas 8B)",
    help_text: 'Tuliskan nama lengkap peserta delegasi dan tingkat kelas',
    required: true,
    icon: 'ri-team-line',
    desc: 'Format fleksibel teks roster umum'
  },
];

// 2. Toolbox Component Types (Including Dynamic Participant Repeater with Logic)
const fieldTypes = [
  { 
    type: 'participant_repeater', 
    label: 'Biodata Multi-Peserta / Tim (Dinamis)', 
    icon: 'ri-group-line', 
    desc: 'Form biodata siswa otomatis muncul sesuai jumlah delegasi yang didaftarkan', 
    defaultOptions: [], 
    badge: '⚡ Roster Dinamis',
    isRepeater: true 
  },
  { type: 'text', label: 'Teks Singkat', icon: 'ri-input-field', desc: 'Nama, NISN, ID, Judul', defaultOptions: [], badge: 'Umum' },
  { type: 'number', label: 'Angka / Nominal', icon: 'ri-hashtag', desc: 'Jumlah peserta, usia, skor, no. urut', defaultOptions: [], badge: 'Numerik' },
  { type: 'textarea', label: 'Paragraf / Roster', icon: 'ri-text', desc: 'Deskripsi, daftar anggota, biodata', defaultOptions: [], badge: 'Teks Panjang' },
  { type: 'select', label: 'Pilihan Dropdown', icon: 'ri-arrow-down-s-line', desc: 'Pilih 1 dari daftar opsi', defaultOptions: ['Pilihan 1', 'Pilihan 2', 'Pilihan 3'], badge: 'Pilihan' },
  { type: 'radio', label: 'Pilihan Tunggal (Radio)', icon: 'ri-radio-button-line', desc: 'Pilihan eksklusif radio button', defaultOptions: ['Pilihan A', 'Pilihan B'], badge: 'Pilihan' },
  { type: 'checkbox', label: 'Pilihan Ganda (Checkbox)', icon: 'ri-checkbox-line', desc: 'Centang satu atau beberapa opsi', defaultOptions: ['Item 1', 'Item 2', 'Item 3'], badge: 'Pilihan' },
  { type: 'file', label: 'Upload Berkas / File', icon: 'ri-upload-cloud-2-line', desc: 'Unggah PDF, JPG, PNG, dll.', defaultOptions: [], badge: 'Media' },
  { type: 'url', label: 'Tautan / Link URL', icon: 'ri-link', desc: 'Link Google Drive, YouTube, dll.', defaultOptions: [], badge: 'Tautan' },
  { type: 'date', label: 'Tanggal', icon: 'ri-calendar-line', desc: 'Tanggal lahir, tanggal terbit', defaultOptions: [], badge: 'Waktu' },
];

// 3. Quick Preset Library (Preset Berbagai Cabang Lomba: Tim, Turnamen, Beregu, Musik/Banjari)
const quickPresets = [
  {
    name: 'Grup Musik / Albanjari / Hadrah (Multi-Personil)',
    type: 'participant_repeater',
    label: 'Biodata Personil Tim Hadrah / Albanjari',
    help_text: 'Tentukan jumlah personil yang dikirimkan, lalu lengkapi biodata masing-masing siswa.',
    required: true,
    max_items: 10,
    min_items: 1,
    sub_fields: {
      name: { active: true, required: true, label: 'Nama Lengkap Personil' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
    },
    category: 'Grup Seni / Musik',
  },
  {
    name: 'Turnamen Tim Futsal / Olahraga (Line-up / Roster)',
    type: 'participant_repeater',
    label: 'Roster Tim Pemain (Inti & Cadangan)',
    help_text: 'Masukkan jumlah pemain yang didaftarkan dalam skuad tim turnamen.',
    required: true,
    max_items: 12,
    min_items: 5,
    sub_fields: {
      name: { active: true, required: true, label: 'Nama Lengkap Pemain' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: true, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: true, label: 'NISN Pemain' },
    },
    category: 'Turnamen Olahraga',
  },
  {
    name: 'Regu Cerdas Cermat / LCC / Debat (3 Siswa)',
    type: 'participant_repeater',
    label: 'Biodata Anggota Regu LCC / Debat',
    help_text: 'Isi biodata untuk juru bicara dan anggota regu delegasi.',
    required: true,
    max_items: 3,
    min_items: 3,
    sub_fields: {
      name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: true, label: 'Nomor Induk Siswa (NISN)' },
    },
    category: 'Lomba Beregu',
  },
  {
    name: 'Tim Riset / Robotik / Karya Ilmiah (2 Siswa)',
    type: 'participant_repeater',
    label: 'Biodata Tim Peneliti Muda (Ketua & Anggota)',
    help_text: 'Lengkapi biodata ketua tim dan anggota tim riset.',
    required: true,
    max_items: 2,
    min_items: 2,
    sub_fields: {
      name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
    },
    category: 'Lomba Beregu',
  },
  {
    name: 'Delegasi Kontingen Dinamis (Batas Fleksibel / Kustom)',
    type: 'participant_repeater',
    label: 'Biodata Siswa Delegasi Sekolah',
    help_text: 'Jumlah formulir biodata otomatis muncul sesuai angka yang dipilih pendaftar.',
    required: true,
    max_items: 15,
    min_items: 1,
    sub_fields: {
      name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
    },
    category: 'Kontingen Dinamis',
  },
  {
    name: 'Peserta Utama & Cadangan (1-2 Siswa)',
    type: 'participant_repeater',
    label: 'Biodata Peserta Utama & Cadangan',
    help_text: 'Pilih 1 siswa jika hanya peserta utama, atau 2 siswa jika menyertakan cadangan.',
    required: true,
    max_items: 2,
    min_items: 1,
    sub_fields: {
      name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
    },
    category: 'Individu / Cadangan',
  },
  {
    name: 'Jumlah Peserta yang Dikirimkan',
    type: 'number',
    label: 'Jumlah Peserta yang Didaftarkan',
    placeholder: 'Contoh: 3',
    help_text: 'Tuliskan angka jumlah siswa yang didelegasikan oleh sekolah',
    required: true,
    category: 'Logika',
  },
  {
    name: 'Nomor Induk Siswa (NISN)',
    type: 'text',
    label: 'Nomor Induk Siswa Nasional (NISN)',
    placeholder: 'Contoh: 0081234567',
    help_text: 'NISN resmi peserta delegasi terdaftar di Kemendikbud/Kemenag',
    required: true,
    category: 'Akademik',
  },
  {
    name: 'Scan / Foto Kartu Pelajar (KTS)',
    type: 'file',
    label: 'Scan / Foto Kartu Tanda Siswa (KTS)',
    help_text: 'Unggah foto Kartu Pelajar atau Surat Keterangan Siswa Aktif (PDF/JPG/PNG)',
    required: true,
    allowed_extensions: ['pdf', 'jpg', 'jpeg', 'png'],
    max_size_mb: 5,
    category: 'Verifikasi',
  },
  {
    name: 'Nama Tim / Squad',
    type: 'text',
    label: 'Nama Tim / Squad Resmi',
    placeholder: 'Contoh: Garuda Muda MTs',
    help_text: 'Nama resmi tim yang akan tercantum pada bagan turnamen',
    required: true,
    category: 'Turnamen',
  },
  {
    name: 'ID Game & Server (E-Sport)',
    type: 'text',
    label: 'User ID & Zone Server Game',
    placeholder: 'Contoh: 12345678 (2045)',
    help_text: 'ID akun in-game beserta zona server dalam kurung',
    required: true,
    category: 'E-Sport',
  },
  {
    name: 'Pilihan Golongan Juz',
    type: 'radio',
    label: 'Golongan Juz yang Diujikan',
    help_text: 'Pilih kategori golongan juz hafalan',
    required: true,
    options: ['Juz 1 s.d. 5', 'Juz 26 s.d. 30'],
    category: 'Keagamaan',
  },
  {
    name: 'Surat Rekomendasi Resmi',
    type: 'file',
    label: 'Surat Rekomendasi Kepala Madrasah',
    help_text: 'Unggah surat rekomendasi delegasi resmi bertandatangan & stempel (PDF)',
    required: true,
    allowed_extensions: ['pdf'],
    max_size_mb: 5,
    category: 'Verifikasi',
  },
];

// Filtered Toolbox Items based on Search
const filteredFieldTypes = computed(() => {
  if (!toolboxSearch.value.trim()) return fieldTypes;
  const q = toolboxSearch.value.toLowerCase();
  return fieldTypes.filter(f => f.label.toLowerCase().includes(q) || f.desc.toLowerCase().includes(q) || f.type.toLowerCase().includes(q));
});

const filteredQuickPresets = computed(() => {
  if (!toolboxSearch.value.trim()) return quickPresets;
  const q = toolboxSearch.value.toLowerCase();
  return quickPresets.filter(p => p.name.toLowerCase().includes(q) || p.label.toLowerCase().includes(q) || p.category.toLowerCase().includes(q));
});

const filteredCorePresets = computed(() => {
  if (!toolboxSearch.value.trim()) return coreIdentityPresets;
  const q = toolboxSearch.value.toLowerCase();
  return coreIdentityPresets.filter(c => c.name.toLowerCase().includes(q) || c.label.toLowerCase().includes(q));
});

// Filtered Canvas Fields based on canvas search
const displayedFields = computed(() => {
  if (!canvasSearch.value.trim()) return fields.value;
  const q = canvasSearch.value.toLowerCase();
  return fields.value.filter(f => (f.label || '').toLowerCase().includes(q) || (f.name || '').toLowerCase().includes(q) || (f.type || '').toLowerCase().includes(q));
});

// Add Field from Toolbox
function addField(typeObj) {
  const id = generateId();
  const label = typeObj.type === 'participant_repeater' ? 'Biodata Siswa Delegasi' : `Pertanyaan Baru (${typeObj.label})`;
  const name = makeSlug(label);

  const newField = {
    id: id,
    name: name,
    label: label,
    type: typeObj.type,
    placeholder: '',
    help_text: typeObj.type === 'participant_repeater' ? 'Jumlah biodata siswa yang tampil otomatis menyesuaikan angka yang dipilih pendaftar.' : '',
    required: typeObj.type === 'participant_repeater',
    options: typeObj.defaultOptions ? [...typeObj.defaultOptions] : [],
    allowed_extensions: typeObj.type === 'file' ? ['pdf', 'jpg', 'jpeg', 'png'] : [],
    max_size_mb: typeObj.type === 'file' ? 5 : null,
    min_items: typeObj.type === 'participant_repeater' ? 1 : null,
    max_items: typeObj.type === 'participant_repeater' ? 10 : null,
    sub_fields: typeObj.type === 'participant_repeater' ? {
      name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
    } : null,
    conditional_logic: {
      enabled: false,
      depend_on_field: '',
      operator: '==',
      value: '',
    }
  };

  fields.value.push(newField);
  expandedMap.value[id] = true;
  if (typeObj.type === 'participant_repeater') {
    previewValues.value[name + '_count'] = 3;
  }
  toast.info(`Field "${typeObj.label}" ditambahkan.`);
}

// Add Core Identity Preset
function addCorePreset(preset) {
  const id = generateId();
  const newField = {
    id: id,
    name: preset.default_name || makeSlug(preset.label),
    label: preset.label,
    type: preset.type,
    placeholder: preset.placeholder || '',
    help_text: preset.help_text || '',
    required: !!preset.required,
    options: [],
    allowed_extensions: [],
    max_size_mb: null,
    conditional_logic: {
      enabled: false,
      depend_on_field: '',
      operator: '==',
      value: '',
    }
  };

  fields.value.push(newField);
  expandedMap.value[id] = true;
  toast.success(`Field "${preset.name}" ditambahkan!`);
}

// Add Quick Preset
function addQuickPreset(preset) {
  const id = generateId();
  const name = makeSlug(preset.label);
  const newField = {
    id: id,
    name: name,
    label: preset.label,
    type: preset.type,
    placeholder: preset.placeholder || '',
    help_text: preset.help_text || '',
    required: !!preset.required,
    options: preset.options ? [...preset.options] : [],
    allowed_extensions: preset.allowed_extensions ? [...preset.allowed_extensions] : [],
    max_size_mb: preset.max_size_mb || (preset.type === 'file' ? 5 : null),
    min_items: preset.min_items || (preset.type === 'participant_repeater' ? 1 : null),
    max_items: preset.max_items !== undefined ? preset.max_items : null,
    sub_fields: preset.sub_fields ? JSON.parse(JSON.stringify(preset.sub_fields)) : (preset.type === 'participant_repeater' ? {
      name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
      gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
      birth_date: { active: true, required: false, label: 'Tanggal Lahir' },
      grade: { active: true, required: true, label: 'Kelas / Tingkat' },
      nisn: { active: true, required: false, label: 'Nomor Induk Siswa (NISN)' },
    } : null),
    conditional_logic: {
      enabled: false,
      depend_on_field: '',
      operator: '==',
      value: '',
    }
  };

  fields.value.push(newField);
  expandedMap.value[id] = true;
  if (preset.type === 'participant_repeater') {
    previewValues.value[name + '_count'] = 3;
  }
  toast.success(`Preset "${preset.name}" ditambahkan!`);
}

// Field Operations
async function removeField(index) {
  const f = fields.value[index];
  const ok = await confirmDialog({
    title: 'Hapus Pertanyaan?',
    message: `Apakah Anda yakin ingin menghapus "${f.label}"? Pertanyaan ini tidak akan muncul lagi di formulir pendaftaran.`,
    type: 'danger',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
    icon: 'ri-delete-bin-line',
  });
  if (ok) {
    fields.value.splice(index, 1);
    delete expandedMap.value[f.id];
    toast.info(`Field "${f.label}" telah dihapus.`);
  }
}

function duplicateField(index) {
  const original = fields.value[index];
  const clone = JSON.parse(JSON.stringify(original));
  clone.id = generateId();
  clone.label = clone.label + ' (Salinan)';
  clone.name = makeSlug(clone.label);
  fields.value.splice(index + 1, 0, clone);
  expandedMap.value[clone.id] = true;
  toast.success('Field berhasil diduplikasi!');
}

function moveFieldUp(index) {
  if (index > 0) {
    const item = fields.value.splice(index, 1)[0];
    fields.value.splice(index - 1, 0, item);
  }
}

function moveFieldDown(index) {
  if (index < fields.value.length - 1) {
    const item = fields.value.splice(index, 1)[0];
    fields.value.splice(index + 1, 0, item);
  }
}

// Options Manager
function addOption(field) {
  if (!Array.isArray(field.options)) {
    field.options = [];
  }
  field.options.push(`Pilihan ${field.options.length + 1}`);
}

function removeOption(field, optIndex) {
  if (field.options.length <= 1) {
    toast.warning('Minimal harus memiliki 1 pilihan opsi.');
    return;
  }
  field.options.splice(optIndex, 1);
}

// Bulk Option Editor Modal
const showBulkModal = ref(false);
const bulkEditingField = ref(null);
const bulkText = ref('');

function openBulkOptions(field) {
  bulkEditingField.value = field;
  bulkText.value = (field.options || []).join('\n');
  showBulkModal.value = true;
}

function saveBulkOptions() {
  if (!bulkEditingField.value) return;
  const lines = bulkText.value
    .split('\n')
    .map(l => l.trim())
    .filter(l => l.length > 0);
  
  if (lines.length === 0) {
    toast.warning('Daftar opsi tidak boleh kosong.');
    return;
  }

  bulkEditingField.value.options = lines;
  showBulkModal.value = false;
  toast.success(`${lines.length} opsi berhasil diperbarui!`);
}

function applyOptionPreset(field, type) {
  if (type === 'yesno') {
    field.options = ['Ya', 'Tidak'];
  } else if (type === 'genders') {
    field.options = ['Laki-laki', 'Perempuan'];
  } else if (type === 'classes') {
    field.options = ['Kelas 7 SMP/MTs', 'Kelas 8 SMP/MTs', 'Kelas 9 SMP/MTs'];
  }
  toast.info('Preset opsi diterapkan.');
}

// File Extensions Toggle & Presets
function toggleExtension(field, ext) {
  if (!Array.isArray(field.allowed_extensions)) {
    field.allowed_extensions = [];
  }
  const idx = field.allowed_extensions.indexOf(ext);
  if (idx === -1) {
    field.allowed_extensions.push(ext);
  } else {
    field.allowed_extensions.splice(idx, 1);
  }
}

function setFilePreset(field, preset) {
  if (preset === 'pdf') {
    field.allowed_extensions = ['pdf'];
  } else if (preset === 'images') {
    field.allowed_extensions = ['jpg', 'jpeg', 'png'];
  } else if (preset === 'audio') {
    field.allowed_extensions = ['mp3', 'wav'];
  } else if (preset === 'docs') {
    field.allowed_extensions = ['pdf', 'docx'];
  } else if (preset === 'all') {
    field.allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'zip'];
  }
  toast.info(`Preset format berkas "${preset}" diterapkan.`);
}

// Conditional Logic Evaluator for Forms
function evaluateCondition(logic, values) {
  if (!logic || !logic.enabled || !logic.depend_on_field) {
    return true; // No active condition: always visible
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

// Drag & Drop
let draggedItemIndex = null;
const dragOverIndex = ref(null);

function onDragStart(index, event) {
  draggedItemIndex = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', index);
}

function onDragOver(index, event) {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'move';
  dragOverIndex.value = index;
}

function onDragLeave() {
  dragOverIndex.value = null;
}

function onDrop(targetIndex, event) {
  event.preventDefault();
  dragOverIndex.value = null;
  if (draggedItemIndex === null || draggedItemIndex === targetIndex) return;

  const item = fields.value.splice(draggedItemIndex, 1)[0];
  fields.value.splice(targetIndex, 0, item);
  draggedItemIndex = null;
  toast.info('Urutan pertanyaan berhasil diubah.');
}

// Save Form
function saveFormFields() {
  if (!currentCompId.value) return;

  // Validation
  for (let i = 0; i < fields.value.length; i++) {
    const f = fields.value[i];
    if (!f.label || !f.label.trim()) {
      expandedMap.value[f.id] = true;
      toast.warning(`Pertanyaan ke-${i + 1} belum memiliki Label Pertanyaan.`);
      return;
    }
    if (['select', 'radio', 'checkbox'].includes(f.type)) {
      if (!f.options || f.options.length === 0) {
        expandedMap.value[f.id] = true;
        toast.warning(`Field "${f.label}" harus memiliki minimal 1 pilihan opsi.`);
        return;
      }
    }
    if (!f.name || !f.name.trim()) {
      f.name = makeSlug(f.label);
    }
  }

  isSaving.value = true;
  router.post(`/admin/form-builder/${currentCompId.value}`, {
    custom_fields: fields.value,
    form_settings: formSettings.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      isDirty.value = false;
      toast.success(`Formulir cabang "${props.selectedCompetition?.title}" berhasil disimpan!`, 'Tersimpan');
    },
    onError: () => {
      isSaving.value = false;
      toast.error('Gagal menyimpan formulir pendaftaran.', 'Terjadi Kesalahan');
    }
  });
}

// Modals
const showPresetModal = ref(false);
const showCopyModal = ref(false);
const showSettingsModal = ref(false);
const sourceCompIdToCopy = ref('');

async function applyPreset(presetKey) {
  const ok = await confirmDialog({
    title: 'Terapkan Template Preset?',
    message: `Terapkan template "${presetKey}"? Susunan pertanyaan saat ini akan digantikan dengan template siap pakai terpilih.`,
    type: 'warning',
    confirmText: 'Ya, Terapkan',
    cancelText: 'Batal',
    icon: 'ri-magic-line',
  });
  if (ok) {
    router.post(`/admin/form-builder/${currentCompId.value}/preset`, {
      preset: presetKey,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        showPresetModal.value = false;
        toast.success(`Template preset "${presetKey}" berhasil diterapkan!`);
      }
    });
  }
}

function executeCopy() {
  if (!sourceCompIdToCopy.value) return;
  router.post(`/admin/form-builder/${currentCompId.value}/copy`, {
    source_competition_id: sourceCompIdToCopy.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showCopyModal.value = false;
      toast.success('Formulir berhasil disalin dari cabang lain!');
    }
  });
}

async function resetForm() {
  const ok = await confirmDialog({
    title: 'Kosongkan Seluruh Formulir?',
    message: `Yakin ingin mengosongkan semua field formulir cabang "${props.selectedCompetition?.title}"? Semua pertanyaan yang telah dikonfigurasi akan dihapus.`,
    type: 'danger',
    confirmText: 'Ya, Kosongkan',
    cancelText: 'Batal',
    icon: 'ri-eraser-line',
  });
  if (ok) {
    router.post(`/admin/form-builder/${currentCompId.value}/reset`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        toast.info('Formulir telah dikosongkan.');
      }
    });
  }
}

// Keyboard Shortcut: Ctrl+S to save
function handleKeyDown(e) {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    saveFormFields();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
});

// Interactive Live Preview Simulation State
const previewValues = ref({});

function changeParticipantCount(field, delta) {
  const min = Number(field.min_items) || 1;
  const max = field.max_items ? Number(field.max_items) : 50;
  const current = previewValues.value[field.name + '_count'] || min;
  const next = Math.max(min, Math.min(max, current + delta));
  previewValues.value[field.name + '_count'] = next;
  previewValues.value[field.name] = next; // Also update direct value for any condition watcher!
}

function setParticipantCountDirect(field, val) {
  const min = Number(field.min_items) || 1;
  const max = field.max_items ? Number(field.max_items) : 50;
  const next = Math.max(min, Math.min(max, Number(val) || min));
  previewValues.value[field.name + '_count'] = next;
  previewValues.value[field.name] = next;
}
</script>

<template>
  <VelzonLayout>
    <Head :title="`Formulir Builder: ${selectedCompetition?.title || 'Cabang Lomba'} | Semarak Aliyah XIV`" />

    <div class="form-builder-wrapper pb-5">
      <!-- ============================================================== -->
      <!-- TOP COMMAND BAR: SWITCHER, STATUS, VIEW MODES, SAVE            -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm mb-3 top-command-bar">
        <div class="card-body p-3">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            
            <!-- Left: Competition Selector & Navigation -->
            <div class="d-flex align-items-center gap-2 flex-grow-1 flex-md-grow-0">
              <div class="d-flex align-items-center gap-1">
                <button 
                  type="button" 
                  class="btn btn-sm btn-icon btn-outline-secondary" 
                  :disabled="!prevCompetition"
                  @click="prevCompetition && switchCompetition(prevCompetition.id)"
                  title="Cabang Sebelumnya"
                >
                  <i class="ri-arrow-left-s-line fs-16"></i>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm btn-icon btn-outline-secondary" 
                  :disabled="!nextCompetition"
                  @click="nextCompetition && switchCompetition(nextCompetition.id)"
                  title="Cabang Berikutnya"
                >
                  <i class="ri-arrow-right-s-line fs-16"></i>
                </button>
              </div>

              <!-- Sleek Competition Selector Dropdown -->
              <div class="dropdown flex-grow-1">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-warning-subtle text-warning border-0 px-2.5">
                    <i class="ri-trophy-line fs-14"></i>
                  </span>
                  <select 
                    class="form-select form-select-sm fw-bold border-0 bg-light-subtle comp-select shadow-none"
                    :value="currentCompId"
                    @change="switchCompetition($event.target.value)"
                  >
                    <option v-for="c in competitions" :key="c.id" :value="c.id">
                      {{ c.icon }} {{ c.title }} ({{ c.custom_fields?.length || 0 }} Pertanyaan) — {{ c.category_label || c.category }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Unsaved Status Indicator -->
              <div v-if="isDirty" class="d-flex align-items-center gap-1.5 px-2 py-1 rounded-pill bg-warning-subtle text-warning-emphasis fs-11 fw-semibold text-nowrap">
                <span class="pulse-dot bg-warning"></span>
                <span>Belum Disimpan</span>
              </div>
              <div v-else class="d-flex align-items-center gap-1 px-2 py-1 rounded-pill bg-success-subtle text-success fs-11 fw-semibold text-nowrap">
                <i class="ri-check-line"></i>
                <span>Tersimpan</span>
              </div>
            </div>

            <!-- Center: View Mode Switcher (Editor / Split / Preview) -->
            <div class="d-flex align-items-center justify-content-center">
              <div class="view-mode-pill p-1 rounded-pill bg-light d-flex gap-1 border">
                <button 
                  type="button" 
                  class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold transition-all"
                  :class="viewMode === 'builder' ? 'btn-primary shadow-sm text-white' : 'btn-ghost-secondary text-muted'"
                  @click="viewMode = 'builder'"
                >
                  <i class="ri-layout-masonry-line me-1"></i> Editor Penuh
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold transition-all"
                  :class="viewMode === 'split' ? 'btn-primary shadow-sm text-white' : 'btn-ghost-secondary text-muted'"
                  @click="viewMode = 'split'"
                  title="Editor dan Live Pratinjau berdampingan secara real-time"
                >
                  <i class="ri-side-bar-line me-1"></i> Berdampingan (Split)
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold transition-all"
                  :class="viewMode === 'preview' ? 'btn-info shadow-sm text-white' : 'btn-ghost-secondary text-muted'"
                  @click="viewMode = 'preview'"
                  title="Simulasi formulir publik penuh layar"
                >
                  <i class="ri-eye-line me-1"></i> Pratinjau Penuh
                </button>
              </div>
            </div>

            <!-- Right: Quick Tools & Save Button -->
            <div class="d-flex align-items-center gap-2">
              <div class="btn-group">
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1.5"
                  data-bs-toggle="dropdown" 
                  aria-expanded="false"
                >
                  <i class="ri-more-2-fill"></i>
                  <span class="d-none d-sm-inline">Opsi Formulir</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-1 fs-12">
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer" @click="showSettingsModal = true">
                      <i class="ri-settings-3-line text-primary"></i> Pengaturan Judul & Paket
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer" @click="showPresetModal = true">
                      <i class="ri-magic-line text-warning"></i> Terapkan Template Siap Pakai
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer" @click="showCopyModal = true">
                      <i class="ri-file-copy-line text-info"></i> Salin dari Cabang Lomba Lain
                    </a>
                  </li>
                  <li><hr class="dropdown-divider my-1"></li>
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger cursor-pointer" @click="resetForm">
                      <i class="ri-refresh-line"></i> Kosongkan Seluruh Field
                    </a>
                  </li>
                </ul>
              </div>

              <button 
                type="button" 
                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 fw-bold px-3 shadow-sm save-btn"
                :disabled="isSaving"
                @click="saveFormFields"
                title="Simpan formulir (Ctrl + S)"
              >
                <i v-if="!isSaving" class="ri-save-line fs-14"></i>
                <span v-if="isSaving" class="spinner-border spinner-border-sm"></span>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Formulir' }}</span>
                <span class="badge bg-dark-subtle text-white font-mono fs-10 px-1 py-0.5 rounded ms-1 opacity-75 d-none d-md-inline">^S</span>
              </button>
            </div>

          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- MAIN WORKSPACE CONTENT                                         -->
      <!-- ============================================================== -->
      <div v-show="viewMode !== 'preview'" class="row g-3">
        
        <!-- LEFT COLUMN: UNIFIED SMART TOOLBOX (Sticky & Tabbed) -->
        <div :class="viewMode === 'split' ? 'col-xl-3 col-lg-4' : 'col-xl-4 col-lg-4'">
          <div class="card border-0 shadow-sm sticky-toolbox">
            
            <!-- Toolbox Header with Search -->
            <div class="card-header bg-transparent border-bottom p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                  <i class="ri-tools-line text-primary fs-15"></i>
                  <span>Tambah Komponen</span>
                </h6>
                <span class="badge bg-primary-subtle text-primary fs-10">Klik untuk Tambah</span>
              </div>

              <!-- Quick Search Input -->
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted ps-2.5">
                  <i class="ri-search-line fs-12"></i>
                </span>
                <input 
                  type="text" 
                  v-model="toolboxSearch"
                  class="form-control form-control-sm border-start-0 bg-light fs-12"
                  placeholder="Cari tipe input atau preset..."
                />
                <button 
                  v-if="toolboxSearch" 
                  class="btn btn-sm btn-light border" 
                  @click="toolboxSearch = ''"
                  type="button"
                >
                  <i class="ri-close-line"></i>
                </button>
              </div>

              <!-- Segmented Tabs for Toolbox -->
              <div class="d-flex gap-1 mt-2.5 pt-1">
                <button 
                  type="button" 
                  class="btn btn-xs flex-fill rounded-2 fw-semibold py-1.5 transition-all"
                  :class="toolboxTab === 'types' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary'"
                  @click="toolboxTab = 'types'"
                >
                  Tipe Input ({{ fieldTypes.length }})
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs flex-fill rounded-2 fw-semibold py-1.5 transition-all"
                  :class="toolboxTab === 'presets' ? 'btn-warning text-dark shadow-sm' : 'btn-outline-secondary'"
                  @click="toolboxTab = 'presets'"
                >
                  Preset ({{ quickPresets.length }})
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs flex-fill rounded-2 fw-semibold py-1.5 transition-all"
                  :class="toolboxTab === 'identity' ? 'btn-info text-white shadow-sm' : 'btn-outline-secondary'"
                  @click="toolboxTab = 'identity'"
                >
                  Identitas ({{ coreIdentityPresets.length }})
                </button>
              </div>
            </div>

            <!-- Toolbox Body Items -->
            <div class="card-body p-2.5 toolbox-scroll-area">
              
              <!-- Tab 1: Basic Input Types -->
              <div v-show="toolboxTab === 'types'" class="d-flex flex-column gap-1.5">
                <div v-if="filteredFieldTypes.length === 0" class="text-center py-4 text-muted fs-12">
                  Tidak ada tipe input cocok dengan "{{ toolboxSearch }}"
                </div>
                <button 
                  v-for="ft in filteredFieldTypes" 
                  :key="ft.type"
                  type="button" 
                  class="btn btn-sm btn-outline-light text-start p-2 rounded-2 border d-flex align-items-center justify-content-between transition-all hover-tool-card"
                  :class="{ 'border-warning-subtle bg-warning-subtle/10': ft.isRepeater }"
                  @click="addField(ft)"
                  :title="`Klik untuk menambahkan pertanyaan bertipe ${ft.label}`"
                >
                  <div class="d-flex align-items-center gap-2 text-truncate">
                    <div 
                      class="tool-icon-box rounded-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                      :class="ft.isRepeater ? 'bg-warning-subtle text-warning' : ''"
                    >
                      <i :class="[ft.icon, ft.isRepeater ? 'text-warning' : 'text-primary']" class="fs-15"></i>
                    </div>
                    <div class="text-truncate">
                      <div class="fs-12 fw-bold text-dark text-truncate lh-sm d-flex align-items-center gap-1">
                        <span>{{ ft.label }}</span>
                        <span v-if="ft.isRepeater" class="badge bg-warning text-dark fs-9 px-1 py-0">Baru</span>
                      </div>
                      <div class="text-muted fs-10 text-truncate lh-sm mt-0.5">{{ ft.desc }}</div>
                    </div>
                  </div>
                  <i class="ri-add-line fs-14 text-muted flex-shrink-0 ms-1"></i>
                </button>
              </div>

              <!-- Tab 2: Quick Presets -->
              <div v-show="toolboxTab === 'presets'" class="d-flex flex-column gap-1.5">
                <div v-if="filteredQuickPresets.length === 0" class="text-center py-4 text-muted fs-12">
                  Tidak ada preset cocok dengan "{{ toolboxSearch }}"
                </div>
                <button 
                  v-for="(qp, qIdx) in filteredQuickPresets" 
                  :key="qIdx"
                  type="button" 
                  class="btn btn-sm btn-outline-light text-start p-2 rounded-2 border d-flex align-items-center justify-content-between transition-all hover-tool-card"
                  @click="addQuickPreset(qp)"
                  :title="`Klik untuk menambahkan preset ${qp.name}`"
                >
                  <div class="d-flex align-items-center gap-2 text-truncate">
                    <div class="tool-icon-box rounded-2 flex-shrink-0 d-flex align-items-center justify-content-center bg-warning-subtle text-warning">
                      <i 
                        :class="qp.type === 'participant_repeater' ? 'ri-group-line text-warning' : (qp.type === 'file' ? 'ri-upload-cloud-2-line text-danger' : (qp.type === 'url' ? 'ri-link text-info' : (qp.type === 'radio' ? 'ri-radio-button-line text-success' : 'ri-flashlight-line text-warning')))"
                        class="fs-15"
                      ></i>
                    </div>
                    <div class="text-truncate">
                      <div class="fs-12 fw-bold text-dark text-truncate lh-sm">{{ qp.name }}</div>
                      <div class="text-muted fs-10 text-truncate lh-sm mt-0.5">{{ qp.category }} • {{ qp.type.toUpperCase() }}</div>
                    </div>
                  </div>
                  <i class="ri-add-line fs-14 text-muted flex-shrink-0 ms-1"></i>
                </button>
              </div>

              <!-- Tab 3: Core Identity Presets -->
              <div v-show="toolboxTab === 'identity'" class="d-flex flex-column gap-1.5">
                <div v-if="filteredCorePresets.length === 0" class="text-center py-4 text-muted fs-12">
                  Tidak ada identitas cocok dengan "{{ toolboxSearch }}"
                </div>
                <button 
                  v-for="(cip, cIdx) in filteredCorePresets" 
                  :key="cIdx"
                  type="button" 
                  class="btn btn-sm btn-outline-light text-start p-2 rounded-2 border d-flex align-items-center justify-content-between transition-all hover-tool-card"
                  @click="addCorePreset(cip)"
                >
                  <div class="d-flex align-items-center gap-2 text-truncate">
                    <div class="tool-icon-box rounded-2 flex-shrink-0 d-flex align-items-center justify-content-center bg-info-subtle text-info">
                      <i :class="cip.icon" class="fs-15 text-info"></i>
                    </div>
                    <div class="text-truncate">
                      <div class="fs-12 fw-bold text-dark text-truncate lh-sm">{{ cip.name }}</div>
                      <div class="text-muted fs-10 text-truncate lh-sm mt-0.5">{{ cip.desc }}</div>
                    </div>
                  </div>
                  <i class="ri-add-line fs-14 text-muted flex-shrink-0 ms-1"></i>
                </button>
              </div>

            </div>

            <!-- Toolbox Footer Quick Helpers -->
            <div class="card-footer bg-light-subtle border-top p-2.5 d-flex gap-1.5">
              <button 
                type="button" 
                class="btn btn-xs btn-outline-primary flex-fill d-flex align-items-center justify-content-center gap-1"
                @click="showPresetModal = true"
              >
                <i class="ri-magic-line"></i> Template Lomba
              </button>
              <button 
                type="button" 
                class="btn btn-xs btn-outline-secondary flex-fill d-flex align-items-center justify-content-center gap-1"
                @click="showSettingsModal = true"
              >
                <i class="ri-equalizer-line"></i> Opsi Form
              </button>
            </div>

          </div>
        </div>

        <!-- CENTER / MAIN COLUMN: FORM CANVAS (Ergonomic Accordion Cards) -->
        <div :class="viewMode === 'split' ? 'col-xl-5 col-lg-8' : 'col-xl-8 col-lg-8'">
          <div class="card border-0 shadow-sm form-canvas-card">
            
            <!-- Canvas Header Bar with Controls -->
            <div class="card-header bg-transparent border-bottom py-3 px-3 px-sm-4">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <span>{{ selectedCompetition?.title }}</span>
                    <span class="badge bg-primary text-white fs-11 font-mono rounded-pill px-2">
                      {{ fields.length }} Field
                    </span>
                  </h5>
                  <p class="text-muted fs-11 mb-0 mt-0.5">
                    Urutkan dan sesuaikan konfigurasi logika & formulir bebas tanpa terkunci.
                  </p>
                </div>

                <!-- Global Expand/Collapse & Filter Controls -->
                <div class="d-flex align-items-center gap-1.5">
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-secondary d-inline-flex align-items-center gap-1"
                    @click="collapseAll"
                    title="Ciutkan semua field menjadi daftar ringkas untuk mudah melihat urutan"
                  >
                    <i class="ri-contract-up-down-line"></i>
                    <span class="d-none d-sm-inline">Ciutkan Semua</span>
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-secondary d-inline-flex align-items-center gap-1"
                    @click="expandAll"
                    title="Bentangkan seluruh field untuk diedit"
                  >
                    <i class="ri-expand-up-down-line"></i>
                    <span class="d-none d-sm-inline">Bentangkan Semua</span>
                  </button>
                </div>
              </div>

              <!-- Quick Canvas Field Search -->
              <div v-if="fields.length > 5" class="mt-2.5">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light border-0 text-muted"><i class="ri-search-line"></i></span>
                  <input 
                    type="text" 
                    v-model="canvasSearch"
                    class="form-control form-control-sm border-0 bg-light fs-12"
                    placeholder="Saring pertanyaan pada kanvas..."
                  />
                  <button v-if="canvasSearch" class="btn btn-sm btn-light border-0" @click="canvasSearch = ''">
                    <i class="ri-close-line"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Canvas Card Body: Field List -->
            <div class="card-body p-3 p-sm-4 canvas-scroll-area">
              
              <!-- Empty State -->
              <div v-if="fields.length === 0" class="text-center py-5 border border-dashed rounded-3">
                <div class="avatar-md bg-warning-subtle text-warning rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3">
                  <i class="ri-survey-line fs-24"></i>
                </div>
                <h5 class="fs-15 fw-bold text-dark">Formulir Cabang Ini Belum Memiliki Pertanyaan</h5>
                <p class="text-muted fs-12 max-w-md mx-auto mb-3">
                  Pilih tipe komponen dari toolbox di sebelah kiri, atau terapkan template siap pakai hanya dengan 1-klik.
                </p>
                <div class="d-flex justify-content-center gap-2">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
                    @click="showPresetModal = true"
                  >
                    <i class="ri-magic-line"></i> Terapkan Template Siap Pakai
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1.5"
                    @click="addCorePreset(coreIdentityPresets[0])"
                  >
                    <i class="ri-community-line"></i> Tambah Identitas Sekolah
                  </button>
                </div>
              </div>

              <!-- Dynamic Field Cards (Collapsible & Draggable) -->
              <div v-else class="d-flex flex-column gap-2.5 field-list-container">
                
                <div 
                  v-for="(field, index) in displayedFields" 
                  :key="field.id"
                  class="field-card card border rounded-3 transition-all"
                  :class="{ 
                    'drag-over': dragOverIndex === index,
                    'is-expanded': isExpanded(field.id),
                    'border-warning-subtle': field.type === 'participant_repeater',
                    'border-primary-subtle': field.required && field.type !== 'participant_repeater',
                    'border-light-subtle': !field.required
                  }"
                  @dragover="onDragOver(index, $event)"
                  @dragleave="onDragLeave"
                  @drop="onDrop(index, $event)"
                >
                  <!-- Card Header Strip (Always Visible, Click to Toggle) -->
                  <div 
                    class="field-header py-2.5 px-3 d-flex align-items-center justify-content-between user-select-none cursor-pointer"
                    @click="toggleExpand(field.id)"
                  >
                    <!-- Left: Drag Handle, Number, Type Badge, Field Label -->
                    <div class="d-flex align-items-center gap-2 text-truncate flex-grow-1 me-2">
                      
                      <!-- Dedicated Drag Handle -->
                      <span 
                        class="drag-grip text-muted p-1 rounded hover-drag"
                        draggable="true"
                        @dragstart.stop="onDragStart(index, $event)"
                        @click.stop
                        title="Tahan & seret untuk memindahkan urutan"
                      >
                        <i class="ri-draggable fs-16"></i>
                      </span>

                      <!-- Index Pill -->
                      <span class="badge bg-dark-subtle text-dark font-mono fs-11 px-1.5 py-0.5 rounded">
                        #{{ index + 1 }}
                      </span>

                      <!-- Field Type Pill -->
                      <span 
                        class="badge fs-10 px-2 py-0.5 rounded-pill text-uppercase d-inline-flex align-items-center gap-1"
                        :class="{
                          'bg-warning-subtle text-warning-emphasis border border-warning-subtle': field.type === 'participant_repeater',
                          'bg-primary-subtle text-primary': field.type === 'text',
                          'bg-info-subtle text-info': field.type === 'textarea' || field.type === 'url',
                          'bg-success-subtle text-success': field.type === 'select' || field.type === 'radio' || field.type === 'checkbox',
                          'bg-danger-subtle text-danger': field.type === 'file',
                          'bg-secondary-subtle text-secondary': field.type === 'number' || field.type === 'date'
                        }"
                      >
                        <i :class="field.type === 'participant_repeater' ? 'ri-group-line' : (field.type === 'file' ? 'ri-upload-cloud-2-line' : (field.type === 'url' ? 'ri-link' : (field.type === 'select' ? 'ri-arrow-down-s-line' : 'ri-input-field')))"></i>
                        <span v-if="field.type === 'participant_repeater'">Multi-Peserta Dinamis <template v-if="field.max_items">(Maks. {{ field.max_items }})</template></span>
                        <span v-else>{{ field.type }}</span>
                      </span>

                      <!-- Question Label Title -->
                      <span class="fs-13 fw-bold text-dark text-truncate field-title-text">
                        {{ field.label || '(Pertanyaan Tanpa Judul)' }}
                      </span>

                      <!-- Conditional Logic Active Badge -->
                      <span 
                        v-if="field.conditional_logic?.enabled"
                        class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10 d-inline-flex align-items-center gap-1"
                        title="Pertanyaan ini memiliki aturan logika bersyarat"
                      >
                        <i class="ri-flashlight-line"></i> Bersyarat
                      </span>

                      <!-- Variable Key Tag -->
                      <span class="badge bg-light text-muted font-mono fs-10 d-none d-md-inline" title="Nama variabel / key di database">
                        {{ field.name }}
                      </span>
                    </div>

                    <!-- Right Controls: Required Switch, Reorder Arrows, Duplicate, Delete, Chevron -->
                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0" @click.stop>
                      
                      <!-- Required Toggle Pill -->
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2 py-0.5 fw-semibold transition-all"
                        :class="field.required ? 'btn-danger-subtle text-danger border border-danger-subtle' : 'btn-light text-muted border'"
                        @click="field.required = !field.required"
                        :title="field.required ? 'Pertanyaan ini wajib diisi oleh pendaftar' : 'Pertanyaan ini opsional'"
                      >
                        {{ field.required ? 'Wajib' : 'Opsional' }}
                      </button>

                      <div class="vr mx-0.5 d-none d-sm-block"></div>

                      <!-- Move Up / Down Buttons -->
                      <div class="btn-group btn-group-xs d-none d-sm-inline-flex">
                        <button 
                          type="button" 
                          class="btn btn-xs btn-light border" 
                          :disabled="index === 0"
                          @click="moveFieldUp(index)"
                          title="Geser Naik"
                        >
                          <i class="ri-arrow-up-s-line"></i>
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-xs btn-light border" 
                          :disabled="index === fields.length - 1"
                          @click="moveFieldDown(index)"
                          title="Geser Turun"
                        >
                          <i class="ri-arrow-down-s-line"></i>
                        </button>
                      </div>

                      <!-- Duplicate Button -->
                      <button 
                        type="button" 
                        class="btn btn-xs btn-light border text-secondary" 
                        @click="duplicateField(index)"
                        title="Duplikasi Pertanyaan"
                      >
                        <i class="ri-file-copy-line"></i>
                      </button>

                      <!-- Delete Button -->
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-danger" 
                        @click="removeField(index)"
                        title="Hapus Pertanyaan"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </button>

                      <!-- Expand/Collapse Chevron -->
                      <button 
                        type="button" 
                        class="btn btn-xs btn-ghost-secondary chevron-btn ms-1"
                        @click="toggleExpand(field.id)"
                        :title="isExpanded(field.id) ? 'Ciutkan' : 'Bentangkan'"
                      >
                        <i :class="isExpanded(field.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="fs-15"></i>
                      </button>

                    </div>
                  </div>

                  <!-- Card Body: Expanded Editor Section -->
                  <div v-show="isExpanded(field.id)" class="field-body p-3 border-top bg-light-subtle">
                    <div class="row g-2.5">
                      
                      <!-- Label Pertanyaan Input -->
                      <div class="col-md-7">
                        <label class="form-label fs-11 fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                          <span>Label Teks Pertanyaan <span class="text-danger">*</span></span>
                          <span class="fs-10 text-muted font-mono">key: {{ field.name }}</span>
                        </label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text bg-light text-muted"><i class="ri-edit-line"></i></span>
                          <input 
                            type="text" 
                            v-model="field.label"
                            class="form-control fs-12 fw-semibold"
                            placeholder="Contoh: Biodata Siswa Delegasi / Nama Sekolah / NISN..."
                            required
                          />
                        </div>
                      </div>

                      <!-- Tipe Komponen Selector -->
                      <div class="col-md-5">
                        <label class="form-label fs-11 fw-bold text-dark mb-1">
                          Tipe Komponen Input
                        </label>
                        <select v-model="field.type" class="form-select form-select-sm fs-12">
                          <option v-for="ft in fieldTypes" :key="ft.type" :value="ft.type">
                            {{ ft.label }} ({{ ft.type }})
                          </option>
                        </select>
                      </div>

                      <!-- Placeholder Input (Except for repeater, file, radio, checkbox) -->
                      <div v-if="!['participant_repeater', 'file', 'radio', 'checkbox'].includes(field.type)" class="col-md-6">
                        <label class="form-label fs-11 text-muted mb-1">
                          Placeholder (Contoh Teks di dalam input)
                        </label>
                        <input 
                          type="text" 
                          v-model="field.placeholder"
                          class="form-control form-control-sm fs-12"
                          placeholder="Contoh: Masukkan nama lengkap..."
                        />
                      </div>

                      <!-- Petunjuk Bantuan (Help Text) -->
                      <div :class="!['participant_repeater', 'file', 'radio', 'checkbox'].includes(field.type) ? 'col-md-6' : 'col-12'">
                        <label class="form-label fs-11 text-muted mb-1">
                          Petunjuk Bantuan Tambahan (Help Text)
                        </label>
                        <input 
                          type="text" 
                          v-model="field.help_text"
                          class="form-control form-control-sm fs-12"
                          placeholder="Contoh: Tentukan jumlah peserta yang didaftarkan..."
                        />
                      </div>

                      <!-- ============================================================ -->
                      <!-- PARTICIPANT REPEATER SPECIAL CONFIGURATION (LOGIKA DINAMIS)  -->
                      <!-- ============================================================ -->
                      <div v-if="field.type === 'participant_repeater'" class="col-12">
                        <div class="p-3 bg-warning-subtle/20 rounded-3 border border-warning-subtle">
                          <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fs-12 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                              <i class="ri-group-line text-warning fs-16"></i>
                              <span>Konfigurasi Kuota & Biodata Multi-Peserta (Tim / Turnamen / Beregu)</span>
                            </label>
                            <span class="badge bg-warning text-dark fs-10 font-mono">Otomatisasi Form</span>
                          </div>

                          <p class="text-muted fs-11 mb-3">
                            Atur batas kuota peserta per tim/kontingen untuk cabang lomba ini (misal regu 3 siswa, turnamen 10-12 pemain, grup musik/banjari, atau bebas). Form biodata yang tampil di pendaftar secara otomatis menyesuaikan dengan jumlah siswa yang didaftarkan.
                          </p>

                          <div class="row g-3">
                            <!-- Min & Max Items -->
                            <div class="col-md-6">
                              <div class="row g-2 mb-2">
                                <div class="col-6">
                                  <label class="form-label fs-11 fw-bold text-dark mb-1">
                                    Kuota Minimal:
                                  </label>
                                  <div class="input-group input-group-sm">
                                    <input 
                                      type="number" 
                                      v-model.number="field.min_items"
                                      class="form-control fs-12 font-mono fw-bold"
                                      min="1"
                                      placeholder="1"
                                    />
                                    <span class="input-group-text bg-light text-muted fs-11">Siswa</span>
                                  </div>
                                </div>
                                <div class="col-6">
                                  <label class="form-label fs-11 fw-bold text-dark mb-1">
                                    Kuota Maksimal:
                                  </label>
                                  <div class="input-group input-group-sm">
                                    <input 
                                      type="number" 
                                      v-model.number="field.max_items"
                                      class="form-control fs-12 font-mono fw-bold"
                                      min="1"
                                      placeholder="Bebas"
                                    />
                                    <span class="input-group-text bg-light text-muted fs-11">Siswa</span>
                                  </div>
                                </div>
                              </div>
                              <!-- Quick preset chips -->
                              <div class="d-flex gap-1.5 mt-1.5 flex-wrap">
                                <span class="text-muted fs-10 w-100 mb-0.5">Preset Batas Kuota Cepat:</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = 2">2 (Duo/Riset)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 3; field.max_items = 3">3 (Regu LCC)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = 5">5 (Futsal/E-Sport)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = 10">10 (Musik/Hadrah)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 5; field.max_items = 12">12 (Line-up Turnamen)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = null">Bebas (Tanpa Batas)</button>
                              </div>
                            </div>

                            <!-- Sub-fields toggles -->
                            <div class="col-md-6">
                              <label class="form-label fs-11 fw-bold text-dark mb-1">
                                Kolom Biodata Siswa yang Diaktifkan:
                              </label>
                              <div class="d-flex flex-column gap-1.5 p-2 bg-body rounded border">
                                <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                  <input type="checkbox" class="form-check-input" checked disabled />
                                  <span class="text-dark fw-semibold">Nama Lengkap Siswa</span>
                                  <span class="badge bg-danger-subtle text-danger fs-9 ms-auto">Wajib</span>
                                </label>
                                <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                  <input type="checkbox" class="form-check-input" v-model="field.sub_fields.gender.active" />
                                  <span class="text-dark">Jenis Kelamin (Laki-laki / Perempuan)</span>
                                </label>
                                <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                  <input type="checkbox" class="form-check-input" v-model="field.sub_fields.birth_date.active" />
                                  <span class="text-dark">Tanggal Lahir Siswa</span>
                                </label>
                                <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                  <input type="checkbox" class="form-check-input" v-model="field.sub_fields.grade.active" />
                                  <span class="text-dark">Kelas / Tingkat (Kelas 7, 8, 9)</span>
                                </label>
                                <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                  <input type="checkbox" class="form-check-input" v-model="field.sub_fields.nisn.active" />
                                  <span class="text-dark">Nomor Induk Siswa Nasional (NISN)</span>
                                </label>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Options Editor for Select, Radio, Checkbox -->
                      <div v-if="['select', 'radio', 'checkbox'].includes(field.type)" class="col-12">
                        <div class="p-3 bg-body rounded-3 border">
                          <div class="d-flex align-items-center justify-content-between mb-2.5 flex-wrap gap-2">
                            <label class="form-label fs-11 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                              <i class="ri-list-check-2 text-success fs-14"></i>
                              <span>Daftar Pilihan Opsi ({{ field.options?.length || 0 }})</span>
                            </label>
                            
                            <div class="d-flex align-items-center gap-1.5">
                              <button 
                                type="button" 
                                class="btn btn-xs btn-outline-secondary"
                                @click="openBulkOptions(field)"
                                title="Tempel banyak opsi sekaligus dari Excel atau catatan"
                              >
                                <i class="ri-file-text-line me-1"></i> Tempel Sekaligus
                              </button>
                              
                              <div class="dropdown">
                                <button class="btn btn-xs btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                  Preset Opsi
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end fs-11 shadow border-0">
                                  <li><a class="dropdown-item cursor-pointer" @click="applyOptionPreset(field, 'yesno')">Ya / Tidak</a></li>
                                  <li><a class="dropdown-item cursor-pointer" @click="applyOptionPreset(field, 'genders')">Laki-laki / Perempuan</a></li>
                                  <li><a class="dropdown-item cursor-pointer" @click="applyOptionPreset(field, 'classes')">Kelas 7 / 8 / 9 SMP</a></li>
                                </ul>
                              </div>

                              <button 
                                type="button" 
                                class="btn btn-xs btn-success d-inline-flex align-items-center gap-1"
                                @click="addOption(field)"
                              >
                                <i class="ri-add-line"></i> Tambah Opsi
                              </button>
                            </div>
                          </div>

                          <!-- Options Rows -->
                          <div class="d-flex flex-column gap-1.5">
                            <div 
                              v-for="(opt, oIdx) in field.options" 
                              :key="oIdx"
                              class="input-group input-group-sm"
                            >
                              <span class="input-group-text bg-light-subtle text-muted fs-11 font-mono px-2">
                                {{ oIdx + 1 }}
                              </span>
                              <input 
                                type="text" 
                                v-model="field.options[oIdx]"
                                class="form-control fs-12"
                                placeholder="Tulis opsi pilihan..."
                                required
                              />
                              <button 
                                type="button" 
                                class="btn btn-outline-danger"
                                @click="removeOption(field, oIdx)"
                                title="Hapus Opsi"
                                :disabled="field.options.length <= 1"
                              >
                                <i class="ri-close-line"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- File Upload Settings Zone -->
                      <div v-if="field.type === 'file'" class="col-12">
                        <div class="p-3 bg-danger-subtle/20 rounded-3 border border-danger-subtle">
                          <div class="row g-2.5">
                            <div class="col-md-7">
                              <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <label class="form-label fs-11 fw-bold text-dark mb-0">
                                  <i class="ri-file-shield-2-line text-danger me-1"></i> Format Berkas Diizinkan:
                                </label>
                                <div class="d-flex gap-1">
                                  <button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1 text-[10px]" @click="setFilePreset(field, 'pdf')">PDF Saja</button>
                                  <button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1 text-[10px]" @click="setFilePreset(field, 'images')">Foto/JPG</button>
                                  <button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1 text-[10px]" @click="setFilePreset(field, 'audio')">Audio</button>
                                  <button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1 text-[10px]" @click="setFilePreset(field, 'all')">Semua</button>
                                </div>
                              </div>

                              <div class="d-flex flex-wrap gap-1.5">
                                <button 
                                  v-for="ext in ['pdf', 'jpg', 'jpeg', 'png', 'mp3', 'wav', 'docx', 'zip']" 
                                  :key="ext"
                                  type="button"
                                  class="btn btn-xs rounded-pill px-2 py-0.5 font-mono"
                                  :class="field.allowed_extensions?.includes(ext) ? 'btn-danger text-white' : 'btn-outline-secondary bg-body'"
                                  @click="toggleExtension(field, ext)"
                                >
                                  .{{ ext }}
                                </button>
                              </div>
                            </div>

                            <div class="col-md-5">
                              <label class="form-label fs-11 fw-bold text-dark mb-1">
                                Ukuran Maksimal Berkas
                              </label>
                              <div class="input-group input-group-sm">
                                <input 
                                  type="number" 
                                  v-model.number="field.max_size_mb"
                                  class="form-control fs-12 font-mono"
                                  min="1"
                                  max="50"
                                />
                                <span class="input-group-text bg-light-subtle text-muted fs-11">MB</span>
                              </div>
                              <div class="d-flex gap-1 mt-1">
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.max_size_mb = 2">2MB</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.max_size_mb = 5">5MB</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.max_size_mb = 10">10MB</button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- ============================================================ -->
                      <!-- GENERAL CONDITIONAL LOGIC ACCORDION FOR EVERY FIELD          -->
                      <!-- ============================================================ -->
                      <div class="col-12 mt-2 pt-2 border-top">
                        <div class="p-2.5 rounded-3 border bg-body">
                          <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                              <i class="ri-flashlight-line text-warning fs-15"></i>
                              <div>
                                <span class="fs-12 fw-bold text-dark">Logika Bersyarat (Conditional Logic)</span>
                                <span class="text-muted fs-10 d-block">Tampilkan pertanyaan ini hanya jika syarat terpenuhi</span>
                              </div>
                            </div>
                            <div class="form-check form-switch mb-0">
                              <input 
                                class="form-check-input cursor-pointer" 
                                type="checkbox" 
                                role="switch"
                                :id="`logic-switch-${field.id}`"
                                v-model="field.conditional_logic.enabled"
                              />
                            </div>
                          </div>

                          <!-- Conditional Logic Configuration Panel -->
                          <div v-if="field.conditional_logic?.enabled" class="mt-2.5 pt-2.5 border-top">
                            <div class="row g-2 align-items-center">
                              <div class="col-sm-4">
                                <label class="form-label fs-10 text-muted mb-1">Tampilkan Jika Pertanyaan:</label>
                                <select v-model="field.conditional_logic.depend_on_field" class="form-select form-select-sm fs-11">
                                  <option value="" disabled selected>-- Pilih Pertanyaan Target --</option>
                                  <option 
                                    v-for="other in fields.filter(f => f.id !== field.id)" 
                                    :key="other.id" 
                                    :value="other.name"
                                  >
                                    {{ other.label }} ({{ other.name }})
                                  </option>
                                </select>
                              </div>

                              <div class="col-sm-3">
                                <label class="form-label fs-10 text-muted mb-1">Operator Kondisi:</label>
                                <select v-model="field.conditional_logic.operator" class="form-select form-select-sm fs-11">
                                  <option value="==">Sama dengan (==)</option>
                                  <option value="!=">Tidak sama dengan (!=)</option>
                                  <option value=">=">Lebih besar / sama (>=)</option>
                                  <option value="<=">Lebih kecil / sama (<=)</option>
                                  <option value=">">Lebih besar dari (>)</option>
                                  <option value="<">Lebih kecil dari (<)</option>
                                  <option value="contains">Mengandung kata</option>
                                </select>
                              </div>

                              <div class="col-sm-5">
                                <label class="form-label fs-10 text-muted mb-1">Nilai Pembanding / Angka:</label>
                                <input 
                                  type="text" 
                                  v-model="field.conditional_logic.value"
                                  class="form-control form-control-sm fs-11 font-mono"
                                  placeholder="Contoh: 3 / Ya / Paket Satuan"
                                />
                              </div>
                            </div>

                            <div v-if="field.conditional_logic.depend_on_field" class="mt-2 p-1.5 rounded bg-warning-subtle/30 text-warning-emphasis fs-10 d-flex align-items-center gap-1.5">
                              <i class="ri-information-line fs-13"></i>
                              <span>
                                Pertanyaan ini <strong>HANYA akan muncul</strong> jika <code>{{ field.conditional_logic.depend_on_field }}</code> bernilai <strong>{{ field.conditional_logic.operator }} '{{ field.conditional_logic.value }}'</strong>.
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>

                </div>

              </div>

            </div>

            <!-- Canvas Footer Summary & Quick Add -->
            <div class="card-footer bg-transparent border-top py-2.5 px-3 px-sm-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="text-muted fs-11">
                Total <strong>{{ fields.length }} pertanyaan</strong> dikonfigurasi • 
                <span class="text-danger fw-semibold">{{ fields.filter(f => f.required).length }} Wajib</span>, 
                <span class="text-secondary">{{ fields.filter(f => !f.required).length }} Opsional</span> •
                <span class="text-warning-emphasis fw-semibold">{{ fields.filter(f => f.conditional_logic?.enabled).length }} Logika Bersyarat</span>
              </div>
              <button 
                type="button" 
                class="btn btn-xs btn-success d-inline-flex align-items-center gap-1 fw-bold"
                :disabled="isSaving"
                @click="saveFormFields"
              >
                <i class="ri-check-line"></i> Simpan Perubahan
              </button>
            </div>

          </div>
        </div>

        <!-- RIGHT COLUMN (ONLY IN SPLIT VIEW): LIVE INTERACTIVE DEVICE PREVIEW WITH LOGIC -->
        <div v-if="viewMode === 'split'" class="col-xl-4 col-lg-12">
          <div class="card border-0 shadow-sm sticky-preview-card">
            
            <!-- Live Preview Header -->
            <div class="card-header bg-slate-900 text-white py-2.5 px-3 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-emerald-500 text-slate-950 fw-bold fs-10">LIVE PREVIEW</span>
                <span class="fs-12 fw-semibold text-slate-300">Formulir Publik & Logika</span>
              </div>

              <!-- Device Switcher -->
              <div class="btn-group btn-group-xs" role="group">
                <button 
                  type="button" 
                  class="btn btn-xs"
                  :class="previewDevice === 'desktop' ? 'btn-light' : 'btn-outline-light'"
                  @click="previewDevice = 'desktop'"
                  title="Simulasi Desktop"
                >
                  <i class="ri-macbook-line"></i>
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs"
                  :class="previewDevice === 'mobile' ? 'btn-light' : 'btn-outline-light'"
                  @click="previewDevice = 'mobile'"
                  title="Simulasi Mobile"
                >
                  <i class="ri-smartphone-line"></i>
                </button>
              </div>
            </div>

            <!-- Live Preview Interactive Area -->
            <div class="card-body p-3 bg-slate-950 text-white preview-scroll-area d-flex justify-content-center">
              <div 
                class="transition-all w-100" 
                :style="{ maxWidth: previewDevice === 'mobile' ? '360px' : '100%' }"
              >
                <!-- Simulated Form Card -->
                <div class="p-3 sm:p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl text-slate-200">
                  
                  <!-- Form Title & Header -->
                  <div class="mb-4 pb-3 border-b border-slate-800">
                    <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest">
                      {{ formSettings.form_title || 'FORMULIR PENDAFTARAN' }}
                    </span>
                    <h5 class="text-base font-black text-white mt-0.5 mb-1">
                      {{ selectedCompetition?.title }}
                    </h5>
                    <p class="text-[11px] text-slate-400 mb-0">
                      {{ formSettings.form_subtitle || `Biaya: Rp ${Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID')}` }}
                    </p>
                  </div>

                  <!-- Optional Package Type -->
                  <div v-if="formSettings.show_package_type" class="mb-4 pb-4 border-b border-slate-800">
                    <label class="block text-[11px] font-bold text-slate-300 mb-1.5">Pilihan Paket:</label>
                    <div class="grid grid-cols-2 gap-2">
                      <div class="p-2 rounded-lg border border-amber-500 bg-amber-500/10 text-white text-[11px]">
                        <div class="font-bold">Satuan</div>
                        <div class="text-emerald-400 font-mono text-[10px]">Rp {{ Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID') }}</div>
                      </div>
                      <div class="p-2 rounded-lg border border-slate-800 bg-slate-950 text-slate-400 text-[11px]">
                        <div class="font-bold">Paket All-In</div>
                        <div class="text-amber-400 font-mono text-[10px]">Rp 200.000</div>
                      </div>
                    </div>
                  </div>

                  <!-- Dynamic Fields Preview with Real-Time Logic Evaluation -->
                  <div class="space-y-3.5 text-xs">
                    <div v-if="fields.length === 0" class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-center text-slate-500 text-[11px]">
                      Belum ada field pertanyaan.
                    </div>

                    <template v-for="field in fields" :key="field.id">
                      <!-- Evaluate Conditional Logic: If condition not met, hide! -->
                      <div 
                        v-if="evaluateCondition(field.conditional_logic, previewValues)"
                        class="space-y-1.5 transition-all"
                      >
                        <!-- Label -->
                        <label class="block text-slate-200 font-bold text-[11px] d-flex align-items-center justify-content-between">
                          <span>
                            {{ field.label }}
                            <span v-if="field.required" class="text-rose-400 ml-0.5">*</span>
                            <span v-else class="text-slate-500 font-normal text-[10px] ml-1">(Opsional)</span>
                          </span>
                          <span v-if="field.conditional_logic?.enabled" class="badge bg-warning-subtle text-amber-300 text-[9px] px-1 py-0">
                            ⚡ Aktif Bersyarat
                          </span>
                        </label>

                        <!-- SPECIAL COMPONENT: PARTICIPANT REPEATER (MULTI-PESERTA DINAMIS) -->
                        <div v-if="field.type === 'participant_repeater'" class="space-y-2.5 pt-1">
                          
                          <!-- Stepper Control: Jumlah Siswa yang Didaftarkan -->
                          <div class="p-2.5 rounded-xl bg-slate-950 border border-amber-500/30 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                              <div class="text-slate-200 font-bold text-[11px] d-flex align-items-center gap-1.5">
                                <i class="ri-user-follow-line text-amber-400"></i>
                                <span>Jumlah Peserta Didaftarkan:</span>
                              </div>
                              <div class="text-[10px] text-slate-400 mt-0.5">
                                <span v-if="field.max_items">Batas maksimal kuota: <strong>{{ field.max_items }} Siswa/Tim</strong></span>
                                <span v-else>Kuota peserta: <strong>Bebas/Fleksibel</strong></span>
                                <span v-if="field.min_items && field.min_items > 1"> (Min. {{ field.min_items }})</span>
                              </div>
                            </div>

                            <div class="d-flex align-items-center gap-1.5">
                              <button 
                                type="button" 
                                class="btn btn-xs btn-outline-secondary text-slate-300 px-2 py-1"
                                :disabled="(previewValues[field.name + '_count'] || 1) <= (field.min_items || 1)"
                                @click="changeParticipantCount(field, -1)"
                              >
                                <i class="ri-subtract-line"></i>
                              </button>
                              <span class="badge bg-slate-900 border border-slate-700 font-mono text-amber-400 font-bold fs-12 px-2.5 py-1">
                                {{ previewValues[field.name + '_count'] || field.min_items || 1 }} Siswa
                              </span>
                              <button 
                                type="button" 
                                class="btn btn-xs btn-outline-secondary text-slate-300 px-2 py-1"
                                :disabled="field.max_items && (previewValues[field.name + '_count'] || 1) >= Number(field.max_items)"
                                @click="changeParticipantCount(field, 1)"
                              >
                                <i class="ri-add-line"></i>
                              </button>
                            </div>
                          </div>

                          <!-- Dynamic Student Biodata Forms (1 s.d. N Sesuai Pilihan) -->
                          <div class="space-y-2">
                            <div 
                              v-for="pIdx in (previewValues[field.name + '_count'] || 3)" 
                              :key="pIdx"
                              class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 transition-all"
                            >
                              <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-b border-slate-800/80">
                                <span class="text-[11px] font-bold text-amber-300 d-flex align-items-center gap-1">
                                  <i class="ri-user-line text-emerald-400"></i>
                                  <span>Biodata Siswa #{{ pIdx }}</span>
                                </span>
                                <span class="badge bg-slate-900 text-slate-400 text-[9px] font-mono">
                                  Peserta {{ pIdx }} dari {{ previewValues[field.name + '_count'] || 3 }}
                                </span>
                              </div>

                              <div class="space-y-2 text-xs">
                                <!-- Nama Siswa -->
                                <div v-if="field.sub_fields?.name?.active !== false">
                                  <label class="block text-[10px] text-slate-400 mb-0.5">Nama Lengkap Siswa *</label>
                                  <input 
                                    type="text" 
                                    :placeholder="`Nama Siswa Peserta ${pIdx}...`"
                                    class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                                  />
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                  <!-- Jenis Kelamin -->
                                  <div v-if="field.sub_fields?.gender?.active !== false">
                                    <label class="block text-[10px] text-slate-400 mb-0.5">Jenis Kelamin</label>
                                    <div class="flex gap-1.5">
                                      <label class="flex items-center gap-1 p-1.5 rounded bg-slate-900 border border-slate-800 cursor-pointer text-[10px] flex-1">
                                        <input type="radio" :name="`p_gender_${field.name}_${pIdx}`" value="L" checked class="text-amber-500" />
                                        <span class="text-slate-300">L</span>
                                      </label>
                                      <label class="flex items-center gap-1 p-1.5 rounded bg-slate-900 border border-slate-800 cursor-pointer text-[10px] flex-1">
                                        <input type="radio" :name="`p_gender_${field.name}_${pIdx}`" value="P" class="text-amber-500" />
                                        <span class="text-slate-300">P</span>
                                      </label>
                                    </div>
                                  </div>

                                  <!-- Kelas -->
                                  <div v-if="field.sub_fields?.grade?.active !== false">
                                    <label class="block text-[10px] text-slate-400 mb-0.5">Kelas / Tingkat</label>
                                    <select class="w-full px-2 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-[11px]">
                                      <option value="Kelas 7">Kelas 7</option>
                                      <option value="Kelas 8">Kelas 8</option>
                                      <option value="Kelas 9" selected>Kelas 9</option>
                                    </select>
                                  </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                  <!-- Tanggal Lahir -->
                                  <div v-if="field.sub_fields?.birth_date?.active !== false">
                                    <label class="block text-[10px] text-slate-400 mb-0.5">Tgl Lahir</label>
                                    <input 
                                      type="date" 
                                      class="w-full px-2 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white text-[11px]"
                                    />
                                  </div>

                                  <!-- NISN -->
                                  <div v-if="field.sub_fields?.nisn?.active !== false">
                                    <label class="block text-[10px] text-slate-400 mb-0.5">NISN</label>
                                    <input 
                                      type="text" 
                                      placeholder="008xxxxxxx"
                                      class="w-full px-2 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white text-[11px]"
                                    />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                        </div>

                        <!-- STANDARD INPUT TYPES -->
                        <div v-else-if="['text', 'number', 'url'].includes(field.type)">
                          <input 
                            :type="field.type" 
                            v-model="previewValues[field.name]"
                            :placeholder="field.placeholder || `Masukkan ${field.label}...`"
                            class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                          />
                        </div>

                        <div v-else-if="field.type === 'textarea'">
                          <textarea 
                            rows="2"
                            v-model="previewValues[field.name]"
                            :placeholder="field.placeholder || `Tuliskan ${field.label}...`"
                            class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                          ></textarea>
                        </div>

                        <div v-else-if="field.type === 'select'">
                          <select 
                            v-model="previewValues[field.name]"
                            class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                          >
                            <option value="" disabled selected>-- Pilih {{ field.label }} --</option>
                            <option v-for="(opt, optIdx) in field.options" :key="optIdx" :value="opt">
                              {{ opt }}
                            </option>
                          </select>
                        </div>

                        <div v-else-if="field.type === 'radio'" class="space-y-1.5 pt-0.5">
                          <label 
                            v-for="(opt, optIdx) in field.options" 
                            :key="optIdx"
                            class="flex items-center gap-2 p-2 rounded-lg border border-slate-800 bg-slate-950 cursor-pointer hover:border-slate-700 text-[11px]"
                          >
                            <input 
                              type="radio" 
                              :name="`preview_split_${field.name}`" 
                              :value="opt" 
                              v-model="previewValues[field.name]"
                              class="text-amber-500 focus:ring-0"
                            />
                            <span class="text-slate-300">{{ opt }}</span>
                          </label>
                        </div>

                        <div v-else-if="field.type === 'checkbox'" class="space-y-1.5 pt-0.5">
                          <label 
                            v-for="(opt, optIdx) in field.options" 
                            :key="optIdx"
                            class="flex items-center gap-2 p-2 rounded-lg border border-slate-800 bg-slate-950 cursor-pointer hover:border-slate-700 text-[11px]"
                          >
                            <input 
                              type="checkbox" 
                              :value="opt" 
                              class="text-amber-500 rounded focus:ring-0"
                            />
                            <span class="text-slate-300">{{ opt }}</span>
                          </label>
                        </div>

                        <div v-else-if="field.type === 'file'" class="pt-0.5">
                          <div class="border border-dashed border-slate-800 hover:border-amber-500/50 rounded-xl p-3 text-center bg-slate-950/70 transition-all cursor-pointer">
                            <i class="ri-upload-cloud-2-line text-lg text-amber-400 mb-0.5 d-block"></i>
                            <div class="text-slate-300 font-semibold text-[11px]">Unggah Berkas</div>
                            <div class="text-[10px] text-slate-500 mt-0.5">
                              {{ field.allowed_extensions?.join(', ') || 'Semua format' }} • Max {{ field.max_size_mb || 5 }} MB
                            </div>
                          </div>
                        </div>

                        <div v-else-if="field.type === 'date'">
                          <input 
                            type="date" 
                            v-model="previewValues[field.name]"
                            class="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                          />
                        </div>

                        <!-- Help Text -->
                        <div v-if="field.help_text" class="text-[10px] text-slate-500">
                          <i class="ri-information-line me-0.5"></i> {{ field.help_text }}
                        </div>
                      </div>
                    </template>

                    <!-- Submit Button Simulation -->
                    <div class="pt-2 mt-4 border-t border-slate-800">
                      <button 
                        type="button" 
                        class="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black text-[11px] uppercase tracking-wider shadow-md"
                        @click="toast.success('Pengujian form dan logika berhasil!')"
                      >
                        {{ formSettings.submit_text || 'Kirim Pendaftaran' }}
                      </button>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

      <!-- MODE 2: FULL PREVIEW VIEW -->
      <div v-show="viewMode === 'preview'" class="card border-0 shadow-sm full-preview-card">
        <div class="card-header bg-slate-900 text-white py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-emerald-500 text-slate-950 fw-bold fs-11">SIMULASI FORMULIR LENGKAP & LOGIKA</span>
            <h5 class="fs-15 fw-bold text-white mb-0">{{ selectedCompetition?.title }}</h5>
          </div>

          <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
              <button 
                type="button" 
                class="btn"
                :class="previewDevice === 'desktop' ? 'btn-light' : 'btn-outline-light'"
                @click="previewDevice = 'desktop'"
              >
                <i class="ri-macbook-line me-1"></i> Desktop
              </button>
              <button 
                type="button" 
                class="btn"
                :class="previewDevice === 'mobile' ? 'btn-light' : 'btn-outline-light'"
                @click="previewDevice = 'mobile'"
              >
                <i class="ri-smartphone-line me-1"></i> Mobile
              </button>
            </div>

            <button 
              type="button" 
              class="btn btn-sm btn-outline-light"
              @click="viewMode = 'split'"
            >
              <i class="ri-arrow-left-line me-1"></i> Kembali ke Editor
            </button>
          </div>
        </div>

        <div class="card-body p-4 bg-slate-950 text-white min-h-[500px] d-flex justify-content-center">
          <div 
            class="transition-all w-full"
            :style="{ maxWidth: previewDevice === 'mobile' ? '420px' : '760px' }"
          >
            <div class="p-4 sm:p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl text-slate-200">
              
              <!-- Header -->
              <div class="mb-6 pb-4 border-b border-slate-800">
                <span class="text-[11px] font-bold text-amber-400 uppercase tracking-widest">
                  {{ formSettings.form_title || 'FORMULIR PENDAFTARAN DELEGASI' }}
                </span>
                <h2 class="text-xl sm:text-2xl font-black text-white mt-1">
                  {{ selectedCompetition?.title }}
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                  {{ formSettings.form_subtitle || `Kategori: ${selectedCompetition?.category_label || selectedCompetition?.category} • Biaya: Rp ${Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID')}` }}
                </p>
              </div>

              <!-- Package Type Simulation -->
              <div v-if="formSettings.show_package_type" class="mb-6 pb-6 border-b border-slate-800">
                <label class="block text-xs font-bold text-slate-300 mb-2">Pilihan Paket:</label>
                <div class="grid grid-cols-2 gap-3">
                  <div class="p-3 rounded-xl border border-amber-500 bg-amber-500/10 text-white text-xs">
                    <div class="font-bold">Paket Satuan</div>
                    <div class="text-emerald-400 font-mono mt-0.5">Rp {{ Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID') }}</div>
                  </div>
                  <div class="p-3 rounded-xl border border-slate-800 bg-slate-950 text-slate-400 text-xs">
                    <div class="font-bold">Paket All-In</div>
                    <div class="text-amber-400 font-mono mt-0.5">Rp 200.000</div>
                  </div>
                </div>
              </div>

              <!-- Fields List with Logic Evaluation in Full Preview -->
              <div class="space-y-4 text-xs">
                <div v-if="fields.length === 0" class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-center text-slate-500">
                  Belum ada pertanyaan pada formulir ini.
                </div>

                <template v-for="field in fields" :key="field.id">
                  <div 
                    v-if="evaluateCondition(field.conditional_logic, previewValues)"
                    class="space-y-1.5 transition-all"
                  >
                    <label class="block text-slate-200 font-bold d-flex align-items-center justify-content-between">
                      <span>
                        {{ field.label }}
                        <span v-if="field.required" class="text-rose-400 ml-0.5">*</span>
                        <span v-else class="text-slate-500 font-normal text-[11px] ml-1">(Opsional)</span>
                      </span>
                      <span v-if="field.conditional_logic?.enabled" class="badge bg-warning-subtle text-amber-300 text-[10px]">
                        ⚡ Bersyarat
                      </span>
                    </label>

                    <!-- REPEATER IN FULL PREVIEW -->
                    <div v-if="field.type === 'participant_repeater'" class="space-y-3 pt-1">
                      <div class="p-3 rounded-2xl bg-slate-950 border border-amber-500/30 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                          <div class="text-slate-200 font-bold text-xs d-flex align-items-center gap-1.5">
                            <i class="ri-user-follow-line text-amber-400"></i>
                            <span>Jumlah Peserta Didaftarkan:</span>
                          </div>
                          <div class="text-[11px] text-slate-400 mt-0.5">
                            <span v-if="field.max_items">Batas maksimal: <strong>{{ field.max_items }} Peserta/Siswa</strong></span>
                            <span v-else>Kuota peserta: <strong>Bebas/Fleksibel</strong></span>
                            <span v-if="field.min_items && field.min_items > 1"> (Min. {{ field.min_items }})</span>
                          </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary text-slate-300"
                            :disabled="(previewValues[field.name + '_count'] || 1) <= (field.min_items || 1)"
                            @click="changeParticipantCount(field, -1)"
                          >
                            <i class="ri-subtract-line"></i>
                          </button>
                          <span class="badge bg-slate-900 border border-slate-700 font-mono text-amber-400 font-bold fs-14 px-3 py-1.5">
                            {{ previewValues[field.name + '_count'] || field.min_items || 1 }} Peserta
                          </span>
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary text-slate-300"
                            :disabled="field.max_items && (previewValues[field.name + '_count'] || 1) >= Number(field.max_items)"
                            @click="changeParticipantCount(field, 1)"
                          >
                            <i class="ri-add-line"></i>
                          </button>
                        </div>
                      </div>

                      <div class="space-y-3">
                        <div 
                          v-for="pIdx in (previewValues[field.name + '_count'] || 3)" 
                          :key="pIdx"
                          class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 transition-all"
                        >
                          <div class="d-flex align-items-center justify-content-between mb-2.5 pb-1.5 border-b border-slate-800">
                            <span class="text-xs font-bold text-amber-300 d-flex align-items-center gap-1.5">
                              <i class="ri-user-line text-emerald-400"></i>
                              <span>Biodata Peserta Siswa #{{ pIdx }}</span>
                            </span>
                            <span class="badge bg-slate-900 text-slate-400 text-[10px] font-mono">
                              Peserta {{ pIdx }} dari {{ previewValues[field.name + '_count'] || 3 }}
                            </span>
                          </div>

                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                            <div v-if="field.sub_fields?.name?.active !== false" class="sm:col-span-2">
                              <label class="block text-[11px] text-slate-400 mb-1">Nama Lengkap Siswa *</label>
                              <input 
                                type="text" 
                                :placeholder="`Nama Lengkap Peserta ${pIdx}...`"
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                              />
                            </div>

                            <div v-if="field.sub_fields?.gender?.active !== false">
                              <label class="block text-[11px] text-slate-400 mb-1">Jenis Kelamin *</label>
                              <div class="flex gap-2">
                                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-900 cursor-pointer text-xs flex-1">
                                  <input type="radio" :name="`full_p_gender_${field.name}_${pIdx}`" value="L" checked class="text-amber-500" />
                                  <span class="text-slate-300">Laki-laki</span>
                                </label>
                                <label class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-800 bg-slate-900 cursor-pointer text-xs flex-1">
                                  <input type="radio" :name="`full_p_gender_${field.name}_${pIdx}`" value="P" class="text-amber-500" />
                                  <span class="text-slate-300">Perempuan</span>
                                </label>
                              </div>
                            </div>

                            <div v-if="field.sub_fields?.grade?.active !== false">
                              <label class="block text-[11px] text-slate-400 mb-1">Kelas / Tingkat *</label>
                              <select class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs">
                                <option value="Kelas 7 SMP/MTs">Kelas 7 SMP/MTs</option>
                                <option value="Kelas 8 SMP/MTs">Kelas 8 SMP/MTs</option>
                                <option value="Kelas 9 SMP/MTs" selected>Kelas 9 SMP/MTs</option>
                              </select>
                            </div>

                            <div v-if="field.sub_fields?.birth_date?.active !== false">
                              <label class="block text-[11px] text-slate-400 mb-1">Tanggal Lahir</label>
                              <input 
                                type="date" 
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                              />
                            </div>

                            <div v-if="field.sub_fields?.nisn?.active !== false">
                              <label class="block text-[11px] text-slate-400 mb-1">NISN</label>
                              <input 
                                type="text" 
                                placeholder="0081234567"
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- STANDARD FIELD TYPES IN FULL PREVIEW -->
                    <div v-else-if="['text', 'number', 'url'].includes(field.type)">
                      <input 
                        :type="field.type" 
                        v-model="previewValues[field.name]"
                        :placeholder="field.placeholder || `Masukkan ${field.label}...`"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                      />
                    </div>

                    <div v-else-if="field.type === 'textarea'">
                      <textarea 
                        rows="3"
                        v-model="previewValues[field.name]"
                        :placeholder="field.placeholder || `Tuliskan ${field.label}...`"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                      ></textarea>
                    </div>

                    <div v-else-if="field.type === 'select'">
                      <select 
                        v-model="previewValues[field.name]"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                      >
                        <option value="" disabled selected>-- Pilih Opsi {{ field.label }} --</option>
                        <option v-for="(opt, optIdx) in field.options" :key="optIdx" :value="opt">
                          {{ opt }}
                        </option>
                      </select>
                    </div>

                    <div v-else-if="field.type === 'radio'" class="space-y-2 pt-1">
                      <label 
                        v-for="(opt, optIdx) in field.options" 
                        :key="optIdx"
                        class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-800 bg-slate-950 cursor-pointer hover:border-slate-700 transition-all text-xs"
                      >
                        <input 
                          type="radio" 
                          :name="`preview_full_${field.name}`" 
                          :value="opt" 
                          v-model="previewValues[field.name]"
                          class="text-amber-500 focus:ring-0"
                        />
                        <span class="text-slate-300 font-medium">{{ opt }}</span>
                      </label>
                    </div>

                    <div v-else-if="field.type === 'checkbox'" class="space-y-2 pt-1">
                      <label 
                        v-for="(opt, optIdx) in field.options" 
                        :key="optIdx"
                        class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-800 bg-slate-950 cursor-pointer hover:border-slate-700 transition-all text-xs"
                      >
                        <input 
                          type="checkbox" 
                          :value="opt" 
                          class="text-amber-500 rounded focus:ring-0"
                        />
                        <span class="text-slate-300 font-medium">{{ opt }}</span>
                      </label>
                    </div>

                    <div v-else-if="field.type === 'file'" class="pt-1">
                      <div class="border-2 border-dashed border-slate-800 hover:border-amber-500/50 rounded-2xl p-4 text-center bg-slate-950/70 transition-all cursor-pointer">
                        <i class="ri-upload-cloud-2-line text-2xl text-amber-400 mb-1 d-block"></i>
                        <div class="text-slate-300 font-semibold text-xs">Klik atau seret berkas ke sini</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                          Format: {{ field.allowed_extensions?.join(', ') || 'Semua berkas' }} • Maksimal {{ field.max_size_mb || 5 }} MB
                        </div>
                      </div>
                    </div>

                    <div v-else-if="field.type === 'date'">
                      <input 
                        type="date" 
                        v-model="previewValues[field.name]"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                      />
                    </div>

                    <div v-if="field.help_text" class="text-[11px] text-slate-500">
                      <i class="ri-information-line me-0.5"></i> {{ field.help_text }}
                    </div>
                  </div>
                </template>

                <!-- Submit Button simulation -->
                <div class="pt-4 mt-6 border-t border-slate-800">
                  <button 
                    type="button" 
                    class="w-full py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20"
                    @click="toast.success('Simulasi pengisian berhasil! Semua validasi lancar.')"
                  >
                    {{ formSettings.submit_text || 'Kirim Pendaftaran Delegasi' }}
                  </button>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: BULK OPTIONS EDITOR                                   -->
    <!-- ============================================================== -->
    <div 
      v-if="showBulkModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0,0,0,0.6); z-index: 1070;"
      @click.self="showBulkModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light border-bottom py-3">
            <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="ri-file-text-line text-primary fs-18"></i>
              Tempel Pilihan Opsi Sekaligus
            </h5>
            <button type="button" class="btn-close" @click="showBulkModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-muted fs-12 mb-2">
              Ketik atau tempel (paste) setiap opsi pada <strong>baris baru</strong> (1 opsi per baris). Anda bisa langsung menyalin dari file Excel atau Word.
            </p>
            <textarea 
              v-model="bulkText" 
              rows="7" 
              class="form-control fs-12 font-mono" 
              placeholder="Pilihan 1&#10;Pilihan 2&#10;Pilihan 3..."
            ></textarea>
          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" @click="showBulkModal = false">Batal</button>
            <button type="button" class="btn btn-sm btn-primary fw-semibold" @click="saveBulkOptions">
              <i class="ri-check-line"></i> Simpan Daftar Opsi
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: FORM SETTINGS & PACKAGE TOGGLE                         -->
    <!-- ============================================================== -->
    <div 
      v-if="showSettingsModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0,0,0,0.6); z-index: 1070;"
      @click.self="showSettingsModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light border-bottom py-3">
            <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="ri-settings-3-line text-primary fs-18"></i>
              Opsi Tampilan Formulir: {{ selectedCompetition?.title }}
            </h5>
            <button type="button" class="btn-close" @click="showSettingsModal = false"></button>
          </div>
          <div class="modal-body p-4 space-y-3">
            <div class="p-3 bg-light rounded-3 border mb-3">
              <div class="form-check form-switch d-flex align-items-center justify-content-between mb-0">
                <div>
                  <label class="form-check-label fs-13 fw-bold text-dark mb-0" for="togglePackage">
                    Tampilkan Pilihan Paket (Satuan / All-In)
                  </label>
                  <p class="text-muted fs-11 mb-0 mt-0.5">
                    Jika nonaktif, pendaftaran menggunakan tarif satuan cabang ini (Rp {{ Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID') }}).
                  </p>
                </div>
                <input 
                  class="form-check-input cursor-pointer ms-3" 
                  type="checkbox" 
                  role="switch" 
                  id="togglePackage"
                  v-model="formSettings.show_package_type" 
                />
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fs-12 fw-semibold text-dark">Judul Header Formulir:</label>
              <input 
                type="text" 
                v-model="formSettings.form_title"
                class="form-control fs-12"
                placeholder="Contoh: FORMULIR PENDAFTARAN DELEGASI"
              />
            </div>

            <div class="mb-3">
              <label class="form-label fs-12 fw-semibold text-dark">Teks Subtitle / Petunjuk:</label>
              <input 
                type="text" 
                v-model="formSettings.form_subtitle"
                class="form-control fs-12"
                placeholder="Contoh: Silakan isi data delegasi di bawah ini dengan lengkap..."
              />
            </div>

            <div class="mb-2">
              <label class="form-label fs-12 fw-semibold text-dark">Teks Tombol Kirim (Submit):</label>
              <input 
                type="text" 
                v-model="formSettings.submit_text"
                class="form-control fs-12"
                placeholder="Contoh: Kirim Pendaftaran Delegasi"
              />
            </div>
          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" @click="showSettingsModal = false">Tutup</button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary fw-semibold"
              @click="showSettingsModal = false; saveFormFields()"
            >
              <i class="ri-check-line"></i> Simpan Pengaturan
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: TEMPLATE PRESET SELECTION                              -->
    <!-- ============================================================== -->
    <div 
      v-if="showPresetModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0,0,0,0.6); z-index: 1070;"
      @click.self="showPresetModal = false"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary-subtle border-bottom py-3">
            <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="ri-magic-line text-primary fs-18"></i>
              Pilih Template Preset Formulir Lomba
            </h5>
            <button type="button" class="btn-close" @click="showPresetModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-muted fs-12 mb-3">
              Pilih template yang sesuai dengan kategori lomba <strong>{{ selectedCompetition?.title }}</strong>. Susunan pertanyaan akan otomatis disesuaikan secara instan.
            </p>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-warning-subtle text-warning fs-11">Akademik & Sains</span>
                    <i class="ri-microscope-line fs-20 text-warning"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Olimpiade MIPA & IPS</h6>
                  <p class="text-muted fs-11 mb-3">
                    Sekolah, pembina, WhatsApp, nama peserta & kelas, NISN siswa, dan scan kartu pelajar (KTS).
                  </p>
                  <button type="button" class="btn btn-sm btn-outline-warning mt-auto w-100 fw-semibold" @click="applyPreset('akademik')">
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-info-subtle text-info fs-11">Keagamaan</span>
                    <i class="ri-book-read-line fs-20 text-info"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">MHQ & Tahfidz Al-Qur'an</h6>
                  <p class="text-muted fs-11 mb-3">
                    Lembaga pondok, pembina tahfidz, WhatsApp, nama santri, pilihan golongan juz, dan surat keterangan tashih.
                  </p>
                  <button type="button" class="btn btn-sm btn-outline-info mt-auto w-100 fw-semibold" @click="applyPreset('tahfidz')">
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary fs-11">Seni & Bahasa</span>
                    <i class="ri-music-2-line fs-20 text-primary"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Albanjari, Singer & Storytelling</h6>
                  <p class="text-muted fs-11 mb-3">
                    Sekolah/sanggar, nama panggung, pembina, WhatsApp, personil grup, judul lagu, dan upload minus one.
                  </p>
                  <button type="button" class="btn btn-sm btn-outline-primary mt-auto w-100 fw-semibold" @click="applyPreset('seni')">
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-danger-subtle text-danger fs-11">E-Sport</span>
                    <i class="ri-gamepad-line fs-20 text-danger"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Mobile Legends (MSA S4)</h6>
                  <p class="text-muted fs-11 mb-3">
                    Nama squad, User ID & Server kapten, nickname in-game, WhatsApp, roster 6 pemain, dan screenshot profil.
                  </p>
                  <button type="button" class="btn btn-sm btn-outline-danger mt-auto w-100 fw-semibold" @click="applyPreset('esport')">
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success-subtle text-success fs-11">Olahraga</span>
                    <i class="ri-football-line fs-20 text-success"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Tournament Bola Voli</h6>
                  <p class="text-muted fs-11 mb-3">
                    Nama tim voli, nama & kontak pelatih, nomor punggung & posisi pemain, dan scan KTS kolektif PDF.
                  </p>
                  <button type="button" class="btn btn-sm btn-outline-success mt-auto w-100 fw-semibold" @click="applyPreset('olahraga')">
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-secondary-subtle text-secondary fs-11">Umum Ringkas</span>
                    <i class="ri-settings-4-line fs-20 text-secondary"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Data Pendaftaran Fleksibel</h6>
                  <p class="text-muted fs-11 mb-3">
                    Nama instansi/sekolah, nomor kontak WhatsApp, dan daftar nama peserta kontingen.
                  </p>
                  <button type="button" class="btn btn-sm btn-outline-secondary mt-auto w-100 fw-semibold" @click="applyPreset('umum')">
                    Terapkan Template Ini
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light border-top py-2 px-4">
            <button type="button" class="btn btn-sm btn-secondary" @click="showPresetModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 4: COPY CONFIG FROM ANOTHER COMPETITION                  -->
    <!-- ============================================================== -->
    <div 
      v-if="showCopyModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0,0,0,0.6); z-index: 1070;"
      @click.self="showCopyModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light border-bottom py-3">
            <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="ri-file-copy-line text-primary fs-18"></i>
              Salin Konfigurasi dari Cabang Lain
            </h5>
            <button type="button" class="btn-close" @click="showCopyModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-muted fs-12 mb-3">
              Pilih cabang lomba sumber yang susunan pertanyaannya ingin disalin ke <strong>{{ selectedCompetition?.title }}</strong>.
            </p>
            <div class="mb-3">
              <label class="form-label fs-12 fw-semibold text-dark">Pilih Cabang Lomba Sumber:</label>
              <select v-model="sourceCompIdToCopy" class="form-select fs-13">
                <option value="" disabled selected>-- Pilih Cabang Sumber --</option>
                <option 
                  v-for="c in competitions.filter(item => item.id !== currentCompId)" 
                  :key="c.id" 
                  :value="c.id"
                >
                  {{ c.icon }} {{ c.title }} ({{ c.custom_fields?.length || 0 }} Field)
                </option>
              </select>
            </div>
          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" @click="showCopyModal = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary fw-semibold"
              :disabled="!sourceCompIdToCopy"
              @click="executeCopy"
            >
              <i class="ri-check-line"></i> Salin Formulir Sekarang
            </button>
          </div>
        </div>
      </div>
    </div>

  </VelzonLayout>
</template>

<style scoped>
/* Top Command Bar & Comp Select */
.comp-select {
  border-radius: 0 0.375rem 0.375rem 0;
}
.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  display: inline-block;
  animation: pulse 1.8s infinite;
}
@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
}

/* Sticky Panes */
.sticky-toolbox {
  position: sticky;
  top: 75px;
  z-index: 10;
}
.toolbox-scroll-area {
  max-height: calc(100vh - 270px);
  overflow-y: auto;
}
.sticky-preview-card {
  position: sticky;
  top: 75px;
  z-index: 10;
}
.preview-scroll-area {
  max-height: calc(100vh - 165px);
  overflow-y: auto;
}
.canvas-scroll-area {
  min-height: 480px;
}

/* Tool Cards */
.tool-icon-box {
  width: 28px;
  height: 28px;
  background-color: rgba(64, 81, 137, 0.08);
}
.hover-tool-card:hover {
  background-color: rgba(64, 81, 137, 0.05) !important;
  border-color: #405189 !important;
  transform: translateX(2px);
}

/* Field Cards */
.field-card {
  transition: all 0.2s ease;
  overflow: hidden;
}
.field-card:hover {
  box-shadow: 0 3px 10px rgba(0,0,0,0.04);
}
.field-card.drag-over {
  border: 2px dashed #405189 !important;
  background-color: rgba(64, 81, 137, 0.05);
}
.drag-grip {
  cursor: grab;
}
.drag-grip:active {
  cursor: grabbing;
}
.hover-drag:hover {
  background-color: rgba(0,0,0,0.06);
  color: #405189 !important;
}

/* Chevron Rotation */
.chevron-btn i {
  transition: transform 0.2s ease;
}

/* Preset Cards */
.hover-preset-card {
  transition: all 0.2s ease;
}
.hover-preset-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
  border-color: #405189 !important;
  transform: translateY(-2px);
}
</style>

<!-- Non-scoped Dark Mode Styles for Form Builder to ensure 100% theme fidelity -->
<style>
[data-bs-theme="dark"] .top-command-bar,
[data-bs-theme="dark"] .sticky-toolbox,
[data-bs-theme="dark"] .form-canvas-card,
[data-bs-theme="dark"] .full-preview-card {
  background-color: #111827 !important;
  border-color: #1e293b !important;
}

[data-bs-theme="dark"] .view-mode-pill {
  background-color: #0b0f19 !important;
  border-color: #1e293b !important;
}

[data-bs-theme="dark"] .comp-select {
  background-color: #0b1120 !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .hover-tool-card {
  background-color: #0b1120 !important;
  border-color: #1e293b !important;
  color: #f1f5f9 !important;
}
[data-bs-theme="dark"] .hover-tool-card:hover {
  background-color: #162032 !important;
  border-color: #3b82f6 !important;
}
[data-bs-theme="dark"] .tool-icon-box {
  background-color: #1e293b !important;
}

[data-bs-theme="dark"] .field-card {
  background-color: #0f172a !important;
  border-color: #1e293b !important;
}
[data-bs-theme="dark"] .field-header {
  background-color: #111827 !important;
}
[data-bs-theme="dark"] .field-body {
  background-color: #0b0f19 !important;
}

[data-bs-theme="dark"] .field-card input,
[data-bs-theme="dark"] .field-card select,
[data-bs-theme="dark"] .field-card textarea,
[data-bs-theme="dark"] .sticky-toolbox input,
[data-bs-theme="dark"] .form-canvas-card input {
  background-color: #0b1120 !important;
  border-color: #1e293b !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .field-card input::placeholder,
[data-bs-theme="dark"] .field-card textarea::placeholder,
[data-bs-theme="dark"] .sticky-toolbox input::placeholder {
  color: #64748b !important;
}

[data-bs-theme="dark"] .field-title-text {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .modal-content {
  background-color: #111827 !important;
  color: #f8fafc !important;
}
[data-bs-theme="dark"] .modal-header,
[data-bs-theme="dark"] .modal-footer {
  background-color: #162032 !important;
  border-color: #1e293b !important;
}
[data-bs-theme="dark"] .modal-content input,
[data-bs-theme="dark"] .modal-content textarea,
[data-bs-theme="dark"] .modal-content select {
  background-color: #0b1120 !important;
  border-color: #1e293b !important;
  color: #f8fafc !important;
}
</style>
