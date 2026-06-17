<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label', 100);
            $table->string('color', 20)->default('gray');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('triggers_loyalty_credit')->default(false);
            $table->timestamps();
        });

        // Seed des statuts initiaux (correspondant aux constantes existantes)
        DB::table('order_statuses')->insert([
            [
                'key'                    => 'pending',
                'label'                  => 'En attente',
                'color'                  => 'gray',
                'sort_order'             => 10,
                'is_active'              => true,
                'is_terminal'            => false,
                'triggers_loyalty_credit'=> false,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'key'                    => 'preparing',
                'label'                  => 'Préparation en cours',
                'color'                  => 'amber',
                'sort_order'             => 20,
                'is_active'              => true,
                'is_terminal'            => false,
                'triggers_loyalty_credit'=> false,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'key'                    => 'serving',
                'label'                  => 'Service en cours',
                'color'                  => 'blue',
                'sort_order'             => 30,
                'is_active'              => true,
                'is_terminal'            => false,
                'triggers_loyalty_credit'=> false,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'key'                    => 'completed',
                'label'                  => 'Terminée',
                'color'                  => 'green',
                'sort_order'             => 90,
                'is_active'              => true,
                'is_terminal'            => true,
                'triggers_loyalty_credit'=> true,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'key'                    => 'cancelled',
                'label'                  => 'Annulée',
                'color'                  => 'red',
                'sort_order'             => 91,
                'is_active'              => true,
                'is_terminal'            => true,
                'triggers_loyalty_credit'=> false,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_statuses');
    }
};
