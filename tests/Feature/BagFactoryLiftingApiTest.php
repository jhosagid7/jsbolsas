<?php

namespace Tests\Feature;

use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\BagProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagFactoryLiftingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $warehouseStaff;
    protected User $supervisor;
    protected User $operator;
    protected BagProduct $product1;
    protected BagProduct $product2;
    protected BagShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product1 = BagProduct::create([
            'name'      => 'Bolsa Negra 50x70',
            'sku'       => 'BN-5070',
            'cost'      => 2.0,
            'price'     => 3.5,
            'is_active' => true,
        ]);

        $this->product2 = BagProduct::create([
            'name'      => 'Bolsa Transparente 30x40',
            'sku'       => 'BT-3040',
            'cost'      => 1.5,
            'price'     => 2.8,
            'is_active' => true,
        ]);

        $this->warehouseStaff = User::create([
            'name'     => 'Mario Almacén',
            'email'    => 'mario.almacen.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Almacen',
        ]);

        $this->supervisor = User::create([
            'name'     => 'Carlos Supervisor',
            'email'    => 'carlos.supervisor.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Supervisor',
        ]);

        $this->operator = User::create([
            'name'     => 'Pedro Operario',
            'email'    => 'pedro.operario.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);

        $this->shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(4),
            'status'     => 'open',
        ]);
    }

    public function test_warehouse_staff_can_get_pending_lifting_bultos(): void
    {
        // 1 Approved (Ready for lifting)
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'quantity'     => 3,
            'weight'       => 60.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'qr_code'      => 'PKG-READY-001',
        ]);

        // 1 Pending review (Should NOT be available for lifting)
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product2->id,
            'quantity'     => 1,
            'weight'       => 20.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
            'qr_code'      => 'PKG-PEND-002',
        ]);

        $response = $this->actingAs($this->warehouseStaff)
            ->getJson('/api/bag-factory/lifting/pending');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'totals'  => [
                    'count'          => 1,
                    'total_packages' => 3.0,
                    'total_weight'   => 60.0,
                ],
            ])
            ->assertJsonFragment(['qr_code' => 'PKG-READY-001'])
            ->assertJsonMissing(['qr_code' => 'PKG-PEND-002']);
    }

    public function test_warehouse_staff_can_scan_qr_code(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'quantity'     => 2,
            'weight'       => 45.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-SCAN-8899',
        ]);

        $response = $this->actingAs($this->warehouseStaff)
            ->getJson('/api/bag-factory/lifting/scan/PKG-SCAN-8899');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'           => $prod->id,
                    'qr_code'      => 'PKG-SCAN-8899',
                    'product_name' => 'Bolsa Negra 50x70',
                    'weight'       => 45.0,
                    'is_ready'     => true,
                ],
            ]);
    }

    public function test_warehouse_staff_can_receive_bultos_and_update_status(): void
    {
        $p1 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1->id,
            'quantity'     => 2,
            'weight'       => 40.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-LIFT-111',
        ]);

        $p2 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product2->id,
            'quantity'     => 3,
            'weight'       => 50.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-LIFT-222',
        ]);

        $payload = [
            'production_ids' => [$p1->id, $p2->id],
            'notes'          => 'Recepción oficial de bultos desde Fábrica JSBolsas',
        ];

        $response = $this->actingAs($this->warehouseStaff)
            ->postJson('/api/bag-factory/lifting/receive', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'received_count' => 2,
            ]);

        // Verify bag_productions were updated to 'lifted'
        $p1->refresh();
        $p2->refresh();
        $this->assertEquals('lifted', $p1->status);
        $this->assertEquals('lifted', $p2->status);
        $this->assertEquals($this->warehouseStaff->id, $p1->lifted_by);
        $this->assertNotNull($p1->lifted_at);
    }
}