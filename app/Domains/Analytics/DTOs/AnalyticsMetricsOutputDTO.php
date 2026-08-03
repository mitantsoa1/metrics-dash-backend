<?php

declare(strict_types=1);

namespace App\Domains\Analytics\DTOs;

readonly class AnalyticsMetricsOutputDTO
{
    /**
     * @param array<string, int> $kpis
     * @param array<int, array<string, mixed>> $timeSeries
     * @param array<string, int> $deviceBreakdown
     * @param array<string, int> $browserBreakdown
     */
    public function __construct(
        public string $instanceId,
        public string $startDate,
        public string $endDate,
        public array $kpis,
        public array $timeSeries,
        public array $deviceBreakdown,
        public array $browserBreakdown
    ) {}
}
