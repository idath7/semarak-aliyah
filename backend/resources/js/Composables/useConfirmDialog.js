import { ref } from 'vue';

// Global reactive state for the Velzon confirmation dialog
const isOpen = ref(false);
const dialogConfig = ref({
  title: 'Konfirmasi Tindakan',
  message: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
  type: 'danger', // 'danger' | 'warning' | 'primary' | 'info' | 'success'
  icon: 'ri-alert-line',
  confirmText: 'Ya, Lanjutkan',
  cancelText: 'Batal',
  confirmButtonClass: 'btn-danger',
});

let resolvePromise = null;

export function useConfirmDialog() {
  /**
   * Trigger a Velzon-styled confirmation modal.
   * Returns a Promise<boolean> resolving true if user confirms, false if cancelled.
   * 
   * @param {Object|string} options - Configuration object or message string
   * @returns {Promise<boolean>}
   */
  function confirm(options = {}) {
    if (typeof options === 'string') {
      options = { message: options };
    }

    const type = options.type || (
      options.isDanger || options.danger ? 'danger' : 
      (options.isWarning || options.warning ? 'warning' : 
      (options.isInfo ? 'info' : 'danger'))
    );

    let defaultIcon = 'ri-alert-line';
    let defaultConfirmClass = 'btn-danger';
    let defaultConfirmText = 'Ya, Lanjutkan';

    if (type === 'danger') {
      defaultIcon = options.icon || 'ri-delete-bin-line';
      defaultConfirmClass = 'btn-danger';
      defaultConfirmText = options.confirmText || 'Ya, Hapus';
    } else if (type === 'warning') {
      defaultIcon = options.icon || 'ri-alert-line';
      defaultConfirmClass = 'btn-warning';
      defaultConfirmText = options.confirmText || 'Ya, Lanjutkan';
    } else if (type === 'success') {
      defaultIcon = options.icon || 'ri-checkbox-circle-line';
      defaultConfirmClass = 'btn-success';
      defaultConfirmText = options.confirmText || 'Ya, Setuju';
    } else if (type === 'info' || type === 'primary') {
      defaultIcon = options.icon || 'ri-question-line';
      defaultConfirmClass = 'btn-primary';
      defaultConfirmText = options.confirmText || 'Ya, Proses';
    }

    dialogConfig.value = {
      title: options.title || (type === 'danger' ? 'Hapus Data?' : 'Konfirmasi Tindakan'),
      message: options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
      type,
      icon: options.icon || defaultIcon,
      confirmText: options.confirmText || defaultConfirmText,
      cancelText: options.cancelText || 'Batal',
      confirmButtonClass: options.confirmButtonClass || defaultConfirmClass,
    };

    isOpen.value = true;

    return new Promise((resolve) => {
      resolvePromise = resolve;
    });
  }

  function handleConfirm() {
    isOpen.value = false;
    if (resolvePromise) {
      resolvePromise(true);
      resolvePromise = null;
    }
  }

  function handleCancel() {
    isOpen.value = false;
    if (resolvePromise) {
      resolvePromise(false);
      resolvePromise = null;
    }
  }

  return {
    isOpen,
    dialogConfig,
    confirm,
    handleConfirm,
    handleCancel,
  };
}
