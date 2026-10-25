<template>
  <div class="rich-text-editor-container" :class="{ 'is-disabled': disabled, 'is-focused': isFocused }">
    <!-- Toolbar -->
    <div class="rte-toolbar" role="toolbar" aria-label="Editor Formatting Toolbar">
      <!-- History Group -->
      <div class="rte-btn-group">
        <button 
          type="button" 
          class="rte-btn" 
          title="Urungkan (Undo)" 
          @mousedown.prevent="exec('undo')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-arrow-go-back-line"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn" 
          title="Ulangi (Redo)" 
          @mousedown.prevent="exec('redo')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-arrow-go-forward-line"></i>
        </button>
      </div>

      <div class="rte-divider"></div>

      <!-- Format Block (Paragraph, Headings) -->
      <div class="rte-btn-group" style="min-width: 170px;">
        <CustomSelect 
          :model-value="activeBlock" 
          @change="setBlockFormat"
          size="xs"
          :disabled="disabled || isCodeMode"
        >
          <option value="p">Paragraf Normal</option>
          <option value="h4">Judul Bagian (H4)</option>
          <option value="h5">Sub Judul (H5)</option>
          <option value="blockquote">Kutipan / Catatan (Quote)</option>
        </CustomSelect>
      </div>

      <div class="rte-divider"></div>

      <!-- Inline Text Formats -->
      <div class="rte-btn-group">
        <button 
          type="button" 
          class="rte-btn" 
          :class="{ 'is-active': activeFormats.bold }"
          title="Tebal (Bold) - Ctrl+B" 
          @mousedown.prevent="exec('bold')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-bold"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn" 
          :class="{ 'is-active': activeFormats.italic }"
          title="Miring (Italic) - Ctrl+I" 
          @mousedown.prevent="exec('italic')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-italic"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn" 
          :class="{ 'is-active': activeFormats.underline }"
          title="Garis Bawah (Underline) - Ctrl+U" 
          @mousedown.prevent="exec('underline')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-underline"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn" 
          :class="{ 'is-active': activeFormats.strikeThrough }"
          title="Coret (Strikethrough)" 
          @mousedown.prevent="exec('strikeThrough')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-strikethrough"></i>
        </button>
      </div>

      <div class="rte-divider"></div>

      <!-- Color Palette Dropdown -->
      <div class="rte-btn-group position-relative" ref="colorDropdownRef">
        <button 
          type="button" 
          class="rte-btn d-flex align-items-center gap-1" 
          title="Warna Teks"
          @mousedown.prevent="toggleColorMenu"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-font-color" :style="{ color: activeColor }"></i>
          <span class="rte-color-dot" :style="{ backgroundColor: activeColor }"></span>
          <i class="ri-arrow-down-s-fill fs-10 text-muted"></i>
        </button>

        <!-- Color Palette Dropdown Menu -->
        <div v-if="showColorMenu" class="rte-color-menu shadow-lg">
          <div class="rte-color-grid">
            <button 
              v-for="color in colorPresets" 
              :key="color.value"
              type="button"
              class="rte-color-swatch" 
              :title="color.label"
              :style="{ backgroundColor: color.value }"
              @mousedown.prevent="applyColor(color.value)"
            ></button>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
            <button 
              type="button" 
              class="btn btn-xs btn-outline-secondary w-100" 
              @mousedown.prevent="applyColor('#1e293b')"
            >
              Reset Warna
            </button>
          </div>
        </div>
      </div>

      <div class="rte-divider"></div>

      <!-- Lists -->
      <div class="rte-btn-group">
        <button 
          type="button" 
          class="rte-btn" 
          :class="{ 'is-active': activeFormats.insertUnorderedList }"
          title="Daftar Poin (Bulleted List)" 
          @mousedown.prevent="exec('insertUnorderedList')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-list-unordered"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn" 
          :class="{ 'is-active': activeFormats.insertOrderedList }"
          title="Daftar Angka (Numbered List)" 
          @mousedown.prevent="exec('insertOrderedList')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-list-ordered"></i>
        </button>
      </div>

      <div class="rte-divider"></div>

      <!-- Link & Clean -->
      <div class="rte-btn-group">
        <button 
          type="button" 
          class="rte-btn" 
          title="Sisipkan / Edit Tautan (Link)" 
          @mousedown.prevent="openLinkPrompt"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-link"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn" 
          title="Hapus Tautan (Unlink)" 
          @mousedown.prevent="exec('unlink')"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-link-unlink-m"></i>
        </button>
        <button 
          type="button" 
          class="rte-btn text-danger" 
          title="Bersihkan Format Teks" 
          @mousedown.prevent="clearFormatting"
          :disabled="disabled || isCodeMode"
        >
          <i class="ri-format-clear"></i>
        </button>
      </div>

      <!-- Mode Switch (Visual vs Code) -->
      <div class="ms-auto d-flex align-items-center gap-1">
        <button 
          type="button" 
          class="rte-mode-btn" 
          :class="{ 'is-active': !isCodeMode }"
          @click="switchToVisualMode"
          title="Mode Tampilan Visual"
        >
          <i class="ri-eye-line me-1"></i> Visual
        </button>
        <button 
          type="button" 
          class="rte-mode-btn" 
          :class="{ 'is-active': isCodeMode }"
          @click="switchToCodeMode"
          title="Mode Kode Sumber HTML"
        >
          <i class="ri-code-s-slash-line me-1"></i> HTML
        </button>
      </div>
    </div>

    <!-- Link Dialog Modal / Inline Popup -->
    <div v-if="showLinkModal" class="rte-link-modal-overlay" @click.self="showLinkModal = false">
      <div class="rte-link-modal shadow-lg">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="fw-semibold fs-13"><i class="ri-link text-primary me-1"></i> Sisipkan Tautan</span>
          <button type="button" class="btn-close fs-11" @click="showLinkModal = false"></button>
        </div>
        <div class="mb-2">
          <label class="form-label fs-11 text-muted mb-1">URL / Alamat Web</label>
          <input 
            type="text" 
            v-model="linkUrl" 
            ref="linkInputRef"
            class="form-control form-control-sm" 
            placeholder="https://contoh.com atau mailto:..."
            @keydown.enter.prevent="submitLink"
          />
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="linkTargetBlank" v-model="linkOpenNewTab" />
          <label class="form-check-label fs-12 text-muted" for="linkTargetBlank">
            Buka di tab baru (target="_blank")
          </label>
        </div>
        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-sm btn-light" @click="showLinkModal = false">Batal</button>
          <button type="button" class="btn btn-sm btn-primary" @click="submitLink">Terapkan Tautan</button>
        </div>
      </div>
    </div>

    <!-- Editor Body -->
    <div class="rte-body-wrapper">
      <!-- WYSIWYG Visual Editor -->
      <div 
        v-show="!isCodeMode"
        ref="editorRef"
        class="rte-content"
        contenteditable="true"
        :style="{ minHeight: minHeight, maxHeight: maxHeight }"
        :data-placeholder="placeholder"
        @input="onVisualInput"
        @focus="isFocused = true"
        @blur="isFocused = false; updateFormatStates()"
        @keyup="updateFormatStates"
        @mouseup="updateFormatStates"
        @paste="onPaste"
      ></div>

      <!-- Raw HTML Code Editor -->
      <textarea 
        v-show="isCodeMode"
        v-model="rawHtml"
        class="rte-code-area"
        :style="{ minHeight: minHeight, maxHeight: maxHeight }"
        placeholder="Ketik atau paste kode HTML di sini..."
        @input="onCodeInput"
        @focus="isFocused = true"
        @blur="isFocused = false"
      ></textarea>
    </div>

    <!-- Status & Counter Footer -->
    <div class="rte-footer">
      <div class="d-flex align-items-center gap-2 text-muted fs-11">
        <span class="badge bg-light text-secondary border">
          {{ isCodeMode ? 'Mode HTML' : 'Mode Visual WYSIWYG' }}
        </span>
        <span v-if="!isCodeMode" class="d-none d-sm-inline">
          <i class="ri-information-line me-1 text-primary"></i>Tekan Enter untuk paragraf baru, Shift+Enter untuk baris baru.
        </span>
      </div>

      <div class="d-flex align-items-center gap-3 text-muted fs-11">
        <span><strong>{{ wordCount }}</strong> kata</span>
        <span>•</span>
        <span><strong>{{ charCount }}</strong> karakter</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Tuliskan jawaban FAQ lengkap dengan format yang rapi...'
  },
  minHeight: {
    type: String,
    default: '220px'
  },
  maxHeight: {
    type: String,
    default: '450px'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue']);

// Editor State
const editorRef = ref(null);
const colorDropdownRef = ref(null);
const linkInputRef = ref(null);
const isFocused = ref(false);
const isCodeMode = ref(false);
const rawHtml = ref('');
const showColorMenu = ref(false);
const activeColor = ref('#2563eb');
const showLinkModal = ref(false);
const linkUrl = ref('');
const linkOpenNewTab = ref(true);
let savedRange = null;

// Format Active States
const activeBlock = ref('p');
const activeFormats = ref({
  bold: false,
  italic: false,
  underline: false,
  strikeThrough: false,
  insertUnorderedList: false,
  insertOrderedList: false,
});

// Presets
const colorPresets = [
  { label: 'Biru Utama (Primary)', value: '#2563eb' },
  { label: 'Indigo Gelap', value: '#4f46e5' },
  { label: 'Hijau Emerald', value: '#059669' },
  { label: 'Kuning Amber / Gold', value: '#d97706' },
  { label: 'Merah Crimson', value: '#dc2626' },
  { label: 'Ungu Purple', value: '#7c3aed' },
  { label: 'Cyan Info', value: '#0891b2' },
  { label: 'Abu-abu Slate', value: '#475569' },
  { label: 'Hitam Pekat', value: '#0f172a' },
];

// Computed Counters
const plainText = computed(() => {
  const source = isCodeMode.value ? rawHtml.value : (editorRef.value ? editorRef.value.innerText : props.modelValue);
  const temp = document.createElement('div');
  temp.innerHTML = source || '';
  return temp.textContent || temp.innerText || '';
});

const wordCount = computed(() => {
  const text = plainText.value.trim();
  if (!text) return 0;
  return text.split(/\s+/).filter(Boolean).length;
});

const charCount = computed(() => {
  return plainText.value.length;
});

// Sync from modelValue into editor
watch(() => props.modelValue, (newVal) => {
  if (isCodeMode.value) {
    if (newVal !== rawHtml.value) {
      rawHtml.value = newVal || '';
    }
  } else if (editorRef.value) {
    const currentHtml = editorRef.value.innerHTML;
    if (newVal !== currentHtml && (newVal || '') !== (currentHtml === '<p><br></p>' ? '' : currentHtml)) {
      editorRef.value.innerHTML = newVal || '';
    }
  }
});

onMounted(() => {
  if (editorRef.value) {
    editorRef.value.innerHTML = props.modelValue || '';
  }
  rawHtml.value = props.modelValue || '';
  document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick);
});

function handleOutsideClick(e) {
  if (colorDropdownRef.value && !colorDropdownRef.value.contains(e.target)) {
    showColorMenu.value = false;
  }
}

// Commands execution
function exec(command, value = null) {
  if (props.disabled || isCodeMode.value) return;
  document.execCommand(command, false, value);
  if (editorRef.value) {
    editorRef.value.focus();
    emitUpdate();
  }
  updateFormatStates();
}

function setBlockFormat(tag) {
  if (props.disabled || isCodeMode.value) return;
  activeBlock.value = tag;
  if (tag === 'blockquote') {
    document.execCommand('formatBlock', false, '<blockquote>');
  } else if (tag.startsWith('h')) {
    document.execCommand('formatBlock', false, `<${tag}>`);
  } else {
    document.execCommand('formatBlock', false, '<p>');
  }
  emitUpdate();
  if (editorRef.value) editorRef.value.focus();
}

function updateFormatStates() {
  if (isCodeMode.value) return;
  try {
    activeFormats.value = {
      bold: document.queryCommandState('bold'),
      italic: document.queryCommandState('italic'),
      underline: document.queryCommandState('underline'),
      strikeThrough: document.queryCommandState('strikeThrough'),
      insertUnorderedList: document.queryCommandState('insertUnorderedList'),
      insertOrderedList: document.queryCommandState('insertOrderedList'),
    };

    // Detect block format
    const selection = window.getSelection();
    if (selection && selection.rangeCount > 0) {
      let node = selection.getRangeAt(0).commonAncestorContainer;
      if (node.nodeType === 3) node = node.parentNode;
      while (node && node !== editorRef.value) {
        const tagName = node.tagName ? node.tagName.toLowerCase() : '';
        if (['h4', 'h5', 'blockquote'].includes(tagName)) {
          activeBlock.value = tagName;
          return;
        }
        node = node.parentNode;
      }
      activeBlock.value = 'p';
    }
  } catch (err) {
    // Ignore query state error
  }
}

function toggleColorMenu() {
  showColorMenu.value = !showColorMenu.value;
}

function applyColor(color) {
  activeColor.value = color;
  showColorMenu.value = false;
  exec('foreColor', color);
}

function clearFormatting() {
  exec('removeFormat');
  document.execCommand('formatBlock', false, '<p>');
  emitUpdate();
}

// Link Modal
function saveSelection() {
  const sel = window.getSelection();
  if (sel.getRangeCount() > 0) {
    savedRange = sel.getRangeAt(0).cloneRange();
  }
}

function restoreSelection() {
  if (savedRange) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }
}

function openLinkPrompt() {
  saveSelection();
  linkUrl.value = '';
  showLinkModal.value = true;
  nextTick(() => {
    if (linkInputRef.value) linkInputRef.value.focus();
  });
}

function submitLink() {
  let url = (linkUrl.value || '').trim();
  if (!url) {
    showLinkModal.value = false;
    return;
  }
  if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url) && !/^tel:/i.test(url)) {
    url = 'https://' + url;
  }

  showLinkModal.value = false;
  restoreSelection();

  if (linkOpenNewTab.value) {
    const sel = window.getSelection();
    if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
      const a = document.createElement('a');
      a.href = url;
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      a.className = 'faq-link';
      a.appendChild(sel.getRangeAt(0).extractContents());
      sel.getRangeAt(0).insertNode(a);
      emitUpdate();
      return;
    }
  }

  document.execCommand('createLink', false, url);
  emitUpdate();
}

// Paste Handler to clean up dirty Microsoft Word / Web styles
function onPaste(e) {
  // Let default paste or clean if needed
  setTimeout(() => {
    emitUpdate();
  }, 10);
}

// Inputs
function onVisualInput() {
  emitUpdate();
}

function onCodeInput() {
  emit('update:modelValue', rawHtml.value);
}

function emitUpdate() {
  if (!editorRef.value) return;
  let html = editorRef.value.innerHTML;
  if (html === '<p><br></p>' || html === '<br>' || html === '<p></p>') {
    html = '';
  }
  rawHtml.value = html;
  emit('update:modelValue', html);
}

// Mode switches
function switchToCodeMode() {
  if (isCodeMode.value) return;
  if (editorRef.value) {
    rawHtml.value = editorRef.value.innerHTML;
  }
  isCodeMode.value = true;
}

function switchToVisualMode() {
  if (!isCodeMode.value) return;
  isCodeMode.value = false;
  nextTick(() => {
    if (editorRef.value) {
      editorRef.value.innerHTML = rawHtml.value;
    }
    emitUpdate();
  });
}
</script>

<style scoped>
.rich-text-editor-container {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background-color: #ffffff;
  overflow: hidden;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  display: flex;
  flex-direction: column;
}

.rich-text-editor-container.is-focused {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.rich-text-editor-container.is-disabled {
  opacity: 0.65;
  pointer-events: none;
  background-color: #f8fafc;
}

/* Toolbar */
.rte-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  padding: 8px 10px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  user-select: none;
}

.rte-btn-group {
  display: inline-flex;
  align-items: center;
  gap: 2px;
}

.rte-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  padding: 0;
  border: 1px solid transparent;
  border-radius: 6px;
  background: transparent;
  color: #334155;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.rte-btn:hover:not(:disabled) {
  background-color: #e2e8f0;
  color: #0f172a;
}

.rte-btn.is-active {
  background-color: #dbeafe;
  color: #1d4ed8;
  border-color: #bfdbfe;
  font-weight: 600;
}

.rte-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.rte-select {
  height: 30px;
  padding: 2px 24px 2px 8px;
  font-size: 12px;
  font-weight: 500;
  border-color: #cbd5e1;
  border-radius: 6px;
  background-color: #ffffff;
  color: #1e293b;
  cursor: pointer;
}

.rte-select:focus {
  border-color: #3b82f6;
  box-shadow: none;
}

.rte-divider {
  width: 1px;
  height: 20px;
  background-color: #cbd5e1;
  margin: 0 4px;
}

.rte-color-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 1px solid rgba(0,0,0,0.15);
}

.rte-color-menu {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 1060;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px;
  width: 190px;
  margin-top: 4px;
}

.rte-color-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 6px;
}

.rte-color-swatch {
  width: 26px;
  height: 26px;
  border-radius: 6px;
  border: 1px solid rgba(0,0,0,0.15);
  cursor: pointer;
  transition: transform 0.1s ease;
}

.rte-color-swatch:hover {
  transform: scale(1.15);
}

.btn-xs {
  padding: 2px 6px;
  font-size: 11px;
}

.rte-mode-btn {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.rte-mode-btn:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.rte-mode-btn.is-active {
  background: #1e293b;
  color: #ffffff;
  border-color: #1e293b;
}

/* Link Modal */
.rte-link-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 1070;
  background: rgba(15, 23, 42, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.rte-link-modal {
  background: #ffffff;
  border-radius: 10px;
  width: 100%;
  max-width: 380px;
  padding: 18px;
  border: 1px solid #e2e8f0;
}

/* Body / Editable */
.rte-body-wrapper {
  position: relative;
  width: 100%;
  background: #ffffff;
}

.rte-content {
  padding: 14px 16px;
  outline: none;
  font-size: 14px;
  line-height: 1.65;
  color: #1e293b;
  overflow-y: auto;
  word-break: break-word;
}

.rte-content:empty:before {
  content: attr(data-placeholder);
  color: #94a3b8;
  pointer-events: none;
  display: block;
}

.rte-code-area {
  width: 100%;
  padding: 12px 16px;
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  font-size: 12.5px;
  line-height: 1.5;
  background-color: #0f172a;
  color: #38bdf8;
  border: none;
  outline: none;
  resize: vertical;
}

/* Content typography */
:deep(.rte-content) p {
  margin-bottom: 0.75rem;
}

:deep(.rte-content) p:last-child {
  margin-bottom: 0;
}

:deep(.rte-content) h4 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}

:deep(.rte-content) h5 {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin-top: 0.8rem;
  margin-bottom: 0.4rem;
}

:deep(.rte-content) ul,
:deep(.rte-content) ol {
  padding-left: 1.4rem;
  margin-bottom: 0.75rem;
}

:deep(.rte-content) li {
  margin-bottom: 0.25rem;
}

:deep(.rte-content) blockquote {
  border-left: 4px solid #3b82f6;
  padding-left: 12px;
  margin: 10px 0;
  color: #475569;
  font-style: italic;
  background: #f8fafc;
  padding: 8px 12px;
  border-radius: 0 6px 6px 0;
}

:deep(.rte-content) a {
  color: #2563eb;
  text-decoration: underline;
  font-weight: 500;
}

/* Footer */
.rte-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}
</style>
