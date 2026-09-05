<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use App\Services\ActivityLogger;
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

        $currentUserId = (int) (auth()->id() ?? 0);

        $items = Supervisor::with(['superadmin:id,name', 'holderAdmin:id,name'])
            ->where(function ($query) use ($currentUserId) {
                $query->where('superadmin_id', $currentUserId)
                    ->orWhere('holder_admin_id', $currentUserId);
            })
            ->orderBy('supervisor_number')
            ->get()
            ->map(function (Supervisor $s) use ($currentUserId) {
                $relationType = (int) $s->superadmin_id === $currentUserId
                    ? 'responsible'
                    : ((int) $s->holder_admin_id === $currentUserId ? 'holder' : 'visible');

                return [
                    'id' => $s->id,
                    'supervisor_number' => $s->supervisor_number,
                    'is_active' => (bool) $s->is_active,
                    'superadmin_id' => $s->superadmin_id,
                    'superadmin_name' => $s->superadmin?->name,
                    'holder_admin_id' => $s->holder_admin_id,
                    'holder_admin_name' => $s->holderAdmin?->name,
                    'relation_type' => $relationType,
                    'created_at' => $s->created_at?->toDateTimeString(),
                ];
            });

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

        ActivityLogger::log(
            'supervisor.qr_viewed',
            'Consultation du QR code du superviseur #' . $supervisor->supervisor_number . ' (application mobile)',
            'supervisor',
            $supervisor->id,
            ['supervisor_number' => $supervisor->supervisor_number, 'channel' => 'mobile']
        );

        return response()->json([
            'id' => $supervisor->id,
            'supervisor_number' => $supervisor->supervisor_number,
            'superadmin_name' => $supervisor->superadmin?->name,
            'holder_admin_name' => $supervisor->holderAdmin?->name,
            'barcode_value' => $supervisor->barcodeValue(),
        ]);
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
