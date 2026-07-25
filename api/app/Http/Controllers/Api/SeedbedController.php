<?php
// #archivo: /backend/app/Http/Controllers/Api/SeedbedController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seedbed;
use Illuminate\Http\Request;

class SeedbedController extends Controller
{

    /** * Listar semilleros */
    public function index()
    {
        $seedbeds = Seedbed::with('program')
            ->select(
                'id',
                'name',
                'program_id',
                'status'
            )->get();

        return response()->json([
            "seedbeds"=>$seedbeds
        ]);
    }


    /** * Crear semillero */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "name" => "required|string|max:255",
            "program_id" => "required|exists:programs,id",
            "status" => "required|in:ACTIVO,INACTIVO"

        ]);

        $seedbed = Seedbed::create([
            "name" => $validated["name"],
            "program_id" => $validated["program_id"],
            "status" => $validated["status"]
        ]);

        return response()->json([
            "message" => "Semillero creado",
            "seedbed" => $seedbed
        ],201);

    }

    /**
     * Actualizar semillero
     */
    public function update(Request $request,$id)
    {

        $seedbed = Seedbed::findOrFail($id);

        $validated = $request->validate([

            "name" => "required|string|max:255",
            "program_id" => "required|exists:programs,id",
            "status" => "required|in:ACTIVO,INACTIVO"

        ]);

        $seedbed->update($validated);

        return response()->json([
            "message" => "Semillero actualizado",
            "seedbed" => $seedbed
        ]);

    }


    public function toggleStatus($id)
    {

        $seedbed = Seedbed::findOrFail($id);

        $seedbed->status = $seedbed->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $seedbed->save();

        return response()->json([
            "message" => "Estado semillero actualizado",
            "seedbed" => $seedbed
        ]);

    }

}