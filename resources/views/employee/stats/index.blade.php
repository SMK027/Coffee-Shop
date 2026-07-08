<x-employee-layout title="Statistiques de vente">

    {{-- Sélecteur de période --}}
    <div class="bg-white rounded-xl p-4 shadow-sm border border-stone-100 mb-6 flex items-center gap-3">
        <span class="text-sm font-medium text-stone-600">Période :</span>
        @foreach(['7' => '7 jours', '30' => '30 jours', '90' => '90 jours'] as $val => $label)
            <a href="{{ route('employee.stats.index', ['period' => $val]) }}"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors
                      {{ $period === $val ? 'bg-amber-700 text-white' : 'bg-stone-100 text-stone-600 hover:bg-stone-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Totaux --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-stone-100">
            <p class="text-xs font-medium text-stone-500 uppercase tracking-wider">Commandes</p>
            <p class="text-3xl font-bold text-stone-800 mt-2">{{ $totals['orders'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-stone-100">
            <p class="text-xs font-medium text-stone-500 uppercase tracking-wider">Revenu total</p>
            <p class="text-3xl font-bold text-stone-800 mt-2">{{ number_format($totals['revenue'], 2, ',', ' ') }} €</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-stone-100">
            <p class="text-xs font-medium text-stone-500 uppercase tracking-wider">Panier moyen</p>
            <p class="text-3xl font-bold text-stone-800 mt-2">{{ number_format($totals['avg'], 2, ',', ' ') }} €</p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-red-50">
            <p class="text-xs font-medium text-red-400 uppercase tracking-wider">Annulations</p>
            <p class="text-3xl font-bold text-red-500 mt-2">{{ $totals['cancelled'] }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">

        {{-- Revenus par jour --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <h2 class="font-semibold text-stone-800 mb-5">Revenu quotidien</h2>
            @if($dailyRevenue->isEmpty())
                <p class="text-sm text-stone-500 text-center py-8">Aucune donnée disponible.</p>
            @else
                @php $maxRevenue = max(1, (float) $dailyRevenue->max('total')); @endphp
                <div class="space-y-2">
                    @foreach($dailyRevenue as $day)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-stone-400 w-20 flex-shrink-0">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                        <div class="flex-1 bg-stone-100 rounded-full h-5 overflow-hidden">
                            <div class="bg-amber-500 h-full rounded-full transition-all"
                                 style="width: {{ round(($day->total / $maxRevenue) * 100) }}%"></div>
                        </div>
                        <span class="text-xs font-medium text-stone-700 w-20 text-right flex-shrink-0">{{ number_format($day->total, 2, ',', ' ') }} €</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Top boissons --}}
        <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-6">
            <h2 class="font-semibold text-stone-800 mb-5">Top boissons vendues</h2>
            @if($topDrinks->isEmpty())
                <p class="text-sm text-stone-500 text-center py-8">Aucune donnée disponible.</p>
            @else
                @php $maxQty = max(1, (int) $topDrinks->max('total_qty')); @endphp
                <div class="space-y-3">
                    @foreach($topDrinks as $i => $drink)
                    <div class="flex items-center gap-3">
                        <span class="w-5 h-5 bg-amber-{{ $i < 3 ? '600' : '200' }} text-{{ $i < 3 ? 'white' : 'amber-700' }} rounded-full text-xs font-bold flex items-center justify-center flex-shrink-0">{{ $i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-medium text-stone-800 truncate">{{ $drink->name }}</p>
                                <span class="text-xs text-stone-500 flex-shrink-0 ml-2">{{ $drink->total_qty }} ventes</span>
                            </div>
                            <div class="bg-stone-100 rounded-full h-2">
                                <div class="bg-amber-500 h-2 rounded-full" style="width: {{ round(($drink->total_qty / $maxQty) * 100) }}%"></div>
                            </div>
                        </div>
                        <span class="text-xs font-medium text-stone-600 flex-shrink-0 w-20 text-right">{{ number_format($drink->total_revenue, 2, ',', ' ') }} €</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</x-employee-layout>
