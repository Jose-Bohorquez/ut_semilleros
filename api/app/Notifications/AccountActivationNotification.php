<?php
// #archivo: backend/app/Notifications/AccountActivationNotification.php
// Correo de activación de cuenta — separado de CustomResetPasswordNotification
// (recuperar contraseña) porque el contenido debe ser distinto: aquí se le avisa
// al usuario que un administrador le creó la cuenta y debe definir su
// contraseña para poder entrar por primera vez. Reutiliza el mismo mecanismo
// de tokens de Laravel (Password::createToken) que ya usaba forgot-password,
// solo que aquí lo dispara el administrador, no el propio usuario.

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $token
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://ut-edu.online'), '/');

        $activationUrl = $frontendUrl
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->email)
            . '&activation=1';

        $primerNombre = trim(explode(' ', trim((string) $notifiable->name))[0] ?? '');

        return (new MailMessage)
            ->subject('Activa tu cuenta — Sistema de Semilleros IDEAD')
            ->greeting('Hola' . ($primerNombre ? ", {$primerNombre}" : '') . ' 👋')
            ->line('Un administrador creó tu cuenta en el Sistema de Semilleros de Investigación de la Universidad del Tolima.')
            ->line('Para poder ingresar, primero debes definir tu contraseña.')
            ->action('Activar mi cuenta', $activationUrl)
            ->line('Este enlace expira en 60 minutos por seguridad.')
            ->line('Si no esperabas este correo, puedes ignorarlo.');
    }
}
