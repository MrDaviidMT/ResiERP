<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Documento de identidad español: DNI o NIE con su letra de control, o pasaporte.
 * Sin esta comprobación entraban documentos inventados, y la app compara DNI entre
 * residentes y familiares para detectar que son la misma persona.
 */
class DocumentoIdentidad implements ValidationRule
{
    private const LETRAS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $documento = self::normalizar((string) $value);

        if (preg_match('/^[0-9]{8}[A-Z]$/', $documento)) {
            if (! $this->letraCorrecta((int) substr($documento, 0, 8), substr($documento, -1))) {
                $fail('La letra del DNI no es correcta.');
            }

            return;
        }

        if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $documento)) {
            $numero = (int) (strpos('XYZ', $documento[0]).substr($documento, 1, 7));
            if (! $this->letraCorrecta($numero, substr($documento, -1))) {
                $fail('La letra del NIE no es correcta.');
            }

            return;
        }

        // Pasaporte u otro documento extranjero
        if (! preg_match('/^[A-Z]{2,3}[0-9]{6,7}$/', $documento)) {
            $fail('Introduce un DNI (8 números y letra), un NIE (X, Y o Z) o un pasaporte.');
        }
    }

    private function letraCorrecta(int $numero, string $letra): bool
    {
        return self::LETRAS[$numero % 23] === $letra;
    }

    /** Quita espacios y guiones y pasa a mayúsculas, para que el mismo documento no entre de dos formas. */
    public static function normalizar(string $documento): string
    {
        return strtoupper(preg_replace('/[\s.\-]/', '', trim($documento)) ?? '');
    }

    /** Letra de control de un DNI, para generar documentos válidos en factories y seeders. */
    public static function letraDe(int $numero): string
    {
        return self::LETRAS[$numero % 23];
    }
}
