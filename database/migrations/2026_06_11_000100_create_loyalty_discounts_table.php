<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedInteger('points_cost');
            $table->enum('discount_type', ['fixed', 'percent']);
            $table->decimal('discount_value', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sold_out')->default(false);
            $table->boolean('employee_only')->default(false);
            $table->boolean('is_permanent')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('quantity_limit')->nullable();
            $table->unsignedInteger('quantity_used')->default(0);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('loyalty_discount_id')->nullable()->after('loyalty_card_id')
                ->constrained('loyalty_discounts')->nullOnDelete();
            $table->unsignedInteger('loyalty_points_spent')->default(0)->after('discount_amount');
            $table->decimal('loyalty_discount_amount', 8, 2)->default(0)->after('loyalty_points_spent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loyalty_discount_id');
            $table->dropColumn(['loyalty_points_spent', 'loyalty_discount_amount']);
        });

        Schema::dropIfExists('loyalty_discounts');
    }
};
