<x-employee-layout title="Statuts de commande">
    <x-slot name="headerActions">
        <a href="{{ route('employee.order-statuses.create') }}"
           class="bg-amber-700 hover:bg-amber-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 sm:gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="hidden sm:inline">Nouveau statut</span>
            <span class="sm:hidden">Nouveau</span>
        </a>
    </x-slot>

    @unless($isSuperAdmin)
        <div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            Les opérations de création et de modification nécessitent la validation d'un superviseur.
        </div>
    @endunless

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">

        @if($statuses->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucun statut configuré.</p>
            </div>
        @else

            {{-- Vue desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Ordre</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Clé</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Label</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Type</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($statuses as $status)
                        <tr class="hover:bg-stone-50 transition-colors {{ $status->is_active ? '' : 'opacity-60' }}">
                            <td class="px-5 py-3 text-stone-500 font-mono text-xs">{{ $status->sort_order }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-stone-700">{{ $status->key }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $status->badge_class }}">
                                    {{ $status->label }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                @if($status->is_terminal)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-600">
                                        Terminal
                                    </span>
                                    @if($status->triggers_loyalty_credit)
                                        <span class="ml-1 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            Crédite fidélité
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                        Progression
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($status->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-500">Désactivé</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('employee.order-statuses.edit', $status) }}"
                                       class="text-stone-500 hover:text-stone-700 text-xs font-medium transition-colors">
                                        Modifier
                                    </a>
                                    @if($isSuperAdmin)
                                        <form action="{{ route('employee.order-statuses.toggle', $status) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="text-xs font-medium transition-colors {{ $status->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }}">
                                                {{ $status->is_active ? 'Désactiver' : 'Réactiver' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('employee.order-statuses.destroy', $status) }}" method="POST"
                                              onsubmit="return confirm('Supprimer le statut « {{ $status->label }} » ?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium transition-colors">
                                                Supprimer
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vue mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($statuses as $status)
                <div class="px-4 py-4 {{ $status->is_active ? '' : 'opacity-60' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $status->badge_class }}">
                                {{ $status->label }}
                            </span>
                            <p class="text-xs text-stone-500 font-mono mt-1">{{ $status->key }} · ordre {{ $status->sort_order }}</p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @if($status->is_terminal)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-stone-100 text-stone-600">Terminal</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-amber-50 text-amber-700">Progression</span>
                                @endif
                                @if($status->triggers_loyalty_credit)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Crédite fidélité</span>
                                @endif
                                @if($status->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-stone-100 text-stone-500">Désactivé</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 flex-shrink-0 text-xs font-medium">
                            <a href="{{ route('employee.order-statuses.edit', $status) }}"
                               class="text-stone-500 hover:text-stone-700 transition-colors">Modifier</a>
                            @if($isSuperAdmin)
                                <form action="{{ route('employee.order-statuses.toggle', $status) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="{{ $status->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800' }} transition-colors">
                                        {{ $status->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </button>
                                </form>
                                <form action="{{ route('employee.order-statuses.destroy', $status) }}" method="POST"
                                      onsubmit="return confirm('Supprimer « {{ $status->label }} » ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition-colors">Supprimer</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        @endif
    </div>

    <p class="mt-4 text-xs text-stone-400">
        L'ordre d'affichage des statuts est défini par le champ <strong>Ordre</strong>.
        Les statuts terminaux ne permettent plus de progression.
        Un seul statut devrait avoir « Crédite fidélité » activé.
    </p>
</x-employee-layout>
