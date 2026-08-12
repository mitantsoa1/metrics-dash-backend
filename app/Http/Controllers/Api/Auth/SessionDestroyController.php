<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Infrastructure\Models\UserRefreshTokenModel;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionDestroyController extends Controller
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $token = UserRefreshTokenModel::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $token) {
            return ApiResponse::notFound('Session introuvable.');
        }

        $token->update(['revoked_at' => now()]);

        return ApiResponse::success(null, 'La session a été révoquée avec succès.');
    }
}
