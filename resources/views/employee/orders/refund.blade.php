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
                <button type="submit"
                        class="bg-red-700 hover:bg-red-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Remboursement total
                </button>
            </form>
        </div>
        @endif

    </div>

    @push('scripts')
    <script>
    function refundForm() {
        const itemDefs = @json($refundableItems->map(fn($i) => [
            'id'       => $i->id,
            'maxQty'   => $i->refundable_qty,
            'price'    => (float) $i->unit_price,
            'points'   => $i->drink?->loyalty_points ?? 0,
        ]));

        // État par item dans un seul objet plat — pas de scope imbriqué
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
    @endpush

</x-employee-layout>

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

            <form action="{{ route('employee.orders.refund.store', $order) }}" method="POST" id="refund-form">
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

                {{-- Liste articles --}}
                <div class="divide-y divide-stone-100 mb-6">
                    @foreach($refundableItems as $i => $item)
                    <div class="py-3 flex items-center gap-3"
                         x-data="{ checked: false, qty: {{ $item->refundable_qty }}, maxQty: {{ $item->refundable_qty }} }">
                        <input type="checkbox" id="item-{{ $item->id }}"
                               x-model="checked"
                               @change="onCheck($el, {{ $item->id }}, qty)"
                               class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500 flex-shrink-0">
                        <label for="item-{{ $item->id }}" class="flex-1 min-w-0 cursor-pointer">
                            <p class="font-medium text-stone-800 text-sm">{{ $item->display_name }}</p>
                            <p class="text-xs text-stone-500">
                                {{ number_format($item->unit_price, 2, ',', ' ') }} € × <span x-text="qty"></span>
                                = <span class="text-red-600 font-medium" x-text="'−' + (parseFloat('{{ $item->unit_price }}') * qty).toFixed(2).replace('.', ',') + ' €'"></span>
                            </p>
                        </label>
                        {{-- Quantité --}}
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button type="button"
                                    @click="if(qty > 1) { qty--; updateHidden({{ $item->id }}, qty) }"
                                    :disabled="!checked || qty <= 1"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-stone-300 text-stone-600 hover:bg-stone-100 disabled:opacity-30 disabled:cursor-not-allowed text-base font-bold leading-none">−</button>
                            <span x-text="qty" class="w-6 text-center text-sm font-medium text-stone-700"></span>
                            <button type="button"
                                    @click="if(qty < maxQty) { qty++; updateHidden({{ $item->id }}, qty) }"
                                    :disabled="!checked || qty >= maxQty"
                                    class="w-7 h-7 flex items-center justify-center rounded border border-stone-300 text-stone-600 hover:bg-stone-100 disabled:opacity-30 disabled:cursor-not-allowed text-base font-bold leading-none">+</button>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Inputs cachés gérés par Alpine --}}
                <template x-for="(item, idx) in selectedItems" :key="item.id">
                    <div>
                        <input type="hidden" :name="'items[' + idx + '][item_id]'" :value="item.id">
                        <input type="hidden" :name="'items[' + idx + '][qty]'" :value="item.qty">
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
                <button type="submit"
                        class="bg-red-700 hover:bg-red-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Remboursement total
                </button>
            </form>
        </div>
        @endif

    </div>

    @push('scripts')
    <script>
    function refundForm() {
        const itemsData = @json($refundableItems->map(fn($i) => [
            'id'     => $i->id,
            'points' => $i->drink?->loyalty_points ?? 0,
        ]));

        return {
            selectedItems: [],
            get totalAmount() {
                // calcule depuis les prix dans le DOM via les hidden inputs n'est pas possible,
                // on stocke le prix à la sélection
                return this.selectedItems.reduce((sum, i) => sum + i.amount, 0);
            },
            get pointsToDebit() {
                return this.selectedItems.reduce((sum, i) => sum + i.points, 0);
            },
            onCheck(checkbox, itemId, qty) {
                const itemInfo = itemsData.find(i => i.id === itemId);
                if (checkbox.checked) {
                    this.addItem(itemId, qty, itemInfo?.points ?? 0);
                } else {
                    this.removeItem(itemId);
                }
            },
            addItem(id, qty, pointsPerUnit) {
                // Récupère le prix depuis le label
                const price = parseFloat(
                    document.querySelector(`#item-${id}`)
                        ?.closest('.flex')
                        ?.querySelector('.text-stone-500')
                        ?.textContent?.match(/[\d]+[,.][\d]+/)?.[0]
                        ?.replace(',', '.') ?? '0'
                );
                this.selectedItems.push({ id, qty, amount: price * qty, points: (pointsPerUnit ?? 0) * qty });
            },
            removeItem(id) {
                this.selectedItems = this.selectedItems.filter(i => i.id !== id);
            },
            updateHidden(id, qty) {
                const idx = this.selectedItems.findIndex(i => i.id === id);
                if (idx >= 0) {
                    const price = parseFloat(
                        document.querySelector(`#item-${id}`)
                            ?.closest('.flex')
                            ?.querySelector('.text-stone-500')
                            ?.textContent?.match(/[\d]+[,.][\d]+/)?.[0]
                            ?.replace(',', '.') ?? '0'
                    );
                    const pointsPerUnit = itemsData.find(i => i.id === id)?.points ?? 0;
                    this.selectedItems[idx].qty    = qty;
                    this.selectedItems[idx].amount = price * qty;
                    this.selectedItems[idx].points = pointsPerUnit * qty;
                }
            },
            selectAll() {
                this.selectedItems = [];
                document.querySelectorAll('[id^="item-"]').forEach(cb => {
                    cb.checked = true;
                    cb.dispatchEvent(new Event('change'));
                    // Déclenche Alpine via __x si nécessaire
                });
            },
            deselectAll() {
                document.querySelectorAll('[id^="item-"]').forEach(cb => {
                    if (cb.checked) {
                        cb.checked = false;
                        cb.dispatchEvent(new Event('change'));
                    }
                });
            },
        };
    }
    </script>
    @endpush

</x-employee-layout>
