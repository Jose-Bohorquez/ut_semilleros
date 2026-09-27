<?php

// #archivo: backend/app/Http/Controllers/Api/UserController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

use App\Http\Resources\UserResource;

use App\Models\User;
use App\Notifications\AccountActivationNotification;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Listar usuarios.
     *
     * RF01 - Gestión usuarios
     */
    public function index(): JsonResponse
    {
        $users = User::select(

            'id',
            'name',
            'email',
            'role',
            'status',
            'created_at'

        )->get();

        return response()->json([

            'users' => $users

        ]);
    }

    /**
     * Consultar usuario individual.
     *
     * RF01 / CU01
     */
    public function show($id): JsonResponse
    {
        $user = User::findOrFail($id);

        return response()->json([

            'user' => new UserResource($user)

        ]);
    }

    /**
     * Crear usuario.
     *
     * RF01 / CU01
     */
    public function store(
        StoreUserRequest $request
    ): JsonResponse {

        $validated = $request->validated();

        [$user, $necesitaActivacion, $correoEnviado] = $this->createUserRecord($validated);

        $message = 'Usuario creado correctamente';
        if ($necesitaActivacion) {
            $message .= $correoEnviado
                ? '. Se envió un correo de activación.'
                : '. ⚠ El usuario se creó pero el correo de activación no pudo enviarse — revisa la configuración SMTP.';
        }

        return response()->json([

            'message' => $message,

            'user' => new UserResource($user)

        ], 201);
    }

    /**
     * Crear usuario en la base de datos.
     * Si no se manda contraseña, se genera una interna (nadie la conoce, ni
     * siquiera el admin) y se dispara el correo de activación para que el
     * propio usuario defina la suya. Reutilizado por store() e import().
     *
     * El envío del correo se separa en su propio try/catch: si el usuario ya
     * quedó creado en la BD pero el correo falla (ej. SMTP caído), eso se
     * reporta aparte — no se pierde silenciosamente ni se confunde con un
     * fallo de creación (que dejaría al usuario sin cuenta y sin correo).
     *
     * @return array{0: User, 1: bool, 2: bool} [usuario, necesitaba activación, correo enviado ok]
     */
    private function createUserRecord(array $validated): array
    {
        $necesitaActivacion = empty($validated['password']);

        $passwordPlano = $necesitaActivacion
            ? Str::random(40)
            : $validated['password'];

        $user = User::create([

            'name' => $validated['name'],

            'email' => $validated['email'],

            'role' => $validated['role'],

            'status' => $validated['status'] ?? 'ACTIVO',

            'password' => Hash::make($passwordPlano),

        ]);

        $correoEnviado = true;

        if ($necesitaActivacion) {
            try {
                $token = Password::createToken($user);
                $user->notify(new AccountActivationNotification($token));
            } catch (\Throwable $e) {
                $correoEnviado = false;
                \Illuminate\Support\Facades\Log::warning(
                    '[Activación] No se pudo enviar el correo de activación',
                    ['user_id' => $user->id, 'email' => $user->email, 'error' => $e->getMessage()]
                );
            }
        }

        return [$user, $necesitaActivacion, $correoEnviado];
    }

    /**
     * Carga masiva de usuarios (RF01 — pedido por Jose 2026-08-31, ej. subir
     * un listado de estudiantes desde Excel/CSV). Cada fila crea un usuario
     * sin contraseña asignada por el admin → dispara correo de activación.
     * No falla todo el lote si una fila falla: cada una se procesa aparte y
     * se reporta individualmente para poder corregir solo esa.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'users'               => 'required|array|min:1|max:500',
            'users.*.name'        => 'required|string|max:255',
            'users.*.email'       => 'required|email|max:255',
            'users.*.role'        => 'required|in:ADMIN_SISTEMA,ESTUDIANTE,LIDER_SEMILLERO,ADMINISTRATIVO',
        ]);

        $resultados = [];

        foreach ($request->input('users') as $i => $fila) {

            $email = trim($fila['email'] ?? '');

            $validator = Validator::make($fila, [
                'name'  => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'role'  => 'required|in:ADMIN_SISTEMA,ESTUDIANTE,LIDER_SEMILLERO,ADMINISTRATIVO',
            ]);

            if ($validator->fails()) {
                $resultados[] = [
                    'fila'    => $i + 1,
                    'email'   => $email,
                    'success' => false,
                    'message' => implode(' ', $validator->errors()->all()),
                ];
                continue;
            }

            try {
                [$user, , $correoEnviado] = $this->createUserRecord([
                    'name'   => $fila['name'],
                    'email'  => $email,
                    'role'   => $fila['role'],
                    'status' => 'ACTIVO',
                ]);

                $resultados[] = [
                    'fila'    => $i + 1,
                    'email'   => $email,
                    'success' => true,
                    'user_id' => $user->id,
                    'message' => $correoEnviado
                        ? 'Creado — correo de activación enviado'
                        : 'Creado, pero el correo de activación falló (revisa SMTP)',
                    'mail_ok' => $correoEnviado,
                ];
            } catch (\Throwable $e) {
                $resultados[] = [
                    'fila'    => $i + 1,
                    'email'   => $email,
                    'success' => false,
                    'message' => 'Error inesperado al crear el usuario',
                ];
            }
        }

        $creados = count(array_filter($resultados, fn ($r) => $r['success']));

        return response()->json([
            'message'   => "Procesados {$creados} de " . count($resultados) . ' usuarios',
            'resultados' => $resultados,
        ], 200);
    }

    /**
     * Actualizar usuario.
     *
     * RF01 / CU01
     */
    public function update(
        UpdateUserRequest $request,
        $id
    ): JsonResponse {

        $user = User::findOrFail($id);

        $validated = $request->validated();

        $user->name = $validated['name'];

        $user->email = $validated['email'];

        $user->role = $validated['role'];

        $user->status = $validated['status'];

        /**
         * Actualizar password
         * solo si viene informado.
         */
        if (!empty($validated['password'])) {

            $user->password = Hash::make(
                $validated['password']
            );
        }

        $user->save();

        return response()->json([

            'message' => 'Usuario actualizado correctamente',

            'user' => new UserResource($user)

        ]);
    }

    /**
     * Activar / inactivar usuario.
     *
     * RF01:
     * No eliminación física.
     */
    public function toggleStatus($id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->status =

            $user->status === 'ACTIVO'
            ? 'INACTIVO'
            : 'ACTIVO';

        $user->save();

        return response()->json([

            'message' => 'Estado actualizado',

            'user' => new UserResource($user)

        ]);
    }
}