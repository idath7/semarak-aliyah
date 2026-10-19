<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { initEcho } from '@/echo';

const props = defineProps({
  competition: Object,
  participants: Array,
  rubrics: Object,
  scores: Array,
  competitionsList: Array,
});

// Stopwatch timer
const stopwatchSeconds = ref(0);
const stopwatchRunning = ref(false);
let stopwatchInterval = null;

const formattedStopwatch = computed(() => {
  const m = String(Math.floor(stopwatchSeconds.value / 60)).padStart(2, '0');
  const s = String(stopwatchSeconds.value % 60).padStart(2, '0');
  return `${m}:${s}`;
});

function toggleStopwatch() {
  if (stopwatchRunning.value) {
    clearInterval(stopwatchInterval);
    stopwatchRunning.value = false;
  } else {
    stopwatchRunning.value = true;
    stopwatchInterval = setInterval(() => {
      stopwatchSeconds.value++;
    }, 1000);
  }
}

function resetStopwatch() {
  clearInterval(stopwatchInterval);
  stopwatchRunning.value = false;
  stopwatchSeconds.value = 0;
}

// Jury Form
const form = useForm({
  competition_id: props.competition.id,
  registration_id: '',
  jury_name: 'Dewan Juri 1',
  criteria_scores: {},
  notes: '',
  performance_duration: '',
});

// Initialize rubric inputs
props.rubrics && Object.keys(props.rubrics).forEach(criterion => {
  form.criteria_scores[criterion] = 80;
});

// Live calculated total score based on weights
const calculatedTotal = computed(() => {
  if (!props.rubrics) return 0;
  let total = 0;
  for (const [criterion, weight] of Object.entries(props.rubrics)) {
    const val = Number(form.criteria_scores[criterion] || 0);
    total += (val * Number(weight)) / 100;
  }
  return total.toFixed(2);
});

function submitJuryScore() {
  form.performance_duration = formattedStopwatch.value;
  form.post('/admin/jury/score', {
    preserveScroll: true,
    onSuccess: () => {
      resetStopwatch();
      form.registration_id = '';
      form.notes = '';
    }
  });
}

function switchCompetition(event) {
  const compId = event.target.value;
  router.visit(`/admin/jury/arena/${compId}`);
}

onUnmounted(() => {
  if (stopwatchInterval) clearInterval(stopwatchInterval);
});
</script>

<template>
  <AppLayout>
    <Head :title="`Arena Penjurian Juri: ${competition.title}`" />

    <!-- Header & Competition Selector -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30">
            ARENA PENJURIAN DIGITAL
          </span>
          <span class="text-xs text-slate-500">Juknis Resmi Semarak Aliyah XIV</span>
        </div>
        <h1 class="text-xl lg:text-2xl font-black text-white flex items-center gap-2">
          <span>{{ competition.icon }}</span>
          <span>{{ competition.title }}</span>
        </h1>
        <p class="text-xs text-slate-400 mt-0.5">
          Penilaian kriteria berbobot persentase otomatis dengan integrasi stopwatch dan siaran nilai live ke panggung.
        </p>
      </div>

      <!-- Quick Competition Switcher -->
      <div class="flex items-center gap-2">
        <label class="text-xs text-slate-400 font-medium">Pindah Lomba:</label>
        <select 
          :value="competition.id" 
          @change="switchCompetition"
          class="px-3 py-2 rounded-xl bg-slate-950 border border-amber-500/50 text-xs text-amber-300 font-bold focus:outline-none"
        >
          <option v-for="c in competitionsList" :key="c.id" :value="c.id">
            {{ c.icon }} {{ c.title }}
          </option>
        </select>
      </div>
    </div>

    <!-- Main Scoring Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Scoring Sheet (lg:col-span-7) -->
      <div class="lg:col-span-7">
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
          <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-800">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
              <i class="ri-edit-line text-amber-400"></i>
              <span>Lembar Penilaian Dewan Juri</span>
            </h2>

            <!-- Stopwatch Timer Pill -->
            <div class="flex items-center gap-2 bg-slate-950 px-3 py-1.5 rounded-xl border border-slate-800 shadow-inner">
              <i class="ri-timer-line text-amber-400"></i>
              <span class="font-digital text-base font-bold text-amber-400">{{ formattedStopwatch }}</span>
              <button 
                type="button" 
                @click="toggleStopwatch"
                :class="[
                  'px-2 py-0.5 rounded text-[11px] font-bold transition-all',
                  stopwatchRunning ? 'bg-rose-500 text-white' : 'bg-emerald-600 text-white'
                ]"
              >
                {{ stopwatchRunning ? 'Pause' : 'Start' }}
              </button>
              <button 
                type="button" 
                @click="resetStopwatch"
                class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-800 text-slate-400 hover:text-white"
              >
                Reset
              </button>
            </div>
          </div>

          <form @submit.prevent="submitJuryScore" class="space-y-4">
            <!-- Select Participant -->
            <div>
              <label class="block text-xs font-bold text-slate-300 mb-1">Pilih Delegasi / Peserta Tampil:</label>
              <select 
                v-model="form.registration_id"
                required
                class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400"
              >
                <option value="">-- Pilih Delegasi Peserta --</option>
                <option v-for="p in participants" :key="p.id" :value="p.id">
                  {{ p.reg_code }} • {{ p.school_name }} ({{ p.participants_names }})
                </option>
              </select>
            </div>

            <!-- Jury Name -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Juri / Meja Penilai:</label>
              <input 
                type="text" 
                v-model="form.jury_name"
                required
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400"
              />
            </div>

            <!-- Dynamic Rubrics List -->
            <div class="space-y-3 pt-2">
              <div class="flex items-center justify-between text-xs text-amber-400 font-bold border-b border-slate-800 pb-1">
                <span>Kriteria Penilaian Resmi Juknis:</span>
                <span class="text-slate-500 font-normal">Skala 0 - 100</span>
              </div>

              <div 
                v-for="(weight, criterion) in rubrics" 
                :key="criterion"
                class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800/80 flex flex-col gap-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-semibold text-slate-200">{{ criterion }}</span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-400">
                    Bobot {{ weight }}%
                  </span>
                </div>
                <div class="flex items-center gap-3">
                  <input 
                    type="range" 
                    min="0" 
                    max="100" 
                    v-model.number="form.criteria_scores[criterion]"
                    class="flex-1 accent-amber-500"
                  />
                  <input 
                    type="number" 
                    min="0" 
                    max="100" 
                    v-model.number="form.criteria_scores[criterion]"
                    class="w-16 px-2 py-1 rounded bg-slate-900 border border-slate-700 text-center font-bold text-xs text-amber-400 focus:outline-none"
                  />
                </div>
              </div>
            </div>

            <!-- Final Score Calculated Card -->
            <div class="p-4 rounded-xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 flex items-center justify-between">
              <div>
                <span class="text-[10px] font-extrabold uppercase text-amber-400 tracking-wider">TOTAL NILAI TERKALKULASI</span>
                <div class="text-[11px] text-slate-400">Bobot persentase dihitung otomatis</div>
              </div>
              <div class="text-right">
                <span class="font-digital text-4xl font-black text-amber-400">{{ calculatedTotal }}</span>
                <span class="text-xs text-slate-500 ml-1">/ 100</span>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Catatan Dewan Juri (Opsional):</label>
              <textarea 
                v-model="form.notes"
                rows="2"
                placeholder="Catatan kelebihan, kekurangan, atau evaluasi dewan juri..."
                class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-amber-400"
              ></textarea>
            </div>

            <!-- Submit Button -->
            <button 
              type="submit" 
              :disabled="form.processing || !form.registration_id"
              class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center justify-center gap-1.5 disabled:opacity-50"
            >
              <i class="ri-lock-line text-sm"></i>
              <span>{{ form.processing ? 'Menyimpan...' : 'Simpan & Kunci Nilai Juri' }}</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Live Leaderboard & Scores Entered (lg:col-span-5) -->
      <div class="lg:col-span-5">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
          <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-800">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
              <i class="ri-trophy-line text-amber-400"></i>
              <span>Rekap Nilai Masuk Cabang Ini</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">{{ scores.length }} Nilai</span>
          </div>

          <div class="divide-y divide-slate-800/60 max-h-[550px] overflow-y-auto">
            <div 
              v-for="(item, idx) in scores" 
              :key="item.id"
              class="py-3 px-2 flex items-center justify-between hover:bg-slate-800/40 rounded-lg transition-colors"
            >
              <div class="flex items-center gap-3">
                <span 
                  :class="[
                    'w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs',
                    idx === 0 ? 'bg-amber-400 text-slate-950 font-black' :
                    idx === 1 ? 'bg-slate-300 text-slate-950 font-bold' :
                    idx === 2 ? 'bg-amber-700 text-white font-bold' :
                    'bg-slate-800 text-slate-400'
                  ]"
                >
                  {{ idx + 1 }}
                </span>
                <div>
                  <div class="text-xs font-bold text-white">{{ item.registration?.school_name || 'Delegasi' }}</div>
                  <div class="text-[11px] text-slate-400">
                    {{ item.jury_name }} • Durasi: {{ item.performance_duration || '-' }}
                  </div>
                </div>
              </div>

              <div class="text-right">
                <span class="font-digital text-xl font-bold text-amber-400">{{ Number(item.total_score).toFixed(2) }}</span>
              </div>
            </div>

            <div v-if="scores.length === 0" class="py-12 text-center text-slate-500 text-xs">
              Belum ada nilai yang dimasukkan oleh dewan juri untuk cabang lomba ini.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
