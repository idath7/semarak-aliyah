<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  competitions: Array,
  juryCounts: Object,
});
</script>

<template>
  <AppLayout>
    <Head title="Hub Penjurian Digital Juri | Semarak Aliyah XIV" />

    <div class="mb-6">
      <div class="flex items-center gap-2 mb-1">
        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30">
          DEWAN JURI TERPADU
        </span>
      </div>
      <h1 class="text-xl lg:text-2xl font-black text-white flex items-center gap-2">
        <i class="ri-scales-3-fill text-amber-400"></i>
        <span>Hub Arena Penjurian Digital</span>
      </h1>
      <p class="text-xs text-slate-400 mt-0.5">
        Pilih cabang lomba untuk membuka lembar penilaian wasit/juri, rubrik berbobot, dan integrasi stopwatch resmi.
      </p>
    </div>

    <!-- Competitions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div 
        v-for="comp in competitions" 
        :key="comp.id"
        class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-amber-500/40 transition-all flex flex-col justify-between shadow-lg group"
      >
        <div>
          <div class="flex items-center justify-between mb-3">
            <span class="text-3xl group-hover:scale-110 transition-transform">{{ comp.icon }}</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-400">
              {{ comp.category }}
            </span>
          </div>

          <h3 class="text-sm font-bold text-white mb-1.5 group-hover:text-amber-400 transition-colors">
            {{ comp.title }}
          </h3>
          <p class="text-xs text-slate-400 line-clamp-2 mb-4">
            {{ comp.description }}
          </p>
        </div>

        <div>
          <div class="flex items-center justify-between text-xs text-slate-400 py-2 border-t border-slate-800/80 mb-3">
            <span>Nilai Tersimpan:</span>
            <span class="font-bold text-amber-400 font-mono">{{ juryCounts?.[comp.id] || 0 }} Nilai</span>
          </div>

          <Link 
            :href="`/admin/jury/arena/${comp.id}`"
            class="w-full py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-amber-500/10 transition-all"
          >
            <span>Masuk Meja Juri</span>
            <i class="ri-arrow-right-line"></i>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
