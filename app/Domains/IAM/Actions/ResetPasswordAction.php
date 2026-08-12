<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\Infrastructure\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordAction
{
    public function execute(string $email, string $token, string $password): array
    {
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $token)
            ->first();

        if (! $resetRecord) {
            throw ValidationException::withMessages([
                'token' => ['Le jeton de réinitialisation est invalide ou expiré.'],
            ]);
        }

        // Vérifier si le jeton est expiré (ex: 60 minutes)
        $createdAt = \Carbon\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['Le jeton de réinitialisation a expiré. Veuillez refaire une demande.'],
            ]);
        }

        $user = UserModel::where('email', $email)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Utilisateur introuvable.'],
            ]);
        }

        $user->password = Hash::make($password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return [
            'message' => 'Votre mot de passe a été réinitialisé avec succès !',
        ];
    }
}
