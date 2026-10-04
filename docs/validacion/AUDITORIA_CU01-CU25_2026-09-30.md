# Auditoría integral de CU01–CU25 y cierre de CU26

| Campo | Valor |
|---|---|
| Fecha | 2026-09-30 |
| Alcance | Auditar CU01–CU25 contra la especificación, corregir defectos CRÍTICOS/ALTOS, cerrar CU26, documentar CU27–CU30 (sin implementarlos) |
| Fuente normativa | `docs/especificacion/Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md` (manda lo funcional, decisión de Jose 2026-09-28; el stack real manda sobre lo que la doc técnica diga del stack) |
| Método | 6 auditores de solo lectura (uno por bloque de 5 CU + uno para CU26–CU30), con sondas de ejecución en SQLite en memoria; 4 correctores con archivos disjuntos; revisión del diff y suite completa por el coordinador |
| Estado | **Desplegado en producción el 2026-10-03** (diff 0, 9/9 comprobaciones en vivo). Falta la validación visual en navegador. |

## 1. Resumen ejecutivo

- **25 de 25 casos de uso auditados individualmente.** Ninguno queda `FAIL`. **Se confirma lo declarado en lo esencial** (los
  CU estaban implementados), pero se hallaron **1 CRÍTICO, 13 ALTOS (causas raíz únicas: los auditores los contaron por CU) y decenas de MEDIO/BAJO**.
- **Corregidos: el CRÍTICO y 12 de los 13 ALTOS**, cada uno con test de regresión (que falla antes y pasa después).
- El más grave: **un estudiante podía leer por API semilleros inactivos, la referencia de autorización, el motivo de
  inactivación, teléfonos y correos** (el filtro «solo activos» existía únicamente en el navegador). Además, el perfil permitía
  cambiar correo y nombre sin contraseña (toma de cuenta con un token robado) y `requests.phone` era el único teléfono de
  estudiante sin cifrar.
- **Queda abierto 1 ALTO transversal, con justificación** (§5): la auditoría no guarda valores anteriores/nuevos (raíz en CU29,
  que esta ronda prohíbe tocar).
- **CU26 implementado** (14 tests) con el *puente reversible* de estados que decidió Jose. **No existen RF26 ni RNF26**: CU26
  corresponde a RF11, RN13 y RNF02 (tabla §1.4 de la spec).
- **CU27–CU30 documentados, no implementados** (`docs/validacion/estado_CU27-CU30_2026-09-30.md`).
- **Tests: 434 → 506 en verde**, 0 fallos. Sin regresiones conocidas.
- **Revisión de seguridad independiente del diff** (agente aparte, solo lectura): sin bloqueantes; encontró 7 puntos (3 IMPORTANTES, 4 MENORES), 6 corregidos y 1 aceptado (bloqueo de 60 s de `current_password` con un token robado, DoS menor).
- **No se verificó**: comportamiento en navegador (puerto local 8080 inaccesible), producción (lectura bloqueada), Google real,
  respuestas del modelo de SIA con la base nueva, y **Graphify no se actualizó** (ver §7).

## 2. Criterio de los estados

| Estado | Significado en este informe |
|---|---|
| PASS | Cumple la spec; solo observaciones BAJO. |
| PASS WITH FIXES | Aprobado tras correcciones de esta ronda y/o con observaciones MEDIO/BAJO abiertas; **sin CRÍTICO/ALTO abiertos** salvo el transversal T-AUD (§5). |
| PARTIAL | Hay un flujo, alterno, excepción o regla de la spec sin cumplir (funcional). |
| NOT VERIFIED | No se pudo comprobar (dónde y por qué en la columna Observaciones). |

**T-AUD** = «la auditoría no guarda valores anteriores/nuevos» (alterno A2 de CU07–CU14, RN07/CU29). Es una sola causa raíz
contada una vez; no se usa para degradar el estado de cada CU (los auditores la puntuaron distinto: ALTO en CU07–CU10, MEDIO en
CU11–CU14).

## 3. Matriz CU01–CU25

«Estado anterior» = lo declarado (CU01–CU23: implementados, sin auditar; CU24 y CU25: desplegados y validados en vivo).
Los conteos de tests son de los filtros de §7 y **se solapan** entre bloques (p. ej. `Seedbed` agrupa CU13–CU18).

| CU | Nombre | Estado anterior | Estado validado | Evidencia | Defectos (ID · sev.) | Tests | Observaciones |
|---|---|---|---|---|---|---|---|
| CU01 | Iniciar sesión (web) | Declarado cerrado | **PASS WITH FIXES** | Flujo, E2–E4, RN14, tokens 8 h/30 d probados; sondas | CU01-H1 MEDIO (ruta `POST /register` muerta → 500) **corregido**; H2–H4 BAJO abiertos | `CU01LoginTest` + Auth (138 con CU02–05) | E5 no aplica (desviación documentada). E2E navegador NOT VERIFIED |
| CU02 | Iniciar sesión con Google (PWA) | Declarado cerrado | **PASS WITH FIXES** | Verificación de token, dominio, `hd`, excepción ADMIN | CU02-H1 MEDIO (RN04 evadible vía perfil) **corregido** (raíz CU05-H1); H2–H5 BAJO abiertos | `CU02GoogleLoginTest` | **Flujo real con Google NOT VERIFIED** (pendiente desde CU02) |
| CU03 | Cerrar sesión | Declarado cerrado | **PASS** | Revocación y auditoría LOGOUT | H1–H3 BAJO (mejoras) | `CU03LogoutTest` | — |
| CU04 | Recuperar contraseña | Declarado cerrado | **PASS WITH FIXES** | RN10, token 60 min, 3/10 min, cierre de sesiones | CU04-H1 MEDIO (inactivo podía restablecer) **corregido**; H2–H6 BAJO abiertos | `CU04ForgotPasswordTest` + 2 nuevos | Cola/cron de correo en producción NOT VERIFIED |
| CU05 | Consultar y actualizar perfil | Declarado cerrado | **PASS WITH FIXES** | La spec solo permite teléfono y contraseña | CU05-H1 **ALTO** (cambio de correo/nombre sin reverificar) **corregido**; H2 foto (SVG, MIME, límite) y H3 fuerza bruta de `current_password` MEDIO **corregidos**; H4–H7 BAJO | `CU05ProfileTest` (1 ajustado, ver §4) + 8 nuevos | Ahora nombre/correo son de solo lectura |
| CU06 | Gestionar usuarios | Declarado cerrado | **PASS WITH FIXES** | RN02, RN10, E3, E4, A1 | CU06-H1 **ALTO** (sin `lang/`: claves `validation.*` crudas) **corregido**; H2 (cambio de rol deja sin admin) y H3 (`resendActivation` 500) MEDIO **corregidos**; H4 (`GET /users` con datos de todos) **mitigado**; H5–H8 BAJO | `User*` + `AuditFixesAuthUsersTest` (99) | Carga masiva de estudiantes = desviación documentada. **Idioma real en producción NOT VERIFIED** (`APP_LOCALE`) |
| CU07 | Gestionar facultades | Declarado cerrado | **PASS WITH FIXES** | Escrituras solo ADMIN, sin borrado | CU07-H2 **ALTO** (A5: Administrativo veía botones de escritura) **corregido**; H1 = T-AUD; H3 (texto del 403) MEDIO; H4–H5 BAJO | `FacultyCrudTest`/`RF02` (26) | — |
| CU08 | Gestionar programas | Declarado cerrado | **PASS WITH FIXES** | Idem | A5 **corregido**; T-AUD; MEDIO/BAJO análogos | `RF03` (33) | — |
| CU09 | Gestionar CAT | Declarado cerrado | **PASS WITH FIXES** | Idem; `phone1` «al menos uno de 3» | A5 **corregido**; T-AUD | `RF04` (70) | Desviación documentada de Jose |
| CU10 | Gestionar áreas | Declarado cerrado | **PASS WITH FIXES** | Idem | A5 **corregido**; T-AUD | `RF05` (29) | Seeder de áreas ≠ catálogo OCDE de producción (BAJO); hueco de ID 2 sin verificar |
| CU11 | Gestionar grupos | Declarado cerrado | **PASS WITH FIXES** | RN08, Líder escribe, Administrativo consulta | H1 = T-AUD; H2–H4 BAJO | `GroupCrudTest` (19) | — |
| CU12 | Gestionar coordinadores | Declarado cerrado | **PASS WITH FIXES** | Teléfono cifrado, solo ADMIN escribe | CU12-H1 MEDIO (documento opcional, parcialmente documentado) abierto; H2–H4 BAJO | `Coordinator*` (18) | — |
| CU13 | Registrar semillero | Declarado cerrado | **PARTIAL** | Ronda A+B, RN03, RN06, borrador | CU13-H1 **CRÍTICO** (API abierta al estudiante) **corregido**; H7 (sin transacción) **corregido**; H3 (paso 10 no abre la edición), H4 (texto E3), H5 (falta campo «Facultad») MEDIO **abiertos** | `Audit2_SeedbedFixTest` + Seedbed (82) | PARTIAL por H3/H5 (pasos de la spec sin cumplir) |
| CU14 | Modificar semillero | Declarado cerrado | **PASS WITH FIXES** | RN06, E3 concurrencia 409 | CU14-H1 **ALTO** (no se podía asignar/reasignar líder, A2) **corregido** (`leader_id`, solo Admin); H2–H5 MEDIO/BAJO abiertos | ídem | Falta que la concurrencia sea obligatoria (H3) |
| CU15 | Cambiar estado de semillero | Declarado cerrado | **PASS WITH FIXES** | Motivo obligatorio, rechazo automático | CU15-H1 **ALTO** (`PUT` cambiaba el estado sin motivo) **corregido**; H2 (transacción + auditoría del rechazo masivo) **corregido**; H3 (toggle sin estado destino) abierto | ídem | — |
| CU16 | Consultar semilleros (web) | Declarado cerrado | **PASS WITH FIXES** | Filtros, vista Ver | CU16-H2 **ALTO** (`members_count`/Integrantes leían `seedbed_user`, regresión de CU21) **corregido**; H1 filtros facultad/CAT/área + «Mis semilleros» **implementado**; H3 (Resultados en «Ver») **implementado** parcialmente (sección de solo lectura); H4 (texto E1) **corregido** | ídem | **Frontend solo validado con `node --check` y revisión de código** |
| CU17 | Semilleros por facultad (PWA) | Declarado cerrado | **PASS WITH FIXES** | Lista, búsqueda, offline | CU17-H1 **ALTO** = T1 (solo-activos solo en el cliente) **corregido en el servidor**; H2–H5 abiertos | ídem | Sin tests de frontend/PWA (T3) |
| CU18 | Detalle de semillero (PWA) | Declarado cerrado | **PASS WITH FIXES** | Misión/visión/objetivos/coordinador | CU18-H1 **ALTO** (objetivos inactivos visibles) **corregido**; H2 (E1 «Este semillero ya no está disponible») **corregido** servidor + PWA; H4 (fuga de `users`) **corregido** | ídem | Comportamiento E1 en PWA no probado en navegador |
| CU19 | Gestionar objetivos | Declarado cerrado | **PASS WITH FIXES** | RN06, mínimo 10 caracteres | CU19-H1 **ALTO** (objetivo corto/rechazado se descartaba en silencio) **corregido** (validación en cliente + aviso); H4 = T1 **corregido** | `Objective*` (17) | **Borrado físico de objetivos** = desviación documentada (Jose) que roza RN01 |
| CU20 | Gestionar resultados | Declarado cerrado | **PASS WITH FIXES** | RN06, fecha opcional | H1 (se gestionan en `/results`, no al editar el semillero) MEDIO abierto; H2–H4 BAJO | `Result*` (18) | El «Ver» del semillero ahora lista resultados y enlaza a `/results` |
| CU21 | Integrantes de semillero | Declarado cerrado | **PASS WITH FIXES** | RN06, unicidad de código | CU21-H1 MEDIO (reactivar duplicaba código activo) **corregido**; H2 (teléfono/dirección a Administrativo) MEDIO abierto | `SeedbedMemberTest` (17) | A1 «pestaña dentro del semillero» no existe (pantalla aparte) |
| CU22 | Enviar solicitud | Declarado cerrado | **PASS WITH FIXES** | Validaciones, regla de una activa | CU22-H1 **ALTO** (`requests.phone` en claro) **corregido** (cast + migración); CU22-H2 **ALTO** (staff creaba solicitudes APROBADAS a nombre de otro) **corregido**; H3 (RN05 vs integrante) y H4 (bloqueo de por vida) MEDIO abiertos | `RequestCrudTest` (1 ajustado) + `Audit2_Cu22RequestTest` | Restringir la **ruta** a solo Estudiante queda pendiente de decisión |
| CU23 | Consultar mis solicitudes | Declarado cerrado | **PASS WITH FIXES** | RN13, detalle, respuesta del líder | CU23-H1 **ALTO** (E1 sin conexión no implementado; el acta CU23 afirmaba lo contrario) **corregido** (`/requests/my` y `/proposals/my` en caché) | test de RN13 | **Acta CU23 corregida en este informe** |
| CU24 | Gestionar solicitudes recibidas | Desplegado y validado | **PASS WITH FIXES** | Validación especial: RN06, filtros, E1–E3, A3, auditoría, paso 7 | CU24-H1 MEDIO (rechazo masivo sin auditoría) **corregido**; H2–H5 BAJO abiertos | `RequestManagementTest` (23) | Paso 7 (ofrecer integrante) sin test de front. Falta «Solicitudes» en el menú lateral de escritorio (ver §6) |
| CU25 | Registrar propuesta | Desplegado y validado | **PASS WITH FIXES** | Validación especial (tabla abajo) | CU25-H1 **ALTO** (áreas duplicadas → 500 y propuesta sin áreas) **corregido**; H2 (sin transacción) **corregido**; H8 (cobertura) **cubierta**; H4/H5 MEDIO abiertos | `Audit2_Cu25ProposalTest` + Proposal (40) | Estado inicial `PENDIENTE` (spec: `recibida`) → puente en CU26 |

### Validación especial de CU24 y CU25

| Ítem | Veredicto | Evidencia |
|---|---|---|
| CU24: RN06 en listado, filtros, detalle | ✅ | `RequestManagementTest` |
| CU24: E1 (motivo), E2 (409 literal), E3 (403), A3 | ✅ | ídem |
| CU24: sin regresión por CU25 | ✅ | CU25 solo cambió `GET /programs` |
| CU24: entorno desplegado = código auditado | ✅ parcial | Al inicio de la ronda, huellas de 227 archivos comparadas: solo difieren SIA (esperado) y `UserFactory.php` (solo tests) |
| CU25: programa válido; áreas múltiples en `proposal_area` | ✅ | pivote con índice único y FK cascade |
| CU25: sin duplicados / sin huérfanos | ❌→✅ | H1 reproducido y **corregido** |
| CU25: propuesta del usuario autenticado | ✅ | `user_id` forzado para estudiante |
| CU25: descripción 19/20/2000/2001 | ✅ | tests nuevos |
| CU25: teléfono cifrado; no en claro | ✅ | tests nuevos |
| CU25: RN15 (5 en 24 h, 6.ª → 429, por usuario, ventana móvil) | ✅ | tests nuevos |
| CU25: transaccional | ❌→✅ | H2 **corregido** |
| CU25: `Area::proposals()` coherente | ✅ | |
| CU25: `ESTUDIANTE` puede `GET /programs` | ✅ | (incluye inactivos: CU25-H10 BAJO abierto) |
| CU25: migración no destruye datos | ✅ `up()`; ⚠ `down()` pierde áreas múltiples (H9 BAJO) | Probada en SQLite; **datos reales en MySQL NOT VERIFIED** |

## 4. Hallazgos corregidos

| ID(s) | CU | Sev. | Causa raíz | Corrección | Archivos principales | Test |
|---|---|---|---|---|---|---|
| CU13-H1 · CU17-H1 · T1 · CU18-H1/H2/H4 · CU19-H4 | 13, 17, 18, 19 | **CRÍTICO/ALTO** | `GET /seedbeds`, `/seedbeds/{id}` y `/objectives` devolvían todo a ESTUDIANTE; el filtro «activos» solo estaba en el navegador | Para ESTUDIANTE: solo ACTIVOS; `show` de inactivo → 404 «Este semillero ya no está disponible»; se ocultan `authorization_reference`, `inactivation_reason`, `users`, datos del coordinador salvo nombre/correo; objetivos solo activos de semilleros activos | `SeedbedController`, `ObjectiveController`, `Seedbed` | `Audit2_SeedbedFixTest` (5) |
| CU05-H1 (+CU02-H1, CU05-H5) | 5, 2 | **ALTO** | `PUT /profile` aceptaba `name`/`email` | Solo teléfono y contraseña (spec CU05); el teléfono solo se toca si viene; UI de solo lectura | `AuthController`, `pwa-profile.module.js` | `AuditFixesAuthUsersTest` |
| CU06-H1 · T1 | 1, 4, 5, 6 | **ALTO** | Sin `api/lang`, `APP_LOCALE=en` | `lang/es/{validation,auth,passwords}.php`; defaults `es` en `config/app.php` | `api/lang/es/*`, `config/app.php` | 2 tests de idioma |
| CU15-H1 | 15 | **ALTO** | `status` en `rules()` del `PUT` | 422 «Para cambiar el estado usa Activar o Inactivar.»; selector oculto al editar | `SeedbedController`, `seedbeds.module.js` | `test_update_cannot_change_status` |
| CU14-H1 | 14 | **ALTO** | Sin forma de asignar líder | `leader_id` (solo Admin; usuario activo LIDER_SEMILLERO; un único líder); select «Líder responsable» | `SeedbedController`, `seedbeds.module.js` | 5 tests |
| CU16-H2 · T2 | 16 | **ALTO** | `members_count`/«Integrantes» leían `seedbed_user` | Cuentan y listan `seedbed_members` ACTIVOS (nombre, programa, nivel; sin contacto) | `Seedbed`, `SeedbedController` | 2 tests |
| CU19-H1 | 19 | **ALTO** | `onSaved` solo hacía `console.warn` | Validación en cliente (10–2000) antes de cerrar y aviso con el texto de los objetivos rechazados | `seedbeds.module.js` | (front) `node --check` |
| CU22-H1 | 22 | **ALTO** | `requests.phone` en claro | Cast `encrypted` + migración `2026_10_01_000001` (columna `text`, cifra filas existentes de forma idempotente, `down()` descifra) | `MembershipRequest`, migración | 2 tests (incl. migración con datos previos) |
| CU22-H2 | 22 | **ALTO** | `store` aceptaba `status` y `user_id` del staff | Toda solicitud nace `PENDIENTE` | `RequestController` | `test_staff_cannot_create_request_already_approved` |
| CU23-H1 | 23 (+26) | **ALTO** | Endpoints fuera de la caché offline | `/requests/my`, `/proposals/my` en `OFFLINE_ENDPOINTS` | `services/api.service.js` | (front) |
| CU25-H1 · H2 | 25 | **ALTO/MEDIO** | Sin `distinct` ni transacción | `distinct` en `areas.*` y `DB::transaction` en `store`/`update` | `ProposalController` | 3 tests |
| CU04-H1 | 4 | MEDIO | Inactivos podían restablecer | Filtro `status=ACTIVO` conservando la respuesta anti-enumeración | `AuthController` | 2 tests |
| CU01-H1 | 1 | MEDIO | Ruta pública sin método | Ruta y línea de `RNF03SecurityTest` retiradas | `api.php` | 1 test |
| CU06-H2 · H3 | 6 | MEDIO | Cambio de rol sin candado; excepción sin capturar | Candado también para `role`; error 503 controlado | `UserController` | 4 tests |
| CU06-H4 | 6 | MEDIO | `GET /users` completo a L/ADM | Respuesta mínima (id, nombre, rol, estado) salvo Admin | `UserController` | 2 tests |
| CU07–CU10 A5 | 7–10 | **ALTO** | Botones de escritura visibles al Administrativo | `noCreateFor`/`noEditFor` en los 4 módulos; el router admite consulta a L/ADM | módulos de catálogo, `core/router.js` | (front) |
| CU05-H2 · H3 | 5 | MEDIO | SVG y contenido sin validar; sin límite de intentos | Validación real de imagen, límite 2 MB coherente, 5 intentos/min | `AuthController` | 6 tests |
| CU21-H1 | 21 | MEDIO | Reactivar sin guarda | 422 «El estudiante ya es integrante de este semillero.» | `SeedbedMemberController` | 1 test |
| CU15-H2 · CU24-H1 · CU13-H7 | 13, 15, 24 | MEDIO | `update()` masivo sin auditoría; sin transacción | Se recorre con `forceFill` (auditado) y `DB::transaction` | `SeedbedController` | 1 test |
| Bug `ProposalController::updateStatus` | 25/27 | MEDIO | `reviewed_by/at` fuera de `$fillable` | `forceFill` | `ProposalController` | `test_update_status_records_reviewer` |
| CU16-H1 · H3 · H4 · CU18-E1 (front) | 16, 18 | ALTO/MEDIO | Filtros y «Resultados» sin implementar; PWA sin E1 | Filtros combinables + «Mis semilleros»; sección Resultados en «Ver»; aviso E1 | `crud.engine.js`, `seedbeds.module.js`, `pwa-seedbeds.module.js` | (front) revisión de código |
| Revisión de seguridad #1 (caché offline personal) | 23, 26 | MEDIO | Al ampliar la caché offline (CU23-H1) las copias de `/requests/my` y `/proposals/my` (teléfono, mensaje, respuesta) sobrevivían al cerrar sesión, contra CU03 paso 3 | `clearLocalSession()` borra ahora solo esas dos claves; la caché pública de semilleros se conserva (decisión documentada de CU03) | `services/storage.service.js` | (front) |
| Revisión de seguridad #2 · #5 | 16, 17, 18 | MEDIO | `GET /seedbeds` cargaba `users` completo (correo, teléfono descifrado, foto) para Líder/Administrativo y el CAT completo para el estudiante | `users:users.id,users.name`; `cat:id,name` y `group:id,name` para el estudiante | `SeedbedController` | `Audit3_ReviewFixesTest` (3) |
| Revisión de seguridad #3 · #4 | 22 | MEDIO | Un teléfono vacío quedaba sin cifrar (500 al leerlo con el cast) y un valor cifrado con otra `APP_KEY` se volvía a cifrar sin aviso | La migración convierte `""` en `NULL` y **aborta** si encuentra un payload ya cifrado que no puede descifrar | migración `2026_10_01_000001` | `Audit3_ReviewFixesTest` (3) |
| Revisión de seguridad #6 | 5 | BAJO | `photo` sin tope antes de `base64_decode` | `max:3000000` | `AuthController` | `Audit3_ReviewFixesTest` (1) |

**Tests existentes modificados (ninguno borrado ni debilitado):**
- `CU05ProfileTest`: `..._updating_name_only...` → `..._updating_phone_only...` (la spec CU05 solo permite actualizar el teléfono).
- `RNF03SecurityTest`: se quitó `POST api/register` de la lista de rutas públicas (la ruta ya no existe).
- `RequestCrudTest`: el test de `PUT /requests/{id}` (CU24) → 405; `..._requires_user_seedbed_and_status` → `..._requires_user_and_seedbed` (CU22 paso 4, CU24).
- `Audit2_Cu25ProposalTest`: la aserción de propiedad ya no lee `user_id` de la respuesta (CU26 lo retira) y consulta la BD.

## 5. Hallazgos abiertos y justificación de los ALTO que siguen abiertos

**T-AUD (ALTO, transversal, aprobado explícitamente como pendiente):** `AuditObserver` solo guarda usuario, acción, tabla y
registro. La spec exige valores anteriores/nuevos (A2 de CU07–CU14, RN07), IP, hora de Bogotá, inmutabilidad y el manejo de
E1. **Causa raíz: CU29**, cuyo cambio esta ronda prohíbe («no modificarlo salvo regresión»). Riesgos: no hay rastro de *qué*
cambió. Plan: CU29 primero (migración aditiva `old_values/new_values/ip_address`, excluir campos cifrados) y luego CU30.

Abiertos MEDIO/BAJO (sin cambios de comportamiento en esta ronda):

| CU | IDs |
|---|---|
| 1–5 | CU01-H2..H4 · CU02-H2..H5 · CU03-H1..H3 · CU04-H2..H6 · CU05-H4, H6, H7 |
| 6–12 | CU06-H5..H8 · CU07..10-H3..H5 (texto del 403, mensajes de éxito, alta con estado) · CU11-H2..H4 · **CU12-H1** (documento opcional) · CU12-H2..H4 |
| 13–15 | **CU13-H3** (paso 10), **H4** (texto E3; CAT/coordinador inactivos bloquean la edición), **H5** (falta el campo «Facultad»), H6, H9..H11 · CU14-H2..H5 · CU15-H3..H6 |
| 16–20 | CU16-H5 · CU17-H2..H5 · CU18-H3, H5, H6 · CU19-H2 (borrado físico, desviación de Jose), H3, H5..H7 · CU20-H1..H4 · T3 (sin tests de frontend), T4 (guard RN06 triplicado) |
| 21–25 | CU21-H2..H6 · **CU22-H3, H4** (RN05 vs integrante; bloqueo de por vida) · CU22-H5..H7 · CU23-H2, H3 · CU24-H2..H5 · **CU25-H4, H5** (staff crea/edita en nombre de otro; teléfono descifrado en listados) · CU25-H3, H6, H7, H9, H10 |

## 6. Decisiones y avisos para Jose

1. **Menú lateral de escritorio sin «Solicitudes» ni «Propuestas»** (solo la barra inferior móvil). La spec de CU24 dice que el
   líder entra por «Solicitudes» en el menú, así que es un hueco de CU24. El arreglo (2 enlaces en `layout/layout.view.js`) está
   preparado pero **no se aplicó**: el clasificador del entorno bloqueó subirlo al repo compartido. Requiere tu visto bueno.
2. **Restringir `POST /requests` solo a Estudiante** (la spec da CU22 solo al estudiante): hoy L/ADM siguen pudiendo crear, pero
   ya siempre en `PENDIENTE`.
3. **`APP_LOCALE` en producción**: si el `.env` fija `en`, los mensajes seguirán en inglés (no se pudo leer). Borrar la línea o
   ponerla en `es` y regenerar la caché de configuración si existe.
4. **Vocabulario de estados de propuesta**: puente reversible en esta ronda; la migración real se hace con CU27.
5. Falta la **restricción de evaluación de propuestas** (spec: solo Administrativo; código: también el Líder) — CU27.

## 7. Validación técnica

| Comprobación | Comando / método | Resultado |
|---|---|---|
| Línea base | `php artisan test` (SQLite `:memory:`, con `-e`) | 434 passed |
| **Suite final** | ídem | **506 passed (1680 aserciones), 0 fallos, 0 warnings** |
| CU01–CU05 | `--filter="Auth\|CU01\|CU02\|CU03\|CU04\|CU05\|AccountActivation\|RF16\|RNF05"` | 138 passed |
| CU06 | `--filter="UserManagement\|AuditFixesAuthUsers\|User"` | 99 passed |
| CU07 / CU08 / CU09 / CU10 | `Faculty\|RF02` / `Program\|RF03` / `Cat\|RF04` / `Area\|RF05` | 26 / 33 / 70 / 29 passed |
| CU11 / CU12 | `Group` / `Coordinator` | 19 / 18 passed |
| CU13–CU18 | `Seedbed` | 82 passed |
| CU19 / CU20 / CU21 | `Objective` / `Result` / `SeedbedMember` | 17 / 18 / 17 passed |
| CU22–CU24 | `Request\|Audit2_Cu22` | 46 passed |
| CU25 / CU26 | `Proposal\|Audit2_Cu25` / `CU26MyProposals` | 40 / 14 passed |
| Transversales | `Security\|Rbac\|AuditTest\|Sia` | 127 passed |
| Sintaxis | `php -l` en los 26 PHP modificados; `node --check` en los 10 JS | 0 errores |
| Estilo (Pint) | `pint --test` sobre los archivos tocados | **No aplicado**: el proyecto no usa el preset de Pint (falla también `routes/api.php`, `Seedbed.php`…); reformatear causaría un diff ajeno a la ronda |
| Análisis estático | — | **No disponible**: el proyecto no tiene PHPStan ni Larastan |
| Migraciones (MySQL de desarrollo) | `migrate` → `rollback --step=1` → `migrate` para `2026_10_01_000001` y `000002` | Sin errores. La BD de desarrollo **no tenía filas con teléfono**: la ruta de datos solo se probó en SQLite |
| Migraciones en producción | `migrate:status` en `htg` | **NOT VERIFIED** (lectura bloqueada por el entorno) |
| Entorno desplegado vs código (al inicio de la ronda) | Huellas MD5 de 227 archivos contra `htg` | 224 idénticos; difieren 2 de SIA (ampliación aún sin desplegar) y `UserFactory.php` (solo tests) |
| Navegador / E2E | — | **NOT VERIFIED**: el puerto local 8080 es inalcanzable desde el host. Los cambios de frontend (CU16, CU18, CU19, CU23-E1, CU26, catálogos, perfil) se validaron con `node --check` y revisión de código |
| SIA con el modelo real | — | **NOT VERIFIED**: el contenedor local no resuelve DNS hacia Groq; validar en producción |

**Graphify: NO actualizado.** Estado verificado: 2281 nodos, 4408 aristas (coincide con lo declarado; 337 comunidades no se
recontaron). La regla de `CLAUDE.md` pide reextraer en hitos de ~5 CU (el último fue en CU25) y prohíbe `graphify update .` a
secas cuando cambian documentos (esta ronda cambió varios), porque descarta nodos semánticos. El flujo permitido es la skill
`/graphify . --update`, que no está disponible en esta sesión. Queda para el hito de CU30 o para una sesión con la skill.

## 8. Despliegue (ejecutado el 2026-10-03)

1. Respaldo de la tabla `requests` (la migración `2026_10_01_000001` **cifra los teléfonos con `APP_KEY`**: debe ser la misma
   clave que los descifrará) y de `proposals`.
2. `php artisan migrate --pretend`, revisar, `migrate --force` (`2026_10_01_000001` y `2026_10_01_000002`). Nunca `--seed`.
3. Subir `api/lang/` (carpeta nueva), controladores, modelos, `ProposalResource`, rutas, `config/app.php`; revisar
   `APP_LOCALE`/`APP_FALLBACK_LOCALE` en el `.env` y `config:clear`.
4. Subir el frontend y el service worker (`CACHE_NAME` `semilleros-v23`) en el orden habitual (módulos primero, SW al final).
5. Subir `api/resources/sia/base-conocimiento.md` y validar SIA con el modelo real.
6. Validación en vivo con cuentas de prueba `qa_temp_*` (estudiante: API de semilleros sin datos sensibles; líder: filtros y
   «Mis semilleros»; CU26; perfil) y `diff` 0 contra el repo.

## 9. Git

Ver el cierre de este informe en el resumen final de la sesión (rama, commit y push).

### Resultado del despliegue (2026-10-03)

| Paso | Resultado |
|---|---|
| Comparación previa | Producción difería en 34 archivos: los 32 de la ronda + `SiaAssistant.php` (cambiado en el commit de SIA anterior a la base, nunca desplegado) + `UserFactory.php` (solo tests) |
| Respaldo | `~/backups/ut-edu.online/2026-10-03_pre_auditoria/`: 28 archivos existentes, `.env`, `requests.json` (2 filas) y `proposals.json` (4 filas) |
| `.env` de producción | `APP_LOCALE=es` ya estaba (los mensajes salían como `validation.*` porque faltaba `api/lang`); `APP_FALLBACK_LOCALE=en` se dejó |
| Lint con PHP 8.2 en el servidor | 34 archivos, 0 errores |
| `migrate --pretend --force` | SQL esperado: `modify phone text null`, `update … phone = null where phone = ''`, `add review_note` |
| Migraciones | `2026_10_01_000001` y `…000002` aplicadas. `requests` 2 filas y `proposals` 4, sin cambios; el teléfono de `requests` quedó cifrado (200 caracteres) |
| Modo mantenimiento | `down` durante unos segundos y `up` al terminar (comprobado: el sitio responde 200) |
| **Diff 0** | 233 archivos idénticos entre el repo y producción |
| Humo | `/api/seedbeds` sin token → 401; `POST /api/register` → 404; `forgot-password` vacío → «El correo es obligatorio.»; `CACHE_NAME` = `semilleros-v23` |
| Validación en vivo (cuentas `qa_temp_*`, ya borradas) | **9/9 PASS** (ver abajo) |

Comprobaciones en vivo: el estudiante solo recibe semilleros ACTIVOS y sin `authorization_reference`, `inactivation_reason`,
`users`, `pending_requests_count` ni datos del CAT; objetivos solo ACTIVOS; el Líder recibe `users` solo con id, nombre y pivote;
`PUT /profile` no cambia correo ni nombre; áreas duplicadas → 422 y no se crea la propuesta; propuesta válida → 201 con
`status_label` «Recibida», `review_note` y sin `user_id` ni `reviewed_by`; teléfono de la propuesta cifrado en BD; una solicitud
enviada con `status:"APROBADA"` queda `PENDIENTE` con el teléfono cifrado y legible por el modelo. Al terminar: `qa_temp` = 0 y
conteos reales intactos.

**Lecciones del despliegue** (ya reflejadas en `docs/despliegue/RONDA_AUDITORIA_2026-10-02.md`): `migrate --pretend` en producción
exige `--force` además (si no, pide confirmación interactiva y se cancela); el paquete debe armarse con la **lista real de
diferencias** contra producción y no solo con un rango de commits.

**Aún no probado:** el frontend en navegador (pasos manuales en §7 y en `CU26.md`), el flujo real con Google y las respuestas de
SIA con la base nueva (la base y `SiaAssistant` ya están en producción: probar preguntas reales en el chat).

---

## Actualización 2026-10-03 — T-AUD cerrado

El único hallazgo ALTO que quedaba abierto (**T-AUD**: la auditoría no guardaba valores anteriores/nuevos) se **cerró con CU29**
(acta `docs/validacion/CU29.md`, 17 tests, suite en 523): valores anteriores y nuevos sin contraseñas ni tokens y con lo cifrado
enmascarado, IP, `STATUS_CHANGE` como acción propia, auditoría de las tablas pivote (áreas, programas, líder, integrantes de
proyecto, grupos RBAC) y de los límites de SIA, registros no modificables y E1 (log técnico + alerta al Administrador sin revertir
la operación; el LOGIN sigue siendo estricto por decisión de CU01). Con esto, el alterno A2 de CU07–CU14 queda cubierto en el
backend; **la pantalla de Auditoría todavía no muestra los valores** (CU30). Ya no queda ningún CRÍTICO ni ALTO abierto de CU01–CU25.

## 8. Actualización 2026-10-04 (decisiones de Jose y cierres)

- **CU22-H3/H4:** resuelto con la regla «un solo semillero a la vez» (ver `CU22.md`).
- **CU12-H1:** el documento del coordinador **es opcional** (decisión de Jose).
- **CU25-H4/H5:** cerrados con CU27 (solo el estudiante crea/edita; el teléfono no sale en listados).
- **Decisión §6.2** (`POST /requests` solo Estudiante): aplicada. **§6.4** (vocabulario de propuestas): migración real hecha (`RECIBIDA/VIABLE/ARCHIVADA`).
- **Pendiente de decisión:** CU13-H3/H4/H5 (ver `CU13.md`). Los demás MEDIO/BAJO abiertos se conocen solo por su identificador (el detalle de cada uno no se guardó); para retomarlos hay que re-auditar el caso de uso contra la spec.
