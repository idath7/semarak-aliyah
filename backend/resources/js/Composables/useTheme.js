import { ref, computed } from 'vue';

let initialTheme = 'dark';
if (typeof document !== 'undefined') {
  const domTheme = document.documentElement.getAttribute('data-bs-theme');
  if (domTheme === 'dark' || domTheme === 'light') {
    initialTheme = domTheme;
  }
}
if (typeof localStorage !== 'undefined') {
  const saved = localStorage.getItem('semarak_theme') || localStorage.getItem('velzon_theme_v2');
  if (saved === 'dark' || saved === 'light') {
    initialTheme = saved;
  }
}
const currentTheme = ref(initialTheme);

function applyTheme(theme) {
  currentTheme.value = theme;
  if (typeof document !== 'undefined') {
    const isPendaftar = typeof window !== 'undefined' && window.location.pathname.startsWith('/pendaftar');
    const isFormulir = typeof window !== 'undefined' && window.location.pathname.startsWith('/formulir');
    const isPortalRoute = typeof window !== 'undefined' && 
      (window.location.pathname.startsWith('/admin') || window.location.pathname.startsWith('/pendaftar') || window.location.pathname.startsWith('/operasional'));

    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.setAttribute('data-topbar', theme === 'dark' ? 'dark' : 'light');
    document.documentElement.setAttribute('data-sidebar', 'dark');
    document.documentElement.style.colorScheme = theme;

    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }

    const bg = theme === 'dark' 
      ? (isFormulir ? '#09110d' : '#0b0f19') 
      : (isFormulir ? '#f7faf7' : (isPendaftar ? '#f8fafc' : (isPortalRoute ? '#f3f3f9' : '#ffffff')));
    document.documentElement.style.backgroundColor = bg;
    if (document.body) {
      document.body.style.backgroundColor = bg;
      document.body.style.color = theme === 'dark' ? '#f1f5f9' : '#212529';
    }
  }
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem('semarak_theme', theme);
    localStorage.setItem('velzon_theme_v2', theme);
    localStorage.setItem('velzon_theme', theme);
  }
}

export function useTheme() {
  function initTheme() {
    if (typeof localStorage !== 'undefined') {
      const saved = localStorage.getItem('semarak_theme') || localStorage.getItem('velzon_theme_v2');
      if (saved === 'dark' || saved === 'light') {
        applyTheme(saved);
        return saved;
      }
    }
    const isPortalRoute = typeof window !== 'undefined' && 
      (window.location.pathname.startsWith('/admin') || window.location.pathname.startsWith('/pendaftar') || window.location.pathname.startsWith('/operasional'));
    const defaultTheme = isPortalRoute ? 'dark' : 'light';
    applyTheme(defaultTheme);
    return defaultTheme;
  }

  function toggleTheme() {
    const next = currentTheme.value === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    return next;
  }

  return {
    theme: currentTheme,
    isDark: computed(() => currentTheme.value === 'dark'),
    initTheme,
    toggleTheme,
    setTheme: applyTheme
  };
}
