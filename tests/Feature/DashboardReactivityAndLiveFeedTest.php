<?php

namespace Tests\Feature;

use App\Models\BagCostSetting;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReactivityAndLiveFeedTest extends TestCase
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
    public function it_returns_json_live_feed_with_realtime_metrics()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['name' => 'Carlos Operador', 'role' => 'operario']);

        $product = BagProduct::create([
            'name'                   => 'Bolsa 50x70',
            'sku'                    => 'B5070',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 10,
            'unit_weight_kg'         => 3.0,
            'real_total_weight_kg'   => 30.0,
            'cost'                   => 42.00,
            'price'                  => 65.00,
            'target_units_per_shift' => 8,
            'is_active'              => true,
        ]);

        $shift = BagShift::create([
            'user_id'    => $operator->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        // Simular pesaje enviado desde la APK móvil
        $prod = BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $operator->id,
            'product_id'   => $product->id,
            'quantity'     => 4,
            'weight'       => 120.0,
            'status'       => 'approved',
            'recorded_at'  => now(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('dashboard.live_data', ['period' => 'today']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'timestamp',
            'latest_production_id',
            'pending_review',
            'financials' => [
                'today_income',
                'today_cost',
                'today_net_profit',
                'factory_target_packages',
                'factory_real_packages',
                'factory_target_progress_percent',
                'is_factory_target_met',
                'active_operators_count',
                'met_operators_count',
            ],
            'shifts',
        ]);

        $data = $response->json();
        $this->assertEquals($prod->id, $data['latest_production_id']);
        $this->assertEquals(8.0, $data['financials']['factory_target_packages']);
        $this->assertEquals(4.0, $data['financials']['factory_real_packages']);
        $this->assertEquals(50.0, $data['financials']['factory_target_progress_percent']);
        $this->assertFalse($data['financials']['is_factory_target_met']);
        $this->assertCount(1, $data['shifts']);
        $this->assertEquals('Carlos Operador', $data['shifts'][0]['user_name']);
        $this->assertEquals(50.0, $data['shifts'][0]['completion_percent']);
        $this->assertEquals(4, $data['shifts'][0]['remaining_units']);
    }

    /** @test */
    public function it_resolves_effective_operator_when_shift_was_initiated_by_admin_but_produced_by_operator()
    {
        $admin = User::factory()->create(['name' => 'Jhonny Sagid (Admin)', 'role' => 'admin']);
        $operator = User::factory()->create(['name' => 'Pedro Operario', 'role' => 'operario']);

        $product = BagProduct::create([
            'name'                   => 'Bolsa Alta Fuelle 25x65',
            'sku'                    => 'B2565',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 1,
            'unit_weight_kg'         => 1.2,
            'real_total_weight_kg'   => 1.2,
            'cost'                   => 1.68,
            'price'                  => 2.50,
            'target_units_per_shift' => 25,
            'is_active'              => true,
        ]);

        // Shift opened with admin credentials
        $shift = BagShift::create([
            'user_id'    => $admin->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        // Operator Pedro syncs production into this shift
        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $operator->id,
            'product_id'   => $product->id,
            'quantity'     => 10,
            'weight'       => 12.0,
            'status'       => 'approved',
            'recorded_at'  => now(),
        ]);

        // 1. Check model accessor
        $shiftFresh = $shift->fresh(['productions.user', 'user']);
        $this->assertEquals('Pedro Operario', $shiftFresh->effective_user->name);

        // 2. Check dashboard live data returns Pedro Operario as user_name
        $response = $this->actingAs($admin)->getJson(route('dashboard.live_data', ['period' => 'today']));
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('Pedro Operario', $data['shifts'][0]['user_name']);

        // 3. Check dashboard web view includes Pedro Operario
        $webResponse = $this->actingAs($admin)->get(route('dashboard'));
        $webResponse->assertStatus(200);
        $webResponse->assertSee('Pedro Operario');
    }

    /** @test */
    public function it_calculates_plant_operation_window_and_individual_shift_durations()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $op1 = User::factory()->create(['name' => 'Pedro Operario', 'role' => 'operario']);
        $op2 = User::factory()->create(['name' => 'Carlos Operario', 'role' => 'operario']);

        $product = BagProduct::create([
            'name'                   => 'Bolsa 30x40',
            'sku'                    => 'B3040',
            'sale_unit'              => 'BULTO',
            'millar_per_bulto'       => 10,
            'unit_weight_kg'         => 1.5,
            'real_total_weight_kg'   => 15.0,
            'cost'                   => 21.00,
            'price'                  => 35.00,
            'target_units_per_shift' => 10,
            'is_active'              => true,
        ]);

        $today = now()->startOfDay();

        // Shift 1: Pedro worked from 07:00 to 15:00 (8 hours, closed)
        $shift1 = BagShift::create([
            'user_id'    => $op1->id,
            'shift_type' => 'diurno',
            'start_time' => $today->copy()->setHour(7)->setMinute(0),
            'end_time'   => $today->copy()->setHour(15)->setMinute(0),
            'status'     => 'closed',
        ]);
        BagProduction::create([
            'bag_shift_id' => $shift1->id,
            'user_id'      => $op1->id,
            'product_id'   => $product->id,
            'quantity'     => 10,
            'weight'       => 150.0,
            'status'       => 'approved',
            'recorded_at'  => $today->copy()->setHour(14)->setMinute(0),
        ]);

        // Shift 2: Carlos started at 08:00 (currently open)
        $shift2 = BagShift::create([
            'user_id'    => $op2->id,
            'shift_type' => 'diurno',
            'start_time' => $today->copy()->setHour(8)->setMinute(0),
            'status'     => 'open',
        ]);
        BagProduction::create([
            'bag_shift_id' => $shift2->id,
            'user_id'      => $op2->id,
            'product_id'   => $product->id,
            'quantity'     => 5,
            'weight'       => 75.0,
            'status'       => 'approved',
            'recorded_at'  => $today->copy()->setHour(10)->setMinute(0),
        ]);

        $this->assertEquals(8.0, $shift1->duration_hours);
        $this->assertEquals('8h 00m', $shift1->duration_human);

        $response = $this->actingAs($admin)->getJson(route('dashboard.live_data', ['period' => 'today']));
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('open', $data['financials']['plant_status']);
        $this->assertEquals(20.0, $data['financials']['factory_target_packages']); // 10 + 10 = 20
        $this->assertEquals(15.0, $data['financials']['factory_real_packages']);   // 10 + 5 = 15
        $this->assertEquals(75.0, $data['financials']['factory_target_progress_percent']); // 15 / 20 = 75%
        $this->assertStringContainsString('07:00 AM', $data['financials']['plant_schedule_window']);
        $this->assertCount(2, $data['shifts']);
    }
}
