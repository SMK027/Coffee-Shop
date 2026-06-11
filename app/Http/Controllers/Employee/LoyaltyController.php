<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyCard;
use App\Models\Setting;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    /**
     * Liste des cartes de fidélité (avec recherche).
     */
    public function index(Request $request)
    {
        $query = LoyaltyCard::withCount('orders')->latest();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('card_number', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $cards          = $query->paginate(20)->withQueryString();
        $pointsPerEuro  = Setting::pointsPerEuro();

        return view('employee.loyalty.index', compact('cards', 'pointsPerEuro'));
    }

    /**
     * Détail d'une carte et de ses commandes.
     */
    public function show(LoyaltyCard $loyaltyCard)
    {
        $loyaltyCard->load(['orders' => fn ($q) => $q->latest()]);

        return view('employee.loyalty.show', compact('loyaltyCard'));
    }

    /**
     * Formulaire de réglage du programme de fidélité (super admin uniquement).
     */
    public function settings()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $pointsPerEuro = Setting::pointsPerEuro();

        return view('employee.loyalty.settings', compact('pointsPerEuro'));
    }

    /**
     * Met à jour le ratio de points par euro (super admin uniquement).
     */
    public function updateSettings(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'points_per_euro' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        Setting::set(Setting::KEY_POINTS_PER_EURO, (string) $validated['points_per_euro']);

        return back()->with('success', 'Ratio de fidélité mis à jour : ' . $validated['points_per_euro'] . ' points par euro.');
    }
}
