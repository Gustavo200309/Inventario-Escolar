<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->string('nombre_rol', 100)->unique();
            $table->string('estatus', 20)->default('Activo');
            $table->timestamp('fecha_creacion')->nullable();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 30)->default('visualizador');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id('id_area');
            $table->string('nombre_area', 150);
            $table->text('descripcion')->nullable();
            $table->string('estatus', 30)->default('Activa');
            $table->timestamp('fecha_registro')->nullable();
        });

        Schema::create('personal', function (Blueprint $table) {
            $table->id('id_personal');
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('puesto', 100);
            $table->string('correo', 150)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->foreignId('id_area')->nullable()
                ->constrained('areas', 'id_area')
                ->nullOnDelete();
            $table->string('estatus', 30)->default('Activo');
            $table->timestamp('fecha_registro')->nullable();
        });

        Schema::create('bienes', function (Blueprint $table) {
            $table->id('id_bien');
            $table->string('id_sep', 50)->nullable();
            $table->string('no_inventario', 100);
            $table->string('nombre_bien');
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('serie', 150)->nullable();
            $table->string('adq', 100)->nullable();
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('resguardo_excel')->nullable();
            $table->string('codigo_barras', 200)->nullable();
            $table->foreignId('id_area')->nullable()
                ->constrained('areas', 'id_area')
                ->nullOnDelete();
            $table->foreignId('id_personal')->nullable()
                ->constrained('personal', 'id_personal')
                ->nullOnDelete();
            $table->string('estatus', 30)->default('Disponible');
            $table->timestamp('fecha_registro')->nullable();
        });

        Schema::create('historial_asignaciones', function (Blueprint $table) {
            $table->id('id_historial');
            $table->foreignId('id_bien')
                ->constrained('bienes', 'id_bien')
                ->cascadeOnDelete();
            $table->foreignId('id_personal_anterior')->nullable()
                ->constrained('personal', 'id_personal')
                ->nullOnDelete();
            $table->foreignId('id_personal_nuevo')->nullable()
                ->constrained('personal', 'id_personal')
                ->nullOnDelete();
            $table->foreignId('id_area_anterior')->nullable()
                ->constrained('areas', 'id_area')
                ->nullOnDelete();
            $table->foreignId('id_area_nueva')->nullable()
                ->constrained('areas', 'id_area')
                ->nullOnDelete();
            $table->timestamp('fecha_movimiento')->nullable();
            $table->string('tipo_movimiento', 100);
            $table->text('observaciones')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_asignaciones');
        Schema::dropIfExists('bienes');
        Schema::dropIfExists('personal');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
