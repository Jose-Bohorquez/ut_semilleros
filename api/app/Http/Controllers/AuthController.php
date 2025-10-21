<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Class AuthController
 * 
 * Handles all authentication processes of the application.
 * (Gestiona todos los procesos de autenticación de la aplicación.)
 * 
 * Includes:
 * - User registration (Registro de usuarios)
 * - Login and token issuance (Inicio de sesión y generación de token)
 * - Logout (Cierre de sesión)
 * - Retrieval of authenticated user data (Datos del usuario autenticado)
 */
class AuthController extends Controller
{
    /**
     * Register a new user and generate an API token.
     * (Registra un nuevo usuario y genera un token de autenticación.)
     * 
     * Example request body (JSON):
     * {
     *   "name": "Jose Bohorquez",
     *   "email": "jose@example.com",
     *   "password": "123456"
     * }
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // ✅ Validate input fields
        // (Valida los campos enviados en la petición)
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        // 🧱 Create new user (Crea un nuevo usuario)
        // Laravel automatically hashes the password because of 'hashed' cast in the model.
        // (Laravel encripta la contraseña automáticamente gracias al cast en el modelo.)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        // 🔐 Generate personal access token (Genera un token de acceso personal)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 📤 Return the user info and token (Devuelve los datos del usuario y token)
        return response()->json([
            'message' => 'User registered successfully / Usuario registrado con éxito',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Log in an existing user using email and password.
     * (Inicia sesión con email y contraseña.)
     * 
     * Example request body (JSON):
     * {
     *   "email": "jose@example.com",
     *   "password": "123456"
     * }
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // ✅ Validate input data (Valida los datos de entrada)
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 🔍 Look for user by email (Busca el usuario por su correo)
        $user = User::where('email', $request->email)->first();

        // ❌ If user not found or password mismatch, throw exception
        // (Si no se encuentra el usuario o la contraseña no coincide, lanza excepción)
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Incorrect credentials / Credenciales incorrectas'],
            ]);
        }

        // 🔐 Generate new token for this session
        // (Genera un nuevo token para esta sesión)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 📤 Return token and user info
        // (Devuelve el token y los datos del usuario)
        return response()->json([
            'message' => 'Login successful / Inicio de sesión correcto',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Log out and revoke current access token.
     * (Cierra la sesión y revoca el token actual.)
     * 
     * Example:
     *   Header → Authorization: Bearer {token}
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // 🧹 Delete the current user's token
        // (Elimina el token activo del usuario actual)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully / Sesión cerrada correctamente',
        ]);
    }

    /**
     * Retrieve the authenticated user's data.
     * (Obtiene los datos del usuario autenticado.)
     * 
     * Example:
     *   Header → Authorization: Bearer {token}
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
