<?php
// #archivo: backend/app/Support/MailBrand.php
// Marca de los correos: «Semilleros UT» (Jose, 2026-09-28). APP_NAME del .env es
// «PWA UT Semilleros» y no se usa en los correos.

namespace App\Support;

class MailBrand
{
    public const NAME = 'Semilleros UT';

    /* Mismo número del botón «Reportar bug» del sistema (core/sia.widget.js) */
    public const WHATSAPP = '573178773186';

    public static function appUrl(): string
    {
        return rtrim(env('FRONTEND_URL', 'https://ut-edu.online'), '/');
    }

    public static function whatsappUrl(string $context): string
    {
        $msg = "Hola, escribo sobre Semilleros UT ({$context}). Quiero: pedir información / cancelar mi registro / reportar una falla.\nMi correo: ";
        return 'https://wa.me/' . self::WHATSAPP . '?text=' . rawurlencode($msg);
    }

    public static function firstName(?string $name): string
    {
        return ucfirst(mb_strtolower(trim(explode(' ', trim((string) $name))[0] ?? '')));
    }

    public static function from(): array
    {
        return [config('mail.from.address'), self::NAME];
    }
}
