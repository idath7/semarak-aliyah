import { ref } from 'vue';

const toasts = ref([]);
let toastCounter = 0;
let lastToastTime = 0;
let lastToastMessage = '';

export function useToast() {
  function showToast({ type = 'success', title = 'Berhasil', message = '', duration = 4000 }) {
    if (!message) return null;
    
    // Strict deduplication: ignore if identical message within 2 seconds
    const now = Date.now();
    if (lastToastMessage === message && (now - lastToastTime) < 2000) {
      return null;
    }
    
    // Also ignore if same message is already in active toasts
    if (toasts.value.some(t => t.message === message)) {
      return null;
    }

    lastToastTime = now;
    lastToastMessage = message;

    const id = ++toastCounter;
    toasts.value.push({ id, type, title, message });

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
    return id;
  }

  function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
      toasts.value.splice(index, 1);
    }
  }

  function success(message, title = 'Berhasil Disimpan') {
    return showToast({ type: 'success', title, message });
  }

  function error(message, title = 'Terjadi Kesalahan') {
    return showToast({ type: 'error', title, message });
  }

  function warning(message, title = 'Perhatian') {
    return showToast({ type: 'warning', title, message });
  }

  function info(message, title = 'Informasi') {
    return showToast({ type: 'info', title, message });
  }

  return {
    toasts,
    showToast,
    removeToast,
    success,
    error,
    warning,
    info,
  };
}
