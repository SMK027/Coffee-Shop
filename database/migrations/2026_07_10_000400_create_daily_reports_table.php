<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('generated_by')->constrained('users');
            $table->decimal('total_collected', 10, 2)->default(0);
            $table->decimal('total_refunded', 10, 2)->default(0);
            $table->json('breakdown')->comment('Détail par moyen de paiement (encaissements)');
            $table->json('refund_breakdown')->comment('Détail des remboursements par moyen de paiement');
            $table->timestamps();

            $table->unique(['report_date', 'generated_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
