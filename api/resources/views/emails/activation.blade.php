{{-- #archivo: backend/resources/views/emails/activation.blade.php
     Correo de registro / activación de cuenta (RF01). HTML de tablas con estilos
     en línea: así se ve igual en Gmail, Outlook y el correo de la UT. --}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>Activa tu cuenta — Semilleros IDEAD</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Tu cuenta en el Sistema de Semilleros IDEAD está lista. Actívala para ingresar.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;">
<tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">

    <tr><td style="background:#b91c1c;background-image:linear-gradient(135deg,#dc2626,#c2410c);padding:28px 32px;" align="left">
      <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td style="background:#ffffff;border-radius:12px;padding:6px 10px;">
          <img src="{{ $appUrl }}/assets/images/login/logo.png" width="110" alt="Universidad del Tolima" style="display:block;border:0;height:auto;">
        </td>
        <td style="padding-left:16px;color:#ffffff;">
          <div style="font-size:18px;font-weight:bold;line-height:1.3;">Sistema de Semilleros</div>
          <div style="font-size:13px;opacity:.9;">Investigación IDEAD · Universidad del Tolima</div>
        </td>
      </tr></table>
    </td></tr>

    <tr><td style="padding:32px 32px 8px;">
      <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#0f172a;">Hola{{ $firstName ? ', '.$firstName : '' }} 👋</h1>
      <p style="margin:0 0 12px;font-size:15px;line-height:1.6;color:#334155;">
        Te creamos una cuenta en el <strong>Sistema de Semilleros de Investigación IDEAD</strong> de la
        Universidad del Tolima con el rol de <strong>{{ $roleLabel }}</strong>.
      </p>
      <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#334155;">
        Para entrar por primera vez, activa tu cuenta y crea tu contraseña:
      </p>
      <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 12px;"><tr>
        <td align="center" style="border-radius:10px;background:#c2410c;background-image:linear-gradient(135deg,#dc2626,#c2410c);">
          <a href="{{ $activationUrl }}" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:bold;color:#ffffff;text-decoration:none;border-radius:10px;">Activar mi cuenta</a>
        </td>
      </tr></table>
      <p style="margin:0 0 24px;font-size:13px;color:#64748b;text-align:center;">El enlace es personal y vence en <strong>7 días</strong>.</p>
    </td></tr>

    @if ($googleEnabled && $institutional)
    <tr><td style="padding:0 32px 8px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
        <tr><td style="padding:16px 18px;font-size:14px;line-height:1.6;color:#334155;">
          <strong style="color:#0f172a;">¿Prefieres no crear contraseña?</strong><br>
          Entra directo con <strong>«Iniciar sesión con Google»</strong> usando tu correo institucional
          <strong>{{ $email }}</strong>.
        </td></tr>
      </table>
    </td></tr>
    @endif

    <tr><td style="padding:16px 32px 8px;">
      <h2 style="margin:0 0 8px;font-size:15px;color:#0f172a;">Qué puedes hacer en el sistema</h2>
      <ul style="margin:0 0 16px;padding-left:20px;font-size:14px;line-height:1.7;color:#334155;">
        @foreach ($capabilities as $cap)
        <li>{{ $cap }}</li>
        @endforeach
      </ul>
      <h2 style="margin:0 0 8px;font-size:15px;color:#0f172a;">Pasos</h2>
      <ol style="margin:0 0 16px;padding-left:20px;font-size:14px;line-height:1.7;color:#334155;">
        <li>Presiona <strong>«Activar mi cuenta»</strong> y crea una contraseña (mínimo 6 caracteres).</li>
        <li>Ingresa en <a href="{{ $appUrl }}" style="color:#b91c1c;">{{ $appHost }}</a> con tu correo <strong>{{ $email }}</strong>.</li>
        @if ($isStudent)
        <li>En tu primer ingreso lee y acepta la <strong>autorización de tratamiento de datos</strong> (Ley 1581 de 2012).</li>
        @endif
        <li>En el celular puedes <strong>instalarla como app</strong>: menú del navegador → «Agregar a pantalla de inicio».</li>
      </ol>
      <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#334155;">
        ¿Dudas de uso? Pregúntale a <strong>SIA</strong>, el asistente que aparece en todas las pantallas.
      </p>
    </td></tr>

    <tr><td style="padding:0 32px 24px;">
      <p style="margin:0;font-size:12px;line-height:1.6;color:#64748b;border-top:1px solid #e2e8f0;padding-top:16px;">
        Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
        <a href="{{ $activationUrl }}" style="color:#b91c1c;word-break:break-all;">{{ $activationUrl }}</a>
      </p>
    </td></tr>

    <tr><td style="background:#f8fafc;padding:18px 32px;font-size:12px;line-height:1.6;color:#64748b;">
      Recibes este correo porque un administrador registró tu cuenta en el Sistema de Semilleros IDEAD.
      Si no lo esperabas, ignóralo: sin activar, la cuenta no se puede usar.<br>
      Tratamiento de datos: <a href="https://administrativos.ut.edu.co/images/RES._0676_DEL_27-05-19_ADOPTA_MANUAL_DE_POLITICAS.pdf" style="color:#64748b;">Resolución 0676 de 2019</a>.
    </td></tr>

  </table>
</td></tr>
</table>
</body>
</html>
