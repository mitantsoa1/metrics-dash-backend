<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Analytics\Infrastructure\Models\MetricWindowModel;
use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Agrégation des métriques par fenêtre de 10 minutes (PU-52).
 * Les fenêtres sont alignées sur :00/:10/:20… (UTC) et recalculées de façon idempotente :
 * un événement arrivé en retard est repris au passage suivant.
 */
class WindowAggregationService
{
    public const WINDOW_MINUTES = 10;

    public function windowStart(Carbon $at): Carbon
    {
        $utc = $at->copy()->utc()->second(0);

        return $utc->minute(intdiv($utc->minute, self::WINDOW_MINUTES) * self::WINDOW_MINUTES);
    }

    /**
     * @return array{window_start: string, instances: int, metrics: int}
     */
    public function aggregateWindow(Carbon $windowStart): array
    {
        $start = $this->windowStart($windowStart);
        $end = $start->copy()->addMinutes(self::WINDOW_MINUTES);
        $summary = ['window_start' => $start->toIso8601String(), 'instances' => 0, 'metrics' => 0];

        foreach (InstanceModel::pluck('id') as $instanceId) {
            $values = $this->compute((string) $instanceId, $start, $end);

            foreach ($values as $name => $value) {
                MetricWindowModel::updateOrCreate(
                    ['instance_id' => $instanceId, 'window_start' => $start, 'metric_name' => $name],
                    ['id' => (string) Str::uuid(), 'metric_value' => $value]
                );
                $summary['metrics']++;
            }
            $summary['instances']++;
        }

        return $summary;
    }

    /** @return array<string, float|int> */
    private function compute(string $instanceId, Carbon $start, Carbon $end): array
    {
        $pageViews = 0;
        $leads = 0;
        $orders = 0;
        $revenue = 0.0;
        $visitors = [];
        $sessions = [];
        $seenOrders = [];

        $events = RawEventModel::where('instance_id', $instanceId)
            ->where('occurred_at', '>=', $start)
            ->where('occurred_at', '<', $end)
            ->cursor();

        foreach ($events as $event) {
            $name = (string) $event->event_name;
            $payload = $event->payload ?? [];

            if ($event->visitor_id) {
                $visitors[$event->visitor_id] = true;
            }
            if ($event->session_id) {
                $sessions[$event->session_id] = true;
            }

            if ($name === 'page_view') {
                $pageViews++;
            } elseif (in_array($name, KpiSummaryService::LEAD_EVENTS, true)) {
                $leads++;
            } elseif (KpiSummaryService::isOrder($name, $event->event_type, is_array($payload['eventData'] ?? null) ? $payload['eventData'] : $payload)) {
                $orderId = $payload['order_id'] ?? $payload['orderId'] ?? null;
                if ($orderId !== null && isset($seenOrders[$orderId])) {
                    continue;
                }
                if ($orderId !== null) {
                    $seenOrders[$orderId] = true;
                }
                $orders++;
                $revenue += (float) ($payload['amount'] ?? $payload['price'] ?? 0);
            }
        }

        return [
            'page_views' => $pageViews,
            'unique_visitors' => count($visitors),
            'sessions' => count($sessions),
            'orders' => $orders,
            'revenue' => round($revenue, 2),
            'leads' => $leads,
        ];
    }
}
