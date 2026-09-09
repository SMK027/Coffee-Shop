<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PaymentMethod;
use App\Models\Supervisor;
use App\Models\User;
use App\Support\SupervisorOperation;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisorHabilitationTest extends TestCase
{
    use RefreshDatabase;

    private const DENIAL_MESSAGE = 'Echec authentification superviseur: fonctionnalité non autorisée';

    public function test_supervisor_without_habilitation_cannot_unlock_the_operation(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP100',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::VOUCHERS], // habilité pour une autre opération
        ]);

        $response = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.payment-methods.store'), [
                'name' => 'Paiement test',
                'slug' => 'payment-test',
            ]);
        $response->assertRedirect(route('employee.supervision.challenge'));

        $approve = $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.supervision.approve'), [
                'supervisor_number' => 'SUP100',
                'supervisor_pin' => '1234',
            ]);

        $approve->assertSessionHasErrors(['supervisor_pin' => self::DENIAL_MESSAGE]);
        $this->assertDatabaseMissing(PaymentMethod::class, ['slug' => 'payment-test']);
        $this->assertDatabaseHas(ActivityLog::class, [
            'action' => 'auth.supervisor_denied',
        ]);

        // Aucun bypass n'a été accordé : l'opération reste en attente.
        $this->assertNotEmpty(session('supervision.pending'));
        $this->assertEmpty(session('supervision.bypasses', []));
    }

    public function test_operation_can_still_be_unlocked_by_another_supervisor_with_sufficient_habilitation(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        Supervisor::create([
            'supervisor_number' => 'SUP100',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::VOUCHERS],
        ]);
        Supervisor::create([
            'supervisor_number' => 'SUP101',
            'password' => Hash::make('5678'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::PAYMENT_METHODS],
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.payment-methods.store'), [
                'name' => 'Paiement test',
                'slug' => 'payment-test',
            ])
            ->assertRedirect(route('employee.supervision.challenge'));

        // Première tentative : superviseur non habilité, refusée.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.supervision.approve'), [
                'supervisor_number' => 'SUP100',
                'supervisor_pin' => '1234',
            ])
            ->assertSessionHasErrors(['supervisor_pin' => self::DENIAL_MESSAGE]);

        $this->assertDatabaseMissing(PaymentMethod::class, ['slug' => 'payment-test']);

        // Seconde tentative avec un autre superviseur habilité : l'opération aboutit.
        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.supervision.approve'), [
                'supervisor_number' => 'SUP101',
                'supervisor_pin' => '5678',
            ])
            ->assertStatus(200);

        $nonce = array_key_first(session('supervision.bypasses', []));

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.payment-methods.store'), [
                'name' => 'Paiement test',
                'slug' => 'payment-test',
                '__supervision_bypass_nonce' => $nonce,
            ])
            ->assertRedirect(route('employee.payment-methods.index'));

        $this->assertDatabaseHas(PaymentMethod::class, ['slug' => 'payment-test']);
    }

    public function test_json_api_request_receives_explicit_denial_message(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $supervisor = Supervisor::create([
            'supervisor_number' => 'SUP102',
            'password' => Hash::make('1234'),
            'superadmin_id' => $admin->id,
            'is_active' => true,
            'permissions' => [], // aucune habilitation
        ]);

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/loyalty-discounts', [
                'name' => 'Réduction test',
                'points_cost' => 10,
                'discount_type' => 'fixed',
                'discount_value' => 1,
                'supervisor_number' => 'SUP102',
                'supervisor_pin' => '1234',
            ]);

        $response->assertStatus(403);
        $response->assertJson(['message' => self::DENIAL_MESSAGE]);
    }
}
