<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Bien;
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
}
