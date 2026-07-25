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

            'password' => [

                'required',
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