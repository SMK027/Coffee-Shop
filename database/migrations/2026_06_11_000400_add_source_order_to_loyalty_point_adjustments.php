<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_point_adjustments', function (Blueprint $table) {
            // Source de l'ajustement : 'manual' (admin), 'order_debit' (échange réduction), 'order_credit' (points gagnés)
            $table->string('source', 20)->default('manual')->after('type');
            // Lien optionnel vers la commande à l'origine du mouvement
            $table->foreignId('order_id')->nullable()->after('loyalty_card_id')
                ->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_point_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
            $table->dropColumn('source');
        });
    }
};
