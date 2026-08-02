<x-employee-layout title="Remboursements">

    {{-- Barre de recherche --}}
    <form id="refunds-search-form" method="GET" action="{{ route('employee.refunds.index') }}"
          class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-stone-100 mb-6 flex flex-wrap gap-2 items-center">
        <div class="flex-1 min-w-[220px] relative">
            <input
                type="text"
                name="q"
                id="refunds-search-input"
                value="{{ $search }}"
                placeholder="Nom du client, n° de carte fidélité ou #ID de commande…"
                oninput="clearTimeout(this._debounce); this._debounce = setTimeout(() => this.form.submit(), 400);"
                class="w-full border border-stone-300 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                autocomplete="off"
                autofocus
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
        </div>
        @if($search !== '')
            <a href="{{ route('employee.refunds.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Effacer
            </a>
        @endif
    </form>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 mb-6 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($search === '')
        {{-- État initial : invite l'utilisateur à rechercher --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 px-6 py-20 text-center">
            <svg class="w-12 h-12 text-stone-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <p class="text-stone-500 text-sm">Saisissez le nom d'un client, son numéro de carte fidélité ou l'identifiant d'une commande pour rechercher les commandes à rembourser.</p>
        </div>

    @elseif($orders->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 px-6 py-20 text-center">
            <p class="text-stone-500 text-sm">Aucune commande terminée trouvée pour « {{ $search }} ».</p>
        </div>

    @else
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
            {{-- Vue desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">#</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Client</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Articles</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Total</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Remboursé</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Date</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($orders as $order)
                        @php
                            $remaining = round((float)$order->total_amount - (float)$order->refunded_amount, 2);
                            $fullyRefunded = $remaining <= 0;
                        @endphp
                        <tr class="hover:bg-stone-50 transition-colors {{ $fullyRefunded ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3 font-mono text-stone-500 text-xs">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-5 py-3 font-medium text-stone-800">
                                {{ $order->customer_name ?? 'Client anonyme' }}
                                @if($order->loyaltyCard)
                                    <span class="ml-1 text-xs text-amber-600 font-normal">★ carte</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-stone-500">{{ $order->items->where('is_refund', false)->count() }} article(s)</td>
                            <td class="px-5 py-3 font-medium text-stone-800">{{ number_format($order->total_amount, 2, ',', ' ') }} €</td>
                            <td class="px-5 py-3">
                                @if($order->refunded_amount > 0)
                                    <span class="text-red-600 font-medium text-xs">
                                        {{ number_format($order->refunded_amount, 2, ',', ' ') }} €
                                        @if($fullyRefunded)
                                            <span class="ml-1 bg-red-100 text-red-700 px-1.5 py-0.5 rounded-full text-xs">Remboursé</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-stone-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-stone-500 text-xs">
                                {{ $order->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if($fullyRefunded)
                                    <span class="text-xs text-stone-400">Déjà remboursé</span>
                                @else
                                    <a href="{{ route('employee.refunds.create', ['order_id' => $order->id]) }}"
                                       class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                        Rembourser
                                    </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vue mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($orders as $order)
                @php
                    $remaining = round((float)$order->total_amount - (float)$order->refunded_amount, 2);
                    $fullyRefunded = $remaining <= 0;
                @endphp
                <div class="px-4 py-3 {{ $fullyRefunded ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-800 text-sm truncate">
                                {{ $order->customer_name ?? 'Client anonyme' }}
                                @if($order->loyaltyCard)
                                    <span class="text-amber-600 text-xs font-normal">★</span>
                                @endif
                            </p>
                            <p class="text-xs text-stone-400 mt-0.5">
                                #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }} — {{ $order->created_at->format('d/m/Y') }}
                            </p>
                            <p class="text-sm font-medium text-stone-700 mt-1">{{ number_format($order->total_amount, 2, ',', ' ') }} €
                                @if($order->refunded_amount > 0)
                                    <span class="text-red-600 text-xs ml-1">({{ number_format($order->refunded_amount, 2, ',', ' ') }} € remboursé)</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            @if($fullyRefunded)
                                <span class="text-xs text-stone-400">Remboursé</span>
                            @else
                                <a href="{{ route('employee.refunds.create', ['order_id' => $order->id]) }}"
                                   class="bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors">
                                    Rembourser
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    @endif

</x-employee-layout>
