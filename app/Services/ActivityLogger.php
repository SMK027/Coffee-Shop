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
        // Authentification / compte
        'login'                              => 'Ouverture de la connexion',
        'logout'                             => 'Déconnexion',
        'password.request'                   => 'Demande de réinitialisation de mot de passe',
        'password.email'                     => 'Envoi du lien de réinitialisation de mot de passe',
        'password.reset'                     => 'Ouverture de la réinitialisation de mot de passe',
        'password.store'                     => 'Réinitialisation du mot de passe',
        'password.confirm'                   => 'Confirmation du mot de passe',
        'password.update'                    => 'Mise à jour du mot de passe',
        'verification.notice'                => 'Demande de vérification d\'adresse email',
        'verification.verify'                => 'Vérification d\'adresse email',
        'verification.send'                  => 'Renvoi de l\'email de vérification',
        // Interface publique
        'home'                               => 'Consultation de l\'accueil',
        'menu'                               => 'Consultation du menu',
        'contact'                            => 'Consultation du formulaire de contact',
        'contact.submit'                     => 'Envoi d\'un message de contact',
        'testimonial.submit'                 => 'Envoi d\'un témoignage',
        'sitemap'                            => 'Consultation du sitemap',
        'robots'                             => 'Consultation du fichier robots',
        'loyalty.create'                     => 'Ouverture de la création de carte fidélité',
        'loyalty.store'                      => 'Création d\'une carte fidélité',
        'loyalty.balance.form'               => 'Ouverture de la consultation des points fidélité',
        'loyalty.balance'                    => 'Consultation des points fidélité',
        'loyalty.balance.order.show'         => 'Consultation d\'une commande fidélité',
        'loyalty.pin.form'                   => 'Ouverture de la réinitialisation du PIN fidélité',
        'loyalty.pin.reset'                  => 'Réinitialisation du PIN fidélité',
        'employee.password.form'             => 'Ouverture de la réinitialisation de mot de passe employé',
        'employee.password.reset'            => 'Réinitialisation du mot de passe employé',
        // Tableau de bord / navigation back-office
        'employee.dashboard'                 => 'Consultation du tableau de bord employé',
        'employee.supervision.challenge'     => 'Consultation de la demande de validation superviseur',
        'employee.supervision.approve'       => 'Validation superviseur pour exécution différée',
        'employee.profile.edit'              => 'Consultation du profil',
        'employee.profile.update'            => 'Mise à jour du profil',
        'employee.profile.destroy'           => 'Suppression du profil',
        'employee.activity-logs.index'       => 'Consultation du journal d\'activité',
        // Bons d'achat
        'employee.vouchers.index'            => 'Consultation des bons d\'achat',
        'employee.vouchers.create'           => 'Ouverture du formulaire de création d\'un bon d\'achat',
        'employee.vouchers.store'              => "Création d'un bon d'achat",
        'employee.vouchers.check'            => 'Vérification d\'un bon d\'achat',
        'employee.vouchers.show'             => 'Consultation d\'un bon d\'achat',
        'employee.vouchers.edit'             => 'Ouverture du formulaire de modification d\'un bon d\'achat',
        'employee.vouchers.update'             => "Modification d'un bon d'achat",
        // Commandes
        'employee.orders.index'              => 'Consultation des commandes',
        'employee.orders.identify'           => 'Ouverture de l\'identification client pour commande',
        'employee.orders.identify.store'     => 'Validation de l\'identification client pour commande',
        'employee.orders.create'             => 'Ouverture de la création de commande',
        'employee.orders.loyalty-check'      => 'Vérification de carte fidélité pour commande',
        'employee.orders.loyalty-search'     => 'Recherche de carte fidélité pour commande',
        'employee.orders.pin-verify'         => 'Vérification du PIN fidélité pour commande',
        'employee.orders.store'              => 'Création d\'une commande',
        'employee.orders.show'               => 'Consultation d\'une commande',
        'employee.orders.status'             => 'Changement de statut de commande',
        'employee.orders.destroy'            => 'Suppression d\'une commande',
        'employee.orders.refund'             => 'Ouverture d\'un remboursement depuis une commande',
        // Remboursements
        'employee.orders.refund.store'         => 'Remboursement (depuis la commande)',
        'employee.refunds.index'             => 'Consultation de la section remboursements',
        'employee.refunds.create'            => 'Ouverture de la création de remboursement',
        'employee.refunds.store'               => 'Remboursement (section dédiée)',
        // Statuts de commande
        'employee.order-statuses.index'      => 'Consultation des statuts de commande',
        'employee.order-statuses.create'     => 'Ouverture du formulaire de création d\'un statut de commande',
        'employee.order-statuses.store'        => "Création d'un statut de commande",
        'employee.order-statuses.edit'       => 'Ouverture du formulaire de modification d\'un statut',
        'employee.order-statuses.update'       => "Modification d'un statut",
        'employee.order-statuses.toggle'       => "Activation/désactivation d'un statut",
        'employee.order-statuses.destroy'      => "Suppression d'un statut",
        'employee.orders.payment'            => 'Ouverture de l\'enregistrement de paiement',
        'employee.orders.payment.store'      => 'Enregistrement de paiement',
        // Moyens de paiement
        'employee.payment-methods.index'     => 'Consultation des moyens de paiement',
        'employee.payment-methods.create'    => 'Ouverture du formulaire de création d\'un moyen de paiement',
        'employee.payment-methods.store'       => "Création d'un moyen de paiement",
        'employee.payment-methods.edit'      => 'Ouverture du formulaire de modification d\'un moyen de paiement',
        'employee.payment-methods.update'      => "Modification d'un moyen de paiement",
        'employee.payment-methods.toggle'      => "Activation d'un moyen de paiement",
        // Récapitulatifs
        'employee.daily-reports.index'       => 'Consultation des récapitulatifs journaliers',
        'employee.daily-reports.create'      => 'Ouverture de la création d\'un récapitulatif journalier',
        'employee.daily-reports.store'       => 'Création d\'un récapitulatif journalier',
        'employee.daily-reports.show'        => 'Consultation d\'un récapitulatif journalier',
        // Boissons
        'employee.drinks.index'              => 'Consultation des boissons',
        'employee.drinks.create'             => 'Ouverture du formulaire d\'ajout d\'une boisson',
        'employee.drinks.store'                => "Ajout d'une boisson (avec prix)",
        'employee.drinks.bulk-disable'       => 'Désactivation en masse de boissons',
        'employee.drinks.bulk-enable'        => 'Réactivation en masse de boissons',
        'employee.drinks.edit'               => 'Ouverture du formulaire de modification d\'une boisson',
        'employee.drinks.update'               => "Modification d'une boisson (prix)",
        'employee.drinks.destroy'            => 'Suppression d\'une boisson',
        'employee.drinks.toggle'             => 'Activation/désactivation d\'une boisson',
        // Témoignages
        'employee.testimonials.index'        => 'Consultation des témoignages',
        'employee.testimonials.publish'      => 'Publication d\'un témoignage',
        'employee.testimonials.reject'       => 'Rejet d\'un témoignage',
        'employee.testimonials.destroy'      => 'Suppression d\'un témoignage',
        // Contacts
        'employee.contacts.index'            => 'Consultation des contacts',
        'employee.contacts.show'             => 'Consultation d\'un contact',
        'employee.contacts.reply'            => 'Réponse à un contact',
        'employee.contacts.archive'          => 'Archivage d\'un contact',
        'employee.contacts.destroy'          => 'Suppression d\'un contact',
        // Statistiques
        'employee.stats.index'               => 'Consultation des statistiques',
        // Images d\'accueil
        'employee.home-images.index'         => 'Consultation des images d\'accueil',
        'employee.home-images.update'        => 'Mise à jour d\'une image d\'accueil',
        'employee.home-images.destroy'       => 'Suppression d\'une image d\'accueil',
        // Fidélité
        'employee.loyalty.index'             => 'Consultation des cartes fidélité',
        'employee.loyalty.create'            => 'Ouverture de la création d\'une carte fidélité',
        'employee.loyalty.store'             => 'Création d\'une carte fidélité (back-office)',
        'employee.loyalty.settings'          => 'Consultation des réglages fidélité',
        'employee.loyalty.settings.update'   => 'Mise à jour des réglages fidélité',
        'employee.loyalty.employees.search'  => 'Recherche d\'employés pour fidélité',
        'employee.loyalty.pin.send'          => 'Envoi d\'un lien de réinitialisation PIN fidélité',
        'employee.loyalty.points.adjust'       => "Ajustement de points fidélité",
        'employee.loyalty.destroy'             => "Suppression d'une carte fidélité",
        'employee.loyalty.holder.update'       => "Modification du titulaire d'une carte",
        'employee.loyalty.benefits.update'     => "Avantages salarié (carte fidélité)",
        'employee.loyalty.offers.store'      => 'Création d\'une offre personnalisée',
        'employee.loyalty.offers.destroy'    => 'Suppression d\'une offre personnalisée',
        'employee.loyalty.show'              => 'Consultation d\'une carte fidélité',
        // Réductions fidélité
        'employee.loyalty-discounts.index'   => 'Consultation des réductions fidélité',
        'employee.loyalty-discounts.create'  => 'Ouverture du formulaire de création d\'une réduction fidélité',
        'employee.loyalty-discounts.store'     => "Création d'une réduction fidélité",
        'employee.loyalty-discounts.edit'    => 'Ouverture du formulaire de modification d\'une réduction fidélité',
        'employee.loyalty-discounts.update'    => "Modification d'une réduction fidélité",
        'employee.loyalty-discounts.destroy'   => "Suppression d'une réduction fidélité",
        // Employés
        'employee.users.index'               => 'Consultation des salariés',
        'employee.users.create'              => 'Ouverture du formulaire de création d\'un salarié',
        'employee.users.store'               => 'Création d\'un salarié',
        'employee.users.edit'                => 'Ouverture du formulaire de modification d\'un salarié',
        'employee.users.update'              => 'Modification d\'un salarié',
        'employee.users.destroy'             => 'Suppression d\'un salarié',
        'employee.users.reset-link'          => 'Envoi d\'un lien de réinitialisation de mot de passe salarié',
        'employee.users.toggle-activation'   => 'Activation/désactivation d\'un salarié',
        // Superviseurs
        'employee.supervisors.index'           => 'Consultation des superviseurs',
        'employee.supervisors.create'          => 'Ouverture du formulaire de création d\'un superviseur',
        'employee.supervisors.store'           => "Création d'un superviseur",
        'employee.supervisors.show'            => "Consultation d'un superviseur",
        'employee.supervisors.edit'            => "Ouverture du formulaire de modification d'un superviseur",
        'employee.supervisors.update'          => "Modification d'un superviseur",
        'employee.supervisors.toggle-activation' => "Activation/désactivation d'un superviseur",
        'employee.supervisors.destroy'         => "Suppression d'un superviseur",
        // Paramètres boutique
        'employee.shop-settings.index'         => 'Consultation des paramètres boutique',
        'employee.shop-settings.update'        => 'Mise à jour des paramètres boutique',
        'employee.shop-settings.exception.add' => 'Ajout d\'une exception de fermeture boutique',
        'employee.shop-settings.exception.remove' => 'Suppression d\'une exception de fermeture boutique',
    ];

    /**
     * Correspondance chemin d'URL API → libellé lisible.
     * Utilisée quand la route n'a pas de nom (routes API mobiles).
     * Les `*` dans les patterns correspondent à un segment de chemin.
     */
    private static array $apiPathLabels = [
        'api/auth/login'                 => 'Connexion (application mobile)',
        'api/auth/refresh'               => 'Rafraîchissement de session (application mobile)',
        'api/auth/logout'                => 'Déconnexion (application mobile)',
        'api/auth/me'                    => 'Consultation du profil connecté (application mobile)',
        'api/drinks'                     => 'Consultation des boissons (application mobile)',
        'api/drinks/*/availability'      => 'Activation/désactivation d\'une boisson (application mobile)',
        'api/orders/statuses'            => 'Consultation des statuts de commande (application mobile)',
        'api/orders/*/refund'            => 'Remboursement (application mobile)',
        'api/orders/*/payments'          => 'Enregistrement de paiement (application mobile)',
        'api/orders/*/status'            => 'Changement de statut de commande (application mobile)',
        'api/orders'                     => 'Création de commande (application mobile)',
        'api/orders/*'                   => 'Consultation d\'une commande (application mobile)',
        'api/payment-methods'            => 'Consultation des moyens de paiement (application mobile)',
        'api/daily-reports/preview'      => 'Prévisualisation d\'un récapitulatif journalier (application mobile)',
        'api/loyalty-cards/*/adjust'     => 'Ajustement de points fidélité (application mobile)',
        'api/loyalty-cards'              => 'Consultation ou création de cartes fidélité (application mobile)',
        'api/loyalty-cards/check'        => 'Vérification d\'une carte fidélité (application mobile)',
        'api/loyalty-cards/verify-pin'   => 'Vérification du PIN fidélité (application mobile)',
        'api/loyalty-cards/*/offers'     => 'Gestion des offres fidélité (application mobile)',
        'api/loyalty-cards/*/offers/*'   => 'Modification ou suppression d\'une offre fidélité (application mobile)',
        'api/loyalty-cards/*'            => 'Consultation d\'une carte fidélité (application mobile)',
        'api/loyalty-discounts'          => 'Consultation ou création de réductions fidélité (application mobile)',
        'api/daily-reports'              => 'Récapitulatif journalier (application mobile)',
        'api/daily-reports/*'            => 'Consultation d\'un récapitulatif journalier (application mobile)',
        'api/vouchers/check'             => 'Vérification d\'un bon d\'achat (application mobile)',
        'api/vouchers'                   => 'Consultation ou création de bons d\'achat (application mobile)',
        'api/vouchers/*'                 => 'Consultation, modification ou suppression d\'un bon d\'achat (application mobile)',
        'api/supervisors'                => 'Consultation des superviseurs (application mobile)',
        'api/supervisors/*/barcode'      => 'Consultation du QR superviseur (application mobile)',
    ];

    /** Traduit un nom de route en libellé lisible. */
    public static function routeLabel(?string $routeName, ?string $path = null): string
    {
        // Route nommée connue dans le mapping web
        if ($routeName !== null && ! str_starts_with($routeName, 'generated::')) {
            return self::$routeLabels[$routeName] ?? $routeName;
        }

        // Route non nommée (API mobile) → matching par chemin
        if ($path !== null) {
            $cleanPath = ltrim($path, '/');
            foreach (self::$apiPathLabels as $pattern => $label) {
                $regex = '#^' . str_replace('\*', '[^/]+', preg_quote($pattern, '#')) . '$#';
                if (preg_match($regex, $cleanPath)) {
                    return $label;
                }
            }
            // Dernier recours : afficher le chemin lisiblement
            return 'API mobile : ' . $cleanPath;
        }

        return 'Action inconnue';
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
