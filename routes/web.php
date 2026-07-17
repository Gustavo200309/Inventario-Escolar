<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\PendientesController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\ReportesController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLogin']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/b/{codigo}', [BienController::class, 'detallePublico'])->name('bien.detalle-corto');
Route::get('/consulta-bien/{codigo}', [BienController::class, 'detallePublico'])->name('bien.detalle-publico');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/personal', [PersonalController::class, 'index'])->name('admin.personal');
    Route::middleware('admin.only')->group(function () {
        Route::post('/personal', [PersonalController::class, 'store'])->name('admin.personal.store');
        Route::put('/personal/{personal}', [PersonalController::class, 'update'])->name('admin.personal.update');
        Route::delete('/personal/{personal}', [PersonalController::class, 'destroy'])->name('admin.personal.destroy');
    });

    Route::get('/bienes', [BienController::class, 'index'])->name('admin.bienes');
    Route::get('/bienes/papelera', [BienController::class, 'papelera'])->name('admin.bienes.papelera');
    Route::get('/bienes/barcodes/download', [BienController::class, 'downloadBarcodes'])->name('admin.bienes.barcodes');
    Route::get('/bienes/barcodes/json', [BienController::class, 'barcodesJson'])->name('admin.bienes.barcodes-json');
    Route::get('/bienes/importar/plantilla', [BienController::class, 'downloadTemplate'])->name('admin.bienes.template');
    Route::middleware('admin.only')->group(function () {
        Route::post('/bienes', [BienController::class, 'store'])->name('admin.bienes.store');
        Route::put('/bienes/{bien}', [BienController::class, 'update'])->name('admin.bienes.update');
        Route::delete('/bienes/{bien}', [BienController::class, 'destroy'])->name('admin.bienes.destroy');
        Route::put('/bienes/{bien}/restaurar', [BienController::class, 'restaurar'])->name('admin.bienes.restaurar');
        Route::delete('/bienes/{bien}/eliminar-permanente', [BienController::class, 'forceDestroy'])->name('admin.bienes.force-destroy');
        Route::post('/bienes/eliminar-todos', [BienController::class, 'destroyAll'])->name('admin.bienes.destroy-all');
        Route::post('/bienes/restaurar-todos', [BienController::class, 'restoreAll'])->name('admin.bienes.restore-all');
        Route::post('/bienes/eliminar-permanente-todos', [BienController::class, 'forceDestroyAll'])->name('admin.bienes.force-destroy-all');
        Route::post('/bienes/importar', [BienController::class, 'importExcel'])->name('admin.bienes.import');
        Route::post('/bienes/bulk-delete', [BienController::class, 'bulkDestroy'])->name('admin.bienes.bulk-delete');
        Route::post('/bienes/restaurar-masivo', [BienController::class, 'bulkRestore'])->name('admin.bienes.bulk-restore');
        Route::post('/bienes/eliminar-permanente-masivo', [BienController::class, 'bulkForceDestroy'])->name('admin.bienes.bulk-force-destroy');
    });

    Route::get('/areas', [AreaController::class, 'index'])->name('admin.areas');
    Route::middleware('admin.only')->group(function () {
        Route::post('/areas', [AreaController::class, 'store'])->name('admin.areas.store');
        Route::put('/areas/{area}', [AreaController::class, 'update'])->name('admin.areas.update');
        Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('admin.areas.destroy');
    });

    Route::middleware('admin.only')->group(function () {
        Route::post('/marcas', [MarcaController::class, 'store'])->name('admin.marcas.store');
    });

    Route::get('/asignaciones', [AsignacionController::class, 'index'])->name('admin.asignaciones');
    Route::middleware('admin.only')->group(function () {
        Route::post('/asignaciones', [AsignacionController::class, 'store'])->name('admin.asignaciones.store');
        Route::put('/asignaciones/{bien}', [AsignacionController::class, 'update'])->name('admin.asignaciones.update');
    });

    Route::get('/historial', [HistorialController::class, 'index'])->name('admin.historial');
    Route::get('/historial/export/{format}', [HistorialController::class, 'export'])->name('admin.historial.export');
    Route::middleware('admin.only')->group(function () {
        Route::post('/historial', [HistorialController::class, 'store'])->name('admin.historial.store');
    });

    Route::get('/reportes', [ReportesController::class, 'index'])->name('admin.reportes');
    Route::get('/reportes/export/{format}', [ReportesController::class, 'export'])->name('admin.reportes.export');

    Route::get('/pendientes', [PendientesController::class, 'index'])->name('admin.pendientes');
    Route::middleware('admin.only')->group(function () {
        Route::put('/pendientes/{bien}/resolver', [PendientesController::class, 'resolver'])->name('admin.pendientes.resolver');
    });

    Route::get('/usuarios', [ConfiguracionController::class, 'index'])->name('admin.usuarios');
    Route::middleware('admin.only')->group(function () {
        Route::post('/usuarios', [ConfiguracionController::class, 'store'])->name('admin.usuarios.store');
        Route::delete('/usuarios/{usuario}', [ConfiguracionController::class, 'destroy'])->name('admin.usuarios.destroy');
    });
});
