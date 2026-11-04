<?php

namespace Tests\Feature\Seguridad;

use App\Enums\Role;
use App\Models\Familiar;
use App\Models\Residente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Los datos que se pasan a Alpine dentro de atributos HTML deben ir escapados para JavaScript (&...),
// no con entidades HTML (&quot;), porque el navegador decodifica las entidades antes de evaluar x-data.
class XssAlpineTest extends TestCase
{
    use RefreshDatabase;

    private const CARGA = 'hija&quot;}],x:alert(document.domain),z:[{&quot;';

    public function test_relative_edit_page_escapes_link_data_for_javascript(): void
    {
        $residente = Residente::factory()->create();
        $familiar = Familiar::create(['nombre' => 'Ana', 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);
        $familiar->residentes()->attach($residente->id, ['parentesco' => self::CARGA, 'autorizado' => true]);

        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->get(route('familiares.edit', $familiar))
            ->assertOk()
            ->assertDontSee('hija&quot;', false)
            // @js codifica para JSON.parse('...'): el "&" llega como "\\u0026" (escape de JavaScript), nunca como entidad HTML
            ->assertSee('hija'.str_repeat(chr(92), 2).'u0026quot;', false);
    }

    public function test_resident_relatives_page_escapes_names_for_javascript(): void
    {
        $residente = Residente::factory()->create();
        Familiar::create(['nombre' => self::CARGA, 'apellidos' => 'Pérez', 'dni' => '12345678Z', 'telefono' => '600000000']);

        $this->actingAs(User::factory()->role(Role::Director)->create())
            ->get(route('familiares.index', $residente))
            ->assertOk()
            ->assertDontSee('x-data=\'vincularFamiliar', false)
            ->assertDontSee('hija&quot;}]', false);
    }
}
