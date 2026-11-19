<?php

namespace Tests\Feature;

use App\Livewire\GestionAuditoria;
use App\Models\Auditoria;
use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GestionAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_establece_la_clave_cuando_no_existe(): void
    {
        Livewire::test(GestionAuditoria::class)
            ->assertSee('Sin configurar')
            ->set('nueva_clave', 'secreta123')
            ->set('nueva_clave_confirmation', 'secreta123')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('Clave de administrador actualizada correctamente.')
            ->assertSee('Configurada');

        $this->assertDatabaseHas('configuraciones', [
            'clave' => Configuracion::CLAVE_ADMINISTRADOR,
        ]);

        $this->assertTrue(Hash::check(
            'secreta123',
            Configuracion::obtener(Configuracion::CLAVE_ADMINISTRADOR),
        ));
    }

    public function test_exige_la_clave_actual_para_cambiarla(): void
    {
        Configuracion::establecer(
            Configuracion::CLAVE_ADMINISTRADOR,
            Hash::make('vieja123'),
        );

        Livewire::test(GestionAuditoria::class)
            ->set('clave_actual', 'incorrecta')
            ->set('nueva_clave', 'nueva123')
            ->set('nueva_clave_confirmation', 'nueva123')
            ->call('guardar')
            ->assertHasErrors(['clave_actual']);

        $this->assertTrue(Hash::check(
            'vieja123',
            Configuracion::obtener(Configuracion::CLAVE_ADMINISTRADOR),
        ));

        Livewire::test(GestionAuditoria::class)
            ->set('clave_actual', 'vieja123')
            ->set('nueva_clave', 'nueva123')
            ->set('nueva_clave_confirmation', 'nueva123')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check(
            'nueva123',
            Configuracion::obtener(Configuracion::CLAVE_ADMINISTRADOR),
        ));
    }

    public function test_valida_la_longitud_y_la_confirmacion_de_la_clave(): void
    {
        Livewire::test(GestionAuditoria::class)
            ->set('nueva_clave', '123')
            ->set('nueva_clave_confirmation', '123')
            ->call('guardar')
            ->assertHasErrors(['nueva_clave' => 'min']);

        Livewire::test(GestionAuditoria::class)
            ->set('nueva_clave', 'secreta123')
            ->set('nueva_clave_confirmation', 'otra123')
            ->call('guardar')
            ->assertHasErrors(['nueva_clave' => 'confirmed']);
    }

    public function test_muestra_los_ultimos_cambios_de_lotes(): void
    {
        $usuario = User::factory()->create(['name' => 'Administrador']);

        Auditoria::create([
            'user_id' => $usuario->id,
            'accion' => 'lote_actualizado',
            'modelo' => 'lote',
            'modelo_id' => 1,
            'descripcion' => 'Lote Dis-abc-2026-11-18-001 actualizado',
            'datos_anteriores' => ['numero_lote' => 'Dis-abc-2026-11-18-001'],
            'datos_nuevos' => ['numero_lote' => 'Dis-abc-2026-11-18-001'],
        ]);

        Livewire::test(GestionAuditoria::class)
            ->assertSee('Últimos cambios en lotes')
            ->assertSee('Lote actualizado')
            ->assertSee('Administrador')
            ->assertSee('Lote Dis-abc-2026-11-18-001 actualizado');
    }
}
