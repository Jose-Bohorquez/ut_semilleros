/* #archivo: /frontend/core/password-policy.js
   RN10: mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.
   Validación en el cliente (feedback inmediato) — el servidor la repite
   siempre (Illuminate\Validation\Rules\Password, ver api/app/Support/PasswordPolicy.php).
   Compartido por reset-password.module.js, pwa-profile.module.js y el campo
   password de core/crud.engine.js, para no repetir la regla 3 veces. */

export const PASSWORD_HINT = "Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.";

/**
 * @returns {string|null} mensaje de error, o null si la contraseña cumple RN10.
 */
export function passwordPolicyError(value) {
    if (!value) return null;   /* "obligatorio" lo valida aparte cada formulario */
    if (value.length < 8) return "La contraseña debe tener al menos 8 caracteres";
    if (!/[A-ZÁÉÍÓÚÑ]/.test(value)) return "Debe incluir al menos una mayúscula";
    if (!/[a-záéíóúñ]/.test(value)) return "Debe incluir al menos una minúscula";
    if (!/[0-9]/.test(value)) return "Debe incluir al menos un número";
    if (!/[^A-Za-z0-9]/.test(value)) return "Debe incluir al menos un símbolo (ej: @, #, %, !)";
    return null;
}
