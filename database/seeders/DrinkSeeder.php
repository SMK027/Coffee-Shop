<?php

namespace Database\Seeders;

use App\Models\Drink;
use App\Models\DrinkCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DrinkSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Cafés',
                'slug' => 'cafes',
                'sort_order' => 1,
                'drinks' => [
                    ['name' => 'Espresso', 'description' => 'Un expresso serré, intense et aromatique, extrait en 25 secondes pour révéler toute la richesse du café.', 'price' => 2.20],
                    ['name' => 'Double Espresso', 'description' => 'Deux shots d\'espresso pour une intensité maximale et une caféine bien dosée.', 'price' => 3.00],
                    ['name' => 'Americano', 'description' => 'Espresso allongé à l\'eau chaude, pour un café long et équilibré.', 'price' => 2.80],
                    ['name' => 'Cappuccino', 'description' => 'Espresso, lait chaud et mousse crémeuse en proportions égales. Un classique italien incontournable.', 'price' => 3.50],
                    ['name' => 'Latte', 'description' => 'Espresso doux noyé dans du lait vapeur velouté, surmonté d\'une légère mousse.', 'price' => 3.80],
                    ['name' => 'Flat White', 'description' => 'Double espresso et lait vapeur micro-texturé pour une boisson plus concentrée que le latte.', 'price' => 4.00],
                    ['name' => 'Macchiato', 'description' => 'Espresso "taché" d\'une touche de lait vapeur et de mousse onctueuse.', 'price' => 2.80],
                ],
            ],
            [
                'name' => 'Boissons Froides',
                'slug' => 'boissons-froides',
                'sort_order' => 2,
                'drinks' => [
                    ['name' => 'Cold Brew', 'description' => 'Café infusé à froid pendant 12h pour une douceur naturelle sans amertume.', 'price' => 4.50],
                    ['name' => 'Iced Latte', 'description' => 'Espresso versé sur des glaçons avec du lait froid. Rafraîchissant et savoureux.', 'price' => 4.20],
                    ['name' => 'Iced Cappuccino', 'description' => 'Notre cappuccino revisité en version glacée pour les journées ensoleillées.', 'price' => 4.20],
                    ['name' => 'Frappuccino Caramel', 'description' => 'Café mixé avec de la glace, du caramel et une touche de crème fouettée.', 'price' => 5.50],
                    ['name' => 'Limonade Maison', 'description' => 'Limonade artisanale au citron frais, légèrement sucrée et pétillante.', 'price' => 3.80],
                ],
            ],
            [
                'name' => 'Thés & Infusions',
                'slug' => 'thes-infusions',
                'sort_order' => 3,
                'drinks' => [
                    ['name' => 'Thé Earl Grey', 'description' => 'Thé noir parfumé à la bergamote, servi avec une tranche de citron.', 'price' => 3.00],
                    ['name' => 'Thé Vert Matcha', 'description' => 'Matcha japonais de qualité cérémonielle, préparé selon la tradition.', 'price' => 4.00],
                    ['name' => 'Infusion Fruits Rouges', 'description' => 'Mélange d\'hibiscus, framboise et cassis pour une infusion fruitée et acidulée.', 'price' => 3.00],
                    ['name' => 'Chai Latte', 'description' => 'Thé épicé à la cannelle, cardamome et gingembre, adouci au lait vapeur.', 'price' => 4.20],
                ],
            ],
            [
                'name' => 'Spécialités',
                'slug' => 'specialites',
                'sort_order' => 4,
                'drinks' => [
                    ['name' => 'Café Viennois', 'description' => 'Espresso couronné d\'une généreuse touche de crème fouettée. Un plaisir viennois.', 'price' => 4.50],
                    ['name' => 'Affogato', 'description' => 'Boule de glace vanille nappée d\'un espresso chaud. Un dessert qui se boit.', 'price' => 5.00],
                    ['name' => 'Latte Noisette', 'description' => 'Latte parfumé au sirop de noisette torréfiée, gourmand et réconfortant.', 'price' => 4.50],
                    ['name' => 'Chocolat Chaud Artisanal', 'description' => 'Chocolat noir 70% fondu dans du lait entier, onctueux et intense.', 'price' => 4.00],
                ],
            ],
        ];

        foreach ($categories as $catData) {
            $drinks = $catData['drinks'];
            unset($catData['drinks']);
            $category = DrinkCategory::firstOrCreate(['slug' => $catData['slug']], $catData);

            foreach ($drinks as $i => $drinkData) {
                Drink::firstOrCreate(
                    ['slug' => Str::slug($drinkData['name'])],
                    array_merge($drinkData, [
                        'slug'       => Str::slug($drinkData['name']),
                        'category_id' => $category->id,
                        'available'  => true,
                        'sort_order' => $i + 1,
                    ])
                );
            }
        }
    }
}
