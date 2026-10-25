<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { initEcho } from '@/echo';
import VelzonConfirmModal from '@/Components/VelzonConfirmModal.vue';

const page = usePage();
const authUser = computed(() => page.props.auth?.user || { name: 'Panitia Semarak' });
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Active Panel Mode: 'pendaftaran' vs 'operasional'
const activeMode = ref('pendaftaran');
const isMobileSidebarOpen = ref(false);
const wsConnected = ref(false);

// Auto-detect mode based on current URL path
onMounted(() => {
  const currentPath = window.location.pathname;
  const savedMode = localStorage.getItem('semarak_panel_mode');

  if (
    currentPath.includes('/operasional') ||
    currentPath.includes('/tournament') ||
    currentPath.includes('/gate') ||
    currentPath.includes('/jury') ||
    currentPath.includes('/verification') ||
    currentPath.includes('/print-studio')
  ) {
    activeMode.value = 'operasional';
  } else if (savedMode) {
    activeMode.value = savedMode;
  }

  // Check Echo / Reverb connection state
  try {
    const echo = initEcho(page.props.reverb || {});
    if (echo && echo.connector && echo.connector.pusher) {
      echo.connector.pusher.connection.bind('connected', () => {
        wsConnected.value = true;
      });
      echo.connector.pusher.connection.bind('disconnected', () => {
        wsConnected.value = false;
      });
      if (echo.connector.pusher.connection.state === 'connected') {
        wsConnected.value = true;
      }
    }
  } catch (e) {
    console.warn('Reverb connection check:', e);
  }
});

function setMode(mode) {
  activeMode.value = mode;
  localStorage.setItem('semarak_panel_mode', mode);
  if (mode === 'operasional' && !window.location.pathname.startsWith('/operasional')) {
    router.visit('/operasional');
  } else if (mode === 'pendaftaran' && window.location.pathname.startsWith('/operasional')) {
    router.visit('/admin');
  }
}

function logout() {
  router.post('/logout', {}, {
    onFinish: () => {
      window.location.href = '/login';
    }
  });
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-amber-500 selection:text-black">
    <!-- Topbar Header -->
    <header class="h-16 border-b border-slate-800 bg-slate-900/90 backdrop-blur-md sticky top-0 z-40 flex items-center justify-between px-4 lg:px-6">
      <div class="flex items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button 
          @click="isMobileSidebarOpen = !isMobileSidebarOpen"
          class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none"
        >
          <i class="ri-menu-2-line text-xl"></i>
        </button>

        <!-- Brand Title -->
        <Link href="/admin" class="flex items-center gap-2.5 group">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-amber-500 via-amber-400 to-emerald-400 p-0.5 shadow-lg shadow-amber-500/10">
            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
              <span class="text-base font-black text-amber-400">XIV</span>
            </div>
          </div>
          <div>
            <div class="text-sm font-extrabold tracking-wider text-white group-hover:text-amber-400 transition-colors">
              SEMARAK ALIYAH
            </div>
            <div class="text-[10px] font-semibold text-amber-400/90 tracking-widest uppercase">
              MA Wahid Hasyim Balung
            </div>
          </div>
        </Link>
      </div>

      <!-- Center: Prominent Mode Switcher (Pendaftaran vs Operasional Hari-H) -->
      <div class="hidden md:flex items-center bg-slate-950/80 p-1 rounded-xl border border-slate-800/80 shadow-inner">
        <button 
          @click="setMode('pendaftaran')"
          :class="[
            'flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all duration-200',
            activeMode === 'pendaftaran' 
              ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' 
              : 'text-slate-400 hover:text-slate-200'
          ]"
        >
          <i class="ri-file-list-3-line text-sm"></i>
          <span>Mode Pendaftaran</span>
        </button>
        <button 
          @click="setMode('operasional')"
          :class="[
            'flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all duration-200',
            activeMode === 'operasional' 
              ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-extrabold shadow-md shadow-amber-500/20' 
              : 'text-slate-400 hover:text-slate-200'
          ]"
        >
          <i class="ri-flashlight-fill text-sm"></i>
          <span>Mode Operasional Hari-H</span>
        </button>
      </div>

      <!-- Right: Realtime status, Links, and User Profile -->
      <div class="flex items-center gap-3">
        <!-- Reverb WebSocket Live Beacon -->
        <div 
          class="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border"
          :class="wsConnected 
            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
            : 'bg-amber-500/10 text-amber-400 border-amber-500/20'"
          :title="wsConnected ? 'WebSocket Reverb Terkoneksi Real-time' : 'Reverb Menghubungkan...'"
        >
          <span class="w-2 h-2 rounded-full" :class="wsConnected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
          <span>{{ wsConnected ? 'Reverb Live' : 'Connecting' }}</span>
        </div>

        <!-- External Web Link -->
        <Link href="/" target="_blank" class="hidden sm:flex items-center gap-1 text-xs text-slate-400 hover:text-white px-2 py-1 rounded-lg hover:bg-slate-800 transition-colors">
          <i class="ri-global-line"></i>
          <span>Laman Publik</span>
        </Link>

        <!-- User Dropdown Profile -->
        <div class="flex items-center gap-2 border-l border-slate-800 pl-3">
          <div class="w-8 h-8 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-xs text-amber-400">
            {{ authUser.name ? authUser.name.charAt(0).toUpperCase() : 'P' }}
          </div>
          <span class="text-xs font-medium text-slate-300 hidden md:inline-block max-w-[120px] truncate">
            {{ authUser.name }}
          </span>
          <button @click="logout" title="Keluar Akun" class="text-slate-400 hover:text-red-400 p-1.5 rounded-lg hover:bg-slate-800 transition-colors">
            <i class="ri-logout-box-r-line text-base"></i>
          </button>
        </div>
      </div>
    </header>

    <div class="flex flex-1">
      <!-- Fixed Sidebar Navigation -->
      <aside 
        :class="[
          'w-64 bg-slate-900 border-r border-slate-800/80 flex flex-col fixed inset-y-0 left-0 z-30 pt-16 transition-transform duration-200 lg:translate-x-0 lg:static lg:pt-0',
          isMobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'
        ]"
      >
        <!-- Mobile Mode Switcher inside Drawer -->
        <div class="p-3 md:hidden border-b border-slate-800">
          <div class="flex flex-col gap-1.5 bg-slate-950 p-1 rounded-xl">
            <button 
              @click="setMode('pendaftaran')"
              :class="[
                'flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-bold',
                activeMode === 'pendaftaran' ? 'bg-blue-600 text-white' : 'text-slate-400'
              ]"
            >
              <i class="ri-file-list-3-line"></i> Pendaftaran
            </button>
            <button 
              @click="setMode('operasional')"
              :class="[
                'flex items-center justify-center gap-2 py-2 rounded-lg text-xs font-bold',
                activeMode === 'operasional' ? 'bg-amber-500 text-slate-950 font-black' : 'text-slate-400'
              ]"
            >
              <i class="ri-flashlight-fill"></i> Operasional Hari-H
            </button>
          </div>
        </div>

        <nav class="flex-1 overflow-y-auto p-3 space-y-4">
          <!-- 1. MENU MODE PENDAFTARAN -->
          <div v-show="activeMode === 'pendaftaran'" class="space-y-4">
            <!-- Grup: Menu Utama -->
            <div>
              <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">
                Menu Utama
              </div>
              <Link 
                href="/admin" 
                :class="[
                  'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                  $page.url === '/admin' ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                ]"
              >
                <i class="ri-dashboard-2-line text-base"></i>
                <span>Dashboard</span>
              </Link>
            </div>

            <!-- Grup: Administrasi Pendaftaran -->
            <div>
              <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">
                Administrasi Pendaftaran
              </div>
              <div class="space-y-1">
                <Link 
                  href="/admin/pendaftar" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/pendaftar') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-team-line text-base text-cyan-400"></i>
                  <span>Data Pendaftar Masuk</span>
                </Link>
                <Link 
                  href="/admin/lomba" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/lomba') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-medal-line text-base text-amber-400"></i>
                  <span>Manajemen Lomba</span>
                </Link>
                <Link 
                  href="/admin/tm" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/tm') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-calendar-todo-line text-base text-cyan-400"></i>
                  <span>Manajemen TM</span>
                </Link>
                <Link 
                  href="/admin/form-builder" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/form-builder') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-survey-line text-base text-orange-400"></i>
                  <span>Formulir Builder</span>
                </Link>
                <a 
                  href="/admin/export" 
                  class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-emerald-400 hover:bg-emerald-500/10 transition-all"
                >
                  <i class="ri-file-excel-2-line text-base"></i>
                  <span>Export Excel</span>
                </a>
                <a 
                  href="/assets/doc/JUKNIS-SEMARAK-2026.pdf" 
                  target="_blank" 
                  class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800/60 transition-all"
                >
                  <div class="flex items-center gap-3">
                    <i class="ri-file-pdf-line text-base text-rose-400"></i>
                    <span>Juknis Resmi PDF</span>
                  </div>
                  <i class="ri-external-link-line text-xs text-slate-500"></i>
                </a>
              </div>
            </div>

            <!-- Grup: Kelola Web Publik -->
            <div>
              <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">
                Kelola Web Publik
              </div>
              <div class="space-y-1">
                <Link 
                  href="/admin/sponsor" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/sponsor') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-shake-hands-line text-base text-indigo-400"></i>
                  <span>Mitra & Sponsorship</span>
                </Link>
                <Link 
                  href="/admin/galeri" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/galeri') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-gallery-fill text-base text-amber-400"></i>
                  <span>Galeri Dokumentasi</span>
                </Link>
                <Link 
                  href="/admin/kustomisasi" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.startsWith('/admin/kustomisasi') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-magic-line text-base text-purple-400"></i>
                  <span>Kustomisasi Lanjutan</span>
                </Link>
              </div>
            </div>

            <!-- Grup: Pengaturan Sistem -->
            <div>
              <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">
                Pengaturan Sistem
              </div>
              <div class="space-y-1">
                <Link 
                  href="/admin/pengaturan/profil-sekolah" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.includes('/profil-sekolah') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-building-line text-base text-slate-400"></i>
                  <span>Profil Sekolah</span>
                </Link>
                <Link 
                  href="/admin/pengaturan/mode-perawatan" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.includes('/mode-perawatan') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-tools-line text-base text-slate-400"></i>
                  <span>Mode Perawatan</span>
                </Link>
                <Link 
                  href="/admin/pengaturan/seo-og" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.includes('/seo-og') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-search-eye-line text-base text-slate-400"></i>
                  <span>SEO & Open Graph</span>
                </Link>
                <Link 
                  href="/admin/pengaturan/wa-gateway" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.includes('/wa-gateway') ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-whatsapp-line text-base text-emerald-400"></i>
                  <span>WA Gateway</span>
                </Link>
                <Link 
                  href="/admin/pengaturan/login-admin" 
                  :class="[
                    'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                    $page.url.includes('/login-admin') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                  ]"
                >
                  <i class="ri-image-edit-line text-base text-blue-400"></i>
                  <span>Login Panel Admin</span>
                </Link>
              </div>
            </div>
          </div>

          <!-- 2. MENU MODE OPERASIONAL HARI-H -->
          <div v-show="activeMode === 'operasional'" class="space-y-1">
            <div class="px-3 text-[10px] font-bold uppercase tracking-widest text-amber-500/80 mb-2 flex items-center justify-between">
              <span>Operasional Lapangan</span>
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
            </div>

            <!-- Gate Checkin -->
            <Link 
              href="/admin/gate-checkin" 
              :class="[
                'flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                $page.url.startsWith('/admin/gate') ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
              ]"
            >
              <div class="flex items-center gap-3">
                <i class="ri-qr-scan-2-line text-base text-emerald-400"></i>
                <span>Gate Check-in Barcode</span>
              </div>
              <span class="px-1.5 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-300 font-bold">Live</span>
            </Link>

            <!-- File Verification -->
            <Link 
              href="/admin/verification" 
              :class="[
                'flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                $page.url.startsWith('/admin/verification') ? 'bg-blue-600/20 text-blue-400 border border-blue-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
              ]"
            >
              <div class="flex items-center gap-3">
                <i class="ri-file-search-line text-base text-blue-400"></i>
                <span>Review & Verifikasi Berkas</span>
              </div>
            </Link>

            <!-- Tournament Knockout Arenas -->
            <div class="pt-2">
              <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                Arena Turnamen Gugur
              </div>
              <Link 
                href="/operasional?tab=turnamen&comp=volleyball-putra" 
                :class="[
                  'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                  $page.url.includes('/volleyball-putra') ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                ]"
              >
                <i class="ri-football-line text-amber-400 text-sm"></i>
                <span>Voli Putra (Wasit & Bagan)</span>
              </Link>
              <Link 
                href="/operasional?tab=turnamen&comp=volleyball-putri" 
                :class="[
                  'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                  $page.url.includes('/volleyball-putri') ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                ]"
              >
                <i class="ri-football-line text-amber-400 text-sm"></i>
                <span>Voli Putri (Wasit & Bagan)</span>
              </Link>
              <Link 
                href="/operasional?tab=turnamen&comp=mobile-legends" 
                :class="[
                  'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                  $page.url.includes('/mobile-legends') ? 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                ]"
              >
                <i class="ri-gamepad-line text-indigo-400 text-sm"></i>
                <span>Mobile Legends (MLBB)</span>
              </Link>
            </div>

            <!-- Digital Jury Hub -->
            <div class="pt-2">
              <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                Penjurian Digital
              </div>
              <Link 
                href="/admin/jury" 
                :class="[
                  'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                  $page.url === '/admin/jury' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
                ]"
              >
                <i class="ri-scales-3-line text-base text-amber-400"></i>
                <span>Hub Semua Arena Juri</span>
              </Link>
            </div>

            <!-- Live Stage Scoreboard -->
            <Link 
              href="/portal/live-scoreboard" 
              target="_blank"
              class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-cyan-400 hover:bg-cyan-500/10 transition-all pt-2"
            >
              <div class="flex items-center gap-3">
                <i class="ri-broadcast-line text-base"></i>
                <span>Scoreboard Layar Panggung</span>
              </div>
              <i class="ri-external-link-line text-xs"></i>
            </Link>

            <!-- Print Studio -->
            <Link 
              href="/admin/print-studio" 
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition-all',
                $page.url.startsWith('/admin/print-studio') ? 'bg-purple-600/20 text-purple-400 border border-purple-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'
              ]"
            >
              <i class="ri-printer-line text-base text-purple-400"></i>
              <span>Print Studio ID Card</span>
            </Link>
          </div>
        </nav>

        <!-- Footer / Version info -->
        <div class="p-3 border-t border-slate-800/80 text-[11px] text-slate-500 flex items-center justify-between">
          <span>Laravel 13 + Vue 3</span>
          <span class="font-mono text-amber-500/70">v14.0</span>
        </div>
      </aside>

      <!-- Main Workspace Container -->
      <main class="flex-1 min-w-0 p-4 lg:p-6 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Toast Alerts for Success / Error -->
        <div v-if="flashSuccess" class="mb-4 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-medium flex items-center gap-2.5 shadow-sm">
          <i class="ri-checkbox-circle-fill text-emerald-400 text-base"></i>
          <span>{{ flashSuccess }}</span>
        </div>

        <div v-if="flashError" class="mb-4 p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-medium flex items-center gap-2.5 shadow-sm">
          <i class="ri-error-warning-fill text-rose-400 text-base"></i>
          <span>{{ flashError }}</span>
        </div>

        <slot />
      </main>
    </div>
    <!-- Global Velzon Confirmation Modal -->
    <VelzonConfirmModal />
  </div>
</template>
