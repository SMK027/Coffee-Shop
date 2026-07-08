<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Mail\LoyaltyPinResetMail;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyPinReset;
use App\Models\LoyaltyPointAdjustment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

        $cards = $query->paginate(20)->withQueryString();

        return view('employee.loyalty.index', compact('cards'));
    }

    /**
     * Détail d'une carte et de ses commandes.
     */
    public function show(LoyaltyCard $loyaltyCard)
    {
        $loyaltyCard->load([
            'orders' => fn ($q) => $q->latest(),
            'user',
            'pointAdjustments' => fn ($q) => $q->with('user')->latest(),
        ]);

        return view('employee.loyalty.show', compact('loyaltyCard'));
    }

    /**
     * Formulaire de création d'une carte (espace salarié).
     */
    public function create()
    {
        return view('employee.loyalty.create');
    }

    /**
     * Enregistre une nouvelle carte créée depuis l'espace salarié.
     * Pas de captcha, PIN saisi par l'employé au nom du client.
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
            'birth_date.before_or_equal' => 'Le titulaire doit avoir au moins ' . LoyaltyCard::MIN_AGE . ' ans.',
            'pin.digits_between'         => 'Le code PIN doit contenir entre 4 et 6 chiffres.',
            'pin.confirmed'              => 'La confirmation du code PIN ne correspond pas.',
            'phone.regex'                => 'Le numéro de téléphone n\'est pas valide.',
            'email.unique'               => 'Une carte de fidélité existe déjà avec cet email.',
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

        return redirect()
            ->route('employee.loyalty.show', $card)
            ->with('success', 'Carte de fidélité créée — n° ' . chunk_split($card->card_number, 4, ' '));
    }

    /**
     * Met à jour les informations du titulaire d'une carte de fidélité.
     */
    public function updateHolder(Request $request, LoyaltyCard $loyaltyCard)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:150', Rule::unique('loyalty_cards', 'email')->ignore($loyaltyCard->id)],
            'phone'      => ['required', 'string', 'max:30', 'regex:/^[0-9 +().-]{6,30}$/'],
        ], [
            'phone.regex' => 'Le numéro de téléphone n\'est pas valide.',
        ]);

        $loyaltyCard->update($validated);

        return back()->with('success', 'Les informations du titulaire ont été mises à jour.');
    }

    /**
     * Ajuste manuellement le solde de points d'une carte (crédit ou débit).
     * Chaque opération est tracée. Réservé aux super administrateurs.
     */
    public function adjustPoints(Request $request, LoyaltyCard $loyaltyCard)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'type'   => ['required', Rule::in([LoyaltyPointAdjustment::TYPE_CREDIT, LoyaltyPointAdjustment::TYPE_DEBIT])],
            'points' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'points.min' => 'Le nombre de points doit être d’au moins 1.',
        ]);

        $isCredit = $validated['type'] === LoyaltyPointAdjustment::TYPE_CREDIT;
        $delta    = $isCredit ? $validated['points'] : -$validated['points'];

        DB::transaction(function () use ($loyaltyCard, $delta, $isCredit, $validated) {
            // Calcul direct pour autoriser un solde négatif (colonne INT signée)
            $newBalance = $loyaltyCard->points + $delta;
            $loyaltyCard->update(['points' => $newBalance]);

            LoyaltyPointAdjustment::create([
                'loyalty_card_id' => $loyaltyCard->id,
                'user_id'         => auth()->id(),
                'type'            => $validated['type'],
                'points'          => $validated['points'],
                'balance_after'   => $newBalance,
                'reason'          => $validated['reason'] ?? null,
            ]);
        });

        $loyaltyCard->refresh();
        $verb = $isCredit ? 'crédités' : 'débités';

        return back()->with('success', "{$validated['points']} point(s) {$verb}. Nouveau solde : {$loyaltyCard->points} point(s).");
    }

    /**
     * Recherche d'salariés pour l'autocomplétion (rattachement carte ↔ salarié).
     * Réservé aux super administrateurs.
     */
    public function searchEmployees(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $search = $request->string('q')->trim()->value();

        $users = User::whereIn('global_role', ['superadmin', 'admin'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    /**
     * Active ou désactive les avantages salariés d'une carte en la rattachant
     * (ou non) à un compte salarié. Réservé aux super administrateurs.
     */
    public function updateEmployeeBenefits(Request $request, LoyaltyCard $loyaltyCard)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $hasBenefits = $request->boolean('employee_benefits');

        $validated = $request->validate([
            'user_id' => [Rule::requiredIf($hasBenefits), 'nullable', 'integer', 'exists:users,id'],
        ], [
            'user_id.required' => 'Veuillez sélectionner l\'salarié titulaire de la carte.',
        ]);

        if ($hasBenefits) {
            $loyaltyCard->update(['user_id' => $validated['user_id']]);

            $employee = User::find($validated['user_id']);

            return back()->with('success', "Avantages salariés activés : carte rattachée à {$employee->name}.");
        }

        $loyaltyCard->update(['user_id' => null]);

        return back()->with('success', 'Avantages salariés désactivés pour cette carte.');
    }

    /**
     * Formulaire de réglage du programme de fidélité (super admin uniquement).
     */
    public function settings()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('employee.loyalty.settings');
    }

    /**
     * Route conservée pour rétro-compat (le formulaire points_per_euro a été supprimé).
     */
    public function updateSettings(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return back()->with('success', 'Les réglages sont désormais gérés directement sur chaque boisson.');
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
