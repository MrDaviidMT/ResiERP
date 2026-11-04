<?php

namespace Tests\Unit;

use App\Rules\DocumentoIdentidad;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// El documento de identidad debe ser válido, no cualquier texto
class DocumentoIdentidadTest extends TestCase
{
    private function valida(string $documento): bool
    {
        $errores = [];
        (new DocumentoIdentidad)->validate('dni', $documento, function ($mensaje) use (&$errores) {
            $errores[] = $mensaje;
        });

        return $errores === [];
    }

    public static function documentos(): array
    {
        return [
            'DNI válido' => ['12345678Z', true],
            'DNI con letra incorrecta' => ['12345678A', false],
            'DNI con guion y minúscula' => ['12.345.678-z', true],
            'NIE válido' => ['X1234567L', true],
            'NIE con letra incorrecta' => ['X1234567A', false],
            'pasaporte' => ['AB123456', true],
            'texto inventado' => ['NOSELOSE', false],
            'solo números' => ['12345678', false],
            'vacío' => ['', false],
        ];
    }

    #[DataProvider('documentos')]
    public function test_valida_documentos(string $documento, bool $esperado): void
    {
        $this->assertSame($esperado, $this->valida($documento), $documento);
    }

    public function test_normaliza_para_detectar_duplicados(): void
    {
        $this->assertSame('12345678Z', DocumentoIdentidad::normalizar(' 12.345.678-z '));
    }

    public function test_calcula_la_letra_de_control(): void
    {
        $this->assertSame('Z', DocumentoIdentidad::letraDe(12345678));
    }
}
