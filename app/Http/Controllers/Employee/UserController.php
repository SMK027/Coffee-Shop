<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Mail\EmployeePasswordResetMail;
use App\Models\EmployeePasswordReset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $search = trim((string) $request->query('q', ''));

        $roles = ['superadmin', 'admin'];

        $users = User::whereIn('global_role', $roles)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('global_role', 'like', "%{$search}%");
                });
            })
            ->orderBy('global_role')
            ->orderBy('name')
            ->get();

        return view('employee.users.index', compact('users'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('employee.users.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'global_role' => ['required', Rule::in(
                auth()->user()->isSuperAdmin()
                    ? ['admin', 'superadmin']
                    : ['admin']
            )],
        ], [
            'username.alpha_dash' => 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores.',
        ]);

        // Mot de passe aléatoire — l'utilisateur le définira via le lien envoyé par email
        $validated['password'] = Hash::make(Str::random(32));
        $validated['is_active'] = true;
        $validated['superadmin_id'] = null;

        $user = User::create($validated);

        // Génère un token de premier accès et envoie le mail d'invitation
        $plainToken = Str::random(64);

        EmployeePasswordReset::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', $plainToken),
            'created_at' => now(),
        ]);

        $resetUrl = route('employee.password.form', ['token' => $plainToken]);

        Mail::to($user->email)->send(new EmployeePasswordResetMail($user, $resetUrl, isNewAccount: true));

        return redirect()->route('employee.users.index')
            ->with('success', "Compte créé. Un email d'invitation a été envoyé à {$user->email}.");
    }

    public function edit(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        // Un admin ne peut pas modifier un superadmin (sauf lui-même si superadmin)
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        return view('employee.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'global_role' => ['required', Rule::in(
                auth()->user()->isSuperAdmin()
                    ? ['admin', 'superadmin']
                    : ['admin']
            )],
        ]);

        $validated['superadmin_id'] = null;

        $user->update($validated);

        return redirect()->route('employee.users.index')
            ->with('success', 'Compte mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        // Ne peut pas supprimer son propre compte ni un superadmin (sauf superadmin)
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('employee.users.index')
            ->with('success', 'Compte supprimé avec succès.');
    }

    public function toggleActivation(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas désactiver ou réactiver votre propre compte.');
        }

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Compte réactivé avec succès.' : 'Compte désactivé avec succès.');
    }

    /**
     * Génère un token de reset à usage unique (30 min) et envoie le lien par email.
     * Réservé aux super administrateurs.
     */
    public function sendResetLink(User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        // Invalide tous les tokens non utilisés précédents pour cet utilisateur
        EmployeePasswordReset::where('user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        // Génère un token brut de 64 caractères
        $plainToken = Str::random(64);

        EmployeePasswordReset::create([
            'user_id'    => $user->id,
            'token'      => hash('sha256', $plainToken),
            'created_at' => now(),
        ]);

        $resetUrl = route('employee.password.form', ['token' => $plainToken]);

        Mail::to($user->email)->send(new EmployeePasswordResetMail($user, $resetUrl));

        return back()->with('success', "Un lien de réinitialisation a été envoyé à {$user->email}. Il expire dans 30 minutes.");
    }
}
