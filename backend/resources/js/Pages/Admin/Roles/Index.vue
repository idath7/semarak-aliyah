<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  roles: {
    type: Array,
    required: true,
  },
  permissionsGrouped: {
    type: Object,
    required: true,
  },
  allPermissions: {
    type: Array,
    required: true,
  },
  usersList: {
    type: Array,
    default: () => [],
  },
  metrics: {
    type: Object,
    default: () => ({}),
  },
});

const { success, error, info } = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// -----------------------------------------------------------------------------
// TABS NAVIGATION STATE
// -----------------------------------------------------------------------------
const activeTab = ref('matrix'); // 'matrix' | 'roles' | 'users'

// -----------------------------------------------------------------------------
// ROLE MATRIX STATE
// -----------------------------------------------------------------------------
// Peran aktif yang sedang diatur izinnya
const selectedRoleId = ref(props.roles[0]?.id || 1);

const selectedRole = computed(() => {
  return props.roles.find(r => r.id === selectedRoleId.value) || props.roles[0];
});

// Peta lokal permission IDs untuk setiap role (untuk manipulasi cepat di UI)
const matrixState = ref({});

function initMatrixState() {
  const state = {};
  props.roles.forEach(role => {
    state[role.id] = [...role.permission_ids];
  });
  matrixState.value = state;
}

initMatrixState();

const currentRolePermIds = computed(() => {
  return matrixState.value[selectedRoleId.value] || [];
});

const isRoleSaving = ref(false);

function isPermChecked(roleId, permId) {
  const role = props.roles.find(r => r.id === roleId);
  if (role && role.name === 'super_admin') return true;
  return (matrixState.value[roleId] || []).includes(permId);
}

function togglePermission(permId) {
  if (selectedRole.value?.name === 'super_admin') return;

  const current = matrixState.value[selectedRoleId.value] || [];
  const idx = current.indexOf(permId);
  if (idx > -1) {
    current.splice(idx, 1);
  } else {
    current.push(permId);
  }
  matrixState.value[selectedRoleId.value] = [...current];
}

function toggleGroupPermissions(groupPerms, grantAll = true) {
  if (selectedRole.value?.name === 'super_admin') return;

  const current = new Set(matrixState.value[selectedRoleId.value] || []);
  groupPerms.forEach(p => {
    if (grantAll) {
      current.add(p.id);
    } else {
      current.delete(p.id);
    }
  });
  matrixState.value[selectedRoleId.value] = Array.from(current);
}

function grantAllPermissions() {
  if (selectedRole.value?.name === 'super_admin') return;
  matrixState.value[selectedRoleId.value] = props.allPermissions.map(p => p.id);
  info(`Seluruh ${props.allPermissions.length} hak akses dipilih untuk peran ${selectedRole.value.label}.`);
}

function revokeAllPermissions() {
  if (selectedRole.value?.name === 'super_admin') return;
  matrixState.value[selectedRoleId.value] = [];
  info(`Seluruh hak akses dicabut untuk peran ${selectedRole.value.label}.`);
}

function saveRolePermissions() {
  isRoleSaving.value = true;
  router.post('/admin/pengguna/hak-akses/matrix', {
    role_id: selectedRoleId.value,
    permission_ids: matrixState.value[selectedRoleId.value] || [],
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isRoleSaving.value = false;
      success(`Matriks hak akses untuk peran '${selectedRole.value.label}' berhasil disimpan!`, 'Tersimpan');
    },
    onError: () => {
      isRoleSaving.value = false;
      error('Gagal menyimpan matriks perizinan.');
    }
  });
}

async function handleResetDefaults() {
  const confirmed = await confirmDialog({
    title: 'Kembalikan Hak Akses ke Standar Awal?',
    message: 'Tindakan ini akan mengembalikan seluruh izin peran bawaan (Super Admin, Admin, Kesekretariatan, Bendahara, Panitia, Juri, Wasit) ke konfigurasi baku sistem.',
    type: 'warning',
    confirmText: 'Ya, Reset ke Standar',
  });

  if (!confirmed) return;

  router.post('/admin/pengguna/hak-akses/reset-defaults', {}, {
    preserveScroll: true,
    onSuccess: () => {
      initMatrixState();
      success('Seluruh hak akses peran bawaan berhasil dikembalikan ke standar awal sistem.', 'Berhasil Reset');
    },
    onError: () => {
      error('Gagal mengembalikan pengaturan ke default.');
    }
  });
}

// -----------------------------------------------------------------------------
// ROLE MANAGEMENT MODAL STATE (CREATE & EDIT)
// -----------------------------------------------------------------------------
const isRoleModalOpen = ref(false);
const isEditingRole = ref(false);
const isSubmittingRole = ref(false);

const roleForm = ref({
  id: null,
  name: '',
  label: '',
  badge_color: 'primary',
  description: '',
  permission_ids: [],
});

const badgeColorOptions = [
  { value: 'primary', label: 'Primary (Biru Velzon)' },
  { value: 'secondary', label: 'Secondary (Abu-abu Slate)' },
  { value: 'success', label: 'Success (Hijau Emerald)' },
  { value: 'danger', label: 'Danger (Merah Ruby)' },
  { value: 'warning', label: 'Warning (Kuning Amber)' },
  { value: 'info', label: 'Info (Biru Muda Sky)' },
  { value: 'dark', label: 'Dark (Hitam Midnight)' },
];

function openCreateRoleModal() {
  isEditingRole.value = false;
  roleForm.value = {
    id: null,
    name: '',
    label: '',
    badge_color: 'info',
    description: '',
    permission_ids: [],
  };
  isRoleModalOpen.value = true;
}

function openEditRoleModal(role) {
  isEditingRole.value = true;
  roleForm.value = {
    id: role.id,
    name: role.name,
    label: role.label,
    badge_color: role.badge_color || 'primary',
    description: role.description || '',
    permission_ids: [...role.permission_ids],
  };
  isRoleModalOpen.value = true;
}

function submitRoleForm() {
  isSubmittingRole.value = true;

  if (isEditingRole.value) {
    router.put(`/admin/pengguna/hak-akses/role/${roleForm.value.id}`, {
      label: roleForm.value.label,
      badge_color: roleForm.value.badge_color,
      description: roleForm.value.description,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmittingRole.value = false;
        isRoleModalOpen.value = false;
        success(`Informasi peran '${roleForm.value.label}' berhasil diperbarui!`, 'Berhasil');
      },
      onError: () => {
        isSubmittingRole.value = false;
        error('Gagal memperbarui peran. Silakan periksa kembali formulir.');
      }
    });
  } else {
    router.post('/admin/pengguna/hak-akses/role', roleForm.value, {
      preserveScroll: true,
      onSuccess: () => {
        isSubmittingRole.value = false;
        isRoleModalOpen.value = false;
        initMatrixState();
        success(`Peran baru '${roleForm.value.label}' berhasil ditambahkan ke sistem!`, 'Peran Ditambahkan');
      },
      onError: () => {
        isSubmittingRole.value = false;
        error('Gagal menambahkan peran baru. Periksa kembali nama slug atau kolom wajib.');
      }
    });
  }
}

async function handleDeleteRole(role) {
  if (role.is_system) {
    error(`Peran '${role.label}' adalah peran bawaan sistem dan tidak dapat dihapus.`);
    return;
  }

  const confirmed = await confirmDialog({
    title: `Hapus Peran '${role.label}'?`,
    message: `Apakah Anda yakin ingin menghapus peran '${role.label}'? Tindakan ini tidak dapat dibatalkan.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Peran',
  });

  if (!confirmed) return;

  router.delete(`/admin/pengguna/hak-akses/role/${role.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (selectedRoleId.value === role.id) {
        selectedRoleId.value = props.roles[0]?.id || 1;
      }
      initMatrixState();
      success(`Peran '${role.label}' berhasil dihapus.`, 'Terhapus');
    },
    onError: () => {
      error('Gagal menghapus peran.');
    }
  });
}

// -----------------------------------------------------------------------------
// USER OVERRIDE PERMISSIONS STATE
// -----------------------------------------------------------------------------
const userSearch = ref('');
const isUserModalOpen = ref(false);
const selectedUser = ref(null);
const userOverrides = ref({}); // { 'perm.name': true | false }
const isSavingUserOverride = ref(false);

const filteredUsers = computed(() => {
  if (!userSearch.value) return props.usersList;
  const q = userSearch.value.toLowerCase();
  return props.usersList.filter(u => 
    (u.name && u.name.toLowerCase().includes(q)) ||
    (u.email && u.email.toLowerCase().includes(q)) ||
    (u.nip && u.nip.toLowerCase().includes(q)) ||
    (u.role && u.role.toLowerCase().includes(q))
  );
});

function openUserOverrideModal(user) {
  selectedUser.value = user;
  userOverrides.value = { ...(user.custom_permissions || {}) };
  isUserModalOpen.value = true;
}

function getUserPermState(permName) {
  if (!selectedUser.value) return 'inherit';
  if (userOverrides.value[permName] === true) return 'granted';
  if (userOverrides.value[permName] === false) return 'denied';
  return 'inherit';
}

function setUserPermState(permName, state) {
  if (state === 'granted') {
    userOverrides.value[permName] = true;
  } else if (state === 'denied') {
    userOverrides.value[permName] = false;
  } else {
    delete userOverrides.value[permName];
  }
}

function resetUserOverrides() {
  userOverrides.value = {};
  info('Seluruh override hak akses dihapus. Pengguna akan kembali mengikuti izin perannya.');
}

function saveUserOverrides() {
  if (!selectedUser.value) return;
  isSavingUserOverride.value = true;

  router.post(`/admin/pengguna/hak-akses/user/${selectedUser.value.id}/custom-permissions`, {
    custom_permissions: userOverrides.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingUserOverride.value = false;
      isUserModalOpen.value = false;
      success(`Hak akses khusus untuk panitia '${selectedUser.value.name}' berhasil disimpan!`, 'Tersimpan');
    },
    onError: () => {
      isSavingUserOverride.value = false;
      error('Gagal menyimpan hak akses khusus pengguna.');
    }
  });
}
</script>

<template>
  <Head title="Manajemen Hak Akses & Peran Panitia | Semarak Aliyah" />

  <VelzonLayout>
    <div class="container-fluid px-2 px-md-3 py-3">

      <!-- Breadcrumbs & Page Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 bg-transparent p-0">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill fs-11 fw-semibold">
                  <i class="ri-shield-keyhole-line me-1"></i>Keamanan &amp; Otorisasi Panitia
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fs-11 fw-semibold">
                  <i class="ri-check-double-line me-1"></i>Role-Based Access Control (RBAC)
                </span>
              </div>
              <h4 class="mb-1 fw-bold text-dark-emphasis d-flex align-items-center gap-2 fs-20">
                <i class="ri-shield-user-line text-primary fs-22"></i>
                <span>Manajemen Hak Akses &amp; Peran Panitia</span>
              </h4>
              <p class="text-muted fs-12 mb-0">
                Konfigurasi matriks perizinan modul per peran panitia, tambah peran khusus baru, dan kelola hak akses override per anggota.
              </p>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <Link 
                href="/admin/pengguna" 
                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1.5 shadow-sm px-3"
                title="Buka daftar seluruh akun pengguna"
              >
                <i class="ri-team-line"></i>
                <span>Daftar Panitia</span>
              </Link>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1.5 shadow-sm px-3"
                @click="handleResetDefaults"
                title="Kembalikan seluruh izin peran sistem ke default baku"
              >
                <i class="ri-restart-line"></i>
                <span>Reset Standar Baku</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-primary d-flex align-items-center gap-1.5 shadow-sm px-3"
                @click="openCreateRoleModal"
              >
                <i class="ri-add-circle-line fs-14"></i>
                <span>Tambah Peran Baru</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Metric Summary Cards -->
      <div class="row g-3 mb-4">
        <!-- 1. Total Peran -->
        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
              <div>
                <p class="text-muted fs-11 fw-semibold text-uppercase mb-1">Total Peran (Roles)</p>
                <h4 class="fs-22 fw-bold text-dark-emphasis mb-0">{{ metrics.total_roles || roles.length }}</h4>
                <div class="fs-11 text-muted mt-1">
                  <span class="text-primary fw-medium">{{ roles.filter(r => r.is_system).length }} Bawaan</span> • 
                  <span class="text-info fw-medium">{{ roles.filter(r => !r.is_system).length }} Kustom</span>
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                  <i class="ri-shield-star-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Total Izin Modul -->
        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
              <div>
                <p class="text-muted fs-11 fw-semibold text-uppercase mb-1">Total Modul Izin</p>
                <h4 class="fs-22 fw-bold text-dark-emphasis mb-0">{{ metrics.total_permissions || allPermissions.length }}</h4>
                <div class="fs-11 text-muted mt-1">
                  <span class="text-success fw-medium">{{ Object.keys(permissionsGrouped).length }} Kategori</span> Modul Sistem
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                  <i class="ri-key-2-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Total Akun Panitia -->
        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
              <div>
                <p class="text-muted fs-11 fw-semibold text-uppercase mb-1">Panitia Aktif</p>
                <h4 class="fs-22 fw-bold text-dark-emphasis mb-0">{{ metrics.total_users || usersList.length }}</h4>
                <div class="fs-11 text-muted mt-1">
                  Terdaftar dalam basis data panitia
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                  <i class="ri-user-settings-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Override Khusus -->
        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
            <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
              <div>
                <p class="text-muted fs-11 fw-semibold text-uppercase mb-1">Izin Khusus (Override)</p>
                <h4 class="fs-22 fw-bold text-dark-emphasis mb-0">{{ metrics.custom_overrides_count || 0 }}</h4>
                <div class="fs-11 text-muted mt-1">
                  Panitia dengan perizinan spesifik
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                  <i class="ri-toggle-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Navigation Tabs -->
      <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header border-bottom bg-transparent p-3">
          <ul class="nav nav-pills custom-role-pills" role="tablist">
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link d-flex align-items-center gap-2 fs-13 fw-semibold px-3.5 py-2"
                :class="{ 'active': activeTab === 'matrix' }"
                @click="activeTab = 'matrix'"
              >
                <i class="ri-table-line fs-15"></i>
                <span>Matriks Hak Akses Peran</span>
              </button>
            </li>
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link d-flex align-items-center gap-2 fs-13 fw-semibold px-3.5 py-2"
                :class="{ 'active': activeTab === 'roles' }"
                @click="activeTab = 'roles'"
              >
                <i class="ri-shield-star-line fs-15"></i>
                <span>Kelola &amp; Daftar Peran ({{ roles.length }})</span>
              </button>
            </li>
            <li class="nav-item">
              <button 
                type="button" 
                class="nav-link d-flex align-items-center gap-2 fs-13 fw-semibold px-3.5 py-2"
                :class="{ 'active': activeTab === 'users' }"
                @click="activeTab = 'users'"
              >
                <i class="ri-user-shared-line fs-15"></i>
                <span>Hak Akses Khusus Anggota Panitia</span>
              </button>
            </li>
          </ul>
        </div>

        <div class="card-body p-3 p-md-4">

          <!-- ============================================================= -->
          <!-- TAB 1: MATRIKS HAK AKSES PERAN                                -->
          <!-- ============================================================= -->
          <div v-if="activeTab === 'matrix'">
            
            <!-- Role Selector Pills Bar -->
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 p-3 rounded-4 bg-light-subtle border">
              <div>
                <span class="fs-11 fw-bold text-muted text-uppercase d-block mb-1.5">Pilih Peran yang Ingin Dikelola:</span>
                <div class="d-flex flex-wrap gap-1.5">
                  <button
                    v-for="role in roles"
                    :key="role.id"
                    type="button"
                    class="btn btn-sm rounded-pill px-3 py-1.5 fs-12 fw-semibold d-inline-flex align-items-center gap-1.5 transition-all"
                    :class="selectedRoleId === role.id ? `btn-${role.badge_color || 'primary'} shadow-sm` : 'btn-outline-secondary'"
                    @click="selectedRoleId = role.id"
                  >
                    <i v-if="selectedRoleId === role.id" class="ri-checkbox-circle-fill"></i>
                    <span>{{ role.label }}</span>
                    <span class="badge bg-white text-dark rounded-pill fs-10 px-1.5 ms-0.5">
                      {{ matrixState[role.id]?.length || 0 }}
                    </span>
                  </button>
                </div>
              </div>

              <!-- Save Matrix Button -->
              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button
                  v-if="selectedRole?.name !== 'super_admin'"
                  type="button"
                  class="btn btn-sm btn-outline-success rounded-pill px-3"
                  @click="grantAllPermissions"
                >
                  <i class="ri-check-line me-1"></i>Pilih Semua
                </button>
                <button
                  v-if="selectedRole?.name !== 'super_admin'"
                  type="button"
                  class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                  @click="revokeAllPermissions"
                >
                  <i class="ri-close-line me-1"></i>Kosongkan
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center gap-1.5"
                  :disabled="isRoleSaving || selectedRole?.name === 'super_admin'"
                  @click="saveRolePermissions"
                >
                  <span v-if="isRoleSaving" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-save-3-line"></i>
                  <span>{{ isRoleSaving ? 'Menyimpan...' : 'Simpan Izin Peran' }}</span>
                </button>
              </div>
            </div>

            <!-- Role Summary Banner -->
            <div 
              class="alert py-3 px-3.5 rounded-4 mb-4 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"
              :class="`alert-${selectedRole?.badge_color || 'primary'}`"
            >
              <div class="d-flex align-items-start gap-3">
                <div class="fs-28 flex-shrink-0 mt-0.5">
                  <i v-if="selectedRole?.name === 'super_admin'" class="ri-shield-flash-fill text-danger"></i>
                  <i v-else-if="selectedRole?.name === 'admin'" class="ri-shield-check-fill text-primary"></i>
                  <i v-else class="ri-shield-keyhole-fill"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 mb-0.5">
                    <h5 class="mb-0 fw-bold fs-16">{{ selectedRole?.label }}</h5>
                    <span class="badge bg-dark text-white rounded-pill fs-10 font-monospace">slug: {{ selectedRole?.name }}</span>
                    <span v-if="selectedRole?.is_system" class="badge bg-secondary-subtle text-secondary rounded-pill fs-10">Bawaan Sistem</span>
                  </div>
                  <p class="mb-0 fs-12 opacity-85">{{ selectedRole?.description || 'Tidak ada keterangan khusus untuk peran ini.' }}</p>
                </div>
              </div>

              <div class="text-md-end flex-shrink-0">
                <div class="fs-12 fw-bold">
                  {{ selectedRole?.name === 'super_admin' ? allPermissions.length : (matrixState[selectedRoleId]?.length || 0) }} / {{ allPermissions.length }} Izin Aktif
                </div>
                <small class="opacity-75 fs-11">{{ selectedRole?.users_count || 0 }} Anggota panitia terdaftar</small>
              </div>
            </div>

            <!-- Super Admin Notice -->
            <div v-if="selectedRole?.name === 'super_admin'" class="alert alert-danger py-2.5 px-3.5 rounded-3 mb-4 fs-12 d-flex align-items-center gap-2">
              <i class="ri-alert-fill fs-16 text-danger"></i>
              <span><strong>Hak Akses Super Admin Bersifat Mutlak (Bypass).</strong> Seluruh izin sistem otomatis aktif dan tidak dapat dinonaktifkan demi integritas kendali penuh sistem.</span>
            </div>

            <!-- Permission Groups Accordions / Cards -->
            <div class="d-flex flex-column gap-3.5">
              <div 
                v-for="(perms, groupName) in permissionsGrouped" 
                :key="groupName"
                class="card border rounded-4 shadow-none permission-group-card"
              >
                <!-- Group Header -->
                <div class="card-header bg-light-subtle py-2.5 px-3.5 d-flex align-items-center justify-content-between border-bottom">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ri-folder-keyhole-line text-primary fs-16"></i>
                    <h6 class="mb-0 fs-13 fw-bold text-dark-emphasis">{{ groupName }}</h6>
                    <span class="badge bg-primary-subtle text-primary rounded-pill fs-11 px-2">
                      {{ perms.filter(p => isPermChecked(selectedRoleId, p.id)).length }} / {{ perms.length }}
                    </span>
                  </div>

                  <!-- Quick Select for this group -->
                  <div v-if="selectedRole?.name !== 'super_admin'" class="d-flex align-items-center gap-1.5">
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0.5 fs-11"
                      @click="toggleGroupPermissions(perms, true)"
                      title="Beri semua izin di modul ini"
                    >
                      Pilih Semua
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0.5 fs-11"
                      @click="toggleGroupPermissions(perms, false)"
                      title="Cabut semua izin di modul ini"
                    >
                      Hapus
                    </button>
                  </div>
                </div>

                <!-- Group Permissions Grid -->
                <div class="card-body p-3">
                  <div class="row g-2.5">
                    <div 
                      v-for="perm in perms" 
                      :key="perm.id"
                      class="col-md-6 col-xl-4"
                    >
                      <div 
                        class="p-2.5 rounded-3 border permission-item-box transition-all cursor-pointer h-100 d-flex align-items-start gap-2.5"
                        :class="{ 
                          'is-active': isPermChecked(selectedRoleId, perm.id),
                          'disabled-super': selectedRole?.name === 'super_admin'
                        }"
                        @click="togglePermission(perm.id)"
                      >
                        <!-- Custom Switch / Checkbox -->
                        <div class="form-check form-switch m-0 pt-0.5">
                          <input 
                            class="form-check-input cursor-pointer" 
                            type="checkbox" 
                            :id="`perm-${selectedRoleId}-${perm.id}`"
                            :checked="isPermChecked(selectedRoleId, perm.id)"
                            :disabled="selectedRole?.name === 'super_admin'"
                            @click.stop="togglePermission(perm.id)"
                          />
                        </div>

                        <!-- Permission Label & Details -->
                        <div class="flex-grow-1">
                          <label 
                            :for="`perm-${selectedRoleId}-${perm.id}`"
                            class="form-check-label d-block fs-12 fw-semibold text-dark-emphasis cursor-pointer mb-0.5"
                          >
                            {{ perm.label }}
                          </label>
                          <div class="font-monospace fs-10 text-muted mb-1">{{ perm.name }}</div>
                          <p class="fs-11 text-muted mb-0 lh-sm">{{ perm.description || 'Tidak ada deskripsi rinci.' }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sticky Bottom Save Bar -->
            <div 
              v-if="selectedRole?.name !== 'super_admin'" 
              class="sticky-bottom mt-4 p-3 bg-card border rounded-4 shadow-lg d-flex align-items-center justify-content-between"
              style="z-index: 100;"
            >
              <div class="fs-12 text-muted d-none d-sm-block">
                Perubahan pada matriks <strong>{{ selectedRole?.label }}</strong> akan langsung berlaku untuk seluruh panitia yang bertugas pada peran ini.
              </div>
              <div class="d-flex align-items-center gap-2 ms-auto">
                <button
                  type="button"
                  class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"
                  :disabled="isRoleSaving"
                  @click="saveRolePermissions"
                >
                  <span v-if="isRoleSaving" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-save-3-fill fs-16"></i>
                  <span>{{ isRoleSaving ? 'Menyimpan Matriks...' : 'Simpan Perubahan Matriks' }}</span>
                </button>
              </div>
            </div>

          </div>

          <!-- ============================================================= -->
          <!-- TAB 2: KELOLA & DAFTAR PERAN (ROLES CARDS)                    -->
          <!-- ============================================================= -->
          <div v-else-if="activeTab === 'roles'">
            
            <div class="d-flex align-items-center justify-content-between mb-3.5">
              <div>
                <h5 class="fs-15 fw-bold text-dark-emphasis mb-0">Daftar Peran &amp; Struktur Tanggung Jawab</h5>
                <p class="fs-12 text-muted mb-0">Daftar seluruh peran panitia yang terdaftar dalam sistem Semarak Aliyah.</p>
              </div>
              <button 
                type="button" 
                class="btn btn-sm btn-primary rounded-pill px-3.5 shadow-sm d-flex align-items-center gap-1.5"
                @click="openCreateRoleModal"
              >
                <i class="ri-add-line"></i>
                <span>Tambah Peran</span>
              </button>
            </div>

            <div class="row g-3">
              <div 
                v-for="role in roles" 
                :key="role.id" 
                class="col-md-6 col-xl-4"
              >
                <div class="card border rounded-4 h-100 shadow-sm role-card transition-all">
                  <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                      <!-- Card Top Header -->
                      <div class="d-flex align-items-start justify-content-between gap-2 mb-2.5">
                        <span 
                          class="badge rounded-pill px-3 py-1 fs-12 fw-bold"
                          :class="`bg-${role.badge_color || 'primary'}-subtle text-${role.badge_color || 'primary'} border border-${role.badge_color || 'primary'}-subtle`"
                        >
                          {{ role.label }}
                        </span>
                        
                        <span v-if="role.is_system" class="badge bg-secondary-subtle text-secondary rounded-pill fs-10">
                          Bawaan Sistem
                        </span>
                        <span v-else class="badge bg-info-subtle text-info rounded-pill fs-10">
                          Kustom
                        </span>
                      </div>

                      <div class="font-monospace fs-11 text-muted mb-2">slug: <strong>{{ role.name }}</strong></div>
                      <p class="fs-12 text-dark-emphasis mb-3 lh-base">{{ role.description || 'Tidak ada deskripsi rinci.' }}</p>
                    </div>

                    <!-- Card Stats & Actions -->
                    <div class="pt-3 border-top mt-2">
                      <div class="d-flex align-items-center justify-content-between fs-12 mb-3">
                        <span class="text-muted d-flex align-items-center gap-1">
                          <i class="ri-team-line text-primary"></i>
                          <span>{{ role.users_count || 0 }} Anggota Panitia</span>
                        </span>
                        <span class="fw-semibold text-success d-flex align-items-center gap-1">
                          <i class="ri-key-line"></i>
                          <span>{{ role.name === 'super_admin' ? allPermissions.length : role.permission_ids.length }} Izin Aktif</span>
                        </span>
                      </div>

                      <div class="d-flex align-items-center gap-2">
                        <button
                          type="button"
                          class="btn btn-xs btn-outline-primary rounded-pill px-3 py-1 flex-grow-1"
                          @click="selectedRoleId = role.id; activeTab = 'matrix'"
                        >
                          <i class="ri-equalizer-line me-1"></i>Atur Izin
                        </button>
                        <button
                          type="button"
                          class="btn btn-xs btn-light border rounded-pill px-2.5 py-1"
                          @click="openEditRoleModal(role)"
                          title="Edit Informasi Peran"
                        >
                          <i class="ri-edit-line text-muted"></i>
                        </button>
                        <button
                          v-if="!role.is_system"
                          type="button"
                          class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1"
                          @click="handleDeleteRole(role)"
                          title="Hapus Peran Kustom"
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

          <!-- ============================================================= -->
          <!-- TAB 3: HAK AKSES KHUSUS PENGGUNA (USER OVERRIDES)             -->
          <!-- ============================================================= -->
          <div v-else-if="activeTab === 'users'">
            
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3.5">
              <div>
                <h5 class="fs-15 fw-bold text-dark-emphasis mb-0">Izin Override Khusus per Anggota Panitia</h5>
                <p class="fs-12 text-muted mb-0">
                  Gunakan fitur ini jika ada panitia tertentu yang memerlukan izin khusus di luar peran standarnya tanpa harus mengubah peran utama.
                </p>
              </div>

              <!-- Search Panitia Input -->
              <div class="position-relative" style="min-width: 280px;">
                <input 
                  v-model="userSearch" 
                  type="text" 
                  class="form-control form-control-sm rounded-pill ps-4 pe-3" 
                  placeholder="Cari nama, NIP, email, atau peran..."
                />
                <span class="ri-search-line position-absolute top-50 translate-middle-y text-muted" style="left: 12px; font-size: 13px;"></span>
              </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive rounded-4 border">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-12 text-uppercase">
                  <tr>
                    <th class="ps-3 py-3">Nama &amp; NIP Panitia</th>
                    <th>Email</th>
                    <th>Peran Utama</th>
                    <th>Status Hak Akses</th>
                    <th class="text-end pe-3">Aksi</th>
                  </tr>
                </thead>
                <tbody class="fs-12">
                  <tr v-for="user in filteredUsers" :key="user.id">
                    <td class="ps-3 py-3">
                      <div class="fw-bold text-dark-emphasis">{{ user.name }}</div>
                      <small class="text-muted font-monospace">{{ user.nip ? `NIP: ${user.nip}` : 'NIP: -' }}</small>
                    </td>
                    <td>
                      <span class="text-muted">{{ user.email }}</span>
                    </td>
                    <td>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-0.5 fs-11">
                        {{ user.role }}
                      </span>
                    </td>
                    <td>
                      <span 
                        v-if="user.custom_permissions && Object.keys(user.custom_permissions).length > 0"
                        class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 fs-11 fw-semibold"
                      >
                        <i class="ri-tools-line me-1"></i>{{ Object.keys(user.custom_permissions).length }} Izin Khusus
                      </span>
                      <span 
                        v-else 
                        class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-0.5 fs-11"
                      >
                        Mengikuti Peran
                      </span>
                    </td>
                    <td class="text-end pe-3">
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1"
                        @click="openUserOverrideModal(user)"
                      >
                        <i class="ri-equalizer-line"></i>
                        <span>Atur Izin Khusus</span>
                      </button>
                    </td>
                  </tr>

                  <tr v-if="filteredUsers.length === 0">
                    <td colspan="5" class="text-center py-4 text-muted">
                      <i class="ri-search-line fs-24 d-block mb-1"></i>
                      <span>Tidak ditemukan data panitia yang cocok dengan pencarian.</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>

        </div>
      </div>

    </div>

    <!-- ================================================================= -->
    <!-- MODAL 1: TAMBAH / EDIT PERAN BARU                                 -->
    <!-- ================================================================= -->
    <div 
      v-if="isRoleModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0, 0, 0, 0.6); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom p-3.5">
            <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2">
              <i class="ri-shield-star-line text-primary"></i>
              <span>{{ isEditingRole ? 'Perbarui Informasi Peran' : 'Tambah Peran Panitia Baru' }}</span>
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="isRoleModalOpen = false"
              aria-label="Tutup"
            ></button>
          </div>

          <form @submit.prevent="submitRoleForm">
            <div class="modal-body p-4 d-flex flex-column gap-3">
              
              <!-- Label / Nama Peran -->
              <div>
                <label class="form-label fs-12 fw-semibold mb-1">Nama Peran / Label <span class="text-danger">*</span></label>
                <input 
                  v-model="roleForm.label" 
                  type="text" 
                  class="form-control fs-12 py-2" 
                  placeholder="Contoh: Koordinator Konsumsi, LO Delegasi, Juri Eksternal"
                  required
                />
              </div>

              <!-- Slug (Hanya untuk buat baru) -->
              <div v-if="!isEditingRole">
                <label class="form-label fs-12 fw-semibold mb-1">Slug Pengenal (Unik) <span class="text-danger">*</span></label>
                <input 
                  v-model="roleForm.name" 
                  type="text" 
                  class="form-control fs-12 font-monospace py-2" 
                  placeholder="contoh: koordinator_konsumsi"
                  required
                />
                <small class="text-muted fs-11">Hanya huruf kecil, angka, dan garis bawah (_).</small>
              </div>

              <!-- Badge Color Selector (Wajib CustomSelect) -->
              <div>
                <label class="form-label fs-12 fw-semibold mb-1">Warna Lencana (Badge Color) <span class="text-danger">*</span></label>
                <CustomSelect
                  v-model="roleForm.badge_color"
                  :options="badgeColorOptions"
                  placeholder="Pilih Warna Lencana"
                />
              </div>

              <!-- Deskripsi Tanggung Jawab -->
              <div>
                <label class="form-label fs-12 fw-semibold mb-1">Deskripsi &amp; Cakupan Tugas</label>
                <textarea 
                  v-model="roleForm.description" 
                  class="form-control fs-12" 
                  rows="3" 
                  placeholder="Jelaskan ruang lingkup tanggung jawab peran panitia ini..."
                ></textarea>
              </div>

            </div>

            <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
              <button 
                type="button" 
                class="btn btn-sm btn-light border rounded-pill px-3" 
                @click="isRoleModalOpen = false"
              >
                Batal
              </button>
              <button 
                type="submit" 
                class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold"
                :disabled="isSubmittingRole"
              >
                <span v-if="isSubmittingRole" class="spinner-border spinner-border-sm me-1"></span>
                <span>{{ isSubmittingRole ? 'Menyimpan...' : (isEditingRole ? 'Simpan Perubahan' : 'Buat Peran Baru') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 2: ATUR HAK AKSES KHUSUS PENGGUNA (USER OVERRIDE)           -->
    <!-- ================================================================= -->
    <div 
      v-if="isUserModalOpen && selectedUser" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0, 0, 0, 0.6); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom p-3.5">
            <div>
              <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2 mb-0.5">
                <i class="ri-user-settings-line text-primary"></i>
                <span>Hak Akses Khusus: {{ selectedUser.name }}</span>
              </h5>
              <div class="fs-11 text-muted">
                Peran Utama: <span class="badge bg-primary-subtle text-primary">{{ selectedUser.role }}</span>
                <span class="mx-1">•</span> Email: {{ selectedUser.email }}
              </div>
            </div>
            <button 
              type="button" 
              class="btn-close" 
              @click="isUserModalOpen = false"
              aria-label="Tutup"
            ></button>
          </div>

          <div class="modal-body p-4">
            
            <div class="alert alert-info py-2.5 px-3 fs-11 rounded-3 mb-3 d-flex align-items-start gap-2">
              <i class="ri-information-line fs-14 text-primary flex-shrink-0 mt-0.5"></i>
              <div>
                <strong>3 Pilihan Override untuk Setiap Modul Izin:</strong><br>
                <span class="badge bg-secondary-subtle text-secondary me-1">Ikuti Peran</span> Mengikuti izin default dari peran utama.<br>
                <span class="badge bg-success-subtle text-success me-1">Izinkan Khusus</span> Berikan akses meskipun peran utamanya tidak memilikinya.<br>
                <span class="badge bg-danger-subtle text-danger me-1">Larang Khusus</span> Cabut akses meskipun peran utamanya mengizinkannya.
              </div>
            </div>

            <!-- Permission Groups for User -->
            <div class="d-flex flex-column gap-3">
              <div 
                v-for="(perms, groupName) in permissionsGrouped" 
                :key="groupName"
                class="border rounded-3 p-3 bg-light-subtle"
              >
                <h6 class="fs-12 fw-bold text-dark-emphasis mb-2.5 d-flex align-items-center gap-1.5">
                  <i class="ri-folder-keyhole-line text-primary"></i>
                  <span>{{ groupName }}</span>
                </h6>

                <div class="d-flex flex-column gap-2">
                  <div 
                    v-for="perm in perms" 
                    :key="perm.id"
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between p-2 rounded border bg-card gap-2"
                  >
                    <div>
                      <div class="fs-12 fw-semibold text-dark-emphasis">{{ perm.label }}</div>
                      <small class="text-muted font-monospace fs-10">{{ perm.name }}</small>
                    </div>

                    <!-- 3-Way Selector Pills -->
                    <div class="btn-group btn-group-sm rounded-pill border p-0.5 bg-light flex-shrink-0" role="group">
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2.5 py-0.5 fs-11 transition-all"
                        :class="getUserPermState(perm.name) === 'inherit' ? 'btn-secondary text-white' : 'btn-light border-0 text-muted'"
                        @click="setUserPermState(perm.name, 'inherit')"
                        title="Mengikuti izin default peran"
                      >
                        Ikuti Peran
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2.5 py-0.5 fs-11 transition-all"
                        :class="getUserPermState(perm.name) === 'granted' ? 'btn-success text-white' : 'btn-light border-0 text-muted'"
                        @click="setUserPermState(perm.name, 'granted')"
                        title="Beri izin khusus"
                      >
                        Izinkan
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2.5 py-0.5 fs-11 transition-all"
                        :class="getUserPermState(perm.name) === 'denied' ? 'btn-danger text-white' : 'btn-light border-0 text-muted'"
                        @click="setUserPermState(perm.name, 'denied')"
                        title="Larang secara spesifik"
                      >
                        Larang
                      </button>
                    </div>
                  </div>
                </div>

              </div>
            </div>

          </div>

          <div class="modal-footer border-top p-3 d-flex align-items-center justify-content-between">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-danger rounded-pill px-3" 
              @click="resetUserOverrides"
            >
              <i class="ri-refresh-line me-1"></i>Hapus Seluruh Override
            </button>

            <div class="d-flex gap-2">
              <button 
                type="button" 
                class="btn btn-sm btn-light border rounded-pill px-3" 
                @click="isUserModalOpen = false"
              >
                Batal
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold"
                :disabled="isSavingUserOverride"
                @click="saveUserOverrides"
              >
                <span v-if="isSavingUserOverride" class="spinner-border spinner-border-sm me-1"></span>
                <span>{{ isSavingUserOverride ? 'Menyimpan...' : 'Simpan Hak Akses Khusus' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </VelzonLayout>
</template>

<style scoped>
.custom-role-pills .nav-link {
  color: var(--vz-muted-color, #64748b);
  border-radius: 9999px;
  transition: all 0.2s ease-in-out;
}

.custom-role-pills .nav-link.active {
  background-color: var(--vz-primary, #405189) !important;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(64, 81, 137, 0.25);
}

.permission-item-box {
  background-color: var(--vz-card-bg, #ffffff);
  border-color: rgba(203, 213, 225, 0.6) !important;
}

.permission-item-box:hover {
  border-color: var(--vz-primary, #405189) !important;
  background-color: rgba(64, 81, 137, 0.02);
}

.permission-item-box.is-active {
  border-color: rgba(10, 179, 156, 0.5) !important;
  background-color: rgba(10, 179, 156, 0.04);
}

.permission-item-box.disabled-super {
  opacity: 0.9;
  cursor: default !important;
}

.role-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08) !important;
}

.btn-xs {
  font-size: 0.72rem;
  padding: 0.2rem 0.6rem;
}

/* Dark Mode Overrides */
[data-bs-theme="dark"] .permission-item-box {
  background-color: #1a2234 !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .permission-item-box.is-active {
  background-color: rgba(10, 179, 156, 0.12) !important;
  border-color: rgba(10, 179, 156, 0.4) !important;
}

[data-bs-theme="dark"] .bg-light-subtle {
  background-color: #151d2f !important;
}

[data-bs-theme="dark"] .role-card {
  background-color: #151d2f !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}
</style>
