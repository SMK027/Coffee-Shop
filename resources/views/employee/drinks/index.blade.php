<x-employee-layout title="Gestion du menu">
    <x-slot name="headerActions">
        <a href="{{ route('employee.drinks.create') }}" class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ajouter une boisson
        </a>
    </x-slot>

    <form id="drinks-search-form" method="GET" action="{{ route('employee.drinks.index') }}" class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-4 flex flex-wrap gap-2 items-center">
        <input
            type="text"
            name="q"
            id="drinks-search-input"
            value="{{ request('q') }}"
            placeholder="Rechercher une boisson (nom, description)…"
            oninput="clearTimeout(this._debounce); this._debounce = setTimeout(() => this.form.submit(), 300);"
            class="flex-1 min-w-[220px] border border-stone-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none"
            autocomplete="off"
        >
        @if(request()->filled('q'))
            <a href="{{ route('employee.drinks.index') }}" class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Effacer
            </a>
        @endif
    </form>

    <form id="bulk-disable-form" method="POST" action="{{ route('employee.drinks.bulk-disable') }}" data-disable-action="{{ route('employee.drinks.bulk-disable') }}" data-enable-action="{{ route('employee.drinks.bulk-enable') }}" class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-4 flex flex-wrap items-center gap-2 sm:gap-3">
        @csrf
        <button
            type="button"
            id="bulk-select-all"
            class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
        >
            Tout sélectionner
        </button>
        <button
            type="button"
            id="bulk-unselect-all"
            class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
        >
            Tout décocher
        </button>
        <div class="text-sm text-stone-600">
            <span id="bulk-selected-count" class="font-semibold text-stone-800">0</span>
            boisson(s) sélectionnée(s)
        </div>
        <button
            type="submit"
            id="bulk-disable-submit"
            disabled
            onclick="return confirm('Désactiver toutes les boissons sélectionnées ?');"
            class="bg-red-600 hover:bg-red-500 disabled:bg-stone-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
        >
            Désactiver la sélection
        </button>
    </form>

    @foreach($categories as $category)
    <div class="bg-white rounded-xl shadow-sm border border-stone-100 mb-6 overflow-hidden">
        <div class="px-6 py-4 bg-stone-50 border-b border-stone-100">
            <h2 class="font-semibold text-stone-800">{{ $category->name }}</h2>
        </div>
        @if($category->drinks->isEmpty())
            <p class="px-6 py-4 text-sm text-stone-500">Aucune boisson dans cette catégorie.</p>
        @else
            <div class="divide-y divide-stone-50">
                @foreach($category->drinks as $drink)
                <div class="px-4 sm:px-6 py-3 sm:py-4 flex items-center gap-3 sm:gap-4">
                    <label class="flex items-center" title="Sélectionner pour la désactivation en masse">
                        <input
                            type="checkbox"
                            class="drink-bulk-checkbox h-4 w-4 rounded border-stone-300 text-red-600 focus:ring-red-500"
                            form="bulk-disable-form"
                            name="drink_ids[]"
                            value="{{ $drink->id }}"
                            data-available="{{ $drink->available ? '1' : '0' }}"
                        >
                    </label>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-medium text-stone-800 text-sm">{{ $drink->name }}</p>
                            @if(!$drink->available)
                                <span class="px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded-full">Indisponible</span>
                            @endif
                            @if($drink->loyalty_points > 0)
                                <span class="inline-flex items-center gap-0.5 bg-amber-50 border border-amber-200 text-amber-700 text-xs px-1.5 py-0.5 rounded-full">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    {{ $drink->loyalty_points }} pt{{ $drink->loyalty_points > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                        @if($drink->description)
                            <p class="text-xs text-stone-500 mt-0.5 line-clamp-1">{{ $drink->description }}</p>
                        @endif
                    </div>
                    <div class="font-semibold text-stone-700 text-sm flex-shrink-0 w-16 text-right">
                        {{ number_format($drink->price, 2, ',', ' ') }} €
                    </div>
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-shrink-0">
                        <form action="{{ route('employee.drinks.toggle', $drink) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $drink->available ? 'Rendre indisponible' : 'Rendre disponible' }}"
                                    class="p-2 sm:p-1.5 rounded-lg transition-colors {{ $drink->available ? 'text-green-600 hover:bg-green-50' : 'text-stone-400 hover:bg-stone-100' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $drink->available ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' : 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21' }}"/>
                                </svg>
                            </button>
                        </form>
                        <a href="{{ route('employee.drinks.edit', $drink) }}" class="p-2 sm:p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Modifier">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <form action="{{ route('employee.drinks.destroy', $drink) }}" method="POST"
                              onsubmit="return confirm('Supprimer {{ addslashes($drink->name) }} ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 sm:p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endforeach

    @if($categories->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 px-6 py-16 text-center text-stone-500">
            <p>{{ request()->filled('q') ? 'Aucune boisson ne correspond à votre recherche.' : 'Aucune catégorie trouvée. Les données initiales doivent être générées.' }}</p>
        </div>
    @endif

    <script>
    (function () {
        const form = document.getElementById('bulk-disable-form');
        const checkboxes = Array.from(document.querySelectorAll('.drink-bulk-checkbox'));
        const countEl = document.getElementById('bulk-selected-count');
        const submitEl = document.getElementById('bulk-disable-submit');
        const selectAllEl = document.getElementById('bulk-select-all');
        const unselectAllEl = document.getElementById('bulk-unselect-all');

        if (!form || !submitEl || !countEl) return;

        const disableAction = form.dataset.disableAction;
        const enableAction = form.dataset.enableAction;

        function setDisableMode() {
            form.action = disableAction;
            submitEl.textContent = 'Désactiver la sélection';
            submitEl.classList.remove('bg-green-600', 'hover:bg-green-500');
            submitEl.classList.add('bg-red-600', 'hover:bg-red-500');
            submitEl.onclick = () => confirm('Désactiver toutes les boissons sélectionnées ?');
        }

        function setEnableMode() {
            form.action = enableAction;
            submitEl.textContent = 'Réactiver la sélection';
            submitEl.classList.remove('bg-red-600', 'hover:bg-red-500');
            submitEl.classList.add('bg-green-600', 'hover:bg-green-500');
            submitEl.onclick = () => confirm('Réactiver toutes les boissons sélectionnées ?');
        }

        function refreshBulkState() {
            const selected = checkboxes.filter((cb) => cb.checked);
            const checked = selected.length;
            countEl.textContent = String(checked);
            submitEl.disabled = checked === 0;

            const allSelectedUnavailable = checked > 0 && selected.every((cb) => cb.dataset.available === '0');
            if (allSelectedUnavailable) {
                setEnableMode();
            } else {
                setDisableMode();
            }
        }

        checkboxes.forEach((cb) => cb.addEventListener('change', refreshBulkState));

        if (selectAllEl) {
            selectAllEl.addEventListener('click', () => {
                checkboxes.forEach((cb) => {
                    cb.checked = true;
                });
                refreshBulkState();
            });
        }

        if (unselectAllEl) {
            unselectAllEl.addEventListener('click', () => {
                checkboxes.forEach((cb) => {
                    cb.checked = false;
                });
                refreshBulkState();
            });
        }

        refreshBulkState();
    })();
    </script>

</x-employee-layout>
