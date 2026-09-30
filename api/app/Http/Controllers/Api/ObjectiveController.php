<?php
// archivo: backend/app/Http/Controllers/Api/ObjectiveController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Objective;
use App\Models\Seedbed;

class ObjectiveController extends Controller
{

    private const MESSAGES = [
        'content.required' => 'El contenido es obligatorio.',
        'content.min'       => 'El contenido debe tener al menos 10 caracteres.',
        'content.max'       => 'El contenido no puede superar los 2000 caracteres.',
    ];

    public function index()
    {

        $objectives = Objective::with([
            'seedbed:id,name'
        ])->orderBy('seedbed_id')->orderBy('order')->get();

        return response()->json([
            "objectives"=>$objectives
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string|min:10|max:2000",

            "order"=>"nullable|integer|min:0"

        ], self::MESSAGES);

        $this->guardLeaderOwnsSeedbed((int) $validated['seedbed_id']);

        if (!isset($validated['order'])) {
            $validated['order'] = (int) Objective::where('seedbed_id', $validated['seedbed_id'])->max('order') + 1;
        }

        $objective = Objective::create($validated);

        return response()->json([
            "message"=>"Objetivo creado",
            "objective"=>$objective
        ],201);

    }


    public function update(Request $request,$id)
    {

        $objective = Objective::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($objective->seedbed_id);

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string|min:10|max:2000",

            "order"=>"nullable|integer|min:0"

        ], self::MESSAGES);

        $this->guardLeaderOwnsSeedbed((int) $validated['seedbed_id']);

        $objective->update($validated);

        return response()->json([
            "message"=>"Objetivo actualizado",
            "objective"=>$objective
        ]);

    }


    /**
     * Eliminar objetivo — antes no existía este endpoint en absoluto
     * (Jose, 2026-07-28: necesario para poder quitar objetivos desde el
     * mismo formulario de semilleros, no solo agregarlos). Decisión
     * confirmada 2026-09-30: se mantiene el borrado físico (desviación
     * intencional de RN01 para este caso puntual) en vez de forzar
     * inactivar — el repetidor del formulario lo necesita para quitar
     * filas ya guardadas, no solo las nuevas sin guardar.
     */
    public function destroy($id)
    {
        $objective = Objective::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($objective->seedbed_id);

        $objective->delete();

        return response()->json([
            "message"=>"Objetivo eliminado"
        ]);
    }


    public function toggleStatus($id)
    {

        $objective = Objective::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($objective->seedbed_id);

        $objective->status = $objective->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $objective->save();

        return response()->json([
            "message"=>"Estado objetivo actualizado",
            "objective"=>$objective
        ]);

    }

    /**
     * RN06 (CU19 E2): el líder solo gestiona objetivos de los semilleros de
     * los que es responsable. El Administrador no se restringe. Hallazgo
     * real (2026-09-30): esta validación no existía en absoluto — cualquier
     * líder autenticado podía editar/borrar/cambiar estado de los objetivos
     * de CUALQUIER semillero, no solo el suyo.
     */
    private function guardLeaderOwnsSeedbed(int $seedbedId): void
    {
        $user = auth()->user();
        if ($user->role !== 'LIDER_SEMILLERO') {
            return;
        }

        $seedbed = Seedbed::find($seedbedId);
        $isResponsible = $seedbed && $seedbed->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'LIDER')
            ->exists();

        if (!$isResponsible) {
            $e = ValidationException::withMessages([
                'seedbed' => ['Solo puedes gestionar los objetivos de los semilleros de los que eres responsable (RN06).'],
            ]);
            $e->status = 403;
            throw $e;
        }
    }

}
