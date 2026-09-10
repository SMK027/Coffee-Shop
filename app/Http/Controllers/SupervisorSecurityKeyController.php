<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkeys;
use Laravel\Passkeys\Support\WebAuthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Génère le défi WebAuthn permettant à un superviseur d'approuver une
 * opération sensible avec sa clé de sécurité physique plutôt qu'avec son
 * PIN — utilisable partout où une validation superviseur est déjà exigée
 * (Controller::validateSupervisorCredentials), y compris avant authentification
 * de l'employé (ex. approbation de connexion par clé de sécurité).
 */
class SupervisorSecurityKeyController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $number = trim((string) $request->input('supervisor_number', ''));

        $supervisor = $number !== ''
            ? Supervisor::where('supervisor_number', $number)->where('is_active', true)->first()
            : null;

        // Aucune fuite d'information selon que le numéro existe ou non :
        // la réponse a toujours la même forme, avec une liste vide si le
        // superviseur est introuvable ou n'a aucune clé enregistrée.
        $allowCredentials = collect($supervisor?->securityKeys ?? [])
            ->map(fn ($key) => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Base64UrlSafe::decodeNoPadding($key->credential_id)
            ))
            ->all();

        $options = PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Passkeys::relyingPartyId(),
            allowCredentials: $allowCredentials,
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: Passkeys::timeout(),
        );

        $request->session()->put('supervisor_security_key.assertion_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }
}
