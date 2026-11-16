<?php

declare(strict_types=1);

namespace App\Domains\IAM\Services;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Infrastructure\Models\UserRefreshTokenModel;
use Illuminate\Support\Str;

class TokenService
{
    private const ACCESS_TOKEN_EXPIRY_MINUTES = 15;
    private const REFRESH_TOKEN_EXPIRY_DAYS = 14;

    /**
     * Generate a new access+refresh token pair for the user.
     *
     * @return array{access_token: string, refresh_token: string, access_token_expires_at: string, refresh_token_model: UserRefreshTokenModel}
     */
    public function generateTokenPair(
        UserModel $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceName = null,
    ): array {
        // Access token via Sanctum (short-lived)
        $expiresAt = now()->addMinutes(self::ACCESS_TOKEN_EXPIRY_MINUTES);
        $sanctumToken = $user->createToken(
            'access-token',
            ['*'],
            $expiresAt,
        );

        // Refresh token (opaque, stored hashed)
        $plainRefreshToken = Str::random(64);
        $tokenHash = hash('sha256', $plainRefreshToken);

        $refreshTokenModel = UserRefreshTokenModel::create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'device_name' => $deviceName ?? $this->parseDeviceName($userAgent),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_EXPIRY_DAYS),
        ]);

        return [
            'access_token' => $sanctumToken->plainTextToken,
            'refresh_token' => $plainRefreshToken,
            'access_token_expires_at' => $expiresAt->toIso8601String(),
            'refresh_token_model' => $refreshTokenModel,
        ];
    }

    /**
     * Rotate a refresh token: validate the old one, revoke it, and issue a new pair.
     *
     * @return array{access_token: string, refresh_token: string, access_token_expires_at: string}|null
     */
    public function rotateRefreshToken(
        string $plainRefreshToken,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ?array {
        $tokenHash = hash('sha256', $plainRefreshToken);

        $existingToken = UserRefreshTokenModel::where('token_hash', $tokenHash)->first();

        if (! $existingToken) {
            return null;
        }

        // REUSE DETECTION: If this token is already revoked, it means it's a replay attack.
        // Revoke ALL tokens for this user as a security measure.
        if ($existingToken->isRevoked()) {
            $this->revokeAllUserTokens($existingToken->user_id);
            app(AuditService::class)->log(
                AuditEvent::REFRESH_TOKEN_REUSE_DETECTED,
                $existingToken->user_id,
                $ipAddress,
                $userAgent,
                ['revoked_token_id' => $existingToken->id]
            );
            return null;
        }

        // Check expiration
        if ($existingToken->isExpired()) {
            $existingToken->update(['revoked_at' => now()]);
            return null;
        }

        $user = UserModel::find($existingToken->user_id);
        if (! $user) {
            return null;
        }

        // Delete expired Sanctum tokens for this user
        $user->tokens()->where('expires_at', '<', now())->delete();

        // Generate new pair
        $newPair = $this->generateTokenPair($user, $ipAddress, $userAgent, $existingToken->device_name);

        // Revoke old refresh token and link to new one
        $existingToken->update([
            'revoked_at' => now(),
            'replaced_by_id' => $newPair['refresh_token_model']->id,
        ]);

        app(AuditService::class)->log(
            AuditEvent::REFRESH_TOKEN_ROTATED,
            $user->id,
            $ipAddress,
            $userAgent,
        );

        return [
            'access_token' => $newPair['access_token'],
            'refresh_token' => $newPair['refresh_token'],
            'access_token_expires_at' => $newPair['access_token_expires_at'],
        ];
    }

    /**
     * Revoke a specific refresh token by its plain text value.
     */
    public function revokeRefreshToken(string $plainRefreshToken): bool
    {
        $tokenHash = hash('sha256', $plainRefreshToken);
        $token = UserRefreshTokenModel::where('token_hash', $tokenHash)->first();

        if ($token && ! $token->isRevoked()) {
            $token->update(['revoked_at' => now()]);
            return true;
        }

        return false;
    }

    /**
     * Revoke all refresh tokens and Sanctum tokens for a user.
     */
    public function revokeAllUserTokens(string $userId): void
    {
        UserRefreshTokenModel::where('user_id', $userId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $user = UserModel::find($userId);
        $user?->tokens()->delete();
    }

    /**
     * Revoke all tokens EXCEPT the current session.
     */
    public function revokeOtherUserTokens(string $userId, ?string $currentRefreshToken = null): void
    {
        $query = UserRefreshTokenModel::where('user_id', $userId)
            ->whereNull('revoked_at');

        if ($currentRefreshToken) {
            $currentHash = hash('sha256', $currentRefreshToken);
            $query->where('token_hash', '!=', $currentHash);
        }

        $query->update(['revoked_at' => now()]);
    }

    private function parseDeviceName(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown Device';
        }

        // Simple UA parsing
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'iPad')) return 'iPad';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'Windows')) return 'Windows PC';
        if (str_contains($userAgent, 'Macintosh')) return 'Mac';
        if (str_contains($userAgent, 'Linux')) return 'Linux';

        return 'Web Browser';
    }
}
