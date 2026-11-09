<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  certificates: {
    type: Array,
    default: () => [],
  },
  competitions: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({
      total_certificates: 0,
      total_students: 0,
      total_schools: 0,
      total_competitions: 0,
    }),
  },
  letterhead: {
    type: Object,
    default: () => ({}),
  },
  certificateConfig: {
    type: Object,
    default: () => ({
      bg_juara: '',
      bg_peserta: '',
      bg_pendamping: '',
      show_kop: true,
      show_signatures: true,
      show_qr: true,
      qr_size: 68,
      name_color: '#064e3b',
      name_size: 26,
      name_top: 45,
      predicate_top: 58,
      comp_top: 68,
      font_family: 'Playfair Display',
      show_text_given_to: true,
      text_given_to: 'Diberikan Kepada :',
      show_text_predicate: true,
      text_predicate: 'Atas partisipasi dan prestasinya sebagai :',
      show_text_competition: true,
      text_competition: 'Dalam Cabang Lomba :',
      show_event_subtitle: true,
      show_school: true,
      text_school: 'Asal Sekolah :',
      predicate_font_family: 'Cinzel',
      predicate_size: 16,
      predicate_color: '#ffffff',
    }),
  },
});

const toast = useToast();

// Tab Switcher: 'list' | 'settings'
const activeTab = ref('list');

// Template Aktif yang Sedang Dikustomisasi: 'juara' | 'peserta' | 'pendamping'
const currentEditingTemplate = ref('juara');

const getInitTmpl = (key, defaultGiven, defaultPredIntro, defaultPredColor) => {
  const t = props.certificateConfig?.templates?.[key] || {};
  return {
    bg: t.bg || props.certificateConfig?.[`bg_${key}`] || '',
    show_kop: t.show_kop ?? true,
    show_signatures: t.show_signatures ?? true,
    show_sig_ketua: t.show_sig_ketua ?? true,
    sig_ketua_title: t.sig_ketua_title || "Mengetahui,\nKetua Panitia Pelaksana,",
    sig_ketua_name: t.sig_ketua_name || 'M. FATHUR RAHMAN, S.Pd.',
    sig_ketua_nip: t.sig_ketua_nip ?? 'NIP. -',
    sig_ketua_image: t.sig_ketua_image || '',
    show_sig_kepala: t.show_sig_kepala ?? true,
    sig_kepala_city_date: t.sig_kepala_city_date || 'Balung, 25 Oktober 2026',
    sig_kepala_title: t.sig_kepala_title || 'Kepala MA Unggulan Wahid Hasyim,',
    sig_kepala_name: t.sig_kepala_name || 'H. NURUL HUDA, S.Ag., M.Pd.I.',
    sig_kepala_nip: t.sig_kepala_nip || 'NIP. 197405122005011003',
    sig_kepala_image: t.sig_kepala_image || '',
    show_stamp: t.show_stamp ?? false,
    stamp_image: t.stamp_image || '',
    show_qr: t.show_qr ?? true,
    qr_size: t.qr_size || 68,
    qr_position: t.qr_position || 'center_bottom',
    qr_x: t.qr_x ?? 50,
    qr_y: t.qr_y ?? 85,
    name_font_family: t.name_font_family || 'Playfair Display',
    name_size: t.name_size || 26,
    name_color: t.name_color || '#064e3b',
    name_top: t.name_top || 45,
    predicate_font_family: t.predicate_font_family || 'Cinzel',
    predicate_size: t.predicate_size || 18,
    predicate_color: t.predicate_color || defaultPredColor,
    predicate_top: t.predicate_top || 58,
    comp_top: t.comp_top || 68,
    show_text_given_to: t.show_text_given_to ?? true,
    text_given_to: t.text_given_to || defaultGiven,
    show_text_predicate: t.show_text_predicate ?? true,
    text_predicate: t.text_predicate || defaultPredIntro,
    show_text_competition: t.show_text_competition ?? true,
    text_competition: t.text_competition || 'Dalam Cabang Lomba:',
    show_school: t.show_school ?? true,
    text_school: t.text_school || 'Asal Sekolah :',
    show_event_subtitle: t.show_event_subtitle ?? true,
  };
};

// Konfigurasi Mandiri Tiap Template
const templateConfigs = ref({
  juara: getInitTmpl('juara', 'Diberikan dengan rasa bangga dan apresiasi setinggi-tingginya kepada:', 'Atas partisipasi dan prestasinya sebagai:', '#d97706'),
  peserta: getInitTmpl('peserta', 'Diberikan dengan rasa bangga dan apresiasi setinggi-tingginya kepada:', 'Atas partisipasi dan prestasinya sebagai:', '#064e3b'),
  pendamping: getInitTmpl('pendamping', 'Diberikan dengan rasa terima kasih dan apresiasi setinggi-tingginya kepada:', 'Sebagai:', '#064e3b'),
});

const templateFiles = ref({
  juara: { file: null, remove: false, sigKetuaFile: null, removeSigKetua: false, sigKepalaFile: null, removeSigKepala: false, stampFile: null, removeStamp: false },
  peserta: { file: null, remove: false, sigKetuaFile: null, removeSigKetua: false, sigKepalaFile: null, removeSigKepala: false, stampFile: null, removeStamp: false },
  pendamping: { file: null, remove: false, sigKetuaFile: null, removeSigKetua: false, sigKepalaFile: null, removeSigKepala: false, stampFile: null, removeStamp: false },
});

const isSaving = ref(false);

// Objek Konfigurasi Template yang Sedang Aktif Diedit
const activeConfig = computed(() => templateConfigs.value[currentEditingTemplate.value]);

// Objek Konfigurasi Template yang Ditampilkan di Kanvas Pratinjau
const previewType = ref('juara'); // 'juara' | 'peserta' | 'pendamping'
const previewConfig = computed(() => templateConfigs.value[previewType.value]);

const currentTemplateLabel = computed(() => {
  if (currentEditingTemplate.value === 'juara') return 'Juara';
  if (currentEditingTemplate.value === 'pendamping') return 'Pendamping';
  return 'Peserta';
});

function selectTemplate(type) {
  currentEditingTemplate.value = type;
  previewType.value = type;
}

const fontFamilyOptions = [
  { value: 'Playfair Display', label: 'Playfair Display (Elegan Serif Resmi)' },
  { value: 'HK Grotesk', label: 'HK Grotesk (Modern Clean Sans-Serif)' },
  { value: 'Poppins', label: 'Poppins (Geometris & Tegas)' },
  { value: 'Inter', label: 'Inter (Presisi Digital & Bersih)' },
];

const predicateFontFamilyOptions = [
  { value: 'Cinzel', label: 'Cinzel (Romawi Megah & Klasik)' },
  { value: 'Playfair Display', label: 'Playfair Display (Elegan Serif Resmi)' },
  { value: 'HK Grotesk', label: 'HK Grotesk (Modern Clean Sans-Serif)' },
  { value: 'Poppins', label: 'Poppins (Geometris & Tegas)' },
  { value: 'Inter', label: 'Inter (Presisi Digital & Bersih)' },
];

const qrPositionOptions = [
  { value: 'center_bottom', label: 'Tengah Bawah (Di Antara TTD Ketua & Kepala)' },
  { value: 'left_bottom', label: 'Sudut Kiri Bawah' },
  { value: 'right_bottom', label: 'Sudut Kanan Bawah' },
  { value: 'custom', label: 'Kustom Bebas (Atur Koordinat X% & Y%)' },
];

function onFileChangeTemplate(type, event) {
  const file = event.target.files?.[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    toast.error('Ukuran berkas melebihi batas maksimal 5MB.');
    event.target.value = '';
    return;
  }

  if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
    toast.error('Format berkas harus berupa JPG, JPEG, atau PNG.');
    event.target.value = '';
    return;
  }

  templateFiles.value[type].file = file;
  templateFiles.value[type].remove = false;
  templateConfigs.value[type].bg = URL.createObjectURL(file);
  selectTemplate(type);
  toast.info(`Gambar blangko ${type} dipilih. Klik "Simpan Pengaturan Blangko" untuk menerapkan.`);
}

function removeBgTemplate(type) {
  templateFiles.value[type].file = null;
  templateFiles.value[type].remove = true;
  templateConfigs.value[type].bg = '';
  selectTemplate(type);
  toast.info(`Gambar blangko ${type} dihapus.`);
}

// Upload & Hapus Scan TTD Ketua Panitia
function onFileChangeSigKetua(type, event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (file.size > 3 * 1024 * 1024) {
    toast.error('Ukuran berkas TTD melebihi batas maksimal 3MB.');
    event.target.value = '';
    return;
  }
  if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
    toast.error('Format berkas TTD harus berupa JPG, JPEG, atau PNG transparan.');
    event.target.value = '';
    return;
  }
  templateFiles.value[type].sigKetuaFile = file;
  templateFiles.value[type].removeSigKetua = false;
  templateConfigs.value[type].sig_ketua_image = URL.createObjectURL(file);
  selectTemplate(type);
  toast.info(`Scan TTD Ketua dipilih. Simpan pengaturan untuk menerapkan.`);
}

function removeSigKetua(type) {
  templateFiles.value[type].sigKetuaFile = null;
  templateFiles.value[type].removeSigKetua = true;
  templateConfigs.value[type].sig_ketua_image = '';
  selectTemplate(type);
  toast.info(`Scan TTD Ketua dihapus.`);
}

// Upload & Hapus Scan TTD Kepala Madrasah
function onFileChangeSigKepala(type, event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (file.size > 3 * 1024 * 1024) {
    toast.error('Ukuran berkas TTD melebihi batas maksimal 3MB.');
    event.target.value = '';
    return;
  }
  if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
    toast.error('Format berkas TTD harus berupa JPG, JPEG, atau PNG transparan.');
    event.target.value = '';
    return;
  }
  templateFiles.value[type].sigKepalaFile = file;
  templateFiles.value[type].removeSigKepala = false;
  templateConfigs.value[type].sig_kepala_image = URL.createObjectURL(file);
  selectTemplate(type);
  toast.info(`Scan TTD Kepala Madrasah dipilih. Simpan pengaturan untuk menerapkan.`);
}

function removeSigKepala(type) {
  templateFiles.value[type].sigKepalaFile = null;
  templateFiles.value[type].removeSigKepala = true;
  templateConfigs.value[type].sig_kepala_image = '';
  selectTemplate(type);
  toast.info(`Scan TTD Kepala Madrasah dihapus.`);
}

// Upload & Hapus Stempel Digital
function onFileChangeStamp(type, event) {
  const file = event.target.files?.[0];
  if (!file) return;
  if (file.size > 3 * 1024 * 1024) {
    toast.error('Ukuran berkas stempel melebihi batas maksimal 3MB.');
    event.target.value = '';
    return;
  }
  if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
    toast.error('Format berkas stempel harus berupa PNG transparan atau JPG.');
    event.target.value = '';
    return;
  }
  templateFiles.value[type].stampFile = file;
  templateFiles.value[type].removeStamp = false;
  templateConfigs.value[type].stamp_image = URL.createObjectURL(file);
  selectTemplate(type);
  toast.info(`Berkas stempel dipilih. Simpan pengaturan untuk menerapkan.`);
}

function removeStamp(type) {
  templateFiles.value[type].stampFile = null;
  templateFiles.value[type].removeStamp = true;
  templateConfigs.value[type].stamp_image = '';
  selectTemplate(type);
  toast.info(`Berkas stempel dihapus.`);
}

function saveCertificateSettings() {
  isSaving.value = true;
  const current = currentEditingTemplate.value;
  const cfg = templateConfigs.value[current];
  const fileState = templateFiles.value[current];

  const form = useForm({
    active_template: current,
    bg: fileState.file,
    remove_bg: fileState.remove ? '1' : '0',
    sig_ketua_file: fileState.sigKetuaFile,
    remove_sig_ketua: fileState.removeSigKetua ? '1' : '0',
    sig_kepala_file: fileState.sigKepalaFile,
    remove_sig_kepala: fileState.removeSigKepala ? '1' : '0',
    stamp_file: fileState.stampFile,
    remove_stamp: fileState.removeStamp ? '1' : '0',
    show_kop: cfg.show_kop ? '1' : '0',
    show_signatures: cfg.show_signatures ? '1' : '0',
    show_sig_ketua: cfg.show_sig_ketua ? '1' : '0',
    sig_ketua_title: cfg.sig_ketua_title,
    sig_ketua_name: cfg.sig_ketua_name,
    sig_ketua_nip: cfg.sig_ketua_nip,
    show_sig_kepala: cfg.show_sig_kepala ? '1' : '0',
    sig_kepala_city_date: cfg.sig_kepala_city_date,
    sig_kepala_title: cfg.sig_kepala_title,
    sig_kepala_name: cfg.sig_kepala_name,
    sig_kepala_nip: cfg.sig_kepala_nip,
    show_stamp: cfg.show_stamp ? '1' : '0',
    show_qr: cfg.show_qr ? '1' : '0',
    qr_size: cfg.qr_size,
    qr_position: cfg.qr_position,
    qr_x: cfg.qr_x,
    qr_y: cfg.qr_y,
    name_font_family: cfg.name_font_family,
    name_size: cfg.name_size,
    name_color: cfg.name_color,
    name_top: cfg.name_top,
    predicate_font_family: cfg.predicate_font_family,
    predicate_size: cfg.predicate_size,
    predicate_color: cfg.predicate_color,
    predicate_top: cfg.predicate_top,
    comp_top: cfg.comp_top,
    show_text_given_to: cfg.show_text_given_to ? '1' : '0',
    text_given_to: cfg.text_given_to,
    show_text_predicate: cfg.show_text_predicate ? '1' : '0',
    text_predicate: cfg.text_predicate,
    show_text_competition: cfg.show_text_competition ? '1' : '0',
    text_competition: cfg.text_competition,
    show_school: cfg.show_school ? '1' : '0',
    text_school: cfg.text_school,
    show_event_subtitle: cfg.show_event_subtitle ? '1' : '0',
  });

  form.post('/admin/sertifikat/settings', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isSaving.value = false;
      fileState.file = null;
      fileState.remove = false;
      fileState.sigKetuaFile = null;
      fileState.removeSigKetua = false;
      fileState.sigKepalaFile = null;
      fileState.removeSigKepala = false;
      fileState.stampFile = null;
      fileState.removeStamp = false;
      const tmplLabel = current === 'juara' ? 'Juara' : (current === 'pendamping' ? 'Pendamping' : 'Peserta');
      toast.success(`Pengaturan mandiri Blangko ${tmplLabel} berhasil disimpan!`);
    },
    onError: (errs) => {
      isSaving.value = false;
      const firstErr = Object.values(errs)[0];
      toast.error(firstErr || 'Gagal menyimpan pengaturan blangko.');
    },
  });
}

// Filters & State for Certificate List
const searchQuery = ref('');
const selectedCompetition = ref('all');
const selectedTarget = ref('all');
const selectedRankFilter = ref('all');
const currentPage = ref(1);
const perPage = ref(25);

// State Predikat Tiap Baris (dinamis diubah langsung oleh admin sebelum mencetak)
const rowRanks = ref({});

// Inisialisasi default rank
props.certificates.forEach((c) => {
  rowRanks.value[c.id] = c.default_rank || 'Peserta';
});

// Options Dropdown
const competitionOptions = computed(() => {
  const opts = [{ value: 'all', label: 'Semua Cabang Lomba' }];
  props.competitions.forEach((c) => {
    opts.push({
      value: String(c.id),
      label: c.title,
    });
  });
  return opts;
});

const targetOptions = [
  { value: 'all', label: 'Semua Kategori Penerima' },
  { value: 'siswa', label: 'Peserta Siswa (Perorangan)' },
  { value: 'sekolah', label: 'Kontingen Sekolah' },
  { value: 'official', label: 'Guru Pendamping / Official' },
];

const rankFilterOptions = [
  { value: 'all', label: 'Semua Predikat Piagam' },
  { value: 'Peserta', label: 'Peserta' },
  { value: 'Juara 1', label: 'Juara 1' },
  { value: 'Juara 2', label: 'Juara 2' },
  { value: 'Juara 3', label: 'Juara 3' },
  { value: 'Juara 4', label: 'Juara 4' },
  { value: 'Harapan 1', label: 'Harapan 1' },
  { value: 'Harapan 2', label: 'Harapan 2' },
  { value: 'Harapan 3', label: 'Harapan 3' },
  { value: 'Juara Favorit', label: 'Juara Favorit' },
  { value: 'Guru Pendamping', label: 'Guru Pendamping' },
];

const predicateRowOptions = [
  { value: 'Peserta', label: 'Peserta' },
  { value: 'Juara 1', label: 'Juara 1' },
  { value: 'Juara 2', label: 'Juara 2' },
  { value: 'Juara 3', label: 'Juara 3' },
  { value: 'Juara 4', label: 'Juara 4' },
  { value: 'Harapan 1', label: 'Harapan 1' },
  { value: 'Harapan 2', label: 'Harapan 2' },
  { value: 'Harapan 3', label: 'Harapan 3' },
  { value: 'Juara Favorit', label: 'Juara Favorit' },
  { value: 'Guru Pendamping', label: 'Guru Pendamping' },
];

const perPageOptions = [
  { value: 15, label: '15 per halaman' },
  { value: 25, label: '25 per halaman' },
  { value: 50, label: '50 per halaman' },
  { value: 100, label: '100 per halaman' },
  { value: 9999, label: 'Tampilkan Semua' },
];

// Computed Filtered List
const filteredCertificates = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  const compId = selectedCompetition.value;
  const target = selectedTarget.value;
  const rankFilter = selectedRankFilter.value;

  return props.certificates.filter((item) => {
    // Filter Competition
    if (compId !== 'all' && String(item.competition_id) !== String(compId)) {
      return false;
    }

    // Filter Target
    if (target !== 'all' && item.target !== target) {
      return false;
    }

    // Filter Rank
    const currentRank = rowRanks.value[item.id] || item.default_rank || 'Peserta';
    if (rankFilter !== 'all' && currentRank !== rankFilter) {
      return false;
    }

    // Filter Search
    if (q) {
      const matchName = String(item.recipient_name || '').toLowerCase().includes(q);
      const matchSchool = String(item.school_name || '').toLowerCase().includes(q);
      const matchReg = String(item.reg_code || '').toLowerCase().includes(q);
      const matchNisn = String(item.nisn || '').toLowerCase().includes(q);
      const matchComp = String(item.competition_title || '').toLowerCase().includes(q);

      if (!matchName && !matchSchool && !matchReg && !matchNisn && !matchComp) {
        return false;
      }
    }

    return true;
  });
});

// Pagination
const totalPages = computed(() => {
  if (perPage.value >= 9999) return 1;
  return Math.ceil(filteredCertificates.value.length / perPage.value) || 1;
});

const paginatedCertificates = computed(() => {
  if (perPage.value >= 9999) return filteredCertificates.value;
  const start = (currentPage.value - 1) * perPage.value;
  return filteredCertificates.value.slice(start, start + perPage.value);
});

function resetFilters() {
  searchQuery.value = '';
  selectedCompetition.value = 'all';
  selectedTarget.value = 'all';
  selectedRankFilter.value = 'all';
  currentPage.value = 1;
  toast.info('Filter berhasil direset.');
}

// Actions
function printSingleCertificate(item) {
  const chosenRank = rowRanks.value[item.id] || 'Peserta';
  const url = `/admin/sertifikat/cetak/${item.registration_id}?target=${item.target}&rank=${encodeURIComponent(chosenRank)}&recipient=${encodeURIComponent(item.recipient_name)}`;
  window.open(url, '_blank');
}

function printBatchCertificates() {
  const compId = selectedCompetition.value;
  const target = selectedTarget.value;
  let rank = selectedRankFilter.value;
  if (rank === 'all') {
    rank = target === 'official' ? 'Guru Pendamping' : 'Peserta';
  }

  const url = `/admin/sertifikat/cetak-massal?competition_id=${compId}&target=${target}&rank=${encodeURIComponent(rank)}`;
  window.open(url, '_blank');
}

function getInitials(name) {
  if (!name) return 'S';
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

// Computed & State for Live Preview (100% Menggunakan Data Nyata Database)
const activePreviewBg = computed(() => {
  return templateConfigs.value[previewType.value]?.bg || '';
});

// Pilihan Cabang Lomba Nyata untuk Pratinjau
const previewCompetitionId = ref(props.competitions[0]?.id ? String(props.competitions[0].id) : 'all');

const previewCompetitionOptions = computed(() => {
  return props.competitions.map((c) => ({
    value: String(c.id),
    label: c.title,
  }));
});

const activePreviewCompetition = computed(() => {
  if (previewCompetitionId.value && previewCompetitionId.value !== 'all') {
    const found = props.competitions.find((c) => String(c.id) === String(previewCompetitionId.value));
    if (found) return found;
  }
  return props.competitions[0] || null;
});

const previewCompetitionTitle = computed(() => {
  return activePreviewCompetition.value?.title || 'Cabang Lomba';
});

// Mengambil Data Peserta & Sekolah Nyata dari Database (props.certificates)
const realPreviewItem = computed(() => {
  if (!props.certificates || props.certificates.length === 0) return null;

  const compId = activePreviewCompetition.value?.id;
  const targetFilter = previewType.value === 'pendamping' ? 'official' : (previewType.value === 'peserta' ? 'siswa' : 'all');

  const match = props.certificates.find((item) => {
    const matchComp = !compId || String(item.competition_id) === String(compId);
    const matchTarget = targetFilter === 'all' || item.target === targetFilter;
    return matchComp && matchTarget;
  });

  if (match) return match;

  const fallbackTarget = props.certificates.find((item) => targetFilter === 'all' || item.target === targetFilter);
  if (fallbackTarget) return fallbackTarget;

  return props.certificates[0];
});

const previewRecipientName = computed(() => {
  if (realPreviewItem.value?.recipient_name) {
    return realPreviewItem.value.recipient_name;
  }
  if (previewType.value === 'juara') return 'NAMA PENERIMA JUARA';
  if (previewType.value === 'peserta') return 'NAMA PESERTA SISWA';
  return 'NAMA GURU PENDAMPING';
});

const previewSchoolName = computed(() => {
  if (realPreviewItem.value?.school_name) {
    return realPreviewItem.value.school_name;
  }
  return 'Madrasah / Sekolah Peserta';
});

const samplePredicate = computed(() => {
  if (previewType.value === 'juara') return 'JUARA 1';
  if (previewType.value === 'peserta') return 'PESERTA';
  return 'GURU PENDAMPING';
});
</script>

<template>
  <VelzonLayout title="E-Sertifikat & Piagam">
    <Head title="E-Sertifikat & Piagam Penghargaan - Semarak Aliyah XIV" />

    <div class="container-fluid py-3">
      <!-- Breadcrumb & Header Title -->
      <div class="row mb-3 align-items-center">
        <div class="col-12 col-md-7">
          <div class="d-flex align-items-center gap-2.5">
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                <i class="ri-award-fill"></i>
              </span>
            </div>
            <div>
              <h4 class="fs-18 fw-bold text-dark mb-0.5">E-Sertifikat & Piagam Penghargaan</h4>
              <p class="text-muted fs-12 mb-0">
                Kelola, tentukan predikat kejuaraan, unggah desain blangko kustom panitia, dan cetak sertifikat resmi A4 Landscape.
              </p>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-5 text-md-end mt-2 mt-md-0">
          <div class="d-inline-flex gap-2">
            <button 
              v-if="activeTab === 'list'"
              type="button" 
              class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1.5"
              @click="printBatchCertificates"
              :title="'Cetak massal sertifikat untuk filter terpilih (' + filteredCertificates.length + ' data)'"
            >
              <i class="ri-printer-line fs-14"></i>
              <span>Cetak Massal Filter Ini ({{ filteredCertificates.length }})</span>
            </button>
            <button 
              v-else
              type="button" 
              class="btn btn-success btn-sm rounded-pill px-3 shadow-sm d-flex align-items-center gap-1.5"
              @click="saveCertificateSettings"
              :disabled="isSaving"
            >
              <i class="ri-save-3-line fs-14"></i>
              <span>{{ isSaving ? 'Menyimpan...' : `Simpan Pengaturan Blangko ${currentTemplateLabel}` }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs: Daftar Sertifikat vs Desain Blangko Panitia -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-2">
          <ul class="nav nav-pills nav-custom-pills gap-2" role="tablist">
            <li class="nav-item">
              <button 
                class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2 rounded-3"
                :class="{ 'active': activeTab === 'list' }"
                @click="activeTab = 'list'"
                type="button"
              >
                <i class="ri-file-list-3-line fs-16"></i>
                <span>Daftar & Cetak Sertifikat</span>
                <span class="badge rounded-pill px-2" :class="activeTab === 'list' ? 'bg-white text-primary' : 'bg-primary-subtle text-primary'">
                  {{ filteredCertificates.length }}
                </span>
              </button>
            </li>
            <li class="nav-item">
              <button 
                class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2 rounded-3"
                :class="{ 'active': activeTab === 'settings' }"
                @click="activeTab = 'settings'"
                type="button"
              >
                <i class="ri-palette-line fs-16"></i>
                <span>Desain & Blangko Panitia</span>
                <span 
                  v-if="templateConfigs.juara.bg || templateConfigs.peserta.bg || templateConfigs.pendamping.bg" 
                  class="badge rounded-pill px-2" 
                  :class="activeTab === 'settings' ? 'bg-white text-warning' : 'bg-warning-subtle text-warning'"
                >
                  Blangko Aktif
                </span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 1: DAFTAR & CETAK SERTIFIKAT           -->
      <!-- ========================================== -->
      <div v-show="activeTab === 'list'">
        <!-- 4 Stat Summary Cards -->
        <div class="row g-3 mb-3.5">
          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 mb-0">
              <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                  <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Total Sertifikat Siap Cetak</span>
                  <h4 class="fs-18 fw-bold text-dark mb-0">{{ summary.total_certificates }}</h4>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle text-primary rounded-3 fs-18">
                    <i class="ri-file-paper-2-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 mb-0">
              <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                  <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Sertifikat Peserta (Siswa)</span>
                  <h4 class="fs-18 fw-bold text-success mb-0">{{ summary.total_students }}</h4>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-success-subtle text-success rounded-3 fs-18">
                    <i class="ri-user-star-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 mb-0">
              <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                  <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Kontingen Sekolah</span>
                  <h4 class="fs-18 fw-bold text-info mb-0">{{ summary.total_schools }}</h4>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-info-subtle text-info rounded-3 fs-18">
                    <i class="ri-community-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 mb-0">
              <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                  <span class="text-muted fs-11 text-uppercase fw-semibold d-block mb-1">Cabang Perlombaan</span>
                  <h4 class="fs-18 fw-bold text-warning mb-0">{{ summary.total_competitions }}</h4>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-3 fs-18">
                    <i class="ri-medal-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Controls Bar -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body p-3">
            <div class="row g-2 align-items-center">
              <!-- Search Box -->
              <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-light border-end-0 text-muted">
                    <i class="ri-search-line"></i>
                  </span>
                  <input 
                    type="text" 
                    class="form-control form-control-sm bg-light border-start-0" 
                    placeholder="Cari penerima, sekolah, NISN, kode..."
                    v-model="searchQuery"
                    @input="currentPage = 1"
                  />
                  <button 
                    v-if="searchQuery" 
                    class="btn btn-light border" 
                    type="button" 
                    @click="searchQuery = ''; currentPage = 1"
                  >
                    <i class="ri-close-line"></i>
                  </button>
                </div>
              </div>

              <!-- Filter Cabang Lomba -->
              <div class="col-6 col-md-3">
                <CustomSelect 
                  v-model="selectedCompetition"
                  :options="competitionOptions"
                  size="sm"
                  @change="currentPage = 1"
                />
              </div>

              <!-- Filter Target -->
              <div class="col-6 col-md-2">
                <CustomSelect 
                  v-model="selectedTarget"
                  :options="targetOptions"
                  size="sm"
                  @change="currentPage = 1"
                />
              </div>

              <!-- Filter Predikat -->
              <div class="col-6 col-md-2">
                <CustomSelect 
                  v-model="selectedRankFilter"
                  :options="rankFilterOptions"
                  size="sm"
                  @change="currentPage = 1"
                />
              </div>

              <!-- Reset Filter Button -->
              <div class="col-6 col-md-1 text-end">
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-secondary w-100"
                  @click="resetFilters"
                  title="Reset seluruh filter"
                >
                  <i class="ri-refresh-line"></i> Reset
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Certificates Table -->
        <div class="card border-0 shadow-sm">
          <div class="card-header border-bottom bg-white d-flex align-items-center justify-content-between p-3">
            <div class="d-flex align-items-center gap-2">
              <h5 class="fs-14 fw-bold text-dark mb-0">Daftar E-Sertifikat & Piagam</h5>
              <span class="badge bg-primary-subtle text-primary fs-11 px-2 py-0.5">
                {{ filteredCertificates.length }} Ditemukan
              </span>
            </div>

            <div class="d-flex align-items-center gap-2">
              <span class="text-muted fs-11 d-none d-sm-inline">Tampilkan:</span>
              <div style="width: 140px;">
                <CustomSelect 
                  v-model="perPage"
                  :options="perPageOptions"
                  size="xs"
                  @change="currentPage = 1"
                />
              </div>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-11 text-uppercase text-muted">
                  <tr>
                    <th style="width: 45px;" class="text-center">#</th>
                    <th style="min-width: 230px;">Penerima Sertifikat</th>
                    <th style="width: 120px;">Kategori</th>
                    <th style="min-width: 180px;">Cabang Perlombaan</th>
                    <th style="min-width: 180px;">Asal Sekolah</th>
                    <th style="width: 170px;">Predikat Piagam</th>
                    <th style="width: 110px;" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="fs-12">
                  <tr v-for="(item, idx) in paginatedCertificates" :key="item.id">
                    <!-- Index -->
                    <td class="text-center text-muted fw-bold">
                      {{ (currentPage - 1) * perPage + idx + 1 }}
                    </td>

                    <!-- Penerima -->
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs flex-shrink-0">
                          <span 
                            class="avatar-title rounded-circle fs-11 fw-bold border"
                            :class="item.target === 'siswa' ? 'bg-primary-subtle text-primary' : (item.target === 'sekolah' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning')"
                          >
                            {{ getInitials(item.recipient_name) }}
                          </span>
                        </div>
                        <div class="min-w-0">
                          <div class="fw-bold text-dark text-truncate" :title="item.recipient_name">
                            {{ item.recipient_name }}
                          </div>
                          <div class="text-muted fs-11 text-truncate" v-if="item.sub_info">
                            {{ item.sub_info }}
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Kategori Target -->
                    <td>
                      <span 
                        v-if="item.target === 'siswa'" 
                        class="badge bg-success-subtle text-success fs-11 px-2 py-1"
                      >
                        <i class="ri-user-star-line me-0.5"></i> Siswa
                      </span>
                      <span 
                        v-else-if="item.target === 'sekolah'" 
                        class="badge bg-info-subtle text-info fs-11 px-2 py-1"
                      >
                        <i class="ri-community-line me-0.5"></i> Sekolah
                      </span>
                      <span 
                        v-else 
                        class="badge bg-warning-subtle text-warning fs-11 px-2 py-1"
                      >
                        <i class="ri-user-settings-line me-0.5"></i> Official
                      </span>
                    </td>

                    <!-- Cabang Lomba -->
                    <td>
                      <span class="badge bg-light text-dark border fs-11 px-2 py-1">
                        {{ item.competition_title }}
                      </span>
                    </td>

                    <!-- Asal Sekolah -->
                    <td>
                      <span class="fw-semibold text-dark">{{ item.school_name }}</span>
                      <small class="text-muted d-block font-monospace fs-10">{{ item.reg_code }}</small>
                    </td>

                    <!-- Predikat Piagam (Editable Dropdown) -->
                    <td>
                      <CustomSelect 
                        v-model="rowRanks[item.id]"
                        :options="predicateRowOptions"
                        size="xs"
                      />
                    </td>

                    <!-- Aksi Cetak -->
                    <td class="text-center">
                      <button 
                        type="button" 
                        class="btn btn-2xs btn-outline-success d-inline-flex align-items-center gap-1 shadow-sm"
                        @click="printSingleCertificate(item)"
                        :title="'Cetak sertifikat untuk ' + item.recipient_name"
                      >
                        <i class="ri-printer-line"></i>
                        <span>Cetak</span>
                      </button>
                    </td>
                  </tr>

                  <!-- Empty State -->
                  <tr v-if="filteredCertificates.length === 0">
                    <td colspan="7" class="text-center py-5">
                      <div class="avatar-md mx-auto mb-3">
                        <span class="avatar-title bg-light text-muted rounded-circle fs-28">
                          <i class="ri-file-search-line"></i>
                        </span>
                      </div>
                      <h6 class="fs-13 fw-bold text-dark mb-1">Tidak Ada Data Sertifikat Ditemukan</h6>
                      <p class="text-muted fs-11 mb-2">
                        Silakan sesuaikan kriteria pencarian atau pilih cabang lomba lain.
                      </p>
                      <button type="button" class="btn btn-sm btn-outline-primary" @click="resetFilters">
                        Reset Filter
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Card Footer & Pagination -->
          <div 
            v-if="totalPages > 1" 
            class="card-footer border-top bg-white p-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2"
          >
            <div class="fs-11 text-muted">
              Menampilkan data {{ (currentPage - 1) * perPage + 1 }} sampai 
              {{ Math.min(currentPage * perPage, filteredCertificates.length) }} dari {{ filteredCertificates.length }} total sertifikat
            </div>

            <div class="d-flex align-items-center gap-1">
              <button 
                type="button" 
                class="btn btn-xs btn-outline-secondary"
                :disabled="currentPage === 1"
                @click="currentPage--"
              >
                <i class="ri-arrow-left-s-line"></i>
              </button>
              <span class="fs-11 px-2 text-dark font-monospace fw-bold">
                {{ currentPage }} / {{ totalPages }}
              </span>
              <button 
                type="button" 
                class="btn btn-xs btn-outline-secondary"
                :disabled="currentPage === totalPages"
                @click="currentPage++"
              >
                <i class="ri-arrow-right-s-line"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 2: DESAIN & BLANGKO KUSTOM PANITIA     -->
      <!-- ========================================== -->
      <div v-show="activeTab === 'settings'">
        <!-- Info Banner -->
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-2.5 p-3 mb-3">
          <div class="fs-20 flex-shrink-0 text-info mt-0.5">
            <i class="ri-information-fill"></i>
          </div>
          <div class="fs-12 text-dark">
            <strong>Sistem Cetak Blangko Kustom Mandiri (Independent Template Printing):</strong>
            Setiap blangko (Juara, Peserta, Pendamping) memiliki pengaturan gambar latar, posisi vertikal teks, jenis font, ukuran, warna, dan sakelar elemen masing-masing secara terpisah tanpa terikat pengaturan global.
          </div>
        </div>

        <!-- Template Selector Bar -->
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body p-2.5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
              <div>
                <span class="fs-13 fw-bold text-dark d-block">
                  <i class="ri-palette-line text-primary me-1"></i> Pilih Template Blangko yang Dikelola:
                </span>
                <small class="text-muted fs-11">Pilih tab di samping untuk mengatur konfigurasi spesifik blangko tersebut secara mandiri.</small>
              </div>
              <div class="btn-group p-1 bg-light rounded-pill border" role="group">
                <button 
                  type="button" 
                  class="btn btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 transition-all"
                  :class="currentEditingTemplate === 'juara' ? 'btn-primary shadow-sm text-white' : 'btn-ghost text-muted'"
                  @click="selectTemplate('juara')"
                >
                  <span>🏆 Blangko Juara</span>
                  <span v-if="templateConfigs.juara.bg" class="badge rounded-pill bg-success-subtle text-success fs-10 px-1.5">Kustom</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 transition-all"
                  :class="currentEditingTemplate === 'peserta' ? 'btn-primary shadow-sm text-white' : 'btn-ghost text-muted'"
                  @click="selectTemplate('peserta')"
                >
                  <span>🎓 Blangko Peserta</span>
                  <span v-if="templateConfigs.peserta.bg" class="badge rounded-pill bg-success-subtle text-success fs-10 px-1.5">Kustom</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm rounded-pill px-3 fw-bold d-flex align-items-center gap-1.5 transition-all"
                  :class="currentEditingTemplate === 'pendamping' ? 'btn-primary shadow-sm text-white' : 'btn-ghost text-muted'"
                  @click="selectTemplate('pendamping')"
                >
                  <span>👔 Blangko Pendamping</span>
                  <span v-if="templateConfigs.pendamping.bg" class="badge rounded-pill bg-success-subtle text-success fs-10 px-1.5">Kustom</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <!-- Left Column: Form Kontrol Upload & Pengaturan -->
          <div class="col-12 col-xl-7">
            <!-- 1. Card Upload Blangko Gambar -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header border-bottom bg-white p-3 d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-image-add-line text-primary"></i>
                    <span>1. Berkas Desain Blangko Latar Belakang</span>
                  </h5>
                  <small class="text-muted fs-11">Rekomendasi ukuran: A4 Landscape (3508 x 2480 px / 300 DPI), Format JPG atau PNG, Maks. 5 MB.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary fs-11">
                  Aktif: {{ currentTemplateLabel }}
                </span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <!-- Blangko Juara -->
                  <div class="col-12 col-md-4">
                    <div 
                      class="border rounded-3 p-2.5 h-100 d-flex flex-column justify-content-between cursor-pointer transition-all"
                      :class="currentEditingTemplate === 'juara' ? 'border-primary border-2 bg-primary-subtle' : 'bg-light'"
                      @click="selectTemplate('juara')"
                    >
                      <div>
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                          <span class="fw-bold fs-12 text-dark">🏆 Blangko Juara</span>
                          <span v-if="templateConfigs.juara.bg" class="badge bg-success-subtle text-success fs-10">Tersedia</span>
                          <span v-else class="badge bg-secondary-subtle text-secondary fs-10">Bawaan</span>
                        </div>
                        <p class="text-muted fs-11 mb-2">Untuk Juara 1, 2, 3, Juara 4, Harapan & Favorit.</p>
                        <div class="blangko-thumb-box mb-2 rounded border bg-white overflow-hidden d-flex align-items-center justify-content-center">
                          <img v-if="templateConfigs.juara.bg" :src="templateConfigs.juara.bg" alt="Blangko Juara" class="img-fluid blangko-thumb" />
                          <div v-else class="text-center p-2 text-muted fs-11">
                            <i class="ri-file-image-line fs-24 d-block mb-1 text-muted"></i>
                            Belum Ada Gambar
                          </div>
                        </div>
                      </div>
                      <div class="d-flex flex-column gap-1.5" @click.stop>
                        <label class="btn btn-2xs btn-outline-primary w-100 mb-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                          <i class="ri-upload-2-line"></i>
                          <span>{{ templateConfigs.juara.bg ? 'Ganti Blangko' : 'Pilih Gambar' }}</span>
                          <input type="file" accept="image/png,image/jpeg,image/jpg" class="d-none" @change="onFileChangeTemplate('juara', $event)" />
                        </label>
                        <button 
                          v-if="templateConfigs.juara.bg" 
                          type="button" 
                          class="btn btn-2xs btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-1"
                          @click="removeBgTemplate('juara')"
                        >
                          <i class="ri-delete-bin-line"></i> Hapus Blangko
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Blangko Peserta -->
                  <div class="col-12 col-md-4">
                    <div 
                      class="border rounded-3 p-2.5 h-100 d-flex flex-column justify-content-between cursor-pointer transition-all"
                      :class="currentEditingTemplate === 'peserta' ? 'border-primary border-2 bg-primary-subtle' : 'bg-light'"
                      @click="selectTemplate('peserta')"
                    >
                      <div>
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                          <span class="fw-bold fs-12 text-dark">🎓 Blangko Peserta</span>
                          <span v-if="templateConfigs.peserta.bg" class="badge bg-success-subtle text-success fs-10">Tersedia</span>
                          <span v-else class="badge bg-secondary-subtle text-secondary fs-10">Bawaan</span>
                        </div>
                        <p class="text-muted fs-11 mb-2">Untuk seluruh peserta siswa & kontingen sekolah.</p>
                        <div class="blangko-thumb-box mb-2 rounded border bg-white overflow-hidden d-flex align-items-center justify-content-center">
                          <img v-if="templateConfigs.peserta.bg" :src="templateConfigs.peserta.bg" alt="Blangko Peserta" class="img-fluid blangko-thumb" />
                          <div v-else class="text-center p-2 text-muted fs-11">
                            <i class="ri-file-image-line fs-24 d-block mb-1 text-muted"></i>
                            Belum Ada Gambar
                          </div>
                        </div>
                      </div>
                      <div class="d-flex flex-column gap-1.5" @click.stop>
                        <label class="btn btn-2xs btn-outline-primary w-100 mb-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                          <i class="ri-upload-2-line"></i>
                          <span>{{ templateConfigs.peserta.bg ? 'Ganti Blangko' : 'Pilih Gambar' }}</span>
                          <input type="file" accept="image/png,image/jpeg,image/jpg" class="d-none" @change="onFileChangeTemplate('peserta', $event)" />
                        </label>
                        <button 
                          v-if="templateConfigs.peserta.bg" 
                          type="button" 
                          class="btn btn-2xs btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-1"
                          @click="removeBgTemplate('peserta')"
                        >
                          <i class="ri-delete-bin-line"></i> Hapus Blangko
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Blangko Pendamping -->
                  <div class="col-12 col-md-4">
                    <div 
                      class="border rounded-3 p-2.5 h-100 d-flex flex-column justify-content-between cursor-pointer transition-all"
                      :class="currentEditingTemplate === 'pendamping' ? 'border-primary border-2 bg-primary-subtle' : 'bg-light'"
                      @click="selectTemplate('pendamping')"
                    >
                      <div>
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                          <span class="fw-bold fs-12 text-dark">👔 Blangko Pendamping</span>
                          <span v-if="templateConfigs.pendamping.bg" class="badge bg-success-subtle text-success fs-10">Tersedia</span>
                          <span v-else class="badge bg-secondary-subtle text-secondary fs-10">Bawaan</span>
                        </div>
                        <p class="text-muted fs-11 mb-2">Untuk guru pendamping, pembina, & official.</p>
                        <div class="blangko-thumb-box mb-2 rounded border bg-white overflow-hidden d-flex align-items-center justify-content-center">
                          <img v-if="templateConfigs.pendamping.bg" :src="templateConfigs.pendamping.bg" alt="Blangko Pendamping" class="img-fluid blangko-thumb" />
                          <div v-else class="text-center p-2 text-muted fs-11">
                            <i class="ri-file-image-line fs-24 d-block mb-1 text-muted"></i>
                            Belum Ada Gambar
                          </div>
                        </div>
                      </div>
                      <div class="d-flex flex-column gap-1.5" @click.stop>
                        <label class="btn btn-2xs btn-outline-primary w-100 mb-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                          <i class="ri-upload-2-line"></i>
                          <span>{{ templateConfigs.pendamping.bg ? 'Ganti Blangko' : 'Pilih Gambar' }}</span>
                          <input type="file" accept="image/png,image/jpeg,image/jpg" class="d-none" @change="onFileChangeTemplate('pendamping', $event)" />
                        </label>
                        <button 
                          v-if="templateConfigs.pendamping.bg" 
                          type="button" 
                          class="btn btn-2xs btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-1"
                          @click="removeBgTemplate('pendamping')"
                        >
                          <i class="ri-delete-bin-line"></i> Hapus Blangko
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Card Toggle Elemen Bawaan -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header border-bottom bg-white p-3 d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-toggle-line text-success"></i>
                    <span>2. Sakelar Tampilan Elemen Bawaan</span>
                  </h5>
                  <small class="text-muted fs-11">Pengaturan elemen bawaan khusus untuk <strong>Blangko {{ currentTemplateLabel }}</strong>.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary fs-11">
                  Blangko {{ currentTemplateLabel }}
                </span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <!-- KOP Surat Toggle -->
                  <div class="col-12 col-md-4">
                    <div class="form-check form-switch p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                      <div>
                        <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchKop">
                          KOP Surat Madrasah
                        </label>
                        <span class="text-muted fs-11">Header & logo resmi</span>
                      </div>
                      <input 
                        class="form-check-input ms-2 fs-18 cursor-pointer" 
                        type="checkbox" 
                        id="switchKop"
                        v-model="activeConfig.show_kop"
                      />
                    </div>
                  </div>

                  <!-- Tanda Tangan Toggle -->
                  <div class="col-12 col-md-4">
                    <div class="form-check form-switch p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                      <div>
                        <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchSignatures">
                          Tanda Tangan & Stempel
                        </label>
                        <span class="text-muted fs-11">Kepala & Ketua Panitia</span>
                      </div>
                      <input 
                        class="form-check-input ms-2 fs-18 cursor-pointer" 
                        type="checkbox" 
                        id="switchSignatures"
                        v-model="activeConfig.show_signatures"
                      />
                    </div>
                  </div>

                  <!-- QR Code Toggle -->
                  <div class="col-12 col-md-4">
                    <div class="form-check form-switch p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                      <div>
                        <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchQr">
                          QR Code Validasi 2D
                        </label>
                        <span class="text-muted fs-11">Modern offline QR</span>
                      </div>
                      <input 
                        class="form-check-input ms-2 fs-18 cursor-pointer" 
                        type="checkbox" 
                        id="switchQr"
                        v-model="activeConfig.show_qr"
                      />
                    </div>
                  </div>
                </div>

                <!-- QR Size & Position Controls -->
                <div v-if="activeConfig.show_qr" class="mt-3 pt-3 border-top">
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label fs-12 fw-semibold text-dark mb-0">Ukuran QR Code Validasi:</label>
                        <span class="badge bg-primary-subtle text-primary font-monospace">{{ activeConfig.qr_size }} px</span>
                      </div>
                      <input 
                        type="range" 
                        class="form-range" 
                        min="40" 
                        max="120" 
                        step="2" 
                        v-model.number="activeConfig.qr_size"
                      />
                    </div>

                    <div class="col-12 col-md-6">
                      <label class="form-label fs-12 fw-semibold text-dark mb-1">Tata Letak / Posisi QR Code:</label>
                      <CustomSelect 
                        v-model="activeConfig.qr_position"
                        :options="qrPositionOptions"
                        size="sm"
                      />
                    </div>

                    <!-- Custom Coordinates if position is 'custom' -->
                    <div v-if="activeConfig.qr_position === 'custom'" class="col-12">
                      <div class="p-2.5 rounded-3 bg-light border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <span class="fs-12 fw-bold text-dark">
                            <i class="ri-drag-move-2-line text-primary me-1"></i> Koordinat Kustom QR Code:
                          </span>
                          <span class="badge bg-info-subtle text-info font-monospace">X: {{ activeConfig.qr_x }}%, Y: {{ activeConfig.qr_y }}%</span>
                        </div>
                        <div class="row g-2">
                          <div class="col-12 col-md-6">
                            <label class="form-label fs-11 text-muted mb-0">Posisi Horizontal (X): {{ activeConfig.qr_x }}%</label>
                            <input 
                              type="range" 
                              class="form-range" 
                              min="5" 
                              max="95" 
                              step="1" 
                              v-model.number="activeConfig.qr_x"
                            />
                          </div>
                          <div class="col-12 col-md-6">
                            <label class="form-label fs-11 text-muted mb-0">Posisi Vertikal (Y): {{ activeConfig.qr_y }}%</label>
                            <input 
                              type="range" 
                              class="form-range" 
                              min="10" 
                              max="95" 
                              step="1" 
                              v-model.number="activeConfig.qr_y"
                            />
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Card Tipografi & Posisi Vertikal Teks -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header border-bottom bg-white p-3 d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-font-size text-warning"></i>
                    <span>3. Tipografi & Tata Letak Teks Dinamis</span>
                  </h5>
                  <small class="text-muted fs-11">Pengaturan posisi dan huruf khusus untuk <strong>Blangko {{ currentTemplateLabel }}</strong>.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary fs-11">
                  Blangko {{ currentTemplateLabel }}
                </span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <!-- Pilihan Font Nama -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">Jenis Font Nama Penerima</label>
                    <CustomSelect 
                      v-model="activeConfig.name_font_family"
                      :options="fontFamilyOptions"
                      size="sm"
                    />
                  </div>

                  <!-- Warna Teks Nama -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">Warna Teks Nama</label>
                    <div class="d-flex align-items-center gap-2">
                      <input 
                        type="color" 
                        class="form-control form-control-color border p-1 rounded-2" 
                        v-model="activeConfig.name_color" 
                        title="Pilih warna teks nama"
                      />
                      <input 
                        type="text" 
                        class="form-control form-control-sm font-monospace text-uppercase" 
                        v-model="activeConfig.name_color" 
                        placeholder="#064e3b"
                        maxlength="7"
                      />
                    </div>
                  </div>

                  <!-- Ukuran Font Nama -->
                  <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label fs-12 fw-semibold text-dark mb-0">Ukuran Font Nama:</label>
                      <span class="badge bg-primary-subtle text-primary font-monospace">{{ activeConfig.name_size }} pt</span>
                    </div>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="16" 
                      max="48" 
                      step="1" 
                      v-model.number="activeConfig.name_size"
                    />
                  </div>

                  <!-- Posisi Vertikal Nama (% dari atas canvas) -->
                  <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label fs-12 fw-semibold text-dark mb-0">Posisi Vertikal Nama (%):</label>
                      <span class="badge bg-info-subtle text-info font-monospace">{{ activeConfig.name_top }}%</span>
                    </div>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="25" 
                      max="70" 
                      step="1" 
                      v-model.number="activeConfig.name_top"
                    />
                  </div>

                  <!-- Posisi Vertikal Predikat (% dari atas canvas) -->
                  <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label fs-12 fw-semibold text-dark mb-0">Posisi Vertikal Predikat (%):</label>
                      <span class="badge bg-info-subtle text-info font-monospace">{{ activeConfig.predicate_top }}%</span>
                    </div>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="35" 
                      max="80" 
                      step="1" 
                      v-model.number="activeConfig.predicate_top"
                    />
                  </div>

                  <!-- Posisi Vertikal Cabang Lomba (% dari atas canvas) -->
                  <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label fs-12 fw-semibold text-dark mb-0">Posisi Vertikal Cabang Lomba (%):</label>
                      <span class="badge bg-info-subtle text-info font-monospace">{{ activeConfig.comp_top }}%</span>
                    </div>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="45" 
                      max="85" 
                      step="1" 
                      v-model.number="activeConfig.comp_top"
                    />
                  </div>

                  <!-- Pemisah Kustomisasi Tipografi Teks Juara / Predikat -->
                  <div class="col-12 mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <h6 class="fs-12 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                        <i class="ri-medal-fill text-warning"></i>
                        <span>Kustomisasi Tipografi Teks Juara / Predikat (Teks Murni)</span>
                      </h6>
                      <span class="badge bg-warning-subtle text-warning fs-10">Tanpa Badge / Kotak</span>
                    </div>
                    <small class="text-muted fs-11 d-block mb-3">Teks juara dicetak sebagai tipografi murni berbaur alami dengan blangko panitia tanpa ornamen pil/badge.</small>
                  </div>

                  <!-- Jenis Font Teks Juara -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">Jenis Font Teks Juara</label>
                    <CustomSelect 
                      v-model="activeConfig.predicate_font_family"
                      :options="predicateFontFamilyOptions"
                      size="sm"
                    />
                  </div>

                  <!-- Ukuran Font Teks Juara -->
                  <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label fs-12 fw-semibold text-dark mb-0">Ukuran Font Teks Juara:</label>
                      <span class="badge bg-warning-subtle text-warning font-monospace">{{ activeConfig.predicate_size }} pt</span>
                    </div>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="10" 
                      max="36" 
                      step="1" 
                      v-model.number="activeConfig.predicate_size"
                    />
                  </div>

                  <!-- Warna Teks Juara -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">Warna Teks Juara</label>
                    <div class="d-flex align-items-center gap-2">
                      <input 
                        type="color" 
                        class="form-control form-control-color border p-1 rounded-2" 
                        v-model="activeConfig.predicate_color" 
                        title="Pilih warna teks juara"
                      />
                      <input 
                        type="text" 
                        class="form-control form-control-sm font-monospace text-uppercase" 
                        v-model="activeConfig.predicate_color" 
                        placeholder="#d97706"
                        maxlength="7"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 4. Card Sakelar & Redaksi Teks Pengantar Dokumen -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header border-bottom bg-white p-3 d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-edit-2-line text-info"></i>
                    <span>4. Sakelar & Redaksi Teks Pengantar Dokumen</span>
                  </h5>
                  <small class="text-muted fs-11">Redaksi kalimat khusus untuk <strong>Blangko {{ currentTemplateLabel }}</strong>.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary fs-11">
                  Blangko {{ currentTemplateLabel }}
                </span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <!-- Opsi 1: Teks "Diberikan Kepada" -->
                  <div class="col-12">
                    <div class="p-2.5 border rounded-3 bg-light">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                          <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchTextGivenTo">
                            1. Teks Pengantar Nama Penerima
                          </label>
                          <span class="text-muted fs-11">Teks di atas nama penerima sertifikat</span>
                        </div>
                        <div class="form-check form-switch mb-0">
                          <input 
                            class="form-check-input fs-18 cursor-pointer" 
                            type="checkbox" 
                            id="switchTextGivenTo"
                            v-model="activeConfig.show_text_given_to"
                          />
                        </div>
                      </div>
                      <div v-if="activeConfig.show_text_given_to" class="mt-2">
                        <label class="form-label fs-11 fw-semibold text-muted mb-1">Redaksi Kalimat Pengantar Nama:</label>
                        <input 
                          type="text" 
                          class="form-control form-control-sm bg-white" 
                          v-model="activeConfig.text_given_to" 
                          placeholder="Diberikan dengan rasa bangga dan apresiasi setinggi-tingginya kepada:"
                        />
                      </div>
                      <div v-else class="text-muted fs-11 fst-italic mt-1">
                        <i class="ri-eye-off-line me-1"></i> Teks pengantar nama disembunyikan (mengikuti desain blangko).
                      </div>
                    </div>
                  </div>

                  <!-- Opsi 2: Teks "Atas Partisipasi dan Prestasinya Sebagai" -->
                  <div class="col-12">
                    <div class="p-2.5 border rounded-3 bg-light">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                          <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchTextPredicate">
                            2. Teks Pengantar Predikat / Kejuaraan
                          </label>
                          <span class="text-muted fs-11">Teks di atas predikat/kejuaraan</span>
                        </div>
                        <div class="form-check form-switch mb-0">
                          <input 
                            class="form-check-input fs-18 cursor-pointer" 
                            type="checkbox" 
                            id="switchTextPredicate"
                            v-model="activeConfig.show_text_predicate"
                          />
                        </div>
                      </div>
                      <div v-if="activeConfig.show_text_predicate" class="mt-2">
                        <label class="form-label fs-11 fw-semibold text-muted mb-1">Redaksi Kalimat Pengantar Predikat:</label>
                        <input 
                          type="text" 
                          class="form-control form-control-sm bg-white" 
                          v-model="activeConfig.text_predicate" 
                          placeholder="Atas partisipasi dan prestasinya sebagai:"
                        />
                      </div>
                      <div v-else class="text-muted fs-11 fst-italic mt-1">
                        <i class="ri-eye-off-line me-1"></i> Teks pengantar predikat disembunyikan (mengikuti desain blangko).
                      </div>
                    </div>
                  </div>

                  <!-- Opsi 3: Teks "Cabang Lomba" -->
                  <div class="col-12">
                    <div class="p-2.5 border rounded-3 bg-light">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                          <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchTextComp">
                            3. Teks Label Cabang Lomba
                          </label>
                          <span class="text-muted fs-11">Label di atas atau sebelum nama cabang lomba</span>
                        </div>
                        <div class="form-check form-switch mb-0">
                          <input 
                            class="form-check-input fs-18 cursor-pointer" 
                            type="checkbox" 
                            id="switchTextComp"
                            v-model="activeConfig.show_text_competition"
                          />
                        </div>
                      </div>
                      <div v-if="activeConfig.show_text_competition" class="mt-2">
                        <label class="form-label fs-11 fw-semibold text-muted mb-1">Redaksi Label Cabang Lomba:</label>
                        <input 
                          type="text" 
                          class="form-control form-control-sm bg-white" 
                          v-model="activeConfig.text_competition" 
                          placeholder="Dalam Cabang Lomba:"
                        />
                      </div>
                      <div v-else class="text-muted fs-11 fst-italic mt-1">
                        <i class="ri-eye-off-line me-1"></i> Label cabang lomba disembunyikan (hanya mencetak nama lombanya saja).
                      </div>
                    </div>
                  </div>

                  <!-- Opsi 4: Keterangan Asal Sekolah -->
                  <div class="col-12">
                    <div class="p-2.5 border rounded-3 bg-light">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                          <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchShowSchool">
                            4. Keterangan Asal Sekolah
                          </label>
                          <span class="text-muted fs-11">Pencetakan asal sekolah/madrasah peserta</span>
                        </div>
                        <div class="form-check form-switch mb-0">
                          <input 
                            class="form-check-input fs-18 cursor-pointer" 
                            type="checkbox" 
                            id="switchShowSchool"
                            v-model="activeConfig.show_school"
                          />
                        </div>
                      </div>
                      <div v-if="activeConfig.show_school" class="mt-2">
                        <label class="form-label fs-11 fw-semibold text-muted mb-1">Label / Awalan Asal Sekolah:</label>
                        <input 
                          type="text" 
                          class="form-control form-control-sm bg-white" 
                          v-model="activeConfig.text_school" 
                          placeholder="Asal Sekolah : (atau kosongkan untuk nama sekolah saja)"
                        />
                      </div>
                      <div v-else class="text-muted fs-11 fst-italic mt-1">
                        <i class="ri-eye-off-line me-1"></i> Asal sekolah disembunyikan (tidak dicetak pada sertifikat).
                      </div>
                    </div>
                  </div>

                  <!-- Opsi 5: Teks Subtitle / Deskripsi Ajang -->
                  <div class="col-12">
                    <div class="p-2.5 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                      <div>
                        <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchEventSub">
                          5. Keterangan Rangkaian Event di Bagian Bawah
                        </label>
                        <span class="text-muted fs-11">Deskripsi ajang Semarak Aliyah resmi di bawah cabang lomba</span>
                      </div>
                      <div class="form-check form-switch mb-0">
                        <input 
                          class="form-check-input fs-18 cursor-pointer" 
                          type="checkbox" 
                          id="switchEventSub"
                          v-model="activeConfig.show_event_subtitle"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. Card Kustomisasi Tanda Tangan & Penandatangan Resmi (Ketua Panitia & Kepala Madrasah) -->
            <div class="card border-0 shadow-sm mb-3">
              <div class="card-header border-bottom bg-white p-3 d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="ri-quill-pen-line text-primary"></i>
                    <span>5. Kustomisasi Tanda Tangan & Penandatangan Resmi</span>
                  </h5>
                  <small class="text-muted fs-11">Atur penandatangan, scan tanda tangan digital transparan, serta stempel untuk <strong>Blangko {{ currentTemplateLabel }}</strong>.</small>
                </div>
                <span class="badge bg-primary-subtle text-primary fs-11">
                  Blangko {{ currentTemplateLabel }}
                </span>
              </div>
              <div class="card-body p-3">
                <!-- Sakelar Utama Tanda Tangan -->
                <div class="form-check form-switch p-2.5 border rounded-3 bg-light d-flex align-items-center justify-content-between mb-3">
                  <div>
                    <label class="form-check-label fw-bold fs-12 text-dark d-block mb-0 cursor-pointer" for="switchMainSignatures">
                      Aktifkan Blok Tanda Tangan Resmi
                    </label>
                    <span class="text-muted fs-11">Tampilkan tanda tangan di lembar sertifikat</span>
                  </div>
                  <input 
                    class="form-check-input ms-2 fs-18 cursor-pointer" 
                    type="checkbox" 
                    id="switchMainSignatures"
                    v-model="activeConfig.show_signatures"
                  />
                </div>

                <div v-if="activeConfig.show_signatures">
                  <div class="row g-3">
                    <!-- Kolom 1: Ketua Panitia Pelaksana -->
                    <div class="col-12 col-md-6">
                      <div class="border rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                          <span class="fw-bold fs-12 text-dark d-flex align-items-center gap-1">
                            <i class="ri-user-star-line text-primary"></i> Ketua Panitia Pelaksana
                          </span>
                          <div class="form-check form-switch mb-0">
                            <input 
                              class="form-check-input fs-16 cursor-pointer" 
                              type="checkbox" 
                              id="switchSigKetua"
                              v-model="activeConfig.show_sig_ketua"
                              title="Tampilkan TTD Ketua Panitia"
                            />
                          </div>
                        </div>

                        <div v-if="activeConfig.show_sig_ketua" class="d-flex flex-column gap-2">
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">Jabatan / Pengantar TTD:</label>
                            <textarea 
                              rows="2" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_ketua_title" 
                              placeholder="Mengetahui,&#10;Ketua Panitia Pelaksana,"
                            ></textarea>
                          </div>
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">Nama Lengkap & Gelar:</label>
                            <input 
                              type="text" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_ketua_name" 
                              placeholder="M. FATHUR RAHMAN, S.Pd."
                            />
                          </div>
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">NIP / Identitas Lainnya:</label>
                            <input 
                              type="text" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_ketua_nip" 
                              placeholder="NIP. - atau kosongkan jika tidak ada"
                            />
                          </div>
                          <!-- Scan TTD Ketua File Upload -->
                          <div class="pt-2 border-top">
                            <label class="form-label fs-11 fw-semibold text-muted mb-1 d-block">Scan Tanda Tangan Digital (PNG Transparan):</label>
                            <div v-if="activeConfig.sig_ketua_image" class="d-flex align-items-center gap-2 mb-2 p-1.5 border rounded bg-light">
                              <img :src="activeConfig.sig_ketua_image" alt="TTD Ketua" style="height: 38px; max-width: 80px; object-fit: contain;" />
                              <button 
                                type="button" 
                                class="btn btn-2xs btn-outline-danger ms-auto"
                                @click="removeSigKetua(currentEditingTemplate)"
                              >
                                <i class="ri-delete-bin-line"></i> Hapus
                              </button>
                            </div>
                            <label class="btn btn-2xs btn-outline-primary w-100 mb-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                              <i class="ri-upload-2-line"></i>
                              <span>{{ activeConfig.sig_ketua_image ? 'Ganti Berkas TTD' : 'Unggah Scan TTD (PNG)' }}</span>
                              <input type="file" accept="image/png,image/jpeg,image/jpg" class="d-none" @change="onFileChangeSigKetua(currentEditingTemplate, $event)" />
                            </label>
                          </div>
                        </div>
                        <div v-else class="text-muted fs-11 fst-italic py-2 text-center">
                          <i class="ri-eye-off-line me-1"></i> TTD Ketua Panitia dinonaktifkan untuk blangko ini.
                        </div>
                      </div>
                    </div>

                    <!-- Kolom 2: Kepala Madrasah -->
                    <div class="col-12 col-md-6">
                      <div class="border rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                          <span class="fw-bold fs-12 text-dark d-flex align-items-center gap-1">
                            <i class="ri-government-line text-success"></i> Kepala Madrasah Aliyah
                          </span>
                          <div class="form-check form-switch mb-0">
                            <input 
                              class="form-check-input fs-16 cursor-pointer" 
                              type="checkbox" 
                              id="switchSigKepala"
                              v-model="activeConfig.show_sig_kepala"
                              title="Tampilkan TTD Kepala Madrasah"
                            />
                          </div>
                        </div>

                        <div v-if="activeConfig.show_sig_kepala" class="d-flex flex-column gap-2">
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">Titimangsa (Kota & Tanggal):</label>
                            <input 
                              type="text" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_kepala_city_date" 
                              placeholder="Balung, 25 Oktober 2026"
                            />
                          </div>
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">Jabatan / Instansi:</label>
                            <textarea 
                              rows="2" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_kepala_title" 
                              placeholder="Kepala MA Unggulan Wahid Hasyim,"
                            ></textarea>
                          </div>
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">Nama Lengkap & Gelar:</label>
                            <input 
                              type="text" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_kepala_name" 
                              placeholder="H. NURUL HUDA, S.Ag., M.Pd.I."
                            />
                          </div>
                          <div>
                            <label class="form-label fs-11 fw-semibold text-muted mb-1">NIP Kepala:</label>
                            <input 
                              type="text" 
                              class="form-control form-control-sm" 
                              v-model="activeConfig.sig_kepala_nip" 
                              placeholder="NIP. 197405122005011003"
                            />
                          </div>
                          <!-- Scan TTD Kepala File Upload -->
                          <div class="pt-2 border-top">
                            <label class="form-label fs-11 fw-semibold text-muted mb-1 d-block">Scan Tanda Tangan Digital (PNG Transparan):</label>
                            <div v-if="activeConfig.sig_kepala_image" class="d-flex align-items-center gap-2 mb-2 p-1.5 border rounded bg-light">
                              <img :src="activeConfig.sig_kepala_image" alt="TTD Kepala" style="height: 38px; max-width: 80px; object-fit: contain;" />
                              <button 
                                type="button" 
                                class="btn btn-2xs btn-outline-danger ms-auto"
                                @click="removeSigKepala(currentEditingTemplate)"
                              >
                                <i class="ri-delete-bin-line"></i> Hapus
                              </button>
                            </div>
                            <label class="btn btn-2xs btn-outline-primary w-100 mb-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                              <i class="ri-upload-2-line"></i>
                              <span>{{ activeConfig.sig_kepala_image ? 'Ganti Berkas TTD' : 'Unggah Scan TTD (PNG)' }}</span>
                              <input type="file" accept="image/png,image/jpeg,image/jpg" class="d-none" @change="onFileChangeSigKepala(currentEditingTemplate, $event)" />
                            </label>
                          </div>
                        </div>
                        <div v-else class="text-muted fs-11 fst-italic py-2 text-center">
                          <i class="ri-eye-off-line me-1"></i> TTD Kepala Madrasah dinonaktifkan untuk blangko ini.
                        </div>
                      </div>
                    </div>

                    <!-- Stempel Resmi Digital -->
                    <div class="col-12">
                      <div class="p-3 border rounded-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <div>
                            <span class="fw-bold fs-12 text-dark d-block">
                              <i class="ri-shield-star-line text-warning me-1"></i> Stempel Resmi Digital Lembaga
                            </span>
                            <small class="text-muted fs-11">Stempel otomatis ditimpa di atas tanda tangan Kepala Madrasah dengan efek rotasi realistis.</small>
                          </div>
                          <div class="form-check form-switch mb-0">
                            <input 
                              class="form-check-input fs-18 cursor-pointer" 
                              type="checkbox" 
                              id="switchStamp"
                              v-model="activeConfig.show_stamp"
                            />
                          </div>
                        </div>

                        <div v-if="activeConfig.show_stamp" class="mt-2 pt-2 border-top">
                          <div class="row g-2 align-items-center">
                            <div class="col-12 col-sm-6">
                              <div v-if="activeConfig.stamp_image" class="d-flex align-items-center gap-2 p-1.5 border rounded bg-white">
                                <img :src="activeConfig.stamp_image" alt="Stempel Digital" style="height: 42px; width: 42px; object-fit: contain;" />
                                <span class="fs-11 text-success fw-semibold">Stempel Terpasang</span>
                                <button 
                                  type="button" 
                                  class="btn btn-2xs btn-outline-danger ms-auto"
                                  @click="removeStamp(currentEditingTemplate)"
                                >
                                  <i class="ri-delete-bin-line"></i> Hapus
                                </button>
                              </div>
                              <span v-else class="text-muted fs-11">Belum ada stempel khusus diunggah.</span>
                            </div>
                            <div class="col-12 col-sm-6">
                              <label class="btn btn-2xs btn-outline-primary w-100 mb-0 cursor-pointer d-flex align-items-center justify-content-center gap-1">
                                <i class="ri-upload-2-line"></i>
                                <span>{{ activeConfig.stamp_image ? 'Ganti File Stempel' : 'Pilih Berkas Stempel (PNG Transparan)' }}</span>
                                <input type="file" accept="image/png,image/jpeg,image/jpg" class="d-none" @change="onFileChangeStamp(currentEditingTemplate, $event)" />
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="p-3 text-center text-muted fs-12 fst-italic">
                  <i class="ri-eye-off-line me-1"></i> Seluruh blok tanda tangan disembunyikan. Blangko Anda dianggap sudah memiliki tanda tangan cetak permanen.
                </div>
              </div>
            </div>

            <!-- Tombol Simpan Pengaturan Mandiri -->
            <div class="d-flex align-items-center justify-content-end gap-2 mb-4">
              <button 
                type="button" 
                class="btn btn-outline-secondary btn-sm px-3"
                @click="activeTab = 'list'"
              >
                Kembali ke Daftar
              </button>
              <button 
                type="button" 
                class="btn btn-primary btn-sm px-4 shadow-sm d-flex align-items-center gap-1.5"
                @click="saveCertificateSettings"
                :disabled="isSaving"
              >
                <i class="ri-save-3-line fs-14"></i>
                <span>{{ isSaving ? 'Menyimpan...' : `Simpan Pengaturan Blangko ${currentTemplateLabel}` }}</span>
              </button>
            </div>
          </div>

          <!-- Right Column: Interactive Live Preview Simulator -->
          <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
              <div class="card-header border-bottom bg-white p-3 d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="fs-14 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5">
                    <i class="ri-eye-line text-info"></i>
                    <span>Pratinjau Langsung (A4 Landscape)</span>
                  </h5>
                  <small class="text-muted fs-11">Simulasi cetak presisi rasio A4</small>
                </div>

                <!-- Preview Switcher Pill -->
                <div class="btn-group btn-group-sm">
                  <button 
                    type="button" 
                    class="btn btn-xs" 
                    :class="previewType === 'juara' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="selectTemplate('juara')"
                  >
                    Juara
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs" 
                    :class="previewType === 'peserta' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="selectTemplate('peserta')"
                  >
                    Peserta
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs" 
                    :class="previewType === 'pendamping' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="selectTemplate('pendamping')"
                  >
                    Pendamping
                  </button>
                </div>
              </div>

              <div class="card-body p-3 bg-light">
                <!-- Dropdown Pemilih Cabang Lomba Nyata dari Database -->
                <div class="mb-2.5" v-if="previewCompetitionOptions.length > 0">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fs-11 fw-semibold text-dark mb-0">Simulasi Cabang Lomba Resmi:</label>
                    <span class="badge bg-primary-subtle text-primary fs-10 font-monospace">{{ previewCompetitionOptions.length }} Lomba Terdaftar</span>
                  </div>
                  <CustomSelect 
                    v-model="previewCompetitionId" 
                    :options="previewCompetitionOptions" 
                    size="xs"
                  />
                </div>

                <!-- Certificate Preview Box (A4 Landscape aspect ratio 297 / 210 = 1.414) -->
                <div 
                  class="cert-preview-canvas shadow rounded overflow-hidden position-relative"
                  :style="{
                    backgroundImage: activePreviewBg ? `url('${activePreviewBg}')` : 'none',
                    backgroundSize: '100% 100%',
                    backgroundRepeat: 'no-repeat',
                    backgroundColor: '#ffffff',
                  }"
                >
                  <!-- Default Border Ornament (only shown if no custom blank is uploaded) -->
                  <div v-if="!activePreviewBg" class="cert-default-border p-2">
                    <div class="border border-warning border-2 h-100 rounded-1 d-flex flex-column justify-content-between p-2">
                      <div class="text-end text-muted font-monospace" style="font-size: 8px;">
                        NO: 014/SA-XIV/2026
                      </div>
                    </div>
                  </div>

                  <!-- KOP Surat Mini Preview -->
                  <div 
                    v-if="previewConfig.show_kop" 
                    class="cert-preview-kop position-absolute start-0 end-0 text-center px-2"
                    style="top: 10px;"
                  >
                    <div class="fw-bold text-dark" style="font-size: 9px; line-height: 1.1;">
                      PANITIA SEMARAK ALIYAH XIV 2026
                    </div>
                    <div class="text-muted" style="font-size: 7px; line-height: 1;">
                      MA UNGGULAN WAHID HASYIM BALUNG - JEMBER
                    </div>
                    <div class="border-bottom border-dark mt-1" style="opacity: 0.25;"></div>
                  </div>

                  <!-- Overlay: Recipient Box (Presisi Sesuai name_top) -->
                  <div 
                    class="position-absolute start-0 end-0 text-center px-2"
                    :style="{
                      top: `${previewConfig.name_top}%`,
                      transform: 'translateY(-50%)',
                      zIndex: 5,
                    }"
                  >
                    <!-- Text Given To -->
                    <div 
                      v-if="previewConfig.show_text_given_to"
                      class="text-muted fw-semibold mb-0.5 text-truncate"
                      style="font-size: 7px; letter-spacing: 0.3px;"
                    >
                      {{ previewConfig.text_given_to }}
                    </div>

                    <!-- Recipient Name -->
                    <div 
                      class="text-truncate"
                      :style="{
                        fontFamily: previewConfig.name_font_family,
                        color: previewConfig.name_color,
                        fontSize: `${Math.round(previewConfig.name_size * 0.45)}px`,
                        fontWeight: 'bold',
                        textShadow: '0 0 2px rgba(255,255,255,0.8)',
                        borderBottom: '1.5px solid #d97706',
                        display: 'inline-block',
                        padding: '0 12px 1px',
                        maxWidth: '90%',
                      }"
                    >
                      {{ previewRecipientName }}
                    </div>

                    <!-- School Name -->
                    <div v-if="previewConfig.show_school" class="text-muted mt-0.5 text-truncate" style="font-size: 6.5px;" :title="previewSchoolName">
                      {{ previewConfig.text_school ? previewConfig.text_school + ' ' : '' }}<strong>{{ previewSchoolName }}</strong>
                    </div>
                  </div>

                  <!-- Overlay: Predicate Box (Presisi Sesuai predicate_top) -->
                  <div 
                    class="position-absolute start-0 end-0 text-center px-2"
                    :style="{
                      top: `${previewConfig.predicate_top}%`,
                      transform: 'translateY(-50%)',
                      zIndex: 5,
                    }"
                  >
                    <div v-if="previewConfig.show_text_predicate" class="text-muted" style="font-size: 6.5px; margin-bottom: 1px;">
                      {{ previewConfig.text_predicate }}
                    </div>
                    <div 
                      class="fw-bold text-uppercase d-inline-block text-truncate" 
                      :style="{
                        fontFamily: previewConfig.predicate_font_family,
                        fontSize: `${Math.round(previewConfig.predicate_size * 0.52)}px`,
                        color: previewConfig.predicate_color,
                        letterSpacing: '0.8px',
                        maxWidth: '92%',
                      }"
                    >
                      SEBAGAI {{ samplePredicate.toUpperCase() }}
                    </div>
                  </div>

                  <!-- Overlay: Competition Box (Presisi Sesuai comp_top) -->
                  <div 
                    class="position-absolute start-0 end-0 text-center px-2"
                    :style="{
                      top: `${previewConfig.comp_top}%`,
                      transform: 'translateY(-50%)',
                      zIndex: 5,
                    }"
                  >
                    <div class="fw-bold text-dark text-truncate" style="font-size: 7.5px;" :title="previewCompetitionTitle">
                      <span v-if="previewConfig.show_text_competition" class="fw-normal text-muted me-1">
                        {{ previewConfig.text_competition }}
                      </span>
                      {{ previewCompetitionTitle.toUpperCase() }}
                    </div>
                    <div v-if="previewConfig.show_event_subtitle" class="text-muted mt-0.5" style="font-size: 5.5px; opacity: 0.85; line-height: 1.2;">
                      Dalam Rangkaian Ajang Perlombaan <strong>"Semarak Aliyah XIV 2026"</strong> se-Jawa Timur oleh MA Unggulan Wahid Hasyim Balung.
                    </div>
                  </div>

                  <!-- Overlay: Signatures Grid (Ketua Panitia, QR Code di Tengah, Kepala Madrasah) -->
                  <div 
                    v-if="previewConfig.show_signatures && (previewConfig.show_sig_ketua || previewConfig.show_sig_kepala)"
                    class="position-absolute d-flex justify-content-between align-items-end px-3"
                    style="bottom: 8px; left: 0; right: 0; z-index: 6;"
                  >
                    <!-- Kolom TTD Ketua Panitia -->
                    <div 
                      :style="{
                        visibility: previewConfig.show_sig_ketua ? 'visible' : 'hidden',
                        width: '100px',
                        textAlign: 'center',
                      }"
                    >
                      <div class="text-muted" style="font-size: 5px; line-height: 1.2; white-space: pre-line; min-height: 13px;">
                        {{ previewConfig.sig_ketua_title }}
                      </div>
                      <div style="height: 22px; display: flex; align-items: center; justify-content: center;">
                        <img 
                          v-if="previewConfig.sig_ketua_image" 
                          :src="previewConfig.sig_ketua_image" 
                          alt="TTD Ketua" 
                          style="max-height: 22px; max-width: 60px; object-fit: contain;" 
                        />
                      </div>
                      <div class="fw-bold text-dark border-bottom border-dark d-inline-block text-truncate" style="font-size: 6px; line-height: 1.1; max-width: 100%;">
                        {{ previewConfig.sig_ketua_name }}
                      </div>
                      <div class="text-muted" style="font-size: 4.8px; line-height: 1;">
                        {{ previewConfig.sig_ketua_nip }}
                      </div>
                    </div>

                    <!-- QR Code di Tengah Bawah (Jika mode center_bottom) -->
                    <div 
                      v-if="previewConfig.show_qr && previewConfig.qr_position === 'center_bottom'"
                      class="bg-white border rounded p-1 d-flex flex-column align-items-center justify-content-center shadow-sm"
                      :style="{
                        width: `${Math.round(previewConfig.qr_size * 0.42)}px`,
                        height: `${Math.round(previewConfig.qr_size * 0.42)}px`,
                        marginBottom: '1px',
                      }"
                    >
                      <i class="ri-qr-code-line text-dark" :style="{ fontSize: `${Math.round(previewConfig.qr_size * 0.28)}px` }"></i>
                      <span style="font-size: 4px; line-height: 1;" class="text-muted text-uppercase fw-semibold mt-0.5">Verifikasi</span>
                    </div>
                    <div v-else style="width: 25px;"></div>

                    <!-- Kolom TTD Kepala Madrasah -->
                    <div 
                      :style="{
                        visibility: previewConfig.show_sig_kepala ? 'visible' : 'hidden',
                        width: '100px',
                        textAlign: 'center',
                        position: 'relative',
                      }"
                    >
                      <!-- Stempel Digital Overlay -->
                      <img 
                        v-if="previewConfig.show_stamp && previewConfig.stamp_image" 
                        :src="previewConfig.stamp_image" 
                        alt="Stempel" 
                        style="position: absolute; left: -8px; top: 6px; width: 34px; height: 34px; object-fit: contain; pointer-events: none; opacity: 0.85; transform: rotate(-8deg); z-index: 3;" 
                      />
                      <div class="text-muted" style="font-size: 5px; line-height: 1.1; margin-bottom: 1px;">
                        {{ previewConfig.sig_kepala_city_date }}
                      </div>
                      <div class="text-muted" style="font-size: 5px; line-height: 1.2; white-space: pre-line; min-height: 13px;">
                        {{ previewConfig.sig_kepala_title }}
                      </div>
                      <div style="height: 22px; display: flex; align-items: center; justify-content: center;">
                        <img 
                          v-if="previewConfig.sig_kepala_image" 
                          :src="previewConfig.sig_kepala_image" 
                          alt="TTD Kepala" 
                          style="max-height: 22px; max-width: 60px; object-fit: contain; position: relative; z-index: 2;" 
                        />
                      </div>
                      <div class="fw-bold text-dark border-bottom border-dark d-inline-block text-truncate" style="font-size: 6px; line-height: 1.1; position: relative; z-index: 2; max-width: 100%;">
                        {{ previewConfig.sig_kepala_name }}
                      </div>
                      <div class="text-muted" style="font-size: 4.8px; line-height: 1; position: relative; z-index: 2;">
                        {{ previewConfig.sig_kepala_nip }}
                      </div>
                    </div>
                  </div>

                  <!-- QR Code Mini Bebas / Sudut (Jika posisi bukan center_bottom) -->
                  <div 
                    v-if="previewConfig.show_qr && previewConfig.qr_position !== 'center_bottom'" 
                    class="position-absolute bg-white border rounded p-1 d-flex flex-column align-items-center justify-content-center shadow-sm"
                    :style="{
                      width: `${Math.round(previewConfig.qr_size * 0.42)}px`,
                      height: `${Math.round(previewConfig.qr_size * 0.42)}px`,
                      zIndex: 10,
                      ...(previewConfig.qr_position === 'left_bottom' ? { bottom: '8px', left: '12px' } : {}),
                      ...(previewConfig.qr_position === 'right_bottom' ? { bottom: '8px', right: '12px' } : {}),
                      ...(previewConfig.qr_position === 'custom' ? { left: `${previewConfig.qr_x}%`, top: `${previewConfig.qr_y}%`, transform: 'translate(-50%, -50%)' } : {}),
                    }"
                  >
                    <i class="ri-qr-code-line text-dark" :style="{ fontSize: `${Math.round(previewConfig.qr_size * 0.28)}px` }"></i>
                    <span style="font-size: 4px; line-height: 1;" class="text-muted text-uppercase fw-semibold mt-0.5">Verifikasi</span>
                  </div>
                </div>

                <!-- Status indicator -->
                <div class="mt-3 d-flex align-items-center justify-content-between fs-11 text-muted">
                  <div class="d-flex align-items-center gap-1">
                    <span 
                      class="d-inline-block rounded-circle" 
                      :class="activePreviewBg ? 'bg-success' : 'bg-secondary'"
                      style="width: 8px; height: 8px;"
                    ></span>
                    <span>Status Blangko {{ currentTemplateLabel }}: <strong>{{ activePreviewBg ? 'Kustom Terpasang' : 'Template Standar' }}</strong></span>
                  </div>
                  <span class="font-monospace">Font: {{ previewConfig.name_font_family }} | {{ previewConfig.predicate_font_family }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </VelzonLayout>
</template>

<style scoped>
.blangko-thumb-box {
  height: 95px;
  background-color: #f8fafc;
}
.blangko-thumb {
  max-height: 100%;
  width: auto;
  object-fit: contain;
}

.cert-preview-canvas {
  width: 100%;
  aspect-ratio: 1.414; /* 297mm x 210mm A4 ratio */
  border: 1px solid #d1d5db;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.cert-default-border {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
}

:deep([data-layout-mode="dark"]) .card {
  background-color: #1a1d21;
  border-color: #2a2f34;
}

:deep([data-layout-mode="dark"]) .card-header,
:deep([data-layout-mode="dark"]) .card-footer {
  background-color: #1e2227 !important;
  border-color: #2a2f34 !important;
}

:deep([data-layout-mode="dark"]) .table-light {
  background-color: #212529 !important;
  color: #f8fafc !important;
}

:deep([data-layout-mode="dark"]) .text-dark {
  color: #f8fafc !important;
}

:deep([data-layout-mode="dark"]) .bg-light {
  background-color: #212529 !important;
}

:deep([data-layout-mode="dark"]) .border {
  border-color: #32383e !important;
}

:deep([data-layout-mode="dark"]) .blangko-thumb-box {
  background-color: #212529 !important;
  border-color: #32383e !important;
}

:deep([data-layout-mode="dark"]) .cert-preview-canvas {
  border-color: #3b4249 !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-primary-subtle {
  background-color: rgba(59, 130, 246, 0.2) !important;
  color: #93c5fd !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-success-subtle {
  background-color: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-warning-subtle {
  background-color: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-danger-subtle {
  background-color: rgba(239, 68, 68, 0.2) !important;
  color: #fca5a5 !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-info-subtle {
  background-color: rgba(14, 165, 233, 0.2) !important;
  color: #7dd3fc !important;
}

:deep([data-layout-mode="dark"]) .alert-info {
  background-color: rgba(14, 165, 233, 0.15) !important;
  color: #e0f2fe !important;
}

:deep([data-layout-mode="dark"]) .alert-info .text-dark {
  color: #e0f2fe !important;
}
</style>


