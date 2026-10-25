<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  }
});

const toast = useToast();

const form = useForm({
  registration_login_required: props.settings.registration_login_required || '0',
  google_client_id: props.settings.google_client_id || '',
  google_client_secret: props.settings.google_client_secret || '',
  allow_google_login: props.settings.allow_google_login || '1',
  allow_wa_otp_login: props.settings.allow_wa_otp_login || '1',
  allow_password_login: props.settings.allow_password_login || '1',
});

const showClientSecret = ref(false);
const isTogglingQuick = ref(false);

const registrantRedirectUri = computed(() => {
  if (typeof window !== 'undefined' && window.location) {
    return `${window.location.origin}/auth/google/callback`;
  }
  return '/auth/google/callback';
});

const adminRedirectUri = computed(() => {
  if (typeof window !== 'undefined' && window.location) {
    return `${window.location.origin}/admin/auth/google/callback`;
  }
  return '/admin/auth/google/callback';
});

function copyToClipboard(text, label) {
  if (typeof navigator !== 'undefined' && navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      toast.success(`${label} berhasil disalin ke clipboard!`);
    }).catch(() => {
      toast.info(`Salin URL: ${text}`);
    });
  } else {
    toast.info(`Salin URL: ${text}`);
  }
}

function submitForm() {
  form.post('/admin/pengaturan/kebijakan-pendaftaran', {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Pengaturan kebijakan pendaftaran dan otentikasi akun berhasil disimpan!');
    },
    onError: () => {
      toast.error('Gagal menyimpan pengaturan. Silakan periksa formulir.');
    }
  });
}

function quickToggleLoginRequired() {
  isTogglingQuick.value = true;
  router.post('/admin/pengaturan/kebijakan-pendaftaran/toggle-login', {}, {
    preserveScroll: true,
    onSuccess: () => {
      form.registration_login_required = form.registration_login_required === '1' ? '0' : '1';
      toast.success(
        form.registration_login_required === '1'
          ? 'Pendaftaran sekarang WAJIB LOGIN akun pendaftar.'
          : 'Pendaftaran sekarang BEBAS (langsung isi formulir).'
      );
    },
    onError: () => {
      toast.error('Gagal mengubah pengaturan secara instan.');
    },
    onFinish: () => {
      isTogglingQuick.value = false;
    }
  });
}
</script>

<template>
  <Head title="Kebijakan Pendaftaran & Akun Pendaftar - Pengaturan Admin" />

  <VelzonLayout>
    <div class="container-fluid policy-settings-page">
      
      <!-- Page Title & Breadcrumb -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-sm-0 fw-bold d-flex align-items-center gap-2 page-header-title">
                <i class="ri-shield-user-fill text-primary fs-22"></i>
                <span>Kebijakan Pendaftaran &amp; Akun Pendaftar</span>
              </h4>
              <p class="page-header-subtitle fs-12 mb-0 mt-1">
                Atur regulasi akses formulir pendaftaran lomba, kontrol kewajiban login akun pendaftar, dan aktifkan metode autentikasi yang fleksibel.
              </p>
            </div>
            <div class="page-title-right mt-2 mt-sm-0">
              <ol class="breadcrumb m-0 fs-12">
                <li class="breadcrumb-item"><Link href="/admin">Dashboard</Link></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active text-primary">Kebijakan Pendaftaran</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Submenu Tabs -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4 policy-tabs-card">
            <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
              <ul class="nav nav-pills gap-2 flex-grow-1" role="tablist">
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/profil-sekolah"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link"
                  >
                    <i class="ri-building-line text-warning"></i>
                    <span>Profil Sekolah</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/mode-perawatan"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link"
                  >
                    <i class="ri-tools-line text-warning"></i>
                    <span>Mode Perawatan</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/seo-og"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link"
                  >
                    <i class="ri-search-eye-line text-info"></i>
                    <span>SEO &amp; Open Graph</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/wa-gateway"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link"
                  >
                    <i class="ri-whatsapp-line text-success"></i>
                    <span>WA Gateway</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/kebijakan-pendaftaran"
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link-active fw-bold"
                  >
                    <i class="ri-user-settings-fill"></i>
                    <span>Kebijakan Pendaftaran</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/login-admin"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link"
                  >
                    <i class="ri-image-edit-line text-info"></i>
                    <span>Login Panel Admin</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/sipresensi"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill policy-tab-link"
                  >
                    <i class="ri-shield-keyhole-line text-success"></i>
                    <span>API Sipresensi</span>
                  </Link>
                </li>
              </ul>

              <div class="d-flex align-items-center gap-2">
                <a href="/layanan-registrasi" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 d-inline-flex align-items-center gap-1.5 fw-semibold">
                  <i class="ri-external-link-line"></i>
                  <span>Buka Layanan Registrasi</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Statistics Banner -->
      <div class="row g-3 mb-4">
        <!-- Stat 1 -->
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 stat-metric-card stat-metric-primary">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrap icon-primary">
                <i class="ri-user-shared-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Total Akun Pendaftar</div>
                <div class="stat-value text-primary">
                  {{ stats.total_registrants || 0 }} <span class="stat-unit">Akun Terdaftar</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stat 2 -->
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 stat-metric-card stat-metric-success">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrap icon-success">
                <i class="ri-file-shield-2-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Pendaftaran Terkait Akun</div>
                <div class="stat-value text-success">
                  {{ stats.total_regs_with_account || 0 }} <span class="stat-unit">Kontingen</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Stat 3 -->
        <div class="col-md-4">
          <div class="card border-0 shadow-sm rounded-4 stat-metric-card stat-metric-info">
            <div class="card-body p-3.5 d-flex align-items-center gap-3">
              <div class="stat-icon-wrap icon-info">
                <i class="ri-pie-chart-2-fill"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="stat-label">Total Seluruh Partisipan</div>
                <div class="stat-value text-info">
                  {{ stats.total_submitted_regs || 0 }} <span class="stat-unit">Pendaftaran</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Form Settings -->
      <form @submit.prevent="submitForm">
        <div class="row g-4">
          
          <!-- Left Column: Core Policy Switch & Methods -->
          <div class="col-lg-7">
            
            <!-- Core Access Mode Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 policy-card">
              <div class="card-header policy-card-header p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <div>
                    <h5 class="fs-16 fw-bold policy-card-title mb-1 d-flex align-items-center gap-2">
                      <i class="ri-shield-keyhole-fill text-warning"></i>
                      <span>Kebijakan Akses Formulir Pendaftaran</span>
                    </h5>
                    <p class="fs-12 policy-card-desc mb-0">Tentukan apakah pendaftar wajib login akun atau bebas langsung mengisi formulir lomba.</p>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <span
                      class="policy-status-pill"
                      :class="form.registration_login_required === '1' ? 'status-pill-wajib' : 'status-pill-bebas'"
                    >
                      <i :class="form.registration_login_required === '1' ? 'ri-lock-2-fill' : 'ri-lock-unlock-fill'" class="me-1"></i>
                      {{ form.registration_login_required === '1' ? 'WAJIB LOGIN' : 'BEBAS DAFTAR' }}
                    </span>

                    <button
                      type="button"
                      class="btn btn-sm btn-quick-toggle"
                      :disabled="isTogglingQuick"
                      @click="quickToggleLoginRequired"
                      title="Ubah mode pendaftaran secara instan ke database"
                    >
                      <i :class="isTogglingQuick ? 'ri-loader-4-line ri-spin' : 'ri-refresh-line'" class="me-1"></i>
                      <span>{{ isTogglingQuick ? 'Mengubah...' : 'Ubah Cepat' }}</span>
                    </button>
                  </div>
                </div>
              </div>

              <div class="card-body p-4">
                
                <!-- Option 1: Bebas Login -->
                <div
                  class="p-3.5 rounded-4 border mb-3 cursor-pointer transition-all policy-option-box"
                  :class="form.registration_login_required === '0' ? 'is-selected-bebas' : ''"
                  @click="form.registration_login_required = '0'"
                >
                  <div class="d-flex align-items-start gap-3">
                    <div class="pt-0.5">
                      <input
                        type="radio"
                        name="policy_choice"
                        value="0"
                        v-model="form.registration_login_required"
                        class="form-check-input policy-radio"
                      />
                    </div>
                    <div class="flex-grow-1 min-w-0">
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1.5">
                        <h6 class="fs-14 fw-bold policy-option-title mb-0 d-flex align-items-center gap-2">
                          <i class="ri-checkbox-circle-fill text-success" v-if="form.registration_login_required === '0'"></i>
                          <span>Pendaftaran Bebas (Tanpa Wajib Login)</span>
                        </h6>
                        <span class="policy-subtle-badge badge-subtle-success">
                          <i class="ri-flashlight-fill me-1"></i> Rekomendasi Cepat
                        </span>
                      </div>
                      <p class="fs-12 policy-option-desc mb-2">
                        Pendaftar, guru pendamping, dan siswa dapat <strong class="text-contrast">langsung mengisi formulir pendaftaran</strong> di halaman <code class="policy-code">/layanan-registrasi</code> tanpa harus membuat akun atau login terlebih dahulu.
                      </p>
                      <ul class="fs-12 policy-bullet-list ps-3 mb-0">
                        <li>Formulir langsung terbuka untuk umum tanpa hambatan kata sandi.</li>
                        <li>Pendaftar tetap dapat membuat akun pendaftar kapan saja jika ingin memantau dashboard kontingen.</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- Option 2: Wajib Login -->
                <div
                  class="p-3.5 rounded-4 border cursor-pointer transition-all policy-option-box"
                  :class="form.registration_login_required === '1' ? 'is-selected-wajib' : ''"
                  @click="form.registration_login_required = '1'"
                >
                  <div class="d-flex align-items-start gap-3">
                    <div class="pt-0.5">
                      <input
                        type="radio"
                        name="policy_choice"
                        value="1"
                        v-model="form.registration_login_required"
                        class="form-check-input policy-radio"
                      />
                    </div>
                    <div class="flex-grow-1 min-w-0">
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1.5">
                        <h6 class="fs-14 fw-bold policy-option-title mb-0 d-flex align-items-center gap-2">
                          <i class="ri-checkbox-circle-fill text-primary" v-if="form.registration_login_required === '1'"></i>
                          <span>Wajib Login Akun Pendaftar</span>
                        </h6>
                        <span class="policy-subtle-badge badge-subtle-warning">
                          <i class="ri-shield-star-fill me-1"></i> Kontrol Ketat
                        </span>
                      </div>
                      <p class="fs-12 policy-option-desc mb-2">
                        Pendaftar <strong class="text-contrast">wajib memiliki akun dan masuk (login)</strong> terlebih dahulu sebelum diizinkan mengakses dan mengirimkan formulir pendaftaran lomba.
                      </p>
                      <ul class="fs-12 policy-bullet-list ps-3 mb-0">
                        <li>Pengunjung tanpa login akan dialihkan secara otomatis ke halaman login pendaftar.</li>
                        <li>Data pendamping, kontak WhatsApp, email, dan asal sekolah otomatis terisi dari akun pendaftar.</li>
                        <li>Pendaftar dapat mengedit data delegasi &amp; mengunggah berkas kapan saja selagi pendaftaran dibuka.</li>
                      </ul>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- Authentication Methods Card -->
            <div class="card border-0 shadow-sm rounded-4 policy-card">
              <div class="card-header policy-card-header p-4">
                <h5 class="fs-16 fw-bold policy-card-title mb-1 d-flex align-items-center gap-2">
                  <i class="ri-fingerprint-fill text-info"></i>
                  <span>Metode Masuk (Login) Fleksibel untuk Pendaftar</span>
                </h5>
                <p class="fs-12 policy-card-desc mb-0">Aktifkan atau nonaktifkan jalur autentikasi akun pendaftar sesuai kenyamanan peserta:</p>
              </div>

              <div class="card-body p-4 d-flex flex-column gap-3">
                
                <!-- Method 1: Password -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border method-item-box">
                  <div class="d-flex align-items-center gap-3">
                    <div class="method-icon-wrap icon-primary">
                      <i class="ri-lock-password-fill"></i>
                    </div>
                    <div>
                      <div class="fs-13 fw-bold method-title">Login dengan Username &amp; Password</div>
                      <div class="fs-11 method-desc">Pendaftar masuk menggunakan Nomor HP WhatsApp / Email serta Password pendaftaran.</div>
                    </div>
                  </div>
                  <div class="form-check form-switch fs-18 mb-0">
                    <input
                      v-model="form.allow_password_login"
                      true-value="1"
                      false-value="0"
                      class="form-check-input policy-switch"
                      type="checkbox"
                      role="switch"
                    />
                  </div>
                </div>

                <!-- Method 2: WA OTP -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border method-item-box">
                  <div class="d-flex align-items-center gap-3">
                    <div class="method-icon-wrap icon-success">
                      <i class="ri-whatsapp-fill"></i>
                    </div>
                    <div>
                      <div class="fs-13 fw-bold method-title">Login Cepat dengan OTP WhatsApp</div>
                      <div class="fs-11 method-desc">Mengirimkan 6 digit kode OTP verifikasi secara otomatis via OpenWA Gateway tanpa password.</div>
                    </div>
                  </div>
                  <div class="form-check form-switch fs-18 mb-0">
                    <input
                      v-model="form.allow_wa_otp_login"
                      true-value="1"
                      false-value="0"
                      class="form-check-input policy-switch"
                      type="checkbox"
                      role="switch"
                    />
                  </div>
                </div>

                <!-- Method 3: Google -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border method-item-box">
                  <div class="d-flex align-items-center gap-3">
                    <div class="method-icon-wrap icon-danger">
                      <i class="ri-google-fill"></i>
                    </div>
                    <div>
                      <div class="fs-13 fw-bold method-title">Login dengan Akun Google</div>
                      <div class="fs-11 method-desc">Akses instan 1-klik menggunakan akun Google aktif pendaftar (OAuth 2.0).</div>
                    </div>
                  </div>
                  <div class="form-check form-switch fs-18 mb-0">
                    <input
                      v-model="form.allow_google_login"
                      true-value="1"
                      false-value="0"
                      class="form-check-input policy-switch"
                      type="checkbox"
                      role="switch"
                    />
                  </div>
                </div>

              </div>
            </div>

          </div>

          <!-- Right Column: Google OAuth Setup & Quick Actions -->
          <div class="col-lg-5">
            
            <!-- Google OAuth Config Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 policy-card overflow-hidden">
              <div class="card-header policy-card-header p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2.5 mb-2.5">
                  <div class="d-flex align-items-center gap-2.5 min-w-0">
                    <div class="oauth-icon-wrap flex-shrink-0">
                      <i class="ri-google-fill text-danger fs-18"></i>
                    </div>
                    <div>
                      <h5 class="fs-15 fw-bold policy-card-title mb-0 lh-sm">
                        Kredensial Google OAuth 2.0
                      </h5>
                      <span class="fs-11 text-muted">Akses Terpadu Google Sign-In</span>
                    </div>
                  </div>
                  <span class="badge badge-oauth-scope px-2.5 py-1 rounded-pill fs-11 fw-semibold d-inline-flex align-items-center gap-1 flex-shrink-0">
                    <i class="ri-global-line"></i>
                    <span>Pendaftar &amp; Panitia</span>
                  </span>
                </div>
                <p class="fs-12 policy-card-desc mb-0 lh-base">
                  Konfigurasi satu pintu Google Cloud Console untuk otentikasi akun Google <strong class="text-contrast">Pendaftar (Portal Publik)</strong> sekaligus <strong class="text-contrast">Panitia / Admin</strong>.
                </p>
              </div>

              <div class="card-body p-4 d-flex flex-column gap-3.5">
                
                <div>
                  <label class="form-label fs-13 fw-semibold policy-label mb-1">
                    Google Client ID
                  </label>
                  <input
                    v-model="form.google_client_id"
                    type="text"
                    class="form-control policy-input fs-12 font-monospace py-2"
                    placeholder="xxxxxxxxxxxx-xxxxxxxxxxxxxxxx.apps.googleusercontent.com"
                  />
                  <div class="fs-11 policy-helper mt-1">Dapatkan dari Google Cloud Console &gt; API &amp; Services &gt; Credentials.</div>
                </div>

                <div>
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fs-13 fw-semibold policy-label mb-0">
                      Google Client Secret
                    </label>
                    <button
                      type="button"
                      class="btn btn-sm btn-link p-0 text-decoration-none fs-12 text-primary"
                      @click="showClientSecret = !showClientSecret"
                    >
                      <i :class="showClientSecret ? 'ri-eye-off-line' : 'ri-eye-line'" class="me-0.5"></i>
                      <span>{{ showClientSecret ? 'Sembunyikan' : 'Tampilkan' }}</span>
                    </button>
                  </div>
                  <input
                    v-model="form.google_client_secret"
                    :type="showClientSecret ? 'text' : 'password'"
                    class="form-control policy-input fs-12 font-monospace py-2"
                    placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx"
                  />
                </div>

                <!-- Info Callout -->
                <div class="alert alert-info py-2.5 px-3 fs-11 rounded-3 mb-0 d-flex gap-2 align-items-start">
                  <i class="ri-information-line fs-15 text-primary flex-shrink-0 mt-0.5"></i>
                  <div>
                    Satu pasang Client ID &amp; Secret ini berlaku untuk seluruh sistem. Daftarkan <strong>kedua Authorized Redirect URI</strong> berikut pada Google Cloud Console Anda:
                  </div>
                </div>

                <!-- Authorized Redirect URI 1: Pendaftar -->
                <div class="p-3 rounded-3 border redirect-uri-container">
                  <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="fs-11 fw-bold text-uppercase redirect-label d-flex align-items-center gap-1.5">
                      <i class="ri-user-smile-line text-success fs-13"></i>
                      <span>1. URI Callback Portal Pendaftar</span>
                    </span>
                    <button
                      type="button"
                      class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-0.5 d-inline-flex align-items-center gap-1"
                      @click="copyToClipboard(registrantRedirectUri, 'URI Callback Portal Pendaftar')"
                      title="Salin URI Pendaftar"
                    >
                      <i class="ri-file-copy-line"></i>
                      <span>Salin URI</span>
                    </button>
                  </div>
                  <div class="p-2 rounded font-monospace fs-11 text-break border redirect-uri-box">
                    {{ registrantRedirectUri }}
                  </div>
                </div>

                <!-- Authorized Redirect URI 2: Panitia / Admin -->
                <div class="p-3 rounded-3 border redirect-uri-container">
                  <div class="d-flex align-items-center justify-content-between mb-1.5">
                    <span class="fs-11 fw-bold text-uppercase redirect-label d-flex align-items-center gap-1.5">
                      <i class="ri-shield-user-line text-primary fs-13"></i>
                      <span>2. URI Callback Portal Panitia / Admin</span>
                    </span>
                    <button
                      type="button"
                      class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-0.5 d-inline-flex align-items-center gap-1"
                      @click="copyToClipboard(adminRedirectUri, 'URI Callback Portal Panitia')"
                      title="Salin URI Panitia"
                    >
                      <i class="ri-file-copy-line"></i>
                      <span>Salin URI</span>
                    </button>
                  </div>
                  <div class="p-2 rounded font-monospace fs-11 text-break border redirect-uri-box">
                    {{ adminRedirectUri }}
                  </div>
                </div>

              </div>
            </div>

            <!-- Save Card -->
            <div class="card border-0 shadow-sm rounded-4 policy-card">
              <div class="card-body p-4 text-center">
                <button
                  type="submit"
                  class="btn btn-save-policy w-100 py-2.5 fs-14 fw-bold rounded-3 shadow d-flex align-items-center justify-content-center gap-2"
                  :disabled="form.processing"
                >
                  <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-save-3-fill fs-18"></i>
                  <span>{{ form.processing ? 'Menyimpan Pengaturan...' : 'Simpan Pengaturan Kebijakan' }}</span>
                </button>
                <div class="fs-11 policy-helper mt-2.5">
                  Perubahan kebijakan langsung berlaku untuk seluruh pendaftar pada halaman publik.
                </div>
              </div>
            </div>

          </div>

        </div>
      </form>

    </div>
  </VelzonLayout>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

/* =============================================================================
   PAGE TITLE & BREADCRUMBS
   ============================================================================= */
.page-header-title {
  color: #0f172a;
}
.page-header-subtitle {
  color: #475569;
}

/* =============================================================================
   NAVIGATION SUBMENU TABS
   ============================================================================= */
.policy-tabs-card {
  background: #ffffff;
  border: 1px solid #e2e8f0 !important;
}

.policy-tab-link {
  color: #475569;
  font-weight: 600;
  font-size: 0.82rem;
  transition: all 0.2s ease;
}

.policy-tab-link:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.policy-tab-link-active {
  background: #4f46e5 !important;
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
}

.policy-tab-link-active i {
  color: #ffffff !important;
}

/* =============================================================================
   STATISTICS METRIC CARDS
   ============================================================================= */
.stat-metric-card {
  border-radius: 18px !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08) !important;
}

.stat-metric-primary {
  background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
  border: 1.5px solid #bfdbfe !important;
}

.stat-metric-success {
  background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
  border: 1.5px solid #a7f3d0 !important;
}

.stat-metric-info {
  background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 100%);
  border: 1.5px solid #bae6fd !important;
}

.stat-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.45rem;
  flex-shrink: 0;
}

.stat-icon-wrap.icon-primary {
  background: #dbeafe;
  color: #1d4ed8;
}

.stat-icon-wrap.icon-success {
  background: #dcfce7;
  color: #15803d;
}

.stat-icon-wrap.icon-info {
  background: #e0f2fe;
  color: #0284c7;
}

.stat-label {
  font-size: 0.76rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 2px;
}

.stat-value {
  font-size: 1.45rem;
  font-weight: 900;
  line-height: 1.2;
}

.stat-unit {
  font-size: 0.76rem;
  font-weight: 600;
  color: #64748b;
  margin-left: 2px;
}

/* =============================================================================
   POLICY CARDS & CONTAINERS
   ============================================================================= */
.policy-card {
  background: #ffffff;
  border: 1px solid #e2e8f0 !important;
}

.policy-card-header {
  border-bottom: 1px solid #f1f5f9;
  background: transparent;
  display: flex !important;
  flex-direction: column !important;
  align-items: stretch !important;
  justify-content: flex-start !important;
}

.oauth-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  display: flex;
  align-items: center;
  justify-content: center;
}

.badge-oauth-scope {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.policy-card-title {
  color: #0f172a;
}

.policy-card-desc {
  color: #475569;
}

.policy-label {
  color: #0f172a;
}

.policy-helper {
  color: #64748b;
}

.text-contrast {
  color: #0f172a;
  font-weight: 700;
}

/* Status Pills */
.policy-status-pill {
  font-size: 0.72rem;
  font-weight: 800;
  padding: 5px 12px;
  border-radius: 9999px;
  letter-spacing: 0.4px;
  display: inline-flex;
  align-items: center;
}

.status-pill-wajib {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
}

.status-pill-bebas {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #86efac;
}

/* Quick Toggle Button */
.btn-quick-toggle {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #334155;
  font-weight: 700;
  font-size: 0.74rem;
  border-radius: 9999px;
  padding: 4px 11px;
  transition: all 0.2s ease;
}

.btn-quick-toggle:hover:not(:disabled) {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #94a3b8;
}

/* Option Boxes */
.policy-option-box {
  background: #ffffff;
  border: 1.5px solid #e2e8f0 !important;
  transition: all 0.2s ease;
}

.policy-option-box:hover {
  border-color: #cbd5e1 !important;
  background: #f8fafc;
}

.policy-option-box.is-selected-bebas {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, #f0fdf4 100%) !important;
  border: 2px solid #10b981 !important;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.15) !important;
}

.policy-option-box.is-selected-wajib {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, #eef2ff 100%) !important;
  border: 2px solid #4f46e5 !important;
  box-shadow: 0 4px 16px rgba(79, 70, 229, 0.15) !important;
}

.policy-option-title {
  color: #0f172a;
}

.policy-option-desc {
  color: #334155;
  line-height: 1.55;
}

.policy-bullet-list {
  color: #475569;
  line-height: 1.55;
}

.policy-bullet-list li {
  margin-bottom: 2px;
}

.policy-code {
  background: #f1f5f9;
  color: #0284c7;
  padding: 1.5px 6px;
  border-radius: 5px;
  font-weight: 700;
  border: 1px solid #cbd5e1;
}

/* Subtle Badges */
.policy-subtle-badge {
  font-size: 0.7rem;
  font-weight: 800;
  padding: 3px 9px;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
}

.badge-subtle-success {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #86efac;
}

.badge-subtle-warning {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}

/* Method Item Boxes */
.method-item-box {
  background: #ffffff;
  border: 1.5px solid #e2e8f0 !important;
  transition: all 0.2s ease;
}

.method-item-box:hover {
  border-color: #cbd5e1 !important;
  background: #f8fafc;
}

.method-icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.method-icon-wrap.icon-primary {
  background: #dbeafe;
  color: #1d4ed8;
}

.method-icon-wrap.icon-success {
  background: #dcfce7;
  color: #15803d;
}

.method-icon-wrap.icon-danger {
  background: #fee2e2;
  color: #b91c1c;
}

.method-title {
  color: #0f172a;
}

.method-desc {
  color: #64748b;
}

/* Inputs & Redirect Box */
.policy-input {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #0f172a;
  border-radius: 10px;
}

.policy-input:focus {
  background: #ffffff;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
  color: #0f172a;
}

.redirect-uri-container {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0 !important;
}

.redirect-label {
  color: #475569;
}

.redirect-uri-box {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #0284c7;
  font-weight: 600;
}

/* Save Button */
.btn-save-policy {
  background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
  color: #ffffff;
  border: none;
  box-shadow: 0 6px 18px rgba(79, 70, 229, 0.45);
  transition: all 0.2s ease;
}

.btn-save-policy:hover:not(:disabled) {
  background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(79, 70, 229, 0.55);
  color: #ffffff;
}

/* =============================================================================
   DARK MODE OVERRIDES (data-bs-theme="dark")
   High Contrast WCAG AA, Luminous Badges, Sleek Slate Cards
   ============================================================================= */
[data-bs-theme="dark"] .page-header-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .page-header-subtitle {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .policy-tabs-card {
  background: #121824 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .policy-tab-link {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .policy-tab-link:hover {
  color: #ffffff !important;
  background: rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .policy-tab-link-active {
  background: #4f46e5 !important;
  color: #ffffff !important;
  box-shadow: 0 4px 14px rgba(79, 70, 229, 0.5) !important;
}

/* Dark Stat Metric Cards */
[data-bs-theme="dark"] .stat-metric-primary {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.14) 0%, rgba(20, 29, 47, 0.95) 100%) !important;
  border: 1.5px solid rgba(59, 130, 246, 0.35) !important;
}

[data-bs-theme="dark"] .stat-metric-success {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.14) 0%, rgba(20, 29, 47, 0.95) 100%) !important;
  border: 1.5px solid rgba(16, 185, 129, 0.35) !important;
}

[data-bs-theme="dark"] .stat-metric-info {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.14) 0%, rgba(20, 29, 47, 0.95) 100%) !important;
  border: 1.5px solid rgba(14, 165, 233, 0.35) !important;
}

[data-bs-theme="dark"] .stat-icon-wrap.icon-primary {
  background: rgba(59, 130, 246, 0.25) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(59, 130, 246, 0.45) !important;
}

[data-bs-theme="dark"] .stat-icon-wrap.icon-success {
  background: rgba(16, 185, 129, 0.25) !important;
  color: #86efac !important;
  border: 1px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .stat-icon-wrap.icon-info {
  background: rgba(14, 165, 233, 0.25) !important;
  color: #7dd3fc !important;
  border: 1px solid rgba(14, 165, 233, 0.45) !important;
}

[data-bs-theme="dark"] .stat-label {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .stat-metric-primary .stat-value {
  color: #93c5fd !important;
}

[data-bs-theme="dark"] .stat-metric-success .stat-value {
  color: #86efac !important;
}

[data-bs-theme="dark"] .stat-metric-info .stat-value {
  color: #7dd3fc !important;
}

[data-bs-theme="dark"] .stat-unit {
  color: #94a3b8 !important;
}

/* Dark Policy Cards */
[data-bs-theme="dark"] .policy-card {
  background: #121824 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .policy-card-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .oauth-icon-wrap {
  background: rgba(239, 68, 68, 0.15) !important;
  border: 1px solid rgba(239, 68, 68, 0.35) !important;
}

[data-bs-theme="dark"] .badge-oauth-scope {
  background: rgba(59, 130, 246, 0.18) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(59, 130, 246, 0.4) !important;
}

[data-bs-theme="dark"] .policy-card-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .policy-card-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .policy-label {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .policy-helper {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .text-contrast {
  color: #ffffff !important;
}

/* Dark Status Pills (Rule 3: Luminous) */
[data-bs-theme="dark"] .status-pill-wajib {
  background: rgba(239, 68, 68, 0.2) !important;
  color: #fca5a5 !important;
  border: 1px solid rgba(239, 68, 68, 0.45) !important;
}

[data-bs-theme="dark"] .status-pill-bebas {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
  border: 1px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .btn-quick-toggle {
  background: #1e293b !important;
  border: 1.5px solid rgba(255, 255, 255, 0.18) !important;
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .btn-quick-toggle:hover:not(:disabled) {
  background: #334155 !important;
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.35) !important;
}

/* Dark Option Boxes */
[data-bs-theme="dark"] .policy-option-box {
  background: rgba(255, 255, 255, 0.03) !important;
  border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .policy-option-box:hover {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
}

[data-bs-theme="dark"] .policy-option-box.is-selected-bebas {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.16) 0%, rgba(20, 29, 47, 0.95) 100%) !important;
  border: 2px solid #10b981 !important;
  box-shadow: 0 0 20px rgba(16, 185, 129, 0.2) !important;
}

[data-bs-theme="dark"] .policy-option-box.is-selected-wajib {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.18) 0%, rgba(20, 29, 47, 0.95) 100%) !important;
  border: 2px solid #6366f1 !important;
  box-shadow: 0 0 20px rgba(99, 102, 241, 0.2) !important;
}

[data-bs-theme="dark"] .policy-option-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .policy-option-desc {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .policy-bullet-list {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .policy-code {
  background: #0f172a !important;
  color: #7dd3fc !important;
  border: 1px solid rgba(14, 165, 233, 0.35) !important;
}

/* Dark Subtle Badges (Luminous) */
[data-bs-theme="dark"] .badge-subtle-success {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
  border: 1px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .badge-subtle-warning {
  background: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
  border: 1px solid rgba(245, 158, 11, 0.45) !important;
}

/* Dark Method Item Boxes */
[data-bs-theme="dark"] .method-item-box {
  background: rgba(255, 255, 255, 0.03) !important;
  border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .method-item-box:hover {
  background: rgba(255, 255, 255, 0.06) !important;
  border-color: rgba(255, 255, 255, 0.2) !important;
}

[data-bs-theme="dark"] .method-icon-wrap.icon-primary {
  background: rgba(59, 130, 246, 0.25) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(59, 130, 246, 0.45) !important;
}

[data-bs-theme="dark"] .method-icon-wrap.icon-success {
  background: rgba(16, 185, 129, 0.25) !important;
  color: #86efac !important;
  border: 1px solid rgba(16, 185, 129, 0.45) !important;
}

[data-bs-theme="dark"] .method-icon-wrap.icon-danger {
  background: rgba(239, 68, 68, 0.25) !important;
  color: #fca5a5 !important;
  border: 1px solid rgba(239, 68, 68, 0.45) !important;
}

[data-bs-theme="dark"] .method-title {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .method-desc {
  color: #cbd5e1 !important;
}

/* Dark Inputs & Redirect Box */
[data-bs-theme="dark"] .policy-input {
  background: #090d16 !important;
  border: 1.5px solid rgba(255, 255, 255, 0.16) !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .policy-input:focus {
  background: #090d16 !important;
  border-color: #6366f1 !important;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .redirect-uri-container {
  background: rgba(255, 255, 255, 0.02) !important;
  border: 1.5px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .redirect-label {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .redirect-uri-box {
  background: #090d16 !important;
  border: 1px solid rgba(14, 165, 233, 0.3) !important;
  color: #7dd3fc !important;
}
</style>
