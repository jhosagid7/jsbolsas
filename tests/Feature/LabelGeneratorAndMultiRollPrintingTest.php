<?php

namespace Tests\Feature;

use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabelGeneratorAndMultiRollPrintingTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;
    protected User $operator;
    protected BagProduct $variableBobina;
    protected BagProduct $bultoProduct;
    protected BagShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supervisor = User::create([
            'name'     => 'Supervisor General',
            'email'    => 'super.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'supervisor',
        ]);

        $this->operator = User::create([
            'name'     => 'Operario Extrusión',
            'email'    => 'oper.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'operario',
        ]);

        $this->variableBobina = BagProduct::create([
            'name'                 => 'Bobina de Zanahoria 1Kg',
            'sku'                  => 'B04BOZHB',
            'sale_unit'            => 'KG',
            'cost'                 => 2.0,
            'price'                => 2.8,
            'is_variable_quantity' => true,
            'is_active'            => true,
        ]);

        $this->bultoProduct = BagProduct::create([
            'name'                 => 'Bolsa Asa 30x40',
            'sku'                  => 'ASA-3040',
            'sale_unit'            => 'BULTO',
            'cost'                 => 12.0,
            'price'                => 16.0,
            'is_variable_quantity' => false,
            'is_active'            => true,
        ]);

        $this->shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'shift_type' => 'diurno',
            'start_time' => now()->subHours(4),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-GEN-' . uniqid(),
        ]);
    }

    /**
     * Test 1: Producción de 3 bobinas con 121.20 Kg genera 3 etiquetas físicas con sus pesos individuales.
     */
    public function test_multi_bobina_production_renders_three_labels_with_individual_weights(): void
    {
        $metadata = [
            ['weight' => 40.20, 'color' => 'Naranja', 'batch' => 'LOTE-13'],
            ['weight' => 41.00, 'color' => 'Naranja', 'batch' => 'LOTE-13'],
            ['weight' => 40.00, 'color' => 'Naranja', 'batch' => 'LOTE-13'],
        ];

        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->variableBobina->id,
            'quantity'     => 3,
            'weight'       => 121.20,
            'metadata'     => $metadata,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-GUOWX3CKHN',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', $prod->id));

        $response->assertStatus(200);
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>3</strong>', false);
        $response->assertSee('40.20 Kg');
        $response->assertSee('41.00 Kg');
        $response->assertSee('40.00 Kg');
        $response->assertSee('BOBINA #1 - PESO VARIABLE');
        $response->assertSee('BOBINA #2 - PESO VARIABLE');
        $response->assertSee('BOBINA #3 - PESO VARIABLE');
    }

    /**
     * Test 2: Si no tiene desglose de metadatos, divide el peso equitativamente entre las 3 bobinas.
     */
    public function test_multi_bobina_without_metadata_splits_weight_equally_per_label(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->variableBobina->id,
            'quantity'     => 3,
            'weight'       => 120.00,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-NO-META',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->get(route('ticket', $prod->id));

        $response->assertStatus(200);
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>3</strong>', false);
        $response->assertSee('40.00 Kg');
    }

    /**
     * Test 3: Vista del Generador de Etiquetas carga correctamente catálogo y producciones de báscula.
     */
    public function test_label_generator_index_loads_successfully(): void
    {
        $response = $this->actingAs($this->supervisor)
            ->get(route('labels.index'));

        $response->assertStatus(200);
        $response->assertSee('Generador de Etiquetas');
        $response->assertSee('Bobina de Zanahoria 1Kg');
        $response->assertSee('Bolsa Asa 30x40');
        $response->assertSee('Rollo 80mm');
        $response->assertSee('Rollo 58mm');
        $response->assertSee('Hoja Carta 3x6');
    }

    /**
     * Test 4: Generación de etiquetas desde el generador con previsualización térmica.
     */
    public function test_label_generator_preview_action(): void
    {
        $payload = [
            'items' => [
                [
                    'type' => 'catalog',
                    'id'   => $this->bultoProduct->id,
                    'qty'  => 2,
                ],
            ],
            'template' => '80mm',
            'output'   => 'preview',
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('labels.generate'), $payload);

        $response->assertStatus(200);
        $response->assertSee('BOLSA ASA 30X40');
        $response->assertSee('Total de etiquetas físicas a imprimir: <strong>2</strong>', false);
    }

    /**
     * Test 5: Descarga de PDF para ticket individual y turno completo.
     */
    public function test_ticket_and_shift_pdf_download(): void
    {
        $prod = BagProduction::create([
            'bag_shift_id' => $this->shift->id,
            'user_id'      => $this->operator->id,
            'product_id'   => $this->variableBobina->id,
            'quantity'     => 1,
            'weight'       => 35.50,
            'status'       => 'approved',
            'reviewed_by'  => $this->supervisor->id,
            'reviewed_at'  => now(),
            'recorded_at'  => now(),
            'qr_code'      => 'PKG-PDF-01',
        ]);

        $singlePdf = $this->actingAs($this->supervisor)
            ->get(route('ticket.pdf', ['id' => $prod->id, 'size' => '80mm']));

        $singlePdf->assertStatus(200);
        $this->assertEquals('application/pdf', $singlePdf->headers->get('content-type'));

        $shiftPdf = $this->actingAs($this->supervisor)
            ->get(route('ticket.shift.pdf', ['shift_id' => $this->shift->id, 'size' => '58mm']));

        $shiftPdf->assertStatus(200);
        $this->assertEquals('application/pdf', $shiftPdf->headers->get('content-type'));
    }

    /**
     * Test 6: Generación desde catálogo con asignación explícita de operario, fecha y lote.
     */
    public function test_label_generator_preview_with_custom_operator_and_batch(): void
    {
        $customOperator = User::create([
            'name'     => 'Jhonny Pirela',
            'email'    => 'jhonny.' . uniqid() . '@bolsas.test',
            'password' => bcrypt('password123'),
            'role'     => 'operario',
        ]);

        $payload = [
            'items' => [
                [
                    'type' => 'catalog',
                    'id'   => $this->bultoProduct->id,
                    'qty'  => 3,
                ],
            ],
            'operator_id'     => $customOperator->id,
            'production_date' => '2026-09-10',
            'batch_code'      => 'LOTE-TURNO-MAÑANA',
            'template'        => '80mm',
            'output'          => 'preview',
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('labels.generate'), $payload);

        $response->assertStatus(200);
        $response->assertSee('BOLSA ASA 30X40');
        $response->assertSee('Jhonny Pirela');
        $response->assertSee('10/09/2026');
        $response->assertSee('LOTE-TURNO-MAÑANA');
        // No debe mostrar peso porque es bulto homogéneo
        $response->assertDontSee('Peso Real:');
    }
}
