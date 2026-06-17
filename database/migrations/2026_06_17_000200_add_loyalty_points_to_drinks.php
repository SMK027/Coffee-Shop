<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drinks', function (Blueprint $table) {
            // Nombre de points de fidélité attribués par unité vendue de cette boisson.
            // 0 = aucun point (valeur par défaut pour ne pas perturber l'existant).
            $table->unsignedSmallInteger('loyalty_points')->default(0)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('drinks', function (Blueprint $table) {
            $table->dropColumn('loyalty_points');
        });
    }
};
