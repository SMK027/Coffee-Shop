<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 150);
            $table->longText('description');
            $table->enum('display_location', ['global_banner', 'employee_banner', 'inbox']);
            $table->enum('target_role', ['superadmin', 'admin', 'moderator'])->nullable();
            $table->string('background_color', 7)->default('#FEF3C7');
            $table->string('text_color', 7)->default('#78350F');
            $table->string('font_family', 80)->default('sans-serif');
            $table->timestamps();

            $table->index(['display_location', 'target_role']);
        });

        Schema::create('internal_note_recipients', function (Blueprint $table) {
            $table->foreignId('internal_note_id')->constrained('internal_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['internal_note_id', 'user_id']);
        });

        Schema::create('internal_note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_note_id')->constrained('internal_notes')->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_note_attachments');
        Schema::dropIfExists('internal_note_recipients');
        Schema::dropIfExists('internal_notes');
    }
};
