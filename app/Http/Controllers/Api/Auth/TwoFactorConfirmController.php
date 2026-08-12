<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\TotpHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class TwoFactorConfirmController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return ApiResponse::error('Aucune configuration 2FA en cours.');
        }

        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
        } catch (\Throwable) {
            return ApiResponse::error('Secret 2FA corrompu.');
        }

        if (! TotpHelper::verifyCode($secret, $request->input('code'))) {
            return ApiResponse::error('Code de vérification 2FA invalide.');
        }

        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->auditService->log(
            AuditEvent::TWO_FACTOR_ENABLED,
            $user->id,
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success(null, 'L\'authentification à deux facteurs a été activée avec succès.');
    }
}
