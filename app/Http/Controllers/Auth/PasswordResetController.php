<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domains\IAM\Actions\ForgotPasswordAction;
use App\Domains\IAM\Actions\ResetPasswordAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request, ForgotPasswordAction $action): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $res = $action->execute($request->input('email'));

        return response()->json($res);
    }

    public function resetPassword(Request $request, ResetPasswordAction $action): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $res = $action->execute(
            $request->input('email'),
            $request->input('token'),
            $request->input('password')
        );

        return response()->json($res);
    }
}
