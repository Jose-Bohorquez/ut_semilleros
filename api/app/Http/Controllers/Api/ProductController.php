<?php #aechivo: 📁 backend/app/Http/Controllers/Api/ProductController.php
 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{

    /**
     * Listar productos
     */
public function index()
{

    $products = Product::with([
        'project:id,title'
    ])
    ->select(
        'id',
        'project_id',
        'type',
        'title',
        'year',
        'url'
    )
    ->get();

    return response()->json([
        "products" => $products
    ]);

}



    /**
     * Crear producto
     */
    public function store(Request $request)
    {

        $validated = $request->validate([

            "project_id"=>"required|exists:projects,id",
            "type"=>"required|in:ARTICULO,PONENCIA,POSTER,LIBRO,SOFTWARE,PROTOTIPO",
            "title"=>"required|string|max:255",
            "year"=>"nullable|integer",
            "url"=>"nullable|string"

        ]);

        $product = Product::create($validated);

        return response()->json([
            "message"=>"Producto creado",
            "product"=>$product
        ],201);

    }



    /**
     * Actualizar producto
     */
    public function update(Request $request,$id)
    {

        $product = Product::findOrFail($id);

        $validated = $request->validate([

            "project_id"=>"required|exists:projects,id",
            "type"=>"required|in:ARTICULO,PONENCIA,POSTER,LIBRO,SOFTWARE,PROTOTIPO",
            "title"=>"required|string|max:255",
            "year"=>"nullable|integer",
            "url"=>"nullable|string"

        ]);

        $product->update($validated);

        return response()->json([
            "message"=>"Producto actualizado",
            "product"=>$product
        ]);

    }

}