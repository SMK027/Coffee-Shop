<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use App\Support\SupervisorOperation;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FeatureToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_features_requires_supervisor(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->put(route('employee.shop-settings.features.update'), [
                'features' => [
                    Setting::KEY_FEATURE_VOUCHERS => '1',
                ],
            ]);

        $response->assertRedirect(route('employee.supervision.challenge'));
    }

    public function test_superadmin_can_update_features_with_permanent_supervision(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::SHOP_SETTINGS],
        ]);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->withSession([
                'supervision.permanent' => [
                    'user_id' => $superAdmin->id,
                    'supervisor_id' => $supervisor->id,
                    'enabled_at' => time(),
                ],
            ])
            ->put(route('employee.shop-settings.features.update'), [
                'features' => [
                    Setting::KEY_FEATURE_VOUCHERS => '1',
                ],
            ]);

        $response->assertRedirect();
        $this->assertFalse(Setting::isFeatureEnabled(Setting::KEY_FEATURE_QUICK_LOGIN));
        $this->assertTrue(Setting::isFeatureEnabled(Setting::KEY_FEATURE_VOUCHERS));
    }

    public function test_disabled_feature_returns_error_on_api_route(): void
    {
        Setting::set(Setting::KEY_FEATURE_QUICK_LOGIN, '0');

        $response = $this->postJson('/api/auth/login/qr/identifier', [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Cette fonctionnalité a été désactivée par un administrateur.',
        ]);
    }

    public function test_disabled_feature_returns_error_on_web_route(): void
    {
        Setting::set(Setting::KEY_FEATURE_VOUCHERS, '0');
        $admin = User::factory()->create(['global_role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get(route('employee.vouchers.create'));

        $response->assertRedirect(route('employee.dashboard'));
        $response->assertSessionHas('error', 'Cette fonctionnalité a été désactivée par un administrateur.');
    }

    public function test_disabled_board_features_block_pdf_generation(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);

        Setting::set(Setting::KEY_FEATURE_SUPERVISOR_BOARDS, '0');
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->from(route('employee.supervisors.index'))
            ->post(route('employee.supervisors.pdf-board'))
            ->assertRedirect(route('employee.supervisors.index'))
            ->assertSessionHas('error', 'Cette fonctionnalité a été désactivée par un administrateur.');

        Setting::set(Setting::KEY_FEATURE_QUICK_LOGIN_BOARDS, '0');
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->from(route('employee.users.index'))
            ->post(route('employee.users.pdf-board'))
            ->assertRedirect(route('employee.users.index'))
            ->assertSessionHas('error', 'Cette fonctionnalité a été désactivée par un administrateur.');
    }
}
