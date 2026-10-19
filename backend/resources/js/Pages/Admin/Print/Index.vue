<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  participants: Array,
  competitions: Array,
});

const selectedComp = ref('all');

const filteredParticipants = computed(() => {
  if (selectedComp.value === 'all') return props.participants;
  return props.participants.filter(p => p.competition_id === selectedComp.value);
});

function handlePrint() {
  window.print();
}
</script>

<template>
  <AppLayout>
    <Head title="Print Studio ID Card | Semarak Aliyah XIV" />

    <!-- Print Studio Controls (Hidden on Print) -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl print:hidden">
      <div>
        <h1 class="text-xl lg:text-2xl font-black text-white flex items-center gap-2">
          <i class="ri-printer-fill text-purple-400 text-xl"></i>
          <span>Print Studio ID Card Delegasi</span>
        </h1>
        <p class="text-xs text-slate-400 mt-0.5">
          Cetak kartu tanda pengenal peserta bertanda barcode untuk dibawa di pintu gate check-in hari-H.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Filter Lomba -->
        <select 
          v-model="selectedComp"
          class="px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-purple-500"
        >
          <option value="all">Semua Cabang Lomba</option>
          <option v-for="c in competitions" :key="c.id" :value="c.id">
            {{ c.icon }} {{ c.title }}
          </option>
        </select>

        <button 
          @click="handlePrint"
          class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-purple-600/20 transition-all"
        >
          <i class="ri-printer-line text-sm"></i>
          <span>Cetak Sekarang (Ctrl + P)</span>
        </button>
      </div>
    </div>

    <!-- Printable Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 print:grid-cols-2 print:gap-3">
      <div 
        v-for="item in filteredParticipants" 
        :key="item.id"
        class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between text-center relative overflow-hidden shadow-lg print:bg-white print:text-black print:border-2 print:border-slate-300 print:shadow-none print:break-inside-avoid"
      >
        <!-- Top Badge -->
        <div>
          <div class="flex items-center justify-between mb-3 text-[10px] font-extrabold uppercase tracking-widest text-amber-400 print:text-amber-600">
            <span>SEMARAK ALIYAH XIV</span>
            <span>2026</span>
          </div>

          <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-800 p-0.5 flex items-center justify-center font-black text-2xl text-amber-400 border border-slate-700 print:bg-slate-100 print:border-slate-300">
            {{ item.competition_title?.charAt(0) || 'S' }}
          </div>

          <h3 class="text-sm font-black text-white print:text-black mb-1 line-clamp-2">
            {{ item.school_name }}
          </h3>
          <div class="text-xs text-amber-400 print:text-slate-700 font-bold mb-3">
            {{ item.competition_title }}
          </div>

          <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800/80 print:bg-slate-50 print:border-slate-200 text-left text-xs mb-3">
            <div class="text-[10px] text-slate-500 uppercase font-semibold">Peserta:</div>
            <div class="text-slate-300 print:text-slate-800 font-medium line-clamp-3">{{ item.participants_names }}</div>
          </div>
        </div>

        <!-- Barcode Block -->
        <div class="pt-3 border-t border-slate-800 print:border-slate-300">
          <div class="font-mono text-xs font-bold text-amber-400 print:text-black tracking-wider mb-1">
            {{ item.reg_code }}
          </div>
          <!-- Barcode Visualization Lines -->
          <div class="flex justify-center items-center gap-0.5 h-8 bg-white p-1 rounded">
            <span v-for="n in 36" :key="n" :class="['h-full bg-black', n % 3 === 0 ? 'w-1' : 'w-0.5']"></span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
