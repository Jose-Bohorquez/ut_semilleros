<?php // archivo: backend/app/Http/Controllers/Api/ProjectController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{

    /**
     * Listar proyectos
     */
public function index()
{

    $projects = Project::with([
        'seedbed:id,name'
    ])
    ->withCount('users')
    ->get();

    return response()->json([
        "projects"=>$projects
    ]);

}



    /**
     * Crear proyecto
     */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",
            "title"=>"required|string|max:255",
            "description"=>"nullable|string",
            "status"=>"required|in:ACTIVO,FINALIZADO,SUSPENDIDO"

        ]);

        $project = Project::create($validated);

        return response()->json([
            "message"=>"Proyecto creado",
            "project"=>$project
        ],201);

    }



    /**
     * Actualizar proyecto
     */
    public function update(Request $request,$id)
    {

        $project = Project::findOrFail($id);

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",
            "title"=>"required|string|max:255",
            "description"=>"nullable|string",
            "status"=>"required|in:ACTIVO,FINALIZADO,SUSPENDIDO"

        ]);

        $project->update($validated);

        return response()->json([
            "message"=>"Proyecto actualizado",
            "project"=>$project
        ]);

    }

}