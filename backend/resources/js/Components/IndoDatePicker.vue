<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  placeholder: { type: String, default: 'dd/mm/yyyy' },
  id: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  hasError: { type: Boolean, default: false },
  inputClass: { type: String, default: '' },
  iconClass: { type: String, default: 'ri-calendar-event-line fs-15 text-primary' },
  minDate: { type: [String, Date], default: null },
  maxDate: { type: [String, Date], default: null },
});

const emit = defineEmits(['update:modelValue']);
const inputElement = ref(null);
let fpInstance = null;

// Convert any format (ISO, YYYY-MM-DD, etc.) into dd/mm/yyyy
function toIndoDate(val) {
  if (!val && val !== 0) return '';
  const sVal = String(val).trim();
  if (!sVal) return '';
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(sVal)) return sVal;
  const m = sVal.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (m) {
    return `${m[3]}/${m[2]}/${m[1]}`;
  }
  const d = new Date(sVal);
  if (!isNaN(d.getTime())) {
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  }
  return sVal;
}

function initFlatpickr() {
  if (!inputElement.value) return;

  const initialVal = toIndoDate(props.modelValue);

  fpInstance = flatpickr(inputElement.value, {
    locale: Indonesian,
    dateFormat: 'd/m/Y',
    allowInput: true,
    disableMobile: true, // Strict Rule 4: Always keep Flatpickr UI on mobile (never native datepicker)
    defaultDate: initialVal || undefined,
    minDate: props.minDate ? toIndoDate(props.minDate) : undefined,
    maxDate: props.maxDate ? toIndoDate(props.maxDate) : undefined,
    onChange: (selectedDates, dateStr) => {
      emit('update:modelValue', dateStr);
    },
    onClose: (selectedDates, dateStr) => {
      const finalVal = dateStr || (inputElement.value ? inputElement.value.value : '');
      emit('update:modelValue', finalVal);
    }
  });

  // Sync initial normalized value if format needed conversion
  if (props.modelValue && initialVal !== props.modelValue) {
    emit('update:modelValue', initialVal);
  }
}

onMounted(() => {
  initFlatpickr();
});

watch(() => props.modelValue, (newVal) => {
  if (!fpInstance) return;
  const normalized = toIndoDate(newVal);
  if (!normalized) {
    fpInstance.clear();
  } else if (inputElement.value && inputElement.value.value !== normalized) {
    fpInstance.setDate(normalized, false, 'd/m/Y');
  }
});

watch(() => props.minDate, (newMin) => {
  if (fpInstance) {
    fpInstance.set('minDate', newMin ? toIndoDate(newMin) : undefined);
  }
});

watch(() => props.maxDate, (newMax) => {
  if (fpInstance) {
    fpInstance.set('maxDate', newMax ? toIndoDate(newMax) : undefined);
  }
});

onBeforeUnmount(() => {
  if (fpInstance) {
    fpInstance.destroy();
    fpInstance = null;
  }
});

function handleInput(e) {
  emit('update:modelValue', e.target.value);
}

function openCalendar() {
  if (props.disabled) return;
  if (fpInstance) {
    fpInstance.open();
    inputElement.value?.focus();
  }
}
</script>

<template>
  <div class="datepicker-wrapper position-relative w-100">
    <input
      :id="id"
      ref="inputElement"
      type="text"
      :value="toIndoDate(modelValue)"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :class="[inputClass || 'form-control fs-13 bg-white', { 'is-invalid': hasError }]"
      autocomplete="off"
      style="padding-right: 2.75rem !important;"
      @input="handleInput"
    />
    <button 
      type="button"
      class="datepicker-toggle-btn"
      tabindex="-1"
      :disabled="disabled"
      @click="openCalendar"
      title="Buka kalender"
      aria-label="Buka kalender"
    >
      <i :class="iconClass"></i>
    </button>
  </div>
</template>

<style>
/* Self-contained Datepicker Wrapper & Toggle Button Styling */
.datepicker-wrapper {
  position: relative !important;
  display: block !important;
  width: 100% !important;
  box-sizing: border-box !important;
}

.datepicker-wrapper input {
  width: 100% !important;
  box-sizing: border-box !important;
  padding-right: 2.75rem !important;
}

.datepicker-wrapper .datepicker-toggle-btn {
  position: absolute !important;
  right: 0.75rem !important;
  top: 50% !important;
  transform: translateY(-50%) !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 2rem !important;
  height: 2rem !important;
  padding: 0 !important;
  margin: 0 !important;
  background: transparent !important;
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
  color: #006948 !important;
  cursor: pointer !important;
  z-index: 10 !important;
  border-radius: 0.375rem !important;
  transition: all 0.15s ease !important;
}

.datepicker-wrapper .datepicker-toggle-btn:hover {
  background-color: rgba(0, 105, 72, 0.08) !important;
  color: #00855d !important;
}

.datepicker-wrapper .datepicker-toggle-btn:active {
  transform: translateY(-50%) scale(0.92) !important;
}

.datepicker-wrapper .datepicker-toggle-btn:disabled {
  opacity: 0.4 !important;
  cursor: not-allowed !important;
}

.datepicker-wrapper .datepicker-toggle-btn i {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  font-size: 1.15rem !important;
  line-height: 1 !important;
  vertical-align: middle !important;
  pointer-events: none !important;
}

/* Dark mode styling */
[data-bs-theme="dark"] .datepicker-wrapper .datepicker-toggle-btn,
.dark .datepicker-wrapper .datepicker-toggle-btn,
.semarak-root-page.dark .datepicker-wrapper .datepicker-toggle-btn {
  color: #86efac !important;
}

[data-bs-theme="dark"] .datepicker-wrapper .datepicker-toggle-btn:hover,
.dark .datepicker-wrapper .datepicker-toggle-btn:hover,
.semarak-root-page.dark .datepicker-wrapper .datepicker-toggle-btn:hover {
  background-color: rgba(134, 239, 172, 0.15) !important;
  color: #bbf7d0 !important;
}

/* Velzon & Semarak Flatpickr Styling - Light & Dark Theme WCAG AA Compliant */
.flatpickr-calendar {
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 14px !important;
  box-shadow: 0 14px 35px rgba(15, 23, 42, 0.16), 0 4px 12px rgba(15, 23, 42, 0.08) !important;
  font-family: var(--vz-font-sans-serif, 'Poppins', sans-serif) !important;
  width: 312px !important;
  padding: 10px !important;
  z-index: 99999 !important;
  animation: fpFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
}

@keyframes fpFadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}

.flatpickr-calendar.arrowTop:before,
.flatpickr-calendar.arrowTop:after {
  border-bottom-color: #3b82f6 !important;
}

.flatpickr-months {
  background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
  border-radius: 10px !important;
  color: #ffffff !important;
  padding: 8px 6px !important;
  position: relative !important;
  display: flex !important;
  align-items: center !important;
}

.flatpickr-month {
  color: #ffffff !important;
  fill: #ffffff !important;
  height: 40px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.flatpickr-current-month {
  color: #ffffff !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  padding: 0 !important;
  position: static !important;
  width: auto !important;
}

.flatpickr-current-month .flatpickr-monthDropdown-months {
  background: rgba(255, 255, 255, 0.18) !important;
  color: #ffffff !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
  border-radius: 6px !important;
  padding: 3px 8px !important;
  font-weight: 600 !important;
  font-size: 13px !important;
  cursor: pointer !important;
  outline: none !important;
  transition: all 0.2s ease !important;
}

.flatpickr-current-month .flatpickr-monthDropdown-months option {
  background-color: #1e293b !important;
  color: #ffffff !important;
}

.flatpickr-current-month .numInputWrapper {
  width: 68px !important;
}

.flatpickr-current-month input.cur-year {
  font-weight: 700 !important;
  color: #ffffff !important;
  font-size: 14px !important;
  background: rgba(255, 255, 255, 0.18) !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
  border-radius: 6px !important;
  padding: 3px 6px !important;
}

.flatpickr-months .flatpickr-prev-month,
.flatpickr-months .flatpickr-next-month {
  color: #ffffff !important;
  fill: #ffffff !important;
  padding: 6px 8px !important;
  border-radius: 6px !important;
  transition: all 0.2s ease !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.flatpickr-months .flatpickr-prev-month:hover,
.flatpickr-months .flatpickr-next-month:hover {
  background: rgba(255, 255, 255, 0.28) !important;
}

.flatpickr-months .flatpickr-prev-month svg,
.flatpickr-months .flatpickr-next-month svg {
  fill: #ffffff !important;
  width: 14px !important;
  height: 14px !important;
}

.flatpickr-weekdays {
  background: #f8fafc !important;
  border-bottom: 1px solid #e2e8f0 !important;
  border-radius: 6px !important;
  height: 32px !important;
  margin-top: 6px !important;
}

.flatpickr-weekday {
  color: #64748b !important;
  font-size: 11px !important;
  font-weight: 700 !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}

.flatpickr-days {
  width: 100% !important;
  padding-top: 6px !important;
}

.dayContainer {
  width: 100% !important;
  min-width: 100% !important;
  max-width: 100% !important;
  justify-content: space-around !important;
}

.flatpickr-day {
  border-radius: 8px !important;
  font-size: 12.5px !important;
  font-weight: 600 !important;
  color: #1e293b !important;
  height: 36px !important;
  line-height: 36px !important;
  margin: 1px 0 !important;
  border: 1px solid transparent !important;
  transition: all 0.15s ease !important;
}

.flatpickr-day:hover {
  background: #eff6ff !important;
  border-color: #bfdbfe !important;
  color: #1d4ed8 !important;
}

.flatpickr-day.today {
  border-color: #3b82f6 !important;
  background: #f0f7ff !important;
  color: #1d4ed8 !important;
  font-weight: 700 !important;
}

.flatpickr-day.selected,
.flatpickr-day.selected:hover,
.flatpickr-day.selected:focus {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
  border-color: #1d4ed8 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  box-shadow: 0 4px 10px rgba(37, 99, 235, 0.4) !important;
}

.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
  color: #cbd5e1 !important;
}

.flatpickr-day.prevMonthDay:hover,
.flatpickr-day.nextMonthDay:hover {
  background: #f8fafc !important;
  color: #94a3b8 !important;
}

.flatpickr-day.flatpickr-disabled,
.flatpickr-day.flatpickr-disabled:hover {
  color: #e2e8f0 !important;
  cursor: not-allowed !important;
}

/* ========================================================= */
/* DARK MODE SUPPORT FOR FLATPICKR (Rule 2 & 3 Compliance)  */
/* ========================================================= */
[data-bs-theme="dark"] .flatpickr-calendar,
.dark-theme .flatpickr-calendar,
.dark .flatpickr-calendar {
  background: #0f172a !important;
  border-color: #334155 !important;
  box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5) !important;
}

[data-bs-theme="dark"] .flatpickr-weekdays,
.dark-theme .flatpickr-weekdays,
.dark .flatpickr-weekdays {
  background: #1e293b !important;
  border-bottom-color: #334155 !important;
}

[data-bs-theme="dark"] .flatpickr-weekday,
.dark-theme .flatpickr-weekday,
.dark .flatpickr-weekday {
  color: #94a3b8 !important;
}

[data-bs-theme="dark"] .flatpickr-day,
.dark-theme .flatpickr-day,
.dark .flatpickr-day {
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .flatpickr-day:hover,
.dark-theme .flatpickr-day:hover,
.dark .flatpickr-day:hover {
  background: #1e293b !important;
  border-color: #3b82f6 !important;
  color: #60a5fa !important;
}

[data-bs-theme="dark"] .flatpickr-day.today,
.dark-theme .flatpickr-day.today,
.dark .flatpickr-day.today {
  border-color: #60a5fa !important;
  background: rgba(59, 130, 246, 0.15) !important;
  color: #93c5fd !important;
}

[data-bs-theme="dark"] .flatpickr-day.prevMonthDay,
[data-bs-theme="dark"] .flatpickr-day.nextMonthDay,
.dark-theme .flatpickr-day.prevMonthDay,
.dark-theme .flatpickr-day.nextMonthDay,
.dark .flatpickr-day.prevMonthDay,
.dark .flatpickr-day.nextMonthDay {
  color: #475569 !important;
}

[data-bs-theme="dark"] .flatpickr-day.flatpickr-disabled,
.dark-theme .flatpickr-day.flatpickr-disabled,
.dark .flatpickr-day.flatpickr-disabled {
  color: #334155 !important;
}
</style>

