<x-employee-layout title="Nouveau superviseur">
    <x-slot name="headerActions">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <form action="{{ route('employee.supervisors.store') }}" method="POST" class="max-w-xl space-y-5">
        @csrf

        @include('employee.supervisors.partials.form')

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Créer le superviseur
            </button>
            <a href="{{ route('employee.supervisors.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>
    </form>
</x-employee-layout>
