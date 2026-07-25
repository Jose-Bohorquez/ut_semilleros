<?php
// #archivo: /backend/app/Http/Controllers/Api/ProgramController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{

    /**
     * Listar programas
     */
    public function index()
    {

        $programs = Program::with('faculty')
            ->select('id','name','faculty_id','status')
            ->get();

        return response()->json([
            "programs" => $programs
        ]);

    }


    /**
     * Crear programa
     */
    public function store(Request $request)
    {

$validated = $request->validate([
    "name" => "required|string|max:255",
    "faculty_id" => "required|exists:faculties,id",
    "status" => "required|in:ACTIVO,INACTIVO"
]);

$program = Program::create([
    "name"=>$validated["name"],
    "faculty_id"=>$validated["faculty_id"],
    "status"=>$validated["status"]
]);

        return response()->json([
            "message"=>"Programa creado",
            "program"=>$program
        ],201);

    }


    /**
     * Actualizar programa
     */
    public function update(Request $request,$id)
    {

        $program = Program::findOrFail($id);

        $validated = $request->validate([

            "name"=>"required|string|max:255",
            "faculty_id"=>"required|exists:faculties,id",
            "status"=>"required"

        ]);

        $program->update($validated);

        return response()->json([
            "message"=>"Programa actualizado",
            "program"=>$program
        ]);

    }


    /**
     * Activar / inactivar
     */
    public function toggleStatus($id)
    {

        $program = Program::findOrFail($id);

        $program->status =
            $program->status === "ACTIVO"
            ? "INACTIVO"
            : "ACTIVO";

        $program->save();

        return response()->json([
            "message"=>"Estado actualizado"
        ]);

    }

}


