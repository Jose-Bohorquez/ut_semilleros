# Graph Report - _public_html  (2026-09-27)

## Corpus Check
- 28 files · ~173,705 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1421 nodes · 2721 edges · 150 communities (84 shown, 35 thin omitted)
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 135 edges (avg confidence: 0.9)
- Token cost: 216,482 input · 17,000 output

## Community Hubs (Navigation)
- Escape HTML y layout
- Modelo User y políticas
- Tests de autenticación
- CU01 login web y actores
- CU estudiante PWA (CU17)
- Motor CRUD frontend
- CU09 Administrar CAT
- Auth frontend y app shell
- CU14 Modificar semillero
- Actores web y CU16
- AuthController API
- Router y guards
- Form Requests de auth
- Dependencias Node (build)
- Facultades API
- Programas API
- Seeders de catálogos
- Rutas API
- Auditoría y proyectos API
- Notificaciones API
- Layout controller
- Suscripciones push
- Semilleros e integrantes API
- Usuarios API e importación
- Propuestas modelo y tests
- Arquitectura 2020 CU→módulos
- Notificaciones frontend
- Manifest PWA
- Resultados API
- Tests de regresión de seguridad
- Stack documentado y RNF
- Correos activación/reset
- Solicitudes modelo y tests
- README y convenciones
- Modelo de datos documentado
- CU02 login Google (PWA)
- CU07 Administrar facultades
- Auditoría modelo y tests
- Coordinadores modelo y tests
- Config Laravel y factories
- CU11 Administrar grupos
- Tests de objetivos
- Decisiones de arquitectura doc
- Objetivos API
- Tests de resultados
- Tests de semilleros
- Tests de integrantes
- Docker compose local
- CU08 Administrar programas
- CU10 Administrar áreas
- Shell index.html y CDNs
- Solicitudes API
- composer.json metadatos
- Scripts de composer
- Semilleros PWA
- Importación de usuarios UI
- Áreas API
- CAT API
- Coordinadores API
- Grupos API
- Productos API
- Middleware rol y activo
- Dependencias dev PHP
- Bocetos de arquitectura
- Decisión doc vs stack real
- Despliegue real Hostinger
- Changelog 2026-07-28
- CU13 Registrar semillero
- Imports de tests
- ProposalController
- Config composer
- Migración create_users_table.php
- Migración create_cache_table.php
- Migración create_programs_table.php
- Changelog 2026-08-31
- Login controller UI
- Manejo de excepciones
- Dependencias PHP
- Agente deploy-engineer
- Agente test-engineer
- Contexto del proyecto
- Autoload PSR-4
- Config de logging
- Agentes y flujo de CU
- Agente backend-tester
- Agente bug-historian
- Agente code-reviewer
- Agente db-architect
- Agente qa-design-mobile
- Agente qa-design-web
- Agente use-case-auditor
- Roles, HU y Scrum
- Test unitario ejemplo
- Agente architecture-advisor
- Agente architecture-reviewer
- Agente frontend-tester
- Agente pwa-tester
- Agente ui-designer
- Normativa y BPMN
- Despliegue según README
- Service worker
- autoload-dev
- extra
- ObjectiveSeeder
- RequestSeeder
- ResultSeeder
- SeedbedSeeder
- console
- Entorno Docker local aislado (do
- Apple Touch Icon 152x152 (PWA br
- Apple Touch Icon 167x167 (PWA br
- Apple Touch Icon 180x180 (PWA br
- Login page background image
- Login page logo (Universidad del
- PWA icon 192x192 (standard brand
- PWA maskable icon 192x192 (Andro
- PWA icon 512x512 (standard brand
- PWA maskable icon 512x512 (Andro
- Pending non-blocking improvement

## God Nodes (most connected - your core abstractions)
1. `User` - 144 edges
2. `escapeHtml()` - 51 edges
3. `TestCase` - 40 edges
4. `Seedbed` - 35 edges
5. `apiFetch()` - 35 edges
6. `Controller` - 33 edges
7. `RN07 Toda escritura/login genera auditoría inmutable` - 32 edges
8. `Faculty` - 30 edges
9. `LayoutView()` - 29 edges
10. `createCrudModule()` - 28 edges

## Surprising Connections (you probably didn't know these)
- `Casos de uso vigentes CU01–CU30` --semantically_similar_to--> `Casos de uso CU01–CU14 (diseño 2020)`  [INFERRED] [semantically similar]
  CLAUDE.md → docs/especificacion/Documentacion_Tecnica_SemillerosUT.md
- `Despliegue real Hostinger compartido (alias htg, PHP 8.2.33, sin Node)` --semantically_similar_to--> `Decisión infra: Hostinger VPS KVM 2 + MongoDB Atlas`  [INFERRED] [semantically similar]
  CLAUDE.md → docs/especificacion/Documentacion_Tecnica_SemillerosUT.md
- `api service — Laravel backend, exposed on :8000` --runs--> `Laravel 12 + PHP 8.4 backend`  [INFERRED]
  docker-compose.yml → README.md
- `db service — mysql:9.0` --runs--> `MySQL 9.0 database`  [INFERRED]
  docker-compose.yml → README.md
- `app.js loaded as ES module entry point` --instance_of--> `Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)`  [INFERRED]
  index.html → docs/Arquitectura propuesta.txt

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Casos de uso que incluyen Registrar auditoría (CU29)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu01, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu02, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu04, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu06, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu13, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu14, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu15, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu22, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu24, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu25, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu27, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu29, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn07 [EXTRACTED 1.00]
- **Flujo de vinculación estudiante-semillero** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu17, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu18, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu22, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu23, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu24, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu21, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rf10, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn05 [EXTRACTED 1.00]
- **Catálogos administrados (CRUD con cambio de estado)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu07, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu08, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu09, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu10, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu11, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu12, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn01, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn08 [INFERRED 0.85]
- **Modelo de dominio documental SemillerosUT** — docs_especificacion_documentacion_tecnica_semillerosut_hotbed, docs_especificacion_documentacion_tecnica_semillerosut_membership_request, docs_especificacion_documentacion_tecnica_semillerosut_proposal, docs_especificacion_documentacion_tecnica_semillerosut_member, docs_especificacion_documentacion_tecnica_semillerosut_user_model, docs_especificacion_documentacion_tecnica_semillerosut_log_model, docs_especificacion_documentacion_tecnica_semillerosut_catalogos [EXTRACTED 1.00]
- **Capas de seguridad de datos (schema validation, bcrypt, Sanctum, auditoría)** — docs_especificacion_documentacion_tecnica_semillerosut_mongodb_schema_validation, docs_especificacion_documentacion_tecnica_semillerosut_bcrypt_hashing, docs_especificacion_documentacion_tecnica_semillerosut_api_rest_sanctum, docs_especificacion_documentacion_tecnica_semillerosut_auditoria_observer [EXTRACTED 1.00]
- **Divergencia entre stack documentado y stack real** — claude_decision_funcional_vs_stack, docs_especificacion_documentacion_tecnica_semillerosut_stack_adaptado, claude_despliegue_hostinger_compartido, docs_especificacion_documentacion_tecnica_semillerosut_infra_hostinger_vps_atlas [INFERRED 0.85]
- **Excepciones de autorización HTTP 403 (control por rol / RN06)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu07_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu08_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu09_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu10_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu11_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu12_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu14_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu15_e1, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu19_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu20_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu21_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu24_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu27_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn06 [EXTRACTED 1.00]
- **Límites de tasa HTTP 429 (login, recuperación, propuestas)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu01_e4, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu04_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu25_e3, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn14, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn15 [EXTRACTED 1.00]
- **Validación de código duplicado en catálogos (RN08)** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu07_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu08_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu09_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu10_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu11_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_cu12_e2, docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_rn08 [INFERRED 0.95]

## Communities (150 total, 35 thin omitted)

### Community 0 - "Escape HTML y layout"
Cohesion: 0.09
Nodes (40): escapeHtml(), ESCAPES, safeImageSrc(), BOTTOM_NAV, LayoutView(), bindEvents(), render(), resetPasswordModule (+32 more)

### Community 1 - "Modelo User y políticas"
Cohesion: 0.07
Nodes (9): User, UserPolicy, AreaCrudTest, CatCrudTest, GroupCrudTest, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable (+1 more)

### Community 2 - "Tests de autenticación"
Cohesion: 0.08
Nodes (11): AuthTest, PasswordResetEndToEndTest, PasswordResetTest, ExampleTest, UserCrudTest, UserManagementTest, TestCase, Illuminate\Foundation\Testing\RefreshDatabase (+3 more)

### Community 3 - "CU01 login web y actores"
Cohesion: 0.08
Nodes (41): Servidor de correo SMTP, Actor Usuario (abstracto), CU01 Iniciar sesión en el panel web, CU01 A1 – Olvidó la contraseña (paso 2), CU01 A2 – Recordarme (paso 3), CU01 A3 – Ruta solicitada previamente (paso 6), CU01 E1 – Campos vacíos o correo mal formado, CU01 E2 – Credenciales incorrectas (+33 more)

### Community 4 - "CU estudiante PWA (CU17)"
Cohesion: 0.09
Nodes (40): Actor Estudiante, CU02 A2 – Token vigente (paso 1), CU13 E3 – Una referencia fue inactivada mientras se diligenciaba, CU17 Consultar semilleros por facultad (PWA), CU17 A1 – Buscar, CU17 A2 – Sin conexión, CU17 A3 – Proponer idea, CU17 E1 – Sin conexión y sin caché previa (+32 more)

### Community 5 - "Motor CRUD frontend"
Cohesion: 0.09
Nodes (25): createCrudModule(), bindEvents(), clearFieldError(), create(), init(), isValidEmail(), renderForm(), renderTable() (+17 more)

### Community 6 - "CU09 Administrar CAT"
Cohesion: 0.08
Nodes (36): CU09 Administrar CAT, CU09 A1 – Consultar detalle (desde el paso 2), CU09 A2 – Modificar (desde el paso 2), CU09 A3 – Cambiar estado (desde el paso 2), CU09 A4 – Buscar y filtrar (desde el paso 2), CU09 A5 – Actor de solo consulta, CU09 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU09 E3 – El actor intenta una operación no permitida para su rol (HTTP 403) (+28 more)

### Community 7 - "Auth frontend y app shell"
Cohesion: 0.14
Nodes (26): login(), logout(), refreshBadge(), startBadgePolling(), updateBellBadge(), apiFetch(), buildUrl(), clearAuthSession() (+18 more)

### Community 8 - "CU14 Modificar semillero"
Cohesion: 0.10
Nodes (31): CU06 A3 – Inactivar / activar, CU14 Modificar semillero, CU14 A1 – Gestionar objetivos, resultados o integrantes, CU14 A2 – Administrador del sistema, CU14 E1 – Datos inválidos, CU14 E2 – El semillero no pertenece al líder (HTTP 403), CU14 E3 – Otro usuario modificó el semillero después de abrirlo, CU15 Cambiar estado de semillero (+23 more)

### Community 9 - "Actores web y CU16"
Cohesion: 0.09
Nodes (29): Actor Administrador del sistema, Actor Administrativo, Actor Líder de semillero, Actor Usuario web (abstracto), CU16 Consultar semilleros (panel web), CU16 A1 – Líder, CU16 A2 – Administrativo, CU16 E1 – Sin resultados (+21 more)

### Community 10 - "AuthController API"
Cohesion: 0.13
Nodes (10): AuthController, Authenticate, UserResource, Illuminate\Auth\Middleware\Authenticate, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request (+2 more)

### Community 11 - "Router y guards"
Cohesion: 0.13
Nodes (16): requireAuth(), requireRole(), navigateTo(), renderRoute(), routes, bindEvents(), forgotPasswordModule, buildSeedbedsByFaculty() (+8 more)

### Community 12 - "Form Requests de auth"
Cohesion: 0.11
Nodes (7): ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, StoreUserRequest, UpdateUserRequest, Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rule

### Community 13 - "Dependencias Node (build)"
Cohesion: 0.09
Nodes (19): devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, private (+11 more)

### Community 14 - "Facultades API"
Cohesion: 0.12
Nodes (4): FacultyController, Faculty, ProgramSeeder, FacultyCrudTest

### Community 15 - "Programas API"
Cohesion: 0.16
Nodes (3): ProgramController, Program, ProgramCrudTest

### Community 16 - "Seeders de catálogos"
Cohesion: 0.14
Nodes (7): AreaSeeder, CatSeeder, CoordinatorSeeder, DatabaseSeeder, FacultySeeder, GroupSeeder, Illuminate\Database\Seeder

### Community 17 - "Rutas API"
Cohesion: 0.11
Nodes (17): App\Http\Controllers\Api\AreaController, App\Http\Controllers\Api\AuditController, App\Http\Controllers\Api\AuthController, App\Http\Controllers\Api\CatController, App\Http\Controllers\Api\FacultyController, App\Http\Controllers\Api\GroupController, App\Http\Controllers\Api\NotificacionController, App\Http\Controllers\Api\ObjectiveController (+9 more)

### Community 18 - "Auditoría y proyectos API"
Cohesion: 0.18
Nodes (5): AuditController, ProjectController, ProjectMemberController, Controller, Project

### Community 19 - "Notificaciones API"
Cohesion: 0.20
Nodes (5): NotificacionController, Notificacion, NotificacionRead, Minishlink\WebPush\Subscription, Minishlink\WebPush\WebPush

### Community 20 - "Layout controller"
Cohesion: 0.21
Nodes (12): applyTheme(), _closeSidebar(), initLayoutController(), _openSidebar(), PAGE_TITLES, _registerNavListener(), syncThemeIcon(), initFacultiesController() (+4 more)

### Community 21 - "Suscripciones push"
Cohesion: 0.23
Nodes (5): PushSubscriptionController, PushSubscription, AuditObserver, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 22 - "Semilleros e integrantes API"
Cohesion: 0.18
Nodes (3): SeedbedController, SeedbedMemberController, Seedbed

### Community 23 - "Usuarios API e importación"
Cohesion: 0.21
Nodes (8): UserController, App\Http\Requests\User\StoreUserRequest, App\Http\Requests\User\UpdateUserRequest, App\Http\Resources\UserResource, App\Models\User, App\Notifications\AccountActivationNotification, Illuminate\Http\JsonResponse, Illuminate\Support\Facades\Validator

### Community 24 - "Propuestas modelo y tests"
Cohesion: 0.14
Nodes (3): Proposal, ProposalSeeder, ProposalCrudTest

### Community 25 - "Arquitectura 2020 CU→módulos"
Cohesion: 0.14
Nodes (16): modules/auditoria/ — CU14, modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores), Arquitectura Derivada — modules/ mapped to casos de uso (CU01-CU14), modules/procesos/ — CU10-CU12 (solicitudes, propuestas, integrantes), modules/semilleros/ — CU08, CU09, CU13, modules/usuarios/ — CU01, Roles y permisos — matriz de acceso validada contra backend real, Account recovery and activation share /reset-password screen, differentiated by ?activation=1 query param, token valid 60 minutes (+8 more)

### Community 26 - "Notificaciones frontend"
Cohesion: 0.23
Nodes (15): safeUrl(), bindEvents(), loadAndRender(), loadTargetValues(), notificationsModule, renderCard(), renderComposer(), renderPage() (+7 more)

### Community 27 - "Manifest PWA"
Cohesion: 0.12
Nodes (15): background_color, categories, description, display, display_override, icons, id, name (+7 more)

### Community 28 - "Resultados API"
Cohesion: 0.20
Nodes (4): ResultController, Result, AppServiceProvider, Illuminate\Support\ServiceProvider

### Community 29 - "Tests de regresión de seguridad"
Cohesion: 0.16
Nodes (3): SecurityRegressionTest, App\Models\Seedbed, Seedbed

### Community 30 - "Stack documentado y RNF"
Cohesion: 0.15
Nodes (15): API REST (Sanctum, /api/v1), ISO/IEC 25010, Laravel Sanctum, MongoDB ($jsonSchema estricto), OpenAPI 3 / Swagger, PWA instalable para estudiantes, RNF04 Manuales de usuario, técnico e instalación, RNF08 Disponibilidad ≥ 99 % (+7 more)

### Community 31 - "Correos activación/reset"
Cohesion: 0.20
Nodes (7): AccountActivationNotification, CustomResetPasswordNotification, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Bus\Queueable, Illuminate\Notifications\Messages\MailMessage, Illuminate\Notifications\Notification, MailMessage

### Community 33 - "README y convenciones"
Cohesion: 0.17
Nodes (13): Laravel framework (vendor README), Conventional Commits standard adopted for commit history, Public repo — no real secrets committed, only where credentials live is documented, apache/ — custom Apache configuration (laravel.conf), api/ — Laravel 12 source code, UT Semilleros – Plataforma Web Académica, Secure authentication with Laravel Sanctum or Passport, Modular architecture based on microservices and RESTful APIs (+5 more)

### Community 34 - "Modelo de datos documentado"
Cohesion: 0.19
Nodes (13): Filtrar campos sensibles en core/crud.engine.js, Prohibiciones derivadas de incidentes reales, Aggregations MongoDB A1–A5 (semilleros por facultad, solicitudes por estado, propuestas por área, integrantes, auditoría), Hash de contraseñas bcrypt cost 12 (cast hashed), Catálogos Faculty, Career, Cat, Area, Group, Coordinator, Hotbed (semillero) — clase/colección, Modelo de calidad ISO/IEC 25010 (sucesor de 9126), Log (auditoría) (+5 more)

### Community 35 - "CU02 login Google (PWA)"
Cohesion: 0.27
Nodes (13): Google Identity (OAuth 2.0), CU02 Iniciar sesión con cuenta institucional (PWA), CU02 A1 – Primer ingreso: autorización de datos (paso 9), CU02 E1 – Correo fuera del dominio institucional, CU02 E2 – El estudiante cancela en Google, CU02 E3 – Usuario estudiante inactivado por el administrador, CU02 E4 – Sin conexión, CU02 E5 – Google no responde o devuelve error (+5 more)

### Community 36 - "CU07 Administrar facultades"
Cohesion: 0.15
Nodes (13): CU07 Administrar facultades, CU07 A1 – Consultar detalle (desde el paso 2), CU07 A2 – Modificar (desde el paso 2), CU07 A3 – Cambiar estado (desde el paso 2), CU07 A4 – Buscar y filtrar (desde el paso 2), CU07 A5 – Actor de solo consulta, CU07 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU07 E2 – Código de la facultad ya registrado (+5 more)

### Community 39 - "Config Laravel y factories"
Cohesion: 0.18
Nodes (5): UserFactory, UserSeeder, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash, Illuminate\Support\Str

### Community 40 - "CU11 Administrar grupos"
Cohesion: 0.17
Nodes (12): CU11 Administrar grupos de investigación, CU11 A1 – Consultar detalle (desde el paso 2), CU11 A2 – Modificar (desde el paso 2), CU11 A3 – Cambiar estado (desde el paso 2), CU11 A4 – Buscar y filtrar (desde el paso 2), CU11 A5 – Actor de solo consulta, CU11 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU11 E2 – Código del grupo ya registrado (+4 more)

### Community 42 - "Decisiones de arquitectura doc"
Cohesion: 0.24
Nodes (11): service-worker.js CACHE_NAME (semilleros-v13) y CDN cache 5 min, API REST /api/v1 con Laravel Sanctum, AuditObserver / colección logs solo-inserción (RF14, CU14), Despliegue piloto CAT Kennedy (Bogotá), Eliminación lógica (solo cambio de estado), Documentación OpenAPI con L5-Swagger, Matriz de ponderación de alternativas (Aplicativo 100 %, Web 67 %, Campaña 30,7 %), Panel web administrativo (Laravel + Blade + Bootstrap) (+3 more)

### Community 47 - "Docker compose local"
Cohesion: 0.27
Nodes (10): docker-compose.yml — service orchestration, Frontend volume mounted live — changes reflect without rebuild, Real schema applied via php artisan migrate --seed inside api container, not init.sql, api service — Laravel backend, exposed on :8000, db service — mysql:9.0, frontend service — serves static PWA (index.html, modules/), phpmyadmin service — phpmyadmin:5.2.2, db/ — database configuration and persistent data (+2 more)

### Community 48 - "CU08 Administrar programas"
Cohesion: 0.20
Nodes (10): CU08 Administrar programas, CU08 A1 – Consultar detalle (desde el paso 2), CU08 A2 – Modificar (desde el paso 2), CU08 A3 – Cambiar estado (desde el paso 2), CU08 A4 – Buscar y filtrar (desde el paso 2), CU08 A5 – Actor de solo consulta, CU08 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU08 E3 – El actor intenta una operación no permitida para su rol (HTTP 403) (+2 more)

### Community 49 - "CU10 Administrar áreas"
Cohesion: 0.20
Nodes (10): CU10 Administrar áreas de conocimiento, CU10 A1 – Consultar detalle (desde el paso 2), CU10 A2 – Modificar (desde el paso 2), CU10 A3 – Cambiar estado (desde el paso 2), CU10 A4 – Buscar y filtrar (desde el paso 2), CU10 A5 – Actor de solo consulta, CU10 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU10 E3 – El actor intenta una operación no permitida para su rol (HTTP 403) (+2 more)

### Community 50 - "Shell index.html y CDNs"
Cohesion: 0.20
Nodes (10): Chart.js 4.4.0 (CDN) for dashboard charts, DataTables + Buttons (CDN) for tabular UI and export, css/theme.css — design tokens source of truth, iOS/Safari PWA meta tags (apple-mobile-web-app-*, apple-touch-icon) — Safari ignores manifest.json for most PWA settings, index.html — PWA shell (frontend/index.html), black-translucent status bar chosen so app gradient fills screen, requires safe-area-inset-top padding, SweetAlert2 (CDN) for modals/alerts, Tailwind CSS via CDN script (+2 more)

### Community 52 - "composer.json metadatos"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 53 - "Scripts de composer"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 54 - "Semilleros PWA"
Cohesion: 0.36
Nodes (8): bindEvents(), loadAndRender(), loadObjectivesForSeedbed(), openDetail(), pwaSeedbedsModule, renderError(), renderList(), renderSkeleton()

### Community 55 - "Importación de usuarios UI"
Cohesion: 0.33
Nodes (8): bindImportModalEvents(), renderRows(), importRowHtml(), openImportModal(), parseImportInput(), ROLES, suggestNameFromEmail(), usersModule

### Community 58 - "Coordinadores API"
Cohesion: 0.29
Nodes (3): CoordinatorController, App\Http\Controllers\Controller, App\Models\Coordinator

### Community 61 - "Middleware rol y activo"
Cohesion: 0.43
Nodes (4): EnsureUserIsActive, RoleMiddleware, Closure, Symfony\Component\HttpFoundation\Response

### Community 62 - "Dependencias dev PHP"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 63 - "Bocetos de arquitectura"
Cohesion: 0.25
Nodes (8): components/ — navbar.js, loader.js, core/ — router.js, guards.js, state.js, modules/{feature}/ pattern — view + service + controller per feature, services/ — api.service.js, storage.service.js, Arquitectura propuesta — frontend/ folder layout (core, modules, services, components), Arquitectura — semilleros-pwa/ top-level topology (frontend/backend/database split), estructura.txt — minimal frontend/ layout (Dockerfile, index.html, app.js, manifest.json, service-worker.js, icon.png temporal), app.js loaded as ES module entry point

### Community 64 - "Decisión doc vs stack real"
Cohesion: 0.25
Nodes (8): Decisión 2026-09-28: lo funcional lo manda el documento; el stack es el real, Brechas conocidas: RN14, RN10, RN06, RN02/RN03, RN08, RF15/CU28, RF16/RN09, CU02 sin Google, Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md (RN01–RN15, 16 RF, 16 RNF, CU01–CU30), Login estudiante con Google institucional (Socialite, HU04), Proyecto base INITIUM 2020 (app móvil semilleros IDEAD), Transformación relacional (17 tablas PostgreSQL) a documental MongoDB, Stack adaptado: Laravel 12 + MongoDB + Bootstrap 5 + PWA + Sanctum + L5-Swagger, Stack original: CakePHP 3.8, PostgreSQL 11, Ionic 4/Angular 6/Cordova, Heroku, Firebase

### Community 65 - "Despliegue real Hostinger"
Cohesion: 0.25
Nodes (8): Despliegue real Hostinger compartido (alias htg, PHP 8.2.33, sin Node), Enrutamiento .htaccess: /api/* a api/public, resto a index.html (SPA), Nunca --seed en producción, SGAA/ proyecto hermano independiente, Diagrama de despliegue UML (Nginx+PHP-FPM, cron mongodump, Atlas), Estimación de costos COP 42.637.000 (12 meses), Decisión infra: Hostinger VPS KVM 2 + MongoDB Atlas, Plan de respaldo mongodump diario, RTO<1h RPO 24h

### Community 66 - "Changelog 2026-07-28"
Cohesion: 0.25
Nodes (8): Changelog — Sesión de validación 2026-07-28, Dashboard, Deuda técnica resuelta en esta ronda, Notificaciones push, Propuestas (estudiante), PWA / instalación, Semilleros, Solicitudes (postulación a semillero)

### Community 67 - "CU13 Registrar semillero"
Cohesion: 0.32
Nodes (8): Acuerdo 0033 de 2018, CU13 Registrar semillero, CU13 A1 – Guardar como borrador, CU13 E1 – Faltan datos obligatorios, CU13 E2 – Código duplicado, CU13 E4 – Falla de base de datos, RN03 Semillero requiere aprobación escrita (Acuerdo 0033), RNF06 Creación de un nuevo semillero

### Community 68 - "Imports de tests"
Cohesion: 0.29
Nodes (5): App\Models\Faculty, App\Models\MembershipRequest, App\Models\Program, App\Models\Proposal, Tests\TestCase

### Community 70 - "Config composer"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 74 - "Changelog 2026-08-31"
Cohesion: 0.29
Nodes (6): Bugs reales encontrados, Contexto, Funcionalidad nueva: importación masiva de usuarios, Pendiente (no bloqueante), Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas, Verificación end-to-end (2026-08-31, contra producción real)

### Community 75 - "Login controller UI"
Cohesion: 0.52
Nodes (6): initLoginController(), clearError(), isValidEmail(), showError(), validateEmail(), validatePassword()

### Community 76 - "Manejo de excepciones"
Cohesion: 0.33
Nodes (4): Handler, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 77 - "Dependencias PHP"
Cohesion: 0.33
Nodes (6): require, laravel/framework, laravel/sanctum, laravel/tinker, minishlink/web-push, php

### Community 78 - "Agente deploy-engineer"
Cohesion: 0.33
Nodes (5): Brechas reales Docker → Hostinger (verifícalas en cada deploy, no las asumas), Entrega, Flujo (el repo es la fuente del código; el servidor no tiene git), Higiene de exposición pública (hallazgos 2026-09-26), Validación en vivo (obligatoria; "se subió" no es "funciona")

### Community 79 - "Agente test-engineer"
Cohesion: 0.33
Nodes (5): Ambientes, Checklist de flujos (corre todos los que toquen el cambio y su código adyacente), Entrega, Honestidad sobre la suite de pruebas, Reglas de aislamiento y limpieza (obligatorias)

### Community 80 - "Contexto del proyecto"
Cohesion: 0.33
Nodes (6): Tokens css/theme.css y gradiente --color-gradient (#ef4444→#f97316), Grafo graphify-out/ (God nodes User, apiFetch, Controller, Seedbed, createCrudModule), UT Semilleros (PWA vanilla JS + Laravel 12 api/), Documentación Técnica SemillerosUT, Ramas main/develop/feature/HUxx + Conventional Commits + tags por trimestre, Entregables SENA ADSO Trimestres I–V

### Community 81 - "Autoload PSR-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 82 - "Config de logging"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 83 - "Agentes y flujo de CU"
Cohesion: 0.50
Nodes (5): Agentes .claude/agents (use-case-auditor, backend/frontend/pwa-tester, architecture-reviewer...), Casos de uso vigentes CU01–CU30, Flujo de validación de casos de uso en equipo (docs/qa/), Numeración (RFxx/CUxx) en api/routes/api.php sigue diseño 2020, Casos de uso CU01–CU14 (diseño 2020)

### Community 84 - "Agente backend-tester"
Cohesion: 0.40
Nodes (4): Datos y limpieza (obligatorio), Entrega, Integración de lo desplegado (Hostinger), Por cada caso de uso (CU01–CU14)

### Community 85 - "Agente bug-historian"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en este orden, Patrones de bug ya vividos (compara el síntoma nuevo contra estos primero), Reglas

### Community 86 - "Agente code-reviewer"
Cohesion: 0.40
Nodes (4): Checklist específico del proyecto, Entrega, Qué revisar primero, Reglas

### Community 87 - "Agente db-architect"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en orden de confianza, Qué validas, Reglas

### Community 88 - "Agente qa-design-mobile"
Cohesion: 0.40
Nodes (4): Entrega, Qué revisar en la UI móvil, Setup, Validación PWA (automática, este proyecto la necesita)

### Community 89 - "Agente qa-design-web"
Cohesion: 0.40
Nodes (4): Entrega, Falsos positivos ya descartados (no los reportes de nuevo sin evidencia nueva), Qué revisar, Setup

### Community 90 - "Agente use-case-auditor"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes de reglas (en orden), Método (para cada regla), Reglas

### Community 91 - "Roles, HU y Scrum"
Cohesion: 0.40
Nodes (5): Sincronía requireRole() frontend / role: backend con roles-usuarios.md, docs/roles-usuarios.md (matriz de roles), Historias de usuario HU01–HU23 y HU-NF01–NF08 (Scrum), Roles: Administrador del sistema, Líder de semillero, Administrativo, Estudiante, Scrum con sprints de 2 semanas (módulo PWA)

### Community 93 - "Agente architecture-advisor"
Cohesion: 0.50
Nodes (3): Arquitectura real (verifícala con `graphify` antes de opinar), Cómo respondes, Reglas

### Community 94 - "Agente architecture-reviewer"
Cohesion: 0.50
Nodes (3): Formato, Recorrido obligatorio (usa `graphify query/path/explain` en cada punto), Trabajo en equipo

### Community 95 - "Agente frontend-tester"
Cohesion: 0.50
Nodes (3): Ambientes y datos, Entrega, Qué pruebas, por cada caso de uso y rol

### Community 96 - "Agente pwa-tester"
Cohesion: 0.50
Nodes (3): Checklist, Datos, Entrega

### Community 97 - "Agente ui-designer"
Cohesion: 0.50
Nodes (3): Alcance, Reglas, Trabajo en equipo

### Community 98 - "Normativa y BPMN"
Cohesion: 0.50
Nodes (4): Acuerdo 0033 de 2018 (sistema de investigación pregrado a distancia UT), BPMN Proceso 1: creación y divulgación de semillero, BPMN Proceso 2: vinculación de estudiante a semillero, Ley 1581 de 2012 / Decreto 1377 de 2013 (protección de datos)

### Community 99 - "Despliegue según README"
Cohesion: 0.50
Nodes (4): Deployment on Hostinger Premium Web Hosting, System deployed to production at https://ut-edu.online/, api/public set as domain root with .htaccess rewrites, api/public/robots.txt — allow all crawling

### Community 101 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 102 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **326 isolated node(s):** `Pending non-blocking improvements (push confirmation, proposal versioning, CAT Kennedy import)`, `modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores)`, `modules/semilleros/ — CU08, CU09, CU13`, `Account recovery and activation share /reset-password screen, differentiated by ?activation=1 query param, token valid 60 minutes`, `ADMINISTRATIVO — admin-like scope on semilleros/proyectos/productos/resultados, no user/facultad/programa/coordinador/auditoria management` (+321 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 537 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **35 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `Modelo User y políticas` to `Solicitudes modelo y tests`, `Tests de autenticación`, `Auditoría modelo y tests`, `Coordinadores modelo y tests`, `Config Laravel y factories`, `Tests de objetivos`, `AuthController API`, `Tests de resultados`, `Tests de semilleros`, `Facultades API`, `Programas API`, `Tests de integrantes`, `Propuestas modelo y tests`, `Resultados API`, `RequestSeeder`, `Correos activación/reset`?**
  _High betweenness centrality (0.062) - this node is a cross-community bridge._
- **Why does `Seedbed` connect `Semilleros e integrantes API` to `Solicitudes modelo y tests`, `Tests de autenticación`, `Tests de objetivos`, `Tests de resultados`, `Resultados API`, `Tests de semilleros`, `Tests de integrantes`, `Suscripciones push`, `ObjectiveSeeder`, `RequestSeeder`, `ResultSeeder`, `SeedbedSeeder`?**
  _High betweenness centrality (0.011) - this node is a cross-community bridge._
- **Why does `Area` connect `Áreas API` to `Seeders de catálogos`, `Modelo User y políticas`, `Resultados API`, `Suscripciones push`?**
  _High betweenness centrality (0.010) - this node is a cross-community bridge._
- **What connects `Pending non-blocking improvements (push confirmation, proposal versioning, CAT Kennedy import)`, `modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores)`, `modules/semilleros/ — CU08, CU09, CU13` to the rest of the system?**
  _326 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Escape HTML y layout` be split into smaller, more focused modules?**
  _Cohesion score 0.0935374149659864 - nodes in this community are weakly interconnected._
- **Should `Modelo User y políticas` be split into smaller, more focused modules?**
  _Cohesion score 0.07419712070874862 - nodes in this community are weakly interconnected._
- **Should `Tests de autenticación` be split into smaller, more focused modules?**
  _Cohesion score 0.07804878048780488 - nodes in this community are weakly interconnected._