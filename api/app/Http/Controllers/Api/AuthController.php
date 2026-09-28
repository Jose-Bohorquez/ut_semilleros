<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Audit;
use App\Models\User;
use App\Services\Auth\GoogleAuthException;
use App\Services\Auth\GoogleIdTokenVerifier;

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
     * Actualizar perfil del usuario autenticado.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'password'              => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ]);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => new UserResource($user),
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

        $status = Password::sendResetLink(

            $request->only('email')

        );

        return response()->json([

            'message' => __($status)

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

            'password' => 'required|min:6|confirmed',

            'activation' => 'sometimes|boolean'

        ]);

        /* Activación (correo de registro): enlace de 7 días, solo para cuentas
           que nunca se activaron. Así un enlace de «olvidé mi contraseña» no
           gana 7 días de vigencia pasando activation=1. */
        $pending = User::where('email', $request->input('email'))->value('email_verified_at') === null;
        $broker  = $request->boolean('activation') && $pending ? 'activations' : null;

        $status = Password::broker($broker)->reset(

            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),

            function ($user, $password) {

                $user->forceFill([

                    'password' => Hash::make($password),

                    /* definir la contraseña desde el correo verifica el correo */
                    'email_verified_at' => $user->email_verified_at ?? now(),

                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {

            throw ValidationException::withMessages([

                'email' => [__($status)]

            ]);
        }

        return response()->json([

            'message' => __($status)

        ]);
    }
}