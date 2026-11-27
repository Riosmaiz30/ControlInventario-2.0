<?php

namespace Tests\Feature;

use App\Livewire\GestionDespachos;
use App\Models\Auditoria;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Despacho;
use App\Models\Empleado;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionDespachosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    private function producto(string $nombre = 'Harina de maíz', string $sku = 'HAR-001'): Producto
    {
        $categoria = Categoria::create([
            'nombre' => 'Abarrotes',
            'descripcion' => 'Productos de consumo básico',
        ]);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => $sku,
            'nombre' => $nombre,
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);
    }

    private function proveedor(string $rif = 'J-12345678-9'): Proveedor
    {
        return Proveedor::create([
            'nombre' => 'Distribuidora ABC',
            'rif' => $rif,
        ]);
    }

    private function empleado(string $cargo, string $cedula, string $nombres = 'Juan', string $apellidos = 'Pérez', bool $activo = true): Empleado
    {
        $cargoModel = Cargo::query()->firstOrCreate(
            ['nombre' => $cargo],
            ['activo' => true],
        );

        return Empleado::create([
            'cedula' => $cedula,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'telefono' => null,
            'cargo_id' => $cargoModel->id,
            'activo' => $activo,
        ]);
    }

    private function sede(array $datos = []): Sede
    {
        return Sede::create(array_merge([
            'nombre' => 'Punto de Consumo Norte',
            'tipo' => 'consumidor',
            'empleado_id' => null,
            'direccion' => 'Av. Principal',
            'telefono' => '0212-1234567',
            'activo' => true,
        ], $datos));
    }

    private function lote(Producto $producto, Proveedor $proveedor, array $atributos = []): Lote
    {
        return Lote::create(array_merge([
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
            'numero_lote' => 'LOTE-001',
            'stock_inicial' => 20,
            'stock_actual' => 20,
            'fecha_registro' => now()->format('Y-m-d'),
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
        ], $atributos));
    }

    private function despacho(Sede $sede, User $usuario, array $atributos = []): Despacho
    {
        return Despacho::create(array_merge([
            'numero' => 'DES-2026-11-23-001',
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'fecha_despacho' => '2026-11-23',
            'observaciones' => null,
            'estado' => Despacho::ESTADO_REGISTRADO,
        ], $atributos));
    }

    private function configurarResponsables(?Sede $sede = null, string $prefijo = '1000'): array
    {
        $jefeAlmacen = $this->empleado('Jefe de Almacén', 'V-'.$prefijo.'0001');
        $jefeCentro = $this->empleado('Jefe de Centro', 'V-'.$prefijo.'0002', 'María', 'Gómez');

        if ($sede) {
            $sede->update(['empleado_id' => $jefeCentro->id]);
        }

        return [$jefeAlmacen, $jefeCentro];
    }

    private function registrarDespacho(Lote $lote, Sede $sede, float $cantidad, string $fecha = '2026-11-23')
    {
        return Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('sede_id', $sede->id)
            ->set('fecha_despacho', $fecha)
            ->set('items', [
                ['lote_id' => $lote->id, 'cantidad' => $cantidad],
            ])
            ->call('guardar');
    }

    public function test_el_formulario_de_despacho_muestra_el_boton_de_guardar_en_el_pie_del_modal(): void
    {
        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->assertSee('Registrar Despacho')
            ->assertSee('modal-footer', false);
    }

    public function test_registra_un_despacho_y_descuenta_el_stock_del_lote_elegido(): void
    {
        $usuario = User::factory()->create(['name' => 'Operador']);
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        [$jefeAlmacen, $jefeCentro] = $this->configurarResponsables($sede);

        $loteVencePrimero = $this->lote($producto, $proveedor, [
            'numero_lote' => 'LOTE-A',
            'stock_inicial' => 5,
            'stock_actual' => 5,
            'fecha_vencimiento' => '2026-12-01',
        ]);

        $loteElegido = $this->lote($producto, $proveedor, [
            'numero_lote' => 'LOTE-B',
            'stock_inicial' => 10,
            'stock_actual' => 10,
            'fecha_vencimiento' => '2027-01-01',
        ]);

        $this->registrarDespacho($loteElegido, $sede, 4)
            ->assertHasNoErrors()
            ->assertDispatched('descargar-nota-entrega', fn (string $event, array $params) => str_contains($params['url'], route('despachos.nota-entrega', ['despacho' => 1])))
            ->assertSee('Despacho registrado correctamente.');

        $this->assertDatabaseHas('despachos', [
            'numero' => 'DES-2026-11-23-001',
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'despachado_por_id' => $jefeAlmacen->id,
            'despachado_por_nombre' => 'Juan Pérez',
            'recibido_por_id' => $jefeCentro->id,
            'recibido_por_nombre' => 'María Gómez',
            'consumo_interno' => false,
            'estado' => Despacho::ESTADO_REGISTRADO,
        ]);

        $this->assertDatabaseHas('lotes', [
            'id' => $loteVencePrimero->id,
            'stock_actual' => 5,
        ]);

        $this->assertDatabaseHas('lotes', [
            'id' => $loteElegido->id,
            'stock_actual' => 6,
        ]);

        $this->assertDatabaseCount('despacho_detalles', 1);
        $this->assertDatabaseHas('despacho_detalles', [
            'lote_id' => $loteElegido->id,
            'numero_lote' => 'LOTE-B',
            'producto_nombre' => 'Harina de maíz',
            'cantidad' => 4,
        ]);

        $auditoria = Auditoria::first();

        $this->assertNotNull($auditoria);
        $this->assertSame('despacho_registrado', $auditoria->accion);
        $this->assertSame('despacho', $auditoria->modelo);
        $this->assertSame($usuario->id, $auditoria->user_id);
        $this->assertEquals(4.0, $auditoria->datos_nuevos['total_unidades']);
        $this->assertSame('Juan Pérez', $auditoria->datos_nuevos['despachado_por']);
        $this->assertSame('María Gómez', $auditoria->datos_nuevos['recibido_por']);
        $this->assertFalse($auditoria->datos_nuevos['consumo_interno']);
    }

    public function test_no_permite_despachar_mas_que_el_stock_del_lote(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->configurarResponsables($sede);
        $lote = $this->lote($producto, $proveedor, ['stock_inicial' => 10, 'stock_actual' => 10]);

        $this->registrarDespacho($lote, $sede, 15)
            ->assertHasErrors(['items']);

        $this->assertDatabaseCount('despachos', 0);
        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'stock_actual' => 10,
        ]);
    }

    public function test_valida_los_campos_obligatorios_del_despacho(): void
    {
        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('sede_id', '')
            ->set('fecha_despacho', '')
            ->set('items', [
                ['lote_id' => '', 'cantidad' => ''],
            ])
            ->call('guardar')
            ->assertHasErrors([
                'sede_id' => 'required',
                'fecha_despacho' => 'required',
                'items.0.lote_id' => 'required',
                'items.0.cantidad' => 'required',
            ]);

        $this->assertDatabaseCount('despachos', 0);
    }

    public function test_no_permite_repetir_el_mismo_lote(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->configurarResponsables($sede);
        $lote = $this->lote($producto, $proveedor, ['stock_actual' => 10]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('sede_id', $sede->id)
            ->set('items', [
                ['lote_id' => $lote->id, 'cantidad' => 1],
                ['lote_id' => $lote->id, 'cantidad' => 2],
            ])
            ->call('guardar')
            ->assertHasErrors(['items.1.lote_id']);

        $this->assertDatabaseCount('despachos', 0);
    }

    public function test_asigna_automaticamente_al_jefe_de_almacen_y_al_jefe_de_centro(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede(['nombre' => 'Punto de Consumo Centro']);
        [$jefeAlmacen, $jefeCentro] = $this->configurarResponsables($sede);
        $lote = $this->lote($producto, $proveedor, ['stock_actual' => 10]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('sede_id', $sede->id)
            ->assertSee('Juan Pérez')
            ->assertSee('María Gómez')
            ->assertViewHas('jefeAlmacen', fn ($empleado) => $empleado->id === $jefeAlmacen->id)
            ->assertViewHas('jefeCentro', fn ($empleado) => $empleado->id === $jefeCentro->id);

        $this->registrarDespacho($lote, $sede, 2)->assertHasNoErrors();

        $this->assertDatabaseHas('despachos', [
            'despachado_por_id' => $jefeAlmacen->id,
            'recibido_por_id' => $jefeCentro->id,
        ]);
    }

    public function test_no_guarda_si_no_hay_jefe_de_almacen(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $jefeCentro = $this->empleado('Jefe de Centro', 'V-10000002', 'María', 'Gómez');
        $sede->update(['empleado_id' => $jefeCentro->id]);
        $lote = $this->lote($producto, $proveedor, ['stock_actual' => 10]);

        $this->registrarDespacho($lote, $sede, 2)
            ->assertHasErrors(['despachado_por'])
            ->assertSee('No hay un empleado activo con el cargo');

        $this->assertDatabaseCount('despachos', 0);
    }

    public function test_no_guarda_si_la_sede_no_tiene_jefe_de_centro(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->empleado('Jefe de Almacén', 'V-10000001');
        $lote = $this->lote($producto, $proveedor, ['stock_actual' => 10]);

        $this->registrarDespacho($lote, $sede, 2)
            ->assertHasErrors(['recibido_por'])
            ->assertSee('no tiene un responsable con el cargo');

        $this->assertDatabaseCount('despachos', 0);
    }

    public function test_registra_como_consumo_interno_cuando_la_sede_es_principal(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede([
            'nombre' => 'Sede Principal',
            'tipo' => 'principal',
        ]);
        $this->configurarResponsables($sede);
        $lote = $this->lote($producto, $proveedor, ['stock_actual' => 10]);

        $this->registrarDespacho($lote, $sede, 3)
            ->assertHasNoErrors()
            ->assertSee('Consumo interno');

        $this->assertDatabaseHas('despachos', [
            'sede_id' => $sede->id,
            'consumo_interno' => true,
        ]);

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'stock_actual' => 7,
        ]);

        $auditoria = Auditoria::first();
        $this->assertTrue($auditoria->datos_nuevos['consumo_interno']);
    }

    public function test_anula_un_despacho_y_restaura_el_stock(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->configurarResponsables($sede);

        $lote = $this->lote($producto, $proveedor, [
            'stock_inicial' => 10,
            'stock_actual' => 10,
            'fecha_vencimiento' => '2026-12-01',
        ]);

        $this->registrarDespacho($lote, $sede, 4)->assertHasNoErrors();

        $despacho = Despacho::first();

        $this->assertDatabaseHas('lotes', ['id' => $lote->id, 'stock_actual' => 6]);

        Livewire::test(GestionDespachos::class)
            ->call('anular', $despacho->id)
            ->assertSee('Despacho anulado y stock restaurado correctamente.');

        $this->assertDatabaseHas('despachos', [
            'id' => $despacho->id,
            'estado' => Despacho::ESTADO_ANULADO,
        ]);

        $this->assertDatabaseHas('lotes', ['id' => $lote->id, 'stock_actual' => 10]);

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'despacho_anulado',
            'modelo' => 'despacho',
            'modelo_id' => $despacho->id,
        ]);

        Livewire::test(GestionDespachos::class)
            ->call('anular', $despacho->id)
            ->assertSee('Este despacho ya se encuentra anulado.');

        $this->assertDatabaseHas('lotes', ['id' => $lote->id, 'stock_actual' => 10]);
    }

    public function test_muestra_el_detalle_del_despacho(): void
    {
        $usuario = User::factory()->create(['name' => 'Operador']);
        $this->actingAs($usuario);

        $producto = $this->producto('Arroz blanco', 'ARR-002');
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->configurarResponsables($sede);

        $lote = $this->lote($producto, $proveedor, [
            'numero_lote' => 'LOTE-ARR-001',
            'stock_inicial' => 10,
            'stock_actual' => 10,
        ]);

        $this->registrarDespacho($lote, $sede, 3)->assertHasNoErrors();

        $despacho = Despacho::first();

        Livewire::test(GestionDespachos::class)
            ->call('verDetalle', $despacho->id)
            ->assertSet('detalleId', $despacho->id)
            ->assertSee('Despacho DES-2026-11-23-001')
            ->assertSee('Arroz blanco')
            ->assertSee('LOTE-ARR-001')
            ->assertSee('Operador')
            ->assertSee('Juan Pérez')
            ->assertSee('María Gómez');
    }

    public function test_genera_numeros_consecutivos_por_fecha(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->configurarResponsables($sede);

        $lote = $this->lote($producto, $proveedor, ['stock_inicial' => 10, 'stock_actual' => 10]);

        $this->registrarDespacho($lote, $sede, 1, '2026-11-23')
            ->assertHasNoErrors()
            ->assertSee('Despacho registrado correctamente.');

        $this->registrarDespacho($lote, $sede, 1, '2026-11-23')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('despachos', ['numero' => 'DES-2026-11-23-001']);
        $this->assertDatabaseHas('despachos', ['numero' => 'DES-2026-11-23-002']);
    }

    public function test_busca_por_numero_sede_o_producto(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);

        $harina = $this->producto('Harina de maíz', 'HAR-001');
        $arroz = $this->producto('Arroz blanco', 'ARR-002');
        $proveedor = $this->proveedor();
        $sedeNorte = $this->sede(['nombre' => 'Punto Norte']);
        $sedeSur = $this->sede(['nombre' => 'Punto Sur']);
        $this->configurarResponsables($sedeNorte, '2000');
        $this->configurarResponsables($sedeSur, '3000');

        $loteHarina = $this->lote($harina, $proveedor, ['numero_lote' => 'LOTE-HAR-001', 'stock_actual' => 20]);
        $loteArroz = $this->lote($arroz, $proveedor, ['numero_lote' => 'LOTE-ARR-001', 'stock_actual' => 20]);

        $this->registrarDespacho($loteHarina, $sedeNorte, 2, '2026-11-22')->assertHasNoErrors();
        $this->registrarDespacho($loteArroz, $sedeSur, 2, '2026-11-23')->assertHasNoErrors();

        Livewire::test(GestionDespachos::class)
            ->set('search', 'DES-2026-11-22')
            ->assertSee('DES-2026-11-22-001')
            ->assertDontSee('DES-2026-11-23-001');

        Livewire::test(GestionDespachos::class)
            ->set('search', 'Punto Sur')
            ->assertSee('DES-2026-11-23-001')
            ->assertDontSee('DES-2026-11-22-001');

        Livewire::test(GestionDespachos::class)
            ->set('search', 'Harina')
            ->assertSee('DES-2026-11-22-001')
            ->assertDontSee('DES-2026-11-23-001');
    }

    public function test_el_buscador_muestra_solo_lotes_con_stock(): void
    {
        $conStock = $this->producto('Harina de maíz', 'HAR-001');
        $sinStock = $this->producto('Arroz blanco', 'ARR-002');
        $proveedor = $this->proveedor();
        $this->lote($conStock, $proveedor, ['numero_lote' => 'LOTE-HAR-001', 'stock_actual' => 10]);
        $this->lote($sinStock, $proveedor, ['numero_lote' => 'LOTE-ARR-001', 'stock_actual' => 0]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->assertViewHas('lotesDisponibles', function ($lotes) {
                return $lotes->pluck('numero_lote')->contains('LOTE-HAR-001')
                    && ! $lotes->pluck('numero_lote')->contains('LOTE-ARR-001');
            });
    }

    public function test_filtra_los_lotes_por_numero_producto_o_sku(): void
    {
        $harina = $this->producto('Harina de maíz', 'HAR-001');
        $arroz = $this->producto('Arroz blanco', 'ARR-002');
        $proveedor = $this->proveedor();
        $this->lote($harina, $proveedor, ['numero_lote' => 'LOTE-HAR-001', 'stock_actual' => 10]);
        $this->lote($arroz, $proveedor, ['numero_lote' => 'LOTE-ARR-001', 'stock_actual' => 20]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('loteBusqueda', 'LOTE-ARR')
            ->assertViewHas('lotesDisponibles', function ($lotes) {
                return $lotes->pluck('numero_lote')->contains('LOTE-ARR-001')
                    && ! $lotes->pluck('numero_lote')->contains('LOTE-HAR-001');
            });

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('loteBusqueda', 'harina')
            ->assertViewHas('lotesDisponibles', function ($lotes) {
                return $lotes->pluck('numero_lote')->contains('LOTE-HAR-001')
                    && ! $lotes->pluck('numero_lote')->contains('LOTE-ARR-001');
            });

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('loteBusqueda', 'ARR-002')
            ->assertViewHas('lotesDisponibles', function ($lotes) {
                return $lotes->pluck('numero_lote')->contains('LOTE-ARR-001')
                    && ! $lotes->pluck('numero_lote')->contains('LOTE-HAR-001');
            });
    }

    public function test_agrega_un_lote_desde_el_buscador(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor, ['numero_lote' => 'LOTE-001', 'stock_actual' => 10]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('loteBusqueda', 'LOTE-001')
            ->assertViewHas('lotesDisponibles', fn ($lotes) => $lotes->pluck('numero_lote')->contains('LOTE-001'))
            ->call('agregarLote', $lote->id)
            ->assertSet('loteBusqueda', '')
            ->assertSet('items.0.lote_id', $lote->id)
            ->assertViewHas('lotesDisponibles', fn ($lotes) => $lotes->isEmpty())
            ->assertSee('Productos a entregar');
    }

    public function test_agrega_el_primer_resultado_con_enter(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor, ['numero_lote' => 'LOTE-001', 'stock_actual' => 10]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('loteBusqueda', 'LOTE-001')
            ->call('agregarPrimerLote')
            ->assertSet('items.0.lote_id', $lote->id);
    }

    public function test_no_agrega_un_lote_sin_stock(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor, ['numero_lote' => 'LOTE-SIN-STOCK', 'stock_actual' => 0]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->call('agregarLote', $lote->id)
            ->assertCount('items', 0)
            ->assertSee('no tiene stock disponible.');
    }

    public function test_no_agrega_el_mismo_lote_dos_veces(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor, ['stock_actual' => 10]);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->call('agregarLote', $lote->id)
            ->call('agregarLote', $lote->id)
            ->assertCount('items', 1)
            ->assertSee('ya está agregado al despacho.');
    }

    public function test_informa_cuando_la_busqueda_no_tiene_resultados(): void
    {
        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('loteBusqueda', 'NO-EXISTE')
            ->call('agregarPrimerLote')
            ->assertHasErrors(['loteBusqueda']);
    }

    public function test_pagina_los_despachos_registrados(): void
    {
        $usuario = User::factory()->create();
        $sede = $this->sede();

        foreach (range(1, 11) as $indice) {
            $this->despacho($sede, $usuario, [
                'numero' => 'DES-2026-11-23-'.str_pad((string) $indice, 3, '0', STR_PAD_LEFT),
            ]);
        }

        Livewire::test(GestionDespachos::class)
            ->assertViewHas('despachos', function ($despachos) {
                return $despachos->count() === 10 && $despachos->total() === 11;
            });
    }
}
