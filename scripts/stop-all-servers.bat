@echo off
title Genmap Server Stopper
color 0c
echo ===================================================
echo      Genmap - Parando todos os Servicos
echo ===================================================
echo.

echo Parando Queue Worker...
taskkill /FI "WindowTitle eq Queue Worker (Genmap)*" /T /F >nul 2>&1

echo Parando Scheduler...
taskkill /FI "WindowTitle eq Scheduler (Genmap)*" /T /F >nul 2>&1

echo Parando Reverb WebSocket...
taskkill /FI "WindowTitle eq Reverb WebSocket (Genmap)*" /T /F >nul 2>&1

echo Parando Stripe Listener...
taskkill /FI "WindowTitle eq Stripe Webhook Listener*" /T /F >nul 2>&1

echo.
echo ===================================================
echo      Todos os servicos foram encerrados!
echo ===================================================
echo.
pause
