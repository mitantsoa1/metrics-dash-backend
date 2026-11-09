<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domains\IAM\Enums\UserRole;
use App\Domains\Support\Infrastructure\Models\SupportTicketModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\UpdateSupportTicketRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminSupportTicketController extends Controller
{
    /**
     * Vérifie si l'utilisateur courant possède les privilèges administratifs.
     */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user('sanctum') ?? $request->user();

        if (! $user) {
            abort(401, 'Non authentifié.');
        }

        $role = is_string($user->role) ? $user->role : $user->role?->value;

        if (! in_array($role, [UserRole::SUPERADMIN->value, UserRole::OWNER->value, UserRole::ADMIN->value], true)) {
            abort(403, 'Accès réservé aux administrateurs (SUPERADMIN, OWNER, ADMIN).');
        }
    }

    /**
     * SUPERADMIN : vue plateforme. OWNER/ADMIN : uniquement les tickets de leur tenant.
     */
    private function scopedQuery(Request $request): Builder
    {
        $user = $request->user('sanctum') ?? $request->user();
        $query = SupportTicketModel::query();

        if ($user->role !== UserRole::SUPERADMIN->value) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }

    /**
     * Liste tous les tickets de support avec statistiques, filtres et pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status');
        $priority = $request->query('priority');
        $search = $request->query('search');

        $query = $this->scopedQuery($request)->with([
            'tenant:id,name',
            'user:id,name,email',
        ])->orderBy('created_at', 'desc');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $tickets = $query->paginate(20);

        // Stats globales
        $stats = [
            'total' => $this->scopedQuery($request)->count(),
            'open' => $this->scopedQuery($request)->where('status', 'open')->count(),
            'in_progress' => $this->scopedQuery($request)->where('status', 'in_progress')->count(),
            'resolved' => $this->scopedQuery($request)->where('status', 'resolved')->count(),
            'closed' => $this->scopedQuery($request)->where('status', 'closed')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * Affiche les détails complets d'un ticket.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $ticket = $this->scopedQuery($request)->with(['tenant:id,name', 'user:id,name,email'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    /**
     * Met à jour le statut, la priorité ou les notes internes d'un ticket.
     */
    public function update(UpdateSupportTicketRequest $request, string $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $ticket = $this->scopedQuery($request)->findOrFail($id);
        $ticket->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticket mis à jour avec succès.',
            'data' => $ticket->fresh(['tenant:id,name', 'user:id,name,email']),
        ]);
    }

    /**
     * Supprime (soft-delete) un ticket.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->authorizeAdmin($request);

        $ticket = $this->scopedQuery($request)->findOrFail($id);
        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket supprimé avec succès.',
        ]);
    }
}
