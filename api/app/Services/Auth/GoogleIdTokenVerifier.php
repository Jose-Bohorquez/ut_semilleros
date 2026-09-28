<?php
// #archivo: /backend/app/Services/Auth/GoogleIdTokenVerifier.php

namespace App\Services\Auth;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CU02 — verifica el ID token que entrega Google Identity Services.
 *
 * Se valida contra el endpoint tokeninfo de Google (firma, vencimiento y
 * emisor los comprueba Google); aquí se revisan aud, iss, exp y
 * email_verified. No hace falta client secret: el flujo de ID token solo
 * usa el Client ID público.
 */
class GoogleIdTokenVerifier
{
    public const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    /**
     * @return array claims verificados
     * @throws GoogleAuthException
     */
    public function verify(string $idToken): array
    {
        $clientId = config('services.google.client_id');

        if (!$clientId) {
            Log::error('[CU02] GOOGLE_CLIENT_ID no está configurado');
            throw new GoogleAuthException('not_configured');
        }

        try {
            $response = Http::timeout(8)->acceptJson()
                ->get(self::TOKENINFO_URL, ['id_token' => $idToken]);
        } catch (ConnectionException $e) {
            /* E5: Google no responde → log técnico */
            Log::error('[CU02] Google tokeninfo no responde', ['error' => $e->getMessage()]);
            throw new GoogleAuthException('unavailable');
        }

        if ($response->serverError()) {
            Log::error('[CU02] Google tokeninfo respondió ' . $response->status());
            throw new GoogleAuthException('unavailable');
        }

        $claims = $response->json();

        if (!$response->successful() || !is_array($claims)) {
            Log::warning('[CU02] ID token rechazado por Google', ['status' => $response->status()]);
            throw new GoogleAuthException('invalid_token');
        }

        $issOk   = in_array($claims['iss'] ?? '', ['accounts.google.com', 'https://accounts.google.com'], true);
        $audOk   = hash_equals((string) $clientId, (string) ($claims['aud'] ?? ''));
        $expOk   = (int) ($claims['exp'] ?? 0) > time();
        /* tokeninfo devuelve email_verified como texto "true" */
        $emailOk = !empty($claims['email']) && filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$issOk || !$audOk || !$expOk || !$emailOk || empty($claims['sub'])) {
            Log::warning('[CU02] ID token con claims inválidos', [
                'iss' => $issOk, 'aud' => $audOk, 'exp' => $expOk, 'email_verified' => $emailOk,
            ]);
            throw new GoogleAuthException('invalid_token');
        }

        return $claims;
    }

    /** Dominios institucionales permitidos (GOOGLE_ALLOWED_DOMAINS, separados por coma). */
    public static function institutionalDomains(): array
    {
        return array_values(array_filter(array_map(
            fn ($d) => strtolower(trim($d)),
            explode(',', (string) config('services.google.allowed_domains', 'ut.edu.co'))
        )));
    }

    /**
     * RN04: el correo es del dominio institucional y la cuenta pertenece a su
     * Google Workspace (claim hd). Exigir hd evita una cuenta personal de
     * Google creada con una dirección @ut.edu.co.
     */
    public static function isInstitutional(array $claims): bool
    {
        $email  = strtolower((string) ($claims['email'] ?? ''));
        $domain = substr(strrchr($email, '@') ?: '', 1);
        $hd     = strtolower((string) ($claims['hd'] ?? ''));

        return $domain !== ''
            && in_array($domain, self::institutionalDomains(), true)
            && $hd === $domain;
    }
}
