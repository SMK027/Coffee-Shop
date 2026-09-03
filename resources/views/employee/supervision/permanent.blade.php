<x-employee-layout title="Mode superviseur permanent" subtitle="Réglage de cette session">
    <div class="max-w-2xl space-y-5">
        @if($isPermanentSupervisionEnabled)
            <div class="bg-green-50 border border-green-200 rounded-xl p-5 space-y-3">
                <div>
                    <p class="text-sm font-semibold text-green-900">Mode actif</p>
                    <p class="text-sm text-green-800 mt-1">Les opérations sensibles ne demanderont pas d’authentification superviseur supplémentaire durant cette session.</p>
                </div>
                <form action="{{ route('employee.supervision.permanent.disable') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-stone-800 hover:bg-stone-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Désactiver le mode
                    </button>
                </form>
            </div>
        @else
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 space-y-2">
                <p class="text-sm font-semibold text-amber-900">Mode inactif</p>
                <p class="text-sm text-amber-800">Chaque opération sensible requiert une identification superviseur. L’activation de ce mode nécessite elle aussi une identification superviseur.</p>
            </div>

            <form action="{{ route('employee.supervision.permanent.enable') }}" method="POST" class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 space-y-4">
                @csrf
                <div>
                    <label for="supervisor_token" class="block text-sm font-medium text-stone-700 mb-1">QR code superviseur (optionnel)</label>
                    <input type="text" name="supervisor_token" id="supervisor_token" value="{{ old('supervisor_token') }}" placeholder="SUPERVISOR:..."
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label for="supervisor_number" class="block text-sm font-medium text-stone-700 mb-1">Identifiant superviseur</label>
                        <input type="text" name="supervisor_number" id="supervisor_number" value="{{ old('supervisor_number') }}"
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
                <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Activer le mode
                </button>
            </form>
        @endif
    </div>
</x-employee-layout>