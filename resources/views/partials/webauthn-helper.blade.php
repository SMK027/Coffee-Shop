<script>
window.WebAuthnHelper = (function () {
    function bufferToBase64url(buffer) {
        const bytes = new Uint8Array(buffer);
        let str = '';
        for (const b of bytes) str += String.fromCharCode(b);
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function base64urlToBuffer(base64url) {
        const padding = '='.repeat((4 - base64url.length % 4) % 4);
        const base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
        const str = atob(base64);
        const bytes = new Uint8Array(str.length);
        for (let i = 0; i < str.length; i++) bytes[i] = str.charCodeAt(i);
        return bytes.buffer;
    }

    function decodeCreationOptions(options) {
        options.challenge = base64urlToBuffer(options.challenge);
        options.user.id = base64urlToBuffer(options.user.id);
        if (options.excludeCredentials) {
            options.excludeCredentials = options.excludeCredentials.map((c) => Object.assign({}, c, { id: base64urlToBuffer(c.id) }));
        }
        return options;
    }

    function decodeRequestOptions(options) {
        options.challenge = base64urlToBuffer(options.challenge);
        if (options.allowCredentials) {
            options.allowCredentials = options.allowCredentials.map((c) => Object.assign({}, c, { id: base64urlToBuffer(c.id) }));
        }
        return options;
    }

    function attestationToJSON(credential) {
        return {
            id: credential.id,
            rawId: bufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                attestationObject: bufferToBase64url(credential.response.attestationObject),
            },
        };
    }

    function assertionToJSON(credential) {
        return {
            id: credential.id,
            rawId: bufferToBase64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                authenticatorData: bufferToBase64url(credential.response.authenticatorData),
                signature: bufferToBase64url(credential.response.signature),
                userHandle: credential.response.userHandle ? bufferToBase64url(credential.response.userHandle) : null,
            },
        };
    }

    /** Lance la cérémonie de création (enregistrement) à partir de la réponse JSON du serveur ({options: PublicKeyCredentialCreationOptions}). */
    async function register(optionsResponseJson) {
        const options = decodeCreationOptions(optionsResponseJson.options);
        const credential = await navigator.credentials.create({ publicKey: options });
        if (!credential) {
            throw new Error('Aucune clé de sécurité détectée.');
        }
        return attestationToJSON(credential);
    }

    /** Lance la cérémonie d'authentification à partir de la réponse JSON du serveur ({options: PublicKeyCredentialRequestOptions}). */
    async function authenticate(optionsResponseJson) {
        const options = decodeRequestOptions(optionsResponseJson.options);
        const credential = await navigator.credentials.get({ publicKey: options });
        if (!credential) {
            throw new Error('Aucune clé de sécurité détectée.');
        }
        return assertionToJSON(credential);
    }

    return { register, authenticate };
})();
</script>
