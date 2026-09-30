<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CU26 «Mis propuestas»: forma pública de una propuesta para su DUEÑO.
 *
 * Solo se usa en GET /proposals/my (RN13: el estudiante ve únicamente las suyas),
 * por eso `phone` (cifrado en reposo, RNF03) se devuelve descifrado: es el dato del
 * propio estudiante. No se expone el id del usuario ni la identidad del evaluador
 * (`reviewed_by`): la spec habla de «observación del evaluador», no de quién es.
 */
class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'created_at'   => $this->created_at,
            'title'        => $this->title,
            'description'  => $this->description,
            'program_id'   => $this->program_id,
            'program'      => $this->program ? ['id' => $this->program->id, 'name' => $this->program->name] : null,
            'areas'        => $this->areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
            /* `status` conserva el valor interno (lo usan el dashboard y la edición);
               `status_label` es el vocabulario de la spec: Recibida, Viable, Archivada. */
            'status'       => $this->status,
            'status_label' => $this->status_label,
            'review_note'  => $this->review_note,
            'reviewed_at'  => $this->reviewed_at,
            'phone'        => $this->phone,
        ];
    }
}
