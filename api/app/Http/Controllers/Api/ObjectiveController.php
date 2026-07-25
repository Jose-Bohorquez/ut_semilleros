<?php
// archivo: backend/app/Http/Controllers/Api/ObjectiveController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Objective;

class ObjectiveController extends Controller
{

    public function index()
    {

        $objectives = Objective::with([
            'seedbed:id,name'
        ])->get();

        return response()->json([
            "objectives"=>$objectives
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string"

        ]);

        $objective = Objective::create($validated);

        return response()->json([
            "message"=>"Objetivo creado",
            "objective"=>$objective
        ],201);

    }


    public function update(Request $request,$id)
    {

        $objective = Objective::findOrFail($id);

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string"

        ]);

        $objective->update($validated);

        return response()->json([
            "message"=>"Objetivo actualizado",
            "objective"=>$objective
        ]);

    }


    public function toggleStatus($id)
    {

        $objective = Objective::findOrFail($id);

        $objective->status = $objective->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $objective->save();

        return response()->json([
            "message"=>"Estado objetivo actualizado",
            "objective"=>$objective
        ]);

    }

}