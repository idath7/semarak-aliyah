<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  backups: {
    type: Array,
    default: () => [],
  },
  tables: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  settings: {
    type: Object,
    default: () => ({}),
  },
});

const { success, error } = useToast();

// Active Tab State
const activeTab = ref('list'); // 'list', 'restore', 'schedule', 'gdrive'

// Filter & Search Backups
const searchQuery = ref('');
const filterFormat = ref('all');

const formatOptions = [
  { value: 'all', label: 'Semua Format (.zip & .json)' },
  { value: 'zip', label: 'Hanya Berkas ZIP (Aplikasi + DB)' },
  { value: 'json', label: 'Hanya Berkas JSON (Data DB)' },
];

const filteredBackups = computed(() => {
  return props.backups.filter((b) => {
    const matchesSearch = b.filename.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesFormat = filterFormat.value === 'all' || b.format === filterFormat.value;
    return matchesSearch && matchesFormat;
  });
});

// Manual Backup Modal State
const isCreateModalOpen = ref(false);
const createForm = useForm({
  format: 'zip',
  sync_gdrive: false,
  note: '',
});

const createFormatOptions = [
  { value: 'zip', label: 'Arsip ZIP Lengkap (Aplikasi, Berkas Upload & Database)' },
  { value: 'json', label: 'Snapshot JSON Cepat (Seluruh Data Database & Pengaturan)' },
];

function submitCreateBackup() {
  createForm.post('/admin/pengaturan/backup/run', {
    preserveScroll: true,
    onSuccess: () => {
      isCreateModalOpen.value = false;
      createForm.reset();
      success('Berkas cadangan berhasil diproses dan disimpan!');
    },
    onError: () => {
      error('Gagal membuat berkas cadangan.');
    },
  });
}

// Restore Form State
const restoreFile = ref(null);
const isRestoring = ref(false);
const restoreConfirmationText = ref('');
const isConfirmRestoreModalOpen = ref(false);
const targetRestoreItem = ref(null); // If restoring from existing list

function onFileSelect(e) {
  const file = e.target.files[0];
  if (file) {
    restoreFile.value = file;
  }
}

function openRestoreConfirm(item = null) {
  targetRestoreItem.value = item;
  restoreConfirmationText.value = '';
  isConfirmRestoreModalOpen.value = true;
}

function executeRestore() {
  if (restoreConfirmationText.value !== 'PULIHKAN') {
    error('Ketik kata "PULIHKAN" dengan huruf kapital untuk konfirmasi.');
    return;
  }

  isRestoring.value = true;
  isConfirmRestoreModalOpen.value = false;

  const formData = new FormData();
  if (targetRestoreItem.value) {
    formData.append('filename', targetRestoreItem.value.filename);
  } else if (restoreFile.value) {
    formData.append('backup_file', restoreFile.value);
  } else {
    isRestoring.value = false;
    error('Pilih berkas cadangan terlebih dahulu.');
    return;
  }

  router.post('/admin/pengaturan/backup/restore', formData, {
    preserveScroll: true,
    onSuccess: () => {
      isRestoring.value = false;
      restoreFile.value = null;
      targetRestoreItem.value = null;
      success('Pemulihan sistem berhasil diselesaikan!');
    },
    onError: (err) => {
      isRestoring.value = false;
      error(err.message || 'Gagal memulihkan cadangan sistem.');
    },
  });
}

// Delete Backup State
function confirmDeleteBackup(filename) {
  if (confirm(`Apakah Anda yakin ingin menghapus berkas cadangan "${filename}"?`)) {
    router.delete('/admin/pengaturan/backup/' + encodeURIComponent(filename), {
      preserveScroll: true,
      onSuccess: () => success(`Berkas cadangan ${filename} berhasil dihapus.`),
      onError: () => error('Gagal menghapus berkas cadangan.'),
    });
  }
}

// Manual Sync to Google Drive
function syncSpecificBackup(filename) {
  if (!props.settings.backup_gdrive_client_id || !props.settings.backup_gdrive_refresh_token) {
    error('Kredensial Google Drive belum dikonfigurasi. Silakan buka tab "Google Drive" terlebih dahulu.');
    return;
  }

  router.post('/admin/pengaturan/backup/sync-gdrive/' + encodeURIComponent(filename), {}, {
    preserveScroll: true,
    onSuccess: () => success(`Cadangan ${filename} berhasil diunggah ke Google Drive!`),
    onError: () => error('Gagal menyinkronkan berkas ke Google Drive.'),
  });
}

// Schedule Settings Form
const scheduleForm = useForm({
  backup_schedule_enabled: props.settings.backup_schedule_enabled,
  backup_schedule_frequency: props.settings.backup_schedule_frequency || 'daily',
  backup_schedule_time: props.settings.backup_schedule_time || '02:00',
  backup_schedule_format: props.settings.backup_schedule_format || 'both',
  backup_retention_days: props.settings.backup_retention_days || 14,
  backup_gdrive_enabled: props.settings.backup_gdrive_enabled,
  backup_gdrive_client_id: props.settings.backup_gdrive_client_id || '',
  backup_gdrive_client_secret: props.settings.backup_gdrive_client_secret || '',
  backup_gdrive_refresh_token: props.settings.backup_gdrive_refresh_token || '',
  backup_gdrive_folder_id: props.settings.backup_gdrive_folder_id || '',
});

const frequencyOptions = [
  { value: 'daily', label: 'Setiap Hari (Rekomendasi - Pukul 02:00 WIB)' },
  { value: 'weekly', label: 'Setiap Minggu (Setiap Hari Minggu)' },
  { value: 'monthly', label: 'Setiap Bulan (Tanggal 1)' },
];

const scheduleFormatOptions = [
  { value: 'both', label: 'Keduanya: ZIP Lengkap & Snapshot JSON' },
  { value: 'zip', label: 'Hanya ZIP Lengkap (Aplikasi + DB)' },
  { value: 'json', label: 'Hanya Snapshot JSON Database' },
];

function submitScheduleSettings() {
  scheduleForm.post('/admin/pengaturan/backup/settings', {
    preserveScroll: true,
    onSuccess: () => success('Pengaturan jadwal cadangan berhasil disimpan!'),
    onError: () => error('Gagal menyimpan pengaturan jadwal cadangan.'),
  });
}

// Test Google Drive Connection
const isTestingGdrive = ref(false);
const gdriveTestResult = ref(null);

async function testGdriveConnection() {
  isTestingGdrive.value = true;
  gdriveTestResult.value = null;

  try {
    const res = await fetch('/admin/pengaturan/backup/test-gdrive', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        client_id: scheduleForm.backup_gdrive_client_id,
        client_secret: scheduleForm.backup_gdrive_client_secret,
        refresh_token: scheduleForm.backup_gdrive_refresh_token,
        folder_id: scheduleForm.backup_gdrive_folder_id,
      }),
    });

    const data = await res.json();
    gdriveTestResult.value = data;
    if (data.success) {
      success(data.message);
    } else {
      error(data.message);
    }
  } catch (err) {
    gdriveTestResult.value = {
      success: false,
      message: 'Gagal terhubung ke endpoint pengujian: ' + err.message,
    };
    error('Gagal terhubung ke endpoint pengujian.');
  } finally {
    isTestingGdrive.value = false;
  }
}
</script>

<template>
  <VelzonLayout>
    <Head title="Manajemen Cadangan & Pemulihan (Backup & Restore)" />

    <div class="container-fluid py-4">
      <!-- HEADER BANNER -->
      <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 16px;">
        <div class="card-body p-4 text-white">
          <div class="row align-items-center g-3">
            <div class="col-lg-8">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 fs-11 text-uppercase" style="letter-spacing: 1px;">
                  <i class="ri-shield-flash-line me-1"></i> Data Continuity & Disaster Recovery
                </span>
                <span class="badge bg-info-subtle text-info fs-11">
                  PostgreSQL &amp; FrankenPHP
                </span>
              </div>
              <h2 class="fs-22 fw-bold text-white mb-2 lh-base">
                Manajemen Cadangan &amp; Pemulihan (Backup &amp; Restore)
              </h2>
              <p class="text-slate-300 fs-13 mb-0 lh-base" style="max-width: 800px;">
                Amankan seluruh data database, pendaftar, dan berkas unggahan aplikasi secara mandiri dalam format <strong>ZIP</strong> atau <strong>JSON</strong>. Konfigurasikan jadwal otomatis di Docker dan sinkronisasi ke Google Drive.
              </p>
            </div>
            <div class="col-lg-4 text-lg-end">
              <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <button 
                  type="button" 
                  class="btn btn-warning fw-semibold px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1.5"
                  @click="isCreateModalOpen = true"
                >
                  <i class="ri-add-circle-line fs-16"></i>
                  <span>Buat Cadangan Baru</span>
                </button>
                <button 
                  type="button" 
                  class="btn btn-outline-light px-3 py-2 d-inline-flex align-items-center gap-1.5"
                  @click="activeTab = 'restore'"
                >
                  <i class="ri-restart-line fs-16"></i>
                  <span>Upload &amp; Restore</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4 KPI SUMMARY CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-20">
                  <i class="ri-file-zip-line"></i>
                </div>
                <div>
                  <div class="text-muted fs-11 fw-medium">Total Cadangan</div>
                  <div class="fs-18 fw-bold text-dark">{{ stats.total_backups || 0 }} Berkas</div>
                  <small class="text-muted fs-10 font-mono">{{ stats.zip_count || 0 }} ZIP • {{ stats.json_count || 0 }} JSON</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center fs-20">
                  <i class="ri-database-2-line"></i>
                </div>
                <div>
                  <div class="text-muted fs-11 fw-medium">Total Ukuran Penyimpanan</div>
                  <div class="fs-18 fw-bold text-dark">{{ stats.total_size_human || '0 B' }}</div>
                  <small class="text-muted fs-10">{{ stats.tables_count || 0 }} Tabel Database</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-3">
                <div 
                  class="avatar-sm rounded-3 d-flex align-items-center justify-content-center fs-20"
                  :class="settings.backup_schedule_enabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted'"
                >
                  <i class="ri-calendar-check-line"></i>
                </div>
                <div>
                  <div class="text-muted fs-11 fw-medium">Jadwal Docker / Cron</div>
                  <div class="fs-14 fw-bold text-dark text-truncate">
                    <span v-if="settings.backup_schedule_enabled" class="text-success">
                      <i class="ri-check-line"></i> Aktif ({{ settings.backup_schedule_time }})
                    </span>
                    <span v-else class="text-muted">Nonaktif</span>
                  </div>
                  <small class="text-muted fs-10 text-capitalize">{{ settings.backup_schedule_frequency }} (Retensi {{ settings.backup_retention_days }} Hari)</small>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3">
              <div class="d-flex align-items-center gap-3">
                <div 
                  class="avatar-sm rounded-3 d-flex align-items-center justify-content-center fs-20"
                  :class="settings.backup_gdrive_enabled ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-muted'"
                >
                  <i class="ri-google-line"></i>
                </div>
                <div>
                  <div class="text-muted fs-11 fw-medium">Google Drive Cloud</div>
                  <div class="fs-14 fw-bold text-dark text-truncate">
                    <span v-if="settings.backup_gdrive_enabled" class="text-warning">
                      <i class="ri-cloud-line"></i> Otomatis Sinkron
                    </span>
                    <span v-else class="text-muted">Belum Aktif</span>
                  </div>
                  <small class="text-muted fs-10 font-mono">{{ settings.backup_gdrive_folder_id ? 'Folder Terhubung' : 'Folder Default' }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN TABS NAVIGATION -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-0">
          <ul class="nav nav-tabs nav-tabs-custom border-bottom-0" role="tablist">
            <li class="nav-item">
              <a 
                class="nav-link py-3 px-4 fs-13 fw-semibold cursor-pointer d-flex align-items-center gap-2"
                :class="{ 'active text-primary border-primary border-bottom border-2': activeTab === 'list' }"
                @click="activeTab = 'list'"
              >
                <i class="ri-folder-zip-line fs-15"></i>
                <span>Riwayat Berkas Cadangan</span>
                <span class="badge bg-primary-subtle text-primary rounded-pill fs-10">{{ backups.length }}</span>
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link py-3 px-4 fs-13 fw-semibold cursor-pointer d-flex align-items-center gap-2"
                :class="{ 'active text-primary border-primary border-bottom border-2': activeTab === 'restore' }"
                @click="activeTab = 'restore'"
              >
                <i class="ri-restart-line fs-15"></i>
                <span>Pemulihan (Restore ZIP / JSON)</span>
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link py-3 px-4 fs-13 fw-semibold cursor-pointer d-flex align-items-center gap-2"
                :class="{ 'active text-primary border-primary border-bottom border-2': activeTab === 'schedule' }"
                @click="activeTab = 'schedule'"
              >
                <i class="ri-time-line fs-15"></i>
                <span>Jadwal Otomatis di Docker</span>
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link py-3 px-4 fs-13 fw-semibold cursor-pointer d-flex align-items-center gap-2"
                :class="{ 'active text-primary border-primary border-bottom border-2': activeTab === 'gdrive' }"
                @click="activeTab = 'gdrive'"
              >
                <i class="ri-google-drive-line fs-15"></i>
                <span>Integrasi Google Drive</span>
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body p-4">
          <!-- ============================================================== -->
          <!-- TAB 1: DAFTAR BERKAS CADANGAN                                  -->
          <!-- ============================================================== -->
          <div v-if="activeTab === 'list'">
            <!-- Filter Bar -->
            <div class="row align-items-center g-3 mb-4">
              <div class="col-md-5">
                <div class="input-group">
                  <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-search-line"></i></span>
                  <input 
                    type="text" 
                    v-model="searchQuery" 
                    class="form-control border-start-0 ps-0" 
                    placeholder="Cari nama berkas cadangan..." 
                  />
                </div>
              </div>
              <div class="col-md-4">
                <CustomSelect 
                  v-model="filterFormat" 
                  :options="formatOptions"
                  size="md"
                />
              </div>
              <div class="col-md-3 text-md-end">
                <button 
                  type="button" 
                  class="btn btn-primary d-inline-flex align-items-center gap-1.5"
                  @click="isCreateModalOpen = true"
                >
                  <i class="ri-file-add-line"></i>
                  <span>Buat Cadangan Baru</span>
                </button>
              </div>
            </div>

            <!-- Table of Backups -->
            <div class="table-responsive border rounded-3">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="ps-3 py-3 fs-12 fw-bold text-dark text-uppercase">Berkas &amp; Format</th>
                    <th class="py-3 fs-12 fw-bold text-dark text-uppercase">Cakupan Cadangan</th>
                    <th class="py-3 fs-12 fw-bold text-dark text-uppercase">Ukuran</th>
                    <th class="py-3 fs-12 fw-bold text-dark text-uppercase">Waktu Dibuat</th>
                    <th class="pe-3 py-3 fs-12 fw-bold text-dark text-uppercase text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in filteredBackups" :key="item.filename">
                    <td class="ps-3 py-3">
                      <div class="d-flex align-items-center gap-3">
                        <div 
                          class="avatar-xs rounded-2 d-flex align-items-center justify-content-center fs-16"
                          :class="item.format === 'zip' ? 'bg-warning-subtle text-warning' : 'bg-info-subtle text-info'"
                        >
                          <i :class="item.format === 'zip' ? 'ri-file-zip-line' : 'ri-braces-line'"></i>
                        </div>
                        <div>
                          <div class="fw-bold text-dark fs-13 font-mono">{{ item.filename }}</div>
                          <span 
                            class="badge fs-10 text-uppercase"
                            :class="item.format === 'zip' ? 'bg-warning text-dark' : 'bg-info text-white'"
                          >
                            {{ item.format }}
                          </span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="fs-12 text-muted">{{ item.scope }}</span>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark border fs-11 font-mono">{{ item.size_human }}</span>
                    </td>
                    <td>
                      <span class="fs-12 text-muted font-mono"><i class="ri-time-line me-1"></i>{{ item.created_at }}</span>
                    </td>
                    <td class="pe-3 text-end">
                      <div class="btn-group btn-group-sm">
                        <a 
                          :href="item.download_url" 
                          class="btn btn-outline-secondary" 
                          title="Unduh Berkas Cadangan"
                        >
                          <i class="ri-download-2-line"></i>
                        </a>
                        <button 
                          type="button" 
                          class="btn btn-outline-success" 
                          title="Pulihkan Sistem Dari Berkas Ini"
                          @click="openRestoreConfirm(item)"
                        >
                          <i class="ri-restart-line"></i>
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-warning" 
                          title="Unggah ke Google Drive"
                          @click="syncSpecificBackup(item.filename)"
                        >
                          <i class="ri-google-line"></i>
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-outline-danger" 
                          title="Hapus Berkas Cadangan"
                          @click="confirmDeleteBackup(item.filename)"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr v-if="filteredBackups.length === 0">
                    <td colspan="5" class="text-center py-5 text-muted">
                      <i class="ri-folder-open-line fs-32 d-block mb-2 text-slate-400"></i>
                      <div class="fw-semibold fs-13">Belum ada berkas cadangan ditemukan.</div>
                      <p class="fs-12 text-muted mb-3">Klik tombol di bawah untuk membuat cadangan ZIP atau JSON pertama Anda.</p>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-primary"
                        @click="isCreateModalOpen = true"
                      >
                        <i class="ri-add-line me-1"></i> Buat Cadangan Sekarang
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 2: RESTORE DARI FILE UNGGAHAN                              -->
          <!-- ============================================================== -->
          <div v-else-if="activeTab === 'restore'">
            <div class="row justify-content-center">
              <div class="col-lg-8">
                <div class="card border border-warning-subtle bg-warning-subtle rounded-3 p-3 mb-4">
                  <div class="d-flex align-items-start gap-3">
                    <i class="ri-alert-fill fs-24 text-warning"></i>
                    <div>
                      <h5 class="fs-14 fw-bold text-dark mb-1">Perhatian Penting Sebelum Melakukan Pemulihan:</h5>
                      <ul class="fs-12 text-muted mb-0 ps-3">
                        <li>Memulihkan cadangan akan <strong>menimpa data database aktif</strong> saat ini sesuai isi berkas cadangan.</li>
                        <li>Pastikan Anda telah membuat cadangan terbaru terlebih dahulu sebelum menjalankan proses ini.</li>
                        <li>Format yang didukung: berkas <code>.zip</code> (Aplikasi + DB) dan berkas <code>.json</code> (Database &amp; Pengaturan).</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- Dropzone / File Upload Zone -->
                <div class="card border border-dashed rounded-3 p-5 text-center bg-light mb-4">
                  <input 
                    type="file" 
                    id="restoreFileInput" 
                    accept=".zip,.json" 
                    class="d-none" 
                    @change="onFileSelect" 
                  />
                  <label for="restoreFileInput" class="cursor-pointer mb-0">
                    <div class="avatar-md bg-primary-subtle text-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fs-28">
                      <i class="ri-upload-cloud-2-line"></i>
                    </div>
                    <h5 class="fs-15 fw-bold text-dark mb-1">Pilih Berkas Cadangan (.ZIP atau .JSON)</h5>
                    <p class="text-muted fs-12 mb-3">Klik di sini untuk memilih berkas dari komputer Anda (Maks. 200 MB)</p>
                    <span class="btn btn-outline-primary btn-sm px-3">
                      <i class="ri-folder-open-line me-1"></i> Telusuri Berkas
                    </span>
                  </label>

                  <div v-if="restoreFile" class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between text-start bg-white p-3 rounded-3 shadow-sm">
                    <div class="d-flex align-items-center gap-2.5">
                      <i :class="restoreFile.name.endsWith('.zip') ? 'ri-file-zip-line text-warning fs-24' : 'ri-braces-line text-info fs-24'"></i>
                      <div>
                        <div class="fw-bold text-dark fs-13">{{ restoreFile.name }}</div>
                        <div class="text-muted fs-11">{{ (restoreFile.size / (1024 * 1024)).toFixed(2) }} MB</div>
                      </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="restoreFile = null">
                      <i class="ri-close-line"></i> Batal
                    </button>
                  </div>
                </div>

                <div class="text-end">
                  <button 
                    type="button" 
                    class="btn btn-danger btn-lg px-4 fs-14 fw-semibold"
                    :disabled="!restoreFile || isRestoring"
                    @click="openRestoreConfirm(null)"
                  >
                    <i class="ri-restart-line me-1"></i>
                    <span v-if="isRestoring">Memproses Pemulihan...</span>
                    <span v-else>Mulai Pemulihan Data Sistem</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 3: JADWAL OTOMATIS DOCKER (CRON)                           -->
          <!-- ============================================================== -->
          <div v-else-if="activeTab === 'schedule'">
            <form @submit.prevent="submitScheduleSettings">
              <div class="row g-4">
                <div class="col-lg-7">
                  <h5 class="fs-14 fw-bold text-dark mb-3">
                    <i class="ri-time-line text-primary me-1"></i> Konfigurasi Penjadwalan Cadangan
                  </h5>

                  <!-- Enable Toggle -->
                  <div class="card border rounded-3 p-3 mb-3 bg-light">
                    <div class="form-check form-switch mb-0 d-flex align-items-center justify-content-between ps-0">
                      <div>
                        <label class="form-check-label fw-bold text-dark fs-13 mb-0" for="scheduleToggle">
                          Aktifkan Cadangan Otomatis Terjadwal
                        </label>
                        <p class="text-muted fs-12 mb-0">Cadangkan database dan aplikasi secara berkala di latar belakang server / container Docker.</p>
                      </div>
                      <input 
                        class="form-check-input ms-3 fs-18" 
                        type="checkbox" 
                        id="scheduleToggle" 
                        v-model="scheduleForm.backup_schedule_enabled" 
                      />
                    </div>
                  </div>

                  <!-- Frequency Dropdown (CustomSelect) -->
                  <div class="mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark">Frekuensi Cadangan</label>
                    <CustomSelect 
                      v-model="scheduleForm.backup_schedule_frequency" 
                      :options="frequencyOptions"
                      size="md"
                    />
                  </div>

                  <!-- Execution Time -->
                  <div class="mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark">Waktu Eksekusi (Format 24 Jam HH:mm)</label>
                    <input 
                      type="text" 
                      v-model="scheduleForm.backup_schedule_time" 
                      class="form-control" 
                      placeholder="02:00" 
                      pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                    />
                    <small class="text-muted fs-11">Disarankan pada jam sepi pengunjung (contoh: 02:00 WIB dini hari).</small>
                  </div>

                  <!-- Format Dropdown (CustomSelect) -->
                  <div class="mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark">Format Cadangan Otomatis</label>
                    <CustomSelect 
                      v-model="scheduleForm.backup_schedule_format" 
                      :options="scheduleFormatOptions"
                      size="md"
                    />
                  </div>

                  <!-- Retention Period -->
                  <div class="mb-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Masa Retensi Cadangan (Hari)</label>
                    <input 
                      type="number" 
                      v-model.number="scheduleForm.backup_retention_days" 
                      class="form-control" 
                      min="1" 
                      max="365" 
                    />
                    <small class="text-muted fs-11">Berkas cadangan yang lebih lama dari jumlah hari ini akan otomatis dibersihkan untuk menghemat ruang disk.</small>
                  </div>

                  <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold" :disabled="scheduleForm.processing">
                      <i class="ri-save-line me-1"></i> Simpan Konfigurasi Jadwal
                    </button>
                  </div>
                </div>

                <!-- Right Column: Docker Guide & Status -->
                <div class="col-lg-5">
                  <div class="card border rounded-3 p-3 bg-slate-900 text-white mb-3" style="background-color: #0f172a;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i class="ri-docker-line fs-20 text-info"></i>
                      <h6 class="fs-13 fw-bold text-white mb-0">Petunjuk Eksekusi di Container Docker</h6>
                    </div>
                    <p class="fs-12 text-slate-300 mb-3">
                      Cadangan otomatis dapat dipicu kapan saja dari host atau task scheduler Docker menggunakan perintah Artisan:
                    </p>
                    <div class="bg-black p-2.5 rounded-2 font-mono fs-11 text-warning mb-3 overflow-x-auto">
                      docker exec -it semarak_frankenphp_octane php artisan backup:run
                    </div>
                    <p class="fs-11 text-slate-400 mb-0">
                      • Lokasi Volume Cadangan: <code>/var/www/html/storage/app/backups</code><br>
                      • Opsi Tambahan: <code>--format=zip</code>, <code>--format=json</code>, <code>--gdrive</code>
                    </p>
                  </div>

                  <!-- Last Run Status -->
                  <div class="card border rounded-3 p-3 bg-light">
                    <h6 class="fs-13 fw-bold text-dark mb-2">
                      <i class="ri-history-line text-muted me-1"></i> Status Eksekusi Terakhir
                    </h6>
                    <div class="fs-12 mb-1">
                      <span class="text-muted">Waktu:</span> 
                      <strong class="ms-1 font-mono">{{ settings.backup_last_run_at || 'Belum pernah dieksekusi' }}</strong>
                    </div>
                    <div class="fs-12 mb-1">
                      <span class="text-muted">Status:</span> 
                      <span 
                        class="badge ms-1"
                        :class="settings.backup_last_status === 'success' ? 'bg-success text-white' : 'bg-secondary text-white'"
                      >
                        {{ settings.backup_last_status || 'Belum Ada' }}
                      </span>
                    </div>
                    <div class="fs-11 text-muted">{{ settings.backup_last_message || '-' }}</div>
                  </div>
                </div>
              </div>
            </form>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 4: INTEGRASI GOOGLE DRIVE                                  -->
          <!-- ============================================================== -->
          <div v-else-if="activeTab === 'gdrive'">
            <form @submit.prevent="submitScheduleSettings">
              <div class="row g-4">
                <div class="col-lg-7">
                  <h5 class="fs-14 fw-bold text-dark mb-3">
                    <i class="ri-google-drive-line text-warning me-1"></i> Konfigurasi Akun &amp; API Google Drive
                  </h5>

                  <!-- Google Drive Auto-Sync Toggle -->
                  <div class="card border rounded-3 p-3 mb-3 bg-light">
                    <div class="form-check form-switch mb-0 d-flex align-items-center justify-content-between ps-0">
                      <div>
                        <label class="form-check-label fw-bold text-dark fs-13 mb-0" for="gdriveToggle">
                          Otomatis Unggah Hasil Cadangan ke Google Drive
                        </label>
                        <p class="text-muted fs-12 mb-0">Setiap cadangan terjadwal akan otomatis diunggah ke folder Google Drive panitia.</p>
                      </div>
                      <input 
                        class="form-check-input ms-3 fs-18" 
                        type="checkbox" 
                        id="gdriveToggle" 
                        v-model="scheduleForm.backup_gdrive_enabled" 
                      />
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark">Google Cloud Client ID</label>
                    <input 
                      type="text" 
                      v-model="scheduleForm.backup_gdrive_client_id" 
                      class="form-control font-mono fs-12" 
                      placeholder="Contoh: 123456789-xxxxxxxx.apps.googleusercontent.com" 
                    />
                  </div>

                  <div class="mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark">Google Cloud Client Secret</label>
                    <input 
                      type="password" 
                      v-model="scheduleForm.backup_gdrive_client_secret" 
                      class="form-control font-mono fs-12" 
                      placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx" 
                    />
                  </div>

                  <div class="mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark">Google OAuth Refresh Token</label>
                    <textarea 
                      v-model="scheduleForm.backup_gdrive_refresh_token" 
                      class="form-control font-mono fs-12" 
                      rows="2" 
                      placeholder="1//0xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                    ></textarea>
                  </div>

                  <div class="mb-4">
                    <label class="form-label fs-12 fw-semibold text-dark">ID Folder Google Drive Target (Opsional)</label>
                    <input 
                      type="text" 
                      v-model="scheduleForm.backup_gdrive_folder_id" 
                      class="form-control font-mono fs-12" 
                      placeholder="ID folder di Google Drive (contoh: 1a2B3c4D5e6F7g8H9i)" 
                    />
                    <small class="text-muted fs-11">Dapat ditemukan pada tautan browser folder Google Drive setelah <code>/folders/ID_FOLDER</code>.</small>
                  </div>

                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <button 
                      type="button" 
                      class="btn btn-outline-warning d-inline-flex align-items-center gap-1.5"
                      :disabled="isTestingGdrive"
                      @click="testGdriveConnection"
                    >
                      <i class="ri-wifi-line"></i>
                      <span v-if="isTestingGdrive">Menguji Koneksi...</span>
                      <span v-else>Uji Koneksi Google Drive</span>
                    </button>

                    <button type="submit" class="btn btn-primary px-4 fw-semibold" :disabled="scheduleForm.processing">
                      <i class="ri-save-line me-1"></i> Simpan Kredensial Google Drive
                    </button>
                  </div>

                  <!-- Test Result Notification -->
                  <div v-if="gdriveTestResult" class="mt-3">
                    <div 
                      class="alert d-flex align-items-center gap-2 mb-0 py-2.5 fs-12"
                      :class="gdriveTestResult.success ? 'alert-success' : 'alert-danger'"
                    >
                      <i :class="gdriveTestResult.success ? 'ri-checkbox-circle-line fs-16' : 'ri-error-warning-line fs-16'"></i>
                      <span>{{ gdriveTestResult.message }}</span>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Setup Steps -->
                <div class="col-lg-5">
                  <div class="card border rounded-3 p-3 bg-light mb-3">
                    <h6 class="fs-13 fw-bold text-dark mb-2">
                      <i class="ri-information-line text-primary me-1"></i> Cara Setup API Google Drive (OAuth 2.0)
                    </h6>
                    <ol class="fs-12 text-muted ps-3 mb-0 lh-base">
                      <li class="mb-1">Buka <strong>Google Cloud Console</strong> (<a href="https://console.cloud.google.com" target="_blank" class="text-primary">console.cloud.google.com</a>).</li>
                      <li class="mb-1">Aktifkan <strong>Google Drive API</strong> pada proyek Anda.</li>
                      <li class="mb-1">Buat kredensial <strong>OAuth 2.0 Client ID</strong> (Web Application).</li>
                      <li class="mb-1">Tambahkan scope <code>https://www.googleapis.com/auth/drive.file</code>.</li>
                      <li class="mb-1">Dapatkan <strong>Refresh Token</strong> offline sekali pakai melalui OAuth Playground atau Google auth flow.</li>
                      <li>Masukkan Client ID, Secret, dan Refresh Token pada formulir di sebelah kiri.</li>
                    </ol>
                  </div>

                  <!-- Last Cloud Sync Log -->
                  <div class="card border rounded-3 p-3 bg-white">
                    <h6 class="fs-13 fw-bold text-dark mb-2">
                      <i class="ri-cloud-line text-warning me-1"></i> Status Sinkronisasi Terakhir
                    </h6>
                    <div class="fs-12 mb-1">
                      <span class="text-muted">Waktu Sinkron:</span> 
                      <strong class="ms-1 font-mono">{{ settings.backup_gdrive_last_sync_at || 'Belum ada data sinkron' }}</strong>
                    </div>
                    <div class="fs-12 mb-1">
                      <span class="text-muted">Status:</span> 
                      <span 
                        class="badge ms-1"
                        :class="settings.backup_gdrive_last_sync_status === 'success' ? 'bg-success text-white' : 'bg-secondary text-white'"
                      >
                        {{ settings.backup_gdrive_last_sync_status || 'Belum Ada' }}
                      </span>
                    </div>
                    <div class="fs-11 text-muted">{{ settings.backup_gdrive_last_sync_message || '-' }}</div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: BUAT CADANGAN BARU                                      -->
    <!-- ============================================================== -->
    <div v-if="isCreateModalOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.6); z-index: 1060;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header border-bottom py-3">
            <h5 class="modal-title fs-15 fw-bold text-dark">
              <i class="ri-file-add-line text-warning me-1"></i> Buat Berkas Cadangan Baru
            </h5>
            <button type="button" class="btn-close" @click="isCreateModalOpen = false"></button>
          </div>
          <form @submit.prevent="submitCreateBackup">
            <div class="modal-body p-4">
              <!-- Format Selection (CustomSelect) -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Format &amp; Cakupan Cadangan</label>
                <CustomSelect 
                  v-model="createForm.format" 
                  :options="createFormatOptions"
                  size="md"
                />
              </div>

              <!-- Note / Suffix -->
              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">Label / Catatan Tambahan (Opsional)</label>
                <input 
                  type="text" 
                  v-model="createForm.note" 
                  class="form-control" 
                  placeholder="Contoh: sebelum_final_harian" 
                  maxlength="50" 
                />
                <small class="text-muted fs-11">Hanya huruf, angka, dan garis bawah.</small>
              </div>

              <!-- Sync to Google Drive Checkbox -->
              <div class="form-check form-switch mb-0">
                <input 
                  class="form-check-input" 
                  type="checkbox" 
                  id="modalSyncGdrive" 
                  v-model="createForm.sync_gdrive" 
                />
                <label class="form-check-label fs-12 fw-medium text-dark cursor-pointer" for="modalSyncGdrive">
                  Langsung unggah hasil cadangan ke Google Drive
                </label>
              </div>
            </div>
            <div class="modal-footer border-top py-2.5 px-4">
              <button type="button" class="btn btn-sm btn-light border" @click="isCreateModalOpen = false">Batal</button>
              <button type="submit" class="btn btn-sm btn-primary px-3 fw-semibold" :disabled="createForm.processing" @click.prevent="submitCreateBackup">
                <i class="ri-check-line me-1"></i> Mulai Proses Cadangan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: KONFIRMASI RESTORE (HIGH RISK DANGER CONFIRMATION)       -->
    <!-- ============================================================== -->
    <div v-if="isConfirmRestoreModalOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.7); z-index: 1070;">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <div class="modal-header bg-danger text-white py-3">
            <h5 class="modal-title fs-15 fw-bold text-white">
              <i class="ri-error-warning-fill me-1"></i> Konfirmasi Pemulihan Sistem (Restore)
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="isConfirmRestoreModalOpen = false"></button>
          </div>
          <div class="modal-body p-4">
            <p class="fs-13 text-dark mb-2">
              Anda akan memulihkan data sistem dari berkas:
            </p>
            <div class="p-2.5 bg-light rounded-2 font-mono fs-12 fw-bold text-danger mb-3 border">
              {{ targetRestoreItem ? targetRestoreItem.filename : (restoreFile ? restoreFile.name : '-') }}
            </div>
            <div class="alert alert-danger fs-12 py-2 mb-3">
              <strong>PERINGATAN:</strong> Seluruh tabel database aktif akan ditimpa dan disinkronkan sesuai data dalam berkas ini. Tindakan ini tidak dapat dibatalkan.
            </div>

            <label class="form-label fs-12 fw-semibold text-dark">
              Ketik kata <strong class="text-danger">PULIHKAN</strong> di bawah untuk melanjutkan:
            </label>
            <input 
              type="text" 
              v-model="restoreConfirmationText" 
              class="form-control font-mono text-center fw-bold" 
              placeholder="PULIHKAN" 
            />
          </div>
          <div class="modal-footer border-top py-2.5 px-4">
            <button type="button" class="btn btn-sm btn-light border" @click="isConfirmRestoreModalOpen = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-sm btn-danger px-3 fw-bold" 
              :disabled="restoreConfirmationText !== 'PULIHKAN'"
              @click="executeRestore"
            >
              <i class="ri-restart-line me-1"></i> Ya, Pulihkan Sekarang
            </button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.nav-tabs-custom .nav-link {
  color: #64748b;
  border-top: none;
  border-left: none;
  border-right: none;
  transition: all 0.2s ease;
}
.nav-tabs-custom .nav-link:hover {
  color: #0f172a;
}
.nav-tabs-custom .nav-link.active {
  color: #405189 !important;
  background-color: transparent !important;
}
</style>
