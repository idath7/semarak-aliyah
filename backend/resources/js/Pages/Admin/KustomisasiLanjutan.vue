<template>
  <Head title="Kustomisasi Lanjutan Web Publik - Admin Panel" />

  <KustomisasiLayout
    active-menu="umum"
    page-title="Kustomisasi Lanjutan Web Publik"
    page-description="Atur banner pengumuman mengambang, pop-up modal selamat datang, tombol WhatsApp live helpdesk, countdown timer, serta injeksi custom CSS & scripts."
    page-badge="Efek & Interaksi Web"
  >
    <template #actions>
      <button 
        type="button" 
        class="btn btn-outline-danger d-flex align-items-center gap-1 shadow-sm"
        @click="openResetModal"
        :disabled="isResetting"
      >
        <i class="ri-refresh-line"></i> Reset Standar
      </button>
      <button 
        type="button" 
        class="btn btn-primary d-flex align-items-center gap-1 shadow-sm px-3"
        @click="saveCustomization"
        :disabled="isSaving"
      >
        <i class="ri-save-line"></i> {{ isSaving ? 'Menyimpan...' : 'Simpan Kustomisasi' }}
      </button>
    </template>

      <!-- Quick Summary Alert -->
      <div class="alert alert-primary-subtle border border-primary-subtle d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar-sm flex-shrink-0">
            <span class="avatar-title bg-primary text-white rounded-circle fs-20">
              <i class="ri-magic-line"></i>
            </span>
          </div>
          <div>
            <h5 class="fs-14 fw-bold text-primary mb-1">Pusat Kendali Pengalaman Visual & Interaksi Publik</h5>
            <p class="fs-12 text-muted mb-0">
              Semua pengaturan di bawah ini diterapkan secara langsung pada halaman portal depan (Web Publik) tanpa perlu menyunting file kode template secara manual.
            </p>
          </div>
        </div>
        <div class="d-none d-md-flex gap-2">
          <span class="badge" :class="form.announcement_banner_active ? 'bg-success' : 'bg-secondary'">
            Banner: {{ form.announcement_banner_active ? 'Aktif' : 'Off' }}
          </span>
          <span class="badge" :class="form.announcement_popup_active ? 'bg-warning' : 'bg-secondary'">
            Pop-up: {{ form.announcement_popup_active ? 'Aktif' : 'Off' }}
          </span>
          <span class="badge" :class="form.floating_wa_active ? 'bg-success' : 'bg-secondary'">
            WA Helpdesk: {{ form.floating_wa_active ? 'Aktif' : 'Off' }}
          </span>
          <span class="badge" :class="form.countdown_active ? 'bg-info' : 'bg-secondary'">
            Countdown: {{ form.countdown_active ? 'Aktif' : 'Off' }}
          </span>
        </div>
      </div>

      <!-- Main Layout: Form Tabs (Col-lg-7) + Live Preview (Col-lg-5) -->
      <div class="row g-4">
        <!-- LEFT: FORM CONTROLS -->
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <!-- Tabs Navigation -->
            <div class="card-header bg-white border-bottom p-0">
              <ul class="nav nav-tabs nav-tabs-custom card-header-tabs border-bottom-0 mx-3 flex-wrap" role="tablist">
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'banner' }"
                    @click="activeTab = 'banner'"
                  >
                    <i class="ri-notification-3-line me-1 text-warning"></i>
                    Banner Info
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'popup' }"
                    @click="activeTab = 'popup'"
                  >
                    <i class="ri-window-line me-1 text-info"></i>
                    Pop-up Modal
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'wa' }"
                    @click="activeTab = 'wa'"
                  >
                    <i class="ri-whatsapp-line me-1 text-success"></i>
                    Floating WA
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'countdown' }"
                    @click="activeTab = 'countdown'"
                  >
                    <i class="ri-time-line me-1 text-primary"></i>
                    Countdown
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'visual' }"
                    @click="activeTab = 'visual'"
                  >
                    <i class="ri-sparkling-fill me-1 text-danger"></i>
                    Efek Visual
                  </a>
                </li>
                <li class="nav-item">
                  <a 
                    class="nav-link py-3 fw-semibold cursor-pointer" 
                    :class="{ 'active': activeTab === 'code' }"
                    @click="activeTab = 'code'"
                  >
                    <i class="ri-code-s-slash-line me-1 text-secondary"></i>
                    Custom Code
                  </a>
                </li>
              </ul>
            </div>

            <div class="card-body p-4">
              <!-- ========================================================== -->
              <!-- TAB 1: BANNER PENGUMUMAN MENGAMBANG                         -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'banner'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 text-dark">Floating Announcement Ribbon</h5>
                    <p class="fs-12 text-muted mb-0">Baris pita pengumuman menonjol di bagian paling atas atau bawah halaman web.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="bannerActiveSwitch"
                      v-model="form.announcement_banner_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="bannerActiveSwitch">
                      {{ form.announcement_banner_active ? 'Banner Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Posisi Banner -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Posisi Penempatan Banner</label>
                    <CustomSelect v-model="form.announcement_banner_position" size="md">
                      <option value="top">Di Bagian Paling Atas (Top of Header)</option>
                      <option value="bottom">Mengambang di Bawah Layar (Sticky Bottom)</option>
                    </CustomSelect>
                  </div>

                  <!-- Tipe Tema Warna -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Tema & Gaya Visual</label>
                    <CustomSelect v-model="form.announcement_banner_type" size="md">
                      <option value="gold">✨ Gold Glow (Mewah Emas Semarak)</option>
                      <option value="primary">🟣 Violet Glow (Modern Ungu)</option>
                      <option value="success">🟢 Emerald Green (Hijau Prestasi)</option>
                      <option value="danger">🔴 Crimson Red (Penting / Mendesak)</option>
                      <option value="info">🔵 Ocean Cyan (Informasi Standar)</option>
                    </CustomSelect>
                  </div>

                  <!-- Teks Pesan Pengumuman -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Pesan Pengumuman</label>
                    <textarea 
                      class="form-control" 
                      rows="2" 
                      v-model="form.announcement_banner_text"
                      placeholder="Tuliskan pesan pengumuman singkat & menarik..."
                    ></textarea>
                    <small class="text-muted fs-11">Mendukung emoji untuk menarik perhatian pengunjung.</small>
                  </div>

                  <!-- Tombol Aksi CTA -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Tombol Aksi (CTA)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_banner_btn_text" 
                      placeholder="Contoh: Daftar Kontingen Sekarang"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Tautan Tujuan Tombol (URL / Anchor)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_banner_btn_url" 
                      placeholder="Contoh: /#biaya atau /layanan-registrasi"
                    />
                  </div>

                  <!-- Opsi Tombol Tutup (Dismissable) -->
                  <div class="col-12">
                    <div class="form-check form-switch">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        id="bannerDismissableSwitch"
                        v-model="form.announcement_banner_dismissable"
                      />
                      <label class="form-check-label fs-13" for="bannerDismissableSwitch">
                        Izinkan pengunjung menutup banner (Tombol Silang [X] yang mengingat sesi penutupan)
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 2: POP-UP MODAL PENGUMUMAN OTOMATIS                     -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'popup'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 text-dark">Pop-up Entrance Modal</h5>
                    <p class="fs-12 text-muted mb-0">Modal interaktif yang otomatis muncul saat pengunjung pertama kali membuka web.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="popupActiveSwitch"
                      v-model="form.announcement_popup_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="popupActiveSwitch">
                      {{ form.announcement_popup_active ? 'Pop-up Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Frekuensi Kemunculan -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Frekuensi Tampil ke Pengunjung</label>
                    <CustomSelect v-model="form.announcement_popup_frequency" size="md">
                      <option value="once_per_session">Sekali per sesi peramban (Rekomendasi agar ramah pengguna)</option>
                      <option value="always">Selalu muncul setiap halaman dimuat ulang</option>
                    </CustomSelect>
                  </div>

                  <!-- Label Badge Pop-up -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Label Kategori / Badge</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_popup_badge" 
                      placeholder="Contoh: Informasi Resmi Panitia"
                    />
                  </div>

                  <!-- Judul Pop-up -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Judul Pengumuman</label>
                    <input 
                      type="text" 
                      class="form-control fw-semibold" 
                      v-model="form.announcement_popup_title" 
                      placeholder="Contoh: Pengumuman Penting: Technical Meeting & Petunjuk Teknis"
                    />
                  </div>

                  <!-- Gambar Banner Pop-up -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">URL Gambar Poster / Banner Pop-up</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light"><i class="ri-image-line"></i></span>
                      <input 
                        type="text" 
                        class="form-control" 
                        v-model="form.announcement_popup_image" 
                        placeholder="/assets/img/pamflet.jpg atau URL gambar https://..."
                      />
                    </div>
                    <small class="text-muted fs-11">Kosongkan jika hanya ingin menampilkan pop-up berbasis teks murni.</small>
                  </div>

                  <!-- Konten Pengumuman -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Isi Pesan Pengumuman</label>
                    <textarea 
                      class="form-control" 
                      rows="4" 
                      v-model="form.announcement_popup_content"
                      placeholder="Tuliskan isi pengumuman lengkap di sini..."
                    ></textarea>
                  </div>

                  <!-- Tombol Aksi Pop-up -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Tombol Aksi Utama</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_popup_btn_text" 
                      placeholder="Contoh: Unduh Juknis Lengkap PDF"
                    />
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Tautan Tombol Aksi</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.announcement_popup_btn_url" 
                      placeholder="Contoh: /assets/doc/JUKNIS-SEMARAK-2026.pdf"
                    />
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 3: HELPDESK WHATSAPP MENGAMBANG (FLOATING WA)           -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'wa'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 text-dark">Floating WhatsApp Helpdesk Button</h5>
                    <p class="fs-12 text-muted mb-0">Tombol obrolan instan cepat ke panitia di sudut layar.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="floatingWaActiveSwitch"
                      v-model="form.floating_wa_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="floatingWaActiveSwitch">
                      {{ form.floating_wa_active ? 'Tombol Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Nomor WhatsApp -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Nomor WhatsApp Panitia *</label>
                    <div class="input-group">
                      <span class="input-group-text bg-success-subtle text-success"><i class="ri-whatsapp-fill"></i></span>
                      <input 
                        type="text" 
                        class="form-control fw-bold font-monospace" 
                        v-model="form.floating_wa_number" 
                        placeholder="Contoh: 0838 5007 0767"
                        required
                      />
                    </div>
                  </div>

                  <!-- Label Samping / Tooltip -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Label Teks Tombol / Tooltip</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.floating_wa_label" 
                      placeholder="Contoh: Bantuan Panitia"
                    />
                  </div>

                  <!-- Posisi Tombol -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Posisi Tombol di Layar</label>
                    <CustomSelect v-model="form.floating_wa_position" size="md">
                      <option value="bottom-right">Sudut Kanan Bawah (Standar Web)</option>
                      <option value="bottom-left">Sudut Kiri Bawah</option>
                    </CustomSelect>
                  </div>

                  <!-- Animasi Tombol -->
                  <div class="col-md-6">
                    <label class="form-label fs-12 fw-semibold text-muted">Efek Animasi Perhatian</label>
                    <CustomSelect v-model="form.floating_wa_animation" size="md">
                      <option value="pulse">Pulse Radar Gelombang (Halus & Elegan)</option>
                      <option value="bounce">Bounce Melompat Halus</option>
                      <option value="none">Statis (Tanpa Animasi)</option>
                    </CustomSelect>
                  </div>

                  <!-- Pesan Pembuka Otomatis -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Template Pesan Otomatis (Pre-filled Text)</label>
                    <textarea 
                      class="form-control" 
                      rows="3" 
                      v-model="form.floating_wa_message"
                      placeholder="Ketik teks sapaan otomatis saat pengunjung mengklik tombol WA..."
                    ></textarea>
                    <small class="text-muted fs-11">Pesan ini akan langsung terisi di kolom chat WhatsApp pengunjung.</small>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 4: COUNTDOWN TIMER ACARA                                -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'countdown'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 text-dark">Countdown Timer Hero Section</h5>
                    <p class="fs-12 text-muted mb-0">Hitung mundur batas akhir pendaftaran dan Technical Meeting di bagian depan beranda.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="countdownActiveSwitch"
                      v-model="form.countdown_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="countdownActiveSwitch">
                      {{ form.countdown_active ? 'Countdown Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Label Judul Countdown -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Judul / Keterangan Countdown</label>
                    <input 
                      type="text" 
                      class="form-control fw-semibold" 
                      v-model="form.countdown_title" 
                      placeholder="Contoh: Batas Akhir Pendaftaran & TM Ditutup Dalam:"
                    />
                  </div>

                  <!-- Target Tanggal & Jam -->
                  <div class="col-md-7">
                    <label class="form-label fs-12 fw-semibold text-muted">Target Waktu Hitung Mundur (ISO / DateTime)</label>
                    <input 
                      type="datetime-local" 
                      class="form-control fw-bold" 
                      v-model="form.countdown_target_date"
                    />
                    <small class="text-muted fs-11">Waktu acuan zona WIB (Waktu Indonesia Barat).</small>
                  </div>

                  <!-- Quick Presets -->
                  <div class="col-md-5">
                    <label class="form-label fs-12 fw-semibold text-muted">Preset Cepat Waktu:</label>
                    <div class="d-flex gap-1 flex-wrap">
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="setCountdownPreset(7)"
                      >
                        +7 Hari
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="setCountdownPreset(14)"
                      >
                        +14 Hari
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-outline-primary"
                        @click="setCountdownPreset(30)"
                      >
                        +30 Hari
                      </button>
                      <button 
                        type="button" 
                        class="btn btn-xs btn-soft-secondary"
                        @click="form.countdown_target_date = '2026-10-24T23:59'"
                      >
                        24 Okt 2026
                      </button>
                    </div>
                  </div>

                  <!-- Teks Saat Waktu Habis -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Teks Ketika Waktu Habis (Ended State)</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="form.countdown_ended_text" 
                      placeholder="Contoh: Pendaftaran Gelombang Utama Resmi Ditutup!"
                    />
                  </div>

                  <!-- Tampilkan Detik -->
                  <div class="col-12">
                    <div class="form-check form-switch">
                      <input 
                        class="form-check-input cursor-pointer" 
                        type="checkbox" 
                        id="showSecondsSwitch"
                        v-model="form.countdown_show_seconds"
                      />
                      <label class="form-check-label fs-13" for="showSecondsSwitch">
                        Tampilkan kotak hitung mundur satuan detik (Hari, Jam, Menit, Detik)
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 5: EFEK VISUAL & INTERAKSI WEB                          -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'visual'">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                  <div>
                    <h5 class="fs-14 fw-bold mb-1 text-dark">Efek Visual & Interaksi Website</h5>
                    <p class="fs-12 text-muted mb-0">Partikel atmosfer kemeriahan acara dan tombol kenyamanan navigasi.</p>
                  </div>
                  <div class="form-check form-switch form-switch-md mb-0">
                    <input 
                      class="form-check-input cursor-pointer" 
                      type="checkbox" 
                      id="visualParticlesActiveSwitch"
                      v-model="form.visual_particles_active"
                    />
                    <label class="form-check-label fs-12 fw-semibold" for="visualParticlesActiveSwitch">
                      {{ form.visual_particles_active ? 'Efek Partikel Aktif' : 'Nonaktif' }}
                    </label>
                  </div>
                </div>

                <div class="row g-3">
                  <!-- Pilihan Tipe Partikel -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted">Pilih Tema Partikel Latar Belakang</label>
                    <div class="row g-3">
                      <div class="col-md-4">
                        <label 
                          class="card border p-3 cursor-pointer h-100 text-center mb-0"
                          :class="{ 'border-warning bg-warning-subtle text-warning-emphasis': form.visual_particles_type === 'glitter' }"
                        >
                          <input type="radio" value="glitter" v-model="form.visual_particles_type" class="d-none">
                          <i class="ri-sparkling-fill fs-24 mb-2 d-block"></i>
                          <div class="fw-bold fs-13">Gold Glitter</div>
                          <small class="text-muted fs-11">Kilau bintang emas mewah bertema Semarak</small>
                        </label>
                      </div>

                      <div class="col-md-4">
                        <label 
                          class="card border p-3 cursor-pointer h-100 text-center mb-0"
                          :class="{ 'border-primary bg-primary-subtle text-primary-emphasis': form.visual_particles_type === 'confetti' }"
                        >
                          <input type="radio" value="confetti" v-model="form.visual_particles_type" class="d-none">
                          <i class="ri-gift-line fs-24 mb-2 d-block"></i>
                          <div class="fw-bold fs-13">Festival Confetti</div>
                          <small class="text-muted fs-11">Taburan pita warna-warni pesta perayaan</small>
                        </label>
                      </div>

                      <div class="col-md-4">
                        <label 
                          class="card border p-3 cursor-pointer h-100 text-center mb-0"
                          :class="{ 'border-info bg-info-subtle text-info-emphasis': form.visual_particles_type === 'snow' }"
                        >
                          <input type="radio" value="snow" v-model="form.visual_particles_type" class="d-none">
                          <i class="ri-snowflake-line fs-24 mb-2 d-block"></i>
                          <div class="fw-bold fs-13">Soft Glow Snow</div>
                          <small class="text-muted fs-11">Pendaran partikel lembut mengambang tenang</small>
                        </label>
                      </div>
                    </div>
                  </div>

                  <!-- Back To Top Button Toggle -->
                  <div class="col-12 mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <div class="fw-bold fs-13 text-dark">Tombol Kembali ke Atas (Back to Top)</div>
                        <small class="text-muted fs-12">Menampilkan tombol bulat dengan indikator progres scroll melingkar di sudut kanan bawah.</small>
                      </div>
                      <div class="form-check form-switch form-switch-md mb-0">
                        <input 
                          class="form-check-input cursor-pointer" 
                          type="checkbox" 
                          id="backToTopSwitch"
                          v-model="form.back_to_top_active"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================== -->
              <!-- TAB 6: INJEKSI KODE KUSTOM (CUSTOM CSS & JS)                -->
              <!-- ========================================================== -->
              <div v-show="activeTab === 'code'">
                <div class="mb-3 pb-2 border-bottom">
                  <h5 class="fs-14 fw-bold mb-1 text-dark">Injeksi Kode Kustom (CSS & Scripting)</h5>
                  <p class="fs-12 text-muted mb-0">Tambahkan aturan styling CSS kustom, tag analitik Google/Meta, atau skrip widget tanpa merusak struktur file asli.</p>
                </div>

                <div class="alert alert-warning-subtle border border-warning-subtle p-2 fs-12 mb-3">
                  <i class="ri-alert-line me-1"></i>
                  <strong>Perhatian:</strong> Pastikan kode JavaScript atau CSS yang Anda masukkan valid dan aman. Kode hanya dieksekusi pada website publik.
                </div>

                <div class="row g-3">
                  <!-- Custom CSS -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted d-flex align-items-center justify-content-between">
                      <span>Custom CSS Stylesheet (Otomatis dibungkus tag &lt;style&gt;)</span>
                      <span class="badge bg-secondary-subtle text-secondary font-monospace">CSS</span>
                    </label>
                    <textarea 
                      class="form-control font-monospace fs-12 text-dark bg-light" 
                      rows="5" 
                      v-model="form.custom_css"
                      placeholder="/* Contoh kustomisasi CSS */&#10;.brand-title { letter-spacing: 2px; }&#10;.btn-primary { border-radius: 50px; }"
                      spellcheck="false"
                    ></textarea>
                  </div>

                  <!-- Header Script Injection -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted d-flex align-items-center justify-content-between">
                      <span>Injeksi Script Header (&lt;head&gt; — Google Analytics / Meta Pixel)</span>
                      <span class="badge bg-primary-subtle text-primary font-monospace">&lt;head&gt;</span>
                    </label>
                    <textarea 
                      class="form-control font-monospace fs-12 text-dark bg-light" 
                      rows="4" 
                      v-model="form.custom_head_script"
                      placeholder="<!-- Masukkan tag tracking Google Analytics GA4 / Google Tag Manager di sini -->&#10;<script async src='https://www.googletagmanager.com/gtag/js?id=G-XXXXX'></script>"
                      spellcheck="false"
                    ></textarea>
                  </div>

                  <!-- Footer / Body Script Injection -->
                  <div class="col-12">
                    <label class="form-label fs-12 fw-semibold text-muted d-flex align-items-center justify-content-between">
                      <span>Injeksi Script Footer (Sebelum &lt;/body&gt; — Chat Widget / Third-Party)</span>
                      <span class="badge bg-success-subtle text-success font-monospace">&lt;body&gt;</span>
                    </label>
                    <textarea 
                      class="form-control font-monospace fs-12 text-dark bg-light" 
                      rows="4" 
                      v-model="form.custom_body_script"
                      placeholder="<!-- Masukkan skrip widget pihak ketiga seperti Tawk.to, Crisp, dsb -->&#10;<script>/* custom code */</script>"
                      spellcheck="false"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Footer Submit -->
            <div class="card-footer bg-light py-3 d-flex align-items-center justify-content-between">
              <span class="text-muted fs-12">
                <i class="ri-shield-check-line text-success me-1"></i> Data disimpan aman di database setting sistem.
              </span>
              <button 
                type="button" 
                class="btn btn-primary px-4 shadow-sm"
                @click="saveCustomization"
                :disabled="isSaving"
              >
                <i class="ri-save-line me-1"></i>
                {{ isSaving ? 'Menyimpan...' : 'Simpan Kustomisasi Lanjutan' }}
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT: INTERACTIVE LIVE PREVIEW PANEL -->
        <div class="col-lg-5">
          <div class="card border-0 shadow-sm sticky-top" style="top: 85px;">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between border-bottom">
              <div class="d-flex align-items-center gap-2">
                <i class="ri-eye-line text-primary fs-16"></i>
                <h5 class="card-title mb-0 fs-13 fw-bold text-dark">Pratinjau Langsung (Live Preview)</h5>
              </div>
              <span class="badge bg-light text-muted border fs-10">Real-time</span>
            </div>

            <div class="card-body p-4 bg-light-subtle">
              <!-- Banner Preview Box -->
              <div v-if="activeTab === 'banner'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Banner Mengambang:</div>
                <div 
                  v-if="form.announcement_banner_active" 
                  class="p-3 rounded shadow-sm text-center position-relative overflow-hidden"
                  :class="getBannerClass(form.announcement_banner_type)"
                >
                  <div class="d-flex flex-wrap align-items-center justify-content-center gap-2 fs-12 fw-semibold">
                    <span>{{ form.announcement_banner_text || '📢 Teks pengumuman akan tampil di sini...' }}</span>
                    <span 
                      v-if="form.announcement_banner_btn_text" 
                      class="badge bg-white text-dark shadow-sm px-2 py-1 cursor-pointer"
                    >
                      {{ form.announcement_banner_btn_text }} &rarr;
                    </span>
                  </div>
                  <button 
                    v-if="form.announcement_banner_dismissable" 
                    type="button" 
                    class="btn-close btn-close-white position-absolute top-50 end-0 translate-middle-y me-2"
                    style="font-size: 8px;"
                    title="Simulasi Tutup"
                  ></button>
                </div>
                <div v-else class="text-center py-4 bg-white rounded border text-muted fs-12">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Banner saat ini dalam status Nonaktif
                </div>
              </div>

              <!-- Pop-up Preview Box -->
              <div v-else-if="activeTab === 'popup'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Pop-up Pengumuman:</div>
                <div v-if="form.announcement_popup_active" class="bg-white rounded-3 shadow border overflow-hidden">
                  <div v-if="form.announcement_popup_image" class="w-100 bg-dark text-center" style="max-height: 140px; overflow: hidden;">
                    <img :src="form.announcement_popup_image" alt="Poster" class="img-fluid" style="object-fit: cover; width: 100%; height: 140px;" @error="previewImageError = true">
                  </div>
                  <div class="p-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis fs-10 mb-2">
                      {{ form.announcement_popup_badge || 'Pengumuman Resmi' }}
                    </span>
                    <h6 class="fw-bold fs-13 text-dark mb-2">
                      {{ form.announcement_popup_title || 'Judul Pengumuman Pop-up' }}
                    </h6>
                    <p class="fs-12 text-muted mb-3 line-clamp-3" style="max-height: 70px; overflow-y: auto;">
                      {{ form.announcement_popup_content || 'Isi teks pengumuman yang akan dibaca oleh pengunjung website...' }}
                    </p>
                    <div class="d-flex gap-2 justify-content-end">
                      <span class="btn btn-sm btn-light fs-11">Tutup</span>
                      <span class="btn btn-sm btn-primary fs-11">
                        {{ form.announcement_popup_btn_text || 'Buka Tautan' }}
                      </span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-4 bg-white rounded border text-muted fs-12">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Pop-up modal saat ini dalam status Nonaktif
                </div>
              </div>

              <!-- Floating WA Preview Box -->
              <div v-else-if="activeTab === 'wa'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Tombol WhatsApp:</div>
                <div class="bg-white p-4 rounded border shadow-sm position-relative text-center" style="min-height: 200px;">
                  <span class="text-muted fs-12 d-block mb-3">Tampilan di pojok browser publik:</span>
                  
                  <div 
                    v-if="form.floating_wa_active"
                    class="d-inline-flex align-items-center gap-2 p-2 px-3 rounded-pill shadow-lg text-white"
                    :class="[
                      form.floating_wa_animation === 'pulse' ? 'anim-pulse' : 
                      form.floating_wa_animation === 'bounce' ? 'anim-bounce' : ''
                    ]"
                    style="background: #22c55e;"
                  >
                    <i class="ri-whatsapp-fill fs-22"></i>
                    <span class="fs-12 fw-bold">{{ form.floating_wa_label || 'Bantuan Panitia' }}</span>
                  </div>
                  <div v-else class="text-muted fs-12 py-3">
                    <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                    Tombol mengambang WA dinonaktifkan
                  </div>

                  <div class="mt-4 pt-3 border-top text-start fs-11 text-muted">
                    <div>Target WA: <strong>{{ form.floating_wa_number || '-' }}</strong></div>
                    <div>Posisi: <strong>{{ form.floating_wa_position === 'bottom-right' ? 'Kanan Bawah' : 'Kiri Bawah' }}</strong></div>
                  </div>
                </div>
              </div>

              <!-- Countdown Preview Box -->
              <div v-else-if="activeTab === 'countdown'" class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Simulasi Tampilan Hitung Mundur:</div>
                <div v-if="form.countdown_active" class="p-3 rounded-3 text-white shadow" style="background: linear-gradient(135deg, #1e1b4b, #312e81);">
                  <div class="d-flex align-items-center gap-1 text-warning fs-11 fw-bold mb-2">
                    <i class="ri-time-line"></i>
                    <span>{{ form.countdown_title || 'Batas Pendaftaran Ditutup Dalam:' }}</span>
                  </div>

                  <div class="d-flex gap-2 justify-content-center my-2">
                    <div class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">05</div>
                      <div class="fs-10 text-white-50">Hari</div>
                    </div>
                    <div class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">14</div>
                      <div class="fs-10 text-white-50">Jam</div>
                    </div>
                    <div class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">32</div>
                      <div class="fs-10 text-white-50">Menit</div>
                    </div>
                    <div v-if="form.countdown_show_seconds" class="bg-white bg-opacity-10 p-2 rounded text-center" style="min-width: 55px;">
                      <div class="fs-18 fw-bold font-monospace text-warning">48</div>
                      <div class="fs-10 text-white-50">Detik</div>
                    </div>
                  </div>

                  <div class="text-center text-white-50 fs-10 mt-2">
                    Target: {{ form.countdown_target_date || '2026-10-24T23:59' }}
                  </div>
                </div>
                <div v-else class="text-center py-4 bg-white rounded border text-muted fs-12">
                  <i class="ri-eye-off-line fs-20 d-block mb-1"></i>
                  Countdown timer dinonaktifkan
                </div>
              </div>

              <!-- Visual / Code Preview Box -->
              <div v-else class="preview-box">
                <div class="text-muted fs-11 fw-bold text-uppercase mb-2">Informasi Penerapan:</div>
                <div class="bg-white p-3 rounded border fs-12">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-checkbox-circle-fill text-success fs-16"></i>
                    <span class="fw-semibold text-dark">Partikel Efek:</span>
                    <span class="badge bg-primary-subtle text-primary">{{ form.visual_particles_active ? form.visual_particles_type : 'Mati' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-checkbox-circle-fill text-success fs-16"></i>
                    <span class="fw-semibold text-dark">Back To Top:</span>
                    <span class="badge" :class="form.back_to_top_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">{{ form.back_to_top_active ? 'Aktif' : 'Mati' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="ri-code-line text-info fs-16"></i>
                    <span class="fw-semibold text-dark">Custom CSS:</span>
                    <span class="badge bg-light text-muted border">{{ form.custom_css ? form.custom_css.length + ' karakter' : 'Kosong' }}</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <i class="ri-javascript-line text-warning fs-16"></i>
                    <span class="fw-semibold text-dark">Script Eksternal:</span>
                    <span class="badge bg-light text-muted border">{{ (form.custom_head_script || form.custom_body_script) ? 'Terpasang' : 'Kosong' }}</span>
                  </div>
                </div>
              </div>

              <!-- Quick Tips -->
              <div class="mt-4 p-3 rounded bg-white border border-light-subtle">
                <div class="fw-bold fs-12 text-dark mb-1 d-flex align-items-center gap-1">
                  <i class="ri-lightbulb-line text-warning"></i> Tips Kustomisasi:
                </div>
                <p class="fs-11 text-muted mb-0">
                  Gunakan banner untuk mengumumkan pengumuman kuota lomba yang hampir habis. Gunakan pop-up modal menjelang hari H Technical Meeting agar seluruh delegasi sekolah mendapatkan info terkini.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- RESET CONFIRMATION MODAL -->
    <div v-if="isResetModalOpen" class="modal-backdrop fade show"></div>
    <div 
      v-if="isResetModalOpen" 
      class="modal fade show d-block" 
      tabindex="-1" 
      role="dialog"
      aria-modal="true"
    >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-danger-subtle py-3">
            <h5 class="modal-title fw-bold fs-15 text-danger d-flex align-items-center gap-2">
              <i class="ri-error-warning-line"></i>
              Konfirmasi Reset Standar Awal
            </h5>
            <button type="button" class="btn-close" @click="isResetModalOpen = false"></button>
          </div>
          <div class="modal-body p-4 text-center">
            <i class="ri-refresh-line fs-48 text-warning d-block mb-3"></i>
            <h5 class="fw-bold fs-16 mb-2">Kembalikan Semua Pengaturan ke Standar?</h5>
            <p class="text-muted fs-13 mb-0">
              Tindakan ini akan mengembalikan teks banner, pop-up, nomor WA, countdown, dan kode kustom ke pengaturan awal bawaan Semarak Aliyah XIV.
            </p>
          </div>
          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-light btn-sm" @click="isResetModalOpen = false">Batal</button>
            <button 
              type="button" 
              class="btn btn-danger btn-sm px-4" 
              :disabled="isResetting"
              @click="submitReset"
            >
              {{ isResetting ? 'Mereset...' : 'Ya, Kembalikan ke Standar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </KustomisasiLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import KustomisasiLayout from '@/Layouts/KustomisasiLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({})
  }
});

const { success, error } = useToast();

// Active Tab: 'banner' | 'popup' | 'wa' | 'countdown' | 'visual' | 'code'
const activeTab = ref('banner');

// Form state
const form = ref({
  announcement_banner_active: props.settings.announcement_banner_active !== false,
  announcement_banner_text: props.settings.announcement_banner_text || '',
  announcement_banner_type: props.settings.announcement_banner_type || 'gold',
  announcement_banner_btn_text: props.settings.announcement_banner_btn_text || '',
  announcement_banner_btn_url: props.settings.announcement_banner_btn_url || '',
  announcement_banner_position: props.settings.announcement_banner_position || 'top',
  announcement_banner_dismissable: props.settings.announcement_banner_dismissable !== false,

  announcement_popup_active: props.settings.announcement_popup_active === true,
  announcement_popup_frequency: props.settings.announcement_popup_frequency || 'once_per_session',
  announcement_popup_title: props.settings.announcement_popup_title || '',
  announcement_popup_badge: props.settings.announcement_popup_badge || '',
  announcement_popup_image: props.settings.announcement_popup_image || '',
  announcement_popup_content: props.settings.announcement_popup_content || '',
  announcement_popup_btn_text: props.settings.announcement_popup_btn_text || '',
  announcement_popup_btn_url: props.settings.announcement_popup_btn_url || '',

  floating_wa_active: props.settings.floating_wa_active !== false,
  floating_wa_number: props.settings.floating_wa_number || '0838 5007 0767',
  floating_wa_message: props.settings.floating_wa_message || '',
  floating_wa_label: props.settings.floating_wa_label || 'Bantuan Panitia',
  floating_wa_position: props.settings.floating_wa_position || 'bottom-right',
  floating_wa_animation: props.settings.floating_wa_animation || 'pulse',

  countdown_active: props.settings.countdown_active !== false,
  countdown_title: props.settings.countdown_title || 'Batas Akhir Pendaftaran & TM Ditutup Dalam:',
  countdown_target_date: props.settings.countdown_target_date || '2026-10-24T23:59',
  countdown_ended_text: props.settings.countdown_ended_text || 'Pendaftaran Resmi Ditutup!',
  countdown_show_seconds: props.settings.countdown_show_seconds !== false,

  custom_css: props.settings.custom_css || '',
  custom_head_script: props.settings.custom_head_script || '',
  custom_body_script: props.settings.custom_body_script || '',

  visual_particles_active: props.settings.visual_particles_active !== false,
  visual_particles_type: props.settings.visual_particles_type || 'glitter',
  back_to_top_active: props.settings.back_to_top_active !== false,
});

const isSaving = ref(false);
const isResetModalOpen = ref(false);
const isResetting = ref(false);
const previewImageError = ref(false);

function setCountdownPreset(daysAhead) {
  const date = new Date();
  date.setDate(date.getDate() + daysAhead);
  date.setHours(23, 59, 0, 0);
  
  const pad = (n) => String(n).padStart(2, '0');
  const formatted = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
  form.value.countdown_target_date = formatted;
}

function getBannerClass(type) {
  switch (type) {
    case 'gold':
      return 'bg-gradient-gold text-dark';
    case 'primary':
      return 'bg-primary text-white';
    case 'success':
      return 'bg-success text-white';
    case 'danger':
      return 'bg-danger text-white';
    case 'info':
      return 'bg-info text-white';
    default:
      return 'bg-dark text-white';
  }
}

function saveCustomization() {
  isSaving.value = true;
  router.post('/admin/kustomisasi', form.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSaving.value = false;
      success('Pengaturan Kustomisasi Lanjutan berhasil disimpan!');
    },
    onError: (errs) => {
      isSaving.value = false;
      error(Object.values(errs)[0] || 'Gagal menyimpan pengaturan.');
    }
  });
}

function openResetModal() {
  isResetModalOpen.value = true;
}

function submitReset() {
  isResetting.value = true;
  router.post('/admin/kustomisasi/reset', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isResetting.value = false;
      isResetModalOpen.value = false;
      success('Kustomisasi lanjutan berhasil dikembalikan ke standar awal.');
    },
    onError: () => {
      isResetting.value = false;
      error('Gagal mereset kustomisasi.');
    }
  });
}
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}

.btn-xs {
  padding: 2px 8px;
  font-size: 11px;
}

.bg-gradient-gold {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
  color: #ffffff;
}

.anim-pulse {
  animation: pulseEffect 2s infinite;
}

@keyframes pulseEffect {
  0% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
  }
  70% {
    box-shadow: 0 0 0 14px rgba(34, 197, 94, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
  }
}

.anim-bounce {
  animation: bounceEffect 2s infinite;
}

@keyframes bounceEffect {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-8px);
  }
  60% {
    transform: translateY(-4px);
  }
}
</style>
