<x-employee-layout title="Nouvelle reduction fidelite">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty-discounts.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-3xl">
        @include('employee.loyalty-discounts.partials.form', [
            'action' => route('employee.loyalty-discounts.store'),
            'method' => 'POST',
            'discount' => null,
        ])
    </div>
</x-employee-layout>
