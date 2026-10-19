# SEMARAK ALIYAH XIV 2026 🏆
**Laman Resmi & Sistem Operasional Terpadu SEMARAK ALIYAH XIV 2026**  
*MAS Wahid Hasyim Balung, Jember • Ajang Kompetisi Bergengsi Tingkat Pelajar SMP/MTs Sederajat Se-Kabupaten Jember*

---

## 🏛️ 1. Arsitektur Sistem Terpadu (Unified Modern Stack)

Sistem SEMARAK ALIYAH XIV 2026 dibangun menggunakan arsitektur modern berbasis **Single Page Application (SPA)** yang terintegrasi secara penuh:

```
                                 ┌───► [Port 8000] Web Publik (Landing Page, Pendaftaran, Scoreboard)
  Browser / Pengguna ────────────┼───► [Port 8001] Portal Admin (Velzon Panel, Kustomisasi, Operasional)
                                 └───► [Port 8085] Real-Time Gateway (Laravel Reverb WebSocket)
```

- **Runtime & Web Server**: **FrankenPHP 1.x + Laravel Octane (PHP 8.4.26 Alpine)**
  - Server in-memory berbasis Caddy Engine dengan persistent worker.
  - Menangani routing dual-port secara native (Port 8000 dan Port 8001) tanpa reverse proxy terpisah.
  - PHP 8.4 JIT Compiler (`tracing`), Realpath Cache 4096k, memori 512M, serta ekstensi **`imagick`**.
- **Backend Core**: **Laravel 13.x**
  - Pemisahan portal ketat via `PortalSeparationMiddleware`.
  - Eloquent ORM, migrasi database terindeks, dan event broadcasting real-time.
- **Frontend SPA**: **Inertia.js v2 + Vue 3 (Composition API)**
  - Navigasi instan tanpa refresh halaman (SPA).
  - UI Web Publik interaktif dan Portal Admin berbasis template modern **Themesbrand Velzon**.
  - Styling canggih dengan **TailwindCSS v4**, Bootstrap 5 utilities, dan ApexCharts.
- **Database Engine**: **PostgreSQL 18.6**
  - Konfigurasi khusus performa tinggi (SSD/NVMe optimization, `shared_buffers = 512MB`, autovacuum agresif).
- **Cache & Message Queue**: **Redis Terbaru (Alpine)**
  - In-memory cache, session store, dan task queue dengan eviction policy `allkeys-lru`.
- **Real-Time Engine**: **Laravel Reverb WebSocket (Port 8085)**
  - Sinkronisasi data skor panggung, check-in, dan kuota peserta secara langsung.

---

## 🌐 2. Panduan Port & Kanal Akses

| Kanal | Port | URL Akses | Deskripsi & Konten Utama |
| :--- | :--- | :--- | :--- |
| **Web Publik** | `8000` | [http://localhost:8000](http://localhost:8000) | Landing page, sambutan kepala madrasah, countdown acara, unduh Juknis PDF resmi. |
| **Layanan Pendaftaran** | `8000` | [http://localhost:8000/layanan-registrasi](http://localhost:8000/layanan-registrasi) | Formulir dinamis registrasi delegasi sekolah per cabang lomba. |
| **Live Scoreboard** | `8000` | [http://localhost:8000/scoreboard](http://localhost:8000/scoreboard) | Papan skor panggung langsung untuk penonton dan suporter. |
| **Portal Admin Velzon** | `8001` | [http://localhost:8001/admin](http://localhost:8001/admin) | Dashboard analitik pendaftar, monitoring kuota, dan verifikasi berkas. |
| **Kustomisasi Lanjutan** | `8001` | [http://localhost:8001/admin/kustomisasi](http://localhost:8001/admin/kustomisasi) | Kelola teks berjalan (marquee), biaya & rekening, maintenance mode, SEO, FAQ, sponsor. |
| **Pusat Operasional** | `8001` | [http://localhost:8001/operasional](http://localhost:8001/operasional) | Pusat komando panitia hari pelaksanaan event. |
| **Gate Check-in** | `8001` | [http://localhost:8001/admin/gate-checkin](http://localhost:8001/admin/gate-checkin) | Scanner barcode / QR code kehadiran delegasi di pintu venue. |
| **Studio Cetak** | `8001` | [http://localhost:8001/admin/print-studio](http://localhost:8001/admin/print-studio) | Generator dan cetak massal ID Card peserta serta sertifikat/piagam. |
| **Arena Wasit & Juri** | `8001` | [http://localhost:8001/admin/jury/arena](http://localhost:8001/admin/jury/arena) | Input nilai juri dengan stopwatch otomatis dan bagan gugur turnamen. |
| **WebSocket Reverb** | `8085` | `ws://localhost:8085` | Engine push realtime update data pertandingan dan pendaftaran. |

---

## 🚀 3. Cara Menjalankan Aplikasi

### OPSI A: Menggunakan Docker (Standar Produksi & Staging)
Seluruh stack layanan sudah dikonfigurasi dalam berkas `docker-compose.yml`:

```bash
# Menjalankan seluruh container di background
docker compose up -d

# Menjalankan build image pertama kali
docker compose up -d --build

# Memantau log server FrankenPHP Octane
docker compose logs -f frankenphp
```

#### 🔄 Fitur Pembaruan Satu-Klik (Update & Hot-Reload):
Jika ada perbaikan kode bugfix atau penambahan fitur baru:
- **Di Windows**: Cukup klik dua kali berkas **`docker-update.bat`**.
- **Di Linux / Server**: Jalankan perintah **`./docker-update.sh --quick`**.
- Skrip ini otomatis mencadangkan database PostgreSQL, mengompilasi aset frontend Vite, menjalankan migrasi database, menyegarkan cache, dan me-reload worker FrankenPHP Octane (**Zero Downtime**).

---

### OPSI B: Menjalankan Secara Native (Pengembangan Lokal Tanpa Docker)

#### 1. Launcher Praktis 1-Klik (Windows):
Cukup klik dua kali berkas:
```cmd
start_all.bat
```
Skrip ini otomatis meluncurkan 4 jendela terminal:
- Server Web Publik (Port 8000)
- Server Portal Admin (Port 8001)
- Server WebSocket Reverb (Port 8085)
- Server Vite Dev Hot-Reload (Port 5173)

#### 2. Manual via Terminal:
```bash
# Masuk ke direktori aplikasi
cd backend

# Instalasi dependency
composer install
npm install

# Kompilasi frontend
npm run build

# Inisialisasi database
php artisan migrate --seed
php artisan storage:link

# Jalankan server
php artisan serve --port=8000
```

---

## 📁 4. Struktur Direktori Repositori

```text
d:\wp\semarak\
├── docker-compose.yml          # Konfigurasi orkestrasi multi-container Docker
├── docker-update.bat           # Skrip otomatisasi pembaruan & hot-reload Windows
├── docker-update.sh            # Skrip otomatisasi pembaruan & hot-reload Linux/Server
├── start_all.bat               # Launcher 1-klik development lokal Windows
├── README.md                   # Dokumentasi master proyek Semarak Aliyah
│
├── docker/                     # Konfigurasi container engine
│   ├── frankenphp/
│   │   └── Caddyfile           # Konfigurasi Caddy routing dual-port & WebSocket
│   ├── php/
│   │   └── custom-optimasi.ini # Konfigurasi performa PHP 8.4.26, JIT, dan Imagick
│   ├── postgres/
│   │   └── optimasi-postgresql.conf # Konfigurasi performa database PostgreSQL 18.6
│   └── entrypoint.sh           # Skrip boot container otomatis
│
├── database/
│   ├── schema.sql              # Skema awal database PostgreSQL
│   └── backups/                # Folder penyimpanan dump cadangan database otomatis
│
└── backend/                    # Inti aplikasi (Laravel 13 + Inertia + Vue 3)
    ├── app/                    # Controller, Middleware, Models
    ├── config/                 # Konfigurasi Octane, Portal, Reverb, Database
    ├── database/               # Migrasi tabel terindeks dan data seeder
    ├── resources/
    │   ├── js/                 # Halaman Vue 3 (Pages/Public, Pages/Admin)
    │   └── views/              # Template Blade (app.blade.php, cetak ID Card)
    ├── routes/                 # Routing web.php, api.php, console.php
    ├── Dockerfile              # Multi-stage image build FrankenPHP Octane
    ├── package.json            # Dependensi Vue 3, Inertia, TailwindCSS v4
    └── composer.json           # Dependensi Laravel 13, Octane, Reverb
```

---

## 🔒 5. Kredensial Administrator Bawaan

| Parameter | Akun Super Admin |
| :--- | :--- |
| **URL Login** | [http://localhost:8001/login](http://localhost:8001/login) |
| **Email** | `admin@semarak.id` |
| **Password** | `password` (atau `admin123`) |

> ⚠️ **Catatan Keamanan**: Pastikan untuk mengubah kredensial default di atas sebelum mendeploy ke server publik yang terhubung ke internet.

---

© 2026 **SEMARAK ALIYAH XIV** • MAS WAHID HASYIM BALUNG, Jember.
