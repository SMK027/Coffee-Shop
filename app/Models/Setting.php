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
        self::KEY_SHOP_HOURS      => '{"regular":{"monday":{"open":true,"from":"07:00","to":"19:00"},"tuesday":{"open":true,"from":"07:00","to":"19:00"},"wednesday":{"open":true,"from":"07:00","to":"19:00"},"thursday":{"open":true,"from":"07:00","to":"19:00"},"friday":{"open":true,"from":"07:00","to":"19:00"},"saturday":{"open":true,"from":"08:00","to":"20:00"},"sunday":{"open":true,"from":"09:00","to":"18:00"}},"exceptions":[]}',
    ];

    /**
     * Retourne la structure par défaut des horaires.
     */
    public static function defaultHours(): array
    {
        return [
            'regular' => [
                'monday'    => ['open' => true,  'from' => '07:00', 'to' => '19:00'],
                'tuesday'   => ['open' => true,  'from' => '07:00', 'to' => '19:00'],
                'wednesday' => ['open' => true,  'from' => '07:00', 'to' => '19:00'],
                'thursday'  => ['open' => true,  'from' => '07:00', 'to' => '19:00'],
                'friday'    => ['open' => true,  'from' => '07:00', 'to' => '19:00'],
                'saturday'  => ['open' => true,  'from' => '08:00', 'to' => '20:00'],
                'sunday'    => ['open' => true,  'from' => '09:00', 'to' => '18:00'],
            ],
            'exceptions' => [],
        ];
    }

    /**
     * Retourne les horaires décodés depuis la base (migration automatique depuis l'ancien format texte).
     */
    public static function getHours(): array
    {
        $raw  = self::get(self::KEY_SHOP_HOURS);
        $data = $raw ? json_decode($raw, true) : null;

        if (!$data || !isset($data['regular'])) {
            // Ancien format texte ou valeur manquante → migration vers le nouveau format
            $default = self::defaultHours();
            self::set(self::KEY_SHOP_HOURS, json_encode($default));
            return $default;
        }

        // Garantit que la clé exceptions existe toujours
        $data['exceptions'] = $data['exceptions'] ?? [];
        return $data;
    }

    /**
     * Formate les horaires réguliers en lignes lisibles, en regroupant les jours consécutifs
     * ayant les mêmes horaires. Ex : ["Lun – Ven : 7h00 – 19h00", "Sam : 8h00 – 20h00", …]
     */
    public static function formatHoursLines(array $hours): array
    {
        $labels = [
            'monday'    => 'Lun',
            'tuesday'   => 'Mar',
            'wednesday' => 'Mer',
            'thursday'  => 'Jeu',
            'friday'    => 'Ven',
            'saturday'  => 'Sam',
            'sunday'    => 'Dim',
        ];
        $keys  = array_keys($labels);
        $n     = count($keys);
        $lines = [];
        $i     = 0;

        while ($i < $n) {
            $key     = $keys[$i];
            $current = $hours['regular'][$key] ?? ['open' => false];
            $j       = $i;

            while ($j + 1 < $n) {
                $next     = $hours['regular'][$keys[$j + 1]] ?? ['open' => false];
                $sameOpen = $current['open'] === $next['open'];
                $sameTime = !$current['open'] || (
                    ($current['from'] ?? '') === ($next['from'] ?? '') &&
                    ($current['to']   ?? '') === ($next['to']   ?? '')
                );
                if ($sameOpen && $sameTime) {
                    $j++;
                } else {
                    break;
                }
            }

            $range = $i === $j
                ? $labels[$keys[$i]]
                : $labels[$keys[$i]] . ' – ' . $labels[$keys[$j]];

            $lines[] = $current['open']
                ? $range . ' : ' . self::formatTime($current['from'] ?? '') . ' – ' . self::formatTime($current['to'] ?? '')
                : $range . ' : Fermé';

            $i = $j + 1;
        }

        return $lines;
    }

    /**
     * Retourne les exceptions à venir (aujourd'hui inclus) dans la limite donnée en jours.
     */
    public static function upcomingExceptions(array $hours, int $days = 60): array
    {
        $today = now()->startOfDay();
        $limit = now()->addDays($days)->endOfDay();

        $exc = array_filter($hours['exceptions'] ?? [], function ($e) use ($today, $limit) {
            try {
                $d = \Carbon\Carbon::parse($e['date']);
                return $d->gte($today) && $d->lte($limit);
            } catch (\Exception) {
                return false;
            }
        });

        usort($exc, fn($a, $b) => strcmp($a['date'], $b['date']));
        return array_values($exc);
    }

    private static function formatTime(string $time): string
    {
        if (!$time || !str_contains($time, ':')) return $time;
        [$h, $m] = explode(':', $time);
        return (int)$h . 'h' . $m;
    }

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
