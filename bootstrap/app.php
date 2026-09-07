<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('internal-notes:purge-expired')->daily();
        $schedule->command('supervisors:disable-expired-temporary')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Faire confiance à Traefik (reverse proxy) pour les headers X-Forwarded-*
        // Permet à Laravel de générer des URLs https:// derrière un proxy SSL
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('employee.dashboard'));

        $middleware->alias([
            'employee' => \App\Http\Middleware\EnsureIsEmployee::class,
            'feature'  => \App\Http\Middleware\EnsureFeatureIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Retourner du JSON pour les erreurs de l'API mobile
        $exceptions->shouldRenderJsonWhen(fn (\Illuminate\Http\Request $request, \Throwable $e) => $request->is('api/*'));
    })->create();
