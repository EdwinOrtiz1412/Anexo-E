<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EstructuraCuadroImport implements ToCollection
{
    protected $idCuadro;
    protected $idCategoria;
    protected $datos = [];

    public function __construct($idCuadro, $idCategoria)
    {
        $this->idCuadro = $idCuadro;
        $this->idCategoria = $idCategoria;
    }

    public function collection(Collection $rows)
    {
        // Verificamos que haya al menos 10 filas (por la estructura actual)
        if ($rows->count() < 10) {
            return;
        }

        // === Fila 6 (índice 5): contiene los IDs de columnas hoja ===
        $header = $rows[5];

        // === Desde fila 10 (índice 9) en adelante: datos ===
        foreach ($rows->slice(9) as $row) {
            $idFila = $row[0] ?? null; // Columna A → idFila
            if (!$idFila) {
                continue;
            }

            // Recorremos columnas a partir de C (índice 2)
            for ($i = 2; $i < count($row); $i++) {
                $idColumna = $header[$i] ?? null;
                if (!$idColumna)
                    continue;

                $valor = $row[$i];

                // Si la celda está vacía o NULL, ignorar
                if ($valor === null)
                    continue;

                $valor = trim((string) $valor);
                if ($valor === '')
                    continue;

                $this->datos[] = [
                    'idFila' => (int) $idFila,
                    'idColumna' => (int) $idColumna,
                    'valor' => $valor, // siempre texto
                ];
            }
        }
    }

    public function getDatos()
    {
        return $this->datos;
    }
}
