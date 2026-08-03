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

    @php
        $currentYear  = now()->format('Y');
        $currentMonth = now()->format('Y-m');
        $monthNames   = [
            '01' => 'Janvier', '02' => 'Février',  '03' => 'Mars',
            '04' => 'Avril',   '05' => 'Mai',       '06' => 'Juin',
            '07' => 'Juillet', '08' => 'Août',      '09' => 'Septembre',
            '10' => 'Octobre', '11' => 'Novembre',  '12' => 'Décembre',
        ];
    @endphp

    @if($grouped->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 px-6 py-16 text-center text-stone-500">
            Aucun récapitulatif généré pour le moment.
        </div>
    @else
        <div class="space-y-4">
            @foreach($grouped as $year => $months)
            @php
                $yearReports = $months->flatten();
                $yearCollected = $yearReports->sum('total_collected');
                $yearRefunded  = $yearReports->sum('total_refunded');
                $yearNet       = $yearCollected - $yearRefunded;
                $yearCount     = $yearReports->count();
                $isCurrentYear = $year === $currentYear;
            @endphp

            {{-- Bloc année --}}
            <div x-data="{ open: {{ $isCurrentYear ? 'true' : 'false' }} }"
                 class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden">

                {{-- En-tête année --}}
                <button type="button" @click="open = !open"
                        class="w-full flex items-center justify-between px-5 py-4 hover:bg-stone-50 transition-colors text-left">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-stone-400 transition-transform flex-shrink-0"
                             :class="open ? 'rotate-90' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="font-bold text-stone-800 text-lg">{{ $year }}</span>
                        <span class="text-xs text-stone-400 font-normal">{{ $yearCount }} rapport{{ $yearCount > 1 ? 's' : '' }}</span>
                    </div>
                    <div class="hidden sm:flex items-center gap-6 text-sm">
                        <div class="text-right">
                            <p class="text-xs text-stone-400">Encaissé</p>
                            <p class="font-semibold text-green-700">{{ number_format($yearCollected, 2, ',', ' ') }} €</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-stone-400">Remboursé</p>
                            <p class="font-semibold text-red-600">{{ number_format($yearRefunded, 2, ',', ' ') }} €</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-stone-400">Net</p>
                            <p class="font-bold text-stone-800">{{ number_format($yearNet, 2, ',', ' ') }} €</p>
                        </div>
                    </div>
                </button>

                {{-- Mois --}}
                <div x-show="open" x-cloak class="border-t border-stone-100 divide-y divide-stone-50">
                    @foreach($months as $yearMonth => $reports)
                    @php
                        $monthNum       = substr($yearMonth, 5, 2);
                        $monthLabel     = ($monthNames[$monthNum] ?? $monthNum) . ' ' . $year;
                        $mCollected     = $reports->sum('total_collected');
                        $mRefunded      = $reports->sum('total_refunded');
                        $mNet           = $mCollected - $mRefunded;
                        $mCount         = $reports->count();
                        $isCurrentMonth = $yearMonth === $currentMonth;
                    @endphp

                    <div x-data="{ mopen: {{ $isCurrentMonth ? 'true' : 'false' }} }">

                        {{-- En-tête mois --}}
                        <button type="button" @click="mopen = !mopen"
                                class="w-full flex items-center justify-between px-5 py-3 bg-stone-50 hover:bg-stone-100 transition-colors text-left">
                            <div class="flex items-center gap-3">
                                <svg class="w-3.5 h-3.5 text-stone-400 transition-transform flex-shrink-0"
                                     :class="mopen ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                <span class="font-semibold text-stone-700 text-sm">{{ $monthLabel }}</span>
                                <span class="text-xs text-stone-400">{{ $mCount }} jour{{ $mCount > 1 ? 's' : '' }}</span>
                            </div>
                            <div class="hidden sm:flex items-center gap-6 text-xs">
                                <span class="text-green-700 font-medium">{{ number_format($mCollected, 2, ',', ' ') }} €</span>
                                <span class="text-red-600">−{{ number_format($mRefunded, 2, ',', ' ') }} €</span>
                                <span class="font-semibold text-stone-700 w-28 text-right">{{ number_format($mNet, 2, ',', ' ') }} € net</span>
                            </div>
                        </button>

                        {{-- Lignes de rapports --}}
                        <div x-show="mopen" x-cloak>
                            {{-- Vue desktop --}}
                            <div class="hidden sm:block">
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-stone-50">
                                        @foreach($reports as $report)
                                        <tr class="hover:bg-amber-50/40 transition-colors">
                                            <td class="px-5 py-2.5 pl-12 font-medium text-stone-800 w-48">
                                                {{ $report->report_date->translatedFormat('d F') }}
                                            </td>
                                            @if($isSuperAdmin)
                                            <td class="px-5 py-2.5 text-stone-500 text-xs">
                                                {{ $report->generator->name }}
                                            </td>
                                            @endif
                                            <td class="px-5 py-2.5 text-right text-green-700 font-medium">
                                                {{ number_format($report->total_collected, 2, ',', ' ') }} €
                                            </td>
                                            <td class="px-5 py-2.5 text-right text-red-600">
                                                {{ number_format($report->total_refunded, 2, ',', ' ') }} €
                                            </td>
                                            <td class="px-5 py-2.5 text-right font-semibold text-stone-800">
                                                {{ number_format($report->total_collected - $report->total_refunded, 2, ',', ' ') }} €
                                            </td>
                                            <td class="px-5 py-2.5 text-right">
                                                <a href="{{ route('employee.daily-reports.show', $report) }}"
                                                   class="text-amber-700 hover:text-amber-900 text-xs font-medium transition-colors">
                                                    Détail →
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Vue mobile --}}
                            <div class="sm:hidden divide-y divide-stone-50">
                                @foreach($reports as $report)
                                <div class="px-4 py-3 pl-8">
                                    <div class="flex items-start justify-between mb-1.5">
                                        <div>
                                            <span class="font-medium text-stone-800 text-sm">
                                                {{ $report->report_date->translatedFormat('d F') }}
                                            </span>
                                            @if($isSuperAdmin)
                                                <p class="text-xs text-stone-400">{{ $report->generator->name }}</p>
                                            @endif
                                        </div>
                                        <a href="{{ route('employee.daily-reports.show', $report) }}"
                                           class="text-amber-700 text-xs font-medium">Détail</a>
                                    </div>
                                    <div class="grid grid-cols-3 text-xs gap-2">
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
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    @endif

</x-employee-layout>
