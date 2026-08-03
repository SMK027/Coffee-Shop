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
}
