<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion d'un salarié et génération du token JWT.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = Auth::guard('api')->attempt($credentials);

        if (!$token) {
            return response()->json([
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        $user = Auth::guard('api')->user();

        if (! $user->isActive()) {
            Auth::guard('api')->logout();
            return response()->json([
                'message' => 'Ce compte a été désactivé.',
            ], 403);
        }

        if (! $user->isAdmin() && ! $user->isModerator()) {
            Auth::guard('api')->logout();
            return response()->json([
                'message' => 'Accès réservé aux salariés.',
            ], 403);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Étape 1-2 : identification du salarié à partir du QR code scanné (mobile).
     */
    public function identifyQr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:200'],
        ]);

        $user = $this->resolveQrUser($validated['token']);

        if (! $user) {
            return response()->json([
                'message' => 'QR code invalide ou compte introuvable.',
            ], 422);
        }

        return response()->json([
            'name'     => $user->name,
            'username' => $user->username,
        ]);
    }

    /**
     * Étape 3 : authentification superviseur obligatoire puis connexion effective par QR code.
     */
    public function loginQr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:200'],
        ]);

        $user = $this->resolveQrUser($validated['token']);

        if (! $user) {
            throw ValidationException::withMessages([
                'token' => 'QR code invalide ou compte introuvable.',
            ]);
        }

        if (! $user->isAdmin() && ! $user->isModerator()) {
            return response()->json([
                'message' => 'Accès réservé aux salariés.',
            ], 403);
        }

        // Authentification superviseur obligatoire, sans dérogation possible.
        $this->requireStrictSupervisorValidation(
            $request,
            'Une authentification superviseur est requise pour se connecter par QR code.'
        );

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token);
    }

    private function resolveQrUser(string $token): ?User
    {
        $user = User::fromLoginToken($token);

        return ($user && $user->isActive()) ? $user : null;
    }

    /**
     * Renouvellement du token JWT.
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::guard('api')->refresh();
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 401);
        }
    }

    /**
     * Déconnexion (invalidation du token).
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * Informations sur l'utilisateur connecté.
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        return response()->json([
            'id'          => $user->id,
            'username'    => $user->username,
            'name'        => $user->name,
            'email'       => $user->email,
            'global_role' => $user->global_role,
        ]);
    }

    private function respondWithToken(string $token): JsonResponse
    {
        $user = Auth::guard('api')->user();
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
            'user'         => [
                'id'          => $user->id,
                'username'    => $user->username,
                'name'        => $user->name,
                'email'       => $user->email,
                'global_role' => $user->global_role,
            ],
        ]);
    }
}
