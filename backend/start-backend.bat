@echo off
rem Jihan Mart admin: double click to start the local database and the Laravel server.
rem Admin panel: http://localhost:8000/admin   (login email and password are in backend\.env: ADMIN_EMAIL / ADMIN_PASSWORD)
rem Keep this window open while you work. Close it to stop the web server (the database keeps running until you restart Windows).
cd /d "%~dp0"

tasklist /FI "IMAGENAME eq mariadbd.exe" | find /I "mariadbd.exe" >nul
if errorlevel 1 (
    echo Starting database...
    start "" /B "E:\devtools\mariadb\bin\mariadbd.exe" --defaults-file="E:\devtools\mariadb-data\my.ini"
    timeout /t 4 >nul
)

echo.
echo   Jihan Mart admin: http://localhost:8000/admin
echo   Keep this window open. Close it to stop.
echo.
start "" cmd /c "timeout /t 2 >nul & start http://localhost:8000/admin"
"E:\devtools\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
pause
