<?php

/* Estos textos los usa el broker de contraseñas (CU04). Los mensajes que
   llegan al usuario los fija AuthController; aquí solo se evita que aparezca
   la clave cruda («passwords.token») si alguna ruta llama __($status). */
return [
    'reset'     => 'Su contraseña ha sido restablecida.',
    'sent'      => 'Si el correo está registrado, recibirá un enlace para recuperar su contraseña.',
    'throttled' => 'Espere antes de volver a intentarlo.',
    'token'     => 'El enlace no es válido o expiró.',
    'user'      => 'El enlace no es válido o expiró.',
];
