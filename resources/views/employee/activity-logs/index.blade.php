<x-employee-layout title="Journal d'activité">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('employee.activity-logs.index') }}"
          class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-stone-100 mb-4 flex flex-wrap gap-2 items-center">

        {{-- Recherche --}}
        <div class="flex-1 min-w-[200px] relative">
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Action, description, utilisateur…"
                   oninput="clearTimeout(this._d); this._d = setTimeout(() => this.form.submit(), 350);"
                   class="w-full border border-stone-300 rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
        </div>

        {{-- Période --}}
        <select name="period" onchange="this.form.submit()"
                class="border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
            <option value="today"  {{ $period === 'today' ? 'selected' : '' }}>Aujourd'hui</option>
            <option value="7d"     {{ $period === '7d'    ? 'selected' : '' }}>7 derniers jours</option>
            <option value="30d"    {{ $period === '30d'   ? 'selected' : '' }}>30 derniers jours</option>
            <option value="all"    {{ $period === 'all'   ? 'selected' : '' }}>Toutes les entrées</option>
        </select>

        {{-- Catégorie --}}
        <select name="category" onchange="this.form.submit()"
                class="border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
            <option value="">Toutes les catégories</option>
            @foreach($categories as $key => $cfg)
                <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $cfg['label'] }}</option>
            @endforeach
        </select>

        {{-- Utilisateur --}}
        <select name="user_id" onchange="this.form.submit()"
                class="border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 outline-none">
            <option value="">Tous les utilisateurs</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>

        @if($search || $category || $userId || $period !== '7d')
            <a href="{{ route('employee.activity-logs.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                Réinitialiser
            </a>
        @endif
    </form>

    {{-- Résumé --}}
    <p class="text-xs text-stone-400 mb-3">{{ $logs->total() }} entrée(s) correspondante(s)</p>

    {{-- Liste des logs --}}
    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($logs->isEmpty())
            <div class="px-6 py-16 text-center text-stone-400 text-sm">
                Aucune entrée pour les critères sélectionnés.
            </div>
        @else
            <div class="divide-y divide-stone-50">
                @foreach($logs as $log)
                @php
                    $cfg = $categories[$log->category] ?? $categories['other'];
                @endphp
                <div class="px-4 sm:px-5 py-3 hover:bg-stone-50 transition-colors"
                     x-data="{ open: false }">
                    <div class="flex items-start gap-3">

                        {{-- Point couleur --}}
                        <div class="flex-shrink-0 mt-1.5">
                            <span class="inline-block w-2 h-2 rounded-full {{ $cfg['dot'] }}"></span>
                        </div>

                        {{-- Contenu principal --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                                {{-- Badge catégorie --}}
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $cfg['badge'] }}">
                                    {{ $cfg['label'] }}
                                </span>
                                {{-- Action code --}}
                                <span class="font-mono text-xs text-stone-400">{{ $log->action }}</span>
                            </div>

                            {{-- Description --}}
                            <p class="text-sm text-stone-800">{{ $log->description }}</p>

                            {{-- Meta --}}
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-1 text-xs text-stone-400">
                                <span class="font-medium text-stone-600">{{ $log->user_name }}</span>
                                <span>{{ $log->created_at->format('d/m/Y à H:i:s') }}</span>
                                @if($log->ip_address)
                                    @php
                                        $isLocalDocker = str_starts_with($log->ip_address, '172.') || $log->ip_address === '127.0.0.1' || $log->ip_address === '::1';
                                    @endphp
                                    <span class="font-mono" title="{{ $log->ip_address }}">
                                        {{ $isLocalDocker ? 'local (' . $log->ip_address . ')' : $log->ip_address }}
                                    </span>
                                @endif
                                @if($log->subject_type && $log->subject_id)
                                    <span class="capitalize">{{ $log->subject_type }} #{{ $log->subject_id }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Bouton contexte --}}
                        @if($log->context)
                        <button @click="open = !open"
                                class="flex-shrink-0 mt-0.5 text-stone-400 hover:text-stone-600 transition-colors"
                                :title="open ? 'Masquer les détails' : 'Voir les détails'">
                            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        @endif
                    </div>

                    {{-- Contexte JSON dépliable --}}
                    @if($log->context)
                    <div x-show="open" x-cloak class="mt-2 ml-5">
                        <pre class="bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 text-xs text-stone-600 overflow-x-auto font-mono leading-relaxed">{{ json_encode($log->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="mt-4">{{ $logs->links() }}</div>
    @endif

</x-employee-layout>
