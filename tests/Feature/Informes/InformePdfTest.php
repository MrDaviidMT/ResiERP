<?php

namespace Tests\Feature\Informes;

use App\Models\Informe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InformePdfTest extends TestCase
{
    use RefreshDatabase;

    private function pdf(): UploadedFile
    {
        $objetos = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>',
        ];

        $contenido = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objetos as $i => $objeto) {
            $offsets[] = strlen($contenido);
            $contenido .= ($i + 1)." 0 obj\n{$objeto}\nendobj\n";
        }

        $xref = strlen($contenido);
        $contenido .= "xref\n0 ".(count($objetos) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $contenido .= sprintf("%010d 00000 n \n", $offset);
        }
        $contenido .= "trailer\n<< /Size ".(count($objetos) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return UploadedFile::fake()->createWithContent('informe.pdf', $contenido);
    }

    public function test_pdf_is_stored_on_the_private_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->role('enfermero')->create();

        $this->actingAs($user)->post(route('informes.store'), [
            'titulo' => 'Revisión mensual',
            'tipo' => 'enfermeria',
            'contenido' => 'Sin incidencias.',
            'pdf' => $this->pdf(),
        ])->assertSessionHasNoErrors();

        $informe = Informe::sole();
        Storage::disk('local')->assertExists($informe->pdf_path);
        Storage::disk('public')->assertMissing($informe->pdf_path);
    }

    public function test_pdf_is_only_served_to_authenticated_users(): void
    {
        Storage::fake('local');
        $autor = User::factory()->role('enfermero')->create();
        Storage::disk('local')->put('informes/prueba.pdf', '%PDF-1.4');
        $informe = tap((new Informe(['titulo' => 'Revisión', 'tipo' => 'enfermeria', 'contenido' => 'Texto', 'pdf_path' => 'informes/prueba.pdf']))
            ->forceFill(['user_id' => $autor->id]))->save();

        $this->get(route('informes.pdf', $informe))->assertRedirect(route('login'));
        $this->actingAs($autor)->get(route('informes.pdf', $informe))->assertOk();
    }

    // el PDF es documentación clínica y no se pierde
    public function test_replacing_the_pdf_keeps_the_previous_file(): void
    {
        Storage::fake('local');
        $autora = User::factory()->role('enfermero')->create();
        $this->actingAs($autora)->post(route('informes.store'), [
            'titulo' => 'Revisión', 'tipo' => 'enfermeria', 'contenido' => 'Texto', 'pdf' => $this->pdf(),
        ]);
        $informe = Informe::sole();
        $anterior = $informe->pdf_path;

        $this->actingAs($autora)->put(route('informes.update', $informe), [
            'titulo' => 'Revisión', 'tipo' => 'enfermeria', 'contenido' => 'Texto', 'pdf' => $this->pdf(),
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($anterior, $informe->fresh()->pdf_path);
        Storage::disk('local')->assertExists($anterior);
        Storage::disk('local')->assertExists($informe->fresh()->pdf_path);
    }

    public function test_annulling_a_report_keeps_its_pdf(): void
    {
        Storage::fake('local');
        $director = User::factory()->role('director')->create();
        Storage::disk('local')->put('informes/prueba.pdf', '%PDF-1.4');
        $this->actingAs($director);
        $informe = Informe::create(['titulo' => 'Revisión', 'tipo' => 'general', 'contenido' => 'Texto', 'pdf_path' => 'informes/prueba.pdf']);

        $this->post(route('informes.anular', $informe), ['motivo' => 'Informe duplicado por error']);

        Storage::disk('local')->assertExists('informes/prueba.pdf');
    }
}
