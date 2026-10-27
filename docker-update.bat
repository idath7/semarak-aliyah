@echo off
setlocal enabledelayedexpansion
title Semarak Aliyah - Pusat Pembaruan Docker

:MENU
cls
echo ==============================================================================
echo       SEMARAK ALIYAH - PUSAT KONTROL PEMBARUAN DOCKER               
echo ==============================================================================
echo.
echo   Pilih jenis pembaruan yang ingin Anda jalankan:
echo.
echo   [1] Hot-Update Cepat (Kode & Fitur Baru - Zero Downtime)
echo       - Cadangkan database otomatis
echo       - Kompilasi aset Vite (Vue 3, Tailwind, JS)
echo       - Jalankan migrasi database PostgreSQL
echo       - Segarkan cache Laravel & Reload Octane Worker
echo.
echo   [2] Full Rebuild & Update (Pembaruan Dockerfile / Caddyfile / Services)
echo       - Build ulang image container dengan Docker Compose
echo       - Refresh dependency & kompilasi ulang aset produksi
echo       - Jalankan migrasi dan optimasi cache
echo.
echo   [3] Kompilasi Ulang Aset Frontend Saja (Vite Build)
echo       - Kompilasi file Vue 3, CSS, dan Tailwind v4
echo.
echo   [4] Cadangkan Database PostgreSQL (Manual Backup)
echo       - Ekspor database ke folder database/backups
echo.
echo   [5] Bersihkan Cache Aplikasi & Restart Worker
echo       - Reset cache config/route/view & restart service FrankenPHP
echo.
echo   [6] Pantau Log FrankenPHP Octane (Live Logs)
echo.
echo   [0] Keluar
echo.
echo ==============================================================================
set /p PILIHAN="Masukkan pilihan Anda [0-6]: "

if "%PILIHAN%"=="1" goto HOT_UPDATE
if "%PILIHAN%"=="2" goto FULL_REBUILD
if "%PILIHAN%"=="3" goto VITE_BUILD
if "%PILIHAN%"=="4" goto BACKUP_DB
if "%PILIHAN%"=="5" goto CLEAR_CACHE
if "%PILIHAN%"=="6" goto VIEW_LOGS
if "%PILIHAN%"=="0" goto EXIT
goto MENU

:: ----------------------------------------------------------------------------
:: 1. HOT UPDATE (ZERO DOWNTIME)
:: ----------------------------------------------------------------------------
:HOT_UPDATE
cls
echo ==============================================================================
echo [1/4] MEMBUAT CADANGAN DATABASE SEBELUM UPDATE...
echo ==============================================================================
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,8%_%datetime:~8,6%
if not exist "database\backups" mkdir database\backups
docker compose exec -T database pg_dump -U semarak_user semarak_db > "database\backups\backup_preupdate_%TIMESTAMP%.sql" 2>nul
if exist "database\backups\backup_preupdate_%TIMESTAMP%.sql" (
    echo [✓] Database berhasil dicadangkan: database\backups\backup_preupdate_%TIMESTAMP%.sql
) else (
    echo [!] Database belum aktif atau tidak dapat dicadangkan langsung, melanjutkan update...
)

echo.
echo ==============================================================================
echo [2/4] MENGOMPILASI ASET FRONTEND TERBARU (NODE.JS + VITE)...
echo ==============================================================================
docker compose run --rm node sh -c "npm install && npm run build"

echo.
echo ==============================================================================
echo [3/4] MENGUPDATE DEPENDENCY COMPOSER (JIKA ADA PACKAGE BARU)...
echo ==============================================================================
docker compose exec -T frankenphp composer install --no-dev --optimize-autoloader --no-interaction

echo.
echo ==============================================================================
echo [4/4] MENJALANKAN MIGRASI, OPTIMASI CACHE & RELOAD WORKER OCTANE...
echo ==============================================================================
docker compose exec -T frankenphp php artisan app:update

echo.
echo ==============================================================================
echo [SELESAI] Hot-Update Berhasil Diterapkan dengan Zero-Downtime!
echo ==============================================================================
pause
goto MENU

:: ----------------------------------------------------------------------------
:: 2. FULL REBUILD
:: ----------------------------------------------------------------------------
:FULL_REBUILD
cls
echo ==============================================================================
echo [1/3] MEMBANGUN ULANG CONTAINER DOCKER (BUILD IMAGE TERBARU)...
echo ==============================================================================
docker compose up -d --build

echo.
echo ==============================================================================
echo [2/3] MENGOMPILASI ASET FRONTEND PRODUKSI...
echo ==============================================================================
docker compose run --rm node sh -c "npm install && npm run build"

echo.
echo ==============================================================================
echo [3/3] MENJALANKAN INISIALISASI & MIGRASI SISTEM...
echo ==============================================================================
docker compose exec -T frankenphp php artisan app:update

echo.
echo ==============================================================================
echo [SELESAI] Full Rebuild & Update Stack Berhasil!
echo ==============================================================================
pause
goto MENU

:: ----------------------------------------------------------------------------
:: 3. VITE BUILD
:: ----------------------------------------------------------------------------
:VITE_BUILD
cls
echo ==============================================================================
echo MENGOMPILASI ASET FRONTEND DENGAN NODE.JS VITE...
echo ==============================================================================
docker compose run --rm node sh -c "npm install && npm run build"
echo.
echo [✓] Kompilasi aset frontend selesai!
pause
goto MENU

:: ----------------------------------------------------------------------------
:: 4. BACKUP DB
:: ----------------------------------------------------------------------------
:BACKUP_DB
cls
echo ==============================================================================
echo MEMBUAT CADANGAN DATABASE POSTGRESQL 18...
echo ==============================================================================
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value') do set datetime=%%I
set TIMESTAMP=%datetime:~0,8%_%datetime:~8,6%
if not exist "database\backups" mkdir database\backups
docker compose exec -T database pg_dump -U semarak_user semarak_db > "database\backups\backup_manual_%TIMESTAMP%.sql"
echo.
if exist "database\backups\backup_manual_%TIMESTAMP%.sql" (
    echo [✓] Cadangan berhasil disimpan di: database\backups\backup_manual_%TIMESTAMP%.sql
) else (
    echo [X] Gagal membuat cadangan database. Pastikan container database sedang berjalan.
)
pause
goto MENU

:: ----------------------------------------------------------------------------
:: 5. CLEAR CACHE & RESTART
:: ----------------------------------------------------------------------------
:CLEAR_CACHE
cls
echo ==============================================================================
echo MEMBERSIHKAN SELURUH CACHE APLIKASI DAN ME-RESTART SERVICE...
echo ==============================================================================
docker compose exec -T frankenphp php artisan optimize:clear
docker compose restart frankenphp reverb
echo.
echo [✓] Cache berhasil dibersihkan dan server FrankenPHP berhasil di-restart!
pause
goto MENU

:: ----------------------------------------------------------------------------
:: 6. VIEW LOGS
:: ----------------------------------------------------------------------------
:VIEW_LOGS
cls
echo Menampilkan live logs dari FrankenPHP Octane (Tekan CTRL+C untuk kembali)...
docker compose logs -f --tail=100 frankenphp
goto MENU

:EXIT
exit /b 0
