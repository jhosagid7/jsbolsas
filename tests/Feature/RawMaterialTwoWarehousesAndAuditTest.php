<?php

namespace Tests\Feature;

use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\ProductionFormula;
use App\Models\RawMaterial;
use App\Models\RawMaterialAudit;
use App\Models\RawMaterialStock;
use App\Models\RawMaterialTransfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawMaterialTwoWarehousesAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterial $virgenMaterial;
    protected BagMachine $extruder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'     => 'Administrador Planta',
            'email'    => 'admin.planta.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
        ]);

        $this->virgenMaterial = RawMaterial::create([
            'name'           => 'Resina Virgen 7000F',
            'code'           => '7000F',
            'base_price'     => 2.55,
            'transport_cost' => 0.13,
            'surcharge'      => 0.13,
            'final_price'    => 2.81,
            'is_active'      => true,
        ]);

        $this->extruder = BagMachine::create([
            'name'      => 'Extrusora Principal #1',
            'code'      => 'EXT-01',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_initializes_stock_in_both_warehouses()
    {
        // 1. Cargar 100 sacos (2500 Kg) en Almacén Principal
        $response = $this->actingAs($this->admin)
            ->post('/raw-materials/stocks/init', [
                'raw_material_id' => $this->virgenMaterial->id,
                'warehouse_type'  => 'principal',
                'sacos_count'     => 100,
                'kg_per_saco'     => 25.0,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('raw_material_stocks', [
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'principal',
            'sacos_count'     => 100.0,
            'total_kg'        => 2500.0,
        ]);
    }

    /** @test */
    public function it_transfers_sacos_from_principal_to_planta_pie_de_tolva()
    {
        // Inicializar 50 sacos en Almacén Principal
        RawMaterialStock::create([
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'principal',
            'sacos_count'     => 50.0,
            'total_kg'        => 1250.0,
        ]);

        // Transferir 20 sacos a Planta
        $response = $this->actingAs($this->admin)
            ->post('/raw-materials/transfers', [
                'raw_material_id'   => $this->virgenMaterial->id,
                'sacos_transferred' => 20,
                'kg_per_saco'       => 25.0, // 500 Kg
                'notes'             => 'Entrega semanal para arranque de extrusora',
            ]);

        $response->assertRedirect();

        // Almacén Principal debe quedar con 30 sacos (750 Kg)
        $this->assertDatabaseHas('raw_material_stocks', [
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'principal',
            'sacos_count'     => 30.0,
            'total_kg'        => 750.0,
        ]);

        // Depósito Planta debe tener 20 sacos (500 Kg)
        $this->assertDatabaseHas('raw_material_stocks', [
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'planta',
            'sacos_count'     => 20.0,
            'total_kg'        => 500.0,
        ]);

        // Trazabilidad registrada
        $this->assertDatabaseHas('raw_material_transfers', [
            'raw_material_id'   => $this->virgenMaterial->id,
            'from_warehouse'    => 'principal',
            'to_warehouse'      => 'planta',
            'sacos_transferred' => 20.0,
            'weight_kg'         => 500.0,
        ]);
    }

    /** @test */
    public function it_calculates_weekly_saturday_mass_balance_and_variance()
    {
        $shift = BagShift::create([
            'user_id'    => $this->admin->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subDays(2),
            'status'     => 'closed',
            'sync_id'    => 'SHIFT-TEST-001',
        ]);

        // 1. Bobinas buenas fabricadas: 450 Kg
        BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->admin->id,
            'machine_id'               => $this->extruder->id,
            'quantity'                 => 18,
            'weight'                   => 450.0,
            'original_weight'          => 450.0,
            'waste_weight_kg'          => 0,
            'is_waste_record'          => false,
            'recorded_at'              => now()->subDays(2),
            'status'                   => 'approved',
            'is_printed'               => true,
            'weight_quality_grade'     => 'B',
            'completed_packages_count' => 18,
            'sync_id'                  => 'PROD-TEST-GOOD',
        ]);

        // 2. Merma / Purga de calibración descartada: 35 Kg
        BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->admin->id,
            'machine_id'               => $this->extruder->id,
            'quantity'                 => 0,
            'weight'                   => 0,
            'original_weight'          => 0,
            'waste_weight_kg'          => 35.0,
            'is_waste_record'          => true,
            'recorded_at'              => now()->subDays(2),
            'status'                   => 'approved',
            'is_printed'               => false,
            'weight_quality_grade'     => 'MERMA',
            'sync_id'                  => 'PROD-TEST-WASTE',
        ]);

        // 3. Ejecutar Arqueo de los Sábados:
        // Stock inicial en planta = 100 Kg
        // Entregado en semana = 500 Kg
        // Total disponible = 600 Kg
        // Conteo físico sábado = 105 Kg
        // Consumo real calculado = 600 - 105 = 495 Kg
        // Justificado en planta = 450 (bobinas) + 35 (purga) = 485 Kg
        // Descuadre / Variación = 495 - 485 = 10 Kg de diferencia inexplicable
        $response = $this->actingAs($this->admin)
            ->post('/raw-materials/audit', [
                'audit_date'              => now()->format('Y-m-d'),
                'period_start'            => now()->subDays(6)->format('Y-m-d'),
                'period_end'              => now()->format('Y-m-d'),
                'initial_plant_kg'        => 100.0,
                'transferred_to_plant_kg' => 500.0,
                'final_counted_plant_kg'  => 105.0,
                'good_production_kg'      => 450.0,
                'waste_purga_kg'          => 35.0,
                'notes'                   => 'Arqueo semanal de prueba con 10 kg de tolerancia',
            ]);

        $response->assertRedirect(route('raw_materials.audit'));

        $this->assertDatabaseHas('raw_material_audits', [
            'consumed_kg'        => 495.0,
            'good_production_kg' => 450.0,
            'waste_purga_kg'     => 35.0,
            'variance_kg'        => 10.0,
            'status'             => 'approved',
        ]);
    }

    /** @test */
    public function it_automatically_deducts_ingredients_from_plant_hopper_when_storing_extrusion_bobina()
    {
        $linealMaterial = RawMaterial::create([
            'name'           => 'Resina Lineal 0209',
            'code'           => '0209',
            'base_price'     => 2.40,
            'transport_cost' => 0.10,
            'surcharge'      => 0.10,
            'final_price'    => 2.60,
            'is_active'      => true,
        ]);

        // 1. Stock inicial en Planta: 100 Kg de Virgen y 100 Kg de Lineal
        RawMaterialStock::create([
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'planta',
            'sacos_count'     => 4.0,
            'total_kg'        => 100.0,
        ]);

        RawMaterialStock::create([
            'raw_material_id' => $linealMaterial->id,
            'warehouse_type'  => 'planta',
            'sacos_count'     => 4.0,
            'total_kg'        => 100.0,
        ]);

        // 2. Crear fórmula activa: 70% Virgen + 30% Lineal
        $formula = ProductionFormula::create([
            'name'      => 'Fórmula Cristal 70-30',
            'code'      => 'FC7030',
            'is_active' => true,
        ]);
        $formula->createNewVersion([
            ['raw_material_id' => $this->virgenMaterial->id, 'quantity_kg' => 70.0],
            ['raw_material_id' => $linealMaterial->id, 'quantity_kg' => 30.0],
        ], $this->admin->id);

        // 3. Crear turno de extrusora
        $operator = User::create([
            'name'     => 'Operario Extrusor Test',
            'email'    => 'operario.deduct.' . uniqid() . '@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $shift = BagShift::create([
            'user_id'    => $operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        // 4. Extruir y pesar bobina de 20.0 Kg
        $response = $this->actingAs($operator)->post(route('operator.store_batch'), [
            'is_extrusion_direct'     => 1,
            'extrusion_width_cm'      => 22.0,
            'extrusion_has_gusset'    => 1,
            'extrusion_gusset_cm'     => 5.0,
            'extrusion_caliber'       => 3.0,
            'extrusion_material_type' => 'Alta Densidad',
            'production_formula_id'   => $formula->id,
            'quantity'                => 1,
            'weight'                  => 20.0,
            'waste_weight_kg'         => 0.0,
            'machine_id'              => $this->extruder->id,
            'print_mode'              => 'save_only',
        ]);

        $response->assertRedirect(route('operator.station'));
        $response->assertSessionHas('success');

        // 5. Verificar que en Planta Tolva se rebajaron exactamente los Kg por receta:
        // Virgen: 100 - (20 * 0.70) = 86.0 Kg
        // Lineal: 100 - (20 * 0.30) = 94.0 Kg
        $stockVirgen = RawMaterialStock::where('raw_material_id', $this->virgenMaterial->id)
            ->where('warehouse_type', 'planta')
            ->first();
        $this->assertEquals(86.0, (float)$stockVirgen->total_kg);

        $stockLineal = RawMaterialStock::where('raw_material_id', $linealMaterial->id)
            ->where('warehouse_type', 'planta')
            ->first();
        $this->assertEquals(94.0, (float)$stockLineal->total_kg);

        // 6. Verificar que la producción guardó la trazabilidad de deducciones en metadata
        $prod = BagProduction::where('weight', 20.0)->latest('id')->first();
        $this->assertNotNull($prod);
        $this->assertNotEmpty($prod->metadata['raw_material_deductions']);
        $this->assertEquals(14.0, (float)$prod->metadata['raw_material_deductions'][0]['quantity_kg']);
        $this->assertEquals(6.0, (float)$prod->metadata['raw_material_deductions'][1]['quantity_kg']);
    }

    /** @test */
    public function it_restores_plant_hopper_stock_when_preloaded_bobina_is_deleted()
    {
        $linealMaterial = RawMaterial::create([
            'name'           => 'Resina Lineal 0209 B',
            'code'           => '0209B',
            'base_price'     => 2.40,
            'transport_cost' => 0.10,
            'surcharge'      => 0.10,
            'final_price'    => 2.60,
            'is_active'      => true,
        ]);

        RawMaterialStock::create([
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'planta',
            'sacos_count'     => 4.0,
            'total_kg'        => 100.0,
        ]);

        RawMaterialStock::create([
            'raw_material_id' => $linealMaterial->id,
            'warehouse_type'  => 'planta',
            'sacos_count'     => 4.0,
            'total_kg'        => 100.0,
        ]);

        $formula = ProductionFormula::create([
            'name'      => 'Fórmula Cristal 50-50',
            'code'      => 'FC5050',
            'is_active' => true,
        ]);
        $formula->createNewVersion([
            ['raw_material_id' => $this->virgenMaterial->id, 'quantity_kg' => 50.0],
            ['raw_material_id' => $linealMaterial->id, 'quantity_kg' => 50.0],
        ], $this->admin->id);

        $operator = User::create([
            'name'     => 'Operario Delete Test',
            'email'    => 'operario.del.' . uniqid() . '@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $shift = BagShift::create([
            'user_id'    => $operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        // Pesar bobina de 30 Kg (15 Kg de cada material)
        $this->actingAs($operator)->post(route('operator.store_batch'), [
            'is_extrusion_direct'     => 1,
            'extrusion_width_cm'      => 22.0,
            'extrusion_has_gusset'    => 1,
            'extrusion_gusset_cm'     => 5.0,
            'extrusion_caliber'       => 3.0,
            'extrusion_material_type' => 'Alta Densidad',
            'production_formula_id'   => $formula->id,
            'quantity'                => 1,
            'weight'                  => 30.0,
            'machine_id'              => $this->extruder->id,
            'print_mode'              => 'save_only',
        ]);

        $prod = BagProduction::where('weight', 30.0)->latest('id')->first();
        $this->assertNotNull($prod);

        // Verificar que bajó a 85 Kg
        $this->assertEquals(85.0, (float)RawMaterialStock::where('raw_material_id', $this->virgenMaterial->id)->where('warehouse_type', 'planta')->value('total_kg'));

        // Operador elimina el pesaje no impreso
        $delResp = $this->actingAs($operator)->delete(route('operator.destroy_batch', $prod->id));
        $delResp->assertRedirect(route('operator.station'));

        // Verificar que el stock en Planta Tolva fue restaurado a 100 Kg
        $this->assertEquals(100.0, (float)RawMaterialStock::where('raw_material_id', $this->virgenMaterial->id)->where('warehouse_type', 'planta')->value('total_kg'));
        $this->assertEquals(100.0, (float)RawMaterialStock::where('raw_material_id', $linealMaterial->id)->where('warehouse_type', 'planta')->value('total_kg'));
    }

    /** @test */
    public function it_deducts_ingredients_proportionally_when_recording_extrusion_purge_waste()
    {
        RawMaterialStock::create([
            'raw_material_id' => $this->virgenMaterial->id,
            'warehouse_type'  => 'planta',
            'sacos_count'     => 4.0,
            'total_kg'        => 100.0,
        ]);

        $formula = ProductionFormula::create([
            'name'      => 'Fórmula 100% Virgen',
            'code'      => 'F100V',
            'is_active' => true,
        ]);
        $formula->createNewVersion([
            ['raw_material_id' => $this->virgenMaterial->id, 'quantity_kg' => 100.0],
        ], $this->admin->id);

        $operator = User::create([
            'name'     => 'Operario Purga Test',
            'email'    => 'operario.purge.' . uniqid() . '@test.com',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $shift = BagShift::create([
            'user_id'    => $operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        // Registrar 4.5 Kg de purga de calibración
        $response = $this->actingAs($operator)->post(route('operator.store_batch'), [
            'machine_id'            => $this->extruder->id,
            'is_waste_record'       => 1,
            'waste_type'            => 'purga_calibracion_extrusion',
            'production_formula_id' => $formula->id,
            'waste_weight_kg'       => 4.5,
        ]);

        $response->assertRedirect(route('operator.station'));

        // El stock de virgen en Planta debe haber bajado de 100.0 a 95.5 Kg
        $stockVirgen = RawMaterialStock::where('raw_material_id', $this->virgenMaterial->id)
            ->where('warehouse_type', 'planta')
            ->first();
        $this->assertEquals(95.5, (float)$stockVirgen->total_kg);
    }
}
