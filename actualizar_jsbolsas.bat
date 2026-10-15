@echo off
chcp 65001 >nul
title Actualizador Automatizado - JSBolsas Pro
color 0B
cls
echo ==============================================================================
echo                      🏭 JSBOLSAS PRO - ACTUALIZADOR OFICIAL
echo ==============================================================================
echo.
echo Este asistente actualizará el sistema local a la última versión estable desde
echo GitHub, protegiendo previamente los pesajes y turnos registrados en esta PC.
echo.
pause

echo.
echo [1/6] Generando respaldo preventivo de la base de datos local...
php artisan db:backup
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ⚠️ ADVERTENCIA: No se pudo generar el archivo de respaldo local.
    echo ¿Desea continuar con la actualizacion sin respaldo previo?
    choice /M "Presione S para continuar o N para cancelar"
    if errorlevel 2 goto cancelado
)

echo.
echo [2/6] Subiendo registros locales pendientes al VPS en la nube...
php artisan cloud:sync --direction=push
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ⚠️ ATENCION: No se pudo conectar con el VPS o hubo un error al sincronizar.
    echo ¿Desea continuar con la actualizacion del codigo de todos modos?
    choice /M "Presione S para continuar o N para cancelar"
    if errorlevel 2 goto cancelado
)

echo.
echo [3/6] Descargando última versión de código desde GitHub (main)...
git fetch origin main
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Error al contactar GitHub. Verifique su conexion a internet.
    goto error
)
git reset --hard origin/main
if %ERRORLEVEL% NEQ 0 (
    echo ❌ Error al aplicar cambios desde el repositorio.
    goto error
)

echo.
echo [4/6] Actualizando dependencias de sistema (Composer)...
call composer install --no-dev --optimize-autoloader --no-interaction
if %ERRORLEVEL% NEQ 0 (
    echo ⚠️ Advertencia: composer no pudo completarse al 100%, continuando con migraciones...
)

echo.
echo [5/6] Aplicando migraciones de base de datos y limpiando caché...
php artisan migrate --force
php artisan optimize:clear
php artisan view:cache

echo.
echo [6/6] Descargando formulas, precios y catalogos nuevos desde el VPS...
php artisan cloud:sync --direction=pull

echo.
echo ==============================================================================
echo               ✅ ¡ACTUALIZACIÓN COMPLETADA CON ÉXITO!
echo ==============================================================================
echo El sistema local de JSBolsas Pro ha sido actualizado y sincronizado.
echo Puede continuar trabajando con normalidad.
echo.
pause
exit /b 0

:cancelado
echo.
echo ⛔ Actualización cancelada por el usuario. Sus datos locales no fueron modificados.
pause
exit /b 1

:error
echo.
echo ❌ Ocurrió un error crítico durante la actualización.
echo Llame a Soporte Técnico (JSDesk) para asistencia remota.
pause
exit /b 1
