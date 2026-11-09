<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

/**
 * Comparaison d'un KPI avec la période précédente (PU-33).
 *
 * trend :
 *  - improving   : amélioration (hausse, ou baisse pour un KPI "lower is better" comme le rebond)
 *  - degrading   : dégradation
 *  - neutral     : variation < 1 %
 *  - unavailable : donnée précédente absente ou nulle (division par zéro, volume insuffisant)
 */
final class KpiComparison
{
    public const NEUTRAL_THRESHOLD_PCT = 1.0;

    /**
     * @return array{current: ?float, previous: ?float, change_pct: ?float, trend: string}
     */
    public static function compare(?float $current, ?float $previous, bool $lowerIsBetter = false): array
    {
        $result = ['current' => $current, 'previous' => $previous, 'change_pct' => null, 'trend' => 'unavailable'];

        if ($current === null || $previous === null) {
            return $result;
        }

        if ($previous == 0.0) {
            // 0 -> 0 : stable ; 0 -> x : variation indéfinie, jamais un % inventé
            if ($current == 0.0) {
                $result['change_pct'] = 0.0;
                $result['trend'] = 'neutral';
            }

            return $result;
        }

        $pct = round((($current - $previous) / abs($previous)) * 100, 1);
        $result['change_pct'] = $pct;

        if (abs($pct) < self::NEUTRAL_THRESHOLD_PCT) {
            $result['trend'] = 'neutral';
        } else {
            $improved = $lowerIsBetter ? $pct < 0 : $pct > 0;
            $result['trend'] = $improved ? 'improving' : 'degrading';
        }

        return $result;
    }
}
