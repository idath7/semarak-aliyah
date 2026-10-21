<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
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
  }
});

const isSuccess = ref(false);
const registeredData = ref(null);
const registerModalOpen = ref(false);

const form = useForm({
  package_type: 'Paket Satuan', // 'Paket Satuan' | 'Paket All-In'
  school_name: '',
  mentor_name: '',
  mentor_wa: '',
  competition_id: props.preselectedLomba || props.competitions?.[0]?.id || 'mipa',
  participants_names: '',
  custom_fields: {},
  notes: '',
  payment_method: 'Transfer Bank BSI',
});

// Dynamic Participant Repeater Logic
const activeComp = computed(() => props.competitions?.find(c => c.id === form.competition_id) || null);
const repeaterField = computed(() => activeComp.value?.custom_fields?.find(f => f.type === 'participant_repeater') || null);
const participantCount = ref(3);

function createEmptyParticipant() {
  return {
    name: '',
    gender: 'L',
    grade: 'Kelas 9 SMP/MTs',
    birth_date: '',
    nisn: '',
    custom_id: '',
    wa_number: '',
    is_captain: false,
    captain_wa: '',
    photo_formal_name: '',
    freestyle_count: 0,
  };
}

const participantsList = ref([
  createEmptyParticipant(),
  createEmptyParticipant(),
  createEmptyParticipant(),
]);

function setCaptain(idx) {
  participantsList.value.forEach((p, i) => {
    p.is_captain = (i === idx);
    if (!p.is_captain) {
      p.captain_wa = '';
    }
  });
}

function handleFreestyleFiles(idx, event) {
  const files = event.target.files;
  participantsList.value[idx].freestyle_count = files ? files.length : 0;
}

function syncParticipantsList() {
  const target = Math.max(1, participantCount.value);
  while (participantsList.value.length < target) {
    participantsList.value.push(createEmptyParticipant());
  }
  if (participantsList.value.length > target) {
    participantsList.value.splice(target);
  }
  if (repeaterField.value?.sub_fields?.captain_option?.active) {
    const hasCap = participantsList.value.some(p => p.is_captain);
    if (!hasCap && participantsList.value.length > 0) {
      participantsList.value[0].is_captain = true;
    }
  }
}

watch(repeaterField, (rf) => {
  if (rf) {
    const min = rf.min_items ? Number(rf.min_items) : 1;
    const max = rf.max_items ? Number(rf.max_items) : 50;
    participantCount.value = Math.max(min, Math.min(max, min));
    syncParticipantsList();
  }
}, { immediate: true });

function adjustCount(delta) {
  if (!repeaterField.value) return;
  const min = repeaterField.value.min_items ? Number(repeaterField.value.min_items) : 1;
  const max = repeaterField.value.max_items ? Number(repeaterField.value.max_items) : 50;
  participantCount.value = Math.max(min, Math.min(max, participantCount.value + delta));
  syncParticipantsList();
}

// Auto-open modal if lomba query param is present
onMounted(() => {
  if (props.preselectedLomba) {
    const matched = props.competitions?.find(c => c.id === props.preselectedLomba);
    if (matched) {
      form.competition_id = matched.id;
      registerModalOpen.value = true;
    }
  }
});

function openRegisterModal(compId = null, isAllIn = false) {
  if (isAllIn) {
    form.package_type = 'Paket All-In';
  } else {
    form.package_type = 'Paket Satuan';
    if (compId) {
      form.competition_id = compId;
    }
  }
  isSuccess.value = false;
  registerModalOpen.value = true;
}

function closeRegisterModal() {
  registerModalOpen.value = false;
  showSchoolDropdown.value = false;
}

// -----------------------------------------------------------------------------
// School Database Autocomplete (SMP / MTs Kabupaten Jember)
// -----------------------------------------------------------------------------
const schoolSuggestions = ref([]);
const isSearchingSchools = ref(false);
const showSchoolDropdown = ref(false);
let schoolSearchTimeout = null;

function onSchoolInput() {
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

function selectSchool(school) {
  form.school_name = school.name;
  showSchoolDropdown.value = false;
  schoolSuggestions.value = [];
}

// Dynamic Fee
const calculatedFee = computed(() => {
  if (form.package_type === 'Paket All-In') {
    return Number(props.pricingSettings?.allin_price || 200000);
  }
  const selected = props.competitions?.find(c => c.id === form.competition_id);
  return selected ? Number(selected.fee || 50000) : 50000;
});

const selectedCompFee = computed(() => {
  const selected = props.competitions?.find(c => c.id === form.competition_id);
  return selected ? Number(selected.fee || 50000) : 50000;
});

function handleSubmit() {
  if (repeaterField.value) {
    const list = participantsList.value.slice(0, participantCount.value);
    const lines = [];
    for (let i = 0; i < list.length; i++) {
      const p = list[i];
      const pName = (p.name || '').trim() || `Peserta ${i + 1}`;
      const details = [];
      if (p.is_captain) details.push('★ KAPTEN TIM');
      if (p.captain_wa) details.push(`WA Kapten: ${p.captain_wa}`);
      if (p.wa_number) details.push(`WA: ${p.wa_number}`);
      if (p.custom_id) details.push(`ID: ${p.custom_id}`);
      if (p.gender) details.push(p.gender === 'L' ? 'Laki-laki' : 'Perempuan');
      if (p.grade) details.push(p.grade);
      if (p.birth_date) details.push(p.birth_date);
      if (p.nisn) details.push(`NISN: ${p.nisn}`);
      if (p.photo_formal_name) details.push(`Pas Foto: ${p.photo_formal_name}`);
      if (p.freestyle_count) details.push(`Foto Bebas: ${p.freestyle_count} file`);
      lines.push(`${i + 1}. ${pName}${details.length ? ' (' + details.join(', ') + ')' : ''}`);
    }
    form.participants_names = lines.join('\n');
    form.custom_fields = {
      ...(form.custom_fields || {}),
      [repeaterField.value.name]: list,
      [repeaterField.value.name + '_count']: participantCount.value,
    };
  }

  form.post('/layanan-registrasi', {
    preserveScroll: true,
    onSuccess: (page) => {
      isSuccess.value = true;
      registeredData.value = page.props.flash?.registered;
    }
  });
}

// Helper to map competitions to clean, modern vector RemixIcons
function getCompIconClass(comp) {
  if (!comp) return 'ri-trophy-line';
  const id = String(comp.id || '').toLowerCase();
  const title = String(comp.title || '').toLowerCase();
  const category = String(comp.category || '').toLowerCase();

  if (id.includes('voli') || title.includes('voli') || title.includes('volley')) return 'ri-football-line';
  if (id.includes('ml') || id.includes('esport') || title.includes('mobile legends') || title.includes('e-sport')) return 'ri-gamepad-line';
  if (id.includes('mipa') || title.includes('mipa') || title.includes('sains')) return 'ri-microscope-line';
  if (id.includes('ips') || title.includes('ips') || title.includes('sosial')) return 'ri-earth-line';
  if (id.includes('story') || title.includes('story') || title.includes('pidato')) return 'ri-mic-line';
  if (id.includes('banjari') || title.includes('banjari') || title.includes('hadrah')) return 'ri-music-2-line';
  if (id.includes('singer') || title.includes('singer') || title.includes('nyanyi') || title.includes('lagu')) return 'ri-mv-line';
  if (id.includes('tahfidz') || title.includes('tahfidz') || title.includes('quran') || title.includes('mtq')) return 'ri-book-open-line';
  if (category === 'akademik') return 'ri-flask-line';
  if (category === 'olahraga') return 'ri-medal-line';
  if (category === 'seni') return 'ri-palette-line';
  return 'ri-trophy-line';
}

// Helper to determine accurate competition status and quota text
function getCompStatusInfo(comp) {
  if (!comp) {
    return {
      text: 'Ditutup',
      badgeClass: 'status-closed',
      iconClass: 'ri-close-circle-line',
      isClosed: true,
      isManuallyClosed: false,
    };
  }

  // 1. Jika masih dibuka normal
  if (!comp.is_registration_closed && comp.status === 'open') {
    return {
      text: 'Buka Pendaftaran',
      badgeClass: 'status-open',
      iconClass: 'ri-checkbox-circle-line',
      isClosed: false,
      isManuallyClosed: false,
    };
  }

  // 2. Jika Kuota Penuh (kapasitas maksimal telah tercapai)
  if (comp.is_quota_full || (comp.max_quota > 0 && comp.current_quota >= comp.max_quota)) {
    return {
      text: 'Kuota Penuh',
      badgeClass: 'status-closed',
      iconClass: 'ri-close-circle-line',
      isClosed: true,
      isManuallyClosed: false,
    };
  }

  // 3. Jika Melewati Batas Tanggal Penutupan Pendaftaran
  if (comp.is_deadline_passed) {
    return {
      text: 'Waktu Habis',
      badgeClass: 'status-closed',
      iconClass: 'ri-calendar-close-line',
      isClosed: true,
      isManuallyClosed: false,
    };
  }

  // 4. Jika Tanggal Masih Berlaku dan Kuota Masih Tersedia, TETAPI status ditutup secara manual oleh panitia/admin
  return {
    text: comp.quota_status_text || 'Ditutup Panitia',
    badgeClass: 'status-manual',
    iconClass: 'ri-lock-2-line',
    isClosed: true,
    isManuallyClosed: true,
  };
}

function getCompWaLink(comp) {
  if (!comp) return getWaChatUrl(school.value.contact_wa || '083850070767');
  const statusInfo = getCompStatusInfo(comp);
  let msg = `Halo Admin Semarak, saya ingin menanyakan perihal cabang lomba ${comp.title}.`;
  if (statusInfo.isManuallyClosed) {
    msg = `Halo Admin Semarak, perihal pendaftaran cabang lomba ${comp.title} yang saat ini berstatus Ditutup Panitia, apakah masih berpeluang bagi sekolah kami untuk mendaftar?`;
  } else if (comp.is_quota_full) {
    msg = `Halo Admin Semarak, apakah delegasi kami bisa masuk waiting list untuk ${comp.title}?`;
  } else if (comp.is_deadline_passed) {
    msg = `Halo Admin Semarak, apakah pendaftaran ${comp.title} masih bisa menerima pendaftar susulan?`;
  }
  return getWaChatUrl(school.value.contact_wa || '083850070767', msg);
}

function getCompDropdownSuffix(c) {
  const status = getCompStatusInfo(c);
  if (!status.isClosed) return '';
  if (status.isManuallyClosed) return ' — [DITUTUP PANITIA]';
  if (c.is_quota_full) return ' — [KUOTA PENUH]';
  if (c.is_deadline_passed) return ' — [WAKTU HABIS]';
  return ' — [TUTUP]';
}
</script>

<template>
  <PublicLayout>
    <Head :title="'Layanan Registrasi - ' + (school.event_name || 'SEMARAK ALIYAH XIV 2026')" />

    <!-- ==========================================================================
         PAGE BANNER
         ========================================================================== -->
    <section class="page-banner">
      <div class="container">
        <h1 class="page-title">
          Layanan Pendaftaran <span class="text-gradient-gold">{{ school.event_name || 'Semarak Aliyah XIV 2026' }}</span>
        </h1>
        <div class="page-breadcrumb">
          <Link href="/">Beranda</Link>
          <span>/</span>
          <span style="color: var(--accent-gold-light);">Layanan Registrasi</span>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         MAIN CONTENT HUB
         ========================================================================== -->
    <main class="section-padding">
      <div class="container">

        <!-- Registration Guidelines Box -->
        <div class="guide-box">
          <h2 class="guide-title">
            <i class="ri-file-list-3-line"></i>
            <span>Petunjuk & Kelengkapan Isian Formulir</span>
          </h2>
          <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 0.92rem;">
            Sebelum melakukan pendaftaran online, mohon siapkan data dan kelengkapan persyaratan berikut:
          </p>

          <div class="guide-list">
            <div class="guide-item">
              <i class="ri-checkbox-circle-line" style="color: var(--accent-gold); font-size: 16px; margin-top: 2px; flex-shrink: 0;"></i>
              <span>Nama Asal Sekolah / Madrasah SMP/MTs sederajat.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-line" style="color: var(--accent-gold); font-size: 16px; margin-top: 2px; flex-shrink: 0;"></i>
              <span>Nama Lengkap Guru Pembina / Pendamping Resmi.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-line" style="color: var(--accent-gold); font-size: 16px; margin-top: 2px; flex-shrink: 0;"></i>
              <span>Nomor WhatsApp Aktif Guru Pendamping (untuk koordinasi teknis).</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-line" style="color: var(--accent-gold); font-size: 16px; margin-top: 2px; flex-shrink: 0;"></i>
              <span>Daftar nama lengkap seluruh siswa delegasi beserta kelasnya.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-line" style="color: var(--accent-gold); font-size: 16px; margin-top: 2px; flex-shrink: 0;"></i>
              <span>Scan Kartu Pelajar / KTS atau Surat Delegasi Kepala Sekolah.</span>
            </div>
            <div class="guide-item">
              <i class="ri-checkbox-circle-line" style="color: var(--accent-gold); font-size: 16px; margin-top: 2px; flex-shrink: 0;"></i>
              <span>Pilihan Skema Biaya: Paket Fleksibel Satuan (Rp 50.000 / lomba) atau Paket All-In Komplit (Rp 200.000 / seluruh cabang lomba).</span>
            </div>
          </div>
        </div>

        <!-- Paket All In Promotion Banner (Skema Biaya Pendaftaran) -->
        <div class="reg-pricing-banner">
          <span class="pricing-ribbon-tag">
            <i class="ri-vip-crown-fill"></i> Paling Hemat • Rekomendasi
          </span>
          <h3 style="font-family: var(--font-heading); font-size: 1.4rem; font-weight: 800; margin: 10px 0 10px; color: var(--text-main);">
            Skema Biaya Pendaftaran Delegasi Sekolah
          </h3>
          <p style="color: var(--text-secondary); max-width: 680px; margin: 0 auto 20px; font-size: 0.92rem; line-height: 1.6;">
            Gunakan <strong>{{ pricingSettings?.allin_name || 'Paket All-In Komplit' }} (Rp {{ Number(pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }})</strong> untuk mendelegasikan kontingen di seluruh cabang lomba sekaligus, atau pilih <strong>Paket Fleksibel Satuan</strong> pada katalog di bawah ini.
          </p>
          <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <button 
              type="button" 
              class="pricing-action-btn btn-allin" 
              style="width: auto; padding: 12px 28px; font-size: 0.92rem;"
              @click="openRegisterModal(null, true)"
            >
              <i class="ri-flashlight-fill"></i>
              <span>Daftar Paket All-In (Seluruh Lomba) Sekarang</span>
            </button>
          </div>
        </div>

        <!-- Section Header -->
        <div class="section-header" style="margin-bottom: 35px;">
          <span class="section-tag">Pilih Pendaftaran Cabang</span>
          <h2 class="section-title">Portal Pendaftaran Per-Cabang Lomba</h2>
          <p class="section-desc">Klik tombol daftar pada cabang kompetisi pilihan Anda untuk membuka formulir pendaftaran.</p>
        </div>

        <!-- Registration Cards Grid for All Competitions -->
        <div class="reg-hub-grid">
          <div 
            v-for="comp in competitions" 
            :key="comp.id" 
            class="reg-card"
          >
            <div>
              <div class="reg-card-top">
                <div class="reg-card-icon">
                  <i :class="getCompIconClass(comp)"></i>
                </div>
                <div>
                  <h3 class="reg-card-title">{{ comp.title }}</h3>
                  <span 
                    :class="[
                      'comp-status-badge',
                      getCompStatusInfo(comp).badgeClass
                    ]"
                  >
                    <i :class="getCompStatusInfo(comp).iconClass"></i>
                    {{ getCompStatusInfo(comp).text }}
                  </span>
                </div>
              </div>

              <div class="reg-info-box">
                {{ comp.description }}
              </div>
            </div>

            <div>
              <div style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 12px; display: flex; justify-content: space-between;">
                <span>Biaya : <strong style="color: var(--theme-orange); font-weight: 700;">Rp {{ Number(comp.fee || 50000).toLocaleString('id-ID') }}</strong></span>
                <span>Terisi : <strong style="color: var(--text-main); font-weight: 700;">{{ comp.current_quota }} / {{ comp.max_quota }}</strong></span>
              </div>

              <button 
                v-if="!getCompStatusInfo(comp).isClosed"
                type="button" 
                class="btn-card-register" 
                style="width: 100%;"
                @click="openRegisterModal(comp.id)"
              >
                <i class="ri-flashlight-line"></i>
                <span>Daftar Sekarang</span>
              </button>
              <a 
                v-else
                :href="getCompWaLink(comp)" 
                target="_blank" 
                rel="noopener" 
                class="btn-card-detail" 
                style="text-align: center; display: block;"
                :title="getCompStatusInfo(comp).isManuallyClosed ? 'Pendaftaran ditutup oleh panitia. Klik untuk bertanya via WhatsApp' : 'Masuk daftar tunggu via WhatsApp'"
              >
                <i :class="getCompStatusInfo(comp).isManuallyClosed ? 'ri-whatsapp-line' : 'ri-time-line'"></i>
                <span>{{ getCompStatusInfo(comp).isManuallyClosed ? 'Hubungi Panitia (Ditutup)' : 'Hubungi Panitia (Waiting List)' }}</span>
              </a>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- ==========================================================================
         REGISTRATION MODAL FORM (INTERACTIVE POPUP)
         ========================================================================== -->
    <div 
      v-if="registerModalOpen" 
      class="modal-overlay" 
      style="display: flex;"
      role="dialog" 
      aria-modal="true" 
      aria-labelledby="reg-modal-title"
    >
      <div class="modal-card" style="max-width: 680px; max-height: 90vh; overflow-y: auto;">
        <button type="button" class="modal-close" @click="closeRegisterModal" aria-label="Tutup Modal">&times;</button>
        
        <!-- SUCCESS STATE INSIDE MODAL -->
        <div v-if="isSuccess" style="text-align: center; padding: 20px 10px;">
          <div style="font-size: 3rem; margin-bottom: 12px; color: #10b981;">
            <i class="ri-checkbox-circle-line"></i>
          </div>
          <h3 style="font-size: 1.35rem; color: var(--text-main); margin-bottom: 8px;">Pendaftaran Berhasil Dikirim!</h3>
          <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px; line-height: 1.6;">
            Terima kasih telah mendaftarkan kontingen di {{ school.event_name || 'Semarak Aliyah XIV 2026' }}. Data Anda telah masuk ke sistem panitia.
          </p>

          <div v-if="registeredData" style="background: var(--bg-surface-elevated); border: 1.5px solid var(--theme-purple-border); border-radius: var(--radius-md); padding: 18px; text-align: left; margin-bottom: 24px; font-size: 0.88rem; display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0, 0, 0, 0.06); padding-bottom: 8px;">
              <span style="color: var(--text-muted);">Kode Registrasi :</span>
              <span style="font-family: monospace; font-weight: 800; color: var(--theme-purple-hover); font-size: 1.05rem;">{{ registeredData.reg_code }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(0, 0, 0, 0.06); padding-bottom: 8px;">
              <span style="color: var(--text-muted);">Asal Sekolah :</span>
              <span style="color: var(--text-main); font-weight: 700;">{{ registeredData.school_name }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 8px;">
              <span style="color: var(--text-muted);">Cabang / Paket :</span>
              <span style="color: var(--accent-gold-light); font-weight: 700;">{{ registeredData.competition_title }} ({{ registeredData.package_type }})</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--text-muted);">Total Biaya :</span>
              <span style="font-family: monospace; font-weight: 800; color: #10b981; font-size: 1rem;">Rp {{ Number(registeredData.total_fee).toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <div style="display: flex; justify-content: center; gap: 12px;">
            <button type="button" class="btn-secondary" @click="closeRegisterModal">
              Selesai & Tutup
            </button>
            <button type="button" class="btn-primary" @click="isSuccess = false; form.reset()">
              Daftar Lomba Lain
            </button>
          </div>
        </div>

        <!-- FORM STATE -->
        <div v-else>
          <h3 id="reg-modal-title" style="font-size: 1.3rem; margin-bottom: 6px; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
            <i class="ri-edit-2-line" style="color: var(--theme-pink);"></i>
            <span>Formulir Registrasi {{ school.event_name || 'Semarak Aliyah 2026' }}</span>
          </h3>
          <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px;">
            Lengkapi data pendaftaran delegasi sekolah di bawah ini untuk diverifikasi oleh panitia.
          </p>

          <form @submit.prevent="handleSubmit">
            <!-- 1. Pilihan Paket -->
            <div class="form-group" style="margin-bottom: 18px;">
              <label class="form-label">Pilihan Paket Registrasi *</label>
              <div class="package-selector-grid">
                <label 
                  style="border: 1.5px solid var(--border-glass); border-radius: var(--radius-sm); padding: 12px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.2s;"
                  :style="{ background: form.package_type === 'Paket Satuan' ? 'var(--theme-purple-subtle)' : 'var(--bg-surface-elevated)', borderColor: form.package_type === 'Paket Satuan' ? 'var(--theme-purple)' : 'var(--border-glass)' }"
                >
                  <div>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">{{ pricingSettings?.satuan_name || 'Paket Satuan' }}</div>
                    <div style="font-size: 0.8rem; color: var(--theme-green-hover); font-family: monospace; font-weight: 600;">Rp {{ selectedCompFee.toLocaleString('id-ID') }} / Lomba</div>
                  </div>
                  <input type="radio" value="Paket Satuan" v-model="form.package_type">
                </label>

                <label 
                  style="border: 1.5px solid var(--border-glass); border-radius: var(--radius-sm); padding: 12px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: 0.2s;"
                  :style="{ background: form.package_type === 'Paket All-In' ? 'var(--theme-pink-subtle)' : 'var(--bg-surface-elevated)', borderColor: form.package_type === 'Paket All-In' ? 'var(--theme-pink)' : 'var(--border-glass)' }"
                >
                  <div>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">{{ pricingSettings?.allin_name || 'Paket All-In Komplit' }}</div>
                    <div style="font-size: 0.8rem; color: var(--theme-orange-hover); font-family: monospace; font-weight: 600;">Rp {{ Number(pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }} (Semua Lomba)</div>
                  </div>
                  <input type="radio" value="Paket All-In" v-model="form.package_type">
                </label>
              </div>
            </div>

            <!-- 2. Data Sekolah & Pendamping -->
            <div class="form-grid-2">
              <div class="form-group" style="position: relative;">
                <label class="form-label" for="reg-sekolah" style="display: flex; align-items: center; justify-content: space-between;">
                  <span>Nama Asal Sekolah / Madrasah *</span>
                  <span v-if="isSearchingSchools" style="font-size: 0.72rem; color: #10b981; font-weight: normal;">
                    <i class="ri-loader-4-line ri-spin"></i> Mencari database...
                  </span>
                </label>
                <input 
                  type="text" 
                  id="reg-sekolah" 
                  v-model="form.school_name"
                  class="form-control" 
                  placeholder="Ketik nama SMP/MTs (Contoh: SMPN 1 Balung / MTs Wahid Hasyim)" 
                  required
                  autocomplete="off"
                  @input="onSchoolInput"
                  @focus="onSchoolInput"
                >

                <!-- Autocomplete Dropdown List -->
                <div 
                  v-if="showSchoolDropdown && schoolSuggestions.length > 0" 
                  class="school-autocomplete-dropdown"
                  style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050; background: #0b1120; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.7); max-height: 220px; overflow-y: auto;"
                >
                  <div 
                    v-for="s in schoolSuggestions" 
                    :key="s.id"
                    @click="selectSchool(s)"
                    style="padding: 9px 12px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: space-between; gap: 8px; transition: background 0.15s;"
                    onmouseover="this.style.background='rgba(16, 185, 129, 0.18)'"
                    onmouseout="this.style.background='transparent'"
                  >
                    <div>
                      <div style="font-weight: 600; font-size: 0.85rem; color: #f8fafc;">{{ s.name }}</div>
                      <div style="font-size: 0.72rem; color: #94a3b8;">
                        <span>Kecamatan {{ s.kecamatan }}</span>
                        <span v-if="s.npsn && s.npsn !== '-'"> • NPSN: {{ s.npsn }}</span>
                      </div>
                    </div>
                    <span 
                      style="font-size: 0.68rem; padding: 2px 7px; border-radius: 4px; font-weight: 700; white-space: nowrap;"
                      :style="s.jenjang === 'MTs' ? 'background: rgba(16,185,129,0.25); color: #34d399; border: 1px solid rgba(16,185,129,0.4);' : 'background: rgba(59,130,246,0.25); color: #60a5fa; border: 1px solid rgba(59,130,246,0.4);'"
                    >
                      {{ s.jenjang }} {{ s.status }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="reg-pendamping">Nama Guru Pendamping *</label>
                <input 
                  type="text" 
                  id="reg-pendamping" 
                  v-model="form.mentor_name"
                  class="form-control" 
                  placeholder="Nama Guru Pembina / Pendamping" 
                  required
                >
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label" for="reg-wa">Nomor WhatsApp Guru Pendamping *</label>
                <input 
                  type="tel" 
                  id="reg-wa" 
                  v-model="form.mentor_wa"
                  class="form-control" 
                  placeholder="Contoh: 081234567890" 
                  required
                >
              </div>

              <div class="form-group">
                <label class="form-label" for="reg-competition">
                  {{ form.package_type === 'Paket Satuan' ? 'Pilih Cabang Lomba *' : 'Cabang Pilihan Utama' }}
                </label>
                <select 
                  id="reg-competition" 
                  v-model="form.competition_id"
                  class="form-control" 
                  :disabled="form.package_type === 'Paket All-In'"
                >
                  <option 
                    v-for="c in competitions" 
                    :key="c.id" 
                    :value="c.id"
                    :disabled="getCompStatusInfo(c).isClosed"
                  >
                    {{ c.title }} (Rp {{ Number(c.fee || 50000).toLocaleString('id-ID') }}){{ getCompDropdownSuffix(c) }}
                  </option>
                </select>
              </div>
            </div>

            <!-- DYNAMIC PARTICIPANT REPEATER / ROSTER -->
            <div v-if="repeaterField" class="form-group" style="margin-bottom: 20px;">
              <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 12px; padding: 14px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                  <div style="font-weight: 700; font-size: 0.88rem; color: #f8fafc; display: flex; align-items: center; gap: 6px;">
                    <i class="ri-group-line" style="color: #10b981;"></i>
                    <span>{{ repeaterField.label || 'Jumlah Peserta yang Didaftarkan' }} *</span>
                  </div>
                  <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">
                    <span v-if="repeaterField.max_items">Batas maksimal kuota: <strong>{{ repeaterField.max_items }} Siswa/Tim</strong></span>
                    <span v-else>Kuota peserta: <strong>Bebas/Fleksibel</strong></span>
                    <span v-if="repeaterField.min_items && repeaterField.min_items > 1"> (Minimal {{ repeaterField.min_items }} Siswa)</span>
                  </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                  <button 
                    type="button" 
                    style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); color: #fff; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                    :disabled="participantCount <= (repeaterField.min_items || 1)"
                    @click="adjustCount(-1)"
                  >
                    <i class="ri-subtract-line"></i>
                  </button>
                  <span style="font-family: monospace; font-weight: 800; font-size: 0.95rem; color: #10b981; padding: 4px 12px; background: #060911; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    {{ participantCount }} Siswa
                  </span>
                  <button 
                    type="button" 
                    style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.15); color: #fff; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                    :disabled="repeaterField.max_items && participantCount >= Number(repeaterField.max_items)"
                    @click="adjustCount(1)"
                  >
                    <i class="ri-add-line"></i>
                  </button>
                </div>
              </div>

              <!-- Student Cards -->
              <div style="display: flex; flex-direction: column; gap: 10px;">
                <div 
                  v-for="pIdx in participantCount" 
                  :key="pIdx"
                  style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 12px;"
                >
                  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid rgba(255, 255, 255, 0.06);">
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                      <span style="font-weight: 700; font-size: 0.8rem; color: #f59e0b; display: flex; align-items: center; gap: 6px;">
                        <i class="ri-user-line" style="color: #10b981;"></i>
                        <span>Biodata Siswa Delegasi #{{ pIdx }}</span>
                      </span>
                      <span v-if="participantsList[pIdx - 1].is_captain" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); font-size: 0.68rem; font-weight: 800; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">
                        <i class="ri-vip-crown-fill" style="color: #f59e0b;"></i> KAPTEN TIM
                      </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px;">
                      <label 
                        v-if="repeaterField.sub_fields?.captain_option?.active" 
                        style="display: flex; align-items: center; gap: 4px; font-size: 0.72rem; cursor: pointer; padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(245, 158, 11, 0.4); transition: all 0.15s;"
                        :style="{ background: participantsList[pIdx - 1].is_captain ? 'rgba(245, 158, 11, 0.25)' : 'transparent', color: participantsList[pIdx - 1].is_captain ? '#fbbf24' : '#94a3b8' }"
                        title="Tandai siswa ini sebagai kapten tim"
                      >
                        <input type="radio" name="reg_captain_sel" :checked="participantsList[pIdx - 1].is_captain" @change="setCaptain(pIdx - 1)" />
                        <span>⭐ Kapten</span>
                      </label>
                      <span style="font-size: 0.7rem; color: #64748b; font-family: monospace;">
                        Peserta {{ pIdx }} dari {{ participantCount }}
                      </span>
                    </div>
                  </div>

                  <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                    <!-- Nama Lengkap -->
                    <div>
                      <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: block;">Nama Lengkap Siswa *</label>
                      <input 
                        type="text" 
                        v-model="participantsList[pIdx - 1].name"
                        class="form-control" 
                        style="padding: 7px 10px; font-size: 0.82rem;"
                        :placeholder="`Nama Lengkap Peserta ${pIdx}`"
                        required
                      />
                    </div>

                    <div class="grid-2-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                      <!-- Jenis Kelamin -->
                      <div v-if="repeaterField.sub_fields?.gender?.active !== false">
                        <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: block;">Jenis Kelamin</label>
                        <div style="display: flex; gap: 6px;">
                          <label style="flex: 1; border: 1px solid rgba(255,255,255,0.1); padding: 5px 8px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 0.76rem;" :style="{ background: participantsList[pIdx - 1].gender === 'L' ? 'rgba(16, 185, 129, 0.2)' : 'transparent' }">
                            <input type="radio" :name="`reg_gender_${pIdx}`" value="L" v-model="participantsList[pIdx - 1].gender">
                            <span>Laki-laki</span>
                          </label>
                          <label style="flex: 1; border: 1px solid rgba(255,255,255,0.1); padding: 5px 8px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 0.76rem;" :style="{ background: participantsList[pIdx - 1].gender === 'P' ? 'rgba(16, 185, 129, 0.2)' : 'transparent' }">
                            <input type="radio" :name="`reg_gender_${pIdx}`" value="P" v-model="participantsList[pIdx - 1].gender">
                            <span>Perempuan</span>
                          </label>
                        </div>
                      </div>

                      <!-- Kelas / Tingkat -->
                      <div v-if="repeaterField.sub_fields?.grade?.active !== false">
                        <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: block;">Kelas / Tingkat</label>
                        <select v-model="participantsList[pIdx - 1].grade" class="form-control" style="padding: 7px 10px; font-size: 0.82rem;">
                          <option value="Kelas 7 SMP/MTs">Kelas 7 SMP/MTs</option>
                          <option value="Kelas 8 SMP/MTs">Kelas 8 SMP/MTs</option>
                          <option value="Kelas 9 SMP/MTs">Kelas 9 SMP/MTs</option>
                        </select>
                      </div>
                    </div>

                    <div class="grid-2-cols" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                      <!-- Tanggal Lahir -->
                      <div v-if="repeaterField.sub_fields?.birth_date?.active !== false">
                        <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: block;">Tanggal Lahir (Opsional)</label>
                        <input 
                          type="date" 
                          v-model="participantsList[pIdx - 1].birth_date"
                          class="form-control" 
                          style="padding: 7px 10px; font-size: 0.82rem;"
                        />
                      </div>
                      <!-- NISN -->
                      <div v-if="repeaterField.sub_fields?.nisn?.active !== false">
                        <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: block;">NISN (Opsional)</label>
                        <input 
                          type="text" 
                          v-model="participantsList[pIdx - 1].nisn"
                          class="form-control" 
                          style="padding: 7px 10px; font-size: 0.82rem;"
                          placeholder="Nomor Induk Siswa"
                        />
                      </div>
                    </div>

                    <!-- ID Siswa (Field Text) -->
                    <div v-if="repeaterField.sub_fields?.custom_id?.active">
                      <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: flex; align-items: center; justify-content: space-between;">
                        <span>{{ repeaterField.sub_fields.custom_id.label || 'ID Siswa / No. Identitas' }}</span>
                        <span style="font-size: 0.68rem; color: #64748b; font-family: monospace;">Field Text</span>
                      </label>
                      <input 
                        type="text" 
                        v-model="participantsList[pIdx - 1].custom_id"
                        class="form-control" 
                        style="padding: 7px 10px; font-size: 0.82rem; font-family: monospace;"
                        placeholder="Contoh: ID-0912 / User ID Game / No. Punggung"
                      />
                    </div>

                    <!-- Nomor WhatsApp Siswa (Field Angka) -->
                    <div v-if="repeaterField.sub_fields?.wa_number?.active">
                      <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: flex; align-items: center; gap: 4px;">
                        <i class="ri-whatsapp-line" style="color: #10b981;"></i>
                        <span>Nomor WhatsApp Siswa</span>
                        <span style="font-size: 0.68rem; color: #10b981; font-family: monospace;">(Field Angka)</span>
                      </label>
                      <input 
                        type="tel" 
                        v-model="participantsList[pIdx - 1].wa_number"
                        class="form-control" 
                        style="padding: 7px 10px; font-size: 0.82rem; font-family: monospace;"
                        placeholder="Contoh: 081234567890"
                      />
                    </div>

                    <!-- Nomor WA Khusus Kapten Tim (Jika siswa ini ditandai sebagai kapten) -->
                    <div v-if="repeaterField.sub_fields?.captain_option?.active && participantsList[pIdx - 1].is_captain" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 8px; padding: 10px;">
                      <label style="font-size: 0.75rem; color: #fbbf24; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                        <i class="ri-whatsapp-fill" style="color: #10b981;"></i>
                        <span>Nomor WhatsApp Khusus Kapten Tim (Field Angka) *</span>
                      </label>
                      <input 
                        type="tel" 
                        v-model="participantsList[pIdx - 1].captain_wa"
                        class="form-control" 
                        style="padding: 7px 10px; font-size: 0.82rem; font-family: monospace; border-color: rgba(245, 158, 11, 0.5);"
                        placeholder="Contoh: 081234567890 (WA Kapten untuk koordinasi)"
                        required
                      />
                      <div style="font-size: 0.68rem; color: #cbd5e1; margin-top: 3px;">
                        Nomor WA ini akan digunakan panitia lomba untuk koordinasi TM & grup WhatsApp resmi kapten.
                      </div>
                    </div>

                    <!-- Upload Foto Siswa Formal (Pas Foto 1 File) -->
                    <div v-if="repeaterField.sub_fields?.photo_formal?.active">
                      <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 4px;">
                          <i class="ri-camera-line" style="color: #38bdf8;"></i> Upload Pas Foto Formal Siswa
                        </span>
                        <span style="font-size: 0.68rem; color: #38bdf8; background: rgba(56, 189, 248, 0.15); padding: 1px 6px; border-radius: 4px;">Formal (1 File)</span>
                      </label>
                      <input 
                        type="file" 
                        accept="image/*"
                        class="form-control" 
                        style="padding: 5px 8px; font-size: 0.78rem;"
                        @change="e => participantsList[pIdx - 1].photo_formal_name = e.target.files[0]?.name || ''"
                      />
                      <div v-if="participantsList[pIdx - 1].photo_formal_name" style="font-size: 0.7rem; color: #38bdf8; margin-top: 2px;">
                        ✓ File terpilih: {{ participantsList[pIdx - 1].photo_formal_name }}
                      </div>
                    </div>

                    <!-- Upload Foto Siswa Pose Gaya Bebas (Bisa >1 Foto) -->
                    <div v-if="repeaterField.sub_fields?.photo_freestyle?.active">
                      <label style="font-size: 0.75rem; color: #94a3b8; margin-bottom: 3px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="display: flex; align-items: center; gap: 4px;">
                          <i class="ri-gallery-upload-line" style="color: #34d399;"></i> Upload Foto Pose Gaya Bebas
                        </span>
                        <span style="font-size: 0.68rem; color: #34d399; background: rgba(52, 211, 153, 0.15); padding: 1px 6px; border-radius: 4px;">Bisa >1 Foto</span>
                      </label>
                      <input 
                        type="file" 
                        multiple
                        accept="image/*"
                        class="form-control" 
                        style="padding: 5px 8px; font-size: 0.78rem;"
                        @change="e => handleFreestyleFiles(pIdx - 1, e)"
                      />
                      <div v-if="participantsList[pIdx - 1].freestyle_count" style="font-size: 0.7rem; color: #34d399; margin-top: 2px;">
                        ✓ {{ participantsList[pIdx - 1].freestyle_count }} file foto pose gaya bebas dipilih
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- STANDARD PARTICIPANTS TEXTAREA (FALLBACK IF NO REPEATER) -->
            <div v-else class="form-group">
              <label class="form-label" for="reg-peserta">Nama Lengkap Peserta / Anggota Tim *</label>
              <textarea 
                id="reg-peserta" 
                v-model="form.participants_names"
                class="form-control" 
                rows="3" 
                placeholder="Contoh :&#10;1. Ahmad Fauzi (Kelas 9A)&#10;2. Siti Nurhaliza (Kelas 8B)" 
                required
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label" for="reg-catatan">Catatan / Keterangan Tambahan (Opsional)</label>
              <input 
                type="text" 
                id="reg-catatan" 
                v-model="form.notes"
                class="form-control" 
                placeholder="Keterangan tambahan jika ada..."
              >
            </div>

            <!-- Total Biaya & Transfer Rekening -->
            <div style="background: rgba(6, 9, 17, 0.7); border: 1px solid var(--border-glass); border-radius: var(--radius-sm); padding: 14px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
              <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">Total Biaya Registrasi :</div>
                <div style="font-family: monospace; font-size: 1.35rem; font-weight: 800; color: #10b981;">
                  Rp {{ calculatedFee.toLocaleString('id-ID') }}
                </div>
              </div>
              <div style="text-align: right; font-size: 0.82rem; color: var(--text-secondary);">
                <div>Transfer ke <strong>{{ pricingSettings?.bank_name || 'Bank BSI' }}</strong></div>
                <div style="color: var(--accent-gold); font-family: monospace; font-weight: 700;">No. Rek : {{ pricingSettings?.account_number || '7123456789' }} a.n. {{ pricingSettings?.account_holder || (school.event_name || 'Semarak Aliyah') }}</div>
              </div>
            </div>

            <button 
              type="submit" 
              class="btn-primary" 
              style="width: 100%; padding: 10px 18px; font-size: 0.88rem; gap: 6px;"
              :disabled="form.processing"
            >
              <i class="ri-send-plane-fill"></i>
              <span>{{ form.processing ? 'Mengirim Pendaftaran...' : 'Kirim Pendaftaran Delegasi' }}</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

