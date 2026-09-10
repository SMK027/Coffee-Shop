<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ScheduleShift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MyPlanningController extends Controller
{
    public function index(Request $request)
    {
        $weekStart = $this->resolveWeekStart($request->query('week'));
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $events = ScheduleShift::where('user_id', auth()->id())
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (ScheduleShift $shift) => $shift->date->toDateString());

        return view('employee.my-planning.index', [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'events' => $events,
            'days' => collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i))->all(),
            'totalWorkedLabel' => ScheduleShift::formatDuration(
                ScheduleShift::totalWorkedMinutes($events->flatten(1))
            ),
        ]);
    }

    private function resolveWeekStart(?string $week): Carbon
    {
        try {
            return $week
                ? Carbon::parse($week)->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return now()->startOfWeek(Carbon::MONDAY);
        }
    }
}
