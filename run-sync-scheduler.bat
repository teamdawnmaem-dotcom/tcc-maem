@echo off
:: Cloud Sync Scheduler - Runs Laravel scheduler continuously
:: This will execute the sync:cloud command every 10 seconds

echo ========================================
echo   Cloud Sync Scheduler - AUTO MODE
echo ========================================
echo.
echo Started at: %date% %time%
echo Syncing every 10 seconds...
echo Press Ctrl+C to stop
echo ========================================
echo.

:loop
php artisan schedule:run
timeout /t 10 /nobreak >nul
goto loop

