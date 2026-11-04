<?php

namespace Tests\Feature\Auditoria;

use App\Models\Concerns\Anulable;
use App\Models\Log;
use App\Models\User;
use App\Observers\AuditObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class RegistroAnulablePrueba extends Model
{
    use Anulable;

    protected $table = 'anulables_prueba';

    protected $fillable = ['texto'];
}

// El mecanismo común de anulación
class AnulableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('anulables_prueba', function (Blueprint $table) {
            $table->id();
            $table->string('texto');
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamps();
        });

        // Como los modelos reales: con el Observer de auditoría registrado
        RegistroAnulablePrueba::observe(AuditObserver::class);

        $this->actingAs(User::factory()->create());
    }

    public function test_annulled_records_are_excluded_by_default(): void
    {
        $vigente = RegistroAnulablePrueba::create(['texto' => 'vigente']);
        $anulado = RegistroAnulablePrueba::create(['texto' => 'anulado']);

        $anulado->anular('Registrado en el residente equivocado');

        $this->assertSame([$vigente->id], RegistroAnulablePrueba::pluck('id')->all());
        $this->assertCount(2, RegistroAnulablePrueba::conAnulados()->get());
        $this->assertSame([$anulado->id], RegistroAnulablePrueba::soloAnulados()->pluck('id')->all());
    }

    public function test_annulment_records_who_when_why_and_is_audited(): void
    {
        $registro = RegistroAnulablePrueba::create(['texto' => 'texto']);

        $registro->anular('Registrado en el residente equivocado');

        $registro = RegistroAnulablePrueba::conAnulados()->find($registro->id);
        $this->assertTrue($registro->estaAnulado());
        $this->assertSame(auth()->id(), $registro->anulado_por);
        $this->assertSame('Registrado en el residente equivocado', $registro->motivo_anulacion);

        $log = Log::where('action', 'annulled')->sole();
        $this->assertSame('Registrado en el residente equivocado', $log->changes['motivo']);
        $this->assertSame(0, Log::where('action', 'updated')->count());
    }

    public function test_a_reason_is_required(): void
    {
        $registro = RegistroAnulablePrueba::create(['texto' => 'texto']);

        $this->expectException(InvalidArgumentException::class);
        $registro->anular('   error  ');
    }

    public function test_annulled_records_cannot_be_modified_or_annulled_again(): void
    {
        $registro = RegistroAnulablePrueba::create(['texto' => 'texto']);
        $registro->anular('Registrado en el residente equivocado');
        $registro = RegistroAnulablePrueba::conAnulados()->find($registro->id);

        try {
            $registro->update(['texto' => 'cambiado']);
            $this->fail('Se pudo modificar un registro anulado');
        } catch (LogicException) {
        }
        $this->assertSame('texto', RegistroAnulablePrueba::conAnulados()->find($registro->id)->texto);

        $this->expectException(LogicException::class);
        $registro->anular('Segundo intento de anulación');
    }

    public function test_records_cannot_be_physically_deleted(): void
    {
        $registro = RegistroAnulablePrueba::create(['texto' => 'texto']);

        try {
            $registro->delete();
            $this->fail('Se pudo borrar un registro anulable');
        } catch (LogicException) {
        }

        $this->assertSame(1, RegistroAnulablePrueba::count());
    }
}
