<x-employee-layout title="Nouveau superviseur">
    <x-slot name="headerActions">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <form action="{{ route('employee.supervisors.store') }}" method="POST" class="max-w-xl space-y-5">
        @csrf

        @include('employee.supervisors.partials.form')

        {{-- Propriétaire --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <label for="superadmin_id" class="block text-sm font-medium text-stone-700 mb-1.5">
                Compte propriétaire <span class="text-red-500">*</span>
            </label>
            @if($superadmins->count() === 1)
                {{-- Un seul superadmin : sélection automatique, affichage informatif --}}
                <input type="hidden" name="superadmin_id" value="{{ $superadmins->first()->id }}">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-lg text-sm text-stone-700">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    {{ $superadmins->first()->name }}
                    <span class="text-stone-400 text-xs">(seul super-administrateur)</span>
                </div>
            @else
                <select name="superadmin_id" id="superadmin_id" required
                        class="w-full border {{ $errors->has('superadmin_id') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    <option value="">— Choisir un compte —</option>
                    @foreach($superadmins as $admin)
                        <option value="{{ $admin->id }}"
                                {{ old('superadmin_id', auth()->id()) == $admin->id ? 'selected' : '' }}>
                            {{ $admin->name }}
                            @if($admin->id === auth()->id()) (moi) @endif
                        </option>
                    @endforeach
                </select>
                @error('superadmin_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-stone-400 mt-1">Le superviseur ne pourra être géré que par ce compte.</p>
            @endif
        </div>

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
