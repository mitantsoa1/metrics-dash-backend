<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly ResetPasswordAction $resetPasswordAction
    ) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->resetPasswordAction->execute(
                trim($request->input('email')),
                $request->input('token'),
                $request->input('password')
            );

            return ApiResponse::success(null, $result['message']);
        } catch (Throwable $e) {
            return ApiResponse::fromException($e, 400);
        }
    }
}
