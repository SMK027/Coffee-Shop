<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Drink;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyDiscount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items.drink', 'handler')->latest();
        $search = trim((string) $request->query('q', ''));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($search !== '') {
            $query->where(function ($filter) use ($search) {
                $filter->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('items.drink', function ($drinkQuery) use ($search) {
                        $drinkQuery->where('name', 'like', "%{$search}%");
                    });

                if (ctype_digit($search)) {
                    $filter->orWhere('id', (int) $search);
                }
            });
        }

        $orders = $query->paginate(20);
        $allStatuses  = OrderStatus::orderBy('sort_order')->get();
        $statusLabels = $allStatuses->pluck('label', 'key')->all();

        return view('employee.orders.index', compact('orders', 'statusLabels', 'allStatuses'));
    }

    public function show(Order $order)
    {
        $order->load('items.drink', 'handler', 'loyaltyCard', 'loyaltyDiscounts');

        $allStatuses  = OrderStatus::orderBy('sort_order')->get();
        $statusLabels = $allStatuses->pluck('label', 'key')->all();

        // Calcule les transitions disponibles depuis le statut courant
        $currentStatus = $allStatuses->firstWhere('key', $order->status);
        $availableTransitions = collect();
        if ($currentStatus && !$currentStatus->is_terminal) {
            $availableTransitions = $allStatuses->filter(
                fn (OrderStatus $s) => $s->is_active
                    && $s->key !== $order->status
                    && (
                        $s->is_terminal
                        || $s->sort_order > $currentStatus->sort_order
                    )
            )->values();
        }

        return view('employee.orders.show', compact('order', 'statusLabels', 'availableTransitions'));
    }

    public function create()
    {
        $drinks = Drink::available()->with('category')->orderBy('category_id')->orderBy('sort_order')->get();
        $discounts = LoyaltyDiscount::where('is_active', true)
            ->latest()
            ->get()
            ->filter(fn (LoyaltyDiscount $discount) => $discount->isValidForUse())
            ->values();

        return view('employee.orders.create', compact('drinks', 'discounts'));
    }

    public function checkLoyaltyCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:20'],
        ]);

        $cardNumber = str_replace(' ', '', $validated['card_number']);
        $card = LoyaltyCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return response()->json([
                'found' => false,
                'message' => 'Aucune carte ne correspond à ce numéro.',
            ]);
        }

        return response()->json([
            'found' => true,
            'card' => [
                'full_name' => $card->full_name,
                'points' => (int) $card->points,
                'has_employee_benefits' => (bool) $card->hasEmployeeBenefits(),
            ],
        ]);
    }

    public function verifyCardPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:20'],
            'pin'         => ['required', 'string', 'max:10'],
        ]);

        $cardNumber = str_replace(' ', '', $validated['card_number']);
        $card = LoyaltyCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return response()->json(['valid' => false, 'message' => 'Carte introuvable.']);
        }

        if (!Hash::check($validated['pin'], $card->pin)) {
            return response()->json(['valid' => false, 'message' => 'Code incorrect.']);
        }

        return response()->json(['valid' => true]);
    }

    public function store(Request $request)
    {
        $useLoyalty      = $request->boolean('use_loyalty');
        $isEmployeeOrder = $request->boolean('is_employee_order');

        $validated = $request->validate([
            'use_loyalty'            => ['nullable', 'boolean'],
            'is_employee_order'      => ['nullable', 'boolean'],
            'customer_name'          => ['nullable', 'string', 'max:100'],
            'loyalty_card_number'    => [Rule::requiredIf($useLoyalty), 'nullable', 'string', 'max:20'],
            'loyalty_discount_ids'   => ['nullable', 'array'],
            'loyalty_discount_ids.*' => ['integer', 'exists:loyalty_discounts,id'],
            'card_pin'               => ['nullable', 'string', 'max:10'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.drink_id'       => ['nullable', 'integer', 'exists:drinks,id'],
            'items.*.custom_label'   => ['nullable', 'string', 'max:150'],
            'items.*.custom_price'   => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'items.*.quantity'       => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'loyalty_card_number.required' => 'Le numéro de carte de fidélité est requis.',
        ]);

        // Rattachement éventuel à une carte de fidélité
        $loyaltyCard = null;
        if ($useLoyalty) {
            $cardNumber  = str_replace(' ', '', $validated['loyalty_card_number']);
            $loyaltyCard = LoyaltyCard::where('card_number', $cardNumber)->first();

            if (!$loyaltyCard) {
                throw ValidationException::withMessages([
                    'loyalty_card_number' => 'Aucune carte de fidélité ne correspond à ce numéro.',
                ]);
            }

            if ($loyaltyCard->hasEmployeeBenefits()) {
                $isEmployeeOrder = true;
            }
        }

        // Sélection de réductions fidélité (plusieurs possibles)
        $discountIds      = array_values(array_filter((array) ($validated['loyalty_discount_ids'] ?? [])));
        $loyaltyDiscounts = collect();

        if (!empty($discountIds)) {
            if (!$useLoyalty || !$loyaltyCard) {
                throw ValidationException::withMessages([
                    'loyalty_discount_ids' => 'Les réductions fidélité nécessitent une carte valide.',
                ]);
            }

            // Vérification du code de la carte
            $pin = $validated['card_pin'] ?? '';
            if (!$pin || !Hash::check($pin, $loyaltyCard->pin)) {
                throw ValidationException::withMessages([
                    'card_pin' => 'Code de carte incorrect ou manquant.',
                ]);
            }

            $loyaltyDiscounts = LoyaltyDiscount::whereIn('id', $discountIds)->get();

            $totalPointsCost = 0;
            foreach ($loyaltyDiscounts as $discount) {
                if (!$discount->isValidForUse()) {
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => "La réduction « {$discount->name} » n'est plus valide.",
                    ]);
                }
                if ($discount->employee_only && !$loyaltyCard->hasEmployeeBenefits()) {
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => "La réduction « {$discount->name} » est réservée aux salariés.",
                    ]);
                }
                $totalPointsCost += $discount->points_cost;
            }

            if ($loyaltyCard->points < $totalPointsCost) {
                throw ValidationException::withMessages([
                    'loyalty_discount_ids' => 'Points insuffisants pour appliquer toutes les réductions sélectionnées.',
                ]);
            }
        }

        // Filtre les lignes sans boisson ni article libre valide
        $rawItems = collect($validated['items'])->filter(function ($item) {
            $hasDrink  = !empty($item['drink_id']);
            $hasCustom = !empty($item['custom_label']) && isset($item['custom_price']) && (float) $item['custom_price'] > 0;
            return $hasDrink || $hasCustom;
        });

        if ($rawItems->isEmpty()) {
            return back()->withInput()->withErrors(['items' => 'Veuillez sélectionner au moins une boisson ou saisir un article libre.']);
        }

        $subtotal   = 0;
        $orderItems = [];

        foreach ($rawItems as $item) {
            if (!empty($item['drink_id'])) {
                $drink        = Drink::findOrFail($item['drink_id']);
                $price        = (float) $drink->price;
                $orderItems[] = [
                    'drink_id'   => $drink->id,
                    'quantity'   => (int) $item['quantity'],
                    'unit_price' => $price,
                ];
            } else {
                $price        = round((float) $item['custom_price'], 2);
                $orderItems[] = [
                    'drink_id'     => null,
                    'custom_label' => trim($item['custom_label']),
                    'custom_price' => $price,
                    'quantity'     => (int) $item['quantity'],
                    'unit_price'   => $price,
                ];
            }
            $subtotal += $price * (int) $item['quantity'];
        }

        // 1. Réductions fidélité appliquées en premier sur le prix brut.
        $discountRows       = [];
        $totalLoyaltyPoints = 0;
        $totalLoyaltyAmount = 0;
        $remaining          = $subtotal;

        foreach ($loyaltyDiscounts as $discount) {
            if ($discount->discount_type === LoyaltyDiscount::TYPE_PERCENT) {
                $amount = round($remaining * ((float) $discount->discount_value / 100), 2);
                if ($discount->max_discount_amount !== null) {
                    $amount = min($amount, round((float) $discount->max_discount_amount, 2));
                }
            } else {
                $amount = round(min($remaining, (float) $discount->discount_value), 2);
            }
            $remaining = max(0.0, $remaining - $amount);
            $discountRows[] = [
                'loyalty_discount_id' => $discount->id,
                'points_spent'        => $discount->points_cost,
                'discount_amount'     => $amount,
            ];
            $totalLoyaltyPoints += $discount->points_cost;
            $totalLoyaltyAmount += $amount;
        }

        $totalLoyaltyAmount    = round($totalLoyaltyAmount, 2);
        $subtotalAfterLoyalty  = round(max(0.0, $subtotal - $totalLoyaltyAmount), 2);

        // 2. Réduction salarié appliquée sur le solde après réductions fidélité.
        $employeeDiscount = $isEmployeeOrder ? round($subtotalAfterLoyalty * Order::EMPLOYEE_DISCOUNT_RATE, 2) : 0;
        $total            = round(max(0.0, $subtotalAfterLoyalty - $employeeDiscount), 2);

        $order = DB::transaction(function () use (
            $validated, $loyaltyCard, $isEmployeeOrder, $total,
            $employeeDiscount, $loyaltyDiscounts, $discountRows,
            $totalLoyaltyPoints, $totalLoyaltyAmount, $orderItems
        ) {
            $lockedCard = null;
            if ($loyaltyCard) {
                $lockedCard = LoyaltyCard::whereKey($loyaltyCard->id)->lockForUpdate()->first();
            }

            $pivotRows         = [];
            $totalPointsNeeded = 0;

            foreach ($loyaltyDiscounts as $discount) {
                $locked = LoyaltyDiscount::whereKey($discount->id)->lockForUpdate()->first();

                if (!$locked || !$locked->isValidForUse()) {
                    $discountName = $locked ? $locked->name : '?';
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => "La réduction « {$discountName} » n'est plus disponible.",
                    ]);
                }

                if ($locked->employee_only && (!$lockedCard || !$lockedCard->hasEmployeeBenefits())) {
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => "La réduction « {$locked->name} » est réservée aux salariés.",
                    ]);
                }

                /* Recalcule le montant depuis locked (cohérence en cas de concurrence) */
                $discountRow    = collect($discountRows)->firstWhere('loyalty_discount_id', $locked->id);
                $computedAmount = $discountRow ? (float) $discountRow['discount_amount'] : 0.0;
                // Réapplique le plafond éventuel
                if ($locked->discount_type === LoyaltyDiscount::TYPE_PERCENT && $locked->max_discount_amount !== null) {
                    $computedAmount = min($computedAmount, round((float) $locked->max_discount_amount, 2));
                }
                $pivotRows[] = [
                    'loyalty_discount_id' => $locked->id,
                    'points_spent'        => $locked->points_cost,
                    'discount_amount'     => $computedAmount,
                ];
                $totalPointsNeeded += $locked->points_cost;
                $locked->increment('quantity_used');
            }

            if ($lockedCard && $totalPointsNeeded > 0) {
                if ($lockedCard->points < $totalPointsNeeded) {
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => 'Points insuffisants au moment de la validation.',
                    ]);
                }
                $lockedCard->decrement('points', $totalPointsNeeded);
            }

            $order = Order::create([
                'customer_name'           => $lockedCard ? $lockedCard->full_name : ($validated['customer_name'] ?? null),
                'loyalty_card_id'         => $lockedCard?->id,
                'is_employee_order'       => $isEmployeeOrder,
                'notes'                   => $validated['notes'] ?? null,
                'total_amount'            => $total,
                'discount_amount'         => $employeeDiscount,
                'loyalty_points_spent'    => $totalPointsNeeded,
                'loyalty_discount_amount' => $totalLoyaltyAmount,
                'status'                  => Order::STATUS_PENDING,
                'handled_by'              => auth()->id(),
            ]);

            $order->items()->createMany($orderItems);

            foreach ($pivotRows as $row) {
                $order->loyaltyDiscounts()->attach($row['loyalty_discount_id'], [
                    'points_spent'    => $row['points_spent'],
                    'discount_amount' => $row['discount_amount'],
                ]);
            }

            // Traçabilité : enregistre le débit de points dans l'historique de la carte.
            if ($lockedCard && $totalPointsNeeded > 0) {
                $balanceAfter = $lockedCard->fresh()->points;
                $discountNames = collect($pivotRows)->map(function ($row) {
                    $d = LoyaltyDiscount::find($row['loyalty_discount_id']);
                    return $d ? $d->name . ' (' . $row['points_spent'] . ' pts)' : '?';
                })->implode(', ');
                \App\Models\LoyaltyPointAdjustment::create([
                    'loyalty_card_id' => $lockedCard->id,
                    'order_id'        => $order->id,
                    'user_id'         => auth()->id(),
                    'type'            => \App\Models\LoyaltyPointAdjustment::TYPE_DEBIT,
                    'source'          => \App\Models\LoyaltyPointAdjustment::SOURCE_ORDER_DEBIT,
                    'points'          => $totalPointsNeeded,
                    'balance_after'   => $balanceAfter,
                    'reason'          => "Réduction(s) appliquée(s) — commande #{$order->id} : {$discountNames}",
                ]);
            }

            return $order;
        });

        return redirect()->route('employee.orders.show', $order)
            ->with('success', 'Commande créée avec succès.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validKeys = OrderStatus::where('is_active', true)->pluck('key')->all();

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in($validKeys)],
        ]);

        $newStatus = OrderStatus::where('key', $validated['status'])->first();
        $data = ['status' => $validated['status'], 'handled_by' => auth()->id()];

        if ($newStatus?->triggers_loyalty_credit) {
            $data['completed_at'] = now();
        }

        $order->update($data);

        // Crédite automatiquement les points de fidélité si le statut le demande
        if ($newStatus?->triggers_loyalty_credit) {
            $order->refresh()->creditLoyaltyPoints();
        }

        return redirect()->back()->with('success', 'Statut de la commande mis à jour.');
    }
}
