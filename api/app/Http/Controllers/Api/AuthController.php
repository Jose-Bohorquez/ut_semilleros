<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login usuario.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([

            'email' => 'required|email',

            'password' => 'required'

        ]);

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {

            return response()->json([

                'message' => 'Credenciales incorrectas'

            ], 401);
        }

        if ($user->status !== 'ACTIVO') {

            return response()->json([

                'message' => 'Usuario inactivo'

            ], 403);
        }

        $token = $user->createToken(
            'auth_token'
        )->plainTextToken;

        return response()->json([

            'message' => 'Inicio de sesión exitoso',

            'user' => new UserResource($user),

            'token' => $token

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