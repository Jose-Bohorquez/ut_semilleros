{{-- Encabezado común de los correos de Semilleros UT. Variables: $title, $preheader, $appUrl --}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title>{{ $title }} · Semilleros UT</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;">
<tr><td align="center" style="padding:24px 12px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">

    <tr><td style="background:#b91c1c;background-image:linear-gradient(135deg,#dc2626,#c2410c);padding:28px 32px;" align="left">
      <table role="presentation" cellpadding="0" cellspacing="0"><tr>
        <td style="background:#ffffff;border-radius:12px;padding:6px 10px;">
          <img src="{{ $appUrl }}/assets/images/login/logo.png" width="110" alt="Universidad del Tolima" style="display:block;border:0;height:auto;">
        </td>
        <td style="padding-left:16px;color:#ffffff;">
          <div style="font-size:18px;font-weight:bold;line-height:1.3;">Semilleros UT</div>
          <div style="font-size:13px;opacity:.9;">Investigación IDEAD · Universidad del Tolima</div>
        </td>
      </tr></table>
    </td></tr>
