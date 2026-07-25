<?php # archivo: backend/app/Http/Controllers/Api/AreaController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Area;

class AreaController extends Controller
{

    public function index()
    {

        $areas = Area::get();

        return response()->json([
            "areas"=>$areas
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "code"=>"required|string|max:50|unique:areas,code"

        ]);

        $area = Area::create($validated);

        return response()->json([
            "message"=>"Área creada",
            "area"=>$area
        ],201);

    }


    public function update(Request $request,$id)
    {

        $area = Area::findOrFail($id);

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "code"=>"required|string|max:50|unique:areas,code,".$id

        ]);

        $area->update($validated);

        return response()->json([
            "message"=>"Área actualizada",
            "area"=>$area
        ]);

    }


    public function toggleStatus($id)
    {

        $area = Area::findOrFail($id);

        $area->status = $area->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $area->save();

        return response()->json([
            "message"=>"Estado área actualizado",
            "area"=>$area
        ]);

    }

}