<?php

namespace Tests\Feature;

use App\Livewire\GestionLotes;
use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GestionLotesTest extends TestCase
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

    private function proveedor(string $nombre = 'Distribuidora ABC', string $rif = 'J-12345678-9'): Proveedor
    {
        return Proveedor::create([
            'nombre' => $nombre,
            'rif' => $rif,
        ]);
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

    public function test_registra_una_entrada_e_inicializa_el_stock_actual(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $hoy = now()->format('Y-m-d');

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->assertSet('fecha_registro', $hoy)
            ->set('producto_id', $producto->id)
            ->set('proveedor_id', $proveedor->id)
            ->assertSet('numero_lote', "Dis-abc-{$hoy}-001")
            ->set('stock_inicial', 25.5)
            ->set('fecha_vencimiento', now()->addYear()->format('Y-m-d'))
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Entrada de mercancía registrada correctamente.');

        $this->assertDatabaseHas('lotes', [
            'numero_lote' => "Dis-abc-{$hoy}-001",
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
            'stock_inicial' => 25.5,
            'stock_actual' => 25.5,
        ]);

        $this->assertSame($hoy, Lote::first()->fecha_registro->format('Y-m-d'));
    }

    public function test_genera_el_numero_con_las_iniciales_de_cada_palabra_del_proveedor(): void
    {
        $proveedor = $this->proveedor('Distribuidora La Oriental', 'J-11111111-1');
        $hoy = now()->format('Y-m-d');

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->set('proveedor_id', $proveedor->id)
            ->assertSet('numero_lote', "Dis-la-ori-{$hoy}-001");
    }

    public function test_incrementa_el_consecutivo_del_numero_de_lote(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $hoy = now()->format('Y-m-d');

        $this->lote($producto, $proveedor, [
            'numero_lote' => "Dis-abc-{$hoy}-001",
        ]);

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->set('proveedor_id', $proveedor->id)
            ->assertSet('numero_lote', "Dis-abc-{$hoy}-002");
    }

    public function test_regenera_el_numero_al_cambiar_el_proveedor(): void
    {
        $proveedorA = $this->proveedor('Distribuidora ABC', 'J-22222222-2');
        $proveedorB = $this->proveedor('Comercial XYZ', 'J-33333333-3');
        $hoy = now()->format('Y-m-d');

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->set('proveedor_id', $proveedorA->id)
            ->assertSet('numero_lote', "Dis-abc-{$hoy}-001")
            ->set('proveedor_id', $proveedorB->id)
            ->assertSet('numero_lote', "Com-xyz-{$hoy}-001");
    }

    public function test_regenera_el_numero_al_cambiar_la_fecha_de_registro(): void
    {
        $proveedor = $this->proveedor();

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->set('proveedor_id', $proveedor->id)
            ->set('fecha_registro', '2026-11-10')
            ->assertSet('numero_lote', 'Dis-abc-2026-11-10-001');
    }

    public function test_valida_producto_proveedor_stock_y_fechas(): void
    {
        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->set('producto_id', 99999)
            ->set('proveedor_id', 99999)
            ->set('stock_inicial', 0)
            ->set('fecha_registro', '')
            ->set('fecha_vencimiento', '')
            ->call('guardar')
            ->assertHasErrors([
                'producto_id' => 'exists',
                'proveedor_id' => 'exists',
                'stock_inicial' => 'gt',
                'fecha_registro' => 'required',
                'fecha_vencimiento' => 'required',
            ]);

        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_valida_que_el_vencimiento_no_sea_anterior_a_la_fecha_de_registro(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->set('producto_id', $producto->id)
            ->set('proveedor_id', $proveedor->id)
            ->set('stock_inicial', 10)
            ->set('fecha_registro', now()->format('Y-m-d'))
            ->set('fecha_vencimiento', now()->subDay()->format('Y-m-d'))
            ->call('guardar')
            ->assertHasErrors(['fecha_vencimiento' => 'after_or_equal']);

        $this->assertDatabaseCount('lotes', 0);
    }

    public function test_el_formulario_genera_el_numero_y_no_muestra_los_campos_eliminados(): void
    {
        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->assertSee('Nº de Lote')
            ->assertSee('Fecha de Registro')
            ->assertSee('readonly', false)
            ->assertDontSee('Se genera al seleccionar el proveedor')
            ->assertDontSee('Se genera con el proveedor, la fecha de registro y un consecutivo.')
            ->assertDontSee('Precio de Compra')
            ->assertDontSee('Fecha de Fabricación');
    }

    public function test_busca_por_numero_de_lote_o_nombre_del_producto(): void
    {
        $harina = $this->producto('Harina de maíz', 'HAR-001');
        $arroz = $this->producto('Arroz blanco', 'ARR-002');
        $proveedor = $this->proveedor();

        $this->lote($harina, $proveedor, ['numero_lote' => 'LOTE-HAR-001']);
        $this->lote($arroz, $proveedor, ['numero_lote' => 'LOTE-ARR-002']);

        Livewire::test(GestionLotes::class)
            ->set('search', 'LOTE-HAR')
            ->assertSee('LOTE-HAR-001')
            ->assertDontSee('LOTE-ARR-002')
            ->set('search', 'Arroz')
            ->assertSee('LOTE-ARR-002')
            ->assertDontSee('LOTE-HAR-001');
    }

    public function test_pagina_los_lotes_registrados(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();

        foreach (range(1, 11) as $indice) {
            $this->lote($producto, $proveedor, [
                'numero_lote' => 'LOTE-'.str_pad((string) $indice, 3, '0', STR_PAD_LEFT),
            ]);
        }

        Livewire::test(GestionLotes::class)
            ->assertViewHas('lotes', function ($lotes) {
                return $lotes->count() === 10 && $lotes->total() === 11;
            });
    }

    public function test_no_permite_editar_si_no_hay_clave_configurada(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor);

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', $lote->id, 'cualquier-clave')
            ->assertSet('mostrarModal', false)
            ->assertSet('isEditing', false)
            ->assertSee('No hay una clave de administrador configurada.');
    }

    public function test_rechaza_la_clave_incorrecta_al_editar(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor);

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('clave-correcta'),
        );

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', $lote->id, 'clave-incorrecta')
            ->assertSet('mostrarModal', false)
            ->assertSet('isEditing', false)
            ->assertSee('La clave de administrador es incorrecta.');
    }

    public function test_abre_el_formulario_de_edicion_con_la_clave_correcta(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor);

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('clave-correcta'),
        );

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', $lote->id, 'clave-correcta')
            ->assertSet('mostrarModal', true)
            ->assertSet('isEditing', true)
            ->assertSet('lote_id', $lote->id)
            ->assertSet('producto_id', $producto->id)
            ->assertSet('proveedor_id', $proveedor->id)
            ->assertSet('numero_lote', $lote->numero_lote)
            ->assertSee('Editar Entrada / Lote');
    }

    public function test_actualiza_un_lote_y_registra_la_auditoria(): void
    {
        $usuario = User::factory()->create(['name' => 'Administrador']);
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $hoy = now()->format('Y-m-d');
        $lote = $this->lote($producto, $proveedor, [
            'numero_lote' => "Dis-abc-{$hoy}-001",
            'stock_inicial' => 20,
            'stock_actual' => 20,
        ]);

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('clave-correcta'),
        );

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', $lote->id, 'clave-correcta')
            ->set('stock_inicial', 25)
            ->set('fecha_vencimiento', now()->addYears(2)->format('Y-m-d'))
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Entrada de mercancía actualizada correctamente.');

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'numero_lote' => "Dis-abc-{$hoy}-001",
            'stock_inicial' => 25,
            'stock_actual' => 25,
        ]);

        $auditoria = Auditoria::first();

        $this->assertNotNull($auditoria);
        $this->assertSame('lote_actualizado', $auditoria->accion);
        $this->assertSame('lote', $auditoria->modelo);
        $this->assertSame($lote->id, $auditoria->modelo_id);
        $this->assertSame($usuario->id, $auditoria->user_id);
        $this->assertEquals(20.0, $auditoria->datos_anteriores['stock_inicial']);
        $this->assertEquals(25.0, $auditoria->datos_nuevos['stock_inicial']);
    }

    public function test_ajusta_el_stock_actual_conservando_los_movimientos(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $lote = $this->lote($producto, $proveedor, [
            'stock_inicial' => 20,
            'stock_actual' => 12,
        ]);

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('clave-correcta'),
        );

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', $lote->id, 'clave-correcta')
            ->set('stock_inicial', 25)
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'stock_inicial' => 25,
            'stock_actual' => 17,
        ]);
    }

    public function test_regenera_el_numero_de_lote_al_cambiar_el_proveedor_en_edicion(): void
    {
        $producto = $this->producto();
        $proveedorA = $this->proveedor('Distribuidora ABC', 'J-44444444-4');
        $proveedorB = $this->proveedor('Comercial XYZ', 'J-55555555-5');
        $hoy = now()->format('Y-m-d');

        $lote = $this->lote($producto, $proveedorA, [
            'numero_lote' => "Dis-abc-{$hoy}-001",
        ]);

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('clave-correcta'),
        );

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', $lote->id, 'clave-correcta')
            ->set('proveedor_id', $proveedorB->id)
            ->assertSet('numero_lote', "Com-xyz-{$hoy}-001")
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'proveedor_id' => $proveedorB->id,
            'numero_lote' => "Com-xyz-{$hoy}-001",
        ]);
    }

    public function test_muestra_el_boton_de_modificar_y_el_estado_de_la_clave(): void
    {
        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $this->lote($producto, $proveedor);

        Livewire::test(GestionLotes::class)
            ->assertSee('data-confirm-edit', false)
            ->assertSee('data-clave-configurada="0"', false);

        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('clave-correcta'),
        );

        Livewire::test(GestionLotes::class)
            ->assertSee('data-clave-configurada="1"', false);
    }
}
