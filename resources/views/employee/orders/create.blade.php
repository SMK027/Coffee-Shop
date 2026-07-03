<x-employee-layout title="Nouvelle commande">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    {{-- Indicateur d'étape --}}
    <div class="mb-6 flex items-center gap-3 text-sm">
        <span class="flex items-center gap-2 text-stone-400">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-stone-200 text-stone-500 text-xs font-bold">1</span>
            Identification du client
        </span>
        <span class="text-stone-300 text-lg">→</span>
        <span class="flex items-center gap-2 font-semibold text-amber-700">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-700 text-white text-xs font-bold">2</span>
            Articles
        </span>
    </div>

    {{-- Résumé client (lecture seule, depuis session) --}}
    <div class="max-w-2xl mb-4 bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 flex items-start justify-between gap-4">
        <div class="space-y-1 text-sm">
            @if($customer['use_loyalty'] && $customer['card_full_name'])
                <p class="font-semibold text-stone-800">
                    <span class="text-amber-700">♦</span> {{ $customer['card_full_name'] }}
                    <span class="font-normal text-stone-500 ml-2">— carte fidélité</span>
                    @if($customer['card_points'] !== null)
                        <span class="text-xs text-stone-500">({{ $customer['card_points'] }} pts)</span>
                    @endif
                </p>
            @elseif($customer['customer_name'])
                <p class="font-semibold text-stone-800">{{ $customer['customer_name'] }}</p>
            @else
                <p class="text-stone-500 italic">Commande anonyme</p>
            @endif

            <div class="flex flex-wrap gap-2 mt-1">
                @if($customer['is_employee_order'])
                    <span class="inline-flex px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs font-medium">Salarié −15 %</span>
                @endif
                @if(!empty($customer['loyalty_discount_ids']) && $loyaltyDiscounts->isNotEmpty())
                    @foreach($loyaltyDiscounts as $disc)
                        <span class="inline-flex px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">
                            {{ $disc->name }} ({{ $disc->points_cost }} pts)
                        </span>
                    @endforeach
                @endif
                @if($customer['notes'])
                    <span class="text-xs text-stone-500">📝 {{ $customer['notes'] }}</span>
                @endif
            </div>
        </div>
        <a href="{{ route('employee.orders.identify') }}"
           class="flex-shrink-0 text-xs text-stone-500 hover:text-amber-700 underline underline-offset-2 transition-colors whitespace-nowrap">
            Modifier
        </a>
    </div>

    <div class="max-w-2xl">
        <form action="{{ route('employee.orders.store') }}" method="POST" id="order-form" class="space-y-5">
            @csrf

            {{-- Articles --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Articles</h2>

                @if($errors->hasAny(['items', 'items.*.drink_id', 'items.*.quantity']))
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">
                        Veuillez sélectionner au moins une boisson valide pour chaque article.
                    </div>
                @endif
                @error('items')
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">{{ $message }}</div>
                @enderror

                @if($drinks->isEmpty())
                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4 text-sm text-amber-700">
                        Aucune boisson disponible. <a href="{{ route('employee.drinks.index') }}" class="underline font-medium">Gérer le menu</a>
                    </div>
                @endif

                @php
                    $oldDrinkId0    = old('items.0.drink_id');
                    $oldDrinkObj0   = $oldDrinkId0 ? $drinks->firstWhere('id', $oldDrinkId0) : null;
                    $oldDrinkLabel0 = $oldDrinkObj0 ? $oldDrinkObj0->category->name . ' · ' . $oldDrinkObj0->name : '';
                @endphp

                <div id="items-container" class="space-y-3 mb-4">
                    <div class="item-row flex gap-3 items-start">
                        <div class="flex-1 relative">
                            <input type="hidden" name="items[0][drink_id]" class="drink-id-input" value="{{ $oldDrinkId0 ?? '' }}">
                            <input type="text"
                                   class="drink-search w-full border {{ $errors->has('items.0.drink_id') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                                   placeholder="Rechercher une boisson…"
                                   value="{{ $oldDrinkLabel0 }}"
                                   autocomplete="off">
                            <ul class="drink-dropdown hidden absolute z-20 w-full bg-white border border-stone-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto"></ul>
                        </div>
                        <div class="w-20">
                            <input type="number" name="items[0][quantity]" value="{{ old('items.0.quantity', 1) }}"
                                   min="1" max="20" required
                                   class="qty-input w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                        <button type="button" class="remove-item flex-shrink-0 text-stone-400 hover:text-red-500 mt-2.5 hidden transition-colors" title="Supprimer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" id="add-item"
                            class="flex items-center gap-2 text-amber-700 hover:text-amber-600 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Ajouter une boisson
                    </button>
                    <button type="button" id="add-custom-item"
                            class="flex items-center gap-2 text-stone-500 hover:text-stone-700 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Article libre (hors catalogue)
                    </button>
                </div>

                {{-- Total estimé --}}
                <div class="mt-5 pt-4 border-t border-stone-100 space-y-1.5 text-sm">
                    <div id="points-earned-line" class="hidden justify-between text-amber-600">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Points gagnés
                        </span>
                        <span id="points-earned-display">0 pt</span>
                    </div>
                    <div id="loyalty-discount-line" class="hidden justify-between text-blue-700">
                        <span>Réduction fidélité</span>
                        <span id="loyalty-discount-display">0,00 €</span>
                    </div>
                    <div id="discount-line" class="hidden justify-between text-green-700">
                        <span>Réduction salarié (−15 %)</span>
                        <span id="discount-display">0,00 €</span>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <span>Total estimé</span>
                        <span id="total-display">0,00 €</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Créer la commande
                </button>
                <a href="{{ route('employee.orders.identify') }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    ← Modifier l'identification
                </a>
            </div>
        </form>
    </div>

    @php
        $drinksData = $drinks->map(fn($d) => [
            'id'             => $d->id,
            'name'           => $d->name,
            'price'          => (float) $d->price,
            'category'       => $d->category->name,
            'loyalty_points' => (int) $d->loyalty_points,
        ]);

        $discountsData = $loyaltyDiscounts->map(fn($d) => [
            'type'       => $d->discount_type,
            'value'      => (float) $d->discount_value,
            'max_amount' => $d->max_discount_amount !== null ? (float) $d->max_discount_amount : null,
        ]);
    @endphp

    <script>
    (function () {
        const drinks           = @json($drinksData);
        const sessionDiscounts = @json($discountsData);
        const isEmployee       = @json((bool) $customer['is_employee_order']);
        const EMPLOYEE_RATE    = 0.15;
        let itemCount = 1;

        /* ── Calcul du total ──────────────────────────────────── */
        function updateTotal() {
            let subtotal = 0;
            let totalPoints = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseInt(row.querySelector('.qty-input')?.value || 1, 10);
                if (row.classList.contains('item-row-custom')) {
                    const price = parseFloat(row.querySelector('.custom-price-input')?.value || 0);
                    if (price > 0) subtotal += price * qty;
                } else {
                    const hidden = row.querySelector('.drink-id-input');
                    if (hidden && hidden.value) {
                        const drink = drinks.find(d => d.id == hidden.value);
                        if (drink) {
                            subtotal += drink.price * qty;
                            totalPoints += (drink.loyalty_points || 0) * qty;
                        }
                    }
                }
            });

            const ptLine = document.getElementById('points-earned-line');
            const ptDisp = document.getElementById('points-earned-display');
            if (totalPoints > 0) {
                ptLine.classList.remove('hidden'); ptLine.classList.add('flex');
                ptDisp.textContent = '+' + totalPoints + ' pt' + (totalPoints > 1 ? 's' : '');
            } else {
                ptLine.classList.add('hidden'); ptLine.classList.remove('flex');
            }

            // 1. Réductions fidélité (depuis session)
            let remaining    = subtotal;
            let loyaltyTotal = 0;
            sessionDiscounts.forEach(disc => {
                let amount;
                if (disc.type === 'percent') {
                    amount = Math.round(remaining * (disc.value / 100) * 100) / 100;
                    if (disc.max_amount !== null) amount = Math.min(amount, disc.max_amount);
                } else {
                    amount = Math.round(Math.min(remaining, disc.value) * 100) / 100;
                }
                loyaltyTotal += amount;
                remaining = Math.max(0, remaining - amount);
            });

            const loyaltyLine = document.getElementById('loyalty-discount-line');
            const loyaltyDisp = document.getElementById('loyalty-discount-display');
            if (loyaltyTotal > 0) {
                loyaltyLine.classList.remove('hidden'); loyaltyLine.classList.add('flex');
                loyaltyDisp.textContent = '−' + loyaltyTotal.toFixed(2).replace('.', ',') + ' €';
            } else {
                loyaltyLine.classList.add('hidden'); loyaltyLine.classList.remove('flex');
            }

            // 2. Réduction salarié
            const afterLoyalty = Math.max(0, subtotal - loyaltyTotal);
            const empDiscount  = isEmployee ? Math.round(afterLoyalty * EMPLOYEE_RATE * 100) / 100 : 0;
            const discountLine = document.getElementById('discount-line');
            const discountDisp = document.getElementById('discount-display');
            if (empDiscount > 0) {
                discountLine.classList.remove('hidden'); discountLine.classList.add('flex');
                discountDisp.textContent = '−' + empDiscount.toFixed(2).replace('.', ',') + ' €';
            } else {
                discountLine.classList.add('hidden'); discountLine.classList.remove('flex');
            }

            document.getElementById('total-display').textContent =
                Math.max(0, afterLoyalty - empDiscount).toFixed(2).replace('.', ',') + ' €';
        }

        /* ── Dropdown boissons ─────────────────────────────────── */
        function filterDrinks(q) {
            const s = q.toLowerCase().trim();
            return s ? drinks.filter(d => d.name.toLowerCase().includes(s) || d.category.toLowerCase().includes(s)) : drinks;
        }

        function renderDropdown(dd, results, activeIdx) {
            dd.innerHTML = '';
            if (!results.length) {
                dd.innerHTML = '<li class="px-3 py-2.5 text-sm text-stone-400 italic">Aucun résultat</li>';
                dd.classList.remove('hidden'); return;
            }
            results.forEach((d, i) => {
                const li = document.createElement('li');
                li.className = ['flex items-center justify-between gap-3 px-3 py-2.5 cursor-pointer text-sm transition-colors',
                    i === activeIdx ? 'bg-amber-50' : 'hover:bg-stone-50'].join(' ');
                li.dataset.id = d.id;
                const pts = d.loyalty_points > 0 ? `<span class="flex-shrink-0 ml-1.5 inline-flex items-center gap-0.5 bg-amber-50 border border-amber-200 text-amber-600 text-xs px-1.5 py-0.5 rounded-full">★ ${d.loyalty_points} pt${d.loyalty_points > 1 ? 's' : ''}</span>` : '';
                li.innerHTML = `<span class="min-w-0 flex items-center gap-1 flex-1 overflow-hidden"><span class="text-xs text-stone-400 flex-shrink-0">${d.category}</span><span class="ml-1 font-medium text-stone-800 truncate">${d.name}</span>${pts}</span><span class="text-amber-700 font-semibold whitespace-nowrap text-xs flex-shrink-0 ml-2">${d.price.toFixed(2).replace('.', ',')} €</span>`;
                dd.appendChild(li);
            });
            dd.classList.remove('hidden');
        }

        function initRow(row) {
            const searchInput = row.querySelector('.drink-search');
            const hiddenInput = row.querySelector('.drink-id-input');
            const dropdown    = row.querySelector('.drink-dropdown');
            if (!searchInput) return;
            let activeIdx = -1, results = [];

            const open  = () => { results = row.getAttribute('value') ? drinks : filterDrinks(searchInput.value); activeIdx = -1; renderDropdown(dropdown, results, activeIdx); };
            const close = () => { dropdown.classList.add('hidden'); activeIdx = -1; };
            const pick  = d => { row.setAttribute('value', d.id); hiddenInput.value = d.id; searchInput.value = d.category + ' · ' + d.name; searchInput.classList.remove('border-red-400','bg-red-50'); searchInput.classList.add('border-stone-300'); close(); updateTotal(); };

            searchInput.addEventListener('focus', open);
            searchInput.addEventListener('input', () => { row.removeAttribute('value'); hiddenInput.value = ''; results = filterDrinks(searchInput.value); activeIdx = -1; renderDropdown(dropdown, results, activeIdx); updateTotal(); });
            searchInput.addEventListener('keydown', e => {
                if (dropdown.classList.contains('hidden')) { if (e.key === 'ArrowDown') open(); return; }
                const v = results.filter(d => d.id);
                if (e.key === 'ArrowDown') { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, v.length - 1); renderDropdown(dropdown, results, activeIdx); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); renderDropdown(dropdown, results, activeIdx); }
                else if (e.key === 'Enter') { e.preventDefault(); if (activeIdx >= 0 && v[activeIdx]) pick(v[activeIdx]); }
                else if (e.key === 'Escape') { close(); if (!hiddenInput.value) searchInput.value = ''; }
            });
            dropdown.addEventListener('mousedown', e => e.preventDefault());
            dropdown.addEventListener('click', e => { const li = e.target.closest('li[data-id]'); if (li) { const d = drinks.find(x => x.id == li.dataset.id); if (d) pick(d); } });
            searchInput.addEventListener('blur', () => {
                setTimeout(() => {
                    close();
                    const sel = row.getAttribute('value');
                    if (sel) { const d = drinks.find(x => x.id == sel); if (d) searchInput.value = d.category + ' · ' + d.name; hiddenInput.value = sel; }
                    else { hiddenInput.value = ''; searchInput.value = ''; }
                }, 160);
            });
        }

        function buildRow(index) {
            const div = document.createElement('div');
            div.className = 'item-row flex gap-3 items-start';
            div.innerHTML = `<div class="flex-1 relative"><input type="hidden" name="items[${index}][drink_id]" class="drink-id-input" value=""><input type="text" class="drink-search w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none" placeholder="Rechercher une boisson…" autocomplete="off"><ul class="drink-dropdown hidden absolute z-20 w-full bg-white border border-stone-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto"></ul></div><div class="w-20"><input type="number" name="items[${index}][quantity]" value="1" min="1" max="20" required class="qty-input w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"></div><button type="button" class="remove-item flex-shrink-0 text-stone-400 hover:text-red-500 mt-2.5 transition-colors" title="Supprimer"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
            return div;
        }

        function buildCustomRow(index) {
            const div = document.createElement('div');
            div.className = 'item-row item-row-custom flex gap-3 items-start';
            div.innerHTML = `<div class="flex-1"><input type="text" name="items[${index}][custom_label]" class="custom-label-input w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none" placeholder="Description de l'article" maxlength="150" autocomplete="off"></div><div class="w-28"><input type="number" name="items[${index}][custom_price]" class="custom-price-input w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-right focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none" placeholder="Prix €" min="0.01" max="999.99" step="0.01"></div><div class="w-20"><input type="number" name="items[${index}][quantity]" value="1" min="1" max="20" required class="qty-input w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"></div><button type="button" class="remove-item flex-shrink-0 text-stone-400 hover:text-red-500 mt-2.5 transition-colors" title="Supprimer"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
            div.querySelector('.custom-price-input').addEventListener('input', updateTotal);
            return div;
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => { const btn = row.querySelector('.remove-item'); if (btn) btn.classList.toggle('hidden', rows.length <= 1); });
        }

        document.querySelectorAll('.item-row').forEach(row => { if (!row.classList.contains('item-row-custom')) initRow(row); });
        document.getElementById('add-item').addEventListener('click', () => { const row = buildRow(itemCount++); document.getElementById('items-container').appendChild(row); initRow(row); updateRemoveButtons(); row.querySelector('.drink-search').focus(); });
        document.getElementById('add-custom-item').addEventListener('click', () => { const row = buildCustomRow(itemCount++); document.getElementById('items-container').appendChild(row); updateRemoveButtons(); row.querySelector('.custom-label-input').focus(); });
        document.getElementById('items-container').addEventListener('click', e => { const btn = e.target.closest('.remove-item'); if (btn) { btn.closest('.item-row').remove(); updateTotal(); updateRemoveButtons(); } });
        document.getElementById('items-container').addEventListener('input', e => { if (e.target.classList.contains('qty-input')) updateTotal(); });

        document.getElementById('order-form').addEventListener('submit', function (e) {
            let valid = false;
            document.querySelectorAll('.item-row').forEach(row => {
                if (row.classList.contains('item-row-custom')) {
                    const label = row.querySelector('.custom-label-input')?.value.trim();
                    const price = parseFloat(row.querySelector('.custom-price-input')?.value || 0);
                    if (label && price > 0) valid = true;
                } else {
                    if (row.querySelector('.drink-id-input')?.value) valid = true;
                }
            });
            if (!valid) {
                e.preventDefault();
                let err = document.getElementById('items-client-error');
                if (!err) { err = document.createElement('p'); err.id = 'items-client-error'; err.className = 'text-red-600 text-sm mt-2'; document.getElementById('items-container').before(err); }
                err.textContent = 'Veuillez sélectionner au moins un article.';
            }
        });

        updateRemoveButtons();
        updateTotal();
    })();
    </script>
</x-employee-layout>
