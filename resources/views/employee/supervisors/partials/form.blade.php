<div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <label for="supervisor_number" class="block text-sm font-medium text-stone-700 mb-1.5">Numéro du superviseur *</label>
            <input type="text" name="supervisor_number" id="supervisor_number" required maxlength="50"
                   value="{{ old('supervisor_number', $supervisor->supervisor_number ?? '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono">
            @error('supervisor_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="supervisor_pin" class="block text-sm font-medium text-stone-700 mb-1.5">PIN du superviseur {{ isset($supervisor) ? '(laisser vide pour conserver)' : '*' }}</label>
            <input type="password" name="supervisor_pin" id="supervisor_pin" {{ isset($supervisor) ? '' : 'required' }} maxlength="6" minlength="4"
                   inputmode="numeric" pattern="\d{4,6}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('supervisor_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            <p class="text-xs text-stone-400 mt-1">PIN de 4 à 6 chiffres.</p>
        </div>

        @if(isset($supervisor))
        <div class="sm:col-span-2">
            <label class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $supervisor->is_active) ? 'checked' : '' }} class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                <span class="text-sm text-stone-700">Actif</span>
            </label>
            @error('is_active')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        @endif
    </div>
</div>
