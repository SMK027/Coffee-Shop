<?php

namespace Database\Seeders;

use App\Models\CardOffer;
use App\Models\LoyaltyCard;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Crée le compte super administrateur par défaut.
     * Les valeurs peuvent être surchargées via les variables d'environnement
     * ADMIN_EMAIL, ADMIN_USERNAME, ADMIN_NAME, ADMIN_PASSWORD.
     *
     * ⚠ Changez le mot de passe immédiatement après la première connexion.
     */
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL', 'admin@app.local');
        $username = env('ADMIN_USERNAME', 'admin');
        $name     = env('ADMIN_NAME', 'Administrateur');
        $password = env('ADMIN_PASSWORD', 'password');

        $superadmin = User::updateOrCreate(
            ['email' => $email],
            [
                'username'    => $username,
                'name'        => $name,
                'password'    => Hash::make($password),
                'global_role' => 'superadmin',
                'is_active'   => true,
            ]
        );

        $simpleAdmin = User::updateOrCreate(
            ['email' => 'admin-simple@app.local'],
            [
                'username'    => 'admin-simple',
                'name'        => 'Administrateur simple',
                'password'    => Hash::make('password'),
                'global_role' => 'admin',
                'is_active'   => true,
                'superadmin_id' => $superadmin->id,
            ]
        );

        $supervisor = Supervisor::updateOrCreate(
            ['supervisor_number' => '0001'],
            [
                'password'      => Hash::make('1234'),
                'is_active'     => true,
                'superadmin_id' => $superadmin->id,
            ]
        );

        $card = LoyaltyCard::updateOrCreate(
            ['card_number' => '0000'],
            [
                'first_name' => 'Client',
                'last_name'  => 'Test',
                'email'      => 'client-test@app.local',
                'phone'      => '0600000000',
                'birth_date' => now()->subYears(25)->toDateString(),
                'pin'        => '0000',
                'points'     => 100,
                'user_id'    => $simpleAdmin->id,
            ]
        );

        $card->cardOffers()->updateOrCreate(
            ['label' => 'Offre fixe test'],
            [
                'discount_type'      => CardOffer::TYPE_FIXED,
                'discount_value'     => 2.50,
                'max_discount_amount' => null,
                'expires_at'         => now()->addMonths(3),
                'is_used'            => false,
                'issued_by'          => $superadmin->id,
            ]
        );

        $card->cardOffers()->updateOrCreate(
            ['label' => 'Offre pourcentage test'],
            [
                'discount_type'      => CardOffer::TYPE_PERCENT,
                'discount_value'     => 15,
                'max_discount_amount' => 4.00,
                'expires_at'         => now()->addMonths(3),
                'is_used'            => false,
                'issued_by'          => $superadmin->id,
            ]
        );
    }
}
