<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderPaymentController extends Controller
{
    /**
     * Affiche le formulaire d'enregistrement des paiements pour une commande.
     */
    public function create(Order $order)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();
        $order->load('payments.paymentMethod');

        $alreadyPaid = $order->payments->sum('amount');
        $remaining   = round(max(0, (float) $order->total_amount - $alreadyPaid), 2);

        return view('employee.orders.payment', compact('order', 'paymentMethods', 'alreadyPaid', 'remaining'));
    }

    /**
     * Enregistre les lignes de paiement pour une commande.
     * Supporte plusieurs moyens de paiement simultanés.
     */
    public function store(Request $request, Order $order)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $request->validate([
            'payments'                  => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'payments.*.amount'         => ['required', 'numeric', 'min:0.01'],
        ]);

        // Vérifier que les méthodes sont actives
        $methodIds = collect($request->input('payments'))->pluck('payment_method_id')->unique();
        $activeMethods = PaymentMethod::active()->whereIn('id', $methodIds)->pluck('id');
        if ($activeMethods->count() !== $methodIds->count()) {
            return back()->withErrors(['payments' => 'Un ou plusieurs moyens de paiement sont inactifs.']);
        }

        DB::transaction(function () use ($request, $order) {
            // Supprimer les paiements existants et recréer (édition complète)
            $order->payments()->delete();

            foreach ($request->input('payments') as $row) {
                OrderPayment::create([
                    'order_id'          => $order->id,
                    'payment_method_id' => $row['payment_method_id'],
                    'amount'            => round((float) $row['amount'], 2),
                ]);
            }
        });

        return redirect()->route('employee.orders.show', $order)
            ->with('success', 'Paiements enregistrés avec succès.');
    }
}
