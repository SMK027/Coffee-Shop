<x-employee-layout title="Modifier le superviseur" subtitle="{{ $supervisor->supervisor_number }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.supervisors.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour</a>
    </x-slot>

    <form action="{{ route('employee.supervisors.update', $supervisor) }}" method="POST" class="max-w-xl space-y-5">
        @csrf @method('PUT')

        @if($isSuperAdmin)
            @include('employee.supervisors.partials.form')
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 relative" x-data="holderAdminSearch()">
                <label for="holder_admin_id" class="block text-sm font-medium text-stone-700 mb-1.5">Détenteur</label>
                <input type="hidden" name="holder_admin_id" :value="selectedId ?? ''">
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
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <button type="button" x-show="query.length > 0 || selectedId" @click="clear()" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-stone-400 hover:text-stone-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <ul x-show="open && filtered.length > 0" x-cloak @mousedown.prevent class="absolute z-20 w-full max-w-xl mt-1 bg-white border border-stone-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
                    <template x-for="(admin, idx) in filtered" :key="admin.id">
                        <li @click="pick(admin)" :class="idx === highlight ? 'bg-amber-50' : 'hover:bg-stone-50'" class="flex items-center gap-3 px-4 py-2.5 cursor-pointer text-sm transition-colors">
                            <span class="flex-1 truncate font-medium text-stone-800" x-text="admin.label"></span>
                            <span class="text-xs text-stone-400" x-text="admin.email"></span>
                        </li>
                    </template>
                </ul>
                @error('holder_admin_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-stone-400 mt-1">Seuls les administrateurs simples actifs peuvent être désignés détenteurs.</p>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-1.5">Numéro du superviseur</label>
                    <input type="text" value="{{ $supervisor->supervisor_number }}" readonly
                           class="w-full border border-stone-200 bg-stone-50 rounded-lg px-4 py-2.5 text-sm text-stone-500 font-mono">
                </div>

                <div>
                    <label for="supervisor_pin" class="block text-sm font-medium text-stone-700 mb-1.5">Nouveau PIN du superviseur *</label>
                    <input type="password" name="supervisor_pin" id="supervisor_pin" required maxlength="6" minlength="4"
                           inputmode="numeric" pattern="\d{4,6}"
                           class="w-full border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    @error('supervisor_pin')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    <p class="text-xs text-stone-400 mt-1">PIN de 4 à 6 chiffres.</p>
                </div>

                @if($supervisor->quarantined_until?->isFuture())
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="reactivate_after_pin_reset" value="1" class="rounded border-stone-300 text-green-600 focus:ring-green-500">
                        <span class="text-sm text-stone-700">Réactiver après changement du PIN et supprimer les codes provisoires</span>
                    </label>
                @endif
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Enregistrer
            </button>
            <a href="{{ route('employee.supervisors.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                Annuler
            </a>
        </div>
    </form>

    @php
        $holderAdminsJson = $holderAdmins->map(fn($admin) => [
            'id' => $admin->id,
            'label' => $admin->name,
            'email' => $admin->email,
        ])->values();
        $defaultHolderId = old('holder_admin_id', $supervisor->holder_admin_id);
        $defaultHolderName = $defaultHolderId ? ($holderAdmins->firstWhere('id', (int) $defaultHolderId)?->name ?? '') : '';
    @endphp
    <script>
        function holderAdminSearch() {
            const admins = @json($holderAdminsJson);
            return {
                query: @json($defaultHolderName),
                selectedId: {{ $defaultHolderId ? (int) $defaultHolderId : 'null' }},
                open: false,
                highlight: 0,
                get filtered() {
                    const q = this.query.toLowerCase().trim();
                    return q ? admins.filter(a => (a.label + ' ' + a.email).toLowerCase().includes(q)) : admins;
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
                        if (!this.selectedId) {
                            const match = admins.find(a => a.label.toLowerCase() === this.query.toLowerCase().trim());
                            if (match) this.pick(match);
                        }
                        this.open = false;
                    }, 150);
                },
            };
        }
    </script>
</x-employee-layout>
