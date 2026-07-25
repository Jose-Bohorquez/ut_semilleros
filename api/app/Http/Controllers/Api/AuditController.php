<?php #archivo:backend/app/Http/Controllers/Api/AuditController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;

class AuditController extends Controller
{

    public function index()
    {

        $audits = Audit::with([
            'user:id,name'
        ])
        ->latest()
        ->get();

        return response()->json([
            "audits"=>$audits
        ]);

    }

}