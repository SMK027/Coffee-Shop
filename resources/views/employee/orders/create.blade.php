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
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom du client *</label>
                    <input type="text" name="customer_name" id="customer_name" required maxlength="100"
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

                <button type="button" id="add-item" class="flex items-center gap-2 text-amber-700 hover:text-amber-600 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Ajouter une boisson
                </button>

                <div class="mt-5 pt-4 border-t border-stone-100 flex justify-between text-sm font-semibold">
                    <span>Total estimé</span>
                    <span id="total-display">0,00 €</span>
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
        const drinks = @json($drinksData);
        let itemCount = 1;

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
                results  = filterDrinks(searchInput.value);
                activeIdx = -1;
                renderDropdown(dropdown, results, activeIdx);
            }

            function close() {
                dropdown.classList.add('hidden');
                activeIdx = -1;
            }

            function pick(drink) {
                hiddenInput.value = drink.id;
                searchInput.value = drink.category + ' · ' + drink.name;
                searchInput.classList.remove('border-red-400', 'bg-red-50');
                searchInput.classList.add('border-stone-300');
                close();
                updateTotal();
            }

            searchInput.addEventListener('focus', open);

            searchInput.addEventListener('input', () => {
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
                    /* Si le texte ne correspond plus à la boisson sélectionnée, reset */
                    if (!hiddenInput.value) searchInput.value = '';
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
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const hidden = row.querySelector('.drink-id-input');
                const qty    = row.querySelector('.qty-input');
                if (hidden && hidden.value && qty) {
                    const drink = drinks.find(d => d.id == hidden.value);
                    if (drink) total += drink.price * parseInt(qty.value || 1, 10);
                }
            });
            document.getElementById('total-display').textContent =
                total.toFixed(2).replace('.', ',') + ' €';
        }

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
            document.querySelectorAll('.item-row').forEach(row => {
                const hidden = row.querySelector('.drink-id-input');
                const search = row.querySelector('.drink-search');
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
