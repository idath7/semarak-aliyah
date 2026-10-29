<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
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

// -----------------------------------------------------------------------------
// Core Form & State Management
// -----------------------------------------------------------------------------
const isSuccess = ref(false);
const registeredData = ref(null);
const submitAttempted = ref(false);
const validationErrors = ref({});

const form = useForm({
  package_type: 'Paket Satuan', // 'Paket Satuan' | 'Paket All-In'
  competition_id: props.preselectedLomba || props.competitions?.[0]?.id || '',
  school_name: props.authRegistrant?.school_name || '',
  mentor_name: props.authRegistrant?.name || '',
  mentor_wa: props.authRegistrant?.phone || '',
  participants_names: '',
  custom_fields: {},
  notes: '',
  payment_method: 'Transfer Bank BSI',
  payment_proof: null,
});

// Toast Notification State
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

// Competition Options for CustomSelect
const competitionOptions = computed(() => {
  return (props.competitions || []).map(c => {
    const feeText = Number(c.fee) > 0 ? `Rp ${Number(c.fee).toLocaleString('id-ID')}` : 'Gratis';
    const cat = c.category ? `[${c.category}] ` : '';
    return {
      value: c.id,
      label: `${cat}${c.title} • ${feeText}`
    };
  });
});

// Help Panitia WA URL
const helpWaUrl = computed(() => {
  const wa = school.value?.contact_wa || '083850070767';
  const compTitle = activeComp.value?.title || 'Lomba';
  const msg = `Halo Panitia Semarak Aliyah XIV, saya butuh bantuan pendaftaran untuk cabang ${compTitle}.`;
  return getWaChatUrl(wa, msg);
});

// Team Information & Participant Composition
const repeaterField = computed(() => {
  return activeComp.value?.custom_fields?.find(f => f.type === 'participant_repeater') || null;
});

function getTeamComposition(comp, rf) {
  if (!comp) return { isTeam: false, min: 1, max: 2, main: 1, reserve: 0 };
  const isTeam = comp.participant_type === 'tim';
  let max = rf?.max_items ? Number(rf.max_items) : (comp.max_participants ? Number(comp.max_participants) : (isTeam ? 10 : 2));
  let min = rf?.min_items ? Number(rf.min_items) : (isTeam ? Math.min(max, 3) : 1);
  let main = rf?.main_members ? Number(rf.main_members) : 0;
  let reserve = rf?.reserve_members ? Number(rf.reserve_members) : 0;

  if (comp.participants_rule) {
    const matchInti = comp.participants_rule.match(/(\d+)\s*(?:pemain|orang)?\s*inti/i);
    const matchCadangan = comp.participants_rule.match(/(\d+)\s*cadangan/i);
    if (matchInti) {
      main = parseInt(matchInti[1], 10);
      if (matchCadangan) reserve = parseInt(matchCadangan[1], 10);
      if (!rf?.min_items) min = main;
      if (!rf?.max_items) max = main + reserve;
    }
  }

  return {
    isTeam,
    min: Math.max(1, min),
    max: Math.max(min, max),
    main: main || (isTeam ? max : 1),
    reserve
  };
}

const teamInfo = computed(() => getTeamComposition(activeComp.value, repeaterField.value));
const participantCount = ref(1);

function createEmptyParticipant() {
  return {
    name: '',
    grade: 'Kelas 9 SMP/MTs',
    gender: 'L',
    is_captain: false,
    captain_wa: '',
    birth_date: '',
    nisn: '',
    custom_columns: {}
  };
}

const participantsList = ref([
  createEmptyParticipant(),
  createEmptyParticipant(),
]);

function syncParticipantsList() {
  const target = Math.max(1, participantCount.value);
  while (participantsList.value.length < target) {
    participantsList.value.push(createEmptyParticipant());
  }
  if (participantsList.value.length > target) {
    participantsList.value.splice(target);
  }
}

function updateJumlahPeserta(count) {
  participantCount.value = count;
  syncParticipantsList();
  showToast(`Pilihan diubah: ${count} Peserta`);
}

watch(activeComp, (newComp) => {
  if (newComp) {
    const info = getTeamComposition(newComp, repeaterField.value);
    if (info.isTeam) {
      participantCount.value = Math.max(info.min, info.main);
    } else {
      participantCount.value = 1;
    }
    syncParticipantsList();
  }
}, { immediate: true });

// -----------------------------------------------------------------------------
// School Search & Autocomplete
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
  const query = form.school_name ? form.school_name.trim() : '';
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
// All-In Package Verification Logic
// -----------------------------------------------------------------------------
const isCheckingPackageCode = ref(false);
const isManualCodeVerified = ref(false);
const codeVerificationError = ref('');
const manualCodeSuccessData = ref(null);

const isAllInFree = computed(() => {
  return isManualCodeVerified.value && !!manualCodeSuccessData.value;
});

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
      showToast('Kode Paket Komplit terverifikasi! Biaya pendaftaran gratis.');
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

function clearManualPackageCode() {
  if (form.custom_fields) {
    form.custom_fields['allin_code'] = '';
  }
  isManualCodeVerified.value = false;
  manualCodeSuccessData.value = null;
  codeVerificationError.value = '';
  form.package_type = 'Paket Satuan';
  showToast('Kode Paket dihapus.');
}

// -----------------------------------------------------------------------------
// Fee & Price Calculation
// -----------------------------------------------------------------------------
const selectedCompFee = computed(() => {
  if (isAllInFree.value) return 0;
  return Number(activeComp.value?.fee || 50000);
});

const calculatedFee = computed(() => {
  if (isAllInFree.value) return 0;
  if (form.package_type === 'Paket All-In') {
    return Number(props.pricingSettings?.allin_price || 250000);
  }
  const base = Number(activeComp.value?.fee || 50000);
  if (!teamInfo.value.isTeam) {
    return base * Math.max(1, participantCount.value);
  }
  return base;
});

// -----------------------------------------------------------------------------
// Payment Channels
// -----------------------------------------------------------------------------
const paymentOptions = computed(() => {
  return [
    {
      id: 'bsi',
      value: 'Transfer Bank BSI',
      badge: 'Transfer BSI',
      badgeClass: 'text-primary',
      accountNumber: '7124686746',
      displayAccount: '7124686746',
      accountHolder: 'a.n. MARIATUL AWALIYAH',
      icon: 'ri-bank-line',
      isCash: false
    },
    {
      id: 'bri',
      value: 'Transfer Bank BRI',
      badge: 'Transfer BRI',
      badgeClass: 'text-amber-dark',
      accountNumber: '622101022421537',
      displayAccount: '6221 0102 2421 537',
      accountHolder: 'a.n. MARIATUL AWALIYAH',
      icon: 'ri-bank-line',
      isCash: false
    },
    {
      id: 'tunai',
      value: 'Tunai di Sekretariat',
      badge: 'Tunai / Cash',
      badgeClass: 'text-emerald-dark',
      accountNumber: '',
      displayAccount: 'Sekretariat Panitia',
      accountHolder: 'MAS Wahid Hasyim Balung',
      icon: 'ri-money-dollar-circle-line',
      isCash: true
    }
  ];
});

function copyAccount(accNum) {
  if (!accNum) return;
  if (navigator?.clipboard) {
    navigator.clipboard.writeText(accNum);
    showToast(`Nomor rekening ${accNum} berhasil disalin!`);
  }
}

// -----------------------------------------------------------------------------
// File Uploads (Naskah & Bukti Pembayaran)
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
    clearFieldError('naskah_berkas');
    showToast(`Berkas naskah terpilih: ${file.name}`);
  }
}

function clearNaskahFile() {
  selectedNaskahFile.value = null;
  if (form.custom_fields) form.custom_fields['naskah_berkas'] = null;
  if (naskahInputRef.value) naskahInputRef.value.value = '';
}

function handlePaymentProofChange(e) {
  const file = e.target?.files?.[0];
  if (file) {
    form.payment_proof = file;
    showToast(`Bukti pembayaran terpilih: ${file.name}`);
  }
}

function clearPaymentProof() {
  form.payment_proof = null;
  if (paymentProofRef.value) paymentProofRef.value.value = '';
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
  else if (fieldKey === 'mentor_name') targetId = 'reg-pendamping';
  else if (fieldKey === 'mentor_wa') targetId = 'reg-wa';
  else if (fieldKey === 'payment_method') targetId = 'sectionPembayaran';
  else if (fieldKey.startsWith('participant_')) targetId = 'sectionDataPeserta';

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

  if (!form.mentor_name || !form.mentor_name.trim()) {
    errs['mentor_name'] = 'Nama Guru Pembina / Pendamping wajib diisi.';
  }

  const cleanWa = (form.mentor_wa || '').replace(/\D/g, '');
  if (!cleanWa) {
    errs['mentor_wa'] = 'Nomor WhatsApp Guru Pembina wajib diisi.';
  } else if (cleanWa.length < 10) {
    errs['mentor_wa'] = 'Nomor WhatsApp pembina minimal 10 digit angka valid.';
  }

  // Validate Active Participants
  const activeList = participantsList.value.slice(0, participantCount.value);
  activeList.forEach((p, idx) => {
    if (!p.name || !p.name.trim()) {
      errs[`participant_${idx}_name`] = `Nama lengkap Peserta ${idx + 1} wajib diisi.`;
    }
  });

  if (calculatedFee.value > 0 && !isAllInFree.value) {
    if (!form.payment_method) {
      errs['payment_method'] = 'Saluran metode pembayaran wajib dipilih.';
    }
  }

  validationErrors.value = errs;

  if (Object.keys(errs).length > 0) {
    nextTick(() => {
      document.getElementById('form-validation-summary')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    return false;
  }
  return true;
}

function handleSubmit() {
  if (!validateForm()) return;

  const mergedCustom = { ...(form.custom_fields || {}) };
  mergedCustom.school_name = form.school_name;
  mergedCustom.school_district = form.custom_fields?.['school_district'] || '';
  mergedCustom.school_address = form.custom_fields?.['school_address'] || '';
  mergedCustom.school_npsn = form.custom_fields?.['school_npsn'] || '';
  mergedCustom.mentor_name = form.mentor_name;
  mergedCustom.mentor_wa = form.mentor_wa;

  // Build participants string representation
  const activeList = participantsList.value.slice(0, participantCount.value);
  const lines = activeList.map((p, idx) => {
    const details = [];
    if (p.is_captain) details.push('Kapten Tim');
    if (p.captain_wa) details.push(`WA: ${p.captain_wa}`);
    if (p.grade) details.push(p.grade);
    if (p.gender) details.push(p.gender === 'L' ? 'Laki-Laki' : 'Perempuan');
    return `${idx + 1}. ${p.name || `Peserta ${idx + 1}`} (${details.join(', ')})`;
  });

  form.participants_names = lines.join('\n');
  if (repeaterField.value) {
    mergedCustom[repeaterField.value.name] = activeList;
  }

  form.custom_fields = mergedCustom;

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
  if (props.preselectedLomba) {
    const matched = props.competitions?.find(c => c.id === props.preselectedLomba);
    if (matched) form.competition_id = matched.id;
  }
  if (props.authRegistrant?.school_name) {
    form.school_name = props.authRegistrant.school_name;
  }
  if (props.authRegistrant?.name) {
    form.mentor_name = props.authRegistrant.name;
  }
  if (props.authRegistrant?.phone) {
    form.mentor_wa = props.authRegistrant.phone;
  }
  if (props.authRegistrant?.school_district && !form.custom_fields['school_district']) {
    form.custom_fields['school_district'] = props.authRegistrant.school_district;
  }
});
</script>

<template>
  <PublicLayout>
    <Head :title="activeComp ? `Formulir Pendaftaran ${activeComp.title} - Semarak Aliyah XIV` : 'Formulir Pendaftaran - Semarak Aliyah XIV 2026'" />

    <div class="reg-page-wrapper">
      <!-- HEADER RINGKAS & NAVIGASI PUBLIK -->
      <header class="reg-top-nav">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
          <!-- Logo Resmi & Branding -->
          <a class="flex items-center gap-3.5 group" href="/">
            <img 
              alt="Logo Resmi Semarak Aliyah XIV 2026" 
              class="h-11 w-11 sm:h-12 sm:w-12 object-contain transition-transform group-hover:scale-105" 
              src="/assets/img/logo.webp"
            />
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
          <nav class="flex items-center gap-2 sm:gap-5">
            <a class="hidden md:inline-flex text-sm font-medium text-on-surface-variant hover:text-primary transition-colors" href="/#kompetisi">
              Tentang Lomba
            </a>
            <a 
              v-if="activeComp?.juknis_url" 
              class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-medium text-on-surface-variant hover:text-primary transition-colors px-3 py-1.5 rounded-lg hover:bg-emerald-light/60" 
              :href="activeComp.juknis_url" 
              target="_blank"
            >
              <i class="ri-download-line text-base"></i>
              <span>Juknis Lomba</span>
            </a>
            <a 
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-emerald-light hover:bg-primary text-emerald-dark hover:text-white font-semibold text-xs sm:text-sm transition-all border border-primary/20 hover:border-transparent" 
              :href="helpWaUrl" 
              target="_blank"
            >
              <i class="ri-customer-service-2-line text-base"></i>
              <span>Bantuan Panitia</span>
            </a>
          </nav>
        </div>
      </header>

      <!-- MAIN REGISTRATION SECTION -->
      <main class="flex-1 py-8 sm:py-12 px-4 sm:px-6">
        <div class="max-w-4xl mx-auto">

          <!-- SUCCESS STATE RECEIPT -->
          <div v-if="isSuccess" class="bg-surface-card rounded-2xl border border-border-subtle shadow-xl p-6 sm:p-10 space-y-6 text-center">
            <div class="w-16 h-16 rounded-full bg-emerald-light text-primary flex items-center justify-center mx-auto text-3xl">
              <i class="ri-checkbox-circle-fill"></i>
            </div>
            <div class="space-y-2">
              <h2 class="font-headline font-bold text-2xl text-on-surface">Pendaftaran Berhasil Dikirim!</h2>
              <p class="text-sm text-on-surface-variant max-w-lg mx-auto">
                Terima kasih, formulir pendaftaran delegasi kontingen Anda telah berhasil diterima oleh sistem Panitia Semarak Aliyah XIV 2026.
              </p>
            </div>

            <div v-if="registeredData" class="max-w-md mx-auto p-4 rounded-xl border border-primary/20 bg-emerald-light/30 space-y-2 text-left text-xs sm:text-sm">
              <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                <span class="text-on-surface-variant">Kode Pendaftaran:</span>
                <span class="font-mono font-bold text-primary">{{ registeredData.reg_code }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                <span class="text-on-surface-variant">Asal Sekolah:</span>
                <span class="font-semibold text-on-surface">{{ registeredData.school_name }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                <span class="text-on-surface-variant">Cabang Lomba:</span>
                <span class="font-semibold text-on-surface">{{ registeredData.competition_title }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                <span class="text-on-surface-variant">Total Biaya:</span>
                <span class="font-bold text-emerald-dark">Rp {{ Number(registeredData.total_fee || 0).toLocaleString('id-ID') }}</span>
              </div>
            </div>

            <div class="flex items-center justify-center gap-3 pt-4 flex-wrap">
              <a href="/#kompetisi" class="px-5 py-2.5 rounded-xl border border-border-subtle hover:bg-background text-sm font-semibold text-on-surface transition-colors">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Beranda
              </a>
              <button 
                type="button" 
                @click="isSuccess = false; form.reset()" 
                class="px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-white text-sm font-semibold shadow-md transition-colors"
              >
                <i class="ri-add-line me-1"></i> Daftarkan Cabang Lain
              </button>
            </div>
          </div>

          <!-- REGISTRATION FORM -->
          <div v-else>
            <!-- HERO HEADER ELEGAN & RINGKAS -->
            <div class="text-center mb-8 sm:mb-10 space-y-3">
              <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-light text-emerald-dark text-xs sm:text-sm font-semibold border border-primary/20 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                Pendaftaran Resmi • Jenjang SMP / MTs Sederajat
              </div>
              <h1 class="font-headline font-extrabold text-2xl sm:text-4xl text-on-surface tracking-tight">
                Formulir Pendaftaran Cabang {{ activeComp?.title || 'Lomba' }}
              </h1>
              <p class="text-on-surface-variant text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
                Silakan lengkapi data delegasi sekolah, peserta, serta berkas persyaratan Anda. Proses cepat, mudah, dan langsung terverifikasi oleh panitia.
              </p>
            </div>

            <!-- FORMULIR TERPUSAT (CLEAN & TO-THE-POINT) -->
            <form 
              id="publicRegisterForm" 
              class="bg-surface-card rounded-2xl border border-border-subtle shadow-xl shadow-primary/5 p-6 sm:p-10 space-y-8" 
              novalidate 
              @submit.prevent="handleSubmit"
            >
              <!-- VALIDATION ERROR SUMMARY BANNER -->
              <div 
                v-if="errorSummaryList.length > 0" 
                id="form-validation-summary" 
                class="p-4 rounded-xl border border-red-300 bg-red-50 text-red-900 space-y-2.5"
              >
                <div class="flex items-center gap-2 text-sm font-bold text-red-700">
                  <i class="ri-error-warning-fill text-lg"></i>
                  <span>Mohon lengkapi {{ errorSummaryList.length }} data wajib berikut:</span>
                </div>
                <ul class="text-xs space-y-1.5 pl-6 list-disc text-red-700">
                  <li 
                    v-for="(err, idx) in errorSummaryList" 
                    :key="idx" 
                    @click="scrollToField(err.key)" 
                    class="cursor-pointer hover:underline font-medium"
                  >
                    {{ err.message }}
                  </li>
                </ul>
              </div>

              <!-- PILIH CABANG LOMBA -->
              <div class="p-4 rounded-xl bg-background/50 border border-border-subtle space-y-2">
                <div class="flex items-center justify-between flex-wrap gap-2">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface flex items-center gap-2">
                    <i class="ri-trophy-line text-primary text-base"></i>
                    Cabang Lomba yang Didaftarkan <span class="text-red-500">*</span>
                  </label>
                  <span class="text-[11px] font-semibold text-emerald-dark bg-emerald-light px-2.5 py-0.5 rounded-full border border-primary/20">
                    {{ activeComp?.category ? `${activeComp.category} • ` : '' }}Biaya: Rp {{ selectedCompFee.toLocaleString('id-ID') }}
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
                  <i class="ri-bank-line text-primary text-xl"></i>
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
                        class="w-full h-11 px-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400"
                        :class="{ 'border-red-400 ring-2 ring-red-200': hasFieldError('school_name') }"
                        placeholder="Contoh: MTs Unggulan Balung atau SMP Negeri 1 Jember" 
                        required 
                        type="text"
                      />
                      <span v-if="isSearchingSchools" class="absolute right-3.5 top-3 text-primary text-sm">
                        <i class="ri-loader-4-line animate-spin"></i>
                      </span>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div 
                      v-if="showSchoolDropdown && schoolSuggestions.length > 0" 
                      class="absolute z-30 left-0 right-0 mt-1 bg-white rounded-xl border border-border-subtle shadow-xl overflow-hidden max-h-56 overflow-y-auto"
                    >
                      <div 
                        v-for="s in schoolSuggestions" 
                        :key="s.id || s.name"
                        @mousedown.prevent="selectSchool(s)"
                        class="px-4 py-2.5 hover:bg-emerald-light/60 cursor-pointer flex items-center justify-between border-b border-border-subtle/50 last:border-b-0 transition-colors"
                      >
                        <div>
                          <div class="text-xs sm:text-sm font-semibold text-on-surface">{{ s.name }}</div>
                          <div class="text-[11px] text-on-surface-variant">{{ s.kecamatan ? `Kec. ${s.kecamatan}` : '' }} {{ s.jenjang || 'SMP/MTs' }}</div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-light text-emerald-dark font-semibold">Pilih</span>
                      </div>
                    </div>
                    <p v-if="getFieldError('school_name')" class="text-xs text-red-500 mt-1">{{ getFieldError('school_name') }}</p>
                  </div>

                  <!-- Kecamatan Asal Sekolah (Using CustomSelect) -->
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
                    <p v-if="getFieldError('school_district')" class="text-xs text-red-500 mt-1">{{ getFieldError('school_district') }}</p>
                  </div>

                  <!-- Alamat Singkat Sekolah -->
                  <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                      Alamat Sekolah / Desa
                    </label>
                    <input 
                      v-model="form.custom_fields['school_address']"
                      class="w-full h-11 px-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400"
                      placeholder="Contoh: Jl. Wahid Hasyim No. 20"
                      type="text"
                    />
                  </div>

                  <!-- Nama Guru Pembina / Pendamping -->
                  <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                      Nama Guru Pembina / Pendamping <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <span class="absolute left-3.5 top-2.5 text-gray-400 text-lg">
                        <i class="ri-user-line"></i>
                      </span>
                      <input 
                        id="reg-pendamping"
                        v-model="form.mentor_name"
                        @input="clearFieldError('mentor_name')"
                        class="w-full h-11 pl-10 pr-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400"
                        :class="{ 'border-red-400 ring-2 ring-red-200': hasFieldError('mentor_name') }"
                        placeholder="Ust. Ahmad Hidayat, S.Pd." 
                        required 
                        type="text"
                      />
                    </div>
                    <p v-if="getFieldError('mentor_name')" class="text-xs text-red-500 mt-1">{{ getFieldError('mentor_name') }}</p>
                  </div>

                  <!-- Nomor WhatsApp Aktif Pembina -->
                  <div class="space-y-1.5">
                    <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                      Nomor WhatsApp Aktif Pembina <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                      <span class="absolute left-3.5 top-2.5 text-emerald-dark text-lg">
                        <i class="ri-whatsapp-line"></i>
                      </span>
                      <input 
                        id="reg-wa"
                        v-model="form.mentor_wa"
                        @input="clearFieldError('mentor_wa')"
                        class="w-full h-11 pl-10 pr-3.5 rounded-xl border border-border-subtle bg-background/50 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400"
                        :class="{ 'border-red-400 ring-2 ring-red-200': hasFieldError('mentor_wa') }"
                        placeholder="0812-3456-7890 (Untuk Grup TM)" 
                        required 
                        type="tel"
                      />
                    </div>
                    <p v-if="getFieldError('mentor_wa')" class="text-xs text-red-500 mt-1">{{ getFieldError('mentor_wa') }}</p>
                  </div>
                </div>
              </div>

              <!-- SECTION 2: Data Peserta & Skema Paket -->
              <div class="space-y-4" id="sectionDataPeserta">
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle flex-wrap gap-2">
                  <div class="flex items-center gap-2.5">
                    <i class="ri-shield-user-line text-primary text-xl"></i>
                    <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">2. Data Peserta &amp; Skema Paket</h2>
                  </div>
                  <span class="text-xs text-on-surface-variant font-medium">
                    {{ teamInfo.isTeam ? `Format Tim (Maks. ${teamInfo.max} Siswa)` : 'Maksimal 2 peserta per delegasi cabang' }}
                  </span>
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
                    <label class="flex items-center justify-between p-3.5 rounded-xl border border-border-subtle bg-white cursor-pointer hover:border-primary/60 transition-all has-[:checked]:border-primary has-[:checked]:bg-emerald-light/50 has-[:checked]:shadow-xs">
                      <div class="flex items-center gap-2.5">
                        <input 
                          type="radio" 
                          name="skema_paket" 
                          value="Paket Satuan" 
                          v-model="form.package_type" 
                          class="w-4 h-4 text-primary focus:ring-primary"
                        />
                        <div class="text-left">
                          <div class="flex items-center gap-1.5">
                            <p class="text-xs sm:text-sm font-bold text-on-surface">Paket Satuan</p>
                            <span class="text-[10px] bg-primary/10 text-primary font-bold px-1.5 py-0.5 rounded">Cabang Ini</span>
                          </div>
                          <p class="text-xs font-semibold text-primary mt-0.5">Rp {{ selectedCompFee.toLocaleString('id-ID') }}</p>
                          <p class="text-[11px] text-on-surface-variant">Khusus pendaftaran cabang {{ activeComp?.title || 'ini' }}</p>
                        </div>
                      </div>
                      <i class="ri-price-tag-3-line text-primary text-xl"></i>
                    </label>

                    <!-- Paket All-In Komplit -->
                    <label class="flex items-center justify-between p-3.5 rounded-xl border border-border-subtle bg-white cursor-pointer hover:border-primary/60 transition-all has-[:checked]:border-primary has-[:checked]:bg-emerald-light/50 has-[:checked]:shadow-xs">
                      <div class="flex items-center gap-2.5">
                        <input 
                          type="radio" 
                          name="skema_paket" 
                          value="Paket All-In" 
                          v-model="form.package_type" 
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
                      <i class="ri-verified-badge-line text-primary text-xl"></i>
                    </label>
                  </div>

                  <!-- Masukkan Kode Paket Komplit (All-In) -->
                  <div class="mt-3 p-3.5 rounded-xl border border-dashed border-primary/40 bg-emerald-light/30 space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                      <label class="text-xs font-semibold text-on-surface flex items-center gap-1.5">
                        <i class="ri-key-2-line text-primary text-base"></i>
                        <span>Kode Paket Komplit (All-In)</span>
                      </label>
                      <span class="text-[11px] text-amber-dark font-medium bg-amber-soft px-2 py-0.5 rounded">Khusus Paket All-In</span>
                    </div>

                    <div v-if="isManualCodeVerified" class="p-3 bg-white rounded-lg border border-primary/30 flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <i class="ri-checkbox-circle-fill text-emerald-dark text-lg"></i>
                        <div>
                          <div class="text-xs font-bold text-primary">{{ manualCodeSuccessData?.reg_code || form.custom_fields['allin_code'] }}</div>
                          <div class="text-[11px] text-emerald-dark font-medium">Terverifikasi: Bebas Biaya Seluruh Cabang Lomba</div>
                        </div>
                      </div>
                      <button type="button" @click="clearManualPackageCode" class="text-xs text-red-500 hover:underline">
                        Hapus
                      </button>
                    </div>

                    <div v-else class="flex items-center gap-2">
                      <div class="relative flex-1">
                        <input 
                          type="text" 
                          v-model="form.custom_fields['allin_code']"
                          placeholder="Masukkan kode verifikasi paket (misal: PKT-ALLIN-XXXX)" 
                          class="w-full h-10 px-3.5 rounded-lg border border-border-subtle bg-white text-xs font-mono uppercase focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:font-body placeholder:normal-case placeholder:text-gray-400"
                        />
                      </div>
                      <button 
                        type="button" 
                        @click="verifyPackageCodeManual"
                        :disabled="isCheckingPackageCode"
                        class="h-10 px-4 rounded-lg bg-primary hover:bg-primary-container text-white text-xs font-semibold flex items-center gap-1.5 shrink-0 transition-colors shadow-xs disabled:opacity-50"
                      >
                        <i v-if="isCheckingPackageCode" class="ri-loader-4-line animate-spin text-sm"></i>
                        <i v-else class="ri-checkbox-circle-line text-sm"></i>
                        <span>Cek Kode</span>
                      </button>
                    </div>

                    <p v-if="codeVerificationError" class="text-xs text-red-600 font-medium">
                      {{ codeVerificationError }}
                    </p>
                    <p v-else class="text-[11px] text-on-surface-variant flex items-center gap-1">
                      <i class="ri-information-line text-primary"></i>
                      <span>Wajib diisi jika sekolah Anda telah mendaftarkan paket kolektif/all-in cabang lomba</span>
                    </p>
                  </div>
                </div>

                <!-- Selector Jumlah Delegasi Peserta -->
                <div class="p-4 rounded-xl bg-background/50 border border-border-subtle space-y-3">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                    <div class="flex items-center gap-2">
                      <i class="ri-group-line text-primary text-lg"></i>
                      <label class="block text-xs sm:text-sm font-semibold text-on-surface">Jumlah Delegasi Peserta</label>
                    </div>
                    <span class="text-[11px] font-medium text-emerald-dark bg-emerald-light border border-primary/20 px-2.5 py-0.5 rounded-full inline-flex items-center gap-1 w-fit">
                      <i class="ri-information-line text-xs"></i>
                      {{ teamInfo.isTeam ? `Maks. ${teamInfo.max} Anggota Tim` : 'Maks. 2 Peserta per Sekolah/Madrasah' }}
                    </span>
                  </div>

                  <!-- Selector for Individual (1 vs 2) -->
                  <div v-if="!teamInfo.isTeam" class="grid grid-cols-2 p-1 bg-white border border-border-subtle rounded-xl gap-1 shadow-xs">
                    <label 
                      class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg cursor-pointer text-xs sm:text-sm font-semibold text-on-surface-variant transition-all hover:text-primary"
                      :class="{ 'bg-emerald-light text-primary shadow-xs border border-primary/20': participantCount === 1 }"
                    >
                      <input type="radio" name="jumlah_peserta" :value="1" :checked="participantCount === 1" @change="updateJumlahPeserta(1)" class="hidden" />
                      <i class="ri-user-line text-base"></i>
                      <span>1 Peserta (Tunggal)</span>
                    </label>

                    <label 
                      class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg cursor-pointer text-xs sm:text-sm font-semibold text-on-surface-variant transition-all hover:text-primary"
                      :class="{ 'bg-emerald-light text-primary shadow-xs border border-primary/20': participantCount === 2 }"
                    >
                      <input type="radio" name="jumlah_peserta" :value="2" :checked="participantCount === 2" @change="updateJumlahPeserta(2)" class="hidden" />
                      <i class="ri-group-line text-base"></i>
                      <span>2 Peserta (Maksimal)</span>
                    </label>
                  </div>

                  <!-- Selector for Team (Count Pills) -->
                  <div v-else class="flex items-center gap-2 flex-wrap">
                    <button 
                      v-for="c in (teamInfo.max - teamInfo.min + 1)" 
                      :key="c"
                      type="button"
                      @click="updateJumlahPeserta(teamInfo.min + c - 1)"
                      class="h-9 px-3.5 rounded-lg border text-xs font-semibold transition-all"
                      :class="(teamInfo.min + c - 1) === participantCount ? 'bg-primary text-white border-primary shadow-xs' : 'bg-white border-border-subtle text-on-surface hover:border-primary/50'"
                    >
                      {{ teamInfo.min + c - 1 }} Anggota
                    </button>
                  </div>
                </div>

                <!-- Kartu Peserta List -->
                <div class="space-y-3.5">
                  <div 
                    v-for="(p, pIdx) in participantsList.slice(0, participantCount)" 
                    :key="pIdx"
                    class="p-4 sm:p-5 rounded-xl border border-border-subtle bg-white space-y-3.5 shadow-xs"
                    :class="{ 'border-dashed border-primary/50': pIdx > 0 }"
                  >
                    <div class="flex items-center justify-between border-b border-border-subtle pb-2">
                      <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-light text-emerald-dark font-headline text-xs font-bold flex items-center justify-center">
                          {{ pIdx + 1 }}
                        </span>
                        <h3 class="font-headline font-bold text-sm text-on-surface">Data Peserta {{ pIdx + 1 }}</h3>
                      </div>
                      <span class="text-[11px] font-semibold text-emerald-dark bg-emerald-light px-2 py-0.5 rounded-full">
                        {{ pIdx === 0 ? 'Peserta Utama' : `Peserta ${pIdx + 1} (Tambahan)` }}
                      </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
                      <!-- Nama Lengkap Siswa -->
                      <div class="space-y-1.5 md:col-span-6">
                        <label class="block text-xs font-semibold text-on-surface">
                          Nama Lengkap Siswa <span class="text-red-500">*</span>
                        </label>
                        <input 
                          v-model="p.name"
                          @input="clearFieldError(`participant_${pIdx}_name`)"
                          class="w-full h-11 px-3.5 rounded-xl border border-border-subtle bg-background/40 text-sm focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all placeholder:text-gray-400"
                          :class="{ 'border-red-400 ring-2 ring-red-200': hasFieldError(`participant_${pIdx}_name`) }"
                          placeholder="Sesuai rapor / akta kelahiran" 
                          required 
                          type="text"
                        />
                        <p v-if="getFieldError(`participant_${pIdx}_name`)" class="text-xs text-red-500 mt-1">
                          {{ getFieldError(`participant_${pIdx}_name`) }}
                        </p>
                      </div>

                      <!-- Kelas / Tingkat (Using CustomSelect) -->
                      <div class="space-y-1.5 md:col-span-3">
                        <label class="block text-xs font-semibold text-on-surface">
                          Kelas / Tingkat <span class="text-red-500">*</span>
                        </label>
                        <CustomSelect 
                          v-model="p.grade"
                          :options="['Kelas 7 SMP/MTs', 'Kelas 8 SMP/MTs', 'Kelas 9 SMP/MTs']"
                          placeholder="Pilih Kelas..."
                          size="md"
                        />
                      </div>

                      <!-- Jenis Kelamin (Radio Pills) -->
                      <div class="space-y-1.5 md:col-span-3">
                        <label class="block text-xs font-semibold text-on-surface">
                          Jenis Kelamin <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2 h-11">
                          <label 
                            class="flex items-center justify-center gap-1 border border-border-subtle rounded-xl cursor-pointer text-xs font-semibold text-on-surface-variant hover:bg-emerald-light/40 transition-colors"
                            :class="{ 'bg-emerald-light border-primary text-primary': p.gender === 'L' }"
                          >
                            <input type="radio" :name="`gender_${pIdx}`" value="L" v-model="p.gender" class="hidden" />
                            <span>Laki-Laki</span>
                          </label>
                          <label 
                            class="flex items-center justify-center gap-1 border border-border-subtle rounded-xl cursor-pointer text-xs font-semibold text-on-surface-variant hover:bg-emerald-light/40 transition-colors"
                            :class="{ 'bg-emerald-light border-primary text-primary': p.gender === 'P' }"
                          >
                            <input type="radio" :name="`gender_${pIdx}`" value="P" v-model="p.gender" class="hidden" />
                            <span>Perempuan</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- SECTION 3: Naskah Puisi & Berkas Persyaratan -->
              <div class="space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle">
                  <div class="flex items-center gap-2.5">
                    <i class="ri-upload-cloud-2-line text-primary text-xl"></i>
                    <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">3. Naskah Puisi &amp; Berkas</h2>
                  </div>
                  <span class="text-xs text-on-surface-variant">PDF/ZIP/Doc (Maks 10 MB)</span>
                </div>

                <!-- Custom Dashed File Upload Dropzone -->
                <div 
                  class="border-2 border-dashed border-border-subtle hover:border-primary/60 rounded-xl p-5 bg-background/50 hover:bg-emerald-light/20 transition-all flex flex-col sm:flex-row items-center justify-between gap-4 cursor-pointer relative"
                  @click="$refs.naskahInputRef?.click()"
                >
                  <input 
                    ref="naskahInputRef" 
                    class="hidden" 
                    type="file" 
                    accept=".pdf,.doc,.docx,.zip,.rar,image/*" 
                    @change="handleNaskahChange" 
                  />

                  <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-emerald-light text-primary flex items-center justify-center shrink-0 text-2xl">
                      <i class="ri-file-text-line"></i>
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
                      v-if="selectedNaskahFile" 
                      type="button" 
                      @click.stop="clearNaskahFile" 
                      class="px-3 py-2 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 text-xs font-semibold transition-colors"
                    >
                      Hapus
                    </button>
                    <button 
                      class="px-4 py-2 rounded-lg bg-white border border-border-subtle shadow-xs text-xs font-semibold text-on-surface hover:text-primary shrink-0 transition-colors" 
                      type="button"
                    >
                      {{ selectedNaskahFile ? 'Ganti Dokumen' : 'Pilih Dokumen' }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- SECTION 4: Metode Pembayaran & Biaya -->
              <div class="space-y-4" id="sectionPembayaran">
                <div class="flex items-center justify-between pb-2 border-b border-border-subtle flex-wrap gap-2">
                  <div class="flex items-center gap-2.5">
                    <i class="ri-wallet-3-line text-primary text-xl"></i>
                    <h2 class="font-headline font-bold text-base sm:text-lg text-on-surface">4. Metode Pembayaran &amp; Biaya</h2>
                  </div>
                  <span class="text-xs font-bold text-primary bg-emerald-light px-2.5 py-1 rounded-full">
                    Biaya: Rp {{ calculatedFee.toLocaleString('id-ID') }} ({{ form.package_type }})
                  </span>
                </div>

                <!-- Verified Free All-In Banner -->
                <div v-if="isAllInFree" class="p-4 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-900 flex items-center gap-3">
                  <i class="ri-checkbox-circle-fill text-2xl text-emerald-dark"></i>
                  <div class="text-xs sm:text-sm">
                    <span class="font-bold">Bebas Biaya Pendaftaran:</span> Sekolah Anda telah terverifikasi Paket Komplit All-In. Anda tidak perlu melakukan pembayaran untuk cabang lomba ini.
                  </div>
                </div>

                <!-- Pilihan Saluran Pembayaran (3 Cards) -->
                <div v-else class="space-y-2.5">
                  <label class="block text-xs sm:text-sm font-semibold text-on-surface">
                    Pilih Saluran Pembayaran <span class="text-red-500">*</span>
                  </label>

                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label 
                      v-for="opt in paymentOptions" 
                      :key="opt.id"
                      class="p-3.5 rounded-xl border border-border-subtle bg-white cursor-pointer hover:border-primary/60 transition-all flex flex-col justify-between gap-2.5 has-[:checked]:border-primary has-[:checked]:bg-emerald-light/40 has-[:checked]:ring-1 has-[:checked]:ring-primary"
                    >
                      <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                          <input 
                            type="radio" 
                            name="payment_channel" 
                            :value="opt.value" 
                            v-model="form.payment_method" 
                            class="w-4 h-4 text-primary focus:ring-primary"
                          />
                          <span class="text-xs font-bold uppercase" :class="opt.badgeClass">{{ opt.badge }}</span>
                        </div>
                        <i :class="opt.icon" class="text-lg text-primary"></i>
                      </div>

                      <div>
                        <div class="flex items-center justify-between">
                          <p class="font-mono text-sm font-bold text-on-surface">{{ opt.displayAccount }}</p>
                          <button 
                            v-if="!opt.isCash" 
                            type="button" 
                            @click.stop="copyAccount(opt.accountNumber)" 
                            class="p-1 text-on-surface-variant hover:text-primary" 
                            title="Salin No. Rekening"
                          >
                            <i class="ri-file-copy-line text-sm"></i>
                          </button>
                        </div>
                        <p class="text-[11px] text-on-surface-variant">{{ opt.accountHolder }}</p>
                      </div>
                    </label>
                  </div>
                </div>

                <!-- Upload Bukti Transfer (Opsional / Langsung) -->
                <div v-if="!isAllInFree" class="p-4 rounded-xl border border-border-subtle bg-background/50 space-y-2.5">
                  <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-on-surface flex items-center gap-1.5">
                      <i class="ri-file-list-3-line text-primary text-base"></i>
                      <span>Upload Bukti Pembayaran / Transfer</span>
                    </label>
                    <span class="text-[11px] text-on-surface-variant font-medium">Bisa disusulkan ke WhatsApp</span>
                  </div>

                  <div class="flex items-center gap-3">
                    <input 
                      type="file" 
                      ref="paymentProofRef" 
                      @change="handlePaymentProofChange" 
                      class="hidden" 
                      accept="image/*,.pdf" 
                    />
                    <button 
                      type="button" 
                      @click="$refs.paymentProofRef?.click()" 
                      class="px-3.5 py-2 rounded-lg bg-white border border-border-subtle hover:border-primary text-xs font-semibold text-on-surface flex items-center gap-2 transition-colors"
                    >
                      <i class="ri-image-add-line text-primary text-base"></i>
                      <span>{{ form.payment_proof ? 'Ganti Bukti' : 'Pilih Bukti Transfer' }}</span>
                    </button>
                    <span class="text-xs text-on-surface-variant truncate max-w-xs">
                      {{ form.payment_proof ? `${form.payment_proof.name} (${formatFileSize(form.payment_proof.size)})` : 'Format: JPG, PNG, atau PDF (Maks 5 MB)' }}
                    </span>
                    <button 
                      v-if="form.payment_proof" 
                      type="button" 
                      @click="clearPaymentProof" 
                      class="text-xs text-red-500 hover:underline"
                    >
                      Hapus
                    </button>
                  </div>
                </div>
              </div>

              <!-- SUBMIT ACTION CTA -->
              <div class="space-y-6 pt-4 border-t border-border-subtle">
                <!-- SECTION 5: Ringkasan Delegasi & Skema Biaya -->
                <div class="rounded-xl border border-primary/20 bg-emerald-light/30 p-5 space-y-3.5 shadow-xs">
                  <div class="flex items-center justify-between pb-2 border-b border-primary/10 flex-wrap gap-2">
                    <div class="flex items-center gap-2">
                      <i class="ri-file-check-line text-primary text-lg"></i>
                      <h3 class="font-headline font-bold text-sm sm:text-base text-on-surface">Ringkasan Delegasi &amp; Pendaftaran</h3>
                    </div>
                    <span class="text-[11px] font-bold text-emerald-dark bg-emerald-light px-2.5 py-0.5 rounded-full border border-primary/20">
                      Resmi Panitia
                    </span>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                      <span class="text-on-surface-variant">Cabang Lomba:</span>
                      <span class="font-semibold text-on-surface">{{ activeComp?.title || 'Baca Puisi' }} (SMP/MTs)</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-dashed border-primary/10">
                      <span class="text-on-surface-variant">Skema Paket:</span>
                      <span class="font-bold text-primary">{{ form.package_type }} (Rp {{ calculatedFee.toLocaleString('id-ID') }})</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-dashed border-primary/10 sm:border-b-0">
                      <span class="text-on-surface-variant">Jumlah Delegasi Cabang:</span>
                      <span class="font-semibold text-on-surface">
                        {{ participantCount }} Siswa {{ (!teamInfo.isTeam && participantCount > 1) ? `(@ Rp ${selectedCompFee.toLocaleString('id-ID')})` : '' }}
                      </span>
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
                        Rp {{ calculatedFee.toLocaleString('id-ID') }}
                      </p>
                    </div>
                    <div class="text-right">
                      <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-on-surface-variant bg-white px-2.5 py-1 rounded-lg border border-border-subtle">
                        <i class="ri-checkbox-circle-fill text-primary text-sm"></i> Bebas Biaya Admin
                      </span>
                    </div>
                  </div>
                </div>

                <!-- SUBMIT ACTION CTA BUTTON -->
                <div class="space-y-3">
                  <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-4 rounded-xl bg-primary hover:bg-primary-container text-white font-headline font-bold text-base sm:text-lg flex items-center justify-center gap-2.5 shadow-lg shadow-primary/20 hover:shadow-xl transition-all active:scale-[0.99] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <i v-if="form.processing" class="ri-loader-4-line animate-spin text-xl"></i>
                    <i v-else class="ri-send-plane-fill text-xl"></i>
                    <span>
                      {{ form.processing ? 'Mengirim Formulir...' : `Kirim Formulir & Bayar • Rp ${calculatedFee.toLocaleString('id-ID')}` }}
                    </span>
                  </button>
                  <div class="flex items-center justify-center gap-2 text-xs text-on-surface-variant text-center">
                    <i class="ri-checkbox-circle-fill text-primary text-base"></i>
                    <span>Data pendaftaran resmi terintegrasi dengan Panitia Semarak Aliyah XIV 2026.</span>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </main>

      <!-- NOTIFICATION TOAST -->
      <div 
        class="fixed bottom-6 right-6 z-50 transform transition-all duration-300 pointer-events-none"
        :class="toast.show ? 'translate-y-0 opacity-100' : 'translate-y-24 opacity-0'"
      >
        <div class="flex items-center gap-2.5 bg-gray-900 text-white px-4 py-3 rounded-xl shadow-xl text-sm">
          <i :class="toast.icon" class="text-emerald-400 text-lg"></i>
          <span>{{ toast.message }}</span>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<style scoped>
/* =============================================================================
   SEMARAK ALIYAH XIV - DEDICATED CLEAN & PROFESSIONAL FORM STYLING
   Matching User Mockup Aesthetic + WCAG AA High-Contrast Dark & Light Mode
   ============================================================================= */

.reg-page-wrapper {
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
  min-height: 100vh;
}

/* Light / Dark Mode WCAG AA Enforcement */
[data-bs-theme="dark"] .reg-page-wrapper,
.dark .reg-page-wrapper {
  --color-primary: #10b981;
  --color-primary-container: #059669;
  --color-background: #090e0b;
  --color-surface-card: #131d17;
  --color-on-surface: #f8fafc;
  --color-on-surface-variant: #cbd5e1;
  --color-border-subtle: #24352a;
  --color-emerald-light: rgba(16, 185, 129, 0.18);
  --color-emerald-dark: #86efac;
  --color-amber-soft: rgba(245, 158, 11, 0.2);
  --color-amber-dark: #fcd34d;
}

.reg-top-nav {
  position: sticky;
  top: 0;
  z-index: 40;
  width: 100%;
  background-color: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--color-border-subtle);
}

[data-bs-theme="dark"] .reg-top-nav,
.dark .reg-top-nav {
  background-color: rgba(19, 29, 23, 0.95);
}

/* Typography tokens */
.font-headline {
  font-family: 'Plus Jakarta Sans', sans-serif;
}

.font-body {
  font-family: 'Inter', sans-serif;
}

/* Color class mappings */
.text-primary {
  color: var(--color-primary) !important;
}

.bg-primary {
  background-color: var(--color-primary) !important;
}

.bg-primary-container {
  background-color: var(--color-primary-container) !important;
}

.bg-background {
  background-color: var(--color-background);
}

.bg-surface-card {
  background-color: var(--color-surface-card);
}

.text-on-surface {
  color: var(--color-on-surface);
}

.text-on-surface-variant {
  color: var(--color-on-surface-variant);
}

.border-border-subtle {
  border-color: var(--color-border-subtle);
}

.bg-emerald-light {
  background-color: var(--color-emerald-light);
}

.text-emerald-dark {
  color: var(--color-emerald-dark);
}

.bg-amber-soft {
  background-color: var(--color-amber-soft);
}

.text-amber-dark {
  color: var(--color-amber-dark);
}

/* Focus and active transitions */
input:focus, select:focus {
  border-color: var(--color-primary) !important;
  box-shadow: 0 0 0 3px rgba(0, 105, 72, 0.15) !important;
}

[data-bs-theme="dark"] input {
  background-color: #0b1310;
  color: #f8fafc;
}

[data-bs-theme="dark"] input::placeholder {
  color: #64748b;
}

[data-bs-theme="dark"] .bg-white {
  background-color: #17231c !important;
  color: #f8fafc;
}
</style>
