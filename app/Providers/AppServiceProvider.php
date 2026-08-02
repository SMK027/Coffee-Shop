<?php

namespace App\Providers;

use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Failed as AuthFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

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
