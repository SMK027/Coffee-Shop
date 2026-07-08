<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drink;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyDiscount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Liste les commandes avec filtres et pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items.drink', 'handler', 'loyaltyCard')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('active')) {
            $query->active();
        }

        if ($request->has('employee') && $request->input('employee') !== '') {
            $query->where('is_employee_order', $request->boolean('employee'));
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($filter) use ($search) {
                $filter->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
                if (ctype_digit($search)) {
                    $filter->orWhere('id', (int) $search);
                }
            });
        }

        $orders = $query->paginate(20);

        return response()->json([
            'data'         => $orders->map(fn(Order $o) => $this->formatOrder($o)),
            'current_page' => $orders->currentPage(),
            'last_page'    => $orders->lastPage(),
            'total'        => $orders->total(),
        ]);
    }

    /**
     * Détail d'une commande.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load('items.drink', 'handler', 'loyaltyCard', 'loyaltyDiscounts');
        return response()->json(['order' => $this->formatOrder($order, true)]);
    }

    /**
     * Création d'une nouvelle commande.
     *
     * Payload attendu :
     * {
     *   "customer_name": "...",          // optionnel si carte fidélité
     *   "loyalty_card_number": "...",    // optionnel
     *   "card_pin": "...",               // requis si loyalty_discount_ids non vide
     *   "is_employee_order": false,      // optionnel
     *   "loyalty_discount_ids": [1, 2],  // optionnel
     *   "notes": "...",                  // optionnel
     *   "items": [
     *     {"drink_id": 1, "quantity": 2},
     *     {"custom_label": "Supplément", "custom_price": 0.50, "quantity": 1}
     *   ]
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'          => ['nullable', 'string', 'max:100'],
            'loyalty_card_number'    => ['nullable', 'string', 'max:20'],
            'card_pin'               => ['nullable', 'string', 'max:10'],
            'is_employee_order'      => ['nullable', 'boolean'],
            'loyalty_discount_ids'   => ['nullable', 'array'],
            'loyalty_discount_ids.*' => ['integer', 'exists:loyalty_discounts,id'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.drink_id'       => ['nullable', 'integer', 'exists:drinks,id'],
            'items.*.custom_label'   => ['nullable', 'string', 'max:150'],
            'items.*.custom_price'   => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'items.*.quantity'       => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $isEmployeeOrder = (bool) ($validated['is_employee_order'] ?? false);
        $loyaltyCard     = null;
        $discountIds     = array_values(array_filter((array) ($validated['loyalty_discount_ids'] ?? [])));

        // Résolution de la carte de fidélité
        if (!empty($validated['loyalty_card_number'])) {
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

        // Vérification du PIN si des réductions sont demandées
        $loyaltyDiscounts = collect();
        if (!empty($discountIds)) {
            if (!$loyaltyCard) {
                throw ValidationException::withMessages([
                    'loyalty_discount_ids' => 'Les réductions fidélité nécessitent une carte valide.',
                ]);
            }

            $pin = $validated['card_pin'] ?? '';
            if (!$pin || !Hash::check($pin, $loyaltyCard->pin)) {
                throw ValidationException::withMessages([
                    'card_pin' => 'Code PIN incorrect ou manquant.',
                ]);
            }

            $loyaltyDiscounts = LoyaltyDiscount::whereIn('id', $discountIds)->get();
            $totalPointsCost  = 0;

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

        // Filtrage et calcul des articles
        $rawItems = collect($validated['items'])->filter(function ($item) {
            $hasDrink  = !empty($item['drink_id']);
            $hasCustom = !empty($item['custom_label']) && isset($item['custom_price']) && (float) $item['custom_price'] > 0;
            return $hasDrink || $hasCustom;
        });

        if ($rawItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Veuillez sélectionner au moins une boisson.',
            ]);
        }

        $subtotal   = 0;
        $orderItems = [];

        foreach ($rawItems as $item) {
            if (!empty($item['drink_id'])) {
                $drink = Drink::findOrFail($item['drink_id']);

                if (!$drink->available) {
                    throw ValidationException::withMessages([
                        'items' => "La boisson « {$drink->name} » n'est plus disponible.",
                    ]);
                }

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

        // Calcul des réductions
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

        $totalLoyaltyAmount   = round($totalLoyaltyAmount, 2);
        $subtotalAfterLoyalty = round(max(0.0, $subtotal - $totalLoyaltyAmount), 2);
        $employeeDiscount     = $isEmployeeOrder ? round($subtotalAfterLoyalty * Order::EMPLOYEE_DISCOUNT_RATE, 2) : 0;
        $total                = round(max(0.0, $subtotalAfterLoyalty - $employeeDiscount), 2);

        $order = DB::transaction(function () use (
            $validated, $loyaltyCard, $isEmployeeOrder, $total,
            $employeeDiscount, $loyaltyDiscounts, $discountRows,
            $totalLoyaltyPoints, $totalLoyaltyAmount, $orderItems
        ) {
            $initialStatus = OrderStatus::where('is_active', true)
                ->orderBy('sort_order')
                ->value('key') ?? Order::STATUS_PENDING;

            $order = Order::create([
                'customer_name'          => $loyaltyCard ? $loyaltyCard->full_name : ($validated['customer_name'] ?? null),
                'loyalty_card_id'        => $loyaltyCard?->id,
                'is_employee_order'      => $isEmployeeOrder,
                'status'                 => $initialStatus,
                'notes'                  => $validated['notes'] ?? null,
                'total_amount'           => $total,
                'discount_amount'        => $isEmployeeOrder ? $employeeDiscount : 0,
                'loyalty_points_spent'   => $totalLoyaltyPoints,
                'loyalty_discount_amount'=> $totalLoyaltyAmount,
                'handled_by'             => Auth::guard('api')->id(),
                'points_credited'        => false,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            foreach ($discountRows as $row) {
                $order->loyaltyDiscounts()->attach($row['loyalty_discount_id'], [
                    'points_spent'    => $row['points_spent'],
                    'discount_amount' => $row['discount_amount'],
                ]);
            }

            if ($loyaltyCard && $totalLoyaltyPoints > 0) {
                $loyaltyCard->decrement('points', $totalLoyaltyPoints);
                foreach ($loyaltyDiscounts as $discount) {
                    if (!$discount->is_permanent && $discount->quantity_limit !== null) {
                        $discount->increment('quantity_used');
                    }
                }
            }

            return $order;
        });

        $order->load('items.drink', 'loyaltyCard', 'loyaltyDiscounts');

        return response()->json([
            'message' => 'Commande créée avec succès.',
            'order'   => $this->formatOrder($order, true),
        ], 201);
    }

    /**
     * Mise à jour du statut d'une commande.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'exists:order_statuses,key'],
        ]);

        $currentStatus = $order->orderStatus;
        if ($currentStatus?->is_terminal && !Auth::user()?->isSuperAdmin()) {
            return response()->json(['message' => 'Ce statut est terminal et ne peut pas être modifié.'], 403);
        }

        $order->update(['status' => $validated['status']]);

        if ($order->fresh()->orderStatus?->is_terminal) {
            $order->update(['completed_at' => now()]);
        }

        return response()->json([
            'message' => 'Statut mis à jour.',
            'order'   => $this->formatOrder($order->fresh()->load('items.drink', 'loyaltyCard', 'loyaltyDiscounts'), true),
        ]);
    }

    /**
     * Liste les statuts disponibles.
     */
    public function statuses(): JsonResponse
    {
        $statuses = OrderStatus::orderBy('sort_order')->get()->map(fn(OrderStatus $s) => [
            'key'         => $s->key,
            'label'       => $s->label,
            'color'       => $s->color ?? null,
            'is_terminal' => (bool) $s->is_terminal,
            'is_active'   => (bool) $s->is_active,
        ]);

        return response()->json(['statuses' => $statuses]);
    }

    private function formatOrder(Order $order, bool $detailed = false): array
    {
        $data = [
            'id'                      => $order->id,
            'customer_name'           => $order->display_name,
            'status'                  => $order->status,
            'status_label'            => $order->status_label,
            'is_employee_order'       => (bool) $order->is_employee_order,
            'total_amount'            => (float) $order->total_amount,
            'discount_amount'         => (float) $order->discount_amount,
            'loyalty_discount_amount' => (float) $order->loyalty_discount_amount,
            'loyalty_points_spent'    => (int) $order->loyalty_points_spent,
            'notes'                   => $order->notes,
            'created_at'              => $order->created_at?->toIso8601String(),
            'completed_at'            => $order->completed_at?->toIso8601String(),
            'handled_by'              => $order->handler?->name,
        ];

        if ($detailed) {
            $data['items'] = $order->items->map(fn(OrderItem $item) => [
                'id'           => $item->id,
                'drink_id'     => $item->drink_id,
                'drink_name'   => $item->display_name,
                'quantity'     => (int) $item->quantity,
                'unit_price'   => (float) $item->unit_price,
                'subtotal'     => (float) $item->subtotal,
                'custom_label' => $item->custom_label,
            ]);
            $data['loyalty_card'] = $order->loyaltyCard ? [
                'card_number' => $order->loyaltyCard->card_number,
                'full_name'   => $order->loyaltyCard->full_name,
                'points'      => (int) $order->loyaltyCard->points,
            ] : null;
            $data['loyalty_discounts'] = $order->loyaltyDiscounts->map(fn(LoyaltyDiscount $d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'points_spent'    => (int) $d->pivot->points_spent,
                'discount_amount' => (float) $d->pivot->discount_amount,
            ]);
        }

        return $data;
    }
}
