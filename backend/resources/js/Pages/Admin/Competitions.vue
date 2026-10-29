<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import IndoDatePicker from '@/Components/IndoDatePicker.vue';
import IndoTimePicker from '@/Components/IndoTimePicker.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  competitions: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  committeeStructure: { type: Object, default: () => ({}) },
});

const toast = useToast();
const { confirm: confirmDialog } = useConfirmDialog();
const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || 'all');
const competitionsList = ref([...props.competitions]);

// Watch for prop changes so edits and creates update immediately
watch(() => props.competitions, (newVal) => {
  competitionsList.value = newVal ? [...newVal] : [];
}, { deep: true });

// Quick Quota Expansion Modal State
const showQuotaModal = ref(false);
const quotaModalData = ref({ id: '', title: '', current_quota: 0, max_quota: 0 });
const quotaModalInput = ref(0);
const isUpdatingStatus = ref(false);

// Create Modal State
const showCreateModal = ref(false);
const createForm = useForm({
  title: '',
  id: '',
  coordinator_id: null,
  category: 'seni',
  fee: 50000,
  max_quota: 30,
  participant_type: 'individu',
  max_teams: 1,
  max_participants: 1,
  registration_deadline: '25/10/2026',
  participants_rule: '',
  tm_date: '26/10/2026 • 08:00 WIB',
  tm_date_date: '26/10/2026',
  tm_date_time: '08:00',
  contact_person: '',
  coordinators: [
    { user_id: '', name: '', contact: '', role: 'Koordinator Lomba' }
  ],
  description: '',
  status: 'open',
  registration_type: 'online',
  direct_registration_note: '',
  direct_registration_template: '',
});

// Edit Modal State
const showEditModal = ref(false);
const editForm = useForm({
  id: '',
  title: '',
  coordinator_id: null,
  category: 'seni',
  fee: 50000,
  max_quota: 30,
  participant_type: 'individu',
  max_teams: 1,
  max_participants: 1,
  registration_deadline: '',
  participants_rule: '',
  tm_date: '',
  tm_date_date: '',
  tm_date_time: '',
  contact_person: '',
  coordinators: [],
  description: '',
  status: 'open',
  registration_type: 'online',
  direct_registration_note: '',
  direct_registration_template: '',
});

// Delete Modal State
const showDeleteModal = ref(false);
const activeDeleteItem = ref(null);

// Category Management State
const showCategoryModal = ref(false);
const editingCategory = ref(false);
const categoryForm = useForm({
  old_id: '',
  id: '',
  name: '',
  label: '',
  color: 'primary',
  icon: 'ri-price-tag-3-line',
  description: '',
});

// Master default categories
const defaultMasterCategories = [
  { id: 'seni', name: 'Seni & Budaya', label: 'Bidang Seni & Budaya', color: 'primary', icon: 'ri-palette-line' },
  { id: 'olahraga', name: 'Olahraga', label: 'Bidang Olahraga', color: 'success', icon: 'ri-football-line' },
  { id: 'akademik', name: 'Sains & Akademik', label: 'Bidang Sains & Akademik', color: 'warning', icon: 'ri-book-open-line' },
  { id: 'keagamaan', name: 'Keagamaan & Da\'wah', label: 'Bidang Keagamaan & Da\'wah', color: 'info', icon: 'ri-compass-3-line' },
  { id: 'teknologi', name: 'Teknologi & E-Sport', label: 'Bidang Teknologi & E-Sport', color: 'secondary', icon: 'ri-gamepad-line' },
];

// Dynamic category list merging database categories and default masters
const categoryList = computed(() => {
  const map = new Map();
  // 1. Daftarkan kategori master bawaan
  defaultMasterCategories.forEach(d => {
    map.set(d.id, { ...d, competitions_count: 0 });
  });
  // 2. Gabungkan dengan data dari database (termasuk count cabang lomba & kategori kustom baru)
  if (Array.isArray(props.categories)) {
    props.categories.forEach(c => {
      map.set(c.id, c);
    });
  }
  return Array.from(map.values());
});

// Category pills filter
const filterCategories = computed(() => {
  return [
    { id: 'all', label: 'Semua Kategori' },
    ...categoryList.value.map(c => ({ id: c.id, label: c.label || c.name }))
  ];
});

// Category Modal Methods
function openCategoryModal() {
  resetCategoryForm();
  showCategoryModal.value = true;
}

function resetCategoryForm() {
  editingCategory.value = false;
  categoryForm.reset();
  categoryForm.clearErrors();
  categoryForm.color = 'primary';
  categoryForm.icon = 'ri-price-tag-3-line';
}

function onCategoryNameInput() {
  if (!editingCategory.value) {
    categoryForm.id = slugify(categoryForm.name);
    categoryForm.label = categoryForm.name ? `Bidang ${categoryForm.name}` : '';
  }
}

function editCategoryItem(cat) {
  editingCategory.value = true;
  categoryForm.clearErrors();
  categoryForm.old_id = cat.id;
  categoryForm.id = cat.id;
  categoryForm.name = cat.name;
  categoryForm.label = cat.label || `Bidang ${cat.name}`;
  categoryForm.color = cat.color || 'primary';
  categoryForm.icon = cat.icon || 'ri-price-tag-3-line';
  categoryForm.description = cat.description || '';
}

function submitCategory() {
  categoryForm.clearErrors();
  if (!categoryForm.name || !categoryForm.name.trim()) {
    categoryForm.setError('name', 'Nama kategori bidang lomba wajib diisi.');
    return;
  }
  if (!categoryForm.id || !categoryForm.id.trim()) {
    categoryForm.setError('id', 'Kode unik ID kategori wajib diisi.');
    return;
  }

  categoryForm.post('/admin/kategori', {
    preserveScroll: true,
    onSuccess: () => {
      resetCategoryForm();
      router.reload({ only: ['categories', 'competitions'] });
    },
    onError: () => {
      toast.error('Gagal menyimpan kategori. Silakan periksa kembali isian formulir.', 'Periksa Formulir');
    }
  });
}

async function deleteCategoryItem(cat) {
  if (cat.competitions_count > 0) {
    toast.error(`Kategori "${cat.name}" tidak dapat dihapus karena masih digunakan oleh ${cat.competitions_count} cabang lomba!`, 'Kategori Masih Digunakan');
    return;
  }
  const ok = await confirmDialog({
    title: 'Hapus Kategori Lomba?',
    message: `Apakah Anda yakin ingin menghapus kategori bidang "${cat.name}"? Tindakan ini tidak dapat dibatalkan.`,
    type: 'danger',
    confirmText: 'Ya, Hapus Kategori',
    icon: 'ri-delete-bin-line',
  });
  if (!ok) return;

  router.delete(`/admin/kategori/${cat.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (editingCategory.value && categoryForm.old_id === cat.id) {
        resetCategoryForm();
      }
      router.reload({ only: ['categories', 'competitions'] });
    },
    onError: () => {
      toast.error(`Gagal menghapus kategori "${cat.name}".`, 'Gagal Menghapus');
    }
  });
}

// Committee Leadership & Coordinator Role Helpers
function getRoleLabel(role) {
  const map = {
    'koordinator_lomba': 'Koordinator Lomba',
    'panitia': 'Panitia Pelaksana',
    'kesekretariatan': 'Kesekretariatan',
    'sekretaris': 'Sekretaris',
    'ketua_panitia': 'Ketua Panitia',
    'wakil_ketua_panitia': 'Wakil Ketua Panitia',
    'admin': 'Administrator',
    'super_admin': 'Super Admin'
  };
  return map[role] || role || 'Panitia';
}

const isCoordinatorRole = computed(() => props.committeeStructure?.is_coordinator === true);
const currentUserId = computed(() => props.committeeStructure?.current_user_id || null);
const currentUserCompId = computed(() => props.committeeStructure?.current_user_comp || null);

function isUserCoordinating(comp) {
  if (!comp) return false;
  return (comp.coordinator_id && comp.coordinator_id === currentUserId.value) ||
         (comp.id && comp.id === currentUserCompId.value);
}

function canUserEditCompetition(comp) {
  if (!isCoordinatorRole.value) return true;
  return isUserCoordinating(comp);
}

const coordinatorDropdownOptions = computed(() => {
  const list = props.committeeStructure?.committee_users || [];
  return [
    { value: '', label: '-- Pilih Koordinator dari Database --' },
    ...list.map(u => ({
      value: String(u.id),
      label: `${u.name} (${getRoleLabel(u.role)})${u.phone ? ' • ' + u.phone : ''}`
    })),
    { value: 'custom', label: '✍️ Input Manual Nama Lain (Luar Database)...' }
  ];
});

function getCoordinatorUser(userId) {
  if (!userId) return null;
  return (props.committeeStructure?.committee_users || []).find(u => String(u.id) === String(userId)) || null;
}

function syncFormCoordinatorId(form) {
  if (!form.coordinators || form.coordinators.length === 0) {
    form.coordinator_id = null;
    return;
  }
  const primaryCoord = form.coordinators.find(c => c.user_id && c.user_id !== 'custom');
  form.coordinator_id = primaryCoord ? Number(primaryCoord.user_id) : null;
}

function onCoordinatorChange(form, cIdx, selectedVal) {
  const coord = form.coordinators[cIdx];
  if (!coord) return;

  const valStr = selectedVal ? String(selectedVal) : '';
  coord.user_id = valStr;

  if (valStr && valStr !== 'custom') {
    const user = getCoordinatorUser(valStr);
    if (user) {
      coord.name = user.name;
      coord.contact = user.phone || '';
      coord.role = 'Koordinator Lomba';
    }
  } else if (valStr === 'custom') {
    if (coord.name && getCoordinatorUser(coord.user_id)) {
      coord.name = '';
      coord.contact = '';
    }
  } else {
    coord.user_id = '';
    coord.name = '';
    coord.contact = '';
  }

  syncFormCoordinatorId(form);
}

function addCoordinator(form) {
  form.coordinators.push({
    user_id: '',
    name: '',
    contact: '',
    role: 'Koordinator Lomba'
  });
}

function removeCoordinator(form, index) {
  if (form.coordinators.length > 1) {
    form.coordinators.splice(index, 1);
  } else {
    form.coordinators[0] = {
      user_id: '',
      name: '',
      contact: '',
      role: 'Koordinator Lomba'
    };
  }
  syncFormCoordinatorId(form);
}

function initializeCoordinators(coordinatorsList, coordinatorId, contactPerson) {
  const users = props.committeeStructure?.committee_users || [];
  let result = [];

  if (coordinatorsList && Array.isArray(coordinatorsList) && coordinatorsList.length > 0) {
    result = coordinatorsList.map((c, idx) => {
      let uid = c.user_id ? String(c.user_id) : '';
      if (!uid) {
        if (idx === 0 && coordinatorId) {
          uid = String(coordinatorId);
        } else if (c.name) {
          const matched = users.find(u => 
            u.name.toLowerCase().trim() === c.name.toLowerCase().trim() ||
            (c.contact && u.phone && u.phone.replace(/\D/g, '') === c.contact.replace(/\D/g, ''))
          );
          if (matched) {
            uid = String(matched.id);
          }
        }
      }

      if (!uid && c.name && c.name.trim()) {
        const found = users.find(u => String(u.id) === String(uid));
        if (!found) {
          uid = 'custom';
        }
      }

      let phone = c.contact || '';
      let cName = c.name || '';
      if (uid && uid !== 'custom') {
        const u = users.find(usr => String(usr.id) === String(uid));
        if (u) {
          if (!phone && u.phone) phone = u.phone;
          if (!cName) cName = u.name;
        }
      }

      return {
        user_id: uid,
        name: cName,
        contact: phone,
        role: c.role || 'Koordinator Lomba'
      };
    });
  } else if (coordinatorId) {
    const u = users.find(usr => usr.id == coordinatorId);
    if (u) {
      result = [
        {
          user_id: String(u.id),
          name: u.name,
          contact: u.phone || '',
          role: u.role === 'koordinator_lomba' ? 'Koordinator Lomba' : (getRoleLabel(u.role) || 'Koordinator Lomba')
        }
      ];
    }
  }

  if (result.length === 0) {
    if (contactPerson) {
      result = [
        {
          user_id: 'custom',
          name: contactPerson,
          contact: '',
          role: 'Koordinator Lomba'
        }
      ];
    } else {
      result = [
        {
          user_id: '',
          name: '',
          contact: '',
          role: 'Koordinator Lomba'
        }
      ];
    }
  }

  return result;
}

// Filtered competitions
const filteredCompetitions = computed(() => {
  return competitionsList.value.filter(comp => {
    const q = searchQuery.value.toLowerCase().trim();
    const matchQuery = !q ||
      comp.title?.toLowerCase().includes(q) ||
      comp.id?.toLowerCase().includes(q) ||
      comp.category?.toLowerCase().includes(q);

    const matchCategory = selectedCategory.value === 'all' || comp.category === selectedCategory.value;

    return matchQuery && matchCategory;
  });
});

// Format currency
function formatRupiah(num) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}

// Format Date string: dd/mm/yyyy
function formatDate(dateStr) {
  if (!dateStr) return '-';
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return dateStr;
  const m = String(dateStr).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (m) {
    return `${m[3]}/${m[2]}/${m[1]}`;
  }
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

// Cek apakah tanggal batas penutupan pendaftaran telah lewat
function isDeadlinePassed(dateStr) {
  if (!dateStr || dateStr === '-' || !String(dateStr).trim()) return false;
  
  let targetDate = null;
  // Format dd/mm/yyyy
  const ddmmyyyy = String(dateStr).match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
  if (ddmmyyyy) {
    const day = parseInt(ddmmyyyy[1], 10);
    const month = parseInt(ddmmyyyy[2], 10) - 1;
    const year = parseInt(ddmmyyyy[3], 10);
    targetDate = new Date(year, month, day, 23, 59, 59, 999);
  } else {
    // Format yyyy-mm-dd
    const yyyymmdd = String(dateStr).match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
    if (yyyymmdd) {
      const year = parseInt(yyyymmdd[1], 10);
      const month = parseInt(yyyymmdd[2], 10) - 1;
      const day = parseInt(yyyymmdd[3], 10);
      targetDate = new Date(year, month, day, 23, 59, 59, 999);
    } else {
      const parsed = new Date(dateStr);
      if (!isNaN(parsed.getTime())) {
        targetDate = parsed;
      }
    }
  }

  if (!targetDate) return false;
  return new Date() > targetDate;
}

// Cek apakah kapasitas kuota telah penuh
function isQuotaFull(comp) {
  return comp && comp.max_quota > 0 && comp.current_quota >= comp.max_quota;
}

// Parse TM string into { date: 'dd/mm/yyyy', time: 'HH:mm' } murni format 24 jam
function parseTmDate(str) {
  if (!str || str === '-' || !String(str).trim()) {
    return { date: '', time: '08:00' };
  }

  const s = String(str).trim();
  let date = '';
  let time = '';

  // 1. Extract Time (HH:mm strictly in 24-hour format, converting any AM/PM)
  const ampm = s.match(/(\d{1,2})[:.](\d{2})\s*(am|pm)/i);
  if (ampm) {
    let h = parseInt(ampm[1], 10);
    const m = ampm[2];
    const isPm = ampm[3].toLowerCase() === 'pm';
    if (isPm && h < 12) h += 12;
    if (!isPm && h === 12) h = 0;
    time = `${String(h).padStart(2, '0')}:${m}`;
  } else {
    const timeMatch = s.match(/(?:pukul\s+)?(\d{1,2})[:.](\d{2})/i);
    if (timeMatch) {
      let hh = parseInt(timeMatch[1], 10);
      const mm = timeMatch[2];
      if (hh > 23) hh = 23;
      time = `${String(hh).padStart(2, '0')}:${mm}`;
    }
  }

  // 2. Extract Date
  const ddmmyyyy = s.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
  if (ddmmyyyy) {
    const dd = ddmmyyyy[1].padStart(2, '0');
    const mm = ddmmyyyy[2].padStart(2, '0');
    const yyyy = ddmmyyyy[3];
    date = `${dd}/${mm}/${yyyy}`;
  } else {
    const yyyymmdd = s.match(/(\d{4})-(\d{2})-(\d{2})/);
    if (yyyymmdd) {
      date = `${yyyymmdd[3]}/${yyyymmdd[2]}/${yyyymmdd[1]}`;
    } else {
      const monthNames = {
        januari: '01', jan: '01', februari: '02', feb: '02', maret: '03', mar: '03',
        april: '04', apr: '04', mei: '05', juni: '06', jun: '06',
        juli: '07', jul: '07', agustus: '08', agt: '08', agu: '08',
        september: '09', sep: '09', oktober: '10', okt: '10',
        november: '11', nov: '11', desember: '12', des: '12'
      };
      const indoMatch = s.match(/(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})/);
      if (indoMatch) {
        const dd = indoMatch[1].padStart(2, '0');
        const mStr = indoMatch[2].toLowerCase();
        const yyyy = indoMatch[3];
        if (monthNames[mStr]) {
          date = `${dd}/${monthNames[mStr]}/${yyyy}`;
        }
      }
    }
  }

  if (!date) {
    date = '26/10/2026';
  }
  if (!time) {
    time = '08:00';
  }

  return { date, time };
}

// Format combined TM string for storage and display (murni format 24 jam WIB)
function formatTmString(date, time) {
  const cleanDate = date ? String(date).trim() : '';
  let cleanTime = time ? String(time).trim() : '';
  
  if (cleanTime) {
    const ampm = cleanTime.match(/(\d{1,2})[:.](\d{2})\s*(am|pm)/i);
    if (ampm) {
      let h = parseInt(ampm[1], 10);
      const m = ampm[2];
      const isPm = ampm[3].toLowerCase() === 'pm';
      if (isPm && h < 12) h += 12;
      if (!isPm && h === 12) h = 0;
      cleanTime = `${String(h).padStart(2, '0')}:${m}`;
    } else {
      const m = cleanTime.match(/(\d{1,2})[:.](\d{2})/);
      if (m) {
        cleanTime = `${m[1].padStart(2, '0')}:${m[2]}`;
      }
    }
  }

  if (!cleanDate && !cleanTime) return '';
  if (cleanDate && cleanTime) {
    return `${cleanDate} • ${cleanTime} WIB`;
  }
  if (cleanDate && !cleanTime) {
    return `${cleanDate} • 08:00 WIB`;
  }
  return `Pukul ${cleanTime} WIB`;
}

function syncCreateTm() {
  createForm.tm_date = formatTmString(createForm.tm_date_date, createForm.tm_date_time);
}

function syncEditTm() {
  editForm.tm_date = formatTmString(editForm.tm_date_date, editForm.tm_date_time);
}

function setCreateTmTime(timeStr) {
  createForm.tm_date_time = timeStr;
  syncCreateTm();
}

function setEditTmTime(timeStr) {
  editForm.tm_date_time = timeStr;
  syncEditTm();
}

// Open Create Modal
function openCreateModal() {
  createForm.reset();
  createForm.clearErrors();
  createForm.coordinator_id = null;
  createForm.participant_type = 'individu';
  createForm.max_teams = 1;
  createForm.max_participants = 1;
  createForm.registration_deadline = '25/10/2026';
  createForm.tm_date_date = '26/10/2026';
  createForm.tm_date_time = '08:00';
  createForm.tm_date = '26/10/2026 • 08:00 WIB';
  createForm.category = categoryList.value[0]?.id || 'seni';
  createForm.coordinators = [
    { user_id: '', name: '', contact: '', role: 'Koordinator Lomba' }
  ];
  createForm.registration_type = 'online';
  createForm.direct_registration_note = '';
  createForm.direct_registration_template = '';
  showCreateModal.value = true;
}

const defaultDirectTemplate = "Assalamu'alaikum Wr. Wb. Kak {nama_pj},\\n\\nSaya perwakilan dari {nama_sekolah}, bermaksud mendaftarkan delegasi sekolah kami untuk cabang lomba *{cabang_lomba}* pada agenda *{nama_event}*.\\n\\n• Asal Lembaga: {nama_sekolah}\\n• Cabang Lomba: {cabang_lomba}\\n• Biaya Pendaftaran: {biaya}\\n• Narahubung/Pembina: {nama_pendaftar} ({nomor_wa})\\n\\nMohon petunjuk teknis pendaftaran langsung, persyaratan berkas, serta ketersediaan kuota delegasi. Terima kasih.\\n\\nWassalamu'alaikum Wr. Wb.";

function applyDefaultDirectTemplate(targetForm) {
  targetForm.direct_registration_template = defaultDirectTemplate;
  toast.success('Template pesan WhatsApp standar berhasil diterapkan ke formulir.');
}

function insertTemplateTag(targetForm, tag) {
  if (!targetForm.direct_registration_template) {
    targetForm.direct_registration_template = '';
  }
  targetForm.direct_registration_template += (targetForm.direct_registration_template.length > 0 ? ' ' : '') + tag;
}

// Auto-fill ID slug from title in Create Form
function onTitleInput() {
  if (!createForm.id || createForm.id === slugify(createForm.title.slice(0, -1))) {
    createForm.id = slugify(createForm.title);
  }
}

function slugify(text) {
  return text
    .toString()
    .toLowerCase()
    .trim()
    .replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-');
}

// Submit Create Form
function submitCreate() {
  createForm.clearErrors();
  if (!createForm.title || !createForm.title.trim()) {
    createForm.setError('title', 'Nama cabang lomba wajib diisi.');
  }
  if (!createForm.id || !createForm.id.trim()) {
    createForm.setError('id', 'Kode ID cabang lomba wajib diisi.');
  }
  if (createForm.fee === '' || createForm.fee === null || createForm.fee === undefined) {
    createForm.setError('fee', 'Biaya pendaftaran wajib ditentukan (masukkan 0 jika gratis).');
  }
  if (!createForm.max_quota) {
    createForm.setError('max_quota', 'Kapasitas kuota maksimal delegasi wajib ditentukan.');
  }
  if (!createForm.registration_deadline) {
    createForm.setError('registration_deadline', 'Tanggal batas tutup pendaftaran wajib diisi.');
  }
  if (createForm.hasErrors) {
    toast.error('Mohon perbaiki kolom isian yang belum lengkap atau bertanda merah.', 'Periksa Formulir');
    return;
  }

  // Sinkronisasi hak akses dan pastikan nama & kontak terisi jika bersumber dari database
  syncFormCoordinatorId(createForm);
  createForm.coordinators.forEach(coord => {
    if (coord.user_id && coord.user_id !== 'custom') {
      const u = getCoordinatorUser(coord.user_id);
      if (u) {
        if (!coord.name) coord.name = u.name;
        if (!coord.contact && u.phone) coord.contact = u.phone;
      }
    }
  });

  createForm.tm_date = formatTmString(createForm.tm_date_date, createForm.tm_date_time);

  createForm.post('/admin/lomba', {
    preserveScroll: true,
    onSuccess: () => {
      showCreateModal.value = false;
      createForm.reset();
      router.reload({ only: ['competitions', 'stats', 'categories'] });
    },
    onError: () => {
      toast.error('Gagal menyimpan data lomba. Mohon periksa kembali isian kolom bertanda merah.', 'Periksa Formulir');
    }
  });
}

// Open Edit Modal
function openEditModal(comp) {
  editForm.clearErrors();
  editForm.id = comp.id;
  editForm.coordinator_id = comp.coordinator_id || null;
  editForm.title = comp.title;
  editForm.category = comp.category;
  editForm.fee = comp.fee;
  editForm.max_quota = comp.max_quota;

  const detectedType = comp.participant_type || (comp.category === 'olahraga' || (comp.participants_rule && comp.participants_rule.toLowerCase().includes('tim')) ? 'tim' : 'individu');
  editForm.participant_type = detectedType;
  editForm.max_teams = comp.max_teams || 1;
  editForm.max_participants = comp.max_participants || (detectedType === 'tim' ? 10 : 1);
  editForm.registration_deadline = formatDate(comp.registration_deadline) || '25/10/2026';
  editForm.participants_rule = comp.participants_rule || '';
  
  const parsedTm = parseTmDate(comp.tm_date);
  editForm.tm_date_date = parsedTm.date || '26/10/2026';
  editForm.tm_date_time = parsedTm.time || (comp.id === 'mobile-legends' ? '14:00' : '08:00');
  editForm.tm_date = formatTmString(editForm.tm_date_date, editForm.tm_date_time) || (comp.tm_date || '26/10/2026 • 08:00 WIB');
  
  editForm.status = comp.status;
  editForm.registration_type = comp.registration_type || 'online';
  editForm.direct_registration_note = comp.direct_registration_note || '';
  editForm.direct_registration_template = comp.direct_registration_template || '';
  editForm.description = comp.description || '';

  editForm.coordinators = initializeCoordinators(comp.coordinators, comp.coordinator_id, comp.contact_person);
  syncFormCoordinatorId(editForm);

  showEditModal.value = true;
}

// Submit Edit Form
function submitEdit() {
  editForm.clearErrors();
  if (!editForm.title || !editForm.title.trim()) {
    editForm.setError('title', 'Nama cabang lomba wajib diisi.');
  }
  if (editForm.fee === '' || editForm.fee === null || editForm.fee === undefined) {
    editForm.setError('fee', 'Biaya pendaftaran wajib ditentukan.');
  }
  if (!editForm.max_quota) {
    editForm.setError('max_quota', 'Kapasitas kuota maksimal delegasi wajib ditentukan.');
  }
  if (editForm.hasErrors) {
    toast.error('Mohon perbaiki kolom isian yang belum lengkap atau bertanda merah.', 'Periksa Formulir');
    return;
  }

  // Sinkronisasi hak akses dan pastikan nama & kontak terisi jika bersumber dari database
  syncFormCoordinatorId(editForm);
  editForm.coordinators.forEach(coord => {
    if (coord.user_id && coord.user_id !== 'custom') {
      const u = getCoordinatorUser(coord.user_id);
      if (u) {
        if (!coord.name) coord.name = u.name;
        if (!coord.contact && u.phone) coord.contact = u.phone;
      }
    }
  });

  editForm.tm_date = formatTmString(editForm.tm_date_date, editForm.tm_date_time);

  editForm.put(`/admin/lomba/${editForm.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false;
      // Immediately reflect edits locally in array
      const idx = competitionsList.value.findIndex(c => c.id === editForm.id);
      if (idx !== -1) {
        competitionsList.value[idx] = {
          ...competitionsList.value[idx],
          coordinator_id: editForm.coordinator_id,
          coordinator: editForm.coordinator_id ? getCoordinatorUser(editForm.coordinator_id) : competitionsList.value[idx].coordinator,
          title: editForm.title,
          category: editForm.category,
          fee: Number(editForm.fee),
          max_quota: Number(editForm.max_quota),
          participant_type: editForm.participant_type,
          max_teams: Number(editForm.max_teams || 1),
          max_participants: Number(editForm.max_participants),
          registration_deadline: editForm.registration_deadline,
          participants_rule: editForm.participants_rule,
          tm_date: editForm.tm_date,
          coordinators: JSON.parse(JSON.stringify(editForm.coordinators)),
          status: editForm.status,
          description: editForm.description,
        };
      }
      router.reload({ only: ['competitions', 'stats', 'categories'] });
    },
    onError: () => {
      toast.error('Gagal memperbarui data lomba. Mohon periksa isian kolom bertanda merah.', 'Periksa Formulir');
    }
  });
}

// Handle Status Toggle with Business Logic
function handleStatusToggle(comp) {
  if (comp.status === 'open' && comp.current_quota < comp.max_quota) {
    // Currently open and slots available -> Admin wants to close
    executeToggleStatus(comp.id, 'closed');
  } else {
    // Admin wants to open
    if (comp.current_quota >= comp.max_quota) {
      // SPECIAL LOGIC: Quota is already full -> Open Quota Expansion Modal
      quotaModalData.value = {
        id: comp.id,
        title: comp.title,
        current_quota: comp.current_quota,
        max_quota: comp.max_quota,
      };
      // Suggest expanding by +5 slots
      quotaModalInput.value = comp.max_quota + 5;
      showQuotaModal.value = true;
    } else {
      // Quota is not full -> Directly open
      executeToggleStatus(comp.id, 'open');
    }
  }
}

// Quick add buttons in Quota Modal
function quickAddQuota(amount) {
  const minAllowed = quotaModalData.value.current_quota + 1;
  const base = Math.max(quotaModalInput.value, quotaModalData.value.max_quota);
  quotaModalInput.value = Math.max(minAllowed, base + amount);
}

// Submit Quota Modal
function submitQuotaModal() {
  if (!quotaModalInput.value || quotaModalInput.value <= quotaModalData.value.current_quota) {
    toast.error(`Kapasitas kuota baru minimal harus ${quotaModalData.value.current_quota + 1} (melebihi jumlah delegasi terisi saat ini).`, 'Kapasitas Kuota Kurang');
    return;
  }

  const targetId = quotaModalData.value.id;
  const newQuota = quotaModalInput.value;
  showQuotaModal.value = false;
  executeToggleStatus(targetId, 'open', newQuota);
}

// Execute Toggle Status via Inertia
function executeToggleStatus(compId, targetStatus, newMaxQuota = null) {
  isUpdatingStatus.value = true;
  const payload = { status: targetStatus };
  if (newMaxQuota) {
    payload.max_quota = newMaxQuota;
  }

  router.post(`/admin/toggle-quota/${compId}`, payload, {
    preserveScroll: true,
    onSuccess: () => {
      isUpdatingStatus.value = false;
      const target = competitionsList.value.find(c => c.id === compId);
      if (target) {
        target.status = targetStatus;
        if (newMaxQuota) {
          target.max_quota = Number(newMaxQuota);
        }
      }
    },
    onError: () => {
      isUpdatingStatus.value = false;
      toast.error('Gagal memperbarui status pendaftaran cabang lomba.', 'Terjadi Kesalahan');
    }
  });
}

// Open Delete Modal
function openDeleteModal(comp) {
  activeDeleteItem.value = comp;
  showDeleteModal.value = true;
}

// Confirm Delete
function confirmDelete() {
  if (!activeDeleteItem.value) return;
  const deletedId = activeDeleteItem.value.id;
  router.delete(`/admin/lomba/${deletedId}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      competitionsList.value = competitionsList.value.filter(c => c.id !== deletedId);
      activeDeleteItem.value = null;
      router.reload({ only: ['competitions', 'stats', 'categories'] });
    },
    onError: () => {
      toast.error('Tidak dapat menghapus cabang lomba.', 'Gagal Menghapus');
    }
  });
}
</script>

<template>
  <VelzonLayout>
    <Head title="Manajemen Cabang Lomba & Kuota | Velzon" />

    <!-- ============================================================== -->
    <!-- PAGE TITLE & BREADCRUMB                                        -->
    <!-- ============================================================== -->
    <div class="row mb-4 align-items-center">
      <div class="col-md-7">
        <div class="d-flex align-items-center gap-2 mb-1">
          <Link href="/admin" class="text-muted text-decoration-none fs-13">Dashboard</Link>
          <span class="text-muted fs-12">/</span>
          <span class="text-dark fw-semibold fs-13">Manajemen Lomba</span>
        </div>
        <h4 class="fs-18 fw-bold mb-1 text-dark">Manajemen Cabang Lomba & Kuota</h4>
        <p class="text-muted mb-0 fs-13">
          Konfigurasi aturan delegasi (individu/tim), batas tutup pendaftaran, koordinator lomba, kuota, dan biaya pendaftaran.
        </p>
      </div>
      <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-2">
        <Link 
          href="/admin/form-builder" 
          class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1 shadow-sm"
          title="Atur formulir pendaftaran dinamis untuk tiap cabang lomba"
        >
          <i class="ri-survey-line fs-14"></i> Formulir Builder
        </Link>
        <Link 
          href="/admin/tm" 
          class="btn btn-sm btn-outline-info d-inline-flex align-items-center gap-1 shadow-sm"
          title="Kelola jadwal Technical Meeting, ketentuan teknis, & pengundian nomor urut peserta"
        >
          <i class="ri-calendar-todo-line fs-14"></i> Manajemen TM
        </Link>
        <button 
          type="button" 
          class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 shadow-sm"
          @click="openCategoryModal"
        >
          <i class="ri-folder-settings-line fs-14"></i> Kelola Kategori Bidang
        </button>
        <Link href="/admin/pendaftar" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
          <i class="ri-team-line"></i> Data Pendaftar
        </Link>
        <button 
          v-if="!isCoordinatorRole"
          type="button" 
          class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm"
          @click="openCreateModal"
        >
          <i class="ri-add-line fs-14"></i> Tambah Cabang Lomba
        </button>
        <span 
          v-else 
          class="badge bg-light text-muted border py-1.5 px-2 fs-11 d-inline-flex align-items-center gap-1"
          title="Pembuatan cabang lomba baru merupakan wewenang Ketua Panitia, Wakil Ketua, atau Sekretaris"
        >
          <i class="ri-lock-line text-warning"></i> Tambah Lomba (Wewenang Pimpinan)
        </span>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- STRUKTUR KEPANITIAAN & PENGAWASAN LOMBA                       -->
    <!-- ============================================================== -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-left: 4px solid #6366f1 !important;">
          <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <div>
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-primary-subtle text-primary fs-11 px-2 py-0.5 fw-semibold">
                    <i class="ri-shield-user-line me-1"></i> Pengawasan & Manajemen Panitia
                  </span>
                  <span v-if="isCoordinatorRole" class="badge bg-warning text-dark fs-11 px-2 py-0.5 fw-semibold">
                    <i class="ri-user-star-line me-1"></i> Peran: Koordinator Cabang Lomba
                  </span>
                </div>
                <h5 class="fs-15 fw-bold text-dark mt-1 mb-0">
                  Struktur Pimpinan Panitia & Penanggung Jawab Lomba
                </h5>
                <p class="text-muted fs-12 mb-0">
                  Pengelolaan regulasi, juknis, kuota, serta Technical Meeting berada di bawah koordinasi Ketua Panitia, Wakil Ketua, Sekretaris, dan Koordinator Lomba terkait.
                </p>
              </div>
              <div v-if="isCoordinatorRole" class="alert alert-warning py-1.5 px-3 mb-0 fs-12 border-warning-subtle d-flex align-items-center gap-2">
                <i class="ri-information-line fs-16 text-warning"></i>
                <span>Anda memiliki kewenangan mengelola cabang lomba yang Anda koordinasi. Penambahan cabang lomba baru diatur oleh Pimpinan Panitia.</span>
              </div>
            </div>

            <!-- Leadership Mini-Cards Grid -->
            <div class="row g-2 mt-1">
              <!-- Ketua Panitia -->
              <div class="col-md-4">
                <div class="p-2.5 rounded border bg-light d-flex align-items-center gap-2.5 h-100">
                  <div class="avatar-xs flex-shrink-0">
                    <span class="avatar-title rounded-circle bg-danger-subtle text-danger fw-bold fs-13">
                      <i class="ri-vip-crown-line"></i>
                    </span>
                  </div>
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted fs-10 text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Ketua Panitia</div>
                    <div class="fw-bold text-dark fs-12 text-truncate">
                      {{ committeeStructure?.ketua_panitia?.[0]?.name || 'Belum Ditugaskan' }}
                    </div>
                    <div v-if="committeeStructure?.ketua_panitia?.[0]?.phone" class="fs-11">
                      <a :href="getWaChatUrl(committeeStructure.ketua_panitia[0].phone, 'Halo Ketua Panitia Semarak Aliyah XIV: ')" target="_blank" class="text-success text-decoration-none">
                        <i class="ri-whatsapp-line me-0.5"></i>{{ committeeStructure.ketua_panitia[0].phone }}
                      </a>
                    </div>
                  </div>
                  <span class="badge bg-danger-subtle text-danger fs-10">Pimpinan</span>
                </div>
              </div>

              <!-- Wakil Ketua Panitia -->
              <div class="col-md-4">
                <div class="p-2.5 rounded border bg-light d-flex align-items-center gap-2.5 h-100">
                  <div class="avatar-xs flex-shrink-0">
                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary fw-bold fs-13">
                      <i class="ri-shield-star-line"></i>
                    </span>
                  </div>
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted fs-10 text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Wakil Ketua Panitia</div>
                    <div class="fw-bold text-dark fs-12 text-truncate">
                      {{ committeeStructure?.wakil_ketua_panitia?.[0]?.name || 'Belum Ditugaskan' }}
                    </div>
                    <div v-if="committeeStructure?.wakil_ketua_panitia?.[0]?.phone" class="fs-11">
                      <a :href="getWaChatUrl(committeeStructure.wakil_ketua_panitia[0].phone, 'Halo Wakil Ketua Panitia Semarak Aliyah XIV: ')" target="_blank" class="text-success text-decoration-none">
                        <i class="ri-whatsapp-line me-0.5"></i>{{ committeeStructure.wakil_ketua_panitia[0].phone }}
                      </a>
                    </div>
                  </div>
                  <span class="badge bg-primary-subtle text-primary fs-10">Wakil</span>
                </div>
              </div>

              <!-- Sekretaris Panitia -->
              <div class="col-md-4">
                <div class="p-2.5 rounded border bg-light d-flex align-items-center gap-2.5 h-100">
                  <div class="avatar-xs flex-shrink-0">
                    <span class="avatar-title rounded-circle bg-info-subtle text-info fw-bold fs-13">
                      <i class="ri-file-list-3-line"></i>
                    </span>
                  </div>
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="text-muted fs-10 text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Sekretaris Panitia</div>
                    <div class="fw-bold text-dark fs-12 text-truncate">
                      {{ committeeStructure?.sekretaris?.[0]?.name || 'Belum Ditugaskan' }}
                    </div>
                    <div v-if="committeeStructure?.sekretaris?.[0]?.phone" class="fs-11">
                      <a :href="getWaChatUrl(committeeStructure.sekretaris[0].phone, 'Halo Sekretaris Panitia Semarak Aliyah XIV: ')" target="_blank" class="text-success text-decoration-none">
                        <i class="ri-whatsapp-line me-0.5"></i>{{ committeeStructure.sekretaris[0].phone }}
                      </a>
                    </div>
                  </div>
                  <span class="badge bg-info-subtle text-info fs-10">Administrasi</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- 4 KPI SUMMARY METRIC CARDS                                     -->
    <!-- ============================================================== -->
    <div class="row">
      <!-- Card 1: Total Cabang Lomba -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Total Cabang Lomba</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">
                  {{ stats.total_competitions || competitionsList.length }}
                  <span class="fs-12 text-muted fw-normal">Kompetisi</span>
                </h4>
                <span class="badge bg-primary-subtle text-primary fs-11 mt-2">
                  <i class="ri-medal-line align-middle me-1"></i> Edisi XIV 2026
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-primary-subtle text-primary rounded fs-20">
                  <i class="ri-trophy-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 2: Status Cabang Aktif -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Status Pendaftaran</p>
                <h4 class="fs-22 fw-bold mb-0 text-success">
                  {{ stats.open_count || competitionsList.filter(c => c.status === 'open').length }}
                  <span class="fs-12 text-muted fw-normal">Buka / {{ stats.closed_count || competitionsList.filter(c => c.status === 'closed').length }} Tutup</span>
                </h4>
                <span class="badge bg-success-subtle text-success fs-11 mt-2">
                  <i class="ri-checkbox-circle-line align-middle me-1"></i> Menerima Delegasi
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                  <i class="ri-door-open-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3: Total Kuota Delegasi -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Keterisian Kuota</p>
                <h4 class="fs-22 fw-bold mb-0 text-dark">
                  {{ stats.total_quota_used || 0 }} 
                  <span class="fs-12 text-muted fw-normal">/ {{ stats.total_quota_max || 0 }} Slot</span>
                </h4>
                <div class="progress mt-2" style="height: 6px; width: 140px;">
                  <div 
                    class="progress-bar bg-warning" 
                    role="progressbar" 
                    :style="{ width: `${stats.quota_filled_percentage || 0}%` }"
                  ></div>
                </div>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-warning-subtle text-warning rounded fs-20">
                  <i class="ri-pie-chart-2-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 4: Persentase Kuota -->
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card card-animate border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <p class="text-uppercase fw-semibold text-muted fs-12 mb-1">Persentase Terisi</p>
                <h4 class="fs-22 fw-bold mb-0 text-primary">
                  {{ stats.quota_filled_percentage || 0 }}%
                </h4>
                <span class="badge bg-info-subtle text-info fs-11 mt-2">
                  <i class="ri-line-chart-line align-middle me-1"></i> Kapasitas Maksimal
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-info-subtle text-info rounded fs-20">
                  <i class="ri-dashboard-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION: COMPETITIONS TABLE & CONTROLS                         -->
    <!-- ============================================================== -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <!-- Card Header with Filters & Search -->
          <div class="card-header bg-white border-bottom py-3">
            <div class="row align-items-center g-3">
              <div class="col-md-5">
                <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                  <i class="ri-list-settings-line text-primary me-1"></i> Daftar Manajemen Cabang Lomba
                </h5>
                <span class="text-muted fs-11">Menampilkan {{ filteredCompetitions.length }} cabang kompetisi aktif</span>
              </div>

              <!-- Search Box -->
              <div class="col-md-7">
                <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                  <div class="position-relative" style="min-width: 250px;">
                    <input 
                      v-model="searchQuery" 
                      type="text" 
                      class="form-control form-control-sm ps-4" 
                      placeholder="Cari cabang lomba, slug, bidang..."
                    />
                    <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-2 text-muted fs-12"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Category Pills Filter -->
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top">
              <button 
                v-for="cat in filterCategories" 
                :key="cat.id"
                type="button"
                @click="selectedCategory = cat.id"
                class="btn btn-xs rounded-pill"
                :class="selectedCategory === cat.id ? 'btn-primary' : 'btn-outline-secondary'"
              >
                {{ cat.label }}
              </button>
            </div>
          </div>

          <!-- Card Body: Table -->
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr class="text-uppercase fs-11" style="letter-spacing: 0.5px;">
                    <th scope="col" style="width: 40px;" class="text-center py-2.5">#</th>
                    <th scope="col" class="py-2.5">Cabang Lomba</th>
                    <th scope="col" class="py-2.5">Format Delegasi</th>
                    <th scope="col" class="py-2.5">Batas Pendaftaran</th>
                    <th scope="col" class="py-2.5">Biaya & Kuota</th>
                    <th scope="col" class="py-2.5">Koordinator / PJ</th>
                    <th scope="col" class="text-center py-2.5">Status Pendaftaran</th>
                    <th scope="col" style="width: 130px;" class="text-center py-2.5">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredCompetitions.length === 0">
                    <td colspan="8" class="text-center py-5 text-muted fs-13">
                      <i class="ri-search-eye-line fs-28 d-block mb-1 text-muted"></i>
                      Tidak ada cabang lomba yang sesuai dengan filter atau kata kunci.
                    </td>
                  </tr>
                  <tr v-for="(comp, idx) in filteredCompetitions" :key="comp.id">
                    <td class="text-center text-muted fs-12">{{ idx + 1 }}</td>
                    <td>
                      <div class="fw-medium text-dark fs-13">{{ comp.title }}</div>
                      <div class="d-flex align-items-center gap-1.5 mt-0.5 flex-wrap">
                        <span class="badge bg-light text-muted border fs-10 font-mono">{{ comp.id }}</span>
                        <span 
                          class="badge fs-10 text-uppercase fw-semibold"
                          :class="{
                            'bg-primary-subtle text-primary': comp.category === 'seni',
                            'bg-success-subtle text-success': comp.category === 'olahraga',
                            'bg-warning-subtle text-warning': comp.category === 'akademik',
                            'bg-info-subtle text-info': comp.category === 'keagamaan',
                            'bg-secondary-subtle text-secondary': !['seni', 'olahraga', 'akademik', 'keagamaan'].includes(comp.category)
                          }"
                        >
                          {{ comp.category }}
                        </span>
                        <Link 
                          :href="`/admin/form-builder/${comp.id}`"
                          class="badge bg-warning-subtle text-warning border border-warning-subtle text-decoration-none fs-10 d-inline-flex align-items-center gap-1"
                          :title="`Buka Form Builder untuk ${comp.title}`"
                        >
                          <i class="ri-survey-line fs-10"></i> {{ comp.custom_fields && comp.custom_fields.length > 0 ? `${comp.custom_fields.length} Field Khusus` : 'Form Standar' }}
                        </Link>
                        <span 
                          v-if="comp.registration_type === 'direct_contact'"
                          class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10 d-inline-flex align-items-center gap-1"
                          :title="comp.direct_registration_note || 'Pendaftaran khusus kontak langsung ke koordinator'"
                        >
                          <i class="ri-whatsapp-line"></i> Kontak Langsung
                        </span>
                      </div>
                    </td>

                    <!-- Format Delegasi -->
                    <td>
                      <div class="d-flex align-items-center gap-1.5 flex-wrap">
                        <span 
                          class="badge fs-11"
                          :class="comp.participant_type === 'tim' ? 'bg-info text-white' : 'bg-primary text-white'"
                        >
                          <i :class="comp.participant_type === 'tim' ? 'ri-team-line' : 'ri-user-line'" class="me-1"></i>
                          {{ comp.participant_type === 'tim' ? 'Tim / Regu' : 'Individu' }}
                        </span>
                        <span v-if="comp.participant_type === 'tim'" class="fw-normal text-dark fs-12">
                          (Maks {{ comp.max_teams || 1 }} Tim) dan (Maks {{ comp.max_participants || 10 }} Anggota)
                        </span>
                        <span v-else class="fw-normal text-dark fs-12">
                          (Maks {{ comp.max_participants || 1 }} Peserta)
                        </span>
                      </div>
                      <small class="text-muted fs-11 d-block mt-0.5">
                        {{ comp.participants_rule || (comp.participant_type === 'tim' ? `Maks ${comp.max_teams || 1} Tim per Lembaga` : '1 Delegasi') }}
                      </small>
                    </td>

                    <!-- Batas Pendaftaran -->
                    <td>
                      <div class="d-flex align-items-center gap-1">
                        <i 
                          class="ri-calendar-event-line fs-14"
                          :class="(comp.is_deadline_passed || isDeadlinePassed(comp.registration_deadline)) ? 'text-danger' : 'text-primary'"
                        ></i>
                        <span class="fw-semibold fs-12 text-dark">{{ formatDate(comp.registration_deadline) }}</span>
                        <span 
                          v-if="comp.is_deadline_passed || isDeadlinePassed(comp.registration_deadline)" 
                          class="badge bg-danger-subtle text-danger border border-danger-subtle fs-9 px-1.5 py-0.2 ms-1"
                          title="Batas waktu pendaftaran telah berakhir"
                        >
                          Lewat
                        </span>
                      </div>
                      <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                        <Link 
                          :href="`/admin/tm?comp=${comp.id}`"
                          class="badge bg-info-subtle text-info border border-info-subtle text-decoration-none fs-10 d-inline-flex align-items-center gap-1"
                          :title="`Buka Manajemen TM & Pengundian: ${comp.title}`"
                        >
                          <i class="ri-calendar-todo-line fs-10"></i> TM: {{ comp.tm_date || 'Atur Jadwal TM' }}
                        </Link>
                        <span 
                          v-if="comp.resolved_tm_settings?.status === 'completed'"
                          class="badge bg-success-subtle text-success border border-success-subtle fs-9 px-1.5"
                          title="Technical Meeting telah selesai"
                        >
                          <i class="ri-check-line"></i> Selesai
                        </span>
                        <span 
                          v-else-if="comp.resolved_tm_settings?.status === 'ongoing'"
                          class="badge bg-primary-subtle text-primary border border-primary-subtle fs-9 px-1.5"
                          title="Technical Meeting sedang berlangsung"
                        >
                          <i class="ri-broadcast-line"></i> Live
                        </span>
                      </div>
                    </td>

                    <!-- Biaya & Kuota -->
                    <td>
                      <div class="fw-bold text-dark fs-12">{{ formatRupiah(comp.fee) }}</div>
                      <div class="d-flex align-items-center gap-2 mt-1" style="min-width: 140px;">
                        <div class="progress flex-1" style="height: 5px;">
                          <div 
                            class="progress-bar"
                            :class="comp.current_quota >= comp.max_quota ? 'bg-danger' : 'bg-primary'"
                            :style="{ width: `${Math.min(100, Math.round((comp.current_quota / (comp.max_quota || 1)) * 100))}%` }"
                          ></div>
                        </div>
                        <span class="text-muted fs-10 font-mono">{{ comp.current_quota }}/{{ comp.max_quota }}</span>
                      </div>
                    </td>

                    <!-- Koordinator / Kontak -->
                    <td>
                      <div v-if="comp.coordinators && comp.coordinators.length > 0">
                        <div 
                          v-for="(cd, cIdx) in comp.coordinators.slice(0, 2)" 
                          :key="cIdx"
                          class="mb-1 d-flex align-items-center gap-1 flex-wrap"
                        >
                          <span 
                            class="badge border fs-11 py-0.5 px-1.5 d-inline-flex align-items-center gap-1 truncate" 
                            :class="cIdx === 0 ? 'bg-primary-subtle text-primary border-primary-subtle fw-semibold' : 'bg-light text-dark border-light-subtle'"
                            style="max-width: 155px;"
                            :title="cd.name + (cd.role ? ' (' + cd.role + ')' : '')"
                          >
                            <i :class="cIdx === 0 ? 'ri-user-star-line' : 'ri-user-line'"></i> {{ cd.name }}
                          </span>
                          <span v-if="cIdx === 0 && isUserCoordinating(comp)" class="badge bg-warning text-dark fs-9 fw-bold">
                            ⭐ Lomba Anda
                          </span>
                          <a 
                            v-if="cd.contact" 
                            :href="getWaChatUrl(cd.contact, `Halo ${cd.name}, koordinasi cabang ${comp.title}: `)" 
                            target="_blank" 
                            class="badge bg-success-subtle text-success fs-9 text-decoration-none d-inline-flex align-items-center gap-0.5"
                            title="Chat WhatsApp Koordinator"
                          >
                            <i class="ri-whatsapp-line"></i> {{ cd.contact }}
                          </a>
                        </div>
                        <small v-if="comp.coordinators.length > 2" class="text-muted fs-10 d-block">
                          +{{ comp.coordinators.length - 2 }} koordinator lainnya
                        </small>
                      </div>

                      <div v-else-if="comp.coordinator || getCoordinatorUser(comp.coordinator_id)">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                          <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-11 py-0.5 px-1.5 d-inline-flex align-items-center gap-1">
                            <i class="ri-user-star-line"></i> {{ comp.coordinator?.name || getCoordinatorUser(comp.coordinator_id)?.name }}
                          </span>
                          <span v-if="isUserCoordinating(comp)" class="badge bg-warning text-dark fs-9 fw-bold">
                            ⭐ Lomba Anda
                          </span>
                        </div>
                        <div v-if="comp.coordinator?.phone || getCoordinatorUser(comp.coordinator_id)?.phone" class="mt-0.5">
                          <a 
                            :href="getWaChatUrl(comp.coordinator?.phone || getCoordinatorUser(comp.coordinator_id)?.phone, `Halo ${comp.coordinator?.name || getCoordinatorUser(comp.coordinator_id)?.name}, koordinasi cabang ${comp.title}: `)"
                            target="_blank"
                            class="text-success text-decoration-none fs-11"
                          >
                            <i class="ri-whatsapp-line"></i> {{ comp.coordinator?.phone || getCoordinatorUser(comp.coordinator_id)?.phone }}
                          </a>
                        </div>
                      </div>

                      <div v-else>
                        <span class="text-muted fs-11">{{ comp.contact_person || 'Panitia Semarak' }}</span>
                      </div>
                    </td>

                    <!-- Status Pendaftaran (Otomatis Ditutup jika Lewat Batas / Kuota Penuh & Toggle Sembunyi) -->
                    <td class="text-center">
                      <!-- Kondisi 1: Melewati Batas Waktu Pendaftaran -->
                      <div v-if="comp.is_deadline_passed || isDeadlinePassed(comp.registration_deadline)">
                        <span 
                          class="badge bg-danger-subtle text-danger border border-danger-subtle fs-11 px-2.5 py-1 d-inline-flex align-items-center gap-1 status-badge-refined fw-medium"
                          title="Pendaftaran otomatis ditutup karena telah melewati tanggal batas penutupan"
                        >
                          <i class="ri-calendar-close-line fs-12"></i> Ditutup (Waktu Habis)
                        </span>
                      </div>

                      <!-- Kondisi 2: Kuota Pendaftaran Sudah Penuh -->
                      <div v-else-if="comp.is_quota_full || isQuotaFull(comp)">
                        <span 
                          class="badge bg-warning-subtle text-warning border border-warning-subtle fs-11 px-2.5 py-1 d-inline-flex align-items-center gap-1 status-badge-refined fw-medium"
                          title="Pendaftaran otomatis ditutup karena kapasitas kuota maksimal telah penuh"
                        >
                          <i class="ri-alarm-warning-fill fs-12"></i> Ditutup (Kuota Penuh)
                        </span>
                      </div>

                      <!-- Kondisi 3: Normal (Masa Pendaftaran Aktif & Kuota Masih Tersedia) -> Toggle Ditampilkan -->
                      <div v-else class="d-inline-flex align-items-center justify-content-center gap-2">
                        <!-- Switch Toggle Input -->
                        <div class="form-check form-switch mb-0">
                          <input 
                            class="form-check-input cursor-pointer status-toggle-switch" 
                            type="checkbox" 
                            role="switch" 
                            :id="`switch-status-${comp.id}`"
                            :checked="comp.status === 'open'" 
                            @click.prevent="handleStatusToggle(comp)"
                            :title="comp.status === 'open' ? 'Klik untuk Tutup Pendaftaran Sementara' : 'Klik untuk Membuka Pendaftaran'"
                          />
                        </div>

                        <!-- Status Badge Label -->
                        <label 
                          :for="`switch-status-${comp.id}`" 
                          class="mb-0 cursor-pointer user-select-none"
                          @click.prevent="handleStatusToggle(comp)"
                        >
                          <span 
                            v-if="comp.status === 'open'"
                            class="badge bg-success-subtle text-success border border-success-subtle fs-11 px-2 py-0.5 d-inline-flex align-items-center gap-1 status-badge-refined fw-medium"
                          >
                            <i class="ri-checkbox-circle-fill fs-11"></i> Dibuka
                          </span>
                          <span 
                            v-else
                            class="badge bg-danger-subtle text-danger border border-danger-subtle fs-11 px-2 py-0.5 d-inline-flex align-items-center gap-1 status-badge-refined fw-medium"
                          >
                            <i class="ri-lock-fill fs-11"></i> Ditutup Panitia
                          </span>
                        </label>
                      </div>
                    </td>

                    <!-- Aksi -->
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <!-- Pintasan TM Button -->
                        <Link 
                          :href="`/admin/tm?comp=${comp.id}`"
                          class="btn btn-sm btn-outline-info"
                          :title="`Pintasan Manajemen TM & Pengundian: ${comp.title}`"
                        >
                          <i class="ri-calendar-todo-line"></i>
                        </Link>

                        <!-- Form Builder Button -->
                        <Link 
                          :href="`/admin/form-builder/${comp.id}`"
                          class="btn btn-sm btn-outline-warning"
                          :title="`Atur Formulir Pendaftaran: ${comp.title}`"
                        >
                          <i class="ri-survey-line"></i>
                        </Link>

                        <!-- Edit Button -->
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-primary"
                          :disabled="!canUserEditCompetition(comp)"
                          @click="openEditModal(comp)"
                          :title="canUserEditCompetition(comp) ? 'Ubah Rincian Cabang Lomba' : 'Anda hanya berwenang mengubah cabang lomba yang Anda koordinasi'"
                        >
                          <i class="ri-edit-line"></i>
                        </button>

                        <!-- Delete Button (Hanya untuk Pimpinan & Admin, bukan Koordinator) -->
                        <button 
                          v-if="!isCoordinatorRole"
                          type="button" 
                          class="btn btn-sm btn-outline-danger"
                          @click="openDeleteModal(comp)"
                          title="Hapus Cabang Lomba"
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
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: TAMBAH CABANG LOMBA BARU                                -->
    <!-- ============================================================== -->
    <Transition name="modal-fade">
      <div 
        v-if="showCreateModal" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(0,0,0,0.55); z-index: 1060;"
        @click.self="showCreateModal = false"
      >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-animate">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
              <h5 class="modal-title fs-15 fw-bold text-dark">
                <i class="ri-add-circle-line text-primary me-1"></i> Tambah Cabang Lomba Baru
              </h5>
              <button type="button" class="btn-close" @click="showCreateModal = false"></button>
            </div>

            <form @submit.prevent="submitCreate" novalidate>
              <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                <!-- Alert Error Validasi Bahasa Indonesia -->
                <div v-if="createForm.hasErrors" class="alert alert-danger alert-dismissible fade show p-3 mb-3 fs-12 rounded-3 shadow-xs border-0" role="alert">
                  <div class="d-flex align-items-start gap-2">
                    <i class="ri-error-warning-fill fs-18 text-danger flex-shrink-0 mt-1"></i>
                    <div class="flex-grow-1">
                      <h6 class="fs-13 fw-bold text-danger mb-1">Perhatian: Mohon perbaiki data formulir berikut:</h6>
                      <ul class="mb-0 ps-3">
                        <li v-for="(errMsg, errKey) in createForm.errors" :key="errKey" class="text-danger">
                          {{ errMsg }}
                        </li>
                      </ul>
                    </div>
                    <button type="button" class="btn-close fs-10" @click="createForm.clearErrors()"></button>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Row 1: Nama & Kode ID -->
                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-dark">Nama Cabang Lomba *</label>
                    <input 
                      v-model="createForm.title" 
                      type="text" 
                      class="form-control fs-13" 
                      :class="{ 'is-invalid': createForm.errors.title }"
                      placeholder="Contoh: Musabaqah Tilawatil Qur'an (MTQ)" 
                      @input="onTitleInput"
                      required
                    />
                    <div v-if="createForm.errors.title" class="invalid-feedback d-block fs-11">{{ createForm.errors.title }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Kode / ID Slug *</label>
                    <input 
                      v-model="createForm.id" 
                      type="text" 
                      class="form-control fs-13 font-mono" 
                      :class="{ 'is-invalid': createForm.errors.id }"
                      placeholder="Contoh: mtq" 
                      required
                    />
                    <div v-if="createForm.errors.id" class="invalid-feedback d-block fs-11">{{ createForm.errors.id }}</div>
                  </div>

                  <!-- Row 2: Kategori & Biaya & Kuota -->
                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Kategori Bidang *</label>
                    <CustomSelect v-model="createForm.category" size="md" placeholder="Pilih Kategori...">
                      <option v-for="cat in categoryList" :key="cat.id" :value="cat.id">
                        {{ cat.name || cat.label }}
                      </option>
                    </CustomSelect>
                    <div v-if="createForm.errors.category" class="invalid-feedback d-block fs-11">{{ createForm.errors.category }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Biaya Pendaftaran (Rp) *</label>
                    <input 
                      v-model="createForm.fee" 
                      type="number" 
                      min="0" 
                      step="5000"
                      class="form-control fs-13" 
                      :class="{ 'is-invalid': createForm.errors.fee }"
                      required
                    />
                    <div v-if="createForm.errors.fee" class="invalid-feedback d-block fs-11">{{ createForm.errors.fee }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Kuota Maksimal Delegasi *</label>
                    <input 
                      v-model="createForm.max_quota" 
                      type="number" 
                      min="1" 
                      class="form-control fs-13" 
                      :class="{ 'is-invalid': createForm.errors.max_quota }"
                      required
                    />
                    <div v-if="createForm.errors.max_quota" class="invalid-feedback d-block fs-11">{{ createForm.errors.max_quota }}</div>
                  </div>

                  <!-- Section: Pilihan Format Aturan Peserta (Individu vs Tim) -->
                  <div class="col-12 mt-3">
                    <div class="p-3 bg-light rounded border">
                      <label class="form-label fs-12 fw-bold text-dark d-block mb-2">
                        <i class="ri-user-settings-line text-primary me-1"></i> Pilihan Format Aturan Peserta *
                      </label>
                      <div class="row g-2 mb-3">
                        <!-- Opsi 1: Individu -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="createForm.participant_type === 'individu' ? 'border-primary shadow-xs' : 'border-light'"
                            @click="createForm.participant_type = 'individu'"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                  <i class="ri-user-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Peserta Individu</span>
                              </div>
                              <input 
                                type="radio" 
                                value="individu" 
                                v-model="createForm.participant_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Format peserta tunggal/perorangan (misal: MTQ, Pidato, Singer, Olimpiade).</p>
                          </div>
                        </div>

                        <!-- Opsi 2: Tim / Regu -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="createForm.participant_type === 'tim' ? 'border-info shadow-xs' : 'border-light'"
                            @click="createForm.participant_type = 'tim'; if(createForm.max_participants < 2) createForm.max_participants = 10;"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                                  <i class="ri-team-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Format Tim / Regu</span>
                              </div>
                              <input 
                                type="radio" 
                                value="tim" 
                                v-model="createForm.participant_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Format kelompok/rombongan (misal: Tournament Voli, Albanjari, Mobile Legends).</p>
                          </div>
                        </div>
                      </div>

                      <!-- Input Khusus: Jumlah Maksimal Individu vs Tim -->
                      <div class="row g-2">
                        <!-- Individu -->
                        <template v-if="createForm.participant_type === 'individu'">
                          <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">
                              Jumlah Maksimal Individu (per Lembaga/Sekolah) *
                            </label>
                            <div class="input-group input-group-sm">
                              <input 
                                v-model="createForm.max_participants" 
                                type="number" 
                                min="1" 
                                max="20"
                                class="form-control fs-12" 
                                :class="{ 'is-invalid': createForm.errors.max_participants }"
                                placeholder="Contoh: 1 atau 2" 
                                required
                              />
                              <span class="input-group-text fs-12 bg-light">Peserta / Lembaga</span>
                            </div>
                            <div v-if="createForm.errors.max_participants" class="invalid-feedback d-block fs-11">{{ createForm.errors.max_participants }}</div>
                            <small class="text-muted fs-11">Batas kuota peserta individu yang dapat didelegasikan oleh 1 sekolah.</small>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Keterangan Aturan Delegasi (Opsional)</label>
                            <input 
                              v-model="createForm.participants_rule" 
                              type="text" 
                              class="form-control form-control-sm fs-12" 
                              placeholder="Cth: Maksimal 2 Siswa per Madrasah" 
                            />
                            <small class="text-muted fs-11">Catatan pembatasan delegasi jika ada.</small>
                          </div>
                        </template>

                        <!-- Tim / Regu -->
                        <template v-else>
                          <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold text-dark">
                              Maksimal Tim (per Lembaga) *
                            </label>
                            <div class="input-group input-group-sm">
                              <input 
                                v-model="createForm.max_teams" 
                                type="number" 
                                min="1" 
                                max="20"
                                class="form-control fs-12" 
                                :class="{ 'is-invalid': createForm.errors.max_teams }"
                                placeholder="Contoh: 1" 
                                required
                              />
                              <span class="input-group-text fs-12 bg-light">Tim / Lembaga</span>
                            </div>
                            <div v-if="createForm.errors.max_teams" class="invalid-feedback d-block fs-11">{{ createForm.errors.max_teams }}</div>
                            <small class="text-muted fs-11">Batas regu yang boleh didaftarkan 1 sekolah.</small>
                          </div>

                          <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold text-dark">
                              Maksimal Anggota (per Tim) *
                            </label>
                            <div class="input-group input-group-sm">
                              <input 
                                v-model="createForm.max_participants" 
                                type="number" 
                                min="2" 
                                max="50"
                                class="form-control fs-12" 
                                :class="{ 'is-invalid': createForm.errors.max_participants }"
                                placeholder="Contoh: 10" 
                                required
                              />
                              <span class="input-group-text fs-12 bg-light">Anggota / Tim</span>
                            </div>
                            <div v-if="createForm.errors.max_participants" class="invalid-feedback d-block fs-11">{{ createForm.errors.max_participants }}</div>
                            <small class="text-muted fs-11">Kapasitas personel anggota dalam 1 regu.</small>
                          </div>

                          <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold text-dark">Keterangan Aturan Delegasi</label>
                            <input 
                              v-model="createForm.participants_rule" 
                              type="text" 
                              class="form-control form-control-sm fs-12" 
                              placeholder="Cth: 1 Tim (6 Inti + 4 Cadangan)" 
                            />
                            <small class="text-muted fs-11">Opsional: Catatan komposisi pemain.</small>
                          </div>
                        </template>
                      </div>
                    </div>
                  </div>

                  <!-- Row: Tanggal Tutup & Jadwal TM -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">
                      <i class="ri-calendar-close-line text-danger me-1"></i> Tanggal Tutup Pendaftaran *
                    </label>
                    <IndoDatePicker 
                      v-model="createForm.registration_deadline" 
                      placeholder="dd/mm/yyyy"
                      :has-error="!!createForm.errors.registration_deadline"
                      required
                    />
                    <div v-if="createForm.errors.registration_deadline" class="invalid-feedback d-block fs-11">{{ createForm.errors.registration_deadline }}</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">
                      <i class="ri-calendar-event-line text-primary me-1"></i> Jadwal Technical Meeting (TM)
                    </label>
                    <div class="row g-2">
                      <div class="col-7">
                        <IndoDatePicker 
                          v-model="createForm.tm_date_date" 
                          placeholder="dd/mm/yyyy"
                          @update:modelValue="syncCreateTm"
                        />
                      </div>
                      <div class="col-5">
                        <IndoTimePicker 
                          v-model="createForm.tm_date_time" 
                          placeholder="08:00"
                          @update:modelValue="syncCreateTm"
                        />
                      </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                      <small class="text-muted fs-11">
                        <i class="ri-time-line me-0.5"></i>Format 24 jam (WIB)
                      </small>
                      <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setCreateTmTime('08:00')">08:00</button>
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setCreateTmTime('09:00')">09:00</button>
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setCreateTmTime('13:00')">13:00</button>
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setCreateTmTime('14:00')">14:00</button>
                      </div>
                    </div>
                  </div>

                  <!-- Section: Koordinator & Nomor Kontak (Tersinkron Database Akun Panitia) -->
                  <div class="col-12 mt-2">
                    <div class="p-3 bg-light rounded border">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label fs-12 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5 flex-wrap">
                          <i class="ri-contacts-book-2-line text-primary"></i> Nama Koordinator & Nomor Kontak (Bisa Lebih Dari 1 Orang)
                          <span class="badge bg-primary-subtle text-primary fs-10">Tersinkron Otomatis Database</span>
                        </label>
                        <button 
                          type="button" 
                          class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1"
                          @click="addCoordinator(createForm)"
                        >
                          <i class="ri-user-add-line"></i> + Tambah Koordinator
                        </button>
                      </div>
                      <p class="text-muted fs-11 mb-2.5">
                        Pilih akun koordinator dari database panitia. Nomor kontak WhatsApp akan terisi otomatis dan akun koordinator utama (baris #1) otomatis memperoleh hak akses penuh pengelolaan Technical Meeting serta undian cabang ini.
                      </p>

                      <div 
                        v-for="(coord, cIdx) in createForm.coordinators" 
                        :key="cIdx"
                        class="p-2 mb-2 rounded border bg-white shadow-sm"
                      >
                        <div class="row g-2 align-items-center">
                          <!-- Kolom 1: Akun / Nama Koordinator (Dropdown) -->
                          <div class="col-md-5">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1 d-flex align-items-center justify-content-between">
                              <span>Koordinator #{{ cIdx + 1 }} {{ cIdx === 0 ? '(PJ Utama)' : '' }}</span>
                              <span v-if="cIdx === 0 && coord.user_id && coord.user_id !== 'custom'" class="badge bg-success-subtle text-success fs-9 py-0">
                                Hak Akses TM Aktif
                              </span>
                            </label>
                            <CustomSelect 
                              v-model="coord.user_id" 
                              :options="coordinatorDropdownOptions"
                              placeholder="-- Pilih Koordinator dari Database --"
                              searchable
                              size="sm"
                              @update:modelValue="onCoordinatorChange(createForm, cIdx, $event)"
                            />
                            <!-- Input manual jika opsi custom dipilih -->
                            <input 
                              v-if="coord.user_id === 'custom'" 
                              v-model="coord.name" 
                              type="text" 
                              class="form-control form-control-sm fs-12 mt-1" 
                              placeholder="Ketik Nama Koordinator..." 
                              required
                            />
                          </div>

                          <!-- Kolom 2: Nomor Kontak WhatsApp (Otomatis dari DB) -->
                          <div class="col-md-4">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">
                              Nomor WhatsApp
                            </label>
                            <div class="input-group input-group-sm">
                              <span class="input-group-text bg-white text-success border-end-0">
                                <i class="ri-whatsapp-line"></i>
                              </span>
                              <input 
                                v-model="coord.contact" 
                                type="tel"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                class="form-control fs-12" 
                                :class="{ 'bg-light text-dark fw-medium': coord.user_id && coord.user_id !== 'custom' && coord.contact }"
                                placeholder="08123456789" 
                                :readonly="coord.user_id && coord.user_id !== 'custom' && Boolean(coord.contact)"
                                @input="coord.contact = coord.contact.replace(/\D/g, '')"
                                required
                              />
                            </div>
                            <div v-if="coord.user_id && coord.user_id !== 'custom'" class="fs-10 mt-0.5">
                              <span v-if="coord.contact" class="text-success d-inline-flex align-items-center gap-0.5">
                                <i class="ri-check-line"></i> Otomatis dari akun panitia
                              </span>
                              <span v-else class="text-warning d-inline-flex align-items-center gap-0.5">
                                <i class="ri-information-line"></i> Belum ada no WA di akun, silakan isi
                              </span>
                            </div>
                          </div>

                          <!-- Kolom 3: Jabatan / Peran -->
                          <div class="col-md-2">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Peran / Jabatan</label>
                            <input 
                              v-model="coord.role" 
                              type="text" 
                              class="form-control form-control-sm fs-12" 
                              placeholder="Koordinator Lomba" 
                            />
                          </div>

                          <!-- Kolom 4: Tombol Hapus -->
                          <div class="col-md-1 text-end pt-3">
                            <button 
                              type="button" 
                              class="btn btn-xs btn-outline-danger"
                              :disabled="createForm.coordinators.length <= 1"
                              @click="removeCoordinator(createForm, cIdx)"
                              title="Hapus Baris Koordinator"
                            >
                              <i class="ri-delete-bin-line"></i>
                            </button>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top fs-11 text-muted">
                        <span>
                          <i class="ri-shield-check-line text-success me-1"></i>
                          <strong class="text-dark">PJ Utama Cabang:</strong> 
                          <span v-if="createForm.coordinators[0]?.name" class="text-primary fw-semibold ms-1">
                            {{ createForm.coordinators[0].name }}
                          </span>
                          <span v-else class="text-secondary ms-1">
                            Pilih akun koordinator pada baris pertama (#1)
                          </span>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Section: Metode Pendaftaran Delegasi (Online vs Kontak Langsung) -->
                  <div class="col-12 mt-2">
                    <div class="p-3 bg-light rounded border">
                      <label class="form-label fs-12 fw-bold text-dark d-block mb-1">
                        <i class="ri-route-line text-primary me-1"></i> Metode Pendaftaran Delegasi *
                      </label>
                      <p class="text-muted fs-11 mb-2.5">
                        Tentukan alur pendaftaran untuk cabang lomba ini: apakah pendaftaran mandiri secara online melalui sistem web, atau pendaftaran khusus yang hanya dilayani via kontak langsung ke Koordinator Lomba.
                      </p>

                      <div class="row g-2 mb-2">
                        <!-- Opsi 1: Pendaftaran Mandiri Online (Sistem Web) -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="createForm.registration_type !== 'direct_contact' ? 'border-primary shadow-xs' : 'border-light'"
                            @click="createForm.registration_type = 'online'"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                  <i class="ri-global-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Portal Online (Sistem Web)</span>
                              </div>
                              <input 
                                type="radio" 
                                value="online" 
                                v-model="createForm.registration_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Peserta mengisi formulir dan berkas secara mandiri melalui website pendaftaran.</p>
                          </div>
                        </div>

                        <!-- Opsi 2: Hanya Kontak Langsung ke Koordinator Lomba -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="createForm.registration_type === 'direct_contact' ? 'border-success shadow-xs' : 'border-light'"
                            @click="createForm.registration_type = 'direct_contact'"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                                  <i class="ri-whatsapp-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Hanya Kontak Langsung</span>
                              </div>
                              <input 
                                type="radio" 
                                value="direct_contact" 
                                v-model="createForm.registration_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Peserta wajib menghubungi Koordinator Lomba. Tombol pendaftaran mengarahkan langsung ke WhatsApp Koordinator.</p>
                          </div>
                        </div>
                      </div>

                      <!-- Catatan & Template Pendaftaran Langsung jika opsi direct_contact dipilih -->
                      <div v-if="createForm.registration_type === 'direct_contact'" class="mt-2.5 p-3 rounded bg-success-subtle border border-success-subtle">
                        <div class="mb-2.5">
                          <label class="form-label fs-11 fw-bold text-success mb-1 d-flex align-items-center gap-1">
                            <i class="ri-information-line"></i> Petunjuk / Catatan Pendaftaran Kontak Langsung (Opsional)
                          </label>
                          <input 
                            v-model="createForm.direct_registration_note"
                            type="text"
                            class="form-control form-control-sm fs-12 bg-white"
                            placeholder="Cth: Pendaftaran cabang ini wajib konfirmasi langsung ke Koordinator via WhatsApp untuk alokasi pool tim."
                          />
                        </div>

                        <div>
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fs-11 fw-bold text-success mb-0 d-flex align-items-center gap-1">
                              <i class="ri-message-3-line"></i> Template Pesan WhatsApp Pendaftaran via PJ (Opsional)
                            </label>
                            <button
                              type="button"
                              class="btn btn-xs btn-outline-success bg-white rounded-pill px-2 py-0.5 fs-11 fw-semibold d-inline-flex align-items-center gap-1 shadow-xs"
                              @click="applyDefaultDirectTemplate(createForm)"
                              title="Terapkan teks format template standar"
                            >
                              <i class="ri-magic-line"></i> Gunakan Format Standar
                            </button>
                          </div>
                          <p class="fs-11 text-muted mb-1.5" style="line-height: 1.4;">
                            Sesuaikan format pesan pembuka yang otomatis disiapkan saat peserta mengklik tombol WhatsApp PJ. Kosongkan jika ingin memakai template standar global.
                          </p>
                          <textarea
                            v-model="createForm.direct_registration_template"
                            rows="5"
                            class="form-control form-control-sm font-monospace fs-11 bg-white"
                            placeholder="Contoh: Assalamu'alaikum Kak {nama_pj}, saya {nama_pendaftar} dari {nama_sekolah}..."
                          ></textarea>
                          <div class="d-flex flex-wrap align-items-center gap-1 mt-1.5">
                            <span class="fs-11 text-muted me-1">Sisipkan tag:</span>
                            <button
                              v-for="tag in ['{nama_pj}', '{cabang_lomba}', '{nama_event}', '{nama_sekolah}', '{nama_pendaftar}', '{nomor_wa}', '{biaya}', '{kontak_pj}']"
                              :key="tag"
                              type="button"
                              class="badge bg-white text-dark border font-monospace fs-10 px-1.5 py-0.5 cursor-pointer hover:bg-success hover:text-white transition-all"
                              @click="insertTemplateTag(createForm, tag)"
                              :title="'Klik untuk sisipkan ' + tag"
                            >
                              {{ tag }}
                            </button>
                          </div>
                        </div>

                        <small class="text-muted fs-11 d-block mt-2 pt-2 border-top border-success-subtle">
                          <i class="ri-checkbox-circle-line text-success me-0.5"></i> Pastikan nomor WhatsApp koordinator lomba sudah terisi pada baris koordinator di atas agar tautan chat otomatis berfungsi.
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- Status & Deskripsi -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">Status Pendaftaran Awal</label>
                    <CustomSelect v-model="createForm.status" size="md">
                      <option value="open">Buka (Terbuka untuk Pendaftaran)</option>
                      <option value="closed">Tutup (Pendaftaran Ditutup Sementara)</option>
                    </CustomSelect>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark">Deskripsi & Ketentuan Singkat</label>
                    <textarea 
                      v-model="createForm.description" 
                      rows="2" 
                      class="form-control fs-13" 
                      placeholder="Penjelasan singkat mengenai lomba atau kriteria peserta..."
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-sm btn-secondary" @click="showCreateModal = false">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary" :disabled="createForm.processing">
                  <i class="ri-save-line me-1"></i> {{ createForm.processing ? 'Menyimpan...' : 'Simpan Cabang Lomba' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ============================================================== -->
    <!-- MODAL: EDIT CABANG LOMBA                                       -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- MODAL: EDIT CABANG LOMBA                                       -->
    <!-- ============================================================== -->
    <Transition name="modal-fade">
      <div 
        v-if="showEditModal" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(0,0,0,0.55); z-index: 1060;"
        @click.self="showEditModal = false"
      >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-animate">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
              <h5 class="modal-title fs-15 fw-bold text-dark">
                <i class="ri-edit-line text-primary me-1"></i> Edit Cabang Lomba: {{ editForm.title }}
              </h5>
              <button type="button" class="btn-close" @click="showEditModal = false"></button>
            </div>

            <form @submit.prevent="submitEdit" novalidate>
              <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
                <!-- Alert Error Validasi Bahasa Indonesia -->
                <div v-if="editForm.hasErrors" class="alert alert-danger alert-dismissible fade show p-3 mb-3 fs-12 rounded-3 shadow-xs border-0" role="alert">
                  <div class="d-flex align-items-start gap-2">
                    <i class="ri-error-warning-fill fs-18 text-danger flex-shrink-0 mt-1"></i>
                    <div class="flex-grow-1">
                      <h6 class="fs-13 fw-bold text-danger mb-1">Perhatian: Mohon perbaiki data formulir berikut:</h6>
                      <ul class="mb-0 ps-3">
                        <li v-for="(errMsg, errKey) in editForm.errors" :key="errKey" class="text-danger">
                          {{ errMsg }}
                        </li>
                      </ul>
                    </div>
                    <button type="button" class="btn-close fs-10" @click="editForm.clearErrors()"></button>
                  </div>
                </div>

                <div class="row g-3">
                  <div class="col-md-8">
                    <label class="form-label fs-12 fw-semibold text-dark">Nama Cabang Lomba *</label>
                    <input 
                      v-model="editForm.title" 
                      type="text" 
                      class="form-control fs-13" 
                      :class="{ 'is-invalid': editForm.errors.title }"
                      required
                    />
                    <div v-if="editForm.errors.title" class="invalid-feedback d-block fs-11">{{ editForm.errors.title }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label text-muted fs-12">Kode ID (Permanen)</label>
                    <input 
                      :value="editForm.id" 
                      type="text" 
                      class="form-control fs-13 font-mono bg-light" 
                      disabled 
                    />
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Kategori Bidang *</label>
                    <CustomSelect v-model="editForm.category" size="md" placeholder="Pilih Kategori...">
                      <option v-for="cat in categoryList" :key="cat.id" :value="cat.id">
                        {{ cat.name || cat.label }}
                      </option>
                    </CustomSelect>
                    <div v-if="editForm.errors.category" class="invalid-feedback d-block fs-11">{{ editForm.errors.category }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Biaya Pendaftaran (Rp) *</label>
                    <input 
                      v-model="editForm.fee" 
                      type="number" 
                      min="0" 
                      step="5000"
                      class="form-control fs-13" 
                      :class="{ 'is-invalid': editForm.errors.fee }"
                      required
                    />
                    <div v-if="editForm.errors.fee" class="invalid-feedback d-block fs-11">{{ editForm.errors.fee }}</div>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fs-12 fw-semibold text-dark">Kuota Maksimal Delegasi *</label>
                    <input 
                      v-model="editForm.max_quota" 
                      type="number" 
                      min="1" 
                      class="form-control fs-13" 
                      :class="{ 'is-invalid': editForm.errors.max_quota }"
                      required
                    />
                    <div v-if="editForm.errors.max_quota" class="invalid-feedback d-block fs-11">{{ editForm.errors.max_quota }}</div>
                  </div>

                  <!-- Section: Pilihan Format Aturan Peserta (Individu vs Tim) -->
                  <div class="col-12 mt-3">
                    <div class="p-3 bg-light rounded border">
                      <label class="form-label fs-12 fw-bold text-dark d-block mb-2">
                        <i class="ri-user-settings-line text-primary me-1"></i> Pilihan Format Aturan Peserta *
                      </label>
                      <div class="row g-2 mb-3">
                        <!-- Opsi 1: Individu -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="editForm.participant_type === 'individu' ? 'border-primary shadow-xs' : 'border-light'"
                            @click="editForm.participant_type = 'individu'"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                  <i class="ri-user-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Peserta Individu</span>
                              </div>
                              <input 
                                type="radio" 
                                value="individu" 
                                v-model="editForm.participant_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Format peserta tunggal/perorangan (misal: MTQ, Pidato, Singer, Olimpiade).</p>
                          </div>
                        </div>

                        <!-- Opsi 2: Tim / Regu -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="editForm.participant_type === 'tim' ? 'border-info shadow-xs' : 'border-light'"
                            @click="editForm.participant_type = 'tim'; if(editForm.max_participants < 2) editForm.max_participants = 10;"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center">
                                  <i class="ri-team-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Format Tim / Regu</span>
                              </div>
                              <input 
                                type="radio" 
                                value="tim" 
                                v-model="editForm.participant_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Format kelompok/rombongan (misal: Tournament Voli, Albanjari, Mobile Legends).</p>
                          </div>
                        </div>
                      </div>

                      <!-- Input Khusus: Jumlah Maksimal Individu vs Tim -->
                      <div class="row g-2">
                        <!-- Individu -->
                        <template v-if="editForm.participant_type === 'individu'">
                          <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">
                              Jumlah Maksimal Individu (per Lembaga/Sekolah) *
                            </label>
                            <div class="input-group input-group-sm">
                              <input 
                                v-model="editForm.max_participants" 
                                type="number" 
                                min="1" 
                                max="20"
                                class="form-control fs-12" 
                                :class="{ 'is-invalid': editForm.errors.max_participants }"
                                placeholder="Contoh: 1 atau 2" 
                                required
                              />
                              <span class="input-group-text fs-12 bg-light">Peserta / Lembaga</span>
                            </div>
                            <div v-if="editForm.errors.max_participants" class="invalid-feedback d-block fs-11">{{ editForm.errors.max_participants }}</div>
                            <small class="text-muted fs-11">Batas kuota peserta individu yang dapat didelegasikan oleh 1 sekolah.</small>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label fs-12 fw-semibold text-dark">Keterangan Aturan Delegasi (Opsional)</label>
                            <input 
                              v-model="editForm.participants_rule" 
                              type="text" 
                              class="form-control form-control-sm fs-12" 
                              placeholder="Cth: Maksimal 2 Siswa per Madrasah" 
                            />
                            <small class="text-muted fs-11">Catatan pembatasan delegasi jika ada.</small>
                          </div>
                        </template>

                        <!-- Tim / Regu -->
                        <template v-else>
                          <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold text-dark">
                              Maksimal Tim (per Lembaga) *
                            </label>
                            <div class="input-group input-group-sm">
                              <input 
                                v-model="editForm.max_teams" 
                                type="number" 
                                min="1" 
                                max="20"
                                class="form-control fs-12" 
                                :class="{ 'is-invalid': editForm.errors.max_teams }"
                                placeholder="Contoh: 1" 
                                required
                              />
                              <span class="input-group-text fs-12 bg-light">Tim / Lembaga</span>
                            </div>
                            <div v-if="editForm.errors.max_teams" class="invalid-feedback d-block fs-11">{{ editForm.errors.max_teams }}</div>
                            <small class="text-muted fs-11">Batas regu yang boleh didaftarkan 1 sekolah.</small>
                          </div>

                          <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold text-dark">
                              Maksimal Anggota (per Tim) *
                            </label>
                            <div class="input-group input-group-sm">
                              <input 
                                v-model="editForm.max_participants" 
                                type="number" 
                                min="2" 
                                max="50"
                                class="form-control fs-12" 
                                :class="{ 'is-invalid': editForm.errors.max_participants }"
                                placeholder="Contoh: 10" 
                                required
                              />
                              <span class="input-group-text fs-12 bg-light">Anggota / Tim</span>
                            </div>
                            <div v-if="editForm.errors.max_participants" class="invalid-feedback d-block fs-11">{{ editForm.errors.max_participants }}</div>
                            <small class="text-muted fs-11">Kapasitas personel anggota dalam 1 regu.</small>
                          </div>

                          <div class="col-md-4">
                            <label class="form-label fs-12 fw-semibold text-dark">Keterangan Aturan Delegasi</label>
                            <input 
                              v-model="editForm.participants_rule" 
                              type="text" 
                              class="form-control form-control-sm fs-12" 
                              placeholder="Cth: 1 Tim (6 Inti + 4 Cadangan)" 
                            />
                            <small class="text-muted fs-11">Opsional: Catatan komposisi pemain.</small>
                          </div>
                        </template>
                      </div>
                    </div>
                  </div>

                  <!-- Row: Tanggal Tutup & Jadwal TM -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">
                      <i class="ri-calendar-close-line text-danger me-1"></i> Tanggal Tutup Pendaftaran
                    </label>
                    <IndoDatePicker 
                      v-model="editForm.registration_deadline" 
                      placeholder="dd/mm/yyyy"
                      :has-error="!!editForm.errors.registration_deadline"
                    />
                    <div v-if="editForm.errors.registration_deadline" class="invalid-feedback d-block fs-11">{{ editForm.errors.registration_deadline }}</div>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">
                      <i class="ri-calendar-event-line text-primary me-1"></i> Jadwal Technical Meeting (TM)
                    </label>
                    <div class="row g-2">
                      <div class="col-7">
                        <IndoDatePicker 
                          v-model="editForm.tm_date_date" 
                          placeholder="dd/mm/yyyy"
                          @update:modelValue="syncEditTm"
                        />
                      </div>
                      <div class="col-5">
                        <IndoTimePicker 
                          v-model="editForm.tm_date_time" 
                          placeholder="08:00"
                          @update:modelValue="syncEditTm"
                        />
                      </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                      <small class="text-muted fs-11">
                        <i class="ri-time-line me-0.5"></i>Format 24 jam (WIB)
                      </small>
                      <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setEditTmTime('08:00')">08:00</button>
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setEditTmTime('09:00')">09:00</button>
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setEditTmTime('13:00')">13:00</button>
                        <button type="button" class="btn btn-xs btn-soft-secondary py-0 px-1.5 fs-10" @click="setEditTmTime('14:00')">14:00</button>
                      </div>
                    </div>
                  </div>

                  <!-- Section: Koordinator & Nomor Kontak (Tersinkron Database Akun Panitia) -->
                  <div class="col-12 mt-2">
                    <div class="p-3 bg-light rounded border">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label fs-12 fw-bold text-dark mb-0 d-flex align-items-center gap-1.5 flex-wrap">
                          <i class="ri-contacts-book-2-line text-primary"></i> Nama Koordinator & Nomor Kontak (Bisa Lebih Dari 1 Orang)
                          <span class="badge bg-primary-subtle text-primary fs-10">Tersinkron Otomatis Database</span>
                        </label>
                        <button 
                          type="button" 
                          class="btn btn-xs btn-outline-primary d-inline-flex align-items-center gap-1"
                          @click="addCoordinator(editForm)"
                        >
                          <i class="ri-user-add-line"></i> + Tambah Koordinator
                        </button>
                      </div>
                      <p class="text-muted fs-11 mb-2.5">
                        Pilih akun koordinator dari database panitia. Nomor kontak WhatsApp akan terisi otomatis dan akun koordinator utama (baris #1) otomatis memperoleh hak akses penuh pengelolaan Technical Meeting serta undian cabang ini.
                      </p>

                      <div 
                        v-for="(coord, cIdx) in editForm.coordinators" 
                        :key="cIdx"
                        class="p-2 mb-2 rounded border bg-white shadow-sm"
                      >
                        <div class="row g-2 align-items-center">
                          <!-- Kolom 1: Akun / Nama Koordinator (Dropdown) -->
                          <div class="col-md-5">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1 d-flex align-items-center justify-content-between">
                              <span>Koordinator #{{ cIdx + 1 }} {{ cIdx === 0 ? '(PJ Utama)' : '' }}</span>
                              <span v-if="cIdx === 0 && coord.user_id && coord.user_id !== 'custom'" class="badge bg-success-subtle text-success fs-9 py-0">
                                Hak Akses TM Aktif
                              </span>
                            </label>
                            <CustomSelect 
                              v-model="coord.user_id" 
                              :options="coordinatorDropdownOptions"
                              placeholder="-- Pilih Koordinator dari Database --"
                              searchable
                              size="sm"
                              @update:modelValue="onCoordinatorChange(editForm, cIdx, $event)"
                            />
                            <!-- Input manual jika opsi custom dipilih -->
                            <input 
                              v-if="coord.user_id === 'custom'" 
                              v-model="coord.name" 
                              type="text" 
                              class="form-control form-control-sm fs-12 mt-1" 
                              placeholder="Ketik Nama Koordinator..." 
                              required
                            />
                          </div>

                          <!-- Kolom 2: Nomor Kontak WhatsApp (Otomatis dari DB) -->
                          <div class="col-md-4">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">
                              Nomor WhatsApp
                            </label>
                            <div class="input-group input-group-sm">
                              <span class="input-group-text bg-white text-success border-end-0">
                                <i class="ri-whatsapp-line"></i>
                              </span>
                              <input 
                                v-model="coord.contact" 
                                type="tel"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                class="form-control fs-12" 
                                :class="{ 'bg-light text-dark fw-medium': coord.user_id && coord.user_id !== 'custom' && coord.contact }"
                                placeholder="08123456789" 
                                :readonly="coord.user_id && coord.user_id !== 'custom' && Boolean(coord.contact)"
                                @input="coord.contact = coord.contact.replace(/\D/g, '')"
                                required
                              />
                            </div>
                            <div v-if="coord.user_id && coord.user_id !== 'custom'" class="fs-10 mt-0.5">
                              <span v-if="coord.contact" class="text-success d-inline-flex align-items-center gap-0.5">
                                <i class="ri-check-line"></i> Otomatis dari akun panitia
                              </span>
                              <span v-else class="text-warning d-inline-flex align-items-center gap-0.5">
                                <i class="ri-information-line"></i> Belum ada no WA di akun, silakan isi
                              </span>
                            </div>
                          </div>

                          <!-- Kolom 3: Jabatan / Peran -->
                          <div class="col-md-2">
                            <label class="form-label fs-11 fw-semibold text-dark mb-1">Peran / Jabatan</label>
                            <input 
                              v-model="coord.role" 
                              type="text" 
                              class="form-control form-control-sm fs-12" 
                              placeholder="Koordinator Lomba" 
                            />
                          </div>

                          <!-- Kolom 4: Tombol Hapus -->
                          <div class="col-md-1 text-end pt-3">
                            <button 
                              type="button" 
                              class="btn btn-xs btn-outline-danger"
                              :disabled="editForm.coordinators.length <= 1"
                              @click="removeCoordinator(editForm, cIdx)"
                              title="Hapus Baris Koordinator"
                            >
                              <i class="ri-delete-bin-line"></i>
                            </button>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top fs-11 text-muted">
                        <span>
                          <i class="ri-shield-check-line text-success me-1"></i>
                          <strong class="text-dark">PJ Utama Cabang:</strong> 
                          <span v-if="editForm.coordinators[0]?.name" class="text-primary fw-semibold ms-1">
                            {{ editForm.coordinators[0].name }}
                          </span>
                          <span v-else class="text-secondary ms-1">
                            Pilih akun koordinator pada baris pertama (#1)
                          </span>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Section: Metode Pendaftaran Delegasi (Online vs Kontak Langsung) -->
                  <div class="col-12 mt-2">
                    <div class="p-3 bg-light rounded border">
                      <label class="form-label fs-12 fw-bold text-dark d-block mb-1">
                        <i class="ri-route-line text-primary me-1"></i> Metode Pendaftaran Delegasi *
                      </label>
                      <p class="text-muted fs-11 mb-2.5">
                        Tentukan alur pendaftaran untuk cabang lomba ini: apakah pendaftaran mandiri secara online melalui sistem web, atau pendaftaran khusus yang hanya dilayani via kontak langsung ke Koordinator Lomba.
                      </p>

                      <div class="row g-2 mb-2">
                        <!-- Opsi 1: Pendaftaran Mandiri Online (Sistem Web) -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="editForm.registration_type !== 'direct_contact' ? 'border-primary shadow-xs' : 'border-light'"
                            @click="editForm.registration_type = 'online'"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                                  <i class="ri-global-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Portal Online (Sistem Web)</span>
                              </div>
                              <input 
                                type="radio" 
                                value="online" 
                                v-model="editForm.registration_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Peserta mengisi formulir dan berkas secara mandiri melalui website pendaftaran.</p>
                          </div>
                        </div>

                        <!-- Opsi 2: Hanya Kontak Langsung ke Koordinator Lomba -->
                        <div class="col-md-6">
                          <div 
                            class="p-3 rounded border cursor-pointer h-100 transition-all bg-white"
                            :class="editForm.registration_type === 'direct_contact' ? 'border-success shadow-xs' : 'border-light'"
                            @click="editForm.registration_type = 'direct_contact'"
                          >
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <div class="d-flex align-items-center gap-2">
                                <span class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center">
                                  <i class="ri-whatsapp-line fs-14"></i>
                                </span>
                                <span class="fw-bold fs-13 text-dark">Hanya Kontak Langsung</span>
                              </div>
                              <input 
                                type="radio" 
                                value="direct_contact" 
                                v-model="editForm.registration_type" 
                                class="form-check-input" 
                              />
                            </div>
                            <p class="fs-11 text-muted mb-0">Peserta wajib menghubungi Koordinator Lomba. Tombol pendaftaran mengarahkan langsung ke WhatsApp Koordinator.</p>
                          </div>
                        </div>
                      </div>

                      <!-- Catatan & Template Pendaftaran Langsung jika opsi direct_contact dipilih -->
                      <div v-if="editForm.registration_type === 'direct_contact'" class="mt-2.5 p-3 rounded bg-success-subtle border border-success-subtle">
                        <div class="mb-2.5">
                          <label class="form-label fs-11 fw-bold text-success mb-1 d-flex align-items-center gap-1">
                            <i class="ri-information-line"></i> Petunjuk / Catatan Pendaftaran Kontak Langsung (Opsional)
                          </label>
                          <input 
                            v-model="editForm.direct_registration_note"
                            type="text"
                            class="form-control form-control-sm fs-12 bg-white"
                            placeholder="Cth: Pendaftaran cabang ini wajib konfirmasi langsung ke Koordinator via WhatsApp untuk alokasi pool tim."
                          />
                        </div>

                        <div>
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fs-11 fw-bold text-success mb-0 d-flex align-items-center gap-1">
                              <i class="ri-message-3-line"></i> Template Pesan WhatsApp Pendaftaran via PJ (Opsional)
                            </label>
                            <button
                              type="button"
                              class="btn btn-xs btn-outline-success bg-white rounded-pill px-2 py-0.5 fs-11 fw-semibold d-inline-flex align-items-center gap-1 shadow-xs"
                              @click="applyDefaultDirectTemplate(editForm)"
                              title="Terapkan teks format template standar"
                            >
                              <i class="ri-magic-line"></i> Gunakan Format Standar
                            </button>
                          </div>
                          <p class="fs-11 text-muted mb-1.5" style="line-height: 1.4;">
                            Sesuaikan format pesan pembuka yang otomatis disiapkan saat peserta mengklik tombol WhatsApp PJ. Kosongkan jika ingin memakai template standar global.
                          </p>
                          <textarea
                            v-model="editForm.direct_registration_template"
                            rows="5"
                            class="form-control form-control-sm font-monospace fs-11 bg-white"
                            placeholder="Contoh: Assalamu'alaikum Kak {nama_pj}, saya {nama_pendaftar} dari {nama_sekolah}..."
                          ></textarea>
                          <div class="d-flex flex-wrap align-items-center gap-1 mt-1.5">
                            <span class="fs-11 text-muted me-1">Sisipkan tag:</span>
                            <button
                              v-for="tag in ['{nama_pj}', '{cabang_lomba}', '{nama_event}', '{nama_sekolah}', '{nama_pendaftar}', '{nomor_wa}', '{biaya}', '{kontak_pj}']"
                              :key="tag"
                              type="button"
                              class="badge bg-white text-dark border font-monospace fs-10 px-1.5 py-0.5 cursor-pointer hover:bg-success hover:text-white transition-all"
                              @click="insertTemplateTag(editForm, tag)"
                              :title="'Klik untuk sisipkan ' + tag"
                            >
                              {{ tag }}
                            </button>
                          </div>
                        </div>

                        <small class="text-muted fs-11 d-block mt-2 pt-2 border-top border-success-subtle">
                          <i class="ri-checkbox-circle-line text-success me-0.5"></i> Pastikan nomor WhatsApp koordinator lomba sudah terisi pada baris koordinator di atas agar tautan chat otomatis berfungsi.
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- Status & Deskripsi -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-dark">Status Pendaftaran</label>
                    <CustomSelect v-model="editForm.status" size="md">
                      <option value="open">Buka (Terbuka untuk Pendaftaran)</option>
                      <option value="closed">Tutup (Pendaftaran Ditutup Sementara)</option>
                    </CustomSelect>
                  </div>

                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-dark">Deskripsi & Ketentuan Singkat</label>
                    <textarea 
                      v-model="editForm.description" 
                      rows="2" 
                      class="form-control fs-13" 
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-sm btn-secondary" @click="showEditModal = false">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary" :disabled="editForm.processing">
                  <i class="ri-check-line me-1"></i> {{ editForm.processing ? 'Menyimpan...' : 'Perbarui Cabang Lomba' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ============================================================== -->
    <!-- MODAL: KONFIRMASI HAPUS                                        -->
    <!-- ============================================================== -->
    <Transition name="modal-fade">
      <div 
        v-if="showDeleteModal && activeDeleteItem" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(0,0,0,0.55); z-index: 1060;"
        @click.self="showDeleteModal = false"
      >
        <div class="modal-dialog modal-dialog-centered modal-sm modal-dialog-animate">
          <div class="modal-content border-0 shadow-lg text-center p-3">
            <div class="modal-body py-4">
              <div class="avatar-lg mx-auto mb-3">
                <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-36 shadow-sm border border-danger-subtle">
                  <i class="ri-delete-bin-line"></i>
                </div>
              </div>
              <h5 class="fs-15 fw-bold text-body mb-1">Hapus Cabang Lomba?</h5>
              <p class="text-muted fs-12 mb-0">
                Apakah Anda yakin ingin menghapus cabang lomba <strong>{{ activeDeleteItem.title }}</strong>? Tindakan ini tidak dapat dibatalkan.
              </p>
            </div>
            <div class="d-flex justify-content-center gap-2">
              <button type="button" class="btn btn-sm btn-light px-3" @click="showDeleteModal = false">Batal</button>
              <button type="button" class="btn btn-sm btn-danger px-3" @click="confirmDelete">Hapus Sekarang</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ============================================================== -->
    <!-- MODAL: KELOLA KATEGORI BIDANG                                  -->
    <!-- ============================================================== -->
    <Transition name="modal-fade">
      <div 
        v-if="showCategoryModal" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(0,0,0,0.55); z-index: 1060;"
        @click.self="showCategoryModal = false"
      >
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-animate">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom">
              <div class="d-flex align-items-center gap-2">
                <span class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                  <i class="ri-folder-settings-line fs-14"></i>
                </span>
                <h5 class="modal-title fs-15 fw-bold text-dark mb-0">
                  Kelola Kategori Bidang Cabang Lomba
                </h5>
              </div>
              <button type="button" class="btn-close" @click="showCategoryModal = false"></button>
            </div>

            <div class="modal-body p-4" style="max-height: 80vh; overflow-y: auto;">
              <!-- Form Section: Tambah / Edit Kategori -->
              <div class="card border mb-4 bg-light shadow-xs">
                <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                  <span class="fw-bold fs-12 text-dark">
                    <i :class="editingCategory ? 'ri-edit-line text-warning' : 'ri-add-line text-primary'" class="me-1"></i>
                    {{ editingCategory ? `Ubah Kategori: ${categoryForm.name}` : 'Tambah Kategori Bidang Baru' }}
                  </span>
                  <button 
                    v-if="editingCategory" 
                    type="button" 
                    class="btn btn-xs btn-outline-secondary"
                    @click="resetCategoryForm"
                  >
                    Batal Ubah
                  </button>
                </div>
                <div class="card-body p-3">
                  <form @submit.prevent="submitCategory">
                    <div class="row g-2">
                      <div class="col-md-5">
                        <label class="form-label fs-11 fw-semibold text-dark mb-1">Nama Bidang *</label>
                        <input 
                          v-model="categoryForm.name" 
                          type="text" 
                          class="form-control form-control-sm fs-12"
                          :class="{ 'is-invalid': categoryForm.errors.name }"
                          placeholder="Cth: Robotika & AI" 
                          @input="onCategoryNameInput"
                          required
                        />
                        <div v-if="categoryForm.errors.name" class="invalid-feedback d-block fs-10">{{ categoryForm.errors.name }}</div>
                      </div>

                      <div class="col-md-3">
                        <label class="form-label fs-11 fw-semibold text-dark mb-1">Kode / Slug ID *</label>
                        <input 
                          v-model="categoryForm.id" 
                          type="text" 
                          class="form-control form-control-sm fs-12 font-mono"
                          :class="{ 'is-invalid': categoryForm.errors.id }"
                          placeholder="Cth: robotika" 
                          :disabled="editingCategory"
                          required
                        />
                        <div v-if="categoryForm.errors.id" class="invalid-feedback d-block fs-10">{{ categoryForm.errors.id }}</div>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label fs-11 fw-semibold text-dark mb-1">Warna Badge</label>
                        <CustomSelect v-model="categoryForm.color" size="sm">
                          <option value="primary">Biru (Primary)</option>
                          <option value="success">Hijau (Success)</option>
                          <option value="warning">Kuning (Warning)</option>
                          <option value="info">Cyan (Info)</option>
                          <option value="danger">Merah (Danger)</option>
                          <option value="secondary">Abu-abu (Secondary)</option>
                          <option value="dark">Hitam (Dark)</option>
                        </CustomSelect>
                      </div>

                      <div class="col-md-8">
                        <label class="form-label fs-11 fw-semibold text-dark mb-1">Label Tampilan (Opsional)</label>
                        <input 
                          v-model="categoryForm.label" 
                          type="text" 
                          class="form-control form-control-sm fs-12"
                          placeholder="Cth: Bidang Robotika & AI" 
                        />
                      </div>

                      <div class="col-md-4 d-flex align-items-end">
                        <button 
                          type="submit" 
                          class="btn btn-sm w-100 fs-12"
                          :class="editingCategory ? 'btn-warning' : 'btn-primary'"
                          :disabled="categoryForm.processing"
                        >
                          <i :class="editingCategory ? 'ri-check-line' : 'ri-save-line'" class="me-1"></i>
                          {{ editingCategory ? 'Perbarui Kategori' : 'Simpan Kategori' }}
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Table of Existing Categories -->
              <h6 class="fs-12 fw-bold text-dark text-uppercase mb-2">
                <i class="ri-list-check text-muted me-1"></i> Daftar Kategori Bidang Tersedia
              </h6>
              <div class="table-responsive border rounded bg-white">
                <table class="table table-hover align-middle table-nowrap mb-0">
                  <thead class="table-light">
                    <tr class="text-uppercase fs-11" style="letter-spacing: 0.5px;">
                      <th class="py-2 ps-3">Kode Slug</th>
                      <th class="py-2">Nama Kategori</th>
                      <th class="py-2">Badge Tampilan</th>
                      <th class="text-center py-2">Jumlah Lomba</th>
                      <th class="text-center py-2 pe-3" style="width: 100px;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="cat in categoryList" :key="cat.id">
                      <td class="ps-3">
                        <span class="badge bg-light text-muted border font-mono fs-11">{{ cat.id }}</span>
                      </td>
                      <td>
                        <span class="fw-semibold text-dark fs-12">{{ cat.name }}</span>
                        <small v-if="cat.label && cat.label !== cat.name" class="text-muted fs-10 d-block">{{ cat.label }}</small>
                      </td>
                      <td>
                        <span 
                          class="badge fs-10 text-uppercase fw-semibold"
                          :class="`bg-${cat.color || 'primary'}-subtle text-${cat.color || 'primary'}`"
                        >
                          {{ cat.name }}
                        </span>
                      </td>
                      <td class="text-center">
                        <span class="badge bg-light text-dark border fs-11 px-2">
                          {{ cat.competitions_count ?? 0 }} Cabang
                        </span>
                      </td>
                      <td class="text-center pe-3">
                        <div class="btn-group btn-group-sm">
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-primary"
                            @click="editCategoryItem(cat)"
                            title="Ubah Kategori"
                          >
                            <i class="ri-edit-line"></i>
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-danger"
                            @click="deleteCategoryItem(cat)"
                            :disabled="(cat.competitions_count || 0) > 0"
                            :title="(cat.competitions_count || 0) > 0 ? 'Kategori sedang digunakan oleh cabang lomba' : 'Hapus Kategori'"
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

            <div class="modal-footer bg-light border-top py-2">
              <button type="button" class="btn btn-sm btn-secondary" @click="showCategoryModal = false">
                Tutup
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ============================================================== -->
    <!-- MODAL: BUKA PENDAFTARAN & TAMBAH KUOTA LOMBA                   -->
    <!-- ============================================================== -->
    <Transition name="modal-fade">
      <div 
        v-if="showQuotaModal" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background: rgba(0,0,0,0.55); z-index: 1070;"
        @click.self="showQuotaModal = false"
      >
        <div class="modal-dialog modal-dialog-centered modal-dialog-animate">
          <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning-subtle border-bottom py-3">
              <h5 class="modal-title fs-14 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="ri-user-add-line text-warning fs-18"></i>
                Buka Pendaftaran & Perluas Kuota
              </h5>
              <button type="button" class="btn-close" @click="showQuotaModal = false"></button>
            </div>

            <form @submit.prevent="submitQuotaModal">
              <div class="modal-body p-4">
                <!-- Info Box -->
                <div class="alert alert-warning border-0 d-flex gap-2.5 p-3 rounded-3 mb-3">
                  <i class="ri-information-line fs-20 text-warning flex-shrink-0 mt-0.5"></i>
                  <div class="fs-12 text-dark">
                    <strong class="d-block mb-1 text-dark fs-13">{{ quotaModalData.title }}</strong>
                    Kuota cabang lomba ini saat ini telah <strong>penuh</strong> yaitu <strong>{{ quotaModalData.current_quota }} / {{ quotaModalData.max_quota }} delegasi</strong>.
                    Agar peserta baru dapat mendaftar kembali di formulir publik, kuota maksimal harus diperluas.
                  </div>
                </div>

                <!-- Input Kuota Baru -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold text-dark">
                    Kapasitas Kuota Maksimal Baru *
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light text-muted fs-12"><i class="ri-group-line"></i></span>
                    <input 
                      v-model.number="quotaModalInput"
                      type="number" 
                      :min="quotaModalData.current_quota + 1"
                      step="1"
                      class="form-control fs-14 fw-bold text-primary font-mono"
                      required
                    />
                    <span class="input-group-text bg-light text-muted fs-12">Delegasi</span>
                  </div>
                  <div class="d-flex justify-content-between text-muted fs-11 mt-1">
                    <span>Terisi saat ini: {{ quotaModalData.current_quota }} delegasi</span>
                    <span>Minimal: {{ quotaModalData.current_quota + 1 }} delegasi</span>
                  </div>
                </div>

                <!-- Quick Add Chips -->
                <div class="mb-2">
                  <label class="form-label fs-11 text-muted mb-1.5 d-block">Penambahan Cepat Kuota:</label>
                  <div class="d-flex gap-2">
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-secondary rounded-pill px-2.5"
                      @click="quickAddQuota(2)"
                    >
                      +2 Kuota
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-primary rounded-pill px-2.5"
                      @click="quickAddQuota(5)"
                    >
                      +5 Kuota
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-success rounded-pill px-2.5"
                      @click="quickAddQuota(10)"
                    >
                      +10 Kuota
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-info rounded-pill px-2.5"
                      @click="quickAddQuota(15)"
                    >
                      +15 Kuota
                    </button>
                  </div>
                </div>
              </div>

              <div class="modal-footer bg-light border-top py-2.5 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-secondary" @click="showQuotaModal = false">
                  Batal
                </button>
                <button 
                  type="submit" 
                  class="btn btn-sm btn-success fw-semibold d-inline-flex align-items-center gap-1.5"
                  :disabled="isUpdatingStatus || quotaModalInput <= quotaModalData.current_quota"
                >
                  <i class="ri-check-line"></i> Simpan & Buka Pendaftaran
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>
  </VelzonLayout>
</template>

<style scoped>
.status-toggle-switch {
  width: 1.85rem !important;
  height: 1.05rem !important;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  margin-top: 0 !important;
}
.status-toggle-switch:checked {
  background-color: #0ab39c !important;
  border-color: #0ab39c !important;
}
.status-toggle-switch:focus {
  box-shadow: 0 0 0 0.15rem rgba(10, 179, 156, 0.25) !important;
}
.status-badge-refined {
  font-weight: 500 !important;
  letter-spacing: 0.01em;
}
</style>
