<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Ratio de points crédités par euro dépensé. */
    public const KEY_POINTS_PER_EURO = 'loyalty_points_per_euro';

    /** Images de la page d'accueil. */
    public const KEY_HOME_IMAGE_HERO     = 'home_image_hero';
    public const KEY_HOME_IMAGE_AMBIANCE = 'home_image_ambiance';
    public const KEY_HOME_IMAGE_BARISTA  = 'home_image_barista';
    public const KEY_HOME_IMAGE_SALLE    = 'home_image_salle';

    public const HOME_IMAGE_KEYS = [
        self::KEY_HOME_IMAGE_HERO     => 'Image hero (bandeau principal)',
        self::KEY_HOME_IMAGE_AMBIANCE => 'Photo ambiance',
        self::KEY_HOME_IMAGE_BARISTA  => 'Photo barista',
        self::KEY_HOME_IMAGE_SALLE    => 'Photo salle',
    ];

    /** Informations boutique. */
    public const KEY_SHOP_ADDRESS = 'shop_address';
    public const KEY_SHOP_PHONE   = 'shop_phone';
    public const KEY_SHOP_EMAIL   = 'shop_email';
    public const KEY_SHOP_HOURS   = 'shop_hours';

    public const DEFAULTS = [
        self::KEY_POINTS_PER_EURO => '5',
        self::KEY_SHOP_ADDRESS    => "12 Rue des Arômes\n75001 Paris",
        self::KEY_SHOP_PHONE      => '01 23 45 67 89',
        self::KEY_SHOP_EMAIL      => 'contact@lecoffeeshop.fr',
        self::KEY_SHOP_HOURS      => "Lun – Ven : 7h00 – 19h00\nSamedi : 8h00 – 20h00\nDimanche : 9h00 – 18h00",
    ];

    /**
     * Récupère une valeur de paramètre (avec valeur par défaut), mise en cache.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting?->value ?? $default ?? self::DEFAULTS[$key] ?? null;
        });
    }

    /**
     * Définit une valeur de paramètre et invalide le cache.
     */
    public static function set(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    /**
     * Ratio de points par euro (nombre de points crédités pour chaque euro dépensé).
     */
    public static function pointsPerEuro(): int
    {
        return (int) self::get(self::KEY_POINTS_PER_EURO, '5');
    }
}
