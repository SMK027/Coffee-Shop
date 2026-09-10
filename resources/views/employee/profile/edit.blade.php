<x-employee-layout title="Mon profil">

    <div class="max-w-2xl space-y-5">

        {{-- Informations personnelles --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Informations personnelles</h2>
            <p class="text-sm text-stone-500 mb-5">Mettez à jour votre nom et votre adresse e-mail.</p>

            <form method="POST" action="{{ route('employee.profile.update') }}" class="space-y-4">
                @csrf @method('PATCH')

                <div>
                    <x-input-label for="name" value="Nom affiché *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                        :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" value="Adresse e-mail *" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                        :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-1" :messages="$errors->get('email')" />
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Enregistrer
                    </button>
                    @if(session('status') === 'profile-updated')
                        <p class="text-sm text-green-600">Profil mis à jour.</p>
                    @endif
                </div>
            </form>
        </div>

        {{-- Mot de passe --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Mot de passe</h2>
            <p class="text-sm text-stone-500 mb-5">Utilisez un mot de passe long et aléatoire pour sécuriser votre compte.</p>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf @method('PUT')

                <div>
                    <x-input-label for="current_password" value="Mot de passe actuel *" />
                    <x-text-input id="current_password" name="current_password" type="password"
                        class="mt-1 block w-full" autocomplete="current-password" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password" value="Nouveau mot de passe *" />
                    <x-text-input id="password" name="password" type="password"
                        class="mt-1 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirmer le mot de passe *" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                        class="mt-1 block w-full" autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Changer le mot de passe
                    </button>
                    @if(session('status') === 'password-updated')
                        <p class="text-sm text-green-600">Mot de passe mis à jour.</p>
                    @endif
                </div>
            </form>
        </div>

        @if(\App\Models\Setting::isFeatureEnabled(\App\Models\Setting::KEY_FEATURE_SECURITY_KEYS))
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6" id="security-keys">
                <h2 class="font-semibold text-stone-800 mb-1">Clés de sécurité</h2>
                <p class="text-sm text-stone-500 mb-5">Connectez-vous sans mot de passe grâce à une clé de sécurité physique (ex. clé USB/NFC compatible FIDO2).</p>

                @if(session('success'))
                    <p class="text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg px-4 py-3 mb-4">{{ session('success') }}</p>
                @endif
                @error('credential')<p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4">{{ $message }}</p>@enderror

                @if($user->passkeys->isNotEmpty())
                    <ul class="divide-y divide-stone-100 mb-5">
                        @foreach($user->passkeys as $passkey)
                            <li class="py-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-stone-800">{{ $passkey->name }}</p>
                                    <p class="text-xs text-stone-400">
                                        Enregistrée le {{ $passkey->created_at->format('d/m/Y') }}
                                        @if($passkey->last_used_at)
                                            · Dernière utilisation le {{ $passkey->last_used_at->format('d/m/Y à H:i') }}
                                        @endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('employee.profile.security-keys.destroy', $passkey) }}" onsubmit="return confirm('Supprimer cette clé de sécurité ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Supprimer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-stone-400 mb-5">Aucune clé de sécurité enregistrée.</p>
                @endif

                <form method="POST" action="{{ route('employee.profile.security-keys.store') }}" id="add-security-key-form" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <input type="hidden" name="credential" id="new-security-key-credential">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="security_key_name" value="Nom de la clé" />
                        <x-text-input id="security_key_name" name="name" type="text" class="mt-1 block w-full" placeholder="Ex : Clé YubiKey bureau" />
                    </div>
                    <button type="button" id="register-security-key"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Enregistrer cette clé
                    </button>
                </form>
                <p id="register-security-key-status" class="text-xs text-stone-500 mt-2"></p>
            </div>

            @include('partials.webauthn-helper')
            <script>
            (function () {
                const nameInput = document.getElementById('security_key_name');
                const credentialInput = document.getElementById('new-security-key-credential');
                const form = document.getElementById('add-security-key-form');
                const registerBtn = document.getElementById('register-security-key');
                const status = document.getElementById('register-security-key-status');
                const optionsUrl = @json(route('employee.profile.security-keys.options'));

                registerBtn.addEventListener('click', async () => {
                    if (!nameInput.value.trim()) {
                        status.textContent = 'Donnez un nom à cette clé avant de continuer.';
                        status.className = 'text-xs mt-2 text-red-600';
                        return;
                    }

                    status.textContent = 'Suivez les instructions de votre navigateur...';
                    status.className = 'text-xs mt-2 text-stone-500';

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
                        const credentialJson = await window.WebAuthnHelper.register(optionsJson);

                        credentialInput.value = JSON.stringify(credentialJson);
                        form.submit();
                    } catch (error) {
                        status.textContent = "Impossible d'enregistrer cette clé de sécurité (annulé ou non compatible).";
                        status.className = 'text-xs mt-2 text-red-600';
                    }
                });
            })();
            </script>
        @endif

        @if($user->isSuperAdmin())
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-stone-800">Supervision</h2>
                        <p class="text-sm text-stone-500 mt-1">Gérez le mode superviseur permanent de cette session.</p>
                    </div>
                    <a href="{{ route('employee.supervision.permanent') }}"
                       class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Gérer le mode
                    </a>
                </div>
            </div>
        @endif

    </div>

</x-employee-layout>
