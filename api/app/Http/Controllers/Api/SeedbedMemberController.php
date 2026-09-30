<?php # archivo: backend/app/Http/Controllers/Api/SeedbedMemberController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Seedbed;
use App\Models\SeedbedMember;
use App\Models\Program;

class SeedbedMemberController extends Controller
{
    private const MESSAGES = [
        'name.required'          => 'El nombre es obligatorio.',
        'student_code.required'  => 'El código estudiantil es obligatorio.',
        'program_id.required'    => 'El programa es obligatorio.',
        'program_id.exists'      => 'El programa seleccionado no existe.',
        'level.required'         => 'El nivel es obligatorio.',
        'level.in'                => 'El nivel debe ser PR (pregrado) o PG (posgrado).',
        'email.required'         => 'El correo es obligatorio.',
        'email.email'             => 'El correo no tiene un formato válido.',
    ];

    public function index($seedbedId)
    {
        $this->guardLeaderOwnsSeedbed((int) $seedbedId);

        $seedbed = Seedbed::findOrFail($seedbedId);

        $members = SeedbedMember::with(['program:id,name'])
            ->where('seedbed_id', $seedbed->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            "members" => $members
        ]);
    }

    public function store(Request $request, $seedbedId)
    {
        $this->guardLeaderOwnsSeedbed((int) $seedbedId);

        $seedbed = Seedbed::findOrFail($seedbedId);

        $validated = $request->validate([
            "name"         => "required|string|max:255",
            "student_code" => "required|string|max:50",
            "program_id"   => ["required", "integer", $this->activeProgramRule()],
            "level"        => "required|in:PR,PG",
            "email"        => "required|email|max:255",
            "address"      => "nullable|string|max:500",
            "phone"        => "nullable|string|max:30",
            "user_id"      => "nullable|integer|exists:users,id",
        ], self::MESSAGES);

        $this->guardCodeNotActiveInSeedbed($seedbed->id, $validated['student_code']);

        $validated['seedbed_id'] = $seedbed->id;
        $validated['status'] = 'ACTIVO';

        $member = SeedbedMember::create($validated);

        return response()->json([
            "message" => "Integrante registrado",
            "member"  => $member
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $member = SeedbedMember::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($member->seedbed_id);

        $validated = $request->validate([
            "name"         => "required|string|max:255",
            "student_code" => "required|string|max:50",
            "program_id"   => ["required", "integer", $this->activeProgramRule($member)],
            "level"        => "required|in:PR,PG",
            "email"        => "required|email|max:255",
            "address"      => "nullable|string|max:500",
            "phone"        => "nullable|string|max:30",
        ], self::MESSAGES);

        $this->guardCodeNotActiveInSeedbed($member->seedbed_id, $validated['student_code'], $member->id);

        $member->update($validated);

        return response()->json([
            "message" => "Integrante actualizado",
            "member"  => $member
        ]);
    }

    /* A3: inactivar/activar con motivo (mismo patrón que CU15 semilleros). */
    public function toggleStatus(Request $request, $id)
    {
        $member = SeedbedMember::findOrFail($id);
        $this->guardLeaderOwnsSeedbed($member->seedbed_id);

        $willInactivate = $member->status === 'ACTIVO';

        if ($willInactivate) {
            $request->validate([
                "reason" => "required|string|min:5|max:500",
            ], [
                "reason.required" => "Indica el motivo de inactivación (retiro, grado, etc.).",
                "reason.min"      => "El motivo debe tener al menos 5 caracteres.",
            ]);
            $member->inactivation_reason = $request->input('reason');
        } else {
            /* CU21-H1 / E1: al reactivar rige la misma unicidad que al crear:
               no puede haber dos ACTIVOS con el mismo código en el semillero. */
            $this->guardCodeNotActiveInSeedbed($member->seedbed_id, $member->student_code, $member->id);
            $member->inactivation_reason = null;
        }

        $member->status = $willInactivate ? 'INACTIVO' : 'ACTIVO';
        $member->save();

        return response()->json([
            "message" => "Estado del integrante actualizado",
            "member"  => $member
        ]);
    }

    /* E1: código ya registrado como integrante ACTIVO del mismo semillero. */
    private function guardCodeNotActiveInSeedbed(int $seedbedId, string $code, ?int $ignoreId = null): void
    {
        $exists = SeedbedMember::where('seedbed_id', $seedbedId)
            ->where('student_code', $code)
            ->where('status', 'ACTIVO')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            $e = ValidationException::withMessages([
                'student_code' => ['El estudiante ya es integrante de este semillero.'],
            ]);
            $e->status = 422;
            throw $e;
        }
    }

    /* Programa activo, o el que ya tenía el integrante al editar (mismo
       criterio que activeProgramItemRule en SeedbedController, CU13). */
    private function activeProgramRule(?SeedbedMember $current = null): \Closure
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

    /* RN06 (CU21 E3): el líder solo gestiona integrantes de los semilleros
       de los que es responsable. El Administrador no se restringe. */
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
                'seedbed' => ['Solo puedes gestionar los integrantes de los semilleros de los que eres responsable (RN06).'],
            ]);
            $e->status = 403;
            throw $e;
        }
    }
}
