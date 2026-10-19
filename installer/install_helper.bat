@echo off
setlocal enabledelayedexpansion

:: Ruta base de instalacion
set "APP_DIR=C:\laragon\www\jsbolsas"
cd /d "%APP_DIR%"

:: 1. Crear .env local si no existe
if not exist .env (
    copy /y .env.example .env
)

:: 2. Asegurar configuraciones clave en .env
findstr /C:"CLOUD_SYNC_URL" .env >nul || echo CLOUD_SYNC_URL=https://bolsas.plasticosmyf.com>>.env
findstr /C:"CLOUD_SYNC_TOKEN" .env >nul || echo CLOUD_SYNC_TOKEN=jsbolsas_secure_sync_token_2026>>.env
findstr /C:"NODE_ROLE" .env >nul || echo NODE_ROLE=local>>.env
findstr /C:"APP_URL" .env >nul || echo APP_URL=http://jsbolsas.test>>.env

:: 3. Localizar binario PHP en Laragon
set "PHP_BIN=php"
for /d %%D in (C:\laragon\bin\php\php-8*) do (
    if exist "%%D\php.exe" set "PHP_BIN=%%D\php.exe"
)

:: 4. Localizar binario MySQL en Laragon
set "MYSQL_BIN=mysql"
for /d %%D in (C:\laragon\bin\mysql\mysql*) do (
    if exist "%%D\bin\mysql.exe" set "MYSQL_BIN=%%D\bin\mysql.exe"
)

:: 5. Asegurar que el motor MySQL este corriendo
net start mysql >nul 2>&1
net start MySQL >nul 2>&1
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | findstr /I "mysqld.exe" >nul || (
    for /d %%D in (C:\laragon\bin\mysql\mysql*) do (
        if exist "%%D\bin\mysqld.exe" start "" /b "%%D\bin\mysqld.exe"
    )
    timeout /t 4 /nobreak >nul
)

:: 6. Crear Base de Datos jsbolsas_db
"%MYSQL_BIN%" -u root -e "CREATE DATABASE IF NOT EXISTS jsbolsas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>nul

:: 7. Inicializar claves, migraciones y estado limpio de fábrica
"%PHP_BIN%" artisan key:generate --force
"%PHP_BIN%" artisan migrate --force
"%PHP_BIN%" artisan db:seed --force
"%PHP_BIN%" artisan factory:reset-tests --force
"%PHP_BIN%" artisan optimize:clear

:: 8. Configurar inicio automatico de Laragon en Windows
if not exist "C:\laragon\usr" mkdir "C:\laragon\usr" 2>nul
if not exist "C:\laragon\usr\laragon.ini" (
    (
        echo [preferences]
        echo RunAtStartup=-1
        echo AutoVirtualHosts=-1
        echo MinimizeWhenRun=-1
        echo AutoStart=-1
    ) > "C:\laragon\usr\laragon.ini"
)

exit /b 0
