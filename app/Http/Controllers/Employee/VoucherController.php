<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyCard;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\Voucher;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', $request->query('filter', 'all'));
        $amountMin = $request->filled('amount_min') ? (float) $request->query('amount_min') : null;
        $amountMax = $request->filled('amount_max') ? (float) $request->query('amount_max') : null;
        $recipient = trim((string) $request->query('recipient', ''));
        $issuerId = $request->filled('issuer_id') ? (int) $request->query('issuer_id') : null;
        $expiresFrom = trim((string) $request->query('expires_from', ''));
        $expiresTo = trim((string) $request->query('expires_to', ''));

        if (! in_array($status, ['all', 'valid', 'used', 'expired'], true)) {
            $status = 'all';
        }

        $query = Voucher::with(['issuedBy', 'restrictedCard'])
            ->orderByDesc('issued_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('issued_by_name', 'like', "%{$search}%");
            });
        }

        if ($recipient !== '') {
            $recipientNormalized = str_replace(' ', '', $recipient);
            $query->where(function ($q) use ($recipient, $recipientNormalized) {
                $q->where('restricted_name', 'like', "%{$recipient}%")
                    ->orWhereHas('restrictedCard', function ($cardQuery) use ($recipient, $recipientNormalized) {
                        $cardQuery->where('card_number', 'like', "%{$recipient}%")
                            ->orWhereRaw("REPLACE(card_number, ' ', '') LIKE ?", ["%{$recipientNormalized}%"]);
                    });
            });
        }

        if ($issuerId !== null) {
            $query->where('issued_by', $issuerId);
        }

        if ($amountMin !== null) {
            $query->where('amount', '>=', $amountMin);
        }

        if ($amountMax !== null) {
            $query->where('amount', '<=', $amountMax);
        }

        if ($expiresFrom !== '') {
            $query->whereDate('expires_at', '>=', $expiresFrom);
        }

        if ($expiresTo !== '') {
            $query->whereDate('expires_at', '<=', $expiresTo);
        }

        $query->when($status === 'valid',   fn($q) => $q->valid())
              ->when($status === 'used',    fn($q) => $q->where('is_used', true))
              ->when($status === 'expired', fn($q) => $q->expired());

        $vouchers = $query->paginate(20)->withQueryString();

        $issuerIds = Voucher::query()
            ->whereNotNull('issued_by')
            ->distinct()
            ->pluck('issued_by')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $issuers = User::query()
            ->whereIn('id', $issuerIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('employee.vouchers.index', compact(
            'vouchers',
            'search',
            'status',
            'amountMin',
            'amountMax',
            'recipient',
            'issuerId',
            'issuers',
            'expiresFrom',
            'expiresTo'
        ));
    }

    public function show(Voucher $voucher)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $voucher->load('usedInOrder', 'restrictedCard');

        return view('employee.vouchers.show', compact('voucher'));
    }

    public function edit(Voucher $voucher)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($voucher->is_used, 403, 'Un bon déjà utilisé ne peut pas être modifié.');

        $voucher->load('restrictedCard');

        return view('employee.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_if($voucher->is_used, 403, 'Un bon déjà utilisé ne peut pas être modifié.');

        $validated = $request->validate([
            'amount'     => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'expires_at' => ['required', 'date', 'after:today'],
            'restriction_type' => ['required', 'in:none,card,name'],
        ]);

        // Résolution de la restriction
        $restrictedCardId = null;
        $restrictedName   = null;

        if ($validated['restriction_type'] === 'card') {
            $cardNumber = str_replace(' ', '', trim((string) $request->input('restricted_card_number', '')));
            if (empty($cardNumber)) {
                return back()->withInput()->withErrors(['restricted_card_number' => 'Le numéro de carte est requis.']);
            }
            $card = LoyaltyCard::where('card_number', $cardNumber)->first();
            if (! $card) {
                return back()->withInput()->withErrors(['restricted_card_number' => 'Aucune carte de fidélité ne correspond à ce numéro.']);
            }
            $restrictedCardId = $card->id;
        } elseif ($validated['restriction_type'] === 'name') {
            $name = trim((string) $request->input('restricted_name', ''));
            if (empty($name)) {
                return back()->withInput()->withErrors(['restricted_name' => 'Le nom complet est requis.']);
            }
            $restrictedName = $name;
        }

        $this->requireSuperAdminOrSupervisor($request);

        $changes = array_filter([
            'montant_avant'    => $voucher->amount != $validated['amount'] ? (float) $voucher->amount : null,
            'montant_après'    => $voucher->amount != $validated['amount'] ? round((float) $validated['amount'], 2) : null,
            'expiration_avant' => $voucher->expires_at->toDateString() !== Carbon::parse($validated['expires_at'])->toDateString() ? $voucher->expires_at->format('d/m/Y') : null,
            'expiration_après' => $voucher->expires_at->toDateString() !== Carbon::parse($validated['expires_at'])->toDateString() ? Carbon::parse($validated['expires_at'])->format('d/m/Y') : null,
        ]);

        $voucher->update([
            'amount'             => round((float) $validated['amount'], 2),
            'expires_at'         => $validated['expires_at'],
            'restricted_card_id' => $restrictedCardId,
            'restricted_name'    => $restrictedName,
        ]);

        ActivityLogger::log(
            'voucher.updated',
            "Bon d'achat {$voucher->code} modifié",
            'voucher', $voucher->id,
            $changes ?: ['code' => $voucher->code]
        );

        return redirect()
            ->route('employee.vouchers.show', $voucher)
            ->with('success', 'Bon d\'achat mis à jour avec succès.');
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.vouchers.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'validity_days'    => ['required', 'integer', 'min:3', 'max:31'],
            'restriction_type' => ['required', 'in:none,card,name'],
        ]);

        // Résolution de la restriction
        $restrictedCardId = null;
        $restrictedName   = null;

        if ($validated['restriction_type'] === 'card') {
            $cardNumber = str_replace(' ', '', trim((string) $request->input('restricted_card_number', '')));
            if (empty($cardNumber)) {
                return back()->withInput()->withErrors([
                    'restricted_card_number' => 'Le numéro de carte est requis.',
                ]);
            }
            $card = LoyaltyCard::where('card_number', $cardNumber)->first();
            if (! $card) {
                return back()->withInput()->withErrors([
                    'restricted_card_number' => 'Aucune carte de fidélité ne correspond à ce numéro.',
                ]);
            }
            $restrictedCardId = $card->id;
        } elseif ($validated['restriction_type'] === 'name') {
            $name = trim((string) $request->input('restricted_name', ''));
            if (empty($name)) {
                return back()->withInput()->withErrors([
                    'restricted_name' => 'Le nom complet est requis.',
                ]);
            }
            $restrictedName = $name;
        }

        $validatedSupervisor = $this->requireSuperAdminOrSupervisor($request);

        // Résolution du super administrateur associé au bon
        if (auth()->user()->isSuperAdmin()) {
            $superadminId   = auth()->id();
            $superadminName = auth()->user()->name;
        } else {
            if (! $validatedSupervisor?->superadmin) {
                return back()->withInput()->withErrors([
                    'supervisor_number' => 'Impossible de déterminer le super administrateur associé à ce superviseur.',
                ]);
            }

            $superadminId   = $validatedSupervisor->superadmin_id;
            $superadminName = $validatedSupervisor->superadmin->name;
        }

        $voucher = Voucher::create([
            'code'               => Voucher::generateCode(),
            'amount'             => round((float) $validated['amount'], 2),
            'issued_by'          => $superadminId,
            'issued_by_name'     => $superadminName,
            'issued_at'          => now(),
            'expires_at'         => now()->addDays((int) $validated['validity_days']),
            'restricted_card_id' => $restrictedCardId,
            'restricted_name'    => $restrictedName,
        ]);

        ActivityLogger::log(
            'voucher.created',
            "Bon d'achat {$voucher->code} créé — {$superadminName} ({$voucher->amount} €, expire le {$voucher->expires_at->format('d/m/Y')})",
            'voucher', $voucher->id,
            array_filter([
                'code'        => $voucher->code,
                'montant'     => (float) $voucher->amount,
                'expiration'  => $voucher->expires_at->format('d/m/Y'),
                'restriction' => $restrictedCardId ? "carte #{$restrictedCardId}" : ($restrictedName ?: null),
            ])
        );

        return redirect()
            ->route('employee.vouchers.index')
            ->with('success', "Bon d'achat créé avec succès.")
            ->with('new_voucher_code', $voucher->code);
    }

    /**
     * Vérifie un code bon d'achat (endpoint AJAX pour la création de commande).
     * Paramètres optionnels : loyalty_card_id, customer_name (pour valider la restriction).
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
            'valid'       => true,
            'code'        => $voucher->code,
            'amount'      => (float) $voucher->amount,
            'expires'     => $voucher->expires_at->format('d/m/Y'),
            'restricted'  => $voucher->restricted_card_id !== null || $voucher->restricted_name !== null,
            'message'     => "Bon d'achat valide",
        ]);
    }
}
