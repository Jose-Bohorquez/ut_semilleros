# Roles y permisos — Sistema de Semilleros IDEAD (UT)

> Actualizado 2026-08-31 (auditoría original 2026-07-28 de permisos frontend vs. backend,
> más la ronda de importación masiva de usuarios / activación de cuentas).
> Antes de este documento no existía una referencia única de qué puede hacer cada rol —
> varias inconsistencias reales entre lo que el frontend mostraba y lo que el backend
> realmente permitía se encontraron y corrigieron en esta ronda (ver `CHANGELOG.md`).

## Roles del sistema

| Rol | Descripción |
|---|---|
| `ADMIN_SISTEMA` | Control total del sistema. Gestiona usuarios, facultades, programas, y tiene acceso administrativo a todos los módulos. |
| `ADMINISTRATIVO` | Rol de apoyo administrativo con alcance similar a Admin en semilleros/proyectos/productos/resultados, pero sin gestión de usuarios/facultades/programas/coordinadores/auditoría. |
| `LIDER_SEMILLERO` | Lidera uno o más semilleros. Gestiona objetivos, resultados, proyectos, coordinadores y semilleros. |
| `ESTUDIANTE` | Consulta semilleros activos, se postula a **uno solo** a la vez, y envía propuestas de investigación. |

## Matriz de acceso por módulo (validada contra el backend real)

| Módulo | Ver | Crear/Editar | Notas |
|---|---|---|---|
| Usuarios | `ADMIN_SISTEMA` | `ADMIN_SISTEMA` | Incluye **importación masiva** (botón "Importar usuarios") — crea usuarios sin contraseña, dispara correo de activación automático. Ver `CHANGELOG.md` (sesión 2026-08-31). |
| Facultades | `ADMIN_SISTEMA`, `ADMINISTRATIVO` | `ADMIN_SISTEMA`, `ADMINISTRATIVO` | |
| Programas | `ADMIN_SISTEMA`, `ADMINISTRATIVO` | `ADMIN_SISTEMA`, `ADMINISTRATIVO` | |
| CAT | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO` | `ADMIN_SISTEMA` | |
| Áreas | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO` | `ADMIN_SISTEMA` | |
| Grupos | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO` | `ADMIN_SISTEMA` | |
| Coordinadores | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO` | `ADMIN_SISTEMA`, `LIDER_SEMILLERO` (crear/editar) · `ADMIN_SISTEMA` (activar/inactivar) | |
| **Semilleros** | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO`, `ESTUDIANTE` | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO` | Incluye **descripción** y **objetivos** (reordenables/editables/eliminables) en el mismo formulario. `ESTUDIANTE` solo consulta (PWA `/seedbeds`). |
| **Objetivos** | Todos los roles | `ADMIN_SISTEMA`, `LIDER_SEMILLERO`, `ADMINISTRATIVO` | Se gestionan **dentro** del formulario de Semilleros, no en una pantalla aparte. |
| Proyectos | `ADMIN_SISTEMA`, `ADMINISTRATIVO`, `LIDER_SEMILLERO` | según módulo | |
| Productos | `ADMIN_SISTEMA`, `ADMINISTRATIVO` | según módulo | |
| Resultados | `ADMIN_SISTEMA`, `ADMINISTRATIVO`, `LIDER_SEMILLERO` | según módulo | |
| **Solicitudes** (postulación a semillero) | Propias (`ESTUDIANTE`) · todas (`LIDER_SEMILLERO`, `ADMINISTRATIVO`, `ADMIN_SISTEMA`) | `ESTUDIANTE` crea la suya · `LIDER_SEMILLERO`/`ADMINISTRATIVO` aprueban/rechazan | **Un estudiante solo puede tener una postulación PENDIENTE o APROBADA a la vez** — no puede postularse a otro semillero mientras esa siga activa. Si es RECHAZADA, sí puede volver a intentarlo. |
| **Propuestas** | Propias (`ESTUDIANTE`) · todas (`LIDER_SEMILLERO`, `ADMINISTRATIVO`, `ADMIN_SISTEMA`) | `ESTUDIANTE` crea y **edita la suya mientras esté PENDIENTE** · `LIDER_SEMILLERO`/`ADMINISTRATIVO` aprueban/rechazan | |
| Auditoría | `ADMIN_SISTEMA` | solo lectura | |

## Reglas de negocio confirmadas (2026-07-28)

1. Un semillero tiene nombre, descripción, programa, estado, y una lista ordenable de objetivos — todo se crea/edita en un único formulario.
2. Un estudiante **no puede tener más de una postulación activa** (Pendiente o Aprobada) a la vez.
3. Un estudiante puede editar su propuesta **solo mientras esté en estado Pendiente**; una vez Aprobada o Rechazada, queda de solo lectura.
4. La aprobación/rechazo de propuestas y solicitudes es exclusiva de Líder de Semillero, Administrativo y Admin — nunca del Estudiante.

## Autenticación y activación de cuentas (agregado 2026-08-31)

- **Recuperar contraseña**: `/forgot-password` (pública) → `/reset-password?token=...&email=...`.
- **Activación de cuenta nueva** (creada por un admin sin contraseña, ej. vía importación
  masiva): mismo mecanismo y misma pantalla `/reset-password`, diferenciada por el query
  param `?activation=1` (cambia el texto pero no la lógica). Token válido 60 minutos.
- Antes de esta fecha ninguna de las dos pantallas existía en el frontend — el link del
  login apuntaba a `href="#"` y el `.env` de producción tenía `MAIL_MAILER=log` (ningún
  correo real se enviaba). Ambos corregidos — ver `CHANGELOG.md`.

## Pendiente (no bloqueante, mejora de pulido)

- Confirmar recepción visible/audible de una notificación push en un dispositivo físico
  real (el envío del lado del servidor ya se verificó exitoso contra FCM).
- Versionamiento de propuestas (historial de cambios) — mencionado como posible mejora
  futura, no confirmado como requerimiento firme.
