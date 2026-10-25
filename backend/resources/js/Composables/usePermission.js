import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Composable untuk pemeriksaan hak akses (Permissions & Roles) panitia di antarmuka Vue.
 */
export function usePermission() {
  const page = usePage();

  const user = computed(() => page.props.auth?.user || null);
  const isSuperAdmin = computed(() => Boolean(user.value?.is_super_admin || user.value?.role === 'super_admin'));
  const userRole = computed(() => user.value?.role || '');
  const userRoleLabel = computed(() => user.value?.role_label || user.value?.role || 'Panitia');
  const userRoleBadge = computed(() => user.value?.role_badge || 'primary');
  const permissions = computed(() => Array.isArray(user.value?.permissions) ? user.value.permissions : []);

  /**
   * Cek apakah pengguna memiliki izin tertentu.
   * Super Admin selalu mengembalikan true.
   */
  function can(permissionName) {
    if (isSuperAdmin.value) return true;
    if (!permissionName) return true;
    return permissions.value.includes(permissionName);
  }

  /**
   * Cek apakah pengguna memiliki minimal salah satu izin dari daftar.
   */
  function canAny(permissionNames = []) {
    if (isSuperAdmin.value) return true;
    if (!permissionNames || permissionNames.length === 0) return true;
    return permissionNames.some(p => permissions.value.includes(p));
  }

  /**
   * Cek apakah pengguna memiliki seluruh izin dalam daftar.
   */
  function canAll(permissionNames = []) {
    if (isSuperAdmin.value) return true;
    if (!permissionNames || permissionNames.length === 0) return true;
    return permissionNames.every(p => permissions.value.includes(p));
  }

  return {
    user,
    isSuperAdmin,
    userRole,
    userRoleLabel,
    userRoleBadge,
    permissions,
    can,
    canAny,
    canAll,
  };
}
