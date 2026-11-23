<?php

namespace Tests\Feature;

use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create();
    }

    public function test_el_panel_usa_la_estructura_de_adminlte(): void
    {
        $response = $this->actingAs($this->usuario())->get(route('home'));

        $response->assertOk()
            ->assertSee('app-header', false)
            ->assertSee('app-sidebar', false)
            ->assertSee('app-main', false)
            ->assertSee('app-footer', false)
            ->assertSee('Dashboard / Inicio')
            ->assertSee('Catálogos')
            ->assertSee('Inventario Central')
            ->assertSee('Organización')
            ->assertSee('Operaciones')
            ->assertSee('Despachos a Sedes')
            ->assertSee('nav-badge badge text-bg-info', false)
            ->assertSee(asset('vendor/fontawesome/css/all.min.css'), false)
            ->assertSee('fas fa-gauge-high', false)
            ->assertSee('fas fa-truck', false)
            ->assertSee('fas fa-boxes-stacked', false)
            ->assertSee('fas fa-paper-plane', false)
            ->assertSee('id="menu-sedes"', false)
            ->assertSee("window.addEventListener('sedes-actualizadas'", false)
            ->assertSee('#menu-sedes .nav-badge', false);
    }

    public function test_el_badge_de_sedes_muestra_el_total_real_de_la_base_de_datos(): void
    {
        Sede::create([
            'nombre' => 'Sede de prueba 1',
            'tipo' => 'principal',
            'direccion' => 'Dirección de prueba 1',
            'telefono' => '',
            'activo' => true,
        ]);

        Sede::create([
            'nombre' => 'Sede de prueba 2',
            'tipo' => 'consumidor',
            'direccion' => 'Dirección de prueba 2',
            'telefono' => '',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->usuario())->get(route('home'));

        $response->assertOk();

        $this->assertMatchesRegularExpression(
            '/nav-badge badge text-bg-info[^>]*>\s*2\s*<\/span>/',
            $response->getContent()
        );
    }

    public function test_los_botones_de_eliminar_usan_sweetalert_local(): void
    {
        Sede::create([
            'nombre' => 'Sede para eliminar',
            'tipo' => 'consumidor',
            'direccion' => 'Dirección de prueba',
            'telefono' => '',
            'activo' => true,
        ]);

        $response = $this->actingAs($this->usuario())->get(route('sedes.index'));

        $response->assertOk()
            ->assertSee(asset('vendor/sweetalert2/sweetalert2.min.css'), false)
            ->assertSee(asset('vendor/sweetalert2/sweetalert2.min.js'), false)
            ->assertSee('data-confirm-delete', false)
            ->assertSee('data-confirm-id="', false)
            ->assertSee('data-confirm-entity="esta sede"', false)
            ->assertSee('Swal.fire', false);
    }

    public function test_el_menu_lateral_enlaza_todas_las_rutas_del_sistema(): void
    {
        $response = $this->actingAs($this->usuario())->get(route('home'));

        foreach ([
            'home',
            'proveedores.index',
            'categorias.index',
            'productos.index',
            'lotes.index',
            'empleados.index',
            'sedes.index',
            'despachos.index',
        ] as $ruta) {
            $response->assertSee(route($ruta), false);
        }
    }

    public function test_cada_modulo_se_renderiza_dentro_del_layout_adminlte(): void
    {
        $usuario = $this->usuario();

        $modulos = [
            'productos.index' => 'Gestión de Productos',
            'lotes.index' => 'Entradas de Mercancía',
            'proveedores.index' => 'Gestión de Proveedores',
            'categorias.index' => 'Gestión de Categorías',
            'empleados.index' => 'Gestión de Empleados',
            'sedes.index' => 'Gestión de Sedes',
            'despachos.index' => 'Despachos a Sedes',
        ];

        foreach ($modulos as $ruta => $contenido) {
            $this->actingAs($usuario)
                ->get(route($ruta))
                ->assertOk()
                ->assertSee($contenido)
                ->assertSee('app-sidebar', false)
                ->assertSee('app-footer', false)
                ->assertSee('nav-link active', false);
        }
    }

    public function test_el_panel_requiere_autenticacion(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_el_login_redirige_directamente_al_panel(): void
    {
        $usuario = $this->usuario();

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_usuario_autenticado_que_entra_a_la_raiz_es_enviado_al_panel(): void
    {
        $this->actingAs($this->usuario())
            ->get('/')
            ->assertRedirect(route('home'));
    }
}
