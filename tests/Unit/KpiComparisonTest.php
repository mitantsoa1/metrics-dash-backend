<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Analytics\Services\KpiComparison;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Garde-fou de non-régression PU-33. */
class KpiComparisonTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'hausse = amélioration' => [120.0, 100.0, false, 20.0, 'improving'],
            'baisse = dégradation' => [80.0, 100.0, false, -20.0, 'degrading'],
            'rebond en baisse = vert' => [30.0, 40.0, true, -25.0, 'improving'],
            'rebond en hausse = rouge' => [50.0, 40.0, true, 25.0, 'degrading'],
            'moins de 1% = neutre' => [100.5, 100.0, false, 0.5, 'neutral'],
            'exactement 1% = pas neutre' => [101.0, 100.0, false, 1.0, 'improving'],
            'arrondi à 1 décimale' => [110.0, 90.0, false, 22.2, 'improving'],
            '0 -> 0 stable' => [0.0, 0.0, false, 0.0, 'neutral'],
            'précédent nul = indisponible' => [10.0, 0.0, false, null, 'unavailable'],
            'précédent absent = indisponible' => [10.0, null, false, null, 'unavailable'],
            'courant absent = indisponible' => [null, 10.0, false, null, 'unavailable'],
        ];
    }

    #[DataProvider('cases')]
    public function test_compare(?float $cur, ?float $prev, bool $lowerIsBetter, ?float $pct, string $trend): void
    {
        $r = KpiComparison::compare($cur, $prev, $lowerIsBetter);

        $this->assertSame($pct, $r['change_pct']);
        $this->assertSame($trend, $r['trend']);
        $this->assertSame($cur, $r['current']);
        $this->assertSame($prev, $r['previous']);
    }
}
