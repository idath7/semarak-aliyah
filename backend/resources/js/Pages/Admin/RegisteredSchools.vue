<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';
import { usePermission } from '@/Composables/usePermission';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  schools: { type: Array, default: () => [] },
  competitions: { type: Array, default: () => [] },
  master_schools: { type: Array, default: () => [] },
  districts: { type: Array, default: () => [] },
  all_registered_school_names: { type: Array, default: () => [] },
  package_allin_price: { type: Number, default: 200000 },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const toast = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// -----------------------------------------------------------------------------
// FILTER & SEARCH STATE
// -----------------------------------------------------------------------------
const searchQuery = ref(props.filters.search || '');
const packageFilter = ref(props.filters.package || 'all');
const paymentStatusFilter = ref(props.filters.payment_status || 'all');
const competitionFilter = ref(props.filters.competition_id || 'all');
const viewMode = ref('card'); // 'card' | 'table'

// Options for CustomSelect filters
const packageFilterOptions = [
  { value: 'all', label: 'Semua Tipe Paket' },
  { value: 'Paket All-In', label: 'Paket Komplit (All-In)' },
  { value: 'Paket Satuan', label: 'Paket Satuan Per Cabang' },
];

const paymentFilterOptions = [
  { value: 'all', label: 'Semua Status Bayar' },
  { value: 'Lunas', label: 'Lunas (Terverifikasi)' },
  { value: 'Menunggu Verifikasi', label: 'Menunggu Verifikasi' },
  { value: 'Sebagian', label: 'Bayar Sebagian' },
  { value: 'Belum Lunas', label: 'Belum Lunas' },
];

const competitionFilterOptions = computed(() => {
  const opts = [{ value: 'all', label: 'Semua Cabang Lomba' }];
  props.competitions.forEach(c => {
    opts.push({ value: String(c.id), label: `${c.title} (${c.category?.name || 'Umum'})` });
  });
  return opts;
});

function applyFilters() {
  router.get('/admin/sekolah-terdaftar', {
    search: searchQuery.value || undefined,
    package: packageFilter.value !== 'all' ? packageFilter.value : undefined,
    payment_status: paymentStatusFilter.value !== 'all' ? paymentStatusFilter.value : undefined,
    competition_id: competitionFilter.value !== 'all' ? competitionFilter.value : undefined,
  }, {
    preserveState: true,
    replace: true,
  });
}

function resetFilters() {
  searchQuery.value = '';
  packageFilter.value = 'all';
  paymentStatusFilter.value = 'all';
  competitionFilter.value = 'all';
  router.get('/admin/sekolah-terdaftar');
}

// -----------------------------------------------------------------------------
// FORMATTING HELPERS
// -----------------------------------------------------------------------------
function formatRupiah(num) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}

// -----------------------------------------------------------------------------
// DETAIL MODAL STATE
// -----------------------------------------------------------------------------
const showDetailModal = ref(false);
const activeSchool = ref(null);

function openSchoolDetail(school) {
  activeSchool.value = school;
  showDetailModal.value = true;
}

// -----------------------------------------------------------------------------
// HAK AKSES & FITUR HAPUS DATA (SUPER ADMIN)
// -----------------------------------------------------------------------------
const { can, isSuperAdmin } = usePermission();
const canDeleteSchool = computed(() => isSuperAdmin.value || can('registered_schools.delete') || can('schools.manage'));
const canDeleteDelegate = computed(() => isSuperAdmin.value || can('registrants.delete'));

async function deleteRegisteredSchool(school) {
  if (!school) return;
  const compCount = school.competitions?.length || school.total_competitions || 0;
  const confirmed = await confirmDialog({
    title: 'Hapus Sekolah Terdaftar?',
    message: `Apakah Anda yakin ingin menghapus seluruh data pendaftaran kontingen "${school.school_name}"? Sebanyak ${compCount} cabang lomba delegasi yang didaftarkan akan dihapus secara permanen dari sistem.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Seluruh Kontingen',
    cancelText: 'Batal',
  });

  if (!confirmed) return;

  router.post('/admin/sekolah-terdaftar/destroy', {
    school_name: school.school_name,
  }, {
    onSuccess: () => {
      showDetailModal.value = false;
      toast.showToast({
        type: 'success',
        title: 'Sekolah Dihapus',
        message: `Data sekolah ${school.school_name} beserta seluruh delegasinya berhasil dihapus permanen!`,
      });
    },
    onError: (errs) => {
      const msg = Object.values(errs)[0] || 'Gagal menghapus data sekolah.';
      toast.showToast({ type: 'danger', title: 'Gagal', message: msg });
    }
  });
}

async function deleteSingleDelegation(comp, schoolContext = null) {
  if (!comp) return;
  const sName = schoolContext?.school_name || activeSchool.value?.school_name || 'sekolah terkait';
  const confirmed = await confirmDialog({
    title: 'Hapus Delegasi Cabang Lomba?',
    message: `Apakah Anda yakin ingin menghapus delegasi cabang "${comp.competition_title}" (Kode: ${comp.reg_code}) dari ${sName}? Data delegasi ini akan dihapus permanen.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Delegasi Ini',
    cancelText: 'Batal',
  });

  if (!confirmed) return;

  const regId = comp.registration_id || comp.id;
  router.delete(`/admin/pendaftar/${regId}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (activeSchool.value && Array.isArray(activeSchool.value.competitions)) {
        activeSchool.value.competitions = activeSchool.value.competitions.filter(c => (c.registration_id || c.id) !== regId);
        if (activeSchool.value.competitions.length === 0) {
          showDetailModal.value = false;
        }
      }
      toast.showToast({
        type: 'success',
        title: 'Delegasi Dihapus',
        message: `Delegasi ${comp.competition_title} (${comp.reg_code}) berhasil dihapus permanen!`,
      });
    },
    onError: (errs) => {
      const msg = Object.values(errs)[0] || 'Gagal menghapus delegasi.';
      toast.showToast({ type: 'danger', title: 'Gagal', message: msg });
    }
  });
}

// -----------------------------------------------------------------------------
// MODAL: INPUT MANUAL (MEJA PANITIA / ON THE SPOT)
// -----------------------------------------------------------------------------
const showManualModal = ref(false);
const isSubmittingManual = ref(false);

const manualForm = ref({
  school_selection_mode: 'master', // 'master' | 'custom'
  selected_master_id: '',
  school_name: '',
  jenjang: 'SMP',
  kecamatan: '',
  custom_kecamatan: '',
  mentor_name: '',
  mentor_wa: '',
  mentor_email: '',
  package_type: 'Paket Satuan', // 'Paket Satuan' | 'Paket All-In'
  competition_id: '',
  competition_ids: [],
  custom_fee: null,
  package_price: props.package_allin_price,
  payment_status: 'Terverifikasi',
  payment_method: 'Tunai di Meja Panitia',
  notes: 'Pendaftaran langsung on-the-spot di Meja Panitia',
});

// Dropdown options for manual form
const jenjangOptions = [
  { value: 'SMP', label: 'SMP (Sekolah Menengah Pertama)' },
  { value: 'MTs', label: 'MTs (Madrasah Tsanawiyah)' },
  { value: 'Lainnya', label: 'Lainnya / Sederajat' },
];

// 31 Kecamatan Resmi Kabupaten Jember + Opsi Lainnya
const JEMBER_KECAMATAN_LIST = [
  'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 'Jenggawah',
  'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 'Mayang', 'Mumbulsari',
  'Pakusari', 'Panti', 'Patrang', 'Puger', 'Rambipuji', 'Semboro', 'Silo', 'Sukorambi',
  'Sukowono', 'Sumberbaru', 'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari',
  'Wuluhan', 'Lainnya'
];

// Opsi Kecamatan Lengkap untuk Dropdown CustomSelect
const kecamatanOptions = computed(() => {
  return JEMBER_KECAMATAN_LIST.map(k => ({
    value: k,
    label: k === 'Lainnya' ? '✍️ Lainnya (Ketik Manual)' : `Kecamatan ${k}`
  }));
});

function onSchoolNameInput() {
  if (manualForm.value.school_selection_mode === 'custom' && manualForm.value.school_name) {
    const nameUpper = manualForm.value.school_name.toUpperCase();
    if (nameUpper.includes('MTS') || nameUpper.includes('MADRASAH')) {
      manualForm.value.jenjang = 'MTs';
    } else if (nameUpper.includes('SMP')) {
      manualForm.value.jenjang = 'SMP';
    }

    // Auto-detect kecamatan jika belum terpilih
    if (!manualForm.value.kecamatan) {
      for (const k of JEMBER_KECAMATAN_LIST) {
        if (k !== 'Lainnya' && new RegExp('\\b' + k + '\\b', 'i').test(manualForm.value.school_name)) {
          manualForm.value.kecamatan = k;
          manualForm.value.custom_kecamatan = '';
          break;
        }
      }
    }
  }
}

const manualPaymentStatusOptions = [
  { value: 'Terverifikasi', label: 'Terverifikasi (Lunas Langsung di Tempat)' },
  { value: 'Menunggu Verifikasi', label: 'Menunggu Verifikasi (Transfer Bank/QRIS)' },
  { value: 'Belum Bayar', label: 'Belum Bayar (Pending)' },
];

const manualPaymentMethodOptions = [
  { value: 'Tunai di Meja Panitia', label: 'Tunai / Cash (Meja Panitia)' },
  { value: 'Transfer Bank BSI', label: 'Transfer Bank BSI (444.777.2026 a.n. SEMARAK)' },
  { value: 'Transfer Bank Mandiri', label: 'Transfer Bank Mandiri' },
  { value: 'QRIS Dinamis', label: 'QRIS Semarak Aliyah' },
  { value: 'Lainnya', label: 'Metode Pembayaran Lainnya' },
];

const genderOptions = [
  { value: 'Laki-laki', label: 'Laki-laki' },
  { value: 'Perempuan', label: 'Perempuan' },
];

const gradeOptions = [
  { value: '9', label: 'Kelas 9' },
  { value: '8', label: 'Kelas 8' },
  { value: '7', label: 'Kelas 7' },
  { value: 'Campuran / Gabungan', label: 'Campuran / Tim' },
];

const masterSchoolOptions = computed(() => {
  const opts = [{ value: '', label: '-- Pilih Sekolah dari Database Master --' }];
  props.master_schools.forEach(s => {
    opts.push({
      value: String(s.id),
      label: `${s.name} (${s.jenjang || '-'}, Kec. ${s.kecamatan || '-'})`
    });
  });
  return opts;
});

function onSelectMasterSchool(schoolId) {
  manualForm.value.selected_master_id = schoolId;
  if (!schoolId) return;
  const found = props.master_schools.find(s => String(s.id) === String(schoolId));
  if (found) {
    manualForm.value.school_name = found.name;
    if (found.jenjang) manualForm.value.jenjang = found.jenjang;
    if (found.npsn) manualForm.value.npsn = found.npsn;
    if (found.alamat) manualForm.value.alamat = found.alamat;
    if (found.kecamatan) {
      const match = JEMBER_KECAMATAN_LIST.find(k => k.toLowerCase() === found.kecamatan.toLowerCase() && k !== 'Lainnya');
      if (match) {
        manualForm.value.kecamatan = match;
        manualForm.value.custom_kecamatan = '';
      } else {
        manualForm.value.kecamatan = 'Lainnya';
        manualForm.value.custom_kecamatan = found.kecamatan;
      }
    }
  }
}

function openManualModal(prefilledSchool = null) {
  let initKecamatan = '';
  let initCustomKecamatan = '';
  if (prefilledSchool?.kecamatan && prefilledSchool.kecamatan !== '-') {
    const match = JEMBER_KECAMATAN_LIST.find(k => k.toLowerCase() === prefilledSchool.kecamatan.toLowerCase() && k !== 'Lainnya');
    if (match) {
      initKecamatan = match;
    } else {
      initKecamatan = 'Lainnya';
      initCustomKecamatan = prefilledSchool.kecamatan;
    }
  }

  manualForm.value = {
    school_selection_mode: 'master',
    selected_master_id: '',
    school_name: prefilledSchool ? prefilledSchool.school_name : '',
    jenjang: prefilledSchool?.jenjang && prefilledSchool.jenjang !== '-' ? prefilledSchool.jenjang : 'SMP',
    kecamatan: initKecamatan,
    custom_kecamatan: initCustomKecamatan,
    npsn: prefilledSchool?.npsn && prefilledSchool.npsn !== '-' ? prefilledSchool.npsn : '',
    alamat: '',
    mentor_name: prefilledSchool?.mentor_name && prefilledSchool.mentor_name !== '-' ? prefilledSchool.mentor_name : '',
    mentor_wa: prefilledSchool?.mentor_wa && prefilledSchool.mentor_wa !== '-' ? prefilledSchool.mentor_wa : '',
    mentor_email: '',
    team_name: '',
    package_type: prefilledSchool?.has_all_in ? 'Paket All-In' : 'Paket Satuan',
    competition_id: props.competitions[0]?.id ? String(props.competitions[0].id) : '',
    competition_ids: prefilledSchool?.competitions ? prefilledSchool.competitions.map(c => c.competition_id) : (props.competitions.map(c => c.id)),
    custom_fee: null,
    package_price: props.package_allin_price,
    payment_status: 'Terverifikasi',
    payment_method: 'Tunai di Meja Panitia',
    notes: 'Pendaftaran langsung on-the-spot di Meja Panitia',
    target_action: 'manage_delegates',
  };

  // If prefilled school exists in master
  if (prefilledSchool) {
    const norm = prefilledSchool.school_name.toLowerCase().replace(/[^a-z0-9]/g, '');
    const found = props.master_schools.find(ms => ms.name.toLowerCase().replace(/[^a-z0-9]/g, '') === norm);
    if (found) {
      manualForm.value.selected_master_id = String(found.id);
      if (found.npsn) manualForm.value.npsn = found.npsn;
      if (found.alamat) manualForm.value.alamat = found.alamat;
      if (!manualForm.value.kecamatan && found.kecamatan) {
        const match = JEMBER_KECAMATAN_LIST.find(k => k.toLowerCase() === found.kecamatan.toLowerCase() && k !== 'Lainnya');
        if (match) {
          manualForm.value.kecamatan = match;
          manualForm.value.custom_kecamatan = '';
        } else {
          manualForm.value.kecamatan = 'Lainnya';
          manualForm.value.custom_kecamatan = found.kecamatan;
        }
      }
    } else {
      manualForm.value.school_selection_mode = 'custom';
    }
  }

  showManualModal.value = true;
}

function toggleAllInCompetition(compId) {
  const idx = manualForm.value.competition_ids.indexOf(compId);
  if (idx > -1) {
    manualForm.value.competition_ids.splice(idx, 1);
  } else {
    manualForm.value.competition_ids.push(compId);
  }
}

function selectAllCompetitions() {
  manualForm.value.competition_ids = props.competitions.map(c => c.id);
}

function deselectAllCompetitions() {
  manualForm.value.competition_ids = [];
}

// -----------------------------------------------------------------------------
// KONFIGURASI FORM BUILDER UNTUK MODAL INPUT MANUAL
// -----------------------------------------------------------------------------
const activeManualCompetition = computed(() => {
  if (manualForm.value.package_type === 'Paket Satuan' && manualForm.value.competition_id) {
    return props.competitions.find(c => String(c.id) === String(manualForm.value.competition_id)) || null;
  }
  return null;
});

const competitionSelectOptions = computed(() => {
  return props.competitions.map(c => {
    const fee = Number(c.price || c.fee || 0);
    const feeText = fee > 0 ? formatRupiah(fee) : 'Gratis';
    const cat = c.category?.name || 'Umum';
    return {
      value: String(c.id),
      label: `${c.title} • ${cat} (${feeText})`
    };
  });
});

const activeManualFormBuilder = computed(() => {
  const comp = activeManualCompetition.value;
  if (!comp) return null;

  const customFields = Array.isArray(comp.custom_fields) ? comp.custom_fields : [];
  const repeater = customFields.find(f => f.type === 'participant_repeater');
  const rawSubs = repeater?.sub_fields || {};

  const hasTeamName = !!rawSubs.team_name?.active || customFields.some(f => ['nama_tim', 'team_name', 'nama_squad', 'nama_regu'].includes(f.name));
  const teamLabel = rawSubs.team_name?.label || 'Nama Tim / Skuad';
  const teamPlaceholder = rawSubs.team_name?.placeholder || 'Contoh: Garuda Squad / Tim Voli Utama';

  const npsnField = customFields.find(f => ['school_npsn', 'npsn', 'nomor_pokok_sekolah_nasional'].includes(f.name));
  const addressField = customFields.find(f => ['school_address', 'alamat_sekolah', 'alamat_lengkap_sekolah', 'alamat'].includes(f.name));
  const emailField = customFields.find(f => ['mentor_email', 'email', 'alamat_email'].includes(f.name));

  const standardNames = [
    'school_name', 'asal_sekolah', 'nama_sekolah', 'nama_lembaga', 'nama_sekolah_madrasah',
    'school_district', 'kecamatan', 'kecamatan_sekolah', 'kecamatan_asal_sekolah',
    'school_npsn', 'npsn', 'npsn_sekolah', 'nomor_pokok_sekolah_nasional',
    'school_address', 'alamat_sekolah', 'alamat_lengkap_sekolah', 'alamat',
    'mentor_name', 'nama_pendamping', 'nama_guru_pendamping', 'nama_pembina',
    'mentor_wa', 'nomor_wa', 'whatsapp', 'nomor_whatsapp', 'nomor_whatsapp_pendamping',
    'mentor_email', 'contact_email', 'email', 'alamat_email',
    'nama_tim', 'team_name', 'nama_squad', 'nama_regu',
    'allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin'
  ];

  const otherFields = customFields.filter(f => f.type !== 'participant_repeater' && !standardNames.includes(f.name));

  const minItems = repeater?.min_items ? Number(repeater.min_items) : Math.max(1, Number(comp.min_participants || 1));
  const maxItems = repeater?.max_items ? Number(repeater.max_items) : Math.max(minItems, Number(comp.max_participants || 1));

  return {
    comp,
    repeater,
    rawSubs,
    hasTeamName,
    teamLabel,
    teamPlaceholder,
    npsnField,
    addressField,
    emailField,
    otherFields,
    minItems,
    maxItems,
    isTeam: maxItems > 1 || hasTeamName || comp.participant_type === 'team' || comp.participant_type === 'regu',
  };
});

const manualTotalCalculatedFee = computed(() => {
  if (manualForm.value.package_type === 'Paket All-In') {
    return manualForm.value.package_price !== null && manualForm.value.package_price !== undefined && manualForm.value.package_price !== ''
      ? Number(manualForm.value.package_price)
      : Number(props.package_allin_price || 200000);
  }
  if (manualForm.value.custom_fee !== null && manualForm.value.custom_fee !== undefined && manualForm.value.custom_fee !== '') {
    return Number(manualForm.value.custom_fee);
  }
  const comp = activeManualCompetition.value;
  return Number(comp?.price || comp?.fee || 0);
});

async function submitManualRegistration(targetAction = 'manage_delegates') {
  if (!manualForm.value.school_name.trim()) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Nama sekolah / madrasah wajib diisi!' });
    return;
  }
  if (!manualForm.value.kecamatan) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Kecamatan asal sekolah wajib dipilih!' });
    return;
  }
  if (manualForm.value.kecamatan === 'Lainnya' && !manualForm.value.custom_kecamatan?.trim()) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Silakan ketik nama kecamatan pada kolom isian manual yang muncul!' });
    return;
  }
  if (!manualForm.value.mentor_name.trim() || !manualForm.value.mentor_wa.trim()) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Nama pembina/pendamping dan nomor WhatsApp wajib diisi!' });
    return;
  }

  if (manualForm.value.package_type === 'Paket Satuan' && !manualForm.value.competition_id) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Pilih cabang lomba untuk pendaftaran Paket Satuan!' });
    return;
  }

  if (manualForm.value.package_type === 'Paket All-In' && manualForm.value.competition_ids.length === 0) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Pilih minimal satu cabang lomba untuk Paket Komplit (All-In)!' });
    return;
  }

  const finalKecamatan = manualForm.value.kecamatan === 'Lainnya'
    ? manualForm.value.custom_kecamatan.trim()
    : manualForm.value.kecamatan;

  const confirmed = await confirmDialog({
    title: 'Konfirmasi Pendaftaran Meja Panitia',
    message: `Daftarkan kontingen ${manualForm.value.school_name} (${manualForm.value.package_type}) dengan total tagihan [${formatRupiah(manualTotalCalculatedFee.value)}] dan status [${manualForm.value.payment_status}]?`,
    type: 'primary',
    confirmText: 'Ya, Simpan Pendaftaran',
    cancelText: 'Cek Kembali',
  });

  if (!confirmed) return;

  isSubmittingManual.value = true;
  manualForm.value.target_action = targetAction;

  router.post('/admin/pendaftar/manual-store', {
    school_name: manualForm.value.school_name,
    jenjang: manualForm.value.jenjang,
    kecamatan: finalKecamatan,
    npsn: manualForm.value.npsn,
    alamat: manualForm.value.alamat,
    mentor_name: manualForm.value.mentor_name,
    mentor_wa: manualForm.value.mentor_wa,
    mentor_email: manualForm.value.mentor_email,
    team_name: manualForm.value.team_name,
    package_type: manualForm.value.package_type,
    competition_id: manualForm.value.package_type === 'Paket Satuan' ? manualForm.value.competition_id : null,
    competition_ids: manualForm.value.package_type === 'Paket All-In' ? manualForm.value.competition_ids : [],
    custom_fee: manualForm.value.custom_fee,
    package_price: manualForm.value.package_price,
    payment_status: manualForm.value.payment_status,
    payment_method: manualForm.value.payment_method,
    notes: manualForm.value.notes,
    target_action: targetAction,
  }, {
    onSuccess: (pageRes) => {
      isSubmittingManual.value = false;
      showManualModal.value = false;

      const newlyAll = pageRes.props?.flash?.newly_registered_all || page.props.flash?.newly_registered_all || [];
      const newlyFirst = pageRes.props?.flash?.newly_registered || page.props.flash?.newly_registered;
      const returnedAction = pageRes.props?.flash?.target_action || targetAction;

      toast.showToast({
        type: 'success',
        title: 'Pendaftaran Sekolah Berhasil',
        message: 'Informasi sekolah, pembina & cabang lomba berhasil disimpan di meja panitia!'
      });

      if (returnedAction === 'manage_delegates') {
        if (newlyAll.length > 0) {
          openParticipantModal(newlyAll[0], { competitions: newlyAll });
        } else if (newlyFirst) {
          openParticipantModal(newlyFirst);
        }
      } else {
        // Mode Simpan Kontingen Saja -> tampilkan dialog ringkasan
        openSummaryModal({
          school_name: manualForm.value.school_name,
          mentor_name: manualForm.value.mentor_name,
          mentor_wa: manualForm.value.mentor_wa,
          package_type: manualForm.value.package_type,
          total_fee: manualTotalCalculatedFee.value,
          payment_status: manualForm.value.payment_status,
          payment_method: manualForm.value.payment_method,
          competitions: newlyAll.length > 0 ? newlyAll : (newlyFirst ? [newlyFirst] : []),
        });
      }
    },
    onError: (errs) => {
      isSubmittingManual.value = false;
      const msg = Object.values(errs)[0] || 'Terjadi kesalahan saat menyimpan pendaftaran.';
      toast.showToast({ type: 'danger', title: 'Gagal', message: msg });
    }
  });
}

// -----------------------------------------------------------------------------
// MODAL: INPUT / KELOLA PESERTA DELEGASI (ATURAN FORM BUILDER)
// -----------------------------------------------------------------------------
const showParticipantModal = ref(false);
const isSubmittingParticipants = ref(false);
const activeRegForParticipants = ref(null);
const activeCompetitionConfig = ref(null);
const participantForm = ref({
  delegates: [],
  additional_fields: {},
});

// Helper: Extract form builder subfields and rules for active competition
const activeBuilderRules = computed(() => {
  if (!activeCompetitionConfig.value) {
    return {
      min_items: 1,
      max_items: 1,
      sub_fields: {
        name: { active: true, required: true, label: 'Nama Lengkap Siswa' },
        gender: { active: true, required: true, label: 'Jenis Kelamin' },
        grade: { active: true, required: true, label: 'Kelas' },
        nisn: { active: true, required: false, label: 'NISN' },
        birth_date: { active: false, required: false, label: 'Tanggal Lahir' },
        custom_id: { active: false, required: false, label: 'ID Siswa / No Dada' },
        wa_number: { active: false, required: false, label: 'WhatsApp Siswa' },
        captain_option: { active: false, required: false, label: 'Kapten Tim' },
        custom_columns: [],
      },
      additional_fields: [],
      participant_type: 'Delegasi Siswa',
    };
  }

  const comp = activeCompetitionConfig.value;
  const customFields = Array.isArray(comp.custom_fields) ? comp.custom_fields : [];
  
  // Cari field bertipe participant_repeater dari Form Builder
  const repeater = customFields.find(f => f.type === 'participant_repeater');
  
  const minItems = repeater?.min_items 
    ? Number(repeater.min_items) 
    : Math.max(1, Number(comp.min_participants || 1));

  const maxItems = repeater?.max_items 
    ? Number(repeater.max_items) 
    : Math.max(minItems, Number(comp.max_participants || comp.max_delegates || 1));

  // Normalisasi sub-fields sesuai Form Builder
  const rawSubs = repeater?.sub_fields || {};
  const subFields = {
    name: { active: true, required: true, label: rawSubs.name?.label || 'Nama Lengkap Siswa', placeholder: rawSubs.name?.placeholder || 'Nama lengkap siswa...' },
    gender: { active: rawSubs.gender?.active !== false, required: rawSubs.gender?.required !== false, label: rawSubs.gender?.label || 'Jenis Kelamin' },
    grade: { active: rawSubs.grade?.active !== false, required: rawSubs.grade?.required !== false, label: rawSubs.grade?.label || 'Kelas' },
    nisn: { active: rawSubs.nisn?.active !== false, required: !!rawSubs.nisn?.required, label: rawSubs.nisn?.label || 'NISN (Opsional)', placeholder: rawSubs.nisn?.placeholder || '0012345678' },
    birth_date: { active: !!rawSubs.birth_date?.active, required: !!rawSubs.birth_date?.required, label: rawSubs.birth_date?.label || 'Tanggal Lahir (dd/mm/yyyy)' },
    custom_id: { active: !!rawSubs.custom_id?.active, required: !!rawSubs.custom_id?.required, label: rawSubs.custom_id?.label || 'No. Dada / ID Game', placeholder: rawSubs.custom_id?.placeholder || 'ID Peserta...' },
    wa_number: { active: !!rawSubs.wa_number?.active, required: !!rawSubs.wa_number?.required, label: rawSubs.wa_number?.label || 'No. WhatsApp Siswa', placeholder: rawSubs.wa_number?.placeholder || '08xxxxxxxxxx' },
    captain_option: { active: !!rawSubs.captain_option?.active, required: false, label: rawSubs.captain_option?.label || 'Kapten Tim' },
    photo_formal: { active: !!rawSubs.photo_formal?.active, required: !!rawSubs.photo_formal?.required, label: rawSubs.photo_formal?.label || 'Pas Foto Formal Siswa' },
    photo_freestyle: { active: !!rawSubs.photo_freestyle?.active, required: !!rawSubs.photo_freestyle?.required, label: rawSubs.photo_freestyle?.label || 'Foto Pose Gaya Bebas' },
    custom_columns: Array.isArray(rawSubs.custom_columns) ? rawSubs.custom_columns : [],
  };

  // Additional custom fields (bukan identity dan bukan repeater)
  const additionalFields = customFields.filter(f => {
    if (f.type === 'participant_repeater') return false;
    if (f.is_mandatory_identity) return false;
    const standardIdentityNames = ['school_name', 'school_district', 'school_npsn', 'school_address', 'mentor_name', 'mentor_wa', 'mentor_email', 'allin_code'];
    return !standardIdentityNames.includes(f.name);
  });

  return {
    min_items: minItems,
    max_items: maxItems,
    sub_fields: subFields,
    additional_fields: additionalFields,
    participant_type: comp.participant_type || 'Delegasi Siswa',
  };
});

const contingentRegistrations = ref([]);

function openParticipantModal(compReg, schoolContext = null) {
  activeRegForParticipants.value = compReg;

  // Normalisasi daftar cabang lomba kontingen untuk tab navigator multi-cabang
  if (schoolContext?.competitions && Array.isArray(schoolContext.competitions) && schoolContext.competitions.length > 0) {
    contingentRegistrations.value = schoolContext.competitions;
  } else if (compReg.contingent_competitions && Array.isArray(compReg.contingent_competitions) && compReg.contingent_competitions.length > 0) {
    contingentRegistrations.value = compReg.contingent_competitions;
  } else if (contingentRegistrations.value.length > 1 && contingentRegistrations.value.some(c => (c.registration_id || c.id) === (compReg.registration_id || compReg.id))) {
    // Pertahankan daftar cabang kontingen yang sedang dibuka
  } else {
    contingentRegistrations.value = [compReg];
  }
  
  // Temukan konfigurasi competition dari props.competitions
  const foundComp = props.competitions.find(c => String(c.id) === String(compReg.competition_id));
  activeCompetitionConfig.value = foundComp || {
    id: compReg.competition_id,
    title: compReg.competition_title,
    category: compReg.category_name,
    min_participants: 1,
    max_participants: 1,
    custom_fields: [],
  };

  // Inisialisasi data delegates
  const existingStudents = Array.isArray(compReg.students) && compReg.students.length > 0 
    ? JSON.parse(JSON.stringify(compReg.students))
    : (Array.isArray(compReg.custom_fields?.delegates) ? JSON.parse(JSON.stringify(compReg.custom_fields.delegates)) : []);

  const minReq = activeBuilderRules.value.min_items || 1;
  
  // Bersihkan dan normalkan delegates
  const normalizedDelegates = [];
  if (existingStudents.length > 0) {
    existingStudents.forEach((st) => {
      normalizedDelegates.push({
        name: st.name || st.nama_lengkap || '',
        gender: st.gender || 'Laki-laki',
        grade: st.grade || '7',
        nisn: st.nisn && st.nisn !== '-' ? st.nisn : '',
        birth_date: st.birth_date || '',
        custom_id: st.custom_id || '',
        wa_number: st.wa_number || '',
        is_captain: !!st.is_captain,
        captain_wa: st.captain_wa || '',
        photo_formal: st.photo_formal || st.photo || null,
        photo_freestyle: st.photo_freestyle || [],
        custom_columns: st.custom_columns || {},
      });
    });
  }

  // Pastikan minimal delegates terpenuhi
  while (normalizedDelegates.length < minReq) {
    normalizedDelegates.push({
      name: '',
      gender: 'Laki-laki',
      grade: '7',
      nisn: '',
      birth_date: '',
      custom_id: '',
      wa_number: '',
      is_captain: normalizedDelegates.length === 0 && !!activeBuilderRules.value.sub_fields.captain_option.active,
      captain_wa: '',
      photo_formal: null,
      photo_freestyle: [],
      custom_columns: {},
    });
  }

  // Inisialisasi additional fields
  const addFields = {};
  activeBuilderRules.value.additional_fields.forEach(f => {
    addFields[f.name] = compReg.custom_fields?.[f.name] || '';
  });

  participantForm.value = {
    delegates: normalizedDelegates,
    additional_fields: addFields,
  };

  showParticipantModal.value = true;
}

function switchContingentCompetition(targetReg) {
  if (!targetReg) return;
  openParticipantModal(targetReg, { competitions: contingentRegistrations.value });
}

function addDelegateItem() {
  const max = activeBuilderRules.value.max_items;
  if (participantForm.value.delegates.length >= max) {
    toast.showToast({ type: 'warning', title: 'Batas Maksimal', message: `Batas kuota delegasi untuk cabang ini maksimal ${max} orang siswa.` });
    return;
  }

  participantForm.value.delegates.push({
    name: '',
    gender: 'Laki-laki',
    grade: '7',
    nisn: '',
    birth_date: '',
    custom_id: '',
    wa_number: '',
    is_captain: false,
    captain_wa: '',
    custom_columns: {},
  });
}

function removeDelegateItem(index) {
  const min = activeBuilderRules.value.min_items;
  if (participantForm.value.delegates.length <= min) {
    toast.showToast({ type: 'warning', title: 'Batas Minimal', message: `Cabang lomba ini mewajibkan minimal ${min} orang siswa delegasi.` });
    return;
  }
  participantForm.value.delegates.splice(index, 1);
}

function setCaptain(index) {
  participantForm.value.delegates.forEach((st, idx) => {
    st.is_captain = (idx === index);
  });
}

async function submitParticipants() {
  if (!activeRegForParticipants.value) return;

  // Validasi nama siswa
  const emptyName = participantForm.value.delegates.some(d => !d.name || !d.name.trim());
  if (emptyName) {
    toast.showToast({ type: 'warning', title: 'Data Belum Lengkap', message: 'Seluruh nama lengkap siswa delegasi wajib diisi!' });
    return;
  }

  isSubmittingParticipants.value = true;
  const regId = activeRegForParticipants.value.registration_id;

  router.post(`/admin/pendaftar/${regId}/update-participants`, {
    delegates: participantForm.value.delegates,
    additional_fields: participantForm.value.additional_fields,
  }, {
    onSuccess: () => {
      isSubmittingParticipants.value = false;

      const savedDelegates = JSON.parse(JSON.stringify(participantForm.value.delegates));
      activeRegForParticipants.value.students = savedDelegates;

      // Update di array kontingen
      const foundInContingent = contingentRegistrations.value.find(c => (c.registration_id || c.id) === regId);
      if (foundInContingent) {
        foundInContingent.students = savedDelegates;
      }

      toast.showToast({
        type: 'success',
        title: 'Berhasil Disimpan',
        message: `Data delegasi untuk ${activeRegForParticipants.value.competition_title} berhasil diperbarui!`
      });

      // Jika pendaftaran satuan (1 cabang), tutup modal. Jika multi-cabang kontingen, biarkan terbuka agar panitia bisa pilih tab lain
      if (contingentRegistrations.value.length <= 1) {
        showParticipantModal.value = false;
      }
    },
    onError: (errs) => {
      isSubmittingParticipants.value = false;
      const msg = Object.values(errs)[0] || 'Terjadi kesalahan saat menyimpan delegasi.';
      toast.showToast({ type: 'danger', title: 'Gagal', message: msg });
    }
  });
}

// -----------------------------------------------------------------------------
// MODAL: RINGKASAN PENDAFTARAN KONTINGEN BARU (SUMMARY DIALOG)
// -----------------------------------------------------------------------------
const showSummaryModal = ref(false);
const summaryData = ref(null);

function openSummaryModal(data) {
  summaryData.value = data;
  showSummaryModal.value = true;
}

// -----------------------------------------------------------------------------
// MODAL: MERGE VARIASI SEKOLAH YANG SAMA
// -----------------------------------------------------------------------------
const showMergeModal = ref(false);
const isSubmittingMerge = ref(false);

const mergeForm = ref({
  target_mode: 'existing', // 'existing' | 'master' | 'custom'
  target_school_name: '',
  source_school_names: [],
});

const targetMasterOptions = computed(() => {
  const opts = [{ value: '', label: '-- Pilih Sekolah Target dari Master --' }];
  props.master_schools.forEach(s => {
    opts.push({
      value: s.name,
      label: `${s.name} (${s.jenjang || '-'}, Kec. ${s.kecamatan || '-'})`
    });
  });
  return opts;
});

const targetExistingOptions = computed(() => {
  const opts = [{ value: '', label: '-- Pilih Nama Sekolah yang Sudah Ada --' }];
  props.all_registered_school_names.forEach(name => {
    opts.push({ value: name, label: name });
  });
  return opts;
});

function openMergeModal(prefilledTarget = null) {
  mergeForm.value = {
    target_mode: 'existing',
    target_school_name: prefilledTarget ? prefilledTarget.school_name : (props.all_registered_school_names[0] || ''),
    source_school_names: [],
  };
  showMergeModal.value = true;
}

function toggleSourceSchool(schoolName) {
  const idx = mergeForm.value.source_school_names.indexOf(schoolName);
  if (idx > -1) {
    mergeForm.value.source_school_names.splice(idx, 1);
  } else {
    mergeForm.value.source_school_names.push(schoolName);
  }
}

function autoSelectSimilarSchools(targetName) {
  if (!targetName) return;
  const cleanTarget = targetName.toLowerCase().replace(/[^a-z0-9]/g, '');
  mergeForm.value.source_school_names = props.all_registered_school_names.filter(name => {
    if (name === targetName) return false;
    const cleanSource = name.toLowerCase().replace(/[^a-z0-9]/g, '');
    return cleanSource.includes(cleanTarget) || cleanTarget.includes(cleanSource);
  });
}

async function submitMergeSchools() {
  const target = mergeForm.value.target_school_name.trim();
  const sources = mergeForm.value.source_school_names.filter(s => s !== target);

  if (!target) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Tentukan nama sekolah target yang menjadi rujukan baku!' });
    return;
  }
  if (sources.length === 0) {
    toast.showToast({ type: 'warning', title: 'Perhatian', message: 'Pilih minimal satu variasi nama sekolah sumber yang akan digabung!' });
    return;
  }

  const confirmed = await confirmDialog({
    title: 'Konfirmasi Penggabungan (Merge) Sekolah',
    message: `PERINGATAN: Seluruh data pendaftaran dari ${sources.length} variasi nama sekolah akan dialihkan dan digabungkan menjadi satu kontingen rujukan: "${target}". Tindakan ini tidak dapat dibatalkan. Lanjutkan?`,
    type: 'warning',
    confirmText: 'Ya, Gabungkan Sekarang',
    cancelText: 'Batal',
  });

  if (!confirmed) return;

  isSubmittingMerge.value = true;
  router.post('/admin/pendaftar/merge-schools', {
    target_school_name: target,
    source_school_names: sources,
  }, {
    onSuccess: () => {
      isSubmittingMerge.value = false;
      showMergeModal.value = false;
      toast.showToast({
        type: 'success',
        title: 'Merge Berhasil',
        message: `Pendaftaran berhasil disatukan di bawah kontingen "${target}".`
      });
    },
    onError: (errs) => {
      isSubmittingMerge.value = false;
      const msg = Object.values(errs)[0] || 'Gagal menggabungkan sekolah.';
      toast.showToast({ type: 'danger', title: 'Gagal', message: msg });
    }
  });
}

// -----------------------------------------------------------------------------
// PRINT REKAPITULASI
// -----------------------------------------------------------------------------
function printReport() {
  window.print();
}
</script>

<template>
  <VelzonLayout>
    <Head title="Rekapitulasi Sekolah & Madrasah Terdaftar - Semarak Aliyah XIV" />

    <!-- Print Only Header -->
    <div class="d-none d-print-block mb-4 text-center">
      <h3 class="fw-bold mb-1">REKAPITULASI SEKOLAH / MADRASAH TERDAFTAR</h3>
      <h5 class="fw-semibold text-muted mb-2">SEMARAK ALIYAH XIV - MAS WAHID HASYIM BALUNG</h5>
      <p class="small text-muted mb-0">Dicetak pada: {{ new Date().toLocaleString('id-ID') }} | Total: {{ schools.length }} Sekolah</p>
      <hr />
    </div>

    <div class="container-fluid px-3 px-md-4 py-3">
      <!-- Top Breadcrumb & Action Banner -->
      <div class="row align-items-center mb-3 g-2 d-print-none">
        <div class="col-12 col-md-6">
          <div class="d-flex align-items-center gap-2">
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                <i class="ri-building-4-line"></i>
              </span>
            </div>
            <div>
              <h4 class="fw-bold text-dark mb-0">Sekolah & Madrasah Terdaftar</h4>
              <p class="text-muted fs-12 mb-0">
                Rekapitulasi kontingen sekolah, cabang lomba yang diikuti, dan pendaftaran meja panitia.
              </p>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 text-md-end d-flex flex-wrap align-items-center justify-content-md-end gap-2">
          <!-- Button Input Manual (Meja Panitia) -->
          <button 
            type="button" 
            class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
            @click="openManualModal()"
          >
            <i class="ri-user-add-line fs-14"></i>
            <span>+ Input Manual (Meja Panitia)</span>
          </button>

          <!-- Button Merge Sekolah -->
          <button 
            type="button" 
            class="btn btn-outline-warning text-dark d-inline-flex align-items-center gap-1.5 shadow-sm"
            @click="openMergeModal()"
            title="Satukan pendaftaran sekolah yang sama akibat variasi penulisan nama oleh peserta"
          >
            <i class="ri-git-merge-line text-warning fs-14"></i>
            <span>Merge Variasi Sekolah</span>
          </button>

          <!-- Button Database Sekolah Master -->
          <Link 
            href="/admin/sekolah" 
            class="btn btn-outline-success d-inline-flex align-items-center gap-1.5"
            title="Kelola 407+ Master Database Sekolah se-Kabupaten Jember"
          >
            <i class="ri-database-2-line fs-14"></i>
            <span class="d-none d-sm-inline">Master Database</span>
          </Link>

          <!-- Button Print -->
          <button 
            type="button" 
            class="btn btn-soft-secondary d-inline-flex align-items-center gap-1"
            @click="printReport"
            title="Cetak Laporan Rekapitulasi"
          >
            <i class="ri-printer-line"></i>
            <span class="d-none d-sm-inline">Cetak</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Cards -->
      <div class="row g-2 mb-3 d-print-none">
        <div class="col-6 col-md-3 col-xl-2">
          <div class="card shadow-sm border-0 h-100 mb-0">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fs-11 fw-semibold text-muted text-uppercase">Sekolah Terdaftar</span>
                <i class="ri-community-line text-primary fs-18"></i>
              </div>
              <h4 class="fw-bold text-dark mb-0">{{ stats.total_schools || schools.length }}</h4>
              <span class="fs-10 text-muted">Kontingen terdata</span>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <div class="card shadow-sm border-0 h-100 mb-0 border-start border-3 border-warning">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fs-11 fw-semibold text-warning text-uppercase">Paket Komplit (All-In)</span>
                <i class="ri-vip-crown-2-line text-warning fs-18"></i>
              </div>
              <h4 class="fw-bold text-dark mb-0">{{ stats.total_all_in || 0 }}</h4>
              <span class="fs-10 text-muted">Sekolah ikut All-In</span>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <div class="card shadow-sm border-0 h-100 mb-0 border-start border-3 border-info">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fs-11 fw-semibold text-info text-uppercase">Paket Satuan</span>
                <i class="ri-medal-line text-info fs-18"></i>
              </div>
              <h4 class="fw-bold text-dark mb-0">{{ stats.total_unit || 0 }}</h4>
              <span class="fs-10 text-muted">Per cabang lomba</span>
            </div>
          </div>
        </div>

        <div class="col-6 col-md-3 col-xl-3">
          <div class="card shadow-sm border-0 h-100 mb-0 border-start border-3 border-success">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fs-11 fw-semibold text-success text-uppercase">Total Delegasi Siswa</span>
                <i class="ri-team-line text-success fs-18"></i>
              </div>
              <h4 class="fw-bold text-dark mb-0">{{ stats.total_students || 0 }} Siswa</h4>
              <span class="fs-10 text-muted">Peserta yang terdaftar</span>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-12 col-xl-3">
          <div class="card shadow-sm border-0 h-100 mb-0 bg-primary-subtle text-primary border-primary">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="fs-11 fw-bold text-uppercase">Estimasi Kas Registrasi</span>
                <i class="ri-wallet-3-line fs-18"></i>
              </div>
              <h5 class="fw-bold mb-0">{{ formatRupiah(stats.total_revenue || 0) }}</h5>
              <div class="d-flex align-items-center justify-content-between mt-1 fs-11">
                <span>Terverifikasi/Lunas:</span>
                <span class="fw-bold text-success">{{ formatRupiah(stats.total_verified_revenue || 0) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="card shadow-sm border-0 mb-3 d-print-none">
        <div class="card-body p-3">
          <div class="row g-2 align-items-center">
            <!-- Search Input -->
            <div class="col-12 col-md-4">
              <div class="position-relative">
                <input 
                  type="text" 
                  v-model="searchQuery" 
                  class="form-control form-control-sm ps-5 fs-12"
                  placeholder="Cari nama sekolah, kecamatan, NPSN, pembina..." 
                  @keyup.enter="applyFilters"
                />
                <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-14"></i>
              </div>
            </div>

            <!-- Filter Cabang Lomba -->
            <div class="col-6 col-md-3 col-xl-2">
              <CustomSelect 
                v-model="competitionFilter" 
                :options="competitionFilterOptions" 
                size="sm"
                searchable
                search-placeholder="Filter lomba..."
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Filter Paket -->
            <div class="col-6 col-md-2">
              <CustomSelect 
                v-model="packageFilter" 
                :options="packageFilterOptions" 
                size="sm"
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Filter Status Pembayaran -->
            <div class="col-6 col-md-2">
              <CustomSelect 
                v-model="paymentStatusFilter" 
                :options="paymentFilterOptions" 
                size="sm"
                @update:modelValue="applyFilters"
              />
            </div>

            <!-- Action Filter & View Mode Toggle -->
            <div class="col-6 col-md-1 col-xl-2 d-flex align-items-center justify-content-end gap-1.5">
              <button 
                type="button" 
                class="btn btn-sm btn-primary px-2"
                @click="applyFilters"
                title="Terapkan Filter"
              >
                <i class="ri-filter-3-line"></i>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-outline-secondary px-2"
                @click="resetFilters"
                title="Reset Filter"
              >
                <i class="ri-refresh-line"></i>
              </button>

              <div class="btn-group btn-group-sm ms-1" role="group">
                <button 
                  type="button" 
                  class="btn"
                  :class="viewMode === 'card' ? 'btn-primary' : 'btn-light border'"
                  @click="viewMode = 'card'"
                  title="Tampilan Grid Card"
                >
                  <i class="ri-grid-fill"></i>
                </button>
                <button 
                  type="button" 
                  class="btn"
                  :class="viewMode === 'table' ? 'btn-primary' : 'btn-light border'"
                  @click="viewMode = 'table'"
                  title="Tampilan Tabel"
                >
                  <i class="ri-table-line"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="schools.length === 0" class="card shadow-sm border-0 py-5 text-center">
        <div class="card-body">
          <div class="avatar-lg mx-auto mb-3">
            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-36 shadow-sm border border-primary-subtle">
              <i class="ri-community-line"></i>
            </span>
          </div>
          <h5 class="fw-bold text-body mb-2">Belum Ada Sekolah yang Sesuai</h5>
          <p class="text-muted fs-13 mb-4 mx-auto" style="max-width: 480px;">
            Tidak ditemukan pendaftaran sekolah yang cocok dengan kriteria filter saat ini.
          </p>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary px-3 shadow-sm" @click="resetFilters">
              <i class="ri-refresh-line me-1"></i> Reset Filter
            </button>
            <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" @click="openManualModal()">
              <i class="ri-user-add-line me-1"></i> Input Manual Pendaftaran
            </button>
          </div>
        </div>
      </div>

      <!-- VIEW MODE 1: CARD GRID VIEW -->
      <div v-else-if="viewMode === 'card'" class="row g-3">
        <div 
          v-for="(school, sIdx) in schools" 
          :key="sIdx" 
          class="col-12 col-lg-6 col-xl-4"
        >
          <div class="card shadow-sm border-0 h-100 hover-shadow transition-all school-card">
            <!-- Card Header -->
            <div class="card-header bg-transparent border-bottom p-3 d-flex align-items-start justify-content-between gap-2">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-1.5 flex-wrap mb-1">
                  <span 
                    class="badge fs-10 px-2 py-0.5"
                    :class="school.jenjang === 'MTs' ? 'bg-success-subtle text-success' : (school.jenjang === 'SMP' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary')"
                  >
                    {{ school.jenjang || 'Sekolah' }}
                  </span>
                  <span v-if="school.kecamatan && school.kecamatan !== '-'" class="badge bg-light text-muted border fs-10">
                    <i class="ri-map-pin-line"></i> Kec. {{ school.kecamatan }}
                  </span>
                  <span v-if="school.has_all_in" class="badge bg-warning-subtle text-warning fs-10 fw-bold border border-warning-subtle">
                    <i class="ri-vip-crown-fill"></i> Paket Komplit (All-In)
                  </span>
                  <span v-else class="badge bg-info-subtle text-info fs-10">
                    Paket Satuan
                  </span>
                </div>
                <h5 class="fw-bold text-dark mb-1 text-truncate-2" :title="school.school_name">
                  {{ school.school_name }}
                </h5>
                <div class="d-flex align-items-center gap-2 fs-11 text-muted">
                  <span v-if="school.npsn && school.npsn !== '-'">NPSN: {{ school.npsn }}</span>
                  <span v-else class="fst-italic">NPSN: -</span>
                  <span>&bull;</span>
                  <span>{{ school.total_competitions }} Cabang Lomba</span>
                </div>
              </div>

              <!-- Payment Summary Badge -->
              <div class="flex-shrink-0 text-end">
                <span 
                  class="badge fs-11 px-2 py-1 d-inline-block"
                  :class="{
                    'bg-success-subtle text-success border border-success-subtle': school.payment_summary === 'Lunas',
                    'bg-warning-subtle text-warning border border-warning-subtle': school.payment_summary === 'Menunggu Verifikasi',
                    'bg-info-subtle text-info border border-info-subtle': school.payment_summary === 'Sebagian',
                    'bg-danger-subtle text-danger border border-danger-subtle': school.payment_summary === 'Belum Lunas'
                  }"
                >
                  <i 
                    class="me-1 fs-11"
                    :class="{
                      'ri-check-double-line': school.payment_summary === 'Lunas',
                      'ri-time-line': school.payment_summary === 'Menunggu Verifikasi',
                      'ri-pie-chart-line': school.payment_summary === 'Sebagian',
                      'ri-close-circle-line': school.payment_summary === 'Belum Lunas'
                    }"
                  ></i>
                  {{ school.payment_summary }}
                </span>
                <div class="fw-bold text-dark fs-12 mt-1">
                  {{ formatRupiah(school.total_fee) }}
                </div>
              </div>
            </div>

            <!-- Card Body: Cabang Lomba Chips -->
            <div class="card-body p-3">
              <!-- Pembina Contact Row -->
              <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border mb-2.5">
                <div class="d-flex align-items-center gap-2">
                  <i class="ri-user-star-line text-primary fs-16"></i>
                  <div>
                    <div class="fw-semibold text-dark fs-11">{{ school.mentor_name || 'Pembina / Pendamping' }}</div>
                    <div class="text-muted fs-10 font-monospace">{{ school.mentor_wa || '-' }}</div>
                  </div>
                </div>
                <div v-if="school.mentor_wa && school.mentor_wa !== '-'">
                  <a 
                    :href="getWaChatUrl(school.mentor_wa, `Halo Bapak/Ibu Pembina ${school.school_name}, konfirmasi pendaftaran Semarak Aliyah XIV...`)" 
                    target="_blank" 
                    class="btn btn-sm btn-success py-1 px-2 fs-11 rounded-pill d-inline-flex align-items-center gap-1 shadow-sm"
                    title="Hubungi Pembina via WhatsApp"
                  >
                    <i class="ri-whatsapp-line fs-13"></i>
                    <span>Chat WA</span>
                  </a>
                </div>
              </div>

              <!-- Cabang Lomba Diikuti Section -->
              <div class="mb-2">
                <div class="d-flex align-items-center justify-content-between mb-1.5">
                  <span class="fs-11 fw-bold text-dark text-uppercase d-flex align-items-center gap-1">
                    <i class="ri-trophy-line text-warning"></i> Cabang Lomba yang Diikuti:
                  </span>
                  <span class="badge bg-secondary-subtle text-secondary fs-10">
                    {{ school.total_students }} Peserta
                  </span>
                </div>

                <!-- Chips container -->
                <div class="d-flex flex-wrap gap-1.5" style="max-height: 140px; overflow-y: auto;">
                  <div 
                    v-for="(comp, cIdx) in school.competitions" 
                    :key="cIdx"
                    class="badge rounded-pill border p-1.5 px-2 text-start d-flex align-items-center gap-1.5 cursor-pointer hover-shadow-sm transition-all"
                    :class="{
                      'bg-success-subtle text-success border-success-subtle': comp.payment_status === 'Terverifikasi',
                      'bg-warning-subtle text-warning border-warning-subtle': comp.payment_status === 'Menunggu Verifikasi',
                      'bg-light text-dark border-secondary-subtle': comp.payment_status !== 'Terverifikasi' && comp.payment_status !== 'Menunggu Verifikasi'
                    }"
                    @click="openParticipantModal(comp, school)"
                    :title="`Klik untuk Input / Kelola Peserta: ${comp.competition_title}`"
                  >
                    <i class="ri-medal-fill text-warning fs-12"></i>
                    <span class="fw-semibold fs-11">{{ comp.competition_title }}</span>
                    <span class="comp-participant-count" :title="`${comp.participants_count} Siswa`">
                      {{ comp.participants_count }}
                    </span>
                    <i class="ri-edit-line fs-10 ms-0.5 opacity-75"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Footer: Quick Actions -->
            <div class="card-footer bg-light border-top p-2.5 d-flex align-items-center justify-content-between gap-1">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 fs-11"
                @click="openSchoolDetail(school)"
              >
                <i class="ri-eye-line"></i>
                <span>Lihat Detail & Delegasi</span>
              </button>

              <div class="d-flex gap-1">
                <button 
                  type="button" 
                  class="btn btn-sm btn-soft-primary fs-11 px-2"
                  @click="openManualModal(school)"
                  title="Tambah cabang lomba untuk sekolah ini"
                >
                  <i class="ri-add-line"></i> Lomba
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm btn-soft-warning text-dark fs-11 px-2"
                  @click="openMergeModal(school)"
                  title="Gabungkan variasi nama sekolah ini"
                >
                  <i class="ri-git-merge-line text-warning"></i>
                </button>
                <button 
                  v-if="canDeleteSchool"
                  type="button" 
                  class="btn btn-sm btn-soft-danger text-danger fs-11 px-2"
                  @click="deleteRegisteredSchool(school)"
                  title="Hapus seluruh data sekolah terdaftar ini"
                >
                  <i class="ri-delete-bin-line"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW MODE 2: TABLE VIEW -->
      <div v-else class="card shadow-sm border-0">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 fs-12">
              <thead class="table-light text-uppercase fs-11">
                <tr>
                  <th style="width: 40px;" class="text-center">No</th>
                  <th>Sekolah / Madrasah</th>
                  <th>Paket</th>
                  <th>Cabang Lomba yang Diikuti</th>
                  <th class="text-center">Delegasi Siswa</th>
                  <th>Pembina / Pendamping</th>
                  <th>Status Pembayaran</th>
                  <th class="text-end" style="width: 140px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(school, index) in schools" :key="index">
                  <td class="text-center fw-bold text-muted">{{ index + 1 }}</td>
                  <td>
                    <div class="fw-bold text-dark fs-13">{{ school.school_name }}</div>
                    <div class="d-flex align-items-center gap-1.5 text-muted fs-11 mt-0.5">
                      <span class="badge bg-light text-muted border fs-10">{{ school.jenjang || 'SMP/MTs' }}</span>
                      <span v-if="school.kecamatan && school.kecamatan !== '-'">Kec. {{ school.kecamatan }}</span>
                      <span v-if="school.npsn && school.npsn !== '-'">NPSN: {{ school.npsn }}</span>
                    </div>
                  </td>
                  <td>
                    <span 
                      class="badge fs-10"
                      :class="school.has_all_in ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-info-subtle text-info'"
                    >
                      {{ school.package_type }}
                    </span>
                  </td>
                  <td>
                    <div class="d-flex flex-wrap gap-1" style="max-width: 320px;">
                      <span 
                        v-for="(comp, cI) in school.competitions" 
                        :key="cI"
                        class="badge rounded-pill border fs-10 px-2 py-0.5 cursor-pointer hover-shadow-sm transition-all"
                        :class="comp.payment_status === 'Terverifikasi' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-light text-dark'"
                        @click="openParticipantModal(comp, school)"
                        :title="`Klik untuk Input / Kelola Peserta: ${comp.competition_title}`"
                      >
                        {{ comp.competition_title }} ({{ comp.participants_count }}) <i class="ri-edit-line ms-0.5 opacity-75"></i>
                      </span>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-secondary-subtle text-secondary fs-11 fw-bold">
                      <i class="ri-user-line me-0.5"></i> {{ school.total_students }} Siswa
                    </span>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark">{{ school.mentor_name || '-' }}</div>
                    <div class="text-muted fs-11 font-monospace">
                      {{ school.mentor_wa || '-' }}
                    </div>
                  </td>
                  <td>
                    <div>
                      <span 
                        class="badge fs-10"
                        :class="{
                          'bg-success-subtle text-success': school.payment_summary === 'Lunas',
                          'bg-warning-subtle text-warning': school.payment_summary === 'Menunggu Verifikasi',
                          'bg-info-subtle text-info': school.payment_summary === 'Sebagian',
                          'bg-danger-subtle text-danger': school.payment_summary === 'Belum Lunas'
                        }"
                      >
                        {{ school.payment_summary }}
                      </span>
                    </div>
                    <div class="fw-bold text-dark fs-11 mt-0.5">
                      {{ formatRupiah(school.total_fee) }}
                    </div>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-primary px-2 py-1"
                        @click="openSchoolDetail(school)"
                        title="Lihat Detail Cabang & Peserta"
                      >
                        <i class="ri-eye-line"></i>
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-soft-primary px-2 py-1"
                        @click="openManualModal(school)"
                        title="Tambah Cabang Lomba Baru"
                      >
                        <i class="ri-add-line"></i>
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-soft-warning px-2 py-1"
                        @click="openMergeModal(school)"
                        title="Merge Sekolah"
                      >
                        <i class="ri-git-merge-line text-warning"></i>
                      </button>
                      <button 
                        v-if="canDeleteSchool"
                        type="button" 
                        class="btn btn-sm btn-soft-danger text-danger px-2 py-1"
                        @click="deleteRegisteredSchool(school)"
                        title="Hapus seluruh data sekolah terdaftar ini"
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

    <!-- ===================================================================== -->
    <!-- MODAL 1: DETAIL LENGKAP SEKOLAH TERDAFTAR                             -->
    <!-- ===================================================================== -->
    <div v-if="showDetailModal && activeSchool" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); z-index: 1055;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
          <!-- Modal Header -->
          <div class="modal-header py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h6 class="modal-title fw-bold mb-0 fs-15">{{ activeSchool.school_name }}</h6>
              <span class="badge bg-primary-subtle text-primary fw-bold fs-10">{{ activeSchool.jenjang || 'Sekolah' }}</span>
              <span v-if="activeSchool.has_all_in" class="badge bg-warning-subtle text-warning fw-bold fs-10">
                <i class="ri-vip-crown-fill"></i> Paket Komplit (All-In)
              </span>
              <span class="badge bg-secondary-subtle text-secondary fs-10">
                {{ activeSchool.total_competitions }} Lomba
              </span>
            </div>
            <button type="button" class="btn-close" @click="showDetailModal = false"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-3 bg-light">
            <!-- Profil Singkat Kontingen -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-body p-3">
                <div class="row g-3">
                  <div class="col-12 col-md-4">
                    <span class="fs-11 text-muted text-uppercase fw-semibold">Wilayah & Identitas</span>
                    <div class="fw-bold text-dark fs-13 mt-0.5">Kecamatan: {{ activeSchool.kecamatan || '-' }}</div>
                    <div class="text-muted fs-12">NPSN: {{ activeSchool.npsn || '-' }}</div>
                  </div>
                  <div class="col-12 col-md-4">
                    <span class="fs-11 text-muted text-uppercase fw-semibold">Pembina / Pendamping</span>
                    <div class="fw-bold text-dark fs-13 mt-0.5">{{ activeSchool.mentor_name || '-' }}</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <span class="text-muted fs-12 font-monospace">{{ activeSchool.mentor_wa || '-' }}</span>
                      <a 
                        v-if="activeSchool.mentor_wa && activeSchool.mentor_wa !== '-'"
                        :href="getWaChatUrl(activeSchool.mentor_wa)" 
                        target="_blank" 
                        class="btn btn-sm btn-success py-0.5 px-2 fs-10 rounded-pill"
                      >
                        <i class="ri-whatsapp-line"></i> Hubungi WA
                      </a>
                    </div>
                  </div>
                  <div class="col-12 col-md-4">
                    <span class="fs-11 text-muted text-uppercase fw-semibold">Status Biaya Pendaftaran</span>
                    <div class="d-flex align-items-center gap-2 mt-0.5">
                      <span 
                        class="badge fs-11"
                        :class="activeSchool.payment_summary === 'Lunas' ? 'bg-success text-white' : 'bg-warning text-dark'"
                      >
                        {{ activeSchool.payment_summary }}
                      </span>
                      <span class="fw-bold text-dark fs-14">{{ formatRupiah(activeSchool.total_fee) }}</span>
                    </div>
                    <div class="text-muted fs-11 mt-1">
                      Terbayar: <strong class="text-success">{{ formatRupiah(activeSchool.paid_fee) }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- List Cabang Lomba & Delegasi Siswa -->
            <h5 class="fw-bold text-dark mb-2.5 d-flex align-items-center justify-content-between">
              <span><i class="ri-medal-fill text-warning me-1"></i> Rincian Cabang Lomba & Delegasi Siswa</span>
              <span class="badge bg-primary fs-11">{{ activeSchool.competitions.length }} Lomba Terdaftar</span>
            </h5>

            <div class="row g-3">
              <div 
                v-for="(comp, idx) in activeSchool.competitions" 
                :key="idx" 
                class="col-12 col-lg-6"
              >
                <div class="card shadow-sm border-0 h-100">
                  <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                      <div class="d-flex align-items-center gap-1.5 mb-0.5">
                        <span class="badge bg-primary-subtle text-primary fs-10 font-monospace">
                          {{ comp.reg_code }}
                        </span>
                        <span class="badge bg-secondary-subtle text-secondary fs-10">
                          {{ comp.category_name }}
                        </span>
                      </div>
                      <h6 class="fw-bold text-dark mb-0">{{ comp.competition_title }}</h6>
                    </div>
                    <div class="text-end">
                      <span 
                        class="badge fs-10"
                        :class="comp.payment_status === 'Terverifikasi' ? 'bg-success text-white' : 'bg-warning text-dark'"
                      >
                        {{ comp.payment_status }}
                      </span>
                      <div class="fw-bold text-dark fs-11 mt-0.5">{{ formatRupiah(comp.total_fee) }}</div>
                    </div>
                  </div>

                  <div class="card-body p-3">
                    <span class="fs-11 fw-semibold text-muted text-uppercase d-block mb-1.5">
                      Delegasi Siswa Peserta ({{ comp.students ? comp.students.length : comp.participants_count }} Peserta):
                    </span>

                    <!-- Tabel Delegasi Siswa -->
                    <div v-if="comp.students && comp.students.length > 0" class="table-responsive">
                      <table class="table table-sm table-bordered mb-0 fs-11">
                        <thead class="table-light">
                          <tr>
                            <th style="width: 25px;" class="text-center">#</th>
                            <th>Nama Siswa</th>
                            <th>NISN</th>
                            <th>L/P</th>
                            <th>Kelas</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(st, stI) in comp.students" :key="stI">
                            <td class="text-center text-muted">{{ stI + 1 }}</td>
                            <td class="fw-semibold text-dark">{{ st.name || st.nama_lengkap || '-' }}</td>
                            <td class="font-monospace text-muted">{{ st.nisn || '-' }}</td>
                            <td>{{ st.gender || '-' }}</td>
                            <td>{{ st.grade || '-' }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!-- Fallback teks biasa jika belum berbentuk structured students -->
                    <div v-else-if="comp.participants_names" class="p-2 rounded bg-light border fs-11 text-dark whitespace-pre-wrap">
                      {{ comp.participants_names }}
                    </div>
                    <div v-else class="text-muted fs-11 fst-italic">
                      Tidak ada rincian nama siswa tercatat.
                    </div>
                  </div>

                  <div class="card-footer bg-light p-2.5 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span class="fs-11 text-muted">Didaftarkan: {{ comp.created_at || '-' }}</span>
                    <div class="d-flex align-items-center gap-1.5">
                      <button 
                        type="button" 
                        class="btn btn-sm btn-outline-success py-1 px-2.5 fs-11 d-inline-flex align-items-center gap-1 shadow-xs"
                        @click="openParticipantModal(comp, activeSchool)"
                        title="Input atau kelola data siswa delegasi sesuai aturan form builder cabang ini"
                      >
                        <i class="ri-user-star-line text-success"></i>
                        <span>Input / Edit Peserta</span>
                      </button>
                      <Link 
                        :href="`/admin/pendaftar/${comp.registration_id}`" 
                        class="btn btn-sm btn-primary py-1 px-2 fs-11 d-inline-flex align-items-center gap-1"
                      >
                        <i class="ri-survey-line"></i>
                        <span>Verifikasi &rarr;</span>
                      </Link>
                      <button 
                        v-if="canDeleteDelegate"
                        type="button" 
                        class="btn btn-sm btn-outline-danger py-1 px-2 fs-11 d-inline-flex align-items-center gap-1 shadow-xs"
                        @click="deleteSingleDelegation(comp, activeSchool)"
                        title="Hapus delegasi pendaftar ini"
                      >
                        <i class="ri-delete-bin-line"></i>
                        <span>Hapus</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-white border-top py-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <button 
                v-if="activeSchool.competitions && activeSchool.competitions.length > 1"
                type="button" 
                class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
                @click="openParticipantModal(activeSchool.competitions[0], activeSchool)"
                title="Kelola delegasi peserta untuk seluruh cabang lomba kontingen ini secara multi-tab"
              >
                <i class="ri-team-line"></i>
                <span>Kelola Delegasi Multi-Cabang</span>
              </button>
              <button 
                type="button" 
                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1"
                @click="openManualModal(activeSchool)"
              >
                <i class="ri-add-line"></i>
                <span>+ Tambah Cabang Lomba</span>
              </button>
              <button 
                v-if="canDeleteSchool"
                type="button" 
                class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1"
                @click="deleteRegisteredSchool(activeSchool)"
                title="Hapus seluruh data kontingen sekolah ini"
              >
                <i class="ri-delete-bin-line"></i>
                <span>Hapus Kontingen Sekolah</span>
              </button>
            </div>
            <button type="button" class="btn btn-sm btn-secondary" @click="showDetailModal = false">Tutup Modal</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 2: INPUT MANUAL PENDAFTARAN & DELEGASI (MEJA PANITIA)           -->
    <!-- ===================================================================== -->
    <div v-if="showManualModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); z-index: 1060;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 1260px; width: 96%;">
        <div class="modal-content shadow-lg border-0">
          <!-- Modal Header -->
          <div class="modal-header py-2.5 px-3 px-md-4 border-bottom d-flex align-items-center justify-content-between">
            <div>
              <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2 fs-14">
                <i class="ri-user-add-line text-primary fs-18"></i>
                <span>Input Manual Pendaftaran &amp; Delegasi (Meja Panitia)</span>
              </h6>
              <div class="fs-11 text-muted mt-0.5 d-flex align-items-center gap-2">
                <span>Alur: 1. Paket &amp; Lomba &rarr; 2. Identitas Lembaga &amp; Pembina &rarr; 3. Pembayaran &amp; Delegasi</span>
              </div>
            </div>
            <button type="button" class="btn-close" @click="showManualModal = false"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-3 p-md-4 bg-light">
            <!-- Top Step Navigation Bar Indicator -->
            <div class="card border-0 shadow-xs mb-3 bg-white">
              <div class="card-body p-2 px-3">
                <div class="row g-2 text-center fs-11">
                  <div class="col-4">
                    <div class="p-1.5 rounded-2 bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center gap-1.5">
                      <span class="badge rounded-circle bg-primary text-white p-1" style="width: 18px; height: 18px; line-height: 10px;">1</span>
                      <span class="d-none d-sm-inline">Pilih Paket &amp; Cabang</span>
                      <span class="d-inline d-sm-none">Paket/Lomba</span>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="p-1.5 rounded-2 bg-light text-dark fw-bold border d-flex align-items-center justify-content-center gap-1.5">
                      <span class="badge rounded-circle bg-secondary text-white p-1" style="width: 18px; height: 18px; line-height: 10px;">2</span>
                      <span class="d-none d-sm-inline">Identitas Sekolah &amp; Pembina</span>
                      <span class="d-inline d-sm-none">Identitas</span>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="p-1.5 rounded-2 bg-light text-dark fw-bold border d-flex align-items-center justify-content-center gap-1.5">
                      <span class="badge rounded-circle bg-secondary text-white p-1" style="width: 18px; height: 18px; line-height: 10px;">3</span>
                      <span class="d-none d-sm-inline">Pembayaran &amp; Delegasi</span>
                      <span class="d-inline d-sm-none">Delegasi</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row g-3">
              <!-- KOLOM KIRI: 1. SKEMA PAKET & CABANG LOMBA + 3. PEMBAYARAN -->
              <div class="col-12 col-lg-6">
                <!-- 1. PILIHAN PAKET & CABANG LOMBA (DITENTUKAN PERTAMA KALI) -->
                <div class="card shadow-sm border-0 mb-3">
                  <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                      <i class="ri-medal-fill text-warning"></i> 1. Pilihan Skema Paket &amp; Cabang Lomba
                    </span>
                    <span class="badge bg-primary text-white fs-10 px-2 py-0.5 rounded-pill">Langkah Pertama</span>
                  </div>

                  <div class="card-body p-3">
                    <!-- Tab Switch Paket Satuan vs Paket All-In -->
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <div 
                          class="p-2.5 rounded-3 border text-center cursor-pointer transition-all"
                          :class="manualForm.package_type === 'Paket Satuan' ? 'border-primary bg-primary-subtle text-primary fw-bold shadow-sm' : 'bg-light text-muted'"
                          @click="manualForm.package_type = 'Paket Satuan'"
                        >
                          <i class="ri-medal-line fs-20 d-block mb-1"></i>
                          <span>Paket Satuan</span>
                          <span class="d-block fs-10 text-muted">Per cabang lomba</span>
                        </div>
                      </div>
                      <div class="col-6">
                        <div 
                          class="p-2.5 rounded-3 border text-center cursor-pointer transition-all"
                          :class="manualForm.package_type === 'Paket All-In' ? 'border-warning bg-warning-subtle text-warning-emphasis fw-bold shadow-sm' : 'bg-light text-muted'"
                          @click="manualForm.package_type = 'Paket All-In'"
                        >
                          <i class="ri-vip-crown-fill fs-20 d-block mb-1 text-warning"></i>
                          <span>Paket Komplit (All-In)</span>
                          <span class="d-block fs-10 text-muted">Semua cabang lomba</span>
                        </div>
                      </div>
                    </div>

                    <!-- IF PAKET SATUAN -->
                    <div v-if="manualForm.package_type === 'Paket Satuan'" class="p-3 rounded-3 border bg-white mb-2 shadow-xs">
                      <label class="form-label fs-11 fw-bold text-dark d-flex align-items-center justify-content-between">
                        <span>Pilih Cabang Lomba <span class="text-danger">*</span></span>
                        <span class="badge bg-primary-subtle text-primary fs-10">Tarif Resmi</span>
                      </label>
                      <CustomSelect 
                        v-model="manualForm.competition_id" 
                        :options="competitionSelectOptions" 
                        size="sm"
                        searchable
                        search-placeholder="Cari cabang lomba..."
                      />

                      <!-- Ringkasan Info Form Builder Cabang Terpilih -->
                      <div v-if="activeManualCompetition" class="mt-2.5 p-2.5 rounded-2 bg-light border fs-11">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                          <span class="fw-bold text-dark">{{ activeManualCompetition.title }}</span>
                          <span class="badge bg-success-subtle text-success fs-10">
                            {{ formatRupiah(activeManualCompetition.price || activeManualCompetition.fee) }}
                          </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted fs-11">
                          <span><i class="ri-group-line me-0.5"></i> Kuota: {{ activeManualFormBuilder?.minItems }} s.d {{ activeManualFormBuilder?.maxItems }} Siswa</span>
                          <span>•</span>
                          <span>Tipe: {{ activeManualFormBuilder?.isTeam ? 'Beregu / Tim' : 'Individu' }}</span>
                        </div>
                      </div>

                      <div class="mt-2.5">
                        <label class="form-label fs-11 fw-bold text-dark">Biaya Pendaftaran Khusus (Opsional / Override)</label>
                        <input 
                          type="number" 
                          v-model="manualForm.custom_fee" 
                          class="form-control form-control-sm fs-12"
                          placeholder="Kosongkan untuk memakai harga default cabang" 
                        />
                        <span class="fs-10 text-muted">Biarkan kosong jika mengikuti tarif resmi cabang lomba.</span>
                      </div>
                    </div>

                    <!-- IF PAKET ALL-IN -->
                    <div v-else class="p-3 rounded-3 border border-warning-subtle bg-warning-subtle bg-opacity-10 mb-2">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-11 fw-bold text-dark">Pilih Cabang Lomba yang Diikuti Kontingen:</span>
                        <div class="d-flex gap-1">
                          <button type="button" class="btn btn-xs btn-outline-dark py-0.5 px-2 fs-10" @click="selectAllCompetitions">Pilih Semua</button>
                          <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2 fs-10" @click="deselectAllCompetitions">Reset</button>
                        </div>
                      </div>

                      <div class="row g-1.5 mb-2" style="max-height: 190px; overflow-y: auto;">
                        <div v-for="comp in competitions" :key="comp.id" class="col-12 col-md-6">
                          <div 
                            class="p-2 rounded-2 border bg-white cursor-pointer d-flex align-items-center gap-2 fs-11 transition-all"
                            :class="manualForm.competition_ids.includes(comp.id) ? 'border-success text-success fw-bold shadow-xs' : 'text-dark border-secondary-subtle'"
                            @click="toggleAllInCompetition(comp.id)"
                          >
                            <i 
                              class="fs-15 flex-shrink-0"
                              :class="manualForm.competition_ids.includes(comp.id) ? 'ri-checkbox-circle-fill text-success' : 'ri-checkbox-blank-circle-line text-muted'"
                            ></i>
                            <span class="text-truncate">{{ comp.title }}</span>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex align-items-center justify-content-between bg-white p-2 rounded border mb-2 fs-11">
                        <span class="text-muted">Total Cabang Dipilih:</span>
                        <span class="badge bg-success text-white fs-11">{{ manualForm.competition_ids.length }} Cabang Lomba</span>
                      </div>

                      <div>
                        <label class="form-label fs-11 fw-bold text-dark">Biaya Paket All-In (Total Tagihan Kontingen)</label>
                        <input 
                          type="number" 
                          v-model="manualForm.package_price" 
                          class="form-control form-control-sm fs-12 fw-bold text-success"
                        />
                        <span class="fs-10 text-muted">Hanya dicatat 1 tagihan paket komplit untuk seluruh cabang kontingen ini.</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 3. ADMINISTRASI PEMBAYARAN DI MEJA PANITIA -->
                <div class="card shadow-sm border-0">
                  <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                      <i class="ri-secure-payment-line text-primary"></i> 3. Administrasi Pembayaran di Meja Panitia
                    </span>
                    <span class="badge bg-success text-white fs-11">
                      Total: {{ formatRupiah(manualTotalCalculatedFee) }}
                    </span>
                  </div>
                  <div class="card-body p-3">
                    <div class="row g-2 mb-2">
                      <div class="col-6">
                        <label class="form-label fs-11 fw-bold text-dark">Status Pembayaran</label>
                        <CustomSelect 
                          v-model="manualForm.payment_status" 
                          :options="manualPaymentStatusOptions" 
                          size="sm"
                        />
                      </div>
                      <div class="col-6">
                        <label class="form-label fs-11 fw-bold text-dark">Metode Bayar</label>
                        <CustomSelect 
                          v-model="manualForm.payment_method" 
                          :options="manualPaymentMethodOptions" 
                          size="sm"
                        />
                      </div>
                    </div>
                    <div>
                      <label class="form-label fs-11 fw-bold text-dark">Catatan Petugas Meja Panitia</label>
                      <input 
                        type="text" 
                        v-model="manualForm.notes" 
                        class="form-control form-control-sm fs-12"
                        placeholder="Nomor kwitansi, berkas fisik, atau info tambahan..." 
                      />
                    </div>
                  </div>
                </div>
              </div>

              <!-- KOLOM KANAN: 2. IDENTITAS SEKOLAH & PEMBINA (ADAPTIF FORM BUILDER) -->
              <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                  <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                      <i class="ri-building-4-line text-primary"></i> 2. Identitas Sekolah &amp; Pembina
                    </span>
                    <div class="btn-group btn-group-sm" role="group">
                      <button 
                        type="button" 
                        class="btn btn-xs py-0.5 px-2.5"
                        :class="manualForm.school_selection_mode === 'master' ? 'btn-primary' : 'btn-light border'"
                        @click="manualForm.school_selection_mode = 'master'"
                      >
                        Pilih Master
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs py-0.5 px-2.5"
                        :class="manualForm.school_selection_mode === 'custom' ? 'btn-primary' : 'btn-light border'"
                        @click="manualForm.school_selection_mode = 'custom'"
                      >
                        Ketik Baru
                      </button>
                    </div>
                  </div>

                  <div class="card-body p-3">
                    <!-- Dropdown Master Sekolah jika mode master -->
                    <div v-if="manualForm.school_selection_mode === 'master'" class="mb-3">
                      <label class="form-label fs-11 fw-bold text-dark">
                        Pilih Sekolah dari Database Master (Jember)
                      </label>
                      <CustomSelect 
                        v-model="manualForm.selected_master_id" 
                        :options="masterSchoolOptions" 
                        size="sm"
                        searchable
                        search-placeholder="Ketik nama sekolah/kecamatan..."
                        @update:modelValue="onSelectMasterSchool"
                      />
                      <span class="fs-10 text-muted mt-1 d-block">Memilih master sekolah akan otomatis mengisi nama, jenjang, kecamatan, NPSN, dan alamat.</span>
                    </div>

                    <!-- Input Nama Sekolah -->
                    <div class="mb-3">
                      <label class="form-label fs-11 fw-bold text-dark">
                        Nama Sekolah / Madrasah <span class="text-danger">*</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="manualForm.school_name" 
                        class="form-control form-control-sm fs-12"
                        placeholder="Contoh: MTs Negeri 1 Jember" 
                        @input="onSchoolNameInput"
                        required
                      />
                    </div>

                    <div class="row g-2 mb-3">
                      <div class="col-5">
                        <label class="form-label fs-11 fw-bold text-dark">Jenjang</label>
                        <CustomSelect 
                          v-model="manualForm.jenjang" 
                          :options="jenjangOptions" 
                          size="sm"
                        />
                      </div>
                      <div class="col-7">
                        <label class="form-label fs-11 fw-bold text-dark">
                          Kecamatan Asal <span class="text-danger">*</span>
                        </label>
                        <CustomSelect 
                          v-model="manualForm.kecamatan" 
                          :options="kecamatanOptions" 
                          size="sm"
                          searchable
                          search-placeholder="Ketik nama kecamatan..."
                          placeholder="Pilih Kecamatan..."
                        />
                      </div>
                      <!-- Input manual jika opsi Lainnya dipilih -->
                      <div v-if="manualForm.kecamatan === 'Lainnya'" class="col-12 mt-1">
                        <label class="form-label fs-10 text-muted fw-semibold">
                          Tuliskan Nama Kecamatan <span class="text-danger">*</span>
                        </label>
                        <input 
                          type="text" 
                          v-model="manualForm.custom_kecamatan" 
                          class="form-control form-control-sm fs-11 text-dark"
                          placeholder="Ketik nama kecamatan manual..." 
                          required
                        />
                      </div>
                    </div>

                    <!-- Field NPSN & Alamat Sekolah (Adaptif Form Builder / Master) -->
                    <div class="row g-2 mb-3">
                      <div class="col-5">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ activeManualFormBuilder?.npsnField?.label || 'NPSN Sekolah' }}
                        </label>
                        <input 
                          type="text" 
                          v-model="manualForm.npsn" 
                          class="form-control form-control-sm fs-12 font-monospace"
                          :placeholder="activeManualFormBuilder?.npsnField?.placeholder || 'Contoh: 20581234'" 
                        />
                      </div>
                      <div class="col-7">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ activeManualFormBuilder?.addressField?.label || 'Alamat Sekolah / Madrasah' }}
                        </label>
                        <input 
                          type="text" 
                          v-model="manualForm.alamat" 
                          class="form-control form-control-sm fs-12"
                          :placeholder="activeManualFormBuilder?.addressField?.placeholder || 'Alamat lengkap sekolah...'" 
                        />
                      </div>
                    </div>

                    <!-- Input Nama Tim / Skuad (Dinamis Jika Cabang Lomba Beregu) -->
                    <div 
                      v-if="manualForm.package_type === 'Paket Satuan' && activeManualFormBuilder?.hasTeamName" 
                      class="p-2.5 rounded-3 border border-primary-subtle bg-primary-subtle bg-opacity-10 mb-3"
                    >
                      <label class="form-label fs-11 fw-bold text-primary mb-1 d-flex align-items-center justify-content-between">
                        <span><i class="ri-shield-star-line me-1"></i> {{ activeManualFormBuilder.teamLabel }} <span class="text-danger">*</span></span>
                        <span class="badge bg-primary text-white fs-10">Delegasi Beregu / Tim</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="manualForm.team_name" 
                        class="form-control form-control-sm fs-12"
                        :placeholder="activeManualFormBuilder.teamPlaceholder" 
                      />
                      <span class="fs-10 text-muted mt-1 d-block">Cabang lomba beregu ini mewajibkan penamaan skuad/tim kontingen.</span>
                    </div>

                    <hr class="my-3" />

                    <!-- Kontak Pembina / Pendamping -->
                    <span class="fs-11 fw-bold text-dark text-uppercase d-block mb-2">
                      <i class="ri-user-star-line text-success"></i> Kontak Pembina / Pendamping Resmi
                    </span>

                    <div class="mb-2">
                      <label class="form-label fs-11 fw-bold text-dark">
                        Nama Lengkap Pembina <span class="text-danger">*</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="manualForm.mentor_name" 
                        class="form-control form-control-sm fs-12"
                        placeholder="Contoh: H. Ahmad Fauzi, S.Pd" 
                        required
                      />
                    </div>

                    <div class="row g-2 mb-2">
                      <div class="col-7">
                        <label class="form-label fs-11 fw-bold text-dark">
                          No. WhatsApp <span class="text-danger">*</span>
                        </label>
                        <input 
                          type="text" 
                          v-model="manualForm.mentor_wa" 
                          class="form-control form-control-sm fs-12 font-monospace"
                          placeholder="081234567890" 
                          required
                        />
                      </div>
                      <div class="col-5">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ activeManualFormBuilder?.emailField?.label || 'Email (Opsional)' }}
                        </label>
                        <input 
                          type="email" 
                          v-model="manualForm.mentor_email" 
                          class="form-control form-control-sm fs-12"
                          placeholder="email@sekolah.sch.id" 
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- BAGIAN BAWAH: INFORMASI & SOLUSI SKEMA DELEGASI PESERTA (KHUSUSNYA PAKET KOMPLIT) -->
              <div class="col-12">
                <!-- IF PAKET SATUAN -->
                <div v-if="manualForm.package_type === 'Paket Satuan'" class="card shadow-sm border-0 border-start border-4 border-primary bg-white">
                  <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-start gap-3">
                      <div class="avatar-sm flex-shrink-0 rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ri-user-follow-line fs-22"></i>
                      </div>
                      <div class="flex-grow-1">
                        <h6 class="fs-13 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                          <span>Alur Delegasi Paket Satuan: {{ activeManualCompetition?.title || 'Cabang Terpilih' }}</span>
                          <span class="badge bg-primary-subtle text-primary fs-10 px-2 py-0.5 rounded-pill">1 Cabang Lomba</span>
                        </h6>
                        <p class="fs-12 text-muted mb-0">
                          Satu cabang lomba terpilih dengan kuota <strong>{{ activeManualFormBuilder?.minItems || 1 }} s.d {{ activeManualFormBuilder?.maxItems || 1 }} siswa</strong>. Anda dapat langsung menginput siswa delegasi sekarang di meja panitia atau menyimpan kontingen terlebih dahulu.
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- IF PAKET KOMPLIT (ALL-IN): SOLUSI UTAMA YANG MENGHILANGKAN KEBINGUNGAN -->
                <div v-else class="card shadow-sm border-0 border-start border-4 border-warning bg-white">
                  <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-start gap-3">
                      <div class="avatar-sm flex-shrink-0 rounded-circle bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="ri-vip-crown-fill fs-22 text-warning"></i>
                      </div>
                      <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                          <h6 class="fs-13 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <span>Solusi Delegasi Paket Komplit (All-In): {{ manualForm.competition_ids.length }} Cabang Terdaftar</span>
                            <span class="badge bg-warning text-dark fs-10 px-2 py-0.5 rounded-pill fw-bold">Multi-Cabang</span>
                          </h6>
                        </div>
                        <p class="fs-12 text-muted mb-2">
                          Karena setiap cabang lomba memiliki ketentuan kuota dan format peserta yang berbeda (misal: beregu vs individu), pengisian peserta dikelompokkan <strong>per cabang lomba</strong> secara teratur melalui <strong>Pengelola Delegasi Multi-Cabang</strong>.
                        </p>
                        <div class="p-2.5 rounded-3 bg-light border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 fs-11 text-dark">
                          <div>
                            <i class="ri-information-line text-primary me-1"></i>
                            <span>Pilih opsi tindakan yang paling sesuai dengan kondisi antrean di meja panitia saat ini:</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-white border-top py-2.5 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="showManualModal = false">Batal</button>

            <!-- Aksi Tombol Paket Satuan -->
            <div v-if="manualForm.package_type === 'Paket Satuan'" class="d-flex align-items-center gap-2 flex-wrap">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1.5 shadow-xs"
                :disabled="isSubmittingManual"
                @click="submitManualRegistration('save_only')"
                title="Simpan profil kontingen dan pembayaran. Pengisian siswa delegasi dapat dilengkapi nanti."
              >
                <i class="ri-save-line" v-if="!isSubmittingManual"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>Simpan Saja (Delegasi Diisi Nanti)</span>
              </button>
              <button 
                type="button" 
                class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
                :disabled="isSubmittingManual"
                @click="submitManualRegistration('manage_delegates')"
              >
                <i class="ri-user-add-line" v-if="!isSubmittingManual"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>Simpan &amp; Input Delegasi Siswa &rarr;</span>
              </button>
            </div>

            <!-- Aksi Tombol Paket Komplit (All-In) -->
            <div v-else class="d-flex align-items-center gap-2 flex-wrap">
              <button 
                type="button" 
                class="btn btn-sm btn-outline-warning text-dark d-inline-flex align-items-center gap-1.5 shadow-xs"
                :disabled="isSubmittingManual"
                @click="submitManualRegistration('save_only')"
                title="Daftarkan kontingen & catat pembayaran lunas. Delegasi semua cabang dapat dilengkapi kapan saja."
              >
                <i class="ri-check-line" v-if="!isSubmittingManual"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>Simpan Kontingen Saja (Delegasi Dilengkapi Nanti)</span>
              </button>
              <button 
                type="button" 
                class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
                :disabled="isSubmittingManual"
                @click="submitManualRegistration('manage_delegates')"
              >
                <i class="ri-team-line" v-if="!isSubmittingManual"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>Simpan &amp; Kelola Delegasi Multi-Cabang &rarr;</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL: KELOLA DELEGASI PESERTA (FORM BUILDER RULES)                  -->
    <!-- ===================================================================== -->
    <div v-if="showParticipantModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.65); z-index: 1065;">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
          <!-- Modal Header -->
          <div class="modal-header py-3 px-3 px-md-4 border-bottom bg-white d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2.5">
              <div class="avatar-xs flex-shrink-0 rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i class="ri-user-settings-line fs-18"></i>
              </div>
              <div>
                <h6 class="modal-title fw-bold mb-0 text-dark fs-14 d-flex align-items-center gap-2">
                  <span>Kelola Delegasi Peserta</span>
                  <span class="badge bg-primary text-white font-monospace fs-11 px-2 py-0.5 rounded-pill" v-if="activeRegForParticipants?.registration_code || activeRegForParticipants?.reg_code">
                    {{ activeRegForParticipants?.registration_code || activeRegForParticipants?.reg_code }}
                  </span>
                </h6>
                <div class="fs-12 text-muted d-flex align-items-center flex-wrap gap-2 mt-0.5">
                  <span class="fw-bold text-dark">{{ activeRegForParticipants?.competition_title }}</span>
                  <span class="text-muted">•</span>
                  <span>{{ activeRegForParticipants?.school_name }}</span>
                </div>
              </div>
            </div>
            <button type="button" class="btn-close" @click="showParticipantModal = false"></button>
          </div>

          <!-- Multi-Cabang Contingent Tab Navigator (Jika Kontingen Memiliki Lebih dari 1 Lomba) -->
          <div v-if="contingentRegistrations.length > 1" class="border-bottom bg-white px-3 px-md-4 py-2.5">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="fs-11 fw-bold text-dark text-uppercase d-flex align-items-center gap-1.5">
                <i class="ri-git-branch-line text-primary fs-14"></i>
                <span>Cabang Lomba Kontingen Ini (Pilih Tab untuk Mengisi / Mengedit Peserta):</span>
              </span>
              <span class="badge bg-primary-subtle text-primary fs-10 px-2 py-0.5 rounded-pill fw-semibold">
                {{ contingentRegistrations.length }} Cabang Terdaftar
              </span>
            </div>
            <div class="d-flex align-items-center gap-2 overflow-x-auto pb-1" style="scrollbar-width: thin;">
              <button 
                v-for="cReg in contingentRegistrations" 
                :key="cReg.registration_id || cReg.id"
                type="button"
                class="btn btn-sm text-nowrap d-inline-flex align-items-center gap-2 py-1 px-3 rounded-pill transition-all"
                :class="(activeRegForParticipants?.registration_id === (cReg.registration_id || cReg.id)) 
                  ? 'btn-primary fw-bold shadow-sm' 
                  : 'btn-outline-secondary bg-light text-dark'"
                @click="switchContingentCompetition(cReg)"
              >
                <span>{{ cReg.competition_title }}</span>
                <span 
                  class="badge rounded-pill fs-10"
                  :class="(cReg.students && cReg.students.length > 0) ? 'bg-success text-white' : 'bg-secondary text-white'"
                >
                  <i class="ri-check-line me-0.5" v-if="cReg.students && cReg.students.length > 0"></i>
                  {{ (cReg.students && cReg.students.length > 0) ? `${cReg.students.length} Siswa` : 'Belum Diisi' }}
                </span>
              </button>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-3 p-md-4 bg-light">
            <!-- Alert Aturan Form Builder -->
            <div class="card border-0 shadow-xs mb-3 bg-white">
              <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="p-2 rounded-2 bg-info-subtle text-info d-flex align-items-center justify-content-center">
                      <i class="ri-settings-4-line fs-18"></i>
                    </div>
                    <div>
                      <span class="fw-bold fs-12 text-dark d-block">Ketentuan Konfigurasi Form Builder Cabang Ini</span>
                      <span class="fs-11 text-muted">
                        Kolom formulir di bawah ini otomatis menyesuaikan aturan Form Builder cabang lomba ini.
                      </span>
                    </div>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success fs-12 px-2.5 py-1 rounded-pill fw-semibold">
                      <i class="ri-team-line me-1"></i> Terisi: {{ participantForm.delegates.length }} Delegasi
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary fs-12 px-2.5 py-1 rounded-pill">
                      Kuota: {{ activeBuilderRules.min_items }} s.d {{ activeBuilderRules.max_items }} Siswa
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- List Delegasi Peserta Repeater -->
            <div class="d-flex flex-column gap-3">
              <div 
                v-for="(delegate, dIdx) in participantForm.delegates" 
                :key="dIdx"
                class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white"
              >
                <!-- Card Header Delegasi -->
                <div class="card-header bg-white py-2.5 px-3 px-md-4 border-bottom d-flex align-items-center justify-content-between">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary text-white font-monospace fs-11 px-2.5 py-1 rounded-pill">
                      Siswa #{{ dIdx + 1 }}
                    </span>
                    <span class="fw-bold text-dark fs-13 text-truncate" v-if="delegate.name">
                      {{ delegate.name }}
                    </span>
                    <span class="badge bg-warning text-dark fs-10 px-2 py-0.5 rounded-pill fw-bold" v-if="delegate.is_captain">
                      <i class="ri-vip-crown-fill me-0.5"></i> Kapten Tim
                    </span>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <!-- Tombol Set Kapten (Jika captain_option aktif) -->
                    <button 
                      v-if="activeBuilderRules.sub_fields.captain_option?.active"
                      type="button" 
                      class="btn btn-xs rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1"
                      :class="delegate.is_captain ? 'btn-warning text-dark fw-bold shadow-xs' : 'btn-outline-secondary'"
                      @click="setCaptain(dIdx)"
                      title="Tetapkan siswa ini sebagai kapten tim"
                    >
                      <i :class="delegate.is_captain ? 'ri-star-fill text-dark' : 'ri-star-line'"></i>
                      <span>{{ delegate.is_captain ? 'Kapten Terpilih' : 'Jadikan Kapten' }}</span>
                    </button>

                    <!-- Tombol Hapus -->
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-danger rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1"
                      :disabled="participantForm.delegates.length <= activeBuilderRules.min_items"
                      @click="removeDelegateItem(dIdx)"
                      :title="participantForm.delegates.length <= activeBuilderRules.min_items ? 'Minimal ' + activeBuilderRules.min_items + ' siswa diwajibkan' : 'Hapus siswa ini'"
                    >
                      <i class="ri-delete-bin-line"></i>
                      <span class="d-none d-sm-inline">Hapus</span>
                    </button>
                  </div>
                </div>

                <!-- Card Body Form Delegasi -->
                <div class="card-body p-3 p-md-4">
                  <div class="row g-3">
                    <!-- Nama Lengkap Siswa (Wajib Ada) -->
                    <div class="col-12 col-md-6">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.name.label }} <span class="text-danger">*</span>
                      </label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                          <i class="ri-user-line"></i>
                        </span>
                        <input 
                          type="text" 
                          v-model="delegate.name" 
                          class="form-control form-control-sm fs-12 border-start-0 ps-1"
                          :placeholder="activeBuilderRules.sub_fields.name.placeholder" 
                        />
                      </div>
                    </div>

                    <!-- Jenis Kelamin (Jika Aktif) -->
                    <div class="col-6 col-md-3" v-if="activeBuilderRules.sub_fields.gender.active">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.gender.label }}
                        <span class="text-danger" v-if="activeBuilderRules.sub_fields.gender.required">*</span>
                      </label>
                      <CustomSelect 
                        v-model="delegate.gender" 
                        :options="genderOptions" 
                        size="sm"
                      />
                    </div>

                    <!-- Kelas (Jika Aktif) -->
                    <div class="col-6 col-md-3" v-if="activeBuilderRules.sub_fields.grade.active">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.grade.label }}
                        <span class="text-danger" v-if="activeBuilderRules.sub_fields.grade.required">*</span>
                      </label>
                      <CustomSelect 
                        v-model="delegate.grade" 
                        :options="gradeOptions" 
                        size="sm"
                      />
                    </div>

                    <!-- NISN (Jika Aktif) -->
                    <div class="col-12 col-md-4" v-if="activeBuilderRules.sub_fields.nisn.active">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.nisn.label }}
                        <span class="text-danger" v-if="activeBuilderRules.sub_fields.nisn.required">*</span>
                      </label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                          <i class="ri-barcode-line"></i>
                        </span>
                        <input 
                          type="text" 
                          v-model="delegate.nisn" 
                          class="form-control form-control-sm fs-12 border-start-0 ps-1 font-monospace"
                          :placeholder="activeBuilderRules.sub_fields.nisn.placeholder" 
                        />
                      </div>
                    </div>

                    <!-- Tanggal Lahir (Jika Aktif) - Rule 4: IndoDatePicker dd/mm/yyyy -->
                    <div class="col-12 col-md-4" v-if="activeBuilderRules.sub_fields.birth_date.active">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.birth_date.label }}
                        <span class="text-danger" v-if="activeBuilderRules.sub_fields.birth_date.required">*</span>
                      </label>
                      <IndoDatePicker 
                        v-model="delegate.birth_date" 
                        placeholder="dd/mm/yyyy"
                        inputClass="form-control form-control-sm fs-12"
                      />
                    </div>

                    <!-- Custom ID / No Dada / Game ID (Jika Aktif) -->
                    <div class="col-12 col-md-4" v-if="activeBuilderRules.sub_fields.custom_id.active">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.custom_id.label }}
                        <span class="text-danger" v-if="activeBuilderRules.sub_fields.custom_id.required">*</span>
                      </label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                          <i class="ri-gamepad-line"></i>
                        </span>
                        <input 
                          type="text" 
                          v-model="delegate.custom_id" 
                          class="form-control form-control-sm fs-12 border-start-0 ps-1 font-monospace"
                          :placeholder="activeBuilderRules.sub_fields.custom_id.placeholder" 
                        />
                      </div>
                    </div>

                    <!-- WhatsApp Siswa (Jika Aktif) -->
                    <div class="col-12 col-md-4" v-if="activeBuilderRules.sub_fields.wa_number.active">
                      <label class="form-label fs-11 fw-bold text-dark mb-1">
                        {{ activeBuilderRules.sub_fields.wa_number.label }}
                        <span class="text-danger" v-if="activeBuilderRules.sub_fields.wa_number.required">*</span>
                      </label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                          <i class="ri-whatsapp-line text-success"></i>
                        </span>
                        <input 
                          type="text" 
                          v-model="delegate.wa_number" 
                          class="form-control form-control-sm fs-12 border-start-0 ps-1"
                          :placeholder="activeBuilderRules.sub_fields.wa_number.placeholder" 
                        />
                      </div>
                    </div>

                    <!-- Nomor WhatsApp Khusus Kapten (Jika ini kapten & captain_option aktif) -->
                    <div class="col-12 col-md-4" v-if="activeBuilderRules.sub_fields.captain_option?.active && delegate.is_captain">
                      <label class="form-label fs-11 fw-bold text-warning-emphasis mb-1">
                        No. WA Kapten Tim
                      </label>
                      <div class="input-group input-group-sm">
                        <span class="input-group-text bg-warning-subtle border-end-0 text-warning-emphasis">
                          <i class="ri-phone-line"></i>
                        </span>
                        <input 
                          type="text" 
                          v-model="delegate.captain_wa" 
                          class="form-control form-control-sm fs-12 border-start-0 ps-1"
                          placeholder="08xxxxxxxxxx" 
                        />
                      </div>
                    </div>

                    <!-- Custom Columns Tambahan per Siswa -->
                    <template v-if="activeBuilderRules.sub_fields.custom_columns?.length">
                      <div 
                        v-for="(col, cIdx) in activeBuilderRules.sub_fields.custom_columns" 
                        :key="col.id || col.name || cIdx"
                        class="col-12 col-md-4"
                      >
                        <label class="form-label fs-11 fw-bold text-dark mb-1">
                          {{ col.label || col.name }}
                          <span class="text-danger" v-if="col.required">*</span>
                        </label>
                        <!-- Jika tipe select -->
                        <CustomSelect 
                          v-if="col.type === 'select'"
                          v-model="delegate.custom_columns[col.name || col.label]"
                          :options="(col.options || []).map(opt => typeof opt === 'object' ? opt : { value: opt, label: opt })"
                          size="sm"
                        />
                        <!-- Default text input -->
                        <input 
                          v-else
                          type="text" 
                          v-model="delegate.custom_columns[col.name || col.label]"
                          class="form-control form-control-sm fs-12"
                          :placeholder="col.placeholder || col.label" 
                        />
                      </div>
                    </template>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tombol Tambah Delegasi -->
            <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2">
              <button 
                type="button" 
                class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 py-1.5 px-3 rounded-pill shadow-xs"
                :disabled="participantForm.delegates.length >= activeBuilderRules.max_items"
                @click="addDelegateItem"
              >
                <i class="ri-user-add-line fs-14"></i>
                <span class="fw-semibold">+ Tambah Anggota Siswa Delegasi</span>
              </button>
              <span class="text-muted fs-11">
                Tersedia kuota: <strong class="text-dark">{{ participantForm.delegates.length }}</strong> dari maksimal <strong class="text-dark">{{ activeBuilderRules.max_items }} siswa</strong>
              </span>
            </div>

            <!-- Form Tambahan Non-Repeater (Jika Dikonfigurasi di Form Builder) -->
            <div class="card border-0 shadow-sm rounded-3 mt-3.5 bg-white" v-if="activeBuilderRules.additional_fields?.length > 0">
              <div class="card-header bg-white py-2.5 px-3 px-md-4 border-bottom">
                <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                  <i class="ri-survey-line text-info"></i> Formulir Khusus Cabang Lomba
                </span>
              </div>
              <div class="card-body p-3 p-md-4">
                <div class="row g-3">
                  <div 
                    v-for="field in activeBuilderRules.additional_fields" 
                    :key="field.id || field.name"
                    class="col-12"
                    :class="field.type === 'textarea' ? 'col-12' : 'col-md-6'"
                  >
                    <label class="form-label fs-11 fw-bold text-dark mb-1">
                      {{ field.label || field.name }}
                      <span class="text-danger" v-if="field.required">*</span>
                    </label>
                    
                    <!-- Select -->
                    <CustomSelect 
                      v-if="field.type === 'select'"
                      v-model="participantForm.additional_fields[field.name]"
                      :options="(field.options || []).map(opt => typeof opt === 'object' ? opt : { value: opt, label: opt })"
                      size="sm"
                    />

                    <!-- Date -->
                    <IndoDatePicker 
                      v-else-if="field.type === 'date'"
                      v-model="participantForm.additional_fields[field.name]"
                      placeholder="dd/mm/yyyy"
                      inputClass="form-control form-control-sm fs-12"
                    />

                    <!-- Textarea -->
                    <textarea 
                      v-else-if="field.type === 'textarea'"
                      v-model="participantForm.additional_fields[field.name]"
                      class="form-control form-control-sm fs-12"
                      rows="3"
                      :placeholder="field.placeholder || ''"
                    ></textarea>

                    <!-- Text / Number default -->
                    <input 
                      v-else
                      :type="field.type === 'number' ? 'number' : 'text'"
                      v-model="participantForm.additional_fields[field.name]"
                      class="form-control form-control-sm fs-12"
                      :placeholder="field.placeholder || ''"
                    />

                    <span class="text-muted fs-11 mt-1 d-block" v-if="field.help_text">{{ field.help_text }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-white border-top py-2.5 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="showParticipantModal = false">
              {{ contingentRegistrations.length > 1 ? 'Selesai Kelola Kontingen' : 'Tutup' }}
            </button>
            <div class="d-flex align-items-center gap-2">
              <span v-if="contingentRegistrations.length > 1" class="fs-11 text-muted d-none d-md-inline">
                Data disimpan per cabang lomba tanpa keluar dari modal.
              </span>
              <button 
                type="button" 
                class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
                :disabled="isSubmittingParticipants"
                @click="submitParticipants"
              >
                <i class="ri-check-double-line" v-if="!isSubmittingParticipants"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>{{ isSubmittingParticipants ? 'Menyimpan Delegasi...' : (contingentRegistrations.length > 1 ? `Simpan Delegasi ${activeCompetitionConfig?.title || 'Cabang Ini'}` : 'Simpan Data Delegasi Peserta') }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL: RINGKASAN PENDAFTARAN KONTINGEN BARU                           -->
    <!-- ===================================================================== -->
    <div v-if="showSummaryModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.65); z-index: 1060;">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
          <div class="modal-header py-3 px-3 px-md-4 border-bottom bg-success-subtle d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2.5">
              <div class="avatar-xs flex-shrink-0 rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="ri-checkbox-circle-fill fs-20"></i>
              </div>
              <div>
                <h6 class="modal-title fw-bold mb-0 text-success-emphasis fs-14">
                  Kontingen Berhasil Didaftarkan di Meja Panitia!
                </h6>
                <p class="fs-11 text-muted mb-0">Informasi sekolah, kontak pembina, dan tagihan telah tercatat resmi di database.</p>
              </div>
            </div>
            <button type="button" class="btn-close" @click="showSummaryModal = false"></button>
          </div>

          <div class="modal-body p-3 p-md-4 bg-light">
            <!-- Profil Kontingen -->
            <div class="card border-0 shadow-sm mb-3 bg-white">
              <div class="card-body p-3">
                <div class="row g-2 fs-12">
                  <div class="col-12 col-md-6">
                    <span class="text-muted d-block fs-11">Sekolah / Kontingen:</span>
                    <strong class="text-dark fs-13">{{ summaryData?.school_name }}</strong>
                  </div>
                  <div class="col-12 col-md-6">
                    <span class="text-muted d-block fs-11">Pembina / Kontak WA:</span>
                    <strong class="text-dark">{{ summaryData?.mentor_name }} ({{ summaryData?.mentor_wa }})</strong>
                  </div>
                  <div class="col-12 col-md-6 mt-2">
                    <span class="text-muted d-block fs-11">Skema Paket:</span>
                    <span class="badge" :class="summaryData?.package_type === 'Paket All-In' ? 'bg-warning text-dark' : 'bg-primary text-white'">
                      {{ summaryData?.package_type }}
                    </span>
                  </div>
                  <div class="col-12 col-md-6 mt-2">
                    <span class="text-muted d-block fs-11">Total Tagihan &amp; Status:</span>
                    <strong class="text-success">{{ formatRupiah(summaryData?.total_fee) }}</strong>
                    <span class="badge bg-success-subtle text-success ms-1.5">{{ summaryData?.payment_status }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Daftar Cabang Lomba Terdaftar -->
            <h6 class="fw-bold text-dark fs-12 mb-2 d-flex align-items-center justify-content-between">
              <span>Cabang Lomba yang Terdaftar ({{ summaryData?.competitions?.length || 0 }} Cabang):</span>
              <span class="text-muted fs-11">Delegasi dapat dilengkapi kapan saja</span>
            </h6>

            <div class="d-flex flex-column gap-2 mb-2">
              <div 
                v-for="(comp, cIdx) in (summaryData?.competitions || [])" 
                :key="cIdx"
                class="p-2.5 px-3 rounded-3 bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs"
              >
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary-subtle text-primary font-monospace fs-10">
                    {{ comp.reg_code || `Cabang #${cIdx + 1}` }}
                  </span>
                  <div>
                    <span class="fw-bold text-dark fs-12 d-block">{{ comp.competition_title }}</span>
                    <span class="fs-10 text-muted">
                      Status Delegasi: 
                      <strong :class="(comp.students && comp.students.length > 0) ? 'text-success' : 'text-warning'">
                        {{ (comp.students && comp.students.length > 0) ? `${comp.students.length} Siswa Terdaftar` : 'Belum Ada Delegasi' }}
                      </strong>
                    </span>
                  </div>
                </div>

                <button 
                  type="button" 
                  class="btn btn-xs btn-outline-success py-1 px-2.5 rounded-pill d-inline-flex align-items-center gap-1 shadow-xs"
                  @click="showSummaryModal = false; openParticipantModal(comp, { competitions: summaryData.competitions });"
                >
                  <i class="ri-user-star-line"></i>
                  <span>Input Delegasi Sekarang</span>
                </button>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-white border-top py-2.5 px-3 px-md-4 d-flex align-items-center justify-content-between">
            <span class="text-muted fs-11">Pendaftaran kontingen tersimpan aman di sistem.</span>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm" @click="showSummaryModal = false">
              <i class="ri-check-line"></i>
              <span>Selesai &amp; Lanjut Antrean Berikutnya</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 3: MERGE VARIASI NAMA SEKOLAH YANG SAMA                         -->
    <!-- ===================================================================== -->
    <div v-if="showMergeModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); z-index: 1060;">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
          <!-- Modal Header -->
          <div class="modal-header py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
            <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2 fs-14">
              <i class="ri-git-merge-line text-warning fs-18"></i>
              <span>Merge / Gabungkan Variasi Sekolah yang Sama</span>
            </h6>
            <button type="button" class="btn-close" @click="showMergeModal = false"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-3 bg-light">
            <!-- Penjelasan Informasi -->
            <div class="alert alert-warning border-warning-subtle d-flex align-items-start gap-2.5 fs-12 mb-3">
              <i class="ri-information-line fs-18 text-warning flex-shrink-0 mt-0.5"></i>
              <div>
                <strong>Cara Kerja Merge Sekolah:</strong>
                <p class="mb-0 mt-1">
                  Seluruh berkas pendaftaran dan cabang lomba yang tercatat di bawah nama sekolah sumber akan dialihkan secara otomatis ke <strong>Sekolah Target Baku</strong>. Jika sekolah mengikuti Paket All-In, sistem akan merapikan billing tagihan secara otomatis agar tidak terjadi tagihan ganda.
                </p>
              </div>
            </div>

            <!-- 1. Tentukan Sekolah Target -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <label class="form-label fs-12 fw-bold text-dark mb-0">
                    1. Tentukan Nama Sekolah Target (Rujukan Baku) <span class="text-danger">*</span>
                  </label>
                  <div class="btn-group btn-group-sm">
                    <button 
                      type="button" 
                      class="btn btn-xs py-0.5 px-2"
                      :class="mergeForm.target_mode === 'existing' ? 'btn-primary' : 'btn-light border'"
                      @click="mergeForm.target_mode = 'existing'"
                    >
                      Dari Terdaftar
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs py-0.5 px-2"
                      :class="mergeForm.target_mode === 'master' ? 'btn-primary' : 'btn-light border'"
                      @click="mergeForm.target_mode = 'master'"
                    >
                      Dari Master Sekolah
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs py-0.5 px-2"
                      :class="mergeForm.target_mode === 'custom' ? 'btn-primary' : 'btn-light border'"
                      @click="mergeForm.target_mode = 'custom'"
                    >
                      Ketik Bebas
                    </button>
                  </div>
                </div>

                <div v-if="mergeForm.target_mode === 'existing'">
                  <CustomSelect 
                    v-model="mergeForm.target_school_name" 
                    :options="targetExistingOptions" 
                    size="sm"
                    searchable
                    search-placeholder="Cari sekolah yang sudah terdaftar..."
                    @update:modelValue="autoSelectSimilarSchools"
                  />
                </div>
                <div v-else-if="mergeForm.target_mode === 'master'">
                  <CustomSelect 
                    v-model="mergeForm.target_school_name" 
                    :options="targetMasterOptions" 
                    size="sm"
                    searchable
                    search-placeholder="Cari dari master sekolah..."
                    @update:modelValue="autoSelectSimilarSchools"
                  />
                </div>
                <div v-else>
                  <input 
                    type="text" 
                    v-model="mergeForm.target_school_name" 
                    class="form-control form-control-sm fs-12"
                    placeholder="Ketik nama sekolah target resmi..." 
                  />
                </div>

                <div v-if="mergeForm.target_school_name" class="mt-2 text-primary fs-11 fw-semibold">
                  <i class="ri-check-line"></i> Target: [{{ mergeForm.target_school_name }}]
                </div>
              </div>
            </div>

            <!-- 2. Pilih Variasi Sekolah Sumber yang Akan Digabung -->
            <div class="card shadow-sm border-0">
              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <label class="form-label fs-12 fw-bold text-dark mb-0">
                    2. Pilih Variasi Sekolah yang Akan Digabungkan (Sumber) <span class="text-danger">*</span>
                  </label>
                  <span class="badge bg-secondary fs-10">
                    {{ mergeForm.source_school_names.length }} Dipilih
                  </span>
                </div>

                <p class="fs-11 text-muted mb-2">
                  Centang nama-nama pendaftaran di bawah ini yang merupakan variasi dari sekolah target:
                </p>

                <div class="border rounded p-2 bg-white" style="max-height: 220px; overflow-y: auto;">
                  <div 
                    v-for="(sName, idx) in all_registered_school_names.filter(n => n !== mergeForm.target_school_name)" 
                    :key="idx"
                    class="p-1.5 px-2 rounded mb-1 cursor-pointer d-flex align-items-center justify-content-between transition-all"
                    :class="mergeForm.source_school_names.includes(sName) ? 'bg-primary-subtle border border-primary text-primary fw-bold' : 'hover-bg-light text-dark'"
                    @click="toggleSourceSchool(sName)"
                  >
                    <div class="d-flex align-items-center gap-2">
                      <i 
                        class="fs-15"
                        :class="mergeForm.source_school_names.includes(sName) ? 'ri-checkbox-circle-fill text-primary' : 'ri-checkbox-blank-circle-line text-muted'"
                      ></i>
                      <span class="fs-12">{{ sName }}</span>
                    </div>
                    <span v-if="mergeForm.source_school_names.includes(sName)" class="badge bg-primary fs-9">
                      Akan Di-merge
                    </span>
                  </div>

                  <div v-if="all_registered_school_names.length <= 1" class="text-muted fs-11 text-center py-3">
                    Tidak ada variasi sekolah lain yang terdaftar.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-white border-top py-2.5 d-flex align-items-center justify-content-between">
            <button type="button" class="btn btn-sm btn-secondary" @click="showMergeModal = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-warning text-dark fw-bold d-inline-flex align-items-center gap-1.5 shadow-sm"
              :disabled="isSubmittingMerge || mergeForm.source_school_names.length === 0"
              @click="submitMergeSchools"
            >
              <i class="ri-git-merge-line"></i>
              <span>{{ isSubmittingMerge ? 'Memproses Merge...' : 'Gabungkan Sekolah Sekarang' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.transition-all {
  transition: all 0.2s ease-in-out;
}

.hover-shadow:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12) !important;
}

.cursor-pointer {
  cursor: pointer;
}

.text-truncate-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.hover-bg-light:hover {
  background-color: #f8fafc;
}

.whitespace-pre-wrap {
  white-space: pre-wrap;
}

/* Elegant Participant Counter Pill (Subtle & Blends with Chip Color) */
.comp-participant-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 17px;
  height: 17px;
  padding: 0 4px;
  border-radius: 9999px;
  font-size: 10px;
  font-weight: 700;
  line-height: 1;
  color: inherit;
  background-color: rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.08);
  margin-left: 2px;
}

:deep([data-bs-theme="dark"]) .comp-participant-count {
  background-color: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.14);
  color: inherit;
}

/* Dark Mode WCAG AA Compliance for Participant Modal */
:deep([data-bs-theme="dark"]) .modal-content {
  background-color: #1a1d21;
  color: #f8fafc;
}

:deep([data-bs-theme="dark"]) .modal-header,
:deep([data-bs-theme="dark"]) .modal-footer,
:deep([data-bs-theme="dark"]) .modal-body .card-header,
:deep([data-bs-theme="dark"]) .modal-body .card.bg-white {
  background-color: #212529 !important;
  color: #f8fafc !important;
}

:deep([data-bs-theme="dark"]) .modal-body.bg-light {
  background-color: #181b1e !important;
}

:deep([data-bs-theme="dark"]) .modal-body .form-label,
:deep([data-bs-theme="dark"]) .modal-title {
  color: #f1f5f9 !important;
}

:deep([data-bs-theme="dark"]) .modal-body .input-group-text.bg-white {
  background-color: #2b3035 !important;
  color: #adb5bd !important;
  border-color: #373b3e !important;
}

:deep([data-bs-theme="dark"]) .modal-body .form-control {
  background-color: #2b3035;
  color: #f8fafc;
  border-color: #373b3e;
}

:deep([data-bs-theme="dark"]) .modal-body .form-control:focus {
  background-color: #2b3035;
  color: #ffffff;
  border-color: #0ab39c;
}

/* Print Styling */
@media print {
  .d-print-none {
    display: none !important;
  }
  .card {
    border: 1px solid #ddd !important;
    box-shadow: none !important;
  }
  .school-card {
    break-inside: avoid;
    page-break-inside: avoid;
    margin-bottom: 1.5rem;
  }
}
</style>
