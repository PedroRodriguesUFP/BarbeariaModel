<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\BarberController;

// ... manter rotas públicas e de autenticação existentes ...

Route::middleware(['auth'])->group(function () {
    // Rotas de transição de estado na marcação (protegidas no controller/policy)
    Route::patch('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])
        ->name('appointments.confirm');
    Route::patch('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])
        ->name('appointments.complete');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
        ->name('appointments.cancel');

    // Área exclusiva do Barbeiro
    Route::middleware(['role:barber'])->prefix('barber')->name('barber.')->group(function () {
        Route::get('/', [BarberController::class, 'dashboard'])->name('dashboard');
        Route::get('/availability', [BarberController::class, 'availability'])->name('availability');
        Route::put('/availability/hours', [BarberController::class, 'updateWorkingHours'])->name('availability.hours');
        Route::post('/availability/blocks', [BarberController::class, 'storeBlock'])->name('availability.blocks.store');
        Route::delete('/availability/blocks/{block}', [BarberController::class, 'destroyBlock'])->name('availability.blocks.destroy');
        Route::get('/services', [BarberController::class, 'services'])->name('services');
        Route::get('/reviews', [BarberController::class, 'reviews'])->name('reviews');
    });
});
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
    Route::middleware('guest')->group(function () {

    Route::get(
        '/esqueci-password',
        [ForgotPasswordController::class, 'showLinkRequestForm']
    )->name('password.request');

    Route::post(
        '/esqueci-password',
        [ForgotPasswordController::class, 'sendResetLinkEmail']
    )->name('password.email');

    Route::get(
        '/repor-password/{token}',
        [ResetPasswordController::class, 'showResetForm']
    )->name('password.reset');

    Route::post(
        '/repor-password',
        [ResetPasswordController::class, 'reset']
    )->name('password.update');
   });
   Route::middleware('guest')->group(function () {

    Route::get(
        '/auth/google',
        [GoogleLoginController::class, 'redirect']
    )->name('auth.google');

    Route::get(
        '/auth/google/callback',
        [GoogleLoginController::class, 'callback']
    )->name('auth.google.callback');

});
