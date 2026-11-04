# Matriz de permisos

✅ = el rol tiene el permiso.

| Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|
| Director | Enfermero | Auxiliar | Recepcionista | Psicólogo | Trabajador social | Fisioterapeuta | Cocinero | Mantenimiento |

## Residentes

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `residentes.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |  |
| `residentes.ver.salud` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `residentes.ver.documento` | ✅ | ✅ |  | ✅ |  | ✅ |  |  |  |
| `residentes.exportar_historial` | ✅ |  |  |  |  | ✅ |  |  |  |
| `residentes.crear` | ✅ |  |  |  |  | ✅ |  |  |  |
| `residentes.editar` | ✅ |  |  |  |  | ✅ |  |  |  |
| `residentes.baja` | ✅ |  |  |  |  |  |  |  |  |
| `residentes.reincorporar` | ✅ |  |  |  |  |  |  |  |  |

## Historial médico

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `historial.diagnosticos.ver` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `historial.diagnosticos.editar` |  | ✅ |  |  | ✅ |  | ✅ |  |  |
| `historial.diagnosticos.anular` |  | ✅ |  |  | ✅ |  | ✅ |  |  |
| `historial.alergias.ver` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `historial.alergias.editar` |  | ✅ |  |  |  |  |  |  |  |
| `historial.alergias.anular` |  | ✅ |  |  |  |  |  |  |  |
| `historial.constantes.ver` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `historial.constantes.registrar` |  | ✅ | ✅ |  |  |  |  |  |  |
| `historial.constantes.anular` |  | ✅ | ✅ |  |  |  |  |  |  |
| `historial.cuidados.ver` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `historial.cuidados.registrar` |  | ✅ | ✅ |  |  |  |  |  |  |
| `historial.cuidados.anular` |  | ✅ | ✅ |  |  |  |  |  |  |
| `historial.visitas_medicas.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |  |  |
| `historial.visitas_medicas.gestionar` | ✅ | ✅ |  | ✅ |  |  | ✅ |  |  |
| `historial.visitas_medicas.anular` | ✅ | ✅ |  | ✅ |  |  | ✅ |  |  |

## Medicación

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `medicacion.ver` | ✅ | ✅ | ✅ |  |  |  |  |  |  |
| `medicacion.editar` |  | ✅ |  |  |  |  |  |  |  |
| `medicacion.retirar` |  | ✅ |  |  |  |  |  |  |  |
| `medicacion.anular` |  | ✅ |  |  |  |  |  |  |  |

## Informes

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `informes.ver.general` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `informes.ver.enfermeria` | ✅ | ✅ | ✅ |  | ✅ |  | ✅ |  |  |
| `informes.ver.medico` | ✅ | ✅ | ✅ |  | ✅ |  | ✅ |  |  |
| `informes.ver.fisioterapia` | ✅ | ✅ |  |  |  |  | ✅ |  |  |
| `informes.ver.social` | ✅ |  |  |  |  | ✅ |  |  |  |
| `informes.ver.psicologico` | ✅ |  |  |  | ✅ |  |  |  |  |
| `informes.crear.general` | ✅ | ✅ |  |  | ✅ | ✅ | ✅ |  |  |
| `informes.crear.enfermeria` |  | ✅ |  |  |  |  |  |  |  |
| `informes.crear.medico` |  | ✅ |  |  |  |  |  |  |  |
| `informes.crear.fisioterapia` |  |  |  |  |  |  | ✅ |  |  |
| `informes.crear.social` |  |  |  |  |  | ✅ |  |  |  |
| `informes.crear.psicologico` |  |  |  |  | ✅ |  |  |  |  |
| `informes.anular` | ✅ |  |  |  |  |  |  |  |  |

## Familiares

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `familiares.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |  |  |
| `familiares.ver.contacto` | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |  |
| `familiares.crear` | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |  |
| `familiares.editar` | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |  |
| `familiares.vincular` | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |  |
| `familiares.borrar` | ✅ |  |  |  |  |  |  |  |  |

## Dietas y menús

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `dietas.ver` | ✅ | ✅ | ✅ |  |  |  |  | ✅ |  |
| `dietas.editar` |  | ✅ |  |  |  |  |  |  |  |
| `menus.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `menus.gestionar` | ✅ |  |  |  |  |  |  | ✅ |  |

## Visitas de familiares

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `visitas.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |  |  |
| `visitas.registrar` | ✅ | ✅ | ✅ | ✅ |  |  |  |  |  |
| `visitas.editar` | ✅ |  |  | ✅ |  |  |  |  |  |
| `visitas.anular` | ✅ |  |  |  |  |  |  |  |  |

## Habitaciones

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `habitaciones.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |  | ✅ |
| `habitaciones.crear` | ✅ |  |  |  |  |  |  |  |  |
| `habitaciones.editar` | ✅ |  |  |  |  |  |  |  |  |
| `habitaciones.mantenimiento` | ✅ |  |  | ✅ |  |  |  |  | ✅ |
| `habitaciones.borrar` | ✅ |  |  |  |  |  |  |  |  |

## Incidencias

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `incidencias.ver` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `incidencias.crear` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `incidencias.ver.personal` | ✅ |  |  |  |  |  |  |  |  |
| `incidencias.atender.sanitaria` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `incidencias.atender.limpieza` | ✅ |  | ✅ | ✅ |  |  |  | ✅ |  |
| `incidencias.atender.infraestructura` | ✅ |  |  | ✅ |  |  |  | ✅ | ✅ |
| `incidencias.atender.otros` | ✅ |  |  | ✅ | ✅ | ✅ |  |  |  |
| `incidencias.atender.personal` | ✅ |  |  |  |  |  |  |  |  |
| `incidencias.anular` | ✅ |  |  |  |  |  |  |  |  |

## Actividades

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `actividades.ver` | ✅ | ✅ | ✅ |  | ✅ | ✅ | ✅ |  |  |
| `actividades.gestionar` | ✅ |  |  |  | ✅ | ✅ |  |  |  |

## Fichajes

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `fichajes.registrar.propios` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `fichajes.ver.propios` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `fichajes.ver.todos` | ✅ |  |  |  |  |  |  |  |  |
| `fichajes.crear.otros` | ✅ |  |  |  |  |  |  |  |  |
| `fichajes.editar` | ✅ |  |  |  |  |  |  |  |  |
| `fichajes.anular` | ✅ |  |  |  |  |  |  |  |  |

## Empleados

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `empleados.ver` | ✅ |  |  |  |  |  |  |  |  |
| `empleados.crear` | ✅ |  |  |  |  |  |  |  |  |
| `empleados.editar` | ✅ |  |  |  |  |  |  |  |  |
| `empleados.desactivar` | ✅ |  |  |  |  |  |  |  |  |
| `empleados.asignar_roles` | ✅ |  |  |  |  |  |  |  |  |
| `empleados.verificar_email` | ✅ |  |  |  |  |  |  |  |  |

## Auditoría

| Permiso | Dir | Enf | Aux | Rec | Psi | TS | Fis | Coc | Mto |
|---|---|---|---|---|---|---|---|---|---|
| `auditoria.ver` | ✅ |  |  |  |  |  |  |  |  |
