<div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5" x-data="{ temporary: {{ old('is_manual_temporary', $supervisor->is_manual_temporary ?? false) ? 'true' : 'false' }} }">
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

        <div class="sm:col-span-2 border-t border-stone-100 pt-5">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_manual_temporary" value="1" x-model="temporary" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                <span class="text-sm font-medium text-stone-700">Superviseur temporaire</span>
            </label>
            <p class="text-xs text-stone-400 mt-1">Les superviseurs temporaires manuels sont gérables et leur identifiant doit commencer par 7.</p>

            <div x-show="temporary" x-cloak class="mt-4">
                <label for="temporary_expires_at" class="block text-sm font-medium text-stone-700 mb-1.5">Date d'expiration *</label>
                <input type="datetime-local" name="temporary_expires_at" id="temporary_expires_at"
                       value="{{ old('temporary_expires_at', isset($supervisor) && $supervisor->temporary_expires_at ? $supervisor->temporary_expires_at->format('Y-m-d\TH:i') : '') }}"
                       class="w-full border {{ $errors->has('temporary_expires_at') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                @error('temporary_expires_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        @if(isset($supervisor))
        @php $isQuarantined = $supervisor->quarantined_until?->isFuture(); @endphp
        <div class="sm:col-span-2">
            <label class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $supervisor->is_active) ? 'checked' : '' }} @disabled($isQuarantined) class="rounded border-stone-300 text-amber-600 focus:ring-amber-500 disabled:opacity-50">
                <span class="text-sm text-stone-700">Actif</span>
            </label>
            @if($isQuarantined)
                <p class="text-xs text-red-600 mt-1">Ce superviseur est en quarantaine jusqu’au {{ $supervisor->quarantined_until->format('d/m/Y à H:i') }}. La réactivation directe est désactivée.</p>
                <label class="flex items-center gap-3 mt-3">
                    <input type="checkbox" name="reactivate_after_pin_reset" value="1" class="rounded border-stone-300 text-green-600 focus:ring-green-500">
                    <span class="text-sm text-stone-700">Réactiver après changement du PIN et supprimer les codes provisoires</span>
                </label>
            @endif
            @error('is_active')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        @endif
    </div>

    <div class="border-t border-stone-100 pt-5">
        <p class="text-sm font-medium text-stone-700 mb-1.5">Habilitations</p>
        <p class="text-xs text-stone-400 mb-3">Opérations que ce superviseur est autorisé à débloquer par bypass. Aucune case cochée = aucune opération sensible autorisée.</p>
        <div class="grid sm:grid-cols-2 gap-x-5 gap-y-2.5">
            @php $selectedPermissions = old('permissions', $supervisor->permissions ?? []); @endphp
            @foreach(\App\Support\SupervisorOperation::options() as $key => $label)
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="permissions[]" value="{{ $key }}"
                           {{ in_array($key, $selectedPermissions ?? [], true) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm text-stone-700">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('permissions')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>
