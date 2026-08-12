<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisors', function (Blueprint $table) {
            $table->foreignId('holder_admin_id')
                ->nullable()
                ->after('superadmin_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropForeign(['holder_admin_id']);
            $table->dropColumn('holder_admin_id');
        });
    }
};
