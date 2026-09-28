# Graph Report - _public_html  (2026-09-27)

## Corpus Check
- 206 files · ~139,021 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1083 nodes · 2175 edges · 124 communities (58 shown, 35 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 77 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `10ee7c35`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- router.js
- composer.json
- User
- UserController
- TestCase
- Illuminate\Http\Request
- apiFetch
- LayoutView
- scripts
- SecurityRegressionTest
- Faculty
- Illuminate\Database\Seeder
- Roles y permisos — matriz de acceso validada contra backend real
- PushSubscription
- package.json
- manifest.json
- Seedbed
- index.html — PWA shell (frontend/index.html)
- Notificacion
- initLayoutController
- notifications.module.js
- UT Semilleros – Plataforma Web Académica
- Deployment on Hostinger Premium Web Hosting
- Illuminate\Database\Eloquent\Model
- Program
- AppServiceProvider.php
- Controller
- CatCrudTest
- Proposal
- ObjectiveCrudTest
- ProposalCrudTest
- ResultCrudTest
- escapeHtml
- Changelog — Sesión de validación 2026-07-28
- Objective
- Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas
- SeedbedCrudTest
- users.module.js
- Result
- initLoginController
- MembershipRequest
- SeedbedMemberTest
- docker-compose.yml — service orchestration
- Area
- Cat
- Coordinator
- Group
- RequestController
- Illuminate\Support\Str
- deploy-engineer.md
- dashboard.controller.js
- test-engineer.md
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- backend-tester.md
- Handler.php
- EnsureUserIsActive.php
- logging.php
- ExampleTest
- service-worker.js
- bug-historian.md
- console.php
- require-dev
- Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)
- Apple Touch Icon 152x152 (PWA branding icon)
- Apple Touch Icon 167x167 (PWA branding icon)
- Apple Touch Icon 180x180 (PWA branding icon)
- Login page background image
- Login page logo (Universidad del Tolima branding)
- PWA icon 192x192 (standard branding)
- PWA maskable icon 192x192 (Android adaptive branding)
- PWA icon 512x512 (standard branding)
- PWA maskable icon 512x512 (Android adaptive branding)
- config
- require
- code-reviewer.md
- psr-4
- CLAUDE.md — UT Semilleros
- autoload-dev
- extra
- db-architect.md
- Pending non-blocking improvements (push confirmation, proposal versioning, CAT Kennedy import)
- qa-design-mobile.md
- qa-design-web.md
- use-case-auditor.md
- architecture-advisor.md
- architecture-reviewer.md
- frontend-tester.md
- pwa-tester.md
- ui-designer.md
- GroupSeeder.php
- UserSeeder.php

## God Nodes (most connected - your core abstractions)
1. `User` - 162 edges
2. `apiFetch()` - 55 edges
3. `escapeHtml()` - 51 edges
4. `TestCase` - 42 edges
5. `Controller` - 41 edges
6. `Seedbed` - 37 edges
7. `Faculty` - 32 edges
8. `initLayoutController()` - 30 edges
9. `LayoutView()` - 30 edges
10. `Program` - 29 edges

## Surprising Connections (you probably didn't know these)
- `renderSkeleton()` --calls--> `LayoutView()`  [EXTRACTED]
  modules/pwa/pwa-requests.module.js → layout/layout.view.js
- `api service — Laravel backend, exposed on :8000` --runs--> `Laravel 12 + PHP 8.4 backend`  [INFERRED]
  docker-compose.yml → README.md
- `db service — mysql:9.0` --runs--> `MySQL 9.0 database`  [INFERRED]
  docker-compose.yml → README.md
- `app.js loaded as ES module entry point` --instance_of--> `Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)`  [INFERRED]
  index.html → docs/Arquitectura propuesta.txt
- `UT Semilleros – Plataforma Web Académica` --documented_by--> `Roles y permisos — matriz de acceso validada contra backend real`  [INFERRED]
  README.md → docs/roles-usuarios.md

## Import Cycles
- None detected.

## Communities (124 total, 35 thin omitted)

### Community 0 - "router.js"
Cohesion: 0.08
Nodes (32): createCrudModule(), bindEvents(), clearFieldError(), create(), init(), isValidEmail(), renderForm(), renderTable() (+24 more)

### Community 1 - "composer.json"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 2 - "User"
Cohesion: 0.06
Nodes (10): User, UserPolicy, AreaCrudTest, CoordinatorCrudTest, FacultyCrudTest, GroupCrudTest, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User (+2 more)

### Community 3 - "UserController"
Cohesion: 0.06
Nodes (16): UserController, ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, StoreUserRequest, UpdateUserRequest, AccountActivationNotification, CustomResetPasswordNotification (+8 more)

### Community 4 - "TestCase"
Cohesion: 0.07
Nodes (11): AuditTest, AuthTest, PasswordResetEndToEndTest, PasswordResetTest, ExampleTest, UserCrudTest, UserManagementTest, TestCase (+3 more)

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.11
Nodes (13): AuthController, Authenticate, UserResource, Illuminate\Auth\Middleware\Authenticate, Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware, Illuminate\Http\Request (+5 more)

### Community 6 - "apiFetch"
Cohesion: 0.12
Nodes (28): login(), logout(), bindEvents(), forgotPasswordModule, refreshBadge(), startBadgePolling(), updateBellBadge(), apiFetch() (+20 more)

### Community 7 - "LayoutView"
Cohesion: 0.17
Nodes (16): LayoutView(), initFacultiesController(), getFaculties(), FacultiesView(), renderSkeleton(), bindListEvents(), closeSheet(), loadAndRender() (+8 more)

### Community 8 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 10 - "Faculty"
Cohesion: 0.21
Nodes (4): FacultyController, Faculty, FacultySeeder, ProgramSeeder

### Community 11 - "Illuminate\Database\Seeder"
Cohesion: 0.14
Nodes (7): AreaSeeder, CatSeeder, CoordinatorSeeder, DatabaseSeeder, ObjectiveSeeder, ProposalSeeder, Illuminate\Database\Seeder

### Community 12 - "Roles y permisos — matriz de acceso validada contra backend real"
Cohesion: 0.14
Nodes (16): modules/auditoria/ — CU14, modules/catalogos/ — CU02-CU07 (facultades, programas, cat, areas, grupos, coordinadores), Arquitectura Derivada — modules/ mapped to casos de uso (CU01-CU14), modules/procesos/ — CU10-CU12 (solicitudes, propuestas, integrantes), modules/semilleros/ — CU08, CU09, CU13, modules/usuarios/ — CU01, Roles y permisos — matriz de acceso validada contra backend real, Account recovery and activation share /reset-password screen, differentiated by ?activation=1 query param, token valid 60 minutes (+8 more)

### Community 14 - "package.json"
Cohesion: 0.09
Nodes (19): devDependencies, axios, concurrently, laravel-vite-plugin, tailwindcss, @tailwindcss/vite, vite, private (+11 more)

### Community 15 - "manifest.json"
Cohesion: 0.12
Nodes (15): background_color, categories, description, display, display_override, icons, id, name (+7 more)

### Community 16 - "Seedbed"
Cohesion: 0.13
Nodes (5): SeedbedController, SeedbedMemberController, Seedbed, RequestSeeder, ResultSeeder

### Community 17 - "index.html — PWA shell (frontend/index.html)"
Cohesion: 0.20
Nodes (10): Chart.js 4.4.0 (CDN) for dashboard charts, DataTables + Buttons (CDN) for tabular UI and export, css/theme.css — design tokens source of truth, iOS/Safari PWA meta tags (apple-mobile-web-app-*, apple-touch-icon) — Safari ignores manifest.json for most PWA settings, index.html — PWA shell (frontend/index.html), black-translucent status bar chosen so app gradient fills screen, requires safe-area-inset-top padding, SweetAlert2 (CDN) for modals/alerts, Tailwind CSS via CDN script (+2 more)

### Community 18 - "Notificacion"
Cohesion: 0.20
Nodes (5): NotificacionController, Notificacion, NotificacionRead, Minishlink\WebPush\Subscription, Minishlink\WebPush\WebPush

### Community 19 - "initLayoutController"
Cohesion: 0.16
Nodes (18): applyTheme(), _closeSidebar(), initLayoutController(), _openSidebar(), PAGE_TITLES, _registerNavListener(), syncThemeIcon(), projectMembersModule (+10 more)

### Community 20 - "notifications.module.js"
Cohesion: 0.26
Nodes (13): bindEvents(), loadAndRender(), loadTargetValues(), notificationsModule, renderCard(), renderComposer(), renderPage(), ROLE_CAN_SEND (+5 more)

### Community 21 - "UT Semilleros – Plataforma Web Académica"
Cohesion: 0.17
Nodes (13): Laravel framework (vendor README), Conventional Commits standard adopted for commit history, Public repo — no real secrets committed, only where credentials live is documented, apache/ — custom Apache configuration (laravel.conf), api/ — Laravel 12 source code, UT Semilleros – Plataforma Web Académica, Secure authentication with Laravel Sanctum or Passport, Modular architecture based on microservices and RESTful APIs (+5 more)

### Community 22 - "Deployment on Hostinger Premium Web Hosting"
Cohesion: 0.50
Nodes (4): Deployment on Hostinger Premium Web Hosting, System deployed to production at https://ut-edu.online/, api/public set as domain root with .htaccess rewrites, api/public/robots.txt — allow all crawling

### Community 23 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.25
Nodes (4): Audit, AuditObserver, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 24 - "Program"
Cohesion: 0.13
Nodes (4): ProgramController, Program, SeedbedSeeder, ProgramCrudTest

### Community 25 - "AppServiceProvider.php"
Cohesion: 0.21
Nodes (4): ProductController, Product, AppServiceProvider, Illuminate\Support\ServiceProvider

### Community 26 - "Controller"
Cohesion: 0.15
Nodes (6): AuditController, ProjectController, ProjectMemberController, Controller, Project, Illuminate\Support\Facades\Route

### Community 32 - "escapeHtml"
Cohesion: 0.12
Nodes (28): escapeHtml(), ESCAPES, safeImageSrc(), safeUrl(), BOTTOM_NAV, bindEvents(), render(), resetPasswordModule (+20 more)

### Community 33 - "Changelog — Sesión de validación 2026-07-28"
Cohesion: 0.25
Nodes (8): Changelog — Sesión de validación 2026-07-28, Dashboard, Deuda técnica resuelta en esta ronda, Notificaciones push, Propuestas (estudiante), PWA / instalación, Semilleros, Solicitudes (postulación a semillero)

### Community 35 - "Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas"
Cohesion: 0.29
Nodes (6): Bugs reales encontrados, Contexto, Funcionalidad nueva: importación masiva de usuarios, Pendiente (no bloqueante), Sesión 2026-08-31 — Importación masiva de usuarios + activación de cuentas, Verificación end-to-end (2026-08-31, contra producción real)

### Community 37 - "users.module.js"
Cohesion: 0.33
Nodes (8): bindImportModalEvents(), renderRows(), importRowHtml(), openImportModal(), parseImportInput(), ROLES, suggestNameFromEmail(), usersModule

### Community 39 - "initLoginController"
Cohesion: 0.52
Nodes (6): initLoginController(), clearError(), isValidEmail(), showError(), validateEmail(), validatePassword()

### Community 42 - "docker-compose.yml — service orchestration"
Cohesion: 0.27
Nodes (10): docker-compose.yml — service orchestration, Frontend volume mounted live — changes reflect without rebuild, Real schema applied via php artisan migrate --seed inside api container, not init.sql, api service — Laravel backend, exposed on :8000, db service — mysql:9.0, frontend service — serves static PWA (index.html, modules/), phpmyadmin service — phpmyadmin:5.2.2, db/ — database configuration and persistent data (+2 more)

### Community 48 - "Illuminate\Support\Str"
Cohesion: 0.29
Nodes (3): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str

### Community 49 - "deploy-engineer.md"
Cohesion: 0.33
Nodes (5): Brechas reales Docker → Hostinger (verifícalas en cada deploy, no las asumas), Entrega, Flujo (el repo es la fuente del código; el servidor no tiene git), Higiene de exposición pública (hallazgos 2026-09-26), Validación en vivo (obligatoria; "se subió" no es "funciona")

### Community 50 - "dashboard.controller.js"
Cohesion: 0.46
Nodes (7): buildSeedbedsByFaculty(), emptyChart(), hideKPI(), initDashboardController(), loadCharts(), loadKPIs(), setKPI()

### Community 51 - "test-engineer.md"
Cohesion: 0.33
Nodes (5): Ambientes, Checklist de flujos (corre todos los que toquen el cambio y su código adyacente), Entrega, Honestidad sobre la suite de pruebas, Reglas de aislamiento y limpieza (obligatorias)

### Community 55 - "backend-tester.md"
Cohesion: 0.40
Nodes (4): Datos y limpieza (obligatorio), Entrega, Integración de lo desplegado (Hostinger), Por cada caso de uso (CU01–CU14)

### Community 56 - "Handler.php"
Cohesion: 0.33
Nodes (4): Handler, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 57 - "EnsureUserIsActive.php"
Cohesion: 0.43
Nodes (4): EnsureUserIsActive, RoleMiddleware, Closure, Symfony\Component\HttpFoundation\Response

### Community 58 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 82 - "bug-historian.md"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en este orden, Patrones de bug ya vividos (compara el síntoma nuevo contra estos primero), Reglas

### Community 84 - "require-dev"
Cohesion: 0.25
Nodes (8): require-dev, fakerphp/faker, laravel/pail, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit

### Community 94 - "Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)"
Cohesion: 0.25
Nodes (8): components/ — navbar.js, loader.js, core/ — router.js, guards.js, state.js, modules/{feature}/ pattern — view + service + controller per feature, services/ — api.service.js, storage.service.js, Arquitectura propuesta — frontend/ folder layout (core, modules, services, components), Arquitectura — semilleros-pwa/ top-level topology (frontend/backend/database split), estructura.txt — minimal frontend/ layout (Dockerfile, index.html, app.js, manifest.json, service-worker.js, icon.png temporal), app.js loaded as ES module entry point

### Community 104 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 105 - "require"
Cohesion: 0.33
Nodes (6): require, laravel/framework, laravel/sanctum, laravel/tinker, minishlink/web-push, php

### Community 106 - "code-reviewer.md"
Cohesion: 0.40
Nodes (4): Checklist específico del proyecto, Entrega, Qué revisar primero, Reglas

### Community 107 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 108 - "CLAUDE.md — UT Semilleros"
Cohesion: 0.17
Nodes (11): Agentes disponibles (`.claude/agents/`), Antes de trabajar aquí, CLAUDE.md — UT Semilleros, Cómo está desplegado de verdad (verificado 2026-09-26), Despliegue, Diseño, Entorno de pruebas local (Docker, aislado de producción), Grafo de conocimiento (graphify) (+3 more)

### Community 109 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 110 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 111 - "db-architect.md"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes, en orden de confianza, Qué validas, Reglas

### Community 114 - "qa-design-mobile.md"
Cohesion: 0.40
Nodes (4): Entrega, Qué revisar en la UI móvil, Setup, Validación PWA (automática, este proyecto la necesita)

### Community 115 - "qa-design-web.md"
Cohesion: 0.40
Nodes (4): Entrega, Falsos positivos ya descartados (no los reportes de nuevo sin evidencia nueva), Qué revisar, Setup

### Community 116 - "use-case-auditor.md"
Cohesion: 0.40
Nodes (4): Entrega, Fuentes de reglas (en orden), Método (para cada regla), Reglas

### Community 117 - "architecture-advisor.md"
Cohesion: 0.50
Nodes (3): Arquitectura real (verifícala con `graphify` antes de opinar), Cómo respondes, Reglas

### Community 118 - "architecture-reviewer.md"
Cohesion: 0.50
Nodes (3): Formato, Recorrido obligatorio (usa `graphify query/path/explain` en cada punto), Trabajo en equipo

### Community 119 - "frontend-tester.md"
Cohesion: 0.50
Nodes (3): Ambientes y datos, Entrega, Qué pruebas, por cada caso de uso y rol

### Community 120 - "pwa-tester.md"
Cohesion: 0.50
Nodes (3): Checklist, Datos, Entrega

### Community 121 - "ui-designer.md"
Cohesion: 0.50
Nodes (3): Alcance, Reglas, Trabajo en equipo

## Knowledge Gaps
- **203 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+198 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 375 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **35 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `UserController`, `TestCase`, `Illuminate\Http\Request`, `SeedbedCrudTest`, `CatCrudTest`, `MembershipRequest`, `SecurityRegressionTest`, `Illuminate\Database\Seeder`, `SeedbedMemberTest`, `Seedbed`, `Illuminate\Database\Eloquent\Model`, `Program`, `AppServiceProvider.php`, `UserSeeder.php`, `ObjectiveCrudTest`, `ProposalCrudTest`, `ResultCrudTest`?**
  _High betweenness centrality (0.104) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `Objective`, `UserController`, `Illuminate\Http\Request`, `Result`, `Faculty`, `Area`, `Cat`, `Coordinator`, `Group`, `PushSubscription`, `RequestController`, `Seedbed`, `Notificacion`, `Program`, `AppServiceProvider.php`, `Proposal`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `TestCase` connect `TestCase` to `User`, `SeedbedCrudTest`, `MembershipRequest`, `SecurityRegressionTest`, `SeedbedMemberTest`, `Program`, `CatCrudTest`, `ObjectiveCrudTest`, `ProposalCrudTest`, `ResultCrudTest`?**
  _High betweenness centrality (0.016) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _203 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `router.js` be split into smaller, more focused modules?**
  _Cohesion score 0.08 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.06095791001451379 - nodes in this community are weakly interconnected._
- **Should `UserController` be split into smaller, more focused modules?**
  _Cohesion score 0.06086956521739131 - nodes in this community are weakly interconnected._