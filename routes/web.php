<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', \App\Http\Middleware\RequirePasswordChange::class])->name('dashboard');

Route::middleware(['auth', 'verified', \App\Http\Middleware\RequirePasswordChange::class])->group(function () {
    Route::get('first-login', [\App\Http\Controllers\Auth\FirstLoginController::class, 'show'])->name('first-login.show');
    Route::post('first-login', [\App\Http\Controllers\Auth\FirstLoginController::class, 'update'])->name('first-login.update');

    Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('questionnaires', \App\Http\Controllers\QuestionnaireController::class);
});

require __DIR__.'/settings.php';
