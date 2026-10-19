# PowerShell Post-Installation & Configuration Script for JSBolsas Pro
param (
    [string]$ProjectDir = "C:\laragon\www\jsbolsas"
)

if (-not (Test-Path $ProjectDir)) {
    $ProjectDir = (Resolve-Path ($PSScriptRoot + "\..")).Path
}

Set-Location $ProjectDir

$folderName = "jsbolsas"
$dbName = "jsbolsas_db"
$appUrl = "http://jsbolsas.test"

Write-Host "=========================================================="
Write-Host "   CONFIGURACION INTEGRAL DE LARAGON - JSBOLSAS PRO"
Write-Host "=========================================================="

# 1. Detectar Laragon e inyectar ejecutables en el PATH actual
$laragonDir = "C:\laragon"
if (-not (Test-Path $laragonDir)) {
    $drive = $ProjectDir.Substring(0,2)
    $laragonDir = "$drive\laragon"
}

if (Test-Path "$laragonDir\bin") {
    $laragonBinPaths = @(
        (Get-ChildItem -Path "$laragonDir\bin\php" -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName),
        (Get-ChildItem -Path "$laragonDir\bin\mysql\*\bin" -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName),
        (Get-ChildItem -Path "$laragonDir\bin\apache\*\bin" -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName),
        "$laragonDir\bin\composer"
    ) | Where-Object { Test-Path $_ }
    if ($laragonBinPaths) {
        $env:PATH = ($laragonBinPaths -join ';') + ';' + $env:PATH
    }
}

# 2. Habilitar extensiones criticas en php.ini (zip, gd, fileinfo, pdo_mysql, mbstring, openssl, curl, etc.)
Write-Host "[1/8] Configurando extensiones de PHP en Laragon (zip, gd, pdo_mysql, intl, etc.)..."
if (Test-Path "$laragonDir\bin\php") {
    $phpIniFiles = Get-ChildItem -Path "$laragonDir\bin\php" -Filter "php.ini" -Recurse -ErrorAction SilentlyContinue
    foreach ($iniFile in $phpIniFiles) {
        try {
            $iniText = Get-Content $iniFile.FullName -Raw
            $extensions = @('zip', 'gd', 'fileinfo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'pdo_sqlite', 'sqlite3', 'exif', 'intl', 'bcmath', 'sodium')
            foreach ($ext in $extensions) {
                # Descomentar extension si existe con punto y coma
                $iniText = $iniText -replace "(?m)^\s*;\s*extension\s*=\s*$ext\b.*$", "extension=$ext"
                $iniText = $iniText -replace "(?m)^\s*;\s*extension\s*=\s*php_$ext\.dll\b.*$", "extension=php_$ext.dll"
                
                # Si no existe la linea activa, agregarla
                if ($iniText -notmatch "(?m)^\s*extension\s*=\s*(php_)?$ext(\.dll)?\b") {
                    $iniText += "`nextension=$ext`n"
                }
            }
            Set-Content -Path $iniFile.FullName -Value $iniText -NoNewline
        } catch {}
    }
}

# 3. Preparar archivo .env
Write-Host "[2/8] Configurando variables de entorno (.env)..."
$envFile = "$ProjectDir\.env"
$envExample = "$ProjectDir\.env.example"
if (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
    Copy-Item $envExample $envFile
}

if (Test-Path $envFile) {
    $content = Get-Content $envFile -Raw
    $content = $content -replace '(?m)^APP_URL=.*', "APP_URL=$appUrl"
    $content = $content -replace '(?m)^DB_DATABASE=.*', "DB_DATABASE=$dbName"
    
    if ($content -notmatch '(?m)^CLOUD_SYNC_URL=') { $content += "`nCLOUD_SYNC_URL=https://bolsas.plasticosmyf.com`n" }
    if ($content -notmatch '(?m)^CLOUD_SYNC_TOKEN=') { $content += "`nCLOUD_SYNC_TOKEN=jsbolsas_secure_sync_token_2026`n" }
    if ($content -notmatch '(?m)^NODE_ROLE=') { $content += "`nNODE_ROLE=local`n" }

    Set-Content -Path $envFile -Value $content -NoNewline
}

# 4. Registrar dominio en archivo hosts de Windows
Write-Host "[3/8] Registrando dominio virtual jsbolsas.test en hosts..."
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
if (Test-Path $hostsFile) {
    try {
        $hostsContent = Get-Content $hostsFile -Raw
        if ($hostsContent -notmatch [regex]::Escape("jsbolsas.test")) {
            Add-Content -Path $hostsFile -Value "`n127.0.0.1      jsbolsas.test #laragon magic!" -ErrorAction SilentlyContinue
        }
    } catch {}
}

# 5. Generar VirtualHost en Apache para acceso local y via red/JSVPN
Write-Host "[4/8] Generando Host Virtual Apache para jsbolsas.test y acceso por red..."
$sitesDir = "$laragonDir\etc\apache2\sites-enabled"
if (-not (Test-Path $sitesDir)) {
    New-Item -ItemType Directory -Path $sitesDir -Force | Out-Null
}

$siteLocal = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias 'Wi-Fi*','Ethernet*','Conexión*' -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -notlike '169.254*' -and $_.IPAddress -notlike '127.*' } | Select-Object -ExpandProperty IPAddress -First 1)
if (-not $siteLocal) { $siteLocal = '127.0.0.1' }

$siteVpn = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias '*JSVPN*','*Tailscale*' -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -like '100.*' } | Select-Object -ExpandProperty IPAddress -First 1)
if (-not $siteVpn) { $siteVpn = '127.0.0.1' }

$rootPath = ($ProjectDir.Replace('\', '/')) + "/public"

$vhostContent = @"
<VirtualHost *:80>
    DocumentRoot "$rootPath"
    ServerName jsbolsas.test
    ServerAlias *.jsbolsas.test $siteLocal $siteVpn
    <Directory "$rootPath">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@
Set-Content -Path "$sitesDir\auto.jsbolsas.test.conf" -Value $vhostContent

# 6. Recrear enlace simbolico storage (public\storage -> storage\app\public)
Write-Host "[5/8] Enlazando almacenamiento publico (storage:link)..."
$targetStorage = "$ProjectDir\storage\app\public"
$publicStorage = "$ProjectDir\public\storage"
if (-not (Test-Path $targetStorage)) { New-Item -ItemType Directory -Path $targetStorage -Force | Out-Null }
cmd /c "if exist `"$publicStorage`" ( rmdir /s /q `"$publicStorage`" )"
cmd /c "mklink /J `"$publicStorage`" `"$targetStorage`""

# 7. Localizar PHP y MySQL para base de datos y migraciones
Write-Host "[6/8] Inicializando base de datos y migraciones limpias de fabrica..."
$phpExe = "php"
$foundPhp = Get-ChildItem -Path "$laragonDir\bin\php" -Filter "php.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
if ($foundPhp) { $phpExe = $foundPhp.FullName }

$mysqlExe = "mysql"
$foundMysql = Get-ChildItem -Path "$laragonDir\bin\mysql\*\bin" -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
if ($foundMysql) { $mysqlExe = $foundMysql.FullName }

# Asegurar que MySQL este en ejecucion
Start-Service -Name "mysql" -ErrorAction SilentlyContinue
Start-Service -Name "MySQL" -ErrorAction SilentlyContinue

$mysqldProc = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqldProc) {
    $foundMysqld = Get-ChildItem -Path "$laragonDir\bin\mysql\*\bin" -Filter "mysqld.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($foundMysqld) {
        Start-Process -FilePath $foundMysqld.FullName -WindowStyle Hidden
        Start-Sleep -Seconds 3
    }
}

# Crear Base de Datos jsbolsas_db
if (Test-Path $mysqlExe) {
    Start-Process -FilePath $mysqlExe -ArgumentList "-u root -e ""CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;""" -WindowStyle Hidden -Wait
}

# Inicializar Laravel
if (Test-Path $phpExe) {
    Start-Process -FilePath $phpExe -ArgumentList "artisan key:generate --force" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan migrate --force" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan db:seed --force" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan factory:reset-tests --force" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan optimize:clear" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
}

# 8. Configurar inicio automatico y preferencias de Laragon
Write-Host "[7/8] Configurando preferencias de inicio automatico de Laragon..."
$usrDir = "$laragonDir\usr"
if (-not (Test-Path $usrDir)) { New-Item -ItemType Directory -Path $usrDir -Force | Out-Null }
$laragonIni = "$usrDir\laragon.ini"
$iniPreferences = "[preferences]`nRunAtStartup=-1`nAutoVirtualHosts=-1`nMinimizeWhenRun=-1`nAutoStart=-1`n"
Set-Content -Path $laragonIni -Value $iniPreferences -NoNewline

# 9. Reiniciar Apache para aplicar php.ini y recargar DNS
Write-Host "[8/8] Reiniciando servicios web de Laragon..."
Get-Process -Name "httpd" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

$httpdExe = Get-ChildItem -Path "$laragonDir\bin\apache\*\bin" -Filter "httpd.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
$httpdConf = Get-ChildItem -Path "$laragonDir\bin\apache\*\conf" -Filter "httpd.conf" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1

if ($httpdExe -and $httpdConf) {
    $apacheHome = (Resolve-Path "$($httpdExe.DirectoryName)\..").Path
    Start-Process -FilePath $httpdExe.FullName -ArgumentList "-f `"$($httpdConf.FullName)`"" -WorkingDirectory $apacheHome -WindowStyle Hidden
}

Start-Process -FilePath "ipconfig" -ArgumentList "/flushdns" -WindowStyle Hidden -Wait

Write-Host "=========================================================="
Write-Host "   ¡LARAGON Y JSBOLSAS PRO CONFIGURADOS EXITOSAMENTE!"
Write-Host "=========================================================="
