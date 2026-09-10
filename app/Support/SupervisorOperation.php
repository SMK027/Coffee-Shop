<?php

namespace App\Support;

/**
 * Catalogue des opérations sensibles pouvant être restreintes par habilitation
 * de superviseur. Chaque opération regroupe un ou plusieurs points d'entrée
 * (routes web nommées et chemins d'API mobile) déjà protégés par un bypass
 * superviseur (voir App\Http\Controllers\Controller::requireSuperAdminOrSupervisor
 * et ::requireStrictSupervisorValidation).
 *
 * Une route/chemin non référencé ici n'est soumis à aucune restriction
 * d'habilitation (comportement inchangé).
 */
final class SupervisorOperation
{
    public const SHOP_SETTINGS = 'shop_settings';
    public const PAYMENT_METHODS = 'payment_methods';
    public const ORDERS = 'orders';
    public const REFUNDS = 'refunds';
    public const VOUCHERS = 'vouchers';
    public const LOYALTY = 'loyalty';
    public const INTERNAL_NOTES = 'internal_notes';
    public const USERS = 'users';
    public const ORDER_STATUSES = 'order_statuses';
    public const DRINKS = 'drinks';
    public const SUPERVISORS = 'supervisors';
    public const LOST_CREDENTIALS = 'lost_credentials';
    public const SUPERVISION_PERMANENT = 'supervision_permanent';
    public const PDF_BOARD = 'pdf_board';
    public const QR_LOGIN = 'qr_login';
    public const PLANNING_EDIT = 'planning_edit';
    public const PLANNING_PDF = 'planning_pdf';

    /** Libellés affichés dans la page de gestion des superviseurs. */
    public const LABELS = [
        self::SHOP_SETTINGS => 'Paramètres boutique (horaires, IP autorisées, fonctionnalités)',
        self::PAYMENT_METHODS => 'Moyens de paiement',
        self::ORDERS => 'Commandes (suppression, création hors horaires, changement de statut)',
        self::REFUNDS => 'Remboursements',
        self::VOUCHERS => 'Bons d\'achat',
        self::LOYALTY => 'Programme fidélité (points, réductions, offres, suppression de carte)',
        self::INTERNAL_NOTES => 'Notes internes',
        self::USERS => 'Comptes salariés (création, connexion rapide, prise de contrôle)',
        self::ORDER_STATUSES => 'Statuts de commande',
        self::DRINKS => 'Tarifs des boissons',
        self::SUPERVISORS => 'Suppression de superviseurs',
        self::LOST_CREDENTIALS => 'Signalement d\'identifiants perdus',
        self::SUPERVISION_PERMANENT => 'Activation du mode superviseur permanent',
        self::PDF_BOARD => 'Génération de planches PDF de connexion',
        self::QR_LOGIN => 'Connexion par QR code ou clé de sécurité physique',
        self::PLANNING_EDIT => 'Modification des plannings salariés',
        self::PLANNING_PDF => 'Génération des plannings au format PDF',
    ];

    /** Correspondance nom de route web → opération. */
    private const ROUTE_MAP = [
        'employee.shop-settings.update' => self::SHOP_SETTINGS,
        'employee.shop-settings.supervisor-ips.update' => self::SHOP_SETTINGS,
        'employee.shop-settings.features.update' => self::SHOP_SETTINGS,
        'employee.shop-settings.exception.add' => self::SHOP_SETTINGS,

        'employee.payment-methods.store' => self::PAYMENT_METHODS,
        'employee.payment-methods.update' => self::PAYMENT_METHODS,
        'employee.payment-methods.toggle' => self::PAYMENT_METHODS,

        'employee.orders.destroy' => self::ORDERS,
        'employee.orders.store' => self::ORDERS,

        'employee.orders.refund.store' => self::REFUNDS,
        'employee.refunds.store' => self::REFUNDS,

        'employee.vouchers.store' => self::VOUCHERS,
        'employee.vouchers.update' => self::VOUCHERS,

        'employee.loyalty.points.adjust' => self::LOYALTY,
        'employee.loyalty.destroy' => self::LOYALTY,
        'employee.loyalty.offers.store' => self::LOYALTY,
        'employee.loyalty.offers.destroy' => self::LOYALTY,
        'employee.loyalty-discounts.store' => self::LOYALTY,
        'employee.loyalty-discounts.update' => self::LOYALTY,
        'employee.loyalty-discounts.destroy' => self::LOYALTY,

        'employee.internal-notes.create' => self::INTERNAL_NOTES,
        'employee.internal-notes.edit' => self::INTERNAL_NOTES,
        'employee.internal-notes.destroy' => self::INTERNAL_NOTES,

        'employee.users.store' => self::USERS,
        'employee.users.quick-login.reactivate' => self::USERS,
        'employee.users.take-control' => self::USERS,

        'employee.order-statuses.store' => self::ORDER_STATUSES,
        'employee.order-statuses.update' => self::ORDER_STATUSES,
        'employee.order-statuses.toggle' => self::ORDER_STATUSES,
        'employee.order-statuses.destroy' => self::ORDER_STATUSES,

        'employee.drinks.store' => self::DRINKS,
        'employee.drinks.update' => self::DRINKS,

        'employee.supervisors.destroy' => self::SUPERVISORS,

        'employee.lost-credentials.create' => self::LOST_CREDENTIALS,

        'employee.supervision.permanent.enable' => self::SUPERVISION_PERMANENT,

        'employee.users.pdf-board' => self::PDF_BOARD,
        'employee.supervisors.pdf-board' => self::PDF_BOARD,

        'login.qr.store' => self::QR_LOGIN,
        'login.security-key.store' => self::QR_LOGIN,

        'employee.plannings.edit' => self::PLANNING_EDIT,
        'employee.plannings.update' => self::PLANNING_EDIT,
        'employee.plannings.pdf' => self::PLANNING_PDF,
    ];

    /**
     * Correspondance chemin d'API mobile → opération.
     * Les `*` correspondent à un segment de chemin (identique au format
     * utilisé par App\Services\ActivityLogger::$apiPathLabels).
     */
    private const API_PATH_MAP = [
        'api/orders' => self::ORDERS,
        'api/orders/*' => self::ORDERS,
        'api/orders/*/status' => self::ORDERS,
        'api/orders/*/refund' => self::REFUNDS,

        'api/vouchers' => self::VOUCHERS,
        'api/vouchers/*' => self::VOUCHERS,

        'api/loyalty-cards/*/adjust' => self::LOYALTY,
        'api/loyalty-cards/*/offers' => self::LOYALTY,
        'api/loyalty-cards/*/offers/*' => self::LOYALTY,
        'api/loyalty-discounts' => self::LOYALTY,

        'api/supervision/permanent' => self::SUPERVISION_PERMANENT,

        'api/auth/login/qr' => self::QR_LOGIN,
    ];

    /** Résout l'opération correspondant à une route nommée ou un chemin d'API. */
    public static function resolve(?string $routeName, ?string $path): ?string
    {
        if ($routeName !== null && isset(self::ROUTE_MAP[$routeName])) {
            return self::ROUTE_MAP[$routeName];
        }

        if ($path !== null) {
            $cleanPath = ltrim($path, '/');
            foreach (self::API_PATH_MAP as $pattern => $operation) {
                $regex = '#^' . str_replace('\*', '[^/]+', preg_quote($pattern, '#')) . '$#';
                if (preg_match($regex, $cleanPath)) {
                    return $operation;
                }
            }
        }

        return null;
    }

    public static function label(string $operation): string
    {
        return self::LABELS[$operation] ?? $operation;
    }

    /** Liste [clé => libellé] pour l'affichage des cases à cocher d'habilitation. */
    public static function options(): array
    {
        return self::LABELS;
    }
}
