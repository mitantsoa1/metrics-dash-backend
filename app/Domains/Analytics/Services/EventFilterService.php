<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use App\Domains\Ingestion\Infrastructure\Models\RawEventModel;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filtres de segmentation du dashboard (PU-37 catégorie, PU-38 région, PU-39 appareil).
 * Catégorie et région viennent du payload des événements ; l'appareil du contexte (SDK) ou du payload (API Push).
 */
class EventFilterService
{
    private const MAX_VALUES = 50;
    private const MAX_OPTIONS = 200;

    /** @return array{category: string[], region: string[], device: string[]} */
    public function fromRequest(Request $request): array
    {
        return [
            'category' => $this->list($request->query('category')),
            'region' => $this->list($request->query('region')),
            'device' => array_map('strtoupper', $this->list($request->query('device'))),
        ];
    }

    /** @param array{category: string[], region: string[], device: string[]} $filters */
    public function isActive(array $filters): bool
    {
        return $filters['category'] !== [] || $filters['region'] !== [] || $filters['device'] !== [];
    }

    /** @param array{category: string[], region: string[], device: string[]} $filters */
    public function apply(Builder $query, array $filters): Builder
    {
        if ($filters['category'] !== []) {
            $query->whereIn('payload->category', $filters['category']);
        }
        if ($filters['region'] !== []) {
            $query->whereIn('payload->region', $filters['region']);
        }
        if ($filters['device'] !== []) {
            $query->where(function (Builder $q) use ($filters) {
                $q->whereIn('context->device_type', $filters['device'])
                    ->orWhereIn('payload->device', $filters['device']);
            });
        }

        return $query;
    }

    /**
     * Valeurs réellement présentes sur la période (les valeurs sans données sont absentes).
     *
     * @return array{categories: array, regions: array, devices: array}
     */
    public function options(Closure $instanceFilter, Carbon $start, Carbon $end): array
    {
        $categories = $regions = $devices = [];

        $events = RawEventModel::where($instanceFilter)
            ->whereBetween('occurred_at', [$start, $end])
            ->cursor();

        foreach ($events as $event) {
            $this->tally($categories, $event->payload['category'] ?? null);
            $this->tally($regions, $event->payload['region'] ?? null);
            $this->tally($devices, isset($event->context['device_type']) ? strtoupper((string) $event->context['device_type']) : ($event->payload['device'] ?? null));
        }

        return [
            'categories' => $this->format($categories),
            'regions' => $this->format($regions),
            'devices' => $this->format($devices),
        ];
    }

    /** @return string[] */
    private function list(mixed $raw): array
    {
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        $clean = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '' && mb_strlen($value) <= 100) {
                $clean[$value] = true;
            }
        }

        return array_slice(array_keys($clean), 0, self::MAX_VALUES);
    }

    private function tally(array &$bucket, mixed $value): void
    {
        if (is_string($value) && trim($value) !== '') {
            $value = trim($value);
            $bucket[$value] = ($bucket[$value] ?? 0) + 1;
        }
    }

    private function format(array $bucket): array
    {
        arsort($bucket);

        $out = [];
        foreach (array_slice($bucket, 0, self::MAX_OPTIONS, true) as $value => $count) {
            $out[] = ['value' => (string) $value, 'count' => $count];
        }

        return $out;
    }
}
