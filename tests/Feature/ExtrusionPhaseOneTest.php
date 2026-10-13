<?php

namespace Tests\Feature;

use App\Http\Controllers\BagFactoryWebController;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\ProductionFormula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtrusionPhaseOneTest extends TestCase
{
    use RefreshDatabase;

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
}
