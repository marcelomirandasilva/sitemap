@echo off
title Reverb WebSocket (Genmap)
echo Iniciando servidor de WebSockets Reverb...
cd /d D:\www\sitemap
C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64\php.exe artisan reverb:start --host=0.0.0.0 --port=8080
pause
