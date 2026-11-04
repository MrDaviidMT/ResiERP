{{-- L4: copia de la historia clínica para el derecho de acceso (Ley 41/2002 art. 18, RGPD art. 15).
     Pensada para imprimir o guardar como PDF desde el navegador. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Historia clínica — {{ $residente->nombre }} {{ $residente->apellidos }}</title>
    @vite(['resources/css/imprimir.css', 'resources/js/imprimir.js'])
</head>
<body>
    <div class="no-print acciones-impresion">
        <button type="button" data-imprimir>Imprimir o guardar como PDF</button>
        <a href="{{ route('residentes.historial', $residente) }}">Volver al historial</a>
    </div>

    <h1>Historia clínica</h1>
    <p>
        <strong>{{ $residente->nombre }} {{ $residente->apellidos }}</strong><br>
        Documento: {{ $residente->dni }} · Nacimiento: {{ $residente->nacimiento?->format('d/m/Y') }}<br>
        Ingreso: {{ $residente->ingreso?->format('d/m/Y') }}
        @if($residente->fecha_defuncion) · Defunción: {{ $residente->fecha_defuncion->format('d/m/Y') }} @endif
    </p>

    @foreach($secciones as $titulo => $filas)
        <h2>{{ $titulo }}</h2>
        @if($filas->isEmpty())
            <p class="nota">Sin registros.</p>
        @else
            <table>
                <thead><tr><th>Fecha</th><th>Detalle</th><th>Profesional</th></tr></thead>
                <tbody>
                @foreach($filas as $fila)
                    <tr class="{{ $fila['anulado'] ? 'anulado' : '' }}">
                        <td>{{ $fila['fecha'] }}</td>
                        <td>
                            {{ $fila['detalle'] }}
                            @if($fila['anulado'])
                                <span class="nota">(anulado: {{ $fila['motivo'] }})</span>
                            @endif
                        </td>
                        <td>{{ $fila['autor'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <footer>
        Copia expedida el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}.
        Incluye los registros anulados, marcados como tales, por trazabilidad de la historia clínica.
        Esta entrega queda registrada en la auditoría del sistema.
    </footer>
</body>
</html>
