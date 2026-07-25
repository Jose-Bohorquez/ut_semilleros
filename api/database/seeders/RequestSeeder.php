<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Seedbed;
use App\Models\MembershipRequest;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $estudiante = User::where('email', 'estudiante@ut.edu.co')->first();
        $lider      = User::where('email', 'lider@ut.edu.co')->first();

        $seedbeds = Seedbed::all();

        $requests = [
            ['user_id' => $estudiante->id, 'seedbed_id' => $seedbeds[0]->id, 'status' => 'PENDIENTE'],
            ['user_id' => $estudiante->id, 'seedbed_id' => $seedbeds[1]->id, 'status' => 'APROBADA'],
            ['user_id' => $lider->id,      'seedbed_id' => $seedbeds[2]->id, 'status' => 'APROBADA'],
            ['user_id' => $estudiante->id, 'seedbed_id' => $seedbeds[3]->id, 'status' => 'RECHAZADA'],
            ['user_id' => $lider->id,      'seedbed_id' => $seedbeds[4]->id, 'status' => 'PENDIENTE'],
        ];

        foreach ($requests as $req) {
            MembershipRequest::firstOrCreate(
                ['user_id' => $req['user_id'], 'seedbed_id' => $req['seedbed_id']],
                $req
            );
        }
    }
}
