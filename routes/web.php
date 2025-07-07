<?php

use Illuminate\Support\Facades\Route;
use App\Infrastructure\Http\Controllers\Auth\LoginController;
use App\Infrastructure\Http\Controllers\WeeklyPresentationController;
use App\Infrastructure\Http\Controllers\AuditController;
use App\Infrastructure\Http\Controllers\MonthlyPresentationController;
use App\Infrastructure\Http\Controllers\DashboardController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Sistema de Auditoría
    Route::get('/audit/logs', [AuditController::class, 'logs'])->name('audit.logs');

    // Presentaciones Semanales
    Route::prefix('weekly-presentations')->name('weekly-presentations.')->group(function () {
        Route::get('/new', [WeeklyPresentationController::class, 'create'])->name('create');
        Route::post('/process-excel', [WeeklyPresentationController::class, 'processExcel'])->name('process-excel');
        Route::post('/generate-json', [WeeklyPresentationController::class, 'generateJson'])->name('generate-json');
        Route::post('/import', [WeeklyPresentationController::class, 'import'])->name('import');
        Route::get('/list', [WeeklyPresentationController::class, 'index'])->name('index');
        Route::post('/save-draft', [WeeklyPresentationController::class, 'saveDraft'])->name('save-draft');
        Route::get('/{presentation}', [WeeklyPresentationController::class, 'show'])->name('show');
        Route::post('/{presentation}/confirm', [WeeklyPresentationController::class, 'confirm'])->name('confirm');
        Route::post('/{presentation}/rectify', [WeeklyPresentationController::class, 'rectify'])->name('rectify');
        Route::get('/{presentation}/download-json', [WeeklyPresentationController::class, 'downloadJson'])->name('download-json');
        Route::get('/{presentation}/download-excel', [WeeklyPresentationController::class, 'downloadExcel'])->name('download-excel');
    });

    // Presentaciones Mensuales
    Route::prefix('monthly-presentations')->name('monthly-presentations.')->group(function () {
        Route::get('/new', [MonthlyPresentationController::class, 'create'])->name('create');
        Route::post('/process-excel', [MonthlyPresentationController::class, 'processExcel'])->name('process-excel');
        Route::post('/generate-json', [MonthlyPresentationController::class, 'generateJson'])->name('generate-json');
        Route::post('/import', [MonthlyPresentationController::class, 'import'])->name('import');
        Route::get('/list', [MonthlyPresentationController::class, 'index'])->name('index');
        Route::post('/save-draft', [MonthlyPresentationController::class, 'saveDraft'])->name('save-draft');
        Route::get('/{presentation}', [MonthlyPresentationController::class, 'show'])->name('show');
        Route::post('/{presentation}/confirm', [MonthlyPresentationController::class, 'confirm'])->name('confirm');
        Route::post('/{presentation}/rectify', [MonthlyPresentationController::class, 'rectify'])->name('rectify');
        Route::post('/{presentation}/send-ssn', [MonthlyPresentationController::class, 'sendSsn'])->name('send-ssn');
        Route::get('/{presentation}/download-json', [MonthlyPresentationController::class, 'downloadJson'])->name('download-json');
        Route::get('/{presentation}/download-excel', [MonthlyPresentationController::class, 'downloadExcel'])->name('download-excel');
    });
});
