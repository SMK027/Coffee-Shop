<x-employee-layout title="Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}" subtitle="{{ $order->display_name }}">
    <x-slot name="headerActions">
        <div class="flex items-center gap-3">
            @if(auth()->user()->isAdmin())
            <a href="{{ route('employee.orders.payment', $order) }}"
               class="flex items-center gap-1.5 bg-green-50 hover:bg-green-100 border border-green-200 text-green-700 text-sm font-medium px-3 py-1.5 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Paiement
            </a>
            <a href="{{ route('employee.orders.refund', $order) }}"
               class="flex items-center gap-1.5 bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 text-sm font-medium px-3 py-1.5 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                Remboursement
            </a>
            @endif
            <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
        </div>
    </x-slot>

    <div class="grid lg:grid-cols-3 gap-4 sm:gap-6">

        {{-- Détail commande --}}
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-stone-800">Articles commandés</h2>
                    @if($order->canEditItems())
                        <span class="text-xs font-medium text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">Modifiable</span>
                    @endif
                </div>

                @if($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="divide-y divide-stone-100">
                    @foreach($order->items as $item)
                    <div class="py-3 flex items-center justify-between {{ $item->is_refund ? 'bg-red-50 -mx-4 sm:-mx-6 px-4 sm:px-6' : '' }}">
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
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-sm font-medium {{ $item->is_refund ? 'text-red-700' : 'text-stone-800' }}">x{{ $item->quantity }}</p>
                                <p class="text-xs font-medium {{ $item->is_refund ? 'text-red-600' : 'text-stone-500' }}">{{ number_format($item->subtotal, 2, ',', ' ') }} €</p>
                            </div>
                            @if($order->canEditItems() && !$item->is_refund)
                                <form action="{{ route('employee.orders.items.destroy', [$order, $item]) }}" method="POST"
                                      onsubmit="return confirm('Retirer cet article de la commande ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="Retirer cet article">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($order->canEditItems())
                    <div class="mt-4 pt-4 border-t border-stone-100">
                        <p class="text-xs font-medium text-stone-600 mb-2">Ajouter un article</p>
                        <form action="{{ route('employee.orders.items.store', $order) }}" method="POST" class="space-y-2">
                            @csrf
                            <div class="grid sm:grid-cols-[1fr_auto] gap-2">
                                <div class="relative">
                                    <input type="hidden" name="drink_id" id="add-item-drink-id" value="">
                                    <input type="text" id="add-item-drink-search"
                                           class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                           placeholder="Rechercher une boisson…" autocomplete="off">
                                    <ul id="add-item-drink-dropdown" class="hidden absolute z-20 w-full bg-white border border-stone-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto"></ul>
                                </div>
                                <input type="number" name="quantity" value="1" min="1" max="250"
                                       class="w-full sm:w-24 border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                       placeholder="Qté">
                            </div>
                            <details class="text-xs text-stone-500">
                                <summary class="cursor-pointer select-none">Ou ajouter un article libre</summary>
                                <div class="grid sm:grid-cols-2 gap-2 mt-2">
                                    <input type="text" name="custom_label" maxlength="150" placeholder="Libellé"
                                           class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                    <input type="number" name="custom_price" step="0.01" min="0.01" max="999.99" placeholder="Prix unitaire (€)"
                                           class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                </div>
                            </details>
                            <button type="submit"
                                    class="w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Ajouter à la commande
                            </button>
                        </form>
                    </div>

                    <script>
                    (function () {
                        const drinks = @js($availableDrinks->map(fn($d) => [
                            'id' => $d->id,
                            'name' => $d->name,
                            'price' => (float) $d->price,
                            'category' => $d->category->name ?? '',
                        ]));

                        const searchInput = document.getElementById('add-item-drink-search');
                        const hiddenInput = document.getElementById('add-item-drink-id');
                        const dropdown    = document.getElementById('add-item-drink-dropdown');
                        if (!searchInput) return;

                        let activeIdx = -1;
                        let results = [];

                        function filterDrinks(q) {
                            const s = q.toLowerCase().trim();
                            return s ? drinks.filter(d => d.name.toLowerCase().includes(s) || d.category.toLowerCase().includes(s)) : drinks;
                        }

                        function renderDropdown() {
                            dropdown.innerHTML = '';
                            if (!results.length) {
                                dropdown.innerHTML = '<li class="px-3 py-2.5 text-sm text-stone-400 italic">Aucun résultat</li>';
                                dropdown.classList.remove('hidden');
                                return;
                            }
                            results.forEach((d, i) => {
                                const li = document.createElement('li');
                                li.className = ['flex items-center justify-between gap-3 px-3 py-2.5 cursor-pointer text-sm transition-colors',
                                    i === activeIdx ? 'bg-amber-50' : 'hover:bg-stone-50'].join(' ');
                                li.dataset.id = d.id;
                                li.innerHTML = '<span class="min-w-0 flex items-center gap-1 flex-1 overflow-hidden"><span class="text-xs text-stone-400 flex-shrink-0">' + d.category + '</span><span class="ml-1 font-medium text-stone-800 truncate">' + d.name + '</span></span><span class="text-amber-700 font-semibold whitespace-nowrap text-xs flex-shrink-0 ml-2">' + d.price.toFixed(2).replace('.', ',') + ' €</span>';
                                dropdown.appendChild(li);
                            });
                            dropdown.classList.remove('hidden');
                        }

                        function open() {
                            results = hiddenInput.value ? drinks : filterDrinks(searchInput.value);
                            activeIdx = -1;
                            renderDropdown();
                        }

                        function close() {
                            dropdown.classList.add('hidden');
                            activeIdx = -1;
                        }

                        function pick(d) {
                            hiddenInput.value = d.id;
                            searchInput.value = d.category + ' · ' + d.name;
                            searchInput.classList.remove('border-red-400', 'bg-red-50');
                            close();
                        }

                        searchInput.addEventListener('focus', open);
                        searchInput.addEventListener('input', function () {
                            hiddenInput.value = '';
                            results = filterDrinks(searchInput.value);
                            activeIdx = -1;
                            renderDropdown();
                        });
                        searchInput.addEventListener('keydown', function (e) {
                            if (dropdown.classList.contains('hidden')) {
                                if (e.key === 'ArrowDown') open();
                                return;
                            }
                            if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, results.length - 1); renderDropdown(); }
                            else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); renderDropdown(); }
                            else if (e.key === 'Enter') { e.preventDefault(); if (activeIdx >= 0 && results[activeIdx]) pick(results[activeIdx]); }
                            else if (e.key === 'Escape') { close(); if (!hiddenInput.value) searchInput.value = ''; }
                        });
                        dropdown.addEventListener('mousedown', function (e) { e.preventDefault(); });
                        dropdown.addEventListener('click', function (e) {
                            const li = e.target.closest('li[data-id]');
                            if (li) {
                                const d = drinks.find(x => x.id == li.dataset.id);
                                if (d) pick(d);
                            }
                        });
                        searchInput.addEventListener('blur', function () {
                            setTimeout(function () {
                                close();
                                if (!hiddenInput.value) searchInput.value = '';
                            }, 160);
                        });
                    })();
                    </script>
                @endif
                <div class="mt-4 pt-4 border-t border-stone-100 space-y-1.5">
                        @php
                        $cardOfferDiscount = (float) ($order->card_offer_discount ?? 0);
                        $hasDiscounts = $order->discount_amount > 0
                            || $order->loyalty_discount_amount > 0
                            || $cardOfferDiscount > 0
                            || $order->voucher_discount_amount > 0;
                        $grossTotal = $order->total_amount
                            + $order->discount_amount
                            + $order->loyalty_discount_amount
                            + $cardOfferDiscount
                            + $order->voucher_discount_amount;
                    @endphp
                    @if($hasDiscounts)
                    <div class="flex justify-between text-sm text-stone-500">
                        <span>Sous-total</span>
                        <span>{{ number_format($grossTotal, 2, ',', ' ') }} €</span>
                    </div>
                    @php
                        $usedCardOffers = $order->loyaltyCard?->cardOffers()
                            ->where('used_in_order_id', $order->id)
                            ->where('is_used', true)
                            ->get() ?? collect();
                    @endphp
                    @if($cardOfferDiscount > 0)
                    <div class="flex justify-between text-sm text-amber-700">
                        <span>Réduction offre personnalisée</span>
                        <span>-{{ number_format($cardOfferDiscount, 2, ',', ' ') }} €</span>
                    </div>
                    @endif
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
                    @if($order->voucher_discount_amount > 0)
                    <div class="flex justify-between text-sm text-purple-700">
                        <span>
                            Bon d'achat
                            @if($order->voucher)
                                <span class="font-mono text-xs ml-1">({{ $order->voucher->code }})</span>
                            @endif
                        </span>
                        <span>-{{ number_format($order->voucher_discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @endif
                    @endif
                    <div class="flex justify-between">
                        <span class="font-semibold text-stone-800">Total</span>
                        <span class="font-bold text-stone-800 text-lg">{{ number_format($order->total_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @if($order->refunded_amount > 0)
                    <div class="flex justify-between text-sm text-red-700">
                        <span>Remboursé</span>
                        <span>-{{ number_format($order->refunded_amount, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="flex justify-between font-semibold text-red-800">
                        <span>Net dû</span>
                        <span>{{ number_format(max(0, $order->total_amount - $order->refunded_amount), 2, ',', ' ') }} €</span>
                    </div>
                    @endif
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

            {{-- Paiements enregistrés --}}
            @if($order->payments->isNotEmpty() || $order->refunds->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Paiements</h2>
                <div class="space-y-1.5 text-sm">
                    @foreach($order->payments as $payment)
                    <div class="flex justify-between">
                        <span class="text-stone-600">{{ $payment->paymentMethod->name }}</span>
                        <span class="font-medium text-stone-800">{{ number_format($payment->amount, 2, ',', ' ') }} €</span>
                    </div>
                    @endforeach
                    @if($order->refunds->isNotEmpty())
                        <div class="pt-2 mt-2 border-t border-stone-100 space-y-1.5">
                            <p class="text-xs font-semibold text-red-700 uppercase tracking-wide">Remboursements</p>
                            @foreach($order->refunds as $refund)
                            <div class="flex justify-between">
                                <span class="text-stone-600">{{ $refund->paymentMethod->name }}</span>
                                <span class="font-medium text-red-600">-{{ number_format($refund->amount, 2, ',', ' ') }} €</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif
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
                    @php
                        $usedCardOffers = $order->loyaltyCard?->cardOffers()
                            ->where('used_in_order_id', $order->id)
                            ->where('is_used', true)
                            ->get() ?? collect();
                    @endphp
                    @if($usedCardOffers->isNotEmpty())
                    <div>
                        <dt class="text-stone-500">Offres personnalisées utilisées</dt>
                        <dd class="space-y-0.5 mt-0.5">
                            @foreach($usedCardOffers as $offer)
                            <p class="font-medium text-amber-700 text-sm">
                                {{ $offer->label }}
                                <span class="font-normal text-amber-600">({{ $offer->display_value }})</span>
                            </p>
                            @endforeach
                        </dd>
                    </div>
                    @endif
                    @if($order->points_credited)
                    <div>
                        <dt class="text-stone-500">Points crédités</dt>
                        <dd class="font-medium text-green-700">+{{ $order->points_awarded }} points
                            @if($order->points_refunded > 0)
                                <span class="text-red-600 font-normal text-xs">({{ $order->points_refunded }} pts débités)</span>
                            @endif
                        </dd>
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
                @if($order->requiresPaymentBeforeStatusChange())
                    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                        Un paiement doit être enregistré avant de pouvoir changer le statut de cette commande.
                    </p>
                    <a href="{{ route('employee.orders.payment', $order) }}"
                       class="mt-3 inline-flex items-center justify-center w-full bg-amber-700 hover:bg-amber-600 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                        Enregistrer le paiement
                    </a>
                @else
                <div class="space-y-2">
                    @foreach($availableTransitions as $transition)
                    <form action="{{ route('employee.orders.status', $order) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $transition->key }}">
                        <button type="submit"
                                class="w-full py-3 sm:py-2.5 px-4 rounded-lg text-sm font-medium transition-colors border border-stone-200 shadow-sm {{ $transition->button_class }}">
                            {{ $transition->label }}
                        </button>
                    </form>
                    @endforeach
                </div>
                @endif
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

    {{-- Suppression de la commande (super admin ou superviseur requis) --}}
    <div class="mt-4">
        @if(auth()->user()->isAdmin())
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 sm:p-6">
            <h2 class="font-semibold text-stone-800 mb-3">Supprimer la commande</h2>
            <p class="text-sm text-stone-600 mb-3">La suppression est définitive. La commande doit être au statut <strong>Annulée</strong>.</p>

            <form action="{{ route('employee.orders.destroy', $order) }}" method="POST" onsubmit="return confirm('Supprimer définitivement cette commande ? Cette action est irréversible.');">
                @csrf
                @method('DELETE')

                @unless(auth()->user()->isSuperAdmin())
                    @include('employee.shared.supervisor-auth-fields')
                @endunless

                <div class="mt-3">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-3 rounded-lg">Supprimer la commande</button>
                </div>
            </form>
        </div>
        @endif
    </div>

</x-employee-layout>
