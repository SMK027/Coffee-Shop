<x-employee-layout title="Modifier le superviseur" subtitle="{{ $supervisor->supervisor_number }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <form action="{{ route('employee.supervisors.update', $supervisor) }}" method="POST" class="max-w-xl space-y-5">
        @csrf @method('PUT')

        @if($isSuperAdmin)
            @include('employee.supervisors.partials.form')
        @else
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Numéro du superviseur</label>
                    <input type="text" value="{{ $supervisor->supervisor_number }}" readonly
                           class="w-full border border-stone-200 bg-stone-50 rounded-lg px-4 py-2.5 text-sm text-stone-500 font-mono">
                </div>

                <div>
                    <label for="supervisor_pin" class="block text-sm font-medium text-stone-700 mb-1.5">Nouveau PIN du superviseur *</label>
                    <input type="password" name="supervisor_pin" id="supervisor_pin" required maxlength="6" minlength="4"
                           inputmode="numeric" pattern="\d{4,6}"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('supervisor_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-stone-400 mt-1">PIN de 4 à 6 chiffres.</p>
                </div>
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Enregistrer
            </button>
            <a href="{{ route('employee.supervisors.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>
    </form>
</x-employee-layout>
