@extends('layouts.administrador')

@section('title', 'Estructura del cuadro')

@section('styles')
    <style>
        body {
            background-color: #f9fafb;
            color: #1e293b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Niveles */
        .nivel-1 {
            margin-left: 0;
            font-weight: 600;
        }

        .nivel-2 {
            margin-left: 20px;
            font-style: italic;
        }

        .nivel-3 {
            margin-left: 40px;
            color: #555;
        }

        .nivel-4 {
            margin-left: 60px;
            font-size: 0.9rem;
        }

        /* Cards */
        .card-header {
            background: linear-gradient(90deg, #111827, #374151);
            color: white;
            font-weight: 600;
            border-radius: .5rem .5rem 0 0;
        }

        table th {
            background-color: #f3f4f6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f5f9;
        }

        .form-control {
            border-radius: .5rem;
            border: 1px solid #d1d5db;
        }

        .form-control:focus {
            border-color: #374151;
            box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, .25);
        }

        /* Botones */
        .btn-fila {
            background-color: #6B7280;
            color: #fff;
            border: none;
        }

        .btn-fila:hover {
            background-color: #4B5563;
        }

        .btn-columna {
            background-color: #4B5563;
            color: #fff;
            border: none;
        }

        .btn-columna:hover {
            background-color: #374151;
        }

        .btn-guardar {
            background-color: #374151;
            color: #fff;
            border: none;
        }

        .btn-guardar:hover {
            background-color: #1F2937;
        }

        .btn-volver {
            background-color: #9CA3AF;
            color: #fff;
            border: none;
        }

        .btn-volver:hover {
            background-color: #6B7280;
        }

        .modal-header.bg-fila {
            background-color: #6B7280 !important;
        }

        .modal-header.bg-columna {
            background-color: #4B5563 !important;
        }
    </style>
@endsection
@section('content')
    <div class="py-4">
        <div class="container-fluid">
            <div class="text-end mb-3">
                <a href="{{ route('anexo.cuadros.listar') }}" class="btn btn-volver">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>

            <!-- Card Información -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Información del Cuadro
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>ID:</strong> {{ $cuadro->idCuadro }}</div>
                        <div class="col-md-3"><strong>Número:</strong> {{ $cuadro->numero }}</div>
                        <div class="col-md-6"><strong>Título:</strong> {{ $cuadro->titulo }}</div>
                    </div>
                </div>
            </div>

            <!-- Card Filas -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-stream"></i> Filas</span>
                    <button class="btn btn-sm btn-fila" data-bs-toggle="modal" data-bs-target="#modalAgregarFila">
                        <i class="bi bi-plus-lg"></i> Agregar Fila
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaFilas" class="table table-hover table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">ID</th>
                                    <th>Nombre de Fila</th>
                                    <th style="width: 100px;">Nivel</th>
                                    <th style="width: 100px;">Orden</th>
                                    <th style="width: 90px;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filas as $fila)
                                    <tr id="fila-{{ $fila->idFila }}">
                                        <td class="text-center align-middle">
                                            <small class="text-muted">{{ $fila->idFila }}</small>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm nivel-{{ $fila->nivel }}"
                                                value="{{ $fila->nombreFila }}">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-center"
                                                value="{{ $fila->nivel }}">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm text-center"
                                                value="{{ $fila->orden }}">
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-danger btn-sm rounded-circle"
                                                onclick="eliminarFila({{ $cuadro->idCuadro }}, {{ $fila->idFila }})"
                                                title="Eliminar fila">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Card Columnas -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-layout-three-columns"></i> Columnas</span>
                    <button class="btn btn-sm btn-columna" data-bs-toggle="modal" data-bs-target="#modalAgregarColumna">
                        <i class="bi bi-plus-lg"></i> Agregar Columna
                    </button>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaColumnas" class="table table-hover table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">ID</th>
                                    <th>Nombre de Columna</th>
                                    <th>Padre</th>
                                    <th style="width: 100px;" class="text-center">Nivel</th>
                                    <th style="width: 100px;" class="text-center">Orden</th>
                                    <th style="width: 90px;" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    function renderColumnasEditable($columnas, $padreId = null, $nivel = 1, $cuadroId)
                                    {
                                        foreach ($columnas->where('idColumnaPadre', $padreId)->sortBy('orden') as $col) {
                                            echo '<tr id="columna-' . $col->idColumna . '">';
                                            echo '<td class="text-center align-middle"><small class="text-muted">' . $col->idColumna . '</small></td>';
                                            echo '<td><input type="text" class="form-control form-control-sm nivel-' . $col->nivel . '" style="margin-left:' . (($nivel - 1) * 25) . 'px; font-weight:' . ($col->idColumnaPadre ? 'normal' : 'bold') . '" value="' . e($col->nombreColumna) . '"></td>';
                                            echo '<td><select class="form-select form-select-sm"><option value="">— Ninguno —</option>';
                                            foreach ($columnas as $p) {
                                                if ($p->idColumna != $col->idColumna) {
                                                    $selected = $col->idColumnaPadre == $p->idColumna ? 'selected' : '';
                                                    echo '<option value="' . $p->idColumna . '" ' . $selected . '>' . e($p->nombreColumna) . '</option>';
                                                }
                                            }
                                            echo '</select></td>';
                                            echo '<td class="text-center"><input type="number" class="form-control form-control-sm text-center" value="' . $col->nivel . '" readonly></td>';
                                            echo '<td><input type="number" class="form-control form-control-sm text-center" value="' . $col->orden . '"></td>';
                                            echo '<td class="text-center"><button class="btn btn-danger btn-sm rounded-circle" onclick="eliminarColumna(' . $cuadroId . ', ' . $col->idColumna . ')" title="Eliminar columna"><i class="bi bi-trash"></i></button></td>';
                                            echo '</tr>';
                                            renderColumnasEditable($columnas, $col->idColumna, $nivel + 1, $cuadroId);
                                        }
                                    }
                                @endphp
                                @php renderColumnasEditable($columnas, null, 1, $cuadro->idCuadro); @endphp
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Botón Guardar -->
            <div class="text-end">
                <button class="btn btn-guardar" onclick="guardarEstructura()">
                    <i class="bi bi-save"></i> Guardar Estructura
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Fila -->
    <div class="modal fade" id="modalAgregarFila" tabindex="-1" aria-labelledby="modalAgregarFilaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-fila text-white">
                    <h5 class="modal-title" id="modalAgregarFilaLabel"><i class="bi bi-plus-lg"></i> Agregar Fila</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="formAgregarFila">
                    <div class="modal-body">
                        <input type="hidden" name="idCuadro" value="{{ $cuadro->idCuadro }}">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Fila</label>
                            <input type="text" name="nombreFila" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nivel</label>
                            <select name="nivel" class="form-select">
                                <option value="1">Nivel 1</option>
                                <option value="2">Nivel 2</option>
                                <option value="3">Nivel 3</option>
                                <option value="4">Nivel 4</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-fila" onclick="agregarFila()">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-volver" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Columna -->
    <div class="modal fade" id="modalAgregarColumna" tabindex="-1" aria-labelledby="modalAgregarColumnaLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-columna text-white">
                    <h5 class="modal-title" id="modalAgregarColumnaLabel">
                        <i class="bi bi-plus-lg"></i> Agregar Columna
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <form id="formAgregarColumna">
                    <div class="modal-body">
                        <input type="hidden" name="idCuadro" value="{{ $cuadro->idCuadro }}">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la Columna</label>
                            <input type="text" name="nombreColumna" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Columna Padre (opcional)</label>
                            <select name="idColumnaPadre" class="form-select">
                                <option value="">— Ninguna —</option>
                                @foreach ($columnas as $posiblePadre)
                                    <option value="{{ $posiblePadre->idColumna }}">{{ $posiblePadre->nombreColumna }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-columna" onclick="agregarColumna()">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-volver" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection



@section('scripts')

    <script>
        function agregarFila() {
            let formData = $("#formAgregarFila").serialize();
            let idCuadro = $("input[name=idCuadro]").val();

            $.ajax({
                url: "/cuadros/" + idCuadro + "/filas",
                type: "POST",
                data: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function (response) {
                    $("#modalAgregarFila").modal("hide");
                    Swal.fire({
                        icon: 'success',
                        title: 'Fila agregada',
                        text: response.message || 'La fila se guardó correctamente.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => location.reload(), 2000);
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Error al guardar la fila'
                    });
                }
            });
        }

        function eliminarFila(idCuadro, idFila) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/cuadros/${idCuadro}/filas/${idFila}`,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function (response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminada',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $(`#fila-${idFila}`).fadeOut(400, function () { $(this).remove(); });
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'No se pudo eliminar la fila'
                            });
                        }
                    });
                }
            });
        }


        function agregarColumna() {
            let formData = $("#formAgregarColumna").serialize();
            let idCuadro = $("input[name=idCuadro]").val();

            $.ajax({
                url: "/cuadros/" + idCuadro + "/columnas",
                type: "POST",
                data: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                beforeSend: function () {
                    $('.btn-columna').prop('disabled', true);
                },
                success: function (response) {
                    $("#modalAgregarColumna").modal("hide");
                    $("#formAgregarColumna")[0].reset();
                    $('.btn-columna').prop('disabled', false);

                    Swal.fire({
                        icon: 'success',
                        title: 'Columna agregada',
                        text: response.message || 'La columna se guardó correctamente.',
                        timer: 1800,
                        showConfirmButton: false
                    });
                    setTimeout(() => location.reload(), 1800);
                },
                error: function (xhr) {
                    $('.btn-columna').prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Error al guardar la columna'
                    });
                }
            });
        }


        function eliminarColumna(idCuadro, idColumna) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción eliminará la columna seleccionada.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/cuadros/${idCuadro}/columnas/${idColumna}`,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminada',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                $(`#columna-${idColumna}`).fadeOut(400, function () { $(this).remove(); });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'No se puede eliminar',
                                    text: response.message
                                });
                            }
                        },
                        error: function (xhr) {
                            let msg = 'No se pudo eliminar la columna.';
                            if (xhr.status === 409 || xhr.status === 404) {
                                msg = xhr.responseJSON?.message || msg;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg
                            });
                        }
                    });
                }
            });
        }


        function guardarEstructura() {
            let idCuadro = "{{ $cuadro->idCuadro }}";

            //Filas
            let filas = [];
            $('#tablaFilas tbody tr').each(function () {
                let idFila = $(this).attr('id')?.replace('fila-', '');
                if (idFila) {
                    filas.push({
                        idFila: idFila,
                        nombreFila: $(this).find('td:eq(1) input').val(),
                        nivel: $(this).find('td:eq(2) input').val(),
                        orden: $(this).find('td:eq(3) input').val()
                    });
                }
            });

            // Columnas
            let columnas = [];
            $('#tablaColumnas tbody tr').each(function () {
                let idColumna = $(this).attr('id')?.replace('columna-', '');
                if (idColumna) {
                    columnas.push({
                        idColumna: idColumna,
                        nombreColumna: $(this).find('td:eq(1) input').val(),
                        idColumnaPadre: $(this).find('td:eq(2) select').val(),
                        nivel: $(this).find('td:eq(3) input').val(),
                        orden: $(this).find('td:eq(4) input').val()
                    });
                }
            });

            $(".btn-guardar")
                .prop("disabled", true)
                .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            $.ajax({
                url: "/cuadros/" + idCuadro + "/guardar-estructura",
                type: "POST",
                data: {
                    filas: filas,
                    columnas: columnas,
                    _token: "{{ csrf_token() }}"
                },
                success: function (response) {
                    $(".btn-guardar")
                        .prop("disabled", false)
                        .html('<i class="fas fa-save"></i> Guardar Estructura');

                    Swal.fire({
                        icon: 'success',
                        title: 'Estructura guardada',
                        text: response.message || 'Las filas y columnas se actualizaron correctamente.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#374151'
                    }).then(() => location.reload());
                },
                error: function (xhr) {
                    $(".btn-guardar")
                        .prop("disabled", false)
                        .html('<i class="fas fa-save"></i> Guardar Estructura');

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Error al guardar la estructura'
                    });
                }
            });
        }

    </script>

@endsection