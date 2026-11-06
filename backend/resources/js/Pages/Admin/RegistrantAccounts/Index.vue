<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  registrants: { type: Object, required: true },
  metrics: { type: Object, default: () => ({}) },
  districts: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const { success, error } = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// -----------------------------------------------------------------------------
// FILTER & PENCARIAN STATE
// -----------------------------------------------------------------------------
const searchInput = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || 'all');
const statusFilter = ref(props.filters.status || 'all');
const loginMethodFilter = ref(props.filters.login_method || 'all');
const districtFilter = ref(props.filters.district || 'all');
const perPage = ref(props.filters.per_page || 15);

const roleFilterOptions = [
  { value: 'all', label: 'Semua Peran Akun' },
  { value: 'Pendamping', label: 'Guru Pendamping / Pembina' },
  { value: 'Koordinator', label: 'Koordinator Kontingen' },
  { value: 'Siswa Peserta', label: 'Siswa Peserta Mandiri' },
];

const statusFilterOptions = [
  { value: 'all', label: 'Semua Status Akun' },
  { value: 'active', label: 'Aktif' },
  { value: 'inactive', label: 'Nonaktif' },
];

const loginMethodOptions = [
  { value: 'all', label: 'Semua Metode Login' },
  { value: 'google', label: 'Google OAuth' },
  { value: 'email', label: 'Email & Password' },
];

const districtFilterOptions = computed(() => {
  const opts = [{ value: 'all', label: 'Semua Kecamatan Lembaga' }];
  props.districts.forEach(d => {
    opts.push({ value: d, label: `Kec. ${d}` });
  });
  return opts;
});

const perPageOptions = [
  { value: 15, label: '15 per halaman' },
  { value: 25, label: '25 per halaman' },
  { value: 50, label: '50 per halaman' },
  { value: 100, label: '100 per halaman' },
];

let searchTimer = null;
function handleSearchInput() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    applyFilters();
  }, 400);
}

function applyFilters() {
  router.get('/admin/akun-pendaftar', {
    search: searchInput.value || undefined,
    role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
    status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    login_method: loginMethodFilter.value !== 'all' ? loginMethodFilter.value : undefined,
    district: districtFilter.value !== 'all' ? districtFilter.value : undefined,
    per_page: perPage.value !== 15 ? perPage.value : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function resetFilters() {
  searchInput.value = '';
  roleFilter.value = 'all';
  statusFilter.value = 'all';
  loginMethodFilter.value = 'all';
  districtFilter.value = 'all';
  perPage.value = 15;
  router.get('/admin/akun-pendaftar', {}, {
    preserveState: true,
    preserveScroll: true,
  });
}

// -----------------------------------------------------------------------------
// FORMATTING & HELPERS (Format dd/mm/yyyy & Jam 24 Jam)
// -----------------------------------------------------------------------------
function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return '-';
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, '0');
  const mins = String(d.getMinutes()).padStart(2, '0');
  return `${day}/${month}/${year} ${hours}:${mins}`;
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(amount || 0);
}

function getInitials(name) {
  if (!name) return 'A';
  return name.trim().split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function getWaUrl(item) {
  const phone = item.phone || '';
  const text = `Halo Bapak/Ibu/Sdr *${item.name}*,\n\nKami dari Panitia *SEMARAK ALIYAH XIV* ingin mengonfirmasi terkait akun pendaftar Anda (*${item.email}*) dari *${item.school_name || 'kontingen sekolah'}*.\n\nApakah ada kendala teknis atau informasi pendaftaran cabang lomba yang dapat kami bantu?`;
  return getWaChatUrl(phone, text);
}

// -----------------------------------------------------------------------------
// TOGGLE STATUS & HAPUS AKUN
// -----------------------------------------------------------------------------
async function handleToggleStatus(item) {
  const targetAction = item.is_active ? 'menonaktifkan' : 'mengaktifkan';
  const isConfirmed = await confirmDialog({
    title: `Konfirmasi Ubah Status`,
    text: `Apakah Anda yakin ingin ${targetAction} akun "${item.name}"?`,
    icon: 'question',
    confirmButtonText: `Ya, ${item.is_active ? 'Nonaktifkan' : 'Aktifkan'}`,
    confirmButtonColor: item.is_active ? '#dc2626' : '#16a34a',
  });

  if (!isConfirmed) return;

  router.post(`/admin/akun-pendaftar/${item.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Status akun "${item.name}" berhasil diperbarui.`);
    },
    onError: () => {
      error('Gagal mengubah status akun pendaftar.');
    }
  });
}

async function handleDeleteAccount(item) {
  const isConfirmed = await confirmDialog({
    title: `Hapus Akun Pendaftar?`,
    text: `Akun "${item.name}" (${item.email}) akan dihapus. Jika akun ini memiliki riwayat pendaftaran lomba, data delegasi lomba akan tetap aman namun terlepas dari akun ini. Lanjutkan?`,
    icon: 'warning',
    confirmButtonText: 'Ya, Hapus Akun',
    confirmButtonColor: '#dc2626',
  });

  if (!isConfirmed) return;

  router.delete(`/admin/akun-pendaftar/${item.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Akun "${item.name}" berhasil dihapus.`);
    },
    onError: () => {
      error('Gagal menghapus akun pendaftar.');
    }
  });
}

// -----------------------------------------------------------------------------
// MODAL: EDIT DATA AKUN
// -----------------------------------------------------------------------------
const isEditModalOpen = ref(false);
const isSubmittingEdit = ref(false);
const editForm = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  gender: 'Laki-laki',
  school_name: '',
  school_district: '',
  registrant_role: 'Pendamping',
  is_active: true,
});
const editErrors = ref({});

const genderOptions = [
  { value: 'Laki-laki', label: 'Laki-laki' },
  { value: 'Perempuan', label: 'Perempuan' },
];

const modalRoleOptions = [
  { value: 'Pendamping', label: 'Guru Pendamping / Pembina' },
  { value: 'Koordinator', label: 'Koordinator Kontingen' },
  { value: 'Siswa Peserta', label: 'Siswa Peserta Mandiri' },
];

const modalDistrictOptions = computed(() => {
  return props.districts.map(d => ({ value: d, label: `Kec. ${d}` }));
});

function openEditModal(item) {
  editErrors.value = {};
  editForm.value = {
    id: item.id,
    name: item.name || '',
    email: item.email || '',
    phone: item.phone || '',
    gender: item.gender || 'Laki-laki',
    school_name: item.school_name || '',
    school_district: item.school_district || '',
    registrant_role: item.registrant_role || 'Pendamping',
    is_active: !!item.is_active,
  };
  isEditModalOpen.value = true;
}

function submitEditForm() {
  isSubmittingEdit.value = true;
  editErrors.value = {};

  router.put(`/admin/akun-pendaftar/${editForm.value.id}`, editForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isEditModalOpen.value = false;
      success('Data akun pendaftar berhasil diperbarui.');
    },
    onError: (err) => {
      editErrors.value = err;
      error('Mohon periksa kembali form isian.');
    },
    onFinish: () => {
      isSubmittingEdit.value = false;
    }
  });
}

// -----------------------------------------------------------------------------
// MODAL: RESET PASSWORD AKUN
// -----------------------------------------------------------------------------
const isResetModalOpen = ref(false);
const isSubmittingReset = ref(false);
const resetForm = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  new_password: '',
});
const showResetPassword = ref(false);
const resetErrors = ref({});

function openResetModal(item) {
  resetErrors.value = {};
  showResetPassword.value = false;
  resetForm.value = {
    id: item.id,
    name: item.name,
    email: item.email,
    phone: item.phone,
    new_password: generateRandomPassword(),
  };
  isResetModalOpen.value = true;
}

function generateRandomPassword() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
  let res = 'Smk';
  for (let i = 0; i < 5; i++) {
    res += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return res;
}

function copyResetCredentials() {
  const text = `Kredensial Akun SEMARAK ALIYAH XIV:\nEmail: ${resetForm.value.email}\nPassword Baru: ${resetForm.value.new_password}\nPortal Login: ${window.location.origin}/pendaftar/login`;
  navigator.clipboard.writeText(text).then(() => {
    success('Kredensial berhasil disalin ke papan klip!');
  }).catch(() => {
    error('Gagal menyalin teks.');
  });
}

function submitResetPassword() {
  isSubmittingReset.value = true;
  resetErrors.value = {};

  router.post(`/admin/akun-pendaftar/${resetForm.value.id}/reset-password`, {
    password: resetForm.value.new_password,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isResetModalOpen.value = false;
      success(`Password akun "${resetForm.value.name}" berhasil direset.`);
    },
    onError: (err) => {
      resetErrors.value = err;
      error('Gagal mereset password.');
    },
    onFinish: () => {
      isSubmittingReset.value = false;
    }
  });
}

// -----------------------------------------------------------------------------
// MODAL: RIWAYAT PENDAFTARAN LOMBA
// -----------------------------------------------------------------------------
const isHistoryModalOpen = ref(false);
const activeRegistrantForHistory = ref(null);

function openHistoryModal(item) {
  activeRegistrantForHistory.value = item;
  isHistoryModalOpen.value = true;
}

// -----------------------------------------------------------------------------
// EKSPOR CSV
// -----------------------------------------------------------------------------
function triggerExportCsv() {
  const params = new URLSearchParams();
  if (searchInput.value) params.set('search', searchInput.value);
  if (roleFilter.value !== 'all') params.set('role', roleFilter.value);
  if (statusFilter.value !== 'all') params.set('status', statusFilter.value);
  if (loginMethodFilter.value !== 'all') params.set('login_method', loginMethodFilter.value);
  if (districtFilter.value !== 'all') params.set('district', districtFilter.value);

  window.location.href = `/admin/akun-pendaftar/export-csv?${params.toString()}`;
}
</script>

<template>
  <VelzonLayout>
    <Head title="Manajemen Akun Pendaftar - Admin SEMARAK" />

    <div class="container-fluid py-4 px-3 px-md-4">
      <!-- ============================================================== -->
      <!-- 1. BREADCRUMB & HEADER                                         -->
      <!-- ============================================================== -->
      <div class="row mb-3 pb-1 align-items-center">
        <div class="col-12 col-md-7 mb-2 mb-md-0">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0 fs-12 fw-medium text-muted">
              <li class="breadcrumb-item"><Link href="/admin" class="text-decoration-none text-muted">Dashboard</Link></li>
              <li class="breadcrumb-item text-muted">Administrasi Pendaftaran</li>
              <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Akun Pendaftar</li>
            </ol>
          </nav>
          <h1 class="h3 fw-bold mt-1 mb-0 page-main-title d-flex align-items-center gap-2">
            <i class="ri-account-circle-line text-primary"></i>
            <span>Manajemen Akun Pendaftar</span>
          </h1>
          <p class="text-muted fs-13 mb-0 mt-0.5">
            Daftar seluruh akun guru pembina, koordinator kontingen, dan siswa peserta yang terdaftar di portal SEMARAK.
          </p>
        </div>

        <div class="col-12 col-md-5 d-flex align-items-center justify-content-md-end gap-2">
          <button 
            type="button" 
            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-3 fw-medium"
            @click="resetFilters"
            title="Muat ulang data tabel"
          >
            <i class="ri-refresh-line fs-14"></i>
            <span>Refresh</span>
          </button>

          <button 
            type="button" 
            class="btn btn-success btn-sm d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-3 fw-medium text-white shadow-sm"
            @click="triggerExportCsv"
            title="Unduh seluruh data akun dalam format file CSV / Excel"
          >
            <i class="ri-file-excel-2-line fs-15"></i>
            <span>Ekspor CSV</span>
          </button>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- 2. STATISTIC METRIC CARDS                                      -->
      <!-- ============================================================== -->
      <div class="row g-3 mb-4">
        <!-- Card 1: Total Akun -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 metric-stat-card card-hover-lift">
            <div class="card-body p-3 p-md-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrapper rounded-3 bg-primary-subtle text-primary flex-shrink-0 d-flex align-items-center justify-content-center">
                <i class="ri-user-shared-line fs-22"></i>
              </div>
              <div class="min-w-0 flex-grow-1">
                <span class="d-block fs-12 fw-medium text-muted text-uppercase mb-0.5" style="letter-spacing: 0.5px;">Total Akun</span>
                <h3 class="fw-bold mb-0 fs-20 text-body lh-1">{{ metrics.total_accounts || 0 }}</h3>
                <span class="fs-11 text-muted d-block mt-1 text-truncate">
                  <i class="ri-checkbox-circle-fill text-success me-0.5"></i> {{ metrics.with_registrations_count || 0 }} sudah daftar lomba
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 2: Akun Aktif -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 metric-stat-card card-hover-lift">
            <div class="card-body p-3 p-md-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrapper rounded-3 bg-success-subtle text-success flex-shrink-0 d-flex align-items-center justify-content-center">
                <i class="ri-shield-check-line fs-22"></i>
              </div>
              <div class="min-w-0 flex-grow-1">
                <span class="d-block fs-12 fw-medium text-muted text-uppercase mb-0.5" style="letter-spacing: 0.5px;">Akun Aktif</span>
                <h3 class="fw-bold mb-0 fs-20 text-body lh-1">{{ metrics.active_accounts || 0 }}</h3>
                <span class="fs-11 text-muted d-block mt-1 text-truncate">
                  <i class="ri-close-circle-line text-danger me-0.5"></i> {{ metrics.inactive_accounts || 0 }} nonaktif
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 3: Guru Pendamping & Koordinator -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 metric-stat-card card-hover-lift">
            <div class="card-body p-3 p-md-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrapper rounded-3 bg-warning-subtle text-warning flex-shrink-0 d-flex align-items-center justify-content-center">
                <i class="ri-user-star-line fs-22"></i>
              </div>
              <div class="min-w-0 flex-grow-1">
                <span class="d-block fs-12 fw-medium text-muted text-uppercase mb-0.5" style="letter-spacing: 0.5px;">Guru &amp; Pembina</span>
                <h3 class="fw-bold mb-0 fs-20 text-body lh-1">{{ (metrics.role_pendamping_count || 0) + (metrics.role_koordinator_count || 0) }}</h3>
                <span class="fs-11 text-muted d-block mt-1 text-truncate">
                  {{ metrics.role_pendamping_count || 0 }} Guru • {{ metrics.role_koordinator_count || 0 }} Koord
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 4: Siswa & Login Google -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 h-100 metric-stat-card card-hover-lift">
            <div class="card-body p-3 p-md-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrapper rounded-3 bg-info-subtle text-info flex-shrink-0 d-flex align-items-center justify-content-center">
                <i class="ri-google-fill fs-22"></i>
              </div>
              <div class="min-w-0 flex-grow-1">
                <span class="d-block fs-12 fw-medium text-muted text-uppercase mb-0.5" style="letter-spacing: 0.5px;">Login Google</span>
                <h3 class="fw-bold mb-0 fs-20 text-body lh-1">{{ metrics.google_accounts_count || 0 }}</h3>
                <span class="fs-11 text-muted d-block mt-1 text-truncate">
                  {{ metrics.role_siswa_count || 0 }} Siswa Mandiri
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- 3. FILTER & SEARCH CONTROL CARD                                -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 filter-control-card">
        <div class="card-body p-3 p-md-4">
          <div class="row g-2.5 align-items-center">
            <!-- Pencarian Teks -->
            <div class="col-12 col-md-4 col-xl-3">
              <div class="position-relative">
                <input 
                  type="text" 
                  v-model="searchInput" 
                  @input="handleSearchInput"
                  class="form-control form-control-md rounded-3 ps-4 pe-3 filter-input-box" 
                  placeholder="Cari nama, email, WA, sekolah..." 
                  autocomplete="off"
                />
                <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2.5 text-muted fs-15"></i>
              </div>
            </div>

            <!-- Filter Peran (MANDATORY: CustomSelect) -->
            <div class="col-6 col-md-3 col-xl-2">
              <CustomSelect 
                v-model="roleFilter" 
                :options="roleFilterOptions" 
                size="md"
                placeholder="Pilih Peran"
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Filter Status (MANDATORY: CustomSelect) -->
            <div class="col-6 col-md-2 col-xl-2">
              <CustomSelect 
                v-model="statusFilter" 
                :options="statusFilterOptions" 
                size="md"
                placeholder="Pilih Status"
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Filter Metode Login (MANDATORY: CustomSelect) -->
            <div class="col-6 col-md-3 col-xl-2">
              <CustomSelect 
                v-model="loginMethodFilter" 
                :options="loginMethodOptions" 
                size="md"
                placeholder="Metode Login"
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Filter Kecamatan (MANDATORY: CustomSelect) -->
            <div class="col-6 col-md-4 col-xl-2">
              <CustomSelect 
                v-model="districtFilter" 
                :options="districtFilterOptions" 
                size="md"
                searchable
                placeholder="Semua Kecamatan"
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Tombol Reset Filter -->
            <div class="col-12 col-xl-1 text-xl-end">
              <button 
                type="button" 
                class="btn btn-outline-danger btn-md w-100 d-inline-flex align-items-center justify-content-center gap-1 rounded-3"
                @click="resetFilters"
                title="Hapus semua filter"
              >
                <i class="ri-filter-off-line fs-14"></i>
                <span class="d-xl-none">Reset Filter</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- 4. TABEL DATA AKUN PENDAFTAR                                   -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 main-table-card">
        <div class="card-header bg-transparent border-bottom p-3.5 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="table-header-title fw-bold fs-15 text-body">Daftar Akun Pengguna</span>
            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1 fs-11 fw-semibold">
              {{ registrants.total || 0 }} Akun
            </span>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="fs-12 text-muted d-none d-sm-inline">Tampilkan:</span>
            <div style="width: 150px;">
              <CustomSelect 
                v-model="perPage" 
                :options="perPageOptions" 
                size="sm"
                @update:modelValue="applyFilters"
              />
            </div>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 custom-data-table">
            <thead class="table-thead-custom fs-12 text-uppercase text-muted">
              <tr>
                <th scope="col" class="ps-3.5" style="width: 50px;">No</th>
                <th scope="col" style="min-width: 240px;">Pendaftar</th>
                <th scope="col" style="min-width: 220px;">Kontak &amp; WhatsApp</th>
                <th scope="col" style="min-width: 210px;">Asal Lembaga</th>
                <th scope="col" style="min-width: 140px;">Peran Akun</th>
                <th scope="col" style="min-width: 130px;">Login</th>
                <th scope="col" class="text-center" style="min-width: 120px;">Lomba</th>
                <th scope="col" class="text-center" style="min-width: 110px;">Status</th>
                <th scope="col" style="min-width: 140px;">Terdaftar</th>
                <th scope="col" class="text-end pe-3.5" style="min-width: 160px;">Aksi</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <tr v-if="!registrants.data || registrants.data.length === 0">
                <td colspan="10" class="text-center py-5">
                  <div class="empty-state-box py-4">
                    <div class="empty-state-icon mx-auto rounded-circle d-flex align-items-center justify-content-center mb-3">
                      <i class="ri-user-search-line fs-32 text-muted"></i>
                    </div>
                    <h5 class="fw-semibold text-body mb-1">Tidak Ada Akun Ditemukan</h5>
                    <p class="text-muted fs-13 mb-3">Tidak ada data akun pendaftar yang cocok dengan filter atau kata kunci saat ini.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 px-3" @click="resetFilters">
                      <i class="ri-refresh-line me-1"></i> Bersihkan Filter
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-for="(item, idx) in registrants.data" :key="item.id" class="table-row-item">
                <!-- No -->
                <td class="ps-3.5 text-muted fs-12 fw-medium">
                  {{ (registrants.current_page - 1) * registrants.per_page + idx + 1 }}
                </td>

                <!-- Pendaftar (Avatar & Nama) -->
                <td>
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="avatar-box flex-shrink-0">
                      <img 
                        v-if="item.avatar_url" 
                        :src="item.avatar_url" 
                        :alt="item.name" 
                        class="rounded-circle object-fit-cover shadow-xs border"
                        style="width: 38px; height: 38px;"
                      />
                      <div 
                        v-else 
                        class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-12 user-initial-avatar shadow-xs"
                        style="width: 38px; height: 38px;"
                      >
                        {{ getInitials(item.name) }}
                      </div>
                    </div>
                    <div class="min-w-0">
                      <div class="fw-semibold text-body fs-13.5 text-truncate" style="max-width: 180px;">
                        {{ item.name }}
                      </div>
                      <div class="d-flex align-items-center gap-1.5 mt-0.5">
                        <span class="badge bg-light text-secondary border fs-10 px-1.5 py-0.5 rounded">
                          ID: #{{ item.id }}
                        </span>
                        <span v-if="item.gender" class="text-muted fs-11">
                          • {{ item.gender === 'Laki-laki' ? 'L' : 'P' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Kontak & WhatsApp -->
                <td>
                  <div class="d-flex flex-column gap-1">
                    <span class="text-body text-truncate fs-12.5" style="max-width: 210px;" :title="item.email">
                      <i class="ri-mail-line text-muted me-1"></i>{{ item.email }}
                    </span>
                    <div class="d-flex align-items-center gap-1.5">
                      <span class="text-muted fs-12 font-monospace">
                        <i class="ri-phone-line text-muted me-1"></i>{{ item.phone || '-' }}
                      </span>
                      <a 
                        v-if="item.phone" 
                        :href="getWaUrl(item)" 
                        target="_blank" 
                        class="btn-wa-chat rounded-pill d-inline-flex align-items-center gap-1 text-decoration-none px-2 py-0.5"
                        title="Buka Chat WhatsApp Resmi Panitia"
                      >
                        <i class="ri-whatsapp-fill fs-13 text-success"></i>
                        <span class="fs-10 fw-semibold text-success">Chat</span>
                      </a>
                    </div>
                  </div>
                </td>

                <!-- Asal Lembaga / Sekolah & Kecamatan -->
                <td>
                  <div class="d-flex flex-column gap-0.5">
                    <span class="fw-semibold text-body fs-12.5 text-truncate" style="max-width: 200px;" :title="item.school_name">
                      {{ item.school_name || 'Belum Mengisi Sekolah' }}
                    </span>
                    <span v-if="item.school_district" class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0.5 fs-10 fw-medium align-self-start">
                      Kec. {{ item.school_district }}
                    </span>
                  </div>
                </td>

                <!-- Peran Akun -->
                <td>
                  <span 
                    v-if="item.registrant_role === 'Koordinator'" 
                    class="badge bg-purple-subtle text-purple border-0 rounded-pill px-2.5 py-1 fs-11 fw-semibold d-inline-flex align-items-center gap-1"
                  >
                    <i class="ri-shield-star-line"></i> Koordinator
                  </span>
                  <span 
                    v-else-if="item.registrant_role === 'Siswa Peserta'" 
                    class="badge bg-info-subtle text-info border-0 rounded-pill px-2.5 py-1 fs-11 fw-semibold d-inline-flex align-items-center gap-1"
                  >
                    <i class="ri-user-smile-line"></i> Siswa Mandiri
                  </span>
                  <span 
                    v-else 
                    class="badge bg-primary-subtle text-primary border-0 rounded-pill px-2.5 py-1 fs-11 fw-semibold d-inline-flex align-items-center gap-1"
                  >
                    <i class="ri-user-star-line"></i> Guru Pembina
                  </span>
                </td>

                <!-- Metode Login -->
                <td>
                  <span 
                    v-if="item.google_id" 
                    class="badge bg-danger-subtle text-danger border-0 rounded-pill px-2 py-1 fs-11 fw-medium d-inline-flex align-items-center gap-1"
                  >
                    <i class="ri-google-fill"></i> Google
                  </span>
                  <span 
                    v-else 
                    class="badge bg-secondary-subtle text-secondary border-0 rounded-pill px-2 py-1 fs-11 fw-medium d-inline-flex align-items-center gap-1"
                  >
                    <i class="ri-lock-password-line"></i> Sandi
                  </span>
                </td>

                <!-- Riwayat Pendaftaran Lomba (Counter Button) -->
                <td class="text-center">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-subtle-info rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 border-0"
                    @click="openHistoryModal(item)"
                    :title="`Klik untuk melihat ${item.registrations_count || 0} cabang lomba yang diajukan`"
                  >
                    <i class="ri-trophy-line fs-12"></i>
                    <span class="fw-bold fs-11">{{ item.registrations_count || 0 }} Lomba</span>
                  </button>
                </td>

                <!-- Status Akun (Toggle) -->
                <td class="text-center">
                  <button 
                    type="button" 
                    class="btn btn-sm border-0 rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1 fw-semibold fs-11 transition-all"
                    :class="item.is_active ? 'btn-status-active' : 'btn-status-inactive'"
                    @click="handleToggleStatus(item)"
                    :title="item.is_active ? 'Klik untuk menonaktifkan akun' : 'Klik untuk mengaktifkan akun'"
                  >
                    <i :class="item.is_active ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"></i>
                    <span>{{ item.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                  </button>
                </td>

                <!-- Terdaftar (MANDATORY: Format dd/mm/yyyy HH:mm) -->
                <td>
                  <span class="fs-12 text-muted fw-medium d-block text-nowrap">
                    {{ formatDate(item.created_at) }}
                  </span>
                </td>

                <!-- Kolom Aksi -->
                <td class="pe-3.5 text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <!-- Tombol Edit Data -->
                    <button 
                      type="button" 
                      class="btn btn-sm btn-icon-action btn-outline-primary rounded-2 p-1.5"
                      @click="openEditModal(item)"
                      title="Ubah Data Profil Akun"
                    >
                      <i class="ri-edit-line fs-14"></i>
                    </button>

                    <!-- Tombol Reset Password -->
                    <button 
                      type="button" 
                      class="btn btn-sm btn-icon-action btn-outline-warning rounded-2 p-1.5"
                      @click="openResetModal(item)"
                      title="Reset Password Akun"
                    >
                      <i class="ri-key-2-line fs-14"></i>
                    </button>

                    <!-- Tombol Hapus Akun -->
                    <button 
                      type="button" 
                      class="btn btn-sm btn-icon-action btn-outline-danger rounded-2 p-1.5"
                      @click="handleDeleteAccount(item)"
                      title="Hapus Akun Pendaftar"
                    >
                      <i class="ri-delete-bin-line fs-14"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- ============================================================ -->
        <!-- PAGINATION                                                   -->
        <!-- ============================================================ -->
        <div v-if="registrants.links && registrants.links.length > 3" class="card-footer bg-transparent border-top p-3 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
          <span class="fs-12 text-muted">
            Menampilkan data <strong>{{ registrants.from || 0 }}</strong> sampai <strong>{{ registrants.to || 0 }}</strong> dari total <strong>{{ registrants.total || 0 }}</strong> akun
          </span>

          <nav aria-label="Navigasi Halaman">
            <ul class="pagination pagination-sm m-0 gap-1 flex-wrap justify-content-center">
              <li 
                v-for="(link, i) in registrants.links" 
                :key="i" 
                class="page-item" 
                :class="{ 'active': link.active, 'disabled': !link.url }"
              >
                <Link 
                  v-if="link.url" 
                  :href="link.url" 
                  class="page-link rounded-2 px-2.5 py-1 fs-12"
                  v-html="link.label"
                />
                <span 
                  v-else 
                  class="page-link rounded-2 px-2.5 py-1 fs-12 text-muted opacity-50"
                  v-html="link.label"
                />
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: EDIT PROFIL AKUN PENDAFTAR                            -->
    <!-- ============================================================== -->
    <div v-if="isEditModalOpen" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom modal-dialog-scrollable bg-body rounded-4 shadow-xl border w-100 animate__animated animate__fadeInUp animate__faster" style="max-width: 580px;">
        <div class="modal-header-custom p-3.5 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center">
              <i class="ri-user-settings-line fs-18"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold fs-16 mb-0 text-body">Ubah Data Akun Pendaftar</h5>
              <small class="text-muted">Perbarui data profil identitas peserta atau pembina</small>
            </div>
          </div>
          <button type="button" class="btn-close-custom" @click="isEditModalOpen = false" aria-label="Tutup">
            <i class="ri-close-line fs-18"></i>
          </button>
        </div>

        <form @submit.prevent="submitEditForm">
          <div class="modal-body-custom p-3.5 p-md-4">
            <div class="row g-3">
              <!-- Nama Lengkap -->
              <div class="col-12">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  v-model="editForm.name" 
                  class="form-control form-control-md rounded-3" 
                  placeholder="Contoh: Ahmad Fauzi, S.Pd" 
                  required
                />
                <div v-if="editErrors.name" class="text-danger fs-11 mt-1">{{ editErrors.name }}</div>
              </div>

              <!-- Email -->
              <div class="col-md-6">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Email <span class="text-danger">*</span></label>
                <input 
                  type="email" 
                  v-model="editForm.email" 
                  class="form-control form-control-md rounded-3" 
                  placeholder="nama@email.com" 
                  required
                />
                <div v-if="editErrors.email" class="text-danger fs-11 mt-1">{{ editErrors.email }}</div>
              </div>

              <!-- Nomor WhatsApp -->
              <div class="col-md-6">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Nomor WhatsApp</label>
                <input 
                  type="text" 
                  v-model="editForm.phone" 
                  class="form-control form-control-md rounded-3" 
                  placeholder="081234567890" 
                />
                <div v-if="editErrors.phone" class="text-danger fs-11 mt-1">{{ editErrors.phone }}</div>
              </div>

              <!-- Peran Akun (MANDATORY: CustomSelect) -->
              <div class="col-md-6">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Peran Akun <span class="text-danger">*</span></label>
                <CustomSelect 
                  v-model="editForm.registrant_role" 
                  :options="modalRoleOptions" 
                  size="md"
                />
                <div v-if="editErrors.registrant_role" class="text-danger fs-11 mt-1">{{ editErrors.registrant_role }}</div>
              </div>

              <!-- Jenis Kelamin (MANDATORY: CustomSelect) -->
              <div class="col-md-6">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Jenis Kelamin</label>
                <CustomSelect 
                  v-model="editForm.gender" 
                  :options="genderOptions" 
                  size="md"
                />
              </div>

              <!-- Asal Lembaga / Sekolah -->
              <div class="col-12">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Asal Lembaga / Sekolah</label>
                <input 
                  type="text" 
                  v-model="editForm.school_name" 
                  class="form-control form-control-md rounded-3" 
                  placeholder="Contoh: MTs Unggulan Al-Qodiri" 
                />
              </div>

              <!-- Kecamatan Lembaga (MANDATORY: CustomSelect) -->
              <div class="col-12">
                <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">Kecamatan Lembaga</label>
                <CustomSelect 
                  v-model="editForm.school_district" 
                  :options="modalDistrictOptions" 
                  size="md"
                  searchable
                  placeholder="Pilih Kecamatan"
                />
              </div>

              <!-- Status Akun Aktif -->
              <div class="col-12 pt-1">
                <div class="form-check form-switch d-flex align-items-center gap-2">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    role="switch" 
                    id="switchActiveAccount" 
                    v-model="editForm.is_active"
                    style="cursor: pointer;"
                  />
                  <label class="form-check-label fs-13 fw-medium" for="switchActiveAccount" style="cursor: pointer;">
                    Status Akun Aktif (Dapat Login ke Portal)
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer-custom p-3 border-top d-flex align-items-center justify-content-end gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5"
              @click="isEditModalOpen = false"
              :disabled="isSubmittingEdit"
            >
              Batal
            </button>
            <button 
              type="submit" 
              class="btn btn-sm btn-primary rounded-3 px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5"
              :disabled="isSubmittingEdit"
            >
              <span v-if="isSubmittingEdit" class="spinner-border spinner-border-sm" role="status"></span>
              <span>{{ isSubmittingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: RESET PASSWORD AKUN                                   -->
    <!-- ============================================================== -->
    <div v-if="isResetModalOpen" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom bg-body rounded-4 shadow-xl border w-100 animate__animated animate__fadeInUp animate__faster" style="max-width: 480px;">
        <div class="modal-header-custom p-3.5 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-warning-subtle text-warning p-2 d-flex align-items-center justify-content-center">
              <i class="ri-key-2-line fs-18"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold fs-16 mb-0 text-body">Reset Password Akun</h5>
              <small class="text-muted">Atur kata sandi baru untuk pendaftar</small>
            </div>
          </div>
          <button type="button" class="btn-close-custom" @click="isResetModalOpen = false" aria-label="Tutup">
            <i class="ri-close-line fs-18"></i>
          </button>
        </div>

        <form @submit.prevent="submitResetPassword">
          <div class="modal-body-custom p-3.5 p-md-4">
            <div class="alert alert-info border-0 rounded-3 p-3 fs-12 mb-3">
              <i class="ri-information-line me-1 fs-14 align-middle"></i>
              Anda sedang mereset password untuk akun: <strong>{{ resetForm.name }}</strong> (<code>{{ resetForm.email }}</code>).
            </div>

            <div class="mb-3">
              <label class="form-label fs-12 fw-semibold text-muted text-uppercase mb-1">
                Password Baru <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input 
                  :type="showResetPassword ? 'text' : 'password'" 
                  v-model="resetForm.new_password" 
                  class="form-control rounded-start-3 font-monospace fs-14 fw-bold" 
                  placeholder="Minimal 6 karakter" 
                  required
                />
                <button 
                  type="button" 
                  class="btn btn-outline-secondary" 
                  @click="showResetPassword = !showResetPassword"
                  title="Lihat password"
                >
                  <i :class="showResetPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                </button>
                <button 
                  type="button" 
                  class="btn btn-outline-primary rounded-end-3" 
                  @click="resetForm.new_password = generateRandomPassword()"
                  title="Acak password baru"
                >
                  <i class="ri-magic-line me-1"></i> Acak
                </button>
              </div>
              <div v-if="resetErrors.password" class="text-danger fs-11 mt-1">{{ resetErrors.password }}</div>
            </div>

            <!-- Tombol Bantuan Salin & Kirim WA -->
            <div class="d-flex align-items-center gap-2 pt-1">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-secondary rounded-2 flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1"
                @click="copyResetCredentials"
              >
                <i class="ri-file-copy-line fs-14"></i>
                <span>Salin Kredensial</span>
              </button>

              <a 
                v-if="resetForm.phone" 
                :href="getWaChatUrl(resetForm.phone, `Halo Bapak/Ibu/Sdr *${resetForm.name}*,\n\nPassword akun SEMARAK ALIYAH Anda telah berhasil direset oleh Panitia.\n\nBerikut kredensial login Anda:\nEmail: *${resetForm.email}*\nPassword Baru: *${resetForm.new_password}*\n\nSilakan masuk melalui tautan: ${window.location.origin}/pendaftar/login\n\nTerima kasih.`)" 
                target="_blank" 
                class="btn btn-sm btn-outline-success rounded-2 flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1 text-decoration-none"
              >
                <i class="ri-whatsapp-line fs-14"></i>
                <span>Kirim via WA</span>
              </a>
            </div>
          </div>

          <div class="modal-footer-custom p-3 border-top d-flex align-items-center justify-content-end gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-1.5"
              @click="isResetModalOpen = false"
              :disabled="isSubmittingReset"
            >
              Batal
            </button>
            <button 
              type="submit" 
              class="btn btn-sm btn-warning rounded-3 px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5"
              :disabled="isSubmittingReset"
            >
              <span v-if="isSubmittingReset" class="spinner-border spinner-border-sm" role="status"></span>
              <span>{{ isSubmittingReset ? 'Mereset...' : 'Terapkan Password' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: RIWAYAT PENDAFTARAN LOMBA                             -->
    <!-- ============================================================== -->
    <div v-if="isHistoryModalOpen" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom modal-dialog-scrollable bg-body rounded-4 shadow-xl border w-100 animate__animated animate__fadeInUp animate__faster" style="max-width: 680px;">
        <div class="modal-header-custom p-3.5 border-bottom d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-info-subtle text-info p-2 d-flex align-items-center justify-content-center">
              <i class="ri-trophy-line fs-18"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold fs-16 mb-0 text-body">Riwayat Pendaftaran Lomba</h5>
              <small class="text-muted">Delegasi cabang lomba yang didaftarkan oleh akun ini</small>
            </div>
          </div>
          <button type="button" class="btn-close-custom" @click="isHistoryModalOpen = false" aria-label="Tutup">
            <i class="ri-close-line fs-18"></i>
          </button>
        </div>

        <div class="modal-body-custom p-3.5 p-md-4">
          <div v-if="activeRegistrantForHistory" class="p-3 rounded-3 bg-light border mb-3 d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold text-body fs-14">{{ activeRegistrantForHistory.name }}</div>
              <small class="text-muted">{{ activeRegistrantForHistory.school_name || 'Sekolah Tidak Dicantumkan' }} • {{ activeRegistrantForHistory.email }}</small>
            </div>
            <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fs-11">
              {{ activeRegistrantForHistory.registrations?.length || 0 }} Pendaftaran
            </span>
          </div>

          <div v-if="!activeRegistrantForHistory?.registrations || activeRegistrantForHistory.registrations.length === 0" class="text-center py-4">
            <i class="ri-file-list-3-line fs-32 text-muted mb-2 d-block"></i>
            <span class="text-muted fs-13">Akun ini belum mengajukan pendaftaran cabang lomba apapun.</span>
          </div>

          <div v-else class="list-group list-group-flush border rounded-3 overflow-hidden">
            <div 
              v-for="reg in activeRegistrantForHistory.registrations" 
              :key="reg.id" 
              class="list-group-item p-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2"
            >
              <div class="min-w-0">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge bg-primary-subtle text-primary font-monospace fs-11 px-2 py-0.5 rounded">
                    {{ reg.reg_code }}
                  </span>
                  <span class="fw-semibold text-body fs-13 text-truncate">{{ reg.competition_title }}</span>
                </div>
                <div class="fs-12 text-muted">
                  Waktu Daftar: {{ formatDate(reg.created_at) }} • Biaya: <strong>{{ formatCurrency(reg.total_fee) }}</strong>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span 
                  v-if="reg.payment_status === 'Lunas'" 
                  class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1 fs-11 fw-semibold"
                >
                  <i class="ri-checkbox-circle-fill me-0.5"></i> Lunas
                </span>
                <span 
                  v-else-if="reg.payment_status === 'Menunggu Verifikasi'" 
                  class="badge bg-warning-subtle text-warning rounded-pill px-2.5 py-1 fs-11 fw-semibold"
                >
                  <i class="ri-time-line me-0.5"></i> Menunggu Verifikasi
                </span>
                <span 
                  v-else 
                  class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1 fs-11 fw-semibold"
                >
                  <i class="ri-close-circle-fill me-0.5"></i> Belum Lunas
                </span>

                <Link 
                  :href="`/admin/pendaftar/${reg.id}`" 
                  class="btn btn-sm btn-outline-primary rounded-2 px-2 py-1 fs-11 text-decoration-none"
                  title="Buka Lembar Verifikasi Pendaftaran"
                >
                  <i class="ri-external-link-line me-0.5"></i> Detail
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer-custom p-3 border-top d-flex align-items-center justify-content-end">
          <button 
            type="button" 
            class="btn btn-sm btn-secondary rounded-3 px-3.5 py-1.5"
            @click="isHistoryModalOpen = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
/* ==========================================================================
   STYLING KHUSUS TABEL & KOMPONEN AKUN PENDAFTAR (WCAG AA & VELZON THEME)
   ========================================================================== */
.page-main-title {
  color: #0f172a;
}
:root.dark .page-main-title,
.dark .page-main-title {
  color: #ffffff !important;
}

.metric-stat-card {
  background-color: #ffffff;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
:root.dark .metric-stat-card,
.dark .metric-stat-card {
  background-color: #111827 !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.card-hover-lift:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06) !important;
}

.stat-icon-wrapper {
  width: 46px;
  height: 46px;
}

.filter-control-card,
.main-table-card {
  background-color: #ffffff;
}
:root.dark .filter-control-card,
:root.dark .main-table-card,
.dark .filter-control-card,
.dark .main-table-card {
  background-color: #111827 !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.table-thead-custom th {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding-top: 0.75rem;
  padding-bottom: 0.75rem;
  letter-spacing: 0.5px;
  font-weight: 600;
}
:root.dark .table-thead-custom th,
.dark .table-thead-custom th {
  background-color: #1e293b !important;
  color: #cbd5e1 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

.table-row-item {
  transition: background-color 0.15s ease;
}
.table-row-item:hover {
  background-color: rgba(59, 130, 246, 0.03);
}
:root.dark .table-row-item:hover,
.dark .table-row-item:hover {
  background-color: rgba(255, 255, 255, 0.02) !important;
}

.user-initial-avatar {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

/* WhatsApp Direct Chat Pill */
.btn-wa-chat {
  background-color: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.25);
  transition: all 0.2s ease;
}
.btn-wa-chat:hover {
  background-color: rgba(34, 197, 94, 0.2);
  transform: scale(1.02);
}

/* Badge Subtle Khusus Mode Terang & Gelap (Aturan 3) */
.text-purple {
  color: #7c3aed !important;
}
.bg-purple-subtle {
  background-color: rgba(124, 58, 237, 0.1) !important;
}
:root.dark .text-purple,
.dark .text-purple {
  color: #c084fc !important;
}

/* Status Active / Inactive Buttons */
.btn-status-active {
  background-color: rgba(34, 197, 94, 0.12);
  color: #15803d;
}
:root.dark .btn-status-active,
.dark .btn-status-active {
  background-color: rgba(34, 197, 94, 0.2);
  color: #86efac !important;
}

.btn-status-inactive {
  background-color: rgba(239, 68, 68, 0.12);
  color: #b91c1c;
}
:root.dark .btn-status-inactive,
.dark .btn-status-inactive {
  background-color: rgba(239, 68, 68, 0.2);
  color: #fca5a5 !important;
}

.btn-subtle-info {
  background-color: rgba(14, 165, 233, 0.1);
  color: #0284c7;
}
:root.dark .btn-subtle-info,
.dark .btn-subtle-info {
  background-color: rgba(14, 165, 233, 0.2);
  color: #7dd3fc !important;
}

.btn-icon-action {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* Modal Custom Backdrop & Box */
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(4px);
  z-index: 1060;
}

.modal-dialog-custom {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-body-custom {
  overflow-y: auto;
}

.btn-close-custom {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-close-custom:hover {
  background-color: rgba(0, 0, 0, 0.05);
  color: #0f172a;
}
:root.dark .btn-close-custom:hover,
.dark .btn-close-custom:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.empty-state-icon {
  width: 64px;
  height: 64px;
  background-color: rgba(148, 163, 184, 0.12);
}
</style>

