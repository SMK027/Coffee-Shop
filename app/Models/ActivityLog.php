<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type',
        'subject_id', 'description', 'context', 'ip_address',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Retourne la catégorie principale (premier segment de l'action). */
    public function getCategoryAttribute(): string
    {
        return explode('.', $this->action)[0] ?? 'other';
    }

    /** Couleurs Tailwind associées à chaque catégorie. */
    public static function categoryConfig(): array
    {
        return [
            'auth'     => ['badge' => 'bg-blue-100 text-blue-700',    'dot' => 'bg-blue-500',    'label' => 'Authentification'],
            'order'    => ['badge' => 'bg-amber-100 text-amber-700',   'dot' => 'bg-amber-500',   'label' => 'Commandes'],
            'refund'   => ['badge' => 'bg-red-100 text-red-700',       'dot' => 'bg-red-500',     'label' => 'Remboursements'],
            'payment'  => ['badge' => 'bg-green-100 text-green-700',   'dot' => 'bg-green-500',   'label' => 'Paiements'],
            'voucher'  => ['badge' => 'bg-purple-100 text-purple-700', 'dot' => 'bg-purple-500',  'label' => 'Bons d\'achat'],
            'user'     => ['badge' => 'bg-indigo-100 text-indigo-700', 'dot' => 'bg-indigo-500',  'label' => 'Utilisateurs'],
            'drink'    => ['badge' => 'bg-orange-100 text-orange-700', 'dot' => 'bg-orange-500',  'label' => 'Menu'],
            'settings' => ['badge' => 'bg-stone-100 text-stone-600',   'dot' => 'bg-stone-400',   'label' => 'Paramètres'],
            'other'    => ['badge' => 'bg-gray-100 text-gray-600',     'dot' => 'bg-gray-400',    'label' => 'Autre'],
        ];
    }
}
