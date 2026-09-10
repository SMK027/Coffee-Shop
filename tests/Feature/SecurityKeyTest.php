<?php

namespace Tests\Feature;

use App\Models\Supervisor;
use App\Models\User;
use App\Support\SupervisorOperation;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_login_habilitation_label_mentions_security_keys(): void
    {
        $label = SupervisorOperation::label(SupervisorOperation::QR_LOGIN);

        $this->assertStringContainsString('clé de sécurité', $label);
        $this->assertStringContainsString('QR code', $label);
    }

    public function test_login_page_shows_security_key_option(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('login.security-key'), false);
    }

    public function test_employee_can_view_registration_challenge_for_their_own_profile(): void
    {
        $employee = User::factory()->create(['global_role' => 'admin']);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($employee)
            ->postJson(route('employee.profile.security-keys.options'));

        $response->assertOk();
        $response->assertJsonStructure(['options' => ['challenge', 'rp', 'user', 'pubKeyCredParams']]);
    }

    public function test_guest_cannot_register_a_security_key_on_their_profile(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->postJson(route('employee.profile.security-keys.options'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_register_a_supervisor_security_key(): void
    {
        $moderator = User::factory()->create(['global_role' => 'moderator']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SEC100',
            'password' => Hash::make('1234'),
            'superadmin_id' => User::factory()->create(['global_role' => 'superadmin'])->id,
            'is_active' => true,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($moderator)
            ->postJson(route('employee.supervisors.security-keys.options', $supervisor), ['supervisor_pin' => '1234'])
            ->assertForbidden();
    }

    public function test_supervisor_security_key_registration_requires_correct_pin(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SEC101',
            'password' => Hash::make('1234'),
            'superadmin_id' => User::factory()->create(['global_role' => 'superadmin'])->id,
            'is_active' => true,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->postJson(route('employee.supervisors.security-keys.options', $supervisor), ['supervisor_pin' => '0000'])
            ->assertJsonValidationErrors('supervisor_pin');
    }

    public function test_supervisor_security_key_registration_challenge_succeeds_with_correct_pin(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SEC102',
            'password' => Hash::make('1234'),
            'superadmin_id' => User::factory()->create(['global_role' => 'superadmin'])->id,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->postJson(route('employee.supervisors.security-keys.options', $supervisor), ['supervisor_pin' => '1234']);

        $response->assertOk();
        $response->assertJsonStructure(['options' => ['challenge', 'rp', 'user', 'pubKeyCredParams']]);
    }

    public function test_security_key_routes_are_disabled_when_feature_flag_is_off(): void
    {
        \App\Models\Setting::set(\App\Models\Setting::KEY_FEATURE_SECURITY_KEYS, '0');

        $this->get(route('login.security-key'))->assertRedirect(route('employee.dashboard'));
    }

    public function test_generic_supervisor_security_key_challenge_does_not_leak_supervisor_existence(): void
    {
        $unknown = $this->withoutMiddleware(PreventRequestForgery::class)
            ->postJson(route('supervisor-security-key.options'), ['supervisor_number' => 'DOES-NOT-EXIST']);
        $unknown->assertOk();
        $unknown->assertJsonPath('options.allowCredentials', []);

        Supervisor::create([
            'supervisor_number' => 'SEC103',
            'password' => Hash::make('1234'),
            'superadmin_id' => User::factory()->create(['global_role' => 'superadmin'])->id,
            'is_active' => true,
        ]);

        $known = $this->withoutMiddleware(PreventRequestForgery::class)
            ->postJson(route('supervisor-security-key.options'), ['supervisor_number' => 'SEC103']);
        $known->assertOk();
        // Aucune clé enregistrée pour ce superviseur : liste vide également,
        // même structure de réponse dans les deux cas.
        $known->assertJsonPath('options.allowCredentials', []);
    }
}
