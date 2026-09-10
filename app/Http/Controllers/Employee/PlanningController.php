<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ScheduleShift;
use App\Models\User;
use App\Services\ActivityLogger;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanningController extends Controller
{
    private const EDIT_UNLOCK_SESSION_KEY = 'planning.edit_unlock';
    private const EDIT_UNLOCK_TTL_SECONDS = 1800;
    private const EMPLOYEE_ROLES = ['superadmin', 'admin', 'moderator'];

    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $employees = $this->employeesQuery()->get();
        $selectedUser = $employees->firstWhere('id', (int) $request->query('user_id'))
            ?? $employees->first();

        $weekStart = $this->resolveWeekStart($request->query('week'));
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $isEditableWeek = $weekStart->gte(now()->startOfWeek(Carbon::MONDAY));

        $events = $selectedUser
            ? $this->eventsFor($selectedUser->id, $weekStart, $weekEnd)
            : collect();

        return view('employee.plannings.index', [
            'employees' => $employees,
            'selectedUser' => $selectedUser,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'isEditableWeek' => $isEditableWeek,
            'events' => $events,
            'days' => $this->weekDays($weekStart),
        ]);
    }

    public function edit(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $employees = $this->employeesQuery()->get();
        $selectedUser = $employees->firstWhere('id', (int) $request->input('user_id'));
        abort_unless($selectedUser, 404, 'Salarié introuvable.');

        $weekStart = $this->resolveWeekStart($request->input('week'));
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        if ($weekStart->lt(now()->startOfWeek(Carbon::MONDAY))) {
            return redirect()->route('employee.plannings.index', [
                'user_id' => $selectedUser->id,
                'week' => $weekStart->toDateString(),
            ])->with('error', 'Les plannings passés ne sont pas modifiables.');
        }

        $supervisor = $this->requireSuperAdminOrSupervisor(
            $request,
            'La modification du planning exige une validation superviseur.'
        );

        $request->session()->put(self::EDIT_UNLOCK_SESSION_KEY, [
            'user_id' => $selectedUser->id,
            'week' => $weekStart->toDateString(),
            'supervisor_number' => $supervisor?->supervisor_number,
            'expires_at' => time() + self::EDIT_UNLOCK_TTL_SECONDS,
        ]);

        $events = $this->eventsFor($selectedUser->id, $weekStart, $weekEnd)->flatten(1);

        return view('employee.plannings.edit', [
            'selectedUser' => $selectedUser,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'days' => $this->weekDays($weekStart),
            'initialEvents' => $events->map(fn (ScheduleShift $shift) => [
                'date' => $shift->date->toDateString(),
                'start_time' => substr((string) $shift->start_time, 0, 5),
                'end_time' => substr((string) $shift->end_time, 0, 5),
                'title' => $shift->title,
            ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::in($this->employeesQuery()->pluck('id')->all())],
            'week_start' => ['required', 'date'],
            'events' => ['nullable', 'array'],
            'events.*.date' => ['required', 'date'],
            'events.*.start_time' => ['required', 'date_format:H:i'],
            'events.*.end_time' => ['required', 'date_format:H:i', 'after:events.*.start_time'],
            'events.*.title' => ['nullable', 'string', 'max:120'],
        ]);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        if ($weekStart->lt(now()->startOfWeek(Carbon::MONDAY))) {
            throw ValidationException::withMessages([
                'week_start' => 'Les plannings passés ne sont pas modifiables.',
            ]);
        }

        foreach ($validated['events'] ?? [] as $event) {
            $eventDate = Carbon::parse($event['date']);
            if ($eventDate->lt($weekStart) || $eventDate->gt($weekEnd)) {
                throw ValidationException::withMessages([
                    'events' => 'Un événement est en dehors de la semaine sélectionnée.',
                ]);
            }
        }

        $unlock = $request->session()->get(self::EDIT_UNLOCK_SESSION_KEY);
        $unlockValid = is_array($unlock)
            && (int) ($unlock['user_id'] ?? 0) === (int) $validated['user_id']
            && ($unlock['week'] ?? null) === $weekStart->toDateString()
            && (int) ($unlock['expires_at'] ?? 0) > time();

        if ($unlockValid) {
            $request->session()->forget(self::EDIT_UNLOCK_SESSION_KEY);
            $supervisorNumber = $unlock['supervisor_number'] ?? null;
        } else {
            $supervisor = $this->requireSuperAdminOrSupervisor(
                $request,
                'La modification du planning exige une validation superviseur.'
            );
            $supervisorNumber = $supervisor?->supervisor_number;
        }

        DB::transaction(function () use ($validated, $weekStart, $weekEnd) {
            ScheduleShift::where('user_id', $validated['user_id'])
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->delete();

            foreach ($validated['events'] ?? [] as $event) {
                ScheduleShift::create([
                    'user_id' => $validated['user_id'],
                    'date' => $event['date'],
                    'start_time' => $event['start_time'],
                    'end_time' => $event['end_time'],
                    'title' => $event['title'] ?? null,
                    'created_by_id' => auth()->id(),
                ]);
            }
        });

        $employee = User::find($validated['user_id']);

        ActivityLogger::log(
            'planning.updated',
            'Planning modifié pour ' . ($employee->name ?? '—') . ' — semaine du ' . $weekStart->format('d/m/Y'),
            'user',
            (int) $validated['user_id'],
            [
                'validated_by_supervisor' => $supervisorNumber,
                'week_start' => $weekStart->toDateString(),
                'events_count' => count($validated['events'] ?? []),
            ]
        );

        return redirect()->route('employee.plannings.index', [
            'user_id' => $validated['user_id'],
            'week' => $weekStart->toDateString(),
        ])->with('success', 'Planning mis à jour avec succès.');
    }

    public function generatePdf(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'selected_users' => ['required', 'array', 'min:1'],
            'selected_users.*' => ['integer', 'distinct', Rule::in($this->employeesQuery()->pluck('id')->all())],
            'week_start' => ['required', 'date'],
        ], [
            'selected_users.required' => 'Sélectionnez au moins un salarié.',
            'selected_users.min' => 'Sélectionnez au moins un salarié.',
        ]);

        $this->requireStrictSupervisorValidation(
            $request,
            'La génération du planning PDF exige une validation superviseur.'
        );

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $selectedIds = array_map('intval', $validated['selected_users']);
        $employees = $this->employeesQuery()->whereIn('id', $selectedIds)->get();

        if ($employees->count() !== count($selectedIds)) {
            throw ValidationException::withMessages([
                'selected_users' => 'Certains salariés sélectionnés sont introuvables.',
            ]);
        }

        $schedules = $employees->map(fn (User $employee) => [
            'employee' => $employee,
            'events' => $this->eventsFor($employee->id, $weekStart, $weekEnd),
        ]);

        ActivityLogger::log(
            'planning.pdf_created',
            'Génération d\'un planning PDF (' . $employees->count() . ' salarié(s)) — semaine du ' . $weekStart->format('d/m/Y'),
            null,
            null,
            [
                'employee_ids' => $employees->pluck('id')->all(),
                'week_start' => $weekStart->toDateString(),
            ]
        );

        $html = view('employee.plannings.pdf', [
            'schedules' => $schedules,
            'days' => $this->weekDays($weekStart),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'title' => $employees->count() === 1 ? 'Planning individuel' : 'Tableau de service',
            'generatedAt' => now(),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'planning-' . $weekStart->format('Ymd') . '-' . now()->format('His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function eventsFor(int $userId, Carbon $weekStart, Carbon $weekEnd)
    {
        return ScheduleShift::where('user_id', $userId)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (ScheduleShift $shift) => $shift->date->toDateString());
    }

    private function employeesQuery()
    {
        return User::whereIn('global_role', self::EMPLOYEE_ROLES)
            ->where('is_active', true)
            ->orderBy('name');
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

    private function weekDays(Carbon $weekStart): array
    {
        return collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i))->all();
    }
}
