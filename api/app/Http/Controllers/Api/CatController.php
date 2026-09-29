<?php # archivo: backend/app/Http/Controllers/Api/CatController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Cat;

/**
 * RF04 — Gestión de Centros de Atención Tutorial (CAT): código (único,
 * RN08), nombre, dirección, ciudad, correo y hasta 3 teléfonos (al menos
 * uno). No se eliminan: solo se activan o inactivan (RN01).
 */
class CatController extends Controller
{
    private const MESSAGES = [
        'code.required' => 'El código es obligatorio.',
        'code.unique'   => 'Ya existe un CAT con ese código.',
        'name.required' => 'El nombre es obligatorio.',
        'email.email'   => 'Ingrese un correo con formato válido.',
        'phone1.regex'  => 'El teléfono principal solo admite números, espacios, +, - y paréntesis.',
        'phone2.regex'  => 'El teléfono 2 solo admite números, espacios, +, - y paréntesis.',
        'phone3.regex'  => 'El teléfono 3 solo admite números, espacios, +, - y paréntesis.',
        'status.in'     => 'El estado debe ser ACTIVO o INACTIVO.',
    ];

    /* Permisivo a propósito: admite +57, espacios, guiones y paréntesis,
       para no rechazar formatos reales de conmutadores institucionales. */
    private const PHONE_REGEX = '/^[0-9+\-\s()]{7,20}$/';

    public function index()
    {

        $cats = Cat::orderBy('name')->get();

        return response()->json([
            "cats"=>$cats
        ]);

    }


    /**
     * Detalle de un CAT (CU09-A1): fechas y estado. Sin relaciones en el
     * esquema (no hay FK de otras tablas hacia cats).
     */
    public function show($id)
    {
        $cat = Cat::findOrFail($id);

        return response()->json([
            'cat' => $cat,
        ]);
    }

    public function store(Request $request)
    {

        $this->normalizeCode($request);

        $validated = $request->validate($this->rules(null), self::MESSAGES);
        $this->requireAtLeastOnePhone($request);
        $validated['status'] = $validated['status'] ?? 'ACTIVO';

        $cat = Cat::create($validated);

        return response()->json([
            "message"=>"CAT creado",
            "cat"=>$cat
        ],201);

    }


    public function update(Request $request,$id)
    {

        $cat = Cat::findOrFail($id);

        $this->normalizeCode($request);

        $validated = $request->validate($this->rules($id), self::MESSAGES);
        $this->requireAtLeastOnePhone($request);

        $cat->update($validated);

        return response()->json([
            "message"=>"CAT actualizado",
            "cat"=>$cat
        ]);

    }


    public function toggleStatus($id)
    {

        $cat = Cat::findOrFail($id);

        $cat->status = $cat->status === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        $cat->save();

        return response()->json([
            "message"=>"Estado CAT actualizado",
            "cat"=>$cat
        ]);

    }

    /* RN08: «cat-bga» y «CAT-BGA» son el mismo código */
    private function normalizeCode(Request $request): void
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => mb_strtoupper(trim($request->input('code')))]);
        }
    }

    private function rules(?int $ignoreId): array
    {
        return [

            "name"    => "required|string|max:255",
            "code"    => ["required", "string", "max:50", Rule::unique('cats', 'code')->ignore($ignoreId)],

            "address" => "nullable|string|max:255",
            "city"    => "nullable|string|max:120",
            "email"   => "nullable|email|max:255",

            "phone1"  => ["nullable", "regex:" . self::PHONE_REGEX],
            "phone2"  => ["nullable", "regex:" . self::PHONE_REGEX],
            "phone3"  => ["nullable", "regex:" . self::PHONE_REGEX],

            "status"  => ($ignoreId ? "required" : "sometimes") . "|in:ACTIVO,INACTIVO",

        ];
    }

    /* Criterio de aceptación RF04: todo CAT tiene al menos un teléfono.
       Aparte de las reglas de arriba porque "nullable" en Laravel salta las
       demás reglas del campo cuando está vacío — un cierre (closure) en
       phone1 no se ejecutaría de forma confiable si phone1 viene vacío. */
    private function requireAtLeastOnePhone(Request $request): void
    {
        if (!$request->filled('phone1') && !$request->filled('phone2') && !$request->filled('phone3')) {
            throw ValidationException::withMessages([
                'phone1' => ['El CAT debe tener al menos un teléfono.'],
            ]);
        }
    }

}
