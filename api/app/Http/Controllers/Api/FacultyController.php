<?php // #archivo: /backend/html/app/Http/Controllers/Api/FacultyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;

/**
 * Controlador de Facultades
 *
 * Implementa:
 * RF02 - Gestión de Facultades
 */
class FacultyController extends Controller
{

    /**
     * Listar facultades
     */
    public function index()
    {

        $faculties = Faculty::select(
            'id',
            'name',
            'status',
            'created_at'
        )->get();

        return response()->json([
            'faculties' => $faculties
        ]);
    }

    /**
     * Crear facultad
     */
    public function store(Request $request)
    {

$validated = $request->validate([
    'name' => 'required|string|max:255',
    'status' => 'required|in:ACTIVO,INACTIVO'
]);

$faculty = Faculty::create([
    'name' => $validated['name'],
    'status' => $validated['status']
]);

        return response()->json([
            "message" => "Facultad creada correctamente",
            "faculty" => $faculty
        ], 201);
    }

    /**
     * Actualizar facultad
     */
    public function update(Request $request, $id)
    {

        $faculty = Faculty::findOrFail($id);

        $validated = $request->validate([

            'name' => 'required|string|max:255',
            'status' => 'required|string'

        ]);

        $faculty->update($validated);

        return response()->json([
            "message" => "Facultad actualizada",
            "faculty" => $faculty
        ]);
    }

    /**
     * Activar / Inactivar facultad
     */
    public function toggleStatus($id)
    {

        $faculty = Faculty::findOrFail($id);

        $faculty->status =
            $faculty->status === "ACTIVO"
            ? "INACTIVO"
            : "ACTIVO";

        $faculty->save();

        return response()->json([
            "message" => "Estado actualizado",
            "faculty" => $faculty
        ]);
    }

}