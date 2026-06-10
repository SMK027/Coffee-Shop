<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmployeePasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EmployeePasswordResetController extends Controller
{
    /**
     * Affiche le formulaire de saisie du nouveau mot de passe.
     */
    public function showForm(string $token)
    {
        $record = EmployeePasswordReset::where('token', hash('sha256', $token))->first();

        if (!$record || !$record->isValid()) {
            return redirect()->route('login')
                ->with('error', 'Ce lien de réinitialisation est invalide ou a expiré. Contactez un super administrateur.');
        }

        return view('auth.employee-reset-password', [
            'token'    => $token,
            'employee' => $record->user,
        ]);
    }

    /**
     * Traite la soumission du nouveau mot de passe.
     */
    public function reset(Request $request, string $token)
    {
        $record = EmployeePasswordReset::where('token', hash('sha256', $token))
            ->with('user')
            ->first();

        if (!$record || !$record->isValid()) {
            return redirect()->route('login')
                ->with('error', 'Ce lien de réinitialisation est invalide ou a expiré. Contactez un super administrateur.');
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        // Mise à jour du mot de passe
        $record->user->update([
            'password' => Hash::make($request->password),
        ]);

        // Invalide le token (usage unique)
        $record->markAsUsed();

        return redirect()->route('login')
            ->with('status', 'Votre mot de passe a été mis à jour. Vous pouvez maintenant vous connecter.');
    }
}
