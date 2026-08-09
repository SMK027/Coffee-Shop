<?php

namespace Tests\Feature;

use App\Models\CardOffer;
use App\Models\LoyaltyCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeOrderCardOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_order_identification_shows_personalized_card_offers(): void
    {
        $employee = User::factory()->create([
            'global_role' => 'admin',
            'is_active' => true,
        ]);

        $card = LoyaltyCard::create([
            'card_number' => '123456789012',
            'first_name'  => 'Ada',
            'last_name'   => 'Lovelace',
            'email'       => 'ada@example.com',
            'phone'       => '0600000000',
            'birth_date'  => now()->subYears(20)->toDateString(),
            'pin'         => '1234',
            'points'      => 20,
        ]);

        $offer = $card->cardOffers()->create([
            'label' => 'Offre carte test',
            'discount_type' => CardOffer::TYPE_PERCENT,
            'discount_value' => 10,
            'max_discount_amount' => 5,
            'expires_at' => now()->addDay(),
            'issued_by' => $employee->id,
        ]);

        $this->actingAs($employee);

        $request = new \Illuminate\Http\Request([
            'card_number' => $card->card_number,
            'pin' => '1234',
        ]);
        $request->setMethod('POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->setUserResolver(fn () => $employee);

        $controller = new \App\Http\Controllers\Employee\OrderController();
        $response = $controller->verifyCardPin($request);

        $this->assertTrue($response->getData(true)['valid']);
        $this->assertCount(1, $response->getData(true)['offers']);
        $this->assertSame($offer->id, $response->getData(true)['offers'][0]['id']);
        $this->assertSame('Offre carte test', $response->getData(true)['offers'][0]['label']);
    }
}
