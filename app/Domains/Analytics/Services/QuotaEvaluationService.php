<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;

class QuotaEvaluationService
{
    public const DEFAULT_MONTHLY_QUOTA = 1000000; // 1 million d'événements par mois

    /**
     * Vérifie si le quota mensuel d'une instance a été dépassé.
     */
    public function isQuotaExceeded(string $instanceId, int $monthlyLimit = self::DEFAULT_MONTHLY_QUOTA): bool
    {
        $startOfMonth = now()->startOfMonth();
        $count = RawEventModel::where('instance_id', $instanceId)
            ->where('occurred_at', '>=', $startOfMonth)
            ->count();

        return $count >= $monthlyLimit;
    }

    /**
     * Retourne l'état d'utilisation du quota pour une instance donnée.
     *
     * @return array{used: int, limit: int, percentage: float, exceeded: bool}
     */
    public function getQuotaUsage(string $instanceId, int $monthlyLimit = self::DEFAULT_MONTHLY_QUOTA): array
    {
        $startOfMonth = now()->startOfMonth();
        $used = RawEventModel::where('instance_id', $instanceId)
            ->where('occurred_at', '>=', $startOfMonth)
            ->count();

        $percentage = min(100.0, round(($used / max(1, $monthlyLimit)) * 100, 2));

        return [
            'used' => $used,
            'limit' => $monthlyLimit,
            'percentage' => $percentage,
            'exceeded' => $used >= $monthlyLimit,
        ];
    }
}
