<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Domains\Support\Infrastructure\Models\SupportTicketModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    /**
     * Crée et enregistre une nouvelle demande d'assistance / ticket de support.
     */
    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user('sanctum') ?? $request->user();

        $ticket = SupportTicketModel::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $user?->tenant_id ?? null,
            'user_id' => $user?->id ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'open',
            'priority' => $validated['priority'] ?? 'normal',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'assistance enregistrée avec succès.',
            'data' => $ticket,
        ], 201);
    }
}
