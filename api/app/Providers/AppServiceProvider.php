<?php

// #archivo: backend/app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/*
|--------------------------------------------------------------------------
| Observers
|--------------------------------------------------------------------------
*/

use App\Observers\AuditObserver;

/*
|--------------------------------------------------------------------------
| Models auditables
|--------------------------------------------------------------------------
*/

use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\Project;
use App\Models\Product;
use App\Models\Coordinator;
use App\Models\Group;
use App\Models\Area;
use App\Models\Cat;
use App\Models\Objective;
use App\Models\Result;
use App\Models\MembershipRequest;
use App\Models\Proposal;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registrar servicios aplicación.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap aplicación.
     */
    public function boot(): void
    {
        $this->registerAuditObservers();

        /* RNF03 «limitación de peticiones»: 120 por minuto por usuario (o por IP
           sin sesión). El panel carga varias listas en paralelo; 60 se quedaba
           corto. El login (RN14) y SIA tienen límites propios más estrictos. */
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            /* El límite corre ANTES de auth:sanctum: se resuelve el usuario desde el
               token aquí mismo. Con $request->user() salía siempre null y el límite
               quedaba por IP (en el Wi-Fi del campus muchos estudiantes comparten IP). */
            $user = $request->bearerToken() ? $request->user('sanctum') : null;
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)
                ->by($user ? 'u:' . $user->id : 'ip:' . $request->ip());
        });
    }

    /**
     * Registrar observers auditoría.
     *
     * RF14:
     * Toda creación/modificación relevante
     * debe registrarse automáticamente.
     */
    private function registerAuditObservers(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Gestión usuarios
        |--------------------------------------------------------------------------
        */

        User::observe(AuditObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Gestión académica
        |--------------------------------------------------------------------------
        */

        Faculty::observe(AuditObserver::class);

        Program::observe(AuditObserver::class);

        Cat::observe(AuditObserver::class);

        Area::observe(AuditObserver::class);

        Group::observe(AuditObserver::class);

        Coordinator::observe(AuditObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Semilleros e investigación
        |--------------------------------------------------------------------------
        */

        Seedbed::observe(AuditObserver::class);

        Project::observe(AuditObserver::class);

        Product::observe(AuditObserver::class);

        Objective::observe(AuditObserver::class);

        Result::observe(AuditObserver::class);

        Proposal::observe(AuditObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Solicitudes membresía
        |--------------------------------------------------------------------------
        */

        MembershipRequest::observe(
            AuditObserver::class
        );

        /*
        |--------------------------------------------------------------------------
        | RBAC granular (permisos por rol y por persona)
        |--------------------------------------------------------------------------
        */

        \App\Models\RolePermission::observe(AuditObserver::class);

        \App\Models\UserPermission::observe(AuditObserver::class);

        \App\Models\PermissionGroup::observe(AuditObserver::class);
    }
}