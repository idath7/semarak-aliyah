<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useConfirmDialog } from '@/Composables/useConfirmDialog';

const { isOpen, dialogConfig, handleConfirm, handleCancel } = useConfirmDialog();

function handleKeydown(e) {
  if (e.key === 'Escape' && isOpen.value) {
    handleCancel();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
  <Transition name="velzon-modal">
    <div 
      v-if="isOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px); z-index: 9999;"
      @click.self="handleCancel"
    >
      <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden confirm-modal-content">
          <div class="modal-body p-4 text-center">
            <!-- Icon Avatar -->
            <div 
              class="avatar-md rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center fs-28 shadow-sm"
              :class="{
                'bg-danger-subtle text-danger': dialogConfig.type === 'danger',
                'bg-warning-subtle text-warning': dialogConfig.type === 'warning',
                'bg-primary-subtle text-primary': dialogConfig.type === 'primary' || dialogConfig.type === 'info',
                'bg-success-subtle text-success': dialogConfig.type === 'success',
              }"
              style="width: 60px; height: 60px;"
            >
              <i :class="dialogConfig.icon"></i>
            </div>

            <!-- Title & Message -->
            <h5 class="fs-16 fw-bold mb-2 confirm-modal-title">{{ dialogConfig.title }}</h5>
            <p class="fs-13 mb-4 lh-base confirm-modal-message" style="white-space: pre-line;">{{ dialogConfig.message }}</p>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 justify-content-center">
              <button 
                type="button" 
                class="btn btn-sm px-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1 confirm-btn-cancel"
                @click="handleCancel"
              >
                <i class="ri-close-line"></i>
                <span>{{ dialogConfig.cancelText }}</span>
              </button>

              <button 
                type="button" 
                class="btn btn-sm px-3.5 py-1.5 rounded-3 shadow-sm d-inline-flex align-items-center gap-1"
                :class="[
                  dialogConfig.confirmButtonClass,
                  (dialogConfig.type === 'warning' || dialogConfig.confirmButtonClass?.includes('warning')) ? 'text-dark fw-bold' : 'text-white'
                ]"
                @click="handleConfirm"
              >
                <i :class="dialogConfig.type === 'danger' ? 'ri-delete-bin-line' : 'ri-check-line'"></i>
                <span>{{ dialogConfig.confirmText }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.confirm-modal-content {
  background-color: #ffffff;
  color: #1e293b;
  border: 1px solid rgba(0, 0, 0, 0.08);
  transition: all 0.2s ease;
}

.confirm-modal-title {
  color: #0f172a;
}

.confirm-modal-message {
  color: #64748b;
}

.confirm-btn-cancel {
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #64748b;
}
.confirm-btn-cancel:hover {
  background-color: #f1f5f9;
  color: #334155;
}

.velzon-modal-enter-active,
.velzon-modal-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.velzon-modal-enter-from,
.velzon-modal-leave-to {
  opacity: 0;
  transform: scale(0.96);
}
</style>

<style>
/* Dark mode overrides for confirmation modal */
[data-bs-theme="dark"] .confirm-modal-content {
  background-color: #1e2227 !important;
  color: #f1f5f9 !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6) !important;
}

[data-bs-theme="dark"] .confirm-modal-title {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .confirm-modal-message {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .confirm-btn-cancel {
  background-color: #292e34 !important;
  border: 1px solid rgba(255, 255, 255, 0.12) !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .confirm-btn-cancel:hover {
  background-color: #343a40 !important;
  color: #ffffff !important;
}
</style>
