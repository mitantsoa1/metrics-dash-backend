<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Domains\Analytics\Providers\AnalyticsMetricsStateProvider;

#[ApiResource(
    shortName: 'Metrics',
    operations: [
        new GetCollection(
            uriTemplate: '/api/v1/metrics',
            provider: AnalyticsMetricsStateProvider::class
        )
    ]
)]

class AnalyticsMetricsResource
{
    public ?string $instanceId = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public array $kpis = [];

    public array $timeSeries = [];

    public array $deviceBreakdown = [];

    public array $browserBreakdown = [];
}
