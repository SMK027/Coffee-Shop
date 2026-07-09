@php
    $supervisorNumberId = 'supervisor_number_' . uniqid();
    $supervisorPinId = 'supervisor_pin_' . uniqid();
@endphp

<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-4">
    <div class="space-y-1">
        <p class="text-sm font-semibold text-amber-800">Validation superviseur</p>
        <p class="text-xs text-amber-700">Un superviseur doit saisir son numéro et son PIN pour autoriser cette action sans créer de session persistante.</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="{{ $supervisorNumberId }}" class="block text-sm font-medium text-amber-900 mb-1">Numéro du superviseur</label>
            <input type="text" name="supervisor_number" id="{{ $supervisorNumberId }}" required
                   value="{{ old('supervisor_number') }}"
                   class="w-full border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('supervisor_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="{{ $supervisorPinId }}" class="block text-sm font-medium text-amber-900 mb-1">PIN du superviseur</label>
            <input type="password" name="supervisor_pin" id="{{ $supervisorPinId }}" required maxlength="6" minlength="4" inputmode="numeric" pattern="\d{4,6}"
                   class="w-full border border-amber-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('supervisor_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <p class="text-xs text-amber-700 mt-1">PIN de 4 à 6 chiffres.</p>
        </div>
    </div>
</div>
