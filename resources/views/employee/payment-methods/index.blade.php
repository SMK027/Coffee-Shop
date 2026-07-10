<x-employee-layout title="Moyens de paiement">
    <x-slot name="headerActions">
        <a href="{{ route('employee.payment-methods.create') }}"
           class="bg-amber-700 hover:bg-amber-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 sm:gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="hidden sm:inline">Nouveau moyen</span>
            <span class="sm:hidden">Nouveau</span>
        </a>
    </x-slot>

    @unless($isSuperAdmin)
        <div class="mb-4 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
            La création, la modification et la désactivation nécessitent la validation d'un superviseur ou un compte super administrateur.
        </div>
    @endunless

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($methods->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucun moyen de paiement configuré.</p>
            </div>
        @else
            {{-- Desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Ordre</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Nom</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Slug</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($methods as $method)
                        <tr class="hover:bg-stone-50 transition-colors {{ $method->is_active ? '' : 'opacity-60' }}">
                            <td class="px-5 py-3 text-stone-500 font-mono text-xs">{{ $method->sort_order }}</td>
                            <td class="px-5 py-3 font-medium text-stone-800">{{ $method->name }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-stone-600">{{ $method->slug }}</td>
                            <td class="px-5 py-3">
                                @if($method->is_active)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Actif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-500">Désactivé</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('employee.payment-methods.edit', $method) }}"
                                       class="text-stone-500 hover:text-stone-700 text-xs font-medium transition-colors">
                                        Modifier
                                    </a>

                                    {{-- Toggle activation --}}
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open"
                                                class="text-xs font-medium transition-colors {{ $method->is_active ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' }}">
                                            {{ $method->is_active ? 'Désactiver' : 'Activer' }}
                                        </button>

                                        <div x-show="open" x-cloak @click.outside="open = false"
                                             class="absolute right-0 mt-2 z-20 bg-white border border-stone-200 rounded-xl shadow-lg p-4 w-80">
                                            <p class="text-sm font-semibold text-stone-800 mb-3">
                                                {{ $method->is_active ? 'Désactiver' : 'Activer' }} « {{ $method->name }} »
                                            </p>
                                            <form action="{{ route('employee.payment-methods.toggle', $method) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                @unless($isSuperAdmin)
                                                    @include('employee.shared.supervisor-auth-fields')
                                                @endunless
                                                <div class="flex gap-2 mt-3">
                                                    <button type="submit"
                                                            class="flex-1 py-2 rounded-lg text-sm font-medium text-white transition-colors {{ $method->is_active ? 'bg-red-600 hover:bg-red-500' : 'bg-green-600 hover:bg-green-500' }}">
                                                        Confirmer
                                                    </button>
                                                    <button type="button" @click="open = false"
                                                            class="flex-1 py-2 rounded-lg text-sm font-medium bg-stone-100 hover:bg-stone-200 text-stone-700 transition-colors">
                                                        Annuler
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($methods as $method)
                <div class="px-4 py-4 {{ $method->is_active ? '' : 'opacity-60' }}" x-data="{ open: false }">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-medium text-stone-800">{{ $method->name }}</span>
                        @if($method->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Actif</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-stone-100 text-stone-500">Désactivé</span>
                        @endif
                    </div>
                    <p class="text-xs text-stone-500 font-mono mb-3">{{ $method->slug }}</p>

                    <div class="flex gap-3">
                        <a href="{{ route('employee.payment-methods.edit', $method) }}"
                           class="text-stone-600 hover:text-stone-800 text-xs font-medium transition-colors">Modifier</a>
                        <button type="button" @click="open = !open"
                                class="text-xs font-medium {{ $method->is_active ? 'text-red-500 hover:text-red-700' : 'text-green-600 hover:text-green-800' }}">
                            {{ $method->is_active ? 'Désactiver' : 'Activer' }}
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="mt-3 border border-stone-200 rounded-xl p-4 bg-stone-50">
                        <p class="text-sm font-semibold text-stone-800 mb-3">
                            {{ $method->is_active ? 'Désactiver' : 'Activer' }} « {{ $method->name }} »
                        </p>
                        <form action="{{ route('employee.payment-methods.toggle', $method) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            @unless($isSuperAdmin)
                                @include('employee.shared.supervisor-auth-fields')
                            @endunless
                            <div class="flex gap-2 mt-3">
                                <button type="submit"
                                        class="flex-1 py-2 rounded-lg text-sm font-medium text-white transition-colors {{ $method->is_active ? 'bg-red-600 hover:bg-red-500' : 'bg-green-600 hover:bg-green-500' }}">
                                    Confirmer
                                </button>
                                <button type="button" @click="open = false"
                                        class="flex-1 py-2 rounded-lg text-sm font-medium bg-stone-100 hover:bg-stone-200 text-stone-700 transition-colors">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-employee-layout>
