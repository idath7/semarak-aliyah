<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
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
const previewValues = ref({});

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

function isDistrictField(f) {
  if (!f) return false;
  return f.name === 'school_district' || 
         f.default_name === 'school_district' || 
         (f.label && f.label.toLowerCase().includes('kecamatan'));
}

function isExpanded(id) {
  if (expandedMap.value[id] !== undefined) {
    return expandedMap.value[id];
  }
  const field = fields.value.find(f => f.id === id);
  if (isDistrictField(field)) {
    return false;
  }
  return true;
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

// Default sub-fields generator for dynamic participant repeater
function createDefaultSubFields() {
  return {
    name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
    gender: { active: true, required: true, label: 'Jenis Kelamin (L/P)' },
    grade: { active: true, required: true, label: 'Kelas / Tingkat' },
    birth_date: { active: true, required: false, label: 'Tanggal Lahir Siswa' },
    nisn: { active: true, required: false, label: 'Nomor Induk Siswa Nasional (NISN)' },
    custom_id: { active: false, required: false, label: 'ID Siswa / No. Identitas (Teks)' },
    wa_number: { active: false, required: false, label: 'Nomor WhatsApp Siswa (Angka)' },
    captain_option: { active: false, required: false, label: 'Tandai Kapten Tim & Nomor WA Khusus Kapten' },
    photo_formal: { active: false, required: false, label: 'Upload Pas Foto Formal Siswa' },
    photo_freestyle: { active: false, required: false, label: 'Upload Foto Pose Gaya Bebas (Bisa >1 Foto)', multiple: true },
    custom_columns: [],
  };
}

function addCustomSubField(field) {
  if (!field.sub_fields) field.sub_fields = createDefaultSubFields();
  if (!Array.isArray(field.sub_fields.custom_columns)) {
    field.sub_fields.custom_columns = [];
  }
  field.sub_fields.custom_columns.push({
    id: 'col_' + Date.now(),
    label: '',
    type: 'text',
    required: false,
  });
  isDirty.value = true;
}

function removeCustomSubField(field, idx) {
  if (Array.isArray(field.sub_fields?.custom_columns)) {
    field.sub_fields.custom_columns.splice(idx, 1);
    isDirty.value = true;
  }
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
 
// 31 Kecamatan Resmi Kabupaten Jember + Pilihan Luar Jember
const JEMBER_KECAMATAN_LIST = [
  'Ajung',
  'Ambulu',
  'Arjasa',
  'Balung',
  'Bangsalsari',
  'Gumukmas',
  'Jelbuk',
  'Jenggawah',
  'Jombang',
  'Kalisat',
  'Kaliwates',
  'Kencong',
  'Ledokombo',
  'Mayang',
  'Mumbulsari',
  'Pakusari',
  'Panti',
  'Patrang',
  'Puger',
  'Rambipuji',
  'Semboro',
  'Silo',
  'Sukorambi',
  'Sukowono',
  'Sumberbaru',
  'Sumberjambe',
  'Sumbersari',
  'Tanggul',
  'Tempurejo',
  'Umbulsari',
  'Wuluhan',
  'Luar Kabupaten Jember'
];

// 7 Standar Wajib Field Identitas Sekolah & Pembina
const MANDATORY_IDENTITY_CONFIG = [
  {
    name: 'school_name',
    label: 'Nama Sekolah / Madrasah',
    type: 'text',
    placeholder: 'Contoh: MTs Negeri 1 Jember / SMPN 1 Balung',
    help_text: 'Nama resmi lembaga sekolah atau madrasah pengirim delegasi',
    required: true,
    aliases: ['school_name', 'asal_sekolah', 'nama_sekolah', 'nama_lembaga', 'nama_sekolah_madrasah', 'nama_sekolah_madrasah_pengirim', 'nama_sekolah_madrasah_instansi_pengirim'],
    icon: 'ri-community-line',
    desc: 'Wajib ada pada setiap formulir cabang lomba'
  },
  {
    name: 'school_district',
    label: 'Kecamatan Sekolah',
    type: 'select',
    placeholder: 'Pilih Kecamatan Asal Sekolah...',
    help_text: 'Wilayah kecamatan tempat sekolah / madrasah berada di Kabupaten Jember',
    required: true,
    options: [...JEMBER_KECAMATAN_LIST],
    aliases: ['school_district', 'kecamatan', 'kecamatan_sekolah', 'kecamatan_asal_sekolah', 'kecamatan_asal_sekolah_madrasah', 'wilayah_kecamatan'],
    icon: 'ri-map-pin-2-line',
    desc: 'Dropdown 31 kecamatan se-Kabupaten Jember & Luar Jember'
  },
  {
    name: 'school_npsn',
    label: 'Nomor Pokok Sekolah Nasional (NPSN)',
    type: 'text',
    placeholder: 'Contoh: 20524589 (opsional jika ada)',
    help_text: 'Nomor Pokok Sekolah Nasional resmi (opsional jika belum memiliki)',
    required: false,
    aliases: ['school_npsn', 'npsn', 'npsn_sekolah', 'nomor_pokok_sekolah_nasional', 'nomor_pokok_sekolah_nasional_npsn'],
    icon: 'ri-hashtag',
    desc: 'Nomor NPSN resmi sekolah/madrasah (opsional)'
  },
  {
    name: 'school_address',
    label: 'Alamat Sekolah / Madrasah',
    type: 'textarea',
    placeholder: 'Contoh: Jl. Raya Puger No. 20, Balung Kulon, Kec. Balung',
    help_text: 'Alamat lengkap jalan, desa/kelurahan, dan nomor bangunan sekolah/madrasah',
    required: true,
    aliases: ['school_address', 'alamat_sekolah', 'alamat_lengkap_sekolah', 'alamat_sekolah_madrasah', 'alamat_lengkap_sekolah_madrasah', 'alamat'],
    icon: 'ri-road-map-line',
    desc: 'Alamat fisik sekolah/madrasah pengirim delegasi'
  },
  {
    name: 'mentor_name',
    label: 'Nama Pendamping',
    type: 'text',
    placeholder: 'Contoh: Ustadz Ahmad Fauzi, S.Pd',
    help_text: 'Nama lengkap guru atau official pembina pendamping peserta',
    required: true,
    aliases: ['mentor_name', 'nama_pendamping', 'nama_guru_pendamping', 'nama_pembina', 'nama_guru_pembina_pendamping', 'nama_pelatih_official'],
    icon: 'ri-user-star-line',
    desc: 'Nama guru pembina pendamping delegasi peserta'
  },
  {
    name: 'mentor_wa',
    label: 'Nomor WA / Kontak Pendamping',
    type: 'number',
    placeholder: 'Contoh: 081234567890',
    help_text: 'Nomor WhatsApp aktif untuk koordinasi teknis dan TM (hanya angka/nomor)',
    required: true,
    aliases: ['mentor_wa', 'nomor_wa', 'whatsapp', 'nomor_whatsapp', 'nomor_whatsapp_pendamping', 'nomor_whatsapp_kontak', 'nomor_whatsapp_pembina', 'no_whatsapp_pelatih_official', 'no_whatsapp_kapten_kontak_tim'],
    icon: 'ri-whatsapp-line',
    desc: 'Nomor WhatsApp aktif pendamping (hanya angka/nomor)'
  },
  {
    name: 'mentor_email',
    label: 'Email Pendamping',
    type: 'email',
    placeholder: 'Contoh: pendamping@gmail.com / official@sekolah.sch.id',
    help_text: 'Alamat email aktif untuk tanda terima dan surat pengantar lomba',
    required: true,
    aliases: ['mentor_email', 'contact_email', 'email', 'alamat_email', 'email_pendamping', 'alamat_email_aktif', 'email_kontak', 'alamat_email_resmi_sekolah_pembina'],
    icon: 'ri-mail-line',
    desc: 'Email aktif pendamping untuk tanda terima pendaftaran'
  },
  {
    name: 'allin_code',
    label: 'Kode Paket Komplit (All-In)',
    type: 'text',
    placeholder: 'Contoh: SA26-XXXXXX (Otomatis jika sekolah terverifikasi All-In)',
    help_text: 'Masukkan kode paket jika sekolah Anda mendaftar Paket Komplit agar pendaftaran cabang mandiri ini bebas biaya (Rp 0).',
    required: false,
    aliases: ['allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin', 'package_code', 'all_in_code', 'kode_paket_allin'],
    icon: 'ri-vip-crown-line',
    desc: 'Penanda pendaftaran cabang mandiri bagian dari Paket Komplit (Rp 0)'
  }
];

// Presets in Toolbox (Tab 3: Identitas)
const coreIdentityPresets = MANDATORY_IDENTITY_CONFIG.map(m => ({
  name: m.label,
  type: m.type,
  default_name: m.name,
  label: m.label,
  placeholder: m.placeholder,
  help_text: m.help_text,
  required: m.required,
  icon: m.icon,
  desc: m.desc,
  options: m.options ? [...m.options] : [],
  is_mandatory_identity: true,
}));

function isMandatoryIdentity(field) {
  if (!field) return false;
  if (field.is_mandatory_identity) return true;
  return MANDATORY_IDENTITY_CONFIG.some(m => m.name === field.name || m.aliases.includes(field.name));
}

function ensureMandatoryIdentityFields() {
  const current = fields.value || [];
  const foundMap = {};
  const remaining = [];

  current.forEach(f => {
    let matchedConfig = null;
    for (const m of MANDATORY_IDENTITY_CONFIG) {
      if (!foundMap[m.name] && (f.name === m.name || m.aliases.includes(f.name))) {
        matchedConfig = m;
        break;
      }
    }
    if (matchedConfig) {
      foundMap[matchedConfig.name] = f;
    } else {
      remaining.push(f);
    }
  });

  const mandatoryOrdered = MANDATORY_IDENTITY_CONFIG.map(m => {
    const existing = foundMap[m.name];
    if (existing) {
      return {
        ...existing,
        id: existing.id || generateId(),
        name: m.name,
        label: existing.label || m.label,
        type: m.type,
        placeholder: existing.placeholder || m.placeholder,
        help_text: existing.help_text || m.help_text,
        required: m.required,
        options: m.type === 'select' ? [...m.options] : (existing.options || []),
        is_mandatory_identity: true,
      };
    } else {
      return {
        id: generateId(),
        name: m.name,
        label: m.label,
        type: m.type,
        placeholder: m.placeholder,
        help_text: m.help_text,
        required: m.required,
        options: m.options ? [...m.options] : [],
        allowed_extensions: [],
        max_size_mb: null,
        is_mandatory_identity: true,
        conditional_logic: {
          enabled: false,
          depend_on_field: '',
          operator: '==',
          value: '',
        }
      };
    }
  });

  fields.value = [...mandatoryOrdered, ...remaining];
}

function restoreMandatoryFields() {
  ensureMandatoryIdentityFields();
  fields.value.forEach((f, idx) => {
    if (isDistrictField(f)) {
      expandedMap.value[f.id] = false;
    } else if (idx < 7) {
      expandedMap.value[f.id] = true;
    }
  });
  isDirty.value = true;
  toast.success('7 Field Identitas Wajib Standar berhasil dipulihkan lengkap!');
}

// Initialize Data from Props
function initializeData() {
  const sourceFields = props.selectedCompetition?.custom_fields;
  if (Array.isArray(sourceFields) && sourceFields.length > 0) {
    fields.value = JSON.parse(JSON.stringify(sourceFields));
  } else {
    fields.value = [];
  }

  // Enforce 7 mandatory identity fields
  ensureMandatoryIdentityFields();

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
      const defs = createDefaultSubFields();
      if (!f.sub_fields || typeof f.sub_fields !== 'object') {
        f.sub_fields = defs;
      } else {
        // Merge any new sub_fields keys safely
        for (const k in defs) {
          if (!f.sub_fields[k]) {
            f.sub_fields[k] = { ...defs[k] };
          }
        }
      }
    }
  });

  // Setup initial expansion: if more than 3 fields, expand first 2 (excluding district), collapse rest
  const newMap = {};
  fields.value.forEach((f, idx) => {
    if (isDistrictField(f)) {
      newMap[f.id] = false;
    } else {
      newMap[f.id] = fields.value.length <= 3 ? true : (idx < 2);
    }
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
  { 
    type: 'number', 
    label: 'Nomor WhatsApp', 
    icon: 'ri-whatsapp-line', 
    desc: 'Nomor WhatsApp kontak (mode angka numerik)', 
    defaultOptions: [], 
    defaultName: 'mentor_wa',
    defaultLabel: 'Nomor WhatsApp Kontak Aktif',
    defaultPlaceholder: 'Contoh: 081234567890',
    defaultHelpText: 'Nomor WhatsApp aktif untuk koordinasi teknis (hanya angka)',
    defaultRequired: true,
    badge: 'Kontak' 
  },
  { 
    type: 'email', 
    label: 'Alamat Email', 
    icon: 'ri-mail-line', 
    desc: 'Alamat email aktif (validasi format @)', 
    defaultOptions: [], 
    defaultName: 'contact_email',
    defaultLabel: 'Alamat Email Aktif',
    defaultPlaceholder: 'nama@sekolah.sch.id',
    defaultHelpText: 'Email untuk bukti pendaftaran atau pengiriman berkas',
    defaultRequired: false,
    badge: 'Kontak' 
  },
  { type: 'textarea', label: 'Paragraf / Roster', icon: 'ri-text', desc: 'Deskripsi, daftar anggota, biodata', defaultOptions: [], badge: 'Teks Panjang' },
  { type: 'select', label: 'Pilihan Dropdown', icon: 'ri-arrow-down-s-line', desc: 'Pilih 1 dari daftar opsi', defaultOptions: ['Pilihan 1', 'Pilihan 2', 'Pilihan 3'], badge: 'Pilihan' },
  { 
    type: 'select', 
    label: 'Kecamatan Sekolah / Madrasah', 
    icon: 'ri-map-pin-2-line', 
    desc: 'Dropdown 31 kecamatan se-Kabupaten Jember & Luar Jember', 
    defaultOptions: [...JEMBER_KECAMATAN_LIST],
    defaultName: 'school_district',
    defaultLabel: 'Kecamatan Asal Sekolah / Madrasah',
    defaultHelpText: 'Pilih wilayah kecamatan tempat sekolah / madrasah berada',
    defaultPlaceholder: 'Pilih Kecamatan...',
    defaultRequired: true,
    badge: 'Wilayah'
  },
  { type: 'radio', label: 'Pilihan Tunggal (Radio)', icon: 'ri-radio-button-line', desc: 'Pilihan eksklusif radio button', defaultOptions: ['Pilihan A', 'Pilihan B'], badge: 'Pilihan' },
  { type: 'checkbox', label: 'Pilihan Ganda (Checkbox)', icon: 'ri-checkbox-line', desc: 'Centang satu atau beberapa opsi', defaultOptions: ['Item 1', 'Item 2', 'Item 3'], badge: 'Pilihan' },
  { type: 'file', label: 'Upload Berkas / File', icon: 'ri-upload-cloud-2-line', desc: 'Unggah PDF, JPG, PNG, dll.', defaultOptions: [], badge: 'Media' },
  { type: 'url', label: 'Tautan / Link URL', icon: 'ri-link', desc: 'Link Google Drive, YouTube, dll.', defaultOptions: [], badge: 'Tautan' },
  { type: 'date', label: 'Tanggal', icon: 'ri-calendar-line', desc: 'Tanggal lahir, tanggal terbit', defaultOptions: [], badge: 'Waktu' },
];

// Dropdown Options for CustomSelect UI
const competitionOptions = computed(() => {
  return (props.competitions || []).map(c => ({
    value: c.id,
    label: `${c.icon ? c.icon + ' ' : ''}${c.title}`,
    desc: `${c.custom_fields?.length || 0} Pertanyaan • ${c.category_label || c.category || 'Cabang Lomba'}`,
    badge: c.category_label || c.category,
    icon: 'ri-trophy-line',
  }));
});

const fieldTypeOptions = computed(() => {
  const seen = new Set();
  const list = [];
  for (const ft of fieldTypes) {
    if (!seen.has(ft.type)) {
      seen.add(ft.type);
      list.push({
        value: ft.type,
        label: ft.type === 'select' ? 'Pilihan Dropdown' : (ft.type === 'number' ? 'Angka / Nominal (Mode Nomor)' : ft.label),
        desc: ft.desc,
        icon: ft.icon,
        badge: ft.badge,
        badgeClass: ft.isRepeater ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary'
      });
    }
  }
  return list;
});

const columnTypeOptions = [
  { value: 'text', label: 'Teks Singkat', icon: 'ri-input-field' },
  { value: 'number', label: 'Angka', icon: 'ri-hashtag' },
  { value: 'email', label: 'Email', icon: 'ri-mail-line' },
  { value: 'date', label: 'Tanggal (dd/mm/yyyy)', icon: 'ri-calendar-line' },
  { value: 'select', label: 'Dropdown Pilihan', icon: 'ri-arrow-down-s-line' },
];

const conditionOperatorOptions = [
  { value: '==', label: 'Sama dengan (==)', icon: 'ri-equal-line' },
  { value: '!=', label: 'Tidak sama dengan (!=)', icon: 'ri-not-equal-line' },
  { value: '>=', label: 'Lebih besar / sama (>=)', icon: 'ri-arrow-right-s-line' },
  { value: '<=', label: 'Lebih kecil / sama (<=)', icon: 'ri-arrow-left-s-line' },
  { value: '>', label: 'Lebih besar dari (>)', icon: 'ri-arrow-right-line' },
  { value: '<', label: 'Lebih kecil dari (<)', icon: 'ri-arrow-left-line' },
  { value: 'contains', label: 'Mengandung kata', icon: 'ri-text' },
];

function getConditionalTargetOptions(currentFieldId) {
  return (fields.value || [])
    .filter(f => f.id !== currentFieldId)
    .map(other => ({
      value: other.name,
      label: `${other.label || 'Field'} (${other.name})`,
      icon: 'ri-questionnaire-line'
    }));
}

const copySourceCompetitionOptions = computed(() => {
  return (props.competitions || [])
    .filter(c => String(c.id) !== String(currentCompId.value))
    .map(c => ({
      value: c.id,
      label: `${c.icon ? c.icon + ' ' : ''}${c.title}`,
      desc: `${c.custom_fields?.length || 0} Pertanyaan`,
      badge: c.category_label || c.category,
      icon: 'ri-trophy-line'
    }));
});

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
  {
    name: 'Kecamatan Asal Sekolah / Madrasah (Jember)',
    type: 'select',
    label: 'Kecamatan Asal Sekolah / Madrasah',
    placeholder: 'Pilih Kecamatan...',
    help_text: 'Wilayah kecamatan tempat sekolah/madrasah berada di Kabupaten Jember',
    required: true,
    options: [...JEMBER_KECAMATAN_LIST],
    category: 'Identitas Sekolah',
  },
  {
    name: 'Nomor WhatsApp Kontak (Mode Angka)',
    type: 'number',
    label: 'Nomor WhatsApp Kontak Aktif',
    placeholder: 'Contoh: 081234567890',
    help_text: 'Nomor WhatsApp aktif untuk koordinasi teknis dan TM (hanya angka)',
    required: true,
    category: 'Kontak',
  },
  {
    name: 'Alamat Email Resmi Sekolah / Pembina',
    type: 'email',
    label: 'Alamat Email Resmi Sekolah / Pembina',
    placeholder: 'nama@sekolah.sch.id',
    help_text: 'Email aktif untuk pengiriman surat tanda terima dan juknis lomba',
    required: false,
    category: 'Kontak',
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
  const label = typeObj.defaultLabel || (typeObj.type === 'participant_repeater' ? 'Biodata Siswa Delegasi' : `Pertanyaan Baru (${typeObj.label})`);
  const name = typeObj.defaultName || makeSlug(label);

  const newField = {
    id: id,
    name: name,
    label: label,
    type: typeObj.type,
    placeholder: typeObj.defaultPlaceholder || '',
    help_text: typeObj.defaultHelpText || (typeObj.type === 'participant_repeater' ? 'Jumlah biodata siswa yang tampil otomatis menyesuaikan angka yang dipilih pendaftar.' : ''),
    required: typeObj.defaultRequired !== undefined ? typeObj.defaultRequired : (typeObj.type === 'participant_repeater'),
    options: typeObj.defaultOptions ? [...typeObj.defaultOptions] : [],
    allowed_extensions: typeObj.type === 'file' ? ['pdf', 'jpg', 'jpeg', 'png'] : [],
    max_size_mb: typeObj.type === 'file' ? 5 : null,
    max_files: typeObj.type === 'file' ? 1 : null,
    min_date: typeObj.min_date || null,
    max_date: typeObj.max_date || null,
    date_default: typeObj.date_default || '',
    min_items: typeObj.type === 'participant_repeater' ? 1 : null,
    max_items: typeObj.type === 'participant_repeater' ? 10 : null,
    sub_fields: typeObj.type === 'participant_repeater' ? createDefaultSubFields() : null,
    conditional_logic: {
      enabled: false,
      depend_on_field: '',
      operator: '==',
      value: '',
    }
  };

  fields.value.push(newField);
  expandedMap.value[id] = !isDistrictField(newField);
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
    required: preset.required !== undefined ? preset.required : true,
    options: preset.options ? [...preset.options] : [],
    allowed_extensions: [],
    max_size_mb: null,
    max_files: null,
    is_mandatory_identity: !!preset.is_mandatory_identity,
    conditional_logic: {
      enabled: false,
      depend_on_field: '',
      operator: '==',
      value: '',
    }
  };

  fields.value.push(newField);
  expandedMap.value[id] = !isDistrictField(preset);
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
    max_files: preset.max_files || (preset.type === 'file' ? 1 : null),
    min_items: preset.min_items || (preset.type === 'participant_repeater' ? 1 : null),
    max_items: preset.max_items !== undefined ? preset.max_items : null,
    sub_fields: preset.sub_fields ? JSON.parse(JSON.stringify(preset.sub_fields)) : (preset.type === 'participant_repeater' ? createDefaultSubFields() : null),
    conditional_logic: {
      enabled: false,
      depend_on_field: '',
      operator: '==',
      value: '',
    }
  };

  fields.value.push(newField);
  expandedMap.value[id] = !isDistrictField(newField);
  if (preset.type === 'participant_repeater') {
    previewValues.value[name + '_count'] = 3;
  }
  toast.success(`Preset "${preset.name}" ditambahkan!`);
}

// Field Operations
async function removeField(index) {
  const f = fields.value[index];
  if (isMandatoryIdentity(f)) {
    const ok = await confirmDialog({
      title: 'Hapus Field Identitas Standar?',
      message: `Pertanyaan "${f.label}" merupakan salah satu dari 7 field identitas standar (Nama Sekolah, Kecamatan, NPSN, Alamat, Pendamping, WA, Email). Anda yakin ingin menghapusnya? (Dapat dipulihkan kapan saja via tombol 'Pulihkan 7 Field Standar').`,
      type: 'warning',
      confirmText: 'Tetap Hapus',
      cancelText: 'Batal',
      icon: 'ri-alert-line',
    });
    if (ok) {
      fields.value.splice(index, 1);
      delete expandedMap.value[f.id];
      toast.info(`Field "${f.label}" dihapus. (Dapat dipulihkan via tombol 'Pulihkan 7 Field Standar')`);
    }
    return;
  }

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
  } else if (type === 'kecamatan_jember') {
    field.options = [...JEMBER_KECAMATAN_LIST];
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

  // Pastikan 7 Field Identitas Wajib Standar selalu lengkap sebelum disimpan
  const existingNames = fields.value.map(f => f.name);
  const missing = MANDATORY_IDENTITY_CONFIG.filter(m => !existingNames.includes(m.name) && !m.aliases.some(a => existingNames.includes(a)));
  if (missing.length > 0) {
    ensureMandatoryIdentityFields();
    toast.info('7 Field Identitas Standar otomatis dilengkapi sebelum penyimpanan.');
  }

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

// Modals & Presets
const showPresetModal = ref(false);
const showCopyModal = ref(false);
const showSettingsModal = ref(false);
const showEditPresetModal = ref(false);
const isSavingPreset = ref(false);
const sourceCompIdToCopy = ref('');
const showOptionsMenu = ref(false);
const optionsDropdownRef = ref(null);

const presetList = computed(() => {
  const p = props.presets || {};
  return Object.values(p);
});

const editPresetForm = ref({
  key: '',
  title: '',
  category: 'Kustom',
  description: '',
  icon: 'ri-magic-line',
  badge: 'Kustom',
  badge_class: 'bg-primary-subtle text-primary',
  fields: [],
  is_custom: true,
});

const expandedPresetFields = ref({});

function togglePresetFieldExpand(id) {
  expandedPresetFields.value[id] = !expandedPresetFields.value[id];
}

function ensurePresetFieldDefaults(pf) {
  if (pf.type === 'participant_repeater') {
    if (!pf.min_items) pf.min_items = 1;
    if (pf.max_items === undefined || pf.max_items === null) pf.max_items = 10;
    const defs = createDefaultSubFields();
    if (!pf.sub_fields || typeof pf.sub_fields !== 'object') {
      pf.sub_fields = defs;
    } else {
      for (const k in defs) {
        if (!pf.sub_fields[k]) {
          pf.sub_fields[k] = { ...defs[k] };
        }
      }
      if (!Array.isArray(pf.sub_fields.custom_columns)) {
        pf.sub_fields.custom_columns = [];
      }
    }
  }
}

function onPresetFieldTypeChange(pf) {
  if (pf.type === 'participant_repeater') {
    ensurePresetFieldDefaults(pf);
    if (!pf.label || pf.label === 'Pertanyaan Baru') {
      pf.label = 'Biodata Siswa Delegasi (Tim / Roster)';
    }
    expandedPresetFields.value[pf.id] = true;
  }
}

function addCustomSubFieldToPreset(pf) {
  ensurePresetFieldDefaults(pf);
  pf.sub_fields.custom_columns.push({
    id: 'col_' + Date.now(),
    label: '',
    type: 'text',
    required: false,
  });
}

function removeCustomSubFieldFromPreset(pf, idx) {
  if (Array.isArray(pf.sub_fields?.custom_columns)) {
    pf.sub_fields.custom_columns.splice(idx, 1);
  }
}

function openEditPresetModal(preset) {
  editPresetForm.value = {
    key: preset.key || '',
    title: preset.title || '',
    category: preset.category || 'Kustom',
    description: preset.description || '',
    icon: preset.icon || 'ri-magic-line',
    badge: preset.badge || 'Kustom',
    badge_class: preset.badge_class || 'bg-primary-subtle text-primary',
    fields: JSON.parse(JSON.stringify(preset.fields || [])),
    is_custom: !!preset.is_custom,
  };
  editPresetForm.value.fields.forEach(pf => {
    ensurePresetFieldDefaults(pf);
    if (pf.type === 'participant_repeater') {
      expandedPresetFields.value[pf.id] = true;
    }
  });
  showEditPresetModal.value = true;
}

function openSaveCurrentAsPresetModal() {
  const comp = props.selectedCompetition;
  const compTitle = comp?.title || 'Kustom';
  editPresetForm.value = {
    key: '',
    title: `Preset Formulir: ${compTitle}`,
    category: comp?.category_label || 'Kustom',
    description: `Template formulir preset yang disesuaikan untuk ${compTitle}`,
    icon: 'ri-magic-line',
    badge: 'Kustom Baru',
    badge_class: 'bg-success-subtle text-success',
    fields: JSON.parse(JSON.stringify(fields.value)),
    is_custom: true,
  };
  editPresetForm.value.fields.forEach(pf => {
    ensurePresetFieldDefaults(pf);
    if (pf.type === 'participant_repeater') {
      expandedPresetFields.value[pf.id] = true;
    }
  });
  showEditPresetModal.value = true;
}

function addPresetField(type = 'text') {
  const isRepeater = type === 'participant_repeater';
  const newId = 'cf_' + Math.random().toString(36).substr(2, 9);
  const newPf = {
    id: newId,
    name: 'field_' + (editPresetForm.value.fields.length + 1),
    label: isRepeater ? 'Biodata Siswa Delegasi (Tim / Roster)' : 'Pertanyaan Baru',
    type: type,
    placeholder: '',
    help_text: isRepeater ? 'Jumlah biodata otomatis menyesuaikan dengan angka yang dipilih pendaftar.' : '',
    required: isRepeater,
    options: [],
    allowed_extensions: [],
    max_size_mb: 5,
    min_items: isRepeater ? 1 : null,
    max_items: isRepeater ? 10 : null,
    main_members: null,
    reserve_members: null,
    sub_fields: isRepeater ? createDefaultSubFields() : null,
  };
  editPresetForm.value.fields.push(newPf);
  if (isRepeater) {
    expandedPresetFields.value[newId] = true;
  }
}

function removePresetField(idx) {
  editPresetForm.value.fields.splice(idx, 1);
}

function savePresetTemplate() {
  if (!editPresetForm.value.title || !editPresetForm.value.title.trim()) {
    toast.error('Nama/Judul Template Preset wajib diisi!');
    return;
  }
  editPresetForm.value.fields.forEach(pf => {
    if (pf.type === 'participant_repeater') {
      ensurePresetFieldDefaults(pf);
    }
  });
  isSavingPreset.value = true;
  router.post('/admin/form-builder/presets/save', editPresetForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingPreset.value = false;
      showEditPresetModal.value = false;
      toast.success(`Template preset "${editPresetForm.value.title}" berhasil disimpan & dapat digunakan!`);
    },
    onError: () => {
      isSavingPreset.value = false;
      toast.error('Gagal menyimpan template preset.');
    }
  });
}

async function deleteCustomPreset(presetKey) {
  const ok = await confirmDialog({
    title: 'Hapus Preset Kustom?',
    message: `Apakah Anda yakin ingin menghapus template preset ini? Tindakan ini tidak dapat dibatalkan.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Preset',
    icon: 'ri-delete-bin-line',
  });
  if (ok) {
    router.post('/admin/form-builder/presets/delete', { key: presetKey }, {
      preserveScroll: true,
      onSuccess: () => {
        toast.info('Template preset telah dihapus.');
      }
    });
  }
}

async function resetAllPresetsToDefault() {
  const ok = await confirmDialog({
    title: 'Kembalikan Preset ke Standar Bawaan?',
    message: 'Apakah Anda yakin ingin me-reset semua template preset ke susunan awal sistem?',
    type: 'warning',
    confirmText: 'Ya, Kembalikan Standar',
    icon: 'ri-refresh-line',
  });
  if (ok) {
    router.post('/admin/form-builder/presets/reset-default', {}, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Semua template preset telah di-reset ke standar bawaan sistem.');
      }
    });
  }
}

function syncWithCompetitionRules(field) {
  const comp = props.selectedCompetition;
  if (!comp) return;

  const isTeam = comp.participant_type === 'tim';
  const rule = comp.participants_rule || '';
  let max = comp.max_participants ? Number(comp.max_participants) : (isTeam ? 10 : 1);
  let min = isTeam ? Math.min(max, 3) : 1;
  let main = max;
  let reserve = 0;

  if (rule) {
    const matchInti = rule.match(/(\d+)\s*(?:pemain|orang)?\s*inti/i);
    const matchCadangan = rule.match(/(\d+)\s*cadangan/i);
    if (matchInti) {
      main = parseInt(matchInti[1], 10);
      min = main;
      if (matchCadangan) {
        reserve = parseInt(matchCadangan[1], 10);
      }
      max = main + reserve;
    }
  }

  field.min_items = min;
  field.max_items = max;
  field.main_members = main;
  field.reserve_members = reserve;

  isDirty.value = true;
  toast.success(`Berhasil disinkronkan dengan ${comp.title}: Maks. ${max} siswa (${main} Inti${reserve ? ' + ' + reserve + ' Cadangan' : ''}).`);
}

async function applyPreset(presetKey) {
  const presetObj = props.presets?.[presetKey];
  const presetTitle = presetObj?.title || presetKey;
  const ok = await confirmDialog({
    title: 'Terapkan Template Preset?',
    message: `Terapkan template "${presetTitle}"? Susunan pertanyaan saat ini akan digantikan dengan template siap pakai terpilih.`,
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
        toast.success(`Template preset "${presetTitle}" berhasil diterapkan!`);
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

function handleClickOutside(e) {
  if (optionsDropdownRef.value && !optionsDropdownRef.value.contains(e.target)) {
    showOptionsMenu.value = false;
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
  document.removeEventListener('click', handleClickOutside);
});

// Interactive Live Preview Simulation Helpers

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
        <div class="card-body p-2 px-3">
          <div class="d-flex align-items-center justify-content-between gap-2 flex-nowrap top-bar-nav-row">
            
            <!-- Left: Competition Selector & Navigation -->
            <div class="d-flex align-items-center gap-1.5 flex-shrink-1 min-w-0">
              <div class="d-flex align-items-center gap-1 flex-shrink-0">
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
              <div class="comp-selector-box flex-shrink-1 min-w-0">
                <CustomSelect
                  :model-value="currentCompId"
                  :options="competitionOptions"
                  @change="switchCompetition"
                  size="sm"
                  prefix-icon="ri-trophy-line text-warning"
                  searchable
                  search-placeholder="Cari cabang lomba..."
                  placeholder="Pilih Cabang Lomba..."
                  custom-class="fw-bold comp-select shadow-none"
                />
              </div>

              <!-- Unsaved Status Indicator -->
              <div v-if="isDirty" class="d-flex align-items-center gap-1.5 px-2 py-1 rounded-pill bg-warning-subtle text-warning-emphasis fs-11 fw-semibold text-nowrap flex-shrink-0">
                <span class="pulse-dot bg-warning"></span>
                <span class="d-none d-xl-inline">Belum Disimpan</span>
                <span class="d-inline d-xl-none">Belum Simpan</span>
              </div>
              <div v-else class="d-flex align-items-center gap-1 px-2 py-1 rounded-pill bg-success-subtle text-success fs-11 fw-semibold text-nowrap flex-shrink-0">
                <i class="ri-check-line"></i>
                <span>Tersimpan</span>
              </div>
            </div>

            <!-- Center: View Mode Switcher (Editor / Split / Preview) -->
            <div class="d-flex align-items-center justify-content-center flex-shrink-0 mx-1">
              <div class="view-mode-pill p-1 rounded-pill bg-light d-flex gap-1 border">
                <button 
                  type="button" 
                  class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold transition-all text-nowrap"
                  :class="viewMode === 'builder' ? 'btn-primary shadow-sm text-white' : 'btn-ghost-secondary text-muted'"
                  @click="viewMode = 'builder'"
                >
                  <i class="ri-layout-masonry-line me-1"></i>
                  <span class="d-none d-xxl-inline">Editor Penuh</span>
                  <span class="d-inline d-xxl-none">Editor</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold transition-all text-nowrap"
                  :class="viewMode === 'split' ? 'btn-primary shadow-sm text-white' : 'btn-ghost-secondary text-muted'"
                  @click="viewMode = 'split'"
                  title="Editor dan Live Pratinjau berdampingan secara real-time"
                >
                  <i class="ri-side-bar-line me-1"></i>
                  <span class="d-none d-xxl-inline">Berdampingan (Split)</span>
                  <span class="d-none d-xl-inline d-xxl-none">Split View</span>
                  <span class="d-inline d-xl-none">Split</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs rounded-pill px-2.5 py-1 fw-bold transition-all text-nowrap"
                  :class="viewMode === 'preview' ? 'btn-info shadow-sm text-white' : 'btn-ghost-secondary text-muted'"
                  @click="viewMode = 'preview'"
                  title="Simulasi formulir publik penuh layar"
                >
                  <i class="ri-eye-line me-1"></i>
                  <span class="d-none d-xxl-inline">Pratinjau Penuh</span>
                  <span class="d-inline d-xxl-none">Pratinjau</span>
                </button>
              </div>
            </div>

            <!-- Right: Quick Tools & Save Button -->
            <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
              <!-- Dedicated Button for Preset & Template Form -->
              <button 
                type="button" 
                class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1 fw-bold px-2.5 shadow-sm text-nowrap"
                @click="showPresetModal = true"
                title="Kelola & Kustomisasi Template / Preset Formulir"
              >
                <i class="ri-magic-line text-warning"></i>
                <span class="d-none d-xxl-inline">Template &amp; Preset</span>
                <span class="d-inline d-xxl-none">Preset</span>
              </button>

              <div class="btn-group position-relative flex-shrink-0" ref="optionsDropdownRef">
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-primary dropdown-toggle d-inline-flex align-items-center gap-1 fw-bold px-2.5 shadow-sm text-nowrap"
                  :class="{ active: showOptionsMenu }"
                  @click="showOptionsMenu = !showOptionsMenu"
                  :aria-expanded="showOptionsMenu"
                >
                  <i class="ri-more-2-fill"></i>
                  <span class="d-none d-xxl-inline">Opsi Formulir</span>
                  <span class="d-inline d-xxl-none">Opsi</span>
                </button>
                <ul 
                  v-show="showOptionsMenu"
                  class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-1 fs-12 show animate__animated animate__fadeIn animate__faster"
                  style="position: absolute; right: 0; top: 100%; margin-top: 4px; z-index: 1050; min-width: 220px;"
                >
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer" @click="showOptionsMenu = false; showSettingsModal = true">
                      <i class="ri-settings-3-line text-primary"></i> Pengaturan Judul &amp; Paket
                    </a>
                  </li>
                  <li>
                    <Link href="/admin/pengaturan/kebijakan-pendaftaran" class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer">
                      <i class="ri-user-settings-line text-warning"></i> Kebijakan Akses &amp; Login Pendaftar
                    </Link>
                  </li>
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer" @click="showOptionsMenu = false; showPresetModal = true">
                      <i class="ri-magic-line text-warning"></i> Terapkan Template Siap Pakai
                    </a>
                  </li>
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 cursor-pointer" @click="showOptionsMenu = false; showCopyModal = true">
                      <i class="ri-file-copy-line text-info"></i> Salin dari Cabang Lomba Lain
                    </a>
                  </li>
                  <li><hr class="dropdown-divider my-1"></li>
                  <li>
                    <a class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger cursor-pointer" @click="showOptionsMenu = false; resetForm()">
                      <i class="ri-refresh-line"></i> Kosongkan Seluruh Field
                    </a>
                  </li>
                </ul>
              </div>

              <button 
                type="button" 
                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 fw-bold px-2.5 px-md-3 shadow-sm save-btn text-nowrap"
                :disabled="isSaving"
                @click="saveFormFields"
                title="Simpan formulir (Ctrl + S)"
              >
                <i v-if="!isSaving" class="ri-save-line fs-14"></i>
                <span v-if="isSaving" class="spinner-border spinner-border-sm"></span>
                <span>Simpan<span class="d-none d-xxl-inline"> Formulir</span></span>
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
            <div class="card-header bg-transparent border-bottom p-3 d-flex flex-column gap-2.5">
              <div class="d-flex align-items-center justify-content-between">
                <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                  <i class="ri-tools-line text-primary fs-15"></i>
                  <span>Tambah Komponen</span>
                </h6>
                <span class="badge bg-primary-subtle text-primary fs-10 px-2 py-0.5 rounded-pill">Klik untuk Tambah</span>
              </div>

              <!-- Quick Search Input -->
              <div class="input-group input-group-sm toolbox-search-group">
                <span class="input-group-text ps-2.5">
                  <i class="ri-search-line fs-12"></i>
                </span>
                <input 
                  type="text" 
                  v-model="toolboxSearch"
                  class="form-control form-control-sm fs-12"
                  placeholder="Cari tipe input atau preset..."
                />
                <button 
                  v-if="toolboxSearch" 
                  class="btn btn-sm btn-clear-search" 
                  @click="toolboxSearch = ''"
                  type="button"
                >
                  <i class="ri-close-line"></i>
                </button>
              </div>

              <!-- Segmented Tabs for Toolbox -->
              <div class="d-flex gap-1 pt-0.5">
                <button 
                  type="button" 
                  class="btn btn-xs flex-fill rounded-2 fw-semibold py-1.5 transition-all text-nowrap"
                  :class="toolboxTab === 'types' ? 'btn-primary text-white shadow-sm' : 'btn-outline-secondary'"
                  @click="toolboxTab = 'types'"
                >
                  Tipe Input ({{ fieldTypes.length }})
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs flex-fill rounded-2 fw-semibold py-1.5 transition-all text-nowrap"
                  :class="toolboxTab === 'presets' ? 'btn-warning text-dark shadow-sm' : 'btn-outline-secondary'"
                  @click="toolboxTab = 'presets'"
                >
                  Preset ({{ quickPresets.length }})
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs flex-fill rounded-2 fw-semibold py-1.5 transition-all text-nowrap"
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
                  :key="ft.label || ft.type"
                  type="button" 
                  class="btn btn-sm btn-outline-light text-start p-2 rounded-2 border d-flex align-items-center justify-content-between transition-all hover-tool-card"
                  :class="{ 'border-warning-subtle bg-warning-subtle/10': ft.isRepeater, 'border-info-subtle bg-info-subtle/10': ft.badge === 'Wilayah' }"
                  @click="addField(ft)"
                  :title="`Klik untuk menambahkan pertanyaan bertipe ${ft.label}`"
                >
                  <div class="d-flex align-items-center gap-2 text-truncate">
                    <div 
                      class="tool-icon-box rounded-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                      :class="ft.isRepeater ? 'bg-warning-subtle text-warning' : (ft.badge === 'Wilayah' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary')"
                    >
                      <i :class="[ft.icon, ft.isRepeater ? 'text-warning' : (ft.badge === 'Wilayah' ? 'text-info' : 'text-primary')]" class="fs-15"></i>
                    </div>
                    <div class="text-truncate">
                      <div class="fs-12 fw-bold text-dark text-truncate lh-sm d-flex align-items-center gap-1">
                        <span>{{ ft.label }}</span>
                        <span v-if="ft.isRepeater" class="badge bg-warning text-dark fs-9 px-1 py-0 fw-bold">Baru</span>
                        <span v-else-if="ft.badge === 'Wilayah'" class="badge bg-info-subtle text-info fs-9 px-1 py-0 fw-bold">Wilayah</span>
                      </div>
                      <div class="text-muted fs-10 text-truncate lh-sm mt-0.5">{{ ft.desc }}</div>
                    </div>
                  </div>
                  <i class="ri-add-line fs-14 text-muted flex-shrink-0 ms-1"></i>
                </button>
              </div>

              <!-- Tab 2: Quick Presets -->
              <div v-show="toolboxTab === 'presets'" class="d-flex flex-column gap-1.5">
                <!-- Action Banner for Template & Preset Customization -->
                <div class="p-2.5 mb-1 rounded-3 bg-warning-subtle border border-warning-subtle d-flex flex-column gap-1.5 shadow-sm">
                  <div class="d-flex align-items-center justify-content-between">
                    <span class="fs-11 fw-bold text-warning-emphasis d-flex align-items-center gap-1">
                      <i class="ri-magic-line"></i> Template Preset Formulir
                    </span>
                    <span class="badge bg-warning text-dark fs-10 px-1.5 py-0.5 fw-bold font-mono">{{ presetList.length }} Template</span>
                  </div>
                  <div class="fs-10 text-muted">
                    Gunakan template siap pakai atau sesuaikan susunan field/kolom formulir.
                  </div>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-warning text-dark fw-bold d-flex align-items-center justify-content-center gap-1 py-1 mt-0.5 shadow-sm"
                    @click="showPresetModal = true"
                  >
                    <i class="ri-edit-line"></i>
                    <span>Kelola & Kustomisasi Preset</span>
                  </button>
                </div>

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
                    <div 
                      class="tool-icon-box rounded-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                      :class="qp.type === 'email' ? 'bg-info-subtle text-info' : (qp.type === 'number' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-warning')"
                    >
                      <i 
                        :class="qp.type === 'participant_repeater' ? 'ri-group-line text-warning' : (qp.type === 'file' ? 'ri-upload-cloud-2-line text-danger' : (qp.type === 'url' ? 'ri-link text-info' : (qp.type === 'email' ? 'ri-mail-line text-info' : (qp.type === 'radio' ? 'ri-radio-button-line text-success' : (qp.type === 'number' ? 'ri-hashtag text-secondary' : 'ri-flashlight-line text-warning')))))"
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
                <!-- Informational Banner for Mandatory Identity Fields -->
                <div class="p-2.5 mb-1 rounded-3 bg-primary-subtle border border-primary-subtle d-flex flex-column gap-1.5 shadow-sm">
                  <div class="d-flex align-items-center justify-content-between">
                    <span class="fs-11 fw-bold text-primary d-flex align-items-center gap-1">
                      <i class="ri-shield-check-line"></i> 8 Field Identitas & Paket Standar
                    </span>
                    <span class="badge bg-primary text-white fs-10 px-1.5 py-0.5 rounded-pill font-mono">Standar</span>
                  </div>
                  <div class="fs-10 text-muted">
                    Setiap formulir cabang lomba memuat: Nama Sekolah, Kecamatan, NPSN, Alamat Sekolah, Nama Pendamping, No. WA (hanya angka), Email, dan Kode Paket Komplit (All-In).
                  </div>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-primary text-white fw-bold d-flex align-items-center justify-content-center gap-1 py-1 mt-0.5 shadow-sm"
                    @click="restoreMandatoryFields"
                  >
                    <i class="ri-refresh-line"></i>
                    <span>Lengkapi 8 Field Standar</span>
                  </button>
                </div>

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
            <div class="card-header bg-transparent border-bottom py-3 px-3 px-sm-4 d-flex flex-column gap-2.5">
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
                    class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1"
                    @click="restoreMandatoryFields"
                    title="Lengkapi & sinkronkan 8 field identitas & paket standar (Sekolah, Kecamatan, NPSN, Alamat, Pendamping, WA, Email, Kode Paket)"
                  >
                    <i class="ri-shield-star-line text-primary"></i>
                    <span class="d-none d-sm-inline">Lengkapi 8 Field Standar</span>
                  </button>
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
                <div class="input-group input-group-sm toolbox-search-group">
                  <span class="input-group-text ps-2.5"><i class="ri-search-line fs-12"></i></span>
                  <input 
                    type="text" 
                    v-model="canvasSearch"
                    class="form-control form-control-sm fs-12"
                    placeholder="Saring pertanyaan pada kanvas..."
                  />
                  <button v-if="canvasSearch" class="btn btn-sm btn-clear-search" @click="canvasSearch = ''">
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
                    <i class="ri-community-line"></i> Identitas Sekolah
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5"
                    @click="addCorePreset(coreIdentityPresets.find(c => c.default_name === 'school_district') || coreIdentityPresets[1])"
                  >
                    <i class="ri-map-pin-2-line"></i> Kecamatan Sekolah
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5"
                    @click="addCorePreset(coreIdentityPresets.find(c => c.default_name === 'mentor_wa') || coreIdentityPresets[2])"
                  >
                    <i class="ri-whatsapp-line"></i> WhatsApp Kontak
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1.5"
                    @click="addCorePreset(coreIdentityPresets.find(c => c.type === 'email') || coreIdentityPresets[3])"
                  >
                    <i class="ri-mail-line"></i> Email Kontak
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
                          'bg-info-subtle text-info': field.type === 'textarea' || field.type === 'url' || field.type === 'email',
                          'bg-success-subtle text-success': field.type === 'select' || field.type === 'radio' || field.type === 'checkbox',
                          'bg-danger-subtle text-danger': field.type === 'file',
                          'bg-primary-subtle text-primary border border-primary-subtle': field.type === 'date',
                          'bg-secondary-subtle text-secondary': field.type === 'number'
                        }"
                      >
                        <i :class="field.type === 'participant_repeater' ? 'ri-group-line' : (field.type === 'file' ? 'ri-upload-cloud-2-line' : (field.type === 'url' ? 'ri-link' : (field.type === 'email' ? 'ri-mail-line' : (field.type === 'select' ? 'ri-arrow-down-s-line' : (field.type === 'number' ? 'ri-hashtag' : (field.type === 'date' ? 'ri-calendar-line' : 'ri-input-field'))))))"></i>
                        <span v-if="field.type === 'participant_repeater'">Multi-Peserta Dinamis <template v-if="field.max_items">(Maks. {{ field.max_items }})</template></span>
                        <span v-else>{{ field.type === 'date' ? 'Tanggal' : field.type }}</span>
                      </span>

                      <!-- Question Label Title -->
                      <span class="fs-13 fw-bold text-dark text-truncate field-title-text">
                        {{ field.label || '(Pertanyaan Tanpa Judul)' }}
                      </span>

                      <!-- Mandatory Standard Badge -->
                      <span 
                        v-if="isMandatoryIdentity(field)"
                        class="badge bg-primary-subtle text-primary border border-primary-subtle fs-10 px-2 py-0.5 rounded-pill d-inline-flex align-items-center gap-1"
                        :title="['school_npsn', 'allin_code'].includes(field.name) ? 'Field standar identitas & paket (opsional)' : 'Field identitas standar wajib untuk seluruh cabang lomba'"
                      >
                        <i class="ri-shield-check-line"></i>
                        <span>{{ ['school_npsn', 'allin_code'].includes(field.name) ? 'Standar (Opsional)' : 'Wajib Standar' }}</span>
                      </span>

                      <!-- Conditional Logic Active Badge -->
                      <span 
                        v-if="field.conditional_logic?.enabled"
                        class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10 d-inline-flex align-items-center gap-1"
                        title="Pertanyaan ini memiliki aturan logika bersyarat"
                      >
                        <i class="ri-flashlight-line"></i> Bersyarat
                      </span>

                      <!-- File Multi-Upload Badge -->
                      <span 
                        v-if="field.type === 'file' && (field.max_files || 1) > 1"
                        class="badge bg-danger-subtle text-danger border border-danger-subtle fs-10 d-inline-flex align-items-center gap-1"
                        title="Maksimal upload lebih dari 1 berkas"
                      >
                        <i class="ri-folders-line"></i> {{ field.max_files }} Berkas
                      </span>

                      <!-- Options Count Badge for Select / Radio / Checkbox -->
                      <span 
                        v-if="['select', 'radio', 'checkbox'].includes(field.type) && field.options?.length"
                        class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-10 d-inline-flex align-items-center gap-1"
                      >
                        <i class="ri-list-check"></i> {{ field.options.length }} Opsi
                      </span>

                    </div>

                    <!-- Right Controls: Required Switch, Reorder Arrows, Duplicate, Delete, Chevron -->
                    <div class="d-flex align-items-center gap-1.5 flex-shrink-0" @click.stop>
                      
                      <!-- Required Toggle Pill -->
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2 py-0.5 fw-semibold transition-all"
                        :class="field.required ? 'btn-danger-subtle text-danger border border-danger-subtle' : 'btn-light text-muted border'"
                        @click="isMandatoryIdentity(field) && !['school_npsn', 'allin_code'].includes(field.name) ? null : (field.required = !field.required)"
                        :disabled="isMandatoryIdentity(field) && !['school_npsn', 'allin_code'].includes(field.name)"
                        :title="isMandatoryIdentity(field) && !['school_npsn', 'allin_code'].includes(field.name) ? 'Field identitas ini wajib diisi sesuai standar lomba' : (field.required ? 'Pertanyaan ini wajib diisi oleh pendaftar' : 'Pertanyaan ini opsional')"
                      >
                        <i v-if="isMandatoryIdentity(field) && !['school_npsn', 'allin_code'].includes(field.name)" class="ri-lock-line me-0.5"></i>
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
                        <CustomSelect 
                          v-model="field.type" 
                          :options="fieldTypeOptions"
                          size="sm"
                          placeholder="Pilih tipe input..."
                        />
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
                            <span class="badge bg-warning text-dark fs-10 font-mono fw-bold">Otomatisasi Form</span>
                          </div>

                          <p class="text-muted fs-11 mb-2">
                            Atur batas kuota peserta per tim/kontingen untuk cabang lomba ini. Form biodata yang tampil di pendaftar secara otomatis menyesuaikan dengan jumlah siswa yang didaftarkan.
                          </p>

                          <!-- Sinkronisasi Otomatis dengan Manajemen Lomba -->
                          <div class="mb-3 p-2.5 rounded-3 border bg-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                              <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 28px; height: 28px;">
                                <i class="ri-refresh-line fs-14"></i>
                              </div>
                              <div>
                                <div class="fs-11 fw-bold text-dark d-flex align-items-center gap-1.5">
                                  <span>Pengaturan Cabang: {{ selectedCompetition?.title }}</span>
                                  <span class="badge" :class="selectedCompetition?.participant_type === 'tim' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary'" style="font-size: 9px;">
                                    {{ selectedCompetition?.participant_type === 'tim' ? 'Tim / Beregu' : 'Individu' }}
                                  </span>
                                </div>
                                <div class="fs-10 text-muted">
                                  Kapasitas: Maks. {{ selectedCompetition?.max_participants || 1 }} Siswa
                                  <template v-if="selectedCompetition?.participants_rule">
                                    • Aturan: <strong class="text-warning-emphasis">{{ selectedCompetition?.participants_rule }}</strong>
                                  </template>
                                </div>
                              </div>
                            </div>
                            <button 
                              type="button" 
                              class="btn btn-xs btn-primary d-flex align-items-center gap-1 fw-semibold py-1 px-2"
                              @click="syncWithCompetitionRules(field)"
                              title="Terapkan kuota peserta, anggota inti & cadangan dari Manajemen Lomba"
                            >
                              <i class="ri-sync-line"></i>
                              <span>Sinkronkan Pengaturan Lomba</span>
                            </button>
                          </div>

                          <div class="row g-3">
                            <!-- Min & Max Items & Inti/Cadangan -->
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

                              <!-- Inti vs Cadangan Distribution -->
                              <div class="row g-2 mb-2 pt-1 border-top">
                                <div class="col-6">
                                  <label class="form-label fs-10 fw-bold text-dark mb-1 d-flex align-items-center gap-1">
                                    <i class="ri-shield-user-line text-success"></i> Personel Inti:
                                  </label>
                                  <div class="input-group input-group-sm">
                                    <input 
                                      type="number" 
                                      v-model.number="field.main_members"
                                      class="form-control fs-11 font-mono"
                                      min="1"
                                      placeholder="Cth: 5"
                                    />
                                    <span class="input-group-text bg-light text-muted fs-10">Inti</span>
                                  </div>
                                </div>
                                <div class="col-6">
                                  <label class="form-label fs-10 fw-bold text-dark mb-1 d-flex align-items-center gap-1">
                                    <i class="ri-user-shared-line text-warning"></i> Personel Cadangan:
                                  </label>
                                  <div class="input-group input-group-sm">
                                    <input 
                                      type="number" 
                                      v-model.number="field.reserve_members"
                                      class="form-control fs-11 font-mono"
                                      min="0"
                                      placeholder="Cth: 1"
                                    />
                                    <span class="input-group-text bg-light text-muted fs-10">Cadangan</span>
                                  </div>
                                </div>
                                <div class="col-12">
                                  <span class="text-muted fs-10">Siswa #1 s/d #{{ field.main_members || field.max_items || 'N' }} akan dilabeli <strong>"Pemain Inti"</strong> di form, dan selanjutnya <strong>"Cadangan"</strong>.</span>
                                </div>
                              </div>

                              <!-- Quick preset chips -->
                              <div class="d-flex gap-1.5 mt-1.5 flex-wrap">
                                <span class="text-muted fs-10 w-100 mb-0.5">Preset Batas Kuota Cepat:</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = 2; field.main_members = 2; field.reserve_members = 0;">2 (Duo/Riset)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 3; field.max_items = 3; field.main_members = 3; field.reserve_members = 0;">3 (Regu LCC)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 5; field.max_items = 6; field.main_members = 5; field.reserve_members = 1;">6 (5 Inti + 1 Cadangan MLBB)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 6; field.max_items = 10; field.main_members = 6; field.reserve_members = 4;">10 (6 Inti + 4 Cadangan Voli)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = 10; field.main_members = 10; field.reserve_members = 0;">10 (Hadrah/Banjari)</button>
                                <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-1.5 fs-10" @click="field.min_items = 1; field.max_items = null; field.main_members = null; field.reserve_members = null;">Bebas (Tanpa Batas)</button>
                              </div>
                            </div>

                            <!-- Sub-fields toggles -->
                            <div class="col-md-6">
                              <label class="form-label fs-11 fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                                <span>Kolom Biodata Siswa yang Diaktifkan:</span>
                                <span class="text-muted fs-10 font-normal">Pilih yang dibutuhkan</span>
                              </label>
                              <div class="d-flex flex-column gap-2 p-2.5 bg-body rounded-3 border" style="max-height: 280px; overflow-y: auto;">
                                
                                <!-- Group 1: Data Identitas Dasar -->
                                <div>
                                  <div class="text-muted fs-10 fw-bold text-uppercase tracking-wider mb-1">1. Identitas & Akademik</div>
                                  <div class="d-flex flex-column gap-1">
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
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.grade.active" />
                                      <span class="text-dark">Kelas / Tingkat (Kelas 7, 8, 9)</span>
                                    </label>
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.birth_date.active" />
                                      <span class="text-dark">Tanggal Lahir Siswa</span>
                                    </label>
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.nisn.active" />
                                      <span class="text-dark">Nomor Induk Siswa Nasional (NISN)</span>
                                    </label>
                                  </div>
                                </div>

                                <!-- Group 2: ID & Kontak WhatsApp -->
                                <div class="pt-2 border-top">
                                  <div class="text-muted fs-10 fw-bold text-uppercase tracking-wider mb-1">2. ID & Kontak Komunikasi</div>
                                  <div class="d-flex flex-column gap-1.5">
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.custom_id.active" />
                                      <div class="d-flex flex-column">
                                        <span class="text-dark fw-medium">Kolom Ketik ID Siswa / No. Peserta / Akun Game</span>
                                        <span class="text-muted fs-10">Field input teks bebas untuk ID in-game, no punggung, dll.</span>
                                      </div>
                                      <span class="badge bg-secondary-subtle text-secondary fs-9 ms-auto">Field Text</span>
                                    </label>
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.wa_number.active" />
                                      <div class="d-flex flex-column">
                                        <span class="text-dark fw-medium">Kolom Nomor WhatsApp Siswa</span>
                                        <span class="text-muted fs-10">Field input angka no. WA untuk seluruh personil/siswa</span>
                                      </div>
                                      <span class="badge bg-success-subtle text-success fs-9 ms-auto">Field Angka</span>
                                    </label>
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-start gap-2 cursor-pointer fs-11 bg-warning-subtle/25 p-1.5 rounded border border-warning-subtle">
                                      <input type="checkbox" class="form-check-input text-warning mt-0.5" v-model="field.sub_fields.captain_option.active" />
                                      <div class="d-flex flex-column">
                                        <span class="text-dark fw-bold d-flex align-items-center gap-1">
                                          <i class="ri-vip-crown-line text-warning"></i> Tandai Kapten Tim & Nomor WA Khusus Kapten
                                        </span>
                                        <span class="text-muted fs-10">Pendaftar dapat menandai 1 siswa sebagai kapten, dan kolom No WA Kapten otomatis muncul pada siswa terpilih.</span>
                                      </div>
                                    </label>
                                  </div>
                                </div>

                                <!-- Group 3: Foto & Media Siswa -->
                                <div class="pt-2 border-top">
                                  <div class="text-muted fs-10 fw-bold text-uppercase tracking-wider mb-1">3. Upload Berkas & Foto Siswa</div>
                                  <div class="d-flex flex-column gap-1.5">
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.photo_formal.active" />
                                      <div class="d-flex flex-column">
                                        <span class="text-dark fw-medium">Upload Pas Foto Siswa Formal</span>
                                        <span class="text-muted fs-10">Upload foto resmi 3x4 atau berseragam (1 file foto)</span>
                                      </div>
                                      <span class="badge bg-info-subtle text-info fs-9 ms-auto">Formal</span>
                                    </label>
                                    <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11">
                                      <input type="checkbox" class="form-check-input" v-model="field.sub_fields.photo_freestyle.active" />
                                      <div class="d-flex flex-column">
                                        <span class="text-dark fw-medium">Upload Foto Siswa Pose Gaya Bebas</span>
                                        <span class="text-muted fs-10">Mendukung upload lebih dari 1 file foto sekaligus (Multi-Foto)</span>
                                      </div>
                                      <span class="badge bg-success-subtle text-success fs-9 ms-auto">Bisa >1 Foto</span>
                                    </label>
                                  </div>
                                </div>

                                <!-- Group 4: Kolom / Field Kustom Tambahan Biodata Siswa -->
                                <div class="pt-2.5 border-top">
                                  <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                    <div>
                                      <div class="text-dark fs-11 fw-bold d-flex align-items-center gap-1.5">
                                        <i class="ri-add-box-line text-primary fs-14"></i>
                                        <span>4. Kolom Tambahan Biodata Siswa (Kustomisasi Kolom Bebas)</span>
                                      </div>
                                      <span class="text-muted fs-10">Tambah kolom khusus untuk tiap biodata siswa (misal: Ukuran Kaos/Jersey, Akun Instagram, Role Game, dll.)</span>
                                    </div>
                                    <button 
                                      type="button" 
                                      class="btn btn-xs btn-outline-primary d-flex align-items-center gap-1 py-1 px-2.5 fw-semibold"
                                      @click="addCustomSubField(field)"
                                    >
                                      <i class="ri-add-line"></i>
                                      <span>Tambah Kolom Biodata</span>
                                    </button>
                                  </div>

                                  <div v-if="!field.sub_fields?.custom_columns || field.sub_fields.custom_columns.length === 0" class="p-2 rounded bg-light-subtle text-muted fs-10 fst-italic">
                                    Belum ada kolom kustom tambahan. Klik <strong>"+ Tambah Kolom Biodata"</strong> di atas jika Anda ingin menambah kolom isian siswa lainnya.
                                  </div>
                                  <div v-else class="d-flex flex-column gap-2">
                                    <div 
                                      v-for="(col, colIdx) in field.sub_fields.custom_columns" 
                                      :key="col.id || colIdx"
                                      class="p-2 rounded border bg-light-subtle d-flex align-items-center gap-2"
                                    >
                                      <span class="badge bg-secondary-subtle text-secondary font-mono fs-9">#{{ colIdx + 1 }}</span>
                                      <div class="flex-grow-1 row g-2 align-items-center">
                                        <div class="col-md-5">
                                          <input 
                                            type="text" 
                                            v-model="col.label" 
                                            class="form-control form-control-sm fs-11" 
                                            placeholder="Nama Kolom (contoh: Ukuran Baju)"
                                          />
                                        </div>
                                        <div class="col-md-4">
                                          <CustomSelect 
                                            v-model="col.type" 
                                            :options="columnTypeOptions"
                                            size="xs"
                                            placeholder="Tipe..."
                                          />
                                        </div>
                                        <div class="col-md-3 d-flex align-items-center gap-2">
                                          <label class="form-check form-check-sm mb-0 cursor-pointer fs-10 d-flex align-items-center gap-1">
                                            <input type="checkbox" v-model="col.required" class="form-check-input" />
                                            <span>Wajib</span>
                                          </label>
                                        </div>
                                      </div>
                                      <button 
                                        type="button" 
                                        class="btn btn-xs btn-outline-danger p-1" 
                                        @click="removeCustomSubField(field, colIdx)"
                                        title="Hapus kolom ini"
                                      >
                                        <i class="ri-delete-bin-line"></i>
                                      </button>
                                    </div>
                                  </div>
                                </div>

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
                                  <li><hr class="dropdown-divider my-1"></li>
                                  <li><a class="dropdown-item cursor-pointer text-primary fw-medium" @click="applyOptionPreset(field, 'kecamatan_jember')"><i class="ri-map-pin-line me-1 text-primary"></i> 31 Kecamatan Jember</a></li>
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
                          <div class="d-flex flex-column gap-1.5" style="max-height: 280px; overflow-y: auto; padding-right: 4px;">
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
                            <div class="col-md-5">
                              <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <label class="form-label fs-11 fw-bold text-dark mb-0">
                                  <i class="ri-file-shield-2-line text-danger me-1"></i> Format Diizinkan:
                                </label>
                                <div class="d-flex gap-1">
                                  <button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1 text-[10px]" @click="setFilePreset(field, 'pdf')">PDF</button>
                                  <button type="button" class="btn btn-xs btn-ghost-danger py-0 px-1 text-[10px]" @click="setFilePreset(field, 'images')">Foto</button>
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

                            <div class="col-md-3">
                              <label class="form-label fs-11 fw-bold text-dark mb-1">
                                <i class="ri-hard-drive-2-line text-danger me-1"></i> Ukuran Maksimal
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

                            <div class="col-md-4">
                              <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label fs-11 fw-bold text-dark mb-0">
                                  <i class="ri-folders-line text-danger me-1"></i> Batas Jumlah Berkas
                                </label>
                                <span class="badge bg-danger-subtle text-danger fs-10 px-1.5 py-0.5">
                                  {{ (field.max_files || 1) > 1 ? `${field.max_files} Berkas (Multi)` : '1 Berkas (Tunggal)' }}
                                </span>
                              </div>
                              <div class="input-group input-group-sm">
                                <input 
                                  type="number" 
                                  v-model.number="field.max_files"
                                  class="form-control fs-12 font-mono"
                                  min="1"
                                  max="20"
                                  placeholder="1"
                                />
                                <span class="input-group-text bg-light-subtle text-muted fs-11">Berkas</span>
                              </div>
                              <div class="d-flex flex-wrap gap-1 mt-1">
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" :class="{'btn-danger text-white': !field.max_files || field.max_files === 1}" @click="field.max_files = 1">1 File</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" :class="{'btn-danger text-white': field.max_files === 2}" @click="field.max_files = 2">2</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" :class="{'btn-danger text-white': field.max_files === 3}" @click="field.max_files = 3">3</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" :class="{'btn-danger text-white': field.max_files === 5}" @click="field.max_files = 5">5</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" :class="{'btn-danger text-white': field.max_files === 10}" @click="field.max_files = 10">10</button>
                              </div>
                              <small class="text-muted fs-10 d-block mt-1">
                                {{ (field.max_files || 1) > 1 ? 'Peserta dapat mengunggah hingga ' + field.max_files + ' berkas sekaligus.' : 'Hanya 1 berkas per pendaftaran.' }}
                              </small>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Date Picker Settings Zone -->
                      <div v-if="field.type === 'date'" class="col-12">
                        <div class="p-3 bg-primary-subtle/20 rounded-3 border border-primary-subtle">
                          <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fs-11 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                              <i class="ri-calendar-2-line text-primary fs-14"></i>
                              <span>Pengaturan Kalender & Format Tanggal (Flatpickr Baku)</span>
                            </label>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-10 font-mono">Rule 4: dd/mm/yyyy</span>
                          </div>

                          <div class="row g-2.5">
                            <div class="col-md-4">
                              <label class="form-label fs-11 text-muted mb-1">
                                Batas Tanggal Minimal (Min Date)
                              </label>
                              <IndoDatePicker 
                                v-model="field.min_date"
                                placeholder="dd/mm/yyyy"
                                inputClass="form-control form-control-sm fs-12 bg-body"
                              />
                              <div class="d-flex gap-1 mt-1">
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.min_date = '01/01/2000'">Thn 2000</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.min_date = '01/01/2010'">Thn 2010</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.min_date = ''">Kosongkan</button>
                              </div>
                            </div>

                            <div class="col-md-4">
                              <label class="form-label fs-11 text-muted mb-1">
                                Batas Tanggal Maksimal (Max Date)
                              </label>
                              <IndoDatePicker 
                                v-model="field.max_date"
                                placeholder="dd/mm/yyyy"
                                inputClass="form-control form-control-sm fs-12 bg-body"
                              />
                              <div class="d-flex gap-1 mt-1">
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.max_date = '31/12/2026'">Akhir 2026</button>
                                <button type="button" class="btn btn-xs btn-light border py-0 px-1 text-[10px]" @click="field.max_date = ''">Kosongkan</button>
                              </div>
                            </div>

                            <div class="col-md-4">
                              <label class="form-label fs-11 text-muted mb-1">
                                Nilai Standar Awal
                              </label>
                              <CustomSelect 
                                v-model="field.date_default"
                                :options="[
                                  { value: '', label: 'Biarkan Kosong (Wajib Dipilih Pendaftar)' },
                                  { value: 'today', label: 'Otomatis Tanggal Hari Ini' }
                                ]"
                                size="sm"
                                placeholder="Pilih default..."
                              />
                              <small class="text-muted fs-10 d-block mt-1">
                                Kalender otomatis tervalidasi dengan Flatpickr lokal offline.
                              </small>
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
                                <CustomSelect 
                                  v-model="field.conditional_logic.depend_on_field" 
                                  :options="getConditionalTargetOptions(field.id)"
                                  size="xs"
                                  placeholder="-- Pilih Pertanyaan Target --"
                                />
                              </div>

                              <div class="col-sm-3">
                                <label class="form-label fs-10 text-muted mb-1">Operator Kondisi:</label>
                                <CustomSelect 
                                  v-model="field.conditional_logic.operator" 
                                  :options="conditionOperatorOptions"
                                  size="xs"
                                />
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
                <span class="badge bg-warning-subtle text-warning-emphasis fw-semibold px-2 py-0.5 rounded-pill">{{ fields.filter(f => f.conditional_logic?.enabled).length }} Logika Bersyarat</span>
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
            <div class="card-header py-2.5 px-3 d-flex align-items-center justify-content-between border-bottom">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-emerald-500 text-slate-950 fw-bold fs-10">LIVE PREVIEW</span>
                <span class="fs-12 fw-semibold preview-header-title">Formulir Publik &amp; Logika</span>
              </div>

              <!-- Device Switcher -->
              <div class="btn-group btn-group-xs" role="group">
                <button 
                  type="button" 
                  class="btn btn-xs preview-toggle-btn"
                  :class="previewDevice === 'desktop' ? 'active' : ''"
                  @click="previewDevice = 'desktop'"
                  title="Simulasi Desktop"
                >
                  <i class="ri-macbook-line"></i>
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs preview-toggle-btn"
                  :class="previewDevice === 'mobile' ? 'active' : ''"
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
                                <span class="text-[11px] font-bold text-amber-300 d-flex align-items-center gap-1.5 flex-wrap">
                                  <i class="ri-user-line text-emerald-400"></i>
                                  <span>Biodata Siswa #{{ pIdx }}</span>
                                  
                                  <!-- Inti vs Cadangan Badge -->
                                  <span v-if="field.main_members" class="badge text-[9px] py-0 d-inline-flex align-items-center gap-1 font-bold" :class="pIdx <= (field.main_members || 1) ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'">
                                    <i :class="pIdx <= (field.main_members || 1) ? 'ri-shield-user-line' : 'ri-user-shared-line'"></i>
                                    {{ pIdx <= (field.main_members || 1) ? `Inti #${pIdx}` : `Cadangan #${pIdx - (field.main_members || 1)}` }}
                                  </span>

                                  <span v-if="previewValues[field.name + '_captain'] === pIdx" class="badge bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[9px] py-0 d-inline-flex align-items-center gap-1 font-bold">
                                    <i class="ri-vip-crown-fill text-amber-400"></i> KAPTEN TIM
                                  </span>
                                </span>
                                <div class="d-flex align-items-center gap-1.5">
                                  <label 
                                    v-if="field.sub_fields?.captain_option?.active" 
                                    class="flex items-center gap-1 px-1.5 py-0.5 rounded cursor-pointer text-[9px] border transition-all"
                                    :class="previewValues[field.name + '_captain'] === pIdx ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:border-slate-700'"
                                    title="Tandai siswa ini sebagai kapten tim"
                                  >
                                    <input type="radio" :name="`cap_${field.name}`" :value="pIdx" v-model="previewValues[field.name + '_captain']" class="text-amber-500" />
                                    <span>⭐ Kapten</span>
                                  </label>
                                  <span class="badge bg-slate-900 text-slate-400 text-[9px] font-mono">
                                    Peserta {{ pIdx }} dari {{ previewValues[field.name + '_count'] || 1 }}
                                  </span>
                                </div>
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
                                    <CustomSelect 
                                      v-model="previewValues[field.name + '_grade_' + pIdx]"
                                      :options="['Kelas 7', 'Kelas 8', 'Kelas 9']"
                                      placeholder="Kelas 9"
                                      size="xs"
                                      variant="dark-slate"
                                    />
                                  </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                  <!-- Tanggal Lahir (dd/mm/yyyy Flatpickr) -->
                                  <div v-if="field.sub_fields?.birth_date?.active !== false">
                                    <label class="block text-[10px] text-slate-400 mb-0.5">Tgl Lahir (dd/mm/yyyy)</label>
                                    <IndoDatePicker 
                                      v-model="previewValues[field.name + '_birth_' + pIdx]"
                                      placeholder="dd/mm/yyyy"
                                      inputClass="w-full px-2 py-1 rounded bg-slate-900 border border-slate-800 text-white text-[11px]"
                                      iconClass="ri-calendar-line text-amber-400 text-xs"
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

                                <!-- ID Siswa (Field Text) -->
                                <div v-if="field.sub_fields?.custom_id?.active">
                                  <label class="block text-[10px] text-slate-400 mb-0.5 d-flex align-items-center justify-content-between">
                                    <span>{{ field.sub_fields.custom_id.label || 'ID Siswa / No. Identitas' }}</span>
                                    <span class="badge bg-slate-900 text-slate-400 text-[9px] py-0 border border-slate-800 font-mono">Field Text</span>
                                  </label>
                                  <input 
                                    type="text" 
                                    placeholder="Contoh: ID-0912 / User ID Game" 
                                    class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                                  />
                                </div>

                                <!-- Nomor WhatsApp Siswa (Field Angka) -->
                                <div v-if="field.sub_fields?.wa_number?.active">
                                  <label class="block text-[10px] text-slate-400 mb-0.5 d-flex align-items-center gap-1">
                                    <i class="ri-whatsapp-line text-emerald-400"></i>
                                    <span>Nomor WhatsApp Siswa</span>
                                    <span class="badge bg-slate-900 text-emerald-400 text-[9px] py-0 border border-slate-800 font-mono">Field Angka</span>
                                  </label>
                                  <input 
                                    type="tel" 
                                    placeholder="08xxxxxxxxxx" 
                                    class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                                  />
                                </div>

                                <!-- Nomor WA Khusus Kapten Tim (Hanya jika siswa ditandai sebagai Kapten) -->
                                <div v-if="field.sub_fields?.captain_option?.active && previewValues[field.name + '_captain'] === pIdx" class="p-2 rounded-lg bg-amber-500/10 border border-amber-500/30">
                                  <label class="block text-[10px] text-amber-300 font-bold mb-1 d-flex align-items-center gap-1">
                                    <i class="ri-whatsapp-fill text-emerald-400"></i>
                                    <span>Nomor WhatsApp Kapten Tim (Field Angka) *</span>
                                  </label>
                                  <input 
                                    type="tel" 
                                    placeholder="08xxxxxxxxxx (WA Kapten untuk koordinasi)" 
                                    class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-amber-500/40 text-white text-xs font-mono placeholder-slate-500 focus:outline-none focus:border-amber-400"
                                  />
                                  <div class="text-[9px] text-amber-300/80 mt-1">
                                    ⭐ Nomor WA kapten otomatis aktif karena siswa ini ditandai sebagai Kapten Tim.
                                  </div>
                                </div>

                                <!-- Upload Foto Siswa Formal (Pas Foto 1 File) -->
                                <div v-if="field.sub_fields?.photo_formal?.active">
                                  <label class="block text-[10px] text-slate-400 mb-0.5 d-flex align-items-center gap-1">
                                    <i class="ri-camera-line text-sky-400"></i>
                                    <span>Upload Pas Foto Formal Siswa</span>
                                    <span class="badge bg-sky-500/20 text-sky-300 text-[9px] py-0 border border-sky-500/30 ms-auto">Formal (1 File)</span>
                                  </label>
                                  <div class="border border-dashed border-slate-800 hover:border-sky-500/50 rounded-lg p-2 text-center bg-slate-900/50 cursor-pointer">
                                    <i class="ri-image-add-line text-sky-400 text-sm"></i>
                                    <div class="text-[10px] text-slate-300 font-medium mt-0.5">Pilih Pas Foto Formal (JPG / PNG)</div>
                                    <div class="text-[9px] text-slate-500">Maks. 3 MB</div>
                                  </div>
                                </div>

                                <!-- Upload Foto Siswa Pose Gaya Bebas (Bisa >1 Foto) -->
                                <div v-if="field.sub_fields?.photo_freestyle?.active">
                                  <label class="block text-[10px] text-slate-400 mb-0.5 d-flex align-items-center gap-1">
                                    <i class="ri-gallery-upload-line text-emerald-400"></i>
                                    <span>Upload Foto Pose Gaya Bebas</span>
                                    <span class="badge bg-emerald-500/20 text-emerald-300 text-[9px] py-0 border border-emerald-500/30 ms-auto">Bisa >1 Foto</span>
                                  </label>
                                  <div class="border border-dashed border-slate-800 hover:border-emerald-500/50 rounded-lg p-2 text-center bg-slate-900/50 cursor-pointer">
                                    <i class="ri-images-line text-emerald-400 text-sm"></i>
                                    <div class="text-[10px] text-slate-300 font-medium mt-0.5">Pilih Satu atau Beberapa Foto Pose Bebas</div>
                                    <div class="text-[9px] text-slate-500">Mendukung upload lebih dari 1 file foto sekaligus</div>
                                  </div>
                                </div>

                                <!-- Kolom Kustom Tambahan Biodata Siswa -->
                                <div v-for="col in (field.sub_fields?.custom_columns || [])" :key="col.id || col.label">
                                  <label class="block text-[10px] text-slate-400 mb-0.5 d-flex align-items-center justify-content-between">
                                    <span>{{ col.label || 'Kolom Tambahan' }}</span>
                                    <span v-if="col.required" class="text-rose-400 ms-1">*</span>
                                  </label>
                                  <IndoDatePicker 
                                    v-if="col.type === 'date'"
                                    v-model="previewValues[field.name + '_' + (col.id || col.label) + '_' + pIdx]"
                                    placeholder="dd/mm/yyyy"
                                    inputClass="w-full px-2 py-1 rounded bg-slate-900 border border-slate-800 text-white text-[11px]"
                                    iconClass="ri-calendar-line text-amber-400 text-xs"
                                  />
                                  <CustomSelect 
                                    v-else-if="col.type === 'select'"
                                    v-model="previewValues[field.name + '_' + (col.id || col.label) + '_' + pIdx]"
                                    :options="col.options || ['Opsi 1', 'Opsi 2']"
                                    size="xs"
                                    variant="dark-slate"
                                    placeholder="Pilih opsi..."
                                  />
                                  <input 
                                    v-else
                                    :type="col.type === 'number' ? 'number' : 'text'" 
                                    v-model="previewValues[field.name + '_' + (col.id || col.label) + '_' + pIdx]"
                                    :placeholder="`Masukkan ${col.label || 'data'}`"
                                    class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                                  />
                                </div>
                              </div>
                            </div>
                          </div>

                        </div>

                        <!-- STANDARD INPUT TYPES -->
                        <div v-else-if="['text', 'number', 'url', 'email'].includes(field.type)">
                          <input 
                            :type="field.type" 
                            :inputmode="field.type === 'number' ? 'numeric' : (field.type === 'email' ? 'email' : undefined)"
                            v-model="previewValues[field.name]"
                            @input="field.type === 'number' ? (previewValues[field.name] = String($event.target.value).replace(/\D/g, '')) : null"
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
                          <CustomSelect 
                            v-model="previewValues[field.name]"
                            :options="field.options || []"
                            :placeholder="`-- Pilih ${field.label} --`"
                            size="xs"
                            variant="dark-slate"
                          />
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
                            <div class="text-slate-300 font-semibold text-[11px]">
                              Unggah Berkas {{ (field.max_files || 1) > 1 ? `(Bisa s.d ${field.max_files} berkas)` : '' }}
                            </div>
                            <div class="text-[10px] text-slate-500 mt-0.5">
                              {{ field.allowed_extensions?.join(', ') || 'Semua format' }} • Max {{ field.max_size_mb || 5 }} MB • {{ (field.max_files || 1) > 1 ? `Maks. ${field.max_files} file` : '1 file' }}
                            </div>
                          </div>
                        </div>

                        <div v-else-if="field.type === 'date'">
                          <IndoDatePicker 
                            v-model="previewValues[field.name]"
                            :min-date="field.min_date"
                            :max-date="field.max_date"
                            placeholder="dd/mm/yyyy"
                            inputClass="w-full px-3 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                            iconClass="ri-calendar-line text-amber-400 text-xs"
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
        <div class="card-header py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-emerald-500 text-slate-950 fw-bold fs-11">SIMULASI FORMULIR LENGKAP & LOGIKA</span>
            <h5 class="fs-15 fw-bold preview-header-title mb-0">{{ selectedCompetition?.title }}</h5>
          </div>

          <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
              <button 
                type="button" 
                class="btn preview-toggle-btn px-2.5 py-1"
                :class="previewDevice === 'desktop' ? 'active' : ''"
                @click="previewDevice = 'desktop'"
              >
                <i class="ri-macbook-line me-1"></i> Desktop
              </button>
              <button 
                type="button" 
                class="btn preview-toggle-btn px-2.5 py-1"
                :class="previewDevice === 'mobile' ? 'active' : ''"
                @click="previewDevice = 'mobile'"
              >
                <i class="ri-smartphone-line me-1"></i> Mobile
              </button>
            </div>

            <button 
              type="button" 
              class="btn btn-sm preview-back-btn"
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
                            <span class="text-xs font-bold text-amber-300 d-flex align-items-center gap-1.5 flex-wrap">
                              <i class="ri-user-line text-emerald-400"></i>
                              <span>Biodata Peserta Siswa #{{ pIdx }}</span>

                              <!-- Inti vs Cadangan Badge in Full Preview -->
                              <span v-if="field.main_members" class="badge text-[10px] py-0.5 d-inline-flex align-items-center gap-1 font-bold" :class="pIdx <= (field.main_members || 1) ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40'">
                                <i :class="pIdx <= (field.main_members || 1) ? 'ri-shield-user-line' : 'ri-user-shared-line'"></i>
                                {{ pIdx <= (field.main_members || 1) ? `Pemain Inti #${pIdx}` : `Cadangan #${pIdx - (field.main_members || 1)}` }}
                              </span>

                              <span v-if="previewValues[field.name + '_captain'] === pIdx" class="badge bg-amber-500/20 text-amber-300 border border-amber-500/40 text-[10px] py-0.5 d-inline-flex align-items-center gap-1 font-bold">
                                <i class="ri-vip-crown-fill text-amber-400"></i> KAPTEN TIM
                              </span>
                            </span>
                            <div class="d-flex align-items-center gap-2">
                              <label 
                                v-if="field.sub_fields?.captain_option?.active" 
                                class="flex items-center gap-1.5 px-2 py-0.5 rounded-lg cursor-pointer text-xs border transition-all"
                                :class="previewValues[field.name + '_captain'] === pIdx ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-bold' : 'bg-slate-900 text-slate-400 border-slate-800 hover:border-slate-700'"
                                title="Tandai siswa ini sebagai kapten tim"
                              >
                                <input type="radio" :name="`full_cap_${field.name}`" :value="pIdx" v-model="previewValues[field.name + '_captain']" class="text-amber-500" />
                                <span>⭐ Kapten Tim</span>
                              </label>
                              <span class="badge bg-slate-900 text-slate-400 text-[10px] font-mono">
                                Peserta {{ pIdx }} dari {{ previewValues[field.name + '_count'] || 1 }}
                              </span>
                            </div>
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
                              <CustomSelect 
                                v-model="previewValues[field.name + '_grade_' + pIdx]"
                                :options="['Kelas 7 SMP/MTs', 'Kelas 8 SMP/MTs', 'Kelas 9 SMP/MTs']"
                                placeholder="Kelas 9 SMP/MTs"
                                size="sm"
                                variant="dark-slate"
                              />
                            </div>

                            <div v-if="field.sub_fields?.birth_date?.active !== false">
                              <label class="block text-[11px] text-slate-400 mb-1">Tanggal Lahir (dd/mm/yyyy)</label>
                              <IndoDatePicker 
                                v-model="previewValues[field.name + '_birth_' + pIdx]"
                                placeholder="dd/mm/yyyy"
                                inputClass="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                                iconClass="ri-calendar-line text-amber-400 text-sm"
                              />
                            </div>

                            <div v-if="field.sub_fields?.nisn?.active !== false">
                              <label class="block text-[11px] text-slate-400 mb-1">NISN</label>
                              <input 
                                type="text" 
                                placeholder="0081234567"
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                              />
                            </div>

                            <!-- ID Siswa (Field Text) -->
                            <div v-if="field.sub_fields?.custom_id?.active" class="sm:col-span-2">
                              <label class="block text-[11px] text-slate-400 mb-1 d-flex align-items-center justify-content-between">
                                <span>{{ field.sub_fields.custom_id.label || 'ID Siswa / No. Identitas Khusus' }}</span>
                                <span class="badge bg-slate-900 text-slate-400 text-[10px] py-0 border border-slate-800 font-mono">Field Text</span>
                              </label>
                              <input 
                                type="text" 
                                placeholder="Contoh: ID-0912 / User ID Game" 
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                              />
                            </div>

                            <!-- Nomor WhatsApp Siswa (Field Angka) -->
                            <div v-if="field.sub_fields?.wa_number?.active" class="sm:col-span-2">
                              <label class="block text-[11px] text-slate-400 mb-1 d-flex align-items-center gap-1.5">
                                <i class="ri-whatsapp-line text-emerald-400"></i>
                                <span>Nomor WhatsApp Siswa</span>
                                <span class="badge bg-slate-900 text-emerald-400 text-[10px] py-0 border border-slate-800 font-mono">Field Angka</span>
                              </label>
                              <input 
                                type="tel" 
                                placeholder="08xxxxxxxxxx" 
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                              />
                            </div>

                            <!-- Nomor WA Khusus Kapten Tim -->
                            <div v-if="field.sub_fields?.captain_option?.active && previewValues[field.name + '_captain'] === pIdx" class="sm:col-span-2 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30">
                              <label class="block text-xs text-amber-300 font-bold mb-1 d-flex align-items-center gap-1.5">
                                <i class="ri-whatsapp-fill text-emerald-400"></i>
                                <span>Nomor WhatsApp Kapten Tim (Field Angka) *</span>
                              </label>
                              <input 
                                type="tel" 
                                placeholder="08xxxxxxxxxx (Nomor WA Kapten untuk grup koordinasi panitia)" 
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-amber-500/40 text-white text-xs font-mono placeholder-slate-500 focus:outline-none focus:border-amber-400"
                              />
                              <div class="text-[11px] text-amber-300/80 mt-1.5">
                                ⭐ Nomor WA kapten otomatis aktif karena siswa ini ditandai sebagai Kapten Tim.
                              </div>
                            </div>

                            <!-- Upload Foto Siswa Formal (Pas Foto 1 File) -->
                            <div v-if="field.sub_fields?.photo_formal?.active" class="sm:col-span-1">
                              <label class="block text-[11px] text-slate-400 mb-1 d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-1">
                                  <i class="ri-camera-line text-sky-400"></i> Foto Formal Siswa
                                </span>
                                <span class="badge bg-sky-500/20 text-sky-300 text-[10px] py-0.5 border border-sky-500/30">Pas Foto (1 File)</span>
                              </label>
                              <div class="border border-dashed border-slate-800 hover:border-sky-500/50 rounded-xl p-3 text-center bg-slate-900/60 cursor-pointer">
                                <i class="ri-image-add-line text-sky-400 text-lg"></i>
                                <div class="text-xs text-slate-300 font-medium mt-1">Upload Pas Foto Formal</div>
                                <div class="text-[10px] text-slate-500">JPG/PNG, Maks. 3 MB</div>
                              </div>
                            </div>

                            <!-- Upload Foto Siswa Pose Gaya Bebas (Bisa >1 Foto) -->
                            <div v-if="field.sub_fields?.photo_freestyle?.active" class="sm:col-span-1">
                              <label class="block text-[11px] text-slate-400 mb-1 d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center gap-1">
                                  <i class="ri-gallery-upload-line text-emerald-400"></i> Foto Pose Gaya Bebas
                                </span>
                                <span class="badge bg-emerald-500/20 text-emerald-300 text-[10px] py-0.5 border border-emerald-500/30">Bisa >1 Foto</span>
                              </label>
                              <div class="border border-dashed border-slate-800 hover:border-emerald-500/50 rounded-xl p-3 text-center bg-slate-900/60 cursor-pointer">
                                <i class="ri-images-line text-emerald-400 text-lg"></i>
                                <div class="text-xs text-slate-300 font-medium mt-1">Upload Foto Pose Bebas</div>
                                <div class="text-[10px] text-slate-500">Mendukung upload lebih dari 1 foto</div>
                              </div>
                            </div>

                            <!-- Kolom Kustom Tambahan Biodata Siswa -->
                            <div v-for="col in (field.sub_fields?.custom_columns || [])" :key="col.id || col.label" class="sm:col-span-1">
                              <label class="block text-[11px] text-slate-400 mb-1 d-flex align-items-center justify-content-between">
                                <span>{{ col.label || 'Kolom Tambahan' }}</span>
                                <span v-if="col.required" class="text-rose-400">*</span>
                              </label>
                              <IndoDatePicker 
                                v-if="col.type === 'date'"
                                v-model="previewValues[field.name + '_' + (col.id || col.label) + '_' + pIdx]"
                                placeholder="dd/mm/yyyy"
                                inputClass="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                                iconClass="ri-calendar-line text-amber-400 text-sm"
                              />
                              <CustomSelect 
                                v-else-if="col.type === 'select'"
                                v-model="previewValues[field.name + '_' + (col.id || col.label) + '_' + pIdx]"
                                :options="col.options || ['Opsi 1', 'Opsi 2']"
                                size="sm"
                                variant="dark-slate"
                                placeholder="Pilih opsi..."
                              />
                              <input 
                                v-else
                                :type="col.type === 'number' ? 'number' : 'text'" 
                                v-model="previewValues[field.name + '_' + (col.id || col.label) + '_' + pIdx]"
                                :placeholder="`Masukkan ${col.label || 'data'}...`" 
                                class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs font-mono"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- STANDARD FIELD TYPES IN FULL PREVIEW -->
                    <div v-else-if="['text', 'number', 'url', 'email'].includes(field.type)">
                      <input 
                        :type="field.type" 
                        :inputmode="field.type === 'number' ? 'numeric' : (field.type === 'email' ? 'email' : undefined)"
                        v-model="previewValues[field.name]"
                        @input="field.type === 'number' ? (previewValues[field.name] = String($event.target.value).replace(/\D/g, '')) : null"
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
                      <CustomSelect 
                        v-model="previewValues[field.name]"
                        :options="field.options || []"
                        :placeholder="`-- Pilih Opsi ${field.label} --`"
                        size="sm"
                        variant="dark-slate"
                      />
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
                        <div class="text-slate-300 font-semibold text-xs">
                          Klik atau seret berkas ke sini {{ (field.max_files || 1) > 1 ? `(Maks. ${field.max_files} berkas)` : '' }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                          Format: {{ field.allowed_extensions?.join(', ') || 'Semua berkas' }} • Maksimal {{ field.max_size_mb || 5 }} MB per berkas • {{ (field.max_files || 1) > 1 ? `Hingga ${field.max_files} berkas` : '1 berkas tunggal' }}
                        </div>
                      </div>
                    </div>

                    <div v-else-if="field.type === 'date'">
                      <IndoDatePicker 
                        v-model="previewValues[field.name]"
                        :min-date="field.min_date"
                        :max-date="field.max_date"
                        placeholder="dd/mm/yyyy"
                        inputClass="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                        iconClass="ri-calendar-line text-amber-400 text-sm"
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
          <div class="modal-header bg-primary-subtle border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-2 mb-0">
              <i class="ri-magic-line text-primary fs-18"></i>
              <span>Pilih Template Preset Formulir Lomba</span>
            </h5>
            <div class="d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-sm btn-success d-flex align-items-center gap-1 fs-11 fw-semibold py-1 px-2.5 shadow-sm"
                @click="openSaveCurrentAsPresetModal()"
                title="Simpan konfigurasi pertanyaan yang sedang dibuka sebagai template baru"
              >
                <i class="ri-save-line"></i>
                <span>Simpan Form Ini Jadi Preset</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-secondary fs-11 py-1 px-2"
                @click="resetAllPresetsToDefault()"
                title="Kembalikan semua preset ke standar sistem"
              >
                <i class="ri-refresh-line"></i>
              </button>
              <button type="button" class="btn-close ms-1" @click="showPresetModal = false"></button>
            </div>
          </div>
          <div class="modal-body p-4">
            <p class="text-muted fs-12 mb-3">
              Pilih template yang sesuai dengan kategori lomba <strong>{{ selectedCompetition?.title }}</strong> atau sesuaikan isinya dengan tombol edit pada kartu.
            </p>
            <div class="row g-3">
              <div v-for="p in presetList" :key="p.key" class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span 
                      class="badge fs-11"
                      :class="p.badge_class || 'bg-primary-subtle text-primary'"
                    >
                      {{ p.badge || p.category || 'Preset' }}
                    </span>
                    <div class="d-flex align-items-center gap-1.5">
                      <span v-if="p.is_custom" class="badge bg-info-subtle text-info fs-10 font-mono">Kustom</span>
                      <i :class="[p.icon || 'ri-magic-line', 'fs-20 text-warning']"></i>
                    </div>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">{{ p.title }}</h6>
                  <p class="text-muted fs-11 mb-2">
                    {{ p.description }}
                  </p>
                  <div class="fs-10 text-muted mb-3 d-flex align-items-center gap-2">
                    <span><i class="ri-list-check fs-12 text-primary me-1"></i>{{ (p.fields || []).length }} Field Pertanyaan</span>
                  </div>

                  <div class="mt-auto d-flex gap-2">
                    <button 
                      type="button" 
                      class="btn btn-sm btn-outline-warning flex-grow-1 fw-semibold d-flex align-items-center justify-content-center gap-1" 
                      @click="applyPreset(p.key)"
                    >
                      <i class="ri-check-line"></i>
                      <span>Terapkan</span>
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-sm btn-soft-primary px-2.5" 
                      @click="openEditPresetModal(p)"
                      title="Kustomisasi & Edit Template Ini"
                    >
                      <i class="ri-edit-line"></i>
                      <span class="fs-11 ms-1 d-none d-sm-inline">Edit</span>
                    </button>
                    <button 
                      v-if="p.is_custom"
                      type="button" 
                      class="btn btn-sm btn-soft-danger px-2" 
                      @click="deleteCustomPreset(p.key)"
                      title="Hapus Preset Kustom"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between align-items-center">
            <span class="text-muted fs-11">Total {{ presetList.length }} Template Preset Siap Pakai</span>
            <button type="button" class="btn btn-sm btn-secondary" @click="showPresetModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3B: KUSTOMISASI / EDIT TEMPLATE PRESET                    -->
    <!-- ============================================================== -->
    <div 
      v-if="showEditPresetModal" 
      class="modal fade show d-block preset-builder-modal" 
      style="background: rgba(0,0,0,0.65); z-index: 1080;"
      @click.self="showEditPresetModal = false"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content preset-modal-content border-0 shadow-lg">
          <div class="modal-header preset-modal-header py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
            <h5 class="modal-title fs-14 fw-bold preset-modal-title d-flex align-items-center gap-2 mb-0">
              <span class="preset-header-icon-box">
                <i class="ri-edit-2-line fs-16"></i>
              </span>
              <span>{{ editPresetForm.key ? 'Kustomisasi Template Preset: ' + editPresetForm.title : 'Buat Template Preset Formulir Baru' }}</span>
            </h5>
            <button type="button" class="btn-close" @click="showEditPresetModal = false"></button>
          </div>
          
          <div class="modal-body p-4 preset-modal-body">
            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="form-label fs-11 fw-bold preset-form-label mb-1">Nama / Judul Template Preset *</label>
                <input 
                  type="text" 
                  v-model="editPresetForm.title" 
                  class="form-control form-control-sm preset-form-input fs-12"
                  placeholder="Contoh: Format Khusus Futsal & E-Sport"
                  required
                />
              </div>
              <div class="col-md-3">
                <label class="form-label fs-11 fw-bold preset-form-label mb-1">Kategori Preset</label>
                <input 
                  type="text" 
                  v-model="editPresetForm.category" 
                  class="form-control form-control-sm preset-form-input fs-12"
                  placeholder="Olahraga / Seni / Sains"
                />
              </div>
              <div class="col-md-3">
                <label class="form-label fs-11 fw-bold preset-form-label mb-1">Badge Tag</label>
                <input 
                  type="text" 
                  v-model="editPresetForm.badge" 
                  class="form-control form-control-sm preset-form-input fs-12"
                  placeholder="Contoh: Populer / Tim"
                />
              </div>
              <div class="col-12">
                <label class="form-label fs-11 fw-bold preset-form-label mb-1">Deskripsi Singkat Template</label>
                <textarea 
                  v-model="editPresetForm.description" 
                  rows="2" 
                  class="form-control form-control-sm preset-form-input fs-12"
                  placeholder="Jelaskan ringkas field apa saja yang tercakup dalam template ini..."
                ></textarea>
              </div>
            </div>

            <!-- Susunan Field Pertanyaan Preset -->
            <div class="preset-section-divider pt-3">
              <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                  <h6 class="fs-12 fw-bold preset-section-title mb-0 d-flex align-items-center gap-1.5">
                    <i class="ri-list-settings-line"></i>
                    <span>Susunan Field Pertanyaan ({{ editPresetForm.fields?.length || 0 }} Field)</span>
                  </h6>
                  <span class="fs-10 preset-subtext">Atur field yang akan otomatis diterapkan saat template ini dipilih.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button 
                    type="button" 
                    class="btn btn-xs preset-btn-outline d-flex align-items-center gap-1 py-1 px-2.5 fw-semibold"
                    @click="addPresetField('text')"
                    title="Tambah pertanyaan biasa (teks/angka/berkas)"
                  >
                    <i class="ri-add-line"></i>
                    <span>+ Pertanyaan Biasa</span>
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-warning text-dark d-flex align-items-center gap-1.5 py-1 px-2.5 fw-bold shadow-sm"
                    @click="addPresetField('participant_repeater')"
                    title="Tambah blok biodata multi-peserta dinamis untuk tim/roster/beregu"
                  >
                    <i class="ri-group-line"></i>
                    <span>+ Multi-Peserta (Dinamis)</span>
                  </button>
                </div>
              </div>

              <div v-if="!editPresetForm.fields || editPresetForm.fields.length === 0" class="text-center py-4 preset-subtext fs-11">
                Belum ada pertanyaan pada preset ini. Klik tombol di atas untuk menambah pertanyaan.
              </div>

              <div v-else class="d-flex flex-column gap-2.5 preset-fields-scrollable" style="max-height: 520px; overflow-y: auto;">
                <div 
                  v-for="(pf, pfIdx) in editPresetForm.fields" 
                  :key="pf.id || pfIdx"
                  class="p-2.5 rounded-3 preset-field-card border d-flex flex-column gap-2 transition-all"
                  :class="pf.type === 'participant_repeater' ? 'preset-field-repeater-active' : ''"
                >
                  <!-- Top Row: Field Basic Configuration -->
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge preset-idx-badge font-mono text-[10px]">#{{ pfIdx + 1 }}</span>
                    
                    <div class="flex-grow-1 row g-2 align-items-center">
                      <div class="col-md-5">
                        <input 
                          type="text" 
                          v-model="pf.label" 
                          class="form-control form-control-sm preset-form-input fs-11"
                          placeholder="Label Pertanyaan (contoh: Nama Sekolah / Biodata Personil)"
                        />
                      </div>
                      <div class="col-md-4">
                        <CustomSelect 
                          v-model="pf.type" 
                          :options="fieldTypeOptions"
                          @change="() => onPresetFieldTypeChange(pf)"
                          size="xs"
                          placeholder="Pilih tipe..."
                        />
                      </div>
                      <div class="col-md-3 d-flex align-items-center gap-2">
                        <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-1 cursor-pointer fs-10 preset-check-label">
                          <input type="checkbox" v-model="pf.required" class="form-check-input text-warning" />
                          <span>Wajib</span>
                        </label>

                        <!-- Dedicated Toggle Button for Participant Repeater -->
                        <button 
                          v-if="pf.type === 'participant_repeater'"
                          type="button" 
                          class="btn btn-xs py-1 px-2 fw-semibold d-flex align-items-center gap-1 text-nowrap ms-auto"
                          :class="expandedPresetFields[pf.id] ? 'btn-warning text-dark' : 'btn-outline-warning'"
                          @click="togglePresetFieldExpand(pf.id)"
                          title="Kustomisasi kuota & kolom biodata siswa"
                        >
                          <i class="ri-settings-3-line"></i>
                          <span>{{ expandedPresetFields[pf.id] ? 'Tutup Atur' : 'Atur Kuota & Kolom' }}</span>
                        </button>
                      </div>
                    </div>

                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-danger p-1" 
                      @click="removePresetField(pfIdx)"
                      title="Hapus field dari preset ini"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>

                  <!-- ============================================================ -->
                  <!-- SPECIAL CUSTOMIZATION: MULTI-PESERTA DINAMIS IN PRESET BUILDER -->
                  <!-- ============================================================ -->
                  <div 
                    v-if="pf.type === 'participant_repeater' && expandedPresetFields[pf.id]"
                    class="p-3 rounded-3 preset-repeater-box border d-flex flex-column gap-3 mt-1 animate__animated animate__fadeIn"
                  >
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-2 preset-section-divider">
                      <div class="d-flex align-items-center gap-2">
                        <span class="avatar-xs rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 24px; height: 24px;">
                          <i class="ri-group-line fs-13"></i>
                        </span>
                        <div>
                          <div class="fs-12 fw-bold preset-repeater-title lh-sm">
                            Kustomisasi Khusus: Kuota &amp; Biodata Multi-Peserta (Tim/Roster)
                          </div>
                          <div class="preset-subtext fs-10 lh-sm">
                            Atur kuota delegasi, anggota inti &amp; cadangan, dan checklist kolom isian biodata per siswa.
                          </div>
                        </div>
                      </div>
                      <span class="badge bg-warning text-dark fs-10 fw-bold font-mono">⚡ Logika Dinamis</span>
                    </div>

                    <!-- Kuota Minimal, Maksimal, Inti, Cadangan -->
                    <div class="row g-2">
                      <div class="col-md-3 col-6">
                        <label class="form-label fs-10 fw-bold preset-form-label mb-1">Kuota Minimal Siswa</label>
                        <div class="input-group input-group-sm">
                          <input 
                            type="number" 
                            v-model.number="pf.min_items"
                            class="form-control form-control-sm preset-form-input fs-11 font-mono fw-bold"
                            min="1"
                            placeholder="1"
                          />
                          <span class="input-group-text preset-input-addon fs-10">Siswa</span>
                        </div>
                      </div>
                      <div class="col-md-3 col-6">
                        <label class="form-label fs-10 fw-bold preset-form-label mb-1">Kuota Maksimal Siswa</label>
                        <div class="input-group input-group-sm">
                          <input 
                            type="number" 
                            v-model.number="pf.max_items"
                            class="form-control form-control-sm preset-form-input fs-11 font-mono fw-bold"
                            min="1"
                            placeholder="Bebas"
                          />
                          <span class="input-group-text preset-input-addon fs-10">Siswa</span>
                        </div>
                      </div>
                      <div class="col-md-3 col-6">
                        <label class="form-label fs-10 fw-bold preset-label-success mb-1 d-flex align-items-center gap-1">
                          <i class="ri-shield-user-line"></i> Pemain Inti
                        </label>
                        <div class="input-group input-group-sm">
                          <input 
                            type="number" 
                            v-model.number="pf.main_members"
                            class="form-control form-control-sm preset-form-input fs-11 font-mono"
                            min="1"
                            placeholder="Cth: 5"
                          />
                          <span class="input-group-text preset-input-addon fs-10">Inti</span>
                        </div>
                      </div>
                      <div class="col-md-3 col-6">
                        <label class="form-label fs-10 fw-bold preset-label-warning mb-1 d-flex align-items-center gap-1">
                          <i class="ri-user-shared-line"></i> Pemain Cadangan
                        </label>
                        <div class="input-group input-group-sm">
                          <input 
                            type="number" 
                            v-model.number="pf.reserve_members"
                            class="form-control form-control-sm preset-form-input fs-11 font-mono"
                            min="0"
                            placeholder="Cth: 1"
                          />
                          <span class="input-group-text preset-input-addon fs-10">Cadangan</span>
                        </div>
                      </div>

                      <!-- Quick Preset Chips -->
                      <div class="col-12 pt-1">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                          <span class="preset-subtext fs-10 me-1">Preset Batas Kuota Cepat:</span>
                          <button type="button" class="btn btn-xs btn-preset-chip py-0.5 px-2 fs-10" @click="pf.min_items = 1; pf.max_items = 2; pf.main_members = 2; pf.reserve_members = 0;">2 (Duo/Riset)</button>
                          <button type="button" class="btn btn-xs btn-preset-chip py-0.5 px-2 fs-10" @click="pf.min_items = 3; pf.max_items = 3; pf.main_members = 3; pf.reserve_members = 0;">3 (Regu LCC)</button>
                          <button type="button" class="btn btn-xs btn-preset-chip py-0.5 px-2 fs-10" @click="pf.min_items = 5; pf.max_items = 6; pf.main_members = 5; pf.reserve_members = 1;">6 (5 Inti + 1 Cadangan MLBB)</button>
                          <button type="button" class="btn btn-xs btn-preset-chip py-0.5 px-2 fs-10" @click="pf.min_items = 6; pf.max_items = 10; pf.main_members = 6; pf.reserve_members = 4;">10 (6 Inti + 4 Cadangan Voli)</button>
                          <button type="button" class="btn btn-xs btn-preset-chip py-0.5 px-2 fs-10" @click="pf.min_items = 1; pf.max_items = 10; pf.main_members = 10; pf.reserve_members = 0;">10 (Hadrah/Banjari)</button>
                          <button type="button" class="btn btn-xs btn-preset-chip py-0.5 px-2 fs-10" @click="pf.min_items = 1; pf.max_items = null; pf.main_members = null; pf.reserve_members = null;">Bebas</button>
                        </div>
                      </div>
                    </div>

                    <!-- Checklist Kolom Biodata Siswa -->
                    <div class="preset-section-divider pt-2">
                      <div class="fs-11 fw-bold preset-form-label mb-2">Kolom Biodata Siswa yang Diaktifkan di Template Ini:</div>
                      
                      <div class="row g-2">
                        <!-- Group 1: Identitas -->
                        <div class="col-md-4">
                          <div class="p-2.5 rounded preset-subcard border h-100 d-flex flex-column gap-1.5">
                            <div class="preset-group-title-amber fs-10 fw-bold text-uppercase mb-1 d-flex align-items-center gap-1">
                              <i class="ri-user-line"></i> Identitas &amp; Akademik
                            </div>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 fs-11 preset-subtext opacity-75">
                              <input type="checkbox" class="form-check-input" checked disabled />
                              <span class="preset-check-label">Nama Lengkap Siswa</span>
                              <span class="badge bg-danger-subtle text-danger fs-9 ms-auto">Wajib</span>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-warning" v-model="pf.sub_fields.gender.active" />
                              <span>Jenis Kelamin (L/P)</span>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-warning" v-model="pf.sub_fields.grade.active" />
                              <span>Kelas / Tingkat</span>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-warning" v-model="pf.sub_fields.birth_date.active" />
                              <span>Tanggal Lahir Siswa</span>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-warning" v-model="pf.sub_fields.nisn.active" />
                              <span>Nomor Induk Siswa (NISN)</span>
                            </label>
                          </div>
                        </div>

                        <!-- Group 2: ID & Kontak WhatsApp -->
                        <div class="col-md-4">
                          <div class="p-2.5 rounded preset-subcard border h-100 d-flex flex-column gap-1.5">
                            <div class="preset-group-title-info fs-10 fw-bold text-uppercase mb-1 d-flex align-items-center gap-1">
                              <i class="ri-smartphone-line"></i> ID &amp; Kontak Komunikasi
                            </div>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-info" v-model="pf.sub_fields.custom_id.active" />
                              <div>
                                <span>ID Siswa / No. Game / Punggung</span>
                                <div class="preset-subtext fs-10">Teks bebas</div>
                              </div>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-info" v-model="pf.sub_fields.wa_number.active" />
                              <div>
                                <span>Nomor WhatsApp Siswa</span>
                                <div class="preset-subtext fs-10">Khusus nomor WA tiap personil</div>
                              </div>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-start gap-2 cursor-pointer fs-11 preset-captain-highlight p-1.5 rounded border">
                              <input type="checkbox" class="form-check-input text-warning mt-0.5" v-model="pf.sub_fields.captain_option.active" />
                              <div>
                                <span class="fw-bold d-flex align-items-center gap-1">
                                  <i class="ri-vip-crown-line"></i> Tandai Kapten &amp; WA Khusus
                                </span>
                                <div class="preset-subtext fs-10">Pendaftar memilih 1 kapten tim</div>
                              </div>
                            </label>
                          </div>
                        </div>

                        <!-- Group 3: Foto & Berkas Siswa -->
                        <div class="col-md-4">
                          <div class="p-2.5 rounded preset-subcard border h-100 d-flex flex-column gap-1.5">
                            <div class="preset-group-title-rose fs-10 fw-bold text-uppercase mb-1 d-flex align-items-center gap-1">
                              <i class="ri-image-line"></i> Berkas &amp; Foto Siswa
                            </div>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-danger" v-model="pf.sub_fields.photo_formal.active" />
                              <div>
                                <span>Upload Pas Foto Formal Siswa</span>
                                <div class="preset-subtext fs-10">Foto resmi 3x4 / berseragam (1 file)</div>
                              </div>
                            </label>
                            <label class="form-check form-check-sm mb-0 d-flex align-items-center gap-2 cursor-pointer fs-11 preset-check-label">
                              <input type="checkbox" class="form-check-input text-danger" v-model="pf.sub_fields.photo_freestyle.active" />
                              <div>
                                <span>Upload Foto Pose Gaya Bebas</span>
                                <div class="preset-subtext fs-10">Bisa &gt;1 file foto sekaligus</div>
                              </div>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Group 4: Kolom Tambahan Bebas di Preset -->
                    <div class="preset-section-divider pt-2">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                          <div class="fs-11 fw-bold preset-form-label d-flex align-items-center gap-1.5">
                            <i class="ri-add-box-line text-warning"></i>
                            <span>Kolom Tambahan Kustom Biodata Siswa:</span>
                          </div>
                          <div class="preset-subtext fs-10">Tambah kolom khusus yang akan otomatis hadir di tiap biodata siswa saat template ini diterapkan.</div>
                        </div>
                        <button 
                          type="button" 
                          class="btn btn-xs preset-btn-outline py-1 px-2 fs-10 d-flex align-items-center gap-1 fw-semibold"
                          @click="addCustomSubFieldToPreset(pf)"
                        >
                          <i class="ri-add-line"></i>
                          <span>+ Tambah Kolom Biodata</span>
                        </button>
                      </div>

                      <div v-if="!pf.sub_fields?.custom_columns || pf.sub_fields.custom_columns.length === 0" class="p-2.5 rounded preset-empty-state border fs-10 fst-italic">
                        Belum ada kolom kustom tambahan di preset ini (contoh: Ukuran Baju/Jersey, Role Hero, Akun IG, dll.). Klik <strong>"+ Tambah Kolom Biodata"</strong> di atas jika diperlukan.
                      </div>
                      <div v-else class="d-flex flex-column gap-2">
                        <div 
                          v-for="(col, colIdx) in pf.sub_fields.custom_columns" 
                          :key="col.id || colIdx"
                          class="p-2 rounded preset-custom-col-row border d-flex align-items-center gap-2"
                        >
                          <span class="badge preset-idx-badge font-mono text-[9px]">#{{ colIdx + 1 }}</span>
                          <div class="flex-grow-1 row g-2 align-items-center">
                            <div class="col-md-5">
                              <input 
                                type="text" 
                                v-model="col.label" 
                                class="form-control form-control-sm preset-form-input fs-11"
                                placeholder="Nama Kolom (contoh: Ukuran Jersey)"
                              />
                            </div>
                            <div class="col-md-4">
                              <CustomSelect 
                                v-model="col.type" 
                                :options="columnTypeOptions"
                                size="xs"
                                placeholder="Tipe..."
                              />
                            </div>
                            <div class="col-md-3 d-flex align-items-center gap-2">
                              <label class="form-check form-check-sm mb-0 cursor-pointer fs-10 preset-check-label d-flex align-items-center gap-1">
                                <input type="checkbox" v-model="col.required" class="form-check-input text-warning" />
                                <span>Wajib</span>
                              </label>
                            </div>
                          </div>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-danger p-1" 
                            @click="removeCustomSubFieldFromPreset(pf, colIdx)"
                            title="Hapus kolom ini dari preset"
                          >
                            <i class="ri-delete-bin-line"></i>
                          </button>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer preset-modal-footer py-2.5 px-4 d-flex justify-content-between align-items-center">
            <button type="button" class="btn btn-sm btn-secondary" @click="showEditPresetModal = false">Batal</button>
            <div class="d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-sm btn-primary d-flex align-items-center gap-1 fw-semibold shadow-sm"
                :disabled="isSavingPreset"
                @click="savePresetTemplate"
              >
                <i class="ri-save-line"></i>
                <span>{{ isSavingPreset ? 'Menyimpan...' : 'Simpan Perubahan Template Preset' }}</span>
              </button>
            </div>
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
              <CustomSelect 
                v-model="sourceCompIdToCopy"
                :options="copySourceCompetitionOptions"
                size="md"
                placeholder="-- Pilih Cabang Sumber --"
                searchable
                search-placeholder="Cari cabang sumber..."
              />
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
.top-bar-nav-row {
  flex-wrap: nowrap !important;
}
.min-w-0 {
  min-width: 0 !important;
}
.comp-selector-box {
  max-width: 260px;
  min-width: 160px;
}
@media (min-width: 1400px) {
  .comp-selector-box {
    max-width: 380px;
  }
}
@media (min-width: 1600px) {
  .comp-selector-box {
    max-width: 500px;
  }
}
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
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.25) transparent;
}
.sticky-preview-card {
  position: sticky;
  top: 75px;
  z-index: 10;
}
.preview-scroll-area {
  max-height: calc(100vh - 165px);
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
}
.canvas-scroll-area {
  min-height: 480px;
}

/* Elegant Slim Scrollbar Rules */
.toolbox-scroll-area::-webkit-scrollbar,
.preview-scroll-area::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.toolbox-scroll-area::-webkit-scrollbar-track,
.preview-scroll-area::-webkit-scrollbar-track {
  background: transparent !important;
}
.toolbox-scroll-area::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.28);
  border-radius: 9999px;
  transition: background-color 0.2s ease;
}
.toolbox-scroll-area::-webkit-scrollbar-thumb:hover {
  background-color: rgba(100, 116, 139, 0.55);
}
.preview-scroll-area::-webkit-scrollbar-thumb {
  background-color: rgba(255, 255, 255, 0.15);
  border-radius: 9999px;
  transition: background-color 0.2s ease;
}
.preview-scroll-area::-webkit-scrollbar-thumb:hover {
  background-color: rgba(255, 255, 255, 0.35);
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
  overflow: visible;
}
.field-card:has(.custom-select-wrapper.is-open) {
  z-index: 30;
  position: relative;
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
/* Card Stacking Context Fix for Custom Select Dropdowns */
.field-card:has(.custom-select-wrapper.is-open),
.card:has(.custom-select-wrapper.is-open),
.col-md-5:has(.custom-select-wrapper.is-open) {
  z-index: 100 !important;
  position: relative !important;
}

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

[data-bs-theme="dark"] .text-warning-emphasis,
[data-bs-theme="dark"] .text-warning-emphasis * {
  color: #fde047 !important;
}

/* ============================================================== */
/* PRESET BUILDER MODAL: FULL LIGHT & DARK MODE ADAPTIVE SYSTEM    */
/* ============================================================== */

/* Modal Content Container */
.preset-modal-content {
  background-color: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
}

[data-bs-theme="dark"] .preset-modal-content {
  background-color: #0f172a !important;
  color: #f8fafc !important;
  border-color: #1e293b !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
}

/* Modal Header */
.preset-modal-header {
  background-color: #f8fafc !important;
  border-bottom: 1px solid #e2e8f0 !important;
}

[data-bs-theme="dark"] .preset-modal-header {
  background-color: #0b0f19 !important;
  border-bottom-color: #1e293b !important;
}

.preset-modal-title {
  color: #0f172a !important;
}

[data-bs-theme="dark"] .preset-modal-title {
  color: #f8fafc !important;
}

.preset-header-icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  background-color: #fef3c7;
  color: #d97706;
}

[data-bs-theme="dark"] .preset-header-icon-box {
  background-color: rgba(245, 158, 11, 0.15) !important;
  color: #fbbf24 !important;
}

/* Modal Body */
.preset-modal-body {
  background-color: #f8fafc !important;
  color: #0f172a !important;
}

[data-bs-theme="dark"] .preset-modal-body {
  background-color: #090d16 !important;
  color: #f1f5f9 !important;
}

/* Form Labels & Subtext */
.preset-form-label {
  color: #1e293b !important;
}

[data-bs-theme="dark"] .preset-form-label {
  color: #e2e8f0 !important;
}

.preset-subtext {
  color: #475569 !important;
}

[data-bs-theme="dark"] .preset-subtext {
  color: #94a3b8 !important;
}

.preset-check-label {
  color: #1e293b !important;
}

[data-bs-theme="dark"] .preset-check-label {
  color: #e2e8f0 !important;
}

/* Section Title & Dividers */
.preset-section-title {
  color: #b45309 !important;
}

[data-bs-theme="dark"] .preset-section-title {
  color: #fbbf24 !important;
}

.preset-section-divider {
  border-top: 1px solid #e2e8f0 !important;
}

[data-bs-theme="dark"] .preset-section-divider {
  border-top-color: #1e293b !important;
}

/* Inputs & Textareas */
.preset-form-input {
  background-color: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  color: #0f172a !important;
}

.preset-form-input::placeholder {
  color: #94a3b8 !important;
}

.preset-form-input:focus {
  background-color: #ffffff !important;
  border-color: #3b82f6 !important;
  color: #0f172a !important;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15) !important;
}

[data-bs-theme="dark"] .preset-form-input {
  background-color: #111827 !important;
  border-color: #1e293b !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .preset-form-input::placeholder {
  color: #64748b !important;
}

[data-bs-theme="dark"] .preset-form-input:focus {
  background-color: #1e293b !important;
  border-color: #6366f1 !important;
  color: #ffffff !important;
  box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.2) !important;
}

/* Input Addon */
.preset-input-addon {
  background-color: #f1f5f9 !important;
  border-color: #cbd5e1 !important;
  color: #475569 !important;
}

[data-bs-theme="dark"] .preset-input-addon {
  background-color: #1e293b !important;
  border-color: #1e293b !important;
  color: #94a3b8 !important;
}

/* Field Cards */
.preset-field-card {
  background-color: #ffffff !important;
  border-color: #e2e8f0 !important;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
}

.preset-field-card.preset-field-repeater-active {
  border-color: #f59e0b !important;
  box-shadow: 0 2px 6px 0 rgba(245, 158, 11, 0.12);
}

[data-bs-theme="dark"] .preset-field-card {
  background-color: #111827 !important;
  border-color: #1e293b !important;
}

[data-bs-theme="dark"] .preset-field-card.preset-field-repeater-active {
  border-color: rgba(245, 158, 11, 0.5) !important;
  box-shadow: 0 2px 8px 0 rgba(245, 158, 11, 0.15);
}

/* Field Index Badge */
.preset-idx-badge {
  background-color: #e2e8f0 !important;
  color: #334155 !important;
}

[data-bs-theme="dark"] .preset-idx-badge {
  background-color: #1e293b !important;
  color: #94a3b8 !important;
}

/* Buttons */
.preset-btn-outline {
  background-color: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  color: #1e293b !important;
}

.preset-btn-outline:hover {
  background-color: #f1f5f9 !important;
  border-color: #94a3b8 !important;
  color: #0f172a !important;
}

[data-bs-theme="dark"] .preset-btn-outline {
  background-color: #111827 !important;
  border-color: #334155 !important;
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .preset-btn-outline:hover {
  background-color: #1e293b !important;
  border-color: #475569 !important;
  color: #ffffff !important;
}

/* Repeater Configuration Box */
.preset-repeater-box {
  background-color: #fffdf5 !important;
  border-color: #fde68a !important;
}

[data-bs-theme="dark"] .preset-repeater-box {
  background-color: #0b1120 !important;
  border-color: rgba(245, 158, 11, 0.4) !important;
}

.preset-repeater-title {
  color: #92400e !important;
}

[data-bs-theme="dark"] .preset-repeater-title {
  color: #fde047 !important;
}

.preset-label-success {
  color: #166534 !important;
}

[data-bs-theme="dark"] .preset-label-success {
  color: #86efac !important;
}

.preset-label-warning {
  color: #b45309 !important;
}

[data-bs-theme="dark"] .preset-label-warning {
  color: #fde047 !important;
}

/* Preset Chips */
.preset-builder-modal .btn-preset-chip {
  background-color: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  color: #334155 !important;
  transition: all 0.15s ease-in-out;
}

.preset-builder-modal .btn-preset-chip:hover {
  background-color: #fef3c7 !important;
  border-color: #f59e0b !important;
  color: #92400e !important;
}

[data-bs-theme="dark"] .preset-builder-modal .btn-preset-chip {
  background-color: #0f172a !important;
  border: 1px solid #334155 !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .preset-builder-modal .btn-preset-chip:hover {
  background-color: #1e293b !important;
  border-color: #f59e0b !important;
  color: #fde047 !important;
}

/* Subfield Cards */
.preset-subcard {
  background-color: #ffffff !important;
  border-color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .preset-subcard {
  background-color: #111827 !important;
  border-color: #1e293b !important;
}

.preset-group-title-amber {
  color: #b45309 !important;
}

[data-bs-theme="dark"] .preset-group-title-amber {
  color: #fde047 !important;
}

.preset-group-title-info {
  color: #0369a1 !important;
}

[data-bs-theme="dark"] .preset-group-title-info {
  color: #7dd3fc !important;
}

.preset-group-title-rose {
  color: #be123c !important;
}

[data-bs-theme="dark"] .preset-group-title-rose {
  color: #fca5a5 !important;
}

/* Captain Highlight Box */
.preset-captain-highlight {
  background-color: #fffbeb !important;
  border-color: #fde68a !important;
  color: #92400e !important;
}

.preset-captain-highlight .preset-subtext {
  color: #b45309 !important;
}

[data-bs-theme="dark"] .preset-captain-highlight {
  background-color: #0b1120 !important;
  border-color: rgba(245, 158, 11, 0.3) !important;
  color: #fbbf24 !important;
}

[data-bs-theme="dark"] .preset-captain-highlight .preset-subtext {
  color: #94a3b8 !important;
}

/* Custom Columns State */
.preset-empty-state {
  background-color: #ffffff !important;
  border-color: #e2e8f0 !important;
  color: #64748b !important;
}

[data-bs-theme="dark"] .preset-empty-state {
  background-color: #111827 !important;
  border-color: #1e293b !important;
  color: #94a3b8 !important;
}

.preset-custom-col-row {
  background-color: #ffffff !important;
  border-color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .preset-custom-col-row {
  background-color: #111827 !important;
  border-color: #1e293b !important;
}

/* Modal Footer */
.preset-modal-footer {
  background-color: #f8fafc !important;
  border-top: 1px solid #e2e8f0 !important;
}

[data-bs-theme="dark"] .preset-modal-footer {
  background-color: #0b0f19 !important;
  border-top-color: #1e293b !important;
}
</style>
