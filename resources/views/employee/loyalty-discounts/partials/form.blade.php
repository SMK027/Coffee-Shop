<form action="{{ $action }}" method="POST" class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="grid sm:grid-cols-2 gap-5">
        <div class="sm:col-span-2">
            <label for="name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom *</label>
            <input type="text" name="name" id="name" required maxlength="120"
                   value="{{ old('name', $discount->name ?? '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-medium text-stone-700 mb-1.5">Description</label>
            <textarea name="description" id="description" rows="3" maxlength="1000"
                      class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('description', $discount->description ?? '') }}</textarea>
            @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="points_cost" class="block text-sm font-medium text-stone-700 mb-1.5">Cout en points *</label>
            <input type="number" name="points_cost" id="points_cost" min="1" required
                   value="{{ old('points_cost', $discount->points_cost ?? '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('points_cost')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="discount_type" class="block text-sm font-medium text-stone-700 mb-1.5">Type *</label>
            <select name="discount_type" id="discount_type" required class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                <option value="fixed" @selected(old('discount_type', $discount->discount_type ?? '') === 'fixed')>Montant fixe</option>
                <option value="percent" @selected(old('discount_type', $discount->discount_type ?? '') === 'percent')>Pourcentage</option>
            </select>
            @error('discount_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="discount_value" class="block text-sm font-medium text-stone-700 mb-1.5">Valeur *</label>
            <input type="number" step="0.01" name="discount_value" id="discount_value" min="0.01" required
                   value="{{ old('discount_value', $discount->discount_value ?? '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('discount_value')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="quantity_limit" class="block text-sm font-medium text-stone-700 mb-1.5">Quantite max</label>
            <input type="number" name="quantity_limit" id="quantity_limit" min="1"
                   value="{{ old('quantity_limit', $discount->quantity_limit ?? '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            <p class="text-xs text-stone-500 mt-1">Laisser vide pour un stock illimite.</p>
            @error('quantity_limit')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2">
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="is_permanent" id="is_permanent" value="1" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                       @checked(old('is_permanent', $discount->is_permanent ?? true))>
                Active en permanence
            </label>
        </div>

        <div>
            <label for="starts_at" class="block text-sm font-medium text-stone-700 mb-1.5">Debut</label>
            <input type="datetime-local" name="starts_at" id="starts_at"
                   value="{{ old('starts_at', isset($discount->starts_at) ? $discount->starts_at->format('Y-m-d\\TH:i') : '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('starts_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="ends_at" class="block text-sm font-medium text-stone-700 mb-1.5">Fin</label>
            <input type="datetime-local" name="ends_at" id="ends_at"
                   value="{{ old('ends_at', isset($discount->ends_at) ? $discount->ends_at->format('Y-m-d\\TH:i') : '') }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('ends_at')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="sm:col-span-2 grid sm:grid-cols-2 gap-5">
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="is_active" value="1" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                       @checked(old('is_active', $discount->is_active ?? true))>
                Reduction active
            </label>
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="is_sold_out" value="1" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                       @checked(old('is_sold_out', $discount->is_sold_out ?? false))>
                Marquer comme soldee
            </label>
            <label class="flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="employee_only" value="1" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500"
                       @checked(old('employee_only', $discount->employee_only ?? false))>
                Reservee salaries uniquement
            </label>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-amber-700 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
            Enregistrer
        </button>
        <a href="{{ route('employee.loyalty-discounts.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-6 py-2.5 rounded-lg font-medium text-sm transition-colors">
            Annuler
        </a>
    </div>
</form>

<script>
(function () {
    const permanent = document.getElementById('is_permanent');
    const starts = document.getElementById('starts_at');
    const ends = document.getElementById('ends_at');

    function syncScheduleInputs() {
        const disabled = permanent && permanent.checked;
        if (starts) starts.disabled = disabled;
        if (ends) ends.disabled = disabled;
    }

    permanent?.addEventListener('change', syncScheduleInputs);
    syncScheduleInputs();
})();
</script>
