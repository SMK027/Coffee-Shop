<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\ScheduleShift;
use App\Models\Setting;
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
    private const OPENING_HOURS_MARGIN_MINUTES = 30;

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

        $totalWorkedLabel = ScheduleShift::formatDuration(
            ScheduleShift::totalWorkedMinutes($events->flatten(1))
        );

        return view('employee.plannings.index', [
            'employees' => $employees,
            'selectedUser' => $selectedUser,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'isEditableWeek' => $isEditableWeek,
            'events' => $events,
            'days' => $this->weekDays($weekStart),
            'totalWorkedLabel' => $totalWorkedLabel,
        ]);
    }

    public function searchEmployees(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $search = $request->string('q')->trim()->value();

        $employees = $this->employeesQuery()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->limit(10)
            ->get(['id', 'name', 'global_role']);

        return response()->json($employees);
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

        $openRanges = collect($this->weekDays($weekStart))
            ->mapWithKeys(fn (Carbon $day) => [$day->toDateString() => Setting::openRangeForDate($day)]);

        return view('employee.plannings.edit', [
            'selectedUser' => $selectedUser,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'days' => $this->weekDays($weekStart),
            'initialEvents' => $events->map(fn (ScheduleShift $shift) => [
                'type' => $shift->type,
                'date' => $shift->date->toDateString(),
                'start_time' => $shift->start_time ? substr((string) $shift->start_time, 0, 5) : null,
                'end_time' => $shift->end_time ? substr((string) $shift->end_time, 0, 5) : null,
                'title' => $shift->title,
            ])->values(),
            'openRanges' => $openRanges,
            'openingHoursMargin' => self::OPENING_HOURS_MARGIN_MINUTES,
            'bypassOpeningHours' => $selectedUser->isModerator(),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::in($this->employeesQuery()->pluck('id')->all())],
            'week_start' => ['required', 'date'],
            'events' => ['nullable', 'array'],
            'events.*.type' => ['required', Rule::in(ScheduleShift::TYPES)],
            'events.*.date' => ['required', 'date'],
            'events.*.start_time' => ['nullable', 'required_if:events.*.type,' . ScheduleShift::TYPE_WORK . ',' . ScheduleShift::TYPE_MEETING, 'date_format:H:i'],
            'events.*.end_time' => ['nullable', 'required_if:events.*.type,' . ScheduleShift::TYPE_WORK . ',' . ScheduleShift::TYPE_MEETING, 'date_format:H:i', 'after:events.*.start_time'],
            'events.*.title' => ['nullable', 'string', 'max:120'],
        ]);

        $employee = User::find($validated['user_id']);
        $bypassOpeningHours = $employee?->isModerator() ?? false;

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        if ($weekStart->lt(now()->startOfWeek(Carbon::MONDAY))) {
            throw ValidationException::withMessages([
                'week_start' => 'Les plannings passés ne sont pas modifiables.',
            ]);
        }

        foreach (collect($validated['events'] ?? [])->groupBy('date') as $date => $dayEvents) {
            $hasLeave = $dayEvents->contains('type', ScheduleShift::TYPE_LEAVE);
            $hasWork = $dayEvents->contains('type', ScheduleShift::TYPE_WORK);
            $hasAnyAbsence = $dayEvents->contains('type', ScheduleShift::TYPE_ABSENCE);
            $hasFullDayAbsence = $dayEvents->contains(fn ($event) => $event['type'] === ScheduleShift::TYPE_ABSENCE && empty($event['start_time'] ?? null));

            // Un congé occupe la journée entière : aucune autre activité ne peut cohabiter avec lui.
            if ($hasLeave && ($hasWork || $hasAnyAbsence)) {
                throw ValidationException::withMessages([
                    'events' => 'Le ' . Carbon::parse($date)->format('d/m/Y') . ' cumule un congé avec une autre activité sur la même journée.',
                ]);
            }

            // Une absence partielle peut cohabiter avec un service, mais une absence d'une
            // journée entière ne le peut pas.
            if ($hasFullDayAbsence && $hasWork) {
                throw ValidationException::withMessages([
                    'events' => 'Le ' . Carbon::parse($date)->format('d/m/Y') . ' cumule une absence d\'une journée entière avec un service.',
                ]);
            }

            $hasMeeting = $dayEvents->contains('type', ScheduleShift::TYPE_MEETING);

            if ($hasMeeting && ($hasLeave || $hasAnyAbsence)) {
                throw ValidationException::withMessages([
                    'events' => 'Une réunion ou une formation ne peut pas être ajoutée un jour de congé ou d\'absence (' . Carbon::parse($date)->format('d/m/Y') . ').',
                ]);
            }
        }

        foreach ($validated['events'] ?? [] as $event) {
            $eventDate = Carbon::parse($event['date']);
            if ($eventDate->lt($weekStart) || $eventDate->gt($weekEnd)) {
                throw ValidationException::withMessages([
                    'events' => 'Un événement est en dehors de la semaine sélectionnée.',
                ]);
            }

            if ($event['type'] === ScheduleShift::TYPE_LEAVE && (! empty($event['start_time'] ?? null) || ! empty($event['end_time'] ?? null))) {
                throw ValidationException::withMessages([
                    'events' => 'Un congé est toujours posé sur une journée entière, sans horaires.',
                ]);
            }

            if (! empty($event['start_time'] ?? null) !== ! empty($event['end_time'] ?? null)) {
                throw ValidationException::withMessages([
                    'events' => 'Une plage horaire doit avoir une heure de début et une heure de fin.',
                ]);
            }

            if (in_array($event['type'], [ScheduleShift::TYPE_WORK, ScheduleShift::TYPE_MEETING], true) && ! $bypassOpeningHours) {
                $range = Setting::openRangeForDate($eventDate);

                if (! $range) {
                    throw ValidationException::withMessages([
                        'events' => 'La boutique est fermée le ' . $eventDate->format('d/m/Y') . ', aucun service ne peut y être planifié.',
                    ]);
                }

                [$fromHour, $fromMinute] = array_map('intval', explode(':', $range['from']));
                [$toHour, $toMinute] = array_map('intval', explode(':', $range['to']));
                $openBound = $eventDate->copy()->setTime($fromHour, $fromMinute)->subMinutes(self::OPENING_HOURS_MARGIN_MINUTES);
                $closeBound = $eventDate->copy()->setTime($toHour, $toMinute)->addMinutes(self::OPENING_HOURS_MARGIN_MINUTES);

                [$startHour, $startMinute] = array_map('intval', explode(':', $event['start_time']));
                [$endHour, $endMinute] = array_map('intval', explode(':', $event['end_time']));
                $shiftStart = $eventDate->copy()->setTime($startHour, $startMinute);
                $shiftEnd = $eventDate->copy()->setTime($endHour, $endMinute);

                if ($shiftStart->lt($openBound) || $shiftEnd->gt($closeBound)) {
                    throw ValidationException::withMessages([
                        'events' => 'Le créneau du ' . $eventDate->format('d/m/Y') . ' dépasse les horaires d\'ouverture (marge de 30 minutes tolérée).',
                    ]);
                }
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
                    'type' => $event['type'],
                    'date' => $event['date'],
                    'start_time' => $event['start_time'] ?? null,
                    'end_time' => $event['end_time'] ?? null,
                    'title' => $event['title'] ?? null,
                    'created_by_id' => auth()->id(),
                ]);
            }
        });

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

        $validatedSupervisor = $this->requireStrictSupervisorValidation(
            $request,
            'La génération du planning PDF exige une validation superviseur.'
        );
        $validatedSupervisor->load(['holderAdmin:id,name', 'superadmin:id,name']);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $selectedIds = array_map('intval', $validated['selected_users']);
        $employees = $this->employeesQuery()->whereIn('id', $selectedIds)->get();

        if ($employees->count() !== count($selectedIds)) {
            throw ValidationException::withMessages([
                'selected_users' => 'Certains salariés sélectionnés sont introuvables.',
            ]);
        }

        $schedules = $employees->map(function (User $employee) use ($weekStart, $weekEnd) {
            $events = $this->eventsFor($employee->id, $weekStart, $weekEnd);

            return [
                'employee' => $employee,
                'events' => $events,
                'totalWorkedLabel' => ScheduleShift::formatDuration(
                    ScheduleShift::totalWorkedMinutes($events->flatten(1))
                ),
            ];
        });

        ActivityLogger::log(
            'planning.pdf_created',
            'Génération d\'un planning PDF (' . $employees->count() . ' salarié(s)) — semaine du ' . $weekStart->format('d/m/Y'),
            null,
            null,
            [
                'employee_ids' => $employees->pluck('id')->all(),
                'week_start' => $weekStart->toDateString(),
                'validated_by_supervisor' => $validatedSupervisor->supervisor_number,
            ]
        );

        $html = view('employee.plannings.pdf', [
            'schedules' => $schedules,
            'days' => $this->weekDays($weekStart),
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'title' => $employees->count() === 1 ? 'Planning individuel' : 'Tableau de service',
            'generatedAt' => now(),
            'issuedBySupervisor' => $validatedSupervisor,
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        // Le tableau de service (plusieurs salariés) est plus lisible en paysage : chaque salarié
        // tient sur une seule ligne avec les 7 jours côte à côte.
        $dompdf->setPaper('A4', $employees->count() > 1 ? 'landscape' : 'portrait');
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
