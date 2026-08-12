<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\TotpHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class TwoFactorEnableController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return ApiResponse::error('L\'authentification à deux facteurs est déjà activée.');
        }

        $secret = TotpHelper::generateSecret();
        $recoveryCodes = TotpHelper::generateRecoveryCodes();

        // Encrypt secret with APP_KEY before storing
        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_recovery_codes = array_map(fn ($code) => Hash::make($code), $recoveryCodes);
        $user->save();

        $qrCodeUrl = TotpHelper::getOtpAuthUrl($user->email, $secret);

        return ApiResponse::success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes, // Displayed ONLY ONCE during setup
        ], 'Secret 2FA généré. Veuillez confirmer avec le code à 6 chiffres.');
    }
}
