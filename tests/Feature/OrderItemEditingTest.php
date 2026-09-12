<?php

namespace Tests\Feature;

use App\Models\Drink;
use App\Models\DrinkCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemEditingTest extends TestCase
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

    private function orderWithOneItem(string $status = Order::STATUS_PENDING, bool $isEmployeeOrder = false): Order
    {
        $drink = $this->drink(3.5);

        $order = Order::create([
            'customer_name' => 'Client Test',
            'status' => $status,
            'total_amount' => 3.5,
            'is_employee_order' => $isEmployeeOrder,
        ]);

        $order->items()->create([
            'drink_id' => $drink->id,
            'quantity' => 1,
            'unit_price' => 3.5,
        ]);

        return $order->fresh();
    }

    public function test_an_item_can_be_added_while_the_order_is_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();
        $extraDrink = $this->drink(2.0);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.orders.items.store', $order), [
                'drink_id' => $extraDrink->id,
                'quantity' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(OrderItem::class, [
            'order_id' => $order->id,
            'drink_id' => $extraDrink->id,
            'quantity' => 2,
        ]);

        // 3.5 (existant) + 2 x 2.0 (nouveau) = 7.5
        $this->assertSame('7.50', $order->fresh()->total_amount);
    }

    public function test_a_custom_item_can_be_added(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.orders.items.store', $order), [
                'custom_label' => 'Pâtisserie maison',
                'custom_price' => 4,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(OrderItem::class, [
            'order_id' => $order->id,
            'custom_label' => 'Pâtisserie maison',
            'quantity' => 1,
        ]);
    }

    public function test_an_item_cannot_be_added_once_the_order_is_no_longer_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem(Order::STATUS_PREPARING);
        $extraDrink = $this->drink(2.0);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.orders.items.store', $order), [
                'drink_id' => $extraDrink->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing(OrderItem::class, [
            'order_id' => $order->id,
            'drink_id' => $extraDrink->id,
        ]);
    }

    public function test_an_item_can_be_removed_while_the_order_is_pending(): void
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

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->delete(route('employee.orders.items.destroy', [$order, $extraItem]))
            ->assertRedirect();

        $this->assertDatabaseMissing(OrderItem::class, ['id' => $extraItem->id]);
        $this->assertSame('3.50', $order->fresh()->total_amount);
    }

    public function test_an_item_cannot_be_removed_once_the_order_is_no_longer_pending(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem(Order::STATUS_SERVING);
        $item = $order->items()->first();

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->delete(route('employee.orders.items.destroy', [$order, $item]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas(OrderItem::class, ['id' => $item->id]);
    }

    public function test_the_last_remaining_item_cannot_be_removed(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();
        $item = $order->items()->first();

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->delete(route('employee.orders.items.destroy', [$order, $item]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas(OrderItem::class, ['id' => $item->id]);
    }

    public function test_a_refund_line_cannot_be_removed(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem();
        $refundItem = $order->items()->create([
            'drink_id' => null,
            'custom_label' => 'Remboursement',
            'quantity' => 1,
            'unit_price' => -1.5,
            'is_refund' => true,
        ]);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->delete(route('employee.orders.items.destroy', [$order, $refundItem]))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseHas(OrderItem::class, ['id' => $refundItem->id]);
    }

    public function test_adding_an_item_recalculates_the_employee_discount(): void
    {
        $admin = User::factory()->create(['global_role' => 'admin']);
        $order = $this->orderWithOneItem(Order::STATUS_PENDING, isEmployeeOrder: true);
        // Réduction salarié initiale : 15% de 3.5 = 0.525 -> 0.53 (arrondi), total = 2.97
        $order->update(['discount_amount' => 0.53, 'total_amount' => 2.97]);

        $extraDrink = $this->drink(6.5);

        $this->withoutMiddleware(PreventRequestForgery::class)
            ->actingAs($admin)
            ->post(route('employee.orders.items.store', $order), [
                'drink_id' => $extraDrink->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $fresh = $order->fresh();
        // Nouveau sous-total : 3.5 + 6.5 = 10 ; réduction salarié 15% = 1.50 ; total = 8.50
        $this->assertSame('1.50', $fresh->discount_amount);
        $this->assertSame('8.50', $fresh->total_amount);
    }
}
