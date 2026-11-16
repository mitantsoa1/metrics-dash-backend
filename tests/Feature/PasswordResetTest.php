<?php

namespace Tests\Feature;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Mail\ResetPasswordMailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_generates_token_and_sends_email(): void
    {
        Mail::fake();

        $tenant = TenantModel::create([
            'id' => (string) Str::uuid(),
            'name' => 'Acme Test Corp',
            'slug' => 'acme-test-corp',
            'status' => 'ACTIVE',
        ]);

        $user = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Alice Test',
            'email' => 'alice.test@acme.com',
            'password' => Hash::make('Secret123!'),
            'role' => 'OWNER',
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Mail::assertSent(ResetPasswordMailable::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_reset_password_updates_user_password(): void
    {
        $tenant = TenantModel::create([
            'id' => (string) Str::uuid(),
            'name' => 'Acme Reset Corp',
            'slug' => 'acme-reset-corp',
            'status' => 'ACTIVE',
        ]);

        $user = UserModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'name' => 'Bob Reset',
            'email' => 'bob.reset@acme.com',
            'password' => Hash::make('OldPassword123!'),
            'role' => 'OWNER',
        ]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewSecretPassword123!',
            'password_confirmation' => 'NewSecretPassword123!',
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword123!', $user->password));

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }
}
