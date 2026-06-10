<x-employee-layout title="Commandes">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouvelle commande
        </a>
    </x-slot>

    {{-- Filtres --}}
    <div class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-6 flex flex-wrap gap-2">
        @php
            $statuses = ['all' => 'Toutes'] + \App\Models\Order::STATUS_LABELS;
        @endphp
        @foreach($statuses as $key => $label)
            <a href="{{ route('employee.orders.index', $key !== 'all' ? ['status' => $key] : []) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                      {{ (request('status', 'all') === $key) ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Tableau des commandes --}}
    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($orders->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucune commande trouvée.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">#</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Client</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Articles</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Total</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Date</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($orders as $order)
                        @php
                            $statusColors = [
                                'pending'   => 'bg-stone-100 text-stone-600',
                                'preparing' => 'bg-amber-100 text-amber-700',
                                'serving'   => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <tr class="hover:bg-stone-50 transition-colors">
                            <td class="px-5 py-3 font-mono text-stone-500 text-xs">#{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-5 py-3 font-medium text-stone-800">{{ $order->customer_name }}</td>
                            <td class="px-5 py-3 text-stone-500">{{ $order->items->count() }} article(s)</td>
                            <td class="px-5 py-3 font-medium">{{ number_format($order->total_amount, 2, ',', ' ') }} €</td>
                            <td class="px-5 py-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$order->status] ?? '' }}">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-stone-500 text-xs">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('employee.orders.show', $order) }}" class="text-amber-600 hover:text-amber-700 font-medium text-xs">Voir →</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="px-5 py-4 border-t border-stone-100">
                    {{ $orders->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>

</x-employee-layout>
