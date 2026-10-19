#!/bin/sh
set -e

echo "======================================================================"
echo "    SEMARAK ALIYAH XIV 2026 - FRANKENPHP OCTANE HIGH PERFORMANCE      "
echo "    Runtime: $(php -v 2>/dev/null | head -n 1 || echo 'PHP 8.4.26')  "
echo "======================================================================"

# 1. Pastikan struktur direktori penyimpanan (storage) tersedia
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/app/public \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

# 2. Perbaiki permission direktori storage dan cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# 2.5 Pastikan paket vendor Composer tersedia (jika host volume belum memiliki vendor)
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "[Entrypoint] Folder vendor belum ditemukan di host mount. Menginstal dependensi Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# 3. Hubungkan symlink storage publik jika belum terhubung
php artisan storage:link --force 2>/dev/null || true

# 4. Tunggu kesiapan database PostgreSQL jika DB_HOST dikonfigurasi
if [ -n "$DB_HOST" ]; then
    echo "[Entrypoint] Menunggu database PostgreSQL di $DB_HOST:${DB_PORT:-5432}..."
    RETRY_COUNT=0
    MAX_RETRIES=30
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-semarak_user}" >/dev/null 2>&1 || [ $RETRY_COUNT -ge $MAX_RETRIES ]; do
        sleep 1
        RETRY_COUNT=$((RETRY_COUNT+1))
    done

    if [ $RETRY_COUNT -lt $MAX_RETRIES ]; then
        echo "[Entrypoint] Database PostgreSQL terhubung dan siap!"
        # Jalankan migrasi database otomatis
        echo "[Entrypoint] Menjalankan migrasi database..."
        php artisan migrate --force || true
    else
        echo "[Entrypoint] Peringatan: Batas waktu menunggu database terlampaui. Melanjutkan inisialisasi..."
    fi
fi

# 5. Optimasi cache sesuai environment
if [ "$APP_ENV" = "production" ]; then
    echo "[Entrypoint] Mode Production: Mengompilasi dan mengoptimasi cache aplikasi..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    echo "[Entrypoint] Mode Development: Membersihkan cache lama untuk live development..."
    php artisan optimize:clear || true
fi

echo "======================================================================"
echo "    APLIKASI SIAP - MENJALANKAN SERVER FRANKENPHP OCTANE              "
echo "======================================================================"

exec "$@"
