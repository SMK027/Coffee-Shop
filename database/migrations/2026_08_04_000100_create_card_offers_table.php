<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_card_id')->constrained()->cascadeOnDelete();
            $table->string('label', 150);
            $table->enum('discount_type', ['fixed', 'percent']);
            $table->decimal('discount_value', 8, 2);
            $table->decimal('max_discount_amount', 8, 2)->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_in_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('issued_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_offers');
    }
};
