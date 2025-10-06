<?php

namespace App\Http\Controllers;

use Str;
use Carbon\Carbon;
use App\Models\Evento;
use App\Models\Registro;
use Illuminate\Http\Request;
use App\Exports\RegistrosExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AsistenciaEventoExport;
use Illuminate\Database\QueryException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AsistenciaController extends Controller
{

    //Seccion del listado de registros de asistenci
    public function listadoRegistros()
    {
        $usuario = Auth::user();

        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_evento')) {
            return view('nopermitido');
        }
        $registros = DB::table('registros as r')
            ->leftJoin('dependencia as d', 'd.idDependencia', '=', 'r.idDependencia')
            ->select([
                'r.idRegistro',
                'r.idDependencia',
                'r.nombre',
                'r.cargo',
                'r.email',
                'r.telefono',
                'r.perfil',
                'r.tipo_enlace',
                'r.qr_uuid',
                DB::raw("COALESCE(d.dependenciaSiglas, d.dependenciaNombre) AS dependencia"),
            ])
            ->orderByDesc('r.idRegistro')
            ->get();
        $dependencias = DB::table('dependencia')
            ->select('idDependencia', 'dependenciaNombre', 'dependenciaSiglas')
            ->orderBy('dependenciaNombre')
            ->get();

        // Agregar data URL SVG a cada registro (evita Imagick)
        foreach ($registros as $r) {
            if (!empty($r->qr_uuid)) {
                // QR "amigable" para lectores de navegador
                $svg = QrCode::format('svg')
                    ->size(380)
                    ->margin(4)// 16 
                    ->errorCorrection('M')
                    ->color(0, 0, 0)
                    ->backgroundColor(255, 255, 255)
                    ->generate((string) $r->qr_uuid);

                // Data URL para usar en <img src="...">
                $r->qr_svg_data = 'data:image/svg+xml;base64,' . base64_encode($svg);
            } else {
                $r->qr_svg_data = null;
            }
        }
        return view('eventos.listadoRegistros', compact('registros', 'dependencias'));
    }
    public function actualizarRegistro(Request $request, int $id)
    {
        // Validación (equivalente a $this->validate)
        $request->validate([
            'cargo' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'perfil' => 'nullable|string|max:255',
            'tipo_enlace' => 'required|in:Directivo,Operativo,Otro',
            'idDependencia' => 'nullable|integer|exists:dependencia,idDependencia',
        ]);

        try {
            $registro = Registro::find($id);
            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado.',
                ], 404);
            }

            $registro->cargo = $request->cargo;
            $registro->telefono = $request->filled('telefono') ? $request->telefono : null;
            $registro->perfil = $request->filled('perfil') ? $request->perfil : null;
            $registro->tipo_enlace = $request->tipo_enlace;
            $registro->idDependencia = $request->idDependencia ?: null;

            $registro->saveOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente.',
                'data' => [
                    'idRegistro' => $registro->idRegistro,
                    'cargo' => $registro->cargo,
                    'telefono' => $registro->telefono,
                    'perfil' => $registro->perfil,
                    'tipo_enlace' => $registro->tipo_enlace,
                    'idDependencia' => $registro->idDependencia,
                ],
            ], 200);

        } catch (\Throwable $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al guardar.',
            ], 500);
        }
    }
    //Seccion para los eventos
    public function listadoEventos()
    {
        $usuario = Auth::user();

        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_evento')) {
            return view('nopermitido');
        }

        $eventos = Evento::orderByDesc('idEvento')->get()->map(function ($e) {
            $estadoStr = [0 => 'pendiente', 1 => 'activo', 2 => 'finalizado'][$e->estado] ?? 'pendiente';

            $fi = $e->fecha_inicio ? Carbon::parse($e->fecha_inicio)->format('Y-m-d H:i:s') : '';
            $ff = $e->fecha_fin ? Carbon::parse($e->fecha_fin)->format('Y-m-d H:i:s') : '';

            // Conteo de asistencias del evento
            $asistCount = DB::table('asistencia_eventos')
                ->where('idEvento', $e->idEvento)
                ->count();

            $e->estado_str = $estadoStr;
            $e->fecha_inicio_fmt = $fi;
            $e->fecha_fin_fmt = $ff;
            $e->asistencias_cnt = $asistCount;
            // Solo se puede eliminar si es pendiente y no tiene asistencias
            $e->can_delete = ($estadoStr === 'pendiente' && $asistCount === 0);

            return $e;
        });
        $dependencias = DB::table('dependencia')
            ->select('idDependencia', DB::raw("COALESCE(dependenciaSiglas,dependenciaNombre) AS nombre"))
            ->orderBy('nombre')
            ->get();

        return view('eventos.listadoEventos', compact('eventos', 'dependencias'));
    }

    public function registrarEvento(Request $request)
    {
        $request->validate([
            'idEvento' => 'nullable|integer',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:255',
            'sede' => 'nullable|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'idDependencia_invitadas' => ['nullable', 'string', 'max:500', 'regex:/^\s*\d+(?:\s*,\s*\d+)*\s*$/'],
        ]);

        try {
            $inicio = $request->filled('fecha_inicio')
                ? Carbon::parse(str_replace('T', ' ', $request->fecha_inicio))
                : null;
            $fin = $request->filled('fecha_fin')
                ? Carbon::parse(str_replace('T', ' ', $request->fecha_fin))
                : null;

            $idsInv = $request->filled('idDependencia_invitadas')
                ? preg_replace('/\s+/', '', $request->idDependencia_invitadas)
                : null;

            // === EDITAR POR ID (nunca crear aquí) ===
            if ($request->filled('idEvento')) {
                $evento = Evento::find($request->idEvento);
                if (!$evento) {
                    return response()->json(['success' => false, 'message' => 'Evento no encontrado.'], 404);
                }
                if ((int) $evento->estado !== 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este evento no puede editarse (no está en pendiente).'
                    ], 409);
                }

                $evento->nombre = $request->nombre;
                $evento->descripcion = $request->descripcion ?: null;
                $evento->sede = $request->sede ?: null;
                $evento->fecha_inicio = $inicio;
                $evento->fecha_fin = $fin;
                $evento->idDependencia_invitadas = $idsInv;

                // No tocar estado
                $evento->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Evento actualizado.',
                    'data' => [
                        'idEvento' => $evento->idEvento,
                        'nombre' => $evento->nombre,
                        'descripcion' => $evento->descripcion,
                        'sede' => $evento->sede,
                        'fecha_inicio' => $evento->fecha_inicio ? $evento->fecha_inicio->format('Y-m-d H:i:s') : '',
                        'fecha_fin' => $evento->fecha_fin ? $evento->fecha_fin->format('Y-m-d H:i:s') : '',
                        'estado' => (int) $evento->estado,
                        'estado_str' => [0 => 'pendiente', 1 => 'activo', 2 => 'finalizado'][$evento->estado] ?? 'pendiente',
                        'idDependencia_invitadas' => $evento->idDependencia_invitadas,

                    ],
                ]);
            }

            $evento = Evento::updateOrCreate(
                ['nombre' => $request->nombre, 'fecha_inicio' => $inicio],
                [
                    'descripcion' => $request->descripcion ?: null,
                    'sede' => $request->sede ?: null,
                    'fecha_fin' => $fin,
                    'estado' => 0, // siempre pendiente al crear
                    'idDependencia_invitadas' => $idsInv,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $evento->wasRecentlyCreated ? 'Evento creado.' : 'Evento actualizado.',
                'data' => [
                    'idEvento' => $evento->idEvento,
                    'nombre' => $evento->nombre,
                    'descripcion' => $evento->descripcion,
                    'sede' => $evento->sede,
                    'fecha_inicio' => $evento->fecha_inicio ? $evento->fecha_inicio->format('Y-m-d H:i:s') : '',
                    'fecha_fin' => $evento->fecha_fin ? $evento->fecha_fin->format('Y-m-d H:i:s') : '',
                    'estado' => (int) $evento->estado,
                    'estado_str' => [0 => 'pendiente', 1 => 'activo', 2 => 'finalizado'][$evento->estado] ?? 'pendiente',
                    'idDependencia_invitadas' => $evento->idDependencia_invitadas,
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo registrar el evento.'], 500);
        }
    }
    public function cambiarEstado(Request $request, int $id)
    {
        $request->validate([
            'estado' => 'required|string|in:pendiente,activo,finalizado',
        ]);

        $map = ['pendiente' => 0, 'activo' => 1, 'finalizado' => 2];

        return DB::transaction(function () use ($id, $request, $map) {
            $evento = Evento::lockForUpdate()->find($id);
            if (!$evento) {
                return response()->json(['success' => false, 'message' => 'Evento no encontrado'], 404);
            }

            $actual = (int) $evento->estado;
            $nuevo = $map[$request->estado];

            // Transiciones permitidas: pendiente->activo, activo->finalizado
            $valida = ($actual === 0 && $nuevo === 1) || ($actual === 1 && $nuevo === 2);
            if (!$valida) {
                return response()->json(['success' => false, 'message' => 'Cambio de estado no permitido'], 409);
            }


            $evento->estado = $nuevo;
            $evento->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => [
                    'idEvento' => $evento->idEvento,
                    'estado' => $evento->estado,
                    'estado_str' => ['pendiente', 'activo', 'finalizado'][$evento->estado] ?? 'pendiente',
                ]
            ]);
        });
    }

    public function eliminarEvento(int $id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $evento = Evento::lockForUpdate()->find($id);
                if (!$evento) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Evento no encontrado.'
                    ], 404);
                }

                // Única regla en backend: solo si está PENDIENTE (estado = 0)
                if ((int) $evento->estado !== 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Solo se pueden eliminar eventos en pendiente.'
                    ], 422);
                }

                $evento->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Evento eliminado correctamente.'
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el evento.'
            ], 500);
        }
    }
    //Selecciona el evento 
    public function selectorEventosActivos()
    {
        $usuario = Auth::user();

        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_evento')) {
            return view('nopermitido');
        }
        $eventosActivos = Evento::where('estado', 1)
            ->orderByDesc('idEvento')
            ->get()
            ->map(function ($e) {
                return (object) [
                    'idEvento' => $e->idEvento,
                    'nombre' => $e->nombre,
                    'descripcion' => $e->descripcion,
                    'sede' => $e->sede,
                    'inicio' => $e->fecha_inicio ? Carbon::parse($e->fecha_inicio)->format('Y-m-d H:i:s') : '',
                    'fin' => $e->fecha_fin ? Carbon::parse($e->fecha_fin)->format('Y-m-d H:i:s') : '',
                    'asistencias' => DB::table('asistencia_eventos')->where('idEvento', $e->idEvento)->count(),
                ];
            });

        return view('eventos.selectorActivos', compact('eventosActivos'));
    }

    //Seccion para la asitencia de eventos
    public function asistenciaEventos(?int $id = null)
    {
        $usuario = Auth::user();
        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_evento')) {
            return view('nopermitido');
        }

        // Si viene ID, usar ese; si no, último activo (compatibilidad con /asistencia-eventos)
        $evento = $id
            ? Evento::where('idEvento', $id)->where('estado', 1)->first()
            : Evento::where('estado', 1)->orderByDesc('idEvento')->first();

        $asistencias = collect();

        if ($evento) {
            $asistencias = DB::table('asistencia_eventos as a')
                ->join('registros as r', 'r.idRegistro', '=', 'a.idRegistro')
                ->leftJoin('dependencia as d', 'd.idDependencia', '=', 'r.idDependencia')
                ->where('a.idEvento', $evento->idEvento)
                ->orderByDesc('a.scanned_at')
                ->get([
                    'a.idAsistencia',
                    'a.idEvento',
                    'r.idRegistro',
                    'r.nombre',
                    'r.qr_uuid',
                    DB::raw("COALESCE(d.dependenciaSiglas, d.dependenciaNombre) AS dependencia"),
                    DB::raw("DATE_FORMAT(a.scanned_at, '%Y-%m-%d %H:%i') as scanned_at"),
                ]);
        }

        $kpiUltimo = $asistencias->first()->scanned_at ?? '—';
        $dependencias = DB::table('dependencia')
            ->select('idDependencia', DB::raw("COALESCE(dependenciaSiglas, dependenciaNombre)AS nombre"))
            ->orderBy('nombre')
            ->get();

        return view('eventos.asistenciaEvento', [
            'eventoActivo' => $evento,
            'idEvento' => $evento?->idEvento,
            'estadoEvento' => $evento ? 'activo' : null,
            'asistencias' => $asistencias,
            'kpiUltimo' => $kpiUltimo,
            'dependencias' => $dependencias,
        ]);
    }
    //Funcion para buscar registros
    public function buscarRegistros(Request $request)
    {
        $request->validate([
            'idDependencia' => 'nullable|integer|exists:dependencia,idDependencia',
            'q' => 'nullable|string|max:200',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $idDep = $request->idDependencia;
        $q = trim((string) $request->q);
        $limit = $request->integer('limit', 50);

        $query = DB::table('registros as r')
            ->leftJoin('dependencia as d', 'd.idDependencia', '=', 'r.idDependencia')
            ->select([
                'r.idRegistro',
                'r.nombre',
                'r.cargo',
                'r.qr_uuid',
                'r.idDependencia',
                DB::raw("COALESCE(d.dependenciaSiglas, d.dependenciaNombre) AS dependencia"),
                'd.dependenciaSiglas as dependenciaSiglas',
                'd.dependenciaNombre as dependenciaNombre',
            ]);

        if ($idDep) {
            $query->where('r.idDependencia', $idDep);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('r.nombre', 'like', "%{$q}%")
                    ->orWhere('r.cargo', 'like', "%{$q}%")
                    ->orWhere('r.qr_uuid', 'like', "%{$q}%");
            });
        }

        $registros = $query->orderBy('r.nombre')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $registros,
        ]);
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'qr_uuid' => 'required|string',
            'idEvento' => 'required|integer|exists:eventos,idEvento',
        ]);

        $evento = Evento::find($request->idEvento);

        if (!$evento || (int) $evento->estado !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'El evento no está activo.'
            ], 409);
        }

        $registro = Registro::where('qr_uuid', $request->qr_uuid)->first();
        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'QR no encontrado en registros.'
            ], 404);
        }

        // Verificar si ya existe asistencia para este evento/registro
        $asistencia = DB::table('asistencia_eventos')
            ->where('idEvento', $evento->idEvento)
            ->where('idRegistro', $registro->idRegistro)
            ->first();

        if ($asistencia) {
            return response()->json([
                'success' => true,
                'message' => 'Asistencia ya registrada.',
                'data' => [
                    'idAsistencia' => $asistencia->idAsistencia,
                    'idEvento' => $evento->idEvento,
                    'idRegistro' => $registro->idRegistro,
                    'nombre' => $registro->nombre,
                    'dependencia' => DB::table('dependencia')
                        ->where('idDependencia', $registro->idDependencia)
                        ->value(DB::raw("COALESCE(dependenciaSiglas, dependenciaNombre)")),
                    'checkin_at' => $asistencia->scanned_at,
                    'duplicado' => true
                ]
            ], 200);
        }

        // Insertar nueva asistencia
        $idAsistencia = DB::table('asistencia_eventos')->insertGetId([
            'idEvento' => $evento->idEvento,
            'idRegistro' => $registro->idRegistro,
            'scanned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in registrado.',
            'data' => [
                'idAsistencia' => $idAsistencia,
                'idEvento' => $evento->idEvento,
                'idRegistro' => $registro->idRegistro,
                'nombre' => $registro->nombre,
                'dependencia' => DB::table('dependencia')
                    ->where('idDependencia', $registro->idDependencia)
                    ->value(DB::raw("COALESCE(dependenciaSiglas, dependenciaNombre)")),
                'checkin_at' => now()->format('Y-m-d H:i:s'),
                'duplicado' => false
            ]
        ], 201);
    }
    public function desgloseDependencias(int $id)
    {
        $usuario = auth()->user();
        $esAdmin = $usuario->hasAnyRole(['administrador', 'administrador_evento']);
        $tieneIE = (bool) ($usuario->ie ?? false);

        if (!($tieneIE || $esAdmin)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $evento = Evento::find($id);
        if (!$evento) {
            return response()->json(['success' => false, 'message' => 'Evento no encontrado'], 404);
        }

        $rows = DB::table('asistencia_eventos as a')
            ->join('registros as r', 'r.idRegistro', '=', 'a.idRegistro')
            ->leftJoin('dependencia as d', 'd.idDependencia', '=', 'r.idDependencia')
            ->where('a.idEvento', $evento->idEvento)
            ->get([
                'r.nombre',
                'r.cargo',
                DB::raw("COALESCE(d.dependenciaSiglas, d.dependenciaNombre) AS dep"),
                DB::raw("DATE_FORMAT(a.scanned_at, '%Y-%m-%d %H:%i') as hora"),
            ]);

        // Agrupar por dependencia
        $map = [];
        foreach ($rows as $row) {
            $dep = $row->dep ?: '—';
            if (!isset($map[$dep])) {
                $map[$dep] = [
                    'dep' => $dep,
                    'presentes' => 0,
                    'firstAt' => null,
                    'lastAt' => null,
                    'personas' => [],
                ];
            }
            $map[$dep]['presentes']++;
            $map[$dep]['personas'][] = [
                'nombre' => $row->nombre ?? '—',
                'cargo' => $row->cargo ?? '_',
                'hora' => $row->hora ?? '',
            ];

            if ($row->hora) {
                if ($map[$dep]['firstAt'] === null || $row->hora < $map[$dep]['firstAt']) {
                    $map[$dep]['firstAt'] = $row->hora;
                }
                if ($map[$dep]['lastAt'] === null || $row->hora > $map[$dep]['lastAt']) {
                    $map[$dep]['lastAt'] = $row->hora;
                }
            }
        }

        // A arreglo y ordenar por presentes desc, luego nombre asc
        $dependencias = array_values($map);
        usort($dependencias, function ($a, $b) {
            if ($a['presentes'] !== $b['presentes'])
                return $b['presentes'] <=> $a['presentes'];
            return strcmp($a['dep'], $b['dep']);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'idEvento' => $evento->idEvento,
                'nombre' => $evento->nombre,
                'dependencias' => $dependencias,
            ]
        ]);
    }
    public function detalleExcelRegistros(Request $request)
    {
        $usuario = auth()->user();
        if (!$usuario->hasRole('administrador') && !$usuario->hasRole('administrador_evento')) {
            return view('nopermitido');
        }
        $fecha = now()->format('Y-m-d-His');
        $nombreArchivo = "registros_$fecha.xlsx";

        return Excel::download(new RegistrosExport, $nombreArchivo);
    }
    public function excelAsistenciaEvento(int $id)
    {
        $evento = Evento::find($id);
        if (!$evento) {
            abort(404, 'Evento no encontrado');
        }
        $fecha = now()->format('Y-m-d-His');
        $safeNombre = Str::slug($evento->nombre ?? "$evento-$id");
        $filename = "{$safeNombre}_Asistencia_{$fecha}.xlsx";

        return Excel::download(new AsistenciaEventoExport($evento), $filename);
    }

    //Registrar particpante por si no se registro 
public function registrarParticipante(Request $request)
{
    // Validación estándar (422 automático en AJAX si falla)
    $request->validate([
        "tipo_enlace"  => 'required|string|max:255',
        "nombre"       => 'required|string|max:255',
        "dependencia"  => 'required|integer|exists:dependencia,idDependencia',
        "cargo"        => 'required|string|max:255',
        "perfil"       => 'required|string|max:255',
        "email"        => 'required|email|max:255',
        "telefono"     => 'required|string|max:50',
    ]);

    try {
        $email = mb_strtolower($request->email);

        // Regla 1: "Mismo nombre + misma dependencia = bloquea"
        $nombreNormalizado = $this->normalizarTexto($request->nombre);

        $existeMismoNombre = Registro::where('idDependencia', (int) $request->dependencia)
            ->whereRaw("
                LOWER(
                    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre,'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),'ü','u')
                ) = ?
            ", [$nombreNormalizado])
            ->exists();

        if ($existeMismoNombre) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un registro con este nombre para la institución seleccionada.',
                'errors'  => [
                    'nombre' => ['Ya existe un registro con este nombre para la institución seleccionada.']
                ],
                'meta'    => [
                    'razon' => 'nombre_duplicado_misma_dependencia'
                ]
            ], 422);
        }

        // Regla 2: Email único a nivel tabla -> si existe, bloquea
        $emailYaExiste = Registro::where('email', $email)->exists();
        if ($emailYaExiste) {
            return response()->json([
                'success' => false,
                'message' => 'El correo ya está registrado.',
                'errors'  => [
                    'email' => ['Este correo ya se encuentra registrado.']
                ],
                'meta'    => [
                    'razon' => 'email_duplicado'
                ]
            ], 422);
        }

        // Alta (si llegamos aquí, el correo no existe)
        $registro = new Registro();
        $registro->idDependencia = (int) $request->dependencia;
        $registro->nombre        = $request->nombre;
        $registro->cargo         = $request->cargo;
        $registro->email         = $email;
        $registro->telefono      = $request->telefono;
        $registro->perfil        = $request->perfil;
        $registro->tipo_enlace   = $request->tipo_enlace;

        // QR único sólo en altas
        $registro->qr_uuid = (string) Str::uuid();

        $registro->saveOrFail();

        // Respuesta JSON para el frontend (AJAX)
        return response()->json([
            'success' => true,
            'message' => 'Participante registrado.',
            'data'    => [
                'esNuevo'    => true,
                'idRegistro' => $registro->idRegistro ?? null,
                'nombre'     => $registro->nombre,
                'qr_uuid'    => $registro->qr_uuid, // útil si luego quieres check-in
            ],
        ], 201);

    } catch (QueryException $ex) {
        // Si por carrera de datos revienta el índice único de email, devolvemos 422 coherente
        $codigo = (string) ($ex->errorInfo[0] ?? '');
        $sqlstate = (string) ($ex->errorInfo[1] ?? '');
        $mensaje = (string) ($ex->getMessage() ?? '');

        // MySQL/MariaDB: SQLSTATE 23000 para UNIQUE violation
        if (str_contains($mensaje, 'uq_registros_email') || $codigo === '23000') {
            return response()->json([
                'success' => false,
                'message' => 'El correo ya está registrado.',
                'errors'  => [
                    'email' => ['Este correo ya se encuentra registrado.']
                ],
                'meta'    => [
                    'razon' => 'email_duplicado'
                ]
            ], 422);
        }

        // Otro error de BD inesperado
        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error al registrar.',
        ], 500);

    } catch (\Throwable $ex) {
        return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error al registrar.',
        ], 500);
    }
}

private function normalizarTexto(string $texto): string
{
    $texto = mb_strtolower($texto);
    $texto = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $texto);
    $texto = preg_replace('/\s+/', ' ', trim($texto));
    return $texto;
}



}
