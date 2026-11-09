<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import CustomSelect from '@/Components/CustomSelect.vue';
import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import { getWaChatUrl } from '@/Utils/whatsapp';

const props = defineProps({
  participants: { type: Array, default: () => [] },
  competitions: { type: Array, default: () => [] },
  schools_list: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
});

const toast = useToast();
const { can } = usePermission();

// -----------------------------------------------------------------------------
// FILTER & SEARCH STATE
// -----------------------------------------------------------------------------
const searchQuery = ref(props.filters.search || '');
const competitionFilter = ref(props.filters.competition_id || 'all');
const typeFilter = ref(props.filters.participant_type || 'all');
const roleFilter = ref(props.filters.team_role || 'all');
const paymentFilter = ref(props.filters.payment_status || 'all');
const checkinFilter = ref(props.filters.checkin_status || 'all');
const schoolFilter = ref(props.filters.school_name || 'all');

// Pagination State
const currentPage = ref(1);
const perPage = ref(25);

const perPageOptions = [
  { value: 10, label: '10 baris' },
  { value: 25, label: '25 baris' },
  { value: 50, label: '50 baris' },
  { value: 100, label: '100 baris' },
];

// Options for CustomSelect filters
const competitionFilterOptions = computed(() => {
  const opts = [{ value: 'all', label: 'Semua Cabang Lomba' }];
  props.competitions.forEach(c => {
    const pType = (c.participant_type === 'tim' || c.participant_type === 'beregu') ? 'Tim' : 'Individu';
    opts.push({ 
      value: String(c.id), 
      label: `${c.title} (${pType})` 
    });
  });
  return opts;
});

const typeFilterOptions = [
  { value: 'all', label: 'Semua Jenis (Individu & Tim)' },
  { value: 'individu', label: 'Lomba Individu' },
  { value: 'tim', label: 'Lomba Tim / Regu' },
];

const roleFilterOptions = [
  { value: 'all', label: 'Semua Peran Siswa' },
  { value: 'kapten', label: 'Kapten / Ketua Regu' },
  { value: 'anggota', label: 'Anggota Regu Saja' },
  { value: 'tunggal', label: 'Peserta Tunggal (Individu)' },
];

const paymentFilterOptions = [
  { value: 'all', label: 'Semua Status Bayar' },
  { value: 'Terverifikasi', label: 'Terverifikasi (Lunas)' },
  { value: 'Menunggu Verifikasi', label: 'Menunggu Verifikasi' },
  { value: 'Belum Lunas', label: 'Belum Lunas / Ditolak' },
];

const checkinFilterOptions = [
  { value: 'all', label: 'Semua Presensi' },
  { value: 'hadir', label: 'Sudah Hadir / Check-in' },
  { value: 'belum', label: 'Belum Hadir' },
];

const schoolFilterOptions = computed(() => {
  const opts = [{ value: 'all', label: 'Semua Asal Sekolah' }];
  props.schools_list.forEach(sch => {
    opts.push({ value: sch, label: sch });
  });
  return opts;
});

// -----------------------------------------------------------------------------
// FILTERED PARTICIPANTS
// -----------------------------------------------------------------------------
const filteredParticipants = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();

  return props.participants.filter(item => {
    // 1. Text Search: Name, NISN, School, Reg Code
    if (q) {
      const matchName = item.name?.toLowerCase().includes(q);
      const matchNisn = item.nisn?.toLowerCase().includes(q);
      const matchSchool = item.school_name?.toLowerCase().includes(q);
      const matchCode = item.reg_code?.toLowerCase().includes(q);
      const matchComp = item.competition_title?.toLowerCase().includes(q);
      if (!matchName && !matchNisn && !matchSchool && !matchCode && !matchComp) {
        return false;
      }
    }

    // 2. Filter Cabang Lomba
    if (competitionFilter.value !== 'all') {
      if (String(item.competition_id) !== String(competitionFilter.value)) {
        return false;
      }
    }

    // 3. Filter Jenis Kepesertaan (Individu vs Tim)
    if (typeFilter.value !== 'all') {
      if (item.participant_type !== typeFilter.value) {
        return false;
      }
    }

    // 4. Filter Peran Tim
    if (roleFilter.value !== 'all') {
      if (roleFilter.value === 'kapten' && !item.is_captain) return false;
      if (roleFilter.value === 'anggota' && (item.is_captain || item.participant_type !== 'tim')) return false;
      if (roleFilter.value === 'tunggal' && item.participant_type !== 'individu') return false;
    }

    // 5. Filter Status Bayar
    if (paymentFilter.value !== 'all') {
      if (paymentFilter.value === 'Terverifikasi' && item.payment_status !== 'Terverifikasi') return false;
      if (paymentFilter.value === 'Menunggu Verifikasi' && item.payment_status !== 'Menunggu Verifikasi') return false;
      if (paymentFilter.value === 'Belum Lunas' && (item.payment_status === 'Terverifikasi' || item.payment_status === 'Menunggu Verifikasi')) return false;
    }

    // 6. Filter Presensi Gate
    if (checkinFilter.value !== 'all') {
      const isCheckedIn = item.checkin_status && item.checkin_status.toLowerCase().includes('sudah');
      if (checkinFilter.value === 'hadir' && !isCheckedIn) return false;
      if (checkinFilter.value === 'belum' && isCheckedIn) return false;
    }

    // 7. Filter Sekolah
    if (schoolFilter.value !== 'all') {
      if (item.school_name !== schoolFilter.value) {
        return false;
      }
    }

    return true;
  });
});

// Dynamic counts based on filtered data
const filteredCount = computed(() => filteredParticipants.value.length);
const totalPages = computed(() => Math.ceil(filteredCount.value / perPage.value) || 1);

// Paginated view
const paginatedParticipants = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredParticipants.value.slice(start, start + perPage.value);
});

function resetFilters() {
  searchQuery.value = '';
  competitionFilter.value = 'all';
  typeFilter.value = 'all';
  roleFilter.value = 'all';
  paymentFilter.value = 'all';
  checkinFilter.value = 'all';
  schoolFilter.value = 'all';
  currentPage.value = 1;
}

function goToPage(p) {
  if (p >= 1 && p <= totalPages.value) {
    currentPage.value = p;
  }
}

// -----------------------------------------------------------------------------
// DETAIL MODAL STATE
// -----------------------------------------------------------------------------
const showDetailModal = ref(false);
const activeStudent = ref(null);

function openStudentDetail(student) {
  activeStudent.value = student;
  showDetailModal.value = true;
}

function closeStudentDetail() {
  showDetailModal.value = false;
  activeStudent.value = null;
}

// -----------------------------------------------------------------------------
// PRINT & EXPORT HELPERS
// -----------------------------------------------------------------------------
function triggerPrint() {
  window.print();
}

function copyToClipboard(text, label = 'Teks') {
  if (!text || text === '-') return;
  navigator.clipboard.writeText(text);
  toast.success(`${label} berhasil disalin!`);
}

function getInitials(name) {
  if (!name) return 'PS';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[1][0]).toUpperCase();
}
</script>

<template>
  <VelzonLayout title="Rincian Peserta (Siswa)">
    <Head title="Rincian Peserta (Siswa) - Semarak Aliyah XIV" />

    <!-- ============================================================== -->
    <!-- HEADER & ACTION BUTTONS                                        -->
    <!-- ============================================================== -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4 d-print-none">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 fs-11">
            <i class="ri-user-star-line me-1"></i> Data Granular Siswa
          </span>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fs-11">
            Individu & Tim / Regu
          </span>
        </div>
        <h4 class="mb-0 fw-bold text-dark fs-20">Rincian Peserta (Siswa)</h4>
        <p class="text-muted fs-12 mb-0">
          Daftar seluruh siswa/peserta yang terdaftar di Semarak Aliyah XIV dengan rincian peran, identitas, dan status.
        </p>
      </div>

      <div class="d-flex align-items-center flex-wrap gap-2">
        <button 
          type="button" 
          @click="triggerPrint" 
          class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5 shadow-sm fs-12"
          title="Cetak daftar siswa peserta"
        >
          <i class="ri-printer-line"></i> Cetak Laporan
        </button>
        <a 
          href="/admin/peserta/export-csv" 
          class="btn btn-sm btn-success d-inline-flex align-items-center gap-1.5 shadow-sm fs-12"
          title="Download rekapitulasi data siswa dalam format CSV/Excel"
        >
          <i class="ri-file-excel-2-line"></i> Ekspor CSV (.csv)
        </a>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- KPI STAT CARDS (5 Cards Responsif)                             -->
    <!-- ============================================================== -->
    <div class="row g-2 g-sm-3 mb-4 d-print-none">
      <!-- Card 1: Total Siswa -->
      <div class="col-6 col-md-4 col-xl">
        <div class="card card-animate border-0 shadow-sm h-100 mb-0">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1 text-truncate">Total Siswa</p>
                <h4 class="fs-20 fw-bold mb-0 text-dark">
                  {{ stats.total_students || participants.length }}
                  <span class="fs-11 text-muted fw-normal">Orang</span>
                </h4>
                <span class="badge bg-primary-subtle text-primary fs-10 mt-1.5 text-truncate d-inline-block">
                  <i class="ri-user-star-line align-middle me-0.5"></i> Seluruh Delegasi
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0 ms-2">
                <span class="avatar-title bg-primary-subtle text-primary rounded fs-20">
                  <i class="ri-team-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 2: Lomba Individu -->
      <div class="col-6 col-md-4 col-xl">
        <div class="card card-animate border-0 shadow-sm h-100 mb-0">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1 text-truncate">Lomba Individu</p>
                <h4 class="fs-20 fw-bold mb-0 text-info">
                  {{ stats.total_individual || 0 }}
                  <span class="fs-11 text-muted fw-normal">Siswa</span>
                </h4>
                <span class="badge bg-info-subtle text-info fs-10 mt-1.5 text-truncate d-inline-block">
                  <i class="ri-user-line align-middle me-0.5"></i> Peserta Tunggal
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0 ms-2">
                <span class="avatar-title bg-info-subtle text-info rounded fs-20">
                  <i class="ri-user-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 3: Lomba Tim / Regu -->
      <div class="col-6 col-md-4 col-xl">
        <div class="card card-animate border-0 shadow-sm h-100 mb-0">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1 text-truncate">Lomba Tim / Regu</p>
                <h4 class="fs-20 fw-bold mb-0 text-purple" style="color: #8b5cf6;">
                  {{ stats.total_team || 0 }}
                  <span class="fs-11 text-muted fw-normal">Siswa</span>
                </h4>
                <span class="badge bg-purple-subtle text-purple fs-10 mt-1.5 text-truncate d-inline-block" style="background-color: rgba(139, 92, 246, 0.1); color: #7c3aed;">
                  <i class="ri-group-line align-middle me-0.5"></i> Tim & Anggota
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0 ms-2">
                <span class="avatar-title rounded fs-20" style="background-color: rgba(139, 92, 246, 0.15); color: #7c3aed;">
                  <i class="ri-vip-crown-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 4: Total Sekolah -->
      <div class="col-6 col-md-6 col-xl">
        <div class="card card-animate border-0 shadow-sm h-100 mb-0">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1 text-truncate">Sekolah Pengirim</p>
                <h4 class="fs-20 fw-bold mb-0 text-success">
                  {{ stats.total_schools || schools_list.length }}
                  <span class="fs-11 text-muted fw-normal">Kontingen</span>
                </h4>
                <span class="badge bg-success-subtle text-success fs-10 mt-1.5 text-truncate d-inline-block">
                  <i class="ri-community-line align-middle me-0.5"></i> SMP / MTs
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0 ms-2">
                <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                  <i class="ri-building-4-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Card 5: Terverifikasi Sah -->
      <div class="col-12 col-md-6 col-xl">
        <div class="card card-animate border-0 shadow-sm h-100 mb-0">
          <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between">
              <div class="min-w-0">
                <p class="text-uppercase fw-semibold text-muted fs-11 mb-1 text-truncate">Siswa Terverifikasi</p>
                <h4 class="fs-20 fw-bold mb-0 text-emerald" style="color: #059669;">
                  {{ stats.total_verified || 0 }}
                  <span class="fs-11 text-muted fw-normal">Siswa</span>
                </h4>
                <span class="badge bg-success-subtle text-success fs-10 mt-1.5 text-truncate d-inline-block">
                  <i class="ri-shield-check-line align-middle me-0.5"></i> Sah Berlomba
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0 ms-2">
                <span class="avatar-title bg-success-subtle text-success rounded fs-20">
                  <i class="ri-checkbox-circle-line"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- PRINT HEADER (Hanya Muncul Saat Print)                         -->
    <!-- ============================================================== -->
    <div class="d-none d-print-block mb-4 text-center border-bottom pb-3">
      <h3 class="fw-bold mb-1">DAFTAR RINCIAN PESERTA (SISWA)</h3>
      <h5 class="fw-semibold text-muted mb-1">SEMARAK ALIYAH XIV - MAS WAHID HASYIM BANGSRI</h5>
      <p class="fs-12 text-muted mb-0">
        Dicetak pada: {{ new Date().toLocaleString('id-ID') }} | Total Peserta: {{ filteredParticipants.length }} Siswa
      </p>
    </div>

    <!-- ============================================================== -->
    <!-- MAIN CARD: FILTER TOOLBAR & PARTICIPANTS TABLE                 -->
    <!-- ============================================================== -->
    <div class="card border-0 shadow-sm">
      <!-- Card Header: Search & Multi-Filters -->
      <div class="card-header bg-white border-bottom py-3 d-print-none">
        <div class="row g-2 align-items-center">
          <!-- Search Box -->
          <div class="col-12 col-md-4 col-xl-3">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i class="ri-search-line"></i>
              </span>
              <input 
                v-model="searchQuery" 
                type="text" 
                class="form-control bg-light border-start-0 fs-12 text-dark" 
                placeholder="Cari nama, NISN, sekolah, kode..." 
              />
              <button 
                v-if="searchQuery" 
                @click="searchQuery = ''" 
                class="btn btn-light border border-start-0 text-muted" 
                type="button"
              >
                <i class="ri-close-line"></i>
              </button>
            </div>
          </div>

          <!-- Filter Cabang Lomba (CustomSelect with search) -->
          <div class="col-6 col-md-4 col-xl-3">
            <CustomSelect
              v-model="competitionFilter"
              :options="competitionFilterOptions"
              size="sm"
              placeholder="Pilih Cabang Lomba..."
              prefixIcon="ri-medal-line"
              :searchable="competitionFilterOptions.length > 7"
              searchPlaceholder="Cari cabang lomba..."
            />
          </div>

          <!-- Filter Jenis Lomba (Individu vs Tim) -->
          <div class="col-6 col-md-4 col-xl-2">
            <CustomSelect
              v-model="typeFilter"
              :options="typeFilterOptions"
              size="sm"
              placeholder="Jenis Lomba..."
              prefixIcon="ri-user-shared-line"
            />
          </div>

          <!-- Filter Peran Siswa (Kapten / Anggota / Tunggal) -->
          <div class="col-6 col-md-4 col-xl-2">
            <CustomSelect
              v-model="roleFilter"
              :options="roleFilterOptions"
              size="sm"
              placeholder="Peran Siswa..."
              prefixIcon="ri-vip-crown-line"
            />
          </div>

          <!-- Filter Status Bayar -->
          <div class="col-6 col-md-4 col-xl-2">
            <CustomSelect
              v-model="paymentFilter"
              :options="paymentFilterOptions"
              size="sm"
              placeholder="Status Bayar..."
              prefixIcon="ri-money-dollar-circle-line"
            />
          </div>
        </div>

        <!-- Baris Filter Tambahan: Presensi & Asal Sekolah -->
        <div class="row g-2 align-items-center mt-2 pt-2 border-top">
          <!-- Filter Asal Sekolah -->
          <div class="col-12 col-md-6 col-xl-4">
            <CustomSelect
              v-model="schoolFilter"
              :options="schoolFilterOptions"
              size="sm"
              placeholder="Pilih Asal Sekolah..."
              prefixIcon="ri-community-line"
              :searchable="schoolFilterOptions.length > 7"
              searchPlaceholder="Cari nama sekolah..."
            />
          </div>

          <!-- Filter Presensi Gate -->
          <div class="col-6 col-md-4 col-xl-3">
            <CustomSelect
              v-model="checkinFilter"
              :options="checkinFilterOptions"
              size="sm"
              placeholder="Presensi / Gate..."
              prefixIcon="ri-qr-scan-line"
            />
          </div>

          <!-- Action Reset & Counter -->
          <div class="col-6 col-md-2 col-xl-5 text-end d-flex align-items-center justify-content-end gap-2">
            <span class="text-muted fs-11 d-none d-sm-inline">
              Menampilkan <strong>{{ filteredCount }}</strong> dari {{ participants.length }} siswa
            </span>
            <button 
              type="button" 
              @click="resetFilters" 
              class="btn btn-sm btn-ghost-secondary text-muted d-inline-flex align-items-center gap-1 fs-11"
              title="Reset semua filter"
            >
              <i class="ri-refresh-line"></i> Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- ============================================================== -->
      <!-- TABLE SECTION                                                  -->
      <!-- ============================================================== -->
      <div class="table-responsive">
        <table class="table table-hover align-middle table-nowrap mb-0">
          <thead class="table-light fs-11 text-uppercase text-muted border-bottom">
            <tr>
              <th scope="col" style="width: 50px;" class="text-center">#</th>
              <th scope="col" style="min-width: 220px;">Peserta (Siswa)</th>
              <th scope="col" style="min-width: 140px;">NISN & Kelas</th>
              <th scope="col" style="min-width: 180px;">Asal Sekolah</th>
              <th scope="col" style="min-width: 180px;">Cabang Lomba</th>
              <th scope="col" style="min-width: 140px;">Jenis & Peran</th>
              <th scope="col" style="min-width: 140px;" class="d-print-none">Kontak (WA)</th>
              <th scope="col" style="min-width: 120px;">No. Registrasi</th>
              <th scope="col" style="min-width: 120px;">Status</th>
              <th scope="col" style="min-width: 100px;">Presensi</th>
              <th scope="col" style="width: 80px;" class="text-center d-print-none">Aksi</th>
            </tr>
          </thead>
          <tbody class="fs-12">
            <tr v-if="paginatedParticipants.length === 0">
              <td colspan="11" class="text-center py-5">
                <div class="avatar-md mx-auto mb-3">
                  <div class="avatar-title bg-light text-muted rounded-circle fs-28">
                    <i class="ri-user-unfollow-line"></i>
                  </div>
                </div>
                <h6 class="fw-bold text-dark fs-14 mb-1">Tidak ada data siswa yang cocok</h6>
                <p class="text-muted fs-12 mb-3">
                  Coba sesuaikan kata kunci pencarian atau bersihkan filter di atas.
                </p>
                <button type="button" @click="resetFilters" class="btn btn-sm btn-primary">
                  <i class="ri-refresh-line me-1"></i> Bersihkan Filter
                </button>
              </td>
            </tr>

            <tr v-for="(item, idx) in paginatedParticipants" :key="item.id" class="cursor-pointer">
              <!-- # Index -->
              <td class="text-center text-muted fw-semibold">
                {{ (currentPage - 1) * perPage + idx + 1 }}
              </td>

              <!-- Peserta (Siswa) Info -->
              <td>
                <div class="d-flex align-items-center gap-2.5">
                  <!-- Avatar Thumbnail -->
                  <div class="avatar-xs flex-shrink-0 position-relative">
                    <img 
                      v-if="item.photo" 
                      :src="item.photo" 
                      :alt="item.name"
                      class="rounded-circle w-100 h-100 object-fit-cover border shadow-2xs" 
                      @error="$event.target.style.display='none'"
                    />
                    <span 
                      v-else 
                      class="avatar-title rounded-circle fs-11 fw-bold border"
                      :class="item.gender === 'Perempuan' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'"
                    >
                      {{ getInitials(item.name) }}
                    </span>
                    <!-- Crown icon if captain -->
                    <span 
                      v-if="item.is_captain" 
                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark p-0.5 border border-white"
                      title="Kapten / Ketua Regu"
                      style="font-size: 8px;"
                    >
                      <i class="ri-vip-crown-fill"></i>
                    </span>
                  </div>

                  <!-- Name and tags -->
                  <div class="min-w-0">
                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                      <span 
                        @click="openStudentDetail(item)"
                        class="fw-bold text-dark text-hover-primary text-truncate cursor-pointer"
                        title="Klik untuk melihat profil lengkap"
                      >
                        {{ item.name }}
                      </span>
                      <!-- Gender Badge -->
                      <span 
                        v-if="item.gender && item.gender !== '-'"
                        class="badge fs-9 px-1 py-0.5"
                        :class="item.gender === 'Perempuan' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary'"
                      >
                        {{ item.gender === 'Perempuan' ? 'P' : 'L' }}
                      </span>
                    </div>

                    <!-- Additional subtitle if available -->
                    <div class="text-muted fs-11 text-truncate">
                      <span v-if="item.birth_date" class="me-1">Lahir: {{ item.birth_date }}</span>
                      <span v-else>{{ item.source }}</span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- NISN & Kelas -->
              <td>
                <div class="d-flex align-items-center gap-1">
                  <span class="fw-semibold text-dark font-monospace fs-11">
                    {{ item.nisn || '-' }}
                  </span>
                  <button 
                    v-if="item.nisn && item.nisn !== '-'" 
                    @click.stop="copyToClipboard(item.nisn, 'NISN')" 
                    class="btn btn-ghost-secondary btn-icon btn-2xs p-0 text-muted d-print-none"
                    title="Salin NISN"
                  >
                    <i class="ri-file-copy-line fs-10"></i>
                  </button>
                </div>
                <div class="text-muted fs-11">
                  {{ item.grade || '-' }}
                </div>
              </td>

              <!-- Asal Sekolah -->
              <td>
                <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" :title="item.school_name">
                  {{ item.school_name }}
                </div>
                <div class="text-muted fs-11" v-if="item.school_district">
                  <i class="ri-map-pin-line me-0.5"></i> Kec. {{ item.school_district }}
                </div>
              </td>

              <!-- Cabang Lomba -->
              <td>
                <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" :title="item.competition_title">
                  {{ item.competition_title }}
                </div>
                <div class="text-muted fs-11">
                  {{ item.competition_category }}
                </div>
              </td>

              <!-- Jenis & Peran -->
              <td>
                <div class="d-flex flex-column gap-1">
                  <!-- Badge Jenis Lomba: Individu vs Tim/Regu -->
                  <div>
                    <span 
                      v-if="item.participant_type === 'tim'" 
                      class="badge border px-1.5 py-0.5 fs-10"
                      style="background-color: rgba(139, 92, 246, 0.1); color: #7c3aed; border-color: rgba(139, 92, 246, 0.25) !important;"
                    >
                      <i class="ri-team-line me-1"></i> Tim / Regu
                    </span>
                    <span 
                      v-else 
                      class="badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5 fs-10"
                    >
                      <i class="ri-user-line me-1"></i> Individu
                    </span>
                  </div>

                  <!-- Badge Peran: Kapten vs Anggota vs Tunggal -->
                  <div>
                    <span 
                      v-if="item.is_captain" 
                      class="badge bg-warning-subtle text-warning border border-warning-subtle px-1.5 py-0.5 fs-10 fw-semibold"
                    >
                      <i class="ri-vip-crown-line me-0.5"></i> Kapten Regu
                    </span>
                    <span 
                      v-else-if="item.participant_type === 'tim'" 
                      class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0.5 fs-10"
                    >
                      {{ item.team_role || 'Anggota' }}
                    </span>
                    <span 
                      v-else 
                      class="badge bg-light text-muted border px-1.5 py-0.5 fs-10"
                    >
                      Peserta Tunggal
                    </span>
                  </div>
                </div>
              </td>

              <!-- Kontak (WA) -->
              <td class="d-print-none">
                <div v-if="item.wa_number && item.wa_number !== '-'" class="d-flex align-items-center gap-1.5">
                  <a 
                    :href="getWaChatUrl(item.wa_number)" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 px-1.5 py-1 text-decoration-none fs-11"
                    title="Chat WhatsApp Siswa"
                  >
                    <i class="ri-whatsapp-line fs-12"></i>
                    <span>{{ item.wa_number }}</span>
                  </a>
                </div>
                <div v-else-if="item.mentor_wa && item.mentor_wa !== '-'" class="d-flex align-items-center gap-1.5">
                  <a 
                    :href="getWaChatUrl(item.mentor_wa)" 
                    target="_blank" 
                    rel="noopener noreferrer"
                    class="badge bg-light text-muted border d-inline-flex align-items-center gap-1 px-1.5 py-1 text-decoration-none fs-11"
                    title="Nomor Pembina (Siswa belum isi WA)"
                  >
                    <i class="ri-phone-line fs-11"></i>
                    <span>{{ item.mentor_wa }} (Pembina)</span>
                  </a>
                </div>
                <div v-else class="text-muted fs-11">-</div>
              </td>

              <!-- No. Registrasi & Paket -->
              <td>
                <Link 
                  :href="`/admin/pendaftar/${item.registration_id}`" 
                  class="fw-bold text-primary font-monospace text-decoration-none fs-11"
                  title="Buka form pendaftaran delegasi"
                >
                  #{{ item.reg_code }}
                </Link>
                <div class="mt-0.5">
                  <span 
                    class="badge fs-9 px-1 py-0.5"
                    :class="item.package_type === 'Paket All-In' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'"
                  >
                    {{ item.package_type === 'Paket All-In' ? 'All-In' : 'Satuan' }}
                  </span>
                </div>
              </td>

              <!-- Status Bayar / Verifikasi -->
              <td>
                <span 
                  class="badge fs-10 px-2 py-1"
                  :class="{
                    'bg-success-subtle text-success border border-success-subtle': item.payment_status === 'Terverifikasi',
                    'bg-warning-subtle text-warning border border-warning-subtle': item.payment_status === 'Menunggu Verifikasi',
                    'bg-danger-subtle text-danger border border-danger-subtle': item.payment_status === 'Ditolak' || item.payment_status === 'Belum Lunas'
                  }"
                >
                  <i 
                    :class="{
                      'ri-checkbox-circle-line me-1': item.payment_status === 'Terverifikasi',
                      'ri-time-line me-1': item.payment_status === 'Menunggu Verifikasi',
                      'ri-close-circle-line me-1': item.payment_status === 'Ditolak' || item.payment_status === 'Belum Lunas'
                    }"
                  ></i>
                  {{ item.payment_status || 'Menunggu' }}
                </span>
              </td>

              <!-- Presensi Gate -->
              <td>
                <span 
                  v-if="item.checkin_status && item.checkin_status.toLowerCase().includes('sudah')"
                  class="badge bg-success-subtle text-success border border-success-subtle fs-10 px-1.5 py-0.5"
                  :title="item.checkin_at ? `Check-in: ${item.checkin_at}` : 'Sudah Hadir'"
                >
                  <i class="ri-check-line me-0.5"></i> Hadir
                </span>
                <span 
                  v-else 
                  class="badge bg-light text-muted border fs-10 px-1.5 py-0.5"
                >
                  Belum
                </span>
              </td>

              <!-- Aksi -->
              <td class="text-center d-print-none">
                <div class="d-flex align-items-center justify-content-center gap-1">
                  <!-- View Profile Modal -->
                  <button 
                    type="button" 
                    @click="openStudentDetail(item)"
                    class="btn btn-sm btn-ghost-primary btn-icon fs-14"
                    title="Lihat Profil Siswa"
                  >
                    <i class="ri-eye-line"></i>
                  </button>

                  <!-- Link to Registration Form -->
                  <Link 
                    :href="`/admin/pendaftar/${item.registration_id}`"
                    class="btn btn-sm btn-ghost-secondary btn-icon fs-14"
                    title="Buka Pendaftaran Delegasi"
                  >
                    <i class="ri-external-link-line"></i>
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ============================================================== -->
      <!-- CARD FOOTER: PAGINATION & ROWS SELECTOR                        -->
      <!-- ============================================================== -->
      <div class="card-footer bg-white border-top py-3 d-print-none">
        <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
          <!-- Left: Showing info & Per Page -->
          <div class="d-flex align-items-center gap-3">
            <span class="text-muted fs-12">
              Menampilkan 
              <strong class="text-dark">
                {{ filteredCount > 0 ? (currentPage - 1) * perPage + 1 : 0 }}
              </strong> 
              - 
              <strong class="text-dark">
                {{ Math.min(currentPage * perPage, filteredCount) }}
              </strong> 
              dari 
              <strong class="text-dark">{{ filteredCount }}</strong> data
            </span>

            <div class="d-flex align-items-center gap-1.5">
              <span class="text-muted fs-11">Baris:</span>
              <div style="width: 110px;">
                <CustomSelect
                  v-model="perPage"
                  :options="perPageOptions"
                  size="xs"
                />
              </div>
            </div>
          </div>

          <!-- Right: Pagination Buttons -->
          <nav v-if="totalPages > 1" aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="goToPage(currentPage - 1)" aria-label="Previous">
                  <i class="ri-arrow-left-s-line"></i>
                </button>
              </li>

              <li 
                v-for="p in totalPages" 
                :key="p" 
                class="page-item" 
                :class="{ active: p === currentPage }"
              >
                <!-- Only show window of pages if totalPages is large -->
                <button 
                  v-if="p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)"
                  class="page-link" 
                  @click="goToPage(p)"
                >
                  {{ p }}
                </button>
                <span v-else-if="p === currentPage - 3 || p === currentPage + 3" class="page-link disabled">
                  ...
                </span>
              </li>

              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="goToPage(currentPage + 1)" aria-label="Next">
                  <i class="ri-arrow-right-s-line"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL: PROFIL LENGKAP PESERTA SISWA                            -->
    <!-- ============================================================== -->
    <div 
      v-if="showDetailModal && activeStudent" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1060;"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
          <!-- Modal Header -->
          <div class="modal-header bg-light border-bottom py-3">
            <div class="d-flex align-items-center gap-2">
              <span class="avatar-xs">
                <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-14">
                  <i class="ri-user-star-line"></i>
                </span>
              </span>
              <div>
                <h5 class="modal-title fw-bold text-dark fs-15 mb-0">Profil Lengkap Peserta Siswa</h5>
                <span class="text-muted fs-11">Kode Registrasi: #{{ activeStudent.reg_code }}</span>
              </div>
            </div>
            <button type="button" class="btn-close" @click="closeStudentDetail" aria-label="Close"></button>
          </div>

          <!-- Modal Body -->
          <div class="modal-body p-4">
            <div class="row g-4">
              <!-- Kolom Kiri: Pas Foto & Status Tim -->
              <div class="col-12 col-md-4 text-center border-end-md">
                <div class="mx-auto position-relative" style="width: 140px; height: 180px;">
                  <img 
                    v-if="activeStudent.photo" 
                    :src="activeStudent.photo" 
                    :alt="activeStudent.name"
                    class="w-100 h-100 object-fit-cover rounded-3 border shadow-sm"
                  />
                  <div 
                    v-else 
                    class="w-100 h-100 rounded-3 border bg-light d-flex flex-column align-items-center justify-content-center text-muted"
                  >
                    <i class="ri-user-smile-line fs-48 mb-2"></i>
                    <span class="fs-11">Belum Ada Foto</span>
                  </div>

                  <!-- Crown badge if captain -->
                  <div 
                    v-if="activeStudent.is_captain" 
                    class="position-absolute bottom-0 start-50 translate-middle-x mb-2 badge bg-warning text-dark px-2 py-1 shadow-sm fs-11"
                  >
                    <i class="ri-vip-crown-fill me-1"></i> Kapten Regu
                  </div>
                </div>

                <h6 class="fw-bold text-dark fs-15 mt-3 mb-1">{{ activeStudent.name }}</h6>
                <p class="text-muted fs-12 mb-2">{{ activeStudent.school_name }}</p>

                <!-- Jenis & Peran Badges -->
                <div class="d-flex justify-content-center gap-1.5 flex-wrap">
                  <span 
                    class="badge px-2 py-1 fs-11"
                    :class="activeStudent.participant_type === 'tim' ? 'bg-purple-subtle text-purple border' : 'bg-primary-subtle text-primary border border-primary-subtle'"
                    :style="activeStudent.participant_type === 'tim' ? 'background-color: rgba(139, 92, 246, 0.1); color: #7c3aed;' : ''"
                  >
                    {{ activeStudent.participant_type === 'tim' ? 'Lomba Tim / Regu' : 'Lomba Individu' }}
                  </span>
                  <span 
                    v-if="activeStudent.gender && activeStudent.gender !== '-'" 
                    class="badge px-2 py-1 fs-11"
                    :class="activeStudent.gender === 'Perempuan' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info'"
                  >
                    {{ activeStudent.gender }}
                  </span>
                </div>

                <!-- Foto Bebas jika ada -->
                <div v-if="activeStudent.photo_freestyle" class="mt-3">
                  <span class="text-muted fs-11 d-block mb-1">Foto Bebas / Gaya:</span>
                  <a :href="activeStudent.photo_freestyle" target="_blank" class="btn btn-2xs btn-outline-secondary">
                    <i class="ri-image-line me-1"></i> Lihat Foto Bebas
                  </a>
                </div>
              </div>

              <!-- Kolom Kanan: Rincian Lengkap -->
              <div class="col-12 col-md-8">
                <!-- 1. Biodata Siswa -->
                <div class="mb-3">
                  <h6 class="fs-12 text-uppercase fw-bold text-primary mb-2.5 d-flex align-items-center gap-1">
                    <i class="ri-profile-line"></i> Biodata Pribadi Siswa
                  </h6>
                  <div class="row g-2">
                    <div class="col-6">
                      <div class="p-2.5 rounded bg-light border">
                        <span class="text-muted fs-11 d-block">NISN:</span>
                        <span class="fw-bold text-dark fs-12 font-monospace">{{ activeStudent.nisn || '-' }}</span>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="p-2.5 rounded bg-light border">
                        <span class="text-muted fs-11 d-block">Kelas / Tingkat:</span>
                        <span class="fw-bold text-dark fs-12">{{ activeStudent.grade || '-' }}</span>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="p-2.5 rounded bg-light border">
                        <span class="text-muted fs-11 d-block">Tanggal Lahir:</span>
                        <span class="fw-bold text-dark fs-12">{{ activeStudent.birth_date || '-' }}</span>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="p-2.5 rounded bg-light border">
                        <span class="text-muted fs-11 d-block">No. WhatsApp Siswa:</span>
                        <a 
                          v-if="activeStudent.wa_number && activeStudent.wa_number !== '-'" 
                          :href="getWaChatUrl(activeStudent.wa_number)" 
                          target="_blank" 
                          class="fw-bold text-success fs-12 text-decoration-none"
                        >
                          <i class="ri-whatsapp-line me-0.5"></i> {{ activeStudent.wa_number }}
                        </a>
                        <span v-else class="text-muted fs-12">-</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 2. Informasi Perlombaan & Peran -->
                <div class="mb-3">
                  <h6 class="fs-12 text-uppercase fw-bold text-primary mb-2.5 d-flex align-items-center gap-1">
                    <i class="ri-medal-line"></i> Cabang Lomba & Peran
                  </h6>
                  <div class="p-3 rounded bg-light border">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <div>
                        <span class="fw-bold text-dark fs-13 d-block">{{ activeStudent.competition_title }}</span>
                        <span class="text-muted fs-11">Kategori: {{ activeStudent.competition_category }}</span>
                      </div>
                      <span 
                        class="badge fs-11 px-2 py-1"
                        :class="activeStudent.is_captain ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary'"
                      >
                        {{ activeStudent.team_role }}
                      </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 pt-2 border-top text-muted fs-11">
                      <span>Sumber data: {{ activeStudent.source }}</span>
                    </div>
                  </div>
                </div>

                <!-- 3. Sekolah & Pembina Pendamping -->
                <div>
                  <h6 class="fs-12 text-uppercase fw-bold text-primary mb-2.5 d-flex align-items-center gap-1">
                    <i class="ri-community-line"></i> Kontingen Sekolah & Pembina
                  </h6>
                  <div class="p-3 rounded bg-light border">
                    <div class="row g-2">
                      <div class="col-12">
                        <span class="text-muted fs-11 d-block">Asal Sekolah:</span>
                        <span class="fw-bold text-dark fs-12">{{ activeStudent.school_name }}</span>
                        <span v-if="activeStudent.school_district" class="text-muted fs-11 ms-1">
                          (Kec. {{ activeStudent.school_district }})
                        </span>
                      </div>
                      <div class="col-6">
                        <span class="text-muted fs-11 d-block">Pembina Pendamping:</span>
                        <span class="fw-semibold text-dark fs-12">{{ activeStudent.mentor_name }}</span>
                      </div>
                      <div class="col-6">
                        <span class="text-muted fs-11 d-block">Kontak WA Pembina:</span>
                        <a 
                          v-if="activeStudent.mentor_wa && activeStudent.mentor_wa !== '-'"
                          :href="getWaChatUrl(activeStudent.mentor_wa)"
                          target="_blank"
                          class="fw-semibold text-success fs-12 text-decoration-none"
                        >
                          <i class="ri-whatsapp-line me-0.5"></i> {{ activeStudent.mentor_wa }}
                        </a>
                        <span v-else class="text-muted fs-12">-</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="modal-footer bg-light border-top py-2.5 px-4 d-flex justify-content-between">
            <Link 
              :href="`/admin/pendaftar/${activeStudent.registration_id}`" 
              class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5"
            >
              <i class="ri-external-link-line"></i> Buka Data Registrasi Induk (#{{ activeStudent.reg_code }})
            </Link>
            <button type="button" class="btn btn-sm btn-secondary" @click="closeStudentDetail">
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<style scoped>
/* Dark Mode Enhancements for high contrast */
:deep([data-layout-mode="dark"]) .card {
  background-color: #1a1d21;
  border-color: #2a2f34;
}

:deep([data-layout-mode="dark"]) .table-light {
  background-color: #212529 !important;
  color: #f8fafc !important;
}

:deep([data-layout-mode="dark"]) .text-dark {
  color: #f8fafc !important;
}

:deep([data-layout-mode="dark"]) .bg-light {
  background-color: #212529 !important;
}

:deep([data-layout-mode="dark"]) .border {
  border-color: #32383e !important;
}

:deep([data-layout-mode="dark"]) .modal-content {
  background-color: #1a1d21;
  border-color: #32383e;
}

:deep([data-layout-mode="dark"]) .modal-header,
:deep([data-layout-mode="dark"]) .modal-footer {
  background-color: #212529 !important;
  border-color: #32383e !important;
}

/* Subtle badge luminescence in Dark Mode */
:deep([data-layout-mode="dark"]) .badge.bg-primary-subtle {
  background-color: rgba(59, 130, 246, 0.2) !important;
  color: #93c5fd !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-success-subtle {
  background-color: rgba(16, 185, 129, 0.2) !important;
  color: #86efac !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-warning-subtle {
  background-color: rgba(245, 158, 11, 0.2) !important;
  color: #fde047 !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-danger-subtle {
  background-color: rgba(239, 68, 68, 0.2) !important;
  color: #fca5a5 !important;
}

:deep([data-layout-mode="dark"]) .badge.bg-info-subtle {
  background-color: rgba(14, 165, 233, 0.2) !important;
  color: #7dd3fc !important;
}

/* Print Styling */
@media print {
  body {
    background: #ffffff !important;
    color: #000000 !important;
  }
  .card {
    border: none !important;
    box-shadow: none !important;
  }
  .table {
    border: 1px solid #dee2e6 !important;
  }
  .table th, .table td {
    padding: 4px 8px !important;
    font-size: 10px !important;
  }
}
</style>

