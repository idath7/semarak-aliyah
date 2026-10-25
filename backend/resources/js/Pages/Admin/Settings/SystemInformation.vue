<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  phpInfo: {
    type: Object,
    default: () => ({})
  },
  frameworkInfo: {
    type: Object,
    default: () => ({})
  },
  dbInfo: {
    type: Object,
    default: () => ({})
  },
  cacheInfo: {
    type: Object,
    default: () => ({})
  },
  storageInfo: {
    type: Object,
    default: () => ({})
  },
  extensionsList: {
    type: Array,
    default: () => []
  }
});

const toast = useToast();
const isClearingCache = ref(false);
const activeCacheType = ref('');
const isCopying = ref(false);

function clearCache(type = 'all') {
  isClearingCache.value = true;
  activeCacheType.value = type;

  router.post('/admin/pengaturan/informasi-sistem/clear-cache', { type }, {
    preserveScroll: true,
    onSuccess: () => {
      isClearingCache.value = false;
      activeCacheType.value = '';
    },
    onError: () => {
      isClearingCache.value = false;
      activeCacheType.value = '';
      toast.error('Gagal membersihkan cache sistem.');
    }
  });
}

function refreshData() {
  router.reload({ preserveScroll: true });
  toast.info('Data status sistem diperbarui.');
}

function copySystemSummary() {
  const summary = `
=== LAPORAN INFORMASI SISTEM SEMARAK ALIYAH XIV ===
Waktu Server        : ${props.phpInfo.server_time || '-'}
PHP Version         : ${props.phpInfo.version || '-'} (${props.phpInfo.zts || '-'})
PHP SAPI            : ${props.phpInfo.sapi || '-'}
OS Host             : ${props.phpInfo.os || '-'} (${props.phpInfo.architecture || '-'})
Memory Limit        : ${props.phpInfo.memory_limit || '-'}
Current Memory      : ${props.phpInfo.current_memory || '-'} (Peak: ${props.phpInfo.peak_memory || '-'})
OPcache             : ${props.phpInfo.opcache_enabled ? 'Aktif' : 'Nonaktif'}
Framework           : Laravel v${props.frameworkInfo.laravel_version || '-'}
Server Runner       : ${props.frameworkInfo.server_software || '-'} (Octane: ${props.frameworkInfo.octane_server || '-'})
Environment         : ${props.frameworkInfo.app_env || '-'}
Mode Debug          : ${props.frameworkInfo.app_debug ? 'AKTIF (ON - Perhatian untuk server production!)' : 'NONAKTIF (OFF - Aman)'}
Status Maintenance  : ${props.frameworkInfo.maintenance ? 'Aktif (Down)' : 'Normal (Beroperasi)'}
Config Cached       : ${props.frameworkInfo.config_cached ? 'Ya' : 'Tidak'}
Routes Cached       : ${props.frameworkInfo.routes_cached ? 'Ya' : 'Tidak'}
Database Driver     : ${props.dbInfo.default_driver || '-'} (${props.dbInfo.status || '-'})
Database Version    : ${props.dbInfo.version || '-'}
Database Size       : ${props.dbInfo.size || '-'}
Cache Driver        : ${props.cacheInfo.cache_driver || '-'}
Redis Status        : ${props.cacheInfo.redis_status || '-'}
Storage Writable    : ${props.storageInfo.storage_path_writable ? 'OK (Writable)' : 'ERROR (Read-Only)'}
Disk Free Space     : ${props.storageInfo.disk_free || '-'} / ${props.storageInfo.disk_total || '-'} (${props.storageInfo.disk_used_percent || 0}% terpakai)
===================================================
`.trim();

  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(summary).then(() => {
      isCopying.value = true;
      toast.success('Ringkasan sistem berhasil disalin ke clipboard!');
      setTimeout(() => {
        isCopying.value = false;
      }, 2500);
    }).catch(() => {
      toast.info('Salin ringkasan secara manual.');
    });
  }
}
</script>

<template>
  <Head title="Informasi Sistem & Server | Pengaturan Admin" />

  <VelzonLayout>
    <div class="container-fluid">
      
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-sm-0 fw-bold d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-server-line text-primary fs-22"></i>
                <span>Informasi Sistem &amp; Kinerja Server</span>
              </h4>
              <p class="text-muted fs-12 mb-0 mt-1">
                Spesifikasi teknis lingkungan runtime PHP, database PostgreSQL, performa memori, dan kesehatan server aplikasi.
              </p>
            </div>
            <div class="page-title-right mt-2 mt-sm-0 d-flex align-items-center gap-2">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-secondary rounded-pill d-inline-flex align-items-center gap-1.5"
                @click="refreshData"
                title="Muat Ulang Status"
              >
                <i class="ri-refresh-line"></i>
                <span>Segarkan</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-primary rounded-pill d-inline-flex align-items-center gap-1.5"
                @click="copySystemSummary"
                title="Salin Rangkuman Sistem untuk Troubleshooting"
              >
                <i :class="isCopying ? 'ri-checkbox-circle-fill text-success' : 'ri-file-copy-line'"></i>
                <span>{{ isCopying ? 'Tersalin!' : 'Salin Laporan' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Metric Cards -->
      <div class="row g-3 mb-4">
        <!-- Metric 1: PHP Runtime -->
        <div class="col-xl-3 col-md-6">
          <div class="card border-0 shadow-sm rounded-4 metric-card metric-card-primary h-100">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="metric-icon-box icon-primary">
                <i class="ri-terminal-box-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="metric-label">Runtime PHP</div>
                <div class="metric-val text-primary d-flex align-items-center gap-1.5 flex-wrap">
                  <span>PHP {{ phpInfo.version }}</span>
                  <span class="badge badge-subtle-primary fs-10 fw-semibold">{{ phpInfo.zts?.includes('ZTS') ? 'ZTS' : 'NTS' }}</span>
                </div>
                <div class="metric-sub text-truncate">{{ phpInfo.sapi }} • {{ phpInfo.architecture }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Metric 2: PostgreSQL Database -->
        <div class="col-xl-3 col-md-6">
          <div class="card border-0 shadow-sm rounded-4 metric-card metric-card-success h-100">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="metric-icon-box icon-success">
                <i class="ri-database-2-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="metric-label">Database Engine</div>
                <div class="metric-val text-success d-flex align-items-center gap-1.5">
                  <span>PostgreSQL</span>
                  <span class="badge badge-subtle-success fs-10 fw-semibold">
                    <i class="ri-checkbox-circle-fill me-0.5"></i>Online
                  </span>
                </div>
                <div class="metric-sub text-truncate">{{ dbInfo.database_name }} • Ukuran: {{ dbInfo.size }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Metric 3: Framework & Environment -->
        <div class="col-xl-3 col-md-6">
          <div class="card border-0 shadow-sm rounded-4 metric-card metric-card-warning h-100">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="metric-icon-box icon-warning">
                <i class="ri-shield-check-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="metric-label">Framework &amp; Lingkungan</div>
                <div class="metric-val text-warning d-flex align-items-center gap-1.5 flex-wrap">
                  <span>Laravel v{{ frameworkInfo.laravel_version }}</span>
                </div>
                <div class="d-flex align-items-center gap-1.5 mt-1 flex-wrap">
                  <span 
                    class="badge fs-10 px-2 py-0.5 rounded-pill fw-bold text-uppercase"
                    :class="frameworkInfo.app_env === 'production' ? 'badge-subtle-success' : 'badge-subtle-warning'"
                  >
                    <i :class="frameworkInfo.app_env === 'production' ? 'ri-checkbox-circle-fill' : 'ri-tools-fill'" class="me-0.5"></i>
                    {{ frameworkInfo.app_env }}
                  </span>
                  <span 
                    class="badge fs-10 px-2 py-0.5 rounded-pill fw-bold"
                    :class="frameworkInfo.app_debug ? 'badge-subtle-danger' : 'badge-subtle-success'"
                  >
                    <i :class="frameworkInfo.app_debug ? 'ri-bug-fill' : 'ri-shield-flash-fill'" class="me-0.5"></i>
                    Debug: {{ frameworkInfo.app_debug ? 'Aktif' : 'Off' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Metric 4: RAM & OPcache -->
        <div class="col-xl-3 col-md-6">
          <div class="card border-0 shadow-sm rounded-4 metric-card metric-card-info h-100">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="metric-icon-box icon-info">
                <i class="ri-speed-up-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="metric-label">Memori &amp; Bytecode Cache</div>
                <div class="metric-val text-info d-flex align-items-center gap-1.5">
                  <span>{{ phpInfo.current_memory }}</span>
                  <span class="fs-11 fw-normal text-muted">/ {{ phpInfo.memory_limit }}</span>
                </div>
                <div class="metric-sub text-truncate">
                  OPcache: <strong :class="phpInfo.opcache_enabled ? 'text-success' : 'text-danger'">{{ phpInfo.opcache_enabled ? 'Aktif' : 'Nonaktif' }}</strong> • Peak: {{ phpInfo.peak_memory }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="row g-4">
        
        <!-- Left Column: Detailed Specifications -->
        <div class="col-lg-8">
          
          <!-- Card 1: PHP Runtime Detail -->
          <div class="card border-0 shadow-sm rounded-4 mb-4 sys-card">
            <div class="card-header sys-card-header p-3.5">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="fs-15 fw-bold sys-card-title mb-0 d-flex align-items-center gap-2">
                  <i class="ri-code-s-slash-fill text-primary"></i>
                  <span>Lingkungan Eksekusi PHP Runtime</span>
                </h5>
                <span class="badge badge-subtle-primary px-2.5 py-1 rounded-pill fs-11">
                  {{ phpInfo.version }}
                </span>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 sys-table">
                  <tbody>
                    <tr>
                      <td class="sys-table-label" style="width: 35%;">Versi PHP Lengkap</td>
                      <td class="sys-table-value font-monospace fw-bold text-primary">PHP {{ phpInfo.version }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Thread Safety (ZTS)</td>
                      <td class="sys-table-value">
                        <span class="badge" :class="phpInfo.zts?.includes('ZTS') ? 'badge-subtle-success' : 'badge-subtle-warning'">
                          {{ phpInfo.zts }}
                        </span>
                        <span class="fs-11 text-muted ms-2">Dibutuhkan FrankenPHP Worker goroutines</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">PHP Interface (SAPI)</td>
                      <td class="sys-table-value font-monospace">{{ phpInfo.sapi }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Sistem Operasi Host</td>
                      <td class="sys-table-value">{{ phpInfo.os }} ({{ phpInfo.architecture }})</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Waktu Server &amp; Zona Waktu</td>
                      <td class="sys-table-value">
                        <span class="fw-semibold">{{ phpInfo.server_time }}</span>
                        <span class="badge badge-subtle-info ms-2 fs-10">{{ phpInfo.timezone }}</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Batas Memori (memory_limit)</td>
                      <td class="sys-table-value font-monospace">{{ phpInfo.memory_limit }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Penggunaan Memori RAM Saat Ini</td>
                      <td class="sys-table-value font-monospace">
                        <span class="text-primary fw-bold">{{ phpInfo.current_memory }}</span>
                        <span class="text-muted fs-11 ms-2">(Puncak Alokasi: {{ phpInfo.peak_memory }})</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Batas Waktu Eksekusi (max_execution_time)</td>
                      <td class="sys-table-value font-monospace">{{ phpInfo.max_execution_time }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Batas Maksimal Unggahan Berkas</td>
                      <td class="sys-table-value font-monospace">
                        Upload: <span class="fw-bold">{{ phpInfo.upload_max_filesize }}</span> | Post: <span class="fw-bold">{{ phpInfo.post_max_size }}</span>
                      </td>
                    </tr>
                    <tr v-if="phpInfo.opcache_stats">
                      <td class="sys-table-label">Statistik OPcache &amp; JIT</td>
                      <td class="sys-table-value">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                          <span class="badge badge-subtle-success fs-11">
                            Hit Rate: {{ phpInfo.opcache_stats.hit_rate }}
                          </span>
                          <span class="fs-11 text-muted">
                            Memori Terpakai: {{ phpInfo.opcache_stats.used_memory }} (Sisa: {{ phpInfo.opcache_stats.free_memory }})
                          </span>
                          <span v-if="phpInfo.opcache_stats.jit_enabled" class="badge badge-subtle-info fs-10">
                            JIT Active
                          </span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Card 2: Framework Laravel & Environment Info -->
          <div class="card border-0 shadow-sm rounded-4 mb-4 sys-card">
            <div class="card-header sys-card-header p-3.5">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="fs-15 fw-bold sys-card-title mb-0 d-flex align-items-center gap-2">
                  <i class="ri-shield-keyhole-line text-warning"></i>
                  <span>Framework Laravel &amp; Konfigurasi Lingkungan</span>
                </h5>
                <div class="d-flex align-items-center gap-1.5">
                  <span 
                    class="badge px-2.5 py-1 rounded-pill fs-11 fw-bold text-uppercase"
                    :class="frameworkInfo.app_env === 'production' ? 'badge-subtle-success' : 'badge-subtle-warning'"
                  >
                    Env: {{ frameworkInfo.app_env }}
                  </span>
                  <span 
                    class="badge px-2.5 py-1 rounded-pill fs-11 fw-bold"
                    :class="frameworkInfo.app_debug ? 'badge-subtle-danger' : 'badge-subtle-success'"
                  >
                    Debug: {{ frameworkInfo.app_debug ? 'Aktif' : 'Off' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 sys-table">
                  <tbody>
                    <tr>
                      <td class="sys-table-label" style="width: 35%;">Environment Aplikasi (APP_ENV)</td>
                      <td class="sys-table-value">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                          <span 
                            class="badge px-2.5 py-1 rounded-pill fs-11 fw-bold text-uppercase"
                            :class="frameworkInfo.app_env === 'production' ? 'badge-subtle-success' : 'badge-subtle-warning'"
                          >
                            <i :class="frameworkInfo.app_env === 'production' ? 'ri-shield-check-fill' : 'ri-tools-fill'" class="me-1"></i>
                            {{ frameworkInfo.app_env }}
                          </span>
                          <span class="fs-12 text-muted">
                            {{ frameworkInfo.app_env === 'production' 
                              ? 'Mode Produksi Resmi (Optimalisasi Keamanan & Performa Publik Aktif).' 
                              : (frameworkInfo.app_env === 'local' 
                                ? 'Mode Pengembangan Lokal (Development Environment).' 
                                : 'Lingkungan: ' + frameworkInfo.app_env) }}
                          </span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Mode Debug Aplikasi (APP_DEBUG)</td>
                      <td class="sys-table-value">
                        <div>
                          <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span 
                              class="badge px-2.5 py-1 rounded-pill fs-11 fw-bold"
                              :class="frameworkInfo.app_debug ? 'badge-subtle-danger' : 'badge-subtle-success'"
                            >
                              <i :class="frameworkInfo.app_debug ? 'ri-bug-fill' : 'ri-shield-check-fill'" class="me-1"></i>
                              {{ frameworkInfo.app_debug ? 'Aktif (Debug Mode ON)' : 'Nonaktif (Debug Mode OFF)' }}
                            </span>
                            <span v-if="!frameworkInfo.app_debug" class="fs-12 text-muted">
                              Aman untuk server produksi. Rincian error teknis &amp; baris kode internal disembunyikan.
                            </span>
                          </div>
                          <div v-if="frameworkInfo.app_debug" class="alert alert-danger-subtle py-1.5 px-3 rounded-3 fs-12 mt-2 mb-0 border border-danger-subtle d-flex align-items-start gap-2">
                            <i class="ri-alert-line text-danger fs-15 mt-0.5"></i>
                            <div>
                              <strong class="text-danger">Peringatan Keamanan Server:</strong>
                              <span class="text-danger-emphasis ms-1">Mode debug aktif akan menampilkan jejak stack trace kode secara transparan saat terjadi kesalahan sistem. Sangat dianjurkan mengubah <code>APP_DEBUG=false</code> di file <code>.env</code> sebelum digunakan publik!</span>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Versi Framework Laravel</td>
                      <td class="sys-table-value font-monospace fw-bold text-dark-emphasis">
                        Laravel v{{ frameworkInfo.laravel_version }}
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Web Server &amp; Engine Runner</td>
                      <td class="sys-table-value">
                        <span class="fw-semibold">{{ frameworkInfo.server_software }}</span>
                        <span v-if="frameworkInfo.octane_server && frameworkInfo.octane_server !== 'None'" class="badge badge-subtle-info ms-2 fs-10">
                          Octane: {{ frameworkInfo.octane_server }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Status Mode Pemeliharaan (Maintenance)</td>
                      <td class="sys-table-value">
                        <span class="badge" :class="frameworkInfo.maintenance ? 'badge-subtle-danger' : 'badge-subtle-success'">
                          <i :class="frameworkInfo.maintenance ? 'ri-error-warning-fill' : 'ri-checkbox-circle-fill'" class="me-1"></i>
                          {{ frameworkInfo.maintenance ? 'Aktif (Situs Ditutup Sementara)' : 'Normal (Aplikasi Online)' }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Status Cache Bootstrap Framework</td>
                      <td class="sys-table-value">
                        <div class="d-flex align-items-center gap-2 flex-wrap fs-11 font-monospace">
                          <span class="badge" :class="frameworkInfo.config_cached ? 'badge-subtle-success' : 'badge-subtle-secondary'">
                            Config: {{ frameworkInfo.config_cached ? 'Cached' : 'Not Cached' }}
                          </span>
                          <span class="badge" :class="frameworkInfo.routes_cached ? 'badge-subtle-success' : 'badge-subtle-secondary'">
                            Routes: {{ frameworkInfo.routes_cached ? 'Cached' : 'Not Cached' }}
                          </span>
                          <span class="badge" :class="frameworkInfo.events_cached ? 'badge-subtle-success' : 'badge-subtle-secondary'">
                            Events: {{ frameworkInfo.events_cached ? 'Cached' : 'Not Cached' }}
                          </span>
                        </div>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">URL Dasar Aplikasi (APP_URL)</td>
                      <td class="sys-table-value font-monospace text-primary">
                        {{ frameworkInfo.app_url || '-' }}
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Bahasa (Locale) &amp; Zona Waktu</td>
                      <td class="sys-table-value">
                        <span class="badge badge-subtle-secondary me-2">Locale: {{ frameworkInfo.app_locale }}</span>
                        <span class="badge badge-subtle-info">{{ frameworkInfo.app_timezone }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Card 3: Database & Backend Engine -->
          <div class="card border-0 shadow-sm rounded-4 mb-4 sys-card">
            <div class="card-header sys-card-header p-3.5">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="fs-15 fw-bold sys-card-title mb-0 d-flex align-items-center gap-2">
                  <i class="ri-database-line text-success"></i>
                  <span>Database PostgreSQL &amp; Layanan Cache</span>
                </h5>
                <span class="badge badge-subtle-success px-2.5 py-1 rounded-pill fs-11">
                  {{ dbInfo.default_driver }}
                </span>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 sys-table">
                  <tbody>
                    <tr>
                      <td class="sys-table-label" style="width: 35%;">Status Koneksi Database</td>
                      <td class="sys-table-value">
                        <span class="badge" :class="dbInfo.status === 'Terhubung' ? 'badge-subtle-success' : 'badge-subtle-danger'">
                          <i :class="dbInfo.status === 'Terhubung' ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'" class="me-1"></i>
                          {{ dbInfo.status }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Versi Server Database</td>
                      <td class="sys-table-value font-monospace fs-12">{{ dbInfo.version }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Nama Database Aktif</td>
                      <td class="sys-table-value font-monospace fw-bold text-success">{{ dbInfo.database_name }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Ukuran Database Fisik</td>
                      <td class="sys-table-value font-monospace">{{ dbInfo.size }}</td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Driver Cache &amp; Sesi</td>
                      <td class="sys-table-value">
                        <span class="badge badge-subtle-primary me-2 font-monospace">{{ cacheInfo.cache_driver }}</span>
                        <span class="fs-11 text-muted">Session Driver: {{ cacheInfo.session_driver }}</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Status Koneksi Redis</td>
                      <td class="sys-table-value">
                        <span class="badge" :class="cacheInfo.redis_status?.includes('Aktif') ? 'badge-subtle-success' : 'badge-subtle-secondary'">
                          {{ cacheInfo.redis_status }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td class="sys-table-label">Driver Queue &amp; Realtime</td>
                      <td class="sys-table-value font-monospace">
                        Queue: {{ cacheInfo.queue_driver }} | Broadcast: {{ cacheInfo.broadcast }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Card 3: Critical Extensions Checklist -->
          <div class="card border-0 shadow-sm rounded-4 sys-card">
            <div class="card-header sys-card-header p-3.5">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="fs-15 fw-bold sys-card-title mb-0 d-flex align-items-center gap-2">
                  <i class="ri-puzzle-line text-warning"></i>
                  <span>Daftar Ekstensi PHP Krusial</span>
                </h5>
                <span class="fs-11 text-muted">10 Modul Terpantau</span>
              </div>
            </div>
            <div class="card-body p-3">
              <div class="row g-2.5">
                <div v-for="ext in extensionsList" :key="ext.name" class="col-md-6">
                  <div class="p-2.5 rounded-3 border ext-item-box d-flex align-items-center justify-content-between">
                    <div class="min-w-0 me-2">
                      <div class="d-flex align-items-center gap-1.5">
                        <span class="fs-12 fw-bold text-dark-emphasis">{{ ext.label }}</span>
                        <span class="font-monospace fs-10 text-muted">({{ ext.name }})</span>
                      </div>
                      <div class="fs-11 text-muted text-truncate">{{ ext.desc }}</div>
                    </div>
                    <span 
                      class="badge rounded-pill fs-10 px-2 py-0.5 flex-shrink-0"
                      :class="ext.installed ? 'badge-subtle-success' : (ext.required ? 'badge-subtle-danger' : 'badge-subtle-secondary')"
                    >
                      <i :class="ext.installed ? 'ri-check-line me-0.5' : 'ri-close-line me-0.5'"></i>
                      {{ ext.installed ? 'Terpasang' : 'Tidak Ada' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Disk Storage & Quick Maintenance -->
        <div class="col-lg-4">
          
          <!-- Disk Storage Card -->
          <div class="card border-0 shadow-sm rounded-4 mb-4 sys-card">
            <div class="card-header sys-card-header p-3.5">
              <h5 class="fs-15 fw-bold sys-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-hard-drive-2-line text-info"></i>
                <span>Ruang Penyimpanan Server</span>
              </h5>
            </div>
            <div class="card-body p-4">
              <div class="d-flex align-items-center justify-content-between mb-1.5">
                <span class="fs-12 text-muted">Kapasitas Disk Terpakai</span>
                <span class="fs-13 fw-bold text-dark-emphasis">{{ storageInfo.disk_used_percent }}%</span>
              </div>
              <div class="progress rounded-pill mb-3" style="height: 10px;">
                <div 
                  class="progress-bar rounded-pill" 
                  role="progressbar" 
                  :style="{ width: `${storageInfo.disk_used_percent}%` }"
                  :class="storageInfo.disk_used_percent > 85 ? 'bg-danger' : (storageInfo.disk_used_percent > 65 ? 'bg-warning' : 'bg-primary')"
                ></div>
              </div>
              <div class="d-flex align-items-center justify-content-between fs-11 text-muted mb-4">
                <span>Tersedia: <strong class="text-dark-emphasis">{{ storageInfo.disk_free }}</strong></span>
                <span>Total: <strong class="text-dark-emphasis">{{ storageInfo.disk_total }}</strong></span>
              </div>

              <div class="border-top pt-3">
                <div class="fs-12 fw-bold text-dark-emphasis mb-2">Izin Akses Tulis (*Writable Permissions*):</div>
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 fs-12">
                  <li class="d-flex align-items-center justify-content-between">
                    <span class="font-monospace text-muted">storage/</span>
                    <span class="badge" :class="storageInfo.storage_path_writable ? 'badge-subtle-success' : 'badge-subtle-danger'">
                      {{ storageInfo.storage_path_writable ? 'Writable' : 'Read-Only' }}
                    </span>
                  </li>
                  <li class="d-flex align-items-center justify-content-between">
                    <span class="font-monospace text-muted">bootstrap/cache/</span>
                    <span class="badge" :class="storageInfo.bootstrap_cache_writable ? 'badge-subtle-success' : 'badge-subtle-danger'">
                      {{ storageInfo.bootstrap_cache_writable ? 'Writable' : 'Read-Only' }}
                    </span>
                  </li>
                  <li class="d-flex align-items-center justify-content-between">
                    <span class="font-monospace text-muted">public/</span>
                    <span class="badge" :class="storageInfo.public_path_writable ? 'badge-subtle-success' : 'badge-subtle-danger'">
                      {{ storageInfo.public_path_writable ? 'Writable' : 'Read-Only' }}
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Quick Maintenance Tools Card -->
          <div class="card border-0 shadow-sm rounded-4 sys-card">
            <div class="card-header sys-card-header p-3.5">
              <h5 class="fs-15 fw-bold sys-card-title mb-0 d-flex align-items-center gap-2">
                <i class="ri-sparkling-fill text-warning"></i>
                <span>Tindakan Pemeliharaan Cache</span>
              </h5>
            </div>
            <div class="card-body p-4 d-flex flex-column gap-2.5">
              <p class="fs-12 text-muted mb-2">
                Gunakan tombol berikut jika Anda baru saja mengubah kode, rute URL, atau memperbarui berkas konfigurasi sistem:
              </p>

              <!-- Button All Clear -->
              <button 
                type="button" 
                class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2 fs-13 fw-bold rounded-3 shadow-sm"
                :disabled="isClearingCache"
                @click="clearCache('all')"
              >
                <span v-if="isClearingCache && activeCacheType === 'all'" class="spinner-border spinner-border-sm"></span>
                <i v-else class="ri-delete-bin-2-line fs-16"></i>
                <span>Bersihkan Seluruh Cache (*Optimize Clear*)</span>
              </button>

              <div class="row g-2 mt-1">
                <div class="col-6">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary w-100 py-1.5 fs-11 rounded-3 d-flex align-items-center justify-content-center gap-1"
                    :disabled="isClearingCache"
                    @click="clearCache('route')"
                  >
                    <i class="ri-route-line"></i>
                    <span>Cache Rute</span>
                  </button>
                </div>
                <div class="col-6">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary w-100 py-1.5 fs-11 rounded-3 d-flex align-items-center justify-content-center gap-1"
                    :disabled="isClearingCache"
                    @click="clearCache('view')"
                  >
                    <i class="ri-layout-line"></i>
                    <span>Cache View</span>
                  </button>
                </div>
                <div class="col-6">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary w-100 py-1.5 fs-11 rounded-3 d-flex align-items-center justify-content-center gap-1"
                    :disabled="isClearingCache"
                    @click="clearCache('config')"
                  >
                    <i class="ri-settings-line"></i>
                    <span>Cache Config</span>
                  </button>
                </div>
                <div class="col-6">
                  <button 
                    type="button" 
                    class="btn btn-outline-secondary w-100 py-1.5 fs-11 rounded-3 d-flex align-items-center justify-content-center gap-1"
                    :disabled="isClearingCache"
                    @click="clearCache('application')"
                  >
                    <i class="ri-database-line"></i>
                    <span>Cache Data</span>
                  </button>
                </div>
              </div>

              <div class="alert alert-info py-2 px-2.5 fs-11 rounded-3 mb-0 mt-2 d-flex align-items-start gap-1.5">
                <i class="ri-information-line fs-14 flex-shrink-0 mt-0.5"></i>
                <span>Pembersihan cache dilakukan aman tanpa mengganggu data pendaftar dan sesi aktif pengguna.</span>
              </div>
            </div>
          </div>

        </div>

      </div>

    </div>
  </VelzonLayout>
</template>

<style scoped>
/* Metric Cards */
.metric-card {
  background: #ffffff;
  border: 1px solid #e2e8f0 !important;
  transition: all 0.2s ease;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
}

.metric-card-primary {
  background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);
  border-left: 4px solid #3b82f6 !important;
}

.metric-card-success {
  background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
  border-left: 4px solid #10b981 !important;
}

.metric-card-warning {
  background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
  border-left: 4px solid #f59e0b !important;
}

.metric-card-info {
  background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
  border-left: 4px solid #0ea5e9 !important;
}

.metric-icon-box {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  flex-shrink: 0;
}

.metric-icon-box.icon-primary {
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
}

.metric-icon-box.icon-success {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.metric-icon-box.icon-warning {
  background: #fffbeb;
  color: #d97706;
  border: 1px solid #fde68a;
}

.metric-icon-box.icon-info {
  background: #f0f9ff;
  color: #0284c7;
  border: 1px solid #bae6fd;
}

.metric-label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  margin-bottom: 2px;
}

.metric-val {
  font-size: 17px;
  font-weight: 700;
  line-height: 1.2;
}

.metric-sub {
  font-size: 11px;
  color: #64748b;
  margin-top: 3px;
}

/* System Cards */
.sys-card {
  background: #ffffff;
  border: 1px solid #e2e8f0 !important;
}

.sys-card-header {
  border-bottom: 1px solid #f1f5f9;
  background: transparent;
}

.sys-card-title {
  color: #0f172a;
}

/* System Table */
.sys-table tbody tr td {
  padding: 11px 16px;
  font-size: 12.5px;
}

.sys-table-label {
  color: #475569;
  font-weight: 600;
}

.sys-table-value {
  color: #0f172a;
}

.ext-item-box {
  background: #f8fafc;
  border-color: #e2e8f0 !important;
  transition: all 0.2s ease;
}

.ext-item-box:hover {
  background: #f1f5f9;
}

/* Subtle Badges - Light Mode */
.badge-subtle-primary {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.badge-subtle-success {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.badge-subtle-warning {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
}

.badge-subtle-info {
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.badge-subtle-danger {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.badge-subtle-secondary {
  background: #f8fafc;
  color: #475569;
  border: 1px solid #cbd5e1;
}

/* =============================================================================
   DARK THEME OVERRIDES (Strict Rule #2 & Rule #3 Compliance)
   ============================================================================= */
[data-bs-theme="dark"] .metric-card {
  background: #121824 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4) !important;
}

[data-bs-theme="dark"] .metric-card-primary {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, #121824 100%) !important;
  border-left: 4px solid #3b82f6 !important;
}

[data-bs-theme="dark"] .metric-card-success {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, #121824 100%) !important;
  border-left: 4px solid #10b981 !important;
}

[data-bs-theme="dark"] .metric-card-warning {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, #121824 100%) !important;
  border-left: 4px solid #f59e0b !important;
}

[data-bs-theme="dark"] .metric-card-info {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.15) 0%, #121824 100%) !important;
  border-left: 4px solid #0ea5e9 !important;
}

[data-bs-theme="dark"] .metric-icon-box.icon-primary {
  background: rgba(59, 130, 246, 0.25) !important;
  color: #93c5fd !important;
  border-color: rgba(59, 130, 246, 0.45) !important;
}

[data-bs-theme="dark"] .metric-icon-box.icon-success {
  background: rgba(16, 185, 129, 0.25) !important;
  color: #86efac !important;
  border-color: rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .metric-icon-box.icon-warning {
  background: rgba(245, 158, 11, 0.25) !important;
  color: #fde047 !important;
  border-color: rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .metric-icon-box.icon-info {
  background: rgba(14, 165, 233, 0.25) !important;
  color: #7dd3fc !important;
  border-color: rgba(14, 165, 233, 0.45) !important;
}

[data-bs-theme="dark"] .metric-label {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .metric-sub {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .sys-card {
  background: #121824 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .sys-card-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .sys-card-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .sys-table tbody tr {
  border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
}

[data-bs-theme="dark"] .sys-table-label {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .sys-table-value {
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .ext-item-box {
  background: rgba(255, 255, 255, 0.03) !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .ext-item-box:hover {
  background: rgba(255, 255, 255, 0.06) !important;
}

/* Luminous Badges in Dark Mode (Strict Rule #3) */
[data-bs-theme="dark"] .badge-subtle-primary {
  background: rgba(59, 130, 246, 0.2) !important;
  color: #93c5fd !important;
  border-color: rgba(59, 130, 246, 0.45) !important;
}

[data-bs-theme="dark"] .badge-subtle-success {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
  border-color: rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .badge-subtle-warning {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
  border-color: rgba(245, 158, 11, 0.45) !important;
}

[data-bs-theme="dark"] .badge-subtle-info {
  background: rgba(14, 165, 233, 0.2) !important;
  color: #7dd3fc !important;
  border-color: rgba(14, 165, 233, 0.45) !important;
}

[data-bs-theme="dark"] .badge-subtle-danger {
  background: rgba(239, 68, 68, 0.2) !important;
  color: #fca5a5 !important;
  border-color: rgba(239, 68, 68, 0.45) !important;
}

[data-bs-theme="dark"] .badge-subtle-secondary {
  background: rgba(255, 255, 255, 0.08) !important;
  color: #cbd5e1 !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
}
</style>
