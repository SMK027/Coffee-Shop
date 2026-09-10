@php
    $chipClass = match($shift->type) {
        \App\Models\ScheduleShift::TYPE_WORK => 'chip-work',
        \App\Models\ScheduleShift::TYPE_MEETING => 'chip-meeting',
        \App\Models\ScheduleShift::TYPE_LEAVE => 'chip-leave',
        \App\Models\ScheduleShift::TYPE_ABSENCE => 'chip-absence',
        default => '',
    };
@endphp
<div class="chip {{ $chipClass }}">
    @if($shift->type === \App\Models\ScheduleShift::TYPE_WORK)
        <span class="chip-time">{{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }}</span>
    @else
        <span class="chip-label">{{ $shift->typeLabel() }}</span>
        @if(! $shift->isFullDay())
            <span class="chip-time">{{ substr($shift->start_time, 0, 5) }}–{{ substr($shift->end_time, 0, 5) }}</span>
        @endif
    @endif
    @if($shift->title)
        <div class="chip-title">{{ $shift->title }}</div>
    @endif
</div>
