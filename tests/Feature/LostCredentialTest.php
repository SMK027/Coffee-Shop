<?php

namespace Tests\Feature;

use App\Models\InternalNote;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LostCredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_lost_credentials_quarantines_supervisor_and_issues_temporary_replacement(): void
    {
        Setting::set(Setting::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS, '127.0.0.1');
        $admin = User::factory()->create(['global_role' => 'superadmin']);
        $holder = User::factory()->create(['global_role' => 'admin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => '0001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'holder_admin_id' => $holder->id,
            'is_active' => true,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($admin)
            ->get(route('employee.lost-credentials.create'))
            ->assertRedirect(route('employee.supervision.challenge'));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($admin)
            ->post(route('employee.supervision.approve'), ['supervisor_number' => '0001', 'supervisor_pin' => '1234']);
        $nonce = array_key_first(session('supervision.bypasses', []));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($admin)
            ->get(route('employee.lost-credentials.create', ['__supervision_bypass_nonce' => $nonce]))
            ->assertOk();

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($admin)
            ->post(route('employee.lost-credentials.store'), [
                'supervisor_ids' => [$supervisor->id],
                'user_ids' => [$holder->id],
            ])->assertRedirect(route('employee.lost-credentials.create'));

        $this->assertDatabaseHas(Supervisor::class, ['id' => $supervisor->id, 'is_active' => false]);
        $temporary = Supervisor::where('replaces_supervisor_id', $supervisor->id)->firstOrFail();
        $this->assertTrue($temporary->is_temporary);
        $this->assertTrue(str_starts_with($temporary->supervisor_number, '70001'));
        $this->assertNotNull($temporary->temporary_expires_at);
        $this->assertDatabaseHas(User::class, ['id' => $holder->id, 'quick_login_disabled' => true]);
        $this->assertDatabaseHas(InternalNote::class, ['title' => 'Identifiants superviseur temporaires']);
        $this->assertDatabaseHas(InternalNote::class, ['title' => 'Connexion rapide désactivée']);
        $this->assertFalse($supervisor->fresh()->isActive());
        $this->assertNull(User::fromLoginToken($holder->loginBarcodeValue()));
    }

    public function test_expired_temporary_supervisor_is_deactivated(): void
    {
        $admin = User::factory()->create(['global_role' => 'superadmin']);
        $temporary = Supervisor::create([
            'supervisor_number' => '70001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'is_temporary' => true,
            'temporary_expires_at' => now()->subMinute(),
        ]);

        $this->artisan('supervisors:disable-expired-temporary')->assertSuccessful();
        $this->assertDatabaseHas(Supervisor::class, ['id' => $temporary->id, 'is_active' => false]);
    }
}
