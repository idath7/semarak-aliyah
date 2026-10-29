<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  galleries: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) }
});

const { success, error } = useToast();

// 1. Settings Form State
const settingsForm = ref({
  gallery_section_tag: props.settings.gallery_section_tag || 'Galeri Dokumentasi',
  gallery_section_title: props.settings.gallery_section_title || 'Momen Spektakuler & Kilas Balik',
  gallery_section_desc: props.settings.gallery_section_desc || 'Saksikan sorak kemeriahan, kobaran semangat sportivitas talenta muda, dan kilas balik keseruan panggung Semarak Aliyah.',
  gallery_is_active: props.settings.gallery_is_active !== false,
});

const isSavingSettings = ref(false);
const showSettingsCard = ref(false);

function saveSettings() {
  isSavingSettings.value = true;
  router.post('/admin/galeri-settings', settingsForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingSettings.value = false;
      success('Pengaturan teks dan status galeri berhasil disimpan!');
    },
    onError: (errs) => {
      isSavingSettings.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan pengaturan.');
    }
  });
}

function quickToggleGalleryActive() {
  settingsForm.value.gallery_is_active = !settingsForm.value.gallery_is_active;
  saveSettings();
}

// 2. Filter & View State
const searchQuery = ref('');
const filterRow = ref('all'); // 'all', '1', '2'
const filterStatus = ref('all'); // 'all', 'active', 'inactive'
const viewMode = ref('grid'); // 'grid' | 'table'

const filteredGalleries = computed(() => {
  return props.galleries.filter(item => {
    const q = searchQuery.value.toLowerCase().trim();
    const matchQuery = !q ||
      item.title?.toLowerCase().includes(q) ||
      item.category_label?.toLowerCase().includes(q) ||
      item.location?.toLowerCase().includes(q) ||
      item.tag?.toLowerCase().includes(q);

    if (!matchQuery) return false;

    if (filterRow.value === '1' && item.row_position !== 1) return false;
    if (filterRow.value === '2' && item.row_position !== 2) return false;

    if (filterStatus.value === 'active' && !item.is_active) return false;
    if (filterStatus.value === 'inactive' && item.is_active) return false;

    return true;
  });
});

// Stats
const totalCount = computed(() => props.galleries.length);
const row1Count = computed(() => props.galleries.filter(g => g.row_position === 1).length);
const row2Count = computed(() => props.galleries.filter(g => g.row_position === 2).length);
const activeCount = computed(() => props.galleries.filter(g => g.is_active).length);

// 3. Modal Form State (Tambah / Edit Foto)
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSavingGallery = ref(false);
const imagePreview = ref(null);
const fileInputRef = ref(null);

const galleryForm = ref({
  title: '',
  category_label: 'Juara & Awarding',
  pill_class: 'pill-gold',
  icon_class: 'ri-trophy-fill',
  location: 'Panggung Utama Semarak Aliyah',
  tag: 'Momen Spektakuler',
  description: '',
  row_position: 1,
  sort_order: 1,
  is_active: true,
  image_url: '',
  image_file: null,
});

const pillColorPresets = [
  { label: 'Emas (Gold)', class: 'pill-gold', bg: '#eab308', text: '#0f172a' },
  { label: 'Ungu (Purple)', class: 'pill-purple', bg: '#9333ea', text: '#ffffff' },
  { label: 'Biru (Blue)', class: 'pill-blue', bg: '#0ea5e9', text: '#ffffff' },
  { label: 'Hijau (Green)', class: 'pill-green', bg: '#22c55e', text: '#ffffff' },
  { label: 'Oranye (Orange)', class: 'pill-orange', bg: '#f97316', text: '#ffffff' },
  { label: 'Pink (Magenta)', class: 'pill-pink', bg: '#ec4899', text: '#ffffff' },
];

const categoryPresets = [
  { label: 'Juara & Awarding', pill: 'pill-gold', icon: 'ri-trophy-fill' },
  { label: 'E-Sport Arena', pill: 'pill-purple', icon: 'ri-gamepad-fill' },
  { label: 'Olimpiade Sains', pill: 'pill-blue', icon: 'ri-flask-fill' },
  { label: 'Bahasa & Sastra', pill: 'pill-pink', icon: 'ri-mic-fill' },
  { label: 'Seni Islami & Budaya', pill: 'pill-green', icon: 'ri-music-2-fill' },
  { label: 'Sportivitas Voli', pill: 'pill-orange', icon: 'ri-football-fill' },
  { label: 'Seni Vokal Islami', pill: 'pill-purple', icon: 'ri-voiceprint-fill' },
  { label: 'Suporter & Fairplay', pill: 'pill-gold', icon: 'ri-flag-fill' },
];

const iconPresets = [
  'ri-trophy-fill',
  'ri-gamepad-fill',
  'ri-flask-fill',
  'ri-mic-fill',
  'ri-music-2-fill',
  'ri-football-fill',
  'ri-voiceprint-fill',
  'ri-flag-fill',
  'ri-award-fill',
  'ri-camera-fill',
  'ri-star-fill',
  'ri-heart-fill',
  'ri-sparkling-fill',
  'ri-medal-fill'
];

function applyCategoryPreset(preset) {
  galleryForm.value.category_label = preset.label;
  galleryForm.value.pill_class = preset.pill;
  galleryForm.value.icon_class = preset.icon;
}

function openCreateModal(targetRow = 1) {
  isEditing.value = false;
  editingId.value = null;
  imagePreview.value = null;
  galleryForm.value = {
    title: '',
    category_label: 'Juara & Awarding',
    pill_class: 'pill-gold',
    icon_class: 'ri-trophy-fill',
    location: 'Panggung Utama Semarak Aliyah',
    tag: 'Momen Spektakuler',
    description: '',
    row_position: targetRow,
    sort_order: props.galleries.length + 1,
    is_active: true,
    image_url: '',
    image_file: null,
  };
  isModalOpen.value = true;
}

function openEditModal(item) {
  isEditing.value = true;
  editingId.value = item.id;
  imagePreview.value = item.image_path || null;
  galleryForm.value = {
    title: item.title,
    category_label: item.category_label || 'Dokumentasi',
    pill_class: item.pill_class || 'pill-gold',
    icon_class: item.icon_class || 'ri-image-line',
    location: item.location || '',
    tag: item.tag || '',
    description: item.description || '',
    row_position: item.row_position || 1,
    sort_order: item.sort_order || 1,
    is_active: item.is_active,
    image_url: item.image_path || '',
    image_file: null,
  };
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  imagePreview.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
}

function handleImageFileChange(e) {
  const file = e.target.files[0];
  if (!file) return;

  if (file.size > 6 * 1024 * 1024) {
    error('Ukuran file maksimal 6MB');
    return;
  }

  galleryForm.value.image_file = file;
  const reader = new FileReader();
  reader.onload = (ev) => {
    imagePreview.value = ev.target.result;
  };
  reader.readAsDataURL(file);
}

function saveGallery() {
  if (!galleryForm.value.title.trim()) {
    error('Judul foto wajib diisi.');
    return;
  }

  if (!isEditing.value && !galleryForm.value.image_file && !galleryForm.value.image_url.trim()) {
    error('File gambar foto atau URL gambar wajib disertakan.');
    return;
  }

  isSavingGallery.value = true;

  const formData = new FormData();
  formData.append('title', galleryForm.value.title);
  formData.append('category_label', galleryForm.value.category_label || '');
  formData.append('pill_class', galleryForm.value.pill_class || 'pill-gold');
  formData.append('icon_class', galleryForm.value.icon_class || 'ri-image-line');
  formData.append('location', galleryForm.value.location || '');
  formData.append('tag', galleryForm.value.tag || '');
  formData.append('description', galleryForm.value.description || '');
  formData.append('row_position', String(galleryForm.value.row_position));
  formData.append('sort_order', String(galleryForm.value.sort_order));
  formData.append('is_active', galleryForm.value.is_active ? '1' : '0');

  if (galleryForm.value.image_file) {
    formData.append('image_file', galleryForm.value.image_file);
  }
  if (galleryForm.value.image_url) {
    formData.append('image_url', galleryForm.value.image_url);
  }

  const endpoint = isEditing.value
    ? `/admin/galeri/${editingId.value}`
    : '/admin/galeri';

  router.post(endpoint, formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isSavingGallery.value = false;
      closeModal();
      success(isEditing.value ? 'Foto galeri berhasil diperbarui!' : 'Foto galeri baru berhasil ditambahkan!');
    },
    onError: (errs) => {
      isSavingGallery.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan foto galeri.');
    }
  });
}

// 4. Quick Actions
function toggleActive(item) {
  router.post(`/admin/galeri-toggle/${item.id}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Status "${item.title}" berhasil diubah!`);
    },
    onError: () => {
      error('Gagal memperbarui status foto.');
    }
  });
}

function quickSwitchRow(item) {
  const newRow = item.row_position === 1 ? 2 : 1;
  const label = newRow === 1 ? 'Baris 1 (Atas - Geser Kiri)' : 'Baris 2 (Bawah - Geser Kanan)';
  
  router.post('/admin/galeri-reorder', {
    orders: [{ id: item.id, row_position: newRow, sort_order: item.sort_order }]
  }, {
    preserveScroll: true,
    onSuccess: () => {
      success(`"${item.title}" dipindahkan ke ${label}!`);
    },
    onError: () => {
      error('Gagal memindahkan baris slider.');
    }
  });
}

// 5. Delete Confirmation Modal
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(item) {
  itemToDelete.value = item;
  isDeleteModalOpen.value = true;
}

function cancelDelete() {
  itemToDelete.value = null;
  isDeleteModalOpen.value = false;
}

function executeDelete() {
  if (!itemToDelete.value) return;
  isDeleting.value = true;

  router.delete(`/admin/galeri/${itemToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleting.value = false;
      cancelDelete();
      success('Foto galeri berhasil dihapus!');
    },
    onError: () => {
      isDeleting.value = false;
      error('Gagal menghapus foto galeri.');
    }
  });
}
</script>

<template>
  <VelzonLayout>
    <Head title="Manajemen Galeri Dokumentasi - Admin Semarak Aliyah" />

    <div class="page-content py-4">
      <div class="container-fluid">
        <!-- Breadcrumb & Title -->
        <div class="row mb-3 align-items-center">
          <div class="col-12 col-md-6 mb-2 mb-md-0">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-warning-subtle text-warning rounded-3 fs-2">
                  <i class="ri-gallery-fill"></i>
                </span>
              </div>
              <div>
                <h4 class="fw-bold mb-0 text-dark">Manajemen Galeri Dokumentasi</h4>
                <p class="text-muted fs-13 mb-0">Atur foto slider 2 baris berlawanan arah, teks judul, serta status publikasi.</p>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
            <a 
              href="/#galeri" 
              target="_blank" 
              class="btn btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm"
            >
              <i class="ri-external-link-line"></i>
              <span>Lihat di Beranda</span>
            </a>

            <button 
              type="button" 
              class="btn btn-soft-secondary d-inline-flex align-items-center gap-1"
              @click="showSettingsCard = !showSettingsCard"
            >
              <i :class="showSettingsCard ? 'ri-arrow-up-s-line' : 'ri-settings-4-line'"></i>
              <span>{{ showSettingsCard ? 'Tutup Pengaturan' : 'Teks & Pengaturan' }}</span>
            </button>

            <button 
              type="button" 
              class="btn btn-warning d-inline-flex align-items-center gap-1 shadow-sm text-dark fw-bold"
              @click="openCreateModal(1)"
            >
              <i class="ri-add-line fs-16"></i>
              <span>Tambah Foto</span>
            </button>
          </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="row g-3 mb-4">
          <!-- Total Foto -->
          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 card-animate">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Total Foto Galeri</span>
                    <h3 class="fw-bold my-1 text-dark">{{ totalCount }}</h3>
                    <span class="text-success fs-11 fw-medium"><i class="ri-check-double-line"></i> {{ activeCount }} tayang aktif</span>
                  </div>
                  <div class="avatar-sm">
                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                      <i class="ri-image-2-fill"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Baris 1: Atas (Geser Kiri) -->
          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 card-animate">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Baris 1 (Atas)</span>
                    <h3 class="fw-bold my-1 text-primary">{{ row1Count }} <span class="fs-12 text-muted fw-normal">foto</span></h3>
                    <span class="badge bg-primary-subtle text-primary fs-11">
                      <i class="ri-arrow-left-line"></i> Gerak ke Kiri
                    </span>
                  </div>
                  <div class="avatar-sm">
                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                      <i class="ri-align-top"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Baris 2: Bawah (Geser Kanan) -->
          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 card-animate">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Baris 2 (Bawah)</span>
                    <h3 class="fw-bold my-1 text-success">{{ row2Count }} <span class="fs-12 text-muted fw-normal">foto</span></h3>
                    <span class="badge bg-success-subtle text-success fs-11">
                      <i class="ri-arrow-right-line"></i> Gerak ke Kanan
                    </span>
                  </div>
                  <div class="avatar-sm">
                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                      <i class="ri-align-bottom"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Section Galeri -->
          <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 rounded-3 card-animate">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <span class="text-muted fs-12 fw-semibold text-uppercase">Status di Beranda</span>
                    <div class="my-1">
                      <span 
                        class="badge fs-12 px-2 py-1"
                        :class="settingsForm.gallery_is_active ? 'bg-success text-white' : 'bg-secondary text-white'"
                      >
                        {{ settingsForm.gallery_is_active ? 'Ditampilkan' : 'Disembunyikan' }}
                      </span>
                    </div>
                    <button 
                      type="button" 
                      class="btn btn-link p-0 text-decoration-none fs-11 text-muted"
                      @click="quickToggleGalleryActive"
                    >
                      <i class="ri-refresh-line"></i> Klik untuk ubah
                    </button>
                  </div>
                  <div class="avatar-sm">
                    <span 
                      class="avatar-title rounded-circle fs-3"
                      :class="settingsForm.gallery_is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted'"
                    >
                      <i :class="settingsForm.gallery_is_active ? 'ri-eye-line' : 'ri-eye-off-line'"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Settings Card (Teks Judul & Deskripsi Galeri) -->
        <transition name="fade">
          <div v-show="showSettingsCard" class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-settings-3-fill text-warning fs-18"></i>
                <h5 class="card-title mb-0 fw-bold">Pengaturan Teks Judul & Deskripsi Galeri</h5>
              </div>
              <span class="badge bg-light text-dark fs-11">Berlaku di Beranda Publik</span>
            </div>
            <div class="card-body p-4">
              <form @submit.prevent="saveSettings">
                <div class="row g-3">
                  <div class="col-12 col-md-4">
                    <label class="form-label fw-semibold fs-13">Tag Kategori Kecil</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="ri-price-tag-3-line"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="settingsForm.gallery_section_tag" 
                        placeholder="Contoh: Galeri Dokumentasi"
                      />
                    </div>
                    <small class="text-muted fs-11">Teks badge kecil di atas judul utama.</small>
                  </div>

                  <div class="col-12 col-md-8">
                    <label class="form-label fw-semibold fs-13">Judul Utama Seksi Galeri <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="ri-heading"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="settingsForm.gallery_section_title" 
                        placeholder="Contoh: Momen Spektakuler & Kilas Balik"
                        required
                      />
                    </div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fw-semibold fs-13">Deskripsi Subjudul <span class="text-danger">*</span></label>
                    <textarea 
                      class="form-control" 
                      rows="2" 
                      v-model="settingsForm.gallery_section_desc"
                      placeholder="Tuliskan narasi singkat kilas balik acara..."
                      required
                    ></textarea>
                  </div>

                  <div class="col-12 d-flex align-items-center justify-content-between pt-2 border-top">
                    <div class="form-check form-switch fs-14">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        role="switch" 
                        id="galleryIsActiveSwitch" 
                        v-model="settingsForm.gallery_is_active"
                      />
                      <label class="form-check-label fw-semibold" for="galleryIsActiveSwitch">
                        Aktifkan / Tampilkan Seksi Galeri di Beranda
                      </label>
                    </div>

                    <button 
                      type="submit" 
                      class="btn btn-primary d-inline-flex align-items-center gap-1 px-4 shadow-sm"
                      :disabled="isSavingSettings"
                    >
                      <i v-if="isSavingSettings" class="ri-loader-4-line ri-spin"></i>
                      <i v-else class="ri-save-line"></i>
                      <span>Simpan Pengaturan Teks</span>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </transition>

        <!-- Main Content Card: List Foto Galeri -->
        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-header bg-white border-bottom py-3">
            <div class="row g-2 align-items-center justify-content-between">
              <!-- Search & Filter Controls -->
              <div class="col-12 col-md-7 d-flex gap-2 flex-wrap">
                <!-- Search Input -->
                <div class="input-group" style="max-width: 260px;">
                  <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                  <input 
                    type="text" 
                    class="form-control border-start-0 ps-0" 
                    placeholder="Cari judul, kategori, lokasi..."
                    v-model="searchQuery"
                  />
                  <button 
                    v-if="searchQuery" 
                    class="btn btn-light border" 
                    type="button" 
                    @click="searchQuery = ''"
                  >
                    <i class="ri-close-line"></i>
                  </button>
                </div>

                <!-- Row Filter -->
                <div style="min-width: 170px;">
                  <CustomSelect v-model="filterRow" size="md">
                    <option value="all">Semua Baris</option>
                    <option value="1">Baris 1 (Atas - Kiri)</option>
                    <option value="2">Baris 2 (Bawah - Kanan)</option>
                  </CustomSelect>
                </div>

                <!-- Status Filter -->
                <div style="min-width: 140px;">
                  <CustomSelect v-model="filterStatus" size="md">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif Saja</option>
                    <option value="inactive">Nonaktif</option>
                  </CustomSelect>
                </div>
              </div>

              <!-- View Mode & Counter -->
              <div class="col-12 col-md-5 d-flex align-items-center justify-content-md-end gap-2">
                <span class="text-muted fs-12">
                  Menampilkan <strong>{{ filteredGalleries.length }}</strong> dari {{ totalCount }} foto
                </span>

                <div class="btn-group" role="group">
                  <button 
                    type="button" 
                    class="btn btn-sm" 
                    :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="viewMode = 'grid'"
                    title="Tampilan Grid Card"
                  >
                    <i class="ri-grid-fill"></i>
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-sm" 
                    :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary'"
                    @click="viewMode = 'table'"
                    title="Tampilan Tabel Data"
                  >
                    <i class="ri-table-line"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="card-body p-4">
            <!-- Empty State -->
            <div v-if="filteredGalleries.length === 0" class="text-center py-5">
              <div class="avatar-lg mx-auto mb-3">
                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-36 shadow-sm border border-warning-subtle">
                  <i class="ri-image-line"></i>
                </span>
              </div>
              <h5 class="fw-bold text-body mb-2">Tidak ada foto ditemukan</h5>
              <p class="text-muted fs-13 mb-3">
                {{ searchQuery ? 'Tidak ada foto yang cocok dengan kata kunci pencarian.' : 'Belum ada foto galeri yang ditambahkan.' }}
              </p>
              <button 
                type="button" 
                class="btn btn-warning btn-sm text-dark fw-bold px-3 shadow-sm"
                @click="openCreateModal(1)"
              >
                <i class="ri-add-line"></i> Tambah Foto Sekarang
              </button>
            </div>

            <!-- VIEW 1: GRID CARDS (VISUAL KONFER STYLE) -->
            <div v-else-if="viewMode === 'grid'" class="row g-3">
              <div 
                v-for="item in filteredGalleries" 
                :key="item.id" 
                class="col-12 col-sm-6 col-lg-4 col-xl-3"
              >
                <div class="card border rounded-3 overflow-hidden shadow-sm h-100 gallery-admin-card" :class="{ 'opacity-75': !item.is_active }">
                  <!-- Image & Overlay Container -->
                  <div class="gallery-admin-thumb-wrap position-relative">
                    <img 
                      :src="item.image_path" 
                      :alt="item.title"
                      class="w-100 object-fit-cover gallery-admin-img"
                      style="height: 190px;"
                      loading="lazy"
                    />

                    <!-- Top Bar: Row Badge & Status Switch -->
                    <div class="position-absolute top-0 start-0 end-0 p-2 d-flex align-items-center justify-content-between gallery-card-top-grad">
                      <!-- Row Badge -->
                      <span 
                        class="badge px-2 py-1 fs-11 shadow-sm d-inline-flex align-items-center gap-1"
                        :class="item.row_position === 1 ? 'bg-primary text-white' : 'bg-success text-white'"
                      >
                        <i :class="item.row_position === 1 ? 'ri-arrow-left-line' : 'ri-arrow-right-line'"></i>
                        <span>{{ item.row_position === 1 ? 'Baris 1 (Atas)' : 'Baris 2 (Bawah)' }}</span>
                      </span>

                      <!-- Active Status Pill -->
                      <button 
                        type="button"
                        class="badge border-0 px-2 py-1 fs-11 cursor-pointer"
                        :class="item.is_active ? 'bg-success text-white' : 'bg-dark text-white'"
                        @click="toggleActive(item)"
                        :title="item.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                      >
                        <i :class="item.is_active ? 'ri-eye-line' : 'ri-eye-off-line'"></i>
                        <span>{{ item.is_active ? 'Aktif' : 'Draft' }}</span>
                      </button>
                    </div>

                    <!-- Category Pill Badge -->
                    <div class="position-absolute bottom-0 start-0 p-2">
                      <span class="gallery-cat-pill" :class="item.pill_class" style="font-size: 0.68rem; padding: 3px 8px;">
                        <i :class="item.icon_class"></i>
                        <span>{{ item.category_label }}</span>
                      </span>
                    </div>
                  </div>

                  <!-- Card Details -->
                  <div class="card-body p-3 d-flex flex-direction-column justify-content-between">
                    <div>
                      <h6 class="fw-bold text-dark text-truncate-2 mb-1" :title="item.title" style="min-height: 40px; line-height: 1.35;">
                        {{ item.title }}
                      </h6>

                      <div class="d-flex align-items-center gap-2 fs-11 text-muted mb-2 flex-wrap">
                        <span v-if="item.location" class="text-truncate" style="max-width: 140px;" :title="item.location">
                          <i class="ri-map-pin-2-line text-warning"></i> {{ item.location }}
                        </span>
                        <span v-if="item.tag" class="text-truncate text-primary" style="max-width: 110px;" :title="item.tag">
                          <i class="ri-price-tag-3-line"></i> {{ item.tag }}
                        </span>
                      </div>

                      <p v-if="item.description" class="text-muted fs-12 text-truncate-2 mb-0" style="line-height: 1.4;">
                        {{ item.description }}
                      </p>
                    </div>

                    <!-- Action Bar -->
                    <div class="d-flex align-items-center justify-content-between pt-3 mt-2 border-top">
                      <!-- Quick Switch Row Button -->
                      <button 
                        type="button" 
                        class="btn btn-sm btn-light border text-muted d-inline-flex align-items-center gap-1 fs-11 px-2 py-1"
                        @click="quickSwitchRow(item)"
                        :title="item.row_position === 1 ? 'Pindahkan ke Baris 2 (Bawah)' : 'Pindahkan ke Baris 1 (Atas)'"
                      >
                        <i class="ri-arrow-up-down-line text-info"></i>
                        <span>Pindah {{ item.row_position === 1 ? 'Bawah' : 'Atas' }}</span>
                      </button>

                      <div class="d-flex gap-1">
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-primary px-2 py-1"
                          @click="openEditModal(item)"
                          title="Edit Foto"
                        >
                          <i class="ri-edit-line"></i>
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-danger px-2 py-1"
                          @click="confirmDelete(item)"
                          title="Hapus Foto"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- VIEW 2: TABLE VIEW -->
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 90px;">Foto</th>
                    <th>Judul & Deskripsi</th>
                    <th>Kategori & Badge</th>
                    <th>Penempatan Baris</th>
                    <th>Lokasi & Tag</th>
                    <th class="text-center" style="width: 90px;">Status</th>
                    <th class="text-end" style="width: 120px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in filteredGalleries" :key="item.id">
                    <td class="text-muted fs-12 fw-medium">{{ index + 1 }}</td>
                    <td>
                      <img 
                        :src="item.image_path" 
                        :alt="item.title"
                        class="rounded-2 object-fit-cover shadow-sm"
                        style="width: 72px; height: 50px;"
                        loading="lazy"
                      />
                    </td>
                    <td>
                      <div class="fw-bold text-dark fs-13 mb-0">{{ item.title }}</div>
                      <small class="text-muted text-truncate d-inline-block" style="max-width: 320px;">
                        {{ item.description || 'Tidak ada deskripsi' }}
                      </small>
                    </td>
                    <td>
                      <span class="gallery-cat-pill" :class="item.pill_class" style="font-size: 0.68rem; padding: 2px 8px;">
                        <i :class="item.icon_class"></i>
                        <span>{{ item.category_label }}</span>
                      </span>
                    </td>
                    <td>
                      <button 
                        type="button"
                        class="badge border-0 px-2 py-1 fs-11 cursor-pointer d-inline-flex align-items-center gap-1 shadow-sm"
                        :class="item.row_position === 1 ? 'bg-primary text-white' : 'bg-success text-white'"
                        @click="quickSwitchRow(item)"
                        :title="'Klik untuk pindah ke baris ' + (item.row_position === 1 ? '2 (Bawah)' : '1 (Atas)')"
                      >
                        <i :class="item.row_position === 1 ? 'ri-arrow-left-line' : 'ri-arrow-right-line'"></i>
                        <span>{{ item.row_position === 1 ? 'Baris 1 (Atas)' : 'Baris 2 (Bawah)' }}</span>
                      </button>
                    </td>
                    <td>
                      <div class="fs-12 text-dark"><i class="ri-map-pin-2-line text-warning"></i> {{ item.location || '-' }}</div>
                      <small class="text-muted"><i class="ri-price-tag-3-line text-primary"></i> {{ item.tag || '-' }}</small>
                    </td>
                    <td class="text-center">
                      <div class="form-check form-switch d-inline-block">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          :checked="item.is_active" 
                          @change="toggleActive(item)"
                          :title="item.is_active ? 'Aktif' : 'Nonaktif'"
                        />
                      </div>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex gap-1">
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-primary"
                          @click="openEditModal(item)"
                          title="Edit Foto"
                        >
                          <i class="ri-edit-line"></i>
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-danger"
                          @click="confirmDelete(item)"
                          title="Hapus Foto"
                        >
                          <i class="ri-delete-bin-line"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL FORM: TAMBAH / EDIT FOTO GALERI -->
    <Teleport to="body">
      <div 
        v-if="isModalOpen" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1055;"
      >
        <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom py-3">
              <div class="d-flex align-items-center gap-2">
                <div class="avatar-xs flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-4">
                    <i :class="isEditing ? 'ri-edit-line' : 'ri-add-line'"></i>
                  </span>
                </div>
                <h5 class="modal-title fw-bold">
                  {{ isEditing ? 'Edit Foto Galeri Dokumentasi' : 'Tambah Foto Galeri Baru' }}
                </h5>
              </div>
              <button type="button" class="btn-close" @click="closeModal" aria-label="Close"></button>
            </div>

            <form @submit.prevent="saveGallery">
              <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                <div class="row g-3">
                  <!-- Penempatan Baris Slider -->
                  <div class="col-12">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Penempatan Baris Slider (Gerakan Berlawanan) <span class="text-danger">*</span>
                    </label>
                    <div class="row g-2">
                      <div class="col-6">
                        <label 
                          class="card border p-3 rounded-3 cursor-pointer text-center h-100 transition-all"
                          :class="galleryForm.row_position === 1 ? 'border-primary bg-primary-subtle text-primary fw-bold shadow-sm' : 'bg-white text-muted'"
                        >
                          <input 
                            type="radio" 
                            name="row_position" 
                            :value="1" 
                            v-model.number="galleryForm.row_position" 
                            class="d-none"
                          />
                          <div class="fs-4 mb-1"><i class="ri-arrow-left-line"></i></div>
                          <div class="fs-13 fw-bold">Baris 1 (Atas)</div>
                          <small class="fs-11 text-muted d-block">Bergerak otomatis ke KIRI</small>
                        </label>
                      </div>

                      <div class="col-6">
                        <label 
                          class="card border p-3 rounded-3 cursor-pointer text-center h-100 transition-all"
                          :class="galleryForm.row_position === 2 ? 'border-success bg-success-subtle text-success fw-bold shadow-sm' : 'bg-white text-muted'"
                        >
                          <input 
                            type="radio" 
                            name="row_position" 
                            :value="2" 
                            v-model.number="galleryForm.row_position" 
                            class="d-none"
                          />
                          <div class="fs-4 mb-1"><i class="ri-arrow-right-line"></i></div>
                          <div class="fs-13 fw-bold">Baris 2 (Bawah)</div>
                          <small class="fs-11 text-muted d-block">Bergerak otomatis ke KANAN</small>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Judul Foto -->
                  <div class="col-12">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Judul Foto / Momen Kegiatan <span class="text-danger">*</span>
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="galleryForm.title" 
                      placeholder="Contoh: Selebrasi Penganugerahan Juara Umum Bergilir"
                      required
                    />
                  </div>

                  <!-- Upload File Gambar / URL -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Upload File Foto (Maks. 6MB)
                    </label>
                    <input 
                      type="file" 
                      class="form-control" 
                      accept="image/*"
                      ref="fileInputRef"
                      @change="handleImageFileChange"
                    />
                    <small class="text-muted fs-11">Mendukung format JPG, PNG, WebP beresolusi tinggi.</small>
                  </div>

                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Atau Gunakan URL / Path Gambar
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="galleryForm.image_url" 
                      placeholder="/assets/img/gallery/gallery_awarding.jpg atau https://..."
                    />
                    <small class="text-muted fs-11">Path relatif aset lokal atau URL CDN.</small>
                  </div>

                  <!-- Pratinjau Gambar -->
                  <div v-if="imagePreview || galleryForm.image_url" class="col-12">
                    <div class="card bg-light border p-2 rounded-3 text-center">
                      <div class="text-muted fs-11 mb-1 fw-semibold">Pratinjau Foto:</div>
                      <img 
                        :src="imagePreview || galleryForm.image_url" 
                        alt="Preview" 
                        class="mx-auto rounded-2 shadow-sm object-fit-cover"
                        style="max-height: 180px; width: 100%; max-width: 340px;"
                      />
                    </div>
                  </div>

                  <!-- Preset Kategori Cepat -->
                  <div class="col-12">
                    <label class="form-label fw-bold fs-13 text-dark mb-1">
                      Pilihan Cepat Kategori & Warna Badge
                    </label>
                    <div class="d-flex flex-wrap gap-1">
                      <button 
                        v-for="p in categoryPresets" 
                        :key="p.label" 
                        type="button" 
                        class="btn btn-sm btn-outline-secondary py-1 px-2 fs-11 d-inline-flex align-items-center gap-1"
                        @click="applyCategoryPreset(p)"
                      >
                        <i :class="p.icon"></i>
                        <span>{{ p.label }}</span>
                      </button>
                    </div>
                  </div>

                  <!-- Input Kategori & Pill Class -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Nama Kategori / Badge
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="galleryForm.category_label" 
                      placeholder="Contoh: Juara & Awarding"
                    />
                  </div>

                  <!-- Warna Badge Pill -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Pilihan Warna Badge Pill
                    </label>
                    <div class="d-flex flex-wrap gap-1 pt-1">
                      <button 
                        v-for="cp in pillColorPresets" 
                        :key="cp.class" 
                        type="button"
                        class="btn btn-sm py-1 px-2 fs-11 rounded-pill"
                        :style="{ backgroundColor: cp.bg, color: cp.text }"
                        :class="{ 'border-dark border-2 shadow-sm fw-bold': galleryForm.pill_class === cp.class }"
                        @click="galleryForm.pill_class = cp.class"
                      >
                        {{ cp.label }}
                      </button>
                    </div>
                  </div>

                  <!-- Ikon Kategori -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Ikon Kategori (RemixIcon)
                    </label>
                    <div class="input-group mb-2">
                      <span class="input-group-text bg-light"><i :class="galleryForm.icon_class"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="galleryForm.icon_class" 
                        placeholder="Contoh: ri-trophy-fill"
                      />
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                      <button 
                        v-for="ic in iconPresets" 
                        :key="ic" 
                        type="button" 
                        class="btn btn-sm btn-light border py-1 px-2 fs-12"
                        :class="{ 'btn-primary text-white': galleryForm.icon_class === ic }"
                        @click="galleryForm.icon_class = ic"
                      >
                        <i :class="ic"></i>
                      </button>
                    </div>
                  </div>

                  <!-- Lokasi Kegiatan -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Lokasi / Venue Kegiatan
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="ri-map-pin-2-line"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="galleryForm.location" 
                        placeholder="Contoh: Panggung Utama Semarak Aliyah"
                      />
                    </div>
                  </div>

                  <!-- Tag / Sub-Momen -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Tag / Momen Acara
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="ri-price-tag-3-line"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="galleryForm.tag" 
                        placeholder="Contoh: Grand Awarding Ceremony"
                      />
                    </div>
                  </div>

                  <!-- Urutan Tampilan -->
                  <div class="col-12 col-md-6">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Urutan Slider (Sort Order)
                    </label>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model.number="galleryForm.sort_order" 
                      min="1"
                    />
                  </div>

                  <!-- Deskripsi Kilas Balik untuk Lightbox -->
                  <div class="col-12">
                    <label class="form-label fw-bold fs-13 text-dark">
                      Deskripsi Narasi Kilas Balik (Muncul di Lightbox Modal)
                    </label>
                    <textarea 
                      class="form-control" 
                      rows="3" 
                      v-model="galleryForm.description"
                      placeholder="Ceritakan momen seru dan kemeriahan pada foto ini..."
                    ></textarea>
                  </div>

                  <!-- Status Aktif Switch -->
                  <div class="col-12">
                    <div class="form-check form-switch fs-14">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        role="switch" 
                        id="modalActiveSwitch" 
                        v-model="galleryForm.is_active"
                      />
                      <label class="form-check-label fw-semibold" for="modalActiveSwitch">
                        Foto ini aktif dan langsung ditampilkan pada slider galeri
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-3" @click="closeModal">
                  Batal
                </button>
                <button 
                  type="submit" 
                  class="btn btn-warning text-dark fw-bold px-4 shadow-sm"
                  :disabled="isSavingGallery"
                >
                  <i v-if="isSavingGallery" class="ri-loader-4-line ri-spin me-1"></i>
                  <i v-else class="ri-check-line me-1"></i>
                  <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambahkan Foto' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL KONFIRMASI HAPUS FOTO -->
    <Teleport to="body">
      <div 
        v-if="isDeleteModalOpen" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1060;"
      >
        <div class="modal-dialog modal-dialog-centered modal-sm">
          <div class="modal-content border-0 shadow-lg rounded-3 text-center p-3">
            <div class="avatar-lg mx-auto my-2">
              <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-36 shadow-sm border border-danger-subtle">
                <i class="ri-delete-bin-line"></i>
              </span>
            </div>
            <h5 class="fw-bold text-body mt-2">Hapus Foto Galeri?</h5>
            <p class="text-muted fs-13 mb-3">
              Apakah Anda yakin ingin menghapus foto <strong>"{{ itemToDelete?.title }}"</strong>? Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="d-flex gap-2 justify-content-center">
              <button type="button" class="btn btn-light px-3" @click="cancelDelete" :disabled="isDeleting">
                Batal
              </button>
              <button type="button" class="btn btn-danger px-3 shadow-sm" @click="executeDelete" :disabled="isDeleting">
                <i v-if="isDeleting" class="ri-loader-4-line ri-spin me-1"></i>
                <span>Hapus Sekarang</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </VelzonLayout>
</template>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.gallery-admin-card {
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.gallery-admin-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.12) !important;
}

.gallery-admin-img {
  transition: transform 0.4s ease;
}

.gallery-admin-card:hover .gallery-admin-img {
  transform: scale(1.05);
}

.gallery-card-top-grad {
  background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(15, 23, 42, 0) 100%);
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
