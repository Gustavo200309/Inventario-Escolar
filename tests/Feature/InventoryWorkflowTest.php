<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Bien;
use App\Models\ParametroSistema;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'admin.usuarios',
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
                'nombre_bien' => 'Escritorio',
                'codigo_barras' => 'ABC123XYZ',
                'estatus' => 'Disponible',
            ])
            ->assertRedirect(route('admin.bienes'));

        $this->assertDatabaseHas('bienes', [
            'codigo_barras' => 'ABC123XYZ',
        ]);
    }

    public function test_admin_cannot_update_bien_identifiers(): void
    {
        $admin = User::factory()->admin()->create();
        $bien = Bien::create([
            'id_sep' => 'SEP-LOCK1',
            'no_inventario' => 'INV-LOCK1',
            'nombre_bien' => 'Mesa',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bienes.update', $bien), [
                'id_sep' => 'SEP-EDIT1',
                'no_inventario' => 'INV-EDIT1',
                'nombre_bien' => 'Mesa actualizada',
                'estatus' => 'Baja',
            ])
            ->assertRedirect(route('admin.bienes'));

        $bien->refresh();

        $this->assertSame('SEP-LOCK1', $bien->id_sep);
        $this->assertSame('INV-LOCK1', $bien->no_inventario);
        $this->assertSame('Mesa actualizada', $bien->nombre_bien);
        $this->assertSame('Baja', $bien->estatus);
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

    public function test_reportes_pdf_omits_unnecessary_report_data(): void
    {
        $admin = User::factory()->admin()->create();
        Bien::create([
            'id_sep' => 'SEP-PDF1',
            'no_inventario' => 'INV-PDF1',
            'nombre_bien' => 'Reporte PDF',
            'codigo_barras' => 'QR-PDF1',
            'valor' => 1500,
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reportes.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $content = $response->getContent();

        $this->assertStringContainsString('Reporte de Inventario', $content);
        $this->assertStringNotContainsString('Inventario general', $content);
        $this->assertStringNotContainsString('Codigo QR', $content);
        $this->assertStringNotContainsString('Valor', $content);
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

    private function filaCsv(string $idSep = '', string $nombre = '', string $marca = '', string $modelo = '', string $serie = '', string $codigo = '', string $idArea = '', string $idPersonal = '', string $estatus = 'Disponible'): string
    {
        return implode(',', [$idSep, $nombre, $marca, $modelo, $serie, $codigo, $idArea, $idPersonal, $estatus]);
    }

    private function importarCsv(array $filas, array $columnas = ['id_sep', 'nombre_bien', 'marca', 'modelo', 'serie', 'codigo_barras', 'id_area', 'id_personal', 'estatus']): \Illuminate\Testing\TestResponse
    {
        $admin = User::factory()->admin()->create();
        $csv = implode(',', $columnas) . "\n" . implode("\n", $filas);

        $archivo = UploadedFile::fake()->createWithContent('bienes.csv', $csv);

        return $this->actingAs($admin)
            ->post(route('admin.bienes.import'), ['archivo' => $archivo]);
    }

    public function test_import_csv_creates_unique_bienes(): void
    {
        $this->importarCsv([
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-001'),
            $this->filaCsv(nombre: 'Computadora B', serie: 'SN-002'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
    }

    public function test_import_csv_allows_duplicate_nombre_bien_in_file(): void
    {
        $this->importarCsv([
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-001'),
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-002'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
        $this->assertSame(2, Bien::where('nombre_bien', 'Computadora A')->count());
    }

    public function test_import_csv_allows_duplicate_nombre_bien_ignoring_accents_and_case(): void
    {
        $this->importarCsv([
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-001'),
            $this->filaCsv(nombre: 'computadora a', serie: 'SN-002'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
    }

    public function test_import_csv_rejects_duplicate_id_sep_in_file(): void
    {
        $this->importarCsv([
            $this->filaCsv(idSep: 'SEP-001', nombre: 'Computadora A', serie: 'SN-001'),
            $this->filaCsv(idSep: 'SEP-001', nombre: 'Computadora B', serie: 'SN-002'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(1, Bien::count());
    }

    public function test_import_csv_allows_duplicate_serie_in_file(): void
    {
        $this->importarCsv([
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-001'),
            $this->filaCsv(nombre: 'Computadora B', serie: 'SN-001'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
    }

    public function test_import_csv_rejects_duplicate_codigo_barras_in_file(): void
    {
        $this->importarCsv([
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-001', codigo: 'COD-111'),
            $this->filaCsv(nombre: 'Computadora B', serie: 'SN-002', codigo: 'COD-111'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(1, Bien::count());
    }

    public function test_import_csv_allows_bien_that_already_exists_in_database(): void
    {
        Bien::create(['no_inventario' => 'INV-EXIST', 'nombre_bien' => 'Laptop Existente', 'serie' => 'SN-X', 'estatus' => 'Disponible', 'fecha_registro' => now()]);

        $this->importarCsv([
            $this->filaCsv(nombre: 'Laptop Existente', serie: 'SN-NUEVA'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
    }

    public function test_import_csv_generates_no_inventario_avoiding_file_collisions(): void
    {
        $this->importarCsv([
            '116100018I1800002341204ADLCSG,,Computadora A,,,SN-001,,,,Disponible',
            ',,Computadora B,,,SN-002,,,,Disponible',
        ], ['no_inventario', 'id_sep', 'nombre_bien', 'marca', 'modelo', 'serie', 'codigo_barras', 'id_area', 'id_personal', 'estatus'])
            ->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
        $this->assertDatabaseHas('bienes', ['no_inventario' => '116100018I1800002341204ADLCSG', 'nombre_bien' => 'Computadora A']);
        $this->assertDatabaseHas('bienes', ['no_inventario' => 'INV-00001', 'nombre_bien' => 'Computadora B']);
    }

    public function test_papelera_can_restore_bien(): void
    {
        $admin = User::factory()->admin()->create();
        $bien = Bien::create(['no_inventario' => 'INV-REST', 'nombre_bien' => 'Restaurar', 'estatus' => 'Disponible', 'fecha_registro' => now()]);
        $bien->delete();
        $this->assertTrue($bien->fresh()->eliminado);

        $this->actingAs($admin)
            ->put(route('admin.bienes.restaurar', $bien))
            ->assertRedirect(route('admin.bienes.papelera'))
            ->assertSessionHas('success');

        $this->assertFalse($bien->fresh()->eliminado);
    }

    public function test_papelera_can_force_destroy_bien(): void
    {
        $admin = User::factory()->admin()->create();
        $bien = Bien::create(['no_inventario' => 'INV-DEL', 'nombre_bien' => 'Eliminar', 'estatus' => 'Disponible', 'fecha_registro' => now()]);
        $bien->delete();

        $this->actingAs($admin)
            ->delete(route('admin.bienes.force-destroy', $bien))
            ->assertRedirect(route('admin.bienes.papelera'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bienes', ['id_bien' => $bien->id_bien]);
    }

    public function test_no_inventario_does_not_reuse_papelera_folio(): void
    {
        $admin = User::factory()->admin()->create();
        Bien::create(['no_inventario' => 'INV-00005', 'nombre_bien' => 'En papelera', 'estatus' => 'Disponible', 'fecha_registro' => now()])->delete();

        $this->actingAs($admin)->post(route('admin.bienes.store'), [
            'nombre_bien' => 'Folio nuevo',
            'estatus' => 'Disponible',
        ])->assertRedirect(route('admin.bienes'));

        $this->assertDatabaseHas('bienes', ['nombre_bien' => 'Folio nuevo', 'no_inventario' => 'INV-00006']);
    }

    public function test_manual_bien_alta_allows_duplicate_nombre(): void
    {
        $admin = User::factory()->admin()->create();
        Bien::create(['no_inventario' => 'INV-DUP', 'nombre_bien' => 'Laptop Duplicada', 'estatus' => 'Disponible', 'fecha_registro' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.bienes.store'), [
                'nombre_bien' => 'Laptop Duplicada',
                'estatus' => 'Disponible',
            ])
            ->assertRedirect(route('admin.bienes'))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Bien::where('nombre_bien', 'Laptop Duplicada')->count());
    }

    public function test_duplicate_area_name_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Area::create(['nombre_area' => 'Direccion', 'estatus' => 'Activa', 'fecha_registro' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.areas.store'), [
                'nombre_area' => 'Direccion',
                'estatus' => 'Activa',
            ])
            ->assertSessionHasErrors('nombre_area');
    }

    public function test_historial_keeps_bien_name_after_soft_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $bien = Bien::create(['no_inventario' => 'INV-HIST2', 'nombre_bien' => 'Bien Eliminado', 'estatus' => 'Disponible', 'fecha_registro' => now()]);
        $historial = \App\Models\HistorialAsignacion::create([
            'id_bien' => $bien->id_bien,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => 'Asignacion',
            'observaciones' => 'Test',
        ]);
        $bien->delete();

        $this->actingAs($admin)
            ->get(route('admin.historial', ['search' => 'Bien Eliminado']))
            ->assertOk()
            ->assertSee('Bien Eliminado');

        $this->assertNotNull($historial->fresh()->bien);
    }

    public function test_import_treats_numeric_id_area_as_existing_id(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::create(['nombre_area' => 'Sistemas', 'estatus' => 'Activa', 'fecha_registro' => now()]);

        $this->importarCsv([
            $this->filaCsv(nombre: 'Computadora A', serie: 'SN-NUM', idArea: (string) $area->id_area),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertDatabaseHas('bienes', ['nombre_bien' => 'Computadora A', 'id_area' => $area->id_area]);
        $this->assertSame(1, Area::count());
    }

    public function test_import_updates_existing_bien_matching_by_id_sep(): void
    {
        $bien = Bien::create([
            'id_sep' => 'SEP-EXIST1',
            'no_inventario' => 'INV-UPD1',
            'nombre_bien' => 'Nombre Original',
            'marca' => 'Marca Original',
            'modelo' => 'Modelo Original',
            'serie' => 'SN-ORIG',
            'codigo_barras' => 'COD-ORIG1',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->importarCsv([
            $this->filaCsv(idSep: 'SEP-EXIST1', nombre: 'Nombre Actualizado', marca: 'Marca Nueva', modelo: 'Modelo Nuevo', serie: 'SN-NUEVA'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(1, Bien::count());

        $bien->refresh();
        $this->assertSame('Nombre Actualizado', $bien->nombre_bien);
        $this->assertSame('Marca Nueva', $bien->marca);
        $this->assertSame('Modelo Nuevo', $bien->modelo);
        $this->assertSame('SN-NUEVA', $bien->serie);
        $this->assertSame('INV-UPD1', $bien->no_inventario);
        $this->assertSame('COD-ORIG1', $bien->codigo_barras);
    }

    public function test_import_update_keeps_existing_identifiers(): void
    {
        $bien = Bien::create([
            'id_sep' => 'SEP-IMPORT1',
            'no_inventario' => 'INV-IMPORT1',
            'nombre_bien' => 'Nombre Original',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->importarCsv([
            implode(',', ['INV-IMPORT1', 'SEP-CHANGE1', 'Nombre Importado']),
        ], ['no_inventario', 'id_sep', 'nombre_bien'])->assertRedirect(route('admin.bienes'));

        $bien->refresh();

        $this->assertSame('SEP-IMPORT1', $bien->id_sep);
        $this->assertSame('INV-IMPORT1', $bien->no_inventario);
        $this->assertSame('Nombre Importado', $bien->nombre_bien);
    }

    public function test_import_mixes_created_and_updated_bienes(): void
    {
        Bien::create([
            'id_sep' => 'SEP-EXIST3',
            'no_inventario' => 'INV-UPD3',
            'nombre_bien' => 'Existente Original',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->importarCsv([
            $this->filaCsv(idSep: 'SEP-EXIST3', nombre: 'Existente Actualizado'),
            $this->filaCsv(nombre: 'Nuevo Bien', serie: 'SN-NUEVO1'),
        ])->assertRedirect(route('admin.bienes'));

        $this->assertSame(2, Bien::count());
        $this->assertDatabaseHas('bienes', ['nombre_bien' => 'Existente Actualizado']);
        $this->assertDatabaseHas('bienes', ['nombre_bien' => 'Nuevo Bien']);

        $mensaje = session('success');
        $this->assertIsString($mensaje);
        $this->assertStringContainsString('Se actualizaron 1 registros existentes', $mensaje);
        $this->assertStringContainsString('Se importaron 1 bienes correctamente', $mensaje);
    }

    public function test_import_rejects_conflicting_unique_keys_across_different_bienes(): void
    {
        Bien::create([
            'id_sep' => 'SEP-EXIST4',
            'no_inventario' => 'INV-UPD4',
            'nombre_bien' => 'Bien Uno',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);
        Bien::create([
            'no_inventario' => 'INV-UPD5',
            'codigo_barras' => 'COD-CONFLICT',
            'nombre_bien' => 'Bien Dos',
            'estatus' => 'Disponible',
            'fecha_registro' => now(),
        ]);

        $this->importarCsv([
            $this->filaCsv(idSep: 'SEP-EXIST4', nombre: 'Conflicto', codigo: 'COD-CONFLICT'),
        ]);

        $this->assertSame(2, Bien::count());

        $mensaje = session('success');
        $this->assertIsString($mensaje);
        $this->assertStringContainsString('error', strtolower($mensaje));
    }
}
