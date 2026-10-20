<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: true,
});

function handleLogin() {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-4 font-sans selection:bg-amber-500 selection:text-black">
    <Head title="Masuk Akun Panitia | Semarak Aliyah XIV 2026" />

    <!-- Container Card -->
    <div class="w-full max-w-md p-8 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-2xl relative overflow-hidden backdrop-blur-xl">
      <!-- Glow effect -->
      <div class="absolute -top-24 -right-24 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Brand Header -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-amber-500 via-amber-400 to-orange-500 p-0.5 shadow-xl shadow-amber-500/20">
          <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center font-black text-2xl text-amber-400">
            XIV
          </div>
        </div>
        <h1 class="text-xl font-black text-white tracking-wider">SEMARAK ALIYAH XIV</h1>
        <p class="text-xs text-amber-400 font-semibold tracking-widest uppercase mt-0.5">
          MA Unggulan Wahid Hasyim Balung
        </p>
      </div>

      <!-- Login Form -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <!-- Error Alert -->
        <div v-if="form.errors.email || form.errors.password" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
          {{ form.errors.email || form.errors.password }}
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Email Panitia:</label>
          <div class="relative">
            <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
            <input 
              type="email" 
              v-model="form.email"
              required
              placeholder="admin@maswahas.sch.id"
              class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-amber-400"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Kata Sandi:</label>
          <div class="relative">
            <i class="ri-lock-line absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
            <input 
              :type="showPassword ? 'text' : 'password'" 
              v-model="form.password"
              required
              class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-amber-400"
            />
            <button 
              type="button" 
              @click="showPassword = !showPassword"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300"
            >
              <i :class="showPassword ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between text-xs text-slate-400 pt-1">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.remember" class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-0">
            <span>Ingat Sesi Saya</span>
          </label>
        </div>

        <button 
          type="submit" 
          :disabled="form.processing"
          class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center justify-center gap-1.5 mt-2"
        >
          <i class="ri-login-box-line text-sm"></i>
          <span>{{ form.processing ? 'Memverifikasi...' : 'Masuk Panel Panitia' }}</span>
        </button>
      </form>

      <!-- Footer Back Link -->
      <div class="text-center mt-6 pt-4 border-t border-slate-800/80 text-xs text-slate-500">
        <a href="/" class="hover:text-slate-300 flex items-center justify-center gap-1">
          <i class="ri-arrow-left-line"></i> Kembali ke Halaman Publik
        </a>
      </div>
    </div>
  </div>
</template>
