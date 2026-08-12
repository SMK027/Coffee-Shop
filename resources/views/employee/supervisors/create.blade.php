<x-employee-layout title="Nouveau superviseur">
    <x-slot name="headerActions">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <form action="{{ route('employee.supervisors.store') }}" method="POST" class="max-w-xl space-y-5">
        @csrf

        @include('employee.supervisors.partials.form')

        {{-- Propriétaire --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <label for="superadmin_id" class="block text-sm font-medium text-stone-700 mb-1.5">
                Compte propriétaire <span class="text-red-500">*</span>
            </label>
            @if($superadmins->count() === 1)
                {{-- Un seul superadmin : sélection automatique, affichage informatif --}}
                <input type="hidden" name="superadmin_id" value="{{ $superadmins->first()->id }}">
                <div class="flex items-center gap-2 px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-lg text-sm text-stone-700">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    {{ $superadmins->first()->name }}
                    <span class="text-stone-400 text-xs">(seul super-administrateur)</span>
                </div>
            @else
                @php
                    $defaultAdmin = $superadmins->firstWhere('id', old('superadmin_id', auth()->id()));
                @endphp
                <div x-data="superadminSearch()" class="relative">
                    <input type="hidden" name="superadmin_id" :value="selectedId">

                    <div class="relative">
                        <input type="text"
                               id="superadmin_search"
                               x-model="query"
                               @focus="open = true"
                               @input="open = true; selectedId = null"
                               @keydown.escape="open = false"
                               @keydown.arrow-down.prevent="highlight = Math.min(highlight + 1, filtered.length - 1)"
                               @keydown.arrow-up.prevent="highlight = Math.max(highlight - 1, 0)"
                               @keydown.enter.prevent="pick(filtered[highlight])"
                               @blur="onBlur()"
                               placeholder="Rechercher un super-administrateur…"
                               autocomplete="off"
                               class="w-full border {{ $errors->has('superadmin_id') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg pl-9 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 pointer-events-none"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <button type="button" x-show="selectedId" @click="clear()"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <ul x-show="open && filtered.length > 0" x-cloak
                        @mousedown.prevent
                        class="absolute z-20 w-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                        <template x-for="(admin, idx) in filtered" :key="admin.id">
                            <li @click="pick(admin)"
                                :class="idx === highlight ? 'bg-amber-50' : 'hover:bg-stone-50'"
                                class="flex items-center gap-3 px-4 py-2.5 cursor-pointer text-sm transition-colors">
                                <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                <span class="flex-1 truncate font-medium text-stone-800" x-text="admin.label"></span>
                                <span x-show="admin.isMe" class="text-xs text-amber-600 font-medium flex-shrink-0">Moi</span>
                            </li>
                        </template>
                    </ul>

                    <ul x-show="open && query.length > 0 && filtered.length === 0" x-cloak
                        class="absolute z-20 w-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg">
                        <li class="px-4 py-3 text-sm text-stone-400 italic">Aucun résultat</li>
                    </ul>
                </div>
                @error('superadmin_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-stone-400 mt-1">Le superviseur ne pourra être géré que par ce compte.</p>
            @endif

            <div class="mt-5" x-data="holderAdminSearch()">
                <label for="holder_admin_id" class="block text-sm font-medium text-stone-700 mb-1.5">
                    Détenteur (administrateur simple)
                </label>
                <input type="hidden" name="holder_admin_id" :value="selectedId">

                <div class="relative">
                    <input type="text"
                           id="holder_admin_search"
                           x-model="query"
                           @focus="open = true"
                           @input="open = true; selectedId = null"
                           @keydown.escape="open = false"
                           @keydown.arrow-down.prevent="highlight = Math.min(highlight + 1, filtered.length - 1)"
                           @keydown.arrow-up.prevent="highlight = Math.max(highlight - 1, 0)"
                           @keydown.enter.prevent="pick(filtered[highlight])"
                           @blur="onBlur()"
                           placeholder="Rechercher un administrateur (optionnel)"
                           autocomplete="off"
                           class="w-full border {{ $errors->has('holder_admin_id') ? 'border-red-400 bg-red-50' : 'border-stone-300' }} rounded-lg pl-9 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <button type="button" x-show="query.length > 0 || selectedId" @click="clear()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <ul x-show="open && filtered.length > 0" x-cloak
                    @mousedown.prevent
                    class="absolute z-20 w-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                    <template x-for="(admin, idx) in filtered" :key="admin.id">
                        <li @click="pick(admin)"
                            :class="idx === highlight ? 'bg-amber-50' : 'hover:bg-stone-50'"
                            class="flex items-center gap-3 px-4 py-2.5 cursor-pointer text-sm transition-colors">
                            <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            <span class="flex-1 truncate font-medium text-stone-800" x-text="admin.label"></span>
                        </li>
                    </template>
                </ul>

                <ul x-show="open && query.length > 0 && filtered.length === 0" x-cloak
                    class="absolute z-20 w-full mt-1 bg-white border border-stone-200 rounded-lg shadow-lg">
                    <li class="px-4 py-3 text-sm text-stone-400 italic">Aucun résultat</li>
                </ul>

                @error('holder_admin_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-stone-400 mt-1">Optionnel : laissez vide si le superviseur est directement détenu par le super-administrateur.</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Créer le superviseur
            </button>
            <a href="{{ route('employee.supervisors.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>
    </form>
</x-employee-layout>

@php
    $adminsJson = $superadmins->map(fn($a) => [
        'id'    => $a->id,
        'label' => $a->name,
        'isMe'  => $a->id === auth()->id(),
    ])->values();

    $defaultAdminId   = old('superadmin_id', auth()->id());
    $defaultAdminName = $superadmins->firstWhere('id', $defaultAdminId)?->name ?? '';
    $holdersJson = $admins->map(fn($a) => [
        'id'           => $a->id,
        'label'        => $a->name,
        'superadminId' => $a->superadmin_id,
    ])->values();
    $defaultHolderId = old('holder_admin_id');
    $defaultHolderName = $defaultHolderId
        ? ($admins->firstWhere('id', (int) $defaultHolderId)?->name ?? '')
        : '';
@endphp

<script>
function superadminSearch() {
    const admins  = @json($adminsJson);
    const defId   = {{ (int) $defaultAdminId }};
    const defName = @json($defaultAdminName);

    return {
        query:      defName,
        selectedId: defId || null,
        open:       false,
        highlight:  0,

        get filtered() {
            const q = this.query.toLowerCase().trim();
            return q ? admins.filter(a => a.label.toLowerCase().includes(q)) : admins;
        },

        pick(admin) {
            if (!admin) return;
            this.selectedId = admin.id;
            this.query      = admin.label;
            this.open       = false;
            this.highlight  = 0;
        },

        clear() {
            this.selectedId = null;
            this.query      = '';
            this.open       = false;
            this.$nextTick(() => document.getElementById('superadmin_search')?.focus());
        },

        onBlur() {
            setTimeout(() => {
                // Si la saisie correspond exactement à un compte, le sélectionner
                if (!this.selectedId) {
                    const match = admins.find(a => a.label.toLowerCase() === this.query.toLowerCase().trim());
                    if (match) this.pick(match);
                }
                this.open = false;
            }, 150);
        },
    };
}

function holderAdminSearch() {
    const holders = @json($holdersJson);
    const defaultId = @json($defaultHolderId);
    const defaultName = @json($defaultHolderName);

    return {
        query: defaultName,
        selectedId: defaultId ? Number(defaultId) : null,
        open: false,
        highlight: 0,

        get ownerId() {
            const ownerInput = document.querySelector('input[name="superadmin_id"]');
            return ownerInput ? Number(ownerInput.value || 0) : 0;
        },

        get filtered() {
            const q = this.query.toLowerCase().trim();
            const byOwner = holders.filter(h => Number(h.superadminId) === this.ownerId);
            return q ? byOwner.filter(h => h.label.toLowerCase().includes(q)) : byOwner;
        },

        pick(admin) {
            if (!admin) return;
            this.selectedId = admin.id;
            this.query = admin.label;
            this.open = false;
            this.highlight = 0;
        },

        clear() {
            this.selectedId = null;
            this.query = '';
            this.open = false;
            this.$nextTick(() => document.getElementById('holder_admin_search')?.focus());
        },

        onBlur() {
            setTimeout(() => {
                if (!this.selectedId && this.query.trim() !== '') {
                    const match = this.filtered.find(h => h.label.toLowerCase() === this.query.toLowerCase().trim());
                    if (match) this.pick(match);
                }
                this.open = false;
            }, 150);
        },
    };
}
</script>
