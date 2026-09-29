<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\PasswordPolicy;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function messages(): array
    {
        return PasswordPolicy::messages() + [
            'authorization_reference.required' => 'La referencia de autorización es obligatoria para usuarios del panel web (RN02).',
            'role.in' => 'Los estudiantes no se crean uno por uno aquí: usa «Importar usuarios» (carga masiva) o el login con Google institucional (CU02).',
        ];
    }

    public function rules(): array
    {
        return [

            'name' => [

                'required',
                'string',
                'max:255'

            ],

            'email' => [

                'required',
                'email',
                'unique:users,email'

            ],

            /* Si el admin no manda contraseña, se genera una aleatoria interna
               y se le envía al usuario un correo de activación para que
               defina la suya (Jose, 2026-08-31). Si sí la manda (flujo viejo
               desde el formulario individual), se usa esa y no se envía
               correo — se mantiene igual que antes. RN10 aplica en ambos casos
               cuando el admin escribe la contraseña a mano. */
            'password' => PasswordPolicy::optional(),

            /* CU06-A4: los estudiantes no se crean aquí uno por uno — se
               crean por carga masiva (UserController::import(), que valida
               aparte y sí permite ESTUDIANTE) o por su cuenta institucional
               de Google (CU02). Decisión de Jose, 2026-09-29: mantener la
               carga masiva de estudiantes tal como está, restringir solo
               este formulario individual a los 3 roles del panel web. */
            'role' => [

                'required',
                'in:ADMIN_SISTEMA,LIDER_SEMILLERO,ADMINISTRATIVO'

            ],

            'status' => [

                'required',
                'in:ACTIVO,INACTIVO'

            ],

            /* RNF05 / RN02: todo usuario del panel web (no ESTUDIANTE, que
               usa la PWA) se crea con la referencia del comunicado que lo
               autorizó. */
            'authorization_reference' => [

                Rule::requiredIf(fn () => $this->input('role') !== 'ESTUDIANTE'),
                'nullable',
                'string',
                'max:255',

            ],
        ];
    }
}