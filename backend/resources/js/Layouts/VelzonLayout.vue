<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import VelzonConfirmModal from '@/Components/VelzonConfirmModal.vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user || { name: 'Panitia Semarak', email: 'admin@maswahas.sch.id' });
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const currentUrl = computed(() => page.url || window.location.pathname);
const school = computed(() => page.props.schoolProfile || {});
const eventInfo = computed(() => {
  const edition = school.value.event_edition || 'XIV';
  const year = school.value.event_year || '2026';
  const name = school.value.event_name || `SEMARAK ALIYAH ${edition} ${year}`;
  return {
    edition,
    year,
    full: name,
    badge: `Edisi ${edition} • ${year}`
  };
});

// Quick Edition Master Modal State
const isEditionModalOpen = ref(false);
const isSubmittingEdition = ref(false);
const editionForm = ref({
  event_edition: 'XIV',
  event_year: '2026',
  event_name: 'SEMARAK ALIYAH XIV 2026',
});

const quickEditions = ['XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX'];
const quickYears = ['2025', '2026', '2027', '2028', '2029', '2030'];

watch(eventInfo, (val) => {
  editionForm.value.event_edition = val.edition;
  editionForm.value.event_year = val.year;
  editionForm.value.event_name = val.full;
}, { immediate: true });

function openEditionModal() {
  editionForm.value.event_edition = eventInfo.value.edition;
  editionForm.value.event_year = eventInfo.value.year;
  editionForm.value.event_name = eventInfo.value.full;
  isEditionModalOpen.value = true;
}

function closeEditionModal() {
  isEditionModalOpen.value = false;
}

function setQuickEdition(ed) {
  editionForm.value.event_edition = ed;
  syncEventName();
}

function setQuickYear(yr) {
  editionForm.value.event_year = yr;
  syncEventName();
}

function syncEventName() {
  const ed = (editionForm.value.event_edition || '').trim().toUpperCase();
  const yr = (editionForm.value.event_year || '').trim();
  editionForm.value.event_name = `SEMARAK ALIYAH ${ed} ${yr}`.trim();
}

function submitQuickEdition() {
  isSubmittingEdition.value = true;
  router.post('/admin/pengaturan/update-edisi', editionForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      closeEditionModal();
      success(`Master Event Edisi ${editionForm.value.event_edition} (${editionForm.value.event_year}) berhasil diterapkan!`, 'Edisi Berubah');
    },
    onError: () => {
      error('Gagal memperbarui master edisi event.');
    },
    onFinish: () => {
      isSubmittingEdition.value = false;
    }
  });
}

const { toasts, removeToast, success, error } = useToast();

watch(() => page.props.flash?.success, (val) => {
  if (val) {
    success(val, 'Berhasil');
  }
}, { immediate: true });

watch(() => page.props.flash?.error, (val) => {
  if (val) {
    error(val, 'Pemberitahuan');
  }
}, { immediate: true });

const isSidebarCollapsed = ref(false);
const isMobileSidebarOpen = ref(false);
const isDarkTheme = ref(false);
const isUserDropdownOpen = ref(false);
const isAppsDropdownOpen = ref(false);
const isSettingsMenuOpen = ref(true);

watch(() => currentUrl.value, (url) => {
  if (url && url.startsWith('/admin/pengaturan')) {
    isSettingsMenuOpen.value = true;
  }
}, { immediate: true });

function toggleSidebar() {
  if (window.innerWidth < 992) {
    isMobileSidebarOpen.value = !isMobileSidebarOpen.value;
  } else {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
  }
}

function toggleTheme() {
  isDarkTheme.value = !isDarkTheme.value;
  const theme = isDarkTheme.value ? 'dark' : 'light';
  document.documentElement.setAttribute('data-bs-theme', theme);
  document.documentElement.style.colorScheme = theme;
  if (theme === 'dark') {
    document.documentElement.style.backgroundColor = '#0b0f19';
    document.body.style.backgroundColor = '#0b0f19';
    document.body.style.color = '#f1f5f9';
  } else {
    document.documentElement.style.backgroundColor = '#f3f3f9';
    document.body.style.backgroundColor = '#f3f3f9';
    document.body.style.color = '#495057';
  }
  localStorage.setItem('velzon_theme_v2', theme);
  localStorage.setItem('velzon_theme', theme);
}

function toggleFullscreen() {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen().catch(() => {});
  } else {
    document.exitFullscreen().catch(() => {});
  }
}

function logout() {
  router.post('/logout', {}, {
    onFinish: () => {
      window.location.href = '/login';
    }
  });
}

onMounted(() => {
  const savedTheme = localStorage.getItem('velzon_theme_v2');
  const effectiveTheme = savedTheme || 'dark';

  isDarkTheme.value = (effectiveTheme === 'dark');
  document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
  document.documentElement.style.colorScheme = effectiveTheme;
  if (effectiveTheme === 'dark') {
    document.documentElement.style.backgroundColor = '#0b0f19';
    document.body.style.backgroundColor = '#0b0f19';
    document.body.style.color = '#f1f5f9';
  } else {
    document.documentElement.style.backgroundColor = '#f3f3f9';
    document.body.style.backgroundColor = '#f3f3f9';
    document.body.style.color = '#495057';
  }
  localStorage.setItem('velzon_theme_v2', effectiveTheme);
  localStorage.setItem('velzon_theme', effectiveTheme);
});
</script>

<template>
  <div 
    id="layout-wrapper" 
    :class="{ 'sidebar-enable': isMobileSidebarOpen, 'vertical-collpsed': isSidebarCollapsed, 'dark': isDarkTheme }"
    class="min-h-screen flex flex-col font-sans"
    :style="{ backgroundColor: isDarkTheme ? '#0b0f19' : '#f3f3f9', color: isDarkTheme ? '#f1f5f9' : '#495057' }"
  >
    <!-- ============================================================== -->
    <!-- TOPBAR HEADER (Velzon Official Header)                         -->
    <!-- ============================================================== -->
    <header 
      id="page-topbar" 
      :style="{ 
        backgroundColor: isDarkTheme ? '#111827' : '#ffffff', 
        borderBottom: isDarkTheme ? '1px solid rgba(255,255,255,0.1)' : '1px solid #e9ebec',
        color: isDarkTheme ? '#f8fafc' : '#495057',
        left: isSidebarCollapsed ? '70px' : '' 
      }"
    >
      <div class="navbar-header">
        <div class="d-flex align-items-center gap-3">
          <!-- Hamburger Sidebar Toggle Button -->
          <button 
            type="button" 
            class="btn btn-topbar" 
            @click="toggleSidebar"
            aria-label="Toggle Navigation"
          >
            <i class="ri-menu-2-line fs-20"></i>
          </button>

          <!-- Topbar Search Bar with Shortcut -->
          <form class="app-search d-none d-md-block" @submit.prevent>
            <div class="position-relative">
              <input 
                type="text" 
                class="form-control" 
                placeholder="Cari nama, sekolah, ID..." 
                autocomplete="off"
              />
              <span class="ri-search-line search-widget-icon"></span>
              <span class="search-shortcut">/</span>
            </div>
          </form>

          <div class="d-none d-lg-block">
            <button 
              type="button" 
              class="edition-badge-btn"
              @click="openEditionModal"
              title="Kunci Utama: Klik untuk kelola Master Edisi & Tahun Event"
            >
              <i class="ri-flashlight-line align-middle me-1.5 text-warning edition-badge-icon"></i>
              <span class="edition-badge-text">Edisi {{ eventInfo.edition }} • {{ eventInfo.year }}</span>
              <i class="ri-equalizer-line ms-1.5 opacity-70 edition-badge-tune"></i>
            </button>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <!-- Mobile Edition Button -->
          <button 
            type="button" 
            class="btn btn-topbar d-lg-none" 
            @click="openEditionModal"
            title="Kelola Master Edisi & Tahun Event"
          >
            <i class="ri-flashlight-line text-warning fs-18"></i>
          </button>

          <!-- External Public Web Link -->
          <Link 
            href="/" 
            target="_blank" 
            class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1 rounded-pill px-3"
          >
            <i class="ri-external-link-line"></i> Web Publik
          </Link>

          <!-- Apps Dropdown -->
          <div class="position-relative">
            <button 
              type="button" 
              class="btn btn-topbar" 
              @click="isAppsDropdownOpen = !isAppsDropdownOpen"
              title="Aplikasi & Pintasan"
            >
              <i class="bx bx-category-alt fs-22"></i>
            </button>
            <div 
              v-if="isAppsDropdownOpen" 
              class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 show position-absolute end-0 mt-2 shadow-lg"
              style="width: 320px; z-index: 1050;"
            >
              <div class="p-3 border-bottom border-light">
                <h6 class="m-0 fw-semibold fs-14">Aplikasi & Pintasan</h6>
              </div>
              <div class="p-2">
                <div class="row g-0">
                  <div class="col-4">
                    <Link class="dropdown-item text-center py-3 rounded" href="/" target="_blank">
                      <i class="ri-earth-line fs-24 text-primary d-block mb-1"></i>
                      <span class="fs-12 text-muted">Landing Page</span>
                    </Link>
                  </div>
                  <div class="col-4">
                    <Link class="dropdown-item text-center py-3 rounded" href="/layanan-registrasi" target="_blank">
                      <i class="ri-user-add-line fs-24 text-success d-block mb-1"></i>
                      <span class="fs-12 text-muted">Form Registrasi</span>
                    </Link>
                  </div>
                  <div class="col-4">
                    <a class="dropdown-item text-center py-3 rounded" href="/assets/doc/JUKNIS-SEMARAK-2026.pdf" target="_blank">
                      <i class="ri-file-pdf-line fs-24 text-danger d-block mb-1"></i>
                      <span class="fs-12 text-muted">Juknis PDF</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Fullscreen Toggle -->
          <button 
            type="button" 
            class="btn btn-topbar d-none d-sm-inline-flex" 
            @click="toggleFullscreen" 
            title="Layar Penuh"
          >
            <i class="bx bx-fullscreen fs-22"></i>
          </button>

          <!-- Theme Toggle (Dark / Light) -->
          <button 
            type="button" 
            id="theme-toggle-btn"
            class="btn btn-topbar" 
            @click="toggleTheme" 
            title="Ganti Tema"
          >
            <i :class="isDarkTheme ? 'bx bx-sun fs-22 text-warning' : 'bx bx-moon fs-22'"></i>
          </button>

          <!-- Export Excel Shortcut -->
          <a 
            href="/admin/export" 
            class="btn btn-sm btn-success d-none d-md-inline-flex align-items-center gap-1 rounded-pill px-3"
          >
            <i class="ri-file-excel-2-line"></i> Export Data
          </a>

          <!-- User Profile Dropdown -->
          <div class="position-relative ms-1">
            <button 
              class="btn btn-sm p-0 d-flex align-items-center gap-2 border-0 bg-transparent" 
              type="button" 
              @click="isUserDropdownOpen = !isUserDropdownOpen"
            >
              <img 
                src="/assets/img/logo.png" 
                alt="Admin Avatar" 
                class="rounded-circle" 
                style="width: 36px; height: 36px; object-fit: contain; background: #111827; padding: 2px;"
              />
              <div class="d-none d-xl-block text-start">
                <span class="d-block fw-semibold text-dark fs-13">{{ authUser.name }}</span>
                <span class="d-block text-muted fs-11">Superadmin Panitia</span>
              </div>
            </button>

            <div 
              v-if="isUserDropdownOpen" 
              class="dropdown-menu dropdown-menu-end shadow-lg show position-absolute end-0 mt-2"
              style="min-width: 200px; z-index: 1050;"
            >
              <div class="px-3 py-2 border-bottom">
                <h6 class="mb-0 fs-13 fw-semibold">{{ authUser.name }}</h6>
                <small class="text-muted">{{ authUser.email }}</small>
              </div>
              <Link class="dropdown-item py-2" href="/admin">
                <i class="ri-dashboard-line text-muted me-2 align-middle"></i> Dashboard
              </Link>
              <a class="dropdown-item py-2" href="/admin/export">
                <i class="ri-download-cloud-line text-muted me-2 align-middle"></i> Download Data
              </a>
              <div class="dropdown-divider"></div>
              <button 
                type="button" 
                @click="logout" 
                class="dropdown-item py-2 text-danger border-0 bg-transparent w-100 text-start"
              >
                <i class="ri-logout-box-line text-danger me-2 align-middle"></i> Keluar (Logout)
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- ============================================================== -->
    <!-- SIDEBAR (Velzon Dark Sidebar)                                  -->
    <!-- ============================================================== -->
    <aside 
      class="app-menu navbar-menu" 
      id="app-sidebar"
      :class="{ 'show': isMobileSidebarOpen }"
      :style="{ 
        backgroundColor: '#111827', 
        borderRight: '1px solid rgba(255, 255, 255, 0.1)',
        width: isSidebarCollapsed ? '70px' : '' 
      }"
    >
      <!-- Brand Logo Box -->
      <div 
        class="navbar-brand-box"
        :style="{ 
          backgroundColor: '#0f172a', 
          borderBottom: '1px solid rgba(255, 255, 255, 0.08)' 
        }"
      >
        <Link href="/admin" class="d-flex align-items-center justify-content-center gap-2 text-decoration-none">
          <img src="/assets/img/logo.png" alt="Logo" style="height: 36px; max-height: 36px; width: auto; max-width: 36px; object-fit: contain;" />
          <div v-show="!isSidebarCollapsed" class="d-flex flex-column text-start">
            <span class="fs-16 fw-bold text-white lh-1" style="letter-spacing: 1.5px; font-family: 'Poppins', sans-serif;">SEMARAK</span>
            <span class="text-warning fw-bold fs-10" style="letter-spacing: 2px;">ALIYAH {{ eventInfo.edition }}</span>
          </div>
        </Link>
      </div>

      <!-- Scrollable Menu -->
      <div class="sidebar-menu-wrapper" id="scrollbar">
        <ul class="navbar-nav" id="navbar-nav">
          <!-- 1. Menu Utama -->
          <li class="menu-title"><span>Menu Utama</span></li>

          <li class="nav-item">
            <Link 
              href="/admin" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl === '/admin' }"
            >
              <i class="ri-dashboard-2-line"></i>
              <span v-show="!isSidebarCollapsed">Dashboard</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/pengguna" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/pengguna') }"
            >
              <i class="ri-user-settings-line text-primary"></i>
              <span v-show="!isSidebarCollapsed">Pengguna & Panitia</span>
            </Link>
          </li>

          <!-- 2. Administrasi Pendaftaran -->
          <li class="menu-title"><span>Administrasi Pendaftaran</span></li>

          <li class="nav-item">
            <Link 
              href="/admin/pendaftar" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/pendaftar') }"
            >
              <i class="ri-team-line"></i>
              <span v-show="!isSidebarCollapsed">Data Pendaftar Masuk</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/sekolah" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/sekolah') }"
            >
              <i class="ri-building-4-line text-success"></i>
              <span v-show="!isSidebarCollapsed">Database Sekolah (SMP/MTs)</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/lomba" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/lomba') }"
            >
              <i class="ri-medal-line"></i>
              <span v-show="!isSidebarCollapsed">Manajemen Lomba</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/tm" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/tm') }"
            >
              <i class="ri-calendar-todo-line text-info"></i>
              <span v-show="!isSidebarCollapsed">Manajemen TM</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/form-builder" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/form-builder') }"
            >
              <i class="ri-survey-line text-warning"></i>
              <span v-show="!isSidebarCollapsed">Formulir Builder</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/biaya" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/biaya') }"
            >
              <i class="ri-money-dollar-circle-line text-success"></i>
              <span v-show="!isSidebarCollapsed">Biaya & Paket Registrasi</span>
            </Link>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="/admin/export">
              <i class="ri-file-excel-2-line text-success"></i>
              <span v-show="!isSidebarCollapsed">Export Excel</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="/assets/doc/JUKNIS-SEMARAK-2026.pdf" target="_blank">
              <i class="ri-file-pdf-line text-danger"></i>
              <span v-show="!isSidebarCollapsed">Juknis Resmi PDF</span>
              <i v-show="!isSidebarCollapsed" class="ri-external-link-line fs-14 ms-auto text-muted"></i>
            </a>
          </li>

          <!-- 3. Kelola Web Publik -->
          <li class="menu-title"><span>Kelola Web Publik</span></li>

          <li class="nav-item">
            <Link 
              href="/admin/sponsor" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/sponsor') }"
            >
              <i class="ri-shake-hands-line text-info"></i>
              <span v-show="!isSidebarCollapsed">Mitra & Sponsorship</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/galeri" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/galeri') }"
            >
              <i class="ri-gallery-fill text-warning"></i>
              <span v-show="!isSidebarCollapsed">Galeri Dokumentasi</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/faq" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/faq') }"
            >
              <i class="ri-question-answer-line text-info"></i>
              <span v-show="!isSidebarCollapsed">Tanya Jawab (FAQ)</span>
            </Link>
          </li>

          <li class="nav-item">
            <Link 
              href="/admin/kustomisasi" 
              class="nav-link menu-link"
              :class="{ 'active': currentUrl.startsWith('/admin/kustomisasi') }"
            >
              <i class="ri-magic-line text-purple"></i>
              <span v-show="!isSidebarCollapsed">Kustomisasi Lanjutan</span>
            </Link>
          </li>

          <!-- 4. Pengaturan Sistem -->
          <li class="menu-title"><span>Pengaturan Sistem</span></li>

          <li class="nav-item">
            <a 
              href="#sidebarSettings" 
              class="nav-link menu-link d-flex align-items-center justify-content-between"
              :class="{ 'active': currentUrl.startsWith('/admin/pengaturan') }"
              @click.prevent="isSettingsMenuOpen = !isSettingsMenuOpen"
            >
              <div class="d-flex align-items-center gap-2">
                <i class="ri-settings-3-line text-warning"></i>
                <span v-show="!isSidebarCollapsed">Pengaturan</span>
              </div>
              <i 
                v-show="!isSidebarCollapsed" 
                :class="isSettingsMenuOpen ? 'ri-arrow-down-s-line' : 'ri-arrow-right-s-line'" 
                class="fs-14 text-muted ms-auto"
              ></i>
            </a>
            <div v-show="isSettingsMenuOpen && !isSidebarCollapsed" class="menu-dropdown">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                  <Link 
                    href="/admin/pengaturan/profil-sekolah" 
                    class="nav-link d-flex align-items-center gap-2"
                    :class="{ 'active': currentUrl.includes('/profil-sekolah') }"
                  >
                    <i class="ri-building-line fs-14"></i>
                    <span>Profil Sekolah</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link 
                    href="/admin/pengaturan/mode-perawatan" 
                    class="nav-link d-flex align-items-center gap-2"
                    :class="{ 'active': currentUrl.includes('/mode-perawatan') }"
                  >
                    <i class="ri-tools-line fs-14"></i>
                    <span>Mode Perawatan</span>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link 
                    href="/admin/pengaturan/seo-og" 
                    class="nav-link d-flex align-items-center gap-2"
                    :class="{ 'active': currentUrl.includes('/seo-og') }"
                  >
                    <i class="ri-search-eye-line fs-14"></i>
                    <span>SEO & Open Graph</span>
                  </Link>
                </li>
              </ul>
            </div>
          </li>

          <!-- 5. Layanan & Operasional -->
          <li class="menu-title"><span>Layanan & Operasional</span></li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="/operasional">
              <i class="ri-flashlight-line text-warning"></i>
              <span v-show="!isSidebarCollapsed">Operasional Hari-H</span>
            </a>
          </li>

          <li class="nav-item">
            <Link class="nav-link menu-link" href="/scoreboard" target="_blank">
              <i class="ri-broadcast-line text-info"></i>
              <span v-show="!isSidebarCollapsed" class="text-truncate">Leaderboard</span>
              <i v-show="!isSidebarCollapsed" class="ri-external-link-line fs-14 ms-auto text-muted"></i>
            </Link>
          </li>

          <li class="nav-item">
            <button 
              type="button" 
              @click="logout" 
              class="nav-link menu-link text-danger border-0 bg-transparent w-100 text-start"
            >
              <i class="ri-logout-box-r-line text-danger"></i>
              <span v-show="!isSidebarCollapsed">Keluar Akun</span>
            </button>
          </li>
        </ul>
      </div>
    </aside>

    <!-- Overlay for mobile sidebar -->
    <div 
      v-if="isMobileSidebarOpen" 
      class="sidebar-backdrop fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="isMobileSidebarOpen = false"
    ></div>

    <!-- ============================================================== -->
    <!-- MAIN CONTENT AREA                                              -->
    <!-- ============================================================== -->
    <main 
      class="main-content flex-1 flex flex-col transition-all duration-300"
      :style="{ marginLeft: isSidebarCollapsed ? '70px' : '' }"
    >
      <div class="page-content flex-1 p-4 lg:p-6" style="margin-top: var(--vz-header-height, 70px);">
        <!-- Injected Page Content -->
        <slot />
      </div>

      <!-- ============================================================== -->
      <!-- VELZON FLOATING TOAST NOTIFICATION CENTER                      -->
      <!-- ============================================================== -->
      <div 
        class="toast-container position-fixed top-0 end-0 p-3" 
        style="z-index: 2050; margin-top: 75px;"
      >
        <TransitionGroup name="toast-slide">
          <div 
            v-for="t in toasts" 
            :key="t.id" 
            class="toast show bg-white shadow-lg border-0 mb-2 rounded-3 overflow-hidden" 
            style="min-width: 320px; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;"
            role="alert" 
            aria-live="assertive" 
            aria-atomic="true"
          >
            <div class="d-flex align-items-stretch">
              <div 
                :class="t.type === 'error' ? 'bg-danger' : (t.type === 'warning' ? 'bg-warning' : 'bg-success')" 
                style="width: 5px;"
              ></div>
              <div class="p-3 d-flex align-items-start gap-2 flex-grow-1">
                <div 
                  :class="t.type === 'error' ? 'text-danger bg-danger-subtle' : (t.type === 'warning' ? 'text-warning bg-warning-subtle' : 'text-success bg-success-subtle')" 
                  class="avatar-xs rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                  style="width: 30px; height: 30px;"
                >
                  <i :class="t.type === 'error' ? 'ri-error-warning-fill fs-18' : (t.type === 'warning' ? 'ri-alert-fill fs-18' : 'ri-checkbox-circle-fill fs-18')"></i>
                </div>
                <div class="flex-grow-1 me-2">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <h6 
                      class="fs-13 fw-bold mb-0" 
                      :class="t.type === 'error' ? 'text-danger' : (t.type === 'warning' ? 'text-warning' : 'text-success')"
                    >
                      {{ t.title }}
                    </h6>
                    <small class="text-muted fs-11">Baru saja</small>
                  </div>
                  <p class="fs-12 text-muted mb-0 lh-base">{{ t.message }}</p>
                </div>
                <button 
                  type="button" 
                  class="btn-close fs-10 mt-1" 
                  @click="removeToast(t.id)"
                  aria-label="Tutup"
                ></button>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Velzon Official Footer -->
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              {{ eventInfo.year }} © {{ school.event_name || 'Semarak Aliyah XIV' }} - {{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }}.
            </div>
            <div class="col-sm-6 text-sm-end">
              Design & Develop by Panitia • Laravit (Vue 3 + Inertia.js + Tailwind CSS)
            </div>
          </div>
        </div>
      </footer>
      <!-- Velzon Global Confirmation Modal -->
      <VelzonConfirmModal />

      <!-- ============================================================== -->
      <!-- MODAL: MASTER GELARAN EDISI & TAHUN EVENT                      -->
      <!-- ============================================================== -->
      <div 
        v-if="isEditionModalOpen" 
        class="modal fade show d-block" 
        tabindex="-1" 
        style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px); z-index: 1055;"
        @click.self="closeEditionModal"
      >
        <div class="modal-dialog modal-dialog-centered" style="max-width: 490px;">
          <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden edition-modal-content">
            <div class="modal-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2.5">
                <div class="avatar-xs rounded-2 d-flex align-items-center justify-content-center edition-header-avatar">
                  <i class="ri-flashlight-line fs-18 text-warning"></i>
                </div>
                <div>
                  <h5 class="modal-title fs-15 fw-bold mb-0 edition-modal-title">Master Gelaran Edisi Event</h5>
                  <small class="edition-modal-subtitle fs-11">Kunci utama acuan seluruh sistem Semarak</small>
                </div>
              </div>
              <button type="button" class="btn-close" @click="closeEditionModal"></button>
            </div>

            <form @submit.prevent="submitQuickEdition">
              <div class="modal-body p-4">
                <!-- Live Preview Card -->
                <div class="p-3 rounded-3 mb-3 border edition-preview-box">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fs-11 fw-bold text-uppercase edition-modal-subtitle">Preview Badge Navbar</span>
                    <span class="badge bg-success-subtle text-success fs-10 px-2 py-0.5">Live Real-time</span>
                  </div>
                  <div class="d-flex align-items-center justify-content-center p-3 rounded-3 edition-preview-surface">
                    <span class="edition-badge-btn" style="pointer-events: none;">
                      <i class="ri-flashlight-line align-middle me-1.5 text-warning edition-badge-icon"></i>
                      <span class="edition-badge-text">Edisi {{ editionForm.event_edition || 'XIV' }} • {{ editionForm.event_year || '2026' }}</span>
                      <i class="ri-equalizer-line ms-1.5 opacity-70 edition-badge-tune"></i>
                    </span>
                  </div>
                </div>

                <!-- Input Edisi & Quick Chips -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold mb-1 edition-modal-label">
                    Pilih / Masukkan Angka Romawi Edisi <span class="text-danger">*</span>
                  </label>
                  <div class="d-flex flex-wrap gap-1.5 mb-2">
                    <button 
                      v-for="ed in quickEditions" 
                      :key="ed" 
                      type="button" 
                      class="btn btn-xs rounded-pill edition-chip"
                      :class="editionForm.event_edition === ed ? 'active' : ''"
                      @click="setQuickEdition(ed)"
                    >
                      {{ ed }}
                    </button>
                  </div>
                  <input 
                    v-model="editionForm.event_edition" 
                    type="text" 
                    class="form-control form-control-sm text-uppercase fw-bold edition-input" 
                    placeholder="Contoh: XIV, XV, XVI"
                    required
                    @input="syncEventName"
                  />
                </div>

                <!-- Input Tahun & Quick Chips -->
                <div class="mb-3">
                  <label class="form-label fs-12 fw-semibold mb-1 edition-modal-label">
                    Tahun Penyelenggaraan <span class="text-danger">*</span>
                  </label>
                  <div class="d-flex flex-wrap gap-1.5 mb-2">
                    <button 
                      v-for="yr in quickYears" 
                      :key="yr" 
                      type="button" 
                      class="btn btn-xs rounded-pill edition-chip"
                      :class="editionForm.event_year === yr ? 'active' : ''"
                      @click="setQuickYear(yr)"
                    >
                      {{ yr }}
                    </button>
                  </div>
                  <input 
                    v-model="editionForm.event_year" 
                    type="number" 
                    class="form-control form-control-sm font-monospace edition-input" 
                    placeholder="Contoh: 2026"
                    min="2020"
                    max="2040"
                    required
                    @input="syncEventName"
                  />
                </div>

                <!-- Nama Acara Utama -->
                <div class="mb-1">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fs-12 fw-semibold mb-0 edition-modal-label">
                      Nama Resmi Event Lengkap
                    </label>
                    <button 
                      type="button" 
                      class="btn btn-xs text-primary p-0 border-0 bg-transparent"
                      @click="syncEventName"
                      title="Sinkronkan otomatis dari Edisi & Tahun"
                    >
                      <i class="ri-magic-line me-0.5"></i> Auto-Generate
                    </button>
                  </div>
                  <input 
                    v-model="editionForm.event_name" 
                    type="text" 
                    class="form-control form-control-sm edition-input" 
                    placeholder="SEMARAK ALIYAH XIV 2026"
                    required
                  />
                  <small class="edition-modal-subtitle fs-11 mt-1 d-block">
                    Nama ini otomatis menjadi rujukan di Sertifikat, Header Publik, Formulir, dan Dokumen Juknis.
                  </small>
                </div>
              </div>

              <div class="modal-footer border-top py-2.5 px-4 d-flex align-items-center justify-content-between">
                <Link 
                  href="/admin/pengaturan/profil-sekolah" 
                  class="btn btn-sm btn-link text-primary p-0 fs-12 text-decoration-none d-flex align-items-center gap-1"
                  @click="closeEditionModal"
                >
                  <i class="ri-settings-4-line"></i> Buka Profil Lengkap
                </Link>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-sm edition-btn-cancel border px-3" @click="closeEditionModal">
                    Batal
                  </button>
                  <button 
                    type="submit" 
                    class="btn btn-sm btn-primary px-3.5 shadow-sm d-flex align-items-center gap-1.5"
                    :disabled="isSubmittingEdition"
                  >
                    <i class="ri-save-line" :class="{ 'ri-spin': isSubmittingEdition }"></i>
                    <span>{{ isSubmittingEdition ? 'Menyimpan...' : 'Terapkan Edisi' }}</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* ============================================================== */
/* NAVBAR EDITION BADGE & MASTER MODAL STYLES                     */
/* ============================================================== */
.edition-badge-btn {
  display: inline-flex;
  align-items: center;
  position: relative;
  background-color: #f0f7ff;
  border: 1px solid #bfdbfe;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
.edition-badge-btn:hover {
  background-color: #e0f2fe;
  border-color: #93c5fd;
  color: #1e40af;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.18);
}

.edition-modal-content {
  background-color: #ffffff;
  color: #1e293b;
  border: 1px solid rgba(0, 0, 0, 0.08);
}
.edition-modal-title {
  color: #0f172a;
}
.edition-modal-subtitle {
  color: #64748b;
}
.edition-modal-label {
  color: #334155;
}
.edition-header-avatar {
  background-color: rgba(245, 158, 11, 0.15);
  width: 32px;
  height: 32px;
}
.edition-preview-box {
  background-color: #f8fafc;
  border-color: #e2e8f0;
}
.edition-preview-surface {
  background-color: #ffffff;
  border: 1px dashed #cbd5e1;
}
.edition-chip {
  background-color: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #475569;
  font-weight: 600;
  font-size: 11px;
  padding: 2px 10px;
  transition: all 0.15s ease;
}
.edition-chip:hover {
  background-color: #e2e8f0;
  color: #1e293b;
}
.edition-chip.active {
  background-color: #3b82f6;
  border-color: #3b82f6;
  color: #ffffff;
  box-shadow: 0 2px 6px rgba(59, 130, 246, 0.35);
}
.edition-btn-cancel {
  background-color: #f8fafc;
  border-color: #e2e8f0;
  color: #64748b;
}
.edition-btn-cancel:hover {
  background-color: #f1f5f9;
  color: #1e293b;
}

</style>

<style>
/* ============================================================== */
/* BULLETPROOF ADMIN DARK MODE COMPREHENSIVE RULES                 */
/* ============================================================== */
[data-bs-theme="dark"] .edition-badge-btn {
  background: linear-gradient(135deg, rgba(30, 58, 138, 0.45) 0%, rgba(37, 99, 235, 0.25) 100%) !important;
  border: 1px solid rgba(96, 165, 250, 0.4) !important;
  color: #93c5fd !important;
  box-shadow: 0 0 14px rgba(59, 130, 246, 0.25) !important;
}
[data-bs-theme="dark"] .edition-badge-btn:hover {
  background: linear-gradient(135deg, rgba(30, 58, 138, 0.7) 0%, rgba(37, 99, 235, 0.45) 100%) !important;
  border-color: rgba(147, 197, 253, 0.65) !important;
  color: #bfdbfe !important;
  box-shadow: 0 0 22px rgba(59, 130, 246, 0.45) !important;
}

[data-bs-theme="dark"] .edition-modal-content {
  background-color: #1e2227 !important;
  color: #f1f5f9 !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7) !important;
}

[data-bs-theme="dark"] .edition-modal-title {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .edition-modal-subtitle {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .edition-modal-label {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .edition-header-avatar {
  background-color: rgba(245, 158, 11, 0.2) !important;
}

[data-bs-theme="dark"] .edition-preview-box {
  background-color: #14171a !important;
  border-color: rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .edition-preview-surface {
  background-color: #1a1d21 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
}

[data-bs-theme="dark"] .edition-chip {
  background-color: #292e34 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .edition-chip:hover {
  background-color: #343a40 !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .edition-chip.active {
  background-color: #3b82f6 !important;
  border-color: #60a5fa !important;
  color: #ffffff !important;
  box-shadow: 0 0 10px rgba(59, 130, 246, 0.5) !important;
}

[data-bs-theme="dark"] .edition-input {
  background-color: #14171a !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .edition-input:focus {
  background-color: #1a1e23 !important;
  border-color: #3b82f6 !important;
  color: #ffffff !important;
  box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.2) !important;
}

[data-bs-theme="dark"] .edition-btn-cancel {
  background-color: #292e34 !important;
  border-color: rgba(255, 255, 255, 0.12) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .edition-btn-cancel:hover {
  background-color: #343a40 !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .toast {
  background-color: #1e2227 !important;
  color: #f1f5f9 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .btn-close {
  filter: invert(1) grayscale(100%) brightness(200%);
}
[data-bs-theme="dark"] body,
[data-bs-theme="dark"] #layout-wrapper,
[data-bs-theme="dark"] .main-content,
[data-bs-theme="dark"] .page-content {
  background-color: #0b0f19 !important;
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] body::before,
[data-bs-theme="dark"] body::after {
  display: none !important;
}

[data-bs-theme="dark"] #page-topbar {
  background-color: #111827 !important;
  background: #111827 !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] #app-sidebar,
[data-bs-theme="dark"] .navbar-menu,
[data-bs-theme="dark"] .app-menu {
  background-color: #111827 !important;
  background: #111827 !important;
  border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .navbar-brand-box {
  background-color: #0f172a !important;
  background: #0f172a !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .navbar-menu .navbar-nav .nav-link {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .navbar-menu .navbar-nav .nav-link:hover,
[data-bs-theme="dark"] .navbar-menu .navbar-nav .nav-link.active {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .card,
[data-bs-theme="dark"] .card-body {
  background-color: #111827 !important;
  border: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .card-header,
[data-bs-theme="dark"] .card-footer {
  background-color: #162032 !important;
  border-color: rgba(255, 255, 255, 0.1) !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .bg-white {
  background-color: #111827 !important;
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .bg-light {
  background-color: #1e293b !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .text-dark {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .text-muted {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .text-secondary {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .border,
[data-bs-theme="dark"] .border-top,
[data-bs-theme="dark"] .border-bottom,
[data-bs-theme="dark"] .border-start,
[data-bs-theme="dark"] .border-end {
  border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-bs-theme="dark"] .footer {
  background-color: #111827 !important;
  border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .table {
  --bs-table-color: #f1f5f9;
  --bs-table-bg: #111827;
  --bs-table-border-color: rgba(255, 255, 255, 0.1);
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .table th,
[data-bs-theme="dark"] .table-light,
[data-bs-theme="dark"] .table-light th {
  background-color: #162032 !important;
  color: #f1f5f9 !important;
  font-weight: 700;
  border-bottom: 1.5px solid rgba(255, 255, 255, 0.18) !important;
}

[data-bs-theme="dark"] .table td {
  background-color: #111827 !important;
  color: #f8fafc !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

[data-bs-theme="dark"] .table tr:hover td {
  background-color: rgba(255, 255, 255, 0.05) !important;
}

[data-bs-theme="dark"] .form-control,
[data-bs-theme="dark"] .form-select {
  background-color: #0b1120 !important;
  border-color: rgba(255, 255, 255, 0.22) !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .form-control:focus,
[data-bs-theme="dark"] .form-select:focus {
  border-color: #f59e0b !important;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
  background-color: #080d19 !important;
}

[data-bs-theme="dark"] .progress {
  background-color: #1e293b !important;
}

[data-bs-theme="dark"] .btn-light {
  background-color: #1e293b !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
  color: #e2e8f0 !important;
}

[data-bs-theme="dark"] .btn-light:hover {
  background-color: #334155 !important;
  border-color: rgba(255, 255, 255, 0.25) !important;
  color: #ffffff !important;
}
</style>
