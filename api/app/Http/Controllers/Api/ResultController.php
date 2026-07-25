<?php #archiv: backend/app/Http/Controllers/Api/ResultController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Result;

class ResultController extends Controller
{

    public function index()
    {

        $results = Result::with([
            'seedbed:id,name'
        ])->get();

        return response()->json([
            "results"=>$results
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string"

        ]);

        $result = Result::create($validated);

        return response()->json([
            "message"=>"Resultado creado",
            "result"=>$result
        ],201);

    }


    public function update(Request $request,$id)
    {

        $result = Result::findOrFail($id);

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string"

        ]);

        $result->update($validated);

        return response()->json([
            "message"=>"Resultado actualizado",
            "result"=>$result
        ]);

    }


    public function toggleStatus($id)
    {

        $result = Result::findOrFail($id);

        $result->status = $result->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $result->save();

        return response()->json([
            "message"=>"Estado resultado actualizado",
            "result"=>$result
        ]);

    }

}