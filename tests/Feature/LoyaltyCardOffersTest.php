<?php

namespace Tests\Feature;

use App\Models\CardOffer;
use App\Models\LoyaltyCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyCardOffersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_valid_personalized_offers_for_the_selected_card(): void
    {
        $user = User::factory()->create();

        $card = LoyaltyCard::create([
            'card_number' => '123456789012',
            'first_name'  => 'Ada',
            'last_name'   => 'Lovelace',
            'email'       => 'ada@example.com',
            'phone'       => '0600000000',
            'birth_date'  => now()->subYears(20)->toDateString(),
            'pin'         => '1234',
            'points'      => 0,
        ]);

        $validOffer = $card->cardOffers()->create([
            'label' => 'Offre bienvenue',
            'discount_type' => CardOffer::TYPE_PERCENT,
            'discount_value' => 10,
            'max_discount_amount' => 5,
            'expires_at' => now()->addDay(),
            'issued_by' => $user->id,
        ]);

        $card->cardOffers()->create([
            'label' => 'Offre expirée',
            'discount_type' => CardOffer::TYPE_FIXED,
            'discount_value' => 3,
            'expires_at' => now()->subDay(),
            'issued_by' => $user->id,
        ]);

        $card->cardOffers()->create([
            'label' => 'Offre utilisée',
            'discount_type' => CardOffer::TYPE_FIXED,
            'discount_value' => 2,
            'expires_at' => now()->addDay(),
            'is_used' => true,
            'used_at' => now(),
            'issued_by' => $user->id,
        ]);

        $otherCard = LoyaltyCard::create([
            'card_number' => '210987654321',
            'first_name'  => 'Grace',
            'last_name'   => 'Hopper',
            'email'       => 'grace@example.com',
            'phone'       => '0600000001',
            'birth_date'  => now()->subYears(25)->toDateString(),
            'pin'         => '4321',
            'points'      => 0,
        ]);
        $otherCard->cardOffers()->create([
            'label' => 'Offre d\'autre carte',
            'discount_type' => CardOffer::TYPE_PERCENT,
            'discount_value' => 15,
            'expires_at' => now()->addDay(),
            'issued_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/loyalty-cards/' . $card->id . '/offers');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $validOffer->id);
        $response->assertJsonPath('0.label', 'Offre bienvenue');
        $response->assertJsonMissing(['id' => $card->cardOffers()->where('label', 'Offre expirée')->value('id')]);
        $response->assertJsonMissing(['id' => $card->cardOffers()->where('label', 'Offre utilisée')->value('id')]);
    }
}
