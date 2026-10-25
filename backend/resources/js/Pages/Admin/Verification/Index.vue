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
  registration_status: 'Terverifikasi',
  payment_status: 'Terverifikasi',
  status: 'Terverifikasi',
  verification_notes: '',
});

function selectForReview(item) {
  selectedItem.value = item;
  verifyForm.registration_status = item.registration_status || 'Menunggu Verifikasi';
  verifyForm.payment_status = item.payment_status || 'Menunggu Verifikasi';
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

              <div class="flex flex-col items-end gap-1">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded-full text-[9px] font-bold',
                    item.registration_status === 'Terverifikasi' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : (item.registration_status === 'Ditolak' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30')
                  ]"
                >
                  Reg: {{ item.registration_status || 'Menunggu' }}
                </span>
                <span 
                  :class="[
                    'px-2 py-0.5 rounded-full text-[9px] font-bold',
                    item.payment_status === 'Terverifikasi' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : (item.payment_status === 'Ditolak' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30')
                  ]"
                >
                  Bayar: {{ item.payment_status || 'Menunggu' }}
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
            <div class="text-xs text-slate-400 mt-0.5">{{ selectedItem.competition_title }} • <span class="text-amber-300 font-semibold">{{ selectedItem.package_type }}</span></div>
          </div>

          <!-- Paket All-In Notice if applicable -->
          <div v-if="selectedItem.package_type === 'Paket All-In'" class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs">
            <div class="font-bold text-amber-300 flex items-center gap-1.5 mb-1">
              <i class="ri-vip-crown-fill text-amber-400"></i>
              <span>Paket All-In Komplit</span>
            </div>
            <p class="text-[11px] text-slate-300 leading-relaxed mb-0">
              Jika <strong>Verifikasi Pendaftaran</strong> & <strong>Verifikasi Pembayaran</strong> keduanya disetujui, sekolah ini bebas mengisi kontingen di semua cabang lomba gratis (Rp 0).
            </p>
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
              <span class="text-slate-300 font-medium text-right max-w-[200px] whitespace-pre-line">{{ selectedItem.participants_names }}</span>
            </div>
            <div class="flex justify-between py-1 border-b border-slate-800/60">
              <span class="text-slate-500">Total Biaya:</span>
              <span class="font-bold text-emerald-400 font-mono">Rp {{ Number(selectedItem.total_fee || 0).toLocaleString('id-ID') }}</span>
            </div>
          </div>

          <!-- Review Form: 2 Verifications -->
          <form @submit.prevent="submitVerification" class="pt-2 space-y-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">1. Verifikasi Pendaftaran (Berkas & Data):</label>
              <CustomSelect 
                v-model="verifyForm.registration_status"
                size="md"
                variant="dark-slate"
              >
                <option value="Terverifikasi">Terverifikasi (Berkas Lengkap & Sah)</option>
                <option value="Menunggu Verifikasi">Menunggu Verifikasi Tambahan</option>
                <option value="Ditolak">Ditolak / Data Tidak Sesuai</option>
              </CustomSelect>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">2. Verifikasi Pembayaran (Bank / Lunas):</label>
              <CustomSelect 
                v-model="verifyForm.payment_status"
                size="md"
                variant="dark-slate"
              >
                <option value="Terverifikasi">Terverifikasi (Lunas / Transfer Diterima)</option>
                <option value="Menunggu Verifikasi">Menunggu Konfirmasi Transfer</option>
                <option value="Ditolak">Ditolak / Belum Ada Dana Masuk</option>
              </CustomSelect>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Catatan Verifikator:</label>
              <textarea 
                v-model="verifyForm.verification_notes"
                rows="2"
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
              <span>{{ verifyForm.processing ? 'Menyimpan...' : 'Simpan Keputusan 2 Verifikasi' }}</span>
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
