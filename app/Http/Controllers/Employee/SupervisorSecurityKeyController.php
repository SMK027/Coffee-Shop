<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use App\Models\SupervisorSecurityKey;
use App\Services\ActivityLogger;
use Cose\Algorithms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Enregistrement de clés de sécurité physiques pour un superviseur. Le PIN du
 * superviseur ciblé doit être saisi avant de démarrer la cérémonie WebAuthn :
 * cela prouve la connaissance du PIN sans jamais l'exposer côté client, et
 * autorise la liaison de la clé à ce superviseur précis.
 */
class SupervisorSecurityKeyController extends Controller
{
    private const PIN_VERIFIED_SESSION_KEY = 'supervisor_security_key.registration_supervisor_id';
    private const OPTIONS_SESSION_KEY = 'supervisor_security_key.registration_options';

    public function options(Request $request, Supervisor $supervisor): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'supervisor_pin' => ['required', 'string'],
        ]);

        if (! $supervisor->isActive() || ! Hash::check($validated['supervisor_pin'], $supervisor->password)) {
            // Réponse JSON explicite : cette route est appelée en fetch() depuis
            // le JS (jamais une navigation), et le rendu JSON automatique des
            // exceptions de l'appli est restreint aux chemins "api/*" (voir
            // bootstrap/app.php shouldRenderJsonWhen), ce qui ne couvre pas
            // les routes de l'espace employé.
            return response()->json([
                'message' => 'PIN superviseur incorrect.',
                'errors' => ['supervisor_pin' => ['PIN superviseur incorrect.']],
            ], 422);
        }

        $excludeCredentials = $supervisor->securityKeys->map(
            fn (SupervisorSecurityKey $key) => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decodeNoPadding($key->credential_id)
            )
        )->all();

        $options = PublicKeyCredentialCreationOptions::create(
            rp: PublicKeyCredentialRpEntity::create(
                name: Passkeys::relyingPartyName(),
                id: Passkeys::relyingPartyId(),
            ),
            user: PublicKeyCredentialUserEntity::create(
                name: 'superviseur-' . $supervisor->supervisor_number,
                id: hash('sha256', 'supervisor:' . $supervisor->id, true),
                displayName: 'Superviseur #' . $supervisor->supervisor_number,
            ),
            challenge: random_bytes(32),
            pubKeyCredParams: [
                PublicKeyCredentialParameters::create(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, Algorithms::COSE_ALGORITHM_ES256),
                PublicKeyCredentialParameters::create(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, Algorithms::COSE_ALGORITHM_RS256),
            ],
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $excludeCredentials,
            timeout: Passkeys::timeout(),
        );

        $request->session()->put(self::OPTIONS_SESSION_KEY, WebAuthn::toJson($options));
        $request->session()->put(self::PIN_VERIFIED_SESSION_KEY, $supervisor->id);

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }

    public function store(Request $request, Supervisor $supervisor): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'credential' => ['required', 'string'],
        ]);

        if ((int) $request->session()->get(self::PIN_VERIFIED_SESSION_KEY) !== $supervisor->id) {
            throw ValidationException::withMessages([
                'credential' => 'Validation du PIN superviseur requise avant enregistrement.',
            ]);
        }

        try {
            $credential = WebAuthn::fromJson(
                json_encode($validated['credential']) ?: '{}',
                PublicKeyCredential::class
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'credential' => 'Clé de sécurité invalide ou requête corrompue.',
            ]);
        }

        $serializedOptions = $request->session()->pull(self::OPTIONS_SESSION_KEY);
        $request->session()->forget(self::PIN_VERIFIED_SESSION_KEY);

        if (! is_string($serializedOptions) || $serializedOptions === '') {
            throw ValidationException::withMessages([
                'credential' => "Session d'enregistrement expirée, veuillez réessayer.",
            ]);
        }

        $options = WebAuthn::fromJson($serializedOptions, PublicKeyCredentialCreationOptions::class);

        $response = $credential->response;
        if (! $response instanceof AuthenticatorAttestationResponse) {
            throw ValidationException::withMessages([
                'credential' => 'Clé de sécurité invalide ou requête corrompue.',
            ]);
        }

        try {
            $source = WebAuthn::attestationValidator()->check(
                authenticatorAttestationResponse: $response,
                publicKeyCredentialCreationOptions: $options,
                host: Passkeys::relyingPartyId(),
            );
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'credential' => 'Impossible de valider cette clé de sécurité.',
            ]);
        }

        $credentialId = Base64UrlSafe::encodeUnpadded($source->publicKeyCredentialId);

        if (SupervisorSecurityKey::where('credential_id', $credentialId)->exists()) {
            throw ValidationException::withMessages([
                'credential' => 'Cette clé de sécurité est déjà enregistrée.',
            ]);
        }

        $securityKey = SupervisorSecurityKey::create([
            'supervisor_id' => $supervisor->id,
            'name' => $validated['name'] ?: null,
            'credential_id' => $credentialId,
            'credential' => json_decode(WebAuthn::toJson($source), true, flags: JSON_THROW_ON_ERROR),
            'registered_ip' => $request->ip(),
        ]);

        ActivityLogger::log(
            'supervisor.security_key_registered',
            'Clé de sécurité enregistrée pour le superviseur #' . $supervisor->supervisor_number,
            'supervisor',
            $supervisor->id,
            ['supervisor_number' => $supervisor->supervisor_number, 'name' => $securityKey->name]
        );

        return back()->with('success', 'Clé de sécurité enregistrée pour ce superviseur.');
    }

    public function destroy(Supervisor $supervisor, SupervisorSecurityKey $securityKey): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        abort_unless($securityKey->supervisor_id === $supervisor->id, 404);

        $securityKey->delete();

        ActivityLogger::log(
            'supervisor.security_key_deleted',
            'Clé de sécurité supprimée pour le superviseur #' . $supervisor->supervisor_number,
            'supervisor',
            $supervisor->id,
            ['supervisor_number' => $supervisor->supervisor_number]
        );

        return back()->with('success', 'Clé de sécurité supprimée.');
    }
}
