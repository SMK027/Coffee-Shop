<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrinkController;
use App\Http\Controllers\Api\LoyaltyCardController;
use App\Http\Controllers\Api\LoyaltyDiscountController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Mobile — Authentification JWT
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::post('/logout',  [AuthController::class, 'logout'])->middleware('auth:api');
    Route::get('/me',       [AuthController::class, 'me'])->middleware('auth:api');
});

/*
|--------------------------------------------------------------------------
| Routes protégées (JWT requis)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // Menu / Boissons
    Route::get('/drinks', [DrinkController::class, 'index']);
    Route::patch('/drinks/{drink}/availability', [DrinkController::class, 'toggleAvailability']);

    // Commandes
    Route::get('/orders/statuses', [OrderController::class, 'statuses']);
    Route::get('/orders',          [OrderController::class, 'index']);
    Route::post('/orders',         [OrderController::class, 'store']);
    Route::get('/orders/{order}',  [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund']);

    // Cartes de fidélité
    Route::get('/loyalty-cards',             [LoyaltyCardController::class, 'index']);
    Route::post('/loyalty-cards',            [LoyaltyCardController::class, 'store']);
    Route::post('/loyalty-cards/check',      [LoyaltyCardController::class, 'check']);
    Route::post('/loyalty-cards/verify-pin', [LoyaltyCardController::class, 'verifyPin']);
    Route::get('/loyalty-cards/{card}',      [LoyaltyCardController::class, 'show']);
    Route::post('/loyalty-cards/{card}/adjust', [LoyaltyCardController::class, 'adjust']);

    // Réductions fidélité
    Route::get('/loyalty-discounts', [LoyaltyDiscountController::class, 'index']);
});
