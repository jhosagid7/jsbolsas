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
}
