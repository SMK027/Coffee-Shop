<?php

namespace Tests\Feature;

use App\Models\InternalNote;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use App\Support\SupervisorOperation;
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
        $otherUser = User::factory()->create(['global_role' => 'moderator']);
        $supervisor = Supervisor::create([
            'supervisor_number' => '0001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'holder_admin_id' => $holder->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::LOST_CREDENTIALS],
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
                'user_ids' => [$holder->id, $otherUser->id],
            ])->assertRedirect(route('employee.lost-credentials.create'));

        $this->assertDatabaseHas(Supervisor::class, ['id' => $supervisor->id, 'is_active' => false]);
        $temporary = Supervisor::where('replaces_supervisor_id', $supervisor->id)->firstOrFail();
        $this->assertTrue($temporary->is_temporary);
        $this->assertTrue(str_starts_with($temporary->supervisor_number, '70001'));
        $this->assertNotNull($temporary->temporary_expires_at);
        $this->assertDatabaseHas(User::class, ['id' => $holder->id, 'quick_login_disabled' => true]);
        $this->assertDatabaseHas(User::class, ['id' => $otherUser->id, 'quick_login_disabled' => true]);
        $this->assertDatabaseHas(InternalNote::class, ['title' => 'Identifiants superviseur temporaires']);
        $this->assertSame(1, InternalNote::where('title', 'Connexion rapide désactivée')->count());
        $this->assertSame(2, InternalNote::where('title', 'Connexion rapide désactivée')->firstOrFail()->recipients()->count());
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

    public function test_expired_temporary_supervisor_removes_internal_note_with_codes(): void
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
        $note = InternalNote::create([
            'author_id' => $admin->id,
            'title' => 'Identifiants superviseur temporaires',
            'description' => 'Votre superviseur temporaire est : 70001',
            'display_location' => 'inbox',
            'background_color' => '#FEF3C7',
            'text_color' => '#78350F',
            'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
        ]);

        $this->artisan('supervisors:disable-expired-temporary')->assertSuccessful();

        $this->assertDatabaseHas(Supervisor::class, ['id' => $temporary->id, 'is_active' => false]);
        $this->assertDatabaseMissing(InternalNote::class, ['id' => $note->id]);
    }

    public function test_quarantined_supervisor_can_be_reactivated_by_changing_pin_and_removes_temporary_credentials(): void
    {
        Setting::set(Setting::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS, '127.0.0.1');
        $admin = User::factory()->create(['global_role' => 'superadmin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => '0001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => false,
            'quarantined_until' => now()->addDays(10),
        ]);
        $temporary = Supervisor::create([
            'supervisor_number' => '70001',
            'password' => Hash::make('5678'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'is_temporary' => true,
            'replaces_supervisor_id' => $supervisor->id,
            'temporary_expires_at' => now()->addDays(10),
        ]);
        $note = InternalNote::create([
            'author_id' => $admin->id,
            'title' => 'Identifiants superviseur temporaires',
            'description' => 'Votre superviseur temporaire est : 70001',
            'display_location' => 'inbox',
            'background_color' => '#FEF3C7',
            'text_color' => '#78350F',
            'font_family' => 'Figtree, ui-sans-serif, system-ui, sans-serif',
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($admin)
            ->put(route('employee.supervisors.update', $supervisor), [
                'supervisor_number' => '0001',
                'supervisor_pin' => '9999',
                'is_active' => '0',
                'reactivate_after_pin_reset' => '1',
            ])
            ->assertRedirect(route('employee.supervisors.index'));

        $supervisor->refresh();
        $this->assertTrue($supervisor->is_active);
        $this->assertNull($supervisor->quarantined_until);
        $this->assertDatabaseMissing(Supervisor::class, ['id' => $temporary->id]);
        $this->assertDatabaseMissing(InternalNote::class, ['id' => $note->id]);
    }
}
