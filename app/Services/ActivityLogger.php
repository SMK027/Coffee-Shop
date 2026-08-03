<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    /**
     * Correspondance nom de route → libellé lisible en français.
     * Utilisée pour rendre les entrées superviseur compréhensibles.
     */
    private static array $routeLabels = [
        // Bons d'achat
        'employee.vouchers.store'              => "Création d'un bon d'achat",
        'employee.vouchers.update'             => "Modification d'un bon d'achat",
        // Remboursements
        'employee.orders.refund.store'         => 'Remboursement (depuis la commande)',
        'employee.refunds.store'               => 'Remboursement (section dédiée)',
        // Statuts de commande
        'employee.order-statuses.store'        => "Création d'un statut de commande",
        'employee.order-statuses.update'       => "Modification d'un statut",
        'employee.order-statuses.toggle'       => "Activation/désactivation d'un statut",
        'employee.order-statuses.destroy'      => "Suppression d'un statut",
        // Moyens de paiement
        'employee.payment-methods.store'       => "Création d'un moyen de paiement",
        'employee.payment-methods.update'      => "Modification d'un moyen de paiement",
        'employee.payment-methods.toggle'      => "Activation d'un moyen de paiement",
        // Boissons
        'employee.drinks.store'                => "Ajout d'une boisson (avec prix)",
        'employee.drinks.update'               => "Modification d'une boisson (prix)",
        // Fidélité
        'employee.loyalty.points.adjust'       => "Ajustement de points fidélité",
        'employee.loyalty.destroy'             => "Suppression d'une carte fidélité",
        'employee.loyalty.holder.update'       => "Modification du titulaire d'une carte",
        'employee.loyalty.benefits.update'     => "Avantages salarié (carte fidélité)",
        // Réductions fidélité
        'employee.loyalty-discounts.store'     => "Création d'une réduction fidélité",
        'employee.loyalty-discounts.update'    => "Modification d'une réduction fidélité",
        'employee.loyalty-discounts.destroy'   => "Suppression d'une réduction fidélité",
        // Paramètres boutique
        'employee.shop-settings.index'         => "Mise à jour des paramètres boutique",
    ];

    /** Traduit un nom de route en libellé lisible. */
    public static function routeLabel(?string $routeName): string
    {
        if ($routeName === null) {
            return 'Action inconnue';
        }
        return self::$routeLabels[$routeName] ?? $routeName;
    }

    /**
     * Enregistre une action dans le journal d'activité.
     * Ne fait jamais échouer la requête principale si l'insertion plante.
     */
    public static function log(
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $context = []
    ): void {
        try {
            ActivityLog::create([
                'user_id'      => auth()->id(),
                'user_name'    => auth()->user()?->name ?? 'Système',
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => $description,
                'context'      => empty($context) ? null : $context,
                'ip_address'   => request()?->ip(),
            ]);
        } catch (\Throwable) {
            // Silencieux : les logs ne doivent jamais bloquer une opération métier
        }
    }

    /** Raccourci avec un utilisateur explicite (pour les événements système). */
    public static function logAs(
        ?int $userId,
        string $userName,
        string $action,
        string $description,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $context = []
    ): void {
        try {
            ActivityLog::create([
                'user_id'      => $userId,
                'user_name'    => $userName,
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'description'  => $description,
                'context'      => empty($context) ? null : $context,
                'ip_address'   => request()?->ip(),
            ]);
        } catch (\Throwable) {
            //
        }
    }
}
