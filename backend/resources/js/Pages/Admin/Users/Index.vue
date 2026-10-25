<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  metrics: { type: Object, default: () => ({}) },
  competitions: { type: Array, default: () => [] },
  divisions: { type: Array, default: () => [] },
  roles: { type: Object, default: () => ({}) },
  currentUserId: { type: Number, default: 0 },
});

const { success, error } = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// -----------------------------------------------------------------------------
// FILTER & SEARCH STATE
// -----------------------------------------------------------------------------
const searchInput = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const divisionFilter = ref(props.filters.division || '');
const statusFilter = ref(props.filters.status || '');

function applyFilters() {
  router.get('/admin/pengguna', {
    search: searchInput.value || undefined,
    role: roleFilter.value || undefined,
    division: divisionFilter.value || undefined,
    status: statusFilter.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
}

function resetFilters() {
  searchInput.value = '';
  roleFilter.value = '';
  divisionFilter.value = '';
  statusFilter.value = '';
  router.get('/admin/pengguna', {}, {
    preserveState: true,
    preserveScroll: true,
  });
}

// -----------------------------------------------------------------------------
// MODAL STATE: CREATE & EDIT USER
// -----------------------------------------------------------------------------
const isModalOpen = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);
const showPassword = ref(false);

const form = ref({
  id: null,
  name: '',
  email: '',
  nip: '',
  password: '',
  role: 'panitia',
  division: 'Acara & Lomba',
  phone: '',
  competition_id: '',
  is_active: true,
});

const formErrors = ref({});

function openCreateModal() {
  isEditing.value = false;
  formErrors.value = {};
  showPassword.value = false;
  form.value = {
    id: null,
    name: '',
    email: '',
    nip: '',
    password: '',
    role: 'panitia',
    division: 'Acara & Lomba',
    phone: '',
    competition_id: '',
    is_active: true,
  };
  isModalOpen.value = true;
}

function openEditModal(user) {
  isEditing.value = true;
  formErrors.value = {};
  showPassword.value = false;
  form.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    nip: user.nip || '',
    password: '', // Kosongkan saat edit jika tidak ingin ubah password
    role: user.role || 'panitia',
    division: user.division || '',
    phone: user.phone || '',
    competition_id: user.competition_id || '',
    is_active: user.is_active !== false,
  };
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  formErrors.value = {};
}

function submitForm() {
  isSubmitting.value = true;
  formErrors.value = {};

  if (isEditing.value) {
    router.put(`/admin/pengguna/${form.value.id}`, form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false;
        closeModal();
        success('Data pengguna / panitia berhasil diperbarui!');
      },
      onError: (errs) => {
        isSubmitting.value = false;
        formErrors.value = errs;
        error(Object.values(errs)[0] || 'Gagal menyimpan perubahan pengguna.');
      }
    });
  } else {
    router.post('/admin/pengguna', form.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmitting.value = false;
        closeModal();
        success('Pengguna / panitia baru berhasil ditambahkan!');
      },
      onError: (errs) => {
        isSubmitting.value = false;
        formErrors.value = errs;
        error(Object.values(errs)[0] || 'Gagal menambahkan pengguna baru.');
      }
    });
  }
}

// -----------------------------------------------------------------------------
// STATUS TOGGLE & DELETE ACTIONS
// -----------------------------------------------------------------------------
async function toggleStatus(user) {
  if (user.id === props.currentUserId) {
    error('Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif.');
    return;
  }

  const actionText = user.is_active ? 'menonaktifkan' : 'mengaktifkan';
  const isDeactivating = user.is_active;

  const ok = await confirmDialog({
    title: `${isDeactivating ? 'Nonaktifkan' : 'Aktifkan'} Akun Pengguna?`,
    message: `Apakah Anda yakin ingin ${actionText} akun ${user.name} (${user.email})?`,
    type: isDeactivating ? 'warning' : 'success',
    confirmText: isDeactivating ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
    icon: isDeactivating ? 'ri-user-unfollow-line' : 'ri-user-shared-line',
  });

  if (!ok) {
    return;
  }

  router.post(`/admin/pengguna/${user.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Status akun ${user.name} berhasil diubah.`);
    },
    onError: (errs) => {
      error(Object.values(errs)[0] || 'Gagal mengubah status akun.');
    }
  });
}

const deleteModalOpen = ref(false);
const userToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(user) {
  if (user.id === props.currentUserId) {
    error('Anda tidak dapat menghapus akun Anda sendiri.');
    return;
  }
  userToDelete.value = user;
  deleteModalOpen.value = true;
}

function executeDelete() {
  if (!userToDelete.value) return;
  isDeleting.value = true;

  router.delete(`/admin/pengguna/${userToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleting.value = false;
      deleteModalOpen.value = false;
      userToDelete.value = null;
      success('Pengguna berhasil dihapus.');
    },
    onError: (errs) => {
      isDeleting.value = false;
      deleteModalOpen.value = false;
      error(Object.values(errs)[0] || 'Gagal menghapus pengguna.');
    }
  });
}

// Format Initial Avatar
function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

// Format WhatsApp Link
function getWaLink(phone) {
  if (!phone) return null;
  return getWaChatUrl(phone);
}

// -----------------------------------------------------------------------------
// SIPRESENSI SYNC STATE & ACTIONS
// -----------------------------------------------------------------------------
const isSyncModalOpen = ref(false);
const isPreviewLoading = ref(false);
const isSyncing = ref(false);
const syncError = ref(null);
const sipresensiData = ref({
  connected: false,
  message: '',
  connection: {},
  total: 0,
  teachers: [],
});
const syncSearch = ref('');
const selectedTeacherIds = ref([]);
const syncSettings = ref({
  default_role: 'panitia',
  default_division: '',
  default_password: 'panitia123',
  update_existing: false,
});
const syncSummary = ref(null);

const filteredTeachers = computed(() => {
  if (!sipresensiData.value.teachers) return [];
  const q = syncSearch.value.trim().toLowerCase();
  if (!q) return sipresensiData.value.teachers;
  return sipresensiData.value.teachers.filter(t => {
    return (t.name && t.name.toLowerCase().includes(q)) ||
           (t.email && t.email.toLowerCase().includes(q)) ||
           (t.username && t.username.toLowerCase().includes(q)) ||
           (t.sipresensi_position && t.sipresensi_position.toLowerCase().includes(q)) ||
           (t.phone && t.phone.toLowerCase().includes(q));
  });
});

const isAllSelected = computed(() => {
  if (filteredTeachers.value.length === 0) return false;
  return filteredTeachers.value.every(t => selectedTeacherIds.value.includes(t.sipresensi_id));
});

const newTeachersCount = computed(() => {
  if (!sipresensiData.value.teachers) return 0;
  return sipresensiData.value.teachers.filter(t => !t.already_exists).length;
});

const existingTeachersCount = computed(() => {
  if (!sipresensiData.value.teachers) return 0;
  return sipresensiData.value.teachers.filter(t => t.already_exists).length;
});

function toggleSelectAll(event) {
  const checked = event.target.checked;
  if (checked) {
    const idsToAdd = filteredTeachers.value.map(t => t.sipresensi_id);
    selectedTeacherIds.value = Array.from(new Set([...selectedTeacherIds.value, ...idsToAdd]));
  } else {
    const idsToRemove = new Set(filteredTeachers.value.map(t => t.sipresensi_id));
    selectedTeacherIds.value = selectedTeacherIds.value.filter(id => !idsToRemove.has(id));
  }
}

function selectOnlyNew() {
  selectedTeacherIds.value = filteredTeachers.value
    .filter(t => !t.already_exists)
    .map(t => t.sipresensi_id);
}

function selectAllTeachers() {
  selectedTeacherIds.value = filteredTeachers.value.map(t => t.sipresensi_id);
}

function clearTeacherSelection() {
  selectedTeacherIds.value = [];
}

async function openSyncModal() {
  isSyncModalOpen.value = true;
  isPreviewLoading.value = true;
  syncError.value = null;
  syncSummary.value = null;
  selectedTeacherIds.value = [];
  syncSearch.value = '';

  try {
    const res = await fetch('/admin/pengguna/sipresensi/preview');
    const data = await res.json();
    if (res.ok && data.success) {
      sipresensiData.value = data;
      // Otomatis pilih semua guru yang belum memiliki akun di Semarak
      selectedTeacherIds.value = (data.teachers || [])
        .filter(t => !t.already_exists)
        .map(t => t.sipresensi_id);
    } else {
      syncError.value = data.message || 'Gagal terhubung ke database Sipresensi.';
      sipresensiData.value = { connected: false, message: data.message, connection: data.connection || {}, total: 0, teachers: [] };
    }
  } catch (err) {
    syncError.value = 'Tidak dapat menghubungi server lokal untuk preview data Sipresensi: ' + err.message;
    sipresensiData.value = { connected: false, message: err.message, connection: {}, total: 0, teachers: [] };
  } finally {
    isPreviewLoading.value = false;
  }
}

function closeSyncModal() {
  isSyncModalOpen.value = false;
  syncSummary.value = null;
}

async function executeSync() {
  if (selectedTeacherIds.value.length === 0) {
    error('Silakan pilih setidaknya satu guru dari daftar untuk disinkronkan.');
    return;
  }

  isSyncing.value = true;
  syncError.value = null;

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch('/admin/pengguna/sipresensi/sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({
        teacher_ids: selectedTeacherIds.value,
        default_role: syncSettings.value.default_role,
        default_division: syncSettings.value.default_division || null,
        default_password: syncSettings.value.default_password || 'panitia123',
        update_existing: syncSettings.value.update_existing,
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      syncSummary.value = data;
      success(data.message || 'Sinkronisasi data guru berhasil dilakukan!');
      router.reload({ preserveScroll: true });
    } else {
      syncError.value = data.message || 'Gagal memproses sinkronisasi guru.';
      error(data.message || 'Terjadi kesalahan saat sinkronisasi.');
    }
  } catch (err) {
    syncError.value = 'Terjadi kesalahan sistem: ' + err.message;
    error(err.message);
  } finally {
    isSyncing.value = false;
  }
}
</script>

<template>
  <VelzonLayout>
    <Head title="Manajemen Pengguna & Panitia - Admin Panel" />

    <div class="container-fluid py-4">
      <!-- ============================================================== -->
      <!-- PAGE HEADER                                                    -->
      <!-- ============================================================== -->
      <div class="row mb-4 align-items-center">
        <div class="col-12 col-md-7">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary fs-12 px-2.5 py-1 rounded-pill">
              <i class="ri-user-settings-line align-middle me-1"></i> Manajemen Akses & Personel
            </span>
          </div>
          <h3 class="fs-22 fw-bold text-dark mb-1">Pengguna & Panitia Pelaksana</h3>
          <p class="text-muted fs-13 mb-0">
            Kelola data akun administrator, panitia pelaksana, koordinator divisi, dewan juri, dan wasit turnamen SEMARAK XIV 2026.
          </p>
        </div>
        <div class="col-12 col-md-5 text-md-end mt-3 mt-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
          <Link 
            href="/admin/pengguna/hak-akses" 
            class="btn btn-outline-primary d-inline-flex align-items-center gap-1.5 shadow-sm rounded-3 px-3 py-2 fw-semibold"
          >
            <i class="ri-shield-keyhole-line fs-16 text-warning"></i>
            <span>Hak Akses &amp; Peran</span>
          </Link>
          <button 
            type="button" 
            class="btn btn-outline-success d-inline-flex align-items-center gap-2 shadow-sm rounded-3 px-3 py-2 fw-semibold"
            @click="openSyncModal"
          >
            <i class="ri-refresh-line fs-16"></i>
            <span>Sinkron Guru Sipresensi</span>
          </button>
          <button 
            type="button" 
            class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm rounded-3 px-3 py-2 fw-semibold"
            @click="openCreateModal"
          >
            <i class="ri-user-add-line fs-16"></i>
            <span>Tambah Panitia / Pengguna</span>
          </button>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- STATISTICAL METRIC CARDS                                       -->
      <!-- ============================================================== -->
      <div class="row g-3 mb-4">
        <!-- 1. Total Pengguna -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="text-muted text-uppercase fs-11 fw-bold tracking-wider mb-1">Total Personel</p>
                  <h4 class="fs-22 fw-bold mb-0 text-dark">{{ metrics.total_users || 0 }}</h4>
                  <small class="text-muted fs-11">Terdaftar dalam sistem</small>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                    <i class="ri-team-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Panitia Aktif -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="text-muted text-uppercase fs-11 fw-bold tracking-wider mb-1">Panitia Aktif</p>
                  <h4 class="fs-22 fw-bold mb-0 text-success">{{ metrics.total_active || 0 }}</h4>
                  <small class="text-success fs-11 fw-medium"><i class="ri-check-double-line me-1"></i>Akun Siap Tugas</small>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                    <i class="ri-user-follow-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Administrator & BPH -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="text-muted text-uppercase fs-11 fw-bold tracking-wider mb-1">Admin & BPH</p>
                  <h4 class="fs-22 fw-bold mb-0 text-danger">{{ metrics.total_admin || 0 }}</h4>
                  <small class="text-muted fs-11">Hak akses penuh</small>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-20">
                    <i class="ri-shield-user-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Dewan Juri & Wasit -->
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <p class="text-muted text-uppercase fs-11 fw-bold tracking-wider mb-1">Juri & Wasit</p>
                  <h4 class="fs-22 fw-bold mb-0 text-warning">{{ metrics.total_juri_wasit || 0 }}</h4>
                  <small class="text-muted fs-11">Penilai arena lomba</small>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                    <i class="ri-award-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- FILTER & SEARCH PANEL                                          -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
          <form @submit.prevent="applyFilters" class="row g-2 align-items-center">
            <!-- Search -->
            <div class="col-12 col-md-4">
              <div class="position-relative">
                <input 
                  v-model="searchInput" 
                  type="text" 
                  class="form-control form-control-sm ps-4" 
                  placeholder="Cari nama, email, no WA..."
                />
                <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-14"></i>
              </div>
            </div>

            <!-- Role Filter -->
            <div class="col-6 col-md-2">
              <CustomSelect v-model="roleFilter" size="sm" @change="applyFilters">
                <option value="">Semua Peran</option>
                <option v-for="(cfg, key) in roles" :key="key" :value="key">
                  {{ cfg.label }}
                </option>
              </CustomSelect>
            </div>

            <!-- Division Filter -->
            <div class="col-6 col-md-3">
              <CustomSelect v-model="divisionFilter" size="sm" @change="applyFilters">
                <option value="">Semua Divisi Panitia</option>
                <option v-for="div in divisions" :key="div" :value="div">
                  {{ div }}
                </option>
              </CustomSelect>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
              <CustomSelect v-model="statusFilter" size="sm" @change="applyFilters">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Non-aktif</option>
              </CustomSelect>
            </div>

            <!-- Reset / Action -->
            <div class="col-6 col-md-1 d-flex gap-1">
              <button type="submit" class="btn btn-sm btn-primary w-100" title="Cari">
                <i class="ri-search-line"></i>
              </button>
              <button type="button" class="btn btn-sm btn-light border w-100" title="Reset Filter" @click="resetFilters">
                <i class="ri-refresh-line"></i>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TABLE DATA PENGGUNA                                            -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <i class="ri-list-check-2 fs-18 text-primary"></i>
            <h5 class="fs-15 fw-bold mb-0">Daftar Akun Pengguna & Personel Panitia</h5>
          </div>
          <span class="badge bg-light text-muted border px-2 py-1">
            Total: {{ users.total || 0 }} Personel
          </span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-12 text-muted text-uppercase">
              <tr>
                <th scope="col" style="width: 50px;" class="text-center">#</th>
                <th scope="col">Nama & Akun</th>
                <th scope="col">Peran / Hak Akses</th>
                <th scope="col">Divisi Panitia</th>
                <th scope="col">Penugasan Cabang</th>
                <th scope="col">Kontak WhatsApp</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-end" style="width: 140px;">Aksi</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <tr v-if="!users.data || users.data.length === 0">
                <td colspan="8" class="text-center py-5">
                  <div class="text-muted">
                    <i class="ri-user-unfollow-line fs-36 text-muted mb-2 d-block"></i>
                    <p class="fs-14 fw-medium mb-1">Tidak ada data pengguna atau panitia yang cocok.</p>
                    <small>Coba sesuaikan kata kunci pencarian atau filter yang dipilih.</small>
                  </div>
                </td>
              </tr>

              <tr v-for="(user, idx) in users.data" :key="user.id">
                <td class="text-center text-muted fw-semibold">
                  {{ (users.current_page - 1) * users.per_page + idx + 1 }}
                </td>

                <!-- Nama & Akun -->
                <td>
                  <div class="d-flex align-items-center gap-2.5">
                    <div 
                      class="avatar-xs rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-11 flex-shrink-0"
                      :class="user.role === 'super_admin' ? 'bg-danger' : (user.role === 'admin' ? 'bg-primary' : (user.role === 'juri' || user.role === 'wasit' ? 'bg-warning' : 'bg-info'))"
                      style="width: 34px; height: 34px;"
                    >
                      {{ getInitials(user.name) }}
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        <span class="fw-semibold text-dark">{{ user.name }}</span>
                        <span v-if="user.id === currentUserId" class="badge bg-success-subtle text-success fs-10 px-1.5 py-0.5">
                          Anda
                        </span>
                      </div>
                      <div class="d-flex align-items-center gap-1.5 flex-wrap mt-0.5">
                        <small class="text-muted font-monospace fs-11">{{ user.email }}</small>
                        <span v-if="user.nip" class="badge bg-secondary-subtle text-secondary font-monospace fs-10 px-1.5 py-0.5" title="NIP Guru Sipresensi">
                          <i class="ri-id-card-line me-0.5"></i>{{ user.nip }}
                        </span>
                        <span v-if="user.must_change_password || !user.has_password" class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10 px-1.5 py-0.5" title="Belum memiliki kata sandi mandiri / Wajib buat sandi baru saat login">
                          <i class="ri-key-line me-0.5"></i>Wajib Sandi
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Peran / Role -->
                <td>
                  <span 
                    class="badge px-2 py-1 fs-11 border"
                    :class="{
                      'bg-danger-subtle text-danger border-danger-subtle': user.role === 'super_admin',
                      'bg-primary-subtle text-primary border-primary-subtle': user.role === 'admin',
                      'bg-info-subtle text-info border-info-subtle': user.role === 'panitia',
                      'bg-secondary-subtle text-secondary border-secondary-subtle': user.role === 'kesekretariatan',
                      'bg-success-subtle text-success border-success-subtle': user.role === 'bendahara',
                      'bg-warning-subtle text-warning border-warning-subtle': user.role === 'juri',
                      'bg-dark-subtle text-dark border-dark-subtle': user.role === 'wasit',
                    }"
                  >
                    <i 
                      class="align-middle me-1"
                      :class="{
                        'ri-shield-star-line': user.role === 'super_admin',
                        'ri-shield-user-line': user.role === 'admin',
                        'ri-team-line': user.role === 'panitia',
                        'ri-file-user-line': user.role === 'kesekretariatan',
                        'ri-money-dollar-circle-line': user.role === 'bendahara',
                        'ri-award-line': user.role === 'juri',
                        'ri-flag-line': user.role === 'wasit',
                      }"
                    ></i>
                    {{ roles[user.role]?.label || user.role }}
                  </span>
                </td>

                <!-- Divisi Panitia -->
                <td>
                  <span v-if="user.division" class="fw-medium text-dark">
                    {{ user.division }}
                  </span>
                  <span v-else class="text-muted fs-12">-</span>
                </td>

                <!-- Penugasan Lomba -->
                <td>
                  <span v-if="user.competition" class="badge bg-light text-dark border border-secondary-subtle fs-11">
                    <i class="ri-medal-fill text-warning me-1"></i>{{ user.competition.title }}
                  </span>
                  <span v-else class="text-muted fs-12">Umum / Semua Cabang</span>
                </td>

                <!-- Kontak WA -->
                <td>
                  <div v-if="user.phone">
                    <a 
                      :href="getWaLink(user.phone)" 
                      target="_blank" 
                      class="btn btn-sm btn-outline-success border-0 py-0.5 px-2 d-inline-flex align-items-center gap-1 rounded-pill fs-11"
                      title="Kirim pesan WhatsApp"
                    >
                      <i class="ri-whatsapp-line fs-13"></i>
                      <span>{{ user.phone }}</span>
                    </a>
                  </div>
                  <span v-else class="text-muted fs-12">-</span>
                </td>

                <!-- Status Aktif / Non-aktif -->
                <td class="text-center">
                  <button 
                    type="button" 
                    class="btn btn-sm rounded-pill px-2.5 py-0.5 fs-11 border d-inline-flex align-items-center gap-1"
                    :class="user.is_active ? 'btn-success-subtle text-success border-success-subtle' : 'btn-danger-subtle text-danger border-danger-subtle'"
                    :disabled="user.id === currentUserId"
                    @click="toggleStatus(user)"
                    :title="user.id === currentUserId ? 'Tidak dapat mengubah status akun sendiri' : 'Klik untuk mengubah status'"
                  >
                    <i :class="user.is_active ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"></i>
                    <span>{{ user.is_active ? 'Aktif' : 'Non-aktif' }}</span>
                  </button>
                </td>

                <!-- Aksi -->
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button 
                      type="button" 
                      class="btn btn-sm btn-light border text-primary" 
                      @click="openEditModal(user)"
                      title="Edit Pengguna"
                    >
                      <i class="ri-edit-2-line"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-sm btn-light border text-danger" 
                      :disabled="user.id === currentUserId"
                      @click="confirmDelete(user)"
                      :title="user.id === currentUserId ? 'Tidak dapat menghapus akun sendiri' : 'Hapus Pengguna'"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="users.links && users.links.length > 3" class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between">
          <small class="text-muted fs-12">
            Menampilkan {{ users.from || 0 }} - {{ users.to || 0 }} dari {{ users.total }} pengguna
          </small>
          <div class="d-flex gap-1">
            <template v-for="(link, i) in users.links" :key="i">
              <Link 
                v-if="link.url" 
                :href="link.url" 
                class="btn btn-sm rounded-2 px-2.5 py-1 fs-12"
                :class="link.active ? 'btn-primary text-white' : 'btn-light border text-dark'"
                v-html="link.label"
              />
              <span 
                v-else 
                class="btn btn-sm btn-light border disabled text-muted px-2.5 py-1 fs-12"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL TAMBAH & EDIT PENGGUNA                                   -->
    <!-- ============================================================== -->
    <div 
      v-if="isModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0, 0, 0, 0.55); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header bg-light border-bottom py-3">
            <div class="d-flex align-items-center gap-2">
              <span class="avatar-xs rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                <i :class="isEditing ? 'ri-edit-2-line' : 'ri-user-add-line'"></i>
              </span>
              <h5 class="modal-title fs-16 fw-bold mb-0">
                {{ isEditing ? 'Edit Data Pengguna / Panitia' : 'Tambah Pengguna / Panitia Baru' }}
              </h5>
            </div>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>

          <form @submit.prevent="submitForm">
            <div class="modal-body p-4">
              <div class="row g-3">
                <!-- Nama Lengkap -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Nama Lengkap <span class="text-danger">*</span>
                  </label>
                  <input 
                    v-model="form.name" 
                    type="text" 
                    class="form-control" 
                    :class="{ 'is-invalid': formErrors.name }"
                    placeholder="Contoh: Ust. Ahmad Fauzi, S.Pd."
                    required
                  />
                  <div v-if="formErrors.name" class="invalid-feedback fs-11">
                    {{ formErrors.name }}
                  </div>
                </div>

                <!-- NIP Guru / Pegawai -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    NIP Guru / Pegawai
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted fs-12">
                      <i class="ri-id-card-line text-primary me-1"></i>
                    </span>
                    <input 
                      v-model="form.nip" 
                      type="text" 
                      class="form-control" 
                      :class="{ 'is-invalid': formErrors.nip }"
                      placeholder="Contoh: 198001012005011001 atau 1001"
                    />
                  </div>
                  <small class="text-muted fs-11">Username NIP untuk verifikasi login SSO Sipresensi.</small>
                </div>

                <!-- Email -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Alamat Email (Untuk Login) <span class="text-danger">*</span>
                  </label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    class="form-control" 
                    :class="{ 'is-invalid': formErrors.email }"
                    placeholder="contoh: panitia@maswahas.sch.id"
                    required
                  />
                  <div v-if="formErrors.email" class="invalid-feedback fs-11">
                    {{ formErrors.email }}
                  </div>
                </div>

                <!-- Password -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Kata Sandi <span v-if="!isEditing" class="text-danger">*</span>
                    <span v-else class="text-muted fw-normal fs-11 ms-1">(Kosongkan jika tidak diubah)</span>
                  </label>
                  <div class="position-relative">
                    <input 
                      v-model="form.password" 
                      :type="showPassword ? 'text' : 'password'" 
                      class="form-control pe-5" 
                      :class="{ 'is-invalid': formErrors.password }"
                      :placeholder="isEditing ? 'Masukkan sandi baru jika ingin ubah' : 'Minimal 6 karakter'"
                      :required="!isEditing"
                    />
                    <button 
                      type="button" 
                      class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted text-decoration-none me-1"
                      @click="showPassword = !showPassword"
                    >
                      <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                  </div>
                  <div v-if="formErrors.password" class="text-danger fs-11 mt-1">
                    {{ formErrors.password }}
                  </div>
                </div>

                <!-- No WhatsApp -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Nomor WhatsApp / HP
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted fs-12">
                      <i class="ri-whatsapp-line text-success me-1"></i> +62
                    </span>
                    <input 
                      v-model="form.phone" 
                      type="text" 
                      class="form-control" 
                      :class="{ 'is-invalid': formErrors.phone }"
                      placeholder="81234567890"
                    />
                  </div>
                  <small class="text-muted fs-11">Untuk koordinasi cepat dan notifikasi panitia.</small>
                </div>

                <!-- Peran / Hak Akses -->
                <!-- Role / Hak Akses -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Peran / Hak Akses (Role) <span class="text-danger">*</span>
                  </label>
                  <CustomSelect 
                    v-model="form.role" 
                    size="md"
                    placeholder="Pilih Peran..."
                  >
                    <option v-for="(cfg, key) in roles" :key="key" :value="key">
                      {{ cfg.label }} — {{ cfg.description }}
                    </option>
                  </CustomSelect>
                  <div v-if="formErrors.role" class="invalid-feedback d-block fs-11">
                    {{ formErrors.role }}
                  </div>
                </div>

                <!-- Divisi Panitia -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Divisi / Bidang Tugas
                  </label>
                  <CustomSelect v-model="form.division" size="md" placeholder="Pilih Divisi...">
                    <option value="">Pilih Divisi...</option>
                    <option v-for="div in divisions" :key="div" :value="div">
                      {{ div }}
                    </option>
                  </CustomSelect>
                </div>

                <!-- Penugasan Lomba Khusus (Opsional) -->
                <div class="col-12 col-md-8">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Penugasan Cabang Lomba Khusus <span class="text-muted fw-normal">(Khusus Juri / Wasit / PJ Lomba)</span>
                  </label>
                  <CustomSelect v-model="form.competition_id" size="md" placeholder="Tidak ada / Penugasan Umum Seluruh Lomba">
                    <option value="">Tidak ada / Penugasan Umum Seluruh Lomba</option>
                    <option v-for="comp in competitions" :key="comp.id" :value="comp.id">
                      {{ comp.title }} ({{ comp.category_label || comp.category }})
                    </option>
                  </CustomSelect>
                  <small class="text-muted fs-11">Jika diisi, personel ini diprioritaskan untuk mengelola penilaian/pertandingan cabang tersebut.</small>
                </div>

                <!-- Status Aktif Toggle -->
                <div class="col-12 col-md-4 d-flex align-items-center pt-md-4">
                  <div class="form-check form-switch form-switch-md">
                    <input 
                      v-model="form.is_active" 
                      class="form-check-input" 
                      type="checkbox" 
                      id="isActiveSwitch"
                    />
                    <label class="form-check-label fw-semibold fs-13 ms-2" for="isActiveSwitch">
                      Status Akun Aktif
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-top py-2.5 px-4 d-flex justify-content-between">
              <button type="button" class="btn btn-sm btn-light border" @click="closeModal">
                Batal
              </button>
              <button 
                type="submit" 
                class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3"
                :disabled="isSubmitting"
              >
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm"></span>
                <i v-else class="ri-save-line"></i>
                <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL KONFIRMASI HAPUS                                         -->
    <!-- ============================================================== -->
    <div 
      v-if="deleteModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0, 0, 0, 0.55); z-index: 1060;"
    >
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-body p-4 text-center">
            <div class="avatar-md bg-danger-subtle text-danger rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
              <i class="ri-delete-bin-line fs-28"></i>
            </div>
            <h5 class="fs-16 fw-bold mb-1">Hapus Pengguna?</h5>
            <p class="text-muted fs-12 mb-3">
              Yakin ingin menghapus akun <strong>{{ userToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-sm btn-light border px-3" @click="deleteModalOpen = false">
                Batal
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-danger px-3 d-inline-flex align-items-center gap-1"
                :disabled="isDeleting"
                @click="executeDelete"
              >
                <span v-if="isDeleting" class="spinner-border spinner-border-sm"></span>
                <span>Ya, Hapus</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- ============================================================== -->
    <!-- MODAL SINKRONISASI DATA GURU SIPRESENSI                        -->
    <!-- ============================================================== -->
    <div 
      v-if="isSyncModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0, 0, 0, 0.6); z-index: 1055;"
    >
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <!-- Modal Header -->
          <div class="modal-header bg-light border-bottom py-3">
            <div class="d-flex align-items-center gap-2.5">
              <span class="avatar-xs rounded-circle bg-success text-white d-flex align-items-center justify-content-center">
                <i class="ri-refresh-line fs-16"></i>
              </span>
              <div>
                <h5 class="modal-title fs-16 fw-bold mb-0 text-dark">
                  Sinkronisasi Panitia dari Data Guru Sipresensi
                </h5>
                <small class="text-muted fs-12">
                  Layanan Integrasi Sipresensi (REST API & Isolated Service)
                </small>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span 
                v-if="sipresensiData.connected" 
                class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fs-11"
              >
                <i class="ri-checkbox-circle-fill me-1"></i>Layanan Sipresensi Terhubung
              </span>
              <span 
                v-else-if="!isPreviewLoading" 
                class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fs-11"
              >
                <i class="ri-close-circle-fill me-1"></i>Layanan Terputus
              </span>
              <button type="button" class="btn-close ms-2" @click="closeSyncModal"></button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-4">
            <!-- 1. LOADING STATE -->
            <div v-if="isPreviewLoading" class="text-center py-5">
              <div class="spinner-border text-success mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>
              <h6 class="fs-14 fw-bold text-dark mb-1">Menghubungkan ke Layanan Sipresensi...</h6>
              <p class="text-muted fs-12 mb-0">Sedang menarik data guru dan memetakan akun panitia Semarak.</p>
            </div>

            <!-- 2. CONNECTION ERROR -->
            <div v-else-if="syncError && !sipresensiData.connected" class="alert alert-danger border-danger-subtle rounded-3 p-3">
              <div class="d-flex gap-3 align-items-start">
                <i class="ri-error-warning-fill fs-24 text-danger flex-shrink-0"></i>
                <div class="flex-grow-1">
                  <h6 class="fs-14 fw-bold text-danger mb-1">Layanan Sipresensi Tidak Dapat Dihubungi</h6>
                  <p class="fs-12 mb-2">{{ syncError }}</p>
                  <small class="d-block text-muted mb-3">
                    Pastikan REST API Sipresensi sedang aktif atau direktori instalasi Sipresensi lokal tersedia di sistem.
                  </small>
                  <button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5" @click="openSyncModal">
                    <i class="ri-restart-line"></i>
                    <span>Coba Hubungkan Ulang</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- 3. HASIL SINKRONISASI (SUMMARY) -->
            <div v-else-if="syncSummary" class="text-center py-4">
              <div class="avatar-lg bg-success-subtle text-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                <i class="ri-checkbox-circle-line fs-36"></i>
              </div>
              <h5 class="fs-18 fw-bold text-dark mb-1">Sinkronisasi Selesai!</h5>
              <p class="text-muted fs-13 mb-4">{{ syncSummary.message }}</p>

              <div class="row g-3 justify-content-center mb-4">
                <div class="col-4 col-md-3">
                  <div class="p-3 bg-success-subtle border border-success-subtle rounded-3">
                    <h3 class="fs-22 fw-bold text-success mb-0">+{{ syncSummary.created_count }}</h3>
                    <small class="text-success fw-medium fs-11">Akun Baru Dibuat</small>
                  </div>
                </div>
                <div class="col-4 col-md-3">
                  <div class="p-3 bg-info-subtle border border-info-subtle rounded-3">
                    <h3 class="fs-22 fw-bold text-info mb-0">{{ syncSummary.updated_count }}</h3>
                    <small class="text-info fw-medium fs-11">Akun Diperbarui</small>
                  </div>
                </div>
                <div class="col-4 col-md-3">
                  <div class="p-3 bg-secondary-subtle border border-secondary-subtle rounded-3">
                    <h3 class="fs-22 fw-bold text-secondary mb-0">{{ syncSummary.skipped_count }}</h3>
                    <small class="text-secondary fw-medium fs-11">Akun Dilewati</small>
                  </div>
                </div>
              </div>

              <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-primary px-4 fw-semibold" @click="closeSyncModal">
                  <i class="ri-check-line me-1"></i>Selesai & Lihat Pengguna
                </button>
                <button type="button" class="btn btn-outline-secondary px-3" @click="openSyncModal">
                  <i class="ri-refresh-line me-1"></i>Sinkronkan Lagi
                </button>
              </div>
            </div>

            <!-- 4. DAFTAR PREVIEW GURU & PENGATURAN SINKRONISASI -->
            <div v-else>
              <!-- Alert Informasi Sumber Data -->
              <div class="alert alert-info border-info-subtle rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-information-line fs-18 text-info"></i>
                  <span class="fs-12 text-dark">
                    Ditemukan <strong>{{ sipresensiData.total }}</strong> data guru di Sipresensi. Pilih guru yang ingin didaftarkan sebagai Panitia Semarak XIV.
                  </span>
                </div>
                <div class="d-flex gap-2">
                  <span class="badge bg-success-subtle text-success border border-success-subtle fs-11">
                    {{ newTeachersCount }} Akun Baru
                  </span>
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11">
                    {{ existingTeachersCount }} Sudah Terdaftar
                  </span>
                </div>
              </div>

              <!-- Bar Pengaturan Parameter Akun Baru -->
              <div class="card border border-light-subtle bg-light rounded-3 p-3 mb-3">
                <h6 class="fs-12 fw-bold text-uppercase text-muted tracking-wider mb-2">
                  <i class="ri-settings-4-line me-1"></i>Konfigurasi Akun Panitia Yang Diimpor
                </h6>
                <div class="row g-2 align-items-center">
                  <!-- Peran Default -->
                  <div class="col-12 col-md-3">
                    <label class="form-label fs-11 fw-semibold text-dark mb-1">Peran (Role) Default</label>
                    <CustomSelect v-model="syncSettings.default_role" size="sm">
                      <option v-for="(cfg, key) in roles" :key="key" :value="key">
                        {{ cfg.label }}
                      </option>
                    </CustomSelect>
                  </div>

                  <!-- Divisi Default -->
                  <div class="col-12 col-md-3">
                    <label class="form-label fs-11 fw-semibold text-dark mb-1">Divisi Default</label>
                    <CustomSelect v-model="syncSettings.default_division" size="sm">
                      <option value="">Otomatis Sesuai Jabatan Sipresensi</option>
                      <option v-for="div in divisions" :key="div" :value="div">
                        {{ div }}
                      </option>
                    </CustomSelect>
                  </div>

                  <!-- Sandi Awal Default -->
                  <div class="col-12 col-md-3">
                    <label class="form-label fs-11 fw-semibold text-dark mb-1">Kebijakan Sandi Awal</label>
                    <div class="badge bg-warning-subtle text-warning border border-warning-subtle d-block text-start p-1.5 fs-11 text-truncate" title="Password awal dikosongkan. Panitia login pertama via SSO Sipresensi/OTP WA lalu wajib buat password.">
                      <i class="ri-shield-keyhole-line me-1"></i>Dikosongkan (Wajib Buat Sandi)
                    </div>
                  </div>

                  <!-- Checkbox Update Existing -->
                  <div class="col-12 col-md-3 d-flex align-items-center pt-md-3">
                    <div class="form-check form-switch mb-0">
                      <input 
                        id="updateExistingSwitch"
                        v-model="syncSettings.update_existing" 
                        type="checkbox" 
                        class="form-check-input"
                      />
                      <label for="updateExistingSwitch" class="form-check-label fs-11 text-dark fw-medium ms-1">
                        Perbarui data jika akun sudah ada
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Bar & Filter Cepat -->
              <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="selectAllTeachers">
                    <i class="ri-check-double-line me-1"></i>Pilih Semua ({{ filteredTeachers.length }})
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-success" @click="selectOnlyNew">
                    <i class="ri-user-add-line me-1"></i>Pilih Hanya Akun Baru ({{ newTeachersCount }})
                  </button>
                  <button type="button" class="btn btn-sm btn-light border" @click="clearTeacherSelection">
                    Batal Pilih
                  </button>
                </div>
                <div class="position-relative" style="min-width: 240px;">
                  <input 
                    v-model="syncSearch" 
                    type="text" 
                    class="form-control form-control-sm ps-4" 
                    placeholder="Cari guru, NIP, jabatan..."
                  />
                  <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-13"></i>
                </div>
              </div>

              <!-- Tabel Preview Data Guru -->
              <div class="table-responsive border rounded-3 mb-2" style="max-height: 380px;">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light fs-11 text-muted text-uppercase sticky-top">
                    <tr>
                      <th scope="col" style="width: 40px;" class="text-center">
                        <input 
                          type="checkbox" 
                          class="form-check-input" 
                          :checked="isAllSelected" 
                          @change="toggleSelectAll"
                        />
                      </th>
                      <th scope="col">Nama Guru & Identitas</th>
                      <th scope="col">Kontak (Email / WhatsApp)</th>
                      <th scope="col">Jabatan Sipresensi</th>
                      <th scope="col">Rekomendasi Divisi</th>
                      <th scope="col" class="text-center">Status di Semarak</th>
                    </tr>
                  </thead>
                  <tbody class="fs-12">
                    <tr v-if="filteredTeachers.length === 0">
                      <td colspan="6" class="text-center py-4 text-muted">
                        Tidak ada guru yang cocok dengan kata kunci pencarian.
                      </td>
                    </tr>
                    <tr 
                      v-for="t in filteredTeachers" 
                      :key="t.sipresensi_id"
                      :class="{ 'table-active': selectedTeacherIds.includes(t.sipresensi_id) }"
                    >
                      <!-- Checkbox -->
                      <td class="text-center">
                        <input 
                          type="checkbox" 
                          class="form-check-input" 
                          :value="t.sipresensi_id"
                          v-model="selectedTeacherIds"
                        />
                      </td>

                      <!-- Nama & Identitas -->
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <span 
                            class="avatar-xs rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-10 flex-shrink-0"
                            :class="t.gender === 'P' ? 'bg-danger' : 'bg-primary'"
                          >
                            {{ getInitials(t.name) }}
                          </span>
                          <div>
                            <span class="fw-semibold text-dark d-block">{{ t.name }}</span>
                            <div class="d-flex align-items-center gap-1.5 fs-11 text-muted">
                              <span>NIP/ID: {{ t.username || '-' }}</span>
                              <span v-if="t.status_guru" class="badge bg-light text-muted border py-0 px-1">
                                {{ t.status_guru }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </td>

                      <!-- Kontak -->
                      <td>
                        <span class="font-monospace fs-11 text-dark d-block">{{ t.email }}</span>
                        <small v-if="t.phone" class="text-success fs-11">
                          <i class="ri-whatsapp-line me-1"></i>{{ t.phone }}
                        </small>
                        <small v-else class="text-muted fs-11">Tanpa No. WhatsApp</small>
                      </td>

                      <!-- Jabatan Sipresensi -->
                      <td>
                        <span class="badge bg-light text-dark border fs-11">
                          {{ t.sipresensi_position || 'Guru' }}
                        </span>
                      </td>

                      <!-- Rekomendasi Divisi -->
                      <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-11">
                          <i class="ri-team-line me-1"></i>{{ t.suggested_division }}
                        </span>
                      </td>

                      <!-- Status di Semarak -->
                      <td class="text-center">
                        <span 
                          v-if="t.already_exists" 
                          class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11 px-2 py-1"
                          :title="`Sudah terdaftar sebagai ${t.existing_user?.name} (${t.existing_user?.role})`"
                        >
                          <i class="ri-user-follow-line me-1"></i>Sudah Terdaftar
                        </span>
                        <span 
                          v-else 
                          class="badge bg-success-subtle text-success border border-success-subtle fs-11 px-2 py-1"
                        >
                          <i class="ri-user-add-line me-1"></i>Akun Baru
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div v-if="!syncSummary && sipresensiData.connected" class="modal-footer bg-light border-top py-2.5 d-flex align-items-center justify-content-between">
            <div>
              <span class="fs-12 text-muted">
                Dipilih: <strong class="text-dark">{{ selectedTeacherIds.length }}</strong> dari {{ filteredTeachers.length }} guru
              </span>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-light border px-3" @click="closeSyncModal">
                Batal
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-success px-3 d-inline-flex align-items-center gap-1.5 fw-semibold"
                :disabled="selectedTeacherIds.length === 0 || isSyncing"
                @click="executeSync"
              >
                <span v-if="isSyncing" class="spinner-border spinner-border-sm"></span>
                <i v-else class="ri-refresh-line"></i>
                <span>Sinkronkan {{ selectedTeacherIds.length }} Guru Sekarang</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>
