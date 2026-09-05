<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->withSession([
                'supervision.permanent' => [
                    'user_id' => $superAdmin->id,
                    'supervisor_id' => 1,
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
}
