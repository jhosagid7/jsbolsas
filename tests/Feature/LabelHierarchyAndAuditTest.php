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

    /**
     * Test 7: Validación de productos hermanos (Bulto 20 Millares vs Millar 1 Millar),
     * eliminación del subtítulo redundante '1000 MILLARES', asignación de SKU correspondiente
     * y manejo de producciones parciales.
     */
    public function test_sibling_products_and_no_redundant_packaging_subtitle_in_kit_and_partial(): void
    {
        $bultoProduct = BagProduct::create([
            'name'                 => 'BOLSA ALTA FUELLE 1KG 19X27  20 MILLARE',
            'sku'                  => 'B04A1927CFA',
            'sale_unit'            => 'BULTO',
            'millar_per_bulto'     => 1000.0000, // Valor crudo de BD
            'cost'                 => 100.0,
            'price'                => 120.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $millarProduct = BagProduct::create([
            'name'                 => 'BOLSA ALTA FUELLE 1KG 19X27  1 MILLAR',
            'sku'                  => 'B04A1927CFM',
            'sale_unit'            => 'MILLAR',
            'millar_per_bulto'     => 1.0000,
            'cost'                 => 5.0,
            'price'                => 6.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        // 1. Verificar resolución bidireccional de hermanos
        $this->assertEquals($millarProduct->id, $bultoProduct->findSiblingMillarProduct()?->id);
        $this->assertEquals($bultoProduct->id, $millarProduct->findSiblingBultoProduct()?->id);

        // 2. Verificar sanitización de capacidad real (detecta 20 millares del nombre en lugar de 1000)
        $this->assertEquals(20.0, $bultoProduct->getRealMillarPerBulto());

        // 3. Producción completa en modo Kit
        $prodFull = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $bultoProduct->id,
            'quantity'     => 1, // 1 Bulto = 20 Millares
            'weight'       => 20.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-BULTO-FULL-01',
        ]);

        $controller = new \App\Http\Controllers\BagFactoryWebController();
        $labels = $controller->formatLabelsList($prodFull, 'kit');

        // Total 21 etiquetas: 1 Bulto Master + 20 Millares interiores
        $this->assertCount(21, $labels);

        // Primera etiqueta: Es el Bulto Master con nombre y SKU del BULTO
        $this->assertEquals('BOLSA ALTA FUELLE 1KG 19X27  20 MILLARE', $labels[0]['product_name']);
        $this->assertEquals('B04A1927CFA', $labels[0]['sku']);
        $this->assertEquals('', $labels[0]['presentation_info']); // Subtítulo redundante suprimido
        $this->assertEquals('bulto', $labels[0]['label_type']);

        // Segunda etiqueta en adelante: Son los desgloses con nombre y SKU del MILLAR
        $this->assertEquals('BOLSA ALTA FUELLE 1KG 19X27  1 MILLAR', $labels[1]['product_name']);
        $this->assertEquals('B04A1927CFM', $labels[1]['sku']);
        $this->assertEquals('', $labels[1]['presentation_info']); // Subtítulo redundante suprimido
        $this->assertEquals('millar', $labels[1]['label_type']);

        // Comprobar la respuesta web: NO debe tener '1000 MILLARES' ni '1 BULTO ('
        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prodFull->id, 'scope' => 'kit']));

        $response->assertStatus(200);
        $response->assertDontSee('1000 MILLARES');
        $response->assertDontSee('1 BULTO (1000 MILLARES)');

        // 4. Producción parcial: El operador solo fabricó 5 millares (menor que el bulto completo)
        $prodPartial = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $millarProduct->id,
            'quantity'     => 5, // 5 millares parciales
            'weight'       => 5.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-PARTIAL-05',
        ]);

        $responsePartial = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prodPartial->id]));

        $responsePartial->assertStatus(200);
        // Debe seleccionar por defecto el modo millar para 5 millares
        $responsePartial->assertSee('Total de etiquetas físicas a imprimir: <strong>5</strong>', false);
        // Al no completar un bulto, NO debe permitir Kit Completo ni Bulto Master
        $responsePartial->assertDontSee('📦 Kit Completo');
        $responsePartial->assertDontSee('🏷️ Solo Bulto Master');
        $responsePartial->assertSee('🔘 Solo Unidades / Millares');
    }

    /**
     * Test 8: Bolsa de Hielo 15x45 con espacios dobles en el nombre y ambos productos con sale_unit='BULTO'.
     * Valida que 10 millares registrados generen 11 etiquetas (1 Bulto Master + 10 Millares).
     */
    public function test_bolsa_hielo_15x45_with_sibling_double_spaces_and_bulto_sale_unit(): void
    {
        // Producto Bulto: ID 143 en VPS con doble espacio y 10 millares
        $bulto15x45 = BagProduct::create([
            'name'                 => 'BOLSA DE HIELO 15X45  C-4 10 MILLARES',
            'sku'                  => 'B04H1545',
            'sale_unit'            => 'BULTO',
            'millar_per_bulto'     => 10.0000,
            'cost'                 => 20.0,
            'price'                => 25.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        // Producto Millar: ID 144 en VPS con un solo espacio y 'sale_unit' = 'BULTO' pero nombre dice 1 MILLAR
        $millar15x45 = BagProduct::create([
            'name'                 => 'BOLSA DE HIELO 15X45 C-4 1 MILLAR',
            'sku'                  => 'B04MH1545M',
            'sale_unit'            => 'BULTO',
            'millar_per_bulto'     => 1.0000,
            'cost'                 => 2.0,
            'price'                => 2.5,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        // Operador carga 10 millares seleccionando el producto unitario
        $prod10 = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $millar15x45->id,
            'quantity'     => 10, // 10 millares (completa 1 bulto)
            'weight'       => 15.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-15X45-10M',
        ]);

        $controller = new \App\Http\Controllers\BagFactoryWebController();

        // 1. Kit Completo: 11 etiquetas (1 Bulto Master + 10 Millares interiores)
        $labelsKit = $controller->formatLabelsList($prod10, 'kit');
        $this->assertCount(11, $labelsKit);
        $this->assertEquals('BOLSA DE HIELO 15X45  C-4 10 MILLARES', $labelsKit[0]['product_name']);
        $this->assertEquals('B04H1545', $labelsKit[0]['sku']);
        $this->assertEquals('bulto', $labelsKit[0]['label_type']);

        $this->assertEquals('BOLSA DE HIELO 15X45 C-4 1 MILLAR', $labelsKit[1]['product_name']);
        $this->assertEquals('B04MH1545M', $labelsKit[1]['sku']);
        $this->assertEquals('millar', $labelsKit[1]['label_type']);

        // 2. Solo Unidades / Millares: 10 etiquetas
        $labelsMillar = $controller->formatLabelsList($prod10, 'millar');
        $this->assertCount(10, $labelsMillar);
        $this->assertEquals('BOLSA DE HIELO 15X45 C-4 1 MILLAR', $labelsMillar[0]['product_name']);
        $this->assertEquals('B04MH1545M', $labelsMillar[0]['sku']);

        // 3. Solo Bulto Master: 1 etiqueta
        $labelsBulto = $controller->formatLabelsList($prod10, 'bulto');
        $this->assertCount(1, $labelsBulto);
        $this->assertEquals('BOLSA DE HIELO 15X45  C-4 10 MILLARES', $labelsBulto[0]['product_name']);
        $this->assertEquals('B04H1545', $labelsBulto[0]['sku']);

        // 4. Verificación en interfaz web: Por defecto selecciona 'kit' para 10 millares y muestra los 3 botones
        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', ['id' => $prod10->id]));

        $response->assertStatus(200);
        $response->assertSee('📦 Kit Completo (Bulto Master + Desglose)');
        $response->assertSee('🏷️ Solo Bulto Master (Saco)');
        $response->assertSee('🔘 Solo Unidades / Millares');
        // Asegurar que APROBÓ fue eliminado de la etiqueta impresa
        $response->assertDontSee('APROBÓ:');
    }

    /**
     * Test 9: Los operarios pueden generar e imprimir etiquetas en su turno sin esperar aprobación administrativa,
     * y las etiquetas físicas NO muestran el campo APROBÓ.
     */
    public function test_operator_can_print_shift_labels_in_pending_status_and_approbo_is_removed(): void
    {
        // Registro de producción aún no aprobado (status: pending_review)
        $prodPending = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->product10Millar->id,
            'quantity'     => 1, // 1 Bulto
            'weight'       => 45.00,
            'status'       => 'pending_review',
            'reviewed_by'  => null,
            'reviewed_at'  => null,
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-OPERATOR-PENDING',
        ]);

        // Operario accede a imprimir el ticket individual
        $responseTicket = $this->actingAs($this->operator)
            ->get(route('ticket', ['id' => $prodPending->id]));

        $responseTicket->assertStatus(200);
        $responseTicket->assertDontSee('APROBÓ:');

        // Operario accede a imprimir todas las etiquetas del turno
        $responseShift = $this->actingAs($this->operator)
            ->get(route('ticket.shift', ['shift_id' => $this->shift->id]));

        $responseShift->assertStatus(200);
        $responseShift->assertSee('PKG-OPERATOR-PENDING');
        $responseShift->assertDontSee('APROBÓ:');
    }
}
