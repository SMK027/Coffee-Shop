<x-visitor-layout title="Détail de commande" description="Consultez le détail de votre commande liée à votre carte de fidélité.">

    <section class="py-12">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-5">
                <a href="{{ route('loyalty.balance.form') }}" class="text-amber-700 hover:text-amber-600 text-sm font-medium underline">
                    ← Retour à mes points
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-stone-100 p-5 sm:p-6 mb-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-stone-800">Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</h1>
                        <p class="text-sm text-stone-500 mt-1">{{ $order->created_at->format('d/m/Y à H:i') }} · {{ $order->status_label }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-stone-400">Total</p>
                        <p class="text-xl font-bold text-stone-800">{{ number_format($order->total_amount, 2, ',', ' ') }} €</p>
                    </div>
                </div>

                @if($order->points_credited)
                    <div class="mt-4 inline-flex items-center rounded-full bg-green-50 text-green-700 px-3 py-1 text-xs font-medium">
                        +{{ $order->points_awarded }} points crédités
                    </div>
                @endif

                @if($order->notes)
                    <div class="mt-4 p-3 bg-amber-50 rounded-lg">
                        <p class="text-xs font-medium text-amber-700 mb-1">Notes</p>
                        <p class="text-sm text-amber-800">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-stone-100 overflow-hidden">
                <h2 class="font-semibold text-stone-800 px-5 py-4 border-b border-stone-100">Articles commandés</h2>
                @if($order->items->isEmpty())
                    <div class="px-5 py-10 text-center text-sm text-stone-500">
                        <p>Aucun article trouvé pour cette commande.</p>
                    </div>
                @else
                    <ul class="divide-y divide-stone-50">
                        @foreach($order->items as $item)
                            <li class="px-5 py-3 flex items-center justify-between {{ $item->is_refund ? 'bg-red-50' : '' }}">
                                <div>
                                    <p class="font-medium text-sm {{ $item->is_refund ? 'text-red-700' : 'text-stone-800' }}">
                                        {{ $item->display_name }}
                                        @if($item->is_refund)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-600">Remboursement</span>
                                        @elseif(!$item->drink_id)
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-stone-100 text-stone-500">Article libre</span>
                                        @endif
                                    </p>
                                    <p class="text-xs {{ $item->is_refund ? 'text-red-500' : 'text-stone-500' }}">{{ number_format($item->unit_price, 2, ',', ' ') }} € l'unité</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium {{ $item->is_refund ? 'text-red-700' : 'text-stone-800' }}">x{{ $item->quantity }}</p>
                                    <p class="text-xs {{ $item->is_refund ? 'text-red-500' : 'text-stone-500' }}">{{ number_format($item->subtotal, 2, ',', ' ') }} €</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

</x-visitor-layout>
