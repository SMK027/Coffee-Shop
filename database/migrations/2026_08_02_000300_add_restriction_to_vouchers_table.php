<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->foreignId('restricted_card_id')
                ->nullable()
                ->after('is_used')
                ->constrained('loyalty_cards')
                ->onDelete('set null');

            $table->string('restricted_name', 150)
                ->nullable()
                ->after('restricted_card_id');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restricted_card_id');
            $table->dropColumn('restricted_name');
        });
    }
};
