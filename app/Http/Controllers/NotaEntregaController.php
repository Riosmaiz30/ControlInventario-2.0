<?php

namespace App\Http\Controllers;

use App\Models\Despacho;
use Spatie\LaravelPdf\Facades\Pdf;

class NotaEntregaController extends Controller
{
    public function __invoke(Despacho $despacho)
    {
        $despacho->loadMissing([
            'sede',
            'despachadoPor',
            'recibidoPor',
            'detalles.producto',
        ]);

        $paginas = $despacho->detalles->sortBy('id')->values()->chunk(15);

        return Pdf::view('pdf.nota-entrega', [
            'despacho' => $despacho,
            'paginas' => $paginas,
        ])->format('a4')->download("nota-entrega-{$despacho->numero}.pdf");
    }
}
