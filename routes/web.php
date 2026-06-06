<?php

use App\Http\Controllers\CasosUso\CU02AdministrarUsuario\ControladorUsuario;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'administrador'])
    ->prefix('usuarios')
    ->name('cu02.usuarios.')
    ->group(function () {
        Route::get('/', [ControladorUsuario::class, 'index'])->name('index');
        Route::get('crear', [ControladorUsuario::class, 'create'])->name('create');
        Route::post('/', [ControladorUsuario::class, 'store'])->name('store');
        Route::get('{usuario}/editar', [ControladorUsuario::class, 'edit'])->name('edit');
        Route::put('{usuario}', [ControladorUsuario::class, 'update'])->name('update');
        Route::put('{usuario}/contrasena', [ControladorUsuario::class, 'updatePassword'])->name('password.update');
        Route::patch('{usuario}/estado', [ControladorUsuario::class, 'updateStatus'])->name('status.update');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
