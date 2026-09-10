<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\Voucher;
use App\Support\SupervisorOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileApiSupervisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_still_needs_supervisor_validation_to_create_a_voucher_via_mobile(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);

        $response = $this->actingAs($superAdmin, 'api')->postJson('/api/vouchers', [
            'amount' => 10,
            'validity_days' => 30,
            'restriction_type' => 'none',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing(Voucher::class, ['amount' => 10]);
    }

    public function test_superadmin_with_habilitated_supervisor_can_create_a_voucher_via_mobile(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        Supervisor::create([
            'supervisor_number' => 'MOB100',
            'password' => Hash::make('1234'),
            'superadmin_id' => $superAdmin->id,
            'is_active' => true,
            'permissions' => [SupervisorOperation::VOUCHERS],
        ]);

        $response = $this->actingAs($superAdmin, 'api')->postJson('/api/vouchers', [
            'amount' => 10,
            'validity_days' => 30,
            'restriction_type' => 'none',
            'supervisor_number' => 'MOB100',
            'supervisor_pin' => '1234',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas(Voucher::class, ['amount' => 10, 'issued_by' => $superAdmin->id]);
    }

    public function test_deleting_a_voucher_via_mobile_requires_supervisor_validation(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $voucher = Voucher::create([
            'code' => Voucher::generateCode(),
            'amount' => 10,
            'issued_by' => $admin->id,
            'issued_by_name' => $admin->name,
            'issued_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($admin, 'api')->deleteJson('/api/vouchers/' . $voucher->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas(Voucher::class, ['id' => $voucher->id]);
    }

    public function test_superadmin_still_needs_supervisor_validation_to_delete_an_order_via_mobile(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);
        $order = Order::create([
            'customer_name' => 'Client test',
            'is_employee_order' => false,
            'status' => Order::STATUS_CANCELLED,
            'total_amount' => 0,
            'discount_amount' => 0,
            'loyalty_points_spent' => 0,
            'loyalty_discount_amount' => 0,
            'voucher_discount_amount' => 0,
            'handled_by' => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin, 'api')->deleteJson('/api/orders/' . $order->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas(Order::class, ['id' => $order->id]);
    }

    public function test_superadmin_still_needs_supervisor_validation_to_reopen_a_terminal_order_via_mobile(): void
    {
        $superAdmin = User::factory()->create(['global_role' => 'superadmin']);

        OrderStatus::create([
            'key' => 'termine_test',
            'label' => 'Terminé (test)',
            'color' => 'green',
            'sort_order' => 1,
            'is_active' => true,
            'is_terminal' => true,
            'triggers_loyalty_credit' => false,
        ]);
        OrderStatus::create([
            'key' => 'en_cours_test',
            'label' => 'En cours (test)',
            'color' => 'blue',
            'sort_order' => 2,
            'is_active' => true,
            'is_terminal' => false,
            'triggers_loyalty_credit' => false,
        ]);

        $order = Order::create([
            'customer_name' => 'Client test',
            'is_employee_order' => false,
            'status' => 'termine_test',
            'total_amount' => 0,
            'discount_amount' => 0,
            'loyalty_points_spent' => 0,
            'loyalty_discount_amount' => 0,
            'voucher_discount_amount' => 0,
            'handled_by' => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin, 'api')->patchJson('/api/orders/' . $order->id . '/status', [
            'status' => 'en_cours_test',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas(Order::class, ['id' => $order->id, 'status' => 'termine_test']);
    }
}
