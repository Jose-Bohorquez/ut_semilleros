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

            'profile_photo' => $this->profile_photo,

            /* RF16: la PWA pide la autorización de datos si viene null */
            'data_consent_at' => $this->data_consent_at,

            'created_at' => $this->created_at,

            'updated_at' => $this->updated_at,

        ];
    }
}