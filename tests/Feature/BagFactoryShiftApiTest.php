<?php

namespace Tests\Feature;

use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\BagProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagFactoryShiftApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::create([
            'name'     => 'Juan Pérez Operador',
            'email'    => 'juan.operador.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'Operario',
        ]);
    }

    public function test_operator_can_open_shift(): void
    {
        $response = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/shifts/open', [
                'shift_type' => 'diurno',
                'start_time' => '2026-08-31 07:00:00',
                'sync_id'    => 'SHIFT-UUID-001',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'shift_type' => 'diurno',
                    'status'     => 'open',
                ],
            ]);

        $this->assertDatabaseHas('bag_shifts', [
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'status'     => 'open',
            'sync_id'    => 'SHIFT-UUID-001',
        ]);
    }

    public function test_operator_cannot_open_duplicate_active_shift(): void
    {
        BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $response = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/shifts/open', [
                'shift_type' => 'nocturno',
                'start_time' => now()->toDateTimeString(),
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_operator_can_get_active_shift(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'nocturno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $response = $this->actingAs($this->operator)
            ->getJson('/api/bag-factory/shifts/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_active_shift' => true,
                'data' => [
                    'id'         => $shift->id,
                    'shift_type' => 'nocturno',
                    'status'     => 'open',
                ],
            ]);
    }

    public function test_operator_can_sync_productions_idempotently(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $product1 = BagProduct::create([
            'name'      => 'Bolsa Vivero 1Kg',
            'sku'       => 'BV-1KG',
            'cost'      => 1.5,
            'price'     => 2.5,
            'is_active' => true,
        ]);
        $product2 = BagProduct::create([
            'name'      => 'Bolsa Basura 50L',
            'sku'       => 'BB-50L',
            'cost'      => 2.0,
            'price'     => 3.5,
            'is_active' => true,
        ]);

        $payload = [
            'shift_id'    => $shift->id,
            'productions' => [
                [
                    'sync_id'     => 'PROD-UUID-001',
                    'product_id'  => $product1->id,
                    'quantity'    => 2,
                    'weight'      => 15.5000,
                    'recorded_at' => '2026-08-31 08:30:00',
                ],
                [
                    'sync_id'     => 'PROD-UUID-002',
                    'product_id'  => $product2->id,
                    'quantity'    => 3,
                    'weight'      => 25.0000,
                    'recorded_at' => '2026-08-31 09:15:00',
                ],
            ],
        ];

        // First sync
        $response = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/productions/sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'synced_count' => 2,
            ]);

        $this->assertDatabaseCount('bag_productions', 2);
        
        $shift->refresh();
        $this->assertEquals(5.00, (float)$shift->total_packages);
        $this->assertEquals(40.5000, (float)$shift->total_weight);

        // Re-syncing the same batch (idempotence test)
        $response2 = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/productions/sync', $payload);

        $response2->assertStatus(200);
        $this->assertDatabaseCount('bag_productions', 2);
    }

    public function test_operator_can_close_shift(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => '2026-08-31 07:00:00',
            'status'     => 'open',
        ]);

        $response = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/shifts/close', [
                'shift_id' => $shift->id,
                'end_time' => '2026-08-31 15:00:00',
                'notes'    => 'Turno culminado sin novedades',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status' => 'closed',
                ],
            ]);

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertNotNull($shift->end_time);
        $this->assertEquals('Turno culminado sin novedades', $shift->notes);
    }

    public function test_get_bag_factory_products_catalog(): void
    {
        BagProduct::create([
            'name'      => 'Bolsa Asa 30x40',
            'sku'       => 'BA-3040',
            'cost'      => 1.0,
            'price'     => 2.0,
            'is_active' => true,
        ]);
        BagProduct::create([
            'name'      => 'Tornillo 2 Pulgadas',
            'sku'       => 'TOR-2P',
            'cost'      => 0.5,
            'price'     => 1.0,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->operator)
            ->getJson('/api/bag-factory/products');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Bolsa Asa 30x40'])
            ->assertJsonMissing(['name' => 'Tornillo 2 Pulgadas']);
    }
}