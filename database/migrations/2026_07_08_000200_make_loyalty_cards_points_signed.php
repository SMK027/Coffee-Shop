<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Passe la colonne points de UNSIGNED INT à INT signé
        // pour autoriser un solde négatif lors de remboursements.
        DB::statement('ALTER TABLE loyalty_cards MODIFY COLUMN points INT NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        // Remet UNSIGNED (les valeurs négatives éventuelles seront forcées à 0)
        DB::statement('ALTER TABLE loyalty_cards MODIFY COLUMN points INT UNSIGNED NOT NULL DEFAULT 0');
    }
};
