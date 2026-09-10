<x-employee-layout title="Détails du superviseur" subtitle="{{ $supervisor->supervisor_number }}">
    <div class="mb-4">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </div>

    <div class="max-w-2xl bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-5">
        <div>
            <h2 class="text-lg font-semibold text-stone-800">Informations</h2>
            <p class="text-sm text-stone-500 mt-1">Utilisez ce QR code pour un bypass superviseur depuis l'application mobile.</p>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-stone-500">Identifiant superviseur</dt>
                <dd class="font-mono text-stone-800 mt-1">{{ $supervisor->supervisor_number }}</dd>
            </div>
            <div>
                <dt class="text-stone-500">Responsable</dt>
                <dd class="text-stone-800 mt-1">{{ $supervisor->superadmin?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500">Détenteur</dt>
                <dd class="text-stone-800 mt-1">{{ $supervisor->holderAdmin?->name ?? 'Non défini' }}</dd>
            </div>
            <div>
                <dt class="text-stone-500">Statut</dt>
                <dd class="mt-1">
                    @if($supervisor->is_active)
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-700">Actif</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-stone-100 text-stone-600">Désactivé</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div>
            <h3 class="text-sm font-semibold text-stone-700 mb-2">Habilitations</h3>
            @if(empty($supervisor->permissions))
                <p class="text-sm text-stone-500 italic">Aucune — ce superviseur ne peut débloquer aucune opération sensible.</p>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach($supervisor->permissions as $permission)
                        <li class="inline-flex px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 text-xs border border-amber-200">
                            {{ \App\Support\SupervisorOperation::label($permission) }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="border border-stone-200 rounded-lg p-4 bg-stone-50">
            <p class="text-sm text-stone-600 mb-3">QR code de bypass superviseur</p>
            <div class="bg-white border border-stone-200 rounded-lg p-3 overflow-x-auto flex justify-center">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data={{ urlencode($barcodeValue) }}"
                    alt="QR code superviseur"
                    width="220"
                    height="220"
                    class="block"
                >
            </div>
            <p class="text-xs text-stone-500 mt-3">Format court signé pour un affichage compact et un scan mobile plus fiable.</p>
        </div>

        @if(\App\Models\Setting::isFeatureEnabled(\App\Models\Setting::KEY_FEATURE_SECURITY_KEYS))
            <div class="border border-stone-200 rounded-lg p-4 bg-stone-50 space-y-4" id="supervisor-security-keys">
                <div>
                    <h3 class="text-sm font-semibold text-stone-700">Clés de sécurité</h3>
                    <p class="text-xs text-stone-500 mt-1">Permet d'approuver une opération sensible avec une clé physique plutôt qu'avec le PIN.</p>
                </div>

                @if($supervisor->securityKeys->isNotEmpty())
                    <ul class="divide-y divide-stone-200 bg-white rounded-lg border border-stone-200">
                        @foreach($supervisor->securityKeys as $securityKey)
                            <li class="p-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-stone-800">{{ $securityKey->name ?: 'Clé sans nom' }}</p>
                                    <p class="text-xs text-stone-400">Enregistrée le {{ $securityKey->created_at->format('d/m/Y') }}</p>
                                </div>
                                <form action="{{ route('employee.supervisors.security-keys.destroy', [$supervisor, $securityKey]) }}" method="POST" onsubmit="return confirm('Supprimer cette clé de sécurité ?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Supprimer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs text-stone-400 italic">Aucune clé de sécurité enregistrée pour ce superviseur.</p>
                @endif

                @error('credential')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('supervisor_pin')<p class="text-xs text-red-600">{{ $message }}</p>@enderror

                <form method="POST" action="{{ route('employee.supervisors.security-keys.store', $supervisor) }}" id="register-supervisor-key-form" class="grid sm:grid-cols-3 gap-3 items-end">
                    @csrf
                    <input type="hidden" name="credential" id="new-supervisor-security-key-credential">
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-1">Nom de la clé (optionnel)</label>
                        <input type="text" id="supervisor_security_key_name_input" name="name"
                               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-stone-600 mb-1">PIN du superviseur</label>
                        <input type="password" id="supervisor_security_key_pin_input" maxlength="6" minlength="4" inputmode="numeric" pattern="\d{4,6}"
                               class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <button type="button" id="register-supervisor-security-key"
                            class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Enregistrer une clé
                    </button>
                </form>
                <p id="register-supervisor-security-key-status" class="text-xs text-stone-500"></p>
            </div>

            @include('partials.webauthn-helper')
            <script>
            (function () {
                const pinInput = document.getElementById('supervisor_security_key_pin_input');
                const nameInput = document.getElementById('supervisor_security_key_name_input');
                const credentialInput = document.getElementById('new-supervisor-security-key-credential');
                const form = document.getElementById('register-supervisor-key-form');
                const registerBtn = document.getElementById('register-supervisor-security-key');
                const status = document.getElementById('register-supervisor-security-key-status');
                const optionsUrl = @json(route('employee.supervisors.security-keys.options', $supervisor));

                registerBtn.addEventListener('click', async () => {
                    const pin = pinInput.value.trim();
                    if (!/^\d{4,6}$/.test(pin)) {
                        status.textContent = 'Saisissez le PIN du superviseur (4 à 6 chiffres).';
                        status.className = 'text-xs text-red-600';
                        return;
                    }

                    status.textContent = 'Vérification du PIN...';
                    status.className = 'text-xs text-stone-500';

                    try {
                        const response = await fetch(optionsUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({ supervisor_pin: pin }),
                        });

                        if (!response.ok) {
                            status.textContent = 'PIN superviseur incorrect.';
                            status.className = 'text-xs text-red-600';
                            return;
                        }

                        status.textContent = 'Suivez les instructions de votre navigateur...';

                        const optionsJson = await response.json();
                        const credentialJson = await window.WebAuthnHelper.register(optionsJson);

                        credentialInput.value = JSON.stringify(credentialJson);
                        form.submit();
                    } catch (error) {
                        status.textContent = "Impossible d'enregistrer cette clé de sécurité (annulé ou non compatible).";
                        status.className = 'text-xs text-red-600';
                    }
                });
            })();
            </script>
        @endif

        @if(! $isSuperAdmin)
            <div class="border border-red-200 rounded-lg p-4 bg-red-50 space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-red-800">Suppression protégée</h3>
                    <p class="text-xs text-red-700 mt-1">
                        La suppression nécessite l'authentification d'un autre superviseur que celui-ci.
                    </p>
                </div>

                <form action="{{ route('employee.supervisors.destroy', $supervisor) }}" method="POST" class="space-y-4"
                      onsubmit="return confirm('Confirmer la suppression de ce superviseur ?')">
                    @csrf @method('DELETE')
                    @include('employee.shared.supervisor-auth-fields')
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Supprimer ce superviseur
                    </button>
                </form>
            </div>
        @endif
    </div>
</x-employee-layout>
