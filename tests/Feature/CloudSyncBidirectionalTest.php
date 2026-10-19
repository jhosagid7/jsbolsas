<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagShift;
use App\Models\BagProduction;
use App\Models\RawMaterial;
use App\Models\RawMaterialStock;
use App\Models\RawMaterialTransfer;
use App\Models\ProductionFormula;
use App\Models\CloudSyncLog;
use App\Services\CloudSyncService;

class CloudSyncBidirectionalTest extends TestCase
{
    use RefreshDatabase;

    protected string $syncToken = 'jsbolsas_secure_sync_token_2026';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.cloud_sync.token' => $this->syncToken]);
        config(['services.cloud_sync.url' => 'https://bolsas.plasticosmyf.com']);
    }

    public function test_handshake_endpoint_authenticates_correctly()
    {
        // Token inválido -> 401
        $responseBad = $this->postJson('/api/cloud-sync/handshake', [], [
            'X-Sync-Token' => 'token_invalido',
        ]);
        $responseBad->assertStatus(401);

        // Token válido -> 200
        $responseOk = $this->postJson('/api/cloud-sync/handshake', [], [
            'X-Sync-Token' => $this->syncToken,
        ]);
        $responseOk->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'node_role', 'server_time', 'app_version']);
    }

    public function test_push_endpoint_imports_deltas_idempotently()
    {
        // Preparar datos base
        $email = 'operario_sync_' . Str::random(6) . '@test.com';
        $user = User::firstOrCreate(['email' => $email], [
            'name' => 'Operario Sync Test',
            'password' => bcrypt('password123'),
            'role' => 'operator',
        ]);
        $machine = BagMachine::firstOrCreate(['code' => 'EXT-SYNC-01'], [
            'name' => 'Extrusora Sync 1',
            'type' => 'extrusora',
            'is_active' => true,
        ]);
        $product = BagProduct::firstOrCreate(['sku' => 'BOB-SYNC-30'], [
            'name' => 'Bobina Sync 30cm',
            'category' => 'bobina',
            'material_type' => 'baja',
            'sale_unit' => 'kg',
            'unit_weight_kg' => 15.0,
        ]);

        $shiftSyncId = 'SHIFT-UUID-' . Str::uuid();
        $prodSyncId = 'PROD-UUID-' . Str::uuid();

        $payload = [
            'shifts' => [
                [
                    'sync_id'        => $shiftSyncId,
                    'user_email'     => $email,
                    'machine_code'   => 'EXT-SYNC-01',
                    'shift_type'     => 'diurno',
                    'start_time'     => now()->subHours(4)->toISOString(),
                    'end_time'       => null,
                    'status'         => 'open',
                    'total_packages' => 1,
                    'total_weight'   => 25.5,
                    'notes'          => 'Turno sincronizado de prueba',
                    'updated_at'     => now()->toISOString(),
                ]
            ],
            'productions' => [
                [
                    'sync_id'                  => $prodSyncId,
                    'shift_sync_id'            => $shiftSyncId,
                    'product_sku'              => 'BOB-SYNC-30',
                    'machine_code'             => 'EXT-SYNC-01',
                    'user_email'               => $email,
                    'quantity'                 => 1,
                    'weight'                   => 25.5,
                    'original_weight'          => 25.5,
                    'waste_weight_kg'          => 0.5,
                    'is_waste_record'          => false,
                    'recorded_at'              => now()->toISOString(),
                    'status'                   => 'pending_review',
                    'is_printed'               => true,
                    'weight_quality_grade'     => 'optimo',
                    'weight_deviation_percent' => 0.0,
                    'qr_code'                  => 'QR-SYNC-001',
                    'metadata'                 => ['test' => true],
                    'updated_at'               => now()->toISOString(),
                ]
            ],
        ];

        $response = $this->postJson('/api/cloud-sync/push', [
            'payload' => $payload,
        ], [
            'X-Sync-Token' => $this->syncToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('processed_count', 2);

        // Verificar inserción en DB
        $this->assertDatabaseHas('bag_shifts', [
            'sync_id' => $shiftSyncId,
            'user_id' => $user->id,
            'machine_id' => $machine->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('bag_productions', [
            'sync_id' => $prodSyncId,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'weight' => 25.5,
        ]);

        // Verificar Idempotencia (segundo envío con el mismo payload no duplica)
        $responseSecond = $this->postJson('/api/cloud-sync/push', [
            'payload' => $payload,
        ], [
            'X-Sync-Token' => $this->syncToken,
        ]);

        $responseSecond->assertStatus(200);
        $this->assertEquals(1, BagProduction::where('sync_id', $prodSyncId)->count());
    }

    public function test_pull_endpoint_exports_deltas_correctly()
    {
        $product = BagProduct::firstOrCreate(['sku' => 'BOB-PULL-TEST'], [
            'name' => 'Bobina Pull Test',
            'category' => 'bobina',
            'material_type' => 'baja',
            'sale_unit' => 'kg',
            'unit_weight_kg' => 20.0,
        ]);

        $response = $this->postJson('/api/cloud-sync/pull', [
            'since' => now()->subDay()->toISOString(),
        ], [
            'X-Sync-Token' => $this->syncToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'payload' => [
                    'shifts',
                    'productions',
                    'raw_materials',
                    'stocks',
                    'transfers',
                    'formulas',
                    'products',
                ],
                'server_time',
            ]);
    }

    public function test_artisan_command_sync_with_cloud_runs_successfully()
    {
        Http::fake([
            'https://bolsas.plasticosmyf.com/api/cloud-sync/handshake' => Http::response([
                'success'     => true,
                'node_role'   => 'cloud',
                'server_time' => now()->toISOString(),
                'app_version' => '2.3.2',
            ], 200),
            'https://bolsas.plasticosmyf.com/api/cloud-sync/push' => Http::response([
                'success'         => true,
                'processed_count' => 0,
                'message'         => 'OK',
            ], 200),
            'https://bolsas.plasticosmyf.com/api/cloud-sync/pull' => Http::response([
                'success'     => true,
                'payload'     => [
                    'shifts'        => [],
                    'productions'   => [],
                    'raw_materials' => [],
                    'stocks'        => [],
                    'transfers'     => [],
                    'formulas'      => [],
                    'products'      => [],
                ],
                'server_time' => now()->toISOString(),
            ], 200),
        ]);

        $this->artisan('cloud:sync', ['--direction' => 'both'])
            ->expectsOutputToContain('Conexión con VPS establecida')
            ->expectsOutputToContain('Sincronización Bidireccional Completada')
            ->assertExitCode(0);
    }

    public function test_web_routes_for_cloud_sync_status_and_manual()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // No autenticado -> redirect login
        $this->getJson('/cloud-sync/status')->assertStatus(401);

        // Autenticado
        $this->actingAs($admin);

        $statusRes = $this->getJson('/cloud-sync/status');
        $statusRes->assertStatus(200)
            ->assertJsonStructure(['pending_count', 'last_sync', 'node_role']);

        Http::fake([
            'https://bolsas.plasticosmyf.com/api/cloud-sync/handshake' => Http::response(['success' => true], 200),
            'https://bolsas.plasticosmyf.com/api/cloud-sync/push' => Http::response(['success' => true], 200),
            'https://bolsas.plasticosmyf.com/api/cloud-sync/pull' => Http::response(['success' => true, 'payload' => []], 200),
        ]);

        $manualRes = $this->postJson('/cloud-sync/manual');
        $manualRes->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_import_deltas_handles_missing_foreign_keys_and_empty_local_db_without_error()
    {
        $service = app(CloudSyncService::class);

        // Database is completely empty of machines, users, products
        $this->assertEquals(0, BagMachine::count());

        $payload = [
            'shifts' => [
                [
                    'sync_id'        => 'SHIFT-TEST-001',
                    'user_email'     => 'nuevo_operario@test.com',
                    'machine_code'   => 'SEL-NUEVA-01',
                    'shift_type'     => 'diurno',
                    'start_time'     => now()->toISOString(),
                    'end_time'       => null,
                    'status'         => 'open',
                    'total_packages' => 2,
                    'total_weight'   => 50.0,
                    'notes'          => null,
                ]
            ],
            'productions' => [
                [
                    'sync_id'                  => 'PROD-TEST-001',
                    'shift_sync_id'            => 'SHIFT-TEST-001',
                    'product_sku'              => 'SKU-NUEVO-99',
                    'machine_code'             => 'SEL-NUEVA-01',
                    'user_email'               => 'nuevo_operario@test.com',
                    'quantity'                 => 10,
                    'weight'                   => 25.0,
                    'original_weight'          => 25.0,
                    'waste_weight_kg'          => 0.5,
                    'recorded_at'              => now()->toISOString(),
                    'status'                   => 'pending_review',
                    'is_printed'               => false,
                    'weight_quality_grade'     => 'A',
                    'weight_deviation_percent' => 0.0,
                    'completed_packages_count' => 1,
                    'is_package_completed'     => true,
                    'qr_code'                  => 'PKG-TEST-001',
                ]
            ],
        ];

        // Must import without throwing Attempt to read property id on null
        $imported = $service->importDeltas($payload);

        $this->assertEquals(2, $imported);
        $this->assertDatabaseHas('bag_machines', ['code' => 'SEL-NUEVA-01']);
        $this->assertDatabaseHas('bag_shifts', ['sync_id' => 'SHIFT-TEST-001']);
        $this->assertDatabaseHas('bag_productions', ['sync_id' => 'PROD-TEST-001']);
    }
}
