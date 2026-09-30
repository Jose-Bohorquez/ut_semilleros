<?php
// #archivo: /backend/app/Http/Controllers/Api/SeedbedController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seedbed;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        'leader_id.exists'   => 'El líder seleccionado no existe, está inactivo o no tiene rol Líder de semillero.',
        'authorization_reference.required' => 'La referencia de la aprobación del área administrativa es obligatoria (RN03).',
    ];

    /* CU16 paso 2: el listado muestra facultad, líder e integrantes además
       de lo que ya se cargaba. */
    /* `users` solo con id y nombre (más el pivote con el rol): el frontend únicamente
       necesita saber quién es el LIDER. Sin esto salían el correo, el teléfono
       descifrado, la referencia de autorización y la foto de cada usuario (CU16-H6). */
    private const LIST_RELATIONS = ['programs.faculty', 'areas', 'group', 'cat', 'coordinator', 'users:users.id,users.name'];

    /* T1 / CU17-H1 / CU18: el ESTUDIANTE (PWA) solo ve semilleros ACTIVOS y
       solo los datos públicos. Nada de referencia de autorización, motivo de
       inactivación, solicitudes pendientes ni datos personales (RN11, RNF03). */
    private const STUDENT_HIDDEN = [
        'authorization_reference', 'inactivation_reason', 'pending_requests_count', 'users',
    ];

    private function isStudent(): bool
    {
        return auth()->user()?->role === 'ESTUDIANTE';
    }

    /** Listar semilleros */
    public function index()
    {
        if ($this->isStudent()) {
            /* Coordinador: solo nombre y correo (lo que muestra la PWA).
               `users` se carga solo para calcular leader_name y se oculta. */
            $seedbeds = Seedbed::with([
                    'programs.faculty', 'areas', 'group:id,name', 'cat:id,name',
                    'coordinator:id,name,email', 'users:users.id,users.name',
                ])
                ->withCount(['members as active_members_count' => fn ($q) => $q->where('status', 'ACTIVO')])
                ->where('status', 'ACTIVO')
                ->get()
                ->each(fn ($s) => $s->makeHidden(self::STUDENT_HIDDEN));

            return response()->json(["seedbeds" => $seedbeds]);
        }

        $seedbeds = Seedbed::with(self::LIST_RELATIONS)
            ->withCount([
                'requests as pending_requests_count' => fn ($q) => $q->where('status', 'PENDIENTE'),
                'members as active_members_count'    => fn ($q) => $q->where('status', 'ACTIVO'),
            ])
            ->get();

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
        if ($this->isStudent()) {
            $seedbed = Seedbed::with([
                    'programs.faculty', 'areas', 'group:id,name', 'cat:id,name',
                    'coordinator:id,name,email', 'users:users.id,users.name',
                ])
                ->withCount(['members as active_members_count' => fn ($q) => $q->where('status', 'ACTIVO')])
                ->findOrFail($id);

            /* CU18-E1: un semillero inactivo ya no está disponible. */
            if ($seedbed->status !== 'ACTIVO') {
                return response()->json(['message' => 'Este semillero ya no está disponible'], 404);
            }

            return response()->json(['seedbed' => $seedbed->makeHidden(self::STUDENT_HIDDEN)]);
        }

        $seedbed = Seedbed::with(self::LIST_RELATIONS)
            ->withCount([
                'requests as pending_requests_count' => fn ($q) => $q->where('status', 'PENDIENTE'),
                'members as active_members_count'    => fn ($q) => $q->where('status', 'ACTIVO'),
            ])
            ->findOrFail($id);

        /* CU16-H2: integrantes reales (seedbed_members ACTIVOS) para la vista
           «Ver»: solo nombre, programa y nivel — sin correo ni teléfono. */
        $seedbed->setAttribute('active_members', $seedbed->members()
            ->with('program:id,name')
            ->where('status', 'ACTIVO')
            ->orderBy('name')
            ->get()
            ->map(fn ($m) => [
                'id'           => $m->id,
                'name'         => $m->name,
                'program_name' => $m->program?->name,
                'level'        => $m->level,
            ])->values()->all());

        return response()->json([
            'seedbed' => $seedbed,
        ]);
    }


    /** Crear semillero */
    public function store(Request $request)
    {

        $this->guardOnlyAdminSetsLeader($request);
        $validated = $request->validate($this->rules(null), self::MESSAGES);
        $validated['status'] = $validated['status'] ?? 'ACTIVO';

        $programs = $validated['programs'];
        $areas = $validated['areas'];
        $leaderId = $validated['leader_id'] ?? null;
        unset($validated['programs'], $validated['areas'], $validated['leader_id']);

        /* CU13-H7: semillero + pivotes + líder se escriben juntos o no se
           escribe nada (E4 "no se guarda"). */
        $seedbed = DB::transaction(function () use ($validated, $programs, $areas, $leaderId) {
            $seedbed = Seedbed::create($validated);
            $seedbed->programs()->attach($programs);
            $seedbed->areas()->attach($areas);

            /* CU13 paso 9: "asigna al líder como responsable". Si crea un
               Líder, es él; si crea el Admin, puede designar uno (CU14-A2). */
            if (auth()->user()->role === 'LIDER_SEMILLERO') {
                $seedbed->users()->attach(auth()->id(), ['role' => 'LIDER']);
            } elseif ($leaderId) {
                $this->assignLeader($seedbed, (int) $leaderId);
            }

            return $seedbed;
        });

        return response()->json([
            "message" => "Semillero creado",
            "seedbed" => $seedbed->load(['programs.faculty', 'areas', 'users'])
        ],201);

    }

    /**
     * Actualizar semillero
     */
    public function update(Request $request,$id)
    {

        $seedbed = Seedbed::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($seedbed);
        $this->guardOnlyAdminSetsLeader($request);
        $this->guardConcurrentEdit($request, $seedbed);

        /* CU15-H1: el estado solo cambia con Activar/Inactivar (toggle-status),
           que exige motivo (E2) y rechaza las solicitudes pendientes. Enviar
           el mismo estado que ya tiene no es un cambio y se tolera. */
        if ($request->has('status') && $request->input('status') !== $seedbed->status) {
            throw ValidationException::withMessages([
                'status' => ['Para cambiar el estado usa Activar o Inactivar.'],
            ]);
        }

        $validated = $request->validate($this->rules($id, $seedbed), self::MESSAGES);

        $programs = $validated['programs'];
        $areas = $validated['areas'];
        $leaderId = $validated['leader_id'] ?? null;
        unset($validated['programs'], $validated['areas'], $validated['leader_id'], $validated['status']);

        DB::transaction(function () use ($seedbed, $validated, $programs, $areas, $leaderId) {
            $seedbed->update($validated);
            $seedbed->programs()->sync($programs);
            $seedbed->areas()->sync($areas);

            /* CU14-A2: solo el Admin (re)asigna al líder responsable. */
            if ($leaderId) {
                $this->assignLeader($seedbed, (int) $leaderId);
            }
        });

        return response()->json([
            "message" => "Semillero actualizado",
            "seedbed" => $seedbed->load(['programs.faculty', 'areas', 'users'])
        ]);

    }

    /** CU14-A2: solo ADMIN_SISTEMA puede designar/reasignar al líder. */
    private function guardOnlyAdminSetsLeader(Request $request): void
    {
        if ($request->filled('leader_id') && auth()->user()->role !== 'ADMIN_SISTEMA') {
            $e = ValidationException::withMessages([
                'leader_id' => ['Solo el Administrador puede asignar o reasignar el líder del semillero.'],
            ]);
            $e->status = 403;
            throw $e;
        }
    }

    /**
     * Deja a $leaderId como único LIDER del semillero (RN06). Conserva otras
     * filas del pivote que no sean de rol LIDER.
     */
    private function assignLeader(Seedbed $seedbed, int $leaderId): void
    {
        $seedbed->users()->wherePivot('role', 'LIDER')->detach();
        $seedbed->users()->detach($leaderId);
        $seedbed->users()->attach($leaderId, ['role' => 'LIDER']);
    }


    /**
     * CU15: activar/inactivar semillero.
     * - Al inactivar: motivo obligatorio (E2), rechaza automáticamente las
     *   solicitudes pendientes con la razón "Semillero inactivo".
     * - Al activar (A1): sin motivo, no toca las solicitudes.
     */
    public function toggleStatus(Request $request, $id)
    {

        $seedbed = Seedbed::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($seedbed);

        $isInactivating = $seedbed->status === 'ACTIVO';
        $rejectedCount = 0;

        if ($isInactivating) {
            $validated = $request->validate([
                'reason' => 'required|string|min:5',
            ], [
                'reason.required' => 'Debes indicar el motivo para inactivar el semillero.',
                'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
            ]);

            $seedbed->inactivation_reason = $validated['reason'];
        } else {
            $seedbed->inactivation_reason = null;
        }

        /* CU13-H7: cambio de estado + rechazo de pendientes son atómicos. */
        DB::transaction(function () use ($seedbed, $isInactivating, &$rejectedCount) {
            if ($isInactivating) {
                /* CU15-H1b / CU24-H1 (RN07): se recorre modelo por modelo (no
                   ->update() masivo) para que el observer registre en
                   `audits` cada cambio de estado PENDIENTE → RECHAZADA. */
                $seedbed->requests()
                    ->where('status', 'PENDIENTE')
                    ->get()
                    ->each(function ($request) use (&$rejectedCount) {
                        $request->forceFill([
                            'status' => 'RECHAZADA',
                            'reason' => 'Semillero inactivo',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ])->save();
                        $rejectedCount++;
                    });
            }

            $seedbed->status = $isInactivating ? 'INACTIVO' : 'ACTIVO';
            $seedbed->save();
        });

        return response()->json([
            "message" => "Estado semillero actualizado",
            "seedbed" => $seedbed,
            "rejected_requests_count" => $rejectedCount,
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
            /* Al crear es obligatorio (ACTIVO, o INACTIVO con «Guardar borrador»);
               al editar ya se rechazó antes cualquier cambio (CU15-H1). */
            "status" => [$current ? "sometimes" : "required", "in:ACTIVO,INACTIVO"],
            /* CU14-A2: solo el Admin lo envía (guardOnlyAdminSetsLeader). */
            "leader_id" => ["nullable", Rule::exists('users', 'id')
                ->where('role', 'LIDER_SEMILLERO')->where('status', 'ACTIVO')],
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
