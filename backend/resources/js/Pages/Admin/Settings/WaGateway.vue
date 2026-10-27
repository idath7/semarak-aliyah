<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  logs: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      total_messages: 0,
      success_messages: 0,
      failed_messages: 0,
      pending_messages: 0,
    }),
  },
  sessions: {
    type: Array,
    default: () => [],
  },
  groups: {
    type: Array,
    default: () => [],
  },
});

const { success, error, info } = useToast();
const { confirm: showConfirm } = useConfirmDialog();

const sessionsList = ref([...props.sessions]);
const isLoadingSessions = ref(false);

const sessionOptions = computed(() => {
  const list = sessionsList.value.map(s => ({
    value: s.id,
    label: s.label || `${s.name} (${s.phone || 'tanpa nomor'})`
  }));
  return [
    { value: 'default', label: '⚡ Auto-Detect Sesi Siap Terkoneksi' },
    ...list
  ];
});

async function refreshSessions() {
  isLoadingSessions.value = true;
  try {
    const res = await fetch('/admin/pengaturan/wa-gateway/sessions');
    const json = await res.json();
    if (json.status === 'success' && Array.isArray(json.data)) {
      sessionsList.value = json.data;
      success(`Berhasil memuat ${json.data.length} sesi dari OpenWA.`, 'Sesi Dimuat');
    }
  } catch (err) {
    error('Gagal memuat sesi: ' + err.message);
  } finally {
    isLoadingSessions.value = false;
  }
}

// WhatsApp Groups State & Refresh
const groupsList = ref([...props.groups]);
const isLoadingGroups = ref(false);

const groupOptions = computed(() => {
  const list = groupsList.value.map(g => ({
    value: g.id,
    label: g.name ? `${g.name} (${g.id.slice(0, 16)}...)` : g.id,
  }));
  return [
    { value: '', label: '-- Pilih Grup WhatsApp Terdeteksi --' },
    ...list,
    { value: 'custom', label: '➕ Tulis Manual ID Grup Lain...' },
  ];
});

async function refreshGroups() {
  isLoadingGroups.value = true;
  try {
    const res = await fetch('/admin/pengaturan/wa-gateway/groups');
    const json = await res.json();
    if (json.status === 'success' && Array.isArray(json.data)) {
      groupsList.value = json.data;
      success(`Berhasil memuat ${json.data.length} grup WhatsApp dari OpenWA.`, 'Grup Dimuat');
    }
  } catch (err) {
    error('Gagal memuat grup WhatsApp: ' + err.message);
  } finally {
    isLoadingGroups.value = false;
  }
}

// Form Data for Settings
const form = useForm({
  wa_gateway_enabled: props.settings.enabled ? '1' : '0',
  wa_gateway_provider: props.settings.provider || 'openwa',
  wa_gateway_url: props.settings.gateway_url || 'http://localhost:8085',
  wa_api_key: props.settings.api_key || '',
  wa_sender_session: props.settings.sender_session || 'default',
  wa_endpoint_send: props.settings.endpoint_send || '/sendText',
  wa_endpoint_status: props.settings.endpoint_status || '/check-auth',
  wa_auth_header_type: props.settings.auth_header_type || 'api_key',
  wa_chat_id_format: props.settings.chat_id_format || 'c_us',
  wa_ssl_bypass: props.settings.ssl_bypass !== false ? '1' : '0',
  wa_notify_new_registration: props.settings.notify_new_registration ? '1' : '0',
  wa_notify_admin_new_registration: props.settings.notify_admin_new_registration ? '1' : '0',
  wa_admin_notification_numbers: props.settings.admin_notification_numbers || '083850070767',
  wa_admin_group_id: props.settings.admin_group_id || '',
  wa_notify_admin_group: props.settings.notify_admin_group ? '1' : '0',
  wa_notify_verified_registration: props.settings.notify_verified_registration ? '1' : '0',
  wa_notify_verified_payment: props.settings.notify_verified_payment ? '1' : '0',
  wa_tpl_new_registration: props.settings.tpl_new_registration || '',
  wa_tpl_admin_notification: props.settings.tpl_admin_notification || '',
  wa_tpl_verified_registration: props.settings.tpl_verified_registration || '',
  wa_tpl_verified_payment: props.settings.tpl_verified_payment || '',
  wa_tpl_direct_registration: props.settings.tpl_direct_registration || '',
});

// Admin Group Selector state
const adminGroupSelect = ref(
  form.wa_admin_group_id
    ? (props.groups.some(g => g.id === form.wa_admin_group_id) ? form.wa_admin_group_id : 'custom')
    : ''
);

watch(adminGroupSelect, (newVal) => {
  if (newVal && newVal !== 'custom') {
    form.wa_admin_group_id = newVal;
  } else if (!newVal) {
    form.wa_admin_group_id = '';
  }
});

// Options for CustomSelect dropdowns
const providerOptions = [
  { value: 'openwa', label: 'OpenWA (wa-automate-nodejs / REST API Server)' },
];

const authHeaderOptions = [
  { value: 'api_key', label: 'api_key: <token> (Standar OpenWA REST API)' },
  { value: 'bearer', label: 'Authorization: Bearer <token>' },
  { value: 'x_api_key', label: 'X-Api-Key: <token>' },
];

const chatIdFormatOptions = [
  { value: 'c_us', label: '628xxx@c.us (Standar OpenWA)' },
  { value: 'number', label: '628xxx (Hanya Digit Nomor)' },
];

const filterTypeOptions = [
  { value: 'all', label: 'Semua Jenis Pesan' },
  { value: 'test', label: 'Uji Coba (Test)' },
  { value: 'new_registration', label: 'Pendaftaran Baru (Koordinator)' },
  { value: 'new_registration_admin', label: 'Pendaftaran Baru (Admin)' },
  { value: 'verification_registration', label: 'Verifikasi Berkas / Pendaftaran' },
  { value: 'verification_payment', label: 'Verifikasi Pembayaran (Lunas)' },
  { value: 'manual', label: 'Kirim Manual' },
];

const filterStatusOptions = [
  { value: 'all', label: 'Semua Status Pengiriman' },
  { value: 'success', label: 'Berhasil Terkirim (Sukses)' },
  { value: 'failed', label: 'Gagal Terkirim' },
  { value: 'pending', label: 'Dalam Antrean (Pending)' },
];

// Show/Hide API Key password toggle
const showApiKey = ref(false);

// Active template tab
const activeTemplateTab = ref('new_reg');

// Server connection test state
const isTestingConnection = ref(false);
const connectionResult = ref(null);

async function testConnection() {
  isTestingConnection.value = true;
  connectionResult.value = null;

  try {
    const res = await fetch('/admin/pengaturan/wa-gateway/test-connection', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });
    const data = await res.json();
    connectionResult.value = data;

    if (data.success) {
      success(data.message, 'Koneksi Berhasil');
    } else {
      error(data.message, 'Koneksi Gagal');
    }
  } catch (err) {
    connectionResult.value = {
      success: false,
      status: 'offline',
      message: 'Gagal menghubungi server: ' + err.message,
    };
    error('Gagal melakukan uji koneksi: ' + err.message);
  } finally {
    isTestingConnection.value = false;
  }
}

// Live test direct message state
const testMsgForm = ref({
  target_type: 'personal', // 'personal' or 'group'
  phone: '',
  group_id: '',
  custom_group_id: '',
  message: 'Halo, ini adalah pesan uji coba dari sistem WhatsApp Gateway SEMARAK ALIYAH XIV 2026 via OpenWA Production.',
});
const isSendingTest = ref(false);
const testSendResult = ref(null);

async function sendTestMessage() {
  const isGroup = testMsgForm.value.target_type === 'group';
  const targetGroupId = testMsgForm.value.group_id === 'custom'
    ? testMsgForm.value.custom_group_id
    : testMsgForm.value.group_id;

  if (!isGroup && !testMsgForm.value.phone) {
    error('Silakan isi nomor WhatsApp tujuan.');
    return;
  }
  if (isGroup && !targetGroupId) {
    error('Silakan pilih atau masukkan ID Grup WhatsApp tujuan.');
    return;
  }
  if (!testMsgForm.value.message) {
    error('Isi pesan tidak boleh kosong.');
    return;
  }

  isSendingTest.value = true;
  testSendResult.value = null;

  try {
    const res = await fetch('/admin/pengaturan/wa-gateway/send-test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        target_type: testMsgForm.value.target_type,
        phone: testMsgForm.value.phone,
        group_id: targetGroupId,
        message: testMsgForm.value.message,
      }),
    });
    const data = await res.json();
    testSendResult.value = data;

    if (data.success) {
      success(
        isGroup
          ? 'Pesan uji coba berhasil terkirim ke Grup WhatsApp!'
          : 'Pesan uji coba berhasil terkirim melalui OpenWA!',
        'Terkirim'
      );
      router.reload({ only: ['logs', 'stats'] });
    } else {
      error(data.message || 'Gagal mengirim pesan uji coba.', 'Gagal');
    }
  } catch (err) {
    testSendResult.value = {
      success: false,
      message: 'Kesalahan jaringan: ' + err.message,
    };
    error('Gagal mengirim pesan uji coba: ' + err.message);
  } finally {
    isSendingTest.value = false;
  }
}

// Save Settings
function submitSettings() {
  form.post('/admin/pengaturan/wa-gateway', {
    preserveScroll: true,
    onSuccess: () => {
      success('Konfigurasi WhatsApp Gateway OpenWA berhasil diperbarui!', 'Tersimpan');
    },
    onError: (errs) => {
      const msg = Object.values(errs)[0] || 'Gagal menyimpan pengaturan.';
      error(msg, 'Periksa Isian Form');
    },
  });
}

// Main Segmented Navigation Tab
const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
const activeMainTab = ref(urlParams.get('tab') || 'logs');

function setMainTab(tab) {
  activeMainTab.value = tab;
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState({}, '', url);
  }
}

// Reactive Log filters & search
const filterType = ref(urlParams.get('type') || 'all');
const filterStatus = ref(urlParams.get('status') || 'all');
const searchQuery = ref(urlParams.get('search') || '');

let searchDebounceTimer = null;

function applyLogFilter() {
  router.get(
    '/admin/pengaturan/wa-gateway',
    {
      tab: activeMainTab.value,
      type: filterType.value,
      status: filterStatus.value,
      search: searchQuery.value,
    },
    { preserveState: true, preserveScroll: true, only: ['logs'] }
  );
}

// Auto reactive filter on dropdown change
watch([filterType, filterStatus], () => {
  applyLogFilter();
});

// Auto reactive search with 350ms debounce
watch(searchQuery, () => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    applyLogFilter();
  }, 350);
});

function resetLogFilter() {
  filterType.value = 'all';
  filterStatus.value = 'all';
  searchQuery.value = '';
  applyLogFilter();
}

// Clear all logs
async function clearAllLogs() {
  const isConfirmed = await showConfirm({
    title: 'Bersihkan Semua Log WhatsApp?',
    message: 'Apakah Anda yakin ingin menghapus seluruh riwayat log pengiriman WhatsApp? Seluruh data riwayat pengiriman akan dihapus permanen.',
    type: 'danger',
    confirmText: 'Ya, Bersihkan Semua',
    cancelText: 'Batal',
  });

  if (!isConfirmed) return;

  try {
    const res = await fetch('/admin/pengaturan/wa-gateway/clear-logs', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });
    const data = await res.json();
    if (data.success) {
      success(data.message || 'Seluruh log WhatsApp berhasil dibersihkan.', 'Log Dibersihkan');
      router.reload({ only: ['logs', 'stats'] });
    } else {
      error(data.message || 'Gagal membersihkan log.');
    }
  } catch (err) {
    error('Gagal membersihkan log: ' + err.message);
  }
}

// Delete single log
async function deleteSingleLog(logId) {
  const isConfirmed = await showConfirm({
    title: 'Hapus Log Ini?',
    message: 'Apakah Anda yakin ingin menghapus baris riwayat log pengiriman ini?',
    type: 'danger',
    confirmText: 'Ya, Hapus',
    cancelText: 'Batal',
  });

  if (!isConfirmed) return;

  try {
    const res = await fetch(`/admin/pengaturan/wa-gateway/logs/${logId}/delete`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });
    const data = await res.json();
    if (data.success) {
      success(data.message || 'Log berhasil dihapus.', 'Terhapus');
      router.reload({ only: ['logs', 'stats'] });
    } else {
      error(data.message || 'Gagal menghapus log.');
    }
  } catch (err) {
    error('Gagal menghapus log: ' + err.message);
  }
}

// Resend message from log
function resendLog(logId) {
  router.post(
    `/admin/pengaturan/wa-gateway/resend/${logId}`,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        success('Permintaan kirim ulang berhasil diproses.');
      },
      onError: (err) => {
        error(Object.values(err)[0] || 'Gagal mengirim ulang pesan.');
      },
    }
  );
}

// Copy placeholder variable to clipboard
function copyVariable(v) {
  navigator.clipboard.writeText(v).then(() => {
    info(`Variabel ${v} disalin ke clipboard!`);
  });
}

const defaultDirectRegistrationTemplate = "Assalamu'alaikum Wr. Wb. Kak {nama_pj},\\n\\nSaya perwakilan dari {nama_sekolah}, bermaksud mendaftarkan delegasi sekolah kami untuk cabang lomba *{cabang_lomba}* pada agenda *{nama_event}*.\\n\\n• Asal Lembaga: {nama_sekolah}\\n• Cabang Lomba: {cabang_lomba}\\n• Biaya Pendaftaran: {biaya}\\n• Narahubung/Pembina: {nama_pendaftar} ({nomor_wa})\\n\\nMohon petunjuk teknis pendaftaran langsung, persyaratan berkas, serta ketersediaan kuota delegasi. Terima kasih.\\n\\nWassalamu'alaikum Wr. Wb.";

function resetToDefaultDirectTpl() {
  form.wa_tpl_direct_registration = defaultDirectRegistrationTemplate;
  info('Template pendaftaran via PJ dipulihkan ke format standar.');
}

// Modal View Full Message
const selectedLogMessage = ref(null);
const showMessageModal = ref(false);

function viewLogMessage(log) {
  selectedLogMessage.value = log;
  showMessageModal.value = true;
}

const successRate = computed(() => {
  const total = props.stats.total_messages;
  if (!total) return 100;
  return Math.round((props.stats.success_messages / total) * 100);
});
</script>

<template>
  <Head title="WA Gateway | Pengaturan Admin" />

  <VelzonLayout>
    <div class="container-fluid wa-gateway-page">
      <!-- Breadcrumb & Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-transparent">
            <div>
              <h4 class="mb-sm-0 fw-bold d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-whatsapp-line text-success fs-22"></i>
                WA Gateway (OpenWA)
              </h4>
              <p class="text-muted fs-12 mb-0 mt-1">
                Kelola integrasi notifikasi pesan WhatsApp otomatis, verifikasi status koneksi OpenWA REST API, kustomisasi template, uji kirim langsung, dan pantau log pengiriman.
              </p>
            </div>
            <div class="page-title-right mt-2 mt-sm-0">
              <ol class="breadcrumb m-0 fs-12">
                <li class="breadcrumb-item"><Link href="/admin">Dashboard</Link></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active text-success">WA Gateway</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Submenu Tabs -->
      <div class="row mb-3">
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
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-tools-line text-warning"></i>
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
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/wa-gateway"
                    class="nav-link active px-3 py-2 d-flex align-items-center gap-2 rounded-pill fw-semibold fs-13 bg-success text-white shadow-sm"
                  >
                    <i class="ri-whatsapp-line"></i>
                    <span>WA Gateway</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/kebijakan-pendaftaran"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-user-settings-line text-primary"></i>
                    <span>Kebijakan Pendaftaran</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/login-admin"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-image-edit-line text-info"></i>
                    <span>Login Panel Admin</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link
                    href="/admin/pengaturan/sipresensi"
                    class="nav-link px-3 py-2 d-flex align-items-center gap-2 rounded-pill text-muted fw-semibold fs-13 hover:text-white"
                  >
                    <i class="ri-shield-keyhole-line text-success"></i>
                    <span>API Sipresensi</span>
                  </Link>
                </li>
              </ul>

              <div class="d-flex align-items-center gap-2">
                <button
                  type="button"
                  class="btn btn-sm btn-outline-success rounded-pill px-3 d-flex align-items-center gap-1.5"
                  :disabled="isTestingConnection"
                  @click="testConnection"
                >
                  <i class="ri-pulse-line" :class="{ 'ri-spin': isTestingConnection }"></i>
                  <span>{{ isTestingConnection ? 'Menguji...' : 'Uji Koneksi Server' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Compact Top Summary Bar: Status, Host, Latency, Metrics -->
      <div class="row g-2 mb-3">
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-2.5 d-flex flex-wrap align-items-center justify-content-between gap-3">
              <!-- Left: Service Status Switch -->
              <div class="d-flex align-items-center gap-2.5">
                <div class="form-check form-switch m-0">
                  <input
                    id="gatewaySwitch"
                    v-model="form.wa_gateway_enabled"
                    class="form-check-input cursor-pointer"
                    type="checkbox"
                    role="switch"
                    true-value="1"
                    false-value="0"
                    @change="submitSettings"
                  />
                </div>
                <div class="d-flex align-items-center gap-2">
                  <span
                    class="badge rounded-pill fs-11 px-2.5 py-1 d-inline-flex align-items-center gap-1.5"
                    :class="form.wa_gateway_enabled === '1' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-muted border border-secondary-subtle'"
                  >
                    <span :class="form.wa_gateway_enabled === '1' ? 'online-indicator' : 'offline-indicator'"></span>
                    {{ form.wa_gateway_enabled === '1' ? 'Gateway Aktif' : 'Gateway Nonaktif' }}
                  </span>
                  <span class="fs-11 text-muted d-none d-md-inline font-monospace">
                    {{ form.wa_gateway_url }}
                  </span>
                </div>
              </div>

              <!-- Connection test result banner if just tested -->
              <div v-if="connectionResult" class="d-flex align-items-center gap-2 fs-11 px-2.5 py-1 rounded-pill" :class="connectionResult.success ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'">
                <i :class="connectionResult.success ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill'"></i>
                <span class="fw-semibold">{{ connectionResult.message }}</span>
                <span v-if="connectionResult.response_time_ms" class="opacity-75">({{ connectionResult.response_time_ms }}ms)</span>
              </div>

              <!-- Right: 4 Compact Counter Badges -->
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="badge bg-body-secondary text-dark-emphasis border px-2.5 py-1.5 rounded-pill fs-11 d-flex align-items-center gap-1.5">
                  <span class="text-muted">Total:</span>
                  <strong>{{ stats.total_messages }}</strong>
                </div>
                <div class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill fs-11 d-flex align-items-center gap-1.5">
                  <i class="ri-check-line"></i>
                  <span>Berhasil:</span>
                  <strong>{{ stats.success_messages }}</strong>
                </div>
                <div class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill fs-11 d-flex align-items-center gap-1.5">
                  <i class="ri-close-line"></i>
                  <span>Gagal:</span>
                  <strong>{{ stats.failed_messages }}</strong>
                </div>
                <div class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill fs-11 d-flex align-items-center gap-1.5">
                  <i class="ri-speed-line"></i>
                  <span>Delivery:</span>
                  <strong>{{ successRate }}%</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Segmented Secondary Tab Switcher (Compact Navigation) -->
      <div class="row mb-3">
        <div class="col-12">
          <ul class="nav nav-pills gap-1.5 p-1 bg-body-tertiary rounded-4 border d-flex flex-wrap" role="tablist">
            <li class="nav-item">
              <button
                type="button"
                class="nav-link px-3 py-1.5 rounded-pill fs-12 fw-semibold d-flex align-items-center gap-2 transition-all cursor-pointer"
                :class="activeMainTab === 'logs' ? 'bg-success text-white shadow-sm' : 'text-dark-emphasis'"
                @click="setMainTab('logs')"
              >
                <i class="ri-history-line"></i>
                <span>Log Pengiriman & Uji Coba</span>
                <span
                  v-if="stats.total_messages"
                  class="badge rounded-pill fs-10 px-1.5 py-0.5"
                  :class="activeMainTab === 'logs' ? 'bg-white text-success' : 'bg-body-secondary text-muted'"
                >
                  {{ stats.total_messages }}
                </span>
              </button>
            </li>
            <li class="nav-item">
              <button
                type="button"
                class="nav-link px-3 py-1.5 rounded-pill fs-12 fw-semibold d-flex align-items-center gap-2 transition-all cursor-pointer"
                :class="activeMainTab === 'server' ? 'bg-success text-white shadow-sm' : 'text-dark-emphasis'"
                @click="setMainTab('server')"
              >
                <i class="ri-server-line"></i>
                <span>Server & Sesi OpenWA</span>
              </button>
            </li>
            <li class="nav-item">
              <button
                type="button"
                class="nav-link px-3 py-1.5 rounded-pill fs-12 fw-semibold d-flex align-items-center gap-2 transition-all cursor-pointer"
                :class="activeMainTab === 'automation' ? 'bg-success text-white shadow-sm' : 'text-dark-emphasis'"
                @click="setMainTab('automation')"
              >
                <i class="ri-notification-3-line"></i>
                <span>Otomasi Notifikasi</span>
              </button>
            </li>
            <li class="nav-item">
              <button
                type="button"
                class="nav-link px-3 py-1.5 rounded-pill fs-12 fw-semibold d-flex align-items-center gap-2 transition-all cursor-pointer"
                :class="activeMainTab === 'templates' ? 'bg-success text-white shadow-sm' : 'text-dark-emphasis'"
                @click="setMainTab('templates')"
              >
                <i class="ri-message-3-line"></i>
                <span>Template Pesan</span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <!-- TAB 1: LOGS & DIRECT TEST -->
      <div v-show="activeMainTab === 'logs'">
        <!-- Fast Direct Test Message Card (Compact 1-row layout) -->
        <div class="card border-0 shadow-sm rounded-4 mb-3">
          <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <span class="fs-12 fw-bold text-dark-emphasis d-flex align-items-center gap-1.5">
                <i class="ri-send-plane-2-line text-success"></i>
                Uji Coba Kirim Cepat (Direct Test Message)
              </span>

              <!-- Target Type Selector -->
              <div class="btn-group btn-group-sm p-0.5 bg-body-tertiary rounded-pill border" role="group">
                <button
                  type="button"
                  class="btn rounded-pill px-2.5 py-0.5 fs-11 fw-semibold d-flex align-items-center gap-1 transition-all"
                  :class="testMsgForm.target_type === 'personal' ? 'btn-success text-white shadow-sm' : 'btn-ghost text-muted'"
                  @click="testMsgForm.target_type = 'personal'"
                >
                  <i class="ri-user-line"></i> Nomor Pribadi
                </button>
                <button
                  type="button"
                  class="btn rounded-pill px-2.5 py-0.5 fs-11 fw-semibold d-flex align-items-center gap-1 transition-all"
                  :class="testMsgForm.target_type === 'group' ? 'btn-success text-white shadow-sm' : 'btn-ghost text-muted'"
                  @click="testMsgForm.target_type = 'group'"
                >
                  <i class="ri-group-line"></i> Grup WA
                  <span v-if="groupsList.length > 0" class="badge bg-white text-success fs-9 rounded-pill ms-0.5">
                    {{ groupsList.length }}
                  </span>
                </button>
              </div>
            </div>

            <div class="row g-2 align-items-center">
              <!-- Target Input (Personal) -->
              <div v-if="testMsgForm.target_type === 'personal'" class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-body text-success"><i class="ri-whatsapp-line"></i></span>
                  <input
                    v-model="testMsgForm.phone"
                    type="text"
                    class="form-control"
                    placeholder="08xxx / 628xxx"
                    @keyup.enter="sendTestMessage"
                  />
                </div>
              </div>

              <!-- Target Input (Group) -->
              <div v-else class="col-12 col-md-3">
                <CustomSelect
                  v-model="testMsgForm.group_id"
                  :options="groupOptions"
                  placeholder="Pilih Grup WA..."
                />
                <input
                  v-if="testMsgForm.group_id === 'custom'"
                  v-model="testMsgForm.custom_group_id"
                  type="text"
                  class="form-control form-control-sm font-monospace mt-1"
                  placeholder="120363xxx@g.us"
                />
              </div>

              <!-- Message Input -->
              <div class="col-12 col-md-7">
                <input
                  v-model="testMsgForm.message"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Ketik pesan uji coba..."
                  @keyup.enter="sendTestMessage"
                />
              </div>

              <!-- Submit Button -->
              <div class="col-12 col-md-2">
                <button
                  type="button"
                  class="btn btn-sm btn-success w-100 rounded-pill py-1.5 fw-semibold d-flex align-items-center justify-content-center gap-1.5 shadow-sm"
                  :disabled="isSendingTest"
                  @click="sendTestMessage"
                >
                  <i class="ri-send-plane-fill" :class="{ 'ri-spin': isSendingTest }"></i>
                  <span>{{ isSendingTest ? 'Mengirim...' : 'Kirim Tes' }}</span>
                </button>
              </div>
            </div>

            <!-- Test Result Banner if any -->
            <div v-if="testSendResult" class="mt-2">
              <div
                class="p-2 rounded-3 fs-11.5 d-flex align-items-center justify-content-between"
                :class="testSendResult.success ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'"
              >
                <div class="d-flex align-items-center gap-1.5">
                  <i :class="testSendResult.success ? 'ri-checkbox-circle-fill text-success fs-15' : 'ri-error-warning-fill text-danger fs-15'"></i>
                  <span class="fw-semibold">{{ testSendResult.message }}</span>
                </div>
                <button type="button" class="btn-close fs-10" @click="testSendResult = null"></button>
              </div>
            </div>
          </div>
        </div>

        <!-- WhatsApp Delivery Logs Table Card -->
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-transparent border-0 pt-3 px-3 pb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
              <h5 class="card-title fw-bold fs-14 mb-0.5 d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-history-line text-info"></i>
                Riwayat Log Pengiriman WhatsApp
              </h5>
              <p class="text-muted fs-11 mb-0">
                Audit riwayat pengiriman pesan otomatis dan manual (real-time).
              </p>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button
                type="button"
                class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 fs-11 d-flex align-items-center gap-1"
                @click="clearAllLogs"
                :disabled="!logs.length"
                title="Hapus seluruh log pengiriman"
              >
                <i class="ri-delete-bin-line"></i> Bersihkan Semua Log
              </button>
            </div>
          </div>

          <div class="card-body p-3 pt-0">
            <!-- Filter Controls Bar (Auto Reactive) -->
            <div class="row g-2 mb-3 bg-body-tertiary p-2 rounded-3 align-items-center">
              <div class="col-12 col-md-3">
                <CustomSelect
                  v-model="filterType"
                  :options="filterTypeOptions"
                  placeholder="Semua Jenis Pesan"
                />
              </div>
              <div class="col-12 col-md-3">
                <CustomSelect
                  v-model="filterStatus"
                  :options="filterStatusOptions"
                  placeholder="Semua Status"
                />
              </div>
              <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-body text-muted"><i class="ri-search-line"></i></span>
                  <input
                    v-model="searchQuery"
                    type="text"
                    class="form-control"
                    placeholder="Cari otomatis: nomor, nama, atau isi pesan..."
                  />
                  <button
                    v-if="searchQuery"
                    type="button"
                    class="btn btn-sm btn-light border"
                    @click="searchQuery = ''"
                    title="Hapus pencarian"
                  >
                    <i class="ri-close-line"></i>
                  </button>
                </div>
              </div>
              <div class="col-12 col-md-1 d-flex">
                <button
                  type="button"
                  class="btn btn-sm btn-light border rounded-pill w-100 py-1.5"
                  @click="resetLogFilter"
                  title="Reset semua filter"
                >
                  <i class="ri-refresh-line"></i>
                </button>
              </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
              <table class="table table-hover table-nowrap align-middle mb-0 fs-12">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width: 40px;">#</th>
                    <th scope="col" style="width: 140px;">Waktu (24 Jam)</th>
                    <th scope="col" style="min-width: 180px;">Penerima & Nomor</th>
                    <th scope="col" style="width: 160px;">Jenis Pesan</th>
                    <th scope="col" style="min-width: 260px;">Pratinjau Pesan</th>
                    <th scope="col" class="text-center" style="width: 110px;">Status</th>
                    <th scope="col" class="text-end" style="width: 100px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(log, idx) in logs" :key="log.id">
                    <td class="text-muted">{{ idx + 1 }}</td>
                    <td class="fw-semibold text-dark-emphasis font-monospace fs-11">
                      {{ log.created_at }}
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <i
                          :class="log.phone && log.phone.includes('@g.us') ? 'ri-group-line text-purple fs-15' : 'ri-user-line text-muted fs-14'"
                        ></i>
                        <div>
                          <div class="fw-bold text-dark-emphasis d-flex align-items-center gap-1.5">
                            <span>{{ log.recipient_name }}</span>
                            <span
                              v-if="log.phone && log.phone.includes('@g.us')"
                              class="badge bg-purple-subtle text-purple border border-purple-subtle fs-9 px-1.5 py-0 rounded-pill"
                            >
                              Grup WA
                            </span>
                          </div>
                          <div class="fs-11 font-monospace text-muted">{{ log.phone }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span
                        v-if="log.type === 'new_registration'"
                        class="badge bg-primary-subtle text-primary border border-primary-subtle"
                      >
                        Pendaftaran Baru
                      </span>
                      <span
                        v-else-if="log.type === 'new_registration_admin'"
                        class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                      >
                        Tembusan Admin
                      </span>
                      <span
                        v-else-if="log.type === 'verification_registration'"
                        class="badge bg-info-subtle text-info border border-info-subtle"
                      >
                        Verifikasi Berkas
                      </span>
                      <span
                        v-else-if="log.type === 'verification_payment'"
                        class="badge bg-success-subtle text-success border border-success-subtle"
                      >
                        Verifikasi Pembayaran
                      </span>
                      <span
                        v-else-if="log.type === 'test'"
                        class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                      >
                        Uji Coba
                      </span>
                      <span
                        v-else
                        class="badge bg-dark-subtle text-dark border border-dark-subtle"
                      >
                        {{ log.type }}
                      </span>
                    </td>
                    <td>
                      <div
                        class="text-truncate cursor-pointer hover:text-success text-dark-emphasis"
                        style="max-width: 320px;"
                        @click="viewLogMessage(log)"
                        title="Klik untuk melihat pesan lengkap"
                      >
                        {{ log.message }}
                      </div>
                    </td>
                    <td class="text-center">
                      <span
                        v-if="log.status === 'success'"
                        class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1"
                      >
                        <i class="ri-check-line"></i> Terkirim
                      </span>
                      <span
                        v-else-if="log.status === 'failed'"
                        class="badge bg-danger-subtle text-danger border border-danger-subtle d-inline-flex align-items-center gap-1"
                        :title="log.error_message"
                      >
                        <i class="ri-close-line"></i> Gagal
                      </span>
                      <span
                        v-else
                        class="badge bg-warning-subtle text-warning border border-warning-subtle"
                      >
                        Pending
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-flex align-items-center justify-content-end gap-1">
                        <button
                          type="button"
                          class="btn btn-sm btn-light border p-1 rounded"
                          title="Lihat pesan lengkap"
                          @click="viewLogMessage(log)"
                        >
                          <i class="ri-eye-line text-muted"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-sm btn-light border p-1 rounded"
                          title="Kirim ulang pesan ini"
                          @click="resendLog(log.id)"
                        >
                          <i class="ri-restart-line text-success"></i>
                        </button>
                        <button
                          type="button"
                          class="btn btn-sm btn-light border p-1 rounded"
                          title="Hapus baris log ini"
                          @click="deleteSingleLog(log.id)"
                        >
                          <i class="ri-delete-bin-line text-danger"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!logs.length">
                    <td colspan="7" class="text-center py-5 text-muted">
                      <i class="ri-inbox-line fs-32 d-block mb-1.5 opacity-50"></i>
                      <span>Tidak ada data log WhatsApp yang sesuai.</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: SERVER & SESSIONS -->
      <div v-show="activeMainTab === 'server'">
        <form @submit.prevent="submitSettings">
          <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-transparent border-0 pt-3 px-3.5 pb-0">
              <h5 class="card-title fw-bold fs-15 mb-1 d-flex align-items-center gap-2 text-dark-emphasis">
                <i class="ri-server-line text-primary"></i>
                Konfigurasi Server OpenWA REST API
              </h5>
              <p class="text-muted fs-11.5 mb-0">
                Parameter koneksi ke instance OpenWA (wa-automate-nodejs / Cloud Multi-Session) yang berjalan di production.
              </p>
            </div>

            <div class="card-body p-3.5 space-y-3">
              <div class="row g-3">
                <!-- Provider -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Provider WhatsApp Gateway
                  </label>
                  <CustomSelect
                    v-model="form.wa_gateway_provider"
                    :options="providerOptions"
                    placeholder="Pilih Provider"
                  />
                  <small class="fs-10.5 text-muted d-block mt-1">
                    Teroptimasi penuh untuk arsitektur OpenWA REST API.
                  </small>
                </div>

                <!-- Host / Base URL -->
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Base URL Server OpenWA <span class="text-danger">*</span>
                  </label>
                  <div class="input-group input-group-sm">
                    <span class="input-group-text bg-body text-muted"><i class="ri-link"></i></span>
                    <input
                      v-model="form.wa_gateway_url"
                      type="url"
                      class="form-control"
                      placeholder="https://wacld.maswahas.sch.id"
                      required
                    />
                  </div>
                  <small class="fs-10.5 text-muted d-block mt-1">
                    Alamat host server OpenWA.
                  </small>
                </div>
              </div>

              <!-- SSL Verify Bypass Switch -->
              <div class="p-2.5 rounded-3 border bg-body-tertiary">
                <div class="d-flex align-items-center justify-content-between">
                  <div>
                    <div class="fw-bold fs-12 text-dark-emphasis d-flex align-items-center gap-1.5">
                      <i class="ri-shield-keyhole-line text-warning"></i>
                      Abaikan Verifikasi Sertifikat SSL (SSL Verify Bypass)
                    </div>
                    <p class="fs-11 text-muted mb-0 mt-0.5">
                      Mencegah error <code>cURL error 60: SSL certificate problem</code> saat server PHP lokal menghubungi HTTPS OpenWA.
                    </p>
                  </div>
                  <div class="form-check form-switch m-0 ms-3">
                    <input
                      id="sslBypassSwitch"
                      v-model="form.wa_ssl_bypass"
                      class="form-check-input cursor-pointer"
                      type="checkbox"
                      true-value="1"
                      false-value="0"
                    />
                  </div>
                </div>
              </div>

              <!-- API Key / Secret Token -->
              <div>
                <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1 d-flex justify-content-between">
                  <span>API Key / Secret Token OpenWA</span>
                  <span class="text-muted fs-11 fw-normal">(Opsional jika server tanpa proteksi token)</span>
                </label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text bg-body text-muted"><i class="ri-key-2-line"></i></span>
                  <input
                    v-model="form.wa_api_key"
                    :type="showApiKey ? 'text' : 'password'"
                    class="form-control"
                    placeholder="Masukkan API Key OpenWA..."
                  />
                  <button
                    type="button"
                    class="btn btn-outline-secondary"
                    @click="showApiKey = !showApiKey"
                  >
                    <i :class="showApiKey ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                  </button>
                </div>
              </div>

              <!-- Auth Header Type & ChatId Format Grid -->
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Header Otorisasi
                  </label>
                  <CustomSelect
                    v-model="form.wa_auth_header_type"
                    :options="authHeaderOptions"
                    placeholder="Pilih Tipe Header"
                  />
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Format Chat ID Tujuan
                  </label>
                  <CustomSelect
                    v-model="form.wa_chat_id_format"
                    :options="chatIdFormatOptions"
                    placeholder="Format Chat ID"
                  />
                </div>
              </div>

              <!-- Endpoint Send & Status Grid -->
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Endpoint Kirim Pesan
                  </label>
                  <input
                    v-model="form.wa_endpoint_send"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="/sendText"
                    required
                  />
                  <small class="fs-10 text-muted d-block mt-0.5">Standar: <code>/sendText</code></small>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-1">
                    Endpoint Cek Status
                  </label>
                  <input
                    v-model="form.wa_endpoint_status"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="/check-auth"
                    required
                  />
                  <small class="fs-10 text-muted d-block mt-0.5">Standar: <code>/check-auth</code> atau <code>/getMe</code></small>
                </div>
              </div>

              <!-- Session / Device Selection -->
              <div>
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <label class="form-label fs-12 fw-semibold text-dark-emphasis mb-0">
                    Pilih Sesi WhatsApp Aktif (OpenWA Multi-Session)
                  </label>
                  <button
                    type="button"
                    class="btn btn-link btn-sm p-0 fs-11 text-primary text-decoration-none fw-semibold d-flex align-items-center gap-1"
                    :disabled="isLoadingSessions"
                    @click="refreshSessions"
                  >
                    <i class="ri-refresh-line" :class="{ 'ri-spin': isLoadingSessions }"></i>
                    <span>Muat Ulang Sesi</span>
                  </button>
                </div>
                <CustomSelect
                  v-model="form.wa_sender_session"
                  :options="sessionOptions"
                  placeholder="Pilih Sesi WhatsApp..."
                />
                <div class="d-flex align-items-center justify-content-between mt-1">
                  <small class="fs-10.5 text-muted">
                    Pilih sesi aktif OpenWA atau gunakan opsi <strong>Auto-Detect</strong> untuk otomatis memilih sesi berstatus Ready.
                  </small>
                  <small v-if="sessionsList.length > 0" class="badge bg-success-subtle text-success border border-success-subtle fs-10 px-1.5 py-0.5 rounded-pill">
                    {{ sessionsList.length }} Sesi Terdeteksi
                  </small>
                </div>
              </div>
            </div>

            <!-- Card Footer Action Buttons -->
            <div class="card-footer bg-transparent p-3 d-flex align-items-center justify-content-between border-top">
              <button
                type="button"
                class="btn btn-sm btn-outline-success rounded-pill px-3 d-flex align-items-center gap-1.5"
                :disabled="isTestingConnection"
                @click="testConnection"
              >
                <i class="ri-pulse-line" :class="{ 'ri-spin': isTestingConnection }"></i>
                <span>{{ isTestingConnection ? 'Menguji...' : 'Uji Koneksi Server' }}</span>
              </button>

              <button
                type="submit"
                class="btn btn-sm btn-success rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-1.5"
                :disabled="form.processing"
              >
                <i class="ri-save-line" :class="{ 'ri-spin': form.processing }"></i>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi Server' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>

      <!-- TAB 3: NOTIFICATION AUTOMATION TRIGGERS -->
      <div v-show="activeMainTab === 'automation'">
        <form @submit.prevent="submitSettings">
          <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-transparent border-0 pt-3 px-3.5 pb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div>
                <h5 class="card-title fw-bold fs-14 mb-0.5 d-flex align-items-center gap-2 text-dark-emphasis">
                  <i class="ri-notification-3-line text-warning"></i>
                  Otomasi Pemicu Notifikasi WhatsApp
                </h5>
                <p class="text-muted fs-11 mb-0">
                  Pilih peristiwa yang otomatis memicu pengiriman pesan WhatsApp secara instan.
                </p>
              </div>

              <button
                type="submit"
                class="btn btn-sm btn-success rounded-pill px-3.5 py-1 fs-11.5 fw-semibold shadow-sm d-flex align-items-center gap-1.5"
                :disabled="form.processing"
              >
                <i class="ri-save-line" :class="{ 'ri-spin': form.processing }"></i>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
              </button>
            </div>

            <div class="card-body p-3 pt-0">
              <div class="row g-2.5">
                <!-- Left Column: Notifikasi Koordinator Pendaftar (3 Triggers) -->
                <div class="col-12 col-lg-6">
                  <div class="border rounded-3 p-2.5 h-100 bg-body-tertiary">
                    <div class="fs-11 fw-bold text-uppercase tracking-wider text-muted mb-2 d-flex align-items-center gap-1">
                      <i class="ri-user-follow-line text-success"></i>
                      Notifikasi Untuk Koordinator Pendaftar
                    </div>

                    <div class="d-flex flex-column gap-2">
                      <!-- Trigger 1: Pendaftaran Baru -->
                      <div class="p-2 rounded-2 bg-body border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fs-13 flex-shrink-0">
                            <i class="ri-user-add-line"></i>
                          </div>
                          <div>
                            <div class="fw-semibold fs-12 text-dark-emphasis">Pendaftaran Baru Masuk</div>
                            <div class="fs-10.5 text-muted">Kirim kode registrasi, rincian biaya & panduan</div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0 ms-2">
                          <input
                            v-model="form.wa_notify_new_registration"
                            class="form-check-input cursor-pointer"
                            type="checkbox"
                            true-value="1"
                            false-value="0"
                          />
                        </div>
                      </div>

                      <!-- Trigger 2: Berkas Terverifikasi -->
                      <div class="p-2 rounded-2 bg-body border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar-xs rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center fs-13 flex-shrink-0">
                            <i class="ri-file-check-line"></i>
                          </div>
                          <div>
                            <div class="fw-semibold fs-12 text-dark-emphasis">Berkas Disahkan Panitia</div>
                            <div class="fs-10.5 text-muted">Kabari koordinator saat verifikasi berkas disetujui</div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0 ms-2">
                          <input
                            v-model="form.wa_notify_verified_registration"
                            class="form-check-input cursor-pointer"
                            type="checkbox"
                            true-value="1"
                            false-value="0"
                          />
                        </div>
                      </div>

                      <!-- Trigger 3: Pembayaran Lunas -->
                      <div class="p-2 rounded-2 bg-body border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                          <div class="avatar-xs rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fs-13 flex-shrink-0">
                            <i class="ri-secure-payment-line"></i>
                          </div>
                          <div>
                            <div class="fw-semibold fs-12 text-dark-emphasis">Pembayaran Lunas Terverifikasi</div>
                            <div class="fs-10.5 text-muted">Kirim tanda terima resmi saat bendahara validasi lunas</div>
                          </div>
                        </div>
                        <div class="form-check form-switch m-0 ms-2">
                          <input
                            v-model="form.wa_notify_verified_payment"
                            class="form-check-input cursor-pointer"
                            type="checkbox"
                            true-value="1"
                            false-value="0"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right Column: Tembusan Admin Panitia & Grup WA (1 Trigger + Targets) -->
                <div class="col-12 col-lg-6">
                  <div class="border rounded-3 p-2.5 h-100 bg-body-tertiary d-flex flex-column justify-content-between">
                    <div>
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fs-11 fw-bold text-uppercase tracking-wider text-muted d-flex align-items-center gap-1">
                          <i class="ri-shield-user-line text-warning"></i>
                          Tembusan Masuk Panitia & Grup
                        </div>
                        <div class="form-check form-switch m-0 ms-2">
                          <input
                            v-model="form.wa_notify_admin_new_registration"
                            class="form-check-input cursor-pointer"
                            type="checkbox"
                            true-value="1"
                            false-value="0"
                          />
                        </div>
                      </div>

                      <div v-if="form.wa_notify_admin_new_registration === '1'" class="space-y-2">
                        <!-- Nomor WhatsApp Pribadi Admin -->
                        <div class="p-2 rounded-2 bg-body border">
                          <label class="form-label fs-11 fw-semibold text-dark-emphasis mb-1 d-flex align-items-center gap-1">
                            <i class="ri-user-line text-primary"></i>
                            Nomor WhatsApp Panitia:
                          </label>
                          <input
                            v-model="form.wa_admin_notification_numbers"
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="083850070767 (pisahkan koma jika > 1)"
                          />
                        </div>

                        <!-- Grup WhatsApp Panitia -->
                        <div class="p-2 rounded-2 bg-body border">
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-11 fw-semibold text-dark-emphasis d-flex align-items-center gap-1">
                              <i class="ri-group-line text-purple"></i>
                              Kirim ke Grup WhatsApp Panitia
                            </span>
                            <div class="form-check form-switch m-0 ms-2">
                              <input
                                v-model="form.wa_notify_admin_group"
                                class="form-check-input cursor-pointer"
                                type="checkbox"
                                true-value="1"
                                false-value="0"
                              />
                            </div>
                          </div>

                          <div v-if="form.wa_notify_admin_group === '1'" class="mt-1.5 pt-1.5 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                              <span class="fs-10.5 text-muted">Pilih Grup:</span>
                              <button
                                type="button"
                                class="btn btn-link btn-sm p-0 fs-10 text-primary text-decoration-none fw-semibold d-flex align-items-center gap-0.5"
                                :disabled="isLoadingGroups"
                                @click="refreshGroups"
                              >
                                <i class="ri-refresh-line" :class="{ 'ri-spin': isLoadingGroups }"></i>
                                Muat Ulang
                              </button>
                            </div>
                            <CustomSelect
                              v-model="adminGroupSelect"
                              :options="groupOptions"
                              placeholder="Pilih Grup WA Panitia..."
                            />
                            <input
                              v-if="adminGroupSelect === 'custom'"
                              v-model="form.wa_admin_group_id"
                              type="text"
                              class="form-control form-control-sm font-monospace mt-1"
                              placeholder="120363xxx@g.us"
                            />
                          </div>
                        </div>
                      </div>
                      <div v-else class="p-3 text-center text-muted fs-11 bg-body rounded-2 border">
                        <i class="ri-notification-off-line fs-20 d-block mb-1 opacity-50"></i>
                        Tembusan notifikasi panitia sedang dinonaktifkan.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- TAB 4: MESSAGE TEMPLATES -->
      <div v-show="activeMainTab === 'templates'">
        <form @submit.prevent="submitSettings">
          <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-transparent border-0 pt-3 px-3.5 pb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
              <div>
                <h5 class="card-title fw-bold fs-15 mb-1 d-flex align-items-center gap-2 text-dark-emphasis">
                  <i class="ri-message-3-line text-success"></i>
                  Kustomisasi Template Pesan WhatsApp
                </h5>
                <p class="text-muted fs-11.5 mb-0">
                  Sesuaikan redaksi pesan otomatis. Klik pada variabel tag dinamis di bawah untuk menyalin ke clipboard.
                </p>
              </div>

              <!-- Variable Cheatsheet Pills -->
              <div class="d-flex flex-wrap gap-1">
                <button
                  v-for="tag in ['{nama_sekolah}', '{nama_koordinator}', '{nama_pj}', '{kode_pendaftaran}', '{cabang_lomba}', '{paket_pendaftaran}', '{total_biaya}', '{biaya}', '{nomor_wa}', '{nama_pendaftar}', '{kontak_pj}', '{status_pendaftaran}', '{status_pembayaran}', '{url_status}']"
                  :key="tag"
                  type="button"
                  class="badge bg-body-secondary text-dark-emphasis border px-2 py-1 cursor-pointer hover:bg-success hover:text-white transition-all font-monospace fs-11"
                  @click="copyVariable(tag)"
                  :title="'Klik untuk salin ' + tag"
                >
                  {{ tag }}
                </button>
              </div>
            </div>

            <div class="card-body p-3.5">
              <!-- Nav Tabs for Templates -->
              <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                <li class="nav-item">
                  <button
                    type="button"
                    class="nav-link fs-12 fw-semibold cursor-pointer"
                    :class="{ 'active': activeTemplateTab === 'new_reg' }"
                    @click="activeTemplateTab = 'new_reg'"
                  >
                    <i class="ri-user-add-line me-1 text-primary"></i> Pendaftaran Baru (Koordinator)
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    type="button"
                    class="nav-link fs-12 fw-semibold cursor-pointer"
                    :class="{ 'active': activeTemplateTab === 'admin_notif' }"
                    @click="activeTemplateTab = 'admin_notif'"
                  >
                    <i class="ri-notification-badge-line me-1 text-warning"></i> Pendaftaran Baru (Admin)
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    type="button"
                    class="nav-link fs-12 fw-semibold cursor-pointer"
                    :class="{ 'active': activeTemplateTab === 'reg_verified' }"
                    @click="activeTemplateTab = 'reg_verified'"
                  >
                    <i class="ri-file-check-line me-1 text-info"></i> Berkas Terverifikasi
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    type="button"
                    class="nav-link fs-12 fw-semibold cursor-pointer"
                    :class="{ 'active': activeTemplateTab === 'pay_verified' }"
                    @click="activeTemplateTab = 'pay_verified'"
                  >
                    <i class="ri-checkbox-circle-line me-1 text-success"></i> Pembayaran Lunas
                  </button>
                </li>
                <li class="nav-item">
                  <button
                    type="button"
                    class="nav-link fs-12 fw-semibold cursor-pointer"
                    :class="{ 'active': activeTemplateTab === 'direct_reg' }"
                    @click="activeTemplateTab = 'direct_reg'"
                  >
                    <i class="ri-whatsapp-line me-1 text-success"></i> Daftar via PJ / Koordinator
                  </button>
                </li>
              </ul>

              <!-- Tab Contents -->
              <div class="tab-content">
                <!-- Tab 1: New Reg Coordinator -->
                <div v-show="activeTemplateTab === 'new_reg'">
                  <textarea
                    v-model="form.wa_tpl_new_registration"
                    rows="8"
                    class="form-control font-monospace fs-12"
                    placeholder="Tuliskan template notifikasi pendaftaran baru untuk koordinator sekolah..."
                  ></textarea>
                  <small class="fs-11 text-muted mt-1.5 d-block">
                    Format WhatsApp: <code>*tebal*</code>, <code>_miring_</code>, <code>~coret~</code>.
                  </small>
                </div>

                <!-- Tab 2: New Reg Admin -->
                <div v-show="activeTemplateTab === 'admin_notif'">
                  <textarea
                    v-model="form.wa_tpl_admin_notification"
                    rows="8"
                    class="form-control font-monospace fs-12"
                    placeholder="Tuliskan template notifikasi pendaftaran masuk untuk panitia..."
                  ></textarea>
                </div>

                <!-- Tab 3: Berkas Terverifikasi -->
                <div v-show="activeTemplateTab === 'reg_verified'">
                  <textarea
                    v-model="form.wa_tpl_verified_registration"
                    rows="8"
                    class="form-control font-monospace fs-12"
                    placeholder="Tuliskan template notifikasi berkas terverifikasi..."
                  ></textarea>
                </div>

                <!-- Tab 4: Pembayaran Lunas -->
                <div v-show="activeTemplateTab === 'pay_verified'">
                  <textarea
                    v-model="form.wa_tpl_verified_payment"
                    rows="8"
                    class="form-control font-monospace fs-12"
                    placeholder="Tuliskan template notifikasi pelunasan biaya lomba..."
                  ></textarea>
                </div>

                <!-- Tab 5: Daftar via PJ / Koordinator -->
                <div v-show="activeTemplateTab === 'direct_reg'">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fs-12 text-muted">
                      Template pesan awal WhatsApp yang disiapkan ketika calon pendaftar memilih pendaftaran khusus <strong>Kontak Langsung (PJ / Koordinator Lomba)</strong>.
                    </span>
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-secondary rounded-pill fs-11 px-2.5 py-0.5 d-inline-flex align-items-center gap-1"
                      @click="resetToDefaultDirectTpl"
                      title="Pulihkan ke teks bawaan sistem"
                    >
                      <i class="ri-restart-line"></i> Reset ke Standar
                    </button>
                  </div>
                  <textarea
                    v-model="form.wa_tpl_direct_registration"
                    rows="9"
                    class="form-control font-monospace fs-12"
                    placeholder="Tuliskan template pesan WhatsApp untuk pendaftaran langsung via Koordinator Lomba..."
                  ></textarea>
                  <small class="fs-11 text-muted mt-1.5 d-block">
                    Format WhatsApp: <code>*tebal*</code>, <code>_miring_</code>, <code>~coret~</code>. Placeholder: <code>{nama_pj}</code>, <code>{cabang_lomba}</code>, <code>{nama_event}</code>, <code>{nama_sekolah}</code>, <code>{nama_pendaftar}</code>, <code>{nomor_wa}</code>, <code>{biaya}</code>, <code>{kontak_pj}</code>.
                  </small>
                </div>
              </div>
            </div>

            <!-- Card Footer Action Buttons -->
            <div class="card-footer bg-transparent p-3 d-flex justify-content-end border-top">
              <button
                type="submit"
                class="btn btn-sm btn-success rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-1.5"
                :disabled="form.processing"
              >
                <i class="ri-save-line" :class="{ 'ri-spin': form.processing }"></i>
                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Semua Template' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal View Message Details -->
    <div
      v-if="showMessageModal"
      class="modal-backdrop-custom d-flex align-items-center justify-content-center p-3"
      @click.self="showMessageModal = false"
    >
      <div class="modal-dialog-custom card border-0 shadow-lg rounded-4 overflow-hidden w-100" style="max-width: 580px;">
        <div class="card-header bg-success text-white py-3 px-4 d-flex align-items-center justify-content-between">
          <div class="fw-bold d-flex align-items-center gap-2">
            <i class="ri-whatsapp-line fs-18"></i>
            Detail Pesan WhatsApp Terkirim
          </div>
          <button type="button" class="btn-close btn-close-white" @click="showMessageModal = false"></button>
        </div>
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div>
              <div class="fs-11 text-muted">Penerima</div>
              <div class="fw-bold fs-14 text-dark-emphasis">{{ selectedLogMessage?.recipient_name }}</div>
              <div class="fs-12 font-monospace text-muted">{{ selectedLogMessage?.phone }}</div>
            </div>
            <div class="text-end">
              <div class="fs-11 text-muted">Waktu Kirim (24 Jam)</div>
              <div class="fw-semibold fs-12 font-monospace">{{ selectedLogMessage?.created_at }}</div>
              <span
                class="badge mt-1"
                :class="selectedLogMessage?.status === 'success' ? 'bg-success' : 'bg-danger'"
              >
                {{ selectedLogMessage?.status === 'success' ? 'Berhasil Terkirim' : 'Gagal' }}
              </span>
            </div>
          </div>

          <!-- Message Box WhatsApp-style -->
          <div class="p-3 rounded-3 bg-light border message-preview-box">
            <div class="fs-11 text-muted fw-bold mb-1.5 text-uppercase">Isi Pesan:</div>
            <pre class="mb-0 fs-12 font-sans-custom text-dark-emphasis whitespace-pre-wrap">{{ selectedLogMessage?.message }}</pre>
          </div>

          <!-- Error or response details if any -->
          <div v-if="selectedLogMessage?.error_message" class="mt-3 p-2.5 rounded-3 bg-danger-subtle text-danger border border-danger-subtle fs-11">
            <strong>Catatan Error:</strong> {{ selectedLogMessage?.error_message }}
          </div>
        </div>
        <div class="card-footer bg-transparent py-2.5 px-4 d-flex justify-content-end gap-2 border-top">
          <button type="button" class="btn btn-sm btn-secondary px-3 rounded-pill" @click="showMessageModal = false">
            Tutup
          </button>
          <button
            type="button"
            class="btn btn-sm btn-success px-3 rounded-pill d-flex align-items-center gap-1.5"
            @click="resendLog(selectedLogMessage?.id); showMessageModal = false;"
          >
            <i class="ri-restart-line"></i> Kirim Ulang Pesan Ini
          </button>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.wa-gateway-page {
  animation: fadeIn 0.25s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}

.online-indicator {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: #22c55e;
  border-radius: 50%;
  box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.35);
  animation: pulseGreen 2s infinite;
}

.offline-indicator {
  display: inline-block;
  width: 8px;
  height: 8px;
  background-color: #94a3b8;
  border-radius: 50%;
}

@keyframes pulseGreen {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.sticky-bottom-bar {
  position: sticky;
  bottom: 1rem;
  z-index: 10;
  backdrop-filter: blur(12px);
  background-color: rgba(var(--vz-card-bg-rgb, 255, 255, 255), 0.95) !important;
}

.modal-backdrop-custom {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(4px);
  z-index: 1055;
}

.message-preview-box {
  background: #f0fdf4 !important;
  border-color: #bbf7d0 !important;
}

[data-bs-theme="dark"] .message-preview-box {
  background: rgba(34, 197, 94, 0.1) !important;
  border-color: rgba(34, 197, 94, 0.25) !important;
}

.whitespace-pre-wrap {
  white-space: pre-wrap;
  word-break: break-word;
}

.font-sans-custom {
  font-family: inherit;
}
</style>
