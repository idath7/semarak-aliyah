#!/usr/bin/env bash
# ==============================================================================
# SEMARAK ALIYAH - DOCKER UPDATE & GIT PULL AUTOMATION SCRIPT
# ==============================================================================
set -e

MODE="${1:-interactive}"

pull_latest_code() {
    echo "======================================================================"
    echo " [1/5] Menarik (Git Pull) Kode & Fitur Terbaru dari GitHub..."
    echo "======================================================================"
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
    echo "• Branch Git: $CURRENT_BRANCH"
    if git pull origin "$CURRENT_BRANCH"; then
        echo "✓ Berhasil memperbarui berkas dari GitHub."
    else
        echo "⚠️ Peringatan: Git pull gagal atau ada konflik lokal. Melewati langkah ini."
    fi
}

backup_database() {
    echo "======================================================================"
    echo " [2/5] Membuat Cadangan Database PostgreSQL..."
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
    echo " [3/5] Mengompilasi Aset Frontend (Node.js 22 + Vite - Cepat)..."
    echo "======================================================================"
    docker compose run --rm node npm run build
}

update_dependencies() {
    echo "======================================================================"
    echo " [4/5] Memeriksa & Mengupdate Dependency Composer..."
    echo "======================================================================"
    docker compose exec -T frankenphp composer install --no-dev --optimize-autoloader --no-interaction
}

apply_app_update() {
    echo "======================================================================"
    echo " [5/5] Migrasi Database, Optimasi Cache & Reload FrankenPHP Octane..."
    echo "======================================================================"
    docker compose exec -T frankenphp php artisan app:update
    echo "• Me-refresh kontainer web service..."
    docker compose restart frankenphp >/dev/null 2>&1 || true
}

quick_update() {
    pull_latest_code
    backup_database
    build_frontend
    update_dependencies
    apply_app_update
    echo "======================================================================"
    echo "✓ PEMBARUAN SELESAI (ZERO-DOWNTIME)! Web publik dan admin sudah aktif."
    echo "======================================================================"
}

full_rebuild() {
    pull_latest_code
    backup_database
    echo "======================================================================"
    echo " Membangun Ulang Docker Container (Full Rebuild)..."
    echo "======================================================================"
    docker compose up -d --build
    build_frontend
    apply_app_update
    echo "======================================================================"
    echo "✓ FULL REBUILD SELESAI! Seluruh stack kontainer telah diperbarui."
    echo "======================================================================"
}

case "$MODE" in
    --quick|-q)
        quick_update
        ;;
    --rebuild|-r)
        full_rebuild
        ;;
    --pull|-p)
        pull_latest_code
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
        echo "   1) Hot-Update Cepat (Git Pull + Build + Migrate + Reload Octane)"
        echo "   2) Full Rebuild & Update Stack (Git Pull + Build Container)"
        echo "   3) Tarik (Git Pull) Kode GitHub Saja"
        echo "   4) Kompilasi Aset Frontend Saja"
        echo "   5) Backup Database PostgreSQL Saja"
        echo "   6) Keluar"
        echo "======================================================================"
        read -p " Pilihan Anda [1-6]: " choice
        case "$choice" in
            1) quick_update ;;
            2) full_rebuild ;;
            3) pull_latest_code ;;
            4) build_frontend ;;
            5) backup_database ;;
            *) exit 0 ;;
        esac
        ;;
esac
