<?php

// #archivo: backend/app/Http/Controllers/Api/UserController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

use App\Http\Resources\UserResource;

use App\Models\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

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

        $user = User::create([

            'name' => $validated['name'],

            'email' => $validated['email'],

            'role' => $validated['role'],

            'status' => $validated['status'],

            'password' => Hash::make(
                $validated['password']
            )

        ]);

        return response()->json([

            'message' => 'Usuario creado correctamente',

            'user' => new UserResource($user)

        ], 201);
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