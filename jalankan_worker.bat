@echo off
echo Menjalankan Pekerja Telegram SIMPERS (Laragon)...
:loop
php D:\simpers\worker_telegram.php
timeout /t 60
goto loop