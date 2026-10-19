@echo off
title Semarak Aliyah XII 2026 - Launcher Dual-Port (Web Publik: 8000 & Portal Admin: 8001)
echo ==============================================================================
echo   MEMULAI SEMARAK ALIYAH XII 2026 (ARSITEKTUR DUAL-PORT / MULTI-DOMAIN)
echo   - Web Publik (Port 8000)   : http://localhost:8000
echo   - Portal Admin (Port 8001) : http://localhost:8001
echo   - Bridge & State           : Inertia.js (Vue 3 + Tailwind v4)
echo   - Real-time Engine         : Laravel Reverb WebSocket (Port 8085)
echo   - Database                 : MariaDB 127.0.0.1:3307 (semarak_db)
echo ==============================================================================
echo.

:: 1. Jalankan Server Web Publik (Port 8000)
echo [1/4] Menjalankan Server Web Publik (Port 8000)...
start "Web Publik Semarak (Port 8000)" cmd /k "cd /d %~dp0backend && php artisan serve --port=8000"
timeout /t 2 /nobreak >nul

:: 2. Jalankan Server Portal Admin (Port 8001)
echo [2/4] Menjalankan Server Portal Admin (Port 8001)...
start "Portal Admin Velzon (Port 8001)" cmd /k "cd /d %~dp0backend && php artisan serve --port=8001"
timeout /t 2 /nobreak >nul

:: 3. Jalankan Laravel Reverb WebSocket Server (Port 8085)
echo [3/4] Menjalankan Laravel Reverb WebSocket Real-time Engine...
start "Laravel Reverb WebSocket (Port 8085)" cmd /k "cd /d %~dp0backend && php artisan reverb:start --port=8085"
timeout /t 2 /nobreak >nul

:: 4. Jalankan Vite Hot-Reload Server untuk Vue 3 + Tailwind v4
echo [4/4] Menjalankan Vite Dev Server (Frontend Vue 3)...
start "Vite Dev Server (Frontend Vue 3)" cmd /k "cd /d %~dp0backend && npm run dev"

echo.
echo ==============================================================================
echo [BERHASIL] Seluruh Layanan Semarak Aliyah XII 2026 Aktif!
echo.
echo 🌐 [WEB PUBLIK]
echo    - Beranda Publik (Vue 3 SPA)  : http://localhost:8000
echo    - Layanan Registrasi Delegasi : http://localhost:8000/layanan-registrasi
echo    - Live Scoreboard Panggung     : http://localhost:8000/scoreboard
echo.
echo 🔐 [PORTAL ADMIN & PANITIA]
echo    - Sign In Admin (Velzon)      : http://localhost:8001/login
echo    - Dashboard & Pendaftar       : http://localhost:8001/admin
echo    - Pusat Komando Operasional   : http://localhost:8001/operasional
echo    - Arena Turnamen & Wasit      : http://localhost:8001/admin/tournament/arena/volleyball-putra
echo    - Arena Penjurian & Stopwatch : http://localhost:8001/admin/jury/arena/albanjari
echo    - Gate Check-in Barcode       : http://localhost:8001/admin/gate-checkin
echo    - Studio ID Card Cetak        : http://localhost:8001/admin/print-studio
echo.
echo 📡 Real-time WebSocket         : ws://127.0.0.1:8085 (Laravel Reverb)
echo 🗄️ Database Terhubung          : MariaDB Port 3307 (semarak_db)
echo ==============================================================================
echo.
pause
