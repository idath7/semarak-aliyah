#!/usr/bin/env bash
# ==============================================================================
# SEMARAK ALIYAH XIV 2026 - DOCKER UPDATE AUTOMATION SCRIPT (BASH/LINUX/WSL)
# ==============================================================================
set -e

MODE="${1:-interactive}"

backup_database() {
    echo "======================================================================"
    echo " [1/4] Membuat Cadangan Database PostgreSQL..."
    echo "======================================================================"
    TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
    mkdir -p database/backups
    if docker compose exec -T database pg_dump -U semarak_user semarak_db > "database/backups/backup_${TIMESTAMP}.sql" 2>/dev/null; then
        echo "✓ Cadangan tersimpan di: database/backups/backup_${TIMESTAMP}.sql"
    else
        echo "! Database tidak aktif atau cadangan dilewati."
    fi
}

build_frontend() {
    echo "======================================================================"
    echo " [2/4] Mengompilasi Aset Frontend (Node.js 22 + Vite)..."
    echo "======================================================================"
    docker compose run --rm node npm run build
}

update_dependencies() {
    echo "======================================================================"
    echo " [3/4] Memeriksa & Mengupdate Dependency Composer..."
    echo "======================================================================"
    docker compose exec -T frankenphp composer install --no-dev --optimize-autoloader --no-interaction
}

apply_app_update() {
    echo "======================================================================"
    echo " [4/4] Migrasi Database, Optimasi Cache & Reload Octane..."
    echo "======================================================================"
    docker compose exec -T frankenphp php artisan app:update
}

full_rebuild() {
    echo "======================================================================"
    echo " Membangun Ulang Docker Container (Full Rebuild)..."
    echo "======================================================================"
    backup_database
    docker compose up -d --build
    build_frontend
    apply_app_update
    echo "✓ Full Rebuild Selesai!"
}

quick_update() {
    backup_database
    build_frontend
    update_dependencies
    apply_app_update
    echo "✓ Hot-Update Selesai dengan Zero-Downtime!"
}

case "$MODE" in
    --quick|-q)
        quick_update
        ;;
    --rebuild|-r)
        full_rebuild
        ;;
    --frontend|-f)
        build_frontend
        ;;
    --backup|-b)
        backup_database
        ;;
    *)
        echo "======================================================================"
        echo "    SEMARAK ALIYAH XIV 2026 - PUSAT KONTROL PEMBARUAN DOCKER          "
        echo "======================================================================"
        echo " Pilih mode pembaruan:"
        echo "   1) Hot-Update Cepat (Zero-Downtime)"
        echo "   2) Full Rebuild & Update Stack"
        echo "   3) Kompilasi Aset Frontend Saja"
        echo "   4) Backup Database PostgreSQL"
        echo "   5) Keluar"
        echo "======================================================================"
        read -p " Pilihan Anda [1-5]: " choice
        case "$choice" in
            1) quick_update ;;
            2) full_rebuild ;;
            3) build_frontend ;;
            4) backup_database ;;
            *) exit 0 ;;
        esac
        ;;
esac
