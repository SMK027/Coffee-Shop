<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drink_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('drinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('drink_categories')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->string('image')->nullable();
            $table->boolean('available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drinks');
        Schema::dropIfExists('drink_categories');
    }
};
