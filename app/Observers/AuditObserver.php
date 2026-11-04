<?php

namespace App\Observers;

use App\Models\Log;
use Illuminate\Database\Eloquent\Model;

// Auditoría automática con minimización de datos (RGPD art. 5.1.c):
// - alta: no se copia el registro (ya existe en su tabla; se guarda quién y cuándo)
// - edición: solo los campos que cambian, con su valor anterior y el nuevo
// - borrado definitivo: instantánea, porque es la única constancia de lo que desapareció
// Nunca se guardan atributos ocultos (contraseñas, tokens).
class AuditObserver
{
    // Ya constan en la fecha del propio registro de auditoría
    private const IGNORADOS = ['updated_at'];

    protected function log(Model $model, string $action, ?array $changes = null): void
    {
        if ($model instanceof Log) {
            return;
        }

        Log::registrar($model, $action, $changes);
    }

    public function created(Model $model): void
    {
        $this->log($model, 'created');
    }

    public function updated(Model $model): void
    {
        $excluir = [...$model->getHidden(), ...self::IGNORADOS];
        $nuevos = collect($model->getChanges())->except($excluir);

        // Cambios solo en campos ignorados u ocultos (p. ej. remember_token al iniciar sesión): nada que auditar
        if ($nuevos->isEmpty()) {
            return;
        }

        // En el evento "updated", getOriginal() todavía devuelve los valores anteriores
        $this->log($model, 'updated', [
            'old' => collect($model->getOriginal())->only($nuevos->keys())->toArray(),
            'new' => $nuevos->toArray(),
        ]);
    }

    public function deleted(Model $model): void
    {
        // Baja lógica (SoftDeletes): el registro sigue existiendo, no hace falta instantánea
        $esBajaLogica = method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting();

        $this->log($model, 'deleted', $esBajaLogica ? null : collect($model->getAttributes())->except($model->getHidden())->toArray());
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored');
    }
}
