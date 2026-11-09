<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Les 6 KPIs principaux (PU-32) avec comparaison à la période précédente de même durée (PU-33).
 * Une valeur absente (null) signifie "pas de donnée" : jamais de valeur de remplacement inventée.
 */
class KpiSummaryService
{
    public const ORDER_EVENTS = ['order_completed', 'payment_success', 'subscription_created', 'checkout_success'];
    public const LEAD_EVENTS = ['lead_captured', 'lead_created', 'lead_submitted', 'lead'];

    public function __construct(private readonly EventFilterService $filterService) {}

    /**
     * @param  array{category: string[], region: string[], device: string[]}  $filters
     * @return array<string, mixed>
     */
    public function build(Closure $instanceFilter, Carbon $start, Carbon $end, array $filters): array
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $days = (int) $start->diffInDays($end->copy()->startOfDay()) + 1;
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->startOfDay()->subDays($days - 1);

        $cur = $this->aggregate($instanceFilter, $start, $end, $filters);
        $prev = $this->aggregate($instanceFilter, $prevStart, $prevEnd, $filters);

        $kpis = [
            'total_revenue' => ['unit' => 'EUR', 'lower_is_better' => false],
            'conversion_rate' => ['unit' => '%', 'lower_is_better' => false],
            'page_views' => ['unit' => 'count', 'lower_is_better' => false],
            'bounce_rate' => ['unit' => '%', 'lower_is_better' => true],
            'engagement' => ['unit' => 's', 'lower_is_better' => false],
            'leads' => ['unit' => 'count', 'lower_is_better' => false],
        ];

        $out = [];
        foreach ($kpis as $key => $meta) {
            $out[$key] = KpiComparison::compare($cur[$key], $prev[$key], $meta['lower_is_better'])
                + ['unit' => $meta['unit']];
        }

        return [
            'period' => ['start' => $start->toDateString(), 'end' => $end->toDateString(), 'days' => $days],
            'previous_period' => ['start' => $prevStart->toDateString(), 'end' => $prevEnd->toDateString()],
            'kpis' => $out,
        ];
    }

    /** @return array<string, float|int|null> */
    private function aggregate(Closure $instanceFilter, Carbon $start, Carbon $end, array $filters): array
    {
        $query = RawEventModel::where($instanceFilter)->whereBetween('occurred_at', [$start, $end]);
        $this->filterService->apply($query, $filters);

        $revenue = 0.0;
        $orders = 0;
        $seenOrders = [];
        $visitors = [];
        $pageViews = 0;
        $leads = 0;
        $durations = 0;
        $bounces = 0;
        $activeSeconds = 0;

        foreach ($query->cursor() as $event) {
            $name = (string) $event->event_name;
            $payload = $event->payload ?? [];
            $payload = is_array($payload['eventData'] ?? null) ? $payload['eventData'] : $payload;

            if ($event->visitor_id) {
                $visitors[$event->visitor_id] = true;
            }

            if ($name === 'page_view') {
                $pageViews++;
            } elseif ($name === 'page_duration') {
                $durations++;
                $bounces += ! empty($payload['is_bounce']) ? 1 : 0;
                $activeSeconds += (int) ($payload['active_duration'] ?? $payload['page_duration'] ?? 0);
            } elseif (in_array($name, self::LEAD_EVENTS, true)) {
                $leads++;
            } elseif ($event->event_type === 'BUSINESS' || in_array($name, self::ORDER_EVENTS, true)) {
                // Une commande rejouée (même order_id) n'est comptée qu'une fois.
                $orderId = $payload['order_id'] ?? $payload['orderId'] ?? null;
                if ($orderId !== null) {
                    if (isset($seenOrders[$orderId])) {
                        continue;
                    }
                    $seenOrders[$orderId] = true;
                }
                $orders++;
                $revenue += (float) ($payload['amount'] ?? $payload['price'] ?? 0);
            }
        }

        return [
            'total_revenue' => round($revenue, 2),
            'conversion_rate' => count($visitors) > 0 ? round($orders / count($visitors) * 100, 2) : null,
            'page_views' => $pageViews,
            'bounce_rate' => $durations > 0 ? round($bounces / $durations * 100, 1) : null,
            'engagement' => $durations > 0 ? (int) round($activeSeconds / $durations) : null,
            'leads' => $leads,
        ];
    }
}
