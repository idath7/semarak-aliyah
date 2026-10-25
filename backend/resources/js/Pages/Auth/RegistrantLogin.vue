<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
  redirectTo: { type: String, default: '' },
  lomba: { type: String, default: '' },
  isLoginRequired: { type: Boolean, default: false },
  schoolName: { type: String, default: 'MA Unggulan Wahid Hasyim Balung' },
  googleEnabled: { type: Boolean, default: true },
  notice: { type: String, default: '' },
});

const toast = useToast();
const { toasts, removeToast } = toast;
const { isDark, toggleTheme } = useTheme();

// ==========================================
// Prioritas Login: 'otp' (1) | 'password' (2) | 'google' (3)
// ==========================================
const activeTab = ref('otp');

// ==========================================
// 1. WhatsApp OTP Login Flow (Prioritas 1)
// ==========================================
const otpPhone = ref('');
const otpCode = ref('');
const otpStep = ref(1); // 1 = input phone, 2 = input otp
const isSendingOtp = ref(false);
const isVerifyingOtp = ref(false);
const otpCountdown = ref(0);
let timerInterval = null;
const otpDebugHint = ref(null);
const otpErrorMessage = ref('');
const otpVerifyError = ref('');

function startOtpCountdown(seconds = 300) {
  otpCountdown.value = seconds;
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    if (otpCountdown.value > 0) {
      otpCountdown.value--;
    } else {
      clearInterval(timerInterval);
    }
  }, 1000);
}

const formattedCountdown = computed(() => {
  const m = Math.floor(otpCountdown.value / 60);
  const s = otpCountdown.value % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

async function requestOtp() {
  otpErrorMessage.value = '';
  if (!otpPhone.value || otpPhone.value.trim().length < 8) {
    otpErrorMessage.value = 'Masukkan nomor WhatsApp aktif yang valid (minimal 8 angka).';
    toast.error(otpErrorMessage.value);
    return;
  }

  isSendingOtp.value = true;
  otpDebugHint.value = null;

  try {
    const res = await fetch('/pendaftar/send-otp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ phone: otpPhone.value.trim() }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success(data.message || 'Kode OTP telah dikirimkan ke WhatsApp Anda!');
      otpStep.value = 2;
      startOtpCountdown(data.expires_in || 300);
      if (data.debug_otp) {
        otpDebugHint.value = data.debug_otp;
      }
    } else {
      otpErrorMessage.value = data.message || 'Nomor WhatsApp belum terdaftar sebagai akun pendaftar. Silakan buat akun baru terlebih dahulu.';
      toast.error(otpErrorMessage.value);
    }
  } catch (err) {
    otpErrorMessage.value = 'Terjadi kendala jaringan saat menghubungi server gateway. Silakan coba kembali.';
    toast.error(otpErrorMessage.value);
  } finally {
    isSendingOtp.value = false;
  }
}

async function verifyOtp() {
  otpVerifyError.value = '';
  if (!otpCode.value || otpCode.value.trim().length !== 6) {
    otpVerifyError.value = 'Masukkan 6 digit kode OTP verifikasi.';
    toast.error(otpVerifyError.value);
    return;
  }

  isVerifyingOtp.value = true;
  try {
    const res = await fetch('/pendaftar/verify-otp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        phone: otpPhone.value.trim(),
        otp: otpCode.value.trim(),
        redirect_to: props.redirectTo,
        lomba: props.lomba || '',
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success('Verifikasi OTP berhasil! Mengalihkan ke dashboard...');
      setTimeout(() => {
        window.location.href = data.redirect || '/pendaftar/dashboard';
      }, 700);
    } else {
      otpVerifyError.value = data.message || 'Kode OTP salah atau telah kadaluwarsa.';
      toast.error(otpVerifyError.value);
    }
  } catch (err) {
    otpVerifyError.value = 'Terjadi kendala jaringan saat memverifikasi OTP. Silakan coba kembali.';
    toast.error(otpVerifyError.value);
  } finally {
    isVerifyingOtp.value = false;
  }
}

function resetOtpFlow() {
  otpStep.value = 1;
  otpCode.value = '';
  otpDebugHint.value = null;
  otpErrorMessage.value = '';
  otpVerifyError.value = '';
  if (timerInterval) clearInterval(timerInterval);
}

// ==========================================
// 2. Password Login Form (Alternatif Opsi 2)
// ==========================================
const form = useForm({
  login: '',
  password: '',
  remember: false,
  redirect_to: props.redirectTo,
  lomba: props.lomba || '',
});

const showPassword = ref(false);

function submitPasswordLogin() {
  form.post('/pendaftar/login', {
    preserveScroll: true,
    onError: () => {
      toast.error('Gagal masuk. Periksa kembali nomor WA / email dan password Anda.');
    }
  });
}

// ==========================================
// 3. Google Registered Email Login Form
// ==========================================
const googleEmailForm = useForm({
  email: '',
  redirect_to: props.redirectTo,
  lomba: props.lomba || '',
});

function submitGoogleEmailLogin() {
  if (!googleEmailForm.email || !googleEmailForm.email.trim()) {
    toast.error('Masukkan alamat email Google terdaftar.');
    return;
  }
  googleEmailForm.post('/auth/google', {
    preserveScroll: true,
    onError: (errs) => {
      const msg = errs.google_email || errs.login || 'Email Google tidak ditemukan pada data pendaftaran.';
      toast.error(msg);
    }
  });
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});
</script>

<template>
  <Head title="Masuk Akun Pendaftar - SEMARAK ALIYAH XIV 2026" />

  <div class="auth-root-wrapper min-h-screen d-flex flex-column justify-content-between p-2.5 p-sm-3 position-relative">
    
    <!-- Floating Toast Notifications Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
      <div
        v-for="t in toasts"
        :key="t.id"
        class="toast show align-items-center border-0 shadow-lg rounded-3 mb-2"
        :class="t.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
        role="alert"
      >
        <div class="d-flex p-2.5 px-3 align-items-center">
          <i :class="t.type === 'success' ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill'" class="fs-18 me-2"></i>
          <div class="toast-body p-0 flex-grow-1 fs-12 fw-semibold">
            {{ t.message }}
          </div>
          <button type="button" class="btn-close btn-close-white ms-2 m-auto" @click="removeToast(t.id)"></button>
        </div>
      </div>
    </div>
    
    <!-- Top Utility Header -->
    <header class="w-100 d-flex align-items-center justify-content-between mx-auto mb-2 mb-md-3" style="max-width: 860px;">
      <Link
        href="/"
        class="btn btn-sm auth-nav-pill rounded-pill d-flex align-items-center gap-1.5 px-2.5 py-1 fs-11.5 fw-semibold"
      >
        <i class="ri-arrow-left-line"></i>
        <span>Beranda Portal Publik</span>
      </Link>

      <div class="d-flex align-items-center gap-2">
        <span class="badge badge-portal-resmi rounded-pill px-2.5 py-1 fs-11 fw-bold d-none d-sm-inline-flex align-items-center gap-1.5">
          <i class="ri-shield-check-line fs-13"></i>
          <span>Portal Pendaftar Resmi</span>
        </span>

        <button
          type="button"
          class="btn btn-sm auth-theme-toggle rounded-pill d-flex align-items-center gap-1.5 px-2.5 py-1 fs-11.5 fw-semibold"
          @click="toggleTheme"
          :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
        >
          <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line text-primary'"></i>
          <span class="d-none d-sm-inline">{{ isDark ? 'Mode Terang' : 'Mode Gelap' }}</span>
        </button>
      </div>
    </header>

    <!-- Center Interactive Container -->
    <main class="w-100 my-auto py-1">
      <div class="auth-layout-box w-100 mx-auto shadow-xl rounded-4 overflow-hidden" style="max-width: 860px;">
        <div class="row g-0">
          
          <!-- LEFT HERO SHOWCASE PANEL (Visible on lg and up) -->
          <div class="col-lg-5 d-none d-lg-flex flex-column justify-content-between p-3.5 p-xl-4 auth-hero-panel position-relative overflow-hidden">
            <!-- Ambient Glow Shapes -->
            <div class="hero-glow-1"></div>
            <div class="hero-glow-2"></div>

            <div class="position-relative z-1">
              <!-- Event Brand & Badge -->
              <div class="d-flex align-items-center gap-2.5 mb-3">
                <img
                  src="/assets/img/logo.png"
                  alt="Logo"
                  class="rounded-circle shadow-sm"
                  style="width: 40px; height: 40px; object-fit: contain; background: rgba(255,255,255,0.1); padding: 2px;"
                />
                <div>
                  <div class="fw-black fs-14 text-white text-uppercase tracking-wider">SEMARAK ALIYAH XIV</div>
                  <div class="fs-11 text-light text-opacity-80 fw-medium">{{ schoolName }}</div>
                </div>
              </div>

              <!-- Hero Headline -->
              <h2 class="fs-17 fw-black text-white lh-sm mb-2">
                Akses Delegasi Lomba <br/>
                <span class="hero-highlight-text">Cepat, Praktis & Mandiri</span>
              </h2>
              <p class="fs-11.5 text-light text-opacity-80 mb-3 lh-base">
                Kelola berkas delegasi kontingen sekolah, pantau verifikasi panitia, dan unduh kartu bukti pendaftaran.
              </p>

              <!-- Value Proposition Chips -->
              <div class="d-flex flex-column gap-2 mb-3">
                <div class="hero-feature-item p-2 px-2.5 rounded-3 d-flex gap-2.5 align-items-center">
                  <div class="hero-feature-icon hero-icon-wa">
                    <i class="ri-whatsapp-fill"></i>
                  </div>
                  <div>
                    <div class="fs-12 fw-bold text-white">Login Cepat OTP WA</div>
                    <div class="fs-11 hero-feature-desc lh-sm">Masuk instan nomor WA tanpa perlu kata sandi.</div>
                  </div>
                </div>

                <div class="hero-feature-item p-2 px-2.5 rounded-3 d-flex gap-2.5 align-items-center">
                  <div class="hero-feature-icon hero-icon-edit">
                    <i class="ri-edit-box-fill"></i>
                  </div>
                  <div>
                    <div class="fs-12 fw-bold text-white">Revisi Berkas Fleksibel</div>
                    <div class="fs-11 hero-feature-desc lh-sm">Lengkapi dokumen selagi status belum ditutup.</div>
                  </div>
                </div>

                <div class="hero-feature-item p-2 px-2.5 rounded-3 d-flex gap-2.5 align-items-center">
                  <div class="hero-feature-icon hero-icon-print">
                    <i class="ri-printer-fill"></i>
                  </div>
                  <div>
                    <div class="fs-12 fw-bold text-white">Cetak Bukti Resmi</div>
                    <div class="fs-11 hero-feature-desc lh-sm">Unduh lembar bukti lengkap barcode pendaftaran.</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bottom Left Footer -->
            <div class="position-relative z-1 pt-2.5 border-top border-white border-opacity-15 d-flex align-items-center justify-content-between fs-11 text-light text-opacity-75">
              <span>Helpdesk Panitia Resmi</span>
              <a href="https://wa.me/6283850070767" target="_blank" class="text-white text-decoration-none fw-semibold d-flex align-items-center gap-1.5">
                <i class="ri-whatsapp-fill fs-14" style="color: #4ade80 !important;"></i>
                <span style="color: #ffffff; font-weight: 700;">0838 5007 0767</span>
              </a>
            </div>
          </div>

          <!-- RIGHT FORM PANEL -->
          <div class="col-lg-7 auth-form-panel p-3 p-sm-4 d-flex flex-column justify-content-center">
            
            <!-- Mobile Brand Header (Visible only on < lg) -->
            <div class="d-lg-none text-center mb-3">
              <img
                src="/assets/img/logo.png"
                alt="Logo"
                class="rounded-circle shadow-sm mb-1.5"
                style="width: 40px; height: 40px; object-fit: contain;"
              />
              <div class="fw-black fs-15 auth-title text-uppercase">SEMARAK ALIYAH XIV</div>
              <div class="fs-11 auth-desc fw-semibold">{{ schoolName }}</div>
            </div>

            <!-- Form Title & Greeting -->
            <div class="mb-3">
              <div class="d-flex align-items-center gap-2 mb-0.5">
                <h1 class="fs-18 fw-black auth-title mb-0">Masuk Akun Pendaftar</h1>
                <span class="badge badge-status-online rounded-pill px-2.5 py-0.5 fs-10 fw-bold d-inline-flex align-items-center gap-1">
                  <span class="online-indicator-dot"></span>
                  <span>Online</span>
                </span>
              </div>
              <p class="fs-12 auth-desc mb-0">
                Pilih metode masuk yang paling mudah dan nyaman bagi Anda:
              </p>
            </div>

            <!-- Notice Alert if registration requires login -->
            <div v-if="notice === 'required' || isLoginRequired" class="alert auth-notice-box border-0 rounded-3 p-2.5 mb-3 d-flex gap-2.5 align-items-start">
              <i class="ri-shield-keyhole-line fs-18 notice-icon flex-shrink-0 mt-0.5"></i>
              <div class="fs-11.5 lh-sm">
                <strong class="d-block mb-0.5 notice-title">Akses Formulir Mewajibkan Akun</strong>
                <span class="notice-desc">Pengaturan panitia mewajibkan login terlebih dahulu. Belum punya akun? </span>
                <Link :href="`/pendaftar/register?redirect_to=${redirectTo}${lomba ? '&lomba=' + lomba : ''}`" class="notice-link fw-bold text-decoration-underline">Daftar Akun Baru (1 Menit)</Link>.
              </div>
            </div>

            <!-- Segmented Control Tabs (Prioritas: OTP WA -> Username -> Google) -->
            <div class="p-1 rounded-3 mb-3 auth-tab-segmented d-flex gap-1">
              
              <!-- TAB 1: OTP WhatsApp (Prioritas 1) -->
              <button
                type="button"
                class="flex-fill py-1.5 px-2 rounded-2 fs-11.5 fw-bold d-flex align-items-center justify-content-center gap-1.5 transition-all tab-item-btn"
                :class="{ 'is-active is-active-otp shadow-sm': activeTab === 'otp' }"
                @click="activeTab = 'otp'"
              >
                <i class="ri-whatsapp-line fs-14"></i>
                <span>OTP WhatsApp</span>
                <span class="badge-pill-priority">Prioritas</span>
              </button>

              <!-- TAB 2: Username & Password (Opsi 2) -->
              <button
                type="button"
                class="flex-fill py-1.5 px-2 rounded-2 fs-11.5 fw-bold d-flex align-items-center justify-content-center gap-1.5 transition-all tab-item-btn"
                :class="{ 'is-active is-active-password shadow-sm': activeTab === 'password' }"
                @click="activeTab = 'password'"
              >
                <i class="ri-user-line fs-14"></i>
                <span>Username</span>
              </button>

              <!-- TAB 3: Google (Opsi 3) -->
              <button
                v-if="googleEnabled"
                type="button"
                class="flex-fill py-1.5 px-2 rounded-2 fs-11.5 fw-bold d-flex align-items-center justify-content-center gap-1.5 transition-all tab-item-btn"
                :class="{ 'is-active is-active-google shadow-sm': activeTab === 'google' }"
                @click="activeTab = 'google'"
              >
                <i class="ri-google-fill fs-14 text-danger"></i>
                <span>Google</span>
              </button>
            </div>

            <!-- ============================================== -->
            <!-- TAB 1 CONTENT: OTP WHATSAPP FLOW (PRIORITAS 1) -->
            <!-- ============================================== -->
            <div v-if="activeTab === 'otp'" class="auth-tab-content">
              
              <!-- Step 1: Input WhatsApp Number -->
              <div v-if="otpStep === 1">
                <div class="p-2.5 rounded-3 mb-2.5 auth-highlight-box d-flex gap-2 align-items-start">
                  <div class="rounded-circle auth-highlight-icon-wrap d-flex align-items-center justify-content-center fs-14 flex-shrink-0" style="width: 26px; height: 26px;">
                    <i class="ri-flashlight-fill"></i>
                  </div>
                  <div class="fs-11.5 lh-sm">
                    <strong class="auth-highlight-title d-block mb-0.5">Metode Cepat & Praktis</strong>
                    <span class="auth-highlight-desc">Tanpa kata sandi. Masukkan nomor WA terdaftar, kode OTP 6 digit akan dikirim ke chat Anda.</span>
                  </div>
                </div>

                <!-- Alert Error Nomor Tidak Terdaftar / Gagal (Rule 2 & 3: High Contrast) -->
                <div v-if="otpErrorMessage" class="p-2.5 rounded-3 mb-2.5 auth-error-box d-flex gap-2 align-items-start">
                  <i class="ri-error-warning-fill fs-18 auth-error-icon flex-shrink-0 mt-0.5"></i>
                  <div class="fs-12 lh-sm flex-grow-1">
                    <strong class="auth-error-title d-block mb-1">Nomor WhatsApp Belum Terdaftar</strong>
                    <div class="auth-error-desc mb-2">{{ otpErrorMessage }}</div>
                    <Link
                      :href="`/pendaftar/register?redirect_to=${redirectTo}`"
                      class="btn btn-sm btn-danger px-3 py-1 fs-11.5 fw-bold rounded-pill text-white text-decoration-none shadow-sm d-inline-flex align-items-center gap-1.5"
                    >
                      <i class="ri-user-add-line"></i>
                      <span>Daftar Akun Baru Sekarang (Gratis)</span>
                    </Link>
                  </div>
                  <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" style="font-size: 10px;" @click="otpErrorMessage = ''"></button>
                </div>

                <form @submit.prevent="requestOtp" class="d-flex flex-column gap-2.5">
                  <div>
                    <label class="form-label fs-12 fw-bold auth-label mb-1">
                      Nomor WhatsApp Terdaftar <span class="text-danger">*</span>
                    </label>
                    <div class="input-group modern-input-group">
                      <span class="input-group-text auth-input-icon px-2.5">
                        <i class="ri-whatsapp-fill fs-15 auth-icon-wa"></i>
                      </span>
                      <input
                        v-model="otpPhone"
                        type="tel"
                        class="form-control auth-input fs-12.5 py-1.5"
                        :class="{ 'is-invalid': otpErrorMessage }"
                        placeholder="Contoh: 08123456789 atau 628123456789"
                        required
                        autofocus
                      />
                    </div>
                    <div v-if="otpErrorMessage" class="text-danger fs-11.5 mt-1.5 fw-bold d-flex align-items-center gap-1">
                      <i class="ri-close-circle-fill"></i>
                      <span>{{ otpErrorMessage }}</span>
                    </div>
                    <div v-else class="fs-11 auth-desc mt-1">
                      Nomor WhatsApp aktif dan dapat menerima pesan dari OpenWA Gateway.
                    </div>
                  </div>

                  <button
                    type="submit"
                    class="btn btn-success auth-submit-btn w-100 py-2 fs-12.5 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 mt-1"
                    :disabled="isSendingOtp"
                  >
                    <span v-if="isSendingOtp" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ri-send-plane-2-fill fs-15"></i>
                    <span>{{ isSendingOtp ? 'Mengirim Kode OTP...' : 'Kirim Kode OTP WhatsApp' }}</span>
                  </button>
                </form>
              </div>

              <!-- Step 2: Input 6-Digit OTP -->
              <div v-else-if="otpStep === 2">
                <div class="p-2.5 rounded-3 mb-2.5 auth-success-box d-flex gap-2 align-items-start">
                  <i class="ri-checkbox-circle-fill fs-18 auth-success-icon flex-shrink-0 mt-0.5"></i>
                  <div class="fs-11.5 lh-sm">
                    <div class="auth-success-text">Kode OTP 6 digit telah dikirim ke nomor WA <strong>{{ otpPhone }}</strong>.</div>
                    <button type="button" class="btn btn-link p-0 auth-link fw-bold fs-11 mt-0.5" @click="resetOtpFlow">
                      <i class="ri-edit-line me-0.5"></i> Ganti Nomor WA
                    </button>
                  </div>
                </div>

                <!-- Alert Error Verifikasi OTP Gagal -->
                <div v-if="otpVerifyError" class="p-2.5 rounded-3 mb-2.5 auth-error-box d-flex gap-2 align-items-start">
                  <i class="ri-error-warning-fill fs-18 auth-error-icon flex-shrink-0 mt-0.5"></i>
                  <div class="fs-12 lh-sm flex-grow-1">
                    <strong class="auth-error-title d-block mb-0.5">Verifikasi OTP Gagal</strong>
                    <div class="auth-error-desc">{{ otpVerifyError }}</div>
                  </div>
                  <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" style="font-size: 10px;" @click="otpVerifyError = ''"></button>
                </div>

                <form @submit.prevent="verifyOtp" class="d-flex flex-column gap-2.5">
                  <div>
                    <label class="form-label fs-12 fw-bold auth-label d-flex justify-content-between align-items-center mb-1">
                      <span>Masukkan 6 Digit Kode OTP <span class="text-danger">*</span></span>
                      <span v-if="otpCountdown > 0" class="badge auth-timer-badge fs-10 py-0.5 px-2">
                        <i class="ri-timer-line me-1"></i>{{ formattedCountdown }}
                      </span>
                      <span v-else class="badge bg-danger text-white fs-10 fw-bold py-0.5 px-2">Kadaluarsa</span>
                    </label>
                    <div class="input-group modern-input-group">
                      <span class="input-group-text auth-input-icon px-2.5">
                        <i class="ri-key-2-fill fs-15 auth-icon-key"></i>
                      </span>
                      <input
                        v-model="otpCode"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        class="form-control auth-input fs-16 font-monospace fw-black tracking-widest text-center py-1.5"
                        :class="{ 'is-invalid': otpVerifyError }"
                        placeholder="• • • • • •"
                        required
                        autofocus
                      />
                    </div>
                    <div v-if="otpVerifyError" class="text-danger fs-11.5 mt-1.5 fw-bold d-flex align-items-center gap-1">
                      <i class="ri-close-circle-fill"></i>
                      <span>{{ otpVerifyError }}</span>
                    </div>

                    <!-- Debug Hint in local testing -->
                    <div v-if="otpDebugHint" class="alert auth-debug-box border rounded-2 p-1.5 mt-1.5 fs-11 d-flex align-items-center justify-content-between">
                      <span>💡 <strong>Kode Uji Coba:</strong> <code class="auth-debug-code">{{ otpDebugHint }}</code></span>
                      <button type="button" class="btn btn-xs auth-debug-btn py-0 px-2 rounded-pill fs-10 fw-bold" @click="otpCode = otpDebugHint">Isi Cepat</button>
                    </div>
                  </div>

                  <div class="d-flex align-items-center justify-content-between fs-11.5">
                    <span class="auth-desc">Tidak menerima kode?</span>
                    <button
                      type="button"
                      class="btn btn-link p-0 auth-link fw-bold fs-11.5 text-decoration-none"
                      :disabled="otpCountdown > 0 || isSendingOtp"
                      @click="requestOtp"
                    >
                      Kirim Ulang OTP
                    </button>
                  </div>

                  <button
                    type="submit"
                    class="btn btn-success auth-submit-btn w-100 py-2 fs-12.5 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 mt-1"
                    :disabled="isVerifyingOtp"
                  >
                    <span v-if="isVerifyingOtp" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ri-shield-check-fill fs-16"></i>
                    <span>{{ isVerifyingOtp ? 'Memverifikasi...' : 'Verifikasi & Masuk ke Dashboard' }}</span>
                  </button>
                </form>
              </div>

            </div>

            <!-- ============================================== -->
            <!-- TAB 2 CONTENT: USERNAME & PASSWORD LOGIN (OPSI 2) -->
            <!-- ============================================== -->
            <div v-else-if="activeTab === 'password'" class="auth-tab-content">
              <form @submit.prevent="submitPasswordLogin" class="d-flex flex-column gap-2.5">
                
                <!-- Identifier (Username / WA / Email) -->
                <div>
                  <label class="form-label fs-12 fw-bold auth-label mb-1">
                    Username (Nomor WhatsApp / Email) <span class="text-danger">*</span>
                  </label>
                  <div class="input-group modern-input-group">
                    <span class="input-group-text auth-input-icon px-2.5">
                      <i class="ri-user-line fs-15"></i>
                    </span>
                    <input
                      v-model="form.login"
                      type="text"
                      class="form-control auth-input fs-12.5 py-1.5"
                      placeholder="nomor whatsapp atau email"
                      required
                      autofocus
                      :class="{ 'is-invalid': form.errors.login }"
                    />
                  </div>
                  <div v-if="form.errors.login" class="text-danger fs-11 mt-1 fw-bold">
                    {{ form.errors.login }}
                  </div>
                </div>

                <!-- Password Input -->
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fs-12 fw-bold auth-label mb-0">
                      Password Akun <span class="text-danger">*</span>
                    </label>
                    <Link href="/pendaftar/lupa-password" class="auth-link fw-bold fs-11 text-decoration-none hover:underline">
                      Lupa Password?
                    </Link>
                  </div>
                  <div class="input-group modern-input-group">
                    <span class="input-group-text auth-input-icon px-2.5">
                      <i class="ri-lock-line fs-15"></i>
                    </span>
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control auth-input fs-12.5 py-1.5"
                      placeholder="Masukkan kata sandi akun Anda"
                      required
                      :class="{ 'is-invalid': form.errors.password }"
                    />
                    <button
                      type="button"
                      class="btn auth-input-btn px-2.5"
                      @click="showPassword = !showPassword"
                      title="Lihat / Sembunyikan Password"
                    >
                      <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                  </div>
                  <div v-if="form.errors.password" class="text-danger fs-11 mt-1 fw-bold">
                    {{ form.errors.password }}
                  </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="form-check mt-0 mb-0">
                  <input
                    v-model="form.remember"
                    type="checkbox"
                    id="rememberMe"
                    class="form-check-input"
                  />
                  <label for="rememberMe" class="form-check-label fs-11.5 auth-checkbox-label">
                    Ingat saya di perangkat ini
                  </label>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary auth-submit-btn w-100 py-2 fs-12.5 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-1.5 mt-0.5"
                  :disabled="form.processing"
                >
                  <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-login-box-line fs-16"></i>
                  <span>{{ form.processing ? 'Memverifikasi Akun...' : 'Masuk ke Dashboard Pendaftar' }}</span>
                </button>
              </form>
            </div>

            <!-- ============================================== -->
            <!-- TAB 3 CONTENT: GOOGLE OAUTH FLOW (OPSI 3) -->
            <!-- ============================================== -->
            <div v-else-if="activeTab === 'google'" class="auth-tab-content py-2">
              <div class="text-center mb-3">
                <div class="rounded-circle auth-google-icon-circle d-inline-flex align-items-center justify-content-center fs-22 mb-1.5" style="width: 44px; height: 44px;">
                  <i class="ri-google-fill text-danger"></i>
                </div>
                <h5 class="fs-14 fw-bold auth-title mb-0.5">Masuk dengan Akun Google</h5>
                <p class="fs-11.5 auth-desc mb-0" style="max-width: 360px; margin: 0 auto;">
                  Akses instan menggunakan akun Google atau alamat email yang didaftarkan saat pengisian formulir.
                </p>
              </div>

              <!-- Button 1: Google OAuth Direct -->
              <div class="text-center mb-2.5">
                <a
                  href="/auth/google"
                  class="btn auth-google-btn w-100 py-2 px-3 fw-bold fs-12 rounded-3 shadow-sm d-inline-flex align-items-center justify-content-center gap-2"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.66-5.17 3.66-9.17z"/>
                    <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.35 24 12 24z"/>
                    <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.98 0 12s.45 3.82 1.25 5.42l4.03-3.15z"/>
                    <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.35 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98z"/>
                  </svg>
                  <span>Lanjutkan dengan Akun Google</span>
                </a>
              </div>

              <!-- OR Divider -->
              <div class="d-flex align-items-center my-2.5">
                <hr class="flex-grow-1 my-0 border-secondary-subtle">
                <span class="px-2 fs-10.5 text-muted fw-bold text-uppercase">Atau Email Google Terdaftar</span>
                <hr class="flex-grow-1 my-0 border-secondary-subtle">
              </div>

              <!-- Form Input Email Google Terdaftar -->
              <form @submit.prevent="submitGoogleEmailLogin" class="d-flex flex-column gap-2 text-start">
                <div>
                  <label class="form-label fs-11.5 fw-bold auth-label mb-1">
                    Email Google yang Didaftarkan <span class="text-danger">*</span>
                  </label>
                  <div class="input-group modern-input-group">
                    <span class="input-group-text auth-input-icon px-2.5">
                      <i class="ri-mail-line fs-15"></i>
                    </span>
                    <input
                      v-model="googleEmailForm.email"
                      type="email"
                      class="form-control auth-input fs-12 py-1.5"
                      placeholder="contoh: pembina@gmail.com"
                      required
                      :class="{ 'is-invalid': googleEmailForm.errors.google_email }"
                    />
                  </div>
                  <div v-if="googleEmailForm.errors.google_email" class="text-danger fs-11 mt-1 fw-bold">
                    {{ googleEmailForm.errors.google_email }}
                  </div>
                  <span class="fs-10.5 auth-desc mt-1 d-block">
                    Sistem akan memverifikasi email ini dengan data pendaftaran Anda.
                  </span>
                </div>

                <button
                  type="submit"
                  class="btn btn-outline-danger w-100 py-1.5 fs-12 fw-bold rounded-3 shadow-xs d-flex align-items-center justify-content-center gap-1.5 mt-0.5"
                  :disabled="googleEmailForm.processing"
                >
                  <span v-if="googleEmailForm.processing" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-google-fill fs-15 text-danger"></i>
                  <span>{{ googleEmailForm.processing ? 'Memverifikasi Email...' : 'Masuk dengan Email Google Terdaftar' }}</span>
                </button>
              </form>
            </div>

            <!-- Bottom Action: Register Link -->
            <div class="pt-3 mt-3 border-top auth-divider text-center">
              <div class="fs-12 auth-register-text">
                Belum memiliki akun pendaftar?
                <Link
                  :href="`/pendaftar/register?redirect_to=${redirectTo}${lomba ? '&lomba=' + lomba : ''}`"
                  class="auth-link fw-bold text-decoration-none ms-1 hover:underline"
                >
                  Daftar Akun Sekarang (Gratis)
                </Link>
              </div>
            </div>

          </div>

        </div>
      </div>
    </main>

    <!-- Bottom Footer Links -->
    <footer class="w-100 text-center py-1.5 mx-auto" style="max-width: 860px;">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 fs-11.5 px-2 auth-desc">
        <span>© 2026 Semarak Aliyah XIV • MA Unggulan Wahid Hasyim Balung</span>
        <div class="d-flex align-items-center gap-2.5">
          <Link v-if="!isLoginRequired" href="/layanan-registrasi" class="auth-desc text-decoration-none hover:text-primary">
            Registrasi Bebas Tanpa Login
          </Link>
          <span v-if="!isLoginRequired">•</span>
          <a href="https://wa.me/6283850070767" target="_blank" class="auth-desc text-decoration-none hover:text-primary">
            Bantuan Panitia
          </a>
        </div>
      </div>
    </footer>

  </div>
</template>

<style scoped>
.transition-all {
  transition: all 0.25s ease-in-out;
}

/* ===================================================
   ROOT PAGE BACKGROUNDS
   =================================================== */
.auth-root-wrapper {
  background: radial-gradient(circle at 50% 10%, #eef2ff 0%, #f8fafc 60%, #e2e8f0 100%);
  transition: background 0.3s ease;
}
[data-bs-theme="dark"] .auth-root-wrapper {
  background: radial-gradient(circle at 50% 15%, #0f172a 0%, #070a13 70%, #03050a 100%);
}

/* ===================================================
   MAIN CONTAINER BOX & CARD
   =================================================== */
.auth-layout-box {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 16px;
  box-shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.12);
}
[data-bs-theme="dark"] .auth-layout-box {
  background: #0b1120;
  border: 1px solid #334155;
  box-shadow: 0 25px 50px -15px rgba(0, 0, 0, 0.8);
}

/* ===================================================
   LEFT HERO SHOWCASE PANEL (High Contrast Text)
   =================================================== */
.auth-hero-panel {
  background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
}
.hero-glow-1 {
  position: absolute;
  top: -40px;
  right: -40px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(99, 102, 241, 0.3) 0%, transparent 70%);
  pointer-events: none;
}
.hero-glow-2 {
  position: absolute;
  bottom: -40px;
  left: -40px;
  width: 180px;
  height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
  pointer-events: none;
}
.hero-highlight-text {
  background: linear-gradient(90deg, #38bdf8 0%, #a5b4fc 50%, #6ee7b7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.hero-feature-item {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  transition: transform 0.2s ease, background 0.2s ease;
}
.hero-feature-item:hover {
  transform: translateY(-1.5px);
  background: rgba(255, 255, 255, 0.12);
}
.hero-feature-desc {
  color: #f1f5f9 !important;
  opacity: 0.92;
}
.hero-feature-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid rgba(255, 255, 255, 0.22);
}
.hero-feature-icon.hero-icon-wa {
  background: #16a34a !important;
  color: #ffffff !important;
  box-shadow: 0 3px 10px rgba(22, 163, 74, 0.45);
}
.hero-feature-icon.hero-icon-edit {
  background: #2563eb !important;
  color: #ffffff !important;
  box-shadow: 0 3px 10px rgba(37, 99, 235, 0.45);
}
.hero-feature-icon.hero-icon-print {
  background: #d97706 !important;
  color: #ffffff !important;
  box-shadow: 0 3px 10px rgba(217, 119, 6, 0.45);
}
.hero-feature-icon i {
  color: #ffffff !important;
  font-size: 16px;
  line-height: 1;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.35));
}

/* ===================================================
   RIGHT FORM PANEL (Strict WCAG AA Contrast)
   =================================================== */
.auth-form-panel {
  background: #ffffff;
  padding: 24px 22px;
}
[data-bs-theme="dark"] .auth-form-panel {
  background: #0b1120;
}

@media (max-width: 576px) {
  .auth-root-wrapper {
    padding: 12px 14px !important;
  }
  .auth-layout-box {
    border-radius: 14px !important;
  }
  .auth-form-panel {
    padding: 20px 16px !important;
  }
}

/* Typography (Rule 2 - High Contrast) */
.auth-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .auth-title {
  color: #ffffff;
}

.auth-desc {
  color: #1e293b;
  font-weight: 500;
}
[data-bs-theme="dark"] .auth-desc {
  color: #cbd5e1;
  font-weight: 500;
}

.auth-label {
  color: #0f172a;
}
[data-bs-theme="dark"] .auth-label {
  color: #f8fafc;
}

.auth-checkbox-label {
  color: #0f172a;
  font-weight: 600;
}
[data-bs-theme="dark"] .auth-checkbox-label {
  color: #f8fafc;
  font-weight: 600;
}

.auth-register-text {
  color: #1e293b;
  font-weight: 600;
}
[data-bs-theme="dark"] .auth-register-text {
  color: #e2e8f0;
  font-weight: 600;
}

.auth-link {
  color: #1d4ed8 !important;
  font-weight: 700;
  text-decoration: none;
}
.auth-link:hover {
  text-decoration: underline;
}
[data-bs-theme="dark"] .auth-link {
  color: #93c5fd !important;
  font-weight: 700;
}

.auth-divider {
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .auth-divider {
  border-color: #334155 !important;
}

/* ===================================================
   SEGMENTED CONTROL TABS (Rule 2 & 3)
   =================================================== */
.auth-tab-segmented {
  background: #e2e8f0;
  border: 1px solid #cbd5e1;
}
[data-bs-theme="dark"] .auth-tab-segmented {
  background: #1e293b;
  border: 1px solid #334155;
}

.tab-item-btn {
  background: transparent;
  border: none;
  color: #0f172a !important;
  font-weight: 700;
  cursor: pointer;
  position: relative;
}
[data-bs-theme="dark"] .tab-item-btn {
  color: #f1f5f9 !important;
}

.tab-item-btn:hover {
  color: #000000 !important;
}
[data-bs-theme="dark"] .tab-item-btn:hover {
  color: #ffffff !important;
}

.tab-item-btn.is-active.is-active-otp {
  background: #15803d !important;
  color: #ffffff !important;
  box-shadow: 0 3px 10px rgba(21, 128, 61, 0.35) !important;
}

.tab-item-btn.is-active.is-active-password {
  background: #4338ca !important;
  color: #ffffff !important;
  box-shadow: 0 3px 10px rgba(67, 56, 202, 0.35) !important;
}

.tab-item-btn.is-active.is-active-google {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #94a3b8 !important;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12) !important;
}
[data-bs-theme="dark"] .tab-item-btn.is-active.is-active-google {
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #ffffff !important;
}

.badge-pill-priority {
  background: #fef08a;
  color: #713f12;
  border: 1px solid #eab308;
  font-size: 9px;
  font-weight: 800;
  padding: 1px 5px;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
[data-bs-theme="dark"] .badge-pill-priority {
  background: #fde047;
  color: #422006;
  border: 1px solid #facc15;
}
.tab-item-btn.is-active.is-active-otp .badge-pill-priority {
  background: #dcfce7 !important;
  color: #14532d !important;
  border: 1px solid #86efac !important;
}

/* ===================================================
   MODERN INPUT FIELDS (High Contrast Borders & Text)
   =================================================== */
.modern-input-group {
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.auth-input {
  background-color: #ffffff;
  border: 1.5px solid #94a3b8;
  color: #0f172a;
  font-weight: 600;
  border-left: none;
  min-height: 38px;
  transition: all 0.2s ease;
}
.auth-input::placeholder {
  color: #64748b;
  font-weight: 400;
}
[data-bs-theme="dark"] .auth-input {
  background-color: #0b0f19;
  border: 1.5px solid #475569;
  color: #ffffff;
  border-left: none;
}
[data-bs-theme="dark"] .auth-input::placeholder {
  color: #94a3b8;
  font-weight: 400;
}
.auth-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.25);
  color: #0f172a;
}
[data-bs-theme="dark"] .auth-input:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 2.5px rgba(96, 165, 250, 0.3);
  color: #ffffff;
}

.auth-input-icon {
  background-color: #f1f5f9;
  border: 1.5px solid #94a3b8;
  border-right: none;
  color: #0f172a;
  min-height: 38px;
}
[data-bs-theme="dark"] .auth-input-icon {
  background-color: #1e293b;
  border: 1.5px solid #475569;
  border-right: none;
  color: #f8fafc;
}

.auth-input-btn {
  background-color: #f1f5f9;
  border: 1.5px solid #94a3b8;
  border-left: none;
  color: #0f172a;
  min-height: 38px;
}
[data-bs-theme="dark"] .auth-input-btn {
  background-color: #1e293b;
  border: 1.5px solid #475569;
  border-left: none;
  color: #f8fafc;
}

.auth-icon-wa {
  color: #15803d !important;
}
[data-bs-theme="dark"] .auth-icon-wa {
  color: #4ade80 !important;
}

.auth-icon-key {
  color: #1d4ed8 !important;
}
[data-bs-theme="dark"] .auth-icon-key {
  color: #93c5fd !important;
}

/* ===================================================
   BOXES & ALERTS (Rule 2 & 3)
   =================================================== */
.auth-error-box {
  background: #fef2f2;
  border: 1.5px solid #f87171;
}
.auth-error-icon {
  color: #dc2626;
}
.auth-error-title {
  color: #991b1b;
  font-weight: 800;
}
.auth-error-desc {
  color: #b91c1c;
  font-weight: 500;
}

[data-bs-theme="dark"] .auth-error-box {
  background: rgba(239, 68, 68, 0.2);
  border: 1.5px solid rgba(248, 113, 113, 0.5);
}
[data-bs-theme="dark"] .auth-error-icon {
  color: #f87171;
}
[data-bs-theme="dark"] .auth-error-title {
  color: #fca5a5;
  font-weight: 800;
}
[data-bs-theme="dark"] .auth-error-desc {
  color: #fee2e2;
  font-weight: 500;
}

.auth-highlight-box {
  background: #f0fdf4;
  border: 1.5px solid #86efac;
}
.auth-highlight-icon-wrap {
  background: #dcfce7;
  color: #15803d;
}
.auth-highlight-title {
  color: #14532d;
}
.auth-highlight-desc {
  color: #166534;
  font-weight: 500;
}

[data-bs-theme="dark"] .auth-highlight-box {
  background: rgba(22, 101, 52, 0.25);
  border: 1.5px solid rgba(74, 222, 128, 0.4);
}
[data-bs-theme="dark"] .auth-highlight-icon-wrap {
  background: rgba(34, 197, 94, 0.3);
  color: #86efac;
}
[data-bs-theme="dark"] .auth-highlight-title {
  color: #86efac;
}
[data-bs-theme="dark"] .auth-highlight-desc {
  color: #f0fdf4;
  font-weight: 500;
}

.auth-success-box {
  background: #f0fdf4;
  border: 1.5px solid #86efac;
}
.auth-success-icon {
  color: #16a34a;
}
.auth-success-text {
  color: #14532d;
  font-weight: 500;
}
[data-bs-theme="dark"] .auth-success-box {
  background: rgba(22, 101, 52, 0.25);
  border: 1.5px solid rgba(74, 222, 128, 0.4);
}
[data-bs-theme="dark"] .auth-success-icon {
  color: #86efac;
}
[data-bs-theme="dark"] .auth-success-text {
  color: #f0fdf4;
  font-weight: 500;
}

.auth-timer-badge {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #78350f;
  font-weight: 800;
}
[data-bs-theme="dark"] .auth-timer-badge {
  background: rgba(245, 158, 11, 0.25);
  border: 1px solid rgba(245, 158, 11, 0.6);
  color: #fde047;
  font-weight: 800;
}

.auth-debug-box {
  background: #eff6ff;
  border-color: #93c5fd !important;
  color: #1e3a8a;
}
.auth-debug-code {
  background: #dbeafe;
  color: #1e40af;
  padding: 2px 6px;
  border-radius: 4px;
}
.auth-debug-btn {
  background: #ffffff;
  border: 1px solid #1d4ed8;
  color: #1d4ed8;
}
[data-bs-theme="dark"] .auth-debug-box {
  background: rgba(30, 58, 138, 0.35);
  border-color: #60a5fa !important;
  color: #dbeafe;
}
[data-bs-theme="dark"] .auth-debug-code {
  background: rgba(59, 130, 246, 0.3);
  color: #ffffff;
}
[data-bs-theme="dark"] .auth-debug-btn {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid #93c5fd;
  color: #93c5fd;
}

/* Notice Box (Rule 2: Never washed out yellow) */
.auth-notice-box {
  background: #fef3c7;
  border-left: 4px solid #d97706 !important;
}
.notice-icon {
  color: #b45309;
}
.notice-title {
  color: #78350f;
  font-weight: 800;
}
.notice-desc {
  color: #78350f;
  font-weight: 500;
}
.notice-link {
  color: #92400e !important;
  font-weight: 800;
}
.notice-link:hover {
  color: #78350f !important;
}

[data-bs-theme="dark"] .auth-notice-box {
  background: rgba(245, 158, 11, 0.15);
  border-left: 4px solid #f59e0b !important;
}
[data-bs-theme="dark"] .notice-icon {
  color: #fde047;
}
[data-bs-theme="dark"] .notice-title {
  color: #fde047;
  font-weight: 800;
}
[data-bs-theme="dark"] .notice-desc {
  color: #fef3c7;
  font-weight: 500;
}
[data-bs-theme="dark"] .notice-link {
  color: #fde047 !important;
  font-weight: 800;
}

/* Buttons */
.auth-submit-btn {
  min-height: 38px;
  color: #ffffff !important;
  font-weight: 700;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.auth-submit-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px -4px rgba(22, 163, 74, 0.45) !important;
}

.auth-google-btn {
  background: #ffffff;
  border: 1.5px solid #94a3b8;
  color: #0f172a;
  min-height: 38px;
  transition: all 0.2s ease;
}
.auth-google-btn:hover {
  background: #f8fafc;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
}
[data-bs-theme="dark"] .auth-google-btn {
  background: #ffffff;
  border: 1.5px solid #ffffff;
  color: #0f172a;
}

.auth-google-icon-circle {
  background: #fee2e2;
  border: 1px solid #fca5a5;
}
[data-bs-theme="dark"] .auth-google-icon-circle {
  background: rgba(239, 68, 68, 0.2);
  border: 1px solid rgba(248, 113, 113, 0.4);
}

/* Top Navigation Pills */
.auth-nav-pill,
.auth-theme-toggle {
  background: #ffffff;
  border: 1.5px solid #94a3b8;
  color: #0f172a;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}
[data-bs-theme="dark"] .auth-nav-pill,
[data-bs-theme="dark"] .auth-theme-toggle {
  background: #1e293b;
  border: 1.5px solid #475569;
  color: #f8fafc;
}
.auth-nav-pill:hover,
.auth-theme-toggle:hover {
  filter: brightness(0.95);
}
[data-bs-theme="dark"] .auth-nav-pill:hover,
[data-bs-theme="dark"] .auth-theme-toggle:hover {
  filter: brightness(1.2);
}

/* ===================================================
   PORTAL RESMI & STATUS ONLINE BADGES (Strict Rule 3)
   =================================================== */
.badge-portal-resmi {
  background: #dbeafe !important;
  border: 1.5px solid #93c5fd !important;
  color: #1e40af !important;
  font-weight: 800;
  box-shadow: 0 1px 3px rgba(30, 64, 175, 0.1);
}
.badge-portal-resmi i {
  color: #1d4ed8 !important;
}
[data-bs-theme="dark"] .badge-portal-resmi {
  background: rgba(30, 58, 138, 0.45) !important;
  border: 1.5px solid #60a5fa !important;
  color: #bfdbfe !important;
  text-shadow: 0 0 10px rgba(147, 197, 253, 0.6);
  box-shadow: 0 0 12px rgba(59, 130, 246, 0.2);
}
[data-bs-theme="dark"] .badge-portal-resmi i {
  color: #93c5fd !important;
}

.badge-status-online {
  background: #dcfce7 !important;
  border: 1.5px solid #86efac !important;
  color: #14532d !important;
  font-weight: 800;
  letter-spacing: 0.3px;
  box-shadow: 0 1px 3px rgba(20, 83, 45, 0.1);
}
.online-indicator-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #15803d;
  display: inline-block;
}
[data-bs-theme="dark"] .badge-status-online {
  background: rgba(20, 83, 45, 0.5) !important;
  border: 1.5px solid #4ade80 !important;
  color: #86efac !important;
  text-shadow: 0 0 10px rgba(134, 239, 172, 0.7);
  box-shadow: 0 0 12px rgba(34, 197, 94, 0.25);
}
[data-bs-theme="dark"] .online-indicator-dot {
  background: #4ade80;
  box-shadow: 0 0 8px #4ade80;
}
</style>
