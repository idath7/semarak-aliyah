<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const page = usePage();
const school = computed(() => page.props.schoolProfile || {});
const cleanWaNumber = computed(() => (school.value.contact_wa || '083850070767').replace(/[^0-9]/g, ''));

const eventInfo = computed(() => {
  const name = (school.value.event_name || 'SEMARAK ALIYAH XIV 2026').trim();
  const match = name.match(/^(.*?)\s+([IVXLCDM]+|\d+)(?:\s+(\d{4}))?$/i);
  if (match) {
    return {
      base: match[1],
      edition: match[2].toUpperCase(),
      year: match[3] || '',
      full: name
    };
  }
  return {
    base: name,
    edition: '',
    year: '',
    full: name
  };
});

const kustomisasi = computed(() => page.props.kustomisasiWeb || {});
const isBannerDismissed = ref(false);
const isPopupOpen = ref(false);

const customWaNumber = computed(() => {
  const num = kustomisasi.value.floating_wa_number || school.value.contact_wa || '083850070767';
  return num.replace(/[^0-9]/g, '');
});

const customWaMessage = computed(() => {
  return kustomisasi.value.floating_wa_message || `Halo Admin ${school.value.event_name || 'Semarak Aliyah'}, saya ingin bertanya seputar pendaftaran`;
});

function dismissBanner() {
  isBannerDismissed.value = true;
  try {
    sessionStorage.setItem('semarak_banner_dismissed', '1');
  } catch(e) {}
}

function closePopup() {
  isPopupOpen.value = false;
  try {
    sessionStorage.setItem('semarak_popup_seen', '1');
  } catch(e) {}
}

const mobileMenuOpen = ref(false);
const isScrolled = ref(false);
const showBackToTop = ref(false);
const scrollProgress = ref(0);

const { isDark, initTheme, toggleTheme } = useTheme();

function closeMobileMenu() {
  mobileMenuOpen.value = false;
}

// Circumference for r=18: 2 * PI * 18 = ~113.097
const CIRCLE_CIRCUMFERENCE = 2 * Math.PI * 18;

const progressOffset = computed(() => {
  const percent = Math.min(100, Math.max(0, scrollProgress.value));
  return CIRCLE_CIRCUMFERENCE - (percent / 100) * CIRCLE_CIRCUMFERENCE;
});

function handleScroll() {
  const scrollTop = window.scrollY || document.documentElement.scrollTop || 0;
  const docHeight = (document.documentElement.scrollHeight || document.body.scrollHeight) - window.innerHeight;

  isScrolled.value = scrollTop > 40;
  showBackToTop.value = scrollTop > 320;

  if (docHeight > 0) {
    scrollProgress.value = Math.min(100, Math.max(0, (scrollTop / docHeight) * 100));
  } else {
    scrollProgress.value = 0;
  }
}

function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
}

onMounted(() => {
  initTheme();
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();

  try {
    if (sessionStorage.getItem('semarak_banner_dismissed') === '1') {
      isBannerDismissed.value = true;
    }

    if (kustomisasi.value.popup_active) {
      const hasSeen = sessionStorage.getItem('semarak_popup_seen');
      if (kustomisasi.value.popup_frequency === 'always' || !hasSeen) {
        setTimeout(() => {
          isPopupOpen.value = true;
        }, 800);
      }
    }
  } catch(e) {}
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <div class="public-website-root">
    <Head>
      <title>{{ school.event_name ? `${school.event_name} - ${school.school_name || 'MAS Wahid Hasyim'}` : 'Semarak Aliyah XIV 2026' }}</title>
    </Head>

    <!-- ==========================================================================
         TOP FLOATING ANNOUNCEMENT BANNER
         ========================================================================== -->
    <aside 
      v-if="kustomisasi.banner_active && !isBannerDismissed && kustomisasi.banner_position === 'top'"
      class="announcement-banner-ribbon position-top"
      :class="'theme-' + (kustomisasi.banner_type || 'gold')"
      aria-label="Pemberitahuan Resmi"
    >
      <div class="container announcement-banner-inner">
        <div class="announcement-content">
          <span class="announcement-text">{{ kustomisasi.banner_text }}</span>
          <a 
            v-if="kustomisasi.banner_btn_text" 
            :href="kustomisasi.banner_btn_url || '/#biaya'" 
            class="announcement-cta-btn"
          >
            <span>{{ kustomisasi.banner_btn_text }}</span>
            <i class="ri-arrow-right-line"></i>
          </a>
        </div>
        <button 
          v-if="kustomisasi.banner_dismissable" 
          type="button" 
          class="announcement-close-btn"
          @click="dismissBanner"
          aria-label="Tutup Pengumuman"
        >
          <i class="ri-close-line"></i>
        </button>
      </div>
    </aside>

    <!-- ==========================================================================
         TOP BAR (Clean Multi-Color Contact Ribbon)
         ========================================================================== -->
    <aside class="top-bar" aria-label="Informasi Kontak Cepat">
      <div class="container top-bar-container">
        <div class="top-bar-left">
          <div class="top-bar-item">
            <span class="top-bar-icon-wrap icon-orange">
              <i class="ri-map-pin-2-fill"></i>
            </span>
            <span>{{ school.school_address || 'Jl. Puger No. 20 Balung, Jember, Jatim 68161' }}</span>
          </div>
          <div class="top-bar-item">
            <span class="top-bar-icon-wrap icon-green">
              <i class="ri-time-fill"></i>
            </span>
            <span>{{ school.service_hours || 'Senin - Sabtu : 07.00 - 13.00 WIB' }}</span>
          </div>
        </div>

        <div class="top-bar-right">
          <div class="top-bar-item">
            <span class="top-bar-icon-wrap icon-purple">
              <i class="ri-mail-fill"></i>
            </span>
            <a :href="'mailto:' + (school.contact_email || 'semarak.aliyah@gmail.com')">{{ school.contact_email || 'semarak.aliyah@gmail.com' }}</a>
          </div>
          <div class="top-bar-item">
            <span class="top-bar-icon-wrap icon-pink">
              <i class="ri-whatsapp-fill"></i>
            </span>
            <a :href="'https://wa.me/' + cleanWaNumber" target="_blank" rel="noopener">{{ school.contact_wa || '0838 5007 0767' }}</a>
          </div>
        </div>
      </div>
    </aside>

    <!-- ==========================================================================
         MAIN NAVIGATION HEADER
         ========================================================================== -->
    <header class="main-header" :class="{ 'scrolled': isScrolled }" id="main-header">
      <div class="container">
        <div class="nav-wrapper">
          <Link href="/" class="brand-logo" aria-label="Beranda Semarak Aliyah">
            <div class="brand-logo-badge">
              <img src="/assets/img/logo.png" alt="Logo Semarak Aliyah 2026">
            </div>
            <div class="brand-info">
              <span class="brand-title">
                {{ eventInfo.base }}
                <span v-if="eventInfo.edition" class="edition-badge">{{ eventInfo.edition }}</span>
              </span>
              <span class="brand-subtitle">{{ school.school_short_name || 'MA Wahid Hasyim Balung' }}</span>
            </div>
          </Link>

          <!-- Desktop Menu -->
          <nav class="nav-menu" aria-label="Menu Utama">
            <Link href="/" class="nav-link">Beranda</Link>
            <a href="/#tentang" class="nav-link">Tentang</a>
            <a href="/#biaya" class="nav-link">Biaya & Paket</a>
            <a href="/#kompetisi" class="nav-link">Kompetisi</a>
            <a href="/#jadwal" class="nav-link">Jadwal & TM</a>
            <a href="/assets/JUKNIS-SEMARAK-2026.pdf" download class="nav-link">Juknis</a>
            <a href="/#kontak" class="nav-link">Kontak</a>
            <Link href="/scoreboard" class="nav-link nav-link-live" aria-label="Live Scoreboard">
              <span class="live-dot-pulse"></span>
              <span>Live Score</span>
            </Link>
          </nav>

          <div class="nav-actions">
            <!-- Dark / Light Mode Toggle Button -->
            <button 
              type="button" 
              class="btn-theme-nav" 
              @click="toggleTheme" 
              :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
              aria-label="Toggle Mode Terang/Gelap"
            >
              <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line'"></i>
            </button>

            <Link href="/layanan-registrasi" class="btn-header-cta">
              <i class="ri-flashlight-fill"></i>
              <span class="btn-cta-text-full">Layanan Registrasi</span>
              <span class="btn-cta-text-short">Daftar</span>
            </Link>
            <button 
              type="button" 
              class="mobile-toggle" 
              :class="{ active: mobileMenuOpen }"
              @click="mobileMenuOpen = !mobileMenuOpen" 
              aria-label="Buka Menu Mobile"
            >
              <span></span>
              <span></span>
              <span></span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Drawer Backdrop -->
    <div 
      v-if="mobileMenuOpen" 
      class="mobile-drawer-backdrop" 
      @click="closeMobileMenu"
    ></div>

    <!-- Mobile Drawer Menu -->
    <div class="mobile-drawer" :class="{ open: mobileMenuOpen }" id="mobile-drawer">
      <div class="mobile-drawer-head">
        <div class="drawer-brand">
          <div class="brand-logo-badge" style="width: 36px; height: 36px;">
            <img src="/assets/img/logo.png" alt="Logo" style="height: 24px;">
          </div>
          <div>
            <div class="drawer-title">
              {{ eventInfo.base }}
              <span v-if="eventInfo.edition" class="edition-badge">{{ eventInfo.edition }}</span>
            </div>
            <div class="drawer-sub">{{ school.school_short_name || 'MA Wahid Hasyim Balung' }}</div>
          </div>
        </div>
        <button type="button" class="drawer-close-btn" @click="closeMobileMenu" aria-label="Tutup Menu">
          <i class="ri-close-line"></i>
        </button>
      </div>

      <nav class="drawer-nav">
        <Link href="/" class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-purple"><i class="ri-home-4-line"></i></span>
          <span>Beranda</span>
        </Link>
        <a href="/#tentang" class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-orange"><i class="ri-information-line"></i></span>
          <span>Tentang Semarak</span>
        </a>
        <a href="/#biaya" class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-green"><i class="ri-money-dollar-circle-line"></i></span>
          <span>Biaya & Paket Registrasi</span>
        </a>
        <a href="/#kompetisi" class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-pink"><i class="ri-trophy-line"></i></span>
          <span>Pilihan Kompetisi</span>
        </a>
        <a href="/#jadwal" class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-orange"><i class="ri-calendar-event-line"></i></span>
          <span>Jadwal & Technical Meeting</span>
        </a>
        <a href="/assets/JUKNIS-SEMARAK-2026.pdf" download class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-purple"><i class="ri-file-download-line"></i></span>
          <span>Unduh Juknis & Pamflet</span>
        </a>
        <a href="/#kontak" class="drawer-link" @click="closeMobileMenu">
          <span class="drawer-icon-wrap icon-pink"><i class="ri-map-pin-line"></i></span>
          <span>Lokasi & Kontak Panitia</span>
        </a>
        <Link href="/scoreboard" class="drawer-link drawer-link-live" @click="closeMobileMenu">
          <span class="drawer-icon-wrap" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;"><i class="ri-broadcast-line"></i></span>
          <span style="flex-grow: 1;">Live Scoreboard</span>
          <span class="live-tag">LIVE</span>
        </Link>

        <!-- Theme Toggle in Drawer -->
        <div class="drawer-theme-toggle" @click="toggleTheme">
          <span class="drawer-icon-wrap" :class="isDark ? 'icon-orange' : 'icon-purple'">
            <i :class="isDark ? 'ri-sun-line text-warning' : 'ri-moon-line'"></i>
          </span>
          <span style="flex-grow: 1;">Tema Tampilan</span>
          <span class="theme-status-badge">{{ isDark ? 'Mode Gelap' : 'Mode Terang' }}</span>
        </div>
      </nav>

      <div class="drawer-actions">
        <Link href="/layanan-registrasi" class="btn-primary drawer-btn-cta" @click="closeMobileMenu">
          <i class="ri-flashlight-fill"></i>
          <span>Layanan Registrasi Online</span>
        </Link>
        <a :href="($page.props.portal?.adminUrl || 'http://localhost:8001') + '/login'" class="btn-secondary drawer-btn-panitia" @click="closeMobileMenu">
          <i class="ri-lock-2-line"></i>
          <span>Masuk Panel Panitia</span>
        </a>
      </div>
    </div>

    <!-- MAIN SLOT -->
    <main>
      <slot />
    </main>

    <!-- ==========================================================================
         FOOTER
         ========================================================================== -->
    <footer class="main-footer">
      <div class="container">
        <div class="footer-grid">
          <!-- Brand / About Column -->
          <div class="footer-brand">
            <div class="footer-brand-header">
              <img src="/assets/img/logo.png" alt="Logo Semarak Aliyah" class="footer-logo-img">
              <div>
                <div class="footer-brand-title">
                  {{ eventInfo.base }}
                  <span v-if="eventInfo.edition" class="edition-badge" style="font-size: 0.65rem; padding: 2px 7px;">{{ eventInfo.edition }}</span>
                </div>
                <div class="footer-brand-subtitle">
                  {{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }}
                </div>
              </div>
            </div>

            <p class="footer-desc">
              <strong>{{ school.event_name || 'Semarak Aliyah XIV 2026' }}</strong> adalah agenda kompetisi tahunan yang diinisiasi oleh <strong>{{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }}</strong>, wadah berprestasi bagi siswa/i SMP/MTs sederajat se-Kabupaten Jember.
            </p>

            <div class="footer-social-wrap">
              <a v-if="school.social_facebook" :href="school.social_facebook" target="_blank" rel="noopener" class="footer-social-btn" title="Facebook"><i class="ri-facebook-fill"></i></a>
              <a v-if="school.social_instagram" :href="school.social_instagram" target="_blank" rel="noopener" class="footer-social-btn" title="Instagram"><i class="ri-instagram-line"></i></a>
              <a v-if="school.social_youtube" :href="school.social_youtube" target="_blank" rel="noopener" class="footer-social-btn" title="YouTube"><i class="ri-youtube-fill"></i></a>
              <a v-if="school.social_tiktok" :href="school.social_tiktok" target="_blank" rel="noopener" class="footer-social-btn" title="TikTok"><i class="ri-tiktok-fill"></i></a>
              <a v-if="school.school_website" :href="school.school_website" target="_blank" rel="noopener" class="footer-social-btn" title="Website Resmi"><i class="ri-global-line"></i></a>
            </div>
          </div>

          <!-- Navigation & Cabang Lomba Links Group (Side-by-side on Mobile) -->
          <div class="footer-links-group">
            <div class="footer-col-nav">
              <h4 class="footer-heading">Navigasi</h4>
              <ul class="footer-links">
                <li><Link href="/"><i class="ri-arrow-right-s-line"></i> Beranda</Link></li>
                <li><a href="/#tentang"><i class="ri-arrow-right-s-line"></i> Tentang Acara</a></li>
                <li><a href="/#biaya"><i class="ri-arrow-right-s-line"></i> Biaya Registrasi</a></li>
                <li><a href="/#kompetisi"><i class="ri-arrow-right-s-line"></i> Daftar Kompetisi</a></li>
                <li><a href="/#jadwal"><i class="ri-arrow-right-s-line"></i> Jadwal & TM</a></li>
                <li><a href="/#sponsor"><i class="ri-arrow-right-s-line"></i> Mitra & Sponsor</a></li>
                <li><a href="/#galeri"><i class="ri-arrow-right-s-line"></i> Galeri Dokumentasi</a></li>
                <li><Link href="/scoreboard"><i class="ri-arrow-right-s-line"></i> Live Scoreboard</Link></li>
              </ul>
            </div>

            <div class="footer-col-lomba">
              <h4 class="footer-heading">Cabang Lomba</h4>
              <ul class="footer-links">
                <li><Link href="/layanan-registrasi?lomba=mipa"><i class="ri-arrow-right-s-line"></i> Olimpiade MIPA</Link></li>
                <li><Link href="/layanan-registrasi?lomba=ips"><i class="ri-arrow-right-s-line"></i> Olimpiade IPS</Link></li>
                <li><Link href="/layanan-registrasi?lomba=storytelling"><i class="ri-arrow-right-s-line"></i> Storytelling</Link></li>
                <li><Link href="/layanan-registrasi?lomba=albanjari"><i class="ri-arrow-right-s-line"></i> Festival Albanjari</Link></li>
                <li><Link href="/layanan-registrasi?lomba=singer"><i class="ri-arrow-right-s-line"></i> Singer Lagu Islami</Link></li>
                <li><Link href="/layanan-registrasi?lomba=mobile-legends"><i class="ri-arrow-right-s-line"></i> Mobile Legends</Link></li>
              </ul>
            </div>
          </div>

          <!-- Sekretariat Column -->
          <div>
            <h4 class="footer-heading">Sekretariat</h4>
            <div class="footer-contact-block">
              <div class="footer-address">
                <i class="ri-map-pin-2-fill"></i>
                <span>Kampus {{ school.school_short_name || 'MA Unggulan Wahid Hasyim' }}<br>{{ school.school_address || 'Jl. Puger No. 20 Balung, Jember, Jawa Timur 68161' }}</span>
              </div>
              <div class="footer-contact-item">
                <i class="ri-whatsapp-fill" style="color: #22c55e;"></i>
                <span>WhatsApp: <a :href="'https://wa.me/' + cleanWaNumber" target="_blank" rel="noopener">{{ school.contact_wa || '0838 5007 0767' }}</a></span>
              </div>
              <div class="footer-contact-item">
                <i class="ri-mail-fill" style="color: #a78bfa;"></i>
                <span>Email: <a :href="'mailto:' + (school.contact_email || 'semarak.aliyah@gmail.com')">{{ school.contact_email || 'semarak.aliyah@gmail.com' }}</a></span>
              </div>
            </div>
          </div>
        </div>

        <div class="footer-bottom">
          <div>
            Copyright © {{ eventInfo.year || '2026' }} <strong>{{ school.event_name || 'SEMARAK ALIYAH' }}</strong> • {{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }}. All Rights Reserved.
          </div>
          <div>
            <span class="footer-tech-tag">maswahasLab Technology</span>
          </div>
        </div>
      </div>
    </footer>

    <!-- ==========================================================================
         FLOATING ANNOUNCEMENT BANNER (BOTTOM POSITION)
         ========================================================================== -->
    <aside 
      v-if="kustomisasi.banner_active && !isBannerDismissed && kustomisasi.banner_position === 'bottom'"
      class="announcement-banner-ribbon position-bottom"
      :class="'theme-' + (kustomisasi.banner_type || 'gold')"
      aria-label="Pemberitahuan Mengambang Bawah"
    >
      <div class="container announcement-banner-inner">
        <div class="announcement-content">
          <span class="announcement-text">{{ kustomisasi.banner_text }}</span>
          <a 
            v-if="kustomisasi.banner_btn_text" 
            :href="kustomisasi.banner_btn_url || '/#biaya'" 
            class="announcement-cta-btn"
          >
            <span>{{ kustomisasi.banner_btn_text }}</span>
            <i class="ri-arrow-right-line"></i>
          </a>
        </div>
        <button 
          v-if="kustomisasi.banner_dismissable" 
          type="button" 
          class="announcement-close-btn"
          @click="dismissBanner"
          aria-label="Tutup Pengumuman"
        >
          <i class="ri-close-line"></i>
        </button>
      </div>
    </aside>

    <!-- ==========================================================================
         ENTRANCE MODAL POP-UP
         ========================================================================== -->
    <div 
      v-if="isPopupOpen" 
      class="popup-modal-backdrop"
      @click.self="closePopup"
      role="dialog"
      aria-modal="true"
    >
      <div class="popup-modal-card">
        <button type="button" class="popup-modal-close" @click="closePopup" aria-label="Tutup Pop-up">&times;</button>
        <div v-if="kustomisasi.popup_image" class="popup-modal-image-wrap">
          <img :src="kustomisasi.popup_image" alt="Poster Pengumuman" class="popup-modal-img" />
        </div>
        <div class="popup-modal-body">
          <span v-if="kustomisasi.popup_badge" class="popup-modal-badge">{{ kustomisasi.popup_badge }}</span>
          <h3 class="popup-modal-title">{{ kustomisasi.popup_title }}</h3>
          <p class="popup-modal-desc">{{ kustomisasi.popup_content }}</p>
          <div class="popup-modal-actions">
            <button type="button" class="btn-popup-secondary" @click="closePopup">Tutup</button>
            <a 
              v-if="kustomisasi.popup_btn_text" 
              :href="kustomisasi.popup_btn_url || '#'" 
              class="btn-popup-primary"
              target="_blank"
              rel="noopener"
              @click="closePopup"
            >
              {{ kustomisasi.popup_btn_text }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Back To Top Button with Circular Scroll Progress -->
    <button 
      v-if="kustomisasi.back_to_top_active !== false"
      type="button" 
      class="back-to-top" 
      :class="{ 'show': showBackToTop }" 
      @click="scrollToTop" 
      title="Kembali ke atas"
      aria-label="Kembali ke atas"
    >
      <svg class="back-to-top-progress" viewBox="0 0 44 44" aria-hidden="true">
        <circle class="progress-bg" cx="22" cy="22" r="18" />
        <circle 
          class="progress-bar" 
          cx="22" 
          cy="22" 
          r="18" 
          :style="{ strokeDashoffset: progressOffset }"
        />
      </svg>
      <i class="ri-arrow-up-line back-to-top-icon"></i>
      <span class="back-to-top-tooltip">Kembali ke Atas</span>
    </button>

    <!-- WhatsApp Floating Button -->
    <a 
      v-if="kustomisasi.floating_wa_active !== false"
      :href="'https://wa.me/' + customWaNumber + '?text=' + encodeURIComponent(customWaMessage)" 
      class="floating-wa-btn" 
      :class="[
        kustomisasi.floating_wa_position === 'bottom-left' ? 'pos-left' : 'pos-right',
        kustomisasi.floating_wa_animation === 'pulse' ? 'anim-pulse' :
        kustomisasi.floating_wa_animation === 'bounce' ? 'anim-bounce' : '',
        kustomisasi.floating_wa_label ? 'has-label' : 'icon-only'
      ]"
      target="_blank" 
      rel="noopener" 
      aria-label="Hubungi WhatsApp Panitia"
      :title="kustomisasi.floating_wa_label || 'Chat WhatsApp Panitia'"
    >
      <i class="ri-whatsapp-fill"></i>
      <span v-if="kustomisasi.floating_wa_label" class="floating-wa-label-chip">{{ kustomisasi.floating_wa_label }}</span>
    </a>
  </div>
</template>

<style scoped>
/* Floating Announcement Ribbon */
.announcement-banner-ribbon {
  width: 100%;
  padding: 8px 16px;
  z-index: 1040;
  font-size: 0.85rem;
  line-height: 1.4;
  transition: all 0.3s ease;
}

.announcement-banner-ribbon.position-top {
  position: relative;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.announcement-banner-ribbon.position-bottom {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.25);
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.announcement-banner-ribbon.theme-gold {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 60%, #b45309 100%);
  color: #ffffff;
}

.announcement-banner-ribbon.theme-primary {
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
  color: #ffffff;
}

.announcement-banner-ribbon.theme-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: #ffffff;
}

.announcement-banner-ribbon.theme-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: #ffffff;
}

.announcement-banner-ribbon.theme-info {
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
  color: #ffffff;
}

.announcement-banner-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.announcement-content {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
  flex: 1;
  text-align: center;
}

.announcement-text {
  font-weight: 600;
  letter-spacing: 0.2px;
}

.announcement-cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 12px;
  background: rgba(255, 255, 255, 0.95);
  color: #111827 !important;
  font-weight: 700;
  font-size: 0.78rem;
  border-radius: 9999px;
  text-decoration: none !important;
  transition: all 0.2s ease;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  white-space: nowrap;
}

.announcement-cta-btn:hover {
  background: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

.announcement-close-btn {
  background: transparent;
  border: none;
  color: inherit;
  font-size: 1.1rem;
  cursor: pointer;
  opacity: 0.8;
  padding: 0 4px;
  line-height: 1;
  transition: opacity 0.2s;
}

.announcement-close-btn:hover {
  opacity: 1;
}

/* Entrance Modal Pop-up */
.popup-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(11, 15, 25, 0.75);
  backdrop-filter: blur(8px);
  z-index: 1099;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

.popup-modal-card {
  position: relative;
  background: var(--bg-surface-elevated, #ffffff);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  max-width: 520px;
  width: 100%;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
  animation: scaleUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.popup-modal-close {
  position: absolute;
  top: 12px;
  right: 14px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  color: #ffffff;
  border: none;
  font-size: 1.4rem;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 10;
  transition: all 0.2s;
}

.popup-modal-close:hover {
  background: rgba(0, 0, 0, 0.8);
  transform: rotate(90deg);
}

.popup-modal-image-wrap {
  width: 100%;
  max-height: 220px;
  overflow: hidden;
  background: #0f172a;
}

.popup-modal-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.popup-modal-body {
  padding: 24px;
}

.popup-modal-badge {
  display: inline-block;
  padding: 4px 10px;
  background: rgba(245, 158, 11, 0.15);
  color: #f59e0b;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-radius: 6px;
  margin-bottom: 10px;
}

.popup-modal-title {
  font-family: var(--font-heading, inherit);
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--text-main, #111827);
  margin-bottom: 10px;
  line-height: 1.35;
}

.popup-modal-desc {
  font-size: 0.9rem;
  color: var(--text-secondary, #6b7280);
  line-height: 1.6;
  margin-bottom: 20px;
  white-space: pre-line;
}

.popup-modal-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.btn-popup-secondary {
  padding: 8px 16px;
  background: transparent;
  border: 1px solid rgba(156, 163, 175, 0.3);
  color: var(--text-secondary, #6b7280);
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
}

.btn-popup-primary {
  padding: 8px 20px;
  background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
  color: #ffffff !important;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  text-decoration: none !important;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
  transition: all 0.2s;
}

.btn-popup-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(124, 58, 237, 0.4);
}

/* Floating WA Positioning & Animations */
.floating-wa-btn.pos-left {
  left: 24px !important;
  right: auto !important;
}

.floating-wa-label-chip {
  font-size: 0.78rem;
  font-weight: 700;
  padding-right: 4px;
}

.anim-pulse {
  animation: waPulse 2s infinite;
}

@keyframes waPulse {
  0% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
  }
  70% {
    box-shadow: 0 0 0 16px rgba(34, 197, 94, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
  }
}

.anim-bounce {
  animation: waBounce 2.5s infinite;
}

@keyframes waBounce {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-8px);
  }
  60% {
    transform: translateY(-4px);
  }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes scaleUp {
  from { transform: scale(0.92); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
</style>

