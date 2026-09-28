# Graph Report - _public_html  (2026-09-28)

## Corpus Check
- 10 files · ~176,996 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1490 nodes · 2797 edges · 177 communities (68 shown, 78 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 97 edges (avg confidence: 0.88)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Seeders de catálogos
- Tests de regresión de seguridad
- Modelo User y políticas
- Agentes y flujo de CU
- Motor CRUD frontend
- Rutas API
- Auth frontend y app shell
- Tests de autenticación
- CU01 login web y actores
- CU08 Administrar programas
- Modelo de datos documentado
- CU estudiante PWA (CU17)
- CU09 Administrar CAT
- CU14 Modificar semillero
- AuthController API
- Escape HTML y layout
- CU estudiante PWA (CU17)
- AuthController API
- Actores web y CU16
- Form Requests de auth
- Dependencias Node (build)
- Arquitectura 2020 CU→módulos
- Auditoría y proyectos API
- CU13 Registrar semillero
- Escape HTML y layout
- Suscripciones push
- Notificaciones API
- Resultados API
- CU02 login Google (PWA)
- Manifest PWA
- Tests de autenticación
- Changelog 2026-07-28
- Correos activación/reset
- Layout controller
- Notificaciones frontend
- Escape HTML y layout
- Objetivos API
- Coordinadores modelo y tests
- Tests de objetivos
- Programas API
- Tests de resultados
- Tests de semilleros
- CU01 login web y actores
- Resultados API
- composer.json metadatos
- Scripts de composer
- Modelo User y políticas
- Solicitudes modelo y tests
- Tests de integrantes
- Auth frontend y app shell
- Router y guards
- Escape HTML y layout
- Importación de usuarios UI
- Áreas API
- Grupos API
- Productos API
- Middleware rol y activo
- Dependencias dev PHP
- Suscripciones push
- Config composer
- Migración create_users_table.php
- Migración create_cache_table.php
- Migración create_programs_table.php
- Manejo de excepciones
- Dependencias PHP
- Modelo User y políticas
- Tests de autenticación
- Agente deploy-engineer
- Agente test-engineer
- Autoload PSR-4
- Config de logging
- Agente backend-tester
- Agente bug-historian
- Agente code-reviewer
- Agente db-architect
- Agente qa-design-mobile
- Agente qa-design-web
- Agente use-case-auditor
- Test unitario ejemplo
- Agente architecture-advisor
- Agente architecture-reviewer
- Agente frontend-tester
- Agente pwa-tester
- Agente ui-designer
- Normativa y BPMN
- Despliegue real Hostinger
- Service worker
- autoload-dev
- extra
- console
- README y convenciones
- Despliegue según README
- dev-setup.sh
- Apple Touch Icon 152x152 (PWA br
- Apple Touch Icon 167x167 (PWA br
- Apple Touch Icon 180x180 (PWA br
- Login page background image
- Login page logo (Universidad del
- Agentes y flujo de CU
- Modelo de datos documentado
- Decisión doc vs stack real
- Contexto del proyecto
- Despliegue real Hostinger
- Entorno Docker local aislado (do
- Decisión doc vs stack real
- Contexto del proyecto
- Despliegue real Hostinger
- Agentes y flujo de CU
- Roles, HU y Scrum
- Decisiones de arquitectura doc
- Entorno Docker local aislado (do
- Docker compose local
- Docker compose local
- Docker compose local
- Docker compose local
- Docker compose local
- Docker compose local
- PWA icon 192x192 (standard brand
- PWA maskable icon 192x192 (Andro
- PWA icon 512x512 (standard brand
- PWA maskable icon 512x512 (Andro
- Bocetos de arquitectura
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- Shell index.html y CDNs
- README y convenciones
- README y convenciones
- README y convenciones
- Docker compose local
- Pending non-blocking improvement
- Despliegue según README
- README y convenciones
- Despliegue según README
- README y convenciones
- README y convenciones
- README y convenciones
- README y convenciones
- README y convenciones
- Docker compose local
- Docker compose local

## God Nodes (most connected - your core abstractions)
1. `User` - 137 edges
2. `escapeHtml()` - 51 edges
3. `TestCase` - 38 edges
4. `Seedbed` - 35 edges
5. `apiFetch()` - 34 edges
6. `RN07 Toda escritura/login genera auditoría inmutable` - 32 edges
7. `Controller` - 31 edges
8. `Faculty` - 30 edges
9. `LayoutView()` - 29 edges
10. `RNF03 Seguridad web y móvil` - 29 edges

## Surprising Connections (you probably didn't know these)
- `renderSkeleton()` --calls--> `LayoutView()`  [EXTRACTED]
  modules/pwa/pwa-proposals.module.js → layout/layout.view.js
- `renderSkeleton()` --calls--> `LayoutView()`  [EXTRACTED]
  modules/pwa/pwa-seedbeds.module.js → layout/layout.view.js
- `api/.env.example ausente por comentario al final de linea en .gitignore` --conceptually_related_to--> `Prohibiciones derivadas de incidentes reales`  [INFERRED]
  docs/CHANGELOG.md → CLAUDE.md
- `CACHE_STORE=database para rate limit de login (RN14)` --references--> `RN14 Bloqueo 60 s tras 5 intentos fallidos`  [EXTRACTED]
  docker-compose.yml → docs/especificacion/Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md
- `servicio frontend (ut_semilleros_frontend, 8080)` --shares_data_with--> `index.html (shell SPA/PWA, #app, app.js)`  [INFERRED]
  docker-compose.yml → index.html

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Casos de uso que incluyen Registrar auditoría (CU29)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu01, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu02, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu04, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu06, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu13, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu14, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu15, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu22, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu24, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu25, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu27, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu29, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn07 [EXTRACTED 1.00]
- **Ronda de validacion CU01 2026-09-28** — docs_validacion_cu01_acta, docs_changelog_sesion_2026_09_28, docs_validacion_cu01_login_test, readme_dev_setup_script, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu01, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rnf01, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rnf03 [EXTRACTED 1.00]
- **Excepciones de autorización HTTP 403 (control por rol / RN06)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu07_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu08_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu09_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu10_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu11_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu12_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu14_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu15_e1, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu19_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu20_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu21_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu24_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu27_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn06 [EXTRACTED 1.00]
- **Flujo de vinculación estudiante-semillero** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu17, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu18, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu22, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu23, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu24, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu21, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rf10, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn05 [EXTRACTED 1.00]
- **Límites de tasa HTTP 429 (login, recuperación, propuestas)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu01_e4, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu04_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu25_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn14, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn15 [EXTRACTED 1.00]
- **Entorno local Docker de desarrollo** — docker_compose_frontend, docker_compose_api, docker_compose_db, docker_compose_phpmyadmin, claude_entorno_docker_aislado [EXTRACTED 1.00]
- **Modelo de dominio documental SemillerosUT** — docs_especificacion_documentacion_tecnica_semillerosut_hotbed, docs_especificacion_documentacion_tecnica_semillerosut_membership_request, docs_especificacion_documentacion_tecnica_semillerosut_proposal, docs_especificacion_documentacion_tecnica_semillerosut_member, docs_especificacion_documentacion_tecnica_semillerosut_user_model, docs_especificacion_documentacion_tecnica_semillerosut_log_model, docs_especificacion_documentacion_tecnica_semillerosut_catalogos [EXTRACTED 1.00]
- **Capas de seguridad de datos (schema validation, bcrypt, Sanctum, auditoría)** — docs_especificacion_documentacion_tecnica_semillerosut_mongodb_schema_validation, docs_especificacion_documentacion_tecnica_semillerosut_bcrypt_hashing, docs_especificacion_documentacion_tecnica_semillerosut_api_rest_sanctum, docs_especificacion_documentacion_tecnica_semillerosut_auditoria_observer [EXTRACTED 1.00]
- **Catálogos administrados (CRUD con cambio de estado)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu07, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu08, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu09, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu10, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu11, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu12, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn01, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn08 [INFERRED 0.85]
- **Validación de código duplicado en catálogos (RN08)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu07_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu08_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu09_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu10_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu11_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu12_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn08 [INFERRED 0.95]

## Communities (177 total, 78 thin omitted)

### Community 0 - "Seeders de catálogos"
Cohesion: 0.06
Nodes (16): SeedbedController, SeedbedMemberController, Seedbed, AreaSeeder, CatSeeder, CoordinatorSeeder, DatabaseSeeder, FacultySeeder (+8 more)

### Community 1 - "Tests de regresión de seguridad"
Cohesion: 0.07
Nodes (16): AuthTest, SecurityRegressionTest, CU01LoginTest, App\Http\Resources\UserResource, App\Models\Audit, App\Models\Faculty, App\Models\MembershipRequest, App\Models\Program (+8 more)

### Community 2 - "Modelo User y políticas"
Cohesion: 0.07
Nodes (10): User, UserPolicy, AreaCrudTest, AuditTest, CatCrudTest, FacultyCrudTest, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User (+2 more)

### Community 3 - "Agentes y flujo de CU"
Cohesion: 0.05
Nodes (47): Agentes .claude/agents (use-case-auditor, backend/frontend/pwa-tester, architecture-reviewer...), Cache CDN Hostinger bajada a 5 min + CACHE_NAME semilleros-v13, Despliegue real en Hostinger (alias htg, ~/domains/ut-edu.online/public_html), Diferencias conocidas a cerrar (RN10, RN06, RN02/RN03, RN08, RF15/CU28, RF16/RN09), Entorno local Docker aislado de produccion, Flujo de validación de casos de uso en equipo (docs/qa/), Grafo graphify-out (god nodes User, apiFetch, Controller, Seedbed, createCrudModule, initLayoutController), Hallazgo C-01: tests sin -e borrarian la BD dev (RefreshDatabase) (+39 more)

### Community 4 - "Motor CRUD frontend"
Cohesion: 0.09
Nodes (28): createCrudModule(), bindEvents(), clearFieldError(), create(), init(), isValidEmail(), renderForm(), renderTable() (+20 more)

### Community 5 - "Rutas API"
Cohesion: 0.06
Nodes (26): UserController, UserFactory, App\Http\Controllers\Api\AreaController, App\Http\Controllers\Api\AuditController, App\Http\Controllers\Api\AuthController, App\Http\Controllers\Api\CatController, App\Http\Controllers\Api\FacultyController, App\Http\Controllers\Api\GroupController (+18 more)

### Community 6 - "Auth frontend y app shell"
Cohesion: 0.11
Nodes (31): requireAuth(), requireRole(), login(), logout(), refreshBadge(), startBadgePolling(), updateBellBadge(), apiFetch() (+23 more)

### Community 7 - "Tests de autenticación"
Cohesion: 0.12
Nodes (11): Faculty, Program, ProgramSeeder, PasswordResetEndToEndTest, PasswordResetTest, ExampleTest, TestCase, Illuminate\Foundation\Testing\RefreshDatabase (+3 more)

### Community 8 - "CU01 login web y actores"
Cohesion: 0.11
Nodes (38): api/.env.example ausente por comentario al final de linea en .gitignore, Sesion 2026-09-28: validacion CU01, RF01, RNF01, Servidor de correo SMTP, Actor Usuario (abstracto), CU01 Iniciar sesión en el panel web, CU01 A1 – Olvidó la contraseña (paso 2), CU01 A2 – Recordarme (paso 3), CU01 A3 – Ruta solicitada previamente (paso 6) (+30 more)

### Community 9 - "CU08 Administrar programas"
Cohesion: 0.06
Nodes (36): CU07 E2 – Código de la facultad ya registrado, CU08 Administrar programas, CU08 A1 – Consultar detalle (desde el paso 2), CU08 A2 – Modificar (desde el paso 2), CU08 A3 – Cambiar estado (desde el paso 2), CU08 A4 – Buscar y filtrar (desde el paso 2), CU08 A5 – Actor de solo consulta, CU08 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422) (+28 more)

### Community 10 - "Modelo de datos documentado"
Cohesion: 0.08
Nodes (33): Aggregations MongoDB A1–A5 (semilleros por facultad, solicitudes por estado, propuestas por área, integrantes, auditoría), API REST /api/v1 con Laravel Sanctum, AuditObserver / colección logs solo-inserción (RF14, CU14), Hash de contraseñas bcrypt cost 12 (cast hashed), Casos de uso CU01–CU14 (diseño 2020), Despliegue piloto CAT Kennedy (Bogotá), Catálogos Faculty, Career, Cat, Area, Group, Coordinator, Documentación Técnica SemillerosUT (+25 more)

### Community 11 - "CU estudiante PWA (CU17)"
Cohesion: 0.10
Nodes (33): Actor Estudiante, CU17 A3 – Proponer idea, CU18 Consultar detalle de semillero (PWA), CU18 A1 – Ser miembro (paso 2), CU18 A2 – Solicitud ya enviada, CU18 A3 – Sin conexión, CU18 E1 – El semillero fue inactivado, CU22 Enviar solicitud de vinculación (+25 more)

### Community 12 - "CU09 Administrar CAT"
Cohesion: 0.09
Nodes (32): CU11 Administrar grupos de investigación, CU11 A1 – Consultar detalle (desde el paso 2), CU11 A2 – Modificar (desde el paso 2), CU11 A3 – Cambiar estado (desde el paso 2), CU11 A4 – Buscar y filtrar (desde el paso 2), CU11 A5 – Actor de solo consulta, CU11 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU11 E2 – Código del grupo ya registrado (+24 more)

### Community 13 - "CU14 Modificar semillero"
Cohesion: 0.09
Nodes (31): CU14 Modificar semillero, CU14 A1 – Gestionar objetivos, resultados o integrantes, CU14 A2 – Administrador del sistema, CU14 E1 – Datos inválidos, CU14 E2 – El semillero no pertenece al líder (HTTP 403), CU14 E3 – Otro usuario modificó el semillero después de abrirlo, CU19 Gestionar objetivos del semillero, CU19 A1 – Modificar (+23 more)

### Community 14 - "AuthController API"
Cohesion: 0.10
Nodes (10): AuthController, ProgramController, Authenticate, UserResource, Illuminate\Auth\Middleware\Authenticate, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware (+2 more)

### Community 15 - "Escape HTML y layout"
Cohesion: 0.12
Nodes (22): escapeHtml(), ESCAPES, safeImageSrc(), safeUrl(), bindEvents(), forgotPasswordModule, bindEvents(), render() (+14 more)

### Community 16 - "CU estudiante PWA (CU17)"
Cohesion: 0.09
Nodes (27): CU02 A2 – Token vigente (paso 1), CU07 Administrar facultades, CU07 A1 – Consultar detalle (desde el paso 2), CU07 A2 – Modificar (desde el paso 2), CU07 A3 – Cambiar estado (desde el paso 2), CU07 A4 – Buscar y filtrar (desde el paso 2), CU07 A5 – Actor de solo consulta, CU07 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422) (+19 more)

### Community 17 - "AuthController API"
Cohesion: 0.09
Nodes (6): CoordinatorController, ProposalController, RequestController, App\Http\Controllers\Controller, App\Models\Coordinator, App\Models\Proposal

### Community 18 - "Actores web y CU16"
Cohesion: 0.11
Nodes (24): Actor Administrador del sistema, Actor Administrativo, Actor Líder de semillero, Actor Usuario web (abstracto), API REST (Sanctum, /api/v1), CU16 Consultar semilleros (panel web), CU16 A1 – Líder, CU16 A2 – Administrativo (+16 more)

### Community 19 - "Form Requests de auth"
Cohesion: 0.11
Nodes (7): ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, StoreUserRequest, UpdateUserRequest, Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rule

### Community 20 - "Dependencias Node (build)"
Cohesion: 0.09
Nodes (19): devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, private (+11 more)

### Community 21 - "Arquitectura 2020 CU→módulos"
Cohesion: 0.09
Nodes (23): modules/auditoria/ — CU14, modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores), Arquitectura Derivada — modules/ mapped to casos de uso (CU01-CU14), modules/procesos/ — CU10-CU12 (solicitudes, propuestas, integrantes), modules/semilleros/ — CU08, CU09, CU13, modules/usuarios/ — CU01, components/ — navbar.js, loader.js, core/ — router.js, guards.js, state.js (+15 more)

### Community 22 - "Auditoría y proyectos API"
Cohesion: 0.13
Nodes (5): FacultyController, ProjectController, ProjectMemberController, Controller, Project

### Community 23 - "CU13 Registrar semillero"
Cohesion: 0.14
Nodes (20): Acuerdo 0033 de 2018, CU13 Registrar semillero, CU13 A1 – Guardar como borrador, CU13 E1 – Faltan datos obligatorios, CU13 E2 – Código duplicado, CU13 E3 – Una referencia fue inactivada mientras se diligenciaba, CU13 E4 – Falla de base de datos, CU15 Cambiar estado de semillero (+12 more)

### Community 24 - "Escape HTML y layout"
Cohesion: 0.17
Nodes (15): BOTTOM_NAV, LayoutView(), renderSkeleton(), projectMembersModule, renderMembers(), projectsModule, bindEvents(), loadAndRender() (+7 more)

### Community 25 - "Suscripciones push"
Cohesion: 0.18
Nodes (5): AuditController, Audit, AuditObserver, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 26 - "Notificaciones API"
Cohesion: 0.20
Nodes (5): NotificacionController, Notificacion, NotificacionRead, Minishlink\WebPush\Subscription, Minishlink\WebPush\WebPush

### Community 27 - "Resultados API"
Cohesion: 0.18
Nodes (5): CatController, Cat, MembershipRequest, AppServiceProvider, Illuminate\Support\ServiceProvider

### Community 28 - "CU02 login Google (PWA)"
Cohesion: 0.20
Nodes (16): CU02 login PWA con Google OAuth (dominio institucional, RN04), Decision: lo funcional lo manda el documento, el stack es el real, Google Identity (OAuth 2.0), CU02 Iniciar sesión con cuenta institucional (PWA), CU02 A1 – Primer ingreso: autorización de datos (paso 9), CU02 E1 – Correo fuera del dominio institucional, CU02 E2 – El estudiante cancela en Google, CU02 E3 – Usuario estudiante inactivado por el administrador (+8 more)

### Community 29 - "Manifest PWA"
Cohesion: 0.12
Nodes (15): background_color, categories, description, display, display_override, icons, id, name (+7 more)

### Community 30 - "Tests de autenticación"
Cohesion: 0.14
Nodes (3): Proposal, ProposalSeeder, ProposalCrudTest

### Community 31 - "Changelog 2026-07-28"
Cohesion: 0.13
Nodes (14): Bugs reales encontrados, Changelog — Sesión de validación 2026-07-28, Contexto, Dashboard, Deuda técnica resuelta en esta ronda, Funcionalidad nueva: importación masiva de usuarios, Notificaciones push, Pendiente (no bloqueante) (+6 more)

### Community 32 - "Correos activación/reset"
Cohesion: 0.20
Nodes (7): AccountActivationNotification, CustomResetPasswordNotification, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Bus\Queueable, Illuminate\Notifications\Messages\MailMessage, Illuminate\Notifications\Notification, MailMessage

### Community 33 - "Layout controller"
Cohesion: 0.29
Nodes (10): applyTheme(), _closeSidebar(), initLayoutController(), _openSidebar(), PAGE_TITLES, _registerNavListener(), syncThemeIcon(), initFacultiesController() (+2 more)

### Community 34 - "Notificaciones frontend"
Cohesion: 0.26
Nodes (13): bindEvents(), loadAndRender(), loadTargetValues(), notificationsModule, renderCard(), renderComposer(), renderPage(), ROLE_CAN_SEND (+5 more)

### Community 35 - "Escape HTML y layout"
Cohesion: 0.27
Nodes (10): bindListEvents(), closeSheet(), loadAndRender(), openSheet(), proposalsCache, pwaProposalsModule, renderError(), renderList() (+2 more)

### Community 42 - "CU01 login web y actores"
Cohesion: 0.24
Nodes (10): CU06 Administrar usuarios, CU06 A1 – Consultar detalle, CU06 A2 – Modificar, CU06 A3 – Inactivar / activar, CU06 A4 – Estudiantes, CU06 E1 – Correo ya registrado, CU06 E2 – Falta la referencia de autorización, CU06 E3 – El Administrador intenta inactivarse a sí mismo o al último… (+2 more)

### Community 44 - "composer.json metadatos"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 45 - "Scripts de composer"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 49 - "Auth frontend y app shell"
Cohesion: 0.36
Nodes (6): initLoginController(), clearError(), isValidEmail(), showError(), validateEmail(), validatePassword()

### Community 50 - "Router y guards"
Cohesion: 0.42
Nodes (8): buildSeedbedsByFaculty(), emptyChart(), hideKPI(), initDashboardController(), loadCharts(), loadKPIs(), setKPI(), getUser()

### Community 51 - "Escape HTML y layout"
Cohesion: 0.36
Nodes (8): bindEvents(), loadAndRender(), loadObjectivesForSeedbed(), openDetail(), pwaSeedbedsModule, renderError(), renderList(), renderSkeleton()

### Community 52 - "Importación de usuarios UI"
Cohesion: 0.33
Nodes (8): bindImportModalEvents(), renderRows(), importRowHtml(), openImportModal(), parseImportInput(), ROLES, suggestNameFromEmail(), usersModule

### Community 56 - "Middleware rol y activo"
Cohesion: 0.43
Nodes (4): EnsureUserIsActive, RoleMiddleware, Closure, Symfony\Component\HttpFoundation\Response

### Community 57 - "Dependencias dev PHP"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 59 - "Config composer"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 63 - "Manejo de excepciones"
Cohesion: 0.33
Nodes (4): Handler, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 64 - "Dependencias PHP"
Cohesion: 0.33
Nodes (6): require, laravel/framework, laravel/sanctum, laravel/tinker, minishlink/web-push, php

### Community 67 - "Agente deploy-engineer"
Cohesion: 0.33
Nodes (5): Brechas reales Docker → Hostinger (verifícalas en cada deploy, no las asumas), Entrega, Flujo (el repo es la fuente del código; el servidor no tiene git), Higiene de exposición pública (hallazgos 2026-09-26), Validación en vivo (obligatoria; "se subió" no es "funciona")

### Community 68 - "Agente test-engineer"
Cohesion: 0.33
Nodes (5): Ambientes, Checklist de flujos (corre todos los que toquen el cambio y su código adyacente), Entrega, Honestidad sobre la suite de pruebas, Reglas de aislamiento y limpieza (obligatorias)

### Community 69 - "Autoload PSR-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 70 - "Config de logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 71 - "Agente backend-tester"
Cohesion: 0.40
Nodes (4): Datos y limpieza (obligatorio), Entrega, Integración de lo desplegado (Hostinger), Por cada caso de uso (CU01–CU14)

### Community 72 - "Agente bug-historian"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en este orden, Patrones de bug ya vividos (compara el síntoma nuevo contra estos primero), Reglas

### Community 73 - "Agente code-reviewer"
Cohesion: 0.40
Nodes (4): Checklist específico del proyecto, Entrega, Qué revisar primero, Reglas

### Community 74 - "Agente db-architect"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en orden de confianza, Qué validas, Reglas

### Community 75 - "Agente qa-design-mobile"
Cohesion: 0.40
Nodes (4): Entrega, Qué revisar en la UI móvil, Setup, Validación PWA (automática, este proyecto la necesita)

### Community 76 - "Agente qa-design-web"
Cohesion: 0.40
Nodes (4): Entrega, Falsos positivos ya descartados (no los reportes de nuevo sin evidencia nueva), Qué revisar, Setup

### Community 77 - "Agente use-case-auditor"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes de reglas (en orden), Método (para cada regla), Reglas

### Community 79 - "Agente architecture-advisor"
Cohesion: 0.50
Nodes (3): Arquitectura real (verifícala con `graphify` antes de opinar), Cómo respondes, Reglas

### Community 80 - "Agente architecture-reviewer"
Cohesion: 0.50
Nodes (3): Formato, Recorrido obligatorio (usa `graphify query/path/explain` en cada punto), Trabajo en equipo

### Community 81 - "Agente frontend-tester"
Cohesion: 0.50
Nodes (3): Ambientes y datos, Entrega, Qué pruebas, por cada caso de uso y rol

### Community 82 - "Agente pwa-tester"
Cohesion: 0.50
Nodes (3): Checklist, Datos, Entrega

### Community 83 - "Agente ui-designer"
Cohesion: 0.50
Nodes (3): Alcance, Reglas, Trabajo en equipo

### Community 84 - "Normativa y BPMN"
Cohesion: 0.50
Nodes (4): Acuerdo 0033 de 2018 (sistema de investigación pregrado a distancia UT), BPMN Proceso 1: creación y divulgación de semillero, BPMN Proceso 2: vinculación de estudiante a semillero, Ley 1581 de 2012 / Decreto 1377 de 2013 (protección de datos)

### Community 85 - "Despliegue real Hostinger"
Cohesion: 0.50
Nodes (4): Diagrama de despliegue UML (Nginx+PHP-FPM, cron mongodump, Atlas), Estimación de costos COP 42.637.000 (12 meses), Decisión infra: Hostinger VPS KVM 2 + MongoDB Atlas, Plan de respaldo mongodump diario, RTO<1h RPO 24h

### Community 87 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 88 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **354 isolated node(s):** `CU08 A1 – Consultar detalle (desde el paso 2)`, `CU08 A2 – Modificar (desde el paso 2)`, `CU08 A3 – Cambiar estado (desde el paso 2)`, `CU08 A4 – Buscar y filtrar (desde el paso 2)`, `CU08 A5 – Actor de solo consulta` (+349 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 578 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **78 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Modelo User y políticas` to `Correos activación/reset`, `Seeders de catálogos`, `Modelo User y políticas`, `Tests de autenticación`, `Coordinadores modelo y tests`, `Tests de objetivos`, `Tests de autenticación`, `Programas API`, `Tests de resultados`, `Tests de semilleros`, `Modelo User y políticas`, `Solicitudes modelo y tests`, `Tests de integrantes`, `Suscripciones push`, `Resultados API`, `Tests de autenticación`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **Why does `SecurityRegressionTest` connect `Tests de regresión de seguridad` to `Tests de autenticación`?**
  _High betweenness centrality (0.013) - this node is a cross-community bridge._
- **Why does `Seedbed` connect `Seeders de catálogos` to `Tests de objetivos`, `Tests de autenticación`, `Tests de resultados`, `Tests de semilleros`, `Solicitudes modelo y tests`, `Tests de integrantes`, `Suscripciones push`, `Resultados API`?**
  _High betweenness centrality (0.012) - this node is a cross-community bridge._
- **What connects `CU08 A1 – Consultar detalle (desde el paso 2)`, `CU08 A2 – Modificar (desde el paso 2)`, `CU08 A3 – Cambiar estado (desde el paso 2)` to the rest of the system?**
  _354 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Seeders de catálogos` be split into smaller, more focused modules?**
  _Cohesion score 0.058823529411764705 - nodes in this community are weakly interconnected._
- **Should `Tests de regresión de seguridad` be split into smaller, more focused modules?**
  _Cohesion score 0.06648936170212766 - nodes in this community are weakly interconnected._
- **Should `Modelo User y políticas` be split into smaller, more focused modules?**
  _Cohesion score 0.0666049953746531 - nodes in this community are weakly interconnected._