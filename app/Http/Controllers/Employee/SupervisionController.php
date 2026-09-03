<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupervisionController extends Controller
{
    public function permanent(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        return view('employee.supervision.permanent', [
            'isPermanentSupervisionEnabled' => $this->hasPermanentSupervision($request),
        ]);
    }

    public function enablePermanent(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $supervisor = $this->requireStrictSupervisorValidation(
            $request,
            'L’activation du mode superviseur permanent exige une authentification superviseur.'
        );

        $this->enablePermanentSupervision($request, $supervisor);

        ActivityLogger::log(
            'auth.supervisor_permanent_enabled',
            'Mode superviseur permanent activé par le superviseur #' . $supervisor->supervisor_number,
            null,
            null,
            ['supervisor_number' => $supervisor->supervisor_number]
        );

        return redirect()->route('employee.supervision.permanent')
            ->with('success', 'Le mode superviseur permanent est activé pour cette session.');
    }

    public function disablePermanent(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->disablePermanentSupervision($request);

        ActivityLogger::log('auth.supervisor_permanent_disabled', 'Mode superviseur permanent désactivé.');

        return redirect()->route('employee.supervision.permanent')
            ->with('success', 'Le mode superviseur permanent est désactivé.');
    }

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

        return response()->view('employee.supervision.replay', [
            'actionPath' => '/' . ltrim((string) ($pending['path'] ?? ''), '/'),
            'method' => strtoupper((string) ($pending['method'] ?? 'POST')),
            'payload' => is_array($payload) ? $payload : [],
        ]);
    }
}
