<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    /**
     * Vérifie la validité d'un bon d'achat.
     * Accepte les paramètres optionnels loyalty_card_id et customer_name
     * pour valider la restriction d'utilisation.
     */
    public function check(Request $request): JsonResponse
    {
        $code = strtoupper(str_replace(' ', '', trim((string) $request->query('code', ''))));

        if (empty($code)) {
            return response()->json(['valid' => false, 'message' => 'Code manquant.']);
        }

        $voucher = Voucher::where('code', $code)->first();

        if (! $voucher) {
            return response()->json(['valid' => false, 'message' => "Ce code ne correspond à aucun bon d'achat."]);
        }

        if ($voucher->is_used) {
            return response()->json(['valid' => false, 'message' => "Ce bon d'achat a déjà été utilisé."]);
        }

        if ($voucher->isExpired()) {
            return response()->json(['valid' => false, 'message' => "Ce bon d'achat est expiré."]);
        }

        // Vérification de la restriction si le contexte client est fourni
        if ($voucher->restricted_card_id !== null || $voucher->restricted_name !== null) {
            $loyaltyCardId = $request->filled('loyalty_card_id')
                ? (int) $request->query('loyalty_card_id')
                : null;
            $customerName = trim((string) $request->query('customer_name', '')) ?: null;

            if (! $voucher->isValidFor($loyaltyCardId, $customerName)) {
                return response()->json([
                    'valid'   => false,
                    'message' => "Ce bon d'achat est réservé à un autre client.",
                ]);
            }
        }

        return response()->json([
            'valid'   => true,
            'code'    => $voucher->code,
            'amount'  => (float) $voucher->amount,
            'expires' => $voucher->expires_at->format('d/m/Y'),
            'message' => "Bon d'achat valide",
        ]);
    }

    /**
     * Création d'un bon d'achat via l'API mobile.
     */
    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'validity_days' => ['required', 'integer', 'min:3', 'max:365'],
            'restriction_type' => ['required', 'in:none,card,name'],
        ]);

        $restrictedCardId = null;
        $restrictedName = null;

        if ($validated['restriction_type'] === 'card') {
            $cardNumber = str_replace(' ', '', trim((string) $request->input('restricted_card_number', '')));
            if (empty($cardNumber)) {
                return response()->json(['message' => 'Numéro de carte requis.'], 422);
            }
            $card = \App\Models\LoyaltyCard::where('card_number', $cardNumber)->first();
            if (! $card) {
                return response()->json(['message' => 'Aucune carte de fidélité correspondante.'], 422);
            }
            $restrictedCardId = $card->id;
        } elseif ($validated['restriction_type'] === 'name') {
            $name = trim((string) $request->input('restricted_name', ''));
            if (empty($name)) {
                return response()->json(['message' => 'Nom complet requis.'], 422);
            }
            $restrictedName = $name;
        }

        // Superadmin handling or supervisor validation for regular admins/moderators
        if (auth()->user()->isSuperAdmin()) {
            $superadminId = auth()->id();
            $superadminName = auth()->user()->name;
        } else {
            // require supervisor credentials when not superadmin
            $supervisorNumber = trim((string) $request->input('supervisor_number', ''));
            $supervisorPin = trim((string) $request->input('supervisor_pin', ''));
            if (! $supervisorNumber || ! $supervisorPin) {
                return response()->json(['message' => 'Validation du superviseur requise.'], 422);
            }
            $supervisor = \App\Models\Supervisor::where('supervisor_number', $supervisorNumber)
                ->where('is_active', true)
                ->with('superadmin:id,name')
                ->first();
            if (! $supervisor?->superadmin) {
                return response()->json(['message' => 'Superviseur invalide.'], 422);
            }
            $superadminId = $supervisor->superadmin_id;
            $superadminName = $supervisor->superadmin->name;
        }

        $voucher = Voucher::create([
            'code' => Voucher::generateCode(),
            'amount' => round((float) $validated['amount'], 2),
            'issued_by' => $superadminId,
            'issued_by_name' => $superadminName,
            'issued_at' => now(),
            'expires_at' => now()->addDays((int) $validated['validity_days']),
            'restricted_card_id' => $restrictedCardId,
            'restricted_name' => $restrictedName,
        ]);

        \App\Services\ActivityLogger::log(
            'voucher.created',
            "Bon d'achat {$voucher->code} créé via mobile",
            'voucher', $voucher->id,
            ['code' => $voucher->code, 'amount' => (float) $voucher->amount]
        );

        return response()->json([
            'message' => 'Bon d\'achat créé',
            'code' => $voucher->code,
            'amount' => (float) $voucher->amount,
            'expires_at' => $voucher->expires_at->toDateString(),
        ]);
    }

    /**
     * Retourne la liste des bons d'achat pour l'API mobile.
     */
    public function index(): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);

        $vouchers = Voucher::orderBy('created_at', 'desc')->get()->map(function ($v) {
            return [
                'id' => $v->id,
                'code' => $v->code,
                'amount' => (float) $v->amount,
                // 'active' is computed from DB: not used and not expired
                'active' => $v->isValid(),
                'is_used' => (bool) $v->is_used,
                'issued_at' => $v->issued_at?->toDateTimeString(),
                'expires_at' => $v->expires_at?->toDateString(),
            ];
        });

        return response()->json($vouchers);
    }

    /**
     * Affiche un bon d'achat.
     */
    public function show(Voucher $voucher): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);

        return response()->json([
            'id' => $voucher->id,
            'code' => $voucher->code,
            'amount' => (float) $voucher->amount,
            'active' => $voucher->isValid(),
            'is_used' => (bool) $voucher->is_used,
            'issued_at' => $voucher->issued_at?->toDateTimeString(),
            'issued_by' => $voucher->issued_by_name,
            'expires_at' => $voucher->expires_at?->toDateString(),
            'restricted_card_id' => $voucher->restricted_card_id,
            'restricted_name' => $voucher->restricted_name,
        ]);
    }

    /**
     * Met à jour certains champs d'un bon (ex: activation/désactivation).
     */
    /**
     * Supprime un bon d'achat.
     */
    public function destroy(Voucher $voucher): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin() || auth()->user()?->isModerator(), 403);

        $voucher->delete();

        return response()->json(['message' => 'Bon supprimé']);
    }
}
