<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  competitions: { type: Array, default: () => [] },
  activeComp: { type: Object, default: () => null },
  participants: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  selectedCompId: { type: String, default: '' },
  sectionSettings: { 
    type: Object, 
    default: () => ({
      tag: 'Agenda Pelaksanaan',
      title: 'Jadwal Technical Meeting & Pelaksanaan',
      desc: 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.',
      guidelines_active: true,
      guidelines: [],
      is_active: true,
    }) 
  },
});

const toast = useToast();

// Local list of participants for instant reactivity
const participantsList = ref([...props.participants]);
watch(() => props.participants, (newVal) => {
  participantsList.value = newVal ? [...newVal] : [];
}, { deep: true });

// Filters & Search
const searchQuery = ref('');
const filterStatus = ref('all'); // all, drawn, undrawn
const compSearchQuery = ref('');

// Filtered Competitions for Sidebar/Dropdown
const filteredCompetitions = computed(() => {
  if (!compSearchQuery.value) return props.competitions;
  const q = compSearchQuery.value.toLowerCase();
  return props.competitions.filter(c => 
    c.title.toLowerCase().includes(q) || 
    c.id.toLowerCase().includes(q) ||
    (c.category && c.category.toLowerCase().includes(q))
  );
});

// Helper to get resolved TM settings from competition
function getResolvedSettings(comp) {
  if (!comp) return {};
  return comp.resolved_tm_settings || comp.tm_settings || {};
}

const initialSettings = getResolvedSettings(props.activeComp);

// TM Settings Form
const tmForm = useForm({
  tm_date: initialSettings.tm_date || '26/10/2026',
  tm_time: initialSettings.tm_time || (props.activeComp?.id === 'mobile-legends' ? '14:00 WIB' : '08:00 WIB'),
  location: initialSettings.location || 'Aula Utama Madrasah Lt. 2',
  meeting_type: initialSettings.meeting_type || (props.activeComp?.id === 'mobile-legends' ? 'hybrid' : 'offline'),
  meeting_link: initialSettings.meeting_link || '',
  whatsapp_group: initialSettings.whatsapp_group || '',
  status: initialSettings.status || 'upcoming',
  pic_name: initialSettings.pic_name || '',
  pic_contact: initialSettings.pic_contact || '',
  agenda: initialSettings.agenda || '',
  notes: initialSettings.notes || '',
  rules_summary: initialSettings.rules_summary || '',
});

// Update form when activeComp changes
watch(() => props.activeComp, (comp) => {
  if (comp) {
    const s = getResolvedSettings(comp);
    tmForm.tm_date = s.tm_date || '26/10/2026';
    tmForm.tm_time = s.tm_time || (comp.id === 'mobile-legends' ? '14:00 WIB' : '08:00 WIB');
    tmForm.location = s.location || 'Aula Utama Madrasah Lt. 2';
    tmForm.meeting_type = s.meeting_type || (comp.id === 'mobile-legends' ? 'hybrid' : 'offline');
    tmForm.meeting_link = s.meeting_link || '';
    tmForm.whatsapp_group = s.whatsapp_group || '';
    tmForm.status = s.status || 'upcoming';
    tmForm.pic_name = s.pic_name || '';
    tmForm.pic_contact = s.pic_contact || '';
    tmForm.agenda = s.agenda || '';
    tmForm.notes = s.notes || '';
    tmForm.rules_summary = s.rules_summary || '';
  }
}, { deep: true });

// Explicit sync button action to re-fetch and re-populate from Competition data
function syncFromCompetitionData() {
  if (!props.activeComp) return;
  const s = getResolvedSettings(props.activeComp);
  tmForm.tm_date = s.tm_date || '26/10/2026';
  tmForm.tm_time = s.tm_time || (props.activeComp.id === 'mobile-legends' ? '14:00 WIB' : '08:00 WIB');
  tmForm.location = s.location || 'Aula Utama Madrasah Lt. 2';
  tmForm.meeting_type = s.meeting_type || (props.activeComp.id === 'mobile-legends' ? 'hybrid' : 'offline');
  tmForm.pic_name = s.pic_name || '';
  tmForm.pic_contact = s.pic_contact || '';
  tmForm.agenda = s.agenda || '';
  tmForm.rules_summary = s.rules_summary || '';
  toast.success('Data TM berhasil disinkronkan ulang dengan data Manajemen Lomba!');
}

function selectCompetition(compId) {
  router.visit(`/admin/tm?comp=${compId}`, {
    preserveState: true,
    preserveScroll: true,
  });
}

// Submit TM Settings
function submitTmSettings() {
  if (!props.activeComp) return;

  tmForm.post(`/admin/tm/${props.activeComp.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Pengaturan TM untuk cabang ${props.activeComp.title} berhasil disimpan!`);
    },
    onError: () => {
      toast.error('Gagal menyimpan pengaturan TM. Periksa isian form.');
    }
  });
}

// Draw Random Machine
const isDrawing = ref(false);
const showDrawModal = ref(false);
const showResetModal = ref(false);

function triggerRandomDraw() {
  if (!props.activeComp) return;
  if (participantsList.value.length === 0) {
    toast.error('Tidak ada delegasi terdaftar pada cabang lomba ini untuk diundi.');
    return;
  }

  isDrawing.value = true;
  router.post(`/admin/tm/${props.activeComp.id}/draw`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      isDrawing.value = false;
      showDrawModal.value = false;
      toast.success(`Pengundian nomor urut TM untuk ${props.activeComp.title} berhasil diacak!`);
    },
    onError: () => {
      isDrawing.value = false;
      toast.error('Gagal melakukan pengundian nomor urut.');
    }
  });
}

function triggerResetDraw() {
  if (!props.activeComp) return;

  router.post(`/admin/tm/${props.activeComp.id}/reset`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      showResetModal.value = false;
      toast.success(`Seluruh nomor urut undian TM cabang ${props.activeComp.title} berhasil direset.`);
    },
    onError: () => {
      toast.error('Gagal mereset nomor undian.');
    }
  });
}

// Manual Edit Draw Number Modal
const showEditOrderModal = ref(false);
const activeEditParticipant = ref(null);
const editOrderForm = useForm({
  registration_id: null,
  draw_number: null,
  draw_notes: '',
});

function openEditOrderModal(p) {
  activeEditParticipant.value = p;
  editOrderForm.registration_id = p.id;
  editOrderForm.draw_number = p.draw_number;
  editOrderForm.draw_notes = p.draw_notes || '';
  showEditOrderModal.value = true;
}

function submitEditOrder() {
  if (!props.activeComp || !editOrderForm.registration_id) return;

  editOrderForm.post(`/admin/tm/${props.activeComp.id}/order`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditOrderModal.value = false;
      toast.success('Nomor undian peserta berhasil diperbarui.');
    },
    onError: () => {
      toast.error('Gagal memperbarui nomor undian peserta.');
    }
  });
}

// Filtered Participants
const filteredParticipants = computed(() => {
  let list = [...participantsList.value];

  if (filterStatus.value === 'drawn') {
    list = list.filter(p => p.draw_number !== null && p.draw_number !== undefined);
  } else if (filterStatus.value === 'undrawn') {
    list = list.filter(p => p.draw_number === null || p.draw_number === undefined);
  }

  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(p => 
      p.school_name.toLowerCase().includes(q) ||
      p.reg_code.toLowerCase().includes(q) ||
      (p.participants_names && p.participants_names.toLowerCase().includes(q)) ||
      (p.mentor_name && p.mentor_name.toLowerCase().includes(q))
    );
  }

  return list;
});

// Count of drawn
const drawnCount = computed(() => {
  return participantsList.value.filter(p => p.draw_number !== null && p.draw_number !== undefined).length;
});

// Print Modal
const showPrintModal = ref(false);

function printDrawSheet() {
  window.print();
}

// Section & Guidelines Customization Modal State
const showSectionModal = ref(false);

const defaultGuidelinesPresets = [
  {
    title: 'Delegasi Resmi',
    desc: 'Setiap madrasah/sekolah wajib mengutus minimal 1 guru pembina atau kapten/ketua tim.',
    icon: 'ri-user-star-line',
    color: '#8b5cf6',
  },
  {
    title: 'Verifikasi Berkas',
    desc: 'Membawa cetak bukti registrasi & Kartu Tanda Siswa (KTS) / delegasi resmi untuk validasi.',
    icon: 'ri-file-shield-line',
    color: '#f97316',
  },
  {
    title: 'Akses Hybrid (Zoom)',
    desc: 'Tersedia tautan Zoom Meeting khusus bagi delegasi luar daerah yang berhalangan hadir offline.',
    icon: 'ri-vidicon-line',
    color: '#10b981',
  },
  {
    title: 'Tepat Waktu & Tertib',
    desc: 'Hadir 15 menit sebelum pembukaan dimulai, mengenakan seragam rapi atau jas almamater.',
    icon: 'ri-time-line',
    color: '#ec4899',
  },
];

const sectionForm = useForm({
  tag: props.sectionSettings?.tag || 'Agenda Pelaksanaan',
  title: props.sectionSettings?.title || 'Jadwal Technical Meeting & Pelaksanaan',
  desc: props.sectionSettings?.desc || 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.',
  guidelines_active: props.sectionSettings?.guidelines_active ?? true,
  is_active: props.sectionSettings?.is_active ?? true,
  guidelines: (props.sectionSettings?.guidelines && props.sectionSettings.guidelines.length > 0)
    ? JSON.parse(JSON.stringify(props.sectionSettings.guidelines))
    : JSON.parse(JSON.stringify(defaultGuidelinesPresets)),
});

// Watch sectionSettings updates from server
watch(() => props.sectionSettings, (newVal) => {
  if (newVal) {
    sectionForm.tag = newVal.tag || 'Agenda Pelaksanaan';
    sectionForm.title = newVal.title || 'Jadwal Technical Meeting & Pelaksanaan';
    sectionForm.desc = newVal.desc || '';
    sectionForm.guidelines_active = newVal.guidelines_active ?? true;
    sectionForm.is_active = newVal.is_active ?? true;
    if (newVal.guidelines && Array.isArray(newVal.guidelines) && newVal.guidelines.length > 0) {
      sectionForm.guidelines = JSON.parse(JSON.stringify(newVal.guidelines));
    }
  }
}, { deep: true });

onMounted(() => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    if (params.get('modal') === 'panduan' || params.get('kustomisasi') === '1') {
      showSectionModal.value = true;
    }
  }
});

function addGuidelineCard() {
  sectionForm.guidelines.push({
    title: 'Poin Baru',
    desc: 'Keterangan atau panduan pelaksanaan...',
    icon: 'ri-information-line',
    color: '#0ea5e9',
  });
}

function removeGuidelineCard(index) {
  sectionForm.guidelines.splice(index, 1);
}

function moveGuidelineCard(index, direction) {
  const targetIndex = index + direction;
  if (targetIndex < 0 || targetIndex >= sectionForm.guidelines.length) return;
  const temp = sectionForm.guidelines[index];
  sectionForm.guidelines[index] = sectionForm.guidelines[targetIndex];
  sectionForm.guidelines[targetIndex] = temp;
}

function resetGuidelinesToDefault() {
  sectionForm.guidelines = JSON.parse(JSON.stringify(defaultGuidelinesPresets));
  toast.info('Daftar kartu panduan telah di-reset ke pengaturan standar.');
}

function submitSectionSettings() {
  sectionForm.post('/admin/tm-settings', {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Pengaturan seksi & panduan TM berhasil disimpan!');
      showSectionModal.value = false;
    },
    onError: () => {
      toast.error('Gagal menyimpan pengaturan. Silakan periksa isian formulir.');
    }
  });
}

const colorPresets = [
  '#8b5cf6', // purple
  '#f97316', // orange
  '#10b981', // green
  '#ec4899', // pink
  '#0ea5e9', // blue
  '#6366f1', // indigo
  '#f59e0b', // amber
  '#ef4444', // red
];

const iconPresets = [
  { icon: 'ri-user-star-line', label: 'Delegasi' },
  { icon: 'ri-file-shield-line', label: 'Berkas' },
  { icon: 'ri-vidicon-line', label: 'Zoom/Video' },
  { icon: 'ri-time-line', label: 'Waktu' },
  { icon: 'ri-shield-check-line', label: 'Tata Tertib' },
  { icon: 'ri-map-pin-line', label: 'Lokasi' },
  { icon: 'ri-award-line', label: 'Juara' },
  { icon: 'ri-information-line', label: 'Info' },
];

function hexToRgbaPreview(hex, alpha = 0.15) {
  if (!hex) return '';
  if (hex.startsWith('rgb')) return hex;
  let c = hex.replace('#', '');
  if (c.length === 3) {
    c = c.split('').map(x => x + x).join('');
  }
  if (c.length === 6) {
    const num = parseInt(c, 16);
    return `rgba(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, ${alpha})`;
  }
  return hex;
}
</script>

<template>
  <Head :title="`Manajemen TM ${activeComp ? '- ' + activeComp.title : ''} | Semarak Aliyah XIV`" />

  <VelzonLayout>
    <!-- ============================================================== -->
    <!-- BREADCRUMB & PAGE TITLE                                        -->
    <!-- ============================================================== -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-white px-4 py-3 rounded-3 shadow-sm border border-light">
          <div>
            <h4 class="mb-sm-0 text-dark fw-bold fs-18">
              <i class="ri-calendar-todo-line text-info me-2 align-middle"></i> Manajemen Technical Meeting (TM)
            </h4>
            <div class="text-muted fs-12 mt-1">
              Kelola jadwal temu teknis, ruang koordinasi, notulensi, dan pengundian nomor urut peserta lomba.
            </div>
          </div>
          <div class="page-title-right mt-3 mt-sm-0 d-flex align-items-center gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
              @click="showSectionModal = true"
              title="Kustomisasi Judul, Deskripsi & Banner Panduan TM di Halaman Depan Web"
            >
              <i class="ri-palette-line"></i> Kustomisasi Seksi & Panduan Web
            </button>
            <Link 
              href="/admin/lomba" 
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 shadow-sm"
              title="Kembali ke halaman Manajemen Lomba"
            >
              <i class="ri-arrow-left-line"></i> Manajemen Lomba
            </Link>
            <button 
              type="button" 
              class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1 shadow-sm"
              @click="showPrintModal = true"
              :disabled="!activeComp || participantsList.length === 0"
              title="Cetak Berita Acara & Daftar Nomor Urut TM"
            >
              <i class="ri-printer-line"></i> Cetak Hasil Undian TM
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- KPI SUMMARY METRICS CARDS                                      -->
    <!-- ============================================================== -->
    <div class="row g-3 mb-4">
      <!-- 1. Total Cabang Lomba -->
      <div class="col-xl-3 col-sm-6">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Total Cabang Lomba</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">
                  {{ stats.total_competitions || competitions.length }}
                  <span class="fs-12 text-muted fw-normal">Cabang</span>
                </h4>
                <span class="badge bg-primary-subtle text-primary fs-11 mt-2">
                  <i class="ri-medal-line align-middle me-1"></i> Edisi XIV 2026
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-primary-subtle text-primary rounded fs-20">
                  <i class="ri-trophy-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Status Jadwal TM -->
      <div class="col-xl-3 col-sm-6">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Status TM Menunggu</p>
                <h4 class="fs-22 fw-bold mb-0 text-warning">
                  {{ stats.scheduled_count || 0 }}
                  <span class="fs-12 text-muted fw-normal">Menunggu TM</span>
                </h4>
                <span class="badge bg-warning-subtle text-warning fs-11 mt-2">
                  <i class="ri-time-line align-middle me-1"></i> Terjadwal
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-warning-subtle text-warning rounded fs-20">
                  <i class="ri-calendar-event-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. TM Sedang Berlangsung / Selesai -->
      <div class="col-xl-3 col-sm-6">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">TM Selesai Dilaksanakan</p>
                <h4 class="fs-22 fw-bold mb-0 text-success">
                  {{ stats.completed_count || 0 }}
                  <span class="fs-12 text-muted fw-normal">/ {{ stats.total_competitions || competitions.length }} Cabang</span>
                </h4>
                <span class="badge bg-success-subtle text-success fs-11 mt-2">
                  <i class="ri-checkbox-circle-line align-middle me-1"></i> Nomor Urut Sah
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                  <i class="ri-check-double-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Total Delegasi Terundi -->
      <div class="col-xl-3 col-sm-6">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1">Delegasi Memiliki No. Undian</p>
                <h4 class="fs-22 fw-bold mb-0 text-info">
                  {{ stats.total_drawed || 0 }}
                  <span class="fs-12 text-muted fw-normal">/ {{ stats.total_participants || 0 }} Delegasi</span>
                </h4>
                <div class="progress mt-2" style="height: 5px; width: 140px;">
                  <div 
                    class="progress-bar bg-info" 
                    role="progressbar" 
                    :style="{ width: `${stats.total_participants > 0 ? Math.round((stats.total_drawed / stats.total_participants) * 100) : 0}%` }"
                  ></div>
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-info-subtle text-info rounded fs-20">
                  <i class="ri-shuffle-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- COMPETITION SELECTOR TABS & SEARCH                             -->
    <!-- ============================================================== -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body py-2.5 px-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
          <!-- Competition pills horizontal scroll -->
          <div class="d-flex align-items-center gap-1.5 overflow-x-auto py-1 flex-grow-1" style="scrollbar-width: thin;">
            <button
              v-for="comp in filteredCompetitions"
              :key="comp.id"
              type="button"
              @click="selectCompetition(comp.id)"
              class="btn btn-sm text-nowrap rounded-pill d-inline-flex align-items-center gap-1.5 px-3"
              :class="activeComp?.id === comp.id ? 'btn-info text-white shadow-sm fw-bold' : 'btn-outline-light text-dark bg-light'"
            >
              <span>{{ comp.title }}</span>
              <span 
                class="badge rounded-pill fs-10"
                :class="activeComp?.id === comp.id ? 'bg-white text-info' : 'bg-secondary text-white'"
              >
                {{ comp.registrations_count ?? comp.current_quota ?? 0 }}
              </span>
            </button>
          </div>

          <!-- Quick Competition Search Filter -->
          <div class="position-relative" style="width: 220px;">
            <input 
              v-model="compSearchQuery" 
              type="text" 
              class="form-control form-control-sm ps-4" 
              placeholder="Cari cabang lomba..."
            />
            <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-12"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- CARD: DATA TERSINKRON DARI MANAJEMEN LOMBA                    -->
    <!-- ============================================================== -->
    <div v-if="activeComp" class="card border-0 shadow-sm mb-4 overflow-hidden">
      <div class="card-header bg-primary-subtle py-2.5 px-3 border-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary text-white fs-11">
            <i class="ri-refresh-line align-middle me-1"></i> Data Tersinkron dari Manajemen Lomba
          </span>
          <span class="text-dark fw-bold fs-13">{{ activeComp.title }}</span>
          <span class="badge bg-light text-muted border font-mono fs-10">{{ activeComp.id }}</span>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button 
            type="button" 
            @click="syncFromCompetitionData" 
            class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm"
            title="Tarik & sinkronkan ulang data terbaru dari Manajemen Lomba"
          >
            <i class="ri-refresh-line"></i> Sinkronkan Ulang
          </button>
          <Link 
            :href="`/admin/lomba`" 
            class="btn btn-xs btn-primary d-inline-flex align-items-center gap-1 shadow-sm"
            title="Buka dan ubah cabang lomba ini di Manajemen Lomba"
          >
            <i class="ri-edit-line"></i> Edit di Manajemen Lomba
          </Link>
          <Link 
            :href="`/admin/form-builder/${activeComp.id}`" 
            class="btn btn-xs btn-warning text-dark d-inline-flex align-items-center gap-1 shadow-sm"
            title="Kelola formulir pendaftaran dinamis lomba ini"
          >
            <i class="ri-survey-line"></i> Form Builder
          </Link>
        </div>
      </div>

      <div class="card-body bg-white py-3 px-4">
        <div class="row g-3">
          <!-- Item 1: Bidang & Format -->
          <div class="col-md-3 col-sm-6">
            <span class="text-muted fs-11 d-block text-uppercase fw-semibold mb-1">Bidang & Format Lomba</span>
            <div class="d-flex align-items-center gap-1.5 flex-wrap">
              <span class="badge bg-primary-subtle text-primary fs-11 text-uppercase fw-semibold">
                {{ activeComp.category_label || activeComp.category }}
              </span>
              <span class="badge bg-info-subtle text-info fs-11">
                {{ activeComp.participant_type === 'tim' ? 'Tim / Regu' : 'Individu' }}
              </span>
            </div>
            <small class="text-muted fs-10 d-block mt-1 truncate" :title="activeComp.participants_rule">
              {{ activeComp.participants_rule || '1 Delegasi' }}
            </small>
          </div>

          <!-- Item 2: Kuota & Pendaftar Terisi -->
          <div class="col-md-3 col-sm-6">
            <span class="text-muted fs-11 d-block text-uppercase fw-semibold mb-1">Keterisian Kuota</span>
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold fs-14 text-dark font-mono">
                {{ activeComp.registrations_count ?? activeComp.current_quota ?? 0 }} / {{ activeComp.max_quota }}
              </span>
              <span 
                class="badge fs-10"
                :class="(activeComp.registrations_count ?? activeComp.current_quota ?? 0) >= activeComp.max_quota ? 'bg-danger text-white' : 'bg-success-subtle text-success'"
              >
                {{ (activeComp.registrations_count ?? activeComp.current_quota ?? 0) >= activeComp.max_quota ? 'Penuh' : 'Tersedia' }}
              </span>
            </div>
            <div class="progress mt-1.5" style="height: 4px; max-width: 140px;">
              <div 
                class="progress-bar"
                :class="(activeComp.registrations_count ?? activeComp.current_quota ?? 0) >= activeComp.max_quota ? 'bg-danger' : 'bg-primary'"
                :style="{ width: `${Math.min(100, Math.round(((activeComp.registrations_count ?? activeComp.current_quota ?? 0) / (activeComp.max_quota || 1)) * 100))}%` }"
              ></div>
            </div>
          </div>

          <!-- Item 3: Biaya & Batas Waktu -->
          <div class="col-md-3 col-sm-6">
            <span class="text-muted fs-11 d-block text-uppercase fw-semibold mb-1">Biaya & Batas Pendaftaran</span>
            <div class="fw-bold text-dark fs-13">
              {{ activeComp.fee > 0 ? `Rp ${Number(activeComp.fee).toLocaleString('id-ID')}` : 'Gratis' }}
            </div>
            <small class="text-muted fs-11 d-block mt-0.5">
              <i class="ri-calendar-event-line text-primary me-0.5"></i> Tutup: {{ activeComp.registration_deadline || '-' }}
            </small>
          </div>

          <!-- Item 4: Koordinator / PJ Lomba -->
          <div class="col-md-3 col-sm-6">
            <span class="text-muted fs-11 d-block text-uppercase fw-semibold mb-1">Koordinator Lomba</span>
            <div class="fw-bold text-dark fs-12 truncate" :title="activeComp.contact_person">
              {{ (activeComp.coordinators && activeComp.coordinators.length > 0 && activeComp.coordinators[0].name) ? activeComp.coordinators[0].name : (activeComp.contact_person || 'Panitia Pelaksana') }}
            </div>
            <small v-if="(activeComp.coordinators && activeComp.coordinators[0]?.contact) || activeComp.contact_person" class="text-muted fs-11 d-block mt-0.5">
              <i class="ri-whatsapp-line text-success me-0.5"></i> {{ activeComp.coordinators?.[0]?.contact || activeComp.contact_person }}
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2-COLUMN MAIN CONTENT: LEFT (FORM TM) & RIGHT (UNDIAN)         -->
    <!-- ============================================================== -->
    <div class="row g-4" v-if="activeComp">
      <!-- ============================================================ -->
      <!-- LEFT COLUMN: PENGATURAN & KETENTUAN TM                       -->
      <!-- ============================================================ -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
              <i class="ri-settings-4-line text-info me-1"></i> Pengaturan & Pelaksanaan TM
            </h5>
            <div class="d-flex align-items-center gap-1.5">
              <button 
                type="button" 
                class="btn btn-xs btn-outline-secondary" 
                @click="syncFromCompetitionData" 
                title="Tarik nilai default dari Manajemen Lomba"
              >
                <i class="ri-refresh-line"></i> Reset ke Data Lomba
              </button>
              <span class="badge bg-light text-muted border fs-11">{{ activeComp.id }}</span>
            </div>
          </div>

          <div class="card-body">
            <form @submit.prevent="submitTmSettings">
              <!-- 1. Status TM -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Status Pelaksanaan TM <span class="text-danger">*</span></label>
                <div class="btn-group w-100" role="group">
                  <input 
                    type="radio" 
                    class="btn-check" 
                    name="tm_status" 
                    id="status_upcoming" 
                    value="upcoming" 
                    v-model="tmForm.status"
                  />
                  <label class="btn btn-sm btn-outline-warning" for="status_upcoming">
                    <i class="ri-time-line"></i> Menunggu
                  </label>

                  <input 
                    type="radio" 
                    class="btn-check" 
                    name="tm_status" 
                    id="status_ongoing" 
                    value="ongoing" 
                    v-model="tmForm.status"
                  />
                  <label class="btn btn-sm btn-outline-primary" for="status_ongoing">
                    <i class="ri-broadcast-line"></i> Berlangsung
                  </label>

                  <input 
                    type="radio" 
                    class="btn-check" 
                    name="tm_status" 
                    id="status_completed" 
                    value="completed" 
                    v-model="tmForm.status"
                  />
                  <label class="btn btn-sm btn-outline-success" for="status_completed">
                    <i class="ri-check-line"></i> Selesai
                  </label>
                </div>
              </div>

              <!-- 2. Tanggal & Jam TM -->
              <div class="row g-2 mb-3">
                <div class="col-7">
                  <label class="form-label fs-12 fw-semibold text-dark">Tanggal TM</label>
                  <input 
                    v-model="tmForm.tm_date" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="Contoh: 12/10/2026"
                  />
                  <small class="text-muted fs-10">Format: DD/MM/YYYY</small>
                </div>
                <div class="col-5">
                  <label class="form-label fs-12 fw-semibold text-dark">Jam / Waktu</label>
                  <input 
                    v-model="tmForm.tm_time" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="08:00 WIB"
                  />
                  <small class="text-muted fs-10">Contoh: 08:00 WIB</small>
                </div>
              </div>

              <!-- 3. Jenis Pelaksanaan TM -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Format Pertemuan</label>
                <div class="d-flex align-items-center gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" value="offline" id="type_offline" v-model="tmForm.meeting_type">
                    <label class="form-check-label fs-12" for="type_offline">
                      Tatap Muka (Offline)
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" value="online" id="type_online" v-model="tmForm.meeting_type">
                    <label class="form-check-label fs-12" for="type_online">
                      Online (Virtual)
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" value="hybrid" id="type_hybrid" v-model="tmForm.meeting_type">
                    <label class="form-check-label fs-12" for="type_hybrid">
                      Hybrid
                    </label>
                  </div>
                </div>
              </div>

              <!-- 4. Ruang / Tempat TM -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Lokasi / Tempat TM</label>
                <input 
                  v-model="tmForm.location" 
                  type="text" 
                  class="form-control form-control-sm" 
                  placeholder="Contoh: Aula Utama Madrasah Lt. 2 / Lab Bahasa"
                />
              </div>

              <!-- 5. Link Virtual Meeting (Zoom / GMeet) -->
              <div class="mb-3" v-if="tmForm.meeting_type === 'online' || tmForm.meeting_type === 'hybrid'">
                <label class="form-label fs-12 fw-semibold text-dark">Tautan Zoom / Google Meet</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text"><i class="ri-video-chat-line"></i></span>
                  <input 
                    v-model="tmForm.meeting_link" 
                    type="url" 
                    class="form-control" 
                    placeholder="https://meet.google.com/..."
                  />
                  <a 
                    v-if="tmForm.meeting_link" 
                    :href="tmForm.meeting_link" 
                    target="_blank" 
                    class="btn btn-outline-secondary"
                    title="Uji buka link meeting"
                  >
                    <i class="ri-external-link-line"></i>
                  </a>
                </div>
              </div>

              <!-- 6. Link WhatsApp Group TM / Official -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Tautan Grup WhatsApp Official / TM</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text text-success"><i class="ri-whatsapp-line"></i></span>
                  <input 
                    v-model="tmForm.whatsapp_group" 
                    type="url" 
                    class="form-control" 
                    placeholder="https://chat.whatsapp.com/..."
                  />
                  <a 
                    v-if="tmForm.whatsapp_group" 
                    :href="tmForm.whatsapp_group" 
                    target="_blank" 
                    class="btn btn-outline-success"
                    title="Uji buka grup WA"
                  >
                    <i class="ri-whatsapp-line"></i>
                  </a>
                </div>
                <small class="text-muted fs-10">Tautan grup WhatsApp koordinasi untuk delegasi cabang lomba ini.</small>
              </div>

              <!-- 7. Penanggung Jawab (PIC TM) & Kontak -->
              <div class="row g-2 mb-3">
                <div class="col-7">
                  <label class="form-label fs-12 fw-semibold text-dark">PIC / Pimpinan TM</label>
                  <input 
                    v-model="tmForm.pic_name" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="Nama Koordinator"
                  />
                </div>
                <div class="col-5">
                  <label class="form-label fs-12 fw-semibold text-dark">No. WhatsApp</label>
                  <input 
                    v-model="tmForm.pic_contact" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="0812..."
                  />
                </div>
              </div>

              <!-- 8. Agenda Utama TM (Tampil di Web Publik) -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fs-12 fw-semibold text-dark mb-0">Agenda Utama TM</label>
                  <span class="badge bg-primary-subtle text-primary fs-10">Tampil di Web Publik</span>
                </div>
                <textarea 
                  v-model="tmForm.agenda" 
                  rows="2" 
                  class="form-control form-control-sm" 
                  placeholder="Contoh: Drawing bagan pertandingan sistem gugur, verifikasi berkas pemain, penegasan regulasi resmi..."
                ></textarea>
                <small class="text-muted fs-10">Agenda ini akan langsung ditampilkan pada kartu jadwal TM di halaman publik web.</small>
              </div>

              <!-- 9. Notulensi & Ketentuan Teknis TM -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Notulensi / Kesepakatan Khusus TM</label>
                <textarea 
                  v-model="tmForm.notes" 
                  rows="3" 
                  class="form-control form-control-sm" 
                  placeholder="Catatan hasil diskusi TM, toleransi waktu keterlambatan, kostum, atau perlengkapan yang wajib dibawa..."
                ></textarea>
              </div>

              <!-- 9. Ringkasan Tata Tertib Lomba -->
              <div class="mb-4">
                <label class="form-label fs-12 fw-semibold text-dark">Ringkasan Tata Tertib & Format Lomba</label>
                <textarea 
                  v-model="tmForm.rules_summary" 
                  rows="2" 
                  class="form-control form-control-sm" 
                  placeholder="Sistem gugur tunggal, durasi penampilan 7 menit, kriteria penilaian dewan juri..."
                ></textarea>
              </div>

              <!-- Tombol Simpan -->
              <button 
                type="submit" 
                class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-1 shadow-sm py-2"
                :disabled="tmForm.processing"
              >
                <i class="ri-save-line fs-14"></i>
                <span class="fw-semibold">Simpan Pengaturan TM</span>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- RIGHT COLUMN: MESIN PENGUNDIAN NOMOR URUT (DRAW MACHINE)     -->
      <!-- ============================================================ -->
      <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div>
                <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                  <i class="ri-shuffle-line text-info me-1"></i> Pengundian Nomor Urut Peserta
                </h5>
                <span class="text-muted fs-11">
                  {{ drawnCount }} dari {{ participantsList.length }} delegasi telah mendapatkan nomor urut tampil / bagan
                </span>
              </div>

              <!-- Action Buttons for Draw -->
              <div class="d-flex align-items-center gap-2">
                <button 
                  type="button" 
                  class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1 shadow-sm"
                  @click="showDrawModal = true"
                  :disabled="participantsList.length === 0"
                  title="Kocok nomor undian secara acak untuk semua delegasi"
                >
                  <i class="ri-shuffle-line"></i> Kocok Acak (Otomatis)
                </button>
                <button 
                  v-if="drawnCount > 0"
                  type="button" 
                  class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                  @click="showResetModal = true"
                  title="Kosongkan seluruh nomor undian"
                >
                  <i class="ri-refresh-line"></i> Reset
                </button>
              </div>
            </div>

            <!-- Filters and Search Inside Card Header -->
            <div class="row align-items-center g-2 mt-2 pt-2 border-top">
              <div class="col-sm-6">
                <div class="btn-group btn-group-sm w-100" role="group">
                  <button 
                    type="button" 
                    class="btn"
                    :class="filterStatus === 'all' ? 'btn-secondary text-white' : 'btn-outline-secondary'"
                    @click="filterStatus = 'all'"
                  >
                    Semua ({{ participantsList.length }})
                  </button>
                  <button 
                    type="button" 
                    class="btn"
                    :class="filterStatus === 'drawn' ? 'btn-info text-white' : 'btn-outline-info'"
                    @click="filterStatus = 'drawn'"
                  >
                    Sudah Undi ({{ drawnCount }})
                  </button>
                  <button 
                    type="button" 
                    class="btn"
                    :class="filterStatus === 'undrawn' ? 'btn-warning text-dark' : 'btn-outline-warning'"
                    @click="filterStatus = 'undrawn'"
                  >
                    Belum ({{ participantsList.length - drawnCount }})
                  </button>
                </div>
              </div>

              <div class="col-sm-6">
                <div class="position-relative">
                  <input 
                    v-model="searchQuery" 
                    type="text" 
                    class="form-control form-control-sm ps-4" 
                    placeholder="Cari sekolah, no. reg, nama..."
                  />
                  <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-12"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Body: Participants List -->
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top text-dark">
                  <tr class="fs-11 text-uppercase" style="letter-spacing: 0.5px;">
                    <th scope="col" style="width: 80px;" class="text-center py-2.5">No. Undian</th>
                    <th scope="col" class="py-2.5">Delegasi & Asal Sekolah</th>
                    <th scope="col" class="py-2.5">Status Bayar</th>
                    <th scope="col" class="py-2.5">Catatan / Undian</th>
                    <th scope="col" style="width: 80px;" class="text-center py-2.5">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredParticipants.length === 0">
                    <td colspan="5" class="text-center py-5 text-muted fs-13">
                      <i class="ri-inbox-line fs-28 d-block mb-1 text-muted"></i>
                      Tidak ada delegasi yang sesuai kriteria pencarian / filter.
                    </td>
                  </tr>

                  <tr v-for="p in filteredParticipants" :key="p.id">
                    <!-- Nomor Undian Badge / Number -->
                    <td class="text-center">
                      <span 
                        v-if="p.draw_number" 
                        class="badge bg-info text-white fs-13 fw-bold px-2.5 py-1 rounded-pill shadow-sm"
                        style="min-width: 40px;"
                        title="Nomor Urut Tampil Resmi"
                      >
                        #{{ p.draw_number }}
                      </span>
                      <span 
                        v-else 
                        class="badge bg-light text-muted border fs-11 px-2 py-0.5"
                        title="Belum diundi"
                      >
                        -
                      </span>
                    </td>

                    <!-- Delegasi Info -->
                    <td>
                      <div class="fw-bold text-dark fs-13">{{ p.school_name }}</div>
                      <div class="text-muted fs-11">
                        <span class="badge bg-light text-muted border fs-9 font-mono me-1">#{{ p.reg_code }}</span>
                        <span class="text-dark">{{ p.participants_names ? p.participants_names.split(',')[0] : 'Delegasi' }}</span>
                      </div>
                      <small v-if="p.mentor_name" class="text-muted fs-10 d-block">
                        Pembina: {{ p.mentor_name }} ({{ p.mentor_wa || '-' }})
                      </small>
                    </td>

                    <!-- Status Pembayaran -->
                    <td>
                      <span 
                        class="badge fs-10 px-2 py-0.5"
                        :class="p.payment_status === 'Terverifikasi' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'"
                      >
                        {{ p.payment_status }}
                      </span>
                    </td>

                    <!-- Catatan Undian -->
                    <td>
                      <span class="text-dark fs-12">{{ p.draw_notes || (p.draw_number ? `Nomor Urut ${p.draw_number}` : 'Menunggu Pengundian') }}</span>
                    </td>

                    <!-- Aksi -->
                    <td class="text-center">
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="openEditOrderModal(p)"
                        title="Atur nomor urut secara manual"
                      >
                        <i class="ri-edit-line"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card-footer bg-white border-top py-2.5 px-3 d-flex align-items-center justify-content-between">
            <span class="text-muted fs-12">
              Menampilkan {{ filteredParticipants.length }} delegasi
            </span>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-info-subtle text-info fs-11">
                {{ drawnCount }} Terundi
              </span>
              <span class="badge bg-light text-muted border fs-11">
                {{ participantsList.length - drawnCount }} Belum
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: KONFIRMASI KOCOK ACAK (RANDOM DRAW)                     -->
    <!-- ============================================================== -->
    <div v-if="showDrawModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-info text-white border-0 py-3">
            <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2">
              <i class="ri-shuffle-line"></i> Kocok Nomor Urut TM Otomatis
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showDrawModal = false"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <div class="avatar-lg bg-info-subtle text-info rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3">
              <i class="ri-shuffle-line fs-32"></i>
            </div>
            <h5 class="fw-bold text-dark fs-16 mb-2">Acak Nomor Urut Peserta?</h5>
            <p class="text-muted fs-13 mb-3">
              Sistem akan mengacak urutan tampil/tanding untuk <strong>{{ participantsList.length }} delegasi</strong> pada cabang lomba <strong>{{ activeComp?.title }}</strong> secara acak (Fisher-Yates shuffle algorithm).
            </p>
            <div class="alert alert-warning text-start fs-12 py-2 px-3 mb-0">
              <i class="ri-alert-line me-1"></i> Jika peserta sebelumnya sudah memiliki nomor undian, nomor lama akan ditimpa dengan hasil kocokan baru.
            </div>
          </div>
          <div class="modal-footer bg-light border-0 py-2.5">
            <button type="button" class="btn btn-sm btn-secondary" @click="showDrawModal = false" :disabled="isDrawing">
              Batal
            </button>
            <button type="button" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1 px-3" @click="triggerRandomDraw" :disabled="isDrawing">
              <i class="ri-shuffle-line" :class="{ 'animate-spin': isDrawing }"></i>
              <span>{{ isDrawing ? 'Mengacak...' : 'Ya, Kocok Sekarang' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: RESET NOMOR UNDIAN                                      -->
    <!-- ============================================================== -->
    <div v-if="showResetModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-danger text-white border-0 py-2.5">
            <h5 class="modal-title fs-14 fw-bold d-flex align-items-center gap-1">
              <i class="ri-alert-line"></i> Reset Nomor Undian
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showResetModal = false"></button>
          </div>
          <div class="modal-body p-3 text-center">
            <p class="text-muted fs-13 mb-0">
              Yakin ingin mengosongkan seluruh nomor urut undian untuk cabang <strong>{{ activeComp?.title }}</strong>?
            </p>
          </div>
          <div class="modal-footer bg-light border-0 py-2">
            <button type="button" class="btn btn-xs btn-secondary" @click="showResetModal = false">Batal</button>
            <button type="button" class="btn btn-xs btn-danger" @click="triggerResetDraw">Ya, Reset</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: EDIT MANUAL NOMOR URUT PESERTA                          -->
    <!-- ============================================================== -->
    <div v-if="showEditOrderModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); z-index: 1055;">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light border-bottom py-2.5">
            <h5 class="modal-title fs-14 fw-bold text-dark">
              Atur Nomor Undian Manual
            </h5>
            <button type="button" class="btn-close" @click="showEditOrderModal = false"></button>
          </div>
          <form @submit.prevent="submitEditOrder">
            <div class="modal-body p-3">
              <div class="mb-2">
                <span class="fs-11 text-muted d-block">Nama Kontingen:</span>
                <span class="fs-13 fw-bold text-dark">{{ activeEditParticipant?.school_name }}</span>
              </div>
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Nomor Undian / Tampil</label>
                <input 
                  v-model.number="editOrderForm.draw_number" 
                  type="number" 
                  min="1" 
                  max="999"
                  class="form-control form-control-sm" 
                  placeholder="Contoh: 1"
                  required
                />
              </div>
              <div class="mb-2">
                <label class="form-label fs-12 fw-semibold text-dark">Keterangan / Pool</label>
                <input 
                  v-model="editOrderForm.draw_notes" 
                  type="text" 
                  class="form-control form-control-sm" 
                  placeholder="Contoh: Pool A / Urutan Tampil 1"
                />
              </div>
            </div>
            <div class="modal-footer bg-light border-0 py-2">
              <button type="button" class="btn btn-xs btn-secondary" @click="showEditOrderModal = false">Batal</button>
              <button type="submit" class="btn btn-xs btn-primary" :disabled="editOrderForm.processing">Simpan Nomor</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: CETAK BERITA ACARA & DAFTAR UNDIAN TM (PRINT VIEW)      -->
    <!-- ============================================================== -->
    <div v-if="showPrintModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.7); z-index: 1060;">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-2xl">
          <div class="modal-header bg-dark text-white border-0 py-2.5 px-4 d-flex align-items-center justify-content-between">
            <h5 class="modal-title fs-14 fw-bold d-flex align-items-center gap-2">
              <i class="ri-printer-line"></i> Pratinjau Berita Acara & Daftar Nomor Undian TM
            </h5>
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1" @click="printDrawSheet">
                <i class="ri-printer-line"></i> Cetak Dokumen
              </button>
              <button type="button" class="btn-close btn-close-white" @click="showPrintModal = false"></button>
            </div>
          </div>

          <div class="modal-body p-4 bg-white" id="printable-tm-sheet" style="color: #000; font-family: 'Times New Roman', Times, serif;">
            <!-- Letterhead KOP Madrasah -->
            <div class="text-center pb-3 mb-3 border-bottom border-dark border-2">
              <h5 class="fw-bold mb-0 text-uppercase fs-16" style="letter-spacing: 1px;">PANITIA PELAKSANA SEMARAK ALIYAH XIV 2026</h5>
              <h4 class="fw-bold mb-0 text-uppercase fs-18">MA UNGGULAN WAHID HASYIM BALUNG</h4>
              <p class="mb-0 fs-11 text-muted" style="font-family: sans-serif;">
                Jl. Balung - Tutul No. 09, Balung, Kabupaten Jember, Jawa Timur • Website: semarak.ma-wh.sch.id
              </p>
            </div>

            <!-- Title Document -->
            <div class="text-center mb-3">
              <h6 class="fw-bold text-uppercase fs-14 mb-1 text-decoration-underline">BERITA ACARA & DAFTAR NOMOR UNDIAN TECHNICAL MEETING</h6>
              <span class="fs-12 fw-bold text-uppercase d-block">CABANG LOMBA: {{ activeComp?.title }}</span>
            </div>

            <!-- Meeting Info Summary -->
            <table class="table table-sm table-borderless fs-12 mb-3" style="width: auto;">
              <tbody>
                <tr>
                  <td style="width: 130px; font-weight: bold;">Hari, Tanggal TM</td>
                  <td style="width: 15px;">:</td>
                  <td>{{ tmForm.tm_date || '-' }} • {{ tmForm.tm_time || '-' }}</td>
                </tr>
                <tr>
                  <td style="font-weight: bold;">Tempat / Ruang</td>
                  <td>:</td>
                  <td>{{ tmForm.location || '-' }} ({{ tmForm.meeting_type.toUpperCase() }})</td>
                </tr>
                <tr>
                  <td style="font-weight: bold;">Pimpinan / Koordinator</td>
                  <td>:</td>
                  <td>{{ tmForm.pic_name || 'Panitia Cabang Lomba' }} ({{ tmForm.pic_contact || '-' }})</td>
                </tr>
              </tbody>
            </table>

            <!-- Table of Draw Numbers -->
            <table class="table table-bordered table-sm text-center align-middle fs-11 mb-4" style="border-color: #000;">
              <thead>
                <tr class="table-light" style="border-color: #000;">
                  <th style="width: 50px; font-weight: bold;">NO.</th>
                  <th style="width: 90px; font-weight: bold;">NO. UNDIAN</th>
                  <th style="font-weight: bold; text-align: left;">NAMA LEMBAGA / KONTINGEN</th>
                  <th style="font-weight: bold; text-align: left;">NAMA PESERTA / TIM</th>
                  <th style="width: 110px; font-weight: bold;">KODE REG</th>
                  <th style="width: 120px; font-weight: bold;">PARAF OFFICIAL</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(p, idx) in participantsList" :key="p.id" style="border-color: #000;">
                  <td>{{ idx + 1 }}</td>
                  <td class="fw-bold fs-13">
                    {{ p.draw_number ? '#' + p.draw_number : '-' }}
                  </td>
                  <td class="text-start fw-semibold">{{ p.school_name }}</td>
                  <td class="text-start">{{ p.participants_names ? p.participants_names.split(',').slice(0, 2).join(', ') : '-' }}</td>
                  <td class="font-mono fs-10">{{ p.reg_code }}</td>
                  <td style="height: 35px;"></td>
                </tr>
              </tbody>
            </table>

            <!-- Signatures -->
            <div class="row pt-3 text-center fs-12">
              <div class="col-6">
                <p class="mb-5">Mengetahui,<br>Ketua Panitia Pelaksana</p>
                <p class="fw-bold text-decoration-underline mb-0">H. Mohammad Zaini, M.Pd</p>
                <small class="text-muted">NIP. 198504122010011008</small>
              </div>
              <div class="col-6">
                <p class="mb-5">Balung, {{ tmForm.tm_date || '26 Oktober 2026' }}<br>Koordinator Cabang Lomba</p>
                <p class="fw-bold text-decoration-underline mb-0">{{ tmForm.pic_name || 'Koordinator Lomba' }}</p>
                <small class="text-muted">{{ activeComp?.title }}</small>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-0 py-2.5 px-4">
            <button type="button" class="btn btn-sm btn-secondary" @click="showPrintModal = false">Tutup</button>
            <button type="button" class="btn btn-sm btn-info text-white d-inline-flex align-items-center gap-1" @click="printDrawSheet">
              <i class="ri-printer-line"></i> Cetak Lembar Ini
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: KUSTOMISASI SEKSI & PANDUAN TM WEB PUBLIK               -->
    <!-- ============================================================== -->
    <div 
      v-if="showSectionModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      style="background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1060;"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <!-- Modal Header -->
          <div class="modal-header bg-light border-bottom py-3 px-4">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-xs flex-shrink-0">
                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16">
                  <i class="ri-settings-4-line"></i>
                </span>
              </div>
              <div>
                <h5 class="modal-title fw-bold fs-16 mb-0 text-dark">
                  Kustomisasi Seksi & Banner Panduan TM (Web Publik)
                </h5>
                <p class="fs-12 text-muted mb-0">
                  Sesuaikan badge, judul, deskripsi, serta kartu panduan penting jadwal TM di halaman depan.
                </p>
              </div>
            </div>
            <button 
              type="button" 
              class="btn-close" 
              @click="showSectionModal = false"
              :disabled="sectionForm.processing"
            ></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-4">
            <!-- Notice Banner -->
            <div class="alert alert-info-subtle border-info-subtle d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
              <div class="d-flex align-items-center gap-3">
                <i class="ri-information-fill fs-22 text-info"></i>
                <div>
                  <div class="fw-bold fs-13 text-info">Pengaturan Tampilan Live Portal</div>
                  <div class="fs-12 text-muted">
                    Semua perubahan teks dan kartu panduan di bawah ini akan langsung tampil secara dinamis pada landing page tanpa perlu edit file code.
                  </div>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge" :class="sectionForm.is_active ? 'bg-success' : 'bg-secondary'">
                  Seksi: {{ sectionForm.is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
                <span class="badge" :class="sectionForm.guidelines_active ? 'bg-primary' : 'bg-secondary'">
                  Banner: {{ sectionForm.guidelines_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
              </div>
            </div>

            <!-- SECTION 1: HEADER & TOGGLES -->
            <div class="card border border-light-subtle shadow-none rounded-3 mb-4">
              <div class="card-header bg-light-subtle py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold fs-13 text-dark">
                  <i class="ri-text me-1 text-primary"></i> 1. Pengaturan Teks Header & Visibilitas Seksi
                </span>
                <span class="fs-11 text-muted">Bagian atas sebelum jadwal cabang lomba</span>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <!-- Toggles -->
                  <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-white h-100 d-flex align-items-center justify-content-between">
                      <div>
                        <div class="fw-bold fs-13 text-dark mb-0.5">Tampilkan Seksi Jadwal & TM</div>
                        <div class="fs-11 text-muted">Jika dinonaktifkan, seluruh seksi jadwal tidak akan muncul di web publik</div>
                      </div>
                      <div class="form-check form-switch form-switch-md mb-0">
                        <input 
                          type="checkbox" 
                          class="form-check-input cursor-pointer" 
                          id="tmSectionActiveSwitch" 
                          v-model="sectionForm.is_active"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="p-3 border rounded-3 bg-white h-100 d-flex align-items-center justify-content-between">
                      <div>
                        <div class="fw-bold fs-13 text-dark mb-0.5">Tampilkan Banner Kartu Panduan</div>
                        <div class="fs-11 text-muted">Banner 4 kartu poin penting (Delegasi Resmi, Verifikasi, dll.)</div>
                      </div>
                      <div class="form-check form-switch form-switch-md mb-0">
                        <input 
                          type="checkbox" 
                          class="form-check-input cursor-pointer" 
                          id="tmGuidelinesActiveSwitch" 
                          v-model="sectionForm.guidelines_active"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Tag & Title -->
                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-muted mb-1">
                      Badge / Tag Seksi <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="text" 
                      class="form-control form-control-sm" 
                      v-model="sectionForm.tag" 
                      placeholder="Contoh: Agenda Pelaksanaan"
                    />
                    <div class="form-text fs-11">Teks kecil di pill atas judul seksi.</div>
                  </div>

                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-muted mb-1">
                      Judul Utama Seksi <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="text" 
                      class="form-control form-control-sm" 
                      v-model="sectionForm.title" 
                      placeholder="Contoh: Jadwal Technical Meeting & Pelaksanaan"
                    />
                    <div class="form-text fs-11">Judul besar seksi jadwal TM di portal.</div>
                  </div>

                  <!-- Description -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted mb-1">
                      Deskripsi Pengantar Seksi
                    </label>
                    <textarea 
                      class="form-control form-control-sm" 
                      rows="2" 
                      v-model="sectionForm.desc" 
                      placeholder="Tuliskan petunjuk atau catatan pengantar untuk peserta dan guru pendamping..."
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 2: LIVE PREVIEW -->
            <div class="card border border-light-subtle shadow-none rounded-3 mb-4 overflow-hidden">
              <div class="card-header bg-dark text-white py-2 px-3 d-flex align-items-center justify-content-between">
                <span class="fs-12 fw-bold">
                  <i class="ri-eye-line me-1 text-warning"></i> Pratinjau Langsung (Live Preview di Halaman Depan)
                </span>
                <span class="badge bg-white-subtle text-white fs-10">Realtime</span>
              </div>
              <div class="card-body p-4 bg-light text-center">
                <!-- If section hidden -->
                <div v-if="!sectionForm.is_active" class="alert alert-secondary py-3 mb-0">
                  <i class="ri-eye-off-line me-1"></i> Seksi Jadwal & TM dinonaktifkan (disembunyikan dari web publik).
                </div>

                <div v-else>
                  <!-- Section Tag -->
                  <div class="mb-2">
                    <span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.25); font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">
                      <i class="ri-calendar-event-fill text-warning me-1"></i> {{ sectionForm.tag || 'AGENDA PELAKSANAAN' }}
                    </span>
                  </div>

                  <!-- Section Title -->
                  <h3 class="fw-bold fs-20 text-dark mb-1">
                    {{ sectionForm.title || 'Jadwal Technical Meeting & Pelaksanaan' }}
                  </h3>
                  <p class="text-muted fs-12 mb-4 mx-auto" style="max-width: 650px;">
                    {{ sectionForm.desc || 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.' }}
                  </p>

                  <!-- Guidelines Banner Preview -->
                  <div v-if="sectionForm.guidelines_active && sectionForm.guidelines.length > 0" class="p-3 rounded-4 bg-white shadow-sm border position-relative overflow-hidden text-start">
                    <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #ec4899, #8b5cf6, #3b82f6, #10b981);"></div>
                    <div class="row g-3">
                      <div 
                        v-for="(item, idx) in sectionForm.guidelines" 
                        :key="idx" 
                        class="col-12 col-md-6 col-lg-3"
                      >
                        <div class="d-flex align-items-start gap-2.5">
                          <div 
                            class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            :style="{
                              width: '38px',
                              height: '38px',
                              color: item.color || '#8b5cf6',
                              background: hexToRgbaPreview(item.color || '#8b5cf6', 0.12),
                              border: `1px solid ${hexToRgbaPreview(item.color || '#8b5cf6', 0.28)}`,
                              fontSize: '18px'
                            }"
                          >
                            <i :class="item.icon || 'ri-information-line'"></i>
                          </div>
                          <div>
                            <h6 class="fw-bold fs-13 text-dark mb-1">{{ item.title || 'Judul Kartu' }}</h6>
                            <p class="text-muted fs-11 mb-0 lh-sm">{{ item.desc || 'Keterangan ringkas...' }}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-else-if="!sectionForm.guidelines_active" class="alert alert-secondary py-2 mb-0 fs-12">
                    <i class="ri-eye-off-line me-1"></i> Banner kartu panduan disembunyikan. Hanya daftar jadwal per cabang yang tampil.
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 3: GUIDELINES CARDS REPEATER -->
            <div class="card border border-light-subtle shadow-none rounded-3 mb-0">
              <div class="card-header bg-light-subtle py-2.5 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-bold fs-13 text-dark">
                    <i class="ri-layout-grid-line me-1 text-primary"></i> 2. Kartu Panduan TM (Important Notice Cards)
                  </span>
                  <span class="badge bg-primary-subtle text-primary fs-11">
                    {{ sectionForm.guidelines.length }} Kartu
                  </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-danger d-inline-flex align-items-center gap-1"
                    @click="resetGuidelinesToDefault"
                    title="Kembalikan ke susunan 4 kartu standar awal"
                  >
                    <i class="ri-refresh-line"></i> Reset Standar (4 Kartu)
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-primary d-inline-flex align-items-center gap-1 shadow-sm"
                    @click="addGuidelineCard"
                  >
                    <i class="ri-add-line"></i> Tambah Kartu
                  </button>
                </div>
              </div>
              <div class="card-body p-3">
                <div v-if="sectionForm.guidelines.length === 0" class="text-center py-4 text-muted">
                  <i class="ri-inbox-line fs-32 text-secondary mb-2 d-block"></i>
                  <p class="fs-13 mb-2">Belum ada kartu panduan yang ditambahkan.</p>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="resetGuidelinesToDefault">
                    Gunakan 4 Kartu Standar
                  </button>
                </div>

                <div v-else class="row g-3">
                  <div 
                    v-for="(card, cIdx) in sectionForm.guidelines" 
                    :key="cIdx" 
                    class="col-12 col-md-6"
                  >
                    <div class="p-3 border rounded-3 bg-white shadow-none position-relative h-100">
                      <!-- Card Header Actions -->
                      <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge bg-dark text-white fs-10 font-mono">#{{ cIdx + 1 }}</span>
                          <span class="fw-bold fs-12 text-dark text-truncate" style="max-width: 140px;">
                            {{ card.title || 'Tanpa Judul' }}
                          </span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                          <button 
                            type="button" 
                            class="btn btn-xs btn-light text-dark p-1" 
                            :disabled="cIdx === 0"
                            @click="moveGuidelineCard(cIdx, -1)" 
                            title="Geser ke Kiri/Atas"
                          >
                            <i class="ri-arrow-up-line"></i>
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-light text-dark p-1" 
                            :disabled="cIdx === sectionForm.guidelines.length - 1"
                            @click="moveGuidelineCard(cIdx, 1)" 
                            title="Geser ke Kanan/Bawah"
                          >
                            <i class="ri-arrow-down-line"></i>
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-danger p-1" 
                            @click="removeGuidelineCard(cIdx)" 
                            title="Hapus Kartu Ini"
                          >
                            <i class="ri-delete-bin-line"></i>
                          </button>
                        </div>
                      </div>

                      <!-- Card Fields -->
                      <div class="row g-2">
                        <div class="col-12">
                          <label class="form-label fs-11 text-muted fw-semibold mb-0.5">Judul Kartu</label>
                          <input 
                            type="text" 
                            class="form-control form-control-sm" 
                            v-model="card.title" 
                            placeholder="Contoh: Delegasi Resmi"
                          />
                        </div>

                        <!-- Icon Field & Quick Presets -->
                        <div class="col-12">
                          <label class="form-label fs-11 text-muted fw-semibold mb-0.5 d-flex justify-content-between">
                            <span>Icon RemixIcon</span>
                            <span class="fs-10 text-muted">Contoh: ri-user-star-line</span>
                          </label>
                          <div class="input-group input-group-sm mb-1">
                            <span class="input-group-text bg-light text-primary">
                              <i :class="card.icon || 'ri-information-line'"></i>
                            </span>
                            <input 
                              type="text" 
                              class="form-control" 
                              v-model="card.icon" 
                              placeholder="ri-user-star-line"
                            />
                          </div>
                          <!-- Quick Icon Suggestions -->
                          <div class="d-flex align-items-center gap-1 flex-wrap pt-0.5">
                            <span class="fs-10 text-muted me-1">Pilihan Cepat:</span>
                            <button 
                              v-for="ico in iconPresets" 
                              :key="ico.icon" 
                              type="button" 
                              class="btn btn-xs py-0 px-1.5 rounded"
                              :class="card.icon === ico.icon ? 'btn-primary' : 'btn-light border text-muted'"
                              @click="card.icon = ico.icon"
                              :title="ico.label"
                            >
                              <i :class="ico.icon"></i>
                            </button>
                          </div>
                        </div>

                        <!-- Color Field & Swatches -->
                        <div class="col-12">
                          <label class="form-label fs-11 text-muted fw-semibold mb-0.5 d-flex justify-content-between">
                            <span>Aksen Warna</span>
                            <span class="font-mono fs-10">{{ card.color || '#8b5cf6' }}</span>
                          </label>
                          <div class="d-flex align-items-center gap-2">
                            <input 
                              type="color" 
                              class="form-control form-control-color form-control-sm border-0 p-0 rounded-circle flex-shrink-0 cursor-pointer" 
                              v-model="card.color" 
                              style="width: 28px; height: 28px;"
                              title="Pilih warna custom"
                            />
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                              <button 
                                v-for="clr in colorPresets" 
                                :key="clr" 
                                type="button" 
                                class="rounded-circle border-0 p-0 cursor-pointer"
                                :style="{
                                  width: '20px',
                                  height: '20px',
                                  backgroundColor: clr,
                                  outline: card.color === clr ? '2px solid #000' : 'none',
                                  outlineOffset: '1px'
                                }"
                                @click="card.color = clr"
                              ></button>
                            </div>
                          </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                          <label class="form-label fs-11 text-muted fw-semibold mb-0.5">Keterangan / Panduan</label>
                          <textarea 
                            class="form-control form-control-sm" 
                            rows="2" 
                            v-model="card.desc" 
                            placeholder="Tuliskan petunjuk ringkas kartu ini..."
                          ></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-light border-0 py-3 px-4 d-flex align-items-center justify-content-between">
            <button 
              type="button" 
              class="btn btn-sm btn-secondary" 
              @click="showSectionModal = false"
              :disabled="sectionForm.processing"
            >
              Batal
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 shadow-sm"
              @click="submitSectionSettings"
              :disabled="sectionForm.processing"
            >
              <i class="ri-save-line" v-if="!sectionForm.processing"></i>
              <span class="spinner-border spinner-border-sm" role="status" v-else></span>
              {{ sectionForm.processing ? 'Menyimpan...' : 'Simpan Kustomisasi' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-tm-sheet, #printable-tm-sheet * {
    visibility: visible;
  }
  #printable-tm-sheet {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
