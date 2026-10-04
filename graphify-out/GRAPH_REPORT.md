# Graph Report - _public_html  (2026-10-03)

## Corpus Check
- 366 files · ~323,253 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 2898 nodes · 6549 edges · 343 communities (108 shown, 180 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 99 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `328fb172`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- createCrudModule
- router.js
- TestCase
- Group
- SeedbedMember
- SecurityRegressionTest
- RN07 Toda escritura/login genera auditoría inmutable
- escape.js
- pwa-requests.module.js
- apiFetch
- RN01 No eliminación física, baja por estado inactivo
- Illuminate\Http\Request
- SeedbedCrudTest
- Illuminate\Database\Seeder
- Coordinator
- User
- CU04ForgotPasswordTest
- SiaController
- RequestManagementTest
- CU29 Registrar auditoría (inclusión)
- AreaController
- package.json
- Roles y permisos — matriz de acceso validada contra backend real
- rbac.module.js
- Cat
- Seedbed
- .rule
- UserController.php
- PasswordPolicy
- CLAUDE.md — UT Semilleros
- RNF03 Seguridad web y móvil
- SiaTest
- SiaKnowledge
- Audit
- ResultCrudTest
- LayoutView
- CU07 Administrar facultades
- initLayoutController
- Controller
- Objective
- UT Semilleros - Plataforma Web Academica
- RbacController
- PermissionResolver
- CoordinatorCrudTest
- Permission
- CU02GoogleLoginTest
- mountSia
- dashboard.controller.js
- Notificacion
- Illuminate\Database\Eloquent\Model
- CU27EvaluateProposalsTest
- manifest.json
- Proposal
- CU30 – Consultar auditoría
- SiaAssistant
- CU01LoginTest
- Changelog — Sesión de validación 2026-07-28
- requests.module.js
- escapeHtml
- RequestCrudTest
- CU13 – Registrar semillero (Ronda A + Ronda B)
- audits.module.js
- Faculty
- sia-admin.module.js
- ProposalCrudTest
- SiaPoolAndLocalTest
- CU30AuditQueryTest
- CU21 – Gestionar integrantes del semillero
- AuditFixesAuthUsersTest
- CU26MyProposalsTest
- RF03ProgramTest
- ReportController
- Audit2_Cu25ProposalTest
- CU06 Administrar usuarios
- CU24 – Gestionar solicitudes recibidas
- push.service.js
- Auditoría integral de CU01–CU25 y cierre de CU26
- RequestController
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- README — Manuales de usuario
- proposals.module.js
- sleep
- CU05ProfileTest
- lib.js
- CU08 Administrar programas
- CU09 Administrar CAT
- CU10 Administrar áreas de conocimiento
- CU11 Administrar grupos de investigación
- CU12 Administrar coordinadores
- CU07 – Administrar facultades
- users.module.js
- GroupController
- composer.json
- scripts
- harness.js
- ReportController.php
- Despliegue de la ronda de auditoría (CU01–CU25 + CU26)
- CU13 Registrar semillero
- Ronda de UX/responsive — 2026-09-30
- initLoginController
- buttons.js
- GroqKeyPool
- require-dev
- RNF03SecurityTest
- RF16ConsentTest
- CU11 – Administrar grupos de investigación
- FacultyController
- ProposalStatusVocabularyTest
- Validación CU27 – Evaluar propuestas
- crudflow.js
- config
- Validación CU26 – Consultar mis propuestas
- Validación CU28 – Consultar reportes y estadísticas
- Handler.php
- require
- RNF05AuthorizationReferenceTest
- Validación CU29 – Registrar auditoría
- CU16 – Consultar semilleros (panel web)
- service-worker.js
- psr-4
- logging.php
- 2026_09_28_000006_encrypt_coordinator_phone.php
- Validación CU30 – Consultar auditoría
- CU28ReportsTest
- Tablas DataTables + responsivas (todas, web y PWA)
- autoload-dev
- extra
- Pruebas visuales y funcionales de la interfaz (2026-10-03)
- Illuminate\Database\Migrations\Migration
- pwa_deep.js
- AuditTest
- Audit2_Cu22RequestTest
- Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)
- Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas
- Validación CU25 – Registrar propuesta de semillero
- cdnserver.js
- activation.blade.php
- reset-password.blade.php
- console.php
- Laravel framework (vendor README)
- Deployment on Hostinger Premium Web Hosting
- AuthTest
- Sesion de validacion 2026-07-28 (auditoria completa)
- Envíos asíncronos: avisos push y correos por la cola (2026-10-03)
- pwa.js
- Importacion masiva de usuarios (POST /api/users/import)
- index.html — frontend PWA shell
- Pruebas visuales automatizadas (Playwright sobre Chrome)
- Apple Touch Icon 152x152 (PWA branding icon)
- Apple Touch Icon 167x167 (PWA branding icon)
- Apple Touch Icon 180x180 (PWA branding icon)
- Login page background image
- Login page logo (Universidad del Tolima branding)
- Agentes .claude/agents (use-case-auditor, backend/frontend/pwa-tester, architecture-reviewer...)
- Casos de uso vigentes CU01–CU30
- Filtrar campos sensibles en core/crud.engine.js
- Decisión 2026-09-28: lo funcional lo manda el documento; el stack es el real
- Tokens css/theme.css y gradiente --color-gradient (#ef4444→#f97316)
- Despliegue real Hostinger compartido (alias htg, PHP 8.2.33, sin Node)
- Entorno Docker local aislado (docker-compose.override.yml, sin config:cache)
- Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md (RN01–RN15, 16 RF, 16 RNF, CU01–CU30)
- Flujo de validación de casos de uso en equipo (docs/qa/)
- Grafo graphify-out/ (God nodes User, apiFetch, Controller, Seedbed, createCrudModule)
- Enrutamiento .htaccess: /api/* a api/public, resto a index.html (SPA)
- Numeración (RFxx/CUxx) en api/routes/api.php sigue diseño 2020
- Sincronía requireRole() frontend / role: backend con roles-usuarios.md
- service-worker.js CACHE_NAME (semilleros-v13) y CDN cache 5 min
- SGAA/ proyecto hermano independiente
- Tests PHPUnit sobre SQLite :memory: con -e en Docker (hallazgo C-01)
- Frontend volume mounted live — changes reflect without rebuild
- Real schema applied via php artisan migrate --seed inside api container, not init.sql
- api service — Laravel backend, exposed on :8000
- db service — mysql:9.0
- frontend service — serves static PWA (index.html, modules/)
- phpmyadmin service — phpmyadmin:5.2.2
- Acuerdo 0033 de 2018 (sistema de investigación pregrado a distancia UT)
- Aggregations MongoDB A1–A5 (semilleros por facultad, solicitudes por estado, propuestas por área, integrantes, auditoría)
- API REST /api/v1 con Laravel Sanctum
- AuditObserver / colección logs solo-inserción (RF14, CU14)
- Hash de contraseñas bcrypt cost 12 (cast hashed)
- BPMN Proceso 1: creación y divulgación de semillero
- BPMN Proceso 2: vinculación de estudiante a semillero
- Casos de uso CU01–CU14 (diseño 2020)
- Despliegue piloto CAT Kennedy (Bogotá)
- Catálogos Faculty, Career, Cat, Area, Group, Coordinator
- Diagrama de despliegue UML (Nginx+PHP-FPM, cron mongodump, Atlas)
- Eliminación lógica (solo cambio de estado)
- Estimación de costos COP 42.637.000 (12 meses)
- Ramas main/develop/feature/HUxx + Conventional Commits + tags por trimestre
- Login estudiante con Google institucional (Socialite, HU04)
- Historias de usuario HU01–HU23 y HU-NF01–NF08 (Scrum)
- Hotbed (semillero) — clase/colección
- Decisión infra: Hostinger VPS KVM 2 + MongoDB Atlas
- Proyecto base INITIUM 2020 (app móvil semilleros IDEAD)
- Modelo de calidad ISO/IEC 25010 (sucesor de 9126)
- Documentación OpenAPI con L5-Swagger
- Ley 1581 de 2012 / Decreto 1377 de 2013 (protección de datos)
- Log (auditoría)
- Matriz de ponderación de alternativas (Aplicativo 100 %, Web 67 %, Campaña 30,7 %)
- Member (integrante)
- MembershipRequest (solicitud de vinculación)
- Transformación relacional (17 tablas PostgreSQL) a documental MongoDB
- MongoDB $jsonSchema Schema Validation (strict/error)
- Panel web administrativo (Laravel + Blade + Bootstrap)
- Plan de respaldo mongodump diario, RTO<1h RPO 24h
- Proposal (propuesta de idea)
- PWA instalable (cliente estudiante)
- Roles: Administrador del sistema, Líder de semillero, Administrativo, Estudiante
- PersonalAccessToken Sanctum sobre MongoDB
- Scrum con sprints de 2 semanas (módulo PWA)
- SemillerosUT (sistema web + PWA)
- Stack adaptado: Laravel 12 + MongoDB + Bootstrap 5 + PWA + Sanctum + L5-Swagger
- Stack original: CakePHP 3.8, PostgreSQL 11, Ionic 4/Angular 6/Cordova, Heroku, Firebase
- Entregables SENA ADSO Trimestres I–V
- User (modelo)
- Google Identity (OAuth 2.0)
- Servidor de correo SMTP
- Acuerdo 0033 de 2018
- API REST (Sanctum, /api/v1)
- ISO/IEC 25010
- Laravel Sanctum
- Ley 1581 de 2012 / Decreto 1377 de 2013
- Lighthouse
- Inicio de sesión como precondición (no include)
- MongoDB ($jsonSchema estricto)
- OpenAPI 3 / Swagger
- OWASP ZAP baseline
- Panel web administrativo
- Diagramas UML 2.5 PlantUML
- PWA instalable para estudiantes
- Refinamiento de 14 a 30 casos de uso
- RF02 Gestión de facultades
- RF03 Gestión de programas
- RF04 Gestión de CAT
- RF06 Gestión de grupos de investigación
- RF07 Gestión de coordinadores
- RF08 Gestión de objetivos
- RF09 Gestión de resultados
- RF10 Gestión de solicitudes de vinculación
- RF11 Gestión de propuestas
- RF12 Gestión de integrantes
- RF13 Gestión de semilleros
- RF14 Registro y consulta de auditoría
- RF15 Reportes y estadísticas
- RF16 Autorización de tratamiento de datos personales
- RNF04 Manuales de usuario, técnico e instalación
- RNF08 Disponibilidad ≥ 99 %
- RNF11 Mantenibilidad (PSR-12, cobertura 70 %)
- RNF13 Respaldo y recuperación (RPO 24 h, RTO 1 h)
- RNF14 Integridad de datos ($jsonSchema)
- RNF15 Documentación de la API (OpenAPI)
- RNF16 Localización es-CO, America/Bogota
- Sistema SemillerosUT
- Baja lógica por estado inactivo (sin borrado físico)
- Matrices de trazabilidad RF/RNF × CU
- PWA icon 192x192 (standard branding)
- PWA maskable icon 192x192 (Android adaptive branding)
- PWA icon 512x512 (standard branding)
- PWA maskable icon 512x512 (Android adaptive branding)
- app.js loaded as ES module entry point
- Chart.js 4.4.0 (CDN) for dashboard charts
- DataTables + Buttons (CDN) for tabular UI and export
- css/theme.css — design tokens source of truth
- iOS/Safari PWA meta tags (apple-mobile-web-app-*, apple-touch-icon) — Safari ignores manifest.json for most PWA settings
- black-translucent status bar chosen so app gradient fills screen, requires safe-area-inset-top padding
- SweetAlert2 (CDN) for modals/alerts
- Tailwind CSS via CDN script
- Anti-FOUC inline script applying saved theme before render
- viewport-fit=cover meta tag enabling safe-area-inset CSS
- Public repo — no real secrets committed, only where credentials live is documented
- apache/ — custom Apache configuration (laravel.conf)
- api/ — Laravel 12 source code
- db/ — database configuration and persistent data
- Pending non-blocking improvements (push confirmation, proposal versioning, CAT Kennedy import)
- System deployed to production at https://ut-edu.online/
- Secure authentication with Laravel Sanctum or Passport
- api/public set as domain root with .htaccess rewrites
- Modular architecture based on microservices and RESTful APIs
- New users created without password; activate via email link
- PWA choice for accessible, secure institutional app
- Apache 2.4 embedded web server
- Docker & Docker Compose
- MySQL 9.0 database
- phpMyAdmin 5.2.2
- build-deploy-bundle.sh
- run.sh
- runui.sh

## God Nodes (most connected - your core abstractions)
1. `User` - 424 edges
2. `escapeHtml()` - 109 edges
3. `TestCase` - 108 edges
4. `apiFetch()` - 102 edges
5. `Seedbed` - 95 edges
6. `Faculty` - 91 edges
7. `Program` - 83 edges
8. `Area` - 56 edges
9. `Proposal` - 51 edges
10. `Controller` - 47 edges

## Surprising Connections (you probably didn't know these)
- `servicio api (Laravel, puerto 8000)` --semantically_similar_to--> `E4 pendiente: cola+cron sin resolver (QUEUE_CONNECTION=sync)`  [INFERRED] [semantically similar]
  docker-compose.yml → docs/validacion/CU04.md
- `PasswordPolicy (RN10, servidor+cliente)` --semantically_similar_to--> `Roles del sistema (Admin, Administrativo, Líder, Estudiante)`  [INFERRED] [semantically similar]
  docs/manuales/manual_tecnico.md → api/resources/sia/base-conocimiento.md
- `view()` --calls--> `escapeHtml()`  [EXTRACTED]
  modules/auth/consent.module.js → core/escape.js
- `render()` --calls--> `escapeHtml()`  [EXTRACTED]
  modules/auth/reset-password.module.js → core/escape.js
- `renderSkeleton()` --calls--> `LayoutView()`  [EXTRACTED]
  modules/pwa/pwa-requests.module.js → layout/layout.view.js

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Precedencia de permisos RBAC: ADMIN_SISTEMA > excepción persona > rol/grupo** — docs_rbac_readme_permission_resolver_effective, docs_rbac_readme_permission_groups, docs_rbac_readme_rbac_controller [EXTRACTED 0.85]
- **Serie de actas de validación CU01-CU04 contra la especificación** — docs_especificacion_especificacion_requerimientos_casos_de_uso_semillerosut_doc, docs_validacion_cu01_md, docs_validacion_cu02_md, docs_validacion_cu03_md, docs_validacion_cu04_md [EXTRACTED 0.90]
- **Arquitectura de SIA (asistente IA): controlador, servicio, pool de keys, conocimiento** — docs_sia_readme_sia_controller, docs_sia_readme_sia_assistant, docs_sia_readme_groq_key_pool, api_resources_sia_base_conocimiento_sia, docs_sia_readme_sia_local_responder [EXTRACTED 0.90]
- **Patrón de bug: ADMIN_SISTEMA faltante en rutas de escritura (Resultados, Integrantes)** — docs_validacion_cu20_admin_sistema_missing, docs_validacion_cu21_admin_sistema_missing [INFERRED 0.85]
- **Precedente 'Administrativo solo consulta' aplicado consistentemente CU16/CU19/CU20/CU21/CU24** — docs_validacion_cu16_administrativo_read_only, docs_validacion_cu19_administrativo_consistency, docs_validacion_cu20_admin_sistema_missing, docs_validacion_cu21_admin_sistema_missing, docs_validacion_cu24_administrativo_a3 [INFERRED 0.85]
- **Patrón RN06: guardLeaderOwnsSeedbed reaparece en Seedbed/Objective/Result/Member/Request** — docs_validacion_cu13_rn06, docs_validacion_cu19_rn06_missing, docs_validacion_cu20_rn06_missing, docs_validacion_cu21_rn06_missing, docs_validacion_cu24_rn06_scope [INFERRED 0.85]

## Communities (343 total, 180 thin omitted)

### Community 0 - "createCrudModule"
Cohesion: 0.18
Nodes (18): createCrudModule(), bindEvents(), clearFieldError(), create(), filterOptions(), init(), isValidEmail(), renderForm() (+10 more)

### Community 1 - "router.js"
Cohesion: 0.10
Nodes (35): requireAuth(), requireRole(), navigateTo(), renderRoute(), routes, initGoogleLogin(), loadGis(), startSession() (+27 more)

### Community 2 - "TestCase"
Cohesion: 0.08
Nodes (16): PasswordResetEndToEndTest, PasswordResetTest, ExampleTest, ResilientRateLimiterWiringTest, TestCase, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Http\Client\ConnectionException (+8 more)

### Community 3 - "Group"
Cohesion: 0.09
Nodes (4): Group, GroupSeeder, GroupCrudTest, Audit3_ReviewFixesTest

### Community 4 - "SeedbedMember"
Cohesion: 0.09
Nodes (5): Closure, SeedbedMemberController, SeedbedMember, Audit2_SeedbedFixTest, SeedbedMemberTest

### Community 6 - "RN07 Toda escritura/login genera auditoría inmutable"
Cohesion: 0.06
Nodes (48): Actor Estudiante, CU16 Consultar semilleros (panel web), CU16 A1 – Líder, CU16 A2 – Administrativo, CU16 E1 – Sin resultados, CU16 E2 – Falla de base de datos, CU17 Consultar semilleros por facultad (PWA), CU17 A1 – Buscar (+40 more)

### Community 7 - "escape.js"
Cohesion: 0.11
Nodes (27): ESCAPES, safeImageSrc(), safeUrl(), BOTTOM_NAV, bindEvents(), loadAndRender(), loadTargetValues(), notificationsModule (+19 more)

### Community 8 - "pwa-requests.module.js"
Cohesion: 0.24
Nodes (11): bindEvents(), loadAndRender(), loadedRequests, loadedSeedbeds, loadSeedbedsSelect(), openRequestDetail(), pwaRequestsModule, renderError() (+3 more)

### Community 9 - "apiFetch"
Cohesion: 0.06
Nodes (36): PUBLIC_PATHS, fmt(), showOfflineBanner(), PASSWORD_HINT, passwordPolicyError(), areasModule, bindEvents(), forgotPasswordModule (+28 more)

### Community 10 - "RN01 No eliminación física, baja por estado inactivo"
Cohesion: 0.08
Nodes (33): Actor Líder de semillero, CU14 Modificar semillero, CU14 A1 – Gestionar objetivos, resultados o integrantes, CU14 A2 – Administrador del sistema, CU14 E1 – Datos inválidos, CU14 E2 – El semillero no pertenece al líder (HTTP 403), CU14 E3 – Otro usuario modificó el semillero después de abrirlo, CU15 Cambiar estado de semillero (+25 more)

### Community 11 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (11): AuthController, Authenticate, ProposalResource, UserResource, DateTimeInterface, Illuminate\Auth\Middleware\Authenticate, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions (+3 more)

### Community 13 - "Illuminate\Database\Seeder"
Cohesion: 0.10
Nodes (10): AreaSeeder, DatabaseSeeder, FacultySeeder, ObjectiveSeeder, ProposalSeeder, RequestSeeder, ResultSeeder, SeedbedSeeder (+2 more)

### Community 14 - "Coordinator"
Cohesion: 0.23
Nodes (3): CoordinatorController, Coordinator, CoordinatorSeeder

### Community 15 - "User"
Cohesion: 0.04
Nodes (13): User, UserPolicy, AreaCrudTest, CatCrudTest, FacultyCrudTest, ProgramCrudTest, CU03LogoutTest, UserCrudTest (+5 more)

### Community 16 - "CU04ForgotPasswordTest"
Cohesion: 0.06
Nodes (16): AccountActivationNotification, CustomResetPasswordNotification, GoogleAuthException, GoogleIdTokenVerifier, SiaUnavailableException, MailBrand, AccountActivationTest, CU04ForgotPasswordTest (+8 more)

### Community 17 - "SiaController"
Cohesion: 0.12
Nodes (4): SiaController, SiaConversation, SiaMessage, SiaSetting

### Community 19 - "CU29 Registrar auditoría (inclusión)"
Cohesion: 0.12
Nodes (23): Prohibiciones derivadas de incidentes reales, api/.env.example ausente por comentario al final de linea en .gitignore, Sesion 2026-09-28: validacion CU01, RF01, RNF01, Actor Usuario web (abstracto), CU01 Iniciar sesión en el panel web, CU01 A1 – Olvidó la contraseña (paso 2), CU01 A2 – Recordarme (paso 3), CU01 A3 – Ruta solicitada previamente (paso 6) (+15 more)

### Community 21 - "package.json"
Cohesion: 0.09
Nodes (19): devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, private (+11 more)

### Community 22 - "Roles y permisos — matriz de acceso validada contra backend real"
Cohesion: 0.14
Nodes (16): modules/auditoria/ — CU14, modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores), Arquitectura Derivada — modules/ mapped to casos de uso (CU01-CU14), modules/procesos/ — CU10-CU12 (solicitudes, propuestas, integrantes), modules/semilleros/ — CU08, CU09, CU13, modules/usuarios/ — CU01, Roles y permisos — matriz de acceso validada contra backend real, Account recovery and activation share /reset-password screen, differentiated by ?activation=1 query param, token valid 60 minutes (+8 more)

### Community 23 - "rbac.module.js"
Cohesion: 0.18
Nodes (22): accordion(), bindActiveTab(), bindGroupsTab(), bindPeopleTab(), bindRolesTab(), bindTabs(), CATEGORY_ORDER, findUserByEmail() (+14 more)

### Community 24 - "Cat"
Cohesion: 0.14
Nodes (4): CatController, Cat, CatSeeder, RF04CatTest

### Community 25 - "Seedbed"
Cohesion: 0.12
Nodes (4): Closure, SeedbedController, Seedbed, Illuminate\Validation\ValidationException

### Community 27 - "UserController.php"
Cohesion: 0.29
Nodes (3): UserController, Illuminate\Http\JsonResponse, Illuminate\Support\Facades\Validator

### Community 28 - "PasswordPolicy"
Cohesion: 0.08
Nodes (8): ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, StoreUserRequest, UpdateUserRequest, PasswordPolicy, Illuminate\Foundation\Http\FormRequest, Illuminate\Validation\Rule

### Community 29 - "CLAUDE.md — UT Semilleros"
Cohesion: 0.05
Nodes (45): Agentes .claude/agents/ (bug-historian, architecture-advisor, etc.), Decisión: lo funcional lo manda el documento, el stack es el real, Google OAuth restringido a dominio institucional (RN04), Flujo incremental de graphify (--update, subagente docs), Prohibición: no usar git add . / -A, Prohibición: no interpolar hashes/secretos en ssh, CLAUDE.md — UT Semilleros, SGAA (proyecto hermano) (+37 more)

### Community 30 - "RNF03 Seguridad web y móvil"
Cohesion: 0.12
Nodes (19): Actor Usuario (abstracto), CU03 Cerrar sesión, CU03 A1 – Sesión vencida, CU03 E1 – Sin conexión en la PWA, CU05 Consultar y actualizar perfil, CU05 A1 – Cambiar contraseña (solo usuarios web), CU05 E1 – Teléfono inválido, CU05 E2 – Contraseña actual incorrecta (+11 more)

### Community 33 - "Audit"
Cohesion: 0.10
Nodes (5): AuditController, Audit, PruneAuditsTest, CU29AuditTest, Illuminate\Database\Eloquent\Builder

### Community 34 - "ResultCrudTest"
Cohesion: 0.12
Nodes (3): ResultController, Result, ResultCrudTest

### Community 35 - "LayoutView"
Cohesion: 0.21
Nodes (22): LayoutView(), renderSkeleton(), allSeedbeds, bindCommonEvents(), bindSeedbedCardClicks(), detailSheetHtml(), fabHtml(), facultiesOf() (+14 more)

### Community 36 - "CU07 Administrar facultades"
Cohesion: 0.14
Nodes (14): Actor Administrador del sistema, Actor Administrativo, CU07 Administrar facultades, CU07 A1 – Consultar detalle (desde el paso 2), CU07 A2 – Modificar (desde el paso 2), CU07 A3 – Cambiar estado (desde el paso 2), CU07 A4 – Buscar y filtrar (desde el paso 2), CU07 A5 – Actor de solo consulta (+6 more)

### Community 37 - "initLayoutController"
Cohesion: 0.16
Nodes (18): applyTheme(), _closeBottomSheet(), _closeSidebar(), _closeUserMenu(), initLayoutController(), _openSidebar(), _openUserMenu(), PAGE_TITLES (+10 more)

### Community 38 - "Controller"
Cohesion: 0.12
Nodes (7): ProductController, ProjectController, ProjectMemberController, Controller, Product, Project, Illuminate\Support\Facades\Route

### Community 39 - "Objective"
Cohesion: 0.13
Nodes (3): ObjectiveController, Objective, ObjectiveCrudTest

### Community 40 - "UT Semilleros - Plataforma Web Academica"
Cohesion: 0.25
Nodes (8): docs/roles-usuarios.md (matriz de roles), Conventional Commits, Credenciales y configuracion sensible (repo publico), Despliegue en Hostinger (/api/public como raiz, desactualizado), scripts/dev-setup.sh (instalacion en un comando, idempotente), Jose Julio Bohorquez Delgado, Stack: Laravel 12, PHP 8.2+/8.4, MySQL 9, phpMyAdmin 5.2.2, Apache 2.4, UT Semilleros - Plataforma Web Academica

### Community 41 - "RbacController"
Cohesion: 0.11
Nodes (4): RbacController, PermissionGroup, RolePermission, RbacSeeder

### Community 42 - "PermissionResolver"
Cohesion: 0.16
Nodes (8): EnsureDataConsent, EnsureUserIsActive, PermissionMiddleware, RoleMiddleware, SecurityHeaders, PermissionResolver, Closure, Symfony\Component\HttpFoundation\Response

### Community 44 - "Permission"
Cohesion: 0.09
Nodes (3): Permission, UserPermission, RbacTest

### Community 46 - "mountSia"
Cohesion: 0.27
Nodes (11): FACES, format(), mountSia(), close(), finish(), open(), renderChat(), renderRating() (+3 more)

### Community 47 - "dashboard.controller.js"
Cohesion: 0.20
Nodes (18): buildSeedbedsByFaculty(), emptyChart(), escapeAttr(), freshCanvas(), hideKPI(), initDashboardController(), loadCharts(), loadKPIs() (+10 more)

### Community 48 - "Notificacion"
Cohesion: 0.05
Nodes (17): NotificacionController, PushSubscriptionController, DeliverPushNotification, Throwable, Notificacion, NotificacionRead, PushSubscription, PushSender (+9 more)

### Community 49 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.12
Nodes (5): AuditObserver, AuditTrail, Throwable, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 51 - "manifest.json"
Cohesion: 0.12
Nodes (15): background_color, categories, description, display, display_override, icons, id, name (+7 more)

### Community 52 - "Proposal"
Cohesion: 0.18
Nodes (3): Closure, ProposalController, Proposal

### Community 53 - "CU30 – Consultar auditoría"
Cohesion: 0.06
Nodes (32): 1. Qué dice la spec, 1. Qué dice la spec, 1. Qué dice la spec, 1. Qué dice la spec, 2. Búsqueda exhaustiva de evidencia, 2. Estado real en el código (evidencia), 2. Estado real en el código (evidencia), 2. Estado real en el código (evidencia) (+24 more)

### Community 56 - "Changelog — Sesión de validación 2026-07-28"
Cohesion: 0.25
Nodes (8): Changelog — Sesión de validación 2026-07-28, Dashboard, Deuda técnica resuelta en esta ronda, Notificaciones push, Propuestas (estudiante), PWA / instalación, Semilleros, Solicitudes (postulación a semillero)

### Community 57 - "requests.module.js"
Cohesion: 0.26
Nodes (14): approve(), canResolve(), filters, fmtDate(), offerMembership(), openRequest(), reject(), renderRequests() (+6 more)

### Community 58 - "escapeHtml"
Cohesion: 0.14
Nodes (26): escapeHtml(), bindListEvents(), closeSheet(), fmtDate(), loadAndRender(), loadAreaOptions(), loadProgramOptions(), openSheet() (+18 more)

### Community 60 - "CU13 – Registrar semillero (Ronda A + Ronda B)"
Cohesion: 0.16
Nodes (13): CU13 – Registrar semillero (Ronda A + Ronda B), Bug: select opcional siempre required (relation/select), config.draftOption – Guardar como borrador (A1), Hostinger CDN cachea .js 5 min (max-age=300), RN06 – Líder solo modifica sus propios semilleros, SeedbedController::show() (bug C-13 corregido), CU14 – Modificar semillero, E3 – bloqueo optimista (expected_updated_at, 409) (+5 more)

### Community 61 - "audits.module.js"
Cohesion: 0.14
Nodes (25): ACTION_LABEL, auditsModule, emptyFilters(), exportCsv(), filters, formatValue(), label(), loadPage() (+17 more)

### Community 62 - "Faculty"
Cohesion: 0.07
Nodes (12): Area, Faculty, MembershipRequest, Program, AppServiceProvider, ProgramSeeder, RF02FacultyTest, RF05AreaTest (+4 more)

### Community 63 - "sia-admin.module.js"
Cohesion: 0.28
Nodes (12): bind(), FACE, fmtDate(), kpi(), LIMIT_GROUPS, LIMIT_LABELS, md(), openConversation() (+4 more)

### Community 67 - "CU21 – Gestionar integrantes del semillero"
Cohesion: 0.18
Nodes (12): Bug: pwa-seedbeds.module.js leía seedbed.program roto desde CU13 Ronda B, CU20 – Gestionar resultados del semillero, Bug: ruta de escritura de resultados sin ADMIN_SISTEMA, Campo results.result_date (fecha opcional), Hallazgo: RN06 no existía en ResultController, CU21 – Gestionar integrantes del semillero, Bug: ADMIN_SISTEMA agregado a rutas de escritura de integrantes (mismo patrón CU20), Decisión de Jose: reconstruir CU21 completo por spec vs. parchar el modelo viejo (+4 more)

### Community 73 - "CU06 Administrar usuarios"
Cohesion: 0.18
Nodes (11): CU06 Administrar usuarios, CU06 A1 – Consultar detalle, CU06 A2 – Modificar, CU06 A3 – Inactivar / activar, CU06 A4 – Estudiantes, CU06 E1 – Correo ya registrado, CU06 E2 – Falta la referencia de autorización, CU06 E3 – El Administrador intenta inactivarse a sí mismo o al último… (+3 more)

### Community 74 - "CU24 – Gestionar solicitudes recibidas"
Cohesion: 0.24
Nodes (11): relation-multi (tipo de campo genérico en crud.engine.js), Ronda B – seedbed_program / seedbed_area (selección múltiple), CU17 – Consultar semilleros por facultad (PWA), CU18 – Consultar detalle de semillero (PWA), Bug: CSS display duplicado en formulario Ser miembro, Bug: user_id faltante en payload POST /requests, CU22 – Enviar solicitud de vinculación, Desviación documentada: una sola solicitud activa en todo el sistema (regla 2026-07-28) (+3 more)

### Community 75 - "push.service.js"
Cohesion: 0.32
Nodes (12): MESSAGES, renderPushCard(), showPushBanner(), TONES, getPushState(), isIOS(), isPushSupported(), isStandalone() (+4 more)

### Community 76 - "Auditoría integral de CU01–CU25 y cierre de CU26"
Cohesion: 0.14
Nodes (13): 1. Resumen ejecutivo, 2. Criterio de los estados, 3. Matriz CU01–CU25, 4. Hallazgos corregidos, 5. Hallazgos abiertos y justificación de los ALTO que siguen abiertos, 6. Decisiones y avisos para Jose, 7. Validación técnica, 8. Despliegue (ejecutado el 2026-10-03) (+5 more)

### Community 81 - "README — Manuales de usuario"
Cohesion: 0.06
Nodes (41): Memoria técnica de SIA (base-conocimiento.md), Diseño original INITIUM (2020, Ema Herrera y Nelly Mahecha), José Bohórquez (desarrollador), Roles del sistema (Admin, Administrativo, Líder, Estudiante), Entorno Docker local aislado de producción, RBAC granular (permisos por módulo+acción), SIA — asistente IA (RF17 propuesto), docker-compose.yml — entorno local (+33 more)

### Community 82 - "proposals.module.js"
Cohesion: 0.29
Nodes (13): badge(), canEvaluate(), day(), emptyFilters(), evaluate(), filters, loadList(), openProposal() (+5 more)

### Community 83 - "sleep"
Cohesion: 0.20
Nodes (11): H, log(), scenario(), settle(), STAMP, { startRelays, connect, sleep, BASE }, swalClick(), swalClose() (+3 more)

### Community 85 - "lib.js"
Cohesion: 0.25
Nodes (9): H, ROUTES, { startRelays, connect, sleep }, cdn, { chromium }, connect(), net, relay() (+1 more)

### Community 86 - "CU08 Administrar programas"
Cohesion: 0.20
Nodes (10): CU08 Administrar programas, CU08 A1 – Consultar detalle (desde el paso 2), CU08 A2 – Modificar (desde el paso 2), CU08 A3 – Cambiar estado (desde el paso 2), CU08 A4 – Buscar y filtrar (desde el paso 2), CU08 A5 – Actor de solo consulta, CU08 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU08 E2 – Código del programa ya registrado (+2 more)

### Community 87 - "CU09 Administrar CAT"
Cohesion: 0.20
Nodes (10): CU09 Administrar CAT, CU09 A1 – Consultar detalle (desde el paso 2), CU09 A2 – Modificar (desde el paso 2), CU09 A3 – Cambiar estado (desde el paso 2), CU09 A4 – Buscar y filtrar (desde el paso 2), CU09 A5 – Actor de solo consulta, CU09 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU09 E2 – Código del cat ya registrado (+2 more)

### Community 88 - "CU10 Administrar áreas de conocimiento"
Cohesion: 0.20
Nodes (10): CU10 Administrar áreas de conocimiento, CU10 A1 – Consultar detalle (desde el paso 2), CU10 A2 – Modificar (desde el paso 2), CU10 A3 – Cambiar estado (desde el paso 2), CU10 A4 – Buscar y filtrar (desde el paso 2), CU10 A5 – Actor de solo consulta, CU10 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU10 E2 – Código del área ya registrado (+2 more)

### Community 89 - "CU11 Administrar grupos de investigación"
Cohesion: 0.20
Nodes (10): CU11 Administrar grupos de investigación, CU11 A1 – Consultar detalle (desde el paso 2), CU11 A2 – Modificar (desde el paso 2), CU11 A3 – Cambiar estado (desde el paso 2), CU11 A4 – Buscar y filtrar (desde el paso 2), CU11 A5 – Actor de solo consulta, CU11 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU11 E2 – Código del grupo ya registrado (+2 more)

### Community 90 - "CU12 Administrar coordinadores"
Cohesion: 0.20
Nodes (10): CU12 Administrar coordinadores, CU12 A1 – Consultar detalle (desde el paso 2), CU12 A2 – Modificar (desde el paso 2), CU12 A3 – Cambiar estado (desde el paso 2), CU12 A4 – Buscar y filtrar (desde el paso 2), CU12 A5 – Actor de solo consulta, CU12 E1 – Campos obligatorios vacíos o con formato inválido (HTTP 422), CU12 E2 – Documento del coordinador ya registrado (+2 more)

### Community 91 - "CU07 – Administrar facultades"
Cohesion: 0.31
Nodes (10): CU06 – Administrar usuarios, A4 – Estudiantes solo por carga masiva, no creación individual, config.filters (crud.engine.js), E3 – guardAgainstLockout (impedir auto-inactivación de Admin), CU07 – Administrar facultades, config.pageLength (crud.engine.js), CU08 – Administrar programas, CU09 – Administrar Centros de Atención Tutorial (CAT) (+2 more)

### Community 92 - "users.module.js"
Cohesion: 0.29
Nodes (8): bindImportModalEvents(), renderRows(), importRowHtml(), openImportModal(), parseImportInput(), ROLES, suggestNameFromEmail(), usersModule

### Community 94 - "composer.json"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 95 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 96 - "harness.js"
Cohesion: 0.25
Nodes (9): checkScreen(), { connect, BASE, PASS, USERS, sleep }, fs, login(), newSession(), report(), setLabel(), VPS (+1 more)

### Community 97 - "ReportController.php"
Cohesion: 0.27
Nodes (3): PruneAudits, Csv, Illuminate\Console\Command

### Community 98 - "Despliegue de la ronda de auditoría (CU01–CU25 + CU26)"
Cohesion: 0.20
Nodes (9): 0. Antes de empezar, 1. Armar el paquete (local, no toca producción), 2. Respaldo en el servidor (obligatorio), 3. Subir el código (modo mantenimiento corto), 4. Migraciones, 5. Verificación, 6. Reversión, Despliegue de la ronda de auditoría (CU01–CU25 + CU26) (+1 more)

### Community 99 - "CU13 Registrar semillero"
Cohesion: 0.22
Nodes (9): CU13 Registrar semillero, CU13 A1 – Guardar como borrador, CU13 E1 – Faltan datos obligatorios, CU13 E2 – Código duplicado, CU13 E3 – Una referencia fue inactivada mientras se diligenciaba, CU13 E4 – Falla de base de datos, RN03 Semillero requiere aprobación escrita (Acuerdo 0033), RN08 Códigos y correo únicos (+1 more)

### Community 100 - "Ronda de UX/responsive — 2026-09-30"
Cohesion: 0.25
Nodes (9): RF05 Gestión de áreas de conocimiento, Catálogo de áreas de conocimiento — carga inicial, Anexo 7 — Áreas OCDE (Minciencias), CU05 – Consultar y actualizar perfil, RNF05/RN02 – Referencia de autorización, Ronda de UX/responsive — 2026-09-30, Incidente: contraseña ESTUDIANTE expuesta en salida por parser desactualizado, Bug: FABs SIA/WhatsApp sobre modales (MutationObserver solo observaba #app) (+1 more)

### Community 101 - "initLoginController"
Cohesion: 0.36
Nodes (6): initLoginController(), clearError(), isValidEmail(), showError(), validateEmail(), validatePassword()

### Community 102 - "buttons.js"
Cohesion: 0.20
Nodes (5): closeCrud(), closeSwal(), H, ROUTES, { startRelays, connect, sleep, BASE }

### Community 104 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 107 - "CU11 – Administrar grupos de investigación"
Cohesion: 0.25
Nodes (8): CU11 – Administrar grupos de investigación, Decisión: Líder escribe grupos, Administrativo solo consulta (A5), normalizeCode() (RN08, código único mayúsculas), CU12 – Administrar coordinadores, Decisión: solo Admin escribe coordinadores, Líder/Administrativo solo consulta (A5), Campo coordinators.document (único, RN08), Tabla seedbed_members (rediseño completo, campos cifrados), Hallazgo sin corregir: requests.phone en texto plano (no cifrado como RNF12)

### Community 110 - "Validación CU27 – Evaluar propuestas"
Cohesion: 0.22
Nodes (8): Actores y alcance (según la spec), Cambios, Decisiones y pendiente, Despliegue y validación en vivo (2026-10-03), Pasos de prueba manual (navegador), Pruebas, Punto de partida, Validación CU27 – Evaluar propuestas

### Community 111 - "crudflow.js"
Cohesion: 0.25
Nodes (7): CATALOGS, closeSwal(), fillModal(), H, log(), STAMP, { startRelays, connect, sleep }

### Community 112 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 113 - "Validación CU26 – Consultar mis propuestas"
Cohesion: 0.25
Nodes (7): Cambios, Cumplimiento de la especificación, Decisión de alcance (Jose, 2026-09-30): puente reversible para los estados, Pendiente / decisiones abiertas, Pruebas, Punto de partida, Validación CU26 – Consultar mis propuestas

### Community 114 - "Validación CU28 – Consultar reportes y estadísticas"
Cohesion: 0.25
Nodes (7): Actores y alcance (según la spec), Cambios, Despliegue, Pendiente / límites conocidos, Punto de partida, Trazabilidad spec → prueba (`CU28ReportsTest`, 22 tests), Validación CU28 – Consultar reportes y estadísticas

### Community 115 - "Handler.php"
Cohesion: 0.33
Nodes (4): Handler, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 116 - "require"
Cohesion: 0.33
Nodes (6): require, laravel/framework, laravel/sanctum, laravel/tinker, minishlink/web-push, php

### Community 118 - "Validación CU29 – Registrar auditoría"
Cohesion: 0.25
Nodes (7): Cambios, Decisiones, Despliegue y validación en vivo (2026-10-03), Pendiente, Pruebas, Punto de partida, Validación CU29 – Registrar auditoría

### Community 119 - "CU16 – Consultar semilleros (panel web)"
Cohesion: 0.33
Nodes (6): CU16 – Consultar semilleros (panel web), Decisión: Administrativo pierde escritura sobre semilleros (revierte decisión 2026-07-28), CU19 – Gestionar objetivos del semillero, Administrativo alineado a solo consulta (mismo criterio que CU16), Hallazgo: RN06 no existía en ObjectiveController (cualquier líder borraba objetivos ajenos), A3 – Administrativo solo consulta solicitudes recibidas

### Community 120 - "service-worker.js"
Cohesion: 0.29
Nodes (3): CDN_HOSTS, CDN_PRECACHE, SHELL_URLS

### Community 121 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 122 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 123 - "2026_09_28_000006_encrypt_coordinator_phone.php"
Cohesion: 0.83
Nodes (3): down(), isEncrypted(), up()

### Community 124 - "Validación CU30 – Consultar auditoría"
Cohesion: 0.25
Nodes (7): Cambios, Despliegue y validación en vivo (2026-10-03), Pasos de prueba manual (navegador, rol Administrador), Pendiente, Pruebas, Punto de partida, Validación CU30 – Consultar auditoría

### Community 125 - "CU28ReportsTest"
Cohesion: 0.11
Nodes (11): Throwable, ResilientRateLimiter, CU28ReportsTest, ExampleTest, ResilientRateLimiterTest, Illuminate\Cache\ArrayStore, Illuminate\Cache\RateLimiter, Illuminate\Cache\Repository (+3 more)

### Community 126 - "Tablas DataTables + responsivas (todas, web y PWA)"
Cohesion: 0.50
Nodes (4): mobile-card-table (solución responsiva propia), Tablas DataTables + responsivas (todas, web y PWA), Aclaración: misión/visión/objetivo NULL en BD, no es bug de código, Bug: project-members.module.js sin DataTables en absoluto

### Community 127 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 128 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 137 - "Pruebas visuales y funcionales de la interfaz (2026-10-03)"
Cohesion: 0.25
Nodes (7): Cobertura, Despliegue, Falsos positivos descartados (no son defectos), Hallazgos corregidos, No verificado, Pruebas visuales y funcionales de la interfaz (2026-10-03), Segunda tanda (2026-10-03/04)

### Community 143 - "pwa_deep.js"
Cohesion: 0.32
Nodes (6): H, log(), ok(), scenario(), settle(), { startRelays, connect, sleep }

### Community 153 - "Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)"
Cohesion: 0.29
Nodes (7): components/ — navbar.js, loader.js, core/ — router.js, guards.js, state.js, modules/{feature}/ pattern — view + service + controller per feature, services/ — api.service.js, storage.service.js, Arquitectura propuesta — frontend/ folder layout (core, modules, services, components), Arquitectura — semilleros-pwa/ top-level topology (frontend/backend/database split), estructura.txt — minimal frontend/ layout (Dockerfile, index.html, app.js, manifest.json, service-worker.js, icon.png temporal)

### Community 156 - "Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas"
Cohesion: 0.29
Nodes (6): Bugs reales encontrados, Contexto, Funcionalidad nueva: importación masiva de usuarios, Pendiente (no bloqueante), Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas, Verificación end-to-end (2026-08-31, contra producción real)

### Community 160 - "Validación CU25 – Registrar propuesta de semillero"
Cohesion: 0.29
Nodes (6): Addendum (auditoría 2026-09-30), Cambios, No implementado / fuera de esta ronda, Pruebas, Punto de partida, Validación CU25 – Registrar propuesta de semillero

### Community 165 - "cdnserver.js"
Cohesion: 0.29
Nodes (5): fs, https, map, path, types

### Community 185 - "Sesion de validacion 2026-07-28 (auditoria completa)"
Cohesion: 0.33
Nodes (6): Objetivos reordenables en formulario de semillero, Bug PUT /proposals excluia rol ESTUDIANTE (403), Fix notificaciones push (modelo PushSubscription, VAPID, push.service.js unificado), Principio requireRole() en todo el router, Sesion de validacion 2026-07-28 (auditoria completa), Validacion de unicidad de solicitudes (422 si Pendiente/Aprobada)

### Community 186 - "Envíos asíncronos: avisos push y correos por la cola (2026-10-03)"
Cohesion: 0.33
Nodes (5): Despliegue, Envíos asíncronos: avisos push y correos por la cola (2026-10-03), Para activar la cola (Jose), Pruebas, Qué cambió

### Community 187 - "pwa.js"
Cohesion: 0.40
Nodes (5): H, log(), scenario(), settle(), { startRelays, connect, sleep, BASE }

### Community 189 - "Importacion masiva de usuarios (POST /api/users/import)"
Cohesion: 0.50
Nodes (5): AccountActivationNotification / flujo /reset-password?activation=1, Importacion masiva de usuarios (POST /api/users/import), MAIL_MAILER=log -> SMTP real en produccion, Sesion 2026-08-31: importacion masiva de usuarios + activacion, Estado actual en produccion (ut-edu.online)

### Community 190 - "index.html — frontend PWA shell"
Cohesion: 0.40
Nodes (5): index.html — frontend PWA shell, app.js (módulo raíz frontend), manifest.json (PWA), css/pwa.css (capa PWA móvil), css/theme.css (design tokens)

### Community 191 - "Pruebas visuales automatizadas (Playwright sobre Chrome)"
Cohesion: 0.40
Nodes (4): Cómo funciona (por qué es tan particular), Preparación única (en el equipo de desarrollo), Pruebas visuales automatizadas (Playwright sobre Chrome), Uso

## Ambiguous Edges - Review These
- `App\Services\Rbac\PermissionResolver::can()` → `PermissionResolver::effective() (precedencia grupo/persona/rol)`  [AMBIGUOUS]
  docs/rbac/README.md · relation: calls

## Knowledge Gaps
- **611 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+606 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 999 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **180 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `App\Services\Rbac\PermissionResolver::can()` and `PermissionResolver::effective() (precedencia grupo/persona/rol)`?**
  _Edge tagged AMBIGUOUS (relation: calls) - confidence is low._
- **Why does `User` connect `User` to `TestCase`, `Group`, `SeedbedMember`, `SecurityRegressionTest`, `Illuminate\Http\Request`, `SeedbedCrudTest`, `Illuminate\Database\Seeder`, `CU04ForgotPasswordTest`, `SiaController`, `RequestManagementTest`, `AuditTest`, `Audit2_Cu22RequestTest`, `Cat`, `UserController.php`, `SiaTest`, `Audit`, `ResultCrudTest`, `Objective`, `RbacController`, `PermissionResolver`, `CoordinatorCrudTest`, `Permission`, `CU02GoogleLoginTest`, `Notificacion`, `Illuminate\Database\Eloquent\Model`, `CU27EvaluateProposalsTest`, `Proposal`, `CU01LoginTest`, `AuthTest`, `RequestCrudTest`, `Faculty`, `ProposalCrudTest`, `CU30AuditQueryTest`, `AuditFixesAuthUsersTest`, `CU26MyProposalsTest`, `RF03ProgramTest`, `Audit2_Cu25ProposalTest`, `CU05ProfileTest`, `GroqKeyPool`, `RNF03SecurityTest`, `RF16ConsentTest`, `ProposalStatusVocabularyTest`, `RNF05AuthorizationReferenceTest`, `CU28ReportsTest`?**
  _High betweenness centrality (0.307) - this node is a cross-community bridge._
- **Why does `CLAUDE.md — UT Semilleros` connect `CLAUDE.md — UT Semilleros` to `README — Manuales de usuario`, `CU29 Registrar auditoría (inclusión)`?**
  _High betweenness centrality (0.205) - this node is a cross-community bridge._
- **Why does `SIA — asistente IA (RF17 propuesto)` connect `README — Manuales de usuario` to `CLAUDE.md — UT Semilleros`?**
  _High betweenness centrality (0.161) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _611 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `router.js` be split into smaller, more focused modules?**
  _Cohesion score 0.10144927536231885 - nodes in this community are weakly interconnected._
- **Should `TestCase` be split into smaller, more focused modules?**
  _Cohesion score 0.07788461538461539 - nodes in this community are weakly interconnected._