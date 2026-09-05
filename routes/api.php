<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DailyReportController;
use App\Http\Controllers\Api\DrinkController;
use App\Http\Controllers\Api\LoyaltyCardController;
use App\Http\Controllers\Api\LoyaltyDiscountController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\SupervisorController;
use App\Http\Controllers\Api\SupervisionController;
use App\Http\Controllers\Api\VoucherController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Mobile — Authentification JWT
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);

    Route::middleware('feature:' . Setting::KEY_FEATURE_QUICK_LOGIN)->group(function () {
        Route::post('/login/qr/identifier', [AuthController::class, 'identifyQr']);
        Route::post('/login/qr', [AuthController::class, 'loginQr']);
    });

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
    Route::patch('/orders/{order}/status',   [OrderController::class, 'updateStatus']);
    Route::post('/orders/{order}/refund',    [OrderController::class, 'refund'])->middleware('feature:' . Setting::KEY_FEATURE_REFUNDS);
    Route::post('/orders/{order}/payments',  [OrderController::class, 'storePayments']);
    Route::delete('/orders/{order}',         [OrderController::class, 'destroy']);

    // Moyens de paiement
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);

    // Récapitulatifs journaliers
    Route::get('/daily-reports',           [DailyReportController::class, 'index']);
    Route::get('/daily-reports/preview',   [DailyReportController::class, 'preview']);
    Route::post('/daily-reports',          [DailyReportController::class, 'store'])->middleware('feature:' . Setting::KEY_FEATURE_DAILY_REPORTS);
    Route::get('/daily-reports/{dailyReport}', [DailyReportController::class, 'show']);

    // Cartes de fidélité
    Route::get('/loyalty-cards',             [LoyaltyCardController::class, 'index']);
    Route::post('/loyalty-cards',            [LoyaltyCardController::class, 'store'])->middleware('feature:' . Setting::KEY_FEATURE_LOYALTY_CARDS);
    Route::post('/loyalty-cards/check',      [LoyaltyCardController::class, 'check']);
    Route::post('/loyalty-cards/verify-pin', [LoyaltyCardController::class, 'verifyPin']);
    Route::get('/loyalty-cards/{card}/offers', [LoyaltyCardController::class, 'offers']);
    Route::post('/loyalty-cards/{card}/offers', [LoyaltyCardController::class, 'storeOffer']);
    Route::put('/loyalty-cards/{card}/offers/{offer}', [LoyaltyCardController::class, 'updateOffer']);
    Route::delete('/loyalty-cards/{card}/offers/{offer}', [LoyaltyCardController::class, 'destroyOffer']);
    Route::get('/loyalty-cards/{card}',      [LoyaltyCardController::class, 'show']);
    Route::post('/loyalty-cards/{card}/adjust', [LoyaltyCardController::class, 'adjust']);

    // Réductions fidélité
    Route::get('/loyalty-discounts', [LoyaltyDiscountController::class, 'index']);
    Route::post('/loyalty-discounts', [LoyaltyDiscountController::class, 'store'])->middleware('feature:' . Setting::KEY_FEATURE_LOYALTY_DISCOUNTS);

    // Bons d'achat
    Route::get('/vouchers/check', [VoucherController::class, 'check']);
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show']);
    Route::post('/vouchers', [VoucherController::class, 'store'])->middleware('feature:' . Setting::KEY_FEATURE_VOUCHERS);
    Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->middleware('feature:' . Setting::KEY_FEATURE_VOUCHERS);
    Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->middleware('feature:' . Setting::KEY_FEATURE_VOUCHERS);

    // Superviseurs (compte connecté)
    Route::get('/supervisors', [SupervisorController::class, 'index']);
    Route::post('/supervisors/{supervisor}/barcode', [SupervisorController::class, 'barcode']);

    // Mode superviseur permanent (super administrateurs)
    Route::get('/supervision/permanent', [SupervisionController::class, 'status']);
    Route::post('/supervision/permanent', [SupervisionController::class, 'enable']);
    Route::delete('/supervision/permanent', [SupervisionController::class, 'disable']);
});
