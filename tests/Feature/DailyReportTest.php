<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_create_page_is_reachable_and_not_intercepted_by_the_show_route(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('employee.daily-reports.create'))
            ->assertOk();
    }

    public function test_the_show_route_still_resolves_an_existing_report(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $report = DailyReport::create([
            'report_date' => today()->toDateString(),
            'generated_by' => $admin->id,
            'total_collected' => 0,
            'total_refunded' => 0,
            'total_vouchers_issued' => 0,
            'breakdown' => [],
            'refund_breakdown' => [],
            'vouchers_issued' => [],
        ]);

        $this->actingAs($admin)
            ->get(route('employee.daily-reports.show', $report))
            ->assertOk();
    }

    public function test_a_non_numeric_report_id_returns_a_404_instead_of_the_create_page(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);

        $this->actingAs($admin)
            ->get('/espace-employe/recapitulatifs/introuvable')
            ->assertNotFound();
    }
}
