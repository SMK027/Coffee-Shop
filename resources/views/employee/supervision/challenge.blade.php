<x-employee-layout title="Validation superviseur requise" subtitle="Action temporairement bloquée">
    <div class="max-w-2xl space-y-5">
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-2">
            <p class="text-sm font-semibold text-amber-900">Cette opération nécessite une autorisation superviseur.</p>
            <p class="text-sm text-amber-800">Opération demandée : <span class="font-medium">{{ $operationLabel }}</span></p>
            <p class="text-xs text-amber-700">Les données du formulaire ont été conservées temporairement et seront rejouées automatiquement après validation.</p>
        </div>

        <form action="{{ route('employee.supervision.approve') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 space-y-4">
            @csrf

            <div>
                <label for="supervisor_token" class="block text-sm font-medium text-stone-700 mb-1">QR code superviseur (optionnel)</label>
                <input type="text" name="supervisor_token" id="supervisor_token"
                       value="{{ old('supervisor_token') }}"
                       class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                       placeholder="SUPERVISOR:...">
                @error('supervisor_token')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="supervisor_number" class="block text-sm font-medium text-stone-700 mb-1">Identifiant superviseur</label>
                    <input type="text" name="supervisor_number" id="supervisor_number"
                           value="{{ old('supervisor_number') }}"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('supervisor_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="supervisor_pin" class="block text-sm font-medium text-stone-700 mb-1">PIN superviseur</label>
                    <input type="password" name="supervisor_pin" id="supervisor_pin" maxlength="6" minlength="4" inputmode="numeric" pattern="\d{4,6}"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('supervisor_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Autoriser et exécuter
                </button>
                <a href="{{ route('employee.dashboard') }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-employee-layout>
