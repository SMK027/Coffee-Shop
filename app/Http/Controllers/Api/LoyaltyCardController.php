<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardOffer;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyPointAdjustment;
use App\Models\Order;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class LoyaltyCardController extends Controller
{
    /**
     * Liste les cartes de fidélité avec pagination et recherche.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LoyaltyCard::query()->orderBy('last_name')->orderBy('first_name');

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('last_name',    'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('email',      'like', "%{$search}%")
                  ->orWhere('phone',      'like', "%{$search}%")
                  ->orWhere('card_number','like', "%{$search}%");
            });
        }

        $cards = $query->paginate(20);

        return response()->json([
            'data'         => $cards->map(fn(LoyaltyCard $c) => $this->formatCard($c)),
            'current_page' => $cards->currentPage(),
            'last_page'    => $cards->lastPage(),
            'total'        => $cards->total(),
        ]);
    }

    /**
     * Crée une nouvelle carte de fidélité.
     */
    public function store(Request $request): JsonResponse
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

        return response()->json([
            'message' => 'Carte de fidélité créée avec succès.',
            'card'    => $this->formatCard($card),
        ], 201);
    }

    /**
     * Vérifie l'existence d'une carte et retourne ses infos.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:20'],
        ]);

        $cardNumber = str_replace(' ', '', $validated['card_number']);
        $card = LoyaltyCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return response()->json(['found' => false, 'message' => 'Aucune carte ne correspond à ce numéro.']);
        }

        return response()->json([
            'found' => true,
            'card'  => $this->formatCard($card),
        ]);
    }

    /**
     * Fiche détaillée d'une carte : infos + commandes + historique de points.
     */
    public function show(LoyaltyCard $card): JsonResponse
    {
        $orders = $card->orders()
            ->with('items')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn(Order $o) => [
                'id'                      => $o->id,
                'status'                  => $o->status,
                'status_label'            => $o->status_label,
                'is_employee_order'       => (bool) $o->is_employee_order,
                'total_amount'            => (float) $o->total_amount,
                'discount_amount'         => (float) $o->discount_amount,
                'loyalty_discount_amount' => (float) $o->loyalty_discount_amount,
                'loyalty_points_spent'    => (int) $o->loyalty_points_spent,
                'points_awarded'          => (int) ($o->points_awarded ?? 0),
                'items_count'             => $o->items->sum('quantity'),
                'created_at'              => $o->created_at?->toIso8601String(),
                'completed_at'            => $o->completed_at?->toIso8601String(),
            ]);

        $adjustments = $card->pointAdjustments()
            ->with('user:id,name', 'order:id')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn(LoyaltyPointAdjustment $a) => [
                'id'            => $a->id,
                'type'          => $a->type,
                'source'        => $a->source,
                'points'        => (int) $a->points,
                'balance_after' => (int) $a->balance_after,
                'reason'        => $a->reason,
                'order_id'      => $a->order_id,
                'user_name'     => $a->user?->name,
                'created_at'    => $a->created_at?->toIso8601String(),
            ]);

        $totals = [
            'orders_count'    => $card->orders()->count(),
            'points_credited' => (int) $card->pointAdjustments()->where('type', LoyaltyPointAdjustment::TYPE_CREDIT)->sum('points'),
            'points_debited'  => (int) $card->pointAdjustments()->where('type', LoyaltyPointAdjustment::TYPE_DEBIT)->sum('points'),
        ];

        return response()->json([
            'card'        => $this->formatCard($card),
            'orders'      => $orders,
            'adjustments' => $adjustments,
            'totals'      => $totals,
        ]);
    }

    public function offers(Request $request, LoyaltyCard $card): JsonResponse
    {
        $includeAll = filter_var($request->query('all', false), FILTER_VALIDATE_BOOL);

        $query = $card->cardOffers()->latest();
        if (! $includeAll) {
            $query->where('is_used', false)
                ->whereNull('used_at')
                ->whereNull('used_in_order_id')
                ->where('expires_at', '>', now());
        }

        $offers = $query->get();

        return response()->json($offers->map(fn (CardOffer $offer) => [
            'id'              => $offer->id,
            'label'           => $offer->label,
            'discount_type'   => $offer->discount_type,
            'discount_value'  => (float) $offer->discount_value,
            'max_discount_amount' => $offer->max_discount_amount !== null ? (float) $offer->max_discount_amount : null,
            'display_value'   => $offer->display_value,
            'expires_at'      => $offer->expires_at?->toIso8601String(),
            'is_used'         => $offer->isUsedState(),
            'used_at'         => $offer->used_at?->toIso8601String(),
            'used_in_order_id'=> $offer->used_in_order_id,
            'is_valid'        => $offer->isValid(),
        ]));
    }

    public function storeOffer(Request $request, LoyaltyCard $card): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);

        $this->requireSuperAdminOrSupervisor($request);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', Rule::in([CardOffer::TYPE_FIXED, CardOffer::TYPE_PERCENT])],
            'discount_value' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        if ($validated['discount_type'] === CardOffer::TYPE_PERCENT && (float) $validated['discount_value'] > 100) {
            return response()->json(['message' => 'Un pourcentage ne peut pas dépasser 100 %.'], 422);
        }

        $offer = $card->cardOffers()->create([
            'label' => $validated['label'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => round((float) $validated['discount_value'], 2),
            'max_discount_amount' => isset($validated['max_discount_amount']) ? round((float) $validated['max_discount_amount'], 2) : null,
            'expires_at' => $validated['expires_at'],
            'issued_by' => auth()->id(),
        ]);

        ActivityLogger::log(
            'card_offer.created',
            "Offre « {$offer->label} » créée via mobile pour la carte {$card->card_number}",
            'card_offer',
            $offer->id,
            ['card' => $card->card_number, 'label' => $offer->label, 'valeur' => $offer->display_value]
        );

        return response()->json(['message' => 'Offre créée.', 'offer_id' => $offer->id], 201);
    }

    public function updateOffer(Request $request, LoyaltyCard $card, CardOffer $offer): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);
        abort_unless($offer->loyalty_card_id === $card->id, 404);
        abort_if($offer->isUsedState(), 403, 'Une offre déjà utilisée ne peut pas être modifiée.');

        $this->requireSuperAdminOrSupervisor($request);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'discount_type' => ['required', Rule::in([CardOffer::TYPE_FIXED, CardOffer::TYPE_PERCENT])],
            'discount_value' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0.01', 'max:9999.99'],
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        if ($validated['discount_type'] === CardOffer::TYPE_PERCENT && (float) $validated['discount_value'] > 100) {
            return response()->json(['message' => 'Un pourcentage ne peut pas dépasser 100 %.'], 422);
        }

        $offer->update([
            'label' => $validated['label'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => round((float) $validated['discount_value'], 2),
            'max_discount_amount' => isset($validated['max_discount_amount']) ? round((float) $validated['max_discount_amount'], 2) : null,
            'expires_at' => $validated['expires_at'],
        ]);

        ActivityLogger::log(
            'card_offer.updated',
            "Offre « {$offer->label} » modifiée via mobile pour la carte {$card->card_number}",
            'card_offer',
            $offer->id,
            ['card' => $card->card_number, 'label' => $offer->label, 'valeur' => $offer->display_value]
        );

        return response()->json(['message' => 'Offre mise à jour.']);
    }

    public function destroyOffer(Request $request, LoyaltyCard $card, CardOffer $offer): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);
        abort_unless($offer->loyalty_card_id === $card->id, 404);
        abort_if($offer->isUsedState(), 403, 'Une offre déjà utilisée ne peut pas être supprimée.');

        $this->requireSuperAdminOrSupervisor($request);

        ActivityLogger::log(
            'card_offer.deleted',
            "Offre « {$offer->label} » supprimée via mobile (carte {$card->card_number})",
            'card_offer',
            $offer->id,
            ['card' => $card->card_number, 'label' => $offer->label]
        );

        $offer->delete();

        return response()->json(['message' => 'Offre supprimée.']);
    }

    public function adjust(Request $request, LoyaltyCard $card): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->requireSuperAdminOrSupervisor($request, 'Action réservée aux super administrateurs ou à un superviseur valide.');

        $validated = $request->validate([
            'type'   => ['required', Rule::in([LoyaltyPointAdjustment::TYPE_CREDIT, LoyaltyPointAdjustment::TYPE_DEBIT])],
            'points' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'points.min' => 'Le nombre de points doit être d’au moins 1.',
        ]);

        $isCredit = $validated['type'] === LoyaltyPointAdjustment::TYPE_CREDIT;
        $delta = $isCredit ? $validated['points'] : -$validated['points'];

        $card->getConnection()->transaction(function () use ($card, $delta, $validated) {
            $newBalance = $card->points + $delta;
            $card->update(['points' => $newBalance]);

            LoyaltyPointAdjustment::create([
                'loyalty_card_id' => $card->id,
                'user_id'         => auth()->id(),
                'type'            => $validated['type'],
                'source'          => LoyaltyPointAdjustment::SOURCE_MANUAL,
                'points'          => $validated['points'],
                'balance_after'   => $newBalance,
                'reason'          => $validated['reason'] ?? null,
            ]);
        });

        $card->refresh();

        return response()->json([
            'message' => 'Solde de points mis à jour.',
            'card' => $this->formatCard($card),
        ]);
    }

    /**
     * Vérifie le code PIN d'une carte de fidélité.
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:20'],
            'pin'         => ['required', 'string', 'max:10'],
        ]);

        $cardNumber = str_replace(' ', '', $validated['card_number']);
        $card = LoyaltyCard::where('card_number', $cardNumber)->first();

        if (!$card) {
            return response()->json(['valid' => false, 'message' => 'Carte introuvable.']);
        }

        if (!Hash::check($validated['pin'], $card->pin)) {
            return response()->json(['valid' => false, 'message' => 'Code PIN incorrect.']);
        }

        return response()->json([
            'valid' => true,
            'card'  => $this->formatCard($card),
        ]);
    }

    private function formatCard(LoyaltyCard $card): array
    {
        return [
            'id'                    => $card->id,
            'card_number'           => $card->card_number,
            'full_name'             => $card->full_name,
            'first_name'            => $card->first_name,
            'last_name'             => $card->last_name,
            'email'                 => $card->email,
            'phone'                 => $card->phone,
            'points'                => (int) $card->points,
            'has_employee_benefits' => (bool) $card->hasEmployeeBenefits(),
        ];
    }
}
