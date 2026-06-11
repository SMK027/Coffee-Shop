<x-employee-layout title="Réglages fidélité">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-lg">
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Programme de fidélité</h2>
            <p class="text-sm text-stone-500 mb-6">Définissez le nombre de points crédités pour chaque euro dépensé. Les points sont attribués automatiquement après la clôture d'une commande.</p>

            <form action="{{ route('employee.loyalty.settings.update') }}" method="POST" class="space-y-5">
                @csrf
                @method('PATCH')
                <div>
                    <label for="points_per_euro" class="block text-sm font-medium text-stone-700 mb-1.5">Points par euro dépensé *</label>
                    <input type="number" name="points_per_euro" id="points_per_euro" required min="0" max="1000"
                           value="{{ old('points_per_euro', $pointsPerEuro) }}"
                           class="w-40 border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('points_per_euro')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-stone-400 mt-1">Exemple : avec 5, une commande de 10 € crédite 50 points.</p>
                </div>
                <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Enregistrer
                </button>
            </form>
        </div>
    </div>

</x-employee-layout>
