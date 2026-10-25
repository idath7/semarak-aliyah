<template>
  <Head title="Kelola Berkas Juknis Lomba - Admin Panel" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
            <div>
              <h4 class="mb-sm-0 font-size-18 fw-bold">Kelola Berkas Petunjuk Teknis (Juknis)</h4>
              <p class="text-muted fs-13 mb-0">
                Pusat manajemen file Juknis Umum (keseluruhan lomba) dan Juknis Spesifik per-cabang kompetisi yang dapat diunduh peserta di halaman web.
              </p>
            </div>
            <div class="page-title-right d-flex align-items-center gap-2 mt-3 mt-sm-0">
              <a 
                href="/#juknis" 
                target="_blank" 
                class="btn btn-soft-info d-flex align-items-center gap-1 shadow-sm"
              >
                <i class="ri-external-link-line"></i> Lihat Bagian Juknis di Web
              </a>
              <a 
                :href="generalJuknis.url || '/assets/JUKNIS-SEMARAK-2026.pdf'" 
                target="_blank" 
                download
                class="btn btn-outline-danger d-flex align-items-center gap-1 shadow-sm"
              >
                <i class="ri-file-download-line"></i> Tes Unduh Juknis Umum
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Overview Stats Cards -->
      <div class="row mb-4">
        <!-- Card 1: Juknis Umum Status -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-20">
                    <i class="ri-file-pdf-2-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Juknis Umum Utama</p>
                  <h5 class="mb-0 fs-15 fw-bold text-truncate" :title="generalJuknis.filename">
                    {{ generalJuknis.filename || 'JUKNIS-SEMARAK-2026.pdf' }}
                  </h5>
                  <span class="text-muted fs-11">
                    Ukuran: {{ generalJuknis.size_formatted || '2.5 MB' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 2: Total Cabang Lomba -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                    <i class="ri-medal-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Total Cabang Lomba</p>
                  <h4 class="mb-0 fs-20 fw-bold text-dark">
                    {{ competitions.length }} Cabang
                  </h4>
                  <span class="text-muted fs-11">
                    Semua kategori kompetisi
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 3: Juknis Spesifik Tersedia -->
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
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Juknis Spesifik Terunggah</p>
                  <h4 class="mb-0 fs-20 fw-bold text-success">
                    {{ specificJuknisCount }} Cabang
                  </h4>
                  <span class="text-muted fs-11">
                    Memiliki berkas tersendiri
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 4: Mengacu Juknis Umum -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                    <i class="ri-book-read-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Mengacu Juknis Umum</p>
                  <h4 class="mb-0 fs-20 fw-bold text-warning">
                    {{ competitions.length - specificJuknisCount }} Cabang
                  </h4>
                  <span class="text-muted fs-11">
                    Otomatis merujuk juknis master
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Card with Navigation Tabs -->
      <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom p-0">
          <ul class="nav nav-tabs nav-tabs-custom card-header-tabs border-bottom-0 mx-3" role="tablist">
            <li class="nav-item">
              <a 
                class="nav-link py-3 fw-semibold cursor-pointer" 
                :class="{ 'active': activeTab === 'general' }"
                @click="activeTab = 'general'"
              >
                <i class="ri-file-pdf-fill me-1 text-danger"></i>
                Juknis Umum (Keseluruhan Lomba)
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link py-3 fw-semibold cursor-pointer" 
                :class="{ 'active': activeTab === 'competitions' }"
                @click="activeTab = 'competitions'"
              >
                <i class="ri-folders-line me-1 text-primary"></i>
                Juknis Spesifik Per-Cabang Lomba ({{ competitions.length }})
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body p-4">
          <!-- ============================================================== -->
          <!-- TAB 1: JUKNIS UMUM (KESELURUHAN LOMBA)                          -->
          <!-- ============================================================== -->
          <div v-show="activeTab === 'general'">
            <div class="row g-4">
              <!-- Left: File Upload & Configuration Form -->
              <div class="col-lg-7">
                <div class="card border shadow-none">
                  <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                      <i class="ri-upload-cloud-2-line text-primary fs-18"></i>
                      <h5 class="card-title mb-0 fs-14 fw-bold text-dark">Perbarui File Juknis Umum</h5>
                    </div>
                    <span class="badge bg-danger-subtle text-danger fs-11">PDF / DOCX Max 30MB</span>
                  </div>
                  <div class="card-body p-4">
                    <p class="text-muted fs-13 mb-3">
                      File yang diunggah di sini secara otomatis terhubung ke seluruh tombol unduh juknis umum di web publik, antara lain:
                      <strong>Hero Banner (Unduh Juknis)</strong>, <strong>Banner Juknis Resmi</strong>, <strong>Navigasi Menu</strong>, dan <strong>Pop-up Pengumuman</strong>.
                    </p>

                    <form @submit.prevent="submitGeneralJuknis">
                      <!-- Mode Selection: Upload File or Cloud Link -->
                      <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted">Sumber Berkas Juknis:</label>
                        <div class="d-flex gap-2">
                          <button 
                            type="button" 
                            class="btn btn-sm flex-fill" 
                            :class="uploadMode === 'file' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="uploadMode = 'file'"
                          >
                            <i class="ri-file-upload-line me-1"></i> Unggah File Langsung (PDF/DOCX)
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-sm flex-fill" 
                            :class="uploadMode === 'url' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="uploadMode = 'url'"
                          >
                            <i class="ri-link me-1"></i> Tautan Cloud / Google Drive
                          </button>
                        </div>
                      </div>

                      <!-- Mode 1: File Upload -->
                      <div v-if="uploadMode === 'file'" class="mb-3">
                        <label class="form-label fs-13 fw-semibold text-dark">
                          Pilih Berkas PDF Juknis Baru <span class="text-danger">*</span>
                        </label>
                        <input 
                          type="file" 
                          ref="generalFileInput"
                          class="form-control"
                          accept=".pdf,.docx,.doc"
                          @change="onGeneralFileSelected"
                        />
                        <div v-if="selectedGeneralFile" class="mt-2 p-2 rounded bg-light border d-flex align-items-center justify-content-between fs-12">
                          <div class="d-flex align-items-center gap-2 text-truncate">
                            <i class="ri-file-pdf-fill text-danger fs-18"></i>
                            <span class="fw-semibold text-dark text-truncate">{{ selectedGeneralFile.name }}</span>
                            <span class="text-muted">({{ formatBytes(selectedGeneralFile.size) }})</span>
                          </div>
                          <button type="button" class="btn btn-link text-danger p-0 ms-2" @click="clearGeneralFile">
                            <i class="ri-close-line"></i>
                          </button>
                        </div>
                        <small class="text-muted fs-11 mt-1 d-block">
                          Format disarankan: Dokumen PDF resmi berstempel dan bertanda tangan panitia pelaksana.
                        </small>
                      </div>

                      <!-- Mode 2: External URL -->
                      <div v-else class="mb-3">
                        <label class="form-label fs-13 fw-semibold text-dark">
                          Tautan URL Berkas Juknis (Google Drive / Direct Link) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                          <span class="input-group-text bg-light"><i class="ri-links-line"></i></span>
                          <input 
                            type="url" 
                            v-model="generalUrlInput" 
                            class="form-control"
                            placeholder="https://drive.google.com/file/d/..."
                            required
                          />
                        </div>
                        <small class="text-muted fs-11 mt-1 d-block">
                          Pastikan hak akses tautan Google Drive telah disetel publik (Siapa saja yang memiliki tautan dapat melihat/mengunduh).
                        </small>
                      </div>

                      <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <button 
                          type="button" 
                          class="btn btn-outline-secondary btn-sm"
                          :disabled="isResettingGeneral"
                          @click="confirmResetGeneral"
                        >
                          <i class="ri-refresh-line me-1"></i>
                          {{ isResettingGeneral ? 'Mereset...' : 'Kembalikan Default Master' }}
                        </button>

                        <button 
                          type="submit" 
                          class="btn btn-primary px-4 shadow-sm"
                          :disabled="isSubmittingGeneral || (uploadMode === 'file' && !selectedGeneralFile) || (uploadMode === 'url' && !generalUrlInput)"
                        >
                          <i class="ri-save-line me-1"></i>
                          {{ isSubmittingGeneral ? 'Mengunggah & Menyimpan...' : 'Simpan Perubahan Juknis Umum' }}
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- Right: Active Juknis Status Card -->
              <div class="col-lg-5">
                <div class="card border shadow-none bg-light-subtle">
                  <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fs-13 fw-bold text-muted">
                      <i class="ri-information-line text-primary me-1"></i> Berkas Juknis Umum Yang Aktif Saat Ini
                    </h5>
                  </div>
                  <div class="card-body p-4">
                    <div class="p-3 rounded border bg-white shadow-sm mb-3">
                      <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar-md flex-shrink-0">
                          <span class="avatar-title bg-danger-subtle text-danger rounded-3 fs-24">
                            <i class="ri-file-pdf-2-fill"></i>
                          </span>
                        </div>
                        <div class="overflow-hidden">
                          <span class="badge bg-success-subtle text-success fs-10 mb-1">
                            <i class="ri-checkbox-circle-fill me-1"></i>Aktif di Seluruh Web
                          </span>
                          <h6 class="mb-0 fs-14 fw-bold text-dark text-truncate" :title="generalJuknis.filename">
                            {{ generalJuknis.filename || 'JUKNIS-SEMARAK-2026.pdf' }}
                          </h6>
                          <small class="text-muted">{{ generalJuknis.size_formatted || '2.5 MB' }}</small>
                        </div>
                      </div>

                      <div class="list-group list-group-flush fs-12 mb-3">
                        <div class="list-group-item px-0 py-1.5 d-flex justify-content-between bg-transparent">
                          <span class="text-muted">Jalur / Lokasi File:</span>
                          <span class="font-monospace fw-semibold text-truncate ms-2" style="max-width: 200px;" :title="generalJuknis.url">
                            {{ generalJuknis.url }}
                          </span>
                        </div>
                        <div class="list-group-item px-0 py-1.5 d-flex justify-content-between bg-transparent">
                          <span class="text-muted">Terakhir Diperbarui:</span>
                          <span class="fw-semibold text-dark">
                            {{ formatDateTime(generalJuknis.updated_at) }}
                          </span>
                        </div>
                      </div>

                      <div class="d-grid gap-2">
                        <a 
                          :href="generalJuknis.url || '/assets/JUKNIS-SEMARAK-2026.pdf'" 
                          target="_blank" 
                          download
                          class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center gap-1 shadow-sm"
                        >
                          <i class="ri-download-2-line"></i> Uji Unduh Berkas Juknis Sekarang
                        </a>
                      </div>
                    </div>

                    <div class="alert alert-info border-0 shadow-sm p-3 mb-0 fs-12">
                      <div class="d-flex gap-2">
                        <i class="ri-lightbulb-line fs-16 text-info flex-shrink-0"></i>
                        <div>
                          <strong>Label Tombol Web Tidak Berubah:</strong> Sesuai standar, tombol pada web publik tetap bertuliskan <em>"Unduh Juknis (PDF)"</em> dan <em>"Unduh Juknis Resmi (PDF)"</em>, namun isi dokumen otomatis mengarah ke file yang Anda unggah di sini.
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 2: JUKNIS SPESIFIK PER-CABANG LOMBA                        -->
          <!-- ============================================================== -->
          <div v-show="activeTab === 'competitions'">
            <!-- Search & Filter Controls -->
            <div class="row g-3 align-items-center mb-4">
              <div class="col-md-5">
                <div class="search-box position-relative">
                  <input 
                    type="text" 
                    v-model="compSearchQuery" 
                    class="form-control ps-5" 
                    placeholder="Cari cabang lomba berdasarkan nama..."
                  />
                  <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                  <button 
                    v-if="compSearchQuery" 
                    type="button" 
                    class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 p-0 text-muted"
                    @click="compSearchQuery = ''"
                  >
                    <i class="ri-close-circle-fill"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-4">
                <CustomSelect v-model="compFilterCategory" size="md">
                  <option value="all">Semua Kategori Bidang</option>
                  <option v-for="cat in uniqueCategories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </CustomSelect>
              </div>

              <div class="col-md-3 text-end">
                <span class="fs-12 text-muted fw-semibold">
                  Menampilkan {{ filteredCompetitions.length }} dari {{ competitions.length }} cabang
                </span>
              </div>
            </div>

            <!-- Table of Competitions with Specific Juknis -->
            <div class="table-responsive">
              <table class="table table-hover align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width: 50px;">No</th>
                    <th scope="col">Cabang Perlombaan</th>
                    <th scope="col">Bidang</th>
                    <th scope="col">Status Berkas Juknis</th>
                    <th scope="col">Nama Berkas & Ukuran</th>
                    <th scope="col" class="text-center" style="width: 200px;">Aksi Berkas</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(comp, idx) in filteredCompetitions" :key="comp.id">
                    <td>
                      <span class="text-muted fw-semibold">{{ idx + 1 }}</span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs flex-shrink-0">
                          <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-14">
                            <i :class="getCompIconClass(comp)"></i>
                          </span>
                        </div>
                        <div>
                          <h6 class="fs-13 mb-0 fw-bold text-dark">{{ comp.title }}</h6>
                          <small class="text-muted font-monospace fs-11">ID: {{ comp.id }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" :class="getCategoryBadgeClass(comp.category)">
                        {{ comp.category_label || comp.category || 'Umum' }}
                      </span>
                    </td>
                    <td>
                      <span 
                        v-if="comp.juknis_url" 
                        class="badge bg-success-subtle text-success fs-11 d-inline-flex align-items-center gap-1"
                      >
                        <i class="ri-checkbox-circle-fill"></i> Juknis Khusus Diunggah
                      </span>
                      <span 
                        v-else 
                        class="badge bg-info-subtle text-info fs-11 d-inline-flex align-items-center gap-1"
                      >
                        <i class="ri-links-line"></i> Mengacu Juknis Umum
                      </span>
                    </td>
                    <td>
                      <div v-if="comp.juknis_url" class="d-flex flex-column">
                        <span class="fw-semibold text-dark fs-12 text-truncate" style="max-width: 240px;" :title="comp.juknis_filename">
                          {{ comp.juknis_filename }}
                        </span>
                        <small class="text-muted fs-11">
                          {{ comp.juknis_size ? formatBytes(comp.juknis_size) : 'File PDF' }}
                        </small>
                      </div>
                      <span v-else class="text-muted fs-12 fst-italic">
                        Mengikuti Buku Juknis Keseluruhan ({{ generalJuknis.filename }})
                      </span>
                    </td>
                    <td class="text-center">
                      <div class="d-flex align-items-center justify-content-center gap-1.5">
                        <!-- Upload / Replace Specific Juknis -->
                        <button 
                          type="button" 
                          class="btn btn-sm btn-soft-primary d-inline-flex align-items-center gap-1"
                          @click="openUploadModal(comp)"
                          title="Unggah / Ganti Berkas Juknis Cabang Ini"
                        >
                          <i class="ri-upload-2-line"></i>
                          <span>{{ comp.juknis_url ? 'Ganti' : 'Unggah' }}</span>
                        </button>

                        <!-- Download Test (if exists) -->
                        <a 
                          v-if="comp.juknis_url"
                          :href="comp.juknis_url" 
                          target="_blank" 
                          download
                          class="btn btn-sm btn-soft-success d-inline-flex align-items-center gap-1"
                          title="Uji Unduh Juknis Khusus Cabang Ini"
                        >
                          <i class="ri-download-2-line"></i>
                          <span class="d-none d-sm-inline">Tes</span>
                        </a>

                        <!-- Delete / Reset to General Juknis (if exists) -->
                        <button 
                          v-if="comp.juknis_url"
                          type="button" 
                          class="btn btn-sm btn-soft-danger d-inline-flex align-items-center"
                          @click="confirmDeleteCompJuknis(comp)"
                          title="Hapus Juknis Khusus (Kembali Mengacu Juknis Umum)"
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

    <!-- ============================================================== -->
    <!-- MODAL UNGGAH JUKNIS SPESIFIK CABANG LOMBA                      -->
    <!-- ============================================================== -->
    <div v-if="isUploadModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isUploadModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light py-3">
            <h5 class="modal-title fw-bold fs-15 d-flex align-items-center gap-2">
              <i class="ri-file-pdf-2-line text-danger"></i>
              Unggah Juknis Khusus: {{ activeUploadComp?.title }}
            </h5>
            <button type="button" class="btn-close" @click="closeUploadModal"></button>
          </div>

          <form @submit.prevent="submitCompJuknis">
            <div class="modal-body p-4">
              <p class="text-muted fs-13 mb-3">
                Berkas juknis khusus ini akan langsung muncul sebagai tombol unduh tersendiri di dalam popup 
                <strong>"Detail & Juknis"</strong> cabang lomba ini pada web publik.
              </p>

              <!-- Current file info if exists -->
              <div v-if="activeUploadComp?.juknis_url" class="p-3 mb-3 rounded border bg-light fs-12">
                <div class="text-muted mb-1">Berkas Khusus Yang Terpasang Saat Ini:</div>
                <div class="fw-bold text-dark text-truncate">{{ activeUploadComp.juknis_filename }}</div>
                <div class="text-muted mt-1 fs-11">
                  Ukuran: {{ activeUploadComp.juknis_size ? formatBytes(activeUploadComp.juknis_size) : '-' }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fs-13 fw-semibold text-dark">
                  Pilih File Dokumen Juknis (PDF / DOCX) <span class="text-danger">*</span>
                </label>
                <input 
                  type="file" 
                  ref="compFileInput"
                  class="form-control"
                  accept=".pdf,.docx,.doc"
                  required
                  @change="onCompFileSelected"
                />
                <div v-if="selectedCompFile" class="mt-2 p-2 rounded bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-2 fs-12">
                  <i class="ri-checkbox-circle-fill fs-16"></i>
                  <span class="fw-semibold text-truncate">{{ selectedCompFile.name }} ({{ formatBytes(selectedCompFile.size) }})</span>
                </div>
                <small class="text-muted fs-11 mt-1 d-block">
                  Maksimal ukuran file: 30 Megabytes.
                </small>
              </div>
            </div>

            <div class="modal-footer bg-light py-2">
              <button type="button" class="btn btn-light btn-sm" @click="closeUploadModal">Batal</button>
              <button 
                type="submit" 
                class="btn btn-primary btn-sm px-4 shadow-sm"
                :disabled="isSubmittingCompJuknis || !selectedCompFile"
              >
                {{ isSubmittingCompJuknis ? 'Mengunggah...' : 'Simpan Berkas Juknis' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  generalJuknis: {
    type: Object,
    default: () => ({
      url: '/assets/JUKNIS-SEMARAK-2026.pdf',
      filename: 'JUKNIS-SEMARAK-2026.pdf',
      size_formatted: '2.5 MB',
      updated_at: null,
    })
  },
  competitions: {
    type: Array,
    default: () => []
  }
});

const { success, error } = useToast();

// Tab state: 'general' | 'competitions'
const activeTab = ref('general');

// Count specific juknis
const specificJuknisCount = computed(() => {
  return props.competitions.filter(c => !!c.juknis_url).length;
});

// =========================================================================
// TAB 1: GENERAL JUKNIS STATE & ACTIONS
// =========================================================================
const uploadMode = ref('file'); // 'file' | 'url'
const generalFileInput = ref(null);
const selectedGeneralFile = ref(null);
const generalUrlInput = ref('');
const isSubmittingGeneral = ref(false);
const isResettingGeneral = ref(false);

function onGeneralFileSelected(e) {
  const file = e.target.files?.[0];
  if (file) {
    if (file.size > 30 * 1024 * 1024) {
      error('Ukuran file melebihi batas maksimal 30 MB!');
      e.target.value = '';
      selectedGeneralFile.value = null;
      return;
    }
    selectedGeneralFile.value = file;
  }
}

function clearGeneralFile() {
  selectedGeneralFile.value = null;
  if (generalFileInput.value) {
    generalFileInput.value.value = '';
  }
}

function submitGeneralJuknis() {
  isSubmittingGeneral.value = true;
  const formData = new FormData();

  if (uploadMode.value === 'file') {
    if (!selectedGeneralFile.value) {
      isSubmittingGeneral.value = false;
      error('Pilih file dokumen PDF terlebih dahulu!');
      return;
    }
    formData.append('juknis_file', selectedGeneralFile.value);
  } else {
    if (!generalUrlInput.value) {
      isSubmittingGeneral.value = false;
      error('Masukkan URL tautan Google Drive / Cloud!');
      return;
    }
    formData.append('external_url', generalUrlInput.value);
  }

  router.post('/admin/juknis/general', formData, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingGeneral.value = false;
      clearGeneralFile();
      generalUrlInput.value = '';
      success('File Juknis Umum berhasil diperbarui dan aktif di seluruh web!');
    },
    onError: (errs) => {
      isSubmittingGeneral.value = false;
      error(Object.values(errs)[0] || 'Gagal mengunggah file Juknis Umum.');
    }
  });
}

function confirmResetGeneral() {
  if (confirm('Apakah Anda yakin ingin mengembalikan berkas Juknis Umum ke file master bawaan sistem?')) {
    isResettingGeneral.value = true;
    router.post('/admin/juknis/general/reset', {}, {
      preserveScroll: true,
      onSuccess: () => {
        isResettingGeneral.value = false;
        success('Juknis Umum berhasil dikembalikan ke file master bawaan.');
      },
      onError: () => {
        isResettingGeneral.value = false;
        error('Gagal mereset Juknis Umum.');
      }
    });
  }
}

// =========================================================================
// TAB 2: COMPETITIONS JUKNIS STATE & ACTIONS
// =========================================================================
const compSearchQuery = ref('');
const compFilterCategory = ref('all');

const uniqueCategories = computed(() => {
  const cats = props.competitions.map(c => c.category_label || c.category || 'Kompetisi');
  return [...new Set(cats)];
});

const filteredCompetitions = computed(() => {
  return props.competitions.filter(c => {
    if (compSearchQuery.value) {
      const q = compSearchQuery.value.toLowerCase().trim();
      const matchTitle = c.title && c.title.toLowerCase().includes(q);
      const matchId = c.id && c.id.toLowerCase().includes(q);
      const matchCat = (c.category_label || c.category || '').toLowerCase().includes(q);
      if (!matchTitle && !matchId && !matchCat) return false;
    }

    if (compFilterCategory.value !== 'all') {
      const cat = c.category_label || c.category;
      if (cat !== compFilterCategory.value) return false;
    }

    return true;
  });
});

// Upload Modal State
const isUploadModalOpen = ref(false);
const activeUploadComp = ref(null);
const compFileInput = ref(null);
const selectedCompFile = ref(null);
const isSubmittingCompJuknis = ref(false);

function openUploadModal(comp) {
  activeUploadComp.value = comp;
  selectedCompFile.value = null;
  isUploadModalOpen.value = true;
}

function closeUploadModal() {
  isUploadModalOpen.value = false;
  activeUploadComp.value = null;
  selectedCompFile.value = null;
}

function onCompFileSelected(e) {
  const file = e.target.files?.[0];
  if (file) {
    if (file.size > 30 * 1024 * 1024) {
      error('Ukuran file melebihi batas maksimal 30 MB!');
      e.target.value = '';
      selectedCompFile.value = null;
      return;
    }
    selectedCompFile.value = file;
  }
}

function submitCompJuknis() {
  if (!activeUploadComp.value || !selectedCompFile.value) return;

  isSubmittingCompJuknis.value = true;
  const formData = new FormData();
  formData.append('juknis_file', selectedCompFile.value);

  router.post(`/admin/juknis/competition/${activeUploadComp.value.id}`, formData, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingCompJuknis.value = false;
      closeUploadModal();
      success(`Berkas Juknis khusus untuk cabang "${activeUploadComp.value?.title}" berhasil diunggah!`);
    },
    onError: (errs) => {
      isSubmittingCompJuknis.value = false;
      error(Object.values(errs)[0] || 'Gagal mengunggah berkas juknis cabang.');
    }
  });
}

function confirmDeleteCompJuknis(comp) {
  if (confirm(`Hapus berkas Juknis khusus untuk cabang "${comp.title}"? Cabang ini akan kembali mengacu pada Buku Juknis Umum.`)) {
    router.delete(`/admin/juknis/competition/${comp.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        success(`Berkas Juknis khusus "${comp.title}" berhasil dihapus.`);
      },
      onError: () => {
        error('Gagal menghapus berkas juknis cabang.');
      }
    });
  }
}

// Helpers
function formatBytes(bytes) {
  if (!bytes || bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDateTime(dtStr) {
  if (!dtStr) return 'Bawaan Master Awal';
  try {
    const d = new Date(dtStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const mins = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${mins} WIB`;
  } catch (e) {
    return dtStr;
  }
}

function getCompIconClass(comp) {
  if (!comp) return 'ri-trophy-line';
  const id = String(comp.id || '').toLowerCase();
  const title = String(comp.title || '').toLowerCase();
  const category = String(comp.category || '').toLowerCase();

  if (id.includes('voli') || title.includes('voli') || title.includes('volley')) return 'ri-football-line';
  if (id.includes('ml') || id.includes('esport') || title.includes('mobile legends')) return 'ri-gamepad-line';
  if (id.includes('mipa') || title.includes('mipa') || title.includes('sains')) return 'ri-microscope-line';
  if (id.includes('ips') || title.includes('ips') || title.includes('sosial')) return 'ri-earth-line';
  if (id.includes('story') || title.includes('story') || title.includes('pidato')) return 'ri-mic-line';
  if (id.includes('banjari') || title.includes('banjari') || title.includes('hadrah')) return 'ri-music-2-line';
  if (id.includes('singer') || title.includes('singer') || title.includes('nyanyi')) return 'ri-mv-line';
  if (id.includes('tahfidz') || title.includes('tahfidz') || title.includes('quran')) return 'ri-book-open-line';
  if (category === 'akademik') return 'ri-flask-line';
  if (category === 'olahraga') return 'ri-medal-line';
  if (category === 'seni') return 'ri-palette-line';
  return 'ri-trophy-line';
}

function getCategoryBadgeClass(category) {
  const c = String(category || '').toLowerCase();
  if (c.includes('akademik') || c.includes('sains')) return 'bg-primary-subtle text-primary';
  if (c.includes('olahraga') || c.includes('voli')) return 'bg-success-subtle text-success';
  if (c.includes('seni') || c.includes('singer') || c.includes('banjari')) return 'bg-warning-subtle text-warning';
  if (c.includes('keagamaan') || c.includes('tahfidz')) return 'bg-info-subtle text-info';
  return 'bg-secondary-subtle text-secondary';
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.table th {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}
</style>
