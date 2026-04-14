@echo off
title Genmap Server Initializer
color 0b
echo ===================================================
echo     Genmap - Inicializando Servicos do Windows
echo ===================================================
echo.
cd /d %~dp0

start "Queue Worker (Genmap)" cmd /c "1-start-queue.bat"
echo [OK] Queue Worker iniciado
timeout /t 1 /nobreak >nul

start "Scheduler (Genmap)" cmd /c "2-start-scheduler.bat"
echo [OK] Scheduler iniciado
timeout /t 1 /nobreak >nul

start "Reverb WebSocket (Genmap)" cmd /c "3-start-reverb.bat"
echo [OK] Reverb WebSocket iniciado
timeout /t 1 /nobreak >nul

start "Stripe Webhook Listener" cmd /c "4-start-stripe.bat"
echo [OK] Stripe Webhook iniciado

echo.
echo ===================================================
echo Todos os processos foram abertos em novas janelas!
echo Para parar e fechar todos, execute stop-all-servers.bat
echo ===================================================
echo.
pause
