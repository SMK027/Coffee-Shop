<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

class SecurityKeyController extends Controller
{
    /**
     * Génère le défi WebAuthn permettant au salarié connecté d'enregistrer
     * une nouvelle clé de sécurité sur son propre compte.
     */
    public function options(Request $request, GenerateRegistrationOptions $generate): JsonResponse
    {
        $options = $generate(auth()->user());

        $request->session()->put('security_key.registration_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    /**
     * Valide et enregistre la clé de sécurité pour le salarié connecté, en
     * conservant l'adresse IP de l'appareil au moment de l'enregistrement.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credential' => ['required', 'string'],
        ]);

        try {
            $credential = WebAuthn::fromJson($validated['credential'], PublicKeyCredential::class);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'credential' => 'Clé de sécurité invalide ou requête corrompue.',
            ]);
        }

        $serializedOptions = $request->session()->pull('security_key.registration_options');
        if (! is_string($serializedOptions) || $serializedOptions === '') {
            throw ValidationException::withMessages([
                'credential' => "Session d'enregistrement expirée, veuillez réessayer.",
            ]);
        }

        $options = WebAuthn::fromJson($serializedOptions, PublicKeyCredentialCreationOptions::class);

        try {
            /** @var Passkey $passkey */
            $passkey = app(StorePasskey::class)(auth()->user(), $validated['name'], $credential, $options);
        } catch (InvalidPasskeyException $e) {
            throw ValidationException::withMessages([
                'credential' => $e->getMessage(),
            ]);
        }

        $passkey->forceFill(['registered_ip' => $request->ip()])->save();

        ActivityLogger::log(
            'security_key.registered',
            'Clé de sécurité enregistrée : ' . $passkey->name,
            'user',
            auth()->id(),
            ['name' => $passkey->name]
        );

        return back()->with('success', 'Clé de sécurité enregistrée avec succès.');
    }

    /**
     * Supprime une clé de sécurité appartenant au salarié connecté.
     */
    public function destroy(Passkey $passkey): RedirectResponse
    {
        abort_unless($passkey->user_id === auth()->id(), 403);

        $passkey->delete();

        ActivityLogger::log(
            'security_key.deleted',
            'Clé de sécurité supprimée : ' . $passkey->name,
            'user',
            auth()->id(),
            ['name' => $passkey->name]
        );

        return back()->with('success', 'Clé de sécurité supprimée.');
    }
}
