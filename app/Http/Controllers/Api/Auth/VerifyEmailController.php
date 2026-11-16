<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Infrastructure\Models\UserModel;
use App\Domains\IAM\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function __invoke(Request $request, string $id, string $hash): JsonResponse
    {
        $user = UserModel::find($id);

        if (! $user) {
            return ApiResponse::notFound('Utilisateur introuvable.');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return ApiResponse::error('Lien de vérification invalide.', [], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Adresse e-mail déjà vérifiée.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $this->auditService->log(
                AuditEvent::EMAIL_VERIFIED,
                $user->id,
                $request->ip(),
                $request->userAgent()
            );
        }

        return ApiResponse::success(null, 'Adresse e-mail vérifiée avec succès.');
    }
}
