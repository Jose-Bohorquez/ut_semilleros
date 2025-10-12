<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 👈 Sanctum: trait necesario para manejar tokens de API

/**
 * Class User
 * 
 * Represents an authenticated user in the application.
 * (Representa a un usuario autenticado en la aplicación)
 *
 * Each instance of this model corresponds to a record in the 'users' table.
 * (Cada instancia de este modelo corresponde a un registro en la tabla 'users')
 */
class User extends Authenticatable
{
    /** 
     * Include traits for factory support, notifications, and API tokens.
     * (Incluye traits para soportar fábricas, notificaciones y tokens de API)
     *
     * - HasFactory → Permite crear usuarios desde factories en pruebas o seeds.
     * - Notifiable → Permite enviar notificaciones (correo, etc.).
     * - HasApiTokens → Proporciona autenticación basada en tokens (Laravel Sanctum).
     *
     * Ejemplo práctico:
     *   $user = User::factory()->create();
     *   $token = $user->createToken('auth_token')->plainTextToken;
     */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     * (Atributos que se pueden asignar de forma masiva)
     *
     * Estos son los campos que Laravel permite rellenar automáticamente
     * desde un formulario o request.
     * 
     * Ejemplo:
     *   User::create([
     *       'name' => 'Jose Bohorquez',
     *       'email' => 'jose@example.com',
     *       'password' => bcrypt('123456'),
     *   ]);
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * (Atributos que deben ocultarse al convertir el modelo en JSON)
     *
     * Esto evita que se expongan datos sensibles, como la contraseña o tokens.
     * 
     * Ejemplo:
     *   return response()->json($user);
     *   → No incluirá 'password' ni 'remember_token' en el resultado.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     * (Obtiene los atributos que deben convertirse automáticamente)
     *
     * Laravel convierte estos campos a los tipos definidos.
     * 
     * Ejemplo:
     *   - 'email_verified_at' → convierte la fecha a objeto Carbon.
     *   - 'password' => 'hashed' → indica que Laravel debe hashear
     *     automáticamente cualquier valor asignado a "password".
     *
     * Ejemplo:
     *   $user->password = '123456';
     *   → Se guarda automáticamente como hash (bcrypt).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
