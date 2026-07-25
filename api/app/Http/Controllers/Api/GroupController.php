<?php #archivo: backend/app/Http/Controllers/Api/GroupController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;

class GroupController extends Controller
{

    public function index()
    {

        $groups = Group::get();

        return response()->json([
            "groups"=>$groups
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "code"=>"required|string|max:50|unique:groups,code"

        ]);

        $group = Group::create($validated);

        return response()->json([
            "message"=>"Grupo creado",
            "group"=>$group
        ],201);

    }


    public function update(Request $request,$id)
    {

        $group = Group::findOrFail($id);

        $validated = $request->validate([

            "name"=>"required|string|max:255",

            "code"=>"required|string|max:50|unique:groups,code,".$id

        ]);

        $group->update($validated);

        return response()->json([
            "message"=>"Grupo actualizado",
            "group"=>$group
        ]);

    }


    public function toggleStatus($id)
    {

        $group = Group::findOrFail($id);

        $group->status = $group->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $group->save();

        return response()->json([
            "message"=>"Estado grupo actualizado",
            "group"=>$group
        ]);

    }

}