<x-employee-layout title="Nouveau statut">
    <x-slot name="headerActions">
        <a href="{{ route('employee.order-statuses.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    @php
        $colors = [
            'gray'   => 'Gris',
            'amber'  => 'Ambre',
            'blue'   => 'Bleu',
            'green'  => 'Vert',
            'red'    => 'Rouge',
            'purple' => 'Violet',
            'indigo' => 'Indigo',
            'orange' => 'Orange',
            'teal'   => 'Sarcelle',
        ];
    @endphp

    <form action="{{ route('employee.order-statuses.store') }}" method="POST" class="max-w-xl space-y-5">
        @csrf

        @include('employee.order-statuses.partials.form')

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Créer le statut
            </button>
            <a href="{{ route('employee.order-statuses.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>
    </form>
</x-employee-layout>
