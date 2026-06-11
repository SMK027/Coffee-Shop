<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->id();
            $table->string('card_number', 20)->unique();
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 30);
            $table->date('birth_date');
            $table->string('pin');               // code PIN chiffré (hash bcrypt)
            $table->unsignedInteger('points')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_cards');
    }
};
