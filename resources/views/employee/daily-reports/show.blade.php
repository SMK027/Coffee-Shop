<x-employee-layout title="Récapitulatif — {{ $dailyReport->report_date->translatedFormat('d F Y') }}">
    <x-slot name="headerActions">
        <a href="{{ route('employee.daily-reports.index') }}" class="text-stone-500 hover:text-stone-700 text-sm">
            ← {{ auth()->user()->isSuperAdmin() && $dailyReport->generated_by !== auth()->id() ? 'Tous les récapitulatifs' : 'Mes récapitulatifs' }}
        </a>
    </x-slot>

    <div class="max-w-2xl space-y-6">

        {{-- En-tête --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="font-bold text-stone-800 text-lg">
                        {{ $dailyReport->report_date->translatedFormat('l d F Y') }}
                    </h2>
                    <p class="text-sm text-stone-500 mt-0.5">
                        Généré par <span class="font-medium text-stone-700">{{ $dailyReport->generator->name }}</span>
                        le {{ $dailyReport->created_at->translatedFormat('d/m/Y à H:i') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-stone-400">Net encaissé</p>
                    <p class="text-2xl font-bold text-stone-800">
                        {{ number_format(max(0, $dailyReport->total_collected - $dailyReport->total_refunded), 2, ',', ' ') }} €
                    </p>
                </div>
            </div>
        </div>

        {{-- Encaissements par moyen de paiement --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h3 class="font-semibold text-stone-800 mb-4">Encaissements</h3>

            @if(empty($dailyReport->breakdown))
                <p class="text-sm text-stone-400 italic">Aucune transaction enregistrée.</p>
            @else
                <div class="space-y-2">
                    @foreach($dailyReport->breakdown as $row)
                    <div class="flex items-center justify-between py-2 border-b border-stone-50 last:border-0">
                        <span class="text-sm text-stone-700">{{ $row['method_name'] }}</span>
                        <span class="font-semibold text-stone-800">{{ number_format($row['total'], 2, ',', ' ') }} €</span>
                    </div>
                    @endforeach
                    <div class="flex items-center justify-between pt-3 font-bold text-green-700 text-base">
                        <span>Total encaissé</span>
                        <span>{{ number_format($dailyReport->total_collected, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Remboursements --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-5">
            <h3 class="font-semibold text-stone-800 mb-4">Remboursements</h3>

            @if(empty($dailyReport->refund_breakdown))
                <p class="text-sm text-stone-400 italic">Aucun remboursement ce jour.</p>
            @else
                <div class="space-y-2">
                    @foreach($dailyReport->refund_breakdown as $row)
                    <div class="flex items-center justify-between py-2 border-b border-stone-50 last:border-0">
                        <span class="text-sm text-stone-700">{{ $row['method_name'] }}</span>
                        <span class="font-semibold text-red-600">{{ number_format($row['total'], 2, ',', ' ') }} €</span>
                    </div>
                    @endforeach
                    <div class="flex items-center justify-between pt-3 font-bold text-red-700 text-base">
                        <span>Total remboursé</span>
                        <span>{{ number_format($dailyReport->total_refunded, 2, ',', ' ') }} €</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Bilan --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-amber-800 font-medium">Bilan net de la journée</p>
                    <p class="text-xs text-amber-600 mt-0.5">Encaissements − Remboursements</p>
                </div>
                <p class="text-3xl font-extrabold text-amber-900">
                    {{ number_format(max(0, $dailyReport->total_collected - $dailyReport->total_refunded), 2, ',', ' ') }} €
                </p>
            </div>
        </div>
    </div>
</x-employee-layout>
