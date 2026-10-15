<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Mostra até 5 serviços na página de boas-vindas.
    $services = Service::orderBy('price')->take(5)->get();

    return view('welcome', compact('services'));
})->name('home');

Route::resource(
    'appointments',
    AppointmentController::class
);

Route::resource(
    'services',
    ServiceController::class
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