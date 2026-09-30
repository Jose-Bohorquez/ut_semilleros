<?php #archiv: backend/app/Http/Controllers/Api/ResultController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Result;
use App\Models\Seedbed;

class ResultController extends Controller
{

    private const MESSAGES = [
        'content.required' => 'El contenido es obligatorio.',
        'content.min'       => 'El contenido debe tener al menos 10 caracteres.',
        'content.max'       => 'El contenido no puede superar los 2000 caracteres.',
    ];

    public function index()
    {

        $results = Result::with([
            'seedbed:id,name'
        ])->get();

        return response()->json([
            "results"=>$results
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string|min:10|max:2000",

            "result_date"=>"nullable|date"

        ], self::MESSAGES);

        $this->guardLeaderOwnsSeedbed((int) $validated['seedbed_id']);

        $result = Result::create($validated);

        return response()->json([
            "message"=>"Resultado creado",
            "result"=>$result
        ],201);

    }


    public function update(Request $request,$id)
    {

        $result = Result::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($result->seedbed_id);

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "content"=>"required|string|min:10|max:2000",

            "result_date"=>"nullable|date"

        ], self::MESSAGES);

        $this->guardLeaderOwnsSeedbed((int) $validated['seedbed_id']);

        $result->update($validated);

        return response()->json([
            "message"=>"Resultado actualizado",
            "result"=>$result
        ]);

    }


    public function toggleStatus($id)
    {

        $result = Result::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($result->seedbed_id);

        $result->status = $result->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $result->save();

        return response()->json([
            "message"=>"Estado resultado actualizado",
            "result"=>$result
        ]);

    }

    /**
     * RN06 (CU20 E2): el líder solo gestiona resultados de los semilleros de
     * los que es responsable. El Administrador no se restringe. Mismo patrón
     * que SeedbedController (CU13) y ObjectiveController (CU19).
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
                'seedbed' => ['Solo puedes gestionar los resultados de los semilleros de los que eres responsable (RN06).'],
            ]);
            $e->status = 403;
            throw $e;
        }
    }

}
