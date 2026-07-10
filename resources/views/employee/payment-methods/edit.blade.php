<x-employee-layout title="Modifier — {{ $paymentMethod->name }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.payment-methods.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <form action="{{ route('employee.payment-methods.update', $paymentMethod) }}" method="POST" class="max-w-xl space-y-5">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-stone-700 mb-1">Nom <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $paymentMethod->name) }}" required
                       class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-stone-700 mb-1">Slug <span class="text-red-500">*</span></label>
                <input type="text" name="slug" id="slug" value="{{ old('slug', $paymentMethod->slug) }}" required
                       class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono">
                @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-stone-700 mb-1">Ordre d'affichage</label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $paymentMethod->sort_order) }}" min="0"
                       class="w-32 border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                @error('sort_order')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        @unless(auth()->user()->isSuperAdmin())
            @include('employee.shared.supervisor-auth-fields')
        @endunless

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Enregistrer
            </button>
            <a href="{{ route('employee.payment-methods.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>
    </form>
</x-employee-layout>
