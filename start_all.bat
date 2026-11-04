@echo off
title Semarak Aliyah XIV 2026 - Launcher Single-Window (Dual-Port: 8000 & 8001)
set LOCAL_IP=127.0.0.1
for /f "tokens=4" %%a in ('route print 0.0.0.0 ^| findstr /c:" 0.0.0.0 "') do set LOCAL_IP=%%a

echo ==============================================================================
echo   SEMARAK ALIYAH XIV 2026 - LAUNCHER SINGLE-WINDOW TERPADU
echo ==============================================================================
echo   - Web Publik (Port 8000)   : http://localhost:8000  (LAN: http://%LOCAL_IP%:8000)
echo   - Portal Admin (Port 8001) : http://localhost:8001  (LAN: http://%LOCAL_IP%:8001)
echo   - Real-time Engine (8085)  : ws://%LOCAL_IP%:8085   (Laravel Reverb)
echo   - Frontend Dev Engine      : Vite Hot-Reload (Vue 3 + Tailwind v4)
echo   - Database Terhubung       : PostgreSQL 127.0.0.1:5432 (semarak_db)
echo.
echo   [PANDUAN OPERASIONAL]
echo   - Seluruh 4 layanan aktif bersamaan di dalam 1 WINDOW TERMINAL ini.
echo   - Tekan [Ctrl + C] sekali untuk mematikan SELURUH layanan secara bersamaan.
echo ==============================================================================
echo.

cd /d %~dp0backend
npm run all
