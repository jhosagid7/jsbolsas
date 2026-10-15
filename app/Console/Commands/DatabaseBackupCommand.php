<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseBackupService;

class DatabaseBackupCommand extends Command
{
    /**
     * El nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = 'db:backup {--upload-drive : Subir automáticamente a Google Drive si está configurado} {--keep=30 : Días de retención de respaldos locales}';

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = 'Genera un respaldo comprimido (.sql.gz) de la base de datos y opcionalmente lo sube a Google Drive.';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('=====================================================');
        $this->info('    JSBolsas Pro - Respaldo Oficial de Base de Datos ');
        $this->info('=====================================================');

        $uploadDrive = (bool) $this->option('upload-drive');
        $keepDays = (int) $this->option('keep');

        $this->line("1. Generando volcado comprimido de la base de datos...");
        $dumpResult = $backupService->dumpDatabase();

        if (!$dumpResult['success']) {
            $this->error("❌ Error al generar el respaldo: " . $dumpResult['message']);
            return 1;
        }

        $this->info("   ✓ Archivo: {$dumpResult['filename']} ({$dumpResult['size_human']})");
        $this->line("   ✓ Ruta: {$dumpResult['path']}");

        $this->line("2. Aplicando política de rotación (retención: {$keepDays} días)...");
        $deleted = $backupService->rotateBackups($keepDays);
        $this->info("   ✓ Respaldos antiguos purgados: {$deleted}");

        if ($uploadDrive) {
            $this->line("3. Sincronizando respaldo con Google Drive...");
            $driveResult = $backupService->uploadToGoogleDrive($dumpResult['path']);

            if ($driveResult['success']) {
                $this->info("   ✅ Sincronizado exitosamente en Google Drive.");
                $this->line("   🔗 Enlace web: {$driveResult['web_link']}");
            } else {
                $this->warn("   ⚠️ No se pudo subir a Google Drive: " . $driveResult['message']);
            }
        }

        $this->info('=====================================================');
        $this->info('  ¡PROCESO DE RESPALDO FINALIZADO SATISFACTORIAMENTE!');
        $this->info('=====================================================');

        return 0;
    }
}
