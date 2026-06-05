<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\PendientesController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ReportesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Rutas de Personal
    Route::get('/personal', [PersonalController::class, 'index'])->name('admin.personal');
    Route::get('/personal/{personal}', [PersonalController::class, 'show'])->name('admin.personal.show');
    
    Route::middleware('admin.only')->group(function () {
        Route::post('/personal', [PersonalController::class, 'store'])->name('admin.personal.store');
        Route::get('/personal/create', [PersonalController::class, 'create'])->name('admin.personal.create');
        Route::get('/personal/{personal}/edit', [PersonalController::class, 'edit'])->name('admin.personal.edit');
        Route::put('/personal/{personal}', [PersonalController::class, 'update'])->name('admin.personal.update');
        Route::delete('/personal/{personal}', [PersonalController::class, 'destroy'])->name('admin.personal.destroy');
    });

    // Rutas de Bienes
    Route::get('/bienes', [BienController::class, 'index'])->name('admin.bienes');
    Route::get('/bienes/{bien}', [BienController::class, 'show'])->name('admin.bienes.show');
    
    Route::middleware('admin.only')->group(function () {
        Route::post('/bienes', [BienController::class, 'store'])->name('admin.bienes.store');
        Route::get('/bienes/create', [BienController::class, 'create'])->name('admin.bienes.create');
        Route::get('/bienes/{bien}/edit', [BienController::class, 'edit'])->name('admin.bienes.edit');
        Route::put('/bienes/{bien}', [BienController::class, 'update'])->name('admin.bienes.update');
        Route::delete('/bienes/{bien}', [BienController::class, 'destroy'])->name('admin.bienes.destroy');
    });

    // Rutas de Áreas
    Route::get('/areas', [AreaController::class, 'index'])->name('admin.areas');
    Route::get('/areas/{area}', [AreaController::class, 'show'])->name('admin.areas.show');
    
    Route::middleware('admin.only')->group(function () {
        Route::post('/areas', [AreaController::class, 'store'])->name('admin.areas.store');
        Route::get('/areas/create', [AreaController::class, 'create'])->name('admin.areas.create');
        Route::get('/areas/{area}/edit', [AreaController::class, 'edit'])->name('admin.areas.edit');
        Route::put('/areas/{area}', [AreaController::class, 'update'])->name('admin.areas.update');
        Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('admin.areas.destroy');
    });

    // Rutas de Asignaciones
    Route::get('/asignaciones', [AsignacionController::class, 'index'])->name('admin.asignaciones');
    
    Route::middleware('admin.only')->group(function () {
        Route::post('/asignaciones', [AsignacionController::class, 'store'])->name('admin.asignaciones.store');
        Route::get('/asignaciones/create', [AsignacionController::class, 'create'])->name('admin.asignaciones.create');
        Route::put('/asignaciones/{bien}', [AsignacionController::class, 'update'])->name('admin.asignaciones.update');
    });

    // Rutas de Historial
    Route::get('/historial', [HistorialController::class, 'index'])->name('admin.historial');
    
    Route::middleware('admin.only')->group(function () {
        Route::post('/historial', [HistorialController::class, 'store'])->name('admin.historial.store');
        Route::get('/historial/create', [HistorialController::class, 'create'])->name('admin.historial.create');
    });

    // Rutas de Reportes
    Route::get('/reportes', [ReportesController::class, 'index'])->name('admin.reportes');
    Route::get('/reportes/export/{format}', [ReportesController::class, 'export'])->name('admin.reportes.export');

    // Rutas de Pendientes
    Route::get('/pendientes', [PendientesController::class, 'index'])->name('admin.pendientes');

    // Rutas de Configuración
    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('admin.configuracion');

    Route::middleware('admin.only')->group(function () {
        Route::post('/configuracion', [ConfiguracionController::class, 'store'])->name('admin.configuracion.store');
        Route::delete('/configuracion/{usuario}', [ConfiguracionController::class, 'destroy'])->name('admin.configuracion.destroy');
    });
});
