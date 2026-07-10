<x-employee-layout title="Remboursement — Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.show', $order) }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour à la commande</a>
    </x-slot>

    <div class="max-w-2xl space-y-6" x-data="refundForm()">

        {{-- Récap commande --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h2 class="font-semibold text-stone-800 mb-1">Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</h2>
            <p class="text-sm text-stone-500">
                {{ $order->display_name }} —
                <span class="font-medium text-stone-700">{{ number_format($order->total_amount, 2, ',', ' ') }} €</span>
                @if($order->refunded_amount > 0)
                    <span class="ml-2 text-red-600 text-xs font-medium">({{ number_format($order->refunded_amount, 2, ',', ' ') }} € déjà remboursé)</span>
                @endif
            </p>
            @if($order->loyaltyCard && $order->points_awarded > 0)
                <p class="text-xs text-amber-700 mt-1">
                    ★ {{ $order->points_awarded }} points crédités sur la carte {{ chunk_split($order->loyaltyCard->card_number, 4, ' ') }}
                    @if($order->points_refunded > 0)
                        <span class="text-red-600">({{ $order->points_refunded }} pts déjà débités)</span>
                    @endif
                </p>
            @endif
        </div>

        {{-- Sélection des articles --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h2 class="font-semibold text-stone-800 mb-4">Articles à rembourser</h2>

            @if($refundableItems->isEmpty())
                <p class="text-sm text-stone-500 text-center py-4">Tous les articles ont déjà été remboursés.</p>
            @else

            <form action="{{ route('employee.orders.refund.store', $order) }}" method="POST">
                @csrf

                {{-- Boutons sélection rapide --}}
                <div class="flex gap-2 mb-4">
                    <button type="button" @click="selectAll()"
                            class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm font-medium px-3 py-1.5 rounded-lg transition-colors">
                        Tout sélectionner
                    </button>
                    <button type="button" @click="deselectAll()"
                            class="bg-stone-100 hover:bg-stone-200 text-stone-700 text-sm font-medium px-3 py-1.5 rounded-lg transition-colors">
                        Tout décocher
                    </button>
                </div>

                {{-- Liste articles : tout géré dans le scope parent via items[id] --}}
                <div class="divide-y divide-stone-100 mb-6">
                    @foreach($refundableItems as $item)
                    <div class="py-3 flex items-center gap-3">
                        <input type="checkbox"
                               id="item-{{ $item->id }}"
                               x-model="items[{{ $item->id }}].checked"
                               class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500 flex-shrink-0">
                        <label for="item-{{ $item->id }}" class="flex-1 min-w-0 cursor-pointer">
                            <p class="font-medium text-stone-800 text-sm">{{ $item->display_name }}</p>
                            <p class="text-xs text-stone-500">
                                {{ number_format($item->unit_price, 2, ',', ' ') }} €
                                × <span x-text="items[{{ $item->id }}].qty"></span>
                                = <span class="text-red-600 font-medium"
                                        x-text="'−' + ({{ (float) $item->unit_price }} * items[{{ $item->id }}].qty).toFixed(2).replace('.', ',') + ' €'"></span>
                            </p>
                        </label>
                        {{-- Stepper quantité --}}
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button type="button"
                                    @click="items[{{ $item->id }}].qty = Math.max(1, items[{{ $item->id }}].qty - 1)"
                                    :disabled="!items[{{ $item->id }}].checked || items[{{ $item->id }}].qty <= 1"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-stone-300 text-stone-600 hover:bg-stone-100 disabled:opacity-30 disabled:cursor-not-allowed text-base font-bold leading-none">−</button>
                            <span x-text="items[{{ $item->id }}].qty"
                                  class="w-6 text-center text-sm font-medium text-stone-700"></span>
                            <button type="button"
                                    @click="items[{{ $item->id }}].qty = Math.min({{ $item->refundable_qty }}, items[{{ $item->id }}].qty + 1)"
                                    :disabled="!items[{{ $item->id }}].checked || items[{{ $item->id }}].qty >= {{ $item->refundable_qty }}"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-stone-300 text-stone-600 hover:bg-stone-100 disabled:opacity-30 disabled:cursor-not-allowed text-base font-bold leading-none">+</button>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Inputs cachés générés via x-for depuis selectedItems --}}
                <template x-for="(sel, idx) in selectedItems" :key="sel.id">
                    <div>
                        <input type="hidden" :name="'items[' + idx + '][item_id]'" :value="sel.id">
                        <input type="hidden" :name="'items[' + idx + '][qty]'" :value="sel.qty">
                    </div>
                </template>

                {{-- Total --}}
                <div class="border-t border-stone-100 pt-4 mb-5">
                    <div class="flex justify-between text-sm text-stone-600 mb-1">
                        <span>Articles sélectionnés</span>
                        <span x-text="selectedItems.length"></span>
                    </div>
                    <div class="flex justify-between font-semibold text-red-700">
                        <span>Montant à rembourser</span>
                        <span x-text="'−' + totalAmount.toFixed(2).replace('.', ',') + ' €'"></span>
                    </div>
                    @if($order->loyaltyCard)
                    <p class="text-xs text-amber-700 mt-1.5" x-show="pointsToDebit > 0">
                        ★ <span x-text="pointsToDebit"></span> point(s) seront débités de la carte fidélité
                        <span class="text-stone-400">(solde négatif autorisé)</span>
                    </p>
                    @endif
                </div>

                {{-- Moyen de paiement du remboursement --}}
                <div class="mt-4">
                    <label for="payment_method_id" class="block text-sm font-medium text-stone-700 mb-1">Moyen de paiement du remboursement <span class="text-red-500">*</span></label>
                    <select name="payment_method_id" id="payment_method_id" required
                            class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        <option value="">— Choisir —</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                    @error('payment_method_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="mt-3">
                    <label for="refund_reason" class="block text-sm font-medium text-stone-700 mb-1">Motif (optionnel)</label>
                    <input type="text" name="refund_reason" id="refund_reason" value="{{ old('refund_reason') }}"
                           maxlength="255"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                </div>

                @unless(auth()->user()->isSuperAdmin())
                    @include('employee.shared.supervisor-auth-fields')
                @endunless

                <div class="flex gap-3">
                    <button type="submit"
                            :disabled="selectedItems.length === 0"
                            class="bg-red-600 hover:bg-red-500 disabled:bg-stone-300 disabled:cursor-not-allowed text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                        Enregistrer le remboursement
                    </button>
                    <a href="{{ route('employee.orders.show', $order) }}"
                       class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg font-medium text-sm transition-colors">
                        Annuler
                    </a>
                </div>

            </form>

            @endif
        </div>

        {{-- Remboursement total --}}
        @php
            $remainingAmount = round((float) $order->total_amount - (float) $order->refunded_amount, 2);
        @endphp
        @if($remainingAmount > 0)
        <div class="bg-red-50 border border-red-200 rounded-xl p-5">
            <h2 class="font-semibold text-red-800 mb-2">Remboursement total</h2>
            <p class="text-sm text-red-700 mb-4">
                Insère une ligne de remboursement total de
                <strong>{{ number_format($remainingAmount, 2, ',', ' ') }} €</strong>
                et débite tous les points restants.
            </p>
            <form action="{{ route('employee.orders.refund.store', $order) }}" method="POST"
                  onsubmit="return confirm('Confirmer le remboursement total de {{ number_format($remainingAmount, 2, ',', ' ') }} € ?')">
                @csrf
                <input type="hidden" name="total_refund" value="1">

                <div class="mb-4">
                    <label for="payment_method_id_total" class="block text-sm font-medium text-red-800 mb-1">Moyen de paiement du remboursement <span class="text-red-500">*</span></label>
                    <select name="payment_method_id" id="payment_method_id_total" required
                            class="w-full border border-red-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none">
                        <option value="">— Choisir —</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="refund_reason_total" class="block text-sm font-medium text-red-800 mb-1">Motif (optionnel)</label>
                    <input type="text" name="refund_reason" id="refund_reason_total" value="{{ old('refund_reason') }}"
                           maxlength="255"
                           class="w-full border border-red-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-400 focus:border-red-400 outline-none">
                </div>

                @unless(auth()->user()->isSuperAdmin())
                    @include('employee.shared.supervisor-auth-fields')
                @endunless
                <button type="submit"
                        class="bg-red-700 hover:bg-red-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Remboursement total
                </button>
            </form>
        </div>
        @endif

    </div>

</x-employee-layout>

@php
    $itemDefsJson = json_encode($refundableItems->map(fn($i) => [
        'id'     => $i->id,
        'maxQty' => $i->refundable_qty,
        'price'  => (float) $i->unit_price,
        'points' => $i->drink?->loyalty_points ?? 0,
    ])->values());
@endphp
<script>
function refundForm() {
    const itemDefs = {!! $itemDefsJson !!};

    const itemsState = {};
    itemDefs.forEach(def => {
        itemsState[def.id] = { checked: false, qty: def.maxQty };
    });

    return {
        items: itemsState,

        get selectedItems() {
            return itemDefs
                .filter(def => this.items[def.id].checked)
                .map(def => ({ id: def.id, qty: this.items[def.id].qty }));
        },

        get totalAmount() {
            return itemDefs
                .filter(def => this.items[def.id].checked)
                .reduce((sum, def) => sum + def.price * this.items[def.id].qty, 0);
        },

        get pointsToDebit() {
            return itemDefs
                .filter(def => this.items[def.id].checked)
                .reduce((sum, def) => sum + def.points * this.items[def.id].qty, 0);
        },

        selectAll() {
            itemDefs.forEach(def => { this.items[def.id].checked = true; });
        },

        deselectAll() {
            itemDefs.forEach(def => { this.items[def.id].checked = false; });
        },
    };
}
</script>
