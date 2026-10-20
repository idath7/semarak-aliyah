<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  schools: { type: Object, required: true },
  metrics: { type: Object, default: () => ({}) },
  kecamatans: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const { success, error } = useToast();

// -----------------------------------------------------------------------------
// FILTER & SEARCH STATE
// -----------------------------------------------------------------------------
const searchInput = ref(props.filters.search || '');
const jenjangFilter = ref(props.filters.jenjang || '');
const statusFilter = ref(props.filters.status || '');
const kecamatanFilter = ref(props.filters.kecamatan || '');

function applyFilters() {
  router.get('/admin/sekolah', {
    search: searchInput.value || undefined,
    jenjang: jenjangFilter.value || undefined,
    status: statusFilter.value || undefined,
    kecamatan: kecamatanFilter.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  searchInput.value = '';
  jenjangFilter.value = '';
  statusFilter.value = '';
  kecamatanFilter.value = '';
  router.get('/admin/sekolah');
}

// -----------------------------------------------------------------------------
// MODAL STATE: CREATE & EDIT
// -----------------------------------------------------------------------------
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);

const defaultForm = {
  id: null,
  name: '',
  jenjang: 'SMP',
  status: 'Negeri',
  kecamatan: 'Balung',
  npsn: '',
  alamat: '',
  phone: '',
  email: '',
  principal_name: '',
  is_active: true,
};

const form = ref({ ...defaultForm });

function openCreateModal() {
  isEditing.value = false;
  form.value = { ...defaultForm };
  isFormModalOpen.value = true;
}

function openEditModal(school) {
  isEditing.value = true;
  form.value = {
    id: school.id,
    name: school.name,
    jenjang: school.jenjang,
    status: school.status,
    kecamatan: school.kecamatan,
    npsn: school.npsn || '',
    alamat: school.alamat || '',
    phone: school.phone || '',
    email: school.email || '',
    principal_name: school.principal_name || '',
    is_active: Boolean(school.is_active),
  };
  isFormModalOpen.value = true;
}

function closeFormModal() {
  isFormModalOpen.value = false;
}

function submitForm() {
  if (!form.value.name.trim()) {
    error('Nama sekolah/madrasah wajib diisi!');
    return;
  }

  isSubmitting.value = true;

  if (isEditing.value) {
    router.put(`/admin/sekolah/${form.value.id}`, form.value, {
      onSuccess: () => {
        closeFormModal();
        success('Data sekolah berhasil diperbarui.');
      },
      onError: (err) => {
        error(Object.values(err)[0] || 'Gagal menyimpan perubahan.');
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  } else {
    router.post('/admin/sekolah', form.value, {
      onSuccess: () => {
        closeFormModal();
        success('Sekolah baru berhasil ditambahkan.');
      },
      onError: (err) => {
        error(Object.values(err)[0] || 'Gagal menambahkan sekolah.');
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  }
}

// -----------------------------------------------------------------------------
// DELETE CONFIRMATION
// -----------------------------------------------------------------------------
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(school) {
  itemToDelete.value = school;
  isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
  isDeleteModalOpen.value = false;
  itemToDelete.value = null;
}

function executeDelete() {
  if (!itemToDelete.value) return;

  isDeleting.value = true;
  router.delete(`/admin/sekolah/${itemToDelete.value.id}`, {
    onSuccess: () => {
      closeDeleteModal();
      success('Sekolah berhasil dihapus.');
    },
    onError: () => {
      error('Gagal menghapus sekolah.');
    },
    onFinish: () => {
      isDeleting.value = false;
    }
  });
}

// -----------------------------------------------------------------------------
// SYNC DEFAULT DATASET
// -----------------------------------------------------------------------------
const isSyncing = ref(false);

function syncDefaults() {
  if (!confirm('Apakah Anda yakin ingin menyinkronkan ulang database dengan 146+ sekolah default Kabupaten Jember? Data yang sudah ada tidak akan hilang.')) {
    return;
  }

  isSyncing.value = true;
  router.post('/admin/sekolah/sync-defaults', {}, {
    onSuccess: () => {
      success('Database sekolah berhasil disinkronkan!');
    },
    onError: () => {
      error('Gagal menyinkronkan data sekolah.');
    },
    onFinish: () => {
      isSyncing.value = false;
    }
  });
}

// List of all 31 kecamatan in Kabupaten Jember for select options
const allJemberKecamatan = [
  'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 
  'Jenggawah', 'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 
  'Mayang', 'Mumbulsari', 'Panti', 'Pakusari', 'Patrang', 'Puger', 
  'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono', 'Sumberbaru', 
  'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari', 'Wuluhan'
];
</script>

<template>
  <VelzonLayout>
    <Head title="Database Sekolah & Madrasah Kabupaten Jember - Admin Panel" />

    <div class="container-fluid py-4">
      <!-- ============================================================== -->
      <!-- PAGE HEADER                                                    -->
      <!-- ============================================================== -->
      <div class="row mb-4 align-items-center">
        <div class="col">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm bg-success-subtle rounded-3 d-flex align-items-center justify-content-center text-success fs-20">
              <i class="ri-building-4-line"></i>
            </div>
            <div>
              <h4 class="fs-18 fw-bold mb-0 text-dark">Database Sekolah & Madrasah (SMP / MTs)</h4>
              <p class="text-muted fs-12 mb-0">Master database sekolah rujukan se-Kabupaten Jember & integrasi API pendaftaran online</p>
            </div>
          </div>
        </div>
        <div class="col-auto d-flex gap-2">
          <!-- Button Sync Defaults -->
          <button 
            type="button" 
            class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5"
            :disabled="isSyncing"
            @click="syncDefaults"
            title="Sinkronkan dengan master dataset 146+ SMP/MTs Kabupaten Jember"
          >
            <i class="ri-refresh-line" :class="{ 'ri-spin': isSyncing }"></i>
            <span class="d-none d-sm-inline">{{ isSyncing ? 'Menyinkronkan...' : 'Sinkron Master Jember' }}</span>
          </button>

          <!-- Button Tambah Sekolah -->
          <button 
            type="button" 
            class="btn btn-sm btn-success d-flex align-items-center gap-1.5 shadow-sm"
            @click="openCreateModal"
          >
            <i class="ri-add-line fs-14"></i>
            <span>Tambah Sekolah</span>
          </button>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- SUMMARY METRIC CARDS                                           -->
      <!-- ============================================================== -->
      <div class="row g-3 mb-4">
        <!-- 1. Total Sekolah -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 text-muted fw-medium">Total Terdata</span>
                <div class="avatar-xs bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center">
                  <i class="ri-building-line fs-15"></i>
                </div>
              </div>
              <h4 class="fs-20 fw-bold mb-0 text-dark">{{ metrics.total_schools || 0 }}</h4>
              <small class="text-muted fs-10">Lembaga SMP & MTs</small>
            </div>
          </div>
        </div>

        <!-- 2. Jenjang SMP -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 text-muted fw-medium">Jenjang SMP</span>
                <div class="avatar-xs bg-info-subtle text-info rounded-2 d-flex align-items-center justify-content-center">
                  <i class="ri-book-read-line fs-15"></i>
                </div>
              </div>
              <h4 class="fs-20 fw-bold mb-0 text-info">{{ metrics.total_smp || 0 }}</h4>
              <small class="text-muted fs-10">Sekolah Menengah Pertama</small>
            </div>
          </div>
        </div>

        <!-- 3. Jenjang MTs -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 text-muted fw-medium">Jenjang MTs</span>
                <div class="avatar-xs bg-success-subtle text-success rounded-2 d-flex align-items-center justify-content-center">
                  <i class="ri-medal-line fs-15"></i>
                </div>
              </div>
              <h4 class="fs-20 fw-bold mb-0 text-success">{{ metrics.total_mts || 0 }}</h4>
              <small class="text-muted fs-10">Madrasah Tsanawiyah</small>
            </div>
          </div>
        </div>

        <!-- 4. Status Negeri -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 text-muted fw-medium">Status Negeri</span>
                <div class="avatar-xs bg-warning-subtle text-warning rounded-2 d-flex align-items-center justify-content-center">
                  <i class="ri-government-line fs-15"></i>
                </div>
              </div>
              <h4 class="fs-20 fw-bold mb-0 text-warning">{{ metrics.total_negeri || 0 }}</h4>
              <small class="text-muted fs-10">SMPN & MTsN</small>
            </div>
          </div>
        </div>

        <!-- 5. Status Swasta -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 text-muted fw-medium">Status Swasta</span>
                <div class="avatar-xs bg-purple-subtle text-purple rounded-2 d-flex align-items-center justify-content-center">
                  <i class="ri-community-line fs-15"></i>
                </div>
              </div>
              <h4 class="fs-20 fw-bold mb-0 text-dark">{{ metrics.total_swasta || 0 }}</h4>
              <small class="text-muted fs-10">Madrasah & Yayasan</small>
            </div>
          </div>
        </div>

        <!-- 6. Cakupan Wilayah -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 text-muted fw-medium">Cakupan Wilayah</span>
                <div class="avatar-xs bg-danger-subtle text-danger rounded-2 d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-line fs-15"></i>
                </div>
              </div>
              <h4 class="fs-20 fw-bold mb-0 text-danger">31</h4>
              <small class="text-muted fs-10">Kecamatan di Jember</small>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- FILTER & SEARCH BAR                                            -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
          <form @submit.prevent="applyFilters" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-12 col-md-4">
              <div class="position-relative">
                <input 
                  v-model="searchInput" 
                  type="text" 
                  class="form-control form-control-sm ps-4" 
                  placeholder="Cari nama sekolah, NPSN, atau alamat..."
                />
                <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-14"></i>
              </div>
            </div>

            <!-- Jenjang Filter -->
            <div class="col-6 col-md-2">
              <select v-model="jenjangFilter" class="form-select form-select-sm" @change="applyFilters">
                <option value="">Semua Jenjang</option>
                <option value="SMP">SMP</option>
                <option value="MTs">MTs</option>
              </select>
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
              <select v-model="statusFilter" class="form-select form-select-sm" @change="applyFilters">
                <option value="">Semua Status</option>
                <option value="Negeri">Negeri</option>
                <option value="Swasta">Swasta</option>
              </select>
            </div>

            <!-- Kecamatan Filter -->
            <div class="col-6 col-md-3">
              <select v-model="kecamatanFilter" class="form-select form-select-sm" @change="applyFilters">
                <option value="">Semua Kecamatan (31)</option>
                <option v-for="kec in allJemberKecamatan" :key="kec" :value="kec">
                  Kecamatan {{ kec }}
                </option>
              </select>
            </div>

            <!-- Actions (Submit & Reset) -->
            <div class="col-6 col-md-1 d-flex gap-1">
              <button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
                <i class="ri-filter-3-line"></i>
              </button>
              <button type="button" class="btn btn-sm btn-light border w-100" title="Reset Filter" @click="resetFilters">
                <i class="ri-refresh-line"></i>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- DATA TABLE                                                     -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="ri-list-check-2 fs-18 text-success"></i>
            <h5 class="fs-15 fw-bold mb-0">Daftar Sekolah & Madrasah Terdaftar</h5>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-muted border px-2.5 py-1 fs-11">
              Total: {{ schools.total || 0 }} Lembaga
            </span>
            <a 
              href="/api/schools?all=1" 
              target="_blank" 
              class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1 rounded-pill"
              title="Lihat Raw JSON Endpoint API"
            >
              <i class="ri-code-s-slash-line"></i> Endpoint API
            </a>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light fs-12 text-muted text-uppercase">
              <tr>
                <th scope="col" style="width: 50px;" class="text-center">#</th>
                <th scope="col">Nama Lembaga & NPSN</th>
                <th scope="col">Jenjang</th>
                <th scope="col">Status</th>
                <th scope="col">Kecamatan & Alamat</th>
                <th scope="col">Kontak / Telepon</th>
                <th scope="col" class="text-center">Status</th>
                <th scope="col" class="text-end" style="width: 120px;">Aksi</th>
              </tr>
            </thead>
            <tbody class="fs-13">
              <tr v-if="!schools.data || schools.data.length === 0">
                <td colspan="8" class="text-center py-5 text-muted">
                  <i class="ri-search-line fs-32 text-secondary mb-2 d-block"></i>
                  <p class="mb-1 fw-semibold">Tidak ada data sekolah yang cocok dengan filter pencarian.</p>
                  <small class="text-muted">Coba ubah kata kunci atau klik reset filter.</small>
                </td>
              </tr>

              <tr v-for="(school, index) in schools.data" :key="school.id">
                <!-- Index -->
                <td class="text-center text-muted fs-12">
                  {{ ((schools.current_page - 1) * schools.per_page) + index + 1 }}
                </td>

                <!-- Nama & NPSN -->
                <td>
                  <div class="d-flex align-items-center gap-2.5">
                    <div 
                      class="avatar-xs rounded-2 d-flex align-items-center justify-content-center fw-bold fs-12"
                      :class="school.jenjang === 'MTs' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary'"
                    >
                      {{ school.jenjang }}
                    </div>
                    <div>
                      <span class="fw-semibold text-dark d-block">{{ school.name }}</span>
                      <small class="text-muted font-monospace fs-11">
                        NPSN: {{ school.npsn || '-' }}
                      </small>
                    </div>
                  </div>
                </td>

                <!-- Jenjang -->
                <td>
                  <span 
                    class="badge px-2 py-0.5 fs-11 border"
                    :class="school.jenjang === 'MTs' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-primary-subtle text-primary border-primary-subtle'"
                  >
                    {{ school.jenjang }}
                  </span>
                </td>

                <!-- Status -->
                <td>
                  <span 
                    class="badge px-2 py-0.5 fs-11 border"
                    :class="school.status === 'Negeri' ? 'bg-warning-subtle text-warning border-warning-subtle' : 'bg-secondary-subtle text-secondary border-secondary-subtle'"
                  >
                    {{ school.status }}
                  </span>
                </td>

                <!-- Kecamatan & Alamat -->
                <td>
                  <div class="d-flex align-items-center gap-1 text-dark fw-medium fs-12">
                    <i class="ri-map-pin-2-line text-danger fs-13"></i>
                    <span>Kec. {{ school.kecamatan }}</span>
                  </div>
                  <small class="text-muted truncate d-block fs-11" style="max-width: 250px;" :title="school.alamat">
                    {{ school.alamat || '-' }}
                  </small>
                </td>

                <!-- Kontak / Phone -->
                <td>
                  <div v-if="school.phone">
                    <a 
                      :href="getWaChatUrl(school.phone, `Halo ${school.name}, kami dari Panitia Semarak Aliyah: `)" 
                      target="_blank" 
                      class="badge bg-success-subtle text-success fs-10 text-decoration-none"
                      title="Hubungi WhatsApp"
                    >
                      <i class="ri-whatsapp-line align-middle me-0.5"></i> {{ school.phone }}
                    </a>
                  </div>
                  <span v-else class="text-muted fs-11">-</span>
                </td>

                <!-- Status Aktif -->
                <td class="text-center">
                  <span 
                    class="badge rounded-pill px-2 py-0.5 fs-10"
                    :class="school.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                  >
                    {{ school.is_active ? 'Aktif' : 'Non-aktif' }}
                  </span>
                </td>

                <!-- Aksi -->
                <td class="text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button 
                      type="button" 
                      class="btn btn-sm btn-light border py-1 px-2 text-primary"
                      title="Edit Data Sekolah"
                      @click="openEditModal(school)"
                    >
                      <i class="ri-edit-line"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-sm btn-light border py-1 px-2 text-danger"
                      title="Hapus Sekolah"
                      @click="confirmDelete(school)"
                    >
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Footer Pagination -->
        <div 
          v-if="schools.links && schools.links.length > 3" 
          class="card-footer bg-white border-top py-3 d-flex align-items-center justify-content-between flex-wrap gap-2"
        >
          <small class="text-muted fs-12">
            Menampilkan {{ schools.from || 0 }} sampai {{ schools.to || 0 }} dari {{ schools.total || 0 }} sekolah
          </small>

          <nav>
            <ul class="pagination pagination-sm mb-0">
              <li 
                v-for="(link, lIdx) in schools.links" 
                :key="lIdx"
                class="page-item"
                :class="{ 'active': link.active, 'disabled': !link.url }"
              >
                <Link 
                  v-if="link.url"
                  :href="link.url" 
                  class="page-link"
                  v-html="link.label"
                />
                <span v-else class="page-link" v-html="link.label"></span>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: ADD / EDIT SCHOOL                                       -->
    <!-- ============================================================== -->
    <div 
      v-if="isFormModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px);"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header bg-white border-bottom py-3">
            <h5 class="modal-title fs-15 fw-bold text-dark d-flex align-items-center gap-2">
              <i class="ri-building-4-line text-success"></i>
              {{ isEditing ? 'Edit Data Sekolah / Madrasah' : 'Tambah Sekolah / Madrasah Baru' }}
            </h5>
            <button type="button" class="btn-close" @click="closeFormModal"></button>
          </div>

          <form @submit.prevent="submitForm">
            <div class="modal-body p-4">
              <div class="row g-3">
                <!-- Nama Lembaga -->
                <div class="col-12">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Nama Sekolah / Madrasah <span class="text-danger">*</span>
                  </label>
                  <input 
                    v-model="form.name" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="Contoh: SMPN 1 Balung atau MTs Unggulan Wahid Hasyim"
                    required
                  />
                </div>

                <!-- Jenjang -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Jenjang Lembaga <span class="text-danger">*</span>
                  </label>
                  <select v-model="form.jenjang" class="form-select form-select-sm" required>
                    <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                    <option value="MTs">MTs (Madrasah Tsanawiyah)</option>
                  </select>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Status Sekolah <span class="text-danger">*</span>
                  </label>
                  <select v-model="form.status" class="form-select form-select-sm" required>
                    <option value="Negeri">Negeri</option>
                    <option value="Swasta">Swasta</option>
                  </select>
                </div>

                <!-- Kecamatan -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Kecamatan (Kabupaten Jember) <span class="text-danger">*</span>
                  </label>
                  <select v-model="form.kecamatan" class="form-select form-select-sm" required>
                    <option v-for="kec in allJemberKecamatan" :key="kec" :value="kec">
                      Kecamatan {{ kec }}
                    </option>
                  </select>
                </div>

                <!-- NPSN -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">NPSN (Nomor Pokok Sekolah Nasional)</label>
                  <input 
                    v-model="form.npsn" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="Contoh: 20581001"
                  />
                </div>

                <!-- Alamat Lengkap -->
                <div class="col-12">
                  <label class="form-label fs-12 fw-semibold text-dark">Alamat Lengkap</label>
                  <textarea 
                    v-model="form.alamat" 
                    rows="2" 
                    class="form-control form-control-sm" 
                    placeholder="Jalan, Desa/Kelurahan..."
                  ></textarea>
                </div>

                <!-- No Telepon / WA -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">Nomor Telepon / WhatsApp</label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-success"><i class="ri-whatsapp-line"></i></span>
                    <input 
                      v-model="form.phone" 
                      type="text" 
                      class="form-control" 
                      placeholder="081254211xxx"
                    />
                  </div>
                </div>

                <!-- Email Resmi -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark">Email Resmi Sekolah</label>
                  <input 
                    v-model="form.email" 
                    type="email" 
                    class="form-control form-control-sm" 
                    placeholder="sekolah@kemdikbud.go.id"
                  />
                </div>

                <!-- Status Aktif Checkbox -->
                <div class="col-12">
                  <div class="form-check form-switch">
                    <input 
                      id="schoolIsActive" 
                      v-model="form.is_active" 
                      class="form-check-input" 
                      type="checkbox"
                    />
                    <label class="form-check-label fs-12 text-dark" for="schoolIsActive">
                      Sekolah aktif (dapat dipilih pada saat pendaftaran online)
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-top py-2.5 px-4 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm btn-light border" @click="closeFormModal">
                Batal
              </button>
              <button type="submit" class="btn btn-sm btn-success" :disabled="isSubmitting">
                <i class="ri-save-line me-1"></i>
                {{ isSubmitting ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Sekolah') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: DELETE CONFIRMATION                                     -->
    <!-- ============================================================== -->
    <div 
      v-if="isDeleteModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px);"
    >
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-body p-4 text-center">
            <div class="avatar-md bg-danger-subtle text-danger rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fs-24">
              <i class="ri-delete-bin-line"></i>
            </div>
            <h5 class="fs-15 fw-bold text-dark mb-1">Hapus Data Sekolah?</h5>
            <p class="text-muted fs-12 mb-3">
              Apakah Anda yakin ingin menghapus <strong>{{ itemToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-sm btn-light border px-3" @click="closeDeleteModal">
                Batal
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-danger px-3" 
                :disabled="isDeleting" 
                @click="executeDelete"
              >
                {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>
