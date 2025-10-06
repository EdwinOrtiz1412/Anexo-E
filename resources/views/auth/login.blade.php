<x-guest-layout>
    <div class="flex justify-end w-full">
        <img src="{{ asset('images/col_gabinete.svg') }}" alt="" width="200">
    </div>

    <!-- Mensajes de sesión -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="w-full bg-white border border-gray-200 p-6 rounded-md shadow">
        <form method="POST" action="{{ route('login') }}" autocomplete="off" novalidate>
            @csrf

            <!-- Logo -->
            <div class="text-center mb-6">
                <img src="{{ asset('images/siibien_colores.png') }}" alt="" width="250" class="mx-auto">
            </div>

            <!-- Cuenta -->
            <div>
                <x-input-label for="cuenta" :value="__('Cuenta')" />
                <x-text-input id="cuenta" class="block mt-1 w-full" type="text" name="cuenta" :value="old('cuenta')" required autofocus />
                <x-input-error :messages="$errors->get('cuenta')" class="mt-2" />
            </div>

            <!-- Contraseña -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Contraseña')" />
                <x-text-input id="password" class="block mt-1 w-full"
                              type="password"
                              name="password"
                              required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Módulo -->
            <div class="mt-4">
                <x-input-label for="mod" :value="__('Módulo')" />
                <select name="mod" id="mod" class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200">
                    <option value="info">Informes</option>
                    <option value="segui">Indicadores</option>
                </select>
            </div>

            <!-- Mostrar contraseña -->
            <div class="mt-3 flex items-center">
                <input type="checkbox" id="showpass" onclick="showPass()" class="mr-2">
                <label for="showpass">Mostrar Contraseña</label>
            </div>

            <!-- Botón -->
            <div class="mt-6">
                <button class="w-full px-4 py-2 bg-[rgb(104,27,46)] text-white font-bold rounded-md hover:bg-red-900 transition" type="submit">
                    Ingresar
                </button>
            </div>

            <!-- Error de sesión -->
            @if (session('error'))
                <div class="mt-4 p-2 bg-red-100 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif
        </form>
    </div>

    <script>
        function showPass() {
            let pass = document.getElementById("password");
            pass.type = pass.type === "password" ? "text" : "password";
        }
    </script>
</x-guest-layout>
