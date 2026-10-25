<script setup>
import { ref, computed } from 'vue';
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
  seo_meta_title: props.settings.seo_meta_title || 'SEMARAK ALIYAH XIV 2026 - MA Unggulan Wahid Hasyim Balung',
  seo_meta_description: props.settings.seo_meta_description || 'Ajang kompetisi tahunan bergengsi siswa/i SMP/MTs sederajat se-Kabupaten Jember. Raih piala juara, piagam resmi, uang pembinaan jutaan rupiah, dan beasiswa tabungan pendidikan.',
  seo_meta_keywords: props.settings.seo_meta_keywords || 'semarak aliyah, ma wahid hasyim balung, olimpiade mipa jember, storytelling, festival albanjari, singer lagu islami, voli jember, mobile legends',
  seo_author: props.settings.seo_author || 'Panitia Semarak Aliyah XIV 2026 • MA Unggulan Wahid Hasyim Balung',
  seo_canonical_url: props.settings.seo_canonical_url || 'http://localhost:8000',
  seo_robots: props.settings.seo_robots || 'index, follow',
  seo_og_title: props.settings.seo_og_title || 'SEMARAK ALIYAH XIV 2026 - Panggung Prestasi Pelajar SMP/MTs',
  seo_og_description: props.settings.seo_og_description || 'Daftarkan delegasi sekolah/madrasah Anda di ajang Semarak Aliyah XIV 2026. Kompetisi Sains, Seni, Religi, Olahraga, dan E-Sport!',
  seo_og_image: props.settings.seo_og_image || '/assets/img/og-thumbnail.jpg',
  seo_og_image_file: null,
  seo_twitter_card: props.settings.seo_twitter_card || 'summary_large_image',
  seo_theme_color: props.settings.seo_theme_color || '#8b5cf6',
});

const activePreviewTab = ref('google'); // 'google', 'whatsapp', 'facebook'
const imagePreviewUrl = ref(props.settings.seo_og_image || '/assets/img/og-thumbnail.jpg');

const ogHostname = computed(() => {
  try {
    return new window.URL(form.seo_canonical_url).hostname;
  } catch {
    return 'localhost';
  }
});

function onImageFileChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.seo_og_image_file = file;
    imagePreviewUrl.value = window.URL.createObjectURL(file);
  }
}

function submitForm() {
  form.post('/admin/pengaturan/seo-og', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      success('Pengaturan SEO dan Open Graph pratinjau media sosial berhasil disimpan.', 'Tersimpan');
    },
    onError: () => {
      error('Mohon periksa isian data formulir SEO.', 'Gagal Menyimpan');
    }
  });
}
</script>

<template>
  <Head title="Setting SEO & Open Graph | Pengaturan Admin" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-sm-0 fw-bold d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-search-eye-line text-info fs-22"></i>
                Setting SEO & Open Graph untuk Web
              </h4>
              <p class="text-muted fs-12 mb-0 mt-1">
                Optimalkan keterlihatan portal di hasil pencarian Google serta kartu pratinjau saat tautan dibagikan ke WhatsApp, Facebook, dan media sosial.
              </p>
            </div>
            <div class="page-title-right mt-2 mt-sm-0">
              <ol class="breadcrumb m-0 fs-12">
                <li class="breadcrumb-item"><Link href="/admin">Dashboard</Link></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active text-info">SEO & Open Graph</li>
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
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill fw-semibold fs-13 bg-info text-dark"
                  >
                    <i class="ri-search-eye-line text-dark"></i>
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
              </ul>

              <div class="d-flex align-items-center gap-2">
                <a href="/" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3">
                  <i class="ri-external-link-line me-1"></i> Uji Halaman Publik
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submitForm">
        <div class="row g-4">
          <!-- Left Column: Settings Inputs -->
          <div class="col-lg-7">
            <!-- 1. Metadata Mesin Pencari (Google SEO) -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                  <i class="ri-google-fill fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Metadata Mesin Pencari (Search Engine SEO)</h6>
                  <small class="text-muted fs-11">Tag meta standar untuk Google, Bing, dan robot pengindeks</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                      <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                        Meta Title (Judul Halaman Web) <span class="text-danger">*</span>
                      </label>
                      <span
                        class="fs-11 fw-mono"
                        :class="form.seo_meta_title.length > 60 ? 'text-warning' : 'text-success'"
                      >
                        {{ form.seo_meta_title.length }}/60 karakter (Optimal: 50-60)
                      </span>
                    </div>
                    <input
                      v-model="form.seo_meta_title"
                      type="text"
                      class="form-control"
                      placeholder="SEMARAK ALIYAH XIV 2026 - MA Unggulan Wahid Hasyim Balung"
                      required
                    />
                    <div v-if="form.errors.seo_meta_title" class="text-danger fs-11 mt-1">{{ form.errors.seo_meta_title }}</div>
                  </div>

                  <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                      <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                        Meta Description (Deskripsi Ringkasan) <span class="text-danger">*</span>
                      </label>
                      <span
                        class="fs-11 fw-mono"
                        :class="form.seo_meta_description.length > 160 ? 'text-warning' : 'text-success'"
                      >
                        {{ form.seo_meta_description.length }}/160 karakter (Optimal: 120-160)
                      </span>
                    </div>
                    <textarea
                      v-model="form.seo_meta_description"
                      rows="3"
                      class="form-control"
                      placeholder="Deskripsi ringkas yang memikat calon pendaftar saat melihat di hasil pencarian Google..."
                      required
                    ></textarea>
                    <div v-if="form.errors.seo_meta_description" class="text-danger fs-11 mt-1">{{ form.errors.seo_meta_description }}</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                      Meta Keywords (Kata Kunci Pencarian)
                    </label>
                    <input
                      v-model="form.seo_meta_keywords"
                      type="text"
                      class="form-control"
                      placeholder="semarak aliyah, ma wahid hasyim balung, olimpiade mipa jember, storytelling"
                    />
                    <small class="text-muted fs-11">Pisahkan kata kunci menggunakan tanda koma (,).</small>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Author / Pemilik Konten</label>
                    <input
                      v-model="form.seo_author"
                      type="text"
                      class="form-control"
                      placeholder="Panitia Semarak Aliyah XIV 2026"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Robots Indexing</label>
                    <CustomSelect v-model="form.seo_robots" size="md">
                      <option value="index, follow">index, follow (Direkomendasikan - Izinkan Google indeks)</option>
                      <option value="noindex, follow">noindex, follow (Sembunyikan dari Google, telusuri link)</option>
                      <option value="index, nofollow">index, nofollow (Indeks halaman, jangan telusuri link)</option>
                      <option value="noindex, nofollow">noindex, nofollow (Tolak pengindeksan penuh)</option>
                    </CustomSelect>
                  </div>

                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Canonical URL</label>
                    <input
                      v-model="form.seo_canonical_url"
                      type="url"
                      class="form-control"
                      placeholder="http://localhost:8000"
                    />
                    <small class="text-muted fs-11">URL kanonis resmi untuk menghindari duplikasi konten di mesin pencari.</small>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Browser Theme Color</label>
                    <div class="d-flex align-items-center gap-2">
                      <input
                        v-model="form.seo_theme_color"
                        type="color"
                        class="form-control form-control-color p-1"
                        style="width: 44px; height: 38px;"
                      />
                      <input
                        v-model="form.seo_theme_color"
                        type="text"
                        class="form-control font-mono fs-12"
                        placeholder="#8b5cf6"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Open Graph & Kartu Media Sosial -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                  <i class="ri-whatsapp-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Open Graph & Kartu Pratinjau Sosial (WhatsApp, Facebook, Twitter)</h6>
                  <small class="text-muted fs-11">Konten yang muncul saat link dibagikan di chat WhatsApp atau postingan medsos</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                      Open Graph Title (Judul Share Media Sosial) <span class="text-danger">*</span>
                    </label>
                    <input
                      v-model="form.seo_og_title"
                      type="text"
                      class="form-control"
                      placeholder="SEMARAK ALIYAH XIV 2026 - Panggung Prestasi Pelajar SMP/MTs"
                      required
                    />
                    <div v-if="form.errors.seo_og_title" class="text-danger fs-11 mt-1">{{ form.errors.seo_og_title }}</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                      Open Graph Description (Deskripsi Ringkas Share) <span class="text-danger">*</span>
                    </label>
                    <textarea
                      v-model="form.seo_og_description"
                      rows="2"
                      class="form-control"
                      placeholder="Daftarkan delegasi sekolah/madrasah Anda di ajang Semarak Aliyah XIV 2026..."
                      required
                    ></textarea>
                    <div v-if="form.errors.seo_og_description" class="text-danger fs-11 mt-1">{{ form.errors.seo_og_description }}</div>
                  </div>

                  <div class="col-md-7">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Tautan Gambar Banner OG (URL Path)</label>
                    <input
                      v-model="form.seo_og_image"
                      type="text"
                      class="form-control"
                      placeholder="/assets/img/og-thumbnail.jpg"
                    />
                    <small class="text-muted fs-11 d-block mt-1">
                      <i class="ri-information-line text-info me-1"></i>
                      Rekomendasi rasio: 1.91:1 (1200 x 630 px). WhatsApp mewajibkan ukuran file di bawah 300 KB. Sistem otomatis mengompresi gambar yang diunggah.
                    </small>
                  </div>

                  <div class="col-md-5">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Unggah Banner Baru</label>
                    <input
                      type="file"
                      class="form-control fs-12"
                      accept="image/jpeg,image/png,image/webp"
                      @change="onImageFileChange"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Twitter / X Card Type</label>
                    <CustomSelect v-model="form.seo_twitter_card" size="md">
                      <option value="summary_large_image">summary_large_image (Gambar Banner Besar - Rekomendasi)</option>
                      <option value="summary">summary (Ikon Thumbnail Kecil di samping)</option>
                    </CustomSelect>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-dark-subtle border border-secondary/20 mb-5">
              <span class="text-muted fs-12">
                <i class="ri-check-double-line me-1 text-info"></i>
                Meta tag akan langsung diinjeksikan pada tag &lt;head&gt; halaman publik.
              </span>
              <button
                type="submit"
                class="btn btn-info text-dark px-4 py-2 rounded-pill fw-bold d-flex align-items-center gap-2 shadow-sm"
                :disabled="form.processing"
              >
                <i v-if="form.processing" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-3-line"></i>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan SEO' }}</span>
              </button>
            </div>
          </div>

          <!-- Right Column: Interactive Live Social Share Simulator -->
          <div class="col-lg-5">
            <div class="sticky-top" style="top: 90px; z-index: 10;">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="fs-12 fw-bold text-muted text-uppercase tracking-wider">
                  <i class="ri-smartphone-line me-1 text-info"></i> Simulator Tampilan Berbagi
                </span>
                <div class="btn-group btn-group-sm" role="group">
                  <button
                    type="button"
                    class="btn"
                    :class="activePreviewTab === 'google' ? 'btn-primary' : 'btn-outline-secondary text-muted'"
                    @click="activePreviewTab = 'google'"
                    title="Google Search"
                  >
                    <i class="ri-google-fill"></i> Google
                  </button>
                  <button
                    type="button"
                    class="btn"
                    :class="activePreviewTab === 'whatsapp' ? 'btn-success' : 'btn-outline-secondary text-muted'"
                    @click="activePreviewTab = 'whatsapp'"
                    title="WhatsApp Chat"
                  >
                    <i class="ri-whatsapp-line"></i> WhatsApp
                  </button>
                  <button
                    type="button"
                    class="btn"
                    :class="activePreviewTab === 'facebook' ? 'btn-info text-dark' : 'btn-outline-secondary text-muted'"
                    @click="activePreviewTab = 'facebook'"
                    title="Facebook / Twitter"
                  >
                    <i class="ri-facebook-fill"></i> Facebook
                  </button>
                </div>
              </div>

              <!-- SIMULATOR 1: GOOGLE SEARCH SERP -->
              <div
                v-if="activePreviewTab === 'google'"
                class="card border-0 shadow-lg p-3 rounded-4"
                style="background: #202124; color: #bdc1c6; font-family: Roboto, Arial, sans-serif;"
              >
                <div class="d-flex align-items-center gap-2 mb-1">
                  <div class="rounded-circle bg-white p-1" style="width: 26px; height: 26px;">
                    <img src="/assets/img/logo.png" alt="Favicon" style="width: 100%; height: 100%; object-fit: contain;" />
                  </div>
                  <div>
                    <div class="fs-12 text-white fw-semibold lh-1">Semarak Aliyah XIV 2026</div>
                    <div class="fs-11 text-slate-400 font-mono lh-1">{{ form.seo_canonical_url || 'http://localhost:8000' }}</div>
                  </div>
                </div>
                <h5 class="fs-15 fw-normal mt-1 mb-1 text-[#8ab4f8] hover:underline cursor-pointer" style="color: #8ab4f8;">
                  {{ form.seo_meta_title || 'SEMARAK ALIYAH XIV 2026' }}
                </h5>
                <p class="fs-12 lh-sm text-[#bdc1c6] mb-0" style="color: #bdc1c6;">
                  {{ form.seo_meta_description || 'Deskripsi ringkas yang memikat pengunjung.' }}
                </p>
                <div class="mt-2 pt-2 border-top border-slate-700 d-flex align-items-center gap-3 fs-11 text-slate-400">
                  <span>Robots: <strong class="text-success">{{ form.seo_robots }}</strong></span>
                  <span>Author: {{ form.seo_author }}</span>
                </div>
              </div>

              <!-- SIMULATOR 2: WHATSAPP CHAT UNURL BUBBLE -->
              <div
                v-if="activePreviewTab === 'whatsapp'"
                class="rounded-4 p-3 shadow-lg"
                style="background: #0b141a; background-image: radial-gradient(#1f2c34 1px, transparent 1px); background-size: 16px 16px;"
              >
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge bg-success-subtle text-success fs-10 px-2 py-0.5 rounded-pill">WhatsApp Link Unfurl</span>
                </div>

                <!-- Chat Bubble -->
                <div class="rounded-3 overflow-hidden shadow-sm ms-auto" style="max-width: 340px; background: #005c4b; color: #e9edef;">
                  <!-- Image thumbnail -->
                  <div class="overflow-hidden bg-black/30" style="height: 160px;">
                    <img
                      :src="imagePreviewUrl"
                      alt="OG Image Preview"
                      class="w-100 h-100"
                      style="object-fit: cover;"
                      @error="imagePreviewUrl = '/assets/img/og-thumbnail.jpg'"
                    />
                  </div>
                  <div class="p-2.5" style="background: rgba(0,0,0,0.25);">
                    <div class="text-slate-300 fs-10 text-uppercase fw-semibold mb-0.5">
                      {{ ogHostname }}
                    </div>
                    <h6 class="fs-12 fw-bold text-white mb-1 lh-sm">{{ form.seo_og_title || 'SEMARAK ALIYAH XIV' }}</h6>
                    <p class="fs-11 text-slate-200 lh-sm mb-1 line-clamp-2">
                      {{ form.seo_og_description || 'Panggung prestasi pelajar...' }}
                    </p>
                  </div>
                  <div class="px-2.5 py-1.5 text-end text-slate-300 fs-10">
                    12:45 <i class="ri-check-double-line text-info"></i>
                  </div>
                </div>
              </div>

              <!-- SIMULATOR 3: FACEBOOK / TWITTER LARGE CARD -->
              <div
                v-if="activePreviewTab === 'facebook'"
                class="card border-0 shadow-lg overflow-hidden rounded-4"
                style="background: #242526; color: #e4e6eb;"
              >
                <div class="overflow-hidden bg-black" style="height: 180px;">
                  <img
                    :src="imagePreviewUrl"
                    alt="Social Preview"
                    class="w-100 h-100"
                    style="object-fit: cover;"
                    @error="imagePreviewUrl = '/assets/img/og-thumbnail.jpg'"
                  />
                </div>
                <div class="p-3" style="background: #242526;">
                  <div class="text-slate-400 fs-10 text-uppercase fw-semibold tracking-wide">
                    {{ ogHostname }}
                  </div>
                  <h6 class="fs-13 fw-bold text-white mt-1 mb-1 lh-sm">{{ form.seo_og_title }}</h6>
                  <p class="fs-11 text-slate-300 lh-sm mb-0">
                    {{ form.seo_og_description }}
                  </p>
                </div>
              </div>

              <!-- Quick Checklist Advice -->
              <div class="card border-0 shadow-sm mt-3 p-3 rounded-3" style="background: var(--vz-card-bg, #1e293b); border: 1px solid rgba(255,255,255,0.08) !important;">
                <h6 class="fs-12 fw-bold text-white mb-2 d-flex align-items-center gap-1">
                  <i class="ri-lightbulb-line text-warning"></i> Tips Optimasi Berbagi
                </h6>
                <ul class="fs-11 text-muted ps-3 mb-0 space-y-1">
                  <li>Gunakan gambar beresolusi minimal 1200x630 pixel untuk kartu besar.</li>
                  <li>Pastikan Meta Title mengandung nama acara & kabupaten ("Jember").</li>
                  <li>Setelah menyimpan, Anda dapat mengecek via WhatsApp atau Facebook Sharing Debugger.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </VelzonLayout>
</template>
