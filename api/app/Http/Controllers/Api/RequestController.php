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