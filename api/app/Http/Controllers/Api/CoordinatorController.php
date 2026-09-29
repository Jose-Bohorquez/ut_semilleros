<?php # archivo: backend/app/Http/Controllers/Api/CoordinatorController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Coordinator;

/**
 * RF07 — Gestión de coordinadores (CU12, actor: Administrador del sistema;
 * Líder y Administrativo solo consultan, A5). Nombre, documento (único,
 * RN08), correo y teléfono (cifrado en reposo, RNF12).
 */
class CoordinatorController extends Controller
{
    private const MESSAGES = [
        'name.required'     => 'El nombre es obligatorio.',
        'document.unique'   => 'Ya existe un coordinador con ese documento.',
        'email.required'    => 'El correo es obligatorio.',
        'email.unique'      => 'Ya existe un coordinador con ese correo.',
        'status.in'         => 'El estado debe ser ACTIVO o INACTIVO.',
    ];

    public function index()
    {

        $coordinators = Coordinator::orderBy('name')->get();

        return response()->json([
            "coordinators"=>$coordinators
        ]);

    }

    /**
     * Detalle de un coordinador (CU12-A1): fechas y estado. Sin relaciones
     * en el esquema (no hay FK de otras tablas hacia coordinators).
     */
    public function show($id)
    {
        $coordinator = Coordinator::findOrFail($id);

        return response()->json([
            'coordinator' => $coordinator,
        ]);
    }

    public function store(Request $request)
    {

        $validated = $request->validate($this->rules(null), self::MESSAGES);
        $validated['status'] = $validated['status'] ?? 'ACTIVO';

        $coordinator = Coordinator::create($validated);

        return response()->json([
            "message"=>"Coordinador creado",
            "coordinator"=>$coordinator
        ],201);

    }

    public function update(Request $request,$id)
    {

        $coordinator = Coordinator::findOrFail($id);

        $validated = $request->validate($this->rules($id), self::MESSAGES);

        $coordinator->update($validated);

        return response()->json([
            "message"=>"Coordinador actualizado",
            "coordinator"=>$coordinator
        ]);

    }


    public function toggleStatus($id)
    {

        $coordinator = Coordinator::findOrFail($id);

        $coordinator->status = $coordinator->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $coordinator->save();

        return response()->json([
            "message"=>"Estado coordinador actualizado",
            "coordinator"=>$coordinator
        ]);

    }

    private function rules(?int $ignoreId): array
    {
        return [
            'name'     => 'required|string|max:255',
            'document' => ['nullable', 'string', 'max:50', Rule::unique('coordinators', 'document')->ignore($ignoreId)],
            'email'    => ['required', 'email', Rule::unique('coordinators', 'email')->ignore($ignoreId)],
            'phone'    => 'nullable|string|max:50',
            'status'   => ($ignoreId ? 'required' : 'sometimes') . '|in:ACTIVO,INACTIVO',
        ];
    }

}
