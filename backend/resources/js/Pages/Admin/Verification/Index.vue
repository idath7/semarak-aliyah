<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  registrations: Array,
  stats: Object,
});

const selectedItem = ref(props.registrations?.[0] || null);

const verifyForm = useForm({
  status: 'Terverifikasi',
  verification_notes: '',
});

function selectForReview(item) {
  selectedItem.value = item;
  verifyForm.status = item.payment_status;
  verifyForm.verification_notes = item.verification_notes || '';
}

function submitVerification() {
  if (!selectedItem.value) return;
  verifyForm.post(`/admin/verification/status/${selectedItem.value.id}`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <AppLayout>
    <Head title="Review & Verifikasi Berkas | Semarak Aliyah XIV" />

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl lg:text-2xl font-black text-white flex items-center gap-2">
          <i class="ri-file-text-fill text-blue-400 text-xl"></i>
          <span>Review & Verifikasi Berkas Pendaftaran</span>
        </h1>
        <p class="text-xs text-slate-400 mt-0.5">
          Pemeriksaan kelengkapan surat tugas, bukti transfer bank, dan keabsahan berkas kontingen sekolah.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <span class="px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-semibold">
          Menunggu Review: <strong class="text-amber-400 font-mono">{{ stats.pending_count || 0 }}</strong>
        </span>
      </div>
    </div>

    <!-- Split-Pane: List (Left) + Detail & Action (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- List Column (lg:col-span-7) -->
      <div class="lg:col-span-7">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl">
          <div class="divide-y divide-slate-800/60 max-h-[620px] overflow-y-auto">
            <div 
              v-for="item in registrations" 
              :key="item.id"
              @click="selectForReview(item)"
              :class="[
                'p-3.5 rounded-xl transition-all cursor-pointer mb-2 flex items-center justify-between',
                selectedItem?.id === item.id ? 'bg-blue-600/15 border border-blue-500/40 ring-1 ring-blue-500/30' : 'bg-slate-950/60 border border-slate-800/80 hover:bg-slate-850'
              ]"
            >
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="font-mono text-xs font-bold text-amber-400">{{ item.reg_code }}</span>
                  <span class="text-xs font-bold text-white">{{ item.school_name }}</span>
                </div>
                <div class="text-[11px] text-slate-400">
                  {{ item.competition_title }} • {{ item.mentor_name }}
                </div>
              </div>

              <div>
                <span 
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold',
                    item.payment_status === 'Terverifikasi' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30'
                  ]"
                >
                  {{ item.payment_status }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail & Verification Review Box (lg:col-span-5) -->
      <div class="lg:col-span-5">
        <div v-if="selectedItem" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
          <div class="pb-3 border-b border-slate-800">
            <div class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Detail Dokumen</div>
            <h2 class="text-base font-black text-white mt-0.5">{{ selectedItem.school_name }}</h2>
            <div class="text-xs text-slate-400 mt-0.5">{{ selectedItem.competition_title }}</div>
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex justify-between py-1 border-b border-slate-800/60">
              <span class="text-slate-500">Kode Registrasi:</span>
              <span class="font-mono font-bold text-amber-400">{{ selectedItem.reg_code }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800/60">
              <span class="text-slate-500">Guru Pendamping:</span>
              <span class="font-bold text-white">{{ selectedItem.mentor_name }} ({{ selectedItem.mentor_wa }})</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800/60">
              <span class="text-slate-500">Peserta Terdaftar:</span>
              <span class="text-slate-300 font-medium text-right max-w-[200px]">{{ selectedItem.participants_names }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800/60">
              <span class="text-slate-500">Total Biaya:</span>
              <span class="font-bold text-emerald-400 font-mono">Rp {{ Number(selectedItem.total_fee || 50000).toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <!-- Review Form -->
          <form @submit.prevent="submitVerification" class="pt-2 space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Keputusan Verifikasi:</label>
              <select 
                v-model="verifyForm.status"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
              >
                <option value="Terverifikasi">Terverifikasi (Lunas & Berkas Sah)</option>
                <option value="Menunggu Verifikasi">Menunggu Verifikasi Tambahan</option>
                <option value="Ditolak">Ditolak / Berkas Tidak Sesuai</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Catatan Verifikator:</label>
              <textarea 
                v-model="verifyForm.verification_notes"
                rows="3"
                placeholder="Tuliskan catatan verifikasi bukti pembayaran atau kekurangan berkas..."
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-blue-500"
              ></textarea>
            </div>

            <button 
              type="submit" 
              :disabled="verifyForm.processing"
              class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/20 transition-all flex items-center justify-center gap-1.5"
            >
              <i class="ri-check-double-line text-sm"></i>
              <span>{{ verifyForm.processing ? 'Menyimpan...' : 'Simpan Keputusan Verifikasi' }}</span>
            </button>
          </form>
        </div>

        <div v-else class="p-12 text-center text-xs text-slate-500 rounded-2xl bg-slate-900 border border-slate-800">
          Pilih salah satu delegasi di sebelah kiri untuk melakukan review berkas.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
