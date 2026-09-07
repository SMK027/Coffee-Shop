<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('quick_login_disabled')->default(false)->after('is_active');
        });

        Schema::table('supervisors', function (Blueprint $table) {
            $table->timestamp('quarantined_until')->nullable()->after('is_active');
            $table->boolean('is_temporary')->default(false)->after('quarantined_until');
            $table->foreignId('replaces_supervisor_id')->nullable()->constrained('supervisors')->nullOnDelete()->after('is_temporary');
            $table->timestamp('temporary_expires_at')->nullable()->after('replaces_supervisor_id');
            $table->index(['is_active', 'quarantined_until']);
            $table->index(['is_temporary', 'temporary_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('supervisors', function (Blueprint $table) {
            $table->dropForeign(['replaces_supervisor_id']);
            $table->dropIndex(['is_active', 'quarantined_until']);
            $table->dropIndex(['is_temporary', 'temporary_expires_at']);
            $table->dropColumn(['quarantined_until', 'is_temporary', 'replaces_supervisor_id', 'temporary_expires_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('quick_login_disabled');
        });
    }
};
