# SEMARAK ALIYAH XIV 2026 - BACKEND & CORE APPLICATION

Aplikasi inti dan sistem informasi manajemen **SEMARAK ALIYAH XIV 2026** di MA Unggulan Wahid Hasyim Balung, Jember. Dibangun dengan arsitektur modern **Laravel 11**, **Inertia.js v2**, **Vue 3**, **TailwindCSS v4**, serta didukung oleh runtime berkinerja tinggi **FrankenPHP Octane (PHP 8.4.26)**.

---

## 🏛️ 1. Arsitektur Teknologi

| Lapisan | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Backend Framework** | **Laravel 11.x** | PHP 8.4.26 dengan arsitektur modular, Eloquent ORM, dan Reverb Real-Time. |
| **Web Server & Runtime** | **FrankenPHP 1.x + Laravel Octane** | Server berbasis Caddy Engine dengan in-memory persistent workers, JIT compiler, dan dual-port native routing. |
| **Frontend SPA & UI** | **Inertia.js v2 + Vue 3 (Composition API)** | Single Page Application tanpa REST API overhead, reactive state, dan transisi mulus. |
| **Styling & Theme** | **TailwindCSS v4 + Themesbrand Velzon** | Portal admin berbasis Velzon Dashboard dengan mode Dark/Light dan responsif mobile. |
| **Database Engine** | **PostgreSQL 18.6** | Database performa tinggi dengan tuning khusus SSD/NVMe, autovacuum agresif, dan indeks komposit. |
| **Cache, Session & Queue**| **Redis Terbaru (Alpine)** | In-memory key-value store dengan eviction policy `allkeys-lru`. |
| **Real-time Engine** | **Laravel Reverb WebSocket** | Push data live untuk scoreboard, penjurian, dan antrean pendaftaran. |

---

## 🌐 2. Pemisahan Portal Dual-Port (Multi-Portal Separation)

Sistem memisahkan akses publik dan panel administrasi secara ketat melalui `PortalSeparationMiddleware`:

```
                             ┌───► :8000  Web Publik (Landing Page, Pendaftaran, Info Lomba)
  Client / Browser ──────────┼───► :8001  Portal Admin & Operasional (Dashboard, Penjurian, Cetak)
                             └───► :8085  WebSocket Gateway (Laravel Reverb)
```

- **Port 8000 - Web Publik**:
  - `http://localhost:8000/` : Beranda Publik, Sambutan, Countdown, & Timeline.
  - `http://localhost:8000/layanan-registrasi` : Formulir Pendaftaran Delegasi Lomba Dinamis.
  - `http://localhost:8000/scoreboard` : Live Scoreboard Panggung untuk Penonton & Peserta.
- **Port 8001 - Portal Admin & Operasional**:
  - `http://localhost:8001/login` : Autentikasi Panitia & Admin.
  - `http://localhost:8001/admin` : Dashboard Statistik Pendaftaran & Kuota Lomba.
  - `http://localhost:8001/admin/kustomisasi` : Kustomisasi Lanjutan Web Publik (Teks Berjalan, Biaya, Galeri, FAQ, SEO).
  - `http://localhost:8001/operasional` : Pusat Komando Operasional Hari-H.
  - `http://localhost:8001/admin/gate-checkin` : Gate Check-in & Scanner Barcode Delegasi.
  - `http://localhost:8001/admin/print-studio` : Studio Cetak ID Card dan Sertifikat/Piagam Massal.
  - `http://localhost:8001/admin/jury/arena/*` : Arena Penjurian dengan Rubrik & Stopwatch Terintegrasi.
  - `http://localhost:8001/admin/tournament/arena/*` : Arena Turnamen & Bagan Knockout Bracket.

---

## 🚀 3. Fitur Unggulan Sistem

### A. Dynamic Form Builder Cabang Lomba
- Setiap cabang lomba memiliki konfigurasi formulir khusus (custom fields: NISN, Pas Foto, Surat Rekomendasi, Bukti Pembayaran, dll).
- Validasi usia peserta dan kuota maksimal tim secara otomatis.
- Unggah berkas berkas lampiran dengan sanitasi keamanan dan symlink otomatis.

### B. Pusat Kustomisasi Lanjutan
- **Kelola Teks Berjalan (Marquee)**: Mengatur pengumuman berjalan di bagian atas halaman publik secara realtime.
- **Biaya & Rekening Registrasi**: Mengatur nomor rekening tujuan transfer, nominal pendaftaran per cabang lomba, dan konfirmasi WhatsApp.
- **Mode Pemeliharaan (Maintenance Mode)**: Mengaktifkan halaman darurat dengan hitung mundur jika sistem sedang dalam perawatan.
- **Galeri Foto & Dokumentasi**: Manajemen foto kegiatan Semarak Aliyah terdahulu.
- **FAQ (Tanya Jawab)**: Pengelolaan pertanyaan yang sering diajukan calon peserta.
- **Profil Sekolah & Medsos**: Penyesuaian nama madrasah, alamat Google Maps, kontak panitia, dan tautan sosial media.
- **SEO & OpenGraph**: Pratinjau link WhatsApp/Facebook/Twitter lengkap dengan banner og:image dinamis.

### C. Manajemen Operasional Hari-H (Event Day Operations)
- **Gate Check-in Barcode**: Validasi kehadiran peserta di pintu masuk venue lomba menggunakan scanner barcode/QR.
- **Studio Cetak**: Cetak ID Card otomatis ber-barcode dan piagam penghargaan dengan format ukuran standar percetakan.
- **Arena Penjurian & Wasit**: Penilaian real-time oleh juri dengan rubrik berbobot nilai, sinkron langsung ke server via WebSocket.

---

## ⚡ 4. Menjalankan Aplikasi

### Opsi A: Menggunakan Docker (Rekomendasi Produksi)
Seluruh stack (FrankenPHP Octane, PostgreSQL 18.6, Redis, Node.js 22, Reverb) dapat dijalankan dalam satu perintah:

```bash
# Menjalankan seluruh kontainer di background
docker compose up -d

# Menjalankan dengan build image pertama kali
docker compose up -d --build
```

#### Pembaruan Satu-Klik (Hot-Update):
- **Windows**: Jalankan `docker-update.bat` di root direktori.
- **Linux/Server**: Jalankan `./docker-update.sh --quick`.
- **Artisan**: `docker compose exec frankenphp php artisan app:update`.

---

### Opsi B: Menjalankan Secara Native (Lokal Development)

#### 1. Prasyarat:
- PHP >= 8.4 (dengan ekstensi `pdo_pgsql`, `pdo_mysql`, `gd`, `imagick`, `pcntl`, `redis`).
- Composer >= 2.x.
- Node.js >= 20.x dan npm.
- PostgreSQL atau MariaDB/MySQL.

#### 2. Konfigurasi Environment:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi koneksi database di file `.env`:
```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=semarak_db
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

#### 3. Instalasi Dependency & Migrasi Database:
```bash
composer install
npm install
npm run build
php artisan migrate --seed
php artisan storage:link
```

#### 4. Menjalankan Server:
```bash
# Terminal 1: Web Publik (Port 8000)
php artisan serve --port=8000

# Terminal 2: Portal Admin (Port 8001)
php artisan serve --port=8001

# Terminal 3: Laravel Reverb WebSocket (Port 8085)
php artisan reverb:start --port=8085

# Terminal 4: Vite Dev Server (Hot Module Reload)
npm run dev
```

---

## 📁 5. Struktur Direktori Utama

```text
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Controller untuk Admin, Publik, Penjurian, Kustomisasi
│   │   └── Middleware/           # PortalSeparationMiddleware (Port/Domain separation)
│   └── Models/                   # Eloquent Models (Competition, Registration, Setting, FAQ, dll)
├── config/
│   ├── octane.php                # Konfigurasi runtime FrankenPHP Octane
│   ├── portal.php                # Konfigurasi pemisahan port & domain portal
│   └── reverb.php                # Konfigurasi engine WebSocket Reverb
├── database/
│   ├── migrations/               # Skema tabel database (termasuk indeks performa tinggi)
│   └── seeders/                  # Data awal cabang lomba, akun admin, dan template formulir
├── docker/
│   ├── frankenphp/               # Caddyfile routing FrankenPHP
│   └── php/                      # custom-optimasi.ini (JIT, memory, Imagick)
├── resources/
│   ├── js/
│   │   ├── Components/           # RichTextEditor, IndoDatePicker, IndoTimePicker
│   │   ├── Layouts/              # VelzonLayout, PublicLayout, KustomisasiLayout
│   │   └── Pages/
│   │       ├── Admin/            # Vue pages untuk dashboard, pendaftar, penjurian, kustomisasi
│   │       └── Public/           # Vue pages untuk landing page, registrasi, live scoreboard
│   └── views/                    # Blade templates (app.blade.php, cetak ID card, sertifikat)
├── routes/
│   ├── web.php                   # Routing web publik dan admin portal
│   ├── api.php                   # Endpoint API publik & operasional
│   ├── channels.php              # Realtime WebSocket channels (Reverb)
│   └── console.php               # Artisan command `app:update`
├── Dockerfile                    # Multi-stage image build FrankenPHP Octane + PHP 8.4.26
└── vite.config.js                # Konfigurasi bundler Vite untuk Vue 3
```

---

## 🔒 6. Kredensial Bawaan (Default Login)

| Role | Email | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin@semarak.id` | `password` (atau `admin123` via Seeder) |

> ⚠️ **PENTING**: Segera ganti kata sandi akun administrator setelah proses instalasi di lingkungan server produksi!
