<?php # archiv: backend/app/Http/Controllers/Api/CoordinatorController.php
# archivo: backend/app/Http/Controllers/Api/CoordinatorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coordinator;

class CoordinatorController extends Controller
{

    /**
     * Listar coordinadores
     */
    public function index()
    {

        $coordinators = Coordinator::get();

        return response()->json([
            "coordinators"=>$coordinators
        ]);

    }



    /**
     * Crear coordinador
     */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "email"=>"required|email|unique:coordinators,email",

            "phone"=>"nullable|string|max:50",

            "status"=>"required|in:ACTIVO,INACTIVO"

        ]);

        $coordinator = Coordinator::create($validated);

        return response()->json([
            "message"=>"Coordinador creado",
            "coordinator"=>$coordinator
        ],201);

    }



    /**
     * Actualizar coordinador
     */
    public function update(Request $request,$id)
    {

        $coordinator = Coordinator::findOrFail($id);

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "email"=>"required|email|unique:coordinators,email,".$id,

            "phone"=>"nullable|string|max:50",

            "status"=>"required|in:ACTIVO,INACTIVO"

        ]);

        $coordinator->update($validated);

        return response()->json([
            "message"=>"Coordinador actualizado",
            "coordinator"=>$coordinator
        ]);

    }


    public function toggleStatus($id)
    {

        $coordinator = Coordinator::findOrFail($id);

        $coordinator->status = $coordinator->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $coordinator->save();

        return response()->json([
            "message"=>"Estado coordinador actualizado",
            "coordinator"=>$coordinator
        ]);

    }

}