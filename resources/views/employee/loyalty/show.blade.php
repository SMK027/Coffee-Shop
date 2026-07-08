<x-employee-layout title="Carte {{ chunk_split($loyaltyCard->card_number, 4, ' ') }}" subtitle="{{ $loyaltyCard->full_name }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

        {{-- Infos titulaire --}}
        <div class="space-y-4 sm:space-y-6">
            <div class="bg-gradient-to-br from-amber-800 to-amber-900 text-amber-50 rounded-xl shadow-sm p-6 text-center">
                <p class="text-amber-300 text-xs uppercase tracking-wider mb-2">Solde de points</p>
                <p class="text-5xl font-bold">{{ $loyaltyCard->points }}</p>
            </div>

            {{-- QR code + code-barres --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6 text-center">
                <p class="text-xs font-medium text-stone-500 uppercase tracking-wider mb-3">N° de carte</p>
                <p class="font-mono text-stone-800 text-sm font-semibold mb-4 tracking-widest">
                    {{ chunk_split($loyaltyCard->card_number, 4, ' ') }}
                </p>
                {{-- QR Code --}}
                <div class="flex justify-center mb-4">
                    <div id="qrcode" class="p-2 bg-white border border-stone-200 rounded-lg inline-block"></div>
                </div>
                {{-- Code-barres --}}
                <div class="overflow-x-auto">
                    <svg id="barcode" class="mx-auto max-w-full"></svg>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Titulaire</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-stone-500">Nom complet</dt><dd class="font-medium text-stone-800">{{ $loyaltyCard->full_name }}</dd></div>
                    <div><dt class="text-stone-500">Email</dt><dd class="text-stone-800">{{ $loyaltyCard->email }}</dd></div>
                    <div><dt class="text-stone-500">Téléphone</dt><dd class="text-stone-800">{{ $loyaltyCard->phone }}</dd></div>
                    <div><dt class="text-stone-500">Date de naissance</dt><dd class="text-stone-800">{{ $loyaltyCard->birth_date->format('d/m/Y') }} ({{ $loyaltyCard->age }} ans)</dd></div>
                    <div><dt class="text-stone-500">Carte créée le</dt><dd class="text-stone-800">{{ $loyaltyCard->created_at->format('d/m/Y') }}</dd></div>
                    <div>
                        <dt class="text-stone-500">Avantages salariés</dt>
                        <dd>
                            @if($loyaltyCard->hasEmployeeBenefits())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Actifs · {{ $loyaltyCard->user->name }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-500">Aucun</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 pt-5 border-t border-stone-100">
                    <h3 class="text-sm font-semibold text-stone-800 mb-3">Modifier les informations du titulaire</h3>
                    <form action="{{ route('employee.loyalty.holder.update', $loyaltyCard) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label for="holder_first_name" class="block text-sm font-medium text-stone-700 mb-1">Prénom</label>
                                <input
                                    type="text"
                                    id="holder_first_name"
                                    name="first_name"
                                    value="{{ old('first_name', $loyaltyCard->first_name) }}"
                                    required
                                    maxlength="100"
                                    class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                >
                                @error('first_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="holder_last_name" class="block text-sm font-medium text-stone-700 mb-1">Nom</label>
                                <input
                                    type="text"
                                    id="holder_last_name"
                                    name="last_name"
                                    value="{{ old('last_name', $loyaltyCard->last_name) }}"
                                    required
                                    maxlength="100"
                                    class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                >
                                @error('last_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="holder_email" class="block text-sm font-medium text-stone-700 mb-1">Email</label>
                            <input
                                type="email"
                                id="holder_email"
                                name="email"
                                value="{{ old('email', $loyaltyCard->email) }}"
                                required
                                maxlength="150"
                                class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                            >
                            @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="holder_phone" class="block text-sm font-medium text-stone-700 mb-1">Téléphone</label>
                            <input
                                type="text"
                                id="holder_phone"
                                name="phone"
                                value="{{ old('phone', $loyaltyCard->phone) }}"
                                required
                                maxlength="30"
                                class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                            >
                            @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">
                            Enregistrer les informations
                        </button>
                    </form>
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin())
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-2">Avantages salariés</h2>
                <p class="text-sm text-stone-500 mb-4">
                    Rattachez cette carte à un compte salarié pour appliquer automatiquement
                    la réduction salarié de 15% sur ses commandes. Les avantages sont retirés
                    automatiquement si le compte est supprimé.
                </p>
                <form action="{{ route('employee.loyalty.benefits.update', $loyaltyCard) }}" method="POST" id="benefits-form">
                    @csrf @method('PATCH')

                    <label class="flex items-center gap-3 cursor-pointer select-none mb-4">
                        <input type="checkbox" name="employee_benefits" id="employee_benefits" value="1"
                               {{ $loyaltyCard->hasEmployeeBenefits() ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                        <span class="text-sm font-medium text-stone-700">Carte détenue par un salarié</span>
                    </label>

                    <div id="employee-picker" class="{{ $loyaltyCard->hasEmployeeBenefits() ? '' : 'hidden' }} relative mb-4">
                        <label for="employee_search" class="block text-sm font-medium text-stone-700 mb-1.5">Salarié titulaire</label>
                        <input type="hidden" name="user_id" id="employee_user_id" value="{{ $loyaltyCard->user_id }}">
                        <input type="text" id="employee_search" autocomplete="off"
                               value="{{ $loyaltyCard->user ? $loyaltyCard->user->name . ' · ' . $loyaltyCard->user->email : '' }}"
                               placeholder="Rechercher un salarié…"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        <ul id="employee-dropdown" class="hidden absolute z-20 w-full bg-white border border-stone-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto"></ul>
                        @error('user_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">
                        Enregistrer les avantages
                    </button>
                </form>
            </div>
            @endif

            @if(auth()->user()->isSuperAdmin())
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-2">Code PIN</h2>
                <p class="text-sm text-stone-500 mb-4">
                    Envoyez au titulaire un lien par email pour qu'il définisse un nouveau code PIN.
                    Le lien expire dans 30 minutes.
                </p>
                <form action="{{ route('employee.loyalty.pin.send', $loyaltyCard) }}" method="POST"
                      onsubmit="return confirm('Envoyer un lien de réinitialisation du code PIN à {{ $loyaltyCard->email }} ?');">
                    @csrf
                    <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Réinitialiser le code PIN par email
                    </button>
                </form>
            </div>
            @endif

            @if(auth()->user()->isSuperAdmin())
            <div id="points-adjustment" class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-2">Ajuster les points</h2>
                <p class="text-sm text-stone-500 mb-4">
                    Créditez ou débitez manuellement le solde de points de cette carte.
                    Chaque opération est enregistrée dans l'historique ci-dessous.
                </p>
                <form action="{{ route('employee.loyalty.points.adjust', $loyaltyCard) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center justify-center gap-2 cursor-pointer border border-stone-300 rounded-lg px-3 py-2.5 text-sm font-medium has-[:checked]:bg-green-50 has-[:checked]:border-green-400 has-[:checked]:text-green-700 transition-colors">
                            <input type="radio" name="type" value="credit" class="text-green-600 focus:ring-green-500" {{ old('type', 'credit') === 'credit' ? 'checked' : '' }}>
                            Créditer
                        </label>
                        <label class="flex items-center justify-center gap-2 cursor-pointer border border-stone-300 rounded-lg px-3 py-2.5 text-sm font-medium has-[:checked]:bg-red-50 has-[:checked]:border-red-400 has-[:checked]:text-red-700 transition-colors">
                            <input type="radio" name="type" value="debit" class="text-red-600 focus:ring-red-500" {{ old('type') === 'debit' ? 'checked' : '' }}>
                            Débiter
                        </label>
                    </div>
                    @error('type')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror

                    <div>
                        <label for="points" class="block text-sm font-medium text-stone-700 mb-1.5">Nombre de points</label>
                        <input type="number" name="points" id="points" min="1" max="100000" required value="{{ old('points') }}"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        @error('points')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-stone-700 mb-1.5">Motif (optionnel)</label>
                        <input type="text" name="reason" id="reason" maxlength="255" value="{{ old('reason') }}"
                               placeholder="Ex. geste commercial, correction…"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        @error('reason')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg font-medium text-sm transition-colors">
                        Appliquer l'ajustement
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Commandes raccordées --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
                <h2 class="font-semibold text-stone-800 px-4 sm:px-6 py-4 border-b border-stone-100">Commandes raccordées</h2>
                @if($loyaltyCard->orders->isEmpty())
                    <div class="px-6 py-12 text-center text-stone-500"><p>Aucune commande raccordée à cette carte.</p></div>
                @else
                    <div class="divide-y divide-stone-50">
                        @foreach($loyaltyCard->orders as $order)
                        <a href="{{ route('employee.orders.show', $order) }}" class="flex items-center justify-between px-4 sm:px-6 py-3 hover:bg-stone-50 transition-colors">
                            <div>
                                <p class="font-medium text-stone-800 text-sm">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }} · {{ $order->status_label }}</p>
                                <p class="text-xs text-stone-400">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-stone-800">{{ number_format($order->total_amount, 2, ',', ' ') }} €</p>
                                @if($order->points_credited)
                                    <p class="text-xs text-green-600">+{{ $order->points_awarded }} pts</p>
                                @endif
                            </div>
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Historique des mouvements de points (visible par tous les salariés) --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden mt-4 sm:mt-6">
                <h2 class="font-semibold text-stone-800 px-4 sm:px-6 py-4 border-b border-stone-100">Historique des points</h2>
                @if($loyaltyCard->pointAdjustments->isEmpty())
                    <div class="px-6 py-12 text-center text-stone-500"><p>Aucun mouvement de points pour cette carte.</p></div>
                @else
                    <div class="divide-y divide-stone-50">
                        @foreach($loyaltyCard->pointAdjustments as $adjustment)
                        @php
                            $isCredit = $adjustment->isCredit();
                            $isManual = $adjustment->isManual();
                            $source   = $adjustment->source;
                        @endphp
                        <div class="flex items-start justify-between gap-3 px-4 sm:px-6 py-3 hover:bg-stone-50 transition-colors">
                            <div class="flex items-start gap-3 min-w-0">
                                {{-- Icône --}}
                                <div class="flex-shrink-0 mt-0.5 w-7 h-7 rounded-full flex items-center justify-center
                                    {{ $isCredit ? 'bg-green-100' : 'bg-red-100' }}">
                                    @if($isCredit)
                                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                    @else
                                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5 mb-0.5">
                                        <span class="text-sm font-medium {{ $isCredit ? 'text-green-700' : 'text-red-700' }}">
                                            {{ $isCredit ? 'Crédit' : 'Débit' }} de {{ $adjustment->points }} pts
                                        </span>
                                        @if($source === 'order_debit')
                                            <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Réduction</span>
                                        @elseif($source === 'order_credit')
                                            <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Points gagnés</span>
                                        @elseif($source === 'refund')
                                            <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Remboursement</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-600">Ajustement manuel</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-stone-400 truncate">
                                        {{ $adjustment->created_at->format('d/m/Y à H:i') }}
                                        @if($adjustment->order_id)
                                            · <a href="{{ route('employee.orders.show', $adjustment->order_id) }}" class="underline hover:text-stone-600">Commande #{{ str_pad($adjustment->order_id, 4, '0', STR_PAD_LEFT) }}</a>
                                        @endif
                                        @if($adjustment->user) · {{ $adjustment->user->name }} @endif
                                        @if($adjustment->reason && $isManual) · {{ $adjustment->reason }} @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-semibold {{ $isCredit ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $isCredit ? '+' : '−' }}{{ $adjustment->points }}
                                </p>
                                <p class="text-xs text-stone-400">Solde : {{ $adjustment->balance_after }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @if(auth()->user()->isSuperAdmin())
            </div>
            @endif
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin())
    <script>
    (function () {
        const toggle   = document.getElementById('employee_benefits');
        const picker   = document.getElementById('employee-picker');
        const search   = document.getElementById('employee_search');
        const hidden    = document.getElementById('employee_user_id');
        const dropdown = document.getElementById('employee-dropdown');
        const searchUrl = @json(route('employee.loyalty.employees.search'));
        let debounce;

        toggle.addEventListener('change', () => {
            picker.classList.toggle('hidden', !toggle.checked);
            if (!toggle.checked) {
                hidden.value = '';
                search.value = '';
                closeDropdown();
            }
        });

        function closeDropdown() {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        }

        function render(results) {
            dropdown.innerHTML = '';
            if (!results.length) {
                dropdown.innerHTML = '<li class="px-3 py-2.5 text-sm text-stone-400 italic">Aucun salarié trouvé</li>';
                dropdown.classList.remove('hidden');
                return;
            }
            results.forEach(u => {
                const li = document.createElement('li');
                li.className = 'px-3 py-2.5 cursor-pointer text-sm hover:bg-stone-50';
                li.innerHTML = `<span class="font-medium text-stone-800">${u.name}</span>
                                <span class="block text-xs text-stone-400">${u.email}</span>`;
                li.addEventListener('click', () => {
                    hidden.value = u.id;
                    search.value = u.name + ' · ' + u.email;
                    closeDropdown();
                });
                dropdown.appendChild(li);
            });
            dropdown.classList.remove('hidden');
        }

        search.addEventListener('input', () => {
            // Toute saisie manuelle invalide la sélection précédente
            hidden.value = '';
            const q = search.value.trim();
            clearTimeout(debounce);
            debounce = setTimeout(() => {
                fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(r => r.json())
                    .then(render)
                    .catch(closeDropdown);
            }, 200);
        });

        document.addEventListener('click', (e) => {
            if (!picker.contains(e.target)) closeDropdown();
        });
    })();
    </script>
    @endif

</x-employee-layout>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function () {
    const cardNumber = '{{ $loyaltyCard->card_number }}';

    // QR Code
    new QRCode(document.getElementById('qrcode'), {
        text: cardNumber,
        width: 140,
        height: 140,
        colorDark: '#44403c',  // stone-700
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
    });

    // Code-barres Code128
    JsBarcode('#barcode', cardNumber, {
        format: 'CODE128',
        lineColor: '#44403c',
        width: 2,
        height: 60,
        displayValue: false,
        margin: 4,
    });
})();
</script>
