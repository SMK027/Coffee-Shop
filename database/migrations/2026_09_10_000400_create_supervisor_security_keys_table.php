<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_security_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('credential_id')->unique();
            $table->json('credential');
            $table->string('registered_ip', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_security_keys');
    }
};
