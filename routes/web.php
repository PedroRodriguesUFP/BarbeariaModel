<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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