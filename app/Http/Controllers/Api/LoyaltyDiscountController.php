<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyDiscount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyDiscountController extends Controller
{
    /**
     * Liste les réductions de fidélité actives et valides.
     */
    public function index(): JsonResponse
    {
        $discounts = LoyaltyDiscount::where('is_active', true)
            ->latest()
            ->get()
            ->filter(fn(LoyaltyDiscount $d) => $d->isValidForUse())
            ->values()
            ->map(fn(LoyaltyDiscount $d) => [
                'id'                  => $d->id,
                'name'                => $d->name,
                'description'         => $d->description,
                'points_cost'         => (int) $d->points_cost,
                'discount_type'       => $d->discount_type,
                'discount_value'      => (float) $d->discount_value,
                'max_discount_amount' => $d->max_discount_amount ? (float) $d->max_discount_amount : null,
                'employee_only'       => (bool) $d->employee_only,
            ]);

        return response()->json(['discounts' => $discounts]);
    }

    /**
     * Création d'une réduction personnalisée via l'API mobile.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);

        $this->requireSuperAdminOrSupervisor($request, 'Validation du superviseur requise.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'gt:0'],
            'is_active' => ['nullable', 'boolean'],
            'employee_only' => ['nullable', 'boolean'],
            'is_permanent' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'quantity_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validated['discount_type'] === 'percent' && (float) $validated['discount_value'] > 100) {
            return response()->json(['message' => 'Une réduction en pourcentage ne peut pas dépasser 100 %.'], 422);
        }

        if ($validated['discount_type'] === 'fixed') {
            $validated['max_discount_amount'] = null;
        }

        if (! empty($validated['is_permanent'])) {
            $validated['starts_at'] = null;
            $validated['ends_at'] = null;
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['is_sold_out'] = false;
        $validated['employee_only'] = (bool) ($validated['employee_only'] ?? false);

        $discount = \App\Models\LoyaltyDiscount::create($validated);

        return response()->json(['message' => 'Réduction créée', 'id' => $discount->id]);
    }
}
