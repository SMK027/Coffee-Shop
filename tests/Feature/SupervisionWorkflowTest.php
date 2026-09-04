<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}