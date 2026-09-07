<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_operation_is_held_until_supervisor_approval(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->post(route('employee.payment-methods.store'), [
            'name' => 'Paiement test',
            'slug' => 'payment-test',
        ]);

        $response->assertRedirect(route('employee.supervision.challenge'));
        $response->assertSessionHas('supervision.pending');
        $this->assertDatabaseMissing(PaymentMethod::class, ['slug' => 'payment-test']);
    }

    public function test_supervisor_deletion_via_challenge_workflow(): void
    {
        \App\Models\Setting::set(\App\Models\Setting::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS, '127.0.0.1');

        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $supervisor1 = Supervisor::create([
            'supervisor_number' => 'SUP001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
        ]);
        $supervisor2 = Supervisor::create([
            'supervisor_number' => 'SUP002',
            'password' => Hash::make('5678'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
        ]);

        // Étape 1 : demande de suppression de SUP001 sans identifiants superviseur -> redirection challenge
        $response1 = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->delete(route('employee.supervisors.destroy', $supervisor1));

        $response1->assertRedirect(route('employee.supervision.challenge'));

        // Étape 2 : validation sur la page challenge avec le PIN de SUP002
        $response2 = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->post(route('employee.supervision.approve'), [
                'supervisor_number' => 'SUP002',
                'supervisor_pin' => '5678',
            ]);

        $response2->assertStatus(200);

        // Étape 3 : rejeu de l'action de suppression avec le nonce de bypass généré
        $bypassNonce = session('supervision.bypasses') ? array_keys(session('supervision.bypasses'))[0] ?? null : null;

        $response3 = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->delete(route('employee.supervisors.destroy', $supervisor1), [
                '__supervision_bypass_nonce' => $bypassNonce,
            ]);

        $response3->assertRedirect(route('employee.supervisors.index'));
        $this->assertDatabaseMissing(Supervisor::class, ['id' => $supervisor1->id]);
    }

    public function test_supervisor_cannot_validate_own_deletion(): void
    {
        \App\Models\Setting::set(\App\Models\Setting::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS, '127.0.0.1');

        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $supervisor1 = Supervisor::create([
            'supervisor_number' => 'SUP001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
        ]);

        $response1 = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->from(route('employee.supervisors.index'))
            ->delete(route('employee.supervisors.destroy', $supervisor1));

        $response1->assertRedirect(route('employee.supervision.challenge'));

        // Approbation avec le propre PIN de SUP001
        $response2 = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->post(route('employee.supervision.approve'), [
                'supervisor_number' => 'SUP001',
                'supervisor_pin' => '1234',
            ]);

        $response2->assertStatus(200);

        // Rejeu -> doit être rejeté par la validation dans SupervisorController::destroy et rediriger vers la page d'origine
        $bypassNonce = session('supervision.bypasses') ? array_keys(session('supervision.bypasses'))[0] ?? null : null;

        $response3 = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->from(route('employee.supervisors.index'))
            ->delete(route('employee.supervisors.destroy', $supervisor1), [
                '__supervision_bypass_nonce' => $bypassNonce,
            ]);

        $response3->assertRedirect(route('employee.supervisors.index'));
        $response3->assertSessionHasErrors(['supervisor_number']);
        $this->assertDatabaseHas(Supervisor::class, ['id' => $supervisor1->id]);
    }

    public function test_responsible_superadmin_can_change_supervisor_holder(): void
    {
        \App\Models\Setting::set(\App\Models\Setting::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS, '127.0.0.1');

        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $holder = User::factory()->create(['global_role' => 'admin', 'is_active' => true]);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP001',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($superAdmin)
            ->put(route('employee.supervisors.update', $supervisor), [
                'supervisor_number' => 'SUP001',
                'is_active' => '1',
                'holder_admin_id' => $holder->id,
            ]);

        $response->assertRedirect(route('employee.supervisors.index'));
        $this->assertDatabaseHas(Supervisor::class, [
            'id' => $supervisor->id,
            'holder_admin_id' => $holder->id,
        ]);
    }

    public function test_quick_login_can_be_reactivated_after_supervisor_validation(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $user = User::factory()->create(['global_role' => 'admin', 'quick_login_disabled' => true]);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP010',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($superAdmin)
            ->post(route('employee.users.quick-login.reactivate', $user))
            ->assertRedirect(route('employee.supervision.challenge'));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($superAdmin)
            ->post(route('employee.supervision.approve'), ['supervisor_number' => $supervisor->supervisor_number, 'supervisor_pin' => '1234']);
        $nonce = array_key_first(session('supervision.bypasses', []));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($superAdmin)
            ->post(route('employee.users.quick-login.reactivate', $user), ['__supervision_bypass_nonce' => $nonce])
            ->assertRedirect(route('employee.users.index'));

        $this->assertFalse($user->fresh()->quick_login_disabled);
    }

    public function test_superadmin_can_take_and_release_control_without_nested_takeover(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $target = User::factory()->create(['global_role' => 'superadmin']);
        $otherTarget = User::factory()->create(['global_role' => 'superadmin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP011',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($superAdmin)
            ->post(route('employee.users.take-control', $target))
            ->assertRedirect(route('employee.supervision.challenge'));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($superAdmin)
            ->post(route('employee.supervision.approve'), ['supervisor_number' => $supervisor->supervisor_number, 'supervisor_pin' => '1234']);
        $nonce = array_key_first(session('supervision.bypasses', []));
        $this->withoutMiddleware(PreventRequestForgery::class)->actingAs($superAdmin)
            ->post(route('employee.users.take-control', $target), ['__supervision_bypass_nonce' => $nonce])
            ->assertRedirect(route('employee.dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(route('employee.users.take-control', $otherTarget))
            ->assertRedirect()
            ->assertSessionHas('error', 'Une prise de contrôle est déjà active. Revenez d’abord à votre compte.');
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->post(route('employee.users.release-control'))
            ->assertRedirect(route('employee.users.index'));

        $this->assertAuthenticatedAs($superAdmin);
    }
}