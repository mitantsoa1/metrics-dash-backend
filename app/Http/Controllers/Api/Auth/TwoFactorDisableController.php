<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TwoFactorDisableController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return ApiResponse::error('Mot de passe incorrect.');
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $this->auditService->log(
            AuditEvent::TWO_FACTOR_DISABLED,
            $user->id,
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success(null, 'L\'authentification à deux facteurs a été désactivée.');
    }
}
