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

    private function getResolvedCallbackUrl(): string
    {
        $configured = config('services.google.redirect');

        if (! empty($configured)) {
            if (str_ends_with($configured, '/google/redirect')) {
                return str_replace('/google/redirect', '/google/callback', $configured);
            }
            if (str_ends_with($configured, '/auth/google/redirect')) {
                return str_replace('/auth/google/redirect', '/auth/google/callback', $configured);
            }
            return $configured;
        }

        return url('/api/v1/auth/google/callback');
    }

    private function getFrontendUrl(Request $request): string
    {
        $envFrontend = env('FRONTEND_URL');
        if (! empty($envFrontend) && ! str_contains($envFrontend, 'localhost')) {
            return rtrim($envFrontend, '/');
        }

        $configFrontend = config('app.frontend_url');
        if (! empty($configFrontend) && ! str_contains($configFrontend, 'localhost')) {
            return rtrim($configFrontend, '/');
        }

        $referer = $request->header('Referer');
        if ($referer) {
            $parsed = parse_url($referer);
            if (! empty($parsed['scheme']) && ! empty($parsed['host'])) {
                return $parsed['scheme'] . '://' . $parsed['host'] . (! empty($parsed['port']) ? ':' . $parsed['port'] : '');
            }
        }

        return env('FRONTEND_URL', 'http://localhost:3000');
    }

    public function getUrl(Request $request)
    {
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            return response()->json([
                'status' => 'error',
                'error' => 'Veuillez configurer GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans le fichier .env.'
            ], 400);
        }

        $redirectUri = $this->getResolvedCallbackUrl();

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
        $frontendUrl = $this->getFrontendUrl($request);
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            return redirect($frontendUrl . '/callback/google?error=' . urlencode('Veuillez configurer GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET.'));
        }

        $redirectUri = $this->getResolvedCallbackUrl();

        $driver = Socialite::driver('google')->stateless();
        if ($redirectUri) {
            $driver->redirectUrl($redirectUri);
        }

        return $driver->redirect();
    }

    public function callback(Request $request, GoogleAuthAction $action)
    {
        $frontendUrl = $this->getFrontendUrl($request);

        try {
            if ($request->has('error')) {
                return redirect($frontendUrl . '/callback/google?error=' . urlencode('Authentification Google annulée ou échouée.'));
            }

            $redirectUri = $this->getResolvedCallbackUrl();

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
