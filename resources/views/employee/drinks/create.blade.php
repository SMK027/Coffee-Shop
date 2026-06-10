<x-employee-layout title="Ajouter une boisson">
    <x-slot name="headerActions">
        <a href="{{ route('employee.drinks.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-xl">
        <form action="{{ route('employee.drinks.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @include('employee.drinks._form')
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
