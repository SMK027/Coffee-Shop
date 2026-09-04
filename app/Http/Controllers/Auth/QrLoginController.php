<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QrLoginController extends Controller
{
    /**
     * Affiche la page de connexion par QR code.
     */
    public function show(): View
    {
        return view('auth.login-qr');
    }

    /**
     * Étape 1-2 : identification du salarié à partir du QR code scanné,
     * avec vérification en base (compte actif, rôle autorisé).
     */
    public function identify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:200'],
        ]);

        $user = $this->resolveUserFromToken($validated['token']);

        if (! $user) {
            return response()->json([
                'message' => 'QR code invalide ou compte introuvable.',
            ], 422);
        }

        return response()->json([
            'name'     => $user->name,
            'username' => $user->username,
        ]);
    }

    /**
     * Étape 3 : authentification superviseur obligatoire puis connexion effective.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:200'],
        ]);

        $user = $this->resolveUserFromToken($validated['token']);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => 'QR code invalide ou compte introuvable.',
            ]);
        }

        // Authentification superviseur obligatoire, sans dérogation possible (mode permanent inapplicable ici).
        $this->requireStrictSupervisorValidation(
            $request,
            'Une authentification superviseur est requise pour se connecter par QR code.'
        );

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        ActivityLogger::log('auth.login_qr', 'Connexion par QR code réussie');

        return redirect()->intended(route('employee.dashboard'));
    }

    private function resolveUserFromToken(string $token): ?User
    {
        $user = User::fromLoginToken($token);

        if (! $user || ! $user->isActive()) {
            return null;
        }

        return $user;
    }
}
