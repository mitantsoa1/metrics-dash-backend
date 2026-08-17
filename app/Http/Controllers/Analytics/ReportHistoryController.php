<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Domains\Analytics\Infrastructure\Models\GeneratedReportModel;
use App\Domains\Analytics\Services\ReportGeneratorService;
use App\Domains\Instance\Infrastructure\Models\InstanceModel;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ReportHistoryController extends Controller
{
    public function __construct(
        private readonly ReportGeneratorService $generatorService
    ) {}

    /**
     * Liste tous les rapports générés avec filtrage par instance ou format.
     */
    public function index(Request $request): JsonResponse
    {
        $instanceId = $request->query('instanceId');
        $format = $request->query('format');

        $query = GeneratedReportModel::with('instance:id,name,domain')
            ->orderBy('created_at', 'desc');

        if ($instanceId && $instanceId !== 'all') {
            $query->where('instance_id', $instanceId);
        }

        if ($format && $format !== 'all') {
            $query->where('format', strtoupper($format));
        }

        $reports = $query->limit(100)->get();

        return response()->json($reports);
    }

    /**
     * Téléchargement / re-téléchargement direct d'un rapport généré.
     */
    public function download(string $id): Response|JsonResponse
    {
        $report = GeneratedReportModel::find($id);
        if (! $report) {
            return response()->json(['message' => 'Rapport introuvable'], 404);
        }

        $extension = strtolower($report->format);
        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };

        $content = base64_decode($report->file_data ?: '');
        $safeName = Str::slug($report->name) . "-{$report->created_at->format('Y-m-d')}." . ($extension === 'excel' ? 'xlsx' : $extension);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => "attachment; filename=\"{$safeName}\"",
            'Content-Length' => strlen($content),
        ]);
    }

    /**
     * Enregistre un nouveau rapport généré dans l'historique (depuis le front ou export).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'format' => 'required|string|in:PDF,EXCEL,CSV,pdf,excel,csv',
            'instance_id' => 'nullable|uuid|exists:instances,id',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'file_data' => 'nullable|string', // Base64 content
            'file_size' => 'nullable|integer',
            'records_count' => 'nullable|integer',
            'kpi_summary' => 'nullable|array',
        ]);

        $user = $request->user();
        $format = strtoupper($validated['format']);
        $fileData = $validated['file_data'] ?? '';

        // Si aucun file_data n'est fourni, générer un CSV automatique
        if (empty($fileData)) {
            $reportData = $this->generatorService->buildReportData(
                instanceId: $validated['instance_id'] ?? null,
                startDateStr: $validated['period_start'] ?? null,
                endDateStr: $validated['period_end'] ?? null,
                reportTitle: $validated['name']
            );
            $csvContent = $this->generatorService->generateCsvReport($reportData);
            $fileData = base64_encode($csvContent);
            $validated['file_size'] = strlen($csvContent);
            $validated['kpi_summary'] = $reportData['kpis'];
        }

        $report = GeneratedReportModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $user?->tenant_id ?? ($validated['instance_id'] ? InstanceModel::find($validated['instance_id'])?->tenant_id : null),
            'instance_id' => $validated['instance_id'] ?? null,
            'name' => $validated['name'],
            'format' => $format,
            'period_start' => $validated['period_start'] ?? null,
            'period_end' => $validated['period_end'] ?? null,
            'file_data' => $fileData,
            'file_size' => $validated['file_size'] ?? strlen(base64_decode($fileData)),
            'records_count' => $validated['records_count'] ?? 0,
            'kpi_summary' => $validated['kpi_summary'] ?? null,
            'created_by' => $user ? $user->email : 'Utilisateur',
        ]);

        return response()->json($report->load('instance:id,name,domain'), 201);
    }

    /**
     * Supprime un rapport de l'historique.
     */
    public function destroy(string $id): JsonResponse
    {
        $report = GeneratedReportModel::find($id);
        if (! $report) {
            return response()->json(['message' => 'Rapport introuvable'], 404);
        }

        $report->delete();

        return response()->json(['message' => 'Rapport supprimé avec succès']);
    }
}
