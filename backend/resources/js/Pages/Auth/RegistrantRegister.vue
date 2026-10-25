<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
  schools: { type: Array, default: () => [] },
  districts: { type: Array, default: () => [] },
  schoolName: { type: String, default: 'MA Unggulan Wahid Hasyim Balung' },
  redirectTo: { type: String, default: '' },
  lomba: { type: String, default: '' },
  isLoginRequired: { type: Boolean, default: false },
});

const toast = useToast();
const { isDark, toggleTheme } = useTheme();

const form = useForm({
  name: '',
  registrant_role: 'Pendamping', // 'Pendamping', 'Koordinator', 'Siswa Peserta'
  gender: 'Laki-laki', // 'Laki-laki', 'Perempuan'
  school_name: '',
  school_district: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
  redirect_to: props.redirectTo,
  lomba: props.lomba || '',
});

const showPassword = ref(false);
const customSchoolInput = ref(false);

// Role Options for CustomSelect (Rule 1)
const roleOptions = [
  { value: 'Pendamping', label: 'Guru Pendamping / Pembina Lomba' },
  { value: 'Koordinator', label: 'Koordinator Kontingen Sekolah' },
  { value: 'Siswa Peserta', label: 'Siswa / Siswi Peserta Mandiri' },
];

// Gender Options for CustomSelect (Rule 1)
const genderOptions = [
  { value: 'Laki-laki', label: 'Laki-laki (Ikhwan)' },
  { value: 'Perempuan', label: 'Perempuan (Akhwat)' },
];

// School Options for CustomSelect
const schoolSelectOptions = computed(() => {
  const list = (props.schools || []).map(s => ({
    value: s.name,
    label: `${s.name}${(s.district || s.kecamatan) ? ' (' + (s.district || s.kecamatan) + ')' : ''}`,
    district: s.district || s.kecamatan || '',
  }));
  list.push({ value: '__other__', label: '➕ Tulis Asal Sekolah Lainnya (Jika belum ada)' });
  return list;
});

// District Options for CustomSelect
const districtOptions = computed(() => {
  return (props.districts || []).map(d => ({
    value: d,
    label: `Kec. ${d}`,
  }));
});

function handleSchoolSelect(selectedName) {
  if (selectedName === '__other__') {
    customSchoolInput.value = true;
    form.school_name = '';
    return;
  }
  form.school_name = selectedName;
  const found = (props.schools || []).find(s => s.name === selectedName);
  if (found && (found.district || found.kecamatan)) {
    form.school_district = found.district || found.kecamatan;
  }
}

function submitRegister() {
  form.post('/pendaftar/register', {
    preserveScroll: true,
    onError: () => {
      toast.error('Harap lengkapi semua kolom formulir pendaftaran dengan benar.');
    }
  });
}
</script>

<template>
  <Head title="Pendaftaran Akun Pendaftar - SEMARAK ALIYAH XIV 2026" />

  <div class="auth-page-wrapper min-h-screen d-flex flex-column justify-content-between p-3 p-md-4 py-4">
    
    <!-- Top Utility Bar: Back to Login & Theme Toggle -->
    <header class="w-100 d-flex align-items-center justify-content-between mx-auto" style="max-width: 660px;">
      <Link href="/pendaftar/login" class="btn btn-sm auth-back-btn rounded-pill d-flex align-items-center gap-1.5 px-3 py-1.5 fs-12 fw-semibold">
        <i class="ri-arrow-left-line"></i>
        <span>Sudah Punya Akun? Masuk</span>
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
      <div class="auth-card-container w-100 mx-auto" style="max-width: 640px;">
        
        <!-- Logo & Heading -->
        <div class="text-center mb-4">
          <Link href="/" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
            <img src="/assets/img/logo.png" alt="Logo" class="rounded-circle shadow-sm" style="width: 50px; height: 50px; object-fit: contain;" />
            <div class="text-start">
              <div class="fw-black fs-15 tracking-wide text-uppercase auth-title" style="letter-spacing: 0.5px;">SEMARAK ALIYAH XIV</div>
              <div class="fs-12 auth-desc fw-semibold text-truncate" style="max-width: 320px;">{{ schoolName }}</div>
            </div>
          </Link>

          <h1 class="fs-22 fw-bold auth-title mb-1">Daftar Akun Pendaftar</h1>
          <p class="fs-13 auth-desc mb-0">Buat akun untuk mendaftarkan delegasi sekolah, unggah berkas, dan pantau status lomba.</p>
        </div>

        <!-- Registration Form Card -->
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden auth-card">
          
          <div class="card-body p-4 p-md-4.5">
            <form @submit.prevent="submitRegister" class="d-flex flex-column gap-3">

              <!-- 1. Nama Lengkap -->
              <div>
                <label class="form-label fs-13 fw-semibold auth-label mb-1">
                  Nama Lengkap <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <span class="input-group-text auth-input-icon px-3">
                    <i class="ri-user-line"></i>
                  </span>
                  <input
                    v-model="form.name"
                    type="text"
                    class="form-control auth-input fs-13 py-2"
                    placeholder="Contoh: Budi Prasetyo, S.Pd. atau Muhammad Fauzan"
                    required
                    autofocus
                    :class="{ 'is-invalid': form.errors.name }"
                  />
                </div>
                <div v-if="form.errors.name" class="text-danger fs-12 mt-1 fw-medium">
                  {{ form.errors.name }}
                </div>
              </div>

              <!-- 2. Status Sebagai & Gender (Row) -->
              <div class="row g-3">
                <div class="col-md-7">
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Sebagai Pendaftar <span class="text-danger">*</span>
                  </label>
                  <!-- Rule 1: Must use CustomSelect -->
                  <CustomSelect
                    v-model="form.registrant_role"
                    :options="roleOptions"
                    size="md"
                    placeholder="Pilih status pendaftar..."
                    prefixIcon="ri-user-star-line"
                  />
                  <div v-if="form.errors.registrant_role" class="text-danger fs-12 mt-1 fw-medium">
                    {{ form.errors.registrant_role }}
                  </div>
                </div>

                <div class="col-md-5">
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Jenis Kelamin <span class="text-danger">*</span>
                  </label>
                  <!-- Rule 1: Must use CustomSelect -->
                  <CustomSelect
                    v-model="form.gender"
                    :options="genderOptions"
                    size="md"
                    placeholder="Pilih gender..."
                    prefixIcon="ri-men-line"
                  />
                  <div v-if="form.errors.gender" class="text-danger fs-12 mt-1 fw-medium">
                    {{ form.errors.gender }}
                  </div>
                </div>
              </div>

              <!-- 3. Asal Sekolah & Kecamatan -->
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fs-13 fw-semibold auth-label mb-0">
                    Asal Sekolah / Madrasah (SMP/MTs) <span class="text-danger">*</span>
                  </label>
                  <button
                    type="button"
                    class="btn btn-link p-0 fs-12 text-decoration-none text-primary fw-medium"
                    @click="customSchoolInput = !customSchoolInput"
                  >
                    {{ customSchoolInput ? 'Pilih dari Daftar' : 'Tulis Manual Sekolah' }}
                  </button>
                </div>

                <!-- CustomSelect Master Sekolah jika ada di list -->
                <div v-if="!customSchoolInput">
                  <CustomSelect
                    :modelValue="form.school_name"
                    @update:modelValue="handleSchoolSelect"
                    :options="schoolSelectOptions"
                    size="md"
                    placeholder="Pilih atau cari asal sekolah..."
                    prefixIcon="ri-building-line"
                    searchable
                  />
                </div>

                <!-- Input manual jika sekolah belum terdata -->
                <div v-else class="input-group">
                  <span class="input-group-text auth-input-icon px-3">
                    <i class="ri-edit-line"></i>
                  </span>
                  <input
                    v-model="form.school_name"
                    type="text"
                    class="form-control auth-input fs-13 py-2"
                    placeholder="Tuliskan nama lengkap sekolah/madrasah..."
                    required
                  />
                </div>
                <div v-if="form.errors.school_name" class="text-danger fs-12 mt-1 fw-medium">
                  {{ form.errors.school_name }}
                </div>
              </div>

              <!-- Kecamatan Asal Sekolah -->
              <div>
                <label class="form-label fs-13 fw-semibold auth-label mb-1">
                  Kecamatan Asal Sekolah <span class="text-danger">*</span>
                </label>
                <!-- Rule 1: Must use CustomSelect -->
                <CustomSelect
                  v-model="form.school_district"
                  :options="districtOptions"
                  size="md"
                  placeholder="Pilih kecamatan asal sekolah..."
                  prefixIcon="ri-map-pin-line"
                  searchable
                />
                <div v-if="form.errors.school_district" class="text-danger fs-12 mt-1 fw-medium">
                  {{ form.errors.school_district }}
                </div>
              </div>

              <!-- 4. Nomor WA & Email (Row) -->
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Nomor WhatsApp Aktif <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-whatsapp-line text-success"></i>
                    </span>
                    <input
                      v-model="form.phone"
                      type="tel"
                      class="form-control auth-input fs-13 py-2"
                      placeholder="Contoh: 08123456789"
                      required
                      :class="{ 'is-invalid': form.errors.phone }"
                    />
                  </div>
                  <div class="fs-11 auth-desc mt-1">
                    Untuk menerima notifikasi status lomba & login kode OTP.
                  </div>
                  <div v-if="form.errors.phone" class="text-danger fs-12 mt-1 fw-medium">
                    {{ form.errors.phone }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Alamat Email Aktif <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-mail-line"></i>
                    </span>
                    <input
                      v-model="form.email"
                      type="email"
                      class="form-control auth-input fs-13 py-2"
                      placeholder="nama@email.com"
                      required
                      :class="{ 'is-invalid': form.errors.email }"
                    />
                  </div>
                  <div class="fs-11 auth-desc mt-1">
                    Dapat digunakan untuk masuk akun.
                  </div>
                  <div v-if="form.errors.email" class="text-danger fs-12 mt-1 fw-medium">
                    {{ form.errors.email }}
                  </div>
                </div>
              </div>

              <!-- 5. Password & Konfirmasi Password (Row) -->
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Password Akun <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-lock-line"></i>
                    </span>
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control auth-input fs-13 py-2"
                      placeholder="Minimal 6 karakter"
                      required
                      :class="{ 'is-invalid': form.errors.password }"
                    />
                    <button
                      type="button"
                      class="btn auth-input-btn px-2.5"
                      @click="showPassword = !showPassword"
                      title="Lihat Password"
                    >
                      <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                  </div>
                  <div v-if="form.errors.password" class="text-danger fs-12 mt-1 fw-medium">
                    {{ form.errors.password }}
                  </div>
                </div>

                <div class="col-md-6">
                  <label class="form-label fs-13 fw-semibold auth-label mb-1">
                    Ulangi Password <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text auth-input-icon px-3">
                      <i class="ri-lock-check-line"></i>
                    </span>
                    <input
                      v-model="form.password_confirmation"
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control auth-input fs-13 py-2"
                      placeholder="Ketik ulang password"
                      required
                    />
                  </div>
                </div>
              </div>

              <!-- Info Box -->
              <div class="p-3 rounded-3 auth-notice-info d-flex gap-2.5 align-items-start mt-2">
                <i class="ri-information-line fs-18 text-primary flex-shrink-0 mt-0.5"></i>
                <div class="fs-12">
                  Setelah membuat akun, Anda dapat masuk kapan saja untuk mendaftarkan delegasi cabang lomba, memperbarui biodata peserta, serta mengunggah berkas persyaratan selagi periode pendaftaran belum ditutup.
                </div>
              </div>

              <!-- Submit Button -->
              <button
                type="submit"
                class="btn btn-primary py-2.5 fw-semibold fs-14 rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2 mt-2"
                :disabled="form.processing"
              >
                <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                <i v-else class="ri-user-add-line fs-16"></i>
                <span>{{ form.processing ? 'Menyimpan Akun Pendaftar...' : 'Daftar Akun Pendaftar Sekarang' }}</span>
              </button>

            </form>
          </div>

          <!-- Card Footer -->
          <div class="card-footer auth-card-footer p-3 text-center">
            <div class="fs-13 auth-desc">
              Sudah memiliki akun pendaftar?
              <Link :href="`/pendaftar/login?redirect_to=${redirectTo}${lomba ? '&lomba=' + lomba : ''}`" class="text-primary fw-bold text-decoration-none ms-1">
                Masuk di Sini
              </Link>
            </div>
          </div>

        </div>

        <!-- Back to Public Links -->
        <div class="d-flex align-items-center justify-content-between mt-4 px-2 fs-12">
          <Link href="/" class="auth-desc text-decoration-none hover:text-primary d-flex align-items-center gap-1">
            <i class="ri-arrow-left-line"></i> Kembali ke Beranda
          </Link>
          <Link v-if="!isLoginRequired" href="/layanan-registrasi" class="auth-desc text-decoration-none hover:text-primary d-flex align-items-center gap-1">
            <span>Formulir Registrasi Lomba</span> <i class="ri-arrow-right-line"></i>
          </Link>
        </div>

      </div>
    </main>

    <!-- Bottom Footer -->
    <footer class="w-100 text-center py-2 mx-auto" style="max-width: 660px;">
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

/* Notice Info Box */
.auth-notice-info {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
}
[data-bs-theme="dark"] .auth-notice-info {
  background: rgba(99, 102, 241, 0.08);
  border: 1px solid rgba(99, 102, 241, 0.2);
  color: #c7d2fe;
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
