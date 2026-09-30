<?php
// #archivo: /backend/app/Http/Controllers/Api/SeedbedController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seedbed;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SeedbedController extends Controller
{
    private const MESSAGES = [
        'code.required'      => 'El código es obligatorio.',
        'code.unique'        => 'Ya existe un semillero con ese código.',
        'programs.required'  => 'Debes seleccionar al menos un programa.',
        'programs.min'       => 'Debes seleccionar al menos un programa.',
        'areas.required'     => 'Debes seleccionar al menos un área.',
        'areas.min'          => 'Debes seleccionar al menos un área.',
        'group_id.exists'    => 'El grupo seleccionado no existe o está inactivo.',
        'cat_id.exists'      => 'El CAT seleccionado no existe o está inactivo.',
        'coordinator_id.exists' => 'El coordinador seleccionado no existe o está inactivo.',
        'objetivo_general.required' => 'El objetivo general es obligatorio.',
        'objetivo_general.min'      => 'El objetivo general debe tener mínimo 10 caracteres.',
        'authorization_reference.required' => 'La referencia de la aprobación del área administrativa es obligatoria (RN03).',
    ];

    private const RELATIONS = ['programs', 'areas', 'group', 'cat', 'coordinator'];

    /** Listar semilleros */
    public function index()
    {
        $seedbeds = Seedbed::with(self::RELATIONS)->get();

        return response()->json([
            "seedbeds"=>$seedbeds
        ]);
    }

    /**
     * Detalle de un semillero (CU13, fixing el bug histórico C-13: la ruta
     * GET /seedbeds/{id} existía pero el método no, daba 500).
     */
    public function show($id)
    {
        $seedbed = Seedbed::with(array_merge(self::RELATIONS, ['users']))->findOrFail($id);

        return response()->json([
            'seedbed' => $seedbed,
        ]);
    }


    /** Crear semillero */
    public function store(Request $request)
    {

        $validated = $request->validate($this->rules(null), self::MESSAGES);
        $validated['status'] = $validated['status'] ?? 'ACTIVO';

        $programs = $validated['programs'];
        $areas = $validated['areas'];
        unset($validated['programs'], $validated['areas']);

        $seedbed = Seedbed::create($validated);
        $seedbed->programs()->attach($programs);
        $seedbed->areas()->attach($areas);

        /* CU13 paso 9: "asigna al líder como responsable". Solo cuando quien
           crea es Líder — si crea Admin/Administrativo no hay un líder
           concreto que asignar automáticamente. */
        if (auth()->user()->role === 'LIDER_SEMILLERO') {
            $seedbed->users()->attach(auth()->id(), ['role' => 'LIDER']);
        }

        return response()->json([
            "message" => "Semillero creado",
            "seedbed" => $seedbed->load(['programs', 'areas'])
        ],201);

    }

    /**
     * Actualizar semillero
     */
    public function update(Request $request,$id)
    {

        $seedbed = Seedbed::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($seedbed);
        $this->guardConcurrentEdit($request, $seedbed);

        $validated = $request->validate($this->rules($id, $seedbed), self::MESSAGES);

        $programs = $validated['programs'];
        $areas = $validated['areas'];
        unset($validated['programs'], $validated['areas']);

        $seedbed->update($validated);
        $seedbed->programs()->sync($programs);
        $seedbed->areas()->sync($areas);

        return response()->json([
            "message" => "Semillero actualizado",
            "seedbed" => $seedbed->load(['programs', 'areas'])
        ]);

    }


    public function toggleStatus($id)
    {

        $seedbed = Seedbed::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($seedbed);

        $seedbed->status = $seedbed->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $seedbed->save();

        return response()->json([
            "message" => "Estado semillero actualizado",
            "seedbed" => $seedbed
        ]);

    }

    /**
     * RN06: "un líder solo modifica los semilleros de los que es
     * responsable. El Administrador puede modificar cualquiera." No aplica
     * a ADMINISTRATIVO (la spec no lo restringe ahí, y ya tenía acceso de
     * escritura por decisión previa del proyecto — 2026-07-28).
     */
    private function guardLeaderOwnsSeedbed(Seedbed $seedbed): void
    {
        $user = auth()->user();
        if ($user->role !== 'LIDER_SEMILLERO') {
            return;
        }

        $isResponsible = $seedbed->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'LIDER')
            ->exists();

        if (!$isResponsible) {
            $e = ValidationException::withMessages([
                'seedbed' => ['Solo puedes modificar los semilleros de los que eres responsable (RN06).'],
            ]);
            $e->status = 403;
            throw $e;
        }
    }

    /**
     * CU14 E3: si otro usuario modificó el semillero después de que el
     * actor abrió el formulario, se rechaza con 409 (no se pisa el cambio
     * ajeno en silencio). El frontend envía la marca de tiempo que tenía
     * cargada; si no la envía (clientes antiguos), no se valida.
     */
    private function guardConcurrentEdit(Request $request, Seedbed $seedbed): void
    {
        $expected = $request->input('expected_updated_at');
        if (!$expected) return;

        if (!$seedbed->updated_at->equalTo($expected)) {
            $e = ValidationException::withMessages([
                'seedbed' => ['El semillero fue modificado por otro usuario; recargue para ver los cambios.'],
            ]);
            $e->status = 409;
            throw $e;
        }
    }

    private function rules(?int $ignoreId, ?Seedbed $current = null): array
    {
        return [
            "code" => ["required", "string", "max:50", Rule::unique('seedbeds', 'code')->ignore($ignoreId)],
            "name" => "required|string|max:255",
            "description" => "nullable|string",
            "programs" => ["required", "array", "min:1"],
            "programs.*" => ["integer", $this->activeProgramItemRule($current)],
            "areas" => ["required", "array", "min:1"],
            "areas.*" => ["integer", $this->activeAreaItemRule($current)],
            "group_id" => ["nullable", $this->activeGroupRule($current)],
            "cat_id" => ["nullable", Rule::exists('cats', 'id')->where('status', 'ACTIVO')],
            "coordinator_id" => ["nullable", Rule::exists('coordinators', 'id')->where('status', 'ACTIVO')],
            "mision" => "nullable|string",
            "vision" => "nullable|string",
            "justificacion" => "nullable|string",
            "objetivo_general" => "required|string|min:10",
            /* RN03 / RNF06: aprobación escrita del área administrativa. */
            "authorization_reference" => "required|string|max:255",
            "status" => "required|in:ACTIVO,INACTIVO",
        ];
    }

    /* RF02 / RF03: no se asigna un semillero a un programa inactivo ni a uno
       cuya facultad esté inactiva. Conserva los programas ya asignados aunque
       se hayan inactivado después (mismo criterio que antes, ahora por ítem
       de la lista en vez de un solo valor). */
    private function activeProgramItemRule(?Seedbed $current): \Closure
    {
        $currentIds = $current ? $current->programs()->pluck('programs.id')->all() : [];
        return function (string $attribute, $value, \Closure $fail) use ($currentIds) {
            if (in_array((int) $value, $currentIds, true)) return;
            $program = Program::with('faculty')->find($value);
            if (!$program) {
                $fail('El programa seleccionado no existe.');
                return;
            }
            if ($program->status !== 'ACTIVO' || $program->faculty?->status !== 'ACTIVO') {
                $fail('El programa seleccionado o su facultad están inactivos.');
            }
        };
    }

    private function activeAreaItemRule(?Seedbed $current): \Closure
    {
        $currentIds = $current ? $current->areas()->pluck('areas.id')->all() : [];
        return function (string $attribute, $value, \Closure $fail) use ($currentIds) {
            if (in_array((int) $value, $currentIds, true)) return;
            if (!\App\Models\Area::where('id', $value)->where('status', 'ACTIVO')->exists()) {
                $fail('El área seleccionada no existe o está inactiva.');
            }
        };
    }

    private function activeGroupRule(?Seedbed $current): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($current) {
            if ($value === null) return;
            if ($current && (int) $value === (int) $current->group_id) return;
            if (!\App\Models\Group::where('id', $value)->where('status', 'ACTIVO')->exists()) {
                $fail('El grupo seleccionado no existe o está inactivo.');
            }
        };
    }
}
