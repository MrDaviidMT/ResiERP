<?php

namespace App\Enums;

// Catálogo de permisos (formato modulo.accion[.ambito]).
// Ningún nombre de permiso se escribe como texto suelto en el código: siempre a través de este enum.
enum Permission: string
{
    // Residentes
    case ResidentesVer = 'residentes.ver';
    case ResidentesVerSalud = 'residentes.ver.salud';
    case ResidentesVerDocumento = 'residentes.ver.documento';
    case ResidentesExportarHistorial = 'residentes.exportar_historial';
    case ResidentesCrear = 'residentes.crear';
    case ResidentesEditar = 'residentes.editar';
    case ResidentesBaja = 'residentes.baja';
    case ResidentesReincorporar = 'residentes.reincorporar';

    // Historial médico
    case DiagnosticosVer = 'historial.diagnosticos.ver';
    case DiagnosticosEditar = 'historial.diagnosticos.editar';
    case DiagnosticosAnular = 'historial.diagnosticos.anular';
    case AlergiasVer = 'historial.alergias.ver';
    case AlergiasEditar = 'historial.alergias.editar';
    case AlergiasAnular = 'historial.alergias.anular';
    case ConstantesVer = 'historial.constantes.ver';
    case ConstantesRegistrar = 'historial.constantes.registrar';
    case ConstantesAnular = 'historial.constantes.anular';
    case CuidadosVer = 'historial.cuidados.ver';
    case CuidadosRegistrar = 'historial.cuidados.registrar';
    case CuidadosAnular = 'historial.cuidados.anular';
    case VisitasMedicasVer = 'historial.visitas_medicas.ver';
    case VisitasMedicasGestionar = 'historial.visitas_medicas.gestionar';
    case VisitasMedicasAnular = 'historial.visitas_medicas.anular';

    // Medicación
    case MedicacionVer = 'medicacion.ver';
    case MedicacionEditar = 'medicacion.editar';
    case MedicacionRetirar = 'medicacion.retirar';
    case MedicacionAnular = 'medicacion.anular';

    // Informes (ámbito = tipo de informe)
    case InformesVerGeneral = 'informes.ver.general';
    case InformesVerEnfermeria = 'informes.ver.enfermeria';
    case InformesVerMedico = 'informes.ver.medico';
    case InformesVerFisioterapia = 'informes.ver.fisioterapia';
    case InformesVerSocial = 'informes.ver.social';
    case InformesVerPsicologico = 'informes.ver.psicologico';
    case InformesCrearGeneral = 'informes.crear.general';
    case InformesCrearEnfermeria = 'informes.crear.enfermeria';
    case InformesCrearMedico = 'informes.crear.medico';
    case InformesCrearFisioterapia = 'informes.crear.fisioterapia';
    case InformesCrearSocial = 'informes.crear.social';
    case InformesCrearPsicologico = 'informes.crear.psicologico';
    case InformesAnular = 'informes.anular';

    // Familiares
    case FamiliaresVer = 'familiares.ver';
    case FamiliaresVerContacto = 'familiares.ver.contacto';
    case FamiliaresCrear = 'familiares.crear';
    case FamiliaresEditar = 'familiares.editar';
    case FamiliaresVincular = 'familiares.vincular';
    case FamiliaresBorrar = 'familiares.borrar';

    // Dietas y menús
    case DietasVer = 'dietas.ver';
    case DietasEditar = 'dietas.editar';
    case MenusVer = 'menus.ver';
    case MenusGestionar = 'menus.gestionar';

    // Visitas de familiares
    case VisitasVer = 'visitas.ver';
    case VisitasRegistrar = 'visitas.registrar';
    case VisitasEditar = 'visitas.editar';
    case VisitasAnular = 'visitas.anular';

    // Habitaciones
    case HabitacionesVer = 'habitaciones.ver';
    case HabitacionesCrear = 'habitaciones.crear';
    case HabitacionesEditar = 'habitaciones.editar';
    case HabitacionesMantenimiento = 'habitaciones.mantenimiento';
    case HabitacionesBorrar = 'habitaciones.borrar';

    // Incidencias (ámbito = categoría)
    case IncidenciasVer = 'incidencias.ver';
    case IncidenciasCrear = 'incidencias.crear';
    case IncidenciasVerPersonal = 'incidencias.ver.personal';
    case IncidenciasAtenderSanitaria = 'incidencias.atender.sanitaria';
    case IncidenciasAtenderLimpieza = 'incidencias.atender.limpieza';
    case IncidenciasAtenderInfraestructura = 'incidencias.atender.infraestructura';
    case IncidenciasAtenderOtros = 'incidencias.atender.otros';
    case IncidenciasAtenderPersonal = 'incidencias.atender.personal';
    case IncidenciasAnular = 'incidencias.anular';

    // Actividades
    case ActividadesVer = 'actividades.ver';
    case ActividadesGestionar = 'actividades.gestionar';

    // Fichajes
    case FichajesRegistrarPropios = 'fichajes.registrar.propios';
    case FichajesVerPropios = 'fichajes.ver.propios';
    case FichajesVerTodos = 'fichajes.ver.todos';
    case FichajesCrearOtros = 'fichajes.crear.otros';
    case FichajesEditar = 'fichajes.editar';
    case FichajesAnular = 'fichajes.anular';

    // Empleados
    case EmpleadosVer = 'empleados.ver';
    case EmpleadosCrear = 'empleados.crear';
    case EmpleadosEditar = 'empleados.editar';
    case EmpleadosDesactivar = 'empleados.desactivar';
    case EmpleadosAsignarRoles = 'empleados.asignar_roles';
    case EmpleadosVerificarEmail = 'empleados.verificar_email';

    // Auditoría
    case AuditoriaVer = 'auditoria.ver';

    /**
     * Permisos que dan poder sobre el sistema o sobre registros con valor legal: gestionar empleados,
     * ver la auditoría, tocar fichajes ajenos y borrar o anular registros no clínicos.
     * Son los que vigila la anti-escalada. Anular registros clínicos NO es administrativo.
     */
    public function esAdministrativo(): bool
    {
        return str_starts_with($this->value, 'empleados.')
            || str_ends_with($this->value, '.borrar')
            || in_array($this, [self::AuditoriaVer, self::FichajesVerTodos, self::FichajesCrearOtros, self::FichajesEditar, self::FichajesAnular, self::InformesAnular, self::IncidenciasAnular, self::VisitasAnular], true);
    }

    /** Permiso para ver informes de un tipo concreto (informes.ver.{tipo}). */
    public static function verInforme(string $tipo): self
    {
        return self::from("informes.ver.{$tipo}");
    }

    /** Permiso para crear (y editar o borrar) informes de un tipo concreto (informes.crear.{tipo}). */
    public static function crearInforme(string $tipo): self
    {
        return self::from("informes.crear.{$tipo}");
    }

    /** Permiso para atender incidencias de una categoría concreta (incidencias.atender.{categoria}). */
    public static function atenderIncidencia(string $categoria): self
    {
        return self::from("incidencias.atender.{$categoria}");
    }

    /** @return list<self> */
    public static function verInformes(): array
    {
        return array_values(array_filter(self::cases(), fn (self $p) => str_starts_with($p->value, 'informes.ver.')));
    }

    /** @return list<self> */
    public static function crearInformes(): array
    {
        return array_values(array_filter(self::cases(), fn (self $p) => str_starts_with($p->value, 'informes.crear.')));
    }

    /** @return list<string> */
    public static function values(array $permisos): array
    {
        return array_map(fn (self $p) => $p->value, $permisos);
    }
}
