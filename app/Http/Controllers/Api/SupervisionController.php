<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupervisionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return response()->json([
            'enabled' => $this->hasPermanentSupervision($request),
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $supervisor = $this->requireStrictSupervisorValidation(
            $request,
            'L’activation du mode superviseur permanent exige une authentification superviseur.'
        );
        $token = $this->createMobilePermanentSupervisionToken($supervisor);

        ActivityLogger::log(
            'auth.supervisor_permanent_enabled',
            'Mode superviseur permanent activé depuis l’application mobile par le superviseur #' . $supervisor->supervisor_number,
            null,
            null,
            ['supervisor_number' => $supervisor->supervisor_number, 'channel' => 'mobile']
        );

        return response()->json([
            'enabled' => true,
            'token' => $token,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->disableMobilePermanentSupervision($request);

        ActivityLogger::log('auth.supervisor_permanent_disabled', 'Mode superviseur permanent désactivé depuis l’application mobile.', null, null, ['channel' => 'mobile']);

        return response()->json(['enabled' => false]);
    }
}