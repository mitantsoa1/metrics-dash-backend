<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Domains\Analytics\Services\EventFilterService;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use App\Http\Controllers\Controller;
use App\Support\AuthorizesTenantAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Valeurs de filtre (catégories, régions, appareils) réellement présentes sur la période. */
class FilterOptionsController extends Controller
{
    use AuthorizesTenantAccess;

    public function __invoke(Request $request, EventFilterService $filters): JsonResponse
    {
        $instanceId = $request->query('instanceId');
        $isAll = ! $instanceId || $instanceId === 'all';

        if (! $isAll) {
            $this->assertInstanceAccessible((string) $instanceId);
        }

        $start = Carbon::parse($request->query('startDate', now()->subDays(30)->toDateString()))->startOfDay();
        $end = Carbon::parse($request->query('endDate', now()->toDateString()))->endOfDay();

        $scope = function ($query) use ($isAll, $instanceId) {
            if (! $isAll) {
                $query->where('instance_id', $instanceId);
            } elseif (! $this->isGlobalUser()) {
                $query->whereIn('instance_id', InstanceModel::where('tenant_id', request()->user()?->tenant_id)->pluck('id'));
            }
        };

        return response()->json($filters->options($scope, $start, $end));
    }
}
