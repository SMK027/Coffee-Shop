<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class SecurityKeyLoginController extends Controller
{
    /**
     * Affiche la page de connexion par clé de sécurité.
     */
    public function show(): View
    {
        return view('auth.login-security-key');
    }

    /**
     * Génère le défi WebAuthn pour une connexion sans identification préalable
     * (clé découvrable) : l'appareil de l'employé propose directement les
     * identifiants disponibles pour ce site.
     */
    public function options(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();

        $request->session()->put('security_key.verification_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * Vérifie la clé de sécurité (elle identifie et authentifie le salarié en
     * une seule étape), exige une validation superviseur (habilitation
     * "connexion QR / clé de sécurité"), puis connecte le salarié.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string'],
        ]);

        try {
            $credential = WebAuthn::fromJson($validated['credential'], PublicKeyCredential::class);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'credential' => 'Clé de sécurité invalide ou requête corrompue.',
            ]);
        }

        $serializedOptions = $request->session()->pull('security_key.verification_options');
        if (! is_string($serializedOptions) || $serializedOptions === '') {
            throw ValidationException::withMessages([
                'credential' => 'Session de connexion expirée, veuillez réessayer.',
            ]);
        }

        $options = WebAuthn::fromJson($serializedOptions, PublicKeyCredentialRequestOptions::class);

        try {
            /** @var Passkey $passkey */
            $passkey = app(VerifyPasskey::class)($credential, $options);
        } catch (InvalidPasskeyException $e) {
            throw ValidationException::withMessages([
                'credential' => $e->getMessage(),
            ]);
        }

        // Par mesure de sécurité, l'adresse IP de l'appareil doit correspondre
        // à celle enregistrée lors de l'enregistrement de la clé.
        if ($passkey->registered_ip !== null && $passkey->registered_ip !== $request->ip()) {
            ActivityLogger::log(
                'auth.security_key_ip_mismatch',
                'Connexion par clé de sécurité refusée : adresse IP différente de celle enregistrée',
                'user',
                $passkey->user_id,
                ['registered_ip' => $passkey->registered_ip, 'current_ip' => $request->ip()]
            );

            abort(403, "L'adresse IP de cet appareil ne correspond pas à celle enregistrée pour cette clé de sécurité.");
        }

        $user = $passkey->user;

        if (! $user || ! $user->isActive() || (! $user->isAdmin() && ! $user->isModerator())) {
            throw ValidationException::withMessages([
                'credential' => 'Ce compte ne peut pas se connecter par clé de sécurité.',
            ]);
        }

        // Authentification superviseur obligatoire, immédiate (pas de différé) :
        // l'employé n'est pas encore connecté à ce stade, or l'écran de validation
        // différée (employee.supervision.challenge) est protégé par le middleware
        // auth+employee et donc inaccessible tant que la connexion n'a pas abouti.
        $this->requireStrictSupervisorValidation(
            $request,
            'Une authentification superviseur est requise pour se connecter par clé de sécurité.',
            allowDeferred: false
        );

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        ActivityLogger::log('auth.login_security_key', 'Connexion par clé de sécurité réussie');

        return redirect()->intended(route('employee.dashboard'));
    }
}
