<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transformar recurso a array.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,

            'name' => $this->name,

            'email' => $this->email,

            'role' => $this->role,

            'status' => $this->status,

            /* CU05 paso 2: el perfil muestra el teléfono */
            'phone' => $this->phone,

            /* RNF05 / RN02: referencia del comunicado que autorizó el alta */
            'authorization_reference' => $this->authorization_reference,

            'profile_photo' => $this->profile_photo,

            /* RF16: la PWA pide la autorización de datos si viene null */
            'data_consent_at' => $this->data_consent_at,

            /* CU06-A1: último inicio de sesión, visible en el detalle */
            'last_login_at' => $this->last_login_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}