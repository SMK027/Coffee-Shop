<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_password_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique()->comment('SHA-256 du token brut envoyé par email');
            $table->timestamp('created_at');
            $table->timestamp('used_at')->nullable()->comment('Non null = déjà utilisé');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_password_resets');
    }
};
