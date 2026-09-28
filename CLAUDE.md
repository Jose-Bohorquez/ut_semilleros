# CLAUDE.md — UT Semilleros

PWA de Sistema de Semilleros de Investigación, Universidad del Tolima. Frontend vanilla JS
(`core/`, `modules/`, `layout/`, `services/`) + backend Laravel 12 en `api/`. Producción:
`https://ut-edu.online/`.

**Esta carpeta contiene además dos proyectos hermanos independientes, no mezclarlos:**
- `SGAA/` — sistema de asistencia académica (tiene su propio `CLAUDE.md`, léelo si trabajas ahí).
- `todo_ut-edu.space_old/` — scaffold Laravel descartado, nunca desarrollado, historia muerta.

## Antes de trabajar aquí

1. Lee `docs/roles-usuarios.md` y `docs/CHANGELOG.md` — matriz de roles y el historial completo de
   bugs encontrados/corregidos en auditorías previas.
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
  (hoy `semilleros-v13`).
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
(`docker exec -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: ut_semilleros_api php artisan test`).
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
- **Después de cada cambio de código corre `graphify update .`** (sin costo de LLM). Si cambiaste
  docs, el refresco semántico es `/graphify --update`.

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

`api/.env` local contiene credenciales de producción: **no se edita**. En su lugar,
`docker-compose.override.yml` (gitignored) fuerza como variables de entorno del contenedor la BD
de Docker y `MAIL_MAILER=log`. Esas variables mandan sobre el `.env` porque Laravel usa Dotenv
inmutable. **Nunca cachees la config** (`config:cache`) en local, porque eso rompería este
aislamiento. `.dockerignore` excluye `db/` (si no, el build falla por permisos). Para levantarlo:
`docker compose up -d --build && docker exec ut_semilleros_api php artisan migrate --force`.

Ningún agente ejecuta DDL/DML ni despliega por su cuenta. Todo lo que recomienden se apoya en este
archivo, en `docs/` y en el grafo, y cualquier cambio se cierra con una validación real y evidencia
de qué se probó.
