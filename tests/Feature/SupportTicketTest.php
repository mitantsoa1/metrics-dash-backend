<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\Support\Infrastructure\Models\SupportTicketModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_support_ticket_publicly(): void
    {
        $payload = [
            'name' => 'Jean Commercial',
            'email' => 'jean@acme.com',
            'role' => 'Commercial / Account Manager',
            'subject' => 'Question sur le quota de télémétrie',
            'message' => 'Bonjour, nous souhaitons augmenter le quota de notre instance client de 50 000 à 100 000 événements.',
            'priority' => 'normal',
        ];

        $response = $this->postJson('/api/v1/support/tickets', $payload);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.name', 'Jean Commercial')
                 ->assertJsonPath('data.email', 'jean@acme.com')
                 ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('support_tickets', [
            'email' => 'jean@acme.com',
            'status' => 'open',
        ]);
    }

    public function test_cannot_submit_invalid_ticket(): void
    {
        $response = $this->postJson('/api/v1/support/tickets', [
            'name' => 'J', // too short
            'email' => 'invalid-email',
            'role' => '',
            'subject' => 'Hi', // too short
            'message' => 'Short', // too short
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'role', 'subject', 'message']);
    }

    public function test_non_admin_cannot_access_admin_tickets_api(): void
    {
        $tenant = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'Org 1', 'slug' => 'org-1']);
        $viewer = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::VIEWER->value,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/admin/support/tickets');
        $response->assertStatus(403);
    }

    public function test_admin_can_list_and_update_ticket(): void
    {
        $tenant = TenantModel::create(['id' => (string) Str::uuid(), 'name' => 'Org 1', 'slug' => 'org-1']);
        $admin = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => UserRole::ADMIN->value,
        ]);

        $ticket = SupportTicketModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'name' => 'Pierre Client',
            'email' => 'pierre@client.com',
            'role' => 'Customer Success Manager (CSM)',
            'subject' => 'Alerte quota HTTP 429',
            'message' => 'Le client Acme a reçu une alerte 429 ce matin.',
            'status' => 'open',
            'priority' => 'high',
        ]);

        Sanctum::actingAs($admin);

        // List
        $listResponse = $this->getJson('/api/v1/admin/support/tickets');
        $listResponse->assertStatus(200)
                     ->assertJsonPath('success', true)
                     ->assertJsonPath('stats.total', 1)
                     ->assertJsonPath('stats.open', 1);

        // Update status to in_progress
        $updateResponse = $this->patchJson("/api/v1/admin/support/tickets/{$ticket->id}", [
            'status' => 'in_progress',
            'admin_notes' => 'Pris en charge par l\'équipe support.',
        ]);

        $updateResponse->assertStatus(200)
                       ->assertJsonPath('success', true)
                       ->assertJsonPath('data.status', 'in_progress')
                       ->assertJsonPath('data.admin_notes', 'Pris en charge par l\'équipe support.');

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }
}
