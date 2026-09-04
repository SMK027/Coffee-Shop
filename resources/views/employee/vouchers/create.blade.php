<x-employee-layout title="Nouveau bon d'achat / avoir">
    <x-slot name="headerActions">
        <a href="{{ route('employee.vouchers.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour aux bons d'achat</a>
    </x-slot>

    <div class="max-w-xl">
        <form action="{{ route('employee.vouchers.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <h2 class="font-semibold text-stone-800">Paramètres du bon</h2>

                {{-- Montant --}}
                <div>
                    <label for="amount" class="block text-sm font-medium text-stone-700 mb-1">
                        Montant <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" id="amount" name="amount"
                               step="0.01" min="0.01" max="9999.99"
                                   value="{{ old('amount', $prefill['amount'] ?? '') }}"
                               class="w-full border {{ $errors->has('amount') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 pr-9 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                               placeholder="0,00" required>
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 text-sm font-medium pointer-events-none">€</span>
                    </div>
                    @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Durée de validité --}}
                <div>
                    <label for="validity_days" class="block text-sm font-medium text-stone-700 mb-1">
                        Durée de validité <span class="text-red-500">*</span>
                        <span class="font-normal text-stone-400 ml-1">(3 à 31 jours)</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="range" id="validity_days" name="validity_days"
                               min="3" max="31" step="1"
                                 value="{{ old('validity_days', $prefill['validity_days'] ?? 7) }}"
                               class="flex-1 accent-amber-700"
                               oninput="document.getElementById('validity_display').textContent = this.value + ' jour' + (this.value > 1 ? 's' : '')">
                        <span id="validity_display"
                              class="w-20 text-center text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg py-1 px-2">
                               {{ old('validity_days', $prefill['validity_days'] ?? 7) }} jours
                        </span>
                    </div>
                    <p class="text-xs text-stone-500 mt-1">
                        Le bon expirera le
                        <span id="expiry_date" class="font-medium text-stone-700">
                               {{ now()->addDays((int) old('validity_days', $prefill['validity_days'] ?? 7))->translatedFormat('d/m/Y') }}
                        </span>
                    </p>
                    @error('validity_days')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Compte rattaché --}}
                <div class="bg-stone-50 rounded-lg px-4 py-3 text-sm">
                    @if(auth()->user()->isSuperAdmin())
                        <p class="text-stone-600">
                            <span class="font-medium text-stone-800">Compte rattaché automatiquement :</span>
                            <span class="text-amber-700 font-semibold ml-1">{{ auth()->user()->name }}</span>
                        </p>
                        <p class="text-xs text-stone-400 mt-0.5">Votre compte super administrateur sera enregistré comme émetteur.</p>
                    @else
                        <p class="text-stone-600">
                            <span class="font-medium text-stone-800">Compte rattaché :</span>
                            <span class="text-stone-500 ml-1">celui du super administrateur associé au superviseur validateur</span>
                        </p>
                    @endif
                </div>

                {{-- Restriction d'utilisation --}}
                <div x-data="voucherRestriction()">
                    <p class="block text-sm font-medium text-stone-700 mb-2">Restriction d'utilisation</p>

                    <div class="grid grid-cols-3 gap-2 mb-3">
                        @foreach(['none' => 'Aucune', 'card' => 'Carte fidélité', 'name' => 'Nom du client'] as $val => $lbl)
                        <label class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium cursor-pointer transition-colors"
                               :class="restrictType === '{{ $val }}' ? 'border-amber-600 bg-amber-50 text-amber-700' : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50'">
                            <input type="radio" name="restriction_type" value="{{ $val }}" x-model="restrictType" class="sr-only">
                            {{ $lbl }}
                        </label>
                        @endforeach
                    </div>
                    @error('restriction_type')<p class="text-red-500 text-xs mb-2">{{ $message }}</p>@enderror

                    {{-- Restriction par carte --}}
                    <div x-show="restrictType === 'card'" x-cloak class="space-y-2">
                        <label for="restricted_card_number" class="block text-sm text-stone-600">Numéro de carte fidélité</label>
                        <div class="flex gap-2">
                            <input type="text" name="restricted_card_number" id="restricted_card_number"
                                       value="{{ old('restricted_card_number', $prefill['restricted_card_number'] ?? '') }}"
                                   maxlength="20"
                                   placeholder="0000 0000 0000 0000"
                                   @input="cardName = ''; cardError = ''"
                                   class="flex-1 border {{ $errors->has('restricted_card_number') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2 text-sm font-mono tracking-wider focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <button type="button" @click="checkCard()"
                                    :disabled="cardChecking"
                                    class="flex-shrink-0 bg-stone-100 hover:bg-stone-200 disabled:opacity-50 text-stone-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <span x-text="cardChecking ? '…' : 'Vérifier'"></span>
                            </button>
                        </div>
                        @error('restricted_card_number')<p class="text-red-500 text-xs">{{ $message }}</p>@enderror
                        <p x-show="cardName" x-text="'✓ Titulaire : ' + cardName" class="text-xs text-green-700"></p>
                        <p x-show="cardError" x-text="cardError" class="text-xs text-red-600"></p>
                    </div>

                    {{-- Restriction par nom --}}
                    <div x-show="restrictType === 'name'" x-cloak>
                        <label for="restricted_name" class="block text-sm text-stone-600 mb-1">Nom complet du client</label>
                        <input type="text" id="restricted_name" name="restricted_name"
                               value="{{ old('restricted_name') }}"
                               maxlength="150"
                               placeholder="Prénom Nom"
                               class="w-full border {{ $errors->has('restricted_name') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        <p class="text-xs text-stone-400 mt-1">La comparaison sera insensible à la casse.</p>
                        @error('restricted_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Validation superviseur (admins seulement) --}}
            @unless(auth()->user()->isSuperAdmin())
                @include('employee.shared.supervisor-auth-fields')
            @endunless

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Générer le bon d'achat
                </button>
                <a href="{{ route('employee.vouchers.index') }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>

</x-employee-layout>

<script>
(function () {
    const slider  = document.getElementById('validity_days');
    const display = document.getElementById('validity_display');
    const expiry  = document.getElementById('expiry_date');

    function updateExpiry(days) {
        const d = new Date();
        d.setDate(d.getDate() + parseInt(days, 10));
        expiry.textContent = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        display.textContent = days + ' jour' + (days > 1 ? 's' : '');
    }

    if (slider) {
        slider.addEventListener('input', () => updateExpiry(slider.value));
        updateExpiry(slider.value);
    }
})();

function voucherRestriction() {
    const checkUrl = '{{ route('employee.orders.loyalty-check') }}';
    return {
        restrictType: '{{ old('restriction_type', $prefill['restriction_type'] ?? 'none') }}',
        cardName: '',
        cardChecking: false,
        cardError: '',
        init() {
            if (this.restrictType === 'card' && document.getElementById('restricted_card_number')?.value) {
                this.checkCard();
            }
        },
        async checkCard() {
            const num = document.getElementById('restricted_card_number').value.replace(/\s/g, '');
            if (!num) return;
            this.cardChecking = true;
            this.cardName = '';
            this.cardError = '';
            try {
                const r = await fetch(checkUrl + '?card_number=' + encodeURIComponent(num));
                const d = await r.json();
                if (d.found) {
                    this.cardName = d.card.full_name;
                } else {
                    this.cardError = d.message || 'Carte introuvable.';
                }
            } catch {
                this.cardError = 'Erreur réseau.';
            } finally {
                this.cardChecking = false;
            }
        },
    };
}
</script>
