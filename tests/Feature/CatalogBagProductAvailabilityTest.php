<?php

namespace Tests\Feature;

use App\Models\BagProduct;
use App\Services\CloudSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogBagProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_and_query_both_ensilar_products_and_bulto_20x30()
    {
        // 1. Crear producto 60x118
        $prod60 = BagProduct::create([
            'sku' => 'B04ESN60118T',
            'name' => 'BOLSA ENSILAR 60X118 C30 NEGRA (100UND)',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'sale_unit' => 'BULTO',
            'millar_per_bulto' => 3.0,
            'width_inch' => 18.0,
            'length_inch' => 30.0,
            'gauge_caliber' => 0.0120,
            'unit_weight_kg' => 19.44,
            'cost' => 56.57,
            'price' => 67.07,
            'is_active' => true,
        ]);

        // 2. Crear producto 50x118
        $prod50 = BagProduct::create([
            'sku' => 'B04ESN50118T',
            'name' => 'BOLSA ENSILAR 50X118 C30 NEGRA (100UND)',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'sale_unit' => 'BULTO',
            'millar_per_bulto' => 3.0,
            'width_inch' => 18.0,
            'length_inch' => 30.0,
            'gauge_caliber' => 0.0120,
            'unit_weight_kg' => 19.44,
            'cost' => 56.57,
            'price' => 67.07,
            'is_active' => true,
        ]);

        // 3. Crear bulto 20x30 (20 millares)
        $prod20m = BagProduct::create([
            'sku' => 'B042030CFAM20',
            'name' => 'BOLSA ALTA PLANA 1½ KG 20X30 C-1.8 20 MILLARES',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'sale_unit' => 'BULTO',
            'millar_per_bulto' => 20.0,
            'width_inch' => 20.0,
            'length_inch' => 30.0,
            'gauge_caliber' => 0.0018,
            'unit_weight_kg' => 21.6,
            'cost' => 60.69,
            'price' => 81.60,
            'is_active' => true,
        ]);

        $this->assertTrue((bool)$prod60->is_active);
        $this->assertTrue((bool)$prod50->is_active);
        $this->assertTrue((bool)$prod20m->is_active);

        // Validar búsqueda como en LabelGeneratorController
        $activeProducts = BagProduct::where('is_active', true)->orderBy('name')->get();
        $found60 = $activeProducts->first(fn($p) => str_contains(strtoupper($p->name), '60X118'));
        $found50 = $activeProducts->first(fn($p) => str_contains(strtoupper($p->name), '50X118'));
        $found20m = $activeProducts->first(fn($p) => str_contains(strtoupper($p->name), '20X30') && str_contains(strtoupper($p->name), '20 MILLARES'));

        $this->assertNotNull($found60);
        $this->assertNotNull($found50);
        $this->assertNotNull($found20m);
        $this->assertEquals(20.0, (float)$found20m->millar_per_bulto);
    }

    /** @test */
    public function it_exports_and_imports_new_products_via_cloud_sync_correctly()
    {
        $prod = BagProduct::create([
            'sku' => 'B042030CFAM20',
            'name' => 'BOLSA ALTA PLANA 1½ KG 20X30 C-1.8 20 MILLARES',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'sale_unit' => 'BULTO',
            'millar_per_bulto' => 20.0,
            'width_inch' => 20.0,
            'length_inch' => 30.0,
            'gauge_caliber' => 0.0018,
            'unit_weight_kg' => 21.6,
            'cost' => 60.69,
            'price' => 81.60,
            'is_active' => true,
        ]);

        $service = app(CloudSyncService::class);
        $deltas = $service->exportDeltas(now()->subMinutes(5));

        $this->assertNotEmpty($deltas['products']);
        $exportedProd = collect($deltas['products'])->firstWhere('sku', 'B042030CFAM20');

        $this->assertNotNull($exportedProd);
        $this->assertEquals('BOLSA ALTA PLANA 1½ KG 20X30 C-1.8 20 MILLARES', $exportedProd['name']);
        $this->assertEquals('BULTO', $exportedProd['sale_unit']);
        $this->assertEquals(20.0, (float)$exportedProd['millar_per_bulto']);
    }

    /** @test */
    public function it_can_create_and_query_22x60_c2_fuelle_and_plana_products()
    {
        // 1. Crear producto 22x60 Fuelle C-2
        $prodFuelle = BagProduct::create([
            'sku' => 'B04BAF226',
            'name' => 'BOLSA ALTA FUELLE 22X60 C-2 1 MILLAR',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'production_formula_id' => 5,
            'sale_unit' => 'MILLAR',
            'millar_per_bulto' => 1000.0,
            'width_inch' => 22.0,
            'length_inch' => 60.0,
            'gusset_cm' => 0.0,
            'gauge_caliber' => 0.0020,
            'unit_weight_kg' => 2.6400,
            'cost' => 10.21,
            'price' => 11.2310,
            'is_active' => true,
        ]);

        // 2. Crear producto 22x60 Plana C-2
        $prodPlana = BagProduct::create([
            'sku' => 'B04BAP2260',
            'name' => 'BOLSA ALTA PLANA 22X60 C-2 1 MILLAR',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'production_formula_id' => 5,
            'sale_unit' => 'MILLAR',
            'millar_per_bulto' => 1000.0,
            'width_inch' => 22.0,
            'length_inch' => 60.0,
            'gusset_cm' => 0.0,
            'gauge_caliber' => 0.0020,
            'unit_weight_kg' => 2.6400,
            'cost' => 10.21,
            'price' => 11.2310,
            'is_active' => true,
        ]);

        $this->assertTrue((bool)$prodFuelle->is_active);
        $this->assertTrue((bool)$prodPlana->is_active);
        $this->assertEquals(0.0020, (float)$prodFuelle->gauge_caliber);
        $this->assertEquals(0.0020, (float)$prodPlana->gauge_caliber);

        // Validar búsqueda en catálogo como en LabelGeneratorController
        $activeProducts = BagProduct::where('is_active', true)->orderBy('name')->get();
        $foundFuelle = $activeProducts->first(fn($p) => $p->sku === 'B04BAF226' || (str_contains(strtoupper($p->name), '22X60') && str_contains(strtoupper($p->name), 'FUELLE') && str_contains(strtoupper($p->name), 'C-2')));
        $foundPlana = $activeProducts->first(fn($p) => $p->sku === 'B04BAP2260' || (str_contains(strtoupper($p->name), '22X60') && str_contains(strtoupper($p->name), 'PLANA') && str_contains(strtoupper($p->name), 'C-2')));

        $this->assertNotNull($foundFuelle, 'El buscador debe encontrar la bolsa 22x60 Fuelle C-2');
        $this->assertNotNull($foundPlana, 'El buscador debe encontrar la bolsa 22x60 Plana C-2');
    }

    /** @test */
    public function it_can_create_and_query_vivero_10x22_c7_product()
    {
        $prodVivero = BagProduct::create([
            'sku' => 'B0410X22C7',
            'name' => 'BOLSA DE VIVERO 10X22 C-7 (10 MILLARES)',
            'category' => 'General',
            'material_type' => 'Baja Densidad',
            'production_formula_id' => 1,
            'sale_unit' => 'BULTO',
            'millar_per_bulto' => 10.0,
            'width_inch' => 10.0,
            'length_inch' => 22.0,
            'gusset_cm' => 0.0,
            'gauge_caliber' => 0.0070,
            'unit_weight_kg' => 15.4,
            'cost' => 44.50,
            'price' => 52.03,
            'is_active' => true,
        ]);

        $this->assertTrue((bool)$prodVivero->is_active);
        $this->assertEquals('B0410X22C7', $prodVivero->sku);
        $this->assertEquals(0.0070, (float)$prodVivero->gauge_caliber);
        $this->assertEquals(10.0, (float)$prodVivero->millar_per_bulto);
        $this->assertEquals(1.54, (float)$prodVivero->theoretical_weight_kg);

        // Validar que el generador de etiquetas lo localiza
        $activeProducts = BagProduct::where('is_active', true)->orderBy('name')->get();
        $found = $activeProducts->first(fn($p) => $p->sku === 'B0410X22C7' || (str_contains(strtoupper($p->name), '10X22') && str_contains(strtoupper($p->name), 'VIVERO')));

        $this->assertNotNull($found, 'El buscador debe encontrar la bolsa de vivero 10x22 C-7');
        $this->assertEquals('BULTO', $found->sale_unit);
    }
}
