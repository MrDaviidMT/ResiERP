<?php

// Rutas web de la aplicación

use App\Enums\Permission as P;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DietaController;
use App\Http\Controllers\FamiliarController;
use App\Http\Controllers\FichajeController;
use App\Http\Controllers\HabitacionController;
use App\Http\Controllers\HistorialMedicoController;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\InformeController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MedicacionController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResidenteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitaController;
use App\Models\Incidencia;
use App\Models\Informe;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/residentes/crear', [ResidenteController::class, 'create'])->name('residentes.create')->can(P::ResidentesCrear);
    Route::post('/residentes', [ResidenteController::class, 'store'])->name('residentes.store')->can(P::ResidentesCrear);
    Route::get('/residentes/{residente}/editar', [ResidenteController::class, 'edit'])->name('residentes.edit')->can(P::ResidentesEditar);
    Route::put('/residentes/{residente}', [ResidenteController::class, 'update'])->name('residentes.update')->can(P::ResidentesEditar);
    Route::delete('/residentes/{residente}', [ResidenteController::class, 'destroy'])->name('residentes.destroy')->can(P::ResidentesBaja);
    Route::post('/residentes/{id}/restaurar', [ResidenteController::class, 'restaurar'])->name('residentes.restaurar')->can(P::ResidentesReincorporar);
    Route::get('/residentes', [ResidenteController::class, 'index'])->name('residentes.index')->can(P::ResidentesVer);
    Route::get('/residentes/{residente}', [ResidenteController::class, 'show'])->name('residentes.show')->can(P::ResidentesVer)->withTrashed();

    Route::get('/habitaciones', [HabitacionController::class, 'index'])->name('habitaciones.index')->can(P::HabitacionesVer);
    Route::get('/habitaciones/crear', [HabitacionController::class, 'create'])->name('habitaciones.create')->can(P::HabitacionesCrear);
    Route::post('/habitaciones', [HabitacionController::class, 'store'])->name('habitaciones.store')->can(P::HabitacionesCrear);
    Route::put('/habitaciones/{habitacion}', [HabitacionController::class, 'update'])->name('habitaciones.update')->can(P::HabitacionesEditar);
    Route::delete('/habitaciones/{habitacion}', [HabitacionController::class, 'destroy'])->name('habitaciones.destroy')->can(P::HabitacionesBorrar);
    Route::post('/habitaciones/{id}/mantenimiento', [HabitacionController::class, 'toggleMantenimiento'])->name('habitaciones.mantenimiento')->can(P::HabitacionesMantenimiento);

    Route::get('/incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index')->can(P::IncidenciasVer);
    Route::post('/incidencias', [IncidenciaController::class, 'store'])->name('incidencias.store')->can(P::IncidenciasCrear);
    Route::get('/incidencias/{incidencia}', [IncidenciaController::class, 'show'])->name('incidencias.show')->can('view', 'incidencia');
    Route::post('/incidencias/{incidencia}/estado/{estado}', [IncidenciaController::class, 'actualizarEstado'])->name('incidencias.estado')->whereIn('estado', Incidencia::ESTADOS)->can('update', 'incidencia');
    Route::get('/incidencias/{incidencia}/editar', [IncidenciaController::class, 'edit'])->name('incidencias.edit')->can('update', 'incidencia');
    Route::put('/incidencias/{incidencia}', [IncidenciaController::class, 'update'])->name('incidencias.update')->can('update', 'incidencia');
    Route::post('/incidencias/{incidencia}/anular', [IncidenciaController::class, 'anular'])->name('incidencias.anular')->can(P::IncidenciasAnular);

    Route::get('/residentes/{residente_id}/medicacion', [MedicacionController::class, 'index'])->name('medicacion.index')->can(P::MedicacionVer);
    Route::post('/residentes/{residente_id}/medicacion', [MedicacionController::class, 'store'])->name('medicacion.store')->can(P::MedicacionEditar);
    Route::post('/medicacion/{medicacion}/retirar', [MedicacionController::class, 'retirar'])->name('medicacion.retirar')->can(P::MedicacionRetirar);
    Route::post('/medicacion/{medicacion}/anular', [MedicacionController::class, 'anular'])->name('medicacion.anular')->can(P::MedicacionAnular);

    Route::get('/residentes/{residente}/historial/exportar', [HistorialMedicoController::class, 'exportar'])->name('residentes.historial.exportar')->can(P::ResidentesExportarHistorial)->withTrashed();
    Route::get('/residentes/{residente}/historial', [HistorialMedicoController::class, 'index'])->name('residentes.historial')->can(P::ResidentesVer)->withTrashed();
    Route::post('/residentes/{residente}/diagnosticos', [HistorialMedicoController::class, 'storeDiagnostico'])->name('diagnosticos.store')->can(P::DiagnosticosEditar)->withTrashed();
    Route::put('/diagnosticos/{diagnostico}', [HistorialMedicoController::class, 'updateDiagnostico'])->name('diagnosticos.update')->can(P::DiagnosticosEditar)->can('update', 'diagnostico');
    Route::post('/diagnosticos/{diagnostico}/anular', [HistorialMedicoController::class, 'anularDiagnostico'])->name('diagnosticos.anular')->can(P::DiagnosticosAnular);
    Route::post('/diagnosticos/{diagnostico}/toggle', [HistorialMedicoController::class, 'toggleDiagnostico'])->name('diagnosticos.toggle')->can(P::DiagnosticosEditar);
    Route::post('/residentes/{residente}/alergias', [HistorialMedicoController::class, 'storeAlergia'])->name('alergias.store')->can(P::AlergiasEditar)->withTrashed();
    Route::put('/alergias/{alergia}', [HistorialMedicoController::class, 'updateAlergia'])->name('alergias.update')->can(P::AlergiasEditar)->can('update', 'alergia');
    Route::post('/alergias/{alergia}/anular', [HistorialMedicoController::class, 'anularAlergia'])->name('alergias.anular')->can(P::AlergiasAnular);
    Route::post('/alergias/{alergia}/toggle', [HistorialMedicoController::class, 'toggleAlergia'])->name('alergias.toggle')->can(P::AlergiasEditar);
    Route::post('/residentes/{residente}/constantes', [HistorialMedicoController::class, 'storeConstante'])->name('constantes.store')->can(P::ConstantesRegistrar)->withTrashed();
    Route::put('/constantes/{constante}', [HistorialMedicoController::class, 'updateConstante'])->name('constantes.update')->can(P::ConstantesRegistrar)->can('update', 'constante');
    Route::post('/constantes/{constante}/anular', [HistorialMedicoController::class, 'anularConstante'])->name('constantes.anular')->can(P::ConstantesAnular);
    Route::post('/residentes/{residente}/cuidados-diarios', [HistorialMedicoController::class, 'storeCuidadoDiario'])->name('cuidados_diarios.store')->can(P::CuidadosRegistrar)->withTrashed();
    Route::put('/cuidados-diarios/{cuidadoDiario}', [HistorialMedicoController::class, 'updateCuidadoDiario'])->name('cuidados_diarios.update')->can(P::CuidadosRegistrar)->can('update', 'cuidadoDiario');
    Route::post('/cuidados-diarios/{cuidadoDiario}/anular', [HistorialMedicoController::class, 'anularCuidadoDiario'])->name('cuidados_diarios.anular')->can(P::CuidadosAnular);
    Route::post('/residentes/{residente}/visitas-medicas', [HistorialMedicoController::class, 'storeVisita'])->name('visitas_medicas.store')->can(P::VisitasMedicasGestionar)->withTrashed();
    Route::put('/visitas-medicas/{visita_medica}', [HistorialMedicoController::class, 'updateVisitaMedica'])->name('visitas_medicas.update')->can(P::VisitasMedicasGestionar)->can('update', 'visita_medica');
    Route::post('/visitas-medicas/{visita}/anular', [HistorialMedicoController::class, 'anularVisita'])->name('visitas_medicas.anular')->can(P::VisitasMedicasAnular);

    Route::delete('/familiares/{familiar}', [FamiliarController::class, 'destroy'])->name('familiares.destroy')->can(P::FamiliaresBorrar);
    Route::get('/familiares/create', [FamiliarController::class, 'create'])->name('familiares.create')->can(P::FamiliaresCrear);
    Route::post('/familiares', [FamiliarController::class, 'storeGeneral'])->name('familiares.storeGeneral')->can(P::FamiliaresCrear);
    Route::get('/familiares/{familiar}/edit', [FamiliarController::class, 'edit'])->name('familiares.edit')->can(P::FamiliaresEditar);
    Route::put('/familiares/{familiar}', [FamiliarController::class, 'update'])->name('familiares.update')->can(P::FamiliaresEditar);
    Route::post('/residentes/{residente}/familiares', [FamiliarController::class, 'store'])->name('familiares.store')->can(P::FamiliaresCrear)->withTrashed();
    Route::post('/residentes/{residente}/familiares/vincular-existente', [FamiliarController::class, 'vincularExistente'])->name('familiares.vincular-existente')->can(P::FamiliaresVincular)->withTrashed();
    Route::get('/familiares', [FamiliarController::class, 'index'])->name('familiares.lista')->can(P::FamiliaresVer);
    Route::get('/familiares/{familiar}', [FamiliarController::class, 'show'])->name('familiares.show')->can(P::FamiliaresVer);
    Route::get('/residentes/{residente}/familiares', [FamiliarController::class, 'porResidente'])->name('familiares.index')->can(P::FamiliaresVer)->withTrashed();

    Route::get('/residentes/{residente}/dieta', [DietaController::class, 'index'])->name('dieta.index')->can(P::DietasVer)->withTrashed();
    Route::post('/residentes/{residente}/dieta', [DietaController::class, 'store'])->name('dieta.store')->can(P::DietasEditar)->withTrashed();
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index')->can(P::MenusGestionar);
    Route::post('/menus', [MenuController::class, 'store'])->name('menus.store')->can(P::MenusGestionar);
    Route::put('/menus/{menu}', [MenuController::class, 'update'])->name('menus.update')->can(P::MenusGestionar);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->name('menus.destroy')->can(P::MenusGestionar);

    Route::get('/residentes/{residente}/visitas', [VisitaController::class, 'index'])->name('visitas.index')->can(P::VisitasVer)->withTrashed();
    Route::post('/residentes/{residente}/visitas', [VisitaController::class, 'store'])->name('visitas.store')->can(P::VisitasRegistrar)->withTrashed();
    Route::put('/visitas/{visita}', [VisitaController::class, 'update'])->name('visitas.update')->can(P::VisitasEditar);
    Route::post('/visitas/{visita}/salida', [VisitaController::class, 'registrarSalida'])->name('visitas.salida')->can(P::VisitasRegistrar);
    Route::post('/visitas/{visita}/anular', [VisitaController::class, 'anular'])->name('visitas.anular')->can(P::VisitasAnular);

    // Orden importante: users/create debe registrarse antes que users/{user}, o "create" se toma como un id
    Route::resource('users', UserController::class)->only(['create', 'store'])->middleware('can:'.P::EmpleadosCrear->value);
    Route::resource('users', UserController::class)->only(['index', 'show'])->middleware('can:'.P::EmpleadosVer->value);
    Route::resource('users', UserController::class)->only(['edit', 'update'])->middleware('can:'.P::EmpleadosEditar->value);
    Route::post('/users/{user}/verify', [UserController::class, 'verifyEmail'])->name('users.verify')->can(P::EmpleadosVerificarEmail);
    Route::post('/users/{user}/desactivar', [UserController::class, 'desactivar'])->name('users.desactivar')->can(P::EmpleadosDesactivar);
    Route::post('/users/{user}/reactivar', [UserController::class, 'reactivar'])->name('users.reactivar')->can(P::EmpleadosDesactivar);

    Route::get('/informes/crear', [InformeController::class, 'create'])->name('informes.create')->can('create', Informe::class);
    Route::post('/informes', [InformeController::class, 'store'])->name('informes.store')->can('create', Informe::class);
    Route::get('/informes/{informe}/editar', [InformeController::class, 'edit'])->name('informes.edit')->can('update', 'informe');
    Route::put('/informes/{informe}', [InformeController::class, 'update'])->name('informes.update')->can('update', 'informe');
    Route::post('/informes/{informe}/anular', [InformeController::class, 'anular'])->name('informes.anular')->can('anular', 'informe');
    Route::get('/informes', [InformeController::class, 'index'])->name('informes.index')->can('viewAny', Informe::class);
    Route::get('/informes/{informe}', [InformeController::class, 'show'])->name('informes.show')->can('view', 'informe');
    Route::get('/informes/{informe}/pdf', [InformeController::class, 'downloadPdf'])->name('informes.pdf')->can('view', 'informe');

    // Mismo motivo: actividades/create antes que actividades/{actividad}
    Route::resource('actividades', ActividadController::class)->parameters(['actividades' => 'actividad'])->except(['index', 'show', 'destroy'])->middleware('can:'.P::ActividadesGestionar->value);
    Route::post('/actividades/{actividad}/anular', [ActividadController::class, 'anular'])->name('actividades.anular')->can(P::ActividadesGestionar);
    Route::resource('actividades', ActividadController::class)->parameters(['actividades' => 'actividad'])->only(['index', 'show'])->middleware('can:'.P::ActividadesVer->value);

    Route::get('/fichajes', [FichajeController::class, 'index'])->name('fichajes.index')->can(P::FichajesVerPropios);
    Route::get('/fichajes/exportar', [FichajeController::class, 'exportar'])->name('fichajes.exportar')->can(P::FichajesVerPropios);
    Route::get('/fichajes/create', [FichajeController::class, 'create'])->can(P::FichajesCrearOtros)->name('fichajes.create');
    Route::get('/fichajes/{fichaje}', [FichajeController::class, 'show'])->name('fichajes.show')->can('view', 'fichaje');
    Route::post('/fichajes', [FichajeController::class, 'store'])->name('fichajes.store')->can(P::FichajesRegistrarPropios);
    Route::post('/fichajes/{fichaje}/salida', [FichajeController::class, 'marcarSalida'])->name('fichajes.salida')->can('registrarSalida', 'fichaje');
    Route::get('/fichajes/{fichaje}/edit', [FichajeController::class, 'edit'])->can('corregir', 'fichaje')->name('fichajes.edit');
    Route::put('/fichajes/{fichaje}', [FichajeController::class, 'update'])->can('corregir', 'fichaje')->name('fichajes.update');
    Route::post('/fichajes/{fichaje}/anular', [FichajeController::class, 'anular'])->name('fichajes.anular')->can('anular', 'fichaje');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index')->can(P::AuditoriaVer);

});

require __DIR__.'/auth.php';
