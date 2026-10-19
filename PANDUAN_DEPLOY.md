# PANDUAN DEPLOY DOCKER - SEMARAK ALIYAH XIV 2026

Panduan komprehensif langkah-demi-langkah untuk mendeploy aplikasi **SEMARAK ALIYAH XIV 2026** ke server produksi (VPS / Cloud Server / Dedicated Server) berbasis **Docker & FrankenPHP Octane**.

---

## 🖥️ 1. Prasyarat Server (Spesifikasi Rekomendasi)

- **Sistem Operasi**: Ubuntu 22.04 LTS / 24.04 LTS atau Debian 12 (64-bit).
- **CPU**: Minimal 2 vCPU (Disarankan 4 vCPU untuk traffic tinggi ribuan peserta).
- **RAM**: Minimal 2 GB (Disarankan 4 GB - 8 GB untuk FrankenPHP Octane + PostgreSQL 18 + Redis).
- **Penyimpanan**: SSD/NVMe minimal 25 GB.
- **Port Terbuka di Firewall (Security Group)**:
  - `80` (HTTP) & `443` (HTTPS)
  - `8000` (Web Publik) & `8001` (Portal Admin) — *atau diproxy lewat domain*
  - `8085` (Laravel Reverb WebSocket)
  - `22` (SSH)

---

## 📦 2. Langkah 1: Instalasi Docker & Docker Compose di Server

Jika server VPS Anda masih baru dan belum memiliki Docker, jalankan skrip instalasi resmi berikut:

```bash
# 1. Update paket server
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git ufw htop

# 2. Instal Docker Engine resmi
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# 3. Beri izin user tanpa sudo (opsional)
sudo usermod -aG docker $USER
newgrp docker

# 4. Verifikasi instalasi
docker --version
docker compose version
```

---

## 📥 3. Langkah 2: Clone Repositori Proyek dari GitHub

Clone repositori proyek ke direktori kerja server (misal `/var/www/semarak`):

```bash
# Buat folder kerja dan clone repositori
sudo mkdir -p /var/www/semarak
sudo chown -R $USER:$USER /var/www/semarak
git clone https://github.com/idath7/semarak-aliyah.git /var/www/semarak

# Pindah ke direktori proyek
cd /var/www/semarak
```

---

## ⚙️ 4. Langkah 3: Konfigurasi File Environment (.env)

Salin berkas template environment ke direktori backend:

```bash
cp backend/.env.example backend/.env
nano backend/.env
```

### Konfigurasi Kunci yang Wajib Diubah di `backend/.env`:

```dotenv
APP_NAME="SEMARAK ALIYAH XIV 2026"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com

# Database Connection (Terhubung ke Container PostgreSQL 18)
DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
DB_DATABASE=semarak_db
DB_USERNAME=semarak_user
DB_PASSWORD=GantiPasswordDatabaseYangKuat123!

# Cache, Session, & Queue (Terhubung ke Container Redis Alpine)
CACHE_STORE=redis
CACHE_PREFIX=semarak_cache_
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PORT=6379

# Octane Server Setting
OCTANE_SERVER=frankenphp

# WebSocket Reverb Settings
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=semarak-app
REVERB_APP_KEY=semarak-key-2026
REVERB_APP_SECRET=semarak-secret-super-secure
REVERB_HOST=your-domain.com
REVERB_PORT=8085
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> 💡 **Penting**: Pastikan nilai `DB_PASSWORD` di `backend/.env` **sama** dengan environment `POSTGRES_PASSWORD` pada [docker-compose.yml](file:///d:/wp/semarak/docker-compose.yml).

---

## 🚀 5. Langkah 4: Build & Jalankan Kontainer Docker

Jalankan perintah berikut untuk membangun seluruh stack image kontainer pertama kali:

```bash
# 1. Bangun dan jalankan kontainer di background
docker compose up -d --build

# 2. Periksa status seluruh kontainer (pastikan semua berstatus Up / healthy)
docker compose ps
```

---

## 🔑 6. Langkah 5: Inisialisasi Database & Aset Produksi

Setelah kontainer aktif, jalankan inisialisasi aplikasi sekali saja:

```bash
# 1. Generate Application Key (jika belum)
docker compose exec frankenphp php artisan key:generate

# 2. Buat symlink storage agar berkas upload publik dapat diakses
docker compose exec frankenphp php artisan storage:link

# 3. Jalankan migrasi tabel database dan data awal seeder
docker compose exec frankenphp php artisan migrate --seed --force

# 4. Kompilasi aset frontend Vite (Vue 3, Tailwind v4, Icons)
docker compose run --rm node npm install
docker compose run --rm node npm run build

# 5. Optimasi cache Laravel (Config, Route, View)
docker compose exec frankenphp php artisan optimize
```

---

## 🌐 7. Langkah 6: Konfigurasi Domain & HTTPS Gratis (SSL Let's Encrypt)

Agar pengguna dapat mengakses melalui domain resmi tanpa mengetik nomor port (misalnya `semarak.maswahas.sch.id` untuk Web Publik dan `admin-semarak.maswahas.sch.id` untuk Portal Admin), gunakan Nginx Host sebagai Reverse Proxy.

### 1. Pasang Nginx & Certbot di Server Host:
```bash
sudo apt install -y nginx certbot python3-certbot-nginx
```

### 2. Konfigurasi Virtual Host Nginx:
Buat file konfigurasi `/etc/nginx/sites-available/semarak.conf`:
```bash
sudo nano /etc/nginx/sites-available/semarak.conf
```

Isi dengan konfigurasi berikut:

```nginx
# 1. Web Publik (Port 8000)
server {
    server_name semarak.maswahas.sch.id;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# 2. Portal Admin & Operasional (Port 8001)
server {
    server_name admin-semarak.maswahas.sch.id;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# 3. WebSocket Reverb Gateway (Port 8085)
server {
    server_name ws.maswahas.sch.id;

    location / {
        proxy_pass http://127.0.0.1:8085;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }
}
```

### 3. Aktifkan Konfigurasi & Pasang SSL Let's Encrypt:
```bash
sudo ln -s /etc/nginx/sites-available/semarak.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Pasang Sertifikat SSL Otomatis
sudo certbot --nginx -d semarak.maswahas.sch.id -d admin-semarak.maswahas.sch.id -d ws.maswahas.sch.id
```

---

## 🔄 8. Langkah 7: Cara Melakukan Update Kode di Masa Mendatang

Jika Anda melakukan push fitur baru atau perbaikan bug di Git, update server Anda hanya butuh 1 perintah tanpa mematikan aplikasi (**Zero-Downtime**):

```bash
cd /var/www/semarak

# Tarik kode terbaru dari GitHub
git pull origin main

# Beri izin eksekusi skrip update jika belum
chmod +x docker-update.sh

# Jalankan update instan (Otomatis backup DB, kompilasi frontend, migrasi, dan reload Octane worker)
./docker-update.sh --quick
```

---

## 💾 9. Backup Database PostgreSQL Otomatis (Cron Job)

Untuk menjaga keamanan data pendaftar lomba, buat backup otomatis setiap hari pukul 02:00 malam:

```bash
crontab -e
```

Tambahkan baris berikut di akhir file:
```cron
0 2 * * * cd /var/www/semarak && docker compose exec -T database pg_dump -U semarak_user semarak_db > /var/www/semarak/database/backups/backup_auto_$(date +\%Y\%m\%d_\%H\%M\%S).sql
```

---

## 🛠️ 10. Perintah Berguna untuk Pemeliharaan Harian

| Kebutuhan | Perintah Terminal |
| :--- | :--- |
| **Cek Status Kontainer** | `docker compose ps` |
| **Lihat Log Live FrankenPHP** | `docker compose logs -f --tail=100 frankenphp` |
| **Lihat Log WebSocket Reverb** | `docker compose logs -f reverb` |
| **Restart Server FrankenPHP** | `docker compose restart frankenphp` |
| **Masuk ke Terminal Kontainer** | `docker compose exec frankenphp bash` |
| **Bersihkan Cache Aplikasi** | `docker compose exec frankenphp php artisan optimize:clear` |
| **Reload Worker Octane Saja** | `docker compose exec frankenphp php artisan octane:reload` |
