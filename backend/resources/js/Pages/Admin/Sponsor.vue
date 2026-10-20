<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  sponsors: { type: Array, default: () => [] },
  settings: { type: Object, default: () => ({}) }
});

const { success, error } = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// 1. Settings Form State
const settingsForm = ref({
  sponsor_section_tag: props.settings.sponsor_section_tag || 'Mitra & Kolaborasi',
  sponsor_section_title: props.settings.sponsor_section_title || 'Didukung Oleh Mitra & Sponsor Terpercaya',
  sponsor_section_desc: props.settings.sponsor_section_desc || 'Apresiasi dan terima kasih kepada institusi perbankan syariah, penyedia telekomunikasi, media massa, serta mitra strategis yang turut bersinergi menyukseskan Semarak Aliyah XIV 2026.',
  sponsor_cta_text: props.settings.sponsor_cta_text || 'Peluang Sponsorship: Bergabunglah sebagai mitra resmi dan promosikan brand Anda kepada ribuan pelajar, santri & pendidik se-Kabupaten Jember.',
  sponsor_cta_wa: props.settings.sponsor_cta_wa || '6283850070767',
  sponsor_cta_btn_text: props.settings.sponsor_cta_btn_text || 'Ajukan Kerja Sama Sponsor',
  sponsor_is_active: props.settings.sponsor_is_active !== false,
});

const isSavingSettings = ref(false);

function saveSettings() {
  isSavingSettings.value = true;
  router.post('/admin/sponsor-settings', settingsForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingSettings.value = false;
      success('Pengaturan judul dan deskripsi sponsor berhasil disimpan!');
    },
    onError: (errs) => {
      isSavingSettings.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan pengaturan.');
    }
  });
}

// 2. Filter & View State
const searchQuery = ref('');
const filterMode = ref('all'); // 'all', 'logo_only', 'standard', 'active'
const viewMode = ref('grid'); // 'grid' | 'table'

const filteredSponsors = computed(() => {
  return props.sponsors.filter(s => {
    const q = searchQuery.value.toLowerCase().trim();
    const matchQuery = !q ||
      s.name?.toLowerCase().includes(q) ||
      s.short_name?.toLowerCase().includes(q) ||
      s.tier?.toLowerCase().includes(q) ||
      s.category?.toLowerCase().includes(q);

    if (!matchQuery) return false;

    if (filterMode.value === 'logo_only') {
      return s.only_logo || (!s.name && s.logo);
    }
    if (filterMode.value === 'standard') {
      return !s.only_logo && s.name;
    }
    if (filterMode.value === 'active') {
      return s.is_active;
    }
    return true;
  });
});

// Stats
const totalCount = computed(() => props.sponsors.length);
const activeCount = computed(() => props.sponsors.filter(s => s.is_active).length);
const logoOnlyCount = computed(() => props.sponsors.filter(s => s.only_logo).length);

// 3. Modal Form State (Tambah / Edit Sponsor)
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSavingSponsor = ref(false);
const logoPreview = ref(null);
const fileInputRef = ref(null);

const sponsorForm = ref({
  name: '',
  short_name: '',
  tier: 'Mitra Resmi',
  category: '',
  only_logo: false,
  icon: 'ri-shake-hands-line',
  color: '#6366f1',
  bg_color: 'rgba(99, 102, 241, 0.1)',
  border_color: 'rgba(99, 102, 241, 0.3)',
  website_url: '',
  sort_order: 1,
  is_active: true,
  logo_url: '',
  logo_file: null,
});

const colorPresets = [
  { name: 'Teal BSI', color: '#00a39d', bg: 'rgba(0, 163, 157, 0.1)', border: 'rgba(0, 163, 157, 0.3)' },
  { name: 'Merah Bank Jatim', color: '#e11d48', bg: 'rgba(225, 29, 72, 0.1)', border: 'rgba(225, 29, 72, 0.3)' },
  { name: 'Merah Telkomsel', color: '#ef4444', bg: 'rgba(239, 68, 68, 0.1)', border: 'rgba(239, 68, 68, 0.3)' },
  { name: 'Sky Cleo', color: '#0ea5e9', bg: 'rgba(14, 165, 233, 0.1)', border: 'rgba(14, 165, 233, 0.3)' },
  { name: 'Kuning Indofood', color: '#f59e0b', bg: 'rgba(245, 158, 11, 0.1)', border: 'rgba(245, 158, 11, 0.3)' },
  { name: 'Ungu ESI', color: '#8b5cf6', bg: 'rgba(139, 92, 246, 0.1)', border: 'rgba(139, 92, 246, 0.3)' },
  { name: 'Hijau Kemenag', color: '#10b981', bg: 'rgba(16, 185, 129, 0.1)', border: 'rgba(16, 185, 129, 0.3)' },
  { name: 'Pink Percetakan', color: '#ec4899', bg: 'rgba(236, 72, 153, 0.1)', border: 'rgba(236, 72, 153, 0.3)' },
  { name: 'Indigo Media', color: '#6366f1', bg: 'rgba(99, 102, 241, 0.1)', border: 'rgba(99, 102, 241, 0.3)' },
  { name: 'Toska Kahf', color: '#14b8a6', bg: 'rgba(20, 184, 166, 0.1)', border: 'rgba(20, 184, 166, 0.3)' },
  { name: 'Rose Wardah', color: '#f43f5e', bg: 'rgba(244, 63, 94, 0.1)', border: 'rgba(244, 63, 94, 0.3)' },
  { name: 'Emerald Yayasan', color: '#059669', bg: 'rgba(5, 150, 105, 0.1)', border: 'rgba(5, 150, 105, 0.3)' },
];

function selectColorPreset(p) {
  sponsorForm.value.color = p.color;
  sponsorForm.value.bg_color = p.bg;
  sponsorForm.value.border_color = p.border;
}

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  logoPreview.value = null;
  sponsorForm.value = {
    name: '',
    short_name: '',
    tier: 'Mitra Resmi',
    category: '',
    only_logo: false,
    icon: 'ri-shake-hands-line',
    color: '#6366f1',
    bg_color: 'rgba(99, 102, 241, 0.1)',
    border_color: 'rgba(99, 102, 241, 0.3)',
    website_url: '',
    sort_order: props.sponsors.length + 1,
    is_active: true,
    logo_url: '',
    logo_file: null,
  };
  isModalOpen.value = true;
}

function openEditModal(sponsor) {
  isEditing.value = true;
  editingId.value = sponsor.id;
  logoPreview.value = sponsor.logo || null;
  sponsorForm.value = {
    name: sponsor.name || '',
    short_name: sponsor.short_name || '',
    tier: sponsor.tier || 'Mitra Resmi',
    category: sponsor.category || '',
    only_logo: Boolean(sponsor.only_logo),
    icon: sponsor.icon || 'ri-shake-hands-line',
    color: sponsor.color || '#6366f1',
    bg_color: sponsor.bg_color || 'rgba(99, 102, 241, 0.1)',
    border_color: sponsor.border_color || 'rgba(99, 102, 241, 0.3)',
    website_url: sponsor.website_url || '',
    sort_order: sponsor.sort_order || 1,
    is_active: Boolean(sponsor.is_active),
    logo_url: sponsor.logo || '',
    logo_file: null,
  };
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  logoPreview.value = null;
}

function handleFileChange(e) {
  const file = e.target.files[0];
  if (!file) return;
  sponsorForm.value.logo_file = file;

  const reader = new FileReader();
  reader.onload = (event) => {
    logoPreview.value = event.target.result;
  };
  reader.readAsDataURL(file);
}

function removeLogo() {
  sponsorForm.value.logo_file = null;
  sponsorForm.value.logo_url = '';
  logoPreview.value = null;
  if (fileInputRef.value) fileInputRef.value.value = '';
}

function submitSponsor() {
  if (!sponsorForm.value.name && !sponsorForm.value.only_logo) {
    error('Nama sponsor wajib diisi jika bukan mode Hanya Logo.');
    return;
  }

  isSavingSponsor.value = true;
  const formData = new FormData();
  formData.append('name', sponsorForm.value.name || 'Sponsor');
  formData.append('short_name', sponsorForm.value.short_name || '');
  formData.append('tier', sponsorForm.value.tier || 'Mitra Resmi');
  formData.append('category', sponsorForm.value.category || '');
  formData.append('only_logo', sponsorForm.value.only_logo ? '1' : '0');
  formData.append('icon', sponsorForm.value.icon || 'ri-shake-hands-line');
  formData.append('color', sponsorForm.value.color || '#6366f1');
  formData.append('bg_color', sponsorForm.value.bg_color || 'rgba(99, 102, 241, 0.1)');
  formData.append('border_color', sponsorForm.value.border_color || 'rgba(99, 102, 241, 0.3)');
  formData.append('website_url', sponsorForm.value.website_url || '');
  formData.append('sort_order', sponsorForm.value.sort_order || '1');
  formData.append('is_active', sponsorForm.value.is_active ? '1' : '0');

  if (sponsorForm.value.logo_file) {
    formData.append('logo_file', sponsorForm.value.logo_file);
  } else if (sponsorForm.value.logo_url) {
    formData.append('logo_url', sponsorForm.value.logo_url);
  }

  const endpoint = isEditing.value
    ? `/admin/sponsor/${editingId.value}`
    : '/admin/sponsor';

  router.post(endpoint, formData, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isSavingSponsor.value = false;
      closeModal();
      success(isEditing.value ? 'Data mitra sponsor berhasil diperbarui!' : 'Mitra sponsor baru berhasil ditambahkan!');
    },
    onError: (errs) => {
      isSavingSponsor.value = false;
      error(Object.values(errs)[0] || 'Terjadi kesalahan saat menyimpan sponsor.');
    }
  });
}

function toggleActive(sponsor) {
  router.post(`/admin/sponsor-toggle/${sponsor.id}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Status mitra "${sponsor.name}" berhasil diubah.`);
    }
  });
}

async function deleteSponsor(sponsor) {
  const ok = await confirmDialog({
    title: 'Hapus Mitra Sponsor?',
    message: `Apakah Anda yakin ingin menghapus mitra sponsor "${sponsor.name}"? Data dan logo sponsor akan dihapus permanen.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Sponsor',
    icon: 'ri-delete-bin-line',
  });

  if (!ok) return;

  router.delete(`/admin/sponsor/${sponsor.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      success(`Mitra sponsor "${sponsor.name}" telah dihapus.`);
    }
  });
}
</script>

<template>
  <VelzonLayout>
    <Head title="Manajemen Mitra & Sponsor Resmi - Admin Semarak" />

    <div class="page-content py-4 px-3 px-md-4">
      <div class="container-fluid">
        <!-- Top Breadcrumb & Title -->
        <div class="row mb-4 align-items-center">
          <div class="col-12 col-md-6 mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-primary-subtle text-primary fs-11 px-2 py-1 rounded">Konten Beranda</span>
              <span class="text-muted fs-12">•</span>
              <span class="text-muted fs-12">Slider Infinite Loop</span>
            </div>
            <h1 class="h3 fw-bold mb-0 text-dark d-flex align-items-center gap-2">
              <i class="ri-shake-hands-line text-primary"></i>
              <span>Manajemen Mitra & Sponsor</span>
            </h1>
            <p class="text-muted mb-0 fs-13">
              Kelola teks judul, deskripsi, serta elemen logo & mitra sponsor yang berjalan otomatis di beranda publik.
            </p>
          </div>

          <div class="col-12 col-md-6 text-md-end d-flex flex-wrap gap-2 justify-content-md-end">
            <a href="/#sponsor" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3">
              <i class="ri-external-link-line"></i>
              <span>Lihat di Website</span>
            </a>
            <button 
              type="button" 
              class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 rounded-pill px-3 shadow-sm"
              @click="openAddModal"
            >
              <i class="ri-add-line fs-14"></i>
              <span>Tambah Mitra Sponsor</span>
            </button>
          </div>
        </div>

        <!-- Metric Summary Cards -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
              <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-20" style="width: 48px; height: 48px;">
                  <i class="ri-building-4-line"></i>
                </div>
                <div>
                  <span class="text-muted fs-12 d-block fw-medium">Total Mitra Sponsor</span>
                  <h4 class="mb-0 fw-bold text-dark">{{ totalCount }} Entitas</h4>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
              <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center fs-20" style="width: 48px; height: 48px;">
                  <i class="ri-checkbox-circle-line"></i>
                </div>
                <div>
                  <span class="text-muted fs-12 d-block fw-medium">Tampil di Slider</span>
                  <h4 class="mb-0 fw-bold text-success">{{ activeCount }} Aktif</h4>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
              <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-20" style="width: 48px; height: 48px;">
                  <i class="ri-image-line"></i>
                </div>
                <div>
                  <span class="text-muted fs-12 d-block fw-medium">Mode Hanya Logo</span>
                  <h4 class="mb-0 fw-bold text-dark">{{ logoOnlyCount }} Logo-Only</h4>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 rounded-3">
              <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="avatar-sm rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center fs-20" style="width: 48px; height: 48px;">
                  <i class="ri-toggle-line"></i>
                </div>
                <div>
                  <span class="text-muted fs-12 d-block fw-medium">Status Seksi Beranda</span>
                  <h4 class="mb-0 fw-bold" :class="settingsForm.sponsor_is_active ? 'text-info' : 'text-danger'">
                    {{ settingsForm.sponsor_is_active ? 'Aktif Tayang' : 'Disembunyikan' }}
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Section 1: Pengaturan Teks Judul & Deskripsi Elemen Sponsor -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <h5 class="card-title mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="ri-edit-2-line text-warning"></i>
                <span>Sesuaikan Teks Judul & Deskripsi Elemen Sponsor</span>
              </h5>
              <p class="text-muted fs-12 mb-0">
                Ubah kalimat judul, deskripsi apresiasi, serta teks ajakan kemitraan sponsor yang muncul di atas slider.
              </p>
            </div>
            <div class="form-check form-switch form-switch-md">
              <input 
                class="form-check-input cursor-pointer" 
                type="checkbox" 
                id="toggleSectionActive"
                v-model="settingsForm.sponsor_is_active"
              />
              <label class="form-check-label fs-13 fw-semibold text-dark" for="toggleSectionActive">
                {{ settingsForm.sponsor_is_active ? 'Seksi Sponsor Aktif' : 'Seksi Sponsor Nonaktif' }}
              </label>
            </div>
          </div>

          <div class="card-body p-4">
            <form @submit.prevent="saveSettings">
              <div class="row g-3">
                <!-- Tag / Sub-heading -->
                <div class="col-12 col-md-4">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Sub-Judul Badge (Tag)
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-price-tag-3-line"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      v-model="settingsForm.sponsor_section_tag"
                      placeholder="Contoh: Mitra & Kolaborasi" 
                      required
                    />
                  </div>
                  <small class="text-muted fs-11">Label kecil berwarna di atas judul utama.</small>
                </div>

                <!-- Judul Utama -->
                <div class="col-12 col-md-8">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Judul Utama Elemen Sponsor <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-heading"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 fw-semibold" 
                      v-model="settingsForm.sponsor_section_title"
                      placeholder="Didukung Oleh Mitra & Sponsor Terpercaya" 
                      required
                    />
                  </div>
                  <small class="text-muted fs-11">Judul besar yang dibaca oleh pengunjung website.</small>
                </div>

                <!-- Deskripsi / Teks Apresiasi -->
                <div class="col-12">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Deskripsi / Kalimat Apresiasi Sponsor <span class="text-danger">*</span>
                  </label>
                  <textarea 
                    class="form-control" 
                    rows="3" 
                    v-model="settingsForm.sponsor_section_desc"
                    placeholder="Tuliskan ucapan terima kasih dan penjelasan sinergi mitra sponsor..."
                    required
                  ></textarea>
                  <small class="text-muted fs-11">
                    Paragraf pengantar yang menjelaskan sinergi antara panitia Semarak Aliyah XIV dengan mitra terpercaya.
                  </small>
                </div>

                <!-- Peluang Sponsor Teks & WA Action -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Teks Peluang Sponsorship (Bilah Bawah Slider)
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="settingsForm.sponsor_cta_text"
                    placeholder="Peluang Sponsorship: Bergabunglah sebagai mitra resmi..." 
                  />
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Nomor WhatsApp Kemitraan
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-success"><i class="ri-whatsapp-line"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 font-mono" 
                      v-model="settingsForm.sponsor_cta_wa"
                      placeholder="6283850070767" 
                    />
                  </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Label Tombol CTA
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="settingsForm.sponsor_cta_btn_text"
                    placeholder="Ajukan Kerja Sama Sponsor" 
                  />
                </div>
              </div>

              <!-- Live Preview Card -->
              <div class="mt-4 p-3 rounded-3 bg-light border border-dashed">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge bg-dark text-white fs-10 px-2 py-1">Pratinjau Tampilan Teks</span>
                  <span class="text-muted fs-11">Tampilan teks yang akan dilihat pengunjung di beranda</span>
                </div>
                <div class="text-center py-2" style="max-width: 760px; margin: 0 auto;">
                  <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 mb-2 fs-12 fw-bold">
                    <i class="ri-shake-hands-line me-1"></i>{{ settingsForm.sponsor_section_tag || 'Mitra & Kolaborasi' }}
                  </span>
                  <h3 class="fw-bolder text-dark mb-2" style="font-size: 1.45rem;">
                    {{ settingsForm.sponsor_section_title || 'Didukung Oleh Mitra & Sponsor Terpercaya' }}
                  </h3>
                  <p class="text-muted fs-13 mb-0" style="line-height: 1.6;">
                    {{ settingsForm.sponsor_section_desc || 'Deskripsi apresiasi sponsor...' }}
                  </p>
                </div>
              </div>

              <div class="mt-3 text-end">
                <button 
                  type="submit" 
                  class="btn btn-success d-inline-flex align-items-center gap-2 px-4 rounded-pill shadow-sm"
                  :disabled="isSavingSettings"
                >
                  <i :class="isSavingSettings ? 'ri-loader-4-line spin' : 'ri-save-3-line'"></i>
                  <span>{{ isSavingSettings ? 'Menyimpan...' : 'Simpan Teks & Pengaturan' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Section 2: Daftar Entitas Mitra & Sponsor -->
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
              <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="ri-list-check-2 text-primary"></i>
                <span>Daftar Logo & Mitra Sponsor</span>
                <span class="badge bg-secondary-subtle text-secondary fs-11 rounded-pill ms-1">{{ filteredSponsors.length }}</span>
              </h5>
            </div>

            <!-- Filter Controls -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Search Input -->
              <div class="position-relative" style="min-width: 220px;">
                <input 
                  type="text" 
                  class="form-control form-control-sm pe-4" 
                  placeholder="Cari sponsor, tier..." 
                  v-model="searchQuery"
                />
                <i class="ri-search-line position-absolute end-0 top-50 translate-middle-y me-2 text-muted fs-13"></i>
              </div>

              <!-- Filter Mode -->
              <select class="form-select form-select-sm" style="width: auto;" v-model="filterMode">
                <option value="all">Semua Mode</option>
                <option value="logo_only">Hanya Logo (Logo-Only)</option>
                <option value="standard">Mode Standar (Icon + Teks)</option>
                <option value="active">Khusus yang Aktif</option>
              </select>

              <!-- View Mode Toggle -->
              <div class="btn-group btn-group-sm">
                <button 
                  type="button" 
                  class="btn" 
                  :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'"
                  @click="viewMode = 'grid'"
                  title="Tampilan Grid Card"
                >
                  <i class="ri-grid-fill"></i>
                </button>
                <button 
                  type="button" 
                  class="btn" 
                  :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary'"
                  @click="viewMode = 'table'"
                  title="Tampilan Tabel"
                >
                  <i class="ri-table-line"></i>
                </button>
              </div>

              <button 
                type="button" 
                class="btn btn-primary btn-sm rounded-pill d-inline-flex align-items-center gap-1 px-3"
                @click="openAddModal"
              >
                <i class="ri-add-line"></i>
                <span>Tambah</span>
              </button>
            </div>
          </div>

          <div class="card-body p-4">
            <!-- Alert Info: Logo-Only Automatic Rule -->
            <div class="alert alert-info border-0 rounded-3 d-flex align-items-start gap-3 p-3 mb-4 shadow-xs">
              <i class="ri-information-fill fs-20 text-info mt-1"></i>
              <div class="fs-13">
                <strong>Aturan Mode Hanya Logo:</strong>
                Jika opsi <em>"Hanya Tampilkan Logo (Logo-Only)"</em> diaktifkan pada sponsor, maka <strong>icon vektor, nama sponsor, dan deskripsi/kategori akan otomatis disembunyikan</strong> di slider publik, sehingga logo brand tampil eksklusif dan terpusat di dalam kartu sponsor.
              </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-if="filteredSponsors.length === 0" class="text-center py-5">
              <div class="avatar-lg rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                <i class="ri-inbox-line fs-28"></i>
              </div>
              <h6 class="fw-bold text-dark mb-1">Tidak Ada Mitra Sponsor Ditemukan</h6>
              <p class="text-muted fs-13 mb-3">Tidak ada data yang sesuai dengan pencarian atau filter yang dipilih.</p>
              <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" @click="openAddModal">
                <i class="ri-add-line me-1"></i> Tambah Mitra Sekarang
              </button>
            </div>

            <!-- 1. GRID VIEW -->
            <div v-else-if="viewMode === 'grid'" class="row g-3">
              <div 
                v-for="(sp, idx) in filteredSponsors" 
                :key="sp.id"
                class="col-12 col-sm-6 col-lg-4 col-xl-3"
              >
                <div 
                  class="card h-100 border transition-all rounded-3 position-relative overflow-hidden"
                  :style="{ 
                    borderColor: sp.is_active ? (sp.border_color || '#e2e8f0') : '#cbd5e1',
                    opacity: sp.is_active ? 1 : 0.65
                  }"
                  style="box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);"
                >
                  <!-- Card Header Bar -->
                  <div class="p-3 border-bottom d-flex align-items-center justify-content-between gap-2 bg-light">
                    <span 
                      class="badge rounded-pill fw-bold fs-10 px-2 py-1 text-uppercase"
                      :style="{ color: sp.color, backgroundColor: sp.bg_color, border: `1px solid ${sp.border_color}` }"
                    >
                      {{ sp.tier || 'Mitra' }}
                    </span>

                    <div class="d-flex align-items-center gap-1">
                      <span 
                        v-if="sp.only_logo || (!sp.name && sp.logo)" 
                        class="badge bg-warning-subtle text-warning fs-10 rounded-pill px-2 py-1"
                        title="Mode Hanya Logo: Icon, Nama, dan Kategori Otomatis Disembunyikan"
                      >
                        <i class="ri-image-line me-1"></i>Hanya Logo
                      </span>
                      <span 
                        class="badge rounded-pill fs-10 px-2 py-1"
                        :class="sp.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                      >
                        {{ sp.is_active ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </div>
                  </div>

                  <!-- Card Body Preview -->
                  <div class="card-body p-3 text-center d-flex flex-column justify-content-center" style="min-height: 120px;">
                    <!-- A. TAMPILAN MODE HANYA LOGO (Icon, Nama, Deskripsi Disembunyikan) -->
                    <div v-if="sp.only_logo || (!sp.name && sp.logo)" class="py-1">
                      <div class="d-flex align-items-center justify-content-center p-1 rounded-2 bg-white border" style="min-height: 80px; width: 100%; overflow: hidden;">
                        <img 
                          v-if="sp.logo" 
                          :src="sp.logo" 
                          :alt="sp.name || 'Logo'" 
                          style="max-height: 76px; width: 100%; object-fit: contain;" 
                        />
                        <div v-else class="text-muted fs-12 d-flex align-items-center gap-2">
                          <i class="ri-image-line fs-20 text-warning"></i>
                          <span>Belum Ada File Logo</span>
                        </div>
                      </div>
                      <div class="mt-2 text-muted fs-11">
                        <i class="ri-eye-off-line me-1 text-muted"></i>Icon, Nama & Deskripsi Disembunyikan
                      </div>
                    </div>

                    <!-- B. TAMPILAN MODE STANDAR (Icon, Nama, Kategori) -->
                    <div v-else class="text-start d-flex align-items-center gap-3">
                      <div 
                        class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                        :style="{ backgroundColor: sp.bg_color, color: sp.color, border: `1px solid ${sp.border_color}` }"
                        style="width: 50px; height: 50px; font-size: 1.5rem;"
                      >
                        <img v-if="sp.logo" :src="sp.logo" :alt="sp.name" style="max-width: 32px; max-height: 32px; object-fit: contain;" />
                        <i v-else :class="sp.icon || 'ri-shake-hands-line'"></i>
                      </div>

                      <div class="overflow-hidden">
                        <h6 class="fw-bold text-dark mb-0 text-truncate" :title="sp.name">{{ sp.name }}</h6>
                        <small class="text-muted d-block text-truncate fs-12">{{ sp.category || 'Sponsor Pendukung' }}</small>
                        <span class="badge bg-light text-muted border fs-10 mt-1 font-mono">
                          {{ sp.short_name || sp.name }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Card Footer Actions -->
                  <div class="card-footer bg-white border-top p-2 d-flex align-items-center justify-content-between">
                    <span class="text-muted fs-11 font-mono">Urutan: #{{ sp.sort_order }}</span>

                    <div class="d-flex align-items-center gap-1">
                      <button 
                        type="button" 
                        class="btn btn-sm p-1 text-muted hover-primary"
                        :class="sp.is_active ? 'text-success' : 'text-muted'"
                        @click="toggleActive(sp)"
                        :title="sp.is_active ? 'Nonaktifkan Sponsor' : 'Aktifkan Sponsor'"
                      >
                        <i :class="sp.is_active ? 'ri-toggle-fill fs-18' : 'ri-toggle-line fs-18'"></i>
                      </button>

                      <button 
                        type="button" 
                        class="btn btn-sm btn-light p-1 px-2 text-primary"
                        @click="openEditModal(sp)"
                        title="Edit Data Sponsor"
                      >
                        <i class="ri-edit-line fs-14"></i>
                      </button>

                      <button 
                        type="button" 
                        class="btn btn-sm btn-light p-1 px-2 text-danger"
                        @click="deleteSponsor(sp)"
                        title="Hapus Sponsor"
                      >
                        <i class="ri-delete-bin-line fs-14"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. TABLE VIEW -->
            <div v-else class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light fs-12 text-uppercase">
                  <tr>
                    <th style="width: 50px;">Urut</th>
                    <th style="width: 70px;">Visual</th>
                    <th>Nama Sponsor</th>
                    <th>Tingkatan (Tier)</th>
                    <th>Kategori / Peran</th>
                    <th>Mode Tampilan</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 110px;" class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody class="fs-13">
                  <tr v-for="sp in filteredSponsors" :key="sp.id">
                    <td class="font-mono text-muted">#{{ sp.sort_order }}</td>
                    <td>
                      <div 
                        class="rounded-2 d-flex align-items-center justify-content-center p-1 bg-white border"
                        style="width: 44px; height: 44px;"
                      >
                        <img 
                          v-if="sp.logo" 
                          :src="sp.logo" 
                          :alt="sp.name" 
                          style="max-width: 38px; max-height: 38px; object-fit: contain;" 
                        />
                        <i v-else :class="sp.icon || 'ri-shake-hands-line'" :style="{ color: sp.color }" class="fs-20"></i>
                      </div>
                    </td>
                    <td>
                      <div class="fw-bold text-dark">{{ sp.name }}</div>
                      <small class="text-muted font-mono fs-11">{{ sp.short_name }}</small>
                    </td>
                    <td>
                      <span 
                        class="badge rounded-pill fw-semibold fs-11 px-2 py-1"
                        :style="{ color: sp.color, backgroundColor: sp.bg_color, border: `1px solid ${sp.border_color}` }"
                      >
                        {{ sp.tier }}
                      </span>
                    </td>
                    <td class="text-muted">{{ sp.category || '-' }}</td>
                    <td>
                      <span 
                        v-if="sp.only_logo || (!sp.name && sp.logo)" 
                        class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1 fs-11"
                      >
                        <i class="ri-image-line me-1"></i>Hanya Logo
                      </span>
                      <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 fs-11">
                        Standar (Icon + Teks)
                      </span>
                    </td>
                    <td>
                      <span 
                        class="badge rounded-pill px-2 py-1 fs-11"
                        :class="sp.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                      >
                        {{ sp.is_active ? 'Aktif' : 'Nonaktif' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-1">
                        <button 
                          type="button" 
                          class="btn btn-sm btn-light p-1 px-2 text-primary" 
                          @click="openEditModal(sp)"
                          title="Edit"
                        >
                          <i class="ri-edit-line"></i>
                        </button>
                        <button 
                          type="button" 
                          class="btn btn-sm btn-light p-1 px-2 text-danger" 
                          @click="deleteSponsor(sp)"
                          title="Hapus"
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

    <!-- =========================================================================
         MODAL TAMBAH / EDIT MITRA SPONSOR
         ========================================================================= -->
    <div 
      v-if="isModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1055;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-white border-bottom py-3 px-4">
            <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
              <i :class="isEditing ? 'ri-edit-line text-warning' : 'ri-add-circle-line text-primary'"></i>
              <span>{{ isEditing ? 'Edit Mitra Sponsor' : 'Tambah Mitra Sponsor Baru' }}</span>
            </h5>
            <button type="button" class="btn-close" @click="closeModal" aria-label="Tutup"></button>
          </div>

          <form @submit.prevent="submitSponsor">
            <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
              <!-- OPSI SPESIAL: HANYA TAMPILKAN LOGO (LOGO-ONLY MODE) -->
              <div 
                class="card border mb-4 rounded-3 transition-all"
                :class="sponsorForm.only_logo ? 'border-warning bg-warning-subtle' : 'border-light bg-light'"
              >
                <div class="card-body p-3">
                  <div class="form-check form-switch form-switch-md mb-2">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="modalOnlyLogoToggle"
                      v-model="sponsorForm.only_logo"
                    />
                    <label class="form-check-label fw-bold text-dark fs-14 cursor-pointer" for="modalOnlyLogoToggle">
                      <i class="ri-image-2-line text-warning me-1"></i>
                      Hanya Tampilkan Logo (Sembunyikan Icon, Nama, dan Kategori)
                    </label>
                  </div>
                  <p class="text-muted fs-12 mb-0" style="line-height: 1.5;">
                    💡 <strong>Penjelasan Fitur:</strong> Bila opsi ini dicentang, kartu sponsor di slider publik akan otomatis 
                    <strong>menyembunyikan icon, nama sponsor, dan deskripsi</strong>, sehingga logo brand sponsor tampil bersih, eksklusif, dan memukau.
                  </p>
                </div>
              </div>

              <!-- Upload File Logo -->
              <div class="mb-4">
                <label class="form-label fs-13 fw-semibold text-dark d-flex align-items-center justify-content-between">
                  <span>Upload Gambar Logo Sponsor</span>
                  <span class="text-muted fs-11">Format: PNG, JPG, WEBP, SVG (Maks 3MB)</span>
                </label>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <!-- Preview Box -->
                  <div 
                    class="rounded-3 border border-dashed d-flex align-items-center justify-content-center p-2 bg-white position-relative"
                    style="width: 100px; height: 70px;"
                  >
                    <img 
                      v-if="logoPreview" 
                      :src="logoPreview" 
                      alt="Pratinjau Logo" 
                      style="max-width: 100%; max-height: 100%; object-fit: contain;" 
                    />
                    <div v-else class="text-center text-muted fs-10">
                      <i class="ri-image-add-line fs-20 d-block"></i>
                      <span>No Logo</span>
                    </div>

                    <button 
                      v-if="logoPreview" 
                      type="button" 
                      class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 translate-middle p-0 d-flex align-items-center justify-content-center shadow-sm"
                      style="width: 20px; height: 20px;"
                      @click="removeLogo"
                      title="Hapus Logo"
                    >
                      <i class="ri-close-line fs-12"></i>
                    </button>
                  </div>

                  <!-- File Input -->
                  <div class="flex-grow-1">
                    <input 
                      type="file" 
                      class="form-control" 
                      accept="image/*"
                      ref="fileInputRef"
                      @change="handleFileChange"
                    />
                    <div class="input-group input-group-sm mt-2">
                      <span class="input-group-text bg-light border-end-0 text-muted fs-11">Atau URL Gambar</span>
                      <input 
                        type="text" 
                        class="form-control border-start-0 fs-12" 
                        v-model="sponsorForm.logo_url"
                        placeholder="Contoh: /assets/img/logo.png atau https://..." 
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div class="row g-3">
                <!-- Nama Sponsor -->
                <div class="col-12 col-md-8">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Nama Mitra / Perusahaan Sponsor <span class="text-danger" v-if="!sponsorForm.only_logo">*</span>
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="sponsorForm.name"
                    placeholder="Contoh: Bank Syariah Indonesia"
                    :required="!sponsorForm.only_logo"
                  />
                  <small class="text-muted fs-11" v-if="sponsorForm.only_logo">
                    (Dalam mode Hanya Logo, nama ini tetap disimpan sebagai label identifikasi admin & alt image).
                  </small>
                </div>

                <!-- Nama Singkat / Inisial -->
                <div class="col-12 col-md-4">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Nama Singkat / Inisial
                  </label>
                  <input 
                    type="text" 
                    class="form-control font-mono" 
                    v-model="sponsorForm.short_name"
                    placeholder="Contoh: BSI"
                  />
                </div>

                <!-- Tingkatan (Tier) -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Tingkatan Sponsor (Tier Badge)
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="sponsorForm.tier"
                    placeholder="Official Bank, Media Partner, Gold Sponsor, dll"
                  />
                </div>

                <!-- Kategori / Peran -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Kategori / Peran Sinergi
                  </label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="sponsorForm.category"
                    placeholder="Contoh: Mitra Perbankan Utama"
                  />
                </div>

                <!-- Icon Vektor RemixIcon -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Vektor Icon (RemixIcon)
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" :style="{ color: sponsorForm.color }">
                      <i :class="sponsorForm.icon || 'ri-shake-hands-line'"></i>
                    </span>
                    <input 
                      type="text" 
                      class="form-control border-start-0 font-mono" 
                      v-model="sponsorForm.icon"
                      placeholder="ri-bank-line"
                    />
                  </div>
                  <small class="text-muted fs-11">Digunakan sebagai visual bila tidak ada gambar logo.</small>
                </div>

                <!-- Website / Tautan URL -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Website Resmi / Tautan Profil
                  </label>
                  <input 
                    type="url" 
                    class="form-control" 
                    v-model="sponsorForm.website_url"
                    placeholder="https://..."
                  />
                </div>

                <!-- Pilihan Warna Brand (Palette) -->
                <div class="col-12">
                  <label class="form-label fs-13 fw-semibold text-dark d-flex align-items-center justify-content-between">
                    <span>Aksen Warna Brand Sponsor</span>
                    <span class="text-muted fs-11">Pilih preset atau masukkan kode HEX</span>
                  </label>

                  <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <button 
                      v-for="(p, pIdx) in colorPresets" 
                      :key="pIdx"
                      type="button" 
                      class="btn btn-sm rounded-pill p-1 px-2 border fs-11 d-flex align-items-center gap-1"
                      :class="sponsorForm.color === p.color ? 'border-dark shadow-sm' : ''"
                      @click="selectColorPreset(p)"
                      :style="{ backgroundColor: p.bg, color: p.color, borderColor: p.border }"
                    >
                      <span class="rounded-circle" :style="{ backgroundColor: p.color, width: '10px', height: '10px' }"></span>
                      <span>{{ p.name }}</span>
                    </button>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <input 
                      type="color" 
                      class="form-control form-control-color cursor-pointer p-1" 
                      v-model="sponsorForm.color"
                      @input="sponsorForm.bg_color = `${sponsorForm.color}1a`; sponsorForm.border_color = `${sponsorForm.color}4d`"
                      title="Pilih Warna Kustom"
                    />
                    <input 
                      type="text" 
                      class="form-control font-mono fs-12" 
                      v-model="sponsorForm.color"
                      placeholder="#6366f1"
                    />
                  </div>
                </div>

                <!-- Urutan & Status Aktif -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-13 fw-semibold text-dark">
                    Urutan Slider (Sort Order)
                  </label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model.number="sponsorForm.sort_order"
                    min="1"
                  />
                </div>

                <div class="col-12 col-md-6 d-flex align-items-center pt-md-4">
                  <div class="form-check form-switch form-switch-md">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="modalIsActiveToggle"
                      v-model="sponsorForm.is_active"
                    />
                    <label class="form-check-label fw-bold text-dark fs-13 cursor-pointer" for="modalIsActiveToggle">
                      Tampilkan di Website Beranda
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light border-top py-3 px-4 d-flex align-items-center justify-content-between">
              <button type="button" class="btn btn-outline-secondary rounded-pill px-3" @click="closeModal">
                Batal
              </button>

              <button 
                type="submit" 
                class="btn btn-primary rounded-pill px-4 shadow-sm d-inline-flex align-items-center gap-2"
                :disabled="isSavingSponsor"
              >
                <i :class="isSavingSponsor ? 'ri-loader-4-line spin' : 'ri-check-line'"></i>
                <span>{{ isSavingSponsor ? 'Menyimpan...' : (isEditing ? 'Perbarui Sponsor' : 'Simpan Sponsor Baru') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.cursor-pointer {
  cursor: pointer;
}
</style>
