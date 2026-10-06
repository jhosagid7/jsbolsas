<?php

namespace Tests\Feature;

use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\BagProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelPrintingAndSplitTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;
    protected User $operator;
    protected BagProduct $homogenousProduct;
    protected BagProduct $variableProduct;
    protected BagShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = User::create([
            'name'     => 'Carlos Supervisor',
            'email'    => 'supervisor.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'supervisor',
        ]);

        $this->operator = User::create([
            'name'     => 'Pedro Operario',
            'email'    => 'operario.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'operario',
        ]);

        $this->homogenousProduct = BagProduct::create([
            'name'                 => 'Bolsa Baja Fuelle 2Kg 22x32 C-3',
            'sku'                  => 'B04B2232CFB',
            'sale_unit'            => 'BULTO',
            'cost'                 => 10.0,
            'price'                => 15.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $this->variableProduct = BagProduct::create([
            'name'                 => 'Bobina Negra Película 60cm',
            'sku'                  => 'BOB-NEG-60',
            'sale_unit'            => 'KG',
            'cost'                 => 1.8,
            'price'                => 2.5,
            'is_variable_quantity' => true,
            'is_active'            => true,
        ]);

        $this->shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(3),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-' . uniqid(),
        ]);
    }

    /**
     * Test 1: Aprobación Web divide automáticamente un pesaje múltiple homogéneo en unidades individuales de cantidad 1.
     */
    public function test_web_approve_splits_multi_package_homogenous_production_into_individual_units(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->homogenousProduct->id,
            'quantity'     => 5,
            'weight'       => 85.00,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
            'sync_id'      => 'PROD-TEST-5PKGS',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post(route('approve', $prod->id));

        $response->assertRedirect();

        // El registro agrupado original de 5 bultos ya no debe existir
        $this->assertDatabaseMissing('bag_productions', [
            'id' => $prod->id,
        ]);

        // Deben existir 5 registros independientes de 1 bulto y 17.00 Kg
        $splitItems = BagProduction::where('bag_shift_id', $this->shift->id)
            ->where('product_id', $this->homogenousProduct->id)
            ->where('status', 'approved')
            ->get();

        $this->assertCount(5, $splitItems);
        foreach ($splitItems as $item) {
            $this->assertEquals(1.0, (float)$item->quantity);
            $this->assertEquals(17.00, (float)$item->weight);
            $this->assertNotEmpty($item->qr_code);
            $this->assertStringStartsWith('PKG-', $item->qr_code);
            $this->assertEquals($this->supervisor->id, $item->reviewed_by);
            $this->assertNotNull($item->reviewed_at);
        }
    }

    /**
     * Test 2: Aprobación API también ejecuta el split para la APK móvil.
     */
    public function test_api_approve_splits_multi_package_homogenous_production(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->homogenousProduct->id,
            'quantity'     => 3,
            'weight'       => 60.00,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
            'sync_id'      => 'PROD-API-3PKGS',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/bag-factory/supervisor/productions/{$prod->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $splitItems = BagProduction::where('bag_shift_id', $this->shift->id)
            ->where('product_id', $this->homogenousProduct->id)
            ->where('status', 'approved')
            ->get();

        $this->assertCount(3, $splitItems);
        foreach ($splitItems as $item) {
            $this->assertEquals(1.0, (float)$item->quantity);
            $this->assertEquals(20.00, (float)$item->weight);
            $this->assertStringStartsWith('PKG-', $item->qr_code);
        }
    }

    /**
     * Test 3: Las bobinas múltiples con metadatos se dividen en bobinas individuales con sus pesos específicos.
     */
    public function test_approve_splits_multi_bobinas_with_individual_weights(): void
    {
        $metadata = [
            ['weight' => 24.50, 'color' => 'Azul', 'batch' => 'LOTE-1'],
            ['weight' => 23.80, 'color' => 'Azul', 'batch' => 'LOTE-1'],
        ];

        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->variableProduct->id,
            'quantity'     => 2,
            'weight'       => 48.30,
            'metadata'     => $metadata,
            'recorded_at'  => now(),
            'status'       => 'pending_review',
            'sync_id'      => 'PROD-BOBINAS-VAR',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post(route('approve', $prod->id));

        $response->assertRedirect();

        // El registro agrupado original fue reemplazado por 2 bobinas individuales
        $this->assertDatabaseMissing('bag_productions', [
            'id' => $prod->id,
        ]);

        $splitRolls = BagProduction::where('bag_shift_id', $this->shift->id)
            ->where('product_id', $this->variableProduct->id)
            ->where('status', 'approved')
            ->get();

        $this->assertCount(2, $splitRolls);
        $this->assertEquals(1.0, (float)$splitRolls[0]->quantity);
        $this->assertEquals(24.50, (float)$splitRolls[0]->weight);
        $this->assertEquals(1.0, (float)$splitRolls[1]->quantity);
        $this->assertEquals(23.80, (float)$splitRolls[1]->weight);
    }

    /**
     * Test 4: La vista de etiquetas oculta el peso real en bultos homogéneos.
     */
    public function test_ticket_view_hides_real_weight_for_homogenous_packages(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->homogenousProduct->id,
            'quantity'     => 1,
            'weight'       => 16.85,
            'qr_code'      => 'PKG-HOMO12345',
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', $prod->id));

        $response->assertStatus(200);
        $response->assertSee('BOLSA BAJA FUELLE 2KG 22X32 C-3');
        $response->assertSee('PKG-HOMO12345');
        $response->assertSee('Pedro Operario');
        $response->assertSee('Carlos Supervisor');
        
        // NO debe contener "Peso Real:" o "16.85 Kg" impreso en el ticket
        $response->assertDontSee('Peso Real:');
        $response->assertDontSee('16.85 Kg');
    }

    /**
     * Test 5: La vista de etiquetas SÍ muestra el peso real para bobinas variables.
     */
    public function test_ticket_view_shows_real_weight_for_variable_bobinas(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->variableProduct->id,
            'quantity'     => 1,
            'weight'       => 28.75,
            'qr_code'      => 'PKG-BOBVAR999',
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', $prod->id));

        $response->assertStatus(200);
        $response->assertSee('BOBINA NEGRA PELÍCULA 60CM');
        $response->assertSee('PKG-BOBVAR999');
        $response->assertSee('Peso Real:');
        $response->assertSee('28.75 Kg');
    }

    /**
     * Test 6: Impresión por lote de todas las etiquetas de un turno.
     */
    public function test_batch_ticket_printing_by_shift(): void
    {
        // Crear 2 producciones aprobadas en el turno
        $prod1 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->homogenousProduct->id,
            'quantity'     => 1,
            'weight'       => 17.00,
            'qr_code'      => 'PKG-SHIFT-01',
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
        ]);

        $prod2 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->homogenousProduct->id,
            'quantity'     => 1,
            'weight'       => 17.00,
            'qr_code'      => 'PKG-SHIFT-02',
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket.shift', $this->shift->id));

        $response->assertStatus(200);
        $response->assertSee('PKG-SHIFT-01');
        $response->assertSee('PKG-SHIFT-02');
    }
}
