<?php

namespace App\Models;

use App\Notifications\CustomResetPasswordNotification;

use Laravel\Sanctum\HasApiTokens;

use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Modelo User
 *
 * RF01 - Gestión usuarios
 * CU01 - Ciclo de vida usuario
 */
class User extends Authenticatable
{
    /*
    |--------------------------------------------------------------------------
    | Traits
    |--------------------------------------------------------------------------
    */

    use HasApiTokens, HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'name',

        'email',

        'password',

        'role',

        'status',

        'phone',

        'authorization_reference',

        'profile_photo',

        /* google_id y data_consent_at NO van aquí: solo se escriben con
           forceFill en AuthController (CU02 / RF16), nunca desde un formulario. */

        'created_by',

        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Hidden Attributes
    |--------------------------------------------------------------------------
    */

    protected $hidden = [

        'password',

        'google_id',

        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',

            'data_consent_at' => 'datetime',

            'password' => 'hashed',

            /* RNF12: dato personal cifrado en reposo con APP_KEY (mismo
               patrón que Coordinator::phone). */
            'phone' => 'encrypted',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Domain Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Verifica si usuario está activo.
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVO';
    }

    /**
     * Verifica rol.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Semilleros a los que pertenece el usuario (integrante).
     */
    public function seedbeds()
    {
        return $this->belongsToMany(Seedbed::class, 'seedbed_user')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    /**
     * Notificación personalizada reset password.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(

            new CustomResetPasswordNotification($token)

        );
    }
}