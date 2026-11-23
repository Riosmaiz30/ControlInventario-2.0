<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Despacho;
use App\Models\Empleado;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sede;
use Illuminate\Contracts\Support\Renderable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        return view('home', [
            'totalProductos' => Producto::count(),
            'totalLotes' => Lote::count(),
            'totalProveedores' => Proveedor::count(),
            'totalCategorias' => Categoria::count(),
            'totalEmpleados' => Empleado::count(),
            'totalSedes' => Sede::count(),
            'totalPuntosConsumo' => Sede::where('tipo', 'consumidor')->count(),
            'totalDespachos' => Despacho::query()->registrados()->count(),
        ]);
    }
}
