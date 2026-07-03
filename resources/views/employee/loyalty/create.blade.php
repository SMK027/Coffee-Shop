<x-employee-layout title="Nouvelle carte de fidélité">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-lg">

        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
                <ul class="space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('employee.loyalty.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <h2 class="font-semibold text-stone-800">Informations du titulaire</h2>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-stone-700 mb-1.5">Prénom *</label>
                        <input type="text" name="first_name" id="first_name" required maxlength="100"
                               value="{{ old('first_name') }}"
                               class="w-full border {{ $errors->has('first_name') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        @error('first_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom *</label>
                        <input type="text" name="last_name" id="last_name" required maxlength="100"
                               value="{{ old('last_name') }}"
                               class="w-full border {{ $errors->has('last_name') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        @error('last_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-stone-700 mb-1.5">Email *</label>
                    <input type="email" name="email" id="email" required maxlength="150"
                           value="{{ old('email') }}"
                           class="w-full border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-stone-700 mb-1.5">Téléphone *</label>
                    <input type="text" name="phone" id="phone" required maxlength="30"
                           value="{{ old('phone') }}" placeholder="06 12 34 56 78"
                           class="w-full border {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="birth_date" class="block text-sm font-medium text-stone-700 mb-1.5">Date de naissance *</label>
                    <input type="date" name="birth_date" id="birth_date" required
                           value="{{ old('birth_date') }}"
                           max="{{ now()->subYears(\App\Models\LoyaltyCard::MIN_AGE)->format('Y-m-d') }}"
                           class="w-full border {{ $errors->has('birth_date') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    <p class="text-xs text-stone-400 mt-1">Le titulaire doit avoir au moins {{ \App\Models\LoyaltyCard::MIN_AGE }} ans.</p>
                    @error('birth_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-4">
                <h2 class="font-semibold text-stone-800">Code PIN</h2>
                <p class="text-xs text-stone-400">4 à 6 chiffres. Le client devra l'utiliser pour identifier sa carte lors des commandes.</p>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="pin" class="block text-sm font-medium text-stone-700 mb-1.5">PIN *</label>
                        <input type="password" name="pin" id="pin" required
                               minlength="4" maxlength="6" inputmode="numeric" pattern="[0-9]{4,6}"
                               autocomplete="new-password"
                               class="w-full border {{ $errors->has('pin') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        @error('pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="pin_confirmation" class="block text-sm font-medium text-stone-700 mb-1.5">Confirmer le PIN *</label>
                        <input type="password" name="pin_confirmation" id="pin_confirmation" required
                               minlength="4" maxlength="6" inputmode="numeric" pattern="[0-9]{4,6}"
                               autocomplete="new-password"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Créer la carte
                </button>
                <a href="{{ route('employee.loyalty.index') }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>

        </form>
    </div>

</x-employee-layout>
