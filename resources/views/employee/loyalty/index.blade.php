<x-employee-layout title="Cartes de fidélité">
    <x-slot name="headerActions">
        @if(auth()->user()->isSuperAdmin())
        <a href="{{ route('employee.loyalty.settings') }}" class="text-stone-500 hover:text-stone-700 text-sm flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="hidden sm:inline">Réglages</span>
        </a>
        @endif
    </x-slot>

    {{-- Bandeau programme --}}
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4 sm:mb-6 text-sm text-amber-800">
        Les points sont attribués par article commandé. Configurez les points par boisson depuis
        <a href="{{ route('employee.drinks.index') }}" class="font-medium underline hover:text-amber-900">la gestion du menu</a>.
    </div>

    {{-- Recherche --}}
    <form method="GET" class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-stone-100 mb-4 sm:mb-6 flex gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher (numéro, nom, email)…"
               class="flex-1 border border-stone-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
        <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Rechercher</button>
        @if(request('q'))
            <a href="{{ route('employee.loyalty.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">Réinitialiser</a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($cards->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucune carte de fidélité trouvée.</p>
            </div>
        @else
            {{-- Desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">N° carte</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Titulaire</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Email</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Commandes</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Points</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($cards as $card)
                        <tr class="hover:bg-stone-50 transition-colors">
                            <td class="px-5 py-3 font-mono text-stone-500 text-xs">{{ chunk_split($card->card_number, 4, ' ') }}</td>
                            <td class="px-5 py-3 font-medium text-stone-800">{{ $card->full_name }}</td>
                            <td class="px-5 py-3 text-stone-500">{{ $card->email }}</td>
                            <td class="px-5 py-3 text-stone-500">{{ $card->orders_count }}</td>
                            <td class="px-5 py-3 text-right font-bold text-amber-700">{{ $card->points }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('employee.loyalty.show', $card) }}" class="text-amber-700 hover:text-amber-600 font-medium">Détail</a>
                                @if(auth()->user()->isSuperAdmin())
                                    <span class="mx-1 text-stone-300">|</span>
                                    <a href="{{ route('employee.loyalty.show', $card) }}#points-adjustment" class="text-green-700 hover:text-green-600 font-medium">Ajuster les points</a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($cards as $card)
                <a href="{{ route('employee.loyalty.show', $card) }}" class="block px-4 py-3 hover:bg-stone-50 transition-colors">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-medium text-stone-800">{{ $card->full_name }}</span>
                        <span class="font-bold text-amber-700">{{ $card->points }} pts</span>
                    </div>
                    <p class="text-xs font-mono text-stone-500">{{ chunk_split($card->card_number, 4, ' ') }}</p>
                    <p class="text-xs text-stone-400">{{ $card->email }} · {{ $card->orders_count }} commande(s)</p>
                    @if(auth()->user()->isSuperAdmin())
                        <p class="text-xs text-green-700 mt-1">Ajustement de points disponible sur la fiche de détail</p>
                    @endif
                </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($cards->hasPages())
        <div class="mt-4">{{ $cards->links() }}</div>
    @endif

</x-employee-layout>
