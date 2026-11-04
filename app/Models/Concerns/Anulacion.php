<?php

namespace App\Models\Concerns;

// Reglas compartidas de la anulación. En una clase aparte porque PHP no permite
// leer la constante de un trait directamente (Anulable::X da error).
final class Anulacion
{
    public const MOTIVO_MINIMO = 10;
}
