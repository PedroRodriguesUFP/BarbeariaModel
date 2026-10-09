<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('appointments', AppointmentController::class);
Route::resource('services', ServiceController::class);
Route::resource('clients', ClientController::class);
Route::resource('barbers', BarberController::class);
Route::resource('payments', PaymentController::class);
Route::resource('reviews', ReviewController::class);