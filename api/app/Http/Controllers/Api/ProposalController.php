<?php #archivo: backend/app/Http/Controllers/Api/ProposalController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Proposal;

class ProposalController extends Controller
{

    public function index()
    {

        $proposals = Proposal::with([
            'user:id,name'
        ])->get();

        return response()->json([
            "proposals"=>$proposals
        ]);

    }


    public function store(Request $request)
    {

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "title"=>"required|string|max:255",

            "description"=>"required|string",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ]);

        /* Seguridad (hallazgo C-02, 2026-09-27): el ESTUDIANTE crea
           propuestas solo a su nombre y siempre PENDIENTE — se ignora lo que
           mande el cliente (antes podía crearla ya APROBADA o como autor otro
           usuario). La aprobación es exclusiva de update-status (L, ADM). */
        if (auth()->user()->role === 'ESTUDIANTE') {
            $validated['user_id'] = auth()->id();
            $validated['status']  = 'PENDIENTE';
        }

        $proposal = Proposal::create($validated);

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

            "title"=>"required|string|max:255",

            "description"=>"required|string",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ]);

        /* El estudiante no puede cambiar el estado ni el autor desde este
           endpoint — se conservan los actuales, aunque el frontend ya manda
           PENDIENTE y su propio id. (C-02, 2026-09-27: antes podía pasarle su
           propuesta a otro usuario con un PUT de user_id.) */
        if (auth()->user()->role === 'ESTUDIANTE') {
            $validated['status']  = $proposal->status;
            $validated['user_id'] = $proposal->user_id;
        }

        $proposal->update($validated);

        return response()->json([
            "message"=>"Propuesta actualizada",
            "proposal"=>$proposal
        ]);

    }


    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:PENDIENTE,APROBADA,RECHAZADA',
        ]);

        if (auth()->user()->role === 'ESTUDIANTE') {
            return response()->json(['message' => 'No autorizado para cambiar estado'], 403);
        }

        $proposal = Proposal::findOrFail($id);
        $proposal->update([
            'status'      => $validated['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message'  => 'Estado actualizado a ' . $validated['status'],
            'proposal' => $proposal,
        ]);
    }

    public function myProposals()
    {
        $user = auth()->user();
        $proposals = Proposal::with(['user:id,name'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json(['proposals' => $proposals]);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'La eliminación no está permitida. Use cambio de estado.',
        ], 405);
    }

}