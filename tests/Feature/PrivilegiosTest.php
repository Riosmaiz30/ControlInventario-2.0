<?php

namespace Tests\Feature;

use App\Livewire\GestionDespachos;
use App\Livewire\GestionLotes;
use App\Livewire\GestionProductos;
use App\Livewire\GestionUsuarios;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Despacho;
use App\Models\DespachoDetalle;
use App\Models\Empleado;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PrivilegiosTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function rutasPermitidas(): array
    {
        return [
            'home' => route('home'),
            'productos.index' => route('productos.index'),
            'lotes.index' => route('lotes.index'),
            'proveedores.index' => route('proveedores.index'),
            'categorias.index' => route('categorias.index'),
            'marcas.index' => route('marcas.index'),
            'empleados.index' => route('empleados.index'),
            'cargos.index' => route('cargos.index'),
            'sedes.index' => route('sedes.index'),
            'usuarios.index' => route('usuarios.index'),
            'despachos.index' => route('despachos.index'),
            'auditoria.index' => route('auditoria.index'),
        ];
    }

    private function producto(string $sku = 'HAR-001'): Producto
    {
        $categoria = Categoria::create([
            'nombre' => 'Abarrotes '.$sku,
            'descripcion' => null,
        ]);

        return Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => $sku,
            'nombre' => 'Harina de maíz '.$sku,
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);
    }

    private function proveedor(): Proveedor
    {
        return Proveedor::create([
            'nombre' => 'Distribuidora ABC',
            'rif' => 'J-'.fake()->randomNumber(8).'-9',
        ]);
    }

    private function sede(): Sede
    {
        return Sede::create([
            'nombre' => 'Punto de Consumo Norte',
            'tipo' => 'consumidor',
            'empleado_id' => null,
            'direccion' => 'Av. Principal',
            'telefono' => null,
            'activo' => true,
        ]);
    }

    private function lote(Producto $producto, Proveedor $proveedor, float $stock = 20): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id,
            'proveedor_id' => $proveedor->id,
            'numero_lote' => 'LOTE-'.fake()->unique()->randomNumber(5),
            'stock_inicial' => $stock,
            'stock_actual' => $stock,
            'fecha_registro' => now()->format('Y-m-d'),
            'fecha_vencimiento' => now()->addYear()->format('Y-m-d'),
        ]);
    }

    private function despacho(Sede $sede, User $usuario): Despacho
    {
        return Despacho::create([
            'numero' => 'DES-2026-11-27-001',
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'fecha_despacho' => '2026-11-27',
            'observaciones' => null,
            'estado' => Despacho::ESTADO_REGISTRADO,
        ]);
    }

    private function responsables(Sede $sede): void
    {
        $cargoAlmacen = Cargo::query()->firstOrCreate(
            ['nombre' => 'Jefe de Almacén'],
            ['activo' => true]
        );
        $cargoCentro = Cargo::query()->firstOrCreate(
            ['nombre' => 'Jefe de Centro'],
            ['activo' => true]
        );

        Empleado::create([
            'cedula' => 'V-10000001',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'telefono' => null,
            'cargo_id' => $cargoAlmacen->id,
            'activo' => true,
        ]);

        $jefeCentro = Empleado::create([
            'cedula' => 'V-10000002',
            'nombres' => 'María',
            'apellidos' => 'Gómez',
            'telefono' => null,
            'cargo_id' => $cargoCentro->id,
            'activo' => true,
        ]);

        $sede->update(['empleado_id' => $jefeCentro->id]);
    }

    public function test_el_administrador_puede_acceder_a_todos_los_modulos(): void
    {
        $this->actingAs(User::factory()->administrador()->create());

        foreach ($this->rutasPermitidas() as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_el_jefe_de_almacen_solo_accede_a_modulos_de_almacen(): void
    {
        $this->actingAs(User::factory()->jefeAlmacen()->create());

        foreach ([
            'productos.index',
            'lotes.index',
            'proveedores.index',
            'categorias.index',
            'marcas.index',
            'despachos.index',
            'notificaciones.campana',
        ] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }

        foreach ([
            'empleados.index',
            'cargos.index',
            'sedes.index',
            'usuarios.index',
            'auditoria.index',
        ] as $ruta) {
            $this->get(route($ruta))->assertForbidden();
        }
    }

    public function test_el_ayudante_solo_accede_a_productos_lotes_despachos_y_notificaciones(): void
    {
        $this->actingAs(User::factory()->ayudanteAlmacen()->create());

        foreach ([
            'productos.index',
            'lotes.index',
            'despachos.index',
            'notificaciones.campana',
        ] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }

        foreach ([
            'proveedores.index',
            'categorias.index',
            'marcas.index',
            'empleados.index',
            'cargos.index',
            'sedes.index',
            'usuarios.index',
            'auditoria.index',
        ] as $ruta) {
            $this->get(route($ruta))->assertForbidden();
        }
    }

    public function test_el_menu_del_jefe_oculta_organizacion_usuarios_y_auditoria(): void
    {
        $this->actingAs(User::factory()->jefeAlmacen()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Catálogos')
            ->assertSee('Inventario Central')
            ->assertSee('Despachos a Sedes')
            ->assertDontSee(route('empleados.index'), false)
            ->assertDontSee(route('usuarios.index'), false)
            ->assertDontSee(route('auditoria.index'), false);
    }

    public function test_el_menu_del_ayudante_oculta_catalogos_y_accesos_restringidos(): void
    {
        $this->actingAs(User::factory()->ayudanteAlmacen()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Inventario Central')
            ->assertSee('Despachos a Sedes')
            ->assertDontSee('Catálogos')
            ->assertDontSee(route('empleados.index'), false)
            ->assertDontSee(route('usuarios.index'), false)
            ->assertDontSee(route('auditoria.index'), false);
    }

    public function test_el_administrador_ve_todos_los_accesos_del_menu(): void
    {
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Catálogos')
            ->assertSee('Inventario Central')
            ->assertSee('Organización')
            ->assertSee('Usuarios')
            ->assertSee('Despachos a Sedes')
            ->assertSee('Auditoría del Sistema');
    }

    public function test_el_ayudante_puede_confirmar_un_despacho_y_descuenta_el_stock(): void
    {
        $usuario = User::factory()->ayudanteAlmacen()->create();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $proveedor = $this->proveedor();
        $sede = $this->sede();
        $this->responsables($sede);
        $lote = $this->lote($producto, $proveedor, 20);

        Livewire::test(GestionDespachos::class)
            ->call('crear')
            ->set('sede_id', $sede->id)
            ->set('fecha_despacho', '2026-11-27')
            ->set('items', [
                ['lote_id' => $lote->id, 'cantidad' => 4],
            ])
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Despacho registrado correctamente.')
            ->assertDispatched('descargar-nota-entrega');

        $this->assertDatabaseHas('despachos', [
            'user_id' => $usuario->id,
            'sede_id' => $sede->id,
            'estado' => Despacho::ESTADO_REGISTRADO,
        ]);

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'stock_actual' => 16,
        ]);
    }

    public function test_el_ayudante_no_puede_programar_despachos(): void
    {
        $usuario = User::factory()->ayudanteAlmacen()->create();
        $this->actingAs($usuario);

        $despacho = $this->despacho($this->sede(), $usuario);

        Livewire::test(GestionDespachos::class)
            ->call('programar', $despacho->id)
            ->assertStatus(403);
    }

    public function test_el_ayudante_no_puede_anular_despachos(): void
    {
        $usuario = User::factory()->ayudanteAlmacen()->create();
        $this->actingAs($usuario);

        $despacho = $this->despacho($this->sede(), $usuario);

        Livewire::test(GestionDespachos::class)
            ->call('anular', $despacho->id)
            ->assertStatus(403);
    }

    public function test_el_jefe_puede_programar_y_anular_un_despacho(): void
    {
        $usuario = User::factory()->jefeAlmacen()->create();
        $this->actingAs($usuario);

        $sede = $this->sede();
        $despacho = $this->despacho($sede, $usuario);
        $producto = $this->producto();
        $lote = $this->lote($producto, $this->proveedor(), 20);

        DespachoDetalle::create([
            'despacho_id' => $despacho->id,
            'producto_id' => $producto->id,
            'lote_id' => $lote->id,
            'producto_nombre' => $producto->nombre,
            'numero_lote' => $lote->numero_lote,
            'cantidad' => 4,
        ]);

        Livewire::test(GestionDespachos::class)
            ->call('programar', $despacho->id)
            ->assertSet('mostrarModalProgramacion', true)
            ->assertSet('programacionDespachoId', $despacho->id);

        Livewire::test(GestionDespachos::class)
            ->call('anular', $despacho->id)
            ->assertSee('Despacho anulado y stock restaurado correctamente.');

        $this->assertDatabaseHas('despachos', [
            'id' => $despacho->id,
            'estado' => Despacho::ESTADO_ANULADO,
        ]);
    }

    public function test_el_ayudante_no_puede_gestionar_productos(): void
    {
        $this->actingAs(User::factory()->ayudanteAlmacen()->create());

        Livewire::test(GestionProductos::class)
            ->call('crear')
            ->assertStatus(403);
    }

    public function test_el_ayudante_puede_registrar_lotes_pero_no_modificarlos(): void
    {
        $this->actingAs(User::factory()->ayudanteAlmacen()->create());

        Livewire::test(GestionLotes::class)
            ->call('crear')
            ->assertSet('mostrarModal', true);

        Livewire::test(GestionLotes::class)
            ->call('solicitarEdicion', 1, 'clave')
            ->assertStatus(403);
    }

    public function test_el_registro_publico_crea_usuarios_como_ayudante(): void
    {
        $this->post('/register', [
            'name' => 'Nuevo Operador',
            'email' => 'operador@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'operador@example.com',
            'role' => User::ROLE_AYUDANTE_ALMACEN,
        ]);
    }

    public function test_solo_el_administrador_puede_crear_usuarios_con_cualquier_rol(): void
    {
        $this->actingAs(User::factory()->administrador()->create());

        Livewire::test(GestionUsuarios::class)
            ->call('crear')
            ->set('name', 'Jefe de Prueba')
            ->set('email', 'jefe-prueba@example.com')
            ->set('role', User::ROLE_JEFE_ALMACEN)
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Usuario registrado correctamente.');

        $this->assertDatabaseHas('users', [
            'email' => 'jefe-prueba@example.com',
            'role' => User::ROLE_JEFE_ALMACEN,
        ]);
    }

    public function test_no_se_puede_degradar_al_ultimo_administrador(): void
    {
        $administrador = User::factory()->administrador()->create();
        $this->actingAs($administrador);

        Livewire::test(GestionUsuarios::class)
            ->call('editar', $administrador->id)
            ->set('role', User::ROLE_AYUDANTE_ALMACEN)
            ->call('actualizar')
            ->assertSee('Debe existir al menos un administrador en el sistema.');

        $this->assertDatabaseHas('users', [
            'id' => $administrador->id,
            'role' => User::ROLE_ADMINISTRADOR,
        ]);
    }

    public function test_el_jefe_no_puede_gestionar_usuarios(): void
    {
        $this->actingAs(User::factory()->jefeAlmacen()->create());

        Livewire::test(GestionUsuarios::class)
            ->call('crear')
            ->assertStatus(403);
    }
}
