<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperadminAndAdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_make_superadmin_command_creates_superadmin_role(): void
    {
        $this->artisan('make:superadmin', [
            '--org' => 'Superadmin Org',
            '--name' => 'Super User',
            '--email' => 'superadmin@example.com',
            '--password' => 'SuperPassword123!',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'superadmin@example.com',
            'role' => UserRole::SUPERADMIN->value,
        ]);
    }

    public function test_superadmin_and_admin_can_access_all_instances(): void
    {
        $tenant1 = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'Org 1', 'slug' => 'org-1']);
        $tenant2 = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'Org 2', 'slug' => 'org-2']);

        $superadmin = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant1->id,
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SUPERADMIN->value,
        ]);

        $instance1 = InstanceModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant1->id,
            'name' => 'App 1',
            'domain' => 'app1.com',
            'environment' => 'PRODUCTION',
            'status' => 'ACTIVE',
        ]);

        $instance2 = InstanceModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant2->id,
            'name' => 'App 2',
            'domain' => 'app2.com',
            'environment' => 'PRODUCTION',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($superadmin)->getJson('/api/v1/instances');
        $response->assertStatus(200);
        $data = $response->json();
        
        $ids = array_column($data, 'id');
        $this->assertContains($instance1->id, $ids);
        $this->assertContains($instance2->id, $ids);
    }

    public function test_superadmin_and_admin_can_crud_members(): void
    {
        $tenant1 = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'Org 1', 'slug' => 'org-1']);
        $tenant2 = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'Org 2', 'slug' => 'org-2']);

        $admin = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant1->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::ADMIN->value,
        ]);

        $member2 = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant2->id,
            'name' => 'Member Org 2',
            'email' => 'member2@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::VIEWER->value,
        ]);

        // 1. List all members
        $responseList = $this->actingAs($admin)->getJson('/api/v1/members');
        $responseList->assertStatus(200);

        // 2. Get single member
        $responseGet = $this->actingAs($admin)->getJson("/api/v1/members/{$member2->id}");
        $responseGet->assertStatus(200);
        $this->assertEquals('Member Org 2', $responseGet->json('name'));

        // 3. Update member
        $responseUpdate = $this->actingAs($admin)->putJson("/api/v1/members/{$member2->id}", [
            'name' => 'Updated Member Name',
            'role' => UserRole::ANALYST->value,
        ]);
        $responseUpdate->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id' => $member2->id,
            'name' => 'Updated Member Name',
            'role' => UserRole::ANALYST->value,
        ]);

        // 4. Delete member (Soft Delete with deleted_name & deleted_email recorded)
        $responseDelete = $this->actingAs($admin)->deleteJson("/api/v1/members/{$member2->id}");
        $responseDelete->assertStatus(204);
        $this->assertSoftDeleted('users', ['id' => $member2->id]);
        $this->assertDatabaseHas('users', [
            'id' => $member2->id,
            'deleted_name' => 'Updated Member Name',
            'deleted_email' => 'member2@example.com',
        ]);
    }
}
