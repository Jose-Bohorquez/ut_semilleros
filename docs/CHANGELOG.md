# Sesión 2026-10-03 — CU28 reportes y estadísticas

Nueva pantalla «Reportes» (Administrador, Administrativo y Líder con alcance propio, A1): semilleros activos por facultad, solicitudes por semillero y estado, 10 áreas con más propuestas e integrantes activos por programa y nivel; filtros por fechas (hora de Bogotá) y CAT; «Exportar CSV» por reporte (BOM, fila de total, anti-inyección de fórmulas con `App\Support\Csv`, compartido con CU30); E1 «Sin datos para el periodo»; E2 corte a 10 s con 503. Acta `docs/validacion/CU28.md`, 22 tests propios, suite en 600. SIA actualizada. `CACHE_NAME` v26. Falta la prueba visual en navegador.

---

# Sesión 2026-10-03 — CU27 evaluar propuestas

Solo el Administrativo evalúa (Marcar viable / Archivar, con observación obligatoria al archivar); el Líder consulta las propuestas de las áreas de sus semilleros; filtros por área, programa, estado y fechas; contacto del estudiante solo en el detalle del Administrativo; `POST`/`PUT /proposals` solo para el Estudiante; menú lateral con Solicitudes y Propuestas. 578 tests. Ver `docs/validacion/CU27.md`.

---

# Sesión 2026-10-03 — CU30 consultar auditoría

Pantalla de Auditoría con filtros (usuario, colección, acción, fechas en hora de Bogotá), paginación de servidor, «Ver» con la comparación de valores anteriores y nuevos, exportación a CSV (con protección contra inyección de fórmulas) y E1/E2. El panel principal usa `/audits/summary`. Sin migraciones. 543 tests. Ver `docs/validacion/CU30.md`.

---

# Sesión 2026-10-03 — CU29 registrar auditoría

La auditoría guarda valores anteriores y nuevos, IP y `STATUS_CHANGE`; registra pivotes y límites de SIA; es inmutable a nivel de modelo; ante una falla escribe en el log y alerta al Administrador sin revertir la operación (el LOGIN sigue estricto). Migración `2026_10_03_000001`. 523 tests. Ver `docs/validacion/CU29.md`.

---

# Sesión 2026-09-30 — Auditoría integral de CU01–CU25 y cierre de CU26

Informe completo: `docs/validacion/AUDITORIA_CU01-CU25_2026-09-30.md`. 506 tests en verde (antes 434).

- **CU13/CU17/CU18/CU19 (CRÍTICO):** la API entregaba a un estudiante semilleros y objetivos inactivos,
  la referencia de autorización, el motivo de inactivación, teléfonos y correos. El filtro «solo
  activos» estaba únicamente en el navegador. Corregido en el servidor.
- **CU05:** `PUT /profile` permitía cambiar correo y nombre sin contraseña. La spec solo permite
  teléfono y contraseña. Corregido; foto de perfil validada (sin SVG, límite real de 2 MB) y límite de
  intentos de contraseña actual.
- **i18n:** no existía `api/lang`; las validaciones sin mensaje propio salían como `validation.*`.
  Agregado `lang/es` y locale por defecto `es` (**revisar `APP_LOCALE` en producción**).
- **CU22:** `requests.phone` se guardaba en claro. Cifrado con `APP_KEY` (migración con datos
  existentes, idempotente). Las solicitudes nacen siempre `PENDIENTE`.
- **CU14/CU15:** el Administrador puede asignar/reasignar el líder; `PUT /seedbeds/{id}` ya no cambia
  el estado (solo Activar/Inactivar con motivo); el rechazo automático de solicitudes queda auditado.
- **CU16:** filtros por facultad, CAT y área, «Mis semilleros» para el Líder, sección Resultados en «Ver»;
  `members_count` e Integrantes leen los integrantes reales (regresión de CU21).
- **CU18/CU19/CU23:** aviso «Este semillero ya no está disponible»; un objetivo inválido ya no se
  descarta en silencio; «Solicitudes» y «Propuestas» funcionan sin conexión con el último dato.
- **CU25:** áreas duplicadas dejaban una propuesta sin áreas (500); ahora hay validación y transacción.
- **CU06/CU04/CU01:** el cambio de rol no puede dejar el sistema sin administrador; usuarios inactivos
  no restablecen contraseña; ruta muerta `POST /register` eliminada.
- **CU07–CU10:** Administrativo y Líder ven los catálogos en solo consulta también en la interfaz.
- **CU26 «Mis propuestas»:** nuevo. Etiquetas Recibida/Viable/Archivada y observación del evaluador
  (`proposals.review_note`), detalle al tocar, mensaje de lista vacía y modo sin conexión.
- **Revisión de seguridad independiente:** `GET /seedbeds` ya no devuelve correo/teléfono de los usuarios ni el CAT completo al estudiante; las copias offline de solicitudes/propuestas se borran al cerrar sesión; la migración del teléfono aborta ante un valor cifrado con otra `APP_KEY`.
- **Despliegue (2026-10-03):** en producción con diff 0; migraciones `2026_10_01_000001` (teléfonos de solicitudes cifrados) y `000002` aplicadas; 9/9 comprobaciones en vivo.
- **SIA:** memoria técnica ampliada a 113 secciones y test de recuperación (57+ preguntas por rol).
- **Abierto (documentado):** la auditoría no guarda valores anteriores/nuevos (CU29); CU27–CU30 sin
  implementar (`docs/validacion/estado_CU27-CU30_2026-09-30.md`).

---

# Sesión 2026-09-28 — Validación CU01 (login web), RF01 y RNF01

Primera ronda de la validación 1 a 1 contra `docs/especificacion/` (fuente de verdad funcional).
Detalle, evidencia y estado en `docs/validacion/CU01.md`.

- **CU01 E2:** con una contraseña errada la página se recargaba (el 401 se trataba como sesión
  expirada) y el mensaje nunca se veía. Corregido.
- **CU01 E3:** un usuario inactivo veía «Credenciales incorrectas». Ahora ve el mensaje de la
  especificación.
- **CU01 E4 / RN14:** no había límite de intentos. Ahora hay bloqueo tras 5 fallos por minuto
  (HTTP 429).
- **CU01 A2 + RNF03:** los tokens no vencían nunca. Ahora vencen a las 8 h, o a los 30 días con
  «Recordarme».
- **CU01 A3:** después del login se vuelve a la ruta que se intentaba abrir.
- **CU01 paso 6 / CU29:** los inicios de sesión no quedaban en auditoría. Ahora sí.
- **UI del login:** formulario en la primera pantalla del móvil, errores accesibles en AA, foco
  visible y tokens de color que faltaban.
- **RNF01:** `api/.env.example` nunca estuvo en el repo (el `.gitignore` tenía un comentario al
  final de la línea). La instalación ahora es un solo comando (`scripts/dev-setup.sh`), verificado
  en un clon limpio.

---

# Changelog — Sesión de validación 2026-07-28

Auditoría completa del sistema de Semilleros IDEAD (PWA + API Laravel) contra los casos
de uso reales. Todos los bugs listados fueron encontrados, corregidos y verificados
extremo a extremo contra producción (`https://ut-edu.online/`).

## PWA / instalación

- **Faltaba `viewport-fit=cover`** en el meta viewport — sin esto, todo el CSS que ya
  usaba `env(safe-area-inset-top)` no tenía efecto, dejando un espacio en blanco arriba
  al abrir la app instalada. Corregido.
- Agregado `display_override: ["fullscreen", "standalone"]` al manifest para preferir
  pantalla completa donde el sistema operativo lo soporte.
- **Header del login roto en móvil** (~390px): el título se encimaba con el logo, los
  links "Sitio Web"/"Manuales" se atropellaban. `.login-header-inner` no tenía ningún
  tratamiento responsive. Corregido con `flex-wrap` + ocultar esos links en pantallas
  muy pequeñas.
- **El CDN de Hostinger cacheaba `.js`/`.css` por 7 días**, anulando la estrategia
  "network-first" que ya tenía el service worker — cada despliegue quedaba invisible
  hasta que expirara la caché. Bajado a 5 minutos vía `.htaccess`.

## Notificaciones push

- **El modelo `PushSubscription` nunca existió como archivo** — la tabla y el
  controlador sí, pero cualquier operación de push fallaba con
  `Class "App\Models\PushSubscription" not found`. Creado.
- **Llaves VAPID nunca configuradas** en el servidor de producción — sin ellas ningún
  push podía enviarse. Generado un par nuevo y configurado.
- **Dos implementaciones de suscripción compitiendo entre sí**: una con una VAPID key
  placeholder sin completar apuntando a una ruta inexistente (`/push/subscribe`), y otra
  correcta apuntando también a una ruta inexistente. Unificadas en una sola
  implementación (`services/push.service.js`) apuntando a la ruta real
  (`/push-subscriptions`, con el payload exacto que espera el backend).

## Dashboard

- Las tarjetas de gráficos ("Semilleros por estado", "Propuestas por estado",
  "Semilleros por facultad") quedaban completamente en blanco cuando no había datos
  (sistema recién desplegado). Se agregó un estado vacío explicativo.

## Semilleros

- **El admin (`ADMIN_SISTEMA`) no podía crear ni editar semilleros** — bloqueado tanto
  en el frontend (`noCreateFor`) como en el backend (middleware de rol). Corregido.
- **Líder de Semillero no podía siquiera abrir la pantalla de gestión de semilleros**
  (`/admin/seedbeds`) — el router del frontend no lo incluía en los roles permitidos.
  Además, el guard usaba `alert()` nativo (bloqueante), lo que hacía que el intento de
  acceso se sintiera como que la pantalla se congelaba. Corregido: se agregó el rol y se
  reemplazó el `alert()` por un toast no bloqueante.
- **Administrativo veía el botón de crear/editar semilleros pero el backend se lo
  rechazaba con 403** (el mismo patrón de bug que en propuestas). Alineado.
- Se agregó el campo **Descripción** (antes no existía en la base de datos ni en el
  formulario ni se mostraba en la PWA a los estudiantes).
- Se agregó **Objetivos** directamente en el mismo formulario de creación/edición del
  semillero (no una vista aparte) — se pueden agregar, **reordenar** (↑↓), **editar** y
  **eliminar de verdad** (antes no existía ni el endpoint `DELETE /objectives/{id}` ni
  una columna de orden).
- El módulo de Objetivos (pantalla propia, además de la sección integrada) tenía el
  mismo bug de admin bloqueado — corregido en frontend y backend.

## Propuestas (estudiante)

- **Bug crítico real**: la ruta `PUT /proposals/{id}` excluía por completo al rol
  `ESTUDIANTE` en el backend. El botón "Editar" se veía en la PWA y el modal abría con
  los datos correctos, pero guardar siempre fallaba con 403 — el estudiante nunca podía
  realmente editar su propuesta. Corregido, con la validación correspondiente de que
  solo pueda editar **su propia** propuesta y **solo mientras esté Pendiente**.

## Solicitudes (postulación a semillero)

- **No existía ninguna validación de unicidad**: un estudiante podía postularse a
  varios semilleros a la vez, o volver a postularse después de ser aceptado. Corregido:
  el backend rechaza (422) una nueva postulación si ya existe una Pendiente o Aprobada;
  el frontend oculta el botón de nueva solicitud en ese caso con un mensaje explicativo.

## Deuda técnica resuelta en esta ronda

- **Principio aplicado a todo el router**: si un rol no tiene la opción visible en el
  menú, tampoco debe poder entrar por URL directa y encontrarse una pantalla rota. Se
  agregó `requireRole()` explícito en `core/router.js` a `/coordinators`, `/cats`,
  `/areas`, `/groups`, `/audits` (antes solo exigían sesión iniciada, sin verificar rol —
  el backend ya protegía los datos, pero la experiencia era confusa), y también a
  `/objectives`, `/results`, `/products`.

---

# Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas

## Contexto

Llegaron 9 correos institucionales reales (`@ut.edu.co`, estudiantes del **CAT Kennedy,
Bogotá**) que debían darse de alta como `ESTUDIANTE`. Al intentar resolver esto se
encontró que **la infraestructura de activación de cuenta nunca existió en el frontend**,
aunque el backend ya tenía todo el mecanismo de Laravel (`Password::createToken()`,
`password_reset_tokens`) cableado desde antes.

## Bugs reales encontrados

- **El link "¿Olvidaste tu contraseña?" del login apuntaba a `href="#"`** — no existía
  ninguna página para recuperar/activar contraseña. 404 silencioso de facto. Creadas
  `/forgot-password` y `/reset-password` (esta última sirve dos propósitos: recuperación
  normal y activación de cuenta nueva, diferenciados por `?activation=1` en la URL).
- **`MAIL_MAILER=log` en producción**: ningún correo real se había enviado jamás desde
  esta aplicación, solo se escribía en el log. Cambiado a SMTP real.
- **Faltaba `FRONTEND_URL` en `.env` de producción**: sin esto, cualquier link generado
  por una notificación (`AccountActivationNotification`, reset de contraseña) apuntaba a
  `http://localhost:5173` — roto en producción. Configurado.
- **`StoreUserRequest` exigía contraseña siempre** (`required|min:6`) — no permitía crear
  un usuario sin contraseña para disparar el flujo de activación. Cambiado a
  `nullable|min:6`.

## Funcionalidad nueva: importación masiva de usuarios

- Botón **"Importar usuarios"** en `/admin/users` (solo `ADMIN_SISTEMA`), vía nuevos
  hooks genéricos en `core/crud.engine.js` (`toolbarExtraHtml()`, `afterTableMount()` —
  reutilizables por cualquier módulo CRUD, no solo usuarios).
- El admin pega una lista (una línea por usuario: `nombre,correo,rol` o solo el correo) o
  sube un `.csv`/`.txt`. Se genera una tabla editable con un **nombre sugerido** derivado
  del usuario del correo (ej. `jaaldanah@...` → `Jaaldanah`) — **presentado siempre como
  sugerencia a revisar, nunca como un nombre real confirmado**, porque no existe ninguna
  fuente confiable para deducir el nombre real a partir del correo institucional.
- Al enviar, cada usuario se crea **sin contraseña** → dispara automáticamente
  `AccountActivationNotification` (correo personalizado con el primer nombre, distinto
  del texto de "recuperar contraseña" normal) con un link de activación válido por 60
  minutos.
- Nuevo endpoint `POST /api/users/import` (rol `ADMIN_SISTEMA`), hasta 500 filas por
  request, valida cada fila individualmente y reporta éxito/error por fila sin abortar
  el lote completo si una fila falla.

## Verificación end-to-end (2026-08-31, contra producción real)

Probado completo con Puppeteer contra `https://ut-edu.online/` y limpiado después:
1. Login admin → `/admin/users` → botón "Importar usuarios" visible y funcional.
2. Pegar una fila → tabla de vista previa se genera correctamente.
3. Enviar → usuario creado en BD (`role=ESTUDIANTE`, `status=ACTIVO`), sin contraseña,
   con token en `password_reset_tokens`.
4. Envío de `AccountActivationNotification` vía SMTP real confirmado sin excepción
   (cuenta `activacion@ut-edu.online`, ya existente en hPanel).
5. Link `/reset-password?token=...&email=...&activation=1` renderiza el estado
   "Activa tu cuenta" (no el estado de "enlace inválido").
6. Definir contraseña desde esa pantalla → `POST /reset-password` responde OK.
7. Login con la contraseña recién definida → redirige a `/dashboard` correctamente.

## Pendiente (no bloqueante)

- **Los 9 usuarios reales del CAT Kennedy aún no se cargaron** — se decidió esperar a que
  Jose confirme si quiere usar el nombre sugerido (heurístico, a revisar fila por fila) o
  prefiere dar los nombres reales antes de la carga. No se encontró ninguna fuente pública
  confiable para deducir esos nombres (se buscó en censos electorales históricos
  publicados por la universidad, sin coincidencias válidas).
- Confirmar recepción real de un correo de activación en una bandeja de entrada real (la
  verificación de esta sesión confirmó que el envío SMTP no lanza excepción, pero no hubo
  una casilla real disponible para confirmar visualmente la recepción).
- Versionamiento de propuestas (historial de cambios) — mencionado por el usuario como
  posible mejora futura, no confirmado como requerimiento firme.
- Confirmar recepción visible/audible de una notificación push en un dispositivo físico
  real (el envío contra FCM se verificó exitoso del lado del servidor, falta la
  confirmación del lado del dispositivo).
