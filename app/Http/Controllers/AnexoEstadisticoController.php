<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Fila;
use App\Models\Celda;
use App\Models\Cuadro;
use App\Models\Columna;
use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Exports\CuadroExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Stmt\Function_;





class AnexoEstadisticoController extends Controller
{
    public function formularioImportarExcel()
    {
        return view('anexo.importar');
    }
    //Cargar cuadros antiguos
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
                throw new Exception("El archivo Excel está vacío o no se pudo leer.");
            }

            $headers = $rows[0];
            unset($rows[0]);

            $ordenFila = [];
            $ordenCol = [];

            foreach ($rows as $i => $row) {
                $control = trim($row[0] ?? '');
                if (!$control)
                    continue;

                $cuadro = Cuadro::where('control', $control)->first();
                if (!$cuadro)
                    continue;

                // Fila
                $mapaFilas = [
                    1 => 1, // actividad
                    2 => 2, // tema0
                    3 => 3, // tema1
                    4 => 4, // tema2
                    5 => 5, // tema3
                ];

                $fila = null;
                foreach ($mapaFilas as $colIndex => $nivel) {
                    if (!empty($row[$colIndex])) {
                        $nombreFila = trim($row[$colIndex]);

                        if (!isset($ordenFila[$cuadro->idCuadro])) {
                            $ordenFila[$cuadro->idCuadro] = 0;
                        }
                        $ordenFila[$cuadro->idCuadro]++;

                        $fila = Fila::firstOrCreate(
                            [
                                'idCuadro' => $cuadro->idCuadro,
                                'nombreFila' => $nombreFila,
                                'nivel' => $nivel,
                            ],
                            ['orden' => $ordenFila[$cuadro->idCuadro]]
                        );
                    }
                }

                // Columna
                $columnaActual = null;
                if (!empty($row[6])) {
                    if (!isset($ordenCol[$cuadro->idCuadro][1])) {
                        $ordenCol[$cuadro->idCuadro][1] = 0;
                    }
                    $ordenCol[$cuadro->idCuadro][1]++;

                    $columnaActual = Columna::firstOrCreate(
                        [
                            'idCuadro' => $cuadro->idCuadro,
                            'nombreColumna' => trim($row[6]),
                            'nivel' => 1,
                        ],
                        ['orden' => $ordenCol[$cuadro->idCuadro][1]]
                    );
                }

                $mapaColumnas = [7 => 2, 8 => 3, 9 => 4];
                foreach ($mapaColumnas as $colIndex => $nivelCol) {
                    if (!empty($row[$colIndex]) && $columnaActual) {
                        if (!isset($ordenCol[$cuadro->idCuadro][$nivelCol])) {
                            $ordenCol[$cuadro->idCuadro][$nivelCol] = 0;
                        }
                        $ordenCol[$cuadro->idCuadro][$nivelCol]++;

                        $columnaActual = Columna::firstOrCreate(
                            [
                                'idCuadro' => $cuadro->idCuadro,
                                'idColumnaPadre' => $columnaActual->idColumna,
                                'nombreColumna' => trim($row[$colIndex]),
                                'nivel' => $nivelCol,
                            ],
                            ['orden' => $ordenCol[$cuadro->idCuadro][$nivelCol]]
                        );
                    }
                }

                // Celdas
                if ($fila && $columnaActual) {
                    for ($j = 10; $j < count($row); $j++) {
                        $valor = $row[$j];
                        if ($valor === null || $valor === '' || !is_numeric($valor))
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

            // Ordenar filas
            foreach (array_keys($ordenFila) as $idCuadro) {
                $filas = Fila::where('idCuadro', $idCuadro)->orderBy('idFila')->get();
                $orden = 1;
                foreach ($filas as $fila) {
                    $fila->orden = $orden++;
                    $fila->save();
                }
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Importación exitosa',
                ]);
            }

            return redirect()->route('anexo.formulario')->with('success', 'Cuadro importado correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error en la importación',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Formulario para exportar
    public function formularioExportar()
    {
        $cuadros = Cuadro::orderBy('numero')->get();
        return view('anexo.exportar', compact('cuadros'));
    }

    // Descargar cuadros en Excel (desde formulario con query string)
    public function exportarEstructuraNueva(Request $request)
    {
        $idCuadro = $request->input('idCuadro');

        // Buscar el cuadro en la BD
        $cuadro = DB::table('cuadros')->where('idCuadro', $idCuadro)->first();

        $nombreArchivo = "cuadro_{$cuadro->numero}.xlsx";

        return Excel::download(new CuadroExport($idCuadro), $nombreArchivo);
    }


    //Administrador - Listar cuadros
    public function listarCuadros()
    {
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
                'c.descripcion',
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
        // Validación flexible: si hay idCuadro, no exigimos unique en los mismos campos
        $rules = [
            'numero' => 'required|string|max:20|unique:cuadros,numero,' . $request->idCuadro . ',idCuadro',
            'control' => 'required|string|max:50|unique:cuadros,control,' . $request->idCuadro . ',idCuadro',
            'titulo' => 'required|string|max:350',
            'descripcion' => 'nullable|string|max:500',
            'idEjePED' => 'nullable|integer|exists:ejeped,idEjePED',
            'idTemaPED' => 'nullable|integer|exists:temaped,idTemaPED',
            'idDependencia' => 'nullable|exists:dependencia,idDependencia',
            'idGrupo' => 'nullable|exists:grupos,idGrupo',
        ];

        $validated = $request->validate($rules);

        try {
            $cuadro = Cuadro::updateOrCreate(
                ['idCuadro' => $request->idCuadro], // condición de búsqueda
                $validated                         // datos para crear/actualizar
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
                    'c.descripcion',
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
                'cuadro' => $cuadroDatos
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el cuadro: ' . $e->getMessage()
            ], 500);
        }
    }

    public function editarEstructura($id)
    {
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

        if (empty($data['idFila'])) {
            // Buscar el mayor orden en TODO el cuadro
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

        // 👇 devolvemos JSON para AJAX
        return response()->json([
            'success' => true,
            'message' => 'Fila guardada correctamente.',
            'fila' => $fila
        ]);
    }

    public function eliminarFila($idCuadro, $idFila)
    {
        $fila = Fila::where('idCuadro', $idCuadro)->where('idFila', $idFila)->firstOrFail();
        $fila->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fila eliminada correctamente.'
        ]);
    }

    public function guardarColumna(Request $request, $idCuadro)
    {
        $data = $request->validate([
            'idColumna' => 'nullable|integer',
            'nombreColumna' => 'required|string|max:250',
            'idColumnaPadre' => 'nullable|integer|exists:columnas,idColumna',
        ]);

        // Calcular nivel dinámicamente
        $nivel = 1;
        if (!empty($data['idColumnaPadre'])) {
            $padre = Columna::find($data['idColumnaPadre']);
            $nivel = $padre ? $padre->nivel + 1 : 1;
        }

        if (empty($data['idColumna'])) {
            // Buscar el mayor orden en TODO el cuadro
            $maxOrden = Columna::where('idCuadro', $idCuadro)->max('orden');
            $nuevoOrden = $maxOrden ? $maxOrden + 1 : 1;

            // Crear nueva columna
            $columna = Columna::create([
                'idCuadro' => $idCuadro,
                'nombreColumna' => $data['nombreColumna'],
                'nivel' => $nivel,
                'orden' => $nuevoOrden,
                'idColumnaPadre' => $data['idColumnaPadre'] ?? null,
            ]);
        } else {
            // Actualizar columna existente
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
            'columna' => $columna
        ]);
    }

    public function eliminarColumna($idCuadro, $idColumna)
    {
        $columna = Columna::where('idCuadro', $idCuadro)->findOrFail($idColumna);
        $columna->delete();

        return response()->json([
            'success' => true,
            'message' => 'Columna eliminada Correctamente'
        ]);

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


}



