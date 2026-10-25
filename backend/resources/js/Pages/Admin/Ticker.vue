<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import KustomisasiLayout from '@/Layouts/KustomisasiLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
});

const { success, error } = useToast();

// Default Phrases
const defaultPhrases = [
  { id: 1, text: 'SEMARAK ALIYAH XIV', style: 'gradient', is_active: true },
  { id: 2, text: 'AJANG TALENTA JUARA', style: 'outline', is_active: true },
  { id: 3, text: 'MA UNGGULAN WAHID HASYIM', style: 'filled', is_active: true },
  { id: 4, text: 'SAINS • SENI • OLAHRAGA • RELIGI', style: 'outline', is_active: true },
  { id: 5, text: 'TOTAL HADIAH JUTAAN RUPIAH', style: 'gradient', is_active: true },
  { id: 6, text: 'FESTIVAL PELAJAR SE-JEMBER', style: 'outline', is_active: true },
];

// Form state
const form = ref({
  ticker_is_active: props.settings.ticker_is_active !== false,
  ticker_speed: Number(props.settings.ticker_speed) || 35,
  ticker_separator: props.settings.ticker_separator || '✦',
  ticker_pause_on_hover: props.settings.ticker_pause_on_hover !== false,
  ticker_font_size: Number(props.settings.ticker_font_size) || 48,
  ticker_font_family: props.settings.ticker_font_family || 'syne',
  ticker_stroke_width: Number(props.settings.ticker_stroke_width) || 2,
  ticker_opacity: Number(props.settings.ticker_opacity) || 100,
  ticker_outline_opacity: Number(props.settings.ticker_outline_opacity) || 80,
  ticker_items: Array.isArray(props.settings.ticker_items) && props.settings.ticker_items.length > 0
    ? JSON.parse(JSON.stringify(props.settings.ticker_items))
    : JSON.parse(JSON.stringify(defaultPhrases))
});

const isSaving = ref(false);
const previewDarkMode = ref(false);

// Font Family Options
const fontOptions = [
  { 
    id: 'syne', 
    name: 'Syne', 
    badge: 'Rekomendasi Konfer', 
    desc: 'Modern Geometric Display (Sangat estetik & gagah untuk gaya Stroke Outline)', 
    fontFamily: "'Syne', sans-serif" 
  },
  { 
    id: 'outfit', 
    name: 'Outfit', 
    badge: 'Modern Clean', 
    desc: 'Geometric Sans-Serif bersih & seimbang', 
    fontFamily: "'Outfit', sans-serif" 
  },
  { 
    id: 'oswald', 
    name: 'Oswald', 
    badge: 'Condensed Impact', 
    desc: 'Huruf tegap, rapat & tegas bervolume tinggi', 
    fontFamily: "'Oswald', sans-serif" 
  },
  { 
    id: 'jakarta', 
    name: 'Plus Jakarta Sans', 
    badge: 'Tech & Sharp', 
    desc: 'Tipografi modern bernuansa profesional', 
    fontFamily: "'Plus Jakarta Sans', sans-serif" 
  },
  { 
    id: 'montserrat', 
    name: 'Montserrat', 
    badge: 'Classic Bold', 
    desc: 'Desain kokoh, elegan & mudah dibaca', 
    fontFamily: "'Montserrat', sans-serif" 
  },
];

const fontSizePresets = [
  { label: '34px (Kecil)', value: 34 },
  { label: '48px (Standar)', value: 48 },
  { label: '68px (Sedang)', value: 68 },
  { label: '96px (Besar)', value: 96 },
  { label: '140px (Ekstra)', value: 140 },
  { label: '200px (Maksimal)', value: 200 },
];

const strokeWidthPresets = [
  { label: '1.5px (Halus)', value: 1.5 },
  { label: '2.0px (Ideal/Standar)', value: 2 },
  { label: '2.5px (Tebal)', value: 2.5 },
  { label: '3.0px (Ekstra Tebal)', value: 3 },
];

const opacityPresets = [
  { label: '40% (Redup)', value: 40 },
  { label: '65% (Sedang)', value: 65 },
  { label: '85% (Jelas)', value: 85 },
  { label: '100% (Solid)', value: 100 },
];

const outlineOpacityPresets = [
  { label: '40% (Halus)', value: 40 },
  { label: '60% (Sedang)', value: 60 },
  { label: '80% (Tegas)', value: 80 },
  { label: '100% (Pekat)', value: 100 },
];

// New phrase input state
const newPhrase = ref({
  text: '',
  style: 'gradient'
});

// Separator suggestions
const separatorPresets = [
  { label: 'Bintang ✦', value: '✦' },
  { label: 'Bintang ★', value: '★' },
  { label: 'Berlian 🟌', value: '🟌' },
  { label: 'Titik •', value: '•' },
  { label: 'Kilat ⚡', value: '⚡' },
  { label: 'Slash //', value: '//' },
  { label: 'Garis |', value: '|' },
];

// Speed presets
function setSpeed(sec) {
  form.value.ticker_speed = sec;
}

// Add phrase
function addPhrase() {
  if (!newPhrase.value.text.trim()) {
    error('Silakan isi teks frasa terlebih dahulu.');
    return;
  }
  form.value.ticker_items.push({
    id: Date.now(),
    text: newPhrase.value.text.trim(),
    style: newPhrase.value.style,
    is_active: true
  });
  newPhrase.value.text = '';
  newPhrase.value.style = 'gradient';
  success('Frasa baru berhasil ditambahkan ke daftar.');
}

// Remove phrase
function removePhrase(index) {
  if (form.value.ticker_items.length <= 1) {
    error('Minimal harus ada 1 frasa teks berjalan.');
    return;
  }
  form.value.ticker_items.splice(index, 1);
}

// Move phrase position
function moveUp(index) {
  if (index === 0) return;
  const temp = form.value.ticker_items[index];
  form.value.ticker_items[index] = form.value.ticker_items[index - 1];
  form.value.ticker_items[index - 1] = temp;
}

function moveDown(index) {
  if (index === form.value.ticker_items.length - 1) return;
  const temp = form.value.ticker_items[index];
  form.value.ticker_items[index] = form.value.ticker_items[index + 1];
  form.value.ticker_items[index + 1] = temp;
}

// Reset to default phrases
function resetToDefault() {
  form.value.ticker_items = JSON.parse(JSON.stringify(defaultPhrases));
  form.value.ticker_speed = 35;
  form.value.ticker_separator = '✦';
  form.value.ticker_pause_on_hover = true;
  form.value.ticker_font_size = 48;
  form.value.ticker_font_family = 'syne';
  form.value.ticker_stroke_width = 2;
  form.value.ticker_opacity = 100;
  form.value.ticker_outline_opacity = 80;
  success('Daftar frasa dan parameter dikembalikan ke rekomendasi bawaan.');
}

// Active phrases count
const activeItemsCount = computed(() => {
  return form.value.ticker_items.filter(item => item.is_active).length;
});

// Active items for live preview
const previewItems = computed(() => {
  const active = form.value.ticker_items.filter(item => item.is_active);
  return active.length > 0 ? active : form.value.ticker_items;
});

const currentFontObj = computed(() => {
  return fontOptions.find(f => f.id === form.value.ticker_font_family) || fontOptions[0];
});

// Save settings to backend
function saveSettings() {
  if (form.value.ticker_items.length === 0) {
    error('Daftar frasa tidak boleh kosong.');
    return;
  }

  isSaving.value = true;
  router.post('/admin/ticker-settings', {
    ticker_is_active: form.value.ticker_is_active,
    ticker_speed: form.value.ticker_speed,
    ticker_separator: form.value.ticker_separator,
    ticker_pause_on_hover: form.value.ticker_pause_on_hover,
    ticker_font_size: form.value.ticker_font_size,
    ticker_font_family: form.value.ticker_font_family,
    ticker_stroke_width: form.value.ticker_stroke_width,
    ticker_opacity: form.value.ticker_opacity,
    ticker_outline_opacity: form.value.ticker_outline_opacity,
    ticker_items: form.value.ticker_items
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      success('Pengaturan teks berjalan & tipografi berhasil disimpan!');
    },
    onError: (errs) => {
      isSaving.value = false;
      error(Object.values(errs)[0] || 'Terjadi kesalahan saat menyimpan pengaturan.');
    }
  });
}
</script>

<template>
  <Head title="Kelola Teks Berjalan (Marquee) - Kustomisasi Lanjutan" />

  <KustomisasiLayout
    active-menu="ticker"
    page-title="Kelola Teks Berjalan (Marquee Ticker)"
    page-description="Atur sorotan teks berjalan konfer modern di halaman beranda: sesuaikan kata-kata, gaya outline/gradient, kecepatan geser, tipografi font, dan transparansi."
    page-badge="Teks Berjalan"
  >
    <template #actions>
      <button 
        type="button" 
        class="btn btn-primary d-flex align-items-center gap-1 shadow-sm px-3"
        :disabled="isSaving"
        @click="saveSettings"
      >
        <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
        <i v-else class="ri-save-line me-1"></i>
        Simpan Teks Berjalan
      </button>
    </template>

      <!-- Top Summary / Action Header -->
      <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);">
        <div class="card-body p-4 text-white">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="d-flex align-items-center gap-3 mb-2">
                <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.2);">
                  <i class="ri-text-wrap fs-22 text-white"></i>
                </div>
                <div>
                  <h3 class="fw-bold mb-1 text-white">Sorotan Teks Berjalan (Konfer Marquee)</h3>
                  <p class="mb-0 text-white-50 fs-13">
                    Atur teks highlight bergaya modern di halaman utama website: sesuaikan kata-kata, gaya outline/gradient, kecepatan geser, dan pemisah.
                  </p>
                </div>
              </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
              <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                <a href="/" target="_blank" class="btn btn-outline-light btn-sm rounded-pill px-3">
                  <i class="ri-external-link-line me-1"></i> Lihat Beranda
                </a>
                <button 
                  type="button" 
                  class="btn btn-light btn-sm rounded-pill px-4 fw-semibold text-primary shadow-sm"
                  :disabled="isSaving"
                  @click="saveSettings"
                >
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="ri-save-line me-1"></i>
                  Simpan Perubahan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Real-Time Interactive Live Preview Card -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary p-2 rounded">
              <i class="ri-eye-line fs-14"></i>
            </span>
            <div>
              <h5 class="card-title mb-0 fs-15 fw-bold">Live Preview Teks Berjalan</h5>
              <small class="text-muted">Simulasi tampilan real-time sesuai preferensi yang Anda atur di bawah</small>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2">
            <div class="form-check form-switch form-switch-sm d-flex align-items-center gap-1 mb-0">
              <input 
                class="form-check-input" 
                type="checkbox" 
                id="previewDarkSwitch" 
                v-model="previewDarkMode"
              >
              <label class="form-check-label fs-12 text-muted fw-medium" for="previewDarkSwitch">
                {{ previewDarkMode ? 'Mode Gelap' : 'Mode Terang' }}
              </label>
            </div>
            <span 
              class="badge rounded-pill fs-11"
              :class="form.ticker_is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
            >
              {{ form.ticker_is_active ? '● Aktif di Web' : '○ Dinonaktifkan' }}
            </span>
          </div>
        </div>

        <div 
          class="card-body p-0 position-relative overflow-hidden"
          :style="{ 
            background: previewDarkMode ? '#0b0f19' : '#ffffff',
            transition: 'background 0.3s ease'
          }"
        >
          <!-- Warning if deactivated -->
          <div v-if="!form.ticker_is_active" class="alert alert-warning m-3 py-2 px-3 fs-12 d-flex align-items-center gap-2 mb-0">
            <i class="ri-alert-line fs-16"></i>
            <span>Teks berjalan saat ini berstatus <strong>Nonaktif</strong>. Pengunjung website tidak akan melihat elemen ini hingga diaktifkan.</span>
          </div>

          <!-- Live Ticker Strip Container -->
          <div 
            class="live-ticker-preview-wrapper py-4"
            :class="{ 'preview-dark': previewDarkMode, 'pause-hover': form.ticker_pause_on_hover }"
          >
            <div 
              class="live-ticker-track" 
              :style="{ 
                animationDuration: form.ticker_speed + 's',
                fontFamily: currentFontObj.fontFamily,
                opacity: form.ticker_opacity / 100,
                '--preview-font-size': Math.round(form.ticker_font_size * 0.72) + 'px',
                '--stroke-w': form.ticker_stroke_width + 'px',
                '--outline-opacity': (form.ticker_outline_opacity || 80) / 100
              }"
            >
              <div class="live-ticker-content">
                <template v-for="(item, idx) in previewItems" :key="'p1-' + idx">
                  <span 
                    class="preview-ticker-text"
                    :class="[
                      item.style === 'gradient' ? 'is-gradient' : 
                      item.style === 'outline' ? 'is-outline' : 'is-filled'
                    ]"
                  >
                    {{ item.text }}
                  </span>
                  <span class="preview-ticker-star">{{ form.ticker_separator || '✦' }}</span>
                </template>
              </div>
              <!-- Duplicate for loop -->
              <div class="live-ticker-content" aria-hidden="true">
                <template v-for="(item, idx) in previewItems" :key="'p2-' + idx">
                  <span 
                    class="preview-ticker-text"
                    :class="[
                      item.style === 'gradient' ? 'is-gradient' : 
                      item.style === 'outline' ? 'is-outline' : 'is-filled'
                    ]"
                  >
                    {{ item.text }}
                  </span>
                  <span class="preview-ticker-star">{{ form.ticker_separator || '✦' }}</span>
                </template>
              </div>
            </div>
          </div>

          <!-- Preview Info Footer -->
          <div class="bg-light px-3 py-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2 fs-12 text-muted">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="badge bg-primary-subtle text-primary fw-medium">
                <i class="ri-font-size me-1"></i> Font: <strong>{{ currentFontObj.name }}</strong>
              </span>
              <span class="badge bg-secondary-subtle text-secondary fw-medium">
                Ukuran: <strong>{{ form.ticker_font_size }}px</strong>
              </span>
              <span class="badge bg-info-subtle text-info fw-medium">
                Tebal Outline: <strong>{{ form.ticker_stroke_width }}px</strong>
              </span>
              <span class="badge bg-success-subtle text-success fw-medium">
                <i class="ri-contrast-drop-2-line me-1"></i> Opasitas: <strong>{{ form.ticker_opacity }}%</strong>
              </span>
              <span class="badge bg-dark-subtle text-dark fw-medium">
                Outline: <strong>{{ form.ticker_outline_opacity }}%</strong>
              </span>
            </div>
            <div>
              <span class="text-muted"><i class="ri-speed-line me-1"></i> Kecepatan: {{ form.ticker_speed }} detik/siklus</span>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Settings Form Column -->
        <div class="col-lg-5 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3">
              <h5 class="card-title mb-0 fs-14 fw-bold">
                <i class="ri-settings-4-line text-primary me-1"></i> Parameter Gerakan & Tampilan
              </h5>
            </div>
            <div class="card-body p-4">
              <!-- Switch: Ticker Active -->
              <div class="p-3 rounded mb-3 border bg-light">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <label class="form-label fw-bold mb-0 fs-13">Tampilkan di Beranda Web</label>
                    <div class="text-muted fs-11">Aktifkan untuk memunculkan teks berjalan sebelum strip statistik.</div>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="tickerIsActiveSwitch" 
                      v-model="form.ticker_is_active"
                    >
                  </div>
                </div>
              </div>

              <!-- Speed Slider -->
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold fs-13 mb-0">Kecepatan Gerakan (Durasi Siklus)</label>
                  <span class="badge bg-primary fs-12 px-2 py-1">{{ form.ticker_speed }} Detik</span>
                </div>
                <small class="text-muted d-block mb-2 fs-11">
                  Semakin kecil detik, gerakan semakin cepat. Semakin besar detik, gerakan semakin santai (maksimal 150 detik).
                </small>
                <input 
                  type="range" 
                  class="form-range" 
                  min="15" 
                  max="150" 
                  step="1" 
                  v-model.number="form.ticker_speed"
                >
                <!-- Quick Speed Presets -->
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <button 
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_speed === 25 ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="setSpeed(25)"
                  >
                    ⚡ Cepat (25s)
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_speed === 45 ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="setSpeed(45)"
                  >
                    🎯 Sedang (45s)
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_speed === 75 ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="setSpeed(75)"
                  >
                    🍃 Santai (75s)
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_speed === 110 ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="setSpeed(110)"
                  >
                    🐢 Lambat (110s)
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_speed === 150 ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="setSpeed(150)"
                  >
                    🛑 Maksimal (150s)
                  </button>
                </div>
              </div>

              <!-- Font Family Selector (NEW) -->
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold fs-13 mb-0">
                    <i class="ri-font-family text-primary me-1"></i> Jenis Font Teks Berjalan
                  </label>
                  <span class="badge bg-primary-subtle text-primary fs-11 px-2 py-0.5">{{ currentFontObj.name }}</span>
                </div>
                <small class="text-muted d-block mb-2 fs-11">
                  Pilih tipografi yang paling pas. Font <strong>Syne</strong> sangat disarankan untuk gaya <strong>Stroke Outline</strong> ala Konfer.
                </small>
                <div class="d-flex flex-column gap-2">
                  <label 
                    v-for="font in fontOptions" 
                    :key="font.id"
                    class="p-2.5 rounded border d-flex align-items-center justify-content-between cursor-pointer transition-all"
                    :class="form.ticker_font_family === font.id ? 'border-primary bg-primary-subtle shadow-sm' : 'border-light bg-white'"
                    style="cursor: pointer;"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <input 
                        type="radio" 
                        name="font_family" 
                        :value="font.id" 
                        v-model="form.ticker_font_family"
                        class="form-check-input mt-0"
                      >
                      <div>
                        <div class="fw-bold fs-13 text-dark d-flex align-items-center gap-1.5">
                          <span>{{ font.name }}</span>
                          <span v-if="font.badge" class="badge bg-success-subtle text-success fs-10 px-1.5 py-0.5">{{ font.badge }}</span>
                        </div>
                        <div class="text-muted fs-11">{{ font.desc }}</div>
                      </div>
                    </div>
                    <!-- Mini visual preview -->
                    <div 
                      class="px-2 py-1 rounded text-uppercase fw-bold text-nowrap d-none d-sm-block"
                      :style="{ 
                        fontFamily: font.fontFamily, 
                        fontSize: '14px', 
                        letterSpacing: '0.04em',
                        background: '#f1f5f9',
                        color: '#0f172a'
                      }"
                    >
                      SEMARAK
                    </div>
                  </label>
                </div>
              </div>

              <!-- Font Size Slider & Presets (NEW) -->
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold fs-13 mb-0">
                    <i class="ri-font-size text-primary me-1"></i> Ukuran Font Teks (Font Size)
                  </label>
                  <span class="badge bg-primary fs-12 px-2 py-1">{{ form.ticker_font_size }} px</span>
                </div>
                <small class="text-muted d-block mb-2 fs-11">
                  Atur besaran huruf teks berjalan di layar desktop & mobile (24px s/d 200px).
                </small>
                <input 
                  type="range" 
                  class="form-range" 
                  min="24" 
                  max="200" 
                  step="2" 
                  v-model.number="form.ticker_font_size"
                >
                <!-- Quick Font Size Presets -->
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <button 
                    v-for="preset in fontSizePresets"
                    :key="preset.value"
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_font_size === preset.value ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="form.ticker_font_size = preset.value"
                  >
                    {{ preset.label }}
                  </button>
                </div>
              </div>

              <!-- Ticker Opacity Slider (NEW) -->
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold fs-13 mb-0">
                    <i class="ri-contrast-drop-2-line text-primary me-1"></i> Opasitas Teks Berjalan (Transparansi)
                  </label>
                  <span class="badge bg-primary fs-12 px-2 py-1">{{ form.ticker_opacity }}%</span>
                </div>
                <small class="text-muted d-block mb-2 fs-11">
                  Atur transparansi keseluruhan teks berjalan (20% s/d 100%). Nilai lebih rendah memberi kesan elegan menyatu dengan latar belakang.
                </small>
                <input 
                  type="range" 
                  class="form-range" 
                  min="20" 
                  max="100" 
                  step="5" 
                  v-model.number="form.ticker_opacity"
                >
                <!-- Quick Opacity Presets -->
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <button 
                    v-for="op in opacityPresets"
                    :key="op.value"
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_opacity === op.value ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="form.ticker_opacity = op.value"
                  >
                    {{ op.label }}
                  </button>
                </div>
              </div>

              <!-- Stroke Outline Width & Opacity (NEW) -->
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold fs-13 mb-0">
                    <i class="ri-pencil-ruler-2-line text-primary me-1"></i> Ketebalan Garis Stroke Outline
                  </label>
                  <span class="badge bg-info-subtle text-info fs-12 px-2 py-1">{{ form.ticker_stroke_width }} px</span>
                </div>
                <small class="text-muted d-block mb-2 fs-11">
                  Ketebalan garis tepi untuk frasa dengan gaya Stroke Outline agar tampak tajam dan tidak samar.
                </small>
                <div class="d-flex flex-wrap gap-1 mb-3">
                  <button 
                    v-for="stk in strokeWidthPresets"
                    :key="stk.value"
                    type="button" 
                    class="btn btn-sm py-1 px-2.5"
                    :class="form.ticker_stroke_width === stk.value ? 'btn-info text-white fw-bold' : 'btn-outline-light text-dark border'"
                    @click="form.ticker_stroke_width = stk.value"
                  >
                    {{ stk.label }}
                  </button>
                </div>

                <!-- Outline Specific Opacity -->
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-semibold fs-12 mb-0 text-muted">
                    <i class="ri-drop-line me-1"></i> Opasitas Khusus Garis Outline
                  </label>
                  <span class="badge bg-secondary-subtle text-secondary fs-11 px-2 py-0.5">{{ form.ticker_outline_opacity }}%</span>
                </div>
                <div class="d-flex flex-wrap gap-1">
                  <button 
                    v-for="oop in outlineOpacityPresets"
                    :key="oop.value"
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_outline_opacity === oop.value ? 'btn-secondary text-white fw-medium' : 'btn-outline-light text-dark border'"
                    @click="form.ticker_outline_opacity = oop.value"
                  >
                    {{ oop.label }}
                  </button>
                </div>
              </div>

              <!-- Separator Symbol -->
              <div class="mb-4">
                <label class="form-label fw-semibold fs-13 mb-1">Simbol Pemisah Frasa</label>
                <small class="text-muted d-block mb-2 fs-11">
                  Karakter atau ikon pemisah yang muncul di antara setiap frasa teks.
                </small>
                <div class="input-group mb-2">
                  <span class="input-group-text bg-white fw-bold fs-15 text-warning">{{ form.ticker_separator }}</span>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="form.ticker_separator" 
                    placeholder="Contoh: ✦, ★, //, •"
                    maxlength="10"
                  >
                </div>
                <!-- Preset Separator Buttons -->
                <div class="d-flex flex-wrap gap-1">
                  <button 
                    v-for="sep in separatorPresets" 
                    :key="sep.value"
                    type="button" 
                    class="btn btn-sm py-1 px-2"
                    :class="form.ticker_separator === sep.value ? 'btn-warning text-dark fw-bold' : 'btn-outline-light text-dark border'"
                    @click="form.ticker_separator = sep.value"
                  >
                    {{ sep.label }}
                  </button>
                </div>
              </div>

              <!-- Pause On Hover -->
              <div class="p-3 rounded border bg-light mb-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <label class="form-label fw-bold mb-0 fs-13">Jeda Otomatis Saat Kursor Diarahkan</label>
                    <div class="text-muted fs-11">Gerakan teks berhenti sementara saat mouse pengguna melayang di atas teks.</div>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="pauseOnHoverSwitch" 
                      v-model="form.ticker_pause_on_hover"
                    >
                  </div>
                </div>
              </div>

              <!-- Action button inside sidebar -->
              <button 
                type="button" 
                class="btn btn-primary w-100 py-2 fw-semibold"
                :disabled="isSaving"
                @click="saveSettings"
              >
                <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ri-check-line me-1"></i> Simpan Semua Pengaturan
              </button>
            </div>
          </div>
        </div>

        <!-- Phrase List Column -->
        <div class="col-lg-7 mb-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <h5 class="card-title mb-0 fs-14 fw-bold">
                  <i class="ri-list-check-2 text-primary me-1"></i> Daftar Frasa Teks Berjalan
                </h5>
                <small class="text-muted">Total {{ form.ticker_items.length }} frasa ({{ activeItemsCount }} aktif)</small>
              </div>

              <button 
                type="button" 
                class="btn btn-outline-secondary btn-sm"
                @click="resetToDefault"
                title="Kembalikan ke susunan teks bawaan"
              >
                <i class="ri-refresh-line me-1"></i> Muat Teks Default
              </button>
            </div>

            <div class="card-body p-4">
              <!-- Add New Phrase Box -->
              <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 mb-4">
                <div class="card-body p-3">
                  <div class="fw-bold fs-13 mb-2 text-primary">
                    <i class="ri-add-circle-fill me-1"></i> Tambah Frasa Baru
                  </div>
                  <div class="row g-2">
                    <div class="col-md-7">
                      <input 
                        type="text" 
                        class="form-control form-control-sm" 
                        v-model="newPhrase.text" 
                        placeholder="Ketik teks baru (misal: JUARA UMUM PIALA BERGILIR)..."
                        @keydown.enter.prevent="addPhrase"
                      >
                    </div>
                    <div class="col-md-3">
                      <CustomSelect v-model="newPhrase.style" size="sm">
                        <option value="gradient">🟣 Gradient Luminous</option>
                        <option value="outline">⚪ Stroked Outline</option>
                        <option value="filled">⚫ Solid Bold</option>
                      </CustomSelect>
                    </div>
                    <div class="col-md-2">
                      <button 
                        type="button" 
                        class="btn btn-primary btn-sm w-100 fw-semibold"
                        @click="addPhrase"
                      >
                        <i class="ri-add-line"></i> Tambah
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- List of Items -->
              <div class="phrase-list-container d-flex flex-column gap-2">
                <div 
                  v-for="(item, index) in form.ticker_items" 
                  :key="item.id || index"
                  class="phrase-item-card p-3 rounded border transition-all"
                  :class="{ 'opacity-50 bg-light': !item.is_active, 'bg-white shadow-xs': item.is_active }"
                >
                  <div class="row align-items-center g-2">
                    <!-- Move Up / Down Buttons -->
                    <div class="col-auto d-flex flex-column gap-1">
                      <button 
                        type="button" 
                        class="btn btn-light btn-xs p-1 lh-1 text-muted" 
                        :disabled="index === 0"
                        @click="moveUp(index)"
                        title="Geser Naik"
                      >
                        <i class="ri-arrow-up-s-line"></i>
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-light btn-xs p-1 lh-1 text-muted" 
                        :disabled="index === form.ticker_items.length - 1"
                        @click="moveDown(index)"
                        title="Geser Turun"
                      >
                        <i class="ri-arrow-down-s-line"></i>
                      </button>
                    </div>

                    <!-- Index badge -->
                    <div class="col-auto">
                      <span class="badge bg-secondary-subtle text-secondary px-2 py-1 rounded">#{{ index + 1 }}</span>
                    </div>

                    <!-- Phrase Input -->
                    <div class="col">
                      <input 
                        type="text" 
                        class="form-control form-control-sm fw-semibold" 
                        v-model="item.text"
                        placeholder="Isi teks frasa..."
                      >
                    </div>

                    <!-- Style Selector -->
                    <div class="col-md-3">
                      <CustomSelect v-model="item.style" size="sm">
                        <option value="gradient">🟣 Gradient Luminous</option>
                        <option value="outline">⚪ Stroked Outline</option>
                        <option value="filled">⚫ Solid Bold</option>
                      </CustomSelect>
                    </div>

                    <!-- Active Toggle Switch -->
                    <div class="col-auto">
                      <div class="form-check form-switch mb-0" :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          v-model="item.is_active"
                        >
                      </div>
                    </div>

                    <!-- Delete button -->
                    <div class="col-auto">
                      <button 
                        type="button" 
                        class="btn btn-outline-danger btn-sm p-1 lh-1" 
                        @click="removePhrase(index)"
                        title="Hapus Frasa"
                      >
                        <i class="ri-delete-bin-line"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Save button bottom -->
              <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <small class="text-muted">
                  <i class="ri-information-line me-1"></i> Jangan lupa klik simpan untuk memperbarui website.
                </small>
                <button 
                  type="button" 
                  class="btn btn-success px-4 fw-semibold"
                  :disabled="isSaving"
                  @click="saveSettings"
                >
                  <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                  <i v-else class="ri-save-3-line me-1"></i> Simpan Perubahan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
  </KustomisasiLayout>
</template>

<style scoped>
/* ==========================================================================
   LIVE PREVIEW TICKER STYLES (MATCHES PUBLIC HOME EXACTLY)
   ========================================================================== */
.live-ticker-preview-wrapper {
  position: relative;
  width: 100%;
  overflow: hidden;
  user-select: none;
}

.live-ticker-preview-wrapper::before,
.live-ticker-preview-wrapper::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  width: 80px;
  z-index: 3;
  pointer-events: none;
}

.live-ticker-preview-wrapper::before {
  left: 0;
  background: linear-gradient(90deg, #ffffff 0%, rgba(255, 255, 255, 0) 100%);
}

.live-ticker-preview-wrapper::after {
  right: 0;
  background: linear-gradient(270deg, #ffffff 0%, rgba(255, 255, 255, 0) 100%);
}

.live-ticker-preview-wrapper.preview-dark::before {
  background: linear-gradient(90deg, #0b0f19 0%, rgba(11, 15, 25, 0) 100%);
}

.live-ticker-preview-wrapper.preview-dark::after {
  background: linear-gradient(270deg, #0b0f19 0%, rgba(11, 15, 25, 0) 100%);
}

.live-ticker-track {
  display: flex;
  width: max-content;
  will-change: transform;
  animation: liveTickerScroll linear infinite;
}

.live-ticker-preview-wrapper.pause-hover .live-ticker-track:hover {
  animation-play-state: paused;
}

@keyframes liveTickerScroll {
  0% { transform: translate3d(0, 0, 0); }
  100% { transform: translate3d(-50%, 0, 0); }
}

.live-ticker-content {
  display: flex;
  align-items: center;
  flex-shrink: 0;
  gap: 24px;
  padding-right: 24px;
}

.preview-ticker-text {
  font-family: inherit !important;
  font-size: var(--preview-font-size, 1.85rem) !important;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  line-height: 1.1;
  white-space: nowrap;
}

.preview-ticker-text.is-filled {
  color: #0f172a;
}

.live-ticker-preview-wrapper.preview-dark .preview-ticker-text.is-filled {
  color: #f8fafc;
}

.preview-ticker-text.is-gradient {
  background: linear-gradient(135deg, #8b5cf6 0%, #ec4899 50%, #f97316 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.preview-ticker-text.is-outline {
  color: transparent;
  -webkit-text-stroke: var(--stroke-w, 2px) rgba(71, 85, 105, var(--outline-opacity, 0.8));
}

.live-ticker-preview-wrapper.preview-dark .preview-ticker-text.is-outline {
  -webkit-text-stroke: var(--stroke-w, 2px) rgba(255, 255, 255, var(--outline-opacity, 0.6));
}

.preview-ticker-star {
  font-size: 1.4rem;
  color: #f97316;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  animation: starPulseLive 4s ease-in-out infinite;
}

@keyframes starPulseLive {
  0%, 100% { transform: scale(1) rotate(0deg); color: #f97316; }
  50% { transform: scale(1.2) rotate(180deg); color: #ec4899; }
}

.phrase-item-card {
  transition: all 0.2s ease;
}

.phrase-item-card:hover {
  border-color: #cbd5e1;
}

.btn-xs {
  font-size: 10px;
  line-height: 1;
}
</style>
