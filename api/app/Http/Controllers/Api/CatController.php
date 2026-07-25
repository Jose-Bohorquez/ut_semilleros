<?php # archivo: backend/app/Http/Controllers/Api/CatController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cat;

class CatController extends Controller
{

    public function index()
    {

        $cats = Cat::get();

        return response()->json([
            "cats"=>$cats
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "name"=>"required|string|max:255",
            "code"=>"required|string|max:50|unique:cats,code",

            "address"=>"nullable|string",
            "city"=>"nullable|string",

            "phone1"=>"nullable|string",
            "phone2"=>"nullable|string",
            "phone3"=>"nullable|string"

        ]);

        $cat = Cat::create($validated);

        return response()->json([
            "message"=>"CAT creado",
            "cat"=>$cat
        ],201);

    }


    public function update(Request $request,$id)
    {

        $cat = Cat::findOrFail($id);

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "code"=>"required|string|max:50|unique:cats,code,".$id,

            "address"=>"nullable|string",
            "city"=>"nullable|string",

            "phone1"=>"nullable|string",
            "phone2"=>"nullable|string",
            "phone3"=>"nullable|string"

        ]);

        $cat->update($validated);

        return response()->json([
            "message"=>"CAT actualizado",
            "cat"=>$cat
        ]);

    }


    public function toggleStatus($id)
    {

        $cat = Cat::findOrFail($id);

        $cat->status = $cat->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $cat->save();

        return response()->json([
            "message"=>"Estado CAT actualizado",
            "cat"=>$cat
        ]);

    }

}