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
}
