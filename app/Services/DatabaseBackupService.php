<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class DatabaseBackupService
{
    protected string $backupDir;
    protected ?string $googleDriveFolderId;
    protected ?string $googleDriveLocalPath;
    protected string $googleKeyPath;
    protected string $nodeRole;
    protected string $cloudUrl;
    protected string $syncToken;

    public function __construct()
    {
        $this->backupDir = str_replace('\\', '/', storage_path('app/backups'));
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0775, true);
        }
        @chmod($this->backupDir, 0775);

        $this->nodeRole = env('NODE_ROLE', 'local');
        $this->cloudUrl = rtrim(config('services.cloud_sync.url', env('CLOUD_SYNC_URL', 'https://bolsas.plasticosmyf.com')), '/');
        $this->syncToken = config('services.cloud_sync.token', env('CLOUD_SYNC_TOKEN', 'jsbolsas_secure_sync_token_2026'));

        $this->googleDriveFolderId = env('GOOGLE_DRIVE_FOLDER_ID');
        $this->googleDriveLocalPath = env('GOOGLE_DRIVE_LOCAL_PATH');
        $keyFile = env('GOOGLE_DRIVE_KEY_FILE', 'storage/app/google-drive-key.json');
        $resolvedKey = file_exists(base_path($keyFile)) ? base_path($keyFile) : storage_path('app/google-drive-key.json');
        $this->googleKeyPath = str_replace('\\', '/', $resolvedKey);
    }

    /**
     * Resuelve la ruta ejecutable de mysqldump según el sistema operativo
     */
    public function resolveMysqldumpBinary(): ?string
    {
        // 1. Configuración explícita
        $configured = config('database.connections.mysql.dump.dump_binary_path');
        if ($configured && file_exists($configured)) {
            return $configured;
        }

        // 2. Linux estándar
        if (file_exists('/usr/bin/mysqldump')) {
            return '/usr/bin/mysqldump';
        }

        // 3. Laragon en Windows
        $laragonGlob = glob('C:/laragon/bin/mysql/mysql-*/bin/mysqldump.exe');
        if (!empty($laragonGlob) && file_exists($laragonGlob[0])) {
            return str_replace('/', DIRECTORY_SEPARATOR, $laragonGlob[0]);
        }

        // 4. PATH del sistema
        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where.exe mysqldump' : 'which mysqldump';
        $res = Process::run($cmd);
        if ($res->successful()) {
            $lines = explode(PHP_EOL, trim($res->output()));
            if (!empty($lines[0]) && file_exists(trim($lines[0]))) {
                return trim($lines[0]);
            }
        }

        return null;
    }

    /**
     * Genera un volcado completo de la base de datos (.sql.gz)
     */
    public function dumpDatabase(?string $customFilename = null): array
    {
        $database = config('database.connections.mysql.database', 'jsbolsas_db');
        $username = config('database.connections.mysql.username', 'root');
        $password = config('database.connections.mysql.password', '');
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');

        $timestamp = now()->format('Y-m-d_His');
        $baseName = $customFilename ?: "backup_{$database}_{$timestamp}";
        $sqlPath = $this->backupDir . DIRECTORY_SEPARATOR . "{$baseName}.sql";
        $gzPath = "{$sqlPath}.gz";

        $dumpBinary = $this->resolveMysqldumpBinary();

        if ($dumpBinary) {
            $passParam = $password ? "--password=" . escapeshellarg($password) : "";
            $command = sprintf(
                '"%s" --host=%s --port=%s --user=%s %s --single-transaction --quick --routines --triggers %s > "%s"',
                $dumpBinary,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $passParam,
                escapeshellarg($database),
                $sqlPath
            );

            $result = Process::timeout(300)->run($command);

            if (!$result->successful() || !file_exists($sqlPath) || filesize($sqlPath) === 0) {
                // Fallback a volcado PDO nativo
                $this->nativePdoDump($sqlPath, $database);
            }
        } else {
            // Fallback a volcado PDO nativo si no hay binario instalado
            $this->nativePdoDump($sqlPath, $database);
        }

        if (!file_exists($sqlPath) || filesize($sqlPath) === 0) {
            return [
                'success' => false,
                'message' => 'No se pudo generar el archivo de respaldo de base de datos.',
            ];
        }

        // Comprimir con gzip para optimizar almacenamiento y velocidad de subida
        $sqlContent = file_get_contents($sqlPath);
        $gzContent = gzencode($sqlContent, 9);
        file_put_contents($gzPath, $gzContent);
        @unlink($sqlPath); // Eliminar el .sql plano dejando solo el comprimido

        // Sincronizar automáticamente con carpeta de Google Drive para Escritorio si existe
        if ($this->googleDriveLocalPath && is_dir($this->googleDriveLocalPath)) {
            $destPath = rtrim(str_replace('\\', '/', $this->googleDriveLocalPath), '/') . '/' . basename($gzPath);
            @copy($gzPath, $destPath);
        }

        return [
            'success'    => true,
            'path'       => $gzPath,
            'filename'   => basename($gzPath),
            'size_bytes' => filesize($gzPath),
            'size_human' => round(filesize($gzPath) / 1024, 2) . ' KB',
            'message'    => "Respaldo generado exitosamente: " . basename($gzPath),
        ];
    }

    /**
     * Volcado nativo mediante PDO como salvaguarda
     */
    protected function nativePdoDump(string $targetFile, string $database): void
    {
        $handle = fopen($targetFile, 'w');
        if (!$handle) {
            throw new \Exception("No se puede escribir en el archivo de respaldo: {$targetFile}");
        }

        fwrite($handle, "-- JSBolsas Pro Native Database Backup\n");
        fwrite($handle, "-- Generado: " . now()->toIso8601String() . "\n");
        fwrite($handle, "-- Base de datos: {$database}\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n\n");

        $tables = DB::select('SHOW TABLES');
        $dbKey = "Tables_in_{$database}";

        foreach ($tables as $t) {
            $tableName = $t->$dbKey ?? current((array) $t);
            $createTable = DB::selectOne("SHOW CREATE TABLE `{$tableName}`");
            $createSql = $createTable->{'Create Table'} ?? '';

            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
            fwrite($handle, "{$createSql};\n\n");

            $rows = DB::table($tableName)->get();
            if ($rows->count() > 0) {
                foreach ($rows->chunk(500) as $chunk) {
                    $insertSql = "INSERT INTO `{$tableName}` VALUES ";
                    $valuesArr = [];
                    foreach ($chunk as $row) {
                        $escapedRow = array_map(function ($val) {
                            if (is_null($val)) return 'NULL';
                            return DB::getPdo()->quote((string) $val);
                        }, (array) $row);
                        $valuesArr[] = '(' . implode(',', $escapedRow) . ')';
                    }
                    $insertSql .= implode(',', $valuesArr) . ";\n";
                    fwrite($handle, $insertSql);
                }
                fwrite($handle, "\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Rota y limpia respaldos locales antiguos (por defecto retiene 30 días)
     */
    public function rotateBackups(int $keepDays = 30): int
    {
        $files = glob($this->backupDir . DIRECTORY_SEPARATOR . 'backup_*.*');
        $threshold = now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $threshold) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Resuelve el binario ejecutable de Rclone en Linux o Windows
     */
    public function resolveRcloneBinary(): ?string
    {
        if (file_exists('C:/laragon/bin/rclone/rclone.exe')) {
            return 'C:/laragon/bin/rclone/rclone.exe';
        }

        if (file_exists('/usr/bin/rclone')) {
            return '/usr/bin/rclone';
        }

        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where.exe rclone' : 'which rclone';
        $res = Process::run($cmd);
        if ($res->successful()) {
            $lines = explode(PHP_EOL, trim($res->output()));
            if (!empty($lines[0]) && file_exists(trim($lines[0]))) {
                return trim($lines[0]);
            }
        }

        return null;
    }

    /**
     * Sube un archivo de respaldo directamente a Google Drive en la carpeta designada
     * o lo delega al VPS si este nodo es un cliente local
     */
    public function uploadToGoogleDrive(string $filePath, ?string $folderId = null): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => "El archivo a subir no existe: {$filePath}",
            ];
        }

        // Si este nodo es una máquina cliente/local, transmite de forma segura al VPS central (Zero Credentials en cliente)
        if ($this->nodeRole === 'local') {
            return $this->sendBackupToCloud($filePath);
        }

        $targetFolderId = $folderId ?: $this->googleDriveFolderId;

        if (!$targetFolderId) {
            return [
                'success' => false,
                'message' => 'No se ha configurado la variable GOOGLE_DRIVE_FOLDER_ID en el archivo .env',
            ];
        }

        // 1. Si Rclone está instalado y configurado en el servidor, subir directamente como usuario (5 TB)
        try {
            $rcloneBin = $this->resolveRcloneBinary();
            if ($rcloneBin) {
                $dest = 'gdrive:Respaldos JSBolsas';
                $rcloneRun = Process::timeout(180)->run(sprintf('"%s" copy "%s" "%s"', $rcloneBin, $filePath, $dest));
                if ($rcloneRun->successful()) {
                    Log::info("Backup {$filePath} subido exitosamente a Google Drive vía Rclone.");
                    return [
                        'success'  => true,
                        'file_id'  => basename($filePath),
                        'web_link' => "https://drive.google.com/drive/folders/{$targetFolderId}",
                        'message'  => "Respaldo cargado exitosamente en Google Drive vía Rclone.",
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Aviso Rclone: " . $e->getMessage() . ", intentando API nativa...");
        }

        try {
            $token = $this->getGoogleAccessToken();
            if (!$token) {
                throw new \Exception('No se pudo obtener el token de autorización OAuth2 de Google.');
            }

            $fileName = basename($filePath);
            $mimeType = str_ends_with($fileName, '.gz') ? 'application/gzip' : 'application/sql';

            $metadata = [
                'name'    => $fileName,
                'parents' => [$targetFolderId],
            ];

            $boundary = '-------' . uniqid();
            $delimiter = "\r\n--" . $boundary . "\r\n";
            $closeDelimiter = "\r\n--" . $boundary . "--";

            $fileContent = file_get_contents($filePath);

            $multipartBody = $delimiter;
            $multipartBody .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
            $multipartBody .= json_encode($metadata) . "\r\n";
            $multipartBody .= $delimiter;
            $multipartBody .= "Content-Type: {$mimeType}\r\n";
            $multipartBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $multipartBody .= chunk_split(base64_encode($fileContent));
            $multipartBody .= $closeDelimiter;

            $response = Http::withToken($token)
                ->withBody($multipartBody, 'multipart/related; boundary=' . $boundary)
                ->timeout(180)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true&fields=id,name,webViewLink');

            if ($response->successful()) {
                $id = $response->json('id');
                $webLink = $response->json('webViewLink') ?? "https://drive.google.com/file/d/{$id}/view";
                
                Log::info("Backup {$fileName} subido exitosamente a Google Drive. ID: {$id}");

                return [
                    'success'  => true,
                    'file_id'  => $id,
                    'web_link' => $webLink,
                    'message'  => "Respaldo cargado exitosamente en Google Drive.",
                ];
            }

            $errBody = $response->body();
            Log::error("Error subiendo backup a Google Drive: {$errBody}");

            return [
                'success' => false,
                'message' => "Google Drive API rechazó la subida: {$errBody}",
            ];
        } catch (\Throwable $e) {
            Log::error("Excepción en uploadToGoogleDrive: " . $e->getMessage());

            return [
                'success' => false,
                'message' => "Error durante la subida a Google Drive: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene el token de acceso OAuth2 usando Service Account JWT
     */
    protected function getGoogleAccessToken(): ?string
    {
        $keyData = json_decode(file_get_contents($this->googleKeyPath), true);
        if (!$keyData || empty($keyData['private_key']) || empty($keyData['client_email'])) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim = [
            'iss'   => $keyData['client_email'],
            'scope' => 'https://www.googleapis.com/auth/drive',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ];

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlClaim = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($claim)));

        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;
        $binarySignature = '';
        $signed = openssl_sign($signatureInput, $binarySignature, $keyData['private_key'], OPENSSL_ALGO_SHA256);

        if (!$signed) {
            return null;
        }

        $jwt = $signatureInput . '.' . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($binarySignature));

        $res = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        return $res->successful() ? $res->json('access_token') : null;
    }

    /**
     * Transmite el archivo de respaldo al servidor VPS en la nube para que éste lo suba a Google Drive (Zero Credentials en cliente)
     */
    public function sendBackupToCloud(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => "El archivo a enviar no existe: {$filePath}",
            ];
        }

        try {
            $fileName = basename($filePath);
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->syncToken,
            ])
            ->timeout(300)
            ->attach('backup_file', fopen($filePath, 'r'), $fileName)
            ->post("{$this->cloudUrl}/api/cloud-sync/upload-backup");

            if ($response->successful()) {
                $data = $response->json();
                $driveSub = $data['drive_result'] ?? [];

                Log::info("Backup {$fileName} transmitido al VPS exitosamente: " . json_encode($data));

                return [
                    'success'      => true,
                    'file_id'      => $driveSub['file_id'] ?? $fileName,
                    'web_link'     => $driveSub['web_link'] ?? "https://drive.google.com/drive/folders/{$this->googleDriveFolderId}",
                    'message'      => $data['message'] ?? 'Respaldo transmitido al servidor VPS y cargado en Google Drive.',
                    'file'         => $fileName,
                    'drive_result' => $driveSub,
                ];
            }

            $errMsg = $response->json('message') ?? $response->body();
            Log::error("Error transmitiendo backup al VPS: HTTP {$response->status()} - {$errMsg}");

            return [
                'success' => false,
                'message' => "El servidor VPS rechazó la recepción del respaldo: {$errMsg}",
            ];
        } catch (\Throwable $e) {
            Log::error("Excepción al transmitir backup al VPS: " . $e->getMessage());

            return [
                'success' => false,
                'message' => "Error de conexión al enviar respaldo al servidor VPS: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Ejecuta ciclo completo: Dump + Rotación + Subida opcional a Google Drive
     */
    public function runFullBackup(bool $uploadToDrive = true, int $keepDays = 30): array
    {
        $dumpResult = $this->dumpDatabase();
        if (!$dumpResult['success']) {
            return $dumpResult;
        }

        $rotated = $this->rotateBackups($keepDays);

        $driveResult = null;
        if ($uploadToDrive) {
            $driveResult = $this->uploadToGoogleDrive($dumpResult['path']);
        }

        return [
            'success'       => true,
            'dump'          => $dumpResult,
            'rotated_count' => $rotated,
            'drive'         => $driveResult,
            'message'       => $dumpResult['message'] . ($driveResult && $driveResult['success'] ? ' y sincronizado en Google Drive.' : ''),
        ];
    }
}
