<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_discounts', function (Blueprint $table) {
            // Plafond optionnel : la réduction appliquée ne dépassera jamais ce montant.
            // Utile pour les réductions en pourcentage (ex. 20% dans la limite de 20 €).
            // NULL = pas de plafond.
            $table->decimal('max_discount_amount', 8, 2)->nullable()->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_discounts', function (Blueprint $table) {
            $table->dropColumn('max_discount_amount');
        });
    }
};
