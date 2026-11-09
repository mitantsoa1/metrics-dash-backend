<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Domains\Analytics\Infrastructure\Models\ScheduledReportModel;
use App\Domains\Analytics\Services\ScheduledReportRunnerService;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\AuthorizesTenantAccess;

class ScheduledReportController extends Controller
{
    use AuthorizesTenantAccess;

    public function __construct(
        private readonly ScheduledReportRunnerService $runnerService
    ) {}

    /**
     * Liste toutes les planifications de rapports avec logs récents.
     */
    public function index(Request $request): JsonResponse
    {
        $instanceId = $request->query('instanceId');

        $query = $this->scopeToTenant(ScheduledReportModel::query())->with(['instance:id,name,domain', 'logs' => function ($q) {
            $q->latest('executed_at')->limit(5);
        }])->orderBy('created_at', 'desc');

        if ($instanceId && $instanceId !== 'all') {
            $query->where('instance_id', $instanceId);
        }

        $schedules = $query->get();

        return response()->json($schedules);
    }

    /**
     * Crée une nouvelle planification de rapport automatique.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'instance_id' => 'nullable|uuid|exists:instances,id',
            'frequency' => 'required|string|in:DAILY,WEEKLY,MONTHLY,daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|between:1,7',
            'day_of_month' => 'nullable|integer|between:1,31',
            'time' => 'required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'timezone' => 'nullable|string|max:64',
            'formats' => 'required|string|in:PDF,EXCEL,BOTH,pdf,excel,both',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|email',
            'included_kpis' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $this->requireManager();
        $this->assertInstanceAccessible($validated['instance_id'] ?? null);

        $user = $request->user();
        $id = (string) Str::uuid();

        $schedule = new ScheduledReportModel();
        $schedule->id = $id;
        $schedule->tenant_id = $user?->tenant_id ?? ($validated['instance_id'] ? InstanceModel::find($validated['instance_id'])?->tenant_id : null);
        $schedule->instance_id = $validated['instance_id'] ?? null;
        $schedule->name = $validated['name'];
        $schedule->frequency = strtoupper($validated['frequency']);
        $schedule->day_of_week = $validated['day_of_week'] ?? 1;
        $schedule->day_of_month = $validated['day_of_month'] ?? 1;
        $schedule->time = $validated['time'];
        $schedule->timezone = $validated['timezone'] ?? 'Europe/Paris';
        $schedule->formats = strtoupper($validated['formats']);
        $schedule->recipients = array_values(array_unique($validated['recipients']));
        $schedule->included_kpis = $validated['included_kpis'] ?? ['audience', 'financials', 'top_pages'];
        $schedule->is_active = $validated['is_active'] ?? true;
        $schedule->created_by = $user ? $user->email : 'Admin';
        $schedule->next_run_at = $schedule->calculateNextRunAt(Carbon::now());
        $schedule->save();

        return response()->json($schedule->load('instance:id,name,domain'), 201);
    }

    /**
     * Affiche une planification spécifique.
     */
    public function show(string $id): JsonResponse
    {
        $schedule = $this->scopeToTenant(ScheduledReportModel::query())->with(['instance:id,name,domain', 'logs'])->find($id);
        if (! $schedule) {
            return response()->json(['message' => 'Planification introuvable'], 404);
        }

        return response()->json($schedule);
    }

    /**
     * Met à jour une planification existante.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireManager();
        $schedule = $this->scopeToTenant(ScheduledReportModel::query())->find($id);
        if (! $schedule) {
            return response()->json(['message' => 'Planification introuvable'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'instance_id' => 'nullable|uuid|exists:instances,id',
            'frequency' => 'sometimes|required|string|in:DAILY,WEEKLY,MONTHLY,daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|between:1,7',
            'day_of_month' => 'nullable|integer|between:1,31',
            'time' => 'sometimes|required|string|regex:/^[0-2][0-9]:[0-5][0-9]$/',
            'timezone' => 'nullable|string|max:64',
            'formats' => 'sometimes|required|string|in:PDF,EXCEL,BOTH,pdf,excel,both',
            'recipients' => 'sometimes|required|array|min:1',
            'recipients.*' => 'required|email',
            'included_kpis' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['frequency'])) $schedule->frequency = strtoupper($validated['frequency']);
        if (isset($validated['name'])) $schedule->name = $validated['name'];
        if (array_key_exists('instance_id', $validated)) {
            $this->assertInstanceAccessible($validated['instance_id']);
        }
        if (array_key_exists('instance_id', $validated)) $schedule->instance_id = $validated['instance_id'];
        if (isset($validated['day_of_week'])) $schedule->day_of_week = $validated['day_of_week'];
        if (isset($validated['day_of_month'])) $schedule->day_of_month = $validated['day_of_month'];
        if (isset($validated['time'])) $schedule->time = $validated['time'];
        if (isset($validated['timezone'])) $schedule->timezone = $validated['timezone'];
        if (isset($validated['formats'])) $schedule->formats = strtoupper($validated['formats']);
        if (isset($validated['recipients'])) $schedule->recipients = array_values(array_unique($validated['recipients']));
        if (isset($validated['included_kpis'])) $schedule->included_kpis = $validated['included_kpis'];
        if (isset($validated['is_active'])) $schedule->is_active = $validated['is_active'];

        $schedule->next_run_at = $schedule->calculateNextRunAt(Carbon::now());
        $schedule->save();

        return response()->json($schedule->load('instance:id,name,domain'));
    }

    /**
     * Supprime une planification.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->requireManager();
        $schedule = $this->scopeToTenant(ScheduledReportModel::query())->find($id);
        if (! $schedule) {
            return response()->json(['message' => 'Planification introuvable'], 404);
        }

        $schedule->delete();

        return response()->json(['message' => 'Planification supprimée avec succès']);
    }

    /**
     * Déclenche un envoi d'email de test immédiat.
     */
    public function testSend(Request $request, string $id): JsonResponse
    {
        $this->requireManager();
        $schedule = $this->scopeToTenant(ScheduledReportModel::query())->find($id);
        if (! $schedule) {
            return response()->json(['message' => 'Planification introuvable'], 404);
        }

        $testRecipient = $request->input('recipient'); // Optionnel (sinon prend tous les destinataires)

        try {
            $result = $this->runnerService->sendTestReport($schedule, $testRecipient);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "Échec de l'envoi de test: " . $e->getMessage(),
            ], 500);
        }
    }
}
