<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  },
  presets: {
    type: Array,
    default: () => []
  }
});

const { success, error } = useToast();

const form = useForm({
  admin_login_bg_image: props.settings.admin_login_bg_image || '/assets/images/auth-one-bg.jpg',
  admin_login_bg_file: null,
  admin_login_bg_overlay_type: props.settings.admin_login_bg_overlay_type || 'gradient_velzon',
  admin_login_bg_overlay_opacity: Number(props.settings.admin_login_bg_overlay_opacity ?? 85),
  admin_login_bg_blur: Number(props.settings.admin_login_bg_blur ?? 0),
  admin_login_show_wave: Boolean(props.settings.admin_login_show_wave ?? true),
  admin_login_particles_active: Boolean(props.settings.admin_login_particles_active ?? true),
  admin_login_title: props.settings.admin_login_title || 'Selamat Datang Kembali !',
  admin_login_subtitle: props.settings.admin_login_subtitle || 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.',
  admin_login_recaptcha_enabled: Boolean(props.settings.admin_login_recaptcha_enabled ?? false),
  recaptcha_version: props.settings.recaptcha_version || 'v2_checkbox',
  recaptcha_site_key: props.settings.recaptcha_site_key || '',
  recaptcha_secret_key: props.settings.recaptcha_secret_key || '',
  admin_login_recaptcha_bypass_local: Boolean(props.settings.admin_login_recaptcha_bypass_local ?? true),
});

const isSaving = ref(false);
const isResetting = ref(false);
const showSecretKey = ref(false);
const filePreviewUrl = ref(null);
const fileInputRef = ref(null);

const recaptchaVersionOptions = [
  { value: 'v2_checkbox', label: 'reCAPTCHA v2 Checkbox ("Saya bukan robot")' },
  { value: 'v3_invisible', label: 'reCAPTCHA v3 Invisible (Skor Otomatis)' },
];

function useGoogleTestKeys() {
  form.recaptcha_site_key = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
  form.recaptcha_secret_key = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';
  form.recaptcha_version = 'v2_checkbox';
  success('Kunci resmi uji coba Google reCAPTCHA v2 berhasil dipasang.', 'Kunci Uji Dipasang');
}

// Overlay Color Options
const overlayOptions = [
  { 
    id: 'gradient_velzon', 
    name: 'Gradien Velzon (Navy Blue)', 
    desc: 'Warna resmi bawaan tema Velzon (Navy Blue to Indigo)',
    badgeColor: '#405189',
    gradient: 'linear-gradient(to right, rgba(54, 69, 116, 0.92), rgba(64, 81, 137, 0.95))'
  },
  { 
    id: 'dark', 
    name: 'Dark Midnight (Slate)', 
    desc: 'Latar gelap elegan kontras tinggi untuk suasana premium',
    badgeColor: '#0f172a',
    gradient: 'linear-gradient(to right, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.95))'
  },
  { 
    id: 'emerald', 
    name: 'Emerald Green (Islami Modern)', 
    desc: 'Nuansa hijau madrasah yang teduh, sejuk, dan islami',
    badgeColor: '#065f46',
    gradient: 'linear-gradient(135deg, rgba(6, 78, 59, 0.9), rgba(4, 120, 87, 0.92))'
  },
  { 
    id: 'gold', 
    name: 'Royal Amber & Gold', 
    desc: 'Gradien emas hangat untuk kesan kemegahan ajang kompetisi',
    badgeColor: '#92400e',
    gradient: 'linear-gradient(135deg, rgba(120, 53, 15, 0.88), rgba(180, 83, 9, 0.9))'
  },
  { 
    id: 'none', 
    name: 'Tanpa Overlay (Asli Transparan)', 
    desc: 'Menampilkan gambar asli secara penuh tanpa lapisan warna',
    badgeColor: '#64748b',
    gradient: 'transparent'
  },
];

// Computed Effective Background Image for Live Preview
const effectiveBgImage = computed(() => {
  if (filePreviewUrl.value) return filePreviewUrl.value;
  return form.admin_login_bg_image || '/assets/images/auth-one-bg.jpg';
});

// Computed Overlay Style for Live Preview
const previewOverlayStyle = computed(() => {
  const opacity = (form.admin_login_bg_overlay_opacity || 0) / 100;
  switch (form.admin_login_bg_overlay_type) {
    case 'dark':
      return { background: `rgba(15, 23, 42, ${opacity})` };
    case 'emerald':
      return { background: `linear-gradient(135deg, rgba(6, 78, 59, ${opacity}), rgba(4, 120, 87, ${opacity}))` };
    case 'gold':
      return { background: `linear-gradient(135deg, rgba(120, 53, 15, ${opacity}), rgba(180, 83, 9, ${opacity}))` };
    case 'none':
      return { background: 'transparent' };
    default:
      return { background: `linear-gradient(to right, rgba(54, 69, 116, ${opacity}), rgba(64, 81, 137, ${opacity}))` };
  }
});

// Handle File Selection with instant preview
function handleFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    error('Ukuran file gambar melebihi batas maksimal 5MB.', 'File Terlalu Besar');
    if (fileInputRef.value) fileInputRef.value.value = '';
    return;
  }

  form.admin_login_bg_file = file;
  filePreviewUrl.value = URL.createObjectURL(file);
}

function removeSelectedFile() {
  form.admin_login_bg_file = null;
  if (filePreviewUrl.value) {
    URL.revokeObjectURL(filePreviewUrl.value);
    filePreviewUrl.value = null;
  }
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

// Select a Preset
function applyPreset(preset) {
  removeSelectedFile();
  form.admin_login_bg_image = preset.image_url;
  form.admin_login_bg_overlay_type = preset.overlay_type;
  form.admin_login_bg_overlay_opacity = preset.overlay_opacity;
  form.admin_login_bg_blur = preset.blur;
  form.admin_login_show_wave = preset.show_wave;
}

// Submit Form
function submitForm() {
  isSaving.value = true;
  form.post('/admin/pengaturan/login-admin', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isSaving.value = false;
      removeSelectedFile();
      success('Tampilan halaman login admin berhasil disimpan dan diterapkan.', 'Tersimpan');
    },
    onError: () => {
      isSaving.value = false;
      error('Mohon periksa kembali kolom isian formulir.', 'Gagal Menyimpan');
    }
  });
}

// Reset to Default
function resetToDefault() {
  if (!confirm('Apakah Anda yakin ingin mengembalikan tampilan login admin ke tema default Velzon?')) {
    return;
  }

  isResetting.value = true;
  form.post('/admin/pengaturan/login-admin/reset', {
    preserveScroll: true,
    onSuccess: () => {
      isResetting.value = false;
      removeSelectedFile();
      form.admin_login_bg_image = '/assets/images/auth-one-bg.jpg';
      form.admin_login_bg_overlay_type = 'gradient_velzon';
      form.admin_login_bg_overlay_opacity = 85;
      form.admin_login_bg_blur = 0;
      form.admin_login_show_wave = true;
      form.admin_login_particles_active = true;
      form.admin_login_title = 'Selamat Datang Kembali !';
      form.admin_login_subtitle = 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.';
      form.admin_login_recaptcha_enabled = false;
      success('Tampilan login admin berhasil dikembalikan ke standar tema Velzon.', 'Berhasil Reset');
    },
    onError: () => {
      isResetting.value = false;
      error('Gagal mereset pengaturan ke default.', 'Error');
    }
  });
}
</script>

<template>
  <Head title="Kustomisasi Login Admin | Pengaturan Sistem" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-1 fw-bold text-dark-emphasis d-flex align-items-center gap-2">
                <i class="ri-image-edit-line text-primary fs-22"></i>
                Kustomisasi Tampilan Login Admin
              </h4>
              <p class="text-muted fs-12 mb-0">
                Atur gambar latar belakang (background image), gradien overlay, efek blur, serta teks sambutan pada halaman login panel admin.
              </p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
              <a 
                href="/login" 
                target="_blank" 
                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 shadow-sm px-3"
                title="Buka halaman login di tab baru"
              >
                <i class="ri-external-link-line"></i>
                <span>Lihat Halaman Login</span>
              </a>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm px-3"
                @click="resetToDefault"
                :disabled="isResetting || isSaving"
              >
                <i class="ri-refresh-line"></i>
                <span>{{ isResetting ? 'Mereset...' : 'Reset Default' }}</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm px-3"
                @click="submitForm"
                :disabled="isSaving || isResetting"
              >
                <i v-if="isSaving" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-line"></i>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Kustomisasi' }}</span>
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
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-building-line text-primary"></i>
                    <span>Profil Sekolah</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/mode-perawatan"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-tools-line text-warning"></i>
                    <span>Mode Perawatan</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/seo-og"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-search-eye-line text-info"></i>
                    <span>SEO & Open Graph</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/wa-gateway"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-whatsapp-line text-success"></i>
                    <span>WA Gateway</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/kebijakan-pendaftaran"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-user-settings-line text-primary"></i>
                    <span>Kebijakan Pendaftaran</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/login-admin"
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill fw-semibold fs-13 bg-primary text-white shadow-sm"
                  >
                    <i class="ri-image-edit-line"></i>
                    <span>Login Panel Admin</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/sipresensi"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-shield-keyhole-line text-success"></i>
                    <span>API Sipresensi</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/kop-invoice"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-file-paper-2-line text-info"></i>
                    <span>KOP &amp; Stempel Invoice</span>
                  </Link>
                </li>
              </ul>

              <div class="d-flex align-items-center gap-2">
                <a href="/login" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                  <i class="ri-external-link-line me-1"></i> Buka Halaman Login
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Info Alert -->
      <div class="alert alert-info border border-info border-opacity-30 d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 bg-info-subtle text-dark-emphasis">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-sm flex-shrink-0">
            <span class="avatar-title bg-info text-white rounded-circle fs-20">
              <i class="ri-palette-line"></i>
            </span>
          </div>
          <div>
            <h5 class="fs-14 fw-bold text-info mb-1">Personalisasi Halaman Masuk Admin Sesuai Identitas Semarak Aliyah</h5>
            <p class="fs-12 text-muted mb-0">
              Anda dapat mengganti foto latar default tema Velzon dengan dokumentasi kegiatan lomba, panggung, atau pamflet resmi. Dilengkapi pratinjau langsung (Live Preview) di sebelah kanan.
            </p>
          </div>
        </div>
      </div>

      <!-- Main Layout: Form Settings (Col-lg-7) + Live Preview (Col-lg-5) -->
      <div class="row g-4">
        <!-- LEFT: FORM CONTROLS -->
        <div class="col-lg-7">
          <form @submit.prevent="submitForm" class="d-flex flex-column gap-4">
            
            <!-- 1. PILIHAN PRESET BACKGROUND CEPAT -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-gallery-line text-warning fs-18"></i>
                  <h5 class="card-title text-dark-emphasis fs-14 fw-bold mb-0">Pilihan Cepat Background Preset</h5>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">
                  Pilih salah satu koleksi gambar latar belakang siap pakai di bawah ini dengan satu kali klik.
                </p>
              </div>
              <div class="card-body p-3">
                <div class="row g-2.5">
                  <div 
                    v-for="p in presets" 
                    :key="p.id" 
                    class="col-sm-6"
                  >
                    <div 
                      class="preset-card cursor-pointer p-2.5 rounded-3 border transition-all position-relative"
                      :class="form.admin_login_bg_image === p.image_url && !filePreviewUrl ? 'border-primary bg-primary-subtle shadow-sm active-preset' : 'bg-body'"
                      @click="applyPreset(p)"
                    >
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="preset-thumb-wrapper rounded-2 overflow-hidden flex-shrink-0 shadow-sm" style="width: 58px; height: 42px;">
                          <img :src="p.image_url" :alt="p.name" class="w-100 h-100 object-fit-cover" />
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                          <div class="d-flex align-items-center justify-content-between">
                            <h6 class="fs-12 fw-bold text-dark-emphasis mb-0 text-truncate">{{ p.name }}</h6>
                            <i v-if="form.admin_login_bg_image === p.image_url && !filePreviewUrl" class="ri-checkbox-circle-fill text-primary fs-14"></i>
                          </div>
                          <p class="text-muted fs-10 mb-0 text-truncate mt-0.5">{{ p.description }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. UPLOAD FOTO SENDIRI / KUSTOM URL -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-upload-cloud-line text-info fs-18"></i>
                  <h5 class="card-title text-dark-emphasis fs-14 fw-bold mb-0">Upload Foto Latar Kustom</h5>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">
                  Unggah file gambar dari komputer Anda atau masukkan path / URL gambar langsung.
                </p>
              </div>
              <div class="card-body p-3">
                <!-- File Upload Box -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">Unggah File Gambar Baru (JPG, PNG, WebP • Maks. 5MB)</label>
                  <div class="input-group">
                    <input 
                      type="file" 
                      ref="fileInputRef" 
                      class="form-control fs-12" 
                      accept="image/jpeg,image/png,image/jpg,image/webp" 
                      @change="handleFileChange"
                    />
                    <button 
                      v-if="filePreviewUrl" 
                      type="button" 
                      class="btn btn-outline-danger btn-sm" 
                      @click="removeSelectedFile"
                      title="Batalkan file pilihan"
                    >
                      <i class="ri-close-line"></i> Batalkan
                    </button>
                  </div>
                  <div v-if="filePreviewUrl" class="text-success fs-11 mt-1 d-flex align-items-center gap-1">
                    <i class="ri-checkbox-circle-line"></i> File terpilih aktif pada pratinjau live. Klik "Simpan Kustomisasi" untuk mengunggah ke server.
                  </div>
                </div>

                <!-- URL Path Input -->
                <div>
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis">Atau Masukkan Path / URL Gambar</label>
                  <div class="input-group">
                    <span class="input-group-text fs-12 text-muted">
                      <i class="ri-link"></i>
                    </span>
                    <input 
                      type="text" 
                      v-model="form.admin_login_bg_image" 
                      class="form-control fs-12" 
                      placeholder="Contoh: /assets/images/auth-one-bg.jpg atau https://..." 
                      :disabled="!!filePreviewUrl"
                    />
                  </div>
                  <div class="text-muted fs-11 mt-1">
                    Path relatif dari folder <code>public/</code> atau URL gambar eksternal yang valid.
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. EFEK VISUAL: OVERLAY GRADIENT, OPASITAS, & BLUR -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-contrast-drop-line text-success fs-18"></i>
                  <h5 class="card-title text-dark-emphasis fs-14 fw-bold mb-0">Efek Overlay & Transparansi Lapisan</h5>
                </div>
                <p class="text-muted fs-11 mb-0 mt-1">
                  Sesuaikan warna gradien penutup, kepekatan overlay, dan tingkat kelembutan blur agar form login tetap terbaca jelas.
                </p>
              </div>
              <div class="card-body p-3">
                <!-- Overlay Color Cards -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-2">Tipe Warna Lapisan Overlay</label>
                  <div class="row g-2">
                    <div 
                      v-for="opt in overlayOptions" 
                      :key="opt.id" 
                      class="col-md-6"
                    >
                      <label 
                        class="d-flex align-items-center gap-2.5 p-2 rounded-2 border cursor-pointer w-100 mb-0 transition-all"
                        :class="form.admin_login_bg_overlay_type === opt.id ? 'border-primary bg-primary-subtle text-primary fw-bold' : 'bg-body text-dark-emphasis'"
                      >
                        <input 
                          type="radio" 
                          name="overlay_type" 
                          :value="opt.id" 
                          v-model="form.admin_login_bg_overlay_type" 
                          class="form-check-input mt-0 flex-shrink-0"
                        />
                        <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                          <span 
                            class="rounded-circle border flex-shrink-0" 
                            :style="{ width: '16px', height: '16px', background: opt.badgeColor }"
                          ></span>
                          <span class="fs-12 text-truncate">{{ opt.name }}</span>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Opacity Slider -->
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-0">Tingkat Kepekatan Overlay (Opacity)</label>
                    <span class="badge bg-primary fs-11 fw-bold">{{ form.admin_login_bg_overlay_opacity }}%</span>
                  </div>
                  <input 
                    type="range" 
                    min="0" 
                    max="100" 
                    step="5" 
                    v-model.number="form.admin_login_bg_overlay_opacity" 
                    class="form-range"
                  />
                  <div class="d-flex justify-content-between text-muted fs-10">
                    <span>0% (Transparan / Gambar Jelas)</span>
                    <span>50% (Sedang)</span>
                    <span>100% (Solid Pekat)</span>
                  </div>
                </div>

                <!-- Blur Slider -->
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-0">Efek Lembut / Blur Latar Belakang</label>
                    <span class="badge bg-secondary fs-11 fw-bold">{{ form.admin_login_bg_blur }} px</span>
                  </div>
                  <input 
                    type="range" 
                    min="0" 
                    max="15" 
                    step="1" 
                    v-model.number="form.admin_login_bg_blur" 
                    class="form-range"
                  />
                  <div class="d-flex justify-content-between text-muted fs-10">
                    <span>0px (Tajam Asli)</span>
                    <span>3px (Lembut)</span>
                    <span>15px (Sangat Blur / Bokeh)</span>
                  </div>
                </div>

                <!-- Toggles: Wave & Particles -->
                <div class="row g-3 pt-2 border-top">
                  <div class="col-sm-6">
                    <div class="form-check form-switch form-switch-md d-flex align-items-center gap-2 mb-0">
                      <input 
                        type="checkbox" 
                        id="showWaveSwitch" 
                        v-model="form.admin_login_show_wave" 
                        class="form-check-input mt-0 cursor-pointer"
                      />
                      <label class="form-check-label fs-12 text-dark-emphasis fw-medium cursor-pointer" for="showWaveSwitch">
                        Tampilkan Bentuk Gelombang Bawah (Wave Shape)
                      </label>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-check form-switch form-switch-md d-flex align-items-center gap-2 mb-0">
                      <input 
                        type="checkbox" 
                        id="particlesSwitch" 
                        v-model="form.admin_login_particles_active" 
                        class="form-check-input mt-0 cursor-pointer"
                      />
                      <label class="form-check-label fs-12 text-dark-emphasis fw-medium cursor-pointer" for="particlesSwitch">
                        Partikel Animasi Bergerak (Floating Particles)
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 4. KUSTOMISASI TEKS SAMBUTAN FORM -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-transparent border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-t-box-line text-purple fs-18"></i>
                  <h5 class="card-title text-dark-emphasis fs-14 fw-bold mb-0">Teks Judul & Subjudul Kartu Login</h5>
                </div>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Judul Sambutan</label>
                    <input 
                      type="text" 
                      v-model="form.admin_login_title" 
                      class="form-control fs-12" 
                      placeholder="Selamat Datang Kembali !"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Subjudul Keterangan</label>
                    <input 
                      type="text" 
                      v-model="form.admin_login_subtitle" 
                      class="form-control fs-12" 
                      placeholder="Masuk untuk melanjutkan ke Panel Semarak Aliyah."
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- 5. KEAMANAN & GOOGLE RECAPTCHA LOGIN ADMIN -->
            <div class="card border-0 shadow-sm rounded-3">
              <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-shield-check-line text-success fs-18"></i>
                  <h5 class="card-title text-dark-emphasis fs-14 fw-bold mb-0">Keamanan & Google reCAPTCHA</h5>
                </div>
                <div class="form-check form-switch mb-0">
                  <input 
                    v-model="form.admin_login_recaptcha_enabled" 
                    class="form-check-input cursor-pointer" 
                    type="checkbox" 
                    id="toggle-recaptcha" 
                  />
                  <label class="form-check-label fs-12 fw-semibold text-dark-emphasis cursor-pointer" for="toggle-recaptcha">
                    {{ form.admin_login_recaptcha_enabled ? 'Aktif' : 'Nonaktif' }}
                  </label>
                </div>
              </div>
              <div class="card-body p-3">
                <p class="text-muted fs-12 mb-3">
                  Cegah serangan brute-force dan bot otomatis pada formulir login panitia/admin dengan verifikasi Google reCAPTCHA.
                </p>

                <div v-if="form.admin_login_recaptcha_enabled">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label fs-12 fw-semibold text-dark-emphasis">Tipe / Versi reCAPTCHA</label>
                      <CustomSelect
                        v-model="form.recaptcha_version"
                        :options="recaptchaVersionOptions"
                        placeholder="Pilih versi reCAPTCHA..."
                        size="md"
                        prefix-icon="ri-shield-line"
                      />
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-info w-100 py-2 d-flex align-items-center justify-content-center gap-1.5"
                        @click="useGoogleTestKeys"
                        title="Terapkan kunci testing resmi Google untuk uji coba di localhost"
                      >
                        <i class="ri-magic-line"></i>
                        <span>Gunakan Kunci Uji Coba Google (Testing)</span>
                      </button>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                        Site Key (Kunci Situs) <span class="text-danger">*</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="form.recaptcha_site_key" 
                        class="form-control fs-12 font-monospace" 
                        placeholder="Contoh: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
                        required
                      />
                      <small class="text-muted fs-11 mt-1 d-block">Kunci publik yang disematkan pada halaman web frontend.</small>
                    </div>

                    <div class="col-md-6">
                      <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                        Secret Key (Kunci Rahasia) <span class="text-danger">*</span>
                      </label>
                      <div class="position-relative">
                        <input 
                          :type="showSecretKey ? 'text' : 'password'" 
                          v-model="form.recaptcha_secret_key" 
                          class="form-control fs-12 pe-5 font-monospace" 
                          placeholder="Contoh: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"
                          required
                        />
                        <button 
                          type="button" 
                          class="btn position-absolute top-50 end-0 translate-middle-y border-0 text-muted p-2"
                          @click="showSecretKey = !showSecretKey"
                          tabindex="-1"
                        >
                          <i :class="showSecretKey ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                        </button>
                      </div>
                      <small class="text-muted fs-11 mt-1 d-block">Kunci privat backend untuk validasi respons ke server Google.</small>
                    </div>

                    <div class="col-12">
                      <div class="form-check form-switch mb-1">
                        <input 
                          v-model="form.admin_login_recaptcha_bypass_local" 
                          class="form-check-input cursor-pointer" 
                          type="checkbox" 
                          id="bypass-local-check" 
                        />
                        <label class="form-check-label fs-12 fw-semibold text-dark-emphasis cursor-pointer" for="bypass-local-check">
                          Bypass Otomatis Saat Akses Lokal / Offline (Localhost Dev)
                        </label>
                      </div>
                      <small class="text-muted fs-11">
                        Jika aktif, pengujian di lingkungan lokal/offline tidak akan terblokir saat internet tidak tersedia atau kunci rahasia belum diverifikasi server Google.
                      </small>
                    </div>
                  </div>
                </div>
                <div v-else class="p-3 bg-light rounded-3 border text-center fs-12 text-muted">
                  <i class="ri-shield-line me-1"></i> Google reCAPTCHA saat ini berstatus <strong>Nonaktif</strong>. Aktifkan sakelar di atas untuk mengaktifkan proteksi.
                </div>
              </div>
            </div>

            <!-- SUBMIT BUTTONS -->
            <div class="d-flex align-items-center justify-content-end gap-2 pb-4">
              <button 
                type="button" 
                class="btn btn-outline-danger px-4 fs-13 fw-semibold"
                @click="resetToDefault"
                :disabled="isResetting || isSaving"
              >
                <i class="ri-refresh-line me-1"></i> Reset ke Default Velzon
              </button>
              <button 
                type="submit" 
                class="btn btn-primary px-4 fs-13 fw-bold d-flex align-items-center gap-1.5"
                :disabled="isSaving || isResetting"
              >
                <i v-if="isSaving" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-line"></i>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Kustomisasi' }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- RIGHT: LIVE INTERACTIVE PREVIEW MOCKUP -->
        <div class="col-lg-5">
          <div class="sticky-top" style="top: 85px; z-index: 10;">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
              <div class="card-header bg-transparent border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <span class="rounded-circle bg-danger" style="width: 10px; height: 10px;"></span>
                  <span class="rounded-circle bg-warning" style="width: 10px; height: 10px;"></span>
                  <span class="rounded-circle bg-success" style="width: 10px; height: 10px;"></span>
                  <span class="fs-12 fw-bold text-dark-emphasis ms-2">Live Preview Halaman Login</span>
                </div>
                <span class="badge bg-success-subtle text-success border border-success border-opacity-30 fs-10 px-2 py-0.5 rounded-pill">
                  Real-time
                </span>
              </div>

              <!-- Miniature Browser Screen Mockup -->
              <div class="mockup-browser-container position-relative overflow-hidden" style="height: 520px; background-color: #f3f3f9;">
                
                <!-- Background Hero Area with Live Effects -->
                <div 
                  class="mockup-hero-bg position-absolute top-0 start-0 end-0 overflow-hidden"
                  :style="{
                    height: '240px',
                    backgroundImage: `url(${effectiveBgImage})`,
                    backgroundPosition: 'center',
                    backgroundSize: 'cover',
                    backgroundRepeat: 'no-repeat',
                    filter: form.admin_login_bg_blur > 0 ? `blur(${form.admin_login_bg_blur}px)` : 'none',
                    transform: form.admin_login_bg_blur > 0 ? 'scale(1.05)' : 'none',
                    transition: 'all 0.3s ease'
                  }"
                >
                </div>

                <!-- Overlay Layer -->
                <div 
                  class="mockup-overlay-layer position-absolute top-0 start-0 end-0"
                  :style="{
                    height: '240px',
                    ...previewOverlayStyle,
                    transition: 'all 0.3s ease'
                  }"
                >
                  <!-- Particle hints -->
                  <div v-if="form.admin_login_particles_active" class="mockup-particles-hint">
                    <span class="dot d1"></span>
                    <span class="dot d2"></span>
                    <span class="dot d3"></span>
                    <span class="dot d4"></span>
                    <span class="dot d5"></span>
                  </div>
                </div>

                <!-- Wave Shape -->
                <div 
                  v-if="form.admin_login_show_wave" 
                  class="mockup-wave-shape position-absolute start-0 end-0"
                  style="top: 195px; z-index: 2; pointer-events: none;"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" style="width: 100%; height: auto; fill: #f3f3f9; display: block;">
                    <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                  </svg>
                </div>

                <!-- Header Brand on Preview -->
                <div class="position-absolute top-0 start-0 end-0 pt-3 text-center" style="z-index: 3;">
                  <div class="d-inline-flex align-items-center gap-1.5 bg-dark bg-opacity-60 px-3 py-1 rounded-pill backdrop-blur">
                    <img src="/assets/img/logo.png" alt="Logo" style="height: 20px; width: auto;" />
                    <span class="fs-11 fw-bold text-white letter-spacing-1">SEMARAK ALIYAH</span>
                  </div>
                  <div class="text-white text-opacity-80 fs-10 mt-1">Panel Admin Panitia</div>
                </div>

                <!-- Floating Login Card Mockup -->
                <div class="position-absolute start-0 end-0 px-4" style="top: 120px; z-index: 4;">
                  <div class="card bg-white shadow-lg border-0 rounded-3 p-3 text-dark mx-auto" style="max-width: 320px;">
                    <div class="text-center mb-2.5">
                      <div class="fs-14 fw-bold text-dark">{{ form.admin_login_title || 'Selamat Datang Kembali !' }}</div>
                      <div class="fs-10 text-muted">{{ form.admin_login_subtitle || 'Masuk untuk melanjutkan ke Panel Semarak Aliyah.' }}</div>
                    </div>

                    <div class="mb-2">
                      <div class="fs-10 fw-semibold text-muted mb-1">Username / Email</div>
                      <div class="bg-light border rounded px-2 py-1 fs-10 text-muted">admin@maswahas.sch.id</div>
                    </div>

                    <div class="mb-2.5">
                      <div class="fs-10 fw-semibold text-muted mb-1">Password</div>
                      <div class="bg-light border rounded px-2 py-1 fs-10 text-muted d-flex justify-content-between align-items-center">
                        <span>••••••••••••</span>
                        <i class="ri-eye-line text-muted"></i>
                      </div>
                    </div>

                    <div v-if="form.admin_login_recaptcha_enabled" class="mb-2 p-1.5 border rounded bg-light d-flex align-items-center justify-content-between fs-9">
                      <div class="d-flex align-items-center gap-1.5">
                        <div class="border rounded bg-white d-flex align-items-center justify-content-center" style="width: 14px; height: 14px;">
                          <i class="ri-check-line text-success" style="font-size: 10px;"></i>
                        </div>
                        <span class="text-dark fw-medium">Saya bukan robot</span>
                      </div>
                      <div class="text-end" style="line-height: 1;">
                        <i class="ri-shield-check-fill text-primary" style="font-size: 12px;"></i>
                        <span class="d-block text-muted" style="font-size: 7px;">reCAPTCHA</span>
                      </div>
                    </div>

                    <button type="button" class="btn btn-success btn-sm w-100 fs-11 fw-semibold py-1 mb-2">
                      Sign In
                    </button>

                    <div class="text-center fs-9 text-muted">
                      &copy; 2026 Semarak Aliyah XIV
                    </div>
                  </div>
                </div>

              </div>

              <!-- Footer Helper -->
              <div class="card-footer bg-transparent border-top py-2 px-3 text-center">
                <span class="text-muted fs-11">
                  <i class="ri-information-line me-1 text-warning"></i>
                  Perubahan akan langsung aktif di halaman <code>/login</code> setelah disimpan.
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.preset-card {
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.preset-card:hover {
  transform: translateY(-2px);
  border-color: rgba(64, 81, 137, 0.6) !important;
}
.active-preset {
  box-shadow: 0 4px 12px rgba(64, 81, 137, 0.3);
}

.mockup-particles-hint .dot {
  position: absolute;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 50%;
  animation: floatDots 3s infinite ease-in-out;
}
.d1 { width: 4px; height: 4px; top: 30px; left: 40px; animation-delay: 0s; }
.d2 { width: 6px; height: 6px; top: 80px; right: 60px; animation-delay: 1s; }
.d3 { width: 3px; height: 3px; top: 110px; left: 80px; animation-delay: 1.5s; }
.d4 { width: 5px; height: 5px; top: 50px; right: 120px; animation-delay: 0.5s; }
.d5 { width: 4px; height: 4px; top: 140px; right: 40px; animation-delay: 2s; }

@keyframes floatDots {
  0%, 100% { transform: translateY(0); opacity: 0.3; }
  50% { transform: translateY(-8px); opacity: 0.9; }
}

.backdrop-blur {
  backdrop-filter: blur(4px);
}
</style>
