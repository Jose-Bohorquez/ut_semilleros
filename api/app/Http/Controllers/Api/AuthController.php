<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Audit;
use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        $token = DB::transaction(function () use ($user, $expiresAt) {

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
        $request
            ->user()
            ->currentAccessToken()
            ->delete();

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

            'password' => 'required|min:6|confirmed'

        ]);

        $status = Password::reset(

            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),

            function ($user, $password) {

                $user->forceFill([

                    'password' => Hash::make($password)

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