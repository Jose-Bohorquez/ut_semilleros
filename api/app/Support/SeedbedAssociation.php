<?php

namespace App\Support;

use App\Models\MembershipRequest;
use App\Models\SeedbedMember;
use App\Models\User;

/**
 * CU22 — ¿puede este estudiante postularse a un semillero?
 *
 * Regla (Jose, 2026-10-04): para postularse a un semillero el estudiante no debe tener NINGUNO asociado ni activo.
 * Tiene uno asociado cuando:
 *   · tiene una solicitud PENDIENTE (a cualquier semillero), o
 *   · es integrante ACTIVO de un semillero activo (lo registró el líder), o
 *   · tiene una solicitud APROBADA a un semillero activo y su líder aún no lo registró como integrante.
 * Si el líder lo inactiva como integrante (sale del semillero), o el semillero se inactiva, queda libre y puede
 * postularse de nuevo. (Antes una solicitud aprobada bloqueaba «de por vida», aunque ya no estuviera en el semillero.)
 *
 * El integrante se reconoce por `user_id` o, si el líder no lo vinculó, por el correo del usuario.
 */
class SeedbedAssociation
{
    /**
     * @return array{can_apply:bool,state:string,message:?string,seedbed_name:?string,request_id:?int}
     *   state: free | pending_here | pending_other | member_here | member_other | approved_here | approved_other
     */
    public static function evaluate(User $user, ?int $seedbedId = null): array
    {
        $pending = MembershipRequest::with('seedbed:id,name')
            ->where('user_id', $user->id)->where('status', 'PENDIENTE')->get();

        $members = SeedbedMember::with('seedbed:id,name,status')
            ->where('status', 'ACTIVO')
            ->where(fn ($q) => self::mine($q, $user))
            ->whereHas('seedbed', fn ($q) => $q->where('status', 'ACTIVO'))
            ->get();

        // Aprobadas a semilleros activos que aún no tienen registro del estudiante como integrante (en ningún estado).
        $approved = MembershipRequest::with('seedbed:id,name,status')
            ->where('user_id', $user->id)->where('status', 'APROBADA')
            ->whereHas('seedbed', fn ($q) => $q->where('status', 'ACTIVO'))
            ->get()
            ->filter(fn ($r) => ! SeedbedMember::where('seedbed_id', $r->seedbed_id)->where(fn ($q) => self::mine($q, $user))->exists());

        // Primero lo que ocurre en ESTE semillero (es lo que se le muestra en su detalle), luego lo de otros.
        $found = [
            ['member',   $members,  fn ($m) => $m->seedbed_id],
            ['approved', $approved, fn ($r) => $r->seedbed_id],
            ['pending',  $pending,  fn ($r) => $r->seedbed_id],
        ];
        foreach ([true, false] as $here) {
            foreach ($found as [$kind, $items, $idOf]) {
                foreach ($items as $item) {
                    if (($seedbedId !== null && (int) $idOf($item) === $seedbedId) === $here) {
                        return self::result($kind, $here, $item->seedbed?->name, $kind === 'pending' ? (int) $item->id : null);
                    }
                }
            }
        }

        return ['can_apply' => true, 'state' => 'free', 'message' => null, 'seedbed_name' => null, 'request_id' => null];
    }

    private static function mine($q, User $user): void
    {
        $q->where('user_id', $user->id)->orWhereRaw('lower(email) = ?', [mb_strtolower((string) $user->email)]);
    }

    private static function result(string $kind, bool $here, ?string $name, ?int $requestId = null): array
    {
        $n = $name ? "«{$name}»" : 'otro semillero';

        $messages = [
            'member_here'    => 'Ya eres integrante de este semillero.',
            'member_other'   => "Ya eres integrante de {$n}. Para postularte a otro semillero primero debes salir de ese: pídele a su líder que te inactive.",
            'approved_here'  => 'Tu solicitud a este semillero fue aprobada: tu líder te registrará como integrante.',
            'approved_other' => "Tu solicitud a {$n} fue aprobada. Mientras seas parte de ese semillero no puedes postularte a otro.",
            'pending_here'   => 'Tu solicitud a este semillero está pendiente de respuesta.',
            'pending_other'  => "Ya tienes una solicitud pendiente en {$n}. Espera su respuesta antes de postularte a otro semillero.",
        ];
        $state = $kind . ($here ? '_here' : '_other');

        return ['can_apply' => false, 'state' => $state, 'message' => $messages[$state], 'seedbed_name' => $name, 'request_id' => $requestId];
    }
}
