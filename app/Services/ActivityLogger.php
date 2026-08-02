<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
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
