<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { formatWaNumber, getWaChatUrl } from '@/Utils/whatsapp';

const page = usePage();
const school = computed(() => page.props.schoolProfile || {});
const cleanSchoolWa = computed(() => formatWaNumber(school.value.contact_wa, '6283850070767'));

const ctaRegisterUrl = computed(() => {
  const isRequired = page.props.registrationLoginRequired;
  const user = page.props.auth?.user;
  if (isRequired && !user) {
    return '/pendaftar/login?redirect_to=register&notice=required';
  }
  return '/layanan-registrasi';
});

function getCompRegisterUrl(compId) {
  const isRequired = page.props.registrationLoginRequired;
  const user = page.props.auth?.user;
  if (isRequired && !user) {
    return `/pendaftar/login?redirect_to=register&notice=required&lomba=${compId}`;
  }
  return `/layanan-registrasi?lomba=${compId}`;
}

const props = defineProps({
  competitions: {
    type: Array,
    default: () => []
  },
  sponsors: {
    type: Array,
    default: () => []
  },
  sponsorSettings: {
    type: Object,
    default: () => ({})
  },
  galleries: {
    type: Array,
    default: () => []
  },
  gallerySettings: {
    type: Object,
    default: () => ({})
  },
  tickerSettings: {
    type: Object,
    default: () => ({
      is_active: true,
      speed: 35,
      separator: '✦',
      pause_on_hover: true,
      items: []
    })
  },
  faqs: {
    type: Array,
    default: () => []
  },
  faqSettings: {
    type: Object,
    default: () => ({
      tag: 'Bantuan Informasi',
      title: 'Pertanyaan Sering Diajukan (FAQ)',
      desc: 'Jawaban atas pertanyaan umum seputar pendaftaran dan pelaksanaan Semarak Aliyah.',
      is_active: true
    })
  },
  pricingSettings: {
    type: Object,
    default: () => ({})
  },
  tmScheduleSettings: {
    type: Object,
    default: () => ({
      tag: 'Agenda Pelaksanaan',
      title: 'Jadwal Technical Meeting & Pelaksanaan',
      desc: 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.',
      guidelines_active: true,
      guidelines: [],
      is_active: true
    })
  },
  heroSettings: {
    type: Object,
    default: () => ({})
  },
  statsSettings: {
    type: Object,
    default: () => ({
      is_active: true,
      items: []
    })
  },
  aboutSettings: {
    type: Object,
    default: () => ({
      is_active: true,
      tag: 'Apresiasi & Fasilitas',
      title: 'Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan',
      desc: '',
      items: []
    })
  }
});

const defaultStatsItems = [
  { icon: 'ri-focus-2-line', number: '8 Cabang', label: 'Kompetisi & Olahraga' },
  { icon: 'ri-building-line', number: '50+ Sekolah', label: 'SMP/MTs se-Jember' },
  { icon: 'ri-hand-coin-line', number: 'Jutaan Rupiah', label: 'Uang Pembinaan & Hadiah' },
  { icon: 'ri-award-line', number: '100% Sertifikat', label: 'Peserta & Guru Pendamping' }
];

const displayStatsItems = computed(() => {
  if (props.statsSettings && Array.isArray(props.statsSettings.items) && props.statsSettings.items.length > 0) {
    return props.statsSettings.items;
  }
  return defaultStatsItems;
});

const defaultAboutPrizes = computed(() => [
  { icon: 'ri-trophy-line', title: 'Piala Juara', desc: 'Trophy eksklusif dan bergengsi untuk Juara 1, Juara 2, dan Juara 3 pada setiap cabang kompetisi.' },
  { icon: 'ri-money-dollar-circle-line', title: 'Uang Pembinaan', desc: 'Bantuan dana pembinaan jutaan rupiah untuk mendukung pengembangan bakat dan prestasi sang juara.' },
  { icon: 'ri-file-shield-line', title: 'E-Piagam & Sertifikat', desc: 'Piagam penghargaan resmi ber-barcode untuk seluruh Peserta, Juara, dan Guru Pembimbing / Pendamping.' },
  { icon: 'ri-graduation-cap-line', title: 'Beasiswa Pendidikan', desc: `Jalur beasiswa penuh dan tabungan pendidikan masuk di ${school.value.school_name || 'MA Unggulan Wahid Hasyim Balung'}, ${school.value.school_city || 'Jember'}.` }
]);

const displayAboutPrizes = computed(() => {
  if (props.aboutSettings && Array.isArray(props.aboutSettings.items) && props.aboutSettings.items.length > 0) {
    return props.aboutSettings.items;
  }
  return defaultAboutPrizes.value;
});

const displayTickerItems = computed(() => {
  if (props.tickerSettings && Array.isArray(props.tickerSettings.items) && props.tickerSettings.items.length > 0) {
    return props.tickerSettings.items.map(item => {
      if (/SEMARAK ALIYAH/i.test(item.text)) {
        return {
          ...item,
          text: item.text.replace(/SEMARAK ALIYAH(\s+[IVXLCDM\d]+)?/i, (school.value.event_name || 'SEMARAK ALIYAH').toUpperCase())
        };
      }
      return item;
    });
  }
  return [
    { text: school.value.event_name ? school.value.event_name.toUpperCase() : 'SEMARAK ALIYAH XIV', style: 'gradient' },
    { text: 'AJANG TALENTA JUARA', style: 'outline' },
    { text: (school.value.school_short_name || 'MA UNGGULAN WAHID HASYIM').toUpperCase(), style: 'filled' },
    { text: 'SAINS • SENI • OLAHRAGA • RELIGI', style: 'outline' },
    { text: 'TOTAL HADIAH JUTAAN RUPIAH', style: 'gradient' },
    { text: 'FESTIVAL PELAJAR SE-JEMBER', style: 'outline' },
  ];
});

// Ticker dynamic typography styles
const tickerFontFamilyMap = {
  syne: "'Syne', sans-serif",
  outfit: "'Outfit', sans-serif",
  oswald: "'Oswald', sans-serif",
  jakarta: "'Plus Jakarta Sans', sans-serif",
  montserrat: "'Montserrat', sans-serif",
};

const tickerComputedStyles = computed(() => {
  const fontKey = props.tickerSettings?.font_family || 'syne';
  const fontFamily = tickerFontFamilyMap[fontKey] || tickerFontFamilyMap.syne;
  const fontSize = props.tickerSettings?.font_size ? `${props.tickerSettings.font_size}px` : null;
  const strokeWidth = props.tickerSettings?.stroke_width ? `${props.tickerSettings.stroke_width}px` : '2px';
  const speed = (props.tickerSettings?.speed || 35) + 's';
  const opacityVal = props.tickerSettings?.opacity != null ? Number(props.tickerSettings.opacity) / 100 : 1;
  const outlineOpacityVal = props.tickerSettings?.outline_opacity != null ? Number(props.tickerSettings.outline_opacity) / 100 : 0.8;

  const styles = {
    animationDuration: speed,
    '--ticker-font-family': fontFamily,
    '--ticker-stroke-width': strokeWidth,
    '--ticker-opacity': opacityVal,
    '--ticker-outline-opacity': outlineOpacityVal,
    opacity: opacityVal,
  };
  if (fontSize) {
    styles['--ticker-font-size'] = fontSize;
  }
  return styles;
});

// 1. Live Countdown Timer
const kustomisasi = computed(() => page.props.kustomisasiWeb || {});
const isCountdownEnded = ref(false);
const days = ref('00');
const hours = ref('00');
const minutes = ref('00');
const seconds = ref('00');
let timerInterval = null;

const formattedCountdownDate = computed(() => {
  const targetStr = kustomisasi.value.countdown_target_date;
  if (!targetStr) return '28 - 29 Oktober 2026';
  try {
    const d = new Date(targetStr);
    if (isNaN(d.getTime())) return targetStr;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
  } catch (e) {
    return targetStr;
  }
});

function getCountdownTimestamp() {
  const targetStr = kustomisasi.value.countdown_target_date;
  if (targetStr) {
    const t = new Date(targetStr).getTime();
    if (!isNaN(t) && t > 0) return t;
  }
  return new Date('2026-10-24T23:59:00+07:00').getTime();
}

function updateCountdown() {
  const now = new Date().getTime();
  const target = getCountdownTimestamp();
  const diff = target - now;

  if (diff <= 0) {
    days.value = '00';
    hours.value = '00';
    minutes.value = '00';
    seconds.value = '00';
    isCountdownEnded.value = true;
    return;
  }

  isCountdownEnded.value = false;
  const d = Math.floor(diff / (1000 * 60 * 60 * 24));
  const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  const s = Math.floor((diff % (1000 * 60)) / 1000);

  days.value = String(d).padStart(2, '0');
  hours.value = String(h).padStart(2, '0');
  minutes.value = String(m).padStart(2, '0');
  seconds.value = String(s).padStart(2, '0');
}

// ==========================================
// HERO SECTION JAVASCRIPT ANIMATED BACKGROUND
// ==========================================
const heroSectionRef = ref(null);
const heroCanvasRef = ref(null);
let heroCleanupFn = null;

function initHeroCanvasAnimation() {
  const canvas = heroCanvasRef.value;
  const section = heroSectionRef.value;
  if (!canvas || !section) return;

  const ctx = canvas.getContext('2d', { alpha: true });
  if (!ctx) return;

  const dpr = Math.min(window.devicePixelRatio || 1, 2);
  let width = 0;
  let height = 0;
  let isVisible = true;
  let heroAnimId = null;

  // Interactive mouse state
  const mouse = {
    x: -1000,
    y: -1000,
    isHover: false,
    radius: 180
  };

  function handleMouseMove(e) {
    const rect = section.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
    mouse.isHover = true;
  }

  function handleMouseLeave() {
    mouse.isHover = false;
    mouse.x = -1000;
    mouse.y = -1000;
  }

  section.addEventListener('mousemove', handleMouseMove, { passive: true });
  section.addEventListener('mouseleave', handleMouseLeave, { passive: true });

  // 8-Sector Ambient Glowing Orbs covering all quadrants & borders
  const orbs = [
    { x: 0.10, y: 0.16, r: 360, color: [139, 92, 246], phase: 0.0, speed: 0.35 },  // Top-Left (Purple)
    { x: 0.50, y: 0.12, r: 330, color: [245, 158, 11], phase: 1.2, speed: 0.40 },  // Top-Center (Gold)
    { x: 0.88, y: 0.16, r: 370, color: [236, 72, 153], phase: 2.4, speed: 0.32 },  // Top-Right (Pink)
    { x: 0.18, y: 0.50, r: 350, color: [14, 165, 233], phase: 3.6, speed: 0.38 },  // Mid-Left (Cyan)
    { x: 0.52, y: 0.52, r: 390, color: [168, 85, 247], phase: 4.8, speed: 0.28 },  // Center (Violet)
    { x: 0.82, y: 0.52, r: 360, color: [251, 146, 60], phase: 5.5, speed: 0.36 },  // Mid-Right (Amber)
    { x: 0.14, y: 0.88, r: 350, color: [16, 185, 129], phase: 1.8, speed: 0.34 },  // Bottom-Left (Emerald)
    { x: 0.50, y: 0.88, r: 370, color: [245, 158, 11], phase: 3.2, speed: 0.42 },  // Bottom-Center (Gold)
    { x: 0.86, y: 0.86, r: 380, color: [236, 72, 153], phase: 4.5, speed: 0.30 },  // Bottom-Right (Pink)
  ];

  // Particle constellation
  let particles = [];
  const palette = [
    { r: 245, g: 158, b: 11, hex: '#f59e0b' }, // Gold
    { r: 236, g: 72, b: 153, hex: '#ec4899' }, // Pink
    { r: 139, g: 92, b: 246, hex: '#8b5cf6' }, // Purple
    { r: 14, g: 165, b: 233, hex: '#0ea5e9' }, // Cyan
    { r: 16, g: 185, b: 129, hex: '#10b981' }, // Emerald
    { r: 251, g: 146, b: 60, hex: '#fb923c' }, // Orange
  ];

  function createParticles() {
    if (width <= 0 || height <= 0) return;
    const count = Math.min(125, Math.max(65, Math.floor((width * height) / 12500)));
    particles = [];
    for (let i = 0; i < count; i++) {
      const colorObj = palette[Math.floor(Math.random() * palette.length)];
      const isStar = Math.random() < 0.30;
      particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        vx: (Math.random() - 0.5) * 0.55,
        vy: (Math.random() - 0.5) * 0.55,
        radius: isStar ? 2.4 + Math.random() * 2.2 : 1.2 + Math.random() * 1.8,
        color: colorObj,
        isStar,
        twinkleSpeed: 0.02 + Math.random() * 0.045,
        twinklePhase: Math.random() * Math.PI * 2,
        alpha: 0.35 + Math.random() * 0.45,
      });
    }
  }

  // Shooting star meteors system
  let meteors = [];
  let lastMeteorTime = 0;

  function spawnMeteor(now) {
    if (now - lastMeteorTime > 2200 + Math.random() * 1600) {
      lastMeteorTime = now;
      const startX = Math.random() * (width * 0.85);
      const startY = Math.random() * (height * 0.35);
      const speed = 7 + Math.random() * 5;
      const angle = Math.PI / 4 + (Math.random() - 0.5) * 0.3;
      meteors.push({
        x: startX,
        y: startY,
        vx: Math.cos(angle) * speed,
        vy: Math.sin(angle) * speed,
        length: 100 + Math.random() * 80,
        life: 1.0,
        decay: 0.018 + Math.random() * 0.012,
        colorHex: Math.random() < 0.5 ? '#f59e0b' : '#ec4899',
      });
    }
  }

  function resize() {
    if (!section || !canvas) return;
    const rect = section.getBoundingClientRect();
    const newWidth = Math.max(Math.floor(section.clientWidth || rect.width || window.innerWidth), 320);
    const newHeight = Math.max(Math.floor(section.clientHeight || rect.height || 720), 450);

    if (newWidth <= 0 || newHeight <= 0) return;

    const prevW = width;
    const prevH = height;
    width = newWidth;
    height = newHeight;

    canvas.width = Math.floor(width * dpr);
    canvas.height = Math.floor(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    // If particles not initialized or dimensions changed significantly (>35px), rebuild particles
    if (particles.length === 0 || Math.abs(prevW - width) > 35 || Math.abs(prevH - height) > 35) {
      createParticles();
    }
  }

  resize();
  window.addEventListener('resize', resize, { passive: true });

  // ResizeObserver to automatically adapt whenever images or fonts load and resize the section
  let resizeObserver = null;
  if (typeof ResizeObserver !== 'undefined') {
    resizeObserver = new ResizeObserver(() => {
      resize();
    });
    resizeObserver.observe(section);
  }

  // Ensure canvas fits section when images finish loading
  const heroImgs = section.querySelectorAll('img');
  heroImgs.forEach((img) => {
    if (!img.complete) {
      img.addEventListener('load', resize, { once: true });
    }
  });

  // Timers to guarantee sync as layout settles
  const t1 = setTimeout(resize, 80);
  const t2 = setTimeout(resize, 300);
  const t3 = setTimeout(resize, 800);
  const t4 = setTimeout(resize, 1500);

  // Draw 4-point sparkle star
  function drawSparkleStar(cx, cy, spikes, outerRadius, innerRadius, colorHex, alpha) {
    let rot = Math.PI / 2 * 3;
    let x = cx;
    let y = cy;
    const step = Math.PI / spikes;

    ctx.save();
    ctx.beginPath();
    ctx.moveTo(cx, cy - outerRadius);
    for (let i = 0; i < spikes; i++) {
      x = cx + Math.cos(rot) * outerRadius;
      y = cy + Math.sin(rot) * outerRadius;
      ctx.lineTo(x, y);
      rot += step;

      x = cx + Math.cos(rot) * innerRadius;
      y = cy + Math.sin(rot) * innerRadius;
      ctx.lineTo(x, y);
      rot += step;
    }
    ctx.lineTo(cx, cy - outerRadius);
    ctx.closePath();
    ctx.fillStyle = colorHex;
    ctx.globalAlpha = alpha;
    ctx.fill();
    ctx.restore();
  }

  let lastTime = performance.now();

  function animate(currentTime) {
    if (!isVisible) {
      heroAnimId = requestAnimationFrame(animate);
      return;
    }

    const dt = Math.min(currentTime - lastTime, 60);
    lastTime = currentTime;
    const timeSec = currentTime * 0.001;

    ctx.clearRect(0, 0, width, height);

    // Detect theme
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    // 1. Draw 8-Sector glowing ambient orbs (covering entire width & height)
    for (let i = 0; i < orbs.length; i++) {
      const orb = orbs[i];
      const cx = (orb.x + Math.sin(timeSec * orb.speed + orb.phase) * 0.07) * width;
      const cy = (orb.y + Math.cos(timeSec * (orb.speed * 0.9) + orb.phase) * 0.07) * height;
      const r = orb.r;

      const grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, r);
      const [cr, cg, cb] = orb.color;
      const peakAlpha = isDark ? 0.22 : 0.12;
      const midAlpha = isDark ? 0.08 : 0.04;
      grad.addColorStop(0, `rgba(${cr}, ${cg}, ${cb}, ${peakAlpha})`);
      grad.addColorStop(0.5, `rgba(${cr}, ${cg}, ${cb}, ${midAlpha})`);
      grad.addColorStop(1, `rgba(${cr}, ${cg}, ${cb}, 0)`);

      ctx.fillStyle = grad;
      ctx.beginPath();
      ctx.arc(cx, cy, r, 0, Math.PI * 2);
      ctx.fill();
    }

    // 2. Subtle ambient digital grid across the entire canvas
    const gridSpacing = 95;
    const gridAlpha = isDark ? 0.045 : 0.025;
    ctx.strokeStyle = isDark ? `rgba(168, 85, 247, ${gridAlpha})` : `rgba(124, 58, 237, ${gridAlpha})`;
    ctx.lineWidth = 0.65;
    ctx.beginPath();
    const xOffset = (timeSec * 5) % gridSpacing;
    for (let gx = xOffset; gx < width; gx += gridSpacing) {
      ctx.moveTo(gx, 0);
      ctx.lineTo(gx, height);
    }
    for (let gy = 0; gy < height; gy += gridSpacing) {
      ctx.moveTo(0, gy);
      ctx.lineTo(width, gy);
    }
    ctx.stroke();

    // 3. Interactive mouse spotlight halo
    if (mouse.isHover) {
      const mouseGrad = ctx.createRadialGradient(mouse.x, mouse.y, 0, mouse.x, mouse.y, mouse.radius);
      const mAlpha = isDark ? 0.26 : 0.14;
      mouseGrad.addColorStop(0, `rgba(245, 158, 11, ${mAlpha})`);
      mouseGrad.addColorStop(0.5, `rgba(139, 92, 246, ${mAlpha * 0.5})`);
      mouseGrad.addColorStop(1, 'rgba(139, 92, 246, 0)');
      ctx.fillStyle = mouseGrad;
      ctx.beginPath();
      ctx.arc(mouse.x, mouse.y, mouse.radius, 0, Math.PI * 2);
      ctx.fill();
    }

    // 4. Connect particles with glowing filaments
    const maxDist = 120;
    for (let i = 0; i < particles.length; i++) {
      const p1 = particles[i];

      // Mouse interaction
      if (mouse.isHover) {
        const mdx = p1.x - mouse.x;
        const mdy = p1.y - mouse.y;
        const mdist = Math.hypot(mdx, mdy);
        if (mdist < 110 && mdist > 0) {
          const pushForce = ((110 - mdist) / 110) * 1.8;
          p1.x += (mdx / mdist) * pushForce;
          p1.y += (mdy / mdist) * pushForce;
        }

        // Connect particle to mouse
        if (mdist < 150) {
          const lineAlpha = (1 - mdist / 150) * (isDark ? 0.38 : 0.24);
          ctx.beginPath();
          ctx.strokeStyle = `rgba(245, 158, 11, ${lineAlpha})`;
          ctx.lineWidth = 1.2;
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(mouse.x, mouse.y);
          ctx.stroke();
        }
      }

      for (let j = i + 1; j < particles.length; j++) {
        const p2 = particles[j];
        const dx = p1.x - p2.x;
        const dy = p1.y - p2.y;
        const dist = Math.hypot(dx, dy);

        if (dist < maxDist) {
          const lineAlpha = (1 - dist / maxDist) * (isDark ? 0.26 : 0.13);
          ctx.beginPath();
          ctx.strokeStyle = `rgba(168, 85, 247, ${lineAlpha})`;
          ctx.lineWidth = 0.85;
          ctx.moveTo(p1.x, p1.y);
          ctx.lineTo(p2.x, p2.y);
          ctx.stroke();
        }
      }
    }

    // 5. Update and draw particles (with seamless wrap-around across all edges)
    for (let i = 0; i < particles.length; i++) {
      const p = particles[i];

      p.x += p.vx * (dt / 16.6);
      p.y += p.vy * (dt / 16.6);

      // Seamless wrap-around so all areas stay evenly populated
      if (p.x < -15) p.x = width + 15;
      if (p.x > width + 15) p.x = -15;
      if (p.y < -15) p.y = height + 15;
      if (p.y > height + 15) p.y = -15;

      p.twinklePhase += p.twinkleSpeed;
      const pulse = 0.65 + Math.sin(p.twinklePhase) * 0.35;
      const currentRadius = p.radius * pulse;
      const currentAlpha = p.alpha * (isDark ? 0.92 : 0.65) * pulse;

      if (p.isStar) {
        drawSparkleStar(p.x, p.y, 4, currentRadius * 1.8, currentRadius * 0.5, p.color.hex, currentAlpha);
      } else {
        ctx.beginPath();
        ctx.arc(p.x, p.y, currentRadius, 0, Math.PI * 2);
        ctx.fillStyle = p.color.hex;
        ctx.globalAlpha = currentAlpha;
        ctx.fill();
        ctx.globalAlpha = 1;
      }
    }

    // 6. Draw dynamic shooting star meteors
    spawnMeteor(currentTime);
    for (let i = meteors.length - 1; i >= 0; i--) {
      const m = meteors[i];
      m.x += m.vx * (dt / 16.6);
      m.y += m.vy * (dt / 16.6);
      m.life -= m.decay;

      if (m.life <= 0 || m.x > width + 100 || m.y > height + 100) {
        meteors.splice(i, 1);
        continue;
      }

      const tailX = m.x - (m.vx / Math.hypot(m.vx, m.vy)) * m.length;
      const tailY = m.y - (m.vy / Math.hypot(m.vx, m.vy)) * m.length;

      const meteorGrad = ctx.createLinearGradient(tailX, tailY, m.x, m.y);
      const mColor = m.colorHex === '#f59e0b' ? '245, 158, 11' : '236, 72, 153';
      meteorGrad.addColorStop(0, `rgba(${mColor}, 0)`);
      meteorGrad.addColorStop(1, `rgba(${mColor}, ${m.life * (isDark ? 0.85 : 0.6)})`);

      ctx.save();
      ctx.beginPath();
      ctx.strokeStyle = meteorGrad;
      ctx.lineWidth = 1.8;
      ctx.moveTo(tailX, tailY);
      ctx.lineTo(m.x, m.y);
      ctx.stroke();

      // Meteor head spark
      ctx.beginPath();
      ctx.arc(m.x, m.y, 2, 0, Math.PI * 2);
      ctx.fillStyle = '#ffffff';
      ctx.globalAlpha = m.life;
      ctx.fill();
      ctx.restore();
    }

    heroAnimId = requestAnimationFrame(animate);
  }

  heroAnimId = requestAnimationFrame(animate);

  // IntersectionObserver to pause when hero is offscreen
  let heroIntersectionObserver = null;
  if (typeof IntersectionObserver !== 'undefined') {
    heroIntersectionObserver = new IntersectionObserver((entries) => {
      isVisible = entries[0].isIntersecting;
    }, { threshold: 0.05 });
    heroIntersectionObserver.observe(section);
  }

  // Cleanup helper
  heroCleanupFn = () => {
    if (heroAnimId) cancelAnimationFrame(heroAnimId);
    window.removeEventListener('resize', resize);
    section.removeEventListener('mousemove', handleMouseMove);
    section.removeEventListener('mouseleave', handleMouseLeave);
    if (resizeObserver) resizeObserver.disconnect();
    if (heroIntersectionObserver) heroIntersectionObserver.disconnect();
    clearTimeout(t1);
    clearTimeout(t2);
    clearTimeout(t3);
    clearTimeout(t4);
  };
}

onMounted(() => {
  updateCountdown();
  timerInterval = setInterval(updateCountdown, 1000);
  setTimeout(() => {
    initSponsorSliderPosition();
  }, 200);
  startAutoSlide();
  initHeroCanvasAnimation();

  if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', handleVisibilityChange);
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  stopAutoSlide();
  if (heroCleanupFn) heroCleanupFn();
  if (typeof document !== 'undefined') {
    document.removeEventListener('visibilitychange', handleVisibilityChange);
  }
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleLightboxKey);
  }
});

// 2. Competition Filter Tabs
const activeFilter = ref('all');

const filteredCompetitions = computed(() => {
  if (!props.competitions || props.competitions.length === 0) return [];
  if (activeFilter.value === 'all') return props.competitions;
  return props.competitions.filter(c => {
    if (activeFilter.value === 'akademik') return c.category === 'akademik';
    if (activeFilter.value === 'seni') return c.category === 'seni' || c.category === 'tahfidz' || c.category === 'keagamaan';
    if (activeFilter.value === 'olahraga') return c.category === 'olahraga' || c.category === 'esport';
    return true;
  });
});

// 3. Detail Modal State
const selectedComp = ref(null);
const detailModalOpen = ref(false);

function openDetailModal(comp) {
  selectedComp.value = comp;
  detailModalOpen.value = true;
}

function closeDetailModal() {
  detailModalOpen.value = false;
  selectedComp.value = null;
}

// 4. FAQ Accordion State
const openFaqIndex = ref(0);

function toggleFaq(index) {
  openFaqIndex.value = openFaqIndex.value === index ? -1 : index;
}

const computedFaqs = computed(() => {
  if (props.faqs && Array.isArray(props.faqs) && props.faqs.length > 0) {
    return props.faqs.map(item => ({
      id: item.id,
      q: item.q || item.question,
      a: item.a || item.answer,
      category: item.category || 'Umum',
    }));
  }
  return [
    {
      q: `Siapa saja yang berhak menjadi peserta ${school.value.event_name || 'Semarak Aliyah XIV 2026'}?`,
      a: '<p>Seluruh siswa-siswi aktif tingkat <strong>SMP / MTs sederajat kelas 7, 8, dan 9 se-Kabupaten Jember</strong> baik dari sekolah negeri maupun swasta, dibuktikan dengan <u>Kartu Tanda Siswa (KTS)</u> atau surat tugas/delegasi resmi dari kepala sekolah/madrasah.</p>',
      category: 'Pendaftaran'
    },
    {
      q: 'Bagaimana sistem pembayaran dan konfirmasi biaya pendaftaran?',
      a: `<p>Pembayaran dapat ditransfer melalui rekening resmi <strong>Bank Syariah Indonesia (BSI)</strong>:</p><ul><li>No. Rekening: <strong>7123456789</strong></li><li>Atas Nama: <strong>${school.value.event_name || 'Semarak Aliyah'}</strong></li></ul><p>Setelah transfer berhasil, bukti pembayaran dapat langsung diunggah melalui formulir pendaftaran online atau dikonfirmasikan via WhatsApp ke tim bendahara panitia.</p>`,
      category: 'Pembayaran & Biaya'
    },
    {
      q: 'Apakah ada kuota maksimal untuk masing-masing cabang perlombaan?',
      a: '<p><strong>Ya</strong>, setiap cabang lomba memiliki batas kuota maksimal untuk menjaga kualitas dan kenyamanan kompetisi. Pendaftaran akan <em>otomatis ditutup</em> ketika kuota cabang lomba telah terpenuhi.</p>',
      category: 'Kompetisi & Juknis'
    },
    {
      q: 'Apakah satu sekolah boleh mendaftar lebih dari satu cabang lomba?',
      a: '<p><strong>Sangat diperbolehkan!</strong> Bahkan panitia menyediakan <strong>Paket All-In Komplit seharga Rp 200.000</strong> untuk sekolah/madrasah yang ingin mengirimkan delegasi terbaiknya di seluruh cabang lomba tanpa batas kuota pendaftaran cabang.</p>',
      category: 'Pendaftaran'
    },
    {
      q: 'Kapan dan di mana Technical Meeting (TM) dilaksanakan?',
      a: `<p>Technical Meeting dilaksanakan secara <strong>offline</strong> di Kampus <strong>${school.value.school_name || 'MA Unggulan Wahid Hasyim Balung'}</strong> sesuai jadwal tiap cabang lomba (dimulai pukul 08:00 WIB untuk Voli &amp; Olimpiade, dan 14:00 WIB untuk Mobile Legends).</p>`,
      category: 'Pelaksanaan & TM'
    }
  ];
});

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
  if (!comp) return getWaChatUrl(cleanSchoolWa.value);
  const statusInfo = getCompStatusInfo(comp);
  let msg = `Halo Admin Semarak, saya ingin menanyakan perihal cabang lomba ${comp.title}.`;
  if (statusInfo.isManuallyClosed) {
    msg = `Halo Admin Semarak, perihal pendaftaran cabang lomba ${comp.title} yang saat ini berstatus Ditutup Panitia, apakah masih berpeluang bagi sekolah kami untuk mendaftar?`;
  } else if (comp.is_quota_full) {
    msg = `Halo Admin Semarak, apakah delegasi kami bisa masuk waiting list untuk ${comp.title}?`;
  } else if (comp.is_deadline_passed) {
    msg = `Halo Admin Semarak, apakah pendaftaran ${comp.title} masih bisa menerima pendaftar susulan?`;
  }
  return getWaChatUrl(cleanSchoolWa.value, msg);
}

// Helper function for converting hex colors to rgba
function hexToRgba(hex, alpha = 0.15) {
  if (!hex) return '';
  if (hex.startsWith('rgb')) return hex;
  let c = hex.replace('#', '');
  if (c.length === 3) {
    c = c.split('').map(x => x + x).join('');
  }
  if (c.length === 6) {
    const num = parseInt(c, 16);
    return `rgba(${(num >> 16) & 255}, ${(num >> 8) & 255}, ${num & 255}, ${alpha})`;
  }
  return hex;
}

const defaultTmGuidelinesList = [
  {
    title: 'Delegasi Resmi',
    desc: 'Setiap madrasah/sekolah wajib mengutus minimal 1 guru pembina atau kapten/ketua tim.',
    icon: 'ri-user-star-line',
    color: '#8b5cf6',
  },
  {
    title: 'Verifikasi Berkas',
    desc: 'Membawa cetak bukti registrasi & Kartu Tanda Siswa (KTS) / delegasi resmi untuk validasi.',
    icon: 'ri-file-shield-line',
    color: '#f97316',
  },
  {
    title: 'Akses Hybrid (Zoom)',
    desc: 'Tersedia tautan Zoom Meeting khusus bagi delegasi luar daerah yang berhalangan hadir offline.',
    icon: 'ri-vidicon-line',
    color: '#10b981',
  },
  {
    title: 'Tepat Waktu & Tertib',
    desc: 'Hadir 15 menit sebelum pembukaan dimulai, mengenakan seragam rapi atau jas almamater.',
    icon: 'ri-time-line',
    color: '#ec4899',
  },
];

const effectiveTmGuidelines = computed(() => {
  if (props.tmScheduleSettings?.guidelines && Array.isArray(props.tmScheduleSettings.guidelines) && props.tmScheduleSettings.guidelines.length > 0) {
    return props.tmScheduleSettings.guidelines;
  }
  return defaultTmGuidelinesList;
});

// Technical Meeting (TM) Filter & Helper States
const activeTmFilter = ref('all');

const filteredTmCompetitions = computed(() => {
  if (!props.competitions || props.competitions.length === 0) return [];
  if (activeTmFilter.value === 'all') return props.competitions;
  return props.competitions.filter(c => {
    if (activeTmFilter.value === 'akademik') return c.category === 'akademik';
    if (activeTmFilter.value === 'seni') return c.category === 'seni' || c.category === 'tahfidz' || c.category === 'keagamaan';
    if (activeTmFilter.value === 'olahraga') return c.category === 'olahraga' || c.category === 'esport';
    return true;
  });
});

function getCompTmLocation(comp) {
  if (!comp) return 'Kampus MA Unggulan Wahid Hasyim Balung';
  if (comp.tm_settings?.location && comp.tm_settings.location.trim()) {
    return comp.tm_settings.location.trim();
  }
  if (comp.resolved_tm_settings?.location && comp.resolved_tm_settings.location.trim()) {
    return comp.resolved_tm_settings.location.trim();
  }
  const id = String(comp.id || '').toLowerCase();
  const cat = String(comp.category || '').toLowerCase();
  if (id.includes('voli') || id.includes('volley')) return 'Gedung Olahraga / Lapangan Utama Madrasah';
  if (id.includes('ml') || id.includes('esport') || id.includes('mobile-legends')) return 'Lab Komputer Multimedia (Online Hybrid)';
  if (id.includes('mipa') || id.includes('ips') || cat === 'akademik') return 'Auditorium Sains Terpadu (Gedung A)';
  if (id.includes('banjari') || id.includes('singer')) return 'Panggung Utama & Plaza Terbuka';
  if (id.includes('tahfidz') || id.includes('mhq') || cat === 'keagamaan') return 'Ruang Tilawah & Masjid Kampus';
  if (id.includes('story')) return 'Stage Kreasi Bahasa (Gedung B)';
  return 'Gedung Induk Kampus MA Wahid Hasyim';
}

function getCompTmAgenda(comp) {
  if (!comp) return 'Penjelasan juknis, tata tertib, dan teknis pelaksanaan lomba.';
  if (comp.tm_settings?.agenda && comp.tm_settings.agenda.trim()) {
    return comp.tm_settings.agenda.trim();
  }
  if (comp.resolved_tm_settings?.agenda && comp.resolved_tm_settings.agenda.trim()) {
    return comp.resolved_tm_settings.agenda.trim();
  }
  const id = String(comp.id || '').toLowerCase();
  const cat = String(comp.category || '').toLowerCase();
  if (id.includes('voli') || id.includes('volley')) return 'Drawing bagan pertandingan sistem gugur, verifikasi berkas pemain, penegasan regulasi resmi PBVSI.';
  if (id.includes('ml') || id.includes('esport') || id.includes('mobile-legends')) return 'Penentuan bagan bracket turnamen, aturan draft pick tournament mode, check-in ID & verifikasi kartu pelajar.';
  if (id.includes('mipa') || id.includes('ips') || cat === 'akademik') return 'Tata tertib ujian berbasis CBT, kisi-kisi babak penyisihan, pembagian sesi waktu & teknis babak final.';
  if (id.includes('banjari')) return 'Pengundian nomor urut tampil, penetapan maqra\' & lagu, pembagian jadwal check-sound panggung.';
  if (id.includes('singer')) return 'Pengundian urutan peserta, penyerahan minus-one/partitur lagu, uji coba tata suara panggung teater.';
  if (id.includes('tahfidz') || id.includes('mhq')) return 'Pengundian giliran tilawah, penegasan rubrik tajwid/fashahah, teknis sambung ayat.';
  if (id.includes('story')) return 'Pengundian nomor urut tampil, penyerahan sinopsis naskah, teknis penilaian dewan juri.';
  return 'Penjelasan petunjuk teknis (Juknis), tata tertib kompetisi, verifikasi berkas, dan pengundian nomor urut.';
}

function getCompCpWaUrl(contactPerson, compTitle = '') {
  const text = `Halo Narahubung ${compTitle || 'Semarak Aliyah'}, saya ingin berkonsultasi mengenai jadwal & teknis pelaksanaan Technical Meeting (TM).`;
  return getWaChatUrl(contactPerson, text, cleanSchoolWa.value);
}

// Helper to parse single or multiple Contact Persons cleanly into structured items
function getCompCpList(comp) {
  if (!comp) return [];
  const compTitle = comp.title || 'Lomba';

  // 1. If coordinators array is provided and not empty
  if (Array.isArray(comp.coordinators) && comp.coordinators.length > 0) {
    const list = comp.coordinators
      .filter(c => c && (c.name || c.contact))
      .map(c => {
        const rawContact = c.contact || c.phone || cleanSchoolWa.value;
        const phone = formatWaNumber(rawContact, cleanSchoolWa.value);
        const text = `Halo ${c.name || 'Narahubung'} (${compTitle}), saya ingin berkonsultasi mengenai Technical Meeting (TM) & pelaksanaan lomba.`;
        return {
          name: c.name || c.role || 'Koordinator Lomba',
          role: c.role || '',
          phone: rawContact,
          waUrl: getWaChatUrl(phone, text, cleanSchoolWa.value)
        };
      });
    if (list.length > 0) return list;
  }

  // 2. Parse contact_person string
  const rawCp = comp.contact_person || '';
  if (!rawCp || rawCp.trim() === '-' || rawCp.trim() === '') {
    return [{
      name: 'Panitia Semarak',
      phone: school.value.contact_wa || '0838 5007 0767',
      waUrl: getWaChatUrl(cleanSchoolWa.value, `Halo Panitia Semarak, saya ingin berkonsultasi mengenai Technical Meeting cabang ${compTitle}.`)
    }];
  }

  // Split multiple CPs: handles comma (before name), slash, semicolon, or newline
  const parts = rawCp.split(/(?:\r?\n|;|\/|&|,(?=\s*[A-Za-z]))/).map(p => p.trim()).filter(Boolean);
  const result = [];

  for (const part of parts) {
    const phoneMatch = part.match(/(?:(?:\+?62)|0)?8[0-9\s-]{7,15}/);
    let rawPhone = phoneMatch ? phoneMatch[0].trim() : '';
    let phone = formatWaNumber(rawPhone || part, cleanSchoolWa.value);

    let name = part.replace(/(?:(?:\+?62)|0)?8[0-9\s-]{7,15}/, '')
                   .replace(/[()]/g, '')
                   .replace(/[-:]/g, ' ')
                   .trim();
    if (!name) name = 'Koordinator';

    const text = `Halo ${name} (${compTitle}), saya ingin berkonsultasi mengenai Technical Meeting (TM) & pelaksanaan lomba.`;
    result.push({
      name,
      phone: rawPhone || phone,
      waUrl: getWaChatUrl(phone, text, cleanSchoolWa.value)
    });
  }

  return result.length > 0 ? result : [{
    name: 'Panitia Semarak',
    phone: school.value.contact_wa || '0838 5007 0767',
    waUrl: getWaChatUrl(cleanSchoolWa.value, `Halo Panitia Semarak, saya ingin berkonsultasi mengenai Technical Meeting cabang ${compTitle}.`)
  }];
}

// Generate direct registration WhatsApp URL for direct contact competitions
function getDirectRegistrationWaUrl(comp) {
  if (!comp) return `https://wa.me/${cleanSchoolWa.value}`;
  if (comp.direct_contact_wa_url) return comp.direct_contact_wa_url;
  
  const cpList = getCompCpList(comp);
  const primaryCp = cpList[0] || {};
  const phone = primaryCp.phone ? formatWaNumber(primaryCp.phone, cleanSchoolWa.value) : cleanSchoolWa.value;
  const compTitle = comp.title || 'Lomba';
  const name = primaryCp.name || 'Koordinator Lomba';
  const text = `Halo ${name}, saya ingin mendaftarkan delegasi sekolah kami untuk cabang lomba ${compTitle} di Semarak Aliyah XIV 2026. Mohon informasi teknis pendaftaran langsung dan kuotanya. Terima kasih.`;
  return getWaChatUrl(phone, text, cleanSchoolWa.value);
}

// 5. Official Sponsors & Partners Slider State
const fallbackSponsors = [
  {
    name: 'Bank Syariah Indonesia',
    short: 'BSI',
    tier: 'Official Bank',
    category: 'Mitra Perbankan Utama',
    logo: null,
    only_logo: false,
    icon: 'ri-bank-line',
    color: '#00a39d',
    bgLight: 'rgba(0, 163, 157, 0.1)',
    borderLight: 'rgba(0, 163, 157, 0.3)'
  },
  {
    name: 'Bank Jatim Syariah',
    short: 'Bank Jatim',
    tier: 'Regional Partner',
    category: 'Mitra Finansial Daerah',
    logo: null,
    only_logo: false,
    icon: 'ri-safe-2-line',
    color: '#e11d48',
    bgLight: 'rgba(225, 29, 72, 0.1)',
    borderLight: 'rgba(225, 29, 72, 0.3)'
  },
  {
    name: 'Telkomsel & By.U',
    short: 'Telkomsel',
    tier: 'Telco & Digital',
    category: 'Official Connectivity',
    logo: null,
    only_logo: false,
    icon: 'ri-signal-tower-line',
    color: '#ef4444',
    bgLight: 'rgba(239, 68, 68, 0.1)',
    borderLight: 'rgba(239, 68, 68, 0.3)'
  },
  {
    name: 'Cleo Pure Water',
    short: 'Cleo',
    tier: 'Hydration Partner',
    category: 'Official Mineral Water',
    logo: null,
    only_logo: false,
    icon: 'ri-drop-line',
    color: '#0ea5e9',
    bgLight: 'rgba(14, 165, 233, 0.1)',
    borderLight: 'rgba(14, 165, 233, 0.3)'
  },
  {
    name: 'Indofood Sukses Makmur',
    short: 'Indofood',
    tier: 'Official Partner',
    category: 'Konsumsi & Nutrisi Peserta',
    logo: null,
    only_logo: false,
    icon: 'ri-restaurant-line',
    color: '#f59e0b',
    bgLight: 'rgba(245, 158, 11, 0.1)',
    borderLight: 'rgba(245, 158, 11, 0.3)'
  },
  {
    name: 'ESI (E-Sport Indonesia) Jember',
    short: 'ESI Jember',
    tier: 'Esports Arbiter',
    category: 'Wasit & Teknis MLBB',
    logo: null,
    only_logo: false,
    icon: 'ri-gamepad-line',
    color: '#8b5cf6',
    bgLight: 'rgba(139, 92, 246, 0.1)',
    borderLight: 'rgba(139, 92, 246, 0.3)'
  },
  {
    name: 'Kemenag Kabupaten Jember',
    short: 'Kemenag',
    tier: 'Instansi Pembina',
    category: 'Dukungan Kelembagaan',
    logo: null,
    only_logo: false,
    icon: 'ri-government-line',
    color: '#10b981',
    bgLight: 'rgba(16, 185, 129, 0.1)',
    borderLight: 'rgba(16, 185, 129, 0.3)'
  },
  {
    name: 'Percetakan Balung Mandiri',
    short: 'PBM Print',
    tier: 'Merchandise Partner',
    category: 'Percetakan & Atribut Lomba',
    logo: null,
    only_logo: false,
    icon: 'ri-printer-line',
    color: '#ec4899',
    bgLight: 'rgba(236, 72, 153, 0.1)',
    borderLight: 'rgba(236, 72, 153, 0.3)'
  },
  {
    name: 'Radar Jember Media',
    short: 'Radar Jember',
    tier: 'Media Partner',
    category: 'Publikasi & Pemberitaan',
    logo: null,
    only_logo: false,
    icon: 'ri-newspaper-line',
    color: '#6366f1',
    bgLight: 'rgba(99, 102, 241, 0.1)',
    borderLight: 'rgba(99, 102, 241, 0.3)'
  },
  {
    name: 'Kahf Everyday Grooming',
    short: 'Kahf',
    tier: 'Personal Care',
    category: 'Official Hygiene Partner',
    logo: null,
    only_logo: false,
    icon: 'ri-sparkling-line',
    color: '#14b8a6',
    bgLight: 'rgba(20, 184, 166, 0.1)',
    borderLight: 'rgba(20, 184, 166, 0.3)'
  },
  {
    name: 'Wardah Cosmetics',
    short: 'Wardah',
    tier: 'Beauty Partner',
    category: 'Sponsor Penunjang Seni',
    logo: null,
    only_logo: false,
    icon: 'ri-magic-line',
    color: '#f43f5e',
    bgLight: 'rgba(244, 63, 94, 0.1)',
    borderLight: 'rgba(244, 63, 94, 0.3)'
  },
  {
    name: 'YPIA Wahid Hasyim Balung',
    short: 'YPIA WH',
    tier: 'Grand Patron',
    category: 'Yayasan Penyelenggara',
    logo: null,
    only_logo: false,
    icon: 'ri-building-line',
    color: '#059669',
    bgLight: 'rgba(5, 150, 105, 0.1)',
    borderLight: 'rgba(5, 150, 105, 0.3)'
  }
];

const effectiveSponsors = computed(() => {
  if (props.sponsors && props.sponsors.length > 0) {
    return props.sponsors;
  }
  return fallbackSponsors;
});

const doubledSponsors = computed(() => {
  const list = effectiveSponsors.value;
  if (!list || list.length === 0) return [];
  // Ensure base list has at least 8 items for a broad scrollable set
  let base = [...list];
  while (base.length < 8) {
    base = [...base, ...list];
  }
  // Return 4 complete identical sets for infinite seamless bi-directional looping
  return [...base, ...base, ...base, ...base];
});

// Dynamic Settings from Admin Panel
const effectiveSponsorSettings = computed(() => ({
  tag: props.sponsorSettings?.tag || 'Mitra & Kolaborasi',
  title: props.sponsorSettings?.title || 'Didukung Oleh Mitra & Sponsor Terpercaya',
  desc: props.sponsorSettings?.desc || 'Apresiasi dan terima kasih kepada institusi perbankan syariah, penyedia telekomunikasi, media massa, serta mitra strategis yang turut bersinergi menyukseskan Semarak Aliyah XIV 2026.',
  cta_text: props.sponsorSettings?.cta_text || 'Peluang Sponsorship: Bergabunglah sebagai mitra resmi dan promosikan brand Anda kepada ribuan pelajar, santri & pendidik se-Kabupaten Jember.',
  cta_wa: props.sponsorSettings?.cta_wa || '6283850070767',
  cta_btn_text: props.sponsorSettings?.cta_btn_text || 'Ajukan Kerja Sama Sponsor',
  is_active: props.sponsorSettings?.is_active !== false,
}));

// 6. Interactive Sponsor Drag-to-Scroll & Auto-Slide Interval State
const isSponsorHovered = ref(false);
const isSponsorPaused = ref(false);
const sponsorSliderRef = ref(null);

const isDragging = ref(false);
const isMouseDown = ref(false);
const startX = ref(0);
const startScrollLeft = ref(0);
let sponsorAutoSlideTimer = null;

function getCardStep() {
  if (!sponsorSliderRef.value) return 317;
  const firstCard = sponsorSliderRef.value.querySelector('.sponsor-card');
  if (firstCard) {
    return firstCard.offsetWidth + 22;
  }
  return 317;
}

function getSetWidth() {
  if (!sponsorSliderRef.value) return 0;
  const cards = sponsorSliderRef.value.querySelectorAll('.sponsor-card');
  const count = cards.length;
  if (count < 4) return sponsorSliderRef.value.scrollWidth / 4;
  const setSize = Math.floor(count / 4);
  const firstCard = cards[0];
  const secondSetFirstCard = cards[setSize];
  if (firstCard && secondSetFirstCard) {
    const dist = secondSetFirstCard.offsetLeft - firstCard.offsetLeft;
    if (dist > 0) return dist;
  }
  return sponsorSliderRef.value.scrollWidth / 4;
}

function initSponsorSliderPosition() {
  if (!sponsorSliderRef.value) return;
  const setWidth = getSetWidth();
  if (setWidth > 0) {
    // Start at Set 2 so user has full sets to the left and right
    sponsorSliderRef.value.scrollLeft = setWidth;
  }
}

// Seamless leftward looping: advances to the left continuously without ever stopping
function slideNext() {
  if (!sponsorSliderRef.value) return;
  const el = sponsorSliderRef.value;
  const step = getCardStep();
  const setWidth = getSetWidth();

  if (setWidth > 0) {
    // When we scroll into Set 3 (past setWidth * 2), seamlessly wrap back by exactly 1 setWidth into Set 2
    if (el.scrollLeft >= setWidth * 2) {
      el.scrollLeft -= setWidth;
    }
  }

  // Smoothly step forward by one card (moves cards to the left)
  el.scrollBy({ left: step, behavior: 'smooth' });
}

// Background tab visibility handler: resume smoothly and normalize scroll position
function handleVisibilityChange() {
  if (!document.hidden) {
    if (sponsorSliderRef.value) {
      const setWidth = getSetWidth();
      if (setWidth > 0) {
        while (sponsorSliderRef.value.scrollLeft >= setWidth * 2) {
          sponsorSliderRef.value.scrollLeft -= setWidth;
        }
        while (sponsorSliderRef.value.scrollLeft < setWidth) {
          sponsorSliderRef.value.scrollLeft += setWidth;
        }
      }
    }
    if (!isSponsorPaused.value) {
      startAutoSlide();
    }
  }
}

// Auto wrap on manual scroll (e.g. trackpad or mouse wheel)
function onSliderScroll() {
  if (!sponsorSliderRef.value || isMouseDown.value) return;
  const el = sponsorSliderRef.value;
  const setWidth = getSetWidth();
  if (setWidth <= 0) return;

  if (el.scrollLeft >= setWidth * 2.8) {
    el.scrollLeft -= setWidth;
  } else if (el.scrollLeft <= setWidth * 0.2) {
    el.scrollLeft += setWidth;
  }
}

// Mouse Drag Events (Grab & Drag Hand Cursor)
function onSliderMouseDown(e) {
  if (!sponsorSliderRef.value) return;
  isMouseDown.value = true;
  isDragging.value = false;
  startX.value = e.pageX - sponsorSliderRef.value.offsetLeft;
  startScrollLeft.value = sponsorSliderRef.value.scrollLeft;
}

function onSliderMouseMove(e) {
  if (!isMouseDown.value || !sponsorSliderRef.value) return;
  e.preventDefault();
  const el = sponsorSliderRef.value;
  const x = e.pageX - el.offsetLeft;
  const walk = (x - startX.value) * 1.4;
  if (Math.abs(walk) > 5) {
    isDragging.value = true;
  }

  let newScrollLeft = startScrollLeft.value - walk;
  const setWidth = getSetWidth();

  if (setWidth > 0) {
    if (newScrollLeft >= setWidth * 2.8) {
      newScrollLeft -= setWidth;
      startScrollLeft.value -= setWidth;
    } else if (newScrollLeft <= setWidth * 0.2) {
      newScrollLeft += setWidth;
      startScrollLeft.value += setWidth;
    }
  }

  el.scrollLeft = newScrollLeft;
}

function onSliderMouseUp() {
  isMouseDown.value = false;
  setTimeout(() => {
    isDragging.value = false;
  }, 50);
}

function onSliderMouseLeave() {
  isMouseDown.value = false;
  isSponsorHovered.value = false;
  setTimeout(() => {
    isDragging.value = false;
  }, 50);
}

// Touch swipe events for mobile devices
let touchStartX = 0;
let touchScrollLeft = 0;
function onSliderTouchStart(e) {
  if (!sponsorSliderRef.value || !e.touches[0]) return;
  isMouseDown.value = true;
  touchStartX = e.touches[0].pageX - sponsorSliderRef.value.offsetLeft;
  touchScrollLeft = sponsorSliderRef.value.scrollLeft;
}

function onSliderTouchMove(e) {
  if (!isMouseDown.value || !sponsorSliderRef.value || !e.touches[0]) return;
  const el = sponsorSliderRef.value;
  const x = e.touches[0].pageX - el.offsetLeft;
  const walk = (x - touchStartX) * 1.2;
  let newScrollLeft = touchScrollLeft - walk;
  const setWidth = getSetWidth();

  if (setWidth > 0) {
    if (newScrollLeft >= setWidth * 2.8) {
      newScrollLeft -= setWidth;
      touchScrollLeft -= setWidth;
    } else if (newScrollLeft <= setWidth * 0.2) {
      newScrollLeft += setWidth;
      touchScrollLeft += setWidth;
    }
  }

  el.scrollLeft = newScrollLeft;
}

function onSliderTouchEnd() {
  isMouseDown.value = false;
}

// Automatic slide step every 3.5 seconds
function startAutoSlide() {
  stopAutoSlide();
  sponsorAutoSlideTimer = setInterval(() => {
    if (!isSponsorPaused.value && !isSponsorHovered.value && !isMouseDown.value) {
      slideNext();
    }
  }, 3500);
}

function stopAutoSlide() {
  if (sponsorAutoSlideTimer) {
    clearInterval(sponsorAutoSlideTimer);
    sponsorAutoSlideTimer = null;
  }
}

function toggleSponsorAutoPlay() {
  isSponsorPaused.value = !isSponsorPaused.value;
  if (!isSponsorPaused.value) {
    startAutoSlide();
  }
}

// ==========================================================================
// 7. Event Gallery (Konfer Style 2-Row Counter Slider)
// ==========================================================================
const isGalleryPaused = ref(false);

function toggleGalleryPause() {
  isGalleryPaused.value = !isGalleryPaused.value;
}

const defaultGalleryPhotos = [
  // Baris 1: Index 0..3 (Berjalan ke Kiri)
  {
    id: 1,
    title: 'Selebrasi Penganugerahan Juara Umum Bergilir',
    categoryLabel: 'Juara & Awarding',
    pillClass: 'pill-gold',
    iconClass: 'ri-trophy-fill',
    src: '/assets/img/gallery/gallery_awarding.jpg',
    location: 'Panggung Utama Semarak Aliyah',
    tag: 'Grand Awarding Ceremony',
    desc: 'Momen puncak penyerahan piala bergilir kehormatan Kementerian Agama dan uang pembinaan kepada kontingen madrasah berprestasi.',
    row: 1,
  },
  {
    id: 2,
    title: 'Arena Grand Final Mobile Legends Championship',
    categoryLabel: 'E-Sport Arena',
    pillClass: 'pill-purple',
    iconClass: 'ri-gamepad-fill',
    src: '/assets/img/gallery/gallery_esport.jpg',
    location: 'E-Sport Center Maswahas',
    tag: 'Mobile Legends Bang Bang',
    desc: 'Duel sengit perebutan tahta Land of Dawn antartim SMP/MTs terbaik se-Kabupaten Jember dengan tata panggung turnamen berstandar esports.',
    row: 1,
  },
  {
    id: 3,
    title: 'Konsentrasi Tinggi Babak Final Olimpiade Sains',
    categoryLabel: 'Olimpiade Sains',
    pillClass: 'pill-blue',
    iconClass: 'ri-flask-fill',
    src: '/assets/img/gallery/gallery_olimpiade.jpg',
    location: 'Auditorium Sains Terpadu',
    tag: 'Olimpiade SMP/MTs',
    desc: 'Ratusan saintis belia adu ketelitian dan kecerdasan dalam menyelesaikan studi kasus sains, matematika terapan, dan logika analitis.',
    row: 1,
  },
  {
    id: 4,
    title: 'Penghayatan Pentas Storytelling & Pidato Bahasa',
    categoryLabel: 'Bahasa & Sastra',
    pillClass: 'pill-pink',
    iconClass: 'ri-mic-fill',
    src: '/assets/img/gallery/gallery_storytelling.jpg',
    location: 'Stage Kreasi Bahasa',
    tag: 'Storytelling & Speech',
    desc: 'Penampilan memukau bertutur cerita bahasa Inggris dan pidato bahasa Arab penuh ekspresi, artikulasi tajam, dan kostum teatrikal tematik.',
    row: 1,
  },

  // Baris 2: Index 4..7 (Berjalan ke KANAN)
  {
    id: 5,
    title: 'Gema Shalawat Festival Al-Banjari Se-Jawa Timur',
    categoryLabel: 'Seni Islami & Budaya',
    pillClass: 'pill-green',
    iconClass: 'ri-music-2-fill',
    src: '/assets/img/gallery/gallery_albanjari.jpg',
    location: 'Panggung Terbuka Plaza',
    tag: 'Festival Banjari Jatim',
    desc: 'Harmoni tabuhan terbang dan lantunan syair shalawat merdu menyatukan syiar Islam dan keindahan kesenian tradisi nusantara.',
    row: 2,
  },
  {
    id: 6,
    title: 'Smash Mematikan di Final Bola Voli Putri',
    categoryLabel: 'Sportivitas Voli',
    pillClass: 'pill-orange',
    iconClass: 'ri-football-fill',
    src: '/assets/img/gallery/gallery_volleyball.jpg',
    location: 'Lapangan Utama Maswahas',
    tag: 'Turnamen Voli Pelajar',
    desc: 'Aksi heroik penyelamatan bola dan lonjakan spike tajam memukau ratusan pasang mata penonton di pinggir lapangan.',
    row: 2,
  },
  {
    id: 7,
    title: 'Vokal Emas Festival Solo Singer Lagu Islami',
    categoryLabel: 'Seni Vokal Islami',
    pillClass: 'pill-purple',
    iconClass: 'ri-voiceprint-fill',
    src: '/assets/img/gallery/gallery_singer.jpg',
    location: 'Teater Tertutup Maswahas',
    tag: 'Solo Song Competition',
    desc: 'Penjiwaan mendalam dan teknik olah vokal spektakuler peserta solo singer dalam melantunkan tembang religi kontemporer.',
    row: 2,
  },
  {
    id: 8,
    title: 'Gemuruh Sorak & Yel-Yel Suporter Madrasah',
    categoryLabel: 'Suporter & Fairplay',
    pillClass: 'pill-gold',
    iconClass: 'ri-flag-fill',
    src: '/assets/img/gallery/gallery_supporters.jpg',
    location: 'Tribun Gelanggang Olahraga',
    tag: 'Supporter of the Year',
    desc: 'Semangat kebersamaan dan yel-yel kreatif suporter madrasah yang terus bergelora memberikan energi tanpa henti bagi para atlet.',
    row: 2,
  }
];

const effectiveGallerySettings = computed(() => ({
  tag: props.gallerySettings?.tag || 'Galeri Dokumentasi',
  title: props.gallerySettings?.title || 'Momen Spektakuler & Kilas Balik',
  desc: props.gallerySettings?.desc || 'Saksikan sorak kemeriahan, kobaran semangat sportivitas talenta muda, dan kilas balik keseruan panggung Semarak Aliyah.',
  is_active: props.gallerySettings?.is_active !== false,
}));

const allGalleryPhotos = computed(() => {
  if (props.galleries && props.galleries.length > 0) {
    return props.galleries;
  }
  return defaultGalleryPhotos;
});

const row1Photos = computed(() => {
  const fromRow1 = allGalleryPhotos.value.filter(p => p.row === 1);
  if (fromRow1.length > 0) return fromRow1;
  const half = Math.ceil(allGalleryPhotos.value.length / 2);
  return allGalleryPhotos.value.slice(0, half);
});

const row2Photos = computed(() => {
  const fromRow2 = allGalleryPhotos.value.filter(p => p.row === 2);
  if (fromRow2.length > 0) return fromRow2;
  const half = Math.ceil(allGalleryPhotos.value.length / 2);
  return allGalleryPhotos.value.slice(half);
});

// Triplicated arrays for continuous seamless CSS infinite marquee
const row1MarqueeList = computed(() => {
  const list = row1Photos.value;
  if (!list.length) return [];
  if (list.length < 4) {
    return [...list, ...list, ...list, ...list, ...list, ...list];
  }
  return [...list, ...list, ...list];
});

const row2MarqueeList = computed(() => {
  const list = row2Photos.value;
  if (!list.length) return [];
  if (list.length < 4) {
    return [...list, ...list, ...list, ...list, ...list, ...list];
  }
  return [...list, ...list, ...list];
});

// Lightbox modal state
const lightboxOpen = ref(false);
const activePhotoIndex = ref(0);

const currentPhoto = computed(() => {
  return allGalleryPhotos.value[activePhotoIndex.value] || allGalleryPhotos.value[0] || {};
});

function openLightboxByPhoto(photo) {
  const idx = allGalleryPhotos.value.findIndex(p => p.id === photo.id);
  activePhotoIndex.value = idx >= 0 ? idx : 0;
  lightboxOpen.value = true;
  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleLightboxKey);
  }
}

function openLightbox(index) {
  activePhotoIndex.value = index;
  lightboxOpen.value = true;
  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleLightboxKey);
  }
}

function closeLightbox() {
  lightboxOpen.value = false;
  if (typeof window !== 'undefined') {
    window.removeEventListener('keydown', handleLightboxKey);
  }
}

function prevPhoto() {
  const total = allGalleryPhotos.value.length;
  if (!total) return;
  activePhotoIndex.value = (activePhotoIndex.value - 1 + total) % total;
}

function nextPhoto() {
  const total = allGalleryPhotos.value.length;
  if (!total) return;
  activePhotoIndex.value = (activePhotoIndex.value + 1) % total;
}

function handleLightboxKey(e) {
  if (e.key === 'Escape') closeLightbox();
  if (e.key === 'ArrowLeft') prevPhoto();
  if (e.key === 'ArrowRight') nextPhoto();
}
</script>

<template>
  <PublicLayout>
    <Head :title="(school.event_name || 'SEMARAK ALIYAH XIV 2026') + ' - ' + (school.school_name || 'MA Unggulan Wahid Hasyim Balung')" />

    <!-- ==========================================================================
         HERO SECTION
         ========================================================================== -->
    <section class="hero-section" id="beranda" ref="heroSectionRef">
      <!-- Interactive JavaScript Animated Canvas Background -->
      <canvas ref="heroCanvasRef" class="hero-canvas-bg" aria-hidden="true"></canvas>

      <div class="container">
        <div class="hero-grid">
          <!-- Left Column: Headlines & Countdown -->
          <div class="hero-content">
            <div class="hero-badge-wrap">
              <span class="hero-badge">
                <span v-if="heroSettings?.hero_badge_pulse !== false" class="pulse-dot"></span>
                {{ heroSettings?.hero_badge_text || 'Ajang Kompetisi Pelajar SMP/MTs Se-Kabupaten Jember' }}
              </span>
            </div>

            <h1 class="hero-title">
              {{ heroSettings?.hero_headline_prefix || 'Selamat Datang di Laman Official' }} <br>
              <span class="text-gradient-gold">
                {{ heroSettings?.hero_headline_highlight || (school.event_name || 'Semarak Aliyah XIV 2026') }}
              </span>
            </h1>

            <p class="hero-subtitle">
              {{ heroSettings?.hero_subheadline || ('Panggung unjuk prestasi, bakat seni, kecerdasan sains, dan sportivitas pelajar SMP/MTs sederajat se-Kabupaten Jember. Raih Piala Juara, Bantuan Uang Pembinaan, serta Beasiswa Tabungan Pendidikan di ' + (school.school_name || 'MA Unggulan Wahid Hasyim Balung') + '.') }}
            </p>

            <!-- Live Countdown Timer -->
            <div v-if="kustomisasi.countdown_active !== false" class="countdown-container">
              <div class="countdown-header">
                <span class="countdown-label">
                  <svg width="18" height="18" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>
                  {{ kustomisasi.countdown_title || 'Menuju Hari Pelaksanaan Lomba' }}
                </span>
                <span class="countdown-date">{{ formattedCountdownDate }}</span>
              </div>
              <div v-if="isCountdownEnded && kustomisasi.countdown_ended_text" class="countdown-ended-alert py-2 text-center text-warning fw-semibold fs-14">
                {{ kustomisasi.countdown_ended_text }}
              </div>
              <div v-else class="countdown-timer">
                <div class="timer-box">
                  <div class="timer-value">{{ days }}</div>
                  <div class="timer-unit">Hari</div>
                </div>
                <div class="timer-box">
                  <div class="timer-value">{{ hours }}</div>
                  <div class="timer-unit">Jam</div>
                </div>
                <div class="timer-box">
                  <div class="timer-value">{{ minutes }}</div>
                  <div class="timer-unit">Menit</div>
                </div>
                <div v-if="kustomisasi.countdown_show_seconds !== false" class="timer-box">
                  <div class="timer-value">{{ seconds }}</div>
                  <div class="timer-unit">Detik</div>
                </div>
              </div>
            </div>

            <!-- CTA Buttons -->
            <div class="hero-cta-group">
              <Link 
                v-if="heroSettings?.hero_cta1_active !== false"
                :href="heroSettings?.hero_cta1_url || ctaRegisterUrl" 
                class="btn-primary"
              >
                <i class="ri-user-follow-line"></i>
                <span>{{ heroSettings?.hero_cta1_text || 'Daftar Sekarang' }}</span>
              </Link>
              <a 
                v-if="heroSettings?.hero_cta2_active !== false"
                :href="heroSettings?.hero_cta2_url || ($page.props.generalJuknis?.url || '/assets/JUKNIS-SEMARAK-2026.pdf')" 
                :download="$page.props.generalJuknis?.filename || 'JUKNIS-SEMARAK-2026.pdf'" 
                target="_blank" 
                class="btn-secondary"
              >
                <i class="ri-file-download-line"></i>
                <span>{{ heroSettings?.hero_cta2_text || 'Unduh Juknis (PDF)' }}</span>
              </a>
              <a 
                v-if="heroSettings?.hero_cta3_active !== false"
                :href="heroSettings?.hero_cta3_url || '#kompetisi'" 
                class="btn-secondary"
              >
                <i class="ri-search-line"></i>
                <span>{{ heroSettings?.hero_cta3_text || 'Lihat Cabang Lomba' }}</span>
              </a>
            </div>
          </div>

          <!-- Right Column: Visual Poster & Mascot Art -->
          <div class="hero-visual">
            <div class="hero-visual-wrapper">
              <div class="hero-main-card">
                <img :src="heroSettings?.hero_poster_image || '/assets/img/pamflet.jpg'" alt="Pamflet Resmi Semarak Aliyah XIV 2026" class="hero-poster-img" loading="eager">
              </div>

              <!-- Floating Character Mascot -->
              <img 
                v-if="heroSettings?.hero_mascot_active !== false"
                :src="heroSettings?.hero_mascot_image || '/assets/img/kadita.png'" 
                alt="Mascot Semarak Aliyah" 
                class="hero-character-float"
              >

              <!-- Floating Info Pill -->
              <div v-if="heroSettings?.hero_float_badge_active !== false" class="hero-badge-float">
                <div class="badge-float-icon">
                  <i :class="heroSettings?.hero_float_badge_icon || 'ri-trophy-line'"></i>
                </div>
                <div>
                  <div class="badge-float-title">{{ heroSettings?.hero_float_badge_title || 'Hadiah Jutaan Rupiah' }}</div>
                  <div class="badge-float-desc">{{ heroSettings?.hero_float_badge_desc || '+ Beasiswa Pendidikan' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==========================================================================
           RUNNING TEXT TICKER STRIP (Inside Hero Section Area)
           ========================================================================== -->
      <div 
        v-if="tickerSettings?.is_active !== false && displayTickerItems.length > 0" 
        class="konfer-ticker-strip hero-ticker-strip" 
        :class="{ 'pause-on-hover': tickerSettings?.pause_on_hover !== false }"
        style="background: transparent !important; background-color: transparent !important; border: none !important; box-shadow: none !important;"
        aria-label="Sorotan Utama Acara"
      >
        <div 
          class="konfer-ticker-track" 
          :style="tickerComputedStyles"
        >
          <div class="konfer-ticker-content">
            <template v-for="(item, idx) in displayTickerItems" :key="'orig-' + idx">
              <span 
                class="ticker-text" 
                :class="[
                  item.style === 'gradient' ? 'filled gradient-text' : 
                  item.style === 'outline' ? 'outline' : 'filled'
                ]"
              >
                {{ item.text }}
              </span>
              <span class="ticker-star">{{ tickerSettings?.separator || '✦' }}</span>
            </template>
          </div>
          <!-- Duplikasi untuk looping mulus tak terputus -->
          <div class="konfer-ticker-content" aria-hidden="true">
            <template v-for="(item, idx) in displayTickerItems" :key="'dup-' + idx">
              <span 
                class="ticker-text" 
                :class="[
                  item.style === 'gradient' ? 'filled gradient-text' : 
                  item.style === 'outline' ? 'outline' : 'filled'
                ]"
              >
                {{ item.text }}
              </span>
              <span class="ticker-star">{{ tickerSettings?.separator || '✦' }}</span>
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         STATS STRIP
         ========================================================================== -->
    <section v-if="statsSettings?.is_active !== false" class="stats-strip" aria-label="Statistik Acara">
      <div class="container">
        <div class="stats-grid">
          <div 
            v-for="(item, idx) in displayStatsItems" 
            :key="idx" 
            class="stat-item"
          >
            <div class="stat-icon"><i :class="item.icon || 'ri-focus-2-line'"></i></div>
            <div>
              <div class="stat-number">{{ item.number }}</div>
              <div class="stat-label">{{ item.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         TENTANG & HADIAH (BENEFITS)
         ========================================================================== -->
    <section v-if="aboutSettings?.is_active !== false" class="section-padding" id="tentang">
      <div class="container">
        <div class="section-header">
          <span class="section-tag">{{ aboutSettings?.tag || 'Apresiasi & Fasilitas' }}</span>
          <h2 class="section-title">{{ aboutSettings?.title || 'Dapatkan Hadiah Bergengsi & Beasiswa Pendidikan' }}</h2>
          <p class="section-desc">
            {{ aboutSettings?.desc || ((school.event_name || 'Semarak Aliyah XIV 2026') + ' diselenggarakan oleh ' + (school.school_name || 'MA Unggulan Wahid Hasyim Balung') + ' sebagai bentuk apresiasi nyata bagi generasi muda yang berprestasi dan berkarakter.') }}
          </p>
        </div>

        <div class="prizes-grid">
          <div 
            v-for="(prize, idx) in displayAboutPrizes" 
            :key="idx" 
            class="prize-card"
          >
            <div class="prize-card-icon"><i :class="prize.icon || 'ri-trophy-line'"></i></div>
            <h3 class="prize-card-title">{{ prize.title }}</h3>
            <p class="prize-card-desc">{{ prize.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         BIAYA & SKEMA REGISTRASI
         ========================================================================== -->
    <section class="section-padding section-biaya" id="biaya">
      <div class="container">
        <div class="section-header">
          <span class="section-tag">Investasi Prestasi</span>
          <h2 class="section-title">Biaya Registrasi {{ school.event_name || 'Semarak Aliyah 2026' }}</h2>
          <p class="section-desc">Pilihan skema pendaftaran hemat dan fleksibel untuk sekolah dan madrasah Anda.</p>
        </div>

        <div class="pricing-grid">
          <!-- Paket Satuan / Fleksibel -->
          <div class="pricing-card">
            <div>
              <div class="pricing-top-bar">
                <div class="pricing-card-icon icon-satuan">
                  <i class="ri-price-tag-3-fill"></i>
                </div>
                <span class="pricing-tier-pill tier-orange">Skema Fleksibel</span>
              </div>

              <div class="pricing-header">
                <h3 class="pricing-card-title">Paket Fleksibel / Satuan</h3>
                <div class="pricing-price-box">
                  <div class="price-original-row">
                    <span class="price-sub-label"><i class="ri-information-line"></i> Pilihan Satuan Lomba</span>
                  </div>
                  <div class="price-main-row">
                    <span class="price-currency">Rp</span>
                    <span class="price-number">50.000</span>
                    <span class="price-frequency">/ cabang lomba</span>
                  </div>
                </div>
                <p class="pricing-card-desc">
                  Ideal bagi sekolah atau madrasah yang ingin mendelegasikan siswa pada 1 atau beberapa cabang lomba tertentu secara fleksibel.
                </p>
              </div>

              <div class="pricing-features-list">
                <div class="pricing-feature-row">
                  <span class="pricing-feature-icon icon-green"><i class="ri-check-line"></i></span>
                  <span>Biaya registrasi setiap 1 cabang lomba pilihan</span>
                </div>
                <div class="pricing-feature-row">
                  <span class="pricing-feature-icon icon-green"><i class="ri-check-line"></i></span>
                  <span>Sudah termasuk kuota maksimal peserta sesuai ketentuan juknis</span>
                </div>
                <div class="pricing-feature-row">
                  <span class="pricing-feature-icon icon-green"><i class="ri-check-line"></i></span>
                  <span>E-Sertifikat resmi ber-barcode untuk seluruh peserta & guru pembimbing</span>
                </div>
                <div class="pricing-feature-row">
                  <span class="pricing-feature-icon icon-green"><i class="ri-check-line"></i></span>
                  <span>Fasilitas ID Card resmi dan akses Technical Meeting (Offline & Zoom)</span>
                </div>
                <div class="pricing-feature-row">
                  <span class="pricing-feature-icon icon-green"><i class="ri-check-line"></i></span>
                  <span>Konsultasi dan panduan teknis langsung via Helpdesk Panitia</span>
                </div>
              </div>
            </div>

            <Link :href="ctaRegisterUrl" class="pricing-action-btn btn-satuan">
              <span>Pilih Cabang Lomba Satuan</span>
              <i class="ri-arrow-right-line"></i>
            </Link>
          </div>

          <!-- Paket All-In Komplit -->
          <div class="pricing-card featured" v-if="pricingSettings?.allin_is_active !== false">
            <div>
              <div class="pricing-top-bar">
                <div class="pricing-card-icon icon-allin">
                  <i class="ri-vip-crown-fill"></i>
                </div>
                <span class="pricing-ribbon-tag">
                  <i class="ri-fire-fill"></i> {{ pricingSettings?.allin_tag || 'Paling Hemat • Rekomendasi' }}
                </span>
              </div>

              <div class="pricing-header">
                <h3 class="pricing-card-title">{{ pricingSettings?.allin_name || 'Paket All-In Komplit' }}</h3>
                <div class="pricing-price-box">
                  <div class="price-original-row">
                    <span class="price-original-strike">Rp {{ (pricingSettings?.allin_original_price || 400000).toLocaleString('id-ID') }}</span>
                    <span class="price-savings-badge">
                      <i class="ri-discount-percent-fill"></i> Hemat Rp {{ Math.max(0, (pricingSettings?.allin_original_price || 400000) - (pricingSettings?.allin_price || 200000)).toLocaleString('id-ID') }}
                    </span>
                  </div>
                  <div class="price-main-row">
                    <span class="price-currency">Rp</span>
                    <span class="price-number">{{ (pricingSettings?.allin_price || 200000).toLocaleString('id-ID') }}</span>
                    <span class="price-frequency">/ seluruh cabang lomba</span>
                  </div>
                </div>
                <p class="pricing-card-desc">
                  {{ pricingSettings?.allin_desc || 'Pilihan terbaik & paling hemat! Daftarkan kontingen sekolah Anda ke seluruh 8 cabang lomba sekaligus hanya dengan setengah harga.' }}
                </p>
              </div>

              <div class="pricing-features-list">
                <template v-if="pricingSettings?.allin_features && pricingSettings.allin_features.length > 0">
                  <div 
                    v-for="(f, fIdx) in pricingSettings.allin_features" 
                    :key="fIdx"
                    class="pricing-feature-row"
                  >
                    <span class="pricing-feature-icon icon-purple"><i class="ri-check-double-line"></i></span>
                    <span>{{ f }}</span>
                  </div>
                </template>
                <template v-else>
                  <div class="pricing-feature-row">
                    <span class="pricing-feature-icon icon-purple"><i class="ri-check-double-line"></i></span>
                    <span><strong>Bebas daftarkan kontingen di seluruh 8 cabang lomba</strong></span>
                  </div>
                  <div class="pricing-feature-row">
                    <span class="pricing-feature-icon icon-purple"><i class="ri-check-double-line"></i></span>
                    <span><strong>Hemat biaya pendaftaran total hingga Rp 200.000,- (50%)</strong></span>
                  </div>
                  <div class="pricing-feature-row">
                    <span class="pricing-feature-icon icon-purple"><i class="ri-check-double-line"></i></span>
                    <span>Prioritas verifikasi berkas cepat & reservasi kuota kontingen</span>
                  </div>
                  <div class="pricing-feature-row">
                    <span class="pricing-feature-icon icon-purple"><i class="ri-check-double-line"></i></span>
                    <span>Berhak memperebutkan <strong>Piala Bergilir Juara Umum Semarak</strong></span>
                  </div>
                  <div class="pricing-feature-row">
                    <span class="pricing-feature-icon icon-purple"><i class="ri-check-double-line"></i></span>
                    <span>E-Sertifikat resmi ber-barcode untuk seluruh siswa & guru pembimbing</span>
                  </div>
                </template>
              </div>
            </div>

            <Link :href="ctaRegisterUrl" class="pricing-action-btn btn-allin">
              <i class="ri-flashlight-fill"></i>
              <span>Daftar Paket All-In Sekarang</span>
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         DAFTAR CABANG KOMPETISI (CATALOG)
         ========================================================================== -->
    <section class="section-padding" id="kompetisi">
      <div class="container">
        <div class="section-header">
          <span class="section-tag">Pilihan Lomba</span>
          <h2 class="section-title">Cabang Perlombaan {{ school.event_name || 'Semarak Aliyah XIV 2026' }}</h2>
          <p class="section-desc">Pilih cabang kompetisi sesuai minat, bakat akademik, religi, seni, atau olahraga delegasi madrasah/sekolah Anda.</p>
        </div>

        <!-- Filter Buttons -->
        <div class="comp-filter-tabs">
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeFilter === 'all' }"
            @click="activeFilter = 'all'"
          >
            Semua Cabang
          </button>
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeFilter === 'akademik' }"
            @click="activeFilter = 'akademik'"
          >
            Akademik & Sains
          </button>
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeFilter === 'seni' }"
            @click="activeFilter = 'seni'"
          >
            Bahasa, Seni & Religi
          </button>
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeFilter === 'olahraga' }"
            @click="activeFilter = 'olahraga'"
          >
            Olahraga & E-Sport
          </button>
        </div>

        <!-- Competitions Dynamic Grid -->
        <div class="competitions-grid" id="competitions-grid">
          <div 
            v-for="comp in filteredCompetitions" 
            :key="comp.id" 
            class="comp-card"
          >
            <div class="comp-card-top">
              <div class="comp-card-icon">
                <i :class="getCompIconClass(comp)"></i>
              </div>
              <div>
                <span class="comp-card-category">{{ comp.category || 'Kompetisi' }}</span>
                <h3 class="comp-card-title">{{ comp.title }}</h3>
              </div>
            </div>

            <p class="comp-card-desc">
              {{ comp.description }}
            </p>

            <div class="comp-card-meta">
              <div class="meta-item">
                <span class="meta-label">Aturan :</span>
                <span class="meta-val">{{ comp.participants_rule || 'Sesuai Juknis' }}</span>
              </div>
              <div class="meta-item comp-meta-fee-item">
                <span class="meta-label">Biaya :</span>
                <div class="meta-val comp-fee-box">
                  <span class="comp-fee-amount">
                    Rp {{ Number(comp.fee || 50000).toLocaleString('id-ID') }}
                    <span class="comp-fee-unit">/ lomba</span>
                  </span>
                  <span class="comp-fee-package-badge" title="Tersedia di Paket All-In Komplit seharga Rp 200.000 untuk seluruh cabang lomba">
                    <i class="ri-vip-crown-line"></i> Tersedia di Paket Lengkap
                  </span>
                </div>
              </div>
              <div class="meta-item">
                <span class="meta-label">Kuota :</span>
                <span class="meta-val">
                  <span 
                    :class="[
                      'comp-status-badge',
                      getCompStatusInfo(comp).badgeClass
                    ]"
                  >
                    <i :class="getCompStatusInfo(comp).iconClass"></i>
                    {{ getCompStatusInfo(comp).text }}
                  </span>
                </span>
              </div>
              <div v-if="comp.registration_type === 'direct_contact'" class="meta-item">
                <span class="meta-label">Metode :</span>
                <span class="meta-val">
                  <span class="badge bg-success-subtle text-success fs-10 border border-success-subtle d-inline-flex align-items-center gap-1">
                    <i class="ri-whatsapp-line"></i> Kontak Koordinator
                  </span>
                </span>
              </div>
            </div>

            <div class="comp-card-actions">
              <button 
                type="button" 
                class="btn-card-detail" 
                @click="openDetailModal(comp)"
              >
                <i class="ri-eye-line"></i>
                Detail
              </button>
              <!-- Tombol khusus jika pendaftaran hanya via kontak langsung ke koordinator -->
              <a 
                v-if="comp.registration_type === 'direct_contact'"
                :href="getDirectRegistrationWaUrl(comp)" 
                target="_blank" 
                rel="noopener"
                class="btn-card-register"
                style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; border: none; text-decoration: none;"
                title="Daftar langsung melalui kontak WhatsApp Koordinator Lomba"
              >
                <i class="ri-whatsapp-line"></i>
                <span>Daftar via PJ</span>
              </a>
              <Link 
                v-else-if="!getCompStatusInfo(comp).isClosed"
                :href="getCompRegisterUrl(comp.id)" 
                class="btn-card-register"
              >
                <i class="ri-flashlight-line"></i>
                Daftar
              </Link>
              <button 
                v-else
                type="button"
                disabled
                class="btn-card-register btn-card-disabled" 
                style="background: #334155; color: #94a3b8; font-size: 0.8rem; border: 1px solid #475569;"
                :title="getCompStatusInfo(comp).isManuallyClosed ? 'Pendaftaran ditutup oleh panitia' : 'Kuota peserta utama telah terpenuhi (Waiting List)'"
              >
                <i :class="getCompStatusInfo(comp).isManuallyClosed ? 'ri-lock-line' : 'ri-time-line'"></i>
                <span>{{ getCompStatusInfo(comp).isManuallyClosed ? 'Ditutup' : 'Waiting List' }}</span>
              </button>
            </div>
          </div>
        </div>

        <div style="text-align: center; margin-top: 45px;">
          <Link :href="ctaRegisterUrl" class="btn-primary">
            <i class="ri-dashboard-line"></i>
            <span>Kunjungi Portal Layanan Registrasi Lengkap</span>
          </Link>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         TECHNICAL MEETING & JADWAL
         ========================================================================== -->
    <section v-if="tmScheduleSettings?.is_active !== false" class="section-padding section-jadwal" id="jadwal">
      <div class="container">
        <div class="section-header">
          <span class="section-tag">
            <i class="ri-calendar-event-fill text-gold"></i> {{ tmScheduleSettings?.tag || 'Agenda Pelaksanaan' }}
          </span>
          <h2 class="section-title">{{ tmScheduleSettings?.title || 'Jadwal Technical Meeting & Pelaksanaan' }}</h2>
          <p class="section-desc">{{ tmScheduleSettings?.desc || 'Harap diperhatikan oleh guru pendamping dan ketua delegasi sekolah mengenai tanggal, jam, ruangan, serta agenda pelaksanaan TM tiap cabang lomba.' }}</p>
        </div>

        <!-- TM Guidelines / Important Notice Banner -->
        <div v-if="tmScheduleSettings?.guidelines_active !== false && effectiveTmGuidelines && effectiveTmGuidelines.length > 0" class="tm-guide-banner">
          <div class="tm-guide-grid">
            <div 
              v-for="(guide, gIdx) in effectiveTmGuidelines" 
              :key="gIdx" 
              class="tm-guide-item"
            >
              <div 
                class="tm-guide-icon"
                :style="guide.color ? { color: guide.color, background: hexToRgba(guide.color, 0.12), borderColor: hexToRgba(guide.color, 0.28) } : {}"
              >
                <i :class="guide.icon || 'ri-information-line'"></i>
              </div>
              <div class="tm-guide-content">
                <h4>{{ guide.title }}</h4>
                <p>{{ guide.desc }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- TM Category Filter Tabs -->
        <div class="tm-filter-tabs">
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeTmFilter === 'all' }"
            @click="activeTmFilter = 'all'"
          >
            Semua Jadwal TM
          </button>
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeTmFilter === 'akademik' }"
            @click="activeTmFilter = 'akademik'"
          >
            Akademik & Sains
          </button>
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeTmFilter === 'seni' }"
            @click="activeTmFilter = 'seni'"
          >
            Bahasa, Seni & Religi
          </button>
          <button 
            type="button" 
            class="filter-btn" 
            :class="{ active: activeTmFilter === 'olahraga' }"
            @click="activeTmFilter = 'olahraga'"
          >
            Olahraga & E-Sport
          </button>
        </div>

        <!-- Dynamic Schedule List (Elegant Agenda List) -->
        <div class="schedule-list">
          <div 
            v-for="comp in filteredTmCompetitions" 
            :key="'tm-' + comp.id"
            class="schedule-list-item"
          >
            <!-- Left: Time & Icon Column -->
            <div class="schedule-list-time-col">
              <div class="schedule-list-icon">
                <i :class="getCompIconClass(comp)"></i>
              </div>
              <div class="schedule-list-time-badge">
                <i class="ri-calendar-event-line"></i>
                <span>{{ comp.tm_date && comp.tm_date !== '-' ? comp.tm_date : '26 Oktober 2026 • 08:00 WIB' }}</span>
              </div>
            </div>

            <!-- Center: Title & Meta Info -->
            <div class="schedule-list-body">
              <div class="schedule-list-tags">
                <span class="comp-card-category" style="margin: 0;">
                  {{ comp.category_label || comp.category || 'Kompetisi' }}
                </span>
                <span 
                  class="schedule-status-pill"
                  :class="getCompStatusInfo(comp).isClosed ? 'status-closed' : 'status-open'"
                >
                  <span class="status-dot"></span>
                  {{ getCompStatusInfo(comp).isClosed ? (getCompStatusInfo(comp).isManuallyClosed ? 'Ditutup' : 'Waiting List') : 'Pendaftaran Dibuka' }}
                </span>
              </div>

              <h3 class="schedule-list-title">
                Technical Meeting {{ comp.title }}
              </h3>

              <div class="schedule-list-meta-grid">
                <div class="schedule-meta-item">
                  <i class="ri-map-pin-2-fill text-warning"></i>
                  <div>
                    <span class="meta-label">Tempat / Ruangan TM</span>
                    <span class="meta-val">{{ getCompTmLocation(comp) }}</span>
                  </div>
                </div>

                <div class="schedule-meta-item">
                  <i class="ri-calendar-check-fill text-primary"></i>
                  <div>
                    <span class="meta-label">Agenda Utama TM</span>
                    <span class="meta-val">{{ getCompTmAgenda(comp) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="schedule-list-actions">
              <button 
                type="button" 
                class="btn-tm-detail"
                @click="openDetailModal(comp)"
              >
                <i class="ri-information-line"></i>
                <span>Detail & Juknis</span>
              </button>
              <Link 
                v-if="!getCompStatusInfo(comp).isClosed"
                :href="getCompRegisterUrl(comp.id)" 
                class="btn-tm-register"
              >
                <i class="ri-flashlight-line"></i>
                <span>Daftar</span>
              </Link>
              <button 
                v-else
                type="button"
                disabled
                class="btn-tm-register btn-tm-disabled" 
                :title="getCompStatusInfo(comp).isManuallyClosed ? 'Pendaftaran ditutup oleh panitia' : 'Kuota telah penuh (Waiting List)'"
              >
                <i :class="getCompStatusInfo(comp).isManuallyClosed ? 'ri-lock-line' : 'ri-time-line'"></i>
                <span>{{ getCompStatusInfo(comp).isManuallyClosed ? 'Ditutup' : 'Waiting List' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         UNDUH JUKNIS & DOKUMEN RESMI
         ========================================================================== -->
    <section class="section-padding" id="juknis">
      <div class="container">
        <div class="section-juknis-banner">
          <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px;">
            <div style="max-width: 650px;">
              <span class="section-tag">Buku Panduan Lomba</span>
              <h2 style="font-size: 1.45rem; margin: 8px 0 10px;">Unduh Petunjuk Teknis & Ketentuan Lengkap</h2>
              <p style="color: var(--text-secondary); font-size: 0.92rem; line-height: 1.6;">
                Pelajari rincian tata tertib, kisi-kisi materi olimpiade, lagu wajib albanjari & singer, bagan pertandingan voli, serta aturan turnamen e-sport melalui dokumen resmi Juknis {{ school.event_name || 'Semarak Aliyah XIV 2026' }}.
              </p>
            </div>
            <div>
              <a 
                :href="$page.props.generalJuknis?.url || '/assets/JUKNIS-SEMARAK-2026.pdf'" 
                :download="$page.props.generalJuknis?.filename || 'JUKNIS-SEMARAK-2026.pdf'" 
                target="_blank"
                class="btn-primary" 
              >
                <i class="ri-file-download-line"></i>
                <span>Unduh Juknis Resmi (PDF)</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         MITRA & SPONSOR RESMI (SLIDER / MARQUEE OTOMATIS)
         ========================================================================== -->
    <section v-if="effectiveSponsorSettings.is_active" class="section-padding section-sponsors" id="sponsor">
      <div class="container">
        <div class="section-header">
          <span class="section-tag">
            <i class="ri-shake-hands-line"></i> {{ effectiveSponsorSettings.tag }}
          </span>
          <h2 class="section-title">{{ effectiveSponsorSettings.title }}</h2>
          <p class="section-desc">
            {{ effectiveSponsorSettings.desc }}
          </p>
        </div>
      </div>

      <!-- Sponsor Interactive Slider (Auto-shift every 3.5s + Mouse Drag with Grab Hand Cursor) -->
      <div 
        class="sponsor-slider-wrapper"
        @mouseenter="isSponsorHovered = true"
        @mouseleave="isSponsorHovered = false"
      >
        <!-- Fade masks at left & right edges -->
        <div class="sponsor-marquee-fade left"></div>
        <div class="sponsor-marquee-fade right"></div>

        <!-- Draggable Horizontal Track with Hand/Grab Cursor -->
        <div 
          ref="sponsorSliderRef"
          class="sponsor-slider-track"
          :class="{ 'is-dragging': isDragging }"
          @scroll.passive="onSliderScroll"
          @mousedown="onSliderMouseDown"
          @mousemove="onSliderMouseMove"
          @mouseup="onSliderMouseUp"
          @mouseleave="onSliderMouseLeave"
          @touchstart="onSliderTouchStart"
          @touchmove="onSliderTouchMove"
          @touchend="onSliderTouchEnd"
        >
          <div 
            v-for="(sp, sIdx) in doubledSponsors" 
            :key="sIdx"
            class="sponsor-card"
            :class="{ 'is-logo-only': sp.only_logo || sp.is_logo_only || (!sp.name && sp.logo) }"
            :style="sp.only_logo || sp.is_logo_only ? { borderColor: sp.borderLight } : {}"
          >
            <!-- 1. JIKA HANYA MENYERTAKAN LOGO: Icon, Nama, dan Deskripsi Otomatis Disembunyikan (Fullwidth Card) -->
            <component 
              :is="sp.website_url ? 'a' : 'div'"
              v-if="sp.only_logo || sp.is_logo_only || (!sp.name && sp.logo)" 
              :href="sp.website_url || undefined"
              :target="sp.website_url ? '_blank' : undefined"
              :rel="sp.website_url ? 'noopener noreferrer' : undefined"
              class="sponsor-logo-only-wrapper"
              :title="sp.name || 'Mitra Sponsor'"
            >
              <img 
                v-if="sp.logo" 
                :src="sp.logo" 
                :alt="sp.name || 'Logo Sponsor'" 
                class="sponsor-brand-logo-only" 
                loading="lazy"
                draggable="false"
              />
              <div v-else class="sponsor-brand-logo-placeholder">
                <i :class="sp.icon || 'ri-building-line'" :style="{ color: sp.color }"></i>
                <span class="placeholder-name">{{ sp.short || sp.name }}</span>
              </div>
            </component>

            <!-- 2. MODE STANDAR: Menampilkan Icon, Nama, dan Kategori/Deskripsi -->
            <template v-else>
              <div class="sponsor-card-top">
                <span 
                  class="sponsor-tier-badge" 
                  :style="{ color: sp.color, backgroundColor: sp.bgLight, borderColor: sp.borderLight }"
                >
                  {{ sp.tier }}
                </span>
                <span class="sponsor-initials-pill font-mono">{{ sp.short }}</span>
              </div>

              <div class="sponsor-card-body">
                <div 
                  class="sponsor-icon-wrap" 
                  :style="{ backgroundColor: sp.bgLight, color: sp.color, borderColor: sp.borderLight }"
                >
                  <img v-if="sp.logo" :src="sp.logo" :alt="sp.name" class="sponsor-thumb-logo" draggable="false" />
                  <i v-else :class="sp.icon"></i>
                </div>
                <div class="sponsor-info">
                  <h4 class="sponsor-name" :title="sp.name">{{ sp.name }}</h4>
                  <span class="sponsor-category">{{ sp.category }}</span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <div class="container">
        <!-- Sponsor Bottom Action Bar -->
        <div class="sponsor-cta-strip">
          <div class="sponsor-cta-left">
            <span class="sponsor-live-dot"></span>
            <span>
              <strong>Peluang Sponsorship:</strong> {{ effectiveSponsorSettings.cta_text }}
            </span>
          </div>

          <div class="sponsor-cta-right">
            <a 
              :href="getWaChatUrl(effectiveSponsorSettings.cta_wa, 'Halo Panitia ' + (school.event_name || 'Semarak Aliyah') + ', kami tertarik untuk bekerja sama sebagai mitra sponsor.', cleanSchoolWa)"
              target="_blank"
              rel="noopener"
              class="sponsor-cta-btn"
            >
              <i class="ri-whatsapp-line"></i>
              <span>{{ effectiveSponsorSettings.cta_btn_text }}</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         GALERI DOKUMENTASI (KONFER 2-ROW COUNTER-SLIDER)
         ========================================================================== -->
    <section v-if="effectiveGallerySettings.is_active" class="section-padding section-gallery" id="galeri">
      <div class="container text-center">
        <!-- Section Header -->
        <div class="section-header text-center mb-3">
          <span class="section-tag">
            <i class="ri-gallery-fill text-gold"></i> {{ effectiveGallerySettings.tag }}
          </span>
          <h2 class="section-title">{{ effectiveGallerySettings.title }}</h2>
          <p class="section-desc">
            {{ effectiveGallerySettings.desc }}
          </p>
        </div>
      </div>

      <!-- 2-Row Infinite Slider Wrapper (Full Bleed Edge-to-Edge like Konfer) -->
      <div class="gallery-ticker-wrapper">
        <!-- Baris 1: Bergerak ke KIRI -->
        <div class="gallery-track-outer">
          <div 
            class="gallery-track gallery-track--row1"
            :class="{ 'is-paused': isGalleryPaused }"
          >
            <div 
              v-for="(photo, idx) in row1MarqueeList" 
              :key="'r1-' + idx"
              class="gallery-card"
              @click="openLightboxByPhoto(photo)"
              :title="`Klik untuk memperbesar: ${photo.title}`"
            >
              <div class="gallery-thumb-wrap">
                <img 
                  :src="photo.src" 
                  :alt="photo.title" 
                  class="gallery-thumb-img" 
                  loading="lazy"
                />
                <div class="gallery-overlay">
                  <div class="gallery-top-badges">
                    <span class="gallery-cat-pill" :class="photo.pillClass">
                      <i :class="photo.iconClass"></i>
                      <span>{{ photo.categoryLabel }}</span>
                    </span>
                    <span class="gallery-zoom-btn" title="Perbesar Foto">
                      <i class="ri-fullscreen-line"></i>
                    </span>
                  </div>

                  <div class="gallery-bottom-content">
                    <h3 class="gallery-card-title">{{ photo.title }}</h3>
                    <div class="gallery-card-meta">
                      <span><i class="ri-map-pin-2-line"></i> {{ photo.location }}</span>
                      <span><i class="ri-calendar-event-line"></i> {{ photo.tag }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Baris 2: Bergerak ke KANAN -->
        <div class="gallery-track-outer">
          <div 
            class="gallery-track gallery-track--row2"
            :class="{ 'is-paused': isGalleryPaused }"
          >
            <div 
              v-for="(photo, idx) in row2MarqueeList" 
              :key="'r2-' + idx"
              class="gallery-card"
              @click="openLightboxByPhoto(photo)"
              :title="`Klik untuk memperbesar: ${photo.title}`"
            >
              <div class="gallery-thumb-wrap">
                <img 
                  :src="photo.src" 
                  :alt="photo.title" 
                  class="gallery-thumb-img" 
                  loading="lazy"
                />
                <div class="gallery-overlay">
                  <div class="gallery-top-badges">
                    <span class="gallery-cat-pill" :class="photo.pillClass">
                      <i :class="photo.iconClass"></i>
                      <span>{{ photo.categoryLabel }}</span>
                    </span>
                    <span class="gallery-zoom-btn" title="Perbesar Foto">
                      <i class="ri-fullscreen-line"></i>
                    </span>
                  </div>

                  <div class="gallery-bottom-content">
                    <h3 class="gallery-card-title">{{ photo.title }}</h3>
                    <div class="gallery-card-meta">
                      <span><i class="ri-map-pin-2-line"></i> {{ photo.location }}</span>
                      <span><i class="ri-calendar-event-line"></i> {{ photo.tag }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Lightbox Modal -->
    <Teleport to="body">
      <div 
        v-if="lightboxOpen" 
        class="gallery-lightbox-backdrop"
        @click.self="closeLightbox"
      >
        <div class="gallery-lightbox-container">
          <button 
            type="button" 
            class="gallery-lightbox-close" 
            @click="closeLightbox" 
            aria-label="Tutup Galeri"
          >
            <i class="ri-close-line"></i>
          </button>

          <button 
            type="button" 
            class="gallery-nav-arrow arrow-prev" 
            @click.stop="prevPhoto" 
            aria-label="Foto Sebelumnya"
          >
            <i class="ri-arrow-left-s-line"></i>
          </button>

          <button 
            type="button" 
            class="gallery-nav-arrow arrow-next" 
            @click.stop="nextPhoto" 
            aria-label="Foto Berikutnya"
          >
            <i class="ri-arrow-right-s-line"></i>
          </button>

          <div class="gallery-lightbox-img-wrap">
            <img 
              :src="currentPhoto.src" 
              :alt="currentPhoto.title" 
              class="gallery-lightbox-img" 
            />
          </div>

          <div class="gallery-lightbox-caption">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="gallery-cat-pill" :class="currentPhoto.pillClass" style="font-size: 0.68rem; padding: 3px 10px;">
                  <i :class="currentPhoto.iconClass"></i>
                  <span>{{ currentPhoto.categoryLabel }}</span>
                </span>
                <span style="font-size: 0.75rem; color: #94a3b8;"><i class="ri-map-pin-2-line text-warning"></i> {{ currentPhoto.location }}</span>
              </div>
              <h4 class="gallery-caption-title">{{ currentPhoto.title }}</h4>
              <p class="gallery-caption-desc">{{ currentPhoto.desc }}</p>
            </div>

            <div class="gallery-caption-badge-wrap">
              <span class="gallery-counter-pill">
                {{ activePhotoIndex + 1 }} / {{ allGalleryPhotos.length }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ==========================================================================
         FAQ (TANYA JAWAB)
         ========================================================================== -->
    <section class="section-padding section-faq" id="faq" v-if="faqSettings.is_active !== false">
      <div class="container" style="max-width: 900px;">
        <div class="section-header">
          <span class="section-tag">{{ faqSettings.tag || 'Bantuan Informasi' }}</span>
          <h2 class="section-title">{{ faqSettings.title || 'Pertanyaan Sering Diajukan (FAQ)' }}</h2>
          <p class="section-desc">{{ faqSettings.desc || `Jawaban atas pertanyaan umum seputar pendaftaran dan pelaksanaan ${school.event_name || 'Semarak Aliyah'}.` }}</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div 
            v-for="(faq, fIdx) in computedFaqs" 
            :key="faq.id || fIdx"
            class="faq-accordion-card"
            style="background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-md); overflow: hidden; transition: var(--transition-fast);"
          >
            <button 
              type="button" 
              @click="toggleFaq(fIdx)"
              style="width: 100%; padding: 18px 24px; text-align: left; background: none; border: none; color: var(--text-main); font-family: var(--font-heading); font-size: 1.05rem; font-weight: 600; display: flex; align-items: center; justify-content: space-between; cursor: pointer;"
            >
              <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span v-if="faq.category" class="faq-category-pill">{{ faq.category }}</span>
                <span>{{ faq.q }}</span>
              </div>
              <i 
                class="ri-arrow-down-s-line" 
                style="font-size: 1.25rem; color: var(--accent-gold); transition: transform 0.2s;" 
                :style="{ transform: openFaqIndex === fIdx ? 'rotate(180deg)' : 'rotate(0)' }"
              ></i>
            </button>
            <div 
              v-show="openFaqIndex === fIdx" 
              class="faq-rich-answer"
              style="padding: 0 24px 20px; border-top: 1px solid rgba(255, 255, 255, 0.05);"
              v-html="faq.a"
            >
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         SEKRETARIAT & LOKASI KAMPUS
         ========================================================================== -->
    <section class="section-padding" id="kontak">
      <div class="container">
        <div class="section-header">
          <span class="section-tag">Lokasi & Kontak</span>
          <h2 class="section-title">Pusat Informasi & Kampus Penyelenggara</h2>
          <p class="section-desc">Kunjungi sekretariat panitia atau hubungi tim helpdesk kami jika membutuhkan pendampingan teknis.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
          <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-lg); padding: 30px;">
            <h3 style="font-size: 1.18rem; margin-bottom: 14px; color: var(--text-main);">{{ school.school_name || 'MA Unggulan Wahid Hasyim Balung' }}</h3>
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px;">
              {{ school.school_tagline || 'Lembaga pendidikan Islam modern terakreditasi "A" dengan program unggulan Plus Keterampilan, Riset, Penguasaan Bahasa Asing, dan International Class Program (ICP).' }}
            </p>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.88rem; color: var(--text-secondary);">
              <div style="display: flex; align-items: flex-start; gap: 8px;">
                <i class="ri-map-pin-2-line" style="color: var(--accent-gold-light); margin-top: 2px;"></i>
                <span><strong>Alamat:</strong> {{ school.school_address || 'Jl. Puger No. 20, Balung Kulon, Kec. Balung, Kabupaten Jember, Jawa Timur 68161' }}</span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ri-time-line" style="color: var(--primary-blue-light);"></i>
                <span><strong>Jam Layanan:</strong> {{ school.service_hours || 'Senin - Sabtu (07.00 - 13.00 WIB)' }}</span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ri-mail-line" style="color: #f97316;"></i>
                <span><strong>Email:</strong> <a :href="'mailto:' + (school.contact_email || 'semarak.aliyah@gmail.com')">{{ school.contact_email || 'semarak.aliyah@gmail.com' }}</a></span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ri-whatsapp-line" style="color: #25d366;"></i>
                <span><strong>WhatsApp:</strong> <a :href="'https://wa.me/' + cleanSchoolWa" target="_blank" rel="noopener">{{ school.contact_wa || '0838 5007 0767' }}</a></span>
              </div>
            </div>
          </div>

          <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-lg); overflow: hidden; min-height: 280px;">
            <iframe 
              :src="school.maps_embed_url || 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3947.8872013898194!2d113.5358055!3d-8.2917778!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m2!1s0x2dd68349f7e8b6b1%3A0x6e9f28d8442c7590!2sMA%20Unggulan%20Wahid%20Hasyim!5e0!3m2!1sid!2sid!4v1710000000000!5m2!1sid!2sid'" 
              width="100%" 
              height="100%" 
              style="border:0; min-height: 280px;" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade"
            ></iframe>
          </div>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         COMPETITION DETAIL MODAL
         ========================================================================== -->
    <div 
      v-if="detailModalOpen && selectedComp" 
      class="modal-overlay" 
      style="display: flex;" 
      role="dialog" 
      aria-modal="true"
    >
      <div class="modal-card">
        <button type="button" class="modal-close" @click="closeDetailModal" aria-label="Tutup Modal">&times;</button>
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
          <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--theme-purple-subtle); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; color: var(--theme-purple-hover); border: 1px solid var(--theme-purple-border); flex-shrink: 0;">
            <i :class="getCompIconClass(selectedComp)"></i>
          </div>
          <div>
            <span class="comp-card-category">{{ selectedComp.category || 'Kompetisi' }}</span>
            <h3 style="font-size: 1.25rem; color: var(--text-main); margin: 2px 0 0;">{{ selectedComp.title }}</h3>
          </div>
        </div>

        <div style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.6; margin-bottom: 20px;">
          <!-- Banner Khusus Pendaftaran Langsung via Koordinator -->
          <div 
            v-if="selectedComp.registration_type === 'direct_contact'" 
            style="padding: 12px 14px; border-radius: var(--radius-sm); background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3); margin-bottom: 14px; display: flex; align-items: flex-start; gap: 10px;"
          >
            <i class="ri-whatsapp-fill" style="font-size: 1.35rem; color: #10b981; flex-shrink: 0; margin-top: 1px;"></i>
            <div>
              <div style="font-size: 0.88rem; font-weight: 700; color: #10b981; margin-bottom: 2px;">
                Pendaftaran Khusus: Kontak Langsung ke Koordinator Lomba
              </div>
              <p style="margin: 0; font-size: 0.82rem; color: var(--text-secondary); line-height: 1.5;">
                {{ selectedComp.direct_registration_note || 'Pendaftaran cabang lomba ini tidak melalui sistem formulir online web, melainkan dilayani langsung melalui kontak WhatsApp Koordinator Lomba.' }}
              </p>
            </div>
          </div>

          <p style="margin-bottom: 14px;">{{ selectedComp.description }}</p>
          
          <div class="modal-detail-box" style="padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); margin-bottom: 16px; display: flex; flex-direction: column; gap: 8px; font-size: 0.86rem; color: var(--text-main);">
            <div><strong>Ketentuan Peserta :</strong> {{ selectedComp.participants_rule || 'Sesuai Juknis' }}</div>
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
              <div>
                <strong>Biaya Registrasi :</strong> 
                <span style="color: var(--accent-gold-light); font-weight: 700;">
                  Rp {{ Number(selectedComp.fee || 50000).toLocaleString('id-ID') }}
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted); margin-left: 2px;">/ lomba</span>
              </div>
              <span class="comp-fee-package-badge" title="Tersedia di Paket All-In Komplit seharga Rp 200.000 untuk seluruh cabang lomba">
                <i class="ri-vip-crown-fill text-gold"></i> Tersedia di Paket Lengkap
              </span>
            </div>
            <div>
              <strong>Kapasitas Kuota :</strong> 
              {{ selectedComp.current_quota }} / {{ selectedComp.max_quota }} Peserta
              <span 
                :class="['comp-status-badge', getCompStatusInfo(selectedComp).badgeClass]"
                style="margin-left: 8px; vertical-align: middle;"
              >
                <i :class="getCompStatusInfo(selectedComp).iconClass"></i>
                {{ getCompStatusInfo(selectedComp).text }}
              </span>
            </div>
            <div v-if="selectedComp.tm_date && selectedComp.tm_date !== '-'">
              <strong>Jadwal TM :</strong> {{ selectedComp.tm_date }}
            </div>
            <!-- Structured CP Chips in Modal -->
            <div class="modal-detail-cp-section">
              <div class="modal-detail-cp-title">
                <i class="ri-user-voice-fill" style="color: #10b981;"></i>
                <span>Narahubung / Contact Person (CP) :</span>
              </div>
              <div class="modal-detail-cp-chips">
                <div 
                  v-for="(cpItem, cpIdx) in getCompCpList(selectedComp)"
                  :key="cpIdx"
                  class="modal-cp-chip"
                >
                  <div class="modal-cp-info">
                    <span class="modal-cp-name">{{ cpItem.name }}</span>
                    <span class="schedule-cp-person-phone">{{ cpItem.phone }}</span>
                  </div>
                  <a 
                    :href="cpItem.waUrl" 
                    target="_blank" 
                    rel="noopener" 
                    class="schedule-cp-wa-action"
                    :title="`Chat WhatsApp dengan ${cpItem.name}`"
                  >
                    <i class="ri-whatsapp-line"></i> <span>Chat WA</span>
                  </a>
                </div>
              </div>
            </div>

            <!-- Berkas Petunjuk Teknis (Juknis) Spesifik Cabang Lomba -->
            <div class="modal-detail-juknis-card" style="margin-top: 14px; padding: 12px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-glass); background: var(--bg-surface-elevated, rgba(255,255,255,0.03)); display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
              <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(239, 68, 68, 0.12); color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                  <i class="ri-file-pdf-2-fill"></i>
                </div>
                <div style="min-width: 0;">
                  <div style="font-size: 0.86rem; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    {{ selectedComp.juknis_url ? `Berkas Juknis Khusus ${selectedComp.title}` : `Buku Panduan Juknis: ${selectedComp.title}` }}
                  </div>
                  <div style="font-size: 0.76rem; color: var(--text-secondary); margin-top: 2px;">
                    <span v-if="selectedComp.juknis_url" class="badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-weight: 600; padding: 2px 6px; border-radius: 4px; margin-right: 6px;">
                      Juknis Spesifik
                    </span>
                    <span v-else class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; font-weight: 600; padding: 2px 6px; border-radius: 4px; margin-right: 6px;">
                      Juknis Umum
                    </span>
                    {{ selectedComp.juknis_url ? (selectedComp.juknis_filename || 'Dokumen PDF') : 'Merujuk pada Buku Panduan Juknis Resmi Keseluruhan Lomba' }}
                  </div>
                </div>
              </div>

              <a 
                :href="selectedComp.juknis_url || $page.props.generalJuknis?.url || '/assets/JUKNIS-SEMARAK-2026.pdf'"
                :download="selectedComp.juknis_url ? (selectedComp.juknis_filename || `Juknis-${selectedComp.id}.pdf`) : ($page.props.generalJuknis?.filename || 'JUKNIS-SEMARAK-2026.pdf')"
                target="_blank"
                class="btn-modal-juknis"
                :title="selectedComp.juknis_url ? `Unduh Juknis Spesifik ${selectedComp.title}` : 'Unduh Juknis Umum Semarak'"
              >
                <i class="ri-download-2-line"></i>
                <span>{{ selectedComp.juknis_url ? 'Unduh Juknis Cabang' : 'Unduh Juknis' }}</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Sleek & Balanced Modal Footer Actions -->
        <div class="modal-footer-actions">
          <!-- Tombol Unduh Juknis Spesifik di Footer -->
          <a 
            :href="selectedComp.juknis_url || $page.props.generalJuknis?.url || '/assets/JUKNIS-SEMARAK-2026.pdf'"
            :download="selectedComp.juknis_url ? (selectedComp.juknis_filename || `Juknis-${selectedComp.id}.pdf`) : ($page.props.generalJuknis?.filename || 'JUKNIS-SEMARAK-2026.pdf')"
            target="_blank"
            class="btn-modal-juknis me-auto"
            style="margin-right: auto;"
            title="Unduh Berkas Petunjuk Teknis Lomba"
          >
            <i class="ri-file-download-line"></i>
            <span>{{ selectedComp.juknis_url ? 'Unduh Juknis Cabang (PDF)' : 'Unduh Juknis (PDF)' }}</span>
          </a>

          <button type="button" class="btn-modal-close" @click="closeDetailModal">
            Tutup
          </button>
          <a 
            v-if="selectedComp.registration_type === 'direct_contact'"
            :href="getDirectRegistrationWaUrl(selectedComp)" 
            target="_blank" 
            rel="noopener"
            class="btn-modal-primary"
            style="background: linear-gradient(135deg, #10b981, #059669); border: none; text-decoration: none;"
            @click="closeDetailModal"
          >
            <i class="ri-whatsapp-line"></i>
            <span>Daftar via WhatsApp Koordinator</span>
          </a>
          <Link 
            v-else-if="!getCompStatusInfo(selectedComp).isClosed"
            :href="getCompRegisterUrl(selectedComp.id)" 
            class="btn-modal-primary"
            @click="closeDetailModal"
          >
            <i class="ri-arrow-right-line"></i>
            <span>Lanjut Daftar</span>
          </Link>
          <button
            v-else
            type="button"
            disabled
            class="btn-modal-primary btn-modal-disabled"
            style="cursor: not-allowed;"
          >
            <i :class="getCompStatusInfo(selectedComp).isManuallyClosed ? 'ri-lock-line' : 'ri-time-line'"></i>
            <span>{{ getCompStatusInfo(selectedComp).isManuallyClosed ? 'Ditutup Panitia' : 'Waiting List' }}</span>
          </button>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>
