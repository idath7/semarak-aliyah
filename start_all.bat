@echo off
title "Semarak Aliyah XIV 2026 - Launcher Dual-Port (Web Publik: 8000 & Portal Admin: 8001)"
set LOCAL_IP=127.0.0.1
for /f "tokens=4" %%a in ('route print 0.0.0.0 ^| findstr /c:" 0.0.0.0 "') do set LOCAL_IP=%%a

echo ==============================================================================
echo   MEMULAI SEMARAK ALIYAH XIV 2026 (ARSITEKTUR DUAL-PORT / MULTI-DOMAIN)
echo   - Web Publik (Port 8000)   : http://localhost:8000  (LAN: http://%LOCAL_IP%:8000)
echo   - Portal Admin (Port 8001) : http://localhost:8001  (LAN: http://%LOCAL_IP%:8001)
echo   - Bridge ^& State           : Inertia.js (Vue 3 + Tailwind v4)
echo   - Real-time Engine         : Laravel Reverb WebSocket (Port 8085)
echo   - Database                 : PostgreSQL 127.0.0.1:5432 (semarak_db)
echo ==============================================================================
echo.

:: 1. Jalankan Server Web Publik (Port 8000) - Terbuka untuk Jaringan Lokal 0.0.0.0
echo [1/4] Menjalankan Server Web Publik (Port 8000 - 0.0.0.0)...
start "Web Publik Semarak (Port 8000)" cmd /k "cd /d %~dp0backend && php artisan serve --host=0.0.0.0 --port=8000"
ping 127.0.0.1 -n 2 >nul

:: 2. Jalankan Server Portal Admin (Port 8001) - Terbuka untuk Jaringan Lokal 0.0.0.0
echo [2/4] Menjalankan Server Portal Admin (Port 8001 - 0.0.0.0)...
start "Portal Admin Velzon (Port 8001)" cmd /k "cd /d %~dp0backend && php artisan serve --host=0.0.0.0 --port=8001"
ping 127.0.0.1 -n 2 >nul

:: 3. Jalankan Laravel Reverb WebSocket Server (Port 8085)
echo [3/4] Menjalankan Laravel Reverb WebSocket Real-time Engine (Port 8085 - 0.0.0.0)...
start "Laravel Reverb WebSocket (Port 8085)" cmd /k "cd /d %~dp0backend && php artisan reverb:start --host=0.0.0.0 --port=8085"
ping 127.0.0.1 -n 2 >nul

:: 4. Jalankan Vite Hot-Reload Server untuk Vue 3 + Tailwind v4
echo [4/4] Menjalankan Vite Dev Server (Frontend Vue 3)...
start "Vite Dev Server (Frontend Vue 3)" cmd /k "cd /d %~dp0backend && npm run dev"

echo.
echo ==============================================================================
echo [BERHASIL] Seluruh Layanan Semarak Aliyah XIV 2026 Aktif!
echo.
echo [WEB PUBLIK]
echo    - Komputer ini   : http://localhost:8000
echo    - Jaringan Lokal : http://%LOCAL_IP%:8000
echo    - Pendaftaran    : http://%LOCAL_IP%:8000/layanan-registrasi
echo    - Scoreboard     : http://%LOCAL_IP%:8000/scoreboard
echo.
echo [PORTAL ADMIN & PANITIA]
echo    - Komputer ini   : http://localhost:8001/login
echo    - Jaringan Lokal : http://%LOCAL_IP%:8001/login
echo    - Dashboard      : http://%LOCAL_IP%:8001/admin
echo    - Operasional    : http://%LOCAL_IP%:8001/operasional
echo    - Arena Turnamen : http://%LOCAL_IP%:8001/admin/tournament/arena/volleyball-putra
echo    - Arena Penjurian: http://%LOCAL_IP%:8001/admin/jury/arena/albanjari
echo.
echo Real-time WebSocket : ws://%LOCAL_IP%:8085 (Laravel Reverb)
echo Database Terhubung  : PostgreSQL Port 5432 (semarak_db)
echo ==============================================================================
echo.
pause
