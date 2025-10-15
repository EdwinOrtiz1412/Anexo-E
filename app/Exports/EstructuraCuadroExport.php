<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class EstructuraCuadroExport implements
    FromArray,
    WithTitle,
    ShouldAutoSize,
    WithEvents
{
    protected $idCuadro;
    protected $idCategoria;

    public function __construct($idCuadro, $idCategoria)
    {
        $this->idCuadro = $idCuadro;
        $this->idCategoria = $idCategoria;
    }

    public function array(): array
    {
        $cuadro = DB::table('cuadros')->where('idCuadro', $this->idCuadro)->first();
        $categoria = DB::table('categorias')->where('idCategoria', $this->idCategoria)->first();

        $filas = DB::table('filas')
            ->where('idCuadro', $this->idCuadro)
            ->orderBy('orden')
            ->get();

        $columnas = DB::table('columnas')
            ->where('idCuadro', $this->idCuadro)
            ->orderBy('orden')
            ->get();

        $celdas = DB::table('celdas')
            ->where('idCuadro', $this->idCuadro)
            ->where('idCategoria', $this->idCategoria)
            ->get();

        $data = [];

        // === Fila 1: título general con salto de línea ===
        $titulo = sprintf(
            //  "%s\nCuadro %s — Categoría: %s",
            "%s\nCuadro %s",
            $cuadro->titulo ?? '(Sin título)',
            $cuadro->numero ?? '',
            // $categoria->nombre ?? ''
        );
        $data[] = [$titulo];

        // === Filas 2 a 5 reservadas para encabezados jerárquicos ===
        for ($i = 2; $i <= 5; $i++) {
            $data[] = ['idFila', 'Fila nombre'];
        }

        // === Calcular posiciones de columnas ===
        $colPositions = $this->getColumnPositions($columnas);

        // === Obtener solo columnas hoja ===
        $columnasHojas = $columnas->whereNotIn(
            'idColumna',
            $columnas->pluck('idColumnaPadre')->filter()->unique()
        );

        // === Ordenar las hojas según su posición real en el Excel ===
        $columnasHojas = $columnasHojas->sortBy(
            fn($col) => is_array($colPositions[$col->idColumna])
            ? $colPositions[$col->idColumna]['start']
            : $colPositions[$col->idColumna]
        )->values();

        // === Fila 6: IDs de columnas (solo hojas) ===
        $filaIds = ['', ''];
        foreach ($columnasHojas as $col) {
            $filaIds[] = $col->idColumna;
        }
        $data[] = $filaIds;

        // === NUEVAS FILAS ===
        $blankRow = array_fill(0, count($filaIds), null); // asegura ancho correcto

        $data[] = $blankRow;                           // Fila 7: vacía
        $data[] = ['', $categoria->nombre ?? ''];      // Fila 8: categoría (rojo)
        $data[] = $blankRow;                           // Fila 9: vacía

        // === Filas de datos (desde fila 10 en adelante) ===
        foreach ($filas as $fila) {
            $row = [$fila->idFila, $fila->nombreFila];

            foreach ($columnasHojas as $col) {
                $celda = $celdas->first(
                    fn($c) => $c->idFila == $fila->idFila && $c->idColumna == $col->idColumna
                );

                if (!$celda) {
                    $row[] = '';
                    continue;
                }

                $valor = $celda->valor_numero;
                if (is_string($valor)) {
                    $valor = trim($valor);
                }

                if ($valor === null) {
                    $row[] = '';
                } else {
                    $valor = trim((string) $valor);

                    if ($valor === '') {
                        $row[] = '';
                    } else {
                        $row[] = $valor; // siempre lo tratamos como texto
                    }
                }

            }

            $data[] = $row;
        }

        return $data;
    }

    private function flattenColumns($columnas, $parentId = null, $level = 1)
    {
        $children = $columnas->where('idColumnaPadre', $parentId)->sortBy('orden');
        $flattened = collect();

        foreach ($children as $col) {
            $col->nivel = $level;
            $flattened->push($col);
            $flattened = $flattened->merge(
                $this->flattenColumns($columnas, $col->idColumna, $level + 1)
            );
        }

        return $flattened;
    }

    private function getColumnPositions($columnas)
    {
        $topCols = $columnas->whereNull('idColumnaPadre')->sortBy('orden');
        $colPositions = [];
        $maxColIndex = 2;

        $assignPositions = function ($cols, &$pos) use (&$assignPositions, &$colPositions, $columnas) {
            foreach ($cols as $col) {
                $children = $columnas->where('idColumnaPadre', $col->idColumna)->sortBy('orden');

                if ($children->isEmpty()) {
                    $pos++;
                    $colPositions[$col->idColumna] = $pos;
                } else {
                    $assignPositions($children, $pos);
                    $startChild = collect($children)->first()->idColumna;
                    $endChild = collect($children)->last()->idColumna;
                    $colPositions[$col->idColumna] = [
                        'start' => $colPositions[$startChild],
                        'end' => $colPositions[$endChild],
                    ];
                }
            }
        };
        $assignPositions($topCols, $maxColIndex);

        return $colPositions;
    }

    public function title(): string
    {
        return 'Estructura';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // === Título (fila 1) ===
                $sheet->mergeCells("A1:{$highestColumn}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '1F2937']
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true
                    ]
                ]);
                $sheet->getRowDimension(1)->setRowHeight(40);

                // === Ocultar IDs ===
                $sheet->getRowDimension(6)->setVisible(false);
                $sheet->getColumnDimension('A')->setVisible(false);

                // === Estilo general ===
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

                // === Encabezado jerárquico ===
                $this->applyHeaderMerges($sheet);

                // === Estilo para nombre de categoría (fila 8) ===
                $sheet->mergeCells("B8:{$highestColumn}8");
                $sheet->getStyle('B8')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'DC2626'], // rojo
                        'size' => 12
                    ],
                    'alignment' => [
                        'horizontal' => 'left',
                        'vertical' => 'center'
                    ]
                ]);

                // === Aumentar altura de fila 9 (espaciado visual) ===
                $sheet->getRowDimension(9)->setRowHeight(10);

                // === Bordes generales ===
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => 'medium',
                            'color' => ['rgb' => '9CA3AF']
                        ],
                        'inside' => [
                            'borderStyle' => 'thin',
                            'color' => ['rgb' => 'D1D5DB']
                        ]
                    ]
                ]);

                // === Estilos por nivel de fila ===
                $niveles = DB::table('filas')
                    ->where('idCuadro', $this->idCuadro)
                    ->orderBy('orden')
                    ->pluck('nivel')
                    ->toArray();

                foreach ($niveles as $index => $nivel) {
                    $rowNum = $index + 10; // ahora comienzan en fila 10
    
                    $filaStyle = $sheet->getStyle("B{$rowNum}");
                    $filaStyle->getAlignment()
                        ->setIndent(max(0, $nivel - 1))
                        ->setHorizontal('left')
                        ->setVertical('center');

                    $sheet->getStyle("B{$rowNum}")->applyFromArray([
                        'fill' => [
                            'fillType' => 'solid',
                            'color' => ['rgb' => 'FFFFFF']
                        ]
                    ]);

                    if ($nivel == 1) {
                        $sheet->getStyle("A{$rowNum}:{$highestColumn}{$rowNum}")
                            ->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => [
                                    'fillType' => 'solid',
                                    'color' => ['rgb' => 'F9FAFB']
                                ]
                            ]);
                    } elseif ($nivel == 2) {
                        $sheet->getStyle("A{$rowNum}:{$highestColumn}{$rowNum}")
                            ->applyFromArray([
                                'fill' => [
                                    'fillType' => 'solid',
                                    'color' => ['rgb' => 'FCFCFC']
                                ]
                            ]);
                    }
                }

                // === Protección ===
                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setPassword('cuadro');

                // Bloquear encabezados y filas estáticas
                $sheet->getStyle('A1:' . $highestColumn . '9')
                    ->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);

                // Desbloquear área editable (desde fila 10)
                $sheet->getStyle('C10:' . $highestColumn . $highestRow)
                    ->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

                // === Congelar encabezado ===
                // $sheet->freezePane('C10');
            },
        ];
    }

    private function applyHeaderMerges($sheet)
    {
        $colLetter = function ($num) {
            if (!is_int($num)) {
                if (is_array($num) && isset($num['start'])) {
                    $num = (int) $num['start'];
                } else {
                    return 'A';
                }
            }
            $result = '';
            while ($num > 0) {
                $num--;
                $result = chr(65 + ($num % 26)) . $result;
                $num = intdiv($num, 26);
            }
            return $result;
        };

        $columnas = DB::table('columnas')
            ->where('idCuadro', $this->idCuadro)
            ->orderBy('orden')
            ->get();

        $topCols = $columnas->whereNull('idColumnaPadre')->sortBy('orden');
        $colPositions = $this->getColumnPositions($columnas);

        $mergeHeaders = function ($cols, $level) use (&$mergeHeaders, $sheet, $columnas, $colPositions, $colLetter) {
            foreach ($cols as $col) {
                $children = $columnas->where('idColumnaPadre', $col->idColumna)->sortBy('orden');
                if ($children->isEmpty()) {
                    $colNum = $colPositions[$col->idColumna];
                    $colL = $colLetter($colNum);
                    $sheet->mergeCells("{$colL}{$level}:{$colL}5");
                    $sheet->setCellValue("{$colL}{$level}", $col->nombreColumna);
                } else {
                    $range = $colPositions[$col->idColumna];
                    $startL = $colLetter($range['start']);
                    $endL = $colLetter($range['end']);
                    $sheet->mergeCells("{$startL}{$level}:{$endL}{$level}");
                    $sheet->setCellValue("{$startL}{$level}", $col->nombreColumna);
                    $mergeHeaders($children, $level + 1);
                }
            }
        };
        $mergeHeaders($topCols, 2);

        $sheet->mergeCells('A2:A5');
        $sheet->setCellValue('A2', 'idFila');
        $sheet->mergeCells('B2:B5');
        $sheet->setCellValue('B2', 'Fila/Columna');

        $lastColNum = max(array_map(fn($v) => is_array($v) ? $v['end'] : $v, $colPositions));
        $lastColLetter = $colLetter($lastColNum);

        $sheet->getStyle("A2:{$lastColLetter}5")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'color' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            'borders' => [
                'outline' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'D1D5DB']],
                'inside' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'D1D5DB']]
            ]
        ]);
    }
}
