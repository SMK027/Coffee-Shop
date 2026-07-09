<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderStatusController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $statuses = OrderStatus::orderBy('sort_order')->get();
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('employee.order-statuses.index', compact('statuses', 'isSuperAdmin'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.order-statuses.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $data = $this->validatePayload($request);
        OrderStatus::create($data);

        return redirect()->route('employee.order-statuses.index')
            ->with('success', 'Statut créé avec succès.');
    }

    public function edit(OrderStatus $orderStatus)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.order-statuses.edit', compact('orderStatus'));
    }

    public function update(Request $request, OrderStatus $orderStatus)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $data = $this->validatePayload($request, $orderStatus);
        $orderStatus->update($data);

        return redirect()->route('employee.order-statuses.index')
            ->with('success', 'Statut mis à jour.');
    }

    public function toggleActive(Request $request, OrderStatus $orderStatus)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $orderStatus->update(['is_active' => !$orderStatus->is_active]);

        $msg = $orderStatus->is_active ? 'Statut réactivé.' : 'Statut désactivé.';

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(Request $request, OrderStatus $orderStatus)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        if ($orderStatus->orders()->exists()) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer ce statut : des commandes y sont associées.');
        }

        $orderStatus->delete();

        return redirect()->route('employee.order-statuses.index')
            ->with('success', 'Statut supprimé.');
    }

    // -------------------------------------------------------------------------

    private function requireSuperAdmin(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Accès réservé aux super administrateurs.');
    }

    private function validatePayload(Request $request, ?OrderStatus $existing = null): array
    {
        return $request->validate([
            'key' => [
                'required', 'string', 'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('order_statuses', 'key')->ignore($existing?->id),
            ],
            'label'                   => ['required', 'string', 'max:100'],
            'color'                   => ['required', Rule::in(array_keys(OrderStatus::BADGE_CLASSES))],
            'sort_order'              => ['required', 'integer', 'min:0', 'max:9999'],
            'is_terminal'             => ['boolean'],
            'triggers_loyalty_credit' => ['boolean'],
        ], [
            'key.regex' => 'La clé ne peut contenir que des lettres minuscules, chiffres et underscores.',
        ]);
    }
}
