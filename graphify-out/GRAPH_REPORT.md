# Graph Report - _public_html  (2026-09-26)

## Corpus Check
- 189 files · ~130,513 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 987 nodes · 1987 edges · 114 communities (46 shown, 37 thin omitted)
- Extraction: 96% EXTRACTED · 4% INFERRED · 0% AMBIGUOUS · INFERRED: 77 edges (avg confidence: 0.92)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2aadc671`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- router.js
- composer.json
- User
- UserController
- TestCase
- Illuminate\Http\Request
- storage.service.js
- LayoutView
- scripts
- apiFetch
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
- AccountActivationNotification
- Proposal
- ObjectiveCrudTest
- ProgramCrudTest
- ResultCrudTest
- pwa-profile.module.js
- Changelog — Sesión de validación 2026-07-28
- Objective
- GroupCrudTest
- SeedbedCrudTest
- users.module.js
- Result
- AreaCrudTest
- RequestCrudTest
- SeedbedMemberTest
- docker-compose.yml — service orchestration
- Area
- Cat
- Coordinator
- Group
- RequestController
- Illuminate\Support\Str
- pwa-requests.module.js
- dashboard.controller.js
- MembershipRequest
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- UserCrudTest
- Handler.php
- RoleMiddleware.php
- logging.php
- ExampleTest
- service-worker.js
- ObjectiveSeeder.php
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
- bootstrap/app.php
- psr-4
- CLAUDE.md — UT Semilleros
- autoload-dev
- extra
- ProposalSeeder.php
- Pending non-blocking improvements (push confirmation, proposal versioning, CAT Kennedy import)

## God Nodes (most connected - your core abstractions)
1. `User` - 150 edges
2. `apiFetch()` - 55 edges
3. `Controller` - 41 edges
4. `TestCase` - 40 edges
5. `Seedbed` - 35 edges
6. `Faculty` - 30 edges
7. `initLayoutController()` - 30 edges
8. `createCrudModule()` - 28 edges
9. `LayoutView()` - 28 edges
10. `Program` - 27 edges

## Surprising Connections (you probably didn't know these)
- `renderSkeleton()` --calls--> `LayoutView()`  [EXTRACTED]
  modules/pwa/pwa-requests.module.js → layout/layout.view.js
- `UT Semilleros – Plataforma Web Académica` --documented_by--> `Roles y permisos — matriz de acceso validada contra backend real`  [INFERRED]
  README.md → docs/roles-usuarios.md
- `app.js loaded as ES module entry point` --instance_of--> `Arquitectura propuesta — frontend/ folder layout (core, modules, services, components)`  [INFERRED]
  index.html → docs/Arquitectura propuesta.txt
- `Arquitectura — semilleros-pwa/ top-level topology (frontend/backend/database split)` --related_to--> `docker-compose.yml — service orchestration`  [INFERRED]
  docs/Arquitectura.txt → docker-compose.yml
- `api service — Laravel backend, exposed on :8000` --runs--> `Laravel 12 + PHP 8.4 backend`  [INFERRED]
  docker-compose.yml → README.md

## Import Cycles
- None detected.

## Communities (114 total, 37 thin omitted)

### Community 0 - "router.js"
Cohesion: 0.09
Nodes (28): createCrudModule(), bindEvents(), clearFieldError(), create(), init(), isValidEmail(), renderForm(), renderTable() (+20 more)

### Community 1 - "composer.json"
Cohesion: 0.22
Nodes (8): description, keywords, license, minimum-stability, name, prefer-stable, $schema, type

### Community 2 - "User"
Cohesion: 0.08
Nodes (9): User, UserPolicy, CatCrudTest, CoordinatorCrudTest, UserManagementTest, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable (+1 more)

### Community 3 - "UserController"
Cohesion: 0.09
Nodes (9): UserController, ForgotPasswordRequest, LoginRequest, ResetPasswordRequest, StoreUserRequest, UpdateUserRequest, Illuminate\Foundation\Http\FormRequest, Illuminate\Http\JsonResponse (+1 more)

### Community 4 - "TestCase"
Cohesion: 0.09
Nodes (8): AuthTest, PasswordResetEndToEndTest, PasswordResetTest, ExampleTest, ProposalCrudTest, TestCase, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase

### Community 5 - "Illuminate\Http\Request"
Cohesion: 0.14
Nodes (10): AuthController, Authenticate, UserResource, Illuminate\Auth\Middleware\Authenticate, Illuminate\Http\Request, Illuminate\Http\Resources\Json\JsonResource, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Password (+2 more)

### Community 6 - "storage.service.js"
Cohesion: 0.15
Nodes (20): requireAuth(), requireRole(), initLoginController(), clearError(), isValidEmail(), showError(), validateEmail(), validatePassword() (+12 more)

### Community 7 - "LayoutView"
Cohesion: 0.13
Nodes (23): BOTTOM_NAV, LayoutView(), initFacultiesController(), getFaculties(), FacultiesView(), bindListEvents(), closeSheet(), loadAndRender() (+15 more)

### Community 8 - "scripts"
Cohesion: 0.22
Nodes (9): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+1 more)

### Community 9 - "apiFetch"
Cohesion: 0.14
Nodes (19): bindEvents(), forgotPasswordModule, bindEvents(), resetPasswordModule, renderMembers(), seedbedMembersModule, objectiveRowHtml(), seedbedsModule (+11 more)

### Community 10 - "Faculty"
Cohesion: 0.10
Nodes (4): Faculty, AuditTest, FacultyCrudTest, Laravel\Sanctum\Sanctum

### Community 11 - "Illuminate\Database\Seeder"
Cohesion: 0.12
Nodes (8): AreaSeeder, CatSeeder, CoordinatorSeeder, DatabaseSeeder, FacultySeeder, GroupSeeder, UserSeeder, Illuminate\Database\Seeder

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
Cohesion: 0.15
Nodes (4): SeedbedController, SeedbedMemberController, Seedbed, ResultSeeder

### Community 17 - "index.html — PWA shell (frontend/index.html)"
Cohesion: 0.20
Nodes (10): Chart.js 4.4.0 (CDN) for dashboard charts, DataTables + Buttons (CDN) for tabular UI and export, css/theme.css — design tokens source of truth, iOS/Safari PWA meta tags (apple-mobile-web-app-*, apple-touch-icon) — Safari ignores manifest.json for most PWA settings, index.html — PWA shell (frontend/index.html), black-translucent status bar chosen so app gradient fills screen, requires safe-area-inset-top padding, SweetAlert2 (CDN) for modals/alerts, Tailwind CSS via CDN script (+2 more)

### Community 18 - "Notificacion"
Cohesion: 0.20
Nodes (5): NotificacionController, Notificacion, NotificacionRead, Minishlink\WebPush\Subscription, Minishlink\WebPush\WebPush

### Community 19 - "initLayoutController"
Cohesion: 0.20
Nodes (13): applyTheme(), _closeSidebar(), initLayoutController(), _openSidebar(), PAGE_TITLES, _registerNavListener(), syncThemeIcon(), refreshBadge() (+5 more)

### Community 20 - "notifications.module.js"
Cohesion: 0.22
Nodes (16): DashboardView(), bindEvents(), loadAndRender(), loadTargetValues(), notificationsModule, renderCard(), renderComposer(), renderPage() (+8 more)

### Community 21 - "UT Semilleros – Plataforma Web Académica"
Cohesion: 0.15
Nodes (14): Laravel framework (vendor README), Conventional Commits standard adopted for commit history, Public repo — no real secrets committed, only where credentials live is documented, apache/ — custom Apache configuration (laravel.conf), api/ — Laravel 12 source code, UT Semilleros – Plataforma Web Académica, Secure authentication with Laravel Sanctum or Passport, Modular architecture based on microservices and RESTful APIs (+6 more)

### Community 22 - "Deployment on Hostinger Premium Web Hosting"
Cohesion: 0.50
Nodes (4): Deployment on Hostinger Premium Web Hosting, System deployed to production at https://ut-edu.online/, api/public set as domain root with .htaccess rewrites, api/public/robots.txt — allow all crawling

### Community 23 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.25
Nodes (4): Audit, AuditObserver, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 24 - "Program"
Cohesion: 0.19
Nodes (4): ProgramController, Program, ProgramSeeder, SeedbedSeeder

### Community 25 - "AppServiceProvider.php"
Cohesion: 0.21
Nodes (4): ProductController, Product, AppServiceProvider, Illuminate\Support\ServiceProvider

### Community 26 - "Controller"
Cohesion: 0.11
Nodes (7): AuditController, FacultyController, ProjectController, ProjectMemberController, Controller, Project, Illuminate\Support\Facades\Route

### Community 27 - "AccountActivationNotification"
Cohesion: 0.20
Nodes (7): AccountActivationNotification, CustomResetPasswordNotification, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Bus\Queueable, Illuminate\Notifications\Messages\MailMessage, Illuminate\Notifications\Notification, MailMessage

### Community 32 - "pwa-profile.module.js"
Cohesion: 0.29
Nodes (9): bindEvents(), clearErrors(), compressImage(), pwaProfileModule, renderProfile(), ROLE_LABELS, showBanner(), showError() (+1 more)

### Community 33 - "Changelog — Sesión de validación 2026-07-28"
Cohesion: 0.13
Nodes (14): Bugs reales encontrados, Changelog — Sesión de validación 2026-07-28, Contexto, Dashboard, Deuda técnica resuelta en esta ronda, Funcionalidad nueva: importación masiva de usuarios, Notificaciones push, Pendiente (no bloqueante) (+6 more)

### Community 37 - "users.module.js"
Cohesion: 0.33
Nodes (9): bindImportModalEvents(), renderRows(), escapeHtml(), importRowHtml(), openImportModal(), parseImportInput(), ROLES, suggestNameFromEmail() (+1 more)

### Community 42 - "docker-compose.yml — service orchestration"
Cohesion: 0.31
Nodes (9): docker-compose.yml — service orchestration, Frontend volume mounted live — changes reflect without rebuild, Real schema applied via php artisan migrate --seed inside api container, not init.sql, api service — Laravel backend, exposed on :8000, db service — mysql:9.0, frontend service — serves static PWA (index.html, modules/), phpmyadmin service — phpmyadmin:5.2.2, db/ — database configuration and persistent data (+1 more)

### Community 48 - "Illuminate\Support\Str"
Cohesion: 0.29
Nodes (3): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str

### Community 49 - "pwa-requests.module.js"
Cohesion: 0.33
Nodes (8): bindEvents(), loadAndRender(), loadSeedbedsSelect(), pwaRequestsModule, renderError(), renderList(), renderSkeleton(), STATUS_MAP

### Community 50 - "dashboard.controller.js"
Cohesion: 0.46
Nodes (7): buildSeedbedsByFaculty(), emptyChart(), hideKPI(), initDashboardController(), loadCharts(), loadKPIs(), setKPI()

### Community 56 - "Handler.php"
Cohesion: 0.33
Nodes (4): Handler, Illuminate\Auth\AuthenticationException, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 57 - "RoleMiddleware.php"
Cohesion: 0.60
Nodes (3): RoleMiddleware, Closure, Symfony\Component\HttpFoundation\Response

### Community 58 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

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

### Community 106 - "bootstrap/app.php"
Cohesion: 0.40
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 107 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 108 - "CLAUDE.md — UT Semilleros"
Cohesion: 0.50
Nodes (3): Antes de trabajar aquí, CLAUDE.md — UT Semilleros, Despliegue

### Community 109 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 110 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **143 isolated node(s):** `$schema`, `name`, `type`, `description`, `keywords` (+138 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 316 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **37 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `UserController`, `TestCase`, `Illuminate\Http\Request`, `Faculty`, `Illuminate\Database\Seeder`, `Illuminate\Database\Eloquent\Model`, `AppServiceProvider.php`, `AccountActivationNotification`, `ObjectiveCrudTest`, `ProgramCrudTest`, `ResultCrudTest`, `GroupCrudTest`, `SeedbedCrudTest`, `AreaCrudTest`, `RequestCrudTest`, `SeedbedMemberTest`, `MembershipRequest`, `UserCrudTest`, `ProposalSeeder.php`?**
  _High betweenness centrality (0.122) - this node is a cross-community bridge._
- **Why does `Controller` connect `Controller` to `Objective`, `UserController`, `Illuminate\Http\Request`, `Result`, `Area`, `Cat`, `Coordinator`, `Group`, `PushSubscription`, `RequestController`, `Seedbed`, `Notificacion`, `Program`, `AppServiceProvider.php`, `Proposal`?**
  _High betweenness centrality (0.028) - this node is a cross-community bridge._
- **Why does `Seedbed` connect `Seedbed` to `SeedbedCrudTest`, `RequestCrudTest`, `SeedbedMemberTest`, `Faculty`, `ObjectiveSeeder.php`, `MembershipRequest`, `Illuminate\Database\Eloquent\Model`, `Program`, `AppServiceProvider.php`, `ObjectiveCrudTest`, `ResultCrudTest`?**
  _High betweenness centrality (0.017) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _143 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `router.js` be split into smaller, more focused modules?**
  _Cohesion score 0.08668076109936575 - nodes in this community are weakly interconnected._
- **Should `User` be split into smaller, more focused modules?**
  _Cohesion score 0.07948717948717948 - nodes in this community are weakly interconnected._
- **Should `UserController` be split into smaller, more focused modules?**
  _Cohesion score 0.08870967741935484 - nodes in this community are weakly interconnected._