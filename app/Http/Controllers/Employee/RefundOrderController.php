<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointAdjustment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\PaymentMethod;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundOrderController extends Controller
{
    /**
     * Section remboursements : recherche de commandes par client.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isModerator(), 403);

        $search = trim((string) $request->query('q', ''));
        $orders = collect();

        if ($search !== '') {
            $query = Order::with('items', 'loyaltyCard', 'payments.paymentMethod')
                ->where('status', 'completed')
                ->latest();

            $query->where(function ($filter) use ($search) {
                $filter->where('customer_name', 'like', "%{$search}%");

                if (ctype_digit(str_replace(' ', '', $search))) {
                    $clean = preg_replace('/\s+/', '', $search);
                    $filter->orWhereHas('loyaltyCard', function ($lq) use ($clean) {
                        $lq->where('card_number', 'like', "%{$clean}%");
                    });
                    if (ctype_digit($search)) {
                        $filter->orWhere('id', (int) $search);
                    }
                }
            });

            $orders = $query->paginate(15)->withQueryString();
        }

        return view('employee.refunds.index', compact('orders', 'search'));
    }

    /**
     * Formulaire de remboursement pour une commande sélectionnée.
     */
    public function create(Request $request)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isModerator(), 403);

        $order = Order::findOrFail($request->integer('order_id'));
        $order->load('items.drink', 'loyaltyCard', 'payments.paymentMethod');

        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();

        $refundableItems = $order->items
            ->where('is_refund', false)
            ->filter(function (OrderItem $item) use ($order) {
                $alreadyRefunded = $order->items
                    ->where('is_refund', true)
                    ->where('refund_item_id', $item->id)
                    ->sum('quantity');
                $item->refundable_qty = $item->quantity - abs((int) $alreadyRefunded);
                return $item->refundable_qty > 0;
            })
            ->values();

        $hasTotalRefund = $order->hasTotalRefund();

        return view('employee.refunds.create', compact('order', 'refundableItems', 'paymentMethods', 'hasTotalRefund'));
    }

    /**
     * Enregistre le remboursement depuis la section dédiée.
     *
     * Payload attendu (un seul mode à la fois) :
     *   total_refund   = 1                  (remboursement total)
     *   custom_amount  = 12.50               (remboursement d'un montant au choix)
     *   items[]        = { item_id, qty }   (remboursement partiel article par article)
     */
    public function store(Request $request, Order $order)
    {
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isModerator(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $order->load('items.drink', 'loyaltyCard');

        $request->validate([
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'refund_reason'     => ['nullable', 'string', 'max:255'],
        ]);

        $paymentMethod = PaymentMethod::active()->find($request->input('payment_method_id'));
        if (! $paymentMethod) {
            return back()->withErrors(['payment_method_id' => 'Le moyen de paiement sélectionné est inactif ou introuvable.'])->withInput();
        }

        $isTotalRefund  = $request->boolean('total_refund');
        $isCustomRefund = ! $isTotalRefund && $request->filled('custom_amount');
        $refundType     = $isTotalRefund ? 'total' : ($isCustomRefund ? 'personnalisé' : 'partiel');

        if (! $isTotalRefund && $order->hasTotalRefund()) {
            return back()->withErrors(['items' => 'Un remboursement total a déjà été effectué pour cette commande.'])->withInput();
        }

        if ($isTotalRefund) {
            $this->applyTotalRefund($order, $paymentMethod->id, $request->input('refund_reason'));
        } elseif ($isCustomRefund) {
            $request->validate([
                'custom_amount' => ['required', 'numeric', 'min:0.01'],
            ]);

            $this->applyCustomRefund($order, (float) $request->input('custom_amount'), $paymentMethod->id, $request->input('refund_reason'));
        } else {
            $request->validate([
                'items'           => ['required', 'array', 'min:1'],
                'items.*.item_id' => ['required', 'integer', 'exists:order_items,id'],
                'items.*.qty'     => ['required', 'integer', 'min:1'],
            ]);

            $this->applyPartialRefund($order, $request->input('items', []), $paymentMethod->id, $request->input('refund_reason'));
        }

        ActivityLogger::log(
            'refund.applied',
            'Remboursement ' . $refundType . ' — commande #' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            'order', $order->id,
            array_filter(['type' => $refundType, 'mode_paiement' => $paymentMethod->name, 'motif' => $request->input('refund_reason')])
        );

        return redirect()
            ->route('employee.refunds.index')
            ->with('success', 'Remboursement enregistré avec succès.');
    }

    private function applyTotalRefund(Order $order, int $paymentMethodId, ?string $reason): void
    {
        DB::transaction(function () use ($order, $paymentMethodId, $reason) {
            $alreadyRefunded = (float) $order->refunded_amount;
            $remaining       = round((float) $order->total_amount - $alreadyRefunded, 2);

            if ($remaining <= 0) {
                return;
            }

            OrderItem::create([
                'order_id'     => $order->id,
                'drink_id'     => null,
                'custom_label' => 'Remboursement total',
                'custom_price' => null,
                'unit_price'   => -$remaining,
                'quantity'     => 1,
                'is_refund'    => true,
            ]);

            OrderRefund::create([
                'order_id'          => $order->id,
                'payment_method_id' => $paymentMethodId,
                'amount'            => $remaining,
                'reason'            => $reason,
                'created_by'        => auth()->id(),
                'type'              => OrderRefund::TYPE_TOTAL,
            ]);

            $order->increment('refunded_amount', $remaining);

            if ($order->loyalty_card_id && $order->points_awarded > 0) {
                $pointsToDebit = $order->points_awarded - $order->points_refunded;
                if ($pointsToDebit > 0) {
                    $card       = $order->loyaltyCard()->lockForUpdate()->first();
                    $newBalance = $card->points - $pointsToDebit;
                    $card->update(['points' => $newBalance]);
                    $order->increment('points_refunded', $pointsToDebit);
                    LoyaltyPointAdjustment::create([
                        'loyalty_card_id' => $order->loyalty_card_id,
                        'order_id'        => $order->id,
                        'user_id'         => auth()->id(),
                        'type'            => LoyaltyPointAdjustment::TYPE_DEBIT,
                        'source'          => LoyaltyPointAdjustment::SOURCE_REFUND,
                        'points'          => $pointsToDebit,
                        'balance_after'   => $newBalance,
                        'reason'          => 'Remboursement total — commande #' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    ]);
                }
            }
        });
    }

    /** Remboursement d'un montant au choix, non rattaché à des articles précis. */
    private function applyCustomRefund(Order $order, float $amount, int $paymentMethodId, ?string $reason): void
    {
        DB::transaction(function () use ($order, $amount, $paymentMethodId, $reason) {
            $remaining = round((float) $order->total_amount - (float) $order->refunded_amount, 2);
            $amount    = round(min($amount, $remaining), 2);

            if ($amount <= 0) {
                return;
            }

            OrderItem::create([
                'order_id'     => $order->id,
                'drink_id'     => null,
                'custom_label' => 'Remboursement — montant personnalisé',
                'custom_price' => null,
                'unit_price'   => -$amount,
                'quantity'     => 1,
                'is_refund'    => true,
            ]);

            OrderRefund::create([
                'order_id'          => $order->id,
                'payment_method_id' => $paymentMethodId,
                'amount'            => $amount,
                'reason'            => $reason,
                'created_by'        => auth()->id(),
                'type'              => OrderRefund::TYPE_CUSTOM,
            ]);

            $order->increment('refunded_amount', $amount);

            if ($order->loyalty_card_id && $order->points_awarded > 0 && (float) $order->total_amount > 0) {
                $pointsRemaining = $order->points_awarded - $order->points_refunded;
                $pointsToDebit   = (int) floor($pointsRemaining * ($amount / (float) $order->total_amount));

                if ($pointsToDebit > 0) {
                    $card       = $order->loyaltyCard()->lockForUpdate()->first();
                    $newBalance = $card->points - $pointsToDebit;
                    $card->update(['points' => $newBalance]);
                    $order->increment('points_refunded', $pointsToDebit);
                    LoyaltyPointAdjustment::create([
                        'loyalty_card_id' => $order->loyalty_card_id,
                        'order_id'        => $order->id,
                        'user_id'         => auth()->id(),
                        'type'            => LoyaltyPointAdjustment::TYPE_DEBIT,
                        'source'          => LoyaltyPointAdjustment::SOURCE_REFUND,
                        'points'          => $pointsToDebit,
                        'balance_after'   => $newBalance,
                        'reason'          => 'Remboursement personnalisé — commande #' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    ]);
                }
            }
        });
    }

    private function applyPartialRefund(Order $order, array $items, int $paymentMethodId, ?string $reason): void
    {
        DB::transaction(function () use ($order, $items, $paymentMethodId, $reason) {
            $totalRefundAmount  = 0;
            $totalPointsToDebit = 0;

            foreach ($items as $itemData) {
                $originalItem = $order->items->firstWhere('id', (int) $itemData['item_id']);

                if (! $originalItem || $originalItem->is_refund) {
                    continue;
                }

                $alreadyRefundedQty = $order->items
                    ->where('is_refund', true)
                    ->where('refund_item_id', $originalItem->id)
                    ->sum('quantity');

                $maxQty     = $originalItem->quantity - abs((int) $alreadyRefundedQty);
                $requestQty = min((int) $itemData['qty'], $maxQty);

                if ($requestQty <= 0) {
                    continue;
                }

                $unitPrice    = $originalItem->refund_unit_price;
                $refundAmount = round($unitPrice * $requestQty, 2);
                $label        = 'Remboursement – ' . $originalItem->display_name;

                OrderItem::create([
                    'order_id'       => $order->id,
                    'drink_id'       => null,
                    'custom_label'   => $label,
                    'custom_price'   => null,
                    'unit_price'     => -$unitPrice,
                    'quantity'       => $requestQty,
                    'is_refund'      => true,
                    'refund_item_id' => $originalItem->id,
                ]);

                $totalRefundAmount += $refundAmount;

                if ($order->loyalty_card_id && $originalItem->drink && $originalItem->drink->loyalty_points > 0) {
                    $totalPointsToDebit += $originalItem->drink->loyalty_points * $requestQty;
                }
            }

            if ($totalRefundAmount > 0) {
                OrderRefund::create([
                    'order_id'          => $order->id,
                    'payment_method_id' => $paymentMethodId,
                    'amount'            => round($totalRefundAmount, 2),
                    'reason'            => $reason,
                    'created_by'        => auth()->id(),
                    'type'              => OrderRefund::TYPE_PARTIAL,
                ]);

                $order->increment('refunded_amount', $totalRefundAmount);
            }

            if ($totalPointsToDebit > 0) {
                $card       = $order->loyaltyCard()->lockForUpdate()->first();
                $newBalance = $card->points - $totalPointsToDebit;
                $card->update(['points' => $newBalance]);
                $order->increment('points_refunded', $totalPointsToDebit);
                LoyaltyPointAdjustment::create([
                    'loyalty_card_id' => $order->loyalty_card_id,
                    'order_id'        => $order->id,
                    'user_id'         => auth()->id(),
                    'type'            => LoyaltyPointAdjustment::TYPE_DEBIT,
                    'source'          => LoyaltyPointAdjustment::SOURCE_REFUND,
                    'points'          => $totalPointsToDebit,
                    'balance_after'   => $newBalance,
                    'reason'          => 'Remboursement partiel — commande #' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }
}
