<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Drink;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyDiscount;
use App\Models\Voucher;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
        $order->load('items.drink', 'handler', 'loyaltyCard', 'loyaltyDiscounts', 'payments.paymentMethod', 'refunds.paymentMethod', 'voucher');

        $allStatuses  = OrderStatus::orderBy('sort_order')->get();
        $statusLabels = $allStatuses->pluck('label', 'key')->all();

        // Calcule les transitions disponibles depuis le statut courant
        $currentStatus = $allStatuses->firstWhere('key', $order->status);
        $availableTransitions = collect();
        $isSuperAdmin = Auth::user()?->isSuperAdmin();
        if ($currentStatus && (!$currentStatus->is_terminal || $isSuperAdmin)) {
            $availableTransitions = $allStatuses->filter(
                fn (OrderStatus $s) => $s->is_active
                    && $s->key !== $order->status
                    && (
                        $isSuperAdmin
                        || $s->is_terminal
                        || $s->sort_order > $currentStatus->sort_order
                    )
            )->values();
        }

        return view('employee.orders.show', compact('order', 'statusLabels', 'availableTransitions'));
    }

    /**
     * Supprime définitivement une commande annulée.
     * Super administrateur autorisé directement, les administrateurs simples
     * doivent fournir la validation d'un superviseur.
     */
    public function destroy(Request $request, Order $order)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($order->status !== Order::STATUS_CANCELLED) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'La commande doit être annulée pour être supprimée.'], 403);
            }
            return back()->withErrors(['order' => 'La commande doit être annulée pour être supprimée.']);
        }

        if (! auth()->user()->isSuperAdmin()) {
            $this->requireSuperAdminOrSupervisor($request, 'Action réservée aux super administrateurs ou à un superviseur valide.');
        }

        DB::transaction(function () use ($order, $request) {
            $order->load('loyaltyCard', 'loyaltyDiscounts', 'voucher');

            // Restaurer les points dépensés sur la carte fidélité
            if ($order->loyaltyCard && ($order->loyalty_points_spent ?? 0) > 0) {
                $order->loyaltyCard()->increment('points', (int) $order->loyalty_points_spent);
            }

            // Restaurer les compteurs des réductions consommables
            foreach ($order->loyaltyDiscounts as $discount) {
                if (! $discount->is_permanent && $discount->quantity_limit !== null) {
                    // Ne pas descendre en dessous de 0
                    $discount->decrement('quantity_used');
                }
            }

            // Marquer le bon d'achat comme disponible à nouveau
            if ($order->voucher) {
                $order->voucher->update(['is_used' => false, 'used_at' => null]);
            }

            // Restaurer les offres personnalisées utilisées par cette commande
            $usedCardOffers = \App\Models\CardOffer::where('used_in_order_id', $order->id)->get();
            foreach ($usedCardOffers as $offer) {
                $offer->update(['is_used' => false, 'used_at' => null, 'used_in_order_id' => null]);
            }

            // Supprime les éléments liés proprement
            $order->items()->delete();
            $order->payments()->delete();
            $order->refunds()->delete();
            $order->loyaltyDiscounts()->detach();

            $orderId = $order->id;
            $order->delete();

            ActivityLogger::log(
                'order.deleted',
                "Commande #{$orderId} supprimée par " . auth()->user()->name,
                null, null,
                ['order_id' => $orderId]
            );
        });

        return redirect()->route('employee.orders.index')->with('success', 'Commande supprimée.');
    }

    public function create()
    {
        // Étape 2 : nécessite que l'étape 1 ait été complétée
        if (!session()->has('order_draft_customer')) {
            return redirect()->route('employee.orders.identify');
        }

        $drinks    = Drink::available()->with('category')->orderBy('category_id')->orderBy('sort_order')->get();
        $customer  = session('order_draft_customer');

        // Charge les réductions et offres de carte pour le calcul du total en JS
        $loyaltyDiscounts = collect();
        $cardOffers = collect();
        if (!empty($customer['loyalty_discount_ids'])) {
            $loyaltyDiscounts = LoyaltyDiscount::whereIn('id', $customer['loyalty_discount_ids'])->get();
        }
        if (!empty($customer['card_offer_ids']) && !empty($customer['loyalty_card_id'])) {
            $card = LoyaltyCard::find($customer['loyalty_card_id']);
            if ($card) {
                $cardOffers = $card->cardOffers()
                    ->whereIn('id', (array) $customer['card_offer_ids'])
                    ->where('is_used', false)
                    ->where('expires_at', '>', now())
                    ->get();
            }
        }

        return view('employee.orders.create', compact('drinks', 'customer', 'loyaltyDiscounts', 'cardOffers'));
    }

    public function identify()
    {
        $discounts = LoyaltyDiscount::where('is_active', true)
            ->latest()
            ->get()
            ->filter(fn(LoyaltyDiscount $d) => $d->isValidForUse())
            ->values();

        $draft = session('order_draft_customer');
        $cardOffers = collect();

        if (!empty($draft['loyalty_card_id'])) {
            $card = LoyaltyCard::find($draft['loyalty_card_id']);
            if ($card) {
                $cardOffers = $card->cardOffers()
                    ->where('is_used', false)
                    ->where('expires_at', '>', now())
                    ->latest()
                    ->get();
            }
        }

        if ($draft['use_loyalty'] ?? false) {
            $cardNumber = $draft['loyalty_card_number'] ?? null;
            if ($cardNumber && $cardOffers->isEmpty()) {
                $card = LoyaltyCard::where('card_number', str_replace(' ', '', $cardNumber))->first();
                if ($card) {
                    $cardOffers = $card->cardOffers()
                        ->where('is_used', false)
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->get();
                }
            }
        }

        return view('employee.orders.identify', compact('discounts', 'draft', 'cardOffers'));
    }

    public function storeIdentification(Request $request)
    {
        $useLoyalty    = $request->boolean('use_loyalty');
        $usesDiscounts = !empty($request->input('loyalty_discount_ids'));
        $usesCardOffers = !empty($request->input('card_offer_ids'));

        $validated = $request->validate([
            'use_loyalty'            => ['nullable', 'boolean'],
            'is_employee_order'      => ['nullable', 'boolean'],
            'customer_name'          => ['nullable', 'string', 'max:100'],
            'loyalty_card_number'    => [Rule::requiredIf($useLoyalty), 'nullable', 'string', 'max:20'],
            'loyalty_discount_ids'   => ['nullable', 'array'],
            'loyalty_discount_ids.*' => ['integer', 'exists:loyalty_discounts,id'],
            'card_offer_ids'         => ['nullable', 'array'],
            'card_offer_ids.*'       => ['integer', 'exists:card_offers,id'],
            'card_pin'               => [Rule::requiredIf($usesDiscounts || $usesCardOffers), 'nullable', 'string', 'max:10'],
            'notes'                  => ['nullable', 'string', 'max:500'],
        ]);

        $isEmployeeOrder = $request->boolean('is_employee_order');
        $loyaltyCard     = null;

        if ($useLoyalty) {
            $cardNumber  = str_replace(' ', '', $validated['loyalty_card_number']);
            $loyaltyCard = LoyaltyCard::where('card_number', $cardNumber)->first();

            if (!$loyaltyCard) {
                return back()->withInput()->withErrors([
                    'loyalty_card_number' => 'Aucune carte de fidélité ne correspond à ce numéro.',
                ]);
            }

            if ($loyaltyCard->hasEmployeeBenefits()) {
                $isEmployeeOrder = true;
            }
        }

        // Vérification du PIN et des réductions (si des réductions sont sélectionnées)
        $discountIds  = array_values(array_filter((array) ($validated['loyalty_discount_ids'] ?? [])));
        $cardOfferIds = array_values(array_filter((array) ($validated['card_offer_ids'] ?? [])));
        $pinVerified  = false;

        if (!empty($discountIds) || !empty($cardOfferIds)) {
            if (!$loyaltyCard) {
                return back()->withInput()->withErrors([
                    'loyalty_discount_ids' => 'Les réductions fidélité nécessitent une carte valide.',
                ]);
            }

            $pin = $validated['card_pin'] ?? '';
            if (!$pin || !Hash::check($pin, $loyaltyCard->pin)) {
                return back()->withInput()->withErrors([
                    'card_pin' => 'Code de carte incorrect ou manquant.',
                ]);
            }

            $selectedDiscounts = LoyaltyDiscount::whereIn('id', $discountIds)->get();
            $totalPointsCost   = 0;

            foreach ($selectedDiscounts as $discount) {
                if (!$discount->isValidForUse()) {
                    return back()->withInput()->withErrors([
                        'loyalty_discount_ids' => "La réduction « {$discount->name} » n'est plus valide.",
                    ]);
                }
                if ($discount->employee_only && !$loyaltyCard->hasEmployeeBenefits()) {
                    return back()->withInput()->withErrors([
                        'loyalty_discount_ids' => "La réduction « {$discount->name} » est réservée aux salariés.",
                    ]);
                }
                $totalPointsCost += $discount->points_cost;
            }

            if ($loyaltyCard->points < $totalPointsCost) {
                return back()->withInput()->withErrors([
                    'loyalty_discount_ids' => 'Points insuffisants pour appliquer toutes les réductions sélectionnées.',
                ]);
            }

            $selectedCardOffers = $loyaltyCard->cardOffers()
                ->whereIn('id', $cardOfferIds)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->get();

            if ($selectedCardOffers->count() !== count($cardOfferIds)) {
                return back()->withInput()->withErrors([
                    'card_offer_ids' => 'Une ou plusieurs offres personnalisées ne sont plus valides.',
                ]);
            }

            $pinVerified = true;
        }

        session([
            'order_draft_customer' => [
                'customer_name'        => $useLoyalty ? null : ($validated['customer_name'] ?? null),
                'use_loyalty'          => $useLoyalty,
                'loyalty_card_id'      => $loyaltyCard?->id,
                'loyalty_card_number'  => $useLoyalty ? str_replace(' ', '', $validated['loyalty_card_number'] ?? '') : null,
                'card_full_name'       => $loyaltyCard?->full_name,
                'card_points'          => $loyaltyCard?->points,
                'card_has_benefits'    => $loyaltyCard ? $loyaltyCard->hasEmployeeBenefits() : false,
                'is_employee_order'    => $isEmployeeOrder,
                'loyalty_discount_ids' => $discountIds,
                'card_offer_ids'       => $cardOfferIds,
                'pin_verified'         => $pinVerified,
                'notes'                => $validated['notes'] ?? null,
            ],
        ]);

        return redirect()->route('employee.orders.create');
    }

    public function searchLoyaltyCard(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $cards = LoyaltyCard::query()
            ->where(function ($q) use ($query) {
                $q->where('last_name',   'like', "%{$query}%")
                  ->orWhere('first_name', 'like', "%{$query}%")
                  ->orWhere('email',      'like', "%{$query}%")
                  ->orWhere('phone',      'like', "%{$query}%")
                  ->orWhere('card_number','like', "%{$query}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get(['id', 'card_number', 'last_name', 'first_name', 'email', 'phone', 'points']);

        return response()->json([
            'results' => $cards->map(fn(LoyaltyCard $c) => [
                'card_number'         => $c->card_number,
                'full_name'           => $c->full_name,
                'email'               => $c->email,
                'phone'               => $c->phone,
                'points'              => (int) $c->points,
                'has_employee_benefits' => (bool) $c->hasEmployeeBenefits(),
            ]),
        ]);
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

        $offers = $card->cardOffers()
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->get()
            ->map(fn ($offer) => [
                'id' => $offer->id,
                'label' => $offer->label,
                'display_value' => $offer->display_value,
            ]);

        return response()->json([
            'valid' => true,
            'offers' => $offers,
        ]);
    }

    public function store(Request $request)
    {
        // Étape 2 : données client depuis la session (étape 1)
        $customer = session('order_draft_customer');
        if (!$customer) {
            return redirect()->route('employee.orders.identify')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }

        // Vérification des horaires d'ouverture
        if (!\App\Models\Setting::isWithinOpeningHours()) {
            $this->requireSuperAdminOrSupervisor($request, 'La boutique est fermée. Un bypass superviseur est requis pour créer une commande.');
        }

        $validated = $request->validate([
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.drink_id'       => ['nullable', 'integer', 'exists:drinks,id'],
            'items.*.custom_label'   => ['nullable', 'string', 'max:150'],
            'items.*.custom_price'   => ['nullable', 'numeric', 'min:0.01', 'max:999.99'],
            'items.*.quantity'       => ['required', 'integer', 'min:1', 'max:250'],
            'voucher_code'           => ['nullable', 'string', 'max:20'],
        ]);

        // Reconstruction depuis la session
        $useLoyalty      = (bool) $customer['use_loyalty'];
        $isEmployeeOrder = (bool) $customer['is_employee_order'];
        $discountIds     = (array) ($customer['loyalty_discount_ids'] ?? []);
        $cardOfferIds    = (array) ($customer['card_offer_ids'] ?? []);
        $pinVerified     = (bool) ($customer['pin_verified'] ?? false);

        $loyaltyCard = null;
        if ($useLoyalty && !empty($customer['loyalty_card_id'])) {
            $loyaltyCard = LoyaltyCard::find($customer['loyalty_card_id']);
            if (!$loyaltyCard) {
                session()->forget('order_draft_customer');
                return redirect()->route('employee.orders.identify')
                    ->with('error', 'La carte de fidélité est introuvable. Veuillez recommencer.');
            }
        }

        // Chargement des réductions depuis la session
        $loyaltyDiscounts = collect();
        $cardOffers = collect();
        if (!empty($discountIds)) {
            if (!$pinVerified || !$loyaltyCard) {
                session()->forget('order_draft_customer');
                return redirect()->route('employee.orders.identify')
                    ->with('error', 'Erreur de session. Veuillez recommencer.');
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

        if (!empty($cardOfferIds)) {
            if (!$pinVerified || !$loyaltyCard) {
                session()->forget('order_draft_customer');
                return redirect()->route('employee.orders.identify')
                    ->with('error', 'Erreur de session. Veuillez recommencer.');
            }

            $cardOffers = $loyaltyCard->cardOffers()
                ->whereIn('id', $cardOfferIds)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->get();

            if ($cardOffers->count() !== count($cardOfferIds)) {
                throw ValidationException::withMessages([
                    'card_offer_ids' => 'Une ou plusieurs offres personnalisées ne sont plus valides.',
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
        $cardOfferDiscount  = 0;
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

        foreach ($cardOffers as $offer) {
            if ($offer->discount_type === \App\Models\CardOffer::TYPE_PERCENT) {
                $amount = round($remaining * ((float) $offer->discount_value / 100), 2);
                if ($offer->max_discount_amount !== null) {
                    $amount = min($amount, round((float) $offer->max_discount_amount, 2));
                }
            } else {
                $amount = round(min($remaining, (float) $offer->discount_value), 2);
            }

            $remaining = max(0.0, $remaining - $amount);
            $cardOfferDiscount += $amount;
        }

        $totalLoyaltyAmount    = round($totalLoyaltyAmount, 2);
        $cardOfferDiscount     = round($cardOfferDiscount, 2);
        $subtotalAfterLoyalty  = round(max(0.0, $subtotal - $totalLoyaltyAmount - $cardOfferDiscount), 2);

        // 2. Réduction salarié appliquée sur le solde après réductions fidélité.
        $employeeDiscount = $isEmployeeOrder ? round($subtotalAfterLoyalty * Order::EMPLOYEE_DISCOUNT_RATE, 2) : 0;
        $afterEmployee    = round(max(0.0, $subtotalAfterLoyalty - $employeeDiscount), 2);

        // 3. Bon d'achat / avoir
        $voucher         = null;
        $voucherDiscount = 0;
        $voucherCode     = strtoupper(str_replace(' ', '', trim((string) ($validated['voucher_code'] ?? ''))));
        if ($voucherCode !== '') {
            $voucher = Voucher::where('code', $voucherCode)->first();
            if (! $voucher || ! $voucher->isValid()) {
                return back()->withInput()->withErrors([
                    'voucher_code' => "Ce bon d'achat est invalide, expiré ou déjà utilisé.",
                ]);
            }

            // Vérification de la restriction client
            $effectiveName = $loyaltyCard
                ? $loyaltyCard->full_name
                : ($customer['customer_name'] ?? null);

            if (! $voucher->isValidFor($loyaltyCard?->id, $effectiveName)) {
                return back()->withInput()->withErrors([
                    'voucher_code' => "Ce bon d'achat est réservé à un autre client.",
                ]);
            }

            $voucherDiscount = round(min((float) $voucher->amount, $afterEmployee), 2);
        }
        $total = round(max(0.0, $afterEmployee - $voucherDiscount), 2);

        $order = DB::transaction(function () use (
            $validated, $customer, $loyaltyCard, $isEmployeeOrder, $total,
            $employeeDiscount, $loyaltyDiscounts, $discountRows,
            $totalLoyaltyPoints, $totalLoyaltyAmount, $orderItems,
            $voucher, $voucherDiscount, $cardOffers, $cardOfferDiscount
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

            // Vérifie et verrouille le bon d'achat si présent
            if ($voucher) {
                $lockedVoucher = Voucher::whereKey($voucher->id)->lockForUpdate()->first();
                if (! $lockedVoucher || ! $lockedVoucher->isValid()) {
                    throw ValidationException::withMessages([
                        'voucher_code' => "Ce bon d'achat n'est plus disponible.",
                    ]);
                }
                $lockedVoucher->update(['is_used' => true, 'used_at' => now()]);
            }

            $order = Order::create([
                'customer_name'            => $lockedCard ? $lockedCard->full_name : ($customer['customer_name'] ?? null),
                'loyalty_card_id'          => $lockedCard?->id,
                'is_employee_order'        => $isEmployeeOrder,
                'notes'                    => $customer['notes'] ?? null,
                'total_amount'             => $total,
                'discount_amount'          => $employeeDiscount,
                'loyalty_points_spent'     => $totalPointsNeeded,
                'loyalty_discount_amount'  => $totalLoyaltyAmount,
            'card_offer_discount'      => $cardOfferDiscount,
                'handled_by'               => auth()->id(),
            ]);

            $order->items()->createMany($orderItems);

            foreach ($pivotRows as $row) {
                $order->loyaltyDiscounts()->attach($row['loyalty_discount_id'], [
                    'points_spent'    => $row['points_spent'],
                    'discount_amount' => $row['discount_amount'],
                ]);
            }

            foreach ($cardOffers as $offer) {
                $offer->forceFill([
                    'is_used' => true,
                    'used_at' => now(),
                    'used_in_order_id' => $order->id,
                ])->save();
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

        session()->forget('order_draft_customer');

        ActivityLogger::log(
            'order.created',
            'Commande #' . str_pad($order->id, 4, '0', STR_PAD_LEFT) . ' créée — ' . ($order->customer_name ?? 'Anonyme'),
            'order', $order->id,
            array_filter([
                'client'  => $order->customer_name,
                'total'   => (float) $order->total_amount,
                'articles'=> $order->items()->count(),
                'bon'     => $voucherCode ?: null,
            ])
        );

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

        ActivityLogger::log(
            'order.status_changed',
            'Statut commande #' . str_pad($order->id, 4, '0', STR_PAD_LEFT) . ' → ' . ($newStatus?->label ?? $validated['status']),
            'order', $order->id,
            ['nouveau_statut' => $validated['status'], 'libellé' => $newStatus?->label]
        );

        return redirect()->back()->with('success', 'Statut de la commande mis à jour.');
    }
}
