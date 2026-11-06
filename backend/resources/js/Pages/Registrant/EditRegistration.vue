<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
  user: { type: Object, required: true },
  registration: { type: Object, required: true },
  competition: { type: Object, required: true },
  paymentSettings: { type: Object, default: () => ({}) },
  isOwner: { type: Boolean, default: true },
  isPic: { type: Boolean, default: false },
  isClosed: { type: Boolean, default: false },
});

const toast = useToast();
const { isDark, toggleTheme, initTheme } = useTheme();

onMounted(() => {
  initTheme();
});

// Dropdown options (Rule 1: CustomSelect)
const gradeOptions = [
  { value: '7', label: 'Kelas 7 (VII)' },
  { value: '8', label: 'Kelas 8 (VIII)' },
  { value: '9', label: 'Kelas 9 (IX)' },
  { value: '7A', label: 'Kelas 7A' },
  { value: '7B', label: 'Kelas 7B' },
  { value: '7C', label: 'Kelas 7C' },
  { value: '8A', label: 'Kelas 8A' },
  { value: '8B', label: 'Kelas 8B' },
  { value: '8C', label: 'Kelas 8C' },
  { value: '9A', label: 'Kelas 9A' },
  { value: '9B', label: 'Kelas 9B' },
  { value: '9C', label: 'Kelas 9C' },
];

const genderOptions = [
  { value: 'Laki-laki', label: 'Laki-laki (Ikhwan)' },
  { value: 'Perempuan', label: 'Perempuan (Akhwat)' },
];

// Inisialisasi Peserta Terstruktur
const participants = ref(
  Array.isArray(props.registration.structured_participants) && props.registration.structured_participants.length > 0
    ? JSON.parse(JSON.stringify(props.registration.structured_participants))
    : [{ name: '', grade: '9', nisn: '', gender: 'Laki-laki', phone: '' }]
);

// Quota Rules
const minQuota = computed(() => Math.max(1, props.competition.min_quota || 1));
const maxQuota = computed(() => Math.max(minQuota.value, props.competition.max_quota || 1));
const isTeam = computed(() => maxQuota.value > 1 || props.competition.participant_type === 'beregu');

function addParticipant() {
  if (participants.value.length >= maxQuota.value) {
    toast.error(`Maksimal ${maxQuota.value} peserta untuk cabang lomba ini.`);
    return;
  }
  participants.value.push({
    name: '',
    grade: '9',
    nisn: '',
    gender: 'Laki-laki',
    phone: '',
  });
}

function removeParticipant(index) {
  if (participants.value.length <= minQuota.value) {
    toast.error(`Minimal harus ada ${minQuota.value} peserta.`);
    return;
  }
  participants.value.splice(index, 1);
}

// Payment Proof State
const paymentProofFile = ref(null);
const paymentProofPreview = ref(null);

function handlePaymentProofChange(e) {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    toast.error('Ukuran berkas bukti transfer maksimal 5 MB.');
    e.target.value = '';
    return;
  }
  paymentProofFile.value = file;
  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = (ev) => {
      paymentProofPreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    paymentProofPreview.value = null;
  }
}

// Custom Requirement Files State
const requirementFiles = ref({});
const requirementFileNames = ref({});

function handleRequirementFileChange(fieldKey, e) {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) {
    toast.error('Ukuran berkas maksimal 5 MB.');
    e.target.value = '';
    return;
  }
  requirementFiles.value[fieldKey] = file;
  requirementFileNames.value[fieldKey] = file.name;
}

// Form State
const form = useForm({
  mentor_name: props.registration.mentor_name || '',
  mentor_wa: props.registration.mentor_wa || '',
  notes: props.registration.notes || '',
  payment_proof: null,
  custom_files: {},
  participants: [],
});

function submitForm() {
  if (props.isClosed) {
    toast.error('Pendaftaran cabang lomba ini telah ditutup.');
    return;
  }

  // Validasi nama peserta minimal
  const validParticipants = participants.value.filter(p => p.name && p.name.trim() !== '');
  if (validParticipants.length < minQuota.value) {
    toast.error(`Mohon isi minimal ${minQuota.value} nama peserta.`);
    return;
  }

  form.participants = participants.value;
  form.payment_proof = paymentProofFile.value;
  form.custom_files = requirementFiles.value;

  form.post(`/pendaftar/registrations/${props.registration.id}/update`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Pembaruan data dan berkas pendaftaran berhasil disimpan!');
    },
    onError: (errors) => {
      console.error(errors);
      toast.error('Gagal memperbarui pendaftaran. Periksa kembali form isian.');
    },
  });
}
</script>

<template>
  <Head :title="`Edit Data Pendaftaran - ${registration.reg_code}`" />

  <div class="edit-page-container min-vh-100 d-flex flex-column" :class="{ 'dark-theme': isDark }">
    <!-- TOP NAVBAR -->
    <header class="edit-navbar px-3 px-md-4 py-2.5 d-flex align-items-center justify-content-between border-bottom">
      <div class="d-flex align-items-center gap-2.5">
        <Link href="/pendaftar/dashboard" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1.5 d-flex align-items-center gap-1.5 fs-12 fw-semibold back-dashboard-btn text-decoration-none">
          <i class="ri-arrow-left-line"></i>
          <span>Kembali ke Dashboard</span>
        </Link>
        <span class="text-muted d-none d-sm-inline">|</span>
        <div class="d-none d-sm-flex align-items-center gap-2">
          <img src="/assets/img/logo.png" alt="Logo" style="height: 24px; width: auto;" />
          <span class="fs-12 fw-bold text-dark-emphasis">SEMARAK ALIYAH XIV</span>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <!-- Theme Toggle -->
        <button 
          type="button" 
          @click="toggleTheme" 
          class="btn btn-sm rounded-circle p-2 theme-toggle-btn d-flex align-items-center justify-content-center"
          :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
          style="width: 34px; height: 34px;"
        >
          <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line text-dark'"></i>
        </button>

        <!-- User Chip -->
        <div class="d-flex align-items-center gap-2 px-2.5 py-1 rounded-pill user-chip border">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-11" style="width: 24px; height: 24px;">
            {{ user.name ? user.name.charAt(0).toUpperCase() : 'P' }}
          </div>
          <span class="fs-12 fw-semibold text-truncate d-none d-md-inline" style="max-width: 140px;">
            {{ user.name }}
          </span>
        </div>
      </div>
    </header>

    <!-- MAIN EDIT CONTAINER -->
    <main class="container-xl px-3 px-md-4 py-4 flex-grow-1">
      
      <!-- HERO HEADER CARD -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 edit-hero-card overflow-hidden">
        <div class="card-body p-4">
          <div class="row g-3 align-items-center">
            <div class="col-lg-8">
              <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb fs-11 mb-0">
                  <li class="breadcrumb-item"><Link href="/pendaftar/dashboard" class="text-decoration-none">Dashboard</Link></li>
                  <li class="breadcrumb-item">Kelola Kontingen</li>
                  <li class="breadcrumb-item active fw-semibold text-primary font-monospace">{{ registration.reg_code }}</li>
                </ol>
              </nav>

              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge font-monospace fs-11 px-2.5 py-1 rounded-pill bg-primary-subtle text-primary border border-primary-subtle">
                  {{ registration.reg_code }}
                </span>
                <span class="badge fs-11 px-2.5 py-1 rounded-pill bg-info-subtle text-info border border-info-subtle">
                  {{ registration.category_label }}
                </span>
                <span class="badge fs-11 px-2.5 py-1 rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                  {{ registration.package_type }}
                </span>
                <span 
                  v-if="registration.payment_status === 'lunas' || registration.payment_status === 'verified'" 
                  class="badge fs-11 px-2.5 py-1 rounded-pill bg-success-subtle text-success border border-success-subtle fw-bold"
                >
                  <i class="ri-checkbox-circle-fill me-0.5"></i> Pembayaran Lunas
                </span>
                <span v-else class="badge fs-11 px-2.5 py-1 rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-bold">
                  <i class="ri-time-line me-0.5"></i> Menunggu Verifikasi
                </span>
              </div>

              <h2 class="fs-20 fs-md-24 fw-black text-dark-emphasis mb-1">
                Perbarui Data &amp; Berkas Pendaftaran
              </h2>
              <p class="fs-13 text-muted mb-0">
                Cabang Lomba: <strong class="text-primary">{{ registration.competition_title }}</strong> &bull; Asal Sekolah: <strong>{{ registration.school_name }}</strong>
              </p>
            </div>

            <div class="col-lg-4 text-lg-end">
              <div class="p-3 rounded-3 border d-inline-flex flex-column gap-1 text-start hero-summary-box">
                <span class="fs-11 text-muted fw-semibold text-uppercase">Terdaftar Pada:</span>
                <span class="fs-13 fw-bold text-dark-emphasis">{{ registration.created_at }}</span>
                <span class="fs-11 text-muted">
                  Tipe: <strong>{{ isTeam ? `Beregu (${minQuota}-${maxQuota} Orang)` : 'Individu (1 Orang)' }}</strong>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ALERT JIKA PENDAFTARAN DITUTUP -->
      <div v-if="isClosed" class="alert alert-danger d-flex align-items-center gap-2.5 rounded-3 mb-4 shadow-sm">
        <i class="ri-error-warning-fill fs-20 flex-shrink-0"></i>
        <div>
          <div class="fw-bold fs-13">Pendaftaran Cabang Lomba Ini Telah Ditutup</div>
          <div class="fs-12">Masa pendaftaran atau kuota cabang lomba telah berakhir. Perubahan data dan berkas sudah dikunci oleh panitia.</div>
        </div>
      </div>

      <!-- MAIN FORM -->
      <form @submit.prevent="submitForm">
        <div class="row g-4">
          
          <!-- SISI KIRI: DATA PESERTA / DELEGASI -->
          <div class="col-lg-7">
            
            <!-- CARD 1: DATA SISWA / DELEGASI -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 section-card">
              <div class="card-header bg-transparent p-3 px-md-4 border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i :class="isTeam ? 'ri-team-line' : 'ri-user-star-line'"></i>
                  </div>
                  <div>
                    <h5 class="fs-15 fw-bold text-dark-emphasis mb-0">
                      {{ isTeam ? 'Data Anggota Delegasi Tim / Regu' : 'Data Siswa Peserta Lomba' }}
                    </h5>
                    <div class="fs-11 text-muted">
                      {{ isTeam ? `Masukkan data ${minQuota === maxQuota ? minQuota : minQuota + '-' + maxQuota} anggota siswa perwakilan.` : 'Masukkan data identitas siswa perwakilan resmi.' }}
                    </div>
                  </div>
                </div>

                <button 
                  v-if="isTeam && !isClosed && participants.length < maxQuota" 
                  type="button" 
                  @click="addParticipant" 
                  class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 fs-12 fw-bold d-flex align-items-center gap-1 shadow-sm"
                >
                  <i class="ri-user-add-line"></i>
                  <span>+ Tambah Anggota</span>
                </button>
              </div>

              <div class="card-body p-3 px-md-4">
                <div class="d-flex flex-column gap-3">
                  <div 
                    v-for="(p, pIdx) in participants" 
                    :key="pIdx"
                    class="p-3 rounded-3 border participant-item-box transition-all"
                  >
                    <!-- Header Kartu Peserta -->
                    <div class="d-flex align-items-center justify-content-between mb-2.5 pb-2 border-bottom">
                      <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white rounded-pill px-2 py-0.5 fs-11 fw-bold">
                          #{{ pIdx + 1 }}
                        </span>
                        <span class="fs-13 fw-bold text-dark-emphasis">
                          {{ isTeam ? (pIdx === 0 ? 'Ketua Regu / Delegasi 1' : `Anggota Delegasi #${pIdx + 1}`) : 'Identitas Siswa' }}
                        </span>
                      </div>

                      <button 
                        v-if="isTeam && !isClosed && participants.length > minQuota"
                        type="button"
                        @click="removeParticipant(pIdx)"
                        class="btn btn-sm btn-outline-danger border-0 p-1 px-2 fs-11 d-flex align-items-center gap-1 rounded-2"
                        title="Hapus Peserta Ini"
                      >
                        <i class="ri-delete-bin-line"></i>
                        <span>Hapus</span>
                      </button>
                    </div>

                    <!-- Input Grid -->
                    <div class="row g-2.5">
                      <!-- Nama Lengkap -->
                      <div class="col-md-7">
                        <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                          Nama Lengkap Siswa <span class="text-danger">*</span>
                        </label>
                        <input 
                          type="text" 
                          v-model="p.name" 
                          class="form-control form-control-sm fs-12 py-1.5" 
                          placeholder="Nama lengkap peserta..." 
                          :disabled="isClosed"
                          required 
                        />
                      </div>

                      <!-- Kelas / Tingkat -->
                      <div class="col-md-5">
                        <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                          Kelas / Tingkat <span class="text-danger">*</span>
                        </label>
                        <!-- Rule 1: CustomSelect Dropdown -->
                        <CustomSelect 
                          v-model="p.grade" 
                          :options="gradeOptions" 
                          size="sm"
                          placeholder="Pilih kelas..."
                          :disabled="isClosed"
                        />
                      </div>

                      <!-- NISN -->
                      <div class="col-md-4">
                        <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                          NISN <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <input 
                          type="text" 
                          v-model="p.nisn" 
                          class="form-control form-control-sm fs-12 py-1.5 font-monospace" 
                          placeholder="10 digit NISN..." 
                          maxlength="15"
                          :disabled="isClosed"
                        />
                      </div>

                      <!-- Jenis Kelamin -->
                      <div class="col-md-4">
                        <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                          Jenis Kelamin
                        </label>
                        <!-- Rule 1: CustomSelect Dropdown -->
                        <CustomSelect 
                          v-model="p.gender" 
                          :options="genderOptions" 
                          size="sm"
                          placeholder="Pilih gender..."
                          :disabled="isClosed"
                        />
                      </div>

                      <!-- No HP / WhatsApp Siswa -->
                      <div class="col-md-4">
                        <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                          No. WA Siswa <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <input 
                          type="tel" 
                          v-model="p.phone" 
                          class="form-control form-control-sm fs-12 py-1.5 font-monospace" 
                          placeholder="08xxxxxxxxxx" 
                          :disabled="isClosed"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- CARD 2: KONTAK GURU PENDAMPING & CATATAN -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 section-card">
              <div class="card-header bg-transparent p-3 px-md-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="ri-user-heart-line"></i>
                  </div>
                  <div>
                    <h5 class="fs-15 fw-bold text-dark-emphasis mb-0">Guru Pendamping &amp; Catatan Khusus</h5>
                    <div class="fs-11 text-muted">Informasi narahubung resmi delegasi sekolah ke panitia lomba.</div>
                  </div>
                </div>
              </div>

              <div class="card-body p-3 px-md-4">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                      Nama Guru Pendamping / Official
                    </label>
                    <input 
                      type="text" 
                      v-model="form.mentor_name" 
                      class="form-control form-control-sm fs-12 py-2" 
                      placeholder="Nama lengkap pendamping..." 
                      :disabled="isClosed || !isOwner"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                      Nomor WhatsApp Guru Pendamping
                    </label>
                    <input 
                      type="tel" 
                      v-model="form.mentor_wa" 
                      class="form-control form-control-sm fs-12 py-2 font-monospace" 
                      placeholder="Contoh: 081277778888" 
                      :disabled="isClosed || !isOwner"
                    />
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                      Catatan Tambahan untuk Panitia (Opsional)
                    </label>
                    <textarea 
                      v-model="form.notes" 
                      rows="2" 
                      class="form-control form-control-sm fs-12" 
                      placeholder="Tuliskan catatan khusus terkait delegasi atau lomba jika ada..."
                      :disabled="isClosed"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- SISI KANAN: DOKUMEN & BERKAS PERSYARATAN -->
          <div class="col-lg-5">
            
            <!-- CARD 3: BERKAS & DOKUMEN PERSYARATAN LOMBA -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 section-card">
              <div class="card-header bg-transparent p-3 px-md-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="ri-folder-upload-line"></i>
                  </div>
                  <div>
                    <h5 class="fs-15 fw-bold text-dark-emphasis mb-0">Berkas Persyaratan Lomba</h5>
                    <div class="fs-11 text-muted">Dokumen resmi kelengkapan administrasi lomba.</div>
                  </div>
                </div>
              </div>

              <div class="card-body p-3 px-md-4">
                
                <!-- Berkas yang Sudah Diunggah Sebelumnya -->
                <div v-if="registration.file_documents && registration.file_documents.length > 0" class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1.5 d-block">
                    Berkas Tersimpan Saat Ini:
                  </label>
                  <div class="d-flex flex-column gap-2">
                    <div 
                      v-for="(doc, dIdx) in registration.file_documents" 
                      :key="dIdx"
                      class="p-2.5 rounded-3 border saved-doc-item d-flex align-items-center justify-content-between"
                    >
                      <div class="d-flex align-items-center gap-2 text-truncate pe-2">
                        <i class="ri-file-text-line fs-18 text-primary flex-shrink-0"></i>
                        <div class="text-truncate">
                          <div class="fs-12 fw-bold text-dark-emphasis text-truncate">{{ doc.title || doc.filename }}</div>
                          <div class="fs-11 text-muted font-monospace">{{ doc.filename || 'Dokumen' }}</div>
                        </div>
                      </div>
                      <a :href="doc.url" target="_blank" class="btn btn-sm btn-outline-primary px-2.5 py-1 fs-11 fw-semibold flex-shrink-0">
                        <i class="ri-external-link-line me-0.5"></i> Lihat
                      </a>
                    </div>
                  </div>
                </div>

                <!-- Dropzone Unggah Berkas Persyaratan Pengganti / Tambahan -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1.5 d-block">
                    Unggah / Ganti Berkas Persyaratan:
                  </label>
                  
                  <div class="p-3 rounded-3 border bg-body-tertiary d-flex flex-column gap-2.5">
                    <!-- Dropzone Pas Foto / Kartu Pelajar / Surat Rekomendasi -->
                    <div>
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fs-12 fw-semibold text-dark-emphasis">
                          <i class="ri-upload-cloud-line text-primary me-1"></i> Surat Rekomendasi / Kartu Pelajar / Dokumen Lomba
                        </span>
                        <span v-if="requirementFileNames['dokumen_lomba']" class="badge bg-success-subtle text-success fs-11">
                          Dipilih
                        </span>
                      </div>
                      <input 
                        type="file" 
                        class="form-control form-control-sm fs-12 mb-1" 
                        accept="image/*,application/pdf"
                        @change="handleRequirementFileChange('dokumen_lomba', $event)"
                        :disabled="isClosed"
                      />
                      <small class="fs-11 text-muted d-block">Format: JPG, PNG, PDF. Maksimal 5 MB.</small>
                      <div v-if="requirementFileNames['dokumen_lomba']" class="fs-11 text-success fw-semibold mt-1">
                        <i class="ri-check-line"></i> {{ requirementFileNames['dokumen_lomba'] }}
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- CARD 4: BUKTI PEMBAYARAN BANK (JIKA BUKAN ALL-IN / JIKA ADA PERUBAHAN) -->
            <div v-if="isOwner && registration.package_type !== 'Paket All-In'" class="card border-0 shadow-sm rounded-4 mb-4 section-card">
              <div class="card-header bg-transparent p-3 px-md-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="ri-receipt-line"></i>
                  </div>
                  <div>
                    <h5 class="fs-15 fw-bold text-dark-emphasis mb-0">Bukti Transfer Pembayaran Bank</h5>
                    <div class="fs-11 text-muted">Unggah bukti transfer baru jika terdapat perbaikan bukti pembayaran.</div>
                  </div>
                </div>
              </div>

              <div class="card-body p-3 px-md-4">
                <!-- Bukti Lama yang Ada -->
                <div v-if="registration.payment_proof_url" class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">Bukti Transfer Sebelumnya:</label>
                  <div class="p-2 border rounded-3 text-center bg-body-tertiary">
                    <img 
                      :src="registration.payment_proof_url" 
                      alt="Bukti Transfer Sebelumnya" 
                      class="rounded shadow-sm"
                      style="max-height: 140px; max-width: 100%; object-fit: contain;" 
                    />
                  </div>
                </div>

                <!-- Unggah Baru -->
                <div class="mb-2">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Pilih Berkas Bukti Transfer Baru (Opsional):
                  </label>
                  <input 
                    type="file" 
                    class="form-control form-control-sm fs-12 mb-1" 
                    accept="image/*,application/pdf"
                    @change="handlePaymentProofChange"
                    :disabled="isClosed"
                  />
                  <small class="fs-11 text-muted d-block">Format: JPG, PNG, PDF. Maksimal 5 MB.</small>
                </div>

                <!-- Preview Bukti Baru -->
                <div v-if="paymentProofPreview" class="mt-2.5 p-2 border rounded-3 bg-success-subtle text-center">
                  <div class="fs-11 fw-bold text-success mb-1">Pratinjau Bukti Baru yang Dipilih:</div>
                  <img :src="paymentProofPreview" alt="Pratinjau Baru" style="max-height: 140px; max-width: 100%; object-fit: contain;" class="rounded shadow-sm" />
                </div>
              </div>
            </div>

            <!-- STICKY ACTION CARD -->
            <div class="card border-0 shadow-sm rounded-4 p-3 section-card action-sticky-box">
              <div class="d-flex flex-column gap-2">
                <button 
                  type="submit" 
                  class="btn btn-success btn-lg w-100 py-2.5 fs-13 fw-bold shadow d-flex align-items-center justify-content-center gap-2"
                  :disabled="form.processing || isClosed"
                >
                  <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-save-fill fs-16"></i>
                  <span>{{ form.processing ? 'Menyimpan Perubahan...' : 'Simpan Pembaruan Data & Berkas' }}</span>
                </button>

                <Link 
                  href="/pendaftar/dashboard" 
                  class="btn btn-outline-secondary w-100 py-2 fs-12 fw-semibold text-center text-decoration-none"
                >
                  Batal &amp; Kembali ke Dashboard
                </Link>
              </div>
            </div>

          </div>

        </div>
      </form>

    </main>
  </div>
</template>

<style scoped>
.edit-page-container {
  background-color: #f8fafc;
  color: #0f172a;
}
.dark-theme.edit-page-container {
  background-color: #0b1120;
  color: #f8fafc;
}

/* Navbar */
.edit-navbar {
  background-color: #ffffff;
  border-color: #e2e8f0 !important;
}
.dark-theme .edit-navbar {
  background-color: #111827;
  border-color: #1f2937 !important;
}

/* Cards */
.section-card,
.edit-hero-card {
  background-color: #ffffff;
  border-color: #e2e8f0 !important;
}
.dark-theme .section-card,
.dark-theme .edit-hero-card {
  background-color: #111827;
  border-color: #1f2937 !important;
}

/* Participant Item */
.participant-item-box {
  background-color: #ffffff;
  border-color: #e2e8f0 !important;
}
.dark-theme .participant-item-box {
  background-color: #1e293b;
  border-color: #334155 !important;
}

/* Saved Doc Item */
.saved-doc-item {
  background-color: #f8fafc;
  border-color: #e2e8f0 !important;
}
.dark-theme .saved-doc-item {
  background-color: #1e293b;
  border-color: #334155 !important;
}

/* Hero Summary Box */
.hero-summary-box {
  background-color: #f8fafc;
  border-color: #e2e8f0 !important;
}
.dark-theme .hero-summary-box {
  background-color: #1e293b;
  border-color: #334155 !important;
}

/* Sticky Action */
.action-sticky-box {
  position: sticky;
  top: 20px;
  z-index: 10;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}
</style>

