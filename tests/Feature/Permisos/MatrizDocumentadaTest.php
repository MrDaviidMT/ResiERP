<?php

namespace Tests\Feature\Permisos;

use App\Enums\Permission;
use Database\Seeders\PermissionSeeder;
use Tests\TestCase;

// Este test falla si docs/permisos.md y las plantillas del PermissionSeeder difieren en una sola celda.
class MatrizDocumentadaTest extends TestCase
{
    private const COLUMNAS = ['director', 'enfermero', 'auxiliar', 'recepcionista', 'psicologo', 'trabajador_social', 'fisioterapeuta', 'cocinero', 'mantenimiento'];

    /** @return array<string, list<string>> permiso => roles marcados con ✅ */
    private function matriz(): array
    {
        $matriz = [];

        foreach (file(base_path('docs/permisos.md'), FILE_IGNORE_NEW_LINES) as $linea) {
            if (! preg_match('/^\| `([a-z_.]+)` \|(.*)$/', $linea, $m)) {
                continue;
            }
            $celdas = array_map('trim', explode('|', $m[2]));
            $matriz[$m[1]] = array_values(array_filter(self::COLUMNAS, fn ($rol, $i) => str_contains($celdas[$i] ?? '', '✅'), ARRAY_FILTER_USE_BOTH));
        }

        return $matriz;
    }

    public function test_every_permission_in_the_code_is_documented_and_vice_versa(): void
    {
        $documentados = array_keys($this->matriz());
        $enCodigo = array_map(fn (Permission $p) => $p->value, Permission::cases());

        $this->assertEqualsCanonicalizing($enCodigo, $documentados);
    }

    public function test_role_templates_match_the_documented_matrix(): void
    {
        $plantillas = array_map(fn ($ps) => array_map(fn (Permission $p) => $p->value, $ps), PermissionSeeder::plantillas());

        foreach ($this->matriz() as $permiso => $roles) {
            foreach (self::COLUMNAS as $rol) {
                $this->assertSame(in_array($rol, $roles, true), in_array($permiso, $plantillas[$rol], true),
                    "{$permiso} / {$rol}: docs/permisos.md y PermissionSeeder no coinciden");
            }
        }
    }
}
