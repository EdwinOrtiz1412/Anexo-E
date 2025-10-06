<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Exportar Anexo Estadístico') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                {{-- Errores --}}
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Éxito --}}
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Formulario de exportación --}}
                <form action="{{ route('anexo.exportar') }}" method="GET" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Selecciona un cuadro</label>
                        <select name="idCuadro"
                            class="mt-1 block w-full text-sm text-gray-700 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                            <option value="">-- Selecciona --</option>
                            @foreach($cuadros as $cuadro)
                                <option value="{{ $cuadro->idCuadro }}">
                                    {{ $cuadro->numero }} - {{ $cuadro->titulo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow hover:bg-indigo-700 focus:outline-none">
                            Descargar Excel
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>