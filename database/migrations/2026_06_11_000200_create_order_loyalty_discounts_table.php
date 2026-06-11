<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table pivot : plusieurs réductions fidélité par commande
        Schema::create('order_loyalty_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('loyalty_discount_id')
                ->constrained('loyalty_discounts')
                ->restrictOnDelete();
            $table->unsignedInteger('points_spent')->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0.00);
            $table->timestamps();
            $table->unique(['order_id', 'loyalty_discount_id']);
        });

        // Migration des données existantes (ancienne colonne 1-to-1) vers la table pivot
        DB::table('orders')
            ->whereNotNull('loyalty_discount_id')
            ->select(['id', 'loyalty_discount_id', 'loyalty_points_spent', 'loyalty_discount_amount', 'created_at', 'updated_at'])
            ->chunkById(100, function ($orders) {
                $rows = $orders->map(fn ($o) => [
                    'order_id'            => $o->id,
                    'loyalty_discount_id' => $o->loyalty_discount_id,
                    'points_spent'        => $o->loyalty_points_spent,
                    'discount_amount'     => $o->loyalty_discount_amount,
                    'created_at'          => $o->created_at,
                    'updated_at'          => $o->updated_at,
                ])->all();
                if (!empty($rows)) {
                    DB::table('order_loyalty_discounts')->insert($rows);
                }
            });

        // Suppression de l'ancienne colonne 1-to-1 sur orders
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loyalty_discount_id');
        });
    }

    public function down(): void
    {
        // Ré-ajouter la colonne FK (best-effort : on restaure la première réduction)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('loyalty_discount_id')
                ->nullable()
                ->after('loyalty_card_id')
                ->constrained('loyalty_discounts')
                ->nullOnDelete();
        });

        DB::statement('
            UPDATE orders o
            INNER JOIN order_loyalty_discounts ld
                ON ld.order_id = o.id
                AND ld.id = (SELECT MIN(id) FROM order_loyalty_discounts WHERE order_id = o.id)
            SET o.loyalty_discount_id = ld.loyalty_discount_id
        ');

        Schema::dropIfExists('order_loyalty_discounts');
    }
};
