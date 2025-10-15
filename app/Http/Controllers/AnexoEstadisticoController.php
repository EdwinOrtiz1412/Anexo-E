<?php

namespace App\Http\Controllers;

use App\Exports\CuadroExport;
use App\Exports\EstructuraCuadroExport;
use App\Models\Categoria;
use App\Models\Celda;
use App\Models\Columna;
use App\Models\Cuadro;
use App\Models\Fila;
use App\Models\Grupo;
use App\Models\Llamada;
use App\Models\NotaPie;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AnexoEstadisticoController extends Controller
{
    public function formularioImportarExcel()
    {
        return view('anexo.importar');
    }

    // Cargar cuadros antiguos
    public function procesarImportacionExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|mimes:xls,xlsx|max:2048',
        ]);

        $file = $request->file('archivo');

        DB::beginTransaction();
        try {
            $spreadsheet = IOFactory::load($file->getPathName());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows)) {
                throw new Exception('El archivo Excel está vacío o no se pudo leer.');
            }

            $headers = $rows[0];
            unset($rows[0]);

            $ordenFila = [];
            $ordenCol = [];

            foreach ($rows as $row) {
                $control = trim($row[0] ?? '');
                if (!$control)
                    continue;

                $cuadro = Cuadro::where('control', $control)->first();
                if (!$cuadro)
                    continue;

                //  Filas
                if (!isset($ordenFila[$cuadro->idCuadro])) {
                    $ordenFila[$cuadro->idCuadro] = 0;
                }

                $fila = null;
                $mapaFilas = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5];

                foreach ($mapaFilas as $colIndex => $nivel) {
                    $nombreFila = trim($row[$colIndex] ?? '');
                    if ($nombreFila !== '') {
                        $ordenFila[$cuadro->idCuadro]++;
                        $fila = Fila::updateOrCreate(
                            [
                                'idCuadro' => $cuadro->idCuadro,
                                'nombreFila' => $nombreFila,
                                'nivel' => $nivel,
                            ],
                            ['orden' => $ordenFila[$cuadro->idCuadro]]
                        );
                    }
                }

                //  Columnas
                if (!isset($ordenCol[$cuadro->idCuadro])) {
                    $ordenCol[$cuadro->idCuadro] = 0;
                }

                $columnaActual = null;

                // Nivel 1
                if (!empty($row[6])) {
                    $ordenCol[$cuadro->idCuadro]++;
                    $columnaActual = Columna::updateOrCreate(
                        [
                            'idCuadro' => $cuadro->idCuadro,
                            'nombreColumna' => trim($row[6]),
                            'nivel' => 1,
                        ],
                        ['orden' => $ordenCol[$cuadro->idCuadro]]
                    );
                }

                // Subniveles (nivel 2–4)
                $mapaColumnas = [7 => 2, 8 => 3, 9 => 4];
                foreach ($mapaColumnas as $colIndex => $nivelCol) {
                    if (!empty($row[$colIndex]) && $columnaActual) {
                        $ordenCol[$cuadro->idCuadro]++;
                        $columnaActual = Columna::updateOrCreate(
                            [
                                'idCuadro' => $cuadro->idCuadro,
                                'idColumnaPadre' => $columnaActual->idColumna,
                                'nombreColumna' => trim($row[$colIndex]),
                                'nivel' => $nivelCol,
                            ],
                            ['orden' => $ordenCol[$cuadro->idCuadro]]
                        );
                    }
                }

                // Celdas
                if ($fila && $columnaActual) {
                    for ($j = 10; $j < count($row); $j++) {
                        $valor = trim((string) ($row[$j] ?? ''));
                        if ($valor === '')
                            continue;

                        $nombreCategoria = trim($headers[$j] ?? '');
                        if (!$nombreCategoria)
                            continue;

                        $categoria = Categoria::whereRaw('LOWER(nombre) = ?', [strtolower($nombreCategoria)])->first();
                        if (!$categoria)
                            continue;

                        Celda::updateOrCreate(
                            [
                                'idCuadro' => $cuadro->idCuadro,
                                'idFila' => $fila->idFila,
                                'idColumna' => $columnaActual->idColumna,
                                'idCategoria' => $categoria->idCategoria,
                            ],
                            [
                                'user_id' => auth()->id(),
                                'valor_numero' => $valor,
                            ]
                        );
                    }
                }
            }

            // Reordenar filas después de importar
            foreach (array_keys($ordenFila) as $idCuadro) {
                $filas = Fila::where('idCuadro', $idCuadro)->orderBy('idFila')->get();
                $orden = 1;
                foreach ($filas as $fila) {
                    $fila->orden = $orden++;
                    $fila->save();
                }
            }

            DB::commit();

            return $request->ajax()
                ? response()->json(['message' => 'Importación exitosa'])
                : redirect()->route('anexo.formulario')->with('success', 'Cuadro importado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error en la importación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    // Formulario para exportar ya no se usa
    public function formularioExportar()
    {
        $cuadros = Cuadro::orderBy('numero')->get();

        return view('anexo.exportar', compact('cuadros'));
    }

    // Descargar la estrucutra completa de los caudros pero con la nueva version 
    public function exportarEstructuraNueva(Request $request)
    {
        $idCuadro = $request->input('idCuadro');

        // Buscar el cuadro en la BD
        $cuadro = DB::table('cuadros')->where('idCuadro', $idCuadro)->first();

        $nombreArchivo = "cuadro_{$cuadro->numero}.xlsx";

        return Excel::download(new CuadroExport($idCuadro), $nombreArchivo);
    }

    // Administrador - Listar cuadros
    public function listarCuadros()
    {
        $usuario = auth()->user();
        if (!$usuario->hasRole('administrador')) {
            return view('nopermitido');
        }
        $cuadros = DB::table('cuadros as c')
            ->leftJoin('ejeped as e', 'c.idEjePED', '=', 'e.idEjePED')
            ->leftJoin('temaped as t', 'c.idTemaPED', '=', 't.idTemaPED')
            ->leftJoin('dependencia as d', 'c.idDependencia', '=', 'd.idDependencia')
            ->leftJoin('grupos as g', 'c.idGrupo', '=', 'g.idGrupo')
            ->select(
                'c.idCuadro',
                'c.numero',
                'c.titulo',
                'e.ejePEDDescripcion',
                't.temaPEDDescripcion',
                'd.dependenciaSiglas',
                'g.nombre as grupoNombre'
            )
            ->get();

        $ejes = DB::table('ejeped')
            ->select('idEjePED', 'ejePEDClave', 'ejePEDDescripcion')
            ->orderBy('ejePEDClave')
            ->get();

        $temas = DB::table('temaped')
            ->select('idTemaPED', 'temaPEDClave', 'temaPEDDescripcion')
            ->orderBy('temaPEDClave')
            ->get();

        $dependencias = DB::table('dependencia')->select('idDependencia', 'dependenciaSiglas')->orderBy('dependenciaSiglas')->get();
        $grupos = DB::table('grupos')->select('idGrupo', 'nombre')->orderBy('nombre')->get();

        return view('anexo.administradorCuadros', compact('cuadros', 'ejes', 'temas', 'dependencias', 'grupos'));
    }

    public function obtenerTemasPorEje($idEje)
    {
        $temas = DB::table('temaped')
            ->where('idEjePED', $idEje)
            ->select('idTemaPED', 'temaPEDClave', 'temaPEDDescripcion')
            ->orderBy('temaPEDClave')
            ->get();

        return response()->json($temas);
    }

    public function obtenerDatosCuadro($id)
    {
        $cuadro = DB::table('cuadros as c')
            ->leftJoin('ejeped as e', 'c.idEjePED', '=', 'e.idEjePED')
            ->leftJoin('temaped as t', 'c.idTemaPED', '=', 't.idTemaPED')
            ->leftJoin('dependencia as d', 'c.idDependencia', '=', 'd.idDependencia')
            ->leftJoin('grupos as g', 'c.idGrupo', '=', 'g.idGrupo')
            ->select(
                'c.idCuadro',
                'c.numero',
                'c.control',
                'c.titulo',
                // 'c.descripcion',
                'c.idEjePED',
                'c.idTemaPED',
                'c.idDependencia',
                'c.idGrupo',
                'e.ejePEDDescripcion',
                't.temaPEDDescripcion',
                'd.dependenciaSiglas',
                'g.nombre as grupoNombre'
            )
            ->where('c.idCuadro', $id)
            ->first();

        return response()->json($cuadro);
    }

    public function guardarCuadro(Request $request)
    {
        $rules = [
            'numero' => 'required|string|max:20|unique:cuadros,numero,' . $request->idCuadro . ',idCuadro',
            'control' => 'required|string|max:50|unique:cuadros,control,' . $request->idCuadro . ',idCuadro',
            'titulo' => 'required|string|max:600',
            // 'descripcion' => 'nullable|string|max:500',
            'idEjePED' => 'required|integer|exists:ejeped,idEjePED',
            'idTemaPED' => 'required|integer|exists:temaped,idTemaPED',
            'idDependencia' => 'required|exists:dependencia,idDependencia',
            'idGrupo' => 'nullable|exists:grupos,idGrupo',
        ];

        $validated = $request->validate($rules);

        try {
            $cuadro = Cuadro::updateOrCreate(
                ['idCuadro' => $request->idCuadro],
                $validated
            );

            $cuadroDatos = DB::table('cuadros as c')
                ->leftJoin('ejeped as e', 'c.idEjePED', '=', 'e.idEjePED')
                ->leftJoin('temaped as t', 'c.idTemaPED', '=', 't.idTemaPED')
                ->leftJoin('dependencia as d', 'c.idDependencia', '=', 'd.idDependencia')
                ->leftJoin('grupos as g', 'c.idGrupo', '=', 'g.idGrupo')
                ->where('c.idCuadro', $cuadro->idCuadro)
                ->select(
                    'c.idCuadro',
                    'c.numero',
                    'c.control',
                    'c.titulo',
                    // 'c.descripcion',
                    'e.ejePEDDescripcion',
                    't.temaPEDDescripcion',
                    'd.dependenciaSiglas',
                    'g.nombre as grupoNombre'
                )
                ->first();

            return response()->json([
                'success' => true,
                'message' => $request->idCuadro
                    ? 'Cuadro actualizado correctamente.'
                    : 'Cuadro creado correctamente.',
                'cuadro' => $cuadroDatos,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el cuadro: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function editarEstructura($id)
    {
        $usuario = auth()->user();
        if (!$usuario->hasRole('administrador')) {
            return view('nopermitido');
        }
        $cuadro = \DB::table('cuadros')->where('idCuadro', $id)->first();

        if (!$cuadro) {
            abort(404, 'Cuadro no encontrado');
        }

        $filas = \DB::table('filas')
            ->where('idCuadro', $id)
            ->orderBy('orden', 'asc')
            ->get();

        $columnas = \DB::table('columnas')
            ->where('idCuadro', $id)
            ->orderBy('orden', 'asc')
            ->get();

        return view('anexo.editarEstructura', compact('cuadro', 'filas', 'columnas'));
    }

    public function guardarFila(Request $request, $idCuadro)
    {
        $data = $request->validate([
            'idFila' => 'nullable|integer',
            'nombreFila' => 'required|string|max:250',
            'nivel' => 'required|integer|min:1|max:10',
        ]);

        try {
            // Normalizamos el nombre
            $nombreFila = trim(mb_strtolower($data['nombreFila']));

            // Verificamos duplicado 
            $duplicada = Fila::where('idCuadro', $idCuadro)
                ->whereRaw('LOWER(nombreFila) = ?', [$nombreFila])
                ->where('nivel', $data['nivel'])
                ->when(!empty($data['idFila']), fn($q) => $q->where('idFila', '!=', $data['idFila']))
                ->exists();

            if ($duplicada) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una fila con el mismo nombre y nivel en este cuadro.',
                ], 409);
            }

            if (empty($data['idFila'])) {
                $maxOrden = Fila::where('idCuadro', $idCuadro)->max('orden');
                $nuevoOrden = $maxOrden ? $maxOrden + 1 : 1;

                $fila = Fila::create([
                    'idCuadro' => $idCuadro,
                    'nombreFila' => $data['nombreFila'],
                    'nivel' => $data['nivel'],
                    'orden' => $nuevoOrden,
                ]);
            } else {
                $fila = Fila::findOrFail($data['idFila']);
                $fila->update([
                    'nombreFila' => $data['nombreFila'],
                    'nivel' => $data['nivel'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fila guardada correctamente.',
                'fila' => $fila,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la fila: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function eliminarFila($idCuadro, $idFila)
    {
        $fila = Fila::where('idCuadro', $idCuadro)->where('idFila', $idFila)->firstOrFail();
        $fila->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fila eliminada correctamente.',
        ]);
    }

    public function guardarColumna(Request $request, $idCuadro)
    {
        $data = $request->validate([
            'idColumna' => 'nullable|integer',
            'nombreColumna' => 'required|string|max:250',
            'idColumnaPadre' => 'nullable|integer|exists:columnas,idColumna',
        ]);

        try {
            // Normalizamos
            $nombreColumna = trim(mb_strtolower($data['nombreColumna']));

            // Calcular nivel
            $nivel = 1;
            if (!empty($data['idColumnaPadre'])) {
                $padre = Columna::find($data['idColumnaPadre']);
                $nivel = $padre ? $padre->nivel + 1 : 1;
            }

            // Verificar duplicado
            $duplicada = Columna::where('idCuadro', $idCuadro)
                ->whereRaw('LOWER(nombreColumna) = ?', [$nombreColumna])
                ->where('idColumnaPadre', $data['idColumnaPadre'] ?? null)
                ->when(!empty($data['idColumna']), fn($q) => $q->where('idColumna', '!=', $data['idColumna']))
                ->exists();

            if ($duplicada) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una columna con el mismo nombre bajo este mismo padre.',
                ], 409);
            }

            if (empty($data['idColumna'])) {
                $maxOrden = Columna::where('idCuadro', $idCuadro)->max('orden');
                $nuevoOrden = $maxOrden ? $maxOrden + 1 : 1;

                $columna = Columna::create([
                    'idCuadro' => $idCuadro,
                    'nombreColumna' => $data['nombreColumna'],
                    'nivel' => $nivel,
                    'orden' => $nuevoOrden,
                    'idColumnaPadre' => $data['idColumnaPadre'] ?? null,
                ]);
            } else {
                $columna = Columna::findOrFail($data['idColumna']);
                $columna->update([
                    'nombreColumna' => $data['nombreColumna'],
                    'nivel' => $nivel,
                    'idColumnaPadre' => $data['idColumnaPadre'] ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Columna guardada correctamente.',
                'columna' => $columna,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Conflicto: ya existe una columna duplicada o relación inválida.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error SQL: ' . $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la columna: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function eliminarColumna($idCuadro, $idColumna)
    {
        try {
            $columna = Columna::where('idCuadro', $idCuadro)->find($idColumna);
            if (!$columna) {
                return response()->json(['success' => false, 'message' => 'Columna no encontrada.'], 404);
            }

            // Verificar si tiene subcolumnas 
            $hijas = Columna::where('idColumnaPadre', $idColumna)->count();
            if ($hijas > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar: existen $hijas subcolumnas dependientes.",
                ], 409);
            }

            // Verificar si tiene celdas
            $celdas = Celda::where('idColumna', $idColumna)->count();
            if ($celdas > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se puede eliminar: hay $celdas celdas vinculadas a esta columna.",
                ], 409);
            }

            $columna->delete();

            return response()->json([
                'success' => true,
                'message' => 'Columna eliminada correctamente.',
            ]);

        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar: la columna está relacionada con otras entidades.',
                ], 409);
            }
            throw $e;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al eliminar la columna: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function guardarEstructura(Request $request, $idCuadro)
    {
        $data = $request->validate([
            'filas' => 'array',
            'columnas' => 'array',
        ]);

        try {
            DB::transaction(function () use ($data, $idCuadro) {

                // Filas
                if (!empty($data['filas'])) {
                    foreach ($data['filas'] as $filaData) {
                        $fila = Fila::where('idCuadro', $idCuadro)
                            ->where('idFila', $filaData['idFila'])
                            ->first();

                        if ($fila) {
                            $fila->update([
                                'nombreFila' => $filaData['nombreFila'],
                                'nivel' => $filaData['nivel'],
                                'orden' => $filaData['orden'],
                            ]);
                        }
                    }
                }

                // Columnas
                if (!empty($data['columnas'])) {
                    foreach ($data['columnas'] as $colData) {
                        $columna = Columna::where('idCuadro', $idCuadro)
                            ->where('idColumna', $colData['idColumna'])
                            ->first();

                        if ($columna) {
                            // Recalcular nivel según el padre (si existe)
                            $nivel = 1;
                            if (!empty($colData['idColumnaPadre'])) {
                                $padre = Columna::find($colData['idColumnaPadre']);
                                $nivel = $padre ? $padre->nivel + 1 : 1;
                            }

                            $columna->update([
                                'nombreColumna' => $colData['nombreColumna'],
                                'nivel' => $nivel,
                                'orden' => $colData['orden'],
                                'idColumnaPadre' => $colData['idColumnaPadre'] ?: null,
                            ]);
                        }
                    }
                }

            });

            return response()->json([
                'success' => true,
                'message' => 'Estructura guardada correctamente.',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la estructura: ' . $e->getMessage(),
            ], 500);
        }
    }

    // panel para administrar categorias y grupos
    public function listarCategorias()
    {
        $usuario = auth()->user();
        if (!$usuario->hasRole('administrador')) {
            return view('nopermitido');
        }
        $grupos = DB::table('grupos')
            ->select('idGrupo', 'nombre')
            ->orderBy('idGrupo')
            ->get();

        $categorias = DB::table('categorias as c')
            ->leftJoin('grupos as g', 'c.idGrupo', '=', 'g.idGrupo')
            ->select(
                'c.idCategoria',
                'c.nombre',
                'c.descripcion',
                'c.anio',
                'c.vigente',
                'c.solicitado',
                'c.idGrupo',
                'g.nombre as grupoNombre'
            )
            ->orderBy('g.idGrupo')
            ->orderBy('c.idCategoria')
            ->get();

        return view('anexo.adminCategorias', compact('categorias', 'grupos'));
    }

    public function crearGrupo(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:200|unique:grupos,nombre',
        ]);

        $grupo = Grupo::create([
            'nombre' => $request->nombre,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo creado correctamente',
            'idGrupo' => $grupo->idGrupo,
            'nombre' => $grupo->nombre,
        ]);
    }

    public function guardarCategoria(Request $request)
    {
        $request->validate([
            'idCategoria' => 'nullable|integer|exists:categorias,idCategoria',
            'nombre' => [
                'required',
                'string',
                'max:200',
                Rule::unique('categorias', 'nombre')->ignore($request->idCategoria, 'idCategoria'),
            ],
            'descripcion' => 'required|string|max:1000',
            'anio' => 'required|integer|min:2000|max:2100',
            'idGrupo' => 'required|integer|exists:grupos,idGrupo',
            'vigente' => 'nullable|boolean',
            'solicitado' => 'nullable|boolean',
        ]);

        try {
            $data = [
                'nombre' => trim($request->nombre),
                'descripcion' => trim($request->descripcion ?? ''),
                'anio' => $request->anio,
                'idGrupo' => $request->idGrupo,
                'vigente' => $request->boolean('vigente'),
                'solicitado' => $request->boolean('solicitado'),
            ];

            $categoria = Categoria::updateOrCreate(
                ['idCategoria' => $request->idCategoria],
                $data
            );

            return response()->json([
                'success' => true,
                'message' => $request->idCategoria
                    ? 'Categoría actualizada correctamente'
                    : 'Categoría creada correctamente',
                'categoria' => $categoria,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una categoría con ese nombre o el grupo asociado no es válido.',
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error SQL: ' . $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la categoría: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mostrarCategoria($id)
    {
        $categoria = Categoria::findOrFail($id);

        return response()->json($categoria);
    }

    public function eliminarCategoria($id)
    {
        try {
            $categoria = Categoria::find($id);
            if (!$categoria) {
                return response()->json(['success' => false, 'message' => 'Categoría no encontrada.'], 404);
            }

            // Verificar si está en uso 
            $enUso = Celda::where('idCategoria', $id)->exists() ||
                Llamada::where('idCategoria', $id)->exists() ||
                NotaPie::where('idCategoria', $id)->exists();

            if ($enUso) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría: está asociada a cuadros o datos.',
                ], 409);
            }

            $categoria->delete();

            return response()->json(['success' => true, 'message' => 'Categoría eliminada correctamente.']);

        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage(),
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado al eliminar la categoría: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Seguimeinto de cuadros:
    public function seguimiento($id)
    {
        $cuadro = Cuadro::findOrFail($id);

        // Solo categorías vigentes
        $categorias = Categoria::where('vigente', 1)
            ->orderBy('nombre')
            ->get();

        return view('anexo.seguimiento', compact('cuadro', 'categorias'));
    }

    public function obtenerEstructuraCuadro($idCuadro, $idCategoria)
    {
        try {
            $filas = DB::table('filas')
                ->where('idCuadro', $idCuadro)
                ->orderBy('orden')
                ->get();

            $columnas = DB::table('columnas')
                ->where('idCuadro', $idCuadro)
                ->orderBy('orden')
                ->orderBy('nivel')
                ->get();

            // Ordenar jerárquicamente 
            $columnasOrdenadas = $this->ordenarColumnasJerarquicamente($columnas->toArray());

            $celdas = DB::table('celdas')
                ->where('idCuadro', $idCuadro)
                ->where('idCategoria', $idCategoria)
                ->get();

            $categoria = DB::table('categorias')
                ->select('idCategoria', 'nombre', 'descripcion', 'vigente', 'solicitado')
                ->where('idCategoria', $idCategoria)
                ->first();

            $cuadro = DB::table('cuadros as c')
                ->leftJoin('dependencia as d', 'c.idDependencia', '=', 'd.idDependencia')
                ->select('d.dependenciaNombre')
                ->where('c.idCuadro', $idCuadro)
                ->first();

            $notaPie = DB::table('notas_pie')
                ->where('idCuadro', $idCuadro)
                ->where('idCategoria', $idCategoria)
                ->select('texto', 'fuente')
                ->first();

            $llamadas = DB::table('llamadas')
                ->where('idCuadro', $idCuadro)
                ->where('idCategoria', $idCategoria)
                ->select('idLlamada', 'idFila', 'idColumna', 'nota')
                ->get();

            return response()->json([
                'success' => true,
                'idCuadro' => $idCuadro,
                'categoria' => $categoria,
                'cuadro_dependencia' => $cuadro->dependenciaNombre ?? null,
                'filas' => $filas,
                'columnas' => $columnasOrdenadas,
                'celdas' => $celdas,
                'nota' => $notaPie->texto ?? '',
                'fuente' => $notaPie->fuente ?? ($cuadro->dependenciaNombre ?? ''),
                'llamadas' => $llamadas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la estructura: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function ordenarColumnasJerarquicamente(array $columnas, $idPadre = null)
    {
        $resultado = [];
        foreach ($columnas as $columna) {
            if ($columna->idColumnaPadre == $idPadre) {
                $resultado[] = $columna;
                $hijos = $this->ordenarColumnasJerarquicamente($columnas, $columna->idColumna);
                $resultado = array_merge($resultado, $hijos);
            }
        }

        return $resultado;

    }

    public function descargarEstructuraExcel($idCuadro, $idCategoria)
    {
        $cuadro = DB::table('cuadros')->where('idCuadro', $idCuadro)->first();
        $categoria = DB::table('categorias')->where('idCategoria', $idCategoria)->first();

        $nombreArchivo = 'estructura_cuadro_' . $cuadro->numero . '_cat_' . preg_replace('/\s+/', '_', $categoria->nombre) . '.xlsx';

        return Excel::download(new EstructuraCuadroExport($idCuadro, $idCategoria), $nombreArchivo);
    }

    public function importarEstructura(Request $request, $idCuadro, $idCategoria)
    {
        $request->validate(['archivo' => 'required|file|mimes:xlsx']);

        try {
            $spreadsheet = IOFactory::load($request->file('archivo')->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            //Devuelve las filas con el valor final(asi ya no se tiene probelmas cuando vienen con formulas dentro)
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo Excel no tiene la estructura esperada .',
                ], 422);
            }

            //  Fila 6: IDs de columnas hoja 
            $header = $rows[6];

            // Saltamos A y B (idFila y nombreFila)
            $columnLetters = array_slice(array_keys($header), 2);

            $datos = [];

            // === Desde fila 10 en adelante ===
            for ($i = 10; $i <= count($rows); $i++) {
                $row = $rows[$i] ?? null;
                if (!$row) {
                    continue;
                }

                $idFila = $row['A'] ?? null;
                if (!$idFila) {
                    continue;
                }

                foreach ($columnLetters as $letter) {
                    $idColumna = $header[$letter] ?? null;
                    if (!$idColumna) {
                        continue;
                    }

                    $valor = $row[$letter] ?? null;

                    if ($valor === null) {
                        continue;
                    }

                    $valor = trim((string) $valor);
                    if ($valor === '') {
                        continue;
                    }

                    $datos[] = [
                        'idFila' => (int) $idFila,
                        'idColumna' => (int) $idColumna,
                        'valor' => $valor, // Texto
                    ];

                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Estructura importada correctamente.',
                'datos' => $datos,
            ]);
        } catch (\Throwable $e) {
            // \Log::error('Error al procesar Excel: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al leer el archivo: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function guardarCeldas(Request $request, $idCuadro, $idCategoria)
    {
        $data = $request->validate([
            'celdas' => 'required|array',
            'celdas.*.idFila' => 'required|integer',
            'celdas.*.idColumna' => 'required|integer',
            'celdas.*.valor' => 'nullable|string',
            'nota' => 'nullable|string|max:800',
        ]);
        $notaTexto = trim($request->input('nota', ''));

        DB::beginTransaction();
        try {
            foreach ($data['celdas'] as $celdaData) {
                $valor = trim((string) $celdaData['valor']);

                // Si el valor está vacío, NO guardar ni eliminar, simplemente ignorar
                if ($valor === '') {
                    continue;
                }

                Celda::updateOrCreate(
                    [
                        'idCuadro' => $idCuadro,
                        'idFila' => $celdaData['idFila'],
                        'idColumna' => $celdaData['idColumna'],
                        'idCategoria' => $idCategoria,
                    ],
                    [
                        'user_id' => auth()->id(),
                        'valor_numero' => $valor,
                    ]
                );
            }
            // Guardar pie de cuadro
            $notaExistente = NotaPie::where('idCuadro', $idCuadro)
                ->where('idCategoria', $idCategoria)
                ->first();

            // Obtener la dependencia del cuadro 
            $idDependenciaCuadro = Cuadro::where('idCuadro', $idCuadro)->value('idDependencia');

            if ($notaTexto !== '') {
                // Crear o actualizar la nota de pie 
                NotaPie::updateOrCreate(
                    [
                        'idCuadro' => $idCuadro,
                        'idCategoria' => $idCategoria,
                    ],
                    [
                        // 'idDependencia' => $idDependenciaCuadro,
                        'texto' => $notaTexto,
                        'fuente' => $request->input('fuente', ''),
                        'orden' => 1,
                    ]
                );
            } elseif ($notaExistente) {
                // Si estaba y se borró el texto elimina la nota
                $notaExistente->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Datos guardados correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            // \Log::error('Error al guardar celdas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los datos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function guardarLLamada(Request $request, $idCuadro, $idCategoria)
    {
        $data = $request->validate([
            'idFila' => 'nullable|integer',
            'idColumna' => 'nullable|integer',
            'nota' => 'required|string|max:500',
        ]);
        try {
            $llamada = Llamada::updateOrCreate(
                [
                    'idCuadro' => $idCuadro,
                    'idCategoria' => $idCategoria,
                    'idFila' => $data['idFila'] ?? null,
                    'idColumna' => $data['idColumna'] ?? null,

                ],
                [
                    'nota' => $data['nota'],
                    'orden' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $llamada->wasRecentlyCreated
                    ? 'Llamada creada correctamente'
                    : 'Llamada actualizada correctamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la llamada:' . $e->getMessage(),
            ], 500);
        }
    }

    public function eliminarLlamada(Request $request, $idCuadro, $idCategoria)
    {
        $data = $request->validate([
            'idFila' => 'nullable|integer',
            'idColumna' => 'nullable|integer',
        ]);

        try {
            $llamada = Llamada::where('idCuadro', $idCuadro)
                ->where('idCategoria', $idCategoria)
                ->where(function ($query) use ($data) {
                    $query->where('idFila', $data['idFila'] ?? null)
                        ->where('idColumna', $data['idColumna'] ?? null);
                })
                ->first();

            if (!$llamada) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la llamada para eliminar.',
                ]);
            }

            $llamada->delete();

            return response()->json([
                'success' => true,
                'message' => 'Llamada eliminada correctamente.',
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la llamada: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Tablero para los usuarios enlaces
    public function mostrarTableroCuadros()
    {
        $usuario = auth()->user();

        // Obtenemos la dependencia del usuario autenticado, si la tiene
        $dependenciaUsuario = $usuario->enlace ? $usuario->enlace->dependencia : null;
        $enlace = $usuario->enlace ?? null;

        // Construimos la consulta base de los cuadros
        $cuadrosQuery = DB::table('cuadros as c')
            ->leftJoin('ejeped as e', 'c.idEjePED', '=', 'e.idEjePED')
            ->leftJoin('temaped as t', 'c.idTemaPED', '=', 't.idTemaPED')
            ->leftJoin('dependencia as d', 'c.idDependencia', '=', 'd.idDependencia')
            ->leftJoin('grupos as g', 'c.idGrupo', '=', 'g.idGrupo')
            ->select(
                'c.idCuadro',
                'c.numero',
                'c.titulo',
                'e.ejePEDDescripcion',
                't.temaPEDDescripcion',
                'd.dependenciaSiglas',
                'g.nombre as grupoNombre'
            );

        // Si el usuario tiene dependencia, filtramos por ella
        if ($dependenciaUsuario) {
            $cuadrosQuery->where('c.idDependencia', $dependenciaUsuario->idDependencia);
        }

        $cuadros = $cuadrosQuery->orderBy('c.numero')->get();

        // Cargamos los catálogos que tu vista necesita
        $ejes = DB::table('ejeped')
            ->select('idEjePED', 'ejePEDClave', 'ejePEDDescripcion')
            ->orderBy('ejePEDClave')
            ->get();

        $temas = DB::table('temaped')
            ->select('idTemaPED', 'temaPEDClave', 'temaPEDDescripcion')
            ->orderBy('temaPEDClave')
            ->get();

        $dependencias = DB::table('dependencia')
            ->select('idDependencia', 'dependenciaSiglas')
            ->orderBy('dependenciaSiglas')
            ->get();

        $grupos = DB::table('grupos')
            ->select('idGrupo', 'nombre')
            ->orderBy('nombre')
            ->get();

        // Retornamos la vista con los datos
        return view('anexo.tableroCuadros', compact('cuadros', 'ejes', 'temas', 'dependencias', 'grupos'));
    }
}
