#!/bin/bash
# ==============================================================================
# SEMARAK ALIYAH XIV - DOCKER CRON BACKUP SCRIPT
# ==============================================================================
# Script ini dapat dipanggil langsung dari Host Crontab sistem operasi Linux:
# Contoh Crontab Host (Jalankan setiap jam 02:00 pagi):
# 0 2 * * * /path/to/semarak/docker/backup/cron-backup.sh >> /path/to/semarak/backend/storage/logs/backup-host-cron.log 2>&1
# ==============================================================================

set -e

# Tentukan direktori kerja proyek
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
cd "$PROJECT_ROOT"

echo "======================================================================"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Memulai Cadangan Otomatis Docker..."
echo "======================================================================"

CONTAINER_NAME="semarak_frankenphp_octane"

# Cek apakah container sedang berjalan
if docker ps --format '{{.Names}}' 2>/dev/null | grep -q "^${CONTAINER_NAME}$"; then
    echo "[Docker Backup] Menjalankan perintah backup di container ${CONTAINER_NAME}..."
    docker exec "$CONTAINER_NAME" php artisan backup:run --gdrive
    echo "[Docker Backup] Berhasil menyelesaikan pencadangan terjadwal."
elif docker ps --format '{{.Names}}' 2>/dev/null | grep -q "^semarak_scheduler$"; then
    echo "[Docker Backup] Menjalankan perintah backup di container semarak_scheduler..."
    docker exec "semarak_scheduler" php artisan backup:run --gdrive
    echo "[Docker Backup] Berhasil menyelesaikan pencadangan terjadwal."
else
    echo "[Docker Backup] Peringatan: Kontainer Docker tidak aktif! Menjalankan php artisan lokal..."
    if command -v php >/dev/null 2>&1; then
        cd "$PROJECT_ROOT/backend"
        php artisan backup:run --gdrive
    else
        echo "[Docker Backup] Gagal: PHP maupun Docker tidak dapat diakses."
        exit 1
    fi
fi

echo "======================================================================"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Cadangan Otomatis Selesai!"
echo "======================================================================"
