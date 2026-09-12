<?php

namespace Tests\Feature;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOrderItemEditingTest extends TestCase
{
    use RefreshDatabase;

    private function drink(float $price = 3.5): Drink
    {
        $category = DrinkCategory::create(['name' => 'Cafés', 'slug' => 'cafes-' . uniqid(), 'sort_order' => 1]);

        return Drink::create([
            'category_id' => $category->id,
            'name' => 'Espresso',
            'slug' => 'espresso-' . uniqid(),
            'price' => $price,
            'available' => true,
            'sort_order' => 1,
            'loyalty_points' => 1,
        ]);
    }

    private function orderWithOneItem(string $status = Order::STATUS_PENDING): Order
    {
        $drink = $this->drink(3.5);

        $order = Order::create([
            'customer_name' => 'Client Test',
            'status' => $status,
            'total_amount' => 3.5,
            'is_employee_order' => false,
        ]);

        $order->items()->create([
            'drink_id' => $drink->id,
            'quantity' => 1,
            'unit_price' => 3.5,
        ]);

        return $order->fresh();
    }

    public function test_the_order_payload_exposes_whether_items_can_be_edited(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $pendingOrder = $this->orderWithOneItem();
        $servingOrder = $this->orderWithOneItem(Order::STATUS_SERVING);

        $this->actingAs($admin, 'api')->getJson('/api/orders/' . $pendingOrder->id)
            ->assertOk()
            ->assertJsonPath('order.can_edit_items', true);

        $this->actingAs($admin, 'api')->getJson('/api/orders/' . $servingOrder->id)
            ->assertOk()
            ->assertJsonPath('order.can_edit_items', false);
    }

    public function test_an_item_can_be_added_via_the_mobile_api_while_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();
        $extraDrink = $this->drink(2.0);

        $response = $this->actingAs($admin, 'api')->postJson('/api/orders/' . $order->id . '/items', [
            'drink_id' => $extraDrink->id,
            'quantity' => 2,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas(OrderItem::class, [
            'order_id' => $order->id,
            'drink_id' => $extraDrink->id,
            'quantity' => 2,
        ]);
        $this->assertSame('7.50', $order->fresh()->total_amount);
    }

    public function test_an_item_cannot_be_added_via_the_mobile_api_once_not_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem(Order::STATUS_PREPARING);
        $extraDrink = $this->drink(2.0);

        $response = $this->actingAs($admin, 'api')->postJson('/api/orders/' . $order->id . '/items', [
            'drink_id' => $extraDrink->id,
            'quantity' => 1,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing(OrderItem::class, [
            'order_id' => $order->id,
            'drink_id' => $extraDrink->id,
        ]);
    }

    public function test_an_item_can_be_removed_via_the_mobile_api_while_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();
        $extraDrink = $this->drink(2.0);
        $extraItem = $order->items()->create([
            'drink_id' => $extraDrink->id,
            'quantity' => 1,
            'unit_price' => 2.0,
        ]);
        $order->update(['total_amount' => 5.5]);

        $response = $this->actingAs($admin, 'api')->deleteJson('/api/orders/' . $order->id . '/items/' . $extraItem->id);

        $response->assertOk();
        $this->assertDatabaseMissing(OrderItem::class, ['id' => $extraItem->id]);
        $this->assertSame('3.50', $order->fresh()->total_amount);
    }

    public function test_the_last_remaining_item_cannot_be_removed_via_the_mobile_api(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();
        $item = $order->items()->first();

        $response = $this->actingAs($admin, 'api')->deleteJson('/api/orders/' . $order->id . '/items/' . $item->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas(OrderItem::class, ['id' => $item->id]);
    }

    public function test_an_item_cannot_be_removed_via_the_mobile_api_once_not_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem(Order::STATUS_COMPLETED);
        $item = $order->items()->first();

        $response = $this->actingAs($admin, 'api')->deleteJson('/api/orders/' . $order->id . '/items/' . $item->id);

        $response->assertStatus(422);
        $this->assertDatabaseHas(OrderItem::class, ['id' => $item->id]);
    }
}
