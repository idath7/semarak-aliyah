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
  maintenance_mode: Boolean(props.settings.maintenance_mode),
  maintenance_title: props.settings.maintenance_title || 'Situs Sedang Dalam Pemeliharaan Berkala',
  maintenance_message: props.settings.maintenance_message || 'Mohon maaf atas ketidaknyamanan ini. Saat ini sistem Semarak Aliyah XIV 2026 sedang dalam proses peningkatan performa dan sinkronisasi data teknis. Website publik akan segera kembali online.',
  maintenance_estimated_end: props.settings.maintenance_estimated_end || '19 Oktober 2026, 12:00 WIB',
  maintenance_emergency_wa: props.settings.maintenance_emergency_wa || '0838 5007 0767',
  maintenance_secret_key: props.settings.maintenance_secret_key || 'semarak2026bypass',
});

const bypassUrl = computed(() => {
  return `${window.location.protocol}//${window.location.hostname}:8000/?bypass=${form.maintenance_secret_key}`;
});

const isCopied = ref(false);

function copyBypassUrl() {
  navigator.clipboard.writeText(bypassUrl.value).then(() => {
    isCopied.value = true;
    success('Tautan akses rahasia berhasil disalin ke clipboard!', 'Tersalin');
    setTimeout(() => {
      isCopied.value = false;
    }, 2500);
  });
}

function submitForm() {
  form.post('/admin/pengaturan/mode-perawatan', {
    preserveScroll: true,
    onSuccess: () => {
      const status = form.maintenance_mode ? 'diaktifkan' : 'dinonaktifkan';
      success(`Mode perawatan website berhasil ${status}.`, 'Berhasil Disimpan');
    },
    onError: () => {
      error('Mohon periksa data formulir pemeliharaan.', 'Gagal Menyimpan');
    }
  });
}
</script>

<template>
  <Head title="Setting Mode Perawatan Web | Pengaturan Admin" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-sm-0 fw-bold d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-tools-line text-warning fs-22"></i>
                Pengaturan Mode Perawatan Web (Maintenance Mode)
              </h4>
              <p class="text-muted fs-12 mb-0 mt-1">
                Alihkan pengunjung publik ke halaman pemeliharaan sementara saat perbaikan sistem, perapian data, atau penutupan sementara portal.
              </p>
            </div>
            <div class="page-title-right mt-2 mt-sm-0">
              <ol class="breadcrumb m-0 fs-12">
                <li class="breadcrumb-item"><Link href="/admin">Dashboard</Link></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active text-warning">Mode Perawatan</li>
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
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill fw-semibold fs-13 bg-warning text-dark"
                  >
                    <i class="ri-tools-line text-dark"></i>
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
                <!-- Status Badge Pill -->
                <span
                  class="badge px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2 fs-12"
                  :class="form.maintenance_mode ? 'bg-danger text-white' : 'bg-success text-white'"
                >
                  <span class="rounded-circle bg-white" style="width: 8px; height: 8px; animation: pulse 1.5s infinite;"></span>
                  <span>{{ form.maintenance_mode ? 'STATUS: MODE PERAWATAN AKTIF' : 'STATUS: NORMAL ONLINE' }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <form @submit.prevent="submitForm">
        <div class="row g-4">
          <!-- Left Column: Controls & Inputs -->
          <div class="col-lg-7">
            <!-- 1. Master Switch Card -->
            <div
              class="card border-0 shadow-sm mb-4 transition-all"
              :style="{
                background: form.maintenance_mode
                  ? 'linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(30, 41, 59, 1) 100%)'
                  : 'var(--vz-card-bg, #1e293b)',
                border: form.maintenance_mode ? '1.5px solid rgba(239, 68, 68, 0.5) !important' : '1px solid rgba(255,255,255,0.08) !important',
                borderRadius: '16px'
              }"
            >
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-3">
                  <div class="d-flex align-items-center gap-3">
                    <div
                      class="avatar-md rounded-circle d-flex align-items-center justify-content-center shadow"
                      :class="form.maintenance_mode ? 'bg-danger text-white' : 'bg-secondary/30 text-muted'"
                    >
                      <i class="ri-alert-fill fs-24" :class="form.maintenance_mode ? 'text-white' : 'text-muted'"></i>
                    </div>
                    <div>
                      <h5 class="fw-bold mb-1 text-dark-emphasis">Saklar Utama Mode Perawatan</h5>
                      <p class="text-muted fs-12 mb-0">
                        {{ form.maintenance_mode
                          ? 'Aktif: Pengunjung publik akan melihat tampilan 503 Maintenance Page.'
                          : 'Nonaktif: Seluruh fitur web publik, formulir pendaftaran, dan informasi dapat diakses normal.'
                        }}
                      </p>
                    </div>
                  </div>

                  <div class="form-check form-switch form-switch-lg mb-0">
                    <input
                      v-model="form.maintenance_mode"
                      class="form-check-input cursor-pointer"
                      type="checkbox"
                      id="maintenanceSwitch"
                      style="width: 3.4em; height: 1.8em;"
                    />
                  </div>
                </div>

                <!-- Info Alert When Maintenance Active -->
                <div v-if="form.maintenance_mode" class="alert alert-danger border-0 mt-4 mb-0 d-flex align-items-start gap-2 fs-12 rounded-3">
                  <i class="ri-error-warning-fill fs-18 flex-shrink-0"></i>
                  <div>
                    <strong>Perhatian:</strong> Halaman publik saat ini ditutup untuk pengunjung umum.
                    Sebagai Panitia / Administrator yang telah login, Anda <strong>tetap memiliki akses penuh</strong> ke seluruh panel ini dan halaman operasional tanpa kendala.
                  </div>
                </div>
              </div>
            </div>

            <!-- 2. Parameter Tampilan Pemeliharaan -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                  <i class="ri-pencil-ruler-2-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Pesan & Konten Halaman Pemeliharaan</h6>
                  <small class="text-muted fs-11">Sesuaikan informasi yang dibaca oleh pengunjung saat membuka web</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Judul Tampilan Pemeliharaan <span class="text-danger">*</span></label>
                    <input
                      v-model="form.maintenance_title"
                      type="text"
                      class="form-control"
                      placeholder="Contoh: Situs Sedang Dalam Pemeliharaan Berkala"
                      required
                    />
                    <div v-if="form.errors.maintenance_title" class="text-danger fs-11 mt-1">{{ form.errors.maintenance_title }}</div>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Pesan Penjelasan Detail <span class="text-danger">*</span></label>
                    <textarea
                      v-model="form.maintenance_message"
                      rows="4"
                      class="form-control"
                      placeholder="Jelaskan alasan pemeliharaan, misalnya peningkatan server atau sinkronisasi data teknis lomba..."
                      required
                    ></textarea>
                    <div v-if="form.errors.maintenance_message" class="text-danger fs-11 mt-1">{{ form.errors.maintenance_message }}</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Estimasi Selesai / Jadwal Online Kembali</label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-time-line"></i></span>
                      <input
                        v-model="form.maintenance_estimated_end"
                        type="text"
                        class="form-control"
                        placeholder="Contoh: 19 Oktober 2026, 12:00 WIB"
                      />
                    </div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Nomor WhatsApp Darurat / Hotline Panitia</label>
                    <div class="input-group">
                      <span class="input-group-text bg-success-subtle text-success border-0"><i class="ri-whatsapp-line"></i></span>
                      <input
                        v-model="form.maintenance_emergency_wa"
                        type="text"
                        class="form-control"
                        placeholder="0838 5007 0767"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 3. Kunci Rahasia Bypass URL -->
            <div class="card border-0 shadow-sm mb-4 rounded-4">
              <div class="card-header border-bottom bg-transparent py-3 d-flex align-items-center gap-2">
                <div class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                  <i class="ri-key-2-line fs-16"></i>
                </div>
                <div>
                  <h6 class="card-title mb-0 fs-14 fw-bold text-dark-emphasis">Kunci Rahasia Akses Penguji (Secret Bypass Key)</h6>
                  <small class="text-muted fs-11">Buka website publik bagi tester/pimpinan tanpa mematikan mode perawatan</small>
                </div>
              </div>
              <div class="card-body p-4">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis">Kata Kunci Rahasia (Bypass Key)</label>
                    <div class="input-group">
                      <span class="input-group-text bg-dark-subtle border-secondary/20 text-muted"><i class="ri-lock-password-line"></i></span>
                      <input
                        v-model="form.maintenance_secret_key"
                        type="text"
                        class="form-control"
                        placeholder="semarak2026bypass"
                      />
                    </div>
                  </div>

                  <div class="col-md-6 d-flex align-items-end">
                    <button
                      type="button"
                      class="btn btn-outline-info w-100 d-flex align-items-center justify-content-center gap-2"
                      @click="copyBypassUrl"
                    >
                      <i :class="isCopied ? 'ri-checkbox-circle-fill text-success' : 'ri-file-copy-line'"></i>
                      <span>{{ isCopied ? 'Tersalin ke Clipboard!' : 'Salin Tautan Bypass URL' }}</span>
                    </button>
                  </div>

                  <div class="col-12">
                    <div class="p-2 rounded bg-black/40 border border-secondary/20 text-muted fs-11 font-mono text-truncate">
                      <span class="text-info fw-bold">URL Bypass:</span> {{ bypassUrl }}
                    </div>
                    <small class="text-muted fs-11 mt-1 d-block">
                      Siapapun yang membuka link tersebut akan tersimpan sesi bypass di perambannya dan dapat melihat website publik secara normal.
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button Bar -->
            <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-dark-subtle border border-secondary/20 mb-5">
              <span class="text-muted fs-12">
                <i class="ri-shield-check-line me-1 text-warning"></i>
                Status baru berlaku seketika setelah disimpan.
              </span>
              <button
                type="submit"
                class="btn btn-warning text-dark px-4 py-2 rounded-pill fw-bold d-flex align-items-center gap-2 shadow-sm"
                :disabled="form.processing"
              >
                <i v-if="form.processing" class="ri-loader-4-line ri-spin"></i>
                <i v-else class="ri-save-3-line"></i>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan Mode Perawatan' }}</span>
              </button>
            </div>
          </div>

          <!-- Right Column: Interactive Live Preview of 503 Screen -->
          <div class="col-lg-5">
            <div class="sticky-top" style="top: 90px; z-index: 10;">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fs-12 fw-bold text-muted text-uppercase tracking-wider">
                  <i class="ri-tv-line me-1 text-primary"></i> Pratinjau Tampilan Pengunjung (Live)
                </span>
                <span class="badge bg-danger-subtle text-danger fs-11">HTTP 503 Preview</span>
              </div>

              <!-- Mock Screen Browser Frame -->
              <div class="rounded-4 overflow-hidden border border-secondary/30 shadow-2xl bg-[#090e1c] text-slate-100 p-4" style="background: #090e1c; min-height: 480px; position: relative;">
                <!-- Ambient glow simulation -->
                <div style="position: absolute; top: -20px; left: -20px; width: 140px; height: 140px; border-radius: 50%; background: #8b5cf6; filter: blur(60px); opacity: 0.3; pointer-events: none;"></div>
                <div style="position: absolute; bottom: -20px; right: -20px; width: 140px; height: 140px; border-radius: 50%; background: #f97316; filter: blur(60px); opacity: 0.25; pointer-events: none;"></div>

                <div class="text-center position-relative" style="z-index: 2;">
                  <!-- Logo Badge -->
                  <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white/5 border border-white/10 mb-3">
                    <img src="/assets/img/logo.png" alt="Logo" style="height: 20px; width: auto;" />
                    <span class="fs-11 fw-bold text-white">SEMARAK ALIYAH <span class="badge bg-warning text-dark fs-9">XIV</span></span>
                  </div>

                  <!-- Rotating Gear Illustration -->
                  <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle border border-warning/40 shadow-lg" style="width: 64px; height: 64px; background: rgba(249, 115, 22, 0.15);">
                    <i class="ri-settings-5-fill text-warning fs-32" style="animation: spin 10s linear infinite;"></i>
                  </div>

                  <!-- Status Pill -->
                  <div class="d-inline-flex align-items-center gap-2 px-2 py-0.5 rounded-pill bg-warning/20 border border-warning/40 text-warning fs-10 fw-bold mb-2">
                    <span class="rounded-circle bg-warning" style="width: 5px; height: 5px;"></span>
                    <span>MODE PEMELIHARAAN AKTIF</span>
                  </div>

                  <!-- Dynamic Title -->
                  <h6 class="fw-bold text-white fs-15 mb-2 lh-sm">{{ form.maintenance_title || 'Situs Sedang Dalam Pemeliharaan Berkala' }}</h6>

                  <!-- Dynamic Message -->
                  <p class="text-slate-300 fs-11 lh-base px-2 mb-3">
                    {{ form.maintenance_message || 'Mohon maaf atas ketidaknyamanan ini. Sistem sedang disinkronkan.' }}
                  </p>

                  <!-- Dynamic Estimated End -->
                  <div v-if="form.maintenance_estimated_end" class="p-2 rounded-3 bg-white/5 border border-white/10 d-inline-flex align-items-center gap-2 text-slate-200 fs-11 mb-3">
                    <i class="ri-time-line text-info"></i>
                    <span>Estimasi: <strong>{{ form.maintenance_estimated_end }}</strong></span>
                  </div>

                  <!-- Action Buttons -->
                  <div class="d-flex flex-column gap-2 mt-2">
                    <button type="button" class="btn btn-success btn-sm rounded-pill fw-bold fs-11 py-2 d-flex align-items-center justify-content-center gap-2 shadow">
                      <i class="ri-whatsapp-line fs-14"></i>
                      <span>Hubungi Hotline Panitia ({{ form.maintenance_emergency_wa || '-' }})</span>
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm rounded-pill fs-11 py-1.5 opacity-70">
                      <i class="ri-lock-2-line me-1"></i> Masuk Panel Panitia
                    </button>
                  </div>

                  <div class="text-slate-500 fs-10 mt-4">
                    MA Unggulan Wahid Hasyim Balung • Semarak Aliyah XIV 2026
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

<style scoped>
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(0.9); }
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
