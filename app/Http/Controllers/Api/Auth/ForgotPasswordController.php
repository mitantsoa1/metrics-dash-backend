<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Domains\IAM\Actions\ForgotPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly ForgotPasswordAction $forgotPasswordAction
    ) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->forgotPasswordAction->execute(trim($request->input('email')));
            return ApiResponse::success(null, $result['message']);
        } catch (Throwable $e) {
            return ApiResponse::fromException($e);
        }
    }
}
