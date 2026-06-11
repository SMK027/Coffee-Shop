<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_point_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Super administrateur ayant réalisé l\'ajustement');
            $table->string('type', 10)->comment('credit ou debit');
            $table->integer('points')->comment('Nombre de points ajustés (toujours positif)');
            $table->integer('balance_after')->comment('Solde de la carte après ajustement');
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_point_adjustments');
    }
};
