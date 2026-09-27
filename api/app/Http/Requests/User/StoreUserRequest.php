<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
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
               correo — se mantiene igual que antes. */
            'password' => [

                'nullable',
                'min:6'

            ],

            'role' => [

                'required',
                'in:ADMIN_SISTEMA,ESTUDIANTE,LIDER_SEMILLERO,ADMINISTRATIVO'

            ],

            'status' => [

                'required',
                'in:ACTIVO,INACTIVO'

            ]
        ];
    }
}