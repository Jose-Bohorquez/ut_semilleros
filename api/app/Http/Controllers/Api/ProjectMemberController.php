<?php # archivo: backend/app/Http/Controllers/Api/ProjectMemberController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Support\AuditTrail;

class ProjectMemberController extends Controller
{

    /**
     * Listar miembros
     */
    public function index($projectId)
    {

        $project = Project::with([
            'users:id,name,email'
        ])->findOrFail($projectId);

        return response()->json([
            "members"=>$project->users
        ]);

    }



    /**
     * Agregar miembro
     */
public function store(Request $request,$projectId)
{

    $validated = $request->validate([

        "user_id"=>"required|exists:users,id",
        "role"=>"required|in:INVESTIGADOR,COINVESTIGADOR,ESTUDIANTE"

    ]);

    $project = Project::findOrFail($projectId);


    /* evitar duplicados */

    if(
        $project->users()
        ->where('user_id',$validated["user_id"])
        ->exists()
    ){
        return response()->json([
            "message"=>"El usuario ya pertenece a este proyecto"
        ],422);
    }


    $beforeMembers = $project->users()->pluck('users.id')->all();

    $project->users()->attach(

        $validated["user_id"],

        ["role"=>$validated["role"]]

    );

    /* CU29: la tabla pivote no tiene modelo; el cambio se registra sobre el proyecto. */
    AuditTrail::pivot($project, 'members', $beforeMembers, array_merge($beforeMembers, [(int) $validated["user_id"]]));


    return response()->json([
        "message"=>"Miembro agregado"
    ],201);

}



    /**
     * Eliminar miembro
     */
    public function destroy($projectId,$userId)
    {

        $project = Project::findOrFail($projectId);

        $beforeMembers = $project->users()->pluck('users.id')->all();

        $project->users()->detach($userId);

        AuditTrail::pivot($project, 'members', $beforeMembers, array_diff($beforeMembers, [(int) $userId]));   // CU29

        return response()->json([
            "message"=>"Miembro eliminado"
        ]);

    }

}