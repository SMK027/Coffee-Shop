<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Espèces',          'slug' => 'cash',         'sort_order' => 1],
            ['name' => 'Carte bancaire',   'slug' => 'card',         'sort_order' => 2],
            ['name' => 'Ticket restaurant','slug' => 'ticket-resto', 'sort_order' => 3],
            ['name' => 'Virement',         'slug' => 'transfer',     'sort_order' => 4],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['slug' => $method['slug']],
                array_merge($method, ['is_active' => true])
            );
        }
    }
}
