<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupervisorController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $search = trim((string) $request->query('q', ''));
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $ownerIdForAdmin = $this->ownerReferenceId();

        $supervisors = Supervisor::with('superadmin:id,name')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('superadmin_id', $ownerIdForAdmin))
            ->when($search !== '', fn($query) => $query->where(function ($q) use ($search) {
                $q->where('supervisor_number', 'like', "%{$search}%")
                  ->orWhereHas('superadmin', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            }))
            // Les superviseurs du compte de référence apparaissent en premier
            ->orderByRaw('superadmin_id = ? DESC', [$ownerIdForAdmin])
            ->orderBy('supervisor_number')
            ->get();

        return view('employee.supervisors.index', compact('supervisors', 'search', 'isSuperAdmin', 'ownerIdForAdmin'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $superadmins = User::where('global_role', 'superadmin')
            ->orderBy('name')
            ->get(['id', 'name']);

        $isSuperAdmin = true;

        return view('employee.supervisors.create', compact('superadmins', 'isSuperAdmin'));
    }

    public function show(Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($this->canAccessSupervisor($supervisor), 403);

        $barcodeValue = $supervisor->barcodeValue();
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('employee.supervisors.show', compact('supervisor', 'barcodeValue', 'isSuperAdmin'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $validated = $request->validate([
            'supervisor_number' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:supervisors,supervisor_number'],
            'supervisor_pin'    => ['required', 'string', 'regex:/^\d{4,6}$/'],
            'superadmin_id'     => ['required', 'integer', 'exists:users,id'],
        ], [
            'supervisor_number.alpha_dash' => 'Le numéro de superviseur ne peut contenir que des lettres, chiffres, tirets et underscores.',
            'supervisor_pin.regex'         => 'Le PIN doit contenir entre 4 et 6 chiffres.',
            'superadmin_id.required'       => 'Le compte propriétaire est requis.',
            'superadmin_id.exists'         => 'Ce compte est invalide.',
        ]);

        // Vérifier que le compte désigné est bien un super-administrateur
        $owner = User::where('id', $validated['superadmin_id'])
            ->where('global_role', 'superadmin')
            ->firstOrFail();

        Supervisor::create([
            'supervisor_number' => $validated['supervisor_number'],
            'password'          => Hash::make($validated['supervisor_pin']),
            'superadmin_id'     => $owner->id,
        ]);

        return redirect()->route('employee.supervisors.index')
            ->with('success', 'Superviseur créé avec succès.');
    }

    public function edit(Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($this->canAccessSupervisor($supervisor), 403);

        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('employee.supervisors.edit', compact('supervisor', 'isSuperAdmin'));
    }

    public function update(Request $request, Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($this->canAccessSupervisor($supervisor), 403);

        $isSuperAdmin = auth()->user()->isSuperAdmin();

        if (! $isSuperAdmin) {
            $validated = $request->validate([
                'supervisor_pin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
            ], [
                'supervisor_pin.required' => 'Le PIN est requis pour la mise à jour.',
                'supervisor_pin.regex'    => 'Le PIN doit contenir entre 4 et 6 chiffres.',
            ]);

            $supervisor->password = Hash::make($validated['supervisor_pin']);
            $supervisor->save();

            return redirect()->route('employee.supervisors.index')
                ->with('success', 'Code PIN du superviseur mis à jour avec succès.');
        }

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
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($this->canAccessSupervisor($supervisor), 403);

        $supervisor->update(['is_active' => ! $supervisor->is_active]);

        return back()->with('success', $supervisor->is_active ? 'Superviseur réactivé.' : 'Superviseur désactivé.');
    }

    public function destroy(Supervisor $supervisor)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($this->canAccessSupervisor($supervisor), 403);

        if (! auth()->user()->isSuperAdmin()) {
            $validatedSupervisor = $this->requireSuperAdminOrSupervisor(
                request(),
                'La suppression exige la validation d\'un superviseur externe à votre compte.'
            );

            if ((int) $validatedSupervisor->superadmin_id === $this->ownerReferenceId()) {
                throw ValidationException::withMessages([
                    'supervisor_number' => "La suppression exige un superviseur non rattaché à votre compte.",
                ]);
            }
        }

        $supervisor->delete();

        return redirect()->route('employee.supervisors.index')
            ->with('success', 'Superviseur supprimé.');
    }

    private function ownerReferenceId(): int
    {
        $user = auth()->user();

        return (int) ($user->superadmin_id ?: $user->id);
    }

    private function canAccessSupervisor(Supervisor $supervisor): bool
    {
        return (int) $supervisor->superadmin_id === $this->ownerReferenceId();
    }
}
