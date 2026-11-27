<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Despacho;
use App\Models\DespachoDetalle;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\TestCase;

class NotaEntregaPdfTest extends TestCase
{
    use RefreshDatabase;

    private function despacho(string $estado = Despacho::ESTADO_REGISTRADO, int $cantidadDetalles = 1): Despacho
    {
        $usuario = User::factory()->create();
        $sede = Sede::create([
            'nombre' => 'Punto de Consumo Norte',
            'tipo' => 'consumidor',
            'direccion' => 'Av. Principal',
            'telefono' => '0212-1234567',
            'activo' => true,
        ]);

        $categoria = Categoria::create([
            'nombre' => 'Abarrotes',
            'descripcion' => 'Productos de consumo básico',
        ]);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);

        $despacho = Despacho::create([
            'numero' => 'DES-2026-11-25-001',
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'despachado_por_nombre' => 'Juan Pérez',
            'recibido_por_nombre' => 'María Gómez',
            'fecha_despacho' => '2026-11-25',
            'observaciones' => 'Entregar en horario matutino.',
            'estado' => $estado,
            'consumo_interno' => false,
        ]);

        for ($i = 1; $i <= $cantidadDetalles; $i++) {
            DespachoDetalle::create([
                'despacho_id' => $despacho->id,
                'producto_id' => $producto->id,
                'lote_id' => null,
                'producto_nombre' => 'Harina de maíz',
                'numero_lote' => sprintf('LOTE-%03d', $i),
                'cantidad' => $i === 1 ? 12.5 : 1,
            ]);
        }

        return $despacho;
    }

    public function test_un_invitado_no_puede_descargar_la_nota_de_entrega(): void
    {
        $despacho = $this->despacho();

        $this->get(route('despachos.nota-entrega', $despacho))
            ->assertRedirect(route('login'));
    }

    public function test_genera_la_nota_de_entrega_con_los_datos_del_despacho(): void
    {
        Pdf::fake();

        $despacho = $this->despacho();
        $usuario = User::first();

        $this->actingAs($usuario)
            ->get(route('despachos.nota-entrega', $despacho))
            ->assertOk();

        Pdf::assertRespondedWithPdf(function ($pdf) {
            return $pdf->contains('DES-2026-11-25-001')
                && $pdf->contains('Almacén Central')
                && $pdf->contains('Punto de Consumo Norte')
                && $pdf->contains('Juan Pérez')
                && $pdf->contains('María Gómez')
                && $pdf->contains('Harina de maíz')
                && $pdf->contains('12,50')
                && $pdf->contains('kg')
                && $pdf->contains('Descripción / Novedades')
                && $pdf->contains('Entregar en horario matutino.')
                && $pdf->contains('Por la Protección Integral De Los Niños, Niñas Y Adolescentes.');
        });
    }

    public function test_la_nota_de_entrega_muestra_la_marca_de_agua_en_despachos_anulados(): void
    {
        Pdf::fake();

        $despacho = $this->despacho(Despacho::ESTADO_ANULADO);

        $this->actingAs(User::first())
            ->get(route('despachos.nota-entrega', $despacho))
            ->assertOk();

        Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->contains('ANULADO'));
    }

    public function test_15_renglones_generan_una_sola_pagina(): void
    {
        Pdf::fake();

        $despacho = $this->despacho(Despacho::ESTADO_REGISTRADO, 15);

        $this->actingAs(User::first())
            ->get(route('despachos.nota-entrega', $despacho))
            ->assertOk();

        Pdf::assertRespondedWithPdf(function ($pdf) {
            $html = $pdf->getHtml();

            return substr_count($html, '<table class="items">') === 1
                && substr_count($html, 'class="pagina-nota') === 1
                && substr_count($html, '<table class="firmas"') === 1;
        });
    }

    public function test_16_renglones_generan_dos_paginas_y_firmas_solo_en_la_ultima(): void
    {
        Pdf::fake();

        $despacho = $this->despacho(Despacho::ESTADO_REGISTRADO, 16);

        $this->actingAs(User::first())
            ->get(route('despachos.nota-entrega', $despacho))
            ->assertOk();

        Pdf::assertRespondedWithPdf(function ($pdf) {
            $html = $pdf->getHtml();
            $primeraTabla = strpos($html, '<table class="items">');
            $segundaTabla = strpos($html, '<table class="items">', $primeraTabla + 1);
            $firmas = strpos($html, '<table class="firmas"');

            return substr_count($html, '<table class="items">') === 2
                && substr_count($html, 'class="pagina-nota pagina-nota--salto"') === 1
                && substr_count($html, '<table class="firmas"') === 1
                && str_contains($html, 'class="pie-fijo"')
                && $segundaTabla !== false
                && $firmas > $segundaTabla;
        });
    }
}
