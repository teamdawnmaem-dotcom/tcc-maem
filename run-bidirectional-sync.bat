@echo off
:: Bidirectional Cloud Sync Scheduler - Runs Laravel scheduler continuously
:: This will execute the sync:bidirectional command every 10 seconds

echo ========================================
echo   Bidirectional Cloud Sync - AUTO MODE
echo ========================================
echo.
echo Started at: %date% %time%
echo Bidirectional sync every 10 seconds...
echo Press Ctrl+C to stop
echo ========================================
echo.

:loop
php artisan schedule:run
timeout /t 10 /nobreak >nul
goto loop

