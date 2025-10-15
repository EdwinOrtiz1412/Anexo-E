@extends('layouts.administrador')

@section('title', 'Seguimiento del Cuadro')

@section('styles')
    <style>
        body {
            background-color: #f9fafb;
            color: #1e293b;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* --- Card principal --- */
        .card-seguimiento {
            border: none;
            border-radius: .5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            background-color: #ffffff;
        }

        .card-header-seguimiento {
            background: linear-gradient(90deg, #111827, #374151);
            color: #ffffff;
            font-weight: 600;
            font-size: 1.1rem;
            border-radius: .5rem .5rem 0 0;
            padding: 0.75rem 1rem;
        }

        .card-body-seguimiento {
            padding: 2rem;
        }

        /* --- Select --- */
        .form-select {
            border-radius: .5rem;
            border: 1px solid #d1d5db;
        }

        .form-select:focus {
            border-color: #374151;
            box-shadow: 0 0 0 0.2rem rgba(55, 65, 81, .25);
        }

        /* --- Botones --- */
        .btn-columna {
            background-color: #4B5563;
            color: #fff;
            border: none;
            border-radius: .4rem;
            padding: 0.5rem 1.25rem;
            transition: background-color 0.3s;
        }

        .btn-columna:hover {
            background-color: #374151;
        }

        .btn-volver {
            background-color: #9CA3AF;
            color: #fff;
            border: none;
            border-radius: .4rem;
            padding: 0.45rem 1.1rem;
            transition: background-color 0.3s;
        }

        .btn-volver:hover {
            background-color: #6B7280;
        }

        /* --- Sección dinámica --- */
        #seccionCategoria {
            margin-top: 2rem;
            background-color: #f3f4f6;
            border-radius: .5rem;
            padding: 1.5rem;
            display: none;
            border: 1px solid #e5e7eb;
        }

        #tituloCategoria {
            color: #374151;
            font-weight: 600;
        }

        /* Animación del icono de carga */
        .bi-arrow-repeat.spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Celdas vacias */
        .bg-empty-subtle {
            background-color: #ffe5e5 !important;
            /* rojo claro */
        }
    </style>
@endsection

@section('content')
    <div class="py-4">
        <div class="container-fluid">

            <div class="text-end mb-3">
                <a href="{{ route('anexo.cuadros.listar') }}" class="btn btn-volver">
                    <i class="bi bi-arrow-left"></i> Regresar
                </a>
            </div>

            <!-- Card principal -->
            <div class="card card-seguimiento">
                <div class="card-header-seguimiento">
                    Seguimiento: [{{ $cuadro->idCuadro }}] {{ $cuadro->titulo }}
                </div>

                <div class="card-body-seguimiento">

                    <!-- Selector de categoría alineado a la derecha -->
                    <form id="formSeguimiento">
                        @csrf
                        <div class="d-flex justify-content-end align-items-center mb-4" style="gap: 10px;">
                            <label for="categoria" class="fw-semibold mb-0">Categoría:</label>
                            <select id="categoria" name="categoria" class="form-select" style="width: 300px;" required>
                                <option value="">Seleccione una categoría...</option>
                                @foreach ($categorias as $cat)
                                    <option value="{{ $cat->idCategoria }}">{{ $cat->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    <div id="seccionCategoria">
                        <h6 id="tituloCategoria" class="mb-3"></h6>

                        <div class="d-flex justify-content-end mb-4" style="gap: 10px;">
                            <button type="button" class="btn btn-outline-primary" id="btnDescargarEstructura"
                                onclick="descargarEstructura()">
                                <i class="bi bi-download"></i> Descargar Cuadro
                            </button>

                            <button type="button" class="btn btn-outline-secondary" id="btnCargarEstructura"
                                onclick="cargarEstructura()" style="display: none;">
                                <i class="bi bi-upload"></i> Cargar Datos
                            </button>

                            <input type="file" id="inputEstructura" accept=".xlsx" style="display: none;">
                        </div>

                        <div class="card shadow-sm mb-3">
                            <div class="card-header" style="background-color:#374151;color:white;">
                                <h6 class="m-0">Datos de seguimiento</h6>
                            </div>
                            <div class="card-body" id="contenedorCuadro">
                                <p class="text-muted text-center">Este espacio mostrará los detalles dinámicos del
                                    seguimiento del cuadro.</p>
                            </div>
                        </div>

                        <div id="pieCuadro" class="mt-4" style="display:none;">
                            <div class="card shadow-sm">
                                <div class="card-header" style="background-color:#e5e7eb;">
                                    <strong>Pie del cuadro</strong>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="notaCuadro" class="form-label fw-semibold">Notas:</label>
                                        <textarea id="notaCuadro" class="form-control" rows="3"
                                            placeholder="Escriba aquí las notas o aclaraciones que considere pertinentes..."></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div id="listaLlamadas" class="mt-2 small"></div>

                                            <p class="mb-1">
                                                <strong><span id="pieCategoria">—</span></strong>:
                                                <span id="pieDescripcion">—</span>
                                            </p>

                                            <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                                                <label for="pieFuente" class="form-label mb-0 fw-semibold">Fuente:</label>
                                                <input type="text" id="pieFuente"
                                                    class="form-control form-control-sm flex-grow-1"
                                                    placeholder="Ingrese la fuente o dependencia..." />
                                            </div>

                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-success" id="btnGuardarCambios" onclick="guardarCambios()">
                                <i class="bi bi-save"></i> Guardar cambios
                            </button>
                        </div>
                    </div>


                </div>
            </div>

        </div>
    </div>

    <!-- Modal para agregar llamada -->
    <div class="modal fade" id="modalLlamada" tabindex="-1" aria-labelledby="modalLlamadaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalLlamadaLabel">
                        Agregar llamada <span id="ll_tituloElemento" class="fw-semibold text-white fst-italic"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formLlamada">
                        <input type="hidden" id="ll_idFila">
                        <input type="hidden" id="ll_idColumna">

                        <div class="mb-3">
                            {{-- <label for="ll_nota" class="form-label">Nota o aclaración:</label> --}}
                            <textarea id="ll_nota" class="form-control" rows="4" required
                                placeholder="Escriba aquí la nota o aclaración relacionada con esta fila o columna...">
                                                                                </textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-danger d-none" id="btnEliminarLlamada" onclick="eliminarLlamada()">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardarLlamada" onclick="guardarLlamada()">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                </div>

            </div>
        </div>
    </div>


@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            inicializarEventosSeguimiento();
        });
        function descargarEstructura() {
            const idCategoria = $('#categoria').val();
            const idCuadro = "{{ $cuadro->idCuadro }}";

            if (!idCategoria) {
                alert('Por favor, seleccione una categoría antes de descargar.');
                return;
            }

            const url = "{{ route('seguimiento.descargarEstructuraExcel', ['idCuadro' => $cuadro->idCuadro, 'idCategoria' => 'ID_CATEGORIA']) }}";
            const finalUrl = url.replace('ID_CATEGORIA', idCategoria);
            window.location.href = finalUrl;
        }

        function cargarEstructura() {
            const idCategoria = $('#categoria').val();
            const idCuadro = "{{ $cuadro->idCuadro }}";

            if (!idCategoria) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Atención',
                    text: 'Por favor, seleccione una categoría antes de cargar la estructura.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            $('#inputEstructura').off('change').on('change', function () {
                if (this.files.length === 0) return;

                const archivo = this.files[0];
                const formData = new FormData();
                formData.append('archivo', archivo);

                const url = `/seguimiento/${idCuadro}/categoria/${idCategoria}/importar`;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    beforeSend: function () {
                        $('#btnCargarEstructura').prop('disabled', true)
                            .html('<i class="bi bi-arrow-repeat spin"></i> Cargando...');
                    },
                    success: function (response) {
                        if (!response.success) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'No se pudo leer el archivo.',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }

                        $('td.celda-seguimiento input').removeClass('bg-warning-subtle');

                        // Rellenar las celdas con los valores importados
                        response.datos.forEach(item => {
                            $(`td.celda-seguimiento[data-id-fila="${item.idFila}"][data-id-columna="${item.idColumna}"] input`)
                                .val(item.valor)
                                .addClass('bg-warning-subtle'); // resaltar las recién importadas
                        });

                        //Marcar celdas vacias y con valor
                        // Marcar celdas vacías y con valor
                        $('td.celda-seguimiento input').each(function () {
                            const valor = $(this).val().trim();
                            if (valor === '') {
                                // Si está vacía rojo
                                $(this).addClass('bg-empty-subtle');
                            } else {
                                // Si tiene valor amarillo
                                $(this).addClass('bg-warning-subtle');
                            }
                        });


                        Swal.fire({
                            icon: 'success',
                            title: '¡Cuadro cargado correctamente!',
                            text: 'Los datos del archivo se han colocado en la estructura.',
                            confirmButtonText: 'OK'
                        });
                    },
                    error: function (xhr) {
                        const mensaje = xhr.responseJSON?.message || 'Error al procesar el archivo.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: mensaje,
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function () {
                        $('#btnCargarEstructura').prop('disabled', false)
                            .html('<i class="bi bi-upload"></i> Cargar estructura');
                        $('#inputEstructura').val('');
                    }
                });
            });

            $('#inputEstructura').click();
        }


        function inicializarEventosSeguimiento() {
            const $selectorCategoria = $('#categoria');
            if ($selectorCategoria.length) {
                $selectorCategoria.on('change', function () {
                    manejarCambioCategoria($(this));
                });
            }
        }
        // Detecta cambios en las celdas y actualiza el color en tiempo real
        $(document).on('input', 'td.celda-seguimiento input', function () {
            const valor = $(this).val().trim();
            if (valor === '') {
                $(this).removeClass('bg-warning-subtle').addClass('bg-empty-subtle');
            } else {
                $(this).removeClass('bg-empty-subtle').addClass('bg-warning-subtle');
            }
        });


        function manejarCambioCategoria($select) {
            const idCategoria = $select.val();
            const nombreCategoria = $select.find('option:selected').text();

            const $seccion = $('#seccionCategoria');
            const $titulo = $('#tituloCategoria');

            if (!idCategoria) {
                $seccion.hide();
                $titulo.text('');
                return;
            }

            $titulo.text(`Categoría seleccionada: ${nombreCategoria}`);
            $seccion.show();

            cargarDatosSeguimiento(idCategoria);
        }

        function cargarDatosSeguimiento(idCategoria) {
            // const $contenedor = $('#seccionCategoria .card-body');
            const $contenedor = $('#contenedorCuadro');
            $('#pieCuadro').hide();
            $contenedor.empty();
            $contenedor.html(`
                                                                                                                    <div class="d-flex flex-column align-items-center justify-content-center my-5 py-5 text-center">
                                                                                                                        <div class="spinner-border text-secondary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                                                                                                            <span class="visually-hidden">Cargando...</span>
                                                                                                                        </div>
                                                                                                                        <h6 class="fw-semibold text-secondary mb-1">Cargando cuadro</h6>
                                                                                                                    </div>
                                                                                                                `);

            $.ajax({
                url: `/seguimiento/{{ $cuadro->idCuadro }}/categoria/${idCategoria}`,
                method: 'GET',
                dataType: 'json',
                success: function (data) {
                    if (!data.success) {
                        $contenedor.html('<p class="text-danger text-center">Error al obtener los datos.</p>');
                        return;
                    }

                    const esEditable = data.categoria && data.categoria.solicitado === 1;

                    // Mostrar u ocultar el botón de Cargar estructura
                    if (esEditable) {
                        $('#btnCargarEstructura').show();
                        $('#btnGuardarCambios').show();
                    } else {
                        $('#btnCargarEstructura').hide();
                        $('#btnGuardarCambios').hide();
                    }

                    // Bloquear o habilitar edición del campo de notas
                    if (esEditable) {
                        $('#notaCuadro, #pieFuente').prop('readonly', false)
                            .removeClass('bg-light text-muted');
                    } else {
                        $('#notaCuadro, #pieFuente').prop('readonly', true)
                            .addClass('bg-light text-muted');
                    }

                    // Guardar las llamadas en memoria global
                    window.llamadasCuadro = data.llamadas || [];
                    // Guardamos en memoria global si se puede editar
                    window.categoriaEditable = esEditable;
                    // Guardar columnas y filas globalmente para usar al actualizar llamadas
                    window.columnasCuadro = data.columnas;
                    window.filasCuadro = data.filas;



                    // construir tabla
                    $contenedor.html(construirTablaCuadro(data));
                    // Mostrar pie del cuadro con información
                    $('#pieCuadro').show();
                    $('#pieCategoria').text(data.categoria.nombre || '—');
                    $('#pieDescripcion').text(data.categoria.descripcion || '—');
                    // $('#pieDependencia').text(data.cuadro_dependencia || '—');

                    $('#notaCuadro').val(data.nota || '');
                    $('#pieFuente').val(data.fuente || '');

                    // Si hay llamadas existentes, marcarlas visualmente
                    if (data.llamadas && data.llamadas.length > 0) {
                        data.llamadas.forEach(ll => {
                            marcarBotonConLlamada(ll.idFila, ll.idColumna);
                        });
                    }
                    // Mostrar listado de llamadas en la sección inferior
                    actualizarListaLlamadas();





                    // si no es editable
                    if (!esEditable) {
                        deshabilitarCeldasEdicion();

                        $contenedor.prepend(`
                                                                                                                                                                                    <div class="alert alert-warning text-center mb-3">
                                                                                                                                                                                        La categoría <strong>${data.categoria.nombre}</strong> no está solicitada, por lo tanto no puede editarse.
                                                                                                                                                                                    </div>
                                                                                                                                                                                `);
                    }
                },
                error: function () {
                    $contenedor.html('<p class="text-danger text-center">Error al cargar el cuadro.</p>');
                }
            });
        }

        function deshabilitarCeldasEdicion() {
            $('.celda-seguimiento').addClass('bg-light text-muted').css('pointer-events', 'none');
        }

        function construirTablaCuadro(data) {
            data.columnas.sort((a, b) => a.orden - b.orden);
            const columnasRaiz = data.columnas.filter(c => !c.idColumnaPadre);
            const niveles = getMaxNivel(data.columnas);

            let html = '<div class="table-responsive"><table class="table table-bordered align-middle">';
            html += '<thead>';

            const filasHeader = Array.from({ length: niveles }, () => []);
            let columnasHojaOrdenadas = [];

            $.each(columnasRaiz, function (_, col) {
                llenarCabeceraRecursiva(col, data.columnas, filasHeader, columnasHojaOrdenadas);
            });

            $.each(filasHeader, function (i, colsNivel) {
                html += '<tr>';
                if (i === 0) {
                    html += `<th rowspan="${niveles}" class="bg-dark text-white text-center align-middle" style="min-width:180px;">Filas / Columnas</th>`;
                }

                $.each(colsNivel, function (_, col) {
                    const hijos = data.columnas.filter(c => c.idColumnaPadre === col.idColumna);
                    const colspan = hijos.length > 0 ? contarDescendientes(data.columnas, col.idColumna) : 1;
                    const rowspan = hijos.length === 0 ? (niveles - col.nivel + 1) : 1;

                    html += `
                                                                                                                                                                                <th class="text-center bg-secondary text-white align-middle"
                                                                                                                                                                                    colspan="${colspan}" rowspan="${rowspan}">
                                                                                                                                                                                    ${col.nombreColumna}
                                                                                                            <button type="button" class="btn btn-sm btn-outline-light ms-2"
                                                                                                              title="Agregar llamada a esta columna"
                                                                                                              onclick="abrirModalLlamada(null, ${col.idColumna})">
                                                                                                              <i class="bi-plus-circle"></i>
                                                                                                            </button>

                                                                                                                                                                                </th>`;
                });
                html += '</tr>';
            });

            html += '</thead><tbody>';

            const esEditable = data.categoria && data.categoria.solicitado === 1;

            data.filas.sort((a, b) => a.orden - b.orden).forEach(fila => {
                const margen = (fila.nivel - 1) * 25;
                html += `<tr>
                                                                                                              <th style="padding-left:${margen}px;" class="bg-light">
                                                                                                                ${fila.nombreFila}
                                                                                                                <button type="button" class="btn btn-sm btn-outline-primary ms-2"
                                                                                                                  title="Agregar llamada a esta fila"
                                                                                                                  onclick="abrirModalLlamada(${fila.idFila}, null)">
                                                                                                                  <i class="bi-plus-circle"></i>
                                                                                                                </button>
                                                                                                              </th>`;

                $.each(columnasHojaOrdenadas, function (_, col) {
                    const celda = data.celdas.find(
                        c => Number(c.idFila) === Number(fila.idFila) &&
                            Number(c.idColumna) === Number(col.idColumna)
                    );

                    let valor = '';
                    if (celda && celda.valor_numero !== null && celda.valor_numero !== '') {
                        const raw = celda.valor_numero;

                        // Si es un número válido → formatear
                        if (!isNaN(raw) && raw.trim() !== '') {
                            const num = parseFloat(raw);
                            valor = Number.isInteger(num)
                                ? num.toString()
                                : num.toString().replace(/0+$/, '').replace(/\.$/, '');
                        } else {
                            // Si no es número (por ejemplo "NA" o "ND") → mostrar texto tal cual
                            valor = raw;
                        }
                    }


                    html += `
                                                                                                                                                                                <td class="text-end celda-seguimiento"
                                                                                                                                                                                    style="text-align:right; padding-right:8px;"
                                                                                                                                                                                    data-id-fila="${fila.idFila}"
                                                                                                                                                                                    data-id-columna="${col.idColumna}"
                                                                                                                                                                                    data-id-cuadro="${data.idCuadro}"
                                                                                                                                                                                    data-id-categoria="${data.idCategoria}">
                                                                                                                                                                                    <input type="text"
                                                                                                                                                                                           class="form-control form-control-sm text-end"
                                                                                                                                                                                           step="any"
                                                                                                                                                                                           value="${valor}"
                                                                                                                                                                                           ${esEditable ? '' : 'disabled'}
                                                                                                                                                                                           onblur="guardarValorCelda(this)">
                                                                                                                                                                                </td>`;
                });

                html += '</tr>';
            });

            html += '</tbody></table></div>';
            return html;
        }

        function llenarCabeceraRecursiva(columna, todas, filasHeader, hojasOrdenadas) {
            filasHeader[columna.nivel - 1].push(columna);

            const hijos = todas
                .filter(c => c.idColumnaPadre === columna.idColumna)
                .sort((a, b) => a.orden - b.orden);

            if (hijos.length > 0) {
                $.each(hijos, function (_, h) {
                    llenarCabeceraRecursiva(h, todas, filasHeader, hojasOrdenadas);
                });
            } else {
                hojasOrdenadas.push(columna);
            }
        }

        function contarDescendientes(columnas, idPadre) {
            const hijos = columnas.filter(c => c.idColumnaPadre === idPadre);
            if (hijos.length === 0) return 1;
            let total = 0;
            $.each(hijos, function (_, h) {
                total += contarDescendientes(columnas, h.idColumna);
            });
            return total;
        }

        function getMaxNivel(columnas) {
            return Math.max(...columnas.map(c => c.nivel));
        }

        function guardarCambios() {
            const idCategoria = $('#categoria').val();
            const idCuadro = "{{ $cuadro->idCuadro }}";

            // Recolectar todas las celdas con valor
            const celdas = [];
            $('td.celda-seguimiento').each(function () {
                const $input = $(this).find('input');
                const valor = $input.val().trim();
                if (valor !== '') {
                    celdas.push({
                        idFila: $(this).data('id-fila'),
                        idColumna: $(this).data('id-columna'),
                        valor: valor
                    });
                }
            });

            const nota = $('#notaCuadro').val().trim();
            const fuente = $('#pieFuente').val().trim(); // ✅ mover aquí

            if (celdas.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sin datos',
                    text: 'No hay valores para guardar.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            $.ajax({
                url: `/seguimiento/${idCuadro}/categoria/${idCategoria}/guardar`,
                type: 'POST',
                data: JSON.stringify({ celdas, nota, fuente }), // ✅ ahora correcto
                contentType: 'application/json',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                beforeSend: function () {
                    $('#btnGuardarCambios')
                        .prop('disabled', true)
                        .html('<i class="bi bi-arrow-repeat spin"></i> Guardando...');
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Guardado!',
                            text: response.message,
                            confirmButtonText: 'OK'
                        });
                        $('td.celda-seguimiento input').removeClass('bg-warning-subtle');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'No se pudieron guardar los datos.',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function (xhr) {
                    const mensaje = xhr.responseJSON?.message || 'Error al guardar los datos.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: mensaje,
                        confirmButtonText: 'OK'
                    });
                },
                complete: function () {
                    $('#btnGuardarCambios')
                        .prop('disabled', false)
                        .html('<i class="bi bi-save"></i> Guardar cambios');
                }
            });
        }


        function abrirModalLlamada(idFila = null, idColumna = null) {
            let nombreElemento = '';
            let tipoElemento = '';

            // Buscar nombre de fila o columna
            if (idFila) {
                const $thFila = $(`th button[onclick*="(${idFila}, null)"]`).closest('th');
                nombreElemento = $thFila.contents().filter(function () {
                    return this.nodeType === 3;
                }).text().trim();
                tipoElemento = 'fila';
            } else if (idColumna) {
                const $thColumna = $(`th button[onclick*="(null, ${idColumna})"]`).closest('th');
                nombreElemento = $thColumna.contents().filter(function () {
                    return this.nodeType === 3;
                }).text().trim();
                tipoElemento = 'columna';
            }

            // Título del modal
            const textoTitulo = nombreElemento
                ? `para la ${tipoElemento}: ${nombreElemento}`
                : '';
            $('#ll_tituloElemento').text(textoTitulo);

            $('#ll_idFila').val(idFila);
            $('#ll_idColumna').val(idColumna);

            // Buscar si existe una llamada guardada
            let notaExistente = '';
            let existeLlamada = false;

            if (window.llamadasCuadro && window.llamadasCuadro.length > 0) {
                const llamada = window.llamadasCuadro.find(l =>
                    Number(l.idFila) === Number(idFila || 0) &&
                    Number(l.idColumna) === Number(idColumna || 0)
                );

                if (llamada) {
                    notaExistente = llamada.nota || '';
                    existeLlamada = true;
                }
            }

            $('#ll_nota').val(notaExistente);

            // Determinar si la categoría actual es editable
            const esEditable = window.categoriaEditable === true;

            // Controlar edición y botones según el estad
            if (esEditable) {
                $('#ll_nota').prop('readonly', false).removeClass('bg-light text-muted');

                if (existeLlamada) {
                    $('#btnEliminarLlamada').removeClass('d-none');
                } else {
                    $('#btnEliminarLlamada').addClass('d-none');
                }

                $('#btnGuardarLlamada').removeClass('d-none');
            } else {
                $('#ll_nota').prop('readonly', true).addClass('bg-light text-muted');
                $('#btnEliminarLlamada').addClass('d-none');
                $('#btnGuardarLlamada').addClass('d-none');
            }

            // Mostrar el modal
            $('#modalLlamada').modal('show');
        }



        function guardarLlamada() {
            const idCuadro = "{{ $cuadro->idCuadro }}";
            const idCategoria = $('#categoria').val();
            const idFila = $('#ll_idFila').val() || null;
            const idColumna = $('#ll_idColumna').val() || null;
            const nota = $('#ll_nota').val().trim();

            if (!nota) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nota vacía',
                    text: 'Por favor, escriba una nota antes de guardar.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            $.ajax({
                url: `/seguimiento/${idCuadro}/categoria/${idCategoria}/llamada/agregar`,
                method: 'POST',
                data: {
                    idFila,
                    idColumna,
                    nota,
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: response.message,
                            confirmButtonText: 'OK'
                        });

                        $('#modalLlamada').modal('hide');

                        marcarBotonConLlamada(idFila, idColumna);

                        if (!window.llamadasCuadro) window.llamadasCuadro = [];
                        const index = window.llamadasCuadro.findIndex(l =>
                            Number(l.idFila) === Number(idFila || 0) &&
                            Number(l.idColumna) === Number(idColumna || 0)
                        );
                        if (index >= 0) {
                            window.llamadasCuadro[index].nota = nota;
                        } else {
                            window.llamadasCuadro.push({ idFila, idColumna, nota });
                        }

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'No se pudo guardar la llamada.',
                            confirmButtonText: 'OK'
                        });
                    }
                    actualizarListaLlamadas();
                },
                error: function (xhr) {
                    const mensaje = xhr.responseJSON?.message || 'Error al guardar la llamada.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: mensaje,
                        confirmButtonText: 'OK'
                    });
                }
            });
        }


        function marcarBotonConLlamada(idFila, idColumna) {
            let $btn;
            if (idFila) {
                $btn = $(`th button[onclick*="(${idFila}, null)"]`);
            } else if (idColumna) {
                $btn = $(`th button[onclick*="(null, ${idColumna})"]`);
            }

            if ($btn && $btn.length) {
                $btn.removeClass('btn-outline-primary btn-outline-light')
                    .addClass('btn-success text-white')
                    .attr('title', 'Llamada guardada o actualizada');
            }
        }

        function eliminarLlamada() {
            const idCuadro = "{{ $cuadro->idCuadro }}";
            const idCategoria = $('#categoria').val();
            const idFila = $('#ll_idFila').val() || null;
            const idColumna = $('#ll_idColumna').val() || null;

            Swal.fire({
                icon: 'warning',
                title: '¿Eliminar llamada?',
                text: 'Esta acción eliminará la nota asociada permanentemente.',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                reverseButtons: true
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/seguimiento/${idCuadro}/categoria/${idCategoria}/llamada/eliminar`,
                        method: 'POST',
                        data: {
                            idFila,
                            idColumna,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminada',
                                    text: response.message,
                                    confirmButtonText: 'OK'
                                });

                                // Cerrar el modal
                                $('#modalLlamada').modal('hide');

                                // Quitar color del botón en la tabla
                                const $btn = idFila
                                    ? $(`th button[onclick*="(${idFila}, null)"]`)
                                    : $(`th button[onclick*="(null, ${idColumna})"]`);

                                $btn.removeClass('btn-success text-white')
                                    .addClass(idFila ? 'btn-outline-primary' : 'btn-outline-light')
                                    .attr('title', 'Agregar llamada');

                                // Quitar del arreglo local de llamadas
                                if (window.llamadasCuadro) {
                                    window.llamadasCuadro = window.llamadasCuadro.filter(l =>
                                        !(Number(l.idFila) === Number(idFila || 0) &&
                                            Number(l.idColumna) === Number(idColumna || 0))
                                    );
                                }

                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message || 'No se pudo eliminar la llamada.',
                                    confirmButtonText: 'OK'
                                });
                            }
                            actualizarListaLlamadas();
                        },
                        error: function (xhr) {
                            const mensaje = xhr.responseJSON?.message || 'Error al eliminar la llamada.';
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: mensaje,
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        function obtenerNumeroFila(idFila, filas) {
            const filasOrdenadas = [...filas].sort((a, b) => a.orden - b.orden);
            const index = filasOrdenadas.findIndex(f => Number(f.idFila) === Number(idFila));
            return index >= 0 ? index + 10 : '';
        }

        function obtenerLetraColumna(idColumna, columnas) {
            if (!idColumna) return '';

            const columnasOrdenadas = [...columnas].sort((a, b) => a.orden - b.orden);

            const colPositions = {};
            let currentIndex = 3;

            const asignarPosiciones = (cols, padreId = null) => {
                const hijos = columnasOrdenadas.filter(c => c.idColumnaPadre === padreId);
                hijos.sort((a, b) => a.orden - b.orden);

                for (const col of hijos) {
                    const nietos = columnasOrdenadas.filter(c => c.idColumnaPadre === col.idColumna);

                    if (nietos.length === 0) {
                        colPositions[col.idColumna] = currentIndex;
                        currentIndex++;
                    } else {
                        asignarPosiciones(columnasOrdenadas, col.idColumna);

                        const hijosIds = nietos.map(h => h.idColumna);
                        const posInicio = colPositions[hijosIds[0]];
                        const posFin = colPositions[hijosIds[hijosIds.length - 1]];
                        colPositions[col.idColumna] = { start: posInicio, end: posFin };
                    }
                }
            };

            asignarPosiciones(columnasOrdenadas, null);

            const pos = colPositions[idColumna];
            let excelIndex;

            if (typeof pos === 'object' && pos.start) {
                excelIndex = pos.start;
            } else {
                excelIndex = pos;
            }

            if (!excelIndex) return '';

            let result = '';
            let n = excelIndex;
            while (n > 0) {
                const remainder = (n - 1) % 26;
                result = String.fromCharCode(65 + remainder) + result;
                n = Math.floor((n - 1) / 26);
            }

            return result;
        }


        function actualizarListaLlamadas() {
            const $listaLlamadas = $('#listaLlamadas');
            $listaLlamadas.empty();

            if (!window.llamadasCuadro || window.llamadasCuadro.length === 0) {
                return;
            }

            const columnas = window.columnasCuadro || [];
            const filas = window.filasCuadro || [];

            const llamadasColumna = window.llamadasCuadro.filter(ll => ll.idFila === null && ll.idColumna !== null);
            const llamadasFilaCelda = window.llamadasCuadro.filter(ll => ll.idFila !== null);

            llamadasColumna.sort((a, b) => {
                const letraA = obtenerLetraColumna(a.idColumna, columnas);
                const letraB = obtenerLetraColumna(b.idColumna, columnas);
                return letraA.localeCompare(letraB);
            });

            llamadasFilaCelda.sort((a, b) => {
                const letraA = obtenerLetraColumna(a.idColumna, columnas);
                const letraB = obtenerLetraColumna(b.idColumna, columnas);
                const numA = parseInt(obtenerNumeroFila(a.idFila, filas)) || 0;
                const numB = parseInt(obtenerNumeroFila(b.idFila, filas)) || 0;

                // primero por columna
                if (letraA < letraB) return -1;
                if (letraA > letraB) return 1;

                // luego por fila
                return numA - numB;
            });

            const llamadasOrdenadas = [...llamadasColumna, ...llamadasFilaCelda];

            let html = '<ul class="list-unstyled mb-0">';
            llamadasOrdenadas.forEach((ll) => {
                const letraColumna = obtenerLetraColumna(ll.idColumna, columnas) || 'B';
                const numeroFila = obtenerNumeroFila(ll.idFila, filas) || '1';
                let referencia = '';

                // Siempre mostrar letra + número + barra
                referencia = `${letraColumna}${numeroFila} /`;

                html += `
                <li class="mb-1">
                    <strong>${referencia}</strong>
                    <span>${ll.nota}</span>
                </li>
            `;
            });


            html += '</ul>';
            $listaLlamadas.html(html);
        }


    </script>
@endsection