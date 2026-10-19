<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CloudSyncService;

class SyncWithCloudCommand extends Command
{
    protected $signature = 'cloud:sync {--direction=both : Dirección de sincronización (both, push, pull)} {--force : Forzar descarga completa desde el inicio}';
    protected $description = 'Sincroniza los registros industriales entre la Fábrica Local y el VPS en la Nube';

    public function handle(CloudSyncService $syncService): int
    {
        $direction = $this->option('direction') ?: 'both';
        $force = (bool)$this->option('force');
        $this->info("Iniciando sincronización con VPS ({$syncService->getCloudUrl()}) en modo: [{$direction}]" . ($force ? " [FORZANDO COMPLETO]" : "") . "...");

        $ping = $syncService->pingCloud();
        if (!$ping['connected']) {
            $this->error("❌ Error de conectividad: " . $ping['message']);
            return Command::FAILURE;
        }

        $this->info("✅ Conexión con VPS establecida. Latencia: {$ping['latency_ms']} ms.");

        if ($direction === 'push') {
            $res = $syncService->pushDeltas();
            $this->info("📤 Push: " . $res['message']);
        } elseif ($direction === 'pull') {
            $res = $syncService->pullDeltas(null, $force);
            $this->info("📥 Pull: " . $res['message']);
        } else {
            $res = $syncService->syncAll($force);
            if ($res['success']) {
                $this->info("🔄 Sincronización Bidireccional Completada:");
                $this->line("   - Subidos: " . ($res['records_pushed'] ?? 0));
                $this->line("   - Descargados: " . ($res['records_pulled'] ?? 0));
                $this->info("   " . $res['message']);
            } else {
                $this->error("❌ Falló la sincronización: " . $res['message']);
                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }
}
