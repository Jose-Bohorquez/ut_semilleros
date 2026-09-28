<?php #archiv: backend/app/Http/Controllers/Api/RequestController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipRequest as RequestModel;

class RequestController extends Controller
{

    public function index()
    {

        $requests = RequestModel::with([
            'user:id,name',
            'seedbed:id,name'
        ])->get();

        return response()->json([
            "requests"=>$requests
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "seedbed_id"=>"required|exists:seedbeds,id",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ]);

        /* Seguridad (hallazgo C-02, 2026-09-27): un ESTUDIANTE solo puede
           postularse a sí mismo y siempre queda PENDIENTE — el user_id y el
           status que mande el cliente se ignoran. Antes podía crear su
           postulación ya APROBADA o a nombre de otro estudiante. La
           aprobación solo ocurre por PUT /requests/{id}/update-status (L, ADM). */
        if (auth()->user()->role === 'ESTUDIANTE') {
            $validated['user_id'] = auth()->id();
            $validated['status']  = 'PENDIENTE';
        }

        /* Regla de negocio (Jose, 2026-07-28): un estudiante no puede tener
           más de una postulación activa a la vez. Si ya tiene una PENDIENTE
           (esperando revisión) o APROBADA (ya es integrante de un semillero),
           no puede postularse a otro. RECHAZADA no cuenta — sí puede volver
           a intentarlo en otro semillero. */
        $yaTieneActiva = RequestModel::where('user_id', $validated['user_id'])
            ->whereIn('status', ['PENDIENTE', 'APROBADA'])
            ->exists();

        if ($yaTieneActiva) {
            return response()->json([
                'message' => 'Ya tienes una postulación pendiente o aprobada. No puedes postularte a otro semillero mientras esa siga activa.',
            ], 422);
        }

        $requestModel = RequestModel::create($validated);

        return response()->json([
            "message"=>"Solicitud creada",
            "request"=>$requestModel
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
        $requests = RequestModel::with(['user:id,name', 'seedbed:id,name'])
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