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
                               value="{{ old('amount') }}"
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
                               value="{{ old('validity_days', 7) }}"
                               class="flex-1 accent-amber-700"
                               oninput="document.getElementById('validity_display').textContent = this.value + ' jour' + (this.value > 1 ? 's' : '')">
                        <span id="validity_display"
                              class="w-20 text-center text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg py-1 px-2">
                            {{ old('validity_days', 7) }} jours
                        </span>
                    </div>
                    <p class="text-xs text-stone-500 mt-1">
                        Le bon expirera le
                        <span id="expiry_date" class="font-medium text-stone-700">
                            {{ now()->addDays(old('validity_days', 7))->translatedFormat('d/m/Y') }}
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
</script>
