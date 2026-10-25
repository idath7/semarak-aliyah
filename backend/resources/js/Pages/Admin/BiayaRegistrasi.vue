<template>
  <Head title="Kelola Biaya Registrasi & Paket - Admin Panel" />

  <VelzonLayout>
    <div class="container-fluid">
      <!-- Breadcrumb & Header -->
      <div class="row">
        <div class="col-12">
          <div class="page-title-box d-sm-flex align-items-center justify-content-between bg-galaxy-transparent">
            <div>
              <h4 class="mb-sm-0 font-size-18 fw-bold">Kelola Biaya Registrasi & Paket Pembayaran</h4>
              <p class="text-muted fs-13 mb-0">Atur tarif biaya pendaftaran cabang lomba, konfigurasi paket All-In hemat, rekening bank tujuan, dan petunjuk transfer.</p>
            </div>
            <div class="page-title-right d-flex align-items-center gap-2 mt-3 mt-sm-0">
              <a 
                href="/#kompetisi" 
                target="_blank" 
                class="btn btn-soft-info d-flex align-items-center gap-1 shadow-sm"
              >
                <i class="ri-external-link-line"></i> Lihat di Web
              </a>
              <button 
                type="button" 
                class="btn btn-outline-primary d-flex align-items-center gap-1 shadow-sm"
                @click="openCalculatorModal"
              >
                <i class="ri-calculator-line"></i> Simulasi Kalkulator
              </button>
              <button 
                type="button" 
                class="btn btn-primary d-flex align-items-center gap-1 shadow-sm"
                @click="openBatchModal"
              >
                <i class="ri-magic-line"></i> Setel Seragam Semua Lomba
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Overview Stats Cards -->
      <div class="row mb-3">
        <!-- Card 1: Tarif Standar Cabang -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                    <i class="ri-price-tag-3-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Tarif Rata-Rata Cabang</p>
                  <h4 class="mb-0 fs-20 fw-bold text-dark">
                    Rp {{ formatRupiah(stats.avg_fee) }}
                  </h4>
                  <span class="text-muted fs-11">
                    Min: Rp {{ formatRupiah(stats.min_fee) }} • Max: Rp {{ formatRupiah(stats.max_fee) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 2: Harga Paket All-In -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                    <i class="ri-vip-crown-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Paket All-In Komplit</p>
                  <h4 class="mb-0 fs-20 fw-bold text-warning">
                    Rp {{ formatRupiah(packageForm.package_allin_price) }}
                  </h4>
                  <span class="text-success fw-semibold fs-11">
                    <i class="ri-discount-percent-fill me-1"></i>Hemat {{ packageSavingsPercent }}% (Rp {{ formatRupiah(packageSavings) }})
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 3: Total Penerimaan Lunas -->
        <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                    <i class="ri-money-dollar-circle-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Penerimaan Biaya Lunas</p>
                  <h4 class="mb-0 fs-20 fw-bold text-success">
                    Rp {{ formatRupiah(stats.total_paid_revenue) }}
                  </h4>
                  <span class="text-muted fs-11">
                    Dari {{ stats.paid_registrations_count }} pendaftar terverifikasi
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 4: Potensi Tagihan Pending -->
        <div class="col-xl-3 col-md-6">
          <div class="card card-animate mb-0 border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                    <i class="ri-time-line"></i>
                  </span>
                </div>
                <div class="flex-grow-1 ms-3">
                  <p class="text-uppercase fw-semibold fs-12 text-muted mb-1">Menunggu Verifikasi</p>
                  <h4 class="mb-0 fs-20 fw-bold text-info">
                    Rp {{ formatRupiah(stats.pending_revenue) }}
                  </h4>
                  <span class="text-muted fs-11">
                    Total {{ stats.total_registrations }} registrasi masuk
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Tabs Navigation -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom p-0">
          <ul class="nav nav-tabs nav-tabs-custom card-header-tabs border-bottom-0 mx-3" role="tablist">
            <li class="nav-item">
              <a 
                class="nav-link py-3 fw-semibold cursor-pointer" 
                :class="{ 'active': activeTab === 'competitions' }"
                @click="activeTab = 'competitions'"
              >
                <i class="ri-medal-line me-1 text-primary"></i>
                Biaya Per-Cabang Lomba ({{ competitions.length }})
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link py-3 fw-semibold cursor-pointer" 
                :class="{ 'active': activeTab === 'packages' }"
                @click="activeTab = 'packages'"
              >
                <i class="ri-vip-crown-fill me-1 text-warning"></i>
                Konfigurasi Paket Pendaftaran (All-In & Satuan)
              </a>
            </li>
            <li class="nav-item">
              <a 
                class="nav-link py-3 fw-semibold cursor-pointer" 
                :class="{ 'active': activeTab === 'payment' }"
                @click="activeTab = 'payment'"
              >
                <i class="ri-bank-card-line me-1 text-success"></i>
                Rekening Bank & Petunjuk Pembayaran
              </a>
            </li>
          </ul>
        </div>

        <div class="card-body p-4">
          <!-- ============================================================== -->
          <!-- TAB 1: BIAYA PER-CABANG LOMBA                                  -->
          <!-- ============================================================== -->
          <div v-show="activeTab === 'competitions'">
            <!-- Search & Filter Bar -->
            <div class="row g-3 align-items-center mb-4">
              <div class="col-md-5">
                <div class="search-box position-relative">
                  <input 
                    type="text" 
                    v-model="searchQuery" 
                    class="form-control ps-5" 
                    placeholder="Cari cabang lomba berdasarkan nama atau kategori..."
                  />
                  <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                  <button 
                    v-if="searchQuery" 
                    type="button" 
                    class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-2 p-0 text-muted"
                    @click="searchQuery = ''"
                  >
                    <i class="ri-close-circle-fill"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-4">
                <CustomSelect v-model="filterCategory" size="md">
                  <option value="all">Semua Kategori Bidang</option>
                  <option v-for="cat in uniqueCategories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </CustomSelect>
              </div>

              <div class="col-md-3 text-end">
                <button 
                  type="button" 
                  class="btn btn-soft-primary w-100 d-flex align-items-center justify-content-center gap-1"
                  @click="openBatchModal"
                >
                  <i class="ri-equalizer-line"></i> Setel Massal Semua Lomba
                </button>
              </div>
            </div>

            <!-- Competitions Fee Table -->
            <div class="table-responsive">
              <table class="table table-hover align-middle table-nowrap mb-0">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width: 50px;">No</th>
                    <th scope="col">Cabang Perlombaan</th>
                    <th scope="col">Bidang</th>
                    <th scope="col" class="text-center">Kapasitas Kuota</th>
                    <th scope="col" class="text-center">Status Pendaftaran</th>
                    <th scope="col" class="text-end">Biaya Registrasi</th>
                    <th scope="col" class="text-center" style="width: 130px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(comp, idx) in filteredCompetitions" :key="comp.id">
                    <td>
                      <span class="text-muted fw-semibold">{{ idx + 1 }}</span>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <div class="avatar-xs flex-shrink-0">
                          <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-14">
                            <i :class="getCompIconClass(comp)"></i>
                          </span>
                        </div>
                        <div>
                          <h6 class="fs-13 fw-bold mb-0 text-dark">{{ comp.title }}</h6>
                          <small class="text-muted">Kode ID: <code>{{ comp.id }}</code></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" :class="getCategoryBadgeClass(comp.category)">
                        {{ comp.category_label || comp.category }}
                      </span>
                    </td>
                    <td class="text-center">
                      <span class="fw-semibold text-dark">{{ comp.current_quota }}</span>
                      <span class="text-muted"> / {{ comp.max_quota }}</span>
                    </td>
                    <td class="text-center">
                      <span 
                        class="badge" 
                        :class="comp.status === 'open' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                      >
                        <i :class="comp.status === 'open' ? 'ri-checkbox-circle-line' : 'ri-lock-line'" class="me-1"></i>
                        {{ comp.status === 'open' ? 'Buka Pendaftaran' : 'Ditutup' }}
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="d-inline-flex align-items-center gap-1">
                        <span class="fs-14 fw-bold text-dark">
                          Rp {{ formatRupiah(comp.fee) }}
                        </span>
                        <span class="text-muted fs-11">/ delegasi</span>
                      </div>
                    </td>
                    <td class="text-center">
                      <button 
                        type="button" 
                        class="btn btn-sm btn-soft-primary d-inline-flex align-items-center gap-1"
                        @click="openEditFeeModal(comp)"
                        title="Ubah Biaya Lomba Ini"
                      >
                        <i class="ri-edit-line"></i>
                        <span>Ubah Biaya</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Total Sum & Info Box -->
            <div class="p-3 mt-3 bg-light-subtle rounded border d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="text-muted fs-12">
                <i class="ri-information-line text-primary me-1"></i>
                Total jika peserta mendaftar seluruh <strong>{{ competitions.length }}</strong> cabang secara satuan: 
                <strong class="text-primary fs-13">Rp {{ formatRupiah(totalAllCompetitionsFee) }}</strong>.
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning-subtle text-warning fs-12">
                  Paket All-In Komplit: Rp {{ formatRupiah(packageForm.package_allin_price) }}
                </span>
                <span class="badge bg-success-subtle text-success fs-12">
                  Hemat: Rp {{ formatRupiah(packageSavings) }} ({{ packageSavingsPercent }}%)
                </span>
              </div>
            </div>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 2: KONFIGURASI PAKET (ALL-IN & SATUAN)                     -->
          <!-- ============================================================== -->
          <div v-show="activeTab === 'packages'">
            <form @submit.prevent="savePackageSettings">
              <div class="row g-4">
                <!-- Paket All-In Column -->
                <div class="col-lg-6">
                  <div class="card border border-warning shadow-none h-100">
                    <div class="card-header bg-warning-subtle py-3 d-flex align-items-center justify-content-between">
                      <div class="d-flex align-items-center gap-2">
                        <i class="ri-vip-crown-fill text-warning fs-18"></i>
                        <h5 class="card-title mb-0 fs-14 fw-bold text-dark">Paket All-In Komplit (Rekomendasi)</h5>
                      </div>
                      <div class="form-check form-switch form-switch-md mb-0">
                        <input 
                          class="form-check-input" 
                          type="checkbox" 
                          id="allinSwitch"
                          v-model="packageForm.package_allin_is_active"
                        />
                        <label class="form-check-label fs-12 fw-semibold" for="allinSwitch">
                          {{ packageForm.package_allin_is_active ? 'Paket Aktif' : 'Nonaktif' }}
                        </label>
                      </div>
                    </div>

                    <div class="card-body">
                      <!-- Nama Paket & Tagline -->
                      <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted">Nama Paket</label>
                        <input 
                          type="text" 
                          v-model="packageForm.package_allin_name" 
                          class="form-control" 
                          placeholder="Paket All-In Komplit"
                          required
                        />
                      </div>

                      <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted">Label / Ribbon Promo</label>
                        <input 
                          type="text" 
                          v-model="packageForm.package_allin_tag" 
                          class="form-control" 
                          placeholder="Paling Hemat • Rekomendasi"
                        />
                      </div>

                      <!-- Harga Paket All-In & Harga Coret -->
                      <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                          <label class="form-label fs-12 fw-semibold text-muted">
                            Harga Paket Spesial (Rp) <span class="text-danger">*</span>
                          </label>
                          <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input 
                              type="number" 
                              v-model.number="packageForm.package_allin_price" 
                              class="form-control fw-bold text-success fs-15" 
                              min="0"
                              step="5000"
                              required
                            />
                          </div>
                          <small class="text-muted fs-11 mt-1 d-block">
                            Tarif promo hemat jika peserta mengambil paket All-In.
                          </small>
                        </div>

                        <div class="col-sm-6">
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label fs-12 fw-semibold text-muted mb-0">Harga Coret Normal (Rp)</label>
                            <div class="form-check form-switch form-switch-sm mb-0">
                              <input 
                                class="form-check-input cursor-pointer" 
                                type="checkbox" 
                                id="autoCalcOriginalSwitch"
                                v-model="packageForm.package_allin_auto_original_price"
                              />
                              <label class="form-check-label fs-11 text-primary fw-semibold cursor-pointer" for="autoCalcOriginalSwitch">
                                <i class="ri-magic-line me-1"></i>Auto Kalkulasi
                              </label>
                            </div>
                          </div>
                          <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input 
                              type="number" 
                              v-model.number="packageForm.package_allin_original_price" 
                              class="form-control" 
                              :class="{ 'bg-light text-dark fw-bold': packageForm.package_allin_auto_original_price, 'text-muted': !packageForm.package_allin_auto_original_price }"
                              :readonly="packageForm.package_allin_auto_original_price"
                              min="0"
                              step="5000"
                            />
                            <span v-if="packageForm.package_allin_auto_original_price" class="input-group-text bg-primary-subtle text-primary border-primary-subtle py-0 fs-11 fw-semibold">
                              <i class="ri-lock-line me-1"></i>Terkunci Auto
                            </span>
                          </div>
                          <div class="mt-1">
                            <span v-if="packageForm.package_allin_auto_original_price" class="badge bg-primary-subtle text-primary fs-11 py-1 px-2 d-inline-flex align-items-center gap-1 text-wrap text-start">
                              <i class="ri-calculator-line flex-shrink-0"></i>
                              <span>Otomatis dihitung dari akumulasi <strong>{{ competitions.length }} cabang lomba satuan</strong> (Rp {{ formatRupiah(totalAllCompetitionsFee) }})</span>
                            </span>
                            <small v-else class="text-muted fs-11 d-block">
                              Mode manual aktif. Masukkan nominal harga coret normal yang diinginkan.
                            </small>
                          </div>
                        </div>
                      </div>

                      <!-- Live Calculation Badge -->
                      <div class="alert alert-success d-flex align-items-center justify-content-between p-2 mb-3">
                        <span class="fs-12">
                          <i class="ri-discount-percent-fill me-1"></i>
                          Potongan Diskon: <strong>Rp {{ formatRupiah(packageSavings) }}</strong>
                        </span>
                        <span class="badge bg-success fs-11">
                          Hemat {{ packageSavingsPercent }}%
                        </span>
                      </div>

                      <!-- Deskripsi Singkat -->
                      <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted">Deskripsi Paket All-In</label>
                        <textarea 
                          v-model="packageForm.package_allin_desc" 
                          class="form-control" 
                          rows="2" 
                          placeholder="Deskripsi singkat keunggulan paket all in..."
                        ></textarea>
                      </div>

                      <!-- Poin-poin Manfaat / Keunggulan (Dynamic List) -->
                      <div class="mb-0">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <label class="form-label fs-12 fw-semibold text-muted mb-0">Daftar Poin Keunggulan Fasilitas</label>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-primary"
                            @click="addAllinFeature"
                          >
                            <i class="ri-add-line"></i> Tambah Poin
                          </button>
                        </div>

                        <div class="d-flex flex-column gap-2">
                          <div 
                            v-for="(feat, fIdx) in packageForm.package_allin_features" 
                            :key="fIdx"
                            class="input-group input-group-sm"
                          >
                            <span class="input-group-text bg-light text-muted">
                              <i class="ri-check-double-line text-warning"></i>
                            </span>
                            <input 
                              type="text" 
                              v-model="packageForm.package_allin_features[fIdx]" 
                              class="form-control" 
                              placeholder="Ketik poin manfaat..."
                            />
                            <button 
                              type="button" 
                              class="btn btn-outline-danger"
                              @click="removeAllinFeature(fIdx)"
                              :disabled="packageForm.package_allin_features.length <= 1"
                            >
                              <i class="ri-close-line"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Paket Satuan Column -->
                <div class="col-lg-6">
                  <div class="card border shadow-none h-100">
                    <div class="card-header bg-light py-3 d-flex align-items-center gap-2">
                      <i class="ri-apps-line text-primary fs-18"></i>
                      <h5 class="card-title mb-0 fs-14 fw-bold text-dark">Paket Fleksibel Satuan (Per-Cabang)</h5>
                    </div>

                    <div class="card-body">
                      <!-- Nama Paket Satuan -->
                      <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted">Nama Opsi Pendaftaran</label>
                        <input 
                          type="text" 
                          v-model="packageForm.package_satuan_name" 
                          class="form-control" 
                          placeholder="Paket Fleksibel Satuan"
                          required
                        />
                      </div>

                      <div class="mb-3">
                        <label class="form-label fs-12 fw-semibold text-muted">Deskripsi Opsi Satuan</label>
                        <textarea 
                          v-model="packageForm.package_satuan_desc" 
                          class="form-control" 
                          rows="3" 
                          placeholder="Deskripsi pendaftaran satuan per-cabang..."
                        ></textarea>
                      </div>

                      <!-- Poin-poin Manfaat Satuan (Dynamic List) -->
                      <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                          <label class="form-label fs-12 fw-semibold text-muted mb-0">Daftar Poin Fasilitas Satuan</label>
                          <button 
                            type="button" 
                            class="btn btn-xs btn-outline-primary"
                            @click="addSatuanFeature"
                          >
                            <i class="ri-add-line"></i> Tambah Poin
                          </button>
                        </div>

                        <div class="d-flex flex-column gap-2">
                          <div 
                            v-for="(sFeat, sIdx) in packageForm.package_satuan_features" 
                            :key="sIdx"
                            class="input-group input-group-sm"
                          >
                            <span class="input-group-text bg-light text-muted">
                              <i class="ri-check-line text-success"></i>
                            </span>
                            <input 
                              type="text" 
                              v-model="packageForm.package_satuan_features[sIdx]" 
                              class="form-control" 
                              placeholder="Ketik poin fasilitas..."
                            />
                            <button 
                              type="button" 
                              class="btn btn-outline-danger"
                              @click="removeSatuanFeature(sIdx)"
                              :disabled="packageForm.package_satuan_features.length <= 1"
                            >
                              <i class="ri-close-line"></i>
                            </button>
                          </div>
                        </div>
                      </div>

                      <div class="alert alert-info p-2 fs-12 mb-0">
                        <i class="ri-information-line me-1"></i>
                        Nominal harga paket satuan diatur secara individual untuk masing-masing cabang lomba pada <strong>Tab 1 (Biaya Per-Cabang Lomba)</strong>.
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 text-end">
                  <button 
                    type="submit" 
                    class="btn btn-primary px-4 shadow-sm"
                    :disabled="isSavingPackage"
                  >
                    <i class="ri-save-line me-1"></i>
                    {{ isSavingPackage ? 'Menyimpan...' : 'Simpan Konfigurasi Paket' }}
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- ============================================================== -->
          <!-- TAB 3: REKENING BANK & PETUNJUK PEMBAYARAN                     -->
          <!-- ============================================================== -->
          <!-- ============================================================== -->
          <!-- TAB 3: REKENING BANK & PETUNJUK PEMBAYARAN (MULTI-REKENING)    -->
          <!-- ============================================================== -->
          <div v-show="activeTab === 'payment'">
            <form @submit.prevent="savePaymentSettings">
              <div class="row g-4">
                <!-- Left: Bank Accounts List & Config -->
                <div class="col-lg-7">
                  <!-- Section 1: Daftar Nomor Rekening Bank -->
                  <div class="card border shadow-none mb-4">
                    <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                      <div class="d-flex align-items-center gap-2">
                        <i class="ri-bank-card-2-line text-success fs-18"></i>
                        <div>
                          <h5 class="card-title mb-0 fs-14 fw-bold text-dark">Daftar Rekening Bank Tujuan Transfer</h5>
                          <small class="text-muted fs-11">Mendukung lebih dari 1 rekening (BSI, BCA, BRI, Mandiri, dll)</small>
                        </div>
                      </div>
                      <button 
                        type="button" 
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1 shadow-sm"
                        @click="openAddAccountModal"
                      >
                        <i class="ri-add-line"></i> Tambah Rekening
                      </button>
                    </div>
                    <div class="card-body p-3">
                      <!-- List of Bank Accounts -->
                      <div v-if="bankAccountsList.length === 0" class="text-center py-4 text-muted">
                        <i class="ri-bank-line fs-36 text-muted mb-2 d-block"></i>
                        <p class="mb-2 fs-13">Belum ada rekening bank yang ditambahkan.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="openAddAccountModal">
                          Tambah Rekening Pertama
                        </button>
                      </div>

                      <div v-else class="d-flex flex-column gap-3">
                        <div 
                          v-for="(acc, aIdx) in bankAccountsList" 
                          :key="acc.id || aIdx"
                          class="p-3 rounded-3 border position-relative transition-all"
                          :class="acc.is_primary ? 'border-primary bg-primary-subtle' : (acc.is_active ? 'bg-light' : 'bg-light opacity-60')"
                        >
                          <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                              <div class="avatar-xs flex-shrink-0">
                                <span class="avatar-title rounded-circle fs-14" :class="acc.is_primary ? 'bg-primary text-white' : 'bg-secondary-subtle text-secondary'">
                                  <i class="ri-bank-line"></i>
                                </span>
                              </div>
                              <div>
                                <h6 class="mb-0 fs-14 fw-bold text-dark d-flex align-items-center gap-1.5 flex-wrap">
                                  {{ acc.bank_name }}
                                  <span v-if="acc.is_primary" class="badge bg-warning text-dark fs-10 fw-bold">
                                    <i class="ri-star-fill me-0.5"></i> Rekening Utama
                                  </span>
                                  <span v-if="!acc.is_active" class="badge bg-secondary-subtle text-muted fs-10">
                                    Nonaktif
                                  </span>
                                </h6>
                                <span class="fs-12 text-muted">A.N. <strong>{{ acc.account_holder }}</strong></span>
                              </div>
                            </div>

                            <!-- Actions dropdown / buttons -->
                            <div class="d-flex align-items-center gap-1">
                              <!-- Set Primary Button -->
                              <button 
                                v-if="!acc.is_primary"
                                type="button" 
                                class="btn btn-xs btn-outline-warning d-inline-flex align-items-center gap-1"
                                @click="setPrimaryAccount(aIdx)"
                                title="Jadikan Rekening Utama"
                              >
                                <i class="ri-star-line"></i> Set Utama
                              </button>

                              <!-- Toggle Active -->
                              <button 
                                type="button" 
                                class="btn btn-xs"
                                :class="acc.is_active ? 'btn-soft-success' : 'btn-soft-secondary'"
                                @click="toggleAccountActive(aIdx)"
                                :title="acc.is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan'"
                              >
                                {{ acc.is_active ? 'Aktif' : 'Nonaktif' }}
                              </button>

                              <!-- Edit Button -->
                              <button 
                                type="button" 
                                class="btn btn-xs btn-soft-info"
                                @click="openEditAccountModal(acc, aIdx)"
                                title="Edit Rekening"
                              >
                                <i class="ri-edit-line"></i>
                              </button>

                              <!-- Delete Button -->
                              <button 
                                type="button" 
                                class="btn btn-xs btn-soft-danger"
                                :disabled="bankAccountsList.length <= 1"
                                @click="deleteAccount(aIdx)"
                                title="Hapus Rekening"
                              >
                                <i class="ri-delete-bin-line"></i>
                              </button>
                            </div>
                          </div>

                          <!-- Account Number with Copy -->
                          <div class="d-flex align-items-center justify-content-between p-2 rounded bg-white border">
                            <span class="font-monospace fs-16 fw-bold text-primary">{{ acc.account_number }}</span>
                            <button 
                              type="button" 
                              class="btn btn-xs btn-outline-secondary d-flex align-items-center gap-1"
                              @click="copyAccountText(acc.account_number)"
                            >
                              <i class="ri-file-copy-line"></i> Salin
                            </button>
                          </div>

                          <div v-if="acc.notes" class="mt-2 fs-11 text-muted">
                            <i class="ri-information-line me-1 text-info"></i> {{ acc.notes }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Section 2: Opsi Tambahan & Instruksi -->
                  <div class="card border shadow-none">
                    <div class="card-header bg-light py-3">
                      <h5 class="card-title mb-0 fs-14 fw-bold text-dark">
                        <i class="ri-settings-3-line text-primary me-1"></i> Pengaturan Konfirmasi & Petunjuk
                      </h5>
                    </div>
                    <div class="card-body p-3">
                      <div class="row g-3">
                        <div class="col-md-12">
                          <label class="form-label fs-12 fw-semibold text-muted">
                            No. WhatsApp Konfirmasi Pembayaran Bendahara
                          </label>
                          <div class="input-group">
                            <span class="input-group-text bg-light"><i class="ri-whatsapp-line text-success"></i></span>
                            <input 
                              type="text" 
                              v-model="paymentForm.payment_confirmation_wa" 
                              class="form-control" 
                              placeholder="Contoh: 0838 5007 0767"
                            />
                          </div>
                          <small class="text-muted fs-11">Nomor ini menjadi kontak otomatis bantuan pembayaran pada invoice dan form pendaftaran.</small>
                        </div>

                        <div class="col-12">
                          <label class="form-label fs-12 fw-semibold text-muted">
                            Metode Pembayaran Tunai (Cash di Sekretariat Kampus)
                          </label>
                          <div class="form-check form-switch form-switch-md">
                            <input 
                              class="form-check-input" 
                              type="checkbox" 
                              id="cashPaymentSwitch"
                              v-model="paymentForm.payment_cash_enabled"
                            />
                            <label class="form-check-label fs-13" for="cashPaymentSwitch">
                              {{ paymentForm.payment_cash_enabled ? 'Izinkan opsi bayar tunai / langsung di tempat' : 'Hanya terima pembayaran transfer bank' }}
                            </label>
                          </div>
                        </div>

                        <div class="col-12">
                          <label class="form-label fs-12 fw-semibold text-muted">
                            Petunjuk / Catatan Pembayaran Peserta
                          </label>
                          <textarea 
                            v-model="paymentForm.payment_instructions" 
                            class="form-control" 
                            rows="3" 
                            placeholder="Tuliskan petunjuk transfer dan konfirmasi pembayaran..."
                          ></textarea>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Right: Live Preview Box Card -->
                <div class="col-lg-5">
                  <div class="card border shadow-none bg-light-subtle">
                    <div class="card-header bg-white py-3">
                      <h5 class="card-title mb-0 fs-13 fw-bold text-muted">
                        <i class="ri-eye-line text-primary me-1"></i> Pratinjau Tampilan Rekening di Web Publik
                      </h5>
                    </div>
                    <div class="card-body p-4">
                      <p class="text-muted fs-12 mb-3">
                        Berikut tampilan opsi transfer yang dilihat peserta pada ringkasan pembayaran formulir pendaftaran:
                      </p>

                      <div class="d-flex flex-column gap-2 mb-3">
                        <div 
                          v-for="(acc, prIdx) in activeBankAccounts" 
                          :key="'preview-' + (acc.id || prIdx)"
                          class="p-3 rounded border bg-white shadow-sm"
                        >
                          <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                              <i class="ri-bank-card-2-fill fs-18 text-success"></i>
                              <span class="fw-bold fs-13 text-dark">{{ acc.bank_name }}</span>
                            </div>
                            <span v-if="acc.is_primary" class="badge bg-warning text-dark fs-10">Utama</span>
                            <span v-else class="badge bg-secondary-subtle text-muted fs-10">Pilihan Bank</span>
                          </div>

                          <div class="mb-1.5">
                            <div class="text-muted fs-10">Nomor Rekening:</div>
                            <div class="d-flex align-items-center justify-content-between">
                              <span class="fs-16 fw-bold font-monospace text-primary">{{ acc.account_number }}</span>
                              <span class="badge bg-light text-muted border fs-10">Salin</span>
                            </div>
                          </div>

                          <div class="fs-12">
                            <span class="text-muted fs-10 d-block">Atas Nama (A.N.):</span>
                            <span class="fw-semibold text-dark">{{ acc.account_holder }}</span>
                          </div>

                          <div v-if="acc.notes" class="mt-2 pt-1 border-top fs-11 text-muted">
                            <i class="ri-information-line text-info me-1"></i>{{ acc.notes }}
                          </div>
                        </div>
                      </div>

                      <div v-if="paymentForm.payment_confirmation_wa" class="p-2.5 rounded bg-white border fs-12 text-muted">
                        <i class="ri-whatsapp-line text-success me-1"></i>
                        Konfirmasi WA Bendahara: <strong>{{ paymentForm.payment_confirmation_wa }}</strong>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="col-12 text-end">
                  <button 
                    type="submit" 
                    class="btn btn-primary px-4 shadow-sm"
                    :disabled="isSavingPayment"
                  >
                    <i class="ri-save-line me-1"></i>
                    {{ isSavingPayment ? 'Menyimpan...' : 'Simpan Seluruh Data Rekening' }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL EDIT BIAYA CABANG LOMBA                                   -->
    <!-- ============================================================== -->
    <div v-if="isEditFeeModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isEditFeeModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light py-3">
            <h5 class="modal-title fw-bold fs-15 d-flex align-items-center gap-2">
              <i class="ri-edit-line text-primary"></i>
              Ubah Biaya Cabang: {{ activeEditingComp?.title }}
            </h5>
            <button type="button" class="btn-close" @click="closeEditFeeModal"></button>
          </div>

          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fs-13 fw-semibold text-muted">Nominal Biaya Registrasi Baru (Rp)</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text bg-light fw-bold">Rp</span>
                <input 
                  type="number" 
                  v-model.number="editFeeValue" 
                  class="form-control fw-bold fs-18 text-primary" 
                  min="0"
                  step="5000"
                  placeholder="50000"
                  @keydown.enter.prevent="submitEditFee"
                />
              </div>
              <small class="text-muted mt-1 d-block">
                Terbilang: <strong>Rp {{ formatRupiah(editFeeValue) }}</strong>
              </small>
            </div>

            <!-- Quick Preset Chips -->
            <div class="mb-0">
              <label class="form-label fs-11 text-muted d-block mb-1">Preset Cepat:</label>
              <div class="d-flex gap-2 flex-wrap">
                <button 
                  v-for="preset in [25000, 35000, 50000, 75000, 100000]" 
                  :key="preset"
                  type="button" 
                  class="btn btn-xs btn-outline-secondary"
                  @click="editFeeValue = preset"
                >
                  Rp {{ formatRupiah(preset) }}
                </button>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-light btn-sm" @click="closeEditFeeModal">Batal</button>
            <button 
              type="button" 
              class="btn btn-primary btn-sm px-4 shadow-sm"
              :disabled="isSubmittingFee"
              @click="submitEditFee"
            >
              {{ isSubmittingFee ? 'Menyimpan...' : 'Terapkan Biaya' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL SETEL BIAYA SERAGAM (BATCH APPLY)                        -->
    <!-- ============================================================== -->
    <div v-if="isBatchModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isBatchModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light py-3">
            <h5 class="modal-title fw-bold fs-15 d-flex align-items-center gap-2">
              <i class="ri-magic-line text-primary"></i>
              Setel Biaya Seragam Semua Cabang Lomba
            </h5>
            <button type="button" class="btn-close" @click="isBatchModalOpen = false"></button>
          </div>

          <div class="modal-body p-4">
            <p class="text-muted fs-13 mb-3">
              Tindakan ini akan menyeragamkan tarif pendaftaran untuk seluruh <strong>{{ competitions.length }} cabang lomba</strong> menjadi nominal yang sama secara otomatis.
            </p>

            <div class="mb-3">
              <label class="form-label fs-13 fw-semibold text-muted">Pilih Nominal Seragam (Rp)</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text bg-light fw-bold">Rp</span>
                <input 
                  type="number" 
                  v-model.number="batchUniformFee" 
                  class="form-control fw-bold fs-18 text-primary" 
                  min="0"
                  step="5000"
                  placeholder="50000"
                  @keydown.enter.prevent="submitBatchUniformFee"
                />
              </div>
              <small class="text-muted mt-1 d-block">
                Semua cabang akan disetel ke: <strong>Rp {{ formatRupiah(batchUniformFee) }}</strong>
              </small>
            </div>

            <!-- Quick Presets -->
            <div class="mb-0">
              <label class="form-label fs-11 text-muted d-block mb-1">Pilihan Cepat:</label>
              <div class="d-flex gap-2 flex-wrap">
                <button 
                  v-for="preset in [30000, 40000, 50000, 60000, 75000]" 
                  :key="preset"
                  type="button" 
                  class="btn btn-xs btn-outline-secondary"
                  @click="batchUniformFee = preset"
                >
                  Rp {{ formatRupiah(preset) }}
                </button>
              </div>
            </div>
          </div>

          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-light btn-sm" @click="isBatchModalOpen = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-primary btn-sm px-4 shadow-sm"
              :disabled="isSubmittingBatch"
              @click="submitBatchUniformFee"
            >
              {{ isSubmittingBatch ? 'Menyimpan...' : 'Terapkan Ke Seluruh Lomba' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL SIMULASI KALKULATOR BIAYA                                -->
    <!-- ============================================================== -->
    <div v-if="isCalculatorModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isCalculatorModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light py-3">
            <h5 class="modal-title fw-bold fs-15 d-flex align-items-center gap-2">
              <i class="ri-calculator-line text-primary"></i>
              Simulasi Kalkulator Biaya Pendaftaran
            </h5>
            <button type="button" class="btn-close" @click="isCalculatorModalOpen = false"></button>
          </div>

          <div class="modal-body p-4">
            <div class="mb-3">
              <label class="form-label fs-12 fw-semibold text-muted">Pilih Jenis Paket Pendaftaran:</label>
              <div class="d-flex gap-2">
                <button 
                  type="button" 
                  class="btn btn-sm flex-fill" 
                  :class="calcPackageType === 'satuan' ? 'btn-primary' : 'btn-outline-secondary'"
                  @click="calcPackageType = 'satuan'"
                >
                  Paket Satuan Cabang
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm flex-fill" 
                  :class="calcPackageType === 'allin' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'"
                  @click="calcPackageType = 'allin'"
                >
                  <i class="ri-vip-crown-fill me-1"></i> Paket All-In
                </button>
              </div>
            </div>

            <!-- Cabang selection if Satuan -->
            <div v-if="calcPackageType === 'satuan'" class="mb-3">
              <label class="form-label fs-12 fw-semibold text-muted">Pilih Cabang Lomba yang Diikuti:</label>
              <div class="d-flex flex-column gap-2" style="max-height: 200px; overflow-y: auto;">
                <label 
                  v-for="c in competitions" 
                  :key="c.id"
                  class="form-check d-flex align-items-center justify-content-between p-2 rounded border cursor-pointer mb-0"
                  :class="{ 'bg-primary-subtle border-primary': calcSelectedCompIds.includes(c.id) }"
                >
                  <div class="d-flex align-items-center gap-2">
                    <input 
                      type="checkbox" 
                      class="form-check-input ms-0 mt-0" 
                      :value="c.id" 
                      v-model="calcSelectedCompIds"
                    />
                    <span class="fs-13 fw-semibold text-dark">{{ c.title }}</span>
                  </div>
                  <span class="fs-12 text-muted fw-bold">Rp {{ formatRupiah(c.fee) }}</span>
                </label>
              </div>
            </div>

            <!-- Result Box -->
            <div class="p-3 rounded border bg-light text-center">
              <span class="text-muted fs-12 d-block mb-1">Total Tagihan Yang Harus Dibayar:</span>
              <h3 class="fw-bold mb-1" :class="calcPackageType === 'allin' ? 'text-warning' : 'text-primary'">
                Rp {{ formatRupiah(simulatedTotalFee) }}
              </h3>
              <small v-if="calcPackageType === 'allin'" class="text-success fw-semibold">
                Bebas ikuti seluruh 8 cabang lomba (Hemat Rp {{ formatRupiah(packageSavings) }})
              </small>
              <small v-else class="text-muted">
                {{ calcSelectedCompIds.length }} cabang lomba dipilih
              </small>
            </div>
          </div>

          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-secondary btn-sm" @click="isCalculatorModalOpen = false">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL TAMBAH / EDIT REKENING BANK                              -->
    <!-- ============================================================== -->
    <div v-if="isAccountModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isAccountModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-light py-3">
            <h5 class="modal-title fw-bold fs-15 d-flex align-items-center gap-2">
              <i class="ri-bank-card-fill text-primary"></i>
              {{ isEditingAccount ? 'Edit Rekening Bank' : 'Tambah Rekening Bank Baru' }}
            </h5>
            <button type="button" class="btn-close" @click="closeAccountModal"></button>
          </div>

          <form @submit.prevent="saveAccountModal">
            <div class="modal-body p-4">
              <!-- Quick Bank Presets -->
              <div class="mb-3">
                <label class="form-label fs-11 text-muted d-block mb-1">Pilihan Cepat Bank Populer:</label>
                <div class="d-flex gap-1.5 flex-wrap">
                  <button 
                    v-for="bPreset in ['BSI', 'BCA', 'BRI', 'Mandiri', 'BNI', 'Bank Jatim', 'Muamalat']" 
                    :key="bPreset"
                    type="button" 
                    class="btn btn-xs btn-outline-secondary"
                    @click="applyBankPreset(bPreset)"
                  >
                    {{ bPreset }}
                  </button>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">
                  Nama Bank <span class="text-danger">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="accountForm.bank_name" 
                  class="form-control" 
                  placeholder="Contoh: Bank Syariah Indonesia (BSI)"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">
                  Nomor Rekening <span class="text-danger">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="accountForm.account_number" 
                  class="form-control font-monospace fw-bold fs-15 text-primary" 
                  placeholder="Contoh: 7123456789"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">
                  Atas Nama Rekening (A.N.) <span class="text-danger">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="accountForm.account_holder" 
                  class="form-control fw-semibold" 
                  placeholder="Contoh: Panitia Semarak Aliyah XIV"
                  required
                />
              </div>

              <div class="mb-3">
                <label class="form-label fs-12 fw-semibold text-dark">
                  Catatan / Keterangan Tambahan (Opsional)
                </label>
                <input 
                  type="text" 
                  v-model="accountForm.notes" 
                  class="form-control" 
                  placeholder="Contoh: Bebas biaya admin transfer sesama BSI"
                />
              </div>

              <div class="d-flex flex-column gap-2 pt-2 border-top">
                <div class="form-check form-switch">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="modalPrimarySwitch"
                    v-model="accountForm.is_primary"
                  />
                  <label class="form-check-label fs-13 fw-semibold" for="modalPrimarySwitch">
                    Jadikan Rekening Utama Panitia
                  </label>
                </div>

                <div class="form-check form-switch">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="modalActiveSwitch"
                    v-model="accountForm.is_active"
                  />
                  <label class="form-check-label fs-13" for="modalActiveSwitch">
                    Status Aktif (Tampil di Formulir Pendaftaran Web)
                  </label>
                </div>
              </div>
            </div>

            <div class="modal-footer bg-light py-2">
              <button type="button" class="btn btn-light btn-sm" @click="closeAccountModal">Batal</button>
              <button 
                type="submit" 
                class="btn btn-primary btn-sm px-4 shadow-sm"
              >
                {{ isEditingAccount ? 'Simpan Perubahan' : 'Tambahkan Rekening' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </VelzonLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import VelzonLayout from '@/Layouts/VelzonLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  competitions: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  packageSettings: {
    type: Object,
    default: () => ({})
  },
  paymentSettings: {
    type: Object,
    default: () => ({})
  }
});

const { success, error } = useToast();

// Tab state: 'competitions' | 'packages' | 'payment'
const activeTab = ref('competitions');

// Search & Filter state
const searchQuery = ref('');
const filterCategory = ref('all');

const uniqueCategories = computed(() => {
  const cats = props.competitions.map(c => c.category_label || c.category || 'Kompetisi');
  return [...new Set(cats)];
});

const filteredCompetitions = computed(() => {
  return props.competitions.filter(c => {
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase().trim();
      const matchTitle = c.title && c.title.toLowerCase().includes(q);
      const matchId = c.id && c.id.toLowerCase().includes(q);
      const matchCat = (c.category_label || c.category || '').toLowerCase().includes(q);
      if (!matchTitle && !matchId && !matchCat) return false;
    }

    if (filterCategory.value !== 'all') {
      const cat = c.category_label || c.category;
      if (cat !== filterCategory.value) return false;
    }

    return true;
  });
});

// 1. Edit Single Competition Fee State
const isEditFeeModalOpen = ref(false);
const activeEditingComp = ref(null);
const editFeeValue = ref(50000);
const isSubmittingFee = ref(false);

function openEditFeeModal(comp) {
  activeEditingComp.value = comp;
  editFeeValue.value = Number(comp.fee || 50000);
  isEditFeeModalOpen.value = true;
}

function closeEditFeeModal() {
  isEditFeeModalOpen.value = false;
  activeEditingComp.value = null;
}

function submitEditFee() {
  if (!activeEditingComp.value) return;
  isSubmittingFee.value = true;

  router.post(`/admin/biaya/competition/${activeEditingComp.value.id}`, {
    fee: editFeeValue.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingFee.value = false;
      closeEditFeeModal();
      success('Biaya registrasi cabang lomba berhasil diperbarui!');
    },
    onError: (errs) => {
      isSubmittingFee.value = false;
      error(Object.values(errs)[0] || 'Gagal mengubah biaya.');
    }
  });
}

// 2. Batch Uniform Fee State
const isBatchModalOpen = ref(false);
const batchUniformFee = ref(50000);
const isSubmittingBatch = ref(false);

function openBatchModal() {
  batchUniformFee.value = props.stats.avg_fee || 50000;
  isBatchModalOpen.value = true;
}

function submitBatchUniformFee() {
  isSubmittingBatch.value = true;
  router.post('/admin/biaya/batch-competition', {
    uniform_fee: batchUniformFee.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingBatch.value = false;
      isBatchModalOpen.value = false;
      success('Biaya seluruh cabang lomba berhasil diseragamkan!');
    },
    onError: () => {
      isSubmittingBatch.value = false;
      error('Gagal memperbarui biaya seragam.');
    }
  });
}

// 3. Package Settings Form State
const isSavingPackage = ref(false);

const totalAllCompetitionsFee = computed(() => {
  return (props.competitions || []).reduce((acc, c) => acc + Number(c.fee || 0), 0);
});

const isAutoOriginal = props.packageSettings.allin_auto_original_price !== false;

const packageForm = ref({
  package_allin_is_active: props.packageSettings.allin_is_active !== false,
  package_allin_auto_original_price: isAutoOriginal,
  package_allin_name: props.packageSettings.allin_name || 'Paket All-In Komplit',
  package_allin_price: props.packageSettings.allin_price || 200000,
  package_allin_original_price: isAutoOriginal 
    ? totalAllCompetitionsFee.value 
    : (props.packageSettings.allin_original_price || totalAllCompetitionsFee.value || 400000),
  package_allin_tag: props.packageSettings.allin_tag || 'Paling Hemat • Rekomendasi',
  package_allin_desc: props.packageSettings.allin_desc || '',
  package_allin_features: Array.isArray(props.packageSettings.allin_features) 
    ? [...props.packageSettings.allin_features] 
    : [
        'Bebas daftarkan kontingen di seluruh 8 cabang lomba',
        'Hemat biaya pendaftaran total hingga Rp 200.000,- (50%)',
        'Prioritas verifikasi berkas cepat & reservasi kuota kontingen',
        'Berhak memperebutkan Piala Bergilir Juara Umum Semarak',
        'E-Sertifikat resmi ber-barcode untuk seluruh siswa & guru pembimbing',
      ],
  package_satuan_name: props.packageSettings.satuan_name || 'Paket Fleksibel Satuan',
  package_satuan_desc: props.packageSettings.satuan_desc || '',
  package_satuan_features: Array.isArray(props.packageSettings.satuan_features) 
    ? [...props.packageSettings.satuan_features] 
    : [
        'Bebas memilih cabang lomba sesuai minat & bakat siswa',
        'Sudah termasuk kuota maksimal peserta sesuai ketentuan juknis',
        'E-Sertifikat resmi ber-barcode untuk seluruh peserta & guru pembimbing',
        'Fasilitas ID Card resmi dan akses Technical Meeting (Offline & Zoom)',
        'Konsultasi dan panduan teknis langsung via Helpdesk Panitia',
      ],
});

// Otomatis sinkronisasi Harga Coret Normal jika mode kalkulasi otomatis aktif
watch(
  [() => packageForm.value.package_allin_auto_original_price, totalAllCompetitionsFee],
  ([isAuto, newTotal]) => {
    if (isAuto) {
      packageForm.value.package_allin_original_price = Number(newTotal || 0);
    }
  },
  { immediate: true }
);

const packageSavings = computed(() => {
  const orig = Number(packageForm.value.package_allin_original_price || 0);
  const now = Number(packageForm.value.package_allin_price || 0);
  return Math.max(0, orig - now);
});

const packageSavingsPercent = computed(() => {
  const orig = Number(packageForm.value.package_allin_original_price || 0);
  if (orig <= 0) return 0;
  return Math.round((packageSavings.value / orig) * 100);
});

function addAllinFeature() {
  packageForm.value.package_allin_features.push('');
}

function removeAllinFeature(idx) {
  packageForm.value.package_allin_features.splice(idx, 1);
}

function addSatuanFeature() {
  packageForm.value.package_satuan_features.push('');
}

function removeSatuanFeature(idx) {
  packageForm.value.package_satuan_features.splice(idx, 1);
}

function savePackageSettings() {
  isSavingPackage.value = true;
  router.post('/admin/biaya/package', packageForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingPackage.value = false;
      success('Konfigurasi paket All-In dan Satuan berhasil disimpan!');
    },
    onError: (errs) => {
      isSavingPackage.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan konfigurasi paket.');
    }
  });
}

// 4. Payment Settings Form State (Multi-Bank Accounts)
const isSavingPayment = ref(false);

const bankAccountsList = ref(
  Array.isArray(props.paymentSettings.bank_accounts) && props.paymentSettings.bank_accounts.length > 0
    ? JSON.parse(JSON.stringify(props.paymentSettings.bank_accounts))
    : [
        {
          id: 'acc-1',
          bank_name: props.paymentSettings.bank_name || 'Bank Syariah Indonesia (BSI)',
          account_number: props.paymentSettings.account_number || '7123456789',
          account_holder: props.paymentSettings.account_holder || 'Semarak Aliyah',
          is_primary: true,
          is_active: true,
          notes: 'Rekening Utama Panitia',
        }
      ]
);

const activeBankAccounts = computed(() => {
  return bankAccountsList.value.filter(a => a.is_active !== false);
});

const paymentForm = ref({
  payment_bank_name: props.paymentSettings.bank_name || 'Bank Syariah Indonesia (BSI)',
  payment_account_number: props.paymentSettings.account_number || '7123456789',
  payment_account_holder: props.paymentSettings.account_holder || 'Semarak Aliyah',
  payment_confirmation_wa: props.paymentSettings.confirmation_wa || '0838 5007 0767',
  payment_instructions: props.paymentSettings.instructions || '',
  payment_cash_enabled: props.paymentSettings.cash_enabled !== false,
});

// Modal State for Bank Accounts
const isAccountModalOpen = ref(false);
const isEditingAccount = ref(false);
const editingAccountIndex = ref(-1);
const accountForm = ref({
  id: '',
  bank_name: '',
  account_number: '',
  account_holder: '',
  notes: '',
  is_primary: false,
  is_active: true,
});

function applyBankPreset(preset) {
  const map = {
    'BSI': { name: 'Bank Syariah Indonesia (BSI)', notes: 'Bebas biaya transfer sesama BSI' },
    'BCA': { name: 'Bank Central Asia (BCA)', notes: 'Transfer via BCA Virtual/Rekening' },
    'BRI': { name: 'Bank Rakyat Indonesia (BRI)', notes: 'Transfer via BRIMO / ATM BRI' },
    'Mandiri': { name: 'Bank Mandiri', notes: 'Transfer via Livin by Mandiri' },
    'BNI': { name: 'Bank Negara Indonesia (BNI)', notes: 'Transfer via BNI Mobile Banking' },
    'Bank Jatim': { name: 'Bank Jatim', notes: 'Rekening Daerah Jawa Timur' },
    'Muamalat': { name: 'Bank Muamalat', notes: 'Perbankan Syariah' },
  };
  if (map[preset]) {
    accountForm.value.bank_name = map[preset].name;
    if (!accountForm.value.notes) {
      accountForm.value.notes = map[preset].notes;
    }
  }
}

function openAddAccountModal() {
  isEditingAccount.value = false;
  editingAccountIndex.value = -1;
  accountForm.value = {
    id: 'acc-' + Date.now(),
    bank_name: '',
    account_number: '',
    account_holder: paymentForm.value.payment_account_holder || 'Semarak Aliyah XIV',
    notes: '',
    is_primary: bankAccountsList.value.length === 0,
    is_active: true,
  };
  isAccountModalOpen.value = true;
}

function openEditAccountModal(acc, idx) {
  isEditingAccount.value = true;
  editingAccountIndex.value = idx;
  accountForm.value = { ...acc };
  isAccountModalOpen.value = true;
}

function closeAccountModal() {
  isAccountModalOpen.value = false;
  editingAccountIndex.value = -1;
}

function saveAccountModal() {
  if (!accountForm.value.bank_name || !accountForm.value.account_number || !accountForm.value.account_holder) {
    error('Nama Bank, Nomor Rekening, dan Atas Nama wajib diisi!');
    return;
  }

  if (accountForm.value.is_primary) {
    bankAccountsList.value.forEach(a => { a.is_primary = false; });
  }

  if (isEditingAccount.value && editingAccountIndex.value >= 0) {
    bankAccountsList.value[editingAccountIndex.value] = { ...accountForm.value };
    success('Data rekening berhasil diperbarui!');
  } else {
    bankAccountsList.value.push({ ...accountForm.value });
    success('Rekening bank baru berhasil ditambahkan!');
  }

  // Ensure at least 1 primary account
  const hasPrimary = bankAccountsList.value.some(a => a.is_primary);
  if (!hasPrimary && bankAccountsList.value.length > 0) {
    bankAccountsList.value[0].is_primary = true;
  }

  closeAccountModal();
}

function setPrimaryAccount(idx) {
  bankAccountsList.value.forEach((a, i) => {
    a.is_primary = (i === idx);
  });
  success(`"${bankAccountsList.value[idx].bank_name}" kini disetel sebagai rekening utama!`);
}

function toggleAccountActive(idx) {
  bankAccountsList.value[idx].is_active = !bankAccountsList.value[idx].is_active;
  const statusStr = bankAccountsList.value[idx].is_active ? 'diaktifkan' : 'dinonaktifkan';
  success(`Rekening ${bankAccountsList.value[idx].bank_name} berhasil ${statusStr}.`);
}

function deleteAccount(idx) {
  if (bankAccountsList.value.length <= 1) {
    error('Minimal harus ada satu rekening bank yang tersimpan!');
    return;
  }
  const deleted = bankAccountsList.value.splice(idx, 1)[0];
  if (deleted.is_primary && bankAccountsList.value.length > 0) {
    bankAccountsList.value[0].is_primary = true;
  }
  success(`Rekening ${deleted.bank_name} (${deleted.account_number}) berhasil dihapus.`);
}

function copyAccountText(text) {
  if (!text) return;
  navigator.clipboard.writeText(text).then(() => {
    success(`Nomor rekening ${text} berhasil disalin ke clipboard!`);
  }).catch(() => {
    // fallback
  });
}

function savePaymentSettings() {
  isSavingPayment.value = true;
  const payload = {
    ...paymentForm.value,
    bank_accounts: bankAccountsList.value,
  };
  router.post('/admin/biaya/payment', payload, {
    preserveScroll: true,
    onSuccess: () => {
      isSavingPayment.value = false;
      success('Daftar nomor rekening bank dan petunjuk pembayaran berhasil disimpan!');
    },
    onError: (errs) => {
      isSavingPayment.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan rekening pembayaran.');
    }
  });
}

// 5. Calculator Simulation State
const isCalculatorModalOpen = ref(false);
const calcPackageType = ref('allin'); // 'allin' | 'satuan'
const calcSelectedCompIds = ref([]);

function openCalculatorModal() {
  calcPackageType.value = 'allin';
  calcSelectedCompIds.value = props.competitions.map(c => c.id);
  isCalculatorModalOpen.value = true;
}

const simulatedTotalFee = computed(() => {
  if (calcPackageType.value === 'allin') {
    return Number(packageForm.value.package_allin_price || 200000);
  }
  let sum = 0;
  for (const id of calcSelectedCompIds.value) {
    const found = props.competitions.find(c => c.id === id);
    if (found) sum += Number(found.fee || 50000);
  }
  return sum;
});

// Formatters
function formatRupiah(val) {
  if (val === null || val === undefined || isNaN(val)) return '0';
  return Number(val).toLocaleString('id-ID');
}

function getCompIconClass(comp) {
  if (!comp) return 'ri-trophy-line';
  const id = String(comp.id || '').toLowerCase();
  const title = String(comp.title || '').toLowerCase();
  const category = String(comp.category || '').toLowerCase();

  if (id.includes('voli') || title.includes('voli') || title.includes('volley')) return 'ri-football-line';
  if (id.includes('ml') || id.includes('esport') || title.includes('mobile legends') || title.includes('e-sport')) return 'ri-gamepad-line';
  if (id.includes('mipa') || title.includes('mipa') || title.includes('sains')) return 'ri-microscope-line';
  if (id.includes('ips') || title.includes('ips') || title.includes('sosial')) return 'ri-earth-line';
  if (id.includes('story') || title.includes('story') || title.includes('pidato')) return 'ri-mic-line';
  if (id.includes('banjari') || title.includes('banjari') || title.includes('hadrah')) return 'ri-music-2-line';
  if (id.includes('singer') || title.includes('singer') || title.includes('nyanyi') || title.includes('lagu')) return 'ri-mv-line';
  if (id.includes('tahfidz') || title.includes('tahfidz') || title.includes('quran') || title.includes('mtq')) return 'ri-book-open-line';
  if (category === 'akademik') return 'ri-flask-line';
  if (category === 'olahraga') return 'ri-medal-line';
  if (category === 'seni') return 'ri-palette-line';
  return 'ri-trophy-line';
}

function getCategoryBadgeClass(category) {
  const c = String(category || '').toLowerCase();
  if (c.includes('akademik') || c.includes('sains')) return 'bg-primary-subtle text-primary';
  if (c.includes('olahraga') || c.includes('voli')) return 'bg-success-subtle text-success';
  if (c.includes('seni') || c.includes('singer') || c.includes('banjari')) return 'bg-warning-subtle text-warning';
  if (c.includes('keagamaan') || c.includes('tahfidz')) return 'bg-info-subtle text-info';
  return 'bg-secondary-subtle text-secondary';
}
</script>

<style scoped>
.btn-xs {
  padding: 2px 8px;
  font-size: 11px;
}

.cursor-pointer {
  cursor: pointer;
}

.table th {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}
</style>
