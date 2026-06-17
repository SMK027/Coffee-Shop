{{--
    Formulaire partagé entre create et edit.
    Variables attendues : $orderStatus (optionnel, pour edit) et $colors (tableau).
--}}
@php $s = $orderStatus ?? null; @endphp

<div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 sm:p-6 space-y-5">

    {{-- Clé --}}
    <div>
        <x-input-label for="key" value="Clé (identifiant machine)" />
        <x-text-input id="key" name="key" type="text" class="mt-1 block w-full font-mono"
            value="{{ old('key', $s?->key) }}"
            placeholder="ex : en_livraison"
            pattern="[a-z0-9_]+"
            title="Lettres minuscules, chiffres et underscores uniquement"
            {{ $s ? 'readonly' : 'required' }} />
        @if($s)
            <p class="mt-1 text-xs text-stone-400">La clé ne peut pas être modifiée après création (des commandes peuvent y faire référence).</p>
            <input type="hidden" name="key" value="{{ $s->key }}">
        @else
            <p class="mt-1 text-xs text-stone-400">Lettres minuscules, chiffres, underscore. Ex : <code>en_livraison</code></p>
        @endif
        <x-input-error :messages="$errors->get('key')" class="mt-1" />
    </div>

    {{-- Label --}}
    <div>
        <x-input-label for="label" value="Libellé affiché" />
        <x-text-input id="label" name="label" type="text" class="mt-1 block w-full"
            value="{{ old('label', $s?->label) }}"
            placeholder="ex : En livraison"
            required />
        <x-input-error :messages="$errors->get('label')" class="mt-1" />
    </div>

    {{-- Couleur --}}
    <div>
        <x-input-label value="Couleur du badge" />
        <div class="mt-2 flex flex-wrap gap-2">
            @foreach($colors as $colorKey => $colorLabel)
            @php
                $badgeClass = \App\Models\OrderStatus::BADGE_CLASSES[$colorKey];
                $checked = old('color', $s?->color ?? 'gray') === $colorKey;
            @endphp
            <label class="cursor-pointer">
                <input type="radio" name="color" value="{{ $colorKey }}" class="sr-only peer" {{ $checked ? 'checked' : '' }}>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border-2 transition-all
                             {{ $badgeClass }} peer-checked:ring-2 peer-checked:ring-offset-1 peer-checked:ring-stone-600
                             peer-checked:border-stone-600 border-transparent">
                    {{ $colorLabel }}
                </span>
            </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('color')" class="mt-1" />
    </div>

    {{-- Ordre --}}
    <div>
        <x-input-label for="sort_order" value="Ordre d'affichage" />
        <x-text-input id="sort_order" name="sort_order" type="number" min="0" max="9999"
            class="mt-1 block w-32"
            value="{{ old('sort_order', $s?->sort_order ?? 50) }}"
            required />
        <p class="mt-1 text-xs text-stone-400">Les statuts sont affichés et parcourus du plus petit au plus grand.</p>
        <x-input-error :messages="$errors->get('sort_order')" class="mt-1" />
    </div>

    {{-- Type --}}
    <div class="space-y-3">
        <p class="text-sm font-medium text-stone-700">Comportement</p>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="is_terminal" value="1"
                   class="mt-0.5 h-4 w-4 text-amber-700 border-stone-300 rounded"
                   {{ old('is_terminal', $s?->is_terminal) ? 'checked' : '' }}
                   id="is_terminal">
            <div>
                <span class="text-sm font-medium text-stone-800">Statut terminal</span>
                <p class="text-xs text-stone-400 mt-0.5">Une commande dans ce statut ne peut plus évoluer. Utilisez-le pour « Terminée », « Annulée », etc.</p>
            </div>
        </label>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="triggers_loyalty_credit" value="1"
                   class="mt-0.5 h-4 w-4 text-amber-700 border-stone-300 rounded"
                   {{ old('triggers_loyalty_credit', $s?->triggers_loyalty_credit) ? 'checked' : '' }}
                   id="triggers_loyalty_credit">
            <div>
                <span class="text-sm font-medium text-stone-800">Crédite les points de fidélité</span>
                <p class="text-xs text-stone-400 mt-0.5">Les points sont automatiquement attribués à la carte fidélité quand ce statut est atteint. Un seul statut devrait avoir cette option.</p>
            </div>
        </label>
    </div>

</div>
