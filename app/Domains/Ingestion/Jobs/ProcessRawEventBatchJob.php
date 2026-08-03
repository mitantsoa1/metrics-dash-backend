<?php

declare(strict_types=1);

namespace App\Domains\Ingestion\Jobs;

use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Ingestion\Services\EventAnonymizationService;
use App\Domains\Ingestion\Services\UserAgentParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRawEventBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $rawEventId,
        public readonly ?string $rawIp = null,
        public readonly ?string $userAgent = null
    ) {
        $this->onQueue('high-ingestion');
    }

    public function handle(
        EventAnonymizationService $anonymizer,
        UserAgentParserService $uaParser
    ): void {
        $event = RawEventModel::find($this->rawEventId);
        if (! $event) {
            return;
        }

        $ip = $this->rawIp ?? request()?->ip() ?? '127.0.0.1';
        $ua = $this->userAgent ?? request()?->userAgent() ?? '';

        // 1. Anonymisation RGPD de l'IP & Visitor ID
        $anonymizedIp = $anonymizer->anonymizeIp($ip);
        $anonymizedVisitorId = $anonymizer->generateAnonymousVisitorId(
            $event->visitor_id,
            $ip,
            $ua
        );

        // 2. Parser le User-Agent
        $uaMetadata = $uaParser->parse($ua);

        // 3. Mettre à jour le contexte enrichi en BDD
        $existingContext = $event->context ?? [];
        $enrichedContext = array_merge($existingContext, [
            'anonymized_ip' => $anonymizedIp,
            'device_type' => $uaMetadata['device_type'],
            'browser' => $uaMetadata['browser'],
            'os' => $uaMetadata['os'],
            'processed_at' => now()->toIso8601String(),
        ]);

        $event->update([
            'visitor_id' => $anonymizedVisitorId,
            'context' => $enrichedContext,
        ]);
    }
}
