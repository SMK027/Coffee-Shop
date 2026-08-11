<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function requireSuperAdminOrSupervisor(Request $request, string $message = 'Numéro de superviseur ou PIN incorrect.'): ?Supervisor
    {
        if (auth()->user()->isSuperAdmin()) {
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
}
