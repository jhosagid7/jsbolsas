<?php

namespace Tests\Feature;

use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\BagProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagFactorySupervisorApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;
    protected User $operator;
    protected BagProduct $product;
    protected BagShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = BagProduct::create([
            'name'      => 'Bolsa Negra 50x70',
            'sku'       => 'BN-5070',
            'cost'      => 2.0,
            'price'     => 3.5,
            'is_active' => true,
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
            'start_time' => now()->subHours(2),
            'status'     => 'open',
        ]);
    }

    public function test_supervisor_can_get_live_feed_of_pending_productions(): void
    {
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 20.5000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->getJson('/api/bag-factory/supervisor/feed');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'product_name' => 'Bolsa Negra 50x70',
                'status'       => 'pending_review',
            ]);
    }

    public function test_supervisor_can_adjust_weight_in_scale(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 20.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->putJson("/api/bag-factory/supervisor/productions/{$prod->id}", [
                'quantity' => 1,
                'weight'   => 21.5000,
                'notes'    => 'Ajuste en báscula por supervisor',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $prod->refresh();
        $this->assertEquals(21.5000, (float)$prod->weight);
        $this->assertEquals(20.0000, (float)$prod->original_weight);
        $this->assertEquals($this->supervisor->id, $prod->reviewed_by);

        // Shift totals updated
        $this->shift->refresh();
        $this->assertEquals(21.5000, (float)$this->shift->total_weight);
    }

    public function test_supervisor_can_approve_production_for_pre_stock(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 2,
            'weight'       => 40.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/bag-factory/supervisor/productions/{$prod->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status' => 'approved',
                ],
            ]);

        $prod->refresh();
        $this->assertEquals('approved', $prod->status);
        $this->assertEquals($this->supervisor->id, $prod->reviewed_by);
        $this->assertNotNull($prod->reviewed_at);
        $this->assertNotNull($prod->qr_code);
    }

    public function test_supervisor_can_bulk_approve_shift_productions(): void
    {
        $p1 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 20.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $p2 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 22.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson('/api/bag-factory/supervisor/productions/bulk-approve', [
                'production_ids' => [$p1->id, $p2->id],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'approved_count' => 2,
            ]);

        $p1->refresh();
        $p2->refresh();
        $this->assertEquals('approved', $p1->status);
        $this->assertEquals('approved', $p2->status);
    }

    public function test_supervisor_can_reject_production_with_reason(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 18.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/bag-factory/supervisor/productions/{$prod->id}/reject", [
                'rejection_reason' => 'Bulto con perforaciones y fuera de especificación',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'status' => 'rejected',
                ],
            ]);

        $prod->refresh();
        $this->assertEquals('rejected', $prod->status);
        $this->assertEquals('Bulto con perforaciones y fuera de especificación', $prod->rejection_reason);
        $this->assertEquals($this->supervisor->id, $prod->reviewed_by);
    }

    public function test_supervisor_can_view_pre_stock(): void
    {
        // 1 approved
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 5,
            'weight'       => 100.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
        ]);

        // 1 pending (should not count in pre-stock)
        BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 2,
            'weight'       => 40.0000,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->getJson('/api/bag-factory/supervisor/pre-stock');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'totals'  => [
                    'total_packages' => 5.0,
                    'total_weight'   => 100.0,
                ],
            ]);
    }

    public function test_supervisor_can_get_ticket_data(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product->id,
            'quantity'     => 1,
            'weight'       => 25.0000,
            'recorded_at'  => now(),
            'status'       => 'approved',
            'qr_code'      => 'PKG-TEST-9988',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->getJson("/api/bag-factory/supervisor/ticket/{$prod->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'qr_code'       => 'PKG-TEST-9988',
                    'product_name'  => 'Bolsa Negra 50x70',
                    'operator_name' => 'Pedro Operario',
                    'weight'        => 25.0,
                ],
            ]);
    }
}