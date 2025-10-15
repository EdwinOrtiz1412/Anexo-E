@extends('layouts.administrador')

@section('title', 'Administración de grupos y categorías')

@section('styles')
    <style>
        /* --- Encabezado de grupo --- */
        .grupo-header {
            background-color: #4B5563 !important;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .grupo-header:hover {
            background-color: #374151 !important;
        }

        /* --- Icono del encabezado --- */
        /* --- Rotación suave del ícono --- */
        .grupo-header i {
            transition: transform 0.35s ease;
            display: inline-block;
        }

        .grupo-header i.rotated {
            transform: rotate(90deg);
        }


        /* --- Filas de categorías --- */
        .categoria-row {
            background-color: #f9fafb;
        }

        .categoria-row td {
            padding-left: 45px !important;
        }

        /* --- Contenedor con animación --- */
        .categorias-wrapper {
            display: none;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            transition: all 0.4s ease;
        }

        /* --- Ajustes generales --- */
        .btn-accion i {
            font-size: 0.9rem;
        }

        .badge {
            font-size: 0.85rem;
        }

        .transition {
            transition: transform 0.35s ease;
        }

        .grupo-header[aria-expanded="true"] .bi-chevron-right {
            transform: rotate(90deg);
        }
    </style>
@endsection

@section('content')
@section('content')
    <div class="py-4">
        <div class="container-fluid">
            <div class="card shadow-sm">
                <div class="card-body">
                    {{-- BOTONES SUPERIORES --}}
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalNuevoGrupo">
                            <i class="bi bi-layers"></i> Nuevo Grupo
                        </button>

                        <button class="btn btn-secondary" onclick="abrirModalCategoria()">
                            <i class="bi bi-tags"></i> Nueva Categoría
                        </button>
                    </div>

                    {{-- 🔹 CONTENEDOR DEL ACORDEÓN (para controlar la apertura única) --}}
                    <div id="accordionGrupos">
                        {{-- TABLA DE GRUPOS Y CATEGORÍAS --}}
                        <table class="table table-bordered table-striped mb-0 align-middle">
                            <thead class="bg-dark text-white text-center">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Grupo / Categorías</th>
                                    <th>Descripción</th>
                                    <th>Año</th>
                                    <th>Vigente</th>
                                    <th>Solicitado</th>
                                    <th style="width: 140px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($grupos as $grupo)
                                    {{-- 🔹 Fila principal del grupo --}}
                                    <tr class="grupo-header bg-secondary text-white" data-bs-toggle="collapse"
                                        data-bs-target="#grupo{{ $grupo->idGrupo }}" aria-expanded="false"
                                        aria-controls="grupo{{ $grupo->idGrupo }}" style="cursor:pointer;">
                                        <td class="text-center align-middle">
                                            <i class="bi bi-chevron-right transition"></i>
                                        </td>
                                        <td colspan="6" class="fw-semibold">{{ $grupo->nombre }}</td>
                                    </tr>

                                    {{-- 🔹 Fila de categorías colapsable --}}
                                    <tr class="p-0">
                                        <td colspan="7" class="p-0 border-0">
                                            <div id="grupo{{ $grupo->idGrupo }}" class="accordion-collapse collapse"
                                                data-bs-parent="#accordionGrupos">
                                                <div class="p-0 bg-light">
                                                    <table class="table table-sm mb-0">
                                                        <tbody>
                                                            @php
                                                                $cats = $categorias->where('idGrupo', $grupo->idGrupo);
                                                            @endphp
                                                            @forelse ($cats as $cat)
                                                                <tr class="categoria-row">
                                                                    <td class="text-center">{{ $cat->idCategoria }}</td>
                                                                    <td>{{ $cat->nombre }}</td>
                                                                    <td>{{ $cat->descripcion ?? '—' }}</td>
                                                                    <td class="text-center">{{ $cat->anio }}</td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="badge {{ $cat->vigente ? 'bg-success' : 'bg-secondary' }}">
                                                                            {{ $cat->vigente ? 'Sí' : 'No' }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span
                                                                            class="badge {{ $cat->solicitado ? 'bg-info' : 'bg-light text-dark' }}">
                                                                            {{ $cat->solicitado ? 'Sí' : 'No' }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <button class="btn btn-warning btn-sm text-white"
                                                                            onclick="abrirModalCategoria({{ $cat->idCategoria }})">
                                                                            <i class="bi bi-pencil"></i>
                                                                        </button>
                                                                        <button class="btn btn-danger btn-sm"
                                                                            onclick="eliminarCategoria({{ $cat->idCategoria }}, '{{ $cat->nombre }}')">
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="7" class="text-center text-muted">
                                                                        No hay categorías para este grupo.
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No hay grupos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div> {{-- Fin #accordionGrupos --}}
                </div>

            </div>
        </div>
    </div>

    <!-- Modal: Nuevo Grupo -->
    <div class="modal fade" id="modalNuevoGrupo" tabindex="-1" aria-labelledby="modalNuevoGrupoLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-layers"></i> Nuevo Grupo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>
                <form id="formNuevoGrupo">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombreGrupo" class="form-label">Nombre del Grupo</label>
                            <input type="text" name="nombre" id="nombreGrupo" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-dark" onclick="agregarGrupo()">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Nueva Categoría -->
    <div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalNuevaCategoriaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-tag"></i> <span id="tituloModalCategoria">Nueva Categoría</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>

                <form id="formNuevaCategoria">
                    @csrf
                    <input type="hidden" name="idCategoria" id="idCategoria">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombreCategoria" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombreCategoria" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcionCategoria" class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcionCategoria" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="idGrupoSelect" class="form-label">Grupo</label>
                            <select name="idGrupo" id="idGrupoSelect" class="form-select" required>
                                <option value="">Seleccione...</option>
                                @foreach($grupos as $g)
                                    <option value="{{ $g->idGrupo }}">{{ $g->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row align-items-end">
                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-secondary"><i class="bi bi-calendar"></i> Año</label>
                                <input type="number" name="anio" id="anioCategoria" class="form-control" min="2000"
                                    max="2100" value="{{ date('Y') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-secondary"><i class="bi bi-check-circle"></i> Vigente</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="vigenteSwitch" name="vigente"
                                        value="1" checked>
                                    <label class="form-check-label text-muted" for="vigenteSwitch">Activado</label>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="fw-bold text-secondary"><i class="bi bi-hand-index-thumb"></i>
                                    Solicitado</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="solicitadoSwitch" name="solicitado"
                                        value="1">
                                    <label class="form-check-label text-muted" for="solicitadoSwitch">Activado</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="agregarCategoria()">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
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
        $(document).ready(function () {
            $('.collapse').on('show.bs.collapse', function () {
                const icon = $(this).closest('tr').prev('tr').find('i');
                icon.addClass('rotate');
            });

            $('.collapse').on('hide.bs.collapse', function () {
                const icon = $(this).closest('tr').prev('tr').find('i');
                icon.removeClass('rotate');
            });
        });

        function agregarGrupo() {
            $.ajax({
                url: "{{ route('grupos.guardar') }}",
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    nombre: $('#formNuevoGrupo [name="nombre"]').val().trim()
                },
                success: function (response) {
                    if (response.success) {
                        // Cierra modal y limpia formulario
                        $('#modalNuevoGrupo').modal('hide');
                        $('#formNuevoGrupo')[0].reset();

                        // Muestra mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Grupo agregado correctamente',
                            text: response.message,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4B5563'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo guardar el grupo.',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let msg = '';
                        for (const key in errors) {
                            msg += errors[key][0] + '\n';
                        }
                        Swal.fire('Error de validación', msg, 'error');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo guardar el grupo.',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        }
        function abrirModalCategoria(id = null) {
            const modal = $('#modalNuevaCategoria');
            const form = modal.find('form');
            const titulo = $('#tituloModalCategoria');
            const btnGuardar = modal.find('.btn-secondary, .btn-warning');

            if (!id) {
                // 🔹 MODO NUEVO
                titulo.text('Nueva Categoría');
                form[0].reset();
                form.find('[name="idCategoria"]').val('');
                form.find('[name="anio"]').val(new Date().getFullYear());

                btnGuardar.removeClass('btn-warning').addClass('btn-secondary')
                    .html('<i class="fas fa-save"></i> Guardar');

                modal.modal('show');
                return;
            }

            // 🔹 MODO EDICIÓN - obtener datos desde el servidor
            $.ajax({
                url: `/categorias/${id}`,
                type: 'GET',
                success: function (categoria) {
                    titulo.text(`Editar Categoría [${categoria.idCategoria}] - ${categoria.nombre}`);
                    form.find('[name="idCategoria"]').val(categoria.idCategoria);
                    form.find('[name="nombre"]').val(categoria.nombre);
                    form.find('[name="descripcion"]').val(categoria.descripcion);
                    form.find('[name="idGrupo"]').val(categoria.idGrupo);
                    form.find('[name="anio"]').val(categoria.anio);
                    form.find('[name="vigente"]').prop('checked', categoria.vigente == 1);
                    form.find('[name="solicitado"]').prop('checked', categoria.solicitado == 1);

                    btnGuardar.removeClass('btn-secondary').addClass('btn-warning')
                        .html('<i class="fas fa-save"></i> Actualizar');

                    modal.modal('show');
                },
                error: function () {
                    Swal.fire('Error', 'No se pudo obtener la categoría', 'error');
                }
            });
        }


        function agregarCategoria() {
            const form = $('#formNuevaCategoria');
            const data = {
                _token: "{{ csrf_token() }}",
                idCategoria: form.find('[name="idCategoria"]').val(),
                nombre: form.find('[name="nombre"]').val().trim(),
                descripcion: form.find('[name="descripcion"]').val().trim(),
                idGrupo: form.find('[name="idGrupo"]').val(),
                anio: form.find('[name="anio"]').val(),
                vigente: form.find('[name="vigente"]').is(':checked') ? 1 : 0,
                solicitado: form.find('[name="solicitado"]').is(':checked') ? 1 : 0
            };
            $.ajax({
                url: "{{ route('categorias.guardar') }}",
                type: 'POST',
                data: data,
                success: function (response) {
                    if (response.success) {
                        $('#modalNuevaCategoria').modal('hide');
                        form[0].reset();

                        Swal.fire({
                            icon: 'success',
                            title: response.message,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#4B5563'
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let msg = '';
                        for (const key in errors) {
                            msg += errors[key][0] + '\n';
                        }
                        Swal.fire('Error de validacion', msg, 'error');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo guardar la categoria',
                            confirmButtonText: 'OK',
                        });
                    }
                }
            });


        }
        function eliminarCategoria(id, nombre) {
    Swal.fire({
        title: '¿Eliminar Categoría?',
        text: `Se eliminará la categoría "${nombre}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/categorias/${id}`,
                type: 'DELETE',
                data: { _token: "{{ csrf_token() }}" },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: response.message,
                            confirmButtonColor: '#4B5563'
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo eliminar',
                            text: response.message
                        });
                    }
                },
                error: function (xhr) {
                    let msg = 'Ocurrió un error al eliminar la categoría.';
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


    </script>
@endsection