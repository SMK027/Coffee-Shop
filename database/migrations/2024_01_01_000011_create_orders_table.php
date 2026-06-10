<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 100);
            $table->enum('status', ['pending', 'preparing', 'serving', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 8, 2)->default(0);
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('drink_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
