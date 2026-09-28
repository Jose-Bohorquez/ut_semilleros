# Graph Report - _public_html  (2026-09-28)

## Corpus Check
- 39 files · ~206,930 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1842 nodes · 3538 edges · 213 communities (83 shown, 94 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 114 edges (avg confidence: 0.87)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- CU08 Administrar programas
- Modelo User y políticas
- Escape HTML y layout
- Auth frontend y app shell
- Motor CRUD frontend
- Agentes y flujo de CU
- Rutas API
- Tests de autenticación
- CU14 Modificar semillero
- Tests de semilleros
- CU01 login web y actores
- Modelo de datos documentado
- CU08 Administrar programas
- CU estudiante PWA (CU17)
- CU estudiante PWA (CU17)
- Dependencias Node (build)
- Arquitectura 2020 CU→módulos
- Seeders de catálogos
- Coordinadores modelo y tests
- Rutas API
- Tests de regresión de seguridad
- Notificaciones frontend
- Tests de semilleros
- Auditoría y proyectos API
- Notificaciones API
- Tests de autenticación
- CU02GoogleLoginTest
- Escape HTML y layout
- AuthController API
- SIA asistente
- Form Requests de auth
- SIA asistente
- Tests de autenticación
- Rutas API
- Actores web y CU16
- Manifest PWA
- Suscripciones push
- AuthController API
- Productos API
- Changelog 2026-07-28
- Modelo User y políticas
- Middleware rol y activo
- Form Requests de auth
- Tests de regresión de seguridad
- CU02 login Google (PWA)
- Layout controller
- Objetivos API
- Auth frontend y app shell
- Resultados API
- SIA asistente
- SIA asistente
- Áreas API
- Grupos API
- Tests de objetivos
- Programas API
- Tests de semilleros
- Productos API
- SIA asistente
- Tests de regresión de seguridad
- Migración create_faculties_table.php
- Migración create_users_table.php
- Migración create_users_table.php
- Actores web y CU16
- AuthController API
- AuthController API
- Seeders de catálogos
- composer.json metadatos
- Scripts de composer
- Tests de integrantes
- RF04CatTest
- Auth frontend y app shell
- Importación de usuarios UI
- Modelo User y políticas
- Productos API
- Dependencias dev PHP
- RF16ConsentTest
- Router y guards
- AuthController API
- Modelo User y políticas
- Rutas API
- Rutas API
- Config composer
- Manejo de excepciones
- Resultados API
- Dependencias PHP
- Rutas API
- Tests de autenticación
- Agente deploy-engineer
- Agente test-engineer
- Service worker
- AuthController API
- AuthController API
- Autoload PSR-4
- Config de logging
- Agente backend-tester
- Agente bug-historian
- Agente code-reviewer
- Agente db-architect
- Agente qa-design-mobile
- Agente qa-design-web
- Agente use-case-auditor
- AuthController API
- Test unitario ejemplo
- Agente architecture-advisor
- Agente architecture-reviewer
- Agente frontend-tester
- Agente pwa-tester
- Agente ui-designer
- Normativa y BPMN
- Despliegue real Hostinger
- autoload-dev
- extra
- activation.blade.php
- reset-password.blade.php
- console
- Auth frontend y app shell
- README y convenciones
- Despliegue según README
- dev-setup.sh
- Rutas API
- Rutas API
- Rutas API
- Rutas API
- Rutas API
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
1. `User` - 124 edges
2. `apiFetch()` - 46 edges
3. `escapeHtml()` - 42 edges
4. `Program` - 33 edges
5. `RN07 Toda escritura/login genera auditoría inmutable` - 32 edges
6. `RNF03 Seguridad web y móvil` - 29 edges
7. `Seedbed` - 28 edges
8. `LayoutView()` - 28 edges
9. `RN01 No eliminación física, baja por estado inactivo` - 28 edges
10. `createCrudModule()` - 28 edges

## Surprising Connections (you probably didn't know these)
- `renderSkeleton()` --calls--> `LayoutView()`  [EXTRACTED]
  modules/notifications/notifications.module.js → layout/layout.view.js
- `api/.env.example ausente por comentario al final de linea en .gitignore` --conceptually_related_to--> `Prohibiciones derivadas de incidentes reales`  [INFERRED]
  docs/CHANGELOG.md → CLAUDE.md
- `CU02 login PWA con Google OAuth (dominio institucional, RN04)` --references--> `CU02 Iniciar sesión con cuenta institucional (PWA)`  [EXTRACTED]
  CLAUDE.md → docs/especificacion/Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md
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

## Communities (213 total, 94 thin omitted)

### Community 0 - "CU08 Administrar programas"
Cohesion: 0.04
Nodes (61): Servidor de correo SMTP, CU04 E4 – Falla del servidor de correo, CU06 Administrar usuarios, CU06 A1 – Consultar detalle, CU06 A2 – Modificar, CU06 A3 – Inactivar / activar, CU06 A4 – Estudiantes, CU06 E1 – Correo ya registrado (+53 more)

### Community 1 - "Modelo User y políticas"
Cohesion: 0.05
Nodes (12): User, UserPolicy, AreaCrudTest, AuditTest, FacultyCrudTest, GroupCrudTest, ProposalCrudTest, App\Notifications\CustomResetPasswordNotification (+4 more)

### Community 2 - "Escape HTML y layout"
Cohesion: 0.08
Nodes (42): escapeHtml(), ESCAPES, safeImageSrc(), safeUrl(), BOTTOM_NAV, LayoutView(), bindEvents(), forgotPasswordModule (+34 more)

### Community 3 - "Auth frontend y app shell"
Cohesion: 0.09
Nodes (45): PUBLIC_PATHS, initGoogleLogin(), loadGis(), startSession(), login(), logout(), consentModule, endLocalSession() (+37 more)

### Community 4 - "Motor CRUD frontend"
Cohesion: 0.08
Nodes (34): createCrudModule(), bindEvents(), clearFieldError(), create(), init(), isValidEmail(), renderForm(), renderTable() (+26 more)

### Community 5 - "Agentes y flujo de CU"
Cohesion: 0.05
Nodes (49): Agentes .claude/agents (use-case-auditor, backend/frontend/pwa-tester, architecture-reviewer...), Cache CDN Hostinger bajada a 5 min + CACHE_NAME semilleros-v13, CU02 login PWA con Google OAuth (dominio institucional, RN04), Decision: lo funcional lo manda el documento, el stack es el real, Despliegue real en Hostinger (alias htg, ~/domains/ut-edu.online/public_html), Diferencias conocidas a cerrar (RN10, RN06, RN02/RN03, RN08, RF15/CU28, RF16/RN09), Entorno local Docker aislado de produccion, Flujo de validación de casos de uso en equipo (docs/qa/) (+41 more)

### Community 6 - "Rutas API"
Cohesion: 0.07
Nodes (19): UserController, AccountActivationNotification, CustomResetPasswordNotification, MailBrand, AccountActivationTest, User, App\Http\Requests\User\StoreUserRequest, App\Http\Requests\User\UpdateUserRequest (+11 more)

### Community 7 - "Tests de autenticación"
Cohesion: 0.11
Nodes (17): PasswordResetEndToEndTest, CU03LogoutTest, App\Models\Faculty, App\Models\Seedbed, App\Models\SiaConversation, App\Models\SiaMessage, App\Models\User, App\Services\Sia\GroqKeyPool (+9 more)

### Community 8 - "CU14 Modificar semillero"
Cohesion: 0.07
Nodes (45): Actor Líder de semillero, CU14 Modificar semillero, CU14 A1 – Gestionar objetivos, resultados o integrantes, CU14 A2 – Administrador del sistema, CU14 E1 – Datos inválidos, CU14 E2 – El semillero no pertenece al líder (HTTP 403), CU14 E3 – Otro usuario modificó el semillero después de abrirlo, CU15 Cambiar estado de semillero (+37 more)

### Community 9 - "Tests de semilleros"
Cohesion: 0.07
Nodes (6): Faculty, Program, ProgramSeeder, RequestCrudTest, ResultCrudTest, RF02FacultyTest

### Community 10 - "CU01 login web y actores"
Cohesion: 0.12
Nodes (35): api/.env.example ausente por comentario al final de linea en .gitignore, Sesion 2026-09-28: validacion CU01, RF01, RNF01, Actor Usuario (abstracto), CU01 Iniciar sesión en el panel web, CU01 A1 – Olvidó la contraseña (paso 2), CU01 A2 – Recordarme (paso 3), CU01 A3 – Ruta solicitada previamente (paso 6), CU01 E1 – Campos vacíos o correo mal formado (+27 more)

### Community 11 - "Modelo de datos documentado"
Cohesion: 0.08
Nodes (33): Aggregations MongoDB A1–A5 (semilleros por facultad, solicitudes por estado, propuestas por área, integrantes, auditoría), API REST /api/v1 con Laravel Sanctum, AuditObserver / colección logs solo-inserción (RF14, CU14), Hash de contraseñas bcrypt cost 12 (cast hashed), Casos de uso CU01–CU14 (diseño 2020), Despliegue piloto CAT Kennedy (Bogotá), Catálogos Faculty, Career, Cat, Area, Group, Coordinator, Documentación Técnica SemillerosUT (+25 more)

### Community 12 - "CU08 Administrar programas"
Cohesion: 0.09
Nodes (32): CU07 Administrar facultades, CU07 A1 – Consultar detalle (desde el paso 2), CU07 A2 – Modificar (desde el paso 2), CU07 A3 – Cambiar estado (desde el paso 2), CU07 A4 – Buscar y filtrar (desde el paso 2), CU07 A5 – Actor de solo consulta, CU07 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU07 E2 – Código de la facultad ya registrado (+24 more)

### Community 13 - "CU estudiante PWA (CU17)"
Cohesion: 0.12
Nodes (28): Acuerdo 0033 de 2018, CU13 Registrar semillero, CU13 A1 – Guardar como borrador, CU13 E1 – Faltan datos obligatorios, CU13 E3 – Una referencia fue inactivada mientras se diligenciaba, CU13 E4 – Falla de base de datos, CU18 Consultar detalle de semillero (PWA), CU18 A1 – Ser miembro (paso 2) (+20 more)

### Community 14 - "CU estudiante PWA (CU17)"
Cohesion: 0.13
Nodes (24): Actor Estudiante, CU02 A2 – Token vigente (paso 1), CU17 Consultar semilleros por facultad (PWA), CU17 A1 – Buscar, CU17 A2 – Sin conexión, CU17 A3 – Proponer idea, CU17 E1 – Sin conexión y sin caché previa, CU17 E2 – No hay semilleros activos (+16 more)

### Community 15 - "Dependencias Node (build)"
Cohesion: 0.09
Nodes (19): devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, private (+11 more)

### Community 16 - "Arquitectura 2020 CU→módulos"
Cohesion: 0.09
Nodes (23): modules/auditoria/ — CU14, modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores), Arquitectura Derivada — modules/ mapped to casos de uso (CU01-CU14), modules/procesos/ — CU10-CU12 (solicitudes, propuestas, integrantes), modules/semilleros/ — CU08, CU09, CU13, modules/usuarios/ — CU01, components/ — navbar.js, loader.js, core/ — router.js, guards.js, state.js (+15 more)

### Community 17 - "Seeders de catálogos"
Cohesion: 0.12
Nodes (8): Proposal, CatSeeder, CoordinatorSeeder, DatabaseSeeder, FacultySeeder, ProposalSeeder, UserSeeder, Illuminate\Database\Seeder

### Community 18 - "Coordinadores modelo y tests"
Cohesion: 0.11
Nodes (3): Coordinator, CoordinatorCrudTest, RNF03SecurityTest

### Community 19 - "Rutas API"
Cohesion: 0.10
Nodes (18): App\Http\Controllers\Api\AreaController, App\Http\Controllers\Api\AuditController, App\Http\Controllers\Api\CatController, App\Http\Controllers\Api\CoordinatorController, App\Http\Controllers\Api\GroupController, App\Http\Controllers\Api\NotificacionController, App\Http\Controllers\Api\ObjectiveController, App\Http\Controllers\Api\ProductController (+10 more)

### Community 20 - "Tests de regresión de seguridad"
Cohesion: 0.19
Nodes (3): AuthTest, CU01LoginTest, User

### Community 21 - "Notificaciones frontend"
Cohesion: 0.17
Nodes (17): refreshBadge(), startBadgePolling(), updateBellBadge(), bindEvents(), loadAndRender(), loadTargetValues(), notificationsModule, renderCard() (+9 more)

### Community 22 - "Tests de semilleros"
Cohesion: 0.14
Nodes (5): SeedbedMemberController, MembershipRequest, Seedbed, RequestSeeder, SeedbedSeeder

### Community 23 - "Auditoría y proyectos API"
Cohesion: 0.18
Nodes (5): AuditController, ProjectController, ProjectMemberController, Controller, Project

### Community 24 - "Notificaciones API"
Cohesion: 0.20
Nodes (5): NotificacionController, Notificacion, NotificacionRead, Minishlink\WebPush\Subscription, Minishlink\WebPush\WebPush

### Community 25 - "Tests de autenticación"
Cohesion: 0.24
Nodes (3): CU04ForgotPasswordTest, User, Password

### Community 27 - "Escape HTML y layout"
Cohesion: 0.19
Nodes (13): PASSWORD_HINT, passwordPolicyError(), bindEvents(), resetPasswordModule, bindEvents(), clearErrors(), compressImage(), pwaProfileModule (+5 more)

### Community 28 - "AuthController API"
Cohesion: 0.15
Nodes (8): CoordinatorController, App\Http\Controllers\Controller, App\Models\Audit, App\Models\Coordinator, App\Services\Auth\GoogleAuthException, App\Services\Auth\GoogleIdTokenVerifier, Illuminate\Validation\Rule, Illuminate\Validation\ValidationException

### Community 29 - "SIA asistente"
Cohesion: 0.18
Nodes (3): SiaController, SiaConversation, SiaSetting

### Community 30 - "Form Requests de auth"
Cohesion: 0.16
Nodes (4): StoreUserRequest, UpdateUserRequest, PasswordPolicy, Illuminate\Validation\Rules\Password

### Community 32 - "Tests de autenticación"
Cohesion: 0.14
Nodes (5): PasswordResetTest, ExampleTest, UserManagementTest, TestCase, Illuminate\Foundation\Testing\TestCase

### Community 33 - "Rutas API"
Cohesion: 0.15
Nodes (5): SiaKnowledge, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Log, Illuminate\Support\Str

### Community 34 - "Actores web y CU16"
Cohesion: 0.15
Nodes (16): API REST (Sanctum, /api/v1), ISO/IEC 25010, Laravel Sanctum, MongoDB ($jsonSchema estricto), OpenAPI 3 / Swagger, Panel web administrativo, PWA instalable para estudiantes, RNF04 Manuales de usuario, técnico e instalación (+8 more)

### Community 35 - "Manifest PWA"
Cohesion: 0.12
Nodes (15): background_color, categories, description, display, display_override, icons, id, name (+7 more)

### Community 36 - "Suscripciones push"
Cohesion: 0.25
Nodes (4): Audit, AuditObserver, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 37 - "AuthController API"
Cohesion: 0.26
Nodes (4): AuthController, User, DateTimeInterface, Illuminate\Http\Request

### Community 38 - "Productos API"
Cohesion: 0.15
Nodes (11): AppServiceProvider, App\Models\Area, App\Models\Cat, App\Models\Group, App\Models\MembershipRequest, App\Models\Objective, App\Models\Product, App\Models\Project (+3 more)

### Community 39 - "Changelog 2026-07-28"
Cohesion: 0.13
Nodes (14): Bugs reales encontrados, Changelog — Sesión de validación 2026-07-28, Contexto, Dashboard, Deuda técnica resuelta en esta ronda, Funcionalidad nueva: importación masiva de usuarios, Notificaciones push, Pendiente (no bloqueante) (+6 more)

### Community 41 - "Middleware rol y activo"
Cohesion: 0.26
Nodes (6): EnsureDataConsent, EnsureUserIsActive, RoleMiddleware, SecurityHeaders, Closure, Symfony\Component\HttpFoundation\Response

### Community 42 - "Form Requests de auth"
Cohesion: 0.18
Nodes (4): ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, Illuminate\Foundation\Http\FormRequest

### Community 44 - "CU02 login Google (PWA)"
Cohesion: 0.24
Nodes (14): Google Identity (OAuth 2.0), CU02 Iniciar sesión con cuenta institucional (PWA), CU02 A1 – Primer ingreso: autorización de datos (paso 9), CU02 E1 – Correo fuera del dominio institucional, CU02 E2 – El estudiante cancela en Google, CU02 E3 – Usuario estudiante inactivado por el administrador, CU02 E4 – Sin conexión, CU02 E5 – Google no responde o devuelve error (+6 more)

### Community 45 - "Layout controller"
Cohesion: 0.29
Nodes (10): applyTheme(), _closeSidebar(), initLayoutController(), _openSidebar(), PAGE_TITLES, _registerNavListener(), syncThemeIcon(), initFacultiesController() (+2 more)

### Community 46 - "Objetivos API"
Cohesion: 0.23
Nodes (3): ObjectiveController, Objective, ObjectiveSeeder

### Community 47 - "Auth frontend y app shell"
Cohesion: 0.27
Nodes (11): FACES, format(), mountSia(), close(), finish(), open(), renderChat(), renderRating() (+3 more)

### Community 48 - "Resultados API"
Cohesion: 0.24
Nodes (3): ResultController, Result, ResultSeeder

### Community 49 - "SIA asistente"
Cohesion: 0.23
Nodes (3): SiaMessage, GroqKeyPool, Illuminate\Support\Facades\Cache

### Community 51 - "Áreas API"
Cohesion: 0.27
Nodes (3): AreaController, Area, AreaSeeder

### Community 52 - "Grupos API"
Cohesion: 0.27
Nodes (3): GroupController, Group, GroupSeeder

### Community 57 - "SIA asistente"
Cohesion: 0.35
Nodes (10): bind(), FACE, fmtDate(), kpi(), LIMIT_LABELS, md(), openConversation(), pct() (+2 more)

### Community 58 - "Tests de regresión de seguridad"
Cohesion: 0.20
Nodes (4): GoogleAuthException, GoogleIdTokenVerifier, SiaUnavailableException, RuntimeException

### Community 62 - "Actores web y CU16"
Cohesion: 0.24
Nodes (10): Actor Administrador del sistema, Actor Administrativo, Actor Usuario web (abstracto), CU27 Evaluar propuestas, CU27 A1 – Líder de semillero, CU27 A2 – Reporte por área, CU27 E1 – Observación vacía al archivar, CU27 E2 – Actor sin permiso para cambiar estado (HTTP 403) (+2 more)

### Community 65 - "Seeders de catálogos"
Cohesion: 0.28
Nodes (3): Closure, SeedbedController, App\Models\Program

### Community 66 - "composer.json metadatos"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 67 - "Scripts de composer"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 70 - "Auth frontend y app shell"
Cohesion: 0.36
Nodes (6): initLoginController(), clearError(), isValidEmail(), showError(), validateEmail(), validatePassword()

### Community 71 - "Importación de usuarios UI"
Cohesion: 0.33
Nodes (8): bindImportModalEvents(), renderRows(), importRowHtml(), openImportModal(), parseImportInput(), ROLES, suggestNameFromEmail(), usersModule

### Community 74 - "Dependencias dev PHP"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 76 - "Router y guards"
Cohesion: 0.46
Nodes (7): buildSeedbedsByFaculty(), emptyChart(), hideKPI(), initDashboardController(), loadCharts(), loadKPIs(), setKPI()

### Community 81 - "Config composer"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 82 - "Manejo de excepciones"
Cohesion: 0.33
Nodes (4): Handler, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 84 - "Dependencias PHP"
Cohesion: 0.33
Nodes (6): require, laravel/framework, laravel/sanctum, laravel/tinker, minishlink/web-push, php

### Community 85 - "Rutas API"
Cohesion: 0.47
Nodes (5): down(), isEncrypted(), up(), Illuminate\Support\Facades\Crypt, Illuminate\Support\Facades\DB

### Community 87 - "Agente deploy-engineer"
Cohesion: 0.33
Nodes (5): Brechas reales Docker → Hostinger (verifícalas en cada deploy, no las asumas), Entrega, Flujo (el repo es la fuente del código; el servidor no tiene git), Higiene de exposición pública (hallazgos 2026-09-26), Validación en vivo (obligatoria; "se subió" no es "funciona")

### Community 88 - "Agente test-engineer"
Cohesion: 0.33
Nodes (5): Ambientes, Checklist de flujos (corre todos los que toquen el cambio y su código adyacente), Entrega, Honestidad sobre la suite de pruebas, Reglas de aislamiento y limpieza (obligatorias)

### Community 91 - "AuthController API"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 92 - "Autoload PSR-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 93 - "Config de logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 94 - "Agente backend-tester"
Cohesion: 0.40
Nodes (4): Datos y limpieza (obligatorio), Entrega, Integración de lo desplegado (Hostinger), Por cada caso de uso (CU01–CU14)

### Community 95 - "Agente bug-historian"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en este orden, Patrones de bug ya vividos (compara el síntoma nuevo contra estos primero), Reglas

### Community 96 - "Agente code-reviewer"
Cohesion: 0.40
Nodes (4): Checklist específico del proyecto, Entrega, Qué revisar primero, Reglas

### Community 97 - "Agente db-architect"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en orden de confianza, Qué validas, Reglas

### Community 98 - "Agente qa-design-mobile"
Cohesion: 0.40
Nodes (4): Entrega, Qué revisar en la UI móvil, Setup, Validación PWA (automática, este proyecto la necesita)

### Community 99 - "Agente qa-design-web"
Cohesion: 0.40
Nodes (4): Entrega, Falsos positivos ya descartados (no los reportes de nuevo sin evidencia nueva), Qué revisar, Setup

### Community 100 - "Agente use-case-auditor"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes de reglas (en orden), Método (para cada regla), Reglas

### Community 103 - "Agente architecture-advisor"
Cohesion: 0.50
Nodes (3): Arquitectura real (verifícala con `graphify` antes de opinar), Cómo respondes, Reglas

### Community 104 - "Agente architecture-reviewer"
Cohesion: 0.50
Nodes (3): Formato, Recorrido obligatorio (usa `graphify query/path/explain` en cada punto), Trabajo en equipo

### Community 105 - "Agente frontend-tester"
Cohesion: 0.50
Nodes (3): Ambientes y datos, Entrega, Qué pruebas, por cada caso de uso y rol

### Community 106 - "Agente pwa-tester"
Cohesion: 0.50
Nodes (3): Checklist, Datos, Entrega

### Community 107 - "Agente ui-designer"
Cohesion: 0.50
Nodes (3): Alcance, Reglas, Trabajo en equipo

### Community 108 - "Normativa y BPMN"
Cohesion: 0.50
Nodes (4): Acuerdo 0033 de 2018 (sistema de investigación pregrado a distancia UT), BPMN Proceso 1: creación y divulgación de semillero, BPMN Proceso 2: vinculación de estudiante a semillero, Ley 1581 de 2012 / Decreto 1377 de 2013 (protección de datos)

### Community 109 - "Despliegue real Hostinger"
Cohesion: 0.50
Nodes (4): Diagrama de despliegue UML (Nginx+PHP-FPM, cron mongodump, Atlas), Estimación de costos COP 42.637.000 (12 meses), Decisión infra: Hostinger VPS KVM 2 + MongoDB Atlas, Plan de respaldo mongodump diario, RTO<1h RPO 24h

### Community 110 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 111 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **368 isolated node(s):** `CU06 A1 – Consultar detalle`, `CU06 A2 – Modificar`, `CU06 A4 – Estudiantes`, `CU06 E3 – El Administrador intenta inactivarse a sí mismo o al último…`, `CU09 A1 – Consultar detalle (desde el paso 2)` (+363 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 688 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **94 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Modelo User y políticas` to `Tests de autenticación`, `Tests de integrantes`, `Tests de autenticación`, `Tests de semilleros`, `RF16ConsentTest`, `Seeders de catálogos`, `Coordinadores modelo y tests`, `Tests de objetivos`, `Tests de semilleros`, `Tests de semilleros`, `CU02GoogleLoginTest`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `Program` connect `Tests de semilleros` to `Suscripciones push`, `Tests de integrantes`, `Productos API`, `Tests de autenticación`, `AuthController API`, `Tests de objetivos`, `Tests de semilleros`, `Programas API`, `Tests de semilleros`, `Productos API`, `AuthController API`?**
  _High betweenness centrality (0.023) - this node is a cross-community bridge._
- **Why does `Faculty` connect `Tests de semilleros` to `Modelo User y políticas`, `Suscripciones push`, `Tests de integrantes`, `Tests de autenticación`, `Modelo User y políticas`, `Seeders de catálogos`, `Tests de objetivos`, `Tests de semilleros`, `AuthController API`?**
  _High betweenness centrality (0.014) - this node is a cross-community bridge._
- **What connects `CU06 A1 – Consultar detalle`, `CU06 A2 – Modificar`, `CU06 A4 – Estudiantes` to the rest of the system?**
  _368 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `CU08 Administrar programas` be split into smaller, more focused modules?**
  _Cohesion score 0.04371584699453552 - nodes in this community are weakly interconnected._
- **Should `Modelo User y políticas` be split into smaller, more focused modules?**
  _Cohesion score 0.053185271770894216 - nodes in this community are weakly interconnected._
- **Should `Escape HTML y layout` be split into smaller, more focused modules?**
  _Cohesion score 0.08455625436757512 - nodes in this community are weakly interconnected._