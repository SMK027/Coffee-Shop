<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyDiscount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LoyaltyDiscountController extends Controller
{
    public function index()
    {
        $discounts = LoyaltyDiscount::orderByDesc('is_active')->latest()->paginate(20);

        return view('employee.loyalty-discounts.index', compact('discounts'));
    }

    public function create()
    {
        return view('employee.loyalty-discounts.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);
        LoyaltyDiscount::create($data);

        return redirect()->route('employee.loyalty-discounts.index')
            ->with('success', 'Reduction creee avec succes.');
    }

    public function edit(LoyaltyDiscount $loyaltyDiscount)
    {
        return view('employee.loyalty-discounts.edit', compact('loyaltyDiscount'));
    }

    public function update(Request $request, LoyaltyDiscount $loyaltyDiscount)
    {
        $data = $this->validatePayload($request);
        $loyaltyDiscount->update($data);

        return redirect()->route('employee.loyalty-discounts.index')
            ->with('success', 'Reduction mise a jour.');
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'points_cost' => ['required', 'integer', 'min:1'],
            'discount_type' => ['required', Rule::in([LoyaltyDiscount::TYPE_FIXED, LoyaltyDiscount::TYPE_PERCENT])],
            'discount_value' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_sold_out' => ['nullable', 'boolean'],
            'employee_only' => ['nullable', 'boolean'],
            'is_permanent' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'quantity_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_sold_out'] = $request->boolean('is_sold_out');
        $validated['employee_only'] = $request->boolean('employee_only');
        $validated['is_permanent'] = $request->boolean('is_permanent');

        if ($validated['discount_type'] === LoyaltyDiscount::TYPE_PERCENT && (float) $validated['discount_value'] > 100) {
            throw ValidationException::withMessages([
                'discount_value' => 'Une reduction en pourcentage ne peut pas depasser 100%.',
            ]);
        }

        if ($validated['is_permanent']) {
            $validated['starts_at'] = null;
            $validated['ends_at'] = null;
        }

        return $validated;
    }
}
