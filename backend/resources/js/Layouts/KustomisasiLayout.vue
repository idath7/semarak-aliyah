<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';

const props = defineProps({
  activeMenu: {
    type: String,
    default: 'umum', // 'umum' | 'ticker' | future features
  },
  pageTitle: {
    type: String,
    default: 'Kustomisasi Lanjutan Web Publik',
  },
  pageDescription: {
    type: String,
    default: 'Pusat pengaturan pengalaman visual, efek interaktif, teks berjalan marquee, serta fungsionalitas portal publik.',
  },
  pageBadge: {
    type: String,
    default: 'Efek & Interaksi Web',
  },
});

const page = usePage();
const kustomisasi = computed(() => page.props.kustomisasiWeb || {});
</script>

<template>
  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Top Page Header -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent pb-0">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-purple-subtle text-purple fs-11 px-2.5 py-1 rounded-pill fw-bold border border-purple-subtle">
                  <i class="ri-palette-line me-1"></i> Kelola Web Publik
                </span>
                <span class="text-muted fs-12">/</span>
                <Link href="/admin/kustomisasi" class="badge bg-light text-secondary fs-11 px-2.5 py-1 rounded-pill fw-semibold text-decoration-none">
                  Kustomisasi Lanjutan
                </Link>
                <span class="text-muted fs-12">/</span>
                <span class="badge bg-light text-dark fs-11 px-2.5 py-1 rounded-pill fw-semibold">
                  {{ pageBadge }}
                </span>
              </div>
              <h4 class="mb-sm-0 font-size-18 fw-bold">{{ pageTitle }}</h4>
              <p class="text-muted fs-13 mb-0">
                {{ pageDescription }}
              </p>
            </div>
            <div class="page-title-right d-flex align-items-center gap-2 mt-3 mt-sm-0">
              <a 
                href="/" 
                target="_blank" 
                class="btn btn-soft-info d-flex align-items-center gap-1 shadow-sm"
              >
                <i class="ri-external-link-line"></i> Lihat Web Publik
              </a>
              <!-- Slot for Page Specific Action Buttons (Save, Reset, dll) -->
              <slot name="actions"></slot>
            </div>
          </div>
        </div>
      </div>

      <!-- Kustomisasi Lanjutan Navigation Bar (Sub-Layout Hub) -->
      <div class="card border-0 shadow-sm mb-4 bg-white rounded-3">
        <div class="card-body p-2 p-md-3">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <!-- Left: Sub-Menu Navigation Items -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Sub-Menu 1: Efek & Interaksi Web -->
              <Link 
                href="/admin/kustomisasi" 
                class="kustomisasi-nav-btn d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                :class="activeMenu === 'umum' ? 'active-kustomisasi-nav' : 'text-muted'"
              >
                <div 
                  class="kustomisasi-icon-box rounded-2 d-flex align-items-center justify-content-center"
                  :class="activeMenu === 'umum' ? 'bg-primary text-white shadow-sm' : 'bg-light text-primary'"
                >
                  <i class="ri-magic-line fs-16"></i>
                </div>
                <div>
                  <div class="fw-bold fs-13 lh-sm" :class="activeMenu === 'umum' ? 'text-primary' : 'text-dark'">
                    Efek & Interaksi Web
                  </div>
                  <div class="fs-11 text-muted">
                    Banner, Pop-up, WA, Countdown, CSS/JS
                  </div>
                </div>
                <span 
                  class="badge rounded-pill ms-1 fs-10" 
                  :class="activeMenu === 'umum' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted'"
                >
                  6 Modul
                </span>
              </Link>

              <!-- Sub-Menu 2: Teks Berjalan (Marquee Ticker) -->
              <Link 
                href="/admin/kustomisasi/teks-berjalan" 
                class="kustomisasi-nav-btn d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                :class="activeMenu === 'ticker' ? 'active-kustomisasi-nav' : 'text-muted'"
              >
                <div 
                  class="kustomisasi-icon-box rounded-2 d-flex align-items-center justify-content-center"
                  :class="activeMenu === 'ticker' ? 'bg-success text-white shadow-sm' : 'bg-light text-success'"
                >
                  <i class="ri-text-wrap fs-16"></i>
                </div>
                <div>
                  <div class="fw-bold fs-13 lh-sm" :class="activeMenu === 'ticker' ? 'text-success' : 'text-dark'">
                    Teks Berjalan (Marquee)
                  </div>
                  <div class="fs-11 text-muted">
                    Frasa, Font Display, Kecepatan, Opasitas
                  </div>
                </div>
                <span 
                  class="badge rounded-pill ms-1 fs-10" 
                  :class="activeMenu === 'ticker' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted'"
                >
                  Tipografi
                </span>
              </Link>

              <!-- Sub-Menu 3: Seksi & Panduan TM -->
              <Link 
                href="/admin/tm?modal=panduan" 
                class="kustomisasi-nav-btn d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                :class="activeMenu === 'tm' ? 'active-kustomisasi-nav' : 'text-muted'"
              >
                <div 
                  class="kustomisasi-icon-box rounded-2 d-flex align-items-center justify-content-center"
                  :class="activeMenu === 'tm' ? 'bg-info text-white shadow-sm' : 'bg-light text-info'"
                >
                  <i class="ri-calendar-todo-line fs-16"></i>
                </div>
                <div>
                  <div class="fw-bold fs-13 lh-sm" :class="activeMenu === 'tm' ? 'text-info' : 'text-dark'">
                    Seksi & Panduan TM
                  </div>
                  <div class="fs-11 text-muted">
                    Header Jadwal, Banner 4 Poin, Tata Tertib
                  </div>
                </div>
                <span 
                  class="badge rounded-pill ms-1 fs-10" 
                  :class="activeMenu === 'tm' ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-light text-muted'"
                >
                  Landing
                </span>
              </Link>
            </div>

            <!-- Right: Quick Portal Sync Status -->
            <div class="d-none d-lg-flex align-items-center gap-3 pe-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fs-11">
                  <i class="ri-checkbox-circle-fill me-1"></i> Live Web Publik Sync
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Page Content Rendered Here -->
      <slot></slot>
    </div>
  </VelzonLayout>
</template>

<style scoped>
.kustomisasi-nav-btn {
  border: 1px solid #e9ebec;
  background: #ffffff;
  transition: all 0.2s ease-in-out;
}

.kustomisasi-nav-btn:hover {
  background: #f8f9fa;
  border-color: #ced4da;
  transform: translateY(-1px);
}

.active-kustomisasi-nav {
  background: #f0f7ff !important;
  border-color: #93c5fd !important;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.08);
}

.kustomisasi-icon-box {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
}

.text-purple {
  color: #7c3aed !important;
}

.bg-purple-subtle {
  background-color: rgba(124, 58, 237, 0.1) !important;
}

.border-purple-subtle {
  border-color: rgba(124, 58, 237, 0.25) !important;
}
</style>
