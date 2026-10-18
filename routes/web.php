<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;


// ======================================================
// ROTAS PÚBLICAS
// ======================================================

Route::get('/', function () {
    $services = Service::orderBy('price')
        ->take(5)
        ->get();

    return view('welcome', compact('services'));
})->name('home');


Route::get(
    '/login',
    [LoginController::class, 'showLoginForm']
)->name('login');

Route::post(
    '/login',
    [LoginController::class, 'login']
);

Route::post(
    '/logout',
    [LoginController::class, 'logout']
)->name('logout');


// ======================================================
// SERVIÇOS
// ======================================================

// Administração dos serviços.
// Tem de aparecer antes de /services/{service}.
Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::resource(
        'services',
        ServiceController::class
    )->except([
        'index',
        'show',
    ]);

});


// Consulta pública dos serviços.
Route::resource(
    'services',
    ServiceController::class
)->only([
    'index',
    'show',
]);


// ======================================================
// ROTAS PROTEGIDAS
// ======================================================

Route::middleware('auth')->group(function () {

    Route::resource(
        'appointments',
        AppointmentController::class
    );

    Route::resource(
        'payments',
        PaymentController::class
    );

    Route::resource(
        'reviews',
        ReviewController::class
    );

    Route::get(
        '/checkout/{serviceId}',
        [PaymentController::class, 'checkout']
    )->name('checkout');

});