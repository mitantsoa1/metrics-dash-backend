<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::unauthorized('Non authentifié.');
        }

        return ApiResponse::success(
            new UserResource($user),
            'Utilisateur authentifié récupéré avec succès.'
        );
    }
}
