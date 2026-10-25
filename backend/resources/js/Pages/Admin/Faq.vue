<template>
  <Head title="Kelola Tanya Jawab (FAQ) - Admin Panel" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
            <div>
              <h4 class="mb-sm-0 font-size-18 fw-bold">Kelola Tanya Jawab (FAQ)</h4>
              <p class="text-muted fs-13 mb-0">Atur konten pertanyaan umum dan jawaban berformat kaya teks untuk peserta di web publik.</p>
            </div>
            <div class="page-title-right d-flex align-items-center gap-2 mt-3 mt-sm-0">
              <a 
                href="/#faq" 
                target="_blank" 
                class="btn btn-soft-info d-flex align-items-center gap-1 shadow-sm"
              >
                <i class="ri-external-link-line"></i> Lihat di Web
              </a>
              <button 
                type="button" 
                class="btn btn-outline-primary d-flex align-items-center gap-1 shadow-sm"
                @click="openPreviewModal"
              >
                <i class="ri-eye-line"></i> Simulasi Accordion
              </button>
              <button 
                type="button" 
                class="btn btn-primary d-flex align-items-center gap-1 shadow-sm"
                @click="openAddModal"
              >
                <i class="ri-add-line"></i> Tambah FAQ Baru
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Overview Stats Cards -->
      <div class="row mb-3">
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                    <i class="ri-question-answer-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Total Pertanyaan</p>
                  <h4 class="mb-0 fs-22 fw-bold text-dark">{{ totalCount }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                    <i class="ri-checkbox-circle-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">FAQ Ditampilkan</p>
                  <h4 class="mb-0 fs-22 fw-bold text-success">{{ activeCount }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                    <i class="ri-eye-off-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">FAQ Tersembunyi</p>
                  <h4 class="mb-0 fs-22 fw-bold text-warning">{{ inactiveCount }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xl-3 col-md-6">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                    <i class="ri-folder-info-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Kategori FAQ</p>
                  <h4 class="mb-0 fs-22 fw-bold text-info">{{ uniqueCategories.length }}</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section Header Settings Card (Collapsible) -->
      <div class="card border-0 shadow-sm mb-4">
        <div 
          class="card-header bg-white py-3 d-flex align-items-center justify-content-between cursor-pointer"
          @click="isSettingsCollapsed = !isSettingsCollapsed"
        >
          <div class="d-flex align-items-center gap-2">
            <i class="ri-settings-4-line fs-18 text-primary"></i>
            <h5 class="card-title mb-0 fs-14 fw-bold">Pengaturan Bagian Teks & Judul FAQ di Halaman Utama</h5>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge" :class="settingsForm.faq_is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
              {{ settingsForm.faq_is_active ? 'Bagian FAQ Aktif' : 'Bagian FAQ Dinonaktifkan' }}
            </span>
            <i :class="isSettingsCollapsed ? 'ri-arrow-down-s-line' : 'ri-arrow-up-s-line'" class="fs-16 text-muted"></i>
          </div>
        </div>

        <div v-show="!isSettingsCollapsed" class="card-body border-top">
          <form @submit.prevent="saveSettings">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label fs-12 fw-semibold text-muted">Tag / Label Kecil</label>
                <input 
                  type="text" 
                  v-model="settingsForm.faq_section_tag" 
                  class="form-control" 
                  placeholder="Contoh: Bantuan Informasi"
                />
              </div>

              <div class="col-md-5">
                <label class="form-label fs-12 fw-semibold text-muted">Judul Utama Bagian FAQ</label>
                <input 
                  type="text" 
                  v-model="settingsForm.faq_section_title" 
                  class="form-control" 
                  placeholder="Contoh: Pertanyaan Sering Diajukan (FAQ)"
                  required
                />
              </div>

              <div class="col-md-4">
                <label class="form-label fs-12 fw-semibold text-muted">Visibilitas di Web Publik</label>
                <div class="form-check form-switch form-switch-md mt-1">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="faqActiveSwitch"
                    v-model="settingsForm.faq_is_active"
                  />
                  <label class="form-check-label fs-13" for="faqActiveSwitch">
                    {{ settingsForm.faq_is_active ? 'Tampilkan Bagian FAQ di Website' : 'Sembunyikan Seluruh Bagian FAQ' }}
                  </label>
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fs-12 fw-semibold text-muted">Deskripsi Singkat Bagian FAQ</label>
                <textarea 
                  v-model="settingsForm.faq_section_desc" 
                  class="form-control" 
                  rows="2" 
                  placeholder="Deskripsi singkat seputar informasi tanya jawab..."
                  required
                ></textarea>
              </div>

              <div class="col-12 text-end">
                <button 
                  type="submit" 
                  class="btn btn-primary btn-sm px-4 shadow-sm"
                  :disabled="isSavingSettings"
                >
                  <i class="ri-save-line me-1"></i>
                  {{ isSavingSettings ? 'Menyimpan...' : 'Simpan Pengaturan Bagian' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Main FAQ List Management -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
          <div class="row g-3 align-items-center">
            <!-- Search bar -->
            <div class="col-lg-4 col-md-6">
              <div class="search-box position-relative">
                <input 
                  type="text" 
                  v-model="searchQuery" 
                  class="form-control ps-5" 
                  placeholder="Cari pertanyaan atau jawaban..."
                />
                <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <button 
                  v-if="searchQuery" 
                  type="button" 
                  class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 p-0 text-muted"
                  @click="searchQuery = ''"
                >
                  <i class="ri-close-circle-fill"></i>
                </button>
              </div>
            </div>

            <!-- Filter Kategori -->
            <div class="col-lg-3 col-md-6">
              <CustomSelect v-model="filterCategory" size="md">
                <option value="all">Semua Kategori ({{ totalCount }})</option>
                <option v-for="cat in uniqueCategories" :key="cat" :value="cat">
                  {{ cat }} ({{ getCategoryCount(cat) }})
                </option>
              </CustomSelect>
            </div>

            <!-- Filter Status -->
            <div class="col-lg-3 col-md-6">
              <CustomSelect v-model="filterStatus" size="md">
                <option value="all">Semua Status ({{ totalCount }})</option>
                <option value="active">Hanya Aktif ({{ activeCount }})</option>
                <option value="inactive">Hanya Tersembunyi ({{ inactiveCount }})</option>
              </CustomSelect>
            </div>

            <!-- Reorder save indicator -->
            <div class="col-lg-2 col-md-6 text-end">
              <button 
                v-if="hasReorderChanges" 
                type="button" 
                class="btn btn-sm btn-success w-100 shadow-sm animate__animated animate__pulse animate__infinite"
                :disabled="isSavingOrder"
                @click="saveReorder"
              >
                <i class="ri-check-line me-1"></i> Simpan Urutan
              </button>
              <span v-else class="text-muted fs-12">
                Total: <strong>{{ filteredFaqs.length }}</strong> FAQ
              </span>
            </div>
          </div>
        </div>

        <div class="card-body p-0">
          <div v-if="filteredFaqs.length === 0" class="text-center py-5">
            <div class="avatar-lg mx-auto mb-3">
              <div class="avatar-title bg-light text-muted rounded-circle fs-36">
                <i class="ri-question-line"></i>
              </div>
            </div>
            <h5 class="text-dark fw-bold">Tidak ada FAQ ditemukan</h5>
            <p class="text-muted fs-13 mb-3">Coba ubah kata kunci pencarian atau tambah pertanyaan FAQ baru.</p>
            <button type="button" class="btn btn-primary btn-sm" @click="openAddModal">
              <i class="ri-add-line me-1"></i> Tambah FAQ Baru
            </button>
          </div>

          <div v-else class="faq-admin-list">
            <div 
              v-for="(faq, index) in filteredFaqs" 
              :key="faq.id"
              class="faq-admin-item p-3 border-bottom transition-all"
              :class="{ 'bg-light-subtle': !faq.is_active, 'is-expanded': expandedFaqIds.includes(faq.id) }"
            >
              <div class="d-flex align-items-start gap-3">
                <!-- Move Up / Down Sort Controls -->
                <div class="d-flex flex-column align-items-center gap-1 sort-controls pt-1">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-light p-1 rounded-circle"
                    title="Pindahkan ke Atas"
                    :disabled="index === 0"
                    @click="moveFaq(index, -1)"
                  >
                    <i class="ri-arrow-up-s-line fs-14"></i>
                  </button>
                  <span class="badge bg-light text-muted border fs-10 px-1 py-0">{{ index + 1 }}</span>
                  <button 
                    type="button" 
                    class="btn btn-sm btn-light p-1 rounded-circle"
                    title="Pindahkan ke Bawah"
                    :disabled="index === filteredFaqs.length - 1"
                    @click="moveFaq(index, 1)"
                  >
                    <i class="ri-arrow-down-s-line fs-14"></i>
                  </button>
                </div>

                <!-- Main Content Area -->
                <div class="flex-grow-1">
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <!-- Category Badge -->
                    <span 
                      class="badge" 
                      :class="getCategoryBadgeClass(faq.category)"
                    >
                      <i class="ri-price-tag-3-line me-1"></i>{{ faq.category || 'Umum' }}
                    </span>

                    <!-- Status Badge -->
                    <span 
                      class="badge" 
                      :class="faq.is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted'"
                    >
                      <i :class="faq.is_active ? 'ri-checkbox-circle-fill' : 'ri-eye-off-fill'" class="me-1"></i>
                      {{ faq.is_active ? 'Ditampilkan' : 'Disembunyikan' }}
                    </span>

                    <span class="text-muted fs-11 ms-auto d-none d-md-inline">
                      <i class="ri-time-line me-1"></i>Diperbarui: {{ formatDate(faq.updated_at) }}
                    </span>
                  </div>

                  <!-- Question Text (Click to toggle expanded answer) -->
                  <div 
                    class="faq-question-title d-flex align-items-center justify-content-between cursor-pointer"
                    @click="toggleExpandAnswer(faq.id)"
                  >
                    <h5 class="fs-15 fw-semibold mb-0 text-dark">
                      {{ faq.question }}
                    </h5>
                    <button type="button" class="btn btn-sm btn-link text-muted p-0 ms-2">
                      <i :class="expandedFaqIds.includes(faq.id) ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line'" class="fs-18"></i>
                    </button>
                  </div>

                  <!-- Expandable Formatted Answer Preview -->
                  <div v-show="expandedFaqIds.includes(faq.id)" class="faq-answer-box mt-3 p-3 rounded">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom text-muted fs-11">
                      <span><i class="ri-layout-masonry-line text-primary me-1"></i> Pratinjau Tampilan Format Teks:</span>
                      <span class="badge bg-white text-secondary border">HTML Rendered</span>
                    </div>
                    <div class="faq-rendered-html" v-html="faq.answer"></div>
                  </div>

                  <!-- Collapsed Snippet -->
                  <div v-if="!expandedFaqIds.includes(faq.id)" class="text-muted fs-12 mt-1 text-truncate" style="max-width: 750px;">
                    {{ stripHtml(faq.answer) }}
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex align-items-center gap-1 action-buttons pt-1">
                  <!-- Quick Toggle Active -->
                  <button 
                    type="button" 
                    class="btn btn-sm"
                    :class="faq.is_active ? 'btn-soft-warning' : 'btn-soft-success'"
                    :title="faq.is_active ? 'Sembunyikan dari Web' : 'Tampilkan di Web'"
                    @click="toggleActive(faq)"
                  >
                    <i :class="faq.is_active ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                  </button>

                  <!-- Edit FAQ -->
                  <button 
                    type="button" 
                    class="btn btn-sm btn-soft-primary" 
                    title="Edit Pertanyaan & Jawaban"
                    @click="openEditModal(faq)"
                  >
                    <i class="ri-edit-line"></i>
                  </button>

                  <!-- Delete FAQ -->
                  <button 
                    type="button" 
                    class="btn btn-sm btn-soft-danger" 
                    title="Hapus FAQ"
                    @click="openDeleteModal(faq)"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL TAMBAH / EDIT FAQ DENGAN TEKS EDITOR FORMATING             -->
    <!-- ============================================================== -->
    <div v-if="isFormModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isFormModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light py-3">
            <h5 class="modal-title fw-bold fs-16 d-flex align-items-center gap-2">
              <i class="ri-question-answer-line text-primary"></i>
              {{ isEditing ? 'Edit Pertanyaan & Jawaban FAQ' : 'Tambah Pertanyaan FAQ Baru' }}
            </h5>
            <button type="button" class="btn-close" @click="closeFormModal"></button>
          </div>

          <div class="modal-body p-4">
            <form @submit.prevent="submitFaqForm" id="faqForm">
              <!-- Pertanyaan -->
              <div class="mb-3">
                <label class="form-label fw-semibold fs-13">
                  Pertanyaan (Question) <span class="text-danger">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="faqForm.question" 
                  class="form-control" 
                  placeholder="Contoh: Apakah ada kuota maksimal untuk masing-masing cabang lomba?"
                  required
                />
              </div>

              <!-- Kategori & Urutan & Status -->
              <div class="row g-3 mb-3">
                <div class="col-md-5">
                  <label class="form-label fw-semibold fs-13">
                    Kategori FAQ <span class="text-danger">*</span>
                  </label>
                  <input 
                    type="text" 
                    v-model="faqForm.category" 
                    class="form-control" 
                    list="categoryList"
                    placeholder="Pilih atau ketik kategori baru"
                    required
                  />
                  <datalist id="categoryList">
                    <option value="Pendaftaran" />
                    <option value="Pembayaran & Biaya" />
                    <option value="Kompetisi & Juknis" />
                    <option value="Pelaksanaan & TM" />
                    <option value="Fasilitas & Hadiah" />
                    <option value="Umum" />
                  </datalist>
                </div>

                <div class="col-md-3">
                  <label class="form-label fw-semibold fs-13">Nomor Urutan</label>
                  <input 
                    type="number" 
                    v-model.number="faqForm.sort_order" 
                    class="form-control" 
                    min="1"
                  />
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-semibold fs-13">Visibilitas</label>
                  <div class="form-check form-switch form-switch-md mt-2">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="faqActiveFormSwitch"
                      v-model="faqForm.is_active"
                    />
                    <label class="form-check-label fs-13" for="faqActiveFormSwitch">
                      {{ faqForm.is_active ? 'Langsung Ditampilkan' : 'Simpan sebagai Draft' }}
                    </label>
                  </div>
                </div>
              </div>

              <!-- Rich Text Formatting Editor for Answer -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fw-semibold fs-13 mb-0">
                    Jawaban Lengkap (Answer) <span class="text-danger">*</span>
                  </label>
                  <span class="text-muted fs-11">
                    <i class="ri-font-color text-primary me-1"></i>Gunakan toolbar untuk format teks tebal, miring, list poin, atau tautan
                  </span>
                </div>

                <!-- RichTextEditor Component -->
                <RichTextEditor 
                  v-model="faqForm.answer"
                  placeholder="Tuliskan jawaban lengkap dengan format rapi (contoh: gunakan poin bullet untuk syarat, tebal untuk poin penting)..."
                  min-height="200px"
                  max-height="350px"
                />
              </div>

              <!-- Live Rendered Preview inside Modal -->
              <div v-if="faqForm.answer" class="mb-0">
                <div class="d-flex align-items-center justify-content-between text-muted fs-11 mb-1">
                  <span><i class="ri-eye-line text-success me-1"></i> Preview Tampilan Akhir di Web Publik:</span>
                </div>
                <div class="faq-modal-preview p-3 rounded border bg-light-subtle">
                  <div class="fw-semibold text-dark mb-2 fs-14">
                    <span class="badge bg-warning-subtle text-warning me-2">{{ faqForm.category || 'Umum' }}</span>
                    {{ faqForm.question || 'Contoh Judul Pertanyaan' }}
                  </div>
                  <div class="faq-rendered-html text-secondary fs-13" v-html="faqForm.answer"></div>
                </div>
              </div>
            </form>
          </div>

          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-light btn-sm" @click="closeFormModal">Batal</button>
            <button 
              type="submit" 
              form="faqForm" 
              class="btn btn-primary btn-sm px-4 shadow-sm"
              :disabled="isSavingFaq"
            >
              <i class="ri-save-line me-1"></i>
              {{ isSavingFaq ? 'Menyimpan...' : (isEditing ? 'Perbarui FAQ' : 'Simpan FAQ Baru') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL SIMULASI ACCORDION FAQ PUBLIK                             -->
    <!-- ============================================================== -->
    <div v-if="isPreviewModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isPreviewModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg text-white" style="background: #0f172a;">
          <div class="modal-header border-bottom border-secondary py-3">
            <div class="d-flex align-items-center gap-2">
              <i class="ri-eye-line text-warning fs-18"></i>
              <div>
                <h5 class="modal-title fw-bold fs-15 text-white mb-0">Simulasi Tampilan Accordion Publik</h5>
                <small class="text-white-50">Menampilkan persis bagaimana accordion FAQ dibuka dan dibaca oleh pengunjung website</small>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" @click="isPreviewModalOpen = false"></button>
          </div>

          <div class="modal-body p-4" style="background: #0b1120;">
            <div class="text-center mb-4">
              <span class="badge bg-warning-subtle text-warning px-3 py-1 rounded-pill fs-11 text-uppercase fw-bold mb-2">
                {{ settingsForm.faq_section_tag || 'Bantuan Informasi' }}
              </span>
              <h3 class="fw-bold text-white fs-20 mb-1">{{ settingsForm.faq_section_title || 'Pertanyaan Sering Diajukan (FAQ)' }}</h3>
              <p class="text-white-50 fs-13 mb-0">{{ settingsForm.faq_section_desc }}</p>
            </div>

            <!-- Public Accordion Simulation -->
            <div class="d-flex flex-column gap-2">
              <div 
                v-for="(f, idx) in activeFaqs" 
                :key="f.id || idx"
                class="rounded-3 border transition-all"
                style="background: rgba(30, 41, 59, 0.7); border-color: rgba(255, 255, 255, 0.08);"
              >
                <button 
                  type="button" 
                  class="btn w-100 text-start text-white p-3 d-flex align-items-center justify-content-between border-0 shadow-none"
                  @click="toggleSimAccordion(idx)"
                >
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark fs-10">{{ f.category || 'Umum' }}</span>
                    <span class="fw-semibold fs-14">{{ f.question }}</span>
                  </div>
                  <i 
                    class="ri-arrow-down-s-line fs-18 text-warning transition-transform"
                    :style="{ transform: openSimAccordionIdx === idx ? 'rotate(180deg)' : 'rotate(0)' }"
                  ></i>
                </button>
                <div 
                  v-show="openSimAccordionIdx === idx" 
                  class="px-3 pb-3 text-white-50 fs-13 border-top"
                  style="border-color: rgba(255, 255, 255, 0.05) !important;"
                >
                  <div class="pt-3 faq-rendered-html-dark" v-html="f.answer"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer border-top border-secondary py-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="isPreviewModalOpen = false">Tutup Simulasi</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL KONFIRMASI HAPUS FAQ                                     -->
    <!-- ============================================================== -->
    <div v-if="isDeleteModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isDeleteModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-body text-center p-4">
            <div class="avatar-lg mx-auto mb-3">
              <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-36">
                <i class="ri-delete-bin-line"></i>
              </div>
            </div>
            <h5 class="fw-bold text-dark">Hapus Pertanyaan FAQ Ini?</h5>
            <p class="text-muted fs-13 mb-3">
              Pertanyaan: <br/>
              <strong class="text-dark">"{{ deletingFaq?.question }}"</strong>
              <br/>Tindakan ini tidak dapat dibatalkan.
            </p>
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-light btn-sm px-3" @click="closeDeleteModal">Batal</button>
              <button 
                type="button" 
                class="btn btn-danger btn-sm px-4 shadow-sm"
                :disabled="isDeleting"
                @click="confirmDelete"
              >
                {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus FAQ' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  faqs: {
    type: Array,
    default: () => []
  },
  settings: {
    type: Object,
    default: () => ({})
  }
});

const { success, error } = useToast();

// 1. Settings State
const isSettingsCollapsed = ref(true);
const isSavingSettings = ref(false);

const settingsForm = ref({
  faq_section_tag: props.settings.faq_section_tag || 'Bantuan Informasi',
  faq_section_title: props.settings.faq_section_title || 'Pertanyaan Sering Diajukan (FAQ)',
  faq_section_desc: props.settings.faq_section_desc || 'Jawaban atas pertanyaan umum seputar pendaftaran dan pelaksanaan Semarak Aliyah XIV 2026.',
  faq_is_active: props.settings.faq_is_active !== false,
});

function saveSettings() {
  isSavingSettings.value = true;
  router.post('/admin/faq-settings', settingsForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingSettings.value = false;
      success('Pengaturan judul dan deskripsi FAQ berhasil disimpan!');
    },
    onError: (errs) => {
      isSavingSettings.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan pengaturan FAQ.');
    }
  });
}

// 2. Search & Filter State
const searchQuery = ref('');
const filterCategory = ref('all');
const filterStatus = ref('all');
const expandedFaqIds = ref([]);

function toggleExpandAnswer(id) {
  if (expandedFaqIds.value.includes(id)) {
    expandedFaqIds.value = expandedFaqIds.value.filter(x => x !== id);
  } else {
    expandedFaqIds.value.push(id);
  }
}

// Categories list
const uniqueCategories = computed(() => {
  const cats = props.faqs.map(f => f.category || 'Umum');
  return [...new Set(cats)];
});

function getCategoryCount(cat) {
  return props.faqs.filter(f => (f.category || 'Umum') === cat).length;
}

function getCategoryBadgeClass(category) {
  const c = String(category || '').toLowerCase();
  if (c.includes('daftar')) return 'bg-primary-subtle text-primary';
  if (c.includes('bayar') || c.includes('biaya')) return 'bg-success-subtle text-success';
  if (c.includes('lomba') || c.includes('juknis') || c.includes('kompetisi')) return 'bg-warning-subtle text-warning';
  if (c.includes('tm') || c.includes('jadwal')) return 'bg-info-subtle text-info';
  if (c.includes('fasilitas') || c.includes('hadiah')) return 'bg-danger-subtle text-danger';
  return 'bg-secondary-subtle text-secondary';
}

// Stats
const totalCount = computed(() => props.faqs.length);
const activeCount = computed(() => props.faqs.filter(f => f.is_active).length);
const inactiveCount = computed(() => props.faqs.filter(f => !f.is_active).length);
const activeFaqs = computed(() => props.faqs.filter(f => f.is_active));

// Filtered FAQs list
const localFaqs = ref([...props.faqs]);
const hasReorderChanges = ref(false);
const isSavingOrder = ref(false);

const filteredFaqs = computed(() => {
  return localFaqs.value.filter(f => {
    // Search query
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase();
      const matchQ = f.question && f.question.toLowerCase().includes(q);
      const matchA = f.answer && f.answer.toLowerCase().includes(q);
      const matchC = f.category && f.category.toLowerCase().includes(q);
      if (!matchQ && !matchA && !matchC) return false;
    }

    // Category filter
    if (filterCategory.value !== 'all') {
      if ((f.category || 'Umum') !== filterCategory.value) return false;
    }

    // Status filter
    if (filterStatus.value === 'active' && !f.is_active) return false;
    if (filterStatus.value === 'inactive' && f.is_active) return false;

    return true;
  });
});

// Reorder Up / Down
function moveFaq(index, direction) {
  const targetIndex = index + direction;
  if (targetIndex < 0 || targetIndex >= filteredFaqs.value.length) return;

  const currentItem = filteredFaqs.value[index];
  const targetItem = filteredFaqs.value[targetIndex];

  // Find indexes in localFaqs
  const localIdx1 = localFaqs.value.findIndex(f => f.id === currentItem.id);
  const localIdx2 = localFaqs.value.findIndex(f => f.id === targetItem.id);

  if (localIdx1 !== -1 && localIdx2 !== -1) {
    const temp = localFaqs.value[localIdx1];
    localFaqs.value[localIdx1] = localFaqs.value[localIdx2];
    localFaqs.value[localIdx2] = temp;
    hasReorderChanges.value = true;
  }
}

function saveReorder() {
  isSavingOrder.value = true;
  const orders = localFaqs.value.map((f, idx) => ({
    id: f.id,
    sort_order: idx + 1
  }));

  router.post('/admin/faq-reorder', { orders }, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingOrder.value = false;
      hasReorderChanges.value = false;
      success('Urutan FAQ berhasil disimpan!');
    },
    onError: () => {
      isSavingOrder.value = false;
      error('Gagal memperbarui urutan FAQ.');
    }
  });
}

// 3. Toggle Active Switch
function toggleActive(faq) {
  router.post(`/admin/faq-toggle/${faq.id}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      faq.is_active = !faq.is_active;
      success(`FAQ "${faq.question.substring(0, 30)}..." berhasil diubah statusnya.`);
    },
    onError: () => {
      error('Gagal mengubah status FAQ.');
    }
  });
}

// 4. Modal Add / Edit State
const isFormModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isSavingFaq = ref(false);

const faqForm = ref({
  question: '',
  category: 'Pendaftaran',
  sort_order: 1,
  is_active: true,
  answer: '',
});

function openAddModal() {
  isEditing.value = false;
  editingId.value = null;
  faqForm.value = {
    question: '',
    category: 'Pendaftaran',
    sort_order: (props.faqs.length || 0) + 1,
    is_active: true,
    answer: '<p>Tuliskan jawaban Anda di sini...</p>',
  };
  isFormModalOpen.value = true;
}

function openEditModal(faq) {
  isEditing.value = true;
  editingId.value = faq.id;
  faqForm.value = {
    question: faq.question,
    category: faq.category || 'Umum',
    sort_order: faq.sort_order || 1,
    is_active: Boolean(faq.is_active),
    answer: faq.answer || '',
  };
  isFormModalOpen.value = true;
}

function closeFormModal() {
  isFormModalOpen.value = false;
}

function submitFaqForm() {
  if (!faqForm.value.question || !faqForm.value.answer) {
    error('Harap isi pertanyaan dan jawaban FAQ.');
    return;
  }

  isSavingFaq.value = true;
  const url = isEditing.value ? `/admin/faq/${editingId.value}` : '/admin/faq';

  router.post(url, faqForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingFaq.value = false;
      isFormModalOpen.value = false;
      success(isEditing.value ? 'FAQ berhasil diperbarui!' : 'FAQ baru berhasil ditambahkan!');
    },
    onError: (errs) => {
      isSavingFaq.value = false;
      error(Object.values(errs)[0] || 'Terjadi kesalahan saat menyimpan FAQ.');
    }
  });
}

// 5. Delete Modal State
const isDeleteModalOpen = ref(false);
const deletingFaq = ref(null);
const isDeleting = ref(false);

function openDeleteModal(faq) {
  deletingFaq.value = faq;
  isDeleteModalOpen.value = true;
}

function closeDeleteModal() {
  isDeleteModalOpen.value = false;
  deletingFaq.value = null;
}

function confirmDelete() {
  if (!deletingFaq.value) return;
  isDeleting.value = true;
  router.delete(`/admin/faq/${deletingFaq.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleting.value = false;
      isDeleteModalOpen.value = false;
      success('FAQ berhasil dihapus.');
    },
    onError: () => {
      isDeleting.value = false;
      error('Gagal menghapus FAQ.');
    }
  });
}

// 6. Simulation Accordion Modal
const isPreviewModalOpen = ref(false);
const openSimAccordionIdx = ref(0);

function openPreviewModal() {
  openSimAccordionIdx.value = 0;
  isPreviewModalOpen.value = true;
}

function toggleSimAccordion(idx) {
  openSimAccordionIdx.value = openSimAccordionIdx.value === idx ? -1 : idx;
}

// Helper utilities
function stripHtml(html) {
  if (!html) return '';
  const tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch (e) {
    return dateStr;
  }
}
</script>

<style scoped>
.faq-admin-item:hover {
  background-color: #f8fafc;
}

.faq-admin-item.is-expanded {
  background-color: #f1f5f9;
}

.faq-question-title {
  padding: 4px 0;
}

.faq-question-title:hover h5 {
  color: #2563eb !important;
}

.faq-answer-box {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
}

.faq-modal-preview {
  max-height: 180px;
  overflow-y: auto;
}

/* Light Theme Rendered HTML */
.faq-rendered-html {
  line-height: 1.65;
  color: #334155;
  font-size: 13.5px;
}

:deep(.faq-rendered-html p) {
  margin-bottom: 0.6rem;
}

:deep(.faq-rendered-html p:last-child) {
  margin-bottom: 0;
}

:deep(.faq-rendered-html h4) {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
  margin-top: 0.8rem;
  margin-bottom: 0.4rem;
}

:deep(.faq-rendered-html h5) {
  font-size: 0.98rem;
  font-weight: 600;
  color: #1e293b;
  margin-top: 0.6rem;
  margin-bottom: 0.3rem;
}

:deep(.faq-rendered-html ul),
:deep(.faq-rendered-html ol) {
  padding-left: 1.3rem;
  margin-bottom: 0.6rem;
}

:deep(.faq-rendered-html li) {
  margin-bottom: 0.2rem;
}

:deep(.faq-rendered-html blockquote) {
  border-left: 3px solid #2563eb;
  padding: 6px 12px;
  margin: 8px 0;
  background: #f8fafc;
  color: #475569;
  border-radius: 0 4px 4px 0;
}

:deep(.faq-rendered-html a) {
  color: #2563eb;
  text-decoration: underline;
}

/* Dark Theme Rendered HTML for Accordion Simulation */
.faq-rendered-html-dark {
  line-height: 1.65;
  color: #cbd5e1;
  font-size: 13.5px;
}

:deep(.faq-rendered-html-dark p) {
  margin-bottom: 0.6rem;
}

:deep(.faq-rendered-html-dark p:last-child) {
  margin-bottom: 0;
}

:deep(.faq-rendered-html-dark h4) {
  font-size: 1.1rem;
  font-weight: 700;
  color: #f8fafc;
  margin-top: 0.8rem;
  margin-bottom: 0.4rem;
}

:deep(.faq-rendered-html-dark h5) {
  font-size: 0.98rem;
  font-weight: 600;
  color: #e2e8f0;
  margin-top: 0.6rem;
  margin-bottom: 0.3rem;
}

:deep(.faq-rendered-html-dark ul),
:deep(.faq-rendered-html-dark ol) {
  padding-left: 1.3rem;
  margin-bottom: 0.6rem;
}

:deep(.faq-rendered-html-dark li) {
  margin-bottom: 0.2rem;
}

:deep(.faq-rendered-html-dark blockquote) {
  border-left: 3px solid #eab308;
  padding: 6px 12px;
  margin: 8px 0;
  background: rgba(255, 255, 255, 0.05);
  color: #e2e8f0;
  border-radius: 0 4px 4px 0;
}

:deep(.faq-rendered-html-dark a) {
  color: #38bdf8;
  text-decoration: underline;
}

.transition-transform {
  transition: transform 0.2s ease-in-out;
}
</style>
