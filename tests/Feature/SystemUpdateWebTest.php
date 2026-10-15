<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Process;

class SystemUpdateWebTest extends TestCase
{
    public function test_guest_cannot_trigger_system_update()
    {
        $response = $this->postJson('/sistema/actualizar');
        $response->assertStatus(401);
    }

    public function test_regular_operator_or_admin_cannot_trigger_system_update()
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $this->actingAs($operator);

        $response = $this->postJson('/sistema/actualizar');
        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_superadmin_can_trigger_system_update()
    {
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $this->actingAs($superadmin);

        // Fake all shell process calls to avoid executing actual git/artisan during testing
        Process::fake([
            'git fetch origin main' => Process::result(output: 'Fetching main', exitCode: 0),
            'git reset --hard origin/main' => Process::result(output: 'HEAD is now at c24d775f', exitCode: 0),
            'php artisan migrate --force' => Process::result(output: 'Nothing to migrate', exitCode: 0),
            'php artisan optimize:clear' => Process::result(output: 'Caches cleared', exitCode: 0),
            'php artisan view:cache' => Process::result(output: 'Views cached', exitCode: 0),
        ]);

        $response = $this->postJson('/sistema/actualizar');
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'logs']);
    }
}
