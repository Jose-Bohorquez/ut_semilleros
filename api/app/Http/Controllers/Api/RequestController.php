<?php #archiv: backend/app/Http/Controllers/Api/RequestController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipRequest as RequestModel;
use App\Models\Seedbed;

class RequestController extends Controller
{

    private const MESSAGES = [
        'program_id.required' => 'Debes seleccionar un programa.',
        'phone.required'      => 'El teléfono es obligatorio.',
        'phone.regex'         => 'El teléfono debe tener entre 7 y 15 dígitos.',
        'message.required'    => 'El mensaje es obligatorio.',
        'message.min'         => 'El mensaje debe tener al menos 10 caracteres.',
        'message.max'         => 'El mensaje no puede superar los 1000 caracteres.',
    ];

    public function index()
    {

        $requests = RequestModel::with([
            'user:id,name',
            'seedbed:id,name',
            'program:id,name'
        ])->get();

        return response()->json([
            "requests"=>$requests
        ]);

    }


    /**
     * CU22: el estudiante se postula a un semillero (paso 2 del flujo
     * básico) — programa (de los activos del semillero), teléfono
     * (7-15 dígitos) y mensaje (10-1000 caracteres).
     */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "seedbed_id"=>"required|exists:seedbeds,id",

            "program_id"=>"required|integer",

            "phone"=>["required", "string", "regex:/^[0-9]{7,15}$/"],

            "message"=>"required|string|min:10|max:1000",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ], self::MESSAGES);

        /* Seguridad (hallazgo C-02, 2026-09-27): un ESTUDIANTE solo puede
           postularse a sí mismo y siempre queda PENDIENTE — el user_id y el
           status que mande el cliente se ignoran. Antes podía crear su
           postulación ya APROBADA o a nombre de otro estudiante. La
           aprobación solo ocurre por PUT /requests/{id}/update-status (L, ADM). */
        if (auth()->user()->role === 'ESTUDIANTE') {
            $validated['user_id'] = auth()->id();
            $validated['status']  = 'PENDIENTE';
        }

        /* E4 (CU22): el semillero debe estar activo. */
        $seedbed = Seedbed::findOrFail($validated['seedbed_id']);
        if ($seedbed->status !== 'ACTIVO') {
            return response()->json([
                'message' => 'Este semillero ya no recibe solicitudes.',
            ], 409);
        }

        /* El programa debe ser uno de los activos del semillero (CU22 paso 2). */
        $perteneceAlSemillero = $seedbed->programs()->where('programs.id', $validated['program_id'])->exists();
        if (!$perteneceAlSemillero) {
            return response()->json([
                'message' => 'El programa seleccionado no pertenece a este semillero.',
                'errors' => ['program_id' => ['El programa seleccionado no pertenece a este semillero.']],
            ], 422);
        }

        /* Regla de negocio (Jose, 2026-07-28): un estudiante no puede tener
           más de una postulación activa a la vez. Si ya tiene una PENDIENTE
           (esperando revisión) o APROBADA (ya es integrante de un semillero),
           no puede postularse a otro. RECHAZADA no cuenta — sí puede volver
           a intentarlo en otro semillero. (Nota: la spec de CU22/RN05 solo
           exige esto por semillero, no a nivel de todo el sistema — esta
           regla más estricta ya estaba decidida por el proyecto y se
           mantiene tal cual). */
        $yaTieneActiva = RequestModel::where('user_id', $validated['user_id'])
            ->whereIn('status', ['PENDIENTE', 'APROBADA'])
            ->exists();

        if ($yaTieneActiva) {
            return response()->json([
                'message' => 'Ya tienes una postulación pendiente o aprobada. No puedes postularte a otro semillero mientras esa siga activa.',
            ], 409);
        }

        $requestModel = RequestModel::create($validated);

        return response()->json([
            "message"=>"Solicitud creada",
            "request"=>$requestModel->load(['seedbed:id,name', 'program:id,name'])
        ],201);

    }


    public function update(Request $request,$id)
    {

        $requestModel = RequestModel::findOrFail($id);

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "seedbed_id"=>"required|exists:seedbeds,id",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ]);

        $requestModel->update($validated);

        return response()->json([
            "message"=>"Solicitud actualizada",
            "request"=>$requestModel
        ]);

    }


    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
        ]);

        if (auth()->user()->role === 'ESTUDIANTE') {
            return response()->json(['message' => 'No autorizado para cambiar estado'], 403);
        }

        $req = RequestModel::findOrFail($id);
        $req->update([
            'status'      => $validated['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Estado actualizado a ' . $validated['status'],
            'request' => $req,
        ]);
    }

    public function myRequests()
    {
        $user = auth()->user();
        $requests = RequestModel::with(['user:id,name', 'seedbed:id,name', 'program:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'La eliminación no está permitida. Use cambio de estado.',
        ], 405);
    }

}