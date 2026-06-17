<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convertit la colonne ENUM en VARCHAR(50) pour accepter les statuts dynamiques
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Remet l'ENUM d'origine (les valeurs inconnues seront tronquées)
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','preparing','serving','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
