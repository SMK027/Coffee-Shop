<x-employee-layout title="Superviseurs">
    <x-slot name="headerActions">
        <a href="{{ route('employee.supervisors.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau superviseur
        </a>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('employee.supervisors.index') }}" class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-4 flex flex-wrap gap-2 items-center">
        <input type="text" name="q" placeholder="Rechercher un superviseur…"
               value="{{ $search }}"
               class="flex-1 min-w-[220px] border border-stone-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
               autocomplete="off">
        @if($search !== '')
            <a href="{{ route('employee.supervisors.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Effacer</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($supervisors->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucun superviseur n'a encore été ajouté.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Numéro</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Créé le</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($supervisors as $supervisor)
                        <tr class="hover:bg-stone-50 transition-colors {{ $supervisor->is_active ? '' : 'opacity-70' }}">
                            <td class="px-5 py-3 font-mono text-xs text-stone-700">{{ $supervisor->supervisor_number }}</td>
                            <td class="px-5 py-3 text-xs">
                                @if($supervisor->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-700">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-stone-100 text-stone-500">Désactivé</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-stone-400 text-xs">{{ $supervisor->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-right text-xs font-medium">
                                <a href="{{ route('employee.supervisors.edit', $supervisor) }}" class="text-stone-500 hover:text-stone-700 transition-colors">Modifier</a>
                                <form action="{{ route('employee.supervisors.toggle-activation', $supervisor) }}" method="POST" class="inline-block ml-3">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="{{ $supervisor->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }} transition-colors">{{ $supervisor->is_active ? 'Désactiver' : 'Réactiver' }}</button>
                                </form>
                                <form action="{{ route('employee.supervisors.destroy', $supervisor) }}" method="POST" class="inline-block ml-3" onsubmit="return confirm('Supprimer ce superviseur ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-employee-layout>
