<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

const props = defineProps({
  modelValue: { type: String, default: '08:00' },
  placeholder: { type: String, default: '08:00' },
  id: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  hasError: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);
const inputElement = ref(null);
let fpInstance = null;

// Normalize time string strictly into 24-hour format (HH:mm, 00:00 - 23:59)
function normalizeTime(val) {
  if (!val) return '08:00';
  const str = String(val).trim();
  
  // Check if string contains am/pm
  const ampm = str.match(/(\d{1,2})[:.](\d{2})\s*(am|pm)/i);
  if (ampm) {
    let h = parseInt(ampm[1], 10);
    const m = ampm[2];
    const isPm = ampm[3].toLowerCase() === 'pm';
    if (isPm && h < 12) h += 12;
    if (!isPm && h === 12) h = 0;
    return `${String(h).padStart(2, '0')}:${m}`;
  }

  const match = str.match(/(\d{1,2})[:.](\d{2})/);
  if (match) {
    let h = parseInt(match[1], 10);
    let m = parseInt(match[2], 10);
    if (isNaN(h)) h = 8;
    if (isNaN(m)) m = 0;
    if (h > 23) h = 23;
    if (h < 0) h = 0;
    if (m > 59) m = 59;
    if (m < 0) m = 0;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
  }

  // Handle bare digits e.g. "8" -> "08:00", "14" -> "14:00"
  if (/^\d{1,2}$/.test(str)) {
    let h = parseInt(str, 10);
    if (h > 23) h = 23;
    return `${String(h).padStart(2, '0')}:00`;
  }

  return '08:00';
}

onMounted(() => {
  if (inputElement.value) {
    fpInstance = flatpickr(inputElement.value, {
      locale: Indonesian,
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i',
      time_24hr: true,
      minuteIncrement: 5,
      allowInput: true,
      defaultDate: normalizeTime(props.modelValue) || '08:00',
      onChange: (selectedDates, dateStr) => {
        const norm = normalizeTime(dateStr);
        emit('update:modelValue', norm);
      },
      onClose: (selectedDates, dateStr) => {
        const val = dateStr || inputElement.value.value;
        const norm = normalizeTime(val);
        emit('update:modelValue', norm);
      }
    });

    if (props.modelValue) {
      const norm = normalizeTime(props.modelValue);
      if (norm !== props.modelValue) {
        emit('update:modelValue', norm);
      }
    }
  }
});

watch(() => props.modelValue, (newVal) => {
  const norm = normalizeTime(newVal);
  if (fpInstance && inputElement.value) {
    if (inputElement.value.value !== norm) {
      fpInstance.setDate(norm, false, 'H:i');
    }
  }
});

onBeforeUnmount(() => {
  if (fpInstance) {
    fpInstance.destroy();
  }
});

function handleInput(e) {
  let val = e.target.value.replace(/[^\d:]/g, '');
  if (val.length === 2 && !val.includes(':')) {
    val = val + ':';
  }
  emit('update:modelValue', val);
}

function handleBlur(e) {
  const norm = normalizeTime(e.target.value);
  emit('update:modelValue', norm);
  if (inputElement.value) {
    inputElement.value.value = norm;
  }
}
</script>

<template>
  <div class="input-group" style="height: 37.5px;">
    <input
      :id="id"
      ref="inputElement"
      type="text"
      :value="normalizeTime(modelValue)"
      :placeholder="placeholder"
      :disabled="disabled"
      class="form-control fs-13 text-center bg-white px-1 fw-semibold font-monospace"
      :class="{ 'is-invalid': hasError }"
      autocomplete="off"
      maxlength="5"
      style="height: 37.5px; letter-spacing: 1px;"
      @input="handleInput"
      @blur="handleBlur"
    />
    <span 
      class="input-group-text fs-11 bg-light text-primary fw-bold px-2.5 user-select-none" 
      style="height: 37.5px; border-color: #ced4da;"
      title="Waktu Indonesia Barat (Murni Format 24 Jam)"
    >
      WIB
    </span>
  </div>
</template>

<style>
/* 24-Hour Flatpickr Time Theme - Completely excludes AM/PM */
.flatpickr-calendar.hasTime.noCalendar {
  width: 140px !important;
  border-radius: 10px !important;
  box-shadow: 0 8px 24px rgba(17, 24, 39, 0.15) !important;
  border: 1px solid #e9ebec !important;
  padding: 6px !important;
  z-index: 1065 !important;
}

.flatpickr-time {
  height: 42px !important;
  line-height: 42px !important;
  border-top: none !important;
}

.flatpickr-time .numInputWrapper {
  height: 40px !important;
}

.flatpickr-time input {
  font-size: 15px !important;
  font-weight: 700 !important;
  color: #212529 !important;
  font-family: monospace !important;
}

.flatpickr-time input:hover,
.flatpickr-time input:focus {
  background: #f0f3fa !important;
}

.flatpickr-time .flatpickr-time-separator {
  font-weight: 700 !important;
  font-size: 16px !important;
  color: #405189 !important;
}

.flatpickr-am-pm {
  display: none !important;
}
</style>
