<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->decimal('total_vouchers_issued', 8, 2)->default(0)->after('total_refunded');
            $table->json('vouchers_issued')->nullable()->after('refund_breakdown');
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropColumn(['total_vouchers_issued', 'vouchers_issued']);
        });
    }
};
