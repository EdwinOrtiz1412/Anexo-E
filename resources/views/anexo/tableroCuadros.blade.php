@extends('layouts.administrador')

@section('title', 'Cuadros de Anexo Estadístico / Listado')

@section('styles')
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
            color: #fff !important;
        }

        .btn-columna,
        .btn-guardar-cuadro {
            background-color: #4B5563 !important;
            color: #fff !important;
            border: none;
        }

        .btn-columna:hover,
        .btn-guardar-cuadro:hover {
            background-color: #374151 !important;
        }

        .btn-volver {
            background-color: #9CA3AF !important;
            color: #fff !important;
            border: none;
        }

        .btn-volver:hover {
            background-color: #6B7280 !important;
        }
    </style>
@endsection


@section('content')
    <div class="py-4">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-body">

                    {{-- <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-columna" onclick="abrirModalNuevoCuadro()">
                            <i class="bi bi-plus-lg"></i> Agregar Cuadro
                        </button>

                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                            <i class="bi bi-file-earmark-arrow-up"></i> Importar Estructura
                        </button>
                    </div> --}}

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="tablaCuadros">
                            <thead class="text-center">
                                <tr>
                                    <th>ID</th>
                                    <th>Número</th>
                                    <th>Título</th>
                                    <th>Eje PED</th>
                                    <th>Tema PED</th>
                                    <th class="text-center">Dependencia</th>
                                    <th>Grupo</th>
                                    <th>Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cuadros as $c)
                                    <tr>
                                        <td class="text-center align-middle">{{ $c->idCuadro }}</td>
                                        <td class="text-center align-middle">{{ $c->numero }}</td>
                                        <td class="align-middle">{{ $c->titulo }}</td>
                                        <td class="align-middle">{{ $c->ejePEDDescripcion ?? '—' }}</td>
                                        <td class="align-middle">{{ $c->temaPEDDescripcion ?? '—' }}</td>
                                        <td class="text-center align-middle">{{ $c->dependenciaSiglas ?? '—' }}</td>
                                        <td class="align-middle">{{ $c->grupoNombre ?? '—' }}</td>
                                        <td class="text-center align-middle">
                                            <div
                                                class="d-flex flex-column flex-sm-row flex-md-column align-items-stretch justify-content-center gap-2">
                                                <button class="btn btn-sm btn-success text-white w-100"
                                                    onclick="abrirModalDatosGenerales({{ $c->idCuadro }})">
                                                    Datos Generales
                                                </button>

                                                {{-- <a href="{{ route('cuadros.estructura', $c->idCuadro) }}"
                                                    class="btn btn-sm btn-primary text-white w-100">
                                                    Estructura
                                                </a> --}}

                                                <a href="{{ route('cuadros.seguimiento', $c->idCuadro) }}"
                                                    class="btn btn-sm btn-warning text-dark w-100">
                                                    Seguimiento
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

    <!-- Modal Importar -->
    <div class="modal fade" id="modalImportar" tabindex="-1" aria-labelledby="modalImportarLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header bg-columna text-white">
                    <h5 class="modal-title" id="modalImportarLabel">
                        <i class="bi bi-file-earmark-arrow-up"></i> Importar Estructura desde Excel
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>

                <form id="formImportarEstructura" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="archivo" class="form-label">Archivo Excel</label>
                            <input type="file" name="archivo" id="archivo" class="form-control" accept=".xls,.xlsx"
                                required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-columna" onclick="importarEstructura()">
                            <i class="bi bi-upload"></i> Importar
                        </button>
                        <button type="button" class="btn btn-volver" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Agregar / Editar Cuadro -->
    <div class="modal fade" id="modalAgregarCuadro" tabindex="-1" aria-labelledby="modalAgregarCuadroLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                {{-- Encabezado --}}
                <div class="modal-header bg-columna text-white">
                    <h5 class="modal-title" id="modalAgregarCuadroLabel">
                        <i class="bi bi-eye"></i> Datos Generales del Cuadro (Solo lectura)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>

                {{-- Formulario --}}
                <form id="formAgregarCuadro">
                    @csrf
                    <input type="hidden" name="idCuadro" id="idCuadro">

                    <div class="modal-body">

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Número de Cuadro</label>
                                <input type="text" name="numero" class="form-control bg-light text-muted border-secondary"
                                    readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Control</label>
                                <input type="text" name="control" class="form-control bg-light text-muted border-secondary"
                                    readonly>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Título del Cuadro</label>
                                <input type="text" name="titulo" class="form-control bg-light text-muted border-secondary"
                                    readonly>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control bg-light text-muted border-secondary" rows="2"
                                readonly></textarea>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Eje PED</label>
                                <select name="idEjePED" id="idEjePED"
                                    class="form-select bg-light text-muted border-secondary" disabled>
                                    <option value="">Seleccione el eje...</option>
                                    @foreach ($ejes as $eje)
                                        <option value="{{ $eje->idEjePED }}">
                                            {{ $eje->ejePEDClave ? $eje->ejePEDClave . ' - ' : '' }}{{ $eje->ejePEDDescripcion }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tema PED</label>
                                <select name="idTemaPED" id="idTemaPED"
                                    class="form-select bg-light text-muted border-secondary" disabled>
                                    <option value="">Seleccione el tema...</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Dependencia</label>
                                <select name="idDependencia" class="form-select bg-light text-muted border-secondary"
                                    disabled>
                                    <option value="">Seleccione...</option>
                                    @foreach ($dependencias as $dep)
                                        <option value="{{ $dep->idDependencia }}">{{ $dep->dependenciaSiglas }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label">Grupo</label>
                            <select name="idGrupo" class="form-select bg-light text-muted border-secondary" disabled>
                                <option value="">Seleccione...</option>
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->idGrupo }}">{{ $grupo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Footer del modal --}}
                    <div class="modal-footer">
                        {{-- Botón de guardar eliminado --}}
                        <button type="button" class="btn btn-volver" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cerrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection


@section('scripts')
    <script>
        $(document).ready(function () {

            if ($('#tablaCuadros tbody tr').length > 0 &&
                !$('#tablaCuadros tbody td').first().attr('colspan')) {
                $('#tablaCuadros').DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50],
                    order: [
                        [0, 'asc']
                    ],
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }

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
                                $temaSelect.html(
                                    '<option value="">— No hay temas para este eje —</option>');
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
            $(document).on('input', 'input[name="numero"]', function () {
                generarControlDesdeNumero($(this).val());
            });


        });

        // function generarControlDesdeNumero(numero) {
        //     if (!numero) {
        //         $('input[name="control"]').val('');
        //         return;
        //     }

        //     // Dividir por puntos
        //     let partes = numero.split('.').map(p => p.trim()).filter(p => p !== '');

        //     // Si solo hay un número (ej. "5"), control = número + "000"
        //     if (partes.length === 1) {
        //         $('input[name="control"]').val(partes[0] + '000');
        //         return;
        //     }

        //     // Si hay dos partes: 6.1 → 6100
        //     if (partes.length === 2) {
        //         let control = partes[0] + partes[1].padStart(2, '0') + '0';
        //         $('input[name="control"]').val(control);
        //         return;
        //     }

        //     // Si hay tres partes: 5.4.3 → 5403 / 1.9.12 → 1912
        //     if (partes.length === 3) {
        //         // Si la segunda parte tiene un dígito, se usa tal cual (5.4.3 → 5403)
        //         // Si tiene dos dígitos (1.10.3 → 1103)
        //         let control = partes[0] + partes[1].padStart(1, '0') + partes[2].padStart(2, '0');
        //         $('input[name="control"]').val(control);
        //         return;
        //     }

        //     // En caso de más de tres partes, unir todas sin puntos
        //     let control = partes.join('');
        //     $('input[name="control"]').val(control);
        // }



        // function abrirModalNuevoCuadro() {
        //     const modal = $("#modalAgregarCuadro");
        //     const titulo = modal.find(".modal-title");
        //     const botonGuardar = modal.find(".btn-guardar-cuadro");

        //     $("#formAgregarCuadro")[0].reset();
        //     $("#idCuadro").val('');

        //     $("#idTemaPED").html('<option value="">Seleccione el tema...</option>');
        //     $("#idEjePED, select[name='idDependencia'], select[name='idGrupo']").prop('disabled', false);

        //     titulo.html('<i class="fas fa-plus"></i> Agregar Nuevo Cuadro');
        //     botonGuardar.html('<i class="fas fa-save"></i> Guardar');
        //     botonGuardar.prop("disabled", false);

        //     modal.modal("show");
        // }


        // function guardarCuadro() {
        //     let datosFormulario = $("#formAgregarCuadro").serialize();

        //     $.ajax({
        //         url: "/cuadros",
        //         type: "POST",
        //         data: datosFormulario,
        //         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },

        //         beforeSend: function () {
        //             $(".btn-guardar-cuadro")
        //                 .prop("disabled", true)
        //                 .html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        //         },

        //         success: function (respuesta) {
        //             $(".btn-guardar-cuadro")
        //                 .prop("disabled", false)
        //                 .html('<i class="fas fa-save"></i> Guardar');

        //             Swal.fire({
        //                 icon: 'success',
        //                 title: 'Cuadro guardado',
        //                 text: respuesta.message || 'El cuadro se guardó correctamente.',
        //                 confirmButtonText: 'OK',
        //                 confirmButtonColor: '#4B5563'
        //             }).then(() => {
        //                 const tabla = $('#tablaCuadros').DataTable();
        //                 const idCuadro = respuesta.cuadro.idCuadro;

        //                 let filaExistente = tabla
        //                     .rows()
        //                     .nodes()
        //                     .to$()
        //                     .filter((_, tr) => $(tr).find('td:first').text().trim() == idCuadro);

        //                 if (filaExistente.length > 0) {
        //                     // Actualizar fila existente
        //                     tabla.row(filaExistente).data([
        //                         respuesta.cuadro.idCuadro,
        //                         respuesta.cuadro.numero,
        //                         respuesta.cuadro.titulo,
        //                         respuesta.cuadro.ejePEDDescripcion ?? '—',
        //                         respuesta.cuadro.temaPEDDescripcion ?? '—',
        //                         respuesta.cuadro.dependenciaSiglas ?? '—',
        //                         respuesta.cuadro.grupoNombre ?? '—',
        //                         `
        //                                 <div class="botones-opciones">
        //                                     <button class="btn btn-sm btn-success text-white"
        //                                         onclick="abrirModalDatosGenerales(${respuesta.cuadro.idCuadro})">
        //                                         <i class="fas fa-info-circle"></i> Datos Generales
        //                                     </button>
        //                                     <a href="/cuadros/${respuesta.cuadro.idCuadro}/estructura" 
        //                                        class="btn btn-sm btn-primary text-white">
        //                                        <i class="fas fa-table"></i> Estructura
        //                                     </a>
        //                                 </div>
        //                                 `
        //                     ]).draw(false);
        //                 } else {
        //                     // Si no existe, agregar nueva
        //                     tabla.row.add([
        //                         respuesta.cuadro.idCuadro,
        //                         respuesta.cuadro.numero,
        //                         respuesta.cuadro.titulo,
        //                         respuesta.cuadro.ejePEDDescripcion ?? '—',
        //                         respuesta.cuadro.temaPEDDescripcion ?? '—',
        //                         respuesta.cuadro.dependenciaSiglas ?? '—',
        //                         respuesta.cuadro.grupoNombre ?? '—',
        //                         `
        //                                 <div class="botones-opciones">
        //                                     <button class="btn btn-sm btn-success text-white"
        //                                         onclick="abrirModalDatosGenerales(${respuesta.cuadro.idCuadro})">
        //                                         <i class="fas fa-info-circle"></i> Datos Generales
        //                                     </button>
        //                                     <a href="/cuadros/${respuesta.cuadro.idCuadro}/estructura" 
        //                                        class="btn btn-sm btn-primary text-white">
        //                                        <i class="fas fa-table"></i> Estructura
        //                                     </a>
        //                                 </div>
        //                                 `
        //                     ]).draw(false);
        //                 }

        //                 $("#modalAgregarCuadro").modal("hide");
        //                 $("#formAgregarCuadro")[0].reset();
        //             });
        //         },

        //         error: function (xhr) {
        //             $(".btn-guardar-cuadro")
        //                 .prop("disabled", false)
        //                 .html('<i class="fas fa-save"></i> Guardar');

        //             let mensaje = 'Error al guardar el cuadro.';
        //             if (xhr.responseJSON?.errors) {
        //                 mensaje = Object.values(xhr.responseJSON.errors).flat().join("\n");
        //             } else if (xhr.responseJSON?.message || xhr.responseJSON?.mensaje) {
        //                 mensaje = xhr.responseJSON.message || xhr.responseJSON.mensaje;
        //             }

        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'Error',
        //                 text: mensaje,
        //                 confirmButtonColor: '#d33'
        //             });
        //         }
        //     });
        // }


        // function importarEstructura() {
        //     let formData = new FormData($("#formImportarEstructura")[0]);

        //     $.ajax({
        //         url: "{{ route('anexo.procesar') }}",
        //         type: "POST",
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },

        //         beforeSend: function () {
        //             $(".btn-columna")
        //                 .prop("disabled", true)
        //                 .html('<i class="fas fa-spinner fa-spin"></i> Importando...');
        //         },

        //         success: function (respuesta) {
        //             $(".btn-columna")
        //                 .prop("disabled", false)
        //                 .html('<i class="fas fa-upload"></i> Importar');

        //             Swal.fire({
        //                 icon: 'success',
        //                 title: 'Importación exitosa',
        //                 text: respuesta.message || 'La estructura se importó correctamente.',
        //                 confirmButtonColor: '#4B5563',
        //                 confirmButtonText: 'OK'
        //             }).then(() => {
        //                 $("#modalImportar").modal("hide");
        //                 $("#formImportarEstructura")[0].reset();
        //                 location.reload();
        //             });
        //         },

        //         error: function (xhr) {
        //             $(".btn-columna")
        //                 .prop("disabled", false)
        //                 .html('<i class="fas fa-upload"></i> Importar');

        //             let mensaje = 'Error al importar la estructura.';
        //             if (xhr.responseJSON?.message) {
        //                 mensaje = xhr.responseJSON.message;
        //             }

        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'Error',
        //                 text: mensaje,
        //                 confirmButtonColor: '#d33'
        //             });
        //         }
        //     });
        // }


        function abrirModalDatosGenerales(idCuadro) {
            // Limpiar y preparar el formulario
            $("#formAgregarCuadro")[0].reset();
            $("#idCuadro").val('');

            const modal = $("#modalAgregarCuadro");
            const titulo = modal.find(".modal-title");
            const botonGuardar = modal.find(".btn-guardar-cuadro");

            // Mostrar el modal y configurar estado inicial
            modal.modal("show");
            titulo.html('<i class="fas fa-spinner fa-spin"></i> Cargando datos...');
            botonGuardar.hide(); // Ocultamos el botón de guardar, solo lectura

            // Petición AJAX para obtener los datos del cuadro
            $.ajax({
                url: `/cuadros/${idCuadro}/datos`,
                type: "GET",
                success: function (data) {

                    // Llenar los campos con la información del cuadro
                    $("#idCuadro").val(data.idCuadro);
                    $("input[name='numero']").val(data.numero);
                    $("input[name='control']").val(data.control);
                    $("input[name='titulo']").val(data.titulo);
                    $("textarea[name='descripcion']").val(data.descripcion);
                    $("select[name='idEjePED']").val(data.idEjePED);
                    $("select[name='idDependencia']").val(data.idDependencia);
                    $("select[name='idGrupo']").val(data.idGrupo);

                    generarControlDesdeNumero(data.numero);

                    // Cargar los temas del eje correspondiente
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

                    $("#formAgregarCuadro input, #formAgregarCuadro textarea").prop("readonly", true);
                    $("#formAgregarCuadro select").prop("disabled", true);

                    // Actualizar título del modal
                    titulo.html(
                        `<i class="fas fa-eye"></i> Datos Generales del Cuadro [${data.idCuadro}] - ${data.titulo}`
                    );

                },
                error: function () {
                    titulo.html('<i class="fas fa-exclamation-triangle"></i> Error al cargar los datos');
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

@endsection