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

    {{-- Recherche + filtres avancés --}}
    <form method="GET" action="{{ route('employee.vouchers.index') }}"
          class="bg-white rounded-xl p-3 sm:p-4 shadow-sm border border-stone-100 mb-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <label for="q" class="block text-xs font-medium text-stone-500 mb-1">Recherche générale</label>
                <input id="q" type="text" name="q" value="{{ $search }}"
                       placeholder="Code ou nom émetteur"
                       class="w-full border border-stone-300 rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                <svg class="absolute left-2.5 top-[35px] w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
            </div>

            <div>
                <label for="status" class="block text-xs font-medium text-stone-500 mb-1">Statut</label>
                <select id="status" name="status"
                        class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none bg-white">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tous</option>
                    <option value="valid" {{ $status === 'valid' ? 'selected' : '' }}>Valides</option>
                    <option value="used" {{ $status === 'used' ? 'selected' : '' }}>Utilisés</option>
                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expirés</option>
                </select>
            </div>

            <div>
                <label for="issuer_id" class="block text-xs font-medium text-stone-500 mb-1">Émetteur</label>
                <select id="issuer_id" name="issuer_id"
                        class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none bg-white">
                    <option value="">Tous les émetteurs</option>
                    @foreach($issuers as $issuer)
                        <option value="{{ $issuer->id }}" {{ $issuerId === $issuer->id ? 'selected' : '' }}>{{ $issuer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="recipient" class="block text-xs font-medium text-stone-500 mb-1">Destinataire</label>
                <input id="recipient" type="text" name="recipient" value="{{ $recipient }}"
                       placeholder="Nom ou numéro de carte"
                       class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div>
                <label for="amount_min" class="block text-xs font-medium text-stone-500 mb-1">Montant min (€)</label>
                <input id="amount_min" type="number" step="0.01" min="0" name="amount_min" value="{{ $amountMin ?? '' }}"
                       class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div>
                <label for="amount_max" class="block text-xs font-medium text-stone-500 mb-1">Montant max (€)</label>
                <input id="amount_max" type="number" step="0.01" min="0" name="amount_max" value="{{ $amountMax ?? '' }}"
                       class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div>
                <label for="expires_from" class="block text-xs font-medium text-stone-500 mb-1">Expiration du</label>
                <input id="expires_from" type="date" name="expires_from" value="{{ $expiresFrom }}"
                       class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div>
                <label for="expires_to" class="block text-xs font-medium text-stone-500 mb-1">Expiration au</label>
                <input id="expires_to" type="date" name="expires_to" value="{{ $expiresTo }}"
                       class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit"
                    class="bg-amber-700 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Appliquer les filtres
            </button>
            <a href="{{ route('employee.vouchers.index') }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-600 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Réinitialiser
            </a>
        </div>
    </form>

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
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Destinataire</th>
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
                            <td class="px-5 py-3 text-stone-600 text-xs">
                                @if($voucher->restricted_name)
                                    {{ $voucher->restricted_name }}
                                @elseif($voucher->restrictedCard)
                                    Carte {{ chunk_split($voucher->restrictedCard->card_number, 4, ' ') }}
                                @else
                                    <span class="text-stone-400">Tous clients</span>
                                @endif
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
                                    @if((auth()->user()->isAdmin() || auth()->user()->isSuperAdmin()) && !$isUsed)
                                        <a href="{{ route('employee.vouchers.edit', $voucher) }}"
                                           class="text-stone-500 hover:text-stone-700 text-xs font-medium transition-colors">
                                            Modifier
                                        </a>
                                    @endif
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
                            @if($voucher->restricted_name)
                                <p class="text-xs text-stone-500 mt-0.5">Dest. : {{ $voucher->restricted_name }}</p>
                            @elseif($voucher->restrictedCard)
                                <p class="text-xs text-stone-500 mt-0.5">Dest. : carte {{ chunk_split($voucher->restrictedCard->card_number, 4, ' ') }}</p>
                            @else
                                <p class="text-xs text-stone-400 mt-0.5">Dest. : tous clients</p>
                            @endif
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
