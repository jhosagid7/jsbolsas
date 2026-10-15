<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\BagLabelPrint;
use App\Models\BagMachineIncident;
use App\Models\RawMaterial;
use App\Models\RawMaterialStock;
use App\Models\RawMaterialTransfer;
use App\Models\RawMaterialAudit;

class ResetFactoryProductionTests extends Command
{
    /**
     * El nombre y firma del comando en consola Artisan.
     *
     * @var string
     */
    protected $signature = 'factory:reset-tests {--force : Ejecutar sin confirmación interactiva}';

    /**
     * Descripción del comando en consola.
     *
     * @var string
     */
    protected $description = 'Reinicia a CERO todos los registros operativos de prueba (turnos, bobinas, sellado, etiquetas, mermas, traslados) y fija los stocks a 25 Kg (1 saco) por insumo en Planta Tolva.';

    public function handle(): int
    {
        $this->warn('========================================================');
        $this->warn('  JSBolsas Pro - Mecanismo de Reseteo para Pruebas Cero');
        $this->warn('========================================================');
        $this->line('Base de datos: <info>' . config('database.connections.mysql.database', 'jsbolsas_db') . '</info>');

        if (!$this->option('force') && !$this->confirm('¿Estás seguro de vaciar todos los turnos, bobinas y registros operativos para iniciar una prueba limpia?', true)) {
            $this->info('Operación cancelada por el usuario.');
            return 0;
        }

        $this->info('Iniciando limpieza operativa...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Limpieza de producción, bobinas, sellados y mermas
        $prods = DB::table('bag_productions')->count();
        DB::table('bag_productions')->truncate();
        $this->line("✓ bag_productions: <comment>{$prods}</comment> registros eliminados.");

        // 2. Limpieza de turnos operativos
        $shifts = DB::table('bag_shifts')->count();
        DB::table('bag_shifts')->truncate();
        $this->line("✓ bag_shifts: <comment>{$shifts}</comment> turnos cerrados y eliminados.");

        // 3. Limpieza de historial de etiquetas impresas
        $prints = DB::table('bag_label_prints')->count();
        DB::table('bag_label_prints')->truncate();
        $this->line("✓ bag_label_prints: <comment>{$prints}</comment> registros eliminados.");

        // 4. Limpieza de incidentes y fallas reportadas
        $incidents = DB::table('bag_machine_incidents')->count();
        DB::table('bag_machine_incidents')->truncate();
        $this->line("✓ bag_machine_incidents: <comment>{$incidents}</comment> incidencias eliminadas.");

        // 5. Limpieza de traslados entre depósitos y arqueos de balance
        $transfers = DB::table('raw_material_transfers')->count();
        DB::table('raw_material_transfers')->truncate();
        $this->line("✓ raw_material_transfers: <comment>{$transfers}</comment> traslados vaciados.");

        $audits = DB::table('raw_material_audits')->count();
        DB::table('raw_material_audits')->truncate();
        $this->line("✓ raw_material_audits: <comment>{$audits}</comment> arqueos vaciados.");

        // 6. Restablecer stocks base de materias primas
        // Planta Tolva: 25.0 Kg (exactamente 1 saco) por cada materia prima activa
        // Almacén Principal: 250.0 Kg (exactamente 10 sacos) por cada materia prima activa
        $materials = RawMaterial::where('is_active', true)->get();
        if ($materials->isEmpty()) {
            $materials = RawMaterial::all();
        }

        foreach ($materials as $mat) {
            RawMaterialStock::updateOrCreate(
                ['raw_material_id' => $mat->id, 'warehouse_type' => 'planta'],
                ['total_kg' => 25.00, 'sacos_count' => 1.0]
            );

            RawMaterialStock::updateOrCreate(
                ['raw_material_id' => $mat->id, 'warehouse_type' => 'principal'],
                ['total_kg' => 250.00, 'sacos_count' => 10.0]
            );
        }

        $totalPlanta = RawMaterialStock::where('warehouse_type', 'planta')->sum('total_kg');
        $totalPrincipal = RawMaterialStock::where('warehouse_type', 'principal')->sum('total_kg');

        $this->line("✓ Materias primas fijadas a medida base para pruebas:");
        $this->line("  • Planta Tolva: <info>{$totalPlanta} Kg</info> (" . count($materials) . " insumos x 25 Kg = " . ($totalPlanta / 25) . " sacos)");
        $this->line("  • Almacén Principal: <info>{$totalPrincipal} Kg</info> (" . count($materials) . " insumos x 250 Kg = " . ($totalPrincipal / 25) . " sacos)");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->newLine();
        $this->info('========================================================');
        $this->info('  ¡SISTEMA LISTO EN ESTADO CERO PARA PRIMER USO!');
        $this->info('  Usuarios, fórmulas, máquinas y catálogo quedan intactos.');
        $this->info('========================================================');

        return 0;
    }
}
