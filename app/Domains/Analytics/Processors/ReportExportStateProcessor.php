<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Processors;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Domains\Analytics\Infrastructure\Models\DailyMetricModel;
use App\Domains\Analytics\Resources\ReportExportResource;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;

class ReportExportStateProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ReportExportResource
    {
        /** @var ReportExportResource $data */
        $instanceId = $data->instanceId;
        if (! $instanceId) {
            $instance = InstanceModel::first();
            $instanceId = $instance?->id ?? 'default';
        }

        $startDate = $data->startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $data->endDate ?? date('Y-m-d');
        $format = strtolower($data->format ?? 'csv');

        $metrics = DailyMetricModel::where('instance_id', $instanceId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $outputResource = new ReportExportResource();
        $outputResource->instanceId = $instanceId;
        $outputResource->format = $format;
        $outputResource->startDate = $startDate;
        $outputResource->endDate = $endDate;
        $outputResource->exportedRecords = $metrics->count();
        $outputResource->downloadUrl = "/api/v1/reports/download?instanceId={$instanceId}&format={$format}";

        return $outputResource;
    }
}
