@echo off
title Queue Worker (Genmap)
echo Iniciando fila de processamento...
cd /d D:\www\sitemap
C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64\php.exe artisan queue:work --sleep=3 --tries=3 --backoff=10
pause
