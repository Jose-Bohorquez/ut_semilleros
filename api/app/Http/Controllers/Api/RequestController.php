<?php #archiv: backend/app/Http/Controllers/Api/RequestController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MembershipRequest as RequestModel;
use App\Models\Seedbed;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{

    private const MESSAGES = [
        'program_id.required' => 'Debes seleccionar un programa.',
        'phone.required'      => 'El teléfono es obligatorio.',
        'phone.regex'         => 'El teléfono debe tener entre 7 y 15 dígitos.',
        'message.required'    => 'El mensaje es obligatorio.',
        'message.min'         => 'El mensaje debe tener al menos 10 caracteres.',
        'message.max'         => 'El mensaje no puede superar los 1000 caracteres.',
    ];

    /**
     * CU24 (paso 2): solicitudes de los semilleros del actor.
     *  - LIDER_SEMILLERO: solo las de los semilleros que lidera (RN06).
     *  - ADMIN_SISTEMA / ADMINISTRATIVO: las de todos los semilleros (A3).
     * Filtros opcionales (A2): seedbed_id, status, from, to (fecha de creación).
     * `seedbeds` devuelve los semilleros con solicitudes dentro del alcance del
     * actor (sin aplicar los filtros) para poblar el selector del frontend.
     */
    public function index(Request $request)
    {

        $filters = $request->validate([
            'seedbed_id' => 'nullable|integer',
            'status'     => 'nullable|in:PENDIENTE,APROBADA,RECHAZADA,CANCELADA',
            'from'       => 'nullable|date',
            'to'         => 'nullable|date|after_or_equal:from',
        ], [
            'status.in'           => 'El estado debe ser PENDIENTE, APROBADA, RECHAZADA o CANCELADA.',
            'from.date'           => 'La fecha inicial no es válida.',
            'to.date'             => 'La fecha final no es válida.',
            'to.after_or_equal'   => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $scoped = $this->scopedQuery();

        $seedbeds = Seedbed::whereIn('id', (clone $scoped)->select('seedbed_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $requests = $scoped
            ->when($filters['seedbed_id'] ?? null, fn ($q, $v) => $q->where('seedbed_id', $v))
            ->when($filters['status'] ?? null,     fn ($q, $v) => $q->where('status', $v))
            ->when($filters['from'] ?? null,       fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null,         fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->with([
                'user:id,name,email',
                'seedbed:id,name',
                'program:id,name'
            ])
            ->latest()
            ->get();

        return response()->json([
            "requests" => $requests,
            "seedbeds" => $seedbeds,
        ]);

    }


    /**
     * CU24 (paso 4): detalle de una solicitud — datos del estudiante y
     * mensaje. E3: un líder no puede ver solicitudes de semilleros ajenos.
     */
    public function show($id)
    {

        $req = RequestModel::with([
            'user:id,name,email',
            'seedbed:id,name',
            'program:id,name',
        ])->findOrFail($id);

        $this->guardLeaderOwnsSeedbed($req->seedbed_id);

        return response()->json([
            "request" => $req,
        ]);

    }


    /**
     * Consulta base con el alcance del actor (RN06): el líder solo ve las
     * solicitudes de los semilleros donde es LIDER en el pivot.
     */
    private function scopedQuery()
    {
        $user  = auth()->user();
        $query = RequestModel::query();

        if ($user->role === 'LIDER_SEMILLERO') {
            $query->whereIn('seedbed_id', $this->ledSeedbedIds($user->id));
        }

        return $query;
    }

    private function ledSeedbedIds(int $userId)
    {
        return DB::table('seedbed_user')
            ->where('user_id', $userId)
            ->where('role', 'LIDER')
            ->select('seedbed_id');
    }

    /**
     * RN06 / CU24-E3: 403 si el actor es líder y no lidera el semillero.
     * ADMIN_SISTEMA no tiene restricción.
     */
    private function guardLeaderOwnsSeedbed(int $seedbedId): void
    {
        $user = auth()->user();

        if ($user->role !== 'LIDER_SEMILLERO') {
            return;
        }

        $leads = DB::table('seedbed_user')
            ->where('user_id', $user->id)
            ->where('seedbed_id', $seedbedId)
            ->where('role', 'LIDER')
            ->exists();

        if (!$leads) {
            abort(403, 'Solo puedes gestionar las solicitudes de los semilleros de los que eres responsable (RN06).');
        }
    }


    /**
     * CU22: el estudiante se postula a un semillero (paso 2 del flujo
     * básico) — programa (de los activos del semillero), teléfono
     * (7-15 dígitos) y mensaje (10-1000 caracteres).
     */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "seedbed_id"=>"required|exists:seedbeds,id",

            "program_id"=>"required|integer",

            "phone"=>["required", "string", "regex:/^[0-9]{7,15}$/"],

            "message"=>"required|string|min:10|max:1000",

            /* Ya no es obligatorio (CU22-H2): si llega se valida el formato por
               compatibilidad, pero el valor se ignora — ver más abajo. */
            "status"=>"nullable|in:PENDIENTE,APROBADA,RECHAZADA"

        ], self::MESSAGES);

        /* CU22 es del ESTUDIANTE (la ruta lo exige con role:ESTUDIANTE; antes también podían L/ADM, 2026-10-03).
           Seguridad (C-02): solo se postula a sí mismo — cualquier user_id que mande el cliente se ignora.
           CU22-H2: nace PENDIENTE (paso 4); aprobar/rechazar es solo por PUT /requests/{id}/update-status (CU24). */
        $validated['user_id'] = auth()->id();
        $validated['status'] = 'PENDIENTE';

        /* E4 (CU22): el semillero debe estar activo. */
        $seedbed = Seedbed::findOrFail($validated['seedbed_id']);
        if ($seedbed->status !== 'ACTIVO') {
            return response()->json([
                'message' => 'Este semillero ya no recibe solicitudes.',
            ], 409);
        }

        /* El programa debe ser uno de los activos del semillero (CU22 paso 2). */
        $perteneceAlSemillero = $seedbed->programs()->where('programs.id', $validated['program_id'])->exists();
        if (!$perteneceAlSemillero) {
            return response()->json([
                'message' => 'El programa seleccionado no pertenece a este semillero.',
                'errors' => ['program_id' => ['El programa seleccionado no pertenece a este semillero.']],
            ], 422);
        }

        /* Regla de negocio (Jose, 2026-07-28; precisada el 2026-10-04): para postularse el estudiante no debe tener
           ningún semillero asociado ni activo — ni una solicitud pendiente, ni ser integrante activo, ni una
           aprobada aún sin registrar. Si el líder lo inactiva (sale del semillero) queda libre. RECHAZADA no cuenta.
           (La spec de CU22/RN05 solo lo exige por semillero; esta regla más estricta es decisión del proyecto.) */
        $estado = \App\Support\SeedbedAssociation::evaluate(auth()->user(), (int) $validated['seedbed_id']);
        if (! $estado['can_apply']) {
            return response()->json(['message' => $estado['message'], 'state' => $estado['state']], 409);
        }

        $requestModel = RequestModel::create($validated);

        return response()->json([
            "message"=>"Solicitud creada",
            "request"=>$requestModel->load(['seedbed:id,name', 'program:id,name'])
        ],201);

    }


    /**
     * CU24 (pasos 5-6, A1): el líder aprueba o rechaza una solicitud.
     *  - `reason` es la respuesta al estudiante (la muestra CU23): opcional al
     *    aprobar, obligatoria al rechazar (E1).
     *  - E2: si ya no está PENDIENTE (resuelta a mano o por la inactivación del
     *    semillero, CU15) se responde 409 y no se toca.
     *  - E3: un líder no resuelve solicitudes de semilleros ajenos (403).
     *  - Auditoría (CU29): la registra AuditObserver al guardar.
     * El antiguo PUT /requests/{id} (editar user/semillero/estado sin reglas)
     * se retiró: dejaba saltarse E1, E2 y E3.
     */
    public function updateStatus(Request $request, $id)
    {
        $req = RequestModel::findOrFail($id);

        $this->guardLeaderOwnsSeedbed($req->seedbed_id);

        if ($req->status !== 'PENDIENTE') {
            return response()->json([
                'message' => 'Esta solicitud ya fue resuelta.',
            ], 409);
        }

        $validated = $request->validate([
            'status' => 'required|in:APROBADA,RECHAZADA',
            'reason' => [
                $request->input('status') === 'RECHAZADA' ? 'required' : 'nullable',
                'string', 'min:5', 'max:1000',
            ],
        ], [
            'status.required' => 'Indica si aprueba o rechaza la solicitud.',
            'status.in'       => 'El estado debe ser APROBADA o RECHAZADA.',
            'reason.required' => 'Escribe el motivo del rechazo.',
            'reason.min'      => 'La respuesta debe tener al menos 5 caracteres.',
            'reason.max'      => 'La respuesta no puede superar los 1000 caracteres.',
        ]);

        /* forceFill: reviewed_by/reviewed_at no son asignables en masa, pero los
           fija el sistema, no el cliente (antes update() los descartaba en silencio). */
        $req->forceFill([
            'status'      => $validated['status'],
            'reason'      => $validated['reason'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ])->save();

        // Aviso al estudiante (campana + push). No puede fallar la operación.
        $aprobada = $validated['status'] === 'APROBADA';
        $semillero = $req->seedbed()->value('name') ?? 'el semillero';
        $nota = trim((string) ($validated['reason'] ?? ''));
        \App\Support\UserNotifier::notify(
            (int) $req->user_id,
            $aprobada ? 'Tu solicitud fue aprobada' : 'Tu solicitud no fue aprobada',
            ($aprobada ? "Ya eres parte de «{$semillero}»." : "Tu solicitud a «{$semillero}» no fue aprobada.")
                . ($nota !== '' ? ' ' . \Illuminate\Support\Str::limit($nota, 160) : ''),
            auth()->id(),
            '/requests'
        );

        return response()->json([
            'message' => $validated['status'] === 'APROBADA'
                ? 'Solicitud aprobada'
                : 'Solicitud rechazada',
            'request' => $req->load(['user:id,name,email', 'seedbed:id,name', 'program:id,name']),
        ]);
    }

    /**
     * PUT /requests/{id}/cancel — el estudiante cancela SU solicitud mientras esté pendiente (por si se equivocó).
     * No se borra: pasa a CANCELADA, queda en «Mis solicitudes» y en la auditoría (STATUS_CHANGE), y el estudiante
     * queda libre para postularse de nuevo (a este u otro semillero). Una solicitud ajena responde 404 (no se revela
     * que existe) y una ya resuelta 409.
     */
    public function cancel($id)
    {
        $req = RequestModel::where('id', $id)->where('user_id', auth()->id())->firstOrFail();

        if ($req->status !== 'PENDIENTE') {
            return response()->json([
                'message' => $req->status === 'CANCELADA'
                    ? 'Esta solicitud ya estaba cancelada.'
                    : 'Solo puedes cancelar solicitudes pendientes. Esta ya fue resuelta por el líder.',
            ], 409);
        }

        $req->forceFill(['status' => 'CANCELADA', 'reason' => 'Cancelada por el estudiante.'])->save();

        return response()->json([
            'message' => 'Solicitud cancelada. Ya puedes postularte de nuevo.',
            'request' => $req->load(['seedbed:id,name', 'program:id,name']),
        ]);
    }

    /**
     * GET /requests/eligibility?seedbed_id= — ¿puede el estudiante postularse (a este semillero)? La PWA lo usa en el
     * detalle del semillero para explicar el motivo en lugar de dejarlo llenar un formulario que será rechazado.
     */
    public function eligibility(Request $request)
    {
        $request->validate(['seedbed_id' => 'nullable|integer']);

        return response()->json(\App\Support\SeedbedAssociation::evaluate(auth()->user(), $request->integer('seedbed_id') ?: null));
    }

    public function myRequests()
    {
        $user = auth()->user();
        $requests = RequestModel::with(['user:id,name', 'seedbed:id,name', 'program:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'La eliminación no está permitida. Use cambio de estado.',
        ], 405);
    }

}