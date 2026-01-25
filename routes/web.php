<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', \App\Http\Middleware\RequirePasswordChange::class])->name('dashboard');

Route::middleware(['auth', 'verified', \App\Http\Middleware\RequirePasswordChange::class])->group(function () {
    Route::get('first-login', [\App\Http\Controllers\Auth\FirstLoginController::class, 'show'])->name('first-login.show');
    Route::post('first-login', [\App\Http\Controllers\Auth\FirstLoginController::class, 'update'])->name('first-login.update');

    Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
