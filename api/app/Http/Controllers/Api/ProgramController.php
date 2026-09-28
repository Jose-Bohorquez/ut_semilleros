<?php
// #archivo: /backend/app/Http/Controllers/Api/ProgramController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * RF03 — Gestión de programas: facultad, código (único, RN08), nombre y tipo
 * (Pregrado / Posgrado). No se eliminan: solo se activan o inactivan (RN01).
 */
class ProgramController extends Controller
{
    public const TYPES = ['PREGRADO', 'POSGRADO'];

    private const MESSAGES = [
        'faculty_id.required' => 'La facultad es obligatoria.',
        'faculty_id.exists'   => 'La facultad seleccionada no existe o está inactiva.',
        'code.required'       => 'El código es obligatorio.',
        'code.unique'         => 'Ya existe un programa con ese código.',
        'code.regex'          => 'El código solo admite letras, números, guion y guion bajo.',
        'code.max'            => 'El código admite máximo 20 caracteres.',
        'name.required'       => 'El nombre es obligatorio.',
        'type.required'       => 'El tipo es obligatorio.',
        'type.in'             => 'El tipo solo admite Pregrado o Posgrado.',
        'status.in'           => 'El estado debe ser ACTIVO o INACTIVO.',
    ];

    /**
     * Listar programas
     */
    public function index()
    {

        $programs = Program::with('faculty')
            ->select('id', 'code', 'name', 'type', 'faculty_id', 'status')
            ->orderBy('name')
            ->get();

        return response()->json([
            "programs" => $programs
        ]);

    }


    /**
     * Crear programa (se registra activo si no se indica el estado)
     */
    public function store(Request $request)
    {
        $this->normalize($request);

        /* RF03 «verificación de la facultad activa» */
        $validated = $request->validate(
            $this->rules(null, Rule::exists('faculties', 'id')->where('status', 'ACTIVO')),
            self::MESSAGES
        );

        $program = Program::create($validated + ['status' => 'ACTIVO']);

        return response()->json([
            "message"=>"Programa creado",
            "program"=>$program
        ],201);

    }


    /**
     * Actualizar programa
     */
    public function update(Request $request,$id)
    {

        $program = Program::findOrFail($id);

        $this->normalize($request);

        /* RF02: al editar se puede conservar la facultad actual aunque se haya
           inactivado después; cambiar a otra exige que esté activa. */
        $facultyRule = (int) $request->input("faculty_id") === (int) $program->faculty_id
            ? "exists:faculties,id"
            : Rule::exists('faculties', 'id')->where('status', 'ACTIVO');

        /* Los programas anteriores a RF03 no tienen código ni tipo: al editarlos se exigen */
        $validated = $request->validate($this->rules($program->id, $facultyRule), self::MESSAGES);

        $program->update($validated);

        return response()->json([
            "message"=>"Programa actualizado",
            "program"=>$program
        ]);

    }


    /**
     * Activar / inactivar
     */
    public function toggleStatus($id)
    {

        $program = Program::findOrFail($id);

        $program->status =
            $program->status === "ACTIVO"
            ? "INACTIVO"
            : "ACTIVO";

        $program->save();

        return response()->json([
            "message"=>"Estado actualizado"
        ]);

    }

    /* RN08: «ISIS» e «isis » son el mismo código; el tipo llega en mayúsculas */
    private function normalize(Request $request): void
    {
        foreach (['code', 'type'] as $f) {
            if (is_string($request->input($f))) {
                $request->merge([$f => mb_strtoupper(trim($request->input($f)))]);
            }
        }
    }

    private function rules(?int $ignoreId, $facultyRule): array
    {
        return [
            'faculty_id' => ['required', $facultyRule],
            'code'       => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/u',
                             Rule::unique('programs', 'code')->ignore($ignoreId)],
            'name'       => 'required|string|max:255',
            'type'       => ['required', Rule::in(self::TYPES)],
            'status'     => ($ignoreId ? 'required' : 'sometimes') . '|in:ACTIVO,INACTIVO',
        ];
    }

}
