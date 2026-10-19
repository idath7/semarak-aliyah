<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { initEcho } from '@/echo';

const props = defineProps({
  stats: Object,
  recentCheckins: Array,
});

const barcodeInput = ref('');
const inputRef = ref(null);
const lastScanned = ref(null);
const scanStatus = ref(null); // 'success' | 'error' | 'already'
const statusMessage = ref('');
const checkinsList = ref(props.recentCheckins || []);

// Sound chime using Web Audio API (No external file needed)
function playBeep(success = true) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    
    if (success) {
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime); // A5
      osc.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.08); // D6
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.25);
    } else {
      osc.type = 'sawtooth';
      osc.frequency.setValueAtTime(220, ctx.currentTime);
      gain.gain.setValueAtTime(0.3, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.3);
    }
  } catch(e) {}
}

const form = useForm({
  code: '',
});

function handleScan() {
  const code = barcodeInput.value.trim();
  if (!code) return;

  form.code = code;
  form.post('/admin/gate-checkin/scan', {
    preserveScroll: true,
    onSuccess: (page) => {
      barcodeInput.value = '';
      playBeep(true);
      // Auto refocus
      nextTick(() => inputRef.value?.focus());
    },
    onError: (errors) => {
      playBeep(false);
      barcodeInput.value = '';
      nextTick(() => inputRef.value?.focus());
    }
  });
}

function handleUndo(id) {
  router.post(`/admin/gate-checkin/undo/${id}`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      playBeep(false);
    }
  });
}

onMounted(() => {
  inputRef.value?.focus();

  // Listen to live gate scans from Reverb
  try {
    const echo = initEcho();
    if (echo) {
      echo.channel('gate.checkin')
        .listen('.gate.logged', (data) => {
          if (data?.registration) {
            if (data.action === 'checkin') {
              checkinsList.value.unshift(data.registration);
              lastScanned.value = data.registration;
            } else if (data.action === 'undo') {
              checkinsList.value = checkinsList.value.filter(r => r.id !== data.registration.id);
            }
          }
        });
    }
  } catch(e) {}
});
</script>

<template>
  <AppLayout>
    <Head title="Gate Check-in Barcode Live | Semarak Aliyah XIV" />

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block animate-ping mr-1"></span>
            LIVE SCANNER ACTIVE
          </span>
          <span class="text-xs text-slate-500">USB HID Scanner Ready</span>
        </div>
        <h1 class="text-xl lg:text-2xl font-black text-white flex items-center gap-2">
          <span>Gate Check-in & Presensi Barcode</span>
        </h1>
        <p class="text-xs text-slate-400 mt-0.5">
          Arahkan barcode scanner pada kartu ID Card atau formulir pendaftaran peserta untuk presensi kedatangan hari-H.
        </p>
      </div>
    </div>

    <!-- Attendance Metric Summary -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-md">
        <span class="text-xs text-slate-400">Total Delegasi</span>
        <div class="text-2xl font-black text-white font-mono mt-1">{{ stats.total_delegates || 0 }}</div>
      </div>
      <div class="p-4 rounded-2xl bg-slate-900 border border-emerald-500/30 bg-emerald-500/5 shadow-md">
        <span class="text-xs text-emerald-400 font-bold">Hadir di Lokasi</span>
        <div class="text-2xl font-black text-emerald-400 font-mono mt-1">{{ stats.present_count || 0 }}</div>
      </div>
      <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 shadow-md">
        <span class="text-xs text-slate-400">Belum Tiba</span>
        <div class="text-2xl font-black text-amber-400 font-mono mt-1">{{ stats.absent_count || 0 }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Scanner Input Box (lg:col-span-5) -->
      <div class="lg:col-span-5 space-y-4">
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
              <i class="ri-qr-scan-2-line text-lg"></i>
            </div>
            <h2 class="text-sm font-bold text-white">Input Scan Barcode</h2>
          </div>

          <form @submit.prevent="handleScan">
            <div class="relative mb-3">
              <input 
                ref="inputRef"
                type="text" 
                v-model="barcodeInput"
                placeholder="Scan barcode di sini (tekan enter)..."
                class="w-full px-4 py-3.5 rounded-xl bg-slate-950 border-2 border-emerald-500/50 text-base font-mono font-bold text-white placeholder-slate-600 focus:outline-none focus:border-emerald-400 shadow-inner"
                autocomplete="off"
                autofocus
              />
            </div>
            <button 
              type="submit" 
              :disabled="form.processing"
              class="w-full py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-1.5"
            >
              <i class="ri-check-line text-sm"></i>
              <span>{{ form.processing ? 'Memproses...' : 'Presensikan Delegasi' }}</span>
            </button>
          </form>

          <p class="text-[11px] text-slate-500 text-center mt-3">
            Scanner USB otomatis mengirim tombol Enter setelah membaca barcode.
          </p>
        </div>

        <!-- Latest Scanned Delegation Card Popup -->
        <div v-if="lastScanned" class="p-5 rounded-2xl bg-emerald-950/30 border border-emerald-500/40 shadow-xl">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-extrabold text-emerald-400 tracking-wider uppercase">
              BARU SAJA CHECK-IN
            </span>
            <span class="font-mono text-xs text-slate-400">{{ lastScanned.reg_code }}</span>
          </div>
          <h3 class="text-base font-black text-white mb-1">{{ lastScanned.school_name }}</h3>
          <div class="text-xs text-amber-300 font-semibold mb-2">{{ lastScanned.competition_title }}</div>
          <div class="text-xs text-slate-400 mb-4 bg-slate-950/60 p-2.5 rounded-lg border border-slate-800">
            <strong class="text-slate-300 block mb-0.5">Pendamping:</strong>
            {{ lastScanned.mentor_name }} ({{ lastScanned.mentor_wa }})
          </div>

          <button 
            type="button" 
            @click="handleUndo(lastScanned.id)"
            class="text-xs text-rose-400 hover:underline flex items-center gap-1"
          >
            <i class="ri-arrow-go-back-line"></i> Batalkan Check-in
          </button>
        </div>
      </div>

      <!-- Recent Check-ins Stream (lg:col-span-7) -->
      <div class="lg:col-span-7">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
          <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
              <i class="ri-history-line text-slate-400"></i>
              <span>Riwayat Presensi Masuk Pintu Hari-H</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">{{ checkinsList.length }} Delegasi</span>
          </div>

          <div class="divide-y divide-slate-800/60 max-h-[500px] overflow-y-auto">
            <div 
              v-for="item in checkinsList" 
              :key="item.id"
              class="py-3 px-2 flex items-center justify-between gap-3 hover:bg-slate-800/40 transition-colors rounded-lg"
            >
              <div>
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="font-mono text-xs font-bold text-amber-400">{{ item.reg_code }}</span>
                  <span class="text-xs font-bold text-white">{{ item.school_name }}</span>
                </div>
                <div class="text-[11px] text-slate-400">
                  {{ item.competition_title }} • {{ item.mentor_name }}
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300">
                  HADIR
                </span>
                <button 
                  @click="handleUndo(item.id)" 
                  title="Batalkan presensi"
                  class="text-slate-500 hover:text-rose-400 p-1"
                >
                  <i class="ri-delete-bin-line text-sm"></i>
                </button>
              </div>
            </div>

            <div v-if="checkinsList.length === 0" class="py-12 text-center text-slate-500 text-xs">
              Belum ada delegasi yang melakukan scan check-in di gerbang.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
