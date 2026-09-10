<x-employee-layout title="Mon planning">

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-stone-800">Mon planning</h1>
            <p class="text-xs text-stone-500 mt-0.5">Total travaillé cette semaine : <span class="font-medium text-stone-700">{{ $totalWorkedLabel }}</span></p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('employee.my-planning.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                ← Semaine précédente
            </a>
            <span class="text-sm font-medium text-stone-700 px-2">
                Semaine du {{ $weekStart->format('d/m/Y') }} au {{ $weekEnd->format('d/m/Y') }}
            </span>
            <a href="{{ route('employee.my-planning.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}"
               class="bg-stone-100 hover:bg-stone-200 text-stone-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                Semaine suivante →
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-stone-100 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-7 gap-2">
            @foreach($days as $day)
                <div class="border border-stone-100 rounded-lg overflow-hidden">
                    <div class="bg-stone-50 px-3 py-2 border-b border-stone-100">
                        <p class="text-xs font-semibold text-stone-700">{{ ucfirst($day->translatedFormat('l')) }}</p>
                        <p class="text-xs text-stone-400">{{ $day->format('d/m') }}</p>
                    </div>
                    <div class="p-2 space-y-1.5 min-h-[64px]">
                        @forelse($events->get($day->toDateString(), collect()) as $shift)
                            @if($shift->type === \App\Models\ScheduleShift::TYPE_WORK)
                                <div class="bg-amber-50 border border-amber-100 rounded-lg px-2 py-1.5">
                                    <p class="text-xs font-medium text-amber-900">
                                        {{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }}
                                    </p>
                                    @if($shift->title)
                                        <p class="text-xs text-amber-700">{{ $shift->title }}</p>
                                    @endif
                                </div>
                            @else
                                <div class="{{ $shift->type === \App\Models\ScheduleShift::TYPE_LEAVE ? 'bg-green-50 border-green-100 text-green-800' : 'bg-red-50 border-red-100 text-red-800' }} border rounded-lg px-2 py-1.5">
                                    <p class="text-xs font-semibold">
                                        {{ $shift->typeLabel() }}
                                        @if(! $shift->isFullDay())
                                            ({{ substr($shift->start_time, 0, 5) }} – {{ substr($shift->end_time, 0, 5) }})
                                        @endif
                                    </p>
                                    @if($shift->title)
                                        <p class="text-xs opacity-80">{{ $shift->title }}</p>
                                    @endif
                                </div>
                            @endif
                        @empty
                            <p class="text-xs text-stone-300 italic">—</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</x-employee-layout>
