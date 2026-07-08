<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Marque un article comme ligne de remboursement (prix négatif)
            $table->boolean('is_refund')->default(false)->after('custom_price');
            // Référence à l'item original remboursé (null pour remboursement total)
            $table->unsignedBigInteger('refund_item_id')->nullable()->after('is_refund');
            $table->foreign('refund_item_id')->references('id')->on('order_items')->onDelete('set null');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Total remboursé (somme des montants négatifs, stocké en positif)
            $table->decimal('refunded_amount', 8, 2)->default(0)->after('loyalty_discount_amount');
            // Points débités suite au remboursement
            $table->integer('points_refunded')->default(0)->after('points_awarded');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['refund_item_id']);
            $table->dropColumn(['is_refund', 'refund_item_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['refunded_amount', 'points_refunded']);
        });
    }
};
