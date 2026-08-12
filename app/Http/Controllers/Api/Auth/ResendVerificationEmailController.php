<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendVerificationEmailController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::error('Adresse e-mail déjà vérifiée.');
        }

        $user->sendEmailVerificationNotification();

        $this->auditService->log(
            AuditEvent::EMAIL_VERIFICATION_RESENT,
            $user->id,
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success(null, 'L\'e-mail de vérification a été renvoyé avec succès.');
    }
}
