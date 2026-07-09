<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SupervisorController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $search = trim((string) $request->query('q', ''));

        $supervisors = Supervisor::where('superadmin_id', auth()->id())
            ->when($search !== '', fn($query) => $query->where(function ($query) use ($search) {
                $query->where('supervisor_number', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->get();

        return view('employee.supervisors.index', compact('supervisors', 'search'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('employee.supervisors.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'supervisor_number' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:supervisors,supervisor_number'],
            'supervisor_pin'    => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ], [
            'supervisor_number.alpha_dash' => 'Le numéro de superviseur ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'supervisor_pin.regex'         => 'Le PIN doit contenir entre 4 et 6 chiffres.',
        ]);

        Supervisor::create([
            'supervisor_number' => $validated['supervisor_number'],
            'password'          => Hash::make($validated['supervisor_pin']),
            'superadmin_id'     => auth()->id(),
        ]);

        return redirect()->route('employee.supervisors.index')
            ->with('success', 'Superviseur créé avec succès.');
    }

    public function edit(Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($supervisor->superadmin_id === auth()->id(), 403);

        return view('employee.supervisors.edit', compact('supervisor'));
    }

    public function update(Request $request, Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($supervisor->superadmin_id === auth()->id(), 403);

        $validated = $request->validate([
            'supervisor_number' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('supervisors', 'supervisor_number')->ignore($supervisor->id)],
            'supervisor_pin'    => ['nullable', 'string', 'regex:/^\d{4,6}$/'],
            'is_active'         => ['required', 'boolean'],
        ], [
            'supervisor_number.alpha_dash' => 'Le numéro de superviseur ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'supervisor_pin.regex'         => 'Le PIN doit contenir entre 4 et 6 chiffres.',
        ]);

        $supervisor->supervisor_number = $validated['supervisor_number'];
        $supervisor->is_active = $validated['is_active'];

        if (! empty($validated['supervisor_pin'])) {
            $supervisor->password = Hash::make($validated['supervisor_pin']);
        }

        $supervisor->save();

        return redirect()->route('employee.supervisors.index')
            ->with('success', 'Superviseur mis à jour avec succès.');
    }

    public function toggleActivation(Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($supervisor->superadmin_id === auth()->id(), 403);

        $supervisor->update(['is_active' => ! $supervisor->is_active]);

        return back()->with('success', $supervisor->is_active ? 'Superviseur réactivé.' : 'Superviseur désactivé.');
    }

    public function destroy(Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($supervisor->superadmin_id === auth()->id(), 403);

        $supervisor->delete();

        return redirect()->route('employee.supervisors.index')
            ->with('success', 'Superviseur supprimé.');
    }
}
