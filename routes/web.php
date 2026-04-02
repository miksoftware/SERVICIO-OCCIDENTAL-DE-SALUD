<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Consultas - both roles
    Route::get('/consultas/search', [ConsultaController::class, 'search'])->name('consultas.search');
    Route::get('/consultas/files', [ConsultaController::class, 'files'])->name('consultas.files');
    Route::get('/consultas/{consulta}/export', [ConsultaController::class, 'export'])->name('consultas.export');
    Route::get('/consultas/{consulta}', [ConsultaController::class, 'show'])->name('consultas.show');

    // Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/consultas', [ConsultaController::class, 'index'])->name('consultas.index');
        Route::post('/consultas/upload', [ConsultaController::class, 'upload'])->name('consultas.upload');
        Route::post('/consultas/process-batch', [ConsultaController::class, 'processBatch'])->name('consultas.process-batch');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
