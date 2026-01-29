<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', \App\Http\Middleware\RequirePasswordChange::class])
    ->name('dashboard');

Route::middleware(['auth', 'verified', \App\Http\Middleware\RequirePasswordChange::class])->group(function () {
    Route::get('first-login', [\App\Http\Controllers\Auth\FirstLoginController::class, 'show'])->name('first-login.show');
    Route::post('first-login', [\App\Http\Controllers\Auth\FirstLoginController::class, 'update'])->name('first-login.update');

    Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::resource('questionnaires', \App\Http\Controllers\QuestionnaireController::class);

    // Routes pour les rapports
    Route::get('/rapports', [\App\Http\Controllers\RapportController::class, 'index'])->name('rapports.index');
    Route::get('/rapports/create', [\App\Http\Controllers\RapportController::class, 'create'])->name('rapports.create');
    Route::get('/rapports/create/{questionnaire}', [\App\Http\Controllers\RapportController::class, 'show'])->name('rapports.show');
    Route::post('/rapports', [\App\Http\Controllers\RapportController::class, 'store'])->name('rapports.store');
    Route::get('/rapports/my-reports', [\App\Http\Controllers\RapportController::class, 'myReports'])->name('rapports.my-reports');
    Route::get('/rapports/my-reports/{response}', [\App\Http\Controllers\RapportController::class, 'showMyReport'])->name('rapports.show-my-report');
    Route::get('/rapports/corrections', [\App\Http\Controllers\RapportController::class, 'corrections'])->name('rapports.corrections');
    Route::get('/rapports/corrections/{response}', [\App\Http\Controllers\RapportController::class, 'showCorrection'])->name('rapports.show-correction');
    Route::put('/rapports/corrections/{response}', [\App\Http\Controllers\RapportController::class, 'updateCorrection'])->name('rapports.update-correction');
    Route::get('/rapports/analysis', [\App\Http\Controllers\RapportController::class, 'analysis'])->name('rapports.analysis');
    Route::get('/rapports/analysis/{response}', [\App\Http\Controllers\RapportController::class, 'showAnalysis'])->name('rapports.show-analysis');
    Route::post('/rapports/analysis/{response}/return', [\App\Http\Controllers\RapportController::class, 'returnForCorrection'])->name('rapports.return-for-correction');
    Route::get('/rapports/analysis/export', [\App\Http\Controllers\RapportController::class, 'export'])->name('rapports.export');
});

require __DIR__.'/settings.php';
