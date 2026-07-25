<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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

            ]
        ];
    }
}