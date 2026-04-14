@echo off
title Scheduler (Genmap)
echo Iniciando Agendador de tarefas...
cd /d D:\www\sitemap
C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64\php.exe artisan schedule:work
pause
