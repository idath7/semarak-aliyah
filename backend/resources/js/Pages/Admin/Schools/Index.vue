<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  schools: { type: Object, required: true },
  metrics: { type: Object, default: () => ({}) },
  kecamatans: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const { success, error } = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

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
// IMPORT CSV & EXPORT CSV
// -----------------------------------------------------------------------------
const isImportModalOpen = ref(false);
const importFileInput = ref(null);
const importFile = ref(null);
const isImporting = ref(false);

function openImportModal() {
  importFile.value = null;
  if (importFileInput.value) importFileInput.value.value = '';
  isImportModalOpen.value = true;
}

function closeImportModal() {
  isImportModalOpen.value = false;
  importFile.value = null;
}

function onFileSelect(e) {
  const files = e.target.files;
  if (files && files[0]) {
    importFile.value = files[0];
  }
}

function submitImport() {
  if (!importFile.value) {
    error('Pilih file CSV terlebih dahulu.');
    return;
  }

  isImporting.value = true;
  const formData = new FormData();
  formData.append('file', importFile.value);

  router.post('/admin/sekolah/import-csv', formData, {
    forceFormData: true,
    onSuccess: () => {
      closeImportModal();
      success('Data sekolah berhasil diimpor!');
    },
    onError: (err) => {
      error(Object.values(err)[0] || 'Gagal mengimpor file CSV.');
    },
    onFinish: () => {
      isImporting.value = false;
    }
  });
}

// -----------------------------------------------------------------------------
// TOGGLE ACTIVE STATUS
// -----------------------------------------------------------------------------
function toggleActiveStatus(school) {
  router.post(`/admin/sekolah/${school.id}/toggle-active`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Status ${school.name} berhasil diubah.`);
    },
    onError: () => {
      error('Gagal memperbarui status aktif.');
    }
  });
}

// -----------------------------------------------------------------------------
// CLEAN DATA MODAL STATE & HANDLERS
// -----------------------------------------------------------------------------
const isCleanModalOpen = ref(false);
const cleanScope = ref('all');
const isCleaning = ref(false);

function openCleanModal() {
  cleanScope.value = 'all';
  isCleanModalOpen.value = true;
}

function closeCleanModal() {
  isCleanModalOpen.value = false;
}

async function executeClean() {
  const scopeLabels = {
    all: 'seluruh data sekolah & madrasah hasil API/seeder',
    inactive: 'sekolah yang berstatus non-aktif',
    dummy: 'data uji coba / dummy'
  };

  const ok = await confirmDialog({
    title: 'Konfirmasi Pembersihan Data',
    message: `Apakah Anda yakin ingin menghapus ${scopeLabels[cleanScope.value]}? Tindakan ini tidak dapat dibatalkan. Data registrasi peserta yang sudah ada tetap aman.`,
    type: cleanScope.value === 'all' ? 'danger' : 'warning',
    confirmText: 'Ya, Bersihkan Sekarang',
    icon: 'ri-delete-bin-line'
  });

  if (!ok) return;

  isCleaning.value = true;
  router.post('/admin/sekolah/clean-invalid', { scope: cleanScope.value }, {
    onSuccess: () => {
      closeCleanModal();
      selectedIds.value = [];
      success('Pembersihan data sekolah berhasil!');
    },
    onError: (err) => {
      error(Object.values(err)[0] || 'Gagal membersihkan data.');
    },
    onFinish: () => {
      isCleaning.value = false;
    }
  });
}

// -----------------------------------------------------------------------------
// BULK SELECTION & BULK DELETE
// -----------------------------------------------------------------------------
const selectedIds = ref([]);

const isAllSelected = computed(() => {
  if (!props.schools.data || props.schools.data.length === 0) return false;
  return props.schools.data.every(s => selectedIds.value.includes(s.id));
});

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = props.schools.data.map(s => s.id);
  }
}

async function bulkDeleteSelected() {
  if (selectedIds.value.length === 0) return;

  const count = selectedIds.value.length;
  const ok = await confirmDialog({
    title: `Hapus ${count} Sekolah Terpilih?`,
    message: `Apakah Anda yakin ingin menghapus ${count} sekolah yang telah dicentang dari database?`,
    type: 'danger',
    confirmText: `Ya, Hapus ${count} Sekolah`,
    icon: 'ri-delete-bin-line'
  });

  if (!ok) return;

  router.post('/admin/sekolah/bulk-delete', { ids: selectedIds.value }, {
    onSuccess: () => {
      selectedIds.value = [];
      success(`${count} sekolah terpilih berhasil dihapus.`);
    },
    onError: () => {
      error('Gagal menghapus sekolah terpilih.');
    }
  });
}

// -----------------------------------------------------------------------------
// SYNC DEFAULT DATASET
// -----------------------------------------------------------------------------
const isSyncing = ref(false);

async function syncDefaults() {
  const ok = await confirmDialog({
    title: 'Sinkronisasi Master Sekolah?',
    message: 'Apakah Anda yakin ingin menyinkronkan ulang database dengan 146+ sekolah & madrasah resmi se-Kabupaten Jember? Data yang sudah ada tidak akan hilang.',
    type: 'info',
    confirmText: 'Ya, Sinkronkan',
    icon: 'ri-refresh-line',
  });

  if (!ok) {
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
        <div class="col-auto d-flex flex-wrap gap-2">
          <!-- Button Export CSV -->
          <a 
            href="/admin/sekolah/export-csv" 
            class="btn btn-sm btn-outline-success d-flex align-items-center gap-1.5"
            title="Download seluruh database sekolah dalam format spreadsheet CSV/Excel"
          >
            <i class="ri-file-excel-2-line"></i>
            <span class="d-none d-md-inline">Export CSV</span>
          </a>

          <!-- Button Import CSV -->
          <button 
            type="button" 
            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1.5"
            @click="openImportModal"
            title="Unggah spreadsheet CSV data resmi dari Dispendik/Kemenag"
          >
            <i class="ri-upload-2-line"></i>
            <span class="d-none d-md-inline">Import CSV</span>
          </button>

          <!-- Button Bersihkan Data -->
          <button 
            type="button" 
            class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1.5"
            @click="openCleanModal"
            title="Bersihkan / kosongkan database sekolah hasil API"
          >
            <i class="ri-magic-line"></i>
            <span class="d-none d-lg-inline">Bersihkan Data</span>
          </button>

          <!-- Button Sync Defaults -->
          <button 
            type="button" 
            class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5"
            :disabled="isSyncing"
            @click="syncDefaults"
            title="Sinkronkan dengan master dataset 146+ SMP/MTs Kabupaten Jember"
          >
            <i class="ri-refresh-line" :class="{ 'ri-spin': isSyncing }"></i>
            <span class="d-none d-sm-inline">{{ isSyncing ? 'Menyinkronkan...' : 'Sinkron Master' }}</span>
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
      <!-- DATA QUALITY & API NOTICE BANNER                              -->
      <!-- ============================================================== -->
      <div class="alert alert-info border-0 shadow-sm rounded-3 d-flex align-items-start gap-3 mb-4 p-3" style="background: rgba(14, 165, 233, 0.08); border-left: 4px solid #0284c7 !important;">
        <div class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5">
          <i class="ri-information-line fs-16"></i>
        </div>
        <div class="flex-grow-1 fs-12 text-dark">
          <strong class="d-block mb-1 fs-13 text-dark">💡 Panduan Validitas Data Sekolah & Madrasah:</strong>
          Data dari API publik pihak ketiga atau arsip Dapodik lama sering kali memiliki informasi yang kadaluarsa (sekolah dimerger/tutup) dan <strong>tidak mencakup Madrasah Tsanawiyah (MTs)</strong> karena MTs dikelola terpisah di sistem EMIS Kementerian Agama.
          Anda dapat memperbarui data secara presisi dengan tombol <strong>Import CSV</strong> menggunakan format resmi dari Dispendik/Kemenag, mengedit langsung data lembaga di tabel, atau mengaktifkan/menonaktifkan sekolah dengan saklar status.
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
      <!-- Bulk Action Bar -->
      <div 
        v-if="selectedIds.length > 0" 
        class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center justify-content-between p-3 mb-3"
      >
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-warning text-dark fs-12 px-2.5 py-1">{{ selectedIds.length }} Terpilih</span>
          <span class="fs-13 fw-semibold text-dark">Data sekolah telah dicentang untuk aksi massal</span>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-light border" @click="selectedIds = []">Batal Pilih</button>
          <button 
            type="button" 
            class="btn btn-sm btn-danger d-flex align-items-center gap-1 shadow-sm"
            @click="bulkDeleteSelected"
          >
            <i class="ri-delete-bin-line"></i>
            <span>Hapus ({{ selectedIds.length }}) Sekolah Terpilih</span>
          </button>
        </div>
      </div>

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
                <th scope="col" style="width: 40px;" class="text-center">
                  <input 
                    type="checkbox" 
                    class="form-check-input cursor-pointer"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                    title="Pilih semua di halaman ini"
                  />
                </th>
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
                <td colspan="9" class="text-center py-5 text-muted">
                  <i class="ri-search-line fs-32 text-secondary mb-2 d-block"></i>
                  <p class="mb-1 fw-semibold">Tidak ada data sekolah yang cocok dengan filter pencarian.</p>
                  <small class="text-muted">Coba ubah kata kunci atau klik reset filter.</small>
                </td>
              </tr>

              <tr v-for="(school, index) in schools.data" :key="school.id">
                <!-- Checkbox -->
                <td class="text-center">
                  <input 
                    type="checkbox" 
                    class="form-check-input cursor-pointer"
                    :value="school.id"
                    v-model="selectedIds"
                  />
                </td>

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
                  <button 
                    type="button"
                    class="btn btn-xs rounded-pill px-2.5 py-1 border d-inline-flex align-items-center gap-1 transition-all"
                    :class="school.is_active ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
                    :title="school.is_active ? 'Klik untuk menonaktifkan sekolah ini' : 'Klik untuk mengaktifkan sekolah ini'"
                    @click="toggleActiveStatus(school)"
                  >
                    <i :class="school.is_active ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'"></i>
                    <span class="fs-11 fw-medium">{{ school.is_active ? 'Aktif' : 'Non-aktif' }}</span>
                  </button>
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
      style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3 custom-modal-content">
          <div class="modal-header border-bottom py-3 px-4">
            <h5 class="modal-title fs-15 fw-bold custom-modal-title d-flex align-items-center gap-2">
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
                  <label class="form-label fs-12 fw-semibold custom-modal-label">
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
                  <label class="form-label fs-12 fw-semibold custom-modal-label">
                    Jenjang Lembaga <span class="text-danger">*</span>
                  </label>
                  <select v-model="form.jenjang" class="form-select form-select-sm" required>
                    <option value="SMP">SMP (Sekolah Menengah Pertama)</option>
                    <option value="MTs">MTs (Madrasah Tsanawiyah)</option>
                  </select>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold custom-modal-label">
                    Status Sekolah <span class="text-danger">*</span>
                  </label>
                  <select v-model="form.status" class="form-select form-select-sm" required>
                    <option value="Negeri">Negeri</option>
                    <option value="Swasta">Swasta</option>
                  </select>
                </div>

                <!-- Kecamatan -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold custom-modal-label">
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
                  <label class="form-label fs-12 fw-semibold custom-modal-label">NPSN (Nomor Pokok Sekolah Nasional)</label>
                  <input 
                    v-model="form.npsn" 
                    type="text" 
                    class="form-control form-control-sm" 
                    placeholder="Contoh: 20581001"
                  />
                </div>

                <!-- Alamat Lengkap -->
                <div class="col-12">
                  <label class="form-label fs-12 fw-semibold custom-modal-label">Alamat Lengkap</label>
                  <textarea 
                    v-model="form.alamat" 
                    rows="2" 
                    class="form-control form-control-sm" 
                    placeholder="Jalan, Desa/Kelurahan..."
                  ></textarea>
                </div>

                <!-- No Telepon / WA -->
                <div class="col-md-6">
                  <label class="form-label fs-12 fw-semibold custom-modal-label">Nomor Telepon / WhatsApp</label>
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
                  <label class="form-label fs-12 fw-semibold custom-modal-label">Email Resmi Sekolah</label>
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
                    <label class="form-check-label fs-12 custom-modal-label" for="schoolIsActive">
                      Sekolah aktif (dapat dipilih pada saat pendaftaran online)
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer border-top py-2.5 px-4 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm custom-modal-cancel border px-3" @click="closeFormModal">
                Batal
              </button>
              <button type="submit" class="btn btn-sm btn-success px-3.5" :disabled="isSubmitting">
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
      style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden custom-modal-content">
          <div class="modal-body p-4 text-center">
            <div class="avatar-md bg-danger-subtle text-danger rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fs-24" style="width: 54px; height: 54px;">
              <i class="ri-delete-bin-line"></i>
            </div>
            <h5 class="fs-15 fw-bold custom-modal-title mb-1">Hapus Data Sekolah?</h5>
            <p class="custom-modal-subtitle fs-12 mb-3">
              Apakah Anda yakin ingin menghapus <strong>{{ itemToDelete?.name }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-sm custom-modal-cancel border px-3" @click="closeDeleteModal">
                Batal
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-danger px-3 shadow-sm" 
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

    <!-- ============================================================== -->
    <!-- MODAL: IMPORT CSV                                              -->
    <!-- ============================================================== -->
    <div 
      v-if="isImportModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden custom-modal-content">
          <div class="modal-header border-bottom py-3 px-4">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-xs bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center">
                <i class="ri-file-upload-line fs-16"></i>
              </div>
              <div>
                <h5 class="modal-title fs-15 fw-bold custom-modal-title mb-0">Import Database Sekolah</h5>
                <small class="text-muted fs-11">Unggah file spreadsheet CSV resmi Dispendik / Kemenag</small>
              </div>
            </div>
            <button type="button" class="btn-close" @click="closeImportModal"></button>
          </div>

          <form @submit.prevent="submitImport">
            <div class="modal-body p-4">
              <!-- Helper & Template Download -->
              <div class="p-3 custom-modal-card rounded-3 border mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fs-12 fw-bold custom-modal-title">
                    <i class="ri-information-line text-primary me-1"></i> Format Kolom CSV
                  </span>
                  <a 
                    href="/admin/sekolah/template-csv" 
                    class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1 rounded-pill"
                    title="Download template CSV siap pakai"
                  >
                    <i class="ri-download-line"></i> Download Template
                  </a>
                </div>
                <p class="fs-11 text-muted mb-0">
                  Pastikan file CSV memiliki kolom header: <code>name</code> (Nama Sekolah), <code>jenjang</code> (SMP/MTs), <code>status</code> (Negeri/Swasta), <code>kecamatan</code>, serta opsional <code>npsn</code>, <code>alamat</code>, <code>phone</code>, <code>email</code>.
                </p>
              </div>

              <!-- File Upload Input Area -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold custom-modal-label">Pilih File CSV (*.csv) <span class="text-danger">*</span></label>
                <input 
                  ref="importFileInput"
                  type="file" 
                  class="form-control form-control-sm" 
                  accept=".csv,text/csv,text/plain" 
                  required
                  @change="onFileSelect"
                />
                <small class="text-muted fs-11 d-block mt-1">
                  Mendukung pemisah koma (<code>,</code>) maupun titik koma (<code>;</code>) dari Microsoft Excel.
                </small>
              </div>

              <!-- Selected File Info -->
              <div v-if="importFile" class="d-flex align-items-center gap-2 p-2 bg-success-subtle text-success rounded-2 fs-12">
                <i class="ri-checkbox-circle-line fs-16"></i>
                <div class="text-truncate">
                  <strong>{{ importFile.name }}</strong> ({{ Math.round(importFile.size / 1024) }} KB)
                </div>
              </div>
            </div>

            <div class="modal-footer border-top py-2.5 px-4 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm custom-modal-cancel border px-3" @click="closeImportModal">
                Batal
              </button>
              <button 
                type="submit" 
                class="btn btn-sm btn-primary px-3.5 d-flex align-items-center gap-1"
                :disabled="isImporting || !importFile"
              >
                <i class="ri-upload-cloud-line" :class="{ 'ri-spin': isImporting }"></i>
                <span>{{ isImporting ? 'Mengimpor...' : 'Mulai Import' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: CLEAN / CLEAR SCHOOLS DATA                             -->
    <!-- ============================================================== -->
    <div 
      v-if="isCleanModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden custom-modal-content">
          <div class="modal-header bg-danger-subtle border-bottom py-3 px-4">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-xs bg-danger text-white rounded-2 d-flex align-items-center justify-content-center">
                <i class="ri-delete-bin-line fs-16"></i>
              </div>
              <div>
                <h5 class="modal-title fs-15 fw-bold text-danger mb-0">Bersihkan Database Sekolah</h5>
                <small class="text-muted fs-11">Pilih cakupan data yang ingin dibersihkan / dihapus</small>
              </div>
            </div>
            <button type="button" class="btn-close" @click="closeCleanModal"></button>
          </div>

          <form @submit.prevent="executeClean">
            <div class="modal-body p-4">
              <p class="fs-13 custom-modal-title mb-3">
                Silakan pilih opsi pembersihan data sekolah & madrasah:
              </p>

              <!-- Option 1: Clear All / Reset -->
              <div 
                class="form-check p-3 rounded-3 border mb-2.5 transition-all cursor-pointer custom-clean-opt"
                :class="cleanScope === 'all' ? 'border-danger bg-danger-subtle' : 'custom-modal-card'"
                @click="cleanScope = 'all'"
              >
                <input 
                  id="scopeAll"
                  v-model="cleanScope"
                  class="form-check-input cursor-pointer" 
                  type="radio" 
                  value="all" 
                />
                <label class="form-check-label ms-1 cursor-pointer w-100" for="scopeAll">
                  <strong class="d-block text-danger fs-13 mb-0.5">
                    <i class="ri-refresh-line me-1"></i> Kosongkan Seluruh Data (Hasil API / Seeder)
                  </strong>
                  <span class="fs-11 text-muted d-block">
                    Menghapus seluruh {{ metrics.total_schools || 0 }} data sekolah saat ini agar database bersih total. Riwayat pendaftaran peserta yang sudah masuk tetap aman.
                  </span>
                </label>
              </div>

              <!-- Option 2: Only Inactive -->
              <div 
                class="form-check p-3 rounded-3 border mb-2.5 transition-all cursor-pointer custom-clean-opt"
                :class="cleanScope === 'inactive' ? 'border-warning bg-warning-subtle' : 'custom-modal-card'"
                @click="cleanScope = 'inactive'"
              >
                <input 
                  id="scopeInactive"
                  v-model="cleanScope"
                  class="form-check-input cursor-pointer" 
                  type="radio" 
                  value="inactive" 
                />
                <label class="form-check-label ms-1 cursor-pointer w-100" for="scopeInactive">
                  <strong class="d-block custom-modal-title fs-13 mb-0.5">
                    <i class="ri-close-circle-line me-1 text-warning"></i> Hapus Sekolah Non-Aktif Saja
                  </strong>
                  <span class="fs-11 text-muted d-block">
                    Menghapus sekolah-sekolah yang statusnya telah dinonaktifkan di sistem.
                  </span>
                </label>
              </div>

              <!-- Option 3: Dummy / Test Data -->
              <div 
                class="form-check p-3 rounded-3 border transition-all cursor-pointer custom-clean-opt"
                :class="cleanScope === 'dummy' ? 'border-primary bg-primary-subtle' : 'custom-modal-card'"
                @click="cleanScope = 'dummy'"
              >
                <input 
                  id="scopeDummy"
                  v-model="cleanScope"
                  class="form-check-input cursor-pointer" 
                  type="radio" 
                  value="dummy" 
                />
                <label class="form-check-label ms-1 cursor-pointer w-100" for="scopeDummy">
                  <strong class="d-block custom-modal-title fs-13 mb-0.5">
                    <i class="ri-magic-line me-1 text-primary"></i> Hapus Data Uji Coba / Dummy Saja
                  </strong>
                  <span class="fs-11 text-muted d-block">
                    Menghapus data dengan nama kosong atau berlabel "test / contoh / dummy".
                  </span>
                </label>
              </div>
            </div>

            <div class="modal-footer border-top py-2.5 px-4 d-flex justify-content-end gap-2">
              <button type="button" class="btn btn-sm custom-modal-cancel border px-3" @click="closeCleanModal">
                Batal
              </button>
              <button 
                type="submit" 
                class="btn btn-sm btn-danger px-3.5 d-flex align-items-center gap-1.5 shadow-sm"
                :disabled="isCleaning"
              >
                <i class="ri-delete-bin-line" :class="{ 'ri-spin': isCleaning }"></i>
                <span>{{ isCleaning ? 'Memproses...' : 'Bersihkan Sekarang' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
/* Custom Modal Elements */
.custom-modal-content {
  background-color: #ffffff;
  color: #1e293b;
  border: 1px solid rgba(0, 0, 0, 0.08);
}
.custom-modal-title {
  color: #0f172a;
}
.custom-modal-subtitle {
  color: #64748b;
}
.custom-modal-label {
  color: #334155;
}
.custom-modal-card {
  background-color: #f8fafc;
  border-color: #e2e8f0;
}
.custom-modal-cancel {
  background-color: #f8fafc;
  border-color: #e2e8f0;
  color: #64748b;
}
.custom-modal-cancel:hover {
  background-color: #f1f5f9;
  color: #1e293b;
}

</style>

<style>
/* Dark Mode Support for Schools Management Page & Modals */
[data-bs-theme="dark"] .custom-modal-content,
[data-bs-theme="dark"] .modal-content {
  background-color: #1e2227 !important;
  color: #f1f5f9 !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7) !important;
}

[data-bs-theme="dark"] .modal-header {
  background-color: #16191d !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .modal-footer {
  background-color: #16191d !important;
  border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .custom-modal-title,
[data-bs-theme="dark"] .modal-title {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .custom-modal-subtitle {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .custom-modal-label,
[data-bs-theme="dark"] .form-label,
[data-bs-theme="dark"] .form-check-label {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .custom-modal-card {
  background-color: #16191d !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .custom-modal-cancel {
  background-color: #292e34 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .custom-modal-cancel:hover {
  background-color: #343a40 !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select {
  background-color: #14171a !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .form-control:focus,
[data-bs-theme="dark"] .form-select:focus {
  background-color: #1a1e23 !important;
  border-color: #3b82f6 !important;
  color: #ffffff !important;
  box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.2) !important;
}

[data-bs-theme="dark"] .input-group-text {
  background-color: #1a1e23 !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .card {
  background-color: #1e2227 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .card-header,
[data-bs-theme="dark"] .card-footer {
  background-color: #1e2227 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .table-light {
  background-color: #24292f !important;
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .table-hover tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.03) !important;
}

[data-bs-theme="dark"] .btn-light {
  background-color: #292e34 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .btn-light:hover {
  background-color: #343a40 !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .btn-close {
  filter: invert(1) grayscale(100%) brightness(200%);
}
</style>
