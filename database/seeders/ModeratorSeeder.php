<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ModeratorSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('MODERATOR_EMAIL', 'moderator@example.com');
        $exists = User::where('email', $email)->first();
        if ($exists) {
            return;
        }

        User::create([
            'username' => 'moderator',
            'name'     => 'Moderator',
            'email'    => $email,
            'password' => bcrypt(env('MODERATOR_PASSWORD', 'moderatorpass')),
            'global_role' => 'moderator',
            'is_active' => true,
        ]);
    }
}
