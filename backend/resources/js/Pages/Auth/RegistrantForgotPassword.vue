<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
  schoolName: { type: String, default: 'MA Unggulan Wahid Hasyim Balung' },
});

const toast = useToast();
const { isDark, toggleTheme } = useTheme();

// Multi-step flow: 1 = Input WhatsApp Phone, 2 = Input OTP & New Password
const step = ref(1);
const phone = ref('');
const otpCode = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);

const isSendingOtp = ref(false);
const isResetting = ref(false);
const otpCountdown = ref(0);
let timerInterval = null;
const otpDebugHint = ref(null);

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

async function requestResetOtp() {
  if (!phone.value || phone.value.trim().length < 8) {
    toast.error('Masukkan nomor WhatsApp yang valid (minimal 8 digit).');
    return;
  }

  isSendingOtp.value = true;
  otpDebugHint.value = null;

  try {
    const res = await fetch('/pendaftar/lupa-password/send-otp', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ phone: phone.value.trim() }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success(data.message || 'Kode verifikasi telah dikirimkan ke WhatsApp Anda!');
      step.value = 2;
      startOtpCountdown(data.expires_in || 300);
      if (data.debug_otp) {
        otpDebugHint.value = data.debug_otp;
      }
    } else {
      toast.error(data.message || 'Nomor WhatsApp tidak terdaftar sebagai akun pendaftar.');
    }
  } catch (err) {
    toast.error('Terjadi kendala jaringan saat menghubungi server.');
  } finally {
    isSendingOtp.value = false;
  }
}

async function handleResetPassword() {
  if (!otpCode.value || otpCode.value.trim().length !== 6) {
    toast.error('Masukkan 6 digit kode verifikasi OTP WhatsApp.');
    return;
  }

  if (!password.value || password.value.length < 6) {
    toast.error('Password baru minimal 6 karakter.');
    return;
  }

  if (password.value !== passwordConfirmation.value) {
    toast.error('Konfirmasi password tidak cocok.');
    return;
  }

  isResetting.value = true;
  try {
    const res = await fetch('/pendaftar/lupa-password/reset', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        phone: phone.value.trim(),
        otp: otpCode.value.trim(),
        password: password.value,
        password_confirmation: passwordConfirmation.value,
      }),
    });

    const data = await res.json();
    if (res.ok && data.success) {
      toast.success(data.message || 'Password berhasil diperbarui! Mengalihkan ke dashboard...');
      setTimeout(() => {
        window.location.href = data.redirect || '/pendaftar/dashboard';
      }, 700);
    } else {
      toast.error(data.message || 'Gagal mereset password. Pastikan kode OTP benar.');
    }
  } catch (err) {
    toast.error('Terjadi kesalahan jaringan.');
  } finally {
    isResetting.value = false;
  }
}

function resendOtp() {
  requestResetOtp();
}

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});
</script>

<template>
  <Head title="Reset Password Akun Pendaftar - SEMARAK ALIYAH XIV 2026" />

  <div class="auth-page-wrapper min-h-screen d-flex flex-column justify-content-between p-3 p-md-4">
    
    <!-- Top Utility Bar: Back & Theme Toggle -->
    <header class="w-100 d-flex align-items-center justify-content-between mx-auto" style="max-width: 540px;">
      <Link href="/pendaftar/login" class="btn btn-sm auth-back-btn rounded-pill d-flex align-items-center gap-1.5 px-3 py-1.5 fs-12 fw-semibold">
        <i class="ri-arrow-left-line"></i>
        <span>Kembali ke Login</span>
      </Link>

      <button
        type="button"
        class="btn btn-sm auth-theme-toggle rounded-pill d-flex align-items-center gap-1.5 px-3 py-1.5 fs-12 fw-semibold"
        @click="toggleTheme"
        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
      >
        <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line text-primary'"></i>
        <span>{{ isDark ? 'Mode Terang' : 'Mode Gelap' }}</span>
      </button>
    </header>

    <!-- Center Card Container -->
    <main class="w-100 my-auto py-3">
      <div class="auth-card-container w-100 mx-auto" style="max-width: 520px;">
        
        <!-- Header Brand -->
        <div class="text-center mb-4">
          <Link href="/" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
            <img src="/assets/img/logo.png" alt="Logo" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: contain;" />
            <div class="text-start">
              <div class="fw-black fs-15 tracking-wide text-uppercase auth-title" style="letter-spacing: 0.5px;">SEMARAK ALIYAH XIV</div>
              <div class="fs-12 auth-desc fw-semibold text-truncate" style="max-width: 280px;">{{ schoolName }}</div>
            </div>
          </Link>

          <h1 class="fs-22 fw-bold auth-title mb-1">Reset Password Akun</h1>
          <p class="fs-13 auth-desc mb-0">Atur ulang password pendaftaran Anda dengan verifikasi aman via WhatsApp.</p>
        </div>

        <!-- Glass Card Form -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden auth-card">
          <div class="card-body p-4 p-md-4.5">
            
            <!-- STEP 1: Masukkan Nomor WhatsApp -->
            <div v-if="step === 1">
              <div class="p-3 rounded-3 mb-3.5 auth-info-box d-flex gap-2.5 align-items-start">
                <i class="ri-information-line fs-18 flex-shrink-0 mt-0.5"></i>
                <div class="fs-12 line-clamp-3">
                  Masukkan nomor WhatsApp aktif yang terdaftar saat membuat akun pendaftar. Sistem akan mengirimkan 6 digit kode OTP verifikasi.
                </div>
              </div>

              <form @submit.prevent="requestResetOtp" class="d-flex flex-column gap-3">
                <div>
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Nomor WhatsApp Terdaftar <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-whatsapp-line text-success"></i>
                    </span>
                    <input
                      v-model="phone"
                      type="tel"
                      class="form-control auth-input fs-13 py-2.5"
                      placeholder="Contoh: 08123456789 atau 628123456789"
                      required
                      autofocus
                    />
                  </div>
                  <div class="fs-11 auth-desc mt-1.5">
                    Pastikan nomor WhatsApp Anda aktif dan dapat menerima pesan dari panitia.
                  </div>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary w-100 py-2.5 fs-13 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-1"
                  :disabled="isSendingOtp"
                >
                  <span v-if="isSendingOtp" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-send-plane-fill fs-15"></i>
                  <span>{{ isSendingOtp ? 'Mengirim Kode OTP...' : 'Kirim Kode Verifikasi WhatsApp' }}</span>
                </button>
              </form>
            </div>

            <!-- STEP 2: Input OTP & Set New Password -->
            <div v-else-if="step === 2">
              <div class="p-3 rounded-3 mb-3.5 auth-success-box d-flex gap-2.5 align-items-start">
                <i class="ri-checkbox-circle-line fs-18 text-success flex-shrink-0 mt-0.5"></i>
                <div class="fs-12">
                  Kode verifikasi 6 digit telah dikirim ke nomor <strong>{{ phone }}</strong>.
                  <a href="javascript:void(0)" class="text-primary fw-semibold ms-1 text-decoration-none" @click="step = 1">Ubah nomor</a>
                </div>
              </div>

              <form @submit.prevent="handleResetPassword" class="d-flex flex-column gap-3">
                <!-- 6-digit OTP -->
                <div>
                  <label class="form-label fs-13 fw-semibold auth-label d-flex justify-content-between mb-1">
                    <span>Kode OTP WhatsApp (6 Digit) <span class="text-danger">*</span></span>
                    <span v-if="otpCountdown > 0" class="badge bg-warning bg-opacity-20 text-warning border border-warning border-opacity-30 fs-11">
                      <i class="ri-timer-line me-1"></i>{{ formattedCountdown }}
                    </span>
                    <span v-else class="text-danger fs-11 fw-semibold">Kadaluwarsa</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-shield-keyhole-line text-info"></i>
                    </span>
                    <input
                      v-model="otpCode"
                      type="text"
                      inputmode="numeric"
                      maxlength="6"
                      class="form-control auth-input fs-16 font-monospace fw-bold tracking-widest text-center py-2"
                      placeholder="• • • • • •"
                      required
                      autofocus
                    />
                  </div>

                  <!-- Debug Hint in local testing -->
                  <div v-if="otpDebugHint" class="alert alert-info border-0 rounded-3 p-2 mt-2 fs-11 d-flex align-items-center justify-content-between">
                    <span>💡 <strong>Kode OTP Uji Coba:</strong> <code>{{ otpDebugHint }}</code></span>
                    <button type="button" class="btn btn-xs btn-outline-info py-0 px-2" @click="otpCode = otpDebugHint">Isi Cepat</button>
                  </div>
                </div>

                <!-- Password Baru -->
                <div>
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Password Baru <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-lock-line"></i>
                    </span>
                    <input
                      v-model="password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control auth-input fs-13 py-2"
                      placeholder="Minimal 6 karakter"
                      required
                    />
                    <button
                      type="button"
                      class="btn auth-input-btn px-3"
                      @click="showPassword = !showPassword"
                    >
                      <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                  </div>
                </div>

                <!-- Konfirmasi Password Baru -->
                <div>
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Ulangi Password Baru <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-lock-check-line"></i>
                    </span>
                    <input
                      v-model="passwordConfirmation"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control auth-input fs-13 py-2"
                      placeholder="Ketik ulang password baru"
                      required
                    />
                  </div>
                </div>

                <div class="d-flex align-items-center justify-content-between fs-12 mt-1">
                  <span class="auth-desc">Tidak menerima kode?</span>
                  <button
                    type="button"
                    class="btn btn-link p-0 text-primary fw-semibold fs-12 text-decoration-none"
                    :disabled="otpCountdown > 0 || isSendingOtp"
                    @click="resendOtp"
                  >
                    Kirim Ulang Kode
                  </button>
                </div>

                <button
                  type="submit"
                  class="btn btn-primary w-100 py-2.5 fs-13 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-1"
                  :disabled="isResetting"
                >
                  <span v-if="isResetting" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-check-double-line fs-16"></i>
                  <span>{{ isResetting ? 'Menyimpan Password...' : 'Simpan & Masuk ke Akun' }}</span>
                </button>
              </form>
            </div>

          </div>

          <!-- Card Footer -->
          <div class="card-footer auth-card-footer p-3 text-center">
            <div class="fs-13 auth-desc">
              Sudah ingat password Anda?
              <Link href="/pendaftar/login" class="text-primary fw-bold text-decoration-none ms-1">
                Masuk di Sini
              </Link>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="w-100 text-center py-2 mx-auto" style="max-width: 540px;">
      <div class="fs-12 auth-desc">
        © 2026 Semarak Aliyah XIV • MA Unggulan Wahid Hasyim Balung Jember
      </div>
    </footer>

  </div>
</template>

<style scoped>
/* Page Wrapper Backgrounds */
.auth-page-wrapper {
  background: radial-gradient(circle at 50% 15%, #e0e7ff 0%, #f8fafc 60%, #f1f5f9 100%);
  transition: background 0.3s ease;
}
[data-bs-theme="dark"] .auth-page-wrapper {
  background: radial-gradient(circle at 50% 20%, #1e1b4b 0%, #0b0f19 70%, #05070e 100%);
}

/* Auth Card Glass Style */
.auth-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08);
}
[data-bs-theme="dark"] .auth-card {
  background: rgba(15, 23, 42, 0.82);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.auth-card-footer {
  background: rgba(248, 250, 252, 0.7);
  border-top: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .auth-card-footer {
  background: rgba(15, 23, 42, 0.5);
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Typography Contrast (Rule 2) */
.auth-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .auth-title {
  color: #ffffff;
}

.auth-desc {
  color: #475569;
}
[data-bs-theme="dark"] .auth-desc {
  color: #94a3b8;
}

.auth-label {
  color: #1e293b;
}
[data-bs-theme="dark"] .auth-label {
  color: #f8fafc;
}

/* Input Fields */
.auth-input {
  background-color: #ffffff;
  border-color: #cbd5e1;
  color: #0f172a;
}
[data-bs-theme="dark"] .auth-input {
  background-color: rgba(30, 41, 59, 0.7);
  border-color: rgba(255, 255, 255, 0.15);
  color: #ffffff;
}
.auth-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.auth-input-icon {
  background-color: #f1f5f9;
  border-color: #cbd5e1;
  color: #475569;
}
[data-bs-theme="dark"] .auth-input-icon {
  background-color: rgba(15, 23, 42, 0.6);
  border-color: rgba(255, 255, 255, 0.15);
  color: #94a3b8;
}

.auth-input-btn {
  background-color: #f8fafc;
  border: 1px solid #cbd5e1;
  border-left: 0;
  color: #64748b;
}
[data-bs-theme="dark"] .auth-input-btn {
  background-color: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-left: 0;
  color: #94a3b8;
}

/* Info & Success Boxes */
.auth-info-box {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}
[data-bs-theme="dark"] .auth-info-box {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.25);
  color: #86efac;
}

.auth-success-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
}
[data-bs-theme="dark"] .auth-success-box {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.25);
  color: #93c5fd;
}

/* Header Buttons */
.auth-back-btn,
.auth-theme-toggle {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
[data-bs-theme="dark"] .auth-back-btn,
[data-bs-theme="dark"] .auth-theme-toggle {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #f8fafc;
}
.auth-back-btn:hover,
.auth-theme-toggle:hover {
  filter: brightness(0.96);
}
[data-bs-theme="dark"] .auth-back-btn:hover,
[data-bs-theme="dark"] .auth-theme-toggle:hover {
  filter: brightness(1.2);
}
</style>
