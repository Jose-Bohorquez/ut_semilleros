<?php #archivo: backend/app/Exceptions/Handler.php ?>
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Registrar callbacks de manejo de excepciones
     */
    public function register(): void
    {
        $this->renderable(function (AuthenticationException $e, $request) {

            // Si la petición es para la API devolvemos JSON
            if ($request->is('api/*')) {

                return response()->json([
                    'message' => 'No autenticado'
                ], 401);

            }

        });
    }
}