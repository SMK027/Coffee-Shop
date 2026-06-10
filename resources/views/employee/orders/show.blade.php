<x-employee-layout title="Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}" subtitle="{{ $order->customer_name }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Détail commande --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Articles commandés</h2>
                <div class="divide-y divide-stone-100">
                    @foreach($order->items as $item)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-stone-800 text-sm">{{ $item->drink->name }}</p>
                            <p class="text-xs text-stone-500">{{ number_format($item->unit_price, 2, ',', ' ') }} € l'unité</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-stone-800">x{{ $item->quantity }}</p>
                            <p class="text-xs text-stone-500">{{ number_format($item->subtotal, 2, ',', ' ') }} €</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-stone-100 flex justify-between">
                    <span class="font-semibold text-stone-800">Total</span>
                    <span class="font-bold text-stone-800 text-lg">{{ number_format($order->total_amount, 2, ',', ' ') }} €</span>
                </div>
                @if($order->notes)
                    <div class="mt-4 p-3 bg-amber-50 rounded-lg">
                        <p class="text-xs font-medium text-amber-700 mb-1">Notes</p>
                        <p class="text-sm text-amber-800">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Statut et actions --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Informations</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-stone-500">Client</dt>
                        <dd class="font-medium text-stone-800">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500">Créée le</dt>
                        <dd class="text-stone-800">{{ $order->created_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @if($order->completed_at)
                    <div>
                        <dt class="text-stone-500">Terminée le</dt>
                        <dd class="text-stone-800">{{ $order->completed_at->format('d/m/Y à H:i') }}</dd>
                    </div>
                    @endif
                    @if($order->handler)
                    <div>
                        <dt class="text-stone-500">Géré par</dt>
                        <dd class="text-stone-800">{{ $order->handler->name }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Changement de statut --}}
            @if(!in_array($order->status, ['completed', 'cancelled']))
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Changer le statut</h2>
                @php
                    $nextStatuses = [
                        'pending'   => ['preparing' => 'Démarrer la préparation', 'cancelled' => 'Annuler'],
                        'preparing' => ['serving' => 'Passer au service', 'cancelled' => 'Annuler'],
                        'serving'   => ['completed' => 'Marquer comme terminée', 'cancelled' => 'Annuler'],
                    ];
                    $buttonColors = [
                        'preparing' => 'bg-amber-600 hover:bg-amber-500 text-white',
                        'serving'   => 'bg-blue-600 hover:bg-blue-500 text-white',
                        'completed' => 'bg-green-600 hover:bg-green-500 text-white',
                        'cancelled' => 'bg-red-100 hover:bg-red-200 text-red-700',
                    ];
                @endphp
                <div class="space-y-2">
                    @foreach($nextStatuses[$order->status] ?? [] as $status => $label)
                    <form action="{{ route('employee.orders.status', $order) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $status }}">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-lg text-sm font-medium transition-colors {{ $buttonColors[$status] ?? '' }}">
                            {{ $label }}
                        </button>
                    </form>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <p class="text-sm text-stone-500 text-center">
                    Cette commande est <strong class="{{ $order->status === 'completed' ? 'text-green-600' : 'text-red-600' }}">{{ $order->status_label }}</strong>.
                </p>
            </div>
            @endif
        </div>
    </div>

</x-employee-layout>
