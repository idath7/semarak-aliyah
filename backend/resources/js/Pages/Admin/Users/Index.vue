<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
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
function toggleStatus(user) {
  if (user.id === props.currentUserId) {
    error('Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif.');
    return;
  }

  const actionText = user.is_active ? 'menonaktifkan' : 'mengaktifkan';
  if (!confirm(`Yakin ingin ${actionText} akun ${user.name}?`)) {
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
        <div class="col-12 col-md-5 text-md-end mt-3 mt-md-0">
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
              <select v-model="roleFilter" class="form-select form-select-sm" @change="applyFilters">
                <option value="">Semua Peran</option>
                <option v-for="(cfg, key) in roles" :key="key" :value="key">
                  {{ cfg.label }}
                </option>
              </select>
            </div>

            <!-- Division Filter -->
            <div class="col-6 col-md-3">
              <select v-model="divisionFilter" class="form-select form-select-sm" @change="applyFilters">
                <option value="">Semua Divisi Panitia</option>
                <option v-for="div in divisions" :key="div" :value="div">
                  {{ div }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
              <select v-model="statusFilter" class="form-select form-select-sm" @change="applyFilters">
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Non-aktif</option>
              </select>
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
                      <div class="d-flex align-items-center gap-1.5">
                        <span class="fw-semibold text-dark">{{ user.name }}</span>
                        <span v-if="user.id === currentUserId" class="badge bg-success-subtle text-success fs-10 px-1.5 py-0.5">
                          Anda
                        </span>
                      </div>
                      <small class="text-muted font-monospace fs-11">{{ user.email }}</small>
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
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Peran / Hak Akses (Role) <span class="text-danger">*</span>
                  </label>
                  <select 
                    v-model="form.role" 
                    class="form-select"
                    :class="{ 'is-invalid': formErrors.role }"
                    required
                  >
                    <option v-for="(cfg, key) in roles" :key="key" :value="key">
                      {{ cfg.label }} — {{ cfg.description }}
                    </option>
                  </select>
                  <div v-if="formErrors.role" class="invalid-feedback fs-11">
                    {{ formErrors.role }}
                  </div>
                </div>

                <!-- Divisi Panitia -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Divisi / Bidang Tugas
                  </label>
                  <select v-model="form.division" class="form-select">
                    <option value="">Pilih Divisi...</option>
                    <option v-for="div in divisions" :key="div" :value="div">
                      {{ div }}
                    </option>
                  </select>
                </div>

                <!-- Penugasan Lomba Khusus (Opsional) -->
                <div class="col-12 col-md-8">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Penugasan Cabang Lomba Khusus <span class="text-muted fw-normal">(Khusus Juri / Wasit / PJ Lomba)</span>
                  </label>
                  <select v-model="form.competition_id" class="form-select">
                    <option value="">Tidak ada / Penugasan Umum Seluruh Lomba</option>
                    <option v-for="comp in competitions" :key="comp.id" :value="comp.id">
                      {{ comp.title }} ({{ comp.category_label || comp.category }})
                    </option>
                  </select>
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
  </VelzonLayout>
</template>
