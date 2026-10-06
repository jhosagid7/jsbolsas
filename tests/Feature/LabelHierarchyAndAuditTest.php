<?php

namespace Tests\Feature;

use App\Models\BagLabelPrint;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelHierarchyAndAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;
    protected User $operator;
    protected BagProduct $product10Millar;
    protected BagProduct $product1Millar;
    protected BagProduct $bobinaVariable;
    protected BagShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = User::create([
            'name'     => 'Carlos Supervisor',
            'email'    => 'super.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'supervisor',
        ]);

        $this->operator = User::create([
            'name'     => 'Jhonny Pirela',
            'email'    => 'jhonny.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'operario',
        ]);

        // Bolsa que trae 10 millares por bulto
        $this->product10Millar = BagProduct::create([
            'name'                 => 'Bolsa Asa 30x40',
            'sku'                  => 'ASA-3040',
            'sale_unit'            => 'BULTO',
            'millar_per_bulto'     => 10.0000,
            'cost'                 => 15.0,
            'price'                => 20.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        // Bolsa grande donde 1 bulto es 1 millar
        $this->product1Millar = BagProduct::create([
            'name'                 => 'Bolsa Jumbo 50x70',
            'sku'                  => 'JUMBO-5070',
            'sale_unit'            => 'BULTO',
            'millar_per_bulto'     => 1.0000,
            'cost'                 => 30.0,
            'price'                => 40.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        // Bobina de peso variable (por Kg)
        $this->bobinaVariable = BagProduct::create([
            'name'                 => 'Bobina Película 60cm',
            'sku'                  => 'BOB-60CM',
            'sale_unit'            => 'KG',
            'cost'                 => 2.0,
            'price'                => 2.8,
            'is_variable_quantity' => true,
            'is_active'            => true,
        ]);

        $this->shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(4),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-AUDIT-' . uniqid(),
        ]);
    }

    /**
     * Test 1: Bulto con 10 millares en modo 'millar' genera 10 etiquetas interiores de "1 MILLAR".
     */
    public function test_single_bulto_with_10_millares_in_millar_scope_generates_10_millar_labels(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product10Millar->id,
            'quantity'     => 1, // 1 Bulto
            'weight'       => 45.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-ASA3040-01',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prod->id, 'scope' => 'millar']));

        $response->assertStatus(200);
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>10</strong>', false);
        $response->assertSee('1 MILLAR');
        $response->assertDontSee('1 BULTO');
    }

    /**
     * Test 2: Bulto con 10 millares en modo 'bulto' genera 1 etiqueta exterior master "1 BULTO (10 MILLARES)".
     */
    public function test_single_bulto_with_10_millares_in_bulto_scope_generates_1_master_label(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product10Millar->id,
            'quantity'     => 1, // 1 Bulto
            'weight'       => 45.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-ASA3040-02',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prod->id, 'scope' => 'bulto']));

        $response->assertStatus(200);
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>1</strong>', false);
        $response->assertSee('1 BULTO (10 MILLARES)');
    }

    /**
     * Test 3: Bulto con 10 millares en modo 'kit' genera 11 etiquetas (1 master + 10 millares).
     */
    public function test_single_bulto_with_10_millares_in_kit_scope_generates_11_labels(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product10Millar->id,
            'quantity'     => 1, // 1 Bulto
            'weight'       => 45.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-ASA3040-03',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prod->id, 'scope' => 'kit']));

        $response->assertStatus(200);
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>11</strong>', false);
        $response->assertSee('1 BULTO (10 MILLARES)');
        $response->assertSee('1 MILLAR');
    }

    /**
     * Test 4: Producto donde el bulto es 1 millar (millar_per_bulto = 1) no genera etiquetas duplicadas.
     */
    public function test_product_where_bulto_equals_1_millar_generates_single_label(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1Millar->id,
            'quantity'     => 1,
            'weight'       => 30.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-JUMBO-01',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prod->id, 'scope' => 'kit']));

        $response->assertStatus(200);
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>1</strong>', false);
        $response->assertSee('1 MILLAR');
    }

    /**
     * Test 5: Cada acción de impresión registra auditoría invisible en bag_label_prints y detecta reimpresiones.
     */
    public function test_ticket_printing_silently_logs_audit_and_detects_reprints(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product10Millar->id,
            'quantity'     => 1,
            'weight'       => 45.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-AUDIT-LOG-01',
        ]);

        $this->assertEquals(0, BagLabelPrint::where('production_id', $prod->id)->count());

        // 1. Previsualización en pantalla NO debe registrar impresión todavía
        $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prod->id, 'scope' => 'bulto']));

        $this->assertEquals(0, BagLabelPrint::where('production_id', $prod->id)->count());

        // 2. Acción de mandar a imprimir (confirm-print) registra la 1era impresión
        $this->actingAs($this->supervisor)
            ->post(route('labels.confirm_print'), [
                'labels' => [
                    [
                        'id'         => $prod->id,
                        'qr_code'    => 'PKG-AUDIT-LOG-01',
                        'label_type' => 'bulto',
                    ]
                ]
            ]);

        $printRecord = BagLabelPrint::where('production_id', $prod->id)
            ->where('qr_code', 'PKG-AUDIT-LOG-01')
            ->first();

        $this->assertNotNull($printRecord);
        $this->assertEquals(1, $printRecord->print_count);
        $this->assertEquals('bulto', $printRecord->label_type);
        $this->assertEquals($this->supervisor->id, $printRecord->user_id);

        // 3. Descarga de PDF / Reimpresión incrementa el contador
        $this->actingAs($this->supervisor)
            ->get(route('ticket.pdf', ['id' => $prod->id, 'scope' => 'bulto']));

        $printRecord->refresh();
        $this->assertEquals(2, $printRecord->print_count);
    }

    /**
     * Test 6: El panel de auditoría muestra estado Consistente y alerta de ANOMALÍA cuando hay exceso.
     */
    public function test_label_audit_panel_shows_consistent_and_anomaly_alerts(): void
    {
        // 1. Producción consistente (1 bulto, impreso 1 vez)
        $prodNormal = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product1Millar->id,
            'quantity'     => 1,
            'weight'       => 30.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-CONSISTENT-01',
        ]);

        BagLabelPrint::create([
            'production_id' => $prodNormal->id,
            'user_id'       => $this->supervisor->id,
            'qr_code'       => 'PKG-CONSISTENT-01',
            'label_type'    => 'millar',
            'print_count'   => 1,
        ]);

        // 2. Producción con anomalía (1 bulto de 10 millares -> máx 11 etiquetas, pero con 18 impresiones o QR duplicado)
        $prodAnomaly = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product10Millar->id,
            'quantity'     => 1,
            'weight'       => 45.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-ANOMALY-01',
        ]);

        BagLabelPrint::create([
            'production_id' => $prodAnomaly->id,
            'user_id'       => $this->supervisor->id,
            'qr_code'       => 'PKG-ANOMALY-01',
            'label_type'    => 'bulto',
            'print_count'   => 3, // Reimpreso 3 veces
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('bag-factory.label-audits'));

        $response->assertStatus(200);
        $response->assertSee('Auditoría Forense y Control de Impresión de Etiquetas');
        $response->assertSee('Consistente');
        $response->assertSee('ANOMALÍA');
        $response->assertSee('PKG-CONSISTENT-01');
        $response->assertSee('PKG-ANOMALY-01');
    }
}
