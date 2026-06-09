<?php

use App\Http\Controllers\CasosUso\CU02AdministrarUsuario\ControladorUsuario;
use App\Http\Controllers\CasosUso\CU03ConfigurarGestionCUP\ControladorGestionCUP;
use App\Http\Controllers\CasosUso\CU04GestionarEtapasGestionCUP\ControladorEtapasGestion;
use App\Http\Controllers\CasosUso\CU05GestionarDocente\ControladorDocente;
use App\Http\Controllers\CasosUso\CU06RealizarInscripcion\ControladorInscripcion;
use App\Http\Controllers\CasosUso\CU06RealizarInscripcion\ControladorWebhookStripe;
use App\Http\Controllers\CasosUso\CU07GestionarPostulantesInscripciones\ControladorPostulanteInscripcion;
use App\Http\Controllers\CasosUso\CU08PlanificarGrupos\ControladorPlanificacionGrupos;
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

Route::prefix('inscripcion')
    ->name('cu06.inscripcion.')
    ->group(function () {
        Route::get('/', [ControladorInscripcion::class, 'create'])->name('create');
        Route::post('/', [ControladorInscripcion::class, 'store'])->name('store');
        Route::get('pago/exito', [ControladorInscripcion::class, 'paymentSuccess'])->name('pago.exito');
        Route::get('pago/cancelado', [ControladorInscripcion::class, 'paymentCanceled'])->name('pago.cancelado');
        Route::get('{inscripcion}/resultado', [ControladorInscripcion::class, 'result'])->name('resultado');
    });

Route::post('stripe/webhook', ControladorWebhookStripe::class)->name('stripe.webhook');

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

Route::middleware(['auth', 'configurador.gestion-cup'])
    ->prefix('gestion-cup')
    ->name('cu03.gestion-cup.')
    ->group(function () {
        Route::get('/', [ControladorGestionCUP::class, 'index'])->name('index');
        Route::get('crear', [ControladorGestionCUP::class, 'create'])->name('create');
        Route::post('/', [ControladorGestionCUP::class, 'store'])->name('store');
        Route::get('{gestionCup}/editar', [ControladorGestionCUP::class, 'edit'])->name('edit');
        Route::put('{gestionCup}', [ControladorGestionCUP::class, 'update'])->name('update');
    });

Route::middleware(['auth', 'configurador.gestion-cup'])
    ->prefix('etapas-gestion-cup')
    ->name('cu04.etapas-cup.')
    ->group(function () {
        Route::get('/', [ControladorEtapasGestion::class, 'index'])->name('index');
        Route::get('{gestionCup}', [ControladorEtapasGestion::class, 'edit'])->name('edit');
        Route::put('{gestionCup}', [ControladorEtapasGestion::class, 'update'])->name('update');
        Route::patch('{etapaGestionCup}/activar', [ControladorEtapasGestion::class, 'activate'])->name('activate');
        Route::patch('{etapaGestionCup}/cerrar', [ControladorEtapasGestion::class, 'close'])->name('close');
        Route::patch('{etapaGestionCup}/reabrir', [ControladorEtapasGestion::class, 'reopen'])->name('reopen');
    });

Route::middleware(['auth', 'configurador.gestion-cup'])
    ->prefix('docentes')
    ->name('cu05.docentes.')
    ->group(function () {
        Route::get('/', [ControladorDocente::class, 'index'])->name('index');
        Route::get('crear', [ControladorDocente::class, 'create'])->name('create');
        Route::post('/', [ControladorDocente::class, 'store'])->name('store');
        Route::get('{docente}/editar', [ControladorDocente::class, 'edit'])->name('edit');
        Route::put('{docente}', [ControladorDocente::class, 'update'])->name('update');
        Route::patch('{docente}/estado', [ControladorDocente::class, 'updateStatus'])->name('status.update');
    });

Route::middleware(['auth', 'configurador.gestion-cup'])
    ->prefix('postulantes-inscripciones')
    ->name('cu07.postulantes-inscripciones.')
    ->group(function () {
        Route::get('/', [ControladorPostulanteInscripcion::class, 'index'])->name('index');
        Route::get('{inscripcion}', [ControladorPostulanteInscripcion::class, 'show'])->name('show');
        Route::patch('{inscripcion}/estado', [ControladorPostulanteInscripcion::class, 'updateStatus'])->name('status.update');
    });

Route::middleware(['auth', 'configurador.gestion-cup'])
    ->prefix('planificacion-grupos')
    ->name('cu08.planificacion-grupos.')
    ->group(function () {
        Route::get('/', [ControladorPlanificacionGrupos::class, 'index'])->name('index');
        Route::post('generar', [ControladorPlanificacionGrupos::class, 'generate'])->name('generate');
        Route::post('generar-horarios', [ControladorPlanificacionGrupos::class, 'generateSchedule'])->name('schedule.generate');
        Route::post('asignaciones', [ControladorPlanificacionGrupos::class, 'storeAssignment'])->name('assignments.store');
        Route::post('publicar', [ControladorPlanificacionGrupos::class, 'publish'])->name('publish');
    });

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
