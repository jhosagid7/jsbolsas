<?php

namespace Tests\Feature;

use App\Models\BagCostSetting;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoryAndWorkerTargetEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        BagCostSetting::create([
            'resin_price_per_kg'        => 1.4000,
            'shift_fixed_cost'          => 20.00,
            'daily_profit_target'       => 105.00,
            'default_margin_percentage' => 45.00,
        ]);
    }

    /** @test */
    public function it_calculates_individual_worker_target_correctly_without_accumulating_per_pesaje_record()
    {
        $user = User::factory()->create(['name' => 'Operario 1', 'role' => 'operario']);
        $product = BagProduct::create([
            'name'                   => 'Bolsa 40x60',
            'sku'                    => 'B4060',
            'category'               => 'Bolsas',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 20,
            'unit_weight_kg'         => 2.1,
            'real_total_weight_kg'   => 42.0,
            'cost'                   => 58.80,
            'price'                  => 85.26,
            'target_units_per_shift' => 5, // Meta: 5 bultos por turno
            'target_daily_profit'    => 105.00,
            'is_variable_quantity'   => false,
            'is_active'              => true,
        ]);

        $shift = BagShift::create([
            'user_id'        => $user->id,
            'shift_type'     => 'diurno',
            'start_time'     => now(),
            'status'         => 'open',
            'total_packages' => 0,
            'total_weight'   => 0,
        ]);

        // Registrar 2 pesajes del mismo producto
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $user->id,
            'product_id'   => $product->id,
            'quantity'     => 2,
            'weight'       => 84.0,
            'status'       => 'approved',
            'recorded_at'  => now(),
        ]);
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $user->id,
            'product_id'   => $product->id,
            'quantity'     => 1,
            'weight'       => 42.0,
            'status'       => 'approved',
            'recorded_at'  => now(),
        ]);

        $shift->load('productions.product');
        $shift->recalculateFinancials();

        // La meta del turno debe ser 5 (definida en el producto), no 5 + 5 = 10
        $this->assertEquals(5.0, (float)$shift->target_packages);
    }

    /** @test */
    public function it_calculates_global_factory_target_as_the_sum_of_all_active_workers_targets()
    {
        $user1 = User::factory()->create(['name' => 'Operario 1', 'role' => 'operario']);
        $user2 = User::factory()->create(['name' => 'Operario 2', 'role' => 'operario']);
        $user3 = User::factory()->create(['name' => 'Operario 3', 'role' => 'operario']);

        $prodA = BagProduct::create([
            'name' => 'Bolsa 40x60', 'sku' => 'A1', 'sale_unit' => 'BULTO', 'millar_per_bulto' => 20,
            'unit_weight_kg' => 2.1, 'real_total_weight_kg' => 42.0, 'cost' => 58.80, 'price' => 85.26,
            'target_units_per_shift' => 5, 'is_active' => true,
        ]);
        $prodB = BagProduct::create([
            'name' => 'Bolsa 30x40', 'sku' => 'B1', 'sale_unit' => 'BULTO', 'millar_per_bulto' => 10,
            'unit_weight_kg' => 1.5, 'real_total_weight_kg' => 15.0, 'cost' => 21.00, 'price' => 35.00,
            'target_units_per_shift' => 10, 'is_active' => true,
        ]);
        $prodC = BagProduct::create([
            'name' => 'Bobina 10Kg', 'sku' => 'C1', 'sale_unit' => 'KG', 'millar_per_bulto' => 1,
            'unit_weight_kg' => 1.0, 'real_total_weight_kg' => 1.0, 'cost' => 1.40, 'price' => 2.50,
            'target_units_per_shift' => 15, 'is_variable_quantity' => true, 'is_active' => true,
        ]);

        // Turno 1: Meta 5, produjo 5 (100% alcanzado)
        $shift1 = BagShift::create(['user_id' => $user1->id, 'start_time' => now(), 'status' => 'open']);
        BagProduction::create(['bag_shift_id' => $shift1->id, 'user_id' => $user1->id, 'product_id' => $prodA->id, 'quantity' => 5, 'weight' => 210, 'recorded_at' => now()]);

        // Turno 2: Meta 10, produjo 10 (100% alcanzado)
        $shift2 = BagShift::create(['user_id' => $user2->id, 'start_time' => now(), 'status' => 'open']);
        BagProduction::create(['bag_shift_id' => $shift2->id, 'user_id' => $user2->id, 'product_id' => $prodB->id, 'quantity' => 10, 'weight' => 150, 'recorded_at' => now()]);

        // Turno 3: Meta 15, produjo 8 (53.3% - en proceso)
        $shift3 = BagShift::create(['user_id' => $user3->id, 'start_time' => now(), 'status' => 'open']);
        BagProduction::create(['bag_shift_id' => $shift3->id, 'user_id' => $user3->id, 'product_id' => $prodC->id, 'quantity' => 8, 'weight' => 80, 'recorded_at' => now()]);

        $shifts = collect([$shift1, $shift2, $shift3]);
        foreach ($shifts as $s) {
            $s->load('productions.product');
            $s->recalculateTotals();
            $s->recalculateFinancials();
        }

        // Meta global de la fábrica: 5 + 10 + 15 = 30 unidades
        $factoryTarget = (float)$shifts->sum('target_packages');
        // Producción real global: 5 + 10 + 8 = 23 unidades
        $factoryReal = (float)$shifts->sum('total_packages');
        // % Cumplimiento fábrica: (23 / 30) * 100 = 76.7%
        $factoryProgress = round(($factoryReal / $factoryTarget) * 100.0, 1);

        $this->assertEquals(30.0, $factoryTarget);
        $this->assertEquals(23.0, $factoryReal);
        $this->assertEquals(76.7, $factoryProgress);
        $this->assertFalse($factoryReal >= $factoryTarget);

        // Turnos individuales:
        $this->assertEquals(100.0, round(((float)$shift1->total_packages / (float)$shift1->target_packages) * 100.0, 1));
        $this->assertEquals(100.0, round(((float)$shift2->total_packages / (float)$shift2->target_packages) * 100.0, 1));
        $this->assertEquals(53.3, round(((float)$shift3->total_packages / (float)$shift3->target_packages) * 100.0, 1));
    }

    /** @test */
    public function it_displays_correct_factory_and_worker_metrics_on_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['name' => 'Operario 1', 'role' => 'operario']);
        $user2 = User::factory()->create(['name' => 'Operario 2', 'role' => 'operario']);

        $prodA = BagProduct::create([
            'name' => 'Bolsa 40x60', 'sku' => 'A1', 'sale_unit' => 'BULTO', 'millar_per_bulto' => 20,
            'unit_weight_kg' => 2.1, 'real_total_weight_kg' => 42.0, 'cost' => 58.80, 'price' => 85.26,
            'target_units_per_shift' => 5, 'is_active' => true,
        ]);

        $shift1 = BagShift::create(['user_id' => $user1->id, 'start_time' => now(), 'status' => 'open']);
        BagProduction::create(['bag_shift_id' => $shift1->id, 'user_id' => $user1->id, 'product_id' => $prodA->id, 'quantity' => 5, 'weight' => 210, 'recorded_at' => now()]);

        $shift2 = BagShift::create(['user_id' => $user2->id, 'start_time' => now(), 'status' => 'open']);
        BagProduction::create(['bag_shift_id' => $shift2->id, 'user_id' => $user2->id, 'product_id' => $prodA->id, 'quantity' => 2, 'weight' => 84, 'recorded_at' => now()]);

        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('financials');

        $financials = $response->viewData('financials');
        $this->assertEquals(10.0, $financials['factory_target_packages']); // 5 + 5 = 10
        $this->assertEquals(7.0, $financials['factory_real_packages']);     // 5 + 2 = 7
        $this->assertEquals(70.0, $financials['factory_target_progress_percent']); // (7/10)*100 = 70.0%
        $this->assertFalse($financials['is_factory_target_met']);
        $this->assertEquals(2, $financials['active_operators_count']);
        $this->assertEquals(1, $financials['met_operators_count']); // Solo shift1 cumplió
    }
}
