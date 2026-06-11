<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyCard;
use App\Models\LoyaltyPinReset;
use App\Models\Order;
use App\Services\CaptchaService;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    /**
     * Formulaire public de création d'une carte de fidélité.
     */
    public function create(Request $request, CaptchaService $captcha)
    {
        return view('visitor.loyalty.create', [
            'captchaQuestion' => $captcha->refreshChallenge($request, 'loyalty_create_form'),
        ]);
    }

    /**
     * Enregistre une nouvelle carte de fidélité.
     */
    public function store(Request $request, CaptchaService $captcha)
    {
        $maxBirthDate = now()->subYears(LoyaltyCard::MIN_AGE)->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', 'unique:loyalty_cards,email'],
            'phone'      => ['required', 'string', 'max:30', 'regex:/^[0-9 +().-]{6,30}$/'],
            'birth_date' => ['required', 'date', 'before_or_equal:' . $maxBirthDate],
            'pin'        => ['required', 'confirmed', 'digits_between:4,6'],
            'captcha'    => $captcha->validationRules($request, 'loyalty_create_form'),
        ], [
            'birth_date.before_or_equal' => 'Vous devez avoir au moins ' . LoyaltyCard::MIN_AGE . ' ans pour créer une carte de fidélité.',
            'pin.digits_between'         => 'Le code PIN doit contenir entre 4 et 6 chiffres.',
            'pin.confirmed'              => 'La confirmation du code PIN ne correspond pas.',
            'phone.regex'                => 'Le numéro de téléphone n\'est pas valide.',
        ]);

        unset($validated['captcha']);

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
    public function showBalanceForm(Request $request, CaptchaService $captcha)
    {
        return view('visitor.loyalty.balance', [
            'captchaQuestion' => $captcha->refreshChallenge($request, 'loyalty_balance_form'),
        ]);
    }

    /**
     * Affiche le solde de points après vérification du numéro de carte et du PIN.
     */
    public function balance(Request $request, CaptchaService $captcha)
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:20'],
            'pin'         => ['required', 'string', 'max:6'],
            'captcha'     => $captcha->validationRules($request, 'loyalty_balance_form'),
        ]);

        // Tolère les espaces saisis entre les groupes de chiffres de la carte.
        $cardNumber = str_replace(' ', '', $validated['card_number']);

        $card = LoyaltyCard::where('card_number', $cardNumber)->first();

        if (!$card || !\Illuminate\Support\Facades\Hash::check($validated['pin'], $card->pin)) {
            return back()
                ->withInput($request->only('card_number'))
                ->withErrors(['card_number' => 'Numéro de carte ou code PIN incorrect.']);
        }

        $card->load([
            'orders' => fn ($q) => $q->where('status', 'completed')->latest()->limit(5),
            'pointAdjustments' => fn ($q) => $q->latest(),
        ]);

        // Ouvre une session de consultation pour accéder au détail des commandes
        // de cette carte uniquement.
        $request->session()->put('loyalty_balance_card_id', $card->id);

        return view('visitor.loyalty.balance', compact('card'));
    }

    /**
     * Détail d'une commande depuis l'espace client fidélité.
     */
    public function showOrder(Request $request, Order $order)
    {
        $allowedCardId = (int) $request->session()->get('loyalty_balance_card_id');

        if (!$allowedCardId) {
            return redirect()->route('loyalty.balance.form')
                ->with('error', 'Veuillez d\'abord consulter votre carte pour accéder aux détails des commandes.');
        }

        if ((int) $order->loyalty_card_id !== $allowedCardId) {
            return redirect()->route('loyalty.balance.form')
                ->with('error', 'Cette commande n\'est pas accessible avec la carte actuellement consultée.');
        }

        $order->load(['items.drink']);

        return view('visitor.loyalty.order-show', compact('order'));
    }

    /**
     * Affiche le formulaire de définition d'un nouveau code PIN (lien email).
     */
    public function showPinResetForm(string $token)
    {
        $record = LoyaltyPinReset::where('token', hash('sha256', $token))
            ->with('loyaltyCard')
            ->first();

        if (!$record || !$record->isValid()) {
            return redirect()->route('loyalty.balance.form')
                ->with('error', 'Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau auprès de l\'équipe.');
        }

        return view('visitor.loyalty.reset-pin', [
            'token' => $token,
            'card'  => $record->loyaltyCard,
        ]);
    }

    /**
     * Enregistre le nouveau code PIN après vérification du token.
     */
    public function resetPin(Request $request, string $token)
    {
        $record = LoyaltyPinReset::where('token', hash('sha256', $token))
            ->with('loyaltyCard')
            ->first();

        if (!$record || !$record->isValid()) {
            return redirect()->route('loyalty.balance.form')
                ->with('error', 'Ce lien de réinitialisation est invalide ou a expiré. Demandez-en un nouveau auprès de l\'équipe.');
        }

        $request->validate([
            'pin' => ['required', 'confirmed', 'digits_between:4,6'],
        ], [
            'pin.digits_between' => 'Le code PIN doit contenir entre 4 et 6 chiffres.',
            'pin.confirmed'      => 'La confirmation du code PIN ne correspond pas.',
        ]);

        // Mise à jour du PIN (chiffré via le cast 'hashed' du modèle)
        $record->loyaltyCard->update(['pin' => $request->pin]);

        // Invalide le token (usage unique)
        $record->markAsUsed();

        return redirect()->route('loyalty.balance.form')
            ->with('success', 'Votre code PIN a été mis à jour. Vous pouvez désormais consulter vos points.');
    }
}
