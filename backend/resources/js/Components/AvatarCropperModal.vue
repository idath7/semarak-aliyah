<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Sesuaikan & Potong Foto Profil',
  },
  currentAvatar: {
    type: String,
    default: null,
  },
  userName: {
    type: String,
    default: 'Pengguna',
  },
  isProcessing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close', 'save', 'delete']);

// DOM Refs
const canvasRef = ref(null);
const fileInputRef = ref(null);

// Image & Transform State
const rawImageSrc = ref(null);
const imageObj = ref(null);
const isImageLoaded = ref(false);
const isDragOver = ref(false);

const scale = ref(1.0);
const panX = ref(0);
const panY = ref(0);
const rotation = ref(0); // 0, 90, 180, 270

// Live preview data URL
const livePreviewUrl = ref(null);

// Canvas size constants
const CANVAS_SIZE = 360;
const CROP_RADIUS = 135; // diameter 270px

// Mouse & Touch Dragging State
const isDragging = ref(false);
let startDragX = 0;
let startDragY = 0;
let initialPinchDistance = null;
let initialPinchScale = 1.0;

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal) {
    nextTick(() => {
      if (props.currentAvatar && !rawImageSrc.value) {
        loadImageFromUrl(props.currentAvatar);
      } else if (rawImageSrc.value) {
        renderCanvas();
      }
    });
  } else {
    resetState();
  }
});

function resetState() {
  scale.value = 1.0;
  panX.value = 0;
  panY.value = 0;
  rotation.value = 0;
  isDragging.value = false;
  livePreviewUrl.value = null;
}

function resetTransform() {
  scale.value = 1.0;
  panX.value = 0;
  panY.value = 0;
  rotation.value = 0;
  renderCanvas();
}

// -----------------------------------------------------------------------------
// FILE LOADING & HANDLING
// -----------------------------------------------------------------------------
function triggerFileInput() {
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
    fileInputRef.value.click();
  }
}

function onFileChange(e) {
  const file = e.target.files?.[0];
  if (file) {
    processFile(file);
  }
}

function onDrop(e) {
  isDragOver.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file) {
    processFile(file);
  }
}

function processFile(file) {
  if (!file.type.startsWith('image/')) {
    alert('Harap pilih berkas gambar yang sah (JPG, PNG, WEBP).');
    return;
  }
  const reader = new FileReader();
  reader.onload = (evt) => {
    loadImageFromUrl(evt.target.result);
  };
  reader.readAsDataURL(file);
}

function loadImageFromUrl(url) {
  const img = new Image();
  img.crossOrigin = 'anonymous';
  img.onload = () => {
    imageObj.value = img;
    rawImageSrc.value = url;
    isImageLoaded.value = true;
    
    // Auto-fit initial scale so entire image fills crop circle nicely
    const minDim = Math.min(img.width, img.height);
    const fitScale = (CROP_RADIUS * 2) / minDim;
    scale.value = Math.max(0.05, Math.min(fitScale * 1.05, 2.0));
    panX.value = 0;
    panY.value = 0;
    rotation.value = 0;

    nextTick(() => {
      renderCanvas();
    });
  };
  img.onerror = () => {
    isImageLoaded.value = false;
  };
  img.src = url;
}

// -----------------------------------------------------------------------------
// CANVAS RENDERING
// -----------------------------------------------------------------------------
function renderCanvas() {
  const canvas = canvasRef.value;
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  const w = canvas.width;
  const h = canvas.height;
  const cx = w / 2;
  const cy = h / 2;

  ctx.clearRect(0, 0, w, h);

  // 1. Draw Checkerboard background for transparency
  drawCheckerboard(ctx, w, h);

  // 2. Draw Transformed Image if loaded
  if (imageObj.value && isImageLoaded.value) {
    ctx.save();
    ctx.translate(cx + panX.value, cy + panY.value);
    ctx.rotate((rotation.value * Math.PI) / 180);
    ctx.scale(scale.value, scale.value);
    
    const iw = imageObj.value.width;
    const ih = imageObj.value.height;
    ctx.drawImage(imageObj.value, -iw / 2, -ih / 2, iw, ih);
    ctx.restore();
  }

  // 3. Draw Dark Mask overlay outside circular crop area
  ctx.save();
  ctx.fillStyle = 'rgba(15, 23, 42, 0.72)';
  ctx.beginPath();
  ctx.rect(0, 0, w, h);
  ctx.arc(cx, cy, CROP_RADIUS, 0, Math.PI * 2, true);
  ctx.closePath();
  ctx.fill();

  // 4. Draw Guide Grid (Rule of Thirds) inside circle
  ctx.save();
  ctx.beginPath();
  ctx.arc(cx, cy, CROP_RADIUS, 0, Math.PI * 2);
  ctx.clip();

  ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
  ctx.lineWidth = 1;
  const third = (CROP_RADIUS * 2) / 3;
  const startX = cx - CROP_RADIUS;
  const startY = cy - CROP_RADIUS;

  // Vertical lines
  ctx.beginPath();
  ctx.moveTo(startX + third, startY);
  ctx.lineTo(startX + third, startY + CROP_RADIUS * 2);
  ctx.moveTo(startX + third * 2, startY);
  ctx.lineTo(startX + third * 2, startY + CROP_RADIUS * 2);

  // Horizontal lines
  ctx.moveTo(startX, startY + third);
  ctx.lineTo(startX + CROP_RADIUS * 2, startY + third);
  ctx.moveTo(startX, startY + third * 2);
  ctx.lineTo(startX + CROP_RADIUS * 2, startY + third * 2);
  ctx.stroke();

  // Center crosshair
  ctx.strokeStyle = 'rgba(56, 189, 248, 0.4)';
  ctx.beginPath();
  ctx.moveTo(cx - 8, cy);
  ctx.lineTo(cx + 8, cy);
  ctx.moveTo(cx, cy - 8);
  ctx.lineTo(cx, cy + 8);
  ctx.stroke();
  ctx.restore();

  // 5. Draw Circular Border with nice accent color
  ctx.beginPath();
  ctx.arc(cx, cy, CROP_RADIUS, 0, Math.PI * 2);
  ctx.strokeStyle = '#38bdf8';
  ctx.lineWidth = 2.5;
  ctx.stroke();

  ctx.restore();

  // Update live preview thumbnail
  updateLivePreview();
}

function drawCheckerboard(ctx, w, h) {
  const tileSize = 12;
  for (let x = 0; x < w; x += tileSize) {
    for (let y = 0; y < h; y += tileSize) {
      ctx.fillStyle = ((x / tileSize + y / tileSize) % 2 === 0) ? '#1e293b' : '#334155';
      ctx.fillRect(x, y, tileSize, tileSize);
    }
  }
}

// Generate cropped preview dataUrl
function updateLivePreview() {
  if (!imageObj.value || !isImageLoaded.value) {
    livePreviewUrl.value = null;
    return;
  }
  livePreviewUrl.value = generateCroppedDataUrl(120);
}

// -----------------------------------------------------------------------------
// MOUSE & TOUCH DRAGGING / ZOOMING
// -----------------------------------------------------------------------------
function onMouseDown(e) {
  if (!isImageLoaded.value) return;
  isDragging.value = true;
  startDragX = e.clientX - panX.value;
  startDragY = e.clientY - panY.value;
}

function onMouseMove(e) {
  if (!isDragging.value) return;
  panX.value = e.clientX - startDragX;
  panY.value = e.clientY - startDragY;
  renderCanvas();
}

function onMouseUp() {
  isDragging.value = false;
}

function onWheel(e) {
  if (!isImageLoaded.value) return;
  e.preventDefault();
  const zoomFactor = e.deltaY < 0 ? 0.08 : -0.08;
  adjustZoom(zoomFactor);
}

// Touch Handling (Pan & Pinch Zoom)
function onTouchStart(e) {
  if (!isImageLoaded.value) return;
  if (e.touches.length === 1) {
    isDragging.value = true;
    startDragX = e.touches[0].clientX - panX.value;
    startDragY = e.touches[0].clientY - panY.value;
  } else if (e.touches.length === 2) {
    isDragging.value = false;
    initialPinchDistance = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    );
    initialPinchScale = scale.value;
  }
}

function onTouchMove(e) {
  if (!isImageLoaded.value) return;
  if (e.touches.length === 1 && isDragging.value) {
    panX.value = e.touches[0].clientX - startDragX;
    panY.value = e.touches[0].clientY - startDragY;
    renderCanvas();
  } else if (e.touches.length === 2 && initialPinchDistance) {
    const currentDist = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    );
    const newScale = initialPinchScale * (currentDist / initialPinchDistance);
    scale.value = Math.max(0.05, Math.min(newScale, 3.5));
    renderCanvas();
  }
}

function onTouchEnd() {
  isDragging.value = false;
  initialPinchDistance = null;
}

// -----------------------------------------------------------------------------
// DEDICATED CONTROL BUTTONS & PRESETS
// -----------------------------------------------------------------------------
function adjustZoom(delta) {
  let step = delta;
  // Adaptive step: smaller 5% step when zoomed way down for fine precision
  if (delta < 0 && scale.value <= 0.25) {
    step = -0.05;
  } else if (delta > 0 && scale.value < 0.20) {
    step = 0.05;
  }
  const newScale = Math.round((scale.value + step) * 100) / 100;
  scale.value = Math.max(0.05, Math.min(newScale, 3.5));
  renderCanvas();
}

function setExactScale(targetScale) {
  scale.value = Math.max(0.05, Math.min(Math.round(targetScale * 100) / 100, 3.5));
  renderCanvas();
}

function fitToCropArea() {
  if (!imageObj.value) return;
  const minDim = Math.min(imageObj.value.width, imageObj.value.height);
  const fitScale = (CROP_RADIUS * 2) / minDim;
  scale.value = Math.max(0.05, Math.min(Math.round(fitScale * 100) / 100, 3.5));
  panX.value = 0;
  panY.value = 0;
  renderCanvas();
}

function onZoomSliderInput() {
  const val = parseFloat(scale.value);
  scale.value = Math.max(0.05, Math.min(isNaN(val) ? 0.05 : val, 3.5));
  renderCanvas();
}

function movePan(dx, dy) {
  panX.value += dx;
  panY.value += dy;
  renderCanvas();
}

function rotateImage(deg) {
  rotation.value = (rotation.value + deg + 360) % 360;
  renderCanvas();
}

// -----------------------------------------------------------------------------
// CROP RESULT EXTRACTION
// -----------------------------------------------------------------------------
function generateCroppedDataUrl(outputSize = 360) {
  if (!imageObj.value || !isImageLoaded.value) return null;

  const exportCanvas = document.createElement('canvas');
  exportCanvas.width = outputSize;
  exportCanvas.height = outputSize;
  const eCtx = exportCanvas.getContext('2d');
  if (!eCtx) return null;

  // Ratio from crop canvas viewport to exportCanvas
  const cropDiameter = CROP_RADIUS * 2;
  const ratio = outputSize / cropDiameter;

  // Fill background with white so empty space around a zoomed-out photo is clean white instead of JPEG pitch-black
  eCtx.fillStyle = '#ffffff';
  eCtx.fillRect(0, 0, outputSize, outputSize);

  eCtx.save();
  // Move center of export canvas to (outputSize/2, outputSize/2)
  eCtx.translate(outputSize / 2, outputSize / 2);
  // Apply user transformations scaled by ratio
  eCtx.translate(panX.value * ratio, panY.value * ratio);
  eCtx.rotate((rotation.value * Math.PI) / 180);
  eCtx.scale(scale.value * ratio, scale.value * ratio);

  const iw = imageObj.value.width;
  const ih = imageObj.value.height;
  eCtx.drawImage(imageObj.value, -iw / 2, -ih / 2, iw, ih);
  eCtx.restore();

  return exportCanvas.toDataURL('image/jpeg', 0.92);
}

function handleSave() {
  if (!isImageLoaded.value) {
    alert('Silakan pilih atau unggah foto terlebih dahulu.');
    return;
  }
  const resultDataUrl = generateCroppedDataUrl(400);
  emit('save', {
    dataUrl: resultDataUrl,
    scale: scale.value,
    panX: panX.value,
    panY: panY.value,
  });
}

function handleDelete() {
  if (confirm('Apakah Anda yakin ingin menghapus foto profil dan kembali menggunakan inisial bawaan?')) {
    emit('delete');
  }
}

// Initials Fallback
const userInitials = computed(() => {
  if (!props.userName) return 'U';
  const parts = props.userName.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return props.userName.substring(0, 2).toUpperCase();
});
</script>

<template>
  <Teleport to="body">
    <Transition name="cropper-fade">
      <div v-if="show" class="cropper-modal-overlay" @click.self="$emit('close')">
        <div class="cropper-modal-dialog">
          <!-- Modal Header -->
          <div class="cropper-modal-header">
            <div class="d-flex align-items-center gap-2">
              <div class="avatar-xs rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                <i class="ri-camera-lens-line fs-16"></i>
              </div>
              <div>
                <h5 class="modal-title fs-15 fw-bold mb-0 text-dark">{{ title }}</h5>
                <small class="text-muted fs-11">Perbesar/perkecil dan geser posisi foto sesuai keinginan</small>
              </div>
            </div>
            <button 
              type="button" 
              class="btn-close" 
              @click="$emit('close')"
              aria-label="Tutup"
            ></button>
          </div>

          <!-- Modal Body -->
          <div class="cropper-modal-body">
            <!-- Hidden File Input -->
            <input 
              ref="fileInputRef" 
              type="file" 
              class="d-none" 
              accept="image/png, image/jpeg, image/jpg, image/webp" 
              @change="onFileChange"
            />

            <!-- Empty State / Dropzone when no image loaded -->
            <div 
              v-if="!isImageLoaded" 
              class="cropper-dropzone"
              :class="{ 'is-dragover': isDragOver }"
              @dragover.prevent="isDragOver = true"
              @dragleave.prevent="isDragOver = false"
              @drop.prevent="onDrop"
              @click="triggerFileInput"
            >
              <div class="avatar-lg rounded-circle bg-light border border-2 border-dashed d-flex align-items-center justify-content-center mx-auto mb-3 text-primary shadow-sm">
                <i class="ri-upload-cloud-2-line fs-32"></i>
              </div>
              <h6 class="fw-bold mb-1 text-dark fs-14">Klik atau Seret Berkas Foto ke Sini</h6>
              <p class="text-muted fs-12 mb-3">Mendukung format JPG, PNG, atau WEBP (Maksimal 5 MB)</p>
              <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm">
                <i class="ri-folder-open-line me-1"></i> Pilih Foto dari Komputer
              </button>
            </div>

            <!-- Workspace when image loaded -->
            <div v-else class="cropper-workspace row g-3">
              <!-- Left/Main: Canvas Viewport -->
              <div class="col-lg-7 d-flex flex-column align-items-center">
                <div class="canvas-container shadow-sm position-relative">
                  <canvas
                    ref="canvasRef"
                    :width="CANVAS_SIZE"
                    :height="CANVAS_SIZE"
                    class="cropper-canvas"
                    :class="{ 'is-grabbing': isDragging }"
                    @mousedown="onMouseDown"
                    @mousemove="onMouseMove"
                    @mouseup="onMouseUp"
                    @mouseleave="onMouseUp"
                    @wheel="onWheel"
                    @touchstart="onTouchStart"
                    @touchmove="onTouchMove"
                    @touchend="onTouchEnd"
                  ></canvas>

                  <!-- Hint Overlay -->
                  <div class="canvas-hint-badge">
                    <i class="ri-drag-move-2-line me-1"></i> Klik & seret untuk menggeser • Scroll untuk zoom
                  </div>
                </div>

                <!-- Bottom Quick Actions for Canvas -->
                <div class="d-flex align-items-center justify-content-between w-100 mt-2 px-1 gap-2 flex-wrap">
                  <button 
                    type="button" 
                    class="btn btn-xs btn-outline-secondary d-inline-flex align-items-center gap-1"
                    @click="triggerFileInput"
                    title="Pilih gambar berbeda"
                  >
                    <i class="ri-folder-open-line"></i> Ganti Foto
                  </button>
                  <div class="d-flex align-items-center gap-1">
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-secondary" 
                      @click="rotateImage(-90)" 
                      title="Putar Kiri 90°"
                    >
                      <i class="ri-anticlockwise-2-line"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-secondary" 
                      @click="rotateImage(90)" 
                      title="Putar Kanan 90°"
                    >
                      <i class="ri-clockwise-2-line"></i>
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-xs btn-outline-secondary" 
                      @click="resetTransform" 
                      title="Reset Posisi & Zoom"
                    >
                      <i class="ri-refresh-line me-1"></i> Reset
                    </button>
                  </div>
                </div>
              </div>

              <!-- Right: Interactive Controls & Live Preview -->
              <div class="col-lg-5">
                <div class="controls-panel p-3 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                  <div>
                    <!-- 1. Perbesar / Perkecil (Zoom Controls) -->
                    <div class="mb-3">
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label fs-12 fw-bold text-dark mb-0">
                          <i class="ri-zoom-in-line text-primary me-1"></i> Perbesar / Perkecil (Zoom)
                        </label>
                        <span class="badge bg-primary-subtle text-primary font-mono fs-11">
                          {{ Math.round(scale * 100) }}%
                        </span>
                      </div>
                      <div class="d-flex align-items-center gap-2">
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-secondary p-1" 
                          style="width: 32px; height: 32px;"
                          @click="adjustZoom(-0.1)"
                          title="Perkecil (Zoom Out)"
                          :disabled="scale <= 0.05"
                        >
                          <i class="ri-subtract-line fs-14"></i>
                        </button>
                        <input 
                          type="range" 
                          class="form-range flex-grow-1" 
                          min="0.05" 
                          max="3.5" 
                          step="0.01" 
                          v-model="scale" 
                          @input="onZoomSliderInput"
                        />
                        <button 
                          type="button" 
                          class="btn btn-sm btn-outline-secondary p-1" 
                          style="width: 32px; height: 32px;"
                          @click="adjustZoom(0.1)"
                          title="Perbesar (Zoom In)"
                          :disabled="scale >= 3.5"
                        >
                          <i class="ri-add-line fs-14"></i>
                        </button>
                      </div>
                      <!-- Quick Scale Presets (5%, 50%, 100%, Fit) -->
                      <div class="d-flex align-items-center justify-content-between mt-1 px-1">
                        <span class="fs-10 text-muted">Min 5%</span>
                        <div class="d-flex align-items-center gap-1">
                          <button 
                            type="button" 
                            class="btn btn-xs py-0 px-1.5 fs-10 rounded border-0" 
                            :class="Math.round(scale * 100) === 5 ? 'bg-primary text-white' : 'bg-secondary-subtle text-muted'"
                            @click="setExactScale(0.05)"
                            title="Set Zoom ke 5%"
                          >
                            5%
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-xs py-0 px-1.5 fs-10 rounded border-0" 
                            :class="Math.round(scale * 100) === 50 ? 'bg-primary text-white' : 'bg-secondary-subtle text-muted'"
                            @click="setExactScale(0.5)"
                            title="Set Zoom ke 50%"
                          >
                            50%
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-xs py-0 px-1.5 fs-10 rounded border-0" 
                            :class="Math.round(scale * 100) === 100 ? 'bg-primary text-white' : 'bg-secondary-subtle text-muted'"
                            @click="setExactScale(1.0)"
                            title="Set Zoom ke 100%"
                          >
                            100%
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-xs py-0 px-1.5 fs-10 rounded border-0 bg-info-subtle text-info fw-semibold"
                            @click="fitToCropArea"
                            title="Sesuaikan ukuran pas dengan lingkaran (Fit)"
                          >
                            Fit
                          </button>
                        </div>
                        <span class="fs-10 text-muted">Maks 350%</span>
                      </div>
                    </div>

                    <!-- 2. Geser Atas / Bawah / Kiri / Kanan (Pan Directional Keypad) -->
                    <div class="mb-3">
                      <label class="form-label fs-12 fw-bold text-dark mb-1.5 d-block">
                        <i class="ri-cursor-line text-info me-1"></i> Geser Posisi Foto
                      </label>
                      <div class="pan-keypad-wrapper mx-auto">
                        <!-- Row 1: Up -->
                        <div class="text-center">
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary keypad-btn"
                            @click="movePan(0, -18)"
                            title="Geser ke Atas"
                          >
                            <i class="ri-arrow-up-s-line fs-16"></i>
                          </button>
                        </div>
                        <!-- Row 2: Left - Center - Right -->
                        <div class="d-flex justify-content-center align-items-center gap-1 my-1">
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary keypad-btn"
                            @click="movePan(-18, 0)"
                            title="Geser ke Kiri"
                          >
                            <i class="ri-arrow-left-s-line fs-16"></i>
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-primary keypad-btn keypad-center-btn"
                            @click="resetTransform"
                            title="Pusatkan Foto"
                          >
                            <i class="ri-focus-3-line fs-15"></i>
                          </button>
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary keypad-btn"
                            @click="movePan(18, 0)"
                            title="Geser ke Kanan"
                          >
                            <i class="ri-arrow-right-s-line fs-16"></i>
                          </button>
                        </div>
                        <!-- Row 3: Down -->
                        <div class="text-center">
                          <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary keypad-btn"
                            @click="movePan(0, 18)"
                            title="Geser ke Bawah"
                          >
                            <i class="ri-arrow-down-s-line fs-16"></i>
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- 3. Pratinjau Tampilan Lingkaran (Live Preview) -->
                    <div class="mb-2 pt-2 border-top">
                      <label class="form-label fs-11 text-muted text-uppercase fw-semibold mb-2 d-block">
                        Pratinjau Hasil Potongan
                      </label>
                      <div class="d-flex align-items-center justify-content-around p-2 bg-white rounded border">
                        <!-- Big Preview (80px) -->
                        <div class="text-center">
                          <div class="preview-avatar-wrap mb-1" style="width: 68px; height: 68px;">
                            <img 
                              v-if="livePreviewUrl" 
                              :src="livePreviewUrl" 
                              alt="Large Preview"
                              class="rounded-circle w-100 h-100 object-fit-cover shadow-sm border border-2 border-primary"
                            />
                            <div v-else class="rounded-circle w-100 h-100 bg-secondary-subtle text-muted d-flex align-items-center justify-content-center fw-bold">
                              {{ userInitials }}
                            </div>
                          </div>
                          <span class="fs-10 text-muted">Profil</span>
                        </div>

                        <!-- Medium Preview (42px, Topbar size) -->
                        <div class="text-center">
                          <div class="preview-avatar-wrap mb-1" style="width: 42px; height: 42px;">
                            <img 
                              v-if="livePreviewUrl" 
                              :src="livePreviewUrl" 
                              alt="Medium Preview"
                              class="rounded-circle w-100 h-100 object-fit-cover shadow-sm border"
                            />
                            <div v-else class="rounded-circle w-100 h-100 bg-secondary-subtle text-muted d-flex align-items-center justify-content-center fs-11 fw-bold">
                              {{ userInitials }}
                            </div>
                          </div>
                          <span class="fs-10 text-muted">Topbar</span>
                        </div>

                        <!-- Small Preview (28px, Table size) -->
                        <div class="text-center">
                          <div class="preview-avatar-wrap mb-1" style="width: 28px; height: 28px;">
                            <img 
                              v-if="livePreviewUrl" 
                              :src="livePreviewUrl" 
                              alt="Small Preview"
                              class="rounded-circle w-100 h-100 object-fit-cover shadow-sm border"
                            />
                            <div v-else class="rounded-circle w-100 h-100 bg-secondary-subtle text-muted d-flex align-items-center justify-content-center fs-9 fw-bold">
                              {{ userInitials }}
                            </div>
                          </div>
                          <span class="fs-10 text-muted">Tabel</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Optional Delete Button if photo already exists -->
                  <div v-if="currentAvatar" class="pt-2 border-top text-center mt-2">
                    <button 
                      type="button" 
                      class="btn btn-xs btn-link text-danger text-decoration-none p-0 d-inline-flex align-items-center gap-1"
                      @click="handleDelete"
                      :disabled="isProcessing"
                    >
                      <i class="ri-delete-bin-line"></i> Hapus Foto (Gunakan Inisial Nama)
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="cropper-modal-footer">
            <button 
              type="button" 
              class="btn btn-sm btn-secondary" 
              @click="$emit('close')"
              :disabled="isProcessing"
            >
              Batal
            </button>
            <button 
              type="button" 
              class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 px-3 shadow-sm"
              @click="handleSave"
              :disabled="!isImageLoaded || isProcessing"
            >
              <span v-if="isProcessing" class="spinner-border spinner-border-sm me-1" role="status"></span>
              <i v-else class="ri-check-line fs-14"></i>
              <span>{{ isProcessing ? 'Menyimpan...' : 'Simpan & Terapkan Foto' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.cropper-modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.78);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1060;
  padding: 16px;
}

.cropper-modal-dialog {
  background-color: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 720px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  max-height: 90vh;
}

.cropper-modal-header {
  padding: 14px 20px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cropper-modal-body {
  padding: 18px 20px;
  overflow-y: auto;
}

.cropper-modal-footer {
  padding: 12px 20px;
  border-top: 1px solid #e2e8f0;
  background-color: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

/* Dropzone styling */
.cropper-dropzone {
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  padding: 48px 24px;
  text-align: center;
  background-color: #f8fafc;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cropper-dropzone:hover,
.cropper-dropzone.is-dragover {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

/* Canvas styling */
.canvas-container {
  width: 100%;
  max-width: 360px;
  aspect-ratio: 1 / 1;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #334155;
  background-color: #0f172a;
  display: flex;
  align-items: center;
  justify-content: center;
}

.cropper-canvas {
  width: 100%;
  height: 100%;
  display: block;
  cursor: grab;
  touch-action: none;
}

.cropper-canvas.is-grabbing {
  cursor: grabbing;
}

.canvas-hint-badge {
  position: absolute;
  bottom: 8px;
  left: 50%;
  transform: translateX(-50%);
  background-color: rgba(15, 23, 42, 0.85);
  color: #f1f5f9;
  font-size: 10px;
  padding: 4px 10px;
  border-radius: 20px;
  pointer-events: none;
  white-space: nowrap;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

/* Pan Keypad */
.pan-keypad-wrapper {
  width: 120px;
}

.keypad-btn {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.keypad-center-btn {
  background-color: #eff6ff;
  border-color: #bfdbfe;
  color: #2563eb;
}

.preview-avatar-wrap {
  display: inline-block;
  overflow: hidden;
}

/* Transitions */
.cropper-fade-enter-active,
.cropper-fade-leave-active {
  transition: opacity 0.25s ease;
}

.cropper-fade-enter-from,
.cropper-fade-leave-to {
  opacity: 0;
}

/* Dark Mode Styles */
[data-bs-theme="dark"] .cropper-modal-dialog {
  background-color: #1e293b;
  color: #f8fafc;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
}

[data-bs-theme="dark"] .cropper-modal-header {
  border-bottom-color: #334155;
}

[data-bs-theme="dark"] .cropper-modal-header .modal-title {
  color: #f8fafc !important;
}

[data-bs-theme="dark"] .cropper-modal-footer {
  background-color: #0f172a;
  border-top-color: #334155;
}

[data-bs-theme="dark"] .cropper-dropzone {
  background-color: #0f172a;
  border-color: #334155;
  color: #cbd5e1;
}

[data-bs-theme="dark"] .controls-panel {
  background-color: #0f172a !important;
  border-color: #334155 !important;
}

[data-bs-theme="dark"] .controls-panel label {
  color: #f1f5f9 !important;
}

[data-bs-theme="dark"] .controls-panel .btn.bg-secondary-subtle {
  background-color: #334155 !important;
  color: #cbd5e1 !important;
}

[data-bs-theme="dark"] .preview-avatar-wrap {
  background-color: #1e293b;
}

[data-bs-theme="dark"] .cropper-modal-header .btn-close {
  filter: invert(1) grayscale(100%) brightness(200%);
}
</style>
