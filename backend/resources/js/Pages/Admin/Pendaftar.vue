<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { initEcho } from '@/echo';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  registrations: { type: Array, default: () => [] },
  competitions: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');
const selectedCompetition = ref(props.filters.competition || 'all');
const registrationsList = ref([...props.registrations]);
const competitionsList = ref([...props.competitions]);

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
      reg.participants_names?.toLowerCase().includes(q) ||
      reg.competition_title?.toLowerCase().includes(q);

    const matchStatus = selectedStatus.value === 'all' || reg.payment_status === selectedStatus.value;
    const matchComp = selectedCompetition.value === 'all' || reg.competition_id === selectedCompetition.value;

    return matchQuery && matchStatus && matchComp;
  });
});

// Dynamic counts for filter pills
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
      toast.success(`Status pendaftaran #${reg.reg_code} diubah menjadi "${nextStatus}"!`, 'Verifikasi Diperbarui');
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
          }
        });
    }
  } catch (e) {}
});
</script>

<template>
  <VelzonLayout>
    <Head title="Data Delegasi Pendaftar Masuk | Velzon" />

    <!-- ============================================================== -->
    <!-- PAGE TITLE & BREADCRUMB                                        -->
    <!-- ============================================================== -->
    <div class="row mb-4 align-items-center">
      <div class="col-md-7">
        <div class="d-flex align-items-center gap-2 mb-1">
          <Link href="/admin" class="text-muted text-decoration-none fs-13">Dashboard</Link>
          <span class="text-muted fs-12">/</span>
          <span class="text-dark fw-semibold fs-13">Data Pendaftar</span>
        </div>
        <h4 class="fs-18 fw-bold mb-1 text-dark">Data Delegasi Pendaftar Masuk</h4>
        <p class="text-muted mb-0 fs-13">
          Pantau seluruh kontingen pendaftar, periksa berkas persyaratan, dan kelola status verifikasi pembayaran.
        </p>
      </div>
      <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-2">
        <Link href="/admin/lomba" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
          <i class="ri-medal-line"></i> Kelola Lomba
        </Link>
        <a href="/admin/export" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 shadow-sm">
          <i class="ri-file-excel-2-line"></i> Export Data CSV
        </a>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- 4 KPI SUMMARY METRIC CARDS                                     -->
    <!-- ============================================================== -->
    <div class="row">
      <!-- Card 1: Total Pendaftar -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Total Delegasi</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">
                  {{ stats.total_registrations || registrationsList.length }}
                  <span class="fs-12 text-muted fw-normal">Peserta</span>
                </h4>
                <span class="badge bg-primary-subtle text-primary fs-11 mt-2">
                  <i class="ri-team-line align-middle me-1"></i> Semua Cabang
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

      <!-- Card 2: Terverifikasi -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Terverifikasi Sah</p>
                <h4 class="fs-22 fw-bold mb-0 text-success">
                  {{ countVerified }}
                  <span class="fs-12 text-muted fw-normal">Delegasi</span>
                </h4>
                <span class="badge bg-success-subtle text-success fs-11 mt-2">
                  <i class="ri-shield-check-line align-middle me-1"></i> Berkas Lengkap
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                  <i class="ri-checkbox-circle-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3: Menunggu Verifikasi -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Menunggu Review</p>
                <h4 class="fs-22 fw-bold mb-0 text-warning">
                  {{ countPending }}
                  <span class="fs-12 text-muted fw-normal">Delegasi</span>
                </h4>
                <span class="badge bg-warning-subtle text-warning fs-11 mt-2">
                  <i class="ri-time-line align-middle me-1"></i> Perlu Tindakan
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-warning-subtle text-warning rounded fs-20">
                  <i class="ri-error-warning-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 4: Total Dana Masuk -->
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
                  <i class="ri-wallet-3-line align-middle me-1"></i> Biaya Registrasi
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-info-subtle text-info rounded fs-20">
                  <i class="ri-money-dollar-circle-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION: DATA DELEGASI TABLE & FILTERS                         -->
    <!-- ============================================================== -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <!-- Card Header with Filters & Search -->
          <div class="card-header bg-white border-bottom py-3">
            <div class="row align-items-center g-3">
              <div class="col-md-4">
                <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                  <i class="ri-team-line text-primary me-1"></i> Daftar Delegasi Kontingen Masuk
                </h5>
                <span class="text-muted fs-11">Menampilkan {{ filteredRegistrations.length }} dari {{ registrationsList.length }} data</span>
              </div>

              <!-- Filter Tabs & Inputs -->
              <div class="col-md-8">
                <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                  <!-- Search Box -->
                  <div class="position-relative" style="min-width: 240px;">
                    <input 
                      v-model="searchQuery"
                      type="text" 
                      class="form-control form-control-sm ps-4" 
                      placeholder="Cari sekolah, ID, nama peserta..."
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
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-2 border-top">
              <div class="d-flex align-items-center gap-2">
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

              <div class="text-muted fs-12 d-none d-md-block">
                <i class="ri-information-line text-info align-middle me-1"></i>
                Klik status untuk memverifikasi 1-klik secara instan
              </div>
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
                    <th scope="col" class="text-center">Gate Masuk</th>
                    <th scope="col" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredRegistrations.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted fs-13">
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
                      <small class="text-muted fs-11 truncate d-block" style="max-width: 240px;">
                        <i class="ri-user-line me-1"></i>{{ reg.participants_names }}
                      </small>
                    </td>
                    <td>
                      <div class="fw-semibold fs-12 text-dark">{{ reg.mentor_name }}</div>
                      <a 
                        :href="`https://wa.me/62${reg.mentor_wa ? reg.mentor_wa.replace(/^0/, '') : ''}`" 
                        target="_blank" 
                        class="badge bg-success-subtle text-success fs-10 text-decoration-none"
                      >
                        <i class="ri-whatsapp-line align-middle me-0.5"></i> {{ reg.mentor_wa }}
                      </a>
                    </td>
                    <td>
                      <div class="fw-semibold fs-12 text-dark">{{ reg.competition_title || reg.competition_id }}</div>
                      <div class="d-flex align-items-center gap-1 mt-0.5 flex-wrap">
                        <span class="badge bg-light text-muted border fs-10">{{ reg.package_type }}</span>
                        <span class="text-muted fs-11">• {{ formatRupiah(reg.total_fee) }}</span>
                        <span 
                          v-if="reg.custom_fields && Object.keys(reg.custom_fields).length > 0" 
                          class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10"
                          title="Pendaftar melengkapi berkas/pertanyaan khusus cabang"
                        >
                          <i class="ri-survey-line fs-10"></i> {{ Object.keys(reg.custom_fields).length }} Field Khusus
                        </span>
                      </div>
                    </td>
                    <td>
                      <span 
                        class="badge fs-11 px-2 py-1 cursor-pointer"
                        :class="{
                          'bg-success-subtle text-success': reg.payment_status === 'Terverifikasi',
                          'bg-warning-subtle text-warning': reg.payment_status === 'Menunggu Verifikasi',
                          'bg-danger-subtle text-danger': reg.payment_status === 'Ditolak'
                        }"
                        @click="toggleVerification(reg)"
                        title="Klik untuk ubah verifikasi"
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
                      <span 
                        class="badge fs-10"
                        :class="reg.checkin_status === 'checked_in' ? 'bg-info-subtle text-info' : 'bg-light text-muted border'"
                      >
                        <i 
                          class="align-middle me-0.5"
                          :class="reg.checkin_status === 'checked_in' ? 'ri-qr-code-line' : 'ri-time-line'"
                        ></i>
                        {{ reg.checkin_status === 'checked_in' ? 'Masuk' : 'Belum' }}
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
                          :title="reg.payment_status === 'Terverifikasi' ? 'Tarik Verifikasi' : 'Verifikasi Cepat'"
                        >
                          <i :class="reg.payment_status === 'Terverifikasi' ? 'ri-close-line' : 'ri-check-line'"></i>
                        </button>

                        <!-- Detail & Berkas Modal Trigger -->
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-primary"
                          @click="openDetailModal(reg)"
                          title="Lihat Detail & Berkas"
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
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: DETAIL PENDAFTARAN & VERIFIKASI BERKAS                  -->
    <!-- ============================================================== -->
    <div 
      v-if="showDetailModal && activeDetailItem" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0,0,0,0.55); z-index: 1060;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light border-bottom">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-primary fs-12 font-mono">{{ activeDetailItem.reg_code }}</span>
              <h5 class="modal-title fs-15 fw-bold text-dark mb-0">Detail Pendaftar Delegasi</h5>
            </div>
            <button type="button" class="btn-close" @click="showDetailModal = false"></button>
          </div>

          <div class="modal-body p-4">
            <div class="row g-4">
              <!-- Left Column: School & Mentor Info -->
              <div class="col-md-6 border-end">
                <h6 class="fs-13 fw-bold text-dark mb-3 text-uppercase" style="letter-spacing: 0.5px;">
                  <i class="ri-community-line text-primary me-1"></i> Informasi Lembaga & Pendamping
                </h6>

                <div class="mb-3">
                  <label class="form-label text-muted fs-11 text-uppercase mb-0">Nama Lembaga / Sekolah</label>
                  <p class="fw-bold fs-14 text-dark mb-0">{{ activeDetailItem.school_name }}</p>
                </div>

                <div class="mb-3">
                  <label class="form-label text-muted fs-11 text-uppercase mb-0">Guru / Pembina Pendamping</label>
                  <p class="fw-semibold fs-13 text-dark mb-0">{{ activeDetailItem.mentor_name }}</p>
                </div>

                <div class="mb-3">
                  <label class="form-label text-muted fs-11 text-uppercase mb-0">Nomor Kontak WhatsApp</label>
                  <div class="mt-1">
                    <a 
                      :href="`https://wa.me/62${activeDetailItem.mentor_wa ? activeDetailItem.mentor_wa.replace(/^0/, '') : ''}`" 
                      target="_blank" 
                      class="btn btn-xs btn-success d-inline-flex align-items-center gap-1"
                    >
                      <i class="ri-whatsapp-line"></i> Hubungi WA: {{ activeDetailItem.mentor_wa }}
                    </a>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label text-muted fs-11 text-uppercase mb-0">Cabang Lomba Terdaftar</label>
                  <p class="fw-bold fs-13 text-primary mb-0">{{ activeDetailItem.competition_title || activeDetailItem.competition_id }}</p>
                  <small class="badge bg-secondary-subtle text-secondary fs-10 mt-1">{{ activeDetailItem.package_type }}</small>
                </div>

                <div>
                  <label class="form-label text-muted fs-11 text-uppercase mb-0">Total Biaya Pendaftaran</label>
                  <h5 class="fw-bold text-dark mb-0">{{ formatRupiah(activeDetailItem.total_fee) }}</h5>
                </div>

                <!-- Custom Fields from Form Builder -->
                <div v-if="activeDetailItem.custom_fields && Object.keys(activeDetailItem.custom_fields).length > 0" class="mt-4 pt-3 border-top">
                  <h6 class="fs-12 fw-bold text-dark mb-2 text-uppercase d-flex align-items-center gap-1.5" style="letter-spacing: 0.5px;">
                    <i class="ri-survey-line text-warning"></i> Data Persyaratan Khusus Cabang
                  </h6>

                  <div class="d-flex flex-column gap-2">
                    <div 
                      v-for="(val, key) in activeDetailItem.custom_fields" 
                      :key="key"
                      class="p-2.5 rounded bg-light border text-start fs-12"
                    >
                      <div class="text-muted fs-11 fw-semibold text-uppercase mb-1">
                        {{ key.replace(/_/g, ' ') }}
                      </div>
                      
                      <!-- File Type with Download Link -->
                      <div v-if="typeof val === 'object' && val !== null && val.url">
                        <a 
                          :href="val.url" 
                          target="_blank" 
                          class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1.5"
                        >
                          <i class="ri-download-cloud-2-line"></i>
                          <span>{{ val.name || 'Lihat / Unduh Berkas' }}</span>
                          <span v-if="val.size" class="badge bg-secondary-subtle text-secondary fs-10 font-mono">
                            {{ (val.size / 1024 / 1024).toFixed(2) }} MB
                          </span>
                        </a>
                      </div>

                      <!-- Array Checkboxes -->
                      <div v-else-if="Array.isArray(val)" class="fw-semibold text-dark">
                        {{ val.join(', ') }}
                      </div>

                      <!-- URL Link -->
                      <div v-else-if="typeof val === 'string' && (val.startsWith('http://') || val.startsWith('https://'))">
                        <a :href="val" target="_blank" class="text-primary text-decoration-underline d-inline-flex align-items-center gap-1">
                          <i class="ri-external-link-line"></i> {{ val }}
                        </a>
                      </div>

                      <!-- Text / Other -->
                      <div v-else class="fw-semibold text-dark whitespace-pre-line">
                        {{ val || '-' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Column: Participants List & Verification Decision -->
              <div class="col-md-6">
                <h6 class="fs-13 fw-bold text-dark mb-3 text-uppercase" style="letter-spacing: 0.5px;">
                  <i class="ri-user-star-line text-primary me-1"></i> Daftar Nama Delegasi
                </h6>

                <div class="p-3 bg-light rounded border mb-3">
                  <div class="fw-medium fs-12 text-dark whitespace-pre-line" style="line-height: 1.6;">
                    {{ activeDetailItem.participants_names }}
                  </div>
                </div>

                <!-- Verification Notes Input -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark">Catatan Verifikasi Panitia</label>
                  <textarea 
                    v-model="verificationNotes" 
                    rows="2" 
                    class="form-control fs-12"
                    placeholder="Contoh: Bukti transfer valid, berkas lengkap..."
                  ></textarea>
                </div>

                <!-- Current Status Pill -->
                <div class="d-flex align-items-center justify-content-between p-2 rounded border bg-light mb-3">
                  <span class="fs-12 text-muted">Status Saat Ini:</span>
                  <span 
                    class="badge fs-11"
                    :class="{
                      'bg-success text-white': activeDetailItem.payment_status === 'Terverifikasi',
                      'bg-warning text-dark': activeDetailItem.payment_status === 'Menunggu Verifikasi',
                      'bg-danger text-white': activeDetailItem.payment_status === 'Ditolak'
                    }"
                  >
                    {{ activeDetailItem.payment_status }}
                  </span>
                </div>

                <!-- Verification Action Buttons -->
                <div class="d-flex gap-2">
                  <button 
                    type="button" 
                    class="btn btn-success flex-1 d-flex align-items-center justify-content-center gap-1"
                    :disabled="isProcessingVerification"
                    @click="submitVerificationDecision('Terverifikasi')"
                  >
                    <i class="ri-check-double-line"></i>
                    {{ isProcessingVerification ? 'Menyimpan...' : 'Setujui & Verifikasi' }}
                  </button>

                  <button 
                    type="button" 
                    class="btn btn-danger flex-1 d-flex align-items-center justify-content-center gap-1"
                    :disabled="isProcessingVerification"
                    @click="submitVerificationDecision('Ditolak')"
                  >
                    <i class="ri-close-circle-line"></i>
                    {{ isProcessingVerification ? 'Menyimpan...' : 'Tolak Berkas' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light border-top py-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="showDetailModal = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>
