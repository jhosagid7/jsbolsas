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

class OperatorDynamicPayrollAndQualityGradingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BagCostSetting::create([
            'resin_price_per_kg'  => 1.65,
            'shift_fixed_cost'    => 20.00,
            'daily_profit_target' => 105.00,
        ]);
    }

    public function test_operator_dynamic_daily_salary_for_5_6_7_workdays(): void
    {
        $op6 = User::factory()->create([
            'name'               => 'Operario 6 Dias',
            'email'              => 'op6@test.com',
            'role'               => 'operario',
            'weekly_salary'      => 90.00,
            'work_days_per_week' => 6,
        ]);

        $op5 = User::factory()->create([
            'name'               => 'Operario 5 Dias',
            'email'              => 'op5@test.com',
            'role'               => 'operario',
            'weekly_salary'      => 90.00,
            'work_days_per_week' => 5,
        ]);

        $op7 = User::factory()->create([
            'name'               => 'Operario 7 Dias',
            'email'              => 'op7@test.com',
            'role'               => 'operario',
            'weekly_salary'      => 90.00,
            'work_days_per_week' => 7,
        ]);

        $this->assertEquals(15.00, $op6->daily_salary);
        $this->assertEquals(18.00, $op5->daily_salary);
        $this->assertEquals(12.86, $op7->daily_salary);
    }

    public function test_labor_tariff_calculation_based_on_product_target(): void
    {
        $op = User::factory()->create([
            'name'               => 'Operario Tarifa',
            'email'              => 'tarifa@test.com',
            'role'               => 'operario',
            'weekly_salary'      => 90.00,
            'work_days_per_week' => 6, // daily salary = $15.00
        ]);

        $product = BagProduct::create([
            'name'                   => 'Bolsa 10x15 Cal 1.5',
            'sku'                    => 'B1015',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 10.0,
            'target_units_per_shift' => 5,
            'unit_weight_kg'         => 1.50,
            'real_total_weight_kg'   => 15.00,
            'cost'                   => 25.00,
            'price'                  => 45.00,
            'is_active'              => true,
        ]);

        $tariffs = $op->calculateLaborTariff($product);

        $this->assertEquals(15.00, $tariffs['daily_salary']);
        $this->assertEquals(3.00, $tariffs['package_tariff']); // 15 / 5
        $this->assertEquals(0.30, $tariffs['fraction_tariff']); // 3 / 10
    }

    public function test_product_breakdown_and_weight_quality_grading(): void
    {
        $product = BagProduct::create([
            'name'                   => 'Bolsa 8x12 Cal 1.0',
            'sku'                    => 'B812',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 10.0,
            'target_units_per_shift' => 5,
            'unit_weight_kg'         => 1.00,
            'real_total_weight_kg'   => 10.00,
            'cost'                   => 16.50,
            'price'                  => 35.00,
            'is_active'              => true,
        ]);

        // Breakdown test: 25 millares => 2 packages of 10, plus 5 loose millares
        $breakdown = $product->calculateBreakdown(25.0);
        $this->assertEquals(2.0, $breakdown['completed_packages']);
        $this->assertEquals(5.0, $breakdown['fractional_units']);
        $this->assertFalse($breakdown['is_package_completed']);

        // Quality Grading: Theoretical for 1 package (10kg)
        // 1. Grade B (Optimal, within +/- 3% -> 10.1 kg is +1.0%)
        $gradeB = $product->calculateWeightQualityGrade(10.10, 1.0, 0.0);
        $this->assertEquals('B', $gradeB['grade']);
        $this->assertEquals(1.00, $gradeB['deviation_percent']);

        // 2. Grade A (Overweight > +3% -> 10.5 kg is +5.0%)
        $gradeA = $product->calculateWeightQualityGrade(10.50, 1.0, 0.0);
        $this->assertEquals('A', $gradeA['grade']);
        $this->assertEquals(5.00, $gradeA['deviation_percent']);

        // 3. Grade C (Underweight < -3% -> 9.5 kg is -5.0%)
        $gradeC = $product->calculateWeightQualityGrade(9.50, 1.0, 0.0);
        $this->assertEquals('C', $gradeC['grade']);
        $this->assertEquals(-5.00, $gradeC['deviation_percent']);
    }

    public function test_api_sync_evaluates_grading_and_fractional_labor_retention(): void
    {
        $operator = User::factory()->create([
            'name'                 => 'Carlos Operario',
            'email'                => 'carlos@test.com',
            'role'                 => 'operario',
            'weekly_salary'        => 90.00,
            'work_days_per_week'   => 6, // $15/day
            'pay_partial_packages' => false,
        ]);

        $machine = BagMachine::create([
            'code'      => 'EXT01',
            'name'      => 'Extrusora 1',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $product = BagProduct::create([
            'name'                   => 'Bolsa Comercial 10k',
            'sku'                    => 'BC10',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 10.0,
            'target_units_per_shift' => 5, // Tariff = $3/pkg, $0.30/millar
            'unit_weight_kg'         => 1.00,
            'real_total_weight_kg'   => 10.00,
            'cost'                   => 16.50,
            'price'                  => 35.00,
            'is_active'              => true,
        ]);

        $shift = BagShift::create([
            'user_id'    => $operator->id,
            'machine_id' => $machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-SYNC-01',
        ]);

        // Sync production: 12 millares (1 full package of 10 millares + 2 loose millares)
        $response = $this->actingAs($operator, 'sanctum')->postJson('/api/bag-factory/productions/sync', [
            'shift_sync_id' => 'SHIFT-SYNC-01',
            'productions'   => [
                [
                    'sync_id'     => 'PROD-001',
                    'product_id'  => $product->id,
                    'quantity'    => 12.0,
                    'weight'      => 12.0,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        $response->assertStatus(200);

        $prod = BagProduction::where('sync_id', 'PROD-001')->first();
        $this->assertNotNull($prod);
        $this->assertEquals(1.0, (float)$prod->completed_packages_count);
        $this->assertEquals(2.0, (float)$prod->fractional_units);
        $this->assertFalse((bool)$prod->is_package_completed);
        $this->assertEquals(3.60, (float)$prod->labor_earned_amount);
        $this->assertEquals(0.60, (float)$prod->labor_retained_amount);
        $this->assertEquals('B', $prod->weight_quality_grade);
        $this->assertStringContainsString('EXT01-B', $prod->effective_batch_code);

        $shiftEarnings = $operator->getShiftEarnings($shift->id);
        $this->assertEquals(3.60, $shiftEarnings['earned']);
        $this->assertEquals(3.00, $shiftEarnings['available']);
        $this->assertEquals(0.60, $shiftEarnings['retained']);

        $weeklyEarnings = $operator->getWeeklyEarnings();
        $this->assertEquals(3.60, $weeklyEarnings['earned']);
        $this->assertEquals(3.00, $weeklyEarnings['available']);
        $this->assertEquals(0.60, $weeklyEarnings['retained']);
    }

    public function test_collaborative_fraction_completion_releases_retained_amount(): void
    {
        $op1 = User::factory()->create([
            'name'                 => 'Turno Dia Operario',
            'email'                => 'op1@test.com',
            'role'                 => 'operario',
            'weekly_salary'        => 90.00,
            'work_days_per_week'   => 6,
            'pay_partial_packages' => false,
        ]);

        $op2 = User::factory()->create([
            'name'                 => 'Turno Noche Operario',
            'email'                => 'op2@test.com',
            'role'                 => 'operario',
            'weekly_salary'        => 90.00,
            'work_days_per_week'   => 6,
            'pay_partial_packages' => false,
        ]);

        $machine = BagMachine::create([
            'code'      => 'SEL01',
            'name'      => 'Selladora 1',
            'type'      => 'selladora',
            'is_active' => true,
        ]);

        $product = BagProduct::create([
            'name'                   => 'Bolsa Asa 10k',
            'sku'                    => 'ASA10',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 10.0,
            'target_units_per_shift' => 5,
            'unit_weight_kg'         => 1.00,
            'real_total_weight_kg'   => 10.00,
            'cost'                   => 16.50,
            'price'                  => 35.00,
            'is_active'              => true,
        ]);

        // Shift 1: op1 leaves 4 loose units open
        $shift1 = BagShift::create([
            'user_id'    => $op1->id,
            'machine_id' => $machine->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(8),
            'status'     => 'closed',
            'sync_id'    => 'SHIFT-01',
        ]);

        $this->actingAs($op1, 'sanctum')->postJson('/api/bag-factory/productions/sync', [
            'shift_sync_id' => 'SHIFT-01',
            'productions'   => [
                [
                    'sync_id'     => 'PROD-FRAC-1',
                    'product_id'  => $product->id,
                    'quantity'    => 4.0, // 4 loose millares, 0 full packages
                    'weight'      => 4.0,
                    'recorded_at' => now()->subHours(4)->toIso8601String(),
                ],
            ],
        ]);

        $prod1 = BagProduction::where('sync_id', 'PROD-FRAC-1')->first();
        $this->assertFalse((bool)$prod1->is_package_completed);
        $this->assertEquals(1.20, (float)$prod1->labor_retained_amount);

        // Shift 2: op2 on same machine completes 1 package of same product
        $shift2 = BagShift::create([
            'user_id'    => $op2->id,
            'machine_id' => $machine->id,
            'shift_type' => 'nocturno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-02',
        ]);

        $this->actingAs($op2, 'sanctum')->postJson('/api/bag-factory/productions/sync', [
            'shift_sync_id' => 'SHIFT-02',
            'productions'   => [
                [
                    'sync_id'     => 'PROD-FRAC-2',
                    'product_id'  => $product->id,
                    'quantity'    => 10.0, // 1 complete package
                    'weight'      => 10.0,
                    'recorded_at' => now()->toIso8601String(),
                ],
            ],
        ]);

        $prod2 = BagProduction::where('sync_id', 'PROD-FRAC-2')->first();
        $this->assertTrue((bool)$prod2->is_package_completed);

        // Verify that op1's previous fraction is now completed and retained amount is released
        $prod1->refresh();
        $this->assertTrue((bool)$prod1->is_package_completed);
        $this->assertEquals(0.00, (float)$prod1->labor_retained_amount);
        $this->assertEquals($prod2->id, $prod1->completed_by_production_id);

        // op1's available earnings now includes the released $1.20
        $op1Earnings = $op1->getShiftEarnings($shift1->id);
        $this->assertEquals(1.20, $op1Earnings['earned']);
        $this->assertEquals(1.20, $op1Earnings['available']);
        $this->assertEquals(0.00, $op1Earnings['retained']);
    }

    public function test_payroll_web_index_and_user_crud(): void
    {
        $admin = User::factory()->create([
            'name'     => 'Super Administrador',
            'email'    => 'superadmin@test.com',
            'role'     => 'superadmin',
            'profile'  => 'superadmin',
        ]);

        $operator = User::factory()->create([
            'name'                 => 'Operario Web Test',
            'email'                => 'opweb@test.com',
            'role'                 => 'operario',
            'weekly_salary'        => 100.00,
            'work_days_per_week'   => 5,
            'pay_partial_packages' => true,
        ]);

        // 1. Check Payroll index view renders properly
        $response = $this->actingAs($admin)->get(route('bag-factory.payroll'));
        $response->assertStatus(200);
        $response->assertSee('Nómina y Rendimiento por Metas');
        $response->assertSee('Operario Web Test');

        // 2. Check User Store with payroll fields
        $storeResp = $this->actingAs($admin)->post(route('users.store'), [
            'name'                 => 'Nuevo Operario 7D',
            'email'                => 'op7d@test.com',
            'password'             => 'secret123',
            'role'                 => 'operario',
            'weekly_salary'        => 120.00,
            'work_days_per_week'   => 7,
            'pay_partial_packages' => 1,
        ]);
        $storeResp->assertSessionHas('status');

        $newUser = User::where('email', 'op7d@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals(120.00, (float)$newUser->weekly_salary);
        $this->assertEquals(7, (int)$newUser->work_days_per_week);
        $this->assertTrue((bool)$newUser->pay_partial_packages);

        // 3. Check User Update
        $updateResp = $this->actingAs($admin)->put(route('users.update', $newUser->id), [
            'name'                 => 'Nuevo Operario Actualizado',
            'email'                => 'op7d@test.com',
            'role'                 => 'operario',
            'weekly_salary'        => 140.00,
            'work_days_per_week'   => 6,
            'pay_partial_packages' => 0,
        ]);
        $updateResp->assertSessionHas('status');

        $newUser->refresh();
        $this->assertEquals('Nuevo Operario Actualizado', $newUser->name);
        $this->assertEquals(140.00, (float)$newUser->weekly_salary);
        $this->assertEquals(6, (int)$newUser->work_days_per_week);
        $this->assertFalse((bool)$newUser->pay_partial_packages);
    }

    public function test_api_sync_auto_sanitizes_weight_entered_in_grams_from_scale(): void
    {
        $operator = User::factory()->create(['role' => 'operario']);
        $product = BagProduct::create([
            'name'                   => 'Bolsa Alta Fuelle 25x65',
            'sku'                    => 'B2565-GRM',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 1.0,
            'target_units_per_shift' => 25,
            'unit_weight_kg'         => 1.20,
            'real_total_weight_kg'   => 1.20,
            'cost'                   => 1.68,
            'price'                  => 2.50,
            'is_active'              => true,
        ]);

        $shift = BagShift::create([
            'user_id'    => $operator->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-GRAM-SYNC-01',
        ]);

        // Simular que el operario pesó 10 millares y la báscula mandó 12030 gramos
        $response = $this->actingAs($operator, 'sanctum')->postJson('/api/bag-factory/productions/sync', [
            'shift_sync_id' => 'SHIFT-GRAM-SYNC-01',
            'productions'   => [
                [
                    'sync_id'     => 'SYNC-GRAM-TEST-01',
                    'product_id'  => $product->id,
                    'quantity'    => 10,
                    'weight'      => 12030.0, // 12030 gramos
                    'recorded_at' => now()->toDateTimeString(),
                ],
            ],
        ]);

        $response->assertStatus(200);
        $prod = BagProduction::where('sync_id', 'SYNC-GRAM-TEST-01')->first();
        $this->assertNotNull($prod);
        // Debe haberse convertido automáticamente a 12.0300 Kg
        $this->assertEquals(12.0300, (float)$prod->weight);
        $this->assertEquals('B', $prod->weight_quality_grade);
    }
}
