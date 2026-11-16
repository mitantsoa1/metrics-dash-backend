<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Services\TokenService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\AuthCookieManager;
use App\Support\TotpHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class TwoFactorVerifyController extends Controller
{
    public function __construct(
        private readonly TokenService $tokenService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string',
        ]);

        $challengeToken = $request->input('challenge_token');
        $userId = cache()->get("2fa_challenge_{$challengeToken}");

        if (! $userId) {
            return ApiResponse::unauthorized('Le jeton de défi 2FA est expiré ou invalide. Veuillez vous reconnecter.');
        }

        $user = UserModel::find($userId);
        if (! $user || ! $user->two_factor_secret) {
            return ApiResponse::unauthorized('Utilisateur invalide.');
        }

        $code = trim($request->input('code'));
        $secret = Crypt::decryptString($user->two_factor_secret);

        $isValid = TotpHelper::verifyCode($secret, $code);

        // If not valid, check recovery codes
        if (! $isValid && ! empty($user->two_factor_recovery_codes)) {
            foreach ($user->two_factor_recovery_codes as $index => $hashedRecoveryCode) {
                if (Hash::check($code, $hashedRecoveryCode)) {
                    $isValid = true;
                    // Consume used recovery code
                    $remainingCodes = $user->two_factor_recovery_codes;
                    unset($remainingCodes[$index]);
                    $user->two_factor_recovery_codes = array_values($remainingCodes);
                    $user->save();
                    break;
                }
            }
        }

        if (! $isValid) {
            return ApiResponse::error('Code de vérification ou code de récupération invalide.', [], 401);
        }

        // Consume challenge token
        cache()->forget("2fa_challenge_{$challengeToken}");

        // Generate dual-token pair
        $tokens = $this->tokenService->generateTokenPair(
            $user,
            $request->ip(),
            $request->userAgent()
        );

        $response = ApiResponse::success([
            'user' => [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'token_type' => 'Bearer',
            'expires_at' => $tokens['access_token_expires_at'],
        ], 'Authentification 2FA réussie.');

        return AuthCookieManager::attachAuthCookies(
            $response,
            $tokens['access_token'],
            $tokens['refresh_token']
        );
    }
}
