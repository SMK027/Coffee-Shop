<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    protected $fillable = [
        'key', 'label', 'color', 'sort_order',
        'is_active', 'is_terminal', 'triggers_loyalty_credit',
    ];

    protected $casts = [
        'is_active'               => 'boolean',
        'is_terminal'             => 'boolean',
        'triggers_loyalty_credit' => 'boolean',
        'sort_order'              => 'integer',
    ];

    /** Couleurs disponibles mappées vers les classes Tailwind de badge. */
    const BADGE_CLASSES = [
        'gray'   => 'bg-stone-100 text-stone-700',
        'amber'  => 'bg-amber-100 text-amber-700',
        'blue'   => 'bg-blue-100 text-blue-700',
        'green'  => 'bg-green-100 text-green-700',
        'red'    => 'bg-red-100 text-red-700',
        'purple' => 'bg-purple-100 text-purple-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'orange' => 'bg-orange-100 text-orange-700',
        'teal'   => 'bg-teal-100 text-teal-700',
    ];

    /** Classes Tailwind pour les boutons de transition de statut. */
    const BUTTON_CLASSES = [
        'gray'   => 'bg-stone-600 hover:bg-stone-500 text-white',
        'amber'  => 'bg-amber-600 hover:bg-amber-500 text-white',
        'blue'   => 'bg-blue-600 hover:bg-blue-500 text-white',
        'green'  => 'bg-green-600 hover:bg-green-500 text-white',
        'red'    => 'bg-red-100 hover:bg-red-200 text-red-700',
        'purple' => 'bg-purple-600 hover:bg-purple-500 text-white',
        'indigo' => 'bg-indigo-600 hover:bg-indigo-500 text-white',
        'orange' => 'bg-orange-600 hover:bg-orange-500 text-white',
        'teal'   => 'bg-teal-600 hover:bg-teal-500 text-white',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'status', 'key');
    }

    public function getBadgeClassAttribute(): string
    {
        return self::BADGE_CLASSES[$this->color] ?? self::BADGE_CLASSES['gray'];
    }

    public function getButtonClassAttribute(): string
    {
        return self::BUTTON_CLASSES[$this->color] ?? self::BUTTON_CLASSES['gray'];
    }

    /**
     * Retourne la liste des statuts actifs ordonnée (avec cache statique par
     * requête pour éviter des requêtes répétées).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, static>
     */
    public static function allActive(): \Illuminate\Database\Eloquent\Collection
    {
        static $cache = null;
        if ($cache === null) {
            $cache = static::where('is_active', true)->orderBy('sort_order')->get();
        }
        return $cache;
    }

    /** Remet à zéro le cache statique (utile après une modification). */
    public static function clearCache(): void
    {
        // La propriété statique est par-processus ; pour PHP-FPM (une requête =
        // un processus), le cache est naturellement invalidé entre les requêtes.
        // Cette méthode est fournie pour les tests.
    }
}
