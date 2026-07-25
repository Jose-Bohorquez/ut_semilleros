<?php # archivo: backend/app/Http/Controllers/Api/SeedbedMemberController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Seedbed;

class SeedbedMemberController extends Controller
{

    /**
     * Listar miembros de un semillero
     */
    public function index($seedbedId)
    {

        $seedbed = Seedbed::with([
            'users:id,name,email'
        ])->findOrFail($seedbedId);

        return response()->json([
            "members" => $seedbed->users
        ]);

    }



    /**
     * Agregar miembro
     */
    public function store(Request $request,$seedbedId)
    {

        $validated = $request->validate([

            "user_id" => "required|exists:users,id",
            "role" => "required|in:LIDER,INVESTIGADOR,AUXILIAR"

        ]);

        $seedbed = Seedbed::findOrFail($seedbedId);

        $seedbed->users()->attach(

            $validated["user_id"],

            ["role"=>$validated["role"]]

        );

        return response()->json([
            "message"=>"Miembro agregado"
        ],201);

    }



    /**
     * Eliminar miembro
     */
    public function destroy($seedbedId,$userId)
    {

        $seedbed = Seedbed::findOrFail($seedbedId);

        $seedbed->users()->detach($userId);

        return response()->json([
            "message"=>"Miembro eliminado"
        ]);

    }

}