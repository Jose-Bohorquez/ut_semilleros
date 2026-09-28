<?php
// #archivo: /backend/app/Support/PasswordPolicy.php
// RN10: mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.
// Reglas de servidor centralizadas para no repetirlas en cada validate()
// (reset-password, activación, perfil, alta y edición de usuario por el
// administrador). El cliente valida lo mismo en core/password-policy.js
// para feedback inmediato, pero el servidor manda.

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    /** Contraseña obligatoria (registro, reset). */
    public static function required(): array
    {
        return ['required', 'confirmed', self::rule()];
    }

    /** Contraseña opcional (perfil, edición de usuario: solo si se quiere cambiar). */
    public static function optional(): array
    {
        return ['nullable', 'confirmed', self::rule()];
    }

    private static function rule(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
    }

    public static function messages(): array
    {
        return [
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed'     => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers'   => 'La contraseña debe incluir al menos un número.',
            'password.symbols'   => 'La contraseña debe incluir al menos un símbolo (ej: @, #, %, !).',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }
}
