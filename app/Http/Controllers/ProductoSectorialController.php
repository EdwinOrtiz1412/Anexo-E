<?php

namespace App\Http\Controllers;

use App\Models\EjePED;
use App\Models\EnlaceDependencia;
use App\Models\TemaPED;
use App\Models\ObjetivoPED;
use App\Models\EstrategiaPED;
use App\Models\LineaPED;
use App\Models\Sector;
use App\Models\ObjetivoSector;
use App\Models\EstrategiaSector;
use App\Models\InformeAccion;
use App\Models\IABS;
use App\Models\Dependencia;
use App\Models\ProductoSector;
use App\Models\AlineacionGeneralProducto;
use App\Models\IndicadorProducto;
use App\Models\ProgramaPresupuestario;
use App\Models\ProgramaPresupuestarioProducto;
use App\Models\SeguimientoMeta;
use App\Models\MedioVerificacion;
use App\Models\Titular;
use App\Models\PsObservacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCPDF;
use App\Exports\ProductosSectorialesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ProductoSectorialController extends Controller
{


    public function listarProductosSectoriales()
    {
        $usuario = auth()->user();
        $dependenciaUsuario = $usuario->enlace ? $usuario->enlace->dependencia : null;
        $enlace = $usuario->enlace ?? null;
        $productosQuery = ProductoSector::leftJoin('alineacion_general_producto', 'productosector.idProducto', '=', 'alineacion_general_producto.idProducto')
            ->join('dependencia', 'productosector.idDependencia', '=', 'dependencia.idDependencia')
            ->select(
                'productosector.*',
                'alineacion_general_producto.idObjetivoPED',
                'dependencia.dependenciaNombre',
                'dependencia.dependenciaSiglas'
            );

        if ($dependenciaUsuario) {
            $productosQuery->where('productosector.idDependencia', $dependenciaUsuario->idDependencia);
        }

        $productos = $productosQuery->get();

        if (auth()->user()->ipes)
            return view('productosSectoriales.productossectoriales', [
                'productos' => $productos,
                'ejes' => EjePED::all(),
                'temas' => TemaPED::all(),
                'objetivos' => ObjetivoPED::all(),
                'estrategias' => EstrategiaPED::all(),
                'lineasaccionped' => LineaPED::all(),
                'objetivosSector' => ObjetivoSector::all(),
                'estrategiasSector' => EstrategiaSector::all(),
                'ppas' => InformeAccion::all(),
                'nombresbs' => IABS::all(),
                'listaSectores' => Sector::all(),
            ]);
        else
            return view("nopermitido");

    }


    public function mostrarFormularioCaptura()
    {
        // Obtener el usuario autenticado
        $usuario = Auth::user();
        $dependenciaUsuario = $usuario->enlace ? $usuario->enlace->dependencia : null;
        $dependencias = Dependencia::all();

        // Obtener los ppas antes del return
        $ppas = InformeAccion::where('idDependencia', $dependenciaUsuario->idDependencia)->get();


        return view('productosSectoriales.productossectoriales', [
            'dependencias' => $dependencias,
            'dependenciaUsuario' => $dependenciaUsuario,
            'ejes' => EjePED::all(),
            'temas' => TemaPED::all(),
            'objetivos' => ObjetivoPED::all(),
            'estrategias' => EstrategiaPED::all(),
            'lineasaccionped' => LineaPED::all(),
            'objetivosSector' => ObjetivoSector::all(),
            'estrategiasSector' => EstrategiaSector::all(),
            'ppas' => $ppas,
            'nombresbs' => IABS::all(),
            'listaSectores' => Sector::all(),


        ]);
    }


    // Guardar el producto sectorial
    public function guardarProductoSectorial(Request $request)
    {
        DB::beginTransaction();

        try {
            $usuario = Auth::user();
            $esAdmin = $usuario->hasRole('administrador') || $usuario->hasRole('administrador_pes');

            // Si NO es admin, debe tener dependencia asignada
            if ((!$usuario->enlace || !$usuario->enlace->dependencia) && !$esAdmin) {
                return response()->json([
                    "result" => "error",
                    "message" => "No tienes una dependencia asignada."
                ], 200);
            }

            // Buscar o crear producto
            if ($request->idProducto) {
                $producto = ProductoSector::find($request->idProducto);

                if (!$producto) {
                    return response()->json([
                        "result" => "error",
                        "message" => "Producto no encontrado para actualizar."
                    ], 404);
                }

                // No modificar la dependencia en actualización
                $mensaje = "Producto actualizado correctamente.";
            } else {
                // En creación, usar dependencia del usuario
                if (!$usuario->enlace || !$usuario->enlace->dependencia) {
                    return response()->json([
                        "result" => "error",
                        "message" => "No se puede crear un producto sin una dependencia asignada."
                    ], 200);
                }

                $producto = ProductoSector::create([
                    'producto' => $request->producto,
                    'idDependencia' => $usuario->enlace->dependencia->idDependencia,
                ]);

                $mensaje = "Producto creado correctamente.";
            }
            /*
            // Validaciones adicionales
            if (empty($request->bienesServicios)) {
                return response()->json([
                    "result" => "error",
                    "message" => "No se seleccionaron bienes o servicios."
                ], 400);
            }

            if (empty($request->nombrePPA)) {
                return response()->json([
                    "result" => "error",
                    "message" => "Debe seleccionar al menos un PPA."
                ], 400);
            }*/

            // Guardar alineación general
            AlineacionGeneralProducto::updateOrCreate(
                ['idProducto' => $producto->idProducto],
                [
                    'idEjePED' => $request->idEjePED,
                    'idTemaPED' => $request->idTemaPED,
                    'idObjetivoPED' => $request->idObjetivoPED,
                    'idEstrategiaPED' => $request->idEstrategiaPED,
                    'idLAPED' => $request->idLAPED,
                    'idSector' => $request->idSector,
                    'idObjetivo' => $request->idObjetivo,
                    'idEstrategia' => $request->idEstrategia,
                    'id' => $request->nombrePPA,
                    'idBS' => $request->bienesServicios,
                ]
            );

            // Guardar indicador
            IndicadorProducto::updateOrCreate(
                ['idProducto' => $producto->idProducto],
                [
                    'nombreIndicador' => $request->nombreIndicador,
                    'tipo' => $request->tipoIndicador,
                    'metodo_calculo' => $request->calculoIndicador,
                    'frecuencia_medicion' => $request->frecuenciaMedicion,
                    'sentido_esperado' => $request->sentidoEsperado,
                    'unidad_medida_producto' => $request->unidadIndicador,
                    'unidad_medida_indicador' => $request->unidadMedidaIndicador,
                    'medio_verificacion_indicador' => $request->medioIndicador,
                ]
            );

            DB::commit();

            return response()->json([
                "result" => "ok",
                "message" => $mensaje
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al guardar los datos: ', ['error' => $e->getMessage()]);
            return response()->json([
                "result" => "error",
                "message" => "Error al guardar los datos: " . $e->getMessage()
            ], 500);
        }
    }



    public function obtenerDatosGenerales($id)
    {
        try {
            \Log::info("Buscando producto con ID: $id");

            $producto = ProductoSector::from('productosector')
                ->leftJoin('alineacion_general_producto as agp', 'productosector.idProducto', '=', 'agp.idProducto')
                ->leftJoin('indicadores_producto as ip', 'productosector.idProducto', '=', 'ip.idProducto')
                ->where('productosector.idProducto', $id)
                ->select([
                    'productosector.idProducto as idProducto',
                    'productosector.producto as Producto',
                    'productosector.idDependencia as idDependencia',
                    'productosector.guardar_generales',
                    'productosector.seccion_ped',
                    'productosector.seccion_pes',
                    'productosector.seccion_ppa',
                    'productosector.seccion_DI',
                    'agp.idEjePED',
                    'agp.idTemaPED',
                    'agp.idObjetivoPED',
                    'agp.idEstrategiaPED',
                    'agp.idLAPED',
                    'agp.idSector',
                    'agp.idObjetivo',
                    'agp.idEstrategia',
                    'agp.id as idPPA',
                    'agp.idBS',
                    'ip.nombreIndicador',
                    'ip.tipo as tipoIndicador',
                    'ip.metodo_calculo as calculoIndicador',
                    'ip.frecuencia_medicion',
                    'ip.sentido_esperado',
                    'ip.unidad_medida_producto as unidadIndicador',
                    'ip.unidad_medida_indicador',
                    'ip.medio_verificacion_indicador as medioIndicador'
                ])
                ->first();

            if (!$producto) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            return response()->json($producto);

        } catch (\Exception $e) {
            \Log::error("Error al obtener datos del producto: " . $e->getMessage());
            return response()->json(['error' => 'Error interno'], 500);
        }
    }
    //Eliminar Bien o Servicio:
    public function eliminarBien($productoId, $bienId)
    {
        // Obtener la alineación del producto
        $alineacion = AlineacionGeneralProducto::where('idProducto', $productoId)->first();

        if (!$alineacion) {
            return response()->json([
                'success' => false,
                'message' => 'Alineación no encontrada.'
            ], 404);
        }

        // Separar los ID de bienes/servicios
        $bienes = explode(',', $alineacion->idBS);

        // Filtrar el ID a eliminar
        $bienesFiltrados = array_filter($bienes, function ($id) use ($bienId) {
            return trim($id) != trim($bienId);
        });

        // Actualizar el campo si hubo cambios
        $alineacion->idBS = implode(',', $bienesFiltrados);
        $alineacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Bien o servicio eliminado correctamente.'
        ]);
    }
    //Elimnar PPA
    public function eliminarPPA($productoId, $ppaId)
    {
        try {
            $alineacion = AlineacionGeneralProducto::where('idProducto', $productoId)->first();

            if (!$alineacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alineación no encontrada.'
                ], 404);
            }

            // Limpiar PPAs
            $ppas = explode(',', $alineacion->id ?? '');
            $ppasFiltrados = array_filter($ppas, fn($id) => trim($id) !== trim($ppaId));
            $alineacion->id = implode(',', $ppasFiltrados);

            // Eliminar bienes asociados al PPA
            $bienesActuales = explode(',', $alineacion->idBS ?? '');
            $bienesFiltrados = [];
            $bienesEliminados = [];

            foreach ($bienesActuales as $bienId) {
                $bienId = trim($bienId);
                if (!is_numeric($bienId) && !ctype_digit($bienId))
                    continue;

                $bien = IABS::where('idBS', $bienId)->first(); // ← Aquí es el cambio clave
                if (!$bien || is_null($bien->ia_id))
                    continue;

                if ((string) $bien->ia_id === (string) $ppaId) {
                    $bienesEliminados[] = $bienId;
                } else {
                    $bienesFiltrados[] = $bienId;
                }
            }


            $alineacion->idBS = implode(',', $bienesFiltrados);
            $alineacion->save();

            return response()->json([
                'success' => true,
                'message' => 'PPA y bienes relacionados eliminados correctamente.',
                'bienesEliminados' => $bienesEliminados
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar PPA: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en el servidor: ' . $e->getMessage()
            ], 500);
        }
    }
    //Eliminar Liena de aaccion 
    public function eliminarLineaAccion($productoId, $lineaAccionId)
    {
        // Obtener la alineación del producto
        $alineacion = AlineacionGeneralProducto::where('idProducto', $productoId)->first();

        if (!$alineacion) {
            return response()->json([
                'success' => false,
                'message' => 'Alineación no encontrada.'
            ], 404);
        }

        // Separar los ID de líneas de acción
        $lineas = explode(',', $alineacion->idLAPED);

        // Filtrar el ID a eliminar
        $lineasFiltradas = array_filter($lineas, function ($id) use ($lineaAccionId) {
            return trim($id) != trim($lineaAccionId);
        });

        // Actualizar el campo
        $alineacion->idLAPED = implode(',', $lineasFiltradas);
        $alineacion->save();

        return response()->json([
            'success' => true,
            'message' => 'Línea de acción eliminada correctamente.'
        ]);
    }


    //Seguimiento de producto:

    public function seguimiento($idProducto)
    {
        $producto = ProductoSector::findOrFail($idProducto);

        // Obtener los seguimientos del producto por año
        $seguimientos = ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
            ->orderBy('anio') // Ordenar por año
            ->get();
        return view('productosSectoriales.seguimientoProductos', compact('producto', 'seguimientos'));
    }

    public function mostrarFormularioSeguimiento($idProducto)
    {
        // Obtener el producto por su ID
        $producto = ProductoSector::findOrFail($idProducto);

        // Obtener todos los programas presupuestarios
        $programapresupuestarios = ProgramaPresupuestario::all();  // Obtención de todos los programas

        // Obtener los años disponibles para este producto desde la tabla programa_presupuestario_producto
        $anosDisponibles = ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
            ->select('anio')
            ->distinct()  // Obtener años únicos
            ->get();

        // Obtener los seguimientos por año para ese producto
        $seguimientos = ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
            ->orderBy('anio') // Ordenar por año
            ->get();

        // Pasar tanto el producto como los programas, los años disponibles y los seguimientos a la vista
        return view('productosSectoriales.seguimientoProductos', [
            'producto' => $producto,
            'programapresupuestarios' => $programapresupuestarios,  // Pasar los programas obtenidos
            'anosDisponibles' => $anosDisponibles, // Pasar los años disponibles
            'seguimientos' => $seguimientos, // Pasar los seguimientos por año
        ]);
    }

    public function guardarSeguimientoProductosSectoriales(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validaciones generales
            $request->validate([
                'idProducto' => 'required|exists:productosector,idProducto',
                'anio' => 'required|integer|min:2023|max:2028',
                'programas' => 'nullable|array|min:1',
                'programas.*.idPrograma' => 'nullable|exists:programa_presupuestario,idPrograma',
                'programas.*.componente' => 'required|string|max:355',
                'programas.*.actividad' => 'required|string|max:355',
                'observaciones' => 'nullable|string',
                // Validar arrays de medios existentes
                'medios.idMedio' => 'array',
                'medios.idMedio.*' => 'exists:medios_verificacion,idMedio',
                'medios.descripcion' => 'array',
                'medios.descripcion.*' => 'nullable|string|max:1000',
                // Validar arrays de medios nuevos
                'nuevosMedios.descripcion' => 'array',
                'nuevosMedios.descripcion.*' => 'nullable|string|max:1000',
                'nuevosMedios.nombreArchivo' => 'array',
                'nuevosMedios.nombreArchivo.*' => 'string',
                'nuevosMedios.rutaArchivo' => 'array',
                'nuevosMedios.rutaArchivo.*' => 'string',
            ]);

            $anio = $request->input('anio');

            $programado = $request->input("programado_$anio");
            $realizado = $request->input("realizado_$anio");
            $valor_indicador = $request->input("valor_indicado_decimal_$anio");

            $programado = is_null($programado) ? null : (float) $programado;
            $realizado = is_null($realizado) ? null : (float) $realizado;
            $valor_indicador = is_null($valor_indicador) ? null : (float) $valor_indicador;

            // Guardar seguimiento meta
            $this->guardarSeguimientoMeta($request->idProducto, $anio, $programado, $realizado, $valor_indicador);

            // Guardar programas presupuestarios (varios)
            $programas = $request->input('programas', []);
            $this->guardarProgramaPresupuestarioProducto(
                $request->idProducto,
                $anio,
                $programas
            );

            // Guardar observaciones
            $this->guardarObservacion(
                $request->idProducto,
                $anio,
                $request->input('observaciones')
            );

            // ACTUALIZAR descripciones de medios existentes
            if (!empty($request->input('medios.idMedio'))) {
                foreach ($request->input('medios.idMedio') as $index => $idMedio) {
                    $descripcion = $request->input('medios.descripcion')[$index] ?? null;
                    MedioVerificacion::where('idMedio', $idMedio)->update(['descripcion' => $descripcion]);
                }
            }

            // GUARDAR medios nuevos (archivos subidos)
            if (!empty($request->input('nuevosMedios.nombreArchivo'))) {
                foreach ($request->input('nuevosMedios.nombreArchivo') as $index => $nombreArchivo) {
                    $descripcion = $request->input('nuevosMedios.descripcion')[$index] ?? null;
                    $rutaArchivo = $request->input('nuevosMedios.rutaArchivo')[$index] ?? null;

                    if ($rutaArchivo && $nombreArchivo) {
                        $carpetaFinal = "medios/ps/{$request->idProducto}/{$anio}";
                        if (!file_exists(public_path($carpetaFinal))) {
                            mkdir(public_path($carpetaFinal), 0755, true);
                        }
                        $nuevoPath = $carpetaFinal . '/' . $nombreArchivo;
                        rename(public_path($rutaArchivo), public_path($nuevoPath));

                        MedioVerificacion::create([
                            'idProducto' => $request->idProducto,
                            'anio' => $anio,
                            'nombreArchivo' => $nombreArchivo,
                            'rutaArchivo' => $nuevoPath,
                            'descripcion' => $descripcion
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                "result" => "ok",
                "message" => "Datos actualizados correctamente."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al guardar el seguimiento: ', ['error' => $e->getMessage()]);
            return response()->json([
                "result" => "error",
                "message" => 'Error al guardar el seguimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    protected function guardarSeguimientoMeta($idProducto, $anio, $programado, $realizado, $valor_indicador)
    {
        SeguimientoMeta::updateOrCreate(
            ['idProducto' => $idProducto, 'año' => $anio],
            [
                'programado' => $programado,
                'realizado' => $realizado,
                'valor_indicador' => $valor_indicador,
            ]
        );
    }

    protected function guardarProgramaPresupuestarioProducto($idProducto, $anio, array $programas)
    {
        // Eliminar registros anteriores para evitar duplicados
        ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
            ->where('anio', $anio)
            ->delete();

        // Insertar o crear cada programa presupuestario del arreglo
        foreach ($programas as $programa) {
            if (empty($programa['idPrograma']) || empty($programa['componente']) || empty($programa['actividad'])) {
                continue; // Ignorar registros incompletos
            }

            ProgramaPresupuestarioProducto::create([
                'idProducto' => $idProducto,
                'anio' => $anio,
                'idPrograma' => $programa['idPrograma'],
                'componente' => $programa['componente'],
                'actividad' => $programa['actividad'],
            ]);
        }
    }

    /**
     * Guarda o actualiza las descripciones de medios de verificación para un producto y año.
     *
     * @param int $idProducto
     * @param int $anio
     * @param array $medios Array con ['idMedio' => int, 'descripcion' => string]
     * @return void
     */
    protected function guardarMediosVerificacion(int $idProducto, int $anio, array $medios): void
    {
        foreach ($medios as $medio) {
            if (!empty($medio['idMedio'])) {
                MedioVerificacion::updateOrCreate(
                    [
                        'idProducto' => $idProducto,
                        'anio' => $anio,
                        'idMedio' => $medio['idMedio'],
                    ],
                    [
                        'descripcion' => $medio['descripcion'] ?? null,
                    ]
                );
            }
        }
    }

    protected function guardarObservacion($idProducto, $anio, $observacionTexto)
    {
        if ($observacionTexto === null) {
            // Si no viene observación, no hace nada
            return;
        }

        PsObservacion::updateOrCreate(
            [
                'idProducto' => $idProducto,
                'anio' => $anio,
            ],
            [
                'observacion' => $observacionTexto,
            ]
        );
    }

    /**
     * Obtener observaciones para un producto y año (para llenar formulario)
     */
    public function obtenerObservacion(Request $request, $idProducto)
    {
        $anio = $request->input('anio');
        if (!$anio) {
            return response()->json(['result' => 'error', 'message' => 'Año no proporcionado'], 400);
        }

        $observacion = PsObservacion::where('idProducto', $idProducto)
            ->where('anio', $anio)
            ->first();

        return response()->json([
            'result' => 'ok',
            'data' => $observacion ? $observacion->observacion : '',
        ]);
    }



    protected function obtenerSeguimientoMeta($idProducto, $anio)
    {
        return SeguimientoMeta::where('idProducto', $idProducto)
            ->where('año', $anio)
            ->first();
    }

    protected function obtenerProgramaPresupuestarioProducto($idProducto, $anio)
    {
        return ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
            ->where('anio', $anio)
            ->get();
    }

    protected function obtenerAniosSeleccionados($idProducto)
    {
        return SeguimientoMeta::where('idProducto', $idProducto)
            ->pluck('año')
            ->toArray();
    }
    public function subirMedioVerificacion(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
            ]);

            $archivo = $request->file('archivo');
            if (!$archivo) {
                return response()->json(['result' => 'error', 'message' => 'No se recibió ningún archivo.'], 400);
            }

            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $carpetaTemporal = "medios/temp";

            if (!file_exists(public_path($carpetaTemporal))) {
                if (!mkdir(public_path($carpetaTemporal), 0755, true)) {
                    return response()->json(['result' => 'error', 'message' => 'No se pudo crear la carpeta de destino.'], 500);
                }
            }

            $archivo->move(public_path($carpetaTemporal), $nombreArchivo);

            $rutaArchivo = "$carpetaTemporal/$nombreArchivo";

            return response()->json([
                'result' => 'ok',
                'message' => 'Archivo subido correctamente',
                'archivo' => [
                    'nombre' => $nombreArchivo,
                    'ruta' => $rutaArchivo
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al subir archivo: ' . $e->getMessage());
            return response()->json([
                'result' => 'error',
                'message' => 'Error al subir el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actualizarDescripcionMedio(Request $request, $idMedio)
    {
        $request->validate([
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $medio = MedioVerificacion::find($idMedio);

        if (!$medio) {
            return response()->json(['result' => 'error', 'message' => 'Medio no encontrado'], 404);
        }

        $medio->descripcion = $request->descripcion;
        $medio->save();

        return response()->json(['result' => 'ok', 'message' => 'Descripción actualizada']);
    }
    public function getMediosVerificacion($idProducto, $anio)
    {
        $medios = MedioVerificacion::where('idProducto', $idProducto)
            ->where('anio', $anio)
            ->get();

        return response()->json([
            'result' => 'ok',
            'medios' => $medios
        ]);
    }
    public function eliminarMedio($idMedio)
    {
        $medio = MedioVerificacion::find($idMedio);

        if (!$medio) {
            return response()->json(['result' => 'error', 'message' => 'Archivo no encontrado'], 404);
        }

        // Eliminar archivo físico si existe
        if (file_exists(public_path($medio->rutaArchivo))) {
            unlink(public_path($medio->rutaArchivo));
        }

        $medio->delete();

        return response()->json(['result' => 'ok']);
    }

    public function obtenerDatosSeguimientoTodos($idProducto)
    {
        $anios = [2023, 2024, 2025, 2026, 2027, 2028];
        $datos = [];
        $primeraVez = true;

        // Carga todo en 2 consultas (una por tabla)
        $seguimientos = SeguimientoMeta::where('idProducto', $idProducto)
            ->whereIn('año', $anios)
            ->get()
            ->keyBy('año');

        $programas = ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
            ->whereIn('anio', $anios)
            ->get()
            ->groupBy('anio');

        foreach ($anios as $anio) {
            $seguimiento = $seguimientos->get($anio);
            $programasAnio = $programas->get($anio, collect());

            $programasArray = $programasAnio->map(fn($p) => [
                'idPrograma' => $p->idPrograma,
                'componente' => $p->componente,
                'actividad' => $p->actividad,
            ])->toArray();

            $programado = $seguimiento->programado ?? null;
            if ($programado !== null && floatval($programado) > 0) {
                $primeraVez = false;
            }

            $datos[$anio] = [
                'programado' => $programado ?? '',
                'realizado' => $seguimiento->realizado ?? '',
                'valor_indicado' => $seguimiento->valor_indicador ?? '',
                'programas' => $programasArray,
                'edicion_programacion' => $seguimiento->edicion_programacion ?? 0,
            ];
        }
        // Se obitnene el producto para saber si tiene  habilitado o no el boton guardar cambios
        $producto = \App\Models\ProductoSector::find($idProducto);
        $guardarSeguimiento = 1;

        if ($producto) {
            if (!(auth()->user()->hasRole('administrador') || auth()->user()->hasRole('administrador_pes'))) {
                $guardarSeguimiento = $producto->guardar_seguimiento;
            }
        }

        return response()->json([
            'result' => 'ok',
            'data' => $datos,
            'primera_vez' => $primeraVez,
            'guardar_seguimiento' => $guardarSeguimiento,

        ]);
    }



    public function eliminarProgramaProducto(Request $request, $idProducto, $idPrograma, $anio)
    {
        try {
            $registro = ProgramaPresupuestarioProducto::where('idProducto', $idProducto)
                ->where('idPrograma', $idPrograma)
                ->where('anio', $anio)
                ->first();

            if (!$registro) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'No se encontró el programa presupuestario para este producto y año.'
                ], 404);
            }

            $registro->delete();

            return response()->json([
                'result' => 'ok',
                'message' => 'Programa presupuestario eliminado correctamente.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar programa presupuestario: ', ['error' => $e->getMessage()]);
            return response()->json([
                'result' => 'error',
                'message' => 'Error al eliminar el programa: ' . $e->getMessage()
            ], 500);
        }
    }

    //Reporte :
    public function detalleReporteProducto($id)
    {
        $producto = ProductoSector::from('productosector as p')
            ->leftJoin('alineacion_general_producto as a', 'p.idProducto', '=', 'a.idProducto')
            ->leftJoin('ejeped as eje', 'a.idEjePED', '=', 'eje.idEjePED')
            ->leftJoin('temaped as tema', 'a.idTemaPED', '=', 'tema.idTemaPED')
            ->leftJoin('objetivoped as objped', 'a.idObjetivoPED', '=', 'objped.idObjetivoPED')
            ->leftJoin('estrategiaped as estped', 'a.idEstrategiaPED', '=', 'estped.idEstrategiaPED')
            ->leftJoin('sectores as s', 'a.idSector', '=', 's.idSector')
            ->leftJoin('objetivosector as objsec', 'a.idObjetivo', '=', 'objsec.idObjetivo')
            ->leftJoin('estrategiasector as estsec', 'a.idEstrategia', '=', 'estsec.idEstrategia')
            ->select([
                'p.idProducto',
                'p.producto',
                DB::raw("CONCAT(eje.ejePEDClave, ' ', eje.ejePEDDescripcion) as eje_nombre"),
                DB::raw("CONCAT(tema.temaPEDClave, ' ', tema.temaPEDDescripcion) as tema_nombre"),
                DB::raw("CONCAT(objped.objetivoPEDClave, ' ', objped.objetivoPEDDescripcion) as objetivo_ped"),
                DB::raw("CONCAT(estped.estrategiaPEDClave, ' ', estped.estrategiaPEDDescripcion) as estrategia_ped"),
                DB::raw("CONCAT(s.claveSector, ' ', s.sector) as sector_nombre"),
                DB::raw("CONCAT(objsec.claveObjetivo, ' ', objsec.objetivo) as objetivo_sector"),
                DB::raw("CONCAT(estsec.claveEstrategia, ' ', estsec.estrategia) as estrategia_sector"),
                'a.idBS',
                'a.id as idPPAS',
                'a.idLAPED'
            ])
            ->where('p.idProducto', $id)
            ->first();


        $lineasAccion = collect();
        $ejes = $temas = $objetivosPed = $estrategiasPed = [];

        // Obtener jerarquía desde línea de acción
        if ($producto && !empty($producto->idLAPED)) {
            $laIds = explode(',', $producto->idLAPED);

            $lineasAccion = DB::table('lineaaccionped as la')
                ->leftJoin('estrategiaped as est', 'la.idEstrategiaPED', '=', 'est.idEstrategiaPED')
                ->leftJoin('objetivoped as obj', 'est.idObjetivoPED', '=', 'obj.idObjetivoPED')
                ->leftJoin('temaped as tema', 'obj.idTemaPED', '=', 'tema.idTemaPED')
                ->leftJoin('ejeped as eje', 'tema.idEjePED', '=', 'eje.idEjePED')
                ->whereIn('la.idLAPED', $laIds)
                ->select([
                    'la.laPEDClave',
                    'la.laPEDDescripcion',
                    DB::raw("CONCAT(est.estrategiaPEDClave, ' ', est.estrategiaPEDDescripcion) as estrategia_ped"),
                    DB::raw("CONCAT(obj.objetivoPEDClave, ' ', obj.objetivoPEDDescripcion) as objetivo_ped"),
                    DB::raw("CONCAT(tema.temaPEDClave, ' ', tema.temaPEDDescripcion) as tema_nombre"),
                    DB::raw("CONCAT(eje.ejePEDClave, ' ', eje.ejePEDDescripcion) as eje_nombre")
                ])
                ->get();

            // Agrupar por campo único
            $ejes = $lineasAccion->pluck('eje_nombre')->unique()->filter()->values()->toArray();
            $temas = $lineasAccion->pluck('tema_nombre')->unique()->filter()->values()->toArray();
            $objetivosPed = $lineasAccion->pluck('objetivo_ped')->unique()->filter()->values()->toArray();
            $estrategiasPed = $lineasAccion->pluck('estrategia_ped')->unique()->filter()->values()->toArray();
        }

        // Bienes o servicios
        $bienesServicios = [];
        if (!empty($producto->idBS)) {
            $ids = explode(',', $producto->idBS);
            $bienesServicios = IABS::whereIn('idBS', $ids)->get();
        }

        // PPAs
        $ppasSeleccionados = [];
        if (!empty($producto->idPPAS)) {
            $ppaIds = explode(',', $producto->idPPAS);
            $ppasSeleccionados = DB::table('informe_acciones')
                ->whereIn('id', $ppaIds)
                ->get();
        }

        // Indicador
        $indicador = IndicadorProducto::where('idProducto', $id)->first();

        // Programas
        $programas = ProgramaPresupuestarioProducto::from('programa_presupuestario_producto as ppp')
            ->leftJoin('programa_presupuestario as pp', 'ppp.idPrograma', '=', 'pp.idPrograma')
            ->where('ppp.idProducto', $id)
            ->orderBy('ppp.anio')
            ->select([
                'ppp.anio',
                'ppp.componente',
                'ppp.actividad',
                'pp.clavePrograma',
                'pp.descripcionPrograma'
            ])
            ->get();

        $seguimientos = SeguimientoMeta::where('idProducto', $id)
            ->orderBy('año')
            ->get();

        $mediosVerificacion = MedioVerificacion::where('idProducto', $producto->idProducto)->get();

        return view('productosSectoriales.detalleReporteProducto', compact(
            'producto',
            'bienesServicios',
            'ppasSeleccionados',
            'indicador',
            'programas',
            'seguimientos',
            'mediosVerificacion',
            'lineasAccion',
            'ejes',
            'temas',
            'objetivosPed',
            'estrategiasPed'
        ));
    }

    //Generar Reporte
    public function verReportePS($id)
    {
        $usuario = auth()->user();
        $isAdmin = $usuario->hasRole('administrador') || $usuario->hasRole('administrador_pes') || $usuario->hasRole('consulta');

        // Obtener el producto con los joins necesarios
        $producto = ProductoSector::from('productosector as p')
            ->leftJoin('alineacion_general_producto as a', 'p.idProducto', '=', 'a.idProducto')
            ->leftJoin('ejeped as eje', 'a.idEjePED', '=', 'eje.idEjePED')
            ->leftJoin('temaped as tema', 'a.idTemaPED', '=', 'tema.idTemaPED')
            ->leftJoin('objetivoped as objped', 'a.idObjetivoPED', '=', 'objped.idObjetivoPED')
            ->leftJoin('estrategiaped as estped', 'a.idEstrategiaPED', '=', 'estped.idEstrategiaPED')
            //->leftJoin('lineaaccionped as lap', 'a.idLAPED', '=', 'lap.idLAPED')
            ->leftJoin('sectores as s', 'a.idSector', '=', 's.idSector')
            ->leftJoin('objetivosector as objsec', 'a.idObjetivo', '=', 'objsec.idObjetivo')
            ->leftJoin('estrategiasector as estsec', 'a.idEstrategia', '=', 'estsec.idEstrategia')
            ->leftJoin('dependencia as dep', 'p.idDependencia', '=', 'dep.idDependencia') // <-- añadido
            ->select([
                'p.idProducto',
                'p.producto',
                'p.idDependencia',
                DB::raw("CONCAT(eje.ejePEDClave, ' ', eje.ejePEDDescripcion) as eje_nombre"),
                DB::raw("CONCAT(tema.temaPEDClave, ' ', tema.temaPEDDescripcion) as tema_nombre"),
                DB::raw("CONCAT(objped.objetivoPEDClave, ' ', objped.objetivoPEDDescripcion) as objetivo_ped"),
                DB::raw("CONCAT(estped.estrategiaPEDClave, ' ', estped.estrategiaPEDDescripcion) as estrategia_ped"),
                // DB::raw("CONCAT(lap.laPEDClave, ' ', lap.laPEDDescripcion) as linea_accion"),
                DB::raw("CONCAT(s.claveSector, ' ', s.sector) as sector_nombre"),
                DB::raw("CONCAT(objsec.claveObjetivo, ' ', objsec.objetivo) as objetivo_sector"),
                DB::raw("CONCAT(estsec.claveEstrategia, ' ', estsec.estrategia) as estrategia_sector"),
                'a.idBS',
                'a.id as idPPAS',
                'dep.idDependencia as prod_dep_id',
                'dep.dependenciaNombre as prod_dep_nombre',
                'dep.dependenciaSiglas as prod_dep_siglas',
                'a.idLAPED'
            ])
            ->where('p.idProducto', $id)
            ->first();
        // Inicialización de jerarquías
        $lineasAccion = collect();
        $ejes = $temas = $objetivosPed = $estrategiasPed = [];

        if ($producto && !empty($producto->idLAPED)) {
            $laIds = explode(',', $producto->idLAPED);

            $lineasAccion = DB::table('lineaaccionped as la')
                ->leftJoin('estrategiaped as est', 'la.idEstrategiaPED', '=', 'est.idEstrategiaPED')
                ->leftJoin('objetivoped as obj', 'est.idObjetivoPED', '=', 'obj.idObjetivoPED')
                ->leftJoin('temaped as tema', 'obj.idTemaPED', '=', 'tema.idTemaPED')
                ->leftJoin('ejeped as eje', 'tema.idEjePED', '=', 'eje.idEjePED')
                ->whereIn('la.idLAPED', $laIds)
                ->select([
                    'la.laPEDClave',
                    'la.laPEDDescripcion',
                    DB::raw("CONCAT(est.estrategiaPEDClave, ' ', est.estrategiaPEDDescripcion) as estrategia_ped"),
                    DB::raw("CONCAT(obj.objetivoPEDClave, ' ', obj.objetivoPEDDescripcion) as objetivo_ped"),
                    DB::raw("CONCAT(tema.temaPEDClave, ' ', tema.temaPEDDescripcion) as tema_nombre"),
                    DB::raw("CONCAT(eje.ejePEDClave, ' ', eje.ejePEDDescripcion) as eje_nombre")
                ])
                ->get();

            // Agrupar jerarquías únicas
            $ejes = $lineasAccion->pluck('eje_nombre')->unique()->filter()->values()->toArray();
            $temas = $lineasAccion->pluck('tema_nombre')->unique()->filter()->values()->toArray();
            $objetivosPed = $lineasAccion->pluck('objetivo_ped')->unique()->filter()->values()->toArray();
            $estrategiasPed = $lineasAccion->pluck('estrategia_ped')->unique()->filter()->values()->toArray();
        }


        if (!$producto) {
            abort(404, 'Producto no encontrado');
        }

        // Obtener la dependencia según el tipo de usuario
        if ($isAdmin) {
            $dependenciaUsuario = (object) [
                'idDependencia' => $producto->prod_dep_id,
                'dependenciaNombre' => $producto->prod_dep_nombre,
                'dependenciaSiglas' => $producto->prod_dep_siglas,
            ];
        } else {
            $dependenciaUsuario = $usuario->enlace ? $usuario->enlace->dependencia : null;
        }

        if ($isAdmin) {
            // Obtener enlace de la dependencia del producto
            $enlace = EnlaceDependencia::where('idDependencia', $dependenciaUsuario->idDependencia)->first();
        } else {
            $enlace = $usuario->enlace ?? null;
        }
        // Obtener múltiples Líneas de Acción
        $lineasAccion = [];
        if ($producto && !empty($producto->idLAPED)) {
            $laIds = explode(',', $producto->idLAPED);
            $lineasAccion = DB::table('lineaaccionped')
                ->whereIn('idLAPED', $laIds)
                ->get();
        }

        // Bienes y Servicios
        $bienesServicios = [];
        if (!empty($producto->idBS)) {
            $ids = explode(',', $producto->idBS);
            $bienesServicios = IABS::whereIn('idBS', $ids)->get();
        }

        // PPAs Seleccionados
        $ppasSeleccionados = [];
        if (!empty($producto->idPPAS)) {
            $ppaIds = explode(',', $producto->idPPAS);
            $ppasSeleccionados = DB::table('informe_acciones')
                ->whereIn('id', $ppaIds)
                ->get();
        }

        // Indicador
        $indicador = IndicadorProducto::where('idProducto', $id)->first();

        // Programas
        $programas = ProgramaPresupuestarioProducto::from('programa_presupuestario_producto as ppp')
            ->leftJoin('programa_presupuestario as pp', 'ppp.idPrograma', '=', 'pp.idPrograma')
            ->where('ppp.idProducto', $id)
            ->orderBy('ppp.anio')
            ->select([
                'ppp.anio',
                'ppp.componente',
                'ppp.actividad',
                'pp.clavePrograma',
                'pp.descripcionPrograma'
            ])
            ->get();

        // Seguimientos
        $seguimientos = SeguimientoMeta::where('idProducto', $id)->orderBy('año')->get();
        $anios = ['2023', '2024', '2025', '2026', '2027', '2028'];
        $seguimientoValores = [];

        foreach ($seguimientos as $s) {
            $seguimientoValores[$s->tipo][$s->año] = $s->valor;
        }

        // Medios de verificación
        $mediosVerificacion = MedioVerificacion::where('idProducto', $producto->idProducto)->get();

        // Titular de dependencia
        $titular = null;
        if ($dependenciaUsuario) {
            $titular = Titular::where('idDependencia', $dependenciaUsuario->idDependencia)->first();
        }

        // Generar PDF
        $pdf = new CustomPDF('P', 'mm', array(310, 210), true, 'UTF-8', false);
        $pdf->SetMargins(15, 10, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 8);

        $fechaActualizacion = now()->format('Y-m-d H:i:s');

        $html = view('productosSectoriales.reportePS', compact(
            'producto',
            'bienesServicios',
            'ppasSeleccionados',
            'indicador',
            'programas',
            'seguimientos',
            'seguimientoValores',
            'mediosVerificacion',
            'dependenciaUsuario',
            'anios',
            'titular',
            'enlace',
            'fechaActualizacion',
            'lineasAccion',
            'ejes',
            'temas',
            'objetivosPed',
            'estrategiasPed'
        ))->render();


        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Ficha Tecnica Del Indicador.pdf', 'I');
    }
    public function guardarSeguimientoPrimeraVez(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'idProducto' => 'required|exists:productosector,idProducto',
            ]);

            $idProducto = $request->input('idProducto');
            $allAnios = [2023, 2024, 2025, 2026, 2027, 2028];

            foreach ($allAnios as $anio) {
                $programado = $request->input("programado_$anio");
                $realizado = $request->input("realizado_$anio");
                $valor_indicador = $request->input("valor_indicado_decimal_$anio");

                if (!is_null($programado) && $programado !== '') {
                    $this->guardarSeguimientoMeta(
                        $idProducto,
                        $anio,
                        (int) $programado,
                        is_null($realizado) || $realizado === '' ? null : (int) $realizado,
                        is_null($valor_indicador) || $valor_indicador === '' ? null : (float) $valor_indicador
                    );
                }
            }

            DB::commit();

            return response()->json([
                'result' => 'ok',
                'message' => 'Seguimiento inicial guardado correctamente.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error en seguimiento inicial: ' . $e->getMessage());
            return response()->json([
                'result' => 'error',
                'message' => 'Error al guardar los datos iniciales: ' . $e->getMessage(),
            ], 500);
        }
    }

    //Revision Pendiente 
    public function enviarRevision(Request $request)
    {
        try {
            ProductoSector::where('idProducto', $request->idProducto)->first()
                ->update([
                    'estado_producto' => $request->estado ?? 'revision' // 
                ]);

            return response()->json([
                'result' => 'ok',
                'message' => 'El producto fue enviado a revisión correctamente.'
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'result' => 'error',
                'message' => 'Ocurrió un error al enviar el producto a revisión.'
            ]);
        }
    }
    //Admin 
    public function listarProductosAdministrador()
    {
        $usuario = Auth::user();

        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_pes')) {
            return view('nopermitido');
        }
        $productos = ProductoSector::leftJoin('alineacion_general_producto', 'productosector.idProducto', '=', 'alineacion_general_producto.idProducto')
            ->join('dependencia', 'productosector.idDependencia', '=', 'dependencia.idDependencia')
            ->select(
                'productosector.*',
                'alineacion_general_producto.idObjetivoPED',
                'dependencia.dependenciaNombre',
                'dependencia.dependenciaSiglas'
            )
            ->get();

        return view('productosSectoriales.admin_productos_sectoriales', [
            'productos' => $productos,
            'dependencias' => Dependencia::all(),
            'ejes' => EjePED::all(),
            'temas' => TemaPED::all(),
            'objetivos' => ObjetivoPED::all(),
            'estrategias' => EstrategiaPED::all(),
            'lineasaccionped' => LineaPED::all(),
            'objetivosSector' => ObjetivoSector::all(),
            'estrategiasSector' => EstrategiaSector::all(),
            'ppas' => InformeAccion::all(),
            'nombresbs' => IABS::all(),
            'listaSectores' => Sector::all()
        ]);
    }

    public function cambiarEstatus(Request $request, $id)
    {
        $request->validate([
            'nuevo_estatus' => 'required|in:activo,revision',
        ]);

        $producto = ProductoSector::findOrFail($id);
        $producto->estado_producto = $request->input('nuevo_estatus');
        $producto->save();

        if ($request->ajax()) {
            return response()->json([
                'result' => 'ok',
                'message' => 'Estatus actualizado.',
                'estatus' => $producto->estado_producto,
                'id' => $producto->idProducto,
            ]);
        }

        return redirect()->back()->with('success', 'Estatus actualizado.');
    }
    public function detalleExelPS()
    {
        $fecha = now()->format('Y-m-d-His');
        $nombreArchivo = "productos_sectoriales_$fecha.xlsx";
        return Excel::download(new ProductosSectorialesExport, $nombreArchivo);
    }

    public function asignarResponsable(Request $request, $id)
    {
        $usuario = auth()->user();

        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_pes')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción.'
            ], 403);
        }

        $request->validate([
            'idDependencia' => 'required|exists:dependencia,idDependencia',
        ]);

        $producto = ProductoSector::findOrFail($id);
        $producto->idDependencia = $request->idDependencia;
        $producto->save();

        return response()->json([
            'success' => true,
            'message' => 'Dependencia asignada correctamente.'
        ]);
    }
    //Habilitra la edicion del seguimeinto de metas 
    public function habilitarAnios(Request $request)
    {

        $idProducto = $request->input('idProducto');
        $aniosHabilitados = $request->input('anios', []); // ← debe ser un array

        if (!$idProducto) {
            return redirect()->back()->with('error', 'ID de producto no proporcionado.');
        }

        foreach (range(2023, 2028) as $anio) {
            SeguimientoMeta::updateOrCreate(
                ['idProducto' => $idProducto, 'año' => $anio],
                ['edicion_programacion' => in_array($anio, $aniosHabilitados) ? 1 : 0]
            );
        }

        return redirect()->back()->with('success', 'Años actualizados correctamente.');
    }
    //Hbailiatra el guardado :
    public function getGuardadoStatus($idProducto)
    {
        $producto = ProductoSector::findOrFail($idProducto);
        return response()->json([
            'guardar_generales' => $producto->guardar_generales,
            'guardar_seguimiento' => $producto->guardar_seguimiento,
            'seccion_ped' => $producto->seccion_ped,
            'seccion_pes' => $producto->seccion_pes,
            'seccion_ppa' => $producto->seccion_ppa,
            'seccion_DI' => $producto->seccion_DI,
        ]);
    }

    public function habilitarGuardado(Request $request)
    {
        $producto = ProductoSector::findOrFail($request->idProducto);
        //Guardado
        $producto->guardar_generales = $request->has('guardar_generales') ? 1 : 0;
        $producto->guardar_seguimiento = $request->has('guardar_seguimiento') ? 1 : 0;
        //Secciones
        $producto->seccion_ped = $request-> has('seccion_ped') ? 1 : 0;
        $producto->seccion_pes = $request->has('seccion_pes') ? 1 : 0;
        $producto->seccion_ppa = $request->has('seccion_ppa') ? 1 : 0;
        $producto->seccion_DI = $request->has('seccion_DI') ? 1 : 0;
        $producto->save();

        return response()->json(['result' => 'ok', 'message' => 'Configuración actualizada']);
    }



    public function obtenerAniosHabilitados($idProducto)
    {
        $anios = SeguimientoMeta::where('idProducto', $idProducto)
            ->where('edicion_programacion', 1)
            ->pluck('año');

        return response()->json([
            'result' => 'ok',
            'anios' => $anios
        ]);
    }
    //Listado consulta 
    public function listarProductosConsulta()
    {
        $usuario = Auth::user();
        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('consulta')) {
            return view('nopermitido');
        }

        $productos = ProductoSector::leftJoin('alineacion_general_producto', 'productosector.idProducto', '=', 'alineacion_general_producto.idProducto')
            ->join('dependencia', 'productosector.idDependencia', '=', 'dependencia.idDependencia')
            ->select(
                'productosector.*',
                'alineacion_general_producto.idObjetivoPED',
                'dependencia.dependenciaNombre',
                'dependencia.dependenciaSiglas'
            )
            ->get();

        return view('productosSectoriales.productoSectorialConsulta', [
            'productos' => $productos,
            'dependencias' => Dependencia::all(),
            'ejes' => EjePED::all(),
            'temas' => TemaPED::all(),
            'objetivos' => ObjetivoPED::all(),
            'estrategias' => EstrategiaPED::all(),
            'lineasaccionped' => LineaPED::all(),
            'objetivosSector' => ObjetivoSector::all(),
            'estrategiasSector' => EstrategiaSector::all(),
            'ppas' => InformeAccion::all(),
            'nombresbs' => IABS::all(),
            'listaSectores' => Sector::all()
        ]);
    }



}
class CustomPDF extends TCPDF
{
    private $paginaPrimera = true;

    public function Header()
    {
        $anchoPagina = $this->getPageWidth();

        // Imagen en la parte superior
        $this->Image(public_path('images/encabezado-pdf.png'), 15, 5, $anchoPagina - 30);

        // Ajuste condicional del margen
        if (!$this->paginaPrimera) {
            $this->SetMargins(15, 25, 15);
        }

        $this->Ln(40);
        $this->paginaPrimera = false;
    }
}

