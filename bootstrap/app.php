<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Faire confiance à Traefik (reverse proxy) pour les headers X-Forwarded-*
        // Permet à Laravel de générer des URLs https:// derrière un proxy SSL
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('employee.dashboard'));

        $middleware->alias([
            'employee' => \App\Http\Middleware\EnsureIsEmployee::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
