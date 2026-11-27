<?php

namespace Tests\Feature;

use App\Livewire\GestionDespachos;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Despacho;
use App\Models\DespachoDetalle;
use App\Models\Empleado;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\ProgramacionDespacho;
use App\Models\ProgramacionDespachoDetalle;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\User;
use App\Notifications\RecordatorioDespacho;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProgramacionDespachosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-27 08:00:00'));

        $this->actingAs(User::factory()->administrador()->create(['name' => 'Operador']));
    }

    private function usuario(): User
    {
        return User::factory()->create(['name' => 'Operador']);
    }

    private function producto(string $nombre = 'Harina de maíz', string $sku = 'HAR-PROG-001'): Producto
    {
        $categoria = Categoria::query()->firstOrCreate(
            ['nombre' => 'Abarrotes'],
            ['descripcion' => 'Productos de consumo básico'],
        );

        return Producto::create([
            'categoria_id' => $categoria->id,
            'sku' => $sku,
            'nombre' => $nombre,
            'unidad_medida' => 'kg',
            'stock_minimo_alerta' => 5,
        ]);
    }

    private function proveedor(): Proveedor
    {
        return Proveedor::create([
            'nombre' => 'Distribuidora ABC',
            'rif' => 'J-12345678-9',
        ]);
    }

    private function lote(Producto $producto, float $stock = 20): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id,
            'proveedor_id' => $this->proveedor()->id,
            'numero_lote' => 'LOTE-PROG-001',
            'stock_inicial' => $stock,
            'stock_actual' => $stock,
            'fecha_registro' => '2026-11-01',
            'fecha_vencimiento' => '2027-11-01',
        ]);
    }

    private function sede(string $nombre = 'Punto de Consumo Norte'): Sede
    {
        return Sede::create([
            'nombre' => $nombre,
            'tipo' => 'consumidor',
            'empleado_id' => null,
            'direccion' => 'Av. Principal',
            'telefono' => '0212-1234567',
            'activo' => true,
        ]);
    }

    private function despacho(Sede $sede, User $usuario, string $numero = 'DES-2026-11-27-001'): Despacho
    {
        return Despacho::create([
            'numero' => $numero,
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'fecha_despacho' => '2026-11-27',
            'observaciones' => 'Entrega inicial de suministros',
            'estado' => Despacho::ESTADO_REGISTRADO,
        ]);
    }

    private function despachoConProducto(Sede $sede, User $usuario, Producto $producto, float $cantidad = 5): Despacho
    {
        $despacho = $this->despacho($sede, $usuario);

        DespachoDetalle::create([
            'despacho_id' => $despacho->id,
            'producto_id' => $producto->id,
            'lote_id' => null,
            'producto_nombre' => $producto->nombre,
            'numero_lote' => 'LOTE-INI-001',
            'cantidad' => $cantidad,
        ]);

        return $despacho;
    }

    private function programacion(
        User $usuario,
        array $atributos = [],
        array $detalles = [],
    ): ProgramacionDespacho {
        $sede = $this->sede();
        $despacho = $this->despacho($sede, $usuario, 'DES-2026-11-20-001');

        $programacion = ProgramacionDespacho::factory()->create(array_merge([
            'despacho_origen_id' => $despacho->id,
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'cada_dias' => 7,
            'fecha_inicio' => '2026-11-20',
            'proxima_fecha' => '2026-11-27 00:00:00',
            'activa' => true,
        ], $atributos));

        foreach ($detalles as $detalle) {
            ProgramacionDespachoDetalle::factory()->create(array_merge([
                'programacion_despacho_id' => $programacion->id,
            ], $detalle));
        }

        return $programacion;
    }

    private function configurarResponsables(Sede $sede): void
    {
        $jefeAlmacen = Cargo::query()->firstOrCreate(
            ['nombre' => 'Jefe de Almacén'],
            ['activo' => true],
        );
        $jefeCentro = Cargo::query()->firstOrCreate(
            ['nombre' => 'Jefe de Centro'],
            ['activo' => true],
        );

        Empleado::create([
            'cedula' => 'V-10000001',
            'nombres' => 'Juan',
            'apellidos' => 'Pérez',
            'telefono' => null,
            'cargo_id' => $jefeAlmacen->id,
            'activo' => true,
        ]);

        $empleadoCentro = Empleado::create([
            'cedula' => 'V-10000002',
            'nombres' => 'María',
            'apellidos' => 'Gómez',
            'telefono' => null,
            'cargo_id' => $jefeCentro->id,
            'activo' => true,
        ]);

        $sede->update(['empleado_id' => $empleadoCentro->id]);
    }

    public function test_programar_copia_la_sede_y_los_productos_del_despacho_original(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        $sede = $this->sede();
        $producto = $this->producto();
        $despacho = $this->despachoConProducto($sede, $usuario, $producto, 5);

        Livewire::test(GestionDespachos::class)
            ->call('programar', $despacho->id)
            ->assertSet('mostrarModalProgramacion', true)
            ->assertSet('programacionDespachoId', $despacho->id)
            ->assertSet('programacionSedeId', $sede->id)
            ->assertSet('programacionDetalles.0.producto_id', $producto->id)
            ->assertSet('programacionDetalles.0.producto_nombre', 'Harina de maíz')
            ->assertSet('programacionDetalles.0.cantidad', 5.0)
            ->set('programacionCadaDias', 15)
            ->set('programacionFechaInicio', '2026-11-27')
            ->call('guardarProgramacion')
            ->assertHasNoErrors()
            ->assertSet('mostrarModalProgramacion', false);

        $this->assertDatabaseHas('programacion_despachos', [
            'despacho_origen_id' => $despacho->id,
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'cada_dias' => 15,
            'fecha_inicio' => '2026-11-27 00:00:00',
            'proxima_fecha' => '2026-11-27 00:00:00',
            'activa' => true,
        ]);

        $this->assertDatabaseHas('programacion_despacho_detalles', [
            'producto_id' => $producto->id,
            'producto_nombre' => 'Harina de maíz',
            'cantidad' => 5,
        ]);

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'programacion_creada',
            'modelo' => 'programacion_despacho',
            'user_id' => $usuario->id,
        ]);
    }

    public function test_valida_el_periodo_y_la_fecha_de_inicio_de_la_programacion(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $despacho = $this->despachoConProducto($this->sede(), $usuario, $producto);

        Livewire::test(GestionDespachos::class)
            ->call('programar', $despacho->id)
            ->set('programacionCadaDias', 0)
            ->set('programacionFechaInicio', '')
            ->call('guardarProgramacion')
            ->assertHasErrors([
                'programacionCadaDias' => 'min',
                'programacionFechaInicio' => 'required',
            ]);

        $this->assertDatabaseCount('programacion_despachos', 0);
    }

    public function test_el_comando_no_notifica_antes_de_la_fecha_programada(): void
    {
        Notification::fake();

        $usuario = $this->usuario();
        $programacion = $this->programacion($usuario, [
            'proxima_fecha' => '2026-11-28 00:00:00',
        ]);

        Artisan::call('despachos:enviar-recordatorios');

        Notification::assertNothingSent();
        $this->assertDatabaseHas('programacion_despachos', [
            'id' => $programacion->id,
            'proxima_fecha' => '2026-11-28 00:00:00',
            'ultima_notificacion_at' => null,
        ]);
    }

    public function test_el_comando_notifica_y_avanza_la_proxima_fecha(): void
    {
        Notification::fake();

        $usuario = $this->usuario();
        $programacion = $this->programacion($usuario, [
            'proxima_fecha' => '2026-11-27 00:00:00',
        ], [
            ['producto_nombre' => 'Harina de maíz', 'cantidad' => 5],
        ]);

        Artisan::call('despachos:enviar-recordatorios');

        Notification::assertSentTo(
            $usuario,
            RecordatorioDespacho::class,
            function (RecordatorioDespacho $notificacion) use ($usuario, $programacion) {
                $datos = $notificacion->toArray($usuario);

                return $datos['programacion_id'] === $programacion->id
                    && $datos['sede'] === $programacion->sede->nombre
                    && $datos['productos'] === [
                        ['producto' => 'Harina de maíz', 'cantidad' => 5.0],
                    ];
            },
        );

        $this->assertDatabaseHas('programacion_despachos', [
            'id' => $programacion->id,
            'proxima_fecha' => '2026-12-04 00:00:00',
            'ultima_notificacion_at' => '2026-11-27 08:00:00',
            'activa' => true,
        ]);

        $this->assertDatabaseHas('auditorias', [
            'accion' => 'recordatorio_despacho_enviado',
            'modelo' => 'programacion_despacho',
            'modelo_id' => $programacion->id,
        ]);
    }

    public function test_una_segunda_ejecucion_no_duplica_el_recordatorio(): void
    {
        Notification::fake();

        $usuario = $this->usuario();
        $this->programacion($usuario);

        Artisan::call('despachos:enviar-recordatorios');
        Artisan::call('despachos:enviar-recordatorios');

        Notification::assertSentToTimes($usuario, RecordatorioDespacho::class, 1);
    }

    public function test_una_programacion_pausada_no_genera_recordatorio(): void
    {
        Notification::fake();

        $usuario = $this->usuario();
        $this->programacion($usuario, ['activa' => false]);

        Artisan::call('despachos:enviar-recordatorios');

        Notification::assertNothingSent();
    }

    public function test_la_campana_muestra_las_no_leidas_y_el_borrador_no_descuenta_stock(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $lote = $this->lote($producto, 20);
        $programacion = $this->programacion($usuario, [
            'sede_id' => $this->sede('Sede Alerta')->id,
        ], [
            ['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre, 'cantidad' => 5],
        ]);

        Artisan::call('despachos:enviar-recordatorios');

        $respuesta = $this->getJson(route('notificaciones.campana'))
            ->assertOk()
            ->assertJsonPath('label', '1')
            ->assertJsonPath('label_color', 'danger');

        $this->assertStringContainsString('Preparar envío a Sede Alerta', $respuesta->json('dropdown'));

        $this->post(route('notificaciones.preparar', $programacion->id))
            ->assertRedirect(route('despachos.index', ['programacion' => $programacion->id]));

        $this->assertSame(0, $usuario->fresh()->unreadNotifications()->count());

        Livewire::withQueryParams(['programacion' => $programacion->id])
            ->test(GestionDespachos::class)
            ->assertSet('mostrarModal', true)
            ->assertSet('sede_id', $programacion->sede_id)
            ->assertSet('items.0.lote_id', $lote->id)
            ->assertSet('items.0.cantidad', 5.0);

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'stock_actual' => 20,
        ]);
    }

    public function test_confirmar_el_borrador_registra_el_despacho_y_descuenta_el_stock(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        $producto = $this->producto();
        $lote = $this->lote($producto, 20);
        $sede = $this->sede('Sede Alerta');
        $this->configurarResponsables($sede);

        $programacion = $this->programacion($usuario, [
            'sede_id' => $sede->id,
        ], [
            ['producto_id' => $producto->id, 'producto_nombre' => $producto->nombre, 'cantidad' => 5],
        ]);

        Livewire::withQueryParams(['programacion' => $programacion->id])
            ->test(GestionDespachos::class)
            ->assertSet('items.0.lote_id', $lote->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('lotes', [
            'id' => $lote->id,
            'stock_actual' => 15,
        ]);

        $this->assertDatabaseHas('despacho_detalles', [
            'producto_id' => $producto->id,
            'lote_id' => $lote->id,
            'cantidad' => 5,
        ]);

        $this->assertSame(1, Despacho::query()->where('sede_id', $sede->id)->count());
    }

    public function test_anular_el_despacho_origen_pausa_sus_programaciones(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        $sede = $this->sede();
        $despacho = $this->despacho($sede, $usuario);
        $programacion = ProgramacionDespacho::factory()->create([
            'despacho_origen_id' => $despacho->id,
            'sede_id' => $sede->id,
            'user_id' => $usuario->id,
            'activa' => true,
        ]);

        Livewire::test(GestionDespachos::class)
            ->call('anular', $despacho->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('programacion_despachos', [
            'id' => $programacion->id,
            'activa' => false,
        ]);
    }
}
