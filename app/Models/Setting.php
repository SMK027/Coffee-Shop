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
    public const KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS = 'supervisor_management_allowed_ips';

    /** Fonctionnalités activables / désactivables. */
    public const KEY_FEATURE_QUICK_LOGIN       = 'feature_quick_login';
    public const KEY_FEATURE_VOUCHERS          = 'feature_vouchers';
    public const KEY_FEATURE_REFUNDS           = 'feature_refunds';
    public const KEY_FEATURE_LOYALTY_CARDS     = 'feature_loyalty_cards';
    public const KEY_FEATURE_LOYALTY_DISCOUNTS = 'feature_loyalty_discounts';
    public const KEY_FEATURE_DAILY_REPORTS     = 'feature_daily_reports';

    public const FEATURES = [
        self::KEY_FEATURE_QUICK_LOGIN       => 'Connexion rapide par QR code',
        self::KEY_FEATURE_VOUCHERS          => 'Émission de bons d\'achat',
        self::KEY_FEATURE_REFUNDS           => 'Remboursements de commande',
        self::KEY_FEATURE_LOYALTY_CARDS     => 'Création de cartes de fidélité',
        self::KEY_FEATURE_LOYALTY_DISCOUNTS => 'Création de réductions de fidélité',
        self::KEY_FEATURE_DAILY_REPORTS     => 'Génération de récapitulatifs journaliers',
    ];

    public const DEFAULTS = [
        self::KEY_POINTS_PER_EURO => '5',
        self::KEY_SHOP_ADDRESS    => "12 Rue des Arômes\n75001 Paris",
        self::KEY_SHOP_PHONE      => '01 23 45 67 89',
        self::KEY_SHOP_EMAIL      => 'contact@lecoffeeshop.fr',
        self::KEY_SHOP_HOURS      => '{"regular":{"monday":{"open":true,"from":"07:00","to":"19:00"},"tuesday":{"open":true,"from":"07:00","to":"19:00"},"wednesday":{"open":true,"from":"07:00","to":"19:00"},"thursday":{"open":true,"from":"07:00","to":"19:00"},"friday":{"open":true,"from":"07:00","to":"19:00"},"saturday":{"open":true,"from":"08:00","to":"20:00"},"sunday":{"open":true,"from":"09:00","to":"18:00"}},"exceptions":[]}',
        self::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS => "127.0.0.1\n::1",
        self::KEY_FEATURE_QUICK_LOGIN       => '1',
        self::KEY_FEATURE_VOUCHERS          => '1',
        self::KEY_FEATURE_REFUNDS           => '1',
        self::KEY_FEATURE_LOYALTY_CARDS     => '1',
        self::KEY_FEATURE_LOYALTY_DISCOUNTS => '1',
        self::KEY_FEATURE_DAILY_REPORTS     => '1',
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
     * Indique si une fonctionnalité de l'application est activée.
     */
    public static function isFeatureEnabled(string $featureKey): bool
    {
        return self::get($featureKey, '1') === '1';
    }

    /**
     * Ratio de points par euro (nombre de points crédités pour chaque euro dépensé).
     */
    public static function pointsPerEuro(): int
    {
        return (int) self::get(self::KEY_POINTS_PER_EURO, '5');
    }

    /**
     * Vérifie si l'heure actuelle est dans la plage d'ouverture ± $marginMinutes.
     * Retourne true si la boutique est ouverte (ou dans la marge), false sinon.
     */
    public static function isWithinOpeningHours(int $marginMinutes = 15): bool
    {
        $hours = self::getHours();
        $now   = now();
        $day   = strtolower($now->format('l')); // 'monday', 'tuesday', …

        // Vérifier d'abord les exceptions
        foreach ($hours['exceptions'] as $exception) {
            if (($exception['date'] ?? '') === $now->toDateString()) {
                if (!($exception['open'] ?? false)) {
                    return false;
                }
                return self::timeInRange($now, $exception['from'] ?? '00:00', $exception['to'] ?? '23:59', $marginMinutes);
            }
        }

        $regular = $hours['regular'][$day] ?? ['open' => false];
        if (!($regular['open'] ?? false)) {
            return false;
        }

        return self::timeInRange($now, $regular['from'] ?? '00:00', $regular['to'] ?? '23:59', $marginMinutes);
    }

    private static function timeInRange(\Carbon\Carbon $now, string $from, string $to, int $marginMinutes): bool
    {
        [$fh, $fm] = array_map('intval', explode(':', $from));
        [$th, $tm] = array_map('intval', explode(':', $to));

        $open  = $now->copy()->setTime($fh, $fm)->subMinutes($marginMinutes);
        $close = $now->copy()->setTime($th, $tm)->addMinutes($marginMinutes);

        return $now->between($open, $close);
    }
}
