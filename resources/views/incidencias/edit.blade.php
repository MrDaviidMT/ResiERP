@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('incidencias.show', $incidencia)" volver-texto="Volver a la incidencia" estrecho>
    <h1 class="titulo-pagina mb-6">Editar Incidencia</h1>


    <div class="tarjeta p-6">
        <form action="{{ route('incidencias.update', $incidencia) }}" method="POST">
            @csrf @method('PUT')

            <div class="mb-4">
                <label class="etiqueta-campo block">Categoría <span class="text-peligro">*</span></label>
                <select name="categoria" required class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm font-semibold text-tinta">
                    <option value="sanitaria" {{ $incidencia->categoria === 'sanitaria' ? 'selected' : '' }}>Sanitaria</option>
                    <option value="infraestructura" {{ $incidencia->categoria === 'infraestructura' ? 'selected' : '' }}>Infraestructura</option>
                    <option value="limpieza" {{ $incidencia->categoria === 'limpieza' ? 'selected' : '' }}>Limpieza</option>
                    <option value="personal" {{ $incidencia->categoria === 'personal' ? 'selected' : '' }}>Personal</option>
                    <option value="otros" {{ $incidencia->categoria === 'otros' ? 'selected' : '' }}>Otros</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="etiqueta-campo block">Ubicación / Habitación</label>
                <select name="habitacion_id" class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm text-tinta">
                    <option value="">-- No aplica / Zona Común --</option>
                    @foreach($habitaciones as $hab)
                        <option value="{{ $hab->id }}" {{ $incidencia->habitacion_id == $hab->id ? 'selected' : '' }}>Habitación {{ $hab->numero }} ({{ ucfirst($hab->tipo) }})</option>
                    @endforeach
                </select>
            </div>

            @can(P::ResidentesVer)
            <div class="mb-4">
                <label class="etiqueta-campo block">Residente Afectado</label>
                <select name="residente_id" class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm text-tinta">
                    <option value="">-- Ninguno --</option>
                    @foreach($residentes as $res)
                        <option value="{{ $res->id }}" {{ $incidencia->residente_id == $res->id ? 'selected' : '' }}>{{ $res->apellidos }}, {{ $res->nombre }}{{ $res->habitacion ? ' (Hab. '.$res->habitacion->numero.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            @endcan

            <div class="mb-4">
                <label class="etiqueta-campo block">Título <span class="text-peligro">*</span></label>
                <input type="text" name="titulo" required value="{{ old('titulo', $incidencia->titulo) }}"
                       class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm">
            </div>

            <div class="mb-4">
                <label class="etiqueta-campo block">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm">{{ old('descripcion', $incidencia->descripcion) }}</textarea>
            </div>

            <div class="mb-6">
                <label class="etiqueta-campo block">Prioridad</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-1">
                    <label class="border border-linea rounded p-2 text-center text-xs font-bold cursor-pointer hover:bg-fondo block">
                        <input type="radio" name="prioridad" value="leve" {{ $incidencia->prioridad === 'leve' ? 'checked' : '' }} class="mr-1"> Leve
                    </label>
                    <label class="border border-linea rounded p-2 text-center text-xs font-bold text-aviso cursor-pointer hover:bg-fondo block">
                        <input type="radio" name="prioridad" value="grave" {{ $incidencia->prioridad === 'grave' ? 'checked' : '' }} class="mr-1"> Grave
                    </label>
                    <label class="border border-linea rounded p-2 text-center text-xs font-bold text-peligro cursor-pointer hover:bg-fondo block">
                        <input type="radio" name="prioridad" value="urgente" {{ $incidencia->prioridad === 'urgente' ? 'checked' : '' }} class="mr-1"> Urgente
                    </label>
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('incidencias.show', $incidencia) }}" title="Cancelar" class="boton tono-neutro">
                    <x-lucide-x />Cancelar
                </a>
                <button type="submit" title="Guardar cambios" class="boton boton-primario">
                    <x-lucide-save />Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
