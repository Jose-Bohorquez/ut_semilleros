<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Audit;
use App\Models\User;
use App\Services\Auth\GoogleAuthException;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Support\PasswordPolicy;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login usuario.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([

            'email' => 'required|email|max:255',

            'password' => 'required',

            'remember' => 'sometimes|boolean'

        ]);

        /* CU01 E4 / RN14: 5 intentos fallidos en un minuto (mismo correo e
           IP) bloquean nuevos intentos durante 60 s. El intento se cuenta
           ANTES de verificar la contraseña: si se contara después, N
           peticiones en paralelo pasarían todas la revisión (review
           2026-09-28). Un login exitoso limpia el contador. */
        $throttleKey = 'login:' . Str::lower($validated['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {

            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([

                'message' => "Demasiados intentos fallidos. Intenta de nuevo en {$seconds} segundos.",

                'retry_after' => $seconds

            ], 429);
        }

        RateLimiter::hit($throttleKey, 60);

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        /* Si el correo no existe se compara contra un hash de relleno, para
           que el tiempo de respuesta no revele qué correos están registrados. */
        $hash = $user?->password ?? '$2y$12$tFxM1f2m3NgpMDubyM7HO.xCn2Cm0CibJXJBKOZDJ0RueGV07NlkC';

        if (
            !Hash::check(
                $validated['password'],
                $hash
            ) || !$user
        ) {

            /* CU01 E2: mensaje genérico, sin indicar qué dato falló. */
            return response()->json([

                'message' => 'Credenciales incorrectas'

            ], 401);
        }

        if ($user->status !== 'ACTIVO') {

            /* CU01 E3 */
            return response()->json([

                'message' => 'Su usuario está inactivo. Contacte al administrador del sistema.'

            ], 403);
        }

        RateLimiter::clear($throttleKey);

        /* CU01 A2 + RNF03: la sesión vence a las 8 horas, o a los 30 días
           con «Recordarme». Sanctum 4 invalida el token cuando pasa
           expires_at (antes los tokens no vencían nunca). */
        $remember  = (bool) ($validated['remember'] ?? false);
        $expiresAt = $remember ? now()->addDays(30) : now()->addHours(8);

        /* CU01 paso 6 → CU29 / RN07: todo inicio de sesión queda en la
           auditoría (el AuditObserver solo cubre altas y cambios de modelos).
           Token y auditoría van juntos: si no se puede auditar no se emite el
           token (RN07 es obligatoria) y no quedan tokens huérfanos. */
        return $this->issueSession($user, $expiresAt);
    }

    /**
     * Emite el token y audita el LOGIN en una sola transacción (CU01 paso 6,
     * CU02 paso 8): si no se puede auditar no se emite el token (RN07) y no
     * quedan tokens huérfanos. $extra se escribe con forceFill dentro de la
     * misma transacción (CU02: alta del estudiante o vínculo de google_id).
     */
    private function issueSession(User $user, \DateTimeInterface $expiresAt, array $extra = [])
    {
        $token = DB::transaction(function () use ($user, $expiresAt, $extra) {

            if (!$user->exists || $extra) {
                $user->forceFill($extra)->save();
            }

            $plain = $user->createToken(
                'auth_token',
                ['*'],
                $expiresAt
            )->plainTextToken;

            Audit::create([
                'user_id'    => $user->id,
                'action'     => 'LOGIN',
                'table_name' => 'users',
                'record_id'  => $user->id,
            ]);

            return $plain;
        });

        return response()->json([

            'message' => 'Inicio de sesión exitoso',

            'user' => new UserResource($user),

            'token' => $token,

            'expires_at' => $expiresAt->toIso8601String()

        ]);
    }

    /**
     * Configuración pública del login (CU02): la PWA solo muestra el botón
     * de Google si hay Client ID configurado en el servidor.
     */
    public function authConfig()
    {
        return response()->json([
            'google_client_id'      => config('services.google.client_id') ?: null,
            'institutional_domains' => GoogleIdTokenVerifier::institutionalDomains(),
        ]);
    }

    /**
     * CU02 — Iniciar sesión con la cuenta de Google institucional.
     *
     * RN04: con Google entran solo correos del dominio institucional (y de su
     * Google Workspace). Única excepción (decisión de Jose, 2026-09-28): un
     * ADMIN_SISTEMA ya registrado puede usar otra cuenta de Google. Nadie se
     * crea con un correo externo (E1).
     */
    public function google(Request $request, GoogleIdTokenVerifier $verifier)
    {
        $validated = $request->validate([
            'credential' => 'required|string|max:4096',
        ]);

        $e5 = 'No fue posible autenticarse con Google, intente nuevamente';

        try {
            $claims = $verifier->verify($validated['credential']);
        } catch (GoogleAuthException $e) {
            /* E5 — el detalle ya quedó en el log técnico */
            return response()->json(['message' => $e5], $e->reason === 'invalid_token' ? 401 : 503);
        }

        $email         = Str::lower($claims['email']);
        $sub           = (string) $claims['sub'];
        $institutional = GoogleIdTokenVerifier::isInstitutional($claims);

        /* Primero por la cuenta de Google ya vinculada; si no, por correo (paso 7) */
        $user = User::where('google_id', $sub)->first()
             ?? User::where('email', $email)->first();

        /* E1: fuera del dominio institucional solo pasa un ADMIN_SISTEMA existente */
        if (!$institutional && (!$user || $user->role !== 'ADMIN_SISTEMA')) {
            return response()->json(['message' => 'Debe ingresar con su cuenta institucional'], 403);
        }

        /* Un correo ya vinculado a otra cuenta de Google no se re-vincula en
           silencio (evita que una cuenta nueva con el mismo correo lo tome). */
        if ($user && $user->google_id && $user->google_id !== $sub) {
            Log::warning('[CU02] google_id distinto para un usuario vinculado', ['user_id' => $user->id]);
            return response()->json(['message' => $e5], 403);
        }

        /* E3 */
        if ($user && $user->status !== 'ACTIVO') {
            return response()->json(['message' => 'Su acceso está inactivo'], 403);
        }

        $extra = [];

        if (!$user) {
            /* Paso 7: alta como estudiante con nombre y correo de Google. Queda
               una contraseña aleatoria que nadie conoce; si el estudiante quiere
               entrar también con contraseña, la crea con «¿Olvidó su
               contraseña?» usando su correo institucional (CU04). */
            $user = new User([
                'name'     => Str::limit(trim($claims['name'] ?? '') ?: Str::before($email, '@'), 250, ''),
                'email'    => $email,
                'password' => Str::random(48),
                'role'     => 'ESTUDIANTE',
                'status'   => 'ACTIVO',
            ]);
            $extra['email_verified_at'] = now();
        }

        if (!$user->google_id) {
            $extra['google_id'] = $sub;
        }

        /* Paso 8 + RNF03: 8 horas */
        return $this->issueSession($user, now()->addHours(8), $extra);
    }

    /**
     * RF16 / CU02 A1 — autorización de tratamiento de datos personales.
     * «Acepto» guarda la fecha; «No acepto» cierra la sesión.
     */
    public function consent(Request $request)
    {
        $validated = $request->validate(['accept' => 'required|boolean']);
        $user = $request->user();

        if (!$validated['accept']) {
            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'No puede usar la aplicación sin la autorización de tratamiento de datos personales.',
                'logged_out' => true,
            ]);
        }

        DB::transaction(function () use ($user) {
            if (!$user->data_consent_at) {
                $user->forceFill(['data_consent_at' => now()])->save();

                Audit::create([
                    'user_id'    => $user->id,
                    'action'     => 'CONSENT',
                    'table_name' => 'users',
                    'record_id'  => $user->id,
                ]);
            }
        });

        return response()->json([
            'message' => 'Autorización registrada',
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Perfil usuario autenticado.
     */
    public function me(Request $request)
    {
        return response()->json([

            'user' => new UserResource(
                $request->user()
            )

        ]);
    }

    /**
     * Subir / actualizar foto de perfil (base64).
     */
    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|string',
        ]);

        $photo = $request->photo;

        /* Solo se aceptan imágenes en base64 con prefijo data URI */
        if (!preg_match('/^data:image\/(jpeg|jpg|png|gif|webp|bmp|svg\+xml);base64,/i', $photo)) {
            return response()->json([
                'message' => 'Formato de imagen no válido. Use JPEG, PNG, GIF, WebP o BMP.',
            ], 422);
        }

        /* Límite: ~2 MB codificado en base64 */
        if (strlen($photo) > 2 * 1024 * 1024) {
            return response()->json([
                'message' => 'La imagen es demasiado grande. Máximo 1.5 MB.',
            ], 422);
        }

        $user = $request->user();
        $user->update(['profile_photo' => $photo]);

        return response()->json([
            'message' => 'Foto de perfil actualizada correctamente',
            'user'    => new UserResource($user),
        ]);
    }

    /**
     * Eliminar foto de perfil.
     */
    public function deletePhoto(Request $request)
    {
        $request->user()->update(['profile_photo' => null]);

        return response()->json(['message' => 'Foto eliminada correctamente']);
    }

    /**
     * CU05 — Consultar y actualizar perfil (paso 3-5: nombre, correo y
     * teléfono; A1: cambiar contraseña).
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,

            /* E1: entre 7 y 15 dígitos (se admite un "+" inicial, no se
               cuenta en el largo). */
            'phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],

            /* A1: cambiar contraseña exige la actual + RN10 en la nueva. */
            'current_password' => 'required_with:password|string',
            'password' => PasswordPolicy::optional(),

        ], PasswordPolicy::messages() + [
            'phone.regex' => 'El teléfono debe tener entre 7 y 15 dígitos.',
            'current_password.required_with' => 'Escribe tu contraseña actual.',
        ]);

        /* E2 */
        if (!empty($validated['password']) && !Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];

        $changingPassword = !empty($validated['password']);
        if ($changingPassword) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::transaction(function () use ($user, $data, $changingPassword) {

            /* Auditoría (CU29): $user->update() ya la genera solo (AuditObserver),
               no hace falta un Audit::create manual aquí. */
            $user->update($data);

            /* A1: al cambiar la contraseña se cierran las demás sesiones —
               esta (la que la está cambiando) se conserva, para no botar de
               inmediato a quien acaba de autenticarse con la anterior. */
            if ($changingPassword) {
                $current = $user->currentAccessToken();
                $user->tokens()->when($current, fn ($q) => $q->where('id', '!=', $current->id))->delete();
            }
        });

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        /* CU03 paso 2: revoca el token de este dispositivo (los de otros
           dispositivos siguen vigentes) y lo deja en la auditoría. Token y
           auditoría juntos, como en el login. */
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->currentAccessToken()?->delete();

            Audit::create([
                'user_id'    => $user->id,
                'action'     => 'LOGOUT',
                'table_name' => 'users',
                'record_id'  => $user->id,
            ]);
        });

        return response()->json([

            'message' => 'Sesión cerrada correctamente'

        ]);
    }

    /**
     * Solicitar recuperación contraseña.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([

            'email' => 'required|email'

        ]);

        $email = Str::lower($request->input('email'));

        /* CU04 E3: más de 3 solicitudes en 10 minutos para el mismo correo →
           429 y no se manda nada más. Se cuenta por correo (no por IP): así
           protege una casilla ajena aunque el atacante rote de IP, y no
           bloquea a otro usuario que pida su propio enlace desde la misma
           red. Se cuenta ANTES de intentar el envío, igual que RN14 en el
           login (evita que N peticiones paralelas se salten el límite). */
        $throttleKey = 'forgot-password:' . $email;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {

            return response()->json([

                'message' => 'Demasiadas solicitudes para este correo. Intenta de nuevo más tarde.',

                'retry_after' => RateLimiter::availableIn($throttleKey)

            ], 429);
        }

        RateLimiter::hit($throttleKey, 600);

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (\Throwable $e) {
            /* E4: si el correo de un solo uso falla al enviarse (SMTP caído,
               dominio que rechaza el mensaje, etc.) esto NUNCA debe filtrarse
               como un 500 al usuario — seguiría revelando por descarte que el
               correo sí existe. Con la cola (producción) esto casi no pasa
               aquí, porque el envío se reintenta en segundo plano; pero si la
               cola está en modo síncrono (sin cron todavía) un fallo de SMTP
               sí llega hasta aquí. Se registra para diagnóstico y se responde
               igual que siempre. */
            Log::error('[CU04] Falló el envío del correo de recuperación', ['error' => $e->getMessage()]);
        }

        /* CU04 paso 5: el mensaje NUNCA revela si el correo existe (antes se
           devolvía el texto de Laravel, que sí lo revelaba: «No encontramos
           un usuario con ese correo» vs «Te hemos enviado el enlace…»). */
        return response()->json([

            'message' => 'Si el correo está registrado, recibirá un enlace para recuperar su contraseña.'

        ]);
    }

    /**
     * Resetear contraseña.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([

            'token' => 'required',

            'email' => 'required|email',

            'password' => PasswordPolicy::required(),   /* RN10 */

            'activation' => 'sometimes|boolean'

        ], PasswordPolicy::messages());

        /* Activación (correo de registro): enlace de 7 días, solo para cuentas
           que nunca se activaron. Así un enlace de «olvidé mi contraseña» no
           gana 7 días de vigencia pasando activation=1. */
        $pending = User::where('email', $request->input('email'))->value('email_verified_at') === null;
        $broker  = $request->boolean('activation') && $pending ? 'activations' : null;

        $resetUser = null;

        /* Todo en una sola transacción (review 2026-09-29): guardar la
           contraseña, revocar los tokens (paso 10) y auditar deben ocurrir
           juntos o no ocurrir — si la auditoría fallara después de guardar
           la contraseña, no debe quedar una contraseña nueva con sesiones
           viejas todavía vigentes. */
        $status = DB::transaction(function () use ($broker, $request, &$resetUser) {

            $status = Password::broker($broker)->reset(

                $request->only(
                    'email',
                    'password',
                    'password_confirmation',
                    'token'
                ),

                function ($user, $password) use (&$resetUser) {

                    $user->forceFill([

                        'password' => Hash::make($password),

                        /* definir la contraseña desde el correo verifica el correo */
                        'email_verified_at' => $user->email_verified_at ?? now(),

                    ])->save();

                    $resetUser = $user;
                }
            );

            /* Paso 10 / RNF03: la contraseña nueva invalida TODAS las sesiones
               abiertas de ese usuario (no solo el dispositivo que la cambió) —
               si alguien tenía acceso con la contraseña vieja, la pierde. */
            if ($status === Password::PASSWORD_RESET) {
                $resetUser->tokens()->delete();

                Audit::create([
                    'user_id'    => $resetUser->id,
                    'action'     => 'PASSWORD_RESET',
                    'table_name' => 'users',
                    'record_id'  => $resetUser->id,
                ]);
            }

            return $status;
        });

        if ($status !== Password::PASSWORD_RESET) {

            /* CU04 E1: token vencido, ya usado, o correo sin coincidencia */
            throw ValidationException::withMessages([

                'email' => [$status === Password::INVALID_TOKEN || $status === Password::INVALID_USER
                    ? 'El enlace no es válido o expiró.'
                    : __($status)]

            ]);
        }

        return response()->json([

            'message' => 'Contraseña actualizada correctamente'

        ]);
    }
}