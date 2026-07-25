<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;

class CustomResetPasswordNotification extends ResetPassword
{
    /**
     * Construir mail reset password.
     */
    public function toMail($notifiable)
    {
        $frontendUrl = env(
            'FRONTEND_URL',
            'http://localhost:5173'
        );

        $resetUrl =
            $frontendUrl .
            '/reset-password?token=' .
            $this->token .
            '&email=' .
            urlencode($notifiable->email);

        return (new MailMessage)

            ->subject('Recuperación de contraseña')

            ->line(
                'Has solicitado recuperar tu contraseña.'
            )

            ->action(
                'Restablecer contraseña',
                $resetUrl
            )

            ->line(
                'Si no solicitaste este cambio puedes ignorar este correo.'
            );
    }
}