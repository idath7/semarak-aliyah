<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'dd/mm/yyyy' },
  id: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  hasError: { type: Boolean, default: false },
  inputClass: { type: String, default: '' },
  iconClass: { type: String, default: 'ri-calendar-event-line fs-15 text-primary' },
});

const emit = defineEmits(['update:modelValue']);
const inputElement = ref(null);
let fpInstance = null;

// Convert any format (ISO, YYYY-MM-DD, etc.) into dd/mm/yyyy
function toIndoDate(val) {
  if (!val) return '';
  if (/^\d{2}\/\d{2}\/\d{4}$/.test(val)) return val;
  const m = String(val).match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (m) {
    return `${m[3]}/${m[2]}/${m[1]}`;
  }
  const d = new Date(val);
  if (!isNaN(d.getTime())) {
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
  }
  return val;
}

onMounted(() => {
  if (inputElement.value) {
    fpInstance = flatpickr(inputElement.value, {
      locale: Indonesian,
      dateFormat: 'd/m/Y',
      allowInput: true,
      monthSelectorType: 'static',
      defaultDate: toIndoDate(props.modelValue) || undefined,
      onChange: (selectedDates, dateStr) => {
        emit('update:modelValue', dateStr);
      },
      onClose: (selectedDates, dateStr) => {
        emit('update:modelValue', dateStr || inputElement.value.value);
      }
    });

    // Sync initial normalized value if needed
    if (props.modelValue) {
      const normalized = toIndoDate(props.modelValue);
      if (normalized !== props.modelValue) {
        emit('update:modelValue', normalized);
      }
    }
  }
});

watch(() => props.modelValue, (newVal) => {
  const normalized = toIndoDate(newVal);
  if (fpInstance && inputElement.value) {
    if (inputElement.value.value !== normalized) {
      fpInstance.setDate(normalized, false, 'd/m/Y');
    }
  }
});

onBeforeUnmount(() => {
  if (fpInstance) {
    fpInstance.destroy();
  }
});

function handleInput(e) {
  emit('update:modelValue', e.target.value);
}
</script>

<template>
  <div class="position-relative w-100">
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
      style="padding-right: 36px;"
      @input="handleInput"
    />
    <span 
      class="position-absolute end-0 top-50 translate-middle-y pe-2.5 text-muted d-flex align-items-center"
      style="pointer-events: none;"
    >
      <i :class="iconClass"></i>
    </span>
  </div>
</template>

<style>
/* Velzon Premium Flatpickr Theme Customization */
.flatpickr-calendar {
  background: #ffffff !important;
  border: 1px solid #e9ebec !important;
  border-radius: 12px !important;
  box-shadow: 0 10px 30px rgba(17, 24, 39, 0.14) !important;
  font-family: var(--vz-font-sans-serif, 'Poppins', sans-serif) !important;
  width: 308px !important;
  padding: 8px !important;
  z-index: 1065 !important;
}

.flatpickr-calendar.arrowTop:before,
.flatpickr-calendar.arrowTop:after {
  border-bottom-color: #405189 !important;
}

.flatpickr-months {
  background: linear-gradient(135deg, #405189 0%, #364574 100%) !important;
  border-radius: 8px 8px 0 0 !important;
  color: #ffffff !important;
  padding: 6px 4px !important;
  position: relative !important;
  align-items: center !important;
}

.flatpickr-month {
  color: #ffffff !important;
  fill: #ffffff !important;
  height: 38px !important;
}

.flatpickr-current-month {
  color: #ffffff !important;
  font-size: 13.5px !important;
  font-weight: 600 !important;
  padding-top: 6px !important;
}

.flatpickr-current-month .cur-month {
  font-weight: 600 !important;
  color: #ffffff !important;
}

.flatpickr-current-month input.cur-year {
  font-weight: 600 !important;
  color: #ffffff !important;
}

.flatpickr-months .flatpickr-prev-month,
.flatpickr-months .flatpickr-next-month {
  color: #ffffff !important;
  fill: #ffffff !important;
  padding: 8px !important;
  border-radius: 6px !important;
  transition: all 0.2s ease !important;
}

.flatpickr-months .flatpickr-prev-month:hover,
.flatpickr-months .flatpickr-next-month:hover {
  background: rgba(255, 255, 255, 0.2) !important;
}

.flatpickr-months .flatpickr-prev-month svg,
.flatpickr-months .flatpickr-next-month svg {
  fill: #ffffff !important;
  width: 14px !important;
  height: 14px !important;
}

.flatpickr-weekdays {
  background: #f8f9fa !important;
  border-bottom: 1px solid #e9ebec !important;
  height: 32px !important;
  margin-top: 4px !important;
}

.flatpickr-weekday {
  color: #878a99 !important;
  font-size: 11px !important;
  font-weight: 600 !important;
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
  font-size: 12px !important;
  font-weight: 500 !important;
  color: #495057 !important;
  height: 36px !important;
  line-height: 36px !important;
  margin: 1px 0 !important;
  border: 1px solid transparent !important;
  transition: all 0.15s ease !important;
}

.flatpickr-day:hover {
  background: #f3f6f9 !important;
  border-color: #f3f6f9 !important;
  color: #405189 !important;
}

.flatpickr-day.today {
  border-color: #405189 !important;
  background: #f0f3fa !important;
  color: #405189 !important;
  font-weight: 700 !important;
}

.flatpickr-day.selected,
.flatpickr-day.selected:hover,
.flatpickr-day.selected:focus {
  background: #405189 !important;
  border-color: #405189 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  box-shadow: 0 3px 8px rgba(64, 81, 137, 0.4) !important;
}

.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
  color: #ced4da !important;
}

.flatpickr-day.prevMonthDay:hover,
.flatpickr-day.nextMonthDay:hover {
  background: #f8f9fa !important;
  color: #878a99 !important;
}
</style>
