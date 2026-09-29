<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\PasswordPolicy;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function messages(): array
    {
        return PasswordPolicy::messages() + [
            'authorization_reference.required' => 'La referencia de autorización es obligatoria para usuarios del panel web (RN02).',
        ];
    }

    public function rules(): array
    {
        return [

            'name' => [

                'required',
                'string'

            ],

            'email' => [

                'required',
                'email',

                Rule::unique('users', 'email')
                    ->ignore($this->route('id'))

            ],

            'role' => [

                'required',
                'in:ADMIN_SISTEMA,ESTUDIANTE,LIDER_SEMILLERO,ADMINISTRATIVO'

            ],

            'status' => [

                'required',
                'in:ACTIVO,INACTIVO'

            ],

            /* Antes NO estaba en las reglas: $request->validated()['password']
               nunca existía y el admin no podía cambiar la contraseña de un
               usuario desde la edición (bug encontrado en la revisión de
               RN10, 2026-09-28). Opcional: en blanco, la contraseña actual
               no cambia. */
            'password' => PasswordPolicy::optional(),

            /* RNF05 / RN02 */
            'authorization_reference' => [

                Rule::requiredIf(fn () => $this->input('role') !== 'ESTUDIANTE'),
                'nullable',
                'string',
                'max:255',

            ],
        ];
    }
}