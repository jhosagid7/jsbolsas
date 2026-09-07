<?php

namespace Tests\Feature;

use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineIntegrationAndTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected User $admin;
    protected BagMachine $machine1;
    protected BagMachine $machine2;
    protected BagProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'name'     => 'Pedro Operario',
            'email'    => 'pedro.operario.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'operario',
        ]);

        $this->admin = User::create([
            'name'     => 'Admin General',
            'email'    => 'admin.general.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'admin',
        ]);

        $this->machine1 = BagMachine::firstOrCreate(
            ['code' => 'EXT-01'],
            [
                'name'      => 'Extrusora Principal #1',
                'type'      => 'extrusora',
                'is_active' => true,
            ]
        );

        $this->machine2 = BagMachine::firstOrCreate(
            ['code' => 'SEL-01'],
            [
                'name'      => 'Selladora Lateral #1',
                'type'      => 'selladora',
                'is_active' => true,
            ]
        );

        $this->product = BagProduct::firstOrCreate(
            ['sku' => 'BA-1424-18'],
            [
                'name'                   => 'Bolsa Asa 14x24 C-1.8',
                'width_inch'             => 14.0,
                'length_inch'            => 24.0,
                'gauge_caliber'          => 1.8000,
                'millar_per_bulto'       => 1.0000,
                'unit_weight_kg'         => 17.0000,
                'real_total_weight_kg'   => 17.0000,
                'cost'                   => 25.0000,
                'price'                  => 40.0000,
                'target_units_per_shift' => 10,
                'target_daily_profit'    => 105.0000,
                'is_variable_quantity'   => false,
                'is_active'              => true,
            ]
        );
    }

    /** @test */
    public function it_returns_machines_catalog_api(): void
    {
        $response = $this->actingAs($this->operator)
            ->getJson('/api/bag-factory/machines');

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'EXT-01', 'name' => 'Extrusora Principal #1', 'type' => 'EXTRUSORA'])
            ->assertJsonFragment(['code' => 'SEL-01', 'name' => 'Selladora Lateral #1', 'type' => 'SELLADORA']);
    }

    /** @test */
    public function it_opens_shift_with_assigned_machine_and_traceable_code(): void
    {
        $response = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/shifts/open', [
                'machine_id' => $this->machine1->id,
                'shift_type' => 'diurno',
                'start_time' => now()->toDateTimeString(),
                'sync_id'    => 'SHIFT-MACH-001',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.machine.code', 'EXT-01')
            ->assertJsonPath('data.machine.name', 'Extrusora Principal #1');

        $this->assertDatabaseHas('bag_shifts', [
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine1->id,
            'sync_id'    => 'SHIFT-MACH-001',
            'status'     => 'open',
        ]);
    }

    /** @test */
    public function it_traces_production_back_to_assigned_shift_machine(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine2->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-MACH-TRACE-001',
        ]);

        $syncPayload = [
            'shift_id'    => $shift->id,
            'productions' => [
                [
                    'sync_id'     => 'PROD-QR-TRACE-001',
                    'product_id'  => $this->product->id,
                    'quantity'    => 5,
                    'weight'      => 85.0000,
                    'recorded_at' => now()->toDateTimeString(),
                ],
            ],
        ];

        $syncResponse = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/productions/sync', $syncPayload);

        $syncResponse->assertStatus(200)->assertJson(['success' => true, 'synced_count' => 1]);

        $production = BagProduction::where('sync_id', 'PROD-QR-TRACE-001')->first();
        $this->assertNotNull($production);
        $this->assertNotNull($production->machine);
        $this->assertEquals('SEL-01', $production->machine->code);
        $this->assertEquals('Selladora Lateral #1', $production->machine->name);

        // Ticket API response includes machine data
        $ticketResponse = $this->actingAs($this->operator)
            ->getJson("/api/bag-factory/ticket/{$production->id}");

        $ticketResponse->assertStatus(200)
            ->assertJsonPath('data.machine_code', 'SEL-01')
            ->assertJsonPath('data.machine_name', 'Selladora Lateral #1')
            ->assertJsonPath('data.operator_name', 'Pedro Operario');
    }

    /** @test */
    public function it_filters_dashboard_and_calculates_machine_kpis(): void
    {
        $shift = BagShift::create([
            'user_id'         => $this->operator->id,
            'machine_id'      => $this->machine1->id,
            'shift_type'      => 'diurno',
            'start_time'      => now()->subHours(8),
            'end_time'        => now(),
            'status'          => 'closed',
            'total_packages'  => 10,
            'total_weight'    => 170.0,
            'target_packages' => 10,
        ]);

        BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 10,
            'weight'       => 170.0,
            'status'       => 'approved',
            'recorded_at'  => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('dashboard', ['machine_id' => $this->machine1->id]));

        $response->assertStatus(200)
            ->assertSee('Extrusora Principal #1')
            ->assertSee('EXT-01')
            ->assertSee('170.00 Kg')
            ->assertSee('EFICIENCIA DE TURNO');
    }

    /** @test */
    public function it_provides_clinical_audit_report_by_qr_code(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine2->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $prod = BagProduction::create([
            'bag_shift_id' => $shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 17.0,
            'qr_code'      => 'PKG-CLINIC-TEST',
            'status'       => 'approved',
            'recorded_at'  => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('scale.index', ['qr' => 'PKG-CLINIC-TEST']));

        $response->assertStatus(200)
            ->assertSee('PKG-CLINIC-TEST')
            ->assertSee('Bolsa Asa 14x24 C-1.8')
            ->assertSee('SEL-01')
            ->assertSee('Selladora Lateral #1')
            ->assertSee('Pedro Operario');
    }
}