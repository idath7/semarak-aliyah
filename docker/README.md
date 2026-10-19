# Panduan Racikan Docker Enterprise - SEMARAK ALIYAH XIV 2026

Racikan Docker mutakhir ini mengintegrasikan server performa tinggi **FrankenPHP + Laravel Octane**, database **PostgreSQL 18.6** teroptimasi, in-memory cache **Redis Alpine**, serta **Node.js 22 LTS** untuk frontend Vite.

---

## 🚀 1. Spesifikasi Stack & Layanan

| Layanan | Komponen / Versi | Port Eksternal | Fungsi Utama |
| :--- | :--- | :--- | :--- |
| **frankenphp** | `dunglas/frankenphp:1-php8.4-alpine` (PHP 8.4.26) | `8000`, `8001`, `8085`, `2019` | Server aplikasi Laravel Octane (Caddy engine + PHP 8.4.26 in-memory worker) |
| **database** | `postgres:18-alpine` | `5432` | Database engine PostgreSQL 18.6 dengan konfigurasi optimasi SSD & RAM |
| **redis** | `redis:alpine` | `6379` | In-memory cache, session store, dan message queue dengan policy LRU |
| **node** | `node:22-alpine` | `5173` | Runtime Node.js untuk kompilasi aset frontend dan Vite Hot Module Reload |
| **reverb** | `backend/Dockerfile` | `8085` (Internal) | WebSocket engine real-time untuk broadcast update data langsung |

---

## 📁 2. Pemetaan Folder Langsung (Live Folder Mapping)

Seluruh folder kode aplikasi dipetakan secara langsung ke dalam container:
- `./backend:/var/www/html` pada container **FrankenPHP** & **Reverb**.
- `./backend:/app` pada container **Node.js**.

> **Keuntungan:** Setiap perubahan file kode PHP, Vue, Blade, atau CSS di komputer lokal Anda akan langsung terbaca seketika di dalam container tanpa perlu melakukan *build* ulang image Docker!

---

## ⚡ 3. Optimasi Kustom PHP 8.4.26 & Ekstensi Imagick

File konfigurasi kustom tersimpan di `docker/php/custom-optimasi.ini`:
- **Ekstensi Aktif:** `imagick`, `pdo_pgsql`, `pdo_mysql`, `gd`, `zip`, `xml`, `bcmath`, `pcntl`, `opcache`, `redis`, `intl`.
- **PHP 8.4 JIT Compiler:** `opcache.jit = tracing` dengan buffer `128M`.
- **Alokasi Memori:** `memory_limit = 512M`.
- **Batas Upload:** `upload_max_filesize = 50M` dan `post_max_size = 50M`.
- **Realpath Cache:** `4096k` dengan TTL `600s` untuk meminimalkan I/O disk.
- **Thread Safety:** `MAGICK_THREAD_LIMIT = 1` untuk mencegah konflik thread antara ImageMagick OpenMP dengan worker goroutine FrankenPHP.

---

## 🗄️ 4. Optimasi Kustom Database PostgreSQL 18.6

File konfigurasi kustom tersimpan di `docker/postgres/optimasi-postgresql.conf`:
- **RAM Tuning:** `shared_buffers = 512MB` (25% RAM), `effective_cache_size = 1536MB` (75% RAM).
- **Work Memory:** `work_mem = 16MB`, `maintenance_work_mem = 128MB`.
- **SSD / NVMe Optimization:** `random_page_cost = 1.1`, `effective_io_concurrency = 200`.
- **WAL & Checkpoint:** `checkpoint_completion_target = 0.9`, `min_wal_size = 1GB`, `max_wal_size = 4GB`.
- **Concurrency & Parallel Workers:** `max_connections = 200`, `max_worker_processes = 4`, `max_parallel_workers = 4`.
- **Autovacuum Tuning:** Agresif memantau dan membersihkan bloat saat lonjakan pendaftaran massal.
- **Slow Query Logging:** Mencatat query yang membutuhkan waktu > 250ms.

---

## 🌐 5. Arsitektur Dual-Port FrankenPHP (Caddy Engine)

FrankenPHP secara otomatis menangani routing multi-portal tanpa ketergantungan Nginx eksternal:
1. **Port 8000**: Portal Web Publik (Landing page pendaftaran, panduan lomba, pengumuman).
2. **Port 8001**: Portal Admin & Operasional (Admin panel, kustomisasi teks berjalan, manajemen lomba).
3. **Port 8085**: Reverse proxy otomatis ke container Laravel Reverb untuk WebSocket realtime.

---

## 🛠️ 6. Cara Menjalankan

### Menjalankan Seluruh Stack di Background:
```bash
docker compose up -d
```

### Menjalankan dengan Build Ulang (Pertama Kali):
```bash
docker compose up -d --build
```

### Menjalankan Vite Dev Server (Hot Module Reload):
```bash
docker compose up node
```

### Membangun Aset Frontend Production (Vite Build):
```bash
docker compose run --rm node npm run build
```

### Memeriksa Status Layanan & Log:
```bash
docker compose ps
docker compose logs -f frankenphp
```

### Menghentikan Layanan:
```bash
docker compose down
```

---

## 🔄 7. Fitur Pembaruan Aplikasi & Docker (Update Feature)

Tersedia skrip pembaruan interaktif satu-klik jika terdapat perbaikan kode, penambahan fitur baru, atau migrasi database:

### Di Windows:
Cukup klik dua kali atau jalankan di terminal:
```cmd
docker-update.bat
```

### Di Linux / WSL / Server:
```bash
chmod +x docker-update.sh
./docker-update.sh --quick
```

### Opsi Pembaruan yang Tersedia:
1. **Hot-Update Cepat (Zero-Downtime)**:
   - Membuat cadangan database otomatis ke `database/backups/`.
   - Mengompilasi ulang aset frontend (Vite Vue 3).
   - Memperbarui package composer jika ada library baru.
   - Menjalankan migrasi database PostgreSQL terbaru (`migrate --force`).
   - Menyegarkan cache Laravel & me-reload worker FrankenPHP Octane (`octane:reload`).
2. **Full Rebuild & Update**:
   - Membangun ulang seluruh container Docker jika terdapat perubahan pada `Dockerfile`, `Caddyfile`, atau ekstensi PHP.
3. **Perintah Artisan Langsung**:
   ```bash
   docker compose exec frankenphp php artisan app:update
   ```

