<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';
import { usePermission } from '@/Composables/usePermission';

const props = defineProps({
  registration: {
    type: Object,
    required: true,
  },
  competition: {
    type: Object,
    default: () => ({}),
  },
  formFields: {
    type: Array,
    default: () => [],
  },
  formSettings: {
    type: Object,
    default: () => ({}),
  },
  prevRegistration: {
    type: Object,
    default: null,
  },
  nextRegistration: {
    type: Object,
    default: null,
  },
  relatedRegistrations: {
    type: Array,
    default: () => [],
  },
});

const toast = useToast();
const { can, isSuperAdmin } = usePermission();
const { confirm: confirmDialog } = useConfirmDialog();
const canDelete = computed(() => isSuperAdmin.value || can('registrants.delete'));

async function deleteThisRegistration() {
  const confirmed = await confirmDialog({
    title: 'Hapus Delegasi Pendaftar?',
    message: `Apakah Anda yakin ingin menghapus data delegasi ${props.registration.competition_title} dari ${props.registration.school_name} (Kode: #${props.registration.reg_code})? Data ini akan dihapus permanen dari sistem dan Anda akan dialihkan ke daftar pendaftar.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Permanen',
    cancelText: 'Batal',
  });

  if (!confirmed) return;

  router.delete(`/admin/pendaftar/${props.registration.id}`, {
    onSuccess: () => {
      toast.success(`Data delegasi #${props.registration.reg_code} berhasil dihapus permanen!`, 'Delegasi Dihapus');
    },
    onError: (errs) => {
      const msg = typeof errs === 'object' ? (Object.values(errs)[0] || 'Gagal menghapus pendaftaran.') : 'Gagal menghapus pendaftaran.';
      toast.error(msg, 'Terjadi Kesalahan');
    }
  });
}

// Verification Form State
const verifyForm = useForm({
  registration_status: props.registration.registration_status || 'Menunggu Verifikasi',
  payment_status: props.registration.payment_status || 'Menunggu Verifikasi',
  verification_notes: props.registration.verification_notes || '',
  notes: props.registration.verification_notes || '',
});

const isSubmitting = ref(false);
const isSendingWa = ref(false);
const activeTab = ref('formulir'); // 'formulir' | 'berkas' | 'allin' | 'operasional'

// Modal Lightbox Preview Gambar
const previewImageModal = ref({
  isOpen: false,
  title: '',
  url: '',
});

function openImagePreview(url, title = 'Pratinjau Berkas') {
  if (!url) return;
  previewImageModal.value = {
    isOpen: true,
    title,
    url,
  };
}

function closeImagePreview() {
  previewImageModal.value.isOpen = false;
}

// Helpers Formatter
function formatRupiah(val) {
  const num = parseInt(val, 10);
  if (isNaN(num) || num === 0) return 'Gratis (Rp 0)';
  return 'Rp ' + num.toLocaleString('id-ID');
}

function formatDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes} WIB`;
  } catch (e) {
    return dateStr;
  }
}

function formatDateOnly(dateStr) {
  if (!dateStr) return '-';
  // Jika sudah format dd/mm/yyyy
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return dateStr;
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

function getWaChatUrl(phone, msg = '') {
  if (!phone) return '#';
  let clean = phone.toString().replace(/\D/g, '');
  if (clean.startsWith('0')) clean = '62' + clean.slice(1);
  return `https://wa.me/${clean}?text=${encodeURIComponent(msg)}`;
}

// Helper Deteksi Tipe Berkas
function isImageFile(val) {
  if (!val) return false;
  const url = typeof val === 'object' ? (val.url || '') : String(val);
  return /\.(jpg|jpeg|png|webp|gif|svg)$/i.test(url);
}

function isPdfFile(val) {
  if (!val) return false;
  const url = typeof val === 'object' ? (val.url || '') : String(val);
  return /\.pdf$/i.test(url);
}

function getFileUrl(val) {
  if (!val) return '';
  if (typeof val === 'object') return val.url || '';
  return String(val);
}

function getFileName(val, defaultName = 'Dokumen') {
  if (!val) return defaultName;
  if (typeof val === 'object' && val.name) return val.name;
  const url = String(typeof val === 'object' ? val.url : val);
  const parts = url.split('/');
  return parts[parts.length - 1] || defaultName;
}

function getFileSizeStr(val) {
  if (!val || typeof val !== 'object' || !val.size) return null;
  const bytes = parseInt(val.size, 10);
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function getCustomFileList(val) {
  if (!val) return [];
  if (Array.isArray(val)) return val;
  return [val];
}

// Custom Fields Map dari Registrasi
const customValues = computed(() => {
  return props.registration.custom_fields || {};
});

// Mode tampilan data peserta: 'table' (Tabel Resmi) | 'cards' (Kartu Grid)
const participantViewMode = ref('table');

// Field-field identitas siswa jika dibuat secara individual di form builder
const STUDENT_IDENTITY_FIELDS = [
  'nama_siswa', 'nama_peserta', 'nama_lengkap', 'nama_lengkap_siswa', 'nama', 'student_name',
  'nisn', 'nisn_siswa', 'gender', 'jenis_kelamin', 'kelas', 'kelas_siswa', 'tingkat',
  'tanggal_lahir', 'tgl_lahir', 'birth_date', 'wa_siswa', 'nomor_wa_siswa', 'no_wa_siswa',
  'foto_formal', 'pas_foto', 'foto_siswa', 'photo_formal', 'photo', 'foto_bebas', 'photo_freestyle'
];
const STUDENT_IDENTITY_SET = new Set(STUDENT_IDENTITY_FIELDS);

// Identifikasi Field Repeater Peserta di skema Form Builder (jika ada)
const repeaterFieldDef = computed(() => {
  if (!Array.isArray(props.formFields)) return null;
  return props.formFields.find(f => f.type === 'participant_repeater' || f.type === 'team_members') || null;
});

// 1. Data Delegasi Peserta (Peserta Siswa) Terstruktur & Lengkap
const participantsTableData = computed(() => {
  const result = [];
  const cv = customValues.value || {};

  // Strategi A: Form Builder Repeater Field (participant_repeater, team_members, delegates, dll)
  let repeaterList = null;
  if (repeaterFieldDef.value && Array.isArray(cv[repeaterFieldDef.value.name])) {
    repeaterList = cv[repeaterFieldDef.value.name];
  } else {
    for (const [key, val] of Object.entries(cv)) {
      if (Array.isArray(val) && val.length > 0 && typeof val[0] === 'object' && val[0] !== null) {
        if (val[0].name || val[0].nama || val[0].nama_siswa || val[0].nama_lengkap || val[0].nisn) {
          repeaterList = val;
          break;
        }
      }
    }
  }

  if (repeaterList && repeaterList.length > 0) {
    repeaterList.forEach((m, idx) => {
      const customCols = {};
      if (m.custom_columns && typeof m.custom_columns === 'object') {
        Object.assign(customCols, m.custom_columns);
      }
      const standardKeys = new Set([
        'name', 'nama', 'nama_siswa', 'nama_lengkap', 'nisn', 'gender', 'jenis_kelamin', 
        'grade', 'kelas', 'birth_date', 'tanggal_lahir', 'tgl_lahir', 'custom_id', 'wa_number', 
        'wa', 'phone', 'captain_option', 'is_captain', 'photo_formal', 'photo_freestyle', 
        'photo', 'foto', 'foto_siswa', 'custom_columns'
      ]);
      for (const [k, v] of Object.entries(m)) {
        if (!standardKeys.has(k) && v !== null && v !== undefined && v !== '') {
          customCols[k] = v;
        }
      }

      result.push({
        no: idx + 1,
        name: m.name || m.nama || m.nama_siswa || m.nama_lengkap || `Peserta ${idx + 1}`,
        nisn: m.nisn || '-',
        gender: m.gender || m.jenis_kelamin || '-',
        grade: m.grade || m.kelas || '-',
        birth_date: m.birth_date || m.tanggal_lahir || m.tgl_lahir || null,
        wa: m.wa_number || m.wa || m.phone || null,
        is_captain: Boolean(m.captain_option || m.is_captain || (idx === 0 && repeaterList.length > 1)),
        custom_id: m.custom_id || null,
        photo: getFileUrl(m.photo_formal || m.photo || m.foto || m.foto_siswa || m.photo_freestyle),
        photo_freestyle: getFileUrl(m.photo_freestyle),
        custom_columns: customCols,
        source: 'Form Builder Repeater',
      });
    });
    return result;
  }

  // Strategi B: Form Builder Field Identitas Siswa Tunggal/Individu
  const singleName = cv.nama_siswa || cv.nama_peserta || cv.nama_lengkap || cv.nama_lengkap_siswa || cv.nama || cv.student_name;
  if (singleName) {
    const customCols = {};
    for (const [k, v] of Object.entries(cv)) {
      if (!STUDENT_IDENTITY_SET.has(k) && typeof v !== 'object') {
        customCols[k] = v;
      }
    }

    result.push({
      no: 1,
      name: singleName,
      nisn: cv.nisn || cv.nisn_siswa || '-',
      gender: cv.gender || cv.jenis_kelamin || '-',
      grade: cv.kelas || cv.kelas_siswa || cv.tingkat || '-',
      birth_date: cv.tanggal_lahir || cv.tgl_lahir || cv.birth_date || null,
      wa: cv.wa_siswa || cv.nomor_wa_siswa || cv.no_wa_siswa || cv.wa || null,
      is_captain: false,
      custom_id: cv.custom_id || cv.id_peserta || null,
      photo: getFileUrl(cv.foto_formal || cv.pas_foto || cv.foto_siswa || cv.photo_formal || cv.photo),
      photo_freestyle: getFileUrl(cv.foto_bebas || cv.photo_freestyle),
      custom_columns: customCols,
      source: 'Form Builder (Data Siswa)',
    });
    return result;
  }

  // Strategi C: Parsing Cerdas dari registration.participants_names
  if (props.registration.participants_names) {
    const rawLines = props.registration.participants_names
      .split(/[\r\n]+/)
      .map(l => l.trim())
      .filter(Boolean);

    rawLines.forEach((line, idx) => {
      let cleanLine = line.replace(/^\d+[\.\)]\s*/, '').replace(/^[-*]\s*/, '').trim();
      let name = cleanLine;
      let nisn = '-';
      let grade = '-';
      let gender = '-';

      // Ekstrak info dalam kurung: (NISN: 0081234567, Kelas: 9A, L)
      const bracketMatch = cleanLine.match(/^(.*?)\s*\((.*?)\)$/);
      if (bracketMatch) {
        name = bracketMatch[1].trim();
        const inside = bracketMatch[2];
        
        const nisnMatch = inside.match(/NISN\s*[:=]\s*([0-9]+)/i);
        if (nisnMatch) nisn = nisnMatch[1];
        
        const gradeMatch = inside.match(/Kelas\s*[:=]\s*([^,;]+)/i);
        if (gradeMatch) grade = gradeMatch[1].trim();

        const genderMatch = inside.match(/\b(Laki-laki|Perempuan|L\/P|L|P)\b/i);
        if (genderMatch) {
          const g = genderMatch[1].toUpperCase();
          gender = g.startsWith('L') ? 'Laki-laki' : (g.startsWith('P') ? 'Perempuan' : g);
        }
      }

      result.push({
        no: idx + 1,
        name: name || `Peserta ${idx + 1}`,
        nisn: nisn,
        gender: gender,
        grade: grade,
        birth_date: null,
        wa: null,
        is_captain: idx === 0 && rawLines.length > 1,
        custom_id: null,
        photo: null,
        photo_freestyle: null,
        custom_columns: {},
        source: 'Daftar Delegasi Registrasi',
      });
    });
  }

  return result;
});

// 2. Field-field Form Builder Non-Siswa (Persyaratan Karya, Naskah, Link, dll)
const otherFormFields = computed(() => {
  if (!Array.isArray(props.formFields) || props.formFields.length === 0) return [];
  const isSingleUsed = participantsTableData.value.some(p => p.source.includes('Data Siswa'));
  return props.formFields.filter(f => {
    if (f.type === 'participant_repeater' || f.type === 'team_members') return false;
    if (isSingleUsed && STUDENT_IDENTITY_SET.has(f.name)) return false;
    return true;
  });
});

// 3. Unmatched Custom Fields (Data ekstra di custom_fields yang tidak terdaftar di skema cabang)
const extraCustomFields = computed(() => {
  const registeredNames = new Set((props.formFields || []).map(f => f.name));
  const ignoredKeys = new Set([
    'is_package_allin_master', 'allin_code', 'school_district', 'school_npsn', 
    'npsn', 'school_address', 'coordinator_email', 'coordinator_name', 'coordinator_phone',
    'payment_proof', 'package_proof', 'surat_tugas', ...STUDENT_IDENTITY_FIELDS
  ]);
  if (repeaterFieldDef.value) {
    registeredNames.add(repeaterFieldDef.value.name);
    registeredNames.add(repeaterFieldDef.value.name + '_count');
  }

  const extras = {};
  for (const [key, val] of Object.entries(customValues.value)) {
    if (!registeredNames.has(key) && !ignoredKeys.has(key)) {
      extras[key] = val;
    }
  }
  return extras;
});

// Helpers Clipboard
function copyText(text, label = 'Teks') {
  if (!text || text === '-') return;
  try {
    navigator.clipboard.writeText(text);
    toast.success(`${label} berhasil disalin ke clipboard!`);
  } catch (e) {
    toast.info(`${label}: ${text}`);
  }
}

function copyAllParticipants() {
  if (!participantsTableData.value.length) return;
  const lines = participantsTableData.value.map(p => {
    let s = `${p.no}. ${p.name}`;
    if (p.nisn && p.nisn !== '-') s += ` (NISN: ${p.nisn})`;
    if (p.grade && p.grade !== '-') s += ` - Kelas: ${p.grade}`;
    if (p.gender && p.gender !== '-') s += ` [${p.gender}]`;
    if (p.is_captain) s += ` [Kapten Tim]`;
    return s;
  });
  copyText(lines.join('\n'), 'Daftar Nama Delegasi Siswa');
}

// Dokumen Berkas Tambahan (file_documents & proof)
const allDocumentFiles = computed(() => {
  const docs = [];
  
  // 1. Dari file_documents table column
  if (props.registration.file_documents) {
    if (Array.isArray(props.registration.file_documents)) {
      props.registration.file_documents.forEach((d, i) => {
        docs.push({
          id: 'doc_' + i,
          title: d.title || d.label || d.name || `Dokumen Lampiran ${i + 1}`,
          url: d.url || d.file_url || '',
          name: d.name || d.file_name || 'Lampiran Berkas',
          size: d.size || d.file_size || null,
          type: d.type || d.document_type || 'Lampiran',
        });
      });
    } else if (typeof props.registration.file_documents === 'object') {
      for (const [k, d] of Object.entries(props.registration.file_documents)) {
        if (!d) continue;
        docs.push({
          id: 'doc_' + k,
          title: k.replace(/_/g, ' ').toUpperCase(),
          url: typeof d === 'object' ? (d.url || '') : String(d),
          name: typeof d === 'object' ? (d.name || k) : k,
          size: typeof d === 'object' ? (d.size || null) : null,
          type: k,
        });
      }
    }
  }

  // 2. Dari custom_fields.payment_proof / package_proof jika ada
  if (customValues.value.payment_proof) {
    const p = customValues.value.payment_proof;
    docs.push({
      id: 'proof_payment',
      title: 'Bukti Pembayaran / Transfer Bank',
      url: typeof p === 'object' ? (p.url || '') : String(p),
      name: typeof p === 'object' ? (p.name || 'Bukti Transfer') : 'Bukti Transfer',
      size: typeof p === 'object' ? (p.size || null) : null,
      type: 'Pembayaran',
    });
  }

  if (customValues.value.surat_tugas) {
    const s = customValues.value.surat_tugas;
    docs.push({
      id: 'proof_surat_tugas',
      title: 'Surat Tugas / Delegasi Sekolah',
      url: typeof s === 'object' ? (s.url || '') : String(s),
      name: typeof s === 'object' ? (s.name || 'Surat Tugas') : 'Surat Tugas',
      size: typeof s === 'object' ? (s.size || null) : null,
      type: 'Surat Tugas',
    });
  }

  return docs;
});

// Submit Keputusan Verifikasi
function submitDecision(regStatus = null, payStatus = null) {
  if (regStatus) verifyForm.registration_status = regStatus;
  if (payStatus) verifyForm.payment_status = payStatus;
  verifyForm.notes = verifyForm.verification_notes;

  isSubmitting.value = true;
  verifyForm.post('/admin/pendaftar/' + props.registration.id + '/verify', {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false;
      toast.success('Keputusan verifikasi berhasil disimpan!');
    },
    onError: () => {
      isSubmitting.value = false;
      toast.error('Gagal menyimpan verifikasi. Periksa koneksi.');
    },
  });
}

// Kirim Cepat WhatsApp via OpenWA
function sendOfficialNotification(templateKey) {
  if (!props.registration.mentor_wa) {
    toast.error('Nomor WhatsApp pembina tidak tersedia!');
    return;
  }

  isSendingWa.value = true;
  router.post('/admin/wa/manual-notify', {
    phone: props.registration.mentor_wa,
    template: templateKey,
    registration_id: props.registration.id,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSendingWa.value = false;
      toast.success('Notifikasi WhatsApp resmi berhasil dikirim ke pembina!');
    },
    onError: () => {
      isSendingWa.value = false;
      // Fallback: buka link WhatsApp Web langsung dengan template teks
      let fallbackText = '';
      if (templateKey === 'tpl_reg') {
        fallbackText = `*PEMBERITAHUAN RESMI PANITIA SEMARAK ALIYAH XIV 2026*\n\nYth. Bapak/Ibu ${props.registration.mentor_name || 'Pembina'} (${props.registration.school_name}),\n\nKami informasikan bahwa data pendaftaran delegasi cabang *${props.registration.competition_title}* dengan kode *${props.registration.reg_code}* telah *DIVERIFIKASI RESMI* dan dinyatakan *SAH* oleh panitia.\n\nTerima kasih.`;
      } else if (templateKey === 'tpl_pay') {
        fallbackText = `*KONFIRMASI PEMBAYARAN SEMARAK ALIYAH XIV 2026*\n\nYth. Bapak/Ibu ${props.registration.mentor_name || 'Pembina'} (${props.registration.school_name}),\n\nAlhamdulillah, pembayaran pendaftaran cabang *${props.registration.competition_title}* (Kode: *${props.registration.reg_code}*) sebesar *${formatRupiah(props.registration.total_fee)}* telah *TERVERIFIKASI LUNAS* di rekening panitia.\n\nTerima kasih atas partisipasinya.`;
      } else {
        fallbackText = `*CATATAN PANITIA SEMARAK ALIYAH XIV 2026*\n\nYth. Bapak/Ibu ${props.registration.mentor_name || 'Pembina'} (${props.registration.school_name}),\n\nTerkait berkas pendaftaran cabang *${props.registration.competition_title}* (Kode: *${props.registration.reg_code}*), mohon kesediaannya untuk memeriksa catatan berikut:\n\n"${verifyForm.verification_notes || 'Mohon melengkapi data berkas yang diperlukan.'}"\n\nTerima kasih.`;
      }
      window.open(getWaChatUrl(props.registration.mentor_wa, fallbackText), '_blank');
      toast.info('Membuka tautan WhatsApp Web...');
    },
    onFinish: () => {
      isSendingWa.value = false;
    }
  });
}
</script>

<template>
  <VelzonLayout>
    <Head :title="`Verifikasi ${registration.reg_code} - ${registration.school_name} | Semarak Aliyah`" />

    <div class="pendaftar-detail-page py-3">
      <!-- ============================================================== -->
      <!-- TOP NAVIGATION BAR & BREADCRUMB                                -->
      <!-- ============================================================== -->
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <Link 
              href="/admin/pendaftar" 
              class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-pill px-2.5 py-1 text-dark"
              title="Kembali ke Tabel Data Pendaftar"
            >
              <i class="ri-arrow-left-line"></i>
              <span>Kembali</span>
            </Link>
            <span class="text-muted fs-12">/</span>
            <span class="text-muted fs-12">Administrasi Pendaftaran</span>
            <span class="text-muted fs-12">/</span>
            <span class="fw-semibold text-primary fs-12">Verifikasi Detail Delegasi</span>
          </div>
          <div class="d-flex align-items-center gap-2.5 mt-1 flex-wrap">
            <h4 class="fs-18 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
              <span>Verifikasi Pendaftaran:</span>
              <span class="badge bg-primary text-white font-monospace fs-14 px-2.5 py-1 rounded shadow-sm">
                {{ registration.reg_code }}
              </span>
            </h4>
            <span 
              class="badge rounded-pill fs-11 px-2.5 py-1 fw-semibold"
              :class="{
                'bg-success text-white': registration.registration_status === 'Terverifikasi',
                'bg-warning text-dark': registration.registration_status === 'Menunggu Verifikasi',
                'bg-danger text-white': registration.registration_status === 'Ditolak'
              }"
            >
              <i class="ri-checkbox-circle-line me-1" v-if="registration.registration_status === 'Terverifikasi'"></i>
              <i class="ri-time-line me-1" v-else-if="registration.registration_status === 'Menunggu Verifikasi'"></i>
              <i class="ri-close-circle-line me-1" v-else></i>
              Berkas: {{ registration.registration_status || 'Menunggu' }}
            </span>
            <span 
              class="badge rounded-pill fs-11 px-2.5 py-1 fw-semibold"
              :class="{
                'bg-success text-white': registration.payment_status === 'Terverifikasi',
                'bg-warning text-dark': registration.payment_status === 'Menunggu Verifikasi',
                'bg-danger text-white': registration.payment_status === 'Ditolak'
              }"
            >
              <i class="ri-money-dollar-circle-line me-1"></i>
              Bayar: {{ registration.payment_status || 'Menunggu' }}
            </span>
          </div>
        </div>

        <!-- Quick Prev / Next Navigator & Actions -->
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <Link 
            v-if="prevRegistration" 
            :href="'/admin/pendaftar/' + prevRegistration.id" 
            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-pill"
            :title="`Sebelumnya: ${prevRegistration.reg_code} (${prevRegistration.school_name})`"
          >
            <i class="ri-arrow-left-s-line"></i>
            <span class="d-none d-sm-inline">Sebelumnya</span>
          </Link>
          <button v-else class="btn btn-sm btn-outline-secondary rounded-pill opacity-50 cursor-not-allowed" disabled>
            <i class="ri-arrow-left-s-line"></i>
            <span class="d-none d-sm-inline">Sebelumnya</span>
          </button>

          <Link 
            v-if="nextRegistration" 
            :href="'/admin/pendaftar/' + nextRegistration.id" 
            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 rounded-pill"
            :title="`Berikutnya: ${nextRegistration.reg_code} (${nextRegistration.school_name})`"
          >
            <span class="d-none d-sm-inline">Berikutnya</span>
            <i class="ri-arrow-right-s-line"></i>
          </Link>
          <button v-else class="btn btn-sm btn-outline-secondary rounded-pill opacity-50 cursor-not-allowed" disabled>
            <span class="d-none d-sm-inline">Berikutnya</span>
            <i class="ri-arrow-right-s-line"></i>
          </button>

          <!-- Cetak Nota / Kwitansi Resmi -->
          <a 
            :href="'/admin/invoice/' + registration.id" 
            target="_blank" 
            class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 rounded-pill shadow-sm"
            title="Cetak Nota Tagihan / Kwitansi Pembayaran Resmi"
          >
            <i class="ri-file-paper-2-line"></i>
            <span>Nota / Kwitansi</span>
          </a>

          <!-- Cetak Ringkasan -->
          <button 
            type="button" 
            onclick="window.print()" 
            class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 rounded-pill"
            title="Cetak Berkas Ringkasan Verifikasi Pendaftaran"
          >
            <i class="ri-printer-line"></i>
            <span>Cetak</span>
          </button>

          <!-- Hapus Delegasi Pendaftar (Super Admin) -->
          <button 
            v-if="canDelete"
            type="button" 
            class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1.5 rounded-pill shadow-xs"
            @click="deleteThisRegistration"
            title="Hapus Delegasi Pendaftar Ini (Super Admin)"
          >
            <i class="ri-delete-bin-line"></i>
            <span>Hapus Delegasi</span>
          </button>

          <!-- Hubungi Pembina via WhatsApp -->
          <a 
            :href="getWaChatUrl(registration.mentor_wa, `Halo Bapak/Ibu ${registration.mentor_name || 'Pembina'} (${registration.school_name || ''}), kami dari Panitia Semarak Aliyah terkait verifikasi pendaftaran delegasi (Kode: ${registration.reg_code}) pada cabang ${registration.competition_title || registration.competition_id}: `)"
            target="_blank" 
            class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 rounded-pill shadow-sm"
          >
            <i class="ri-whatsapp-line"></i>
            <span>WA Pembina</span>
          </a>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TOP OVERVIEW CARD (Grid Lembaga, Cabang, Biaya, Kontak)       -->
      <!-- ============================================================== -->
      <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-light border-bottom">
          <div class="row g-3 align-items-center">
            <!-- Col 1: Lembaga & NPSN -->
            <div class="col-md-4 col-sm-6 border-md-end">
              <div class="d-flex align-items-start gap-2.5">
                <div class="avatar-sm rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                  <i class="ri-building-line fs-22"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-muted fs-11 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Asal Lembaga / Kontingen</div>
                  <h6 class="fs-15 fw-bold text-dark text-truncate mb-0" :title="registration.school_name">
                    {{ registration.school_name }}
                  </h6>
                  <div class="d-flex align-items-center gap-2 mt-1 fs-11 text-muted">
                    <span v-if="customValues.school_npsn || customValues.npsn" class="badge bg-secondary-subtle text-secondary font-monospace fs-10">
                      NPSN: {{ customValues.school_npsn || customValues.npsn }}
                    </span>
                    <span v-if="customValues.school_district" class="text-truncate">
                      Kec. {{ customValues.school_district }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Col 2: Cabang Lomba & Paket -->
            <div class="col-md-3 col-sm-6 border-md-end">
              <div class="d-flex align-items-start gap-2.5">
                <div class="avatar-sm rounded-3 bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                  <i class="ri-trophy-line fs-22"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-muted fs-11 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Cabang Perlombaan</div>
                  <h6 class="fs-14 fw-bold text-dark text-truncate mb-0" :title="registration.competition_title">
                    {{ registration.competition_title || registration.competition_id }}
                  </h6>
                  <div class="d-flex align-items-center gap-1.5 mt-1">
                    <span class="badge bg-primary-subtle text-primary fs-10">
                      {{ competition?.category_label || competition?.category || 'Umum' }}
                    </span>
                    <span class="badge bg-info-subtle text-info fs-10">
                      {{ registration.package_type || 'Paket Satuan' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Col 3: Guru Pembina & WA -->
            <div class="col-md-3 col-sm-6 border-md-end">
              <div class="d-flex align-items-start gap-2.5">
                <div class="avatar-sm rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                  <i class="ri-user-follow-line fs-22"></i>
                </div>
                <div class="min-w-0">
                  <div class="text-muted fs-11 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Guru / Pembina Pendamping</div>
                  <h6 class="fs-13 fw-bold text-dark text-truncate mb-0" :title="registration.mentor_name">
                    {{ registration.mentor_name || '-' }}
                  </h6>
                  <div class="mt-1">
                    <a 
                      :href="getWaChatUrl(registration.mentor_wa)" 
                      target="_blank" 
                      class="badge bg-success-subtle text-success fs-11 text-decoration-none font-monospace d-inline-flex align-items-center gap-1"
                    >
                      <i class="ri-whatsapp-fill"></i> {{ registration.mentor_wa || '-' }}
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Col 4: Biaya & Waktu Daftar -->
            <div class="col-md-2 col-sm-6">
              <div class="min-w-0 text-md-end">
                <div class="text-muted fs-11 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Total Biaya Registrasi</div>
                <h5 class="fs-16 fw-bold text-primary mb-0 font-monospace">
                  {{ formatRupiah(registration.total_fee) }}
                </h5>
                <div class="fs-11 text-muted mt-1" :title="registration.created_at">
                  <i class="ri-calendar-event-line me-0.5"></i> {{ formatDateOnly(registration.created_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notification Banner if Paket All-In -->
        <div v-if="registration.package_type === 'Paket All-In'" class="p-2.5 px-4 bg-warning-subtle text-dark border-bottom border-warning-subtle d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2 fs-12">
            <i class="ri-vip-crown-fill text-warning fs-16"></i>
            <span>
              <strong>Pendaftaran ini terikat Paket All-In Komplit Sekolah.</strong> Verifikasi pembayaran akan otomatis disinkronkan ke seluruh cabang kontingen ini.
            </span>
          </div>
          <span v-if="relatedRegistrations && relatedRegistrations.length" class="badge bg-warning text-dark fs-11 fw-semibold">
            {{ relatedRegistrations.length }} Cabang Lain Terhubung
          </span>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- MAIN SPLIT CONTENT: (Kiri: Output Form Builder) + (Kanan: Aksi)-->
      <!-- ============================================================== -->
      <div class="row g-4">
        <!-- ============================================================ -->
        <!-- LEFT COLUMN: OUTPUT FORMULIR PER-CABANG & LAMPIRAN BERKAS    -->
        <!-- ============================================================ -->
        <div class="col-lg-8">
          <!-- TABS HEADER -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-bottom p-0">
              <ul class="nav nav-tabs nav-tabs-custom card-header-tabs m-0 px-3" role="tablist">
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-3 px-3.5 fs-13 fw-bold d-flex align-items-center gap-2"
                    :class="{ 'active text-primary': activeTab === 'formulir' }"
                    @click="activeTab = 'formulir'"
                  >
                    <i class="ri-survey-line fs-16"></i>
                    <span>Output Formulir Cabang</span>
                    <span v-if="formFields && formFields.length" class="badge bg-primary-subtle text-primary fs-10 rounded-pill">
                      {{ formFields.length }}
                    </span>
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-3 px-3.5 fs-13 fw-bold d-flex align-items-center gap-2"
                    :class="{ 'active text-primary': activeTab === 'berkas' }"
                    @click="activeTab = 'berkas'"
                  >
                    <i class="ri-file-shield-2-line fs-16"></i>
                    <span>Dokumen & Berkas</span>
                    <span v-if="allDocumentFiles && allDocumentFiles.length" class="badge bg-success-subtle text-success fs-10 rounded-pill">
                      {{ allDocumentFiles.length }}
                    </span>
                  </button>
                </li>
                <li v-if="registration.package_type === 'Paket All-In'" class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-3 px-3.5 fs-13 fw-bold d-flex align-items-center gap-2"
                    :class="{ 'active text-primary': activeTab === 'allin' }"
                    @click="activeTab = 'allin'"
                  >
                    <i class="ri-apps-2-line fs-16"></i>
                    <span>Kontingen All-In ({{ (relatedRegistrations?.length || 0) + 1 }})</span>
                  </button>
                </li>
                <li class="nav-item">
                  <button 
                    type="button" 
                    class="nav-link py-3 px-3.5 fs-13 fw-bold d-flex align-items-center gap-2"
                    :class="{ 'active text-primary': activeTab === 'operasional' }"
                    @click="activeTab = 'operasional'"
                  >
                    <i class="ri-qr-code-line fs-16"></i>
                    <span>Data Teknis & TM</span>
                  </button>
                </li>
              </ul>
            </div>

            <div class="card-body p-4">
              <!-- ====================================================== -->
              <!-- TAB 1: OUTPUT FORMULIR PENDAFTARAN PER-CABANG (BUILDER)-->
              <!-- ====================================================== -->
              <div v-show="activeTab === 'formulir'">
                <!-- Banner Info Form Builder -->
                <div class="p-3 rounded-3 bg-light border mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                  <div class="d-flex align-items-center gap-2.5">
                    <div class="p-2 bg-primary rounded-3 text-white">
                      <i class="ri-shapes-line fs-18"></i>
                    </div>
                    <div>
                      <h6 class="fs-13 fw-bold text-dark mb-0">
                        Skema Formulir: {{ competition?.title || registration.competition_title }}
                      </h6>
                      <small class="text-muted fs-11">
                        Formulir cabang lomba ini dikonfigurasi melalui <strong>Form Builder Dinamis</strong>.
                      </small>
                    </div>
                  </div>
                  <Link 
                    :href="'/admin/form-builder/' + registration.competition_id" 
                    class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1"
                    title="Buka Desain Form Builder Cabang Ini"
                  >
                    <i class="ri-edit-line"></i> Desain Form Cabang
                  </Link>
                </div>

                <!-- ============================================================== -->
                <!-- SEKSI 1: TABEL DATA DELEGASI PESERTA (PESERTA SISWA)           -->
                <!-- ============================================================== -->
                <div class="card border rounded-3 mb-4 shadow-none bg-white overflow-hidden">
                  <div class="card-header bg-light border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                      <div class="d-flex align-items-center gap-2">
                        <i class="ri-user-star-line text-primary fs-18"></i>
                        <h6 class="fs-13 fw-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.5px;">
                          Tabel Data Delegasi Peserta (Peserta Siswa)
                        </h6>
                      </div>
                      <small class="text-muted fs-11 mt-0.5 d-block">
                        Data identitas resmi siswa/peserta yang didaftarkan oleh sekolah melalui formulir pendaftaran cabang lomba.
                      </small>
                    </div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <!-- Badge Total & Kuota -->
                      <span class="badge bg-primary text-white font-monospace fs-11 px-2.5 py-1 shadow-sm">
                        <i class="ri-team-line me-1"></i> {{ participantsTableData.length }} Siswa Terdaftar
                      </span>
                      <span class="badge bg-secondary-subtle text-secondary fs-11">
                        {{ competition?.participant_type || 'Delegasi Siswa' }} (Maks. {{ competition?.max_participants || 1 }} Orang)
                      </span>

                      <!-- Switcher Tampilan: Table vs Cards -->
                      <div class="btn-group btn-group-sm">
                        <button 
                          type="button" 
                          class="btn"
                          :class="participantViewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary'"
                          @click="participantViewMode = 'table'"
                          title="Tampilan Tabel Resmi"
                        >
                          <i class="ri-table-line me-1"></i> Tabel
                        </button>
                        <button 
                          type="button" 
                          class="btn"
                          :class="participantViewMode === 'cards' ? 'btn-primary' : 'btn-outline-secondary'"
                          @click="participantViewMode = 'cards'"
                          title="Tampilan Kartu Delegasi"
                        >
                          <i class="ri-grid-fill me-1"></i> Kartu
                        </button>
                      </div>

                      <!-- Tombol Salin Daftar Nama -->
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-secondary d-inline-flex align-items-center gap-1"
                        @click="copyAllParticipants()"
                        title="Salin seluruh nama delegasi ke clipboard"
                      >
                        <i class="ri-file-copy-line"></i> Salin Nama
                      </button>
                    </div>
                  </div>

                  <div class="card-body p-0">
                    <!-- VIEW MODE 1: TABEL DATA RESMI -->
                    <div v-if="participantViewMode === 'table'" class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-11 text-uppercase text-muted border-bottom" style="letter-spacing: 0.5px;">
                          <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th class="text-center" style="width: 65px;">Pas Foto</th>
                            <th>Nama Lengkap Siswa</th>
                            <th>NISN</th>
                            <th class="text-center" style="width: 90px;">L/P</th>
                            <th class="text-center" style="width: 80px;">Kelas</th>
                            <th>Tanggal Lahir</th>
                            <th>Kontak WhatsApp</th>
                            <th>Data Khusus Cabang</th>
                            <th class="text-center" style="width: 70px;">Aksi</th>
                          </tr>
                        </thead>
                        <tbody class="fs-12 divide-y">
                          <tr v-for="p in participantsTableData" :key="p.no" class="transition-all">
                            <!-- No -->
                            <td class="text-center">
                              <span class="badge rounded-pill bg-dark text-white font-monospace fs-10 px-1.5 py-0.5">
                                #{{ p.no }}
                              </span>
                            </td>

                            <!-- Foto -->
                            <td class="text-center py-2">
                              <div 
                                v-if="p.photo || p.photo_freestyle"
                                class="rounded-3 border overflow-hidden cursor-pointer position-relative d-inline-block shadow-sm"
                                style="width: 42px; height: 52px; background: #f1f5f9;"
                                @click="openImagePreview(p.photo || p.photo_freestyle, `Foto Siswa: ${p.name}`)"
                                title="Klik untuk memperbesar foto siswa"
                              >
                                <img 
                                  :src="p.photo || p.photo_freestyle" 
                                  alt="Foto"
                                  class="w-100 h-100 object-fit-cover"
                                />
                              </div>
                              <div 
                                v-else
                                class="rounded-3 bg-light border text-muted d-inline-flex flex-column align-items-center justify-content-center"
                                style="width: 42px; height: 52px;"
                                title="Tidak ada pas foto"
                              >
                                <i class="ri-user-3-line fs-18 opacity-50"></i>
                                <span class="fs-8 text-muted">No Foto</span>
                              </div>
                            </td>

                            <!-- Nama & Status Kapten -->
                            <td>
                              <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                <span class="fw-bold text-dark fs-13">{{ p.name }}</span>
                                <span v-if="p.is_captain" class="badge bg-warning text-dark fs-10 fw-bold d-inline-flex align-items-center gap-0.5">
                                  <i class="ri-shield-user-fill"></i> Kapten Tim
                                </span>
                                <span v-if="p.custom_id" class="badge bg-light text-muted border fs-10 font-monospace">
                                  ID: {{ p.custom_id }}
                                </span>
                              </div>
                              <div class="fs-10 text-muted mt-0.5">
                                Sumber: <span class="fst-italic">{{ p.source }}</span>
                              </div>
                            </td>

                            <!-- NISN -->
                            <td>
                              <div class="d-flex align-items-center gap-1">
                                <span 
                                  class="badge bg-light text-dark border font-monospace fs-11 px-2 py-1 cursor-pointer"
                                  @click="copyText(p.nisn, 'NISN')"
                                  title="Klik untuk salin NISN"
                                >
                                  {{ p.nisn }}
                                </span>
                                <button 
                                  v-if="p.nisn && p.nisn !== '-'"
                                  type="button" 
                                  class="btn btn-xs p-0 text-muted" 
                                  @click="copyText(p.nisn, 'NISN')"
                                  title="Salin NISN"
                                >
                                  <i class="ri-file-copy-line fs-12"></i>
                                </button>
                              </div>
                            </td>

                            <!-- Gender (L/P) -->
                            <td class="text-center">
                              <span 
                                v-if="p.gender && p.gender !== '-'"
                                class="badge fs-10 px-2 py-0.5"
                                :class="p.gender.startsWith('L') ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'"
                              >
                                <i :class="p.gender.startsWith('L') ? 'ri-men-line' : 'ri-women-line'" class="me-0.5"></i>
                                {{ p.gender.startsWith('L') ? 'L' : (p.gender.startsWith('P') ? 'P' : p.gender) }}
                              </span>
                              <span v-else class="text-muted fs-11">-</span>
                            </td>

                            <!-- Kelas -->
                            <td class="text-center">
                              <span v-if="p.grade && p.grade !== '-'" class="badge bg-secondary-subtle text-secondary fs-11">
                                {{ p.grade }}
                              </span>
                              <span v-else class="text-muted fs-11">-</span>
                            </td>

                            <!-- Tanggal Lahir -->
                            <td>
                              <span v-if="p.birth_date" class="font-monospace fs-11 text-dark">
                                <i class="ri-calendar-event-line text-primary me-0.5"></i>
                                {{ formatDateOnly(p.birth_date) }}
                              </span>
                              <span v-else class="text-muted fs-11">-</span>
                            </td>

                            <!-- WhatsApp Siswa -->
                            <td>
                              <a 
                                v-if="p.wa && p.wa !== '-'"
                                :href="getWaChatUrl(p.wa)" 
                                target="_blank" 
                                class="btn btn-xs btn-outline-success d-inline-flex align-items-center gap-1 font-monospace"
                                title="Kirim pesan WhatsApp ke siswa"
                              >
                                <i class="ri-whatsapp-line"></i> {{ p.wa }}
                              </a>
                              <span v-else class="text-muted fs-11">-</span>
                            </td>

                            <!-- Data Khusus Form Builder per Siswa -->
                            <td>
                              <div v-if="p.custom_columns && Object.keys(p.custom_columns).length" class="d-flex flex-column gap-0.5 fs-11">
                                <div v-for="(cVal, cKey) in p.custom_columns" :key="cKey" class="text-truncate" style="max-width: 160px;">
                                  <span class="text-muted text-capitalize">{{ cKey.replace(/_/g, ' ') }}:</span>
                                  <strong class="text-dark ms-1">{{ cVal }}</strong>
                                </div>
                              </div>
                              <span v-else class="text-muted fs-11 fst-italic">Standar</span>
                            </td>

                            <!-- Aksi -->
                            <td class="text-center">
                              <div class="d-flex align-items-center justify-content-center gap-1">
                                <button 
                                  v-if="p.photo || p.photo_freestyle"
                                  type="button" 
                                  class="btn btn-xs btn-outline-primary"
                                  @click="openImagePreview(p.photo || p.photo_freestyle, `Pas Foto: ${p.name}`)"
                                  title="Lihat Foto Peserta"
                                >
                                  <i class="ri-eye-line"></i>
                                </button>
                                <button 
                                  type="button" 
                                  class="btn btn-xs btn-outline-secondary"
                                  @click="copyText(`${p.name} - NISN: ${p.nisn} - Kelas: ${p.grade}`, 'Data Siswa')"
                                  title="Salin Data Siswa Ini"
                                >
                                  <i class="ri-file-copy-line"></i>
                                </button>
                              </div>
                            </td>
                          </tr>

                          <!-- Baris Kosong jika tidak ada data sama sekali -->
                          <tr v-if="!participantsTableData || participantsTableData.length === 0">
                            <td colspan="10" class="text-center py-4 text-muted fs-12">
                              <i class="ri-inbox-line fs-24 opacity-40 d-block mb-1"></i>
                              Belum ada data delegasi peserta yang diinputkan.
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>

                    <!-- VIEW MODE 2: KARTU GRID DELEGASI -->
                    <div v-else class="p-3">
                      <div class="row g-3">
                        <div v-for="member in participantsTableData" :key="member.no" class="col-md-6">
                          <div class="card border rounded-3 h-100 shadow-none hover-shadow transition-all bg-light overflow-hidden">
                            <div class="card-body p-3">
                              <div class="d-flex align-items-start gap-3">
                                <div class="position-relative flex-shrink-0">
                                  <div 
                                    v-if="member.photo || member.photo_freestyle"
                                    class="rounded-3 border overflow-hidden cursor-pointer"
                                    style="width: 58px; height: 72px; background: #e2e8f0;"
                                    @click="openImagePreview(member.photo || member.photo_freestyle, `Foto: ${member.name}`)"
                                  >
                                    <img :src="member.photo || member.photo_freestyle" alt="Foto Siswa" class="w-100 h-100 object-fit-cover" />
                                  </div>
                                  <div v-else class="rounded-3 bg-white border text-muted d-flex flex-column align-items-center justify-content-center" style="width: 58px; height: 72px;">
                                    <i class="ri-user-line fs-24 opacity-50"></i>
                                    <span class="fs-9 text-muted mt-0.5">No Foto</span>
                                  </div>
                                  <span class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-dark text-white fs-10 px-1.5 py-0.5 font-monospace">
                                    #{{ member.no }}
                                  </span>
                                </div>

                                <div class="min-w-0 flex-grow-1">
                                  <div class="d-flex align-items-center gap-1.5 mb-1 flex-wrap">
                                    <h6 class="fs-13 fw-bold text-dark mb-0 text-truncate">{{ member.name }}</h6>
                                    <span v-if="member.is_captain" class="badge bg-warning text-dark fs-10 fw-bold"><i class="ri-shield-user-fill"></i> Kapten</span>
                                  </div>
                                  <div class="fs-11 text-muted d-flex flex-column gap-0.5">
                                    <div v-if="member.nisn && member.nisn !== '-'"><span class="fw-semibold text-dark font-monospace">NISN:</span> {{ member.nisn }}</div>
                                    <div class="d-flex align-items-center gap-2">
                                      <span v-if="member.gender && member.gender !== '-'">{{ member.gender }}</span>
                                      <span v-if="member.grade && member.grade !== '-'">• Kls: {{ member.grade }}</span>
                                    </div>
                                    <div v-if="member.birth_date"><i class="ri-cake-2-line text-warning me-0.5"></i> {{ formatDateOnly(member.birth_date) }}</div>
                                    <div v-if="member.wa"><a :href="getWaChatUrl(member.wa)" target="_blank" class="text-success text-decoration-none"><i class="ri-whatsapp-line"></i> {{ member.wa }}</a></div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ============================================================== -->
                <!-- SEKSI 2: ISIAN FORMULIR KHUSUS CABANG LOMBA (FORM BUILDER)     -->
                <!-- ============================================================== -->
                <div v-if="otherFormFields && otherFormFields.length > 0" class="mb-4">
                  <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fs-13 fw-bold text-dark text-uppercase d-flex align-items-center gap-2 mb-0" style="letter-spacing: 0.5px;">
                      <i class="ri-file-list-3-line text-primary fs-16"></i>
                      <span>Isian Persyaratan Khusus Cabang Lomba (Form Builder)</span>
                    </h6>
                    <span class="badge bg-secondary-subtle text-secondary fs-11">
                      {{ otherFormFields.length }} Field Form Builder
                    </span>
                  </div>

                  <div class="row g-3">
                    <div 
                      v-for="field in otherFormFields" 
                      :key="field.id || field.name"
                      class="col-12"
                      :class="{ 'col-md-6': field.type !== 'textarea' && field.type !== 'file' && field.type !== 'coordinators' }"
                    >
                      <div class="p-3 rounded-3 bg-light border h-100">
                        <div class="d-flex align-items-center justify-content-between mb-1.5">
                          <label class="form-label fs-11 fw-bold text-uppercase text-muted mb-0" style="letter-spacing: 0.5px;">
                            {{ field.label }}
                          </label>
                          <span v-if="field.required" class="badge bg-danger-subtle text-danger fs-9">Wajib</span>
                        </div>

                        <!-- Render Berdasarkan Tipe Input Form Builder -->
                        <!-- A. TIPE FILE -->
                        <div v-if="field.type === 'file'">
                          <div v-if="getCustomFileList(customValues[field.name]).length > 0" class="d-flex flex-column gap-2">
                            <div 
                              v-for="(fItem, fIdx) in getCustomFileList(customValues[field.name])" 
                              :key="fIdx"
                            >
                              <div class="d-flex align-items-center justify-content-between p-2.5 rounded-3 bg-white border">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                  <span v-if="getCustomFileList(customValues[field.name]).length > 1" class="badge bg-light text-muted fs-10 px-1.5 py-0.5">
                                    #{{ fIdx + 1 }}
                                  </span>
                                  <i class="ri-file-text-line fs-20 text-primary"></i>
                                  <div class="min-w-0">
                                    <div class="fs-12 fw-semibold text-dark text-truncate">
                                      {{ getFileName(fItem) }}
                                    </div>
                                    <div v-if="getFileSizeStr(fItem)" class="fs-10 text-muted font-monospace">
                                      {{ getFileSizeStr(fItem) }}
                                    </div>
                                  </div>
                                </div>
                                <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                  <button 
                                    v-if="isImageFile(fItem)"
                                    type="button" 
                                    class="btn btn-xs btn-outline-primary"
                                    @click="openImagePreview(getFileUrl(fItem), field.label + (getCustomFileList(customValues[field.name]).length > 1 ? ` #${fIdx + 1}` : ''))"
                                  >
                                    <i class="ri-eye-line"></i> Pratinjau
                                  </button>
                                  <a 
                                    :href="getFileUrl(fItem)" 
                                    target="_blank" 
                                    class="btn btn-xs btn-primary d-inline-flex align-items-center gap-1"
                                  >
                                    <i class="ri-download-cloud-line"></i> Buka / Unduh
                                  </a>
                                </div>
                              </div>
                              <!-- Mini Image Preview jika file gambar -->
                              <div v-if="isImageFile(fItem)" class="mt-2 text-center">
                                <img 
                                  :src="getFileUrl(fItem)" 
                                  alt="Pratinjau" 
                                  class="rounded-3 border cursor-pointer hover-opacity"
                                  style="max-height: 140px; max-width: 100%; object-fit: contain;"
                                  @click="openImagePreview(getFileUrl(fItem), field.label + (getCustomFileList(customValues[field.name]).length > 1 ? ` #${fIdx + 1}` : ''))"
                                />
                              </div>
                            </div>
                          </div>
                          <div v-else class="text-muted fs-12 fst-italic">
                            Tidak ada berkas diunggah.
                          </div>
                        </div>

                        <!-- B. TIPE TEXTAREA -->
                        <div v-else-if="field.type === 'textarea'">
                          <div class="p-2.5 rounded-3 bg-white border fs-12 text-dark whitespace-pre-line" style="min-height: 56px;">
                            {{ customValues[field.name] || '-' }}
                          </div>
                        </div>

                        <!-- C. TIPE CHECKBOX (ARRAY) -->
                        <div v-else-if="field.type === 'checkbox'">
                          <div v-if="Array.isArray(customValues[field.name]) && customValues[field.name].length" class="d-flex flex-wrap gap-1.5">
                            <span 
                              v-for="(cItem, cIdx) in customValues[field.name]" 
                              :key="cIdx"
                              class="badge bg-primary-subtle text-primary fs-11 px-2 py-1"
                            >
                              <i class="ri-check-line me-0.5"></i> {{ cItem }}
                            </span>
                          </div>
                          <div v-else class="text-muted fs-12 fst-italic">
                            {{ customValues[field.name] || 'Tidak ada pilihan' }}
                          </div>
                        </div>

                        <!-- D. TIPE SELECT / RADIO -->
                        <div v-else-if="field.type === 'select' || field.type === 'radio'">
                          <span v-if="customValues[field.name]" class="badge bg-secondary-subtle text-dark fs-12 px-2.5 py-1.5 border">
                            <i class="ri-checkbox-circle-fill text-primary me-1"></i>
                            {{ customValues[field.name] }}
                          </span>
                          <span v-else class="text-muted fs-12 fst-italic">-</span>
                        </div>

                        <!-- E. TIPE DATE -->
                        <div v-else-if="field.type === 'date'">
                          <div class="fs-13 fw-semibold text-dark font-monospace">
                            <i class="ri-calendar-line text-primary me-1"></i>
                            {{ formatDateOnly(customValues[field.name]) }}
                          </div>
                        </div>

                        <!-- F. TIPE URL -->
                        <div v-else-if="field.type === 'url'">
                          <div v-if="customValues[field.name]">
                            <a :href="customValues[field.name]" target="_blank" class="text-primary text-decoration-underline fs-12 d-inline-flex align-items-center gap-1">
                              <i class="ri-external-link-line"></i> {{ customValues[field.name] }}
                            </a>
                          </div>
                          <div v-else class="text-muted fs-12 fst-italic">-</div>
                        </div>

                        <!-- G. TIPE PHONE -->
                        <div v-else-if="field.type === 'phone'">
                          <div v-if="customValues[field.name]" class="d-flex align-items-center gap-2">
                            <span class="fs-13 fw-semibold text-dark font-monospace">{{ customValues[field.name] }}</span>
                            <a :href="getWaChatUrl(customValues[field.name])" target="_blank" class="btn btn-xs btn-success d-inline-flex align-items-center gap-1">
                              <i class="ri-whatsapp-line"></i> Chat
                            </a>
                          </div>
                          <div v-else class="text-muted fs-12 fst-italic">-</div>
                        </div>

                        <!-- H. TIPE COORDINATORS / OFFICIAL -->
                        <div v-else-if="field.type === 'coordinators'">
                          <div v-if="Array.isArray(customValues[field.name]) && customValues[field.name].length" class="d-flex flex-column gap-2">
                            <div 
                              v-for="(co, coIdx) in customValues[field.name]" 
                              :key="coIdx"
                              class="p-2.5 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2"
                            >
                              <div>
                                <span class="fw-bold text-dark fs-12">{{ co.name || co.nama || 'Pendamping' }}</span>
                                <span class="badge bg-light text-muted ms-1.5">{{ co.role || co.jabatan || 'Official' }}</span>
                              </div>
                              <div v-if="co.wa || co.phone">
                                <a :href="getWaChatUrl(co.wa || co.phone)" target="_blank" class="btn btn-xs btn-outline-success d-inline-flex align-items-center gap-1">
                                  <i class="ri-whatsapp-line"></i> {{ co.wa || co.phone }}
                                </a>
                              </div>
                            </div>
                          </div>
                          <div v-else class="text-muted fs-12 fst-italic">-</div>
                        </div>

                        <!-- I. TEXT / DEFAULT -->
                        <div v-else class="fs-13 fw-semibold text-dark">
                          {{ customValues[field.name] || '-' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Info jika seluruh data sudah masuk di tabel peserta -->
                <div v-else class="p-3 bg-light rounded-3 border text-center text-muted fs-12 mb-4">
                  <i class="ri-checkbox-circle-line text-success fs-16 me-1"></i>
                  Seluruh data formulir pendaftaran cabang lomba ini telah tercakup secara lengkap dalam Tabel Data Delegasi Peserta di atas.
                </div>

                <!-- 4. EXTRA / UNMATCHED CUSTOM FIELDS (Jika ada data ekstra yang diisi) -->
                <div v-if="Object.keys(extraCustomFields).length > 0" class="mt-4 pt-3 border-top">
                  <h6 class="fs-12 fw-bold text-dark text-uppercase d-flex align-items-center gap-1.5 mb-2.5 text-muted" style="letter-spacing: 0.5px;">
                    <i class="ri-more-2-fill"></i> Data Formulir Tambahan / Legacy
                  </h6>
                  <div class="row g-2">
                    <div v-for="(exVal, exKey) in extraCustomFields" :key="exKey" class="col-md-6">
                      <div class="p-2.5 rounded bg-light border fs-12">
                        <div class="text-muted fs-10 text-uppercase fw-semibold mb-0.5">{{ exKey.replace(/_/g, ' ') }}</div>
                        <div v-if="typeof exVal === 'object' && exVal !== null && exVal.url">
                          <a :href="exVal.url" target="_blank" class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1">
                            <i class="ri-download-cloud-line"></i> {{ exVal.name || 'Unduh Berkas' }}
                          </a>
                        </div>
                        <div v-else-if="Array.isArray(exVal)" class="fw-semibold text-dark">
                          {{ exVal.join(', ') }}
                        </div>
                        <div v-else class="fw-semibold text-dark whitespace-pre-line">
                          {{ exVal || '-' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ====================================================== -->
              <!-- TAB 2: DOKUMEN & BERKAS PERSYARATAN                   -->
              <!-- ====================================================== -->
              <div v-show="activeTab === 'berkas'">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div>
                    <h6 class="fs-14 fw-bold text-dark mb-0">Dokumen Lampiran Pendaftaran</h6>
                    <small class="text-muted fs-11">Pemeriksaan bukti transfer bank, surat tugas, dan dokumen legalitas sekolah.</small>
                  </div>
                  <span class="badge bg-secondary-subtle text-secondary fs-11">
                    {{ allDocumentFiles.length }} Berkas Terdeteksi
                  </span>
                </div>

                <div v-if="allDocumentFiles && allDocumentFiles.length > 0" class="row g-3">
                  <div v-for="doc in allDocumentFiles" :key="doc.id" class="col-md-6">
                    <div class="card border rounded-3 h-100 shadow-none bg-light overflow-hidden">
                      <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-2.5">
                          <div class="p-2 rounded-2 bg-primary-subtle text-primary flex-shrink-0">
                            <i class="ri-file-shield-line fs-20"></i>
                          </div>
                          <div class="min-w-0 flex-grow-1">
                            <span class="badge bg-white border text-dark fs-10 mb-1">{{ doc.type }}</span>
                            <h6 class="fs-13 fw-bold text-dark mb-1 text-truncate" :title="doc.title">{{ doc.title }}</h6>
                            <div class="fs-11 text-muted text-truncate font-monospace">{{ doc.name }}</div>
                            <div v-if="doc.size" class="fs-10 text-muted font-monospace mt-0.5">{{ doc.size }}</div>
                          </div>
                        </div>

                        <!-- Image Preview Thumbnail jika gambar -->
                        <div v-if="isImageFile(doc.url)" class="mt-3 text-center bg-white rounded border p-2">
                          <img 
                            :src="doc.url" 
                            :alt="doc.title" 
                            class="img-fluid rounded cursor-pointer"
                            style="max-height: 180px; object-fit: contain;"
                            @click="openImagePreview(doc.url, doc.title)"
                          />
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top">
                          <button 
                            v-if="isImageFile(doc.url)"
                            type="button" 
                            class="btn btn-xs btn-outline-primary flex-1 d-inline-flex align-items-center justify-content-center gap-1"
                            @click="openImagePreview(doc.url, doc.title)"
                          >
                            <i class="ri-zoom-in-line"></i> Pratinjau
                          </button>
                          <a 
                            :href="doc.url" 
                            target="_blank" 
                            class="btn btn-xs btn-primary flex-1 d-inline-flex align-items-center justify-content-center gap-1"
                          >
                            <i class="ri-download-cloud-line"></i> Buka / Unduh
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="text-center py-5 text-muted">
                  <i class="ri-inbox-line fs-36 opacity-40 d-block mb-2"></i>
                  <p class="fs-13 mb-0">Tidak ada dokumen berkas tambahan yang terlampir pada pendaftaran ini.</p>
                </div>
              </div>

              <!-- ====================================================== -->
              <!-- TAB 3: KONTINGEN ALL-IN (JIKA TERHUBUNG)               -->
              <!-- ====================================================== -->
              <div v-if="registration.package_type === 'Paket All-In'" v-show="activeTab === 'allin'">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div>
                    <h6 class="fs-14 fw-bold text-dark mb-0">Daftar Cabang Lomba Terhubung (Paket All-In)</h6>
                    <small class="text-muted fs-11">Seluruh pendaftaran di bawah naungan kontingen sekolah ini.</small>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light fs-11 text-uppercase text-muted">
                      <tr>
                        <th>Kode</th>
                        <th>Cabang Lomba</th>
                        <th>Delegasi</th>
                        <th>Verifikasi Berkas</th>
                        <th>Status Pembayaran</th>
                        <th class="text-end">Aksi</th>
                      </tr>
                    </thead>
                    <tbody class="fs-12">
                      <!-- Baris Registrasi Saat Ini -->
                      <tr class="table-primary-subtle">
                        <td><span class="badge bg-primary text-white font-monospace fs-10">{{ registration.reg_code }}</span></td>
                        <td class="fw-bold text-dark">{{ registration.competition_title }} (Halaman Ini)</td>
                        <td class="text-muted fs-11 text-truncate" style="max-width: 140px;">{{ registration.participants_names }}</td>
                        <td>
                          <span class="badge" :class="registration.registration_status === 'Terverifikasi' ? 'bg-success' : 'bg-warning text-dark'">
                            {{ registration.registration_status }}
                          </span>
                        </td>
                        <td>
                          <span class="badge" :class="registration.payment_status === 'Terverifikasi' ? 'bg-success' : 'bg-warning text-dark'">
                            {{ registration.payment_status }}
                          </span>
                        </td>
                        <td class="text-end text-muted fs-10 fst-italic">Aktif</td>
                      </tr>

                      <!-- Baris Cabang Lain Kontingen -->
                      <tr v-for="rel in relatedRegistrations" :key="rel.id">
                        <td><span class="font-monospace text-muted fs-11">{{ rel.reg_code }}</span></td>
                        <td class="fw-semibold text-dark">{{ rel.competition_title }}</td>
                        <td class="text-muted fs-11 text-truncate" style="max-width: 140px;">{{ rel.participants_names }}</td>
                        <td>
                          <span class="badge" :class="rel.registration_status === 'Terverifikasi' ? 'bg-success' : 'bg-warning text-dark'">
                            {{ rel.registration_status }}
                          </span>
                        </td>
                        <td>
                          <span class="badge" :class="rel.payment_status === 'Terverifikasi' ? 'bg-success' : 'bg-warning text-dark'">
                            {{ rel.payment_status }}
                          </span>
                        </td>
                        <td class="text-end">
                          <Link :href="'/admin/pendaftar/' + rel.id" class="btn btn-xs btn-outline-primary">
                            Buka Verifikasi
                          </Link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- ====================================================== -->
              <!-- TAB 4: DATA TEKNIS & TM & GATE CHECK-IN                -->
              <!-- ====================================================== -->
              <div v-show="activeTab === 'operasional'">
                <div class="row g-4">
                  <!-- Col 1: Nomor Undian TM -->
                  <div class="col-md-6">
                    <div class="p-3.5 rounded-3 bg-light border">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ri-shuffle-line fs-20 text-warning"></i>
                        <h6 class="fs-13 fw-bold text-dark mb-0">Nomor Urut Undian TM</h6>
                      </div>
                      <div class="d-flex align-items-baseline gap-2 mb-2">
                        <span class="display-6 fw-bold text-dark font-monospace">
                          {{ registration.draw_number ? ('#' + registration.draw_number) : '-' }}
                        </span>
                        <span v-if="!registration.draw_number" class="text-muted fs-12 fst-italic">
                          (Belum diundi / TM belum dilaksanakan)
                        </span>
                      </div>
                      <p v-if="registration.draw_notes" class="fs-12 text-muted mb-0">
                        Catatan TM: {{ registration.draw_notes }}
                      </p>
                    </div>
                  </div>

                  <!-- Col 2: Gate Check-in -->
                  <div class="col-md-6">
                    <div class="p-3.5 rounded-3 bg-light border">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ri-door-lock-line fs-20 text-info"></i>
                        <h6 class="fs-13 fw-bold text-dark mb-0">Gate Check-in Hari-H</h6>
                      </div>
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <span 
                          class="badge fs-12 px-2.5 py-1"
                          :class="registration.checkin_status === 'sudah_hadir' ? 'bg-success' : 'bg-secondary'"
                        >
                          {{ registration.checkin_status === 'sudah_hadir' ? 'Sudah Check-in Masuk' : 'Belum Hadir di Lokasi' }}
                        </span>
                      </div>
                      <div v-if="registration.checkin_at" class="fs-11 text-muted">
                        Waktu Masuk: {{ formatDate(registration.checkin_at) }}
                      </div>
                      <div class="fs-11 text-muted mt-1 font-monospace">
                        Barcode ID: {{ registration.barcode || registration.reg_code }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================ -->
        <!-- RIGHT COLUMN: STICKY VERIFICATION DECISION & ACTIONS PANEL    -->
        <!-- ============================================================ -->
        <div class="col-lg-4">
          <div class="sticky-top" style="top: 85px; z-index: 10;">
            <!-- CARD 1: KEPUTUSAN 2 VERIFIKASI (PENDAFTARAN & PEMBAYARAN) -->
            <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
              <div class="card-header bg-primary text-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                  <h6 class="fs-13 fw-bold text-white mb-0 text-uppercase d-flex align-items-center gap-1.5" style="letter-spacing: 0.5px;">
                    <i class="ri-shield-check-line fs-16"></i> Keputusan Verifikasi Panitia
                  </h6>
                  <span class="badge bg-white text-primary fs-10 font-monospace">2 Jalur</span>
                </div>
              </div>

              <div class="card-body p-3.5">
                <!-- 1. KONTROL VERIFIKASI PENDAFTARAN & BERKAS -->
                <div class="mb-3.5">
                  <label class="form-label fs-11 fw-bold text-uppercase text-muted mb-1.5 d-flex justify-content-between align-items-center">
                    <span>1. Verifikasi Berkas & Identitas</span>
                    <span 
                      class="badge fs-10 px-2 py-0.5"
                      :class="{
                        'bg-success text-white': verifyForm.registration_status === 'Terverifikasi',
                        'bg-warning text-dark': verifyForm.registration_status === 'Menunggu Verifikasi',
                        'bg-danger text-white': verifyForm.registration_status === 'Ditolak'
                      }"
                    >
                      {{ verifyForm.registration_status }}
                    </span>
                  </label>
                  <CustomSelect 
                    v-model="verifyForm.registration_status" 
                    size="md"
                    :options="[
                      { value: 'Terverifikasi', label: 'Terverifikasi (Data & Berkas Sah)' },
                      { value: 'Menunggu Verifikasi', label: 'Menunggu Verifikasi Tambahan' },
                      { value: 'Ditolak', label: 'Ditolak / Berkas Tidak Sesuai' }
                    ]"
                  />
                  <small class="text-muted fs-10 d-block mt-1">
                    Memvalidasi kesesuaian identitas siswa, kuota peserta, dan kelengkapan berkas khusus cabang lomba.
                  </small>
                </div>

                <!-- 2. KONTROL VERIFIKASI PEMBAYARAN -->
                <div class="mb-3.5">
                  <label class="form-label fs-11 fw-bold text-uppercase text-muted mb-1.5 d-flex justify-content-between align-items-center">
                    <span>2. Verifikasi Pembayaran (Bank)</span>
                    <span 
                      class="badge fs-10 px-2 py-0.5"
                      :class="{
                        'bg-success text-white': verifyForm.payment_status === 'Terverifikasi',
                        'bg-warning text-dark': verifyForm.payment_status === 'Menunggu Verifikasi',
                        'bg-danger text-white': verifyForm.payment_status === 'Ditolak'
                      }"
                    >
                      {{ verifyForm.payment_status }}
                    </span>
                  </label>
                  <CustomSelect 
                    v-model="verifyForm.payment_status" 
                    size="md"
                    :options="[
                      { value: 'Terverifikasi', label: 'Terverifikasi (Lunas / Transfer Valid)' },
                      { value: 'Menunggu Verifikasi', label: 'Menunggu Konfirmasi Transfer' },
                      { value: 'Ditolak', label: 'Ditolak / Belum Ada Dana Masuk' }
                    ]"
                  />
                  <small class="text-muted fs-10 d-block mt-1">
                    Total Biaya: <strong>{{ formatRupiah(registration.total_fee) }}</strong> via {{ registration.payment_method || 'Transfer Bank' }}.
                  </small>
                </div>

                <!-- 3. CATATAN VERIFIKASI PANITIA -->
                <div class="mb-3.5">
                  <label class="form-label fs-11 fw-bold text-uppercase text-muted mb-1">
                    Catatan Verifikasi Panitia
                  </label>
                  <textarea 
                    v-model="verifyForm.verification_notes" 
                    rows="3" 
                    class="form-control fs-12 rounded-3"
                    placeholder="Tuliskan catatan untuk pembina (contoh: Berkas formulir lengkap, pembayaran BSI valid, dll)..."
                  ></textarea>
                </div>

                <!-- 4. TOMBOL EKSEKUSI -->
                <div class="d-flex flex-column gap-2">
                  <button 
                    type="button" 
                    class="btn btn-primary d-flex align-items-center justify-content-center gap-1.5 w-100 py-2 fs-13 fw-bold rounded-3 shadow-sm"
                    :disabled="isSubmitting"
                    @click="submitDecision()"
                  >
                    <i class="ri-save-line" v-if="!isSubmitting"></i>
                    <span class="spinner-border spinner-border-sm" v-else></span>
                    <span>{{ isSubmitting ? 'Menyimpan Keputusan...' : 'Simpan Keputusan Verifikasi' }}</span>
                  </button>

                  <div class="d-flex gap-2">
                    <button 
                      type="button" 
                      class="btn btn-success flex-1 d-flex align-items-center justify-content-center gap-1 btn-sm py-1.5 rounded-3 fw-semibold"
                      :disabled="isSubmitting"
                      @click="submitDecision('Terverifikasi', 'Terverifikasi')"
                      title="Setujui Verifikasi Pendaftaran & Pembayaran Sekaligus"
                    >
                      <i class="ri-check-double-line"></i>
                      <span>Setujui Keduanya</span>
                    </button>

                    <button 
                      type="button" 
                      class="btn btn-danger flex-1 d-flex align-items-center justify-content-center gap-1 btn-sm py-1.5 rounded-3 fw-semibold"
                      :disabled="isSubmitting"
                      @click="submitDecision('Ditolak', 'Ditolak')"
                      title="Tolak Berkas & Pembayaran"
                    >
                      <i class="ri-close-circle-line"></i>
                      <span>Tolak Berkas</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- CARD 2: DISPATCHER NOTIFIKASI WHATSAPP RESMI -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden border border-success-subtle bg-white">
              <div class="card-header bg-success-subtle p-3 border-bottom border-success-subtle">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="fw-bold fs-12 text-success d-flex align-items-center gap-1.5">
                    <i class="ri-whatsapp-line fs-16"></i>
                    <span>Notifikasi WhatsApp Resmi</span>
                  </div>
                  <span class="badge bg-success text-white fs-10 font-monospace">OpenWA</span>
                </div>
              </div>

              <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2 fs-11 text-muted">
                  <span>Penerima:</span>
                  <span class="fw-bold text-dark font-monospace">{{ registration.mentor_wa }}</span>
                </div>

                <div class="d-flex flex-column gap-1.5">
                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-success d-flex align-items-center justify-content-between text-start py-1.5 px-2.5 rounded-3 fs-11"
                    :disabled="isSendingWa"
                    @click="sendOfficialNotification('tpl_reg')"
                    title="Kirim pesan bahwa berkas pendaftaran telah terverifikasi resmi"
                  >
                    <span><i class="ri-file-check-line me-1"></i> Notif Berkas Terverifikasi</span>
                    <i class="ri-send-plane-line opacity-75"></i>
                  </button>

                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-success d-flex align-items-center justify-content-between text-start py-1.5 px-2.5 rounded-3 fs-11"
                    :disabled="isSendingWa"
                    @click="sendOfficialNotification('tpl_pay')"
                    title="Kirim pesan bahwa pembayaran pendaftaran telah lunas terverifikasi"
                  >
                    <span><i class="ri-money-dollar-circle-line me-1"></i> Notif Pembayaran Lunas</span>
                    <i class="ri-send-plane-line opacity-75"></i>
                  </button>

                  <button 
                    type="button" 
                    class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-between text-start py-1.5 px-2.5 rounded-3 fs-11"
                    :disabled="isSendingWa"
                    @click="sendOfficialNotification('tpl_reject')"
                    title="Kirim pesan bahwa berkas perlu perbaikan atau ditolak"
                  >
                    <span><i class="ri-error-warning-line me-1"></i> Notif Revisi / Tolak Berkas</span>
                    <i class="ri-send-plane-line opacity-75"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL LIGHTBOX PREVIEW GAMBAR                                  -->
    <!-- ============================================================== -->
    <div 
      v-if="previewImageModal.isOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(0,0,0,0.8); z-index: 1070;"
      @click.self="closeImagePreview"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-2xl bg-dark text-white rounded-4 overflow-hidden">
          <div class="modal-header border-secondary p-3">
            <h6 class="modal-title fs-14 text-white mb-0 d-flex align-items-center gap-2">
              <i class="ri-image-line text-primary"></i>
              <span>{{ previewImageModal.title }}</span>
            </h6>
            <button type="button" class="btn-close btn-close-white" @click="closeImagePreview"></button>
          </div>
          <div class="modal-body p-2 text-center bg-black bg-opacity-50">
            <img 
              :src="previewImageModal.url" 
              :alt="previewImageModal.title" 
              class="img-fluid rounded"
              style="max-height: 75vh; object-fit: contain;"
            />
          </div>
          <div class="modal-footer border-secondary p-2.5 d-flex justify-content-between">
            <span class="fs-11 text-muted text-truncate font-monospace" style="max-width: 320px;">
              {{ previewImageModal.url }}
            </span>
            <div class="d-flex gap-2">
              <a :href="previewImageModal.url" target="_blank" class="btn btn-sm btn-primary">
                <i class="ri-external-link-line me-1"></i> Buka Asli
              </a>
              <button type="button" class="btn btn-sm btn-secondary" @click="closeImagePreview">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.hover-shadow:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
  transform: translateY(-2px);
}
.transition-all {
  transition: all 0.2s ease-in-out;
}
.whitespace-pre-line {
  white-space: pre-line;
}
.group:hover .group-hover-opacity-100 {
  opacity: 1 !important;
}
</style>
