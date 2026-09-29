<?php # archivo: backend/app/Http/Controllers/Api/AreaController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Area;

/**
 * RF05 — Gestión de áreas de conocimiento: código (único, RN08) y nombre.
 * Sin eliminación (RN01): solo se activan o inactivan.
 */
class AreaController extends Controller
{
    private const MESSAGES = [
        'code.required' => 'El código es obligatorio.',
        'code.unique'    => 'Ya existe un área con ese código.',
        'name.required'  => 'El nombre es obligatorio.',
        'status.in'      => 'El estado debe ser ACTIVO o INACTIVO.',
    ];

    public function index()
    {

        $areas = Area::orderBy('name')->get();

        return response()->json([
            "areas"=>$areas
        ]);

    }


    /**
     * Detalle de un área (CU10-A1): fechas y semilleros/propuestas asociados.
     */
    public function show($id)
    {
        $area = Area::withCount(['seedbeds', 'proposals'])->findOrFail($id);

        return response()->json([
            'area' => $area,
        ]);
    }

    public function store(Request $request)
    {

        $this->normalizeCode($request);

        $validated = $request->validate($this->rules(null), self::MESSAGES);
        $validated['status'] = $validated['status'] ?? 'ACTIVO';

        $area = Area::create($validated);

        return response()->json([
            "message"=>"Área creada",
            "area"=>$area
        ],201);

    }


    public function update(Request $request,$id)
    {

        $area = Area::findOrFail($id);

        $this->normalizeCode($request);

        $validated = $request->validate($this->rules($id), self::MESSAGES);

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

    /* RN08: «tic» y «TIC » son el mismo código */
    private function normalizeCode(Request $request): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => mb_strtoupper(trim($request->input('code')))]);
        }
    }

    private function rules(?int $ignoreId): array
    {
        return [
            'code'   => ['required', 'string', 'max:50', Rule::unique('areas', 'code')->ignore($ignoreId)],
            'name'   => 'required|string|max:255',
            'status' => ($ignoreId ? 'required' : 'sometimes') . '|in:ACTIVO,INACTIVO',
        ];
    }

}
