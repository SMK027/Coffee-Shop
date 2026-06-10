<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'author_name' => 'Sophie M.',
                'rating'      => 5,
                'content'     => 'Un endroit absolument magnifique ! Le café est exceptionnel et le personnel est aux petits soins. Je reviens chaque semaine sans faute.',
                'status'      => 'published',
            ],
            [
                'author_name' => 'Thomas L.',
                'rating'      => 5,
                'content'     => 'Le meilleur cappuccino de la ville, sans aucun doute. L\'ambiance est chaleureuse et relaxante, parfaite pour travailler ou se retrouver entre amis.',
                'status'      => 'published',
            ],
            [
                'author_name' => 'Marie-Claire D.',
                'rating'      => 4,
                'content'     => 'Une belle découverte ! Le Cold Brew est délicieux et le service est rapide même aux heures de pointe. Je recommande vivement !',
                'status'      => 'published',
            ],
            [
                'author_name' => 'Antoine R.',
                'rating'      => 5,
                'content'     => 'L\'Affogato est un vrai régal. Le personnel connaît parfaitement ses produits et sait conseiller. Une adresse à ne pas manquer.',
                'status'      => 'published',
            ],
            [
                'author_name' => 'Isabelle P.',
                'rating'      => 5,
                'content'     => 'Cadre décoré avec goût, café de qualité et musique douce en fond. On s\'y sent comme chez soi. Mon café préféré en ville !',
                'status'      => 'published',
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::firstOrCreate(
                ['author_name' => $data['author_name'], 'content' => $data['content']],
                $data
            );
        }
    }
}
