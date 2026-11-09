<?php

use App\Http\Controllers\Admin\BarberController as AdminBarberController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\BarberController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

// ======================================================
// PÚBLICO
// ======================================================

Route::get('/', function () {
    $services = Service::orderBy('price')->take(5)->get();

    return view('welcome', compact('services'));
})->name('home');

// Consulta pública dos serviços.
// A administração (create/edit/...) está mais abaixo e tem de vir antes de /services/{service}.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('services', ServiceController::class)
        ->except(['index', 'show']);
});

Route::resource('services', ServiceController::class)
    ->only(['index', 'show']);

// ======================================================
// VISITANTES (guest)
// ======================================================

Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleLoginController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])->name('auth.google.callback');
});

// AUTENTICADO (qualquer role)

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route(auth()->user()->homeRoute());
    })->name('dashboard');

    // Marcações (a policy decide quem pode o quê)
    Route::patch('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::patch('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::resource('appointments', AppointmentController::class);

    Route::post('/payments/{appointment}/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

    Route::resource('payments', PaymentController::class);
    Route::resource('reviews', ReviewController::class);

    // ---------------- BARBEIRO ----------------
    Route::middleware('role:barber')->group(function () {
        Route::get('/calendar', [CalendarController::class, 'mine'])->name('calendar.mine');
        Route::get('/calendar/appointments/create', [CalendarController::class, 'create'])->name('calendar.appointments.create');
        Route::post('/calendar/appointments', [CalendarController::class, 'store'])->name('calendar.appointments.store');

        Route::prefix('barber')->name('barber.')->group(function () {
            Route::get('/', [BarberController::class, 'dashboard'])->name('dashboard');
            Route::get('/availability', [BarberController::class, 'availability'])->name('availability');
            Route::put('/availability/hours', [BarberController::class, 'updateWorkingHours'])->name('availability.hours');
            Route::post('/availability/blocks', [BarberController::class, 'storeBlock'])->name('availability.blocks.store');
            Route::delete('/availability/blocks/{block}', [BarberController::class, 'destroyBlock'])->name('availability.blocks.destroy');
            Route::get('/services', [BarberController::class, 'services'])->name('services');
            Route::get('/reviews', [BarberController::class, 'reviews'])->name('reviews');
        });
    });

    // ---------------- ADMIN ----------------
    Route::middleware('role:admin')->group(function () {
        Route::get('/barbers', [CalendarController::class, 'barbers'])->name('barbers.index');
        Route::get('/barbers/{barber}/calendar', [CalendarController::class, 'show'])->name('barbers.calendar');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::resource('barbers', AdminBarberController::class)->except(['show']);

            Route::resource('users', AdminUserController::class)
                ->only(['index', 'create', 'store', 'update', 'destroy']);
        });
    });
});

require __DIR__.'/auth.php';
