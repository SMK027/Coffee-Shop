<?php

use App\Models\PaymentMethod;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Crée le moyen de paiement "Bon d'achat" (activé par défaut) sur toutes les instances,
        // s'il n'existe pas déjà.
        PaymentMethod::firstOrCreate(
            ['slug' => PaymentMethod::SLUG_VOUCHER],
            [
                'name'       => "Bon d'achat",
                'is_active'  => true,
                'sort_order' => 5,
            ]
        );
    }

    public function down(): void
    {
        PaymentMethod::where('slug', PaymentMethod::SLUG_VOUCHER)->delete();
    }
};
