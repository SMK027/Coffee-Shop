<x-employee-layout title="Nouvelle commande">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <div class="max-w-2xl">
        <form action="{{ route('employee.orders.store') }}" method="POST" id="order-form" class="space-y-6">
            @csrf

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

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
                <h2 class="font-semibold text-stone-800 mb-4">Articles</h2>
                @error('items')<p class="text-red-500 text-xs mb-3">{{ $message }}</p>@enderror

                <div id="items-container" class="space-y-3 mb-4">
                    <div class="item-row flex gap-3 items-start">
                        <div class="flex-1">
                            <select name="items[0][drink_id]" required class="w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                <option value="">-- Choisir une boisson --</option>
                                @foreach($drinks as $drink)
                                    <option value="{{ $drink->id }}" data-price="{{ $drink->price }}"
                                            {{ old('items.0.drink_id') == $drink->id ? 'selected' : '' }}>
                                        {{ $drink->category->name }} · {{ $drink->name }} ({{ number_format($drink->price, 2, ',', ' ') }} €)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-20">
                            <input type="number" name="items[0][quantity]" value="{{ old('items.0.quantity', 1) }}"
                                   min="1" max="20" required
                                   class="w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                        <button type="button" class="remove-item text-stone-400 hover:text-red-500 mt-2.5 hidden transition-colors">
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

    <script>
    (function() {
        const drinks = @json($drinks->map(fn($d) => ['id' => $d->id, 'name' => $d->name, 'price' => (float)$d->price, 'category' => $d->category->name]));
        let itemCount = 1;

        function buildSelect(index, selectedId = '') {
            let opts = '<option value="">-- Choisir une boisson --</option>';
            drinks.forEach(d => {
                opts += `<option value="${d.id}" data-price="${d.price}" ${selectedId == d.id ? 'selected' : ''}>${d.category} · ${d.name} (${d.price.toFixed(2).replace('.', ',')} €)</option>`;
            });
            return `<select name="items[${index}][drink_id]" required class="w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">${opts}</select>`;
        }

        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row flex gap-3 items-start';
            row.innerHTML = `<div class="flex-1">${buildSelect(itemCount)}</div>
                <div class="w-20"><input type="number" name="items[${itemCount}][quantity]" value="1" min="1" max="20" required class="w-full border border-stone-300 rounded-lg px-3 py-2.5 text-sm text-center focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"></div>
                <button type="button" class="remove-item text-stone-400 hover:text-red-500 mt-2.5 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>`;
            container.appendChild(row);
            itemCount++;
            updateTotal();
            updateRemoveButtons();
        });

        document.getElementById('items-container').addEventListener('change', updateTotal);
        document.getElementById('items-container').addEventListener('input', updateTotal);
        document.getElementById('items-container').addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-item');
            if (btn) { btn.closest('.item-row').remove(); updateTotal(); updateRemoveButtons(); }
        });

        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const select = row.querySelector('select');
                const qty = row.querySelector('input[type=number]');
                if (select && select.value && qty) {
                    const opt = select.options[select.selectedIndex];
                    total += parseFloat(opt.dataset.price || 0) * parseInt(qty.value || 1);
                }
            });
            document.getElementById('total-display').textContent = total.toFixed(2).replace('.', ',') + ' €';
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach((row, i) => {
                const btn = row.querySelector('.remove-item');
                if (btn) btn.classList.toggle('hidden', rows.length === 1);
            });
        }

        updateTotal();
    })();
    </script>

</x-employee-layout>
