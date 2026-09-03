<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Services\ActivityLogger;
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

    protected function requireSuperAdminOrSupervisor(Request $request, string $message = 'Numéro de superviseur ou PIN incorrect.'): ?Supervisor
    {
        if ($this->hasPermanentSupervision($request)) {
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
        bool $allowSuperAdminBypass = false
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

                if ($supervisor) {
                    $payload = $supervisor->supervisor_number . '|' . $supervisor->password;
                    $expected = substr(hash_hmac('sha256', $payload, (string) config('app.key')), 0, 20);

                    if (hash_equals($expected, $signature)) {
                        ActivityLogger::log(
                            'auth.supervisor',
                            'Validation superviseur #' . $supervisor->supervisor_number . ' (token court) — ' . ActivityLogger::routeLabel($request->route()?->getName()),
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

                $valid = $supervisor && hash_equals($supervisor->password, $passwordHash);
                if ($valid) {
                    ActivityLogger::log(
                        'auth.supervisor',
                        'Validation superviseur #' . $supervisor->supervisor_number . ' (token) — ' . ActivityLogger::routeLabel($request->route()?->getName()),
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

        $valid = $supervisor && Hash::check($validated['supervisor_pin'], $supervisor->password);

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

        ActivityLogger::log(
            'auth.supervisor',
            'Validation superviseur #' . $supervisor->supervisor_number . ' — ' . ActivityLogger::routeLabel($request->route()?->getName()),
            null, null,
            ['supervisor_number' => $supervisor->supervisor_number, 'action' => ActivityLogger::routeLabel($request->route()?->getName(), $request->path())]
        );

        return $supervisor;
    }

    protected function hasPermanentSupervision(Request $request): bool
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return false;
        }

        $sessionValue = $request->hasSession()
            ? $request->session()->get(self::PERMANENT_SUPERVISION_KEY)
            : null;

        if (is_array($sessionValue) && (int) ($sessionValue['user_id'] ?? 0) === (int) auth()->id()) {
            return true;
        }

        return $this->hasMobilePermanentSupervision($request);
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
        $token = trim((string) $request->header(self::MOBILE_PERMANENT_SUPERVISION_HEADER, ''));
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $authorization = Cache::get($this->mobilePermanentSupervisionCacheKey($token));
        if (! is_array($authorization) || (int) ($authorization['user_id'] ?? 0) !== (int) auth()->id()) {
            return false;
        }

        return Supervisor::query()
            ->whereKey((int) ($authorization['supervisor_id'] ?? 0))
            ->where('is_active', true)
            ->exists();
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
