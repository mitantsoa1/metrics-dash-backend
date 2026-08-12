<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domains\IAM\Actions\GoogleAuthAction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            return redirect($frontendUrl . '/callback/google?error=' . urlencode('Veuillez configurer GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans le fichier .env du backend.'));
        }

        return Socialite::driver('google')
            ->stateless()
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request, GoogleAuthAction $action)
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        try {
            if ($request->has('error')) {
                return redirect($frontendUrl . '/callback/google?error=' . urlencode('Authentification Google annulée ou échouée.'));
            }

            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = $action->handleGoogleUser($googleUser);

            $token = $user->createToken('google-auth-token')->plainTextToken;

            $redirectParams = http_build_query([
                'token' => $token,
                'userId' => $user->id,
                'tenantId' => $user->tenant_id,
                'email' => $user->email,
                'role' => $user->role,
            ]);

            return redirect($frontendUrl . '/callback/google?' . $redirectParams);
        } catch (Throwable $e) {
            return redirect($frontendUrl . '/callback/google?error=' . urlencode('Erreur lors de la connexion Google: ' . $e->getMessage()));
        }
    }
}
