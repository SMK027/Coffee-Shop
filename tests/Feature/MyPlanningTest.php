<?php

namespace Tests\Feature;

use App\Models\ScheduleShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MyPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_view_their_own_planning_read_only(): void
    {
        $moderator = User::factory()->create(['global_role' => 'moderator']);
        $colleague = User::factory()->create(['global_role' => 'moderator']);

        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        ScheduleShift::create([
            'user_id' => $moderator->id,
            'date' => $weekStart->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'title' => 'Service perso',
        ]);

        ScheduleShift::create([
            'user_id' => $colleague->id,
            'date' => $weekStart->toDateString(),
            'start_time' => '14:00',
            'end_time' => '18:00',
            'title' => 'Service collègue',
        ]);

        $response = $this->actingAs($moderator)->get(route('employee.my-planning.index'));

        $response->assertOk();
        $response->assertSee('Service perso');
        $response->assertDontSee('Service collègue');
    }

    public function test_admin_can_view_their_own_planning_read_only(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('employee.my-planning.index'))
            ->assertOk();
    }
}
