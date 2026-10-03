<?php
// #archivo: backend/app/Notifications/AccountActivationNotification.php
// Correo de registro / activación de cuenta — separado de CustomResetPasswordNotification
// (recuperar contraseña) porque el contenido debe ser distinto: aquí se le avisa
// al usuario que un administrador le creó la cuenta y debe definir su
// contraseña para poder entrar por primera vez. El token es del broker
// «activations» (7 días, config/auth.php). Plantilla: resources/views/emails/activation.blade.php.

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Support\MailBrand;

class AccountActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /* Encolado con reintentos, igual que CustomResetPasswordNotification (CU04 E4): crear un usuario (o
       importar estudiantes en lote) no espera al servidor de correo, y si el SMTP falla se reintenta. */
    public int $tries = 3;

    public array $backoff = [30, 120];

    private const ROLES = [
        'ESTUDIANTE'      => 'Estudiante',
        'LIDER_SEMILLERO' => 'Líder de semillero',
        'ADMINISTRATIVO'  => 'Administrativo',
        'ADMIN_SISTEMA'   => 'Administrador del sistema',
    ];

    private const CAPABILITIES = [
        'ESTUDIANTE' => [
            'Consultar los semilleros de investigación activos y su detalle.',
            'Solicitar tu vinculación a un semillero y seguir el estado de tus solicitudes.',
            'Registrar propuestas de investigación y consultar su evaluación.',
        ],
        'LIDER_SEMILLERO' => [
            'Gestionar la información, los objetivos y los resultados de tus semilleros.',
            'Revisar y responder las solicitudes de vinculación de los estudiantes.',
            'Administrar los integrantes y los proyectos del semillero.',
        ],
        'ADMINISTRATIVO' => [
            'Consultar semilleros, programas y facultades.',
            'Evaluar las propuestas de investigación.',
        ],
        'ADMIN_SISTEMA' => [
            'Administrar usuarios, facultades, programas y semilleros.',
            'Consultar la auditoría y el panel de SIA.',
        ],
    ];

    public function __construct(
        protected string $token
    ) {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    /** Datos de la plantilla (también los usa la vista previa de pruebas). */
    public static function viewData(object $notifiable, string $activationUrl): array
    {
        $appUrl = MailBrand::appUrl();
        $email  = strtolower((string) $notifiable->email);
        $domain = substr(strrchr($email, '@') ?: '', 1);
        $role   = $notifiable->role ?? 'ESTUDIANTE';

        return [
            'firstName'     => MailBrand::firstName($notifiable->name),
            'whatsappUrl'   => MailBrand::whatsappUrl('correo de registro'),
            'email'         => $email,
            'roleLabel'     => self::ROLES[$role] ?? 'Usuario',
            'capabilities'  => self::CAPABILITIES[$role] ?? [],
            'isStudent'     => $role === 'ESTUDIANTE',
            'activationUrl' => $activationUrl,
            'appUrl'        => $appUrl,
            'appHost'       => parse_url($appUrl, PHP_URL_HOST) ?: $appUrl,
            'googleEnabled' => (bool) config('services.google.client_id'),
            'institutional' => in_array($domain, GoogleIdTokenVerifier::institutionalDomains(), true),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $frontendUrl = MailBrand::appUrl();

        $activationUrl = $frontendUrl
            . '/reset-password?token=' . $this->token
            . '&email=' . urlencode($notifiable->email)
            . '&activation=1';

        return (new MailMessage)
            ->from(...MailBrand::from())
            ->subject('Activa tu cuenta · Semilleros UT')
            ->view('emails.activation', self::viewData($notifiable, $activationUrl));
    }
}
