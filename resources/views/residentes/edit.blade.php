@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.show', $residente)" volver-texto="Volver al perfil" estrecho>
    <h1 class="titulo-pagina mb-6">Editar Residente: {{ $residente->nombre }}</h1>


    <div class="tarjeta p-6">
        <form action="{{ route('residentes.update', $residente->id) }}" method="POST" id="form-editar-residente" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="nombre" class="etiqueta-campo block">Nombre <span class="text-peligro">*</span></label>
                <input type="text" name="nombre" id="nombre" 
                       value="{{ old('nombre', $residente->nombre) }}" 
                       class="campo mt-1" required>
            </div>

            <div>
                <label for="apellidos" class="etiqueta-campo block">Apellidos <span class="text-peligro">*</span></label>
                <input type="text" name="apellidos" id="apellidos" 
                       value="{{ old('apellidos', $residente->apellidos) }}" 
                       class="campo mt-1" required>
            </div>

            <div>
                <label for="dni" class="etiqueta-campo block">DNI / NIE <span class="text-peligro">*</span></label>
                <input type="text" name="dni" id="dni" 
                       value="{{ old('dni', $residente->dni) }}" 
                       class="campo mt-1" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nacimiento" class="etiqueta-campo block">Fecha de Nacimiento</label>
                    <input type="date" name="nacimiento" id="nacimiento" 
                        value="{{ old('nacimiento', $residente->nacimiento ? $residente->nacimiento->format('Y-m-d') : '') }}"
                        class="campo mt-1">
                </div>

                <div>
                    <label for="ingreso" class="etiqueta-campo block">Fecha de Ingreso</label>
                    <input type="date" name="ingreso" id="ingreso" 
                        value="{{ old('ingreso', $residente->ingreso ? $residente->ingreso->format('Y-m-d') : '') }}"
                        class="campo mt-1">
                </div>
            </div>

            @can(P::ResidentesBaja)
            <div>
                <label for="fecha_defuncion" class="etiqueta-campo block">Fecha de Defunción</label>
                <input type="date" name="fecha_defuncion" id="fecha_defuncion" 
                    value="{{ old('fecha_defuncion', $residente->fecha_defuncion ? $residente->fecha_defuncion->format('Y-m-d') : '') }}"
                    class="campo mt-1">
                <p class="text-xs text-tinta-suave mt-1">Al registrar la defunción, el residente pasa al histórico y su plaza queda libre.</p>
            </div>
            @endcan

            <div>
                <label for="grupo_sanguineo" class="etiqueta-campo block">Grupo Sanguíneo</label>
                <input type="text" name="grupo_sanguineo" id="grupo_sanguineo" 
                       value="{{ old('grupo_sanguineo', $residente->grupo_sanguineo) }}" 
                       class="campo mt-1" placeholder="Ej: A+">
            </div>

            <div>
                <label for="dependencia" class="etiqueta-campo block">Dependencia</label>
                <select name="dependencia" id="dependencia" class="campo mt-1">
                    <option value="">Selecciona nivel...</option>
                    <option value="ninguno" {{ old('dependencia', $residente->dependencia) === 'ninguno' ? 'selected' : '' }}>Ninguno</option>
                    <option value="leve" {{ old('dependencia', $residente->dependencia) === 'leve' ? 'selected' : '' }}>Leve</option>
                    <option value="moderado" {{ old('dependencia', $residente->dependencia) === 'moderado' ? 'selected' : '' }}>Moderado</option>
                    <option value="grave" {{ old('dependencia', $residente->dependencia) === 'grave' ? 'selected' : '' }}>Grave</option>
                    <option value="gran_dependiente" {{ old('dependencia', $residente->dependencia) === 'gran_dependiente' ? 'selected' : '' }}>Gran Dependiente</option>
                </select>
            </div>

            <div>
                <label for="habitacion_id" class="etiqueta-campo block">Habitación Asignada</label>
                <select name="habitacion_id" id="habitacion_id" class="campo mt-1">
                    <option value="">Sin habitación</option>
                    @foreach($habitaciones as $habitacion)
                        <option value="{{ $habitacion->id }}" 
                            {{ old('habitacion_id', $residente->habitacion_id) == $habitacion->id ? 'selected' : '' }}>
                            Habitación {{ $habitacion->numero }} ({{ ucfirst($habitacion->tipo) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('residentes.show', $residente) }}" title="Cancelar y volver al perfil" class="boton tono-neutro"><x-lucide-x />Cancelar</a>
                <button type="button" title="Guardar cambios del residente" data-swal-editar data-title="¿Confirmar modificaciones?" data-text="Se actualizará la ficha del residente con los nuevos datos introducidos." data-form-id="form-editar-residente" class="boton boton-primario">
                    <x-lucide-save />Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
