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

        $proposal = Proposal::create($validated);

        return response()->json([
            "message"=>"Propuesta creada",
            "proposal"=>$proposal
        ],201);

    }


    public function update(Request $request,$id)
    {

        $proposal = Proposal::findOrFail($id);

        $validated = $request->validate([

            "user_id"=>"required|exists:users,id",

            "title"=>"required|string|max:255",

            "description"=>"required|string",

            "status"=>"required|in:PENDIENTE,APROBADA,RECHAZADA"

        ]);

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