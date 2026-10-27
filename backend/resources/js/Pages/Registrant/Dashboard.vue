<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useTheme } from '@/Composables/useTheme';
import AvatarCropperModal from '@/Components/AvatarCropperModal.vue';

const props = defineProps({
  user: { type: Object, required: true },
  registrations: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  competitions: { type: Array, default: () => [] },
  paymentInfo: { type: Object, default: () => ({}) },
  allInPricing: { type: Object, default: () => ({ price: 200000, activeCompetitionsCount: 0, totalIndividualPrice: 0, savings: 0 }) },
  package_payment: { type: Object, default: () => null },
  isAllInActive: { type: Boolean, default: false },
  isCurrentUserPicOnly: { type: Boolean, default: false },
});

const toast = useToast();
const { isDark, toggleTheme, initTheme } = useTheme();

onMounted(() => {
  initTheme();
});

const packageDetails = computed(() => {
  const p = props.allInPricing || {};
  const activeCount = p.active_competitions_count || p.activeCompetitionsCount || (props.competitions ? props.competitions.length : 0) || 0;
  const originalPrice = p.original_price || p.totalIndividualPrice || 450000;
  const packagePrice = p.price || 200000;
  const savings = p.savings !== undefined ? p.savings : Math.max(0, originalPrice - packagePrice);

  return {
    price: packagePrice,
    activeCount,
    originalPrice,
    savings,
  };
});

const packagePayment = computed(() => {
  return props.package_payment || {
    is_active: props.isAllInActive,
    price: props.allInPricing?.price || 200000,
    payment_status: 'Menunggu Verifikasi',
    is_paid: false,
    master_reg_id: null,
    master_comp_title: null,
    payment_proof_url: null,
    bank_name: props.paymentInfo?.bank_name || 'Bank Syariah Indonesia (BSI)',
    account_number: props.paymentInfo?.account_number || '7123456789',
    account_holder: props.paymentInfo?.account_holder || 'Semarak Aliyah',
  };
});

// Active tab filter: 'all' | 'pending' | 'verified' | 'rejected'
const activeFilter = ref('all');
const searchQuery = ref('');

// Filtered Registrations
const filteredRegistrations = computed(() => {
  let list = props.registrations || [];

  if (activeFilter.value === 'pending') {
    list = list.filter(r => (r.registration_status || '').toLowerCase().includes('menunggu'));
  } else if (activeFilter.value === 'verified') {
    list = list.filter(r => (r.registration_status || '').toLowerCase().includes('terverifikasi'));
  } else if (activeFilter.value === 'rejected') {
    list = list.filter(r => (r.registration_status || '').toLowerCase().includes('tolak'));
  }

  if (searchQuery.value && searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(r =>
      (r.reg_code && r.reg_code.toLowerCase().includes(q)) ||
      (r.competition_title && r.competition_title.toLowerCase().includes(q)) ||
      (r.participants_names && r.participants_names.toLowerCase().includes(q))
    );
  }

  return list;
});

// Helper format tanggal & jam sesuai Rule 4 (dd/mm/yyyy & 24 jam)
function formatIndoDate(dateStr) {
  if (!dateStr) return '-';
  if (/^\d{2}\/\d{2}\/\d{4}/.test(dateStr)) return dateStr;
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  } catch (e) {
    return dateStr;
  }
}

function formatIndoDateOnly(dateStr) {
  if (!dateStr) return '-';
  if (/^\d{2}\/\d{2}\/\d{4}/.test(dateStr)) return dateStr;
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  } catch (e) {
    return dateStr;
  }
}

function formatRupiah(num) {
  if (!num && num !== 0) return 'Rp 0';
  return 'Rp ' + Number(num).toLocaleString('id-ID');
}

// ========================================================
// Modal 1: Perbarui Data & Berkas Pendaftaran
// ========================================================
const isEditModalOpen = ref(false);
const activeReg = ref(null);

const editForm = useForm({
  participants_names: '',
  mentor_name: '',
  mentor_wa: '',
  notes: '',
  payment_method: '',
  payment_proof: null,
  custom_files: {},
});

const paymentProofPreview = ref(null);

function openEditModal(reg) {
  activeReg.value = reg;
  editForm.participants_names = reg.participants_names || '';
  editForm.mentor_name = reg.mentor_name || '';
  editForm.mentor_wa = reg.mentor_wa || '';
  editForm.notes = reg.notes || '';
  editForm.payment_method = reg.payment_method || 'Transfer Bank BSI';
  editForm.payment_proof = null;
  editForm.custom_files = {};
  paymentProofPreview.value = null;

  isEditModalOpen.value = true;
}

function handlePaymentProofChange(e) {
  const file = e.target.files[0];
  if (file) {
    editForm.payment_proof = file;
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
}

function submitEditRegistration() {
  if (!activeReg.value) return;

  editForm.post(`/pendaftar/registrations/${activeReg.value.id}/update`, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      isEditModalOpen.value = false;
      toast.success('Data dan berkas pendaftaran berhasil diperbarui!');
    },
    onError: () => {
      toast.error('Gagal memperbarui berkas. Periksa kembali form isian.');
    }
  });
}

// ========================================================
// Modal 2: Detail Lengkap Pendaftaran
// ========================================================
const isDetailModalOpen = ref(false);
const detailReg = ref(null);

function openDetailModal(reg) {
  detailReg.value = reg;
  isDetailModalOpen.value = true;
}

// ========================================================
// Modal 4: Pratinjau & Cetak Bukti Resmi A4
// ========================================================
const isPrintModalOpen = ref(false);
const printReg = ref(null);
const printPreviewTheme = ref('paper'); // 'paper' | 'dark'

function openPrintModal(reg) {
  printReg.value = reg || detailReg.value;
  // Sesuaikan pratinjau dengan mode tema aktif saat ini
  printPreviewTheme.value = isDark.value ? 'dark' : 'paper';
  isPrintModalOpen.value = true;
}

function togglePrintPreviewTheme() {
  printPreviewTheme.value = printPreviewTheme.value === 'dark' ? 'paper' : 'dark';
}

function printOfficialDocument() {
  // Simpan preferensi tampilan pratinjau pengguna
  const prevTheme = printPreviewTheme.value;
  // Pastikan saat dicetak ke printer fisik selalu format kertas putih resmi
  printPreviewTheme.value = 'paper';
  setTimeout(() => {
    window.print();
    setTimeout(() => {
      printPreviewTheme.value = prevTheme;
    }, 500);
  }, 120);
}

function parseParticipants(namesStr) {
  if (!namesStr) return [];
  const lines = namesStr.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
  if (lines.length > 1) return lines;
  return namesStr.split(/[,;]/).map(s => s.trim()).filter(Boolean);
}

function getWaChatUrl(phone, reg) {
  if (!phone) return '#';
  let clean = phone.replace(/[^0-9]/g, '');
  if (clean.startsWith('0')) {
    clean = '62' + clean.slice(1);
  }
  const text = encodeURIComponent(`Halo Panitia Semarak Aliyah XIV, saya konfirmasi terkait pendaftaran ${reg?.competition_title || ''} (Kode: ${reg?.reg_code || ''}).`);
  return `https://wa.me/${clean}?text=${text}`;
}

// ========================================================
// Modal 3: Edit Profil Pendaftar
// ========================================================
const isProfileModalOpen = ref(false);

const profileForm = useForm({
  name: props.user?.name || '',
  registrant_role: props.user?.registrant_role || 'Pendamping',
  gender: props.user?.gender || 'Laki-laki',
  school_name: props.user?.school_name || '',
  school_district: props.user?.school_district || '',
});

const roleOptions = [
  { value: 'Pendamping', label: 'Guru Pendamping / Pembina Lomba' },
  { value: 'Koordinator', label: 'Koordinator Kontingen Sekolah' },
  { value: 'Siswa Peserta', label: 'Siswa / Siswi Peserta Mandiri' },
];

const genderOptions = [
  { value: 'Laki-laki', label: 'Laki-laki (Ikhwan)' },
  { value: 'Perempuan', label: 'Perempuan (Akhwat)' },
];

function openProfileModal() {
  profileForm.name = props.user?.name || '';
  profileForm.registrant_role = props.user?.registrant_role || 'Pendamping';
  profileForm.gender = props.user?.gender || 'Laki-laki';
  profileForm.school_name = props.user?.school_name || '';
  profileForm.school_district = props.user?.school_district || '';
  isProfileModalOpen.value = true;
}

function submitProfileUpdate() {
  profileForm.post('/pendaftar/profile/update', {
    preserveScroll: true,
    onSuccess: () => {
      isProfileModalOpen.value = false;
      toast.success('Profil pendaftar berhasil diperbarui!');
    },
    onError: () => {
      toast.error('Gagal memperbarui profil. Periksa data kembali.');
    }
  });
}

// Avatar Cropper Modal State
const isPendaftarAvatarModalOpen = ref(false);
const isSavingPendaftarAvatar = ref(false);

function openPendaftarAvatarCropper() {
  isPendaftarAvatarModalOpen.value = true;
}

function handleSavePendaftarAvatar(payload) {
  isSavingPendaftarAvatar.value = true;
  router.post('/pendaftar/profile/avatar', {
    avatar_data: payload.dataUrl,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingPendaftarAvatar.value = false;
      isPendaftarAvatarModalOpen.value = false;
      toast.success('Foto profil Anda berhasil disesuaikan dan diperbarui!');
    },
    onError: (errs) => {
      isSavingPendaftarAvatar.value = false;
      toast.error(Object.values(errs)[0] || 'Gagal memperbarui foto profil.');
    }
  });
}

function handleDeletePendaftarAvatar() {
  isSavingPendaftarAvatar.value = true;
  router.delete('/pendaftar/profile/avatar', {
    preserveScroll: true,
    onSuccess: () => {
      isSavingPendaftarAvatar.value = false;
      isPendaftarAvatarModalOpen.value = false;
      toast.success('Foto profil berhasil dihapus.');
    },
    onError: (errs) => {
      isSavingPendaftarAvatar.value = false;
      toast.error(Object.values(errs)[0] || 'Gagal menghapus foto profil.');
    }
  });
}

// ========================================================
// Modal 5: Penentuan Skema Pendaftaran / Paket
// ========================================================
const isPackageModalOpen = ref(false);
const isActivatingAllIn = ref(false);

function openPackageModal() {
  isPackageModalOpen.value = true;
}

function activateAllIn() {
  if (props.isAllInActive) {
    toast.info('Paket All-In Komplit sudah aktif untuk kontingen Anda.');
    return;
  }
  isActivatingAllIn.value = true;
  router.post('/pendaftar/paket/all-in/activate', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isActivatingAllIn.value = false;
      isPackageModalOpen.value = false;
      toast.success('Selamat! Seluruh cabang lomba berhasil diaktifkan dalam Paket All-In Komplit.');
    },
    onError: () => {
      isActivatingAllIn.value = false;
      toast.error('Gagal mengaktifkan paket All-In. Silakan coba kembali.');
    }
  });
}

// ========================================================
// Modal 6: Penunjukan Koordinator / PIC Cabang Lomba
// ========================================================
const isAssignPicModalOpen = ref(false);
const selectedRegForPic = ref(null);

const picForm = useForm({
  assigned_name: '',
  assigned_phone: '',
  assigned_role: 'Koordinator Cabang',
  assigned_notes: '',
});

const picRoleOptions = [
  { value: 'Koordinator Cabang', label: 'Koordinator Cabang (Guru / Pembina Khusus)' },
  { value: 'Kapten Tim', label: 'Kapten Tim (Ketua Regu / Delegasi Lomba)' },
  { value: 'Siswa Peserta', label: 'Siswa Peserta (Peserta Mandiri)' },
];

function openAssignPicModal(reg) {
  selectedRegForPic.value = reg;
  picForm.assigned_name = reg.assigned_name || '';
  picForm.assigned_phone = reg.assigned_phone || '';
  picForm.assigned_role = reg.assigned_role || 'Koordinator Cabang';
  picForm.assigned_notes = reg.assigned_notes || '';
  isAssignPicModalOpen.value = true;
}

function submitAssignPic() {
  if (!selectedRegForPic.value) return;
  picForm.post(`/pendaftar/registrations/${selectedRegForPic.value.id}/assign-pic`, {
    preserveScroll: true,
    onSuccess: () => {
      isAssignPicModalOpen.value = false;
      toast.success('Koordinator/PIC berhasil ditugaskan & notifikasi WA telah terkirim!');
    },
    onError: () => {
      toast.error('Gagal menyimpan penugasan. Periksa nomor WhatsApp dan nama petugas.');
    }
  });
}

function removeAssignedPic(regId) {
  if (!confirm('Apakah Anda yakin ingin membatalkan penunjukan petugas/PIC pada cabang lomba ini?')) return;
  router.post(`/pendaftar/registrations/${regId}/remove-pic`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      isAssignPicModalOpen.value = false;
      toast.success('Penunjukan petugas/PIC berhasil dibatalkan.');
    },
    onError: () => {
      toast.error('Gagal membatalkan penugasan.');
    }
  });
}

// ========================================================
// Modal 7: Unggah Bukti Pembayaran Paket All-In Terpadu
// ========================================================
const isPackageProofModalOpen = ref(false);
const packageProofFile = ref(null);
const packageProofPreview = ref(null);
const packageProofMethod = ref('Transfer Bank BSI');
const packagePaymentMethodOptions = [
  { value: 'Transfer Bank BSI', label: 'Transfer Bank BSI (Rek. 7123456789 a.n Semarak Aliyah)' },
  { value: 'Tunai di Kantor Panitia', label: 'Pembayaran Tunai di Kantor Sekretariat Panitia' },
];

const packageProofForm = useForm({
  payment_proof: null,
  payment_method: 'Transfer Bank BSI',
});

function openPackageProofModal() {
  packageProofFile.value = null;
  packageProofPreview.value = null;
  packageProofMethod.value = 'Transfer Bank BSI';
  packageProofForm.payment_proof = null;
  packageProofForm.payment_method = 'Transfer Bank BSI';
  isPackageProofModalOpen.value = true;
}

function handlePackageProofChange(e) {
  const file = e.target.files[0];
  if (file) {
    packageProofFile.value = file;
    packageProofForm.payment_proof = file;
    if (file.type && file.type.startsWith('image/')) {
      packageProofPreview.value = URL.createObjectURL(file);
    } else {
      packageProofPreview.value = null;
    }
  }
}

function submitPackageProof() {
  if (!packageProofForm.payment_proof) {
    toast.error('Silakan pilih berkas foto / bukti transfer terlebih dahulu.');
    return;
  }
  packageProofForm.payment_method = packageProofMethod.value;
  packageProofForm.post('/pendaftar/paket-all-in/upload-proof', {
    preserveScroll: true,
    onSuccess: () => {
      isPackageProofModalOpen.value = false;
      toast.success('Bukti transfer Paket All-In berhasil diunggah! Status pembayaran seluruh cabang lomba kini sedang diverifikasi panitia.');
    },
    onError: (errors) => {
      toast.error(errors.payment_proof || errors.message || 'Gagal mengunggah bukti pembayaran.');
    }
  });
}
</script>

<template>
  <Head title="Dashboard Pendaftar - SEMARAK ALIYAH XIV 2026" />

  <div class="registrant-dashboard-container min-h-screen" :class="{ 'dark-theme': isDark }">
    
    <!-- Top Header Navigation -->
    <header class="registrant-topbar sticky-top shadow-sm">
      <div class="container-fluid px-3 px-md-4 py-2.5 d-flex align-items-center justify-content-between">
        
        <!-- Brand & Logo -->
        <div class="d-flex align-items-center gap-2.5">
          <Link href="/" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="/assets/img/logo.png" alt="Logo" class="rounded-circle shadow-sm" style="width: 38px; height: 38px; object-fit: contain;" />
            <div class="d-none d-sm-block text-start">
              <div class="fw-black fs-13.5 tracking-wide text-uppercase topbar-brand" style="letter-spacing: 0.5px;">SEMARAK ALIYAH XIV</div>
              <div class="fs-11 topbar-subbrand fw-semibold">Portal Akun Pendaftar</div>
            </div>
          </Link>

          <span class="badge badge-portal-resmi rounded-pill px-2.5 py-1 fs-11 fw-bold d-none d-md-inline-flex align-items-center gap-1">
            <i class="ri-shield-user-line"></i>
            <span>Area Akun Pendaftar</span>
          </span>
        </div>

        <!-- Right User Actions -->
        <div class="d-flex align-items-center gap-2 gap-md-2.5">
          
          <Link
            href="/pendaftar/daftar-lomba"
            class="btn btn-primary btn-sm px-3 py-1.5 rounded-pill fs-12 fw-bold d-flex align-items-center gap-1.5 shadow-sm"
          >
            <i class="ri-add-circle-fill fs-14"></i>
            <span>Daftar Lomba Baru</span>
          </Link>

          <!-- Theme Switcher Button (Rule 2 & 3: Mandatory Light / Dark Toggle) -->
          <button
            type="button"
            class="btn btn-sm dashboard-theme-toggle rounded-pill d-flex align-items-center gap-1.5 px-2.5 py-1 fs-11.5 fw-bold"
            @click="toggleTheme"
            :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
          >
            <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line text-primary'"></i>
            <span class="d-none d-sm-inline">{{ isDark ? 'Mode Terang' : 'Mode Gelap' }}</span>
          </button>

          <!-- User Profile Dropdown Button -->
          <div class="d-flex align-items-center gap-1.5 ps-2 border-start topbar-divider">
            <button
              type="button"
              class="btn topbar-profile-btn btn-sm px-2.5 py-1 rounded-pill d-flex align-items-center gap-2"
              @click="openProfileModal"
              title="Pengaturan Profil Akun"
            >
              <img 
                v-if="user.avatar" 
                :src="user.avatar" 
                :alt="user.name" 
                class="rounded-circle object-fit-cover shadow-xs border" 
                style="width: 24px; height: 24px;"
              />
              <div 
                v-else 
                class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-11" 
                style="width: 24px; height: 24px;"
              >
                {{ user.name ? user.name.charAt(0).toUpperCase() : 'P' }}
              </div>
              <span class="fs-12 fw-bold d-none d-md-inline text-truncate" style="max-width: 140px;">
                {{ user.name }}
              </span>
              <i class="ri-edit-line fs-12 opacity-75"></i>
            </button>

            <!-- Logout Button -->
            <form action="/pendaftar/logout" method="POST" class="d-inline m-0">
              <input type="hidden" name="_token" :value="document?.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content')" />
              <button
                type="submit"
                class="btn topbar-logout-btn btn-sm px-2.5 py-1 rounded-pill d-flex align-items-center gap-1 fs-12 fw-semibold"
                title="Keluar dari Akun"
              >
                <i class="ri-logout-box-r-line"></i>
                <span class="d-none d-lg-inline">Keluar</span>
              </button>
            </form>
          </div>

        </div>

      </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="container-fluid px-3 px-md-4 py-4">
      
      <!-- Welcome Hero Banner -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 welcome-hero-card overflow-hidden">
        <div class="card-body p-4 position-relative">
          <div class="row align-items-center g-3">
            <div class="col-lg-8">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge hero-badge-status rounded-pill px-2.5 py-1 fs-11 fw-bold d-inline-flex align-items-center gap-1">
                  <i class="ri-checkbox-circle-fill"></i> Akun Aktif
                </span>
                <span class="badge hero-badge-role rounded-pill px-2.5 py-1 fs-11 fw-bold d-inline-flex align-items-center gap-1">
                  <i class="ri-user-star-fill"></i> {{ user.registrant_role }}
                </span>
                <span class="fs-12 hero-school-tag">• Asal: <strong class="text-white">{{ user.school_name }}</strong> (Kec. {{ user.school_district }})</span>
              </div>

              <h2 class="fs-20 fs-md-22 fw-black text-white mb-1.5">
                Selamat Datang, {{ user.name }}! 👋
              </h2>
              <p class="fs-13 hero-subtitle mb-0" style="max-width: 680px;">
                Kelola data kontingen lomba sekolah Anda di sini. Anda dapat memperbarui data peserta, catatan, dan berkas persyaratan pendaftaran selagi masa pendaftaran belum ditutup.
              </p>
            </div>

            <div class="col-lg-4 text-lg-end">
              <div class="d-inline-flex flex-column gap-1.5 text-start p-3 rounded-3 hero-helpdesk-box">
                <div class="fs-10.5 text-uppercase fw-bold tracking-wider hero-helpdesk-label">Helpdesk Panitia Resmi</div>
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-whatsapp-fill fs-20" style="color: #4ade80 !important;"></i>
                  <a :href="`https://wa.me/${paymentInfo.helpdesk_wa ? paymentInfo.helpdesk_wa.replace(/\D/g, '') : '6283850070767'}`" target="_blank" class="text-white text-decoration-none fw-bold fs-13 hover:text-success">
                    {{ paymentInfo.helpdesk_wa || '0838 5007 0767' }}
                  </a>
                </div>
                <div class="fs-11 hero-helpdesk-desc">Hubungi panitia jika mengalami kendala pendaftaran.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Summary Stats Grid -->
      <div class="row g-3 mb-4">
        
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 stat-mini-card">
            <div class="card-body p-3 d-flex align-items-center gap-2.5">
              <div class="stat-icon-wrap stat-icon-primary rounded-3 d-flex align-items-center justify-content-center fs-20 flex-shrink-0">
                <i class="ri-file-list-3-fill"></i>
              </div>
              <div class="overflow-hidden">
                <div class="fs-11.5 stat-label fw-bold text-truncate">Total Pendaftaran</div>
                <div class="fs-19 fw-black stat-value">{{ stats.total_regs || 0 }} <span class="fs-11.5 stat-unit fw-semibold">Lomba</span></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 stat-mini-card">
            <div class="card-body p-3 d-flex align-items-center gap-2.5">
              <div class="stat-icon-wrap stat-icon-success rounded-3 d-flex align-items-center justify-content-center fs-20 flex-shrink-0">
                <i class="ri-checkbox-circle-fill"></i>
              </div>
              <div class="overflow-hidden">
                <div class="fs-11.5 stat-label fw-bold text-truncate">Berkas Terverifikasi</div>
                <div class="fs-19 fw-black stat-value text-success-highlight">{{ stats.verified_count || 0 }} <span class="fs-11.5 stat-unit fw-semibold">Valid</span></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 stat-mini-card">
            <div class="card-body p-3 d-flex align-items-center gap-2.5">
              <div class="stat-icon-wrap stat-icon-warning rounded-3 d-flex align-items-center justify-content-center fs-20 flex-shrink-0">
                <i class="ri-time-fill"></i>
              </div>
              <div class="overflow-hidden">
                <div class="fs-11.5 stat-label fw-bold text-truncate">Menunggu Verifikasi</div>
                <div class="fs-19 fw-black stat-value text-warning-highlight">{{ stats.pending_count || 0 }} <span class="fs-11.5 stat-unit fw-semibold">Antrean</span></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-4 stat-mini-card">
            <div class="card-body p-3 d-flex align-items-center gap-2.5">
              <div class="stat-icon-wrap stat-icon-info rounded-3 d-flex align-items-center justify-content-center fs-20 flex-shrink-0">
                <i class="ri-wallet-3-fill"></i>
              </div>
              <div class="overflow-hidden">
                <div class="fs-11.5 stat-label fw-bold text-truncate">Pembayaran Lunas</div>
                <div class="fs-16 fw-black stat-value text-info-highlight text-truncate">{{ formatRupiah(stats.paid_fee) }}</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Banner Khusus Petugas / PIC yang Ditunjuk -->
      <div v-if="isCurrentUserPicOnly" class="card border-0 shadow-sm rounded-4 mb-4 pic-notice-card">
        <div class="card-body p-3.5 d-flex align-items-center gap-3">
          <div class="stat-icon-wrap stat-icon-info rounded-circle d-flex align-items-center justify-content-center fs-22 flex-shrink-0">
            <i class="ri-user-shared-fill"></i>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span class="badge bg-primary text-white rounded-pill px-2.5 py-0.5 fs-11 fw-bold">Hak Akses Petugas Terpilih</span>
              <span class="fs-12 fw-semibold table-muted-text">Akses Khusus Cabang Lomba Ditugaskan</span>
            </div>
            <div class="fs-13.5 fw-bold dashboard-title">
              Anda masuk sebagai petugas / koordinator yang ditunjuk oleh pembina kontingen sekolah.
            </div>
            <div class="fs-12 dashboard-desc mt-0.5">
              Anda memiliki wewenang untuk mengisi daftar nama delegasi dan mengunggah berkas persyaratan lomba yang ditugaskan kepada Anda.
            </div>
          </div>
        </div>
      </div>

      <!-- Banner Penentuan Skema Paket Pendaftaran (Hanya Tampil untuk Pemilik Akun Utama) -->
      <div v-if="!isCurrentUserPicOnly" class="card border-0 shadow-sm rounded-4 mb-4 package-banner-card overflow-hidden">
        <div class="card-body p-3.5 p-md-4">
          <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            
            <div class="d-flex align-items-start gap-3">
              <div class="package-banner-icon-wrap rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" :class="isAllInActive ? 'package-icon-success' : 'package-icon-primary'">
                <i :class="isAllInActive ? 'ri-shield-check-fill fs-24' : 'ri-gift-2-fill fs-24'"></i>
              </div>
              <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                  <span class="fs-11 fw-bold text-uppercase tracking-wider" :class="isAllInActive ? 'text-success-highlight' : 'text-primary-highlight'">
                    {{ isAllInActive ? 'Skema Aktif: Paket All-In Komplit' : 'Penentuan Skema Pendaftaran Lomba' }}
                  </span>
                  <span v-if="isAllInActive" class="badge bg-success text-white rounded-pill px-2 py-0.5 fs-10 fw-bold">
                    Semua Cabang Aktif
                  </span>
                  <span v-else class="badge badge-all-in rounded-pill px-2 py-0.5 fs-10 fw-bold">
                    Tersedia Opsi Hemat
                  </span>
                </div>

                <h4 class="fs-16 fw-black mb-1 package-banner-title">
                  {{ isAllInActive ? 'Kontingen Anda Menggunakan Paket All-In Komplit' : 'Pilih Skema Pendaftaran: Paket Satuan atau Paket All-In Komplit?' }}
                </h4>
                <p class="fs-12.5 mb-0 package-banner-desc" style="max-width: 740px;">
                  {{ isAllInActive 
                    ? `Seluruh ${packageDetails.activeCount} cabang lomba telah otomatis terdaftar ke tabel delegasi di bawah. Tagihan pembayaran terkonsolidasi menjadi satu (${formatRupiah(packagePayment.price)}) dan mencakup seluruh cabang lomba kontingen Anda.` 
                    : `Paket Satuan memungkinkan pendaftaran per cabang secara mandiri. Ingin lebih praktis dan hemat? Pilih Paket All-In Komplit (${formatRupiah(packageDetails.price)}), semua ${packageDetails.activeCount} cabang lomba otomatis masuk ke tabel sehingga Anda cukup melengkapi berkas tanpa mendaftar ulang satu per satu!` 
                  }}
                </p>
              </div>
            </div>

            <!-- CTA Actions -->
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
              <button
                type="button"
                class="btn btn-sm rounded-pill px-3.5 py-2 fs-12 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm"
                :class="isAllInActive ? 'btn-outline-success' : 'btn-primary'"
                @click="openPackageModal"
              >
                <i :class="isAllInActive ? 'ri-award-line' : 'ri-magic-line'"></i>
                <span>{{ isAllInActive ? 'Detail Skema Paket' : 'Pilih / Pelajari Skema Paket' }}</span>
              </button>
            </div>

          </div>

          <!-- Integrated Payment Status Panel for Paket All-In -->
          <div v-if="isAllInActive" class="mt-3.5 pt-3.5 border-top package-payment-panel">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-3 rounded-3 package-payment-box">
              <div class="d-flex align-items-center gap-3">
                <div class="package-payment-icon-wrap rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" :class="packagePayment.is_paid ? 'pay-icon-success' : 'pay-icon-warning'">
                  <i :class="packagePayment.is_paid ? 'ri-checkbox-circle-fill fs-22' : 'ri-time-fill fs-22'"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="fs-11 fw-bold text-uppercase tracking-wider table-muted-text">Status Tagihan Paket All-In:</span>
                    <span
                      v-if="packagePayment.is_paid"
                      class="badge badge-status-verified rounded-pill px-2.5 py-0.5 fs-11 fw-bold"
                    >
                      <i class="ri-checkbox-circle-fill me-1"></i> LUNAS TERVERIFIKASI
                    </span>
                    <span
                      v-else-if="packagePayment.payment_status === 'Ditolak'"
                      class="badge badge-status-rejected rounded-pill px-2.5 py-0.5 fs-11 fw-bold"
                    >
                      <i class="ri-close-circle-fill me-1"></i> BUKTI PEMBAYARAN PERLU PERBAIKAN
                    </span>
                    <span
                      v-else
                      class="badge badge-status-pending rounded-pill px-2.5 py-0.5 fs-11 fw-bold"
                    >
                      <i class="ri-time-fill me-1"></i> MENUNGGU VERIFIKASI PANITIA
                    </span>
                  </div>

                  <div class="fs-13 fw-bold dashboard-title d-flex align-items-center gap-2 flex-wrap">
                    <span>Total Tagihan: <strong class="text-success-highlight">{{ formatRupiah(packagePayment.price) }}</strong></span>
                    <span class="table-muted-text fs-11">({{ packageDetails.activeCount }} cabang lomba terdaftar)</span>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill fs-10 px-2 py-0.5">
                      {{ packagePayment.bank_name }} - {{ packagePayment.account_number }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Quick Action Proof of Payment -->
              <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap">
                <a
                  v-if="packagePayment.payment_proof_url"
                  :href="packagePayment.payment_proof_url"
                  target="_blank"
                  class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fs-12 fw-bold d-inline-flex align-items-center gap-1.5"
                >
                  <i class="ri-image-line"></i>
                  <span>Lihat Bukti Transfer</span>
                </a>

                <button
                  v-if="!packagePayment.is_paid"
                  type="button"
                  class="btn btn-sm btn-success rounded-pill px-3.5 py-1.5 fs-12 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm"
                  @click="openPackageProofModal"
                >
                  <i :class="packagePayment.payment_proof_url ? 'ri-refresh-line' : 'ri-upload-2-fill'"></i>
                  <span>{{ packagePayment.payment_proof_url ? 'Unggah Ulang Bukti Transfer' : 'Unggah Bukti Transfer Paket' }}</span>
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Main Registrations Section -->
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden dashboard-card">
        
        <!-- Table Header & Controls -->
        <div class="card-header dashboard-card-header p-3 p-md-4">
          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            
            <div>
              <h5 class="fs-16 fw-black dashboard-section-title mb-1 d-flex align-items-center gap-2">
                <i class="ri-trophy-fill text-warning"></i>
                <span>Daftar Partisipasi Lomba Delegasi</span>
              </h5>
              <p class="fs-12 dashboard-section-desc mb-0">
                Klik tombol <strong>"Perbarui"</strong> untuk mengoreksi nama atau melengkapi dokumen sebelum pendaftaran ditutup.
              </p>
            </div>

            <!-- Filter Buttons & Search -->
            <div class="d-flex flex-wrap align-items-center gap-2">
              
              <button
                v-if="!isCurrentUserPicOnly"
                type="button"
                class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fs-11.5 fw-bold d-inline-flex align-items-center gap-1.5"
                @click="openPackageModal"
                title="Lihat / Atur Pilihan Skema Paket Pendaftaran"
              >
                <i class="ri-award-fill"></i>
                <span>Skema Paket</span>
              </button>

              <div class="btn-group btn-group-sm rounded-pill overflow-hidden p-0.5 filter-btn-group">
                <button
                  type="button"
                  class="btn btn-sm px-3 rounded-pill fw-bold fs-11"
                  :class="activeFilter === 'all' ? 'filter-btn-active-all' : 'filter-btn-inactive'"
                  @click="activeFilter = 'all'"
                >
                  Semua ({{ registrations.length }})
                </button>
                <button
                  type="button"
                  class="btn btn-sm px-3 rounded-pill fw-bold fs-11"
                  :class="activeFilter === 'pending' ? 'filter-btn-active-pending' : 'filter-btn-inactive'"
                  @click="activeFilter = 'pending'"
                >
                  Menunggu
                </button>
                <button
                  type="button"
                  class="btn btn-sm px-3 rounded-pill fw-bold fs-11"
                  :class="activeFilter === 'verified' ? 'filter-btn-active-verified' : 'filter-btn-inactive'"
                  @click="activeFilter = 'verified'"
                >
                  Terverifikasi
                </button>
              </div>

              <div class="input-group input-group-sm dashboard-search-group" style="max-width: 200px;">
                <span class="input-group-text dashboard-search-icon">
                  <i class="ri-search-line"></i>
                </span>
                <input
                  v-model="searchQuery"
                  type="text"
                  class="form-control dashboard-search-input fs-12"
                  placeholder="Cari kode/lomba..."
                />
              </div>

            </div>

          </div>
        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
          
          <!-- Empty State -->
          <div v-if="filteredRegistrations.length === 0" class="text-center py-5 px-3">
            <div class="rounded-circle dashboard-empty-icon d-inline-flex align-items-center justify-content-center fs-28 mb-3" style="width: 64px; height: 64px;">
              <i class="ri-file-search-line"></i>
            </div>
            <h6 class="fs-15 fw-bold dashboard-title mb-1">Belum Ada Pendaftaran Lomba</h6>
            <p class="fs-13 dashboard-desc mb-3" style="max-width: 420px; margin: 0 auto;">
              Anda belum mendaftarkan kontingen lomba atau tidak ada data yang cocok dengan filter pencarian.
            </p>
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
              <Link
                href="/pendaftar/daftar-lomba"
                class="btn btn-primary btn-sm px-4 py-2 rounded-pill fs-13 fw-bold shadow-sm d-inline-flex align-items-center gap-2"
              >
                <i class="ri-add-circle-fill"></i>
                <span>Daftarkan Delegasi Satuan</span>
              </Link>
              <button
                v-if="!isCurrentUserPicOnly"
                type="button"
                class="btn btn-outline-primary btn-sm px-3.5 py-2 rounded-pill fs-13 fw-bold d-inline-flex align-items-center gap-2"
                @click="openPackageModal"
              >
                <i class="ri-award-line"></i>
                <span>Aktifkan Paket All-In</span>
              </button>
            </div>
          </div>

          <!-- Registrations Table -->
          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0 dashboard-table fs-13">
              <thead>
                <tr>
                  <th scope="col" class="ps-4 py-3">Kode & Cabang Lomba</th>
                  <th scope="col" class="py-3">Delegasi & Peserta</th>
                  <th scope="col" class="py-3 text-center">Status Berkas</th>
                  <th scope="col" class="py-3 text-center">Pembayaran</th>
                  <th scope="col" class="py-3 text-center">Batas Waktu Edit</th>
                  <th scope="col" class="pe-4 py-3 text-end">Aksi Pendaftar</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="reg in filteredRegistrations" :key="reg.id">
                  
                  <!-- Kode & Cabang Lomba -->
                  <td class="ps-4 py-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="badge badge-reg-code font-monospace fs-11 px-2 py-0.5 rounded-2">
                        {{ reg.reg_code }}
                      </span>
                      <span v-if="reg.package_type === 'Paket All-In'" class="badge badge-all-in fs-10 px-2 py-0.5 rounded-pill fw-bold">
                        <i class="ri-award-fill me-0.5"></i> Paket All-In
                      </span>
                    </div>
                    <div class="fw-bold fs-14 table-title-text mt-1">
                      {{ reg.competition_title }}
                    </div>
                    <div class="fs-11 table-muted-text d-flex align-items-center gap-2 mt-0.5 flex-wrap">
                      <span><i class="ri-calendar-line me-0.5"></i> {{ formatIndoDate(reg.created_at) }}</span>
                      <span v-if="reg.draw_number" class="table-draw-number fw-bold">
                        • No. Urut: #{{ reg.draw_number }}
                      </span>
                    </div>
                  </td>

                  <!-- Delegasi & Peserta -->
                  <td class="py-3">
                    <div class="table-title-text text-truncate fw-semibold" style="max-width: 260px;" :title="reg.participants_names">
                      {{ reg.participants_names || '-' }}
                    </div>
                    <div class="fs-11 table-muted-text mt-0.5">
                      Pendamping: <span class="table-subtitle-text fw-medium">{{ reg.mentor_name || user.name }}</span> ({{ reg.mentor_wa || user.phone }})
                    </div>

                    <!-- Assigned PIC Info / Button -->
                    <div v-if="reg.assigned_name" class="mt-1.5 pt-1 border-top border-dashed d-flex align-items-center justify-content-between gap-1 flex-wrap">
                      <div class="d-flex align-items-center gap-1 fs-11">
                        <span class="badge badge-assigned-pic rounded-pill px-2 py-0.5 fw-bold d-inline-flex align-items-center gap-1">
                          <i class="ri-user-star-line"></i> {{ reg.assigned_role || 'Koordinator' }}
                        </span>
                        <strong class="table-title-text">{{ reg.assigned_name }}</strong>
                        <a v-if="reg.assigned_phone" :href="`https://wa.me/${reg.assigned_phone.replace(/\D/g, '')}`" target="_blank" class="text-success text-decoration-none fw-semibold ms-0.5" title="Chat WhatsApp PIC">
                          <i class="ri-whatsapp-line"></i>
                        </a>
                      </div>
                      <button v-if="!isCurrentUserPicOnly" type="button" class="btn btn-link btn-sm p-0 fs-10.5 text-primary text-decoration-none fw-bold" @click="openAssignPicModal(reg)">
                        Ubah
                      </button>
                    </div>
                    <div v-else-if="!isCurrentUserPicOnly" class="mt-1">
                      <button type="button" class="btn btn-xs rounded-pill px-2 py-0.5 fs-10.5 fw-semibold d-inline-flex align-items-center gap-1 btn-assign-pic-quick" @click="openAssignPicModal(reg)" title="Tunjuk Guru Koordinator atau Kapten Tim">
                        <i class="ri-user-add-line"></i>
                        <span>+ Tunjuk PIC / Koordinator</span>
                      </button>
                    </div>
                  </td>

                  <!-- Status Berkas Badge (Rule 3) -->
                  <td class="py-3 text-center">
                    <span
                      v-if="reg.registration_status === 'Terverifikasi'"
                      class="badge badge-status-verified rounded-pill px-2.5 py-1 fs-11 fw-bold"
                    >
                      <i class="ri-checkbox-circle-fill me-1"></i> Terverifikasi
                    </span>
                    <span
                      v-else-if="reg.registration_status === 'Ditolak'"
                      class="badge badge-status-rejected rounded-pill px-2.5 py-1 fs-11 fw-bold"
                    >
                      <i class="ri-close-circle-fill me-1"></i> Perlu Perbaikan
                    </span>
                    <span
                      v-else
                      class="badge badge-status-pending rounded-pill px-2.5 py-1 fs-11 fw-bold"
                    >
                      <i class="ri-time-fill me-1"></i> Menunggu
                    </span>

                    <div v-if="reg.verification_notes" class="fs-10 text-danger fw-bold mt-1 text-truncate" style="max-width: 140px;" :title="reg.verification_notes">
                      {{ reg.verification_notes }}
                    </div>
                  </td>

                  <!-- Status Pembayaran Badge (Rule 3) -->
                  <td class="py-3 text-center">
                    <!-- Jika Paket All-In -->
                    <template v-if="reg.package_type === 'Paket All-In'">
                      <span
                        v-if="reg.payment_status === 'Terverifikasi' || reg.payment_status === 'Lunas'"
                        class="badge badge-status-verified rounded-pill px-2.5 py-1 fs-11 fw-bold"
                      >
                        <i class="ri-checkbox-circle-fill me-1"></i> Lunas (Paket All-In)
                      </span>
                      <span
                        v-else-if="reg.payment_status === 'Ditolak'"
                        class="badge badge-status-rejected rounded-pill px-2.5 py-1 fs-11 fw-bold"
                      >
                        <i class="ri-close-circle-fill me-1"></i> Perlu Perbaikan
                      </span>
                      <span
                        v-else-if="reg.is_all_in_master || reg.total_fee > 0"
                        class="badge badge-status-pending rounded-pill px-2.5 py-1 fs-11 fw-bold"
                      >
                        <i class="ri-time-fill me-1"></i> Menunggu Verifikasi
                      </span>
                      <span
                        v-else
                        class="badge badge-status-allin-included rounded-pill px-2.5 py-1 fs-11 fw-bold"
                        title="Biaya telah tercakup dalam pembayaran tunggal Paket All-In Komplit"
                      >
                        <i class="ri-shield-check-line me-1"></i> Tercakup Paket All-In
                      </span>

                      <!-- Sub Keterangan Biaya -->
                      <div v-if="reg.is_all_in_master || reg.total_fee > 0" class="fs-11 fw-bold table-title-text mt-1 d-flex align-items-center justify-content-center gap-1">
                        <span>{{ formatRupiah(reg.total_fee) }}</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-9 px-1.5 py-0.2">Induk Tagihan</span>
                      </div>
                      <div v-else class="fs-10.5 mt-1 fw-semibold" :class="(reg.payment_status === 'Terverifikasi' || reg.payment_status === 'Lunas') ? 'text-success' : 'table-muted-text'">
                        <i :class="(reg.payment_status === 'Terverifikasi' || reg.payment_status === 'Lunas') ? 'ri-check-double-line me-0.5' : 'ri-time-line me-0.5'"></i>
                        {{ (reg.payment_status === 'Terverifikasi' || reg.payment_status === 'Lunas') ? 'Bebas Biaya Mandiri' : 'Ikut Verifikasi Induk' }}
                      </div>
                    </template>

                    <!-- Jika Paket Satuan Mandiri -->
                    <template v-else>
                      <span
                        v-if="reg.payment_status === 'Terverifikasi' || reg.payment_status === 'Lunas'"
                        class="badge badge-status-verified rounded-pill px-2.5 py-1 fs-11 fw-bold"
                      >
                        <i class="ri-money-dollar-circle-fill me-1"></i> Lunas
                      </span>
                      <span
                        v-else-if="reg.payment_status === 'Ditolak'"
                        class="badge badge-status-rejected rounded-pill px-2.5 py-1 fs-11 fw-bold"
                      >
                        <i class="ri-close-circle-fill me-1"></i> Ditolak
                      </span>
                      <span
                        v-else
                        class="badge badge-status-pending rounded-pill px-2.5 py-1 fs-11 fw-bold"
                      >
                        <i class="ri-error-warning-fill me-1"></i> Menunggu Verifikasi
                      </span>
                      <div class="fs-11 fw-bold table-title-text mt-1">
                        {{ formatRupiah(reg.total_fee) }}
                      </div>
                    </template>
                  </td>

                  <!-- Status Edit / Deadline -->
                  <td class="py-3 text-center">
                    <div v-if="reg.can_edit" class="text-success-highlight fs-12 fw-bold">
                      <i class="ri-check-line"></i> Terbuka Diedit
                      <div class="fs-10.5 table-muted-text fw-medium" v-if="reg.comp_deadline">
                        Hingga: {{ formatIndoDateOnly(reg.comp_deadline) }}
                      </div>
                    </div>
                    <div v-else class="text-danger fs-12 fw-bold">
                      <i class="ri-lock-fill"></i> Ditutup Panitia
                      <div class="fs-10.5 table-muted-text fw-medium">Data Terkunci</div>
                    </div>
                  </td>

                  <!-- Action Buttons -->
                  <td class="pe-4 py-3 text-end">
                    <div class="d-inline-flex align-items-center gap-1.5 flex-nowrap">
                      
                      <!-- Perbarui Data & Berkas Button -->
                      <button
                        type="button"
                        class="btn btn-sm rounded-3 px-2.5 py-1 fs-12 fw-bold d-inline-flex align-items-center gap-1 table-action-edit-btn"
                        :class="reg.can_edit ? '' : 'disabled opacity-50'"
                        :disabled="!reg.can_edit"
                        @click="openEditModal(reg)"
                        :title="reg.can_edit ? 'Perbarui data peserta & berkas' : 'Pendaftaran cabang ini telah ditutup'"
                      >
                        <i class="ri-file-edit-line"></i>
                        <span>Perbarui</span>
                      </button>

                      <!-- Detail Pendaftaran Button -->
                      <button
                        type="button"
                        class="btn btn-sm rounded-3 px-2.5 py-1 fs-12 fw-bold d-inline-flex align-items-center gap-1 table-action-detail-btn"
                        @click="openDetailModal(reg)"
                        title="Lihat Detail Lengkap Pendaftaran"
                      >
                        <i class="ri-file-list-3-line"></i>
                        <span>Detail</span>
                      </button>

                      <!-- Tunjuk PIC / Koordinator Cabang Button -->
                      <button
                        v-if="!isCurrentUserPicOnly"
                        type="button"
                        class="btn btn-sm rounded-3 px-2 py-1 fs-12 fw-bold d-inline-flex align-items-center gap-1 table-action-pic-btn"
                        @click="openAssignPicModal(reg)"
                        :title="reg.assigned_name ? `Petugas: ${reg.assigned_name} (${reg.assigned_role})` : 'Tunjuk Koordinator / Kapten Cabang Ini'"
                      >
                        <i :class="reg.assigned_name ? 'ri-user-star-fill text-warning' : 'ri-user-shared-line'"></i>
                        <span class="d-none d-xl-inline">{{ reg.assigned_name ? 'PIC' : 'Tunjuk PIC' }}</span>
                      </button>

                      <!-- Cetak Nota / Kwitansi Resmi Button -->
                      <a
                        :href="'/pendaftar/invoice/' + reg.id"
                        target="_blank"
                        class="btn btn-sm rounded-3 px-2 py-1 fs-12 fw-bold d-inline-flex align-items-center gap-1 table-action-invoice-btn text-decoration-none"
                        title="Cetak Nota Invoice / Kwitansi Pembayaran Resmi (A4)"
                      >
                        <i class="ri-file-paper-2-line"></i>
                        <span class="d-none d-xl-inline">Nota</span>
                      </a>

                      <!-- Cetak Bukti Resmi Button -->
                      <button
                        type="button"
                        class="btn btn-sm rounded-3 px-2.5 py-1 fs-12 fw-bold d-inline-flex align-items-center gap-1 table-action-print-btn"
                        @click="openPrintModal(reg)"
                        title="Cetak Bukti Pendaftaran Resmi (Standar Dokumen A4)"
                      >
                        <i class="ri-printer-line"></i>
                        <span>Cetak</span>
                      </button>

                    </div>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>

        </div>

      </div>

    </main>

    <!-- ========================================================================= -->
    <!-- MODAL 1: PERBARUI DATA & BERKAS PENDAFTARAN                               -->
    <!-- ========================================================================= -->
    <div v-if="isEditModalOpen && activeReg" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-lg overflow-hidden" style="max-width: 680px; max-height: 90vh; display: flex; flex-direction: column;">
        
        <!-- Modal Header -->
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between">
          <div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge badge-reg-code fs-11 font-monospace">{{ activeReg.reg_code }}</span>
              <h5 class="fs-16 fw-bold modal-title-text mb-0">Perbarui Data & Berkas Pendaftaran</h5>
            </div>
            <div class="fs-12 modal-desc-text mt-0.5">
              Cabang Lomba: <strong class="modal-title-text">{{ activeReg.competition_title }}</strong>
            </div>
          </div>
          <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" @click="isEditModalOpen = false"></button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-3 px-md-4 overflow-y-auto flex-grow-1 modal-body-box">
          <form @submit.prevent="submitEditRegistration" class="d-flex flex-column gap-3">
            
            <!-- Alert Info -->
            <div class="p-3 rounded-3 modal-alert-info d-flex gap-2">
              <i class="ri-information-line fs-18 text-info flex-shrink-0"></i>
              <div class="fs-12 modal-alert-text">
                Perubahan data dan pengunggahan berkas baru akan langsung tersinkronisasi ke sistem panitia. Pastikan data yang dimasukkan sudah valid.
              </div>
            </div>

            <!-- Peserta / Delegasi -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Daftar Nama Siswa Peserta / Anggota Tim <span class="text-danger">*</span>
              </label>
              <textarea
                v-model="editForm.participants_names"
                rows="3"
                class="form-control modal-input fs-13"
                placeholder="Tuliskan nama lengkap peserta delegasi..."
                required
              ></textarea>
              <div class="fs-11 modal-desc-text mt-1">
                Jika terdapat beberapa peserta, pisahkan dengan koma atau baris baru (enter).
              </div>
            </div>

            <!-- Pendamping & WA (Hanya Akun Utama yang Mengelola) -->
            <div v-if="!isCurrentUserPicOnly" class="row g-3">
              <div class="col-md-6">
                <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                  Nama Guru Pendamping / Official
                </label>
                <input
                  v-model="editForm.mentor_name"
                  type="text"
                  class="form-control modal-input fs-13 py-2"
                  placeholder="Nama pendamping..."
                />
              </div>

              <div class="col-md-6">
                <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                  Nomor WhatsApp Pendamping
                </label>
                <input
                  v-model="editForm.mentor_wa"
                  type="tel"
                  class="form-control modal-input fs-13 py-2"
                  placeholder="08xxxxxxxxxx"
                />
              </div>
            </div>

            <!-- Upload Bukti Pembayaran (Hanya Akun Utama yang Mengelola Finansial) -->
            <div v-if="!isCurrentUserPicOnly" class="p-3 rounded-3 modal-upload-box">
              <label class="form-label fs-13 fw-bold modal-label-text d-flex align-items-center gap-1.5 mb-1">
                <i class="ri-receipt-line text-success"></i>
                <span>Unggah Ulang Bukti Transfer Pembayaran Bank (Opsional)</span>
              </label>
              <input
                type="file"
                class="form-control modal-input fs-12 mb-2"
                accept="image/*,application/pdf"
                @change="handlePaymentProofChange"
              />
              <div class="fs-11 modal-desc-text">
                Format didukung: JPG, PNG, PDF. Maksimal 5 MB. Unggah jika ada perubahan transfer atau bukti baru.
              </div>

              <!-- Preview if selected -->
              <div v-if="paymentProofPreview" class="mt-2 text-center p-2 rounded modal-preview-box">
                <img :src="paymentProofPreview" alt="Bukti Transfer Baru" style="max-height: 160px; object-fit: contain;" class="rounded" />
              </div>
            </div>

            <!-- Berkas yang Sudah Diunggah Sebelumnya -->
            <div v-if="activeReg.file_documents && activeReg.file_documents.length > 0">
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Dokumen & Berkas Tersimpan
              </label>
              <div class="d-flex flex-column gap-1.5">
                <div
                  v-for="(doc, dIdx) in activeReg.file_documents"
                  :key="dIdx"
                  class="p-2 px-3 rounded-2 modal-doc-item d-flex align-items-center justify-content-between fs-12"
                >
                  <div class="d-flex align-items-center gap-2 text-truncate">
                    <i class="ri-file-text-line text-primary"></i>
                    <span class="modal-title-text fw-medium text-truncate">{{ doc.title || doc.filename }}</span>
                  </div>
                  <a :href="doc.url" target="_blank" class="btn btn-sm btn-outline-info border-0 p-1 fs-12">
                    <i class="ri-external-link-line"></i> Lihat
                  </a>
                </div>
              </div>
            </div>

            <!-- Catatan Khusus -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Catatan Khusus untuk Panitia
              </label>
              <textarea
                v-model="editForm.notes"
                rows="2"
                class="form-control modal-input fs-13"
                placeholder="Catatan tambahan bila ada..."
              ></textarea>
            </div>

          </form>
        </div>

        <!-- Modal Footer -->
        <div class="p-3 px-md-4 modal-footer-box d-flex align-items-center justify-content-end gap-2">
          <button
            type="button"
            class="btn modal-btn-cancel btn-sm px-3 py-2 fs-12 fw-semibold"
            @click="isEditModalOpen = false"
            :disabled="editForm.processing"
          >
            Batal
          </button>
          <button
            type="button"
            class="btn btn-success btn-sm px-4 py-2 fs-13 fw-bold shadow-sm d-flex align-items-center gap-1.5"
            :disabled="editForm.processing"
            @click="submitEditRegistration"
          >
            <span v-if="editForm.processing" class="spinner-border spinner-border-sm"></span>
            <i v-else class="ri-save-fill"></i>
            <span>{{ editForm.processing ? 'Menyimpan...' : 'Simpan Pembaruan' }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: DETAIL LENGKAP PENDAFTARAN (Screen View - Sesuai Aturan UI/UX)   -->
    <!-- ========================================================================= -->
    <div v-if="isDetailModalOpen && detailReg" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-lg overflow-hidden d-flex flex-column" style="max-width: 740px; max-height: 90vh;">
        
        <!-- Modal Header -->
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge badge-reg-code fs-11 font-monospace px-2 py-0.5 rounded-2">
              {{ detailReg.reg_code }}
            </span>
            <h5 class="fs-16 fw-bold modal-title-text mb-0">Detail Pendaftaran Delegasi</h5>
            
            <!-- Badges Sesuai Rule 3 -->
            <span
              v-if="detailReg.registration_status === 'Terverifikasi'"
              class="badge badge-status-verified rounded-pill px-2.5 py-0.5 fs-11 fw-bold"
            >
              <i class="ri-checkbox-circle-fill me-1"></i> Terverifikasi
            </span>
            <span
              v-else-if="detailReg.registration_status === 'Ditolak'"
              class="badge badge-status-rejected rounded-pill px-2.5 py-0.5 fs-11 fw-bold"
            >
              <i class="ri-close-circle-fill me-1"></i> Perlu Perbaikan
            </span>
            <span
              v-else
              class="badge badge-status-pending rounded-pill px-2.5 py-0.5 fs-11 fw-bold"
            >
              <i class="ri-time-fill me-1"></i> Menunggu Verifikasi
            </span>
          </div>

          <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" @click="isDetailModalOpen = false"></button>
        </div>

        <!-- Modal Body (Scrollable & High Contrast - Rule 2) -->
        <div class="p-3 px-md-4 overflow-y-auto flex-grow-1 modal-body-box d-flex flex-column gap-3 fs-13">
          
          <!-- Catatan Verifikasi Panitia jika ada -->
          <div v-if="detailReg.verification_notes" class="p-3 rounded-3 modal-alert-danger d-flex gap-2.5">
            <i class="ri-error-warning-fill fs-18 text-danger flex-shrink-0 mt-0.5"></i>
            <div>
              <div class="fw-bold text-danger fs-12 mb-0.5">Catatan Penting dari Panitia Pelaksana:</div>
              <div class="modal-alert-text fs-12">{{ detailReg.verification_notes }}</div>
            </div>
          </div>

          <!-- Section 1: Informasi Cabang Lomba -->
          <div class="p-3 rounded-3 detail-section-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="detail-label">Informasi Cabang Lomba</span>
              <span class="badge badge-all-in fs-11 px-2 py-0.5 rounded-pill" v-if="detailReg.package_type === 'Paket All-In'">Paket All-In</span>
              <span class="badge badge-reg-code fs-11 px-2 py-0.5 rounded-pill" v-else>{{ detailReg.package_type || 'Paket Satuan' }}</span>
            </div>
            
            <div class="row g-2 align-items-center">
              <div class="col-sm-8">
                <div class="fs-15 fw-bold detail-value">{{ detailReg.competition_title }}</div>
                <div class="fs-11 modal-desc-text mt-0.5">{{ detailReg.comp_category || 'Tingkat SMP / MTs Sederajat Se-Jawa Timur' }}</div>
              </div>
              <div class="col-sm-4 text-sm-end">
                <div class="detail-label">Nomor Undian TM</div>
                <div v-if="detailReg.draw_number" class="fs-16 fw-black text-info-highlight font-monospace">
                  #{{ detailReg.draw_number }}
                </div>
                <div v-else class="fs-11 modal-desc-text fst-italic">
                  Diundi saat TM
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Identitas Lembaga & Official -->
          <div class="p-3 rounded-3 detail-section-card">
            <span class="detail-label d-block mb-2">Identitas Lembaga & Official Kontingen</span>
            <div class="row g-3">
              <div class="col-md-6">
                <div class="detail-label-sub">Asal Sekolah / Madrasah</div>
                <div class="fw-bold detail-value fs-14">{{ detailReg.school_name }}</div>
              </div>
              <div class="col-md-6">
                <div class="detail-label-sub">Guru Pendamping / Official</div>
                <div class="fw-bold detail-value fs-14 d-flex align-items-center gap-1.5 flex-wrap">
                  <span>{{ detailReg.mentor_name || user.name }}</span>
                  <a
                    :href="getWaChatUrl(detailReg.mentor_wa || user.phone, detailReg)"
                    target="_blank"
                    class="badge bg-success-subtle text-success text-decoration-none px-2 py-0.5 rounded-pill fs-10.5 d-inline-flex align-items-center gap-1"
                    title="Hubungi via WhatsApp"
                  >
                    <i class="ri-whatsapp-line"></i>
                    <span>{{ detailReg.mentor_wa || user.phone }}</span>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2B: Petugas Koordinator / PIC Khusus Cabang Lomba -->
          <div class="p-3 rounded-3 detail-section-card">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="detail-label mb-0">Petugas / Koordinator Khusus Cabang Ini (PIC)</span>
              <button
                v-if="!isCurrentUserPicOnly"
                type="button"
                class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5 fs-11 fw-bold d-inline-flex align-items-center gap-1"
                @click="openAssignPicModal(detailReg); isDetailModalOpen = false;"
              >
                <i class="ri-user-shared-line"></i>
                <span>{{ detailReg.assigned_name ? 'Ubah Penugasan' : 'Tunjuk PIC/Koordinator' }}</span>
              </button>
            </div>

            <div v-if="detailReg.assigned_name" class="row g-2 align-items-center">
              <div class="col-sm-6">
                <div class="detail-label-sub">Nama Petugas / Yang Ditunjuk</div>
                <div class="fw-bold detail-value fs-13.5 d-flex align-items-center gap-1.5 flex-wrap">
                  <span>{{ detailReg.assigned_name }}</span>
                  <span class="badge badge-assigned-pic rounded-pill px-2 py-0.5 fs-10.5 fw-bold">
                    {{ detailReg.assigned_role || 'Koordinator Cabang' }}
                  </span>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="detail-label-sub">Nomor WhatsApp & Akses OTP</div>
                <div class="fw-bold detail-value fs-13 d-flex align-items-center gap-1.5">
                  <a
                    v-if="detailReg.assigned_phone"
                    :href="`https://wa.me/${detailReg.assigned_phone.replace(/\D/g, '')}`"
                    target="_blank"
                    class="badge bg-success-subtle text-success text-decoration-none px-2.5 py-1 rounded-pill fs-11 d-inline-flex align-items-center gap-1"
                  >
                    <i class="ri-whatsapp-line"></i>
                    <span>{{ detailReg.assigned_phone }}</span>
                  </a>
                  <span v-else class="text-muted">-</span>
                </div>
              </div>
              <div v-if="detailReg.assigned_notes" class="col-12 mt-1">
                <div class="detail-label-sub">Catatan / Instruksi Khusus:</div>
                <div class="fs-12 fst-italic text-muted">{{ detailReg.assigned_notes }}</div>
              </div>
            </div>
            <div v-else class="fs-12 text-muted fst-italic">
              Belum ada koordinator khusus yang ditunjuk untuk cabang lomba ini. Pengelolaan berkas dilakukan penuh oleh akun utama sekolah.
            </div>
          </div>

          <!-- Section 3: Delegasi Peserta -->
          <div class="p-3 rounded-3 detail-section-card">
            <span class="detail-label d-block mb-2">Nama Siswa Peserta / Anggota Tim</span>
            <div class="d-flex flex-column gap-1.5">
              <div
                v-for="(name, nIdx) in parseParticipants(detailReg.participants_names)"
                :key="nIdx"
                class="p-2 px-3 rounded-2 detail-participant-item d-flex align-items-center gap-2 fs-12.5"
              >
                <i class="ri-user-star-line text-primary"></i>
                <span class="fw-semibold">{{ name }}</span>
              </div>
              <div v-if="!detailReg.participants_names" class="text-muted fst-italic fs-12">
                Belum ada nama peserta tercantum.
              </div>
            </div>
          </div>

          <!-- Section 4: Administrasi Keuangan & Bukti Bayar -->
          <div class="p-3 rounded-3 detail-section-card">
            <span class="detail-label d-block mb-2">Rincian Administrasi & Verifikasi Pembayaran</span>
            <div class="row g-3 align-items-center">
              <div class="col-sm-6">
                <div class="detail-label-sub">Total Biaya Pendaftaran</div>
                <div v-if="detailReg.package_type === 'Paket All-In'">
                  <div class="fs-16 fw-black text-success-highlight">
                    {{ detailReg.total_fee > 0 ? formatRupiah(detailReg.total_fee) : 'Tercakup Paket All-In' }}
                  </div>
                  <div class="fs-11 modal-desc-text mt-0.5">
                    Skema: <strong>Paket All-In Komplit</strong>
                    <span v-if="detailReg.total_fee > 0" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-9 px-1.5 py-0.2 ms-1">Induk Tagihan</span>
                    <span v-else class="text-success ms-1 fw-semibold">(Bebas Biaya Mandiri)</span>
                  </div>
                </div>
                <div v-else>
                  <div class="fs-16 fw-black text-success-highlight">{{ formatRupiah(detailReg.total_fee) }}</div>
                  <div class="fs-11 modal-desc-text mt-0.5">
                    Skema: <strong>Paket Satuan Mandiri</strong>
                  </div>
                </div>
                <div class="fs-11 modal-desc-text mt-0.5">
                  Saluran: <strong class="detail-value">{{ detailReg.payment_method }}</strong>
                </div>
                <div class="fs-11 modal-desc-text mt-0.5">
                  Rek. Tujuan: <strong>BSI 7123456789 (Semarak Aliyah)</strong>
                </div>
              </div>

              <div class="col-sm-6">
                <div class="detail-label-sub">Status Pembayaran</div>
                <div class="mt-1 mb-2">
                  <span
                    v-if="detailReg.payment_status === 'Terverifikasi' || detailReg.payment_status === 'Lunas'"
                    class="badge badge-status-verified rounded-pill px-2.5 py-1 fs-11 fw-bold"
                  >
                    <i class="ri-checkbox-circle-fill me-1"></i>
                    {{ detailReg.package_type === 'Paket All-In' ? 'Lunas (Paket All-In Terverifikasi)' : 'Lunas Terverifikasi' }}
                  </span>
                  <span
                    v-else-if="detailReg.payment_status === 'Ditolak'"
                    class="badge badge-status-rejected rounded-pill px-2.5 py-1 fs-11 fw-bold"
                  >
                    <i class="ri-close-circle-fill me-1"></i> Bukti Perlu Perbaikan
                  </span>
                  <span
                    v-else-if="detailReg.package_type === 'Paket All-In' && (detailReg.is_all_in_included || detailReg.total_fee === 0)"
                    class="badge badge-status-allin-included rounded-pill px-2.5 py-1 fs-11 fw-bold"
                  >
                    <i class="ri-shield-check-line me-1"></i> Tercakup Paket All-In
                  </span>
                  <span
                    v-else
                    class="badge badge-status-pending rounded-pill px-2.5 py-1 fs-11 fw-bold"
                  >
                    <i class="ri-error-warning-fill me-1"></i> Menunggu Verifikasi
                  </span>
                </div>

                <!-- Bukti Pembayaran preview / link -->
                <div v-if="detailReg.payment_proof_url" class="d-flex align-items-center gap-2">
                  <a :href="detailReg.payment_proof_url" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fs-11.5 fw-bold d-inline-flex align-items-center gap-1">
                    <i class="ri-image-line"></i>
                    <span>{{ detailReg.package_type === 'Paket All-In' ? 'Buka Bukti Transfer Paket All-In' : 'Buka Gambar Bukti Transfer' }}</span>
                  </a>
                </div>
                <div v-else class="fs-11 text-warning-highlight fw-medium">
                  <i class="ri-alert-line"></i> Bukti transfer belum diunggah.
                </div>
              </div>
            </div>
          </div>

          <!-- Section 5: Dokumen Berkas Persyaratan Terlampir (jika ada) -->
          <div v-if="detailReg.file_documents && detailReg.file_documents.length > 0" class="p-3 rounded-3 detail-section-card">
            <span class="detail-label d-block mb-2">Dokumen & Berkas Terlampir</span>
            <div class="d-flex flex-column gap-1.5">
              <div
                v-for="(doc, dIdx) in detailReg.file_documents"
                :key="dIdx"
                class="p-2 px-3 rounded-2 detail-participant-item d-flex align-items-center justify-content-between fs-12"
              >
                <div class="d-flex align-items-center gap-2 text-truncate">
                  <i class="ri-file-text-line text-primary"></i>
                  <span class="fw-medium text-truncate">{{ doc.title || doc.filename }}</span>
                </div>
                <a :href="doc.url" target="_blank" class="btn btn-sm btn-outline-info border-0 p-1 fs-12">
                  <i class="ri-external-link-line"></i> Buka File
                </a>
              </div>
            </div>
          </div>

          <!-- Section 6: Catatan Tambahan bila ada -->
          <div v-if="detailReg.notes" class="p-3 rounded-3 detail-section-card">
            <span class="detail-label d-block mb-1">Catatan Khusus Pendaftar</span>
            <div class="fs-12 modal-desc-text fst-italic">{{ detailReg.notes }}</div>
          </div>

          <!-- Footer Metadata Sesuai Rule 4 (dd/mm/yyyy & 24 jam) -->
          <div class="d-flex align-items-center justify-content-between pt-1 fs-11 modal-desc-text">
            <span><i class="ri-time-line me-1"></i> Waktu Pendaftaran: {{ formatIndoDate(detailReg.created_at) }} WIB</span>
            <span>Kode Barcode: <strong class="font-monospace">{{ detailReg.barcode || detailReg.reg_code }}</strong></span>
          </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-3 px-md-4 modal-footer-box d-flex align-items-center justify-content-between gap-2">
          <button
            type="button"
            class="btn modal-btn-cancel btn-sm px-3 py-2 fs-12 fw-semibold"
            @click="isDetailModalOpen = false"
          >
            Tutup
          </button>

          <div class="d-flex align-items-center gap-2">
            <button
              v-if="detailReg.can_edit"
              type="button"
              class="btn btn-outline-primary btn-sm px-3 py-2 fs-12 fw-bold d-flex align-items-center gap-1.5"
              @click="openEditModal(detailReg); isDetailModalOpen = false;"
            >
              <i class="ri-file-edit-line"></i>
              <span>Perbarui Berkas</span>
            </button>

            <a
              :href="'/pendaftar/invoice/' + detailReg.id"
              target="_blank"
              class="btn btn-outline-info btn-sm px-3 py-2 fs-12 fw-bold d-flex align-items-center gap-1.5 text-decoration-none"
              title="Cetak Nota Invoice / Kwitansi Pembayaran Resmi (A4)"
            >
              <i class="ri-file-paper-2-line"></i>
              <span>Cetak Nota / Kwitansi</span>
            </a>

            <button
              type="button"
              class="btn btn-primary btn-sm px-3 py-2 fs-12 fw-bold shadow-sm d-flex align-items-center gap-1.5"
              @click="openPrintModal(detailReg); isDetailModalOpen = false;"
            >
              <i class="ri-printer-line"></i>
              <span>Cetak Bukti Pendaftaran (A4)</span>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: PRATINJAU CETAK BUKTI RESMI (Standar Dokumen A4 Resmi Madrasah) -->
    <!-- ========================================================================= -->
    <div v-if="isPrintModalOpen && printReg" class="modal-backdrop-custom is-printing-modal d-flex align-items-center justify-content-center p-2 p-md-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-2xl overflow-hidden d-flex flex-column" style="max-width: 860px; max-height: 94vh;">
        
        <!-- Header Preview Bar (No-Print) -->
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between no-print">
          <div class="d-flex align-items-center gap-2 overflow-hidden">
            <i class="ri-printer-fill fs-18 text-primary flex-shrink-0"></i>
            <div class="text-truncate">
              <h5 class="fs-14 fw-bold modal-title-text mb-0 text-truncate">Pratinjau Lembar Bukti Registrasi Resmi</h5>
              <div class="fs-10.5 modal-desc-text text-truncate">Standar Dokumen A4 Resmi Panitia SEMARAK ALIYAH XIV 2026</div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Theme Toggle for Print Preview (Paper vs Dark Screen) -->
            <button
              type="button"
              id="btn-print-theme-toggle"
              class="btn btn-sm print-theme-mode-btn rounded-pill px-2.5 py-1 fs-11.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm"
              @click="togglePrintPreviewTheme"
              :title="printPreviewTheme === 'dark' ? 'Beralih ke Pratinjau Kertas Putih Fisik' : 'Beralih ke Pratinjau Layar Gelap (Dark Mode)'"
            >
              <i :class="printPreviewTheme === 'dark' ? 'ri-sun-fill text-warning' : 'ri-moon-clear-fill text-primary'"></i>
              <span>{{ printPreviewTheme === 'dark' ? 'Mode Kertas Putih' : 'Mode Layar Gelap' }}</span>
            </button>

            <!-- Link Cetak Invoice / Kwitansi Resmi -->
            <a
              :href="'/pendaftar/invoice/' + printReg.id"
              target="_blank"
              class="btn btn-outline-info btn-sm px-2.5 py-1 rounded-pill fs-11.5 fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm text-decoration-none"
              title="Cetak Nota Invoice / Kwitansi Pembayaran Resmi (A4)"
            >
              <i class="ri-file-paper-2-line"></i>
              <span>Nota / Kwitansi</span>
            </a>

            <!-- Quick Print Button -->
            <button
              type="button"
              id="btn-print-action"
              class="btn btn-primary btn-sm px-3 py-1 rounded-pill fs-11.5 fw-bold d-flex align-items-center gap-1.5 shadow"
              @click="printOfficialDocument"
              title="Cetak Dokumen Resmi Sekarang"
            >
              <i class="ri-printer-line fs-13"></i>
              <span>Cetak (Ctrl+P)</span>
            </button>

            <!-- Close Button -->
            <button
              type="button"
              id="btn-print-close"
              class="btn modal-btn-cancel btn-sm px-2.5 py-1 rounded-pill fs-11.5 fw-semibold"
              @click="isPrintModalOpen = false"
            >
              Tutup
            </button>
          </div>
        </div>

        <!-- Scrollable Document Desk View (No-Print Background Wrapper) -->
        <div class="p-3 p-md-4 overflow-y-auto flex-grow-1 print-desk-preview-wrapper d-flex justify-content-center">
          
          <!-- ================================================================ -->
          <!-- OFFICIAL PRINT SHEET (MANDATORY PRINTABLE AREA - A4 DOKUMEN)     -->
          <!-- ================================================================ -->
          <div :class="['official-print-sheet shadow-lg', printPreviewTheme === 'dark' ? 'theme-dark' : 'theme-paper']">
            
            <!-- OUTER FORMAL FRAME BORDER -->
            <div class="print-inner-frame">

              <!-- 1. KOP SURAT RESMI MADRASAH & PANITIA -->
              <div class="print-kop-wrapper d-flex align-items-center justify-content-between pb-2">
                <img src="/assets/img/logo.png" alt="Logo MAWH" class="print-kop-logo" />

                <div class="text-center flex-grow-1 px-2">
                  <div class="print-kop-foundation">YAYASAN PONDOK PESANTREN WAHID HASYIM</div>
                  <div class="print-kop-committee">PANITIA PELAKSANA SEMARAK ALIYAH XIV TAHUN 2026</div>
                  <div class="print-kop-school">MADRASAH ALIYAH UNGGULAN WAHID HASYIM BALUNG</div>
                  <div class="print-kop-accreditation">STATUS TERAKREDITASI "A" • NSM: 131235090014 • NPSN: 69941846</div>
                  <div class="print-kop-address">Jl. Balung - Tutul No. 09 Balung Kidul, Kec. Balung, Kab. Jember, Jawa Timur 68161</div>
                  <div class="print-kop-contact">Website: semarak.ma-wh.sch.id • Email: semarak@ma-wh.sch.id • Hotline WA: 0838 5007 0767</div>
                </div>

                <img src="/assets/img/logo.png" alt="Logo Semarak" class="print-kop-logo" />
              </div>

              <!-- Double Line Divider KOP Surat -->
              <div class="print-double-divider"></div>

              <!-- 2. JUDUL DOKUMEN RESMI -->
              <div class="text-center my-2">
                <h5 class="print-document-title">
                  SURAT TANDA BUKTI PENDAFTARAN & KARTU KONTINGEN RESMI
                </h5>
                <div class="print-document-number">
                  Nomor Dokumen: SA-XIV/REG/2026/{{ printReg.reg_code }}
                </div>
              </div>

              <!-- 3. BARCODE SECURITY & SYSTEM SEAL -->
              <div class="print-security-strip d-flex align-items-center justify-content-between p-2 px-3 my-2.5 rounded">
                <div class="d-flex align-items-center gap-3">
                  <!-- Barcode Simulation Lines -->
                  <div class="print-barcode-box p-1 rounded border">
                    <div class="d-flex align-items-center gap-0.5 h-100" style="height: 28px;">
                      <span v-for="n in 34" :key="n" :class="['h-100 print-bar-line', n % 4 === 0 ? 'print-bar-thick' : (n % 2 === 0 ? 'print-bar-med' : 'print-bar-thin')]"></span>
                    </div>
                    <div class="font-monospace text-center fw-bold fs-9 tracking-wider mt-0.5 print-barcode-label">
                      *{{ printReg.reg_code }}*
                    </div>
                  </div>

                  <div>
                    <div class="fs-10 print-label-sub text-uppercase fw-bold">Kode Registrasi Resmi</div>
                    <div class="font-monospace fw-black fs-14 print-code-val">{{ printReg.reg_code }}</div>
                    <div class="fs-9 print-date-sub">Tanggal: {{ formatIndoDate(printReg.created_at) }} WIB</div>
                  </div>
                </div>

                <div class="text-end">
                  <span class="print-system-tag">
                    <i class="ri-shield-check-fill me-0.5"></i> SISTEM ELEKTRONIK RESMI
                  </span>
                  <div class="fs-9 text-muted mt-0.5">Tercatat dalam Database Panitia</div>
                </div>
              </div>

              <!-- 4. TABEL FORMAL DATA PENDAFTARAN -->
              <table class="table table-sm table-bordered print-formal-table mb-2.5 align-middle">
                <tbody>
                  <!-- BAGIAN I: DATA LEMBAGA -->
                  <tr class="print-table-header-row">
                    <th colspan="2">I. DATA LEMBAGA & OFFICIAL KONTINGEN</th>
                  </tr>
                  <tr>
                    <td class="print-col-label">Asal Lembaga / Sekolah</td>
                    <td class="print-col-value fw-bold fs-12">{{ printReg.school_name }}</td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Guru Pendamping / Official</td>
                    <td class="print-col-value">{{ printReg.mentor_name || user.name }}</td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Nomor WhatsApp Official</td>
                    <td class="print-col-value font-monospace">{{ printReg.mentor_wa || user.phone }}</td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Skema Pendaftaran</td>
                    <td class="print-col-value fw-semibold">{{ printReg.package_type || 'Paket Satuan' }}</td>
                  </tr>
                  <tr v-if="printReg.assigned_name">
                    <td class="print-col-label">Petugas / Koordinator Khusus</td>
                    <td class="print-col-value font-monospace">{{ printReg.assigned_name }} ({{ printReg.assigned_role || 'Koordinator Cabang' }} - WA: {{ printReg.assigned_phone }})</td>
                  </tr>

                  <!-- BAGIAN II: DATA LOMBA & PESERTA -->
                  <tr class="print-table-header-row">
                    <th colspan="2">II. CABANG LOMBA & DELEGASI PESERTA</th>
                  </tr>
                  <tr>
                    <td class="print-col-label">Cabang Perlombaan</td>
                    <td class="print-col-value fw-bold fs-12 text-uppercase">{{ printReg.competition_title }}</td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Kategori Sasaran</td>
                    <td class="print-col-value">{{ printReg.comp_category || 'Tingkat SMP / MTs Sederajat Se-Jawa Timur' }}</td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Nomor Undian Technical Meeting</td>
                    <td class="print-col-value fw-bold">
                      <span v-if="printReg.draw_number" class="fs-12 font-monospace">Nomor Urut: #{{ printReg.draw_number }}</span>
                      <span v-else class="fst-italic text-muted fs-10.5">Belum Terbit (Diundi pada sesi Technical Meeting)</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="print-col-label align-top pt-1.5">Nama Siswa / Anggota Delegasi</td>
                    <td class="print-col-value">
                      <ol class="mb-0 ps-3 print-participant-list">
                        <li v-for="(pName, pIdx) in parseParticipants(printReg.participants_names)" :key="pIdx" class="fw-semibold">
                          {{ pName }}
                        </li>
                      </ol>
                    </td>
                  </tr>

                  <!-- BAGIAN III: ADMINISTRASI & PEMBAYARAN -->
                  <tr class="print-table-header-row">
                    <th colspan="2">III. ADMINISTRASI KEUANGAN & STATUS VERIFIKASI</th>
                  </tr>
                  <tr>
                    <td class="print-col-label">Total Biaya Pendaftaran</td>
                    <td class="print-col-value fw-black fs-12">
                      <span v-if="printReg.package_type === 'Paket All-In'">
                        {{ formatRupiah(printReg.total_fee > 0 ? printReg.total_fee : (allInPricing?.price || 200000)) }}
                        <span class="fs-10 fw-normal text-muted">(Paket All-In Komplit - Terpadu Seluruh Cabang)</span>
                      </span>
                      <span v-else>
                        {{ formatRupiah(printReg.total_fee) }}
                      </span>
                    </td>
                  </tr>
                  <tr v-if="printReg.package_type === 'Paket All-In'">
                    <td class="print-col-label">Skema Pendaftaran</td>
                    <td class="print-col-value fs-10.5">
                      <strong>Paket All-In Komplit</strong> (Satu tagihan terpadu kontingen yang mencakup seluruh cabang lomba resmi).
                    </td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Saluran Pembayaran</td>
                    <td class="print-col-value">{{ printReg.payment_method }} (Rek. Bank BSI 7123456789 a.n Semarak Aliyah)</td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Status Pembayaran</td>
                    <td class="print-col-value">
                      <span class="print-status-stamp print-status-success" v-if="printReg.payment_status === 'Terverifikasi' || printReg.payment_status === 'Lunas'">
                        {{ printReg.package_type === 'Paket All-In' ? 'LUNAS TERVERIFIKASI (PAKET ALL-IN)' : 'LUNAS TERVERIFIKASI' }}
                      </span>
                      <span class="print-status-stamp print-status-warning" v-else>
                        {{ printReg.package_type === 'Paket All-In' ? 'MENUNGGU VERIFIKASI PEMBAYARAN (PAKET ALL-IN)' : 'MENUNGGU VERIFIKASI PEMBAYARAN' }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td class="print-col-label">Status Verifikasi Berkas</td>
                    <td class="print-col-value">
                      <span class="print-status-stamp print-status-success" v-if="printReg.registration_status === 'Terverifikasi'">
                        BERKAS LENGKAP & TERVERIFIKASI
                      </span>
                      <span class="print-status-stamp print-status-danger" v-else-if="printReg.registration_status === 'Ditolak'">
                        PERLU PERBAIKAN BERKAS
                      </span>
                      <span class="print-status-stamp print-status-warning" v-else>
                        DALAM PROSES PEMERIKSAAN PANITIA
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- 5. KETENTUAN DAN TATA TERTIB HARI-H -->
              <div class="print-rules-box p-2 px-3 rounded mb-2.5">
                <div class="print-rules-title">PETUNJUK & TATA TERTIB RESMI HARI-H:</div>
                <ol class="mb-0 ps-3 print-rules-list">
                  <li>Lembar tanda bukti pendaftaran ini wajib dicetak dan dibawa oleh guru pendamping / peserta saat Technical Meeting (TM) dan Registrasi Ulang Hari-H di MA Unggulan Wahid Hasyim Balung.</li>
                  <li>Setiap peserta delegasi wajib membawa Kartu Tanda Siswa (KTS) asli / Surat Rekomendasi Kepala Sekolah untuk validasi fisik sebelum memasuki ruangan perlombaan.</li>
                  <li>Peserta wajib hadir di lokasi 30 menit sebelum acara pembukaan / perlombaan dimulai untuk proses pemeriksaan berkas dan pengambilan ID Card resmi kontingen.</li>
                  <li>Dokumen ini diterbitkan secara elektronik dan sah tanpa tanda tangan basah tambahan selama nomor barcode pendaftaran valid tercatat di sistem panitia.</li>
                </ol>
              </div>

              <!-- 6. LEGALITAS TANDA TANGAN & STEMPEL RESMI -->
              <div class="print-signatures-wrapper pt-1">
                <div class="d-flex justify-content-end mb-1">
                  <div class="print-date-location">
                    Balung, Jember, {{ formatIndoDateOnly(printReg.created_at) }}
                  </div>
                </div>

                <div class="row text-center position-relative">
                  <!-- Kolom Kiri: Ketua Panitia -->
                  <div class="col-6">
                    <div class="print-sig-role">Mengetahui,</div>
                    <div class="print-sig-title">Ketua Panitia Pelaksana SEMARAK ALIYAH XIV</div>
                    <div class="print-sig-space"></div>
                    <div class="print-sig-name">H. Mohammad Zaini, M.Pd</div>
                    <div class="print-sig-nip">NIP. 198504122010011008</div>
                  </div>

                  <!-- Kolom Kanan: Koordinator Kesekretariatan -->
                  <div class="col-6">
                    <div class="print-sig-role">Petugas Administrasi & Pendaftaran,</div>
                    <div class="print-sig-title">Sekretariat Panitia Semarak Aliyah XIV</div>
                    <div class="print-sig-space"></div>
                    <div class="print-sig-name">M. Fikri Nuril, S.Kom</div>
                    <div class="print-sig-nip">Koordinator Verifikasi Berkas</div>
                  </div>

                  <!-- Stempel Digital Panitia Resmi (Lingkaran Ungu-Biru Melingkar) -->
                  <div class="print-stamp-seal-badge">
                    <div class="stamp-outer-ring">
                      <div class="stamp-inner-ring">
                        <div class="stamp-arc-top">PANITIA SEMARAK ALIYAH XIV</div>
                        <i class="ri-star-fill stamp-star"></i>
                        <div class="stamp-arc-bottom">MA UNGGULAN WAHID HASYIM</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 7. FOOTER KEASLIAN SISTEM -->
              <div class="print-system-footer text-center pt-2 mt-2 border-top">
                <span>Dokumen ini diterbitkan secara resmi melalui Portal Pendaftar Semarak Aliyah XIV (semarak.ma-wh.sch.id). Dicetak pada: {{ formatIndoDate(new Date()) }} WIB.</span>
              </div>

            </div>
          </div>

        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: PENGATURAN PROFIL PENDAFTAR                                      -->
    <!-- ========================================================================= -->
    <div v-if="isProfileModalOpen" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-lg overflow-hidden" style="max-width: 520px;">
        
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between">
          <h5 class="fs-16 fw-bold modal-title-text mb-0">Pengaturan Profil Pendaftar</h5>
          <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" @click="isProfileModalOpen = false"></button>
        </div>

        <div class="p-3 px-md-4 modal-body-box">
          <form @submit.prevent="submitProfileUpdate" class="d-flex flex-column gap-3">
            
            <!-- Avatar Profil Foto Pendaftar -->
            <div class="p-3 rounded-3 d-flex align-items-center gap-3" :style="{ backgroundColor: isDark ? 'rgba(255,255,255,0.05)' : '#f8fafc', border: isDark ? '1px solid rgba(255,255,255,0.1)' : '1px solid #e2e8f0' }">
              <div class="position-relative flex-shrink-0">
                <img 
                  v-if="user.avatar" 
                  :src="user.avatar" 
                  alt="Avatar" 
                  class="rounded-circle object-fit-cover shadow-sm border" 
                  style="width: 58px; height: 58px;"
                />
                <div 
                  v-else 
                  class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-18 shadow-sm" 
                  style="width: 58px; height: 58px;"
                >
                  {{ user.name ? user.name.charAt(0).toUpperCase() : 'P' }}
                </div>
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold fs-13 mb-0.5" :style="{ color: isDark ? '#f8fafc' : '#0f172a' }">Foto Profil Akun</div>
                <p class="fs-11 text-muted mb-2">Sesuaikan perbesaran & geser posisi foto Anda secara mandiri.</p>
                <div class="d-flex flex-wrap gap-1.5">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 py-1 px-2.5 fs-12"
                    @click="openPendaftarAvatarCropper"
                  >
                    <i class="ri-crop-2-line"></i>
                    <span>{{ user.avatar ? 'Sesuaikan / Ganti' : 'Unggah & Sesuaikan' }}</span>
                  </button>
                  <button 
                    v-if="user.avatar" 
                    type="button" 
                    class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 py-1 px-2.5 fs-12"
                    @click="handleDeletePendaftarAvatar"
                  >
                    <i class="ri-delete-bin-line"></i>
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            </div>

            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">Nama Lengkap</label>
              <input v-model="profileForm.name" type="text" class="form-control modal-input fs-13 py-2" required />
            </div>

            <div class="row g-2">
              <div class="col-7">
                <label class="form-label fs-13 fw-bold modal-label-text mb-1">Sebagai Pendaftar</label>
                <!-- Rule 1: CustomSelect -->
                <CustomSelect
                  v-model="profileForm.registrant_role"
                  :options="roleOptions"
                  size="md"
                  placeholder="Pilih status..."
                />
              </div>
              <div class="col-5">
                <label class="form-label fs-13 fw-bold modal-label-text mb-1">Jenis Kelamin</label>
                <!-- Rule 1: CustomSelect -->
                <CustomSelect
                  v-model="profileForm.gender"
                  :options="genderOptions"
                  size="md"
                  placeholder="Pilih gender..."
                />
              </div>
            </div>

            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">Asal Sekolah</label>
              <input v-model="profileForm.school_name" type="text" class="form-control modal-input fs-13 py-2" required />
            </div>

            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">Kecamatan Sekolah</label>
              <input v-model="profileForm.school_district" type="text" class="form-control modal-input fs-13 py-2" required />
            </div>

            <div class="d-flex justify-content-end gap-2 pt-2">
              <button type="button" class="btn modal-btn-cancel btn-sm px-3 fw-semibold" @click="isProfileModalOpen = false">Batal</button>
              <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold" :disabled="profileForm.processing">
                {{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil' }}
              </button>
            </div>

          </form>
        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 5: PENENTUAN SKEMA PENDAFTARAN / PAKET LOMBA                       -->
    <!-- ========================================================================= -->
    <div v-if="isPackageModalOpen" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-2xl overflow-hidden d-flex flex-column" style="max-width: 820px; max-height: 92vh;">
        
        <!-- Modal Header -->
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-20 flex-shrink-0" style="width: 38px; height: 38px;">
              <i class="ri-award-fill"></i>
            </div>
            <div>
              <h5 class="fs-16 fw-black modal-title-text mb-0">Penentuan Skema Pendaftaran Kontingen</h5>
              <div class="fs-11.5 modal-desc-text">Pilih metode pendaftaran yang paling praktis & hemat untuk sekolah Anda</div>
            </div>
          </div>
          <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" @click="isPackageModalOpen = false"></button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-3 px-md-4 overflow-y-auto flex-grow-1 modal-body-box d-flex flex-column gap-3.5 fs-13">
          
          <!-- Panduan Pendaftar -->
          <div class="p-3 rounded-3 modal-alert-info d-flex gap-2.5">
            <i class="ri-lightbulb-flash-line fs-20 text-info flex-shrink-0 mt-0.5"></i>
            <div class="fs-12 modal-alert-text">
              <strong>Cara Kerja Skema Pendaftaran:</strong>
              Pilih <strong>Paket Satuan</strong> jika sekolah hanya mengikuti segelintir cabang lomba, atau aktifkan <strong>Paket All-In Komplit</strong> agar seluruh {{ packageDetails.activeCount }} cabang lomba langsung masuk otomatis ke tabel pendaftaran Anda dengan 1 biaya hemat terpadu.
            </div>
          </div>

          <!-- Cards Komparasi Paket -->
          <div class="row g-3">
            
            <!-- OPSI 1: PAKET SATUAN -->
            <div class="col-md-6">
              <div class="package-plan-card h-100 p-3.5 rounded-4 d-flex flex-column justify-content-between" :class="!isAllInActive ? 'package-plan-selected-satuan' : ''">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-2.5 py-1 fs-11 fw-bold">
                      Opsi Pilihan Fleksibel
                    </span>
                    <span v-if="!isAllInActive" class="badge bg-primary text-white rounded-pill px-2 py-0.5 fs-10 fw-bold">
                      <i class="ri-check-line me-0.5"></i> Mode Berjalan
                    </span>
                  </div>

                  <h4 class="fs-16 fw-black mb-1 package-plan-title">
                    Paket Satuan (Per Cabang)
                  </h4>
                  <div class="fs-12 text-muted mb-3">
                    Daftarkan delegasi lomba satu per satu sesuai minat cabang tertentu.
                  </div>

                  <div class="p-2.5 rounded-3 mb-3 package-price-box">
                    <div class="fs-11 text-muted">Biaya Pendaftaran:</div>
                    <div class="fs-18 fw-black text-heading">Tarif Normal / Lomba</div>
                    <div class="fs-11 text-muted mt-0.5">Mulai Rp 25.000 s/d Rp 50.000 per cabang</div>
                  </div>

                  <!-- Poin-poin Karakteristik -->
                  <div class="d-flex flex-column gap-2 fs-12 mb-3">
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-checkbox-circle-line text-success fs-15 flex-shrink-0 mt-0.5"></i>
                      <span>Cocok jika sekolah hanya berminat mengirim delegasi di 1 atau 2 cabang lomba spesifik.</span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-checkbox-circle-line text-success fs-15 flex-shrink-0 mt-0.5"></i>
                      <span>Penambahan cabang dilakukan bertahap lewat menu <strong>"Daftar Lomba Baru"</strong>.</span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-information-line text-muted fs-15 flex-shrink-0 mt-0.5"></i>
                      <span>Pembayaran transfer dan bukti verifikasi diunggah untuk masing-masing cabang lomba.</span>
                    </div>
                  </div>
                </div>

                <div class="pt-2 border-top">
                  <div v-if="!isAllInActive" class="text-center py-1 fs-12 fw-bold text-success">
                    <i class="ri-checkbox-circle-fill me-1"></i> Saat ini Anda menggunakan pendaftaran satuan
                  </div>
                  <div v-else class="text-center py-1 fs-11 text-muted">
                    Anda sudah mengaktifkan Paket All-In Komplit
                  </div>
                </div>
              </div>
            </div>

            <!-- OPSI 2: PAKET ALL-IN KOMPLIT -->
            <div class="col-md-6">
              <div class="package-plan-card package-plan-allin h-100 p-3.5 rounded-4 d-flex flex-column justify-content-between position-relative overflow-hidden" :class="isAllInActive ? 'package-plan-selected-allin' : ''">
                
                <!-- Ribbon Rekomendasi -->
                <div class="package-plan-ribbon">
                  HEMAT & PRAKTIS
                </div>

                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge badge-all-in rounded-pill px-2.5 py-1 fs-11 fw-bold">
                      <i class="ri-award-fill me-1"></i> Rekomendasi Juara Umum
                    </span>
                    <span v-if="isAllInActive" class="badge bg-success text-white rounded-pill px-2.5 py-0.5 fs-10 fw-bold">
                      <i class="ri-check-double-line me-0.5"></i> Telah Aktif
                    </span>
                  </div>

                  <h4 class="fs-16 fw-black mb-1 package-plan-title text-primary-highlight">
                    Paket All-In Komplit
                  </h4>
                  <div class="fs-12 text-muted mb-3">
                    Semua cabang lomba langsung aktif otomatis ke tabel delegasi Anda!
                  </div>

                  <!-- Price Highlight -->
                  <div class="p-2.5 rounded-3 mb-3 package-price-box package-price-allin">
                    <div class="d-flex align-items-baseline justify-content-between">
                      <div class="fs-11 text-muted">Biaya Paket Total:</div>
                      <del class="fs-11.5 text-danger fw-bold" v-if="packageDetails.originalPrice > packageDetails.price">
                        {{ formatRupiah(packageDetails.originalPrice) }}
                      </del>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                      <div class="fs-22 fw-black text-success-highlight">{{ formatRupiah(packageDetails.price) }}</div>
                      <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 fs-10 fw-bold">
                        Hemat {{ formatRupiah(packageDetails.savings) }}
                      </span>
                    </div>
                    <div class="fs-11 text-muted mt-0.5">Mencakup seluruh {{ packageDetails.activeCount }} cabang lomba tanpa biaya tambahan</div>
                  </div>

                  <!-- Benefit Otomatis -->
                  <div class="d-flex flex-column gap-2 fs-12 mb-3">
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-magic-line text-warning fs-15 flex-shrink-0 mt-0.5"></i>
                      <span><strong>Otomatis ke Tabel:</strong> Semua {{ packageDetails.activeCount }} cabang lomba langsung masuk ke tabel <em>"Daftar Partisipasi Lomba Delegasi"</em> Anda.</span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-checkbox-circle-fill text-success fs-15 flex-shrink-0 mt-0.5"></i>
                      <span><strong>Tanpa Klik Berulang:</strong> Anda tinggal melengkapi nama siswa delegasi & berkas pada cabang yang diikuti.</span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-checkbox-circle-fill text-success fs-15 flex-shrink-0 mt-0.5"></i>
                      <span><strong>1 Bukti Transfer:</strong> Cukup transfer 1 kali ke rekening panitia untuk seluruh delegasi sekolah.</span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                      <i class="ri-trophy-line text-warning fs-15 flex-shrink-0 mt-0.5"></i>
                      <span><strong>Peluang Juara Umum:</strong> Memenuhi syarat maksimal poin penilaian Juara Umum Bergilir Semarak Aliyah XIV.</span>
                    </div>
                  </div>
                </div>

                <!-- Activation Action -->
                <div class="pt-2 border-top">
                  <div v-if="isAllInActive" class="text-center py-2">
                    <span class="badge bg-success text-white rounded-pill px-3 py-1.5 fs-12 fw-bold d-inline-flex align-items-center gap-1">
                      <i class="ri-checkbox-circle-fill"></i> Paket All-In Komplit Aktif di Akun Anda
                    </span>
                    <div class="fs-11 text-muted mt-1">
                      Seluruh cabang lomba telah tersedia di tabel pendaftaran Anda.
                    </div>
                  </div>
                  <button
                    v-else
                    type="button"
                    class="btn btn-success w-100 py-2.5 rounded-pill fs-13 fw-bold d-flex align-items-center justify-content-center gap-2 shadow"
                    :disabled="isActivatingAllIn"
                    @click="activateAllIn"
                  >
                    <span v-if="isActivatingAllIn" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ri-flashlight-fill"></i>
                    <span>{{ isActivatingAllIn ? 'Mengaktifkan Semua Cabang...' : `Aktifkan Paket All-In (${formatRupiah(packageDetails.price)})` }}</span>
                  </button>
                </div>

              </div>
            </div>

          </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-3 px-md-4 modal-footer-box d-flex align-items-center justify-content-between">
          <div class="fs-11.5 modal-desc-text">
            Ada pertanyaan seputar paket? Hubungi <strong class="text-success">WhatsApp Panitia: 0838 5007 0767</strong>
          </div>
          <button type="button" class="btn modal-btn-cancel btn-sm px-3.5 py-1.5 rounded-pill fs-12 fw-semibold" @click="isPackageModalOpen = false">
            Tutup
          </button>
        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 6: PENUNJUKAN KOORDINATOR / PIC CABANG LOMBA                        -->
    <!-- ========================================================================= -->
    <div v-if="isAssignPicModalOpen && selectedRegForPic" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-2xl overflow-hidden d-flex flex-column" style="max-width: 580px; max-height: 90vh;">
        
        <!-- Modal Header -->
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fs-20 flex-shrink-0" style="width: 38px; height: 38px;">
              <i class="ri-user-shared-line"></i>
            </div>
            <div>
              <h5 class="fs-16 fw-black modal-title-text mb-0">Penunjukan Koordinator / PIC Cabang</h5>
              <div class="fs-11.5 modal-desc-text text-truncate" style="max-width: 380px;">
                Cabang: <strong class="modal-title-text">{{ selectedRegForPic.competition_title }}</strong> ({{ selectedRegForPic.reg_code }})
              </div>
            </div>
          </div>
          <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" @click="isAssignPicModalOpen = false"></button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-3 px-md-4 overflow-y-auto flex-grow-1 modal-body-box">
          <form @submit.prevent="submitAssignPic" class="d-flex flex-column gap-3 fs-13">
            
            <!-- Alert Info Edukatif -->
            <div class="p-3 rounded-3 modal-alert-info d-flex gap-2.5">
              <i class="ri-shield-keyhole-line fs-20 text-info flex-shrink-0 mt-0.5"></i>
              <div class="fs-12 modal-alert-text">
                <strong>Kolaborasi Pengisian Berkas & Login OTP WA:</strong>
                Petugas yang ditunjuk (koordinator / kapten regu / siswa) dapat langsung login ke portal pendaftar menggunakan <strong>kode OTP yang dikirim ke nomor WhatsApp-nya</strong> untuk membantu melengkapi nama peserta dan berkas cabang lomba ini.
              </div>
            </div>

            <!-- Nama Petugas -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Nama Lengkap Petugas / Anggota yang Ditunjuk <span class="text-danger">*</span>
              </label>
              <input
                v-model="picForm.assigned_name"
                type="text"
                class="form-control modal-input fs-13 py-2"
                placeholder="Contoh: Ust. Ahmad Fauzi, S.Pd atau Rian Hidayat"
                required
              />
              <div class="fs-11 modal-desc-text mt-1">
                Tulis nama lengkap petugas atau kapten tim delegasi.
              </div>
            </div>

            <!-- Nomor WhatsApp (OTP) -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Nomor WhatsApp Aktif Petugas <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <span class="input-group-text modal-input-prefix fs-13 fw-bold text-success">
                  <i class="ri-whatsapp-line me-1"></i> WA
                </span>
                <input
                  v-model="picForm.assigned_phone"
                  type="tel"
                  class="form-control modal-input fs-13 py-2"
                  placeholder="Contoh: 081234567890"
                  required
                />
              </div>
              <div class="fs-11 modal-desc-text mt-1">
                Wajib nomor WhatsApp aktif. Nomor ini akan menerima kode OTP saat petugas login ke portal.
              </div>
            </div>

            <!-- Peran Penugasan (Rule 1: CustomSelect) -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Peran Penugasan Khusus <span class="text-danger">*</span>
              </label>
              <CustomSelect
                v-model="picForm.assigned_role"
                :options="picRoleOptions"
                size="md"
                placeholder="Pilih peran penugasan..."
              />
              <div class="fs-11 modal-desc-text mt-1">
                Perjelas tanggung jawab apakah sebagai Guru Koordinator, Kapten Tim, atau Siswa Peserta Mandiri.
              </div>
            </div>

            <!-- Catatan Khusus -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Instruksi Tugas / Catatan Khusus untuk Petugas (Opsional)
              </label>
              <textarea
                v-model="picForm.assigned_notes"
                rows="2"
                class="form-control modal-input fs-13"
                placeholder="Contoh: Mohon lengkapi surat delegasi kepala sekolah dan foto 3x4 peserta sebelum hari Kamis."
              ></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
              <div>
                <button
                  v-if="selectedRegForPic.assigned_name"
                  type="button"
                  class="btn btn-outline-danger btn-sm px-3 rounded-pill fs-12 fw-semibold d-inline-flex align-items-center gap-1"
                  @click="removeAssignedPic(selectedRegForPic.id)"
                >
                  <i class="ri-delete-bin-line"></i>
                  <span>Hapus Penugasan</span>
                </button>
              </div>

              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn modal-btn-cancel btn-sm px-3 rounded-pill fw-semibold" @click="isAssignPicModalOpen = false">
                  Batal
                </button>
                <button type="submit" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold d-inline-flex align-items-center gap-1.5 shadow" :disabled="picForm.processing">
                  <span v-if="picForm.processing" class="spinner-border spinner-border-sm"></span>
                  <i v-else class="ri-check-line"></i>
                  <span>{{ picForm.processing ? 'Menyimpan...' : 'Simpan & Beri Hak Akses' }}</span>
                </button>
              </div>
            </div>

          </form>
        </div>

      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 7: UNGGAH BUKTI PEMBAYARAN TERPADU PAKET ALL-IN                     -->
    <!-- ========================================================================= -->
    <div v-if="isPackageProofModalOpen" class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3">
      <div class="modal-dialog-custom w-100 modal-card-box rounded-4 shadow-lg overflow-hidden d-flex flex-column" style="max-width: 580px;">
        
        <!-- Modal Header -->
        <div class="p-3 px-md-4 modal-header-box d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fs-20 flex-shrink-0" style="width: 38px; height: 38px;">
              <i class="ri-secure-payment-line"></i>
            </div>
            <div>
              <h5 class="fs-16 fw-black modal-title-text mb-0">Unggah Bukti Pembayaran Paket All-In</h5>
              <div class="fs-11.5 modal-desc-text">
                1 Bukti Pembayaran Terpadu untuk Seluruh Cabang Lomba Kontingen
              </div>
            </div>
          </div>
          <button type="button" class="btn-close" :class="{ 'btn-close-white': isDark }" @click="isPackageProofModalOpen = false"></button>
        </div>

        <!-- Modal Body -->
        <div class="p-3 px-md-4 modal-body-box">
          <form @submit.prevent="submitPackageProof" class="d-flex flex-column gap-3 fs-13">
            
            <!-- Ringkasan Tagihan & Rekening -->
            <div class="p-3 rounded-3 modal-alert-info d-flex flex-column gap-2">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="fs-12 fw-bold modal-alert-text">Total Tagihan Paket All-In:</span>
                <span class="fs-16 fw-black text-success-highlight">{{ formatRupiah(packagePayment.price) }}</span>
              </div>
              <div class="fs-11.5 text-muted border-top border-dashed pt-2">
                Transfer ke: <strong>{{ packagePayment.bank_name }}</strong><br />
                Nomor Rekening: <strong class="text-primary font-monospace fs-12">{{ packagePayment.account_number }}</strong> (a.n {{ packagePayment.account_holder }})
              </div>
            </div>

            <!-- Saluran Pembayaran (Rule 1: CustomSelect) -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Saluran Pembayaran Bank <span class="text-danger">*</span>
              </label>
              <CustomSelect
                v-model="packageProofMethod"
                :options="packagePaymentMethodOptions"
                size="md"
                placeholder="Pilih saluran pembayaran..."
              />
            </div>

            <!-- Input File Bukti Pembayaran -->
            <div>
              <label class="form-label fs-13 fw-bold modal-label-text mb-1">
                Pilih Berkas Foto / PDF Bukti Transfer <span class="text-danger">*</span>
              </label>
              <input
                type="file"
                class="form-control modal-input fs-12"
                accept="image/*,application/pdf"
                required
                @change="handlePackageProofChange"
              />
              <div class="fs-11 modal-desc-text mt-1">
                Format didukung: JPG, PNG, PDF. Ukuran berkas maksimal 5 MB. Pastikan struk atau tangkapan layar transfer terlihat jelas.
              </div>

              <!-- Preview Image -->
              <div v-if="packageProofPreview" class="mt-2.5 text-center p-2 rounded modal-preview-box">
                <img :src="packageProofPreview" alt="Pratinjau Bukti Transfer" style="max-height: 180px; object-fit: contain;" class="rounded" />
              </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top">
              <button
                type="button"
                class="btn modal-btn-cancel btn-sm px-3 rounded-pill fw-semibold"
                @click="isPackageProofModalOpen = false"
                :disabled="packageProofForm.processing"
              >
                Batal
              </button>
              <button
                type="submit"
                class="btn btn-success btn-sm px-4 rounded-pill fw-bold d-inline-flex align-items-center gap-1.5 shadow"
                :disabled="packageProofForm.processing || !packageProofForm.payment_proof"
              >
                <span v-if="packageProofForm.processing" class="spinner-border spinner-border-sm"></span>
                <i v-else class="ri-upload-cloud-fill"></i>
                <span>{{ packageProofForm.processing ? 'Mengunggah...' : 'Unggah & Konfirmasi Bukti' }}</span>
              </button>
            </div>

          </form>
        </div>

      </div>
    </div>

    <!-- Modal Sesuaikan Foto Profil Pendaftar -->
    <AvatarCropperModal
      :show="isPendaftarAvatarModalOpen"
      title="Sesuaikan & Potong Foto Profil Akun"
      :current-avatar="user.avatar"
      :user-name="user.name"
      :is-processing="isSavingPendaftarAvatar"
      @close="isPendaftarAvatarModalOpen = false"
      @save="handleSavePendaftarAvatar"
      @delete="handleDeletePendaftarAvatar"
    />

  </div>
</template>

<style>
/* Container (Rule 2: High Contrast Light & Dark) */
.registrant-dashboard-container {
  background-color: #f8fafc;
  min-height: 100vh;
  color: #0f172a;
  transition: background-color 0.25s ease, color 0.25s ease;
}
[data-bs-theme="dark"] .registrant-dashboard-container,
.registrant-dashboard-container.dark-theme {
  background-color: #0b0f19 !important;
  color: #f8fafc !important;
}

/* Topbar */
.registrant-topbar {
  background: #ffffff;
  border-bottom: 1.5px solid #e2e8f0;
  z-index: 1020;
}
[data-bs-theme="dark"] .registrant-topbar,
.dark-theme .registrant-topbar {
  background: rgba(15, 23, 42, 0.96) !important;
  border-bottom: 1.5px solid rgba(255, 255, 255, 0.12) !important;
  backdrop-filter: blur(12px);
}
.topbar-brand {
  color: #0f172a;
}
[data-bs-theme="dark"] .topbar-brand {
  color: #ffffff;
}
.topbar-subbrand {
  color: #475569;
}
[data-bs-theme="dark"] .topbar-subbrand {
  color: #94a3b8;
}

/* Theme Toggle Button */
.dashboard-theme-toggle {
  background: #ffffff;
  border: 1.5px solid #94a3b8;
  color: #0f172a;
  transition: all 0.2s ease;
}
.dashboard-theme-toggle:hover {
  background: #f1f5f9;
}
[data-bs-theme="dark"] .dashboard-theme-toggle {
  background: #1e293b;
  border: 1.5px solid #475569;
  color: #f8fafc;
}
[data-bs-theme="dark"] .dashboard-theme-toggle:hover {
  filter: brightness(1.2);
}

/* Topbar Profile & Logout */
.topbar-profile-btn {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #0f172a;
}
.topbar-profile-btn:hover {
  background: #f8fafc;
  color: #000000;
}
[data-bs-theme="dark"] .topbar-profile-btn {
  background: #1e293b;
  border: 1.5px solid #334155;
  color: #f8fafc;
}
[data-bs-theme="dark"] .topbar-profile-btn:hover {
  background: #334155;
  color: #ffffff;
}

.topbar-logout-btn {
  background: #fef2f2;
  border: 1.5px solid #fca5a5;
  color: #dc2626;
}
.topbar-logout-btn:hover {
  background: #fee2e2;
  color: #b91c1c;
}
[data-bs-theme="dark"] .topbar-logout-btn {
  background: rgba(239, 68, 68, 0.15);
  border: 1.5px solid rgba(248, 113, 113, 0.4);
  color: #fca5a5;
}
[data-bs-theme="dark"] .topbar-logout-btn:hover {
  background: rgba(239, 68, 68, 0.3);
  color: #ffffff;
}
.topbar-divider {
  border-color: #cbd5e1 !important;
}
[data-bs-theme="dark"] .topbar-divider {
  border-color: #334155 !important;
}

/* Hero Welcome Banner */
.welcome-hero-card,
.welcome-hero-card > .card-body {
  background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #2563eb 100%) !important;
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  box-shadow: 0 4px 20px rgba(30, 58, 138, 0.25) !important;
  color: #ffffff !important;
}
[data-bs-theme="dark"] .welcome-hero-card,
[data-bs-theme="dark"] .welcome-hero-card > .card-body,
.dark-theme .welcome-hero-card,
.dark-theme .welcome-hero-card > .card-body {
  background: linear-gradient(135deg, #1e1b4b 0%, #1e293b 50%, #0f172a 100%) !important;
  border: 1px solid rgba(255, 255, 255, 0.15) !important;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
  color: #ffffff !important;
}
.hero-subtitle {
  color: #f1f5f9;
  opacity: 0.92;
}
.hero-school-tag {
  color: #e2e8f0;
}
.hero-badge-status {
  background: #16a34a !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
}
.hero-badge-role {
  background: #0284c7 !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
}
.hero-helpdesk-box {
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.25);
}
.hero-helpdesk-label {
  color: #93c5fd;
}
.hero-helpdesk-desc {
  color: #e2e8f0;
}

/* Stat Cards */
.stat-mini-card {
  background: #ffffff !important;
  border: 1.5px solid #e2e8f0 !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}
.stat-mini-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
}
[data-bs-theme="dark"] .stat-mini-card,
.dark-theme .stat-mini-card {
  background: #111827 !important;
  border: 1.5px solid #1f2937 !important;
  box-shadow: none;
}
[data-bs-theme="dark"] .stat-mini-card:hover {
  border-color: rgba(99, 102, 241, 0.5) !important;
}

.stat-icon-wrap {
  width: 44px;
  height: 44px;
}
.stat-icon-primary {
  background: #dbeafe;
  color: #1d4ed8;
}
[data-bs-theme="dark"] .stat-icon-primary {
  background: rgba(59, 130, 246, 0.25);
  color: #93c5fd;
}
.stat-icon-success {
  background: #dcfce7;
  color: #15803d;
}
[data-bs-theme="dark"] .stat-icon-success {
  background: rgba(34, 197, 94, 0.25);
  color: #86efac;
}
.stat-icon-warning {
  background: #fef3c7;
  color: #b45309;
}
[data-bs-theme="dark"] .stat-icon-warning {
  background: rgba(245, 158, 11, 0.25);
  color: #fde047;
}
.stat-icon-info {
  background: #e0f2fe;
  color: #0369a1;
}
[data-bs-theme="dark"] .stat-icon-info {
  background: rgba(14, 165, 233, 0.25);
  color: #7dd3fc;
}

.stat-label {
  color: #475569;
}
[data-bs-theme="dark"] .stat-label {
  color: #94a3b8;
}
.stat-value {
  color: #0f172a;
}
[data-bs-theme="dark"] .stat-value {
  color: #ffffff;
}
.stat-unit {
  color: #64748b;
}
[data-bs-theme="dark"] .stat-unit {
  color: #94a3b8;
}
.text-success-highlight {
  color: #15803d !important;
}
[data-bs-theme="dark"] .text-success-highlight {
  color: #4ade80 !important;
}
.text-warning-highlight {
  color: #b45309 !important;
}
[data-bs-theme="dark"] .text-warning-highlight {
  color: #fde047 !important;
}
.text-info-highlight {
  color: #0369a1 !important;
}
[data-bs-theme="dark"] .text-info-highlight {
  color: #7dd3fc !important;
}

/* Dashboard Main Section & Card */
.dashboard-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .dashboard-card,
.dark-theme .dashboard-card {
  background: #111827 !important;
  border: 1.5px solid #1f2937 !important;
}

.dashboard-card-header {
  background: #f8fafc;
  border-bottom: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .dashboard-card-header,
.dark-theme .dashboard-card-header {
  background: #162033 !important;
  border-bottom: 1.5px solid #1f2937 !important;
}

.dashboard-section-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .dashboard-section-title {
  color: #ffffff;
}
.dashboard-section-desc {
  color: #475569;
}
[data-bs-theme="dark"] .dashboard-section-desc {
  color: #94a3b8;
}

/* Filter Group */
.filter-btn-group {
  background: #e2e8f0;
  border: 1px solid #cbd5e1;
}
[data-bs-theme="dark"] .filter-btn-group {
  background: #0f172a;
  border: 1px solid #334155;
}
.filter-btn-active-all {
  background: #2563eb !important;
  color: #ffffff !important;
}
.filter-btn-active-pending {
  background: #f59e0b !important;
  color: #0f172a !important;
  font-weight: 600 !important;
}
.filter-btn-active-verified {
  background: #16a34a !important;
  color: #ffffff !important;
}
.filter-btn-inactive {
  background: transparent;
  color: #334155;
}
[data-bs-theme="dark"] .filter-btn-inactive {
  color: #94a3b8;
}

/* Search Group */
.dashboard-search-icon {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-right: none;
  color: #64748b;
}
[data-bs-theme="dark"] .dashboard-search-icon {
  background: #0f172a;
  border: 1.5px solid #334155;
  border-right: none;
  color: #94a3b8;
}
.dashboard-search-input {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  border-left: none;
  color: #0f172a;
  font-weight: 600;
}
[data-bs-theme="dark"] .dashboard-search-input {
  background: #0f172a;
  border: 1.5px solid #334155;
  border-left: none;
  color: #ffffff;
}

/* Dashboard Table */
.dashboard-table thead {
  background: #f1f5f9;
  border-bottom: 1.5px solid #cbd5e1;
}
[data-bs-theme="dark"] .dashboard-table thead {
  background: #0f172a;
  border-bottom: 1.5px solid #334155;
}
.dashboard-table thead th {
  color: #334155;
  font-weight: 600;
  font-size: 11px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
[data-bs-theme="dark"] .dashboard-table thead th {
  color: #94a3b8;
}

.dashboard-table tbody tr {
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s ease;
}
.dashboard-table tbody tr:hover {
  background: #f8fafc;
}
[data-bs-theme="dark"] .dashboard-table tbody tr,
.dark-theme .dashboard-table tbody tr {
  --bs-table-bg: #111827 !important;
  background-color: #111827 !important;
  border-bottom: 1px solid #1f2937 !important;
}
[data-bs-theme="dark"] .dashboard-table tbody tr:hover,
.dark-theme .dashboard-table tbody tr:hover {
  --bs-table-bg: #1e293b !important;
  background-color: #1e293b !important;
}

.table-title-text {
  color: #0f172a;
}
[data-bs-theme="dark"] .table-title-text {
  color: #ffffff;
}
.table-subtitle-text {
  color: #334155;
}
[data-bs-theme="dark"] .table-subtitle-text {
  color: #e2e8f0;
}
.table-muted-text {
  color: #64748b;
}
[data-bs-theme="dark"] .table-muted-text {
  color: #94a3b8;
}
.table-draw-number {
  color: #0284c7;
}
[data-bs-theme="dark"] .table-draw-number {
  color: #38bdf8;
}

/* Status Badges */
.badge-reg-code {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  font-weight: 600;
}
[data-bs-theme="dark"] .badge-reg-code {
  background: #1e293b;
  border: 1px solid #475569;
  color: #f8fafc;
}
.badge-all-in {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
  font-weight: 600;
}
[data-bs-theme="dark"] .badge-all-in {
  background: rgba(245, 158, 11, 0.25);
  border: 1px solid #f59e0b;
  color: #fde047;
}

.badge-status-verified {
  background: #dcfce7 !important;
  border: 1.5px solid #86efac !important;
  color: #15803d !important;
}
[data-bs-theme="dark"] .badge-status-verified {
  background: rgba(22, 101, 52, 0.45) !important;
  border: 1.5px solid #4ade80 !important;
  color: #86efac !important;
}

.badge-status-rejected {
  background: #fee2e2 !important;
  border: 1.5px solid #fca5a5 !important;
  color: #991b1b !important;
}
[data-bs-theme="dark"] .badge-status-rejected {
  background: rgba(185, 28, 28, 0.45) !important;
  border: 1.5px solid #f87171 !important;
  color: #fca5a5 !important;
}

.badge-status-pending {
  background: #fef3c7 !important;
  border: 1.5px solid #fcd34d !important;
  color: #92400e !important;
}
[data-bs-theme="dark"] .badge-status-pending {
  background: rgba(217, 119, 6, 0.45) !important;
  border: 1.5px solid #f59e0b !important;
  color: #fde047 !important;
}

.badge-status-allin-included {
  background: #e0f2fe !important;
  border: 1.5px solid #7dd3fc !important;
  color: #0369a1 !important;
}
[data-bs-theme="dark"] .badge-status-allin-included {
  background: rgba(3, 105, 161, 0.45) !important;
  border: 1.5px solid #38bdf8 !important;
  color: #7dd3fc !important;
}

.package-payment-panel {
  border-color: rgba(226, 232, 240, 0.8) !important;
}
[data-bs-theme="dark"] .package-payment-panel {
  border-color: rgba(255, 255, 255, 0.1) !important;
}

.package-payment-box {
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
}
[data-bs-theme="dark"] .package-payment-box {
  background: rgba(15, 23, 42, 0.6);
  border: 1.5px solid rgba(255, 255, 255, 0.12);
}

.pay-icon-success {
  background: #dcfce7;
  color: #15803d;
  width: 44px;
  height: 44px;
}
[data-bs-theme="dark"] .pay-icon-success {
  background: rgba(22, 101, 52, 0.4);
  color: #86efac;
}

.pay-icon-warning {
  background: #fef3c7;
  color: #b45309;
  width: 44px;
  height: 44px;
}
[data-bs-theme="dark"] .pay-icon-warning {
  background: rgba(245, 158, 11, 0.35);
  color: #fde047;
}

/* Action Buttons in Table */
.table-action-edit-btn {
  background: #eff6ff;
  border: 1.5px solid #93c5fd;
  color: #1d4ed8;
}
.table-action-edit-btn:hover:not(.disabled) {
  background: #2563eb;
  color: #ffffff;
}
[data-bs-theme="dark"] .table-action-edit-btn {
  background: rgba(59, 130, 246, 0.2);
  border: 1.5px solid #60a5fa;
  color: #93c5fd;
}
[data-bs-theme="dark"] .table-action-edit-btn:hover:not(.disabled) {
  background: #2563eb;
  color: #ffffff;
}

.table-action-invoice-btn {
  background: #f0fdf4;
  border: 1.5px solid #86efac;
  color: #166534;
}
.table-action-invoice-btn:hover {
  background: #16a34a;
  color: #ffffff;
}
[data-bs-theme="dark"] .table-action-invoice-btn {
  background: rgba(22, 101, 52, 0.25);
  border: 1.5px solid #4ade80;
  color: #86efac;
}
[data-bs-theme="dark"] .table-action-invoice-btn:hover {
  background: #16a34a;
  color: #ffffff;
}

.table-action-print-btn {
  background: #f8fafc;
  border: 1.5px solid #cbd5e1;
  color: #0f172a;
}
.table-action-print-btn:hover {
  background: #e2e8f0;
}
[data-bs-theme="dark"] .table-action-print-btn {
  background: #1e293b;
  border: 1.5px solid #475569;
  color: #f8fafc;
}
[data-bs-theme="dark"] .table-action-print-btn:hover {
  background: #334155;
}

/* Empty State */
.dashboard-empty-icon {
  background: #e2e8f0;
  color: #64748b;
}
[data-bs-theme="dark"] .dashboard-empty-icon {
  background: rgba(255, 255, 255, 0.08);
  color: #94a3b8;
}
.dashboard-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .dashboard-title {
  color: #ffffff;
}
.dashboard-desc {
  color: #475569;
}
[data-bs-theme="dark"] .dashboard-desc {
  color: #94a3b8;
}

/* Modals */
.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  z-index: 1060;
}

.modal-dialog-custom {
  animation: modalFadeIn 0.2s ease-out;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-card-box {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #0f172a;
}
[data-bs-theme="dark"] .modal-card-box {
  background: #111827;
  border: 1.5px solid #334155;
  color: #ffffff;
}
.modal-header-box {
  background: #f8fafc;
  border-bottom: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .modal-header-box {
  background: #0f172a;
  border-bottom: 1.5px solid #1f2937;
}
.modal-footer-box {
  background: #f8fafc;
  border-top: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .modal-footer-box {
  background: #0f172a;
  border-top: 1.5px solid #1f2937;
}
.modal-body-box {
  background: #ffffff;
}
[data-bs-theme="dark"] .modal-body-box {
  background: #111827;
}
.modal-title-text {
  color: #0f172a;
}
[data-bs-theme="dark"] .modal-title-text {
  color: #ffffff;
}
.modal-desc-text {
  color: #475569;
}
[data-bs-theme="dark"] .modal-desc-text {
  color: #94a3b8;
}
.modal-label-text {
  color: #0f172a;
}
[data-bs-theme="dark"] .modal-label-text {
  color: #f8fafc;
}
.modal-input {
  background: #ffffff;
  border: 1.5px solid #cbd5e1;
  color: #0f172a;
  font-weight: 500;
}
.modal-input:focus {
  border-color: #2563eb;
  color: #0f172a;
}
[data-bs-theme="dark"] .modal-input {
  background: #0f172a;
  border: 1.5px solid #475569;
  color: #ffffff;
}
[data-bs-theme="dark"] .modal-input:focus {
  border-color: #60a5fa;
  color: #ffffff;
}
.modal-alert-info {
  background: #f0f9ff;
  border: 1.5px solid #bae6fd;
}
.modal-alert-text {
  color: #0369a1;
  font-weight: 500;
}
[data-bs-theme="dark"] .modal-alert-info {
  background: rgba(14, 165, 233, 0.15);
  border: 1.5px solid rgba(56, 189, 248, 0.4);
}
[data-bs-theme="dark"] .modal-alert-text {
  color: #e0f2fe;
}
.modal-upload-box {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .modal-upload-box {
  background: #0f172a;
  border: 1.5px solid #1f2937;
}
.modal-preview-box {
  background: #ffffff;
  border: 1px solid #cbd5e1;
}
[data-bs-theme="dark"] .modal-preview-box {
  background: #0f172a;
  border: 1px solid #334155;
}
.modal-doc-item {
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .modal-doc-item {
  background: #0f172a;
  border: 1.5px solid #1f2937;
}
.modal-btn-cancel {
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  color: #334155;
}
.modal-btn-cancel:hover {
  background: #e2e8f0;
}
[data-bs-theme="dark"] .modal-btn-cancel {
  background: #1e293b;
  border: 1.5px solid #475569;
  color: #f8fafc;
}
[data-bs-theme="dark"] .modal-btn-cancel:hover {
  background: #334155;
}

/* Badge Portal Resmi */
.badge-portal-resmi {
  background: #dbeafe !important;
  border: 1.5px solid #93c5fd !important;
  color: #1e40af !important;
}
[data-bs-theme="dark"] .badge-portal-resmi {
  background: rgba(30, 58, 138, 0.45) !important;
  border: 1.5px solid #60a5fa !important;
  color: #bfdbfe !important;
}

/* Detail Modal Styles (Screen View - Rule 2 & 3 High Contrast) */
.detail-section-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
}
[data-bs-theme="dark"] .detail-section-card {
  background: #111827;
  border: 1.5px solid #1f2937;
}
.detail-label {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: #475569;
}
[data-bs-theme="dark"] .detail-label {
  color: #94a3b8;
}
.detail-label-sub {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 2px;
}
[data-bs-theme="dark"] .detail-label-sub {
  color: #cbd5e1;
}
.detail-value {
  color: #0f172a;
}
[data-bs-theme="dark"] .detail-value {
  color: #ffffff;
}
.detail-participant-item {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #0f172a;
}
[data-bs-theme="dark"] .detail-participant-item {
  background: #0b0f19;
  border: 1px solid #334155;
  color: #f8fafc;
}

/* Action Buttons in Table */
.table-action-detail-btn {
  background: #e0f2fe;
  border: 1.5px solid #7dd3fc;
  color: #0284c7;
}
.table-action-detail-btn:hover {
  background: #bae6fd;
  color: #0369a1;
}
[data-bs-theme="dark"] .table-action-detail-btn {
  background: rgba(14, 165, 233, 0.2);
  border: 1.5px solid #38bdf8;
  color: #7dd3fc;
}
[data-bs-theme="dark"] .table-action-detail-btn:hover {
  background: rgba(14, 165, 233, 0.35);
  color: #ffffff;
}

.table-action-print-btn {
  background: #ede9fe;
  border: 1.5px solid #c4b5fd;
  color: #6d28d9;
}
.table-action-print-btn:hover {
  background: #ddd6fe;
  color: #5b21b6;
}
[data-bs-theme="dark"] .table-action-print-btn {
  background: rgba(124, 58, 237, 0.2);
  border: 1.5px solid #a78bfa;
  color: #c4b5fd;
}
[data-bs-theme="dark"] .table-action-print-btn:hover {
  background: rgba(124, 58, 237, 0.35);
  color: #ffffff;
}

/* Modal Alert Danger */
.modal-alert-danger {
  background: #fee2e2;
  border: 1.5px solid #fca5a5;
}
[data-bs-theme="dark"] .modal-alert-danger {
  background: rgba(220, 38, 38, 0.2);
  border: 1.5px solid rgba(248, 113, 113, 0.4);
}

/* ========================================================================= */
/* PRINT PREVIEW DESK & OFFICIAL A4 PRINT DOCUMENT                           */
/* ========================================================================= */

/* Print Preview Theme Mode Toggle Button */
.print-theme-mode-btn {
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  color: #0f172a;
  transition: all 0.2s ease;
}
.print-theme-mode-btn:hover {
  background: #e2e8f0;
  color: #000000;
}
[data-bs-theme="dark"] .print-theme-mode-btn {
  background: #1e293b;
  border: 1.5px solid #475569;
  color: #f8fafc;
}
[data-bs-theme="dark"] .print-theme-mode-btn:hover {
  background: #334155;
  color: #ffffff;
}

.print-desk-preview-wrapper {
  background: #334155;
  transition: background 0.25s ease;
}
[data-bs-theme="dark"] .print-desk-preview-wrapper {
  background: #090d16;
}

.official-print-sheet {
  width: 100%;
  max-width: 760px;
  font-family: 'Poppins', Arial, Helvetica, sans-serif;
  line-height: 1.35;
  transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
}

/* KOP Surat Universal Dimensions */
.print-kop-logo {
  width: 58px;
  height: 58px;
  object-fit: contain;
}
.print-kop-foundation {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.8px;
  text-transform: uppercase;
}
.print-kop-committee {
  font-size: 12.5px;
  font-weight: 800;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}
.print-kop-school {
  font-size: 15px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.print-kop-accreditation {
  font-size: 9.5px;
  font-weight: 700;
}
.print-kop-address {
  font-size: 9.5px;
}
.print-kop-contact {
  font-size: 9px;
}
.print-double-divider {
  height: 4px;
  margin: 8px 0 12px 0;
}

/* Document Title Universal */
.print-document-title {
  font-size: 13.5px;
  font-weight: 900;
  letter-spacing: 0.5px;
  text-decoration: underline;
  margin-bottom: 2px;
}
.print-document-number {
  font-size: 10.5px;
  font-weight: 700;
  font-family: monospace;
}

/* Security Barcode Strip Universal */
.print-bar-thick {
  width: 2.5px;
}
.print-bar-med {
  width: 1.5px;
}
.print-bar-thin {
  width: 0.75px;
}
.print-system-tag {
  font-size: 9.5px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 4px;
}

/* Formal Table Base */
.print-formal-table {
  font-size: 11px;
}
.print-formal-table td, .print-formal-table th {
  padding: 4px 8px !important;
}
.print-table-header-row {
  font-weight: 800;
  font-size: 10.5px;
  letter-spacing: 0.5px;
}
.print-col-label {
  width: 210px;
  font-weight: 600;
}
.print-participant-list {
  padding-left: 16px;
}
.print-participant-list li {
  margin-bottom: 2px;
}

/* Status Stamps in Print Universal */
.print-status-stamp {
  display: inline-block;
  padding: 2px 7px;
  font-size: 9.5px;
  font-weight: 800;
  border-radius: 3px;
  text-transform: uppercase;
}

/* Rules Box Base */
.print-rules-title {
  font-size: 10px;
  font-weight: 800;
  margin-bottom: 2px;
}
.print-rules-list {
  font-size: 9.5px;
}
.print-rules-list li {
  margin-bottom: 2px;
}

/* Signatures Base */
.print-date-location {
  font-size: 10px;
  font-weight: 600;
}
.print-sig-role {
  font-size: 9.5px;
}
.print-sig-title {
  font-size: 10px;
  font-weight: 700;
}
.print-sig-space {
  height: 48px;
}
.print-sig-name {
  font-size: 10.5px;
  font-weight: 800;
  text-decoration: underline;
}
.print-sig-nip {
  font-size: 9px;
}

/* Official Circular Stamp Base */
.print-stamp-seal-badge {
  position: absolute;
  top: 15px;
  left: 45%;
  transform: translateX(-50%) rotate(-12deg);
  pointer-events: none;
  opacity: 0.85;
}
.stamp-outer-ring {
  width: 95px;
  height: 95px;
  border: 2.5px solid #2563eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3px;
}
.stamp-inner-ring {
  width: 82px;
  height: 82px;
  border: 1px dashed #2563eb;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #2563eb;
  padding: 4px;
}
.stamp-arc-top {
  font-size: 7px;
  font-weight: 900;
  text-transform: uppercase;
  line-height: 1;
}
.stamp-star {
  font-size: 14px;
  color: #2563eb;
  margin: 2px 0;
}
.stamp-arc-bottom {
  font-size: 6.5px;
  font-weight: 900;
  text-transform: uppercase;
  line-height: 1;
}

/* System Footer Base */
.print-system-footer {
  font-size: 8.5px;
}

/* ========================================================================= */
/* THEME 1: PHYSICAL PAPER WHITE MODE (.theme-paper)                         */
/* (Strictly isolates and prevents dark theme variables leaking into paper) */
/* ========================================================================= */
.official-print-sheet.theme-paper {
  background: #ffffff !important;
  color: #0f172a !important;
}
.official-print-sheet.theme-paper .print-inner-frame {
  border: 2px solid #0f172a !important;
  outline: 1px solid #0f172a !important;
  outline-offset: -5px !important;
  padding: 20px 24px;
  background: #ffffff !important;
}

.official-print-sheet.theme-paper .print-kop-foundation { color: #1e293b !important; }
.official-print-sheet.theme-paper .print-kop-committee { color: #0f172a !important; }
.official-print-sheet.theme-paper .print-kop-school { color: #1e3a8a !important; }
.official-print-sheet.theme-paper .print-kop-accreditation { color: #334155 !important; }
.official-print-sheet.theme-paper .print-kop-address { color: #475569 !important; }
.official-print-sheet.theme-paper .print-kop-contact { color: #64748b !important; }
.official-print-sheet.theme-paper .print-double-divider {
  border-top: 2.5px solid #0f172a !important;
  border-bottom: 1px solid #0f172a !important;
}

.official-print-sheet.theme-paper .print-document-title {
  color: #0f172a !important;
  text-decoration-color: #0f172a !important;
}
.official-print-sheet.theme-paper .print-document-number {
  color: #334155 !important;
}

.official-print-sheet.theme-paper .print-security-strip {
  background: #f8fafc !important;
  border: 1px dashed #cbd5e1 !important;
}
.official-print-sheet.theme-paper .print-barcode-box {
  background: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
}
.official-print-sheet.theme-paper .print-bar-line {
  background-color: #000000 !important;
}
.official-print-sheet.theme-paper .print-barcode-label {
  color: #000000 !important;
}
.official-print-sheet.theme-paper .print-label-sub,
.official-print-sheet.theme-paper .print-date-sub {
  color: #64748b !important;
}
.official-print-sheet.theme-paper .print-code-val {
  color: #0f172a !important;
}
.official-print-sheet.theme-paper .print-system-tag {
  background: #dbeafe !important;
  color: #1e40af !important;
  border: 1px solid #93c5fd !important;
}

.official-print-sheet.theme-paper .print-formal-table {
  --bs-table-bg: #ffffff !important;
  --bs-table-color: #0f172a !important;
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #0f172a !important;
}
.official-print-sheet.theme-paper .print-formal-table td,
.official-print-sheet.theme-paper .print-formal-table th {
  --bs-table-bg: #ffffff !important;
  --bs-table-color: #0f172a !important;
  background: #ffffff !important;
  color: #0f172a !important;
  border: 1px solid #0f172a !important;
}
.official-print-sheet.theme-paper .print-table-header-row th {
  --bs-table-bg: #f1f5f9 !important;
  background: #f1f5f9 !important;
  color: #0f172a !important;
}
.official-print-sheet.theme-paper .print-col-label {
  --bs-table-bg: #f8fafc !important;
  background: #f8fafc !important;
  color: #334155 !important;
}
.official-print-sheet.theme-paper .print-col-value {
  --bs-table-bg: #ffffff !important;
  background: #ffffff !important;
  color: #0f172a !important;
}

.official-print-sheet.theme-paper .print-status-success {
  background: #dcfce7 !important;
  color: #15803d !important;
  border: 1px solid #86efac !important;
}
.official-print-sheet.theme-paper .print-status-warning {
  background: #fef3c7 !important;
  color: #b45309 !important;
  border: 1px solid #fde047 !important;
}
.official-print-sheet.theme-paper .print-status-danger {
  background: #fee2e2 !important;
  color: #b91c1c !important;
  border: 1px solid #fca5a5 !important;
}

.official-print-sheet.theme-paper .print-rules-box {
  background: #f8fafc !important;
  border: 1px solid #e2e8f0 !important;
}
.official-print-sheet.theme-paper .print-rules-title {
  color: #0f172a !important;
}
.official-print-sheet.theme-paper .print-rules-list {
  color: #334155 !important;
}

.official-print-sheet.theme-paper .print-date-location { color: #0f172a !important; }
.official-print-sheet.theme-paper .print-sig-role { color: #475569 !important; }
.official-print-sheet.theme-paper .print-sig-title { color: #0f172a !important; }
.official-print-sheet.theme-paper .print-sig-name { color: #0f172a !important; }
.official-print-sheet.theme-paper .print-sig-nip { color: #475569 !important; }
.official-print-sheet.theme-paper .stamp-outer-ring { border-color: #2563eb !important; }
.official-print-sheet.theme-paper .stamp-inner-ring { border-color: #2563eb !important; color: #2563eb !important; }
.official-print-sheet.theme-paper .stamp-star { color: #2563eb !important; }
.official-print-sheet.theme-paper .print-system-footer {
  border-top: 1px solid #cbd5e1 !important;
  color: #64748b !important;
}

/* ========================================================================= */
/* THEME 2: DARK SCREEN MODE (.theme-dark)                                   */
/* (High contrast WCAG AA compliant, glowing stamps, vibrant headers)        */
/* ========================================================================= */
.official-print-sheet.theme-dark {
  background: #0f172a !important;
  color: #f8fafc !important;
}
.official-print-sheet.theme-dark .print-inner-frame {
  border: 2px solid #334155 !important;
  outline: 1px solid #475569 !important;
  outline-offset: -5px !important;
  padding: 20px 24px;
  background: #0f172a !important;
  box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.45);
}

.official-print-sheet.theme-dark .print-kop-foundation { color: #cbd5e1 !important; }
.official-print-sheet.theme-dark .print-kop-committee { color: #f8fafc !important; }
.official-print-sheet.theme-dark .print-kop-school { color: #60a5fa !important; }
.official-print-sheet.theme-dark .print-kop-accreditation { color: #94a3b8 !important; }
.official-print-sheet.theme-dark .print-kop-address { color: #94a3b8 !important; }
.official-print-sheet.theme-dark .print-kop-contact { color: #64748b !important; }
.official-print-sheet.theme-dark .print-double-divider {
  border-top: 2.5px solid #475569 !important;
  border-bottom: 1px solid #334155 !important;
}

.official-print-sheet.theme-dark .print-document-title {
  color: #ffffff !important;
  text-decoration-color: #38bdf8 !important;
}
.official-print-sheet.theme-dark .print-document-number {
  color: #93c5fd !important;
}

.official-print-sheet.theme-dark .print-security-strip {
  background: #1e293b !important;
  border: 1px dashed #475569 !important;
}
.official-print-sheet.theme-dark .print-barcode-box {
  background: #090d16 !important;
  border: 1px solid #334155 !important;
}
.official-print-sheet.theme-dark .print-bar-line {
  background-color: #f8fafc !important;
}
.official-print-sheet.theme-dark .print-barcode-label {
  color: #f8fafc !important;
}
.official-print-sheet.theme-dark .print-label-sub,
.official-print-sheet.theme-dark .print-date-sub {
  color: #94a3b8 !important;
}
.official-print-sheet.theme-dark .print-code-val {
  color: #38bdf8 !important;
}
.official-print-sheet.theme-dark .print-system-tag {
  background: rgba(37, 99, 235, 0.25) !important;
  color: #93c5fd !important;
  border: 1px solid #3b82f6 !important;
}

.official-print-sheet.theme-dark .print-formal-table {
  --bs-table-bg: #0f172a !important;
  --bs-table-color: #f8fafc !important;
  border: 1px solid #334155 !important;
  background: #0f172a !important;
  color: #f8fafc !important;
}
.official-print-sheet.theme-dark .print-formal-table td,
.official-print-sheet.theme-dark .print-formal-table th {
  --bs-table-bg: #0f172a !important;
  --bs-table-color: #f8fafc !important;
  border: 1px solid #334155 !important;
  background: #0f172a !important;
  color: #f8fafc !important;
}
.official-print-sheet.theme-dark .print-table-header-row th {
  --bs-table-bg: #1e293b !important;
  background: #1e293b !important;
  color: #38bdf8 !important;
}
.official-print-sheet.theme-dark .print-col-label {
  --bs-table-bg: #1e293b !important;
  background: #1e293b !important;
  color: #cbd5e1 !important;
}
.official-print-sheet.theme-dark .print-col-value {
  --bs-table-bg: #0f172a !important;
  background: #0f172a !important;
  color: #f8fafc !important;
}

.official-print-sheet.theme-dark .print-status-success {
  background: rgba(34, 197, 94, 0.22) !important;
  color: #86efac !important;
  border: 1px solid rgba(134, 239, 172, 0.45) !important;
}
.official-print-sheet.theme-dark .print-status-warning {
  background: rgba(234, 179, 8, 0.22) !important;
  color: #fde047 !important;
  border: 1px solid rgba(253, 224, 71, 0.45) !important;
}
.official-print-sheet.theme-dark .print-status-danger {
  background: rgba(239, 68, 68, 0.22) !important;
  color: #fca5a5 !important;
  border: 1px solid rgba(252, 165, 165, 0.45) !important;
}

.official-print-sheet.theme-dark .print-rules-box {
  background: #1e293b !important;
  border: 1px solid #334155 !important;
}
.official-print-sheet.theme-dark .print-rules-title {
  color: #fde047 !important;
}
.official-print-sheet.theme-dark .print-rules-list {
  color: #cbd5e1 !important;
}

.official-print-sheet.theme-dark .print-date-location { color: #cbd5e1 !important; }
.official-print-sheet.theme-dark .print-sig-role { color: #94a3b8 !important; }
.official-print-sheet.theme-dark .print-sig-title { color: #e2e8f0 !important; }
.official-print-sheet.theme-dark .print-sig-name {
  color: #ffffff !important;
  text-decoration-color: #38bdf8 !important;
}
.official-print-sheet.theme-dark .print-sig-nip { color: #94a3b8 !important; }
.official-print-sheet.theme-dark .stamp-outer-ring {
  border-color: #60a5fa !important;
  box-shadow: 0 0 15px rgba(96, 165, 250, 0.25);
}
.official-print-sheet.theme-dark .stamp-inner-ring {
  border-color: #60a5fa !important;
  color: #93c5fd !important;
}
.official-print-sheet.theme-dark .stamp-star { color: #60a5fa !important; }
.official-print-sheet.theme-dark .print-system-footer {
  border-top: 1px solid #334155 !important;
  color: #94a3b8 !important;
}

/* ========================================================================= */
/* PIC & PACKAGE SELECTION CSS (RULES 2 & 3 COMPLIANT)                       */
/* ========================================================================= */

/* PIC Notice Card */
.pic-notice-card {
  background: #f0f9ff !important;
  border: 1.5px solid #bae6fd !important;
}
[data-bs-theme="dark"] .pic-notice-card {
  background: rgba(14, 165, 233, 0.12) !important;
  border: 1.5px solid rgba(56, 189, 248, 0.35) !important;
}

/* Package Banner Card */
.package-banner-card {
  background: #ffffff !important;
  border: 1.5px solid #cbd5e1 !important;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}
[data-bs-theme="dark"] .package-banner-card,
.dark-theme .package-banner-card {
  background: #111827 !important;
  border: 1.5px solid #1f2937 !important;
  box-shadow: none;
}

.package-banner-icon-wrap {
  width: 52px;
  height: 52px;
}
.package-icon-primary {
  background: #dbeafe !important;
  color: #1d4ed8 !important;
}
[data-bs-theme="dark"] .package-icon-primary {
  background: rgba(59, 130, 246, 0.25) !important;
  color: #93c5fd !important;
}
.package-icon-success {
  background: #dcfce7 !important;
  color: #15803d !important;
}
[data-bs-theme="dark"] .package-icon-success {
  background: rgba(34, 197, 94, 0.25) !important;
  color: #86efac !important;
}

.package-banner-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .package-banner-title {
  color: #ffffff;
}
.package-banner-desc {
  color: #475569;
}
[data-bs-theme="dark"] .package-banner-desc {
  color: #cbd5e1;
}

/* Assigned PIC Badge (Rule 3) */
.badge-assigned-pic {
  background: #e0f2fe !important;
  border: 1px solid #7dd3fc !important;
  color: #0369a1 !important;
}
[data-bs-theme="dark"] .badge-assigned-pic {
  background: rgba(14, 165, 233, 0.25) !important;
  border: 1px solid #38bdf8 !important;
  color: #7dd3fc !important;
}

/* Quick Assign PIC button in table cell */
.btn-assign-pic-quick {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  color: #2563eb;
  transition: all 0.2s ease;
}
.btn-assign-pic-quick:hover {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}
[data-bs-theme="dark"] .btn-assign-pic-quick {
  background: #1e293b;
  border: 1px solid #334155;
  color: #93c5fd;
}
[data-bs-theme="dark"] .btn-assign-pic-quick:hover {
  background: #2563eb;
  color: #ffffff;
}

/* PIC Action Button in Table */
.table-action-pic-btn {
  background: #fefce8;
  border: 1.5px solid #fde047;
  color: #854d0e;
}
.table-action-pic-btn:hover {
  background: #fde047;
  color: #713f12;
}
[data-bs-theme="dark"] .table-action-pic-btn {
  background: rgba(234, 179, 8, 0.15);
  border: 1.5px solid rgba(250, 204, 21, 0.45);
  color: #fde047;
}
[data-bs-theme="dark"] .table-action-pic-btn:hover {
  background: rgba(234, 179, 8, 0.35);
  color: #ffffff;
}

/* Package Plan Cards in Modal 5 */
.package-plan-card {
  background: #ffffff;
  border: 2px solid #e2e8f0;
  transition: all 0.25s ease;
}
.package-plan-selected-satuan {
  border-color: #94a3b8 !important;
}
.package-plan-allin {
  border-color: #3b82f6 !important;
  background: linear-gradient(180deg, #ffffff 0%, #f0f7ff 100%);
}
.package-plan-selected-allin {
  border-color: #10b981 !important;
  box-shadow: 0 4px 18px rgba(16, 185, 129, 0.2) !important;
}
[data-bs-theme="dark"] .package-plan-card {
  background: #111827;
  border-color: #334155;
}
[data-bs-theme="dark"] .package-plan-allin {
  background: linear-gradient(180deg, #111827 0%, #0f172a 100%);
  border-color: #60a5fa !important;
}
[data-bs-theme="dark"] .package-plan-selected-allin {
  border-color: #34d399 !important;
  box-shadow: 0 4px 18px rgba(52, 211, 153, 0.25) !important;
}

.package-plan-ribbon {
  position: absolute;
  top: 14px;
  right: -28px;
  transform: rotate(45deg);
  background: #f59e0b;
  color: #ffffff;
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0.5px;
  padding: 3px 28px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.package-price-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}
[data-bs-theme="dark"] .package-price-box {
  background: #1f2937;
  border-color: #374151;
}
.package-price-allin {
  background: #eff6ff !important;
  border-color: #bfdbfe !important;
}
[data-bs-theme="dark"] .package-price-allin {
  background: rgba(37, 99, 235, 0.15) !important;
  border-color: rgba(96, 165, 250, 0.3) !important;
}

.package-plan-title {
  color: #0f172a;
}
[data-bs-theme="dark"] .package-plan-title {
  color: #ffffff;
}

.modal-input-prefix {
  background: #f1f5f9;
  border: 1.5px solid #cbd5e1;
  border-right: none;
}
[data-bs-theme="dark"] .modal-input-prefix {
  background: #1e293b;
  border-color: #475569;
  border-right: none;
}

/* ========================================================================= */
/* PRECISE A4 @media print CSS (100% FAITHFUL TO PREVIEW)                    */
/* ========================================================================= */
@media print {
  @page {
    size: A4 portrait;
    margin: 8mm 10mm;
  }
  html, body {
    background: #ffffff !important;
    color: #000000 !important;
    margin: 0 !important;
    padding: 0 !important;
    height: auto !important;
    overflow: visible !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  /* Sembunyikan elemen latar dashboard, navigasi, dan tombol no-print */
  .registrant-dashboard-container,
  .registrant-topbar,
  header,
  footer,
  .no-print,
  .modal-backdrop-custom:not(.is-printing-modal) {
    display: none !important;
  }

  /* Tampilkan container modal cetak secara statis transparan tanpa efek overlay */
  .modal-backdrop-custom.is-printing-modal {
    display: block !important;
    position: static !important;
    width: 100% !important;
    height: auto !important;
    background: transparent !important;
    backdrop-filter: none !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
  }

  .modal-backdrop-custom.is-printing-modal .modal-dialog-custom {
    display: block !important;
    position: static !important;
    max-width: 100% !important;
    max-height: none !important;
    width: 100% !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
  }

  .modal-backdrop-custom.is-printing-modal .print-desk-preview-wrapper {
    display: block !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
  }

  /* Lembar dokumen resmi A4 cetak fisik */
  .official-print-sheet {
    display: block !important;
    position: static !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
    border: none !important;
    background: #ffffff !important;
    color: #000000 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .print-inner-frame {
    border: 2px solid #000000 !important;
    outline: 1px solid #000000 !important;
    outline-offset: -4px !important;
    padding: 14px 18px !important;
    background: #ffffff !important;
  }

  /* KOP Cetak */
  .print-kop-foundation { color: #000000 !important; }
  .print-kop-committee { color: #000000 !important; }
  .print-kop-school { color: #1e3a8a !important; }
  .print-kop-accreditation { color: #333333 !important; }
  .print-kop-address { color: #333333 !important; }
  .print-kop-contact { color: #444444 !important; }
  .print-double-divider {
    border-top: 2.5px solid #000000 !important;
    border-bottom: 1px solid #000000 !important;
  }

  /* Judul & Barcode */
  .print-document-title { color: #000000 !important; }
  .print-document-number { color: #000000 !important; }

  .print-security-strip {
    background: #f8fafc !important;
    border: 1px dashed #94a3b8 !important;
  }
  .print-barcode-box {
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
  }
  .print-bar-line {
    background-color: #000000 !important;
  }
  .print-barcode-label {
    color: #000000 !important;
  }
  .print-label-sub, .print-date-sub {
    color: #475569 !important;
  }
  .print-code-val {
    color: #000000 !important;
  }
  .print-system-tag {
    background: #dbeafe !important;
    color: #1e40af !important;
    border: 1px solid #93c5fd !important;
  }

  /* Tabel Formal Data */
  .print-formal-table {
    --bs-table-bg: #ffffff !important;
    --bs-table-color: #000000 !important;
    border: 1px solid #000000 !important;
    background: #ffffff !important;
    color: #000000 !important;
  }
  .print-formal-table td,
  .print-formal-table th {
    --bs-table-bg: #ffffff !important;
    --bs-table-color: #000000 !important;
    border: 1px solid #000000 !important;
    background: #ffffff !important;
    color: #000000 !important;
    padding: 3.5px 7px !important;
  }
  .print-table-header-row th {
    --bs-table-bg: #f1f5f9 !important;
    background: #f1f5f9 !important;
    color: #000000 !important;
  }
  .print-col-label {
    --bs-table-bg: #f8fafc !important;
    background: #f8fafc !important;
    color: #1e293b !important;
    width: 200px !important;
  }
  .print-col-value {
    --bs-table-bg: #ffffff !important;
    background: #ffffff !important;
    color: #000000 !important;
  }

  /* Status Stamps & Tata Tertib */
  .print-status-success {
    background: #dcfce7 !important;
    color: #15803d !important;
    border: 1px solid #86efac !important;
  }
  .print-status-warning {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fde047 !important;
  }
  .print-status-danger {
    background: #fee2e2 !important;
    color: #b91c1c !important;
    border: 1px solid #fca5a5 !important;
  }

  .print-rules-box {
    background: #f8fafc !important;
    border: 1px solid #cbd5e1 !important;
  }
  .print-rules-title {
    color: #000000 !important;
  }
  .print-rules-list {
    color: #1e293b !important;
  }

  /* Tanda Tangan & Stempel */
  .print-date-location,
  .print-sig-role,
  .print-sig-title,
  .print-sig-name,
  .print-sig-nip {
    color: #000000 !important;
  }
  .stamp-outer-ring {
    border-color: #2563eb !important;
  }
  .stamp-inner-ring {
    border-color: #2563eb !important;
    color: #2563eb !important;
  }
  .stamp-star {
    color: #2563eb !important;
  }
  .print-system-footer {
    border-top: 1px solid #cbd5e1 !important;
    color: #64748b !important;
  }
}
</style>
