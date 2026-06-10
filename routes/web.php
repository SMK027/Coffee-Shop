<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Employee\DashboardController;
use App\Http\Controllers\Employee\OrderController;
use App\Http\Controllers\Employee\DrinkController;
use App\Http\Controllers\Employee\TestimonialController;
use App\Http\Controllers\Employee\ContactController;
use App\Http\Controllers\Employee\StatsController;
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

/*
|--------------------------------------------------------------------------
| Interface Employé (authentification requise)
|--------------------------------------------------------------------------
*/
Route::prefix('espace-employe')->name('employee.')->middleware(['auth'])->group(function () {

    Route::get('/tableau-de-bord', [DashboardController::class, 'index'])->name('dashboard');

    // Commandes
    Route::get('/commandes', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/commandes/nouvelle', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/commandes', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/commandes/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/commandes/{order}/statut', [OrderController::class, 'updateStatus'])->name('orders.status');

    // Gestion du menu
    Route::get('/boissons', [DrinkController::class, 'index'])->name('drinks.index');
    Route::get('/boissons/nouvelle', [DrinkController::class, 'create'])->name('drinks.create');
    Route::post('/boissons', [DrinkController::class, 'store'])->name('drinks.store');
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

    // Statistiques
    Route::get('/statistiques', [StatsController::class, 'index'])->name('stats.index');

    // Profil
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
