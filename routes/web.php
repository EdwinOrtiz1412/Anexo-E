<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnexoEstadisticoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//
Route::get('/anexo/importar', [AnexoEstadisticoController::class, 'formularioImportarExcel'])
    ->name('anexo.formulario');

// Procesar importación
Route::post('/anexo/importar', [AnexoEstadisticoController::class, 'procesarImportacionExcel'])
    ->name('anexo.procesar');

// Formulario para exportar
Route::get('/anexo/exportar', [AnexoEstadisticoController::class, 'formularioExportar'])
    ->name('anexo.formularioExportar');

// Descargar Excel con la nueva función
Route::get('/anexo/export-cuadro', [AnexoEstadisticoController::class, 'exportarEstructuraNueva'])
    ->name('anexo.exportar');

//Adminisrador
Route::get('/administrador/cuadros', [AnexoEstadisticoController::class, 'listarCuadros'])
    ->name('anexo.cuadros.listar');
//ver estructura
Route::get('/cuadros/{id}/estructura', [AnexoEstadisticoController::class, 'editarEstructura'])
    ->name('cuadros.estructura');

Route::get('/temas-por-eje/{idEje}', [AnexoEstadisticoController::class, 'obtenerTemasPorEje']);
// Guardar nuevo cuadro
Route::post('/cuadros', [AnexoEstadisticoController::class, 'guardarCuadro'])
    ->name('cuadros.guardar');
Route::get('/cuadros/{id}/datos', [AnexoEstadisticoController::class, 'obtenerDatosCuadro']);


//modificar estrctura
Route::post('/cuadros/{id}/filas', [AnexoEstadisticoController::class, 'guardarFila'])->name('cuadros.filas.guardar');
Route::post('/cuadros/{id}/columnas', [AnexoEstadisticoController::class, 'guardarColumna'])->name('cuadros.columnas.guardar');
Route::delete('/cuadros/{id}/filas/{fila}', [AnexoEstadisticoController::class, 'eliminarFila'])
    ->name('cuadros.filas.eliminar');
Route::delete('/cuadros/{idCuadro}/columnas/{idColumna}', [AnexoEstadisticoController::class, 'eliminarColumna'])
    ->name('columnas.destroy');

Route::post('/cuadros/{idCuadro}/guardar-estructura', [AnexoEstadisticoController::class, 'guardarEstructura'])
    ->name('cuadros.guardarEstructura');


require __DIR__ . '/auth.php';
