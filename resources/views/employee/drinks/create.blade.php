<x-employee-layout title="Ajouter une boisson">
    <x-slot name="headerActions">
        <a href="{{ route('employee.drinks.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-xl">
        <form action="{{ route('employee.drinks.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
              x-data="{ currentPrice: '' }">
            @csrf
            @include('employee.drinks._form')

            @unless(auth()->user()->isSuperAdmin())
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                <p class="font-semibold mb-1">Validation superviseur requise</p>
                <p class="text-xs text-amber-700">En tant qu'administrateur, la création d'une boisson avec un prix nécessite l'approbation d'un superviseur.</p>
            </div>
            @include('employee.shared.supervisor-auth-fields')
            @endunless

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Ajouter la boisson
                </button>
                <a href="{{ route('employee.drinks.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>

</x-employee-layout>
