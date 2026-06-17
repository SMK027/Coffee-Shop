<x-employee-layout title="Réglages fidélité">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-lg space-y-5">

        {{-- Nouveau système --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <h2 class="font-semibold text-stone-800 mb-1">Programme de fidélité — points par article</h2>
            <p class="text-sm text-stone-500 mb-4">
                Les points sont désormais attribués article par article.
                Configurez le nombre de points accordés pour chaque boisson depuis
                <a href="{{ route('employee.drinks.index') }}" class="text-amber-700 hover:underline">la gestion du menu</a>.
            </p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
                <strong>Exemple :</strong> si un espresso vaut 15 points et qu'un client en commande 2, il reçoit 30 points à la clôture de sa commande.
            </div>
        </div>

    </div>

</x-employee-layout>
