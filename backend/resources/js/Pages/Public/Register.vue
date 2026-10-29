<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useTheme } from '@/Composables/useTheme';
import { getWaChatUrl } from '@/Utils/whatsapp';

const page = usePage();
const school = computed(() => page.props.schoolProfile || {});

const props = defineProps({
  competitions: {
    type: Array,
    default: () => []
  },
  preselectedLomba: String,
  pricingSettings: {
    type: Object,
    default: () => ({})
  },
  schools: {
    type: Array,
    default: () => []
  },
  isLoginRequired: {
    type: Boolean,
    default: false
  },
  authRegistrant: {
    type: Object,
    default: null
  }
});

// Theme Management (Light / Dark)
const { isDark, initTheme, toggleTheme } = useTheme();

// -----------------------------------------------------------------------------
// Core Form & State Management
// -----------------------------------------------------------------------------
const isSuccess = ref(false);
const registeredData = ref(null);
const submitAttempted = ref(false);
const validationErrors = ref({});

const form = useForm({
  package_type: 'Paket Satuan', // 'Paket Satuan' | 'Paket All-In'
  competition_id: props.preselectedLomba || props.competitions?.[0]?.id || 'puisi',
  school_name: props.authRegistrant?.school_name || '',
  mentor_name: props.authRegistrant?.name || '',
  mentor_wa: props.authRegistrant?.phone || '',
  participants_names: '',
  custom_fields: {
    school_district: props.authRegistrant?.school_district || '',
    school_address: '',
    mentor_email: props.authRegistrant?.email || '',
    allin_code: '',
    biodata_peserta: []
  },
  notes: '',
  payment_method: 'Transfer Bank BSI',
  payment_proof: null
});

// Toast Notification
const toast = ref({
  show: false,
  message: '',
  icon: 'ri-checkbox-circle-fill'
});
let toastTimer = null;

function showToast(msg, icon = 'ri-checkbox-circle-fill') {
  toast.value.message = msg;
  toast.value.icon = icon;
  toast.value.show = true;
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.value.show = false;
  }, 3200);
}

// Active Competition
const activeComp = computed(() => {
  return props.competitions?.find(c => c.id === form.competition_id) || props.competitions?.[0] || null;
});

// Cabang Lomba Options for CustomSelect
const competitionOptions = computed(() => {
  return (props.competitions || []).map(c => {
    const feeText = Number(c.fee) > 0 ? `Rp ${Number(c.fee).toLocaleString('id-ID')}` : 'Gratis';
    const cat = c.category_label || (c.category ? `[${c.category}] ` : '');
    return {
      value: c.id,
      label: `${c.title} • ${feeText} (${cat || 'Umum'})`
    };
  });
});

// Help Panitia WA URL
const helpWaUrl = computed(() => {
  const wa = school.value?.contact_wa || '083850070767';
  const compTitle = activeComp.value?.title || 'Lomba';
  const msg = `Halo Panitia Semarak Aliyah XIV, saya butuh bantuan pendaftaran cabang ${compTitle}.`;
  return getWaChatUrl(wa, msg);
});

// Juknis URL
const juknisUrl = computed(() => {
  return activeComp.value?.juknis_url || '/#juknis';
});

// -----------------------------------------------------------------------------
// School & District Handling
// -----------------------------------------------------------------------------
const schoolSuggestions = ref([]);
const isSearchingSchools = ref(false);
const showSchoolDropdown = ref(false);
let schoolSearchTimeout = null;

const kecamatanList = [
  'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 'Jenggawah',
  'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 'Mayang', 'Mumbulsari', 'Pakusari',
  'Panti', 'Patrang', 'Puger', 'Rambipuji', 'Semboro', 'Silo', 'Sukorambi', 'Sukowono',
  'Sumberbaru', 'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari', 'Wuluhan',
  'Luar Kabupaten Jember'
];

function onSchoolInput() {
  clearFieldError('school_name');
  const query = (form.school_name || '').trim();
  if (!query || query.length < 2) {
    schoolSuggestions.value = [];
    showSchoolDropdown.value = false;
    return;
  }

  if (schoolSearchTimeout) clearTimeout(schoolSearchTimeout);
  schoolSearchTimeout = setTimeout(async () => {
    isSearchingSchools.value = true;
    try {
      const res = await fetch(`/api/schools?search=${encodeURIComponent(query)}&limit=6&paginate=0`);
      if (res.ok) {
        const json = await res.json();
        schoolSuggestions.value = json.data || [];
        showSchoolDropdown.value = schoolSuggestions.value.length > 0;
      }
    } catch (e) {
      console.error('Failed to fetch school suggestions:', e);
    } finally {
      isSearchingSchools.value = false;
    }
  }, 250);
}

function onSchoolBlur() {
  setTimeout(() => {
    showSchoolDropdown.value = false;
  }, 220);
}

function selectSchool(s) {
  form.school_name = s.name;
  if (!form.custom_fields) form.custom_fields = {};
  if (s.kecamatan) form.custom_fields['school_district'] = s.kecamatan;
  if (s.alamat) form.custom_fields['school_address'] = s.alamat;
  if (s.npsn && s.npsn !== '-') form.custom_fields['school_npsn'] = s.npsn;
  showSchoolDropdown.value = false;
  schoolSuggestions.value = [];
  showToast(`Sekolah terpilih: ${s.name}`);
}

// -----------------------------------------------------------------------------
// Participant Count & Dynamic Repeater
// -----------------------------------------------------------------------------
const participantCount = ref(1);

const participants = ref([
  { name: '', grade: 'Kelas 9 SMP/MTs', gender: 'L', nisn: '' },
  { name: '', grade: 'Kelas 9 SMP/MTs', gender: 'L', nisn: '' },
]);

const gradeOptions = [
  'Kelas 9 SMP / MTs',
  'Kelas 8 SMP / MTs',
  'Kelas 7 SMP / MTs'
];

function setParticipantCount(count) {
  participantCount.value = count;
  if (count === 2) {
    showToast('Pilihan diubah: 2 Siswa (Maksimal Delegasi)');
  } else {
    showToast('Pilihan diubah: 1 Siswa (Tunggal)');
  }
}

// -----------------------------------------------------------------------------
// Package Scheme (Satuan vs All-In) & Verification
// -----------------------------------------------------------------------------
const isCheckingPackageCode = ref(false);
const isManualCodeVerified = ref(false);
const codeVerificationError = ref('');
const manualCodeSuccessData = ref(null);

const isAllInFree = computed(() => {
  return isManualCodeVerified.value && !!manualCodeSuccessData.value;
});

function setPackageType(type) {
  form.package_type = type;
  if (type === 'Paket All-In') {
    showToast('Skema diubah: Paket All-In Komplit');
  } else {
    showToast('Skema diubah: Paket Satuan');
  }
}

async function verifyPackageCodeManual() {
  const code = (form.custom_fields?.['allin_code'] || '').trim();
  if (!code) {
    codeVerificationError.value = 'Silakan masukkan Kode Paket Komplit terlebih dahulu.';
    return;
  }

  isCheckingPackageCode.value = true;
  codeVerificationError.value = '';
  try {
    const res = await fetch(`/api/schools/verify-package-code?code=${encodeURIComponent(code)}`);
    const json = await res.json();
    if (res.ok && json.status === 'success' && json.valid) {
      isManualCodeVerified.value = true;
      manualCodeSuccessData.value = json.data;
      codeVerificationError.value = '';
      form.package_type = 'Paket All-In';
      if (json.data.school_name && !form.school_name) form.school_name = json.data.school_name;
      if (json.data.school_district && !form.custom_fields['school_district']) form.custom_fields['school_district'] = json.data.school_district;
      showToast('Kode Paket Komplit terverifikasi! Biaya pendaftaran Rp 0.');
    } else {
      isManualCodeVerified.value = false;
      manualCodeSuccessData.value = null;
      codeVerificationError.value = json.message || 'Kode Paket Komplit tidak valid atau tidak terdaftar.';
    }
  } catch (err) {
    isManualCodeVerified.value = false;
    manualCodeSuccessData.value = null;
    codeVerificationError.value = 'Gagal menghubungi server untuk validasi kode paket.';
  } finally {
    isCheckingPackageCode.value = false;
  }
}

// -----------------------------------------------------------------------------
// Price & Fee Calculation
// -----------------------------------------------------------------------------
const baseCompFee = computed(() => {
  return Number(activeComp.value?.fee || 50000);
});

const totalCalculatedFee = computed(() => {
  if (isAllInFree.value) return 0;
  if (form.package_type === 'Paket All-In') {
    return Number(props.pricingSettings?.allin_price || 250000);
  }
  return baseCompFee.value * participantCount.value;
});

// -----------------------------------------------------------------------------
// Payment Methods & Copy Account
// -----------------------------------------------------------------------------
function selectPaymentMethod(val) {
  form.payment_method = val;
  showToast(`Metode dipilih: ${val}`);
}

function copyAccount(accNum) {
  if (!accNum) return;
  if (navigator?.clipboard) {
    navigator.clipboard.writeText(accNum);
  }
  showToast(`Nomor rekening ${accNum} berhasil disalin!`);
}

// -----------------------------------------------------------------------------
// File Uploads (Naskah Puisi & Bukti Bayar)
// -----------------------------------------------------------------------------
const selectedNaskahFile = ref(null);
const naskahInputRef = ref(null);
const paymentProofRef = ref(null);

function handleNaskahChange(e) {
  const file = e.target?.files?.[0];
  if (file) {
    selectedNaskahFile.value = file;
    if (!form.custom_fields) form.custom_fields = {};
    form.custom_fields['naskah_berkas'] = file;
    form.custom_fields['scan_kartu_pelajar'] = file;
    clearFieldError('naskah_berkas');
    showToast(`Berkas terpilih: ${file.name}`);
  }
}

function clearNaskahFile(e) {
  if (e) e.stopPropagation();
  selectedNaskahFile.value = null;
  if (form.custom_fields) {
    form.custom_fields['naskah_berkas'] = null;
    form.custom_fields['scan_kartu_pelajar'] = null;
  }
  if (naskahInputRef.value) naskahInputRef.value.value = '';
}

function handlePaymentProofChange(e) {
  const file = e.target?.files?.[0];
  if (file) {
    form.payment_proof = file;
    showToast(`Bukti pembayaran terunggah: ${file.name}`);
  }
}

function clearPaymentProof(e) {
  if (e) e.stopPropagation();
  form.payment_proof = null;
  if (paymentProofRef.value) paymentProofRef.value.value = '';
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const mb = (bytes / (1024 * 1024)).toFixed(2);
  return `${mb} MB`;
}

// -----------------------------------------------------------------------------
// Form Validation & Navigation
// -----------------------------------------------------------------------------
function getFieldError(fieldName) {
  return validationErrors.value[fieldName] || form.errors[fieldName] || form.errors['cf_' + fieldName] || form.errors['custom_fields.' + fieldName];
}

function hasFieldError(fieldName) {
  return !!getFieldError(fieldName);
}

function clearFieldError(fieldName) {
  if (validationErrors.value[fieldName]) {
    const updated = { ...validationErrors.value };
    delete updated[fieldName];
    validationErrors.value = updated;
  }
  if (form.errors[fieldName]) delete form.errors[fieldName];
  if (form.errors['cf_' + fieldName]) delete form.errors['cf_' + fieldName];
  if (form.errors['custom_fields.' + fieldName]) delete form.errors['custom_fields.' + fieldName];
}

const errorSummaryList = computed(() => {
  const list = [];
  const handled = new Set();
  Object.entries(validationErrors.value).forEach(([k, msg]) => {
    if (msg) {
      list.push({ key: k, message: msg });
      handled.add(k);
    }
  });
  Object.entries(form.errors).forEach(([k, msg]) => {
    const cleanKey = k.replace(/^cf_/, '').replace(/^custom_fields\./, '');
    if (!handled.has(k) && !handled.has(cleanKey) && msg) {
      list.push({ key: k, message: msg });
    }
  });
  return list;
});

function scrollToField(fieldKey) {
  let targetId = '';
  if (fieldKey === 'school_name') targetId = 'reg-sekolah';
  else if (fieldKey === 'school_district') targetId = 'reg-kecamatan';
  else if (fieldKey === 'school_address') targetId = 'reg-alamat';
  else if (fieldKey === 'mentor_name') targetId = 'reg-pendamping';
  else if (fieldKey === 'mentor_wa') targetId = 'reg-wa';
  else if (fieldKey === 'mentor_email') targetId = 'reg-email';
  else if (fieldKey.startsWith('participant_0')) targetId = 'reg-siswa-1';
  else if (fieldKey.startsWith('participant_1')) targetId = 'reg-siswa-2';

  nextTick(() => {
    const el = targetId ? document.getElementById(targetId) : null;
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      el.focus?.();
    }
  });
}

function validateForm() {
  submitAttempted.value = true;
  const errs = {};

  if (!form.school_name || !form.school_name.trim()) {
    errs['school_name'] = 'Nama Asal Sekolah / Madrasah wajib diisi.';
  }

  if (!form.custom_fields?.['school_district'] || !form.custom_fields['school_district'].trim()) {
    errs['school_district'] = 'Kecamatan Asal Sekolah wajib dipilih.';
  }

  if (!form.custom_fields?.['school_address'] || !form.custom_fields['school_address'].trim()) {
    errs['school_address'] = 'Alamat Lengkap Sekolah / Madrasah wajib diisi.';
  }

  if (!form.mentor_name || !form.mentor_name.trim()) {
    errs['mentor_name'] = 'Nama Guru Pembina / Pendamping wajib diisi.';
  }

  const cleanWa = (form.mentor_wa || '').replace(/\D/g, '');
  if (!cleanWa) {
    errs['mentor_wa'] = 'Nomor WhatsApp Guru Pembina wajib diisi.';
  } else if (cleanWa.length < 10) {
    errs['mentor_wa'] = 'Nomor WhatsApp pembina minimal 10 digit angka valid.';
  }

  const email = (form.custom_fields?.['mentor_email'] || '').trim();
  if (!email) {
    errs['mentor_email'] = 'Email Guru Pembina wajib diisi untuk bukti pendaftaran.';
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errs['mentor_email'] = 'Format email pembina tidak valid (contoh: pembina@gmail.com).';
  }

  // Validate Participant 1
  if (!participants.value[0]?.name?.trim()) {
    errs['participant_0_name'] = 'Nama lengkap Siswa #1 wajib diisi.';
  }

  // Validate Participant 2 if 2 participants selected
  if (participantCount.value === 2 && !participants.value[1]?.name?.trim()) {
    errs['participant_1_name'] = 'Nama lengkap Siswa #2 wajib diisi.';
  }

  validationErrors.value = errs;
  return Object.keys(errs).length === 0;
}

function handleSubmit() {
  if (!validateForm()) {
    showToast('Mohon lengkapi seluruh isian wajib yang ditandai merah!', 'ri-error-warning-fill');
    nextTick(() => {
      document.getElementById('form-validation-summary')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    return;
  }

  // Prepare participants array
  const activeParticipants = participants.value.slice(0, participantCount.value);
  form.participants_names = activeParticipants
    .map((p, idx) => `${idx + 1}. ${p.name} (${p.grade}, ${p.gender === 'L' ? 'Laki-Laki' : 'Perempuan'}${p.nisn ? ', NISN: ' + p.nisn : ''})`)
    .join('\n');

  // Sync custom_fields
  form.custom_fields.biodata_peserta = activeParticipants;
  form.custom_fields['school_name'] = form.school_name;
  form.custom_fields['mentor_name'] = form.mentor_name;
  form.custom_fields['mentor_wa'] = form.mentor_wa;

  form.post('/layanan-registrasi', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: (pageRes) => {
      isSuccess.value = true;
      registeredData.value = pageRes.props.flash?.registered;
      validationErrors.value = {};
      submitAttempted.value = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    onError: (errors) => {
      validationErrors.value = { ...errors };
      nextTick(() => {
        document.getElementById('form-validation-summary')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    }
  });
}

onMounted(() => {
  initTheme();
  if (props.preselectedLomba) {
    const matched = props.competitions?.find(c => c.id === props.preselectedLomba);
    if (matched) form.competition_id = matched.id;
  }
  if (props.authRegistrant?.school_name) form.school_name = props.authRegistrant.school_name;
  if (props.authRegistrant?.name) form.mentor_name = props.authRegistrant.name;
  if (props.authRegistrant?.phone) form.mentor_wa = props.authRegistrant.phone;
  if (props.authRegistrant?.email && !form.custom_fields['mentor_email']) form.custom_fields['mentor_email'] = props.authRegistrant.email;
  if (props.authRegistrant?.school_district && !form.custom_fields['school_district']) form.custom_fields['school_district'] = props.authRegistrant.school_district;
});
</script>

<template>
  <div class="semarak-portal-page" :class="{ 'dark': isDark }">
    <Head :title="activeComp ? `Pendaftaran Lomba ${activeComp.title} - Semarak Aliyah XIV 2026 | MAS Wahid Hasyim Balung` : 'Pendaftaran Semarak Aliyah XIV 2026 | MAS Wahid Hasyim Balung'" />

    <!-- ======================================================================
         HEADER PUBLIK ELEGAN & RINGKAS (Sticky Top)
         ====================================================================== -->
    <header class="page-top-header">
      <div class="header-inner-container">
        <!-- Logo Resmi & Branding -->
        <Link href="/" class="brand-link-group">
          <img alt="Logo Resmi Semarak Aliyah XIV 2026" class="brand-logo-img" src="/assets/img/logo.webp" />
          <div class="brand-text-column">
            <span class="brand-title-text">
              SEMARAK ALIYAH XIV
            </span>
            <span class="brand-subtitle-text">
              MAS Wahid Hasyim Balung • 2026
            </span>
          </div>
        </Link>

        <!-- Navigasi Ringkas Publik -->
        <nav class="header-nav-actions">
          <a class="nav-link-subtle hidden md:inline-flex" href="/#tentang">
            Tentang Lomba
          </a>
          <a :href="juknisUrl" target="_blank" class="nav-btn-juknis">
            <i class="ri-download-line text-[18px]"></i>
            <span>Juknis Lomba</span>
          </a>
          <a :href="helpWaUrl" target="_blank" class="nav-btn-wa">
            <i class="ri-customer-service-2-line text-[18px]"></i>
            <span>Bantuan Panitia</span>
          </a>
          <!-- Dark Mode Toggle -->
          <button 
            type="button" 
            @click="toggleTheme" 
            class="btn-theme-toggle"
            :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
          >
            <i :class="isDark ? 'ri-sun-line text-amber-400' : 'ri-moon-line text-slate-600'" class="text-[18px]"></i>
          </button>
        </nav>
      </div>
    </header>

    <!-- ======================================================================
         MAIN REGISTRATION SECTION
         ====================================================================== -->
    <main class="page-main-container">
      <div class="main-max-wrapper">

        <!-- SUCCESS STATE CONFIRMATION RECEIPT -->
        <div v-if="isSuccess" class="form-card-box text-center success-box-paddings">
          <div class="success-icon-badge">
            <i class="ri-checkbox-circle-fill"></i>
          </div>
          <div class="space-y-2 mt-4">
            <h2 class="font-headline font-extrabold text-2xl sm:text-3xl text-emerald-dark">Pendaftaran Berhasil Dikirim!</h2>
            <p class="text-on-surface-variant text-sm sm:text-base max-w-xl mx-auto">
              Terima kasih, formulir pendaftaran kontingen sekolah Anda telah berhasil tercatat di sistem Panitia Semarak Aliyah XIV 2026.
            </p>
          </div>

          <div v-if="registeredData" class="success-receipt-card">
            <div class="receipt-item-row">
              <span class="r-label">Kode Registrasi:</span>
              <span class="r-value code-bold">{{ registeredData.reg_code }}</span>
            </div>
            <div class="receipt-item-row">
              <span class="r-label">Asal Sekolah:</span>
              <span class="r-value">{{ registeredData.school_name }}</span>
            </div>
            <div class="receipt-item-row">
              <span class="r-label">Cabang Lomba:</span>
              <span class="r-value">{{ registeredData.competition_title }}</span>
            </div>
            <div class="receipt-item-row">
              <span class="r-label">Skema Paket:</span>
              <span class="r-value">{{ registeredData.package_type }}</span>
            </div>
            <div class="receipt-item-row total-row-highlight">
              <span class="r-label font-bold text-base">Total Biaya Pendaftaran:</span>
              <span class="r-value font-headline font-extrabold text-xl text-primary">
                Rp {{ Number(registeredData.total_fee || 0).toLocaleString('id-ID') }}
              </span>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
            <a href="/" class="btn-secondary-action">
              <i class="ri-arrow-left-line"></i> Kembali ke Beranda
            </a>
            <button 
              type="button" 
              @click="isSuccess = false; form.reset()" 
              class="btn-primary-action"
            >
              <i class="ri-add-line"></i> Daftarkan Cabang Lain
            </button>
          </div>
        </div>

        <!-- FORM CARD SECTION -->
        <div v-else>
          <!-- HERO HEADER ELEGAN & RINGKAS -->
          <div class="text-center mb-8 sm:mb-10 space-y-3">
            <div class="hero-status-pill">
              <span class="pulsing-emerald-dot"></span>
              <span>Pendaftaran Resmi • Jenjang SMP / MTs Sederajat</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-4xl text-on-surface tracking-tight">
              Formulir Pendaftaran Cabang {{ activeComp?.title || 'Baca Puisi' }}
            </h1>
            <p class="text-on-surface-variant text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
              Silakan lengkapi data delegasi sekolah, peserta, serta berkas lampiran Anda. Proses cepat, mudah, dan langsung terverifikasi oleh panitia.
            </p>
          </div>

          <!-- FORMULIR TERPUSAT (CLEAN & TO-THE-POINT) -->
          <form 
            id="publicRegisterForm" 
            class="form-card-box space-y-8" 
            novalidate 
            @submit.prevent="handleSubmit"
          >
            <!-- TOP VALIDATION SUMMARY ALERT (IF ANY ERRORS) -->
            <div 
              v-if="errorSummaryList.length > 0" 
              id="form-validation-summary" 
              class="validation-alert-banner"
            >
              <div class="flex items-center gap-2 font-bold text-red-600 dark:text-red-400 text-sm">
                <i class="ri-error-warning-fill text-lg"></i>
                <span>Mohon lengkapi {{ errorSummaryList.length }} isian wajib berikut:</span>
              </div>
              <ul class="val-alert-list mt-2 space-y-1 text-xs">
                <li 
                  v-for="(err, idx) in errorSummaryList" 
                  :key="idx" 
                  @click="scrollToField(err.key)" 
                  class="cursor-pointer hover:underline text-red-700 dark:text-red-300 flex items-center gap-1.5"
                >
                  <i class="ri-arrow-right-s-line text-xs"></i>
                  <span>{{ err.message }}</span>
                </li>
              </ul>
            </div>

            <!-- PILIH CABANG LOMBA INTEGRATED SELECTOR -->
            <div class="subcard-neutral-box space-y-2">
              <div class="flex items-center justify-between flex-wrap gap-2">
                <label class="block text-xs sm:text-sm font-semibold text-on-surface flex items-center gap-2">
                  <i class="ri-trophy-line text-primary text-[18px]"></i>
                  <span>Cabang Lomba yang Didaftarkan <span class="text-red-500">*</span></span>
                </label>
                <span class="text-[11px] font-semibold text-emerald-dark bg-emerald-light border border-primary/20 px-2.5 py-0.5 rounded-full">
                  {{ activeComp?.category ? `${activeComp.category} • ` : '' }}Biaya Satuan: Rp {{ baseCompFee.toLocaleString('id-ID') }}
                </span>
              </div>
              <CustomSelect
                v-model="form.competition_id"
                :options="competitionOptions"
                placeholder="Pilih Cabang Lomba..."
                size="md"
                searchable
              />
            </div>

            <!-- SECTION 1: Informasi Asal Sekolah & Guru Pembina -->
            <div class="space-y-4">
              <div class="flex items-center gap-2.5 pb-2 border-b border-border-subtle">
                <i class="ri-bank-line text-primary text-[22px]"></i>
                <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">1. Delegasi &amp; Asal Sekolah</h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                <!-- Nama Asal Sekolah with Autocomplete -->
                <div class="space-y-1.5 md:col-span-2 relative">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Nama Asal Sekolah / Madrasah <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      id="reg-sekolah"
                      v-model="form.school_name"
                      @input="onSchoolInput"
                      @focus="onSchoolInput"
                      @blur="onSchoolBlur"
                      class="clean-form-input"
                      :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('school_name') }"
                      placeholder="Contoh: MTs Unggulan Balung atau SMP Negeri 1 Jember" 
                      required 
                      type="text"
                    />
                    <span v-if="isSearchingSchools" class="absolute right-3.5 top-3 text-primary">
                      <i class="ri-loader-4-line animate-spin text-[18px]"></i>
                    </span>
                  </div>

                  <!-- Dropdown Suggestions Autocomplete -->
                  <div 
                    v-if="showSchoolDropdown && schoolSuggestions.length > 0" 
                    class="autocomplete-suggestions-box"
                  >
                    <div 
                      v-for="s in schoolSuggestions" 
                      :key="s.id || s.name"
                      @mousedown.prevent="selectSchool(s)"
                      class="autocomplete-row-item"
                    >
                      <div>
                        <p class="font-bold text-on-surface">{{ s.name }}</p>
                        <p class="text-[11px] text-on-surface-variant">{{ s.kecamatan ? `Kec. ${s.kecamatan}` : '' }} {{ s.jenjang || 'SMP/MTs' }}</p>
                      </div>
                      <span class="text-[10px] font-semibold text-primary bg-emerald-light px-2 py-0.5 rounded-full">Pilih</span>
                    </div>
                  </div>
                  <p v-if="getFieldError('school_name')" class="field-error-text">{{ getFieldError('school_name') }}</p>
                </div>

                <!-- Kecamatan Asal Sekolah (CustomSelect Non-Native) -->
                <div class="space-y-1.5" id="reg-kecamatan">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Kecamatan Asal Sekolah <span class="text-red-500">*</span>
                  </label>
                  <CustomSelect 
                    v-model="form.custom_fields['school_district']"
                    :options="kecamatanList"
                    placeholder="Pilih Kecamatan Asal Sekolah..."
                    size="md"
                    searchable
                    @change="clearFieldError('school_district')"
                  />
                  <p v-if="getFieldError('school_district')" class="field-error-text">{{ getFieldError('school_district') }}</p>
                </div>

                <!-- Alamat Lengkap Sekolah / Madrasah -->
                <div class="space-y-1.5" id="reg-alamat">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Alamat Lengkap Sekolah / Madrasah <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model="form.custom_fields['school_address']"
                    @input="clearFieldError('school_address')"
                    class="clean-form-input"
                    :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('school_address') }"
                    placeholder="Contoh: Jl. Puger No. 20 Balung"
                    type="text"
                  />
                  <p v-if="getFieldError('school_address')" class="field-error-text">{{ getFieldError('school_address') }}</p>
                </div>

                <!-- Nama Guru Pembina / Pendamping -->
                <div class="space-y-1.5" id="reg-pendamping">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Nama Guru Pembina / Pendamping <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-gray-400 text-[20px] pointer-events-none">
                      <i class="ri-user-line"></i>
                    </span>
                    <input 
                      v-model="form.mentor_name"
                      @input="clearFieldError('mentor_name')"
                      class="clean-form-input pl-10"
                      :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('mentor_name') }"
                      placeholder="Ust. Ahmad Hidayat, S.Pd." 
                      required 
                      type="text"
                    />
                  </div>
                  <p v-if="getFieldError('mentor_name')" class="field-error-text">{{ getFieldError('mentor_name') }}</p>
                </div>

                <!-- Nomor WhatsApp Aktif Pembina -->
                <div class="space-y-1.5" id="reg-wa">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Nomor WhatsApp Aktif Pembina <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-emerald-dark text-[20px] pointer-events-none">
                      <i class="ri-whatsapp-line"></i>
                    </span>
                    <input 
                      v-model="form.mentor_wa"
                      @input="clearFieldError('mentor_wa')"
                      class="clean-form-input pl-10"
                      :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('mentor_wa') }"
                      placeholder="0812-3456-7890 (Untuk Grup TM)" 
                      required 
                      type="tel"
                    />
                  </div>
                  <p v-if="getFieldError('mentor_wa')" class="field-error-text">{{ getFieldError('mentor_wa') }}</p>
                </div>

                <!-- Email Aktif Pembina / Official -->
                <div class="space-y-1.5 md:col-span-2" id="reg-email">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Email Aktif Pembina / Official <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-2.5 text-gray-400 text-[20px] pointer-events-none">
                      <i class="ri-mail-line"></i>
                    </span>
                    <input 
                      v-model="form.custom_fields['mentor_email']"
                      @input="clearFieldError('mentor_email')"
                      class="clean-form-input pl-10"
                      :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('mentor_email') }"
                      placeholder="pembina@sekolah.sch.id (Untuk Tanda Terima Resmi)" 
                      required 
                      type="email"
                    />
                  </div>
                  <p v-if="getFieldError('mentor_email')" class="field-error-text">{{ getFieldError('mentor_email') }}</p>
                </div>
              </div>
            </div>

            <!-- SECTION 2: Data Peserta & Skema Paket -->
            <div class="space-y-4" id="sectionDataPeserta">
              <div class="flex items-center justify-between pb-2 border-b border-border-subtle flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                  <i class="ri-shield-user-line text-primary text-[22px]"></i>
                  <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">2. Data Peserta &amp; Skema Paket</h2>
                </div>
                <span class="text-xs text-on-surface-variant font-medium">Maksimal 2 peserta per delegasi cabang puisi</span>
              </div>

              <!-- Pilihan Skema Paket Pendaftaran -->
              <div class="subcard-neutral-box space-y-2.5">
                <div class="flex items-center justify-between">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Pilihan Skema Paket Pendaftaran <span class="text-red-500">*</span>
                  </label>
                  <span class="text-[11px] text-primary font-semibold bg-emerald-light px-2 py-0.5 rounded-full">Biaya Fleksibel</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <!-- Paket Satuan -->
                  <label 
                    class="radio-package-card"
                    :class="{ 'is-selected': form.package_type === 'Paket Satuan' }"
                    @click="setPackageType('Paket Satuan')"
                  >
                    <div class="flex items-center gap-2.5">
                      <input 
                        type="radio" 
                        name="skema_paket" 
                        value="Paket Satuan" 
                        :checked="form.package_type === 'Paket Satuan'" 
                        class="w-4 h-4 text-primary focus:ring-primary"
                      />
                      <div class="text-left">
                        <div class="flex items-center gap-1.5">
                          <p class="text-xs sm:text-sm font-bold text-on-surface">Paket Satuan</p>
                          <span class="text-[10px] bg-primary/10 text-primary font-bold px-1.5 py-0.5 rounded">Cabang Ini</span>
                        </div>
                        <p class="text-xs font-semibold text-primary mt-0.5">Rp {{ baseCompFee.toLocaleString('id-ID') }}</p>
                        <p class="text-[11px] text-on-surface-variant">Khusus pendaftaran cabang {{ activeComp?.title || 'Baca Puisi' }}</p>
                      </div>
                    </div>
                    <i class="ri-price-tag-3-line text-primary text-[20px]"></i>
                  </label>

                  <!-- Paket All-In Komplit -->
                  <label 
                    class="radio-package-card"
                    :class="{ 'is-selected': form.package_type === 'Paket All-In' }"
                    @click="setPackageType('Paket All-In')"
                  >
                    <div class="flex items-center gap-2.5">
                      <input 
                        type="radio" 
                        name="skema_paket" 
                        value="Paket All-In" 
                        :checked="form.package_type === 'Paket All-In'" 
                        class="w-4 h-4 text-primary focus:ring-primary"
                      />
                      <div class="text-left">
                        <div class="flex items-center gap-1.5">
                          <p class="text-xs sm:text-sm font-bold text-on-surface">Paket All-In Komplit</p>
                          <span class="text-[10px] bg-amber-soft text-amber-dark font-bold px-1.5 py-0.5 rounded">Terlaris</span>
                        </div>
                        <p class="text-xs font-semibold text-emerald-dark mt-0.5">Rp {{ Number(pricingSettings?.allin_price || 250000).toLocaleString('id-ID') }}</p>
                        <p class="text-[11px] text-on-surface-variant">Semua cabang lomba kontingen sekolah</p>
                      </div>
                    </div>
                    <i class="ri-verified-badge-line text-primary text-[20px]"></i>
                  </label>
                </div>

                <!-- Input Kode Paket All-In Komplit -->
                <div class="package-code-box">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <label class="block text-xs font-semibold text-on-surface flex items-center gap-1.5">
                      <i class="ri-key-line text-primary text-[18px]"></i>
                      <span>Kode Paket Komplit (All-In) <span class="text-red-500">*</span></span>
                    </label>
                    <span class="text-[11px] text-amber-dark font-medium bg-amber-soft px-2 py-0.5 rounded">Khusus Paket All-In</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                      <input 
                        v-model="form.custom_fields['allin_code']"
                        type="text" 
                        placeholder="Masukkan kode verifikasi paket dari panitia kontingen (misal: PKT-ALLIN-XXXX)" 
                        class="w-full h-10 px-3.5 rounded-lg border border-border-subtle bg-white dark:bg-[#111d17] text-xs font-mono uppercase focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:font-body placeholder:normal-case placeholder:text-gray-400 text-on-surface"
                      />
                    </div>
                    <button 
                      type="button" 
                      @click="verifyPackageCodeManual" 
                      :disabled="isCheckingPackageCode"
                      class="h-10 px-4 rounded-lg bg-primary hover:bg-primary-container text-white text-xs font-semibold flex items-center gap-1.5 shrink-0 transition-colors shadow-xs cursor-pointer"
                    >
                      <i v-if="isCheckingPackageCode" class="ri-loader-4-line animate-spin text-[16px]"></i>
                      <i v-else class="ri-checkbox-circle-line text-[16px]"></i>
                      <span>Cek Kode</span>
                    </button>
                  </div>
                  <p v-if="codeVerificationError" class="text-xs text-red-500 font-medium">{{ codeVerificationError }}</p>
                  <p v-else class="text-[11px] text-on-surface-variant flex items-center gap-1">
                    <i class="ri-information-line text-[14px] text-primary"></i>
                    <span>Wajib diisi jika sekolah Anda telah mendaftarkan paket kolektif/all-in cabang lomba</span>
                  </p>
                </div>
              </div>

              <!-- Selector Jumlah Peserta (Tanpa Biaya Perkalian Berlebihan) -->
              <div class="subcard-neutral-box space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                  <div class="flex items-center gap-2">
                    <i class="ri-team-line text-primary text-[18px]"></i>
                    <label class="block text-xs sm:text-sm font-semibold text-on-surface">Jumlah Delegasi Peserta</label>
                  </div>
                  <span class="text-[11px] font-medium text-emerald-dark bg-emerald-light border border-primary/20 px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 w-fit">
                    <i class="ri-information-line text-[14px]"></i>
                    Maks. 2 Peserta per Sekolah/Madrasah
                  </span>
                </div>
                <div class="grid grid-cols-2 p-1 bg-white dark:bg-[#111d17] border border-border-subtle rounded-xl gap-1 shadow-xs">
                  <label 
                    class="participant-count-tab"
                    :class="{ 'is-active': participantCount === 1 }"
                    @click="setParticipantCount(1)"
                  >
                    <input type="radio" name="jumlah_peserta" value="1" :checked="participantCount === 1" class="hidden" />
                    <i class="ri-user-line text-[18px]"></i>
                    <span>1 Peserta (Tunggal)</span>
                  </label>
                  <label 
                    class="participant-count-tab"
                    :class="{ 'is-active': participantCount === 2 }"
                    @click="setParticipantCount(2)"
                  >
                    <input type="radio" name="jumlah_peserta" value="2" :checked="participantCount === 2" class="hidden" />
                    <i class="ri-team-line text-[18px]"></i>
                    <span>2 Peserta (Maksimal)</span>
                  </label>
                </div>
              </div>

              <!-- Kartu Peserta 1 -->
              <div class="participant-card-box">
                <div class="flex items-center justify-between border-b border-border-subtle pb-2">
                  <div class="flex items-center gap-2">
                    <span class="participant-number-badge">1</span>
                    <h3 class="font-headline font-bold text-sm text-on-surface">Data Peserta 1</h3>
                  </div>
                  <span class="text-[11px] font-semibold text-emerald-dark bg-emerald-light px-2 py-0.5 rounded-full">Peserta Utama</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
                  <!-- Nama Peserta 1 -->
                  <div class="space-y-1.5 md:col-span-6" id="reg-siswa-1">
                    <label class="block text-xs font-semibold text-on-surface">
                      Nama Lengkap Siswa <span class="text-red-500">*</span>
                    </label>
                    <input 
                      v-model="participants[0].name"
                      @input="clearFieldError('participant_0_name')"
                      class="clean-form-input"
                      :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('participant_0_name') }"
                      placeholder="Sesuai rapor / akta kelahiran" 
                      required 
                      type="text"
                    />
                    <p v-if="getFieldError('participant_0_name')" class="field-error-text">{{ getFieldError('participant_0_name') }}</p>
                  </div>

                  <!-- Kelas / Tingkat 1 (CustomSelect Non-Native) -->
                  <div class="space-y-1.5 md:col-span-3">
                    <label class="block text-xs font-semibold text-on-surface">
                      Kelas / Tingkat <span class="text-red-500">*</span>
                    </label>
                    <CustomSelect 
                      v-model="participants[0].grade"
                      :options="gradeOptions"
                      placeholder="Pilih Kelas..."
                      size="md"
                    />
                  </div>

                  <!-- Jenis Kelamin 1 -->
                  <div class="space-y-1.5 md:col-span-3">
                    <label class="block text-xs font-semibold text-on-surface">
                      Jenis Kelamin <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 h-11">
                      <label 
                        class="gender-radio-pill"
                        :class="{ 'is-checked': participants[0].gender === 'L' }"
                        @click="participants[0].gender = 'L'"
                      >
                        <input type="radio" name="gender_1" value="L" :checked="participants[0].gender === 'L'" class="hidden" />
                        <span>Laki-Laki</span>
                      </label>
                      <label 
                        class="gender-radio-pill"
                        :class="{ 'is-checked': participants[0].gender === 'P' }"
                        @click="participants[0].gender = 'P'"
                      >
                        <input type="radio" name="gender_1" value="P" :checked="participants[0].gender === 'P'" class="hidden" />
                        <span>Perempuan</span>
                      </label>
                    </div>
                  </div>

                  <!-- NISN Siswa 1 -->
                  <div class="space-y-1.5 md:col-span-12">
                    <label class="block text-xs font-semibold text-on-surface flex items-center justify-between">
                      <span>Nomor Induk Siswa Nasional (NISN)</span>
                      <span class="text-[11px] text-on-surface-variant font-normal">Opsional</span>
                    </label>
                    <input 
                      v-model="participants[0].nisn"
                      class="clean-form-input"
                      placeholder="10 digit nomor NISN resmi dari Kemdikbud / Kemenag" 
                      type="text"
                    />
                  </div>
                </div>
              </div>

              <!-- Kartu Peserta 2 (Dinamis / Opsional) -->
              <div 
                v-if="participantCount === 2" 
                class="participant-card-box border-dashed border-primary/40 animate-fadeIn"
              >
                <div class="flex items-center justify-between border-b border-border-subtle pb-2">
                  <div class="flex items-center gap-2">
                    <span class="participant-number-badge">2</span>
                    <h3 class="font-headline font-bold text-sm text-on-surface">Data Peserta 2</h3>
                  </div>
                  <span class="text-[11px] font-semibold text-primary bg-emerald-light px-2 py-0.5 rounded-full">Peserta 2 (Tambahan)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
                  <!-- Nama Peserta 2 -->
                  <div class="space-y-1.5 md:col-span-6" id="reg-siswa-2">
                    <label class="block text-xs font-semibold text-on-surface">
                      Nama Lengkap Siswa <span class="text-red-500">*</span>
                    </label>
                    <input 
                      v-model="participants[1].name"
                      @input="clearFieldError('participant_1_name')"
                      class="clean-form-input"
                      :class="{ 'border-red-500 ring-1 ring-red-500': hasFieldError('participant_1_name') }"
                      placeholder="Sesuai rapor / akta kelahiran" 
                      required 
                      type="text"
                    />
                    <p v-if="getFieldError('participant_1_name')" class="field-error-text">{{ getFieldError('participant_1_name') }}</p>
                  </div>

                  <!-- Kelas / Tingkat 2 (CustomSelect Non-Native) -->
                  <div class="space-y-1.5 md:col-span-3">
                    <label class="block text-xs font-semibold text-on-surface">
                      Kelas / Tingkat <span class="text-red-500">*</span>
                    </label>
                    <CustomSelect 
                      v-model="participants[1].grade"
                      :options="gradeOptions"
                      placeholder="Pilih Kelas..."
                      size="md"
                    />
                  </div>

                  <!-- Jenis Kelamin 2 -->
                  <div class="space-y-1.5 md:col-span-3">
                    <label class="block text-xs font-semibold text-on-surface">
                      Jenis Kelamin <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 h-11">
                      <label 
                        class="gender-radio-pill"
                        :class="{ 'is-checked': participants[1].gender === 'L' }"
                        @click="participants[1].gender = 'L'"
                      >
                        <input type="radio" name="gender_2" value="L" :checked="participants[1].gender === 'L'" class="hidden" />
                        <span>Laki-Laki</span>
                      </label>
                      <label 
                        class="gender-radio-pill"
                        :class="{ 'is-checked': participants[1].gender === 'P' }"
                        @click="participants[1].gender = 'P'"
                      >
                        <input type="radio" name="gender_2" value="P" :checked="participants[1].gender === 'P'" class="hidden" />
                        <span>Perempuan</span>
                      </label>
                    </div>
                  </div>

                  <!-- NISN Siswa 2 -->
                  <div class="space-y-1.5 md:col-span-12">
                    <label class="block text-xs font-semibold text-on-surface flex items-center justify-between">
                      <span>Nomor Induk Siswa Nasional (NISN)</span>
                      <span class="text-[11px] text-on-surface-variant font-normal">Opsional</span>
                    </label>
                    <input 
                      v-model="participants[1].nisn"
                      class="clean-form-input"
                      placeholder="10 digit nomor NISN resmi dari Kemdikbud / Kemenag" 
                      type="text"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 3: Lampiran Berkas Naskah & Kartu Pelajar -->
            <div class="space-y-4">
              <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                <div class="flex items-center gap-2.5">
                  <i class="ri-file-upload-line text-primary text-[22px]"></i>
                  <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">3. Naskah Puisi &amp; Berkas</h2>
                </div>
                <span class="text-xs text-on-surface-variant">PDF/ZIP (Maks 10 MB)</span>
              </div>

              <!-- Dashed File Dropzone -->
              <div 
                class="dashed-file-dropzone"
                @click="naskahInputRef?.click()"
              >
                <input 
                  ref="naskahInputRef"
                  type="file" 
                  class="hidden" 
                  accept=".pdf,.zip,.rar,.doc,.docx"
                  @change="handleNaskahChange"
                />
                <div class="flex items-center gap-3.5">
                  <div class="dropzone-icon-box">
                    <i class="ri-file-text-line text-[24px]"></i>
                  </div>
                  <div class="text-left">
                    <p class="text-sm font-semibold text-on-surface">
                      {{ selectedNaskahFile ? selectedNaskahFile.name : 'Upload Naskah Puisi & Kartu Pelajar' }}
                    </p>
                    <p class="text-xs text-on-surface-variant">
                      {{ selectedNaskahFile ? `${formatFileSize(selectedNaskahFile.size)} • Berkas siap dikirim` : 'Klik untuk memilih berkas lampiran (Bisa disusulkan saat TM)' }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <button 
                    class="btn-pick-file-trigger" 
                    type="button"
                  >
                    {{ selectedNaskahFile ? 'Ganti Berkas' : 'Pilih Dokumen' }}
                  </button>
                  <button 
                    v-if="selectedNaskahFile"
                    type="button" 
                    @click.stop="clearNaskahFile" 
                    class="btn-delete-file-trigger"
                    title="Hapus Berkas"
                  >
                    <i class="ri-delete-bin-line"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- SECTION 4: Pembayaran Ringkas -->
            <div class="space-y-4" id="sectionPembayaran">
              <div class="flex items-center justify-between pb-2 border-b border-border-subtle flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                  <i class="ri-wallet-3-line text-primary text-[22px]"></i>
                  <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">4. Metode Pembayaran &amp; Biaya</h2>
                </div>
                <span class="text-xs font-bold text-primary bg-emerald-light px-2.5 py-1 rounded-full">
                  Biaya: Rp {{ totalCalculatedFee.toLocaleString('id-ID') }} ({{ form.package_type }})
                </span>
              </div>

              <!-- Pilihan Saluran Pembayaran (3 Cards) -->
              <div class="space-y-2.5">
                <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                  Pilih Saluran Pembayaran <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <!-- Pilihan 1: BSI -->
                  <label 
                    class="payment-channel-card"
                    :class="{ 'is-selected': form.payment_method === 'Transfer Bank BSI' }"
                    @click="selectPaymentMethod('Transfer Bank BSI')"
                  >
                    <div class="flex items-center justify-between w-full">
                      <div class="flex items-center gap-2">
                        <input 
                          type="radio" 
                          name="payment_method" 
                          value="Transfer Bank BSI" 
                          :checked="form.payment_method === 'Transfer Bank BSI'" 
                          class="w-4 h-4 text-primary focus:ring-primary"
                        />
                        <span class="text-xs font-bold text-primary uppercase">Transfer BSI</span>
                      </div>
                      <i class="ri-bank-line text-primary text-[18px]"></i>
                    </div>
                    <div>
                      <div class="flex items-center justify-between">
                        <p class="font-mono text-sm font-bold text-on-surface">7124686746</p>
                        <button 
                          type="button" 
                          @click.stop="copyAccount('7124686746')" 
                          class="p-1 text-on-surface-variant hover:text-primary cursor-pointer" 
                          title="Salin No. Rekening"
                        >
                          <i class="ri-file-copy-line text-[16px]"></i>
                        </button>
                      </div>
                      <p class="text-[11px] text-on-surface-variant">a.n. MARIATUL AWALIYAH</p>
                    </div>
                  </label>

                  <!-- Pilihan 2: BRI -->
                  <label 
                    class="payment-channel-card"
                    :class="{ 'is-selected': form.payment_method === 'Transfer Bank BRI' }"
                    @click="selectPaymentMethod('Transfer Bank BRI')"
                  >
                    <div class="flex items-center justify-between w-full">
                      <div class="flex items-center gap-2">
                        <input 
                          type="radio" 
                          name="payment_method" 
                          value="Transfer Bank BRI" 
                          :checked="form.payment_method === 'Transfer Bank BRI'" 
                          class="w-4 h-4 text-primary focus:ring-primary"
                        />
                        <span class="text-xs font-bold text-amber-dark uppercase">Transfer BRI</span>
                      </div>
                      <i class="ri-bank-line text-amber-dark text-[18px]"></i>
                    </div>
                    <div>
                      <div class="flex items-center justify-between">
                        <p class="font-mono text-sm font-bold text-on-surface">6221 0102 2421 537</p>
                        <button 
                          type="button" 
                          @click.stop="copyAccount('622101022421537')" 
                          class="p-1 text-on-surface-variant hover:text-primary cursor-pointer" 
                          title="Salin No. Rekening"
                        >
                          <i class="ri-file-copy-line text-[16px]"></i>
                        </button>
                      </div>
                      <p class="text-[11px] text-on-surface-variant">a.n. MARIATUL AWALIYAH</p>
                    </div>
                  </label>

                  <!-- Pilihan 3: Cash / Tunai -->
                  <label 
                    class="payment-channel-card"
                    :class="{ 'is-selected': form.payment_method === 'Tunai di Sekretariat' }"
                    @click="selectPaymentMethod('Tunai di Sekretariat')"
                  >
                    <div class="flex items-center justify-between w-full">
                      <div class="flex items-center gap-2">
                        <input 
                          type="radio" 
                          name="payment_method" 
                          value="Tunai di Sekretariat" 
                          :checked="form.payment_method === 'Tunai di Sekretariat'" 
                          class="w-4 h-4 text-primary focus:ring-primary"
                        />
                        <span class="text-xs font-bold text-emerald-dark uppercase">Tunai / Cash</span>
                      </div>
                      <i class="ri-money-dollar-circle-line text-emerald-dark text-[18px]"></i>
                    </div>
                    <div>
                      <p class="text-xs font-bold text-on-surface">Sekretariat Panitia</p>
                      <p class="text-[11px] text-on-surface-variant">MAS Wahid Hasyim Balung</p>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Upload Bukti Transfer (Opsional / Langsung) -->
              <div class="subcard-neutral-box space-y-2.5">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-semibold text-on-surface flex items-center gap-1.5">
                    <i class="ri-bill-line text-primary text-[18px]"></i>
                    <span>Upload Bukti Pembayaran / Transfer</span>
                  </label>
                  <span class="text-[11px] text-on-surface-variant font-medium">Bisa disusulkan ke WhatsApp</span>
                </div>
                <div class="flex items-center gap-3">
                  <input 
                    ref="paymentProofRef"
                    type="file" 
                    class="hidden" 
                    accept="image/*,.pdf"
                    @change="handlePaymentProofChange"
                  />
                  <button 
                    type="button" 
                    @click="paymentProofRef?.click()" 
                    class="btn-pick-file-trigger flex items-center gap-2"
                  >
                    <i class="ri-image-add-line text-[18px] text-primary"></i>
                    <span>{{ form.payment_proof ? 'Ganti Bukti' : 'Pilih Bukti Transfer' }}</span>
                  </button>
                  <div class="flex items-center gap-2">
                    <span class="text-xs text-on-surface-variant truncate max-w-xs">
                      {{ form.payment_proof ? `${form.payment_proof.name} (${formatFileSize(form.payment_proof.size)})` : 'Format: JPG, PNG, atau PDF (Maks 5 MB)' }}
                    </span>
                    <button 
                      v-if="form.payment_proof"
                      type="button" 
                      @click.stop="clearPaymentProof" 
                      class="text-xs text-red-500 hover:text-red-700 p-1"
                      title="Hapus Bukti"
                    >
                      <i class="ri-close-circle-line text-base"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 5: Ringkasan Delegasi & Skema Biaya -->
            <div class="space-y-6 pt-4 border-t border-border-subtle">
              <div class="summary-receipt-box space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-primary/10 flex-wrap gap-2">
                  <div class="flex items-center gap-2">
                    <i class="ri-file-list-3-line text-primary text-[20px]"></i>
                    <h3 class="font-headline font-bold text-sm sm:text-base text-on-surface">Ringkasan Delegasi &amp; Pendaftaran</h3>
                  </div>
                  <span class="text-[11px] font-bold text-emerald-dark bg-emerald-light px-2.5 py-0.5 rounded-full border border-primary/20">Resmi Panitia</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                    <span class="text-on-surface-variant">Cabang Lomba:</span>
                    <span class="font-semibold text-on-surface">{{ activeComp?.title || 'Baca Puisi' }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                    <span class="text-on-surface-variant">Skema Paket:</span>
                    <span class="font-bold text-primary">{{ form.package_type }} (Rp {{ totalCalculatedFee.toLocaleString('id-ID') }})</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-dashed border-primary/10 sm:border-b-0">
                    <span class="text-on-surface-variant">Jumlah Delegasi Cabang:</span>
                    <span class="font-semibold text-on-surface">{{ participantCount }} Siswa (@ Rp {{ baseCompFee.toLocaleString('id-ID') }})</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-dashed border-primary/10 sm:border-b-0">
                    <span class="text-on-surface-variant">Metode Bayar:</span>
                    <span class="font-semibold text-on-surface">{{ form.payment_method }}</span>
                  </div>
                </div>
                <div class="pt-3 border-t border-primary/20 flex items-center justify-between flex-wrap gap-2">
                  <div>
                    <p class="text-xs text-on-surface-variant font-medium">Total Biaya Pendaftaran</p>
                    <p class="font-headline font-extrabold text-xl sm:text-2xl text-primary tracking-tight">
                      Rp {{ totalCalculatedFee.toLocaleString('id-ID') }}
                    </p>
                  </div>
                  <div class="text-right">
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-on-surface-variant bg-white dark:bg-[#111d17] px-2.5 py-1 rounded-lg border border-border-subtle">
                      <i class="ri-verified-badge-line text-primary text-[14px]"></i> Bebas Biaya Admin
                    </span>
                  </div>
                </div>
              </div>

              <!-- SUBMIT ACTION CTA BUTTON -->
              <div class="space-y-3">
                <button 
                  id="submitBtnCta" 
                  class="btn-cta-submit" 
                  type="submit"
                  :disabled="form.processing"
                >
                  <i v-if="form.processing" class="ri-loader-4-line animate-spin text-[22px]"></i>
                  <i v-else class="ri-send-plane-fill text-[22px]"></i>
                  <span>{{ form.processing ? 'Mengirim Formulir...' : `Kirim Formulir & Bayar • Rp ${totalCalculatedFee.toLocaleString('id-ID')}` }}</span>
                </button>
                <div class="flex items-center justify-center gap-2 text-xs text-on-surface-variant text-center">
                  <i class="ri-verified-badge-line text-primary text-[16px]"></i>
                  <span>Data pendaftaran resmi terintegrasi langsung dengan Panitia Semarak Aliyah XIV 2026.</span>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </main>

    <!-- ======================================================================
         FOOTER PUBLIK SEDERHANA
         ====================================================================== -->
    <footer class="page-bottom-footer">
      <div class="footer-inner-container">
        <div class="flex items-center gap-3">
          <img alt="Logo MAS Wahid Hasyim" class="h-8 w-8 object-contain" src="/assets/img/logo.webp" />
          <div>
            <p class="font-headline font-bold text-sm text-on-surface">SEMARAK ALIYAH XIV 2026</p>
            <p class="text-xs text-on-surface-variant">Madrasah Aliyah Unggulan Wahid Hasyim Balung, Jember</p>
          </div>
        </div>
        <p class="text-xs text-on-surface-variant">
          © 2026 MAS Wahid Hasyim Balung. Hak Cipta Dilindungi.
        </p>
      </div>
    </footer>

    <!-- ======================================================================
         NOTIFICATION TOAST (Floating Bottom Right)
         ====================================================================== -->
    <div 
      class="toast-notification-wrap" 
      :class="{ 'is-visible': toast.show }"
    >
      <div class="toast-inner-pill">
        <i :class="toast.icon" class="text-emerald-400 text-[20px]"></i>
        <span>{{ toast.message }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ==========================================================================
   SEMANTIC COLOR SYSTEM & LAYOUT RULES
   ========================================================================== */
.semarak-portal-page {
  --color-primary: #006948;
  --color-primary-container: #00855d;
  --color-background: #f7faf7;
  --color-surface-card: #FFFFFF;
  --color-on-surface: #171d19;
  --color-on-surface-variant: #3d4a42;
  --color-border-subtle: #E2E8F0;
  --color-emerald-light: #ECFDF5;
  --color-emerald-dark: #065F46;
  --color-amber-soft: #FEF3C7;
  --color-amber-dark: #B45309;

  background-color: var(--color-background);
  color: var(--color-on-surface);
  font-family: var(--font-body, "Inter", sans-serif);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Dark Mode Overrides */
.semarak-portal-page.dark {
  --color-primary: #10b981;
  --color-primary-container: #059669;
  --color-background: #09110d;
  --color-surface-card: #111d17;
  --color-on-surface: #f8fafc;
  --color-on-surface-variant: #94a3b8;
  --color-border-subtle: #1e3529;
  --color-emerald-light: rgba(6, 95, 70, 0.35);
  --color-emerald-dark: #86efac;
  --color-amber-soft: rgba(180, 83, 9, 0.25);
  --color-amber-dark: #fde047;
}

/* ==========================================================================
   HEADER BAR
   ========================================================================== */
.page-top-header {
  position: sticky;
  top: 0;
  z-index: 40;
  width: 100%;
  background-color: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--color-border-subtle);
}

.semarak-portal-page.dark .page-top-header {
  background-color: rgba(17, 29, 23, 0.95);
}

.header-inner-container {
  max-width: 72rem;
  margin-left: auto;
  margin-right: auto;
  padding-left: 1rem;
  padding-right: 1rem;
  height: 5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

@media (min-width: 640px) {
  .header-inner-container {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

.brand-link-group {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  text-decoration: none;
}

.brand-logo-img {
  height: 3rem;
  width: 3rem;
  object-fit: contain;
  transition: transform 0.2s;
}

.brand-link-group:hover .brand-logo-img {
  transform: scale(1.05);
}

.brand-text-column {
  display: flex;
  flex-direction: column;
}

.brand-title-text {
  font-family: var(--font-headline, "Plus Jakarta Sans", sans-serif);
  font-weight: 800;
  font-size: 1rem;
  color: var(--color-primary);
  letter-spacing: -0.025em;
  line-height: 1.2;
  text-transform: uppercase;
}

@media (min-width: 640px) {
  .brand-title-text {
    font-size: 1.125rem;
  }
}

.brand-subtitle-text {
  font-family: var(--font-headline, "Plus Jakarta Sans", sans-serif);
  font-size: 0.75rem;
  color: var(--color-on-surface-variant);
  font-weight: 500;
}

@media (min-width: 640px) {
  .brand-subtitle-text {
    font-size: 0.875rem;
  }
}

.header-nav-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

@media (min-width: 640px) {
  .header-nav-actions {
    gap: 1rem;
  }
}

.nav-link-subtle {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-on-surface-variant);
  text-decoration: none;
  transition: color 0.15s;
}

.nav-link-subtle:hover {
  color: var(--color-primary);
}

.nav-btn-juknis {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-on-surface-variant);
  text-decoration: none;
  padding: 0.375rem 0.75rem;
  border-radius: 0.5rem;
  transition: all 0.15s;
}

.nav-btn-juknis:hover {
  color: var(--color-primary);
  background-color: var(--color-emerald-light);
}

.nav-btn-wa {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.875rem;
  border-radius: 0.5rem;
  background-color: var(--color-emerald-light);
  color: var(--color-emerald-dark);
  font-weight: 600;
  font-size: 0.75rem;
  text-decoration: none;
  border: 1px solid rgba(0, 105, 72, 0.2);
  transition: all 0.15s;
}

@media (min-width: 640px) {
  .nav-btn-wa {
    font-size: 0.875rem;
  }
}

.nav-btn-wa:hover {
  background-color: var(--color-primary);
  color: #ffffff;
  border-color: transparent;
}

.btn-theme-toggle {
  padding: 0.5rem;
  border-radius: 0.5rem;
  border: 1px solid var(--color-border-subtle);
  background: transparent;
  color: var(--color-on-surface-variant);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.btn-theme-toggle:hover {
  background-color: var(--color-emerald-light);
  color: var(--color-primary);
}

/* ==========================================================================
   MAIN CONTENT WRAPPER
   ========================================================================== */
.page-main-container {
  flex: 1;
  padding-top: 2rem;
  padding-bottom: 3rem;
  padding-left: 1rem;
  padding-right: 1rem;
}

@media (min-width: 640px) {
  .page-main-container {
    padding-top: 3rem;
    padding-bottom: 4rem;
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

.main-max-wrapper {
  max-width: 56rem; /* max-w-4xl */
  margin-left: auto;
  margin-right: auto;
}

.hero-status-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.875rem;
  border-radius: 9999px;
  background-color: var(--color-emerald-light);
  color: var(--color-emerald-dark);
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid rgba(0, 105, 72, 0.2);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

@media (min-width: 640px) {
  .hero-status-pill {
    font-size: 0.875rem;
  }
}

.pulsing-emerald-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
  background-color: var(--color-primary);
  animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulseDot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(1.2); }
}

/* ==========================================================================
   FORM CONTAINER CARD
   ========================================================================== */
.form-card-box {
  background-color: var(--color-surface-card);
  border-radius: 1rem;
  border: 1px solid var(--color-border-subtle);
  box-shadow: 0 20px 25px -5px rgba(0, 105, 72, 0.05), 0 8px 10px -6px rgba(0, 105, 72, 0.05);
  padding: 1.5rem;
}

@media (min-width: 640px) {
  .form-card-box {
    padding: 2.5rem;
  }
}

.success-box-paddings {
  padding: 2rem 1.5rem;
}

@media (min-width: 640px) {
  .success-box-paddings {
    padding: 3rem 2.5rem;
  }
}

.success-icon-badge {
  width: 4rem;
  height: 4rem;
  border-radius: 9999px;
  background-color: var(--color-emerald-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.25rem;
  margin: 0 auto;
}

.success-receipt-card {
  max-width: 36rem;
  margin: 1.75rem auto;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-subtle);
  background-color: rgba(247, 250, 247, 0.6);
  padding: 1.25rem;
  text-align: left;
}

.semarak-portal-page.dark .success-receipt-card {
  background-color: rgba(22, 37, 30, 0.6);
}

.receipt-item-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px dashed var(--color-border-subtle);
  font-size: 0.875rem;
}

.receipt-item-row.total-row-highlight {
  border-bottom: none;
  padding-top: 0.75rem;
  margin-top: 0.5rem;
  border-top: 1px solid var(--color-border-subtle);
}

.r-label {
  color: var(--color-on-surface-variant);
}

.r-value {
  color: var(--color-on-surface);
  font-weight: 600;
}

.r-value.code-bold {
  font-family: monospace;
  font-weight: 700;
  color: var(--color-primary);
}

.btn-secondary-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-subtle);
  background-color: var(--color-surface-card);
  color: var(--color-on-surface);
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s;
}

.btn-secondary-action:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.btn-primary-action {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 0.75rem;
  background-color: var(--color-primary);
  color: #ffffff;
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-primary-action:hover {
  background-color: var(--color-primary-container);
}

/* ==========================================================================
   INPUTS & SUBCARDS
   ========================================================================== */
.clean-form-input {
  width: 100%;
  height: 2.75rem; /* 44px (h-11) */
  padding-left: 0.875rem;
  padding-right: 0.875rem;
  border-radius: 0.75rem; /* 12px (rounded-xl) */
  border: 1px solid var(--color-border-subtle);
  background-color: rgba(247, 250, 247, 0.5);
  font-size: 0.875rem;
  color: var(--color-on-surface);
  outline: none;
  transition: all 0.15s;
}

.semarak-portal-page.dark .clean-form-input {
  background-color: rgba(17, 29, 23, 0.6);
}

.clean-form-input:focus {
  background-color: var(--color-surface-card);
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(0, 105, 72, 0.2);
}

.clean-form-input.pl-10 {
  padding-left: 2.5rem;
}

.field-error-text {
  font-size: 0.75rem;
  color: #ef4444;
  font-weight: 500;
  margin-top: 0.25rem;
}

.subcard-neutral-box {
  padding: 1rem;
  border-radius: 0.75rem;
  background-color: rgba(247, 250, 247, 0.5);
  border: 1px solid var(--color-border-subtle);
}

.semarak-portal-page.dark .subcard-neutral-box {
  background-color: rgba(17, 29, 23, 0.5);
}

.radio-package-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-subtle);
  background-color: var(--color-surface-card);
  cursor: pointer;
  transition: all 0.15s;
}

.radio-package-card:hover {
  border-color: rgba(0, 105, 72, 0.6);
}

.radio-package-card.is-selected {
  border-color: var(--color-primary);
  background-color: var(--color-emerald-light);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.package-code-box {
  margin-top: 0.75rem;
  padding: 0.875rem;
  border-radius: 0.75rem;
  border: 1px dashed rgba(0, 105, 72, 0.4);
  background-color: rgba(236, 253, 245, 0.3);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.semarak-portal-page.dark .package-code-box {
  background-color: rgba(6, 95, 70, 0.15);
}

.participant-count-tab {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-on-surface-variant);
  transition: all 0.15s;
}

@media (min-width: 640px) {
  .participant-count-tab {
    font-size: 0.875rem;
  }
}

.participant-count-tab:hover {
  color: var(--color-primary);
}

.participant-count-tab.is-active {
  background-color: var(--color-emerald-light);
  color: var(--color-primary);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 105, 72, 0.2);
}

.participant-card-box {
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-subtle);
  background-color: var(--color-surface-card);
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

@media (min-width: 640px) {
  .participant-card-box {
    padding: 1.25rem;
  }
}

.participant-number-badge {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 9999px;
  background-color: var(--color-emerald-light);
  color: var(--color-emerald-dark);
  font-family: var(--font-headline, "Plus Jakarta Sans", sans-serif);
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.gender-radio-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--color-border-subtle);
  border-radius: 0.75rem;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-on-surface-variant);
  transition: all 0.15s;
}

.gender-radio-pill:hover {
  background-color: rgba(236, 253, 245, 0.4);
}

.gender-radio-pill.is-checked {
  background-color: var(--color-emerald-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

/* ==========================================================================
   FILE DROPZONE
   ========================================================================== */
.dashed-file-dropzone {
  border: 2px dashed var(--color-border-subtle);
  border-radius: 0.75rem;
  padding: 1.25rem;
  background-color: rgba(247, 250, 247, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  cursor: pointer;
  transition: all 0.15s;
}

@media (min-width: 640px) {
  .dashed-file-dropzone {
    flex-direction: row;
  }
}

.semarak-portal-page.dark .dashed-file-dropzone {
  background-color: rgba(17, 29, 23, 0.5);
}

.dashed-file-dropzone:hover {
  border-color: rgba(0, 105, 72, 0.6);
  background-color: rgba(236, 253, 245, 0.2);
}

.dropzone-icon-box {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.75rem;
  background-color: var(--color-emerald-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.btn-pick-file-trigger {
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  background-color: var(--color-surface-card);
  border: 1px solid var(--color-border-subtle);
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-on-surface);
  cursor: pointer;
  transition: all 0.15s;
}

.btn-pick-file-trigger:hover {
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.btn-delete-file-trigger {
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  background-color: #fee2e2;
  color: #dc2626;
  border: none;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-delete-file-trigger:hover {
  background-color: #fecaca;
}

/* ==========================================================================
   PAYMENT CHANNELS
   ========================================================================== */
.payment-channel-card {
  padding: 0.875rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-subtle);
  background-color: var(--color-surface-card);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 0.5rem;
  transition: all 0.15s;
}

.payment-channel-card:hover {
  border-color: rgba(0, 105, 72, 0.6);
}

.payment-channel-card.is-selected {
  border-color: var(--color-primary);
  background-color: var(--color-emerald-light);
  box-shadow: 0 0 0 1px var(--color-primary);
}

/* ==========================================================================
   SUMMARY & CTA
   ========================================================================== */
.summary-receipt-box {
  border-radius: 0.75rem;
  border: 1px solid rgba(0, 105, 72, 0.2);
  background-color: rgba(236, 253, 245, 0.3);
  padding: 1.25rem;
}

.semarak-portal-page.dark .summary-receipt-box {
  background-color: rgba(6, 95, 70, 0.15);
}

.btn-cta-submit {
  width: 100%;
  padding-top: 1rem;
  padding-bottom: 1rem;
  border-radius: 0.75rem;
  background-color: var(--color-primary);
  color: #ffffff;
  font-family: var(--font-headline, "Plus Jakarta Sans", sans-serif);
  font-weight: 700;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
  border: none;
  box-shadow: 0 10px 15px -3px rgba(0, 105, 72, 0.2);
  cursor: pointer;
  transition: all 0.15s;
}

@media (min-width: 640px) {
  .btn-cta-submit {
    font-size: 1.125rem;
  }
}

.btn-cta-submit:hover:not(:disabled) {
  background-color: var(--color-primary-container);
  box-shadow: 0 15px 20px -3px rgba(0, 105, 72, 0.3);
}

.btn-cta-submit:active:not(:disabled) {
  transform: scale(0.99);
}

.btn-cta-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* ==========================================================================
   AUTOCOMPLETE & VALIDATION BANNER
   ========================================================================== */
.autocomplete-suggestions-box {
  position: absolute;
  left: 0;
  right: 0;
  top: 100%;
  margin-top: 0.25rem;
  background-color: var(--color-surface-card);
  border: 1px solid var(--color-border-subtle);
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  z-index: 30;
  max-height: 14rem;
  overflow-y: auto;
  padding: 0.375rem;
}

.autocomplete-row-item {
  padding: 0.625rem;
  border-radius: 0.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.75rem;
  transition: background-color 0.15s;
}

.autocomplete-row-item:hover {
  background-color: var(--color-emerald-light);
}

.validation-alert-banner {
  padding: 1rem 1.25rem;
  border-radius: 0.75rem;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
}

.semarak-portal-page.dark .validation-alert-banner {
  background-color: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.3);
}

/* ==========================================================================
   FOOTER
   ========================================================================== */
.page-bottom-footer {
  width: 100%;
  background-color: var(--color-surface-card);
  border-top: 1px solid var(--color-border-subtle);
  padding: 2rem 1rem;
  margin-top: 3rem;
}

@media (min-width: 640px) {
  .page-bottom-footer {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

.footer-inner-container {
  max-width: 72rem;
  margin-left: auto;
  margin-right: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  text-align: center;
}

@media (min-width: 640px) {
  .footer-inner-container {
    flex-direction: row;
    text-align: left;
  }
}

/* ==========================================================================
   TOAST NOTIFICATION
   ========================================================================== */
.toast-notification-wrap {
  position: fixed;
  bottom: 1.5rem;
  right: 1.5rem;
  z-index: 50;
  transform: translateY(6rem);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  pointer-events: none;
}

.toast-notification-wrap.is-visible {
  transform: translateY(0);
  opacity: 1;
}

.toast-inner-pill {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  background-color: #0f172a;
  color: #ffffff;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  font-size: 0.875rem;
}

/* ==========================================================================
   CUSTOMSELECT OVERRIDES
   ========================================================================== */
:deep(.custom-select-trigger) {
  height: 2.75rem !important; /* 44px */
  border-radius: 0.75rem !important; /* 12px */
  background-color: rgba(247, 250, 247, 0.5) !important;
  border-color: var(--color-border-subtle) !important;
  font-size: 0.875rem !important;
  color: var(--color-on-surface) !important;
}

.semarak-portal-page.dark :deep(.custom-select-trigger) {
  background-color: rgba(17, 29, 23, 0.6) !important;
  border-color: var(--color-border-subtle) !important;
  color: var(--color-on-surface) !important;
}

:deep(.custom-select-trigger:hover) {
  border-color: rgba(0, 105, 72, 0.6) !important;
}

:deep(.custom-select-trigger.is-open) {
  border-color: var(--color-primary) !important;
  box-shadow: 0 0 0 2px rgba(0, 105, 72, 0.2) !important;
  background-color: var(--color-surface-card) !important;
}

:deep(.custom-select-dropdown) {
  border-radius: 0.75rem !important;
  border-color: var(--color-border-subtle) !important;
  background-color: var(--color-surface-card) !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fadeIn {
  animation: fadeIn 0.2s ease-out forwards;
}
</style>
