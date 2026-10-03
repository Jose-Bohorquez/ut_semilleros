<?php #archivo: backend/app/Http/Controllers/Api/ProposalController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Proposal;
use App\Models\Program;
use App\Http\Resources\ProposalResource;
use App\Support\AuditTrail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    private const MESSAGES = [
        'areas.required'      => 'Debes seleccionar al menos un área de conocimiento.',
        'areas.min'            => 'Debes seleccionar al menos un área de conocimiento.',
        'program_id.required' => 'El programa es obligatorio.',
        'description.min'     => 'La descripción debe tener entre 20 y 2000 caracteres.',
        'description.max'     => 'La descripción debe tener entre 20 y 2000 caracteres.',
    ];

    /* CU27: los dos nombres de cada estado de evaluación. Internamente siguen siendo
       APROBADA/RECHAZADA (puente reversible de CU26); la spec los llama Viable/Archivada. */
    private const EVALUATION_STATUSES = [
        'VIABLE'    => 'APROBADA',
        'ARCHIVADA' => 'RECHAZADA',
        'APROBADA'  => 'APROBADA',
        'RECHAZADA' => 'RECHAZADA',
    ];

    private const FILTER_STATUSES = [
        'RECIBIDA'  => 'PENDIENTE',
        'VIABLE'    => 'APROBADA',
        'ARCHIVADA' => 'RECHAZADA',
        'PENDIENTE' => 'PENDIENTE',
        'APROBADA'  => 'APROBADA',
        'RECHAZADA' => 'RECHAZADA',
    ];

    /**
     * CU27 pasos 1-2: listado de propuestas con fecha, estudiante, programa, áreas y estado, y los
     * filtros de la spec (área, programa, estado y fechas; los días son de America/Bogota).
     *  - ADMINISTRATIVO y ADMIN_SISTEMA ven todas.
     *  - A1: el LIDER_SEMILLERO solo ve las de las áreas de sus semilleros, y solo consulta.
     * El listado NO incluye datos de contacto (RNF12): van en el detalle y solo para el
     * Administrativo.
     */
    public function index(Request $request)
    {
        $f = $request->validate([
            'area_id'    => 'nullable|integer',
            'program_id' => 'nullable|integer',
            'status'     => ['nullable', Rule::in(array_keys(self::FILTER_STATUSES))],
            'from'       => 'nullable|date_format:Y-m-d',
            'to'         => 'nullable|date_format:Y-m-d|after_or_equal:from',
        ], [
            'status.in'         => 'El estado debe ser Recibida, Viable o Archivada.',
            'from.date_format'  => 'La fecha inicial no es válida (use AAAA-MM-DD).',
            'to.date_format'    => 'La fecha final no es válida (use AAAA-MM-DD).',
            'to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $tz = 'America/Bogota';

        $proposals = $this->visibleQuery()
            ->with(['user:id,name', 'areas:id,name', 'program:id,name'])
            ->when($f['area_id'] ?? null,    fn ($q, $v) => $q->whereHas('areas', fn ($a) => $a->where('areas.id', $v)))
            ->when($f['program_id'] ?? null, fn ($q, $v) => $q->where('program_id', $v))
            ->when($f['status'] ?? null,     fn ($q, $v) => $q->where('status', self::FILTER_STATUSES[$v]))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v, $tz)->startOfDay()->utc()))
            ->when($f['to'] ?? null,   fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v, $tz)->endOfDay()->utc()))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Proposal $p) => $this->reviewRow($p))
            ->values();

        return response()->json([
            'proposals' => $proposals,
            'message'   => $proposals->isEmpty() ? 'No hay propuestas para los filtros seleccionados' : null,
        ]);
    }

    /**
     * CU27 paso 4: descripción completa y, **solo para el Administrativo** (actor principal), los
     * datos de contacto del estudiante. El Líder (A1) y el Administrador consultan sin contacto.
     * Un Líder no puede abrir una propuesta fuera de las áreas de sus semilleros (403).
     */
    public function show($id)
    {
        $proposal = Proposal::with(['user:id,name,email,phone', 'areas:id,name', 'program:id,name', 'reviewer:id,name'])->findOrFail($id);

        if (!$this->visibleQuery()->whereKey($proposal->id)->exists()) {
            return response()->json(['message' => 'Esta propuesta no corresponde a las áreas de tus semilleros.'], 403);
        }

        return response()->json(['proposal' => $this->reviewRow($proposal, detail: true)]);
    }

    /** Propuestas que el actor puede consultar (A1: el Líder, solo las de las áreas de sus semilleros). */
    private function visibleQuery()
    {
        $user  = auth()->user();
        $query = Proposal::query();

        if ($user->role === 'LIDER_SEMILLERO') {
            $areaIds = DB::table('seedbed_area')
                ->whereIn('seedbed_id', DB::table('seedbed_user')->where('user_id', $user->id)->where('role', 'LIDER')->select('seedbed_id'))
                ->select('area_id');
            $query->whereHas('areas', fn ($a) => $a->whereIn('areas.id', $areaIds));
        }

        return $query;
    }

    /** Forma de una propuesta para quien la evalúa o consulta (sin teléfono salvo el detalle del Administrativo). */
    private function reviewRow(Proposal $p, bool $detail = false): array
    {
        $row = [
            'id'               => $p->id,
            'created_at'       => $p->created_at,
            'created_at_local' => $p->created_at?->copy()->setTimezone('America/Bogota')->format('Y-m-d H:i:s'),
            'student'          => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
            'program'          => $p->program ? ['id' => $p->program->id, 'name' => $p->program->name] : null,
            'areas'            => $p->areas->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->values(),
            'title'            => $p->title,
            'status'           => $p->status,
            'status_label'     => $p->status_label,
            'review_note'      => $p->review_note,
            'reviewed_at'      => $p->reviewed_at,
        ];

        if (!$detail) {
            $row['excerpt'] = mb_substr((string) $p->description, 0, 160);
            return $row;
        }

        $row['description'] = $p->description;
        $row['reviewer']    = $p->reviewer ? ['id' => $p->reviewer->id, 'name' => $p->reviewer->name] : null;
        // RNF12: datos personales solo para el actor principal de CU27.
        if (auth()->user()->role === 'ADMINISTRATIVO' && $p->user) {
            $row['contact'] = ['email' => $p->user->email, 'phone' => $p->phone ?: $p->user->phone];
        }

        return $row;
    }


    public function store(Request $request)
    {

        /* CU25: solo el ESTUDIANTE registra propuestas (la ruta lo exige; antes también
           podían el Líder y el Administrativo a nombre de otro usuario, CU25-H4).
           E3 / RN15: máximo 5 propuestas en 24 horas por estudiante. */
        $this->guardDailyLimit(auth()->id());

        $validated = $request->validate([

            "user_id"=>"nullable|integer",

            "program_id"=>["required", "integer", $this->activeProgramRule()],

            "areas"=>"required|array|min:1",
            "areas.*"=>["integer", "distinct", $this->activeAreaItemRule()],

            "title"=>"required|string|max:255",

            "description"=>"required|string|min:20|max:2000",

            "phone"=>"nullable|string|max:30",

            "status"=>"nullable|in:PENDIENTE,APROBADA,RECHAZADA"

        ], self::MESSAGES);

        /* Seguridad (hallazgo C-02, 2026-09-27): la propuesta es siempre del estudiante
           autenticado y nace PENDIENTE (Recibida) — se ignora el `user_id` y el `status`
           que mande el cliente. La evaluación es exclusiva de update-status (CU27). */
        $validated['user_id'] = auth()->id();
        $validated['status']  = 'PENDIENTE';

        $areas = $validated['areas'];
        unset($validated['areas']);

        /* CU25-H1: propuesta y áreas en una sola transacción — si el attach
           falla no puede quedar una propuesta sin áreas. */
        $proposal = DB::transaction(function () use ($validated, $areas) {
            $p = Proposal::create($validated);
            $p->areas()->attach($areas);
            AuditTrail::pivot($p, 'areas', [], $areas);   // CU29: las áreas no van en el CREATE del modelo
            return $p;
        });
        $proposal->load(['user:id,name', 'areas', 'program:id,name']);

        return response()->json([
            "message"=>"Propuesta creada",
            "proposal"=>$proposal
        ],201);

    }


    public function update(Request $request,$id)
    {

        $proposal = Proposal::findOrFail($id);

        /* Solo el ESTUDIANTE (la ruta lo exige) y solo SU PROPIA propuesta, mientras siga
           PENDIENTE (Recibida). Es una extensión de CU25/CU26 que ya existía; la evaluación
           (estado y observación) NO pasa por aquí: es de CU27 (update-status, Administrativo). */
        if ($proposal->user_id !== auth()->id()) {
            return response()->json(['message' => 'No puedes editar una propuesta que no es tuya'], 403);
        }
        if ($proposal->status !== 'PENDIENTE') {
            return response()->json(['message' => 'Solo puedes editar propuestas en estado Pendiente'], 403);
        }

        $validated = $request->validate([

            "user_id"=>"nullable|integer",

            "program_id"=>["required", "integer", $this->activeProgramRule($proposal)],

            "areas"=>"required|array|min:1",
            "areas.*"=>["integer", "distinct", $this->activeAreaItemRule($proposal)],

            "title"=>"required|string|max:255",

            "description"=>"required|string|min:20|max:2000",

            "phone"=>"nullable|string|max:30",

            "status"=>"nullable|in:PENDIENTE,APROBADA,RECHAZADA"

        ], self::MESSAGES);

        /* No se puede cambiar el estado ni el autor desde este endpoint — se conservan los
           actuales (C-02, 2026-09-27: antes podía pasarle su propuesta a otro usuario). */
        $validated['status']  = $proposal->status;
        $validated['user_id'] = $proposal->user_id;

        $areas = $validated['areas'];
        unset($validated['areas']);

        DB::transaction(function () use ($proposal, $validated, $areas) {
            $beforeAreas = $proposal->areas()->pluck('areas.id')->all();
            $proposal->update($validated);
            $proposal->areas()->sync($areas);
            AuditTrail::pivot($proposal, 'areas', $beforeAreas, $areas);   // CU29
        });
        $proposal->load(['user:id,name', 'areas', 'program:id,name']);

        return response()->json([
            "message"=>"Propuesta actualizada",
            "proposal"=>$proposal
        ]);

    }


    /**
     * CU27 pasos 5-6: «Marcar viable» o «Archivar» con una observación.
     *  - E2: solo el ADMINISTRATIVO (la ruta responde 403 a cualquier otro rol, incluidos el
     *    Líder —que solo consulta, A1— y el Administrador del sistema).
     *  - E1: archivar exige observación (RF11: «Archivar exige observación»); marcar viable no.
     *  - Una propuesta ya evaluada no se vuelve a evaluar (409): la spec no define la reevaluación
     *    y su postcondición de falla es «conserva su estado». Decisión reversible quitando esta guarda.
     *  - Paso 6: guarda estado, observación, evaluador y fecha, y audita (STATUS_CHANGE, CU29).
     * Acepta Viable/Archivada (vocabulario de la spec) o APROBADA/RECHAZADA (valores internos).
     */
    public function updateStatus(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        if ($proposal->status !== 'PENDIENTE') {
            return response()->json(['message' => 'Esta propuesta ya fue evaluada.'], 409);
        }

        $target   = self::EVALUATION_STATUSES[$request->input('status')] ?? null;
        $archives = $target === 'RECHAZADA';

        $validated = $request->validate([
            'status'      => ['required', Rule::in(array_keys(self::EVALUATION_STATUSES))],
            'review_note' => [$archives ? 'required' : 'nullable', 'string', 'min:5', 'max:1000'],
        ], [
            'status.required'      => 'Indica si marcas la propuesta como viable o la archivas.',
            'status.in'            => 'El estado debe ser Viable o Archivada.',
            'review_note.required' => 'La observación es obligatoria para archivar la propuesta.',
            'review_note.min'      => 'La observación debe tener al menos 5 caracteres.',
            'review_note.max'      => 'La observación no puede superar los 1000 caracteres.',
        ]);

        /* forceFill: reviewed_by/reviewed_at no son asignables en masa (los fija el sistema,
           no el cliente); con update() se descartaban en silencio. */
        $proposal->forceFill([
            'status'      => $target,
            'review_note' => $validated['review_note'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        return response()->json([
            'message'  => $archives ? 'Propuesta archivada' : 'Propuesta marcada como viable',
            'proposal' => $this->reviewRow($proposal->load(['user:id,name', 'areas:id,name', 'program:id,name'])),
        ]);
    }

    /**
     * CU26 «Mis propuestas»: solo las del estudiante autenticado (RN13; se filtra
     * por el dueño en el servidor, nunca por un parámetro), de la más reciente a la
     * más antigua. Devuelve fecha, áreas, programa, estado (con su etiqueta
     * Recibida/Viable/Archivada) y la observación del evaluador; la descripción
     * completa viaja en la misma respuesta para el detalle (paso 5).
     */
    public function myProposals()
    {
        $proposals = Proposal::with(['areas:id,name', 'program:id,name'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'proposals' => ProposalResource::collection($proposals)->resolve(),
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'La eliminación no está permitida. Use cambio de estado.',
        ], 405);
    }

    /* E3 / RN15: máximo 5 propuestas en 24 horas por estudiante. */
    private function guardDailyLimit(int $userId): void
    {
        $count = Proposal::where('user_id', $userId)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        if ($count >= 5) {
            $e = ValidationException::withMessages([
                'limit' => ['Has alcanzado el límite diario de propuestas.'],
            ]);
            $e->status = 429;
            throw $e;
        }
    }

    /* Programa activo, o el que ya tenía la propuesta al editar. */
    private function activeProgramRule(?Proposal $current = null): \Closure
    {
        return function ($attribute, $value, $fail) use ($current) {
            $program = Program::find($value);
            if (!$program) {
                $fail('El programa seleccionado no existe.');
                return;
            }
            if ($program->status !== 'ACTIVO' && (!$current || $current->program_id !== (int) $value)) {
                $fail('El programa seleccionado no está activo.');
            }
        };
    }

    /* Área activa, o una que ya tenía asignada la propuesta que se edita
       (mismo criterio que activeAreaItemRule en SeedbedController, CU13). */
    private function activeAreaItemRule(?Proposal $current = null): \Closure
    {
        $currentIds = $current ? $current->areas()->pluck('areas.id')->all() : [];
        return function (string $attribute, $value, \Closure $fail) use ($currentIds) {
            if (in_array((int) $value, $currentIds, true)) return;
            if (!\App\Models\Area::where('id', $value)->where('status', 'ACTIVO')->exists()) {
                $fail('El área seleccionada no existe o está inactiva.');
            }
        };
    }

}
