<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use App\Models\Voucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $search = trim((string) $request->query('q', ''));
        $filter = $request->query('filter', 'all'); // all | valid | used | expired

        $query = Voucher::with('issuedBy')
            ->orderByDesc('issued_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('issued_by_name', 'like', "%{$search}%");
            });
        }

        $query->when($filter === 'valid',   fn($q) => $q->valid())
              ->when($filter === 'used',    fn($q) => $q->where('is_used', true))
              ->when($filter === 'expired', fn($q) => $q->expired());

        $vouchers = $query->paginate(20)->withQueryString();

        return view('employee.vouchers.index', compact('vouchers', 'search', 'filter'));
    }

    public function show(Voucher $voucher)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $voucher->load('usedInOrder');

        return view('employee.vouchers.show', compact('voucher'));
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
            'amount'        => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'validity_days' => ['required', 'integer', 'min:3', 'max:31'],
        ]);

        // Résolution du super administrateur associé au bon
        if (auth()->user()->isSuperAdmin()) {
            $superadminId   = auth()->id();
            $superadminName = auth()->user()->name;
        } else {
            // Validation superviseur : lance une ValidationException en cas d'échec
            $this->requireSuperAdminOrSupervisor($request);

            // Après validation réussie, retrouver le superviseur pour obtenir son superadmin
            $supervisorNumber = trim((string) $request->input('supervisor_number', ''));
            $supervisor = Supervisor::where('supervisor_number', $supervisorNumber)
                ->where('is_active', true)
                ->with('superadmin:id,name')
                ->first();

            if (! $supervisor?->superadmin) {
                return back()->withInput()->withErrors([
                    'supervisor_number' => 'Impossible de déterminer le super administrateur associé à ce superviseur.',
                ]);
            }

            $superadminId   = $supervisor->superadmin_id;
            $superadminName = $supervisor->superadmin->name;
        }

        $voucher = Voucher::create([
            'code'           => Voucher::generateCode(),
            'amount'         => round((float) $validated['amount'], 2),
            'issued_by'      => $superadminId,
            'issued_by_name' => $superadminName,
            'issued_at'      => now(),
            'expires_at'     => now()->addDays((int) $validated['validity_days']),
        ]);

        return redirect()
            ->route('employee.vouchers.index')
            ->with('success', "Bon d'achat créé avec succès.")
            ->with('new_voucher_code', $voucher->code);
    }

    /**
     * Vérifie un code bon d'achat (endpoint AJAX pour la création de commande).
     */
    public function check(Request $request): JsonResponse
    {
        $code = strtoupper(str_replace(' ', '', trim((string) $request->query('code', ''))));

        if (empty($code)) {
            return response()->json(['valid' => false, 'message' => 'Code manquant.']);
        }

        $voucher = Voucher::where('code', $code)->first();

        if (! $voucher) {
            return response()->json(['valid' => false, 'message' => 'Ce code ne correspond à aucun bon d\'achat.']);
        }

        if ($voucher->is_used) {
            return response()->json(['valid' => false, 'message' => 'Ce bon d\'achat a déjà été utilisé.']);
        }

        if ($voucher->isExpired()) {
            return response()->json(['valid' => false, 'message' => 'Ce bon d\'achat est expiré.']);
        }

        return response()->json([
            'valid'   => true,
            'code'    => $voucher->code,
            'amount'  => (float) $voucher->amount,
            'expires' => $voucher->expires_at->format('d/m/Y'),
            'message' => 'Bon d\'achat valide',
        ]);
    }
}
