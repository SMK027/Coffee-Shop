<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Une commande peut être raccordée à une seule et unique carte
            $table->foreignId('loyalty_card_id')
                  ->nullable()
                  ->after('customer_name')
                  ->constrained('loyalty_cards')
                  ->nullOnDelete();

            // Indique si les points de fidélité ont déjà été crédités (anti double-crédit)
            $table->boolean('points_credited')->default(false)->after('completed_at');
            $table->unsignedInteger('points_awarded')->default(0)->after('points_credited');

            // Le nom du client devient optionnel : inutile si une carte est passée
            $table->string('customer_name', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['loyalty_card_id']);
            $table->dropColumn(['loyalty_card_id', 'points_credited', 'points_awarded']);
            $table->string('customer_name', 100)->nullable(false)->change();
        });
    }
};
