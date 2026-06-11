<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
        $statusLabels = Order::STATUS_LABELS;

        return view('employee.orders.index', compact('orders', 'statusLabels'));
    }

    public function show(Order $order)
    {
        $order->load('items.drink', 'handler', 'loyaltyCard', 'loyaltyDiscounts');
        $statusLabels = Order::STATUS_LABELS;

        return view('employee.orders.show', compact('order', 'statusLabels'));
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
            'customer_name'          => [Rule::requiredIf(!$useLoyalty), 'nullable', 'string', 'max:100'],
            'loyalty_card_number'    => [Rule::requiredIf($useLoyalty), 'nullable', 'string', 'max:20'],
            'loyalty_discount_ids'   => ['nullable', 'array'],
            'loyalty_discount_ids.*' => ['integer', 'exists:loyalty_discounts,id'],
            'card_pin'               => ['nullable', 'string', 'max:10'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.drink_id'       => ['required', 'integer', 'exists:drinks,id'],
            'items.*.quantity'       => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'customer_name.required'       => 'Le nom du client est requis (ou passez une carte de fidélité).',
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

        // Filtre les lignes sans boisson sélectionnée
        $rawItems = collect($validated['items'])->filter(fn ($item) => !empty($item['drink_id']));

        if ($rawItems->isEmpty()) {
            return back()->withInput()->withErrors(['items' => 'Veuillez sélectionner au moins une boisson.']);
        }

        $subtotal   = 0;
        $orderItems = [];

        foreach ($rawItems as $item) {
            $drink        = Drink::findOrFail($item['drink_id']);
            $subtotal    += $drink->price * $item['quantity'];
            $orderItems[] = [
                'drink_id'   => $drink->id,
                'quantity'   => (int) $item['quantity'],
                'unit_price' => $drink->price,
            ];
        }

        $employeeDiscount      = $isEmployeeOrder ? round($subtotal * Order::EMPLOYEE_DISCOUNT_RATE, 2) : 0;
        $subtotalAfterEmployee = round($subtotal - $employeeDiscount, 2);

        // Calcul des réductions fidélité (application séquentielle sur le solde restant)
        $discountRows       = [];
        $totalLoyaltyPoints = 0;
        $totalLoyaltyAmount = 0;
        $remaining          = $subtotalAfterEmployee;

        foreach ($loyaltyDiscounts as $discount) {
            if ($discount->discount_type === LoyaltyDiscount::TYPE_PERCENT) {
                $amount = round($remaining * ((float) $discount->discount_value / 100), 2);
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

        $totalLoyaltyAmount = round($totalLoyaltyAmount, 2);
        $total              = round(max(0.0, $subtotalAfterEmployee - $totalLoyaltyAmount), 2);

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
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => "La réduction « {$locked->name ?? '?'} » n'est plus disponible.",
                    ]);
                }

                if ($locked->employee_only && (!$lockedCard || !$lockedCard->hasEmployeeBenefits())) {
                    throw ValidationException::withMessages([
                        'loyalty_discount_ids' => "La réduction « {$locked->name} » est réservée aux salariés.",
                    ]);
                }

                $row = collect($discountRows)->firstWhere('loyalty_discount_id', $locked->id);
                $pivotRows[] = [
                    'loyalty_discount_id' => $locked->id,
                    'points_spent'        => $locked->points_cost,
                    'discount_amount'     => $row['discount_amount'],
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
                'customer_name'           => $lockedCard ? $lockedCard->full_name : $validated['customer_name'],
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

            return $order;
        });

        return redirect()->route('employee.orders.show', $order)
            ->with('success', 'Commande créée avec succès.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,preparing,serving,completed,cancelled'],
        ]);

        $data = ['status' => $validated['status'], 'handled_by' => auth()->id()];

        if ($validated['status'] === Order::STATUS_COMPLETED) {
            $data['completed_at'] = now();
        }

        $order->update($data);

        // Crédite automatiquement les points de fidélité une fois la commande terminée
        if ($validated['status'] === Order::STATUS_COMPLETED) {
            $order->refresh()->creditLoyaltyPoints();
        }

        return redirect()->back()->with('success', 'Statut de la commande mis à jour.');
    }
}
