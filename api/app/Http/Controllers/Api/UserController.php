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
        /* CU06 es solo del Administrador. Líder y Administrativo conservan un
           listado MÍNIMO (id, nombre, rol y estado) porque lo usan los
           selectores de destinatario de notificaciones y de miembros de
           proyecto; sin correo ni referencia de autorización (RN02), que son
           datos personales/administrativos de todos los usuarios. */
        $columns = auth()->user()?->role === 'ADMIN_SISTEMA'
            ? ['id', 'name', 'email', 'role', 'status', 'authorization_reference', 'created_at']
            : ['id', 'name', 'role', 'status'];

        $users = User::select($columns)->get();

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

        /* CU06-A1: "para líderes, los semilleros a su cargo" (pivot
           seedbed_user.role = LIDER). */
        $seedbeds = $user->role === 'LIDER_SEMILLERO'
            ? $user->seedbeds()->wherePivot('role', 'LIDER')->get(['seedbeds.id', 'seedbeds.name', 'seedbeds.status'])
            : null;

        return response()->json([

            'user' => new UserResource($user),

            'led_seedbeds' => $seedbeds,

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

            /* RNF05 / RN02: obligatoria para todo rol distinto de ESTUDIANTE
               (la exige StoreUserRequest; el import masivo no crea estos roles). */
            'authorization_reference' => $validated['authorization_reference'] ?? null,

        ]);

        $correoEnviado = true;

        if ($necesitaActivacion) {
            try {
                $token = Password::broker('activations')->createToken($user);
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
                /* RNF05 / RN02: obligatoria en la carga masiva también,
                   cuando la fila no es un ESTUDIANTE (poco común, pero la
                   carga admite cualquier rol). */
                'authorization_reference' => [
                    \Illuminate\Validation\Rule::requiredIf(($fila['role'] ?? null) !== 'ESTUDIANTE'),
                    'nullable', 'string', 'max:255',
                ],
            ], [
                'authorization_reference.required' => 'La referencia de autorización es obligatoria para este rol (RN02).',
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
                    'authorization_reference' => $fila['authorization_reference'] ?? null,
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

        /* CU06-E3: no permitir auto-inactivarse ni inactivar al último
           ADMIN_SISTEMA activo (dejaría el sistema sin nadie que administre). */
        if ($validated['status'] === 'INACTIVO' && $user->status === 'ACTIVO') {
            $this->guardAgainstLockout($user);
        }

        /* CU06-E3 (H2): degradar el rol de un ADMIN_SISTEMA tiene el mismo
           efecto que inactivarlo — no a sí mismo ni al último admin activo. */
        if ($user->role === 'ADMIN_SISTEMA' && $validated['role'] !== 'ADMIN_SISTEMA') {
            $this->guardAgainstLockout($user, 'role');
        }

        $user->name = $validated['name'];

        $user->email = $validated['email'];

        $user->role = $validated['role'];

        $user->status = $validated['status'];

        $user->authorization_reference = $validated['authorization_reference'] ?? null;

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

        /* Mismo criterio que toggleStatus: inactivar desde el formulario de
           edición también revoca sus sesiones (C-04, 2026-09-27). */
        if ($user->status === 'INACTIVO') {
            $user->tokens()->delete();
        }

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

        /* CU06-E3: solo aplica al pasar de ACTIVO a INACTIVO. */
        if ($user->status === 'ACTIVO') {
            $this->guardAgainstLockout($user);
        }

        $user->status =

            $user->status === 'ACTIVO'
            ? 'INACTIVO'
            : 'ACTIVO';

        $user->save();

        /* Al inactivar, revocar todas sus sesiones: los tokens Sanctum no
           vencen, así que sin esto el usuario seguía entrando con el token
           que ya tenía (C-04, 2026-09-27). El middleware 'active' cubre además
           los tokens de usuarios inactivados antes de este cambio. */
        if ($user->status === 'INACTIVO') {
            $user->tokens()->delete();
        }

        return response()->json([

            'message' => 'Estado actualizado',

            'user' => new UserResource($user)

        ]);
    }

    /**
     * CU06-E3: impide inactivar al propio admin autenticado o al último
     * ADMIN_SISTEMA activo del sistema.
     */
    private function guardAgainstLockout(User $user, string $field = 'status'): void
    {
        $mensaje = $field === 'role'
            ? 'No es posible cambiar el rol de este usuario.'
            : 'No es posible inactivar este usuario.';

        if ($user->id === auth()->id()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                $field => [$mensaje],
            ]);
        }

        /* Un admin ya inactivo no cuenta como «activo»: cambiarle el rol no
           deja al sistema con menos administradores operativos. */
        if ($user->role === 'ADMIN_SISTEMA' && $user->status === 'ACTIVO') {
            $otrosAdminsActivos = User::where('role', 'ADMIN_SISTEMA')
                ->where('status', 'ACTIVO')
                ->where('id', '!=', $user->id)
                ->exists();

            if (!$otrosAdminsActivos) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    $field => [$mensaje],
                ]);
            }
        }
    }

    /**
     * CU06-E4: reenvía el correo de activación (nuevo token, invalida el
     * anterior) — para cuando el primer envío falló o el correo se perdió.
     */
    public function resendActivation($id): JsonResponse
    {
        $user = User::findOrFail($id);

        /* CU06-H3: con el SMTP caído esto lanzaba una excepción sin capturar
           (500 genérico). Ahora es un 503 controlado con mensaje claro; el
           detalle técnico va al log sin correo ni token. */
        try {
            $token = Password::broker('activations')->createToken($user);
            $user->notify(new AccountActivationNotification($token));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[CU06] No se pudo reenviar el correo de activación',
                ['user_id' => $user->id, 'error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'No se pudo enviar el correo de activación. Intenta de nuevo más tarde.',
            ], 503);
        }

        return response()->json([
            'message' => 'Correo de activación reenviado.',
        ]);
    }
}