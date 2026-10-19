<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';

const page = usePage();
const authUser = computed(() => page.props.auth?.user || { name: 'Panitia Semarak', email: 'admin@maswahas.sch.id' });
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const currentUrl = computed(() => page.url || window.location.pathname);
const school = computed(() => page.props.schoolProfile || {});
const eventInfo = computed(() => {
  const name = (school.value.event_name || 'SEMARAK ALIYAH XIV 2026').trim();
  const match = name.match(/^(.*?)\s+([IVXLCDM]+|\d+)(?:\s+(\d{4}))?$/i);
  if (match) {
    return {
      base: match[1],
      edition: match[2].toUpperCase(),
      year: match[3] || '2026',
      full: name
    };
  }
  return { base: name, edition: 'XIV', year: '2026', full: name };
});

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
  router.post('/logout');
}

onMounted(() => {
  const savedTheme = localStorage.getItem('velzon_theme');
  if (savedTheme === 'dark') {
    isDarkTheme.value = true;
    document.documentElement.setAttribute('data-bs-theme', 'dark');
  } else {
    document.documentElement.setAttribute('data-bs-theme', 'light');
  }
});
</script>

<template>
  <div 
    id="layout-wrapper" 
    :class="{ 'sidebar-enable': isMobileSidebarOpen, 'vertical-collpsed': isSidebarCollapsed }"
    class="min-h-screen flex flex-col font-sans"
    style="background-color: var(--vz-body-bg, #f3f3f9);"
  >
    <!-- ============================================================== -->
    <!-- TOPBAR HEADER (Velzon Official Header)                         -->
    <!-- ============================================================== -->
    <header id="page-topbar" :style="{ left: isSidebarCollapsed ? '70px' : '' }">
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
            <span class="badge bg-primary-subtle text-primary fs-12 px-2 py-1">
              <i class="ri-flashlight-line align-middle me-1"></i> Edisi {{ eventInfo.edition }} • {{ eventInfo.year }}
            </span>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
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
      :style="{ width: isSidebarCollapsed ? '70px' : '' }"
    >
      <!-- Brand Logo Box -->
      <div class="navbar-brand-box">
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
    </main>
  </div>
</template>
