<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyCard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoyaltyController extends Controller
{
    /**
     * Formulaire public de création d'une carte de fidélité.
     */
    public function create()
    {
        return view('visitor.loyalty.create');
    }

    /**
     * Enregistre une nouvelle carte de fidélité.
     */
    public function store(Request $request)
    {
        $maxBirthDate = now()->subYears(LoyaltyCard::MIN_AGE)->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', 'unique:loyalty_cards,email'],
            'phone'      => ['required', 'string', 'max:30', 'regex:/^[0-9 +().-]{6,30}$/'],
            'birth_date' => ['required', 'date', 'before_or_equal:' . $maxBirthDate],
            'pin'        => ['required', 'confirmed', 'digits_between:4,6'],
        ], [
            'birth_date.before_or_equal' => 'Vous devez avoir au moins ' . LoyaltyCard::MIN_AGE . ' ans pour créer une carte de fidélité.',
            'pin.digits_between'         => 'Le code PIN doit contenir entre 4 et 6 chiffres.',
            'pin.confirmed'              => 'La confirmation du code PIN ne correspond pas.',
            'phone.regex'                => 'Le numéro de téléphone n\'est pas valide.',
        ]);

        $card = LoyaltyCard::create([
            'card_number' => LoyaltyCard::generateCardNumber(),
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'email'       => $validated['email'],
            'phone'       => $validated['phone'],
            'birth_date'  => $validated['birth_date'],
            'pin'         => $validated['pin'],
            'points'      => 0,
        ]);

        return view('visitor.loyalty.success', compact('card'));
    }

    /**
     * Formulaire de consultation du solde de points.
     */
    public function showBalanceForm()
    {
        return view('visitor.loyalty.balance');
    }

    /**
     * Affiche le solde de points après vérification du numéro de carte et du PIN.
     */
    public function balance(Request $request)
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:20'],
            'pin'         => ['required', 'string', 'max:6'],
        ]);

        $card = LoyaltyCard::where('card_number', $validated['card_number'])->first();

        if (!$card || !\Illuminate\Support\Facades\Hash::check($validated['pin'], $card->pin)) {
            return back()
                ->withInput($request->only('card_number'))
                ->withErrors(['card_number' => 'Numéro de carte ou code PIN incorrect.']);
        }

        return view('visitor.loyalty.balance', compact('card'));
    }
}
