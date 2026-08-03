<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Middleware;

use App\Domains\Analytics\Services\QuotaEvaluationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceQuotaMiddleware
{
    public function __construct(
        private readonly QuotaEvaluationService $quotaService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $instanceId = $request->header('X-Instance-Id') ?? $request->input('instanceId');

        if ($instanceId && $this->quotaService->isQuotaExceeded((string) $instanceId)) {
            return response()->json([
                'type' => '/errors/quota_exceeded',
                'title' => 'Quota Exceeded',
                'status' => 429,
                'detail' => 'Le quota mensuel d\'ingestion d\'événements autorise pour cette instance a ete atteint.',
            ], 429, ['X-Quota-Exceeded' => 'true']);
        }

        return $next($request);
    }
}
