<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\OrderController;
use App\Http\Controllers\Employee\DrinkController;
use App\Http\Controllers\Employee\TestimonialController;
use App\Http\Controllers\Employee\ContactController;
use App\Http\Controllers\Employee\StatsController;
use App\Http\Controllers\Employee\HomeImageController;
use App\Http\Controllers\Employee\UserController;
use App\Http\Controllers\Employee\LoyaltyController as EmployeeLoyaltyController;
use App\Http\Controllers\Employee\LoyaltyDiscountController;
use App\Http\Controllers\Employee\OrderStatusController;
use App\Http\Controllers\Auth\EmployeePasswordResetController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Interface Visiteur (publique)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/menu', [HomeController::class, 'menu'])->name('menu');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', [HomeController::class, 'submitContact'])->name('contact.submit');
Route::post('/temoignages', [HomeController::class, 'submitTestimonial'])->name('testimonial.submit');

// SEO
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

// Carte de fidélité (publique)
Route::get('/fidelite', [LoyaltyController::class, 'create'])->name('loyalty.create');
Route::post('/fidelite', [LoyaltyController::class, 'store'])->name('loyalty.store');
Route::get('/fidelite/mes-points', [LoyaltyController::class, 'showBalanceForm'])->name('loyalty.balance.form');
Route::post('/fidelite/mes-points', [LoyaltyController::class, 'balance'])->name('loyalty.balance');
Route::get('/fidelite/mes-points/commande/{order}', [LoyaltyController::class, 'showOrder'])->name('loyalty.balance.order.show');
Route::get('/fidelite/reinitialiser-pin/{token}', [LoyaltyController::class, 'showPinResetForm'])->name('loyalty.pin.form');
Route::post('/fidelite/reinitialiser-pin/{token}', [LoyaltyController::class, 'resetPin'])->name('loyalty.pin.reset');

/*
|--------------------------------------------------------------------------
| Interface Employé (authentification requise)
|--------------------------------------------------------------------------
*/
Route::prefix('espace-employe')->name('employee.')->middleware(['auth', 'employee'])->group(function () {

    Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

    // Commandes
    Route::get('/commandes', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/commandes/identification', [OrderController::class, 'identify'])->name('orders.identify');
    Route::post('/commandes/identification', [OrderController::class, 'storeIdentification'])->name('orders.identify.store');
    Route::get('/commandes/nouvelle', [OrderController::class, 'create'])->name('orders.create');
    Route::get('/commandes/verification-carte-fidelite', [OrderController::class, 'checkLoyaltyCard'])->name('orders.loyalty-check');
    Route::get('/commandes/recherche-carte-fidelite', [OrderController::class, 'searchLoyaltyCard'])->name('orders.loyalty-search');
    Route::post('/commandes/verification-pin-carte', [OrderController::class, 'verifyCardPin'])->name('orders.pin-verify');
    Route::post('/commandes', [OrderController::class, 'store'])->name('orders.store');

    // Statuts de commande (lecture seule admin ; CRUD super admin)
    Route::get('/commandes/statuts', [OrderStatusController::class, 'index'])->name('order-statuses.index');
    Route::get('/commandes/statuts/nouveau', [OrderStatusController::class, 'create'])->name('order-statuses.create');
    Route::post('/commandes/statuts', [OrderStatusController::class, 'store'])->name('order-statuses.store');
    Route::get('/commandes/statuts/{orderStatus}/modifier', [OrderStatusController::class, 'edit'])->name('order-statuses.edit');
    Route::put('/commandes/statuts/{orderStatus}', [OrderStatusController::class, 'update'])->name('order-statuses.update');
    Route::patch('/commandes/statuts/{orderStatus}/activation', [OrderStatusController::class, 'toggleActive'])->name('order-statuses.toggle');
    Route::delete('/commandes/statuts/{orderStatus}', [OrderStatusController::class, 'destroy'])->name('order-statuses.destroy');

    Route::get('/commandes/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/commandes/{order}/statut', [OrderController::class, 'updateStatus'])->name('orders.status');

    // Gestion du menu
    Route::get('/boissons', [DrinkController::class, 'index'])->name('drinks.index');
    Route::get('/boissons/nouvelle', [DrinkController::class, 'create'])->name('drinks.create');
    Route::post('/boissons', [DrinkController::class, 'store'])->name('drinks.store');
    Route::post('/boissons/desactivation-masse', [DrinkController::class, 'bulkDisable'])->name('drinks.bulk-disable');
    Route::post('/boissons/reactivation-masse', [DrinkController::class, 'bulkEnable'])->name('drinks.bulk-enable');
    Route::get('/boissons/{drink}/modifier', [DrinkController::class, 'edit'])->name('drinks.edit');
    Route::put('/boissons/{drink}', [DrinkController::class, 'update'])->name('drinks.update');
    Route::delete('/boissons/{drink}', [DrinkController::class, 'destroy'])->name('drinks.destroy');
    Route::patch('/boissons/{drink}/disponibilite', [DrinkController::class, 'toggleAvailability'])->name('drinks.toggle');

    // Témoignages
    Route::get('/temoignages', [TestimonialController::class, 'index'])->name('testimonials.index');
    Route::patch('/temoignages/{testimonial}/publier', [TestimonialController::class, 'publish'])->name('testimonials.publish');
    Route::patch('/temoignages/{testimonial}/rejeter', [TestimonialController::class, 'reject'])->name('testimonials.reject');
    Route::delete('/temoignages/{testimonial}', [TestimonialController::class, 'destroy'])->name('testimonials.destroy');

    // Contacts
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::post('/contacts/{contact}/repondre', [ContactController::class, 'reply'])->name('contacts.reply');
    Route::patch('/contacts/{contact}/archiver', [ContactController::class, 'archive'])->name('contacts.archive');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

    // Statistiques
    Route::get('/statistiques', [StatsController::class, 'index'])->name('stats.index');

    // Images page d'accueil
    Route::get('/accueil/images', [HomeImageController::class, 'index'])->name('home-images.index');
    Route::post('/accueil/images/{key}', [HomeImageController::class, 'update'])->name('home-images.update');
    Route::delete('/accueil/images/{key}', [HomeImageController::class, 'destroy'])->name('home-images.destroy');

    // Fidélité
    Route::get('/fidelite', [EmployeeLoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('/fidelite/reglages', [EmployeeLoyaltyController::class, 'settings'])->name('loyalty.settings');
    Route::patch('/fidelite/reglages', [EmployeeLoyaltyController::class, 'updateSettings'])->name('loyalty.settings.update');
    Route::get('/fidelite/employes/recherche', [EmployeeLoyaltyController::class, 'searchEmployees'])->name('loyalty.employees.search');
    Route::post('/fidelite/{loyaltyCard}/reset-pin', [EmployeeLoyaltyController::class, 'sendPinReset'])->name('loyalty.pin.send');
    Route::patch('/fidelite/{loyaltyCard}/avantages-salaries', [EmployeeLoyaltyController::class, 'updateEmployeeBenefits'])->name('loyalty.benefits.update');
    Route::patch('/fidelite/{loyaltyCard}/titulaire', [EmployeeLoyaltyController::class, 'updateHolder'])->name('loyalty.holder.update');
    Route::post('/fidelite/{loyaltyCard}/ajuster-points', [EmployeeLoyaltyController::class, 'adjustPoints'])->name('loyalty.points.adjust');
    Route::get('/fidelite/reductions', [LoyaltyDiscountController::class, 'index'])->name('loyalty-discounts.index');
    Route::get('/fidelite/reductions/nouvelle', [LoyaltyDiscountController::class, 'create'])->name('loyalty-discounts.create');
    Route::post('/fidelite/reductions', [LoyaltyDiscountController::class, 'store'])->name('loyalty-discounts.store');
    Route::get('/fidelite/reductions/{loyaltyDiscount}/modifier', [LoyaltyDiscountController::class, 'edit'])->name('loyalty-discounts.edit');
    Route::put('/fidelite/reductions/{loyaltyDiscount}', [LoyaltyDiscountController::class, 'update'])->name('loyalty-discounts.update');
    Route::delete('/fidelite/reductions/{loyaltyDiscount}', [LoyaltyDiscountController::class, 'destroy'])->name('loyalty-discounts.destroy');
    Route::get('/fidelite/{loyaltyCard}', [EmployeeLoyaltyController::class, 'show'])->name('loyalty.show');

    // Gestion des employés (admin uniquement)
    Route::get('/employes', [UserController::class, 'index'])->name('users.index');
    Route::get('/employes/nouveau', [UserController::class, 'create'])->name('users.create');
    Route::post('/employes', [UserController::class, 'store'])->name('users.store');
    Route::get('/employes/{user}/modifier', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/employes/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/employes/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/employes/{user}/reset-mdp', [UserController::class, 'sendResetLink'])->name('users.reset-link');

    // Profil
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Réinitialisation de mot de passe employé (lien email, public)
|--------------------------------------------------------------------------
*/
Route::get('/reset-employe/{token}', [EmployeePasswordResetController::class, 'showForm'])
    ->name('employee.password.form');
Route::post('/reset-employe/{token}', [EmployeePasswordResetController::class, 'reset'])
    ->name('employee.password.reset');
