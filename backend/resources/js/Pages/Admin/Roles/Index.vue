<script setup>
import { ref, computed, watch } from 'vue';
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
  competitions: {
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
// STANDARDIZED ROLE BADGE & ICON HELPERS
// -----------------------------------------------------------------------------
function getRoleLabel(roleKey, fallbackLabel = null) {
  if (fallbackLabel) return fallbackLabel;
  if (!roleKey) return 'Panitia';
  const defaultLabels = {
    super_admin: 'Super Admin',
    admin: 'Administrator',
    ketua_panitia: 'Ketua Panitia',
    wakil_ketua_panitia: 'Wakil Ketua Panitia',
    sekretaris: 'Sekretaris Pelaksana',
    koordinator_lomba: 'Koordinator Lomba',
    panitia: 'Panitia Pelaksana',
    kesekretariatan: 'Kesekretariatan',
    bendahara: 'Bendahara & Tiket',
    juri: 'Dewan Juri',
    wasit: 'Wasit / Match Official',
  };
  return defaultLabels[roleKey] || roleKey.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function getRoleBadgeClass(roleKey, badgeColor = null) {
  const badgeMap = {
    super_admin: 'badge-role-danger',
    admin: 'badge-role-primary',
    ketua_panitia: 'badge-role-purple',
    wakil_ketua_panitia: 'badge-role-indigo',
    sekretaris: 'badge-role-teal',
    koordinator_lomba: 'badge-role-warning',
    panitia: 'badge-role-info',
    kesekretariatan: 'badge-role-secondary',
    bendahara: 'badge-role-success',
    juri: 'badge-role-amber',
    wasit: 'badge-role-dark',
  };

  if (badgeMap[roleKey]) return badgeMap[roleKey];

  if (badgeColor) {
    const colorMap = {
      danger: 'badge-role-danger',
      primary: 'badge-role-primary',
      success: 'badge-role-success',
      warning: 'badge-role-warning',
      info: 'badge-role-info',
      secondary: 'badge-role-secondary',
      dark: 'badge-role-dark',
      purple: 'badge-role-purple',
      indigo: 'badge-role-indigo',
      teal: 'badge-role-teal',
    };
    return colorMap[badgeColor] || 'badge-role-primary';
  }

  return 'badge-role-secondary';
}

function getRoleIcon(roleKey) {
  const iconMap = {
    super_admin: 'ri-shield-star-fill',
    admin: 'ri-shield-user-line',
    ketua_panitia: 'ri-vip-crown-line',
    wakil_ketua_panitia: 'ri-user-star-line',
    sekretaris: 'ri-file-text-line',
    koordinator_lomba: 'ri-medal-fill',
    panitia: 'ri-team-line',
    kesekretariatan: 'ri-file-user-line',
    bendahara: 'ri-money-dollar-circle-line',
    juri: 'ri-award-line',
    wasit: 'ri-flag-line',
  };
  return iconMap[roleKey] || 'ri-shield-keyhole-line';
}

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

// Watch update props.roles dari Inertia
watch(() => props.roles, () => {
  initMatrixState();
}, { deep: true });

function isRoleModified(roleId) {
  const role = props.roles.find(r => r.id === roleId);
  if (!role || role.name === 'super_admin') return false;
  const original = role.permission_ids || [];
  const current = matrixState.value[roleId] || [];
  if (original.length !== current.length) return true;
  const currentSet = new Set(current);
  return original.some(id => !currentSet.has(id));
}

const hasUnsavedChanges = computed(() => {
  return isRoleModified(selectedRoleId.value);
});

function resetCurrentRoleMatrix() {
  const role = props.roles.find(r => r.id === selectedRoleId.value);
  if (!role) return;
  matrixState.value[selectedRoleId.value] = [...role.permission_ids];
  info(`Perubahan hak akses peran '${role.label}' dikembalikan ke data tersimpan.`);
}

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

// -----------------------------------------------------------------------------
// PERMISSION CLASSIFICATION & READ-ONLY HELPERS
// -----------------------------------------------------------------------------
function getPermissionKind(permName) {
  if (permName.endsWith('.view')) {
    return 'view'; // Read-only / Lihat Saja
  }
  if (permName.startsWith('settings.') || permName === 'roles.manage' || permName === 'users.manage') {
    return 'system'; // Kritis / Sistem / Pengaturan
  }
  return 'manage'; // Kelola / Eksekusi / Operasional
}

function getPermissionKindBadge(permName) {
  const kind = getPermissionKind(permName);
  if (kind === 'view') {
    return {
      kind: 'view',
      label: 'Lihat Saja',
      icon: 'ri-eye-line',
      class: 'bg-info-subtle text-info border border-info-subtle',
      desc: 'Hanya dapat memantau / membaca data tanpa hak mengubah.'
    };
  }
  if (kind === 'system') {
    return {
      kind: 'system',
      label: 'Sistem / Kritis',
      icon: 'ri-shield-keyhole-line',
      class: 'bg-danger-subtle text-danger border border-danger-subtle',
      desc: 'Konfigurasi mendalam, integrasi, dan wewenang pengguna.'
    };
  }
  return {
    kind: 'manage',
    label: 'Kelola / Eksekusi',
    icon: 'ri-settings-4-line',
    class: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
    desc: 'Wewenang operasional, tambah, ubah, dan hapus data.'
  };
}

function setReadOnlyMode() {
  if (selectedRole.value?.name === 'super_admin') return;
  // Ambil seluruh permission yang bertipe 'view' dan bukan pengaturan sistem
  const viewPermIds = props.allPermissions
    .filter(p => p.name.endsWith('.view') && !p.name.startsWith('settings.'))
    .map(p => p.id);
  matrixState.value[selectedRoleId.value] = viewPermIds;
  info(`Mode Lihat Saja (Read-Only) diterapkan pada peran '${selectedRole.value.label}'. Seluruh wewenang kelola & konfigurasi sistem dinonaktifkan.`);
}

// -----------------------------------------------------------------------------
// MATRIX LIVE SEARCH & FILTERING
// -----------------------------------------------------------------------------
const permSearch = ref('');
const moduleFilter = ref('all');
const statusPermFilter = ref('all'); // 'all' | 'active' | 'inactive'
const permKindFilter = ref('all'); // 'all' | 'view' | 'manage' | 'system'

const filteredPermissionsGrouped = computed(() => {
  const query = permSearch.value.trim().toLowerCase();
  const selectedMod = moduleFilter.value;
  const statusFilt = statusPermFilter.value;
  const kindFilt = permKindFilter.value;

  const result = {};

  for (const [groupName, perms] of Object.entries(props.permissionsGrouped || {})) {
    if (selectedMod !== 'all' && selectedMod !== groupName) {
      continue;
    }

    const filteredPerms = perms.filter(p => {
      // 1. Kind Filter (Lihat Saja vs Kelola vs Sistem)
      if (kindFilt !== 'all' && getPermissionKind(p.name) !== kindFilt) {
        return false;
      }

      // 2. Text Search Filter
      const matchesSearch = !query || 
        p.label.toLowerCase().includes(query) || 
        p.name.toLowerCase().includes(query) || 
        (p.description && p.description.toLowerCase().includes(query)) ||
        groupName.toLowerCase().includes(query);

      if (!matchesSearch) return false;

      // 3. Status Filter
      if (statusFilt === 'active') {
        return isPermChecked(selectedRoleId.value, p.id);
      }
      if (statusFilt === 'inactive') {
        return !isPermChecked(selectedRoleId.value, p.id);
      }
      return true;
    });

    if (filteredPerms.length > 0) {
      result[groupName] = filteredPerms;
    }
  }

  return result;
});

const totalFilteredCount = computed(() => {
  return Object.values(filteredPermissionsGrouped.value).reduce((acc, perms) => acc + perms.length, 0);
});

const moduleOptions = computed(() => {
  const opts = [
    { value: 'all', label: 'Semua Kategori Modul' },
  ];
  Object.keys(props.permissionsGrouped || {}).forEach(groupName => {
    opts.push({
      value: groupName,
      label: `${groupName} (${props.permissionsGrouped[groupName]?.length || 0} Izin)`,
    });
  });
  return opts;
});

const statusFilterOptions = [
  { value: 'all', label: 'Semua Status Izin' },
  { value: 'active', label: 'Hanya Izin Aktif' },
  { value: 'inactive', label: 'Hanya Izin Non-aktif' },
];

const permKindFilterOptions = [
  { value: 'all', label: 'Semua Tipe Izin' },
  { value: 'view', label: '👁️ Hanya Izin Lihat Saja (Read-Only)' },
  { value: 'manage', label: '⚙️ Hanya Izin Kelola / Eksekusi' },
  { value: 'system', label: '🚨 Hanya Izin Sistem / Kritis' },
];

function resetFilters() {
  permSearch.value = '';
  moduleFilter.value = 'all';
  statusPermFilter.value = 'all';
  permKindFilter.value = 'all';
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

// -----------------------------------------------------------------------------
// BULK ROLE PERMISSIONS STATE & ACTIONS
// -----------------------------------------------------------------------------
const isBulkRoleModalOpen = ref(false);
const bulkRoleAction = ref('grant'); // 'grant' | 'revoke' | 'sync'
const bulkRoleSelectedRoleIds = ref([]);
const bulkRoleSelectedPermIds = ref([]);
const isSubmittingBulkRole = ref(false);

function openBulkRoleModal() {
  bulkRoleAction.value = 'grant';
  bulkRoleSelectedRoleIds.value = props.roles.filter(r => r.name !== 'super_admin').map(r => r.id);
  bulkRoleSelectedPermIds.value = [];
  isBulkRoleModalOpen.value = true;
}

function toggleBulkRolePermission(permId) {
  const idx = bulkRoleSelectedPermIds.value.indexOf(permId);
  if (idx > -1) {
    bulkRoleSelectedPermIds.value.splice(idx, 1);
  } else {
    bulkRoleSelectedPermIds.value.push(permId);
  }
}

function toggleBulkRoleGroup(perms, grantAll = true) {
  const current = new Set(bulkRoleSelectedPermIds.value);
  perms.forEach(p => {
    if (grantAll) {
      current.add(p.id);
    } else {
      current.delete(p.id);
    }
  });
  bulkRoleSelectedPermIds.value = Array.from(current);
}

function submitBulkRole() {
  if (bulkRoleSelectedRoleIds.value.length === 0) {
    error('Pilih minimal satu peran sasaran.');
    return;
  }
  if (bulkRoleSelectedPermIds.value.length === 0) {
    error('Pilih minimal satu hak akses yang ingin diterapkan.');
    return;
  }

  isSubmittingBulkRole.value = true;
  router.post('/admin/pengguna/hak-akses/bulk-matrix', {
    role_ids: bulkRoleSelectedRoleIds.value,
    action: bulkRoleAction.value,
    permission_ids: bulkRoleSelectedPermIds.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingBulkRole.value = false;
      isBulkRoleModalOpen.value = false;
      initMatrixState();
      success('Operasi hak akses massal berhasil diterapkan pada seluruh peran terpilih!', 'Berhasil');
    },
    onError: () => {
      isSubmittingBulkRole.value = false;
      error('Gagal menerapkan hak akses massal ke peran.');
    }
  });
}

// -----------------------------------------------------------------------------
// COPY ROLE PERMISSIONS STATE & ACTIONS
// -----------------------------------------------------------------------------
const isCopyRoleModalOpen = ref(false);
const copySourceRoleId = ref(props.roles[0]?.id || null);
const copyTargetRoleIds = ref([]);
const isSubmittingCopyRole = ref(false);

const copySourceOptions = computed(() => {
  return props.roles.map(r => ({
    value: r.id,
    label: `${r.label} (${r.permissions?.length || (matrixState.value[r.id]?.length || 0)} Izin)`
  }));
});

function openCopyRoleModal() {
  copySourceRoleId.value = selectedRoleId.value;
  copyTargetRoleIds.value = [];
  isCopyRoleModalOpen.value = true;
}

function submitCopyRole() {
  if (!copySourceRoleId.value) {
    error('Pilih peran sumber acuan.');
    return;
  }
  if (copyTargetRoleIds.value.length === 0) {
    error('Pilih minimal satu peran tujuan.');
    return;
  }

  isSubmittingCopyRole.value = true;
  router.post('/admin/pengguna/hak-akses/copy-role-matrix', {
    source_role_id: copySourceRoleId.value,
    target_role_ids: copyTargetRoleIds.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingCopyRole.value = false;
      isCopyRoleModalOpen.value = false;
      initMatrixState();
      success('Susunan hak akses berhasil disalin ke seluruh peran tujuan!', 'Berhasil Disalin');
    },
    onError: () => {
      isSubmittingCopyRole.value = false;
      error('Gagal menyalin hak akses antar peran.');
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
const selectedUserRole = ref('');
const selectedUserCompId = ref('');
const isSavingUserRole = ref(false);
const isSyncingCoordinators = ref(false);
const userOverrides = ref({}); // { 'perm.name': true | false }
const isSavingUserOverride = ref(false);

const roleDropdownOptions = computed(() => {
  return (props.roles || []).map(r => ({
    value: r.name,
    label: r.label + (r.name === 'koordinator_lomba' ? ' (PJ Lomba)' : '')
  }));
});

const compDropdownOptions = computed(() => {
  return [
    { value: '', label: '-- Pilih Cabang Lomba yang Dikoordinasi --' },
    ...(props.competitions || []).map(c => ({
      value: String(c.id),
      label: `${c.title} (${c.category_label || 'Lomba'})`
    }))
  ];
});

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
  selectedUserRole.value = user.role || 'panitia';
  selectedUserCompId.value = user.competition_id || '';
  userOverrides.value = { ...(user.custom_permissions || {}) };
  isUserModalOpen.value = true;
}

function saveUserPrimaryRole() {
  if (!selectedUser.value) return;
  isSavingUserRole.value = true;

  router.put(`/admin/pengguna/hak-akses/user/${selectedUser.value.id}/role`, {
    role: selectedUserRole.value,
    competition_id: selectedUserRole.value === 'koordinator_lomba' ? selectedUserCompId.value : null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingUserRole.value = false;
      if (selectedUser.value) {
        selectedUser.value.role = selectedUserRole.value;
        selectedUser.value.competition_id = selectedUserRole.value === 'koordinator_lomba' ? selectedUserCompId.value : null;
      }
      success(`Peran utama panitia '${selectedUser.value?.name}' berhasil diubah menjadi '${getRoleLabel(selectedUserRole.value)}'!`, 'Tersimpan');
    },
    onError: () => {
      isSavingUserRole.value = false;
      error('Gagal memperbarui peran utama pengguna.');
    }
  });
}

function handleSyncCoordinators() {
  isSyncingCoordinators.value = true;
  router.post('/admin/pengguna/hak-akses/sync-coordinators', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isSyncingCoordinators.value = false;
      success('Penyelarasan peran koordinator lomba selesai diterapkan.', 'Tersinkron');
    },
    onError: () => {
      isSyncingCoordinators.value = false;
      error('Gagal menyelaraskan peran koordinator lomba.');
    }
  });
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

const userPermSearch = ref('');

function doesRoleHavePerm(permName) {
  if (!selectedUser.value) return false;
  if (selectedUser.value.role === 'super_admin') return true;
  const userRole = props.roles.find(r => r.name === selectedUser.value.role);
  if (!userRole) return false;
  return (userRole.permission_names || []).includes(permName);
}

const filteredModalPermissionsGrouped = computed(() => {
  const q = userPermSearch.value.trim().toLowerCase();
  if (!q) return props.permissionsGrouped;

  const res = {};
  for (const [groupName, perms] of Object.entries(props.permissionsGrouped || {})) {
    const matched = perms.filter(p => 
      p.label.toLowerCase().includes(q) || 
      p.name.toLowerCase().includes(q) || 
      (p.description && p.description.toLowerCase().includes(q))
    );
    if (matched.length > 0) {
      res[groupName] = matched;
    }
  }
  return res;
});

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

// -----------------------------------------------------------------------------
// BULK USER OVERRIDES STATE & ACTIONS
// -----------------------------------------------------------------------------
const selectedUserIds = ref([]);
const isBulkUserModalOpen = ref(false);
const bulkUserAction = ref('grant'); // 'grant' | 'revoke' | 'clear_perms' | 'reset_all'
const bulkUserPermNames = ref([]);
const isSubmittingBulkUser = ref(false);

const isAllUsersSelected = computed(() => {
  if (filteredUsers.value.length === 0) return false;
  return filteredUsers.value.every(u => selectedUserIds.value.includes(u.id));
});

function toggleSelectAllUsers() {
  if (isAllUsersSelected.value) {
    selectedUserIds.value = [];
  } else {
    selectedUserIds.value = filteredUsers.value.map(u => u.id);
  }
}

function toggleUserSelection(userId) {
  const idx = selectedUserIds.value.indexOf(userId);
  if (idx > -1) {
    selectedUserIds.value.splice(idx, 1);
  } else {
    selectedUserIds.value.push(userId);
  }
}

function openBulkUserModal(action = 'grant') {
  if (selectedUserIds.value.length === 0) {
    error('Pilih minimal satu anggota panitia terlebih dahulu.');
    return;
  }
  bulkUserAction.value = action;
  bulkUserPermNames.value = [];
  isBulkUserModalOpen.value = true;
}

function toggleBulkUserPerm(permName) {
  const idx = bulkUserPermNames.value.indexOf(permName);
  if (idx > -1) {
    bulkUserPermNames.value.splice(idx, 1);
  } else {
    bulkUserPermNames.value.push(permName);
  }
}

function toggleBulkUserGroup(perms, selectAll = true) {
  const current = new Set(bulkUserPermNames.value);
  perms.forEach(p => {
    if (selectAll) {
      current.add(p.name);
    } else {
      current.delete(p.name);
    }
  });
  bulkUserPermNames.value = Array.from(current);
}

function submitBulkUserOverrides() {
  if (selectedUserIds.value.length === 0) return;
  if (['grant', 'revoke', 'clear_perms'].includes(bulkUserAction.value) && bulkUserPermNames.value.length === 0) {
    error('Pilih minimal satu hak akses yang ingin diproses.');
    return;
  }

  isSubmittingBulkUser.value = true;
  router.post('/admin/pengguna/hak-akses/bulk-user-permissions', {
    user_ids: selectedUserIds.value,
    action: bulkUserAction.value,
    permission_names: bulkUserPermNames.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingBulkUser.value = false;
      isBulkUserModalOpen.value = false;
      selectedUserIds.value = [];
      success('Hak akses khusus massal berhasil diterapkan pada seluruh panitia terpilih!', 'Berhasil');
    },
    onError: () => {
      isSubmittingBulkUser.value = false;
      error('Gagal menerapkan hak akses khusus massal.');
    }
  });
}

async function handleBulkResetAllUsers() {
  if (selectedUserIds.value.length === 0) return;
  const confirmed = await confirmDialog({
    title: 'Reset Seluruh Override Panitia Terpilih?',
    message: `Tindakan ini akan menghapus seluruh hak akses khusus pada ${selectedUserIds.value.length} panitia terpilih dan mengembalikan mereka sepenuhnya mengikuti perannya masing-masing.`,
    type: 'warning',
    confirmText: 'Ya, Reset Semua',
  });

  if (!confirmed) return;

  router.post('/admin/pengguna/hak-akses/bulk-user-permissions', {
    user_ids: selectedUserIds.value,
    action: 'reset_all',
    permission_names: [],
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedUserIds.value = [];
      success('Seluruh override panitia terpilih berhasil direset.', 'Berhasil');
    },
    onError: () => {
      error('Gagal mereset override panitia.');
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
            
            <!-- Role Selector Strip -->
            <div class="p-3 rounded-4 bg-light-subtle border mb-4">
              <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-2.5">
                <span class="fs-12 fw-bold text-dark-emphasis d-flex align-items-center gap-1.5">
                  <i class="ri-user-settings-line text-primary"></i>
                  <span>PILIH PERAN PANITIA:</span>
                </span>
                <span class="fs-11 text-muted">
                  Klik peran untuk mengonfigurasi matriks izinnya. Peran dengan tanda bintang memiliki perubahan belum disimpan.
                </span>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <button
                  v-for="role in roles"
                  :key="role.id"
                  type="button"
                  class="btn btn-sm rounded-pill px-3 py-1.5 fs-12 fw-semibold d-inline-flex align-items-center gap-2 transition-all position-relative"
                  :class="selectedRoleId === role.id 
                    ? 'btn-primary text-white shadow' 
                    : 'btn-light border text-dark-emphasis bg-card'"
                  @click="selectedRoleId = role.id"
                >
                  <i :class="getRoleIcon(role.name)" class="fs-13"></i>
                  <span>{{ getRoleLabel(role.name, role.label) }}</span>
                  
                  <!-- Permission Count Pill -->
                  <span 
                    class="badge rounded-pill fs-10 px-1.5 py-0.5"
                    :class="selectedRoleId === role.id ? 'bg-white text-primary' : 'bg-primary-subtle text-primary border border-primary-subtle'"
                  >
                    {{ role.name === 'super_admin' ? allPermissions.length : (matrixState[role.id]?.length || 0) }}
                  </span>

                  <!-- Unsaved Dot Indicator -->
                  <span 
                    v-if="isRoleModified(role.id)" 
                    class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"
                    title="Ada perubahan belum disimpan"
                  >
                    <span class="visually-hidden">Belum disimpan</span>
                  </span>
                </button>
              </div>
            </div>

            <!-- Role Summary Banner -->
            <div class="card border rounded-4 p-3.5 mb-4 shadow-sm bg-card">
              <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="avatar-md flex-shrink-0">
                    <span 
                      class="avatar-title rounded-circle fs-24 border shadow-sm"
                      :class="getRoleBadgeClass(selectedRole?.name, selectedRole?.badge_color)"
                    >
                      <i :class="getRoleIcon(selectedRole?.name)"></i>
                    </span>
                  </div>
                  <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                      <h5 class="mb-0 fw-bold fs-16 text-dark-emphasis">{{ selectedRole?.label }}</h5>
                      <span class="badge bg-dark-subtle text-dark-emphasis rounded-pill fs-11 font-monospace border">
                        slug: {{ selectedRole?.name }}
                      </span>
                      <span 
                        class="badge rounded-pill fs-11 fw-semibold"
                        :class="getRoleBadgeClass(selectedRole?.name, selectedRole?.badge_color)"
                      >
                        {{ selectedRole?.is_system ? 'Peran Bawaan Sistem' : 'Peran Kustom' }}
                      </span>
                      <span v-if="hasUnsavedChanges" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill fs-11 fw-semibold">
                        <i class="ri-alert-line me-1"></i>Ada Perubahan Belum Disimpan
                      </span>
                    </div>
                    <p class="mb-0 fs-12 text-muted lh-sm">{{ selectedRole?.description || 'Tidak ada deskripsi rinci untuk peran ini.' }}</p>
                  </div>
                </div>

                <!-- Stats & Primary Action Buttons -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3 ms-lg-auto">
                  <div class="text-start text-lg-end pe-lg-3 border-lg-end">
                    <div class="fs-13 fw-bold text-dark-emphasis">
                      {{ selectedRole?.name === 'super_admin' ? allPermissions.length : (matrixState[selectedRoleId]?.length || 0) }} / {{ allPermissions.length }} Izin Aktif
                    </div>
                    <small class="text-muted fs-11 d-flex align-items-center gap-1 justify-content-lg-end">
                      <i class="ri-user-line"></i>
                      <span>{{ selectedRole?.users_count || 0 }} Panitia Bertugas</span>
                    </small>
                  </div>

                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button
                      v-if="selectedRole?.name !== 'super_admin'"
                      type="button"
                      class="btn btn-sm btn-outline-info rounded-pill px-3 fw-semibold"
                      @click="setReadOnlyMode"
                      title="Setel peran ini hanya memiliki hak akses melihat saja (Read-Only)"
                    >
                      <i class="ri-eye-line me-1"></i>Mode Lihat Saja
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold"
                      @click="openCopyRoleModal"
                      title="Salin susunan hak akses dari peran lain"
                    >
                      <i class="ri-file-copy-line me-1"></i>Salin Peran
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold"
                      @click="openBulkRoleModal"
                      title="Kelola hak akses untuk banyak peran sekaligus"
                    >
                      <i class="ri-stack-line me-1"></i>Kelola Massal
                    </button>
                    <button
                      v-if="selectedRole?.name !== 'super_admin'"
                      type="button"
                      class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold"
                      @click="grantAllPermissions"
                      title="Pilih seluruh hak akses untuk peran ini"
                    >
                      <i class="ri-checkbox-multiple-line me-1"></i>Pilih Semua
                    </button>
                    <button
                      v-if="selectedRole?.name !== 'super_admin'"
                      type="button"
                      class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold"
                      @click="revokeAllPermissions"
                      title="Hapus seluruh hak akses untuk peran ini"
                    >
                      <i class="ri-close-circle-line me-1"></i>Kosongkan
                    </button>
                    <button
                      v-if="hasUnsavedChanges"
                      type="button"
                      class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold"
                      @click="resetCurrentRoleMatrix"
                      title="Kembalikan perubahan yang belum disimpan"
                    >
                      <i class="ri-restart-line me-1"></i>Batal
                    </button>
                    <button
                      type="button"
                      class="btn btn-sm btn-primary rounded-pill px-3.5 shadow-sm fw-bold d-flex align-items-center gap-1.5"
                      :disabled="isRoleSaving || selectedRole?.name === 'super_admin'"
                      @click="saveRolePermissions"
                    >
                      <span v-if="isRoleSaving" class="spinner-border spinner-border-sm"></span>
                      <i v-else class="ri-save-3-fill"></i>
                      <span>{{ isRoleSaving ? 'Menyimpan...' : 'Simpan Izin Peran' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Super Admin Notice -->
            <div v-if="selectedRole?.name === 'super_admin'" class="alert alert-danger py-2.5 px-3.5 rounded-3 mb-4 fs-12 d-flex align-items-center gap-2.5 border border-danger-subtle">
              <i class="ri-shield-flash-fill fs-18 text-danger flex-shrink-0"></i>
              <div>
                <strong>Hak Akses Super Admin Bersifat Mutlak (Bypass Penuh):</strong> Seluruh izin sistem otomatis aktif secara permanen dan tidak dapat dinonaktifkan demi integritas kendali penuh sistem.
              </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="card border rounded-4 p-3 mb-3.5 bg-light-subtle shadow-none">
              <div class="row g-2.5 align-items-center">
                <!-- Search Box -->
                <div class="col-12 col-md-4">
                  <div class="position-relative">
                    <input 
                      v-model="permSearch" 
                      type="text" 
                      class="form-control form-control-sm rounded-pill ps-4 pe-4 bg-card border" 
                      placeholder="Cari izin berdasarkan nama, modul, deskripsi..."
                    />
                    <i class="ri-search-line position-absolute top-50 translate-middle-y text-muted" style="left: 12px; font-size: 13px;"></i>
                    <button 
                      v-if="permSearch" 
                      type="button" 
                      class="btn btn-link btn-sm position-absolute top-50 translate-middle-y text-muted p-0" 
                      style="right: 12px;"
                      @click="permSearch = ''"
                    >
                      <i class="ri-close-circle-fill"></i>
                    </button>
                  </div>
                </div>

                <!-- Module Category Filter (CustomSelect) -->
                <div class="col-12 col-sm-6 col-md-3">
                  <CustomSelect
                    v-model="moduleFilter"
                    :options="moduleOptions"
                    placeholder="Pilih Kategori Modul"
                  />
                </div>

                <!-- Kind Filter (View vs Manage vs System) (CustomSelect) -->
                <div class="col-12 col-sm-6 col-md-3">
                  <CustomSelect
                    v-model="permKindFilter"
                    :options="permKindFilterOptions"
                    placeholder="Pilih Tipe Izin"
                  />
                </div>

                <!-- Status Filter (CustomSelect) -->
                <div class="col-12 col-sm-6 col-md-2">
                  <CustomSelect
                    v-model="statusPermFilter"
                    :options="statusFilterOptions"
                    placeholder="Pilih Status Izin"
                  />
                </div>
              </div>

              <!-- Filter Info & Reset Link -->
              <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top fs-11 text-muted">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <span>
                    Menampilkan <strong>{{ totalFilteredCount }}</strong> dari {{ allPermissions.length }} total izin sistem
                  </span>
                  <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill fs-10 px-2 py-0.5">
                    👁️ Lihat Saja: {{ allPermissions.filter(p => p.name.endsWith('.view')).length }}
                  </span>
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill fs-10 px-2 py-0.5">
                    ⚙️ Kelola: {{ allPermissions.filter(p => !p.name.endsWith('.view') && !p.name.startsWith('settings.') && p.name !== 'roles.manage').length }}
                  </span>
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill fs-10 px-2 py-0.5">
                    🚨 Sistem: {{ allPermissions.filter(p => p.name.startsWith('settings.') || p.name === 'roles.manage').length }}
                  </span>
                </div>
                <button 
                  v-if="permSearch || moduleFilter !== 'all' || statusPermFilter !== 'all' || permKindFilter !== 'all'"
                  type="button" 
                  class="btn btn-link btn-xs p-0 text-primary text-decoration-none fw-semibold"
                  @click="resetFilters"
                >
                  <i class="ri-refresh-line me-1"></i>Reset Filter Pencarian
                </button>
              </div>
            </div>

            <!-- Empty Filter State -->
            <div v-if="totalFilteredCount === 0" class="card border rounded-4 p-5 text-center bg-card shadow-sm mb-4">
              <div class="avatar-lg mx-auto mb-3">
                <span class="avatar-title bg-light text-muted rounded-circle fs-32">
                  <i class="ri-filter-off-line"></i>
                </span>
              </div>
              <h5 class="fs-15 fw-bold text-dark-emphasis mb-1">Tidak Ada Izin yang Cocok</h5>
              <p class="fs-12 text-muted mb-3">Tidak ditemukan izin yang sesuai dengan kata kunci pencarian atau filter yang dipilih.</p>
              <div>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3.5" @click="resetFilters">
                  <i class="ri-refresh-line me-1"></i>Reset Filter
                </button>
              </div>
            </div>

            <!-- Permission Groups Accordions / Cards -->
            <div v-else class="d-flex flex-column gap-3.5">
              <div 
                v-for="(perms, groupName) in filteredPermissionsGrouped" 
                :key="groupName"
                class="card border rounded-4 shadow-none permission-group-card bg-card overflow-hidden"
              >
                <!-- Group Header -->
                <div class="card-header bg-light-subtle py-2.5 px-3.5 d-flex align-items-center justify-content-between border-bottom">
                  <div class="d-flex align-items-center gap-2">
                    <i class="ri-folder-keyhole-line text-primary fs-16"></i>
                    <h6 class="mb-0 fs-13 fw-bold text-dark-emphasis">{{ groupName }}</h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-11 px-2">
                      {{ perms.filter(p => isPermChecked(selectedRoleId, p.id)).length }} / {{ perms.length }} Aktif
                    </span>
                  </div>

                  <!-- Quick Select for this group -->
                  <div v-if="selectedRole?.name !== 'super_admin'" class="d-flex align-items-center gap-1.5">
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 fs-11 fw-medium"
                      @click="toggleGroupPermissions(perms, true)"
                      title="Beri semua izin di modul ini"
                    >
                      <i class="ri-check-line me-0.5"></i>Pilih Semua
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1 fs-11 fw-medium"
                      @click="toggleGroupPermissions(perms, false)"
                      title="Cabut semua izin di modul ini"
                    >
                      <i class="ri-close-line me-0.5"></i>Kosongkan
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
                        class="p-2.5 rounded-3 border permission-item-box transition-all cursor-pointer h-100 d-flex align-items-start gap-2.5 position-relative"
                        :class="{ 
                          'is-active': isPermChecked(selectedRoleId, perm.id),
                          'disabled-super': selectedRole?.name === 'super_admin'
                        }"
                        @click="togglePermission(perm.id)"
                      >
                        <!-- Custom Switch (pointer-events none to eliminate double click bug permanently) -->
                        <div class="form-check form-switch m-0 pt-0.5 flex-shrink-0" style="pointer-events: none;">
                          <input 
                            class="form-check-input" 
                            type="checkbox" 
                            :checked="isPermChecked(selectedRoleId, perm.id)"
                            :disabled="selectedRole?.name === 'super_admin'"
                          />
                        </div>

                        <!-- Permission Label & Details -->
                        <div class="flex-grow-1 user-select-none">
                          <div class="d-flex align-items-center justify-content-between gap-1 mb-1 flex-wrap">
                            <span class="fs-12 fw-semibold text-dark-emphasis">
                              {{ perm.label }}
                            </span>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                              <span 
                                class="badge rounded-pill fs-10 px-1.5 py-0.5"
                                :class="getPermissionKindBadge(perm.name).class"
                                :title="getPermissionKindBadge(perm.name).desc"
                              >
                                <i :class="getPermissionKindBadge(perm.name).icon" class="me-0.5"></i>
                                {{ getPermissionKindBadge(perm.name).label }}
                              </span>
                              <span 
                                v-if="isPermChecked(selectedRoleId, perm.id)" 
                                class="badge bg-success-subtle text-success border border-success-subtle fs-10 px-1.5 py-0.5 rounded-pill"
                              >
                                <i class="ri-check-line"></i> Aktif
                              </span>
                            </div>
                          </div>
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
              class="sticky-bottom mt-4 p-3 bg-card border rounded-4 shadow-lg d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3"
              style="z-index: 100;"
            >
              <div class="d-flex align-items-center gap-2">
                <span 
                  class="badge p-1.5 rounded-circle"
                  :class="hasUnsavedChanges ? 'bg-warning text-dark' : 'bg-success text-white'"
                >
                  <i :class="hasUnsavedChanges ? 'ri-alert-fill' : 'ri-check-fill'"></i>
                </span>
                <div class="fs-12 text-dark-emphasis">
                  <strong v-if="hasUnsavedChanges" class="text-warning-emphasis">
                    Ada perubahan belum disimpan untuk peran '{{ selectedRole?.label }}'.
                  </strong>
                  <span v-else class="text-muted">
                    Matriks hak akses peran <strong>{{ selectedRole?.label }}</strong> tersimpan sinkron.
                  </span>
                  <div class="fs-11 text-muted">
                    Perubahan akan langsung berdampak pada seluruh panitia dengan peran ini.
                  </div>
                </div>
              </div>

              <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-sm-auto">
                <button
                  v-if="hasUnsavedChanges"
                  type="button"
                  class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                  @click="resetCurrentRoleMatrix"
                >
                  <i class="ri-restart-line me-1"></i>Batalkan
                </button>
                <button
                  type="button"
                  class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2"
                  :class="{ 'btn-warning text-dark': hasUnsavedChanges }"
                  :disabled="isRoleSaving"
                  @click="saveRolePermissions"
                >
                  <span v-if="isRoleSaving" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-save-3-fill fs-15"></i>
                  <span>{{ isRoleSaving ? 'Menyimpan...' : (hasUnsavedChanges ? 'Simpan Perubahan' : 'Simpan Matriks') }}</span>
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
                          class="badge rounded-pill px-3 py-1 fs-12 fw-bold d-inline-flex align-items-center gap-1.5"
                          :class="getRoleBadgeClass(role.name, role.badge_color)"
                        >
                          <i :class="getRoleIcon(role.name)"></i>
                          <span>{{ getRoleLabel(role.name, role.label) }}</span>
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

              <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Tombol Sinkronisasi Koordinator Lomba -->
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-sm"
                  :disabled="isSyncingCoordinators"
                  @click="handleSyncCoordinators"
                  title="Penyelarasan otomatis seluruh akun panitia yang ditugaskan di cabang lomba menjadi peran Koordinator Lomba"
                >
                  <i class="ri-refresh-line" :class="{ 'ri-spin': isSyncingCoordinators }"></i>
                  <span>{{ isSyncingCoordinators ? 'Menyelaraskan...' : 'Sinkronkan Koordinator' }}</span>
                </button>

                <!-- Search Panitia Input -->
                <div class="position-relative" style="min-width: 260px;">
                  <input 
                    v-model="userSearch" 
                    type="text" 
                    class="form-control form-control-sm rounded-pill ps-4 pe-3" 
                    placeholder="Cari nama, NIP, email, atau peran..."
                  />
                  <span class="ri-search-line position-absolute top-50 translate-middle-y text-muted" style="left: 12px; font-size: 13px;"></span>
                </div>
              </div>
            </div>

            <!-- Bulk Action Toolbar (When users selected) -->
            <div 
              v-if="selectedUserIds.length > 0" 
              class="p-3 bg-primary-subtle border border-primary-subtle rounded-4 mb-3 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm"
            >
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white rounded-pill px-2.5 py-1 fs-12 fw-bold">
                  {{ selectedUserIds.length }} Panitia Dipilih
                </span>
                <span class="fs-12 text-dark-emphasis fw-medium">Kelola izin khusus panitia secara serentak</span>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <button 
                  type="button" 
                  class="btn btn-sm btn-success rounded-pill px-3 fw-semibold shadow-sm d-flex align-items-center gap-1"
                  @click="openBulkUserModal('grant')"
                >
                  <i class="ri-add-line"></i>
                  <span>Beri Izin Khusus</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm btn-danger rounded-pill px-3 fw-semibold shadow-sm d-flex align-items-center gap-1"
                  @click="openBulkUserModal('revoke')"
                >
                  <i class="ri-forbid-line"></i>
                  <span>Blokir Izin Massal</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-semibold d-flex align-items-center gap-1"
                  @click="handleBulkResetAllUsers"
                >
                  <i class="ri-restart-line"></i>
                  <span>Reset ke Default Peran</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm btn-light border rounded-pill px-3"
                  @click="selectedUserIds = []"
                >
                  Batal Pilih
                </button>
              </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive rounded-4 border">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-12 text-uppercase">
                  <tr>
                    <th class="ps-3 py-3" style="width: 44px;">
                      <input 
                        type="checkbox" 
                        class="form-check-input m-0 cursor-pointer" 
                        :checked="isAllUsersSelected" 
                        @change="toggleSelectAllUsers" 
                        title="Pilih / Batalkan Semua Panitia"
                      />
                    </th>
                    <th>Nama &amp; NIP Panitia</th>
                    <th>Email</th>
                    <th>Peran Utama</th>
                    <th>Status Hak Akses</th>
                    <th class="text-end pe-3">Aksi</th>
                  </tr>
                </thead>
                <tbody class="fs-12">
                  <tr 
                    v-for="user in filteredUsers" 
                    :key="user.id"
                    :class="{ 'table-active': selectedUserIds.includes(user.id) }"
                  >
                    <td class="ps-3 py-3">
                      <input 
                        type="checkbox" 
                        class="form-check-input m-0 cursor-pointer" 
                        :checked="selectedUserIds.includes(user.id)" 
                        @change="toggleUserSelection(user.id)" 
                      />
                    </td>
                    <td>
                      <div class="fw-bold text-dark-emphasis">{{ user.name }}</div>
                      <small class="text-muted font-monospace">{{ user.nip ? `NIP: ${user.nip}` : 'NIP: -' }}</small>
                    </td>
                    <td>
                      <span class="text-muted">{{ user.email }}</span>
                    </td>
                    <td>
                      <div class="d-flex flex-column align-items-start gap-1">
                        <span 
                          class="badge rounded-pill px-2.5 py-1 fs-11 fw-semibold d-inline-flex align-items-center gap-1.5"
                          :class="getRoleBadgeClass(user.role)"
                        >
                          <i :class="getRoleIcon(user.role)"></i>
                          <span>{{ getRoleLabel(user.role) }}</span>
                        </span>

                        <!-- Coordinated competitions badge if any -->
                        <div v-if="user.coordinated_competitions && user.coordinated_competitions.length > 0" class="d-flex flex-wrap gap-1 mt-0.5">
                          <span 
                            v-for="comp in user.coordinated_competitions" 
                            :key="comp.id"
                            class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-10 px-2 py-0.5 d-inline-flex align-items-center gap-1"
                            :title="`Koordinator Cabang Lomba: ${comp.title}`"
                          >
                            <i class="ri-medal-fill text-warning"></i>
                            <span>{{ comp.title }}</span>
                          </span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span 
                        v-if="user.custom_permissions && Object.keys(user.custom_permissions).length > 0"
                        class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 fs-11 fw-semibold d-inline-flex align-items-center gap-1"
                      >
                        <i class="ri-tools-line"></i>
                        <span>{{ Object.keys(user.custom_permissions).length }} Izin Khusus</span>
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
                    <td colspan="6" class="text-center py-4 text-muted">
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
              <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2 mb-1">
                <i class="ri-user-settings-line text-primary fs-18"></i>
                <span>Hak Akses Khusus: {{ selectedUser.name }}</span>
              </h5>
              <div class="fs-11 text-muted d-flex align-items-center gap-2 flex-wrap">
                <span>Peran Utama:</span>
                <span 
                  class="badge rounded-pill px-2.5 py-0.5 fs-11 fw-semibold d-inline-flex align-items-center gap-1"
                  :class="getRoleBadgeClass(selectedUser.role)"
                >
                  <i :class="getRoleIcon(selectedUser.role)"></i>
                  <span>{{ getRoleLabel(selectedUser.role) }}</span>
                </span>
                <span class="mx-0.5">•</span>
                <span>Email: {{ selectedUser.email }}</span>
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
            
            <!-- Quick Role Adjustment Card inside Modal -->
            <div class="card border rounded-3 mb-3.5 shadow-xs bg-body">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                  <div class="d-flex align-items-center gap-1.5">
                    <i class="ri-shield-user-line text-primary fs-15"></i>
                    <h6 class="fs-12 fw-bold text-dark-emphasis mb-0">Peran Utama &amp; Penugasan Panitia</h6>
                  </div>
                  <span class="fs-11 text-muted">Sesuaikan peran struktural panitia ini</span>
                </div>

                <div class="row g-2 align-items-end">
                  <div :class="selectedUserRole === 'koordinator_lomba' ? 'col-md-5' : 'col-md-8'">
                    <label class="form-label fs-11 fw-semibold mb-1 text-dark-emphasis">Pilih Peran Utama</label>
                    <CustomSelect
                      v-model="selectedUserRole"
                      :options="roleDropdownOptions"
                      placeholder="Pilih Peran"
                      size="sm"
                    />
                  </div>

                  <div v-if="selectedUserRole === 'koordinator_lomba'" class="col-md-4">
                    <label class="form-label fs-11 fw-semibold mb-1 text-dark-emphasis">Cabang Lomba yang Dinaungi</label>
                    <CustomSelect
                      v-model="selectedUserCompId"
                      :options="compDropdownOptions"
                      placeholder="Pilih Lomba"
                      size="sm"
                    />
                  </div>

                  <div :class="selectedUserRole === 'koordinator_lomba' ? 'col-md-3' : 'col-md-4'">
                    <button
                      type="button"
                      class="btn btn-sm btn-primary w-100 rounded-pill fw-semibold d-inline-flex align-items-center justify-content-center gap-1"
                      :disabled="isSavingUserRole || (selectedUserRole === selectedUser.role && (selectedUserRole !== 'koordinator_lomba' || selectedUserCompId === (selectedUser.competition_id || '')))"
                      @click="saveUserPrimaryRole"
                    >
                      <span v-if="isSavingUserRole" class="spinner-border spinner-border-sm"></span>
                      <i v-else class="ri-check-line"></i>
                      <span>Simpan Peran</span>
                    </button>
                  </div>
                </div>

                <!-- Coordinated Competitions Info -->
                <div v-if="selectedUser.coordinated_competitions && selectedUser.coordinated_competitions.length > 0" class="mt-2.5 pt-2 border-top d-flex align-items-center gap-2 flex-wrap fs-11">
                  <span class="text-muted fw-medium"><i class="ri-medal-fill text-warning me-1"></i>Penugasan Koordinator:</span>
                  <span 
                    v-for="c in selectedUser.coordinated_competitions" 
                    :key="c.id"
                    class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-0.5"
                  >
                    {{ c.title }}
                  </span>
                </div>
              </div>
            </div>

            <div class="alert alert-info py-2.5 px-3 fs-11 rounded-3 mb-3 d-flex align-items-start gap-2 border border-info-subtle">
              <i class="ri-information-line fs-15 text-primary flex-shrink-0 mt-0.5"></i>
              <div>
                <strong>Panduan Otorisasi Khusus (Override):</strong><br>
                <span class="badge bg-secondary text-white me-1">Ikuti Peran</span> Mengikuti izin default peran panitia.<br>
                <span class="badge bg-success text-white me-1">Izinkan</span> Memberikan hak akses khusus meskipun perannya tidak memilikinya.<br>
                <span class="badge bg-danger text-white me-1">Larang</span> Mencabut hak akses meskipun peran utamanya mengizinkannya.
              </div>
            </div>

            <!-- Modal Search Input -->
            <div class="position-relative mb-3.5">
              <input 
                v-model="userPermSearch" 
                type="text" 
                class="form-control form-control-sm rounded-pill ps-4 pe-4 bg-light-subtle" 
                placeholder="Cari izin modul khusus untuk panitia ini..."
              />
              <i class="ri-search-line position-absolute top-50 translate-middle-y text-muted" style="left: 12px; font-size: 13px;"></i>
              <button 
                v-if="userPermSearch" 
                type="button" 
                class="btn btn-link btn-sm position-absolute top-50 translate-middle-y text-muted p-0" 
                style="right: 12px;"
                @click="userPermSearch = ''"
              >
                <i class="ri-close-circle-fill"></i>
              </button>
            </div>

            <!-- Permission Groups for User -->
            <div class="d-flex flex-column gap-3">
              <div 
                v-for="(perms, groupName) in filteredModalPermissionsGrouped" 
                :key="groupName"
                class="border rounded-3 p-3 bg-light-subtle"
              >
                <h6 class="fs-12 fw-bold text-dark-emphasis mb-2.5 d-flex align-items-center gap-1.5">
                  <i class="ri-folder-keyhole-line text-primary"></i>
                  <span>{{ groupName }}</span>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-10 px-2 ms-auto">
                    {{ perms.length }} Izin
                  </span>
                </h6>

                <div class="d-flex flex-column gap-2">
                  <div 
                    v-for="perm in perms" 
                    :key="perm.id"
                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between p-2.5 rounded-3 border bg-card gap-2"
                  >
                    <div>
                      <div class="fs-12 fw-semibold text-dark-emphasis">{{ perm.label }}</div>
                      <small class="text-muted font-monospace fs-10 d-block">{{ perm.name }}</small>
                      
                      <!-- Default Role Indicator -->
                      <div class="d-flex align-items-center gap-1.5 mt-1">
                        <span 
                          v-if="doesRoleHavePerm(perm.name)" 
                          class="badge bg-success-subtle text-success border border-success-subtle fs-10 fw-normal rounded-pill"
                        >
                          <i class="ri-check-line me-0.5"></i>Bawaan Peran: Aktif
                        </span>
                        <span 
                          v-else 
                          class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-10 fw-normal rounded-pill"
                        >
                          <i class="ri-close-line me-0.5"></i>Bawaan Peran: Non-aktif
                        </span>

                        <span 
                          v-if="getUserPermState(perm.name) !== 'inherit'" 
                          class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-10 fw-semibold rounded-pill"
                        >
                          Override: {{ getUserPermState(perm.name) === 'granted' ? 'Diizinkan' : 'Dilarang' }}
                        </span>
                      </div>
                    </div>

                    <!-- 3-Way Selector Pills -->
                    <div class="btn-group btn-group-sm rounded-pill border p-0.5 bg-light-subtle flex-shrink-0" role="group">
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2.5 py-1 fs-11 transition-all"
                        :class="getUserPermState(perm.name) === 'inherit' ? 'btn-secondary text-white shadow-sm fw-semibold' : 'btn-light border-0 text-muted'"
                        @click="setUserPermState(perm.name, 'inherit')"
                        title="Mengikuti izin default peran"
                      >
                        Ikuti Peran
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2.5 py-1 fs-11 transition-all"
                        :class="getUserPermState(perm.name) === 'granted' ? 'btn-success text-white shadow-sm fw-semibold' : 'btn-light border-0 text-muted'"
                        @click="setUserPermState(perm.name, 'granted')"
                        title="Beri izin khusus"
                      >
                        Izinkan
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs rounded-pill px-2.5 py-1 fs-11 transition-all"
                        :class="getUserPermState(perm.name) === 'denied' ? 'btn-danger text-white shadow-sm fw-semibold' : 'btn-light border-0 text-muted'"
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
    <!-- ================================================================= -->
    <!-- MODAL 3: KELOLA IZIN MASSAL MULTI-PERAN                           -->
    <!-- ================================================================= -->
    <div 
      v-if="isBulkRoleModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0, 0, 0, 0.6); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom p-3.5">
            <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2">
              <i class="ri-stack-line text-primary"></i>
              <span>Kelola Hak Akses Massal Multi-Peran</span>
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="isBulkRoleModalOpen = false"
              aria-label="Tutup"
            ></button>
          </div>

          <div class="modal-body p-3.5 d-flex flex-column gap-3.5">
            <!-- Action Selector -->
            <div>
              <label class="form-label fs-12 fw-semibold mb-1.5">Pilih Aksi Pengelolaan Massal:</label>
              <div class="d-flex flex-wrap gap-2">
                <button 
                  type="button" 
                  class="btn btn-sm rounded-pill px-3 py-1.5 fs-12 fw-semibold d-flex align-items-center gap-1.5 transition-all"
                  :class="bulkRoleAction === 'grant' ? 'btn-success text-white shadow-sm' : 'btn-light border text-muted'"
                  @click="bulkRoleAction = 'grant'"
                >
                  <i class="ri-add-circle-line"></i>
                  <span>Tambahkan Izin (Grant)</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm rounded-pill px-3 py-1.5 fs-12 fw-semibold d-flex align-items-center gap-1.5 transition-all"
                  :class="bulkRoleAction === 'revoke' ? 'btn-danger text-white shadow-sm' : 'btn-light border text-muted'"
                  @click="bulkRoleAction = 'revoke'"
                >
                  <i class="ri-indeterminate-circle-line"></i>
                  <span>Cabut Izin (Revoke)</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm rounded-pill px-3 py-1.5 fs-12 fw-semibold d-flex align-items-center gap-1.5 transition-all"
                  :class="bulkRoleAction === 'sync' ? 'btn-primary text-white shadow-sm' : 'btn-light border text-muted'"
                  @click="bulkRoleAction = 'sync'"
                >
                  <i class="ri-refresh-line"></i>
                  <span>Selaraskan Seluruh Izin (Sync / Replace)</span>
                </button>
              </div>
              <small class="text-muted fs-11 mt-1 d-block">
                {{ bulkRoleAction === 'grant' ? 'Menambahkan izin yang dipilih ke dalam peran sasaran tanpa menghapus izin yang sudah ada sebelumnya.' : (bulkRoleAction === 'revoke' ? 'Mencabut izin yang dipilih dari seluruh peran sasaran.' : 'Mengganti seluruh susunan izin peran sasaran secara total hanya dengan daftar yang dipilih di bawah.') }}
              </small>
            </div>

            <!-- Target Roles Checkboxes -->
            <div class="p-3 rounded-4 bg-light-subtle border">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 fw-bold text-dark-emphasis">Pilih Peran Sasaran ({{ bulkRoleSelectedRoleIds.length }} Dipilih):</span>
                <button 
                  type="button" 
                  class="btn btn-link btn-xs p-0 text-primary text-decoration-none fw-semibold"
                  @click="bulkRoleSelectedRoleIds = bulkRoleSelectedRoleIds.length === roles.filter(r => r.name !== 'super_admin').length ? [] : roles.filter(r => r.name !== 'super_admin').map(r => r.id)"
                >
                  {{ bulkRoleSelectedRoleIds.length === roles.filter(r => r.name !== 'super_admin').length ? 'Batalkan Semua' : 'Pilih Semua Peran' }}
                </button>
              </div>
              <div class="d-flex flex-wrap gap-2">
                <label 
                  v-for="r in roles.filter(role => role.name !== 'super_admin')" 
                  :key="r.id"
                  class="btn btn-sm rounded-pill px-2.5 py-1 fs-12 border cursor-pointer d-flex align-items-center gap-1.5"
                  :class="bulkRoleSelectedRoleIds.includes(r.id) ? 'btn-primary-subtle text-primary border-primary' : 'btn-light border text-muted'"
                >
                  <input 
                    type="checkbox" 
                    class="form-check-input m-0 cursor-pointer" 
                    :value="r.id" 
                    v-model="bulkRoleSelectedRoleIds" 
                  />
                  <span>{{ r.label }}</span>
                </label>
              </div>
            </div>

            <!-- Permission Selection -->
            <div>
              <div class="d-flex align-items-center justify-content-between mb-2">
                <label class="form-label fs-12 fw-semibold mb-0">Pilih Hak Akses yang Diproses ({{ bulkRoleSelectedPermIds.length }} Izin):</label>
                <div class="d-flex gap-2">
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-info rounded-pill px-2.5"
                    @click="bulkRoleSelectedPermIds = allPermissions.filter(p => p.name.endsWith('.view') && !p.name.startsWith('settings.')).map(p => p.id)"
                  >
                    <i class="ri-eye-line me-1"></i>Hanya Lihat Saja
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-primary rounded-pill px-2.5"
                    @click="bulkRoleSelectedPermIds = allPermissions.map(p => p.id)"
                  >
                    Pilih Semua
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-secondary rounded-pill px-2.5"
                    @click="bulkRoleSelectedPermIds = []"
                  >
                    Kosongkan
                  </button>
                </div>
              </div>

              <div class="d-flex flex-column gap-3" style="max-height: 380px; overflow-y: auto;">
                <div 
                  v-for="(perms, grpName) in permissionsGrouped" 
                  :key="grpName"
                  class="card border rounded-3 shadow-none p-2.5 bg-card"
                >
                  <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                    <span class="fs-12 fw-bold text-dark-emphasis">{{ grpName }}</span>
                    <div class="d-flex gap-1">
                      <button 
                        type="button" 
                        class="btn btn-link btn-xs p-0 text-primary text-decoration-none"
                        @click="toggleBulkRoleGroup(perms, true)"
                      >
                        Pilih Kategori
                      </button>
                      <span class="text-muted">|</span>
                      <button 
                        type="button" 
                        class="btn btn-link btn-xs p-0 text-secondary text-decoration-none"
                        @click="toggleBulkRoleGroup(perms, false)"
                      >
                        Batal
                      </button>
                    </div>
                  </div>
                  <div class="row g-2">
                    <div v-for="p in perms" :key="p.id" class="col-md-6">
                      <div 
                        class="p-2 rounded-2 border d-flex align-items-start gap-2 cursor-pointer transition-all"
                        :class="bulkRoleSelectedPermIds.includes(p.id) ? 'border-primary bg-primary-subtle' : 'bg-light-subtle'"
                        @click="toggleBulkRolePermission(p.id)"
                      >
                        <input 
                          type="checkbox" 
                          class="form-check-input mt-0.5 cursor-pointer" 
                          :checked="bulkRoleSelectedPermIds.includes(p.id)"
                        />
                        <div class="flex-grow-1">
                          <div class="d-flex align-items-center justify-content-between gap-1">
                            <span class="fs-11 fw-semibold text-dark-emphasis">{{ p.label }}</span>
                            <span 
                              class="badge rounded-pill fs-9 px-1 py-0.5"
                              :class="getPermissionKindBadge(p.name).class"
                            >
                              {{ getPermissionKindBadge(p.name).label }}
                            </span>
                          </div>
                          <small class="text-muted font-monospace fs-10 d-block">{{ p.name }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-light border rounded-pill px-3" 
              @click="isBulkRoleModalOpen = false"
            >
              Batal
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold"
              :disabled="isSubmittingBulkRole || bulkRoleSelectedRoleIds.length === 0 || bulkRoleSelectedPermIds.length === 0"
              @click="submitBulkRole"
            >
              <span v-if="isSubmittingBulkRole" class="spinner-border spinner-border-sm me-1"></span>
              <span>{{ isSubmittingBulkRole ? 'Menerapkan...' : 'Terapkan Hak Akses Massal' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 4: SALIN HAK AKSES DARI PERAN LAIN                          -->
    <!-- ================================================================= -->
    <div 
      v-if="isCopyRoleModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0, 0, 0, 0.6); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom p-3.5">
            <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2">
              <i class="ri-file-copy-line text-primary"></i>
              <span>Salin Susunan Hak Akses Antar Peran</span>
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="isCopyRoleModalOpen = false"
              aria-label="Tutup"
            ></button>
          </div>

          <div class="modal-body p-4 d-flex flex-column gap-3.5">
            <!-- Source Role Selector (CustomSelect) -->
            <div>
              <label class="form-label fs-12 fw-semibold mb-1">Peran Sumber Acuan (Source):</label>
              <CustomSelect
                v-model="copySourceRoleId"
                :options="copySourceOptions"
                placeholder="Pilih Peran Acuan"
              />
              <small class="text-muted fs-11 mt-1 d-block">
                Seluruh izin yang aktif pada peran acuan ini akan diduplikasi ke peran target.
              </small>
            </div>

            <!-- Target Roles (Checkboxes) -->
            <div>
              <div class="d-flex align-items-center justify-content-between mb-1.5">
                <label class="form-label fs-12 fw-semibold mb-0">Pilih Peran Target (Tujuan Salin):</label>
                <button 
                  type="button" 
                  class="btn btn-link btn-xs p-0 text-primary text-decoration-none"
                  @click="copyTargetRoleIds = copyTargetRoleIds.length === roles.filter(r => r.id !== copySourceRoleId && r.name !== 'super_admin').length ? [] : roles.filter(r => r.id !== copySourceRoleId && r.name !== 'super_admin').map(r => r.id)"
                >
                  {{ copyTargetRoleIds.length === roles.filter(r => r.id !== copySourceRoleId && r.name !== 'super_admin').length ? 'Batalkan' : 'Pilih Semua Target' }}
                </button>
              </div>

              <div class="d-flex flex-column gap-1.5 p-3 rounded-4 bg-light-subtle border">
                <label 
                  v-for="r in roles.filter(role => role.id !== copySourceRoleId && role.name !== 'super_admin')" 
                  :key="r.id"
                  class="form-check m-0 d-flex align-items-center gap-2 cursor-pointer p-1 rounded-2"
                >
                  <input 
                    type="checkbox" 
                    class="form-check-input m-0 cursor-pointer" 
                    :value="r.id" 
                    v-model="copyTargetRoleIds" 
                  />
                  <span class="fs-12 text-dark-emphasis fw-medium">{{ r.label }}</span>
                  <span class="badge rounded-pill fs-10 ms-auto" :class="getRoleBadgeClass(r.name, r.badge_color)">
                    {{ r.name === 'super_admin' ? allPermissions.length : (matrixState[r.id]?.length || 0) }} Izin saat ini
                  </span>
                </label>
              </div>
            </div>

            <div class="alert alert-warning py-2.5 px-3 rounded-3 fs-11 mb-0 border border-warning-subtle">
              <i class="ri-alert-line me-1"></i>
              Izin pada peran target yang dipilih akan ditimpa dan diselaraskan penuh dengan peran sumber acuan.
            </div>
          </div>

          <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-light border rounded-pill px-3" 
              @click="isCopyRoleModalOpen = false"
            >
              Batal
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold"
              :disabled="isSubmittingCopyRole || !copySourceRoleId || copyTargetRoleIds.length === 0"
              @click="submitCopyRole"
            >
              <span v-if="isSubmittingCopyRole" class="spinner-border spinner-border-sm me-1"></span>
              <span>{{ isSubmittingCopyRole ? 'Menyalin...' : 'Terapkan Salin Izin' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 5: KELOLA OVERRIDE MASSAL PANITIA (BULK USER OVERRIDE)      -->
    <!-- ================================================================= -->
    <div 
      v-if="isBulkUserModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0, 0, 0, 0.6); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-bottom p-3.5">
            <div>
              <h5 class="modal-title fs-15 fw-bold d-flex align-items-center gap-2 mb-0.5">
                <i class="ri-shield-user-line text-primary"></i>
                <span>{{ bulkUserAction === 'grant' ? 'Beri Izin Khusus Serentak' : (bulkUserAction === 'revoke' ? 'Blokir Izin Spesifik Serentak' : 'Hapus Override Izin Tertentu') }}</span>
              </h5>
              <small class="text-muted fs-11">
                Akan diterapkan pada <strong>{{ selectedUserIds.length }} panitia</strong> terpilih.
              </small>
            </div>
            <button 
              type="button" 
              class="btn-close" 
              @click="isBulkUserModalOpen = false"
              aria-label="Tutup"
            ></button>
          </div>

          <div class="modal-body p-3.5 d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fs-12 fw-semibold text-dark-emphasis">Pilih Hak Akses ({{ bulkUserPermNames.length }} Dipilih):</span>
              <div class="d-flex gap-2">
                <button 
                  type="button" 
                  class="btn btn-xs btn-outline-info rounded-pill px-2.5"
                  @click="bulkUserPermNames = allPermissions.filter(p => p.name.endsWith('.view') && !p.name.startsWith('settings.')).map(p => p.name)"
                >
                  <i class="ri-eye-line me-1"></i>Hanya Lihat Saja
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs btn-outline-primary rounded-pill px-2.5"
                  @click="bulkUserPermNames = allPermissions.map(p => p.name)"
                >
                  Pilih Semua
                </button>
                <button 
                  type="button" 
                  class="btn btn-xs btn-outline-secondary rounded-pill px-2.5"
                  @click="bulkUserPermNames = []"
                >
                  Kosongkan
                </button>
              </div>
            </div>

            <div class="d-flex flex-column gap-3" style="max-height: 420px; overflow-y: auto;">
              <div 
                v-for="(perms, grpName) in permissionsGrouped" 
                :key="grpName"
                class="card border rounded-3 shadow-none p-2.5 bg-card"
              >
                <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                  <span class="fs-12 fw-bold text-dark-emphasis">{{ grpName }}</span>
                  <div class="d-flex gap-1">
                    <button 
                      type="button" 
                      class="btn btn-link btn-xs p-0 text-primary text-decoration-none"
                      @click="toggleBulkUserGroup(perms, true)"
                    >
                      Pilih Semua
                    </button>
                    <span class="text-muted">|</span>
                    <button 
                      type="button" 
                      class="btn btn-link btn-xs p-0 text-secondary text-decoration-none"
                      @click="toggleBulkUserGroup(perms, false)"
                    >
                      Batal
                    </button>
                  </div>
                </div>
                <div class="row g-2">
                  <div v-for="p in perms" :key="p.name" class="col-md-6">
                    <div 
                      class="p-2 rounded-2 border d-flex align-items-start gap-2 cursor-pointer transition-all"
                      :class="bulkUserPermNames.includes(p.name) ? 'border-primary bg-primary-subtle' : 'bg-light-subtle'"
                      @click="toggleBulkUserPerm(p.name)"
                    >
                      <input 
                        type="checkbox" 
                        class="form-check-input mt-0.5 cursor-pointer" 
                        :checked="bulkUserPermNames.includes(p.name)"
                      />
                      <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between gap-1">
                          <span class="fs-11 fw-semibold text-dark-emphasis">{{ p.label }}</span>
                          <span 
                            class="badge rounded-pill fs-9 px-1 py-0.5"
                            :class="getPermissionKindBadge(p.name).class"
                          >
                            {{ getPermissionKindBadge(p.name).label }}
                          </span>
                        </div>
                        <small class="text-muted font-monospace fs-10 d-block">{{ p.name }}</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-top p-3 d-flex justify-content-end gap-2">
            <button 
              type="button" 
              class="btn btn-sm btn-light border rounded-pill px-3" 
              @click="isBulkUserModalOpen = false"
            >
              Batal
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary rounded-pill px-4 fw-semibold"
              :disabled="isSubmittingBulkUser || bulkUserPermNames.length === 0"
              @click="submitBulkUserOverrides"
            >
              <span v-if="isSubmittingBulkUser" class="spinner-border spinner-border-sm me-1"></span>
              <span>{{ isSubmittingBulkUser ? 'Menerapkan...' : 'Terapkan Izin Serentak' }}</span>
            </button>
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
  background-color: rgba(64, 81, 137, 0.03);
}

.permission-item-box.is-active {
  border-color: rgba(10, 179, 156, 0.5) !important;
  background-color: rgba(10, 179, 156, 0.05);
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

/* High-Contrast Standardized Role Badges (WCAG AA Compliant in Light & Dark Mode) */
.badge-role-danger {
  background-color: #fee2e2 !important;
  color: #991b1b !important;
  border-color: #fca5a5 !important;
}
[data-bs-theme="dark"] .badge-role-danger {
  background-color: rgba(239, 68, 68, 0.18) !important;
  color: #fca5a5 !important;
  border-color: rgba(239, 68, 68, 0.4) !important;
}

.badge-role-primary {
  background-color: #dbeafe !important;
  color: #1e40af !important;
  border-color: #93c5fd !important;
}
[data-bs-theme="dark"] .badge-role-primary {
  background-color: rgba(59, 130, 246, 0.18) !important;
  color: #93c5fd !important;
  border-color: rgba(59, 130, 246, 0.4) !important;
}

.badge-role-purple {
  background-color: #f3e8ff !important;
  color: #6b21a8 !important;
  border-color: #d8b4fe !important;
}
[data-bs-theme="dark"] .badge-role-purple {
  background-color: rgba(168, 85, 247, 0.18) !important;
  color: #d8b4fe !important;
  border-color: rgba(168, 85, 247, 0.4) !important;
}

.badge-role-indigo {
  background-color: #e0e7ff !important;
  color: #3730a3 !important;
  border-color: #a5b4fc !important;
}
[data-bs-theme="dark"] .badge-role-indigo {
  background-color: rgba(99, 102, 241, 0.18) !important;
  color: #a5b4fc !important;
  border-color: rgba(99, 102, 241, 0.4) !important;
}

.badge-role-teal {
  background-color: #ccfbf1 !important;
  color: #115e59 !important;
  border-color: #5eead4 !important;
}
[data-bs-theme="dark"] .badge-role-teal {
  background-color: rgba(20, 184, 166, 0.18) !important;
  color: #5eead4 !important;
  border-color: rgba(20, 184, 166, 0.4) !important;
}

.badge-role-warning {
  background-color: #fef3c7 !important;
  color: #92400e !important;
  border-color: #fcd34d !important;
}
[data-bs-theme="dark"] .badge-role-warning {
  background-color: rgba(245, 158, 11, 0.18) !important;
  color: #fde047 !important;
  border-color: rgba(245, 158, 11, 0.4) !important;
}

.badge-role-amber {
  background-color: #fffbeb !important;
  color: #78350f !important;
  border-color: #fde68a !important;
}
[data-bs-theme="dark"] .badge-role-amber {
  background-color: rgba(217, 119, 6, 0.18) !important;
  color: #fde047 !important;
  border-color: rgba(217, 119, 6, 0.4) !important;
}

.badge-role-info {
  background-color: #e0f2fe !important;
  color: #075985 !important;
  border-color: #7dd3fc !important;
}
[data-bs-theme="dark"] .badge-role-info {
  background-color: rgba(14, 165, 233, 0.18) !important;
  color: #7dd3fc !important;
  border-color: rgba(14, 165, 233, 0.4) !important;
}

.badge-role-secondary {
  background-color: #f1f5f9 !important;
  color: #334155 !important;
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .badge-role-secondary {
  background-color: rgba(148, 163, 184, 0.18) !important;
  color: #cbd5e1 !important;
  border-color: rgba(148, 163, 184, 0.4) !important;
}

.badge-role-success {
  background-color: #dcfce7 !important;
  color: #166534 !important;
  border-color: #86efac !important;
}
[data-bs-theme="dark"] .badge-role-success {
  background-color: rgba(34, 197, 94, 0.18) !important;
  color: #86efac !important;
  border-color: rgba(34, 197, 94, 0.4) !important;
}

.badge-role-dark {
  background-color: #e2e8f0 !important;
  color: #0f172a !important;
  border-color: #94a3b8 !important;
}
[data-bs-theme="dark"] .badge-role-dark {
  background-color: rgba(255, 255, 255, 0.12) !important;
  color: #f8fafc !important;
  border-color: rgba(255, 255, 255, 0.25) !important;
}

/* Dark Mode Overrides for Containers */
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
