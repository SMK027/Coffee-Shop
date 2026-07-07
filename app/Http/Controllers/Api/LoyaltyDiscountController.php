<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyDiscount;
use Illuminate\Http\JsonResponse;

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
}
