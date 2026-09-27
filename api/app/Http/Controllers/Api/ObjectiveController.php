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
        ])->orderBy('seedbed_id')->orderBy('order')->get();

        return response()->json([
            "objectives"=>$objectives
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string",

            "order"=>"nullable|integer|min:0"

        ]);

        if (!isset($validated['order'])) {
            $validated['order'] = (int) Objective::where('seedbed_id', $validated['seedbed_id'])->max('order') + 1;
        }

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

            "content"=>"required|string",

            "order"=>"nullable|integer|min:0"

        ]);

        $objective->update($validated);

        return response()->json([
            "message"=>"Objetivo actualizado",
            "objective"=>$objective
        ]);

    }


    /**
     * Eliminar objetivo — antes no existía este endpoint en absoluto
     * (Jose, 2026-07-28: necesario para poder quitar objetivos desde el
     * mismo formulario de semilleros, no solo agregarlos).
     */
    public function destroy($id)
    {
        $objective = Objective::findOrFail($id);
        $objective->delete();

        return response()->json([
            "message"=>"Objetivo eliminado"
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
