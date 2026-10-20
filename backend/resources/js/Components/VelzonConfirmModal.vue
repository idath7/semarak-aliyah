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
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden bg-white">
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
            <h5 class="fs-16 fw-bold text-dark mb-2">{{ dialogConfig.title }}</h5>
            <p class="text-muted fs-13 mb-4 lh-base" style="white-space: pre-line;">{{ dialogConfig.message }}</p>

            <!-- Action Buttons -->
            <div class="d-flex gap-2 justify-content-center">
              <button 
                type="button" 
                class="btn btn-sm btn-light border px-3.5 py-1.5 rounded-3 d-inline-flex align-items-center gap-1 text-muted"
                @click="handleCancel"
              >
                <i class="ri-close-line"></i>
                <span>{{ dialogConfig.cancelText }}</span>
              </button>

              <button 
                type="button" 
                class="btn btn-sm px-3.5 py-1.5 rounded-3 shadow-sm d-inline-flex align-items-center gap-1 text-white"
                :class="dialogConfig.confirmButtonClass"
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
