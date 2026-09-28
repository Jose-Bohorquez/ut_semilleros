<?php
// #archivo: /backend/app/Http/Controllers/Api/SeedbedController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seedbed;
use App\Models\Program;
use Illuminate\Http\Request;

class SeedbedController extends Controller
{

    /** * Listar semilleros */
    public function index()
    {
        $seedbeds = Seedbed::with('program')
            ->select(
                'id',
                'name',
                'description',
                'program_id',
                'status'
            )->get();

        return response()->json([
            "seedbeds"=>$seedbeds
        ]);
    }


    /** * Crear semillero */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "program_id" => ["required", "exists:programs,id", $this->activeProgramRule()],
            "status" => "required|in:ACTIVO,INACTIVO"

        ]);

        $seedbed = Seedbed::create([
            "name" => $validated["name"],
            "description" => $validated["description"] ?? null,
            "program_id" => $validated["program_id"],
            "status" => $validated["status"]
        ]);

        return response()->json([
            "message" => "Semillero creado",
            "seedbed" => $seedbed
        ],201);

    }

    /**
     * Actualizar semillero
     */
    public function update(Request $request,$id)
    {

        $seedbed = Seedbed::findOrFail($id);

        $validated = $request->validate([

            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "program_id" => array_filter([
                "required", "exists:programs,id",
                /* Conservar el programa actual siempre se permite */
                (int) $request->input("program_id") !== (int) $seedbed->program_id
                    ? $this->activeProgramRule() : null,
            ]),
            "status" => "required|in:ACTIVO,INACTIVO"

        ]);

        $seedbed->update($validated);

        return response()->json([
            "message" => "Semillero actualizado",
            "seedbed" => $seedbed
        ]);

    }


    public function toggleStatus($id)
    {

        $seedbed = Seedbed::findOrFail($id);

        $seedbed->status = $seedbed->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $seedbed->save();

        return response()->json([
            "message" => "Estado semillero actualizado",
            "seedbed" => $seedbed
        ]);

    }


    /* RF02 / RF03: no se asigna un semillero a un programa inactivo ni a uno
       cuya facultad esté inactiva. */
    private function activeProgramRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            $program = Program::with('faculty')->find($value);
            if (!$program) return;
            if ($program->status !== 'ACTIVO' || $program->faculty?->status !== 'ACTIVO') {
                $fail('El programa seleccionado o su facultad están inactivos.');
            }
        };
    }
}
