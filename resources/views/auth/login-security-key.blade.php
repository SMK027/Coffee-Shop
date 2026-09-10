<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion par clé de sécurité | {{ config('app.name', 'Le Coffee Shop') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-amber-950 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <svg class="w-12 h-12 text-amber-400 mx-auto mb-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H14v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
            </svg>
            <h1 class="text-2xl font-bold text-white">Connexion par clé de sécurité</h1>
            <p class="text-amber-300 text-sm mt-1">{{ config('app.name') }}</p>
        </div>

        <div class="bg-white rounded-2xl p-8 shadow-2xl space-y-5" id="security-key-login-card">
            @if(session('error'))
                <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ session('error') }}</p>
            @endif
            @error('credential')<p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ $message }}</p>@enderror

            {{-- Étape 1 : présentation de la clé de sécurité personnelle --}}
            <div id="step-identify">
                <p class="text-sm font-semibold text-stone-800 mb-2">1. Présentez votre clé de sécurité</p>
                <button type="button" id="use-security-key"
                        class="w-full bg-amber-700 hover:bg-amber-600 text-white py-3 rounded-lg font-semibold text-sm transition-colors">
                    Utiliser ma clé de sécurité
                </button>
                <p id="identify-status" class="text-xs mt-2 text-stone-500"></p>
            </div>

            {{-- Étape 2 : authentification superviseur obligatoire --}}
            <form id="step-supervisor" action="{{ route('login.security-key.store') }}" method="POST" class="hidden space-y-4 border-t border-stone-100 pt-4">
                @csrf
                <input type="hidden" name="credential" id="employee-credential">
                <p class="text-sm font-semibold text-stone-800">2. Authentification superviseur obligatoire</p>

                @include('employee.shared.supervisor-qr-scanner', ['scannerId' => 'security-key-login-supervisor'])

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-stone-700 mb-1">Identifiant superviseur</label>
                        <input type="text" name="supervisor_number" value="{{ old('supervisor_number') }}"
                               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-700 mb-1">PIN superviseur</label>
                        <input type="password" name="supervisor_pin" maxlength="6" minlength="4" inputmode="numeric" pattern="\d{4,6}"
                               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                </div>
                @error('supervisor_pin')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror

                @include('partials.supervisor-security-key', ['widgetId' => 'login-supervisor-security-key'])

                <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white py-3 rounded-lg font-semibold text-sm transition-colors">
                    Se connecter
                </button>
            </form>
        </div>

        <div class="text-center mt-6 space-y-2">
            <a href="{{ route('login') }}" class="block text-amber-300 hover:text-amber-200 text-sm transition-colors">← Connexion par mot de passe</a>
            <a href="{{ route('home') }}" class="block text-amber-300 hover:text-amber-200 text-sm transition-colors">Retour au site</a>
        </div>
    </div>

    @include('partials.webauthn-helper')

    <script>
    (function () {
        const useKeyBtn = document.getElementById('use-security-key');
        const identifyStatus = document.getElementById('identify-status');
        const stepIdentify = document.getElementById('step-identify');
        const stepSupervisor = document.getElementById('step-supervisor');
        const employeeCredentialInput = document.getElementById('employee-credential');
        const optionsUrl = @json(route('login.security-key.options'));

        useKeyBtn.addEventListener('click', async () => {
            identifyStatus.textContent = 'Interrogation de la clé de sécurité...';
            identifyStatus.className = 'text-xs mt-2 text-stone-500';

            try {
                const response = await fetch(optionsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                    },
                });

                if (!response.ok) {
                    throw new Error('options request failed');
                }

                const optionsJson = await response.json();
                const credentialJson = await window.WebAuthnHelper.authenticate(optionsJson);

                employeeCredentialInput.value = JSON.stringify(credentialJson);
                stepIdentify.classList.add('hidden');
                stepSupervisor.classList.remove('hidden');
            } catch (error) {
                identifyStatus.textContent = 'Clé de sécurité non reconnue ou opération annulée.';
                identifyStatus.className = 'text-xs mt-2 text-red-600';
            }
        });
    })();
    </script>
</body>
</html>
