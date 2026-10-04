<?php
// #archivo: /backend/routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiaController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FacultyController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\CoordinatorController;
use App\Http\Controllers\Api\SeedbedController;
use App\Http\Controllers\Api\SeedbedMemberController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CatController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ObjectiveController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\RbacController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS — no requieren autenticación
|--------------------------------------------------------------------------
*/

/* CU01-H1: se quitó POST /register — apuntaba a un método inexistente y
   respondía 500. Las cuentas las crea el ADMIN_SISTEMA (CU06) o el alta con
   Google (CU02); no hay autorregistro. */
Route::post('/login',            [AuthController::class, 'login']);
Route::post('/forgot-password',  [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',   [AuthController::class, 'resetPassword']);

/* CU02 — Google institucional. tokeninfo es una llamada externa: se limita
   por IP para que el endpoint no sirva de amplificador. */
Route::get('/auth/config',       [AuthController::class, 'authConfig']);
Route::middleware('throttle:60,1')
     ->post('/auth/google',      [AuthController::class, 'google']);

/* SIA — asistente con IA (RF17 propuesto). Público: también se usa en el login.
   Límites por IP/usuario/conversación y tope global en SiaController; el throttle
   por minuto frena ráfagas antes de tocar la BD. */
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/sia/chat',  [SiaController::class, 'chat']);
    Route::post('/sia/close', [SiaController::class, 'close']);
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS — requieren token Sanctum
|
| NOTA IMPORTANTE sobre diseño de rutas:
|   Cada URL+método debe aparecer UNA SOLA VEZ.
|   Laravel solo hace match con la primera ruta registrada para
|   method+path. Rutas duplicadas en grupos separados se ignoran.
|
|   Por eso usamos role:A,B,C en lugar de grupos separados por rol.
|   El RoleMiddleware acepta múltiples roles vía variadic: ...$roles
|--------------------------------------------------------------------------
|
|   Abreviaciones usadas en comentarios:
|     A   = ADMIN_SISTEMA
|     L   = LIDER_SEMILLERO
|     ADM = ADMINISTRATIVO
|     E   = ESTUDIANTE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active', 'consent'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Sesión (todos los roles autenticados)
    |----------------------------------------------------------------------
    */

    Route::get('/me',                [AuthController::class, 'me']);
    Route::put('/profile',           [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo',    [AuthController::class, 'updatePhoto']);
    Route::delete('/profile/photo',  [AuthController::class, 'deletePhoto']);
    Route::post('/logout',           [AuthController::class, 'logout']);
    Route::post('/consent',          [AuthController::class, 'consent']);   /* RF16 */

    /*
    |----------------------------------------------------------------------
    | USUARIOS (RF01 / CU01)
    |   GET list      → A (completo); L, ADM (mínimo: id, nombre, rol, estado — para selectores)
    |   GET show      → A
    |   POST/PUT/TOGGLE → A
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/users', [UserController::class, 'index']);

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::get('/users/{id}',                    [UserController::class, 'show']);
        Route::post('/users',                        [UserController::class, 'store']);
        Route::post('/users/import',                 [UserController::class, 'import']);
        Route::put('/users/{id}',                    [UserController::class, 'update']);
        Route::put('/users/{id}/toggle-status',      [UserController::class, 'toggleStatus']);
        Route::post('/users/{id}/resend-activation', [UserController::class, 'resendActivation']);
    });

    /*
    |----------------------------------------------------------------------
    | FACULTADES (RF02 / CU02)
    |   GET  → A, L, ADM
    |   WRITE → A
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::get('/faculties',      [FacultyController::class, 'index']);
        Route::get('/faculties/{id}', [FacultyController::class, 'show']);
    });

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::post('/faculties',                    [FacultyController::class, 'store']);
        Route::put('/faculties/{id}',                [FacultyController::class, 'update']);
        Route::put('/faculties/{id}/toggle-status',  [FacultyController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | PROGRAMAS (RF03 / CU03)
    |   GET  → A, L, ADM
    |   WRITE → A
    |----------------------------------------------------------------------
    */

    /* CU25: el ESTUDIANTE necesita leer los programas para elegir uno al
       crear su propuesta — mismo hallazgo que ya se corrigió en /areas
       (2026-09-29): sin esto, el selector de programa del formulario
       quedaría vacío por un 403. */
    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')->group(function () {
        Route::get('/programs',      [ProgramController::class, 'index']);
        Route::get('/programs/{id}', [ProgramController::class, 'show']);
    });

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::post('/programs',                     [ProgramController::class, 'store']);
        Route::put('/programs/{id}',                 [ProgramController::class, 'update']);
        Route::put('/programs/{id}/toggle-status',   [ProgramController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | CAT (RF04 / CU04)
    |   GET  → A, L, ADM
    |   WRITE → A
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::get('/cats',      [CatController::class, 'index']);
        Route::get('/cats/{id}', [CatController::class, 'show']);
    });

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::post('/cats',                         [CatController::class, 'store']);
        Route::put('/cats/{id}',                     [CatController::class, 'update']);
        Route::put('/cats/{id}/toggle-status',       [CatController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | ÁREAS (RF05 / CU05)
    |   GET  → A, L, ADM
    |   WRITE → A
    |----------------------------------------------------------------------
    */

    /* RF05: el ESTUDIANTE necesita leer las áreas para elegir una al crear su
       propuesta (POST /proposals sí lo permite) — bug encontrado en la
       validación de RF05, 2026-09-29: podía crear la propuesta pero nunca
       veía el selector de área porque este GET le daba 403. */
    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')->group(function () {
        Route::get('/areas',      [AreaController::class, 'index']);
        Route::get('/areas/{id}', [AreaController::class, 'show']);
    });

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::post('/areas',                        [AreaController::class, 'store']);
        Route::put('/areas/{id}',                    [AreaController::class, 'update']);
        Route::put('/areas/{id}/toggle-status',      [AreaController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | GRUPOS (RF06 / CU11)
    |   GET  → A, L, ADM
    |   WRITE → L (actor principal de CU11; A también, por consistencia
    |     administrativa con el resto de catálogos — ADM solo consulta, A5)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::get('/groups',      [GroupController::class, 'index']);
        Route::get('/groups/{id}', [GroupController::class, 'show']);
    });

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO')->group(function () {
        Route::post('/groups',                       [GroupController::class, 'store']);
        Route::put('/groups/{id}',                   [GroupController::class, 'update']);
        Route::put('/groups/{id}/toggle-status',     [GroupController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | COORDINADORES (RF07 / CU07)
    |   GET  → A, L, ADM (A5: L y ADM solo consulta)
    |   WRITE → A (CU12, 2026-09-29: antes L también escribía — desalineado
    |     con la spec, corregido; Jose aprobó restringir solo a A)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::get('/coordinators',      [CoordinatorController::class, 'index']);
        Route::get('/coordinators/{id}', [CoordinatorController::class, 'show']);
    });

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::post('/coordinators',                 [CoordinatorController::class, 'store']);
        Route::put('/coordinators/{id}',             [CoordinatorController::class, 'update']);
        Route::put('/coordinators/{id}/toggle-status', [CoordinatorController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | SEMILLEROS (RF13 / CU13-CU16)
    |   GET list+show → todos los roles (A, L, ADM, E)
    |   POST/PUT/TOGGLE → L, ADM_SISTEMA (CU16 A2, 2026-09-30: Administrativo
    |     consulta pero no edita — revierte la decisión previa del
    |     2026-07-28 para alinear con la especificación de CU16)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->get('/seedbeds', [SeedbedController::class, 'index']);

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->get('/seedbeds/{id}', [SeedbedController::class, 'show']);

    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA')->group(function () {
        Route::post('/seedbeds',                         [SeedbedController::class, 'store']);
        Route::put('/seedbeds/{id}',                     [SeedbedController::class, 'update']);
        Route::put('/seedbeds/{id}/toggle-status',       [SeedbedController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | INTEGRANTES SEMILLEROS (RF12 / CU21)
    |   GET → L, ADM, ADMINISTRATIVO (solo consulta)
    |   POST/PUT/TOGGLE → L, ADM
    |----------------------------------------------------------------------
    */

    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA,ADMINISTRATIVO')
         ->get('/seedbeds/{id}/members', [SeedbedMemberController::class, 'index']);

    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA')->group(function () {
        Route::post('/seedbeds/{id}/members',                          [SeedbedMemberController::class, 'store']);
        Route::put('/seedbeds/{seedbedId}/members/{id}',                [SeedbedMemberController::class, 'update']);
        Route::put('/seedbeds/{seedbedId}/members/{id}/toggle-status', [SeedbedMemberController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | OBJETIVOS (RF08 / CU19)
    |   GET list → todos los roles
    |   POST/PUT/TOGGLE → L, ADMIN_SISTEMA (CU19, 2026-09-30: el actor
    |     secundario Administrativo es "solo consulta" — se le quita
    |     escritura, mismo criterio ya aplicado a semilleros en CU16)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->get('/objectives', [ObjectiveController::class, 'index']);

    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA')->group(function () {
        Route::post('/objectives',                   [ObjectiveController::class, 'store']);
        Route::put('/objectives/{id}',               [ObjectiveController::class, 'update']);
        Route::put('/objectives/{id}/toggle-status', [ObjectiveController::class, 'toggleStatus']);
        Route::delete('/objectives/{id}',             [ObjectiveController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | RESULTADOS (RF09 / CU09)
    |   GET list → A, L, ADM
    |   POST/PUT/TOGGLE → L, ADM
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/results', [ResultController::class, 'index']);

    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA')->group(function () {
        Route::post('/results',                      [ResultController::class, 'store']);
        Route::put('/results/{id}',                  [ResultController::class, 'update']);
        Route::put('/results/{id}/toggle-status',    [ResultController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | SOLICITUDES (RF10 / CU22, CU23, CU24)
    |   GET list, GET {id} → A, L, ADM (CU24: el líder solo ve las de sus
    |                         semilleros, RN06; Administrativo solo consulta)
    |   POST      → L, ADM, E (CU22: crear la suya)
    |   update-status → L, ADM (CU24: aprobar/rechazar; RN06 en el controller)
    |   GET /my   → E (CU23: sus propias solicitudes)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/requests', [RequestController::class, 'index']);

    /* GET propias — solo E. Va antes que /requests/{id} para que "my" no se
       interprete como un id. */
    Route::middleware('role:ESTUDIANTE')
         ->get('/requests/my', [RequestController::class, 'myRequests']);

    /* GET /requests/eligibility — ¿puede postularse? (CU22); va antes de /requests/{id} por la misma razón. */
    Route::middleware('role:ESTUDIANTE')
         ->get('/requests/eligibility', [RequestController::class, 'eligibility']);

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/requests/{id}', [RequestController::class, 'show'])
         ->whereNumber('id');

    /* POST /requests — solo ESTUDIANTE (CU22: la spec le da este caso de uso solo al estudiante; antes también L/ADM) */
    Route::middleware('role:ESTUDIANTE')
         ->post('/requests', [RequestController::class, 'store']);

    /* Aprobar/rechazar — solo L y ADM (CU24-A3: Administrativo solo consulta) */
    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA')
         ->put('/requests/{id}/update-status', [RequestController::class, 'updateStatus']);

    /*
    |----------------------------------------------------------------------
    | PROPUESTAS (RF11 / CU25, CU26, CU27)
    |   GET list, GET {id} → A, L (solo las de las áreas de sus semilleros, A1), ADM
    |   POST   → E (CU25: registra la suya)
    |   PUT    → E (edita la suya mientras esté Recibida; extensión de CU25/CU26)
    |   update-status → ADM (CU27: Marcar viable / Archivar; E2: 403 al resto)
    |   GET /my → E (CU26)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/proposals', [ProposalController::class, 'index']);

    /* GET /proposals/my va antes que /proposals/{id}; {id} es numérico. */
    Route::middleware('role:ESTUDIANTE')
         ->get('/proposals/my', [ProposalController::class, 'myProposals']);

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/proposals/{id}', [ProposalController::class, 'show'])
         ->whereNumber('id');

    /* CU25: solo el estudiante registra propuestas (antes también L y ADM a nombre de otro, CU25-H4). */
    Route::middleware('role:ESTUDIANTE')
         ->post('/proposals', [ProposalController::class, 'store']);

    Route::middleware('role:ESTUDIANTE')
         ->put('/proposals/{id}', [ProposalController::class, 'update']);

    /* CU27: solo el Administrativo evalúa (spec); el Líder solo consulta y el Administrador del sistema no es actor. */
    Route::middleware('role:ADMINISTRATIVO')
         ->put('/proposals/{id}/update-status', [ProposalController::class, 'updateStatus']);

    /*
    |----------------------------------------------------------------------
    | PROYECTOS — acceso A, L, ADM
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::get('/projects',          [ProjectController::class, 'index']);
        Route::post('/projects',         [ProjectController::class, 'store']);
        Route::put('/projects/{id}',     [ProjectController::class, 'update']);
        Route::get('/projects/{id}/members',               [ProjectMemberController::class, 'index']);
        Route::post('/projects/{id}/members',              [ProjectMemberController::class, 'store']);
        Route::delete('/projects/{projectId}/members/{userId}', [ProjectMemberController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | PRODUCTOS — acceso A, L, ADM
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::get('/products',          [ProductController::class, 'index']);
        Route::post('/products',         [ProductController::class, 'store']);
        Route::put('/products/{id}',     [ProductController::class, 'update']);
    });

    /*
    |----------------------------------------------------------------------
    | AUDITORÍA (RF14 / CU30 consultar; CU29 registra) — solo ADMIN_SISTEMA (E2: 403)
    |   Las rutas fijas (summary, options, export) van antes que /audits/{id}.
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA')->group(function () {
        Route::get('/audits',          [AuditController::class, 'index']);
        Route::get('/audits/summary',  [AuditController::class, 'summary']);
        Route::get('/audits/options',  [AuditController::class, 'options']);
        Route::get('/audits/export',   [AuditController::class, 'export']);
        Route::get('/audits/{id}',     [AuditController::class, 'show'])->whereNumber('id');
    });

    /*
    |----------------------------------------------------------------------
    | REPORTES Y ESTADÍSTICAS (RF15 / CU28) — A, ADM y L (el Líder solo ve sus semilleros, RN06)
    |   El Estudiante no tiene acceso (403).
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,ADMINISTRATIVO,LIDER_SEMILLERO')->group(function () {
        Route::get('/reports',         [ReportController::class, 'index']);
        Route::get('/reports/options', [ReportController::class, 'options']);
        Route::get('/reports/export',  [ReportController::class, 'export']);
    });

    /*
    |----------------------------------------------------------------------
    | NOTIFICACIONES INTERNAS — todos los roles autenticados
    |----------------------------------------------------------------------
    */

    /* Lectura — todos los roles */
    Route::get('/notifications',             [NotificacionController::class, 'index']);
    Route::get('/notifications/unread-count',[NotificacionController::class, 'unreadCount']);
    Route::put('/notifications/read-all',    [NotificacionController::class, 'markAllRead']);
    Route::put('/notifications/{id}/read',   [NotificacionController::class, 'markRead']);

    /* Envío — solo roles con permiso */
    Route::middleware('role:ADMIN_SISTEMA,ADMINISTRATIVO,LIDER_SEMILLERO')->group(function () {
        Route::post('/notifications',             [NotificacionController::class, 'store']);
        Route::get('/notifications/sent',         [NotificacionController::class, 'sent']);
    });

    /*
    |----------------------------------------------------------------------
    | PUSH SUBSCRIPTIONS (Web Push) — todos los roles autenticados
    |----------------------------------------------------------------------
    */

    Route::post('/push-subscriptions',   [PushSubscriptionController::class, 'store']);
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
    Route::middleware('throttle:6,1')->post('/push-subscriptions/test', [PushSubscriptionController::class, 'test']);
    /*
    |----------------------------------------------------------------------
    | SIA — panel del administrador (RF17): consumo, feedback, conocimiento
    |----------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN_SISTEMA')->prefix('sia/admin')->group(function () {
        Route::get('/stats',                  [SiaController::class, 'stats']);
        Route::get('/conversations',          [SiaController::class, 'conversations']);
        Route::get('/conversations/{id}',     [SiaController::class, 'conversation']);
        Route::put('/conversations/{id}/review', [SiaController::class, 'review']);
        Route::get('/knowledge',              [SiaController::class, 'knowledgeIndex']);
        Route::post('/knowledge',             [SiaController::class, 'knowledgeStore']);
        Route::put('/knowledge/{id}',         [SiaController::class, 'knowledgeUpdate']);
        Route::get('/settings',               [SiaController::class, 'settings']);
        Route::put('/settings',               [SiaController::class, 'settingsUpdate']);
    });

    /*
    |----------------------------------------------------------------------
    | RBAC granular (permisos por módulo+acción, por rol y por persona)
    |   Solo ADMIN_SISTEMA administra permisos — ver config/rbac.php y
    |   App\Services\Rbac\PermissionResolver.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN_SISTEMA')->prefix('rbac')->group(function () {
        Route::get('/catalog',                    [RbacController::class, 'catalog']);
        Route::get('/roles',                       [RbacController::class, 'rolePermissions']);
        Route::put('/roles/{role}',                [RbacController::class, 'updateRolePermissions']);
        Route::get('/users-lite',                  [RbacController::class, 'usersLite']);
        Route::get('/users/{user}',                [RbacController::class, 'userPermissions']);
        Route::put('/users/{user}',                 [RbacController::class, 'updateUserPermissions']);

        /* Grupos de permisos (v2, 2026-09-29): personas de distintos roles
           agrupadas, con permisos que se otorgan al grupo completo. */
        Route::get('/groups',                      [RbacController::class, 'groups']);
        Route::post('/groups',                     [RbacController::class, 'storeGroup']);
        Route::get('/groups/{group}',               [RbacController::class, 'showGroup']);
        Route::put('/groups/{group}',               [RbacController::class, 'updateGroup']);
        Route::delete('/groups/{group}',            [RbacController::class, 'destroyGroup']);
        Route::put('/groups/{group}/members',       [RbacController::class, 'updateGroupMembers']);
        Route::put('/groups/{group}/permissions',   [RbacController::class, 'updateGroupPermissions']);
    });

});
