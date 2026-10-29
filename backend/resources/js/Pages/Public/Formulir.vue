<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
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
    school_district: props.authRegistrant?.school_district || 'Balung',
    school_address: props.authRegistrant?.school_name || '-',
    mentor_email: props.authRegistrant?.email || 'pembina@sekolah.sch.id',
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
// School Search & Autocomplete
// -----------------------------------------------------------------------------
const schoolSuggestions = ref([]);
const isSearchingSchools = ref(false);
const showSchoolDropdown = ref(false);
let schoolSearchTimeout = null;

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
  showSchoolDropdown.value = false;
  schoolSuggestions.value = [];
  showToast(`Sekolah terpilih: ${s.name}`);
}

// -----------------------------------------------------------------------------
// Participant Count & Dynamic Repeater
// -----------------------------------------------------------------------------
const participantCount = ref(1);

const participants = ref([
  { name: '', grade: 'Kelas 9 SMP / MTs', gender: 'L' },
  { name: '', grade: 'Kelas 9 SMP / MTs', gender: 'L' }
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
    showToast('Skema dipilih: Paket All-In Komplit');
  } else {
    showToast('Skema dipilih: Paket Satuan');
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
  showToast(`Nomor rekening ${accNum} disalin!`);
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
// Validation & Field Error Handling
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

function validateForm() {
  submitAttempted.value = true;
  const errs = {};

  if (!form.school_name || !form.school_name.trim()) {
    errs['school_name'] = 'Nama Asal Sekolah / Madrasah wajib diisi.';
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
    showToast('Mohon lengkapi isian wajib yang ditandai merah!', 'ri-error-warning-fill');
    nextTick(() => {
      const firstErr = document.querySelector('.border-red-500');
      firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstErr?.focus?.();
    });
    return;
  }

  // Prepare participants text
  const activeParticipants = participants.value.slice(0, participantCount.value);
  form.participants_names = activeParticipants
    .map((p, idx) => `${idx + 1}. ${p.name} (${p.grade}, ${p.gender === 'L' ? 'Laki-Laki' : 'Perempuan'})`)
    .join('\n');

  // Sync custom_fields
  if (!form.custom_fields) form.custom_fields = {};
  form.custom_fields.biodata_peserta = activeParticipants;
  form.custom_fields['school_name'] = form.school_name;
  form.custom_fields['mentor_name'] = form.mentor_name;
  form.custom_fields['mentor_wa'] = form.mentor_wa;
  if (!form.custom_fields['school_district']) form.custom_fields['school_district'] = 'Balung';
  if (!form.custom_fields['school_address']) form.custom_fields['school_address'] = form.school_name;
  if (!form.custom_fields['mentor_email']) form.custom_fields['mentor_email'] = 'pembina@sekolah.sch.id';

  form.post('/formulir-pendaftaran', {
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
  if (props.authRegistrant?.email) form.custom_fields['mentor_email'] = props.authRegistrant.email;
  if (props.authRegistrant?.school_district) form.custom_fields['school_district'] = props.authRegistrant.school_district;
});
</script>

<template>
  <div class="semarak-root-page" :class="{ 'dark': isDark }">
    <Head :title="activeComp ? `Pendaftaran Lomba ${activeComp.title} - Semarak Aliyah XIV 2026 | MAS Wahid Hasyim Balung` : 'Pendaftaran Semarak Aliyah XIV 2026 | MAS Wahid Hasyim Balung'" />

    <!-- ======================================================================
         HEADER PUBLIK (EXACT TO USER MOCKUP)
         ====================================================================== -->
    <header class="reg-header">
      <div class="header-inner max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <!-- Logo Resmi & Branding -->
        <a class="brand-group flex items-center gap-3.5 group text-decoration-none" href="/">
          <img alt="Logo Resmi Semarak Aliyah XIV 2026" class="brand-logo h-12 w-12 object-contain transition-transform group-hover:scale-105" src="/assets/img/logo.webp">
          <div class="flex flex-col">
            <span class="font-headline font-extrabold text-base sm:text-lg text-primary tracking-tight leading-tight uppercase">
              SEMARAK ALIYAH XIV
            </span>
            <span class="font-headline text-xs sm:text-sm text-on-surface-variant font-medium">
              MAS Wahid Hasyim Balung • 2026
            </span>
          </div>
        </a>

        <!-- Navigasi Ringkas Publik -->
        <nav class="flex items-center gap-2 sm:gap-4">
          <a class="hidden md:inline-flex text-sm font-medium text-on-surface-variant hover:text-primary transition-colors text-decoration-none" href="/#tentang">
            Tentang Lomba
          </a>
          <a class="inline-flex items-center gap-1.5 text-sm font-medium text-on-surface-variant hover:text-primary transition-colors px-3 py-1.5 rounded-lg hover:bg-emerald-light/60 text-decoration-none" :href="juknisUrl" target="_blank">
            <i class="ri-download-line text-[18px] leading-none"></i>
            <span>Juknis Lomba</span>
          </a>
          <a class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-light hover:bg-primary text-emerald-dark hover:text-white font-semibold text-xs sm:text-sm transition-all border border-primary/20 hover:border-transparent text-decoration-none" :href="helpWaUrl" target="_blank">
            <i class="ri-customer-service-2-line text-[18px] leading-none"></i>
            <span>Bantuan Panitia</span>
          </a>
          <!-- Dark Mode Toggle Button -->
          <button 
            type="button" 
            @click="toggleTheme" 
            class="p-2 rounded-lg border border-border-subtle text-on-surface-variant hover:text-primary transition-colors cursor-pointer bg-transparent"
            :title="isDark ? 'Mode Terang' : 'Mode Gelap'"
          >
            <i :class="isDark ? 'ri-sun-line text-amber-400' : 'ri-moon-line text-slate-600'" class="text-[18px] leading-none"></i>
          </button>
        </nav>
      </div>
    </header>

    <!-- ======================================================================
         MAIN REGISTRATION SECTION
         ====================================================================== -->
    <main class="flex-1 py-8 sm:py-12 px-4 sm:px-6">
      <div class="max-w-4xl mx-auto">

        <!-- SUCCESS STATE CONFIRMATION RECEIPT -->
        <div v-if="isSuccess" class="bg-surface-card rounded-2xl border border-border-subtle shadow-xl p-8 sm:p-12 text-center space-y-6">
          <div class="w-16 h-16 rounded-full bg-emerald-light text-primary flex items-center justify-center text-3xl mx-auto">
            <i class="ri-checkbox-circle-fill"></i>
          </div>
          <div class="space-y-2">
            <h2 class="font-headline font-extrabold text-2xl sm:text-3xl text-emerald-dark">Pendaftaran Berhasil Dikirim!</h2>
            <p class="text-on-surface-variant text-sm sm:text-base max-w-xl mx-auto">
              Terima kasih, formulir pendaftaran delegasi sekolah Anda telah berhasil tercatat di sistem Panitia Semarak Aliyah XIV 2026.
            </p>
          </div>

          <div v-if="registeredData" class="max-w-lg mx-auto rounded-xl border border-border-subtle bg-background/50 p-5 text-left space-y-2.5 text-sm">
            <div class="flex justify-between py-1 border-b border-dashed border-border-subtle">
              <span class="text-on-surface-variant">Kode Registrasi:</span>
              <span class="font-mono font-bold text-primary">{{ registeredData.reg_code }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-dashed border-border-subtle">
              <span class="text-on-surface-variant">Asal Sekolah:</span>
              <span class="font-semibold text-on-surface">{{ registeredData.school_name }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-dashed border-border-subtle">
              <span class="text-on-surface-variant">Cabang Lomba:</span>
              <span class="font-semibold text-on-surface">{{ registeredData.competition_title }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-dashed border-border-subtle">
              <span class="text-on-surface-variant">Skema Paket:</span>
              <span class="font-semibold text-on-surface">{{ registeredData.package_type }}</span>
            </div>
            <div class="flex justify-between pt-2 text-base font-bold">
              <span>Total Biaya:</span>
              <span class="text-primary font-headline font-extrabold text-lg">
                Rp {{ Number(registeredData.total_fee || 0).toLocaleString('id-ID') }}
              </span>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <a href="/" class="px-5 py-2.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] text-on-surface font-semibold text-sm hover:border-primary transition-all text-decoration-none">
              <i class="ri-arrow-left-line"></i> Kembali ke Beranda
            </a>
            <button 
              type="button" 
              @click="isSuccess = false; form.reset()" 
              class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-white font-semibold text-sm transition-all cursor-pointer border-none"
            >
              <i class="ri-add-line"></i> Daftarkan Cabang Lain
            </button>
          </div>
        </div>

        <!-- FORM CARD SECTION -->
        <div v-else>
          <!-- HERO HEADER ELEGAN & RINGKAS -->
          <div class="text-center mb-8 sm:mb-10 space-y-3">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-light text-emerald-dark text-xs sm:text-sm font-semibold border border-primary/20 shadow-xs">
              <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
              Pendaftaran Resmi • Jenjang SMP / MTs Sederajat
            </div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-4xl text-on-surface tracking-tight">
              Formulir Pendaftaran Cabang {{ activeComp?.title || 'Baca Puisi' }}
            </h1>
            <!-- Inline Switcher if multiple competitions exist -->
            <div v-if="props.competitions && props.competitions.length > 1" class="flex items-center justify-center pt-1">
              <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] text-xs text-on-surface shadow-xs">
                <i class="ri-arrow-left-right-line text-primary"></i>
                <span class="text-on-surface-variant font-medium">Ganti Cabang:</span>
                <div class="w-48 sm:w-60">
                  <CustomSelect
                    v-model="form.competition_id"
                    :options="competitionOptions"
                    placeholder="Pilih Cabang..."
                    size="sm"
                    searchable
                  />
                </div>
              </div>
            </div>
            <p class="text-on-surface-variant text-sm sm:text-base max-w-2xl mx-auto leading-relaxed pt-1">
              Silakan lengkapi data delegasi sekolah, peserta, serta berkas naskah Anda. Proses cepat, mudah, dan langsung terverifikasi oleh panitia.
            </p>
          </div>

          <!-- FORMULIR TERPUSAT (CLEAN & TO-THE-POINT) -->
          <form 
            id="publicRegisterForm" 
            class="bg-surface-card rounded-2xl border border-border-subtle shadow-xl shadow-primary/5 p-6 sm:p-10 space-y-8" 
            novalidate 
            @submit.prevent="handleSubmit"
          >
            <!-- SECTION 1: Informasi Asal Sekolah & Guru Pembina -->
            <div class="space-y-4">
              <div class="flex items-center gap-2.5 pb-2 border-b border-border-subtle">
                <i class="ri-bank-line text-primary text-[22px] leading-none"></i>
                <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">1. Delegasi &amp; Asal Sekolah</h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                <!-- Nama Asal Sekolah with Autocomplete -->
                <div class="space-y-1.5 md:col-span-2 relative">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Nama Asal Sekolah / Madrasah <span class="text-red-500">*</span>
                  </label>
                  <input 
                    id="reg-sekolah"
                    v-model="form.school_name"
                    @input="onSchoolInput"
                    @focus="onSchoolInput"
                    @blur="onSchoolBlur"
                    class="w-full h-11 px-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400 text-on-surface"
                    :class="{ 'border-red-500 ring-2 ring-red-500/20': hasFieldError('school_name') }"
                    placeholder="Contoh: MTs Unggulan Balung atau SMP Negeri 1 Jember" 
                    required 
                    type="text"
                  >
                  <!-- Dropdown Suggestions Autocomplete -->
                  <div 
                    v-if="showSchoolDropdown && schoolSuggestions.length > 0" 
                    class="absolute left-0 right-0 top-full mt-1 bg-surface-card border border-border-subtle rounded-xl shadow-xl z-30 max-h-56 overflow-y-auto p-1.5"
                  >
                    <div 
                      v-for="s in schoolSuggestions" 
                      :key="s.id || s.name"
                      @mousedown.prevent="selectSchool(s)"
                      class="p-2.5 rounded-lg hover:bg-emerald-light/50 cursor-pointer flex items-center justify-between text-xs transition-colors"
                    >
                      <div>
                        <p class="font-bold text-on-surface">{{ s.name }}</p>
                        <p class="text-[11px] text-on-surface-variant">{{ s.kecamatan ? `Kec. ${s.kecamatan}` : '' }} {{ s.jenjang || 'SMP/MTs' }}</p>
                      </div>
                      <span class="text-[10px] font-semibold text-primary bg-emerald-light px-2 py-0.5 rounded-full">Pilih</span>
                    </div>
                  </div>
                  <p v-if="getFieldError('school_name')" class="text-xs text-red-500 font-medium">{{ getFieldError('school_name') }}</p>
                </div>

                <!-- Nama Guru Pembina / Pendamping -->
                <div class="space-y-1.5" id="reg-pendamping">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Nama Guru Pembina / Pendamping <span class="text-red-500">*</span>
                  </label>
                  <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                      <i class="ri-user-line text-[18px] leading-none"></i>
                    </div>
                    <input 
                      v-model="form.mentor_name"
                      @input="clearFieldError('mentor_name')"
                      class="w-full h-11 pl-10 pr-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400 text-on-surface"
                      :class="{ 'border-red-500 ring-2 ring-red-500/20': hasFieldError('mentor_name') }"
                      placeholder="Ust. Ahmad Hidayat, S.Pd." 
                      required 
                      type="text"
                    >
                  </div>
                  <p v-if="getFieldError('mentor_name')" class="text-xs text-red-500 font-medium">{{ getFieldError('mentor_name') }}</p>
                </div>

                <!-- Nomor WhatsApp Aktif Pembina -->
                <div class="space-y-1.5" id="reg-wa">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Nomor WhatsApp Aktif Pembina <span class="text-red-500">*</span>
                  </label>
                  <div class="relative flex items-center">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-emerald-dark">
                      <i class="ri-whatsapp-line text-[18px] leading-none"></i>
                    </div>
                    <input 
                      v-model="form.mentor_wa"
                      @input="clearFieldError('mentor_wa')"
                      class="w-full h-11 pl-10 pr-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400 text-on-surface"
                      :class="{ 'border-red-500 ring-2 ring-red-500/20': hasFieldError('mentor_wa') }"
                      placeholder="0812-3456-7890 (Untuk Grup TM)" 
                      required 
                      type="tel"
                    >
                  </div>
                  <p v-if="getFieldError('mentor_wa')" class="text-xs text-red-500 font-medium">{{ getFieldError('mentor_wa') }}</p>
                </div>
              </div>
            </div>

            <!-- SECTION 2: Data Peserta Lomba -->
            <div class="space-y-4" id="sectionDataPeserta">
              <div class="flex items-center justify-between pb-2 border-b border-border-subtle flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                  <i class="ri-shield-user-line text-primary text-[22px] leading-none"></i>
                  <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">2. Data Peserta &amp; Skema Paket</h2>
                </div>
                <span class="text-xs text-on-surface-variant font-medium">Maksimal 2 peserta per delegasi cabang puisi</span>
              </div>

              <!-- Pilihan Skema Paket Pendaftaran -->
              <div class="p-4 rounded-xl bg-background/50 border border-border-subtle space-y-2.5">
                <div class="flex items-center justify-between">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Pilihan Skema Paket Pendaftaran <span class="text-red-500">*</span>
                  </label>
                  <span class="text-[11px] text-primary font-semibold bg-emerald-light px-2 py-0.5 rounded-full">Biaya Fleksibel</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <!-- Paket Satuan -->
                  <label 
                    class="flex items-center justify-between p-3.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] cursor-pointer hover:border-primary/60 transition-all"
                    :class="{ 'border-primary bg-emerald-light/50 shadow-xs ring-1 ring-primary': form.package_type === 'Paket Satuan' }"
                    @click="setPackageType('Paket Satuan')"
                  >
                    <div class="flex items-center gap-2.5">
                      <input type="radio" name="skema_paket" value="Paket Satuan" :checked="form.package_type === 'Paket Satuan'" class="w-4 h-4 text-primary focus:ring-primary">
                      <div class="text-left">
                        <div class="flex items-center gap-1.5">
                          <p class="text-xs sm:text-sm font-bold text-on-surface">Paket Satuan</p>
                          <span class="text-[10px] bg-primary/10 text-primary font-bold px-1.5 py-0.5 rounded">Cabang Ini</span>
                        </div>
                        <p class="text-xs font-semibold text-primary mt-0.5">Rp {{ baseCompFee.toLocaleString('id-ID') }}</p>
                        <p class="text-[11px] text-on-surface-variant">Khusus pendaftaran cabang {{ activeComp?.title || 'Baca Puisi' }}</p>
                      </div>
                    </div>
                    <i class="ri-price-tag-3-line text-primary text-[20px] shrink-0"></i>
                  </label>

                  <!-- Paket All-In Komplit -->
                  <label 
                    class="flex items-center justify-between p-3.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] cursor-pointer hover:border-primary/60 transition-all"
                    :class="{ 'border-primary bg-emerald-light/50 shadow-xs ring-1 ring-primary': form.package_type === 'Paket All-In' }"
                    @click="setPackageType('Paket All-In')"
                  >
                    <div class="flex items-center gap-2.5">
                      <input type="radio" name="skema_paket" value="Paket All-In" :checked="form.package_type === 'Paket All-In'" class="w-4 h-4 text-primary focus:ring-primary">
                      <div class="text-left">
                        <div class="flex items-center gap-1.5">
                          <p class="text-xs sm:text-sm font-bold text-on-surface">Paket All-In Komplit</p>
                          <span class="text-[10px] bg-amber-soft text-amber-dark font-bold px-1.5 py-0.5 rounded">Terlaris</span>
                        </div>
                        <p class="text-xs font-semibold text-emerald-dark mt-0.5">Rp {{ Number(pricingSettings?.allin_price || 250000).toLocaleString('id-ID') }}</p>
                        <p class="text-[11px] text-on-surface-variant">Semua cabang lomba kontingen sekolah</p>
                      </div>
                    </div>
                    <i class="ri-verified-badge-line text-primary text-[20px] shrink-0"></i>
                  </label>
                </div>

                <!-- Input Kode Paket All-In Komplit -->
                <div class="mt-3 p-3.5 rounded-xl border border-dashed border-primary/40 bg-emerald-light/30 space-y-2">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <label class="block text-xs font-semibold text-on-surface flex items-center gap-1.5">
                      <i class="ri-key-line text-primary text-[18px] leading-none"></i>
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
                      >
                    </div>
                    <button 
                      type="button" 
                      @click="verifyPackageCodeManual" 
                      :disabled="isCheckingPackageCode"
                      class="h-10 px-4 rounded-lg bg-primary hover:bg-primary-container text-white text-xs font-semibold flex items-center gap-1.5 shrink-0 transition-colors shadow-xs cursor-pointer border-none"
                    >
                      <i v-if="isCheckingPackageCode" class="ri-loader-4-line animate-spin text-[16px]"></i>
                      <i v-else class="ri-checkbox-circle-line text-[16px]"></i>
                      <span>Cek Kode</span>
                    </button>
                  </div>
                  <p v-if="codeVerificationError" class="text-xs text-red-500 font-medium">{{ codeVerificationError }}</p>
                  <p v-else class="text-[11px] text-on-surface-variant flex items-center gap-1">
                    <i class="ri-information-line text-[14px] text-primary leading-none"></i>
                    <span>Wajib diisi jika sekolah Anda telah mendaftarkan paket kolektif/all-in cabang lomba</span>
                  </p>
                </div>
              </div>

              <!-- Selector Jumlah Peserta (Tanpa Biaya Perkalian) -->
              <div class="p-4 rounded-xl bg-background/50 border border-border-subtle space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                  <div class="flex items-center gap-2">
                    <i class="ri-group-line text-primary text-[18px] leading-none"></i>
                    <label class="block text-xs sm:text-sm font-semibold text-on-surface">Jumlah Delegasi Peserta</label>
                  </div>
                  <span class="text-[11px] font-medium text-emerald-dark bg-emerald-light border border-primary/20 px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 w-fit">
                    <i class="ri-information-line text-[14px] leading-none"></i>
                    Maks. 2 Peserta per Sekolah/Madrasah
                  </span>
                </div>
                <div class="grid grid-cols-2 p-1 bg-white dark:bg-[#111d17] border border-border-subtle rounded-xl gap-1 shadow-xs">
                  <label 
                    class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg cursor-pointer text-xs sm:text-sm font-semibold text-on-surface-variant transition-all hover:text-primary"
                    :class="{ 'bg-emerald-light text-primary shadow-xs border border-primary/20': participantCount === 1 }"
                    @click="setParticipantCount(1)"
                  >
                    <input type="radio" name="jumlah_peserta" value="1" :checked="participantCount === 1" class="hidden">
                    <i class="ri-user-line text-[18px] leading-none"></i>
                    <span>1 Peserta (Tunggal)</span>
                  </label>
                  <label 
                    class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg cursor-pointer text-xs sm:text-sm font-semibold text-on-surface-variant transition-all hover:text-primary"
                    :class="{ 'bg-emerald-light text-primary shadow-xs border border-primary/20': participantCount === 2 }"
                    @click="setParticipantCount(2)"
                  >
                    <input type="radio" name="jumlah_peserta" value="2" :checked="participantCount === 2" class="hidden">
                    <i class="ri-group-line text-[18px] leading-none"></i>
                    <span>2 Peserta (Maksimal)</span>
                  </label>
                </div>
              </div>

              <!-- Kartu Peserta 1 -->
              <div class="p-4 sm:p-5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] space-y-3.5 shadow-xs">
                <div class="flex items-center justify-between border-b border-border-subtle pb-2">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-light text-emerald-dark font-headline text-xs font-bold flex items-center justify-center">1</span>
                    <h3 class="font-headline font-bold text-sm text-on-surface">Data Peserta 1</h3>
                  </div>
                  <span class="text-[11px] font-semibold text-emerald-dark bg-emerald-light px-2 py-0.5 rounded-full">Peserta Utama</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
                  <div class="space-y-1.5 md:col-span-6" id="reg-siswa-1">
                    <label class="block text-xs font-semibold text-on-surface">
                      Nama Lengkap Siswa <span class="text-red-500">*</span>
                    </label>
                    <input 
                      v-model="participants[0].name"
                      @input="clearFieldError('participant_0_name')"
                      class="w-full h-11 px-3.5 rounded-xl border border-border-subtle bg-background/40 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400 text-on-surface"
                      :class="{ 'border-red-500 ring-2 ring-red-500/20': hasFieldError('participant_0_name') }"
                      placeholder="Sesuai rapor / akta kelahiran" 
                      required 
                      type="text"
                    >
                    <p v-if="getFieldError('participant_0_name')" class="text-xs text-red-500 font-medium">{{ getFieldError('participant_0_name') }}</p>
                  </div>
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
                  <div class="space-y-1.5 md:col-span-3">
                    <label class="block text-xs font-semibold text-on-surface">
                      Jenis Kelamin <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 h-11">
                      <label 
                        class="flex items-center justify-center gap-1 border border-border-subtle rounded-xl cursor-pointer text-xs font-semibold text-on-surface-variant hover:bg-emerald-light/40 transition-colors"
                        :class="{ 'bg-emerald-light border-primary text-primary': participants[0].gender === 'L' }"
                        @click="participants[0].gender = 'L'"
                      >
                        <input type="radio" name="gender_1" value="L" :checked="participants[0].gender === 'L'" class="hidden">
                        <span>Laki-Laki</span>
                      </label>
                      <label 
                        class="flex items-center justify-center gap-1 border border-border-subtle rounded-xl cursor-pointer text-xs font-semibold text-on-surface-variant hover:bg-emerald-light/40 transition-colors"
                        :class="{ 'bg-emerald-light border-primary text-primary': participants[0].gender === 'P' }"
                        @click="participants[0].gender = 'P'"
                      >
                        <input type="radio" name="gender_1" value="P" :checked="participants[0].gender === 'P'" class="hidden">
                        <span>Perempuan</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Kartu Peserta 2 (Dinamis / Opsional) -->
              <div 
                v-if="participantCount === 2" 
                class="p-4 sm:p-5 rounded-xl border border-dashed border-primary/40 bg-white dark:bg-[#111d17] space-y-3.5 shadow-xs"
              >
                <div class="flex items-center justify-between border-b border-border-subtle pb-2">
                  <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full bg-emerald-light text-emerald-dark font-headline text-xs font-bold flex items-center justify-center">2</span>
                    <h3 class="font-headline font-bold text-sm text-on-surface">Data Peserta 2</h3>
                  </div>
                  <span class="text-[11px] font-semibold text-primary bg-emerald-light px-2 py-0.5 rounded-full">Peserta 2 (Tambahan)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
                  <div class="space-y-1.5 md:col-span-6" id="reg-siswa-2">
                    <label class="block text-xs font-semibold text-on-surface">
                      Nama Lengkap Siswa <span class="text-red-500">*</span>
                    </label>
                    <input 
                      v-model="participants[1].name"
                      @input="clearFieldError('participant_1_name')"
                      class="w-full h-11 px-3.5 rounded-xl border border-border-subtle bg-background/40 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400 text-on-surface"
                      :class="{ 'border-red-500 ring-2 ring-red-500/20': hasFieldError('participant_1_name') }"
                      placeholder="Sesuai rapor / akta kelahiran" 
                      required 
                      type="text"
                    >
                    <p v-if="getFieldError('participant_1_name')" class="text-xs text-red-500 font-medium">{{ getFieldError('participant_1_name') }}</p>
                  </div>
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
                  <div class="space-y-1.5 md:col-span-3">
                    <label class="block text-xs font-semibold text-on-surface">
                      Jenis Kelamin <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 h-11">
                      <label 
                        class="flex items-center justify-center gap-1 border border-border-subtle rounded-xl cursor-pointer text-xs font-semibold text-on-surface-variant hover:bg-emerald-light/40 transition-colors"
                        :class="{ 'bg-emerald-light border-primary text-primary': participants[1].gender === 'L' }"
                        @click="participants[1].gender = 'L'"
                      >
                        <input type="radio" name="gender_2" value="L" :checked="participants[1].gender === 'L'" class="hidden">
                        <span>Laki-Laki</span>
                      </label>
                      <label 
                        class="flex items-center justify-center gap-1 border border-border-subtle rounded-xl cursor-pointer text-xs font-semibold text-on-surface-variant hover:bg-emerald-light/40 transition-colors"
                        :class="{ 'bg-emerald-light border-primary text-primary': participants[1].gender === 'P' }"
                        @click="participants[1].gender = 'P'"
                      >
                        <input type="radio" name="gender_2" value="P" :checked="participants[1].gender === 'P'" class="hidden">
                        <span>Perempuan</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 3: Lampiran Berkas Naskah & Kartu Pelajar -->
            <div class="space-y-4">
              <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                <div class="flex items-center gap-2.5">
                  <i class="ri-file-upload-line text-primary text-[22px] leading-none"></i>
                  <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">3. Naskah Puisi &amp; Berkas</h2>
                </div>
                <span class="text-xs text-on-surface-variant">PDF/ZIP (Maks 10 MB)</span>
              </div>
              <div 
                class="border-2 border-dashed border-border-subtle hover:border-primary/60 rounded-xl p-5 bg-background/50 hover:bg-emerald-light/20 transition-all flex flex-col sm:flex-row items-center justify-between gap-4 cursor-pointer relative"
                @click="naskahInputRef?.click()"
              >
                <input 
                  ref="naskahInputRef"
                  type="file" 
                  class="hidden" 
                  accept=".pdf,.zip,.rar,.doc,.docx"
                  @change="handleNaskahChange"
                >
                <div class="flex items-center gap-3.5">
                  <div class="w-11 h-11 rounded-xl bg-emerald-light text-primary flex items-center justify-center shrink-0">
                    <i class="ri-file-text-line text-[24px] leading-none"></i>
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
                    class="px-4 py-2 rounded-lg bg-white dark:bg-[#111d17] border border-border-subtle shadow-xs text-xs font-semibold text-on-surface hover:text-primary shrink-0 transition-colors cursor-pointer" 
                    type="button"
                  >
                    {{ selectedNaskahFile ? 'Ganti Dokumen' : 'Pilih Dokumen' }}
                  </button>
                  <button 
                    v-if="selectedNaskahFile"
                    type="button" 
                    @click.stop="clearNaskahFile" 
                    class="px-2.5 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 text-xs font-semibold cursor-pointer transition-colors"
                    title="Hapus berkas"
                  >
                    <i class="ri-delete-bin-line leading-none"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- SECTION 4: Pembayaran Ringkas -->
            <div class="space-y-4" id="sectionPembayaran">
              <div class="flex items-center justify-between pb-2 border-b border-border-subtle flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                  <i class="ri-wallet-3-line text-primary text-[22px] leading-none"></i>
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
                    class="p-3.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] cursor-pointer hover:border-primary/60 transition-all flex flex-col justify-between gap-2"
                    :class="{ 'border-primary bg-emerald-light/40 ring-1 ring-primary': form.payment_method === 'Transfer Bank BSI' }"
                    @click="selectPaymentMethod('Transfer Bank BSI')"
                  >
                    <div class="flex items-center justify-between w-full">
                      <div class="flex items-center gap-2">
                        <input type="radio" name="payment_method" value="Transfer Bank BSI" :checked="form.payment_method === 'Transfer Bank BSI'" class="w-4 h-4 text-primary focus:ring-primary">
                        <span class="text-xs font-bold text-primary uppercase">Transfer BSI</span>
                      </div>
                      <i class="ri-bank-line text-primary text-[18px] leading-none"></i>
                    </div>
                    <div>
                      <div class="flex items-center justify-between">
                        <p class="font-mono text-sm font-bold text-on-surface">7124686746</p>
                        <button type="button" @click.stop="copyAccount('7124686746')" class="p-1 text-on-surface-variant hover:text-primary cursor-pointer border-none bg-transparent" title="Salin No. Rekening">
                          <i class="ri-file-copy-line text-[16px] leading-none"></i>
                        </button>
                      </div>
                      <p class="text-[11px] text-on-surface-variant">a.n. MARIATUL AWALIYAH</p>
                    </div>
                  </label>

                  <!-- Pilihan 2: BRI -->
                  <label 
                    class="p-3.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] cursor-pointer hover:border-primary/60 transition-all flex flex-col justify-between gap-2"
                    :class="{ 'border-primary bg-emerald-light/40 ring-1 ring-primary': form.payment_method === 'Transfer Bank BRI' }"
                    @click="selectPaymentMethod('Transfer Bank BRI')"
                  >
                    <div class="flex items-center justify-between w-full">
                      <div class="flex items-center gap-2">
                        <input type="radio" name="payment_method" value="Transfer Bank BRI" :checked="form.payment_method === 'Transfer Bank BRI'" class="w-4 h-4 text-primary focus:ring-primary">
                        <span class="text-xs font-bold text-amber-dark uppercase">Transfer BRI</span>
                      </div>
                      <i class="ri-bank-line text-amber-dark text-[18px] leading-none"></i>
                    </div>
                    <div>
                      <div class="flex items-center justify-between">
                        <p class="font-mono text-sm font-bold text-on-surface">6221 0102 2421 537</p>
                        <button type="button" @click.stop="copyAccount('622101022421537')" class="p-1 text-on-surface-variant hover:text-primary cursor-pointer border-none bg-transparent" title="Salin No. Rekening">
                          <i class="ri-file-copy-line text-[16px] leading-none"></i>
                        </button>
                      </div>
                      <p class="text-[11px] text-on-surface-variant">a.n. MARIATUL AWALIYAH</p>
                    </div>
                  </label>

                  <!-- Pilihan 3: Cash / Tunai -->
                  <label 
                    class="p-3.5 rounded-xl border border-border-subtle bg-white dark:bg-[#111d17] cursor-pointer hover:border-primary/60 transition-all flex flex-col justify-between gap-2"
                    :class="{ 'border-primary bg-emerald-light/40 ring-1 ring-primary': form.payment_method === 'Tunai di Sekretariat' }"
                    @click="selectPaymentMethod('Tunai di Sekretariat')"
                  >
                    <div class="flex items-center justify-between w-full">
                      <div class="flex items-center gap-2">
                        <input type="radio" name="payment_method" value="Tunai di Sekretariat" :checked="form.payment_method === 'Tunai di Sekretariat'" class="w-4 h-4 text-primary focus:ring-primary">
                        <span class="text-xs font-bold text-emerald-dark uppercase">Tunai / Cash</span>
                      </div>
                      <i class="ri-money-dollar-circle-line text-emerald-dark text-[18px] leading-none"></i>
                    </div>
                    <div>
                      <p class="text-xs font-bold text-on-surface">Sekretariat Panitia</p>
                      <p class="text-[11px] text-on-surface-variant">MAS Wahid Hasyim Balung</p>
                    </div>
                  </label>
                </div>
              </div>

              <!-- Upload Bukti Transfer -->
              <div class="p-4 rounded-xl border border-border-subtle bg-background/50 space-y-2.5">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-semibold text-on-surface flex items-center gap-1.5">
                    <i class="ri-bill-line text-primary text-[18px] leading-none"></i>
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
                  >
                  <button 
                    type="button" 
                    @click="paymentProofRef?.click()" 
                    class="px-3.5 py-2 rounded-lg bg-white dark:bg-[#111d17] border border-border-subtle hover:border-primary text-xs font-semibold text-on-surface flex items-center gap-2 transition-colors cursor-pointer"
                  >
                    <i class="ri-image-add-line text-[18px] text-primary leading-none"></i>
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
                      class="text-xs text-red-500 hover:text-red-700 p-1 cursor-pointer border-none bg-transparent"
                      title="Hapus Bukti"
                    >
                      <i class="ri-close-circle-line text-base leading-none"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- SECTION 5: Ringkasan Delegasi & Skema Biaya -->
            <div class="space-y-6 pt-4 border-t border-border-subtle">
              <div class="rounded-xl border border-primary/20 bg-emerald-light/30 p-5 space-y-3.5 shadow-xs">
                <div class="flex items-center justify-between pb-2 border-b border-primary/10 flex-wrap gap-2">
                  <div class="flex items-center gap-2">
                    <i class="ri-file-list-3-line text-primary text-[20px] leading-none"></i>
                    <h3 class="font-headline font-bold text-sm sm:text-base text-on-surface">Ringkasan Delegasi &amp; Pendaftaran</h3>
                  </div>
                  <span class="text-[11px] font-bold text-emerald-dark bg-emerald-light px-2.5 py-0.5 rounded-full border border-primary/20">Resmi Panitia</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                  <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                    <span class="text-on-surface-variant">Cabang Lomba:</span>
                    <span class="font-semibold text-on-surface">{{ activeComp?.title || 'Baca Puisi' }} (SMP/MTs)</span>
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
                      <i class="ri-checkbox-circle-fill text-primary text-[14px] leading-none"></i> Bebas Biaya Admin
                    </span>
                  </div>
                </div>
              </div>

              <!-- SUBMIT ACTION CTA BUTTON -->
              <div class="space-y-3">
                <button 
                  id="submitBtnCta" 
                  class="w-full py-4 rounded-xl bg-primary hover:bg-primary-container text-white font-headline font-bold text-base sm:text-lg flex items-center justify-center gap-2.5 shadow-lg shadow-primary/20 hover:shadow-xl transition-all active:scale-[0.99] cursor-pointer border-none" 
                  type="submit"
                  :disabled="form.processing"
                >
                  <i v-if="form.processing" class="ri-loader-4-line animate-spin text-[22px] leading-none"></i>
                  <i v-else class="ri-send-plane-fill text-[22px] leading-none"></i>
                  <span>{{ form.processing ? 'Mengirim Formulir...' : `Kirim Formulir & Bayar • Rp ${totalCalculatedFee.toLocaleString('id-ID')}` }}</span>
                </button>
                <div class="flex items-center justify-center gap-2 text-xs text-on-surface-variant text-center">
                  <i class="ri-checkbox-circle-fill text-primary text-[16px] leading-none"></i>
                  <span>Data pendaftaran resmi terintegrasi dengan Panitia Semarak Aliyah XIV 2026.</span>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </main>

    <!-- ======================================================================
         FOOTER PUBLIK SEDERHANA (EXACT TO USER MOCKUP)
         ====================================================================== -->
    <footer class="reg-footer w-full bg-white dark:bg-[#111d17] border-t border-border-subtle dark:border-[#1e3529] py-8 px-4 sm:px-6 mt-12">
      <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
        <div class="flex items-center gap-3">
          <img alt="Logo MAS Wahid Hasyim" class="h-8 w-8 object-contain" src="/assets/img/logo.webp">
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
      class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 pointer-events-none"
      :class="toast.show ? 'translate-y-0 opacity-100' : 'translate-y-24 opacity-0'"
    >
      <div class="flex items-center gap-2.5 bg-gray-900 text-white px-4 py-3 rounded-xl shadow-xl text-sm">
        <i :class="toast.icon" class="text-emerald-400 text-[20px] leading-none"></i>
        <span>{{ toast.message }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ==========================================================================
   SEMANTIC ROOT & BULLETPROOF SCOPED FALLBACK STYLING
   ========================================================================== */
.semarak-root-page {
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

.semarak-root-page.dark {
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

/* Base utility fallbacks ensuring layout never collapses */
.flex { display: flex !important; }
.inline-flex { display: inline-flex !important; }
.grid { display: grid !important; }
.flex-col { flex-direction: column !important; }
.flex-1 { flex: 1 1 0% !important; }
.items-center { align-items: center !important; }
.justify-between { justify-content: space-between !important; }
.justify-center { justify-content: center !important; }
.text-center { text-align: center !important; }
.text-left { text-align: left !important; }
.text-right { text-align: right !important; }
.relative { position: relative !important; }
.absolute { position: absolute !important; }
.inset-y-0 { top: 0 !important; bottom: 0 !important; }
.left-0 { left: 0 !important; }
.right-0 { right: 0 !important; }
.top-0 { top: 0 !important; }
.top-full { top: 100% !important; }
.bottom-6 { bottom: 1.5rem !important; }
.right-6 { right: 1.5rem !important; }
.pointer-events-none { pointer-events: none !important; }
.sticky { position: sticky !important; }
.fixed { position: fixed !important; }
.z-40 { z-index: 40 !important; }
.z-50 { z-index: 50 !important; }
.z-30 { z-index: 30 !important; }
.shrink-0 { flex-shrink: 0 !important; }
.hidden { display: none !important; }
.overflow-y-auto { overflow-y: auto !important; }
.max-h-56 { max-height: 14rem !important; }
.w-full { width: 100% !important; }
.min-h-screen { min-height: 100vh !important; }
.mx-auto { margin-left: auto !important; margin-right: auto !important; }

/* Constrained container widths */
.max-w-6xl { max-width: 72rem !important; }
.max-w-4xl { max-width: 56rem !important; }
.max-w-2xl { max-width: 42rem !important; }
.max-w-xl { max-width: 36rem !important; }
.max-w-lg { max-width: 32rem !important; }
.max-w-xs { max-width: 20rem !important; }

/* Heights & Sizes */
.h-20 { height: 5rem !important; }
.h-12 { height: 3rem !important; }
.w-12 { width: 3rem !important; }
.h-11 { height: 2.75rem !important; }
.h-10 { height: 2.5rem !important; }
.h-8 { height: 2rem !important; }
.w-8 { width: 2rem !important; }
.w-16 { width: 4rem !important; }
.h-16 { height: 4rem !important; }
.w-6 { width: 1.5rem !important; }
.h-6 { height: 1.5rem !important; }
.w-2 { width: 0.5rem !important; }
.h-2 { height: 0.5rem !important; }

/* Image scaling & containment */
img.brand-logo,
.brand-group img,
.h-12.w-12 {
  width: 3rem !important;
  height: 3rem !important;
  max-width: 3rem !important;
  max-height: 3rem !important;
  object-fit: contain !important;
  flex-shrink: 0 !important;
}

/* Colors */
.bg-background { background-color: var(--color-background) !important; }
.text-on-surface { color: var(--color-on-surface) !important; }
.text-on-surface-variant { color: var(--color-on-surface-variant) !important; }
.bg-surface-card { background-color: var(--color-surface-card) !important; }
.border-border-subtle { border-color: var(--color-border-subtle) !important; }
.text-primary { color: var(--color-primary) !important; }
.bg-primary { background-color: var(--color-primary) !important; }
.bg-primary:hover, .hover\:bg-primary-container:hover { background-color: var(--color-primary-container) !important; }
.bg-emerald-light { background-color: var(--color-emerald-light) !important; }
.text-emerald-dark { color: var(--color-emerald-dark) !important; }
.bg-amber-soft { background-color: var(--color-amber-soft) !important; }
.text-amber-dark { color: var(--color-amber-dark) !important; }
.text-red-500 { color: #ef4444 !important; }
.text-red-600 { color: #dc2626 !important; }
.text-gray-400 { color: #9ca3af !important; }
.text-slate-600 { color: #475569 !important; }
.text-amber-400 { color: #fbbf24 !important; }
.border-red-500 { border-color: #ef4444 !important; }
.ring-2 { box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25) !important; }
.ring-red-500\/20 { box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25) !important; }
.ring-1 { box-shadow: 0 0 0 1px var(--color-primary, #006948) !important; }
.ring-primary { box-shadow: 0 0 0 1px var(--color-primary, #006948) !important; }
.bg-emerald-light\/60 { background-color: rgba(236, 253, 245, 0.8) !important; }
.bg-emerald-light\/50 { background-color: rgba(236, 253, 245, 0.7) !important; }
.bg-emerald-light\/40 { background-color: rgba(236, 253, 245, 0.5) !important; }
.bg-emerald-light\/30 { background-color: rgba(236, 253, 245, 0.35) !important; }
.bg-emerald-light\/20 { background-color: rgba(236, 253, 245, 0.2) !important; }
.bg-background\/50 { background-color: rgba(247, 250, 247, 0.6) !important; }
.bg-background\/40 { background-color: rgba(247, 250, 247, 0.5) !important; }
.bg-primary\/20 { background-color: rgba(0, 105, 72, 0.2) !important; }
.bg-primary\/10 { background-color: rgba(0, 105, 72, 0.1) !important; }
.border-primary\/40 { border-color: rgba(0, 105, 72, 0.4) !important; }
.border-primary\/20 { border-color: rgba(0, 105, 72, 0.2) !important; }
.border-primary\/10 { border-color: rgba(0, 105, 72, 0.1) !important; }

/* Border radius */
.rounded-2xl { border-radius: 1rem !important; }
.rounded-xl { border-radius: 0.75rem !important; }
.rounded-lg { border-radius: 0.5rem !important; }
.rounded-full { border-radius: 9999px !important; }

/* Borders & Shadows */
.border { border-width: 1px !important; border-style: solid !important; border-color: var(--color-border-subtle) !important; }
.border-b { border-bottom-width: 1px !important; border-bottom-style: solid !important; border-bottom-color: var(--color-border-subtle) !important; }
.border-t { border-top-width: 1px !important; border-top-style: solid !important; border-top-color: var(--color-border-subtle) !important; }
.border-2 { border-width: 2px !important; }
.border-dashed { border-style: dashed !important; }
.shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 105, 72, 0.05), 0 8px 10px -6px rgba(0, 105, 72, 0.05) !important; }
.shadow-xs { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important; }
.shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 105, 72, 0.2) !important; }

/* Margins & Paddings */
.p-6 { padding: 1.5rem !important; }
.p-5 { padding: 1.25rem !important; }
.p-4 { padding: 1rem !important; }
.p-3\.5 { padding: 0.875rem !important; }
.p-3 { padding: 0.75rem !important; }
.p-2\.5 { padding: 0.625rem !important; }
.p-2 { padding: 0.5rem !important; }
.p-1 { padding: 0.25rem !important; }
.px-4 { padding-left: 1rem !important; padding-right: 1rem !important; }
.px-3\.5 { padding-left: 0.875rem !important; padding-right: 0.875rem !important; }
.px-3 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
.px-2\.5 { padding-left: 0.625rem !important; padding-right: 0.625rem !important; }
.px-2 { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
.py-8 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
.py-4 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
.py-2\.5 { padding-top: 0.625rem !important; padding-bottom: 0.625rem !important; }
.py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
.py-1\.5 { padding-top: 0.375rem !important; padding-bottom: 0.375rem !important; }
.py-1 { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
.py-0\.5 { padding-top: 0.125rem !important; padding-bottom: 0.125rem !important; }
.pb-2 { padding-bottom: 0.5rem !important; }
.pt-4 { padding-top: 1rem !important; }
.pt-3 { padding-top: 0.75rem !important; }
.pt-2 { padding-top: 0.5rem !important; }
.pt-1 { padding-top: 0.25rem !important; }
.mt-12 { margin-top: 3rem !important; }
.mt-3 { margin-top: 0.75rem !important; }
.mt-1 { margin-top: 0.25rem !important; }
.mt-0\.5 { margin-top: 0.125rem !important; }
.mb-8 { margin-bottom: 2rem !important; }

/* Gap & Spacing */
.gap-5 { gap: 1.25rem !important; }
.gap-4 { gap: 1rem !important; }
.gap-3\.5 { gap: 0.875rem !important; }
.gap-3 { gap: 0.75rem !important; }
.gap-2\.5 { gap: 0.625rem !important; }
.gap-2 { gap: 0.5rem !important; }
.gap-1\.5 { gap: 0.375rem !important; }
.gap-1 { gap: 0.25rem !important; }

.space-y-8 > * + * { margin-top: 2rem !important; }
.space-y-6 > * + * { margin-top: 1.5rem !important; }
.space-y-4 > * + * { margin-top: 1rem !important; }
.space-y-3\.5 > * + * { margin-top: 0.875rem !important; }
.space-y-3 > * + * { margin-top: 0.75rem !important; }
.space-y-2\.5 > * + * { margin-top: 0.625rem !important; }
.space-y-2 > * + * { margin-top: 0.5rem !important; }
.space-y-1\.5 > * + * { margin-top: 0.375rem !important; }

/* Grid columns */
.grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)) !important; }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }

@media (min-width: 640px) {
  .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
  .sm\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
  .sm\:flex-row { flex-direction: row !important; }
  .sm\:p-10 { padding: 2.5rem !important; }
  .sm\:p-5 { padding: 1.25rem !important; }
  .sm\:py-12 { padding-top: 3rem !important; padding-bottom: 3rem !important; }
  .sm\:px-6 { padding-left: 1.5rem !important; padding-right: 1.5rem !important; }
  .sm\:mb-10 { margin-bottom: 2.5rem !important; }
  .sm\:text-4xl { font-size: 2.25rem !important; line-height: 2.5rem !important; }
  .sm\:text-3xl { font-size: 1.875rem !important; line-height: 2.25rem !important; }
  .sm\:text-2xl { font-size: 1.5rem !important; line-height: 2rem !important; }
  .sm\:text-lg { font-size: 1.125rem !important; line-height: 1.75rem !important; }
  .sm\:text-base { font-size: 1rem !important; line-height: 1.5rem !important; }
  .sm\:text-sm { font-size: 0.875rem !important; }
  .sm\:text-left { text-align: left !important; }
}

@media (min-width: 768px) {
  .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
  .md\:grid-cols-12 { grid-template-columns: repeat(12, minmax(0, 1fr)) !important; }
  .md\:col-span-2 { grid-column: span 2 / span 2 !important; }
  .md\:col-span-3 { grid-column: span 3 / span 3 !important; }
  .md\:col-span-6 { grid-column: span 6 / span 6 !important; }
  .md\:col-span-12 { grid-column: span 12 / span 12 !important; }
  .md\:inline-flex { display: inline-flex !important; }
}

/* Typography */
.font-headline { font-family: var(--font-headline, "Plus Jakarta Sans", sans-serif) !important; }
.font-body { font-family: var(--font-body, "Inter", sans-serif) !important; }
.font-extrabold { font-weight: 800 !important; }
.font-bold { font-weight: 700 !important; }
.font-semibold { font-weight: 600 !important; }
.font-medium { font-weight: 500 !important; }
.text-2xl { font-size: 1.5rem !important; line-height: 2rem !important; }
.text-xl { font-size: 1.25rem !important; line-height: 1.75rem !important; }
.text-lg { font-size: 1.125rem !important; line-height: 1.75rem !important; }
.text-base { font-size: 1rem !important; line-height: 1.5rem !important; }
.text-sm { font-size: 0.875rem !important; line-height: 1.25rem !important; }
.text-xs { font-size: 0.75rem !important; line-height: 1rem !important; }
.text-\[11px\] { font-size: 11px !important; line-height: 15px !important; }
.text-\[10px\] { font-size: 10px !important; line-height: 14px !important; }
.tracking-tight { letter-spacing: -0.025em !important; }
.leading-tight { line-height: 1.25 !important; }
.leading-none { line-height: 1 !important; }
.uppercase { text-transform: uppercase !important; }
.truncate { overflow: hidden !important; text-overflow: ellipsis !important; white-space: nowrap !important; }

/* Perfect input styling */
input[type="text"], input[type="tel"], input[type="email"] {
  height: 2.75rem !important; /* 44px (h-11) */
  border-radius: 0.75rem !important; /* 12px (rounded-xl) */
  border: 1px solid var(--color-border-subtle, #E2E8F0) !important;
  background-color: rgba(247, 250, 247, 0.5) !important;
  padding-left: 0.875rem !important;
  padding-right: 0.875rem !important;
  font-size: 0.875rem !important;
  outline: none !important;
  width: 100% !important;
  color: var(--color-on-surface, #171d19) !important;
  box-sizing: border-box !important;
  transition: all 0.15s ease !important;
}

.semarak-root-page.dark input[type="text"], 
.semarak-root-page.dark input[type="tel"], 
.semarak-root-page.dark input[type="email"] {
  background-color: rgba(17, 29, 23, 0.6) !important;
  border-color: #1e3529 !important;
  color: #f8fafc !important;
}

input[type="text"].pl-10, input[type="tel"].pl-10, input[type="email"].pl-10 {
  padding-left: 2.5rem !important;
}

input:focus {
  background-color: var(--color-surface-card, #FFFFFF) !important;
  border-color: var(--color-primary, #006948) !important;
  box-shadow: 0 0 0 2px rgba(0, 105, 72, 0.2) !important;
}

/* Header & Footer specific */
.reg-header {
  position: sticky !important;
  top: 0 !important;
  z-index: 40 !important;
  width: 100% !important;
  background-color: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(8px) !important;
  border-bottom: 1px solid var(--color-border-subtle, #E2E8F0) !important;
}

.semarak-root-page.dark .reg-header {
  background-color: rgba(17, 29, 23, 0.95) !important;
  border-bottom-color: #1e3529 !important;
}

/* Pulse Animation */
@keyframes pulseDot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(1.2); }
}
.animate-pulse {
  animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite !important;
}

/* CustomSelect Uniform Height */
:deep(.custom-select-trigger) {
  height: 2.75rem !important; /* 44px */
  border-radius: 0.75rem !important; /* 12px */
  background-color: rgba(247, 250, 247, 0.5) !important;
  border-color: var(--color-border-subtle, #E2E8F0) !important;
  font-size: 0.875rem !important;
  color: var(--color-on-surface, #171d19) !important;
}

.semarak-root-page.dark :deep(.custom-select-trigger) {
  background-color: rgba(17, 29, 23, 0.6) !important;
  border-color: #1e3529 !important;
  color: #f8fafc !important;
}

:deep(.custom-select-trigger:hover) {
  border-color: rgba(0, 105, 72, 0.6) !important;
}

:deep(.custom-select-trigger.is-open) {
  border-color: var(--color-primary, #006948) !important;
  box-shadow: 0 0 0 2px rgba(0, 105, 72, 0.2) !important;
  background-color: var(--color-surface-card, #FFFFFF) !important;
}

:deep(.custom-select-dropdown) {
  border-radius: 0.75rem !important;
  border-color: var(--color-border-subtle, #E2E8F0) !important;
  background-color: var(--color-surface-card, #FFFFFF) !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}
</style>
