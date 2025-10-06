<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cuadros de Anexo Estadístico / Listado') }}
        </h2>
    </x-slot>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css" rel="stylesheet">

    <style>
        table.table-list thead {
            background-color: #374151;
            color: #fff;
        }

        table.table-list td,
        table.table-list th {
            padding: 10px;
            vertical-align: middle;
            border: 1px solid #e5e7eb;
        }

        table.table-list tbody tr:hover {
            background-color: #f9fafb;
        }

        .botones-opciones {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
            justify-content: center;
        }

        .botones-opciones a {
            width: 100%;
            text-align: center;
        }

        /* Estilos personalizados coherentes en todos los modales */
        .bg-columna {
            background-color: #4B5563 !important;
            /* Gris acero */
        }

        .btn-columna {
            background-color: #4B5563 !important;
            /* Gris acero */
            color: #fff !important;
            border: none;
        }

        .btn-guardar-cuadro {
            background-color: #4B5563 !important;
            /* Gris acero */
            color: #fff !important;
            border: none;
        }

        .btn-columna:hover {
            background-color: #374151 !important;
            /* Gris oscuro */
        }

        .btn-volver {
            background-color: #9CA3AF !important;
            /* Gris claro */
            color: #fff !important;
            border: none;
        }

        .btn-volver:hover {
            background-color: #6B7280 !important;
            /* Gris medio */
        }
    </style>

    <div class="py-4">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-columna" onclick="abrirModalNuevoCuadro()">
                            <i class="fas fa-plus"></i> Agregar Cuadro
                        </button>

                        <button class="btn btn-primary" data-toggle="modal" data-target="#modalImportar">
                            <i class="fas fa-file-import"></i> Importar Estructura
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-list" id="tablaCuadros"
                            style="width:100%">
                            <thead>
                                <tr class="text-center">
                                    <th>ID</th>
                                    <th>Número</th>
                                    <th>Título</th>
                                    <th>Eje PED</th>
                                    <th>Tema PED</th>
                                    <th>Dependencia</th>
                                    <th>Grupo</th>
                                    <th>Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cuadros as $c)
                                    <tr>
                                        <td class="text-center">{{ $c->idCuadro }}</td>
                                        <td class="text-center">{{ $c->numero }}</td>
                                        <td>{{ $c->titulo }}</td>
                                        <td>{{ $c->ejePEDDescripcion ?? '—' }}</td>
                                        <td>{{ $c->temaPEDDescripcion ?? '—' }}</td>
                                        <td>{{ $c->dependenciaSiglas ?? '—' }}</td>
                                        <td>{{ $c->grupoNombre ?? '—' }}</td>
                                        <td>
                                            <div class="botones-opciones">
                                                <button class="btn btn-sm btn-success text-white"
                                                    onclick="abrirModalDatosGenerales({{ $c->idCuadro }})">
                                                    <i class="fas fa-info-circle"></i> Datos Generales
                                                </button>
                                                <a href="{{ route('cuadros.estructura', $c->idCuadro) }}"
                                                    class="btn btn-sm btn-primary text-white">
                                                    <i class="fas fa-table"></i> Estructura
                                                </a>
                                            </div>


                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No hay cuadros registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!--  Modal para Importar Estructura -->
    <div class="modal fade" id="modalImportar" tabindex="-1" aria-labelledby="modalImportarLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <!-- Encabezado -->
                <div class="modal-header bg-columna text-white">
                    <h5 class="modal-title" id="modalImportarLabel">
                        <i class="fas fa-file-import"></i> Importar Estructura desde Excel
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formImportarEstructura" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="archivo">Archivo Excel</label>
                            <input type="file" name="archivo" id="archivo" class="form-control" accept=".xls,.xlsx"
                                required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-columna" onclick="importarEstructura()">
                            <i class="fas fa-upload"></i> Importar
                        </button>
                        <button type="button" class="btn btn-volver" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    <!-- Modal para Agregar / Editar Cuadro -->
    <div class="modal fade" id="modalAgregarCuadro" tabindex="-1" aria-labelledby="modalAgregarCuadroLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header bg-columna text-white">
                    <h5 class="modal-title" id="modalAgregarCuadroLabel">
                        <i class="fas fa-plus"></i> Agregar Nuevo Cuadro
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formAgregarCuadro">
                    @csrf
                    <input type="hidden" name="idCuadro" id="idCuadro">

                    <div class="modal-body">

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Número de Cuadro</label>
                                <input type="text" name="numero" class="form-control" required>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Control</label>
                                <input type="text" name="control" class="form-control" required>
                            </div>

                            <div class="form-group col-md-8">
                                <label>Título del Cuadro</label>
                                <input type="text" name="titulo" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Eje PED</label>
                                <select name="idEjePED" id="idEjePED" class="form-control" required>
                                    <option value="">Seleccione el eje... </option>
                                    @foreach($ejes as $eje)
                                        <option value="{{ $eje->idEjePED }}">
                                            {{ $eje->ejePEDClave ? $eje->ejePEDClave . ' - ' : '' }}{{ $eje->ejePEDDescripcion }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Tema PED</label>
                                <select name="idTemaPED" id="idTemaPED" class="form-control" required>
                                    <option value="">Seleccione el tema...</option>
                                </select>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Dependencia</label>
                                <select name="idDependencia" class="form-control" required>
                                    <option value="">Seleccione... </option>
                                    @foreach($dependencias as $dep)
                                        <option value="{{ $dep->idDependencia }}">{{ $dep->dependenciaSiglas }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label>Grupo</label>
                                <select name="idGrupo" class="form-control">
                                    <option value="">Seleccione...</option>
                                    @foreach($grupos as $grupo)
                                        <option value="{{ $grupo->idGrupo }}">{{ $grupo->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-guardar-cuadro" onclick="guardarCuadro()">
                            <i class="fas fa-save"></i> Guardar
                        </button>

                        <button type="button" class="btn btn-volver" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {

            if ($('#tablaCuadros tbody tr').length > 0 &&
                !$('#tablaCuadros tbody td').first().attr('colspan')) {
                $('#tablaCuadros').DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50],
                    order: [[0, 'asc']],
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
                });
            }

            function cargarTemasPorEje(idEje) {
                const $temaSelect = $('#idTemaPED');

                $temaSelect.prop('disabled', true).html('<option value="">Cargando...</option>');

                if (idEje) {
                    $.ajax({
                        url: '/temas-por-eje/' + idEje,
                        type: 'GET',
                        success: function (temas) {
                            if (temas.length > 0) {
                                let options = '<option value="">— Seleccione —</option>';
                                temas.forEach(t => {
                                    options += `<option value="${t.idTemaPED}">
                                ${t.temaPEDClave ? t.temaPEDClave + ' - ' : ''}${t.temaPEDDescripcion}
                            </option>`;
                                });
                                $temaSelect.html(options).prop('disabled', false);
                            } else {
                                $temaSelect.html('<option value="">— No hay temas para este eje —</option>');
                            }
                        },
                        error: function () {
                            $temaSelect.html('<option value="">Error al cargar temas</option>');
                        }
                    });
                } else {
                    $temaSelect.html('<option value="">— Seleccione un Eje primero —</option>');
                }
            }

            $(document).on('change', '#idEjePED', function () {
                let idEje = $(this).val();
                cargarTemasPorEje(idEje);
            });

        });

        function abrirModalNuevoCuadro() {
            const modal = $("#modalAgregarCuadro");
            const titulo = modal.find(".modal-title");
            const botonGuardar = modal.find(".btn-guardar-cuadro");

            $("#formAgregarCuadro")[0].reset();
            $("#idCuadro").val('');

            $("#idTemaPED").html('<option value="">Seleccione el tema...</option>');
            $("#idEjePED, select[name='idDependencia'], select[name='idGrupo']").prop('disabled', false);

            titulo.html('<i class="fas fa-plus"></i> Agregar Nuevo Cuadro');
            botonGuardar.html('<i class="fas fa-save"></i> Guardar');
            botonGuardar.prop("disabled", false);

            modal.modal("show");
        }


        function guardarCuadro() {
            let datosFormulario = $("#formAgregarCuadro").serialize();

            $.ajax({
                url: "/cuadros",
                type: "POST",
                data: datosFormulario,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },

                beforeSend: function () {
                    $(".btn-guardar-cuadro")
                        .prop("disabled", true)
                        .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
                },

                success: function (respuesta) {
                    $(".btn-guardar-cuadro")
                        .prop("disabled", false)
                        .html('<i class="fas fa-save"></i> Guardar');

                    Swal.fire({
                        icon: 'success',
                        title: 'Cuadro guardado',
                        text: respuesta.message || 'El cuadro se guardó correctamente.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#4B5563'
                    }).then(() => {
                        const tabla = $('#tablaCuadros').DataTable();
                        const idCuadro = respuesta.cuadro.idCuadro;

                        let filaExistente = tabla
                            .rows()
                            .nodes()
                            .to$()
                            .filter((_, tr) => $(tr).find('td:first').text().trim() == idCuadro);

                        if (filaExistente.length > 0) {
                            // Actualizar fila existente
                            tabla.row(filaExistente).data([
                                respuesta.cuadro.idCuadro,
                                respuesta.cuadro.numero,
                                respuesta.cuadro.titulo,
                                respuesta.cuadro.ejePEDDescripcion ?? '—',
                                respuesta.cuadro.temaPEDDescripcion ?? '—',
                                respuesta.cuadro.dependenciaSiglas ?? '—',
                                respuesta.cuadro.grupoNombre ?? '—',
                                `
                        <div class="botones-opciones">
                            <button class="btn btn-sm btn-success text-white"
                                onclick="abrirModalDatosGenerales(${respuesta.cuadro.idCuadro})">
                                <i class="fas fa-info-circle"></i> Datos Generales
                            </button>
                            <a href="/cuadros/${respuesta.cuadro.idCuadro}/estructura" 
                               class="btn btn-sm btn-primary text-white">
                               <i class="fas fa-table"></i> Estructura
                            </a>
                        </div>
                        `
                            ]).draw(false);
                        } else {
                            // Si no existe, agregar nueva
                            tabla.row.add([
                                respuesta.cuadro.idCuadro,
                                respuesta.cuadro.numero,
                                respuesta.cuadro.titulo,
                                respuesta.cuadro.ejePEDDescripcion ?? '—',
                                respuesta.cuadro.temaPEDDescripcion ?? '—',
                                respuesta.cuadro.dependenciaSiglas ?? '—',
                                respuesta.cuadro.grupoNombre ?? '—',
                                `
                        <div class="botones-opciones">
                            <button class="btn btn-sm btn-success text-white"
                                onclick="abrirModalDatosGenerales(${respuesta.cuadro.idCuadro})">
                                <i class="fas fa-info-circle"></i> Datos Generales
                            </button>
                            <a href="/cuadros/${respuesta.cuadro.idCuadro}/estructura" 
                               class="btn btn-sm btn-primary text-white">
                               <i class="fas fa-table"></i> Estructura
                            </a>
                        </div>
                        `
                            ]).draw(false);
                        }

                        $("#modalAgregarCuadro").modal("hide");
                        $("#formAgregarCuadro")[0].reset();
                    });
                },

                error: function (xhr) {
                    $(".btn-guardar-cuadro")
                        .prop("disabled", false)
                        .html('<i class="fas fa-save"></i> Guardar');

                    let mensaje = 'Error al guardar el cuadro.';
                    if (xhr.responseJSON?.errors) {
                        mensaje = Object.values(xhr.responseJSON.errors).flat().join("\n");
                    } else if (xhr.responseJSON?.message || xhr.responseJSON?.mensaje) {
                        mensaje = xhr.responseJSON.message || xhr.responseJSON.mensaje;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: mensaje,
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }


        function importarEstructura() {
            let formData = new FormData($("#formImportarEstructura")[0]);

            $.ajax({
                url: "{{ route('anexo.procesar') }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },

                beforeSend: function () {
                    $(".btn-columna")
                        .prop("disabled", true)
                        .html('<i class="fas fa-spinner fa-spin"></i> Importando...');
                },

                success: function (respuesta) {
                    $(".btn-columna")
                        .prop("disabled", false)
                        .html('<i class="fas fa-upload"></i> Importar');

                    Swal.fire({
                        icon: 'success',
                        title: 'Importación exitosa',
                        text: respuesta.message || 'La estructura se importó correctamente.',
                        confirmButtonColor: '#4B5563',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $("#modalImportar").modal("hide");
                        $("#formImportarEstructura")[0].reset();
                        location.reload();
                    });
                },

                error: function (xhr) {
                    $(".btn-columna")
                        .prop("disabled", false)
                        .html('<i class="fas fa-upload"></i> Importar');

                    let mensaje = 'Error al importar la estructura.';
                    if (xhr.responseJSON?.message) {
                        mensaje = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: mensaje,
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }


        function abrirModalDatosGenerales(idCuadro) {
            $("#formAgregarCuadro")[0].reset();
            $("#idCuadro").val(''); // limpiar id
            $("#idEjePED, #idTemaPED, select[name='idDependencia'], select[name='idGrupo']").prop('disabled', false);

            const modal = $("#modalAgregarCuadro");
            const titulo = modal.find(".modal-title");
            const botonGuardar = modal.find(".btn-guardar-cuadro");

            modal.modal("show");
            titulo.html('<i class="fas fa-spinner fa-spin"></i> Cargando datos...');
            botonGuardar.prop("disabled", true);

            $.ajax({
                url: `/cuadros/${idCuadro}/datos`,
                type: "GET",
                success: function (data) {
                    // Llenar formulario básico
                    $("#idCuadro").val(data.idCuadro);
                    $("input[name='numero']").val(data.numero);
                    $("input[name='control']").val(data.control);
                    $("input[name='titulo']").val(data.titulo);
                    $("textarea[name='descripcion']").val(data.descripcion);
                    $("select[name='idEjePED']").val(data.idEjePED);
                    $("select[name='idDependencia']").val(data.idDependencia);
                    $("select[name='idGrupo']").val(data.idGrupo);

                    $.ajax({
                        url: '/temas-por-eje/' + data.idEjePED,
                        type: 'GET',
                        success: function (temas) {
                            let options = '<option value="">— Seleccione —</option>';
                            temas.forEach(t => {
                                options += `<option value="${t.idTemaPED}">
                            ${t.temaPEDClave ? t.temaPEDClave + ' - ' : ''}${t.temaPEDDescripcion}
                        </option>`;
                            });
                            $("#idTemaPED").html(options);
                            $("#idTemaPED").val(data.idTemaPED);
                        },
                        error: function () {
                            $("#idTemaPED").html('<option value="">Error al cargar temas</option>');
                        }
                    });

                    // Cambiar título y botón
                    titulo.html(`<i class="fas fa-info-circle"></i> Datos Generales del Cuadro [${data.idCuadro}] - ${data.titulo}`);
                    botonGuardar.html('<i class="fas fa-save"></i> Actualizar');
                    botonGuardar.prop("disabled", false);
                },
                error: function () {
                    titulo.html('<i class="fas fa-exclamation-triangle"></i> Error al cargar los datos');
                    botonGuardar.prop("disabled", true);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudieron cargar los datos del cuadro.",
                        confirmButtonColor: "#d33"
                    });
                }
            });
        }







    </script>

</x-app-layout>