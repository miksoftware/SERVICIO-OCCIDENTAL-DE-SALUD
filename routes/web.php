<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\SosCredentialController;
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
        Route::post('/consultas/{consulta}/retry', [ConsultaController::class, 'retry'])->name('consultas.retry');
        Route::post('/consultas/{consulta}/retry-failed', [ConsultaController::class, 'retryFailed'])->name('consultas.retry-failed');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Credenciales SOS
        Route::get('/sos/credentials', [SosCredentialController::class, 'index'])->name('sos.credentials');
        Route::post('/sos/credentials/save', [SosCredentialController::class, 'save'])->name('sos.credentials.save');
        Route::post('/sos/credentials/test', [SosCredentialController::class, 'test'])->name('sos.credentials.test');
        Route::post('/sos/credentials/logout', [SosCredentialController::class, 'logout'])->name('sos.credentials.logout');
    });
});
