<?php

namespace Tests\Feature;

use App\Http\Controllers\BagFactoryWebController;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\ProductionFormula;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ExtrusionPhaseOneTest extends TestCase
{
    use DatabaseTransactions;

    protected User $operator;
    protected BagMachine $extruder;
    protected ProductionFormula $formula;
    protected BagProduct $mangaProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'name'     => 'Carlos Extrusor',
            'email'    => 'carlos.extrusor.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $this->extruder = BagMachine::create([
            'name'      => 'Extrusora Monocapa 01',
            'code'      => 'EXT-01',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $this->formula = ProductionFormula::create([
            'name'        => 'Receta Cristal Alta Densidad',
            'code'        => 'F-CRISTAL-HD',
            'description' => '70% Virgen + 25% Recuperado + 5% Pigmento',
            'is_active'   => true,
        ]);

        $this->mangaProduct = BagProduct::create([
            'name'                  => 'Manga 22 Baja Plana',
            'sku'                   => 'MANG-22-BP',
            'category'              => 'Manga',
            'material_type'         => 'Baja Plana',
            'width_inch'            => 22.0, // Ancho 22 cm
            'gusset_cm'             => 5.0,  // Fuelle 5 cm
            'gauge_caliber'         => 3.0,  // Calibre C-3
            'unit_weight_kg'        => 25.0,
            'millar_per_bulto'      => 1.0,
            'sale_unit'             => 'BOBINA',
            'is_variable_quantity'  => true,
            'is_active'             => true,
            'production_formula_id' => $this->formula->id,
        ]);
    }

    /** @test */
    public function it_formats_dimensions_strictly_in_centimeters()
    {
        $formatted = $this->mangaProduct->getDimensionsCmFormatted();
        $this->assertEquals('ANCHO: 22 cm | FUELLE: 5 cm | C-3', $formatted);
    }

    /** @test */
    public function operator_can_store_extrusion_bobina_with_active_formula()
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        $response = $this->actingAs($this->operator)
            ->post('/operario/produccion/batch', [
                'machine_id'            => $this->extruder->id,
                'product_id'            => $this->mangaProduct->id,
                'quantity'              => 1,
                'weight'                => 28.5, // 28.50 Kg bobina pesada
                'production_formula_id' => $this->formula->id,
                'print_mode'            => 'save_only',
            ]);

        $response->assertRedirect(route('operator.station'));

        $this->assertDatabaseHas('bag_productions', [
            'bag_shift_id'          => $shift->id,
            'user_id'               => $this->operator->id,
            'product_id'            => $this->mangaProduct->id,
            'production_formula_id' => $this->formula->id,
            'weight'                => 28.5,
            'is_waste_record'       => false,
        ]);
    }

    /** @test */
    public function operator_can_store_direct_extrusion_bobina_without_selecting_finished_bag()
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        $response = $this->actingAs($this->operator)
            ->post('/operario/produccion/batch', [
                'machine_id'             => $this->extruder->id,
                'is_extrusion_direct'    => 1,
                'extrusion_width_cm'     => 22.0,
                'extrusion_has_gusset'   => 1,
                'extrusion_gusset_cm'    => 5.0,
                'extrusion_caliber'      => 3.0,
                'extrusion_material_type'=> 'Baja Densidad',
                'quantity'               => 1,
                'weight'                 => 25.5,
                'production_formula_id'  => $this->formula->id,
                'print_mode'             => 'save_only',
            ]);

        $response->assertRedirect(route('operator.station'));

        $this->assertDatabaseHas('bag_products', [
            'width_inch' => 22.0,
            'gusset_cm'  => 5.0,
            'sale_unit'  => 'BOBINA',
        ]);

        $this->assertDatabaseHas('bag_productions', [
            'bag_shift_id'          => $shift->id,
            'user_id'               => $this->operator->id,
            'production_formula_id' => $this->formula->id,
            'weight'                => 25.5,
            'quantity'              => 1,
            'is_waste_record'       => false,
        ]);
    }

    /** @test */
    public function operator_can_record_calibration_waste_merma()
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        $response = $this->actingAs($this->operator)
            ->post('/operario/produccion/batch', [
                'machine_id'            => $this->extruder->id,
                'is_waste_record'       => 1,
                'production_formula_id' => $this->formula->id,
                'waste_weight_kg'       => 4.25, // 4.25 Kg de purga
            ]);

        $response->assertRedirect(route('operator.station'));

        $this->assertDatabaseHas('bag_productions', [
            'bag_shift_id'          => $shift->id,
            'user_id'               => $this->operator->id,
            'production_formula_id' => $this->formula->id,
            'waste_weight_kg'       => 4.25,
            'is_waste_record'       => true,
            'weight_quality_grade'  => 'MERMA',
        ]);
    }

    /** @test */
    public function labels_include_centimeters_and_formula_and_no_approver_field()
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        $production = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->operator->id,
            'product_id'               => $this->mangaProduct->id,
            'production_formula_id'    => $this->formula->id,
            'machine_id'               => $this->extruder->id,
            'quantity'                 => 1,
            'weight'                   => 25.0,
            'original_weight'          => 25.0,
            'waste_weight_kg'          => 0,
            'is_waste_record'          => false,
            'recorded_at'              => now(),
            'status'                   => 'pending_review',
            'is_printed'               => false,
            'weight_quality_grade'     => 'B',
            'weight_deviation_percent' => 0,
            'completed_packages_count' => 1,
            'fractional_units'         => 0,
            'is_package_completed'     => true,
            'labor_earned_amount'      => 3.0,
            'labor_retained_amount'    => 0,
            'qr_code'                  => 'PKG-TEST-001',
            'sync_id'                  => 'PROD-EXT-' . uniqid(),
            'metadata'                 => [],
        ]);

        $controller = new BagFactoryWebController();
        $labels = $controller->formatLabelsList($production, 'bulto');

        $this->assertNotEmpty($labels);
        $firstLabel = $labels[0];

        // Verificar dimensiones en cm y receta
        $this->assertStringContainsString('ANCHO: 22 cm', $firstLabel['presentation_info']);
        $this->assertStringContainsString('FUELLE: 5 cm', $firstLabel['presentation_info']);
        $this->assertStringContainsString('RECETA: RECETA CRISTAL ALTA DENSIDAD', $firstLabel['presentation_info']);
        $this->assertEquals(25.0, $firstLabel['weight_kg']);
    }

    /** @test */
    public function it_smartly_normalizes_decimal_calibers_to_commercial_format()
    {
        $productC15Decimal = BagProduct::create([
            'name'                  => 'Manga 30 Plana',
            'sku'                   => 'MANG-30-PL-DEC',
            'width_inch'            => 30.0,
            'gusset_cm'             => 0,
            'gauge_caliber'         => 0.0150,
            'unit_weight_kg'        => 25.0,
            'sale_unit'             => 'BOBINA',
            'is_variable_quantity'  => true,
            'is_active'             => true,
        ]);

        $this->assertEquals('ANCHO: 30 cm | C-15', $productC15Decimal->getDimensionsCmFormatted());

        $productC3 = BagProduct::create([
            'name'                  => 'Manga 22 Plana',
            'sku'                   => 'MANG-22-PL-3',
            'width_inch'            => 22.0,
            'gusset_cm'             => 0,
            'gauge_caliber'         => 3.0,
            'unit_weight_kg'        => 25.0,
            'sale_unit'             => 'BOBINA',
            'is_variable_quantity'  => true,
            'is_active'             => true,
        ]);

        $this->assertEquals('ANCHO: 22 cm | C-3', $productC3->getDimensionsCmFormatted());
    }

    /** @test */
    public function operator_can_set_roll_destination_for_sealing_and_direct_sale()
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        // 1. Bobina para Selladora
        $responseSealing = $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'is_extrusion_direct'     => 1,
            'extrusion_width_cm'      => 30,
            'extrusion_has_gusset'    => 0,
            'extrusion_material_type' => 'Baja Densidad',
            'extrusion_caliber'       => '0.0150', // Debe normalizarse a C-15
            'quantity'                => 1,
            'weight'                  => 15.0,
            'machine_id'              => $this->extruder->id,
            'roll_destination'        => 'sealing',
            'print_mode'              => 'save_only',
        ]);

        $responseSealing->assertRedirect();
        $prodSealing = BagProduction::latest('id')->first();
        $this->assertEquals('sealing', $prodSealing->metadata['roll_destination']);
        $this->assertEquals(15.0, $prodSealing->metadata['rolls'][0]['weight']);
        $this->assertEquals(15.0, $prodSealing->product->gauge_caliber);
        // El lote de extrusión debe tener máquina e iniciales de Carlos Extrusor (-CE) y terminar allí (sin sufijo de grado -A, -B o -C al final)
        $this->assertStringContainsString('-EXT-01-CE', $prodSealing->effective_batch_code);
        $this->assertStringEndsWith('-CE', $prodSealing->effective_batch_code);
        $this->assertStringNotContainsString('-EXT-01-CE-', $prodSealing->effective_batch_code);

        // 2. Bobina Venta Directa (Zanahoria)
        $responseDirect = $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'is_extrusion_direct'     => 1,
            'extrusion_width_cm'      => 40,
            'extrusion_has_gusset'    => 0,
            'extrusion_material_type' => 'Baja Densidad',
            'extrusion_caliber'       => '3',
            'quantity'                => 1,
            'weight'                  => 28.0,
            'machine_id'              => $this->extruder->id,
            'roll_destination'        => 'direct_sale',
            'print_mode'              => 'save_only',
        ]);

        $responseDirect->assertRedirect();
        $prodDirect = BagProduction::latest('id')->first();
        $this->assertEquals('direct_sale', $prodDirect->metadata['roll_destination']);
        $this->assertEquals(28.0, $prodDirect->metadata['rolls'][0]['weight']);
        $this->assertEquals('BOB-BAJA-40-C3-PL', $prodDirect->product->sku);
    }

    /** @test */
    public function sealing_operator_can_mount_bobina_and_link_bags_production_traceability()
    {
        // 1. Crear operario sellador y máquina selladora
        $sealer = User::create([
            'name'     => 'Pedro Sellador',
            'email'    => 'pedro.sellador.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $sealerMachine = BagMachine::create([
            'name'      => 'Selladora Lateral 01',
            'code'      => 'SEL-01',
            'type'      => 'selladora',
            'is_active' => true,
        ]);

        // 2. Extrusor abre turno y fabrica una bobina para sellar
        $extShift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        $bobina = BagProduction::create([
            'bag_shift_id'          => $extShift->id,
            'user_id'               => $this->operator->id, // Carlos Extrusor (-CE)
            'product_id'            => $this->mangaProduct->id,
            'machine_id'            => $this->extruder->id,
            'quantity'              => 1,
            'weight'                => 20.0,
            'status'                => 'pending_review',
            'recorded_at'           => now(),
            'metadata'              => [
                'roll_destination' => 'sealing',
            ],
        ]);

        // 3. Sellador abre turno en la máquina selladora
        $shiftResponse = $this->actingAs($sealer)->post(route('operator.open_shift'), [
            'machine_id' => $sealerMachine->id,
            'shift_type' => 'diurno',
        ]);
        $shiftResponse->assertRedirect(route('operator.station'));

        $shift = BagShift::where('user_id', $sealer->id)->where('status', 'open')->first();
        $this->assertNotNull($shift);

        // 4. Sellador monta la bobina en la selladora
        $mountResponse = $this->actingAs($sealer)->post(route('operator.mount_bobina'), [
            'bobina_id' => $bobina->id,
        ]);
        $mountResponse->assertRedirect(route('operator.station'));
        $shift->refresh();
        $this->assertEquals($bobina->id, $shift->mounted_bobina_id);
        $this->assertEquals($bobina->id, $shift->mounted_bobina->id);

        // 5. Crear producto bolsa terminada compatible con bobina 22cm Baja Fuelle
        $hieloProduct = BagProduct::create([
            'name'                  => 'Bolsa 22x50 Baja Fuelle C-15',
            'sku'                   => 'BOL-2250-F-C15',
            'category'              => 'Bolsa Fuelle',
            'material_type'         => 'Baja Densidad',
            'width_inch'            => 22.0,
            'length_inch'           => 50.0,
            'gusset_cm'             => 5.0,
            'gauge_caliber'         => 15.0,
            'sale_unit'             => 'BULTO',
            'millar_per_bulto'      => 1.0,
            'unit_weight_kg'        => 5.0,
            'is_variable_quantity'  => false,
            'is_active'             => true,
        ]);

        // 6. Sellador registra producción de bolsas
        $prodResponse = $this->actingAs($sealer)->post(route('operator.store_batch'), [
            'product_id' => $hieloProduct->id,
            'quantity'   => 1,
            'weight'     => 5.0,
            'machine_id' => $sealerMachine->id,
            'print_mode' => 'save_only',
        ]);
        $prodResponse->assertRedirect(route('operator.station'));

        $bagProd = BagProduction::latest('id')->first();
        $this->assertEquals($hieloProduct->id, $bagProd->product_id);
        // Verificar trazabilidad de bobina origen
        $this->assertEquals($bobina->id, $bagProd->metadata['source_bobina_id']);
        $this->assertEquals($bobina->effective_batch_code, $bagProd->metadata['source_bobina_batch']);
        $this->assertStringContainsString('-EXT-01-CE', $bagProd->metadata['source_bobina_batch']);
        $this->assertEquals('Carlos Extrusor', $bagProd->metadata['source_bobina_operator']);

        // 7. Verificar que el ticket de la bolsa refleje el lote origen de la bobina
        $ticketResponse = $this->actingAs($sealer)->get(route('ticket', ['id' => $bagProd->id, 'scope' => 'bulto']));
        $ticketResponse->assertStatus(200);
        $ticketResponse->assertSee('BOBINA ORIGEN:');
        $ticketResponse->assertSee($bobina->effective_batch_code);

        // 8. Verificar cálculo dinámico de Saldo y Consumo en la Bobina Madre (20 kg inicial - 5 kg sellados = 15 kg)
        $bobina->refresh();
        $this->assertEquals(5.0, $bobina->consumed_weight);
        $this->assertEquals(15.0, $bobina->remaining_weight);
        $this->assertFalse($bobina->is_consumed);

        // 9. Verificar que en la estación del operario se muestre el saldo de 15.00 Kg y el botón desmontar
        $stationView = $this->actingAs($sealer)->get(route('operator.station'));
        $stationView->assertStatus(200);
        $stationView->assertSee('Saldo: 15.00 Kg');
        $stationView->assertSee('Desmontar');
        $stationView->assertSee('Agotar');

        // 10. Sellador puede desmontar la bobina y esta queda disponible en piso con su saldo de 15 Kg
        $unmountResponse = $this->actingAs($sealer)->post(route('operator.unmount_bobina'));
        $unmountResponse->assertRedirect(route('operator.station'));
        $shift->refresh();
        $this->assertNull($shift->mounted_bobina_id);

        // 11. Sellador puede volver a montarla si lo desea
        $remountResponse = $this->actingAs($sealer)->post(route('operator.mount_bobina'), [
            'bobina_id' => $bobina->id,
        ]);
        $remountResponse->assertRedirect(route('operator.station'));
        $shift->refresh();
        $this->assertEquals($bobina->id, $shift->mounted_bobina_id);

        // 12. Sellador puede marcar la bobina como agotada/terminada al acabarse
        $agotarResponse = $this->actingAs($sealer)->post(route('operator.mark_bobina_consumed'), [
            'bobina_id' => $bobina->id,
        ]);
        $agotarResponse->assertRedirect(route('operator.station'));
        $shift->refresh();
        $this->assertNull($shift->mounted_bobina_id);
        $bobina->refresh();
        $this->assertTrue($bobina->is_consumed);

        // 13. La bobina agotada ya no aparece en la bandeja de bobinas disponibles
        $stationAfterAgotar = $this->actingAs($sealer)->get(route('operator.station'));
        $stationAfterAgotar->assertDontSee('Lote: ' . $bobina->effective_batch_code);
    }

    /** @test */
    public function it_strictly_enforces_bobina_compatibility_and_blocks_incompatible_bags()
    {
        $sealer = User::create([
            'name'     => 'Pedro Sellador Compat',
            'email'    => 'pedro.compat.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $sealerMachine = BagMachine::create([
            'name'      => 'Selladora Lateral 02',
            'code'      => 'SEL-02',
            'type'      => 'selladora',
            'is_active' => true,
        ]);

        // Bobina Madre: 22 cm, Alta Densidad, Con Fuelle (4 cm)
        $bobinaProduct = BagProduct::create([
            'name'                 => 'BOBINA ALTA FUELLE 22X60',
            'sku'                  => 'BOB-ALTA-22-F',
            'category'             => 'Bobina',
            'material_type'        => 'Alta Densidad',
            'width_inch'           => 22.0,
            'gusset_cm'            => 4.0,
            'gauge_caliber'        => 1.5,
            'unit_weight_kg'       => 20.0,
            'millar_per_bulto'     => 1.0,
            'sale_unit'            => 'BOBINA',
            'is_variable_quantity' => true,
            'is_active'            => true,
        ]);

        $shiftExt = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->extruder->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-EXT-' . uniqid(),
        ]);

        $bobina = BagProduction::create([
            'bag_shift_id'          => $shiftExt->id,
            'user_id'               => $this->operator->id,
            'product_id'            => $bobinaProduct->id,
            'production_formula_id' => $this->formula->id,
            'quantity'              => 1,
            'weight'                => 20.0,
            'status'                => 'approved',
            'batch_code'            => 'EXT-01-ALTA22',
            'is_waste_record'       => false,
            'recorded_at'           => now(),
            'sync_id'               => 'PROD-' . uniqid(),
        ]);

        // Turno del sellador y montaje de la bobina
        $sealerShift = BagShift::create([
            'user_id'    => $sealer->id,
            'machine_id' => $sealerMachine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-SEL-' . uniqid(),
        ]);
        $sealerShift->mountBobina($bobina->id);

        // Bolsa Incompatible 1: Mismo ancho y fuelle pero Baja Densidad
        $incompatibleMaterialBag = BagProduct::create([
            'name'                 => 'BOLSA BAJA FUELLE 22X45',
            'sku'                  => 'BOL-BAJA-22-F',
            'category'             => 'Bolsa',
            'material_type'        => 'Baja Densidad',
            'width_inch'           => 22.0,
            'gusset_cm'            => 4.0,
            'unit_weight_kg'       => 5.0,
            'millar_per_bulto'     => 1.0,
            'sale_unit'            => 'MILLAR',
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $respMat = $this->actingAs($sealer)->post(route('operator.store_batch'), [
            'product_id' => $incompatibleMaterialBag->id,
            'quantity'   => 1,
            'weight'     => 5.0,
            'machine_id' => $sealerMachine->id,
            'print_mode' => 'save_only',
        ]);
        $respMat->assertRedirect(route('operator.station'));
        $respMat->assertSessionHas('error');
        $this->assertStringContainsString('Alta Densidad', session('error'));
        $this->assertStringContainsString('Baja Densidad', session('error'));

        // Bolsa Incompatible 2: Mismo ancho y material pero Plana (sin fuelle)
        $incompatibleGussetBag = BagProduct::create([
            'name'                 => 'BOLSA ALTA PLANA 22X50',
            'sku'                  => 'BOL-ALTA-22-P',
            'category'             => 'Bolsa',
            'material_type'        => 'Alta Densidad',
            'width_inch'           => 22.0,
            'gusset_cm'            => 0.0,
            'unit_weight_kg'       => 5.0,
            'millar_per_bulto'     => 1.0,
            'sale_unit'            => 'MILLAR',
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $respGusset = $this->actingAs($sealer)->post(route('operator.store_batch'), [
            'product_id' => $incompatibleGussetBag->id,
            'quantity'   => 1,
            'weight'     => 5.0,
            'machine_id' => $sealerMachine->id,
            'print_mode' => 'save_only',
        ]);
        $respGusset->assertRedirect(route('operator.station'));
        $respGusset->assertSessionHas('error');
        $this->assertStringContainsString('Con Fuelle', session('error'));
        $this->assertStringContainsString('Plana', session('error'));

        // Bolsa Incompatible 3: Mismo material y fuelle pero Ancho 30 cm
        $incompatibleWidthBag = BagProduct::create([
            'name'                 => 'BOLSA ALTA FUELLE 30X60',
            'sku'                  => 'BOL-ALTA-30-F',
            'category'             => 'Bolsa',
            'material_type'        => 'Alta Densidad',
            'width_inch'           => 30.0,
            'gusset_cm'            => 4.0,
            'unit_weight_kg'       => 5.0,
            'millar_per_bulto'     => 1.0,
            'sale_unit'            => 'MILLAR',
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $respWidth = $this->actingAs($sealer)->post(route('operator.store_batch'), [
            'product_id' => $incompatibleWidthBag->id,
            'quantity'   => 1,
            'weight'     => 5.0,
            'machine_id' => $sealerMachine->id,
            'print_mode' => 'save_only',
        ]);
        $respWidth->assertRedirect(route('operator.station'));
        $respWidth->assertSessionHas('error');
        $this->assertStringContainsString('ancho', session('error'));

        // Bolsa Totalmente Compatible: 22 cm, Alta Densidad, Con Fuelle
        $compatibleBag = BagProduct::create([
            'name'                 => 'BOLSA ALTA FUELLE 22X50',
            'sku'                  => 'BOL-ALTA-22-F-OK',
            'category'             => 'Bolsa',
            'material_type'        => 'Alta Densidad',
            'width_inch'           => 22.0,
            'gusset_cm'            => 4.0,
            'unit_weight_kg'       => 5.0,
            'millar_per_bulto'     => 1.0,
            'sale_unit'            => 'MILLAR',
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $respCompat = $this->actingAs($sealer)->post(route('operator.store_batch'), [
            'product_id' => $compatibleBag->id,
            'quantity'   => 1,
            'weight'     => 5.0,
            'machine_id' => $sealerMachine->id,
            'print_mode' => 'save_only',
        ]);
        $respCompat->assertRedirect(route('operator.station'));
        $respCompat->assertSessionHas('success');

        // Verificar trazabilidad correcta
        $sealedProd = BagProduction::where('product_id', $compatibleBag->id)->first();
        $this->assertNotNull($sealedProd);
        $this->assertEquals($bobina->id, $sealedProd->metadata['source_bobina_id']);
        $this->assertEquals($bobina->effective_batch_code, $sealedProd->metadata['source_bobina_batch']);
    }
}
