<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_notes', function (Blueprint $table) {
            $table->boolean('is_for_all')->default(false)->after('target_role');
            $table->timestamp('expires_at')->nullable()->after('font_family');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('internal_notes', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['is_for_all', 'expires_at']);
        });
    }
};
