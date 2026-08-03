<x-employee-layout title="Modifier le bon — {{ $voucher->code }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.vouchers.show', $voucher) }}" class="text-stone-500 hover:text-stone-700 text-sm">
            ← Retour au bon d'achat
        </a>
    </x-slot>

    @php
        $oldRestrict = old('restriction_type',
            $voucher->restricted_card_id !== null ? 'card'
            : ($voucher->restricted_name !== null ? 'name' : 'none')
        );
        $oldCardNumber = old('restricted_card_number',
            $voucher->restrictedCard?->card_number ?? ''
        );
        $oldName = old('restricted_name', $voucher->restricted_name ?? '');
    @endphp

    <div class="max-w-xl">
        <form action="{{ route('employee.vouchers.update', $voucher) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">

                {{-- Code (lecture seule) --}}
                <div class="bg-stone-50 rounded-lg px-4 py-3 flex items-center justify-between">
                    <span class="text-sm text-stone-500">Code du bon</span>
                    <span class="font-mono font-bold text-lg tracking-widest text-stone-800">{{ $voucher->code }}</span>
                </div>

                {{-- Montant --}}
                <div>
                    <label for="amount" class="block text-sm font-medium text-stone-700 mb-1">
                        Montant <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" id="amount" name="amount"
                               step="0.01" min="0.01" max="9999.99"
                               value="{{ old('amount', number_format((float)$voucher->amount, 2, '.', '')) }}"
                               class="w-full border {{ $errors->has('amount') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 pr-9 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                               required>
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-stone-400 text-sm font-medium pointer-events-none">€</span>
                    </div>
                    @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Date d'expiration --}}
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-stone-700 mb-1">
                        Date d'expiration <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="expires_at" name="expires_at"
                           value="{{ old('expires_at', $voucher->expires_at->format('Y-m-d')) }}"
                           min="{{ now()->addDay()->format('Y-m-d') }}"
                           max="{{ now()->addYear()->format('Y-m-d') }}"
                           class="w-full border {{ $errors->has('expires_at') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                           required>
                    @if($voucher->isExpired())
                        <p class="text-xs text-amber-600 mt-1">Ce bon est actuellement expiré. Définir une date future le réactivera.</p>
                    @endif
                    @error('expires_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Informations non modifiables --}}
                <div class="bg-stone-50 rounded-lg px-4 py-3 text-sm space-y-1">
                    <div class="flex justify-between">
                        <span class="text-stone-500">Émis par</span>
                        <span class="font-medium text-stone-700">{{ $voucher->issued_by_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-stone-500">Date d'émission</span>
                        <span class="text-stone-700">{{ $voucher->issued_at->format('d/m/Y') }}</span>
                    </div>
                </div>

                {{-- Restriction d'utilisation --}}
                <div x-data="voucherEditRestriction()">
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
                                   value="{{ $oldCardNumber }}"
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
                        @if($voucher->restrictedCard && old('restriction_type', 'card') === 'card')
                            <p class="text-xs text-stone-500">
                                Actuellement réservé à :
                                <span class="font-medium text-stone-700">{{ $voucher->restrictedCard->full_name }}</span>
                            </p>
                        @endif
                    </div>

                    {{-- Restriction par nom --}}
                    <div x-show="restrictType === 'name'" x-cloak>
                        <label for="restricted_name" class="block text-sm text-stone-600 mb-1">Nom complet du client</label>
                        <input type="text" id="restricted_name" name="restricted_name"
                               value="{{ $oldName }}"
                               maxlength="150"
                               placeholder="Prénom Nom"
                               class="w-full border {{ $errors->has('restricted_name') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        <p class="text-xs text-stone-400 mt-1">La comparaison sera insensible à la casse.</p>
                        @error('restricted_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Validation superviseur (admins simples) --}}
            @unless(auth()->user()->isSuperAdmin())
                @include('employee.shared.supervisor-auth-fields')
            @endunless

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Enregistrer les modifications
                </button>
                <a href="{{ route('employee.vouchers.show', $voucher) }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>

</x-employee-layout>

<script>
function voucherEditRestriction() {
    const checkUrl = '{{ route('employee.orders.loyalty-check') }}';
    return {
        restrictType: '{{ $oldRestrict }}',
        cardName: '',
        cardChecking: false,
        cardError: '',
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
