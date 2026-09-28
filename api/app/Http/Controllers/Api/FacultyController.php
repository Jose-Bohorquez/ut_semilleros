<?php // #archivo: /backend/html/app/Http/Controllers/Api/FacultyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador de Facultades
 *
 * Implementa:
 * RF02 - Gestión de Facultades (código + nombre, código único por RN08,
 *        sin eliminación: solo activar / inactivar)
 */
class FacultyController extends Controller
{

    /**
     * Listar facultades
     */
    public function index()
    {

        $faculties = Faculty::select(
            'id',
            'code',
            'name',
            'status',
            'created_at'
        )->orderBy('name')->get();

        return response()->json([
            'faculties' => $faculties
        ]);
    }

    /**
     * Crear facultad (se registra activa, RF02 «Procesamiento»)
     */
    public function store(Request $request)
    {
        $this->normalizeCode($request);

        $validated = $request->validate($this->rules(), $this->messages());

        $faculty = Faculty::create([
            'code'   => $validated['code'],
            'name'   => $validated['name'],
            'status' => $validated['status'] ?? 'ACTIVO',
        ]);

        return response()->json([
            "message" => "Facultad creada correctamente",
            "faculty" => $faculty
        ], 201);
    }

    /**
     * Actualizar facultad
     */
    public function update(Request $request, $id)
    {

        $faculty = Faculty::findOrFail($id);

        $this->normalizeCode($request);

        /* Las facultades anteriores a RF02 no tienen código: al editarlas se
           exige completarlo, igual que al crear. */
        $validated = $request->validate($this->rules($faculty->id), $this->messages());

        $faculty->update($validated);

        return response()->json([
            "message" => "Facultad actualizada",
            "faculty" => $faculty
        ]);
    }

    /**
     * Activar / Inactivar facultad
     */
    public function toggleStatus($id)
    {

        $faculty = Faculty::findOrFail($id);

        $faculty->status =
            $faculty->status === "ACTIVO"
            ? "INACTIVO"
            : "ACTIVO";

        $faculty->save();

        return response()->json([
            "message" => "Estado actualizado",
            "faculty" => $faculty
        ]);
    }

    /* RN08: «FCE» y «fce » son el mismo código */
    private function normalizeCode(Request $request): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => mb_strtoupper(trim($request->input('code')))]);
        }
    }

    private function rules(?int $ignoreId = null): array
    {
        return [
            'code'   => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/u',
                         Rule::unique('faculties', 'code')->ignore($ignoreId)],
            'name'   => 'required|string|max:255',
            'status' => ($ignoreId ? 'required' : 'sometimes') . '|in:ACTIVO,INACTIVO',
        ];
    }

    private function messages(): array
    {
        return [
            'code.required' => 'El código es obligatorio.',
            'code.unique'   => 'Ya existe una facultad con ese código.',
            'code.regex'    => 'El código solo admite letras, números, guion y guion bajo.',
            'code.max'      => 'El código admite máximo 20 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'status.in'     => 'El estado debe ser ACTIVO o INACTIVO.',
        ];
    }

}
