<x-employee-layout title="Nouvelle commande">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    {{-- Indicateur d'étape --}}
    <div class="mb-6 flex items-center gap-3 text-sm">
        <span class="flex items-center gap-2 font-semibold text-amber-700">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-700 text-white text-xs font-bold">1</span>
            Identification du client
        </span>
        <span class="text-stone-300 text-lg">→</span>
        <span class="flex items-center gap-2 text-stone-400">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-stone-200 text-stone-500 text-xs font-bold">2</span>
            Articles
        </span>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @php
        $d = $draft ?? [];
        $old = fn(string $key, $default = null) => old($key, $d[$key] ?? $default);
    @endphp

    <div class="max-w-xl">
        <form action="{{ route('employee.orders.identify.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">

                {{-- Carte de fidélité --}}
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="use_loyalty" id="use_loyalty" value="1"
                           {{ $old('use_loyalty') ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm font-medium text-stone-700">Le client passe sa carte de fidélité</span>
                </label>

                {{-- Commande salarié --}}
                <label class="flex items-center gap-3 cursor-pointer select-none">
                    <input type="checkbox" name="is_employee_order" id="is_employee_order" value="1"
                           {{ $old('is_employee_order') ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm font-medium text-stone-700">Commande salarié (réduction immédiate de 15 %)</span>
                </label>

                {{-- Bloc carte de fidélité --}}
                <div id="loyalty-block" class="{{ $old('use_loyalty') ? '' : 'hidden' }} bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-4">

                    {{-- Recherche par nom / prénom / email / téléphone --}}
                    <div>
                        <label for="card-search" class="block text-sm font-medium text-stone-700 mb-1.5">
                            Rechercher un client
                            <span class="font-normal text-stone-400">(nom, prénom, e-mail ou téléphone)</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="card-search" autocomplete="off"
                                   placeholder="Tapez au moins 2 caractères…"
                                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <ul id="card-search-results"
                                class="hidden absolute z-30 w-full bg-white border border-stone-200 rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto"></ul>
                        </div>
                    </div>

                    <div>
                        <label for="loyalty_card_number" class="block text-sm font-medium text-stone-700 mb-1.5">Numéro de carte</label>
                        <input type="text" name="loyalty_card_number" id="loyalty_card_number"
                               inputmode="numeric" maxlength="20"
                               value="{{ $old('loyalty_card_number') }}"
                               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none font-mono tracking-wider">
                        @error('loyalty_card_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        <div id="loyalty-check-status" class="mt-2 hidden rounded-lg border px-3 py-2 text-xs"></div>
                    </div>

                    {{-- Code PIN (requis pour les réductions) --}}
                    <div id="pin-block" class="{{ $old('loyalty_card_number') ? '' : 'hidden' }}">
                        <label for="card_pin" class="block text-sm font-medium text-stone-700 mb-1.5">
                            Code de la carte
                            <span class="font-normal text-stone-400">(requis pour utiliser des réductions)</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="password" id="card_pin" name="card_pin"
                                   maxlength="10" inputmode="numeric" autocomplete="off"
                                   class="flex-1 border border-stone-300 rounded-lg px-4 py-2.5 text-sm font-mono tracking-widest focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <button type="button" id="verify-pin-btn"
                                    class="flex-shrink-0 bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                                Valider le code
                            </button>
                        </div>
                        <div id="pin-status" class="hidden mt-1.5 rounded-lg border px-3 py-2 text-xs"></div>
                        @error('card_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Réductions disponibles (après validation du PIN) --}}
                    <div id="discounts-block" class="hidden">
                        <p class="text-sm font-medium text-stone-700 mb-2">Réductions disponibles</p>
                        @if($discounts->isNotEmpty())
                            <div class="space-y-2" id="discounts-list">
                                @foreach($discounts as $discount)
                                <label id="discount-option-{{ $discount->id }}"
                                       class="flex items-start gap-3 p-2.5 rounded-lg border border-stone-200 cursor-pointer hover:bg-stone-50 transition-colors bg-white"
                                       data-id="{{ $discount->id }}"
                                       data-points-cost="{{ $discount->points_cost }}"
                                       data-type="{{ $discount->discount_type }}"
                                       data-value="{{ (float) $discount->discount_value }}"
                                       data-max-amount="{{ $discount->max_discount_amount !== null ? (float) $discount->max_discount_amount : '' }}"
                                       data-employee-only="{{ $discount->employee_only ? '1' : '0' }}">
                                    <input type="checkbox" name="loyalty_discount_ids[]" value="{{ $discount->id }}"
                                           class="discount-checkbox mt-0.5 h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                                           @checked(in_array((string)$discount->id, (array)old('loyalty_discount_ids', $d['loyalty_discount_ids'] ?? [])))>
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

                {{-- Nom du client (masqué si carte) --}}
                <div id="customer-name-block" class="{{ $old('use_loyalty') ? 'hidden' : '' }}">
                    <label for="customer_name" class="block text-sm font-medium text-stone-700 mb-1.5">
                        Nom du client
                        <span class="font-normal text-stone-400">(optionnel)</span>
                    </label>
                    <input type="text" name="customer_name" id="customer_name"
                           maxlength="100"
                           value="{{ $old('customer_name') }}"
                           placeholder="Laisser vide pour une commande anonyme"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('customer_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-stone-700 mb-1.5">Notes
                        <span class="font-normal text-stone-400">(optionnel)</span>
                    </label>
                    <textarea name="notes" id="notes" rows="2" maxlength="500"
                              class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ $old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors flex items-center gap-2">
                    Continuer vers les articles
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <a href="{{ route('employee.orders.index') }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>

    <script>
    (function () {
        const loyaltyCheckUrl  = @json(route('employee.orders.loyalty-check'));
        const loyaltySearchUrl = @json(route('employee.orders.loyalty-search'));
        const pinVerifyUrl     = @json(route('employee.orders.pin-verify'));

        const toggle       = document.getElementById('use_loyalty');
        const loyaltyBlk   = document.getElementById('loyalty-block');
        const nameBlk      = document.getElementById('customer-name-block');
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

        function setStatus(type, message) {
            statusBox.classList.remove('hidden',
                'bg-amber-100','border-amber-200','text-amber-800',
                'bg-green-100','border-green-200','text-green-800',
                'bg-red-100','border-red-200','text-red-700');
            const cls = {loading:['bg-amber-100','border-amber-200','text-amber-800'],
                         success:['bg-green-100','border-green-200','text-green-800'],
                         error:['bg-red-100','border-red-200','text-red-700']}[type] || [];
            statusBox.classList.add(...cls);
            statusBox.textContent = message;
        }
        function clearStatus() { statusBox.classList.add('hidden'); statusBox.textContent = ''; }

        function setPinStatus(type, message) {
            pinStatusBox.classList.remove('hidden',
                'bg-amber-100','border-amber-200','text-amber-800',
                'bg-green-100','border-green-200','text-green-800',
                'bg-red-100','border-red-200','text-red-700');
            const cls = {loading:['bg-amber-100','border-amber-200','text-amber-800'],
                         success:['bg-green-100','border-green-200','text-green-800'],
                         error:['bg-red-100','border-red-200','text-red-700']}[type] || [];
            pinStatusBox.classList.add(...cls);
            pinStatusBox.textContent = message;
            pinStatusBox.classList.remove('hidden');
        }
        function clearPinStatus() { pinStatusBox.classList.add('hidden'); }

        function setPinVerified(verified) {
            pinVerified = verified;
            discountsBlk.classList.toggle('hidden', !verified);
            if (!verified) {
                document.querySelectorAll('.discount-checkbox').forEach(cb => { cb.checked = false; cb.disabled = false; });
                document.querySelectorAll('[data-id]').forEach(lbl => {
                    lbl.classList.remove('opacity-40','cursor-not-allowed');
                    lbl.classList.add('cursor-pointer');
                });
                if (pointsSummary) pointsSummary.textContent = '';
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
                const cb = label.querySelector('.discount-checkbox');
                if (!cb) return;
                const cost    = parseInt(label.dataset.pointsCost || 0, 10);
                const empOnly = label.dataset.employeeOnly === '1';
                const avail   = cb.checked ? currentCard.points : currentCard.points - usedPoints;
                if ((empOnly && !currentCard.has_employee_benefits) || (!cb.checked && avail < cost)) {
                    cb.disabled = true; cb.checked = false;
                    label.classList.add('opacity-40','cursor-not-allowed');
                    label.classList.remove('hover:bg-stone-50','cursor-pointer');
                } else {
                    cb.disabled = false;
                    label.classList.remove('opacity-40','cursor-not-allowed');
                    label.classList.add('hover:bg-stone-50','cursor-pointer');
                }
            });
            updatePointsSummary();
        }

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
                    body: JSON.stringify({ card_number: (cardInput?.value || '').trim(), pin }),
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

        async function checkCard() {
            const raw        = (cardInput?.value || '').trim();
            const normalized = raw.replace(/\s+/g, '');
            if (!toggle.checked || normalized.length < 8) {
                currentCard = null; clearStatus();
                pinBlock.classList.add('hidden');
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
                if (!res.ok) { currentCard = null; setStatus('error', 'Vérification impossible.'); pinBlock.classList.add('hidden'); setPinVerified(false); return; }
                const data = await res.json();
                if (!data.found) {
                    currentCard = null; setStatus('error', data.message || 'Carte introuvable.');
                    pinBlock.classList.add('hidden');
                    if (pinInput) pinInput.value = '';
                    clearPinStatus(); setPinVerified(false); return;
                }
                currentCard = data.card || {};
                if (currentCard.has_employee_benefits && employeeTgl) employeeTgl.checked = true;
                const benefitsMsg = currentCard.has_employee_benefits ? ' Avantage salarié détecté.' : '';
                setStatus('success', `Carte valide : ${currentCard.full_name} — ${currentCard.points} pts.${benefitsMsg}`);
                pinBlock.classList.remove('hidden');
                if (pinInput) pinInput.value = '';
                clearPinStatus(); setPinVerified(false);
            } catch (_) {
                if (seq !== requestSeq) return;
                currentCard = null; setStatus('error', 'Vérification impossible.');
                pinBlock.classList.add('hidden'); setPinVerified(false);
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
            if (!useLoyalty) {
                currentCard = null; clearStatus();
                pinBlock.classList.add('hidden');
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
            pinInput.addEventListener('input', () => { if (pinVerified) { clearPinStatus(); setPinVerified(false); } });
        }
        if (cardInput) {
            cardInput.addEventListener('input', queueCheck);
            cardInput.addEventListener('blur', checkCard);
        }
        document.querySelectorAll('.discount-checkbox').forEach(cb => {
            cb.addEventListener('change', refreshDiscountEligibility);
        });

        /* ── Recherche de carte par nom / prénom / email / tél ───── */
        (function () {
            const searchInput  = document.getElementById('card-search');
            const resultsList  = document.getElementById('card-search-results');
            if (!searchInput || !resultsList) return;

            let debounce = null;
            let reqSeq   = 0;

            function closeResults() { resultsList.classList.add('hidden'); resultsList.innerHTML = ''; }

            function pickCard(card) {
                // Remplit le numéro de carte et déclenche la vérification AJAX
                cardInput.value = card.card_number;
                searchInput.value = '';
                closeResults();
                checkCard();
            }

            async function doSearch() {
                const q = searchInput.value.trim();
                if (q.length < 2) { closeResults(); return; }
                const seq = ++reqSeq;
                try {
                    const res  = await fetch(`${loyaltySearchUrl}?q=${encodeURIComponent(q)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    if (seq !== reqSeq) return;
                    const data = await res.json();
                    const results = data.results || [];

                    resultsList.innerHTML = '';
                    if (!results.length) {
                        resultsList.innerHTML = '<li class="px-4 py-3 text-sm text-stone-400 italic">Aucun résultat</li>';
                        resultsList.classList.remove('hidden');
                        return;
                    }
                    results.forEach(card => {
                        const li = document.createElement('li');
                        li.className = 'flex items-center justify-between gap-3 px-4 py-2.5 cursor-pointer hover:bg-amber-50 transition-colors text-sm border-b border-stone-50 last:border-0';
                        const empBadge = card.has_employee_benefits
                            ? '<span class="ml-1 px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">Salarié</span>'
                            : '';
                        const contact = [card.email, card.phone].filter(Boolean).join(' · ');
                        li.innerHTML = `
                            <span class="min-w-0">
                                <span class="font-medium text-stone-800">${card.full_name}</span>${empBadge}
                                ${contact ? `<span class="block text-xs text-stone-400 truncate">${contact}</span>` : ''}
                            </span>
                            <span class="flex-shrink-0 text-right">
                                <span class="block font-mono text-xs text-stone-500">${card.card_number}</span>
                                <span class="block text-xs text-amber-700 font-medium">${card.points} pts</span>
                            </span>`;
                        li.addEventListener('mousedown', e => e.preventDefault());
                        li.addEventListener('click', () => pickCard(card));
                        resultsList.appendChild(li);
                    });
                    resultsList.classList.remove('hidden');
                } catch (_) { closeResults(); }
            }

            searchInput.addEventListener('input', () => {
                if (debounce) clearTimeout(debounce);
                debounce = setTimeout(doSearch, 280);
            });
            searchInput.addEventListener('blur', () => setTimeout(closeResults, 200));
            searchInput.addEventListener('keydown', e => {
                if (e.key === 'Escape') { closeResults(); searchInput.value = ''; }
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        })();

        sync();
    })();
    </script>
</x-employee-layout>
