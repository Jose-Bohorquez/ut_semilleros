<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'webpush' => [
        'public_key'  => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
    ],

    /* SIA — asistente con IA (RF17). La key vive solo en .env del servidor. */
    'groq' => [
        'key'      => env('GROQ_API_KEY'),
        /* Varias cuentas en rotación: "cta_01=gsk_…,cta_02=gsk_…". Si no se define,
           se usa GROQ_API_KEY como única cuenta (cta_01). */
        'keys'     => env('GROQ_API_KEYS'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model'    => env('SIA_MODEL', 'openai/gpt-oss-20b'),
    ],

    /* CU02 — ingreso con Google (flujo de ID token: solo Client ID, sin secret).
       GOOGLE_ALLOWED_DOMAINS: dominios institucionales, separados por coma.
       Fuera de esos dominios solo entra un ADMIN_SISTEMA ya registrado. */
    'google' => [
        'client_id'       => env('GOOGLE_CLIENT_ID'),
        'allowed_domains' => env('GOOGLE_ALLOWED_DOMAINS', 'ut.edu.co'),
    ],

];
