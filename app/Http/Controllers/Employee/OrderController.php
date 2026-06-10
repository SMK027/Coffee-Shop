<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Drink;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'customer_name'     => ['required', 'string', 'max:100'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.drink_id'  => ['required', 'integer', 'exists:drinks,id'],
            'items.*.quantity'  => ['required', 'integer', 'min:1', 'max:20'],
        ]);

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

        $order = Order::create([
            'customer_name' => $validated['customer_name'],
            'notes'         => $validated['notes'] ?? null,
            'total_amount'  => $total,
            'status'        => Order::STATUS_PENDING,
            'handled_by'    => auth()->id(),
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

        return redirect()->back()->with('success', 'Statut de la commande mis à jour.');
    }
}
