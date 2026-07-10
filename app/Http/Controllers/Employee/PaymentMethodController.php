<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $methods = PaymentMethod::orderBy('sort_order')->get();
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('employee.payment-methods.index', compact('methods', 'isSuperAdmin'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.payment-methods.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'slug'       => ['required', 'string', 'max:100', 'unique:payment_methods,slug', 'regex:/^[a-z0-9-]+$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        PaymentMethod::create(array_merge($data, [
            'is_active'  => true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]));

        return redirect()->route('employee.payment-methods.index')
            ->with('success', 'Moyen de paiement créé avec succès.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'slug'       => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('payment_methods', 'slug')->ignore($paymentMethod->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $paymentMethod->update($data);

        return redirect()->route('employee.payment-methods.index')
            ->with('success', 'Moyen de paiement mis à jour.');
    }

    public function toggleActive(Request $request, PaymentMethod $paymentMethod)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $this->requireSuperAdminOrSupervisor($request);

        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        $msg = $paymentMethod->is_active ? 'Moyen de paiement activé.' : 'Moyen de paiement désactivé.';

        return redirect()->route('employee.payment-methods.index')->with('success', $msg);
    }
}
