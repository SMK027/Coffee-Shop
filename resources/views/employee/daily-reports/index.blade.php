<x-employee-layout title="Récapitulatifs journaliers">
    <x-slot name="headerActions">
        <a href="{{ route('employee.daily-reports.create') }}"
           class="bg-amber-700 hover:bg-amber-600 text-white px-3 sm:px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 sm:gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="hidden sm:inline">Générer un récapitulatif</span>
            <span class="sm:hidden">Générer</span>
        </a>
    </x-slot>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">
        @if($reports->isEmpty())
            <div class="px-6 py-16 text-center text-stone-500">
                <p>Aucun récapitulatif généré pour le moment.</p>
            </div>
        @else
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 border-b border-stone-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-medium text-stone-600">Date</th>
                            @if($isSuperAdmin)
                                <th class="px-5 py-3 text-left font-medium text-stone-600">Généré par</th>
                            @endif
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Encaissé</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Remboursé</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Net</th>
                            <th class="px-5 py-3 text-right font-medium text-stone-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-50">
                        @foreach($reports as $report)
                        <tr class="hover:bg-stone-50 transition-colors">
                            <td class="px-5 py-3 font-medium text-stone-800">
                                {{ $report->report_date->translatedFormat('d F Y') }}
                            </td>
                            @if($isSuperAdmin)
                                <td class="px-5 py-3 text-stone-600">{{ $report->generator->name }}</td>
                            @endif
                            <td class="px-5 py-3 text-right text-green-700 font-medium">
                                {{ number_format($report->total_collected, 2, ',', ' ') }} €
                            </td>
                            <td class="px-5 py-3 text-right text-red-600">
                                {{ number_format($report->total_refunded, 2, ',', ' ') }} €
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-stone-800">
                                {{ number_format($report->total_collected - $report->total_refunded, 2, ',', ' ') }} €
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('employee.daily-reports.show', $report) }}"
                                   class="text-amber-700 hover:text-amber-900 text-xs font-medium transition-colors">
                                    Voir le détail
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile --}}
            <div class="sm:hidden divide-y divide-stone-100">
                @foreach($reports as $report)
                <div class="px-4 py-4">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="font-medium text-stone-800">{{ $report->report_date->translatedFormat('d F Y') }}</span>
                            @if($isSuperAdmin)
                                <p class="text-xs text-stone-400 mt-0.5">{{ $report->generator->name }}</p>
                            @endif
                        </div>
                        <a href="{{ route('employee.daily-reports.show', $report) }}"
                           class="text-amber-700 text-xs font-medium">Détail</a>
                    </div>
                    <div class="grid grid-cols-3 text-xs text-stone-600 gap-2">
                        <div>
                            <p class="text-stone-400">Encaissé</p>
                            <p class="font-medium text-green-700">{{ number_format($report->total_collected, 2, ',', ' ') }} €</p>
                        </div>
                        <div>
                            <p class="text-stone-400">Remboursé</p>
                            <p class="font-medium text-red-600">{{ number_format($report->total_refunded, 2, ',', ' ') }} €</p>
                        </div>
                        <div>
                            <p class="text-stone-400">Net</p>
                            <p class="font-semibold text-stone-800">{{ number_format($report->total_collected - $report->total_refunded, 2, ',', ' ') }} €</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="px-5 py-4 border-t border-stone-100">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</x-employee-layout>
