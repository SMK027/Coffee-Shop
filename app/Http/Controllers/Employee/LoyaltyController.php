<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Mail\LoyaltyPinResetMail;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyPinReset;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    /**
     * Déclenche la réinitialisation du code PIN d'une carte : génère un token
     * à usage unique (30 min) et envoie le lien au titulaire par email.
     * Réservé aux super administrateurs.
     */
    public function sendPinReset(LoyaltyCard $loyaltyCard)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        // Invalide tous les tokens non utilisés précédents pour cette carte
        LoyaltyPinReset::where('loyalty_card_id', $loyaltyCard->id)
            ->whereNull('used_at')
            ->delete();

        // Génère un token brut de 64 caractères (seul son hash est stocké)
        $plainToken = Str::random(64);

        LoyaltyPinReset::create([
            'loyalty_card_id' => $loyaltyCard->id,
            'token'           => hash('sha256', $plainToken),
            'created_at'      => now(),
        ]);

        $resetUrl = route('loyalty.pin.form', ['token' => $plainToken]);

        Mail::to($loyaltyCard->email)->send(new LoyaltyPinResetMail($loyaltyCard, $resetUrl));

        return back()->with('success', "Un lien de réinitialisation du code PIN a été envoyé à {$loyaltyCard->email}. Il expire dans 30 minutes.");
    }
}
