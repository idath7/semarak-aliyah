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

const currentCompId = ref(props.selectedCompetition?.id || props.competitions[0]?.id || '');
const activeTab = ref('builder'); // 'builder' | 'preview'
const previewDevice = ref('desktop'); // 'desktop' | 'mobile'

// Clone custom_fields and form_settings from props
const fields = ref([]);
const formSettings = ref({
  show_package_type: false,
  form_title: '',
  form_subtitle: '',
  submit_text: 'Kirim Pendaftaran Delegasi',
});
const isDirty = ref(false);
const isSaving = ref(false);

function initializeData() {
  const sourceFields = props.selectedCompetition?.custom_fields;
  if (Array.isArray(sourceFields) && sourceFields.length > 0) {
    fields.value = JSON.parse(JSON.stringify(sourceFields));
  } else {
    fields.value = [];
  }

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

// Watch for changes in fields to set dirty flag
watch([fields, formSettings], () => {
  isDirty.value = true;
}, { deep: true });

// Competition Switching
async function switchCompetition(compId) {
  if (isDirty.value) {
    const ok = await confirmDialog({
      title: 'Perubahan Belum Disimpan',
      message: 'Ada perubahan formulir yang belum disimpan. Yakin ingin beralih ke cabang lomba lain?',
      type: 'warning',
      confirmText: 'Beralih & Abaikan',
      cancelText: 'Tetap di Sini',
      icon: 'ri-alert-line',
    });
    if (!ok) {
      return;
    }
  }
  router.visit(`/admin/form-builder/${compId}`, {
    preserveScroll: true,
  });
}

// Generate unique field ID
function generateId() {
  return 'cf_' + Math.random().toString(36).substr(2, 9);
}

// Sluggify field name
function makeSlug(text) {
  return (text || '')
    .toString()
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '_')
    .replace(/^-+|-+$/g, '');
}

// 1. Core Identity Field Presets (Bisa Ditambahkan & Dikustomisasi Bebas)
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
  },
];

// 2. Toolbox Component Types
const fieldTypes = [
  { type: 'text', label: 'Teks Singkat', icon: 'ri-input-field', desc: 'Nama, NISN, ID, Judul', defaultOptions: [] },
  { type: 'textarea', label: 'Paragraf / Roster', icon: 'ri-text', desc: 'Deskripsi, daftar anggota, biodata', defaultOptions: [] },
  { type: 'select', label: 'Pilihan Dropdown', icon: 'ri-arrow-down-s-line', desc: 'Pilih salah satu dari daftar opsi', defaultOptions: ['Pilihan 1', 'Pilihan 2', 'Pilihan 3'] },
  { type: 'radio', label: 'Pilihan Tunggal (Radio)', icon: 'ri-radio-button-line', desc: 'Pilihan eksklusif radio button', defaultOptions: ['Pilihan A', 'Pilihan B'] },
  { type: 'checkbox', label: 'Pilihan Ganda (Checkbox)', icon: 'ri-checkbox-line', desc: 'Centang satu atau beberapa opsi', defaultOptions: ['Item 1', 'Item 2', 'Item 3'] },
  { type: 'file', label: 'Upload Dokumen / File', icon: 'ri-upload-cloud-2-line', desc: 'Unggah berkas PDF, JPG, PNG, dll.', defaultOptions: [] },
  { type: 'url', label: 'Tautan / Link URL', icon: 'ri-link', desc: 'Link Google Drive, YouTube, dll.', defaultOptions: [] },
  { type: 'number', label: 'Angka / Nominal', icon: 'ri-hashtag', desc: 'Usia, kelas, skor, no. urut', defaultOptions: [] },
  { type: 'date', label: 'Tanggal', icon: 'ri-calendar-line', desc: 'Tanggal lahir, tanggal terbit', defaultOptions: [] },
];

// 3. Quick Preset Library
const quickPresets = [
  {
    name: 'Nomor Induk Siswa (NISN)',
    type: 'text',
    label: 'Nomor Induk Siswa Nasional (NISN)',
    placeholder: 'Contoh: 0081234567',
    help_text: 'NISN resmi peserta delegasi terdaftar di Kemendikbud/Kemenag',
    required: true,
  },
  {
    name: 'Scan / Foto Kartu Pelajar (KTS)',
    type: 'file',
    label: 'Scan / Foto Kartu Tanda Siswa (KTS)',
    help_text: 'Unggah foto Kartu Pelajar atau Surat Keterangan Siswa Aktif (PDF/JPG/PNG)',
    required: true,
    allowed_extensions: ['pdf', 'jpg', 'jpeg', 'png'],
    max_size_mb: 5,
  },
  {
    name: 'Nama Tim / Squad',
    type: 'text',
    label: 'Nama Tim / Squad Resmi',
    placeholder: 'Contoh: Garuda Muda MTs',
    help_text: 'Nama resmi tim yang akan tercantum pada bagan turnamen',
    required: true,
  },
  {
    name: 'ID Game & Server (E-Sport)',
    type: 'text',
    label: 'User ID & Zone Server Game',
    placeholder: 'Contoh: 12345678 (2045)',
    help_text: 'ID akun in-game beserta zona server dalam kurung',
    required: true,
  },
  {
    name: 'Link Karya / Video (Drive/YT)',
    type: 'url',
    label: 'Link Video / Portofolio Karya',
    placeholder: 'https://drive.google.com/...',
    help_text: 'Tautan rekaman video atau portofolio digital (Pastikan diset publik)',
    required: true,
  },
  {
    name: 'Pilihan Golongan Juz',
    type: 'radio',
    label: 'Golongan Juz yang Diujikan',
    help_text: 'Pilih kategori golongan juz hafalan',
    required: true,
    options: ['Juz 1 s.d. 5', 'Juz 26 s.d. 30'],
  },
  {
    name: 'Roster Pemain & Posisi',
    type: 'textarea',
    label: 'Daftar Pemain & Posisi Lapangan',
    placeholder: "1. Nama (No. Punggung - Posisi)\n2. Nama (No. Punggung - Posisi)...",
    help_text: 'Sebutkan nomor punggung dan posisi masing-masing pemain',
    required: true,
  },
  {
    name: 'Surat Keterangan / Rekomendasi',
    type: 'file',
    label: 'Surat Rekomendasi Kepala Madrasah',
    help_text: 'Unggah surat rekomendasi delegasi resmi bertandatangan & stempel (PDF)',
    required: true,
    allowed_extensions: ['pdf'],
    max_size_mb: 5,
  },
];

// Add Field from Toolbox
function addField(typeObj) {
  const id = generateId();
  const label = `Pertanyaan Baru (${typeObj.label})`;
  const name = makeSlug(label);

  const newField = {
    id: id,
    name: name,
    label: label,
    type: typeObj.type,
    placeholder: '',
    help_text: '',
    required: false,
    options: typeObj.defaultOptions ? [...typeObj.defaultOptions] : [],
    allowed_extensions: typeObj.type === 'file' ? ['pdf', 'jpg', 'jpeg', 'png'] : [],
    max_size_mb: typeObj.type === 'file' ? 5 : null,
  };

  fields.value.push(newField);
  toast.info(`Field "${typeObj.label}" berhasil ditambahkan.`);
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
  };

  fields.value.push(newField);
  toast.success(`Field "${preset.name}" ditambahkan!`);
}

// Add Quick Preset
function addQuickPreset(preset) {
  const id = generateId();
  const newField = {
    id: id,
    name: makeSlug(preset.label),
    label: preset.label,
    type: preset.type,
    placeholder: preset.placeholder || '',
    help_text: preset.help_text || '',
    required: !!preset.required,
    options: preset.options ? [...preset.options] : [],
    allowed_extensions: preset.allowed_extensions ? [...preset.allowed_extensions] : [],
    max_size_mb: preset.max_size_mb || (preset.type === 'file' ? 5 : null),
  };

  fields.value.push(newField);
  toast.success(`Preset "${preset.name}" ditambahkan!`);
}

// Field Operations
async function removeField(index) {
  const f = fields.value[index];
  const ok = await confirmDialog({
    title: 'Hapus Field Formulir?',
    message: `Apakah Anda yakin ingin menghapus field "${f.label}"?`,
    type: 'danger',
    confirmText: 'Ya, Hapus Field',
    icon: 'ri-delete-bin-line',
  });
  if (ok) {
    fields.value.splice(index, 1);
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

// Options Manager for Select, Radio, Checkbox
function addOption(field) {
  if (!Array.isArray(field.options)) {
    field.options = [];
  }
  field.options.push(`Pilihan ${field.options.length + 1}`);
}

function removeOption(field, optIndex) {
  if (field.options.length <= 1) {
    alert('Minimal harus memiliki 1 pilihan opsi.');
    return;
  }
  field.options.splice(optIndex, 1);
}

// Allowed extensions toggle for File type
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

// Native Drag & Drop Reordering
let draggedItemIndex = null;

function onDragStart(index, event) {
  draggedItemIndex = index;
  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', index);
}

function onDragOver(event) {
  event.preventDefault();
  event.dataTransfer.dropEffect = 'move';
}

function onDrop(targetIndex, event) {
  event.preventDefault();
  if (draggedItemIndex === null || draggedItemIndex === targetIndex) return;

  const item = fields.value.splice(draggedItemIndex, 1)[0];
  fields.value.splice(targetIndex, 0, item);
  draggedItemIndex = null;
}

// Save Form Fields and Form Settings
function saveFormFields() {
  if (!currentCompId.value) return;

  // Validation
  for (let i = 0; i < fields.value.length; i++) {
    const f = fields.value[i];
    if (!f.label || !f.label.trim()) {
      alert(`Field ke-${i + 1} belum memiliki Nama Label Pertanyaan.`);
      return;
    }
    if (['select', 'radio', 'checkbox'].includes(f.type)) {
      if (!f.options || f.options.length === 0) {
        alert(`Field "${f.label}" bertipe ${f.type} harus memiliki minimal 1 pilihan opsi.`);
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
      toast.success(`Formulir pendaftaran cabang "${props.selectedCompetition?.title}" berhasil disimpan!`, 'Tersimpan');
    },
    onError: () => {
      isSaving.value = false;
      toast.error('Gagal menyimpan formulir pendaftaran.', 'Terjadi Kesalahan');
    }
  });
}

// Template Preset Modal
const showPresetModal = ref(false);

async function applyPreset(presetKey) {
  const ok = await confirmDialog({
    title: 'Terapkan Template Preset?',
    message: `Terapkan preset "${presetKey}"? Susunan field saat ini akan digantikan oleh preset terpilih.`,
    type: 'warning',
    confirmText: 'Ya, Terapkan Preset',
    icon: 'ri-magic-line',
  });
  if (ok) {
    router.post(`/admin/form-builder/${currentCompId.value}/preset`, {
      preset: presetKey,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        showPresetModal.value = false;
        toast.success(`Preset "${presetKey}" berhasil diterapkan!`);
      }
    });
  }
}

// Copy from Another Competition Modal
const showCopyModal = ref(false);
const sourceCompIdToCopy = ref('');

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

// Reset Confirmation
async function resetForm() {
  const ok = await confirmDialog({
    title: 'Kosongkan Seluruh Formulir?',
    message: `Yakin ingin mengosongkan semua field formulir cabang "${props.selectedCompetition?.title}"? Semua konfigurasi field tambahan akan di-reset.`,
    type: 'danger',
    confirmText: 'Ya, Kosongkan',
    icon: 'ri-eraser-line',
  });
  if (ok) {
    router.post(`/admin/form-builder/${currentCompId.value}/reset`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        toast.info('Formulir telah direset.');
      }
    });
  }
}

// Settings Modal
const showSettingsModal = ref(false);

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

// Preview Dummy State for Interactive Testing
const previewValues = ref({});
</script>

<template>
  <VelzonLayout>
    <Head :title="`Formulir Builder: ${selectedCompetition?.title || 'Cabang Lomba'} | Semarak Aliyah XIV`" />

    <!-- ============================================================== -->
    <!-- HEADER BAR: TITLE, COMPETITION SWITCHER, ACTIONS                -->
    <!-- ============================================================== -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3 p-lg-4">
        <div class="row align-items-center g-3">
          <!-- Title & Breadcrumbs -->
          <div class="col-xl-4 col-lg-5">
            <div class="d-flex align-items-center gap-2 mb-1">
              <Link href="/admin" class="text-muted text-decoration-none fs-12">Dashboard</Link>
              <span class="text-muted fs-11">/</span>
              <Link href="/admin/lomba" class="text-muted text-decoration-none fs-12">Manajemen Lomba</Link>
              <span class="text-muted fs-11">/</span>
              <span class="text-dark fw-semibold fs-12">Formulir Builder</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-sm flex-shrink-0 bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center">
                <i class="ri-survey-line fs-20"></i>
              </div>
              <div>
                <h4 class="fs-17 fw-bold mb-0 text-dark">Formulir Builder Pendaftaran</h4>
                <p class="text-muted fs-12 mb-0">Atur seluruh pertanyaan, urutan, & syarat berkas pendaftaran bebas tanpa terkunci</p>
              </div>
            </div>
          </div>

          <!-- Competition Switcher Dropdown -->
          <div class="col-xl-4 col-lg-4">
            <div class="bg-light p-2 rounded-3 border d-flex align-items-center gap-2">
              <label class="fs-12 fw-semibold text-muted text-nowrap mb-0 ms-1">
                <i class="ri-medal-line text-warning"></i> Pilih Cabang:
              </label>
              <select 
                class="form-select form-select-sm border-0 bg-white fw-bold text-dark fs-13 shadow-none"
                :value="currentCompId"
                @change="switchCompetition($event.target.value)"
              >
                <option v-for="c in competitions" :key="c.id" :value="c.id">
                  {{ c.icon }} {{ c.title }} ({{ c.custom_fields?.length || 0 }} Field)
                </option>
              </select>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="col-xl-4 col-lg-3 text-lg-end d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
            <!-- Mode Switcher Tab Buttons -->
            <div class="btn-group btn-group-sm" role="group">
              <button 
                type="button" 
                class="btn"
                :class="activeTab === 'builder' ? 'btn-primary' : 'btn-outline-secondary'"
                @click="activeTab = 'builder'"
              >
                <i class="ri-edit-2-line"></i> Desain
              </button>
              <button 
                type="button" 
                class="btn"
                :class="activeTab === 'preview' ? 'btn-info' : 'btn-outline-secondary'"
                @click="activeTab = 'preview'"
              >
                <i class="ri-eye-line"></i> Pratinjau
              </button>
            </div>

            <!-- Save Button -->
            <button 
              type="button" 
              class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 shadow-sm fw-bold px-3"
              :disabled="isSaving"
              @click="saveFormFields"
              title="Tekan Ctrl+S untuk simpan cepat"
            >
              <i v-if="!isSaving" class="ri-save-line fs-14"></i>
              <span v-if="isSaving" class="spinner-border spinner-border-sm"></span>
              <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Formulir' }}</span>
              <span v-if="isDirty" class="badge bg-warning text-dark fs-9 ms-1">Ada Perubahan</span>
            </button>
          </div>
        </div>

        <!-- Competition Detail Ribbon & Actions Bar -->
        <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-2 fs-12">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-primary text-white fs-11">
              <i class="ri-award-line me-0.5"></i> {{ selectedCompetition?.title }}
            </span>
            <span class="badge bg-secondary-subtle text-secondary fs-11 text-uppercase">
              {{ selectedCompetition?.category }}
            </span>
            <span class="badge bg-info-subtle text-info fs-11">
              {{ selectedCompetition?.participant_type === 'tim' ? 'Tim / Regu' : 'Individu' }}
            </span>
            <span class="text-muted fs-11">
              • Biaya: <strong class="text-success font-mono">Rp {{ Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID') }}</strong>
            </span>
          </div>

          <div class="d-flex align-items-center gap-2">
            <!-- Form Settings Button -->
            <button 
              type="button" 
              class="btn btn-xs btn-outline-dark d-inline-flex align-items-center gap-1"
              @click="showSettingsModal = true"
            >
              <i class="ri-settings-3-line"></i> Pengaturan Formulir
            </button>

            <!-- Template Preset Button -->
            <button 
              type="button" 
              class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1"
              @click="showPresetModal = true"
            >
              <i class="ri-magic-line"></i> Terapkan Template
            </button>

            <!-- Copy Button -->
            <button 
              type="button" 
              class="btn btn-xs btn-outline-secondary d-inline-flex align-items-center gap-1"
              @click="showCopyModal = true"
            >
              <i class="ri-file-copy-line"></i> Salin dari Cabang Lain
            </button>

            <!-- Reset Button -->
            <button 
              type="button" 
              class="btn btn-xs btn-outline-danger d-inline-flex align-items-center gap-1"
              @click="resetForm"
              title="Kosongkan seluruh field formulir"
            >
              <i class="ri-refresh-line"></i> Kosongkan
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MAIN WORKSPACE: BUILDER CANVAS vs LIVE PREVIEW                  -->
    <!-- ============================================================== -->
    <div v-show="activeTab === 'builder'" class="row g-4">
      <!-- ------------------------------------------------------------ -->
      <!-- LEFT COLUMN: FIELD TOOLBOX & PRESETS                         -->
      <!-- ------------------------------------------------------------ -->
      <div class="col-xl-4 col-lg-5">
        <!-- Palette 1: Core Identity / Fundamental Fields (Bisa ditambah bebas) -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-info-subtle border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
            <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
              <i class="ri-user-shared-line text-info"></i> Komponen Identitas Inti
            </h6>
            <span class="badge bg-info text-white fs-10">Bebas Custom</span>
          </div>
          <div class="card-body p-2.5">
            <p class="text-muted fs-11 mb-2">
              Klik komponen di bawah untuk menambahkan identitas dasar (dapat Anda ubah nama, diurutkan, atau dihapus):
            </p>
            <div class="d-flex flex-column gap-1.5">
              <button 
                v-for="(cip, cIdx) in coreIdentityPresets" 
                :key="cIdx"
                type="button" 
                class="btn btn-sm btn-outline-light text-dark text-start border p-2 rounded-2 d-flex align-items-center justify-content-between hover-core-preset"
                @click="addCorePreset(cip)"
              >
                <div class="d-flex align-items-center gap-2 text-truncate">
                  <i :class="cip.icon" class="fs-15 text-primary flex-shrink-0"></i>
                  <span class="fs-12 fw-semibold text-truncate">{{ cip.name }}</span>
                </div>
                <i class="ri-add-line fs-14 text-muted flex-shrink-0"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Palette 2: Basic Input Types -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-light border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
            <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
              <i class="ri-tools-line text-primary"></i> Tipe Input Komponen
            </h6>
            <span class="badge bg-primary-subtle text-primary fs-10">Bebas</span>
          </div>
          <div class="card-body p-2.5">
            <div class="row g-2">
              <div v-for="ft in fieldTypes" :key="ft.type" class="col-6">
                <button 
                  type="button" 
                  class="btn btn-outline-light text-start p-2.5 w-100 rounded-3 border d-flex flex-column justify-content-between h-100 hover-toolbox-item"
                  @click="addField(ft)"
                  :title="`Klik untuk menambahkan input ${ft.label}`"
                >
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <i :class="ft.icon" class="fs-16 text-primary flex-shrink-0"></i>
                    <span class="fs-12 fw-bold text-dark text-truncate">{{ ft.label }}</span>
                  </div>
                  <small class="text-muted fs-10 lh-1 d-block text-truncate">{{ ft.desc }}</small>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Palette 3: 1-Click Common Field Presets -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-warning-subtle border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
            <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
              <i class="ri-flashlight-line text-warning"></i> Preset Pertanyaan Siap Pakai
            </h6>
            <span class="badge bg-warning text-dark fs-10">1-Klik</span>
          </div>
          <div class="card-body p-2.5">
            <div class="d-flex flex-column gap-1.5">
              <button 
                v-for="(qp, qIdx) in quickPresets" 
                :key="qIdx"
                type="button" 
                class="btn btn-sm btn-outline-light text-dark text-start border p-2 rounded-2 d-flex align-items-center justify-content-between hover-quick-preset"
                @click="addQuickPreset(qp)"
              >
                <div class="d-flex align-items-center gap-2 text-truncate">
                  <i 
                    :class="qp.type === 'file' ? 'ri-upload-cloud-2-line text-danger' : (qp.type === 'url' ? 'ri-link text-info' : (qp.type === 'radio' ? 'ri-radio-button-line text-success' : 'ri-input-field text-primary'))"
                    class="fs-15 flex-shrink-0"
                  ></i>
                  <span class="fs-12 fw-semibold text-truncate">{{ qp.name }}</span>
                </div>
                <i class="ri-add-line fs-14 text-muted flex-shrink-0"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ------------------------------------------------------------ -->
      <!-- RIGHT COLUMN: ACTIVE FORM CANVAS (DRAG & REORDER & EDIT)      -->
      <!-- ------------------------------------------------------------ -->
      <div class="col-xl-8 col-lg-7">
        <!-- Canvas Card -->
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <h5 class="fs-15 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <span>Susunan Formulir: {{ selectedCompetition?.title }}</span>
                <span class="badge bg-light text-dark border fs-11">{{ fields.length }} Pertanyaan</span>
              </h5>
              <p class="text-muted fs-11 mb-0 mt-0.5">
                Tidak ada field yang dikunci. Anda bebas mengubah nama label, petunjuk bantuan, menentukan wajib/opsional, menggeser urutan, atau menghapus field.
              </p>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1"
                @click="activeTab = 'preview'"
              >
                <i class="ri-play-line"></i> Pratinjau Tampilan
              </button>
            </div>
          </div>

          <div class="card-body p-3 p-lg-4">
            <!-- Unlocked Freedom Notification Banner -->
            <div class="alert alert-success border-0 d-flex align-items-center justify-content-between p-2.5 rounded-3 mb-4">
              <div class="d-flex align-items-center gap-2 fs-12 text-success-emphasis">
                <i class="ri-lock-unlock-line fs-18 text-success flex-shrink-0"></i>
                <div>
                  <strong>Formulir Dinamis Bebas Kustomisasi:</strong>
                  Seluruh pertanyaan di bawah ini adalah field yang akan tampil di formulir pendaftaran publik cabang ini.
                </div>
              </div>
              <button 
                type="button" 
                class="btn btn-xs btn-outline-success fw-semibold text-nowrap ms-2"
                @click="showSettingsModal = true"
              >
                <i class="ri-equalizer-line"></i> Opsi Formulir
              </button>
            </div>

            <!-- Empty State -->
            <div v-if="fields.length === 0" class="text-center py-5 border border-dashed rounded-3">
              <div class="avatar-lg bg-warning-subtle text-warning rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3">
                <i class="ri-survey-line fs-32"></i>
              </div>
              <h5 class="fs-16 fw-bold text-dark">Formulir Cabang Ini Masih Kosong</h5>
              <p class="text-muted fs-12 max-w-md mx-auto mb-4">
                Tambahkan field pertanyaan dari panel toolbox sebelah kiri, atau gunakan template preset siap pakai dengan 1 kali klik.
              </p>
              <div class="d-flex justify-content-center gap-2">
                <button 
                  type="button" 
                  class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm"
                  @click="showPresetModal = true"
                >
                  <i class="ri-magic-line"></i> Terapkan Template Siap Pakai
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                  @click="addCorePreset(coreIdentityPresets[0])"
                >
                  <i class="ri-add-line"></i> Tambah Identitas Sekolah
                </button>
              </div>
            </div>

            <!-- List of All Configured Fields (Fully Unlocked & Editable) -->
            <div v-else class="space-y-3 d-flex flex-column gap-3">
              <div 
                v-for="(field, index) in fields" 
                :key="field.id"
                class="card border rounded-3 field-card shadow-sm position-relative transition-all"
                :class="{ 'border-primary': field.required, 'border-light-subtle': !field.required }"
                draggable="true"
                @dragstart="onDragStart(index, $event)"
                @dragover="onDragOver($event)"
                @drop="onDrop(index, $event)"
              >
                <!-- Card Header with Reorder Handles & Actions -->
                <div class="card-header bg-light border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <!-- Drag Handle -->
                    <span class="cursor-grab text-muted p-1 hover-drag" title="Tahan & seret untuk mengubah urutan">
                      <i class="ri-drag-move-2-line fs-16"></i>
                    </span>

                    <!-- Index Badge -->
                    <span class="badge bg-dark text-white font-mono fs-11">#{{ index + 1 }}</span>

                    <!-- Type Badge -->
                    <span 
                      class="badge fs-11 d-inline-flex align-items-center gap-1"
                      :class="{
                        'bg-primary-subtle text-primary': field.type === 'text',
                        'bg-info-subtle text-info': field.type === 'textarea' || field.type === 'url',
                        'bg-success-subtle text-success': field.type === 'select' || field.type === 'radio' || field.type === 'checkbox',
                        'bg-danger-subtle text-danger': field.type === 'file',
                        'bg-secondary-subtle text-secondary': field.type === 'number' || field.type === 'date'
                      }"
                    >
                      <i 
                        :class="field.type === 'file' ? 'ri-upload-cloud-2-line' : (field.type === 'url' ? 'ri-link' : (field.type === 'select' ? 'ri-arrow-down-s-line' : 'ri-input-field'))"
                      ></i>
                      <span class="text-uppercase">{{ field.type }}</span>
                    </span>

                    <span class="text-muted fs-11 font-mono">({{ field.name }})</span>
                  </div>

                  <!-- Right Header Controls: Reorder buttons, Duplicate, Required Switch, Delete -->
                  <div class="d-flex align-items-center gap-2">
                    <!-- Required Toggle -->
                    <div class="form-check form-switch mb-0 d-flex align-items-center gap-1.5" title="Tandai sebagai pertanyaan wajib diisi">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        role="switch" 
                        :id="`req-switch-${field.id}`"
                        v-model="field.required" 
                      />
                      <label :for="`req-switch-${field.id}`" class="form-check-label fs-11 mb-0 cursor-pointer fw-semibold text-muted">
                        {{ field.required ? 'Wajib' : 'Opsional' }}
                      </label>
                    </div>

                    <div class="vr mx-1"></div>

                    <!-- Move Up / Down -->
                    <div class="btn-group btn-group-sm">
                      <button 
                        type="button" 
                        class="btn btn-xs btn-light border text-muted"
                        :disabled="index === 0"
                        @click="moveFieldUp(index)"
                        title="Pindah ke Atas"
                      >
                        <i class="ri-arrow-up-s-line"></i>
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-light border text-muted"
                        :disabled="index === fields.length - 1"
                        @click="moveFieldDown(index)"
                        title="Pindah ke Bawah"
                      >
                        <i class="ri-arrow-down-s-line"></i>
                      </button>
                    </div>

                    <!-- Duplicate Button -->
                    <button 
                      type="button" 
                      class="btn btn-xs btn-light border text-secondary"
                      @click="duplicateField(index)"
                      title="Duplikasi Pertanyaan Ini"
                    >
                      <i class="ri-file-copy-line"></i>
                    </button>

                    <!-- Delete Button (Bisa Dihapus Kapan Saja!) -->
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-danger"
                      @click="removeField(index)"
                      title="Hapus Pertanyaan Ini dari Formulir"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>
                </div>

                <!-- Card Body: Field Configuration Inputs -->
                <div class="card-body p-3">
                  <div class="row g-3">
                    <!-- Label Pertanyaan (Bebas Diedit!) -->
                    <div class="col-md-7">
                      <label class="form-label fs-12 fw-bold text-dark mb-1">
                        Label Teks Pertanyaan *
                      </label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted"><i class="ri-edit-line"></i></span>
                        <input 
                          type="text" 
                          v-model="field.label"
                          class="form-control fs-13 fw-semibold text-dark"
                          placeholder="Contoh: Nama Sekolah / Asal Kontingen / Nama Tim..."
                          required
                        />
                      </div>
                    </div>

                    <!-- Tipe Field & Slug Key -->
                    <div class="col-md-5">
                      <label class="form-label fs-12 fw-semibold text-dark mb-1">
                        Tipe Komponen Input
                      </label>
                      <select v-model="field.type" class="form-select form-select-sm fs-12 text-dark">
                        <option v-for="ft in fieldTypes" :key="ft.type" :value="ft.type">
                          {{ ft.label }} ({{ ft.type }})
                        </option>
                      </select>
                    </div>

                    <!-- Placeholder -->
                    <div v-if="!['file', 'radio', 'checkbox'].includes(field.type)" class="col-md-6">
                      <label class="form-label fs-11 text-muted mb-1">
                        Teks Contoh / Petunjuk Input (Placeholder)
                      </label>
                      <input 
                        type="text" 
                        v-model="field.placeholder"
                        class="form-control form-control-sm fs-12"
                        placeholder="Contoh: Masukkan teks petunjuk..."
                      />
                    </div>

                    <!-- Keterangan / Bantuan Pengisian (Help Text) -->
                    <div :class="!['file', 'radio', 'checkbox'].includes(field.type) ? 'col-md-6' : 'col-12'">
                      <label class="form-label fs-11 text-muted mb-1">
                        Petunjuk Bantuan Tambahan (Help Text)
                      </label>
                      <input 
                        type="text" 
                        v-model="field.help_text"
                        class="form-control form-control-sm fs-12"
                        placeholder="Keterangan kecil di bawah input..."
                      />
                    </div>

                    <!-- Options Editor for Select, Radio, Checkbox -->
                    <div v-if="['select', 'radio', 'checkbox'].includes(field.type)" class="col-12">
                      <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <label class="form-label fs-12 fw-bold text-dark mb-0">
                            <i class="ri-list-check-2 text-success me-1"></i> Daftar Pilihan Opsi (Options)
                          </label>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-success d-inline-flex align-items-center gap-1"
                            @click="addOption(field)"
                          >
                            <i class="ri-add-line"></i> Tambah Opsi
                          </button>
                        </div>

                        <div class="d-flex flex-column gap-2">
                          <div 
                            v-for="(opt, oIdx) in field.options" 
                            :key="oIdx"
                            class="input-group input-group-sm"
                          >
                            <span class="input-group-text bg-white text-muted fs-11 font-mono">
                              {{ oIdx + 1 }}
                            </span>
                            <input 
                              type="text" 
                              v-model="field.options[oIdx]"
                              class="form-control fs-12"
                              placeholder="Tuliskan teks opsi pilihan..."
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

                    <!-- File Upload Configuration (Allowed Extensions & Size Limit) -->
                    <div v-if="field.type === 'file'" class="col-12">
                      <div class="p-3 bg-danger-subtle/30 rounded-3 border border-danger-subtle">
                        <div class="row g-3">
                          <div class="col-md-7">
                            <label class="form-label fs-12 fw-bold text-dark mb-1.5 d-block">
                              <i class="ri-file-shield-2-line text-danger me-1"></i> Format Berkas yang Diizinkan:
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                              <label 
                                v-for="ext in ['pdf', 'jpg', 'jpeg', 'png', 'mp3', 'wav', 'docx', 'zip']" 
                                :key="ext"
                                class="btn btn-xs rounded-pill cursor-pointer user-select-none"
                                :class="field.allowed_extensions?.includes(ext) ? 'btn-danger text-white' : 'btn-outline-secondary bg-white'"
                                @click="toggleExtension(field, ext)"
                              >
                                .{{ ext }}
                              </label>
                            </div>
                            <small class="text-muted fs-10 mt-1 d-block">
                              Klik format berkas di atas untuk mengaktifkan atau menonaktifkan.
                            </small>
                          </div>

                          <div class="col-md-5">
                            <label class="form-label fs-12 fw-bold text-dark mb-1">
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
                              <span class="input-group-text bg-light text-muted fs-11">Megabyte (MB)</span>
                            </div>
                            <small class="text-muted fs-10 mt-1 d-block">
                              Batas aman file upload adalah 5 s.d. 10 MB per berkas.
                            </small>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bottom Floating Save Reminder -->
            <div v-if="fields.length > 0" class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
              <span class="text-muted fs-12">
                Total <strong>{{ fields.length }} pertanyaan</strong> dikonfigurasi untuk cabang ini.
              </span>
              <button 
                type="button" 
                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 fw-bold shadow-sm px-4"
                :disabled="isSaving"
                @click="saveFormFields"
              >
                <i v-if="!isSaving" class="ri-check-line"></i>
                <span v-if="isSaving" class="spinner-border spinner-border-sm"></span>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan Formulir' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB: LIVE INTERACTIVE PREVIEW                                   -->
    <!-- ============================================================== -->
    <div v-show="activeTab === 'preview'" class="card border-0 shadow-sm">
      <div class="card-header bg-dark text-white py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-emerald-500 text-slate-950 font-bold fs-11">LIVE TEST PREVIEW</span>
          <h5 class="fs-15 fw-bold text-white mb-0">Simulasi Tampilan Formulir Publik</h5>
        </div>

        <div class="d-flex align-items-center gap-2">
          <!-- Device Preview Selector -->
          <div class="btn-group btn-group-sm" role="group">
            <button 
              type="button" 
              class="btn"
              :class="previewDevice === 'desktop' ? 'btn-light' : 'btn-outline-light'"
              @click="previewDevice = 'desktop'"
            >
              <i class="ri-macbook-line"></i> Desktop
            </button>
            <button 
              type="button" 
              class="btn"
              :class="previewDevice === 'mobile' ? 'btn-light' : 'btn-outline-light'"
              @click="previewDevice = 'mobile'"
            >
              <i class="ri-smartphone-line"></i> Mobile
            </button>
          </div>

          <button 
            type="button" 
            class="btn btn-sm btn-outline-light"
            @click="activeTab = 'builder'"
          >
            <i class="ri-arrow-left-line"></i> Kembali ke Desain
          </button>
        </div>
      </div>

      <!-- Preview Canvas Body (Dark Portal Simulation) -->
      <div class="card-body p-4 bg-slate-950 text-white min-h-[500px] d-flex justify-content-center">
        <div 
          class="transition-all w-full"
          :style="{ maxWidth: previewDevice === 'mobile' ? '420px' : '760px' }"
        >
          <!-- Simulated Form Card -->
          <div class="p-4 sm:p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl text-slate-200">
            <!-- Header simulation -->
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

            <!-- Optional Package Type if enabled in form settings -->
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

            <!-- Dynamic Custom Fields Section (Penuh Tanpa Ada yang Terkunci) -->
            <div class="space-y-5 text-xs">
              <div v-if="fields.length === 0" class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 text-center text-slate-500">
                Belum ada pertanyaan pada formulir ini.
              </div>

              <div 
                v-for="field in fields" 
                :key="field.id"
                class="space-y-1.5"
              >
                <!-- Label & Required Star -->
                <label class="block text-slate-200 font-bold">
                  {{ field.label }}
                  <span v-if="field.required" class="text-rose-400 ml-0.5">*</span>
                  <span v-else class="text-slate-500 font-normal text-[11px] ml-1">(Opsional)</span>
                </label>

                <!-- Input Type: Text or Number or URL -->
                <div v-if="['text', 'number', 'url'].includes(field.type)">
                  <input 
                    :type="field.type" 
                    v-model="previewValues[field.name]"
                    :placeholder="field.placeholder || `Masukkan ${field.label}...`"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                  />
                </div>

                <!-- Input Type: Textarea -->
                <div v-else-if="field.type === 'textarea'">
                  <textarea 
                    rows="3"
                    v-model="previewValues[field.name]"
                    :placeholder="field.placeholder || `Tuliskan rincian ${field.label}...`"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-amber-400 text-xs"
                  ></textarea>
                </div>

                <!-- Input Type: Select Dropdown -->
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

                <!-- Input Type: Radio -->
                <div v-else-if="field.type === 'radio'" class="space-y-2 pt-1">
                  <label 
                    v-for="(opt, optIdx) in field.options" 
                    :key="optIdx"
                    class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-800 bg-slate-950 cursor-pointer hover:border-slate-700 transition-all text-xs"
                  >
                    <input 
                      type="radio" 
                      :name="`preview_${field.name}`" 
                      :value="opt" 
                      v-model="previewValues[field.name]"
                      class="text-amber-500 focus:ring-0"
                    />
                    <span class="text-slate-300 font-medium">{{ opt }}</span>
                  </label>
                </div>

                <!-- Input Type: Checkbox -->
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

                <!-- Input Type: File Upload Zone -->
                <div v-else-if="field.type === 'file'" class="pt-1">
                  <div class="border-2 border-dashed border-slate-800 hover:border-amber-500/50 rounded-2xl p-4 text-center bg-slate-950/70 transition-all cursor-pointer">
                    <i class="ri-upload-cloud-2-line text-2xl text-amber-400 mb-1 d-block"></i>
                    <div class="text-slate-300 font-semibold text-xs">Klik atau seret berkas ke sini</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">
                      Format: {{ field.allowed_extensions?.join(', ') || 'Semua berkas' }} • Maksimal {{ field.max_size_mb || 5 }} MB
                    </div>
                  </div>
                </div>

                <!-- Input Type: Date -->
                <div v-else-if="field.type === 'date'">
                  <input 
                    type="date" 
                    v-model="previewValues[field.name]"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-amber-400 text-xs"
                  />
                </div>

                <!-- Help Text -->
                <div v-if="field.help_text" class="text-[11px] text-slate-500">
                  <i class="ri-information-line me-0.5"></i> {{ field.help_text }}
                </div>
              </div>

              <!-- Submit Button simulation -->
              <div class="pt-4 mt-6 border-t border-slate-800">
                <button 
                  type="button" 
                  class="w-full py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20"
                  @click="toast.success('Simulasi pengisian berhasil! Semua validasi berjalan lancar.')"
                >
                  {{ formSettings.submit_text || 'Kirim Pendaftaran Delegasi' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: PENGATURAN UMUM FORMULIR                                -->
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
            <!-- Toggle Pilihan Paket -->
            <div class="p-3 bg-light rounded-3 border mb-3">
              <div class="form-check form-switch d-flex align-items-center justify-content-between mb-0">
                <div>
                  <label class="form-check-label fs-13 fw-bold text-dark mb-0" for="togglePackage">
                    Tampilkan Pilihan Paket (Satuan / All-In)
                  </label>
                  <p class="text-muted fs-11 mb-0 mt-0.5">
                    Jika dinonaktifkan, pendaftaran otomatis menggunakan biaya satuan cabang ini (Rp {{ Number(selectedCompetition?.fee || 50000).toLocaleString('id-ID') }}).
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

            <!-- Custom Form Subtitle -->
            <div class="mb-3">
              <label class="form-label fs-12 fw-semibold text-dark">Teks Petunjuk / Keterangan Formulir:</label>
              <input 
                type="text" 
                v-model="formSettings.form_subtitle"
                class="form-control fs-12"
                placeholder="Contoh: Silakan isi formulir delegasi di bawah ini dengan lengkap..."
              />
            </div>

            <!-- Custom Submit Button Text -->
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
    <!-- MODAL: TEMPLATE PRESET SELECTION                                -->
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
              Pilih template yang sesuai dengan kategori cabang lomba <strong>{{ selectedCompetition?.title }}</strong>. Template akan otomatis menyusun serangkaian pertanyaan lengkap yang relevan dan dapat Anda kustomisasi kembali.
            </p>

            <div class="row g-3">
              <!-- Template 1: Akademik & Sains -->
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
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-warning mt-auto w-100 fw-semibold"
                    @click="applyPreset('akademik')"
                  >
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <!-- Template 2: Tahfidz & Keagamaan -->
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
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-info mt-auto w-100 fw-semibold"
                    @click="applyPreset('tahfidz')"
                  >
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <!-- Template 3: Seni & Suara -->
              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary fs-11">Seni & Bahasa</span>
                    <i class="ri-music-2-line fs-20 text-primary"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Albanjari, Singer & Storytelling</h6>
                  <p class="text-muted fs-11 mb-3">
                    Sekolah/sanggar, nama panggung, pembina, WhatsApp, personil grup, judul lagu, dan upload file minus one.
                  </p>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary mt-auto w-100 fw-semibold"
                    @click="applyPreset('seni')"
                  >
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <!-- Template 4: E-Sport -->
              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-danger-subtle text-danger fs-11">E-Sport</span>
                    <i class="ri-gamepad-line fs-20 text-danger"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Mobile Legends (MSA S4)</h6>
                  <p class="text-muted fs-11 mb-3">
                    Nama squad, User ID & Server kapten, nickname in-game, WhatsApp, roster 6 pemain, dan screenshot profil kapten.
                  </p>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-danger mt-auto w-100 fw-semibold"
                    @click="applyPreset('esport')"
                  >
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <!-- Template 5: Olahraga Voli -->
              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-success-subtle text-success fs-11">Olahraga</span>
                    <i class="ri-football-line fs-20 text-success"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Tournament Bola Voli</h6>
                  <p class="text-muted fs-11 mb-3">
                    Nama tim voli, nama & kontak pelatih/official, nomor punggung & posisi pemain, dan scan KTS kolektif PDF.
                  </p>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-success mt-auto w-100 fw-semibold"
                    @click="applyPreset('olahraga')"
                  >
                    Terapkan Template Ini
                  </button>
                </div>
              </div>

              <!-- Template 6: Umum Ringkas -->
              <div class="col-md-6">
                <div class="card h-100 border p-3 rounded-3 hover-preset-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-secondary-subtle text-secondary fs-11">Umum Ringkas</span>
                    <i class="ri-settings-4-line fs-20 text-secondary"></i>
                  </div>
                  <h6 class="fs-13 fw-bold text-dark mb-1">Data Pendaftaran Fleksibel</h6>
                  <p class="text-muted fs-11 mb-3">
                    Nama instansi/sekolah, nomor kontak WhatsApp, dan nama peserta kontingen.
                  </p>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-secondary mt-auto w-100 fw-semibold"
                    @click="applyPreset('umum')"
                  >
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
    <!-- MODAL: COPY FORM FIELDS FROM ANOTHER COMPETITION               -->
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
              Salin Konfigurasi Formulir dari Cabang Lain
            </h5>
            <button type="button" class="btn-close" @click="showCopyModal = false"></button>
          </div>

          <div class="modal-body p-4">
            <p class="text-muted fs-12 mb-3">
              Pilih cabang lomba sumber yang susunan formulirnya ingin disalin ke <strong>{{ selectedCompetition?.title }}</strong>.
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
.cursor-grab {
  cursor: grab;
}
.cursor-grab:active {
  cursor: grabbing;
}
.hover-toolbox-item:hover {
  background-color: rgba(10, 179, 156, 0.05) !important;
  border-color: #0ab39c !important;
  transform: translateY(-1px);
}
.hover-core-preset:hover {
  background-color: rgba(13, 202, 240, 0.08) !important;
  border-color: #0dcaf0 !important;
  transform: translateY(-1px);
}
.hover-quick-preset:hover {
  background-color: rgba(247, 184, 75, 0.1) !important;
  border-color: #f7b84b !important;
  transform: translateY(-1px);
}
.hover-preset-card {
  transition: all 0.2s ease-in-out;
}
.hover-preset-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
  border-color: #405189 !important;
  transform: translateY(-2px);
}
.field-card {
  transition: border-color 0.2s, box-shadow 0.2s;
}
.field-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
</style>
