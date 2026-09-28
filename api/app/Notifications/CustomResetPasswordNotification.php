<?php
// #archivo: backend/app/Notifications/CustomResetPasswordNotification.php
// CU04 — correo de recuperación de contraseña con la plantilla de Semilleros UT
// (antes era el correo genérico de Laravel con «PWA UT Semilleros»).

namespace App\Notifications;

use App\Support\MailBrand;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class CustomResetPasswordNotification extends ResetPassword
{
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
