<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

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
  notes: '',
  payment_method: 'Transfer Bank BSI',
});

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
  if (!comp) return 'https://wa.me/6283850070767';
  const statusInfo = getCompStatusInfo(comp);
  let msg = `Halo Admin Semarak, saya ingin menanyakan perihal cabang lomba ${comp.title}.`;
  if (statusInfo.isManuallyClosed) {
    msg = `Halo Admin Semarak, perihal pendaftaran cabang lomba ${comp.title} yang saat ini berstatus Ditutup Panitia, apakah masih berpeluang bagi sekolah kami untuk mendaftar?`;
  } else if (comp.is_quota_full) {
    msg = `Halo Admin Semarak, apakah delegasi kami bisa masuk waiting list untuk ${comp.title}?`;
  } else if (comp.is_deadline_passed) {
    msg = `Halo Admin Semarak, apakah pendaftaran ${comp.title} masih bisa menerima pendaftar susulan?`;
  }
  return `https://wa.me/6283850070767?text=${encodeURIComponent(msg)}`;
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
              <div class="form-group">
                <label class="form-label" for="reg-sekolah">Nama Asal Sekolah / Madrasah *</label>
                <input 
                  type="text" 
                  id="reg-sekolah" 
                  v-model="form.school_name"
                  class="form-control" 
                  placeholder="Contoh: SMPN 1 Balung / MTs Wahid Hasyim" 
                  required
                >
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

            <div class="form-group">
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

