<?php

namespace App\Providers;

use App\Models\Actividad;
use App\Models\Alergia;
use App\Models\ConstanteVital;
use App\Models\CuidadoDiario;
use App\Models\Diagnostico;
use App\Models\Dieta;
use App\Models\Familiar;
use App\Models\Fichaje;
use App\Models\Habitacion;
use App\Models\Incidencia;
use App\Models\Informe;
use App\Models\Medicacion;
use App\Models\Menu;
use App\Models\Residente;
use App\Models\User;
use App\Models\Visita;
use App\Models\VisitaMedica;
use App\Observers\AuditObserver;
use App\Policies\RegistroClinicoPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Modo estricto de Eloquent: en desarrollo y tests, error inmediato ante consultas N+1,
        // atributos descartados por $fillable y atributos que no se cargaron
        Model::shouldBeStrict(! $this->app->isProduction());

        // En producción no se rompe la página por un N+1: se registra para corregirlo
        if ($this->app->isProduction()) {
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
                Log::warning('Consulta N+1: carga diferida de ['.$relation.'] en '.$model::class);
            });
        }

        Residente::observe(AuditObserver::class);
        Habitacion::observe(AuditObserver::class);
        Familiar::observe(AuditObserver::class);
        Incidencia::observe(AuditObserver::class);
        Medicacion::observe(AuditObserver::class);
        Diagnostico::observe(AuditObserver::class);
        ConstanteVital::observe(AuditObserver::class);
        VisitaMedica::observe(AuditObserver::class);
        Dieta::observe(AuditObserver::class);
        Menu::observe(AuditObserver::class);
        Visita::observe(AuditObserver::class);
        CuidadoDiario::observe(AuditObserver::class);
        Informe::observe(AuditObserver::class);
        Actividad::observe(AuditObserver::class);
        User::observe(AuditObserver::class);
        Fichaje::observe(AuditObserver::class);
        Alergia::observe(AuditObserver::class);

        // Una única Policy para la regla común de los registros clínicos (solo el autor edita)
        foreach ([Diagnostico::class, Alergia::class, ConstanteVital::class,
            VisitaMedica::class, CuidadoDiario::class] as $modelo) {
            Gate::policy($modelo, RegistroClinicoPolicy::class);
        }

        // Acceso a datos de salud. Mínimo 12 caracteres, letras y números, y
        // contraseña no filtrada (se consulta haveibeenpwned por hash parcial: la contraseña no sale de aquí).
        // En los tests no se comprueba: dependería de una llamada de red.
        Password::defaults(function () {
            $regla = Password::min(16)->letters()->numbers();

            return $this->app->runningUnitTests() ? $regla : $regla->uncompromised();
        });

        // El superadministrador (perfil técnico) tiene todos los permisos.
        // El resto de comprobaciones usan permisos de spatie/laravel-permission (App\Enums\Permission).
        Gate::before(fn (User $user) => $user->esSuperadmin() ? true : null);
    }
}
