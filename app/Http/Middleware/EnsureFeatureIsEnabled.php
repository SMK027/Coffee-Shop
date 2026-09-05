<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureIsEnabled
{
    /**
     * Vérifie qu'une fonctionnalité spécifique est activée.
     * Envoie une erreur JSON (403) pour l'API mobile ou une redirection avec message d'erreur pour le web.
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        if (! Setting::isFeatureEnabled($featureKey)) {
            $message = 'Cette fonctionnalité a été désactivée par un administrateur.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $message,
                ], 403);
            }

            if ($request->isMethod('get')) {
                return redirect()->route('employee.dashboard')->with('error', $message);
            }

            return redirect()->back()->withInput()->with('error', $message);
        }

        return $next($request);
    }
}
