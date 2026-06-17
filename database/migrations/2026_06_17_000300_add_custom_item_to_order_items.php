<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Rend drink_id nullable pour permettre les articles libres
            $table->foreignId('drink_id')->nullable()->change();

            // Libellé et prix saisis manuellement pour les articles hors catalogue
            $table->string('custom_label', 150)->nullable()->after('drink_id');
            $table->decimal('custom_price', 8, 2)->nullable()->after('custom_label');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['custom_label', 'custom_price']);
            $table->foreignId('drink_id')->nullable(false)->change();
        });
    }
};
