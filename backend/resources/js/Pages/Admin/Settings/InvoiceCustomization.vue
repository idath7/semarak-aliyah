<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import VelzonConfirmModal from '@/Components/VelzonConfirmModal.vue';
import CustomSelect from '@/Components/CustomSelect.vue';

const props = defineProps({
  config: {
    type: Object,
    default: () => ({})
  },
  sampleId: {
    type: [Number, String],
    default: 1
  }
});

const { success, error } = useToast();

const isSaving = ref(false);
const isResetting = ref(false);
const isResetModalOpen = ref(false);

// Local file preview URLs
const previewLogoLeft = ref(props.config.logo_left || '/assets/img/logo.png');
const previewLogoRight = ref(props.config.logo_right || '/assets/img/logo.png');
const previewStampImage = ref(props.config.stamp_image || '');
const previewSignatureImage = ref(props.config.signature_image || '');

const form = useForm({
  theme: props.config.theme || 'formal',

  logo_left: props.config.logo_left || '/assets/img/logo.png',
  logo_right: props.config.logo_right || '/assets/img/logo.png',
  show_logo_right: props.config.show_logo_right !== undefined ? Boolean(props.config.show_logo_right) : true,
  logo_size: props.config.logo_size || 68,
  logo_left_file: null,
  logo_right_file: null,

  kop_foundation: props.config.kop_foundation ?? 'YAYASAN PONDOK PESANTREN WAHID HASYIM',
  kop_committee: props.config.kop_committee ?? 'PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026',
  kop_school: props.config.kop_school ?? 'MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG',
  kop_accreditation: props.config.kop_accreditation ?? 'STATUS TERAKREDITASI "A" • NSM: 131235090014 • NPSN: 69941846',
  kop_address: props.config.kop_address ?? 'Jl. Balung - Tutul No. 09 Balung Kidul, Kec. Balung, Kab. Jember, Jawa Timur 68161',
  kop_contact: props.config.kop_contact ?? 'Website: semarak.ma-wh.sch.id • Email: semarak@ma-wh.sch.id • Hotline WA: 0838 5007 0767',

  stamp_mode: props.config.stamp_mode || 'digital',
  stamp_top_text: props.config.stamp_top_text || 'SEMARAK ALIYAH XIV',
  stamp_bottom_text: props.config.stamp_bottom_text || 'MA WAHID HASYIM',
  stamp_center_text: props.config.stamp_center_text || '★',
  stamp_color: props.config.stamp_color || '#2563eb',
  stamp_rotation: props.config.stamp_rotation !== undefined ? Number(props.config.stamp_rotation) : -8,
  stamp_image_file: null,
  remove_stamp_image: false,

  sig_city: props.config.sig_city || 'Balung - Jember',
  treasurer_name: props.config.treasurer_name || 'M. Irfan Zidni, S.Pd.',
  treasurer_role: props.config.treasurer_role || 'Bendahara Pelaksana',
  treasurer_subrole: props.config.treasurer_subrole || 'Semarak Aliyah XIV Tahun 2026',
  signature_image_file: null,
  remove_signature_image: false,

  footer_note: props.config.footer_note || 'Dokumen ini diterbitkan secara sah dan otomatis oleh Sistem Informasi Semarak Aliyah XIV MA Unggulan Wahid Hasyim Balung.',
});

// Theme options for CustomSelect (Rule 1)
const themeOptions = [
  { value: 'formal', label: 'Tema Formal (Klasik KOP Surat Kedinasan & Garis Ganda)' },
  { value: 'professional', label: 'Tema Profesional (Modern Corporate & Fintech Sleek)' },
];

// Interactive Preview Theme state
const previewTheme = ref(props.config.theme || 'formal');
watch(() => form.theme, (newVal) => {
  if (newVal) previewTheme.value = newVal;
});

// Color Presets for Vector Digital Stamp
const stampColorPresets = [
  { name: 'Biru Resmi (Default)', hex: '#2563eb' },
  { name: 'Biru Tua Formal', hex: '#1d4ed8' },
  { name: 'Biru Navy', hex: '#1e3a8a' },
  { name: 'Hijau Emerald', hex: '#059669' },
  { name: 'Merah Resmi', hex: '#dc2626' },
  { name: 'Ungu Elegan', hex: '#7c3aed' },
];

// File input handlers
function onLogoLeftSelect(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    error('Ukuran logo kiri maksimal 2MB.');
    return;
  }
  form.logo_left_file = file;
  previewLogoLeft.value = URL.createObjectURL(file);
}

function onLogoRightSelect(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    error('Ukuran logo kanan maksimal 2MB.');
    return;
  }
  form.logo_right_file = file;
  previewLogoRight.value = URL.createObjectURL(file);
}

function onStampImageSelect(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    error('Ukuran gambar stempel maksimal 2MB.');
    return;
  }
  form.stamp_image_file = file;
  form.remove_stamp_image = false;
  previewStampImage.value = URL.createObjectURL(file);
}

function removeStampImage() {
  form.stamp_image_file = null;
  form.remove_stamp_image = true;
  previewStampImage.value = '';
}

function onSignatureImageSelect(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    error('Ukuran tanda tangan maksimal 2MB.');
    return;
  }
  form.signature_image_file = file;
  form.remove_signature_image = false;
  previewSignatureImage.value = URL.createObjectURL(file);
}

function removeSignatureImage() {
  form.signature_image_file = null;
  form.remove_signature_image = true;
  previewSignatureImage.value = '';
}

// Active Tab in configuration card
const activeConfigTab = ref('theme');

// Submit Form
function submitCustomization() {
  isSaving.value = true;
  form.post('/admin/pengaturan/kop-invoice', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isSaving.value = false;
      success('Konfigurasi Tema, KOP Surat, Logo, dan Stempel Invoice resmi berhasil disimpan!', 'Tersimpan');
    },
    onError: (errs) => {
      isSaving.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan penyesuaian invoice.', 'Peringatan');
    }
  });
}

// Reset Configuration
function confirmReset() {
  isResetModalOpen.value = true;
}

function executeReset() {
  isResetting.value = true;
  router.post('/admin/pengaturan/kop-invoice/reset', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isResetting.value = false;
      isResetModalOpen.value = false;
      success('Konfigurasi KOP Surat, Logo, Tema, dan Stempel berhasil dikembalikan ke standar awal!', 'Direset');
      window.location.reload();
    },
    onError: () => {
      isResetting.value = false;
      error('Gagal mengembalikan konfigurasi ke standar awal.');
    }
  });
}

// Today formatted in Indonesian dd/mm/yyyy (Rule 4)
const todayFormatted = computed(() => {
  const d = new Date();
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
});
</script>

<template>
  <Head title="Kustomisasi Tema, KOP & Stempel Invoice | Pengaturan Sistem" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Page Header & Breadcrumb -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-1 fw-bold text-dark-emphasis d-flex align-items-center gap-2">
                <i class="ri-file-paper-2-line text-primary fs-22"></i>
                Kustomisasi Tema &amp; KOP Invoice Resmi
              </h4>
              <p class="text-muted fs-12 mb-0">
                Pilih tema desain (Formal atau Profesional), atur identitas lembaga, logo resmi ganda, stempel panitia, dan tanda tangan bendahara pada cetakan Nota &amp; Kwitansi.
              </p>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
              <a 
                :href="`/admin/invoice/${sampleId}?theme=${previewTheme}`" 
                target="_blank" 
                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 shadow-sm px-3"
                title="Buka contoh cetak invoice di tab baru"
              >
                <i class="ri-printer-line"></i>
                <span>Lihat Cetak Invoice</span>
              </a>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 shadow-sm px-3"
                @click="confirmReset"
                :disabled="isResetting || isSaving"
              >
                <i class="ri-refresh-line"></i>
                <span>Reset Standar Awal</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm px-3 fw-semibold"
                @click="submitCustomization"
                :disabled="isSaving || isResetting"
              >
                <i v-if="isSaving" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-line"></i>
                <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
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
                    <span>SEO &amp; Open Graph</span>
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
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-image-edit-line text-info"></i>
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
                    href="/admin/pengaturan/backup"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-save-3-line text-warning"></i>
                    <span>Cadangan</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/kop-invoice"
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill fw-semibold fs-13"
                  >
                    <i class="ri-file-paper-2-line"></i>
                    <span>KOP &amp; Stempel Invoice</span>
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Layout: Form Controls (Left) + Real-time WYSIWYG Preview (Right) -->
      <div class="row g-4">
        <!-- LEFT COLUMN: SETTINGS TABS & INPUTS -->
        <div class="col-xl-6 col-lg-6">
          <div class="card border-0 shadow-sm rounded-4">
            <!-- Tabs Navigation -->
            <div class="card-header bg-transparent border-bottom p-3">
              <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0 d-flex gap-2">
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-2 px-3 fw-semibold fs-13 d-flex align-items-center gap-1.5"
                    :class="{ 'active text-primary': activeConfigTab === 'theme' }"
                    @click="activeConfigTab = 'theme'"
                  >
                    <i class="ri-palette-line"></i> Tema Desain
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-2 px-3 fw-semibold fs-13 d-flex align-items-center gap-1.5"
                    :class="{ 'active text-primary': activeConfigTab === 'kop' }"
                    @click="activeConfigTab = 'kop'"
                  >
                    <i class="ri-government-line"></i> KOP &amp; Logo
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-2 px-3 fw-semibold fs-13 d-flex align-items-center gap-1.5"
                    :class="{ 'active text-primary': activeConfigTab === 'stamp' }"
                    @click="activeConfigTab = 'stamp'"
                  >
                    <i class="ri-shield-check-line"></i> Stempel Panitia
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-2 px-3 fw-semibold fs-13 d-flex align-items-center gap-1.5"
                    :class="{ 'active text-primary': activeConfigTab === 'signature' }"
                    @click="activeConfigTab = 'signature'"
                  >
                    <i class="ri-quill-pen-line"></i> Tanda Tangan
                  </button>
                </li>
              </ul>
            </div>

            <div class="card-body p-3 p-md-4">
              <!-- TAB 0: TEMA DESAIN UI INVOICE -->
              <div v-show="activeConfigTab === 'theme'">
                <div class="mb-3">
                  <h6 class="fw-bold mb-1 text-dark-emphasis d-flex align-items-center gap-2">
                    <i class="ri-palette-line text-primary fs-18"></i>
                    Pilih Tema Tata Letak UI Dokumen
                  </h6>
                  <p class="text-muted fs-12 mb-3">
                    Pilih gaya visual standar yang digunakan saat mencetak Nota Tagihan dan Kwitansi Pembayaran Resmi.
                  </p>

                  <!-- Visual Selection Cards -->
                  <div class="row g-3 mb-4">
                    <!-- Tema Formal -->
                    <div class="col-sm-6">
                      <div 
                        class="p-3 rounded-3 border cursor-pointer transition-all position-relative h-100"
                        :class="form.theme === 'formal' ? 'border-primary bg-primary-subtle shadow-sm' : 'border-secondary-subtle bg-body hover:border-primary'"
                        @click="form.theme = 'formal'"
                      >
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="badge bg-primary text-white px-2 py-1 fs-11">
                            <i class="ri-government-line me-1"></i> Standar Dinas
                          </span>
                          <i v-if="form.theme === 'formal'" class="ri-checkbox-circle-fill text-primary fs-18"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark-emphasis">Tema Formal</h6>
                        <p class="text-muted fs-11 mb-2">
                          Format resmi instansi madrasah/sekolah. Dilengkapi KOP lembaga terpusat, garis ganda resmi, dan tabel berbingkai tradisional.
                        </p>
                        <div class="p-2 rounded-2 bg-body border text-center fs-11 text-muted">
                          <i class="ri-file-text-line me-1 text-primary"></i> Cocok untuk Arsip Surat Lembaga
                        </div>
                      </div>
                    </div>

                    <!-- Tema Profesional -->
                    <div class="col-sm-6">
                      <div 
                        class="p-3 rounded-3 border cursor-pointer transition-all position-relative h-100"
                        :class="form.theme === 'professional' ? 'border-primary bg-primary-subtle shadow-sm' : 'border-secondary-subtle bg-body hover:border-primary'"
                        @click="form.theme = 'professional'"
                      >
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span class="badge bg-success text-white px-2 py-1 fs-11">
                            <i class="ri-briefcase-4-line me-1"></i> Modern Sleek
                          </span>
                          <i v-if="form.theme === 'professional'" class="ri-checkbox-circle-fill text-primary fs-18"></i>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark-emphasis">Tema Profesional</h6>
                        <p class="text-muted fs-11 mb-2">
                          Format modern corporate berstandar fintech. Dilengkapi aksen garis warna, kartu data modular, tabel clean tanpa garis vertikal, dan ringkasan pembayaran tegas.
                        </p>
                        <div class="p-2 rounded-2 bg-body border text-center fs-11 text-muted">
                          <i class="ri-sparkling-line me-1 text-warning"></i> Tampilan Kontemporer &amp; Elegan
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Dropdown Pengatur Tema Sesuai Rule 1 (CustomSelect) -->
                  <div class="p-3 rounded-3 border bg-body-tertiary">
                    <label class="form-label fw-semibold fs-12 mb-1.5 text-dark-emphasis">
                      Opsi Pilihan Tema Utama:
                    </label>
                    <CustomSelect 
                      v-model="form.theme" 
                      :options="themeOptions"
                      prefix-icon="ri-layout-grid-line"
                      size="md"
                    />
                    <small class="text-muted fs-11 mt-1.5 d-block">
                      Pengguna di halaman cetak tetap dapat mengganti tema secara instan melalui tombol switcher di toolbar atas.
                    </small>
                  </div>
                </div>
              </div>

              <!-- TAB 1: KOP SURAT & LOGO RESMI -->
              <div v-show="activeConfigTab === 'kop'">
                <!-- Logo Configuration Section -->
                <div class="mb-4 p-3 rounded-3 border bg-body-tertiary">
                  <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-dark-emphasis">
                    <i class="ri-image-2-line text-primary fs-16"></i>
                    Logo Resmi Dokumen (Kiri &amp; Kanan)
                  </h6>

                  <div class="row g-3">
                    <!-- Logo Kiri -->
                    <div class="col-sm-6">
                      <div class="p-3 bg-body rounded-3 border text-center">
                        <label class="form-label fw-semibold fs-12 mb-2 d-block text-dark-emphasis">Logo Kiri (Madrasah / Yayasan)</label>
                        <div class="d-flex justify-content-center align-items-center mb-2" style="height: 80px;">
                          <img :src="previewLogoLeft" alt="Preview Kiri" style="max-height: 72px; max-width: 100%; object-fit: contain;" />
                        </div>
                        <label class="btn btn-sm btn-outline-primary w-100 mb-1 cursor-pointer">
                          <i class="ri-upload-2-line me-1"></i> Unggah Logo Kiri
                          <input type="file" accept="image/*" class="d-none" @change="onLogoLeftSelect" />
                        </label>
                        <small class="text-muted fs-11 d-block">Maksimal 2MB (PNG / JPG / SVG)</small>
                      </div>
                    </div>

                    <!-- Logo Kanan -->
                    <div class="col-sm-6">
                      <div class="p-3 bg-body rounded-3 border text-center">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <label class="form-label fw-semibold fs-12 mb-0 text-dark-emphasis">Logo Kanan (Event / Panitia)</label>
                          <div class="form-check form-switch mb-0">
                            <input class="form-check-input cursor-pointer" type="checkbox" id="showLogoRightToggle" v-model="form.show_logo_right" />
                          </div>
                        </div>

                        <div class="d-flex justify-content-center align-items-center mb-2" style="height: 80px;">
                          <img v-if="form.show_logo_right" :src="previewLogoRight" alt="Preview Kanan" style="max-height: 72px; max-width: 100%; object-fit: contain;" />
                          <span v-else class="badge bg-secondary-subtle text-secondary px-2 py-1 fs-11">Dinonaktifkan</span>
                        </div>

                        <label class="btn btn-sm btn-outline-primary w-100 mb-1 cursor-pointer" :class="{ 'disabled opacity-50': !form.show_logo_right }">
                          <i class="ri-upload-2-line me-1"></i> Unggah Logo Kanan
                          <input type="file" accept="image/*" class="d-none" :disabled="!form.show_logo_right" @change="onLogoRightSelect" />
                        </label>
                        <small class="text-muted fs-11 d-block">Maksimal 2MB (PNG / JPG / SVG)</small>
                      </div>
                    </div>
                  </div>

                  <!-- Slider Ukuran Logo -->
                  <div class="mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="form-label fs-12 fw-semibold mb-0 text-dark-emphasis">Tinggi / Ukuran Logo KOP</label>
                      <span class="badge bg-primary-subtle text-primary fw-bold font-monospace">{{ form.logo_size }} px</span>
                    </div>
                    <input 
                      type="range" 
                      class="form-range" 
                      min="48" 
                      max="110" 
                      step="2" 
                      v-model.number="form.logo_size" 
                    />
                    <div class="d-flex justify-content-between text-muted fs-11">
                      <span>48 px (Kompak)</span>
                      <span>68 px (Standar)</span>
                      <span>110 px (Besar)</span>
                    </div>
                  </div>
                </div>

                <!-- Teks KOP Surat -->
                <div class="alert alert-info py-2 px-3 fs-12 mb-3 border-0 bg-info-subtle text-info-emphasis rounded-3 d-flex align-items-center gap-2">
                  <i class="ri-information-line fs-16"></i>
                  <span>Seluruh baris teks KOP di bawah ini bersifat opsional. Jika dikosongkan, baris tersebut otomatis tidak akan ditampilkan.</span>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Baris 1: Nama Yayasan / Instansi Pembina</label>
                  <input type="text" class="form-control" v-model="form.kop_foundation" placeholder="Contoh: YAYASAN PONDOK PESANTREN WAHID HASYIM" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Baris 2: Nama Panitia Pelaksana</label>
                  <input type="text" class="form-control" v-model="form.kop_committee" placeholder="Contoh: PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Baris 3: Nama Madrasah / Sekolah (Utama)</label>
                  <input type="text" class="form-control fw-bold" v-model="form.kop_school" placeholder="Contoh: MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Baris 4: Status Akreditasi, NSM, &amp; NPSN</label>
                  <input type="text" class="form-control" v-model="form.kop_accreditation" placeholder="Contoh: STATUS TERAKREDITASI 'A' • NSM: 131235090014 • NPSN: 69941846" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Baris 5: Alamat Lembaga</label>
                  <input type="text" class="form-control" v-model="form.kop_address" placeholder="Contoh: Jl. Balung - Tutul No. 09 Balung Kidul, Kec. Balung, Jember" />
                </div>

                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Baris 6: Kontak, Website, &amp; Email</label>
                  <input type="text" class="form-control" v-model="form.kop_contact" placeholder="Contoh: Website: semarak.ma-wh.sch.id • Hotline WA: 0838 5007 0767" />
                </div>
              </div>

              <!-- TAB 2: STEMPEL RESMI PANITIA -->
              <div v-show="activeConfigTab === 'stamp'">
                <div class="mb-4 p-3 rounded-3 border bg-body-tertiary">
                  <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-dark-emphasis">
                    <i class="ri-shield-check-line text-primary fs-16"></i>
                    Mode Stempel Resmi Panitia
                  </h6>

                  <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                      <input class="form-check-input cursor-pointer" type="radio" id="stampModeDigital" value="digital" v-model="form.stamp_mode" />
                      <label class="form-check-label cursor-pointer fw-semibold text-dark-emphasis" for="stampModeDigital">
                        Stempel Vektor Digital (Otomatis &amp; Rapi)
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input cursor-pointer" type="radio" id="stampModeImage" value="image" v-model="form.stamp_mode" />
                      <label class="form-check-label cursor-pointer fw-semibold text-dark-emphasis" for="stampModeImage">
                        Unggah Gambar Cap Basah (PNG Transparan)
                      </label>
                    </div>
                  </div>

                  <!-- MODE 1: DIGITAL VECTOR STAMP -->
                  <div v-if="form.stamp_mode === 'digital'" class="p-3 bg-body rounded-3 border">
                    <div class="row g-3">
                      <div class="col-sm-6">
                        <label class="form-label fw-semibold fs-12 text-dark-emphasis">Teks Lingkar Atas</label>
                        <input type="text" class="form-control" v-model="form.stamp_top_text" placeholder="SEMARAK ALIYAH XIV" />
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label fw-semibold fs-12 text-dark-emphasis">Teks Lingkar Bawah</label>
                        <input type="text" class="form-control" v-model="form.stamp_bottom_text" placeholder="MA WAHID HASYIM" />
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label fw-semibold fs-12 text-dark-emphasis">Simbol / Ikon Tengah</label>
                        <input type="text" class="form-control" v-model="form.stamp_center_text" placeholder="★" />
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label fw-semibold fs-12 text-dark-emphasis">Warna Tinta Stempel</label>
                        <div class="d-flex align-items-center gap-2">
                          <input type="color" class="form-control form-control-color p-1" v-model="form.stamp_color" style="width: 44px; height: 38px;" />
                          <input type="text" class="form-control font-monospace" v-model="form.stamp_color" style="max-width: 120px;" />
                        </div>
                      </div>
                    </div>

                    <!-- Preset Warna -->
                    <div class="mt-3 pt-3 border-top">
                      <label class="form-label fw-semibold fs-11 text-dark-emphasis mb-2 d-block">Pilihan Preset Warna Resmi:</label>
                      <div class="d-flex flex-wrap gap-2">
                        <button 
                          v-for="color in stampColorPresets" 
                          :key="color.hex"
                          type="button" 
                          class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5 py-1 px-2.5 fs-11"
                          :class="{ 'border-primary bg-primary-subtle text-primary fw-bold': form.stamp_color === color.hex }"
                          @click="form.stamp_color = color.hex"
                        >
                          <span class="rounded-circle d-inline-block" :style="{ width: '12px', height: '12px', backgroundColor: color.hex }"></span>
                          <span>{{ color.name }}</span>
                        </button>
                      </div>
                    </div>

                    <!-- Sudut Kemiringan / Rotasi Stempel -->
                    <div class="mt-3 pt-3 border-top">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fs-12 fw-semibold mb-0 text-dark-emphasis">Sudut Kemiringan Stempel (Rotasi Alami)</label>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ form.stamp_rotation }}°</span>
                      </div>
                      <input type="range" class="form-range" min="-25" max="25" step="1" v-model.number="form.stamp_rotation" />
                    </div>
                  </div>

                  <!-- MODE 2: UPLOAD IMAGE STAMP -->
                  <div v-else class="p-3 bg-body rounded-3 border">
                    <label class="form-label fw-semibold fs-12 mb-2 d-block text-dark-emphasis">Unggah Berkas Stempel Asli (Format PNG Transparan)</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                      <div v-if="previewStampImage" class="p-2 border rounded-3 bg-body-tertiary text-center" style="width: 90px; height: 90px;">
                        <img :src="previewStampImage" alt="Stempel" style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                      </div>
                      <div v-else class="p-2 border rounded-3 bg-body-tertiary d-flex align-items-center justify-content-center text-muted" style="width: 90px; height: 90px; font-size: 11px;">
                        Belum Ada
                      </div>
                      <div>
                        <label class="btn btn-sm btn-outline-primary mb-1 cursor-pointer">
                          <i class="ri-upload-2-line me-1"></i> Pilih Berkas PNG Stempel
                          <input type="file" accept="image/png,image/webp" class="d-none" @change="onStampImageSelect" />
                        </label>
                        <small class="text-muted fs-11 d-block">Gunakan PNG berlatar transparan dengan resolusi tajam (Maks. 2MB)</small>
                      </div>
                    </div>
                    <button 
                      v-if="previewStampImage" 
                      type="button" 
                      class="btn btn-sm btn-outline-danger" 
                      @click="removeStampImage"
                    >
                      <i class="ri-delete-bin-line me-1"></i> Hapus Stempel Gambar
                    </button>
                  </div>
                </div>
              </div>

              <!-- TAB 3: TANDA TANGAN & CATATAN KAKI -->
              <div v-show="activeConfigTab === 'signature'">
                <div class="row g-3 mb-3">
                  <div class="col-sm-6">
                    <label class="form-label fw-semibold fs-12 text-dark-emphasis">Kota Penerbitan</label>
                    <input type="text" class="form-control" v-model="form.sig_city" placeholder="Balung - Jember" />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label fw-semibold fs-12 text-dark-emphasis">Nama Lengkap Penandatangan (Bendahara)</label>
                    <input type="text" class="form-control" v-model="form.treasurer_name" placeholder="M. Irfan Zidni, S.Pd." />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label fw-semibold fs-12 text-dark-emphasis">Jabatan Resmi</label>
                    <input type="text" class="form-control" v-model="form.treasurer_role" placeholder="Bendahara Pelaksana" />
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label fw-semibold fs-12 text-dark-emphasis">Sub-Jabatan / Lembaga Pelaksana</label>
                    <input type="text" class="form-control" v-model="form.treasurer_subrole" placeholder="Semarak Aliyah XIV Tahun 2026" />
                  </div>
                </div>

                <!-- Tanda Tangan Basah Scan (Opsional) -->
                <div class="mb-4 p-3 rounded-3 border bg-body-tertiary">
                  <h6 class="fw-bold mb-2 d-flex align-items-center gap-2 text-dark-emphasis">
                    <i class="ri-quill-pen-line text-primary fs-16"></i>
                    Unggah Scan Tanda Tangan Digital (Opsional)
                  </h6>
                  <p class="text-muted fs-11 mb-3">
                    Jika diunggah, tanda tangan ini akan ditampilkan secara otomatis di atas nama bendahara pada nota invoice dan kwitansi.
                  </p>
                  <div class="d-flex align-items-center gap-3 mb-2">
                    <div v-if="previewSignatureImage" class="p-2 border rounded-3 bg-body text-center" style="width: 120px; height: 60px;">
                      <img :src="previewSignatureImage" alt="Signature" style="max-height: 100%; max-width: 100%; object-fit: contain;" />
                    </div>
                    <div v-else class="p-2 border rounded-3 bg-body d-flex align-items-center justify-content-center text-muted" style="width: 120px; height: 60px; font-size: 11px;">
                      Tanpa TTD
                    </div>
                    <div>
                      <label class="btn btn-sm btn-outline-primary mb-1 cursor-pointer">
                        <i class="ri-upload-2-line me-1"></i> Unggah Gambar Tanda Tangan
                        <input type="file" accept="image/png,image/webp" class="d-none" @change="onSignatureImageSelect" />
                      </label>
                      <small class="text-muted fs-11 d-block">Format PNG transparan (Maks. 2MB)</small>
                    </div>
                  </div>
                  <button 
                    v-if="previewSignatureImage" 
                    type="button" 
                    class="btn btn-sm btn-outline-danger" 
                    @click="removeSignatureImage"
                  >
                    <i class="ri-delete-bin-line me-1"></i> Hapus
                  </button>
                </div>

                <!-- Catatan Kaki Dokumen -->
                <div class="mb-3">
                  <label class="form-label fw-semibold fs-12 text-dark-emphasis">Teks Catatan Kaki / Footnote Resmi</label>
                  <textarea class="form-control" rows="2" v-model="form.footer_note" placeholder="Catatan legalitas di bagian paling bawah invoice"></textarea>
                </div>
              </div>
            </div>

            <!-- Card Footer: Save & Shortcut Actions -->
            <div class="card-footer bg-transparent border-top p-3 d-flex justify-content-between align-items-center">
              <span class="text-muted fs-12">
                <i class="ri-information-line me-1 text-primary"></i> Perubahan berlaku untuk semua cetakan Kwitansi &amp; Nota Invoice.
              </span>
              <button 
                type="button" 
                class="btn btn-primary d-flex align-items-center gap-1.5 px-4 fw-semibold"
                @click="submitCustomization"
                :disabled="isSaving || isResetting"
              >
                <i v-if="isSaving" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-check-line"></i>
                <span>{{ isSaving ? 'Menyimpan...' : 'Terapkan &amp; Simpan' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: REAL-TIME WYSIWYG PREVIEW SHEET -->
        <div class="col-xl-6 col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 80px; z-index: 10;">
            <div class="card-header bg-transparent border-bottom p-3 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-2 bg-success text-white d-flex align-items-center justify-content-center shadow-sm">
                  <i class="ri-eye-line fs-14"></i>
                </div>
                <div>
                  <h6 class="mb-0 fw-bold fs-13 text-dark-emphasis">Pratinjau Langsung (WYSIWYG)</h6>
                  <small class="text-muted fs-11">
                    Pratinjau: <strong>{{ previewTheme === 'formal' ? 'Tema Formal (Klasik)' : 'Tema Profesional (Modern)' }}</strong>
                  </small>
                </div>
              </div>

              <!-- Preview Theme Toggle Switcher -->
              <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group">
                  <button 
                    type="button" 
                    class="btn btn-sm px-2.5 py-1 fs-11 fw-semibold"
                    :class="previewTheme === 'formal' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="previewTheme = 'formal'"
                  >
                    <i class="ri-government-line me-1"></i> Formal
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm px-2.5 py-1 fs-11 fw-semibold"
                    :class="previewTheme === 'professional' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="previewTheme = 'professional'"
                  >
                    <i class="ri-briefcase-4-line me-1"></i> Profesional
                  </button>
                </div>
              </div>
            </div>

            <!-- Paper Container (A4 Mini Preview with Realistic Shadows) -->
            <div class="card-body p-3 d-flex justify-content-center overflow-auto" style="max-height: 85vh; background-color: #f1f5f9 !important;">
              <div 
                class="preview-paper-sheet p-4 rounded-1 shadow-lg position-relative d-flex flex-column justify-content-between"
                style="width: 100%; max-width: 540px; min-height: 680px; font-family: 'Poppins', sans-serif; font-size: 8pt; background-color: #ffffff !important; color: #0f172a !important; border: 1px solid #94a3b8;"
              >
                <!-- ==========================================
                     PREVIEW 1: TEMA FORMAL
                     ========================================== -->
                <div v-if="previewTheme === 'formal'">
                  <!-- 1. KOP SURAT FORMAL -->
                  <div class="d-flex align-items-center justify-content-between gap-2 pb-1">
                    <img 
                      :src="previewLogoLeft" 
                      alt="Logo Kiri" 
                      :style="{ height: `${Math.round(form.logo_size * 0.72)}px`, width: 'auto', objectFit: 'contain' }"
                      class="flex-shrink-0"
                    />

                    <div class="text-center flex-grow-1 px-1">
                      <div v-if="form.kop_foundation?.trim()" class="fw-bold" style="font-size: 7.5pt; color: #0f172a; letter-spacing: 0.3px;">
                        {{ form.kop_foundation }}
                      </div>
                      <div v-if="form.kop_committee?.trim()" class="fw-bold" style="font-size: 7.2pt; color: #047857; letter-spacing: 0.3px;">
                        {{ form.kop_committee }}
                      </div>
                      <div v-if="form.kop_school?.trim()" class="fw-bolder" style="font-size: 9.2pt; color: #1e3a8a; letter-spacing: 0.5px; margin: 1px 0;">
                        {{ form.kop_school }}
                      </div>
                      <div v-if="form.kop_accreditation?.trim()" class="fw-semibold text-muted" style="font-size: 5.8pt;">
                        {{ form.kop_accreditation }}
                      </div>
                      <div v-if="form.kop_address?.trim()" style="font-size: 5.5pt; color: #334155; margin-top: 1px;">
                        {{ form.kop_address }}
                      </div>
                      <div v-if="form.kop_contact?.trim()" class="text-muted" style="font-size: 5.2pt;">
                        {{ form.kop_contact }}
                      </div>
                    </div>

                    <img 
                      v-if="form.show_logo_right"
                      :src="previewLogoRight" 
                      alt="Logo Kanan" 
                      :style="{ height: `${Math.round(form.logo_size * 0.72)}px`, width: 'auto', objectFit: 'contain' }"
                      class="flex-shrink-0"
                    />
                    <div v-else style="width: 40px;" class="flex-shrink-0"></div>
                  </div>

                  <!-- Double Divider Line -->
                  <div style="border-top: 2px solid #0f172a; border-bottom: 0.8px solid #0f172a; height: 3px; margin: 4px 0 10px 0;"></div>

                  <!-- Judul Dokumen & Nomor -->
                  <div class="text-center mb-2">
                    <div class="fw-bold fs-12 text-uppercase" style="letter-spacing: 0.5px; color: #0f172a;">
                      KWITANSI TANDA TERIMA PEMBAYARAN RESMI
                    </div>
                    <div class="font-monospace text-muted" style="font-size: 7pt;">
                      Nomor Kwitansi: KW/SA-XIV/2026/SA26-DEMO
                    </div>
                  </div>

                  <!-- Mock Metadata Grid -->
                  <div class="p-2 mb-2 rounded-2" style="background-color: #f8fafc; border: 1px solid #e2e8f0; font-size: 6.8pt; line-height: 1.4;">
                    <div class="d-flex justify-content-between">
                      <div>
                        <div><strong style="color: #64748b;">Telah Diterima Dari :</strong> <span class="fw-bold" style="color: #1e3a8a;">MTs Negeri 1 Jember</span></div>
                        <div><strong style="color: #64748b;">Pembina / PIC :</strong> Ahmad Fauzi, S.Pd.</div>
                      </div>
                      <div class="text-end">
                        <div><strong style="color: #64748b;">Tanggal :</strong> {{ todayFormatted }}</div>
                        <span class="badge bg-success-subtle text-success" style="font-size: 6pt; font-weight: 800;">LUNAS / TERVERIFIKASI</span>
                      </div>
                    </div>
                  </div>

                  <!-- Mini Itemization Table -->
                  <table class="w-100 mb-2" style="border-collapse: collapse; font-size: 6.8pt;">
                    <thead>
                      <tr style="background-color: #f8fafc; color: #0f172a; border-bottom: 2px solid #64748b;">
                        <th style="padding: 4px 6px; border: 1px solid #cbd5e1;">Deskripsi Layanan / Lomba</th>
                        <th style="padding: 4px 6px; border: 1px solid #cbd5e1; text-align: right; width: 90px;">Jumlah</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td style="padding: 4px 6px; border: 1px solid #cbd5e1;">Pendaftaran Olimpiade Matematika Terpadu</td>
                        <td style="padding: 4px 6px; border: 1px solid #cbd5e1; text-align: right; font-weight: 600;">Rp 60.000</td>
                      </tr>
                      <tr style="background-color: #f1f5f9; font-weight: bold;">
                        <td style="padding: 4px 6px; border: 1px solid #cbd5e1;">TOTAL PEMBAYARAN</td>
                        <td style="padding: 4px 6px; border: 1px solid #cbd5e1; text-align: right; color: #047857; font-weight: 800;">Rp 60.000</td>
                      </tr>
                    </tbody>
                  </table>

                  <!-- Terbilang Box -->
                  <div class="p-1.5 px-2 mb-2 rounded-1 d-flex gap-1" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-left: 3px solid #2563eb; font-size: 6.5pt;">
                    <span class="fw-bold text-primary">TERBILANG:</span>
                    <span class="fst-italic fw-semibold text-dark"># Enam Puluh Ribu Rupiah #</span>
                  </div>
                </div>

                <!-- ==========================================
                     PREVIEW 2: TEMA PROFESIONAL (MODERN)
                     ========================================== -->
                <div v-else>
                  <!-- Top Gradient Accent Bar -->
                  <div style="height: 4px; background: linear-gradient(90deg, #1e3a8a 0%, #2563eb 50%, #059669 100%); border-radius: 2px 2px 0 0; margin-bottom: 10px;"></div>

                  <!-- Modern Header Split -->
                  <div class="d-flex justify-content-between align-items-start pb-2 mb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                      <img 
                        :src="previewLogoLeft" 
                        alt="Logo" 
                        :style="{ height: `${Math.round(form.logo_size * 0.65)}px`, width: 'auto', objectFit: 'contain' }"
                        class="flex-shrink-0"
                      />
                      <div>
                        <div class="fw-bold text-dark" style="font-size: 8.5pt; letter-spacing: -0.2px; line-height: 1.15;">SEMARAK ALIYAH XIV</div>
                        <div class="fw-semibold text-primary" style="font-size: 7pt;">{{ form.kop_school || 'MA Unggulan Wahid Hasyim' }}</div>
                        <div class="text-muted" style="font-size: 5.5pt; line-height: 1.2;">
                          {{ form.kop_address || 'Balung Kidul, Jember' }}
                        </div>
                      </div>
                    </div>

                    <div class="text-end">
                      <div class="fw-bold text-uppercase" style="font-size: 14pt; color: #0f172a; letter-spacing: 1px; line-height: 1;">
                        KWITANSI
                      </div>
                      <div class="badge bg-light text-dark border font-monospace mt-1" style="font-size: 5.8pt;">
                        KW/SA-XIV/2026/SA26-DEMO
                      </div>
                      <div class="text-muted mt-1" style="font-size: 5.8pt;">
                        Tgl: {{ todayFormatted }}
                      </div>
                    </div>
                  </div>

                  <!-- Modular Info Cards Grid -->
                  <div class="row g-2 mb-2">
                    <div class="col-7">
                      <div class="p-2 rounded-2 bg-light border" style="font-size: 6.5pt; line-height: 1.35;">
                        <div class="text-muted fw-bold text-uppercase" style="font-size: 5.8pt;">TELAH DITERIMA DARI:</div>
                        <div class="fw-bold text-dark" style="font-size: 7.8pt;">MTs Negeri 1 Jember</div>
                        <div>PIC: <strong>Ahmad Fauzi, S.Pd.</strong></div>
                        <div>WA: <strong>0812-7777-8888</strong></div>
                      </div>
                    </div>
                    <div class="col-5">
                      <div class="p-2 rounded-2 bg-light border text-end" style="font-size: 6.5pt; line-height: 1.35;">
                        <div class="text-muted fw-bold text-uppercase" style="font-size: 5.8pt;">STATUS PEMBAYARAN:</div>
                        <div class="badge bg-success text-white my-1" style="font-size: 6.2pt;">LUNAS / VERIFIED</div>
                        <div class="text-muted">Metode: Transfer BSI</div>
                      </div>
                    </div>
                  </div>

                  <!-- Modern Sleek Table -->
                  <table class="w-100 mb-2" style="border-collapse: collapse; font-size: 6.8pt;">
                    <thead>
                      <tr style="background-color: #f1f5f9; color: #334155; border-top: 1.5px solid #cbd5e1; border-bottom: 1.5px solid #cbd5e1;">
                        <th style="padding: 4px 6px; font-weight: 700; text-align: left;">Layanan / Lomba</th>
                        <th style="padding: 4px 6px; font-weight: 700; text-align: center; width: 70px;">Skema</th>
                        <th style="padding: 4px 6px; font-weight: 700; text-align: right; width: 85px;">Jumlah</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 5px 6px;">
                          <strong style="color: #0f172a;">Olimpiade Matematika Terpadu</strong>
                          <div class="text-muted" style="font-size: 5.5pt;">Delegasi: Rian Hidayat</div>
                        </td>
                        <td style="padding: 5px 6px; text-align: center;">
                          <span class="badge bg-light text-dark border">Reguler</span>
                        </td>
                        <td style="padding: 5px 6px; text-align: right; font-weight: 700;">Rp 60.000</td>
                      </tr>
                    </tbody>
                  </table>

                  <!-- Modern Calculation Card -->
                  <div class="p-2 rounded-2 bg-light border mb-2" style="font-size: 6.8pt;">
                    <div class="d-flex justify-content-between text-muted pb-1">
                      <span>Subtotal Biaya</span>
                      <span>Rp 60.000</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center p-1.5 rounded-1 border fw-bold" style="background-color: #ecfdf5; border-color: #a7f3d0 !important; color: #065f46;">
                      <span style="font-size: 6.5pt; text-transform: uppercase; color: #065f46;">Total Akhir</span>
                      <span style="color: #047857; font-size: 8.5pt;">Rp 60.000</span>
                    </div>
                  </div>

                  <!-- Modern Terbilang Quote -->
                  <div class="p-1.5 px-2 mb-2 rounded-1 bg-primary-subtle border border-primary-subtle" style="font-size: 6.2pt; color: #1e40af;">
                    <span class="fw-bold">Terbilang:</span> <em>"# Enam Puluh Ribu Rupiah #"</em>
                  </div>
                </div>

                <!-- BOTTOM PORTION: SIGNATURE & OFFICIAL STEMPEL (SHARED LIVE PREVIEW) -->
                <div>
                  <div class="d-flex justify-content-between align-items-start mt-2 px-2 position-relative">
                    <!-- Sisi Kiri: Penyetor -->
                    <div style="width: 140px; text-align: center; font-size: 6.8pt;">
                      <div class="text-muted">&nbsp;</div>
                      <div class="fw-bold mb-4">Penyetor / Pembina,</div>
                      <div class="fw-bold text-decoration-underline">Ahmad Fauzi, S.Pd.</div>
                      <div class="text-muted" style="font-size: 6pt;">MTs Negeri 1 Jember</div>
                    </div>

                    <!-- Sisi Kanan: Bendahara & Stempel Panitia -->
                    <div style="width: 170px; text-align: center; font-size: 6.8pt; position: relative;">
                      <div class="text-muted" style="font-size: 6.5pt;">{{ form.sig_city || 'Balung - Jember' }}, {{ todayFormatted }}</div>
                      <div class="fw-bold" style="font-size: 7pt; margin-bottom: 38px;">
                        {{ form.treasurer_role || 'Bendahara Pelaksana' }},<br>
                        <span class="text-muted fw-normal" style="font-size: 5.8pt;">{{ form.treasurer_subrole || 'Semarak Aliyah XIV' }}</span>
                      </div>

                      <!-- Cap Stempel Panitia (Live Rendered) -->
                      <div 
                        v-if="form.stamp_mode === 'image' && previewStampImage"
                        class="position-absolute"
                        :style="{
                          top: '12px',
                          left: '-10px',
                          transform: `rotate(${form.stamp_rotation}deg)`,
                          pointerEvents: 'none',
                          userSelect: 'none',
                          zIndex: 2,
                          opacity: 0.88
                        }"
                      >
                        <img 
                          :src="previewStampImage" 
                          alt="Stempel Panitia" 
                          style="width: 66px; height: 66px; object-fit: contain;" 
                        />
                      </div>

                      <!-- Stempel Vektor Digital -->
                      <div 
                        v-else
                        class="position-absolute d-flex flex-column align-items-center justify-content-center"
                        :style="{
                          top: '12px',
                          left: '-10px',
                          width: '64px',
                          height: '64px',
                          borderRadius: '50%',
                          border: `1.8px solid ${form.stamp_color}`,
                          color: form.stamp_color,
                          transform: `rotate(${form.stamp_rotation}deg)`,
                          pointerEvents: 'none',
                          userSelect: 'none',
                          opacity: 0.88,
                          zIndex: 2
                        }"
                      >
                        <div 
                          class="d-flex flex-column align-items-center justify-content-center text-center"
                          :style="{
                            width: '54px',
                            height: '54px',
                            borderRadius: '50%',
                            border: `0.8px dashed ${form.stamp_color}`,
                            padding: '1px'
                          }"
                        >
                          <span style="font-size: 4.2pt; font-weight: 800; text-transform: uppercase; line-height: 1;">
                            {{ form.stamp_top_text || 'SEMARAK ALIYAH XIV' }}
                          </span>
                          <span :style="{ color: form.stamp_color, fontSize: '6pt', margin: '1px 0' }">
                            {{ form.stamp_center_text || '★' }}
                          </span>
                          <span style="font-size: 3.8pt; font-weight: 800; text-transform: uppercase; line-height: 1;">
                            {{ form.stamp_bottom_text || 'MA WAHID HASYIM' }}
                          </span>
                        </div>
                      </div>

                      <!-- Signature Image Layer (If present) -->
                      <div 
                        v-if="previewSignatureImage" 
                        class="position-absolute"
                        style="top: 24px; left: 16px; pointer-events: none; z-index: 1;"
                      >
                        <img :src="previewSignatureImage" alt="Signature" style="height: 34px; max-width: 90px; object-fit: contain;" />
                      </div>

                      <div class="fw-bold text-decoration-underline" style="color: #0f172a; position: relative; z-index: 3;">
                        {{ form.treasurer_name || 'M. Irfan Zidni, S.Pd.' }}
                      </div>
                      <div class="text-muted" style="font-size: 6pt; position: relative; z-index: 3;">
                        {{ form.treasurer_role || 'Bendahara Pelaksana' }}
                      </div>
                    </div>
                  </div>

                  <!-- Catatan Kaki Dokumen -->
                  <div class="d-flex justify-content-between align-items-center pt-1.5 mt-2 border-top text-muted" style="font-size: 5.5pt;">
                    <span>{{ form.footer_note }}</span>
                    <span class="font-monospace">SA-XIV/DEMO/PREVIEW</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reset Confirmation Modal -->
      <VelzonConfirmModal
        :show="isResetModalOpen"
        title="Kembalikan ke Standar Awal?"
        message="Apakah Anda yakin ingin mengembalikan seluruh konfigurasi KOP Surat, Logo, Tema Desain, dan Stempel Invoice resmi ke standar bawaan sistem Semarak Aliyah XIV?"
        confirmText="Ya, Reset Sekarang"
        cancelText="Batal"
        variant="danger"
        @confirm="executeReset"
        @cancel="isResetModalOpen = false"
      />
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
.nav-tabs-custom .nav-link {
  border: 1px solid transparent;
  border-top-left-radius: 8px;
  border-top-right-radius: 8px;
  color: #64748b;
  background-color: transparent;
  transition: all 0.15s ease-in-out;
}
.nav-tabs-custom .nav-link:hover {
  color: #0f172a;
}
.nav-tabs-custom .nav-link.active {
  color: #2563eb !important;
  background-color: var(--bs-card-bg, #ffffff);
  border-color: #e2e8f0 #e2e8f0 transparent;
  box-shadow: 0 -2px 6px rgba(0,0,0,0.03);
}
:global(.dark) .nav-tabs-custom .nav-link:hover {
  color: #ffffff;
}
:global(.dark) .nav-tabs-custom .nav-link.active {
  background-color: #1f2937;
  border-color: #374151 #374151 transparent;
}
.preview-paper-sheet {
  background-color: #ffffff !important;
  color: #0f172a !important;
}
.preview-paper-sheet * {
  color-scheme: light;
}
.preview-paper-sheet .text-muted {
  color: #475569 !important;
}
.preview-paper-sheet th {
  background-color: #f8fafc !important;
  color: #0f172a !important;
}
.preview-paper-sheet td {
  color: #1e293b !important;
  border-color: #cbd5e1 !important;
}
</style>
