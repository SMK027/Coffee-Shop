<div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">

    <div>
        <label for="category_id" class="block text-sm font-medium text-stone-700 mb-1.5">Catégorie *</label>
        <select name="category_id" id="category_id" required
                class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            <option value="">-- Choisir une catégorie --</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ old('category_id', $drink->category_id ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        @error('category_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-stone-700 mb-1.5">Nom *</label>
        <input type="text" name="name" id="name" required maxlength="150"
               value="{{ old('name', $drink->name ?? '') }}"
               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-stone-700 mb-1.5">Description</label>
        <textarea name="description" id="description" rows="3" maxlength="500"
                  class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none resize-none">{{ old('description', $drink->description ?? '') }}</textarea>
        @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="price" class="block text-sm font-medium text-stone-700 mb-1.5">Prix (€) *</label>
            <input type="number" name="price" id="price" required min="0.01" max="99.99" step="0.01"
                   value="{{ old('price', $drink->price ?? '') }}"
                   x-model="currentPrice"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            @error('price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="sort_order" class="block text-sm font-medium text-stone-700 mb-1.5">Ordre d'affichage</label>
            <input type="number" name="sort_order" id="sort_order" min="0"
                   value="{{ old('sort_order', $drink->sort_order ?? 0) }}"
                   class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
        </div>
    </div>

    <div>
        <label for="loyalty_points" class="block text-sm font-medium text-stone-700 mb-1.5">Points de fidélité par unité vendue</label>
        <input type="number" name="loyalty_points" id="loyalty_points" min="0" max="9999"
               value="{{ old('loyalty_points', $drink->loyalty_points ?? 0) }}"
               class="w-32 border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
        <p class="text-xs text-stone-400 mt-1">Nombre de points crédités par unité commandée. Ex : 15 → 2 unités = 30 points. Laisser à 0 pour ne pas attribuer de points.</p>
        @error('loyalty_points')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-stone-700 mb-1.5">Photo</label>
        @isset($drink)
            @if($drink->image)
                <div class="mb-2">
                    <img src="{{ Storage::url($drink->image) }}" alt="{{ $drink->name }}" loading="lazy" class="h-24 w-24 object-cover rounded-lg border border-stone-200">
                    <p class="text-xs text-stone-500 mt-1">Image actuelle. Téléchargez une nouvelle image pour la remplacer.</p>
                </div>
            @endif
        @endisset
        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
               class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none file:mr-3 file:text-xs file:bg-amber-50 file:text-amber-700 file:border-0 file:rounded file:px-2 file:py-1">
        <p class="text-xs text-stone-400 mt-1">JPEG, PNG ou WebP · max 2 Mo</p>
        @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-center gap-3">
        <input type="hidden" name="available" value="0">
        <input type="checkbox" name="available" id="available" value="1"
               {{ old('available', $drink->available ?? true) ? 'checked' : '' }}
               class="w-4 h-4 text-amber-600 border-stone-300 rounded focus:ring-amber-500">
        <label for="available" class="text-sm font-medium text-stone-700">Boisson disponible à la vente</label>
    </div>

</div>
