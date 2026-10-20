<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { initEcho } from '@/echo';
import { useToast } from '@/Composables/useToast';
import { getWaChatUrl } from '@/Utils/whatsapp';

const page = usePage();
const school = computed(() => page.props.schoolProfile || {});
const eventInfo = computed(() => {
  const name = (school.value.event_name || 'SEMARAK ALIYAH XIV 2026').trim();
  const match = name.match(/^(.*?)\s+([IVXLCDM]+|\d+)(?:\s+(\d{4}))?$/i);
  if (match) {
    return {
      base: match[1],
      edition: match[2].toUpperCase(),
      year: match[3] || '2026',
      full: name
    };
  }
  return { base: name, edition: 'XIV', year: '2026', full: name };
});

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  registrations: { type: Array, default: () => [] },
  competitions: { type: Array, default: () => [] },
  activities: { type: Array, default: () => [] },
});

const searchQuery = ref('');
const selectedStatus = ref('all');
const selectedCompetition = ref('all');
const registrationsList = ref([...props.registrations]);
const competitionsList = ref([...props.competitions]);

// Recent Activity State (Velzon Official Theme Design)
const activitiesList = ref([...props.activities]);
const selectedActivityFilter = ref('all');
const isActivityDropdownOpen = ref(false);

const activityFilterLabel = computed(() => {
  if (selectedActivityFilter.value === 'registration') return 'Pendaftaran';
  if (selectedActivityFilter.value === 'verification') return 'Verifikasi';
  if (selectedActivityFilter.value === 'checkin') return 'Gate Check-in';
  return 'Semua Aktivitas';
});

function setActivityFilter(type) {
  selectedActivityFilter.value = type;
  isActivityDropdownOpen.value = false;
}

const filteredActivities = computed(() => {
  if (selectedActivityFilter.value === 'all') return activitiesList.value;
  return activitiesList.value.filter(a => a.type === selectedActivityFilter.value);
});

// Detail modal state
const showDetailModal = ref(false);
const activeDetailItem = ref(null);
const verificationNotes = ref('');
const isProcessingVerification = ref(false);

// Filtered registrations
const filteredRegistrations = computed(() => {
  return registrationsList.value.filter(reg => {
    const q = searchQuery.value.toLowerCase().trim();
    const matchQuery = !q || 
      reg.reg_code?.toLowerCase().includes(q) ||
      reg.school_name?.toLowerCase().includes(q) ||
      reg.mentor_name?.toLowerCase().includes(q) ||
      reg.participants_names?.toLowerCase().includes(q);

    const matchStatus = selectedStatus.value === 'all' || reg.payment_status === selectedStatus.value;
    const matchComp = selectedCompetition.value === 'all' || reg.competition_id === selectedCompetition.value;

    return matchQuery && matchStatus && matchComp;
  });
});

// Counts for filter pills
const countAll = computed(() => registrationsList.value.length);
const countPending = computed(() => registrationsList.value.filter(r => r.payment_status === 'Menunggu Verifikasi').length);
const countVerified = computed(() => registrationsList.value.filter(r => r.payment_status === 'Terverifikasi').length);
const countRejected = computed(() => registrationsList.value.filter(r => r.payment_status === 'Ditolak').length);

// Format Indonesian Currency
function formatRupiah(num) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}

const toast = useToast();

// Format Date string: dd/mm/yyyy
function formatDate(dateStr) {
  if (!dateStr) return '-';
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return dateStr;
  const m = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (m) {
    return `${m[3]}/${m[2]}/${m[1]}`;
  }
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

// 1-Click Quick Verification Toggle
function toggleVerification(reg) {
  const nextStatus = reg.payment_status === 'Terverifikasi' ? 'Menunggu Verifikasi' : 'Terverifikasi';
  router.post(`/admin/verify/${reg.id}`, { status: nextStatus }, {
    preserveScroll: true,
    onSuccess: () => {
      reg.payment_status = nextStatus;
      activitiesList.value.unshift({
        id: 'rt-act-' + Date.now(),
        type: 'verification',
        title: nextStatus === 'Terverifikasi' ? 'Verifikasi Pembayaran Sukses' : 'Status Verifikasi Ditarik',
        highlight: reg.school_name,
        desc: `Status pembayaran #${reg.reg_code} diubah menjadi ${nextStatus}`,
        badge_icon: nextStatus === 'Terverifikasi' ? 'ri-shield-check-line' : 'ri-time-line',
        badge_class: nextStatus === 'Terverifikasi' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning',
        time_str: 'Baru saja',
        reg_code: reg.reg_code,
      });
      toast.success(`Status delegasi ${reg.school_name} (${reg.reg_code}) berhasil diubah menjadi "${nextStatus}"!`, 'Verifikasi Berhasil');
    },
    onError: () => {
      toast.error(`Gagal mengubah status pendaftaran #${reg.reg_code}.`, 'Terjadi Kesalahan');
    }
  });
}

// Open Detail Modal
function openDetailModal(reg) {
  activeDetailItem.value = reg;
  verificationNotes.value = reg.verification_notes || '';
  showDetailModal.value = true;
}

// Submit Verification Decision from Modal
function submitVerificationDecision(status) {
  if (!activeDetailItem.value) return;
  isProcessingVerification.value = true;
  const regCode = activeDetailItem.value.reg_code;
  router.post(`/admin/verify/${activeDetailItem.value.id}`, {
    status: status,
    notes: verificationNotes.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      activeDetailItem.value.payment_status = status;
      activeDetailItem.value.verification_notes = verificationNotes.value;
      const idx = registrationsList.value.findIndex(r => r.id === activeDetailItem.value.id);
      if (idx !== -1) {
        registrationsList.value[idx].payment_status = status;
        registrationsList.value[idx].verification_notes = verificationNotes.value;
      }
      activitiesList.value.unshift({
        id: 'rt-modal-' + Date.now(),
        type: 'verification',
        title: status === 'Terverifikasi' ? 'Verifikasi Berkas Disetujui' : 'Verifikasi Berkas Ditolak',
        highlight: activeDetailItem.value.school_name,
        desc: `Status registrasi #${activeDetailItem.value.reg_code} ditetapkan: ${status}`,
        badge_icon: status === 'Terverifikasi' ? 'ri-shield-check-line' : 'ri-close-circle-line',
        badge_class: status === 'Terverifikasi' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger',
        time_str: 'Baru saja',
        reg_code: activeDetailItem.value.reg_code,
      });
      isProcessingVerification.value = false;
      showDetailModal.value = false;
      toast.success(`Status registrasi #${regCode} berhasil ditetapkan: "${status}"!`, 'Verifikasi Berhasil');
    },
    onError: () => {
      isProcessingVerification.value = false;
      toast.error(`Gagal memperbarui status registrasi #${regCode}.`, 'Gagal Verifikasi');
    }
  });
}

// Toggle Quota status (Buka vs Tutup)
function toggleQuota(comp) {
  const nextStatus = comp.status === 'open' ? 'closed' : 'open';
  router.post(`/admin/toggle-quota/${comp.id}`, { status: nextStatus }, {
    preserveScroll: true,
    onSuccess: () => {
      comp.status = nextStatus;
      const statusKet = nextStatus === 'open' ? 'dibuka kembali' : 'ditutup sementara';
      activitiesList.value.unshift({
        id: 'rt-quota-' + Date.now(),
        type: 'system',
        title: 'Perubahan Kuota Cabang',
        highlight: comp.title,
        desc: `Status pendaftaran cabang resmi ${nextStatus === 'open' ? 'Dibuka kembali' : 'Ditutup'} oleh panitia`,
        badge_icon: 'ri-pie-chart-2-line',
        badge_class: nextStatus === 'open' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger',
        time_str: 'Baru saja',
        reg_code: comp.category,
      });
      toast.success(`Status kuota "${comp.title}" berhasil ${statusKet}!`, 'Status Kuota Diperbarui');
    }
  });
}

// Listen to Reverb broadcast events
onMounted(() => {
  try {
    const echo = initEcho();
    if (echo) {
      echo.channel('admin.dashboard')
        .listen('.gate.logged', (data) => {
          if (data?.registration) {
            const idx = registrationsList.value.findIndex(r => r.id === data.registration.id);
            if (idx !== -1) {
              registrationsList.value[idx].checkin_status = data.registration.checkin_status;
            }
            activitiesList.value.unshift({
              id: 'rt-gate-' + Date.now(),
              type: 'checkin',
              title: 'Gate Check-in Kontingen',
              highlight: data.registration.school_name,
              desc: `Kontingen telah diverifikasi barcode dan masuk arena (${data.registration.competition_title || ''})`,
              badge_icon: 'ri-qr-code-line',
              badge_class: 'bg-info-subtle text-info',
              time_str: 'Baru saja',
              reg_code: data.registration.reg_code,
            });
          }
        });
    }
  } catch (e) {}
});
</script>

<template>
  <VelzonLayout>
    <Head title="Dashboard Panitia Pendaftaran | Velzon" />

    <!-- ============================================================== -->
    <!-- PAGE TITLE & BREADCRUMB                                        -->
    <!-- ============================================================== -->
    <div class="row mb-4 align-items-center">
      <div class="col-md-7">
        <h4 class="fs-18 fw-bold mb-1 text-dark">Dashboard Panitia {{ school.event_name || 'Semarak Aliyah XIV' }}</h4>
        <p class="text-muted mb-0 fs-13">
          {{ school.school_name || 'MA Unggulan Wahid Hasyim Balung, Jember' }} • Pantau pendaftaran, verifikasi peserta, dan kuota lomba secara real-time.
        </p>
      </div>
      <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-2">
        <span class="badge bg-light text-dark border px-3 py-2 fs-12">
          <i class="ri-calendar-line text-primary me-1"></i> Edisi {{ eventInfo.edition }} • {{ eventInfo.year }}
        </span>
        <a href="/admin/export" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
          <i class="ri-download-cloud-2-line"></i> Unduh Data
        </a>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- 4 VELZON KPI METRIC WIDGET CARDS                               -->
    <!-- ============================================================== -->
    <div class="row">
      <!-- Widget 1: Total Pendaftar -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Total Pendaftar</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">
                  {{ stats.total_registrations || registrationsList.length }} 
                  <span class="fs-12 text-muted fw-normal">Peserta</span>
                </h4>
                <span class="badge bg-success-subtle text-success fs-11 mt-2">
                  <i class="ri-arrow-up-line align-middle"></i> Terdaftar Aktif
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-primary-subtle text-primary rounded fs-20">
                  <i class="ri-user-star-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Widget 2: Total Dana Masuk -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Total Dana Masuk</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">
                  {{ formatRupiah(stats.total_fee) }}
                </h4>
                <span class="badge bg-info-subtle text-info fs-11 mt-2">
                  <i class="ri-shield-check-line align-middle"></i> Terverifikasi Panitia
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                  <i class="ri-wallet-3-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Widget 3: Kuota Terisi -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Tingkat Keterisian Kuota</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">{{ stats.quota_filled_percentage || 0 }}%</h4>
                <div class="progress mt-2" style="height: 6px; width: 140px;">
                  <div 
                    class="progress-bar bg-warning" 
                    role="progressbar" 
                    :style="{ width: `${stats.quota_filled_percentage || 0}%` }"
                    aria-valuemin="0" 
                    aria-valuemax="100"
                  ></div>
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-warning-subtle text-warning rounded fs-20">
                  <i class="ri-pie-chart-2-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Widget 4: Menunggu Verifikasi -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Menunggu Verifikasi</p>
                <h4 class="fs-22 fw-bold mb-0 text-danger">
                  {{ countPending }} 
                  <span class="fs-12 text-muted fw-normal">Peserta</span>
                </h4>
                <span class="badge bg-danger-subtle text-danger fs-11 mt-2">
                  <i class="ri-error-warning-line align-middle"></i> Perlu Tindakan
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-danger-subtle text-danger rounded fs-20">
                  <i class="ri-error-warning-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION: MONITORING KUOTA CABANG LOMBA                         -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- SECTION: MONITORING KUOTA & RECENT ACTIVITY (Velzon 2-Col Grid)-->
    <!-- ============================================================== -->
    <div class="row mb-4">
      <!-- Left Column: Monitoring Kuota Cabang Lomba (col-xl-8) -->
      <div class="col-xl-8 mb-4 mb-xl-0">
        <div id="section-kuota" class="card border-0 shadow-sm h-100">
          <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-3">
            <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
              <i class="ri-medal-line text-warning me-1"></i> Monitoring Kuota Cabang Lomba
            </h5>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary-subtle text-primary fs-11">
                {{ competitionsList.length }} Cabang Lomba
              </span>
              <Link href="/admin/lomba" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                Kelola Lomba <i class="ri-arrow-right-line"></i>
              </Link>
            </div>
          </div>

          <div class="card-body">
            <div class="row g-3">
              <div 
                v-for="comp in competitionsList" 
                :key="comp.id" 
                class="col-sm-6"
              >
                <div class="p-3 rounded border bg-light h-100 d-flex flex-column justify-content-between">
                  <div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <span class="badge bg-secondary-subtle text-secondary fs-10 uppercase fw-semibold">
                        {{ comp.category }}
                      </span>
                      <span 
                        class="badge fs-10"
                        :class="comp.status === 'open' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                      >
                        {{ comp.status === 'open' ? 'Buka' : 'Tutup' }}
                      </span>
                    </div>

                    <h6 class="fs-13 fw-bold text-dark mb-1">{{ comp.title }}</h6>
                    <div class="text-muted fs-11 mb-2">{{ formatRupiah(comp.fee) }} / delegasi</div>
                  </div>

                  <div>
                    <div class="d-flex align-items-center justify-content-between fs-11 text-muted mb-1">
                      <span>Kuota Terisi</span>
                      <span class="fw-semibold text-dark">{{ comp.current_quota }} / {{ comp.max_quota }}</span>
                    </div>
                    <div class="progress mb-2" style="height: 5px;">
                      <div 
                        class="progress-bar"
                        :class="comp.current_quota >= comp.max_quota ? 'bg-danger' : 'bg-primary'"
                        :style="{ width: `${Math.min(100, Math.round((comp.current_quota / (comp.max_quota || 1)) * 100))}%` }"
                      ></div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-1">
                      <span class="fs-11 text-muted">Status Registrasi:</span>
                      <button 
                        @click="toggleQuota(comp)"
                        type="button" 
                        class="btn btn-xs rounded-pill"
                        :class="comp.status === 'open' ? 'btn-outline-danger' : 'btn-outline-success'"
                      >
                        {{ comp.status === 'open' ? 'Tutup Kuota' : 'Buka Kuota' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Recent Activity (col-xl-4) - Official Velzon Theme Timeline -->
      <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header align-items-center d-flex bg-white border-bottom py-3">
            <h5 class="card-title mb-0 flex-grow-1 fs-14 fw-bold text-dark">
              <i class="ri-history-line text-primary me-1"></i> Recent Activity
            </h5>
            <div class="flex-shrink-0">
              <div class="dropdown position-relative">
                <button 
                  class="btn btn-sm btn-light border dropdown-toggle fs-11 py-1 px-2 d-inline-flex align-items-center gap-1"
                  type="button"
                  @click="isActivityDropdownOpen = !isActivityDropdownOpen"
                >
                  <span class="text-muted">{{ activityFilterLabel }}</span>
                  <i class="ri-arrow-down-s-line fs-12"></i>
                </button>
                <div 
                  v-if="isActivityDropdownOpen" 
                  class="dropdown-menu dropdown-menu-end show position-absolute end-0 mt-1 shadow-sm"
                  style="min-width: 170px; z-index: 1050;"
                >
                  <button class="dropdown-item fs-12 py-1.5" @click="setActivityFilter('all')">
                    <i class="ri-list-check align-middle me-1 text-primary"></i> Semua Aktivitas
                  </button>
                  <button class="dropdown-item fs-12 py-1.5" @click="setActivityFilter('registration')">
                    <i class="ri-user-add-line align-middle me-1 text-info"></i> Pendaftaran Baru
                  </button>
                  <button class="dropdown-item fs-12 py-1.5" @click="setActivityFilter('verification')">
                    <i class="ri-shield-check-line align-middle me-1 text-success"></i> Verifikasi Berkas
                  </button>
                  <button class="dropdown-item fs-12 py-1.5" @click="setActivityFilter('checkin')">
                    <i class="ri-qr-code-line align-middle me-1 text-warning"></i> Gate Check-in
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="card-body p-3">
            <div class="activity-scroll-feed">
              <div class="acitivity-timeline acitivity-main">
                <div 
                  v-for="act in filteredActivities" 
                  :key="act.id" 
                  class="acitivity-item d-flex"
                >
                  <div class="flex-shrink-0 avatar-xs acitivity-avatar">
                    <div 
                      class="avatar-title rounded-circle"
                      :class="act.badge_class || 'bg-primary-subtle text-primary'"
                    >
                      <i :class="act.badge_icon || 'ri-notification-3-line'" class="fs-14"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="mb-1 fs-13 fw-semibold text-dark lh-base">
                      {{ act.title }}
                      <span v-if="act.highlight" class="text-primary fw-bold ms-1">• {{ act.highlight }}</span>
                    </h6>
                    <p class="text-muted fs-12 mb-1 lh-sm">{{ act.desc }}</p>
                    <div class="d-flex align-items-center gap-2">
                      <small class="text-muted fs-11">
                        <i class="ri-time-line align-middle me-0.5"></i> {{ act.time_str }}
                      </small>
                      <span v-if="act.reg_code" class="badge bg-light text-muted border fs-10 font-mono">
                        {{ act.reg_code }}
                      </span>
                    </div>
                  </div>
                </div>

                <div v-if="filteredActivities.length === 0" class="text-center py-5 text-muted fs-12">
                  <i class="ri-calendar-event-line fs-24 d-block mb-1 text-muted"></i>
                  Belum ada aktivitas pada kategori ini.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION: DATA DELEGASI PENDAFTAR MASUK                         -->
    <!-- ============================================================== -->
    <div id="section-pendaftar" class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <!-- Card Header with Filters & Search -->
          <div class="card-header bg-white border-bottom py-3">
            <div class="row align-items-center g-3">
              <div class="col-md-5">
                <div class="d-flex align-items-center gap-2">
                  <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                    <i class="ri-team-line text-primary me-1"></i> Data Delegasi Pendaftar Masuk
                  </h5>
                  <Link href="/admin/pendaftar" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                    Buka Halaman Lengkap <i class="ri-arrow-right-line"></i>
                  </Link>
                </div>
              </div>

              <!-- Filter Tabs & Inputs -->
              <div class="col-md-8">
                <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                  <!-- Search Box -->
                  <div class="position-relative" style="min-width: 200px;">
                    <input 
                      v-model="searchQuery"
                      type="text" 
                      class="form-control form-control-sm ps-4" 
                      placeholder="Cari sekolah, ID, pendamping..."
                    />
                    <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-12"></i>
                  </div>

                  <!-- Competition Dropdown Filter -->
                  <select v-model="selectedCompetition" class="form-select form-select-sm" style="width: auto;">
                    <option value="all">Semua Cabang Lomba</option>
                    <option v-for="c in competitionsList" :key="c.id" :value="c.id">{{ c.title }}</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Status Filter Pills -->
            <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
              <button 
                @click="selectedStatus = 'all'" 
                class="btn btn-xs rounded-pill"
                :class="selectedStatus === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
              >
                Semua ({{ countAll }})
              </button>
              <button 
                @click="selectedStatus = 'Menunggu Verifikasi'" 
                class="btn btn-xs rounded-pill"
                :class="selectedStatus === 'Menunggu Verifikasi' ? 'btn-warning text-dark font-bold' : 'btn-outline-warning'"
              >
                Menunggu Review ({{ countPending }})
              </button>
              <button 
                @click="selectedStatus = 'Terverifikasi'" 
                class="btn btn-xs rounded-pill"
                :class="selectedStatus === 'Terverifikasi' ? 'btn-success' : 'btn-outline-success'"
              >
                Terverifikasi ({{ countVerified }})
              </button>
              <button 
                @click="selectedStatus = 'Ditolak'" 
                class="btn btn-xs rounded-pill"
                :class="selectedStatus === 'Ditolak' ? 'btn-danger' : 'btn-outline-danger'"
              >
                Ditolak ({{ countRejected }})
              </button>
            </div>
          </div>

          <!-- Card Body: Table -->
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width: 50px;" class="text-center">#</th>
                    <th scope="col">Kode Registrasi</th>
                    <th scope="col">Nama Lembaga / Kontingen</th>
                    <th scope="col">Pendamping & Kontak</th>
                    <th scope="col">Cabang Lomba & Paket</th>
                    <th scope="col">Status Verifikasi</th>
                    <th scope="col" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredRegistrations.length === 0">
                    <td colspan="7" class="text-center py-5 text-muted fs-13">
                      <i class="ri-search-eye-line fs-28 d-block mb-1 text-muted"></i>
                      Tidak ada data pendaftaran yang sesuai dengan pencarian atau filter.
                    </td>
                  </tr>
                  <tr v-for="(reg, idx) in filteredRegistrations" :key="reg.id">
                    <td class="text-center text-muted fs-12">{{ idx + 1 }}</td>
                    <td>
                      <span class="fw-bold font-mono text-primary fs-12">{{ reg.reg_code }}</span>
                      <small class="d-block text-muted fs-10">{{ formatDate(reg.created_at) }}</small>
                    </td>
                    <td>
                      <div class="fw-bold text-dark fs-13">{{ reg.school_name }}</div>
                      <small class="text-muted fs-11 truncate" style="max-width: 220px;">{{ reg.participants_names }}</small>
                    </td>
                    <td>
                      <div class="fw-semibold fs-12 text-dark">{{ reg.mentor_name }}</div>
                      <a 
                        :href="getWaChatUrl(reg.mentor_wa, `Halo Bapak/Ibu ${reg.mentor_name} (${reg.school_name}), kami dari Panitia ${school.event_name || 'Semarak Aliyah'}. Terkait pendaftaran Anda (Kode: ${reg.reg_code}) pada cabang ${reg.competition_title}: `)" 
                        target="_blank" 
                        class="badge bg-success-subtle text-success fs-10 text-decoration-none"
                        title="Hubungi Guru Pendamping via WhatsApp"
                      >
                        <i class="ri-whatsapp-line align-middle me-0.5"></i> {{ reg.mentor_wa }}
                      </a>
                    </td>
                    <td>
                      <div class="fw-semibold fs-12 text-dark">{{ reg.competition_title || reg.competition_id }}</div>
                      <span class="badge bg-light text-muted border fs-10">{{ reg.package_type }}</span>
                    </td>
                    <td>
                      <span 
                        class="badge fs-11 px-2 py-1"
                        :class="{
                          'bg-success-subtle text-success': reg.payment_status === 'Terverifikasi',
                          'bg-warning-subtle text-warning': reg.payment_status === 'Menunggu Verifikasi',
                          'bg-danger-subtle text-danger': reg.payment_status === 'Ditolak'
                        }"
                      >
                        <i 
                          class="align-middle me-1"
                          :class="{
                            'ri-checkbox-circle-line': reg.payment_status === 'Terverifikasi',
                            'ri-time-line': reg.payment_status === 'Menunggu Verifikasi',
                            'ri-close-circle-line': reg.payment_status === 'Ditolak'
                          }"
                        ></i>
                        {{ reg.payment_status }}
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <!-- 1-Click Quick Verify Toggle -->
                        <button 
                          type="button" 
                          class="btn btn-sm"
                          :class="reg.payment_status === 'Terverifikasi' ? 'btn-outline-warning' : 'btn-outline-success'"
                          @click="toggleVerification(reg)"
                          :title="reg.payment_status === 'Terverifikasi' ? 'Batal Verifikasi' : 'Verifikasi Cepat'"
                        >
                          <i :class="reg.payment_status === 'Terverifikasi' ? 'ri-arrow-go-back-line' : 'ri-check-line'"></i>
                        </button>

                        <!-- Detail & Bukti Modal Trigger -->
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-primary"
                          @click="openDetailModal(reg)"
                          title="Periksa Detail & Dokumen Berkas"
                        >
                          <i class="ri-eye-line"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Card Footer with Link to Full Page -->
          <div class="card-footer bg-light border-top d-flex align-items-center justify-content-between py-2.5 px-3">
            <span class="fs-12 text-muted">Menampilkan ringkasan pendaftar delegasi aktif</span>
            <Link href="/admin/pendaftar" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-xs">
              Buka Halaman Lengkap Pendaftar ({{ registrationsList.length }} Delegasi) <i class="ri-arrow-right-line"></i>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- DETAIL & VERIFIKASI BERKAS MODAL                               -->
    <!-- ============================================================== -->
    <div 
      v-if="showDetailModal && activeDetailItem" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden text-dark">
        <div class="px-4 py-3 bg-light border-bottom d-flex align-items-center justify-content-between">
          <h5 class="modal-title fs-15 fw-bold mb-0 text-dark">
            <i class="ri-file-search-line text-primary me-1"></i> Detail Delegasi & Pemeriksaan Berkas
          </h5>
          <button type="button" class="btn-close" @click="showDetailModal = false"></button>
        </div>

        <div class="p-4 space-y-3 fs-13">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="text-muted fs-11 fw-semibold mb-0 d-block">Asal Sekolah / Lembaga:</label>
              <div class="fw-bold fs-14 text-dark">{{ activeDetailItem.school_name }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted fs-11 fw-semibold mb-0 d-block">Kode Pendaftaran:</label>
              <div class="fw-bold font-mono text-primary fs-14">{{ activeDetailItem.reg_code }}</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted fs-11 fw-semibold mb-0 d-block">Cabang Lomba & Paket:</label>
              <div class="fw-bold text-dark">{{ activeDetailItem.competition_title || activeDetailItem.competition_id }} ({{ activeDetailItem.package_type }})</div>
            </div>
            <div class="col-md-6">
              <label class="text-muted fs-11 fw-semibold mb-0 d-block">Pendamping & WhatsApp:</label>
              <div class="fw-bold text-dark">{{ activeDetailItem.mentor_name }} ({{ activeDetailItem.mentor_wa }})</div>
            </div>
            <div class="col-12">
              <label class="text-muted fs-11 fw-semibold mb-0 d-block">Nama Peserta / Anggota Regu:</label>
              <div class="p-2 bg-light rounded text-dark fs-12">{{ activeDetailItem.participants_names }}</div>
            </div>
          </div>

          <!-- Document Attachments -->
          <div class="pt-3 border-top">
            <label class="fw-bold text-dark fs-12 mb-2 d-block">Dokumen Berkas Terunggah:</label>
            <div class="row g-2">
              <div class="col-md-6">
                <a 
                  v-if="activeDetailItem.assignment_letter_path" 
                  :href="`/storage/${activeDetailItem.assignment_letter_path}`" 
                  target="_blank"
                  class="btn btn-sm btn-outline-danger w-100 d-flex align-items-center justify-content-between p-2"
                >
                  <span><i class="ri-file-pdf-line me-1"></i> Surat Tugas Sekolah</span>
                  <i class="ri-external-link-line"></i>
                </a>
                <div v-else class="p-2 bg-light rounded text-muted fs-11 text-center border">
                  Surat tugas belum diunggah.
                </div>
              </div>

              <div class="col-md-6">
                <a 
                  v-if="activeDetailItem.payment_proof_path" 
                  :href="`/storage/${activeDetailItem.payment_proof_path}`" 
                  target="_blank"
                  class="btn btn-sm btn-outline-success w-100 d-flex align-items-center justify-content-between p-2"
                >
                  <span><i class="ri-image-line me-1"></i> Bukti Transfer Bank</span>
                  <i class="ri-external-link-line"></i>
                </a>
                <div v-else class="p-2 bg-light rounded text-muted fs-11 text-center border">
                  Bukti bayar belum diunggah (tunai).
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="pt-2">
            <label class="fw-semibold text-dark fs-12 mb-1 d-block">Catatan Verifikator:</label>
            <textarea 
              v-model="verificationNotes"
              class="form-control form-control-sm"
              rows="2"
              placeholder="Catatan tambahan untuk delegasi (opsional)..."
            ></textarea>
          </div>
        </div>

        <div class="px-4 py-3 bg-light border-top d-flex align-items-center justify-content-between">
          <button type="button" class="btn btn-sm btn-light" @click="showDetailModal = false">Tutup</button>
          <div class="d-flex align-items-center gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-danger"
              @click="submitVerificationDecision('Ditolak')"
              :disabled="isProcessingVerification"
            >
              <i class="ri-close-circle-line me-1"></i> Tolak Berkas
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-success"
              @click="submitVerificationDecision('Terverifikasi')"
              :disabled="isProcessingVerification"
            >
              <i class="ri-check-double-line me-1"></i> Verifikasi Sah
            </button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>
