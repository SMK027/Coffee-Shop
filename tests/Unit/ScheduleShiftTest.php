<?php

namespace Tests\Unit;

use App\Models\ScheduleShift;
use PHPUnit\Framework\TestCase;

class ScheduleShiftTest extends TestCase
{
    private function shift(string $type, ?string $start, ?string $end): ScheduleShift
    {
        return new ScheduleShift([
            'type' => $type,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function test_total_worked_minutes_counts_work_and_meeting_shifts(): void
    {
        $shifts = [
            $this->shift(ScheduleShift::TYPE_WORK, '09:00', '17:00'),
            $this->shift(ScheduleShift::TYPE_MEETING, '10:00', '11:00'),
        ];

        $this->assertSame(9 * 60, ScheduleShift::totalWorkedMinutes($shifts));
    }

    public function test_total_worked_minutes_excludes_leave_and_absence(): void
    {
        $shifts = [
            $this->shift(ScheduleShift::TYPE_WORK, '09:00', '17:00'),
            $this->shift(ScheduleShift::TYPE_LEAVE, null, null),
            $this->shift(ScheduleShift::TYPE_ABSENCE, '14:00', '15:00'),
            $this->shift(ScheduleShift::TYPE_ABSENCE, null, null),
        ];

        $this->assertSame(8 * 60, ScheduleShift::totalWorkedMinutes($shifts));
    }

    public function test_total_worked_minutes_is_zero_without_work_or_meeting_shifts(): void
    {
        $shifts = [
            $this->shift(ScheduleShift::TYPE_LEAVE, null, null),
        ];

        $this->assertSame(0, ScheduleShift::totalWorkedMinutes($shifts));
    }
}
