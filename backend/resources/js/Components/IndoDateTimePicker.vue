<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih tanggal & waktu selesai (24 Jam WIB)...' },
  id: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  hasError: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'formatted']);
const inputElement = ref(null);
let fpInstance = null;

const INDO_MONTHS = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

function formatIndoDateTime(val) {
  if (!val) return '';
  const d = new Date(String(val).replace(' ', 'T'));
  if (isNaN(d.getTime())) return '';
  const day = d.getDate();
  const month = INDO_MONTHS[d.getMonth()];
  const year = d.getFullYear();
  const hour = String(d.getHours()).padStart(2, '0');
  const minute = String(d.getMinutes()).padStart(2, '0');
  return `${day} ${month} ${year}, ${hour}:${minute} WIB`;
}

onMounted(() => {
  if (inputElement.value) {
    fpInstance = flatpickr(inputElement.value, {
      locale: Indonesian,
      enableTime: true,
      time_24hr: true,
      minuteIncrement: 5,
      dateFormat: 'Y-m-d H:i',
      altInput: true,
      altFormat: 'j F Y, H:i \\W\\I\\B',
      altInputClass: `form-control ${props.hasError ? 'is-invalid' : ''}`,
      allowInput: true,
      monthSelectorType: 'static',
      defaultDate: props.modelValue || undefined,
      onChange: (selectedDates, dateStr) => {
        emit('update:modelValue', dateStr);
        if (selectedDates.length > 0) {
          emit('formatted', formatIndoDateTime(selectedDates[0]));
        } else {
          emit('formatted', '');
        }
      },
      onClose: (selectedDates, dateStr) => {
        emit('update:modelValue', dateStr);
        if (selectedDates.length > 0) {
          emit('formatted', formatIndoDateTime(selectedDates[0]));
        }
      }
    });
  }
});

watch(() => props.modelValue, (newVal) => {
  if (fpInstance) {
    const curVal = fpInstance.input.value;
    if (newVal !== curVal) {
      fpInstance.setDate(newVal || '', false);
      if (newVal) {
        emit('formatted', formatIndoDateTime(newVal));
      }
    }
  }
});

onBeforeUnmount(() => {
  if (fpInstance) {
    fpInstance.destroy();
    fpInstance = null;
  }
});
</script>

<template>
  <div class="indo-datetime-picker position-relative">
    <input
      :id="id"
      ref="inputElement"
      type="text"
      class="form-control"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
    />
  </div>
</template>

<style>
/* 24-Hour Indonesian Flatpickr Theme - No AM/PM, Sleek Velzon Styling */
.flatpickr-calendar {
  background: #ffffff !important;
  border: 1px solid #e9ebec !important;
  border-radius: 12px !important;
  box-shadow: 0 12px 35px rgba(17, 24, 39, 0.18) !important;
  font-family: var(--vz-font-sans-serif, 'Plus Jakarta Sans', sans-serif) !important;
  width: 310px !important;
  padding: 8px !important;
  z-index: 1065 !important;
}

.flatpickr-months {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
  border-radius: 8px 8px 0 0 !important;
  color: #ffffff !important;
  padding: 6px 4px !important;
  align-items: center !important;
}

.flatpickr-current-month {
  color: #ffffff !important;
  font-size: 13.5px !important;
  font-weight: 700 !important;
}

.flatpickr-current-month .cur-month {
  font-weight: 700 !important;
  color: #ffffff !important;
}

.flatpickr-current-month input.cur-year {
  font-weight: 700 !important;
  color: #ffffff !important;
}

.flatpickr-months .flatpickr-prev-month svg,
.flatpickr-months .flatpickr-next-month svg {
  fill: #ffffff !important;
}

.flatpickr-day.selected,
.flatpickr-day.selected:hover {
  background: #f59e0b !important;
  border-color: #f59e0b !important;
  color: #ffffff !important;
  font-weight: 700 !important;
}

.flatpickr-day.today {
  border-color: #f59e0b !important;
  color: #d97706 !important;
  font-weight: 700 !important;
}

.flatpickr-time {
  border-top: 1px solid #f1f5f9 !important;
  padding: 8px 0 !important;
  background: #f8fafc !important;
  border-radius: 0 0 8px 8px !important;
}

.flatpickr-time input {
  font-weight: 700 !important;
  color: #1e293b !important;
}

/* Explicitly hide AM/PM toggle as it is strictly 24 hours format */
.flatpickr-am-pm {
  display: none !important;
}
</style>
