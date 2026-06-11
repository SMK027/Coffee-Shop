<x-employee-layout title="Modifier la réduction" subtitle="{{ $loyaltyDiscount->name }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.loyalty-discounts.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-3xl">
        @include('employee.loyalty-discounts.partials.form', [
            'action' => route('employee.loyalty-discounts.update', $loyaltyDiscount),
            'method' => 'PUT',
            'discount' => $loyaltyDiscount,
        ])
    </div>
</x-employee-layout>
