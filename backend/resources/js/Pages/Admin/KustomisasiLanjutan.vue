<template>
  <Head title="Kustomisasi Lanjutan Web Publik - Admin Panel" />

  <KustomisasiLayout
    active-menu="umum"
    page-title="Kustomisasi Lanjutan Web Publik"
    page-description="Atur elemen hero section, statistik angka acara, seksi apresiasi tentang & hadiah, banner pengumuman, pop-up modal, tombol WhatsApp live helpdesk, countdown timer, dan custom code."
    page-badge="Efek & Interaksi Web"
  >
    <template #actions>
      <button 
        type="button" 
        class="btn btn-outline-danger d-flex align-items-center gap-1 shadow-sm"
        @click="openResetModal"
        :disabled="isResetting"
      >
        <i class="ri-refresh-line"></i> Reset Standar
      </button>
      <button 
        type="button" 
        class="btn btn-primary d-flex align-items-center gap-1 shadow-sm px-3"
        @click="saveCustomization"
        :disabled="isSaving"
      >
        <i class="ri-save-line"></i> {{ isSaving ? 'Menyimpan...' : 'Simpan Kustomisasi' }}
      </button>
    </template>

      <!-- Quick Summary Alert -->
      <div class="alert alert-primary-subtle border border-primary-subtle d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-sm flex-shrink-0">
            <span class="avatar-title bg-primary text-white rounded-circle fs-20">
              <i class="ri-magic-line"></i>
            </span>
          </div>
          <div>
            <h5 class="fs-14 fw-bold text-primary mb-1">Pusat Kendali Pengalaman Visual & Interaksi Publik</h5>
            <p class="fs-12 text-muted mb-0">
              Semua konfigurasi di bawah ini diterapkan secara langsung pada halaman portal depan (Web Publik) tanpa perlu mengedit file kode template secara manual.
            </p>
          </div>
        </div>
        <div class="d-none d-md-flex gap-2 flex-wrap">
          <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
            Hero: Aktif
          </span>
          <span class="badge" :class="form.stats_active ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-secondary-subtle text-secondary'">
            Stats: {{ form.stats_active ? 'Aktif' : 'Off' }}
          </span>
          <span class="badge" :class="form.about_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary'">
            Tentang: {{ form.about_active ? 'Aktif' : 'Off' }}
          </span>
          <span class="badge" :class="form.announcement_banner_active ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-secondary'">
            Banner: {{ form.announcement_banner_active ? 'Aktif' : 'Off' }}
          </span>
          <span class="badge" :class="form.floating_wa_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary'">
            WA: {{ form.floating_wa_active ? 'Aktif' : 'Off' }}
          </span>
        </div>
      </div>

      <!-- Main Layout: Form Tabs (Col-lg-7) + Live Preview (Col-lg-5) -->
      <div class="row g-4">
        <!-- LEFT: FORM CONTROLS -->
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <!-- Tabs Navigation -->
            <div class="card-header border-bottom p-0 kustomisasi-tabs-header">
              <ul class="nav nav-tabs nav-tabs-custom card-header-tabs border-bottom-0 mx-3 flex-wrap" role="tablist">
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'hero' }"
                    @click="activeTab = 'hero'"
                  >
                    <i class="ri-layout-top-line me-1 text-warning"></i>
                    Hero Section
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'stats' }"
                    @click="activeTab = 'stats'"
                  >
                    <i class="ri-bar-chart-box-line me-1 text-info"></i>
                    Statistik Acara
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'about' }"
                    @click="activeTab = 'about'"
                  >
                    <i class="ri-gift-line me-1 text-success"></i>
                    Tentang & Hadiah
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'banner' }"
                    @click="activeTab = 'banner'"
                  >
                    <i class="ri-notification-3-line me-1 text-warning"></i>
                    Banner Info
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'popup' }"
                    @click="activeTab = 'popup'"
                  >
                    <i class="ri-window-line me-1 text-info"></i>
                    Pop-up Modal
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'wa' }"
                    @click="activeTab = 'wa'"
                  >
                    <i class="ri-whatsapp-line me-1 text-success"></i>
                    Floating WA
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'countdown' }"
                    @click="activeTab = 'countdown'"
                  >
                    <i class="ri-time-line me-1 text-primary"></i>
                    Countdown
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'visual' }"
                    @click="activeTab = 'visual'"
                  >
                    <i class="ri-sparkling-fill me-1 text-danger"></i>
                    Efek Visual
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'code' }"
                    @click="activeTab = 'code'"
                  >
                    <i class="ri-code-s-slash-line me-1 text-secondary"></i>
                    Custom Code
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body p-4">
              <!-- ========================================================== -->
              <!-- TAB 0A: HERO SECTION KUSTOMISASI                           -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'hero'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Kustomisasi Elemen Hero Section (Layar Utama)</h5>
                    <p class="fs-12 text-muted mb-0">Atur teks badge, judul sambutan, subjudul, tombol CTA, pamflet poster, maskot visual, dan floating badge.</p>
                  </div>
                  <span class="badge bg-warning-subtle text-warning-emphasis fs-11 fw-bold">Hero Frontpage</span>
                </div>

                <div class="row g-3">
                  <!-- Hero Badge & Pulse -->
                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Badge Pengantar (Atas Judul)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.hero_badge_text" 
                      placeholder="Ajang Kompetisi Pelajar SMP/MTs Se-Kabupaten Jember"
                    />
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        id="heroBadgePulseSwitch"
                        v-model="form.hero_badge_pulse"
                      />
                      <label class="form-check-label fs-12 fw-semibold" for="heroBadgePulseSwitch">
                        Titik Berkedip (Pulse Dot)
                      </label>
                    </div>
                  </div>

                  <!-- Headline Prefix & Highlight -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Baris Judul Awalan (Headline Prefix)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.hero_headline_prefix" 
                      placeholder="Selamat Datang di Laman Official"
                    />
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">
                      Teks Sorotan Warna Emas (Highlight)
                    </label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.hero_headline_highlight" 
                      :placeholder="$page.props.schoolProfile?.event_name || 'Semarak Aliyah XIV 2026'"
                    />
                    <small class="text-muted fs-11 d-block mt-1">Kosongkan untuk otomatis memakai nama acara dari Profil Sekolah.</small>
                  </div>

                  <!-- Subheadline -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Subjudul / Deskripsi Sambutan (Subheadline)</label>
                    <textarea 
                      class="form-control" 
                      rows="3" 
                      v-model="form.hero_subheadline" 
                      placeholder="Panggung unjuk prestasi, bakat seni, kecerdasan sains, dan sportivitas pelajar SMP/MTs..."
                    ></textarea>
                  </div>

                  <!-- CTA 1 (Primary / Daftar) -->
                  <div class="col-12 pt-2 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <label class="form-label fs-12 fw-bold section-title-text mb-0">
                        <i class="ri-user-follow-line text-primary me-1"></i> Tombol Utama (CTA 1 - Daftar)
                      </label>
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="cta1Switch" v-model="form.hero_cta1_active">
                        <label class="form-check-label fs-11" for="cta1Switch">{{ form.hero_cta1_active ? 'Tampil' : 'Sembunyikan' }}</label>
                      </div>
                    </div>
                    <div class="row g-2">
                      <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_cta1_text" placeholder="Daftar Sekarang">
                      </div>
                      <div class="col-md-7">
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_cta1_url" placeholder="URL kustom (kosongkan = otomatis ke formulir registrasi)">
                      </div>
                    </div>
                  </div>

                  <!-- CTA 2 (Secondary / Juknis) -->
                  <div class="col-12 pt-2 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <label class="form-label fs-12 fw-bold section-title-text mb-0">
                        <i class="ri-file-download-line text-warning me-1"></i> Tombol Kedua (CTA 2 - Unduh Juknis)
                      </label>
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="cta2Switch" v-model="form.hero_cta2_active">
                        <label class="form-check-label fs-11" for="cta2Switch">{{ form.hero_cta2_active ? 'Tampil' : 'Sembunyikan' }}</label>
                      </div>
                    </div>
                    <div class="row g-2">
                      <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_cta2_text" placeholder="Unduh Juknis (PDF)">
                      </div>
                      <div class="col-md-7">
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_cta2_url" placeholder="URL kustom (kosongkan = otomatis ke file Juknis umum)">
                      </div>
                    </div>
                  </div>

                  <!-- CTA 3 (Secondary / Cabang Lomba) -->
                  <div class="col-12 pt-2 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <label class="form-label fs-12 fw-bold section-title-text mb-0">
                        <i class="ri-search-line text-info me-1"></i> Tombol Ketiga (CTA 3 - Lihat Lomba)
                      </label>
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="cta3Switch" v-model="form.hero_cta3_active">
                        <label class="form-check-label fs-11" for="cta3Switch">{{ form.hero_cta3_active ? 'Tampil' : 'Sembunyikan' }}</label>
                      </div>
                    </div>
                    <div class="row g-2">
                      <div class="col-md-5">
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_cta3_text" placeholder="Lihat Cabang Lomba">
                      </div>
                      <div class="col-md-7">
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_cta3_url" placeholder="Target tautan / anchor (default: #kompetisi)">
                      </div>
                    </div>
                  </div>

                  <!-- Visual Gambar Poster & Maskot Hero Section -->
                  <div class="col-12 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                      <div>
                        <h6 class="fs-13 fw-bold section-title-text mb-0">
                          <i class="ri-image-2-line text-success me-1"></i> Visual Poster Pamflet & Maskot Karakter Hero
                        </h6>
                        <small class="text-muted fs-11">Unggah berkas gambar langsung dari perangkat Anda atau gunakan berkas bawaan sistem.</small>
                      </div>
                    </div>

                    <div class="row g-3">
                      <!-- 1. KARTU UPLOAD POSTER PAMFLET -->
                      <div class="col-md-6">
                        <div class="card border rounded-3 h-100 shadow-none upload-media-card">
                          <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <label class="form-label fs-12 fw-bold upload-media-title mb-0">
                                <i class="ri-file-image-line text-primary me-1"></i> Gambar Poster Pamflet Hero
                              </label>
                              <span 
                                class="badge fs-10" 
                                :class="filePreviewPosterUrl ? 'bg-warning-subtle text-warning border border-warning' : (form.hero_poster_image?.startsWith('/storage/') ? 'bg-success-subtle text-success border border-success' : 'bg-primary-subtle text-primary border border-primary')"
                              >
                                {{ filePreviewPosterUrl ? 'Berkas Baru Dipilih' : (form.hero_poster_image?.startsWith('/storage/') ? 'Kustom Terunggah' : 'Bawaan Sistem') }}
                              </span>
                            </div>

                            <!-- Preview Thumbnail Box -->
                            <div class="d-flex gap-3 align-items-center p-2 rounded border upload-media-inner mb-3">
                              <div class="poster-thumb-preview flex-shrink-0 position-relative rounded overflow-hidden border" style="width: 70px; height: 95px; background: #0f172a;">
                                <img 
                                  :src="effectivePosterImage" 
                                  alt="Poster Preview" 
                                  class="w-100 h-100 object-fit-cover"
                                />
                                <div v-if="filePreviewPosterUrl" class="position-absolute bottom-0 start-0 end-0 bg-warning text-dark text-center fw-bold fs-9 py-0.5">
                                  Baru
                                </div>
                              </div>
                              <div class="flex-grow-1 min-w-0">
                                <div class="fs-12 fw-semibold text-truncate mb-1 upload-media-filename" :title="form.hero_poster_image">
                                  {{ filePreviewPosterUrl ? (form.hero_poster_file?.name || 'File Lokal Baru') : (form.hero_poster_image ? form.hero_poster_image.split('/').pop() : 'pamflet.jpg') }}
                                </div>
                                <div class="fs-11 text-muted mb-2">
                                  {{ filePreviewPosterUrl ? `Ukuran: ${(form.hero_poster_file.size / 1024).toFixed(1)} KB` : 'Format: JPG, PNG, WebP (Rasio ~3:4)' }}
                                </div>
                                <div class="d-flex gap-1.5 flex-wrap">
                                  <button 
                                    v-if="form.hero_poster_image !== '/assets/img/pamflet.jpg' || filePreviewPosterUrl"
                                    type="button" 
                                    class="btn btn-outline-secondary btn-xs py-1 px-2"
                                    @click="resetPosterToDefault"
                                    title="Kembalikan ke pamflet bawaan"
                                  >
                                    <i class="ri-refresh-line me-1"></i>Gunakan Bawaan
                                  </button>
                                  <button 
                                    v-if="filePreviewPosterUrl"
                                    type="button" 
                                    class="btn btn-outline-danger btn-xs py-1 px-2"
                                    @click="removeSelectedPosterFile"
                                    title="Batalkan pilihan berkas"
                                  >
                                    <i class="ri-close-line me-1"></i>Batal
                                  </button>
                                </div>
                              </div>
                            </div>

                            <!-- Input Upload File -->
                            <div class="mb-2">
                              <label class="form-label fs-11 text-muted mb-1 fw-semibold">Unggah File Baru (Maks. 10MB):</label>
                              <div class="input-group input-group-sm">
                                <input 
                                  type="file" 
                                  ref="posterFileInputRef" 
                                  class="form-control" 
                                  accept="image/jpeg,image/png,image/jpg,image/webp" 
                                  @change="handlePosterFileChange"
                                />
                              </div>
                            </div>

                            <!-- Toggle / Input Path Manual -->
                            <div class="mt-auto pt-2 border-top">
                              <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-11 text-muted">Path / URL Gambar:</span>
                                <a 
                                  href="javascript:void(0);" 
                                  class="fs-10 text-primary text-decoration-none fw-semibold"
                                  @click="showManualPosterInput = !showManualPosterInput"
                                >
                                  {{ showManualPosterInput ? 'Sembunyikan' : 'Edit Manual' }}
                                </a>
                              </div>
                              <input 
                                v-if="showManualPosterInput"
                                type="text" 
                                class="form-control form-control-sm font-monospace fs-11" 
                                v-model="form.hero_poster_image" 
                                placeholder="/assets/img/pamflet.jpg"
                              />
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- 2. KARTU UPLOAD MASKOT KARAKTER -->
                      <div class="col-md-6">
                        <div class="card border rounded-3 h-100 shadow-none upload-media-card">
                          <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                              <label class="form-label fs-12 fw-bold upload-media-title mb-0">
                                <i class="ri-user-smile-line text-success me-1"></i> Gambar Maskot Melayang
                              </label>
                              <div class="form-check form-switch mb-0">
                                <input class="form-check-input cursor-pointer" type="checkbox" id="mascotSwitch" v-model="form.hero_mascot_active">
                                <label class="form-check-label fs-11 fw-semibold" for="mascotSwitch">{{ form.hero_mascot_active ? 'Tampil' : 'Off' }}</label>
                              </div>
                            </div>

                            <!-- Preview Thumbnail Box with Checkerboard Background for PNG Transparency -->
                            <div class="d-flex gap-3 align-items-center p-2 rounded border upload-media-inner mb-3">
                              <div class="mascot-thumb-preview flex-shrink-0 position-relative rounded overflow-hidden border checkerboard-bg" style="width: 70px; height: 95px;">
                                <img 
                                  :src="effectiveMascotImage" 
                                  alt="Mascot Preview" 
                                  class="w-100 h-100 object-fit-contain p-1"
                                />
                                <div v-if="filePreviewMascotUrl" class="position-absolute bottom-0 start-0 end-0 bg-warning text-dark text-center fw-bold fs-9 py-0.5">
                                  Baru
                                </div>
                              </div>
                              <div class="flex-grow-1 min-w-0">
                                <div class="fs-12 fw-semibold text-truncate mb-1 upload-media-filename" :title="form.hero_mascot_image">
                                  {{ filePreviewMascotUrl ? (form.hero_mascot_file?.name || 'File Maskot Baru') : (form.hero_mascot_image ? form.hero_mascot_image.split('/').pop() : 'kadita.png') }}
                                </div>
                                <div class="fs-11 text-muted mb-2">
                                  {{ filePreviewMascotUrl ? `Ukuran: ${(form.hero_mascot_file.size / 1024).toFixed(1)} KB` : 'Format: PNG Transparan / WebP' }}
                                </div>
                                <div class="d-flex gap-1.5 flex-wrap">
                                  <button 
                                    v-if="form.hero_mascot_image !== '/assets/img/kadita.png' || filePreviewMascotUrl"
                                    type="button" 
                                    class="btn btn-outline-secondary btn-xs py-1 px-2"
                                    @click="resetMascotToDefault"
                                    title="Kembalikan ke maskot bawaan"
                                  >
                                    <i class="ri-refresh-line me-1"></i>Gunakan Bawaan
                                  </button>
                                  <button 
                                    v-if="filePreviewMascotUrl"
                                    type="button" 
                                    class="btn btn-outline-danger btn-xs py-1 px-2"
                                    @click="removeSelectedMascotFile"
                                    title="Batalkan pilihan berkas"
                                  >
                                    <i class="ri-close-line me-1"></i>Batal
                                  </button>
                                </div>
                              </div>
                            </div>

                            <!-- Input Upload File -->
                            <div class="mb-2">
                              <label class="form-label fs-11 text-muted mb-1 fw-semibold">Unggah File Baru (PNG Transparan / WebP, Maks. 10MB):</label>
                              <div class="input-group input-group-sm">
                                <input 
                                  type="file" 
                                  ref="mascotFileInputRef" 
                                  class="form-control" 
                                  accept="image/png,image/webp,image/jpeg,image/svg+xml" 
                                  @change="handleMascotFileChange"
                                />
                              </div>
                            </div>

                            <!-- Toggle / Input Path Manual -->
                            <div class="mt-auto pt-2 border-top">
                              <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fs-11 text-muted">Path / URL Gambar:</span>
                                <a 
                                  href="javascript:void(0);" 
                                  class="fs-10 text-primary text-decoration-none fw-semibold"
                                  @click="showManualMascotInput = !showManualMascotInput"
                                >
                                  {{ showManualMascotInput ? 'Sembunyikan' : 'Edit Manual' }}
                                </a>
                              </div>
                              <input 
                                v-if="showManualMascotInput"
                                type="text" 
                                class="form-control form-control-sm font-monospace fs-11" 
                                v-model="form.hero_mascot_image" 
                                placeholder="/assets/img/kadita.png"
                              />
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Floating Badge Pill -->
                  <div class="col-12 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <h6 class="fs-13 fw-bold section-title-text mb-0">
                        <i class="ri-trophy-line text-warning me-1"></i> Floating Badge (Pill Melayang di Poster)
                      </h6>
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="floatBadgeSwitch" v-model="form.hero_float_badge_active">
                        <label class="form-check-label fs-11" for="floatBadgeSwitch">{{ form.hero_float_badge_active ? 'Aktif' : 'Off' }}</label>
                      </div>
                    </div>
                    <div class="row g-2">
                      <div class="col-md-3">
                        <label class="form-label fs-11 text-muted mb-1">Ikon Remixicon</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text"><i :class="form.hero_float_badge_icon || 'ri-trophy-line'"></i></span>
                          <input type="text" class="form-control" v-model="form.hero_float_badge_icon" placeholder="ri-trophy-line">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fs-11 text-muted mb-1">Judul Pill</label>
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_float_badge_title" placeholder="Hadiah Jutaan Rupiah">
                      </div>
                      <div class="col-md-5">
                        <label class="form-label fs-11 text-muted mb-1">Keterangan Tambahan</label>
                        <input type="text" class="form-control form-control-sm" v-model="form.hero_float_badge_desc" placeholder="+ Beasiswa Pendidikan">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 0B: SECTION STATISTIK ACARA                            -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'stats'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Kustomisasi Strip Statistik Acara</h5>
                    <p class="fs-12 text-muted mb-0">Atur kartu angka pencapaian, jumlah sekolah, hadiah, dan sertifikat yang tampil tepat di bawah Hero Section.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="statsActiveSwitch"
                      v-model="form.stats_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="statsActiveSwitch">
                      {{ form.stats_active ? 'Section Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <div v-for="(stat, idx) in form.stats_items" :key="idx" class="col-12">
                    <div class="p-3 rounded border preview-info-box">
                      <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 fs-11 fw-bold">#{{ idx + 1 }}</span>
                          <span class="fw-bold fs-13 section-title-text">Kartu Statistik {{ idx + 1 }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                          <button 
                            v-if="form.stats_items.length > 1" 
                            type="button" 
                            class="btn btn-outline-danger btn-xs" 
                            @click="removeStatItem(idx)"
                            title="Hapus Kartu Ini"
                          >
                            <i class="ri-delete-bin-line"></i> Hapus
                          </button>
                        </div>
                      </div>

                      <div class="row g-2">
                        <div class="col-md-4">
                          <label class="form-label fs-11 text-muted mb-1">Ikon Remixicon</label>
                          <div class="input-group input-group-sm">
                            <span class="input-group-text"><i :class="stat.icon || 'ri-star-line'"></i></span>
                            <input type="text" class="form-control" v-model="stat.icon" placeholder="ri-focus-2-line">
                          </div>
                          <!-- Quick icon suggestions -->
                          <div class="d-flex gap-1 flex-wrap mt-1">
                            <span 
                              v-for="ic in ['ri-focus-2-line', 'ri-building-line', 'ri-hand-coin-line', 'ri-award-line', 'ri-trophy-line', 'ri-team-line']" 
                              :key="ic"
                              class="badge border cursor-pointer fs-11 p-1"
                              :class="stat.icon === ic ? 'bg-primary text-white' : 'bg-light text-muted'"
                              @click="stat.icon = ic"
                              :title="ic"
                            >
                              <i :class="ic"></i>
                            </span>
                          </div>
                        </div>

                        <div class="col-md-4">
                          <label class="form-label fs-11 text-muted mb-1">Angka / Nilai Sorotan</label>
                          <input type="text" class="form-control form-control-sm" v-model="stat.number" placeholder="Contoh: 8 Cabang, 50+ Sekolah">
                        </div>

                        <div class="col-md-4">
                          <label class="form-label fs-11 text-muted mb-1">Label / Keterangan</label>
                          <input type="text" class="form-control form-control-sm" v-model="stat.label" placeholder="Contoh: Kompetisi & Olahraga">
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Tombol Tambah Kartu Statistik -->
                  <div class="col-12 text-center pt-2">
                    <button 
                      v-if="form.stats_items.length < 8"
                      type="button" 
                      class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1"
                      @click="addStatItem"
                    >
                      <i class="ri-add-line"></i> Tambah Kartu Statistik
                    </button>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 0C: SECTION TENTANG & HADIAH                           -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'about'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Kustomisasi Seksi Tentang & Hadiah (Benefits)</h5>
                    <p class="fs-12 text-muted mb-0">Atur judul, deskripsi sambutan penyelenggara, dan kartu fasilitas/apresiasi (Piala, Hadiah Uang, Piagam, Beasiswa).</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="aboutActiveSwitch"
                      v-model="form.about_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="aboutActiveSwitch">
                      {{ form.about_active ? 'Section Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Header Settings -->
                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-muted">Tag Kategori Seksi</label>
                    <input type="text" class="form-control" v-model="form.about_tag" placeholder="Apresiasi & Fasilitas">
                  </div>
                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-muted">Judul Utama Seksi</label>
                    <input type="text" class="form-control" v-model="form.about_title" placeholder="Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan">
                  </div>
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Deskripsi Paragraf Seksi</label>
                    <textarea class="form-control" rows="2" v-model="form.about_desc" placeholder="Penyelenggaraan Semarak Aliyah sebagai bentuk apresiasi nyata bagi generasi muda..."></textarea>
                  </div>

                  <!-- Cards Editor -->
                  <div class="col-12 pt-2">
                    <h6 class="fs-13 fw-bold section-title-text mb-2">
                      <i class="ri-gift-line text-success me-1"></i> Kartu Hadiah & Fasilitas (Prizes Grid)
                    </h6>
                  </div>

                  <div v-for="(prize, idx) in form.about_items" :key="idx" class="col-12">
                    <div class="p-3 rounded border preview-info-box">
                      <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge bg-success text-white rounded-pill px-2 py-0.5 fs-11 fw-bold">#{{ idx + 1 }}</span>
                          <span class="fw-bold fs-13 section-title-text">{{ prize.title || ('Kartu Hadiah ' + (idx + 1)) }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                          <button 
                            v-if="form.about_items.length > 1" 
                            type="button" 
                            class="btn btn-outline-danger btn-xs" 
                            @click="removeAboutItem(idx)"
                            title="Hapus Kartu Ini"
                          >
                            <i class="ri-delete-bin-line"></i> Hapus
                          </button>
                        </div>
                      </div>

                      <div class="row g-2">
                        <div class="col-md-4">
                          <label class="form-label fs-11 text-muted mb-1">Ikon Remixicon</label>
                          <div class="input-group input-group-sm">
                            <span class="input-group-text"><i :class="prize.icon || 'ri-trophy-line'"></i></span>
                            <input type="text" class="form-control" v-model="prize.icon" placeholder="ri-trophy-line">
                          </div>
                          <!-- Quick icon suggestions -->
                          <div class="d-flex gap-1 flex-wrap mt-1">
                            <span 
                              v-for="ic in ['ri-trophy-line', 'ri-money-dollar-circle-line', 'ri-file-shield-line', 'ri-graduation-cap-line', 'ri-gift-line', 'ri-medal-2-line']" 
                              :key="ic"
                              class="badge border cursor-pointer fs-11 p-1"
                              :class="prize.icon === ic ? 'bg-success text-white' : 'bg-light text-muted'"
                              @click="prize.icon = ic"
                              :title="ic"
                            >
                              <i :class="ic"></i>
                            </span>
                          </div>
                        </div>

                        <div class="col-md-8">
                          <label class="form-label fs-11 text-muted mb-1">Nama / Judul Hadiah</label>
                          <input type="text" class="form-control form-control-sm" v-model="prize.title" placeholder="Contoh: Piala Juara, Uang Pembinaan">
                        </div>

                        <div class="col-12 mt-2">
                          <label class="form-label fs-11 text-muted mb-1">Deskripsi Fasilitas / Hadiah</label>
                          <textarea class="form-control form-control-sm" rows="2" v-model="prize.desc" placeholder="Keterangan lengkap hadiah atau fasilitas yang didapatkan..."></textarea>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Tombol Tambah Kartu Hadiah -->
                  <div class="col-12 text-center pt-2">
                    <button 
                      v-if="form.about_items.length < 8"
                      type="button" 
                      class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1"
                      @click="addAboutItem"
                    >
                      <i class="ri-add-line"></i> Tambah Kartu Hadiah / Fasilitas
                    </button>
                  </div>
                </div>
              </div>
              <!-- ========================================================== -->
              <!-- TAB 1: BANNER PENGUMUMAN MENGAMBANG                         -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'banner'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Floating Announcement Ribbon</h5>
                    <p class="fs-12 text-muted mb-0">Baris pita pengumuman menonjol di bagian paling atas atau bawah halaman web.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="bannerActiveSwitch"
                      v-model="form.announcement_banner_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="bannerActiveSwitch">
                      {{ form.announcement_banner_active ? 'Banner Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Posisi Banner -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Posisi Penempatan Banner</label>
                    <CustomSelect v-model="form.announcement_banner_position" size="md">
                      <option value="top">Di Bagian Paling Atas (Top of Header)</option>
                      <option value="bottom">Mengambang di Bawah Layar (Sticky Bottom)</option>
                    </CustomSelect>
                  </div>

                  <!-- Tipe Tema Warna -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Tema & Gaya Visual</label>
                    <CustomSelect v-model="form.announcement_banner_type" size="md">
                      <option value="gold">✨ Gold Glow (Mewah Emas Semarak)</option>
                      <option value="primary">🟣 Violet Glow (Modern Ungu)</option>
                      <option value="success">🟢 Emerald Green (Hijau Prestasi)</option>
                      <option value="danger">🔴 Crimson Red (Penting / Mendesak)</option>
                      <option value="info">🔵 Ocean Cyan (Informasi Standar)</option>
                    </CustomSelect>
                  </div>

                  <!-- Teks Pesan Pengumuman -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Pesan Pengumuman</label>
                    <textarea 
                      class="form-control" 
                      rows="2" 
                      v-model="form.announcement_banner_text"
                      placeholder="Tuliskan pesan pengumuman singkat & menarik..."
                    ></textarea>
                    <small class="text-muted fs-11">Mendukung emoji untuk menarik perhatian pengunjung.</small>
                  </div>

                  <!-- Tombol Aksi CTA -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Tombol Aksi (CTA)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_banner_btn_text" 
                      placeholder="Contoh: Daftar Kontingen Sekarang"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Tautan Tujuan Tombol (URL / Anchor)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_banner_btn_url" 
                      placeholder="Contoh: /#biaya atau /layanan-registrasi"
                    />
                  </div>

                  <!-- Opsi Tombol Tutup (Dismissable) -->
                  <div class="col-12">
                    <div class="form-check form-switch">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        id="bannerDismissableSwitch"
                        v-model="form.announcement_banner_dismissable"
                      />
                      <label class="form-check-label fs-13" for="bannerDismissableSwitch">
                        Izinkan pengunjung menutup banner (Tombol Silang [X] yang mengingat sesi penutupan)
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 2: POP-UP MODAL PENGUMUMAN OTOMATIS                     -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'popup'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Pop-up Entrance Modal</h5>
                    <p class="fs-12 text-muted mb-0">Modal interaktif yang otomatis muncul saat pengunjung pertama kali membuka web.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="popupActiveSwitch"
                      v-model="form.announcement_popup_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="popupActiveSwitch">
                      {{ form.announcement_popup_active ? 'Pop-up Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Frekuensi Kemunculan -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Frekuensi Tampil ke Pengunjung</label>
                    <CustomSelect v-model="form.announcement_popup_frequency" size="md">
                      <option value="once_per_session">Sekali per sesi peramban (Rekomendasi agar ramah pengguna)</option>
                      <option value="always">Selalu muncul setiap halaman dimuat ulang</option>
                    </CustomSelect>
                  </div>

                  <!-- Label Badge Pop-up -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Label Kategori / Badge</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_popup_badge" 
                      placeholder="Contoh: Informasi Resmi Panitia"
                    />
                  </div>

                  <!-- Judul Pop-up -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Judul Pengumuman</label>
                    <input 
                      type="text" 
                      class="form-control fw-semibold" 
                      v-model="form.announcement_popup_title" 
                      placeholder="Contoh: Pengumuman Penting: Technical Meeting & Petunjuk Teknis"
                    />
                  </div>

                  <!-- Gambar Banner Pop-up -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">URL Gambar Poster / Banner Pop-up</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="ri-image-line"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.announcement_popup_image" 
                        placeholder="/assets/img/pamflet.jpg atau URL gambar https://..."
                      />
                    </div>
                    <small class="text-muted fs-11">Kosongkan jika hanya ingin menampilkan pop-up berbasis teks murni.</small>
                  </div>

                  <!-- Konten Pengumuman -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Isi Pesan Pengumuman</label>
                    <textarea 
                      class="form-control" 
                      rows="4" 
                      v-model="form.announcement_popup_content"
                      placeholder="Tuliskan isi pengumuman lengkap di sini..."
                    ></textarea>
                  </div>

                  <!-- Tombol Aksi Pop-up -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Tombol Aksi Utama</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_popup_btn_text" 
                      placeholder="Contoh: Unduh Juknis Lengkap PDF"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Tautan Tombol Aksi</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_popup_btn_url" 
                      placeholder="Contoh: /assets/doc/JUKNIS-SEMARAK-2026.pdf"
                    />
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 3: HELPDESK WHATSAPP MENGAMBANG (FLOATING WA)           -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'wa'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Floating WhatsApp Helpdesk Button</h5>
                    <p class="fs-12 text-muted mb-0">Tombol obrolan instan cepat ke panitia di sudut layar.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="floatingWaActiveSwitch"
                      v-model="form.floating_wa_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="floatingWaActiveSwitch">
                      {{ form.floating_wa_active ? 'Tombol Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Nomor WhatsApp -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Nomor WhatsApp Panitia *</label>
                    <div class="input-group">
                      <span class="input-group-text bg-success-subtle text-success"><i class="ri-whatsapp-fill"></i></span>
                      <input 
                        type="text" 
                        class="form-control fw-bold font-monospace" 
                        v-model="form.floating_wa_number" 
                        placeholder="Contoh: 0838 5007 0767"
                        required
                      />
                    </div>
                  </div>

                  <!-- Label Samping / Tooltip -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Label Teks Tombol / Tooltip</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.floating_wa_label" 
                      placeholder="Contoh: Bantuan Panitia"
                    />
                  </div>

                  <!-- Posisi Tombol -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Posisi Tombol di Layar</label>
                    <CustomSelect v-model="form.floating_wa_position" size="md">
                      <option value="bottom-right">Sudut Kanan Bawah (Standar Web)</option>
                      <option value="bottom-left">Sudut Kiri Bawah</option>
                    </CustomSelect>
                  </div>

                  <!-- Animasi Tombol -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Efek Animasi Perhatian</label>
                    <CustomSelect v-model="form.floating_wa_animation" size="md">
                      <option value="pulse">Pulse Radar Gelombang (Halus & Elegan)</option>
                      <option value="bounce">Bounce Melompat Halus</option>
                      <option value="none">Statis (Tanpa Animasi)</option>
                    </CustomSelect>
                  </div>

                  <!-- Pesan Pembuka Otomatis -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Template Pesan Otomatis (Pre-filled Text)</label>
                    <textarea 
                      class="form-control" 
                      rows="3" 
                      v-model="form.floating_wa_message"
                      placeholder="Ketik teks sapaan otomatis saat pengunjung mengklik tombol WA..."
                    ></textarea>
                    <small class="text-muted fs-11">Pesan ini akan langsung terisi di kolom chat WhatsApp pengunjung.</small>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 4: COUNTDOWN TIMER ACARA                                -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'countdown'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Countdown Timer Hero Section</h5>
                    <p class="fs-12 text-muted mb-0">Hitung mundur batas akhir pendaftaran dan Technical Meeting di bagian depan beranda.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="countdownActiveSwitch"
                      v-model="form.countdown_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="countdownActiveSwitch">
                      {{ form.countdown_active ? 'Countdown Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Label Judul Countdown -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Judul / Keterangan Countdown</label>
                    <input 
                      type="text" 
                      class="form-control fw-semibold" 
                      v-model="form.countdown_title" 
                      placeholder="Contoh: Batas Akhir Pendaftaran & TM Ditutup Dalam:"
                    />
                  </div>

                  <!-- Target Tanggal & Jam -->
                  <div class="col-md-7">
                    <label class="form-label fs-12 fw-semibold text-muted">Target Waktu Hitung Mundur (ISO / DateTime)</label>
                    <input 
                      type="datetime-local" 
                      class="form-control fw-bold" 
                      v-model="form.countdown_target_date"
                    />
                    <small class="text-muted fs-11">Waktu acuan zona WIB (Waktu Indonesia Barat).</small>
                  </div>

                  <!-- Quick Presets -->
                  <div class="col-md-5">
                    <label class="form-label fs-12 fw-semibold text-muted">Preset Cepat Waktu:</label>
                    <div class="d-flex gap-1 flex-wrap">
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="setCountdownPreset(7)"
                      >
                        +7 Hari
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="setCountdownPreset(14)"
                      >
                        +14 Hari
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="setCountdownPreset(30)"
                      >
                        +30 Hari
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-soft-secondary"
                        @click="form.countdown_target_date = '2026-10-24T23:59'"
                      >
                        24 Okt 2026
                      </button>
                    </div>
                  </div>

                  <!-- Teks Saat Waktu Habis -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Ketika Waktu Habis (Ended State)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.countdown_ended_text" 
                      placeholder="Contoh: Pendaftaran Gelombang Utama Resmi Ditutup!"
                    />
                  </div>

                  <!-- Tampilkan Detik -->
                  <div class="col-12">
                    <div class="form-check form-switch">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        id="showSecondsSwitch"
                        v-model="form.countdown_show_seconds"
                      />
                      <label class="form-check-label fs-13" for="showSecondsSwitch">
                        Tampilkan kotak hitung mundur satuan detik (Hari, Jam, Menit, Detik)
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 5: EFEK VISUAL & INTERAKSI WEB                          -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'visual'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 section-title-text">Efek Visual & Interaksi Website</h5>
                    <p class="fs-12 text-muted mb-0">Partikel atmosfer kemeriahan acara dan tombol kenyamanan navigasi.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="visualParticlesActiveSwitch"
                      v-model="form.visual_particles_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="visualParticlesActiveSwitch">
                      {{ form.visual_particles_active ? 'Efek Partikel Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Pilihan Tipe Partikel -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Pilih Tema Partikel Latar Belakang</label>
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label 
                          class="card border p-3 cursor-pointer h-100 text-center mb-0"
                          :class="{ 'border-warning bg-warning-subtle text-warning-emphasis': form.visual_particles_type === 'glitter' }"
                        >
                          <input type="radio" value="glitter" v-model="form.visual_particles_type" class="d-none">
                          <i class="ri-sparkling-fill fs-24 mb-2 d-block"></i>
                          <div class="fw-bold fs-13">Gold Glitter</div>
                          <small class="text-muted fs-11">Kilau bintang emas mewah bertema Semarak</small>
                        </label>
                      </div>

                      <div class="col-md-4">
                        <label 
                          class="card border p-3 cursor-pointer h-100 text-center mb-0"
                          :class="{ 'border-primary bg-primary-subtle text-primary-emphasis': form.visual_particles_type === 'confetti' }"
                        >
                          <input type="radio" value="confetti" v-model="form.visual_particles_type" class="d-none">
                          <i class="ri-gift-line fs-24 mb-2 d-block"></i>
                          <div class="fw-bold fs-13">Festival Confetti</div>
                          <small class="text-muted fs-11">Taburan pita warna-warni pesta perayaan</small>
                        </label>
                      </div>

                      <div class="col-md-4">
                        <label 
                          class="card border p-3 cursor-pointer h-100 text-center mb-0"
                          :class="{ 'border-info bg-info-subtle text-info-emphasis': form.visual_particles_type === 'snow' }"
                        >
                          <input type="radio" value="snow" v-model="form.visual_particles_type" class="d-none">
                          <i class="ri-snowflake-line fs-24 mb-2 d-block"></i>
                          <div class="fw-bold fs-13">Soft Glow Snow</div>
                          <small class="text-muted fs-11">Pendaran partikel lembut mengambang tenang</small>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Back To Top Button Toggle -->
                  <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <div class="fw-bold fs-13 section-title-text">Tombol Kembali ke Atas (Back to Top)</div>
                        <small class="text-muted fs-12">Menampilkan tombol bulat dengan indikator progres scroll melingkar di sudut kanan bawah.</small>
                      </div>
                      <div class="form-check form-switch form-switch-md mb-0">
                        <input 
                          class="form-check-input cursor-pointer" 
                          type="checkbox" 
                          id="backToTopSwitch"
                          v-model="form.back_to_top_active"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Animasi Preload Halaman Publik (Cold Start & Refresh) -->
                  <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <div>
                        <div class="fw-bold fs-13 section-title-text d-flex align-items-center gap-2">
                          <i class="ri-loader-3-line text-primary fs-16"></i>
                          <span>Animasi Preload Web Publik (Akses Pertama & Refresh)</span>
                          <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-10 px-2 py-0.5 rounded-pill">Rekomendasi</span>
                        </div>
                        <small class="text-muted fs-12">Menampilkan transisi logo & spinner orbital saat pengunjung pertama kali membuka website atau me-refresh browser.</small>
                      </div>
                      <div class="form-check form-switch form-switch-md mb-0">
                        <input 
                          class="form-check-input cursor-pointer" 
                          type="checkbox" 
                          id="preloaderActiveSwitch"
                          v-model="form.preloader_active"
                        />
                      </div>
                    </div>

                    <div v-if="form.preloader_active" class="mt-3 p-3 rounded-3 border bg-light-subtle">
                      <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                          <label class="form-label fs-12 fw-semibold text-muted mb-1" for="preloaderTextInput">
                            Teks Indikator Status Loading
                          </label>
                          <div class="input-group input-group-sm">
                            <span class="input-group-text bg-body border-end-0 text-muted">
                              <i class="ri-chat-voice-line"></i>
                            </span>
                            <input 
                              type="text" 
                              id="preloaderTextInput" 
                              class="form-control" 
                              v-model="form.preloader_text" 
                              placeholder="Contoh: Memuat keseruan Semarak Aliyah..."
                              maxlength="100"
                            />
                          </div>
                          <small class="text-muted fs-11 mt-1 d-block">Teks ini akan tampil tepat di bawah progress bar animasi dengan efek titik berdenyut.</small>
                        </div>

                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                          <label class="form-label fs-12 fw-semibold text-muted d-none d-md-block">&nbsp;</label>
                          <button 
                            type="button" 
                            class="btn btn-sm btn-soft-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
                            @click="triggerPreloadPreview"
                            :disabled="isPreloaderPreviewing"
                          >
                            <i class="ri-play-circle-line fs-14"></i>
                            <span>{{ isPreloaderPreviewing ? 'Sedang Memutar...' : 'Uji Coba Animasi' }}</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 6: INJEKSI KODE KUSTOM (CUSTOM CSS & JS)                -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'code'">
                <div class="mb-3 pb-2 border-bottom">
                  <h5 class="fs-14 fw-bold mb-1 section-title-text">Injeksi Kode Kustom (CSS & Scripting)</h5>
                  <p class="fs-12 text-muted mb-0">Tambahkan aturan styling CSS kustom, tag analitik Google/Meta, atau skrip widget tanpa merusak struktur file asli.</p>
                </div>

                <div class="alert alert-warning-subtle border border-warning-subtle p-2 fs-12 mb-3">
                  <i class="ri-alert-line me-1"></i>
                  <strong>Perhatian:</strong> Pastikan kode JavaScript atau CSS yang Anda masukkan valid dan aman. Kode hanya dieksekusi pada website publik.
                </div>

                <div class="row g-3">
                  <!-- Custom CSS -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted d-flex align-items-center justify-content-between">
                      <span>Custom CSS Stylesheet (Otomatis dibungkus tag &lt;style&gt;)</span>
                      <span class="badge bg-secondary-subtle text-secondary font-monospace">CSS</span>
                    </label>
                    <textarea 
                      class="form-control font-monospace fs-12 custom-code-textarea" 
                      rows="5" 
                      v-model="form.custom_css"
                      placeholder="/* Contoh kustomisasi CSS */&#10;.brand-title { letter-spacing: 2px; }&#10;.btn-primary { border-radius: 50px; }"
                      spellcheck="false"
                    ></textarea>
                  </div>

                  <!-- Header Script Injection -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted d-flex align-items-center justify-content-between">
                      <span>Injeksi Script Header (&lt;head&gt; — Google Analytics / Meta Pixel)</span>
                      <span class="badge bg-primary-subtle text-primary font-monospace">&lt;head&gt;</span>
                    </label>
                    <textarea 
                      class="form-control font-monospace fs-12 custom-code-textarea" 
                      rows="4" 
                      v-model="form.custom_head_script"
                      placeholder="<!-- Masukkan tag tracking Google Analytics GA4 / Google Tag Manager di sini -->&#10;<script async src='https://www.googletagmanager.com/gtag/js?id=G-XXXXX'></script>"
                      spellcheck="false"
                    ></textarea>
                  </div>

                  <!-- Footer / Body Script Injection -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted d-flex align-items-center justify-content-between">
                      <span>Injeksi Script Footer (Sebelum &lt;/body&gt; — Chat Widget / Third-Party)</span>
                      <span class="badge bg-success-subtle text-success font-monospace">&lt;body&gt;</span>
                    </label>
                    <textarea 
                      class="form-control font-monospace fs-12 custom-code-textarea" 
                      rows="4" 
                      v-model="form.custom_body_script"
                      placeholder="<!-- Masukkan skrip widget pihak ketiga seperti Tawk.to, Crisp, dsb -->&#10;<script>/* custom code */</script>"
                      spellcheck="false"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Footer Submit -->
            <div class="card-footer kustomisasi-card-footer py-3 d-flex align-items-center justify-content-between">
              <span class="text-muted fs-12">
                <i class="ri-shield-check-line text-success me-1"></i> Data disimpan aman di database setting sistem.
              </span>
              <button 
                type="button" 
                class="btn btn-primary px-4 shadow-sm"
                @click="saveCustomization"
                :disabled="isSaving"
              >
                <i class="ri-save-line me-1"></i>
                {{ isSaving ? 'Menyimpan...' : 'Simpan Kustomisasi Lanjutan' }}
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT: INTERACTIVE LIVE PREVIEW PANEL -->
        <div class="col-lg-5">
          <div class="card border-0 shadow-sm sticky-top" style="top: 85px;">
            <div class="card-header kustomisasi-preview-header py-3 d-flex align-items-center justify-content-between border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-eye-line text-primary fs-16"></i>
                <h5 class="card-title mb-0 fs-13 fw-bold section-title-text">Pratinjau Langsung (Live Preview)</h5>
              </div>
              <span class="badge preview-badge-realtime fs-10">Real-time</span>
            </div>

            <div class="card-body p-4 preview-body-area">
              <!-- Hero Section Preview Box -->
              <div v-if="activeTab === 'hero'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Hero Section (Layar Utama):</div>
                <div class="p-3 rounded-3 shadow-sm border preview-hero-box" style="background: linear-gradient(135deg, #090d16 0%, #111827 50%, #1e1b4b 100%); color: #f8fafc;">
                  <!-- Hero Badge -->
                  <div class="mb-2">
                    <span class="badge rounded-pill px-2.5 py-1 text-warning border border-warning border-opacity-25" style="background: rgba(245, 158, 11, 0.1); font-size: 10px;">
                      <span v-if="form.hero_badge_pulse" class="d-inline-block rounded-circle bg-warning me-1" style="width: 6px; height: 6px; animation: pulse 1.5s infinite;"></span>
                      {{ form.hero_badge_text || 'Ajang Kompetisi Pelajar SMP/MTs' }}
                    </span>
                  </div>

                  <!-- Headline -->
                  <div class="fw-bold fs-13 mb-1" style="line-height: 1.3;">
                    {{ form.hero_headline_prefix || 'Selamat Datang di Laman Official' }} <br>
                    <span class="text-warning fw-extrabold" style="color: #fbbf24 !important;">{{ form.hero_headline_highlight || ($page.props.schoolProfile?.event_name || 'SEMARAK ALIYAH XIV 2026') }}</span>
                  </div>

                  <!-- Subheadline -->
                  <div class="fs-11 text-light opacity-75 mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35;">
                    {{ form.hero_subheadline || 'Panggung unjuk prestasi, bakat seni, kecerdasan sains, dan sportivitas pelajar...' }}
                  </div>

                  <!-- CTA Buttons -->
                  <div class="d-flex flex-wrap gap-1 mb-3">
                    <span v-if="form.hero_cta1_active" class="btn btn-warning btn-xs fw-bold px-2 py-0.5 rounded text-dark fs-10">
                      <i class="ri-user-follow-line me-1"></i>{{ form.hero_cta1_text || 'Daftar' }}
                    </span>
                    <span v-if="form.hero_cta2_active" class="btn btn-outline-light btn-xs fw-semibold px-2 py-0.5 rounded fs-10">
                      <i class="ri-file-download-line me-1"></i>{{ form.hero_cta2_text || 'Juknis' }}
                    </span>
                    <span v-if="form.hero_cta3_active" class="btn btn-outline-light btn-xs fw-semibold px-2 py-0.5 rounded fs-10">
                      <i class="ri-search-line me-1"></i>{{ form.hero_cta3_text || 'Lomba' }}
                    </span>
                  </div>

                  <!-- Visual Strip -->
                  <div class="d-flex align-items-center gap-2 p-2 rounded bg-black bg-opacity-30 border border-white border-opacity-10">
                    <div class="flex-shrink-0 position-relative" style="width: 48px; height: 64px; border-radius: 4px; overflow: hidden; background: #1e293b;">
                      <img :src="effectivePosterImage" alt="Poster" style="width: 100%; height: 100%; object-fit: cover;">
                      <img 
                        v-if="form.hero_mascot_active" 
                        :src="effectiveMascotImage" 
                        alt="Mascot" 
                        class="position-absolute bottom-0 end-0" 
                        style="width: 24px; height: 24px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));"
                      >
                    </div>
                    <div class="flex-grow-1 min-w-0">
                      <div v-if="form.hero_float_badge_active" class="d-flex align-items-center gap-1.5">
                        <i :class="form.hero_float_badge_icon || 'ri-trophy-line'" class="text-warning fs-14"></i>
                        <div>
                          <div class="fw-bold fs-11 text-white lh-1">{{ form.hero_float_badge_title || 'Hadiah Jutaan Rupiah' }}</div>
                          <div class="fs-10 text-white-50">{{ form.hero_float_badge_desc || '+ Beasiswa Pendidikan' }}</div>
                        </div>
                      </div>
                      <div v-if="form.hero_mascot_active" class="mt-1 fs-10 text-info text-truncate">
                        <i class="ri-user-smile-line me-1"></i>Maskot: {{ filePreviewMascotUrl ? (form.hero_mascot_file?.name || 'File Baru') : (form.hero_mascot_image ? form.hero_mascot_image.split('/').pop() : 'kadita.png') }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Stats Strip Preview Box -->
              <div v-else-if="activeTab === 'stats'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Strip Statistik:</div>
                <div v-if="form.stats_active" class="p-3 rounded-3 shadow-sm border preview-stats-box" style="background: rgba(99, 102, 241, 0.04);">
                  <div class="row g-2">
                    <div v-for="(st, idx) in form.stats_items" :key="idx" class="col-6">
                      <div class="p-2.5 rounded border text-center h-100 preview-stat-card">
                        <div class="avatar-xs mx-auto mb-1 rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                          <i :class="st.icon || 'ri-star-line'" class="fs-14"></i>
                        </div>
                        <div class="fw-bold fs-12 text-primary lh-1 mb-0.5">{{ st.number || '-' }}</div>
                        <div class="text-muted fs-10 lh-sm">{{ st.label || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-4 rounded border text-muted fs-12 preview-empty-box">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Section Statistik saat ini dinonaktifkan
                </div>
              </div>

              <!-- Tentang & Hadiah Preview Box -->
              <div v-else-if="activeTab === 'about'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Seksi Tentang & Hadiah:</div>
                <div v-if="form.about_active" class="p-3 rounded-3 shadow-sm border preview-about-box" style="background: rgba(245, 158, 11, 0.04);">
                  <div class="text-center mb-2.5">
                    <span class="badge bg-warning-subtle text-warning-emphasis fs-10 mb-1">
                      {{ form.about_tag || 'Apresiasi & Fasilitas' }}
                    </span>
                    <h6 class="fw-bold fs-13 section-title-text mb-1">
                      {{ form.about_title || 'Dapatkan Hadiah Bergengsi' }}
                    </h6>
                    <p class="fs-11 text-muted mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.35;">
                      {{ form.about_desc || 'Bentuk apresiasi nyata bagi generasi muda...' }}
                    </p>
                  </div>

                  <div class="row g-2">
                    <div v-for="(pr, idx) in form.about_items" :key="idx" class="col-6">
                      <div class="p-2 rounded border h-100 preview-prize-card">
                        <div class="d-flex align-items-center gap-1.5 mb-1">
                          <i :class="pr.icon || 'ri-trophy-line'" class="text-warning fs-14"></i>
                          <div class="fw-bold fs-11 section-title-text text-truncate">{{ pr.title || 'Hadiah' }}</div>
                        </div>
                        <div class="text-muted fs-10 lh-sm" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{ pr.desc || '-' }}</div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-4 rounded border text-muted fs-12 preview-empty-box">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Section Tentang & Hadiah saat ini dinonaktifkan
                </div>
              </div>

              <!-- Banner Preview Box -->
              <div v-else-if="activeTab === 'banner'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Banner Mengambang:</div>
                <div 
                  v-if="form.announcement_banner_active" 
                  class="p-3 rounded shadow-sm text-center position-relative overflow-hidden"
                  :class="getBannerClass(form.announcement_banner_type)"
                >
                  <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 fs-12 fw-semibold">
                    <span>{{ form.announcement_banner_text || '📢 Teks pengumuman akan tampil di sini...' }}</span>
                    <span 
                      v-if="form.announcement_banner_btn_text" 
                      class="badge bg-white text-dark shadow-sm px-2 py-1 cursor-pointer"
                    >
                      {{ form.announcement_banner_btn_text }} &rarr;
                    </span>
                  </div>
                  <button 
                    v-if="form.announcement_banner_dismissable" 
                    type="button" 
                    class="btn-close btn-close-white position-absolute top-50 end-0 translate-middle-y me-2"
                    style="font-size: 8px;"
                    title="Simulasi Tutup"
                  ></button>
                </div>
                <div v-else class="text-center py-4 rounded border text-muted fs-12 preview-empty-box">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Banner saat ini dalam status Nonaktif
                </div>
              </div>

              <!-- Pop-up Preview Box -->
              <div v-else-if="activeTab === 'popup'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Pop-up Pengumuman:</div>
                <div v-if="form.announcement_popup_active" class="rounded-3 shadow border overflow-hidden preview-popup-box">
                  <div v-if="form.announcement_popup_image" class="w-100 bg-dark text-center" style="max-height: 140px; overflow: hidden;">
                    <img :src="form.announcement_popup_image" alt="Poster" class="img-fluid" style="object-fit: cover; width: 100%; height: 140px;" @error="previewImageError = true">
                  </div>
                  <div class="p-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis fs-10 mb-2">
                      {{ form.announcement_popup_badge || 'Pengumuman Resmi' }}
                    </span>
                    <h6 class="fw-bold fs-13 section-title-text mb-2">
                      {{ form.announcement_popup_title || 'Judul Pengumuman Pop-up' }}
                    </h6>
                    <p class="fs-12 text-muted mb-3 line-clamp-3" style="max-height: 70px; overflow-y: auto;">
                      {{ form.announcement_popup_content || 'Isi teks pengumuman yang akan dibaca oleh pengunjung website...' }}
                    </p>
                    <div class="d-flex gap-2 justify-content-end">
                      <span class="btn btn-sm btn-light fs-11">Tutup</span>
                      <span class="btn btn-sm btn-primary fs-11">
                        {{ form.announcement_popup_btn_text || 'Buka Tautan' }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-4 rounded border text-muted fs-12 preview-empty-box">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Pop-up modal saat ini dalam status Nonaktif
                </div>
              </div>

              <!-- Floating WA Preview Box -->
              <div v-else-if="activeTab === 'wa'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Tombol WhatsApp:</div>
                <div class="p-4 rounded border shadow-sm position-relative text-center preview-wa-display-box" style="min-height: 200px;">
                  <span class="text-muted fs-12 d-block mb-3">Tampilan di pojok browser publik:</span>
                  
                  <div 
                    v-if="form.floating_wa_active"
                    class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-pill shadow-lg text-white"
                    :class="[
                      form.floating_wa_animation === 'pulse' ? 'anim-pulse' : 
                      form.floating_wa_animation === 'bounce' ? 'anim-bounce' : ''
                    ]"
                    style="background: #22c55e;"
                  >
                    <i class="ri-whatsapp-fill fs-22"></i>
                    <span class="fs-12 fw-bold">{{ form.floating_wa_label || 'Bantuan Panitia' }}</span>
                  </div>
                  <div v-else class="text-muted fs-12 py-3">
                    <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                    Tombol mengambang WA dinonaktifkan
                  </div>

                  <div class="mt-4 pt-3 border-top text-start fs-11 text-muted">
                    <div>Target WA: <strong>{{ form.floating_wa_number || '-' }}</strong></div>
                    <div>Posisi: <strong>{{ form.floating_wa_position === 'bottom-right' ? 'Kanan Bawah' : 'Kiri Bawah' }}</strong></div>
                  </div>
                </div>
              </div>

              <!-- Countdown Preview Box -->
              <div v-else-if="activeTab === 'countdown'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Tampilan Hitung Mundur:</div>
                <div v-if="form.countdown_active" class="p-3 rounded-3 text-white shadow" style="background: linear-gradient(135deg, #1e1b4b, #312e81);">
                  <div class="d-flex align-items-center gap-1 text-warning fs-11 fw-bold mb-2">
                    <i class="ri-time-line"></i>
                    <span>{{ form.countdown_title || 'Batas Pendaftaran Ditutup Dalam:' }}</span>
                  </div>

                  <div class="d-flex gap-2 justify-content-center my-2">
                    <div class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">05</div>
                      <div class="fs-10 text-white-50">Hari</div>
                    </div>
                    <div class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">14</div>
                      <div class="fs-10 text-white-50">Jam</div>
                    </div>
                    <div class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">32</div>
                      <div class="fs-10 text-white-50">Menit</div>
                    </div>
                    <div v-if="form.countdown_show_seconds" class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">48</div>
                      <div class="fs-10 text-white-50">Detik</div>
                    </div>
                  </div>

                  <div class="text-center text-white-50 fs-10 mt-2">
                    Target: {{ form.countdown_target_date || '2026-10-24T23:59' }}
                  </div>
                </div>
                <div v-else class="text-center py-4 rounded border text-muted fs-12 preview-empty-box">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Countdown timer dinonaktifkan
                </div>
              </div>

              <!-- Visual / Code Preview Box -->
              <div v-else class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Informasi Penerapan:</div>
                <div class="p-3 rounded border fs-12 preview-info-box">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-checkbox-circle-fill text-success fs-16"></i>
                    <span class="fw-semibold section-title-text">Partikel Efek:</span>
                    <span class="badge bg-primary-subtle text-primary">{{ form.visual_particles_active ? form.visual_particles_type : 'Mati' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-checkbox-circle-fill text-success fs-16"></i>
                    <span class="fw-semibold section-title-text">Back To Top:</span>
                    <span class="badge" :class="form.back_to_top_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">{{ form.back_to_top_active ? 'Aktif' : 'Mati' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-checkbox-circle-fill text-success fs-16"></i>
                    <span class="fw-semibold section-title-text">Animasi Preload:</span>
                    <span class="badge" :class="form.preloader_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">{{ form.preloader_active ? 'Aktif' : 'Mati' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-code-line text-info fs-16"></i>
                    <span class="fw-semibold section-title-text">Custom CSS:</span>
                    <span class="badge preview-badge-realtime border">{{ form.custom_css ? form.custom_css.length + ' karakter' : 'Kosong' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ri-javascript-line text-warning fs-16"></i>
                    <span class="fw-semibold section-title-text">Script Eksternal:</span>
                    <span class="badge preview-badge-realtime border">{{ (form.custom_head_script || form.custom_body_script) ? 'Terpasang' : 'Kosong' }}</span>
                  </div>
                </div>
              </div>

              <!-- Quick Tips -->
              <div class="mt-4 p-3 rounded border preview-tips-card">
                <div class="fw-bold fs-12 section-title-text mb-1 d-flex align-items-center gap-1">
                  <i class="ri-lightbulb-line text-warning"></i> Tips Kustomisasi:
                </div>
                <p class="fs-11 text-muted mb-0">
                  Gunakan banner untuk mengumumkan pengumuman kuota lomba yang hampir habis. Gunakan pop-up modal menjelang hari H Technical Meeting agar seluruh delegasi sekolah mendapatkan info terkini.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- RESET CONFIRMATION MODAL -->
    <div v-if="isResetModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isResetModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-danger-subtle py-3">
            <h5 class="modal-title fw-bold fs-15 text-danger d-flex align-items-center gap-2">
              <i class="ri-error-warning-line"></i>
              Konfirmasi Reset Standar Awal
            </h5>
            <button type="button" class="btn-close" @click="isResetModalOpen = false"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <i class="ri-refresh-line fs-48 text-warning d-block mb-3"></i>
            <h5 class="fw-bold fs-16 mb-2">Kembalikan Semua Pengaturan ke Standar?</h5>
            <p class="text-muted fs-13 mb-0">
              Tindakan ini akan mengembalikan teks banner, pop-up, nomor WA, countdown, dan kode kustom ke pengaturan awal bawaan Semarak Aliyah XIV.
            </p>
          </div>
          <div class="modal-footer kustomisasi-card-footer py-2">
            <button type="button" class="btn btn-light btn-sm" @click="isResetModalOpen = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-danger btn-sm px-4" 
              :disabled="isResetting"
              @click="submitReset"
            >
              {{ isResetting ? 'Mereset...' : 'Ya, Kembalikan ke Standar' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ======================================================================
         PREVIEW OVERLAY FOR PRELOADER ANIMATION
         ====================================================================== -->
    <Teleport to="body">
      <div 
        v-if="isPreloaderPreviewing" 
        id="public-preloader" 
        class="public-preloader"
        style="position: fixed; inset: 0; z-index: 99999999;"
        aria-hidden="true"
      >
        <div class="preloader-backdrop"></div>
        <div class="preloader-glow-orb preloader-glow-1"></div>
        <div class="preloader-glow-orb preloader-glow-2"></div>
        
        <div class="preloader-stage">
          <div class="preloader-logo-area">
            <div class="preloader-orbit-outer"></div>
            <div class="preloader-orbit-inner"></div>
            <div class="preloader-logo-halo"></div>
            <div class="preloader-logo-badge">
              <img src="/assets/img/logo.png" alt="Logo Semarak" class="preloader-logo-img" />
            </div>
          </div>

          <div class="preloader-brand-box">
            <div class="preloader-badge-tag">
              <span class="preloader-sparkle">✦</span>
              <span>SEMARAK ALIYAH XIV 2026</span>
              <span class="preloader-sparkle">✦</span>
            </div>
            <h1 class="preloader-school-title">MA Unggulan Wahid Hasyim Balung</h1>
          </div>

          <div class="preloader-progress-wrap">
            <div class="preloader-progress-track">
              <div class="preloader-progress-bar" style="width: 85%;">
                <div class="preloader-shimmer"></div>
              </div>
            </div>
            <div class="preloader-status-text">
              <span class="preloader-status-msg">{{ form.preloader_text || 'Memuat keseruan Semarak Aliyah...' }}</span>
              <span class="preloader-dot-pulse">
                <span>.</span><span>.</span><span>.</span>
              </span>
            </div>
          </div>

          <div class="mt-4">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-light rounded-pill px-3 py-1 fs-11"
              @click="isPreloaderPreviewing = false"
            >
              <i class="ri-close-line me-1"></i> Selesai Preview
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </KustomisasiLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import KustomisasiLayout from '@/Layouts/KustomisasiLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
});

const { success, error } = useToast();

// Active Tab: 'hero' | 'stats' | 'about' | 'banner' | 'popup' | 'wa' | 'countdown' | 'visual' | 'code'
const activeTab = ref('hero');

const filePreviewPosterUrl = ref(null);
const filePreviewMascotUrl = ref(null);
const posterFileInputRef = ref(null);
const mascotFileInputRef = ref(null);
const showManualPosterInput = ref(false);
const showManualMascotInput = ref(false);

const defaultStatsItems = [
  { icon: 'ri-focus-2-line', number: '8 Cabang', label: 'Kompetisi & Olahraga' },
  { icon: 'ri-building-line', number: '50+ Sekolah', label: 'SMP/MTs se-Jember' },
  { icon: 'ri-hand-coin-line', number: 'Jutaan Rupiah', label: 'Uang Pembinaan & Hadiah' },
  { icon: 'ri-award-line', number: '100% Sertifikat', label: 'Peserta & Guru Pendamping' }
];

const defaultAboutPrizes = [
  { icon: 'ri-trophy-line', title: 'Piala Juara', desc: 'Trophy eksklusif dan bergengsi untuk Juara 1, Juara 2, dan Juara 3 pada setiap cabang kompetisi.' },
  { icon: 'ri-money-dollar-circle-line', title: 'Uang Pembinaan', desc: 'Bantuan dana pembinaan jutaan rupiah untuk mendukung pengembangan bakat dan prestasi sang juara.' },
  { icon: 'ri-file-shield-line', title: 'E-Piagam & Sertifikat', desc: 'Piagam penghargaan resmi ber-barcode untuk seluruh Peserta, Juara, dan Guru Pembimbing / Pendamping.' },
  { icon: 'ri-graduation-cap-line', title: 'Beasiswa Pendidikan', desc: 'Jalur beasiswa penuh dan tabungan pendidikan masuk di MA Unggulan Wahid Hasyim Balung, Jember.' }
];

// Form state
const form = ref({
  // Hero Section
  hero_badge_text: props.settings.hero_badge_text || 'Ajang Kompetisi Pelajar SMP/MTs Se-Kabupaten Jember',
  hero_badge_pulse: props.settings.hero_badge_pulse !== false,
  hero_headline_prefix: props.settings.hero_headline_prefix || 'Selamat Datang di Laman Official',
  hero_headline_highlight: props.settings.hero_headline_highlight || '',
  hero_subheadline: props.settings.hero_subheadline || 'Panggung unjuk prestasi, bakat seni, kecerdasan sains, dan sportivitas pelajar SMP/MTs sederajat se-Kabupaten Jember. Raih Piala Juara, Bantuan Uang Pembinaan, serta Beasiswa Tabungan Pendidikan di MA Unggulan Wahid Hasyim Balung.',
  hero_cta1_text: props.settings.hero_cta1_text || 'Daftar Sekarang',
  hero_cta1_url: props.settings.hero_cta1_url || '',
  hero_cta1_active: props.settings.hero_cta1_active !== false,
  hero_cta2_text: props.settings.hero_cta2_text || 'Unduh Juknis (PDF)',
  hero_cta2_url: props.settings.hero_cta2_url || '',
  hero_cta2_active: props.settings.hero_cta2_active !== false,
  hero_cta3_text: props.settings.hero_cta3_text || 'Lihat Cabang Lomba',
  hero_cta3_url: props.settings.hero_cta3_url || '#kompetisi',
  hero_cta3_active: props.settings.hero_cta3_active !== false,
  hero_poster_image: props.settings.hero_poster_image || '/assets/img/pamflet.jpg',
  hero_poster_file: null,
  hero_mascot_image: props.settings.hero_mascot_image || '/assets/img/kadita.png',
  hero_mascot_file: null,
  hero_mascot_active: props.settings.hero_mascot_active !== false,
  hero_float_badge_active: props.settings.hero_float_badge_active !== false,
  hero_float_badge_icon: props.settings.hero_float_badge_icon || 'ri-trophy-line',
  hero_float_badge_title: props.settings.hero_float_badge_title || 'Hadiah Jutaan Rupiah',
  hero_float_badge_desc: props.settings.hero_float_badge_desc || '+ Beasiswa Pendidikan',

  // Stats Section
  stats_active: props.settings.stats_active !== false,
  stats_items: Array.isArray(props.settings.stats_items) && props.settings.stats_items.length > 0 
    ? JSON.parse(JSON.stringify(props.settings.stats_items)) 
    : JSON.parse(JSON.stringify(defaultStatsItems)),

  // Tentang Section
  about_active: props.settings.about_active !== false,
  about_tag: props.settings.about_tag || 'Apresiasi & Fasilitas',
  about_title: props.settings.about_title || 'Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan',
  about_desc: props.settings.about_desc || 'Semarak Aliyah diselenggarakan oleh MA Unggulan Wahid Hasyim Balung sebagai bentuk apresiasi nyata bagi generasi muda yang berprestasi dan berkarakter.',
  about_items: Array.isArray(props.settings.about_items) && props.settings.about_items.length > 0 
    ? JSON.parse(JSON.stringify(props.settings.about_items)) 
    : JSON.parse(JSON.stringify(defaultAboutPrizes)),

  announcement_banner_active: props.settings.announcement_banner_active !== false,
  announcement_banner_text: props.settings.announcement_banner_text || '',
  announcement_banner_type: props.settings.announcement_banner_type || 'gold',
  announcement_banner_btn_text: props.settings.announcement_banner_btn_text || '',
  announcement_banner_btn_url: props.settings.announcement_banner_btn_url || '',
  announcement_banner_position: props.settings.announcement_banner_position || 'top',
  announcement_banner_dismissable: props.settings.announcement_banner_dismissable !== false,

  announcement_popup_active: props.settings.announcement_popup_active === true,
  announcement_popup_frequency: props.settings.announcement_popup_frequency || 'once_per_session',
  announcement_popup_title: props.settings.announcement_popup_title || '',
  announcement_popup_badge: props.settings.announcement_popup_badge || '',
  announcement_popup_image: props.settings.announcement_popup_image || '',
  announcement_popup_content: props.settings.announcement_popup_content || '',
  announcement_popup_btn_text: props.settings.announcement_popup_btn_text || '',
  announcement_popup_btn_url: props.settings.announcement_popup_btn_url || '',

  floating_wa_active: props.settings.floating_wa_active !== false,
  floating_wa_number: props.settings.floating_wa_number || '0838 5007 0767',
  floating_wa_message: props.settings.floating_wa_message || '',
  floating_wa_label: props.settings.floating_wa_label || 'Bantuan Panitia',
  floating_wa_position: props.settings.floating_wa_position || 'bottom-right',
  floating_wa_animation: props.settings.floating_wa_animation || 'pulse',

  countdown_active: props.settings.countdown_active !== false,
  countdown_title: props.settings.countdown_title || 'Batas Akhir Pendaftaran & TM Ditutup Dalam:',
  countdown_target_date: props.settings.countdown_target_date || '2026-10-24T23:59',
  countdown_ended_text: props.settings.countdown_ended_text || 'Pendaftaran Resmi Ditutup!',
  countdown_show_seconds: props.settings.countdown_show_seconds !== false,

  custom_css: props.settings.custom_css || '',
  custom_head_script: props.settings.custom_head_script || '',
  custom_body_script: props.settings.custom_body_script || '',

  visual_particles_active: props.settings.visual_particles_active !== false,
  visual_particles_type: props.settings.visual_particles_type || 'glitter',
  back_to_top_active: props.settings.back_to_top_active !== false,
  preloader_active: props.settings.preloader_active !== false,
  preloader_text: props.settings.preloader_text || 'Memuat keseruan Semarak Aliyah...',
});

const isSaving = ref(false);
const isResetModalOpen = ref(false);
const isResetting = ref(false);
const previewImageError = ref(false);
const isPreloaderPreviewing = ref(false);

function triggerPreloadPreview() {
  isPreloaderPreviewing.value = true;
  setTimeout(() => {
    isPreloaderPreviewing.value = false;
  }, 2500);
}

function addStatItem() {
  form.value.stats_items.push({
    icon: 'ri-star-line',
    number: 'Baru',
    label: 'Keterangan Statistik'
  });
}

function removeStatItem(idx) {
  form.value.stats_items.splice(idx, 1);
}

function addAboutItem() {
  form.value.about_items.push({
    icon: 'ri-gift-line',
    title: 'Fasilitas / Hadiah Baru',
    desc: 'Deskripsi fasilitas atau apresiasi yang diberikan kepada delegasi.'
  });
}

function removeAboutItem(idx) {
  form.value.about_items.splice(idx, 1);
}

function setCountdownPreset(daysAhead) {
  const date = new Date();
  date.setDate(date.getDate() + daysAhead);
  date.setHours(23, 59, 0, 0);
  
  const pad = (n) => String(n).padStart(2, '0');
  const formatted = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  form.value.countdown_target_date = formatted;
}

function getBannerClass(type) {
  switch (type) {
    case 'gold':
      return 'bg-gradient-gold text-dark';
    case 'primary':
      return 'bg-primary text-white';
    case 'success':
      return 'bg-success text-white';
    case 'danger':
      return 'bg-danger text-white';
    case 'info':
      return 'bg-info text-white';
    default:
      return 'bg-dark text-white';
  }
}

// Computed Effective Image URLs for live preview
const effectivePosterImage = computed(() => {
  if (filePreviewPosterUrl.value) return filePreviewPosterUrl.value;
  return form.value.hero_poster_image || '/assets/img/pamflet.jpg';
});

const effectiveMascotImage = computed(() => {
  if (filePreviewMascotUrl.value) return filePreviewMascotUrl.value;
  return form.value.hero_mascot_image || '/assets/img/kadita.png';
});

function handlePosterFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    error('Ukuran berkas poster pamflet melebihi batas maksimal 10MB.', 'Berkas Terlalu Besar');
    if (posterFileInputRef.value) posterFileInputRef.value.value = '';
    return;
  }

  form.value.hero_poster_file = file;
  if (filePreviewPosterUrl.value) {
    URL.revokeObjectURL(filePreviewPosterUrl.value);
  }
  filePreviewPosterUrl.value = URL.createObjectURL(file);
  success('Berkas poster dipilih! Klik "Simpan Kustomisasi" untuk menerapkan.', 'Berkas Poster Siap');
}

function removeSelectedPosterFile() {
  form.value.hero_poster_file = null;
  if (filePreviewPosterUrl.value) {
    URL.revokeObjectURL(filePreviewPosterUrl.value);
    filePreviewPosterUrl.value = null;
  }
  if (posterFileInputRef.value) {
    posterFileInputRef.value.value = '';
  }
}

function resetPosterToDefault() {
  removeSelectedPosterFile();
  form.value.hero_poster_image = '/assets/img/pamflet.jpg';
  success('Gambar poster dikembalikan ke pamflet bawaan sistem.');
}

function handleMascotFileChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 10 * 1024 * 1024) {
    error('Ukuran berkas maskot melebihi batas maksimal 10MB.', 'Berkas Terlalu Besar');
    if (mascotFileInputRef.value) mascotFileInputRef.value.value = '';
    return;
  }

  form.value.hero_mascot_file = file;
  if (filePreviewMascotUrl.value) {
    URL.revokeObjectURL(filePreviewMascotUrl.value);
  }
  filePreviewMascotUrl.value = URL.createObjectURL(file);
  success('Berkas maskot dipilih! Klik "Simpan Kustomisasi" untuk menerapkan.', 'Berkas Maskot Siap');
}

function removeSelectedMascotFile() {
  form.value.hero_mascot_file = null;
  if (filePreviewMascotUrl.value) {
    URL.revokeObjectURL(filePreviewMascotUrl.value);
    filePreviewMascotUrl.value = null;
  }
  if (mascotFileInputRef.value) {
    mascotFileInputRef.value.value = '';
  }
}

function resetMascotToDefault() {
  removeSelectedMascotFile();
  form.value.hero_mascot_image = '/assets/img/kadita.png';
  success('Gambar maskot dikembalikan ke karakter bawaan sistem.');
}

function saveCustomization() {
  isSaving.value = true;
  router.post('/admin/kustomisasi', form.value, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isSaving.value = false;
      removeSelectedPosterFile();
      removeSelectedMascotFile();
      success('Pengaturan Kustomisasi Lanjutan berhasil disimpan!');
    },
    onError: (errs) => {
      isSaving.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan pengaturan.');
    }
  });
}

function openResetModal() {
  isResetModalOpen.value = true;
}

function submitReset() {
  isResetting.value = true;
  router.post('/admin/kustomisasi/reset', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isResetting.value = false;
      isResetModalOpen.value = false;
      success('Kustomisasi lanjutan berhasil dikembalikan ke standar awal.');
    },
    onError: () => {
      isResetting.value = false;
      error('Gagal mereset kustomisasi.');
    }
  });
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.btn-xs {
  padding: 2px 8px;
  font-size: 11px;
}

.bg-gradient-gold {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
  color: #ffffff;
}

.anim-pulse {
  animation: pulseEffect 2s infinite;
}

@keyframes pulseEffect {
  0% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
  }
  70% {
    box-shadow: 0 0 0 14px rgba(34, 197, 94, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
  }
}

.anim-bounce {
  animation: bounceEffect 2s infinite;
}

@keyframes bounceEffect {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-8px);
  }
  60% {
    transform: translateY(-4px);
  }
}

/* ==========================================================================
   DARK MODE ADAPTATIONS FOR KUSTOMISASI LANJUTAN
   ========================================================================== */
.section-title-text {
  color: #0f172a;
}
[data-bs-theme="dark"] .section-title-text {
  color: #f8fafc !important;
}

.kustomisasi-tabs-header,
.kustomisasi-preview-header {
  background-color: #ffffff;
}
[data-bs-theme="dark"] .kustomisasi-tabs-header,
[data-bs-theme="dark"] .kustomisasi-preview-header {
  background-color: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.kustomisasi-card-footer {
  background-color: #f8f9fa;
  border-top: 1px solid #e9ebec;
}
[data-bs-theme="dark"] .kustomisasi-card-footer {
  background-color: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.custom-code-textarea {
  background-color: #f8fafc;
  color: #0f172a;
  border-color: #cbd5e1;
}
[data-bs-theme="dark"] .custom-code-textarea {
  background-color: #0b0f19 !important;
  color: #38bdf8 !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}

.preview-body-area {
  background-color: #f8fafc;
}
[data-bs-theme="dark"] .preview-body-area {
  background-color: #0f172a !important;
}

.preview-empty-box,
.preview-popup-box,
.preview-wa-display-box,
.preview-info-box,
.preview-tips-card,
.preview-stat-card,
.preview-prize-card {
  background-color: #ffffff;
  border-color: #e2e8f0;
  color: #1e293b;
}
[data-bs-theme="dark"] .preview-empty-box,
[data-bs-theme="dark"] .preview-popup-box,
[data-bs-theme="dark"] .preview-wa-display-box,
[data-bs-theme="dark"] .preview-info-box,
[data-bs-theme="dark"] .preview-tips-card,
[data-bs-theme="dark"] .preview-stat-card,
[data-bs-theme="dark"] .preview-prize-card {
  background-color: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #f1f5f9 !important;
}

.preview-badge-realtime {
  background-color: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .preview-badge-realtime {
  background-color: rgba(255, 255, 255, 0.08);
  color: #94a3b8;
  border-color: rgba(255, 255, 255, 0.12);
}

[data-bs-theme="dark"] .nav-tabs-custom .nav-link {
  color: #94a3b8;
}
[data-bs-theme="dark"] .nav-tabs-custom .nav-link:hover {
  color: #38bdf8;
}
[data-bs-theme="dark"] .nav-tabs-custom .nav-link.active {
  color: #38bdf8 !important;
  border-bottom: 2px solid #38bdf8 !important;
  background: transparent;
}

[data-bs-theme="dark"] .card {
  background-color: #1e293b;
  border-color: rgba(255, 255, 255, 0.08);
}
[data-bs-theme="dark"] .form-control {
  background-color: #0f172a;
  border-color: rgba(255, 255, 255, 0.15);
  color: #f8fafc;
}
[data-bs-theme="dark"] .form-control:focus {
  background-color: #0f172a;
  border-color: #38bdf8;
  color: #ffffff;
}
[data-bs-theme="dark"] .input-group-text {
  background-color: rgba(34, 197, 94, 0.2);
  border-color: rgba(255, 255, 255, 0.15);
  color: #86efac;
}
[data-bs-theme="dark"] .form-check-label {
  color: #e2e8f0;
}
[data-bs-theme="dark"] .modal-content {
  background-color: #1e293b;
  color: #f8fafc;
}
[data-bs-theme="dark"] .text-muted {
  color: #94a3b8 !important;
}

/* Upload Media Cards styling */
.upload-media-card {
  background-color: #f8fafc;
  border-color: #e2e8f0 !important;
}
[data-bs-theme="dark"] .upload-media-card {
  background-color: #161f30 !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}

.upload-media-inner {
  background-color: #ffffff;
  border-color: #e2e8f0 !important;
}
[data-bs-theme="dark"] .upload-media-inner {
  background-color: #0f172a !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
}

.upload-media-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .upload-media-title {
  color: #f8fafc !important;
}

.upload-media-filename {
  color: #1e293b;
}
[data-bs-theme="dark"] .upload-media-filename {
  color: #f1f5f9 !important;
}

/* Checkerboard background pattern for transparent images */
.checkerboard-bg {
  background-color: #f1f5f9;
  background-image: 
    linear-gradient(45deg, #e2e8f0 25%, transparent 25%), 
    linear-gradient(-45deg, #e2e8f0 25%, transparent 25%), 
    linear-gradient(45deg, transparent 75%, #e2e8f0 75%), 
    linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
  background-size: 16px 16px;
  background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
}

[data-bs-theme="dark"] .checkerboard-bg {
  background-color: #1e293b;
  background-image: 
    linear-gradient(45deg, #0f172a 25%, transparent 25%), 
    linear-gradient(-45deg, #0f172a 25%, transparent 25%), 
    linear-gradient(45deg, transparent 75%, #0f172a 75%), 
    linear-gradient(-45deg, transparent 75%, #0f172a 75%);
}

/* ==========================================================================
   PUBLIC PRELOADER PREVIEW STYLES (MATCHES app.blade.php)
   ========================================================================== */
#public-preloader {
  position: fixed;
  inset: 0;
  z-index: 99999999;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  user-select: none;
  background-color: #0b0f19;
  color: #f8fafc;
}

[data-bs-theme="light"] #public-preloader {
  background-color: #ffffff;
  color: #0f172a;
}

.preloader-glow-orb {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(40px);
}
.preloader-glow-1 {
  width: 460px;
  height: 460px;
  top: 25%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: radial-gradient(circle, rgba(139, 92, 246, 0.24) 0%, rgba(236, 72, 153, 0.12) 45%, transparent 70%);
  animation: preloaderPulseGlow 4s ease-in-out infinite alternate;
}
.preloader-glow-2 {
  width: 320px;
  height: 320px;
  bottom: 10%;
  right: 15%;
  background: radial-gradient(circle, rgba(249, 115, 22, 0.12) 0%, transparent 65%);
}

.preloader-stage {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px;
  max-width: 440px;
  width: 100%;
}

.preloader-logo-area {
  position: relative;
  width: 120px;
  height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 22px;
}
.preloader-orbit-outer {
  position: absolute;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 2.5px solid transparent;
  border-top-color: #8b5cf6;
  border-right-color: #ec4899;
  border-bottom-color: #f97316;
  animation: preloaderSpin 2.4s cubic-bezier(0.68, -0.2, 0.27, 1.2) infinite;
}
.preloader-orbit-inner {
  position: absolute;
  width: 104px;
  height: 104px;
  border-radius: 50%;
  border: 1.5px dashed rgba(139, 92, 246, 0.4);
  animation: preloaderSpinRev 3.5s linear infinite;
}
.preloader-logo-halo {
  position: absolute;
  width: 86px;
  height: 86px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.4) 0%, rgba(236, 72, 153, 0.18) 50%, transparent 70%);
  animation: preloaderHalo 2.4s ease-in-out infinite;
}
.preloader-logo-badge {
  position: relative;
  z-index: 3;
  width: 86px;
  height: 86px;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #111827;
  border: 1.5px solid rgba(139, 92, 246, 0.38);
  box-shadow: 0 18px 40px -8px rgba(0, 0, 0, 0.7);
  animation: preloaderFloat 2.6s ease-in-out infinite;
}
[data-bs-theme="light"] .preloader-logo-badge {
  background: #ffffff;
  border-color: rgba(139, 92, 246, 0.2);
  box-shadow: 0 16px 36px -10px rgba(139, 92, 246, 0.22);
}
.preloader-logo-img {
  width: 54px;
  height: 54px;
  object-fit: contain;
}

.preloader-brand-box {
  margin-bottom: 24px;
}
.preloader-badge-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  margin-bottom: 8px;
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(139, 92, 246, 0.45);
  color: #c4b5fd;
}
[data-bs-theme="light"] .preloader-badge-tag {
  background: rgba(139, 92, 246, 0.08);
  border-color: rgba(139, 92, 246, 0.22);
  color: #7c3aed;
}
.preloader-sparkle {
  font-size: 0.72rem;
  color: #f59e0b;
}
.preloader-school-title {
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.35;
  margin: 0;
  color: #f1f5f9;
}
[data-bs-theme="light"] .preloader-school-title {
  color: #1e293b;
}

.preloader-progress-wrap {
  width: 100%;
  max-width: 240px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}
.preloader-progress-track {
  width: 100%;
  height: 4.5px;
  border-radius: 999px;
  overflow: hidden;
  position: relative;
  background: rgba(30, 41, 59, 0.85);
}
[data-bs-theme="light"] .preloader-progress-track {
  background: rgba(226, 232, 240, 0.85);
}
.preloader-progress-bar {
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  background: linear-gradient(90deg, #8b5cf6 0%, #ec4899 45%, #f97316 80%, #10b981 100%);
  border-radius: 999px;
  overflow: hidden;
}
.preloader-shimmer {
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.65), transparent);
  transform: translateX(-100%);
  animation: preloaderShimmer 1.5s infinite linear;
}
.preloader-status-text {
  font-size: 0.8rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  color: #cbd5e1;
}
[data-bs-theme="light"] .preloader-status-text {
  color: #475569;
}
.preloader-dot-pulse span {
  display: inline-block;
  font-weight: 700;
  animation: preloaderDot 1.4s infinite ease-in-out both;
}
.preloader-dot-pulse span:nth-child(1) { animation-delay: 0s; }
.preloader-dot-pulse span:nth-child(2) { animation-delay: 0.2s; }
.preloader-dot-pulse span:nth-child(3) { animation-delay: 0.4s; }

@keyframes preloaderSpin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
@keyframes preloaderSpinRev {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(-360deg); }
}
@keyframes preloaderFloat {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-4px); }
}
@keyframes preloaderHalo {
  0%, 100% { transform: scale(0.95); opacity: 0.5; }
  50% { transform: scale(1.08); opacity: 0.9; }
}
@keyframes preloaderPulseGlow {
  0% { transform: translate(-50%, -50%) scale(0.95); opacity: 0.7; }
  100% { transform: translate(-50%, -50%) scale(1.08); opacity: 1; }
}
@keyframes preloaderShimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}
@keyframes preloaderDot {
  0%, 80%, 100% { opacity: 0.2; transform: translateY(0); }
  40% { opacity: 1; transform: translateY(-2px); }
}
</style>
