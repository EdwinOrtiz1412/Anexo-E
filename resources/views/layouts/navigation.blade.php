<nav class="navbar navbar-expand-lg navbar-light bg-transparent">
    <div class="container-fluid justify-content-end">
        <!-- Dropdown de usuario -->
        <div class="dropdown">
            <button
                class="btn d-flex align-items-center gap-2 dropdown-toggle shadow-sm"
                id="userDropdown"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                style="
                    background-color: var(--color-header);
                    color: var(--color-text);
                    border: 1px solid var(--color-border);
                    padding: 0.5rem 1rem;
                    border-radius: 8px;
                    font-weight: 500;
                    transition: all 0.2s ease;
                "
                onmouseover="this.style.backgroundColor='var(--color-bg)'"
                onmouseout="this.style.backgroundColor='var(--color-header)'"
            >
                <i class="bi bi-person-circle fs-5 text-secondary"></i>
                <span class="text-uppercase small fw-semibold">
                    {{ Auth::user()->name }}
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2"
                aria-labelledby="userDropdown"
                style="
                    min-width: 240px;
                    border-radius: 0.5rem;
                    background-color: var(--color-surface);
                    color: var(--color-text);
                    border: 1px solid var(--color-border);
                ">
                <!-- Encabezado del menú -->
                <li class="px-3 py-2 border-bottom" style="border-color: var(--color-border);">
                    <div class="fw-semibold">{{ Auth::user()->name }}</div>
                    <div class="small text-muted">{{ Auth::user()->email }}</div>
                </li>

                <!-- Enlaces -->
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2"
                       href="{{ route('profile.edit') }}"
                       style="transition: all 0.2s;">
                        <i class="bi bi-person text-secondary"></i>
                        Perfil
                    </a>
                </li>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="dropdown-item d-flex align-items-center gap-2 text-danger"
                            style="transition: all 0.2s;">
                            <i class="bi bi-box-arrow-right"></i>
                            Cerrar sesión
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
