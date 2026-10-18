<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;


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

Route::middleware('auth')->post(
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

    // ======================================================
// CALENDÁRIO DO BARBEIRO
// ======================================================

Route::middleware(['auth', 'role:barber'])->group(function () {

    Route::get('/calendar', [CalendarController::class, 'mine'])->name('calendar.mine');
    Route::get('/calendar/appointments/create', [CalendarController::class, 'create'])->name('calendar.appointments.create');
    Route::post('/calendar/appointments', [CalendarController::class, 'store'])->name('calendar.appointments.store');

});

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/barbers', [CalendarController::class, 'barbers'])->name('barbers.index');
    Route::get('/barbers/{barber}/calendar', [CalendarController::class, 'show'])->name('barbers.calendar');

});

});