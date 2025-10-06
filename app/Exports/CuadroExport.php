<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CuadroExport implements FromArray
{
    protected $idCuadro;

    public function __construct($idCuadro)
    {
        $this->idCuadro = $idCuadro;
    }

    public function array(): array
    {
        // Traer cuadro
        $cuadro = DB::table('cuadros')->where('idCuadro', $this->idCuadro)->first();

        // Categorías involucradas en este cuadro
        $categorias = DB::table('categorias')
            ->select('idCategoria', 'nombre')
            ->orderBy('idCategoria', 'asc')
            ->get();

        // Traer todas las celdas con join de fila y columna
        $celdas = DB::table('celdas as ce')
            ->join('filas as f', 'f.idFila', '=', 'ce.idFila')
            ->join('columnas as c', 'c.idColumna', '=', 'ce.idColumna')
            ->select(
                'ce.idCuadro',
                DB::raw("'" . ($cuadro ? $cuadro->control : '') . "' as numeroCuadro"),
                'f.idFila',
                'f.nombreFila',
                'f.nivel as nivelFila',
                'f.orden as ordenFila',
                'c.idColumna',
                'c.nombreColumna',
                'c.nivel as nivelColumna',
                'c.orden as ordenColumna',
                'ce.idCategoria',
                'ce.valor_numero'
            )
            ->where('ce.idCuadro', $this->idCuadro)
            ->get();

        // Cabecera base
        $header = [
            'ID Cuadro',
            'Número Cuadro',
            'ID Fila',
            'Nombre Fila',
            'Nivel Fila',
            'Orden Fila',
            'ID Columna',
            'Nombre Columna',
            'Nivel Columna',
            'Orden Columna'
        ];

        // Agregar categorías como columnas dinámicas
        foreach ($categorias as $cat) {
            $header[] = $cat->nombre;
        }

        $data = [$header];

        // Agrupar por fila + columna
        $agrupados = [];
        foreach ($celdas as $c) {
            $key = "{$c->idFila}-{$c->idColumna}";
            if (!isset($agrupados[$key])) {
                $agrupados[$key] = [
                    'idCuadro' => $c->idCuadro,
                    'numeroCuadro' => $c->numeroCuadro,
                    'idFila' => $c->idFila,
                    'nombreFila' => $c->nombreFila,
                    'nivelFila' => $c->nivelFila,
                    'ordenFila' => $c->ordenFila,
                    'idColumna' => $c->idColumna,
                    'nombreColumna' => $c->nombreColumna,
                    'nivelColumna' => $c->nivelColumna,
                    'ordenColumna' => $c->ordenColumna,
                    'categorias' => []
                ];
            }
            $agrupados[$key]['categorias'][$c->idCategoria] = $c->valor_numero;
        }

        // Construir filas finales
        foreach ($agrupados as $fila) {
            $row = [
                $fila['idCuadro'],
                $fila['numeroCuadro'],
                $fila['idFila'],
                $fila['nombreFila'],
                $fila['nivelFila'],
                $fila['ordenFila'],
                $fila['idColumna'],
                $fila['nombreColumna'],
                $fila['nivelColumna'],
                $fila['ordenColumna'],
            ];

            foreach ($categorias as $cat) {
                $row[] = $fila['categorias'][$cat->idCategoria] ?? '';
            }

            $data[] = $row;
        }

        return $data;
    }

}
