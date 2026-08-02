<x-employee-layout title="Bon d'achat — {{ $voucher->code }}">
    <x-slot name="headerActions">
        <div class="flex items-center gap-3">
            <button onclick="window.print()"
                    class="flex items-center gap-1.5 bg-amber-700 hover:bg-amber-600 text-white text-sm font-medium px-3 py-1.5 rounded-lg transition-colors print:hidden">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
            <a href="{{ route('employee.vouchers.index') }}" class="text-stone-500 hover:text-stone-700 text-sm print:hidden">
                ← Retour aux bons d'achat
            </a>
        </div>
    </x-slot>

    @php
        $isValid   = $voucher->isValid();
        $isExpired = $voucher->isExpired();
        $isUsed    = $voucher->is_used;
    @endphp

    {{-- Statut inline (écran uniquement) --}}
    <div class="mb-4 print:hidden">
        @if($isUsed)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-stone-100 text-stone-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Bon utilisé le {{ $voucher->used_at?->format('d/m/Y à H:i') }}
                @if($voucher->usedInOrder)
                    — <a href="{{ route('employee.orders.show', $voucher->usedInOrder) }}" class="underline hover:text-stone-800 transition-colors">
                        Commande #{{ str_pad($voucher->usedInOrder->id, 4, '0', STR_PAD_LEFT) }}
                    </a>
                @endif
            </span>
        @elseif($isExpired)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-red-100 text-red-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Expiré depuis le {{ $voucher->expires_at->format('d/m/Y') }}
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Valide · expire le {{ $voucher->expires_at->format('d/m/Y') }}
            </span>
        @endif
    </div>

    {{-- Zone imprimable --}}
    <div id="print-area" class="max-w-md mx-auto">

        {{-- Ticket / bon --}}
        <div class="bg-white rounded-2xl shadow-md border border-stone-200 overflow-hidden">

            {{-- En-tête boutique --}}
            <div class="bg-amber-900 text-white px-8 py-5 text-center">
                <p class="text-amber-300 text-xs font-medium tracking-widest uppercase mb-1">Bon d'achat</p>
                <p class="text-xl font-bold tracking-wide">{{ config('app.name') }}</p>
            </div>

            {{-- Code --}}
            <div class="px-8 py-6 text-center border-b border-dashed border-stone-200">
                <p class="text-xs text-stone-400 uppercase tracking-widest mb-2">Code</p>
                <p class="text-3xl font-mono font-bold tracking-[.25em] text-stone-900 select-all">
                    {{ $voucher->code }}
                </p>
            </div>

            {{-- Montant --}}
            <div class="px-8 py-6 text-center border-b border-dashed border-stone-200">
                <p class="text-xs text-stone-400 uppercase tracking-widest mb-2">Valeur</p>
                <p class="text-5xl font-bold text-amber-700">
                    {{ number_format($voucher->amount, 2, ',', ' ') }}&thinsp;<span class="text-3xl">€</span>
                </p>
            </div>

            {{-- Détails --}}
            <div class="px-8 py-5 space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-stone-500">Date d'émission</span>
                    <span class="font-medium text-stone-800">{{ $voucher->issued_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">Date d'expiration</span>
                    <span class="font-medium {{ $isExpired ? 'text-red-600' : 'text-stone-800' }}">
                        {{ $voucher->expires_at->format('d/m/Y') }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-stone-500">Émis par</span>
                    <span class="font-medium text-stone-800">{{ $voucher->issued_by_name }}</span>
                </div>
                @if($voucher->restricted_card_id !== null || $voucher->restricted_name !== null)
                <div class="flex justify-between items-start">
                    <span class="text-stone-500 flex-shrink-0">Réservé à</span>
                    <span class="font-medium text-stone-800 text-right ml-4">
                        @if($voucher->restricted_card_id !== null)
                            @if($voucher->restrictedCard)
                                {{ $voucher->restrictedCard->full_name }}
                                <span class="block font-mono text-xs text-stone-400">{{ chunk_split($voucher->restrictedCard->card_number, 4, ' ') }}</span>
                            @else
                                <span class="text-stone-400 italic">Carte #{{ $voucher->restricted_card_id }}</span>
                            @endif
                        @else
                            {{ $voucher->restricted_name }}
                            <span class="block text-xs text-stone-400">(par nom)</span>
                        @endif
                    </span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-stone-500">Statut</span>
                    <span class="font-semibold
                        {{ $isUsed ? 'text-stone-500' : ($isExpired ? 'text-red-600' : 'text-green-700') }}">
                        @if($isUsed) Utilisé
                        @elseif($isExpired) Expiré
                        @else Valide
                        @endif
                    </span>
                </div>
                @if($isUsed && $voucher->usedInOrder)
                <div class="flex justify-between">
                    <span class="text-stone-500">Utilisé commande</span>
                    <span class="font-medium text-stone-800">#{{ str_pad($voucher->usedInOrder->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                @endif
            </div>

            {{-- Pied de ticket --}}
            <div class="bg-stone-50 px-8 py-4 text-center border-t border-dashed border-stone-200">
                <p class="text-xs text-stone-400 leading-relaxed">
                    Ce bon est utilisable en une seule fois.<br>
                    Non remboursable · Non échangeable contre des espèces.
                </p>
            </div>
        </div>

        {{-- Mention ID (bas de page, discrète) --}}
        <p class="text-center text-xs text-stone-300 mt-4 font-mono">ID {{ str_pad($voucher->id, 6, '0', STR_PAD_LEFT) }}</p>
    </div>

</x-employee-layout>

{{-- Styles d'impression : cache tout sauf le bon --}}
<style>
@media print {
    /* Cache le layout employé (sidebar, header, boutons) */
    body > * { visibility: hidden; }
    #sidebar, header, nav, [id^="sidebar"] { display: none !important; }

    /* Affiche uniquement la zone imprimable */
    #print-area,
    #print-area * {
        visibility: visible;
    }
    #print-area {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        max-width: 420px;
        margin: 40px auto;
    }

    /* Supprime les ombres et bordures adaptées à l'impression */
    #print-area .shadow-md { box-shadow: none; }
    #print-area .rounded-2xl { border-radius: 8px; }

    /* Force les couleurs pour impression */
    #print-area .bg-amber-900 {
        background-color: #78350f !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #print-area .bg-stone-50 {
        background-color: #fafaf9 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
