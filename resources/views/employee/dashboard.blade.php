<x-employee-layout title="Tableau de bord">

    {{-- Cartes de statistiques --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-stone-100">
            <p class="text-xs font-medium text-stone-500 uppercase tracking-wider">Commandes aujourd'hui</p>
            <p class="text-3xl font-bold text-stone-800 mt-2">{{ $stats['orders_today'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-amber-100">
            <p class="text-xs font-medium text-amber-600 uppercase tracking-wider">En cours</p>
            <p class="text-3xl font-bold text-amber-700 mt-2">{{ $stats['orders_active'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-green-100">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wider">Terminées aujourd'hui</p>
            <p class="text-3xl font-bold text-green-700 mt-2">{{ $stats['orders_completed'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-stone-100">
            <p class="text-xs font-medium text-stone-500 uppercase tracking-wider">Revenu aujourd'hui</p>
            <p class="text-3xl font-bold text-stone-800 mt-2">{{ number_format($stats['revenue_today'], 2, ',', ' ') }} €</p>
        </div>
    </div>

    {{-- Alertes --}}
    @if($stats['pending_testimonials'] > 0 || $stats['new_contacts'] > 0 || $stats['drinks_unavailable'] > 0)
    <div class="grid sm:grid-cols-3 gap-4 mb-8">
        @if($stats['pending_testimonials'] > 0)
        <a href="{{ route('employee.testimonials.index', ['status' => 'pending']) }}" class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 hover:bg-amber-100 transition-colors">
            <div class="w-8 h-8 bg-amber-200 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-amber-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-amber-800 text-sm">{{ $stats['pending_testimonials'] }} témoignage(s) en attente</p>
                <p class="text-xs text-amber-600">À modérer</p>
            </div>
        </a>
        @endif
        @if($stats['new_contacts'] > 0)
        <a href="{{ route('employee.contacts.index', ['status' => 'new']) }}" class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-xl p-4 hover:bg-blue-100 transition-colors">
            <div class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-blue-800 text-sm">{{ $stats['new_contacts'] }} nouveau(x) message(s)</p>
                <p class="text-xs text-blue-600">Non lus</p>
            </div>
        </a>
        @endif
        @if($stats['drinks_unavailable'] > 0)
        <a href="{{ route('employee.drinks.index') }}" class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-xl p-4 hover:bg-red-100 transition-colors">
            <div class="w-8 h-8 bg-red-200 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-red-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-red-800 text-sm">{{ $stats['drinks_unavailable'] }} boisson(s) indisponible(s)</p>
                <p class="text-xs text-red-600">Voir le menu</p>
            </div>
        </a>
        @endif
    </div>
    @endif

    {{-- Commandes en cours --}}
    <div class="bg-white rounded-xl shadow-sm border border-stone-100">
        <div class="px-6 py-4 border-b border-stone-100 flex items-center justify-between">
            <h2 class="font-semibold text-stone-800">Commandes en cours</h2>
            <a href="{{ route('employee.orders.index') }}" class="text-amber-600 hover:text-amber-700 text-sm font-medium">Voir tout →</a>
        </div>
        @if($recent_orders->isEmpty())
            <div class="px-6 py-10 text-center text-stone-500">
                <p class="text-sm">Aucune commande active pour le moment.</p>
                <a href="{{ route('employee.orders.identify') }}" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Créer une commande</a>
            </div>
        @else
            <div class="divide-y divide-stone-50">
                @foreach($recent_orders as $order)
                <a href="{{ route('employee.orders.show', $order) }}" class="px-4 sm:px-6 py-3 sm:py-4 flex items-center gap-3 hover:bg-stone-50 transition-colors">
                    @php
                        $statusColors = [
                            'pending'   => 'bg-stone-100 text-stone-600',
                            'preparing' => 'bg-amber-100 text-amber-700',
                            'serving'   => 'bg-blue-100 text-blue-700',
                            'completed' => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-stone-800 text-sm truncate">{{ $order->customer_name }}</p>
                        <p class="text-xs text-stone-500">{{ $order->items->count() }} article(s) · {{ number_format($order->total_amount, 2, ',', ' ') }} €</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? 'bg-stone-100 text-stone-600' }}">
                            {{ $order->status_label }}
                        </span>
                        <svg class="w-4 h-4 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Accès rapides --}}
    <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('employee.orders.identify') }}" class="bg-amber-700 hover:bg-amber-600 text-white rounded-xl p-5 text-center transition-colors">
            <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <p class="text-sm font-medium">Nouvelle commande</p>
        </a>
        <a href="{{ route('employee.drinks.create') }}" class="bg-white hover:bg-stone-50 border border-stone-200 text-stone-700 rounded-xl p-5 text-center transition-colors">
            <svg class="w-6 h-6 mx-auto mb-2 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <p class="text-sm font-medium">Ajouter une boisson</p>
        </a>
        <a href="{{ route('employee.testimonials.index') }}" class="bg-white hover:bg-stone-50 border border-stone-200 text-stone-700 rounded-xl p-5 text-center transition-colors">
            <svg class="w-6 h-6 mx-auto mb-2 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-sm font-medium">Témoignages</p>
        </a>
        <a href="{{ route('employee.stats.index') }}" class="bg-white hover:bg-stone-50 border border-stone-200 text-stone-700 rounded-xl p-5 text-center transition-colors">
            <svg class="w-6 h-6 mx-auto mb-2 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <p class="text-sm font-medium">Statistiques</p>
        </a>
    </div>

</x-employee-layout>
