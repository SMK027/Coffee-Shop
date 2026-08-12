<x-employee-layout title="Superviseurs">
    <x-slot name="headerActions">
        @if($isSuperAdmin)
        <a href="{{ route('employee.supervisors.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau superviseur
        </a>
        @endif
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-lg px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('employee.supervisors.index') }}" class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-4 flex flex-wrap gap-2 items-center">
        <input type="text" name="q" placeholder="Rechercher par numéro ou par compte propriétaire…"
               value="{{ $search }}"
               oninput="clearTimeout(this._d); this._d = setTimeout(() => this.form.submit(), 300);"
               class="flex-1 min-w-[220px] border border-stone-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
               autocomplete="off">
        @if($search !== '')
            <a href="{{ route('employee.supervisors.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Effacer</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($supervisors->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>{{ $isSuperAdmin ? 'Aucun superviseur trouvé.' : 'Aucun superviseur rattaché à votre compte.' }}</p>
            </div>
        @else
            {{-- Vue desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Numéro</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Propriétaire</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Créé le</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($supervisors as $supervisor)
                        @php $mine = $supervisor->superadmin_id === $ownerIdForAdmin; @endphp
                        <tr class="hover:bg-stone-50 transition-colors {{ $supervisor->is_active ? '' : 'opacity-70' }}">
                            <td class="px-5 py-3 font-mono text-xs text-stone-700">{{ $supervisor->supervisor_number }}</td>
                            <td class="px-5 py-3">
                                @if($mine)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        Moi
                                    </span>
                                @else
                                    <span class="text-xs text-stone-500">{{ $supervisor->superadmin?->name ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs">
                                @if($supervisor->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-green-100 text-green-700">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-stone-100 text-stone-500">Désactivé</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-stone-400 text-xs">{{ $supervisor->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-right text-xs font-medium">
                                @if($mine)
                                                <a href="{{ route('employee.supervisors.show', $supervisor) }}"
                                                    class="text-sky-600 hover:text-sky-800 transition-colors">Détails</a>
                                    <a href="{{ route('employee.supervisors.edit', $supervisor) }}"
                                                    class="text-stone-500 hover:text-stone-700 transition-colors ml-3">Modifier</a>
                                    <form action="{{ route('employee.supervisors.toggle-activation', $supervisor) }}" method="POST" class="inline-block ml-3">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="{{ $supervisor->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }} transition-colors">
                                            {{ $supervisor->is_active ? 'Désactiver' : 'Réactiver' }}
                                        </button>
                                    </form>
                                    @if($isSuperAdmin)
                                    <form action="{{ route('employee.supervisors.destroy', $supervisor) }}" method="POST" class="inline-block ml-3"
                                          onsubmit="return confirm('Supprimer ce superviseur ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 transition-colors">Supprimer</button>
                                    </form>
                                    @endif
                                @else
                                    <span class="text-stone-300 italic">Lecture seule</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vue mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($supervisors as $supervisor)
                @php $mine = $supervisor->superadmin_id === $ownerIdForAdmin; @endphp
                <div class="px-4 py-3 {{ $supervisor->is_active ? '' : 'opacity-70' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono font-semibold text-stone-800 text-sm">{{ $supervisor->supervisor_number }}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                @if($mine)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Moi</span>
                                @else
                                    <span class="text-xs text-stone-400">{{ $supervisor->superadmin?->name ?? '—' }}</span>
                                @endif
                                @if($supervisor->is_active)
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Actif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs bg-stone-100 text-stone-500">Désactivé</span>
                                @endif
                                <span class="text-xs text-stone-400">{{ $supervisor->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex items-center gap-3 text-xs font-medium">
                            @if($mine)
                                          <a href="{{ route('employee.supervisors.show', $supervisor) }}"
                                              class="text-sky-600 hover:text-sky-800 transition-colors">Détails</a>
                                <a href="{{ route('employee.supervisors.edit', $supervisor) }}"
                                              class="text-stone-500 hover:text-stone-700 transition-colors">Modifier</a>
                                @if($isSuperAdmin)
                                <form action="{{ route('employee.supervisors.destroy', $supervisor) }}" method="POST"
                                      onsubmit="return confirm('Supprimer ce superviseur ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors">Supprimer</button>
                                </form>
                                @endif
                            @else
                                <span class="text-stone-300 italic text-xs">Lecture seule</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-employee-layout>
