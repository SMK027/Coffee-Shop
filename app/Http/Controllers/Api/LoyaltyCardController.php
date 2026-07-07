<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
