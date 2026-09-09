<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Services\ActivityLogger;
use App\Support\SupervisorOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    private const SUPERVISION_PENDING_KEY = 'supervision.pending';
    private const SUPERVISION_BYPASSES_KEY = 'supervision.bypasses';
    private const PERMANENT_SUPERVISION_KEY = 'supervision.permanent';
    private const MOBILE_PERMANENT_SUPERVISION_HEADER = 'X-Supervisor-Permanent-Token';
    private const MOBILE_PERMANENT_SUPERVISION_TTL_HOURS = 8;
    private const SUPERVISION_BYPASS_TTL_SECONDS = 300;
    private const HABILITATION_DENIED_MESSAGE = 'Echec authentification superviseur: fonctionnalité non autorisée';

    protected function requireSuperAdminOrSupervisor(Request $request, string $message = 'Numéro de superviseur ou PIN incorrect.'): ?Supervisor
    {
        $permanentSupervisor = $this->resolvePermanentSupervisor($request);
        if ($permanentSupervisor !== null) {
            $this->ensureHabilitated($permanentSupervisor, $request);

            return null;
        }

        $bypassSupervisor = $this->consumeSupervisionBypass($request);
        if ($bypassSupervisor !== null) {
            return $bypassSupervisor;
        }

        if (! $this->requestHasSupervisorCredentials($request) && ! $request->expectsJson()) {
            $this->storePendingSupervision($request, $message);

            throw new HttpResponseException(
                redirect()->route('employee.supervision.challenge')
            );
        }

        return $this->validateSupervisorCredentials($request, $message);
    }

    protected function validateSupervisorCredentials(
        Request $request,
        string $message = 'Numéro de superviseur ou PIN incorrect.',
        bool $allowSuperAdminBypass = false,
        ?string $operationRouteName = null,
        ?string $operationPath = null
    ): ?Supervisor
    {
        if ($allowSuperAdminBypass && auth()->user()->isSuperAdmin()) {
            return null;
        }

        $tokenRaw = trim((string) $request->input('supervisor_token', ''));
        if ($tokenRaw !== '') {
            $tokenCompact = preg_replace('/\s+/', '', $tokenRaw) ?? $tokenRaw;
            $token = preg_replace('/^SUPERVISOR:/', '', $tokenCompact) ?? $tokenCompact;

            // Short token format: "<supervisor_number>.<signature>"
            if (preg_match('/^([A-Za-z0-9_-]{1,50})\.([A-Fa-f0-9]{20})$/', $token, $matches) === 1) {
                $number = $matches[1];
                $signature = strtolower($matches[2]);

                $supervisor = Supervisor::where('supervisor_number', $number)
                    ->where('is_active', true)
                    ->first();

                if ($supervisor?->isActive()) {
                    $payload = $supervisor->supervisor_number . '|' . $supervisor->password;
                    $expected = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 20);

                    if (hash_equals($expected, $signature)) {
                        $this->ensureHabilitated($supervisor, $request, $operationRouteName, $operationPath);

                        ActivityLogger::log(
                            'auth.supervisor',
                            'Validation superviseur #' . $supervisor->supervisor_number . ' (token court) — ' . ActivityLogger::routeLabel($request->route()?->getName(), $request->path()),
                            null,
                            null,
                            ['supervisor_number' => $supervisor->supervisor_number, 'action' => ActivityLogger::routeLabel($request->route()?->getName(), $request->path())]
                        );

                        return $supervisor;
                    }
                }
            }

            // Legacy fallback (old encrypted token format)
            try {
                $decrypted = Crypt::decryptString($token);
                $decoded = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);

                $number = trim((string) ($decoded['supervisor_number'] ?? ''));
                $passwordHash = trim((string) ($decoded['password_hash'] ?? ''));

                if ($number === '' || $passwordHash === '') {
                    throw new \RuntimeException('invalid payload');
                }

                $supervisor = Supervisor::where('supervisor_number', $number)
                    ->where('is_active', true)
                    ->first();

                $valid = $supervisor?->isActive() && hash_equals($supervisor->password, $passwordHash);
                if ($valid) {
                    $this->ensureHabilitated($supervisor, $request, $operationRouteName, $operationPath);

                    ActivityLogger::log(
                        'auth.supervisor',
                        'Validation superviseur #' . $supervisor->supervisor_number . ' (token) — ' . ActivityLogger::routeLabel($request->route()?->getName(), $request->path()),
                        null,
                        null,
                        ['supervisor_number' => $supervisor->supervisor_number, 'action' => ActivityLogger::routeLabel($request->route()?->getName(), $request->path())]
                    );

                    return $supervisor;
                }
            } catch (\Throwable $e) {
                // fallback to classic credentials validation below
            }
        }

        $payload = [
            'supervisor_number' => trim((string) $request->input('supervisor_number', $request->input('supervisor_username', ''))),
            'supervisor_pin'    => trim((string) $request->input('supervisor_pin',    $request->input('supervisor_password', ''))),
        ];

        $validated = Validator::make($payload, [
            'supervisor_number' => ['required', 'string', 'max:50'],
            'supervisor_pin'    => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ], [
            'supervisor_number.required' => 'Le numéro du superviseur est requis.',
            'supervisor_pin.required'    => 'Le PIN du superviseur est requis.',
            'supervisor_pin.regex'       => 'Le PIN doit contenir entre 4 et 6 chiffres.',
        ])->validate();

        $supervisor = Supervisor::where('supervisor_number', $validated['supervisor_number'])
            ->where('is_active', true)
            ->first();

        $valid = $supervisor?->isActive() && Hash::check($validated['supervisor_pin'], $supervisor->password);

        if (! $valid) {
            ActivityLogger::log(
                'auth.supervisor_failed',
                'Échec de validation superviseur — numéro : ' . $validated['supervisor_number'] . ' — ' . ActivityLogger::routeLabel($request->route()?->getName(), $request->path()),
                null, null,
                ['supervisor_number' => $validated['supervisor_number'], 'action' => ActivityLogger::routeLabel($request->route()?->getName(), $request->path())]
            );

            if ($request->expectsJson()) {
                abort(403, $message);
            }

            throw ValidationException::withMessages([
                'supervisor_pin' => $message,
            ]);
        }

        $this->ensureHabilitated($supervisor, $request, $operationRouteName, $operationPath);

        ActivityLogger::log(
            'auth.supervisor',
            'Validation superviseur #' . $supervisor->supervisor_number . ' — ' . ActivityLogger::routeLabel($request->route()?->getName(), $request->path()),
            null, null,
            ['supervisor_number' => $supervisor->supervisor_number, 'action' => ActivityLogger::routeLabel($request->route()?->getName(), $request->path())]
        );

        return $supervisor;
    }

    protected function hasPermanentSupervision(Request $request): bool
    {
        return $this->resolvePermanentSupervisor($request) !== null;
    }

    /**
     * Résout le superviseur à l'origine du mode superviseur permanent actif
     * pour l'utilisateur courant (session web ou token mobile), ou null si
     * le mode n'est pas actif / ne peut plus être rattaché à un superviseur
     * valide.
     */
    private function resolvePermanentSupervisor(Request $request): ?Supervisor
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return null;
        }

        $sessionValue = $request->hasSession()
            ? $request->session()->get(self::PERMANENT_SUPERVISION_KEY)
            : null;

        if (is_array($sessionValue) && (int) ($sessionValue['user_id'] ?? 0) === (int) auth()->id()) {
            $supervisor = Supervisor::query()
                ->whereKey((int) ($sessionValue['supervisor_id'] ?? 0))
                ->where('is_active', true)
                ->first();

            if ($supervisor !== null) {
                return $supervisor;
            }
        }

        return $this->resolveMobilePermanentSupervisor($request);
    }

    protected function enablePermanentSupervision(Request $request, Supervisor $supervisor): void
    {
        $request->session()->put(self::PERMANENT_SUPERVISION_KEY, [
            'user_id' => auth()->id(),
            'supervisor_id' => $supervisor->id,
            'enabled_at' => time(),
        ]);
    }

    protected function disablePermanentSupervision(Request $request): void
    {
        $request->session()->forget(self::PERMANENT_SUPERVISION_KEY);
    }

    protected function createMobilePermanentSupervisionToken(Supervisor $supervisor): string
    {
        $token = bin2hex(random_bytes(32));

        Cache::put($this->mobilePermanentSupervisionCacheKey($token), [
            'user_id' => auth()->id(),
            'supervisor_id' => $supervisor->id,
        ], now()->addHours(self::MOBILE_PERMANENT_SUPERVISION_TTL_HOURS));

        return $token;
    }

    protected function disableMobilePermanentSupervision(Request $request): void
    {
        $token = trim((string) $request->header(self::MOBILE_PERMANENT_SUPERVISION_HEADER, ''));

        if ($token !== '') {
            Cache::forget($this->mobilePermanentSupervisionCacheKey($token));
        }
    }

    protected function requireStrictSupervisorValidation(
        Request $request,
        string $message = 'Validation superviseur requise.'
    ): Supervisor {
        $supervisor = $this->validateSupervisorCredentials($request, $message, false);

        if (! $supervisor) {
            throw ValidationException::withMessages([
                'supervisor_pin' => $message,
            ]);
        }

        return $supervisor;
    }

    protected function pendingSupervision(Request $request): ?array
    {
        $pending = $request->session()->get(self::SUPERVISION_PENDING_KEY);

        return is_array($pending) ? $pending : null;
    }

    protected function clearPendingSupervision(Request $request): void
    {
        $request->session()->forget(self::SUPERVISION_PENDING_KEY);
    }

    protected function grantSupervisionBypass(Request $request, Supervisor $supervisor, array $pending): string
    {
        $nonce = (string) Str::uuid();
        $bypasses = $request->session()->get(self::SUPERVISION_BYPASSES_KEY, []);

        if (! is_array($bypasses)) {
            $bypasses = [];
        }

        $bypasses[$nonce] = [
            'supervisor_id' => $supervisor->id,
            'route_name'    => $pending['route_name'] ?? null,
            'path'          => $pending['path'] ?? null,
            'expires_at'    => time() + self::SUPERVISION_BYPASS_TTL_SECONDS,
        ];

        $request->session()->put(self::SUPERVISION_BYPASSES_KEY, $bypasses);

        return $nonce;
    }

    protected function replayPendingSupervision(Request $request, array $pending, array $payload)
    {
        $method = strtoupper((string) ($pending['method'] ?? 'POST'));
        $path = '/' . ltrim((string) ($pending['path'] ?? ''), '/');
        $server = $request->server->all();
        $server['REQUEST_METHOD'] = $method;
        $server['HTTP_REFERER'] = (string) ($pending['referer'] ?? route('employee.dashboard'));

        $replayRequest = Request::create(
            $path,
            $method,
            $payload,
            $request->cookies->all(),
            [],
            $server
        );

        $replayRequest->setLaravelSession($request->session());
        $replayRequest->setUserResolver(fn () => auth()->user());

        return app()->handle($replayRequest);
    }

    private function requestHasSupervisorCredentials(Request $request): bool
    {
        return $request->filled('supervisor_token')
            || $request->filled('supervisor_number')
            || $request->filled('supervisor_username')
            || $request->filled('supervisor_pin')
            || $request->filled('supervisor_password');
    }

    private function hasMobilePermanentSupervision(Request $request): bool
    {
        return $this->resolveMobilePermanentSupervisor($request) !== null;
    }

    private function resolveMobilePermanentSupervisor(Request $request): ?Supervisor
    {
        $token = trim((string) $request->header(self::MOBILE_PERMANENT_SUPERVISION_HEADER, ''));
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $authorization = Cache::get($this->mobilePermanentSupervisionCacheKey($token));
        if (! is_array($authorization) || (int) ($authorization['user_id'] ?? 0) !== (int) auth()->id()) {
            return null;
        }

        return Supervisor::query()
            ->whereKey((int) ($authorization['supervisor_id'] ?? 0))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Vérifie que le superviseur validé possède l'habilitation requise pour
     * l'opération ciblée par la requête (ou par $overrideRouteName /
     * $overridePath, utilisés pour rejouer l'opération d'origine mise en
     * attente — voir SupervisionController::approve()). Aucune restriction
     * n'est appliquée si l'opération n'est pas référencée dans le catalogue
     * App\Support\SupervisorOperation.
     *
     * En cas de refus, l'opération est bloquée (exception ou HTTP 403) et
     * l'échec est journalisé ; l'employé peut réessayer avec un autre
     * superviseur ou abandonner l'action.
     */
    private function ensureHabilitated(
        Supervisor $supervisor,
        Request $request,
        ?string $overrideRouteName = null,
        ?string $overridePath = null
    ): void {
        $routeName = $overrideRouteName ?? $request->route()?->getName();
        $path = $overridePath ?? $request->path();

        $operation = SupervisorOperation::resolve($routeName, $path);

        if ($operation === null || $supervisor->hasHabilitation($operation)) {
            return;
        }

        ActivityLogger::log(
            'auth.supervisor_denied',
            'Bypass refusé — superviseur #' . $supervisor->supervisor_number . ' non habilité pour « ' . SupervisorOperation::label($operation) . ' » — ' . ActivityLogger::routeLabel($routeName, $path),
            null,
            null,
            [
                'supervisor_number' => $supervisor->supervisor_number,
                'operation'         => $operation,
                'action'            => ActivityLogger::routeLabel($routeName, $path),
            ]
        );

        if ($request->expectsJson()) {
            abort(403, self::HABILITATION_DENIED_MESSAGE);
        }

        throw ValidationException::withMessages([
            'supervisor_pin' => self::HABILITATION_DENIED_MESSAGE,
        ]);
    }

    private function mobilePermanentSupervisionCacheKey(string $token): string
    {
        return 'supervision:mobile-permanent:' . hash('sha256', $token);
    }

    private function storePendingSupervision(Request $request, string $message): void
    {
        $payload = $request->except([
            'supervisor_token',
            'supervisor_number',
            'supervisor_username',
            'supervisor_pin',
            'supervisor_password',
            '__supervision_bypass_nonce',
        ]);

        $request->session()->put(self::SUPERVISION_PENDING_KEY, [
            'id'         => (string) Str::uuid(),
            'route_name' => $request->route()?->getName(),
            'method'     => strtoupper($request->method()),
            'path'       => ltrim($request->path(), '/'),
            'referer'    => $request->headers->get('referer'),
            'message'    => $message,
            'input'      => $payload,
            'created_at' => time(),
        ]);
    }

    private function consumeSupervisionBypass(Request $request): ?Supervisor
    {
        $nonce = trim((string) $request->input('__supervision_bypass_nonce', ''));
        if ($nonce === '') {
            return null;
        }

        $bypasses = $request->session()->get(self::SUPERVISION_BYPASSES_KEY, []);
        if (! is_array($bypasses) || ! isset($bypasses[$nonce]) || ! is_array($bypasses[$nonce])) {
            return null;
        }

        $entry = $bypasses[$nonce];
        $expiresAt = (int) ($entry['expires_at'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < time()) {
            unset($bypasses[$nonce]);
            $request->session()->put(self::SUPERVISION_BYPASSES_KEY, $bypasses);

            return null;
        }

        $currentRoute = $request->route()?->getName();
        if (($entry['route_name'] ?? null) !== null && $currentRoute !== $entry['route_name']) {
            return null;
        }

        $currentPath = ltrim($request->path(), '/');
        if (($entry['path'] ?? null) !== null && $currentPath !== $entry['path']) {
            return null;
        }

        unset($bypasses[$nonce]);
        $request->session()->put(self::SUPERVISION_BYPASSES_KEY, $bypasses);

        $supervisor = Supervisor::query()
            ->whereKey((int) ($entry['supervisor_id'] ?? 0))
            ->where('is_active', true)
            ->first();

        if ($supervisor !== null) {
            ActivityLogger::log(
                'auth.supervisor_bypass',
                'Bypass superviseur ponctuel accordé pour ' . ActivityLogger::routeLabel($request->route()?->getName(), $request->path()),
                null,
                null,
                [
                    'supervisor_number' => $supervisor->supervisor_number,
                    'action'            => ActivityLogger::routeLabel($request->route()?->getName(), $request->path()),
                ]
            );
        }

        return $supervisor;
    }
}
