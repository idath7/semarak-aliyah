<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch, useSlots } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: ''
  },
  options: {
    type: Array,
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Pilih opsi...'
  },
  size: {
    type: String,
    default: 'sm', // 'xs', 'sm', 'md', 'lg'
    validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v)
  },
  prefixIcon: {
    type: String,
    default: ''
  },
  searchable: {
    type: Boolean,
    default: false
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari opsi...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  customClass: {
    type: String,
    default: ''
  },
  menuClass: {
    type: String,
    default: ''
  },
  variant: {
    type: String,
    default: 'default', // 'default', 'subtle', 'dark-slate'
    validator: (v) => ['default', 'subtle', 'dark-slate'].includes(v)
  },
  dropdownAlign: {
    type: String,
    default: 'start', // 'start', 'end'
    validator: (v) => ['start', 'end'].includes(v)
  },
  maxHeight: {
    type: String,
    default: '260px'
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const slots = useSlots();
const isOpen = ref(false);
const openUpward = ref(false);
const searchQuery = ref('');
const dropdownRef = ref(null);
const searchInputRef = ref(null);

function extractOptionsFromVNodes(vnodes) {
  const result = [];
  if (!vnodes) return result;
  
  function walk(nodes) {
    if (!nodes) return;
    const list = Array.isArray(nodes) ? nodes : [nodes];
    for (const node of list) {
      if (!node) continue;
      // Handle Fragment (e.g. from v-for)
      if (node.type === Symbol.for('v-fgt') || (typeof node.type === 'symbol' && String(node.type).includes('Fragment'))) {
        if (Array.isArray(node.children)) {
          walk(node.children);
        }
      } else if (node.type === 'option') {
        const val = node.props?.value !== undefined ? node.props.value : '';
        let label = '';
        if (typeof node.children === 'string') {
          label = node.children;
        } else if (Array.isArray(node.children)) {
          label = node.children.map(c => (typeof c === 'string' ? c : (c.children || ''))).join('').trim();
        } else if (typeof node.children?.default === 'function') {
          const slotChildren = node.children.default();
          label = slotChildren.map(c => (typeof c === 'string' ? c : (c.children || ''))).join('').trim();
        }
        result.push({
          value: val,
          label: label || String(val),
          disabled: node.props?.disabled !== undefined && node.props.disabled !== false,
          icon: node.props?.['data-icon'] || '',
          badge: node.props?.['data-badge'] || '',
          desc: node.props?.['data-desc'] || '',
          raw: node
        });
      } else if (Array.isArray(node.children)) {
        walk(node.children);
      }
    }
  }
  walk(vnodes);
  return result;
}

const rawOptions = computed(() => {
  if (props.options && props.options.length > 0) {
    return props.options;
  }
  if (slots.default) {
    try {
      return extractOptionsFromVNodes(slots.default());
    } catch (e) {
      return [];
    }
  }
  return [];
});

// Normalize options to object structure: { value, label, icon, badge, badgeClass, desc, category, disabled }
const normalizedOptions = computed(() => {
  return rawOptions.value.map(opt => {
    if (typeof opt === 'object' && opt !== null) {
      return {
        value: opt.value !== undefined ? opt.value : (opt.id !== undefined ? opt.id : opt.type),
        label: opt.label || opt.title || opt.name || String(opt.value),
        icon: opt.icon || '',
        badge: opt.badge || '',
        badgeClass: opt.badgeClass || opt.badge_class || '',
        desc: opt.desc || opt.description || '',
        category: opt.category || opt.category_label || '',
        disabled: !!opt.disabled,
        raw: opt
      };
    }
    return {
      value: opt,
      label: String(opt),
      icon: '',
      badge: '',
      desc: '',
      disabled: false,
      raw: opt
    };
  });
});

const selectedOption = computed(() => {
  return normalizedOptions.value.find(o => String(o.value) === String(props.modelValue)) || null;
});

const effectivePlaceholder = computed(() => {
  if (props.placeholder && props.placeholder !== 'Pilih opsi...') {
    return props.placeholder;
  }
  const emptyOpt = normalizedOptions.value.find(o => o.value === '' || o.value === null || o.value === undefined);
  if (emptyOpt) return emptyOpt.label;
  return props.placeholder;
});

const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) return normalizedOptions.value;
  const q = searchQuery.value.toLowerCase();
  return normalizedOptions.value.filter(o => {
    return (
      o.label.toLowerCase().includes(q) ||
      (o.desc && o.desc.toLowerCase().includes(q)) ||
      (o.category && o.category.toLowerCase().includes(q))
    );
  });
});

function updatePlacement() {
  if (!dropdownRef.value) return;
  const rect = dropdownRef.value.getBoundingClientRect();
  const estimatedMenuHeight = 220;

  const spaceBelowViewport = window.innerHeight - rect.bottom;
  const spaceAboveViewport = rect.top;

  const scrollContainer = dropdownRef.value.closest('.modal-body') || dropdownRef.value.closest('.table-responsive') || null;
  let spaceBelowContainer = spaceBelowViewport;
  let spaceAboveContainer = spaceAboveViewport;

  if (scrollContainer) {
    const containerRect = scrollContainer.getBoundingClientRect();
    spaceBelowContainer = containerRect.bottom - rect.bottom;
    spaceAboveContainer = rect.top - containerRect.top;
  }

  const effectiveSpaceBelow = Math.min(spaceBelowViewport, spaceBelowContainer);
  const effectiveSpaceAbove = Math.min(spaceAboveViewport, spaceAboveContainer);

  if (effectiveSpaceBelow < estimatedMenuHeight && effectiveSpaceAbove > effectiveSpaceBelow) {
    openUpward.value = true;
  } else {
    openUpward.value = false;
  }
}

function toggleDropdown() {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    updatePlacement();
    searchQuery.value = '';
    if (props.searchable) {
      nextTick(() => {
        searchInputRef.value?.focus();
      });
    }
  }
}

function selectOption(option) {
  emit('update:modelValue', option.value);
  emit('change', option.value, option.raw);
  isOpen.value = false;
  searchQuery.value = '';
}

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
}

function handleKeyDown(e) {
  if (!isOpen.value) {
    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleDropdown();
    }
    return;
  }
  if (e.key === 'Escape') {
    isOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div 
    class="custom-select-wrapper position-relative d-inline-block w-100" 
    :class="[customClass, { 'is-open': isOpen, 'is-disabled': disabled }]" 
    ref="dropdownRef"
    @keydown="handleKeyDown"
  >
    <!-- Trigger Button -->
    <button
      type="button"
      class="custom-select-trigger btn d-flex align-items-center justify-content-between text-truncate w-100"
      :class="[
        `size-${size}`,
        `variant-${variant}`,
        { 'active-open': isOpen }
      ]"
      :disabled="disabled"
      @click="toggleDropdown"
      :aria-expanded="isOpen"
    >
      <div class="d-flex align-items-center gap-2 text-truncate me-1">
        <i v-if="prefixIcon || selectedOption?.icon" :class="[prefixIcon || selectedOption?.icon, 'trigger-icon flex-shrink-0']"></i>
        
        <span v-if="selectedOption" class="selected-label text-truncate fw-semibold">
          {{ selectedOption.label }}
        </span>
        <span v-else class="placeholder-label text-truncate text-muted">
          {{ effectivePlaceholder }}
        </span>

        <span v-if="selectedOption?.badge" class="badge fs-10 px-1.5 py-0.5 rounded-pill flex-shrink-0 d-none d-lg-inline-block" :class="selectedOption.badgeClass || 'bg-primary-subtle text-primary'">
          {{ selectedOption.badge }}
        </span>
      </div>

      <!-- Chevron Arrow with smooth rotation -->
      <i class="ri-arrow-down-s-line chevron-icon transition-transform fs-14 flex-shrink-0 ms-1" :class="{ 'rotate-180': isOpen }"></i>
    </button>

    <!-- Hidden Slot for Reactive VNode Parsing -->
    <div class="d-none" style="display: none !important;" aria-hidden="true">
      <slot />
    </div>

    <!-- Dropdown Menu Popup -->
    <div 
      v-show="isOpen" 
      class="custom-select-menu shadow-lg rounded-3 border"
      :class="[
        menuClass,
        `align-${dropdownAlign}`,
        `variant-${variant}`,
        { 'open-upward': openUpward },
        'animate__animated animate__fadeIn animate__faster'
      ]"
    >
      <!-- Optional Search Input -->
      <div v-if="searchable || normalizedOptions.length > 7" class="p-2 border-bottom search-box-wrapper">
        <div class="input-group input-group-sm flex-nowrap">
          <span class="input-group-text bg-transparent border-end-0 text-muted ps-2.5">
            <i class="ri-search-line fs-12"></i>
          </span>
          <input 
            ref="searchInputRef"
            type="text" 
            v-model="searchQuery" 
            class="form-control form-control-sm border-start-0 shadow-none fs-12 custom-search-input" 
            :placeholder="searchPlaceholder"
            @click.stop
          />
          <button 
            v-if="searchQuery" 
            type="button" 
            class="btn btn-sm btn-ghost-secondary px-2"
            @click.stop="searchQuery = ''"
          >
            <i class="ri-close-line fs-12"></i>
          </button>
        </div>
      </div>

      <!-- Options List -->
      <div 
        class="options-scroll-container py-1"
        :style="{ maxHeight: maxHeight, overflowY: 'auto' }"
      >
        <div v-if="filteredOptions.length === 0" class="text-center py-3 text-muted fs-12 fst-italic">
          Tidak ada opsi ditemukan
        </div>

        <template v-else>
          <div 
            v-for="opt in filteredOptions" 
            :key="opt.value"
            class="custom-select-item d-flex align-items-center justify-content-between px-3 py-2 cursor-pointer transition-all"
            :class="{
              'is-selected': String(opt.value) === String(modelValue)
            }"
            @click="selectOption(opt)"
          >
            <!-- Option Label, Icon & Details -->
            <div class="d-flex align-items-center gap-2 text-truncate me-2">
              <i v-if="opt.icon" :class="[opt.icon, 'opt-icon flex-shrink-0 fs-15']"></i>
              <div class="text-truncate">
                <div class="opt-label text-truncate fs-12 lh-sm d-flex align-items-center gap-1.5">
                  <span class="fw-medium">{{ opt.label }}</span>
                  <span v-if="opt.badge" class="badge fs-9 px-1 py-0 rounded" :class="opt.badgeClass || 'bg-light text-muted'">
                    {{ opt.badge }}
                  </span>
                </div>
                <div v-if="opt.desc" class="opt-desc text-muted fs-10 text-truncate lh-sm mt-0.5">
                  {{ opt.desc }}
                </div>
              </div>
            </div>

            <!-- Tanda Centang (Checkmark Icon) -->
            <div class="check-mark-col flex-shrink-0 d-flex align-items-center ms-2">
              <i 
                v-if="String(opt.value) === String(modelValue)" 
                class="ri-check-line fs-16 check-icon text-primary animate__animated animate__fadeIn"
              ></i>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.d-none {
  display: none !important;
}
.w-100 {
  width: 100% !important;
}
.position-relative {
  position: relative !important;
}
.d-inline-block {
  display: inline-block !important;
}
.d-flex {
  display: flex !important;
}
.align-items-center {
  align-items: center !important;
}
.justify-content-between {
  justify-content: space-between !important;
}
.flex-shrink-0 {
  flex-shrink: 0 !important;
}
.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.custom-select-wrapper {
  user-select: none;
}
.custom-select-wrapper.is-open {
  z-index: 1065;
}
.check-mark-col {
  min-width: 18px;
}

/* Trigger Styles */
.custom-select-trigger {
  background-color: var(--vz-input-bg, #ffffff);
  border: 1px solid var(--vz-input-border, #ced4da);
  color: var(--vz-body-color, #212529);
  border-radius: 0.375rem;
  padding: 0.375rem 0.65rem;
  font-size: 0.8125rem;
  line-height: 1.5;
  transition: all 0.2s ease;
  text-align: left;
}

.custom-select-trigger:hover:not(:disabled) {
  border-color: #3b82f6;
  background-color: var(--vz-input-bg, #ffffff);
}

.custom-select-trigger:focus,
.custom-select-trigger.active-open {
  border-color: #3b82f6;
  outline: none;
  box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
}

/* Sizes */
.custom-select-trigger.size-xs {
  padding: 0.2rem 0.5rem;
  font-size: 0.725rem;
}
.custom-select-trigger.size-sm {
  padding: 0.275rem 0.65rem;
  font-size: 0.775rem;
}
.custom-select-trigger.size-md {
  padding: 0.45rem 0.85rem;
  font-size: 0.85rem;
}
.custom-select-trigger.size-lg {
  padding: 0.6rem 1rem;
  font-size: 0.95rem;
}

/* Chevron */
.chevron-icon {
  color: #64748b;
  transition: transform 0.2s ease;
}
.chevron-icon.rotate-180 {
  transform: rotate(180deg);
}

/* Dropdown Menu Popup */
.custom-select-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
  min-width: 100%;
  width: max-content;
  max-width: 480px;
  background-color: #ffffff;
  border-color: #e2e8f0;
  z-index: 1060;
  overflow: hidden;
}

.custom-select-menu.open-upward {
  top: auto !important;
  bottom: 100% !important;
  margin-top: 0 !important;
  margin-bottom: 4px !important;
}

.custom-select-menu.align-end {
  left: auto;
  right: 0;
}

/* Options Items */
.custom-select-item {
  color: #1e293b;
  border-left: 3px solid transparent;
}

/* 1. HOVER EFFECT */
.custom-select-item:hover {
  background-color: #f1f5f9 !important;
  color: #0f172a !important;
}

/* 2. SELECTED / ACTIVE OPTION WITH CHECKMARK */
.custom-select-item.is-selected {
  background-color: #eef2ff !important;
  color: #312e81 !important;
  border-left-color: #4f46e5;
  font-weight: 600;
}

.custom-select-item.is-selected .opt-label span {
  font-weight: 700 !important;
  color: #3730a3 !important;
}

.custom-select-item.is-selected .check-icon {
  color: #4f46e5 !important;
  font-weight: bold;
}

/* Dark Slate Variant (for Dark Modals / Builders) */
.custom-select-menu.variant-dark-slate {
  background-color: #0b1120 !important;
  border-color: #1e293b !important;
  color: #f1f5f9 !important;
}

.custom-select-menu.variant-dark-slate .search-box-wrapper {
  border-bottom-color: #1e293b !important;
  background-color: #0b1120 !important;
}

.custom-select-menu.variant-dark-slate .custom-search-input {
  background-color: #162032 !important;
  border-color: #1e293b !important;
  color: #f8fafc !important;
}

.custom-select-menu.variant-dark-slate .custom-select-item {
  color: #e2e8f0 !important;
}

.custom-select-menu.variant-dark-slate .custom-select-item:hover {
  background-color: #1e293b !important;
  color: #ffffff !important;
}

.custom-select-menu.variant-dark-slate .custom-select-item.is-selected {
  background-color: rgba(79, 70, 229, 0.25) !important;
  color: #ffffff !important;
  border-left-color: #818cf8;
}

.custom-select-menu.variant-dark-slate .custom-select-item.is-selected .check-icon {
  color: #a5b4fc !important;
}

/* Subtle Variant */
.custom-select-trigger.variant-subtle {
  background-color: transparent !important;
  border: none !important;
  padding-left: 0.25rem;
}
.custom-select-trigger.variant-subtle:focus,
.custom-select-trigger.variant-subtle.active-open {
  box-shadow: none !important;
}

/* Options Scroll Area */
.options-scroll-container {
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.25) transparent;
}
.options-scroll-container::-webkit-scrollbar {
  width: 5px;
}
.options-scroll-container::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.28);
  border-radius: 9999px;
}
</style>

<!-- Global Dark Theme overrides for CustomSelect -->
<style>
[data-bs-theme="dark"] .custom-select-trigger {
  background-color: #0b1120 !important;
  border-color: #1e293b !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .custom-select-trigger:hover:not(:disabled) {
  border-color: #3b82f6 !important;
}

[data-bs-theme="dark"] .custom-select-menu {
  background-color: #0b1120 !important;
  border-color: #1e293b !important;
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .custom-select-menu .search-box-wrapper {
  border-bottom-color: #1e293b !important;
}

[data-bs-theme="dark"] .custom-select-menu .custom-search-input {
  background-color: #162032 !important;
  border-color: #1e293b !important;
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .custom-select-menu .custom-select-item {
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .custom-select-menu .custom-select-item:hover {
  background-color: #1e293b !important;
  color: #ffffff !important;
}

[data-bs-theme="dark"] .custom-select-menu .custom-select-item.is-selected {
  background-color: rgba(79, 70, 229, 0.28) !important;
  color: #ffffff !important;
  border-left-color: #818cf8 !important;
}

[data-bs-theme="dark"] .custom-select-menu .custom-select-item.is-selected .opt-label span {
  color: #ffffff !important;
}

[data-bs-theme="dark"] .custom-select-menu .custom-select-item.is-selected .check-icon {
  color: #a5b4fc !important;
}
</style>
