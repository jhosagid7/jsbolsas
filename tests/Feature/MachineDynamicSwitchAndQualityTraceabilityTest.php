<?php

namespace Tests\Feature;

use App\Models\BagMachine;
use App\Models\BagMachineIncident;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineDynamicSwitchAndQualityTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $supervisor;
    protected BagMachine $machineA;
    protected BagMachine $machineB;
    protected BagProduct $product1;
    protected BagProduct $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operario',
            'name' => 'Carlos Operario',
        ]);

        $this->supervisor = User::factory()->create([
            'role' => 'supervisor',
            'name' => 'Maria Supervisora',
        ]);

        $this->machineA = BagMachine::create([
            'name'      => 'Extrusora Principal #1',
            'code'      => 'EXT-01',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $this->machineB = BagMachine::create([
            'name'      => 'Extrusora Secundaria #2',
            'code'      => 'EXT-02',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $this->product1 = BagProduct::create([
            'name'                 => 'Bolsa 30x40 Negra',
            'sku'                  => 'BOL-3040-N',
            'sale_format'          => 'bulto',
            'units_per_package'    => 1000,
            'weight_per_millar_kg' => 8.50,
            'is_active'            => true,
        ]);

        $this->product2 = BagProduct::create([
            'name'                 => 'Bolsa 40x50 Transparente',
            'sku'                  => 'BOL-4050-T',
            'sale_format'          => 'bulto',
            'units_per_package'    => 500,
            'weight_per_millar_kg' => 14.00,
            'is_active'            => true,
        ]);
    }

    public function test_operator_can_switch_machines_dynamically_in_same_shift(): void
    {
        // 1. Iniciar turno en Maquina A
        $responseOpen = $this->actingAs($this->operator, 'sanctum')->postJson('/api/bag-factory/shifts/open', [
            'shift_type' => 'diurno',
            'machine_id' => $this->machineA->id,
            'start_time' => now()->toIso8601String(),
            'sync_id'    => 'SHIFT-TEST-001',
        ]);

        $responseOpen->assertStatus(200);
        $shiftId = $responseOpen->json('data.id');

        // 2. Sincronizar Pesaje 1 en Maquina A y Pesaje 2 en Maquina B dentro del mismo turno
        $syncPayload = [
            'shift_id'      => $shiftId,
            'shift_sync_id' => 'SHIFT-TEST-001',
            'productions'   => [
                [
                    'sync_id'     => 'PROD-001-MA',
                    'product_id'  => $this->product1->id,
                    'machine_id'  => $this->machineA->id,
                    'quantity'    => 5,
                    'weight'      => 42.50,
                    'recorded_at' => now()->subMinutes(30)->toIso8601String(),
                ],
                [
                    'sync_id'     => 'PROD-002-MB',
                    'product_id'  => $this->product2->id,
                    'machine_id'  => $this->machineB->id, // Cambio de maquina a mitad de turno
                    'quantity'    => 3,
                    'weight'      => 42.00,
                    'recorded_at' => now()->subMinutes(10)->toIso8601String(),
                ],
            ],
        ];

        $responseSync = $this->actingAs($this->operator, 'sanctum')->postJson('/api/bag-factory/productions/sync', $syncPayload);
        $responseSync->assertStatus(200);

        // 3. Verificar persistencia de maquinas independientes por pesaje
        $prodA = BagProduction::where('sync_id', 'PROD-001-MA')->first();
        $prodB = BagProduction::where('sync_id', 'PROD-002-MB')->first();

        $this->assertNotNull($prodA);
        $this->assertNotNull($prodB);

        $this->assertEquals($this->machineA->id, $prodA->machine_id);
        $this->assertEquals($this->machineB->id, $prodB->machine_id);

        $this->assertEquals('EXT-01', $prodA->effective_machine->code);
        $this->assertEquals('EXT-02', $prodB->effective_machine->code);
    }

    public function test_machine_profile_view_aggregates_kpis_and_products_breakdown(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machineA->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-MACH-KPI',
        ]);

        // Produccion 1 en Maquina A (Aprobada)
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'machine_id'   => $this->machineA->id,
            'quantity'     => 10,
            'weight'       => 85.00,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'sync_id'      => 'PROD-KPI-1',
        ]);

        // Produccion 2 en Maquina A (Aprobada)
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product2->id,
            'machine_id'   => $this->machineA->id,
            'quantity'     => 4,
            'weight'       => 56.00,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'sync_id'      => 'PROD-KPI-2',
        ]);

        $response = $this->actingAs($this->supervisor)->get('/machines/' . $this->machineA->id);

        $response->assertStatus(200);
        $response->assertViewHas('totalKg', 141.00);
        $response->assertViewHas('totalUnits', 14.0);
        $response->assertViewHas('totalBatches', 2);
        $response->assertSee('Extrusora Principal #1');
        $response->assertSee('BOL-3040-N');
        $response->assertSee('BOL-4050-T');
    }

    public function test_supervisor_can_report_and_resolve_quality_incident_with_root_cause(): void
    {
        // 1. Reportar falla mecanica (Causa Raiz -> Maquina)
        $responseIncident1 = $this->actingAs($this->supervisor)->post('/machines/' . $this->machineA->id . '/incidents', [
            'title'         => 'Variacion de calibre en burbuja',
            'incident_type' => 'mecanica',
            'severity'      => 'alta',
            'product_id'    => $this->product1->id,
            'batch_code'    => 'L260909-EXT01',
            'description'   => 'El cabezal presenta temperatura dispareja en la zona 2.',
        ]);

        $responseIncident1->assertStatus(302);

        $incident1 = BagMachineIncident::where('title', 'Variacion de calibre en burbuja')->first();
        $this->assertNotNull($incident1);
        $this->assertEquals('maquina', $incident1->root_cause_analysis);
        $this->assertEquals('abierta', $incident1->status);

        // 2. Reportar falla de formulacion (Causa Raiz -> Formula)
        $responseIncident2 = $this->actingAs($this->supervisor)->post('/machines/' . $this->machineA->id . '/incidents', [
            'title'         => 'Rotura prematura al estirar',
            'incident_type' => 'formula',
            'severity'      => 'media',
            'product_id'    => $this->product1->id,
            'description'   => 'Falta de polietileno lineal en la mezcla.',
        ]);

        $responseIncident2->assertStatus(302);
        $incident2 = BagMachineIncident::where('title', 'Rotura prematura al estirar')->first();
        $this->assertNotNull($incident2);
        $this->assertEquals('formula', $incident2->root_cause_analysis);

        // 3. Resolver la incidencia 1
        $responseResolve = $this->actingAs($this->supervisor)->put('/machines/' . $this->machineA->id . '/incidents/' . $incident1->id . '/resolve', [
            'resolution_notes' => 'Se calibro la resistencia de zona 2 y se nivelo el flujo de aire.',
        ]);

        $responseResolve->assertStatus(302);
        $incident1->refresh();
        $this->assertEquals('resuelta', $incident1->status);
        $this->assertEquals('Se calibro la resistencia de zona 2 y se nivelo el flujo de aire.', $incident1->resolution_notes);
        $this->assertEquals($this->supervisor->id, $incident1->resolved_by);
    }

    public function test_labels_and_tickets_generate_discreet_batch_code_and_sku_qr(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machineA->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-DISCREET',
        ]);

        $prod = BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'machine_id'   => $this->machineA->id,
            'quantity'     => 1,
            'weight'       => 8.50,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'sync_id'      => 'PROD-DISCREET-01',
        ]);

        $response = $this->actingAs($this->supervisor)->get('/ticket/' . $prod->id);

        $response->assertStatus(200);
        // Debe mostrar el SKU del producto para compatibilidad universal de escaner
        $response->assertSee('BOL-3040-N');
        // Debe generar el lote discreto con el codigo de maquina
        $expectedDate = now()->format('ymd');
        $response->assertSee("L{$expectedDate}-EXT-01");
    }

    public function test_scale_audit_view_renders_correctly_with_split_and_nested_metadata(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machineA->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-SCALE-AUDIT',
        ]);

        // Producción 1: pendiente con metadata tipo lista
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'machine_id'   => $this->machineA->id,
            'quantity'     => 2,
            'weight'       => 17.00,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
            'sync_id'      => 'PROD-SCALE-PENDING',
            'metadata'     => [
                ['weight' => 8.50, 'color' => 'Negro', 'batch' => 'L01'],
                ['weight' => 8.50, 'color' => 'Negro', 'batch' => 'L02'],
            ],
        ]);

        // Producción 2: aprobada con metadata asociativa ('roll' => [...]) producto de división/split
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product2->id,
            'machine_id'   => $this->machineB->id,
            'quantity'     => 1,
            'weight'       => 14.00,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'sync_id'      => 'PROD-SCALE-APPROVED-SPLIT',
            'metadata'     => [
                'roll' => ['weight' => 14.00, 'color' => 'Transparente', 'batch' => 'L99'],
            ],
        ]);

        $response = $this->actingAs($this->supervisor)->get('/scale');

        $response->assertStatus(200);
        $response->assertSee('Bobina #1');
        $response->assertSee('Bobina #2');
        $response->assertSee('14.00 Kg');
        $response->assertSee('17.00 Kg');
    }

    public function test_reports_fabrica_view_renders_correctly_with_split_metadata(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machineA->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'closed',
            'sync_id'    => 'SHIFT-REPORTS-TEST',
        ]);

        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'machine_id'   => $this->machineA->id,
            'quantity'     => 1,
            'weight'       => 12.50,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'sync_id'      => 'PROD-REP-SPLIT',
            'metadata'     => [
                'roll' => ['weight' => 12.50, 'color' => 'Negro', 'batch' => 'L-REP'],
            ],
        ]);

        $response = $this->actingAs($this->supervisor)->get('/reports-fabrica');
        $response->assertStatus(200);
        $response->assertSee('#1: 12.5 Kg');
        $response->assertSee('(Negro)');
    }
}


