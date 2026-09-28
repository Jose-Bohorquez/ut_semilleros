<?php #archivo: backend/app/Http/Middleware/SecurityHeaders.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RNF03 — cabeceras de seguridad en todas las respuestas de la API.
 * Las del frontend estático van en el .htaccess raíz.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /* No revelar la versión de PHP (la API respondía X-Powered-By: PHP/8.3.x) */
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        /* Respuestas con token: nunca a la caché del CDN ni del navegador
           (llevan datos personales y dependen del rol) */
        if ($request->bearerToken()) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
