<script setup>
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  show: { type: Boolean, default: false },
  competitions: { type: Array, default: () => [] },
  masterSchools: { type: Array, default: () => [] },
  packageAllinPrice: { type: Number, default: 200000 },
  bankAccounts: { type: Array, default: () => [] },
  prefilledSchool: { type: Object, default: null },
});

const emit = defineEmits(['update:show', 'saved']);

const page = usePage();
const toast = useToast();
const { confirm: confirmDialog } = useConfirmDialog();

// -----------------------------------------------------------------------------
// FORMATTING HELPERS
// -----------------------------------------------------------------------------
function formatRupiah(num) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}

// -----------------------------------------------------------------------------
// 31 KECAMATAN RESMI KABUPATEN JEMBER
// -----------------------------------------------------------------------------
const JEMBER_KECAMATAN_LIST = [
  'Ajung', 'Ambulu', 'Arjasa', 'Balung', 'Bangsalsari', 'Gumukmas', 'Jelbuk', 'Jenggawah',
  'Jombang', 'Kalisat', 'Kaliwates', 'Kencong', 'Ledokombo', 'Mayang', 'Mumbulsari',
  'Pakusari', 'Panti', 'Patrang', 'Puger', 'Rambipuji', 'Semboro', 'Silo', 'Sukorambi',
  'Sukowono', 'Sumberbaru', 'Sumberjambe', 'Sumbersari', 'Tanggul', 'Tempurejo', 'Umbulsari',
  'Wuluhan', 'Lainnya'
];

const kecamatanOptions = computed(() => {
  return JEMBER_KECAMATAN_LIST.map(k => ({
    value: k,
    label: k === 'Lainnya' ? '✍️ Lainnya (Ketik Manual)' : `Kecamatan ${k}`
  }));
});

const manualPaymentStatusOptions = [
  { value: 'Terverifikasi', label: 'Terverifikasi (Lunas Langsung di Tempat)' },
  { value: 'Menunggu Verifikasi', label: 'Menunggu Verifikasi (Transfer Bank/QRIS)' },
  { value: 'Belum Bayar', label: 'Belum Bayar (Pending)' },
];

const manualPaymentMethodOptions = computed(() => {
  const options = [
    { value: 'Tunai di Meja Panitia', label: 'Tunai / Cash (Meja Panitia)' }
  ];

  const bankAccounts = props.bankAccounts?.length 
    ? props.bankAccounts 
    : (page?.props?.paymentBankAccounts || []);

  const activeBanks = (bankAccounts || []).filter(b => b.is_active !== false);

  if (activeBanks.length > 0) {
    activeBanks.forEach(b => {
      const bankTitle = b.bank_name || 'Bank';
      const num = b.account_number ? ` (${b.account_number}${b.account_holder ? ' a.n. ' + b.account_holder : ''})` : '';
      options.push({
        value: `Transfer ${bankTitle}`,
        label: `Transfer ${bankTitle}${num}`
      });
    });
  } else {
    options.push({ value: 'Transfer Bank BSI', label: 'Transfer Bank BSI' });
  }

  options.push(
    { value: 'QRIS Semarak Aliyah', label: 'QRIS Semarak Aliyah' },
    { value: 'Lainnya', label: 'Metode Pembayaran Lainnya' }
  );

  return options;
});

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

// -----------------------------------------------------------------------------
// STATE MODAL
// -----------------------------------------------------------------------------
const manualStep = ref(1); // 1: Paket & Sekolah, 2: Delegasi Siswa, 3: Pembayaran & Simpan
const isSubmittingManual = ref(false);

const manualSingleDelegates = ref([
  { name: '', nisn: '', gender: 'Laki-laki', grade: '7', is_captain: false }
]);
const manualAllInDelegates = ref({});
const activeAllInTab = ref(null);

const manualForm = ref({
  school_selection_mode: 'master', // 'master' | 'custom'
  selected_master_id: '',
  school_name: '',
  kecamatan: '',
  custom_kecamatan: '',
  npsn: '',
  alamat: '',
  mentor_name: '',
  mentor_wa: '',
  mentor_email: '',
  team_name: '',
  package_type: 'Paket Satuan', // 'Paket Satuan' | 'Paket All-In'
  competition_id: '',
  competition_ids: [],
  custom_fee: null,
  package_price: props.packageAllinPrice,
  payment_status: 'Terverifikasi',
  payment_method: 'Tunai di Meja Panitia',
  notes: 'Pendaftaran langsung on-the-spot di Meja Panitia',
});

// Summary modal state after successful submission
const showSummaryModal = ref(false);
const summaryData = ref(null);

const masterSchoolOptions = computed(() => {
  const opts = [{ value: '', label: '-- Pilih Sekolah dari Database Master --' }];
  (props.masterSchools || []).forEach(s => {
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
  const found = (props.masterSchools || []).find(s => String(s.id) === String(schoolId));
  if (found) {
    manualForm.value.school_name = found.name;
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

function onSchoolNameInput() {
  if (manualForm.value.school_selection_mode === 'custom' && manualForm.value.school_name) {
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

function createEmptyManualDelegate(builder = null) {
  const customCols = {};
  if (builder?.sub_fields?.custom_columns && Array.isArray(builder.sub_fields.custom_columns)) {
    builder.sub_fields.custom_columns.forEach(col => {
      customCols[col.name || col.label] = '';
    });
  }
  return {
    name: '',
    nisn: '',
    gender: 'Laki-laki',
    grade: '7',
    birth_date: '',
    custom_id: '',
    wa_number: '',
    is_captain: false,
    custom_columns: customCols,
  };
}

function initModalData(prefilledSchool = null) {
  manualStep.value = 1;
  manualSingleDelegates.value = [
    createEmptyManualDelegate(activeManualFormBuilder.value)
  ];
  manualAllInDelegates.value = {};

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
    package_price: props.packageAllinPrice,
    payment_status: 'Terverifikasi',
    payment_method: 'Tunai di Meja Panitia',
    notes: 'Pendaftaran langsung on-the-spot di Meja Panitia',
  };

  if (manualForm.value.competition_ids.length > 0) {
    activeAllInTab.value = manualForm.value.competition_ids[0];
  } else if (props.competitions && props.competitions.length > 0) {
    activeAllInTab.value = props.competitions[0].id;
  }

  if (prefilledSchool) {
    const norm = prefilledSchool.school_name.toLowerCase().replace(/[^a-z0-9]/g, '');
    const found = (props.masterSchools || []).find(ms => ms.name.toLowerCase().replace(/[^a-z0-9]/g, '') === norm);
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
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    initModalData(props.prefilledSchool);
  }
});

function closeModal() {
  emit('update:show', false);
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
// NAVIGASI LANGKAH WIZARD (3 LANGKAH)
// -----------------------------------------------------------------------------
function goToManualStep(targetStep) {
  if (targetStep > 1) {
    if (manualForm.value.package_type === 'Paket Satuan' && !manualForm.value.competition_id) {
      toast.warning('Silakan pilih cabang lomba pada kolom sebelah kiri terlebih dahulu!', 'Pilih Cabang Lomba');
      manualStep.value = 1;
      return;
    }
    if (manualForm.value.package_type === 'Paket All-In' && manualForm.value.competition_ids.length === 0) {
      toast.warning('Pilih minimal satu cabang lomba pada kolom sebelah kiri untuk Paket Komplit (All-In)!', 'Pilih Cabang Lomba');
      manualStep.value = 1;
      return;
    }

    if (!manualForm.value.school_name.trim()) {
      toast.warning('Silakan isi nama sekolah / madrasah pada kolom sebelah kanan!', 'Nama Sekolah Wajib');
      manualStep.value = 1;
      return;
    }
    if (!manualForm.value.kecamatan) {
      toast.warning('Kecamatan asal sekolah wajib dipilih!', 'Kecamatan Belum Dipilih');
      manualStep.value = 1;
      return;
    }
    if (manualForm.value.kecamatan === 'Lainnya' && !manualForm.value.custom_kecamatan?.trim()) {
      toast.warning('Silakan ketik nama kecamatan pada kolom isian manual!', 'Ketik Nama Kecamatan');
      manualStep.value = 1;
      return;
    }
    if (!manualForm.value.mentor_name.trim() || !manualForm.value.mentor_wa.trim()) {
      toast.warning('Nama pembina/pendamping dan nomor WhatsApp wajib diisi!', 'Kontak Pembina Wajib');
      manualStep.value = 1;
      return;
    }

    if (manualForm.value.package_type === 'Paket Satuan') {
      if (!manualSingleDelegates.value || manualSingleDelegates.value.length === 0) {
        manualSingleDelegates.value = [createEmptyManualDelegate(activeManualFormBuilder.value)];
      }
    } else {
      manualForm.value.competition_ids.forEach(cId => {
        if (!manualAllInDelegates.value[cId] || manualAllInDelegates.value[cId].length === 0) {
          manualAllInDelegates.value[cId] = [createEmptyManualDelegate(getAllInBuilder(cId))];
        }
      });
      if (!activeAllInTab.value || !manualForm.value.competition_ids.includes(activeAllInTab.value)) {
        activeAllInTab.value = manualForm.value.competition_ids[0] || null;
      }
    }
  }

  // Validasi sebelum melangkah ke Langkah 3 (Pembayaran)
  if (targetStep === 3) {
    if (manualForm.value.package_type === 'Paket Satuan') {
      const rules = activeManualFormBuilder.value;
      if (rules && rules.sub_fields) {
        for (let i = 0; i < manualSingleDelegates.value.length; i++) {
          const st = manualSingleDelegates.value[i];
          if (st.name && st.name.trim()) {
            if (rules.sub_fields.nisn?.active && rules.sub_fields.nisn?.required && !st.nisn?.trim()) {
              toast.warning(`NISN untuk siswa "${st.name}" wajib diisi!`, 'NISN Wajib Diisi');
              return;
            }
            if (rules.sub_fields.birth_date?.active && rules.sub_fields.birth_date?.required && !st.birth_date?.trim()) {
              toast.warning(`Tanggal lahir untuk siswa "${st.name}" wajib diisi!`, 'Tanggal Lahir Wajib');
              return;
            }
            if (rules.sub_fields.custom_id?.active && rules.sub_fields.custom_id?.required && !st.custom_id?.trim()) {
              toast.warning(`${rules.sub_fields.custom_id.label} untuk siswa "${st.name}" wajib diisi!`, `${rules.sub_fields.custom_id.label} Wajib`);
              return;
            }
            if (rules.sub_fields.wa_number?.active && rules.sub_fields.wa_number?.required && !st.wa_number?.trim()) {
              toast.warning(`Nomor WhatsApp untuk siswa "${st.name}" wajib diisi!`, 'No. WA Wajib');
              return;
            }
            if (rules.sub_fields.custom_columns?.length) {
              for (const col of rules.sub_fields.custom_columns) {
                if (col.required && (!st.custom_columns || !st.custom_columns[col.name || col.label]?.trim())) {
                  toast.warning(`${col.label || col.name} untuk siswa "${st.name}" wajib diisi!`, `${col.label || col.name} Wajib`);
                  return;
                }
              }
            }
          }
        }
      }
    } else {
      // Paket All-In
      for (const cId of manualForm.value.competition_ids) {
        const rules = getAllInBuilder(cId);
        const delegates = manualAllInDelegates.value[cId] || [];
        if (rules && rules.sub_fields) {
          for (let i = 0; i < delegates.length; i++) {
            const st = delegates[i];
            if (st.name && st.name.trim()) {
              if (rules.sub_fields.nisn?.active && rules.sub_fields.nisn?.required && !st.nisn?.trim()) {
                toast.warning(`[${rules.comp?.title}] NISN untuk siswa "${st.name}" wajib diisi!`, 'NISN Wajib Diisi');
                activeAllInTab.value = cId;
                return;
              }
              if (rules.sub_fields.birth_date?.active && rules.sub_fields.birth_date?.required && !st.birth_date?.trim()) {
                toast.warning(`[${rules.comp?.title}] Tanggal lahir untuk siswa "${st.name}" wajib diisi!`, 'Tanggal Lahir Wajib');
                activeAllInTab.value = cId;
                return;
              }
              if (rules.sub_fields.custom_id?.active && rules.sub_fields.custom_id?.required && !st.custom_id?.trim()) {
                toast.warning(`[${rules.comp?.title}] ${rules.sub_fields.custom_id.label} untuk siswa "${st.name}" wajib diisi!`, `${rules.sub_fields.custom_id.label} Wajib`);
                activeAllInTab.value = cId;
                return;
              }
              if (rules.sub_fields.wa_number?.active && rules.sub_fields.wa_number?.required && !st.wa_number?.trim()) {
                toast.warning(`[${rules.comp?.title}] Nomor WhatsApp untuk siswa "${st.name}" wajib diisi!`, 'No. WA Wajib');
                activeAllInTab.value = cId;
                return;
              }
              if (rules.sub_fields.custom_columns?.length) {
                for (const col of rules.sub_fields.custom_columns) {
                  if (col.required && (!st.custom_columns || !st.custom_columns[col.name || col.label]?.trim())) {
                    toast.warning(`[${rules.comp?.title}] ${col.label || col.name} untuk siswa "${st.name}" wajib diisi!`, `${col.label || col.name} Wajib`);
                    activeAllInTab.value = cId;
                    return;
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  manualStep.value = targetStep;
}

// -----------------------------------------------------------------------------
// HELPER FORM BUILDER & DELEGASI SISWA
// -----------------------------------------------------------------------------
const activeManualCompetition = computed(() => {
  if (manualForm.value.package_type === 'Paket Satuan') {
    if (manualForm.value.competition_id) {
      return props.competitions.find(c => String(c.id) === String(manualForm.value.competition_id)) || props.competitions[0] || null;
    }
    return props.competitions[0] || null;
  }
  if (manualForm.value.competition_ids && manualForm.value.competition_ids.length > 0) {
    return props.competitions.find(c => c.id === manualForm.value.competition_ids[0]) || props.competitions[0] || null;
  }
  return props.competitions[0] || null;
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

const IDENTITY_ALIASES = {
  school_name: ['school_name', 'asal_sekolah', 'nama_sekolah', 'nama_lembaga', 'nama_sekolah_madrasah', 'nama_sekolah_madrasah_pengirim', 'nama_sekolah_madrasah_instansi_pengirim'],
  school_district: ['school_district', 'kecamatan', 'kecamatan_sekolah', 'kecamatan_asal_sekolah', 'kecamatan_asal_sekolah_madrasah', 'wilayah_kecamatan'],
  school_npsn: ['school_npsn', 'npsn', 'npsn_sekolah', 'nomor_pokok_sekolah_nasional', 'nomor_pokok_sekolah_nasional_npsn'],
  school_address: ['school_address', 'alamat_sekolah', 'alamat_lengkap_sekolah', 'alamat_sekolah_madrasah', 'alamat_lengkap_sekolah_madrasah', 'alamat'],
  mentor_name: ['mentor_name', 'nama_pendamping', 'nama_guru_pendamping', 'nama_pembina', 'nama_guru_pembina_pendamping', 'nama_pelatih_official'],
  mentor_wa: ['mentor_wa', 'nomor_wa', 'whatsapp', 'nomor_whatsapp', 'nomor_whatsapp_pendamping', 'nomor_whatsapp_kontak', 'nomor_whatsapp_pembina', 'no_whatsapp_pelatih_official', 'no_whatsapp_kapten_kontak_tim'],
  mentor_email: ['mentor_email', 'contact_email', 'email', 'alamat_email', 'email_pendamping', 'alamat_email_aktif', 'email_kontak', 'alamat_email_resmi_sekolah_pembina'],
  allin_code: ['allin_code', 'kode_paket_komplit', 'kode_paket', 'kode_allin', 'package_code', 'all_in_code', 'kode_paket_allin']
};

function getManualFieldConfig(canonicalName) {
  const fields = activeManualCompetition.value?.custom_fields || [];
  const aliases = IDENTITY_ALIASES[canonicalName] || [canonicalName];
  return fields.find(f => f && (aliases.includes(f.name) || aliases.includes(f.default_name))) || null;
}

function getManualFieldLabel(canonicalName, fallback = '') {
  const f = getManualFieldConfig(canonicalName);
  if (f && typeof f.label === 'string' && f.label.trim()) {
    return f.label.trim();
  }
  return fallback;
}

function getManualFieldPlaceholder(canonicalName, fallback = '') {
  const f = getManualFieldConfig(canonicalName);
  if (f && typeof f.placeholder === 'string' && f.placeholder.trim()) {
    return f.placeholder.trim();
  }
  return fallback;
}

function getManualFieldHelpText(canonicalName, fallback = '') {
  const f = getManualFieldConfig(canonicalName);
  if (f && typeof f.help_text === 'string' && f.help_text.trim()) {
    return f.help_text.trim();
  }
  return fallback;
}

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
  const isTeam = maxItems > 1 || hasTeamName || comp.participant_type === 'team' || comp.participant_type === 'regu' || !!rawSubs.captain_option?.active;

  const subFields = {
    name: { active: true, required: true, label: rawSubs.name?.label || 'Nama Siswa', placeholder: rawSubs.name?.placeholder || 'Nama lengkap siswa...' },
    gender: { active: rawSubs.gender?.active !== false, required: rawSubs.gender?.required !== false, label: rawSubs.gender?.label || 'L/P' },
    grade: { active: rawSubs.grade?.active !== false, required: rawSubs.grade?.required !== false, label: rawSubs.grade?.label || 'Kelas' },
    nisn: { active: rawSubs.nisn?.active !== false, required: !!rawSubs.nisn?.required, label: rawSubs.nisn?.label || 'NISN', placeholder: rawSubs.nisn?.placeholder || 'NISN...' },
    birth_date: { active: !!rawSubs.birth_date?.active, required: !!rawSubs.birth_date?.required, label: rawSubs.birth_date?.label || 'Tgl Lahir' },
    custom_id: { active: !!rawSubs.custom_id?.active, required: !!rawSubs.custom_id?.required, label: rawSubs.custom_id?.label || 'No. Dada / ID Game', placeholder: rawSubs.custom_id?.placeholder || 'ID Peserta / Akun...' },
    wa_number: { active: !!rawSubs.wa_number?.active, required: !!rawSubs.wa_number?.required, label: rawSubs.wa_number?.label || 'WhatsApp Siswa', placeholder: rawSubs.wa_number?.placeholder || '08xxxxxxxxxx' },
    captain_option: { active: !!rawSubs.captain_option?.active || isTeam, required: false, label: rawSubs.captain_option?.label || 'Kapten' },
    custom_columns: Array.isArray(rawSubs.custom_columns) ? rawSubs.custom_columns : [],
  };

  return {
    comp,
    repeater,
    rawSubs,
    sub_fields: subFields,
    hasTeamName,
    teamLabel,
    teamPlaceholder,
    npsnField,
    addressField,
    emailField,
    otherFields,
    minItems,
    maxItems,
    isTeam,
  };
});

const manualTotalCalculatedFee = computed(() => {
  if (manualForm.value.package_type === 'Paket All-In') {
    return manualForm.value.package_price !== null && manualForm.value.package_price !== undefined && manualForm.value.package_price !== ''
      ? Number(manualForm.value.package_price)
      : Number(props.packageAllinPrice || 200000);
  }
  if (manualForm.value.custom_fee !== null && manualForm.value.custom_fee !== undefined && manualForm.value.custom_fee !== '') {
    return Number(manualForm.value.custom_fee);
  }
  const comp = activeManualCompetition.value;
  return Number(comp?.price || comp?.fee || 0);
});

// Repeater helpers - Satuan
function addSingleDelegate() {
  const max = activeManualFormBuilder.value?.maxItems || 20;
  if (manualSingleDelegates.value.length >= max) {
    toast.warning(`Maksimal peserta untuk cabang ini adalah ${max} siswa.`, 'Batas Kuota');
    return;
  }
  manualSingleDelegates.value.push(createEmptyManualDelegate(activeManualFormBuilder.value));
}

function removeSingleDelegate(idx) {
  if (manualSingleDelegates.value.length > 1) {
    manualSingleDelegates.value.splice(idx, 1);
  } else {
    manualSingleDelegates.value[0] = createEmptyManualDelegate(activeManualFormBuilder.value);
  }
}

function setSingleCaptain(idx) {
  manualSingleDelegates.value.forEach((d, i) => {
    d.is_captain = (i === idx);
  });
}

// Repeater helpers - All-In
function getAllInComp(cId) {
  return props.competitions.find(c => c.id === cId) || null;
}

function getAllInBuilder(cId) {
  const comp = getAllInComp(cId);
  if (!comp) {
    return {
      comp: null,
      minItems: 1,
      maxItems: 1,
      isTeam: false,
      teamLabel: 'Tim',
      rawSubs: {},
      sub_fields: {
        name: { active: true, required: true, label: 'Nama Siswa', placeholder: 'Nama lengkap siswa...' },
        gender: { active: true, required: true, label: 'L/P' },
        grade: { active: true, required: true, label: 'Kelas' },
        nisn: { active: true, required: false, label: 'NISN', placeholder: 'NISN...' },
        birth_date: { active: false, required: false, label: 'Tgl Lahir' },
        custom_id: { active: false, required: false, label: 'No. Dada / ID Game', placeholder: 'ID...' },
        wa_number: { active: false, required: false, label: 'WhatsApp', placeholder: '08...' },
        captain_option: { active: false, required: false, label: 'Kapten' },
        custom_columns: [],
      }
    };
  }
  const customFields = Array.isArray(comp.custom_fields) ? comp.custom_fields : [];
  const repeater = customFields.find(f => f.type === 'participant_repeater');
  const rawSubs = repeater?.sub_fields || {};
  const minItems = repeater?.min_items ? Number(repeater.min_items) : Math.max(1, Number(comp.min_participants || 1));
  const maxItems = repeater?.max_items ? Number(repeater.max_items) : Math.max(minItems, Number(comp.max_participants || 1));
  const isTeam = maxItems > 1 || comp.participant_type === 'team' || comp.participant_type === 'regu' || !!rawSubs.captain_option?.active;

  const subFields = {
    name: { active: true, required: true, label: rawSubs.name?.label || 'Nama Siswa', placeholder: rawSubs.name?.placeholder || 'Nama lengkap siswa...' },
    gender: { active: rawSubs.gender?.active !== false, required: rawSubs.gender?.required !== false, label: rawSubs.gender?.label || 'L/P' },
    grade: { active: rawSubs.grade?.active !== false, required: rawSubs.grade?.required !== false, label: rawSubs.grade?.label || 'Kelas' },
    nisn: { active: rawSubs.nisn?.active !== false, required: !!rawSubs.nisn?.required, label: rawSubs.nisn?.label || 'NISN', placeholder: rawSubs.nisn?.placeholder || 'NISN...' },
    birth_date: { active: !!rawSubs.birth_date?.active, required: !!rawSubs.birth_date?.required, label: rawSubs.birth_date?.label || 'Tgl Lahir' },
    custom_id: { active: !!rawSubs.custom_id?.active, required: !!rawSubs.custom_id?.required, label: rawSubs.custom_id?.label || 'No. Dada / ID Game', placeholder: rawSubs.custom_id?.placeholder || 'ID Peserta / Akun...' },
    wa_number: { active: !!rawSubs.wa_number?.active, required: !!rawSubs.wa_number?.required, label: rawSubs.wa_number?.label || 'WhatsApp Siswa', placeholder: rawSubs.wa_number?.placeholder || '08xxxxxxxxxx' },
    captain_option: { active: !!rawSubs.captain_option?.active || isTeam, required: false, label: rawSubs.captain_option?.label || 'Kapten' },
    custom_columns: Array.isArray(rawSubs.custom_columns) ? rawSubs.custom_columns : [],
  };

  return { comp, minItems, maxItems, isTeam, rawSubs, sub_fields: subFields };
}

function addAllInDelegate(cId) {
  if (!manualAllInDelegates.value[cId]) {
    manualAllInDelegates.value[cId] = [];
  }
  const builder = getAllInBuilder(cId);
  if (manualAllInDelegates.value[cId].length >= builder.maxItems) {
    toast.warning(`Maksimal peserta untuk cabang ${builder.comp?.title} adalah ${builder.maxItems} siswa.`, 'Batas Kuota');
    return;
  }
  manualAllInDelegates.value[cId].push(createEmptyManualDelegate(builder));
}

function removeAllInDelegate(cId, idx) {
  const builder = getAllInBuilder(cId);
  if (manualAllInDelegates.value[cId] && manualAllInDelegates.value[cId].length > 1) {
    manualAllInDelegates.value[cId].splice(idx, 1);
  } else if (manualAllInDelegates.value[cId]) {
    manualAllInDelegates.value[cId][0] = createEmptyManualDelegate(builder);
  }
}

function setAllInCaptain(cId, idx) {
  if (manualAllInDelegates.value[cId]) {
    manualAllInDelegates.value[cId].forEach((d, i) => {
      d.is_captain = (i === idx);
    });
  }
}

function getAllInFilledCount(cId) {
  const list = manualAllInDelegates.value[cId] || [];
  return list.filter(d => d.name && d.name.trim().length > 0).length;
}

// -----------------------------------------------------------------------------
// SUBMIT PENDAFTARAN MANUAL (DIEKSEKUSI DI LANGKAH 3)
// -----------------------------------------------------------------------------
async function submitManualRegistration(saveMode = 'final') {
  if (manualForm.value.package_type === 'Paket Satuan' && !manualForm.value.competition_id) {
    manualStep.value = 1;
    toast.warning('Silakan pilih cabang lomba terlebih dahulu.', 'Pilih Cabang Lomba');
    return;
  }
  if (manualForm.value.package_type === 'Paket All-In' && manualForm.value.competition_ids.length === 0) {
    manualStep.value = 1;
    toast.warning('Pilih minimal satu cabang lomba untuk Paket Komplit (All-In).', 'Pilih Cabang Lomba');
    return;
  }

  if (!manualForm.value.school_name.trim() || !manualForm.value.kecamatan || !manualForm.value.mentor_name.trim() || !manualForm.value.mentor_wa.trim()) {
    manualStep.value = 1;
    toast.warning('Identitas sekolah dan kontak pembina wajib diisi pada Langkah 1!', 'Lengkapi Identitas');
    return;
  }

  const finalKecamatan = manualForm.value.kecamatan === 'Lainnya'
    ? manualForm.value.custom_kecamatan.trim()
    : manualForm.value.kecamatan;

  if (saveMode === 'final') {
    if (manualForm.value.package_type === 'Paket Satuan') {
      const minReq = activeManualFormBuilder.value?.minItems || 1;
      const filledCount = manualSingleDelegates.value.filter(d => d.name && d.name.trim().length > 0).length;
      if (filledCount < minReq) {
        toast.warning(`Cabang ${activeManualCompetition.value?.title || ''} membutuhkan minimal ${minReq} siswa (baru terisi ${filledCount}). Gunakan tombol "Simpan Saja (Draf / Diisi Nanti)" jika pengisian delegasi ditunda.`, 'Delegasi Belum Lengkap');
        return;
      }
    } else {
      let totalFilled = 0;
      manualForm.value.competition_ids.forEach(cId => {
        totalFilled += getAllInFilledCount(cId);
      });
      if (totalFilled === 0) {
        const confirmDraft = await confirmDialog({
          title: 'Belum Ada Nama Peserta Diisi',
          message: 'Anda memilih Simpan Final tetapi belum ada siswa delegasi yang diisi pada cabang lomba manapun. Apakah ingin menyimpan pendaftaran ini sebagai Draf (Diisi Nanti)?',
          type: 'warning',
          confirmText: 'Ya, Simpan Saja (Draf)',
          cancelText: 'Lengkapi Sekarang',
        });
        if (confirmDraft) {
          saveMode = 'draft';
        } else {
          return;
        }
      }
    }
  }

  const isDraft = (saveMode === 'draft');
  const confirmMsg = isDraft
    ? `Simpan data pendaftaran kontingen ${manualForm.value.school_name}? Pendaftaran akan berstatus [Menunggu Pengisian Peserta] (belum final dan belum bisa diverifikasi sampai data delegasi diisi lengkap).`
    : `Simpan data kontingen ${manualForm.value.school_name} secara FINAL dengan total tagihan [${formatRupiah(manualTotalCalculatedFee.value)}] dan status pembayaran [${manualForm.value.payment_status}]?`;

  const confirmed = await confirmDialog({
    title: isDraft ? 'Simpan Pendaftaran (Draf / Diisi Nanti)' : 'Simpan Final Pendaftaran Meja Panitia',
    message: confirmMsg,
    type: isDraft ? 'warning' : 'primary',
    confirmText: isDraft ? 'Ya, Simpan Saja (Draf)' : 'Ya, Simpan Final & Selesai',
    cancelText: 'Cek Kembali',
  });

  if (!confirmed) return;

  isSubmittingManual.value = true;

  const filteredSingleDelegates = manualSingleDelegates.value.filter(d => d.name && d.name.trim().length > 0);
  const filteredAllInDelegates = {};
  if (manualForm.value.package_type === 'Paket All-In') {
    manualForm.value.competition_ids.forEach(cId => {
      const list = manualAllInDelegates.value[cId] || [];
      filteredAllInDelegates[cId] = list.filter(d => d.name && d.name.trim().length > 0);
    });
  }

  router.post('/admin/pendaftar/manual-store', {
    school_name: manualForm.value.school_name,
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
    save_mode: saveMode,
    target_action: isDraft ? 'save_only' : 'manage_delegates',
    students: filteredSingleDelegates,
    delegates_by_comp: filteredAllInDelegates,
  }, {
    onSuccess: (pageRes) => {
      isSubmittingManual.value = false;
      closeModal();

      const newlyAll = pageRes.props?.flash?.newly_registered_all || [];
      const newlyFirst = pageRes.props?.flash?.newly_registered;

      if (isDraft) {
        toast.warning(`Kontingen ${manualForm.value.school_name} berhasil disimpan dengan status [Menunggu Pengisian Peserta]. Pendaftaran belum final dan belum bisa diverifikasi sebelum peserta diisi.`, 'Pendaftaran Draf Disimpan');
      } else {
        toast.success(`Pendaftaran kontingen ${manualForm.value.school_name} berhasil disimpan resmi dan siap diverifikasi!`, 'Pendaftaran Berhasil');
      }

      emit('saved', { pageRes, saveMode, school_name: manualForm.value.school_name });
    },
    onError: (errs) => {
      isSubmittingManual.value = false;
      const msg = typeof errs === 'object' ? (Object.values(errs)[0] || 'Gagal menyimpan pendaftaran.') : 'Gagal menyimpan pendaftaran.';
      toast.error(msg, 'Terjadi Kesalahan');
    }
  });
}
</script>

<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.65); z-index: 1055;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content shadow-lg border-0">
        <!-- Modal Header -->
        <div class="modal-header py-2.5 px-3 px-md-4 border-bottom bg-white d-flex align-items-center justify-content-between">
          <div>
            <h6 class="modal-title fw-bold mb-0 text-dark fs-14 d-flex align-items-center gap-2">
              <i class="ri-user-add-line text-primary fs-18"></i>
              <span>Input Manual Pendaftaran &amp; Delegasi (Meja Panitia)</span>
            </h6>
            <div class="fs-11 text-muted mt-0.5 d-flex align-items-center gap-2">
              <span class="badge bg-primary text-white fs-10 px-2 py-0.5 rounded-pill">
                Langkah {{ manualStep }} dari 3
              </span>
              <span class="fw-medium text-dark">
                {{ manualStep === 1 ? '1. Paket Lomba & Identitas Sekolah' : (manualStep === 2 ? '2. Pengisian Siswa Delegasi Peserta' : '3. Pembayaran & Simpan Pendaftaran') }}
              </span>
            </div>
          </div>
          <button type="button" class="btn-close" @click="closeModal"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body p-3 p-md-4 bg-light">
          <!-- Top Step Navigation Bar Indicator (3 Langkah Jelas) -->
          <div class="card border-0 shadow-xs mb-3 bg-white">
            <div class="card-body p-2 px-3">
              <div class="row g-2 text-center fs-11">
                <!-- Step 1: Paket & Identitas Sekolah -->
                <div class="col-4">
                  <div 
                    class="p-2 rounded-2 fw-bold d-flex align-items-center justify-content-center gap-1.5 cursor-pointer transition-all"
                    :class="manualStep === 1 ? 'bg-primary text-white shadow-xs' : (manualStep > 1 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border')"
                    @click="goToManualStep(1)"
                  >
                    <span 
                      class="badge rounded-circle p-1"
                      :class="manualStep === 1 ? 'bg-white text-primary' : (manualStep > 1 ? 'bg-primary text-white' : 'bg-secondary text-white')"
                      style="width: 20px; height: 20px; line-height: 12px;"
                    >1</span>
                    <span class="d-none d-sm-inline">1. Paket &amp; Data Sekolah</span>
                    <span class="d-inline d-sm-none">1. Paket/Sekolah</span>
                  </div>
                </div>

                <!-- Step 2: Pengisian Siswa Delegasi -->
                <div class="col-4">
                  <div 
                    class="p-2 rounded-2 fw-bold d-flex align-items-center justify-content-center gap-1.5 cursor-pointer transition-all"
                    :class="manualStep === 2 ? 'bg-primary text-white shadow-xs' : (manualStep > 2 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border')"
                    @click="goToManualStep(2)"
                  >
                    <span 
                      class="badge rounded-circle p-1"
                      :class="manualStep === 2 ? 'bg-white text-primary' : (manualStep > 2 ? 'bg-primary text-white' : 'bg-secondary text-white')"
                      style="width: 20px; height: 20px; line-height: 12px;"
                    >2</span>
                    <span class="d-none d-sm-inline">2. Pengisian Siswa Delegasi</span>
                    <span class="d-inline d-sm-none">2. Delegasi</span>
                  </div>
                </div>

                <!-- Step 3: Pembayaran & Simpan -->
                <div class="col-4">
                  <div 
                    class="p-2 rounded-2 fw-bold d-flex align-items-center justify-content-center gap-1.5 cursor-pointer transition-all"
                    :class="manualStep === 3 ? 'bg-primary text-white shadow-xs' : 'bg-light text-muted border'"
                    @click="goToManualStep(3)"
                  >
                    <span 
                      class="badge rounded-circle p-1"
                      :class="manualStep === 3 ? 'bg-white text-primary' : 'bg-secondary text-white'"
                      style="width: 20px; height: 20px; line-height: 12px;"
                    >3</span>
                    <span class="d-none d-sm-inline">3. Pembayaran &amp; Simpan</span>
                    <span class="d-inline d-sm-none">3. Simpan</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================= -->
          <!-- LANGKAH 1: KIRI = PAKET & CABANG, KANAN = IDENTITAS SEKOLAH   -->
          <!-- ============================================================= -->
          <div v-if="manualStep === 1">
            <div class="row g-3">
              <!-- SEBELAH KIRI: 1. PILIHAN SKEMA PAKET & CABANG LOMBA -->
              <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100 bg-white">
                  <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                      <i class="ri-medal-fill text-warning"></i> 1. Pilihan Skema Paket &amp; Cabang Lomba
                    </span>
                    <span class="badge bg-primary text-white fs-10 px-2 py-0.5 rounded-pill">Kolom Kiri</span>
                  </div>

                  <div class="card-body p-3">
                    <!-- Switch Paket Satuan vs Paket All-In -->
                    <div class="row g-2 mb-3">
                      <div class="col-6">
                        <div 
                          class="p-2.5 rounded-3 border text-center cursor-pointer transition-all"
                          :class="manualForm.package_type === 'Paket Satuan' ? 'border-primary bg-primary-subtle text-primary fw-bold shadow-sm' : 'bg-light text-muted'"
                          @click="manualForm.package_type = 'Paket Satuan'"
                        >
                          <i class="ri-medal-line fs-20 d-block mb-1"></i>
                          <span>Paket Satuan</span>
                          <span class="d-block fs-10 text-muted">Tarif per cabang lomba</span>
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
                          <span class="d-block fs-11 text-success fw-bold">{{ formatRupiah(manualForm.package_price || packageAllinPrice) }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- IF PAKET SATUAN -->
                    <div v-if="manualForm.package_type === 'Paket Satuan'" class="p-3 rounded-3 border bg-light bg-opacity-50">
                      <label class="form-label fs-11 fw-bold text-dark d-flex align-items-center justify-content-between">
                        <span>Pilih Cabang Lomba <span class="text-danger">*</span></span>
                        <span class="badge bg-primary text-white fs-10">Tarif Resmi</span>
                      </label>
                      <CustomSelect 
                        v-model="manualForm.competition_id" 
                        :options="competitionSelectOptions" 
                        size="sm"
                        searchable
                        search-placeholder="Cari cabang lomba..."
                        placeholder="-- Silakan Pilih Cabang Lomba --"
                      />

                      <!-- Ringkasan Info Form Builder Cabang Terpilih -->
                      <div v-if="activeManualCompetition" class="mt-2.5 p-2.5 rounded-2 bg-white border fs-11">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                          <span class="fw-bold text-dark fs-12">{{ activeManualCompetition.title }}</span>
                          <span class="badge bg-success-subtle text-success fs-10">
                            Tarif: {{ formatRupiah(activeManualCompetition.price || activeManualCompetition.fee) }}
                          </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted fs-11">
                          <span><i class="ri-group-line me-0.5"></i> Kuota: <strong>{{ activeManualFormBuilder?.minItems }} s.d {{ activeManualFormBuilder?.maxItems }} Siswa</strong></span>
                          <span>•</span>
                          <span>Tipe: <strong>{{ activeManualFormBuilder?.isTeam ? 'Beregu / Tim' : 'Individu' }}</strong></span>
                        </div>
                      </div>
                    </div>

                    <!-- IF PAKET ALL-IN -->
                    <div v-else class="p-3 rounded-3 border border-warning-subtle bg-warning-subtle bg-opacity-10">
                      <!-- Banner Keterangan Biaya Paket Komplit (All-In) -->
                      <div class="p-2.5 rounded-2 bg-white border border-warning-subtle mb-2.5 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-xs">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar-xs rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                            <i class="ri-vip-crown-fill fs-18 text-warning"></i>
                          </div>
                          <div>
                            <span class="fs-10 text-muted d-block text-uppercase fw-bold">Biaya Resmi Paket Komplit</span>
                            <span class="fs-15 fw-bold text-success">{{ formatRupiah(manualForm.package_price || packageAllinPrice) }}</span>
                          </div>
                        </div>
                        <div class="text-end">
                          <span class="badge bg-warning text-dark fs-10 px-2 py-0.5 rounded-pill fw-bold d-block mb-0.5">
                            <i class="ri-sparkling-fill me-0.5"></i> Flat Seluruh Cabang
                          </span>
                          <span class="fs-10 text-muted">Bebas ikuti seluruh cabang lomba</span>
                        </div>
                      </div>

                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fs-11 fw-bold text-dark">Pilih Cabang Lomba yang Diikuti:</span>
                        <div class="d-flex gap-1">
                          <button type="button" class="btn btn-xs btn-outline-dark py-0.5 px-2 fs-10" @click="selectAllCompetitions">Pilih Semua</button>
                          <button type="button" class="btn btn-xs btn-outline-secondary py-0.5 px-2 fs-10" @click="deselectAllCompetitions">Reset</button>
                        </div>
                      </div>

                      <div class="row g-1.5 mb-2" style="max-height: 220px; overflow-y: auto;">
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

                      <div class="d-flex align-items-center justify-content-between bg-white p-2 rounded border fs-11">
                        <span class="text-muted">Total Cabang Dipilih:</span>
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge bg-success text-white fs-11">{{ manualForm.competition_ids.length }} Cabang Lomba</span>
                          <span class="text-muted">•</span>
                          <strong class="text-success">{{ formatRupiah(manualTotalCalculatedFee) }}</strong>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- SEBELAH KANAN: 2. IDENTITAS SEKOLAH / MADRASAH & KONTAK PEMBINA -->
              <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100 bg-white">
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
                      <span class="fs-10 text-muted mt-1 d-block">Memilih master sekolah akan otomatis mengisi nama lembaga, kecamatan, NPSN, dan alamat.</span>
                    </div>

                    <!-- Input Nama Asal Sekolah / Madrasah (Mengikuti Form Builder) -->
                    <div class="mb-3">
                      <label class="form-label fs-11 fw-bold text-dark">
                        {{ getManualFieldLabel('school_name', 'Nama Asal Sekolah / Madrasah') }} <span class="text-danger">*</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="manualForm.school_name" 
                        class="form-control form-control-sm fs-12"
                        :placeholder="getManualFieldPlaceholder('school_name', 'Contoh: MTs Negeri 1 Jember / SMPN 1 Balung')" 
                        @input="onSchoolNameInput"
                        required
                      />
                      <span v-if="getManualFieldHelpText('school_name')" class="fs-10 text-muted mt-1 d-block">
                        {{ getManualFieldHelpText('school_name') }}
                      </span>
                    </div>

                    <!-- Kecamatan Asal Sekolah & NPSN (Mengikuti Form Builder, Tanpa Field Jenjang) -->
                    <div class="row g-2 mb-3">
                      <div class="col-12 col-md-7">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ getManualFieldLabel('school_district', 'Kecamatan Asal Sekolah') }} <span class="text-danger">*</span>
                        </label>
                        <CustomSelect 
                          v-model="manualForm.kecamatan" 
                          :options="kecamatanOptions" 
                          size="sm"
                          searchable
                          search-placeholder="Ketik nama kecamatan..."
                          :placeholder="getManualFieldPlaceholder('school_district', 'Pilih Kecamatan Asal Sekolah...')"
                        />
                      </div>
                      <div class="col-12 col-md-5">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ getManualFieldLabel('school_npsn', 'Nomor Pokok Sekolah Nasional (NPSN)') }}
                        </label>
                        <input 
                          type="text" 
                          v-model="manualForm.npsn" 
                          class="form-control form-control-sm fs-12 font-monospace"
                          :placeholder="getManualFieldPlaceholder('school_npsn', 'Contoh: 20581234 (opsional)')" 
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

                    <!-- Alamat Lengkap Sekolah / Madrasah (Mengikuti Form Builder) -->
                    <div class="mb-3">
                      <label class="form-label fs-11 fw-bold text-dark">
                        {{ getManualFieldLabel('school_address', 'Alamat Lengkap Sekolah / Madrasah') }}
                      </label>
                      <textarea 
                        rows="2"
                        v-model="manualForm.alamat" 
                        class="form-control form-control-sm fs-12"
                        :placeholder="getManualFieldPlaceholder('school_address', 'Contoh: Jl. Raya Puger No. 20, Balung Kulon, Kec. Balung')" 
                      ></textarea>
                      <span v-if="getManualFieldHelpText('school_address')" class="fs-10 text-muted mt-1 d-block">
                        {{ getManualFieldHelpText('school_address') }}
                      </span>
                    </div>

                    <!-- Input Nama Tim / Skuad (Dinamis Jika Cabang Lomba Beregu di Paket Satuan) -->
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

                    <!-- Kontak Pembina / Pendamping Resmi (Mengikuti Form Builder) -->
                    <span class="fs-11 fw-bold text-dark text-uppercase d-block mb-2">
                        <i class="ri-user-star-line text-success"></i> Kontak Pembina / Pendamping Resmi
                    </span>

                    <div class="mb-2">
                      <label class="form-label fs-11 fw-bold text-dark">
                        {{ getManualFieldLabel('mentor_name', 'Nama Guru Pembina / Pendamping') }} <span class="text-danger">*</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="manualForm.mentor_name" 
                        class="form-control form-control-sm fs-12"
                        :placeholder="getManualFieldPlaceholder('mentor_name', 'Contoh: Ustadz Ahmad Fauzi, S.Pd')" 
                        required
                      />
                      <span v-if="getManualFieldHelpText('mentor_name')" class="fs-10 text-muted mt-1 d-block">
                        {{ getManualFieldHelpText('mentor_name') }}
                      </span>
                    </div>

                    <div class="row g-2">
                      <div class="col-7">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ getManualFieldLabel('mentor_wa', 'Nomor WhatsApp Pembina') }} <span class="text-danger">*</span>
                        </label>
                        <input 
                          type="text" 
                          v-model="manualForm.mentor_wa" 
                          class="form-control form-control-sm fs-12 font-monospace"
                          :placeholder="getManualFieldPlaceholder('mentor_wa', '08xxxxxxxxxx')" 
                          required
                        />
                      </div>
                      <div class="col-5">
                        <label class="form-label fs-11 fw-bold text-dark">
                          {{ getManualFieldLabel('mentor_email', 'Alamat Email (Opsional)') }}
                        </label>
                        <input 
                          type="email" 
                          v-model="manualForm.mentor_email" 
                          class="form-control form-control-sm fs-12"
                          :placeholder="getManualFieldPlaceholder('mentor_email', 'email@sekolah.sch.id')" 
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================= -->
          <!-- LANGKAH 2: PENGISIAN DELEGASI SISWA PESERTA                   -->
          <!-- ============================================================= -->
          <div v-if="manualStep === 2">
            <!-- Ringkasan Singkat Kontingen di Atas Form Delegasi -->
            <div class="card border-0 shadow-sm mb-3 bg-white">
              <div class="card-body p-3">
                <div class="row g-2 fs-12 align-items-center">
                  <div class="col-12 col-md-5">
                    <span class="text-muted fs-11 d-block">Kontingen Sekolah:</span>
                    <strong class="text-dark fs-13">{{ manualForm.school_name }}</strong>
                    <span class="badge ms-1.5" :class="manualForm.package_type === 'Paket All-In' ? 'bg-warning text-dark' : 'bg-primary text-white'">
                      {{ manualForm.package_type }}
                    </span>
                  </div>
                  <div class="col-12 col-md-4">
                    <span class="text-muted fs-11 d-block">Pembina / WhatsApp:</span>
                    <strong class="text-dark">{{ manualForm.mentor_name }}</strong>
                    <span class="text-muted font-monospace ms-1">({{ manualForm.mentor_wa }})</span>
                  </div>
                  <div class="col-12 col-md-3 text-md-end">
                    <span class="text-muted fs-11 d-block">Tagihan Pembayaran:</span>
                    <strong class="text-success fs-13">{{ formatRupiah(manualTotalCalculatedFee) }}</strong>
                    <span class="badge bg-success-subtle text-success fs-10 ms-1">{{ manualForm.payment_status }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- KASUS A: PAKET SATUAN (1 CABANG) -->
            <div v-if="manualForm.package_type === 'Paket Satuan'" class="card shadow-sm border-0">
              <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                <div>
                  <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                    <i class="ri-user-star-line text-warning"></i> Delegasi: {{ activeManualCompetition?.title || 'Cabang Terpilih' }}
                  </span>
                  <div class="fs-10 text-muted mt-0.5">
                    Ketentuan kuota: <strong>{{ activeManualFormBuilder?.minItems }} s.d {{ activeManualFormBuilder?.maxItems }} siswa</strong>
                    ({{ activeManualFormBuilder?.isTeam ? 'Beregu / Tim' : 'Individu' }})
                  </div>
                </div>
                <span 
                  class="badge fs-10 px-2 py-0.5"
                  :class="manualSingleDelegates.filter(d => d.name?.trim()).length >= (activeManualFormBuilder?.minItems || 1) ? 'bg-success text-white' : 'bg-warning text-dark'"
                >
                  Terisi: {{ manualSingleDelegates.filter(d => d.name?.trim()).length }}/{{ activeManualFormBuilder?.minItems || 1 }} Siswa
                </span>
              </div>

              <div class="card-body p-3">
                <!-- Tabel Repeater Delegasi Tunggal -->
                <div class="table-responsive mb-2">
                  <table class="table table-sm table-bordered align-middle mb-0 fs-11">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 32px;" class="text-center">#</th>
                        <th style="min-width: 140px;">
                          {{ activeManualFormBuilder?.sub_fields?.name?.label || 'Nama Siswa' }}
                          <span class="text-danger">*</span>
                        </th>
                        <th v-if="activeManualFormBuilder?.sub_fields?.nisn?.active" style="width: 110px;">
                          {{ activeManualFormBuilder?.sub_fields?.nisn?.label || 'NISN' }}
                          <span class="text-danger" v-if="activeManualFormBuilder?.sub_fields?.nisn?.required">*</span>
                        </th>
                        <th v-if="activeManualFormBuilder?.sub_fields?.gender?.active" style="width: 110px;">
                          {{ activeManualFormBuilder?.sub_fields?.gender?.label || 'L/P' }}
                          <span class="text-danger" v-if="activeManualFormBuilder?.sub_fields?.gender?.required">*</span>
                        </th>
                        <th v-if="activeManualFormBuilder?.sub_fields?.grade?.active" style="width: 110px;">
                          {{ activeManualFormBuilder?.sub_fields?.grade?.label || 'Kelas' }}
                          <span class="text-danger" v-if="activeManualFormBuilder?.sub_fields?.grade?.required">*</span>
                        </th>
                        <th v-if="activeManualFormBuilder?.sub_fields?.birth_date?.active" style="width: 125px;">
                          {{ activeManualFormBuilder?.sub_fields?.birth_date?.label || 'Tgl Lahir' }}
                          <span class="text-danger" v-if="activeManualFormBuilder?.sub_fields?.birth_date?.required">*</span>
                        </th>
                        <th v-if="activeManualFormBuilder?.sub_fields?.custom_id?.active" style="min-width: 130px;">
                          {{ activeManualFormBuilder?.sub_fields?.custom_id?.label || 'No. Dada / ID' }}
                          <span class="text-danger" v-if="activeManualFormBuilder?.sub_fields?.custom_id?.required">*</span>
                        </th>
                        <th v-if="activeManualFormBuilder?.sub_fields?.wa_number?.active" style="width: 120px;">
                          {{ activeManualFormBuilder?.sub_fields?.wa_number?.label || 'No. WA' }}
                          <span class="text-danger" v-if="activeManualFormBuilder?.sub_fields?.wa_number?.required">*</span>
                        </th>
                        <template v-if="activeManualFormBuilder?.sub_fields?.custom_columns?.length">
                          <th 
                            v-for="(col, cIdx) in activeManualFormBuilder.sub_fields.custom_columns" 
                            :key="col.id || col.name || cIdx"
                            style="min-width: 120px;"
                          >
                            {{ col.label || col.name }}
                            <span class="text-danger" v-if="col.required">*</span>
                          </th>
                        </template>
                        <th v-if="activeManualFormBuilder?.sub_fields?.captain_option?.active || activeManualFormBuilder?.isTeam" style="width: 60px;" class="text-center">Kapten</th>
                        <th style="width: 35px;" class="text-center">Hapus</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(st, idx) in manualSingleDelegates" :key="idx">
                        <td class="text-center text-muted fw-bold">{{ idx + 1 }}</td>
                        <td>
                          <input 
                            type="text" 
                            v-model="st.name" 
                            class="form-control form-control-sm fs-11" 
                            :placeholder="activeManualFormBuilder?.sub_fields?.name?.placeholder || 'Nama lengkap siswa...'" 
                          />
                        </td>
                        <td v-if="activeManualFormBuilder?.sub_fields?.nisn?.active">
                          <input 
                            type="text" 
                            v-model="st.nisn" 
                            class="form-control form-control-sm fs-11 font-monospace" 
                            :placeholder="activeManualFormBuilder?.sub_fields?.nisn?.placeholder || 'NISN...'" 
                          />
                        </td>
                        <td v-if="activeManualFormBuilder?.sub_fields?.gender?.active">
                          <CustomSelect 
                            v-model="st.gender" 
                            :options="genderOptions" 
                            size="sm"
                            placeholder="Pilih L/P"
                          />
                        </td>
                        <td v-if="activeManualFormBuilder?.sub_fields?.grade?.active">
                          <CustomSelect 
                            v-model="st.grade" 
                            :options="gradeOptions" 
                            size="sm"
                            placeholder="Pilih Kelas"
                          />
                        </td>
                        <td v-if="activeManualFormBuilder?.sub_fields?.birth_date?.active">
                          <IndoDatePicker 
                            v-model="st.birth_date" 
                            placeholder="dd/mm/yyyy"
                            inputClass="form-control form-control-sm fs-11 font-monospace"
                          />
                        </td>
                        <td v-if="activeManualFormBuilder?.sub_fields?.custom_id?.active">
                          <input 
                            type="text" 
                            v-model="st.custom_id" 
                            class="form-control form-control-sm fs-11 font-monospace" 
                            :placeholder="activeManualFormBuilder?.sub_fields?.custom_id?.placeholder || 'ID Peserta / Akun...'" 
                          />
                        </td>
                        <td v-if="activeManualFormBuilder?.sub_fields?.wa_number?.active">
                          <input 
                            type="text" 
                            v-model="st.wa_number" 
                            class="form-control form-control-sm fs-11 font-monospace" 
                            :placeholder="activeManualFormBuilder?.sub_fields?.wa_number?.placeholder || '08xxxxxxxxxx'" 
                          />
                        </td>
                        <template v-if="activeManualFormBuilder?.sub_fields?.custom_columns?.length">
                          <td 
                            v-for="(col, cIdx) in activeManualFormBuilder.sub_fields.custom_columns" 
                            :key="col.id || col.name || cIdx"
                          >
                            <CustomSelect 
                              v-if="col.type === 'select'"
                              v-model="(st.custom_columns = st.custom_columns || {})[col.name || col.label]"
                              :options="(col.options || []).map(opt => typeof opt === 'object' ? opt : { value: opt, label: opt })"
                              size="sm"
                              :placeholder="col.placeholder || col.label || 'Pilih...'"
                            />
                            <input 
                              v-else
                              :type="col.type === 'number' ? 'number' : 'text'"
                              v-model="(st.custom_columns = st.custom_columns || {})[col.name || col.label]"
                              class="form-control form-control-sm fs-11"
                              :placeholder="col.placeholder || col.label || 'Isi data...'" 
                            />
                          </td>
                        </template>
                        <td v-if="activeManualFormBuilder?.sub_fields?.captain_option?.active || activeManualFormBuilder?.isTeam" class="text-center">
                          <button 
                            type="button" 
                            class="btn btn-xs py-0.5 px-1.5"
                            :class="st.is_captain ? 'btn-primary text-white' : 'btn-light border text-muted'"
                            @click="setSingleCaptain(idx)"
                            :title="st.is_captain ? 'Kapten terpilih' : 'Set sebagai kapten'"
                          >
                            <i :class="st.is_captain ? 'ri-shield-star-fill' : 'ri-shield-star-line'"></i>
                          </button>
                        </td>
                        <td class="text-center">
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-danger py-0.5 px-1"
                            @click="removeSingleDelegate(idx)"
                            title="Hapus baris"
                          >
                            <i class="ri-delete-bin-line"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-1">
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-primary py-1 px-2.5 fs-11 d-inline-flex align-items-center gap-1 shadow-xs"
                    @click="addSingleDelegate"
                    :disabled="manualSingleDelegates.length >= (activeManualFormBuilder?.maxItems || 20)"
                  >
                    <i class="ri-user-add-line"></i>
                    <span>Tambah Baris Siswa</span>
                  </button>
                  <span class="fs-10 text-muted">Maksimal: {{ activeManualFormBuilder?.maxItems || 20 }} siswa</span>
                </div>
              </div>
            </div>

            <!-- KASUS B: PAKET KOMPLIT ALL-IN (MULTI-CABANG TAB MANAGER) -->
            <div v-else class="card shadow-sm border-0">
              <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                  <i class="ri-vip-crown-fill text-warning"></i> Pengelola Delegasi Multi-Cabang (Paket All-In)
                </span>
                <span class="badge bg-warning text-dark fs-10 px-2 py-0.5 rounded-pill fw-bold">
                  {{ manualForm.competition_ids.length }} Cabang Terdaftar
                </span>
              </div>

              <div class="card-body p-3">
                <!-- Tab Bar Horizontal Antar Cabang Lomba -->
                <div class="mb-3">
                  <div class="fs-11 fw-semibold text-muted mb-1.5">Pilih Cabang Lomba untuk Mengisi Siswa Delegasi:</div>
                  <div class="d-flex align-items-center gap-1.5 overflow-x-auto pb-1" style="white-space: nowrap;">
                    <button 
                      v-for="cId in manualForm.competition_ids" 
                      :key="cId"
                      type="button"
                      class="btn btn-xs py-1.5 px-3 rounded-pill d-inline-flex align-items-center gap-1.5 transition-all text-nowrap"
                      :class="activeAllInTab === cId ? 'btn-primary shadow-xs' : 'btn-light border text-dark'"
                      @click="activeAllInTab = cId"
                    >
                      <i class="ri-medal-fill text-warning fs-12"></i>
                      <span>{{ getAllInComp(cId)?.title || ('Lomba #' + cId) }}</span>
                      <span 
                        class="badge rounded-pill fs-9 px-1.5 py-0.5"
                        :class="getAllInFilledCount(cId) >= getAllInBuilder(cId).minItems ? 'bg-success text-white' : (getAllInFilledCount(cId) > 0 ? 'bg-warning text-dark' : (activeAllInTab === cId ? 'bg-white text-primary' : 'bg-secondary text-white'))"
                      >
                        {{ getAllInFilledCount(cId) }}/{{ getAllInBuilder(cId).minItems }}
                      </span>
                    </button>
                  </div>
                </div>

                <!-- Konten Delegasi untuk Cabang yang Sedang Aktif di Tab -->
                <div v-if="activeAllInTab" class="p-3 rounded-2 bg-light border mb-2">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-2">
                    <span class="fw-bold text-dark fs-13">
                      Cabang: {{ getAllInComp(activeAllInTab)?.title }}
                    </span>
                    <span class="fs-10 text-muted">
                      Ketentuan Kuota: <strong>{{ getAllInBuilder(activeAllInTab).minItems }} s.d {{ getAllInBuilder(activeAllInTab).maxItems }} Siswa</strong> 
                      ({{ getAllInBuilder(activeAllInTab).isTeam ? 'Beregu / Tim' : 'Individu' }})
                    </span>
                  </div>

                  <!-- Tabel Repeater untuk Cabang Aktif di All-In -->
                  <div class="table-responsive mb-2">
                    <table class="table table-sm table-bordered align-middle mb-0 fs-11 bg-white">
                      <thead class="table-light">
                        <tr>
                          <th style="width: 32px;" class="text-center">#</th>
                          <th style="min-width: 140px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.name.label || 'Nama Siswa' }}
                            <span class="text-danger">*</span>
                          </th>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.nisn.active" style="width: 110px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.nisn.label || 'NISN' }}
                            <span class="text-danger" v-if="getAllInBuilder(activeAllInTab).sub_fields.nisn.required">*</span>
                          </th>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.gender.active" style="width: 110px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.gender.label || 'L/P' }}
                            <span class="text-danger" v-if="getAllInBuilder(activeAllInTab).sub_fields.gender.required">*</span>
                          </th>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.grade.active" style="width: 110px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.grade.label || 'Kelas' }}
                            <span class="text-danger" v-if="getAllInBuilder(activeAllInTab).sub_fields.grade.required">*</span>
                          </th>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.birth_date.active" style="width: 125px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.birth_date.label || 'Tgl Lahir' }}
                            <span class="text-danger" v-if="getAllInBuilder(activeAllInTab).sub_fields.birth_date.required">*</span>
                          </th>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.custom_id.active" style="min-width: 130px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.custom_id.label || 'No. Dada / ID' }}
                            <span class="text-danger" v-if="getAllInBuilder(activeAllInTab).sub_fields.custom_id.required">*</span>
                          </th>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.wa_number.active" style="width: 120px;">
                            {{ getAllInBuilder(activeAllInTab).sub_fields.wa_number.label || 'No. WA' }}
                            <span class="text-danger" v-if="getAllInBuilder(activeAllInTab).sub_fields.wa_number.required">*</span>
                          </th>
                          <template v-if="getAllInBuilder(activeAllInTab).sub_fields.custom_columns?.length">
                            <th 
                              v-for="(col, cIdx) in getAllInBuilder(activeAllInTab).sub_fields.custom_columns" 
                              :key="col.id || col.name || cIdx"
                              style="min-width: 120px;"
                            >
                              {{ col.label || col.name }}
                              <span class="text-danger" v-if="col.required">*</span>
                            </th>
                          </template>
                          <th v-if="getAllInBuilder(activeAllInTab).sub_fields.captain_option.active || getAllInBuilder(activeAllInTab).isTeam" style="width: 60px;" class="text-center">Kapten</th>
                          <th style="width: 35px;" class="text-center">Hapus</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(st, idx) in (manualAllInDelegates[activeAllInTab] || [])" :key="idx">
                          <td class="text-center text-muted fw-bold">{{ idx + 1 }}</td>
                          <td>
                            <input 
                              type="text" 
                              v-model="st.name" 
                              class="form-control form-control-sm fs-11" 
                              :placeholder="getAllInBuilder(activeAllInTab).sub_fields.name.placeholder || 'Nama siswa...'" 
                            />
                          </td>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.nisn.active">
                            <input 
                              type="text" 
                              v-model="st.nisn" 
                              class="form-control form-control-sm fs-11 font-monospace" 
                              :placeholder="getAllInBuilder(activeAllInTab).sub_fields.nisn.placeholder || 'NISN...'" 
                            />
                          </td>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.gender.active">
                            <CustomSelect 
                              v-model="st.gender" 
                              :options="genderOptions" 
                              size="sm"
                              placeholder="Pilih L/P"
                            />
                          </td>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.grade.active">
                            <CustomSelect 
                              v-model="st.grade" 
                              :options="gradeOptions" 
                              size="sm"
                              placeholder="Pilih Kelas"
                            />
                          </td>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.birth_date.active">
                            <IndoDatePicker 
                              v-model="st.birth_date" 
                              placeholder="dd/mm/yyyy"
                              inputClass="form-control form-control-sm fs-11 font-monospace"
                            />
                          </td>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.custom_id.active">
                            <input 
                              type="text" 
                              v-model="st.custom_id" 
                              class="form-control form-control-sm fs-11 font-monospace" 
                              :placeholder="getAllInBuilder(activeAllInTab).sub_fields.custom_id.placeholder || 'ID Peserta / Akun...'" 
                            />
                          </td>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.wa_number.active">
                            <input 
                              type="text" 
                              v-model="st.wa_number" 
                              class="form-control form-control-sm fs-11 font-monospace" 
                              :placeholder="getAllInBuilder(activeAllInTab).sub_fields.wa_number.placeholder || '08xxxxxxxxxx'" 
                            />
                          </td>
                          <template v-if="getAllInBuilder(activeAllInTab).sub_fields.custom_columns?.length">
                            <td 
                              v-for="(col, cIdx) in getAllInBuilder(activeAllInTab).sub_fields.custom_columns" 
                              :key="col.id || col.name || cIdx"
                            >
                              <CustomSelect 
                                v-if="col.type === 'select'"
                                v-model="(st.custom_columns = st.custom_columns || {})[col.name || col.label]"
                                :options="(col.options || []).map(opt => typeof opt === 'object' ? opt : { value: opt, label: opt })"
                                size="sm"
                                :placeholder="col.placeholder || col.label || 'Pilih...'"
                              />
                              <input 
                                v-else
                                :type="col.type === 'number' ? 'number' : 'text'"
                                v-model="(st.custom_columns = st.custom_columns || {})[col.name || col.label]"
                                class="form-control form-control-sm fs-11"
                                :placeholder="col.placeholder || col.label || 'Isi data...'" 
                              />
                            </td>
                          </template>
                          <td v-if="getAllInBuilder(activeAllInTab).sub_fields.captain_option.active || getAllInBuilder(activeAllInTab).isTeam" class="text-center">
                            <button 
                              type="button" 
                              class="btn btn-xs py-0.5 px-1.5"
                              :class="st.is_captain ? 'btn-primary text-white' : 'btn-light border text-muted'"
                              @click="setAllInCaptain(activeAllInTab, idx)"
                              :title="st.is_captain ? 'Kapten terpilih' : 'Set kapten'"
                            >
                              <i :class="st.is_captain ? 'ri-shield-star-fill' : 'ri-shield-star-line'"></i>
                            </button>
                          </td>
                          <td class="text-center">
                            <button 
                              type="button" 
                              class="btn btn-xs btn-outline-danger py-0.5 px-1"
                              @click="removeAllInDelegate(activeAllInTab, idx)"
                              title="Hapus baris"
                            >
                              <i class="ri-delete-bin-line"></i>
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="d-flex align-items-center justify-content-between pt-1">
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-primary py-1 px-2.5 fs-11 d-inline-flex align-items-center gap-1 shadow-xs"
                      @click="addAllInDelegate(activeAllInTab)"
                      :disabled="(manualAllInDelegates[activeAllInTab] || []).length >= getAllInBuilder(activeAllInTab).maxItems"
                    >
                      <i class="ri-user-add-line"></i>
                      <span>Tambah Baris Siswa ({{ getAllInComp(activeAllInTab)?.title }})</span>
                    </button>
                    <span class="fs-10 text-muted">
                      Terisi: <strong>{{ getAllInFilledCount(activeAllInTab) }}</strong> / {{ getAllInBuilder(activeAllInTab).minItems }} Siswa
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ============================================================= -->
          <!-- LANGKAH 3: ADMINISTRASI PEMBAYARAN & KONFIRMASI SIMPAN        -->
          <!-- ============================================================= -->
          <div v-if="manualStep === 3">
            <div class="row g-3">
              <!-- KOLOM KIRI: RINGKASAN LENGKAP KONTINGEN & DELEGASI -->
              <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100 bg-white">
                  <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                      <i class="ri-survey-line text-primary"></i> Ringkasan Kontingen &amp; Delegasi
                    </span>
                    <span class="badge" :class="manualForm.package_type === 'Paket All-In' ? 'bg-warning text-dark' : 'bg-primary text-white'">
                      {{ manualForm.package_type }}
                    </span>
                  </div>

                  <div class="card-body p-3">
                    <!-- Profil Lembaga & Kontak -->
                    <div class="p-2.5 rounded-2 bg-light border mb-3 fs-11">
                      <div class="mb-1">
                        <strong class="text-dark fs-13">{{ manualForm.school_name }}</strong>
                      </div>
                      <div class="text-muted d-flex flex-wrap gap-x-3 gap-y-1 mt-1">
                        <span><i class="ri-map-pin-2-line me-0.5"></i> Kec. {{ manualForm.kecamatan === 'Lainnya' ? manualForm.custom_kecamatan : manualForm.kecamatan }}</span>
                        <span v-if="manualForm.npsn"><i class="ri-barcode-line me-0.5"></i> NPSN: {{ manualForm.npsn }}</span>
                      </div>
                      <div v-if="manualForm.alamat" class="text-muted mt-1 text-truncate">
                        <i class="ri-building-line me-0.5"></i> {{ manualForm.alamat }}
                      </div>
                      <hr class="my-1.5 border-secondary-subtle" />
                      <div class="d-flex align-items-center justify-content-between flex-wrap gap-1">
                        <span><i class="ri-user-star-line me-0.5 text-success"></i> Pembina: <strong>{{ manualForm.mentor_name }}</strong></span>
                        <span class="font-monospace text-dark"><i class="ri-whatsapp-line text-success me-0.5"></i> {{ manualForm.mentor_wa }}</span>
                      </div>
                      <div v-if="manualForm.team_name" class="mt-1 text-primary fw-semibold">
                        <i class="ri-shield-star-line me-0.5"></i> Tim: {{ manualForm.team_name }}
                      </div>
                    </div>

                    <!-- Status Pengisian Delegasi Siswa -->
                    <span class="fs-11 fw-bold text-dark text-uppercase d-block mb-2">
                      <i class="ri-team-line text-warning"></i> Status Delegasi Siswa per Cabang Lomba:
                    </span>

                    <!-- Jika Paket Satuan -->
                    <div v-if="manualForm.package_type === 'Paket Satuan'" class="p-2.5 rounded-2 border bg-white fs-11">
                      <div class="d-flex align-items-center justify-content-between mb-1.5">
                        <span class="fw-bold text-dark">{{ activeManualCompetition?.title || 'Cabang Terpilih' }}</span>
                        <span 
                          class="badge fs-10"
                          :class="manualSingleDelegates.filter(d => d.name?.trim()).length >= (activeManualFormBuilder?.minItems || 1) ? 'bg-success text-white' : 'bg-warning text-dark'"
                        >
                          {{ manualSingleDelegates.filter(d => d.name?.trim()).length }}/{{ activeManualFormBuilder?.minItems || 1 }} Siswa
                        </span>
                      </div>
                      <div class="d-flex flex-wrap gap-1 mt-1" v-if="manualSingleDelegates.filter(d => d.name?.trim()).length > 0">
                        <span 
                          v-for="(st, sIdx) in manualSingleDelegates.filter(d => d.name?.trim())" 
                          :key="sIdx"
                          class="badge bg-light text-dark border fs-10 px-2 py-0.5"
                        >
                          <i class="ri-shield-star-fill text-warning me-0.5" v-if="st.is_captain"></i>
                          {{ st.name }} (Kls {{ st.grade }})
                        </span>
                      </div>
                      <div v-else class="text-warning fs-11 mt-1">
                        <i class="ri-time-line me-1"></i> Belum ada nama siswa diisi (pengisian dapat ditunda).
                      </div>
                    </div>

                    <!-- Jika Paket All-In -->
                    <div v-else class="d-flex flex-column gap-1.5" style="max-height: 220px; overflow-y: auto;">
                      <div 
                        v-for="cId in manualForm.competition_ids" 
                        :key="cId"
                        class="p-2 rounded-2 border bg-white d-flex align-items-center justify-content-between fs-11"
                      >
                        <div class="d-flex align-items-center gap-1.5">
                          <i class="ri-medal-fill text-warning fs-13"></i>
                          <span class="text-dark fw-semibold">{{ getAllInComp(cId)?.title || ('Lomba #' + cId) }}</span>
                        </div>
                        <span 
                          class="badge fs-10"
                          :class="getAllInFilledCount(cId) >= getAllInBuilder(cId).minItems ? 'bg-success text-white' : (getAllInFilledCount(cId) > 0 ? 'bg-warning text-dark' : 'bg-secondary text-white')"
                        >
                          {{ getAllInFilledCount(cId) }}/{{ getAllInBuilder(cId).minItems }} Siswa
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- KOLOM KANAN: ADMINISTRASI PEMBAYARAN MEJA PANITIA -->
              <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 h-100 bg-white">
                  <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark fs-12 text-uppercase d-flex align-items-center gap-1.5">
                      <i class="ri-money-dollar-circle-line text-success"></i> Administrasi Pembayaran Meja Panitia
                    </span>
                    <span class="badge bg-success-subtle text-success fs-10 px-2 py-0.5 rounded-pill">Kasir On-Site</span>
                  </div>

                  <div class="card-body p-3">
                    <!-- Total Tagihan -->
                    <div class="p-3 rounded-3 border border-success-subtle bg-success-subtle bg-opacity-10 mb-3 text-center">
                      <span class="fs-11 text-muted text-uppercase fw-semibold d-block mb-1">Total Tagihan Pendaftaran</span>
                      <div class="fs-22 fw-bold text-success">{{ formatRupiah(manualTotalCalculatedFee) }}</div>
                      <span class="badge bg-success text-white fs-10 mt-1">
                        {{ manualForm.package_type === 'Paket All-In' ? 'Tarif Paket Komplit (All-In)' : 'Tarif Satuan Cabang Lomba' }}
                      </span>
                    </div>

                    <!-- Penyesuaian / Override Tarif jika ada dispensasi -->
                    <div class="row g-2 mb-3">
                      <div class="col-12" v-if="manualForm.package_type === 'Paket Satuan'">
                        <label class="form-label fs-11 fw-bold text-dark d-flex align-items-center justify-content-between mb-1">
                          <span>Biaya Cabang Lomba (Rp)</span>
                          <span class="text-muted fs-10">Bisa di-override jika ada diskon</span>
                        </label>
                        <input 
                          type="number" 
                          v-model.number="manualForm.custom_fee" 
                          class="form-control form-control-sm fs-12 font-monospace"
                          :placeholder="`Tarif default: ${activeManualCompetition?.price || activeManualCompetition?.fee || 0}`" 
                        />
                      </div>
                      <div class="col-12" v-else>
                        <label class="form-label fs-11 fw-bold text-dark d-flex align-items-center justify-content-between mb-1">
                          <span>Tarif Paket All-In (Rp)</span>
                          <span class="text-muted fs-10">Bisa disesuaikan jika perlu</span>
                        </label>
                        <input 
                          type="number" 
                          v-model.number="manualForm.package_price" 
                          class="form-control form-control-sm fs-12 font-monospace"
                          :placeholder="`Tarif default: ${packageAllinPrice}`" 
                        />
                      </div>
                    </div>

                    <!-- Status Pembayaran -->
                    <div class="mb-3">
                      <label class="form-label fs-11 fw-bold text-dark">
                        Status Pembayaran <span class="text-danger">*</span>
                      </label>
                      <CustomSelect 
                        v-model="manualForm.payment_status" 
                        :options="manualPaymentStatusOptions" 
                        size="sm"
                      />
                    </div>

                    <!-- Metode Pembayaran -->
                    <div class="mb-3">
                      <label class="form-label fs-11 fw-bold text-dark">
                        Metode Pembayaran <span class="text-danger">*</span>
                      </label>
                      <CustomSelect 
                        v-model="manualForm.payment_method" 
                        :options="manualPaymentMethodOptions" 
                        size="sm"
                      />
                    </div>

                    <!-- Catatan Kwitansi -->
                    <div class="mb-2">
                      <label class="form-label fs-11 fw-bold text-dark">
                        Catatan / No. Kwitansi Meja Panitia (Opsional)
                      </label>
                      <textarea 
                        v-model="manualForm.notes" 
                        class="form-control form-control-sm fs-12"
                        rows="2"
                        placeholder="Nomor kwitansi fisik atau catatan khusus meja panitia..."
                      ></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Callout Penjelasan Simpan Saja (Draf) vs Simpan Final di Langkah 3 -->
            <div class="p-2.5 rounded-3 border border-warning-subtle bg-warning-subtle bg-opacity-20 d-flex align-items-start gap-2.5 fs-11 text-dark mt-3">
              <i class="ri-information-fill fs-18 text-warning flex-shrink-0 mt-0.5"></i>
              <div>
                <strong>Perhatian Meja Panitia Mengenai Pilihan Simpan di Langkah 3:</strong>
                <ul class="mb-0 ps-3 mt-1 text-muted">
                  <li>
                    <strong>Simpan Saja (Draf / Diisi Nanti)</strong>: Menyimpan data kontingen &amp; pembayaran di meja panitia dengan status <em>Menunggu Pengisian Peserta</em>. Pendaftaran <strong>belum final dan belum bisa diverifikasi</strong> sampai delegasi siswa dilengkapi.
                  </li>
                  <li>
                    <strong>Simpan Final &amp; Selesai</strong>: Menyimpan seluruh kontingen dan data siswa delegasi secara lengkap &amp; final, sehingga langsung siap untuk diverifikasi.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer bg-white border-top py-2.5 px-3 px-md-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <!-- FOOTER LANGKAH 1 -->
          <template v-if="manualStep === 1">
            <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Batal</button>

            <button 
              type="button" 
              class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
              @click="goToManualStep(2)"
            >
              <span>Lanjut ke Pengisian Siswa Delegasi (Langkah 2) &rarr;</span>
              <i class="ri-arrow-right-line"></i>
            </button>
          </template>

          <!-- FOOTER LANGKAH 2 -->
          <template v-else-if="manualStep === 2">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5"
              @click="manualStep = 1"
            >
              <i class="ri-arrow-left-line"></i>
              <span>&larr; Kembali ke Data Sekolah (Langkah 1)</span>
            </button>

            <button 
              type="button" 
              class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm"
              @click="goToManualStep(3)"
            >
              <span>Lanjut ke Pembayaran &amp; Simpan (Langkah 3) &rarr;</span>
              <i class="ri-arrow-right-line"></i>
            </button>
          </template>

          <!-- FOOTER LANGKAH 3 (PEMBAYARAN & SIMPAN) -->
          <template v-else-if="manualStep === 3">
            <button 
              type="button" 
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5"
              @click="manualStep = 2"
            >
              <i class="ri-arrow-left-line"></i>
              <span>&larr; Kembali ke Pengisian Siswa Delegasi (Langkah 2)</span>
            </button>

            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Tombol Simpan Saja (Draf / Diisi Nanti) -->
              <button 
                type="button" 
                class="btn btn-sm btn-outline-warning text-dark d-inline-flex align-items-center gap-1.5 shadow-xs"
                :disabled="isSubmittingManual"
                @click="submitManualRegistration('draft')"
                title="Simpan profil kontingen & pembayaran. Status pendaftaran: Menunggu Pengisian Peserta (belum bisa diverifikasi sampai delegasi diisi)."
              >
                <i class="ri-save-line" v-if="!isSubmittingManual"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>💾 Simpan Saja (Draf / Diisi Nanti)</span>
              </button>

              <!-- Tombol Simpan Final & Selesai -->
              <button 
                type="button" 
                class="btn btn-sm btn-success text-white d-inline-flex align-items-center gap-1.5 shadow-sm"
                :disabled="isSubmittingManual"
                @click="submitManualRegistration('final')"
                title="Simpan seluruh profil kontingen dan delegasi siswa secara final. Siap untuk diverifikasi."
              >
                <i class="ri-check-double-line" v-if="!isSubmittingManual"></i>
                <span class="spinner-border spinner-border-sm" v-else></span>
                <span>✅ Simpan Final &amp; Selesai</span>
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.transition-all {
  transition: all 0.2s ease-in-out;
}

.cursor-pointer {
  cursor: pointer;
}

/* Dark Mode WCAG AA Compliance */
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
</style>

