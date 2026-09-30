# Roles y permisos — Sistema de Semilleros IDEAD (UT)

> Reescrito el 2026-09-30 tras la auditoría de CU01–CU25 (`docs/validacion/AUDITORIA_CU01-CU25_2026-09-30.md`), contra el
> código real (`api/routes/api.php`, controladores, `core/router.js`, `layout/layout.view.js`). La versión anterior (2026-08-31)
> estaba desactualizada. **Si este documento contradice la especificación oficial
> (`docs/especificacion/`), manda la especificación** (decisión de Jose, 2026-09-28); las diferencias conocidas están al final.

## Roles del sistema

| Rol | Descripción |
|---|---|
| `ADMIN_SISTEMA` | Control total: usuarios, catálogos, semilleros, auditoría, SIA y permisos. Resuelve solicitudes. En Propuestas solo consulta. |
| `ADMINISTRATIVO` | **Solo consulta** en semilleros, objetivos, resultados, integrantes, grupos, coordinadores, catálogos y solicitudes. Crea y edita Proyectos y Productos. Evalúa propuestas. |
| `LIDER_SEMILLERO` | Gestiona **solo los semilleros de los que es responsable** (RN06): su información, objetivos, resultados, integrantes y solicitudes recibidas. Crea grupos y proyectos. Consulta el resto. |
| `ESTUDIANTE` | Usa la PWA: explora semilleros **activos**, envía solicitudes, registra propuestas y consulta las suyas. |

## Matriz de acceso por módulo (contra el backend real, 2026-09-30)

Leyenda: **E** = escribe (crea/edita/activa-inactiva) · **C** = solo consulta · — = sin acceso.

| Módulo | ADMIN_SISTEMA | ADMINISTRATIVO | LIDER_SEMILLERO | ESTUDIANTE | Notas |
|---|---|---|---|---|---|
| Usuarios | E | — | — | — | `GET /users` devuelve datos mínimos (id, nombre, rol, estado) a Líder/Administrativo para los selectores de otros módulos; el detalle completo es solo del Administrador. Incluye importación masiva. |
| Facultades | E | C | C | — | La interfaz muestra los botones de escritura solo al Administrador (A5 de CU07–CU10). |
| Programas | E | C | C | C¹ | Igual; el estudiante los lee para «Ser miembro» y para registrar propuestas. |
| CAT | E | C | C | — | |
| Áreas | E | C | C | C¹ | El estudiante las lee para registrar propuestas. |
| Grupos | E | C | E | — | RN08: código único, en mayúsculas. |
| Coordinadores | E | C | C | — | Teléfono cifrado; documento único. |
| Semilleros | E | C | E (solo los suyos) | C (solo ACTIVOS) | El estudiante no recibe datos internos (referencia de autorización, motivo de inactivación, correos/teléfonos). El Administrador asigna o reasigna el líder (CU14-A2). El **estado** solo cambia con Activar/Inactivar (motivo obligatorio al inactivar). |
| Objetivos, Resultados | E | C | E (solo los suyos) | Objetivos ACTIVOS | RN06. |
| Integrantes | E | C | E (solo los suyos) | — | Código estudiantil único entre los integrantes activos del semillero. |
| Proyectos | E | E | E | — | Sin RN06 (hallazgo abierto). |
| Productos | E | E | — en el menú² | — | |
| **Solicitudes** | E (resuelve) | C | E (solo las de sus semilleros) | Crea y ve las suyas | Nacen siempre `PENDIENTE`; aprobar/rechazar solo por CU24. Rechazar exige motivo; una solicitud resuelta no se reabre. |
| **Propuestas** | C | E³ | E³ | Crea, ve y (mientras esté Recibida) edita las suyas | Estados internos `PENDIENTE/APROBADA/RECHAZADA`, mostrados al estudiante como Recibida/Viable/Archivada. |
| Auditoría | C | — | — | — | Solo lectura. No guarda valores anteriores/nuevos (CU29 pendiente). |
| SIA (panel), Permisos (RBAC) | E | — | — | — | El acceso real lo define el rol; `permission:` aún no se aplica a las rutas. |

¹ Solo lectura de lo necesario para sus formularios. ² La API lo permite pero el menú del Líder no lo incluye.
³ Ver "Diferencias con la especificación".

## Reglas de negocio vigentes

1. **RN06**: el Líder solo modifica lo suyo (semillero, objetivos, resultados, integrantes, solicitudes). El Administrador no tiene restricción.
2. **Una solicitud activa por estudiante** en todo el sistema (Pendiente o Aprobada); si la rechazan, puede volver a intentar. (Más estricta que RN05 de la spec, por decisión del proyecto.)
3. **RN15**: un estudiante puede registrar como máximo **5 propuestas cada 24 horas** (429 al superarlo).
4. Nada se elimina (RN01): se inactiva o cambia de estado. Excepción documentada: quitar un objetivo desde «Editar» semillero lo borra.
5. **Perfil (CU05)**: el usuario solo edita su **teléfono** y su **contraseña**; nombre y correo los cambia el Administrador (CU06).
6. Teléfonos de coordinadores, integrantes, usuarios, propuestas y solicitudes se guardan **cifrados** con `APP_KEY`.
7. Un semillero INACTIVO no se ve ni recibe solicitudes; al inactivarlo, sus solicitudes pendientes se rechazan solas («Semillero inactivo»).

## Autenticación y activación de cuentas

- **Recuperar contraseña**: `/forgot-password` → `/reset-password?token=...&email=...`. Token de 60 minutos; solo usuarios ACTIVOS; máx. 3 solicitudes por correo cada 10 minutos.
- **Activación de cuenta nueva** (creada sin contraseña, p. ej. por importación masiva): misma pantalla con `?activation=1`; el enlace dura 7 días.
- Los estudiantes pueden entrar con Google institucional (`@ut.edu.co`); el login con contraseña sigue abierto (decisión de Jose, CU01-E5 no se aplica).

## Diferencias con la especificación (para decidir)

- **Evaluación de propuestas (CU27)**: la spec dice que evalúa el Administrativo y que el Líder solo consulta las de las áreas de sus semilleros. El código permite evaluar al Líder y al Administrativo, y el Líder ve todas. El Administrador ve «Editar» pero la API responde 403. Se corrige con CU27.
- **Vocabulario de propuestas**: spec Recibida/Viable/Archivada frente a `PENDIENTE/APROBADA/RECHAZADA` internos (puente reversible en CU26).
- **Auditoría (CU29)**: falta guardar valores anteriores/nuevos, IP e inmutabilidad; varios CU (CU07–CU14) lo exigen en su alterno A2.
- **Reportes (CU28)**: no existe el módulo.
- Menú lateral de escritorio: «Solicitudes» y «Propuestas» solo están en la barra inferior (móvil); en escritorio se llega por `/requests` y `/proposals`.

## Pendiente (no bloqueante)

- Confirmar recepción visible/audible de una notificación push en un dispositivo físico real.
- Versionamiento de propuestas (historial de cambios): posible mejora futura, no es requisito firme.
