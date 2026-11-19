<?php

namespace Tests\Feature;

use App\Livewire\GestionProveedores;
use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GestionProveedoresTest extends TestCase
{
    use RefreshDatabase;

    private function proveedor(string $nombre = 'Distribuidora ABC', string $rif = 'J-12345678-9'): Proveedor
    {
        return Proveedor::create([
            'nombre' => $nombre,
            'rif' => $rif,
        ]);
    }

    public function test_crea_un_proveedor_y_genera_el_codigo(): void
    {
        Livewire::test(GestionProveedores::class)
            ->call('crear')
            ->assertSet('mostrarModal', true)
            ->set('nombre', 'Distribuidora ABC')
            ->set('rif_nit', 'J-12345678-9')
            ->set('telefono', '0414-1234567')
            ->set('correo', 'contacto@abc.com')
            ->set('direccion', 'Av. Principal')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Proveedor registrado correctamente.');

        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Distribuidora ABC',
            'codigo' => 'DIS-001',
            'rif' => 'J-12345678-9',
        ]);
    }

    public function test_no_permite_rif_duplicado(): void
    {
        $this->proveedor();

        Livewire::test(GestionProveedores::class)
            ->call('crear')
            ->set('nombre', 'Otro Proveedor')
            ->set('rif_nit', 'J-12345678-9')
            ->call('guardar')
            ->assertHasErrors(['rif_nit' => 'unique']);

        $this->assertDatabaseCount('proveedores', 1);
    }

    public function test_edita_y_actualiza_un_proveedor(): void
    {
        $proveedor = $this->proveedor();

        Livewire::test(GestionProveedores::class)
            ->call('editar', $proveedor->id)
            ->assertSet('isEditing', true)
            ->assertSet('rif_nit', 'J-12345678-9')
            ->set('nombre', 'Distribuidora XYZ')
            ->set('rif_nit', 'J-98765432-1')
            ->call('actualizar')
            ->assertHasNoErrors()
            ->assertSee('Proveedor actualizado correctamente.');

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre' => 'Distribuidora XYZ',
            'rif' => 'J-98765432-1',
            'codigo' => $proveedor->codigo,
        ]);
    }

    public function test_elimina_un_proveedor_sin_lotes(): void
    {
        $proveedor = $this->proveedor();

        Livewire::test(GestionProveedores::class)
            ->call('eliminar', $proveedor->id)
            ->assertSee('Proveedor eliminado correctamente.');

        $this->assertDatabaseMissing('proveedores', ['id' => $proveedor->id]);
    }

    public function test_no_elimina_un_proveedor_con_lotes(): void
    {
        $categoria = Categoria::create(['nombre' => 'Abarrotes']);
        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => 'HAR-001',
            'nombre' => 'Harina de maíz',
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);
        $proveedor = $this->proveedor();

        Lote::create([
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
            'numero_lote' => 'LOTE-001',
            'stock_inicial' => 10,
            'stock_actual' => 10,
            'fecha_registro' => now()->format('Y-m-d'),
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
        ]);

        Livewire::test(GestionProveedores::class)
            ->call('eliminar', $proveedor->id)
            ->assertSee('No se puede eliminar el proveedor porque tiene lotes asociados.');

        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id]);
    }

    public function test_busca_por_nombre_codigo_o_rif(): void
    {
        $this->proveedor('Distribuidora ABC', 'J-12345678-9');
        $this->proveedor('Comercial XYZ', 'J-98765432-1');

        Livewire::test(GestionProveedores::class)
            ->set('search', 'Distribuidora')
            ->assertSee('Distribuidora ABC')
            ->assertDontSee('Comercial XYZ')
            ->set('search', 'COM-001')
            ->assertSee('Comercial XYZ')
            ->assertDontSee('Distribuidora ABC')
            ->set('search', 'J-98765432-1')
            ->assertSee('Comercial XYZ')
            ->assertDontSee('Distribuidora ABC');
    }
}
