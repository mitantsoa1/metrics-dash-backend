<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $tenantId): UserModel
    {
        return UserModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $tenantId, 'name' => $role,
            'email' => strtolower($role).Str::random(5).'@t.test', 'password' => 'secret123', 'role' => $role,
        ]);
    }

    private function makeTenantWithInstance(string $slug): array
    {
        $t = TenantModel::create(['id' => (string) Str::uuid(), 'name' => $slug, 'slug' => $slug]);
        $i = InstanceModel::create([
            'id' => (string) Str::uuid(), 'tenant_id' => $t->id, 'name' => $slug, 'domain' => $slug.'.test',
            'environment' => 'DEVELOPMENT', 'status' => 'ACTIVE',
        ]);

        return [$t, $i];
    }

    public function test_anonymous_cannot_read_metrics_or_download_reports(): void
    {
        [, $i] = $this->makeTenantWithInstance('a');
        $this->getJson("/api/v1/metrics?instanceId={$i->id}")->assertStatus(401);
        $this->getJson("/api/v1/reports/download?instanceId={$i->id}")->assertStatus(401);
    }

    public function test_viewer_cannot_mutate_instance_or_schedules(): void
    {
        [$t, $i] = $this->makeTenantWithInstance('a');
        Sanctum::actingAs($this->makeUser('VIEWER', $t->id));

        $this->patchJson("/api/v1/instances/{$i->id}", ['name' => 'x'], ['Content-Type' => 'application/merge-patch+json'])->assertStatus(403);
        $this->deleteJson("/api/v1/instances/{$i->id}")->assertStatus(403);
        $this->postJson('/api/v1/scheduled-reports', [
            'name' => 'n', 'instance_id' => $i->id, 'frequency' => 'WEEKLY', 'time' => '09:00',
            'formats' => 'PDF', 'recipients' => ['a@b.test'],
        ])->assertStatus(403);
    }

    public function test_user_cannot_reach_another_tenant(): void
    {
        [$t1] = $this->makeTenantWithInstance('a');
        [$t2, $i2] = $this->makeTenantWithInstance('b');
        Sanctum::actingAs($this->makeUser('OWNER', $t1->id));

        $this->getJson("/api/v1/tenants/{$t2->id}/instances")->assertStatus(403);
        $this->patchJson("/api/v1/instances/{$i2->id}", ['name' => 'x'], ['Content-Type' => 'application/merge-patch+json'])->assertStatus(403);
        $this->getJson("/api/v1/reports/download?instanceId={$i2->id}")->assertStatus(404);
    }
}
