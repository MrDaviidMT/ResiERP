{{-- Mensajes de la última acción y errores de validación --}}
@foreach(['success' => 'exito', 'error' => 'peligro', 'info' => 'marca', 'warning' => 'aviso'] as $clave => $tono)
    @if(session($clave))
        <div class="aviso tono-{{ $tono }}" role="status">{{ session($clave) }}</div>
    @endif
@endforeach

@if($errors->any())
    <div class="aviso tono-peligro" role="alert">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
