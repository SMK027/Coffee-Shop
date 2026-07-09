<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointAdjustment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    /**
     * Affiche le formulaire de remboursement pour une commande.
     */
    public function create(Order $order)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $order->load('items.drink', 'loyaltyCard');

        // Seuls les articles non-remboursement et qui ont encore un solde positif
        $refundableItems = $order->items
            ->where('is_refund', false)
            ->filter(function (OrderItem $item) use ($order) {
                // Quantité déjà remboursée pour cet article (par référence à l'item_id via custom_label)
                $alreadyRefunded = $order->items
                    ->where('is_refund', true)
                    ->where('refund_item_id', $item->id)
                    ->sum('quantity');
                $item->refundable_qty = $item->quantity - abs((int) $alreadyRefunded);
                return $item->refundable_qty > 0;
            })
            ->values();

        return view('employee.orders.refund', compact('order', 'refundableItems'));
    }

    /**
     * Enregistre le remboursement.
     *
     * Payload attendu :
     *   items[]       = { item_id, qty }   (articles sélectionnés + quantité)
     *   total_refund  = 1                  (si remboursement total)
     */
    public function store(Request $request, Order $order)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $order->load('items.drink', 'loyaltyCard');

        $isTotalRefund = $request->boolean('total_refund');

        if ($isTotalRefund) {
            $this->applyTotalRefund($order);
        } else {
            $request->validate([
                'items'          => ['required', 'array', 'min:1'],
                'items.*.item_id' => ['required', 'integer', 'exists:order_items,id'],
                'items.*.qty'     => ['required', 'integer', 'min:1'],
            ]);

            $this->applyPartialRefund($order, $request->input('items', []));
        }

        return redirect()
            ->route('employee.orders.show', $order)
            ->with('success', 'Remboursement enregistré avec succès.');
    }

    private function applyTotalRefund(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Montant déjà remboursé
            $alreadyRefunded = (float) $order->refunded_amount;
            $remaining       = round((float) $order->total_amount - $alreadyRefunded, 2);

            if ($remaining <= 0) {
                return; // déjà totalement remboursé
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

            $order->increment('refunded_amount', $remaining);

            // Débiter les points si une carte est liée et que des points ont été crédités
            if ($order->loyalty_card_id && $order->points_awarded > 0) {
                $pointsToDebit = $order->points_awarded - $order->points_refunded;
                if ($pointsToDebit > 0) {
                    // Calcul direct pour autoriser un solde négatif (unsigned interdit par MySQL sinon)
                    $card         = $order->loyaltyCard()->lockForUpdate()->first();
                    $newBalance   = $card->points - $pointsToDebit;
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

    private function applyPartialRefund(Order $order, array $items): void
    {
        DB::transaction(function () use ($order, $items) {
            $totalRefundAmount = 0;
            $totalPointsToDebit = 0;

            foreach ($items as $itemData) {
                $originalItem = $order->items->firstWhere('id', (int) $itemData['item_id']);

                if (!$originalItem || $originalItem->is_refund) {
                    continue;
                }

                // Quantité déjà remboursée pour cet item
                $alreadyRefundedQty = $order->items
                    ->where('is_refund', true)
                    ->where('refund_item_id', $originalItem->id)
                    ->sum('quantity');

                $maxQty     = $originalItem->quantity - abs((int) $alreadyRefundedQty);
                $requestQty = min((int) $itemData['qty'], $maxQty);

                if ($requestQty <= 0) {
                    continue;
                }

                $unitPrice     = (float) $originalItem->unit_price;
                $refundAmount  = round($unitPrice * $requestQty, 2);
                $label         = 'Remboursement – ' . $originalItem->display_name;

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

                // Points à débiter proportionnellement
                if ($order->loyalty_card_id && $originalItem->drink && $originalItem->drink->loyalty_points > 0) {
                    $totalPointsToDebit += $originalItem->drink->loyalty_points * $requestQty;
                }
            }

            if ($totalRefundAmount > 0) {
                $order->increment('refunded_amount', $totalRefundAmount);
            }

            if ($totalPointsToDebit > 0) {
                // Calcul direct pour autoriser un solde négatif (unsigned interdit par MySQL sinon)
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
