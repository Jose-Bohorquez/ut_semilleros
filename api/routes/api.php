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
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\RbacController;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS — no requieren autenticación
|--------------------------------------------------------------------------
*/

Route::post('/register',         [AuthController::class, 'register']);
Route::post('/login',            [AuthController::class, 'login']);
Route::post('/forgot-password',  [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',   [AuthController::class, 'resetPassword']);

/* CU02 — Google institucional. tokeninfo es una llamada externa: se limita
   por IP para que el endpoint no sirva de amplificador. */
Route::get('/auth/config',       [AuthController::class, 'authConfig']);
Route::middleware('throttle:10,1')
     ->post('/auth/google',      [AuthController::class, 'google']);

/* SIA — asistente con IA (RF17 propuesto). Público: también se usa en el login.
   Límites por IP/usuario/conversación y tope global en SiaController; el throttle
   por minuto frena ráfagas antes de tocar la BD. */
Route::middleware('throttle:15,1')->group(function () {
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
    |   GET list      → A, L, ADM
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

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
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
    |   GET  → A, L, ADM
    |   POST/PUT → A, L
    |   TOGGLE   → A
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/coordinators', [CoordinatorController::class, 'index']);

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO')->group(function () {
        Route::post('/coordinators',                 [CoordinatorController::class, 'store']);
        Route::put('/coordinators/{id}',             [CoordinatorController::class, 'update']);
    });

    Route::middleware('role:ADMIN_SISTEMA')
         ->put('/coordinators/{id}/toggle-status',   [CoordinatorController::class, 'toggleStatus']);

    /*
    |----------------------------------------------------------------------
    | SEMILLEROS (RF13 / CU13)
    |   GET list+show → todos los roles (A, L, ADM, E)
    |   POST/PUT/TOGGLE → L, ADM_SISTEMA, ADMINISTRATIVO (Jose, 2026-07-28: el
    |     admin debe poder crear/editar semilleros desde el panel, no solo el
    |     líder; ADMINISTRATIVO ya veía el botón en el frontend pero el backend
    |     se lo rechazaba con 403 — mismo bug que ya se encontró en propuestas)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->get('/seedbeds', [SeedbedController::class, 'index']);

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->get('/seedbeds/{id}', [SeedbedController::class, 'show']);

    Route::middleware('role:LIDER_SEMILLERO,ADMIN_SISTEMA,ADMINISTRATIVO')->group(function () {
        Route::post('/seedbeds',                         [SeedbedController::class, 'store']);
        Route::put('/seedbeds/{id}',                     [SeedbedController::class, 'update']);
        Route::put('/seedbeds/{id}/toggle-status',       [SeedbedController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | INTEGRANTES SEMILLEROS (RF12 / CU12)
    |   Todos → L
    |----------------------------------------------------------------------
    */

    Route::middleware('role:LIDER_SEMILLERO')->group(function () {
        Route::get('/seedbeds/{id}/members',                      [SeedbedMemberController::class, 'index']);
        Route::post('/seedbeds/{id}/members',                     [SeedbedMemberController::class, 'store']);
        Route::delete('/seedbeds/{seedbedId}/members/{userId}',   [SeedbedMemberController::class, 'destroy']);
    });

    /*
    |----------------------------------------------------------------------
    | OBJETIVOS (RF08 / CU08)
    |   GET list → todos los roles
    |   POST/PUT/TOGGLE → L, ADM, ADMIN_SISTEMA (Jose, 2026-07-28: el admin
    |     también debe poder gestionar objetivos, igual que semilleros)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->get('/objectives', [ObjectiveController::class, 'index']);

    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO,ADMIN_SISTEMA')->group(function () {
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

    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::post('/results',                      [ResultController::class, 'store']);
        Route::put('/results/{id}',                  [ResultController::class, 'update']);
        Route::put('/results/{id}/toggle-status',    [ResultController::class, 'toggleStatus']);
    });

    /*
    |----------------------------------------------------------------------
    | SOLICITUDES (RF10 / CU10)
    |   GET list  → A, L, ADM
    |   POST/PUT  → L, ADM
    |   update-status → L, ADM
    |   GET /my   → E (sus propias solicitudes)
    |   POST      → E (crear la suya)
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/requests', [RequestController::class, 'index']);

    /* POST /requests — L, ADM, E (cada uno crea las suyas) */
    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->post('/requests', [RequestController::class, 'store']);

    /* PUT (update + status) — solo L, ADM */
    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO')->group(function () {
        Route::put('/requests/{id}',               [RequestController::class, 'update']);
        Route::put('/requests/{id}/update-status', [RequestController::class, 'updateStatus']);
    });

    /* GET propias — solo E */
    Route::middleware('role:ESTUDIANTE')
         ->get('/requests/my', [RequestController::class, 'myRequests']);

    /*
    |----------------------------------------------------------------------
    | PROPUESTAS (RF11 / CU11)
    |   GET list  → A, L, ADM
    |   POST/PUT  → L, ADM
    |   update-status → L, ADM
    |   GET /my   → E
    |   POST      → E
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO')
         ->get('/proposals', [ProposalController::class, 'index']);

    /* POST /proposals — L, ADM, E (cada uno crea las suyas) */
    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->post('/proposals', [ProposalController::class, 'store']);

    /* PUT /proposals/{id} — L, ADM (cualquier propuesta) + ESTUDIANTE (solo la
       suya, y solo si sigue PENDIENTE — validado dentro del controlador).
       update-status (aprobar/rechazar) sigue siendo exclusivo de L, ADM. */
    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO,ESTUDIANTE')
         ->put('/proposals/{id}', [ProposalController::class, 'update']);

    Route::middleware('role:LIDER_SEMILLERO,ADMINISTRATIVO')
         ->put('/proposals/{id}/update-status', [ProposalController::class, 'updateStatus']);

    /* GET propias — solo E */
    Route::middleware('role:ESTUDIANTE')
         ->get('/proposals/my', [ProposalController::class, 'myProposals']);

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
    | AUDITORÍA (RF14 / CU14) — solo A
    |----------------------------------------------------------------------
    */

    Route::middleware('role:ADMIN_SISTEMA')
         ->get('/audits', [AuditController::class, 'index']);

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
