<x-employee-layout title="Nouvelle commande">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('employee.orders.store') }}" method="POST" id="order-form" class="space-y-6">
            @csrf

            {{-- Informations client --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <h2 class="font-semibold text-stone-800">Informations client</h2>

                {{-- Choix : carte de fidélité ou non --}}
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="use_loyalty" id="use_loyalty" value="1"
                           {{ old('use_loyalty') ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm font-medium text-stone-700">Le client passe sa carte de fidélité</span>
                </label>

                {{-- Commande salarié : réduction immédiate de 15% --}}
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="is_employee_order" id="is_employee_order" value="1"
                           {{ old('is_employee_order') ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm font-medium text-stone-700">Commande salarié (réduction immédiate de 15%)</span>
                </label>

                {{-- Bloc carte de fidélité --}}
                <div id="loyalty-block" class="{{ old('use_loyalty') ? '' : 'hidden' }} bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div>
                        <label for="loyalty_card_number" class="block text-sm font-medium text-stone-700 mb-1.5">Numéro de carte</label>
                        <input type="text" name="loyalty_card_number" id="loyalty_card_number" inputmode="numeric" maxlength="20"
                               value="{{ old('loyalty_card_number') }}"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono tracking-wider">
                        @error('loyalty_card_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div id="loyalty-check-status" class="mt-3 hidden rounded-lg border px-3 py-2 text-xs"></div>

                    {{-- Code de carte (requis pour utiliser des réductions) --}}
                    <div id="pin-block" class="hidden mt-4">
                        <label for="card_pin" class="block text-sm font-medium text-stone-700 mb-1.5">
                            Code de la carte
                            <span class="font-normal text-stone-400">(requis pour utiliser des réductions)</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="password" id="card_pin" name="card_pin" maxlength="10" inputmode="numeric"
                                   autocomplete="off"
                                   class="flex-1 border border-stone-300 rounded-lg px-4 py-2.5 text-sm font-mono tracking-widest focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <button type="button" id="verify-pin-btn"
                                    class="flex-shrink-0 bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                                Valider le code
                            </button>
                        </div>
                        <div id="pin-status" class="hidden mt-1.5 rounded-lg border px-3 py-2 text-xs"></div>
                        @error('card_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Réductions fidélité (visibles après validation du code) --}}
                    <div id="discounts-block" class="hidden mt-4">
                        <p class="text-sm font-medium text-stone-700 mb-2">Réductions disponibles</p>
                        @if($discounts->isNotEmpty())
                            <div class="space-y-2" id="discounts-list">
                                @foreach($discounts as $discount)
                                <label id="discount-option-{{ $discount->id }}"
                                       class="flex items-start gap-3 p-2.5 rounded-lg border border-stone-200 cursor-pointer hover:bg-stone-50 transition-colors"
                                       data-id="{{ $discount->id }}"
                                       data-points-cost="{{ $discount->points_cost }}"
                                       data-type="{{ $discount->discount_type }}"
                                       data-value="{{ (float) $discount->discount_value }}"
                                       data-max-amount="{{ $discount->max_discount_amount !== null ? (float) $discount->max_discount_amount : '' }}"
                                       data-employee-only="{{ $discount->employee_only ? '1' : '0' }}">
                                    <input type="checkbox" name="loyalty_discount_ids[]" value="{{ $discount->id }}"
                                           class="discount-checkbox mt-0.5 h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                                           @checked(in_array((string)$discount->id, (array)old('loyalty_discount_ids', [])))>
                                    <span class="text-sm leading-tight">
                                        <span class="font-medium text-stone-800">{{ $discount->name }}</span>
                                        <span class="text-stone-500"> — {{ $discount->points_cost }} pts → {{ $discount->display_value }}</span>
                                        @if($discount->employee_only)
                                            <span class="inline-flex ml-1 px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Salariés</span>
                                        @endif
                                    </span>
                                </label>
                                @endforeach
                            </div>
                            <p id="points-summary" class="text-xs text-stone-500 mt-2"></p>
                        @else
                            <p class="text-xs text-stone-500 italic">Aucune réduction active disponible.</p>
                        @endif
                        @error('loyalty_discount_ids')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Nom du client (masqué si carte passée) --}}
                <div id="customer-name-block" class="{{ old('use_loyalty') ? 'hidden' : '' }}">
                    <label for="customer_name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom du client *</label>
                    <input type="text" name="customer_name" id="customer_name" maxlength="100"
                           value="{{ old('customer_name') }}"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('customer_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-stone-700 mb-1.5">Notes (optionnel)</label>
                    <textarea name="notes" id="notes" rows="2" maxlength="500"
                              class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- Articles --}}
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Articles</h2>

                @if($errors->hasAny(['items', 'items.0.drink_id', 'items.*.drink_id', 'items.*.quantity']))
                    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">
                        Veuillez sélectionner au moins une boisson valide pour chaque article.
                    </div>
                @endif

                @if($drinks->isEmpty())
                    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-4 text-sm text-amber-700">
                        Aucune boisson disponible. <a href="{{ route('employee.drinks.index') }}" class="underline font-medium">Gérer le menu</a>
                    </div>
                @endif

                @php
                    $oldDrinkId0  = old('items.0.drink_id');
                    $oldDrinkObj0 = $oldDrinkId0 ? $drinks->firstWhere('id', $oldDrinkId0) : null;
                    $oldDrinkLabel0 = $oldDrinkObj0
                        ? $oldDrinkObj0->category->name . ' · ' . $oldDrinkObj0->name
                        : '';
                @endphp

                <div id="items-container" class="space-y-3 mb-4">
                    <div class="item-row flex gap-3 items-start" {{ $oldDrinkId0 ? 'value="'.$oldDrinkId0.'"' : '' }}>
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

                <button type="button" id="add-item" class="flex items-center gap-2 text-amber-700 hover:text-amber-600 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Ajouter une boisson
                </button>

                <div class="mt-5 pt-4 border-t border-stone-100 space-y-1.5 text-sm">
                    <div id="discount-line" class="hidden justify-between text-green-700">
                        <span>Réduction salarié (-15%)</span>
                        <span id="discount-display">0,00 €</span>
                    </div>
                    <div id="loyalty-discount-line" class="hidden justify-between text-blue-700">
                        <span id="loyalty-discount-label">Réduction fidélité</span>
                        <span id="loyalty-discount-display">0,00 €</span>
                    </div>
                    <div class="flex justify-between font-semibold">
                        <span>Total estimé</span>
                        <span id="total-display">0,00 €</span>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Créer la commande
                </button>
                <a href="{{ route('employee.orders.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>

    @php
        $drinksData = $drinks->map(fn($d) => [
            'id'       => $d->id,
            'name'     => $d->name,
            'price'    => (float) $d->price,
            'category' => $d->category->name,
        ]);
    @endphp

    <script>
    (function () {
        const drinks         = @json($drinksData);
        const loyaltyCheckUrl = @json(url('/espace-employe/commandes/verification-carte-fidelite'));
        const pinVerifyUrl    = @json(url('/espace-employe/commandes/verification-pin-carte'));
        let itemCount = 1;

        /* ── Bascule carte de fidélité / nom du client ─────────────── */
        (function () {
            const toggle       = document.getElementById('use_loyalty');
            const loyaltyBlk   = document.getElementById('loyalty-block');
            const nameBlk      = document.getElementById('customer-name-block');
            const nameInput    = document.getElementById('customer_name');
            const cardInput    = document.getElementById('loyalty_card_number');
            const statusBox    = document.getElementById('loyalty-check-status');
            const employeeTgl  = document.getElementById('is_employee_order');
            const pinBlock     = document.getElementById('pin-block');
            const pinInput     = document.getElementById('card_pin');
            const pinStatusBox = document.getElementById('pin-status');
            const verifyBtn    = document.getElementById('verify-pin-btn');
            const discountsBlk = document.getElementById('discounts-block');
            const pointsSummary = document.getElementById('points-summary');
            let currentCard   = null;
            let pinVerified   = false;
            let debounceTimer = null;
            let requestSeq    = 0;

            /* ── Boîte statut carte ── */
            function setStatus(type, message) {
                if (!statusBox) return;
                statusBox.classList.remove(
                    'hidden',
                    'bg-amber-100', 'border-amber-200', 'text-amber-800',
                    'bg-green-100', 'border-green-200', 'text-green-800',
                    'bg-red-100',   'border-red-200',   'text-red-700'
                );
                if (type === 'loading') statusBox.classList.add('bg-amber-100', 'border-amber-200', 'text-amber-800');
                else if (type === 'success') statusBox.classList.add('bg-green-100', 'border-green-200', 'text-green-800');
                else if (type === 'error') statusBox.classList.add('bg-red-100', 'border-red-200', 'text-red-700');
                statusBox.textContent = message;
            }
            function clearStatus() {
                if (!statusBox) return;
                statusBox.classList.add('hidden');
                statusBox.textContent = '';
            }

            /* ── Boîte statut PIN ── */
            function setPinStatus(type, message) {
                if (!pinStatusBox) return;
                pinStatusBox.classList.remove(
                    'hidden',
                    'bg-amber-100', 'border-amber-200', 'text-amber-800',
                    'bg-green-100', 'border-green-200', 'text-green-800',
                    'bg-red-100',   'border-red-200',   'text-red-700'
                );
                if (type === 'loading') pinStatusBox.classList.add('bg-amber-100', 'border-amber-200', 'text-amber-800');
                else if (type === 'success') pinStatusBox.classList.add('bg-green-100', 'border-green-200', 'text-green-800');
                else if (type === 'error') pinStatusBox.classList.add('bg-red-100', 'border-red-200', 'text-red-700');
                pinStatusBox.textContent = message;
                pinStatusBox.classList.remove('hidden');
            }
            function clearPinStatus() {
                if (!pinStatusBox) return;
                pinStatusBox.classList.add('hidden');
                pinStatusBox.textContent = '';
            }

            /* ── Gestion de l'état PIN/réductions ── */
            function setPinVerified(verified) {
                pinVerified = verified;
                if (discountsBlk) discountsBlk.classList.toggle('hidden', !verified);
                if (!verified) {
                    document.querySelectorAll('.discount-checkbox').forEach(cb => { cb.checked = false; cb.disabled = false; });
                    document.querySelectorAll('[data-id]').forEach(label => {
                        label.classList.remove('opacity-40', 'cursor-not-allowed');
                        label.classList.add('cursor-pointer');
                    });
                    if (pointsSummary) pointsSummary.textContent = '';
                    updateTotal();
                } else {
                    refreshDiscountEligibility();
                }
            }

            function updatePointsSummary() {
                if (!pointsSummary || !currentCard) return;
                let used = 0;
                document.querySelectorAll('.discount-checkbox:checked').forEach(cb => {
                    const lbl = cb.closest('[data-points-cost]');
                    if (lbl) used += parseInt(lbl.dataset.pointsCost || 0, 10);
                });
                const remaining = currentCard.points - used;
                pointsSummary.textContent = used > 0
                    ? `${used} pts utilisés — ${remaining} pts restants sur ${currentCard.points} disponibles.`
                    : `${currentCard.points} pts disponibles.`;
            }

            function refreshDiscountEligibility() {
                if (!pinVerified || !currentCard) return;
                let usedPoints = 0;
                document.querySelectorAll('.discount-checkbox:checked').forEach(cb => {
                    const lbl = cb.closest('[data-points-cost]');
                    if (lbl) usedPoints += parseInt(lbl.dataset.pointsCost || 0, 10);
                });
                document.querySelectorAll('[data-id]').forEach(label => {
                    const cb        = label.querySelector('.discount-checkbox');
                    if (!cb) return;
                    const cost      = parseInt(label.dataset.pointsCost || 0, 10);
                    const empOnly   = label.dataset.employeeOnly === '1';
                    const available = cb.checked ? currentCard.points : currentCard.points - usedPoints;
                    if (empOnly && !currentCard.has_employee_benefits) {
                        cb.disabled = true; cb.checked = false;
                        label.classList.add('opacity-40', 'cursor-not-allowed');
                        label.classList.remove('hover:bg-stone-50', 'cursor-pointer');
                    } else if (!cb.checked && available < cost) {
                        cb.disabled = true;
                        label.classList.add('opacity-40', 'cursor-not-allowed');
                        label.classList.remove('hover:bg-stone-50', 'cursor-pointer');
                    } else {
                        cb.disabled = false;
                        label.classList.remove('opacity-40', 'cursor-not-allowed');
                        label.classList.add('hover:bg-stone-50', 'cursor-pointer');
                    }
                });
                updatePointsSummary();
                updateTotal();
            }

            /* ── Vérification PIN via AJAX ── */
            async function verifyPin() {
                if (!currentCard || !pinInput) return;
                const pin = pinInput.value;
                if (!pin) { setPinStatus('error', 'Saisissez le code de la carte.'); return; }
                setPinStatus('loading', 'Vérification du code…');
                try {
                    const csrf = document.querySelector('input[name="_token"]')?.value || '';
                    const res  = await fetch(pinVerifyUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            card_number: (cardInput?.value || '').trim(),
                            pin: pin,
                        }),
                    });
                    const data = await res.json();
                    if (data.valid) {
                        setPinStatus('success', 'Code validé — sélectionnez les réductions souhaitées.');
                        setPinVerified(true);
                    } else {
                        setPinStatus('error', data.message || 'Code incorrect.');
                        setPinVerified(false);
                    }
                } catch (_) {
                    setPinStatus('error', 'Vérification impossible pour le moment.');
                    setPinVerified(false);
                }
            }

            /* ── Vérification carte via AJAX ── */
            async function checkCard() {
                const raw        = (cardInput?.value || '').trim();
                const normalized = raw.replace(/\s+/g, '');
                if (!toggle.checked || normalized.length < 8) {
                    currentCard = null; clearStatus();
                    if (pinBlock) pinBlock.classList.add('hidden');
                    if (pinInput) pinInput.value = '';
                    clearPinStatus(); setPinVerified(false);
                    return;
                }
                setStatus('loading', 'Vérification de la carte en cours…');
                const seq = ++requestSeq;
                try {
                    const res = await fetch(`${loyaltyCheckUrl}?card_number=${encodeURIComponent(raw)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (seq !== requestSeq) return;
                    if (!res.ok) {
                        currentCard = null; setStatus('error', 'Vérification impossible pour le moment.');
                        if (pinBlock) pinBlock.classList.add('hidden');
                        setPinVerified(false); return;
                    }
                    const data = await res.json();
                    if (!data.found) {
                        currentCard = null; setStatus('error', data.message || 'Carte introuvable.');
                        if (pinBlock) pinBlock.classList.add('hidden');
                        if (pinInput) pinInput.value = '';
                        clearPinStatus(); setPinVerified(false); return;
                    }
                    currentCard = data.card || {};
                    if (currentCard.has_employee_benefits && employeeTgl) {
                        employeeTgl.checked = true; updateTotal();
                    }
                    const benefitsMsg = currentCard.has_employee_benefits ? ' Avantage salarié détecté.' : '';
                    setStatus('success', `Carte valide : ${currentCard.full_name} — ${currentCard.points} pts.${benefitsMsg}`);
                    /* Afficher le champ PIN, réinitialiser la validation précédente */
                    if (pinBlock) pinBlock.classList.remove('hidden');
                    if (pinInput) pinInput.value = '';
                    clearPinStatus(); setPinVerified(false);
                } catch (_) {
                    if (seq !== requestSeq) return;
                    currentCard = null; setStatus('error', 'Vérification impossible pour le moment.');
                    if (pinBlock) pinBlock.classList.add('hidden');
                    setPinVerified(false);
                }
            }

            function queueCheck() {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(checkCard, 350);
            }

            function sync() {
                const useLoyalty = toggle.checked;
                loyaltyBlk.classList.toggle('hidden', !useLoyalty);
                nameBlk.classList.toggle('hidden', useLoyalty);
                if (nameInput) nameInput.required = !useLoyalty;
                if (!useLoyalty) {
                    currentCard = null; clearStatus();
                    if (pinBlock) pinBlock.classList.add('hidden');
                    if (pinInput) pinInput.value = '';
                    clearPinStatus(); setPinVerified(false);
                } else {
                    queueCheck();
                }
            }

            toggle.addEventListener('change', sync);
            if (verifyBtn) verifyBtn.addEventListener('click', verifyPin);
            if (pinInput) {
                pinInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); verifyPin(); } });
                pinInput.addEventListener('input', () => {
                    if (pinVerified) { clearPinStatus(); setPinVerified(false); }
                });
            }
            if (cardInput) {
                cardInput.addEventListener('input', queueCheck);
                cardInput.addEventListener('blur', checkCard);
            }
            document.querySelectorAll('.discount-checkbox').forEach(cb => {
                cb.addEventListener('change', refreshDiscountEligibility);
            });
            sync();
        })();

        /* ── Filtrage ─────────────────────────────────────────── */
        function filterDrinks(query) {
            const q = query.toLowerCase().trim();
            if (!q) return drinks;
            return drinks.filter(d =>
                d.name.toLowerCase().includes(q) || d.category.toLowerCase().includes(q)
            );
        }

        /* ── Affichage du dropdown ─────────────────────────────── */
        function renderDropdown(dropdown, results, activeIdx) {
            dropdown.innerHTML = '';
            if (!results.length) {
                dropdown.innerHTML = '<li class="px-3 py-2.5 text-sm text-stone-400 italic">Aucun résultat</li>';
                dropdown.classList.remove('hidden');
                return;
            }
            results.forEach((d, i) => {
                const li = document.createElement('li');
                li.className = [
                    'flex items-center justify-between gap-3 px-3 py-2.5 cursor-pointer text-sm transition-colors',
                    i === activeIdx ? 'bg-amber-50' : 'hover:bg-stone-50',
                ].join(' ');
                li.dataset.id    = d.id;
                li.dataset.price = d.price;
                li.dataset.label = d.category + ' · ' + d.name;
                li.innerHTML = `
                    <span class="min-w-0">
                        <span class="text-xs text-stone-400">${d.category}</span>
                        <span class="ml-1 font-medium text-stone-800">${d.name}</span>
                    </span>
                    <span class="text-amber-700 font-semibold whitespace-nowrap text-xs">
                        ${d.price.toFixed(2).replace('.', ',')} €
                    </span>`;
                dropdown.appendChild(li);
            });
            dropdown.classList.remove('hidden');
        }

        /* ── Initialisation d'une ligne ────────────────────────── */
        function initRow(row) {
            const searchInput = row.querySelector('.drink-search');
            const hiddenInput = row.querySelector('.drink-id-input');
            const dropdown    = row.querySelector('.drink-dropdown');
            let activeIdx     = -1;
            let results       = [];

            function open() {
                // Source de vérité : attribut value sur le div de la ligne
                results  = row.getAttribute('value') ? drinks : filterDrinks(searchInput.value);
                activeIdx = -1;
                renderDropdown(dropdown, results, activeIdx);
            }

            function close() {
                dropdown.classList.add('hidden');
                activeIdx = -1;
            }

            function pick(drink) {
                row.setAttribute('value', drink.id);
                hiddenInput.value = drink.id;
                searchInput.value = drink.category + ' · ' + drink.name;
                searchInput.classList.remove('border-red-400', 'bg-red-50');
                searchInput.classList.add('border-stone-300');
                close();
                updateTotal();
            }

            searchInput.addEventListener('focus', open);

            searchInput.addEventListener('input', () => {
                row.removeAttribute('value');
                hiddenInput.value = '';
                results   = filterDrinks(searchInput.value);
                activeIdx = -1;
                renderDropdown(dropdown, results, activeIdx);
                updateTotal();
            });

            searchInput.addEventListener('keydown', e => {
                if (dropdown.classList.contains('hidden')) {
                    if (e.key === 'ArrowDown') open();
                    return;
                }
                const validResults = results.filter(d => d.id); // exclude empty msg
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIdx = Math.min(activeIdx + 1, validResults.length - 1);
                    renderDropdown(dropdown, results, activeIdx);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIdx = Math.max(activeIdx - 1, 0);
                    renderDropdown(dropdown, results, activeIdx);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (activeIdx >= 0 && validResults[activeIdx]) pick(validResults[activeIdx]);
                } else if (e.key === 'Escape') {
                    close();
                    if (!hiddenInput.value) searchInput.value = '';
                }
            });

            /* mousedown prevents blur before click fires */
            dropdown.addEventListener('mousedown', e => e.preventDefault());

            dropdown.addEventListener('click', e => {
                const li = e.target.closest('li[data-id]');
                if (!li) return;
                const drink = drinks.find(d => d.id == li.dataset.id);
                if (drink) pick(drink);
            });

            searchInput.addEventListener('blur', () => {
                setTimeout(() => {
                    close();
                    const selectedId = row.getAttribute('value');
                    if (selectedId) {
                        // Restaure le label depuis l'attribut value de la ligne
                        const selected = drinks.find(d => d.id == selectedId);
                        if (selected) searchInput.value = selected.category + ' · ' + selected.name;
                        hiddenInput.value = selectedId; // resync au cas où
                    } else {
                        hiddenInput.value = '';
                        searchInput.value = '';
                    }
                }, 160);
            });
        }

        /* ── Construction HTML d'une ligne dynamique ───────────── */
        function buildRow(index) {
            const div = document.createElement('div');
            div.className = 'item-row flex gap-3 items-start';
            div.innerHTML = `
                <div class="flex-1 relative">
                    <input type="hidden" name="items[${index}][drink_id]" class="drink-id-input" value="">
                    <input type="text"
                           class="drink-search w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
                           placeholder="Rechercher une boisson…"
                           autocomplete="off">
                    <ul class="drink-dropdown hidden absolute z-20 w-full bg-white border border-stone-200 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto"></ul>
                </div>
                <div class="w-20">
                    <input type="number" name="items[${index}][quantity]" value="1" min="1" max="20" required
                           class="qty-input w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                </div>
                <button type="button" class="remove-item flex-shrink-0 text-stone-400 hover:text-red-500 mt-2.5 transition-colors" title="Supprimer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            return div;
        }

        /* ── Init des lignes existantes ────────────────────────── */
        document.querySelectorAll('.item-row').forEach(initRow);

        /* ── Ajout d'une ligne ─────────────────────────────────── */
        document.getElementById('add-item').addEventListener('click', () => {
            const row = buildRow(itemCount++);
            document.getElementById('items-container').appendChild(row);
            initRow(row);
            updateRemoveButtons();
            row.querySelector('.drink-search').focus();
        });

        /* ── Suppression d'une ligne ───────────────────────────── */
        document.getElementById('items-container').addEventListener('click', e => {
            const btn = e.target.closest('.remove-item');
            if (btn) {
                btn.closest('.item-row').remove();
                updateTotal();
                updateRemoveButtons();
            }
        });

        /* ── Mise à jour quantité ──────────────────────────────── */
        document.getElementById('items-container').addEventListener('input', e => {
            if (e.target.classList.contains('qty-input')) updateTotal();
        });

        /* ── Calcul du total ───────────────────────────────────── */
        const EMPLOYEE_DISCOUNT_RATE = 0.15;

        function updateTotal() {
            let subtotal = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const hidden = row.querySelector('.drink-id-input');
                const qty    = row.querySelector('.qty-input');
                if (hidden && hidden.value && qty) {
                    const drink = drinks.find(d => d.id == hidden.value);
                    if (drink) subtotal += drink.price * parseInt(qty.value || 1, 10);
                }
            });

            const empToggle  = document.getElementById('is_employee_order');
            const isEmployee = empToggle && empToggle.checked;
            const employeeDiscount      = isEmployee ? subtotal * EMPLOYEE_DISCOUNT_RATE : 0;
            const subtotalAfterEmployee = subtotal - employeeDiscount;

            /* Réductions fidélité : application séquentielle sur le solde restant */
            let remaining            = subtotalAfterEmployee;
            let totalLoyaltyDiscount = 0;
            let checkedCount         = 0;
            document.querySelectorAll('.discount-checkbox:checked').forEach(cb => {
                const label = cb.closest('[data-type]');
                if (!label) return;
                const type      = label.dataset.type;
                const value     = parseFloat(label.dataset.value) || 0;
                const maxAmount = label.dataset.maxAmount !== '' ? parseFloat(label.dataset.maxAmount) : null;
                let amount;
                if (type === 'percent') {
                    amount = Math.round(remaining * (value / 100) * 100) / 100;
                    if (maxAmount !== null) amount = Math.min(amount, Math.round(maxAmount * 100) / 100);
                } else {
                    amount = Math.round(Math.min(remaining, value) * 100) / 100;
                }
                remaining = Math.max(0, remaining - amount);
                totalLoyaltyDiscount += amount;
                checkedCount++;
            });
            totalLoyaltyDiscount = Math.round(totalLoyaltyDiscount * 100) / 100;
            const total = Math.max(0, subtotalAfterEmployee - totalLoyaltyDiscount);

            const discountLine = document.getElementById('discount-line');
            if (discountLine) {
                discountLine.classList.toggle('hidden', !isEmployee);
                discountLine.classList.toggle('flex', isEmployee);
                document.getElementById('discount-display').textContent =
                    '-' + employeeDiscount.toFixed(2).replace('.', ',') + ' €';
            }

            const loyaltyLine = document.getElementById('loyalty-discount-line');
            if (loyaltyLine) {
                const active = totalLoyaltyDiscount > 0.001;
                loyaltyLine.classList.toggle('hidden', !active);
                loyaltyLine.classList.toggle('flex', active);
                document.getElementById('loyalty-discount-label').textContent =
                    checkedCount > 1 ? `Réductions fidélité (×${checkedCount})` : 'Réduction fidélité';
                document.getElementById('loyalty-discount-display').textContent =
                    '-' + totalLoyaltyDiscount.toFixed(2).replace('.', ',') + ' €';
            }

            document.getElementById('total-display').textContent =
                total.toFixed(2).replace('.', ',') + ' €';
        }

        /* ── Recalcul lors du basculement « commande salarié » ── */
        (function () {
            const empToggle = document.getElementById('is_employee_order');
            if (empToggle) empToggle.addEventListener('change', updateTotal);
        })();


        /* ── Boutons supprimer (masqué si seule ligne) ─────────── */
        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                const btn = row.querySelector('.remove-item');
                if (btn) btn.classList.toggle('hidden', rows.length === 1);
            });
        }

        /* ── Validation avant soumission ───────────────────────── */
        document.getElementById('order-form').addEventListener('submit', e => {
            let invalid = false;
            const useLoyalty = document.getElementById('use_loyalty');
            /* Si des réductions sont cochées sans carte active, les décocher */
            if (!(useLoyalty && useLoyalty.checked)) {
                document.querySelectorAll('.discount-checkbox').forEach(cb => { cb.checked = false; });
            }

            document.querySelectorAll('.item-row').forEach(row => {
                const hidden = row.querySelector('.drink-id-input');
                const search = row.querySelector('.drink-search');
                // Resync le hidden input depuis l'attribut value du row avant validation
                if (row.getAttribute('value')) hidden.value = row.getAttribute('value');
                if (!hidden.value) {
                    search.classList.add('border-red-400', 'bg-red-50');
                    search.classList.remove('border-stone-300');
                    invalid = true;
                }
            });
            if (invalid) {
                e.preventDefault();
                document.querySelector('.item-row .drink-search').focus();
            }
        });

        /* ── Fermer tous les dropdowns au clic extérieur ───────── */
        document.addEventListener('click', e => {
            if (!e.target.closest('.item-row')) {
                document.querySelectorAll('.drink-dropdown').forEach(dd => dd.classList.add('hidden'));
            }
        });

        updateTotal();
    })();
    </script>

</x-employee-layout>
