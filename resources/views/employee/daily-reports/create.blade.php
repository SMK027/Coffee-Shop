<x-employee-layout title="Générer un récapitulatif">
    <x-slot name="headerActions">
        <a href="{{ route('employee.daily-reports.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">← Mes récapitulatifs</a>
    </x-slot>

    <div class="max-w-2xl space-y-6">

        {{-- Sélecteur de date --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h2 class="font-semibold text-stone-800 mb-4">Choisir une journée</h2>
            <form method="GET" action="{{ route('employee.daily-reports.create') }}" class="flex items-end gap-3">
                <div>
                    <label for="date" class="block text-sm font-medium text-stone-700 mb-1">Date</label>
                    <input type="date" name="date" id="date" value="{{ $date }}" max="{{ today()->toDateString() }}"
                           class="border border-stone-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                </div>
                <button type="submit"
                        class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-4 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    Charger
                </button>
            </form>
        </div>

        {{-- Aperçu des encaissements --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h2 class="font-semibold text-stone-800 mb-4">
                Aperçu — {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
            </h2>

            <div class="space-y-4">

                {{-- Encaissements --}}
                <div>
                    <h3 class="text-sm font-semibold text-stone-700 mb-2">Encaissements</h3>
                    @if(empty($breakdown))
                        <p class="text-sm text-stone-400 italic">Aucune commande complétée ce jour.</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach($breakdown as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-600">{{ $row['method_name'] }}</span>
                                <span class="font-medium text-stone-800">{{ number_format($row['total'], 2, ',', ' ') }} €</span>
                            </div>
                            @endforeach
                            <div class="flex justify-between pt-2 border-t border-stone-100 font-semibold text-green-700">
                                <span>Total encaissé</span>
                                <span>{{ number_format($totalCollected, 2, ',', ' ') }} €</span>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Remboursements --}}
                <div>
                    <h3 class="text-sm font-semibold text-stone-700 mb-2">Remboursements</h3>
                    @if(empty($refundBreakdown))
                        <p class="text-sm text-stone-400 italic">Aucun remboursement ce jour.</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach($refundBreakdown as $row)
                            <div class="flex justify-between text-sm">
                                <span class="text-stone-600">{{ $row['method_name'] }}</span>
                                <span class="font-medium text-red-600">{{ number_format($row['total'], 2, ',', ' ') }} €</span>
                            </div>
                            @endforeach
                            <div class="flex justify-between pt-2 border-t border-stone-100 font-semibold text-red-700">
                                <span>Total remboursé</span>
                                <span>{{ number_format($totalRefunded, 2, ',', ' ') }} €</span>
                            </div>
                        </div>
                    @endif
                </div>

{{-- Bilan net --}}
        <div class="flex justify-between pt-3 border-t-2 border-stone-200 font-bold text-stone-800 text-base">
            <span>Net encaissé</span>
            <span>{{ number_format(max(0, $totalCollected - $totalRefunded), 2, ',', ' ') }} €</span>
        </div>
            </div>
        </div>

        {{-- Avertissement si rapport existant --}}
        @if($existing)
        <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div>
                <p class="text-sm font-semibold text-amber-800">Un récapitulatif existe déjà pour cette journée</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    Généré le {{ $existing->created_at->format('d/m/Y à H:i') }}.
                    En cliquant sur « Mettre à jour », l'ancien récapitulatif sera écrasé par les données ci-dessus.
                </p>
            </div>
        </div>
        @endif

        {{-- Bouton génération --}}
        <form action="{{ route('employee.daily-reports.store') }}" method="POST"
              @if($existing) onsubmit="return confirm('Ce récapitulatif a déjà été généré. Confirmer la mise à jour avec les données actuelles ?')" @endif>
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <button type="submit"
                    class="w-full sm:w-auto bg-amber-700 hover:bg-amber-600 text-white px-6 py-3 rounded-lg text-sm font-medium transition-colors">
                {{ $existing ? 'Mettre à jour le récapitulatif' : 'Enregistrer ce récapitulatif' }}
            </button>
        </form>
    </div>
</x-employee-layout>
