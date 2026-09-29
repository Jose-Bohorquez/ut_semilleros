<?php #archivo: backend/app/Http/Controllers/Api/GroupController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Group;

/**
 * RF06 — Gestión de grupos de investigación (CU11, actor: Líder de
 * semillero): código (único, RN08) y nombre. Sin eliminación (RN01): solo
 * se activan o inactivan.
 */
class GroupController extends Controller
{
    private const MESSAGES = [
        'code.required' => 'El código es obligatorio.',
        'code.unique'   => 'Ya existe un grupo con ese código.',
        'name.required' => 'El nombre es obligatorio.',
        'status.in'     => 'El estado debe ser ACTIVO o INACTIVO.',
    ];

    public function index()
    {

        $groups = Group::orderBy('name')->get();

        return response()->json([
            "groups"=>$groups
        ]);

    }

    /**
     * Detalle de un grupo (CU11-A1): fechas y estado. Sin relaciones en el
     * esquema (no hay FK de otras tablas hacia groups).
     */
    public function show($id)
    {
        $group = Group::findOrFail($id);

        return response()->json([
            'group' => $group,
        ]);
    }


    public function store(Request $request)
    {

        $this->normalizeCode($request);

        $validated = $request->validate($this->rules(null), self::MESSAGES);
        $validated['status'] = $validated['status'] ?? 'ACTIVO';

        $group = Group::create($validated);

        return response()->json([
            "message"=>"Grupo creado",
            "group"=>$group
        ],201);

    }


    public function update(Request $request,$id)
    {

        $group = Group::findOrFail($id);

        $this->normalizeCode($request);

        $validated = $request->validate($this->rules($id), self::MESSAGES);

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

    /* RN08: «gie» y «GIE » son el mismo código */
    private function normalizeCode(Request $request): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => mb_strtoupper(trim($request->input('code')))]);
        }
    }

    private function rules(?int $ignoreId): array
    {
        return [
            'code'   => ['required', 'string', 'max:50', Rule::unique('groups', 'code')->ignore($ignoreId)],
            'name'   => 'required|string|max:255',
            'status' => ($ignoreId ? 'required' : 'sometimes') . '|in:ACTIVO,INACTIVO',
        ];
    }

}
