<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
});

const { success, error } = useToast();

const form = useForm({
  school_name: props.settings.school_name || 'MA Unggulan Wahid Hasyim Balung',
  school_short_name: props.settings.school_short_name || 'MA Wahid Hasyim',
  event_edition: props.settings.event_edition || 'XIV',
  event_year: props.settings.event_year || '2026',
  event_name: props.settings.event_name || 'SEMARAK ALIYAH XIV 2026',
  school_tagline: props.settings.school_tagline || '',
  school_address: props.settings.school_address || '',
  school_city: props.settings.school_city || 'Jember',
  school_province: props.settings.school_province || 'Jawa Timur',
  school_postal_code: props.settings.school_postal_code || '68161',
  contact_phone: props.settings.contact_phone || '',
  contact_wa: props.settings.contact_wa || '',
  contact_email: props.settings.contact_email || '',
  service_hours: props.settings.service_hours || '',
  maps_embed_url: props.settings.maps_embed_url || '',
  social_facebook: props.settings.social_facebook || '',
  social_instagram: props.settings.social_instagram || '',
  social_youtube: props.settings.social_youtube || '',
  social_tiktok: props.settings.social_tiktok || '',
  school_website: props.settings.school_website || '',
});

const quickEditions = ['XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX'];
const quickYears = ['2025', '2026', '2027', '2028', '2029', '2030'];

function setQuickEdition(ed) {
  form.event_edition = ed;
  syncEventName();
}

function setQuickYear(yr) {
  form.event_year = yr;
  syncEventName();
}

function syncEventName() {
  const ed = (form.event_edition || '').trim().toUpperCase();
  const yr = (form.event_year || '').trim();
  form.event_name = `SEMARAK ALIYAH ${ed} ${yr}`.trim();
}

function submitForm() {
  form.post('/admin/pengaturan/profil-sekolah', {
    preserveScroll: true,
    onSuccess: () => {
      success('Konfigurasi profil sekolah dan kontak resmi berhasil diperbarui.', 'Tersimpan');
    },
    onError: (err) => {
      error('Mohon periksa kembali kolom isian formulir.', 'Gagal Menyimpan');
    }
  });
}
</script>

<template>
  <Head title="Konfigurasi Profil Sekolah | Pengaturan Admin" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-sm-0 fw-bold d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-building-line text-primary fs-22"></i>
                Konfigurasi Profil Sekolah & Lembaga
              </h4>
              <p class="text-muted fs-12 mb-0 mt-1">
                Kelola identitas resmi madrasah, alamat operasional, jam pelayanan, kontak darurat, dan jejaring media sosial.
              </p>
            </div>
            <div class="page-title-right mt-2 mt-sm-0">
              <ol class="breadcrumb m-0 fs-12">
                <li class="breadcrumb-item"><Link href="/admin">Dashboard</Link></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active text-primary">Profil Sekolah</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Submenu Tabs -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
              <ul class="nav nav-pills gap-2 flex-grow-1" role="tablist">
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/profil-sekolah"
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill fw-semibold fs-13"
                  >
                    <i class="ri-building-line"></i>
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
              </ul>

              <div class="d-flex align-items-center gap-2">
                <a href="/" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                  <i class="ri-external-link-line me-1"></i> Lihat Portal Publik
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submitForm">
        <div class="row g-4">
          <!-- Left Column: Form Fields -->
          <div class="col-lg-8">
            <!-- MASTER GELARAN EDISI & TAHUN EVENT (KUNCI UTAMA) -->
            <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden" style="border: 1px solid rgba(59, 130, 246, 0.25) !important;">
              <div class="card-header border-bottom bg-primary-subtle py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar-xs rounded-2 bg-primary text-white d-flex align-items-center justify-content-center shadow-sm">
                    <i class="ri-flashlight-line fs-18 text-warning"></i>
                  </div>
                  <div>
                    <h6 class="card-title mb-0 fs-14 fw-bold text-primary">Master Gelaran Edisi & Tahun Event</h6>
                    <small class="text-muted fs-11">Kunci utama acuan seluruh sistem Semarak (Navbar, Sertifikat, Juknis, Leaderboard & Web Publik)</small>
                  </div>
                </div>
                <span class="badge bg-primary text-white px-2.5 py-1 fs-11 rounded-pill shadow-xs">
                  Edisi Aktif: {{ form.event_edition || 'XIV' }} • {{ form.event_year || '2026' }}
                </span>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <!-- Live Preview Dual Theme (Light & Dark) -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-2">
                      Live Preview Tampilan Badge di Navbar Sistem
                    </label>
                    <div class="row g-2">
                      <div class="col-md-6">
                        <div class="p-3 rounded-3 border bg-white text-center">
                          <small class="text-muted fs-11 d-block mb-2 fw-semibold text-uppercase">Mode Terang (Light Mode)</small>
                          <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill shadow-xs border" style="background-color: #f0f7ff; border-color: #bfdbfe; color: #1d4ed8; font-size: 12px; font-weight: 600;">
                            <i class="ri-flashlight-line text-warning"></i>
                            <span>Edisi {{ form.event_edition || 'XIV' }} • {{ form.event_year || '2026' }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="p-3 rounded-3 border text-center" style="background-color: #1a1d21; border-color: rgba(255,255,255,0.1) !important;">
                          <small class="fs-11 d-block mb-2 fw-semibold text-uppercase" style="color: #94a3b8;">Mode Gelap (Dark Mode)</small>
                          <div class="d-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill shadow-xs border" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.45) 0%, rgba(37, 99, 235, 0.25) 100%); border-color: rgba(96, 165, 250, 0.45); color: #93c5fd; font-size: 12px; font-weight: 600; box-shadow: 0 0 12px rgba(59, 130, 246, 0.25);">
                            <i class="ri-flashlight-line text-warning"></i>
                            <span>Edisi {{ form.event_edition || 'XIV' }} • {{ form.event_year || '2026' }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Kolom Edisi -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                      Angka Romawi Edisi <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex flex-wrap gap-1.5 mb-2">
                      <button 
                        v-for="ed in quickEditions" 
                        :key="ed" 
                        type="button" 
                        class="btn btn-xs rounded-pill"
                        :class="form.event_edition === ed ? 'btn-primary' : 'btn-light border'"
                        @click="setQuickEdition(ed)"
                      >
                        {{ ed }}
                      </button>
                    </div>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-flashlight-line"></i></span>
                      <input
                        v-model="form.event_edition"
                        type="text"
                        class="form-control text-uppercase fw-bold"
                        placeholder="Contoh: XIV"
                        required
                        @input="syncEventName"
                      />
                    </div>
                    <div v-if="form.errors.event_edition" class="text-danger fs-11 mt-1">{{ form.errors.event_edition }}</div>
                  </div>

                  <!-- Kolom Tahun -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">
                      Tahun Penyelenggaraan <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex flex-wrap gap-1.5 mb-2">
                      <button 
                        v-for="yr in quickYears" 
                        :key="yr" 
                        type="button" 
                        class="btn btn-xs rounded-pill"
                        :class="form.event_year === yr ? 'btn-primary' : 'btn-light border'"
                        @click="setQuickYear(yr)"
                      >
                        {{ yr }}
                      </button>
                    </div>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-calendar-event-line"></i></span>
                      <input
                        v-model="form.event_year"
                        type="number"
                        class="form-control font-monospace"
                        placeholder="Contoh: 2026"
                        min="2020"
                        max="2040"
                        required
                        @input="syncEventName"
                      />
                    </div>
                    <div v-if="form.errors.event_year" class="text-danger fs-11 mt-1">{{ form.errors.event_year }}</div>
                  </div>

                  <!-- Nama Acara Utama Lengkap -->
                  <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-0">
                        Nama Acara Resmi Lengkap <span class="text-danger">*</span>
                      </label>
                      <button 
                        type="button" 
                        class="btn btn-xs text-primary p-0 border-0 bg-transparent fw-semibold"
                        @click="syncEventName"
                      >
                        <i class="ri-magic-line me-0.5"></i> Auto-Generate dari Edisi & Tahun
                      </button>
                    </div>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-trophy-line"></i></span>
                      <input
                        v-model="form.event_name"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: SEMARAK ALIYAH XIV 2026"
                        required
                      />
                    </div>
                    <div v-if="form.errors.event_name" class="text-danger fs-11 mt-1">{{ form.errors.event_name }}</div>
                    <small class="text-muted fs-11 mt-1 d-block">
                      Nama ini menjadi rujukan otomatis di seluruh portal publik, banner, formulir pendaftaran, juknis perlombaan, dan sertifikat juara.
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- 1. Identitas Resmi Lembaga & Website -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                  <i class="ri-school-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Identitas Resmi Lembaga & Website</h6>
                  <small class="text-muted fs-11">Data madrasah penyelenggara dan website resmi</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Nama Sekolah / Madrasah Resmi <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-government-line"></i></span>
                      <input
                        v-model="form.school_name"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: MA Unggulan Wahid Hasyim Balung"
                        required
                      />
                    </div>
                    <div v-if="form.errors.school_name" class="text-danger fs-11 mt-1">{{ form.errors.school_name }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Nama Singkat / Brand <span class="text-danger">*</span></label>
                    <input
                      v-model="form.school_short_name"
                      type="text"
                      class="form-control"
                      placeholder="Contoh: MA Wahid Hasyim"
                      required
                    />
                    <div v-if="form.errors.school_short_name" class="text-danger fs-11 mt-1">{{ form.errors.school_short_name }}</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Website Utama Sekolah</label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-global-line"></i></span>
                      <input
                        v-model="form.school_website"
                        type="url"
                        class="form-control"
                        placeholder="https://maswahas.sch.id"
                      />
                    </div>
                    <div v-if="form.errors.school_website" class="text-danger fs-11 mt-1">{{ form.errors.school_website }}</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Tagline / Visi Singkat Lembaga</label>
                    <textarea
                      v-model="form.school_tagline"
                      rows="2"
                      class="form-control"
                      placeholder="Contoh: Lembaga Pendidikan Islam Modern Terakreditasi A dengan Program Unggulan Plus Keterampilan, Riset, & Penguasaan Bahasa Asing"
                    ></textarea>
                    <small class="text-muted fs-11">Akan tampil di bawah logo utama pada footer halaman web.</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Alamat Lengkap & Geografis -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                  <i class="ri-map-pin-2-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Alamat & Lokasi Madrasah</h6>
                  <small class="text-muted fs-11">Koordinat dan alamat fisik untuk panduan delegasi lomba</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Alamat Lengkap Kantor / Kampus <span class="text-danger">*</span></label>
                    <textarea
                      v-model="form.school_address"
                      rows="2"
                      class="form-control"
                      placeholder="Contoh: Jl. Puger No. 20 Balung Kulon, Kec. Balung, Kabupaten Jember, Jawa Timur 68161"
                      required
                    ></textarea>
                    <div v-if="form.errors.school_address" class="text-danger fs-11 mt-1">{{ form.errors.school_address }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Kabupaten / Kota</label>
                    <input
                      v-model="form.school_city"
                      type="text"
                      class="form-control"
                      placeholder="Jember"
                    />
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Provinsi</label>
                    <input
                      v-model="form.school_province"
                      type="text"
                      class="form-control"
                      placeholder="Jawa Timur"
                    />
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Kode Pos</label>
                    <input
                      v-model="form.school_postal_code"
                      type="text"
                      class="form-control"
                      placeholder="68161"
                    />
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis d-flex align-items-center justify-content-between">
                      <span>URL Google Maps Embed (iframe src)</span>
                      <a href="https://maps.google.com" target="_blank" class="fs-11 text-primary text-decoration-none">
                        <i class="ri-external-link-line me-1"></i> Buka Google Maps
                      </a>
                    </label>
                    <input
                      v-model="form.maps_embed_url"
                      type="text"
                      class="form-control"
                      placeholder="https://www.google.com/maps/embed?pb=..."
                    />
                    <small class="text-muted fs-11 d-block mt-1">
                      Salin tautan dari fitur Google Maps: Bagikan -> Sematkan Peta -> Salin URL di dalam parameter <code>src="..."</code>.
                    </small>

                    <!-- Interactive Map Preview -->
                    <div v-if="form.maps_embed_url" class="mt-3 rounded-3 overflow-hidden border border-secondary/20 shadow-sm" style="height: 180px;">
                      <iframe
                        :src="form.maps_embed_url"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                      ></iframe>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Saluran Kontak & Jam Layanan -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                  <i class="ri-customer-service-2-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Kontak Layanan & Jam Operasional</h6>
                  <small class="text-muted fs-11">Hotline panitia untuk memandu peserta dan pendaftar lomba</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Nomor WhatsApp Resmi / Hotline <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <span class="input-group-text bg-success-subtle text-success border-0"><i class="ri-whatsapp-line"></i></span>
                      <input
                        v-model="form.contact_wa"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: 0838 5007 0767"
                        required
                      />
                    </div>
                    <div v-if="form.errors.contact_wa" class="text-danger fs-11 mt-1">{{ form.errors.contact_wa }}</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Telepon Kantor / Tata Usaha</label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-phone-line"></i></span>
                      <input
                        v-model="form.contact_phone"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: (0336) 621123"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Email Resmi Sekretariat <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <span class="input-group-text bg-danger-subtle text-danger border-0"><i class="ri-mail-line"></i></span>
                      <input
                        v-model="form.contact_email"
                        type="email"
                        class="form-control"
                        placeholder="semarak.aliyah@gmail.com"
                        required
                      />
                    </div>
                    <div v-if="form.errors.contact_email" class="text-danger fs-11 mt-1">{{ form.errors.contact_email }}</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Jam Layanan / Operasional</label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-time-line"></i></span>
                      <input
                        v-model="form.service_hours"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: Senin - Sabtu : 07.00 - 13.00 WIB"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 4. Akun Media Sosial Resmi -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                  <i class="ri-share-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Tautan Media Sosial Resmi</h6>
                  <small class="text-muted fs-11">Tautan profil resmi yang terhubung di navigasi dan footer</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Instagram Profile URL</label>
                    <div class="input-group">
                      <span class="input-group-text bg-danger-subtle text-danger border-0"><i class="ri-instagram-line"></i></span>
                      <input
                        v-model="form.social_instagram"
                        type="url"
                        class="form-control"
                        placeholder="https://www.instagram.com/ma_wahas/"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Facebook Fanpage URL</label>
                    <div class="input-group">
                      <span class="input-group-text bg-primary-subtle text-primary border-0"><i class="ri-facebook-fill"></i></span>
                      <input
                        v-model="form.social_facebook"
                        type="url"
                        class="form-control"
                        placeholder="https://www.facebook.com/maswahas"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">YouTube Official Channel URL</label>
                    <div class="input-group">
                      <span class="input-group-text bg-danger-subtle text-danger border-0"><i class="ri-youtube-line"></i></span>
                      <input
                        v-model="form.social_youtube"
                        type="url"
                        class="form-control"
                        placeholder="https://www.youtube.com/@maswahas"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">TikTok Official Account URL</label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark text-white border-0"><i class="ri-tiktok-line"></i></span>
                      <input
                        v-model="form.social_tiktok"
                        type="url"
                        class="form-control"
                        placeholder="https://www.tiktok.com/@maswahas"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-dark-subtle border border-secondary/20 mb-5">
              <span class="text-muted fs-12">
                <i class="ri-information-line me-1 text-primary"></i>
                Perubahan akan langsung aktif di seluruh halaman publik tanpa restart server.
              </span>
              <button
                type="submit"
                class="btn btn-primary px-4 py-2 rounded-pill fw-bold d-flex align-items-center gap-2 shadow-sm"
                :disabled="form.processing"
              >
                <i v-if="form.processing" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-3-line"></i>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Profil Sekolah' }}</span>
              </button>
            </div>
          </div>

          <!-- Right Column: Live Card Preview -->
          <div class="col-lg-4">
            <div class="sticky-top" style="top: 90px; z-index: 10;">
              <!-- Live Preview Card -->
              <div class="card border-0 shadow-lg overflow-hidden" style="background: #0f172a; border-radius: 20px; border: 1px solid rgba(255,255,255,0.12);">
                <div class="card-header border-0 bg-gradient p-3 text-center" style="background: linear-gradient(135deg, rgba(139,92,246,0.3), rgba(236,72,153,0.2));">
                  <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 fs-11 fw-semibold">
                    <i class="ri-eye-line me-1"></i> Live Footer Card Preview
                  </span>
                </div>
                <div class="card-body p-4 text-center">
                  <div class="mb-3 d-inline-block p-2 rounded-circle bg-dark border border-light/10 shadow">
                    <img src="/assets/img/logo.png" alt="Logo" style="height: 54px; width: auto;" />
                  </div>
                  <h5 class="fw-bold text-white mb-1 fs-15">{{ form.event_name || 'SEMARAK ALIYAH XIV' }}</h5>
                  <div class="mb-2">
                    <span class="badge rounded-pill px-3 py-1 fs-11" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.45) 0%, rgba(37, 99, 235, 0.25) 100%); border: 1px solid rgba(96, 165, 250, 0.45); color: #93c5fd; box-shadow: 0 0 10px rgba(59, 130, 246, 0.25);">
                      <i class="ri-flashlight-line text-warning me-1"></i> Edisi {{ form.event_edition || 'XIV' }} • {{ form.event_year || '2026' }}
                    </span>
                  </div>
                  <p class="text-primary fs-12 fw-semibold mb-2">{{ form.school_name || 'MA Unggulan Wahid Hasyim' }}</p>
                  <p class="text-muted fs-11 lh-base px-2 mb-3">
                    {{ form.school_tagline || 'Pendidikan Islam Modern Terakreditasi A dengan Program Unggulan.' }}
                  </p>

                  <hr class="border-secondary/20 my-3" />

                  <!-- Contact Items Preview -->
                  <div class="text-start fs-12 text-slate-300 space-y-2">
                    <div class="d-flex align-items-start gap-2 mb-2">
                      <i class="ri-map-pin-line text-warning mt-1 flex-shrink-0"></i>
                      <span class="fs-11 text-muted lh-sm">{{ form.school_address || 'Jl. Puger No. 20 Balung Kulon' }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i class="ri-whatsapp-line text-success flex-shrink-0"></i>
                      <span class="fs-11 text-white font-mono">{{ form.contact_wa || '-' }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i class="ri-mail-line text-danger flex-shrink-0"></i>
                      <span class="fs-11 text-muted">{{ form.contact_email || '-' }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      <i class="ri-time-line text-info flex-shrink-0"></i>
                      <span class="fs-11 text-muted">{{ form.service_hours || '07.00 - 13.00 WIB' }}</span>
                    </div>
                  </div>

                  <!-- Social Links Preview Chips -->
                  <div class="d-flex justify-content-center gap-2 mt-4">
                    <a v-if="form.social_instagram" :href="form.social_instagram" target="_blank" class="avatar-xs rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center text-decoration-none">
                      <i class="ri-instagram-line"></i>
                    </a>
                    <a v-if="form.social_facebook" :href="form.social_facebook" target="_blank" class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center text-decoration-none">
                      <i class="ri-facebook-fill"></i>
                    </a>
                    <a v-if="form.social_youtube" :href="form.social_youtube" target="_blank" class="avatar-xs rounded-circle bg-danger text-white d-flex align-items-center justify-content-center text-decoration-none">
                      <i class="ri-youtube-line"></i>
                    </a>
                    <a v-if="form.social_tiktok" :href="form.social_tiktok" target="_blank" class="avatar-xs rounded-circle bg-dark text-white d-flex align-items-center justify-content-center text-decoration-none border border-secondary">
                      <i class="ri-tiktok-line"></i>
                    </a>
                    <a v-if="form.school_website" :href="form.school_website" target="_blank" class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center text-decoration-none">
                      <i class="ri-global-line"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </VelzonLayout>
</template>
