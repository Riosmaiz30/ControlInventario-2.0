<?php

namespace Tests\Feature;

use App\Livewire\GestionProductos;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionProductosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->administrador()->create());
    }

    private function categoria(): Categoria
    {
        return Categoria::create([
            'nombre' => 'Abarrotes',
            'descripcion' => 'Productos de consumo básico',
        ]);
    }

    private function producto(Categoria $categoria, array $atributos = []): Producto
    {
        return Producto::create(array_merge([
            'categoria_id' => $categoria->id,
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ], $atributos));
    }

    private function proveedor(): Proveedor
    {
        return Proveedor::create([
            'nombre' => 'Distribuidora ABC',
            'rif' => 'J-12345678-9',
        ]);
    }

    private function lote(Producto $producto, Proveedor $proveedor, float $stockActual, string $numeroLote): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
            'numero_lote' => $numeroLote,
            'stock_inicial' => $stockActual,
            'stock_actual' => $stockActual,
            'fecha_registro' => now()->format('Y-m-d'),
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
        ]);
    }

    public function test_crea_un_producto_con_datos_validos(): void
    {
        $categoria = $this->categoria();

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->assertSet('isEditing', false)
            ->set('nombre', 'Harina de maíz')
            ->set('categoria_id', $categoria->id)
            ->set('unidad_medida', 'kg')
            ->set('stock_minimo_alerta', 5)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Producto registrado correctamente.');

        $this->assertDatabaseHas('productos', [
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'kg',
        ]);
    }

    public function test_genera_el_codigo_con_las_tres_primeras_letras_del_nombre(): void
    {
        $categoria = $this->categoria();

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Salchicha',
            'unidad_medida' => 'paquete',
            'stock_minimo_alerta' => 10,
        ]);

        $this->assertSame('SAL-001', $producto->sku);
    }

    public function test_incrementa_el_codigo_para_productos_con_el_mismo_prefijo(): void
    {
        $categoria = $this->categoria();

        $primero = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Salchicha',
            'unidad_medida' => 'paquete',
            'stock_minimo_alerta' => 10,
        ]);

        $segundo = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Salami',
            'unidad_medida' => 'unidad',
            'stock_minimo_alerta' => 8,
        ]);

        $this->assertSame('SAL-001', $primero->sku);
        $this->assertSame('SAL-002', $segundo->sku);
    }

    public function test_no_permite_categoria_inexistente(): void
    {
        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Producto sin categoría')
            ->set('categoria_id', 99999)
            ->set('unidad_medida', 'unidad')
            ->set('stock_minimo_alerta', 1)
            ->call('guardar')
            ->assertHasErrors(['categoria_id' => 'exists']);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_rechaza_una_unidad_de_medida_fuera_de_la_lista(): void
    {
        $categoria = $this->categoria();

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Producto de prueba')
            ->set('categoria_id', $categoria->id)
            ->set('unidad_medida', 'galon')
            ->set('stock_minimo_alerta', 1)
            ->call('guardar')
            ->assertHasErrors(['unidad_medida' => 'in']);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_el_formulario_muestra_la_lista_de_unidades_y_el_codigo_de_producto(): void
    {
        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->assertSee('Código de producto')
            ->assertSee('alert-info', false)
            ->assertSee('tres primeras letras del nombre y un consecutivo')
            ->assertSee('Seleccione una unidad')
            ->assertSee('Kilogramo (kg)')
            ->assertSee('Litro (L)')
            ->assertSee('Paquete')
            ->assertSee('Tipo de empaque')
            ->assertSee('No aplica')
            ->assertDontSee('SKU');
    }

    public function test_muestra_la_vista_previa_del_codigo_al_escribir_el_nombre(): void
    {
        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Salchicha')
            ->assertSet('sku', 'SAL-001');
    }

    public function test_crea_un_producto_con_empaque_de_bulto(): void
    {
        $categoria = $this->categoria();

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Azúcar refinada')
            ->set('categoria_id', $categoria->id)
            ->set('unidad_medida', 'kg')
            ->set('tipo_empaque', 'bulto')
            ->set('cantidad_empaque', 24)
            ->set('stock_minimo_alerta', 5)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Producto registrado correctamente.');

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Azúcar refinada',
            'unidad_medida' => 'kg',
            'tipo_empaque' => 'bulto',
            'cantidad_empaque' => 24,
            'stock_minimo_alerta' => 5,
        ]);
    }

    public function test_exige_la_cantidad_cuando_hay_empaque(): void
    {
        $categoria = $this->categoria();

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Bulto de prueba')
            ->set('categoria_id', $categoria->id)
            ->set('unidad_medida', 'kg')
            ->set('tipo_empaque', 'bulto')
            ->set('stock_minimo_alerta', 1)
            ->call('guardar')
            ->assertHasErrors([
                'cantidad_empaque' => 'required',
            ]);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_limpia_la_cantidad_al_quitar_el_empaque(): void
    {
        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('tipo_empaque', 'bulto')
            ->set('cantidad_empaque', 24)
            ->set('tipo_empaque', '')
            ->assertSet('cantidad_empaque', '');
    }

    public function test_el_formulario_muestra_la_cantidad_solo_con_empaque(): void
    {
        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->assertSee('Tipo de empaque')
            ->assertSee('No aplica')
            ->assertDontSee('Cantidad que trae el bulto')
            ->assertDontSee('Cantidad que trae la paca')
            ->set('tipo_empaque', 'bulto')
            ->assertSee('Cantidad que trae el bulto')
            ->set('tipo_empaque', 'paca')
            ->assertSee('Cantidad que trae la paca');
    }

    public function test_muestra_el_empaque_en_la_tabla(): void
    {
        $categoria = $this->categoria();
        $this->producto($categoria, [
            'nombre' => 'Azúcar refinada',
            'sku' => 'AZU-001',
            'unidad_medida' => 'kg',
            'tipo_empaque' => 'bulto',
            'cantidad_empaque' => 24,
        ]);

        Livewire::test(GestionProductos::class)
            ->assertSee('Empaque')
            ->assertSee('Bulto de 24 kg');
    }

    public function test_edita_un_producto_con_empaque(): void
    {
        $categoria = $this->categoria();
        $producto = $this->producto($categoria, [
            'nombre' => 'Azúcar refinada',
            'sku' => 'AZU-001',
            'unidad_medida' => 'kg',
            'tipo_empaque' => 'bulto',
            'cantidad_empaque' => 24,
        ]);

        Livewire::test(GestionProductos::class)
            ->call('editar', $producto->id)
            ->assertSet('unidad_medida', 'kg')
            ->assertSet('tipo_empaque', 'bulto')
            ->assertSet('cantidad_empaque', 24)
            ->set('cantidad_empaque', 30)
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'unidad_medida' => 'kg',
            'tipo_empaque' => 'bulto',
            'cantidad_empaque' => 30,
        ]);
    }

    public function test_no_guarda_empaque_cuando_no_aplica(): void
    {
        $categoria = $this->categoria();

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Arroz blanco')
            ->set('categoria_id', $categoria->id)
            ->set('unidad_medida', 'kg')
            ->set('tipo_empaque', 'bulto')
            ->set('cantidad_empaque', 24)
            ->set('tipo_empaque', '')
            ->set('stock_minimo_alerta', 10)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Arroz blanco',
            'unidad_medida' => 'kg',
            'tipo_empaque' => null,
            'cantidad_empaque' => null,
        ]);
    }

    public function test_edita_y_actualiza_un_producto(): void
    {
        $categoria = $this->categoria();
        $producto = $this->producto($categoria);

        Livewire::test(GestionProductos::class)
            ->call('editar', $producto->id)
            ->assertSet('isEditing', true)
            ->assertSet('producto_id', $producto->id)
            ->assertSet('nombre', 'Harina de maíz')
            ->assertSet('sku', 'HAR-001')
            ->set('nombre', 'Harina de maíz refinada')
            ->set('stock_minimo_alerta', 10)
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz refinada',
            'stock_minimo_alerta' => 10,
        ]);
    }

    public function test_elimina_un_producto(): void
    {
        $categoria = $this->categoria();
        $producto = $this->producto($categoria);

        Livewire::test(GestionProductos::class)
            ->call('eliminar', $producto->id);

        $this->assertDatabaseMissing('productos', ['id' => $producto->id]);
    }

    public function test_busca_por_nombre_o_codigo_de_producto(): void
    {
        $categoria = $this->categoria();
        $this->producto($categoria, ['sku' => 'HAR-001', 'nombre' => 'Harina de maíz']);
        $this->producto($categoria, ['sku' => 'ARR-002', 'nombre' => 'Arroz blanco']);

        Livewire::test(GestionProductos::class)
            ->set('search', 'HAR')
            ->assertSee('Harina de maíz')
            ->assertDontSee('Arroz blanco')
            ->set('search', 'ARR-002')
            ->assertSee('Arroz blanco')
            ->assertDontSee('Harina de maíz');
    }

    public function test_crea_un_producto_con_marca(): void
    {
        $categoria = $this->categoria();
        $marca = Marca::factory()->create(['nombre' => 'Polar']);

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->set('nombre', 'Harina de maíz')
            ->set('categoria_id', $categoria->id)
            ->set('marca_id', $marca->id)
            ->set('unidad_medida', 'kg')
            ->set('stock_minimo_alerta', 5)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Harina de maíz',
            'marca_id' => $marca->id,
        ]);
    }

    public function test_permite_crear_una_marca_desde_el_formulario_de_productos(): void
    {
        $componente = Livewire::test(GestionProductos::class)
            ->call('crear')
            ->call('crearMarca')
            ->assertSet('mostrarModalMarca', true)
            ->set('nuevoMarcaNombre', 'Polar')
            ->set('nuevoMarcaDescripcion', 'Alimentos y bebidas')
            ->call('guardarMarca')
            ->assertHasNoErrors()
            ->assertSet('mostrarModalMarca', false)
            ->assertSet('mostrarModal', true)
            ->assertSee('Marca registrada correctamente.');

        $marca = Marca::query()->where('nombre', 'Polar')->firstOrFail();

        $componente->assertSet('marca_id', $marca->id);

        $this->assertDatabaseHas('marcas', [
            'nombre' => 'Polar',
            'descripcion' => 'Alimentos y bebidas',
            'activo' => true,
        ]);
    }

    public function test_no_permite_crear_una_marca_duplicada_desde_el_formulario(): void
    {
        Marca::factory()->create(['nombre' => 'Polar']);

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->call('crearMarca')
            ->set('nuevoMarcaNombre', 'Polar')
            ->call('guardarMarca')
            ->assertHasErrors(['nuevoMarcaNombre' => 'unique'])
            ->assertSet('mostrarModalMarca', true);

        $this->assertDatabaseCount('marcas', 1);
    }

    public function test_el_formulario_muestra_el_select_de_marcas_y_el_boton_para_crearlas(): void
    {
        Marca::factory()->create(['nombre' => 'Polar']);

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->assertSee('Marca')
            ->assertSee('Sin marca')
            ->assertSee('Polar')
            ->assertSee('Si la marca no aparece, créala con el botón +.')
            ->assertSee('Agregar nueva marca');
    }

    public function test_muestra_la_marca_en_la_tabla_y_permite_filtrar_por_marca(): void
    {
        $categoria = $this->categoria();
        $polar = Marca::factory()->create(['nombre' => 'Polar']);
        $savoy = Marca::factory()->create(['nombre' => 'Savoy']);

        $this->producto($categoria, ['sku' => 'HAR-001', 'nombre' => 'Harina de maíz', 'marca_id' => $polar->id]);
        $this->producto($categoria, ['sku' => 'CHO-001', 'nombre' => 'Chocolate', 'marca_id' => $savoy->id]);
        $this->producto($categoria, ['sku' => 'ARR-001', 'nombre' => 'Arroz blanco']);

        Livewire::test(GestionProductos::class)
            ->assertSee('Marca')
            ->assertSee('Harina de maíz')
            ->assertSee('Chocolate')
            ->set('marcaFiltro', $polar->id)
            ->assertSee('Harina de maíz')
            ->assertDontSee('Chocolate')
            ->assertDontSee('Arroz blanco')
            ->set('marcaFiltro', 'sin-marca')
            ->assertSee('Arroz blanco')
            ->assertDontSee('Harina de maíz')
            ->assertDontSee('Chocolate');
    }

    public function test_busca_productos_por_nombre_de_marca(): void
    {
        $categoria = $this->categoria();
        $polar = Marca::factory()->create(['nombre' => 'Polar']);
        $savoy = Marca::factory()->create(['nombre' => 'Savoy']);

        $this->producto($categoria, ['sku' => 'HAR-001', 'nombre' => 'Harina de maíz', 'marca_id' => $polar->id]);
        $this->producto($categoria, ['sku' => 'CHO-001', 'nombre' => 'Chocolate', 'marca_id' => $savoy->id]);

        Livewire::test(GestionProductos::class)
            ->set('search', 'Polar')
            ->assertSee('Harina de maíz')
            ->assertDontSee('Chocolate');
    }

    public function test_actualiza_la_marca_de_un_producto_y_permite_dejarlo_sin_marca(): void
    {
        $categoria = $this->categoria();
        $marca = Marca::factory()->create(['nombre' => 'Polar']);
        $producto = $this->producto($categoria, ['marca_id' => $marca->id]);

        Livewire::test(GestionProductos::class)
            ->call('editar', $producto->id)
            ->assertSet('marca_id', $marca->id)
            ->set('marca_id', '')
            ->call('actualizar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'marca_id' => null,
        ]);
    }

    public function test_muestra_el_stock_disponible_sumando_los_lotes_del_producto(): void
    {
        $categoria = $this->categoria();
        $producto = $this->producto($categoria, [
            'sku' => 'PAN-001',
            'nombre' => 'Harina PAN',
            'stock_minimo_alerta' => 10,
        ]);
        $proveedor = $this->proveedor();

        $this->lote($producto, $proveedor, 30, 'LOTE-001');
        $this->lote($producto, $proveedor, 18.5, 'LOTE-002');

        Livewire::test(GestionProductos::class)
            ->assertSee('Stock disponible')
            ->assertSee('Harina PAN')
            ->assertSee('48,50')
            ->assertSee('Kilogramo (kg)');
    }

    public function test_muestra_cero_de_stock_disponible_cuando_el_producto_no_tiene_lotes(): void
    {
        $categoria = $this->categoria();
        $this->producto($categoria, ['sku' => 'KAL-001', 'nombre' => 'Harina Kali']);

        Livewire::test(GestionProductos::class)
            ->assertSee('Harina Kali')
            ->assertSee('0,00');
    }

    public function test_el_stock_disponible_respeta_la_busqueda_y_el_filtro_por_marca(): void
    {
        $categoria = $this->categoria();
        $polar = Marca::factory()->create(['nombre' => 'Polar']);
        $kali = Marca::factory()->create(['nombre' => 'Kali']);

        $harinaPan = $this->producto($categoria, [
            'sku' => 'PAN-001',
            'nombre' => 'Harina PAN',
            'marca_id' => $polar->id,
            'stock_minimo_alerta' => 10,
        ]);
        $harinaKali = $this->producto($categoria, [
            'sku' => 'KAL-001',
            'nombre' => 'Harina Kali',
            'marca_id' => $kali->id,
            'stock_minimo_alerta' => 10,
        ]);

        $proveedor = $this->proveedor();
        $this->lote($harinaPan, $proveedor, 30, 'LOTE-001');
        $this->lote($harinaKali, $proveedor, 12, 'LOTE-002');

        Livewire::test(GestionProductos::class)
            ->set('search', 'Harina')
            ->assertSee('Harina PAN')
            ->assertSee('30,00')
            ->assertSee('Harina Kali')
            ->assertSee('12,00')
            ->set('marcaFiltro', $polar->id)
            ->assertSee('Harina PAN')
            ->assertSee('30,00')
            ->assertDontSee('Harina Kali')
            ->assertDontSee('12,00');
    }

    public function test_marca_el_stock_disponible_por_debajo_del_minimo(): void
    {
        $categoria = $this->categoria();
        $producto = $this->producto($categoria, [
            'sku' => 'PAN-001',
            'nombre' => 'Harina PAN',
            'stock_minimo_alerta' => 20,
        ]);

        $this->lote($producto, $this->proveedor(), 5, 'LOTE-001');

        Livewire::test(GestionProductos::class)
            ->assertSee('Harina PAN')
            ->assertSee('5,00')
            ->assertSee('Bajo mínimo');
    }
}
