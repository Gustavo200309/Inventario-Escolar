<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::view('/login', 'auth.login')->name('login');

Route::get('/dashboard', function () {
    return view('admin.dashboard', [
        'activeMenu' => 'dashboard',
        'pageTitle' => 'Dashboard administrativo',
        'pageSubtitle' => 'Vista general del sistema para administración visual.',
    ]);
})->name('admin.dashboard');
 
Route::view('/personal', 'admin.personal', [
    'activeMenu' => 'personal',
    'pageTitle' => 'Gestión de Personal',
    'pageSubtitle' => 'Administra el personal y sus asignaciones en una vista completamente visual.',
])->name('admin.personal');

Route::view('/bienes', 'admin.bienes')->name('admin.bienes');

Route::view('/areas', 'admin.areas')->name('admin.areas');

Route::view('/asignaciones', 'admin.asignaciones')->name('admin.asignaciones');

Route::view('/historial', 'admin.historial')->name('admin.historial');

Route::view('/reportes', 'admin.reportes')->name('admin.reportes');

Route::view('/pendientes', 'admin.pendientes')->name('admin.pendientes');

Route::view('/configuracion', 'admin.configuracion')->name('admin.configuracion');
