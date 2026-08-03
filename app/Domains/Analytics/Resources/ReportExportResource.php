<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Resources;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Domains\Analytics\Processors\ReportExportStateProcessor;

#[ApiResource(
    shortName: 'ReportExport',
    operations: [
        new Post(
            uriTemplate: '/api/v1/reports/export',
            processor: ReportExportStateProcessor::class
        )
    ]
)]
class ReportExportResource
{
    public ?string $instanceId = null;

    public ?string $format = 'csv'; // csv ou json

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $downloadUrl = null;

    public ?int $exportedRecords = null;
}
