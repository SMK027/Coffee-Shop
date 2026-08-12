<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SupervisorController extends Controller
{
    /**
     * Liste des superviseurs rattachés au compte connecté.
     */
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $user = auth()->user();
        $isSuperAdmin = (bool) $user?->isSuperAdmin();
        $ownerId = $this->ownerReferenceId();
        $currentUserId = (int) ($user?->id ?? 0);

        $items = Supervisor::query()
            ->when($isSuperAdmin, fn ($query) => $query->where('superadmin_id', $currentUserId))
            ->when(! $isSuperAdmin, function ($query) use ($currentUserId) {
                $query->where('holder_admin_id', $currentUserId)
                    ;
            })
            ->orderBy('supervisor_number')
            ->get()
            ->map(fn (Supervisor $s) => [
                'id' => $s->id,
                'supervisor_number' => $s->supervisor_number,
                'is_active' => (bool) $s->is_active,
                'superadmin_id' => $s->superadmin_id,
                'holder_admin_id' => $s->holder_admin_id,
                'created_at' => $s->created_at?->toDateTimeString(),
            ]);

        return response()->json($items);
    }

    /**
     * Retourne la valeur QR (token bypass) d'un superviseur après vérification de son code PIN.
     */
    public function barcode(Request $request, Supervisor $supervisor): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless($this->canManageSupervisor($supervisor), 403);

        $validated = $request->validate([
            'supervisor_pin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ], [
            'supervisor_pin.required' => 'Le code superviseur est requis.',
            'supervisor_pin.regex' => 'Le code doit contenir entre 4 et 6 chiffres.',
        ]);

        if (! Hash::check($validated['supervisor_pin'], $supervisor->password)) {
            return response()->json([
                'message' => 'Code superviseur incorrect.',
            ], 422);
        }

        return response()->json([
            'id' => $supervisor->id,
            'supervisor_number' => $supervisor->supervisor_number,
            'barcode_value' => $supervisor->barcodeValue(),
        ]);
    }

    private function ownerReferenceId(): int
    {
        $user = auth()->user();

        return (int) ($user?->superadmin_id ?: $user?->id);
    }

    private function canManageSupervisor(Supervisor $supervisor): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return (int) $supervisor->superadmin_id === (int) $user->id;
        }

        $userId = (int) $user->id;
        return (int) $supervisor->holder_admin_id === $userId;
    }
}
