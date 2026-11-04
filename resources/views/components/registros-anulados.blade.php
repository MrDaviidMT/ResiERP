@props(['registros'])
{{-- Lo anulado no se muestra como vigente, pero se puede consultar con su motivo --}}
@if($registros->isNotEmpty())
    <div x-data="{ abierto: false }" class="mt-3">
        <button type="button" @click="abierto = !abierto" class="text-xs text-tinta-suave hover:text-tinta cursor-pointer">
            <span x-text="abierto ? 'Ocultar anulados' : 'Mostrar anulados ({{ $registros->count() }})'"></span>
        </button>
        <ul x-show="abierto" x-cloak class="mt-2 space-y-1">
            @foreach($registros as $registro)
                <li class="text-xs text-tinta-suave border-l-2 border-peligro pl-2">
                    <span class="line-through">{{ $registro['resumen'] }}</span>
                    <span class="block">Anulado por {{ $registro['por'] ?? '—' }} el {{ $registro['anulado_at']->format('d/m/Y H:i') }}. Motivo: {{ $registro['motivo'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
