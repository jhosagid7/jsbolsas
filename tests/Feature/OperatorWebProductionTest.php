<?php

namespace Tests\Feature;

use App\Models\BagCostSetting;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorWebProductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $admin;
    protected BagMachine $machine;
    protected BagProduct $productA;
    protected BagProduct $productB;

    protected function setUp(): void
    {
        parent::setUp();

        BagCostSetting::create([
            'resin_price_per_kg'  => 1.65,
            'shift_fixed_cost'    => 20.00,
            'daily_profit_target' => 105.00,
        ]);

        $this->machine = BagMachine::create([
            'name'      => 'Selladora Industrial 01',
            'code'      => 'SEL-01',
            'type'      => 'selladora',
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'name'               => 'Pedro Perez',
            'email'              => 'pedro@jsbolsas.test',
            'role'               => 'operario',
            'weekly_salary'      => 90.00,
            'work_days_per_week' => 6, // daily_salary = 15.00
        ]);

        $this->admin = User::factory()->create([
            'name'  => 'Carlos Admin',
            'email' => 'carlos@jsbolsas.test',
            'role'  => 'admin',
        ]);

        // Product A: 40x60 (Target: 20 millares, Peso Teórico: 4.50 kg/millar)
        $this->productA = BagProduct::create([
            'name'                   => 'BOLSA 40X60 TRANSPARENTE',
            'category'               => 'Bolsas',
            'sale_unit'              => 'MILLAR',
            'sku'                    => 'BOL-4060-TRA',
            'millar_per_bulto'       => 1.0,
            'unit_weight_kg'         => 4.5000,
            'real_total_weight_kg'   => 4.5000,
            'target_units_per_shift' => 20,
            'is_variable_quantity'   => false,
            'is_active'              => true,
            'price'                  => 12.00,
            'cost'                   => 7.50,
        ]);

        // Product B: 30x40 (Target: 10 millares, Peso Teórico: 2.20 kg/millar)
        $this->productB = BagProduct::create([
            'name'                   => 'BOLSA 30X40 NEGRA',
            'category'               => 'Bolsas',
            'sale_unit'              => 'MILLAR',
            'sku'                    => 'BOL-3040-NEG',
            'millar_per_bulto'       => 1.0,
            'unit_weight_kg'         => 2.2000,
            'real_total_weight_kg'   => 2.2000,
            'target_units_per_shift' => 10,
            'is_variable_quantity'   => false,
            'is_active'              => true,
            'price'                  => 8.00,
            'cost'                   => 4.50,
        ]);
    }

    public function test_operator_user_is_redirected_to_operator_station_from_dashboard(): void
    {
        $response = $this->actingAs($this->operator)->get('/dashboard');
        $response->assertRedirect(route('operator.station'));
    }

    public function test_operator_cannot_access_administrative_cost_views(): void
    {
        $response = $this->actingAs($this->operator)->get('/costs');
        $response->assertStatus(403);

        $responseF = $this->actingAs($this->operator)->get('/formulas');
        $responseF->assertStatus(403);
    }

    public function test_admin_can_access_both_dashboard_and_operator_station(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);

        $responseStation = $this->actingAs($this->admin)->get(route('operator.station'));
        $responseStation->assertStatus(200);
    }

    public function test_operator_can_open_and_close_shift_via_web(): void
    {
        $response = $this->actingAs($this->operator)->post(route('operator.open_shift'), [
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
        ]);

        $response->assertRedirect(route('operator.station'));
        $this->assertDatabaseHas('bag_shifts', [
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'status'     => 'open',
        ]);

        $shift = BagShift::where('user_id', $this->operator->id)->where('status', 'open')->first();
        $this->assertNotNull($shift);

        $closeResponse = $this->actingAs($this->operator)->post(route('operator.close_shift'), [
            'shift_id' => $shift->id,
        ]);
        $closeResponse->assertRedirect(route('operator.station'));

        $this->assertEquals('closed', $shift->fresh()->status);
    }

    public function test_batch_production_calculates_average_weight_and_quality_grades(): void
    {
        // 1. Abrir turno
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        // Caso B: 10 millares con 45.0 kg (Promedio exacto 4.50 kg -> Grado B: Óptimo)
        $responseB = $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productA->id,
            'quantity'   => 10,
            'weight'     => 45.0,
            'print_mode' => 'save_only',
        ]);
        $responseB->assertRedirect(route('operator.station'));

        $prodB = BagProduction::where('user_id', $this->operator->id)
            ->where('product_id', $this->productA->id)
            ->first();

        $this->assertNotNull($prodB);
        $this->assertEquals(10, (float)$prodB->quantity);
        $this->assertEquals(45.0, (float)$prodB->weight);
        $this->assertEquals('B', $prodB->weight_quality_grade);
        $this->assertStringContainsString('-B', $prodB->effective_batch_code);

        // Caso A: 10 millares con 50.0 kg (Promedio 5.0 kg -> +11.11% sobrepeso -> Grado A)
        $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productA->id,
            'quantity'   => 10,
            'weight'     => 50.0,
            'print_mode' => 'save_only',
        ]);

        $prodA = BagProduction::where('user_id', $this->operator->id)
            ->where('weight', 50.0)
            ->first();
        $this->assertEquals('A', $prodA->weight_quality_grade);
        $this->assertStringContainsString('-A', $prodA->effective_batch_code);

        // Caso C: 10 millares con 40.0 kg (Promedio 4.0 kg -> -11.11% subcalibre -> Grado C)
        $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productA->id,
            'quantity'   => 10,
            'weight'     => 40.0,
            'print_mode' => 'save_only',
        ]);

        $prodC = BagProduction::where('user_id', $this->operator->id)
            ->where('weight', 40.0)
            ->first();
        $this->assertEquals('C', $prodC->weight_quality_grade);
        $this->assertStringContainsString('-C', $prodC->effective_batch_code);
    }

    public function test_multiproduct_shift_target_deduction_accumulates_progress(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        // Product A target = 20. Hacemos 10 -> 50%
        $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productA->id,
            'quantity'   => 10,
            'weight'     => 45.0,
            'print_mode' => 'save_only',
        ]);

        // Product B target = 10. Hacemos 5 -> 50%
        $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productB->id,
            'quantity'   => 5,
            'weight'     => 11.0,
            'print_mode' => 'save_only',
        ]);

        $response = $this->actingAs($this->operator)->get(route('operator.station'));
        $response->assertStatus(200);

        // Validar que en la vista se comparte el 100% de cumplimiento de meta combinada
        $response->assertViewHas('combinedProgressPercent', 100.0);
    }

    public function test_operator_earnings_in_usd_accumulates_correctly_in_shift(): void
    {
        // Daily salary = 15.00 USD
        // Product A target = 20 millares -> Tarifa = 15.00 / 20 = 0.75 USD por millar
        // Product B target = 10 millares -> Tarifa = 15.00 / 10 = 1.50 USD por millar
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        // 10 millares de A -> 10 * 0.75 = 7.50 USD
        $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productA->id,
            'quantity'   => 10,
            'weight'     => 45.0,
            'print_mode' => 'save_only',
        ]);

        // 5 millares de B -> 5 * 1.50 = 7.50 USD
        $this->actingAs($this->operator)->post(route('operator.store_batch'), [
            'product_id' => $this->productB->id,
            'quantity'   => 5,
            'weight'     => 11.0,
            'print_mode' => 'save_only',
        ]);

        // Total ganado esperado = 7.50 + 7.50 = 15.00 USD
        $response = $this->actingAs($this->operator)->get(route('operator.station'));
        $response->assertStatus(200);
        $response->assertViewHas('totalEarnedUsd', 15.00);
    }

    public function test_operator_station_renders_intelligent_product_search_and_dashboard_shortcut(): void
    {
        $response = $this->actingAs($this->operator)->get(route('operator.station'));
        $response->assertStatus(200);
        $response->assertSee('productSearchInput');
        $response->assertSee('productDropdownList');
        $response->assertSee('selectedProductId');

        // Validar acceso directo en el dashboard principal
        $adminDashboard = $this->actingAs($this->admin)->get('/dashboard');
        $adminDashboard->assertStatus(200);
        $adminDashboard->assertSee(route('operator.station'));
    }

    public function test_operator_cannot_see_operator_selector_or_view_other_operator_station(): void
    {
        $operatorB = User::factory()->create([
            'name' => 'Ernesto Operario',
            'email' => 'ernesto.test@jsbolsas.test',
            'role' => 'operario',
        ]);

        // 1. El operario NO debe ver el selector en el HTML
        $response = $this->actingAs($this->operator)->get(route('operator.station'));
        $response->assertStatus(200);
        $response->assertDontSee('Ver Operador:');
        $response->assertDontSee('name="operator_id"', false);

        // 2. Si el operario intenta forzar el parámetro ?operator_id en la URL, el controlador lo ignora y forza su propio usuario
        $responseSpoof = $this->actingAs($this->operator)->get(route('operator.station', ['operator_id' => $operatorB->id]));
        $responseSpoof->assertStatus(200);
        $this->assertEquals($this->operator->id, $responseSpoof->viewData('targetUser')->id);
    }

    public function test_ticket_label_displays_quality_grade_and_batch_suffix(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $production = BagProduction::create([
            'bag_shift_id'         => $shift->id,
            'user_id'              => $this->operator->id,
            'product_id'           => $this->productA->id,
            'machine_id'           => $this->machine->id,
            'quantity'             => 10,
            'weight'               => 45.0,
            'weight_quality_grade' => 'B',
            'status'               => 'approved',
            'recorded_at'          => now(),
        ]);

        $response = $this->actingAs($this->operator)->get(route('ticket', ['id' => $production->id, 'scope' => 'bulto']));
        $response->assertStatus(200);
        $response->assertSee('-B');
        $response->assertDontSee('CALIDAD:');
    }

    public function test_operator_can_edit_preloaded_production_batch(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $production = BagProduction::create([
            'bag_shift_id'         => $shift->id,
            'user_id'              => $this->operator->id,
            'product_id'           => $this->productA->id,
            'machine_id'           => $this->machine->id,
            'quantity'             => 10,
            'weight'               => 45.0,
            'status'               => 'pending_review',
            'is_printed'           => false,
            'recorded_at'          => now(),
        ]);

        $response = $this->actingAs($this->operator)->put(route('operator.update_batch', $production->id), [
            'quantity' => 12,
            'weight'   => 54.0,
        ]);

        $response->assertRedirect(route('operator.station'));
        $this->assertEquals(12, (float)$production->fresh()->quantity);
        $this->assertEquals(54.0, (float)$production->fresh()->weight);
    }

    public function test_operator_can_delete_preloaded_production_batch(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $production = BagProduction::create([
            'bag_shift_id'         => $shift->id,
            'user_id'              => $this->operator->id,
            'product_id'           => $this->productA->id,
            'machine_id'           => $this->machine->id,
            'quantity'             => 10,
            'weight'               => 45.0,
            'status'               => 'pending_review',
            'is_printed'           => false,
            'recorded_at'          => now(),
        ]);

        $response = $this->actingAs($this->operator)->delete(route('operator.destroy_batch', $production->id));
        $response->assertRedirect(route('operator.station'));
        $this->assertDatabaseMissing('bag_productions', ['id' => $production->id]);
    }

    public function test_operator_cannot_edit_or_delete_printed_production_batch(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $production = BagProduction::create([
            'bag_shift_id'         => $shift->id,
            'user_id'              => $this->operator->id,
            'product_id'           => $this->productA->id,
            'machine_id'           => $this->machine->id,
            'quantity'             => 10,
            'weight'               => 45.0,
            'status'               => 'approved',
            'is_printed'           => true,
            'printed_at'           => now(),
            'recorded_at'          => now(),
        ]);

        // Intento de edición por operario -> Bloqueado
        $responseEdit = $this->actingAs($this->operator)->put(route('operator.update_batch', $production->id), [
            'quantity' => 15,
            'weight'   => 67.5,
        ]);
        $responseEdit->assertSessionHas('error');
        $this->assertEquals(10, (float)$production->fresh()->quantity);

        // Intento de eliminación por operario -> Bloqueado
        $responseDel = $this->actingAs($this->operator)->delete(route('operator.destroy_batch', $production->id));
        $responseDel->assertSessionHas('error');
        $this->assertDatabaseHas('bag_productions', ['id' => $production->id]);
    }

    public function test_admin_can_edit_or_delete_even_if_printed(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $production = BagProduction::create([
            'bag_shift_id'         => $shift->id,
            'user_id'              => $this->operator->id,
            'product_id'           => $this->productA->id,
            'machine_id'           => $this->machine->id,
            'quantity'             => 10,
            'weight'               => 45.0,
            'status'               => 'approved',
            'is_printed'           => true,
            'printed_at'           => now(),
            'recorded_at'          => now(),
        ]);

        // Admin sí puede editar aunque esté impreso
        $responseEdit = $this->actingAs($this->admin)->put(route('operator.update_batch', $production->id), [
            'quantity' => 20,
            'weight'   => 90.0,
        ]);
        $responseEdit->assertRedirect(route('operator.station'));
        $this->assertEquals(20, (float)$production->fresh()->quantity);

        // Admin sí puede eliminar aunque esté impreso
        $responseDel = $this->actingAs($this->admin)->delete(route('operator.destroy_batch', $production->id));
        $responseDel->assertRedirect(route('operator.station'));
        $this->assertDatabaseMissing('bag_productions', ['id' => $production->id]);
    }

    public function test_admin_can_close_supervised_operator_shift(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $response = $this->actingAs($this->admin)->post(route('operator.close_shift'), [
            'shift_id'    => $shift->id,
            'operator_id' => $this->operator->id,
        ]);

        $response->assertRedirect(route('operator.station', ['operator_id' => $this->operator->id]));
        $this->assertEquals('closed', $shift->fresh()->status);
    }
}
