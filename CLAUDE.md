# CLAUDE.md — UT Semilleros

PWA de Sistema de Semilleros de Investigación, Universidad del Tolima. Frontend vanilla JS
(`core/`, `modules/`, `layout/`, `services/`) + backend Laravel 12 en `api/`. Producción:
`https://ut-edu.online/`.

**Esta carpeta contiene además dos proyectos hermanos independientes, no mezclarlos:**
- `SGAA/` — sistema de asistencia académica (tiene su propio `CLAUDE.md`, léelo si trabajas ahí).
- `todo_ut-edu.space_old/` — scaffold Laravel descartado, nunca desarrollado, historia muerta.

## Especificación oficial (fuente de verdad funcional)

`docs/especificacion/` tiene el documento del proyecto (entregable SENA ADSO, ficha 3311941,
adaptado de INITIUM 2020):
- `Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md`: **15 reglas de negocio (RN01–RN15),
  16 RF, 16 RNF, 30 casos de uso extendidos** (flujo básico, alternos A*n*, excepciones E*n*,
  postcondiciones) y matrices de trazabilidad.
- `Documentacion_Tecnica_SemillerosUT.md`: trimestres I–V (BPMN, HU, clases, despliegue,
  seguridad, pruebas y manuales).
- `uml/`: diagramas de CU por actor, en `.puml` y `.png`.

**Decisión (Jose, 2026-09-28): lo funcional lo manda el documento; el stack es el real.** Los CU,
RF, RN y RNF son obligatorios, y lo que falte se implementa. El stack se queda como está (MySQL,
SPA vanilla JS, Hostinger compartido), y es la Documentación Técnica la que se actualiza para
describir la arquitectura real. El documento dice MongoDB, Bootstrap 5/Blade, Swagger, Pest y
nginx+supervisor; nada de eso se usa ni corre en el plan compartido. CU02 sí se implementa con
**Google OAuth** restringido al dominio institucional (RN04).

**Numeración:** los comentarios `(RFxx / CUxx)` de `api/routes/api.php` siguen el diseño de 2020
(14 CU). En esos comentarios, "CUxx" es en realidad el RF del mismo número. Los CU vigentes son
CU01–CU30 del documento (ver su tabla §1.4 de equivalencias).

Casos de uso: CU01 login web · CU02 login PWA con Google · CU03 cerrar sesión · CU04 recuperar
contraseña · CU05 perfil · CU06 usuarios · CU07 facultades · CU08 programas · CU09 CAT · CU10 áreas
· CU11 grupos (Líder) · CU12 coordinadores · CU13 registrar semillero · CU14 modificar semillero ·
CU15 estado de semillero · CU16 consultar semilleros (web) · CU17 semilleros por facultad (PWA) ·
CU18 detalle de semillero (PWA) · CU19 objetivos · CU20 resultados · CU21 integrantes · CU22 enviar
solicitud · CU23 mis solicitudes · CU24 gestionar solicitudes · CU25 registrar propuesta · CU26 mis
propuestas · CU27 evaluar propuestas (Administrativo) · CU28 reportes · CU29 registrar auditoría
(include) · CU30 consultar auditoría.

**Avance de la validación 1 a 1** (acta por CU en `docs/validacion/CUxx.md`, versionada): CU01
web ✅ desplegado y validado en producción · PWA ✅ desplegada y validada en producción (2026-09-28) · RNF01 ✅ (instalación desde un clon limpio) · RNF03
parcial (tokens de 8 h / 30 d). CU02 + RF02 + RF16 + RNF02 ✅ en local (2026-09-28, acta
`docs/validacion/CU02.md`); **Client ID de Google ya instalado en producción (2026-09-29)** —
el botón real ya aparece; falta la primera prueba real de un usuario con su cuenta de Google
(la pantalla de consentimiento está en modo prueba, restringida a usuarios de prueba).
CU03 + RF03 + RNF03 ✅ desplegados y validados en producción (2026-09-28, 14/14, ZAP 0 altos; acta `docs/validacion/CU03.md`). **Los teléfonos de
coordinadores se cifran con APP_KEY: si se pierde o se regenera el `.env`, no se pueden leer.**
CU04 + RF04 + RNF04 ✅ desplegados y validados en producción (2026-09-29, 235 tests + 18/18 local +
9/9 producción; acta `docs/validacion/CU04.md`). RN10 (contraseña fuerte) ya aplica en todo el
sistema. Manuales de usuario/técnico/instalación en `docs/manuales/`. **Hostinger no permite
`crontab` por SSH: `QUEUE_CONNECTION` de producción quedó en `sync` (correo se envía al instante,
sin los 3 reintentos de E4) hasta que Jose agregue el cron desde hPanel (pasos en
`docs/manuales/manual_tecnico.md` §5 y en el acta) y confirme para volverlo a `database`.**
CU05 + RF05 + RNF05 ✅ desplegados y validados en producción (2026-09-29, 256 tests + 18/18 local +
validación por API en vivo; acta `docs/validacion/CU05.md`). Perfil con teléfono cifrado y cambio
de contraseña con verificación; áreas de conocimiento obligatorias en semilleros/propuestas
(bug de rol ESTUDIANTE en `GET /areas` corregido); referencia de autorización (RN02) obligatoria
para roles no-ESTUDIANTE. Incluye el rediseño de UX/UI del dashboard (sidebar agrupado, gráficas
balanceadas, fix del eje del gráfico "Semilleros por facultad").

**Aviso de numeración (Jose, 2026-09-29):** CU06 no está ligado a RF06/RNF06 — cada uno pertenece a
un CU distinto (ver tabla de equivalencias de la especificación §1.4 antes de agrupar un CU con un
RF/RNF del mismo número; no siempre coinciden, a diferencia de CU01-05 que sí estaban alineados).
CU06 + RF01 ✅ desplegado y validado en producción (2026-09-29, 273 tests + 4/4 E2E local + 1/1
verificación en vivo; acta `docs/validacion/CU06.md`). Paginación (ya existía vía DataTables) + filtros por rol/estado
nuevos en el listado de usuarios (`config.filters`, reutilizable por cualquier módulo CRUD). El
formulario individual de creación ya no acepta `ESTUDIANTE` (CU06-A4); **la carga masiva de
estudiantes se mantiene sin cambios** — decisión explícita de Jose de priorizar la funcionalidad
real ya usada en producción sobre la letra literal de la especificación. Quedan pendientes para una
ronda futura: E3 (no poder auto-inactivarse ni inactivar al último ADMIN_SISTEMA), E4 (reenviar
correo de activación) y A1 (último acceso y semilleros a cargo del líder en el detalle).

CU07 no está ligado a RF07/RNF07 tampoco — RF07 es coordinadores (CU12), RNF07 es rendimiento
(transversal). CU07 + RF02 (facultades) ✅ desplegado en producción (acta `docs/validacion/CU07.md`).
Se agregó `config.pageLength` genérico a `crud.engine.js` (15/página en Facultades, 10 default sin
cambio en el resto) y filtro por estado reutilizando `config.filters` de CU06. Se descartó a
propósito la extensión DataTables Responsive: el sistema ya tiene su propio responsive
(`mobile-card-table`, `style.css` @768px) y dos mecanismos competirían.

CU08 tampoco liga con RF08/RNF08 — RF08 es objetivos (CU19), RNF08 es disponibilidad (transversal).
CU08 + RF03 (programas) ✅ desplegado en producción (acta `docs/validacion/CU08.md`), mismo patrón
que CU07: filtro por estado + `pageLength:15`.

CU09 tampoco liga con RF09/RNF09 — RF09 es resultados (CU20), RNF09 es usabilidad/accesibilidad
(transversal). CU09 + RF04 (CAT) ✅ desplegado en producción (acta `docs/validacion/CU09.md`),
mismo patrón. **Hallazgo sin corregir:** la especificación pide teléfono principal (`phone1`)
obligatorio; el código solo exige "al menos uno de los 3 teléfonos" — pendiente de decisión de
Jose antes de tocarlo (podría romper CAT ya registrados con solo `phone2`/`phone3`).

**Ronda 2 de pendientes (2026-09-29)**, antes de seguir con CU10: CU06-E3 (bloquear
auto-inactivarse / inactivar al último `ADMIN_SISTEMA` activo — `guardAgainstLockout()` en
`UserController`), CU06-E4 (`POST /users/{id}/resend-activation`, botón "Reenviar activación"),
y A1 (endpoint `show()` + botón "Ver" con `Swal`) en Facultades/Programas/CAT. `phone1` de CAT
se mantiene como "al menos uno de 3" (decisión de Jose, no se fuerza estricto).

**Ronda 3 (2026-09-29):** CU06-A1 resuelto — `users.last_login_at` (migración nueva, se
actualiza en cada login exitoso desde `issueSession()`) y `led_seedbeds` en
`UserController::show()` (semilleros donde el usuario es `LIDER` en el pivot `seedbed_user`,
solo para rol `LIDER_SEMILLERO`). **CU06 queda completo.**

CU10 tampoco liga con RF10/RNF10 — RF10 es solicitudes (CU22/23/24), RNF10 es compatibilidad
(transversal). CU10 + RF05 (áreas) ✅ desplegado en producción (acta `docs/validacion/CU10.md`),
mismo patrón que CU07/08/09 (filtro + `pageLength:15` + `show()`).

CU11 tampoco liga con RF11/RNF11 — RF11 es propuestas (CU25-27), RNF11 es mantenibilidad
(transversal). CU11 + RF06 (grupos) ✅ desplegado en producción (acta `docs/validacion/CU11.md`).
**Hallazgo real corregido**: el actor principal de CU11 es el Líder de semillero (no el Admin,
a diferencia de CU07-10) — el código se lo impedía en 3 capas (backend `role:`, router, sidebar
sin enlace). Ya corregido: escriben `ADMIN_SISTEMA` y `LIDER_SEMILLERO`, `ADMINISTRATIVO` solo
consulta. También se corrigió que el código de grupo no se normalizaba a mayúsculas (RN08) y que
`status` no se validaba. 291 tests en total.

CU12 tampoco liga con RF12/RNF12 — RF12 es integrantes (CU21), RNF12 es protección de datos
personales (transversal, ya cumplida: teléfono de coordinador cifrado). CU12 + RF07
(coordinadores) ✅ desplegado en producción (acta `docs/validacion/CU12.md`). 2 hallazgos reales
corregidos: faltaba el campo "documento" (único, migración nueva `coordinators.document`
nullable) — se usaba el correo como único en su lugar; y el Líder podía escribir coordinadores
(con un fix C-16 previo parcheándolo, evidencia de que era real) cuando la spec dice que aquí
(a diferencia de CU11) Líder y Administrativo son solo-consulta — corregido en backend, router y
sidebar. 297 tests en total.

CU13 (Registrar semillero) + RF13 sí coinciden en número (a diferencia de CU06-12). **El CU más
grande trabajado hasta ahora**, dividido en 2 rondas (decisión de Jose). Ronda A ✅ desplegada
(acta `docs/validacion/CU13.md`): migración con 9 campos que faltaban por completo (`code`,
`group_id`, `cat_id`, `coordinator_id`, `mision`, `vision`, `justificacion`,
`objetivo_general` mín. 10 caracteres, `authorization_reference` RN03/RNF06 — todos nullable,
los 2 semilleros ya en producción no los tienen, se les exigirá al próximo editarlos); RN06
aplicado (el líder solo edita/togglea sus propios semilleros, 403 si no; Admin sin restricción,
Administrativo tampoco por decisión previa del proyecto); el líder que crea queda asignado
responsable automáticamente. De paso se corrigió el bug histórico **C-13** (`GET /seedbeds/{id}`
existía como ruta desde antes pero `show()` no, daba 500) y un `TypeError` real encontrado al
probar (`activeAreaRule()` con tipo de retorno incorrecto). **Ronda B pendiente**:
programa/área pasan de FK simple a selección múltiple — cambio de esquema más invasivo, toca
RF05/CU10 ya cerrado. 305 tests en total. Sigue pendiente E4 (mensaje genérico de fallo de
conexión, transversal CU07-CU13).

**Nota transversal (2026-09-29):** desde CU06, la red local de Docker (puertos 8080/8000) quedó
inalcanzable desde el host (contenedores sanos, problema de iptables/docker-proxy que necesita
`sudo` interactivo, fuera de mi alcance). Las rondas CU07-CU09 se validaron con backend vía
`docker exec` (sin red) + verificación en producción; falta el E2E completo en navegador local
hasta que Jose resuelva el acceso `sudo` en su máquina.

**Decisiones de CU02 (Jose, 2026-09-28):** con Google entran solo cuentas del dominio institucional
(`GOOGLE_ALLOWED_DOMAINS`, por defecto `ut.edu.co`, y claim `hd` igual). Única excepción: un
ADMIN_SISTEMA ya registrado puede usar cualquier cuenta de Google. **El login con contraseña sigue
abierto para todos los roles** (CU01 E5 no se aplica): el estudiante puede entrar directo con Google o
crear una contraseña desde «¿Olvidó su contraseña?» con su correo institucional.

**SIA (RF17 propuesto, 2026-09-28):** asistente con IA (Groq `gpt-oss-20b`) y botón de WhatsApp
solo para bugs (+57 3178773186), en todas las pantallas; panel `/admin/sia`. Diseño, límites y
operación en `docs/sia/README.md`. La memoria técnica que usa SIA está en
`api/resources/sia/base-conocimiento.md`: **actualízala cuando cambie una función del sistema**.
La key (`GROQ_API_KEY`) vive solo en `.env` o en el override local; nunca en git.

**RBAC granular (2026-09-29, fuera de la especificación oficial, pedido directo de Jose):**
permisos por módulo+acción, asignables a un rol completo, a un **grupo** de personas de distintos
roles, o a una persona como excepción (grant/revoke), sin tocar código. Documentado a fondo en
`docs/rbac/README.md`. Los 4 roles siguen siendo la base (`ADMIN_SISTEMA` con acceso total,
inamovible); no hay roles dinámicos (decisión explícita de Jose, se evalúa después). El `role:`
hardcodeado de las 38 rutas de `api/routes/api.php` sigue mandando — el `permission:` nuevo convive
en paralelo mientras se prueba y se migra ruta por ruta, no se reemplazó de una vez. Panel en
`/admin/rbac` (`modules/rbac/rbac.module.js` + `RbacController`) con 3 pestañas (Roles/Grupos/
Personas) y acordeón por módulo para usabilidad; 25 tests nuevos (271 en total) + 8/8 y 5/5 E2E
local.

**CU13 Ronda B (2026-09-29) a CU20 (2026-09-30), resumen** (acta completa por CU en
`docs/validacion/CUxx.md`): programa/área de semilleros pasaron a selección múltiple (pivots
`seedbed_program`/`seedbed_area`). CU14 modificar semillero, CU15 estado (inactivar exige motivo
y rechaza solicitudes pendientes), CU16 consultar semilleros web (Administrativo pasó a solo
consulta, precedente que se repite en CU19/CU20), CU17 semilleros por facultad PWA, CU18 detalle
de semillero PWA (+ botón "Ser miembro" = CU22, trabajados juntos) desplegados y validados. CU19
objetivos y CU20 resultados: mismo hallazgo real en ambos — RN06 (líder solo gestiona lo suyo) no
existía en absoluto en el controller, corregido con `guardLeaderOwnsSeedbed()`; Administrativo
alineado a solo consulta. CU20 tuvo además un bug real propio: la ruta de escritura le faltaba
`ADMIN_SISTEMA` (el admin no podía gestionar resultados) y el módulo frontend bloqueaba al admin
en vez de a Administrativo — corregido en ambas capas. 336 tests en total. RN06 ya se aplica en
Seedbed/Objective/Result; sigue faltando en el resto de entidades con dueño (revisar caso por
caso al llegar a cada CU).

**CU21 integrantes (2026-09-30) + CU22 (2026-09-29, junto con CU18) + CU23 (2026-09-30):** CU22
enviar solicitud ✅ (formulario embebido en el detalle de CU18: programa/teléfono/mensaje, E1 409
de postulación única, E4 semillero inactivo). CU23 mis solicitudes ✅ — hallazgo real: las
tarjetas no abrían detalle (flecha decorativa sin `click`), corregido con un sheet que muestra
mensaje, fecha, programa y la respuesta del líder (`requests.reason`); botón "Ver semilleros" en
el estado vacío. CU21 integrantes ✅ — **reconstruido completo por decisión de Jose**: lo que
existía era un pivot de Usuario+rol sin ninguno de los campos de la spec (código, programa, nivel
PR/PG, dirección/teléfono); tabla nueva `seedbed_members` con esos campos cifrados (mismo patrón
que Coordinator/CU12), RN06 aplicado (no existía), `ADMIN_SISTEMA` restaurado en escritura,
Administrativo solo consulta. Pendiente: A1 (precargar el formulario desde una solicitud
aprobada) — requiere su propia ronda.

**CU24 gestionar solicitudes recibidas (2026-09-30, trabajado por la segunda cuenta Claude en
paralelo, desplegado y validado en vivo por la cuenta principal):** acta `docs/validacion/CU24.md`,
368 tests en verde. Estaba casi todo por hacer: `index()` devolvía todas las solicitudes a
cualquier líder (RN06 ausente, igual que CU19/CU20), sin filtros ni `show()`; `updateStatus()` no
exigía motivo al rechazar, no validaba E2/E3 y **descartaba en silencio `reviewed_by`/
`reviewed_at`** (no estaban en `$fillable`); Administrativo podía resolver y `ADMIN_SISTEMA` no;
el módulo frontend era un CRUD genérico sin "Ver". Se rehízo por spec y se retiró
`PUT /requests/{id}` (editaba user/semillero/estado saltándose E1-E3). La respuesta del líder se
guarda en `requests.reason`, que CU23 ya mostraba. Aprobar ofrece registrar al estudiante como
integrante (CU21, ya reconstruido). ~~Hallazgo: `requests.phone` en claro~~ — **corregido en la
ronda de auditoría (2026-09-30)**, ver abajo.

**Ronda de auditoría de CU01–CU25 + CU26 (2026-09-30; desplegada en producción el 2026-10-03, diff 0 y 9/9 comprobaciones en vivo):** informe completo
en `docs/validacion/AUDITORIA_CU01-CU25_2026-09-30.md` (matriz por CU, hallazgos, decisiones),
acta `docs/validacion/CU26.md` y estado de CU27–CU30 en `docs/validacion/estado_CU27-CU30_2026-09-30.md`.
506 tests en verde (antes 434). Lo más importante: **un estudiante podía leer por API semilleros
inactivos, `authorization_reference`, teléfonos y correos** (el filtro «solo activos» estaba solo en
el navegador; corregido en el servidor, CU13/17/18); el perfil dejaba cambiar correo y nombre sin
contraseña (CU05, la spec solo permite teléfono y contraseña); `requests.phone` va ahora cifrado
(migración `2026_10_01_000001`, **cifra con `APP_KEY`: respaldar `requests` antes**); no existía
`api/lang` (mensajes `validation.*` crudos; **revisar `APP_LOCALE` en el `.env` de producción**);
el Administrador puede asignar/reasignar el líder de un semillero (CU14-A2); inactivar por `PUT`
ya no es posible (solo Activar/Inactivar con motivo); áreas duplicadas en propuestas dejaban una
propuesta sin áreas (500) y ahora hay transacción. Catálogos: Administrativo/Líder en solo consulta
también en la interfaz. CU16: filtros por facultad/CAT/área y «Mis semilleros» para el Líder
(extensión retrocompatible de `config.filters` en `core/crud.engine.js`: `options` como función,
`roles`, `allLabel`, `test`, `defaultValue`, `emptyFilterMessage`).
**CU26 «Mis propuestas»**: no existen RF26/RNF26 (le corresponden RF11, RN13, RNF02). **Puente
reversible de estados (decisión de Jose):** el enum sigue siendo `PENDIENTE/APROBADA/RECHAZADA` y la
API/PWA muestran Recibida/Viable/Archivada (`status_label`) más la observación del evaluador
(`proposals.review_note`, migración `2026_10_01_000002`); la migración real del vocabulario va con
CU27. **Abierto y justificado:** la auditoría no guarda valores anteriores/nuevos ni IP (raíz en CU29,
no tocado en esta ronda). **Pendiente de decisión:** enlaces «Solicitudes»/«Propuestas» en el menú
lateral de escritorio (hoy solo en la barra inferior móvil) y restringir `POST /requests` a
Estudiante. **No verificado:** navegador (el puerto 8080 sigue inaccesible), producción
(`migrate:status`, `.env`), Google real y las respuestas de SIA con la base nueva. **Graphify no se
refrescó** (regla: hitos de ~5 CU; el flujo `/graphify . --update` no estaba disponible).

**CU29 registrar auditoría (2026-10-03, desplegado en producción con diff 0):** acta `docs/validacion/CU29.md`, 523 tests en verde.
`audits` guarda ahora `old_values`/`new_values` (json) e `ip_address` (migración `2026_10_03_000001`), todo vía `App\Support\AuditTrail`:
sin contraseñas ni tokens, campos cifrados enmascarados como `[cifrado]`, `STATUS_CHANGE` como acción propia, pivotes y límites de SIA
auditados, `Audit` inmutable a nivel de modelo (`update`/`delete` lanzan), y E1 (falla → log + alerta al Administrador, sin revertir la
operación). **El LOGIN sigue siendo estricto** (sin auditoría no se emite token: decisión de CU01). Se guarda en UTC; CU30 mostrará la
hora de America/Bogota. **Comprobado en vivo:** el hosting entrega la IP real del cliente en `REMOTE_ADDR` (181.x desde casa), no hace falta `trustProxies`. La pantalla de
Auditoría aún no muestra los valores: es CU30.

**CU30 consultar auditoría (2026-10-03, en local, pendiente de desplegar):** acta `docs/validacion/CU30.md`, 543 tests en verde.
`GET /audits` paginado por el servidor (25, máx. 100) con filtros usuario/colección/acción/fechas (los días son de **America/Bogota**,
convertidos a UTC), `/audits/{id}` con la comparación campo a campo, `/audits/export` (CSV UTF-8 con BOM, máx. 20.000 filas,
**neutraliza inyección de fórmulas**), `/audits/options` y `/audits/summary` (el panel principal ya no descarga toda la tabla). E1 con
el texto exacto y E2 (403) probado en cada endpoint. Pantalla reescrita (`modules/audits/audits.module.js`); `apiDownload` nuevo en
`services/api.service.js`; `CACHE_NAME` v24. **Falta probar el frontend en navegador.** Pendiente: retención de la tabla `audits`.

**Trabajo en paralelo con 2 cuentas Claude (desde 2026-09-30):** ver `ONBOARDING.md` en la raíz —
es la guía de arranque para cualquier cuenta Claude que se sume (ahora mismo hay una segunda
cuenta conectada por SSH desde una VM Windows, mismo filesystem/repo/servidor de producción que
esta). Reglas de coordinación: `git pull` antes de cada ronda, no trabajar el mismo CU en
paralelo sin avisar, no desplegar a producción los dos a la vez.

Diferencias conocidas a cerrar (2026-09-29): RN02/RN03 (referencia de autorización escrita) sin
campo en algunos módulos; RN08 (códigos únicos) en CAT, áreas, grupos, facultades y programas
(falta semillero); RF15/CU28 reportes no existen. El avance de la validación 1 a 1 queda en
`docs/qa/` (no versionado mientras haya vulnerabilidades abiertas).

## Antes de trabajar aquí

1. Lee la especificación oficial (arriba) del CU/RF que toques, y además `docs/roles-usuarios.md` y
   `docs/CHANGELOG.md` — matriz de roles e historial de bugs de auditorías previas. Si
   `roles-usuarios.md` contradice la especificación, manda la especificación.
2. Revisa `graphify-out/GRAPH_REPORT.md` (God Nodes, comunidades, bugs documentados) o corre
   `graphify query "<pregunta>"` desde esta carpeta antes de explorar el código a ciegas — 1031
   nodos, arquitectura completa frontend+API mapeada (2026-09-03). Vista interactiva:
   `graphify-out/graph.html`.
3. Si el código cambió desde el mapeo, refresca con `graphify . --update` antes de confiar en el
   grafo.

## Despliegue

Ver `README.md` para el flujo de deploy vía scp (patrón: lint PHP en servidor con `php -l` antes de
subir, `php artisan route:clear && config:clear` después de tocar backend). Credenciales reales
nunca se documentan en este repo — es público en GitHub (`Jose-Bohorquez/ut_semilleros`). Ver
sección "🔑 Credenciales y configuración sensible" del `README.md`.

> Nota (2026-09-26): el `README.md` todavía no tiene una sección de deploy propiamente dicha, y su
> sección "Despliegue en Hostinger" (`/api/public` como raíz del dominio) no refleja cómo está
> desplegado hoy. Lo real es lo que sigue.

### Cómo está desplegado de verdad (verificado 2026-09-26)

- Servidor: alias SSH `htg` (Hostinger compartido). Ruta: `~/domains/ut-edu.online/public_html/`.
  El frontend está en la raíz y la API en `api/`. `SGAA/` vive en la misma carpeta pero es otro
  proyecto: nunca lo incluyas en un rsync/scp de Semilleros.
- Enrutamiento (`.htaccess` raíz): `/api/*` va a `api/public/index.php`; los archivos reales se
  sirven directo; todo lo demás va a `index.html` (router SPA). Si cambias el orden de las reglas,
  el SPA se traga las llamadas a la API.
- **PHP en producción: 8.2.33** (CLI). Docker local usa PHP 8.4, y el README también dice 8.4.
  Nada de sintaxis ni funciones exclusivas de 8.3 o 8.4 (`composer.json` exige `^8.2`).
- Hostinger **no tiene Node ni npm**. **Composer sí está** (`/usr/local/bin/composer`, 2.9), pero
  el patrón usado hasta ahora es subir `api/vendor/` ya instalado.
- `api/.env` existe solo en el servidor (`chmod 600`). Nunca se sobrescribe desde un rsync: excluye
  siempre `api/.env`, `api/storage` y `SGAA`.
- Caché: el CDN de Hostinger cacheaba `.js`/`.css` por 7 días. El `.htaccess` lo baja a 5 minutos.
  Si cambias un asset cacheado por el service worker, sube `CACHE_NAME` en `service-worker.js`
  (hoy `semilleros-v16`).
- Migraciones: todas aplicadas en producción (`php artisan migrate:status`). **Nunca `--seed` en
  producción**: los seeders crean usuarios demo con contraseña conocida.
- Producción tiene 4 usuarios, uno por rol, y muy pocos datos. Cualquier prueba en vivo usa datos
  desechables `qa_temp_*` y se borra al terminar.

## Prohibiciones (cada una viene de un incidente real de este proyecto)

- **No usar `git add .` ni `git add -A` en esta carpeta.** `SGAA/` (que tiene su propio repo),
  `todo_ut-edu.space_old/`, `api.zip`, `frontend.zip` y `db/` (99 MB de datos MySQL locales)
  no están ignorados. El `.gitignore` raíz pone comentarios al final de la línea
  (`/db/data/   # ...`), y git no los reconoce, así que esa regla no aplica. Agrega archivos por
  ruta explícita. El repo es público.
- **No interpolar hashes bcrypt ni secretos en un comando `ssh "..."`.** El shell remoto expande
  `$2y$10$...` y corrompe el hash sin avisar (ya pasó en SGAA). Escribe un archivo local y súbelo
  con scp.
- **No mezclar rutas de subcarpetas distintas en un solo `scp`.** Aplana la estructura (ya pasó en
  Nido Pastel con `app.css`). Usa un scp por carpeta destino o `rsync -R`.
- **No filtrar campos sensibles solo en un módulo.** Filtra en `core/crud.engine.js`: el motor
  reutilizaba `fields` para formulario y tabla, y eso mostraba una columna "Contraseña".
- **No asumir que una migración corre en MySQL porque corre en SQLite.** El índice único sobre
  TEXT de `push_subscriptions` fallaba en MySQL (error 1170). Los tests usan SQLite `:memory:`.
- **No dejar una ruta del frontend sin su `requireRole()`** si el rol no ve la opción en el menú.
  Frontend (`core/router.js`, `noCreateFor`/`noEditFor` del CRUD) y backend (`role:` en
  `api/routes/api.php`) deben coincidir con `docs/roles-usuarios.md`. Ya hubo 403 en botones
  visibles y pantallas abiertas por URL.
- **No reportar un bug visual solo porque se ve raro en una captura.** Confírmalo con
  `getComputedStyle` o leyendo el código. El header naranja en móvil y el padding del bottom-nav
  ya se reportaron como bugs sin serlo.

## Pruebas

Existen 21 archivos de test en `api/tests/Feature` (CRUD por recurso, Auth, SeedbedMember), con
`phpunit.xml` sobre SQLite en memoria. **No hay evidencia de que se hayan corrido recientemente.**
Local no tiene PHP instalado, así que se corren dentro del contenedor
(`docker exec ut_semilleros_api php artisan test`). Desde el 2026-09-28, `phpunit.xml` fuerza
SQLite con `<env>` **y** `<server>` `force="true"`. Laravel lee `$_SERVER` primero, así que con
`<env>` solo no alcanzaba (verificado con una sonda). Los `-e` ya no son obligatorios, pero no
hacen daño.
**Nunca sin los `-e`**: el override de Docker inyecta `DB_CONNECTION=mysql` y `phpunit.xml` no usa
`force="true"`, así que la suite correría sobre la BD de dev y `RefreshDatabase` la borraría
(hallazgo C-01, 2026-09-27). Con los `-e`: 121 tests en verde y la BD intacta. El frontend no tiene tests automatizados. Los
flujos se validan con Puppeteer.

## Diseño

Para cualquier trabajo de diseño nuevo (vistas, dashboard, PWA, componentes), usa el skill
`ui-ux-pro-max` o `impeccable`. Respeta la marca actual: el gradiente `--color-gradient`
(`#ef4444→#f97316`) y el header naranja en móvil son **deliberados**
(`css/pwa.css`: "Convierte el navbar existente al estilo PWA"), no una inconsistencia. Los tokens
viven en `css/theme.css`, con variante clara y oscura. Úsalos antes de inventar colores nuevos.

## Grafo de conocimiento (graphify)

`graphify-out/` mapea frontend + API. `SGAA/`, `todo_ut-edu.space_old/`, `db/` y los `.zip` quedan
fuera vía `.graphifyignore`. Último refresco: commit `0a636ce` (CU13B–CU25, 89 archivos), con 2281
nodos y 4408 aristas (comprobado el 2026-09-30); la ronda de auditoría posterior no lo refrescó. God nodes: `User`, `apiFetch()`, `Controller`, `Seedbed`, `createCrudModule()`,
`initLayoutController()`.

- `graphify query "<pregunta>"`: antes de explorar a ciegas (¿dónde se valida X?, ¿qué usa Y?).
- `graphify path "A" "B"`: para ver cómo un cambio en A afecta a B (p.ej. `crud.engine.js` →
  un módulo concreto).
- `graphify explain "X"`: para entender un nodo y sus vecinos antes de modificarlo.
- **Después de cada cambio, actualiza el grafo con el flujo incremental del skill**
  (`/graphify . --update`): reextrae con AST el código cambiado y, con un subagente, los
  documentos pequeños cambiados, y fusiona con `build_merge`.
  **No uses `graphify update .` a secas si cambiaron documentos:** ese modo solo-código
  reconstruye el grafo y descarta los nodos semánticos de los docs cambiados (2026-09-28: se
  perdieron ~180 nodos de la especificación; se restauró desde git).
- La especificación y la documentación técnica pesan ~260 KB. Reextraerlas cuesta ~200k tokens,
  así que se reextraen en hitos (cada ~5 CU validados). Entre hitos conservan sus nodos y quedan
  sin marcar en el manifest.

## Agentes disponibles (`.claude/agents/`)

- `bug-historian`: antes de diagnosticar un bug, revisa si ya pasó (CHANGELOG, CLAUDE.md, git log).
- `architecture-advisor`: decisiones de arquitectura, siempre con 2-3 opciones y sus trade-offs.
- `code-reviewer`: antes de commitear o desplegar, con los gotchas de este proyecto.
- `test-engineer`: QA end-to-end de flujos y reglas de negocio por rol (no de diseño).
- `db-architect`: valida el esquema real (migraciones + BD viva), normalización, ER en Mermaid y
  diccionario de datos. Es el único que escribe, y solo en `docs/`.
- `deploy-engineer`: despliegues Docker → Hostinger y validación en vivo.
- `qa-design-web`: QA visual en navegador real (Puppeteer) en desktop.
- `qa-design-mobile`: QA en viewports móviles, más instalabilidad y offline de la PWA.
- `use-case-auditor`: cruza las reglas de negocio documentadas contra el código real.
- `architecture-reviewer`: revisión arquitectónica integral sin pregunta previa. Entrega
  observaciones priorizadas y concilia los hallazgos del resto del equipo.
- `ui-designer`: propone mejoras visuales (web, responsive y PWA) con `impeccable` +
  `ui-ux-pro-max`. No implementa sin confirmación.
- `frontend-tester`: pruebas funcionales de la UI por caso de uso y rol (Puppeteer).
- `backend-tester`: API por endpoint y rol, PHPUnit, e integración de lo desplegado en
  Hostinger.
- `pwa-tester`: instalabilidad, service worker, caché tras deploy, offline, push y casos de uso del
  estudiante en la PWA.

### Trabajo en equipo (validación de casos de uso)

La especificación de CU01–CU14 no está en un documento aparte. Está en los comentarios
`(RFxx / CUxx)` de `api/routes/api.php`, en `docs/roles-usuarios.md` y en `docs/CHANGELOG.md`.
El flujo es:

1. `use-case-auditor` arma la especificación y el veredicto estático.
2. `backend-tester`, `frontend-tester` y `pwa-tester` prueban en paralelo, cada uno con su prefijo
   de datos (`qa_temp_be_`, `qa_temp_fe_` y `qa_temp_pwa_`).
3. `architecture-reviewer` concilia los resultados, busca la causa raíz y da feedback a cada
   agente. `ui-designer` evalúa la UX de cada caso de uso.
4. Cada hallazgo crítico o alto pasa por un verificador adversarial.

El informe queda en `docs/qa/`.

### Entorno de pruebas local (Docker, aislado de producción)

`api/.env` local contiene credenciales de producción: **no se edita**. Las variables de desarrollo
(BD de Docker, `MAIL_MAILER=log`, `CACHE_STORE=database`) están en `docker-compose.yml` desde el
2026-09-28, y mandan sobre el `.env` porque Laravel usa Dotenv inmutable. El
`docker-compose.override.yml` local quedó redundante. Instalación desde cero:
`./scripts/dev-setup.sh`. Gotcha: con `CACHE_STORE=file` el rate limit de login no cuenta,
porque `storage/` no es escribible en el contenedor. **Nunca cachees la config** (`config:cache`) en local, porque eso rompería este
aislamiento. `.dockerignore` excluye `db/` (si no, el build falla por permisos). Para levantarlo:
`docker compose up -d --build && docker exec ut_semilleros_api php artisan migrate --force`.

Ningún agente ejecuta DDL/DML ni despliega por su cuenta. Todo lo que recomienden se apoya en este
archivo, en `docs/` y en el grafo, y cualquier cambio se cierra con una validación real y evidencia
de qué se probó.
