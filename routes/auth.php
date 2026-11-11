<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Registo público: cria sempre um cliente. O throttle trava criação em massa de contas.
    Route::get('/registar', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('/registar', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/esqueci-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('/esqueci-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('/repor-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('/repor-password', [NewPasswordController::class, 'store'])
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Email verificado com sucesso.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('appointments.index'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->middleware('verified')
        ->name('password.confirm');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('/password', [PasswordController::class, 'update'])
        ->name('profile.password.update');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});
