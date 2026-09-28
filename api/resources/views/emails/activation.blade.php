{{-- #archivo: backend/resources/views/emails/activation.blade.php
     Correo de registro / activación de cuenta (RF01). HTML de tablas con estilos
     en línea: así se ve igual en Gmail, Outlook y el correo de la UT. --}}
@include('emails.partials.brand-open', ['title' => 'Activa tu cuenta', 'preheader' => 'Tu cuenta en Semilleros UT está lista. Actívala para ingresar.'])
<tr><td style="padding:32px 32px 8px;">
      <h1 style="margin:0 0 12px;font-size:22px;line-height:1.3;color:#0f172a;">Hola{{ $firstName ? ', '.$firstName : '' }} 👋</h1>
      <p style="margin:0 0 12px;font-size:15px;line-height:1.6;color:#334155;">
        Te creamos una cuenta en <strong>Semilleros UT</strong>, el Sistema de Semilleros de Investigación
        IDEAD de la Universidad del Tolima, con el rol de <strong>{{ $roleLabel }}</strong>.
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

@include('emails.partials.brand-close', ['reason' => 'Recibes este correo porque un administrador registró tu cuenta en Semilleros UT. Si no lo esperabas, puedes ignorarlo: sin activar, la cuenta no se puede usar.'])
