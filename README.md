# SEMARAK ALIYAH 🏆
**Laman Official Semarak Aliyah XII 2026 • MA Unggulan Wahid Hasyim Balung, Jember**  
*Ajang Kompetisi Bergengsi Tingkat Pelajar SMP/MTs Sederajat Se-Kabupaten Jember*

---

## 🏛️ Arsitektur Sistem: Fullstack Microservices

Sistem ini dibangun dengan arsitektur decoupled modern:
1. **Frontend Server (Express.js - Node.js v24)**:
   - Berjalan di port `8080`.
   - Mengelola routing tampilan UI (`index.html`, `layanan-registrasi.html`, `admin.html`).
   - Menyajikan asset statis (`assets/css`, `assets/js`, `assets/img`, `JUKNIS`).
   - Bertindak sebagai API Gateway yang meneruskan request ke Laravel Backend.
2. **Backend & Admin Panel (Laravel + Vite - PHP 8.4)**:
   - Berjalan di port `8000`.
   - **Template Panel Admin**: **Themesbrand Velzon** (`https://themesbrand.com/velzon/html/default/index.html`) menggunakan integrasi **Laravel & Vite** (`@vite`).
   - Asset bundler Vite: Bootstrap 5, RemixIcon, Boxicons, SimpleBar, dan ApexCharts.
   - Menyediakan REST API: `/api/competitions`, `/api/register`, `/api/admin/*`.
   - Web Routes: `/admin`, `/admin/verify/{id}`, `/admin/toggle-quota/{id}`, `/admin/export`.
   - Menggunakan Eloquent ORM, migrasi tabel database, seeder data, dan validasi formulir.
3. **Database (MariaDB Wamp64 / Docker)**:
   - Terhubung ke database `semarak_db` di **MariaDB 11.4 WampServer** pada port `3307` (port default MariaDB Wamp64).
   - Dilengkapi fallback SQLite otomatis jika Wamp sedang belum aktif.

---

## 🚀 Cara Menjalankan di Komputer Lokal

### 1. Cara Paling Praktis (1-Klik Launcher)
Cukup **klik dua kali (double-click)** file:
```
start_all.bat
```
File batch ini akan otomatis membuka 2 terminal di jendela terpisah:
- Terminal 1: Backend Laravel + Velzon Admin (`php artisan serve --port=8000`)
- Terminal 2: Frontend Express.js (`node server.js` di port `8080`)

### 2. Cara Manual (Melalui Terminal)

**Buka Terminal 1 (Backend Laravel & Vite):**
```bash
cd backend
npm run build      # atau npm run dev untuk live reload Vite
php artisan serve --port=8000
```

**Buka Terminal 2 (Frontend Express.js):**
```bash
cd frontend
node server.js
```

### 3. Akses Website di Browser
- 🏠 **Beranda Website (Express.js):** [http://localhost:8080](http://localhost:8080)
- 📋 **Layanan Registrasi:** [http://localhost:8080/layanan-registrasi](http://localhost:8080/layanan-registrasi)
- 🔐 **Panel Admin Velzon (Laravel + Vite):** [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)
- 🔐 **Panel Admin Panitia (Express.js Gateway):** [http://localhost:8080/admin](http://localhost:8080/admin)
- 🔗 **Backend API Laravel:** [http://127.0.0.1:8000/api/competitions](http://127.0.0.1:8000/api/competitions)

---

## 🎨 Fitur Dashboard Velzon Admin (Laravel + Vite)

Panel admin mengadaptasi template **Themesbrand Velzon** (`https://themesbrand.com/velzon/html/default/index.html`):
- **Layout & Topbar**:
  - Dark vertical sidebar menu (`data-sidebar="dark"`, `data-topbar="light"`).
  - Topbar search dengan shortcut keyboard `/`.
  - Fullscreen toggle dan Dark/Light mode theme switch.
  - Dropdown Web Apps & User Profile.
- **Statistik & Metrik**:
  - 4 Kartu Metrik Animatif: Total Pendaftar, Total Dana Masuk, Keterisian Kuota, dan Menunggu Verifikasi.
- **Grafik Interaktif (ApexCharts)**:
  - Grafik tren pendaftaran harian (Smooth Area Chart dengan gradient).
  - Grafik distribusi kategori lomba (Donut Chart).
- **Manajemen Kuota 8 Cabang Lomba**:
  - Progress bar kapasitas kuota setiap cabang lomba.
  - Tombol 1-klik untuk Buka / Tutup pendaftaran lomba secara real-time.
- **Tabel Pendaftar & Tindakan Cepat**:
  - Filter status (Semua, Terverifikasi, Menunggu Verifikasi) dan filter cabang lomba.
  - Live search nama peserta, asal sekolah, dan kode registrasi.
  - Tombol Verifikasi / Batalkan Verifikasi 1-klik.
  - Tombol Direct WhatsApp dengan pesan salam dan konfirmasi otomatis kepada guru pendamping.
  - Export data pendaftar format Excel (CSV).

---

## 🐳 Cara Deploy ke Production Menggunakan Docker

Proyek ini telah siap untuk produksi dengan **Docker Compose** multi-container:

```bash
docker compose up -d --build
```

Container yang akan berjalan:
1. `semarak_frontend_express` (Express.js UI di port `8080`)
2. `semarak_backend_laravel` (Laravel API & Velzon Admin di port `8000`)
3. `semarak_mysql_db` (MySQL 8.4 Database di port `3306`)

---

## 📁 Struktur Direktori Proyek

```
d:\wp\semarak\
├── start_all.bat               # Launcher 1-klik untuk Windows (Express + Laravel)
├── docker-compose.yml          # Konfigurasi container produksi (Express + Laravel + MySQL)
│
├── frontend/                   # Frontend Express.js
│   ├── server.js               # Server Express & API gateway proxy
│   ├── package.json            # Dependensi Express, CORS, Dotenv
│   ├── Dockerfile              # Container image Express
│   ├── views/                  # File HTML tampilan (index.html, layanan-registrasi.html, admin.html)
│   └── public/                 # File statis (CSS, JS, Gambar, Pamflet, Juknis)
│
└── backend/                    # Backend Laravel + Vite
    ├── vite.config.js          # Konfigurasi Vite asset bundling
    ├── package.json            # Dependensi Vite, Bootstrap 5, ApexCharts, RemixIcon, Boxicons
    ├── resources/
    │   ├── css/app.css         # Velzon CSS theme & Bootstrap styling
    │   ├── js/app.js           # Velzon JS (ApexCharts, theme toggle, filters, SimpleBar)
    │   └── views/admin/        # Blade view: dashboard.blade.php (Velzon layout)
    ├── app/Http/Controllers/   # AdminPanelController, Api Controllers
    ├── app/Models/             # Eloquent Model: Competition, Registration
    ├── database/migrations/    # Migrasi tabel MySQL
    ├── database/seeders/       # Seeder 8 cabang lomba resmi
    ├── routes/web.php          # Route Admin Panel Velzon
    ├── routes/api.php          # Route REST API
    ├── Dockerfile              # Container image Laravel
    └── .env                    # Konfigurasi database MySQL Wamp64
```

---

© 2026 **SEMARAK ALIYAH XII** • MA Unggulan Wahid Hasyim Balung, Jember.
