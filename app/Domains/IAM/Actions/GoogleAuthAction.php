<?php

declare(strict_types=1);

namespace App\Domains\IAM\Actions;

use App\Domains\IAM\Infrastructure\Models\TenantModel;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use Illuminate\Support\Str;

class GoogleAuthAction
{
    public function handleGoogleUser(object $googleUser): UserModel
    {
        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $name = $googleUser->getName() ?? 'Utilisateur Google';
        $avatar = $googleUser->getAvatar();

        // 1. Chercher un utilisateur existant par google_id ou email
        $user = UserModel::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->google_id = $googleId;
            if ($avatar && ! $user->avatar) {
                $user->avatar = $avatar;
            }
            $user->save();

            return $user;
        }

        // 2. Créer une nouvelle organisation tenant et un utilisateur OWNER si nouveau
        $tenantId = (string) Str::uuid();
        $userId = (string) Str::uuid();

        $tenantName = "Organisation " . ($name ?: 'Google');

        TenantModel::create([
            'id' => $tenantId,
            'name' => $tenantName,
            'slug' => Str::slug($tenantName) . '-' . Str::random(5),
            'status' => 'ACTIVE',
        ]);

        $newUser = UserModel::create([
            'id' => $userId,
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'password' => null,
            'email_verified_at' => now(),
            'role' => 'OWNER',
            'google_id' => $googleId,
            'avatar' => $avatar,
        ]);

        return $newUser;
    }
}
