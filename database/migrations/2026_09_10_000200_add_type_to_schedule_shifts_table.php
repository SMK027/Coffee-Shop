<?php

use App\Models\ScheduleShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_shifts', function (Blueprint $table) {
            $table->string('type', 20)->default(ScheduleShift::TYPE_WORK)->after('user_id');
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('schedule_shifts', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
        });
    }
};
