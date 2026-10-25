<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  diagnostic: {
    type: Object,
    default: () => ({
      mode: 'auto',
      overall_status: 'disconnected',
      http_api: { status: 'untested', reachable: false, message: '' },
      local_artisan: { status: 'untested', path_exists: false, message: '' },
      recommended_action: '',
    }),
  },
});

const { success, error, info } = useToast();

const form = useForm({
  sipresensi_mode: props.settings.sipresensi_mode || 'auto',
  sipresensi_api_url: props.settings.sipresensi_api_url || 'https://sipresensi.maswahas.sch.id',
  sipresensi_api_token: props.settings.sipresensi_api_token || '',
  sipresensi_path: props.settings.sipresensi_path || 'D:\\1Laravel\\sipresensi',
  sipresensi_teachers_endpoint: props.settings.sipresensi_teachers_endpoint || '/api/v1/integration/pkl/teachers/all',
  sipresensi_verify_endpoint: props.settings.sipresensi_verify_endpoint || '/api/v1/integration/pkl/verify-credentials',
  sipresensi_auto_sync_on_login: Boolean(props.settings.sipresensi_auto_sync_on_login ?? true),
  sipresensi_timeout: Number(props.settings.sipresensi_timeout || 5),
});

const isSaving = ref(false);
const isTestingConn = ref(false);
const isTestingSync = ref(false);
const showToken = ref(false);

const liveDiagnostic = ref({ ...props.diagnostic });
const teachersPreview = ref(null);
const showPreviewModal = ref(false);

// Mode Dropdown Options for CustomSelect
const modeOptions = [
  { value: 'auto', label: '⚡ Otomatis (Prioritas REST API, Fallback Runner Lokal)' },
  { value: 'http_api', label: '🌐 Hanya REST API HTTP Endpoint' },
  { value: 'local_artisan', label: '💻 Hanya Local Artisan CLI Runner' },
];

function submitForm() {
  isSaving.value = true;
  form.post('/admin/pengaturan/sipresensi', {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      success('Konfigurasi API Sipresensi berhasil disimpan.', 'Berhasil Disimpan');
      runConnectionTest(false);
    },
    onError: (errs) => {
      isSaving.value = false;
      const firstErr = Object.values(errs)[0];
      error(firstErr || 'Gagal menyimpan konfigurasi. Periksa isian form.');
    },
  });
}

async function runConnectionTest(showSuccessToast = true) {
  isTestingConn.value = true;
  try {
    const res = await fetch('/admin/pengaturan/sipresensi/test-connection', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    });
    const json = await res.json();
    if (json.success && json.diagnostic) {
      liveDiagnostic.value = json.diagnostic;
      if (showSuccessToast) {
        if (json.diagnostic.overall_status === 'connected_api') {
          success('REST API Sipresensi online dan merespons dengan cepat!', 'Koneksi Sukses');
        } else if (json.diagnostic.overall_status === 'connected_artisan') {
          info('Artisan Runner Lokal siap digunakan (HTTP API offline/dilewati).', 'Koneksi Lokal Siap');
        } else {
          error('Layanan Sipresensi belum dapat dihubungi. Periksa URL atau folder lokal.', 'Gagal Terhubung');
        }
      }
    }
  } catch (err) {
    error('Gagal menjalankan uji koneksi: ' + err.message);
  } finally {
    isTestingConn.value = false;
  }
}

async function runSyncTest() {
  isTestingSync.value = true;
  teachersPreview.value = null;
  try {
    const res = await fetch('/admin/pengaturan/sipresensi/test-sync', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    });
    const json = await res.json();
    if (json.success && Array.isArray(json.data)) {
      teachersPreview.value = json;
      showPreviewModal.value = true;
      success(`Berhasil menarik ${json.data.length} data guru melalui jalur ${json.source === 'http_api' ? 'REST API' : 'Artisan CLI Lokal'}.`, 'Tarik Data Sukses');
    } else {
      error(json.message || 'Gagal menarik data guru dari Sipresensi.');
    }
  } catch (err) {
    error('Terjadi kesalahan saat menarik data: ' + err.message);
  } finally {
    isTestingSync.value = false;
  }
}
</script>

<template>
  <Head title="Konfigurasi API Sipresensi | Pengaturan Sistem" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-1 fw-bold text-dark-emphasis d-flex align-items-center gap-2">
                <i class="ri-shield-keyhole-line text-success fs-22"></i>
                Konfigurasi & Integrasi API Sipresensi
              </h4>
              <p class="text-muted fs-12 mb-0">
                Atur endpoint REST API, kredensial token, mode integrasi, serta direktori runner lokal untuk autentikasi SSO guru dan sinkronisasi akun panitia.
              </p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 shadow-sm px-3"
                @click="runConnectionTest(true)"
                :disabled="isTestingConn || isSaving"
                title="Uji status koneksi HTTP API dan runner lokal"
              >
                <i v-if="isTestingConn" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-pulse-line"></i>
                <span>{{ isTestingConn ? 'Menguji...' : 'Uji Koneksi' }}</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-success d-flex align-items-center gap-1 shadow-sm px-3"
                @click="runSyncTest"
                :disabled="isTestingSync || isSaving"
                title="Tarik sampel data guru dari Sipresensi"
              >
                <i v-if="isTestingSync" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-download-cloud-line"></i>
                <span>{{ isTestingSync ? 'Menarik Data...' : 'Uji Tarik Data Guru' }}</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm px-3"
                @click="submitForm"
                :disabled="isSaving || isTestingConn"
              >
                <i v-if="isSaving" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-line"></i>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Konfigurasi' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Submenu Tabs -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
              <ul class="nav nav-pills gap-2 flex-grow-1" role="tablist">
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/profil-sekolah"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13"
                  >
                    <i class="ri-building-line text-primary"></i>
                    <span>Profil Sekolah</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/mode-perawatan"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13"
                  >
                    <i class="ri-tools-line text-warning"></i>
                    <span>Mode Perawatan</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/seo-og"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13"
                  >
                    <i class="ri-search-eye-line text-info"></i>
                    <span>SEO & Open Graph</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/wa-gateway"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13"
                  >
                    <i class="ri-whatsapp-line text-success"></i>
                    <span>WA Gateway</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/kebijakan-pendaftaran"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13"
                  >
                    <i class="ri-shield-user-line text-purple"></i>
                    <span>Kebijakan Pendaftaran</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/login-admin"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13"
                  >
                    <i class="ri-image-edit-line text-primary"></i>
                    <span>Login Admin</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/sipresensi"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill active fw-semibold fs-13 shadow-sm"
                  >
                    <i class="ri-shield-keyhole-line"></i>
                    <span>API Sipresensi</span>
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Diagnostic Status Banner -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-body p-3.5">
              <div class="row align-items-center g-3">
                <div class="col-lg-7">
                  <div class="d-flex align-items-start gap-3">
                    <div 
                      class="rounded-circle p-2.5 d-flex align-items-center justify-content-center flex-shrink-0"
                      :class="{
                        'bg-success-subtle text-success': liveDiagnostic.overall_status === 'connected_api' || liveDiagnostic.overall_status === 'connected_artisan',
                        'bg-danger-subtle text-danger': liveDiagnostic.overall_status === 'disconnected'
                      }"
                      style="width: 48px; height: 48px;"
                    >
                      <i 
                        class="fs-24"
                        :class="{
                          'ri-shield-check-fill': liveDiagnostic.overall_status === 'connected_api' || liveDiagnostic.overall_status === 'connected_artisan',
                          'ri-shield-cross-fill': liveDiagnostic.overall_status === 'disconnected'
                        }"
                      ></i>
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h6 class="mb-0 fw-bold fs-15 text-dark-emphasis">Status Integrasi Sipresensi:</h6>
                        <span 
                          v-if="liveDiagnostic.overall_status === 'connected_api'"
                          class="badge bg-success-subtle text-success border border-success/20 px-2.5 py-1 rounded-pill fs-12 fw-semibold"
                        >
                          <i class="ri-checkbox-circle-fill me-1"></i> REST API Terhubung (Online)
                        </span>
                        <span 
                          v-else-if="liveDiagnostic.overall_status === 'connected_artisan'"
                          class="badge bg-info-subtle text-info border border-info/20 px-2.5 py-1 rounded-pill fs-12 fw-semibold"
                        >
                          <i class="ri-cpu-line me-1"></i> Local Runner Terhubung (Artisan Ready)
                        </span>
                        <span 
                          v-else
                          class="badge bg-danger-subtle text-danger border border-danger/20 px-2.5 py-1 rounded-pill fs-12 fw-semibold"
                        >
                          <i class="ri-close-circle-fill me-1"></i> Tidak Terhubung
                        </span>
                      </div>
                      <p class="text-muted fs-12 mb-0">
                        {{ liveDiagnostic.recommended_action || 'Tekan tombol "Uji Koneksi" untuk memeriksa kesehatan jalur komunikasi data.' }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-5">
                  <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                    <!-- HTTP API Chip -->
                    <div class="p-2 rounded-3 border bg-light d-flex align-items-center gap-2 fs-11">
                      <span class="text-muted fw-semibold">HTTP API:</span>
                      <span 
                        class="badge rounded-pill"
                        :class="liveDiagnostic.http_api.status === 'online' ? 'bg-success text-white' : 'bg-secondary text-white'"
                      >
                        {{ liveDiagnostic.http_api.status === 'online' ? `${liveDiagnostic.http_api.latency_ms} ms` : liveDiagnostic.http_api.status }}
                      </span>
                    </div>

                    <!-- Local Artisan Chip -->
                    <div class="p-2 rounded-3 border bg-light d-flex align-items-center gap-2 fs-11">
                      <span class="text-muted fw-semibold">Local Runner:</span>
                      <span 
                        class="badge rounded-pill"
                        :class="liveDiagnostic.local_artisan.artisan_exists ? 'bg-success text-white' : 'bg-warning text-dark'"
                      >
                        {{ liveDiagnostic.local_artisan.artisan_exists ? 'Artisan OK' : 'File Missing' }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Form Settings -->
      <form @submit.prevent="submitForm">
        <div class="row g-3">
          <!-- Left Column: Core API Settings -->
          <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis d-flex align-items-center gap-2">
                  <i class="ri-global-line text-primary"></i>
                  Pengaturan Koneksi REST API Sipresensi
                </h5>
              </div>
              <div class="card-body p-3.5">
                <!-- Mode Integrasi dengan CustomSelect -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                    Mode Integrasi Sipresensi <span class="text-danger">*</span>
                  </label>
                  <CustomSelect
                    v-model="form.sipresensi_mode"
                    :options="modeOptions"
                    placeholder="Pilih mode integrasi..."
                    size="md"
                    prefix-icon="ri-shield-keyhole-line"
                  />
                  <small class="text-muted fs-11 mt-1 d-block">
                    Pilihan "Otomatis" direkomendasikan: aplikasi akan mencoba memanggil REST API endpoint, dan bila server offline akan otomatis dialihkan ke runner lokal.
                  </small>
                </div>

                <!-- Base URL API -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                    Base URL API Sipresensi <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="ri-link-m"></i></span>
                    <input
                      v-model="form.sipresensi_api_url"
                      type="text"
                      class="form-control fs-12"
                      placeholder="Contoh: https://sipresensi.maswahas.sch.id"
                      required
                    />
                  </div>
                  <div v-if="form.errors.sipresensi_api_url" class="text-danger fs-11 mt-1">{{ form.errors.sipresensi_api_url }}</div>
                  <small class="text-muted fs-11 mt-1 d-block">
                    Alamat host server aplikasi Sipresensi tempat endpoint REST API dibuka.
                  </small>
                </div>

                <!-- API Bearer Token -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                    Token Rahasia API (Bearer Token)
                  </label>
                  <div class="position-relative">
                    <input
                      v-model="form.sipresensi_api_token"
                      :type="showToken ? 'text' : 'password'"
                      class="form-control fs-12 pe-5 font-monospace"
                      placeholder="Masukkan secret token API Sipresensi"
                    />
                    <button 
                      type="button" 
                      class="btn position-absolute top-50 end-0 translate-middle-y border-0 text-muted p-2"
                      @click="showToken = !showToken"
                      tabindex="-1"
                    >
                      <i :class="showToken ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                  </div>
                  <div v-if="form.errors.sipresensi_api_token" class="text-danger fs-11 mt-1">{{ form.errors.sipresensi_api_token }}</div>
                  <small class="text-muted fs-11 mt-1 d-block">
                    Token otorisasi untuk mengakses route integrasi Sipresensi (disertakan pada header Authorization: Bearer).
                  </small>
                </div>

                <div class="row g-2">
                  <!-- Endpoint Data Guru -->
                  <div class="col-md-6 mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                      Endpoint Data Guru <span class="text-danger">*</span>
                    </label>
                    <input
                      v-model="form.sipresensi_teachers_endpoint"
                      type="text"
                      class="form-control fs-12 font-monospace"
                      placeholder="/api/v1/integration/pkl/teachers/all"
                      required
                    />
                    <div v-if="form.errors.sipresensi_teachers_endpoint" class="text-danger fs-11 mt-1">{{ form.errors.sipresensi_teachers_endpoint }}</div>
                  </div>

                  <!-- Endpoint Verifikasi SSO -->
                  <div class="col-md-6 mb-3">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                      Endpoint Verifikasi SSO <span class="text-danger">*</span>
                    </label>
                    <input
                      v-model="form.sipresensi_verify_endpoint"
                      type="text"
                      class="form-control fs-12 font-monospace"
                      placeholder="/api/v1/integration/pkl/verify-credentials"
                      required
                    />
                    <div v-if="form.errors.sipresensi_verify_endpoint" class="text-danger fs-11 mt-1">{{ form.errors.sipresensi_verify_endpoint }}</div>
                  </div>
                </div>

                <!-- Timeout -->
                <div class="mb-1">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                    Batas Waktu Timeout Request (Detik) <span class="text-danger">*</span>
                  </label>
                  <div class="input-group" style="max-width: 200px;">
                    <input
                      v-model.number="form.sipresensi_timeout"
                      type="number"
                      min="2"
                      max="60"
                      class="form-control fs-12"
                      required
                    />
                    <span class="input-group-text bg-light text-muted fs-12">Detik</span>
                  </div>
                  <small class="text-muted fs-11 mt-1 d-block">Waktu tunggu maksimal saat menghubungi REST API sebelum dialihkan ke runner lokal.</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Local Runner & Policy -->
          <div class="col-lg-5">
            <!-- Local Runner Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis d-flex align-items-center gap-2">
                  <i class="ri-folder-settings-line text-warning"></i>
                  Direktori Runner Sipresensi Lokal
                </h5>
              </div>
              <div class="card-body p-3.5">
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                    Path Instalasi Sipresensi Lokal
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="ri-folder-line"></i></span>
                    <input
                      v-model="form.sipresensi_path"
                      type="text"
                      class="form-control fs-12 font-monospace"
                      placeholder="D:\1Laravel\sipresensi"
                    />
                  </div>
                  <div v-if="form.errors.sipresensi_path" class="text-danger fs-11 mt-1">{{ form.errors.sipresensi_path }}</div>
                  <small class="text-muted fs-11 mt-1 d-block">
                    Folder tempat file <code>artisan</code> dan <code>.env</code> milik aplikasi Sipresensi berada.
                  </small>
                </div>

                <!-- Callout Info Keamanan Isolasi -->
                <div class="p-3 rounded-3 border bg-info-subtle border-info/20 text-info-emphasis fs-12 mb-2">
                  <div class="d-flex gap-2">
                    <i class="ri-shield-check-line fs-18 flex-shrink-0"></i>
                    <div>
                      <strong>Isolasi Database Terjamin:</strong><br>
                      Sistem tidak pernah membuat koneksi langsung ke database Sipresensi. Eksekusi lokal dijalankan menggunakan CLI Artisan resmi di env terisolasi, sehingga struktur dan data kedua aplikasi 100% aman dan mandiri.
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- SSO Policy Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <h5 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis d-flex align-items-center gap-2">
                  <i class="ri-user-follow-line text-success"></i>
                  Kebijakan Autentikasi SSO Panitia
                </h5>
              </div>
              <div class="card-body p-3.5">
                <div class="form-check form-switch mb-2">
                  <input
                    v-model="form.sipresensi_auto_sync_on_login"
                    class="form-check-input"
                    type="checkbox"
                    id="auto-sync-check"
                  />
                  <label class="form-check-label fs-12 fw-semibold text-dark-emphasis" for="auto-sync-check">
                    Otomatis Buat Akun Panitia Saat Login SSO
                  </label>
                </div>
                <p class="text-muted fs-11 mb-0">
                  Jika aktif, guru dari Sipresensi yang berhasil login melalui tab SSO akan langsung otomatis didaftarkan sebagai Panitia di Semarak dan diarahkan membuat kata sandi baru.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky Bottom Actions -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="text-muted fs-12 d-flex align-items-center gap-2">
              <i class="ri-information-line text-primary fs-16"></i>
              <span>Perubahan konfigurasi akan langsung berlaku untuk proses login SSO dan sinkronisasi panitia.</span>
            </div>
            <div class="d-flex align-items-center gap-2 ms-auto">
              <button 
                type="button" 
                class="btn btn-outline-secondary px-3 fs-12"
                @click="runConnectionTest(true)"
                :disabled="isTestingConn || isSaving"
              >
                <i class="ri-refresh-line me-1"></i> Uji Ulang
              </button>
              <button 
                type="submit" 
                class="btn btn-primary px-4 fs-12 fw-semibold shadow-sm"
                :disabled="isSaving"
              >
                <i v-if="isSaving" class="ri-loader-4-line ri-spin me-1"></i>
                <i v-else class="ri-check-line me-1"></i>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Konfigurasi' }}</span>
              </button>
            </div>
          </div>
        </div>
      </form>

      <!-- Modal Preview Data Guru -->
      <div 
        v-if="showPreviewModal" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(0,0,0,0.5); z-index: 1055;"
      >
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
          <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 px-4">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar-xs bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center">
                  <i class="ri-team-line fs-16"></i>
                </div>
                <div>
                  <h6 class="modal-title fw-bold mb-0 text-dark-emphasis">Hasil Uji Tarik Data Guru Sipresensi</h6>
                  <small class="text-muted fs-11">
                    Sumber Jalur: <strong>{{ teachersPreview?.source === 'http_api' ? 'REST API Endpoint' : 'Artisan CLI Lokal' }}</strong> • Total: <strong>{{ teachersPreview?.data?.length || 0 }} Guru</strong>
                  </small>
                </div>
              </div>
              <button 
                type="button" 
                class="btn-close" 
                @click="showPreviewModal = false"
                aria-label="Tutup"
              ></button>
            </div>
            <div class="modal-body p-4">
              <div v-if="teachersPreview?.data?.length" class="table-responsive border rounded-3">
                <table class="table table-hover table-striped align-middle mb-0 fs-12">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-3 py-2">No</th>
                      <th class="py-2">NIP Guru</th>
                      <th class="py-2">Nama Lengkap</th>
                      <th class="py-2">Email</th>
                      <th class="py-2">No. WhatsApp</th>
                      <th class="pe-3 py-2 text-center">JK</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(t, idx) in teachersPreview.data.slice(0, 15)" :key="t.nip || idx">
                      <td class="ps-3 text-muted">{{ idx + 1 }}</td>
                      <td class="fw-bold font-monospace text-primary">{{ t.nip || '-' }}</td>
                      <td class="fw-semibold text-dark">{{ t.name }}</td>
                      <td class="text-muted font-monospace">{{ t.email || '-' }}</td>
                      <td class="text-muted font-monospace">{{ t.phone || '-' }}</td>
                      <td class="pe-3 text-center">
                        <span class="badge bg-light text-dark border">{{ t.gender || '-' }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-if="(teachersPreview?.data?.length || 0) > 15" class="text-muted fs-11 text-center mt-2 mb-0">
                Menampilkan 15 guru pertama dari total {{ teachersPreview.data.length }} data.
              </p>
            </div>
            <div class="modal-footer border-top py-2.5 px-4 bg-light rounded-bottom-4">
              <button 
                type="button" 
                class="btn btn-secondary btn-sm px-3" 
                @click="showPreviewModal = false"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.text-purple {
  color: #7c3aed !important;
}
</style>
