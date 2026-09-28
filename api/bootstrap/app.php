<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))

    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | Alias personalizados
        |--------------------------------------------------------------------------
        */

        $middleware->alias([

            'role' => \App\Http\Middleware\RoleMiddleware::class,

            /* Usuario INACTIVO no puede usar su token (C-04, 2026-09-27) */
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,

            /* RF16: el estudiante debe aceptar el tratamiento de datos */
            'consent' => \App\Http\Middleware\EnsureDataConsent::class,

            /*
            |--------------------------------------------------------------------------
            | Reemplazo Authenticate API
            |--------------------------------------------------------------------------
            */

            'auth' => \App\Http\Middleware\Authenticate::class,

        ]);

    })

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | Forzar respuestas JSON en API
        |--------------------------------------------------------------------------
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );

    })

    ->create();