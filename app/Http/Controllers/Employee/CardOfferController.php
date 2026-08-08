<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CardOffer;
use App\Models\LoyaltyCard;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CardOfferController extends Controller
{
    public function store(Request $request, LoyaltyCard $loyaltyCard)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'label'               => ['required', 'string', 'max:150'],
            'discount_type'       => ['required', 'in:fixed,percent'],
            'discount_value'      => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'expires_at'          => ['required', 'date', 'after:today'],
        ]);

        if ($validated['discount_type'] === 'percent' && $validated['discount_value'] > 100) {
            return back()->withInput()->withErrors(['discount_value' => 'Un pourcentage ne peut pas dépasser 100 %.']);
        }

        if (! auth()->user()->isSuperAdmin()) {
            $this->requireSuperAdminOrSupervisor($request);
        }

        $offer = $loyaltyCard->cardOffers()->create([
            'label'               => $validated['label'],
            'discount_type'       => $validated['discount_type'],
            'discount_value'      => round((float) $validated['discount_value'], 2),
            'max_discount_amount' => isset($validated['max_discount_amount']) ? round((float) $validated['max_discount_amount'], 2) : null,
            'expires_at'          => $validated['expires_at'],
            'issued_by'           => auth()->id(),
        ]);

        ActivityLogger::log(
            'card_offer.created',
            "Offre « {$offer->label} » créée pour la carte {$loyaltyCard->card_number} ({$offer->display_value}, expire le {$offer->expires_at->format('d/m/Y')})",
            'card_offer', $offer->id,
            ['card' => $loyaltyCard->card_number, 'label' => $offer->label, 'valeur' => $offer->display_value]
        );

        return back()->with('success', 'Offre créée avec succès.');
    }

    public function destroy(LoyaltyCard $loyaltyCard, CardOffer $cardOffer)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($cardOffer->loyalty_card_id === $loyaltyCard->id, 404);
        abort_if($cardOffer->is_used, 403, 'Une offre déjà utilisée ne peut pas être supprimée.');

        if (! auth()->user()->isSuperAdmin()) {
            $this->requireSuperAdminOrSupervisor(request());
        }

        ActivityLogger::log(
            'card_offer.deleted',
            "Offre « {$cardOffer->label} » supprimée (carte {$loyaltyCard->card_number})",
            'card_offer', $cardOffer->id,
            ['card' => $loyaltyCard->card_number, 'label' => $cardOffer->label]
        );

        $cardOffer->delete();

        return back()->with('success', 'Offre supprimée.');
    }
}
