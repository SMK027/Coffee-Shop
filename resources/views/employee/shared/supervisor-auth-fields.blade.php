@php
    $supervisorNumberId = 'supervisor_number_' . uniqid();
    $supervisorPinId = 'supervisor_pin_' . uniqid();
    $supervisorTokenId = 'supervisor_token_' . uniqid();
@endphp

<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-4">
    <div class="space-y-1">
        <p class="text-sm font-semibold text-amber-800">Validation superviseur</p>
        <p class="text-xs text-amber-700">Saisissez l'identifiant + mot de passe d'un superviseur, ou scannez/collez un code-barres superviseur.</p>
    </div>

    <div>
        <label for="{{ $supervisorTokenId }}" class="block text-sm font-medium text-amber-900 mb-1">Code-barres superviseur (optionnel)</label>
        <input type="text" name="supervisor_token" id="{{ $supervisorTokenId }}"
               value="{{ old('supervisor_token') }}"
               class="w-full border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
               placeholder="SUPERVISOR:...">
        @error('supervisor_token')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="{{ $supervisorNumberId }}" class="block text-sm font-medium text-amber-900 mb-1">Identifiant superviseur</label>
            <input type="text" name="supervisor_number" id="{{ $supervisorNumberId }}"
                   value="{{ old('supervisor_number') }}"
                   class="w-full border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('supervisor_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="{{ $supervisorPinId }}" class="block text-sm font-medium text-amber-900 mb-1">Mot de passe superviseur</label>
            <input type="password" name="supervisor_pin" id="{{ $supervisorPinId }}" maxlength="6" minlength="4" inputmode="numeric" pattern="\d{4,6}"
                   class="w-full border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('supervisor_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <p class="text-xs text-amber-700 mt-1">Mot de passe de 4 à 6 chiffres (si saisie manuelle).</p>
        </div>
    </div>
</div>
