<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Mail\ResetPasswordMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordAction
{
    public function execute(string $email): array
    {
        $user = UserModel::where('email', $email)->first();

        // Pour des raisons de sécurité, répondre succès même si l'email n'existe pas
        if (! $user) {
            return [
                'message' => 'Si cette adresse e-mail existe dans notre système, un lien de réinitialisation vous a été envoyé.',
            ];
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        Mail::to($email)->send(new ResetPasswordMailable($resetUrl, $user->name));

        return [
            'message' => 'Si cette adresse e-mail existe dans notre système, un lien de réinitialisation vous a été envoyé.',
        ];
    }
}
