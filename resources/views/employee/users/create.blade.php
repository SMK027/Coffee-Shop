<x-employee-layout title="Nouvel employé">
    <x-slot name="headerActions">
        <a href="{{ route('employee.users.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-xl">
        <form action="{{ route('employee.users.store') }}" method="POST" class="space-y-5">
            @csrf
            @include('employee.users._form')
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Créer et envoyer l'invitation
                </button>
                <a href="{{ route('employee.users.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-employee-layout>
