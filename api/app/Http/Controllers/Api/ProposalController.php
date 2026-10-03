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

    public function index()
    {

        $proposals = Proposal::with([
            'user:id,name',
            'areas',
            'program:id,name'
        ])->get();

        return response()->json([
            "proposals"=>$proposals
        ]);

    }


    public function store(Request $request)
    {

        /* E3 / RN15: máximo 5 propuestas por estudiante en 24 horas. Se
           evalúa sobre el autor real (el estudiante mismo, o el que venga en
           el payload si lo crea un Líder/Administrativo a nombre de otro). */
        $authorId = auth()->user()->role === 'ESTUDIANTE' ? auth()->id() : (int) $request->input('user_id');
        $this->guardDailyLimit($authorId);

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "program_id"=>["required", "integer", $this->activeProgramRule()],

            "areas"=>"required|array|min:1",
            "areas.*"=>["integer", "distinct", $this->activeAreaItemRule()],

            "title"=>"required|string|max:255",

            "description"=>"required|string|min:20|max:2000",

            "phone"=>"nullable|string|max:30",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ], self::MESSAGES);

        /* Seguridad (hallazgo C-02, 2026-09-27): el ESTUDIANTE crea
           propuestas solo a su nombre y siempre PENDIENTE — se ignora lo que
           mande el cliente (antes podía crearla ya APROBADA o como autor otro
           usuario). La aprobación es exclusiva de update-status (L, ADM). */
        if (auth()->user()->role === 'ESTUDIANTE') {
            $validated['user_id'] = auth()->id();
            $validated['status']  = 'PENDIENTE';
        }

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

        /* El estudiante solo puede editar SU PROPIA propuesta, y solo
           mientras siga PENDIENTE — igual regla que ya aplicaba el frontend,
           pero antes no existía backend porque esta ruta estaba cerrada
           por completo para ESTUDIANTE (Jose, 2026-07-28). */
        if (auth()->user()->role === 'ESTUDIANTE') {
            if ($proposal->user_id !== auth()->id()) {
                return response()->json(['message' => 'No puedes editar una propuesta que no es tuya'], 403);
            }
            if ($proposal->status !== 'PENDIENTE') {
                return response()->json(['message' => 'Solo puedes editar propuestas en estado Pendiente'], 403);
            }
        }

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "program_id"=>["required", "integer", $this->activeProgramRule($proposal)],

            "areas"=>"required|array|min:1",
            "areas.*"=>["integer", "distinct", $this->activeAreaItemRule($proposal)],

            "title"=>"required|string|max:255",

            "description"=>"required|string|min:20|max:2000",

            "phone"=>"nullable|string|max:30",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ], self::MESSAGES);

        /* El estudiante no puede cambiar el estado ni el autor desde este
           endpoint — se conservan los actuales, aunque el frontend ya manda
           PENDIENTE y su propio id. (C-02, 2026-09-27: antes podía pasarle su
           propuesta a otro usuario con un PUT de user_id.) */
        if (auth()->user()->role === 'ESTUDIANTE') {
            $validated['status']  = $proposal->status;
            $validated['user_id'] = $proposal->user_id;
        }

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


    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status'      => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
            /* CU26: observación del evaluador, la que ve el estudiante. Opcional aquí;
               que archivar la exija (CU27-E1) se implementa con CU27. */
            'review_note' => 'nullable|string|min:5|max:1000',
        ], [
            'review_note.min' => 'La observación debe tener al menos 5 caracteres.',
            'review_note.max' => 'La observación no puede superar los 1000 caracteres.',
        ]);

        if (auth()->user()->role === 'ESTUDIANTE') {
            return response()->json(['message' => 'No autorizado para cambiar estado'], 403);
        }

        $proposal = Proposal::findOrFail($id);
        /* forceFill: reviewed_by/reviewed_at no son asignables en masa (los fija
           el sistema, no el cliente); con update() se descartaban en silencio. */
        $proposal->forceFill([
            'status'      => $validated['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ] + (array_key_exists('review_note', $validated) ? ['review_note' => $validated['review_note']] : []))->save();

        return response()->json([
            'message'  => 'Estado actualizado a ' . $validated['status'],
            'proposal' => $proposal,
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
