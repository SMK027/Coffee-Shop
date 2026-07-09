<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    protected function requireSuperAdminOrSupervisor(Request $request, string $message = 'Accès réservé aux super administrateurs.'): void
    {
        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        $payload = [
            'supervisor_number' => $request->input('supervisor_number', $request->input('supervisor_username')),
            'supervisor_pin'    => $request->input('supervisor_pin', $request->input('supervisor_password')),
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

        if (! $supervisor || ! Hash::check($validated['supervisor_pin'], $supervisor->password)) {
            abort(403, $message);
        }
    }
}
