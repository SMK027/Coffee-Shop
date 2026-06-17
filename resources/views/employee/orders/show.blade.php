<x-employee-layout title="Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}" subtitle="{{ $order->display_name }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

        {{-- Détail commande --}}
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Articles commandés</h2>
                <div class="divide-y divide-stone-100">
                    @foreach($order->items as $item)
                    <div class="py-3 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-stone-800 text-sm">
                                {{ $item->display_name }}
                                @if(!$item->drink_id)
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-stone-100 text-stone-500">Article libre</span>
                                @endif
                            </p>
                            <p class="text-xs text-stone-500">{{ number_format($item->unit_price, 2, ',', ' ') }} € l'unité</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-stone-800">x{{ $item->quantity }}</p>
                            <p class="text-xs text-stone-500">{{ number_format($item->subtotal, 2, ',', ' ') }} €</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t border-stone-100 space-y-1.5">
                    @if($order->discount_amount > 0 || $order->loyalty_discount_amount > 0)
                    <div class="flex justify-between text-sm text-stone-500">
                        <span>Sous-total</span>
                        <span>{{ number_format($order->total_amount + $order->discount_amount + $order->loyalty_discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @if($order->loyalty_discount_amount > 0)
                    <div class="flex justify-between text-sm text-blue-700">
                        <span>Réduction{{ $order->loyaltyDiscounts->count() > 1 ? 's' : '' }} fidélité</span>
                        <span>-{{ number_format($order->loyalty_discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @endif
                    @if($order->discount_amount > 0)
                    <div class="flex justify-between text-sm text-green-700">
                        <span>Réduction salarié (-15%)</span>
                        <span>-{{ number_format($order->discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @endif
                    @endif
                    <div class="flex justify-between">
                        <span class="font-semibold text-stone-800">Total</span>
                        <span class="font-bold text-stone-800 text-lg">{{ number_format($order->total_amount, 2, ',', ' ') }} €</span>
                    </div>
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
        <div class="space-y-4 sm:space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Informations</h2>
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-stone-500">Client</dt>
                        <dd class="font-medium text-stone-800">{{ $order->display_name }}</dd>
                    </div>
                    @if($order->is_employee_order)
                    <div>
                        <dt class="text-stone-500">Type</dt>
                        <dd class="font-medium text-green-700">Commande salarié (-15%)</dd>
                    </div>
                    @endif
                    @if($order->loyaltyCard)
                    <div>
                        <dt class="text-stone-500">Carte de fidélité</dt>
                        <dd class="font-medium text-amber-700 font-mono">{{ chunk_split($order->loyaltyCard->card_number, 4, ' ') }}</dd>
                    </div>
                    @if($order->loyaltyDiscounts->isNotEmpty())
                    <div>
                        <dt class="text-stone-500">Réduction{{ $order->loyaltyDiscounts->count() > 1 ? 's' : '' }} fidélité</dt>
                        <dd class="space-y-0.5 mt-0.5">
                            @foreach($order->loyaltyDiscounts as $discount)
                            <p class="font-medium text-blue-700 text-sm">
                                {{ $discount->name }}
                                <span class="font-normal text-blue-600">(-{{ number_format($discount->pivot->discount_amount, 2, ',', ' ') }} € / {{ $discount->pivot->points_spent }} pts)</span>
                            </p>
                            @endforeach
                        </dd>
                    </div>
                    @endif
                    @if($order->points_credited)
                    <div>
                        <dt class="text-stone-500">Points crédités</dt>
                        <dd class="font-medium text-green-700">+{{ $order->points_awarded }} points</dd>
                    </div>
                    @endif
                    @endif
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
            @if($availableTransitions->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Changer le statut</h2>
                <div class="space-y-2">
                    @foreach($availableTransitions as $transition)
                    <form action="{{ route('employee.orders.status', $order) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $transition->key }}">
                        <button type="submit"
                                class="w-full py-3 sm:py-2.5 px-4 rounded-lg text-sm font-medium transition-colors {{ $transition->button_class }}">
                            {{ $transition->label }}
                        </button>
                    </form>
                    @endforeach
                </div>
            </div>
            @else
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                @php
                    $currentOrderStatus = \App\Models\OrderStatus::where('key', $order->status)->first();
                    $isSuccess = $currentOrderStatus?->triggers_loyalty_credit ?? ($order->status === 'completed');
                @endphp
                <p class="text-sm text-stone-500 text-center">
                    Cette commande est <strong class="{{ $isSuccess ? 'text-green-600' : 'text-red-600' }}">{{ $order->status_label }}</strong>.
                </p>
            </div>
            @endif
        </div>
    </div>

</x-employee-layout>
