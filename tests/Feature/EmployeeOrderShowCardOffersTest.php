<?php

namespace Tests\Feature;

use App\Models\CardOffer;
use App\Models\LoyaltyCard;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeOrderShowCardOffersTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_order_details_show_used_personalized_card_offers(): void
    {
        $employee = User::factory()->create([
            'global_role' => 'admin',
            'is_active' => true,
        ]);

        $card = LoyaltyCard::create([
            'card_number' => '987654321098',
            'first_name'  => 'Ada',
            'last_name'   => 'Lovelace',
            'email'       => 'ada2@example.com',
            'phone'       => '0600000001',
            'birth_date'  => now()->subYears(20)->toDateString(),
            'pin'         => '1234',
            'points'      => 20,
        ]);

        $order = Order::create([
            'customer_name'           => 'Ada',
            'loyalty_card_id'         => $card->id,
            'is_employee_order'       => false,
            'status'                  => Order::STATUS_PENDING,
            'notes'                   => null,
            'total_amount'            => 10.00,
            'discount_amount'         => 0.00,
            'loyalty_points_spent'    => 0,
            'loyalty_discount_amount' => 0.00,
            'voucher_discount_amount' => 0.00,
            'handled_by'              => $employee->id,
        ]);

        $offer = $card->cardOffers()->create([
            'label' => 'Offre de test commande',
            'discount_type' => CardOffer::TYPE_PERCENT,
            'discount_value' => 10,
            'max_discount_amount' => 5,
            'expires_at' => now()->addDay(),
            'is_used' => true,
            'used_at' => now(),
            'used_in_order_id' => $order->id,
            'issued_by' => $employee->id,
        ]);

        $this->actingAs($employee);

        $response = $this->get(route('employee.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Offres personnalisées utilisées');
        $response->assertSee($offer->label);
    }
}
