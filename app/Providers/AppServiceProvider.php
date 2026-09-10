<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed as AuthFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Les ceremonies WebAuthn (clés de sécurité) sont pilotées par nos
        // propres contrôleurs (auth employé custom, middleware espace-employe,
        // habilitation superviseur) plutôt que par les routes/middlewares par
        // défaut du package (guard "web" nu, password.confirm inexistant ici).
        Passkeys::ignoreRoutes();
    }

    public function boot(): void
    {
        // Log des tentatives de connexion échouées
        Event::listen(AuthFailed::class, function (AuthFailed $event) {
            ActivityLogger::logAs(
                null,
                $event->credentials['email'] ?? 'inconnu',
                'auth.failed',
                'Tentative de connexion échouée',
                null,
                null,
                ['email' => $event->credentials['email'] ?? null]
            );
        });
    }
}
