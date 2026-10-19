import { ref, computed } from 'vue';

let initialTheme = 'light';
if (typeof localStorage !== 'undefined') {
  const saved = localStorage.getItem('semarak_theme');
  if (saved === 'dark' || saved === 'light') {
    initialTheme = saved;
  }
}
const currentTheme = ref(initialTheme);

function applyTheme(theme) {
  currentTheme.value = theme;
  if (typeof document !== 'undefined') {
    document.documentElement.setAttribute('data-bs-theme', theme);
    document.documentElement.style.colorScheme = theme;
    const bg = theme === 'dark' ? '#0b0f19' : '#f8fafc';
    document.documentElement.style.backgroundColor = bg;
    if (document.body) {
      document.body.style.backgroundColor = bg;
    }
  }
  if (typeof localStorage !== 'undefined') {
    localStorage.setItem('semarak_theme', theme);
  }
}

export function useTheme() {
  function initTheme() {
    if (typeof localStorage !== 'undefined') {
      const saved = localStorage.getItem('semarak_theme');
      if (saved === 'dark' || saved === 'light') {
        applyTheme(saved);
        return saved;
      }
    }
    applyTheme('light');
    return 'light';
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
