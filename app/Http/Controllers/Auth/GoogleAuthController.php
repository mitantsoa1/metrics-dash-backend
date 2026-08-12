<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domains\IAM\Actions\GoogleAuthAction;
use App\Domains\IAM\Enums\AuditEvent;
use App\Domains\IAM\Services\AuditService;
use App\Domains\IAM\Services\TokenService;
use App\Support\AuthCookieManager;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly AuditService $auditService
    ) {}

    public function getUrl(Request $request)
    {
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            return response()->json([
                'status' => 'error',
                'error' => 'Veuillez configurer GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans le fichier .env.'
            ], 400);
        }

        $redirectUri = config('services.google.redirect');

        $driver = Socialite::driver('google')->stateless();
        if ($redirectUri) {
            $driver->redirectUrl($redirectUri);
        }

        $targetUrl = $driver->redirect()->getTargetUrl();

        return response()->json([
            'status' => 'success',
            'url' => $targetUrl,
        ]);
    }

    public function redirect(Request $request)
    {
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
            return redirect($frontendUrl . '/callback/google?error=' . urlencode('Veuillez configurer GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET.'));
        }

        $redirectUri = config('services.google.redirect');

        $driver = Socialite::driver('google')->stateless();
        if ($redirectUri) {
            $driver->redirectUrl($redirectUri);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, GoogleAuthAction $action)
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

        try {
            if ($request->has('error')) {
                return redirect($frontendUrl . '/callback/google?error=' . urlencode('Authentification Google annulée ou échouée.'));
            }

            $redirectUri = config('services.google.redirect');

            $driver = Socialite::driver('google')->stateless();
            if ($redirectUri) {
                $driver->redirectUrl($redirectUri);
            }

            $googleUser = $driver->user();
            $user = $action->handleGoogleUser($googleUser);

            $tokens = $this->tokenService->generateTokenPair($user, $request->ip(), $request->userAgent());

            $this->auditService->log(
                AuditEvent::GOOGLE_LOGIN,
                $user->id,
                $request->ip(),
                $request->userAgent()
            );

            $redirectUrl = $frontendUrl . '/callback/google?success=true&token=' . urlencode($tokens['access_token']);
            $redirectResponse = redirect($redirectUrl);

            // Convert RedirectResponse into JsonResponse-style cookies
            $isSecure = $request->secure() || config('app.env') === 'production' || str_starts_with($frontendUrl, 'https://');
            $redirectResponse->cookie(AuthCookieManager::ACCESS_TOKEN_COOKIE, $tokens['access_token'], 15, '/', null, $isSecure, true, false, 'Lax');
            $redirectResponse->cookie(AuthCookieManager::REFRESH_TOKEN_COOKIE, $tokens['refresh_token'], 14 * 1440, '/', null, $isSecure, true, false, 'Lax');

            return $redirectResponse;
        } catch (Throwable $e) {
            return redirect($frontendUrl . '/callback/google?error=' . urlencode('Erreur lors de la connexion Google: ' . $e->getMessage()));
        }
    }
}
