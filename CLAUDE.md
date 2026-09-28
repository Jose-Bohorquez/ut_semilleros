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
web ✅ desplegado y validado en producción · PWA ✅ en local (2026-09-28; E5 queda para CU02) · RNF01 ✅ (instalación desde un clon limpio) · RNF03
parcial (tokens de 8 h / 30 d).

Diferencias conocidas a cerrar (2026-09-28): RN10
(contraseña fuerte) no se aplica; RN06 (líder solo en sus semilleros) no se aplica; RN02/RN03
(referencia de autorización escrita) sin campo; RN08 (códigos únicos) solo en CAT, áreas y grupos;
RF15/CU28 reportes y RF16/RN09 consentimiento de datos no existen; CU02 no usa Google. El avance de
la validación 1 a 1 queda en `docs/qa/` (no versionado mientras haya vulnerabilidades abiertas).

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
fuera vía `.graphifyignore`. Último refresco: 2026-09-26, con 987 nodos, 1987 aristas y 114
comunidades. God nodes: `User`, `apiFetch()`, `Controller`, `Seedbed`, `createCrudModule()`,
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
