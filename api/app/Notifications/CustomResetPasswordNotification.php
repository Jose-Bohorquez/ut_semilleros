<?php
// #archivo: backend/app/Notifications/CustomResetPasswordNotification.php
// CU04 — correo de recuperación de contraseña con la plantilla de Semilleros UT
// (antes era el correo genérico de Laravel con «PWA UT Semilleros»).
//
// E4 «falla del servidor de correo: el envío queda en cola y se reintenta
// hasta 3 veces»: ShouldQueue + $tries=3. QUEUE_CONNECTION=database (ya
// configurado); en el servidor un cron ejecuta `queue:work --stop-when-empty`
// cada minuto (ver docs/manuales/manual_tecnico.md «Colas»). Si los 3
// intentos fallan, el job cae a `failed_jobs` (tabla creada en esta ronda)
// y no se pierde: se puede reintentar con `queue:retry`.

namespace App\Notifications;

use App\Support\MailBrand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class CustomResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Espera 30 s, luego 2 min, luego 5 min entre reintentos. */
    public array $backoff = [30, 120, 300];

    public function toMail($notifiable)
    {
        $resetUrl = MailBrand::appUrl()
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->from(...MailBrand::from())
            ->subject('Recupera tu contraseña · Semilleros UT')
            ->view('emails.reset-password', [
                'firstName'   => MailBrand::firstName($notifiable->name),
                'email'       => $notifiable->email,
                'resetUrl'    => $resetUrl,
                'appUrl'      => MailBrand::appUrl(),
                'whatsappUrl' => MailBrand::whatsappUrl('recuperar contraseña'),
            ]);
    }
}
