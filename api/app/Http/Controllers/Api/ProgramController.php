<?php
// #archivo: /backend/app/Http/Controllers/Api/ProgramController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    private const FACULTY_MESSAGES = [
        'faculty_id.exists' => 'La facultad seleccionada no existe o está inactiva.',
    ];

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

/* RF02: una facultad inactiva no se puede elegir para un programa */
$validated = $request->validate([
    "name" => "required|string|max:255",
    "faculty_id" => ["required", Rule::exists('faculties', 'id')->where('status', 'ACTIVO')],
    "status" => "required|in:ACTIVO,INACTIVO"
], self::FACULTY_MESSAGES);

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

        /* RF02: al editar se puede conservar la facultad actual aunque se haya
           inactivado después; cambiar a otra exige que esté activa. */
        $facultyRule = (int) $request->input("faculty_id") === (int) $program->faculty_id
            ? "exists:faculties,id"
            : Rule::exists('faculties', 'id')->where('status', 'ACTIVO');

        $validated = $request->validate([

            "name"=>"required|string|max:255",
            "faculty_id"=>["required", $facultyRule],
            "status"=>"required|in:ACTIVO,INACTIVO"

        ], self::FACULTY_MESSAGES);

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


