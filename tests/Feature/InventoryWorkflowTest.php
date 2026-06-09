<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Bien;
use App\Models\ParametroSistema;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_admin_routes_require_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_visualizador_cannot_create_bienes(): void
    {
        $user = User::factory()->create(['role' => 'visualizador']);

        $this->actingAs($user)
            ->post(route('admin.bienes.store'), [
                'no_inventario' => 'INV-001',
                'nombre_bien' => 'Laptop',
                'estatus' => 'Disponible',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_render_main_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            'admin.dashboard',
            'admin.bienes',
            'admin.personal',
            'admin.areas',
            'admin.asignaciones',
            'admin.historial',
            'admin.reportes',
            'admin.pendientes',
            'admin.configuracion',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }
    }

    public function test_admin_can_register_assignment_and_history(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::create([
            'nombre_area' => 'Sistemas',
            'estatus' => 'Activa',
            'fecha_registro' => now(),
        ]);
        $personal = Personal::create([
            'nombre' => 'Ada',
            'apellido_paterno' => 'Lovelace',
            'puesto' => 'Docente',
            'id_area' => $area->id_area,
            'estatus' => 'Activo',
            'fecha_registro' => now(),
        ]);
        $bien = Bien::create([
            'no_inventario' => 'INV-002',
            'nombre_bien' => 'Proyector',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.asignaciones.store'), [
                'id_bien' => $bien->id_bien,
                'id_personal_nuevo' => $personal->id_personal,
                'id_area_nueva' => $area->id_area,
                'tipo_movimiento' => 'Asignacion',
                'observaciones' => 'Asignacion inicial',
            ])
            ->assertRedirect(route('admin.asignaciones'));

        $this->assertDatabaseHas('bienes', [
            'id_bien' => $bien->id_bien,
            'id_personal' => $personal->id_personal,
            'id_area' => $area->id_area,
            'estatus' => 'Asignado',
        ]);

        $this->assertDatabaseHas('historial_asignaciones', [
            'id_bien' => $bien->id_bien,
            'id_personal_nuevo' => $personal->id_personal,
            'id_area_nueva' => $area->id_area,
            'tipo_movimiento' => 'Asignacion',
        ]);
    }

    public function test_assignment_update_uses_route_bien_instead_of_submitted_id(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::create([
            'nombre_area' => 'Biblioteca',
            'estatus' => 'Activa',
            'fecha_registro' => now(),
        ]);
        $personal = Personal::create([
            'nombre' => 'Grace',
            'apellido_paterno' => 'Hopper',
            'puesto' => 'Coordinadora',
            'id_area' => $area->id_area,
            'estatus' => 'Activo',
            'fecha_registro' => now(),
        ]);
        $bienA = Bien::create([
            'no_inventario' => 'INV-003',
            'nombre_bien' => 'Impresora',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);
        $bienB = Bien::create([
            'no_inventario' => 'INV-004',
            'nombre_bien' => 'Monitor',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.asignaciones.update', $bienA), [
                'id_bien' => $bienB->id_bien,
                'id_personal_nuevo' => $personal->id_personal,
                'id_area_nueva' => $area->id_area,
                'tipo_movimiento' => 'Transferencia',
            ])
            ->assertRedirect(route('admin.asignaciones'));

        $this->assertDatabaseHas('bienes', [
            'id_bien' => $bienA->id_bien,
            'id_personal' => $personal->id_personal,
            'id_area' => $area->id_area,
        ]);

        $this->assertDatabaseHas('bienes', [
            'id_bien' => $bienB->id_bien,
            'id_personal' => null,
            'id_area' => null,
        ]);
    }

    public function test_assignment_requires_destination_when_bien_has_none(): void
    {
        $admin = User::factory()->admin()->create();
        $bien = Bien::create([
            'no_inventario' => 'INV-005',
            'nombre_bien' => 'Bocina',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.asignaciones'))
            ->post(route('admin.asignaciones.store'), [
                'id_bien' => $bien->id_bien,
                'tipo_movimiento' => 'Asignacion',
            ])
            ->assertRedirect(route('admin.asignaciones'))
            ->assertSessionHasErrors('id_personal_nuevo');

        $this->assertDatabaseMissing('historial_asignaciones', [
            'id_bien' => $bien->id_bien,
        ]);
    }

    public function test_admin_can_create_bien_with_codigo_barras(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.bienes.store'), [
                'no_inventario' => 'INV-BAR-001',
                'nombre_bien' => 'Escritorio',
                'codigo_barras' => 'ABC123XYZ',
                'estatus' => 'Disponible',
            ])
            ->assertRedirect(route('admin.bienes'));

        $this->assertDatabaseHas('bienes', [
            'no_inventario' => 'INV-BAR-001',
            'codigo_barras' => 'ABC123XYZ',
        ]);
    }

    public function test_admin_can_create_personal(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.personal.store'), [
                'nombre' => 'Margaret',
                'apellido_paterno' => 'Hamilton',
                'puesto' => 'Ingeniera',
                'correo' => 'margaret@example.com',
                'telefono' => '555-0100',
                'estatus' => 'Activo',
            ])
            ->assertRedirect(route('admin.personal'));

        $this->assertDatabaseHas('personal', [
            'nombre' => 'Margaret',
            'apellido_paterno' => 'Hamilton',
            'puesto' => 'Ingeniera',
        ]);
    }

    public function test_admin_can_create_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.areas.store'), [
                'nombre_area' => 'Laboratorio',
                'descripcion' => 'Laboratorio de computo',
                'estatus' => 'Activa',
            ])
            ->assertRedirect(route('admin.areas'));

        $this->assertDatabaseHas('areas', [
            'nombre_area' => 'Laboratorio',
            'descripcion' => 'Laboratorio de computo',
        ]);
    }

    public function test_admin_can_update_parametros(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.configuracion.parametros'), [
                'institucion_nombre' => 'Escuela Test',
                'inventario_prefijo' => 'ESC-',
                'numeracion_automatica' => '1',
            ])
            ->assertRedirect(route('admin.configuracion'));

        $this->assertDatabaseHas('parametros_sistema', [
            'clave' => 'institucion_nombre',
            'valor' => 'Escuela Test',
        ]);
        $this->assertDatabaseHas('parametros_sistema', [
            'clave' => 'inventario_prefijo',
            'valor' => 'ESC-',
        ]);
        $this->assertDatabaseHas('parametros_sistema', [
            'clave' => 'numeracion_automatica',
            'valor' => '1',
        ]);
    }

    public function test_historial_page_loads_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::create(['nombre_area' => 'Test', 'estatus' => 'Activa', 'fecha_registro' => now()]);
        $personal = Personal::create(['nombre' => 'John', 'apellido_paterno' => 'Doe', 'puesto' => 'Test', 'estatus' => 'Activo', 'fecha_registro' => now()]);
        $bien = Bien::create(['no_inventario' => 'INV-HIST', 'nombre_bien' => 'Historial Test', 'estatus' => 'Disponible', 'fecha_registro' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.historial', ['search' => 'Historial', 'tipo' => 'Asignacion', 'fecha_inicio' => '2024-01-01', 'fecha_fin' => '2026-12-31']))
            ->assertOk();
    }

    public function test_historial_csv_export(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.historial.export', 'csv'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_reportes_export_csv(): void
    {
        $admin = User::factory()->admin()->create();
        $bien = Bien::create(['no_inventario' => 'INV-RPT', 'nombre_bien' => 'Reporte Test', 'estatus' => 'Disponible', 'fecha_registro' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.reportes.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_search_bien_by_codigo_barras(): void
    {
        $admin = User::factory()->admin()->create();
        Bien::create(['no_inventario' => 'INV-CB', 'nombre_bien' => 'Codigo Test', 'codigo_barras' => 'COD-999', 'estatus' => 'Disponible', 'fecha_registro' => now()]);

        $response = $this->actingAs($admin)
            ->get(route('admin.bienes', ['search' => 'COD-999']))
            ->assertOk();

        $response->assertSee('COD-999');
        $response->assertSee('Codigo Test');
    }
}
