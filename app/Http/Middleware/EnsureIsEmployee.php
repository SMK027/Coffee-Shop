<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsEmployee
{
    /**
     * Vérifie que l'utilisateur connecté possède un rôle d'employé (admin ou superadmin).
     * Redirige vers la page d'accueil avec un message d'erreur sinon.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->isAdmin()) {
            abort(403, 'Accès réservé aux employés.');
        }

        return $next($request);
    }
}
