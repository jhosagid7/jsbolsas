<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = str_replace('\\', '/', storage_path('app/backups'));
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function test_backup_command_creates_valid_compressed_sql_file(): void
    {
        $exitCode = $this->artisan('db:backup', ['--keep' => 30])
            ->assertExitCode(0);

        $files = glob($this->backupDir . '/backup_*.sql.gz');
        $this->assertNotEmpty($files, 'Debe haberse generado al menos un archivo .sql.gz en storage/app/backups');

        $latestFile = end($files);
        $this->assertFileExists($latestFile);
        $this->assertGreaterThan(0, filesize($latestFile));

        // Verificar que es un archivo gzip válido y contiene sintaxis SQL
        $content = gzdecode(file_get_contents($latestFile));
        $this->assertNotFalse($content, 'El contenido debe descomprimirse con éxito');
        $this->assertStringContainsString('CREATE TABLE', $content);
    }

    public function test_backup_rotation_deletes_files_older_than_retention_days(): void
    {
        $backupService = app(DatabaseBackupService::class);

        // Crear archivo de prueba antiguo (35 días atrás)
        $oldFile = $this->backupDir . DIRECTORY_SEPARATOR . 'backup_test_old.sql.gz';
        file_put_contents($oldFile, gzencode('DUMMY OLD BACKUP CONTENT', 9));
        touch($oldFile, now()->subDays(35)->timestamp);

        $this->assertFileExists($oldFile);

        $deletedCount = $backupService->rotateBackups(30);
        $this->assertGreaterThanOrEqual(1, $deletedCount);
        $this->assertFileDoesNotExist($oldFile);
    }

    public function test_guest_cannot_download_database_backup(): void
    {
        $response = $this->get(route('sistema.respaldo.descargar'));
        $response->assertRedirect('/login');
    }

    public function test_operator_cannot_download_database_backup(): void
    {
        $operator = User::factory()->create([
            'profile' => 'operario',
        ]);

        $response = $this->actingAs($operator)->get(route('sistema.respaldo.descargar'));
        $response->assertStatus(403);
    }

    public function test_superadmin_can_download_database_backup(): void
    {
        $superadmin = User::factory()->create([
            'profile' => 'superadmin',
        ]);

        $response = $this->actingAs($superadmin)->get(route('sistema.respaldo.descargar'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-type') ?? '', 'gzip') ||
            str_contains($response->headers->get('content-disposition') ?? '', 'attachment')
        );
    }

    public function test_local_node_delegates_backup_upload_to_vps_endpoint(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/cloud-sync/upload-backup' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'message' => 'Respaldo recibido exitosamente en VPS y cargado en Google Drive.',
                'file' => 'test_backup.sql.gz',
                'drive_result' => [
                    'success' => true,
                    'file_id' => 'test_backup.sql.gz',
                    'web_link' => 'https://drive.google.com/drive/folders/test',
                ],
            ], 200),
        ]);

        $backupService = app(DatabaseBackupService::class);
        $testFile = $this->backupDir . '/test_backup.sql.gz';
        file_put_contents($testFile, gzencode('DUMMY BACKUP SQL CONTENT', 9));

        $result = $backupService->uploadToGoogleDrive($testFile);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('cargado en Google Drive', $result['message']);

        \Illuminate\Support\Facades\Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/cloud-sync/upload-backup')
                && $request->hasHeader('X-Sync-Token');
        });

        @unlink($testFile);
    }

    public function test_cloud_backup_endpoint_rejects_invalid_token(): void
    {
        $response = $this->withHeaders([
            'X-Sync-Token' => 'invalid_token_12345',
        ])->postJson('/api/cloud-sync/upload-backup');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'No autorizado: Token de sincronización inválido o ausente.',
            ]);
    }

    public function test_cloud_backup_endpoint_accepts_file_with_valid_token(): void
    {
        $token = config('services.cloud_sync.token', env('CLOUD_SYNC_TOKEN', 'jsbolsas_secure_sync_token_2026'));
        $file = \Illuminate\Http\UploadedFile::fake()->create('client_backup.sql.gz', 100, 'application/gzip');

        $response = $this->withHeaders([
            'X-Sync-Token' => $token,
        ])->post('/api/cloud-sync/upload-backup', [
            'backup_file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'file',
                'drive_result',
            ]);
    }
}
