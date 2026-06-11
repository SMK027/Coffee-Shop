<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Drink;
use App\Models\LoyaltyCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items.drink', 'handler')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(20);
        $statusLabels = Order::STATUS_LABELS;

        return view('employee.orders.index', compact('orders', 'statusLabels'));
    }

    public function show(Order $order)
    {
        $order->load('items.drink', 'handler');
        $statusLabels = Order::STATUS_LABELS;

        return view('employee.orders.show', compact('order', 'statusLabels'));
    }

    public function create()
    {
        $drinks = Drink::available()->with('category')->orderBy('category_id')->orderBy('sort_order')->get();

        return view('employee.orders.create', compact('drinks'));
    }

    public function store(Request $request)
    {
        $useLoyalty      = $request->boolean('use_loyalty');
        $isEmployeeOrder = $request->boolean('is_employee_order');

        $validated = $request->validate([
            'use_loyalty'        => ['nullable', 'boolean'],
            'is_employee_order'  => ['nullable', 'boolean'],
            'customer_name'      => [Rule::requiredIf(!$useLoyalty), 'nullable', 'string', 'max:100'],
            'loyalty_card_number'=> [Rule::requiredIf($useLoyalty), 'nullable', 'string', 'max:20'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.drink_id'  => ['required', 'integer', 'exists:drinks,id'],
            'items.*.quantity'  => ['required', 'integer', 'min:1', 'max:20'],
        ], [
            'customer_name.required'       => 'Le nom du client est requis (ou passez une carte de fidélité).',
            'loyalty_card_number.required' => 'Le numéro de carte de fidélité est requis.',
        ]);

        // Rattachement éventuel à une carte de fidélité (identification par numéro de carte uniquement).
        $loyaltyCard = null;
        if ($useLoyalty) {
            $cardNumber  = str_replace(' ', '', $validated['loyalty_card_number']);
            $loyaltyCard = LoyaltyCard::where('card_number', $cardNumber)->first();

            if (!$loyaltyCard) {
                throw ValidationException::withMessages([
                    'loyalty_card_number' => 'Aucune carte de fidélité ne correspond à ce numéro.',
                ]);
            }

            // La carte d'un salarié déclenche automatiquement la réduction employé.
            if ($loyaltyCard->hasEmployeeBenefits()) {
                $isEmployeeOrder = true;
            }
        }

        // Filtre les lignes sans boisson sélectionnée (sécurité côté serveur)
        $rawItems = collect($validated['items'])->filter(
            fn($item) => !empty($item['drink_id'])
        );

        if ($rawItems->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['items' => 'Veuillez sélectionner au moins une boisson.']);
        }

        $total      = 0;
        $orderItems = [];

        foreach ($rawItems as $item) {
            $drink      = Drink::findOrFail($item['drink_id']);
            $total      += $drink->price * $item['quantity'];
            $orderItems[] = [
                'drink_id'   => $drink->id,
                'quantity'   => (int) $item['quantity'],
                'unit_price' => $drink->price,
            ];
        }

        // Réduction immédiate de 15% pour les commandes des employés.
        $discount = $isEmployeeOrder ? round($total * Order::EMPLOYEE_DISCOUNT_RATE, 2) : 0;
        $total    = round($total - $discount, 2);

        $order = Order::create([
            'customer_name'     => $loyaltyCard ? $loyaltyCard->full_name : $validated['customer_name'],
            'loyalty_card_id'   => $loyaltyCard?->id,
            'is_employee_order' => $isEmployeeOrder,
            'notes'             => $validated['notes'] ?? null,
            'total_amount'      => $total,
            'discount_amount'   => $discount,
            'status'            => Order::STATUS_PENDING,
            'handled_by'        => auth()->id(),
        ]);

        $order->items()->createMany($orderItems);

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
