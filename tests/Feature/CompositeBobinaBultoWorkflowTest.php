<?php

namespace Tests\Feature;

use App\Http\Controllers\BagFactoryWebController;
use App\Models\BagLabelPrint;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompositeBobinaBultoWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $operator;
    protected User $warehouse;
    protected BagMachine $machine;
    protected BagProduct $compositeProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'  => 'Admin Owner',
            'email' => 'admin@factory.com',
            'role'  => 'superadmin',
        ]);

        $this->supervisor = User::factory()->create([
            'name'  => 'Supervisor Calidad',
            'email' => 'supervisor@factory.com',
            'role'  => 'supervisor',
        ]);

        $this->operator = User::factory()->create([
            'name'  => 'Operario Bambi',
            'email' => 'operario@factory.com',
            'role'  => 'operario',
        ]);

        $this->warehouse = User::factory()->create([
            'name'  => 'Almacenista Central',
            'email' => 'almacen@factory.com',
            'role'  => 'almacen',
        ]);

        $this->machine = BagMachine::create([
            'code'      => 'EXT-01',
            'name'      => 'Extrusora Bambi 01',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $this->compositeProduct = BagProduct::create([
            'name'                        => 'BOBINA BAMBI 3.4 PESO VARIABLE',
            'sku'                         => 'BAMB-34',
            'category'                    => 'Bobinas',
            'sale_unit'                   => 'KG',
            'millar_per_bulto'            => 1.0,
            'is_variable_quantity'        => true,
            'is_composite_rolls'          => true,
            'suggested_rolls_per_package' => 9,
            'unit_weight_kg'              => 1.0,
            'real_total_weight_kg'        => 1.0,
            'price'                       => 2.50,
            'target_units_per_shift'      => 10,
            'is_active'                   => true,
        ]);
    }

    public function test_ficha_tecnica_identifies_composite_bobina_bulto(): void
    {
        $this->assertTrue($this->compositeProduct->is_variable_quantity);
        $this->assertTrue($this->compositeProduct->is_composite_rolls);
        $this->assertTrue($this->compositeProduct->isCompositeBobinaBulto());
        $this->assertEquals(9, $this->compositeProduct->suggested_rolls_per_package);

        // Actualizar ficha técnica desde web
        $response = $this->actingAs($this->admin)->put(route('products.technical.update', $this->compositeProduct->id), [
            'name'                        => 'BOBINA BAMBI 3.6 PESO VARIABLE',
            'category'                    => 'Bobinas',
            'sku'                         => 'BAMB-36',
            'sale_unit'                   => 'KG',
            'is_variable_quantity'        => '1',
            'is_composite_rolls'          => '1',
            'suggested_rolls_per_package' => 10,
            'target_units_per_shift'      => 8,
            'price'                       => 2.80,
            'millar_per_bulto'            => 1.0,
        ]);

        $response->assertSessionHasNoErrors();
        $this->compositeProduct->refresh();
        $this->assertEquals('BOBINA BAMBI 3.6 PESO VARIABLE', $this->compositeProduct->name);
        $this->assertEquals(10, $this->compositeProduct->suggested_rolls_per_package);
        $this->assertTrue($this->compositeProduct->isCompositeBobinaBulto());
    }

    public function test_stage_1_operator_registers_composite_bulto_with_dynamic_rolls(): void
    {
        // 1. Iniciar turno de operario
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-OP-001',
        ]);

        // 2. Operario pesa 9 bobinitas con pesos variables
        $rollsData = [
            ['weight' => 2.50, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.10, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 1.90, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.45, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.30, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.05, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.20, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.15, 'color' => 'Transparente', 'batch' => 'L1'],
            ['weight' => 2.35, 'color' => 'Transparente', 'batch' => 'L1'],
        ];
        $totalWeight = array_sum(array_column($rollsData, 'weight')); // 20.00 Kg

        $response = $this->actingAs($this->operator, 'sanctum')->postJson('/api/bag-factory/productions/sync', [
            'shift_id'    => $shift->id,
            'productions' => [
                [
                    'sync_id'     => 'PROD-BAMB-BULTO-01',
                    'product_id'  => $this->compositeProduct->id,
                    'quantity'    => count($rollsData),
                    'weight'      => $totalWeight,
                    'recorded_at' => now()->toDateTimeString(),
                    'metadata'    => $rollsData,
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'synced_count' => 1]);

        $production = BagProduction::where('sync_id', 'PROD-BAMB-BULTO-01')->first();
        $this->assertNotNull($production);
        $this->assertEquals(9, $production->quantity);
        $this->assertEquals(20.00, (float)$production->weight);
        $this->assertEquals('pending_review', $production->status);
        $this->assertCount(9, $production->metadata);
    }

    public function test_stage_2_supervisor_scale_audits_edits_and_approves_without_destroying_bulto(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $initialRolls = [
            ['weight' => 2.50, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.10, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 1.90, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.45, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.30, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.05, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.20, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.15, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.35, 'color' => 'Natural', 'batch' => '01'],
        ];

        $prod = BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->compositeProduct->id,
            'quantity'     => 9,
            'weight'       => 20.00,
            'status'       => 'pending_review',
            'metadata'     => $initialRolls,
            'recorded_at'  => now(),
        ]);

        // 1. Supervisor ve la vista de báscula
        $scaleView = $this->actingAs($this->supervisor)->get(route('scale.index'));
        $scaleView->assertOk();
        $scaleView->assertSee('BOBINA BAMBI 3.4');
        $scaleView->assertSee('20.00 Kg');
        $scaleView->assertSee('Bobina #1:');

        // 2. Supervisor ajusta en báscula: modifica peso de Rollo 1 (2.50 -> 2.40) y elimina Rollo 9 (quedan 8 rollos)
        $adjustedRolls = [
            ['weight' => 2.40, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.10, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 1.90, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.45, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.30, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.05, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.20, 'color' => 'Natural', 'batch' => '01'],
            ['weight' => 2.15, 'color' => 'Natural', 'batch' => '01'],
        ];
        $expectedSum = 17.55; // 2.40 + 2.10 + 1.90 + 2.45 + 2.30 + 2.05 + 2.20 + 2.15

        $adjustResponse = $this->actingAs($this->supervisor)->post(route('adjust', $prod->id), [
            'product_id' => $this->compositeProduct->id,
            'user_id'    => $this->operator->id,
            'rolls'      => $adjustedRolls,
        ]);

        $adjustResponse->assertSessionHasNoErrors();
        $prod->refresh();
        $this->assertEquals(8, $prod->quantity);
        $this->assertEquals(17.55, (float)$prod->weight);
        $this->assertEquals(20.00, (float)$prod->original_weight);
        $this->assertCount(8, $prod->metadata);

        // 3. Supervisor aprueba el bulto compuesto
        $approveResponse = $this->actingAs($this->supervisor)->post(route('approve', $prod->id));
        $approveResponse->assertSessionHasNoErrors();

        // El registro NO debe dividirse en 8 registros sueltos; debe permanecer como 1 bulto compuesto íntegro
        $this->assertEquals(1, BagProduction::count());
        $prod->refresh();
        $this->assertEquals('approved', $prod->status);
        $this->assertEquals(8, $prod->quantity);
        $this->assertEquals(17.55, (float)$prod->weight);
        $this->assertNotEmpty($prod->qr_code);
        $this->assertCount(8, $prod->metadata);
    }

    public function test_stage_3_pos80_label_generation_in_all_scopes(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $prod = BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->compositeProduct->id,
            'quantity'     => 8,
            'weight'       => 17.55,
            'status'       => 'approved',
            'qr_code'      => 'PKG-BAMB-TEST01',
            'metadata'     => [
                ['weight' => 2.40],
                ['weight' => 2.10],
                ['weight' => 1.90],
                ['weight' => 2.45],
                ['weight' => 2.30],
                ['weight' => 2.05],
                ['weight' => 2.20],
                ['weight' => 2.15],
            ],
            'recorded_at'  => now(),
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
        ]);

        $controller = new BagFactoryWebController();

        // A) Scope BULTO -> 1 Etiqueta Master de Bulto con peso total sumado
        $bultoLabels = $controller->formatLabelsList($prod, 'bulto');
        $this->assertCount(1, $bultoLabels);
        $this->assertEquals('1 BULTO (8 BOBINAS)', $bultoLabels[0]['presentation_info']);
        $this->assertEquals(17.55, $bultoLabels[0]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01', $bultoLabels[0]['qr_code']);
        $this->assertEquals('bulto', $bultoLabels[0]['label_type']);

        // B) Scope KIT -> 1 Master (17.55 Kg) + 8 Hijas con sus pesos exactos (Total 9 etiquetas)
        $kitLabels = $controller->formatLabelsList($prod, 'kit');
        $this->assertCount(9, $kitLabels);

        // Master
        $this->assertEquals('1 BULTO (8 BOBINAS)', $kitLabels[0]['presentation_info']);
        $this->assertEquals(17.55, $kitLabels[0]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01', $kitLabels[0]['qr_code']);
        $this->assertEquals('bulto', $kitLabels[0]['label_type']);

        // Hijas
        $this->assertEquals('BOBINA BAMBI 3.4 PESO VARIABLE - BOBINA 1/8', $kitLabels[1]['product_name']);
        $this->assertEquals(2.40, $kitLabels[1]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01-R1', $kitLabels[1]['qr_code']);
        $this->assertEquals('bobina', $kitLabels[1]['label_type']);

        $this->assertEquals('BOBINA BAMBI 3.4 PESO VARIABLE - BOBINA 2/8', $kitLabels[2]['product_name']);
        $this->assertEquals(2.10, $kitLabels[2]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01-R2', $kitLabels[2]['qr_code']);

        $this->assertEquals('BOBINA BAMBI 3.4 PESO VARIABLE - BOBINA 8/8', $kitLabels[8]['product_name']);
        $this->assertEquals(2.15, $kitLabels[8]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01-R8', $kitLabels[8]['qr_code']);

        // C) Scope MILLAR (Individual) -> 8 Etiquetas Hijas individuales
        $millarLabels = $controller->formatLabelsList($prod, 'millar');
        $this->assertCount(8, $millarLabels);
        $this->assertEquals(2.40, $millarLabels[0]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01-R1', $millarLabels[0]['qr_code']);
        $this->assertEquals(2.15, $millarLabels[7]['weight_kg']);
        $this->assertEquals('PKG-BAMB-TEST01-R8', $millarLabels[7]['qr_code']);

        // D) Verificación de Vista Ticket (Previsualización no incrementa contador de auditoría)
        $this->assertEquals(0, BagLabelPrint::count());
        $ticketView = $this->actingAs($this->supervisor)->get(route('ticket', ['id' => $prod->id, 'scope' => 'kit']));
        $ticketView->assertOk();
        $ticketView->assertSee('1 BULTO (8 BOBINAS)');
        $ticketView->assertSee('17.55 Kg');
        $ticketView->assertSee('PKG-BAMB-TEST01-R1');
        $this->assertEquals(0, BagLabelPrint::count()); // Sigue en 0

        // E) Confirmación de Impresión física registra auditoría
        $confirmResp = $this->actingAs($this->supervisor)->postJson(route('labels.confirm_print'), [
            'labels' => $kitLabels,
        ]);
        $confirmResp->assertOk();
        $this->assertEquals(9, BagLabelPrint::count());
    }

    public function test_stage_4_warehouse_lifting_and_adjustment(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $prod = BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->compositeProduct->id,
            'quantity'     => 8,
            'weight'       => 17.55,
            'status'       => 'approved',
            'qr_code'      => 'PKG-BAMB-LIFT01',
            'metadata'     => [
                ['weight' => 2.40],
                ['weight' => 2.10],
                ['weight' => 1.90],
                ['weight' => 2.45],
                ['weight' => 2.30],
                ['weight' => 2.05],
                ['weight' => 2.20],
                ['weight' => 2.15],
            ],
            'recorded_at'  => now(),
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
        ]);

        // 1. Almacén consulta pendientes de levantamiento
        $pendingResp = $this->actingAs($this->warehouse, 'sanctum')->getJson('/api/bag-factory/lifting/pending');
        $pendingResp->assertOk();
        $pendingResp->assertJsonFragment(['qr_code' => 'PKG-BAMB-LIFT01']);

        // 2. Almacén escanea QR
        $scanResp = $this->actingAs($this->warehouse, 'sanctum')->getJson('/api/bag-factory/lifting/scan/PKG-BAMB-LIFT01');
        $scanResp->assertOk();
        $scanResp->assertJson([
            'success' => true,
            'data'    => [
                'qr_code'  => 'PKG-BAMB-LIFT01',
                'is_ready' => true,
            ],
        ]);

        // 3. Almacén confirma recepción con ajuste (ej. rollo dañado en traslado descartado)
        $liftResp = $this->actingAs($this->warehouse, 'sanctum')->postJson('/api/bag-factory/lifting/receive', [
            'items' => [
                [
                    'id'    => $prod->id,
                    'rolls' => [
                        ['weight' => 2.40],
                        ['weight' => 2.10],
                        ['weight' => 1.90],
                        ['weight' => 2.45],
                        ['weight' => 2.30],
                        ['weight' => 2.05],
                        ['weight' => 2.20],
                    ], // 7 rollos en lugar de 8 (merma de 2.15 Kg)
                ],
            ],
            'notes' => 'Rollo #8 dañado en transporte, ingresaron 7 bobinas',
        ]);

        $liftResp->assertOk();
        $liftResp->assertJson(['success' => true, 'received_count' => 1]);

        $prod->refresh();
        $this->assertEquals('lifted', $prod->status);
        $this->assertEquals($this->warehouse->id, $prod->lifted_by);
        $this->assertNotNull($prod->lifted_at);
        $this->assertEquals(7, $prod->quantity);
        $this->assertEquals(15.40, (float)$prod->weight); // 17.55 - 2.15 = 15.40
        $this->assertStringContainsString('Rollo #8 dañado', $prod->metadata['lifting_notes'] ?? '');
    }
}
