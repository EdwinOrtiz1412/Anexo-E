<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">


    <title>@yield('title', 'Panel Administrativo')</title>

    <!-- Fuentes -->
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!--  SweetAlert2 (alertas globales) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Estilos globales -->
    {{--
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"> --}}

    @yield('styles')

    <style>
        :root {
            --color-bg: #f9fafb;
            --color-surface: #ffffff;
            --color-border: #e5e7eb;
            --color-text: #1f2937;
            --color-header: #f3f4f6;
            --color-sidebar: #374151;
            --color-sidebar-hover: #4b5563;
        }

        body {
            font-family: 'Figtree', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            overflow-x: hidden;
        }

        /* --- SIDEBAR --- */
        aside {
            background-color: var(--color-sidebar);
            color: var(--color-surface);
            width: 250px;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease-in-out;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.08);
            z-index: 1040;
        }

        aside header {
            padding: 1rem;
            font-weight: 600;
            font-size: 1.05rem;
            text-align: center;
            background-color: var(--color-sidebar-hover);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        aside nav {
            flex: 1;
            overflow-x: hidden;
            position: relative;
            z-index: 1;
        }

        aside nav .collapse {
            overflow: visible !important;
        }

        aside nav h6 {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            padding: 0.75rem 1rem 0.25rem;
            margin: 0;
            letter-spacing: 0.5px;
        }

        aside nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.7rem 1rem;
            color: var(--color-surface);
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.94rem;
        }

        aside nav a:hover,
        aside nav a.active {
            background-color: var(--color-sidebar-hover);
            color: #ffffff;
        }

        aside nav a i {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* --- HEADER --- */
        header.app-header {
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            height: 60px;
            background-color: var(--color-sidebar-hover);
            color: var(--color-surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            z-index: 1030;
            transition: left 0.3s ease;
        }

        header.app-header h1 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
        }

        /* --- MAIN --- */
        main {
            margin-top: 60px;
            margin-left: 250px;
            padding: 2rem;
            min-height: calc(100vh - 60px);
            background-color: var(--color-bg);
            transition: margin-left 0.3s ease;
        }

        /* --- FOOTER --- */
        footer {
            background-color: var(--color-header);
            color: var(--color-text);
            text-align: center;
            padding: 0.9rem;
            margin-left: 250px;
            font-size: 0.9rem;
            border-top: 1px solid var(--color-border);
            transition: margin-left 0.3s ease;
        }

        /* --- TOGGLE --- */
        .toggle-btn {
            background: none;
            border: none;
            color: var(--color-surface);
            font-size: 1.6rem;
            cursor: pointer;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 992px) {
            aside {
                transform: translateX(-100%);
            }

            aside.open {
                transform: translateX(0);
            }

            header.app-header {
                left: 0;
            }

            main,
            footer {
                margin-left: 0;
            }

            .close-sidebar-btn {
                display: block !important;
            }
        }

        /* --- SCROLL --- */
        aside nav::-webkit-scrollbar {
            width: 8px;
        }

        aside nav::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }

        aside nav::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* --- Chevron --- */
        .chevron-rotate {
            transition: transform 0.3s ease;
        }

        .nav-link[aria-expanded="true"] .chevron-rotate {
            transform: rotate(180deg);
        }

        /* --- Close sidebar btn --- */
        .close-sidebar-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            width: 100%;
            text-align: center;
            padding: 1rem 0;
            font-size: 1.4rem;
            cursor: pointer;
            transition: color 0.3s ease;
            display: none;
        }

        .close-sidebar-btn:hover {
            color: #fff;
        }

        .collapse {
            position: relative;
            z-index: 2;
        }
    </style>
</head>

<body>
    <aside id="sidebar">
        <header>Panel Administrativo</header>
        <nav id="sidebarMenu">
            <ul class="list-unstyled mb-0">

                <!-- Inicio -->
                <li>
                    <a href="{{ route('dashboard') }}"
                        class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none text-white">
                        <i class="bi bi-house-door"></i> <span>Inicio</span>
                    </a>
                </li>

                <hr class="my-3 border-light opacity-25">

                {{-- Sección: Administrador --}}
                @if (auth()->user()->hasRole('administrador'))
                    <h6 class="text-uppercase small px-3 mb-2" style="color: rgba(255,255,255,0.6); letter-spacing: 0.5px;">
                        Administración - Cuadros
                    </h6>

                    <li class="nav-item">
                        <a class="nav-link collapsed d-flex align-items-center justify-content-between px-3 py-2 text-white text-decoration-none"
                            data-bs-toggle="collapse" href="#collapseAdminMenu" role="button" aria-expanded="false"
                            aria-controls="collapseAdminMenu">
                            <span> Anexo Estadístico</span>
                            <i class="bi bi-chevron-down small opacity-75 chevron-rotate"></i>
                        </a>

                        <div class="collapse" id="collapseAdminMenu" data-bs-parent="#sidebarMenu">
                            <div class="bg-white rounded mt-1 mx-3 py-2 shadow-sm">
                                <h6 class="px-3 mb-2 text-muted text-uppercase"
                                    style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                    Opciones:
                                </h6>

                                <a href="{{ route('admin.categorias') }}"
                                    class="d-block px-3 py-1 text-decoration-none text-dark" style="font-size: 0.9rem;">
                                    Categorías
                                </a>

                                <a href="{{ route('anexo.cuadros.listar') }}"
                                    class="d-block px-3 py-1 text-decoration-none text-dark" style="font-size: 0.9rem;">
                                    Administrar Cuadros
                                </a>
                            </div>
                        </div>
                    </li>

                    <hr class="my-3 border-light opacity-25">
                @endif

                {{-- Módulo Informes --}}
                @if (session('mod') == 'info')
                    <h6 class="text-uppercase small px-3 mb-2" style="color: rgba(255,255,255,0.6);">Informes</h6>

                    <li>
                        <a href="#" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none text-white">
                            <i class="bi bi-file-earmark-text"></i> <span>Sección Informes Prueba</span>
                        </a>
                    </li>

                    @if (auth()->user()->ipes && session('mod') == 'info')
                    <h6 class="text-uppercase small px-3 mb-2" style="color: rgba(255,255,255,0.6); letter-spacing: 0.5px;">
                        Anexo Estadístico
                    </h6>
                        <li class="nav-item">
                            <a class="nav-link collapsed d-flex align-items-center justify-content-between px-3 py-2 text-white text-decoration-none"
                                data-bs-toggle="collapse" href="#collapseIpesMenu" role="button" aria-expanded="false"
                                aria-controls="collapseIpesMenu">
                                <span>Anexo Estadístico</span>
                                <i class="bi bi-chevron-down small opacity-75 chevron-rotate"></i>
                            </a>

                            <div class="collapse" id="collapseIpesMenu" data-bs-parent="#sidebarMenu">
                                <div class="bg-white rounded mt-1 mx-3 py-2 shadow-sm">
                                    <h6 class="px-3 mb-2 text-muted text-uppercase"
                                        style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                        Opciones:
                                    </h6>

                                    <a href="{{ route('tablero.cuadros') }}"
                                        class="d-block px-3 py-1 text-decoration-none text-dark" style="font-size: 0.9rem;">
                                        Tablero de Cuadros Estadísticos
                                    </a>

                                    {{-- <a href=""
                                        class="d-block px-3 py-1 text-decoration-none text-dark" style="font-size: 0.9rem;">
                                        Estadísticas generales
                                    </a> --}}
                                </div>
                            </div>
                        </li>
                    @endif

                    <hr class="my-3 border-light opacity-25">
                @endif


                {{-- Módulo Indicadores --}}
                @if (session('mod') == 'segui')
                    <h6 class="text-uppercase small px-3 mb-2" style="color: rgba(255,255,255,0.6);">Indicadores</h6>
                    <li>
                        <a href="#" class="d-flex align-items-center gap-2 px-3 py-2 text-decoration-none text-white">
                            <i class="bi bi-graph-up"></i> <span>Sección Indicadores</span>
                        </a>
                    </li>
                    <hr class="my-3 border-light opacity-25">
                @endif
            </ul>
        </nav>

        <button class="close-sidebar-btn" id="closeSidebar">
            <i class="bi bi-chevron-left"></i>
        </button>
    </aside>

    <div class="main-content">
        <header class="app-header">
            <div class="d-flex align-items-center gap-3">
                <button class="toggle-btn d-lg-none" id="toggleSidebar"><i class="bi bi-list"></i></button>
                <h1>@yield('title', 'Panel Administrativo')</h1>
            </div>
            <div class="d-flex align-items-center gap-3">
                @include('layouts.navigation')
            </div>
        </header>

        <main>@yield('content')</main>

        <footer>&copy; {{ date('Y') }} — Anexo Estadístico · Sistema Administrativo</footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    {{--
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> --}}

    <script>
        $(function () {
            $('#toggleSidebar').on('click', function () {
                $('#sidebar').toggleClass('open');
            });
            $('#closeSidebar').on('click', function () {
                $('#sidebar').removeClass('open');
            });
            $(document).on('click', function (e) {
                if ($(window).width() <= 992 && !$(e.target).closest('#sidebar, #toggleSidebar').length) {
                    $('#sidebar').removeClass('open');
                }
            });
        });


    </script>
    <!-- DataTables (Bootstrap 5) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


    @yield('scripts')
</body>

</html>