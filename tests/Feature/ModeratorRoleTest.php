<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeratorRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_create_and_view_orders_but_cannot_access_vouchers()
    {
        $moderator = User::factory()->create(['global_role' => 'moderator']);

        $this->actingAs($moderator)
            ->get(route('employee.orders.create'))
            ->assertStatus(200);

        $this->actingAs($moderator)
            ->get(route('employee.orders.index'))
            ->assertStatus(200);

        // Vouchers index should be forbidden for moderator
        $this->actingAs($moderator)
            ->get(route('employee.vouchers.index'))
            ->assertStatus(403);
    }
}
