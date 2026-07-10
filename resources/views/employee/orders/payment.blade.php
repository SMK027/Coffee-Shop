<x-employee-layout title="Paiement — Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.orders.show', $order) }}" class="text-stone-500 hover:text-stone-700 text-sm">← Retour à la commande</a>
    </x-slot>

@php
    $initialRows = $order->payments->isNotEmpty()
        ? $order->payments->map(function ($p) {
            return ['method_id' => $p->payment_method_id, 'amount' => number_format($p->amount, 2, '.', '')];
          })->values()->toArray()
        : [['method_id' => '', 'amount' => '']];
@endphp

    <div class="max-w-2xl space-y-6"
         x-data="{
            rows: @json($initialRows),
            totalOrder: {{ (float) $order->total_amount }},
            get totalPaid() { return this.rows.reduce((s, r) => s + (parseFloat(r.amount) || 0), 0); },
            get remaining() { return Math.max(0, this.totalOrder - this.totalPaid).toFixed(2); },
            addRow() { this.rows.push({ method_id: '', amount: '' }); },
            removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); }
         }">

        {{-- Récap commande --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h2 class="font-semibold text-stone-800 mb-1">Commande #{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</h2>
            <p class="text-sm text-stone-600">
                {{ $order->display_name }} —
                <span class="font-semibold text-stone-800">{{ number_format($order->total_amount, 2, ',', ' ') }} €</span>
            </p>
            @if($alreadyPaid > 0)
                <p class="text-xs text-amber-700 mt-1">
                    Déjà enregistré : {{ number_format($alreadyPaid, 2, ',', ' ') }} €
                    — Reste à régler : {{ number_format($remaining, 2, ',', ' ') }} €
                </p>
            @endif
        </div>

        <form action="{{ route('employee.orders.payment.store', $order) }}" method="POST">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5 space-y-4">
                <h2 class="font-semibold text-stone-800">Répartition du paiement</h2>

                {{-- Lignes paiement --}}
                <template x-for="(row, index) in rows" :key="index">
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-stone-600 mb-1">Moyen de paiement</label>
                            <select :name="'payments[' + index + '][payment_method_id]'"
                                    x-model="row.method_id" required
                                    class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                                <option value="">— Choisir —</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-36">
                            <label class="block text-xs font-medium text-stone-600 mb-1">Montant (€)</label>
                            <input type="number" :name="'payments[' + index + '][amount]'"
                                   x-model="row.amount" required min="0.01" step="0.01"
                                   class="w-full border border-stone-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        </div>
                        <button type="button" @click="removeRow(index)"
                                x-show="rows.length > 1"
                                class="mb-0.5 text-red-400 hover:text-red-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>

                <button type="button" @click="addRow()"
                        class="flex items-center gap-1.5 text-sm text-amber-700 hover:text-amber-900 font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajouter un moyen de paiement
                </button>

                {{-- Totaux --}}
                <div class="pt-4 border-t border-stone-100 space-y-1.5 text-sm">
                    <div class="flex justify-between text-stone-600">
                        <span>Total commande</span>
                        <span>{{ number_format($order->total_amount, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="flex justify-between font-semibold text-stone-800">
                        <span>Total saisi</span>
                        <span x-text="totalPaid.toFixed(2).replace('.', ',') + ' €'"></span>
                    </div>
                    <div class="flex justify-between text-stone-500">
                        <span>Écart</span>
                        <span x-text="remaining + ' €'" :class="remaining > 0 ? 'text-red-600 font-medium' : 'text-green-600'"></span>
                    </div>
                </div>

                @error('payments')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 mt-4">
                <button type="submit"
                        class="bg-amber-700 hover:bg-amber-600 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Enregistrer les paiements
                </button>
                <a href="{{ route('employee.orders.show', $order) }}"
                   class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-5 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</x-employee-layout>
