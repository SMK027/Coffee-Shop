<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisionController extends Controller
{
    public function challenge(Request $request)
    {
        $pending = $this->pendingSupervision($request);

        if ($pending === null) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Aucune opération en attente de validation superviseur.');
        }

        return view('employee.supervision.challenge', [
            'pending' => $pending,
            'operationLabel' => ActivityLogger::routeLabel($pending['route_name'] ?? null, $pending['path'] ?? null),
        ]);
    }

    public function approve(Request $request): Response
    {
        $pending = $this->pendingSupervision($request);

        if ($pending === null) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Aucune opération en attente de validation superviseur.');
        }

        $supervisor = $this->validateSupervisorCredentials($request, $pending['message'] ?? 'Numéro de superviseur ou PIN incorrect.');
        if ($supervisor === null) {
            abort(403, 'Validation superviseur requise.');
        }

        $nonce = $this->grantSupervisionBypass($request, $supervisor, $pending);
        $this->clearPendingSupervision($request);

        $payload = $pending['input'] ?? [];
        $payload['__supervision_bypass_nonce'] = $nonce;

        return $this->replayPendingSupervision($request, $pending, $payload);
    }
}
