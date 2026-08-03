<x-employee-layout title="Bons d'achat / Avoirs">
    <x-slot name="headerActions">
        @if(auth()->user()->isAdmin())
        <a href="{{ route('employee.vouchers.create') }}"
           class="bg-amber-700 hover:bg-amber-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="hidden sm:inline">Nouveau bon d'achat</span>
            <span class="sm:hidden">Nouveau</span>
        </a>
        @endif
    </x-slot>

    {{-- Nouveau code mis en avant --}}
    @if(session('new_voucher_code'))
        <div class="bg-green-50 border border-green-200 rounded-xl px-5 py-4 mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-green-800 mb-1">{{ session('success') }}</p>
                <p class="text-xs text-green-700">Code à remettre au client :</p>
                <p class="text-2xl font-mono font-bold tracking-widest text-green-900 mt-1">{{ session('new_voucher_code') }}</p>
            </div>
            <button onclick="navigator.clipboard.writeText('{{ session('new_voucher_code') }}').then(() => this.textContent = 'Copié ✓')"
                    class="flex-shrink-0 bg-green-100 hover:bg-green-200 text-green-800 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Copier
            </button>
        </div>
    @elseif(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 mb-6 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Recherche + filtres --}}
    <form method="GET" action="{{ route('employee.vouchers.index') }}"
          class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-stone-100 mb-4 flex flex-wrap gap-2 items-center">
        <div class="flex-1 min-w-[220px] relative">
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Rechercher par code ou compte…"
                   oninput="clearTimeout(this._d); this._d = setTimeout(() => this.form.submit(), 300);"
                   class="w-full border border-stone-300 rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
        </div>
        @if($search !== '')
            <input type="hidden" name="filter" value="{{ $filter }}">
            <a href="{{ route('employee.vouchers.index', ['filter' => $filter]) }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Effacer
            </a>
        @endif
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 mb-4 p-3 flex flex-wrap gap-2">
        @foreach(['all' => 'Tous', 'valid' => 'Valides', 'used' => 'Utilisés', 'expired' => 'Expirés'] as $key => $label)
            <a href="{{ route('employee.vouchers.index', array_filter(['q' => $search, 'filter' => $key])) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                      {{ $filter === $key ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($vouchers->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500 text-sm">Aucun bon d'achat trouvé.</div>
        @else
            {{-- Vue desktop --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Code</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Montant</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Émis par</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Émission</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Expiration</th>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Statut</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($vouchers as $voucher)
                        @php
                            $isValid   = $voucher->isValid();
                            $isExpired = $voucher->isExpired();
                            $isUsed    = $voucher->is_used;
                        @endphp
                        <tr class="hover:bg-stone-50 transition-colors {{ !$isValid ? 'opacity-60' : '' }}">
                            <td class="px-5 py-3 font-mono font-semibold text-stone-800 text-sm tracking-wider">
                                {{ $voucher->code }}
                            </td>
                            <td class="px-5 py-3 font-semibold text-amber-700">
                                {{ number_format($voucher->amount, 2, ',', ' ') }} €
                            </td>
                            <td class="px-5 py-3 text-stone-600">{{ $voucher->issued_by_name }}</td>
                            <td class="px-5 py-3 text-stone-500 text-xs">{{ $voucher->issued_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-stone-500 text-xs">
                                {{ $voucher->expires_at->format('d/m/Y') }}
                                @if($isExpired && !$isUsed)
                                    <span class="ml-1 text-red-500">(expiré)</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($isUsed)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-500">Utilisé</span>
                                @elseif($isExpired)
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600">Expiré</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Valide</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('employee.vouchers.show', $voucher) }}"
                                       class="text-amber-700 hover:text-amber-900 text-xs font-medium transition-colors">
                                        Détails →
                                    </a>
                                    @unless($isUsed)
                                    <a href="{{ route('employee.vouchers.edit', $voucher) }}"
                                       class="text-stone-500 hover:text-stone-700 text-xs font-medium transition-colors">
                                        Modifier
                                    </a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Vue mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($vouchers as $voucher)
                @php
                    $isValid = $voucher->isValid();
                    $isUsed  = $voucher->is_used;
                    $isExp   = $voucher->isExpired();
                @endphp
                <div class="px-4 py-3 {{ !$isValid ? 'opacity-60' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('employee.vouchers.show', $voucher) }}"
                               class="font-mono font-bold text-amber-700 tracking-wider text-sm hover:underline">
                                {{ $voucher->code }}
                            </a>
                            <p class="text-stone-700 font-semibold text-sm mt-0.5">{{ number_format($voucher->amount, 2, ',', ' ') }} €</p>
                            <p class="text-xs text-stone-500 mt-0.5">{{ $voucher->issued_by_name }} · exp. {{ $voucher->expires_at->format('d/m/Y') }}</p>
                        </div>
                        <div class="flex-shrink-0 mt-0.5">
                            @if($isUsed)
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-stone-100 text-stone-500">Utilisé</span>
                            @elseif($isExp)
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600">Expiré</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Valide</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($vouchers->hasPages())
        <div class="mt-4">{{ $vouchers->links() }}</div>
    @endif

</x-employee-layout>
