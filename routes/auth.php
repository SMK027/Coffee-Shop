<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\QrLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SecurityKeyLoginController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\SupervisorSecurityKeyController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // L'inscription publique est désactivée : les comptes sont créés par les admins
    // depuis l'espace employé (/espace-employe/employes).

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::middleware('feature:' . Setting::KEY_FEATURE_QUICK_LOGIN)->group(function () {
        Route::get('login/qr', [QrLoginController::class, 'show'])
            ->name('login.qr');

        Route::post('login/qr/identifier', [QrLoginController::class, 'identify'])
            ->name('login.qr.identify');

        Route::post('login/qr', [QrLoginController::class, 'store'])
            ->name('login.qr.store');
    });

    Route::middleware('feature:' . Setting::KEY_FEATURE_SECURITY_KEYS)->group(function () {
        Route::get('login/cle-de-securite', [SecurityKeyLoginController::class, 'show'])
            ->name('login.security-key');

        Route::post('login/cle-de-securite/options', [SecurityKeyLoginController::class, 'options'])
            ->name('login.security-key.options');

        Route::post('login/cle-de-securite', [SecurityKeyLoginController::class, 'store'])
            ->name('login.security-key.store');
    });

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// Défi WebAuthn d'approbation superviseur par clé de sécurité : utilisé aussi
// bien avant authentification (ex. connexion par clé de sécurité) que depuis
// l'espace employé, partout où une validation superviseur est déjà exigée.
Route::middleware('feature:' . Setting::KEY_FEATURE_SECURITY_KEYS)->group(function () {
    Route::post('supervision/cle-de-securite/options', [SupervisorSecurityKeyController::class, 'options'])
        ->name('supervisor-security-key.options');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
