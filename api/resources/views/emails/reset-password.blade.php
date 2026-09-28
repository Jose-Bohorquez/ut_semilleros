{{-- #archivo: backend/resources/views/emails/reset-password.blade.php — CU04 recuperar contraseña --}}
@include('emails.partials.brand-open', ['title' => 'Recupera tu contraseña', 'preheader' => 'Enlace para crear una nueva contraseña en Semilleros UT.'])
    <tr><td style="padding:32px 32px 8px;">
      <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#0f172a;">Hola{{ $firstName ? ', '.$firstName : '' }}</h1>
      <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#334155;">
        Recibimos una solicitud para recuperar la contraseña de <strong>{{ $email }}</strong> en Semilleros UT.
        Presiona el botón para crear una nueva:
      </p>
      <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 12px;"><tr>
        <td align="center" style="border-radius:10px;background:#c2410c;background-image:linear-gradient(135deg,#dc2626,#c2410c);">
          <a href="{{ $resetUrl }}" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px;">Crear nueva contraseña</a>
        </td>
      </tr></table>
      <p style="margin:0 0 24px;font-size:13px;color:#64748b;text-align:center;">Por seguridad, el enlace vence en <strong>60 minutos</strong>.</p>
      <p style="margin:0 0 16px;font-size:12px;line-height:1.6;color:#64748b;border-top:1px solid #e2e8f0;padding-top:16px;">
        Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
        <a href="{{ $resetUrl }}" style="color:#b91c1c;word-break:break-all;">{{ $resetUrl }}</a>
      </p>
    </td></tr>
@include('emails.partials.brand-close', ['waTitle' => '¿No pediste este cambio, necesitas ayuda o encontraste una falla?', 'reason' => 'Si no pediste este cambio, ignora este correo: tu contraseña actual sigue funcionando y nadie más puede usar este enlace sin acceso a tu correo.'])
