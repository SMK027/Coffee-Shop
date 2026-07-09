<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('global_role', ['superadmin', 'admin', 'moderator', 'user', 'supervisor'])
                ->default('user')
                ->change();
            $table->boolean('is_active')->default(true)->after('global_role');
            $table->foreignId('superadmin_id')->nullable()->constrained('users')->nullOnDelete()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['superadmin_id']);
            $table->dropColumn('superadmin_id');
            $table->dropColumn('is_active');
            $table->enum('global_role', ['superadmin', 'admin', 'moderator', 'user'])
                ->default('user')
                ->change();
        });
    }
};
