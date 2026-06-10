<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Crée le compte super administrateur par défaut.
     * Les valeurs peuvent être surchargées via les variables d'environnement
     * ADMIN_EMAIL, ADMIN_USERNAME, ADMIN_NAME, ADMIN_PASSWORD.
     *
     * ⚠ Changez le mot de passe immédiatement après la première connexion.
     */
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL',    'admin@app.local');
        $username = env('ADMIN_USERNAME', 'admin');
        $name     = env('ADMIN_NAME',     'Administrateur');
        $password = env('ADMIN_PASSWORD', 'password');

        User::firstOrCreate(
            ['email' => $email],
            [
                'username'    => $username,
                'name'        => $name,
                'password'    => Hash::make($password),
                'global_role' => 'superadmin',
            ]
        );
    }
}
